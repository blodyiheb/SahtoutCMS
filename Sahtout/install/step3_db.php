<?php
define('ALLOWED_ACCESS', true);
include __DIR__ . '/header.inc.php';

$errors = [];
$success = false;
$configFile = realpath(__DIR__ . '/../includes/config.php');
$configCapFile = realpath(__DIR__ . '/../includes/config.cap.php');

$default_site_key = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI';
$default_secret_key = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost  = trim($_POST['db_host'] ?? '');
    $dbUser  = trim($_POST['db_user'] ?? '');
    $dbPass  = trim($_POST['db_pass'] ?? '');
    $dbAuth  = trim($_POST['db_auth'] ?? '');
    $dbWorld = trim($_POST['db_world'] ?? '');
    $dbChar  = trim($_POST['db_char'] ?? '');
    $dbSite  = trim($_POST['db_site'] ?? 'sahtout_site');
    $recaptcha_site_key = trim($_POST['recaptcha_site_key'] ?? '') ?: $default_site_key;
    $recaptcha_secret_key = trim($_POST['recaptcha_secret_key'] ?? '') ?: $default_secret_key;

    if (empty($dbHost)) $errors[] = translate('err_db_host_required', 'Database host is required');
    if (empty($dbUser)) $errors[] = translate('err_db_user_required', 'Database username is required');
    if (empty($dbAuth)) $errors[] = translate('err_db_auth_required', 'Auth database name is required');
    if (empty($dbWorld)) $errors[] = translate('err_db_world_required', 'World database name is required');
    if (empty($dbChar)) $errors[] = translate('err_db_char_required', 'Character database name is required');
    if (empty($dbSite)) $errors[] = translate('err_db_site_required', 'Site database name is required');

    if (empty($errors)) {
        $dbConns = [
            'Auth DB' => [$dbAuth, null, 'auth'],
            'World DB' => [$dbWorld, null, 'world'],
            'Char DB' => [$dbChar, null, 'char'],
            'Site DB' => [$dbSite, null, 'site'],
        ];

        foreach ($dbConns as $name => $connInfo) {
            try {
                $conn = new mysqli($dbHost, $dbUser, $dbPass, $connInfo[0]);
                $dbConns[$name][1] = $conn;

                $requiredTables = [];
                switch ($connInfo[2]) {
                    case 'auth':
                        $requiredTables = ['account', 'realmcharacters'];
                        break;
                    case 'world':
                        $requiredTables = ['creature_template', 'item_template'];
                        break;
                    case 'char':
                        $requiredTables = ['characters', 'character_inventory'];
                        break;
                }

                foreach ($requiredTables as $table) {
                    $result = $conn->query("SHOW TABLES LIKE '$table'");
                    if (!$result || $result->num_rows === 0) {
                        $errors[] = "❌ " . translate('err_missing_table', 'Missing required table:') . " {$table}";
                    }
                    if ($result) $result->free();
                }
            } catch (Exception $e) {
                $errors[] = "❌ {$name} " . translate('err_connection_failed', 'Connection failed:') . " " . $e->getMessage();
            }
        }

        if (empty($errors)) {
            $configContent = "<?php
if (!defined('ALLOWED_ACCESS')) exit('Direct access not allowed.');
\$db_host = '" . addslashes($dbHost) . "';
\$db_user = '" . addslashes($dbUser) . "';
\$db_pass = '" . addslashes($dbPass) . "';
\$db_auth = '" . addslashes($dbAuth) . "';
\$db_world = '" . addslashes($dbWorld) . "';
\$db_char = '" . addslashes($dbChar) . "';
\$db_site = '" . addslashes($dbSite) . "';

\$auth_db = new mysqli(\$db_host,\$db_user,\$db_pass,\$db_auth);
\$world_db = new mysqli(\$db_host,\$db_user,\$db_pass,\$db_world);
\$char_db = new mysqli(\$db_host,\$db_user,\$db_pass,\$db_char);
\$site_db = new mysqli(\$db_host,\$db_user,\$db_pass,\$db_site);

if (\$auth_db->connect_error) die('Auth DB Connection failed: ' . \$auth_db->connect_error);
if (\$world_db->connect_error) die('World DB Connection failed: ' . \$world_db->connect_error);
if (\$char_db->connect_error) die('Char DB Connection failed: ' . \$char_db->connect_error);
if (\$site_db->connect_error) die('Site DB Connection failed: ' . \$site_db->connect_error);
?>";

            $capConfigContent = "<?php
if (!defined('ALLOWED_ACCESS')) exit('Direct access not allowed.');
\$recaptcha_site_key = '" . addslashes($recaptcha_site_key) . "';
\$recaptcha_secret_key = '" . addslashes($recaptcha_secret_key) . "';
define('RECAPTCHA_SITE_KEY', \$recaptcha_site_key);
define('RECAPTCHA_SECRET_KEY', \$recaptcha_secret_key);
?>";

            $configDir = dirname($configFile);
            $capConfigDir = dirname($configCapFile);

            if (!is_writable($configDir)) {
                $errors[] = "⚠️ " . translate('err_config_dir_not_writable', 'Config directory is not writable:') . " {$configDir}";
            } elseif (!is_writable($capConfigDir)) {
                $errors[] = "⚠️ " . translate('err_cap_dir_not_writable', 'reCAPTCHA config directory is not writable:') . " {$capConfigDir}";
            } else {
                if (file_put_contents($configFile, $configContent) === false) {
                    $errors[] = "⚠️ " . translate('err_failed_write_config', 'Failed to write config file:') . " {$configFile}";
                }
                if (file_put_contents($configCapFile, $capConfigContent) === false) {
                    $errors[] = "⚠️ " . translate('err_failed_write_cap', 'Failed to write reCAPTCHA config file:') . " {$configCapFile}";
                }

                if (empty($errors)) {
                    $success = true;
                }
            }
        }

        foreach ($dbConns as $name => $connInfo) {
            if ($connInfo[1] instanceof mysqli && !$connInfo[1]->connect_error) {
                try {
                    $connInfo[1]->close();
                } catch (Exception $e) {
                    $errors[] = translate('err_close_connection', 'Failed to close connection:') . " {$name}: " . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($langCode ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <title><?= translate('installer_title') ?> - <?= translate('step3_title') ?></title>
    <style>
        body {margin:0;padding:0;font-family:'Cinzel', serif;background:#0a0a0a;color:#f0e6d2;}
        .overlay {background: rgba(0,0,0,0.9); inset:0; display:flex; align-items:center; justify-content:center; padding:20px;}
        .container {text-align:center; max-width:700px; width:100%;min-height: 70vh; max-height:90vh; overflow-y:auto; padding:30px 20px; border:2px solid #6b4226; background: rgba(20,10,5,0.95); border-radius:12px; box-shadow:0 0 30px #6b4226;}
        h1 {font-size:2.5em; margin-bottom:20px; color:#d4af37; text-shadow:0 0 10px #000;}
        label {display:block; text-align:left; margin:10px 0 5px;}
        input {width:100%; padding:10px; border-radius:6px; border:1px solid #6b4226; background:rgba(30,15,5,0.9); color:#f0e6d2;}
        .btn {display:inline-block; padding:12px 30px; font-size:1.2em; font-weight:bold; color:#fff; background: linear-gradient(135deg,#6b4226,#a37e2c); border:none; border-radius:8px; cursor:pointer; text-decoration:none; box-shadow:0 0 15px #a37e2c; transition:0.3s ease; margin-top:15px;}
        .btn:hover {background: linear-gradient(135deg,#a37e2c,#d4af37); box-shadow:0 0 25px #d4af37;}
        .error-box {background:rgba(100,0,0,0.4); padding:10px; border:1px solid #ff4040; border-radius:6px; margin-bottom:20px; text-align:left;}
        .error {color:#ff4040; font-weight:bold; margin-top:5px;}
        .success {color:#7CFC00; font-weight:bold; margin-top:15px;}
        .section-title {margin-top:30px; font-size:1.5em; color:#d4af37; text-decoration: underline;}
        .db-status {display: flex; align-items: center; margin: 5px 0;}
        .db-status-icon {margin-right: 10px; font-size: 1.2em;}
        .db-status-success {color: #7CFC00;}
        .db-status-error {color: #ff4040;}
        .note {font-size:0.9em; color:#a37e2c; margin-top:10px; text-align:left;}
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="overlay">
    <div class="container">
        <h1>⚔️ <?= translate('installer_name') ?></h1>
        <h2 class="section-title"><?= translate('step3_title', 'Step 3: Database & reCAPTCHA Setup') ?></h2>

        <?php if (!empty($errors)): ?>
            <div class="error-box">
                <strong><?= translate('err_fix_errors', 'Please fix the following errors:') ?></strong>
                <?php foreach ($errors as $err): ?>
                    <div class="db-status">
                        <span class="db-status-icon db-status-error">❌</span>
                        <span class="error"><?= htmlspecialchars($err) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif ($success): ?>
            <div class="db-status">
                <span class="db-status-icon db-status-success">✔</span>
                <span class="success"><?= translate('msg_config_saved', 'All databases connected successfully! Config and reCAPTCHA files created.') ?></span>
            </div>
            <a href="step4_realm" class="btn"><?= translate('btn_proceed_to_realm', 'Proceed to Step 4 Realm configuration ➡️') ?></a>
        <?php endif; ?>

        <?php if (!$success): ?>
            <form method="post">
                <div class="section-title"><?= translate('db_credentials', 'Database Credentials') ?></div>
                <label for="db_host"><?= translate('label_db_host', 'Database Host') ?></label>
                <input type="text" id="db_host" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>

                <label for="db_user"><?= translate('label_db_user', 'Database Username') ?></label>
                <input type="text" id="db_user" name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" required>

                <label for="db_pass"><?= translate('label_db_pass', 'Database Password') ?></label>
                <input type="password" id="db_pass" name="db_pass" value="<?= htmlspecialchars($_POST['db_pass'] ?? '') ?>">

                <label for="db_auth"><?= translate('label_db_auth', 'Auth DB Name') ?></label>
                <input type="text" id="db_auth" name="db_auth" value="<?= htmlspecialchars($_POST['db_auth'] ?? '') ?>" required>

                <label for="db_world"><?= translate('label_db_world', 'World DB Name') ?></label>
                <input type="text" id="db_world" name="db_world" value="<?= htmlspecialchars($_POST['db_world'] ?? '') ?>" required>

                <label for="db_char"><?= translate('label_db_char', 'Char DB Name') ?></label>
                <input type="text" id="db_char" name="db_char" value="<?= htmlspecialchars($_POST['db_char'] ?? '') ?>" required>

                <label for="db_site"><?= translate('label_db_site', 'Site DB Name') ?></label>
                <input type="text" id="db_site" name="db_site" value="<?= htmlspecialchars($_POST['db_site'] ?? 'sahtout_site') ?>" required>
                <p class="note"><?= translate('note_site_db', '“sahtout_site” is recommended for the site database name') ?></p>

                <div class="section-title"><?= translate('section_recaptcha', 'reCAPTCHA V2 Checkbox Keys (Optional)') ?></div>
                <label for="recaptcha_site_key"><?= translate('label_recaptcha_site_key', 'Site Key') ?></label>
                <input type="text" id="recaptcha_site_key" name="recaptcha_site_key" placeholder="<?= translate('placeholder_recaptcha_default', 'Leave empty for default') ?>" value="<?= htmlspecialchars($_POST['recaptcha_site_key'] ?? '') ?>">

                <label for="recaptcha_secret_key"><?= translate('label_recaptcha_secret_key', 'Secret Key') ?></label>
                <input type="text" id="recaptcha_secret_key" name="recaptcha_secret_key" placeholder="<?= translate('placeholder_recaptcha_default') ?>" value="<?= htmlspecialchars($_POST['recaptcha_secret_key'] ?? '') ?>">
                <p class="note"><?= translate('note_recaptcha_empty', 'Leave reCAPTCHA fields empty to use default keys') ?></p>

                <button type="submit" class="btn"><?= translate('btn_test_save_db', 'Test & Save Database and reCAPTCHA Settings') ?></button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/footer.inc.php'; ?>
</body>
</html>