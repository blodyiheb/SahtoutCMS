<?php
define('ALLOWED_ACCESS', true);
// Include paths.php using __DIR__ to access $project_root and $base_path
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
    } else {
        $errors[] = translate('err_log_not_writable', 'Debug log directory is not writable.');
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
            $max_size = 1 * 1024 * 1024; // 1MB
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

// Include header after all redirect logic
$page_class = 'vote-sites';
require_once $project_root . 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($langCode); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo translate('page_title_manage_vote_sites', 'Manage Vote Sites'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/admin/settings/vote_sites.css">
     <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/admin/admin_sidebar.css">
     <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/admin/settings/settings_navbar.css">
    
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include $project_root . 'includes/admin_sidebar.php'; ?>
            <main class="col-md-10 main-content">
                <?php include $project_root . 'pages/admin/settings/settings_navbar.php'; ?>
                <div class="content">
                    <h2><?php echo translate('page_title_manage_vote_sites', 'Manage Vote Sites'); ?></h2>
                    <?php if ($status === 'success' || (isset($_GET['status']) && $_GET['status'] === 'success')): ?>
                        <div class="success-box mb-3 col-md-6 mx-auto">
                            <span class="db-status-icon db-status-success">✔</span>
                            <span class="success"><?php echo htmlspecialchars($message ?: urldecode($_GET['message'])); ?></span>
                        </div>
                    <?php elseif (!empty($errors) || (isset($_GET['status']) && $_GET['status'] === 'error')): ?>
                        <div class="error-box mb-3 col-md-6 mx-auto">
                            <strong><?php echo translate('err_fix_errors', 'Please fix the following errors:'); ?></strong>
                            <?php if (isset($_GET['status']) && $_GET['status'] === 'error'): ?>
                                <div class="db-status">
                                    <span class="db-status-icon db-status-error">❌</span>
                                    <span class="error"><?php echo htmlspecialchars(urldecode($_GET['message'])); ?></span>
                                </div>
                            <?php else: ?>
                                <?php foreach ($errors as $error): ?>
                                    <div class="db-status">
                                        <span class="db-status-icon db-status-error">❌</span>
                                        <span class="error"><?php echo htmlspecialchars($error); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="row justify-content-center mb-4">
                        <form action="<?php echo $base_path; ?>admin/settings/vote_sites" method="POST" enctype="multipart/form-data" class="col-md-6">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="site_id" value="<?php echo $site_id; ?>">
                            <input type="hidden" name="MAX_FILE_SIZE" value="1048576">
                            <div class="mb-3">
                                <label for="callback_file_name" class="form-label"><?php echo translate('label_callback_file_name', 'Callback File Name'); ?></label>
                                <input type="text" name="callback_file_name" id="callback_file_name" class="form-control" placeholder="<?php echo translate('placeholder_callback_file_name', 'Enter callback file name (e.g., arenaTop100)'); ?>" value="<?php echo htmlspecialchars($site_data['callback_file_name']); ?>" required maxlength="50">
                                <small class="form-text text-muted"><?php echo translate('label_callback_file_name_info', 'Name for identifying the voting site in callbacks.gtop100,top100arena,etc'); ?></small>
                            </div>
                            <div class="mb-3">
                                <label for="site_name" class="form-label"><?php echo translate('label_site_name', 'Site Name'); ?></label>
                                <input type="text" name="site_name" id="site_name" class="form-control" placeholder="<?php echo translate('placeholder_site_name', 'Enter site name'); ?>" value="<?php echo htmlspecialchars($site_data['site_name']); ?>" required maxlength="50">
                            </div>
                            <div class="mb-3">
                                <label for="siteid" class="form-label"><?php echo translate('label_siteid', 'Site ID'); ?></label>
                                <input type="text" name="siteid" id="siteid" class="form-control" placeholder="<?php echo translate('placeholder_siteid', 'Enter server ID on the voting site'); ?>" value="<?php echo htmlspecialchars($site_data['siteid']); ?>" required maxlength="255">
                                <small class="form-text text-muted"><?php echo translate('label_siteid_info', 'Your server’s unique ID on the voting site (e.g., SahtoutServer, 12345).'); ?></small>
                            </div>
                            <div class="mb-3">
                                <label for="url_format" class="form-label"><?php echo translate('label_url_format', 'Vote URL Format'); ?></label>
                                <input type="text" name="url_format" id="url_format" class="form-control" placeholder="<?php echo translate('placeholder_url_format', 'e.g., https://site.com/vote/{siteid}/{userid}'); ?>" value="<?php echo htmlspecialchars($site_data['url_format']); ?>" required maxlength="255">
                                <small class="form-text text-muted"><?php echo translate('label_url_format_info', 'Use {siteid}, {userid}, or {username} as placeholders.'); ?></small>
                            </div>
                            <div class="mb-3">
                                <label for="button_image" class="form-label"><?php echo translate('label_button_image', 'Upload Button Image'); ?></label>
                                <?php if ($site_data['button_image_url']): ?>
                                    <div class="mb-2">
                                        <img src="<?php echo htmlspecialchars($site_data['button_image_url']); ?>" alt="<?php echo translate('label_button_image', 'Button Image'); ?>" class="img-fluid" style="max-width: 150px;">
                                    </div>
                                    <a href="<?php echo $base_path; ?>admin/settings/vote_sites?delete_image=<?php echo $site_id; ?>&csrf_token=<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
                                       class="btn btn-danger mb-2"
                                       onclick="return confirm('<?php echo translate('confirm_delete_image', 'Are you sure you want to delete this image?'); ?>');">
                                       <i class="fas fa-trash"></i> <?php echo translate('btn_delete_image', 'Delete Image'); ?>
                                    </a>
                                <?php endif; ?>
                                <div class="custom-file-upload">
                                    <input type="file" id="button_image" name="button_image" accept="image/jpeg,image/png,image/gif">
                                    <button type="button" class="btn" onclick="document.getElementById('button_image').click();"><?php echo translate('btn_choose_file', 'Choose File'); ?></button>
                                    <div class="file-name" id="file-name-button"><?php echo translate('placeholder_button_image', 'Upload a JPEG, PNG, or GIF image (max 1MB).'); ?></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="button_image_url" class="form-label"><?php echo translate('label_button_image_url', 'Button Image URL (Optional)'); ?></label>
                                <input type="text" name="button_image_url" id="button_image_url" class="form-control" placeholder="<?php echo translate('placeholder_button_image_url', 'Enter button image URL (optional)'); ?>" value="<?php echo htmlspecialchars($site_data['button_image_url']); ?>" maxlength="255">
                                <small class="form-text text-muted"><?php echo translate('label_image_url_info', 'Enter an image URL if you prefer not to upload an image. Leave empty to clear the image.'); ?></small>
                            </div>
                            <div class="mb-3">
                                <label for="cooldown_hours" class="form-label"><?php echo translate('label_cooldown_hours', 'Cooldown Hours'); ?></label>
                                <input type="number" name="cooldown_hours" id="cooldown_hours" class="form-control compact-input" placeholder="<?php echo translate('placeholder_cooldown_hours', 'Enter cooldown hours'); ?>" value="<?php echo htmlspecialchars($site_data['cooldown_hours']); ?>" required min="1" max="999">
                            </div>
                            <div class="mb-3">
                                <label for="reward_points" class="form-label"><?php echo translate('label_reward_points', 'Reward Points'); ?></label>
                                <input type="number" name="reward_points" id="reward_points" class="form-control compact-input" placeholder="<?php echo translate('placeholder_reward_points', 'Enter reward points'); ?>" value="<?php echo htmlspecialchars($site_data['reward_points']); ?>" required min="1" max="255">
                            </div>
                            <div class="mb-3">
                                <label for="uses_callback" class="form-label"><?php echo translate('label_uses_callback', 'Uses Callback'); ?></label>
                                <select name="uses_callback" id="uses_callback" class="form-control compact-input">
                                    <option value="0" <?php echo $site_data['uses_callback'] == 0 ? 'selected' : ''; ?>><?php echo translate('option_no', 'No'); ?></option>
                                    <option value="1" <?php echo $site_data['uses_callback'] == 1 ? 'selected' : ''; ?>><?php echo translate('option_yes', 'Yes'); ?></option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="callback_secret" class="form-label"><?php echo translate('label_callback_secret', 'Callback Secret'); ?></label>
                                <input type="text" name="callback_secret" id="callback_secret" class="form-control compact-input" placeholder="<?php echo translate('placeholder_callback_secret', 'Enter callback secret (optional)'); ?>" value="<?php echo htmlspecialchars($site_data['callback_secret'] ?? ''); ?>" maxlength="64">
                            </div>
                            <button type="submit" class="btn btn-primary"><?php echo translate('btn_save_vote_site', 'Save Vote Site'); ?></button>
                            <?php if ($site_id > 0): ?>
                                <a href="<?php echo $base_path; ?>admin/settings/vote_sites" class="btn btn-reset"><?php echo translate('btn_reset', 'Reset Form'); ?></a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th><?php echo translate('label_callback_file_name', 'Callback File Name'); ?></th>
                                    <th><?php echo translate('label_site_name', 'Site Name'); ?></th>
                                    <th><?php echo translate('label_siteid', 'Site ID'); ?></th>
                                    <th><?php echo translate('label_url_format', 'Vote URL Format'); ?></th>
                                    <th><?php echo translate('label_button_image', 'Button Image'); ?></th>
                                    <th><?php echo translate('label_cooldown_hours', 'Cooldown Hours'); ?></th>
                                    <th><?php echo translate('label_reward_points', 'Reward Points'); ?></th>
                                    <th><?php echo translate('label_uses_callback', 'Uses Callback'); ?></th>
                                    <th><?php echo translate('label_actions', 'Actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($voteSites)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center"><?php echo translate('msg_no_vote_sites', 'No vote sites available.'); ?></td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($voteSites as $site): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($site['callback_file_name']); ?></td>
                                            <td><?php echo htmlspecialchars($site['site_name']); ?></td>
                                            <td><?php echo htmlspecialchars($site['siteid']); ?></td>
                                            <td><?php echo htmlspecialchars($site['url_format']); ?></td>
                                            <td>
                                                <?php if ($site['button_image_url']): ?>
                                                    <img src="<?php echo htmlspecialchars($site['button_image_url']); ?>" alt="<?php echo htmlspecialchars($site['site_name']); ?>">
                                                <?php else: ?>
                                                    <?php echo translate('label_no_image', 'No Image'); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($site['cooldown_hours']); ?></td>
                                            <td><?php echo htmlspecialchars($site['reward_points']); ?></td>
                                            <td><?php echo $site['uses_callback'] ? translate('option_yes', 'Yes') : translate('option_no', 'No'); ?></td>
                                            <td>
                                                <a href="<?php echo $base_path; ?>admin/settings/vote_sites?id=<?php echo $site['id']; ?>"
                                                   class="btn btn-primary px-3 py-2">
                                                   <i class="fas fa-edit"></i> <?php echo translate('btn_edit', 'Edit'); ?>
                                                </a>
                                                <a href="<?php echo $base_path; ?>admin/settings/vote_sites?delete=<?php echo $site['id']; ?>&csrf_token=<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('<?php echo translate('confirm_delete', 'Are you sure you want to delete this vote site?'); ?>');">
                                                   <i class="fas fa-trash"></i> <?php echo translate('btn_delete', 'Delete'); ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <?php require_once $project_root . 'includes/footer.php'; ?>
    <script>
        document.getElementById('button_image').addEventListener('change', function() {
            const fileName = this.files.length > 0 ? this.files[0].name : '<?php echo translate('placeholder_button_image', 'Upload a JPEG, PNG, or GIF image (max 1MB).'); ?>';
            document.getElementById('file-name-button').textContent = fileName;
        });
    </script>
</body>
</html>