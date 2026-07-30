<?php
define('ALLOWED_ACCESS', true);
// Include paths.php using __DIR__ to access $project_root and $base_path
require_once __DIR__ . '/../../../includes/paths.php';
require_once $project_root . 'includes/session.php';
require_once $project_root . 'languages/language.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'moderator'])) {
    header("Location: {$base_path}login");
    exit;
}

$page_class = 'recaptcha';
require_once $project_root . 'includes/header.php';

$errors = [];
$success = false;
$configCapFile = realpath($project_root . 'includes/config.cap.php');
$default_site_key = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI';
$default_secret_key = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe';

// Load current reCAPTCHA settings
$recaptcha_status = 'disabled';
if (file_exists($configCapFile)) {
    include $configCapFile;
    $recaptcha_status = defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED ? 'enabled' : 'disabled';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $captcha_type = trim($_POST['captcha_type'] ?? 'recaptcha');
    $recaptcha_enabled = isset($_POST['recaptcha_enabled']) ? 1 : 0;
    $recaptcha_site_key = $recaptcha_enabled ? trim($_POST['recaptcha_site_key'] ?? '') : '';
    $recaptcha_secret_key = $recaptcha_enabled ? trim($_POST['recaptcha_secret_key'] ?? '') : '';

    // Use default keys if none provided and reCAPTCHA is enabled
    if ($recaptcha_enabled && empty($recaptcha_site_key)) {
        $recaptcha_site_key = $default_site_key;
    }
    if ($recaptcha_enabled && empty($recaptcha_secret_key)) {
        $recaptcha_secret_key = $default_secret_key;
    }

    // Validation
    if ($captcha_type !== 'recaptcha') {
        $errors[] = translate('err_invalid_captcha_type', 'Invalid CAPTCHA type selected. Only reCAPTCHA is supported.');
    }
    if ($recaptcha_enabled && (empty($recaptcha_site_key) || empty($recaptcha_secret_key))) {
        $errors[] = translate('err_recaptcha_keys_required', 'reCAPTCHA Site Key and Secret Key are required when reCAPTCHA is enabled.');
    }

    if (empty($errors)) {
        $capConfigContent = "<?php
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit(translate('error_direct_access', 'Direct access not allowed.'));
}
\$captcha_type = '" . addslashes($captcha_type) . "';
\$recaptcha_enabled = " . ($recaptcha_enabled ? 'true' : 'false') . ";
\$recaptcha_site_key = '" . addslashes($recaptcha_site_key) . "';
\$recaptcha_secret_key = '" . addslashes($recaptcha_secret_key) . "';
define('CAPTCHA_TYPE', \$captcha_type);
define('RECAPTCHA_ENABLED', \$recaptcha_enabled);
define('RECAPTCHA_SITE_KEY', \$recaptcha_site_key);
define('RECAPTCHA_SECRET_KEY', \$recaptcha_secret_key);
?>";

        $capConfigDir = dirname($configCapFile);
        if (!is_writable($capConfigDir)) {
            $errors[] = sprintf(translate('err_cap_dir_not_writable', 'reCAPTCHA config directory is not writable: %s'), $capConfigDir);
        } elseif (file_put_contents($configCapFile, $capConfigContent) === false) {
            $errors[] = sprintf(translate('err_failed_write_cap', 'Failed to write reCAPTCHA config file: %s'), $configCapFile);
        } else {
            $success = true;
            $recaptcha_status = $recaptcha_enabled ? 'enabled' : 'disabled';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($langCode); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo translate('page_title_recaptcha', 'reCAPTCHA Settings'); ?></title>
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <!-- Roboto font -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/admin/settings/recaptcha.css">
     <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/admin/admin_sidebar.css">
     <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/admin/settings/settings_navbar.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Admin Sidebar -->
            <?php include $project_root . 'includes/admin_sidebar.php'; ?>
            
            <!-- Main Content with Settings Navbar -->
            <main class="col-md-10 main-content">
                <?php include $project_root . 'pages/admin/settings/settings_navbar.php'; ?>
                <div class="content">
                    <h2><?php echo translate('settings_recaptcha', 'reCAPTCHA Settings'); ?></h2>
                    
                    <!-- Status Message -->
                    <div class="status-box mb-3 col-md-6 mx-auto">
                        <span class="db-status-icon <?php echo $recaptcha_status === 'enabled' ? 'db-status-success' : 'db-status-muted'; ?>">
                            <?php echo $recaptcha_status === 'enabled' ? '✔' : '✖'; ?>
                        </span>
                        <span class="<?php echo $recaptcha_status === 'enabled' ? 'text-success' : 'text-muted'; ?>">
                            <?php echo translate(
                                $recaptcha_status === 'enabled' ? 'msg_recaptcha_enabled' : 'msg_recaptcha_disabled',
                                $recaptcha_status === 'enabled' ? 'reCAPTCHA is currently enabled.' : 'reCAPTCHA is currently disabled.'
                            ); ?>
                        </span>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="error-box mb-3 col-md-6 mx-auto">
                            <strong><?php echo translate('err_fix_errors', 'Please fix the following errors:'); ?></strong>
                            <?php foreach ($errors as $err): ?>
                                <div class="db-status">
                                    <span class="db-status-icon db-status-error">❌</span>
                                    <span class="error"><?php echo htmlspecialchars($err); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="success-box mb-3 col-md-6 mx-auto">
                            <span class="db-status-icon db-status-success">✔</span>
                            <span class="success"><?php echo translate('msg_recaptcha_saved', 'reCAPTCHA settings saved successfully!'); ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="row justify-content-center">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="captcha_type" class="form-label"><?php echo translate('label_captcha_type', 'CAPTCHA Type'); ?></label>
                                    <select id="captcha_type" name="captcha_type" class="form-select">
                                        <option value="recaptcha" <?php echo (($_POST['captcha_type'] ?? 'recaptcha') === 'recaptcha') ? 'selected' : ''; ?>><?php echo translate('option_recaptcha', 'reCAPTCHA'); ?></option>
                                        <option value="hcaptcha" disabled><?php echo translate('option_hcaptcha', 'hCaptcha (Coming Soon)'); ?></option>
                                        <option value="other" disabled><?php echo translate('option_other', 'Other (Coming Soon)'); ?></option>
                                    </select>
                                </div>

                                <div class="mb-3 form-check">
                                    <input type="checkbox" id="recaptcha_enabled" name="recaptcha_enabled" class="form-check-input" <?php echo isset($_POST['recaptcha_enabled']) || $recaptcha_status === 'enabled' ? 'checked' : ''; ?>>
                                    <label for="recaptcha_enabled" class="form-check-label"><?php echo translate('label_recaptcha_enabled', 'Enable reCAPTCHA'); ?></label>
                                </div>

                                <div class="recaptcha-fields <?php echo (isset($_POST['recaptcha_enabled']) || $recaptcha_status === 'enabled') ? 'active' : ''; ?>">
                                    <div class="mb-3">
                                        <label for="recaptcha_site_key" class="form-label"><?php echo translate('label_recaptcha_site_key', 'Site Key'); ?></label>
                                        <input type="text" id="recaptcha_site_key" name="recaptcha_site_key" class="form-control" placeholder="<?php echo translate('placeholder_recaptcha_default', 'Leave empty for default'); ?>" value="<?php echo htmlspecialchars($_POST['recaptcha_site_key'] ?? (defined('RECAPTCHA_SITE_KEY') ? RECAPTCHA_SITE_KEY : '')); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="recaptcha_secret_key" class="form-label"><?php echo translate('label_recaptcha_secret_key', 'Secret Key'); ?></label>
                                        <input type="text" id="recaptcha_secret_key" name="recaptcha_secret_key" class="form-control" placeholder="<?php echo translate('placeholder_recaptcha_default', 'Leave empty for default'); ?>" value="<?php echo htmlspecialchars($_POST['recaptcha_secret_key'] ?? (defined('RECAPTCHA_SECRET_KEY') ? RECAPTCHA_SECRET_KEY : '')); ?>">
                                    </div>
                                    <p class="note"><?php echo translate('note_recaptcha_empty', 'Leave reCAPTCHA fields empty to use default keys when enabled'); ?></p>
                                </div>

                                <button type="submit" class="btn btn-primary"><?php echo translate('btn_save_recaptcha', 'Save reCAPTCHA Settings'); ?></button>
                            </div>
                        </form>
                    </div>
                   <script src="<?php echo $base_path; ?>assets/js/pages/admin/settings/recaptcha.js"></script>
                </div>
            </main>
        </div>
    </div>
    <?php include_once $project_root . 'includes/footer.php'; ?>
</body>
</html>