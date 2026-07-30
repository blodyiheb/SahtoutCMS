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

$page_class = 'smtp';
require_once $project_root . 'includes/header.php';

$errors = [];
$success = false;
$configMailFile = realpath($project_root . 'includes/config.mail.php');

// Load current SMTP settings
$smtp_status = 'disabled';
$current_smtp_host = '';
$current_smtp_user = '';
$current_smtp_pass = '';
$current_smtp_from = 'noreply@yourdomain.com';
$current_smtp_name = 'Sahtout Account';
$current_smtp_port = '587';
$current_smtp_secure = 'tls';

if (file_exists($configMailFile)) {
    include $configMailFile;
    $smtp_status = defined('SMTP_ENABLED') && SMTP_ENABLED ? 'enabled' : 'disabled';
    if ($smtp_status === 'enabled') {
        require_once $project_root . 'vendor/autoload.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail = getMailer(); // Use the getMailer() function from config.mail.php
        $current_smtp_host = $mail->Host;
        $current_smtp_user = $mail->Username;
        $current_smtp_pass = ''; // Do not prefill password for security
        $current_smtp_from = $mail->From;
        $current_smtp_name = $mail->FromName;
        $current_smtp_port = $mail->Port;
        $current_smtp_secure = $mail->SMTPSecure;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $smtp_enabled = isset($_POST['smtp_enabled']);
    $smtpHost = trim($_POST['smtp_host'] ?? '');
    $smtpUser = trim($_POST['smtp_user'] ?? '');
    $smtpPass = trim($_POST['smtp_pass'] ?? '');
    $smtpFrom = trim($_POST['smtp_from'] ?? 'noreply@yourdomain.com');
    $smtpName = trim($_POST['smtp_name'] ?? 'Sahtout Account');
    $smtpPort = trim($_POST['smtp_port'] ?? '587');
    $smtpSecure = trim($_POST['smtp_secure'] ?? 'tls');

    // Validation only when SMTP is enabled
    if ($smtp_enabled) {
        if (empty($smtpHost)) {
            $errors[] = translate('err_smtp_host_required', 'SMTP Host is required.');
        }
        if (empty($smtpUser)) {
            $errors[] = translate('err_smtp_user_required', 'SMTP Username is required.');
        }
        if (empty($smtpPass)) {
            $errors[] = translate('err_smtp_pass_required', 'SMTP Password is required.');
        }
    }

    // Test SMTP only if enabled and no validation errors
    if (empty($errors) && $smtp_enabled) {
        require_once $project_root . 'vendor/autoload.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->CharSet = 'UTF-8'; // Ensure UTF-8 encoding for non-Latin characters
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;
            $mail->SMTPSecure = $smtpSecure;
            $mail->Port = $smtpPort;
            $mail->setFrom($smtpFrom, $smtpName);
            $mail->addAddress($smtpUser);
            $mail->Subject = translate('mail_test_subject', 'Test Email - Sahtout CMS');
            $mail->Body = translate('mail_test_body', 'This is a test email from your Sahtout CMS admin settings.');
            $mail->send();
        } catch (Exception $e) {
            $errors[] = translate('err_smtp_test_failed', 'SMTP test failed:') . ' ' . $mail->ErrorInfo;
        }
    }

    // Save settings regardless of enable/disable state
    if (empty($errors)) {
        if ($smtp_enabled) {
            $configContent = "<?php
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit(translate('error_direct_access', 'Direct access to this file is not allowed.'));
}

define('SMTP_ENABLED', true);

use PHPMailer\\PHPMailer\\PHPMailer;
use PHPMailer\\PHPMailer\\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function getMailer(): PHPMailer {
    \$mail = new PHPMailer(true);
    try {
        \$mail->CharSet = 'UTF-8';
        \$mail->isSMTP();
        \$mail->Host       = '" . addslashes($smtpHost) . "';
        \$mail->SMTPAuth   = true;
        \$mail->Username   = '" . addslashes($smtpUser) . "';
        \$mail->Password   = '" . addslashes($smtpPass) . "';
        \$mail->SMTPSecure = '" . addslashes($smtpSecure) . "';
        \$mail->Port       = " . (int)$smtpPort . ";
        \$mail->setFrom('" . addslashes($smtpFrom) . "', '" . addslashes($smtpName) . "');
        \$mail->isHTML(true);
    } catch (Exception \$e) {}
    return \$mail;
}
?>";
        } else {
            $configContent = "<?php
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit(translate('error_direct_access', 'Direct access to this file is not allowed.'));
}

use PHPMailer\\PHPMailer\\PHPMailer;
use PHPMailer\\PHPMailer\\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

\$smtp_enabled = false;
define('SMTP_ENABLED', \$smtp_enabled);

function getMailer(): PHPMailer {
    \$mail = new PHPMailer(true);
    return \$mail;
}
?>";
        }

        $configDir = dirname($configMailFile);
        if (!is_writable($configDir)) {
            $errors[] = sprintf(translate('err_config_dir_not_writable', 'Config directory is not writable: %s'), $configDir);
        } elseif (file_put_contents($configMailFile, $configContent) === false) {
            $errors[] = sprintf(translate('err_failed_write_config', 'Failed to write config file: %s'), $configMailFile);
        } else {
            $success = true;
            $smtp_status = $smtp_enabled ? 'enabled' : 'disabled';
            // Update current settings for display
            $current_smtp_host = $smtpHost;
            $current_smtp_user = $smtpUser;
            $current_smtp_pass = ''; // Do not store password
            $current_smtp_from = $smtpFrom;
            $current_smtp_name = $smtpName;
            $current_smtp_port = $smtpPort;
            $current_smtp_secure = $smtpSecure;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($langCode); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo translate('page_title_smtp', 'SMTP Settings'); ?></title>
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <!-- Roboto font -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=block" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/admin/settings/smtp.css">
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
                    <h2><?php echo translate('settings_smtp', 'SMTP Settings'); ?></h2>
                    
                    <!-- Status Message -->
                    <div class="status-box mb-3 col-md-6 mx-auto">
                        <span class="db-status-icon <?php echo $smtp_status === 'enabled' ? 'db-status-success' : 'db-status-muted'; ?>">
                            <?php echo $smtp_status === 'enabled' ? '✔' : '✖'; ?>
                        </span>
                        <span class="<?php echo $smtp_status === 'enabled' ? 'text-success' : 'text-muted'; ?>">
                            <?php echo translate(
                                $smtp_status === 'enabled' ? 'msg_smtp_enabled' : 'msg_smtp_disabled',
                                $smtp_status === 'enabled' ? 'SMTP is currently enabled.' : 'SMTP is currently disabled.'
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
                            <span class="success"><?php echo translate('msg_smtp_saved', 'SMTP settings saved successfully!'); ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="row justify-content-center">
                            <div class="col-md-6">
                                <div class="mb-3 form-check">
                                    <input type="checkbox" id="smtp_enabled" name="smtp_enabled" class="form-check-input" <?php echo isset($_POST['smtp_enabled']) || $smtp_status === 'enabled' ? 'checked' : ''; ?>>
                                    <label for="smtp_enabled" class="form-check-label"><?php echo translate('label_smtp_enabled', 'Enable SMTP'); ?></label>
                                </div>

                                <div class="smtp-fields <?php echo isset($_POST['smtp_enabled']) || $smtp_status === 'enabled' ? 'active' : ''; ?>">
                                    <div class="mb-3">
                                        <label for="smtp_host" class="form-label"><?php echo translate('label_smtp_host', 'SMTP Host'); ?></label>
                                        <input type="text" id="smtp_host" name="smtp_host" class="form-control" placeholder="<?php echo translate('placeholder_smtp_host', 'e.g., smtp.gmail.com'); ?>" value="<?php echo htmlspecialchars($_POST['smtp_host'] ?? $current_smtp_host); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="smtp_user" class="form-label"><?php echo translate('label_email_address', 'Email Address'); ?></label>
                                        <input type="email" id="smtp_user" name="smtp_user" class="form-control" placeholder="<?php echo translate('placeholder_email', 'e.g., yourname@gmail.com'); ?>" value="<?php echo htmlspecialchars($_POST['smtp_user'] ?? $current_smtp_user); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="smtp_pass" class="form-label"><?php echo translate('label_app_password', 'App Password / SMTP Password'); ?></label>
                                        <input type="password" id="smtp_pass" name="smtp_pass" class="form-control" placeholder="<?php echo translate('placeholder_app_password', 'App password for Gmail/Outlook'); ?>" value="<?php echo htmlspecialchars($_POST['smtp_pass'] ?? $current_smtp_pass); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="smtp_from" class="form-label"><?php echo translate('label_from_email', 'From Email'); ?></label>
                                        <input type="email" id="smtp_from" name="smtp_from" class="form-control" placeholder="<?php echo translate('placeholder_from_email', 'e.g., noreply@yourdomain.com'); ?>" value="<?php echo htmlspecialchars($_POST['smtp_from'] ?? $current_smtp_from); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="smtp_name" class="form-label"><?php echo translate('label_from_name', 'From Name'); ?></label>
                                        <input type="text" id="smtp_name" name="smtp_name" class="form-control" placeholder="<?php echo translate('placeholder_from_name', 'e.g., Sahtout Account'); ?>" value="<?php echo htmlspecialchars($_POST['smtp_name'] ?? $current_smtp_name); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="smtp_port" class="form-label"><?php echo translate('label_port', 'Port'); ?></label>
                                        <input type="number" id="smtp_port" name="smtp_port" class="form-control" placeholder="<?php echo translate('placeholder_port_tls_ssl', '587 for TLS, 465 for SSL'); ?>" value="<?php echo htmlspecialchars($_POST['smtp_port'] ?? $current_smtp_port); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="smtp_secure" class="form-label"><?php echo translate('label_encryption', 'Encryption (tls or ssl)'); ?></label>
                                        <input type="text" id="smtp_secure" name="smtp_secure" class="form-control" placeholder="<?php echo translate('placeholder_tls_or_ssl', 'tls or ssl'); ?>" value="<?php echo htmlspecialchars($_POST['smtp_secure'] ?? $current_smtp_secure); ?>">
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary"><?php echo translate('btn_save_test_smtp', 'Save & Test SMTP'); ?></button>
                            </div>
                        </div>
                    </form>

                   <script src="<?php echo $base_path; ?>assets/js/pages/admin/settings/smtp.js"></script>
                </div>
            </main>
        </div>
    </div>
    <?php require_once $project_root . 'includes/footer.php'; ?>
</body>
</html>