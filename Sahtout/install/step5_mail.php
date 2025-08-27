<?php
define('ALLOWED_ACCESS', true);
include __DIR__ . '/header.inc.php';

$error = '';
$success = false;
$configMailFile = __DIR__ . '/../includes/config.mail.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $smtpHost   = $_POST['smtp_host'] ?? '';
    $smtpUser   = $_POST['smtp_user'] ?? '';
    $smtpPass   = $_POST['smtp_pass'] ?? '';
    $smtpFrom   = $_POST['smtp_from'] ?? 'noreply@yourdomain.com';
    $smtpName   = $_POST['smtp_name'] ?? 'Sahtout Account';
    $smtpPort   = $_POST['smtp_port'] ?? 587;
    $smtpSecure = $_POST['smtp_secure'] ?? 'tls';

    require_once __DIR__ . '/../vendor/autoload.php';
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUser;
        $mail->Password   = $smtpPass;
        $mail->SMTPSecure = $smtpSecure;
        $mail->Port       = $smtpPort;

        $mail->setFrom($smtpFrom, $smtpName);
        $mail->addAddress($smtpUser);
        $mail->Subject = translate('mail_test_subject', 'Test Email - Sahtout CMS');
        $mail->Body    = translate('mail_test_body', 'This is a test email from your Sahtout CMS installation.');
        $mail->send();

        $configContent = "<?php
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access to this file is not allowed.');
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function getMailer(): PHPMailer {
    \$mail = new PHPMailer(true);
    try {
        \$mail->isSMTP();
        \$mail->Host       = '$smtpHost';
        \$mail->SMTPAuth   = true;
        \$mail->Username   = '$smtpUser';
        \$mail->Password   = '$smtpPass';
        \$mail->SMTPSecure = '$smtpSecure';
        \$mail->Port       = $smtpPort;
        \$mail->setFrom('$smtpFrom', '$smtpName');
        \$mail->isHTML(true);
    } catch (Exception \$e) {}
    return \$mail;
}
?>";

        if (file_put_contents($configMailFile, $configContent)) {
            $success = true;
        } else {
            $error = translate('err_write_mail_config', 'Cannot write to') . " $configMailFile. " . translate('err_check_permissions', 'Check folder permissions.');
        }

    } catch (Exception $e) {
        $error = translate('err_smtp_test_failed', 'SMTP test failed:') . " " . $mail->ErrorInfo;
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($langCode ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <title><?= translate('installer_title') ?> - <?= translate('step5_title', 'Email Setup') ?></title>
    <style>
        body {margin:0;padding:0;font-family:'Cinzel', serif;background:#0a0a0a;color:#f0e6d2;}
        .overlay {background: rgba(0,0,0,0.9); inset:0; display:flex; align-items:center; justify-content:center; padding:20px;}
        .container {text-align:center; max-width:700px; width:100%; min-height: 70vh; max-height:90vh; overflow-y:auto; padding:30px 20px; border:2px solid #6b4226; background: rgba(20,10,5,0.95); border-radius:12px; box-shadow:0 0 30px #6b4226;}
        h1 {font-size:2.5em; margin-bottom:20px; color:#d4af37; text-shadow:0 0 10px #000;}
        label {display:block; text-align:left; margin:10px 0 5px;}
        input {width:100%; padding:10px; border-radius:6px; border:1px solid #6b4226; background:rgba(30,15,5,0.9); color:#f0e6d2;}
        .btn {display:inline-block; padding:12px 30px; font-size:1.2em; font-weight:bold; color:#fff; background: linear-gradient(135deg,#6b4226,#a37e2c); border:none; border-radius:8px; cursor:pointer; text-decoration:none; box-shadow:0 0 15px #a37e2c; transition:0.3s ease; margin-top:15px;}
        .btn:hover {background: linear-gradient(135deg,#a37e2c,#d4af37); box-shadow:0 0 25px #d4af37;}
        .error {color:#ff4040; font-weight:bold; margin-top:15px;}
        .success {color:#7CFC00; font-weight:bold; margin-top:15px;}
        .section-title {margin-top:30px; font-size:1.5em; color:#d4af37; text-decoration: underline;}
        .helper-box {margin-top:25px; text-align:left; background:rgba(30,15,5,0.85); padding:15px; border-radius:10px; border:1px solid #6b4226;}
        .helper-title {font-weight:bold; color:#d4af37; margin-bottom:10px; cursor:pointer;}
        .helper-content {display:none; color:#f0e6d2; line-height:1.5;}
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600&display=swap" rel="stylesheet">
    <script>
        function toggleHelper(el) {
            const content = el.nextElementSibling;
            content.style.display = content.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</head>
<body>
    <div class="overlay">
        <div class="container">
            <h1><?= translate('step5_title', 'Step 5: Email Setup') ?></h1>

            <?php if ($error): ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php elseif ($success): ?>
                <p class="success">✔ <?= translate('msg_mail_saved', 'Email configuration saved! Test email sent successfully.') ?></p>
                <a href="step6_soap" class="btn"><?= translate('btn_proceed_to_soap', 'Proceed to Soap Configuration ➡️') ?></a>
            <?php else: ?>
                <form method="post">
                    <div class="section-title"><?= translate('section_smtp_config', 'SMTP Configuration') ?></div>

                    <label for="smtp_host"><?= translate('label_smtp_host', 'SMTP Host') ?></label>
                    <input type="text" id="smtp_host" name="smtp_host" placeholder="<?= translate('placeholder_smtp_host', 'e.g., smtp.gmail.com') ?>" required>

                    <label for="smtp_user"><?= translate('label_email_address', 'Email Address') ?></label>
                    <input type="email" id="smtp_user" name="smtp_user" placeholder="<?= translate('placeholder_email', 'e.g., yourname@gmail.com') ?>" required>

                    <label for="smtp_pass"><?= translate('label_app_password', 'App Password / SMTP Password') ?></label>
                    <input type="password" id="smtp_pass" name="smtp_pass" placeholder="<?= translate('placeholder_app_password', 'App password for Gmail/Outlook') ?>" required>

                    <label for="smtp_from"><?= translate('label_from_email', 'From Email') ?></label>
                    <input type="email" id="smtp_from" name="smtp_from" placeholder="<?= translate('placeholder_from_email', 'e.g., noreply@yourdomain.com') ?>" value="noreply@yourdomain.com">

                    <label for="smtp_name"><?= translate('label_from_name', 'From Name') ?></label>
                    <input type="text" id="smtp_name" name="smtp_name" placeholder="<?= translate('placeholder_from_name', 'e.g., Sahtout Account') ?>" value="Sahtout Account">

                    <label for="smtp_port"><?= translate('label_port', 'Port') ?></label>
                    <input type="number" id="smtp_port" name="smtp_port" placeholder="<?= translate('placeholder_port_tls_ssl', '587 for TLS, 465 for SSL') ?>" value="587">

                    <label for="smtp_secure"><?= translate('label_encryption', 'Encryption (tls or ssl)') ?></label>
                    <input type="text" id="smtp_secure" name="smtp_secure" placeholder="<?= translate('placeholder_tls_or_ssl', 'tls or ssl') ?>" value="tls">

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
                        <li><?= translate('helper_smtp_li3', 'SMTP Host examples:') ?></li>
                        <ul>
                            <li>Gmail: smtp.gmail.com</li>
                            <li>Outlook: smtp.office365.com</li>
                            <li><?= translate('helper_smtp_custom_domain', 'Custom domain: usually mail.yourdomain.com') ?></li>
                        </ul>
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