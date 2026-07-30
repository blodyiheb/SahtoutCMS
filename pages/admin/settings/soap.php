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

$page_class = 'soap';
$errors = [];
$success = false;
$soapConfigFile = realpath($project_root . 'includes/soap.conf.php');

// Load current SOAP status
$soap_status = 'not_configured';
if (file_exists($soapConfigFile)) {
    include $soapConfigFile;
    if (!empty($soap_url) && !empty($soap_user) && !empty($soap_pass)) {
        $soap_status = 'configured';
    }
}

$soapUrl = $_POST['soap_url'] ?? 'http://127.0.0.1:7878';
$soapUser = $_POST['soap_user'] ?? '';
$soapPass = $_POST['soap_pass'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $soapUrl = trim($_POST['soap_url'] ?? 'http://127.0.0.1:7878');
    $soapUser = trim($_POST['soap_user'] ?? '');
    $soapPass = trim($_POST['soap_pass'] ?? '');

    // Validation
    if (empty($soapUrl)) {
        $errors[] = translate('error_soap_url_required', 'SOAP URL is required.');
    }
    if (empty($soapUser)) {
        $errors[] = translate('error_soap_user_required', 'GM Account Username is required.');
    }
    if (empty($soapPass)) {
        $errors[] = translate('error_soap_pass_required', 'SOAP Password is required.');
    }

    // Validate GM account
    if (empty($errors)) {
        $stmt = $auth_db->prepare("SELECT id FROM account WHERE username = ?");
        if (!$stmt) {
            $errors[] = sprintf(translate('error_db_query', 'Database query error: %s'), $auth_db->error);
        } else {
            $stmt->bind_param('s', $soapUser);
            $stmt->execute();
            $stmt->bind_result($accountId);
            $stmt->fetch();
            $stmt->close();

            if (!$accountId) {
                $errors[] = sprintf(translate('error_account_not_exist', 'Account %s does not exist in Auth DB.'), $soapUser);
            } else {
                $stmt2 = $auth_db->prepare("SELECT gmlevel FROM account_access WHERE id = ? AND RealmID = -1");
                if (!$stmt2) {
                    $errors[] = sprintf(translate('error_db_query', 'Database query error: %s'), $auth_db->error);
                } else {
                    $stmt2->bind_param('i', $accountId);
                    $stmt2->execute();
                    $stmt2->bind_result($gmLevel);
                    $stmt2->fetch();
                    $stmt2->close();

                    if (!$gmLevel || $gmLevel < 3) {
                        $errors[] = sprintf(translate('error_account_not_gm_level_3', 'Account %s exists but is not GM level 3.'), $soapUser);
                    }
                }
            }
        }
    }

    // Save settings if no errors
    if (empty($errors)) {
        $configContent = "<?php
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access to this file is not allowed.');
}

\$soap_url  = '" . addslashes($soapUrl) . "';
\$soap_user = '" . addslashes($soapUser) . "'; // Must be GM level 3
\$soap_pass = '" . addslashes($soapPass) . "';
?>";

        $configDir = dirname($soapConfigFile);
        if (!is_writable($configDir)) {
            $errors[] = sprintf(translate('error_config_dir_not_writable', 'Config directory is not writable: %s'), $configDir);
        } elseif (file_put_contents($soapConfigFile, $configContent) === false) {
            $errors[] = sprintf(translate('error_config_file_write_failed', 'Failed to write config file: %s'), $soapConfigFile);
        } else {
            $success = true;
            $soap_status = 'configured';
        }
    }
}

require_once $project_root . 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($langCode); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo translate('title_soap_settings', 'SOAP Settings'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/admin/settings/soap.css">
     <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/admin/admin_sidebar.css">
     <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/admin/settings/settings_navbar.css">
   <script src="<?php echo $base_path; ?>assets/js/pages/admin/settings/soap.js"></script>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include $project_root . 'includes/admin_sidebar.php'; ?>
            <main class="col-md-10 main-content">
                <?php include $project_root . 'pages/admin/settings/settings_navbar.php'; ?>
                <div class="content">
                    <h2><?php echo translate('header_soap_settings', 'SOAP Settings'); ?></h2>

                    <!-- Status Message -->
                    <div class="status-box mb-3">
                        <span class="db-status-icon <?php echo $soap_status === 'configured' ? 'db-status-success' : 'db-status-muted'; ?>">
                            <?php echo $soap_status === 'configured' ? '✔' : '✖'; ?>
                        </span>
                        <span class="<?php echo $soap_status === 'configured' ? 'text-success' : 'text-muted'; ?>">
                            <?php echo $soap_status === 'configured' ? translate('status_soap_configured', 'SOAP is currently configured.') : translate('status_soap_not_configured', 'SOAP is not configured.'); ?>
                        </span>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="error-box mb-3">
                            <strong><?php echo translate('error_box_title', 'Please fix the following errors:'); ?></strong>
                            <?php foreach ($errors as $err): ?>
                                <div class="db-status">
                                    <span class="db-status-icon db-status-error">❌</span>
                                    <span class="error"><?php echo htmlspecialchars($err); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="success-box mb-3">
                            <span class="db-status-icon db-status-success">✔</span>
                            <span class="success"><?php echo translate('success_soap_settings_saved', 'SOAP settings saved successfully!'); ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="row justify-content-center">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="soap_url" class="form-label"><?php echo translate('label_soap_url', 'SOAP URL'); ?></label>
                                    <input type="text" id="soap_url" name="soap_url" class="form-control" placeholder="<?php echo translate('placeholder_soap_url', 'e.g., http://127.0.0.1:7878'); ?>" value="<?php echo htmlspecialchars($soapUrl); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="soap_user" class="form-label"><?php echo translate('label_soap_user', 'GM Account Username'); ?></label>
                                    <input type="text" id="soap_user" name="soap_user" class="form-control" placeholder="<?php echo translate('placeholder_soap_user', 'Must be GM level 3'); ?>" value="<?php echo htmlspecialchars($soapUser); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="soap_pass" class="form-label"><?php echo translate('label_soap_pass', 'SOAP Password'); ?></label>
                                    <input type="password" id="soap_pass" name="soap_pass" class="form-control" placeholder="<?php echo translate('placeholder_soap_pass', 'SOAP password=Account password'); ?>" value="<?php echo htmlspecialchars($soapPass); ?>" required>
                                </div>
                                <button type="submit" class="btn btn-primary"><?php echo translate('button_save_verify_soap', 'Save & Verify SOAP'); ?></button>
                                <p class="form-text mt-2"><?php echo translate('note_soap_config', 'Note: Ensure the account has GM level 3 and SOAP is enabled in your worldserver.conf.'); ?></p>
                            </div>
                        </div>
                    </form>

                    <div class="info-box mb-3">
                        <div class="info-title" onclick="toggleInfo(this)">
                            <?php echo translate('info_box_title', 'Important Steps (Click to expand)'); ?>
                        </div>
                        <div class="info-content">
                            <ul>
                                <li><?php echo translate('info_step_1', 'Make sure the GM account exists in your Auth DB and has GM level 3 in <code>account_access</code> with <code>RealmID = -1</code>.'); ?></li>
                                <li><?php echo translate('info_step_2', 'Open your <code>worldserver.conf</code> file and set: <strong>SOAP.Enabled = 1</strong>'); ?></li>
                                <li><?php echo translate('info_step_3', 'Ensure the SOAP port in <code>soap_url</code> is correct and accessible.'); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <?php include $project_root . 'includes/footer.php'; ?>
</body>
</html>