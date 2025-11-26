<?php
define('ALLOWED_ACCESS', true);
// Include paths.php for $project_root and $base_path
require_once __DIR__ . '/../../includes/paths.php';
require_once $project_root . 'includes/session.php';
require_once $project_root . 'languages/language.php';
require_once $project_root . 'includes/config.settings.php';
// Check if user is admin or moderator
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'moderator'])) {
    header("Location: {$base_path}login");
    exit;
}

$page_class = 'anews';
global $site_db;

// Get current username for posted_by
$current_username = $_SESSION['username'] ?? translate('admin_news_unknown_user', 'Unknown');

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Directory for image uploads
$base_upload_dir = $project_root . 'img/newsimg/';
$base_upload_url = 'img/newsimg/';
$default_image_url = 'img/newsimg/news.png';

// Ensure upload directory exists
if (!file_exists($base_upload_dir)) {
    mkdir($base_upload_dir, 0755, true);
}
if (!is_writable($base_upload_dir)) {
    error_log("Directory not writable: $base_upload_dir", 3, $project_root . 'logs/upload_errors.log');
}

// Check if default image exists
$default_image_path = $base_upload_dir . 'news.png';
if (!file_exists($default_image_path)) {
    error_log("Default image missing: $default_image_path", 3, $project_root . 'logs/upload_errors.log');
}

// Directory for logs
$log_dir = $project_root . 'logs/';
$log_file = $log_dir . 'upload_errors.log';
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
}
if (!is_writable($log_dir)) {
    error_log("Log directory not writable: $log_dir", 3, $log_file);
}

// Handle form submissions
$update_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $update_message = '<div class="alert alert-danger">' . translate('admin_news_csrf_error', 'CSRF token validation failed.') . '</div>';
        } else {
            $title = trim($_POST['title']);
            $slug = trim($_POST['slug']);
            $content = trim($_POST['content']);
            $category = in_array($_POST['category'], ['update', 'event', 'maintenance', 'other']) ? $_POST['category'] : 'update';
            $is_important = isset($_POST['is_important']) ? 1 : 0;
            $image_url = $default_image_url;

            // Handle file upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 2 * 1024 * 1024; // 2MB
                $file = $_FILES['image'];

                // Log file details
                error_log("File upload attempt: name={$file['name']}, type={$file['type']}, size={$file['size']}, error={$file['error']}", 3, $log_file);

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $error_messages = [
                        UPLOAD_ERR_INI_SIZE => translate('admin_news_upload_err_ini_size', 'File size exceeds server limit (upload_max_filesize).'),
                        UPLOAD_ERR_FORM_SIZE => translate('admin_news_upload_err_form_size', 'File size exceeds form limit.'),
                        UPLOAD_ERR_PARTIAL => translate('admin_news_upload_err_partial', 'File was only partially uploaded.'),
                        UPLOAD_ERR_NO_FILE => translate('admin_news_upload_err_no_file', 'No file was uploaded.'),
                        UPLOAD_ERR_NO_TMP_DIR => translate('admin_news_upload_err_no_tmp_dir', 'Missing temporary directory.'),
                        UPLOAD_ERR_CANT_WRITE => translate('admin_news_upload_err_cant_write', 'Failed to write file to disk.'),
                        UPLOAD_ERR_EXTENSION => translate('admin_news_upload_err_extension', 'A PHP extension stopped the upload.')
                    ];
                    $error_message = isset($error_messages[$file['error']]) ? $error_messages[$file['error']] : translate('admin_news_upload_err_unknown', 'Unknown upload error.');
                    error_log("Upload error: $error_message", 3, $log_file);
                    $update_message = '<div class="alert alert-danger">' . htmlspecialchars($error_message) . '</div>';
                } elseif (!in_array($file['type'], $allowed_types)) {
                    error_log("Invalid file type: {$file['type']}", 3, $log_file);
                    $update_message = '<div class="alert alert-danger">' . translate('admin_news_invalid_file_type', 'Invalid file type. Only JPG, PNG, GIF allowed.') . '</div>';
                } elseif ($file['size'] > $max_size) {
                    error_log("File size exceeds limit: {$file['size']} bytes", 3, $log_file);
                    $update_message = '<div class="alert alert-danger">' . translate('admin_news_file_size_exceeded', 'File size exceeds 2MB limit.') . '</div>';
                } else {
                    if (!is_writable($base_upload_dir)) {
                        error_log("Upload directory not writable: $base_upload_dir", 3, $log_file);
                        $update_message = '<div class="alert alert-danger">' . translate('admin_news_upload_dir_not_writable', 'Upload directory is not writable.') . '</div>';
                    } else {
                        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        $filename = uniqid('news_') . '.' . $ext;
                        $destination = $base_upload_dir . $filename;

                        if (move_uploaded_file($file['tmp_name'], $destination)) {
                            $image_url = $base_upload_url . $filename;
                            error_log("File uploaded successfully: $image_url", 3, $log_file);
                        } else {
                            error_log("Failed to move uploaded file to: $destination", 3, $log_file);
                            $update_message = '<div class="alert alert-danger">' . translate('admin_news_upload_failed', 'Failed to move uploaded file.') . '</div>';
                        }
                    }
                }
            }

            if (empty($update_message)) {
                $stmt = $site_db->prepare("INSERT INTO server_news (title, slug, content, posted_by, category, is_important, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssis", $title, $slug, $content, $current_username, $category, $is_important, $image_url);
                if ($stmt->execute()) {
                    $update_message = '<div class="alert alert-success">' . translate('admin_news_add_success', 'News added successfully.') . '</div>';
                } else {
                    $update_message = '<div class="alert alert-danger">' . sprintf(translate('admin_news_add_failed', 'Failed to add news: %s'), htmlspecialchars($site_db->error)) . '</div>';
                }
                $stmt->close();
            }
        }
    } elseif ($_POST['action'] === 'update') {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $update_message = '<div class="alert alert-danger">' . translate('admin_news_csrf_error', 'CSRF token validation failed.') . '</div>';
        } else {
            $id = (int)$_POST['id'];
            $title = trim($_POST['title']);
            $slug = trim($_POST['slug']);
            $content = trim($_POST['content']);
            $category = in_array($_POST['category'], ['update', 'event', 'maintenance', 'other']) ? $_POST['category'] : 'update';
            $is_important = isset($_POST['is_important']) ? 1 : 0;
            $image_url = isset($_POST['existing_image']) && !empty($_POST['existing_image']) ? trim($_POST['existing_image']) : $default_image_url;

            // Handle file upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 2 * 1024 * 1024; // 2MB
                $file = $_FILES['image'];

                // Log file details
                error_log("File upload attempt: name={$file['name']}, type={$file['type']}, size={$file['size']}, error={$file['error']}", 3, $log_file);

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $error_messages = [
                        UPLOAD_ERR_INI_SIZE => translate('admin_news_upload_err_ini_size', 'File size exceeds server limit (upload_max_filesize).'),
                        UPLOAD_ERR_FORM_SIZE => translate('admin_news_upload_err_form_size', 'File size exceeds form limit.'),
                        UPLOAD_ERR_PARTIAL => translate('admin_news_upload_err_partial', 'File was only partially uploaded.'),
                        UPLOAD_ERR_NO_FILE => translate('admin_news_upload_err_no_file', 'No file was uploaded.'),
                        UPLOAD_ERR_NO_TMP_DIR => translate('admin_news_upload_err_no_tmp_dir', 'Missing temporary directory.'),
                        UPLOAD_ERR_CANT_WRITE => translate('admin_news_upload_err_cant_write', 'Failed to write file to disk.'),
                        UPLOAD_ERR_EXTENSION => translate('admin_news_upload_err_extension', 'A PHP extension stopped the upload.')
                    ];
                    $error_message = isset($error_messages[$file['error']]) ? $error_messages[$file['error']] : translate('admin_news_upload_err_unknown', 'Unknown upload error.');
                    error_log("Upload error: $error_message", 3, $log_file);
                    $update_message = '<div class="alert alert-danger">' . htmlspecialchars($error_message) . '</div>';
                } elseif (!in_array($file['type'], $allowed_types)) {
                    error_log("Invalid file type: {$file['type']}", 3, $log_file);
                    $update_message = '<div class="alert alert-danger">' . translate('admin_news_invalid_file_type', 'Invalid file type. Only JPG, PNG, GIF allowed.') . '</div>';
                } elseif ($file['size'] > $max_size) {
                    error_log("File size exceeds limit: {$file['size']} bytes", 3, $log_file);
                    $update_message = '<div class="alert alert-danger">' . translate('admin_news_file_size_exceeded', 'File size exceeds 2MB limit.') . '</div>';
                } else {
                    if (!is_writable($base_upload_dir)) {
                        error_log("Upload directory not writable: $base_upload_dir", 3, $log_file);
                        $update_message = '<div class="alert alert-danger">' . translate('admin_news_upload_dir_not_writable', 'Upload directory is not writable.') . '</div>';
                    } else {
                        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        $filename = uniqid('news_') . '.' . $ext;
                        $destination = $base_upload_dir . $filename;

                        if (move_uploaded_file($file['tmp_name'], $destination)) {
                            $image_url = $base_upload_url . $filename;
                            error_log("File uploaded successfully: $image_url", 3, $log_file);
                            // Delete old image if it's not the default
                            if (!empty($_POST['existing_image']) && $_POST['existing_image'] !== $default_image_url) {
                                $old_image_path = str_replace($base_upload_url, $base_upload_dir, $_POST['existing_image']);
                                if (file_exists($old_image_path)) {
                                    unlink($old_image_path);
                                    error_log("Deleted old image: $old_image_path", 3, $log_file);
                                }
                            }
                        } else {
                            error_log("Failed to move uploaded file to: $destination", 3, $log_file);
                            $update_message = '<div class="alert alert-danger">' . translate('admin_news_upload_failed', 'Failed to move uploaded file.') . '</div>';
                        }
                    }
                }
            }

            if (empty($update_message)) {
                $stmt = $site_db->prepare("UPDATE server_news SET title = ?, slug = ?, content = ?, category = ?, is_important = ?, image_url = ? WHERE id = ?");
                $stmt->bind_param("ssssisi", $title, $slug, $content, $category, $is_important, $image_url, $id);
                if ($stmt->execute()) {
                    $update_message = '<div class="alert alert-success">' . translate('admin_news_update_success', 'News updated successfully.') . '</div>';
                } else {
                    $update_message = '<div class="alert alert-danger">' . sprintf(translate('admin_news_update_failed', 'Failed to update news: %s'), htmlspecialchars($site_db->error)) . '</div>';
                }
                $stmt->close();
            }
        }
    } elseif ($_POST['action'] === 'delete') {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $update_message = '<div class="alert alert-danger">' . translate('admin_news_csrf_error', 'CSRF token validation failed.') . '</div>';
        } else {
            $id = (int)$_POST['id'];
            // Delete associated image if not default
            $stmt = $site_db->prepare("SELECT image_url FROM server_news WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                if (!empty($row['image_url']) && $row['image_url'] !== $default_image_url) {
                    $image_path = str_replace($base_upload_url, $base_upload_dir, $row['image_url']);
                    if (file_exists($image_path)) {
                        unlink($image_path);
                        error_log("Deleted image on news delete: $image_path", 3, $log_file);
                    }
                }
            }
            $stmt->close();

            $stmt = $site_db->prepare("DELETE FROM server_news WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $update_message = '<div class="alert alert-success">' . translate('admin_news_delete_success', 'News deleted successfully.') . '</div>';
            } else {
                $update_message = '<div class="alert alert-danger">' . sprintf(translate('admin_news_delete_failed', 'Failed to delete news: %s'), htmlspecialchars($site_db->error)) . '</div>';
            }
            $stmt->close();
        }
    }
}

// Pagination settings
$items_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Count total news articles
$count_query = "SELECT COUNT(*) as total FROM server_news";
$count_result = $site_db->query($count_query);
$total_items = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page);

// Fetch news for current page
$news_query = "SELECT id, title, slug, content, posted_by, category, post_date, image_url, is_important 
               FROM server_news 
               ORDER BY is_important DESC, post_date DESC 
               LIMIT ? OFFSET ?";
$stmt = $site_db->prepare($news_query);
$stmt->bind_param("ii", $items_per_page, $offset);
$stmt->execute();
$news_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo translate('admin_news_meta_description', 'News Management for Sahtout WoW Server'); ?>">
    <meta name="robots" content="noindex">
    <title><?php echo translate('admin_news_page_title', 'News Management'); ?></title>
    <link rel="icon" href="<?php echo $base_path . $site_logo; ?>" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/admin/anews.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/admin/admin_sidebar.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
</head>
<body class="news">
    <div class="wrapper">
        <?php include $project_root . 'includes/header.php'; ?>
        <div class="dashboard-container">
            <div class="row">
                <!-- Sidebar -->
                <?php include $project_root . 'includes/admin_sidebar.php'; ?>
                <!-- Main Content -->
                <div class="col-md-9">
                    <h1 class="dashboard-title"><?php echo translate('admin_news_title', 'News Management'); ?></h1>
                    <?php echo $update_message; ?>
                    <!-- Add News Form -->
                    <div class="card">
                        <div class="card-header"><?php echo translate('admin_news_add_header', 'Add New News'); ?></div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <div class="mb-3">
                                    <label class="form-label"><?php echo translate('admin_news_label_title', 'Title'); ?></label>
                                    <input type="text" name="title" class="form-control" required maxlength="100" placeholder="<?php echo translate('admin_news_placeholder_title', 'Enter news title'); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label"><?php echo translate('admin_news_label_slug', 'Slug'); ?></label>
                                    <input type="text" name="slug" class="form-control" maxlength="120" placeholder="<?php echo translate('admin_news_placeholder_slug', 'Enter slug (optional)'); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label"><?php echo translate('admin_news_label_content', 'Content'); ?></label>
                                    <textarea name="content" class="form-control" rows="5" required placeholder="<?php echo translate('admin_news_placeholder_content', 'Enter news content'); ?>"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label"><?php echo translate('admin_news_label_category', 'Category'); ?></label>
                                    <select name="category" id="category" class="form-select">
                                        <option value="update"><?php echo translate('admin_news_category_update', 'Update'); ?></option>
                                        <option value="event"><?php echo translate('admin_news_category_event', 'Event'); ?></option>
                                        <option value="maintenance"><?php echo translate('admin_news_category_maintenance', 'Maintenance'); ?></option>
                                        <option value="other"><?php echo translate('admin_news_category_other', 'Other'); ?></option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label"><?php echo translate('admin_news_label_image', 'Image Upload (JPG, PNG, GIF, max 2MB, Optional)'); ?></label>
                                    <input type="file" name="image" id="image" class="form-control" accept="image/jpeg,image/png,image/gif">
                                    <small class="form-text text-muted"><?php echo translate('admin_news_image_help', 'Leave blank to use default image (news.png).'); ?></small>
                                    <img id="image_preview" class="image-preview" src="" alt="<?php echo translate('admin_news_image_preview_alt', 'Image Preview'); ?>">
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" name="is_important" class="form-check-input" id="is_important">
                                        <label class="form-check-label" for="is_important" style="color: #dc3545;"><?php echo translate('admin_news_label_is_important', 'Mark as Important'); ?></label>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary"><?php echo translate('admin_news_add_button', 'Add News'); ?></button>
                            </form>
                        </div>
                    </div>
                    <!-- News List -->
                    <div class="card">
                        <div class="card-header"><?php echo translate('admin_news_list_header', 'News Articles'); ?></div>
                        <div class="card-body">
                            <div class="table-wrapper">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th><?php echo translate('admin_news_table_title', 'Title'); ?></th>
                                            <th><?php echo translate('admin_news_table_category', 'Category'); ?></th>
                                            <th><?php echo translate('admin_news_table_posted_by', 'Posted By'); ?></th>
                                            <th><?php echo translate('admin_news_table_date', 'Date'); ?></th>
                                            <th><?php echo translate('admin_news_table_important', 'Important'); ?></th>
                                            <th><?php echo translate('admin_news_table_image', 'Image'); ?></th>
                                            <th><?php echo translate('admin_news_table_actions', 'Actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($news_result->num_rows === 0): ?>
                                            <tr>
                                                <td colspan="7"><?php echo translate('admin_news_no_news', 'No news available.'); ?></td>
                                            </tr>
                                        <?php else: ?>
                                            <?php while ($news = $news_result->fetch_assoc()): ?>
                                                <tr id="news-<?php echo $news['id']; ?>">
                                                    <td><a href="<?php echo $base_path; ?>news?slug=<?php echo urlencode(htmlspecialchars($news['slug'])); ?>" class="news-title-link"><?php echo htmlspecialchars($news['title']); ?></a></td>
                                                    <td><?php echo translate('admin_news_category_' . $news['category'], ucfirst($news['category'])); ?></td>
                                                    <td><?php echo htmlspecialchars($news['posted_by']); ?></td>
                                                    <td><?php echo date('M j, Y H:i', strtotime($news['post_date'])); ?></td>
                                                    <td><span class="<?php echo $news['is_important'] ? 'status-important' : ''; ?>">
                                                        <?php echo $news['is_important'] ? translate('admin_news_yes', 'Yes') : translate('admin_news_no', 'No'); ?>
                                                    </span></td>
                                                    <td>
                                                        <img src="<?php echo htmlspecialchars($news['image_url'] ?? $default_image_url); ?>" alt="<?php echo translate('admin_news_image_alt', 'News Image'); ?>" style="max-width: 50px; max-height: 50px;">
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-edit" data-bs-toggle="modal" data-bs-target="#editModal-<?php echo $news['id']; ?>"><?php echo translate('admin_news_edit_button', 'Edit'); ?></button>
                                                        <button class="btn btn-delete" data-bs-toggle="modal" data-bs-target="#deleteModal-<?php echo $news['id']; ?>"><?php echo translate('admin_news_delete_button', 'Delete'); ?></button>
                                                    </td>
                                                </tr>
                                                <!-- Edit Modal -->
                                                <div class="modal fade" id="editModal-<?php echo $news['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel-<?php echo $news['id']; ?>" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="editModalLabel-<?php echo $news['id']; ?>"><?php echo translate('admin_news_edit_modal_title', 'Edit News: ') . htmlspecialchars($news['title']); ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo translate('admin_news_close_button', 'Close'); ?>"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form method="POST" enctype="multipart/form-data">
                                                                    <input type="hidden" name="action" value="update">
                                                                    <input type="hidden" name="id" value="<?php echo $news['id']; ?>">
                                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                                    <input type="hidden" name="existing_image" id="existing_image_<?php echo $news['id']; ?>" value="<?php echo htmlspecialchars($news['image_url'] ?? $default_image_url); ?>">
                                                                    <div class="mb-3">
                                                                        <label class="form-label"><?php echo translate('admin_news_label_title', 'Title'); ?></label>
                                                                        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($news['title']); ?>" required maxlength="100" placeholder="<?php echo translate('admin_news_placeholder_title', 'Enter news title'); ?>">
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label"><?php echo translate('admin_news_label_slug', 'Slug'); ?></label>
                                                                        <input type="text" name="slug" class="form-control" value="<?php echo htmlspecialchars($news['slug'] ?? ''); ?>" maxlength="120" placeholder="<?php echo translate('admin_news_placeholder_slug', 'Enter slug (optional)'); ?>">
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label"><?php echo translate('admin_news_label_content', 'Content'); ?></label>
                                                                        <textarea name="content" class="form-control" rows="5" required placeholder="<?php echo translate('admin_news_placeholder_content', 'Enter news content'); ?>"><?php echo htmlspecialchars($news['content']); ?></textarea>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label"><?php echo translate('admin_news_label_category', 'Category'); ?></label>
                                                                        <select name="category" class="form-select">
                                                                            <option value="update" <?php echo $news['category'] === 'update' ? 'selected' : ''; ?>><?php echo translate('admin_news_category_update', 'Update'); ?></option>
                                                                            <option value="event" <?php echo $news['category'] === 'event' ? 'selected' : ''; ?>><?php echo translate('admin_news_category_event', 'Event'); ?></option>
                                                                            <option value="maintenance" <?php echo $news['category'] === 'maintenance' ? 'selected' : ''; ?>><?php echo translate('admin_news_category_maintenance', 'Maintenance'); ?></option>
                                                                            <option value="other" <?php echo $news['category'] === 'other' ? 'selected' : ''; ?>><?php echo translate('admin_news_category_other', 'Other'); ?></option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label"><?php echo translate('admin_news_label_image', 'Image Upload (JPG, PNG, GIF, max 2MB, Optional)'); ?></label>
                                                                        <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/gif">
                                                                        <small class="form-text text-muted"><?php echo translate('admin_news_image_edit_help', 'Leave blank to keep existing image (default: news.png).'); ?></small>
                                                                        <img class="image-preview active" id="image_preview_<?php echo $news['id']; ?>" src="<?php echo htmlspecialchars($news['image_url'] ?? $default_image_url); ?>" alt="<?php echo translate('admin_news_image_preview_alt', 'Image Preview'); ?>">
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <div class="form-check">
                                                                            <input type="checkbox" name="is_important" class="form-check-input" id="is_important_<?php echo $news['id']; ?>" <?php echo $news['is_important'] ? 'checked' : ''; ?>>
                                                                            <label class="form-check-label" for="is_important_<?php echo $news['id']; ?>"><?php echo translate('admin_news_label_is_important', 'Mark as Important'); ?></label>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo translate('admin_news_cancel_button', 'Cancel'); ?></button>
                                                                        <button type="submit" class="btn btn-primary"><?php echo translate('admin_news_save_button', 'Save'); ?></button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- Delete Modal -->
                                                <div class="modal fade" id="deleteModal-<?php echo $news['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel-<?php echo $news['id']; ?>" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="deleteModalLabel-<?php echo $news['id']; ?>"><?php echo translate('admin_news_delete_modal_title', 'Delete News: ') . htmlspecialchars($news['title']); ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo translate('admin_news_close_button', 'Close'); ?>"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p><?php echo translate('admin_news_delete_confirm', 'Are you sure you want to delete this news article?'); ?></p>
                                                                <form method="POST">
                                                                    <input type="hidden" name="action" value="delete">
                                                                    <input type="hidden" name="id" value="<?php echo $news['id']; ?>">
                                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo translate('admin_news_cancel_button', 'Cancel'); ?></button>
                                                                        <button type="submit" class="btn btn-danger"><?php echo translate('admin_news_delete_button', 'Delete'); ?></button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                        <?php $news_result->free(); ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <nav aria-label="<?php echo translate('admin_news_pagination_aria', 'News pagination'); ?>">
                                    <ul class="pagination">
                                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="<?php echo $base_path; ?>admin/anews?page=<?php echo $page - 1; ?>" aria-label="<?php echo translate('admin_news_previous', 'Previous'); ?>">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                                <a class="page-link" href="<?php echo $base_path; ?>admin/anews?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="<?php echo $base_path; ?>admin/anews?page=<?php echo $page + 1; ?>" aria-label="<?php echo translate('admin_news_next', 'Next'); ?>">
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
        <?php include $project_root . 'includes/footer.php'; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Image preview for add form
            const addImageInput = document.getElementById('image');
            const addImagePreview = document.getElementById('image_preview');

            if (addImageInput && addImagePreview) {
                addImageInput.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        const file = this.files[0];
                        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                        const maxSize = 2 * 1024 * 1024; // 2MB

                        if (!allowedTypes.includes(file.type)) {
                            alert('<?php echo translate('admin_news_js_invalid_file_type', 'Invalid file type. Only JPG, PNG, or GIF allowed.'); ?>');
                            this.value = '';
                            addImagePreview.classList.remove('active');
                            addImagePreview.src = '<?php echo htmlspecialchars($default_image_url); ?>';
                            return;
                        }
                        if (file.size > maxSize) {
                            alert('<?php echo translate('admin_news_js_file_size_exceeded', 'File size exceeds 2MB limit.'); ?>');
                            this.value = '';
                            addImagePreview.classList.remove('active');
                            addImagePreview.src = '<?php echo htmlspecialchars($default_image_url); ?>';
                            return;
                        }

                        const reader = new FileReader();
                        reader.onload = function(e) {
                            addImagePreview.src = e.target.result;
                            addImagePreview.classList.add('active');
                        };
                        reader.readAsDataURL(file);
                    } else {
                        addImagePreview.classList.remove('active');
                        addImagePreview.src = '<?php echo htmlspecialchars($default_image_url); ?>';
                    }
                });
            }

            // Image preview for edit modals
            document.querySelectorAll('.btn-edit').forEach(button => {
                button.addEventListener('click', function() {
                    const modalId = this.getAttribute('data-bs-target');
                    const modal = document.querySelector(modalId);
                    const imageInput = modal.querySelector('input[type="file"]');
                    const imagePreview = modal.querySelector('.image-preview');
                    const existingImageInput = modal.querySelector('input[name="existing_image"]');

                    if (imageInput && imagePreview) {
                        imageInput.addEventListener('change', function() {
                            if (this.files && this.files[0]) {
                                const file = this.files[0];
                                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                                const maxSize = 2 * 1024 * 1024; // 2MB

                                if (!allowedTypes.includes(file.type)) {
                                    alert('<?php echo translate('admin_news_js_invalid_file_type', 'Invalid file type. Only JPG, PNG, or GIF allowed.'); ?>');
                                    this.value = '';
                                    imagePreview.classList.add('active');
                                    imagePreview.src = existingImageInput.value;
                                    return;
                                }
                                if (file.size > maxSize) {
                                    alert('<?php echo translate('admin_news_js_file_size_exceeded', 'File size exceeds 2MB limit.'); ?>');
                                    this.value = '';
                                    imagePreview.classList.add('active');
                                    imagePreview.src = existingImageInput.value;
                                    return;
                                }

                                const reader = new FileReader();
                                reader.onload = function(e) {
                                    imagePreview.src = e.target.result;
                                    imagePreview.classList.add('active');
                                };
                                reader.readAsDataURL(file);
                            } else {
                                imagePreview.src = existingImageInput.value;
                                imagePreview.classList.add('active');
                            }
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
<?php $site_db->close(); ?>