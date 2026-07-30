<?php
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../includes/paths.php'; // Include paths.php
require_once __DIR__ . '/header.inc.php';
require_once __DIR__ . '/languages/language.php';

$errors = [];
$success = false;
$configMailFile = __DIR__ . '/../includes/config.mail.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $smtpEnabled = isset($_POST['smtp_enabled']);
    $smtpHost = trim($_POST['smtp_host'] ?? '');
    $smtpUser = trim($_POST['smtp_user'] ?? '');
    $smtpPass = trim($_POST['smtp_pass'] ?? '');
    $smtpFrom = trim($_POST['smtp_from'] ?? 'noreply@yourdomain.com');
    $smtpName = trim($_POST['smtp_name'] ?? 'Sahtout Account');
    $smtpPort = trim($_POST['smtp_port'] ?? '587');
    $smtpSecure = trim($_POST['smtp_secure'] ?? 'tls');

    if ($smtpEnabled) {
        // Validate SMTP fields
        if (empty($smtpHost)) {
            $errors[] = translate('err_smtp_host_required', 'SMTP Host is required.');
        }
        if (empty($smtpUser)) {
            $errors[] = translate('err_smtp_user_required', 'SMTP Username is required.');
        }
        if (empty($smtpPass)) {
            $errors[] = translate('err_smtp_pass_required', 'SMTP Password is required.');
        }

        if (empty($errors)) {
            require_once __DIR__ . '/../vendor/autoload.php';
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
                $mail->Body = translate('mail_test_body', 'This is a test email from your Sahtout CMS installation.');
                $mail->send();

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

                if (file_put_contents($configMailFile, $configContent) === false) {
                    $errors[] = translate('err_write_mail_config', 'Cannot write to %s.', $configMailFile);
                } else {
                    $success = true;
                }
            } catch (Exception $e) {
                $errors[] = translate('err_smtp_test_failed', 'SMTP test failed: %s', $mail->ErrorInfo);
            }
        }
    } else {
        // SMTP disabled, write minimal config
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

        if (file_put_contents($configMailFile, $configContent) === false) {
            $errors[] = translate('err_write_mail_config', 'Cannot write to %s.', $configMailFile);
        } else {
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($langCode ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <title><?= translate('installer_title', 'SahtoutCMS Installer') ?> - <?= translate('step5_title', 'Step 5: Email Setup') ?></title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Cinzel', serif;
            background: #0a0a0a;
            color: #f0e6d2;
        }
        .overlay {
            background: rgba(0,0,0,0.9);
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            text-align: center;
            max-width: 700px;
            width: 100%;
            min-height: 70vh;
            max-height: 90vh;
            overflow-y: auto;
            padding: 30px 20px;
            border: 2px solid #6b4226;
            background: rgba(20,10,5,0.95);
            border-radius: 12px;
            box-shadow: 0 0 30px #6b4226;
        }
        h1 {
            font-size: 2.5em;
            margin-bottom: 20px;
            color: #d4af37;
            text-shadow: 0 0 10px #000;
        }
        label {
            display: block;
            text-align: left;
            margin: 10px 0 5px;
            font-family: 'Roboto', Arial, sans-serif;
            color: #f0e6d2;
        }
        input {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #6b4226;
            background: rgba(30,15,5,0.9);
            color: #f0e6d2;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            font-size: 1.2em;
            font-weight: bold;
            color: #fff;
            background: linear-gradient(135deg,#6b4226,#a37e2c);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 0 15px #a37e2c;
            transition: 0.3s ease;
            margin-top: 15px;
        }
        .btn:hover {
            background: linear-gradient(135deg,#a37e2c,#d4af37);
            box-shadow: 0 0 25px #d4af37;
        }
        .error-box {
            background: rgba(255,64,64,0.2);
            border: 1px solid #ff4040;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 20px;
            text-align: left;
        }
        .error {
            color: #ff4040;
            font-weight: bold;
            font-family: 'Roboto', Arial, sans-serif;
            font-size: 1rem;
            margin: 5px 0;
        }
        .success {
            color: #7CFC00;
            font-weight: bold;
            margin-top: 15px;
            font-family: 'Roboto', Arial, sans-serif;
            font-size: 1rem;
        }
        .section-title {
            margin-top: 30px;
            font-size: 1.5em;
            color: #d4af37;
            text-decoration: underline;
        }
        .helper-box {
            margin-top: 25px;
            text-align: left;
            background: rgba(30,15,5,0.85);
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #6b4226;
        }
        .helper-title {
            font-weight: bold;
            color: #d4af37;
            margin-bottom: 10px;
            cursor: pointer;
        }
        .helper-content {
            display: none;
            color: #f0e6d2;
            line-height: 1.5;
        }
        /* Custom toggle switch */
        .form-check {
            max-width: 400px;
            margin: 20px auto;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .form-check-input {
            width: 0;
            height: 0;
            opacity: 0;
            position: absolute;
        }
        .form-check-label {
            font-family: 'Roboto', Arial, sans-serif;
            font-size: 1rem;
            color: #f0e6d2;
            cursor: pointer;
            padding-left: 3.5rem;
            user-select: none;
            position: relative;
        }
        .form-check-label::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3rem;
            height: 1.5rem;
            background: #6c757d; /* Gray when unchecked */
            border-radius: 1.5rem;
            transition: background-color 0.3s ease;
        }
        .form-check-label::after {
            content: '';
            position: absolute;
            left: 0.25rem;
            top: 50%;
            transform: translateY(-50%);
            width: 1.25rem;
            height: 1.25rem;
            background: #ffffff;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            transition: left 0.3s ease;
        }
        .form-check-input:checked + .form-check-label::before {
            background: #28a745; /* Green when checked */
        }
        .form-check-input:checked + .form-check-label::after {
            left: 1.5rem; /* Slide circle to the right */
        }
        .form-check-label:hover::before {
            background: #1e7e34; /* Darker green on hover when checked */
        }
        .form-check-input:not(:checked) + .form-check-label:hover::before {
            background: #5a6268; /* Darker gray on hover when unchecked */
        }
        .form-check-input:focus + .form-check-label::before {
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25); /* Focus ring */
        }
        .form-check-status {
            font-family: 'Roboto', Arial, sans-serif;
            font-size: 1rem;
            color: #f0e6d2;
        }
        @media (max-width: 768px) {
            .container {
                padding: 20px 15px;
                margin: 15px;
            }
            h1 {
                font-size: 2em;
            }
            input, .form-check {
                max-width: 100%;
            }
            .btn {
                padding: 10px 20px;
                font-size: 1.1em;
            }
            .error-box {
                margin: 15px;
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <script>
        function toggleHelper(el) {
            const content = el.nextElementSibling;
            content.style.display = content.style.display === 'none' ? 'block' : 'none';
        }
        function toggleSmtpFields() {
            const fields = document.querySelectorAll('.smtp-field');
            const enabled = document.getElementById('smtp_enabled').checked;
            fields.forEach(f => f.style.display = enabled ? 'block' : 'none');
            document.querySelector('.form-check-status').textContent = enabled ? '<?= translate('enabled', 'Enabled') ?>' : '<?= translate('missing', 'Disabled') ?>';
        }
    </script>
</head>
<body onload="toggleSmtpFields()">
    <div class="overlay">
        <div class="container">
            <h1><?= translate('step5_title', 'Step 5: Email Setup') ?></h1>

            <?php if (!empty($errors)): ?>
                <div class="error-box">
                    <?php foreach ($errors as $error): ?>
                        <p class="error">❌ <?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <p class="success">✔ <?= translate('msg_mail_saved', 'Email configuration saved! Test email sent successfully.') ?></p>
                <a href="<?php echo $base_path; ?>install/step6_soap" class="btn"><?= translate('btn_proceed_to_soap', 'Proceed to Soap Configuration ➡️') ?></a>
            <?php endif; ?>

            <?php if (!$success): ?>
                <form method="post">
                    <div class="form-check">
                        <input type="checkbox" id="smtp_enabled" name="smtp_enabled" class="form-check-input" onclick="toggleSmtpFields()" <?php echo isset($_POST['smtp_enabled']) ? 'checked' : ''; ?>>
                        <label for="smtp_enabled" class="form-check-label"><?= translate('label_enable_smtp', 'Enable SMTP Mailer?') ?></label>
                        <span class="form-check-status"><?php echo isset($_POST['smtp_enabled']) ? translate('enabled', 'Enabled') : translate('missing', 'Disabled'); ?></span>
                    </div>

                    <div class="smtp-field">
                        <div class="section-title"><?= translate('section_smtp_config', 'SMTP Configuration') ?></div>

                        <label for="smtp_host"><?= translate('label_smtp_host', 'SMTP Host') ?></label>
                        <input type="text" id="smtp_host" name="smtp_host" placeholder="<?= translate('placeholder_smtp_host', 'e.g., smtp.gmail.com') ?>" value="<?php echo htmlspecialchars($_POST['smtp_host'] ?? ''); ?>">

                        <label for="smtp_user"><?= translate('label_email_address', 'Email Address') ?></label>
                        <input type="email" id="smtp_user" name="smtp_user" placeholder="<?= translate('placeholder_email', 'e.g., yourname@gmail.com') ?>" value="<?php echo htmlspecialchars($_POST['smtp_user'] ?? ''); ?>">

                        <label for="smtp_pass"><?= translate('label_app_password', 'App Password / SMTP Password') ?></label>
                        <input type="password" id="smtp_pass" name="smtp_pass" placeholder="<?= translate('placeholder_app_password', 'App password for Gmail/Outlook') ?>" value="<?php echo htmlspecialchars($_POST['smtp_pass'] ?? ''); ?>">

                        <label for="smtp_from"><?= translate('label_from_email', 'From Email') ?></label>
                        <input type="email" id="smtp_from" name="smtp_from" value="<?php echo htmlspecialchars($_POST['smtp_from'] ?? 'noreply@yourdomain.com'); ?>">

                        <label for="smtp_name"><?= translate('label_from_name', 'From Name') ?></label>
                        <input type="text" id="smtp_name" name="smtp_name" value="<?php echo htmlspecialchars($_POST['smtp_name'] ?? 'Sahtout Account'); ?>">

                        <label for="smtp_port"><?= translate('label_port', 'Port') ?></label>
                        <input type="number" id="smtp_port" name="smtp_port" value="<?php echo htmlspecialchars($_POST['smtp_port'] ?? '587'); ?>">

                        <label for="smtp_secure"><?= translate('label_encryption', 'Encryption (tls or ssl)') ?></label>
                        <input type="text" id="smtp_secure" name="smtp_secure" value="<?php echo htmlspecialchars($_POST['smtp_secure'] ?? 'tls'); ?>">
                    </div>

                    <button type="submit" class="btn"><?= translate('btn_save_test_smtp', 'Save & Test SMTP') ?></button>
                </form>
            <?php endif; ?>

            <div class="helper-box">
                <div class="helper-title" onclick="toggleHelper(this)">
                    ⚔️ <?= translate('helper_title_smtp', 'How to get your SMTP info / App Password (Click to expand)') ?>
                </div>
                <div class="helper-content">
                    <ol>
                        <li><?= translate('helper_smtp_li1', 'Use a real email account (Gmail, Outlook, or your own domain).') ?></li>
                        <li><?= translate('helper_smtp_li2', 'For Gmail, enable 2FA and generate an <strong>App Password</strong>.') ?></li>
                        <li><?= translate('helper_smtp_li3', 'SMTP Host examples:') ?><br>Gmail: smtp.gmail.com<br>Outlook: smtp.office365.com<br><?= translate('helper_smtp_custom_domain', 'Custom domain: usually mail.yourdomain.com') ?></li>
                        <li><?= translate('helper_smtp_li4', 'Use port <strong>587</strong> with <strong>TLS</strong> or port <strong>465</strong> with <strong>SSL</strong>.') ?></li>
                        <li><?= translate('helper_smtp_li5', 'Enter your email address as the username and your App Password (or regular password if allowed).') ?></li>
                        <li><?= translate('helper_smtp_li6', 'The "From Email" can be the same as your SMTP user or a different sender you own.') ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/footer.inc.php'; ?>
</body>
</html>