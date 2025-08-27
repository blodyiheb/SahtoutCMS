<?php
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit(translate('error_direct_access', 'Direct access to this file is not allowed.'));
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    exit(translate('error_access_denied', 'Access denied.'));
}

require_once 'config.php';
require_once __DIR__ . '/../languages/language.php'; // Include language detection

$realmlist = [
    [
        'id' => 1,
        'name' => translate('realm_sahtout_name', 'Sahtout Realm'),
        'address' => '127.0.0.1',
        'port' => 8085,
        'logo' => 'img/logos/realm1_logo.webp'
    ],
    [
        'id' => 2,
        'name' => translate('realm_mysql_test_name', 'MySQL database Test'),
        'address' => '127.0.0.1',
        'port' => 3306,
        'logo' => 'img/logos/realm2_logo.webp'
    ]
];

function isRealmOnline($address, $port, $timeout = 2) {
    $fp = @fsockopen($address, $port, $errCode, $errStr, $timeout);
    if ($fp) {
        fclose($fp);
        return true;
    }
    return false;
}

function getOnlinePlayers($char_db) {
    $result = $char_db->query("SELECT COUNT(*) AS count FROM characters WHERE online = 1");
    $row = $result->fetch_assoc();
    return $row['count'] ?? 0;
}

function getServerUptime(mysqli $auth_db, int $realmId = 1): string {
    $stmt = $auth_db->prepare("SELECT uptime FROM uptime WHERE realmid = ? ORDER BY starttime DESC LIMIT 1");
    $stmt->bind_param('i', $realmId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        $uptimeSeconds = (int)$row['uptime'];
        $days = floor($uptimeSeconds / 86400);
        $hours = floor(($uptimeSeconds % 86400) / 3600);
        $minutes = floor(($uptimeSeconds % 3600) / 60);
        return sprintf("%d %s, %d %s, %d %s", 
            $days, translate('uptime_days', 'days'), 
            $hours, translate('uptime_hours', 'hours'), 
            $minutes, translate('uptime_minutes', 'minutes')
        );
    }
    return translate('uptime_unknown', 'Unknown');
}
?>

<div class="server-status">
    <h2><?php echo translate('server_status_title', 'Server Status'); ?></h2>
    <ul>
        <?php foreach ($realmlist as $realm): ?>
            <li>
                <img src="<?php echo $realm['logo']; ?>" alt="<?php echo translate('realm_logo_alt', 'Realm Logo'); ?>" height="40"><br>
                <strong><?php echo htmlspecialchars($realm['name'], ENT_QUOTES, 'UTF-8'); ?>:</strong><br>
                <?php if (isRealmOnline($realm['address'], $realm['port'])): ?>
                    <span class="online"><?php echo translate('status_online', '🟢 Online'); ?></span><br>
                    <span class="players"><?php echo sprintf(translate('players_online', '👥 Players Online: %s'), getOnlinePlayers($char_db)); ?></span><br>
                    <span class="uptime"><?php echo sprintf(translate('uptime', '⏱️ Uptime: %s'), getServerUptime($auth_db, $realm['id'])); ?></span><br>
                <?php else: ?>
                    <span class="offline"><?php echo translate('status_offline', '🔴 Offline'); ?></span><br>
                    <span class="players"><?php echo translate('players_online_none', '👥 Players Online: 0'); ?></span><br>
                    <span class="uptime"><?php echo translate('uptime_none', '⏱️ Uptime: Unknown'); ?></span><br>
                <?php endif; ?>
                <span class="realm-ip"><?php echo sprintf(translate('realmlist', '🌐 Realmlist: %s'), htmlspecialchars($realm['address'], ENT_QUOTES, 'UTF-8')); ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
</div>