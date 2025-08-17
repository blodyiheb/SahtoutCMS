<?php
define('ALLOWED_ACCESS', true);
include __DIR__ . '/header.inc.php';

// Check required config files
$configFiles = [
    'Database config' => __DIR__ . '/../includes/config.php',
    'reCAPTCHA config' => __DIR__ . '/../includes/config.cap.php',
    'SOAP config' => __DIR__ . '/../includes/soap.conf.php',
    'Mail config' => __DIR__ . '/../includes/config.mail.php',
];

$errors = [];
foreach ($configFiles as $name => $path) {
    if (!file_exists($path)) {
        $errors[] = "$name file missing: " . basename($path);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SahtoutCMS Installer - Finish</title>
<style>
body {margin:0;padding:0;font-family:'Cinzel', serif;background:#0a0a0a;color:#f0e6d2;}
.overlay {background: rgba(0,0,0,0.9); inset:0; display:flex; align-items:center; justify-content:center; padding:20px;}
.container {text-align:center; max-width:700px; width:100%; min-height:70vh; padding:30px 20px; border:2px solid #6b4226; background: rgba(20,10,5,0.95); border-radius:12px; box-shadow:0 0 30px #6b4226;}
h1 {font-size:2.5em; margin-bottom:20px; color:#d4af37; text-shadow:0 0 10px #000;}
p {margin-bottom:20px; line-height:1.5;}
.btn {display:inline-block; padding:12px 30px; font-size:1.2em; font-weight:bold; color:#fff; background: linear-gradient(135deg,#6b4226,#a37e2c); border:none; border-radius:8px; cursor:pointer; text-decoration:none; box-shadow:0 0 15px #a37e2c; transition:0.3s ease; margin-top:15px;}
.btn:hover {background: linear-gradient(135deg,#a37e2c,#d4af37); box-shadow:0 0 25px #d4af37;}
.error {color:#ff4040; font-weight:bold; margin-top:10px;}
.success {color:#7CFC00; font-weight:bold; margin-top:10px;}
</style>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600&display=swap" rel="stylesheet">
</head>
<body>
<div class="overlay">
<div class="container">
    <h1>Installer Complete ⚔️</h1>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <?php foreach ($errors as $err): ?>
                <p><?= htmlspecialchars($err) ?></p>
            <?php endforeach; ?>
        </div>
        <p>Some required configuration files are missing. Please make sure all steps are completed.</p>
    <?php else: ?>
        <p class="success">✔ All configuration files are present!</p>
        <p>Congratulations, SahtoutCMS is fully installed and ready to use.</p>
        <p>For security, it is strongly recommended to <strong>delete the "install" folder</strong> from your server.</p>
        <a href="../" class="btn">Go to SahtoutCMS Homepage</a>
    <?php endif; ?>
</div>
</div>
</body>
<?php include __DIR__ . '/footer.inc.php'; ?>
</html>
