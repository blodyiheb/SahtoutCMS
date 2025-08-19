<?php
// install/step4_mail.php
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
        // Test SMTP
        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUser;
        $mail->Password   = $smtpPass;
        $mail->SMTPSecure = $smtpSecure;
        $mail->Port       = $smtpPort;

        $mail->setFrom($smtpFrom, $smtpName);
        $mail->addAddress($smtpUser);
        $mail->Subject = 'Test Email - Sahtout CMS';
        $mail->Body    = 'This is a test email from your Sahtout CMS installation.';
        $mail->send();

        // Save configuration
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
            $error = "Cannot write to $configMailFile. Check folder permissions.";
        }

    } catch (Exception $e) {
        $error = "SMTP test failed: " . $mail->ErrorInfo;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SahtoutCMS Installer - Step 4</title>
<style>
body {margin:0;padding:0;font-family:'Cinzel', serif;background:#0a0a0a;color:#f0e6d2;}
.overlay {background: rgba(0,0,0,0.9); inset:0; display:flex; align-items:center; justify-content:center; padding:20px;}
.container {text-align:center; max-width:700px; width:100%; min-height: 70vh;max-height:90vh; overflow-y:auto; padding:30px 20px; border:2px solid #6b4226; background: rgba(20,10,5,0.95); border-radius:12px; box-shadow:0 0 30px #6b4226;}
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
function toggleHelper(el){
    const content = el.nextElementSibling;
    content.style.display = (content.style.display === 'none' ? 'block' : 'none');
}
</script>
</head>
<body>
<div class="overlay">
<div class="container">
    <h1>Step 4: Email Setup</h1>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php elseif ($success): ?>
        <p class="success">✔ Email configuration saved! Test email sent successfully.</p>
        <a href="step6_soap" class="btn">Proceed to Soap Configuration ➡️</a>
    <?php else: ?>
       <form method="post">
           <div class="section-title">SMTP Configuration</div>

           <label for="smtp_host">SMTP Host</label>
           <input type="text" id="smtp_host" name="smtp_host" placeholder="e.g., smtp.gmail.com" required>

           <label for="smtp_user">Email Address</label>
           <input type="email" id="smtp_user" name="smtp_user" placeholder="e.g., yourname@gmail.com" required>

           <label for="smtp_pass">App Password / SMTP Password</label>
           <input type="password" id="smtp_pass" name="smtp_pass" placeholder="App password for Gmail/Outlook" required>

           <label for="smtp_from">From Email</label>
           <input type="email" id="smtp_from" name="smtp_from" placeholder="e.g., noreply@yourdomain.com" value="noreply@yourdomain.com">

           <label for="smtp_name">From Name</label>
           <input type="text" id="smtp_name" name="smtp_name" placeholder="e.g., Sahtout Account" value="Sahtout Account">

           <label for="smtp_port">Port</label>
           <input type="number" id="smtp_port" name="smtp_port" placeholder="587 for TLS, 465 for SSL" value="587">

           <label for="smtp_secure">Encryption (tls or ssl)</label>
           <input type="text" id="smtp_secure" name="smtp_secure" placeholder="tls or ssl" value="tls">

           <button type="submit" class="btn">Save & Test SMTP</button>
       </form>
    <?php endif; ?>

    <div class="helper-box">
        <div class="helper-title" onclick="toggleHelper(this)">
            ⚔️ How to get your SMTP info / App Password (Click to expand)
        </div>
        <div class="helper-content">
            <ol>
                <li>Use a real email account (Gmail, Outlook, or your own domain).</li>
                <li>For Gmail, enable 2FA and generate an <strong>App Password</strong>.</li>
                <li>SMTP Host examples:
                    <ul>
                        <li>Gmail: smtp.gmail.com</li>
                        <li>Outlook: smtp.office365.com</li>
                        <li>Custom domain: usually mail.yourdomain.com</li>
                    </ul>
                </li>
                <li>Use port <strong>587</strong> with <strong>TLS</strong> or port <strong>465</strong> with <strong>SSL</strong>.</li>
                <li>Enter your email address as the username and your App Password (or regular password if allowed).</li>
                <li>The "From Email" can be the same as your SMTP user or a different sender you own.</li>
            </ol>
        </div>
    </div>
</div>
</div>
</body>
<?php include __DIR__ . '/footer.inc.php'; ?>
</html>
