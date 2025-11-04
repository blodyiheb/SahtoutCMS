<?php
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../includes/paths.php';
require_once __DIR__ . '/header.inc.php';
require_once __DIR__ . '/languages/language.php';

$errors   = [];
$success  = false;
$dbStatus = [];

$configFile    = realpath(__DIR__ . '/../includes/config.php');
$configCapFile = realpath(__DIR__ . '/../includes/config.cap.php');

$default_site_key   = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI';
$default_secret_key = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* --------------------------------------------------------------
   Helper – create a mysqli connection and return [conn, error]
   -------------------------------------------------------------- */
function makeConnection(array $c): array
{
    $conn  = null;
    $error = '';

    try {
        $conn = new mysqli($c['host'], $c['user'], $c['pass'], $c['name'], $c['port']);
        if ($conn->connect_error) {
            $error = $conn->connect_error;
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }

    return [$conn, $error];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $recaptcha_enabled    = isset($_POST['recaptcha_enabled']) ? 1 : 0;
    $recaptcha_site_key   = $recaptcha_enabled ? trim($_POST['recaptcha_site_key'] ?? '') : '';
    $recaptcha_secret_key = $recaptcha_enabled ? trim($_POST['recaptcha_secret_key'] ?? '') : '';

    if ($recaptcha_enabled && empty($recaptcha_site_key))  $recaptcha_site_key   = $default_site_key;
    if ($recaptcha_enabled && empty($recaptcha_secret_key))$recaptcha_secret_key = $default_secret_key;

    if ($recaptcha_enabled && (empty($recaptcha_site_key) || empty($recaptcha_secret_key))) {
        $errors[] = translate('err_recaptcha_keys_required',
            'reCAPTCHA Site Key and Secret Key are required when reCAPTCHA is enabled.');
    }

    $dbGroups = [
        'auth' => [
            'label' => translate('db_auth', 'Auth DB'),
            'name'  => trim($_POST['db_auth_name'] ?? ''),
            'host'  => trim($_POST['db_auth_host'] ?? ''),
            'port'  => trim($_POST['db_auth_port'] ?? '3306'),
            'user'  => trim($_POST['db_auth_user'] ?? ''),
            'pass'  => $_POST['db_auth_pass'] ?? '',
        ],
        'world' => [
            'label' => translate('db_world', 'World DB'),
            'name'  => trim($_POST['db_world_name'] ?? ''),
            'host'  => trim($_POST['db_world_host'] ?? ''),
            'port'  => trim($_POST['db_world_port'] ?? '3306'),
            'user'  => trim($_POST['db_world_user'] ?? ''),
            'pass'  => $_POST['db_world_pass'] ?? '',
        ],
        'char' => [
            'label' => translate('db_char', 'Char DB'),
            'name'  => trim($_POST['db_char_name'] ?? ''),
            'host'  => trim($_POST['db_char_host'] ?? ''),
            'port'  => trim($_POST['db_char_port'] ?? '3306'),
            'user'  => trim($_POST['db_char_user'] ?? ''),
            'pass'  => $_POST['db_char_pass'] ?? '',
        ],
        'site' => [
            'label' => translate('db_site', 'Site DB'),
            'name'  => trim($_POST['db_site_name'] ?? 'sahtout_site'),
            'host'  => trim($_POST['db_site_host'] ?? ''),
            'port'  => trim($_POST['db_site_port'] ?? '3306'),
            'user'  => trim($_POST['db_site_user'] ?? ''),
            'pass'  => $_POST['db_site_pass'] ?? '',
        ],
    ];

    // === Validate inputs ===
    foreach ($dbGroups as $key => $g) {
        if (empty($g['host'])) $errors[] = translate('err_host_required', '[%s] Host is required', $g['label']);
        if (empty($g['user'])) $errors[] = translate('err_user_required', '[%s] Username is required', $g['label']);
        if (empty($g['port']) || !is_numeric($g['port']) || $g['port'] < 1 || $g['port'] > 65535) {
            $errors[] = translate('err_port_invalid', '[%s] Port must be 1–65535', $g['label']);
        }
        if (empty($g['name'])) $errors[] = translate('err_dbname_required', '[%s] Database name is required', $g['label']);
    }

    // === Test connections & tables ===
    if (empty($errors)) {
        foreach ($dbGroups as $key => $g) {
            [$conn, $connError] = makeConnection($g);

            $status = ['success' => false, 'message' => ''];

            if ($connError) {
                $status['message'] = translate('err_connection_failed', 'Connection failed: %s', $connError);
            } else {
                $required = match ($key) {
                    'auth'  => ['account', 'realmcharacters'],
                    'world' => ['creature_template', 'item_template'],
                    'char'  => ['characters', 'character_inventory'],
                    'site'  => [],
                    default => [],
                };

                $missing = [];
                foreach ($required as $tbl) {
                    $res = $conn->query("SHOW TABLES LIKE '{$conn->real_escape_string($tbl)}'");
                    if (!$res || $res->num_rows === 0) $missing[] = $tbl;
                    $res?->free();
                }

                if ($missing) {
                    $status['message'] = translate('err_missing_tables', 'Missing tables: %s', implode(', ', $missing));
                } else {
                    $status['success'] = true;
                    $status['message'] = translate('msg_db_ok', 'Connected & tables OK');
                }
            }

            $dbStatus[$key] = $status;
            $dbGroups[$key]['_conn'] = $conn;

            if (!$status['success']) {
                $errors[] = "[{$g['label']}] " . $status['message'];
            }
        }
    }

    // === Write config files ===
    if (empty($errors)) {
        $cfg = "<?php\nif (!defined('ALLOWED_ACCESS')) exit('Direct access not allowed.');\n\n";
        foreach ($dbGroups as $key => $g) {
            $p = "db_{$key}_";
            $cfg .= "\${$p}host = '" . addslashes($g['host']) . "';\n";
            $cfg .= "\${$p}port = '" . addslashes($g['port']) . "';\n";
            $cfg .= "\${$p}user = '" . addslashes($g['user']) . "';\n";
            $cfg .= "\${$p}pass = '" . addslashes($g['pass']) . "';\n";
            $cfg .= "\${$p}name = '" . addslashes($g['name']) . "';\n\n";
        }

        $cfg .= "\$auth_db  = new mysqli(\$db_auth_host,  \$db_auth_user,  \$db_auth_pass,  \$db_auth_name,  \$db_auth_port);\n";
        $cfg .= "\$world_db = new mysqli(\$db_world_host, \$db_world_user, \$db_world_pass, \$db_world_name, \$db_world_port);\n";
        $cfg .= "\$char_db  = new mysqli(\$db_char_host,  \$db_char_user,  \$db_char_pass,  \$db_char_name,  \$db_char_port);\n";
        $cfg .= "\$site_db  = new mysqli(\$db_site_host,  \$db_site_user,  \$db_site_pass,  \$db_site_name,  \$db_site_port);\n\n";

        $cfg .= "if (\$auth_db->connect_error)  die('Auth DB Connection failed: '  . \$auth_db->connect_error);\n";
        $cfg .= "if (\$world_db->connect_error) die('World DB Connection failed: ' . \$world_db->connect_error);\n";
        $cfg .= "if (\$char_db->connect_error)  die('Char DB Connection failed: '  . \$char_db->connect_error);\n";
        $cfg .= "if (\$site_db->connect_error)  die('Site DB Connection failed: '  . \$site_db->connect_error);\n";
        $cfg .= "?>\n";

        $cap = "<?php\nif (!defined('ALLOWED_ACCESS')) exit('Direct access not allowed.');\n";
        $cap .= "\$recaptcha_enabled    = " . ($recaptcha_enabled ? 'true' : 'false') . ";\n";
        $cap .= "\$recaptcha_site_key   = '" . addslashes($recaptcha_site_key) . "';\n";
        $cap .= "\$recaptcha_secret_key = '" . addslashes($recaptcha_secret_key) . "';\n";
        $cap .= "define('RECAPTCHA_ENABLED', \$recaptcha_enabled);\n";
        $cap .= "define('RECAPTCHA_SITE_KEY', \$recaptcha_site_key);\n";
        $cap .= "define('RECAPTCHA_SECRET_KEY', \$recaptcha_secret_key);\n";
        $cap .= "?>\n";

        $cfgDir = dirname($configFile);
        $capDir = dirname($configCapFile);

        if (!is_writable($cfgDir)) {
            $errors[] = translate('err_config_dir_not_writable', 'Config directory not writable: %s', $cfgDir);
        } elseif (!is_writable($capDir)) {
            $errors[] = translate('err_cap_dir_not_writable', 'reCAPTCHA config directory not writable: %s', $capDir);
        } else {
            if (file_put_contents($configFile, $cfg) === false) {
                $errors[] = translate('err_failed_write_config', 'Failed to write config.php');
            }
            if (file_put_contents($configCapFile, $cap) === false) {
                $errors[] = translate('err_failed_write_cap', 'Failed to write config.cap.php');
            }
            if (empty($errors)) $success = true;
        }
    }

    foreach ($dbGroups as $g) {
        if (isset($g['_conn']) && $g['_conn'] instanceof mysqli) {
            $g['_conn']->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($langCode ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <title><?= translate('installer_title', 'SahtoutCMS Installer') ?> - <?= translate('step3_title', 'Step 3: Database & reCAPTCHA Setup') ?></title>
    <style>
        :root {
            --bg: #0a0a0a; --card: rgba(20,10,5,0.95); --accent: #6b4226; --gold: #d4af37;
            --gold-h: #a37e2c; --text: #f0e6d2; --input: rgba(30,15,5,0.9); --border: #6b4226;
            --err: #ff4040; --succ: #7CFC00; --warn: #ff9800;
        }
        * { box-sizing: border-box; }
        body { margin:0; font-family:'Cinzel',serif; background:var(--bg); color:var(--text); }
        .overlay { background:rgba(0,0,0,0.9); display:flex; align-items:center; justify-content:center; padding:20px; min-height:100vh; }
        .container { max-width:820px; width:100%; background:var(--card); border:2px solid var(--accent); border-radius:12px; padding:30px; box-shadow:0 0 30px var(--accent); max-height:95vh; overflow-y:auto; }
        h1 { font-size:2.5em; color:var(--gold); text-align:center; margin:0 0 20px; text-shadow:0 0 10px #000; }
        h2.section-title { margin:30px 0 15px; font-size:1.6em; color:var(--gold); text-decoration:underline; text-align:center; }

        .db-group { background:rgba(40,20,10,0.7); border:1px solid var(--accent); border-radius:10px; padding:18px; margin-bottom:22px; position:relative; }
        .db-group:hover { box-shadow:0 6px 18px rgba(212,175,55,0.2); border-color:var(--gold); }
        .db-group h3 { margin:0 0 15px; color:var(--gold); font-size:1.3em; display:flex; align-items:center; gap:8px; }

        .db-status {
            position: absolute; top: 12px; right: 12px; font-weight: bold; font-size: 0.9rem;
            max-width: 70%; word-wrap: break-word; overflow-wrap: break-word; line-height: 1.3; text-align: right;
        }
        .db-status.success { color: var(--succ); }
        .db-status.error   { color: var(--err); }

        .input-group { position:relative; margin-bottom:14px; }
        .input-group label { display:block; margin-bottom:6px; font-family:'Roboto',sans-serif; color:var(--text); font-size:.95rem; font-weight:500; }
        .input-group input { width:100%; padding:10px 12px 10px 38px; border-radius:6px; border:1px solid var(--border); background:var(--input); color:var(--text); font-family:'Roboto',sans-serif; transition:all 0.3s; }
        .input-group input:focus { outline:none; border-color:var(--gold); box-shadow:0 0 0 2px rgba(212,175,55,0.3); }
        .input-group .icon { position:absolute; left:10px; top:34px; color:var(--gold); font-size:1.1rem; }

        .error-box { background:rgba(255,64,64,0.2); border:1px solid var(--err); border-radius:6px; padding:12px; margin:15px 0; }
        .error { color:var(--err); font-weight:bold; margin:5px 0; font-family:'Roboto',sans-serif; font-size:.95rem; }
        .success-msg { color:var(--succ); font-weight:bold; text-align:center; margin:15px 0; font-size:1.1rem; }

        .btn { display:block; width:100%; max-width:400px; margin:25px auto 0; padding:14px; font-size:1.2em; font-weight:bold; color:white;
                background:linear-gradient(135deg,var(--accent),var(--gold-h)); border:none; border-radius:8px; cursor:pointer; box-shadow:0 0 15px var(--gold-h); transition:0.3s; }
        .btn:hover { background:linear-gradient(135deg,var(--gold-h),var(--gold)); box-shadow:0 0 25px var(--gold); }

        .recaptcha-fields { display:none; margin-top:15px; }
        .recaptcha-fields.active { display:block; }
        .form-check { display:flex; align-items:center; justify-content:center; gap:10px; margin:20px 0; }
        .form-check-input { display:none; }
        .form-check-label { cursor:pointer; padding-left:3.5rem; position:relative; font-family:'Roboto',sans-serif; color:var(--text); }
        .form-check-label::before { content:''; position:absolute; left:0; top:50%; transform:translateY(-50%); width:3rem; height:1.5rem; background:#6c757d; border-radius:1.5rem; transition:background 0.3s; }
        .form-check-label::after { content:''; position:absolute; left:.25rem; top:50%; transform:translateY(-50%); width:1.25rem; height:1.25rem; background:white; border-radius:50%; box-shadow:0 2px 4px rgba(0,0,0,0.3); transition:left 0.3s; }
        .form-check-input:checked + .form-check-label::before { background:#28a745; }
        .form-check-input:checked + .form-check-label::after { left:1.5rem; }

        .note { font-size:0.85rem; color:#a37e2c; font-style:italic; margin-top:5px; }

        @media (max-width:768px) {
            .container { padding:20px; }
            h1 { font-size:2em; }
            .db-group { padding:15px; }
            .db-status { max-width: 65%; font-size: 0.85rem; }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <script>
        function toggleRecaptchaFields() {
            const f = document.querySelector('.recaptcha-fields');
            const e = document.getElementById('recaptcha_enabled').checked;
            f.classList.toggle('active', e);
            document.querySelector('.form-check-status').textContent = e ? '<?= translate('enabled', 'Enabled') ?>' : '<?= translate('disabled', 'Disabled') ?>';
        }
    </script>
</head>
<body onload="toggleRecaptchaFields()">
<div class="overlay">
    <div class="container">
        <h1><?= translate('installer_name', 'SahtoutCMS Installer') ?></h1>
        <h2 class="section-title"><?= translate('step3_title', 'Step 3: Database & reCAPTCHA Setup') ?></h2>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($dbStatus)): ?>
            <?php foreach ($dbGroups as $key => $g): ?>
                <?php $st = $dbStatus[$key]; ?>
                <div class="db-group">
                    <h3><i class="ri-<?= $key==='auth'?'shield-check':($key==='world'?'earth':($key==='char'?'user':'database')) ?>-line"></i> <?= $g['label'] ?></h3>
                    <div class="db-status <?= $st['success'] ? 'success' : 'error' ?>">
                        <?= $st['success'] ? translate('success', 'Success') : translate('error', 'Error') ?> <?= htmlspecialchars($st['message']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="error-box">
                <strong><?= translate('err_fix_errors', 'Please fix the following errors:') ?></strong>
                <?php foreach ($errors as $e): ?>
                    <div class="error"><?= htmlspecialchars($e) ?></div>
                <?php endforeach; ?>
            </div>
        <?php elseif ($success): ?>
            <div class="success-msg">
                <?= translate('msg_config_saved', 'All databases connected successfully! Config and reCAPTCHA files created.') ?>
            </div>
            <a href="<?= $base_path ?>install/step4_realm" class="btn">
                <?= translate('btn_proceed_to_realm', 'Proceed to Step 4 Realm configuration') ?>
            </a>
        <?php endif; ?>

        <?php if (!$success): ?>
            <form method="post">
                <?php foreach (['auth','world','char','site'] as $type): ?>
                    <div class="db-group">
                        <h3><i class="ri-<?= $type==='auth'?'shield-check':($type==='world'?'earth':($type==='char'?'user':'database')) ?>-line"></i> 
                            <?= $dbGroups[$type]['label'] ?? translate("db_{$type}", ucfirst($type) . ' DB') ?>
                        </h3>

                        <div class="input-group">
                            <label><?= translate('label_db_host', 'Host') ?></label>
                            <span class="icon"><i class="ri-server-line"></i></span>
                            <input type="text" name="db_<?= $type ?>_host"
                                   value="<?= htmlspecialchars($_POST["db_{$type}_host"] ?? 'localhost') ?>" required>
                        </div>

                        <div class="input-group">
                            <label><?= translate('label_db_port', 'Port') ?> <small>(<?= translate('default', 'default') ?> 3306)</small></label>
                            <span class="icon"><i class="ri-plug-line"></i></span>
                            <input type="text" name="db_<?= $type ?>_port"
                                   value="<?= htmlspecialchars($_POST["db_{$type}_port"] ?? '3306') ?>" required>
                        </div>

                        <div class="input-group">
                            <label><?= translate('label_db_user', 'Username') ?></label>
                            <span class="icon"><i class="ri-user-line"></i></span>
                            <input type="text" name="db_<?= $type ?>_user"
                                   value="<?= htmlspecialchars($_POST["db_{$type}_user"] ?? '') ?>" required>
                        </div>

                        <div class="input-group">
                            <label><?= translate('label_db_pass', 'Password') ?></label>
                            <span class="icon"><i class="ri-lock-password-line"></i></span>
                            <input type="password" name="db_<?= $type ?>_pass"
                                   value="<?= htmlspecialchars($_POST["db_{$type}_pass"] ?? '') ?>">
                        </div>

                        <div class="input-group">
                            <label><?= translate('label_db_name', 'Database Name') ?></label>
                            <span class="icon"><i class="ri-database-2-line"></i></span>
                            <input type="text" name="db_<?= $type ?>_name"
                                   value="<?= htmlspecialchars($_POST["db_{$type}_name"] ?? ($type==='site'?'sahtout_site':'')) ?>" required>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="section-title"><?= translate('section_recaptcha', 'reCAPTCHA V2 Configuration') ?></div>
                <div class="form-check">
                    <input type="checkbox" id="recaptcha_enabled" name="recaptcha_enabled" class="form-check-input" onclick="toggleRecaptchaFields()" <?= isset($_POST['recaptcha_enabled']) ? 'checked' : '' ?>>
                    <label for="recaptcha_enabled" class="form-check-label"><?= translate('label_recaptcha_enabled', 'Enable reCAPTCHA') ?></label>
                    <span class="form-check-status"><?= isset($_POST['recaptcha_enabled']) ? translate('enabled', 'Enabled') : translate('disabled', 'Disabled') ?></span>
                </div>

                <div class="recaptcha-fields <?= isset($_POST['recaptcha_enabled']) ? 'active' : '' ?>">
                    <div class="input-group">
                        <label><?= translate('label_recaptcha_site_key', 'Site Key') ?></label>
                        <input type="text" name="recaptcha_site_key" placeholder="<?= translate('placeholder_recaptcha_default', 'Leave empty for default') ?>" value="<?= htmlspecialchars($_POST['recaptcha_site_key'] ?? '') ?>">
                    </div>
                    <div class="input-group">
                        <label><?= translate('label_recaptcha_secret_key', 'Secret Key') ?></label>
                        <input type="text" name="recaptcha_secret_key" placeholder="<?= translate('placeholder_recaptcha_default', 'Leave empty for default') ?>" value="<?= htmlspecialchars($_POST['recaptcha_secret_key'] ?? '') ?>">
                    </div>
                    <p class="note"><?= translate('note_recaptcha_empty', 'Leave empty to use default test keys.') ?></p>
                </div>

                <button type="submit" class="btn"><?= translate('btn_test_save_db', 'Test & Save Settings') ?></button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/footer.inc.php'; ?>
</body>
</html>