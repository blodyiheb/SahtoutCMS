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

$page_class = 'anews';
global $site_db;

$current_username = $_SESSION['username'] ?? translate('admin_news_unknown_user', 'Unknown');

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$base_upload_dir = $project_root . 'img/newsimg/';
$base_upload_url = 'img/newsimg/';
$default_image_url = 'img/newsimg/news.png';

if (!file_exists($base_upload_dir)) mkdir($base_upload_dir, 0755, true);

function adminNewsImageSrc($image_url) {
    global $base_path, $default_image_url;

    $image_url = trim((string)$image_url);
    if ($image_url === '') {
        $image_url = $default_image_url;
    }

    if (preg_match('#^(https?:)?//#i', $image_url) || str_starts_with($image_url, 'data:') || str_starts_with($image_url, '/')) {
        return $image_url;
    }

    return $base_path . ltrim($image_url, '/');
}

$log_dir = $project_root . 'logs/';
$log_file = $log_dir . 'upload_errors.log';
if (!file_exists($log_dir)) mkdir($log_dir, 0755, true);

// Category colors
$category_colors = [
    'update' => ['bg' => 'rgba(46, 204, 113, 0.15)', 'border' => 'rgba(46, 204, 113, 0.4)', 'text' => '#2ecc71', 'hover' => 'rgba(46, 204, 113, 0.25)', 'badge' => '#2ecc71'],
    'event' => ['bg' => 'rgba(52, 152, 219, 0.15)', 'border' => 'rgba(52, 152, 219, 0.4)', 'text' => '#3498db', 'hover' => 'rgba(52, 152, 219, 0.25)', 'badge' => '#3498db'],
    'maintenance' => ['bg' => 'rgba(231, 76, 60, 0.15)', 'border' => 'rgba(231, 76, 60, 0.4)', 'text' => '#e74c3c', 'hover' => 'rgba(231, 76, 60, 0.25)', 'badge' => '#e74c3c'],
    'other' => ['bg' => 'rgba(155, 89, 182, 0.15)', 'border' => 'rgba(155, 89, 182, 0.4)', 'text' => '#9b59b6', 'hover' => 'rgba(155, 89, 182, 0.25)', 'badge' => '#9b59b6'],
];

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

$update_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $update_message = $alert_danger(translate('admin_news_csrf_error', 'CSRF token validation failed.'));
        } else {
            $title = trim($_POST['title']);
            $slug = trim($_POST['slug']);
            $content = trim($_POST['content']);
            $category = in_array($_POST['category'], ['update', 'event', 'maintenance', 'other']) ? $_POST['category'] : 'update';
            $is_important = isset($_POST['is_important']) ? 1 : 0;
            $image_url = $default_image_url;

            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 2 * 1024 * 1024;
                $file = $_FILES['image'];

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $update_message = $alert_danger(translate('admin_news_upload_err_unknown', 'Upload error occurred.'));
                } elseif (!in_array($file['type'], $allowed_types)) {
                    $update_message = $alert_danger(translate('admin_news_invalid_file_type', 'Invalid file type. Only JPG, PNG, GIF allowed.'));
                } elseif ($file['size'] > $max_size) {
                    $update_message = $alert_danger(translate('admin_news_file_size_exceeded', 'File size exceeds 2MB limit.'));
                } else {
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $filename = uniqid('news_') . '.' . $ext;
                    $destination = $base_upload_dir . $filename;
                    if (move_uploaded_file($file['tmp_name'], $destination)) {
                        $image_url = $base_upload_url . $filename;
                    } else {
                        $update_message = $alert_danger(translate('admin_news_upload_failed', 'Failed to upload file.'));
                    }
                }
            }

            if (empty($update_message)) {
                $stmt = $site_db->prepare("INSERT INTO server_news (title, slug, content, posted_by, category, is_important, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssis", $title, $slug, $content, $current_username, $category, $is_important, $image_url);
                if ($stmt->execute()) {
                    $update_message = $alert_success(translate('admin_news_add_success', 'News added successfully.'));
                } else {
                    $update_message = $alert_danger(sprintf(translate('admin_news_add_failed', 'Failed to add news.'), htmlspecialchars($site_db->error)));
                }
                $stmt->close();
            }
        }
    } elseif ($_POST['action'] === 'update') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $update_message = $alert_danger(translate('admin_news_csrf_error', 'CSRF token validation failed.'));
        } else {
            $id = (int)$_POST['id'];
            $title = trim($_POST['title']);
            $slug = trim($_POST['slug']);
            $content = trim($_POST['content']);
            $category = in_array($_POST['category'], ['update', 'event', 'maintenance', 'other']) ? $_POST['category'] : 'update';
            $is_important = isset($_POST['is_important']) ? 1 : 0;
            $image_url = isset($_POST['existing_image']) && !empty($_POST['existing_image']) ? trim($_POST['existing_image']) : $default_image_url;

            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 2 * 1024 * 1024;
                $file = $_FILES['image'];

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $update_message = $alert_danger(translate('admin_news_upload_err_unknown', 'Upload error occurred.'));
                } elseif (!in_array($file['type'], $allowed_types)) {
                    $update_message = $alert_danger(translate('admin_news_invalid_file_type', 'Invalid file type. Only JPG, PNG, GIF allowed.'));
                } elseif ($file['size'] > $max_size) {
                    $update_message = $alert_danger(translate('admin_news_file_size_exceeded', 'File size exceeds 2MB limit.'));
                } else {
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $filename = uniqid('news_') . '.' . $ext;
                    $destination = $base_upload_dir . $filename;
                    if (move_uploaded_file($file['tmp_name'], $destination)) {
                        $image_url = $base_upload_url . $filename;
                        if (!empty($_POST['existing_image']) && $_POST['existing_image'] !== $default_image_url) {
                            $old_image_path = str_replace($base_upload_url, $base_upload_dir, $_POST['existing_image']);
                            if (file_exists($old_image_path)) unlink($old_image_path);
                        }
                    } else {
                        $update_message = $alert_danger(translate('admin_news_upload_failed', 'Failed to upload file.'));
                    }
                }
            }

            if (empty($update_message)) {
                $stmt = $site_db->prepare("UPDATE server_news SET title = ?, slug = ?, content = ?, category = ?, is_important = ?, image_url = ? WHERE id = ?");
                $stmt->bind_param("ssssisi", $title, $slug, $content, $category, $is_important, $image_url, $id);
                if ($stmt->execute()) {
                    $update_message = $alert_success(translate('admin_news_update_success', 'News updated successfully.'));
                } else {
                    $update_message = $alert_danger(sprintf(translate('admin_news_update_failed', 'Failed to update news.'), htmlspecialchars($site_db->error)));
                }
                $stmt->close();
            }
        }
    } elseif ($_POST['action'] === 'delete') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $update_message = $alert_danger(translate('admin_news_csrf_error', 'CSRF token validation failed.'));
        } else {
            $id = (int)$_POST['id'];
            $stmt = $site_db->prepare("SELECT image_url FROM server_news WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                if (!empty($row['image_url']) && $row['image_url'] !== $default_image_url) {
                    $image_path = str_replace($base_upload_url, $base_upload_dir, $row['image_url']);
                    if (file_exists($image_path)) unlink($image_path);
                }
            }
            $stmt->close();

            $stmt = $site_db->prepare("DELETE FROM server_news WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $update_message = $alert_success(translate('admin_news_delete_success', 'News deleted successfully.'));
            } else {
                $update_message = $alert_danger(sprintf(translate('admin_news_delete_failed', 'Failed to delete news.'), htmlspecialchars($site_db->error)));
            }
            $stmt->close();
        }
    }
}

$items_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

$count_result = $site_db->query("SELECT COUNT(*) as total FROM server_news");
$total_items = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page);

$stmt = $site_db->prepare("SELECT id, title, slug, content, posted_by, category, post_date, image_url, is_important FROM server_news ORDER BY is_important DESC, post_date DESC LIMIT ? OFFSET ?");
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
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/tailwind.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&family=Cinzel:wght@600;700;900&display=swap" rel="stylesheet">
    
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

        .wow-input, .wow-select, .wow-textarea {
            width: 100%;
            background: rgba(10, 14, 22, 0.8);
            border: 1px solid rgba(201,162,39,.3);
            color: #e5e7eb;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            outline: none;
        }

        .wow-input:focus, .wow-select:focus, .wow-textarea:focus {
            border-color: #f2cf5b;
            box-shadow: 0 0 10px rgba(242,207,82,.2);
            background: rgba(15, 20, 30, 0.9);
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
            border-radius: 3px;
            border: 1px solid transparent;
            transition: all 0.2s ease;
        }

        .category-badge:hover {
            transform: scale(1.05);
        }

        .news-thumb {
            width: 82px;
            height: 52px;
            object-fit: cover;
            border: 1px solid rgba(201,162,39,.3);
            background: rgba(10, 14, 22, 0.8);
        }

        .toggle-switch {
            position: relative;
            width: 48px;
            height: 28px;
            flex-shrink: 0;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(30, 35, 45, 0.8);
            border: 1px solid rgba(201,162,39,.2);
            transition: 0.3s;
            border-radius: 34px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background: #6a7a8a;
            transition: 0.3s;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .toggle-switch input:checked + .toggle-slider {
            background: linear-gradient(180deg, #ff4d4d 0%, #cc0000 48%, #8a0000 100%);
            border-color: rgba(255,50,80,.3);
        }

        .toggle-switch input:checked + .toggle-slider:before {
            transform: translateX(20px);
            background: white;
        }

        .upload-area {
            border: 2px dashed rgba(201,162,39,.2);
            background: rgba(10, 14, 22, 0.5);
            transition: all 0.3s ease;
            cursor: pointer;
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
<body class="news">
    <?php include $project_root . 'includes/header.php'; ?>

    <!-- Main Content Area with Sidebar -->
    <div class="flex relative min-h-screen">
        
        <!-- Sidebar -->
        <?php include $project_root . 'includes/admin_sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content-area flex-1 p-3 sm:p-4 md:p-6 lg:p-8 transition-all duration-300 lg:ml-[280px]">
            <div class="content-wrapper">
                <div class="space-y-4 md:space-y-6 lg:space-y-8">
                    
                    <h1 class="wow-title text-2xl md:text-3xl lg:text-4xl"><?php echo translate('admin_news_title', 'News Management'); ?></h1>
                    
                    <?php echo $update_message; ?>

                    <!-- Add News Form -->
                    <div class="wow-panel p-4 md:p-6 lg:p-8">
                        <h2 class="section-title text-lg md:text-xl mb-4 md:mb-6"><?php echo translate('admin_news_add_header', 'Add New News'); ?></h2>
                        <form method="POST" enctype="multipart/form-data" class="space-y-4 md:space-y-6">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            
                            <div>
                                <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_news_label_title', 'Title'); ?></label>
                                <input type="text" name="title" class="wow-input" required maxlength="100" placeholder="<?php echo translate('admin_news_placeholder_title', 'Enter news title'); ?>">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_news_label_slug', 'Slug'); ?></label>
                                <input type="text" name="slug" class="wow-input" maxlength="120" placeholder="<?php echo translate('admin_news_placeholder_slug', 'Enter slug (optional)'); ?>">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_news_label_content', 'Content'); ?></label>
                                <textarea name="content" class="wow-textarea" rows="5" required placeholder="<?php echo translate('admin_news_placeholder_content', 'Enter news content'); ?>"></textarea>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_news_label_category', 'Category'); ?></label>
                                    <select name="category" class="wow-select">
                                        <?php foreach ($category_colors as $key => $color): ?>
                                            <option value="<?php echo $key; ?>" style="color: <?php echo $color['text']; ?>; background: rgba(10, 14, 22, 0.8);">
                                                <?php echo ucfirst(translate('admin_news_category_' . $key, ucfirst($key))); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_news_label_image', 'Image Upload'); ?></label>
                                    <div class="upload-area rounded-sm p-4 text-center" id="uploadArea">
                                        <input type="file" name="image" id="image" class="hidden" accept="image/jpeg,image/png,image/gif">
                                        <div id="uploadPlaceholder">
                                            <i class="fas fa-cloud-upload-alt text-3xl text-[#c9a227]/40 mb-2"></i>
                                            <p class="text-sm text-gray-400"><?php echo translate('admin_news_image_help', 'Click or drag to upload image'); ?></p>
                                            <p class="text-xs text-gray-500 mt-1"><?php echo translate('admin_news_max_filesize', 'Max 2MB • JPG, PNG, GIF'); ?></p>
                                        </div>
                                        <img id="image_preview" class="hidden mx-auto max-h-40 rounded-sm mt-2 border border-[#c9a227]/30" src="" alt="<?php echo translate('admin_news_image_preview_alt', 'Image Preview'); ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Mark as Important - Toggle Switch -->
                            <div class="flex items-center gap-4 pt-2 p-4 rounded-sm bg-[rgba(201,162,39,0.05)] border border-[rgba(201,162,39,0.1)]">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="is_important" id="is_important">
                                    <span class="toggle-slider"></span>
                                </label>
                                <div>
                                    <label for="is_important" class="text-sm font-semibold text-red-400 cursor-pointer flex items-center gap-2">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <?php echo translate('admin_news_label_is_important', 'Mark as Important'); ?>
                                    </label>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo translate('admin_news_important_help', 'Important news will be highlighted and appear at the top'); ?></p>
                                </div>
                            </div>

                            <div class="pt-4 flex justify-end">
                                <button type="submit" class="btn-game btn-gold">
                                    <i class="fas fa-plus"></i>
                                    <?php echo translate('admin_news_add_button', 'Add News'); ?>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- News List -->
                    <div class="wow-panel p-4 md:p-6 lg:p-8">
                        <h2 class="section-title text-lg md:text-xl mb-4 md:mb-6"><?php echo translate('admin_news_list_header', 'News Articles'); ?></h2>
                        <div class="overflow-x-auto -mx-4 md:-mx-6 lg:-mx-8 px-4 md:px-6 lg:px-8">
                            <table class="wow-table">
                                <thead>
                                    <tr>
                                        <th class="hidden sm:table-cell"><?php echo translate('admin_news_table_image', 'Image'); ?></th>
                                        <th><?php echo translate('admin_news_table_title', 'Title'); ?></th>
                                        <th class="hidden md:table-cell"><?php echo translate('admin_news_table_category', 'Category'); ?></th>
                                        <th class="hidden lg:table-cell"><?php echo translate('admin_news_table_posted_by', 'Posted By'); ?></th>
                                        <th class="hidden sm:table-cell"><?php echo translate('admin_news_table_date', 'Date'); ?></th>
                                        <th class="text-center"><?php echo translate('admin_news_table_important', 'Important'); ?></th>
                                        <th class="text-right"><?php echo translate('admin_news_table_actions', 'Actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($news_result->num_rows === 0): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-gray-400 py-6 md:py-8">
                                                <i class="fas fa-newspaper text-3xl md:text-4xl text-gray-600 block mb-3"></i>
                                                <?php echo translate('admin_news_no_news', 'No news available.'); ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php while ($news = $news_result->fetch_assoc()): ?>
                                            <?php 
                                            $category = $news['category'];
                                            $colors = $category_colors[$category] ?? $category_colors['other'];
                                            ?>
                                            <tr>
                                                <td class="hidden sm:table-cell">
                                                    <img src="<?php echo htmlspecialchars(adminNewsImageSrc($news['image_url'] ?? $default_image_url)); ?>" alt="<?php echo htmlspecialchars($news['title']); ?>" class="news-thumb">
                                                </td>
                                                <td class="font-semibold text-white text-sm md:text-base">
                                                    <a href="<?php echo $base_path; ?>news?slug=<?php echo urlencode(htmlspecialchars($news['slug'])); ?>" class="hover:text-[#f2cf5b] transition-colors">
                                                        <?php echo htmlspecialchars($news['title']); ?>
                                                    </a>
                                                </td>
                                                <td class="hidden md:table-cell">
                                                    <span class="category-badge" style="background: <?php echo $colors['bg']; ?>; border-color: <?php echo $colors['border']; ?>; color: <?php echo $colors['text']; ?>;">
                                                        <i class="fas fa-tag mr-1"></i>
                                                        <?php echo translate('admin_news_category_' . $category, ucfirst($category)); ?>
                                                    </span>
                                                </td>
                                                <td class="hidden lg:table-cell text-sm text-gray-400"><?php echo htmlspecialchars($news['posted_by']); ?></td>
                                                <td class="hidden sm:table-cell text-sm text-gray-400"><?php echo date('M j, Y', strtotime($news['post_date'])); ?></td>
                                                <td class="text-center">
                                                    <?php if ($news['is_important']): ?>
                                                        <span class="inline-flex items-center gap-1 text-red-400 text-xs font-bold">
                                                            <i class="fas fa-star"></i>
                                                            <?php echo translate('admin_news_yes', 'Yes'); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-gray-500 text-xs">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-right">
                                                    <div class="flex justify-end gap-2">
                                                        <button type="button" onclick="openModal('editModal-<?php echo $news['id']; ?>')" class="btn-game btn-iron text-xs py-2 px-3">
                                                            <i class="fas fa-edit"></i>
                                                            <?php echo translate('admin_news_edit_button', 'Edit'); ?>
                                                        </button>
                                                        <button type="button" onclick="openModal('deleteModal-<?php echo $news['id']; ?>')" class="btn-game btn-danger text-xs py-2 px-3">
                                                            <i class="fas fa-trash"></i>
                                                            <?php echo translate('admin_news_delete_button', 'Delete'); ?>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>

                                            <!-- Edit Modal -->
                                            <div id="editModal-<?php echo $news['id']; ?>" class="fixed inset-0 z-50 hidden items-center justify-center p-4 modal-backdrop">
                                                <div class="wow-panel w-full max-w-2xl p-6 md:p-8 relative my-8">
                                                    <button class="absolute top-4 right-4 text-gray-400 hover:text-white text-2xl" onclick="closeModal('editModal-<?php echo $news['id']; ?>')">&times;</button>
                                                    <h3 class="wow-title text-2xl mb-6"><?php echo translate('admin_news_edit_modal_title', 'Edit News: ') . htmlspecialchars($news['title']); ?></h3>
                                                    
                                                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                                                        <input type="hidden" name="action" value="update">
                                                        <input type="hidden" name="id" value="<?php echo $news['id']; ?>">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                        <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($news['image_url'] ?? $default_image_url); ?>">
                                                        
                                                        <div>
                                                            <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_news_label_title', 'Title'); ?></label>
                                                            <input type="text" name="title" class="wow-input" value="<?php echo htmlspecialchars($news['title']); ?>" required>
                                                        </div>
                                                        <div>
                                                            <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_news_label_slug', 'Slug'); ?></label>
                                                            <input type="text" name="slug" class="wow-input" value="<?php echo htmlspecialchars($news['slug'] ?? ''); ?>">
                                                        </div>
                                                        <div>
                                                            <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_news_label_content', 'Content'); ?></label>
                                                            <textarea name="content" class="wow-textarea" rows="4" required><?php echo htmlspecialchars($news['content']); ?></textarea>
                                                        </div>
                                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                            <div>
                                                                <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_news_label_category', 'Category'); ?></label>
                                                                <select name="category" class="wow-select">
                                                                    <?php foreach ($category_colors as $key => $color): ?>
                                                                        <option value="<?php echo $key; ?>" <?php echo $news['category'] === $key ? 'selected' : ''; ?> style="color: <?php echo $color['text']; ?>; background: rgba(10, 14, 22, 0.8);">
                                                                            <?php echo ucfirst(translate('admin_news_category_' . $key, ucfirst($key))); ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div>
                                                                <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_news_label_image', 'Image Upload'); ?></label>
                                                                <div class="upload-area rounded-sm p-3 text-center">
                                                                    <input type="file" name="image" class="hidden edit-image-input" accept="image/jpeg,image/png,image/gif">
                                                                    <img class="image-preview mx-auto max-h-24 rounded-sm cursor-pointer border border-[#c9a227]/30" src="<?php echo htmlspecialchars(adminNewsImageSrc($news['image_url'] ?? $default_image_url)); ?>" alt="<?php echo translate('admin_news_image_preview_alt', 'Image Preview'); ?>">
                                                                    <p class="text-xs text-gray-500 mt-1"><?php echo translate('admin_news_image_edit_help', 'Click image to change'); ?></p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center gap-4 p-3 rounded-sm bg-[rgba(201,162,39,0.05)] border border-[rgba(201,162,39,0.1)]">
                                                            <label class="toggle-switch">
                                                                <input type="checkbox" name="is_important" <?php echo $news['is_important'] ? 'checked' : ''; ?>>
                                                                <span class="toggle-slider"></span>
                                                            </label>
                                                            <span class="text-sm font-semibold text-red-400 flex items-center gap-2">
                                                                <i class="fas fa-exclamation-triangle"></i>
                                                                <?php echo translate('admin_news_label_is_important', 'Mark as Important'); ?>
                                                            </span>
                                                        </div>
                                                        <div class="flex justify-end gap-4 pt-4">
                                                            <button type="button" class="btn-game btn-iron" onclick="closeModal('editModal-<?php echo $news['id']; ?>')"><?php echo translate('admin_news_cancel_button', 'Cancel'); ?></button>
                                                            <button type="submit" class="btn-game btn-gold"><?php echo translate('admin_news_save_button', 'Save Changes'); ?></button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>

                                            <!-- Delete Modal -->
                                            <div id="deleteModal-<?php echo $news['id']; ?>" class="fixed inset-0 z-50 hidden items-center justify-center p-4 modal-backdrop">
                                                <div class="wow-panel w-full max-w-md p-6 relative">
                                                    <h3 class="wow-title text-xl mb-4"><?php echo translate('admin_news_delete_modal_title', 'Delete News'); ?></h3>
                                                    <p class="text-gray-300 mb-6"><?php echo translate('admin_news_delete_confirm', 'Are you sure you want to delete this news article?'); ?></p>
                                                    <form method="POST" class="flex justify-end gap-4">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo $news['id']; ?>">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                        <button type="button" class="btn-game btn-iron" onclick="closeModal('deleteModal-<?php echo $news['id']; ?>')"><?php echo translate('admin_news_cancel_button', 'Cancel'); ?></button>
                                                        <button type="submit" class="btn-game btn-danger"><?php echo translate('admin_news_delete_button', 'Delete'); ?></button>
                                                    </form>
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
                            <nav class="flex justify-center gap-2 mt-6 md:mt-8 flex-wrap" aria-label="<?php echo translate('admin_news_pagination_aria', 'News pagination'); ?>">
                                <?php if ($page > 1): ?>
                                    <a href="<?php echo $base_path; ?>admin/anews?page=<?php echo $page - 1; ?>" class="btn-game btn-iron py-2 px-4 text-xs">
                                        <i class="fas fa-chevron-left"></i> <?php echo translate('admin_news_previous', 'Previous'); ?>
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <?php if ($page === $i): ?>
                                        <span class="btn-game btn-gold py-2 px-4 text-xs cursor-default"><?php echo $i; ?></span>
                                    <?php else: ?>
                                        <a href="<?php echo $base_path; ?>admin/anews?page=<?php echo $i; ?>" class="btn-game btn-iron py-2 px-4 text-xs"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="<?php echo $base_path; ?>admin/anews?page=<?php echo $page + 1; ?>" class="btn-game btn-iron py-2 px-4 text-xs">
                                        <?php echo translate('admin_news_next', 'Next'); ?> <i class="fas fa-chevron-right"></i>
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
            if(modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }
        
        function closeModal(id) {
            const modal = document.getElementById(id);
            if(modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.modal-backdrop[id]').forEach(function(modal) {
                document.body.appendChild(modal);
            });

            const uploadArea = document.getElementById('uploadArea');
            const imageInput = document.getElementById('image');
            const imagePreview = document.getElementById('image_preview');
            const placeholder = document.getElementById('uploadPlaceholder');

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
                        if (file.size > 2 * 1024 * 1024) {
                            alert('<?php echo translate('admin_news_js_file_size_exceeded', 'File size exceeds 2MB limit.'); ?>');
                            this.value = '';
                            return;
                        }
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            imagePreview.src = e.target.result;
                            imagePreview.classList.remove('hidden');
                            placeholder.classList.add('hidden');
                        };
                        reader.readAsDataURL(file);
                    } else {
                        imagePreview.classList.add('hidden');
                        placeholder.classList.remove('hidden');
                    }
                });
            }

            document.querySelectorAll('.edit-image-input').forEach(input => {
                const preview = input.parentElement.querySelector('.image-preview');
                if (preview) {
                    preview.addEventListener('click', () => input.click());
                }
                input.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            if (preview) preview.src = e.target.result;
                        };
                        reader.readAsDataURL(this.files[0]);
                    }
                });
            });
        });
    </script>
    <?php $site_db->close(); ?>
</body>
</html>