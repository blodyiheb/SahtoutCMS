<?php
require_once '../includes/session.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'moderator'])) {
    header("Location: /Sahtout/pages/login.php");
    exit;
}

$page_class = 'shop';

// Pagination settings
$items_per_page = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $items_per_page;

// Handle filter and search
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$valid_categories = ['Mount', 'Pet', 'Gold', 'Service', 'Stuff'];

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

// Directory for image uploads
$base_upload_dir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'shopimg' . DIRECTORY_SEPARATOR;
$base_upload_url = 'img/shopimg/';

// Map categories to subdirectories
$category_dirs = [
    'Gold' => 'gold',
    'Mount' => 'items',
    'Pet' => 'items',
    'Stuff' => 'items',
    'Service' => 'services'
];

// Ensure upload subdirectories exist
foreach ($category_dirs as $dir) {
    $full_dir = $base_upload_dir . $dir;
    if (!file_exists($full_dir)) {
        mkdir($full_dir, 0755, true);
    }
    if (!is_writable($full_dir)) {
        error_log("Directory not writable: $full_dir", 3, __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'upload_errors.log');
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            $image = null;

            // Validate category
            if (!in_array($category, $valid_categories)) {
                header("Location: ashop.php?status=error&message=Invalid category&page=$page" . ($category_filter ? "&category=$category_filter" : "") . ($search_query ? "&search=" . urlencode($search_query) : ""));
                exit;
            }

            // Validate entry for Mount, Pet, Stuff
            if (in_array($category, ['Mount', 'Pet', 'Stuff']) && $entry !== null) {
                $sql = "SELECT COUNT(*) FROM site_items WHERE entry = ?";
                $stmt = $site_db->prepare($sql);
                $stmt->bind_param("i", $entry);
                $stmt->execute();
                $stmt->bind_result($count);
                $stmt->fetch();
                $stmt->close();
                if ($count == 0) {
                    header("Location: ashop.php?status=error&message=Invalid entry ID&page=$page" . ($category_filter ? "&category=$category_filter" : "") . ($search_query ? "&search=" . urlencode($search_query) : ""));
                    exit;
                }
            }

            // Only validate level boost if category is Service
            if ($category === 'Service' && $level_boost !== null && ($level_boost < 2 || $level_boost > 255)) {
                header("Location: ashop.php?status=error&message=Level boost must be between 2 and 255&page=$page" . ($category_filter ? "&category=$category_filter" : "") . ($search_query ? "&search=" . urlencode($search_query) : ""));
                exit;
            }

            // Determine upload directory based on category
            $upload_subdir = $category_dirs[$category] ?? 'items';
            $upload_dir = $base_upload_dir . $upload_subdir . DIRECTORY_SEPARATOR;
            $upload_url = $base_upload_url . $upload_subdir . '/';

            // Check upload directory permissions
            if (!is_writable($upload_dir)) {
                error_log("Upload directory not writable: $upload_dir", 3, __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'upload_errors.log');
                header("Location: ashop.php?status=error&message=Upload directory is not writable&page=$page" . ($category_filter ? "&category=$category_filter" : "") . ($search_query ? "&search=" . urlencode($search_query) : ""));
                exit;
            }

            // Handle file upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 2 * 1024 * 1024; // 2MB
                $file = $_FILES['image'];

                // Log file details for debugging
                error_log("File upload attempt: name={$file['name']}, type={$file['type']}, size={$file['size']}, error={$file['error']}, category=$category", 3, __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'upload_errors.log');

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $error_messages = [
                        UPLOAD_ERR_INI_SIZE => 'File size exceeds server limit (upload_max_filesize)',
                        UPLOAD_ERR_FORM_SIZE => 'File size exceeds form limit',
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary directory',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload'
                    ];
                    $error_message = isset($error_messages[$file['error']]) ? $error_messages[$file['error']] : 'Unknown upload error';
                    error_log("Upload error: $error_message", 3, __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'upload_errors.log');
                    header("Location: ashop.php?status=error&message=" . urlencode($error_message) . "&page=$page" . ($category_filter ? "&category=$category_filter" : "") . ($search_query ? "&search=" . urlencode($search_query) : ""));
                    exit;
                }

                if (!in_array($file['type'], $allowed_types)) {
                    error_log("Invalid file type: {$file['type']}", 3, __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'upload_errors.log');
                    header("Location: ashop.php?status=error&message=Invalid file type. Only JPG, PNG, GIF allowed&page=$page" . ($category_filter ? "&category=$category_filter" : "") . ($search_query ? "&search=" . urlencode($search_query) : ""));
                    exit;
                }
                if ($file['size'] > $max_size) {
                    error_log("File size exceeds limit: {$file['size']} bytes", 3, __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'upload_errors.log');
                    header("Location: ashop.php?status=error&message=File size exceeds 2MB limit&page=$page" . ($category_filter ? "&category=$category_filter" : "") . ($search_query ? "&search=" . urlencode($search_query) : ""));
                    exit;
                }

                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $filename = uniqid('shop_item_') . '.' . $ext;
                $destination = $upload_dir . $filename;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $image = $upload_url . $filename;
                    error_log("File uploaded successfully: $image", 3, __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'upload_errors.log');
                    // Delete old image if editing
                    if ($action === 'edit' && isset($_POST['existing_image']) && $_POST['existing_image']) {
                        $old_image_path = str_replace($base_upload_url, $base_upload_dir, $_POST['existing_image']);
                        if (file_exists($old_image_path)) {
                            unlink($old_image_path);
                            error_log("Deleted old image: $old_image_path", 3, __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'upload_errors.log');
                        }
                    }
                } else {
                    error_log("Failed to move uploaded file to: $destination", 3, __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'upload_errors.log');
                    header("Location: ashop.php?status=error&message=Failed to move uploaded file&page=$page" . ($category_filter ? "&category=$category_filter" : "") . ($search_query ? "&search=" . urlencode($search_query) : ""));
                    exit;
                }
            } elseif ($action === 'edit' && isset($_POST['existing_image'])) {
                $image = $_POST['existing_image']; // Retain existing image if no new file uploaded
            }

            // Set fields to NULL or defaults based on category
            if ($category === 'Gold') {
                $entry = null;
                $level_boost = null;
                $at_login_flags = 0;
                $is_item = 0;
            } elseif ($category === 'Mount' || $category === 'Pet' || $category === 'Stuff') {
                $description = null;
                $gold_amount = 0;
                $level_boost = null;
                $at_login_flags = 0;
            } elseif ($category === 'Service') {
                $entry = null;
                $gold_amount = 0;
                $is_item = 0;
            }

            try {
                if ($action === 'add') {
                    $sql = "INSERT INTO shop_items (category, name, description, image, point_cost, token_cost, stock, entry, gold_amount, level_boost, at_login_flags, is_item) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $site_db->prepare($sql);
                    $stmt->bind_param("ssssiiiiiiii", $category, $name, $description, $image, $point_cost, $token_cost, $stock, $entry, $gold_amount, $level_boost, $at_login_flags, $is_item);
                } else {
                    $sql = "UPDATE shop_items SET category = ?, name = ?, description = ?, image = ?, point_cost = ?, token_cost = ?, stock = ?, entry = ?, gold_amount = ?, level_boost = ?, at_login_flags = ?, is_item = ? WHERE item_id = ?";
                    $stmt = $site_db->prepare($sql);
                    $stmt->bind_param("ssssiiiiiiiii", $category, $name, $description, $image, $point_cost, $token_cost, $stock, $entry, $gold_amount, $level_boost, $at_login_flags, $is_item, $item_id);
                }
                
                if ($stmt->execute()) {
                    header("Location: ashop.php?status=success&page=$page" . ($category_filter ? "&category=$category_filter" : "") . ($search_query ? "&search=" . urlencode($search_query) : ""));
                    exit;
                } else {
                    throw new Exception("Database error: " . $stmt->error);
                }
            } catch (Exception $e) {
                error_log("Database error: " . $e->getMessage(), 3, __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'upload_errors.log');
                header("Location: ashop.php?status=error&message=" . urlencode($e->getMessage()) . "&page=$page" . ($category_filter ? "&category=$category_filter" : "") . ($search_query ? "&search=" . urlencode($search_query) : ""));
                exit;
            } finally {
                if (isset($stmt)) $stmt->close();
            }
        } elseif ($action === 'delete') {
            $item_id = (int)$_POST['item_id'];
            try {
                // Get existing image to delete it
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
                            error_log("Deleted image on item delete: $file_path", 3, __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'upload_errors.log');
                        }
                    }
                }
                $stmt->close();

                $sql = "DELETE FROM shop_items WHERE item_id = ?";
                $stmt = $site_db->prepare($sql);
                $stmt->bind_param("i", $item_id);
                
                if ($stmt->execute()) {
                    header("Location: ashop.php?status=success&page=$page" . ($category_filter ? "&category=$category_filter" : "") . ($search_query ? "&search=" . urlencode($search_query) : ""));
                    exit;
                } else {
                    throw new Exception("Database error: " . $stmt->error);
                }
            } catch (Exception $e) {
                error_log("Delete error: " . $e->getMessage(), 3, __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'upload_errors.log');
                header("Location: ashop.php?status=error&message=" . urlencode($e->getMessage()) . "&page=$page" . ($category_filter ? "&category=$category_filter" : "") . ($search_query ? "&search=" . urlencode($search_query) : ""));
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
    error_log("Error counting shop items: " . $e->getMessage(), 3, __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'upload_errors.log');
}

// Fetch shop items for current page
$items = [];
try {
    $sql = "SELECT si.*, sit.name as entry_name FROM shop_items si LEFT JOIN site_items sit ON si.entry = sit.entry WHERE 1=1";
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
    error_log("Error fetching shop items: " . $e->getMessage(), 3, __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'upload_errors.log');
} finally {
    if (isset($stmt)) $stmt->close();
}

// Status messages
$status_message = '';
if (isset($_GET['status'])) {
    $status_class = $_GET['status'] === 'success' ? 'success' : 'danger';
    $message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 
              ($_GET['status'] === 'success' ? 'Operation successful!' : 'An error occurred.');
    
    $status_message = '<div class="alert alert-' . $status_class . '">' . $message . '</div>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Shop Management for Sahtout WoW Server">
    <meta name="robots" content="noindex">
    <title>Shop Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Sahtout/assets/css/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            display: flex;
            flex-direction: column;
        }
        body {
            background-color: #ffffff;
            color: #333;
            font-family: Arial, sans-serif;
        }
        .wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .dashboard-container {
            flex-grow: 1;
            padding-bottom: 20px;
        }
        .dashboard-title {
            font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;
            font-size: 2.5rem;
            text-align: center;
            margin-bottom: 1.5rem;
            color: #333;
        }
        .card {
            text-align: center;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid #ccc;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .card-header {
            background: rgba(230, 230, 230, 0.9);
            border-bottom: 1px solid #ccc;
            color: #333;
            font-size: 1.25rem;
            padding: 0.75rem 1rem;
        }
        .card-body {
            padding: 1rem;
        }
        .table {
            color: #333;
            background: none;
            width: 100%;
        }
        .table th, .table td {
            border: 1px solid #ccc;
            padding: 0.5rem;
            vertical-align: middle;
            transition: all 0.3s ease;
        }
        .table th {
            background: rgba(240, 240, 240, 0.9);
            color: #000;
        }
        .table tbody tr:hover {
            background: rgba(200, 200, 200, 0.2);
            cursor: pointer;
        }
        .table tbody tr:hover td {
            color: #000;
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        }
        .table .btn {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            margin-right: 0.5rem;
        }
        .table .btn-edit {
            background: #007bff;
            border: 2px solid #0056b3;
            color: #fff;
        }
        .table .btn-edit:hover {
            background: #0056b3;
            border-color: #003087;
        }
        .table .btn-danger {
            background: #dc3545;
            border: 2px solid #a71d2a;
            color: #fff;
        }
        .table .btn-danger:hover {
            background: #a71d2a;
            border-color: #721c24;
        }
        .form-control, .form-select {
            background: #f9f9f9;
            color: #333;
            border: 1px solid #999;
        }
        .form-control:focus, .form-select:focus {
            background: #fff;
            color: #000;
            border-color: #666;
            box-shadow: none;
        }
        .form-control::placeholder {
            color: #666;
            font-weight: bold;
            opacity: 1;
        }
        .form-label {
            color: #333;
        }
        .btn-primary {
            background: #007bff;
            border: 2px solid #0056b3;
            color: #fff;
        }
        .btn-primary:hover {
            background: #0056b3;
            border-color: #003087;
        }
        .btn-secondary {
            background: #6c757d;
            border: 2px solid #5a6268;
            color: #fff;
        }
        .btn-secondary:hover {
            background: #5a6268;
            border-color: #4b5257;
        }
        .alert {
            border-radius: 5px;
            padding: 10px 15px;
            margin-bottom: 1rem;
        }
        .form-group {
            display: none;
        }
        .form-group.active {
            display: block;
        }
        .pagination {
            justify-content: center;
            margin-top: 1.5rem;
        }
        .pagination .page-link {
            background: #f9f9f9;
            color: #333;
            border: 1px solid #999;
            margin: 0 0.2rem;
            padding: 0.5rem 0.75rem;
            transition: all 0.3s ease;
        }
        .pagination .page-link:hover {
            background: #e9ecef;
            color: #000;
            border-color: #666;
        }
        .pagination .page-item.active .page-link {
            background: #007bff;
            color: #fff;
            border-color: #0056b3;
        }
        .pagination .page-item.disabled .page-link {
            background: #f9f9f9;
            color: #6c757d;
            border-color: #999;
        }
        .required-field::after {
            content: ' *';
            color: #dc3545;
        }
        .table-wrapper {
            overflow-x: auto;
        }
        .filter-search-form {
            margin-bottom: 1.5rem;
        }
        .image-preview {
            max-width: 100px;
            max-height: 100px;
            margin-top: 0.5rem;
            display: none;
        }
        .image-preview.active {
            display: block;
        }
        @media (max-width: 768px) {
            .dashboard-container {
                width: calc(100% - 2rem);
                margin: 1rem auto;
                padding: 0 1rem;
            }
            .dashboard-title {
                font-size: 2rem;
            }
            .card-header {
                font-size: 1.1rem;
            }
            .table {
                font-size: 0.9rem;
            }
            .pagination .page-link {
                padding: 0.3rem 0.5rem;
                font-size: 0.9rem;
            }
            .filter-search-form .row {
                flex-direction: column;
            }
            .filter-search-form .col-md-4 {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body class="shop">
    <div class="wrapper">
        <?php include '../includes/header.php'; ?>
        <div class="dashboard-container">
            <div class="row">
                <?php include '../includes/admin_sidebar.php'; ?>
                <div class="col-md-9">
                    <h1 class="dashboard-title">Shop Management</h1>
                    <?php echo $status_message; ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Add/Edit Shop Item</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="mb-4" id="itemForm" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="add" id="formAction">
                                <input type="hidden" name="item_id" id="item_id">
                                <input type="hidden" name="existing_image" id="existing_image">
                                
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="category" class="form-label required-field">Category</label>
                                        <select name="category" id="category" class="form-select" required>
                                            <option value="Mount">Mount</option>
                                            <option value="Pet">Pet</option>
                                            <option value="Gold">Gold</option>
                                            <option value="Service">Service</option>
                                            <option value="Stuff">Stuff</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4 form-group name-group active">
                                        <label for="name" class="form-label required-field">Name</label>
                                        <input type="text" name="name" id="name" class="form-control" required maxlength="100">
                                    </div>
                                    
                                    <div class="col-md-4 form-group point-cost-group active">
                                        <label for="point_cost" class="form-label required-field">Point Cost</label>
                                        <input type="number" name="point_cost" id="point_cost" class="form-control" min="0" required>
                                    </div>
                                    
                                    <div class="col-md-4 form-group token-cost-group active">
                                        <label for="token_cost" class="form-label required-field">Token Cost</label>
                                        <input type="number" name="token_cost" id="token_cost" class="form-control" min="0" required>
                                    </div>
                                    
                                    <div class="col-md-4 form-group stock-group active">
                                        <label for="stock" class="form-label">Stock (leave empty for unlimited)</label>
                                        <input type="number" name="stock" id="stock" class="form-control" min="0">
                                    </div>
                                    
                                    <div class="col-md-4 form-group entry-group">
                                        <label for="entry" class="form-label">Item Entry (Optional)</label>
                                        <select name="entry" id="entry" class="form-select">
                                            <option value="">Select Entry</option>
                                            <?php foreach ($site_items as $item): ?>
                                                <option value="<?php echo $item['entry']; ?>">
                                                    <?php echo htmlspecialchars($item['entry'] . ' - ' . $item['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4 form-group gold-amount-group">
                                        <label for="gold_amount" class="form-label">Gold Amount</label>
                                        <input type="number" name="gold_amount" id="gold_amount" class="form-control" min="0" value="0">
                                    </div>
                                    
                                    <div class="col-md-4 form-group level-boost-group">
                                        <label for="level_boost" class="form-label">Level Boost (2-255)</label>
                                        <input type="number" name="level_boost" id="level_boost" class="form-control" min="2" max="255">
                                    </div>
                                    
                                    <div class="col-md-4 form-group at-login-flags-group">
                                        <label for="at_login_flags" class="form-label">Login Flags</label>
                                        <select name="at_login_flags" id="at_login_flags" class="form-select">
                                            <option value="0">None</option>
                                            <option value="1">Force character to change name</option>
                                            <option value="2">Reset spells (professions as well)</option>
                                            <option value="4">Reset Talents</option>
                                            <option value="8">Customize Character</option>
                                            <option value="16">Reset Pet Talents</option>
                                            <option value="32">First Login</option>
                                            <option value="64">Faction Change</option>
                                            <option value="128">Race Change</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4 form-group is-item-group">
                                        <label for="is_item" class="form-label">Is Item?</label>
                                        <select name="is_item" id="is_item" class="form-select">
                                            <option value="0">No</option>
                                            <option value="1">Yes</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-8 form-group image-group active">
                                        <label for="image" class="form-label">Image Upload (JPG, PNG, GIF, max 2MB)</label>
                                        <input type="file" name="image" id="image" class="form-control" accept="image/jpeg,image/png,image/gif">
                                        <small class="form-text text-muted">Leave blank to keep existing image when editing.</small>
                                        <img id="image_preview" class="image-preview" src="" alt="Image Preview">
                                    </div>
                                    
                                    <div class="col-12 form-group description-group">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea name="description" id="description" class="form-control" rows="4"></textarea>
                                    </div>
                                    
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary" id="submitBtn">Add Item</button>
                                        <button type="button" id="cancelEdit" class="btn btn-secondary" style="display:none;">Cancel</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Shop Items</h5>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="filter-search-form mb-4">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="category_filter" class="form-label">Filter by Category</label>
                                        <select name="category" id="category_filter" class="form-select">
                                            <option value="">All Categories</option>
                                            <?php foreach ($valid_categories as $cat): ?>
                                                <option value="<?php echo $cat; ?>" <?php echo $category_filter === $cat ? 'selected' : ''; ?>>
                                                    <?php echo $cat; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="search" class="form-label">Search by Name</label>
                                        <input type="text" name="search" id="search" class="form-control" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Enter item name">
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary w-100">Apply</button>
                                    </div>
                                </div>
                            </form>
                            <div class="table-wrapper">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Category</th>
                                            <th>Name</th>
                                            <th>Points</th>
                                            <th>Tokens</th>
                                            <th>Stock</th>
                                            <th>Image</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($items)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center">No items found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($items as $row): ?>
                                                <tr>
                                                    <td><?php echo $row['item_id']; ?></td>
                                                    <td><?php echo htmlspecialchars($row['category']); ?></td>
                                                    <td>
                                                        <?php echo htmlspecialchars($row['name']); ?>
                                                        <?php if (!empty($row['entry_name'])): ?>
                                                            <br><small class="text-muted">Item: <?php echo htmlspecialchars($row['entry_name']); ?></small>
                                                        <?php elseif ($row['gold_amount'] > 0): ?>
                                                            <br><small class="text-muted">Gold: <?php echo number_format($row['gold_amount']); ?></small>
                                                        <?php elseif ($row['level_boost']): ?>
                                                            <br><small class="text-muted">Level: +<?php echo $row['level_boost']; ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo $row['point_cost']; ?></td>
                                                    <td><?php echo $row['token_cost']; ?></td>
                                                    <td><?php echo $row['stock'] ?? '∞'; ?></td>
                                                    <td>
                                                        <?php if ($row['image']): ?>
                                                            <img src="<?php echo htmlspecialchars($row['image']); ?>" alt="Item Image" style="max-width: 50px; max-height: 50px;">
                                                        <?php else: ?>
                                                            No Image
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-edit edit-item" 
                                                                data-item='<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES); ?>'>
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="item_id" value="<?php echo $row['item_id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger" 
                                                                    onclick="return confirm('Are you sure you want to delete this item?');">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Pagination Controls -->
                            <?php if ($total_pages > 1): ?>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination">
                                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="admin/ashop.php?page=<?php echo $page - 1; ?><?php echo $category_filter ? '&category=' . urlencode($category_filter) : ''; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                                <a class="page-link" href="admin/ashop.php?page=<?php echo $i; ?><?php echo $category_filter ? '&category=' . urlencode($category_filter) : ''; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="admin/ashop.php?page=<?php echo $page + 1; ?><?php echo $category_filter ? '&category=' . urlencode($category_filter) : ''; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include '../includes/footer.php'; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const categorySelect = document.getElementById('category');
            const form = document.getElementById('itemForm');
            const formAction = document.getElementById('formAction');
            const submitBtn = document.getElementById('submitBtn');
            const cancelBtn = document.getElementById('cancelEdit');
            const imageInput = document.getElementById('image');
            const imagePreview = document.getElementById('image_preview');
            const existingImageInput = document.getElementById('existing_image');
            
            // Field groups configuration
            const fieldGroups = {
                'name-group': ['Mount', 'Pet', 'Gold', 'Service', 'Stuff'],
                'point-cost-group': ['Mount', 'Pet', 'Gold', 'Service', 'Stuff'],
                'token-cost-group': ['Mount', 'Pet', 'Gold', 'Service', 'Stuff'],
                'stock-group': ['Mount', 'Pet', 'Gold', 'Service', 'Stuff'],
                'entry-group': ['Mount', 'Pet', 'Stuff'],
                'gold-amount-group': ['Gold'],
                'level-boost-group': ['Service'],
                'at-login-flags-group': ['Service'],
                'is-item-group': ['Mount', 'Pet', 'Stuff'],
                'image-group': ['Mount', 'Pet', 'Gold', 'Service', 'Stuff'],
                'description-group': ['Service', 'Gold']
            };
            
            // Update form fields based on category
            function updateFormFields() {
                const category = categorySelect.value;
                
                // Toggle field groups
                Object.keys(fieldGroups).forEach(group => {
                    const element = document.querySelector(`.${group}`);
                    const shouldShow = fieldGroups[group].includes(category);
                    
                    if (element) {
                        element.classList.toggle('active', shouldShow);
                        
                        // Clear fields when hidden
                        if (!shouldShow) {
                            const inputs = element.querySelectorAll('input, select, textarea');
                            inputs.forEach(input => {
                                if (input.tagName === 'SELECT') {
                                    input.value = '';
                                } else {
                                    input.value = '';
                                }
                            });
                        }
                    }
                });
                
                // Special handling for Gold category
                if (category === 'Gold') {
                    document.getElementById('is_item').value = '0';
                }
            }
            
            // Initialize form fields
            updateFormFields();
            
            // Handle category change
            categorySelect.addEventListener('change', updateFormFields);
            
            // Image preview
            imageInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    const maxSize = 2 * 1024 * 1024; // 2MB
                    
                    if (!allowedTypes.includes(file.type)) {
                        alert('Invalid file type. Only JPG, PNG, or GIF allowed.');
                        this.value = '';
                        imagePreview.classList.remove('active');
                        imagePreview.src = '';
                        return;
                    }
                    if (file.size > maxSize) {
                        alert('File size exceeds 2MB limit.');
                        this.value = '';
                        imagePreview.classList.remove('active');
                        imagePreview.src = '';
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                        imagePreview.classList.add('active');
                    };
                    reader.readAsDataURL(file);
                } else {
                    imagePreview.classList.remove('active');
                    imagePreview.src = '';
                }
            });
            
            // Edit item button handler
            document.querySelectorAll('.edit-item').forEach(button => {
                button.addEventListener('click', function() {
                    const item = JSON.parse(this.dataset.item);
                    
                    // Set form to edit mode
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
                    existingImageInput.value = item.image || '';
                    imagePreview.src = item.image || '';
                    imagePreview.classList.toggle('active', !!item.image);
                    imageInput.value = ''; // Clear file input
                    
                    // Update UI
                    submitBtn.textContent = 'Update Item';
                    cancelBtn.style.display = 'inline-block';
                    
                    // Update form fields based on selected category
                    updateFormFields();
                    
                    // Scroll to form
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
                submitBtn.textContent = 'Add Item';
                this.style.display = 'none';
                updateFormFields();
            });
            
            // Form validation
            form.addEventListener('submit', function(e) {
                const category = document.getElementById('category').value;
                const name = document.getElementById('name').value.trim();
                const pointCost = document.getElementById('point_cost').value;
                const tokenCost = document.getElementById('token_cost').value;
                
                if (!name || pointCost === '' || tokenCost === '') {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                    return;
                }
                
                // Only validate level boost if category is Service
                if (category === 'Service') {
                    const levelBoost = document.getElementById('level_boost').value;
                    if (levelBoost && (levelBoost < 2 || levelBoost > 255)) {
                        e.preventDefault();
                        alert('Level boost must be between 2 and 255.');
                        return;
                    }
                }
            });
        });
    </script>
</body>
</html>
<?php $site_db->close(); ?>