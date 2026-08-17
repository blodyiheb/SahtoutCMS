<?php
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../../../includes/paths.php';
require_once $project_root . 'includes/session.php';
require_once $project_root . 'languages/language.php';
require_once $project_root . 'includes/config.settings.php';

// Redirect helper function
function redirect_with_params(string $base, array $params = []) {
    $url = $base . ($params ? '?' . http_build_query($params) : '');
    header("Location: $url");
    exit;
}

// Handle session check and redirect before any output
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'moderator'])) {
    redirect_with_params("{$base_path}login");
}

// Initialize variables
$site_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$site_data = [
    'callback_file_name' => '',
    'site_name' => '',
    'siteid' => '',
    'url_format' => '',
    'button_image_url' => '',
    'cooldown_hours' => 12,
    'reward_points' => 1,
    'uses_callback' => 0,
    'callback_secret' => ''
];
$errors = [];
$status = '';
$message = '';

// Log form submissions for debugging
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $log_dir = $project_root . 'pages/pingback';
    $log_file = $log_dir . '/debug.log';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    if (is_writable($log_dir)) {
        file_put_contents($log_file, "Vote Sites Form Submission: " . json_encode($_POST, JSON_PRETTY_PRINT) . "\n---\n", FILE_APPEND);
    }
}

// Handle Delete Image
if (isset($_GET['delete_image']) && is_numeric($_GET['delete_image'])) {
    $delete_id = (int)$_GET['delete_image'];
    try {
        if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
            $errors[] = translate('err_invalid_csrf', 'Invalid CSRF token.');
        } else {
            if (!in_array($_SESSION['role'], ['admin', 'moderator'])) {
                $errors[] = translate('err_permission_denied', 'Permission denied.');
            } else {
                $stmt = $site_db->prepare("SELECT button_image_url FROM vote_sites WHERE id = ?");
                $stmt->bind_param("i", $delete_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    if ($row['button_image_url'] && strpos($row['button_image_url'], '/Sahtout/') === 0) {
                        $image_path = $project_root . parse_url($row['button_image_url'], PHP_URL_PATH);
                        if (file_exists($image_path)) {
                            unlink($image_path);
                        }
                    }
                    $stmt = $site_db->prepare("UPDATE vote_sites SET button_image_url = NULL WHERE id = ?");
                    $stmt->bind_param("i", $delete_id);
                    $stmt->execute();
                    if ($stmt->affected_rows > 0) {
                        $status = 'success';
                        $message = translate('msg_image_deleted', 'Image deleted successfully!');
                    } else {
                        $errors[] = translate('err_vote_site_not_found', 'Vote site not found.');
                    }
                } else {
                    $errors[] = translate('err_vote_site_not_found', 'Vote site not found.');
                }
                $stmt->close();
            }
        }
    } catch (Exception $e) {
        $errors[] = translate('err_database', 'Database error: ') . $e->getMessage();
    }
    redirect_with_params("{$base_path}admin/settings/vote_sites", $errors ? ['id' => $site_id, 'status' => 'error', 'message' => implode(', ', $errors)] : ['id' => $site_id, 'status' => 'success', 'message' => $message]);
}

// Handle Delete Site
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    try {
        if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
            $errors[] = translate('err_invalid_csrf', 'Invalid CSRF token.');
        } else {
            if (!in_array($_SESSION['role'], ['admin', 'moderator'])) {
                $errors[] = translate('err_permission_denied', 'Permission denied.');
            } else {
                $stmt = $site_db->prepare("SELECT button_image_url FROM vote_sites WHERE id = ?");
                $stmt->bind_param("i", $delete_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    if ($row['button_image_url'] && strpos($row['button_image_url'], '/Sahtout/') === 0) {
                        $image_path = $project_root . parse_url($row['button_image_url'], PHP_URL_PATH);
                        if (file_exists($image_path)) {
                            unlink($image_path);
                        }
                    }
                }
                $stmt = $site_db->prepare("DELETE FROM vote_sites WHERE id = ?");
                $stmt->bind_param("i", $delete_id);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    $status = 'success';
                    $message = translate('msg_vote_site_deleted', 'Vote site deleted successfully!');
                } else {
                    $errors[] = translate('err_vote_site_not_found', 'Vote site not found.');
                }
                $stmt->close();
            }
        }
    } catch (Exception $e) {
        $errors[] = translate('err_database', 'Database error: ') . $e->getMessage();
    }
    redirect_with_params("{$base_path}admin/settings/vote_sites", $errors ? ['status' => 'error', 'message' => implode(', ', $errors)] : ['status' => 'success', 'message' => $message]);
}

// Handle Form Submission (Create/Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = translate('err_invalid_csrf', 'Invalid CSRF token.');
    } else {
        $site_id = isset($_POST['site_id']) ? (int)$_POST['site_id'] : 0;
        $callback_file_name = trim($_POST['callback_file_name'] ?? '');
        $site_name = trim($_POST['site_name'] ?? '');
        $siteid = trim($_POST['siteid'] ?? '');
        $url_format = trim($_POST['url_format'] ?? '');
        $button_image_url = trim($_POST['button_image_url'] ?? '');
        $cooldown_hours = (int)($_POST['cooldown_hours'] ?? 12);
        $reward_points = (int)($_POST['reward_points'] ?? 1);
        $uses_callback = isset($_POST['uses_callback']) && $_POST['uses_callback'] == 1 ? 1 : 0;
        $callback_secret = trim(strip_tags($_POST['callback_secret'] ?? ''));

        // Validate callback_file_name
        if (empty($callback_file_name)) {
            $errors[] = translate('err_callback_file_name_required', 'Callback File Name is required.');
        } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $callback_file_name)) {
            $errors[] = translate('err_invalid_callback_file_name', 'Callback File Name must be alphanumeric with underscores or hyphens.');
        } elseif (strlen($callback_file_name) > 50) {
            $errors[] = translate('err_callback_file_name_too_long', 'Callback File Name must not exceed 50 characters.');
        } else {
            $stmt = $site_db->prepare("SELECT id FROM vote_sites WHERE callback_file_name = ? AND id != ?");
            $stmt->bind_param("si", $callback_file_name, $site_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $errors[] = translate('err_callback_file_name_exists', 'Callback File Name already exists.');
            }
            $stmt->close();
        }

        // Validate siteid
        if (empty($siteid)) {
            $errors[] = translate('err_siteid_required', 'Site ID is required.');
        } elseif (strlen($siteid) > 255) {
            $errors[] = translate('err_siteid_too_long', 'Site ID must not exceed 255 characters.');
        }

        // Validate url_format
        if (empty($url_format)) {
            $errors[] = translate('err_url_format_required', 'Vote URL Format is required.');
        } elseif (strlen($url_format) > 255) {
            $errors[] = translate('err_url_format_too_long', 'URL format must not exceed 255 characters.');
        }

        // Handle file upload
        if (isset($_FILES['button_image']) && $_FILES['button_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload_dir = $project_root . 'img/voteimg/';
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 1 * 1024 * 1024;
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $file = $_FILES['button_image'];
            $file_name = basename($file['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $file_size = $file['size'];
            $new_file_name = uniqid('voteimg_') . '.' . $file_ext;
            $upload_path = $upload_dir . $new_file_name;

            if ($file['error'] === UPLOAD_ERR_FORM_SIZE || $file['error'] === UPLOAD_ERR_INI_SIZE) {
                $errors[] = translate('err_image_too_large', 'Image size must not exceed 1MB.');
            } elseif ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = translate('err_image_upload_failed', 'Image upload failed: ') . $file['error'];
            } elseif ($file_size > $max_size) {
                $errors[] = translate('err_image_too_large', 'Image size must not exceed 1MB.');
            } else {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $file_type = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                if (!in_array($file_type, $allowed_types)) {
                    $errors[] = translate('err_invalid_image_type', 'Only JPEG, PNG, and GIF images are allowed.');
                } else {
                    if ($site_id > 0) {
                        $stmt = $site_db->prepare("SELECT button_image_url FROM vote_sites WHERE id = ?");
                        $stmt->bind_param("i", $site_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($result->num_rows > 0) {
                            $row = $result->fetch_assoc();
                            if ($row['button_image_url'] && strpos($row['button_image_url'], '/Sahtout/') === 0) {
                                $image_path = $project_root . parse_url($row['button_image_url'], PHP_URL_PATH);
                                if (file_exists($image_path)) {
                                    unlink($image_path);
                                }
                            }
                        }
                        $stmt->close();
                    }
                    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                        $button_image_url = '/Sahtout/img/voteimg/' . $new_file_name;
                    } else {
                        $errors[] = translate('err_image_upload_failed', 'Failed to move uploaded image.');
                    }
                }
            }
        } elseif (empty($button_image_url)) {
            $button_image_url = null;
            if ($site_id > 0) {
                $stmt = $site_db->prepare("SELECT button_image_url FROM vote_sites WHERE id = ?");
                $stmt->bind_param("i", $site_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    if ($row['button_image_url'] && strpos($row['button_image_url'], '/Sahtout/') === 0) {
                        $image_path = $project_root . parse_url($row['button_image_url'], PHP_URL_PATH);
                        if (file_exists($image_path)) {
                            unlink($image_path);
                        }
                    }
                }
                $stmt->close();
            }
        } elseif (filter_var($button_image_url, FILTER_VALIDATE_URL)) {
            if ($site_id > 0) {
                $stmt = $site_db->prepare("SELECT button_image_url FROM vote_sites WHERE id = ?");
                $stmt->bind_param("i", $site_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    if ($row['button_image_url'] && strpos($row['button_image_url'], '/Sahtout/') === 0) {
                        $image_path = $project_root . parse_url($row['button_image_url'], PHP_URL_PATH);
                        if (file_exists($image_path)) {
                            unlink($image_path);
                        }
                    }
                }
                $stmt->close();
            }
        }

        // Validate other fields
        if (empty($site_name)) {
            $errors[] = translate('err_site_name_required', 'Site name is required.');
        } elseif (strlen($site_name) > 50) {
            $errors[] = translate('err_site_name_too_long', 'Site name must not exceed 50 characters.');
        }
        if (!is_null($button_image_url) && !empty($button_image_url) && strlen($button_image_url) > 255) {
            $errors[] = translate('err_invalid_image_url', 'Button image URL too long.');
        }
        if ($cooldown_hours < 1 || $cooldown_hours > 999) {
            $errors[] = translate('err_invalid_cooldown', 'Cooldown hours must be between 1 and 999.');
        }
        if ($reward_points < 1 || $reward_points > 255) {
            $errors[] = translate('err_invalid_reward', 'Reward points must be between 1 and 255.');
        }
        if (!empty($callback_secret) && strlen($callback_secret) > 64) {
            $errors[] = translate('err_callback_secret_too_long', 'Callback secret must not exceed 64 characters.');
        }

        if (empty($errors)) {
            try {
                if ($site_id > 0) {
                    $stmt = $site_db->prepare("UPDATE vote_sites SET callback_file_name = ?, site_name = ?, siteid = ?, url_format = ?, button_image_url = ?, cooldown_hours = ?, reward_points = ?, uses_callback = ?, callback_secret = ? WHERE id = ?");
                    $stmt->bind_param("sssssiissi", $callback_file_name, $site_name, $siteid, $url_format, $button_image_url, $cooldown_hours, $reward_points, $uses_callback, $callback_secret, $site_id);
                } else {
                    $stmt = $site_db->prepare("INSERT INTO vote_sites (callback_file_name, site_name, siteid, url_format, button_image_url, cooldown_hours, reward_points, uses_callback, callback_secret) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssiiss", $callback_file_name, $site_name, $siteid, $url_format, $button_image_url, $cooldown_hours, $reward_points, $uses_callback, $callback_secret);
                }
                $stmt->execute();
                $stmt->close();
                $status = 'success';
                $message = translate('msg_vote_site_saved', 'Vote site saved successfully!');
                $site_data = [
                    'callback_file_name' => '',
                    'site_name' => '',
                    'siteid' => '',
                    'url_format' => '',
                    'button_image_url' => '',
                    'cooldown_hours' => 12,
                    'reward_points' => 1,
                    'uses_callback' => 0,
                    'callback_secret' => ''
                ];
                $site_id = 0;
            } catch (Exception $e) {
                $errors[] = translate('err_database', 'Database error: ') . $e->getMessage();
            }
        } else {
            $site_data = [
                'callback_file_name' => $callback_file_name,
                'site_name' => $site_name,
                'siteid' => $siteid,
                'url_format' => $url_format,
                'button_image_url' => $button_image_url ?? '',
                'cooldown_hours' => $cooldown_hours,
                'reward_points' => $reward_points,
                'uses_callback' => $uses_callback,
                'callback_secret' => $callback_secret
            ];
        }
        redirect_with_params("{$base_path}admin/settings/vote_sites", $site_id ? ['id' => $site_id] + ($errors ? ['status' => 'error', 'message' => implode(', ', $errors)] : ['status' => 'success', 'message' => $message]) : ($errors ? ['status' => 'error', 'message' => implode(', ', $errors)] : ['status' => 'success', 'message' => $message]));
    }
}

// Fetch existing site data for editing
if ($site_id > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    try {
        $stmt = $site_db->prepare("SELECT * FROM vote_sites WHERE id = ?");
        $stmt->bind_param("i", $site_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $site_data = $result->fetch_assoc();
            $site_data['callback_file_name'] = $site_data['callback_file_name'] ?? '';
            $site_data['siteid'] = $site_data['siteid'] ?? '';
            $site_data['url_format'] = $site_data['url_format'] ?? '';
            $site_data['callback_secret'] = $site_data['callback_secret'] ?? '';
            $site_data['button_image_url'] = $site_data['button_image_url'] ?? '';
        } else {
            $errors[] = translate('err_vote_site_not_found', 'Vote site not found.');
        }
        $stmt->close();
    } catch (Exception $e) {
        $errors[] = translate('err_database', 'Database error: ') . $e->getMessage();
    }
}

// Fetch all vote sites
try {
    $stmt = $site_db->prepare("SELECT id, callback_file_name, site_name, siteid, url_format, button_image_url, cooldown_hours, reward_points, uses_callback, callback_secret FROM vote_sites");
    $stmt->execute();
    $voteSites = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    $errors[] = translate('err_database', 'Database error: ') . $e->getMessage();
    $voteSites = [];
}

$page_class = 'vote-sites';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo translate('page_description_vote_sites', 'Vote Sites Management for Sahtout WoW Server'); ?>">
    <meta name="robots" content="noindex">
    <title><?php echo translate('page_title_manage_vote_sites', 'Manage Vote Sites'); ?></title>
    <link rel="icon" href="<?php echo $base_path . $site_logo; ?>" type="image/x-icon">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/tailwind.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&family=Cinzel:wght@600;700;900&display=swap" rel="stylesheet">
    
    <style>
        /* Only keep what Tailwind CANNOT do */
        
        /* Font families */
        * { font-family: 'Inter', sans-serif; }
        .wow-title, .section-title, .form-label, .table-wow th { font-family: 'Cinzel', serif; }
        
        /* Panel with gold corners AND inner border */
        .panel-gold-corners {
            position: relative;
        }
        
        /* Outer gold corner decorations */
        .panel-gold-corners::after {
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
        
        /* Inner border inset */
        .panel-gold-corners::before {
            content: '';
            position: absolute;
            inset: 5px;
            border: 1px solid rgba(201,162,39,.14);
            pointer-events: none;
        }
        
        /* Custom clip-path for buttons */
        .btn-clip {
            clip-path: polygon(10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%, 0 10px);
        }
        
        /* Modal backdrop */
        .modal-backdrop {
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
            position: fixed;
            inset: 0;
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .modal-backdrop.show {
            display: flex;
        }
        .modal-backdrop .panel-gold-corners {
            max-width: 28rem;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        /* Table styles */
        .table-wow th {
            background: rgba(10, 14, 22, 0.9);
            color: #f2cf5b;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            padding: 0.75rem;
            border-bottom: 2px solid rgba(201,162,39,.4);
            text-align: left;
            white-space: nowrap;
        }
        .table-wow td {
            padding: 0.75rem;
            border-bottom: 1px solid rgba(201,162,39,.1);
            color: #d8d8d8;
            background: rgba(22, 25, 32, 0.6);
            font-size: 0.85rem;
            word-break: break-word;
        }
        .table-wow tr:hover td {
            background: rgba(30, 35, 45, 0.8);
        }
        
        /* Vote image */
        .vote-image {
            max-height: 50px;
            max-width: 100px;
            object-fit: contain;
        }
        
        /* File input hidden */
        .hidden-input {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            border: 0;
        }
    </style>
</head>
<body class="min-h-screen text-[#d8d8d8] bg-[#05070b] bg-fixed"
      style="background-image: 
        radial-gradient(1000px 700px at -10% 35%, rgba(59,130,246,.14), transparent 65%),
        radial-gradient(800px 600px at -5% 85%, rgba(124,58,237,.10), transparent 70%),
        linear-gradient(180deg, #0a0e16 0%, #060810 45%, #03040a 100%);">
    
    <?php include $project_root . 'includes/header.php'; ?>

    <div class="flex relative min-h-screen">
        
        <!-- Sidebar -->
        <?php include $project_root . 'includes/admin_sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content-area flex-1 p-3 sm:p-4 md:p-6 lg:p-8 transition-all duration-300 lg:ml-[280px]">
            <div class="max-w-[1400px] mx-auto px-1 sm:px-4 md:px-6 lg:px-8 xl:px-10">
                <div class="space-y-4 md:space-y-6 lg:space-y-8">
                    
                    <h1 class="wow-title text-2xl md:text-3xl lg:text-4xl font-black 
                               bg-gradient-to-b from-[#fff7d6] via-[#f2cf5b] via-[#c9a227] to-[#8a6a14] 
                               bg-clip-text text-transparent drop-shadow-[0_3px_6px_rgba(0,0,0,.85)]">
                        <?php echo translate('page_title_manage_vote_sites', 'Manage Vote Sites'); ?>
                    </h1>

                    <!-- Settings Navbar -->
                    <?php include $project_root . 'pages/admin/settings/settings_navbar.php'; ?>

                    <!-- Success / Error Messages -->
                    <?php if ($status === 'success' || (isset($_GET['status']) && $_GET['status'] === 'success')): ?>
                        <div class="bg-[#2ecc71]/15 border border-[#2ecc71]/40 text-[#2ecc71] 
                                    p-4 rounded-sm flex items-center gap-3">
                            <i class="fas fa-check-circle text-xl"></i>
                            <span><?php echo htmlspecialchars($message ?: urldecode($_GET['message'])); ?></span>
                        </div>
                    <?php elseif (!empty($errors) || (isset($_GET['status']) && $_GET['status'] === 'error')): ?>
                        <div class="bg-[#e74c3c]/15 border border-[#e74c3c]/40 text-[#e74c3c] 
                                    p-4 rounded-sm flex items-start gap-3">
                            <i class="fas fa-exclamation-circle text-xl mt-0.5"></i>
                            <div>
                                <strong><?php echo translate('err_fix_errors', 'Please fix the following errors:'); ?></strong>
                                <?php if (isset($_GET['status']) && $_GET['status'] === 'error'): ?>
                                    <div class="text-sm mt-1">• <?php echo htmlspecialchars(urldecode($_GET['message'])); ?></div>
                                <?php else: ?>
                                    <?php foreach ($errors as $error): ?>
                                        <div class="text-sm mt-1">• <?php echo htmlspecialchars($error); ?></div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Vote Site Form -->
                    <div class="relative bg-gradient-to-b from-[#161920]/92 to-[#080a0e]/90 
                                border border-[#c9a227]/[0.22] 
                                shadow-[0_12px_32px_rgba(0,0,0,.55),inset_0_0_60px_rgba(0,0,0,.45)]
                                p-4 md:p-6 lg:p-8 panel-gold-corners">
                        
                        <h2 class="section-title text-lg md:text-xl mb-4 md:mb-6 flex items-center gap-3 
                                   text-[#f2cf5b] font-bold drop-shadow-[0_0_12px_rgba(201,162,39,.35),0_2px_4px_rgba(0,0,0,.8)]">
                            <i class="fas fa-vote-yea text-[#f2cf5b]"></i>
                            <?php echo $site_id > 0 ? translate('title_edit_vote_site', 'Edit Vote Site') : translate('title_add_vote_site', 'Add Vote Site'); ?>
                        </h2>

                        <form method="POST" enctype="multipart/form-data" class="space-y-4 md:space-y-6 max-w-3xl mx-auto">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="site_id" value="<?php echo $site_id; ?>">
                            <input type="hidden" name="MAX_FILE_SIZE" value="1048576">

                            <!-- Callback File Name -->
                            <div>
                                <label for="callback_file_name" class="form-label text-[#f2cf5b] font-bold text-sm 
                                                                         tracking-wider block mb-2 
                                                                         drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                    <?php echo translate('label_callback_file_name', 'Callback File Name'); ?>
                                </label>
                                <input type="text" name="callback_file_name" id="callback_file_name" 
                                       class="w-full px-4 py-3 text-[0.95rem] text-[#e5e7eb] 
                                              bg-[#0a0e16]/80 border border-[#c9a227]/30 rounded-sm 
                                              focus:border-[#f2cf5b] focus:shadow-[0_0_10px_rgba(242,207,82,.2)] 
                                              focus:bg-[#0f141e]/90 outline-none transition-all duration-200 
                                              placeholder:text-[#96aac8]/40"
                                       placeholder="<?php echo translate('placeholder_callback_file_name', 'Enter callback file name (e.g., arenaTop100)'); ?>" 
                                       value="<?php echo htmlspecialchars($site_data['callback_file_name']); ?>" 
                                       required maxlength="50">
                                <p class="text-[#6a7a8a] text-xs mt-1"><?php echo translate('label_callback_file_name_info', 'Name for identifying the voting site in callbacks (gtop100, top100arena, etc)'); ?></p>
                            </div>

                            <!-- Site Name -->
                            <div>
                                <label for="site_name" class="form-label text-[#f2cf5b] font-bold text-sm 
                                                             tracking-wider block mb-2 
                                                             drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                    <?php echo translate('label_site_name', 'Site Name'); ?>
                                </label>
                                <input type="text" name="site_name" id="site_name" 
                                       class="w-full px-4 py-3 text-[0.95rem] text-[#e5e7eb] 
                                              bg-[#0a0e16]/80 border border-[#c9a227]/30 rounded-sm 
                                              focus:border-[#f2cf5b] focus:shadow-[0_0_10px_rgba(242,207,82,.2)] 
                                              focus:bg-[#0f141e]/90 outline-none transition-all duration-200 
                                              placeholder:text-[#96aac8]/40"
                                       placeholder="<?php echo translate('placeholder_site_name', 'Enter site name'); ?>" 
                                       value="<?php echo htmlspecialchars($site_data['site_name']); ?>" 
                                       required maxlength="50">
                            </div>

                            <!-- Site ID -->
                            <div>
                                <label for="siteid" class="form-label text-[#f2cf5b] font-bold text-sm 
                                                           tracking-wider block mb-2 
                                                           drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                    <?php echo translate('label_siteid', 'Site ID'); ?>
                                </label>
                                <input type="text" name="siteid" id="siteid" 
                                       class="w-full px-4 py-3 text-[0.95rem] text-[#e5e7eb] 
                                              bg-[#0a0e16]/80 border border-[#c9a227]/30 rounded-sm 
                                              focus:border-[#f2cf5b] focus:shadow-[0_0_10px_rgba(242,207,82,.2)] 
                                              focus:bg-[#0f141e]/90 outline-none transition-all duration-200 
                                              placeholder:text-[#96aac8]/40"
                                       placeholder="<?php echo translate('placeholder_siteid', 'Enter server ID on the voting site'); ?>" 
                                       value="<?php echo htmlspecialchars($site_data['siteid']); ?>" 
                                       required maxlength="255">
                                <p class="text-[#6a7a8a] text-xs mt-1"><?php echo translate('label_siteid_info', 'Your server\'s unique ID on the voting site (e.g., SahtoutServer, 12345).'); ?></p>
                            </div>

                            <!-- URL Format -->
                            <div>
                                <label for="url_format" class="form-label text-[#f2cf5b] font-bold text-sm 
                                                               tracking-wider block mb-2 
                                                               drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                    <?php echo translate('label_url_format', 'Vote URL Format'); ?>
                                </label>
                                <input type="text" name="url_format" id="url_format" 
                                       class="w-full px-4 py-3 text-[0.95rem] text-[#e5e7eb] 
                                              bg-[#0a0e16]/80 border border-[#c9a227]/30 rounded-sm 
                                              focus:border-[#f2cf5b] focus:shadow-[0_0_10px_rgba(242,207,82,.2)] 
                                              focus:bg-[#0f141e]/90 outline-none transition-all duration-200 
                                              placeholder:text-[#96aac8]/40"
                                       placeholder="<?php echo translate('placeholder_url_format', 'e.g., https://site.com/vote/{siteid}/{userid}'); ?>" 
                                       value="<?php echo htmlspecialchars($site_data['url_format']); ?>" 
                                       required maxlength="255">
                                <p class="text-[#6a7a8a] text-xs mt-1"><?php echo translate('label_url_format_info', 'Use {siteid}, {userid}, or {username} as placeholders.'); ?></p>
                            </div>

                            <!-- Button Image Upload -->
                            <div>
                                <label for="button_image" class="form-label text-[#f2cf5b] font-bold text-sm 
                                                                 tracking-wider block mb-2 
                                                                 drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                    <?php echo translate('label_button_image', 'Upload Button Image'); ?>
                                </label>
                                <?php if ($site_data['button_image_url']): ?>
                                    <div class="mb-3 flex items-center gap-4 flex-wrap">
                                        <img src="<?php echo htmlspecialchars($site_data['button_image_url']); ?>" 
                                             alt="<?php echo translate('label_button_image', 'Button Image'); ?>" 
                                             class="vote-image border border-[rgba(201,162,39,.2)] p-1 bg-[#0a0e16]/50 rounded-sm">
                                        <a href="<?php echo $base_path; ?>admin/settings/vote_sites?delete_image=<?php echo $site_id; ?>&csrf_token=<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" 
                                           class="btn-clip inline-flex items-center gap-1.5 px-3 py-1.5 
                                                  font-extrabold text-xs uppercase tracking-wider
                                                  bg-gradient-to-b from-[#ff4d4d] via-[#cc0000] to-[#8a0000] 
                                                  text-white shadow-[inset_0_0_0_1px_rgba(255,255,255,.2),inset_0_-8px_14px_rgba(0,0,0,.3)]
                                                  hover:scale-105 transition-transform duration-200"
                                           onclick="return confirm('<?php echo translate('confirm_delete_image', 'Are you sure you want to delete this image?'); ?>');">
                                            <i class="fas fa-trash"></i> <?php echo translate('btn_delete_image', 'Delete Image'); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                                <div class="border-2 border-dashed border-[#c9a227]/20 
                                            bg-[#0a0e16]/50 hover:border-[#c9a227]/40 
                                            hover:bg-[#0f141e]/70 cursor-pointer transition-all duration-300 
                                            p-8 text-center rounded-sm" 
                                     id="uploadArea">
                                    <input type="file" id="button_image" name="button_image" 
                                           class="absolute w-px h-px p-0 -m-px overflow-hidden clip-[rect(0,0,0,0)] border-0" 
                                           accept="image/jpeg,image/png,image/gif">
                                    <div id="uploadPlaceholder">
                                        <i class="fas fa-cloud-upload-alt text-3xl text-[#c9a227]/40 block mb-2"></i>
                                        <p class="text-sm text-gray-400"><?php echo translate('placeholder_button_image', 'Click or drag to upload a button image'); ?></p>
                                        <p class="text-xs text-gray-500 mt-1">JPEG, PNG, GIF (max 1MB)</p>
                                    </div>
                                    <div id="file-name" class="text-sm text-[#f2cf5b] hidden mt-2 font-semibold"></div>
                                </div>
                            </div>

                            <!-- Button Image URL (Optional) -->
                            <div>
                                <label for="button_image_url" class="form-label text-[#f2cf5b] font-bold text-sm 
                                                                      tracking-wider block mb-2 
                                                                      drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                    <?php echo translate('label_button_image_url', 'Button Image URL (Optional)'); ?>
                                </label>
                                <input type="text" name="button_image_url" id="button_image_url" 
                                       class="w-full px-4 py-3 text-[0.95rem] text-[#e5e7eb] 
                                              bg-[#0a0e16]/80 border border-[#c9a227]/30 rounded-sm 
                                              focus:border-[#f2cf5b] focus:shadow-[0_0_10px_rgba(242,207,82,.2)] 
                                              focus:bg-[#0f141e]/90 outline-none transition-all duration-200 
                                              placeholder:text-[#96aac8]/40"
                                       placeholder="<?php echo translate('placeholder_button_image_url', 'Enter button image URL (optional)'); ?>" 
                                       value="<?php echo htmlspecialchars($site_data['button_image_url']); ?>" 
                                       maxlength="255">
                                <p class="text-[#6a7a8a] text-xs mt-1"><?php echo translate('label_image_url_info', 'Enter an image URL if you prefer not to upload an image. Leave empty to clear the image.'); ?></p>
                            </div>

                            <!-- Cooldown Hours -->
                            <div>
                                <label for="cooldown_hours" class="form-label text-[#f2cf5b] font-bold text-sm 
                                                                    tracking-wider block mb-2 
                                                                    drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                    <?php echo translate('label_cooldown_hours', 'Cooldown Hours'); ?>
                                </label>
                                <input type="number" name="cooldown_hours" id="cooldown_hours" 
                                       class="w-full px-4 py-3 text-[0.95rem] text-[#e5e7eb] 
                                              bg-[#0a0e16]/80 border border-[#c9a227]/30 rounded-sm 
                                              focus:border-[#f2cf5b] focus:shadow-[0_0_10px_rgba(242,207,82,.2)] 
                                              focus:bg-[#0f141e]/90 outline-none transition-all duration-200 
                                              placeholder:text-[#96aac8]/40"
                                       placeholder="<?php echo translate('placeholder_cooldown_hours', 'Enter cooldown hours'); ?>" 
                                       value="<?php echo htmlspecialchars($site_data['cooldown_hours']); ?>" 
                                       required min="1" max="999">
                            </div>

                            <!-- Reward Points -->
                            <div>
                                <label for="reward_points" class="form-label text-[#f2cf5b] font-bold text-sm 
                                                                   tracking-wider block mb-2 
                                                                   drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                    <?php echo translate('label_reward_points', 'Reward Points'); ?>
                                </label>
                                <input type="number" name="reward_points" id="reward_points" 
                                       class="w-full px-4 py-3 text-[0.95rem] text-[#e5e7eb] 
                                              bg-[#0a0e16]/80 border border-[#c9a227]/30 rounded-sm 
                                              focus:border-[#f2cf5b] focus:shadow-[0_0_10px_rgba(242,207,82,.2)] 
                                              focus:bg-[#0f141e]/90 outline-none transition-all duration-200 
                                              placeholder:text-[#96aac8]/40"
                                       placeholder="<?php echo translate('placeholder_reward_points', 'Enter reward points'); ?>" 
                                       value="<?php echo htmlspecialchars($site_data['reward_points']); ?>" 
                                       required min="1" max="255">
                            </div>

                            <!-- Uses Callback -->
                            <div>
                                <label for="uses_callback" class="form-label text-[#f2cf5b] font-bold text-sm 
                                                                   tracking-wider block mb-2 
                                                                   drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                    <?php echo translate('label_uses_callback', 'Uses Callback'); ?>
                                </label>
                                <select name="uses_callback" id="uses_callback" 
                                        class="w-full px-4 py-3 text-[0.95rem] text-[#e5e7eb] 
                                               bg-[#0a0e16]/80 border border-[#c9a227]/30 rounded-sm 
                                               focus:border-[#f2cf5b] focus:shadow-[0_0_10px_rgba(242,207,82,.2)] 
                                               focus:bg-[#0f141e]/90 outline-none transition-all duration-200 
                                               placeholder:text-[#96aac8]/40">
                                    <option value="0" <?php echo $site_data['uses_callback'] == 0 ? 'selected' : ''; ?>><?php echo translate('option_no', 'No'); ?></option>
                                    <option value="1" <?php echo $site_data['uses_callback'] == 1 ? 'selected' : ''; ?>><?php echo translate('option_yes', 'Yes'); ?></option>
                                </select>
                            </div>

                            <!-- Callback Secret -->
                            <div>
                                <label for="callback_secret" class="form-label text-[#f2cf5b] font-bold text-sm 
                                                                     tracking-wider block mb-2 
                                                                     drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                    <?php echo translate('label_callback_secret', 'Callback Secret'); ?>
                                </label>
                                <input type="text" name="callback_secret" id="callback_secret" 
                                       class="w-full px-4 py-3 text-[0.95rem] text-[#e5e7eb] 
                                              bg-[#0a0e16]/80 border border-[#c9a227]/30 rounded-sm 
                                              focus:border-[#f2cf5b] focus:shadow-[0_0_10px_rgba(242,207,82,.2)] 
                                              focus:bg-[#0f141e]/90 outline-none transition-all duration-200 
                                              placeholder:text-[#96aac8]/40"
                                       placeholder="<?php echo translate('placeholder_callback_secret', 'Enter callback secret (optional)'); ?>" 
                                       value="<?php echo htmlspecialchars($site_data['callback_secret'] ?? ''); ?>" 
                                       maxlength="64">
                            </div>

                            <!-- Buttons -->
                            <div class="pt-4 border-t border-[rgba(201,162,39,.1)] flex flex-wrap gap-3">
                                <button type="submit" class="btn-clip inline-flex items-center gap-2 px-6 py-3 
                                                             font-extrabold text-xs uppercase tracking-wider
                                                             bg-gradient-to-b from-[#f6d478] via-[#c9a227] to-[#8a6a14] 
                                                             text-[#1a1200] shadow-[inset_0_0_0_1px_rgba(255,255,255,.28),inset_0_-8px_14px_rgba(0,0,0,.25)]
                                                             hover:scale-105 transition-transform duration-200">
                                    <i class="fas fa-save"></i>
                                    <?php echo translate('btn_save_vote_site', 'Save Vote Site'); ?>
                                </button>
                                <?php if ($site_id > 0): ?>
                                    <a href="<?php echo $base_path; ?>admin/settings/vote_sites" 
                                       class="btn-clip inline-flex items-center gap-2 px-6 py-3 
                                              font-extrabold text-xs uppercase tracking-wider
                                              bg-gradient-to-b from-[#3a404d] via-[#232833] to-[#141821] 
                                              text-[#cfe1ff] shadow-[inset_0_0_0_1px_rgba(120,160,255,.25),inset_0_-8px_14px_rgba(0,0,0,.4)]
                                              hover:scale-105 transition-transform duration-200">
                                        <i class="fas fa-times"></i>
                                        <?php echo translate('btn_reset', 'Reset Form'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>

                    <!-- Vote Sites Table -->
                    <div class="relative bg-gradient-to-b from-[#161920]/92 to-[#080a0e]/90 
                                border border-[#c9a227]/[0.22] 
                                shadow-[0_12px_32px_rgba(0,0,0,.55),inset_0_0_60px_rgba(0,0,0,.45)]
                                p-4 md:p-6 lg:p-8 panel-gold-corners">
                        
                        <h2 class="section-title text-lg md:text-xl mb-4 md:mb-6 flex items-center gap-3 
                                   text-[#f2cf5b] font-bold drop-shadow-[0_0_12px_rgba(201,162,39,.35),0_2px_4px_rgba(0,0,0,.8)]">
                            <i class="fas fa-list text-[#f2cf5b]"></i>
                            <?php echo translate('title_vote_sites_list', 'Vote Sites List'); ?>
                        </h2>

                        <div class="overflow-x-auto -mx-4 md:-mx-6 lg:-mx-8 px-4 md:px-6 lg:px-8">
                            <table class="w-full table-wow min-w-[600px]">
                                <thead>
                                    <tr>
                                        <th><?php echo translate('label_callback_file_name', 'Callback File Name'); ?></th>
                                        <th><?php echo translate('label_site_name', 'Site Name'); ?></th>
                                        <th class="hidden sm:table-cell"><?php echo translate('label_siteid', 'Site ID'); ?></th>
                                        <th class="hidden md:table-cell"><?php echo translate('label_url_format', 'URL Format'); ?></th>
                                        <th class="hidden lg:table-cell"><?php echo translate('label_button_image', 'Image'); ?></th>
                                        <th><?php echo translate('label_cooldown_hours', 'Cooldown'); ?></th>
                                        <th class="hidden xs:table-cell"><?php echo translate('label_reward_points', 'Points'); ?></th>
                                        <th class="hidden xl:table-cell"><?php echo translate('label_uses_callback', 'Callback'); ?></th>
                                        <th><?php echo translate('label_actions', 'Actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($voteSites)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center text-gray-400 py-6 md:py-8">
                                                <i class="fas fa-vote-yea text-3xl text-gray-600 block mb-2"></i>
                                                <?php echo translate('msg_no_vote_sites', 'No vote sites available.'); ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($voteSites as $site): ?>
                                            <tr>
                                                <td class="font-semibold text-white text-sm"><?php echo htmlspecialchars($site['callback_file_name']); ?></td>
                                                <td><?php echo htmlspecialchars($site['site_name']); ?></td>
                                                <td class="hidden sm:table-cell text-sm text-gray-400"><?php echo htmlspecialchars($site['siteid']); ?></td>
                                                <td class="hidden md:table-cell text-sm text-gray-400"><?php echo htmlspecialchars(substr($site['url_format'], 0, 30)) . (strlen($site['url_format']) > 30 ? '...' : ''); ?></td>
                                                <td class="hidden lg:table-cell">
                                                    <?php if ($site['button_image_url']): ?>
                                                        <img src="<?php echo htmlspecialchars($site['button_image_url']); ?>" alt="<?php echo htmlspecialchars($site['site_name']); ?>" class="vote-image">
                                                    <?php else: ?>
                                                        <span class="text-gray-500 text-xs"><?php echo translate('label_no_image', 'No Image'); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($site['cooldown_hours']); ?></td>
                                                <td class="hidden xs:table-cell"><?php echo htmlspecialchars($site['reward_points']); ?></td>
                                                <td class="hidden xl:table-cell">
                                                    <span class="text-xs px-2 py-1 rounded-sm <?php echo $site['uses_callback'] ? 'bg-[#2ecc71]/20 text-[#2ecc71] border border-[#2ecc71]/30' : 'bg-gray-900/20 text-gray-400 border border-gray-500/30'; ?>">
                                                        <?php echo $site['uses_callback'] ? translate('option_yes', 'Yes') : translate('option_no', 'No'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="flex flex-wrap gap-1.5">
                                                        <a href="<?php echo $base_path; ?>admin/settings/vote_sites?id=<?php echo $site['id']; ?>" 
                                                           class="btn-clip inline-flex items-center gap-1.5 px-3 py-1.5 
                                                                  font-extrabold text-xs uppercase tracking-wider
                                                                  bg-gradient-to-b from-[#4a90d9] via-[#2a5f8a] to-[#1a3f5a] 
                                                                  text-white shadow-[inset_0_0_0_1px_rgba(100,180,255,.25),inset_0_-8px_14px_rgba(0,0,0,.3)]
                                                                  hover:scale-105 transition-transform duration-200">
                                                            <i class="fas fa-edit"></i>
                                                            <span class="hidden sm:inline"><?php echo translate('btn_edit', 'Edit'); ?></span>
                                                        </a>
                                                        <button onclick="openDeleteModal(<?php echo $site['id']; ?>, '<?php echo htmlspecialchars($site['site_name']); ?>')" 
                                                                class="btn-clip inline-flex items-center gap-1.5 px-3 py-1.5 
                                                                       font-extrabold text-xs uppercase tracking-wider
                                                                       bg-gradient-to-b from-[#ff4d4d] via-[#cc0000] to-[#8a0000] 
                                                                       text-white shadow-[inset_0_0_0_1px_rgba(255,255,255,.2),inset_0_-8px_14px_rgba(0,0,0,.3)]
                                                                       hover:scale-105 transition-transform duration-200">
                                                            <i class="fas fa-trash"></i>
                                                            <span class="hidden sm:inline"><?php echo translate('btn_delete', 'Delete'); ?></span>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Delete Modal -->
    <div class="modal-backdrop" id="deleteModal">
        <div class="relative bg-gradient-to-b from-[#161920]/92 to-[#080a0e]/90 
                    border border-[#c9a227]/[0.22] 
                    shadow-[0_12px_32px_rgba(0,0,0,.55),inset_0_0_60px_rgba(0,0,0,.45)]
                    p-6 md:p-8 panel-gold-corners">
            <button class="absolute top-4 right-4 text-gray-400 hover:text-white text-2xl transition-colors" 
                    onclick="closeDeleteModal()">&times;</button>
            <div class="text-center">
                <div class="text-5xl text-red-500 mb-4">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3 class="wow-title text-2xl mb-4 font-black 
                           bg-gradient-to-b from-[#fff7d6] via-[#f2cf5b] via-[#c9a227] to-[#8a6a14] 
                           bg-clip-text text-transparent drop-shadow-[0_3px_6px_rgba(0,0,0,.85)]">
                    <?php echo translate('confirm_delete_title', 'Confirm Delete'); ?>
                </h3>
                <p class="text-gray-300 mb-2"><?php echo translate('confirm_delete_vote_site', 'Are you sure you want to delete this vote site?'); ?></p>
                <p class="text-red-400 text-sm font-semibold" id="deleteSiteName"></p>
                <p class="text-gray-500 text-xs mt-2"><?php echo translate('confirm_delete_irreversible', 'This action cannot be undone.'); ?></p>
                <form method="GET" action="<?php echo $base_path; ?>admin/settings/vote_sites" class="flex justify-center gap-4 mt-6">
                    <input type="hidden" name="delete" id="deleteSiteId" value="">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <button type="button" class="btn-clip inline-flex items-center gap-2 px-6 py-3 
                                                 font-extrabold text-xs uppercase tracking-wider
                                                 bg-gradient-to-b from-[#3a404d] via-[#232833] to-[#141821] 
                                                 text-[#cfe1ff] shadow-[inset_0_0_0_1px_rgba(120,160,255,.25),inset_0_-8px_14px_rgba(0,0,0,.4)]
                                                 hover:scale-105 transition-transform duration-200" 
                            onclick="closeDeleteModal()">
                        <?php echo translate('btn_cancel', 'Cancel'); ?>
                    </button>
                    <button type="submit" class="btn-clip inline-flex items-center gap-2 px-6 py-3 
                                                 font-extrabold text-xs uppercase tracking-wider
                                                 bg-gradient-to-b from-[#ff4d4d] via-[#cc0000] to-[#8a0000] 
                                                 text-white shadow-[inset_0_0_0_1px_rgba(255,255,255,.2),inset_0_-8px_14px_rgba(0,0,0,.3)]
                                                 hover:scale-105 transition-transform duration-200">
                        <i class="fas fa-trash"></i>
                        <?php echo translate('btn_confirm_delete', 'Confirm Delete'); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openDeleteModal(id, name) {
            document.getElementById('deleteSiteId').value = id;
            document.getElementById('deleteSiteName').textContent = 'Site: ' + name;
            document.getElementById('deleteModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
            document.body.style.overflow = '';
        }

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDeleteModal();
            }
        });

        // Close on overlay click
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('button_image');
            const uploadPlaceholder = document.getElementById('uploadPlaceholder');
            const fileName = document.getElementById('file-name');

            if (uploadArea && fileInput) {
                uploadArea.addEventListener('click', () => fileInput.click());

                uploadArea.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    uploadArea.classList.add('border-[#f2cf5b]', 'bg-[#c9a227]/10');
                });

                uploadArea.addEventListener('dragleave', () => {
                    uploadArea.classList.remove('border-[#f2cf5b]', 'bg-[#c9a227]/10');
                });

                uploadArea.addEventListener('drop', (e) => {
                    e.preventDefault();
                    uploadArea.classList.remove('border-[#f2cf5b]', 'bg-[#c9a227]/10');
                    if (e.dataTransfer.files.length) {
                        fileInput.files = e.dataTransfer.files;
                        fileInput.dispatchEvent(new Event('change'));
                    }
                });

                fileInput.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        const file = this.files[0];
                        const maxSize = 1 * 1024 * 1024;
                        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

                        if (!allowedTypes.includes(file.type)) {
                            alert('Invalid file type. Please upload JPEG, PNG, or GIF.');
                            this.value = '';
                            return;
                        }
                        if (file.size > maxSize) {
                            alert('File size exceeds 1MB limit.');
                            this.value = '';
                            return;
                        }

                        fileName.textContent = 'Selected: ' + file.name;
                        fileName.classList.remove('hidden');
                        uploadPlaceholder.style.display = 'none';
                    } else {
                        fileName.classList.add('hidden');
                        uploadPlaceholder.style.display = 'block';
                    }
                });
            }
        });
    </script>
</body>
</html>