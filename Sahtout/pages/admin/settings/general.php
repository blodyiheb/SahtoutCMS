<?php
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../../../includes/paths.php';
require_once $project_root . 'includes/session.php';
require_once $project_root . 'languages/language.php';
require_once $project_root . 'includes/config.settings.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'moderator'])) {
    header("Location: {$base_path}login");
    exit;
}

$page_class = 'general';
require_once $project_root . 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($langCode); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo translate('page_title_general', 'General Settings'); ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/admin/settings/general.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/admin/admin_sidebar.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/admin/settings/settings_navbar.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include $project_root . 'includes/admin_sidebar.php'; ?>

            <main class="col-md-10 main-content">
                <?php include dirname(__DIR__) . '/settings/settings_navbar.php'; ?>

                <div class="content">
                    <h2><?php echo translate('settings_general', 'General Settings'); ?></h2>

                    <!-- Success / Error Messages -->
                    <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
                        <div class="success-box mb-3 col-md-6 mx-auto">
                            <span class="db-status-icon db-status-success">Success</span>
                            <span class="success"><?php echo translate('msg_settings_saved', 'Settings updated successfully!'); ?></span>
                        </div>
                    <?php elseif (isset($_GET['status']) && $_GET['status'] === 'error'): ?>
                        <div class="error-box mb-3 col-md-6 mx-auto">
                            <strong><?php echo translate('err_fix_errors', 'Please fix the following errors:'); ?></strong>
                            <div class="db-status">
                                <span class="db-status-icon db-status-error">Error</span>
                                <span class="error"><?php echo htmlspecialchars(urldecode($_GET['message'])); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- General Settings Form -->
                    <div class="row justify-content-center">
                        <form action="<?php echo $base_path; ?>pages/admin/settings/save_general.php" method="POST" enctype="multipart/form-data" class="col-md-7">

                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="MAX_FILE_SIZE" value="3145728">

                            <!-- Website Title -->
                            <div class="mb-4">
                                <label for="site_title_name" class="form-label fw-bold">
                                    <?php echo translate('label_website_title', 'Website Title'); ?>
                                </label>
                                <input type="text"
                                       id="site_title_name"
                                       name="site_title_name"
                                       class="form-control form-control-lg"
                                       value="<?php echo htmlspecialchars($site_title_name); ?>"
                                       placeholder="<?php echo translate('placeholder_site_title', 'e.g. My Awesome Site'); ?>"
                                       required>
                                <div class="form-text">
                                    <?php echo translate('help_site_title', 'This title appears in the browser tab, site header, and SEO.'); ?>
                                </div>
                            </div>

                            <!-- Logo Upload -->
                            <div class="mb-4">
                                <label for="logo" class="form-label fw-bold"><?php echo translate('label_website_logo', 'Website Logo'); ?></label>
                                <div class="mb-3">
                                    <img src="<?php echo $base_path . htmlspecialchars($site_logo); ?>" alt="Current Logo" class="img-fluid rounded" style="max-height: 120px;">
                                </div>
                                <div class="custom-file-upload">
                                    <input type="file" id="logo" name="logo" accept=".png,.jpg,.jpeg,.svg">
                                    <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('logo').click();">
                                        <?php echo translate('btn_choose_file', 'Choose File'); ?>
                                    </button>
                                    <div class="file-name mt-2 text-muted" id="file-name">
                                        <?php echo translate('placeholder_logo', 'No file chosen – PNG, JPG or SVG (max 3MB)'); ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Social Links -->
                            <div class="mb-4">
                                <label class="form-label fw-bold"><?php echo translate('label_social_media', 'Social Media Links'); ?></label>

                                <?php
                                $icons = [
                                    'facebook'  => 'fab fa-facebook-f',
                                    'twitter'   => 'fab fa-x-twitter',
                                    'tiktok'    => 'fab fa-tiktok',
                                    'youtube'   => 'fab fa-youtube',
                                    'discord'   => 'fab fa-discord',
                                    'twitch'    => 'fab fa-twitch',
                                    'kick'      => 'custom', // we'll use image
                                    'instagram' => 'fab fa-instagram',
                                    'github'    => 'fab fa-github',
                                    'linkedin' => 'fab fa-linkedin-in',
                                ];

                                foreach ($icons as $platform => $icon): ?>
                                    <div class="input-group mb-2">
                                        <span class="input-group-text social-icon">
                                            <?php if ($platform === 'kick'): ?>
                                                <img src="<?php echo $base_path; ?>img/icons/kick-logo.png" alt="Kick" style="width:16px;">
                                            <?php else: ?>
                                                <i class="<?php echo $icon; ?>"></i>
                                            <?php endif; ?>
                                        </span>
                                        <input type="url"
                                               name="<?php echo $platform; ?>"
                                               class="form-control"
                                               placeholder="<?php echo translate("placeholder_{$platform}", ucfirst($platform) . ' URL'); ?>"
                                               value="<?php echo htmlspecialchars($social_links[$platform] ?? ''); ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Save Button -->
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-lg px-5">
                                    <?php echo translate('btn_save_settings', 'Save All Settings'); ?>
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php require_once $project_root . 'includes/footer.php'; ?>

    <script>
        document.getElementById('logo').addEventListener('change', function() {
            const fileName = this.files.length > 0 
                ? this.files[0].name 
                : '<?php echo translate('placeholder_logo', 'No file chosen – PNG, JPG or SVG (max 3MB)'); ?>';
            document.getElementById('file-name').textContent = fileName;
        });
    </script>
</body>
</html>