<?php
// install/step2_check.php
define('ALLOWED_ACCESS', true);
include __DIR__ . '/header.inc.php';

// Required PHP extensions
$requiredExtensions = ["mysqli", "curl", "openssl", "soap", "gd", "gmp", "mbstring", "xml"];

// Optional PHP extensions
$optionalExtensions = ["intl", "zip", "json"];

// Apache modules
$requiredApacheModules = ["mod_rewrite", "mod_headers"];
$optionalApacheModules = ["mod_expires", "mod_deflate"];

// Functions
function isApacheModuleEnabled($module) {
    if (function_exists('apache_get_modules')) return in_array($module, apache_get_modules());
    return null; // Unknown if not Apache
}

// Checks
$phpVersionPass = version_compare(PHP_VERSION, '8.0.0', '>=');

// PHP extensions
$requiredExtResults = [];
foreach ($requiredExtensions as $ext) $requiredExtResults[$ext] = extension_loaded($ext);

$optionalExtResults = [];
foreach ($optionalExtensions as $ext) $optionalExtResults[$ext] = extension_loaded($ext);

// Apache modules
$requiredApacheResults = [];
foreach ($requiredApacheModules as $mod) $requiredApacheResults[$mod] = isApacheModuleEnabled($mod);

$optionalApacheResults = [];
foreach ($optionalApacheModules as $mod) $optionalApacheResults[$mod] = isApacheModuleEnabled($mod);

// Can proceed?
$allRequiredPass = $phpVersionPass 
    && !in_array(false, $requiredExtResults, true) 
    && !in_array(false, $requiredApacheResults, true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SahtoutCMS Installer - Step 2</title>
<style>
body {margin:0;padding:0;font-family:'Cinzel', serif;background:#0a0a0a;color:#f0e6d2;}
.overlay {background: rgba(0,0,0,0.9); inset:0; display:flex; align-items:center; justify-content:center; padding:20px;}
.container {text-align:center; max-width:900px; width:100%; max-height:90vh; overflow-y:auto; padding:30px 20px; border:2px solid #6b4226; background: rgba(20,10,5,0.95); border-radius:12px; box-shadow:0 0 30px #6b4226;}
h1 {font-size:2.5em; margin-bottom:20px; color:#d4af37; text-shadow:0 0 10px #000;}
table {width:100%; border-collapse: collapse; margin:20px 0; font-size:1.1em;}
th, td {padding:12px; border:1px solid #6b4226; text-align:left;}
th {background:#6b4226; color:#f0e6d2;}
.ok {color:#7CFC00;font-weight:bold;}
.fail {color:#ff4040;font-weight:bold;}
.warn {color:#f0e68c;font-weight:bold;}
.btn {display:inline-block; padding:12px 30px; font-size:1.2em; font-weight:bold; color:#fff; background: linear-gradient(135deg,#6b4226,#a37e2c); border:none; border-radius:8px; cursor:pointer; text-decoration:none; box-shadow:0 0 15px #a37e2c; transition:0.3s ease; margin-top:15px;}
.btn:hover {background: linear-gradient(135deg,#a37e2c,#d4af37); box-shadow:0 0 25px #d4af37;}
.section-title {margin-top:30px; font-size:1.5em; color:#d4af37; text-decoration: underline;}
.help-content {display:none; text-align:left; margin-top:15px; padding:15px; background: rgba(50,30,10,0.8); border-radius:10px;}
#path_color {color: #00ffbfff; font-weight: bold;}
</style>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600&display=swap" rel="stylesheet">
<script>
function toggleHelp() {
    const help = document.getElementById('helpContent');
    help.style.display = (help.style.display === 'none') ? 'block' : 'none';
}
</script>
</head>
<body>
<div class="overlay">
<div class="container">
    <h1>Step 2: Environment Check</h1>

    <div class="section-title">PHP Version</div>
    <table>
        <tr><th>Requirement</th><th>Status</th></tr>
        <tr><td>PHP Version >= 8.0</td>
            <td><?= $phpVersionPass ? "<span class='ok'>✔ ".PHP_VERSION."</span>" : "<span class='fail'>✘ ".PHP_VERSION."</span>" ?></td>
        </tr>
    </table>

    <div class="section-title">Required PHP Extensions</div>
    <table>
        <tr><th>Extension</th><th>Status</th></tr>
        <?php foreach ($requiredExtResults as $ext => $status): ?>
            <tr><td><?= $ext ?></td><td><?= $status ? "<span class='ok'>✔</span>" : "<span class='fail'>✘</span>" ?></td></tr>
        <?php endforeach; ?>
    </table>

    <div class="section-title">Optional PHP Extensions</div>
    <table>
        <tr><th>Extension</th><th>Status</th></tr>
        <?php foreach ($optionalExtResults as $ext => $status): ?>
            <tr><td><?= $ext ?></td><td><?= $status ? "<span class='ok'>✔</span>" : "<span class='warn'>⚠</span>" ?></td></tr>
        <?php endforeach; ?>
    </table>

    <div class="section-title">Required Apache Modules</div>
    <table>
        <tr><th>Module</th><th>Status</th></tr>
        <?php foreach ($requiredApacheResults as $mod => $status): ?>
            <tr><td><?= $mod ?></td><td><?= $status===true ? "<span class='ok'>✔ Enabled</span>" : ($status===false ? "<span class='fail'>✘ Missing</span>" : "<span class='warn'>⚠ Unknown</span>") ?></td></tr>
        <?php endforeach; ?>
    </table>

    <div class="section-title">Optional Apache Modules</div>
    <table>
        <tr><th>Module</th><th>Status</th></tr>
        <?php foreach ($optionalApacheResults as $mod => $status): ?>
            <tr><td><?= $mod ?></td><td><?= $status===true ? "<span class='ok'>✔ Enabled</span>" : ($status===false ? "<span class='warn'>⚠ Missing</span>" : "<span class='warn'>⚠ Unknown</span>") ?></td></tr>
        <?php endforeach; ?>
    </table>

    <button class="btn" onclick="toggleHelp()">💡 How to enable PHP & Apache modules?</button>
    <div id="helpContent" class="help-content">
        <h3>PHP Extensions</h3>
        <p>To enable a PHP extension:</p>
        <ul>
            <li id="path_color">Go to C:\xampp\php</li>
            <li>Locate your <code>php.ini</code> file.</li>
            <li>Find the line with the extension name, e.g., <code>;extension=curl</code>.</li>
            <li>Remove the semicolon <code>;</code> to enable it: <code>extension=curl</code>.</li>
            <li>Restart your web server (Apache/Nginx).</li>
        </ul>
        <h3>Apache Modules</h3>
        <p>To enable Apache modules:</p>
        <ul>
            <li id="path_color">Go to C:\xampp\apache\conf</li>
            <li>For Windows XAMPP, check the <code>httpd.conf</code> file and uncomment the module lines.</li>
            <li>Restart Apache:</li>
        </ul>
        <p style="text-align: center;"><img src="phphttpd.png" alt="image example" width="700"></p>
    </div>

    <?php if ($allRequiredPass): ?>
        <a href="step3_db" class="btn">Proceed to Database Setup ➡️</a>
    <?php else: ?>
        <p class="fail">❌ Some required checks failed. Fix them before continuing.</p>
    <?php endif; ?>
</div>
</div>
</body>
<?php include __DIR__ . '/footer.inc.php'; ?>
</html>
