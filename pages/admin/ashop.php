<?php
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../../includes/paths.php';
require_once $project_root . 'includes/session.php';
require_once $project_root . 'languages/language.php';
require_once $project_root . 'includes/config.settings.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'moderator'])) {
    header("Location: {$base_path}login");
    exit;
}

$page_class = 'ashop';

// Pagination settings
$items_per_page = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $items_per_page;

// Handle filter and search
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$valid_categories = ['Mount', 'Pet', 'Gold', 'Service', 'Stuff', 'Set'];

// Fetch all available site items for Mount, Pet, Stuff dropdown
$site_items = [];
$sql = "SELECT entry, name FROM site_items ORDER BY name";
$stmt = $site_db->prepare($sql);
if ($stmt->execute()) {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $site_items[] = $row;
    }
}
$stmt->close();

// Preload item set composition for admin preview.
$site_itemsets = [];
$sql = "SELECT itemset, entry, name FROM site_items WHERE itemset > 0 ORDER BY itemset, entry";
$stmt = $site_db->prepare($sql);
if ($stmt->execute()) {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $itemset_id = (int)$row['itemset'];
        if (!isset($site_itemsets[$itemset_id])) {
            $site_itemsets[$itemset_id] = [];
        }
        $site_itemsets[$itemset_id][] = [
            'entry' => (int)$row['entry'],
            'name' => $row['name']
        ];
    }
}
$stmt->close();

// CSRF Token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Directory for image uploads
$base_upload_dir = $project_root . 'img/shopimg/';
$base_upload_url = 'img/shopimg/';

// Map categories to subdirectories
$category_dirs = [
    'Gold' => 'gold',
    'Mount' => 'items',
    'Pet' => 'items',
    'Stuff' => 'items',
    'Set' => 'items',
    'Service' => 'services'
];

// Ensure upload subdirectories exist and are writable
foreach ($category_dirs as $dir) {
    $full_dir = $base_upload_dir . $dir;
    if (!file_exists($full_dir)) {
        mkdir($full_dir, 0755, true);
    }
}

// Helper functions for alerts
$alert_danger = function($msg) {
    return '<div class="bg-red-900/40 border border-red-500/50 text-red-300 px-4 py-3 rounded-sm mb-6 flex items-center gap-3 shadow-lg">
        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
        <span>' . $msg . '</span>
    </div>';
};
$alert_success = function($msg) {
    return '<div class="bg-green-900/40 border border-green-500/50 text-green-300 px-4 py-3 rounded-sm mb-6 flex items-center gap-3 shadow-lg">
        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
        <span>' . $msg . '</span>
    </div>';
};

// Status messages
$update_message = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success') {
        $update_message = $alert_success(htmlspecialchars($_GET['message'] ?? translate('admin_shop_operation_success', 'Operation successful!')));
    } else {
        $update_message = $alert_danger(htmlspecialchars($_GET['message'] ?? translate('admin_shop_operation_error', 'An error occurred.')));
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: {$base_path}admin/ashop?status=error&message=" . urlencode(translate('admin_shop_csrf_error', 'CSRF token validation failed.')));
        exit;
    }

    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add' || $action === 'edit') {
            $item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : null;
            $category = $_POST['category'] ?? '';
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? null;
            $point_cost = (int)($_POST['point_cost'] ?? 0);
            $token_cost = (int)($_POST['token_cost'] ?? 0);
            $stock = isset($_POST['stock']) && $_POST['stock'] !== '' ? (int)$_POST['stock'] : null;
            $entry = isset($_POST['entry']) && $_POST['entry'] !== '' ? (int)$_POST['entry'] : null;
            $gold_amount = (int)($_POST['gold_amount'] ?? 0);
            $level_boost = isset($_POST['level_boost']) && $_POST['level_boost'] !== '' ? (int)$_POST['level_boost'] : null;
            $at_login_flags = (int)($_POST['at_login_flags'] ?? 0);
            $is_item = (int)($_POST['is_item'] ?? 0);
            $is_set = ($category === 'Set') ? 1 : 0;
            $itemset_id = isset($_POST['itemset_id']) && $_POST['itemset_id'] !== '' ? (int)$_POST['itemset_id'] : null;
            $image = null;

            if (!in_array($category, $valid_categories)) {
                header("Location: {$base_path}admin/ashop?status=error&message=" . urlencode(translate('admin_shop_invalid_category', 'Invalid category')) . "&page=$page" . ($category_filter ? "&category=$category_filter" : "") . ($search_query ? "&search=" . urlencode($search_query) : ""));
                exit;
            }

            if (in_array($category, ['Mount', 'Pet', 'Stuff', 'Set'], true)) {
                $is_item = 1;

                if ($is_set === 1) {
                    if ($itemset_id === null || $itemset_id <= 0) {
                        header("Location: {$base_path}admin/ashop?status=error&message=" . urlencode(translate('admin_shop_invalid_itemset_id', 'Invalid item set ID')) . "&page=$page" . ($category_filter ? "&category=$category_filter" : "") . ($search_query ? "&search=" . urlencode($search_query) : ""));
                        exit;
                    }

                    $sql = "SELECT COUNT(*) FROM site_items WHERE itemset = ?";
                    $stmt = $site_db->prepare($sql);
                    $stmt->bind_param("i", $itemset_id);
                    $stmt->execute();
                    $stmt->bind_result($count);
                    $stmt->fetch();
                    $stmt->close();
                    if ($count == 0) {
                        header("Location: {$base_path}admin/ashop?status=error&message=" . urlencode(translate('admin_shop_invalid_itemset_id', 'Invalid item set ID')) . "&page=$page" . ($category_filter ? "&category=$category_filter" : "") . ($search_query ? "&search=" . urlencode($search_query) : ""));
                        exit;
                    }
                    $entry = null;
                } else {
                    $itemset_id = null;

                    if ($entry === null || $entry <= 0) {
                        header("Location: {$base_path}admin/ashop?status=error&message=" . urlencode(translate('admin_shop_invalid_entry_id', 'Invalid entry ID')) . "&page=$page" . ($category_filter ? "&category=$category_filter" : "") . ($search_query ? "&search=" . urlencode($search_query) : ""));
                        exit;
                    }

                    $sql = "SELECT COUNT(*) FROM site_items WHERE entry = ?";
                    $stmt = $site_db->prepare($sql);
                    $stmt->bind_param("i", $entry);
                    $stmt->execute();
                    $stmt->bind_result($count);
                    $stmt->fetch();
                    $stmt->close();
                    if ($count == 0) {
                        header("Location: {$base_path}admin/ashop?status=error&message=" . urlencode(translate('admin_shop_invalid_entry_id', 'Invalid entry ID')) . "&page=$page" . ($category_filter ? "&category=$category_filter" : "") . ($search_query ? "&search=" . urlencode($search_query) : ""));
                        exit;
                    }
                }
            }

            if ($category === 'Service' && $level_boost !== null && ($level_boost < 2 || $level_boost > 255)) {
                header("Location: {$base_path}admin/ashop?status=error&message=" . urlencode(translate('admin_shop_invalid_level_boost', 'Level boost must be between 2 and 255')) . "&page=$page" . ($category_filter ? "&category=$category_filter" : "") . ($search_query ? "&search=" . urlencode($search_query) : ""));
                exit;
            }

            $upload_subdir = $category_dirs[$category] ?? 'items';
            $upload_dir = $base_upload_dir . $upload_subdir . DIRECTORY_SEPARATOR;
            $upload_url = $base_upload_url . $upload_subdir . '/';

            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 2 * 1024 * 1024;
                $file = $_FILES['image'];

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $error_messages = [
                        UPLOAD_ERR_INI_SIZE => translate('admin_shop_upload_err_ini_size', 'File size exceeds server limit'),
                        UPLOAD_ERR_FORM_SIZE => translate('admin_shop_upload_err_form_size', 'File size exceeds form limit'),
                        UPLOAD_ERR_PARTIAL => translate('admin_shop_upload_err_partial', 'File was only partially uploaded'),
                        UPLOAD_ERR_NO_FILE => translate('admin_shop_upload_err_no_file', 'No file was uploaded'),
                        UPLOAD_ERR_NO_TMP_DIR => translate('admin_shop_upload_err_no_tmp_dir', 'Missing temporary directory'),
                        UPLOAD_ERR_CANT_WRITE => translate('admin_shop_upload_err_cant_write', 'Failed to write file to disk'),
                        UPLOAD_ERR_EXTENSION => translate('admin_shop_upload_err_extension', 'A PHP extension stopped the upload')
                    ];
                    $error_message = isset($error_messages[$file['error']]) ? $error_messages[$file['error']] : translate('admin_shop_upload_err_unknown', 'Unknown upload error');
                    header("Location: {$base_path}admin/ashop?status=error&message=" . urlencode($error_message) . "&page=$page" . ($category_filter ? "&category=$category_filter" : "") . ($search_query ? "&search=" . urlencode($search_query) : ""));
                    exit;
                }

                if (!in_array($file['type'], $allowed_types)) {
                    header("Location: {$base_path}admin/ashop?status=error&message=" . urlencode(translate('admin_shop_invalid_file_type', 'Invalid file type. Only JPG, PNG, GIF allowed')) . "&page=$page" . ($category_filter ? "&category=$category_filter" : "") . ($search_query ? "&search=" . urlencode($search_query) : ""));
                    exit;
                }
                if ($file['size'] > $max_size) {
                    header("Location: {$base_path}admin/ashop?status=error&message=" . urlencode(translate('admin_shop_file_size_exceeded', 'File size exceeds 2MB limit')) . "&page=$page" . ($category_filter ? "&category=$category_filter" : "") . ($search_query ? "&search=" . urlencode($search_query) : ""));
                    exit;
                }

                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $filename = uniqid('shop_item_') . '.' . $ext;
                $destination = $upload_dir . $filename;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $image = $upload_url . $filename;
                    if ($action === 'edit' && isset($_POST['existing_image']) && $_POST['existing_image']) {
                        $old_image_path = str_replace($base_upload_url, $base_upload_dir, $_POST['existing_image']);
                        if (file_exists($old_image_path)) {
                            unlink($old_image_path);
                        }
                    }
                } else {
                    header("Location: {$base_path}admin/ashop?status=error&message=" . urlencode(translate('admin_shop_upload_failed', 'Failed to upload file')) . "&page=$page" . ($category_filter ? "&category=$category_filter" : "") . ($search_query ? "&search=" . urlencode($search_query) : ""));
                    exit;
                }
            } elseif ($action === 'edit' && isset($_POST['existing_image'])) {
                $image = $_POST['existing_image'];
            }

            if ($category === 'Gold') {
                $entry = null;
                $itemset_id = null;
                $level_boost = null;
                $at_login_flags = 0;
                $is_item = 0;
                $is_set = 0;
            } elseif ($category === 'Mount' || $category === 'Pet' || $category === 'Stuff' || $category === 'Set') {
                $description = null;
                $gold_amount = 0;
                $level_boost = null;
                $at_login_flags = 0;
                if ($is_set !== 1) {
                    $itemset_id = null;
                }
            } elseif ($category === 'Service') {
                $entry = null;
                $itemset_id = null;
                $gold_amount = 0;
                $is_item = 0;
                $is_set = 0;
            }

            try {
                if ($action === 'add') {
                    $sql = "INSERT INTO shop_items (category, name, description, image, point_cost, token_cost, stock, entry, gold_amount, level_boost, at_login_flags, is_item, is_set, itemset_id) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $site_db->prepare($sql);
                    $stmt->bind_param("ssssiiiiiiiiii", $category, $name, $description, $image, $point_cost, $token_cost, $stock, $entry, $gold_amount, $level_boost, $at_login_flags, $is_item, $is_set, $itemset_id);
                } else {
                    $sql = "UPDATE shop_items SET category = ?, name = ?, description = ?, image = ?, point_cost = ?, token_cost = ?, stock = ?, entry = ?, gold_amount = ?, level_boost = ?, at_login_flags = ?, is_item = ?, is_set = ?, itemset_id = ? WHERE item_id = ?";
                    $stmt = $site_db->prepare($sql);
                    $stmt->bind_param("ssssiiiiiiiiiii", $category, $name, $description, $image, $point_cost, $token_cost, $stock, $entry, $gold_amount, $level_boost, $at_login_flags, $is_item, $is_set, $itemset_id, $item_id);
                }
                
                if ($stmt->execute()) {
                    header("Location: {$base_path}admin/ashop?status=success&message=" . urlencode(translate('admin_shop_operation_success', 'Operation successful!')) . "&page=$page" . ($category_filter ? "&category=$category_filter" : "") . ($search_query ? "&search=" . urlencode($search_query) : ""));
                    exit;
                } else {
                    throw new Exception(sprintf(translate('admin_shop_db_error', 'Database error: %s'), $stmt->error));
                }
            } catch (Exception $e) {
                error_log("Database error: " . $e->getMessage(), 3, $project_root . 'logs/upload_errors.log');
                header("Location: {$base_path}admin/ashop?status=error&message=" . urlencode($e->getMessage()) . "&page=$page" . ($category_filter ? "&category=$category_filter" : "") . ($search_query ? "&search=" . urlencode($search_query) : ""));
                exit;
            } finally {
                if (isset($stmt)) $stmt->close();
            }
        } elseif ($action === 'delete') {
            $item_id = (int)$_POST['item_id'];
            try {
                $sql = "SELECT image FROM shop_items WHERE item_id = ?";
                $stmt = $site_db->prepare($sql);
                $stmt->bind_param("i", $item_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    if ($row['image']) {
                        $file_path = str_replace($base_upload_url, $base_upload_dir, $row['image']);
                        if (file_exists($file_path)) {
                            unlink($file_path);
                        }
                    }
                }
                $stmt->close();

                $sql = "DELETE FROM shop_items WHERE item_id = ?";
                $stmt = $site_db->prepare($sql);
                $stmt->bind_param("i", $item_id);
                
                if ($stmt->execute()) {
                    header("Location: {$base_path}admin/ashop?status=success&message=" . urlencode(translate('admin_shop_operation_success', 'Operation successful!')) . "&page=$page" . ($category_filter ? "&category=$category_filter" : "") . ($search_query ? "&search=" . urlencode($search_query) : ""));
                    exit;
                } else {
                    throw new Exception(sprintf(translate('admin_shop_db_error', 'Database error: %s'), $stmt->error));
                }
            } catch (Exception $e) {
                error_log("Delete error: " . $e->getMessage(), 3, $project_root . 'logs/upload_errors.log');
                header("Location: {$base_path}admin/ashop?status=error&message=" . urlencode($e->getMessage()) . "&page=$page" . ($category_filter ? "&category=$category_filter" : "") . ($search_query ? "&search=" . urlencode($search_query) : ""));
                exit;
            } finally {
                if (isset($stmt)) $stmt->close();
            }
        }
    }
}

// Count total items for pagination
$total_items = 0;
$total_pages = 1;
try {
    $count_sql = "SELECT COUNT(*) as total FROM shop_items WHERE 1=1";
    $count_params = [];
    $count_types = "";
    
    if ($category_filter && in_array($category_filter, $valid_categories)) {
        $count_sql .= " AND category = ?";
        $count_params[] = $category_filter;
        $count_types .= "s";
    }
    if ($search_query) {
        $count_sql .= " AND name LIKE ?";
        $count_params[] = "%$search_query%";
        $count_types .= "s";
    }

    $count_stmt = $site_db->prepare($count_sql);
    if (!empty($count_params)) {
        $count_stmt->bind_param($count_types, ...$count_params);
    }
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    if ($count_result) {
        $total_items = $count_result->fetch_assoc()['total'];
        $total_pages = ceil($total_items / $items_per_page);
    }
    $count_stmt->close();
} catch (Exception $e) {
    error_log("Error counting shop items: " . $e->getMessage(), 3, $project_root . 'logs/upload_errors.log');
}

// Fetch shop items for current page
$items = [];
try {
    $sql = "SELECT si.*, sit.name as entry_name, sis.set_item_count
            FROM shop_items si
            LEFT JOIN site_items sit ON si.entry = sit.entry
            LEFT JOIN (
                SELECT itemset, COUNT(*) AS set_item_count
                FROM site_items
                WHERE itemset > 0
                GROUP BY itemset
            ) sis ON si.itemset_id = sis.itemset
            WHERE 1=1";
    $params = [];
    $types = "";
    
    if ($category_filter && in_array($category_filter, $valid_categories)) {
        $sql .= " AND si.category = ?";
        $params[] = $category_filter;
        $types .= "s";
    }
    if ($search_query) {
        $sql .= " AND si.name LIKE ?";
        $params[] = "%$search_query%";
        $types .= "s";
    }
    $sql .= " ORDER BY si.category, si.name LIMIT ? OFFSET ?";
    $params[] = $items_per_page;
    $params[] = $offset;
    $types .= "ii";

    $stmt = $site_db->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
} catch (Exception $e) {
    error_log("Error fetching shop items: " . $e->getMessage(), 3, $project_root . 'logs/upload_errors.log');
} finally {
    if (isset($stmt)) $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo translate('admin_shop_meta_description', 'Shop Management for Sahtout WoW Server'); ?>">
    <meta name="robots" content="noindex">
    <title><?php echo translate('admin_shop_page_title', 'Shop Management'); ?></title>
    <link rel="icon" href="<?php echo $base_path . $site_logo; ?>" type="image/x-icon">
    <link rel="stylesheet" href="<?php echo $base_path; ?>node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&family=Cinzel:wght@600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/tailwind.css">
    
    <style>
        * { font-family: 'Inter', sans-serif; }

        body {
            min-height: 100vh;
            color: #d8d8d8;
            background-color: #05070b;
            background-image:
                radial-gradient(1000px 700px at -10% 35%, rgba(59,130,246,.14), transparent 65%),
                radial-gradient(800px 600px at -5% 85%, rgba(124,58,237,.10), transparent 70%),
                linear-gradient(180deg, #0a0e16 0%, #060810 45%, #03040a 100%);
            background-attachment: fixed;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image:
                radial-gradient(2px 2px at 10% 20%, rgba(242,207,82,.7), transparent 55%),
                radial-gradient(1.5px 1.5px at 30% 70%, rgba(242,207,82,.5), transparent 55%),
                radial-gradient(2px 2px at 55% 40%, rgba(255,160,60,.55), transparent 55%);
            background-size: 900px 700px;
            animation: emberDrift 45s linear infinite;
            opacity: .4;
            pointer-events: none;
            z-index: 0;
        }
        @keyframes emberDrift {
            from { background-position: 0 0; }
            to   { background-position: 900px -700px; }
        }

        .wow-panel {
            position: relative;
            background: linear-gradient(180deg, rgba(22,25,32,.92), rgba(8,10,14,.9));
            border: 1px solid rgba(201,162,39,.22);
            box-shadow: 0 12px 32px rgba(0,0,0,.55), inset 0 0 60px rgba(0,0,0,.45);
            border-radius: 0;
        }

        .wow-panel::before {
            content: '';
            position: absolute;
            inset: 5px;
            border: 1px solid rgba(201,162,39,.14);
            pointer-events: none;
        }

        .wow-panel::after {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                linear-gradient(#e8c552,#e8c552) left top / 18px 2px,
                linear-gradient(#e8c552,#e8c552) left top / 2px 18px,
                linear-gradient(#e8c552,#e8c552) right top / 18px 2px,
                linear-gradient(#e8c552,#e8c552) right top / 2px 18px,
                linear-gradient(#e8c552,#e8c552) left bottom / 18px 2px,
                linear-gradient(#e8c552,#e8c552) left bottom / 2px 18px,
                linear-gradient(#e8c552,#e8c552) right bottom / 18px 2px,
                linear-gradient(#e8c552,#e8c552) right bottom / 2px 18px;
            background-repeat: no-repeat;
        }

        .wow-title {
            font-family: 'Cinzel', serif;
            font-weight: 900;
            background: linear-gradient(180deg, #fff7d6 0%, #f2cf5b 35%, #c9a227 62%, #8a6a14 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 3px 6px rgba(0,0,0,.85));
            letter-spacing: .02em;
        }

        .section-title {
            font-family: 'Cinzel', serif;
            font-weight: 700;
            color: #f2cf5b;
            text-shadow: 0 0 12px rgba(201,162,39,.35), 0 2px 4px rgba(0,0,0,.8);
            display: flex;
            align-items: center;
            gap: .6rem;
        }

        .btn-game {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            padding: .75rem 1.5rem;
            font-weight: 800;
            font-size: .85rem;
            letter-spacing: .04em;
            text-transform: uppercase;
            clip-path: polygon(10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%, 0 10px);
            transition: transform .2s ease, filter .2s ease;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }

        .btn-game:hover {
            transform: translateY(-2px) scale(1.02);
        }

        .btn-gold {
            background: linear-gradient(180deg, #f6d478 0%, #c9a227 48%, #8a6a14 100%);
            color: #1a1200;
            text-shadow: 0 1px 0 rgba(255,255,255,.35);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.28), inset 0 -8px 14px rgba(0,0,0,.25);
        }

        .btn-iron {
            background: linear-gradient(180deg, #3a404d 0%, #232833 55%, #141821 100%);
            color: #cfe1ff;
            box-shadow: inset 0 0 0 1px rgba(120,160,255,.25), inset 0 -8px 14px rgba(0,0,0,.4);
            filter: drop-shadow(0 0 8px rgba(59,130,246,.25));
        }

        .btn-danger {
            background: linear-gradient(180deg, #ff4d4d 0%, #cc0000 48%, #8a0000 100%);
            color: #fff;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.2), inset 0 -8px 14px rgba(0,0,0,.3);
        }

        .btn-edit-action {
            background: linear-gradient(180deg, #4a90d9 0%, #2a5f8a 48%, #1a3f5a 100%);
            color: #fff;
            box-shadow: inset 0 0 0 1px rgba(100,180,255,.25), inset 0 -8px 14px rgba(0,0,0,.3);
        }

        .wow-input, .wow-select, .wow-textarea {
            width: 100%;
            background: rgba(10, 14, 22, 0.8);
            border: 1px solid rgba(201,162,39,.3);
            color: #e5e7eb;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            outline: none;
            border-radius: 0;
        }

        .wow-input:focus, .wow-select:focus, .wow-textarea:focus {
            border-color: #f2cf5b;
            box-shadow: 0 0 10px rgba(242,207,82,.2);
            background: rgba(15, 20, 30, 0.9);
        }

        .wow-input:disabled, .wow-select:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .wow-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .wow-table th {
            background: rgba(10, 14, 22, 0.9);
            color: #f2cf5b;
            font-family: 'Cinzel', serif;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.05em;
            padding: 1rem;
            border-bottom: 2px solid rgba(201,162,39,.4);
            text-align: left;
        }

        .wow-table td {
            padding: 1rem;
            border-bottom: 1px solid rgba(201,162,39,.1);
            color: #d8d8d8;
            background: rgba(22, 25, 32, 0.6);
            vertical-align: middle;
        }

        .wow-table tr:hover td {
            background: rgba(30, 35, 45, 0.8);
        }

        .category-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: rgba(201,162,39,0.15);
            color: #f2cf5b;
            border: 1px solid rgba(201,162,39,0.2);
        }

        .upload-area {
            border: 2px dashed rgba(201,162,39,.2);
            background: rgba(10, 14, 22, 0.5);
            transition: all 0.3s ease;
            cursor: pointer;
            padding: 1.5rem;
            text-align: center;
        }

        .upload-area:hover {
            border-color: rgba(201,162,39,.4);
            background: rgba(15, 20, 30, 0.7);
        }

        .upload-area.dragover {
            border-color: #f2cf5b;
            background: rgba(201,162,39,.08);
        }

        .modal-backdrop {
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
        }

        .modal-backdrop .wow-panel {
            max-height: 90vh;
            overflow-y: auto;
        }

        .image-preview {
            max-height: 80px;
            margin-top: 0.75rem;
            border: 1px solid rgba(201,162,39,.2);
            display: none;
        }

        .image-preview.active {
            display: block;
        }

        .field-group {
            display: none;
        }

        .field-group.active {
            display: block;
        }

        .itemset-preview {
            background: rgba(10, 14, 22, 0.6);
            border: 1px solid rgba(201,162,39,.15);
            padding: 1rem;
            max-height: 150px;
            overflow-y: auto;
        }

        .itemset-preview ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .itemset-preview ul li {
            padding: 0.25rem 0;
            color: #b0b8c8;
            font-size: 0.9rem;
            border-bottom: 1px solid rgba(201,162,39,.05);
        }

        .itemset-preview ul li:last-child {
            border-bottom: none;
        }

        .text-muted-wow {
            color: #6a7a8a;
        }

        .border-gold {
            border-color: rgba(201,162,39,0.2);
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(10, 14, 22, 0.5);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(201,162,39,.3);
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(201,162,39,.5);
        }

        /* Main content area - works with sidebar */
        .main-content-area {
            transition: margin-left 0.3s ease;
            min-height: calc(100vh - 72px);
            width: 100%;
        }

        /* Content wrapper with proper spacing */
        .content-wrapper {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        @media (min-width: 640px) {
            .content-wrapper {
                padding: 0 1.5rem;
            }
        }

        @media (min-width: 1024px) {
            .content-wrapper {
                padding: 0 2rem;
            }
        }

        @media (min-width: 1280px) {
            .content-wrapper {
                padding: 0 2.5rem;
            }
        }

        @media (min-width: 1024px) {
            .main-content-area.lg\:ml-0 {
                margin-left: 0;
            }
            .main-content-area.lg\:ml-\[280px\] {
                margin-left: 280px;
            }
        }

        @media (max-width: 1023px) {
            .main-content-area {
                margin-left: 0 !important;
                padding: 1rem;
            }
            .content-wrapper {
                padding: 0 0.5rem;
            }
        }
    </style>
</head>
<body class="shop">
    <?php include $project_root . 'includes/header.php'; ?>

    <!-- Main Content Area with Sidebar -->
    <div class="flex relative min-h-screen">
        
        <!-- Sidebar -->
        <?php include $project_root . 'includes/admin_sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content-area flex-1 p-3 sm:p-4 md:p-6 lg:p-8 transition-all duration-300 lg:ml-[280px]">
            <div class="content-wrapper">
                <div class="space-y-4 md:space-y-6 lg:space-y-8">
                    
                    <h1 class="wow-title text-2xl md:text-3xl lg:text-4xl"><?php echo translate('admin_shop_title', 'Shop Management'); ?></h1>
                    
                    <?php echo $update_message; ?>

                    <!-- Add/Edit Shop Item Form -->
                    <div class="wow-panel p-4 md:p-6 lg:p-8">
                        <h2 class="section-title text-lg md:text-xl mb-4 md:mb-6"><?php echo translate('admin_shop_add_edit_header', 'Add / Edit Shop Item'); ?></h2>
                        <form method="POST" enctype="multipart/form-data" id="itemForm">
                            <input type="hidden" name="action" value="add" id="formAction">
                            <input type="hidden" name="item_id" id="item_id">
                            <input type="hidden" name="existing_image" id="existing_image">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                                <!-- Category -->
                                <div class="field-group active">
                                    <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_shop_label_category', 'Category'); ?></label>
                                    <select name="category" id="category" class="wow-select" required>
                                        <option value="Mount"><?php echo translate('admin_shop_category_mount', 'Mount'); ?></option>
                                        <option value="Pet"><?php echo translate('admin_shop_category_pet', 'Pet'); ?></option>
                                        <option value="Gold"><?php echo translate('admin_shop_category_gold', 'Gold'); ?></option>
                                        <option value="Service"><?php echo translate('admin_shop_category_service', 'Service'); ?></option>
                                        <option value="Stuff"><?php echo translate('admin_shop_category_stuff', 'Stuff'); ?></option>
                                        <option value="Set"><?php echo translate('admin_shop_category_set', 'Set'); ?></option>
                                    </select>
                                </div>

                                <!-- Name -->
                                <div class="field-group active">
                                    <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_shop_label_name', 'Name'); ?></label>
                                    <input type="text" name="name" id="name" class="wow-input" required maxlength="100" placeholder="<?php echo translate('admin_shop_placeholder_name', 'Enter item name'); ?>">
                                </div>

                                <!-- Point Cost -->
                                <div class="field-group active">
                                    <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_shop_label_point_cost', 'Point Cost'); ?></label>
                                    <input type="number" name="point_cost" id="point_cost" class="wow-input" min="0" required placeholder="<?php echo translate('admin_shop_placeholder_point_cost', 'Enter point cost'); ?>">
                                </div>

                                <!-- Token Cost -->
                                <div class="field-group active">
                                    <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_shop_label_token_cost', 'Token Cost'); ?></label>
                                    <input type="number" name="token_cost" id="token_cost" class="wow-input" min="0" required placeholder="<?php echo translate('admin_shop_placeholder_token_cost', 'Enter token cost'); ?>">
                                </div>

                                <!-- Stock -->
                                <div class="field-group active">
                                    <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_shop_label_stock', 'Stock'); ?></label>
                                    <input type="number" name="stock" id="stock" class="wow-input" min="0" placeholder="<?php echo translate('admin_shop_placeholder_stock', 'Leave empty for unlimited'); ?>">
                                </div>

                                <!-- Entry (Item) -->
                                <div class="field-group" id="entry-group">
                                    <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_shop_label_entry', 'Item Entry'); ?></label>
                                    <select name="entry" id="entry" class="wow-select">
                                        <option value=""><?php echo translate('admin_shop_select_entry', 'Select Entry'); ?></option>
                                        <?php foreach ($site_items as $item): ?>
                                            <option value="<?php echo $item['entry']; ?>">
                                                <?php echo htmlspecialchars($item['entry'] . ' - ' . $item['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Itemset ID -->
                                <div class="field-group" id="itemset-group">
                                    <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_shop_label_itemset_id', 'Item Set ID'); ?></label>
                                    <input type="number" name="itemset_id" id="itemset_id" class="wow-input" min="1" placeholder="<?php echo translate('admin_shop_placeholder_itemset_id', 'Enter AzerothCore itemset ID'); ?>">
                                </div>

                                <!-- Gold Amount -->
                                <div class="field-group" id="gold-group">
                                    <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_shop_label_gold_amount', 'Gold Amount'); ?></label>
                                    <input type="number" name="gold_amount" id="gold_amount" class="wow-input" min="0" value="0" placeholder="<?php echo translate('admin_shop_placeholder_gold_amount', 'Enter gold amount'); ?>">
                                </div>

                                <!-- Level Boost -->
                                <div class="field-group" id="level-boost-group">
                                    <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_shop_label_level_boost', 'Level Boost (2-255)'); ?></label>
                                    <input type="number" name="level_boost" id="level_boost" class="wow-input" min="2" max="255" placeholder="<?php echo translate('admin_shop_placeholder_level_boost', 'Enter level boost'); ?>">
                                </div>

                                <!-- Login Flags -->
                                <div class="field-group" id="login-flags-group">
                                    <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_shop_label_at_login_flags', 'Login Flags'); ?></label>
                                    <select name="at_login_flags" id="at_login_flags" class="wow-select">
                                        <option value="0"><?php echo translate('admin_shop_at_login_none', 'None'); ?></option>
                                        <option value="1"><?php echo translate('admin_shop_at_login_force_name', 'Force character to change name'); ?></option>
                                        <option value="2"><?php echo translate('admin_shop_at_login_reset_spells', 'Reset spells (professions as well)'); ?></option>
                                        <option value="4"><?php echo translate('admin_shop_at_login_reset_talents', 'Reset Talents'); ?></option>
                                        <option value="8"><?php echo translate('admin_shop_at_login_customize', 'Customize Character'); ?></option>
                                        <option value="16"><?php echo translate('admin_shop_at_login_reset_pet', 'Reset Pet Talents'); ?></option>
                                        <option value="32"><?php echo translate('admin_shop_at_login_first_login', 'First Login'); ?></option>
                                        <option value="64"><?php echo translate('admin_shop_at_login_faction_change', 'Faction Change'); ?></option>
                                        <option value="128"><?php echo translate('admin_shop_at_login_race_change', 'Race Change'); ?></option>
                                    </select>
                                </div>

                                <!-- Is Item -->
                                <div class="field-group" id="is-item-group">
                                    <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_shop_label_is_item', 'Is Item?'); ?></label>
                                    <select name="is_item" id="is_item" class="wow-select">
                                        <option value="0"><?php echo translate('admin_shop_no', 'No'); ?></option>
                                        <option value="1"><?php echo translate('admin_shop_yes', 'Yes'); ?></option>
                                    </select>
                                </div>

                                <!-- Image Upload -->
                                <div class="field-group active md:col-span-2">
                                    <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_shop_label_image', 'Image Upload'); ?></label>
                                    <div class="upload-area" id="uploadArea">
                                        <input type="file" name="image" id="image" class="hidden" accept="image/jpeg,image/png,image/gif">
                                        <div id="uploadPlaceholder">
                                            <i class="fas fa-cloud-upload-alt text-3xl text-[#c9a227]/40 mb-2"></i>
                                            <p class="text-sm text-gray-400"><?php echo translate('admin_shop_image_help', 'Click or drag to upload image'); ?></p>
                                            <p class="text-xs text-gray-500 mt-1">Max 2MB • JPG, PNG, GIF</p>
                                        </div>
                                        <img id="image_preview" class="image-preview" src="" alt="Preview">
                                    </div>
                                </div>

                                <!-- Itemset Preview -->
                                <div class="field-group md:col-span-2" id="itemset-preview-group">
                                    <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_shop_label_itemset_preview', 'Set Preview'); ?></label>
                                    <div class="itemset-preview">
                                        <p id="itemset_preview_empty" class="text-muted-wow text-sm mb-0"><?php echo translate('admin_shop_itemset_preview_hint', 'Enter an item set ID to preview its items.'); ?></p>
                                        <ul id="itemset_preview_list" class="mb-0"></ul>
                                    </div>
                                </div>

                                <!-- Description -->
                                <div class="field-group md:col-span-2" id="description-group">
                                    <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_shop_label_description', 'Description'); ?></label>
                                    <textarea name="description" id="description" class="wow-textarea" rows="3" placeholder="<?php echo translate('admin_shop_placeholder_description', 'Enter description'); ?>"></textarea>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-4 mt-6 pt-4 border-t border-[rgba(201,162,39,.1)]">
                                <button type="submit" class="btn-game btn-gold" id="submitBtn">
                                    <i class="fas fa-plus"></i>
                                    <?php echo translate('admin_shop_add_button', 'Add Item'); ?>
                                </button>
                                <button type="button" class="btn-game btn-iron" id="cancelEdit" style="display:none;">
                                    <i class="fas fa-times"></i>
                                    <?php echo translate('admin_shop_cancel_button', 'Cancel'); ?>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Shop Items List -->
                    <div class="wow-panel p-4 md:p-6 lg:p-8">
                        <h2 class="section-title text-lg md:text-xl mb-4 md:mb-6"><?php echo translate('admin_shop_list_header', 'Shop Items'); ?></h2>
                        
                        <!-- Filter & Search -->
                        <form method="GET" class="grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-3 md:gap-4 items-end mb-4 md:mb-6" action="<?php echo $base_path; ?>admin/ashop">
                            <div>
                                <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_shop_label_category_filter', 'Filter by Category'); ?></label>
                                <select name="category" id="category_filter" class="wow-select">
                                    <option value=""><?php echo translate('admin_shop_all_categories', 'All Categories'); ?></option>
                                    <?php foreach ($valid_categories as $cat): ?>
                                        <option value="<?php echo $cat; ?>" <?php echo $category_filter === $cat ? 'selected' : ''; ?>>
                                            <?php echo translate('admin_shop_category_' . strtolower($cat), $cat); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_shop_label_search', 'Search by Name'); ?></label>
                                <input type="text" name="search" id="search" class="wow-input" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="<?php echo translate('admin_shop_placeholder_search', 'Enter item name'); ?>">
                            </div>
                            <button type="submit" class="btn-game btn-gold w-full md:w-auto justify-center">
                                <i class="fas fa-search"></i>
                                <?php echo translate('admin_shop_apply_button', 'Apply'); ?>
                            </button>
                        </form>

                        <!-- Table -->
                        <div class="overflow-x-auto -mx-4 md:-mx-6 lg:-mx-8 px-4 md:px-6 lg:px-8">
                            <table class="wow-table">
                                <thead>
                                    <tr>
                                        <th><?php echo translate('admin_shop_table_id', 'ID'); ?></th>
                                        <th><?php echo translate('admin_shop_table_category', 'Category'); ?></th>
                                        <th><?php echo translate('admin_shop_table_name', 'Name'); ?></th>
                                        <th class="hidden sm:table-cell"><?php echo translate('admin_shop_table_points', 'Points'); ?></th>
                                        <th class="hidden sm:table-cell"><?php echo translate('admin_shop_table_tokens', 'Tokens'); ?></th>
                                        <th class="hidden md:table-cell"><?php echo translate('admin_shop_table_stock', 'Stock'); ?></th>
                                        <th class="hidden lg:table-cell"><?php echo translate('admin_shop_table_image', 'Image'); ?></th>
                                        <th><?php echo translate('admin_shop_table_actions', 'Actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($items)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-gray-400 py-6 md:py-8">
                                                <i class="fas fa-shopping-cart text-3xl md:text-4xl text-gray-600 block mb-3"></i>
                                                <?php echo translate('admin_shop_no_items', 'No items found.'); ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($items as $row): ?>
                                            <tr>
                                                <td class="text-sm md:text-base"><?php echo $row['item_id']; ?></td>
                                                <td>
                                                    <span class="category-badge text-xs">
                                                        <?php echo translate('admin_shop_category_' . strtolower($row['category']), $row['category']); ?>
                                                    </span>
                                                </td>
                                                <td class="text-sm md:text-base">
                                                    <?php echo htmlspecialchars($row['name']); ?>
                                                    <?php if ((int)$row['is_set'] === 1 && !empty($row['itemset_id'])): ?>
                                                        <br><small class="text-muted-wow"><?php echo translate('admin_shop_set_label', 'Set:') . ' #' . (int)$row['itemset_id']; ?></small>
                                                    <?php elseif (!empty($row['entry_name'])): ?>
                                                        <br><small class="text-muted-wow"><?php echo translate('admin_shop_item_label', 'Item:') . ' ' . htmlspecialchars($row['entry_name']); ?></small>
                                                    <?php elseif ($row['gold_amount'] > 0): ?>
                                                        <br><small class="text-muted-wow"><?php echo translate('admin_shop_gold_label', 'Gold:') . ' ' . number_format($row['gold_amount']); ?></small>
                                                    <?php elseif ($row['level_boost']): ?>
                                                        <br><small class="text-muted-wow"><?php echo translate('admin_shop_level_label', 'Level:') . ' +' . $row['level_boost']; ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="hidden sm:table-cell text-sm md:text-base"><?php echo $row['point_cost']; ?></td>
                                                <td class="hidden sm:table-cell text-sm md:text-base"><?php echo $row['token_cost']; ?></td>
                                                <td class="hidden md:table-cell text-sm md:text-base"><?php echo $row['stock'] ?? '∞'; ?></td>
                                                <td class="hidden lg:table-cell">
                                                    <?php if ($row['image']): ?>
                                                        <img src="<?php echo htmlspecialchars($row['image']); ?>" alt="<?php echo translate('admin_shop_image_alt', 'Item Image'); ?>" class="max-w-[50px] max-h-[50px]">
                                                    <?php else: ?>
                                                        <span class="text-muted-wow text-xs"><?php echo translate('admin_shop_no_image', 'No Image'); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="flex flex-wrap gap-1.5 md:gap-2">
                                                        <button class="btn-game btn-edit-action text-xs py-1.5 px-2 md:px-3 edit-item" data-item='<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES); ?>'>
                                                            <i class="fas fa-edit"></i>
                                                            <span class="hidden sm:inline"><?php echo translate('admin_shop_edit_button', 'Edit'); ?></span>
                                                        </button>
                                                        <button type="button" class="btn-game btn-danger text-xs py-1.5 px-2 md:px-3" onclick="openModal('deleteModal-<?php echo $row['item_id']; ?>')">
                                                            <i class="fas fa-trash-alt"></i>
                                                            <span class="hidden sm:inline"><?php echo translate('admin_shop_delete_button', 'Delete'); ?></span>
                                                        </button>
                                                    </div>

                                                    <div id="deleteModal-<?php echo $row['item_id']; ?>" class="fixed inset-0 z-50 hidden items-center justify-center p-4 modal-backdrop">
                                                        <div class="wow-panel w-full max-w-md p-6 relative">
                                                            <h3 class="wow-title text-xl mb-4"><?php echo translate('admin_shop_delete_modal_title', 'Delete Item'); ?></h3>
                                                            <p class="text-gray-300 mb-6"><?php echo translate('admin_shop_delete_confirm', 'Are you sure you want to delete this item?'); ?></p>
                                                            <form method="POST" class="flex justify-end gap-4">
                                                                <input type="hidden" name="action" value="delete">
                                                                <input type="hidden" name="item_id" value="<?php echo $row['item_id']; ?>">
                                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                                <button type="button" class="btn-game btn-iron" onclick="closeModal('deleteModal-<?php echo $row['item_id']; ?>')">
                                                                    <?php echo translate('admin_shop_cancel_button', 'Cancel'); ?>
                                                                </button>
                                                                <button type="submit" class="btn-game btn-danger">
                                                                    <?php echo translate('admin_shop_confirm_delete_button', 'Confirm Delete'); ?>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav class="flex justify-center gap-1.5 md:gap-2 flex-wrap mt-6 md:mt-8" aria-label="<?php echo translate('admin_shop_pagination_aria', 'Page navigation'); ?>">
                                <?php if ($page > 1): ?>
                                    <a href="<?php echo $base_path; ?>admin/ashop?page=<?php echo $page - 1; ?><?php echo $category_filter ? '&category=' . urlencode($category_filter) : ''; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>" class="btn-game btn-iron py-2 px-3 md:px-4 text-xs">
                                        <i class="fas fa-chevron-left"></i> <?php echo translate('admin_shop_previous', 'Previous'); ?>
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <?php if ($page === $i): ?>
                                        <span class="btn-game btn-gold py-2 px-3 md:px-4 text-xs cursor-default"><?php echo $i; ?></span>
                                    <?php else: ?>
                                        <a href="<?php echo $base_path; ?>admin/ashop?page=<?php echo $i; ?><?php echo $category_filter ? '&category=' . urlencode($category_filter) : ''; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>" class="btn-game btn-iron py-2 px-3 md:px-4 text-xs"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="<?php echo $base_path; ?>admin/ashop?page=<?php echo $page + 1; ?><?php echo $category_filter ? '&category=' . urlencode($category_filter) : ''; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>" class="btn-game btn-iron py-2 px-3 md:px-4 text-xs">
                                        <?php echo translate('admin_shop_next', 'Next'); ?> <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function openModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const categorySelect = document.getElementById('category');
            const form = document.getElementById('itemForm');
            const formAction = document.getElementById('formAction');
            const submitBtn = document.getElementById('submitBtn');
            const cancelBtn = document.getElementById('cancelEdit');
            const imageInput = document.getElementById('image');
            const imagePreview = document.getElementById('image_preview');
            const existingImageInput = document.getElementById('existing_image');
            const uploadArea = document.getElementById('uploadArea');
            const uploadPlaceholder = document.getElementById('uploadPlaceholder');
            const entrySelect = document.getElementById('entry');
            const isItemSelect = document.getElementById('is_item');
            const itemsetInput = document.getElementById('itemset_id');
            const itemsetPreviewEmpty = document.getElementById('itemset_preview_empty');
            const itemsetPreviewList = document.getElementById('itemset_preview_list');
            const itemsetPreviewData = <?php echo json_encode($site_itemsets, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

            // Field groups configuration
            const fieldGroups = {
                'entry-group': ['Mount', 'Pet', 'Stuff'],
                'itemset-group': ['Set'],
                'gold-group': ['Gold'],
                'level-boost-group': ['Service'],
                'login-flags-group': ['Service'],
                'is-item-group': ['Mount', 'Pet', 'Stuff'],
                'description-group': ['Service', 'Gold'],
                'itemset-preview-group': ['Set']
            };

            function renderItemsetPreview() {
                const category = categorySelect.value;
                const isSet = category === 'Set';
                const rawSetId = itemsetInput.value.trim();

                itemsetPreviewList.innerHTML = '';

                if (!isSet) {
                    itemsetPreviewEmpty.textContent = '<?php echo translate('admin_shop_itemset_preview_hidden', 'Choose the "Set" category to preview the set contents.'); ?>';
                    itemsetPreviewEmpty.classList.remove('hidden');
                    return;
                }

                if (!rawSetId) {
                    itemsetPreviewEmpty.textContent = '<?php echo translate('admin_shop_itemset_preview_hint', 'Enter an item set ID to preview its items.'); ?>';
                    itemsetPreviewEmpty.classList.remove('hidden');
                    return;
                }

                const setItems = itemsetPreviewData[rawSetId] || [];
                if (setItems.length === 0) {
                    itemsetPreviewEmpty.textContent = '<?php echo translate('admin_shop_itemset_preview_empty', 'No items were found for this item set ID.'); ?>';
                    itemsetPreviewEmpty.classList.remove('hidden');
                    return;
                }

                itemsetPreviewEmpty.classList.add('hidden');
                setItems.forEach(item => {
                    const listItem = document.createElement('li');
                    listItem.textContent = `${item.entry} - ${item.name}`;
                    itemsetPreviewList.appendChild(listItem);
                });
            }

            function updateFormFields() {
                const category = categorySelect.value;
                const isSet = category === 'Set';

                // Auto-set is_item for item categories
                if (['Mount', 'Pet', 'Stuff', 'Set'].includes(category)) {
                    isItemSelect.value = '1';
                } else {
                    isItemSelect.value = '0';
                }

                // Toggle field groups
                Object.keys(fieldGroups).forEach(group => {
                    const element = document.getElementById(group);
                    if (!element) return;
                    
                    let shouldShow = fieldGroups[group].includes(category);

                    if (group === 'entry-group') {
                        shouldShow = shouldShow && !isSet;
                    }

                    if (group === 'itemset-group') {
                        shouldShow = shouldShow && isSet;
                    }

                    if (group === 'is-item-group') {
                        shouldShow = shouldShow && !isSet;
                    }

                    if (group === 'itemset-preview-group') {
                        shouldShow = shouldShow && isSet;
                    }

                    element.classList.toggle('active', shouldShow);

                    // Clear fields when hidden
                    if (!shouldShow) {
                        const inputs = element.querySelectorAll('input, select, textarea');
                        inputs.forEach(input => {
                            input.value = '';
                        });
                    }
                });

                // Special handling
                if (category === 'Gold' || category === 'Service') {
                    isItemSelect.value = '0';
                }

                if (isSet) {
                    isItemSelect.value = '1';
                    entrySelect.value = '';
                } else {
                    itemsetInput.value = '';
                }

                renderItemsetPreview();
            }

            // Initialize form fields
            updateFormFields();

            // Handle category change
            categorySelect.addEventListener('change', updateFormFields);
            itemsetInput.addEventListener('input', renderItemsetPreview);

            // Image upload area
            if (uploadArea && imageInput) {
                uploadArea.addEventListener('click', () => imageInput.click());

                uploadArea.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    uploadArea.classList.add('dragover');
                });

                uploadArea.addEventListener('dragleave', () => {
                    uploadArea.classList.remove('dragover');
                });

                uploadArea.addEventListener('drop', (e) => {
                    e.preventDefault();
                    uploadArea.classList.remove('dragover');
                    if (e.dataTransfer.files.length) {
                        imageInput.files = e.dataTransfer.files;
                        imageInput.dispatchEvent(new Event('change'));
                    }
                });

                imageInput.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        const file = this.files[0];
                        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                        const maxSize = 2 * 1024 * 1024;

                        if (!allowedTypes.includes(file.type)) {
                            alert('<?php echo translate('admin_shop_js_invalid_file_type', 'Invalid file type. Only JPG, PNG, or GIF allowed.'); ?>');
                            this.value = '';
                            imagePreview.classList.remove('active');
                            imagePreview.src = '';
                            return;
                        }
                        if (file.size > maxSize) {
                            alert('<?php echo translate('admin_shop_js_file_size_exceeded', 'File size exceeds 2MB limit.'); ?>');
                            this.value = '';
                            imagePreview.classList.remove('active');
                            imagePreview.src = '';
                            return;
                        }

                        const reader = new FileReader();
                        reader.onload = function(e) {
                            imagePreview.src = e.target.result;
                            imagePreview.classList.add('active');
                            uploadPlaceholder.style.display = 'none';
                        };
                        reader.readAsDataURL(file);
                    } else {
                        imagePreview.classList.remove('active');
                        imagePreview.src = '';
                        uploadPlaceholder.style.display = 'block';
                    }
                });
            }

            // Edit item button handler
            document.querySelectorAll('.edit-item').forEach(button => {
                button.addEventListener('click', function() {
                    const item = JSON.parse(this.dataset.item);

                    formAction.value = 'edit';
                    document.getElementById('item_id').value = item.item_id;
                    document.getElementById('category').value = item.category;
                    document.getElementById('name').value = item.name;
                    document.getElementById('description').value = item.description || '';
                    document.getElementById('point_cost').value = item.point_cost;
                    document.getElementById('token_cost').value = item.token_cost;
                    document.getElementById('stock').value = item.stock || '';
                    document.getElementById('entry').value = item.entry || '';
                    document.getElementById('gold_amount').value = item.gold_amount || 0;
                    document.getElementById('level_boost').value = item.level_boost || '';
                    document.getElementById('at_login_flags').value = item.at_login_flags || 0;
                    document.getElementById('is_item').value = item.is_item || 0;
                    document.getElementById('itemset_id').value = item.itemset_id || '';
                    existingImageInput.value = item.image || '';
                    
                    if (item.image) {
                        imagePreview.src = item.image;
                        imagePreview.classList.add('active');
                        uploadPlaceholder.style.display = 'none';
                    } else {
                        imagePreview.classList.remove('active');
                        imagePreview.src = '';
                        uploadPlaceholder.style.display = 'block';
                    }
                    
                    imageInput.value = '';

                    submitBtn.innerHTML = '<i class="fas fa-save"></i> <?php echo translate('admin_shop_update_button', 'Update Item'); ?>';
                    cancelBtn.style.display = 'inline-flex';

                    updateFormFields();
                    form.scrollIntoView({ behavior: 'smooth' });
                });
            });

            // Cancel edit handler
            cancelBtn.addEventListener('click', function() {
                form.reset();
                formAction.value = 'add';
                document.getElementById('item_id').value = '';
                existingImageInput.value = '';
                imagePreview.classList.remove('active');
                imagePreview.src = '';
                uploadPlaceholder.style.display = 'block';
                submitBtn.innerHTML = '<i class="fas fa-plus"></i> <?php echo translate('admin_shop_add_button', 'Add Item'); ?>';
                this.style.display = 'none';
                updateFormFields();
            });

            // Form validation
            form.addEventListener('submit', function(e) {
                const category = document.getElementById('category').value;
                const name = document.getElementById('name').value.trim();
                const pointCost = document.getElementById('point_cost').value;
                const tokenCost = document.getElementById('token_cost').value;
                const isSet = category === 'Set';
                const entryValue = document.getElementById('entry').value;
                const itemsetValue = document.getElementById('itemset_id').value;

                if (!name || pointCost === '' || tokenCost === '') {
                    e.preventDefault();
                    alert('<?php echo translate('admin_shop_js_required_fields', 'Please fill in all required fields.'); ?>');
                    return;
                }

                if (['Mount', 'Pet', 'Stuff', 'Set'].includes(category)) {
                    if (isSet && (!itemsetValue || parseInt(itemsetValue, 10) <= 0)) {
                        e.preventDefault();
                        alert('<?php echo translate('admin_shop_js_invalid_itemset_id', 'Please provide a valid item set ID.'); ?>');
                        return;
                    }

                    if (category !== 'Set' && !entryValue) {
                        e.preventDefault();
                        alert('<?php echo translate('admin_shop_js_select_entry', 'Please select an item entry.'); ?>');
                        return;
                    }
                }

                if (category === 'Service') {
                    const levelBoost = document.getElementById('level_boost').value;
                    if (levelBoost && (levelBoost < 2 || levelBoost > 255)) {
                        e.preventDefault();
                        alert('<?php echo translate('admin_shop_js_invalid_level_boost', 'Level boost must be between 2 and 255.'); ?>');
                        return;
                    }
                }
            });
        });
    </script>
</body>
</html>
<?php $site_db->close(); ?>