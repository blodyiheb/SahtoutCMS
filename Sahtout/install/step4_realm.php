<?php
define('ALLOWED_ACCESS', true);
include __DIR__ . '/header.inc.php';

$errors = [];
$success = false;
$realmsFile = realpath(__DIR__ . '/../includes/realm_status.php');
$defaultLogo = 'img/logos/realm1_logo.webp';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $realmName = trim($_POST['realm_name'] ?? '');
    $realmIP = trim($_POST['realm_ip'] ?? '');
    $realmPort = (int) ($_POST['realm_port'] ?? 0);

    if (empty($realmName)) {
        $errors[] = "❌ " . translate('err_realm_name_required', 'Realm Name is required.');
    }
    if (empty($realmIP)) {
        $errors[] = "❌ " . translate('err_realm_ip_required', 'Realm IP is required.');
    }
    if ($realmPort <= 0 || $realmPort > 65535) {
        $errors[] = "❌ " . translate('err_realm_port_invalid', 'Realm Port must be a valid number (1-65535).');
    }

    if (empty($errors)) {
        $realmConfig = "<?php
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access to this file is not allowed.');
}

if (basename(\$_SERVER['PHP_SELF']) == basename(__FILE__)) {
    exit('Access denied.');
}

require_once 'config.php';

\$realmlist = [
    [
        'id' => 1,
        'name' => '" . addslashes($realmName) . "',
        'address' => '" . addslashes($realmIP) . "',
        'port' => $realmPort,
        'logo' => '" . addslashes($defaultLogo) . "'
    ],
    [
        'id' => 2,
        'name' => 'MySQL database Test',
        'address' => '127.0.0.1',
        'port' => 3306,
        'logo' => 'img/logos/realm2_logo.webp'
    ]
];

function isRealmOnline(\$address, \$port, \$timeout = 2) {
    \$fp = @fsockopen(\$address, \$port, \$errCode, \$errStr, \$timeout);
    if (\$fp) {
        fclose(\$fp);
        return true;
    }
    return false;
}

function getOnlinePlayers(\$char_db) {
    \$result = \$char_db->query(\"SELECT COUNT(*) AS count FROM characters WHERE online = 1\");
    \$row = \$result->fetch_assoc();
    return \$row['count'] ?? 0;
}

function getServerUptime(mysqli \$auth_db, int \$realmId = 1): string {
    \$stmt = \$auth_db->prepare(\"SELECT uptime FROM uptime WHERE realmid = ? ORDER BY starttime DESC LIMIT 1\");
    \$stmt->bind_param('i', \$realmId);
    \$stmt->execute();
    \$result = \$stmt->get_result();
    if (\$result && \$row = \$result->fetch_assoc()) {
        \$uptimeSeconds = (int)\$row['uptime'];
        \$days = floor(\$uptimeSeconds / 86400);
        \$hours = floor((\$uptimeSeconds % 86400) / 3600);
        \$minutes = floor((\$uptimeSeconds % 3600) / 60);
        return \"\$days days, \$hours hours, \$minutes minutes\";
    }
    return \"Unknown\";
}
?>

<div class=\"server-status\">
    <h2>" . translate('server_status_title', 'Server Status') . "</h2>
    <ul>
        <?php foreach (\$realmlist as \$realm): ?>
            <li>
                <img src=\"<?php echo \$realm['logo']; ?>\" alt=\"Realm Logo\" height=\"40\"><br>
                <strong><?php echo htmlspecialchars(\$realm['name']); ?>:</strong><br>
                <?php if (isRealmOnline(\$realm['address'], \$realm['port'])): ?>
                    <span class=\"online\">🟢 " . translate('status_online', 'Online') . "</span><br>
                    <span class=\"players\">👥 " . translate('players_online', 'Players Online') . ": <?php echo getOnlinePlayers(\$char_db); ?></span><br>
                    <span class=\"uptime\">⏱️ " . translate('uptime', 'Uptime') . ": <?php echo getServerUptime(\$auth_db, \$realm['id']); ?></span><br>
                <?php else: ?>
                    <span class=\"offline\">🔴 " . translate('status_offline', 'Offline') . "</span><br>
                    <span class=\"players\">👥 " . translate('players_online', 'Players Online') . ": 0</span><br>
                    <span class=\"uptime\">⏱️ " . translate('uptime', 'Uptime') . ": " . translate('uptime_unknown', 'Unknown') . "</span><br>
                <?php endif; ?>
                <span class=\"realm-ip\">🌐 " . translate('realmlist', 'Realmlist') . ": <?php echo htmlspecialchars(\$realm['address']); ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
";

        $configDir = dirname($realmsFile);
        if (!is_writable($configDir)) {
            $errors[] = "⚠️ " . translate('err_config_dir_not_writable', 'Config directory is not writable:') . " {$configDir}";
        } elseif (file_put_contents($realmsFile, $realmConfig) === false) {
            $errors[] = "⚠️ " . translate('err_write_realm_config', 'Cannot write realm configuration file:') . " {$realmsFile}";
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
    <title><?= translate('installer_title') ?> - <?= translate('step4_title', 'Step 4: Realm Setup') ?></title>
    <style>
        body {margin:0;padding:0;font-family:'Cinzel', serif;background:#0a0a0a;color:#f0e6d2;}
        .overlay {background: rgba(0,0,0,0.9); inset:0; display:flex; align-items:center; justify-content:center; padding:20px;}
        .container {text-align:center; max-width:700px; width:100%; min-height:70vh; max-height:90vh; overflow-y:auto; padding:30px 20px; border:2px solid #6b4226; background: rgba(20,10,5,0.95); border-radius:12px; box-shadow:0 0 30px #6b4226;}
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
        .db-status-error {color: #ff4040;}
        .db-status-success {color: #7CFC00;}
        .note {font-size:0.9em; color:#a37e2c; margin-top:10px; text-align:left;}
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="overlay">
    <div class="container">
        <h1>⚔️ <?= translate('installer_name') ?></h1>
        <h2 class="section-title"><?= translate('step4_title', 'Step 4: Realm Setup') ?></h2>

        <?php if (!empty($errors)): ?>
            <div class="error-box">
                <strong><?= translate('err_fix_errors') ?></strong>
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
                <span class="success"><?= translate('msg_realm_saved', 'Realm configuration saved successfully!') ?></span>
            </div>
            <a href="step5_mail" class="btn"><?= translate('btn_proceed_to_mail', 'Proceed to Email Setup ➡️') ?></a>
        <?php endif; ?>

        <?php if (!$success): ?>
            <form method="post">
                <div class="section-title"><?= translate('section_realm_config', 'Realm 1 Configuration') ?></div>
                <label for="realm_name"><?= translate('label_realm_name', 'Realm Name') ?></label>
                <input type="text" id="realm_name" name="realm_name" placeholder="<?= translate('placeholder_realm_name', 'Enter realm name') ?>" value="<?= htmlspecialchars($_POST['realm_name'] ?? 'Sahtout Realm') ?>" required>

                <label for="realm_ip"><?= translate('label_realm_ip', 'Realm IP / Host') ?></label>
                <input type="text" id="realm_ip" name="realm_ip" placeholder="127.0.0.1" value="<?= htmlspecialchars($_POST['realm_ip'] ?? '127.0.0.1') ?>" required>

                <label for="realm_port"><?= translate('label_realm_port', 'Realm Port') ?></label>
                <input type="number" id="realm_port" name="realm_port" placeholder="8085" value="<?= htmlspecialchars($_POST['realm_port'] ?? '8085') ?>" required>

                <p class="note"><?= translate('note_realm_config', 'Note: This configures Realm 1. Realm 2 is a MySQL database pre-configured with default settings Just to test.') ?></p>
                <button type="submit" class="btn"><?= translate('btn_save_realm', 'Save Realm Configuration') ?></button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/footer.inc.php'; ?>
</body>
</html>