<?php
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit(translate('error_direct_access')); // Use translation for error message
}
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    exit(translate('error_access_denied')); // Use translation for error message
}
require_once __DIR__ . '/paths.php';

// Realm list configuration
require_once __DIR__ . '/realm_config.php';


// Check if realm is online
function isRealmOnline($address, $port, $timeout = 2) {
    $fp = @fsockopen($address, $port, $errCode, $errStr, $timeout);
    if ($fp) {
        fclose($fp);
        return true;
    }
    return false;
}

// Get number of online players
function getOnlinePlayers($char_db) {
    $result = $char_db->query("SELECT COUNT(*) AS count FROM characters WHERE online = 1");
    $row = $result->fetch_assoc();
    return $row['count'] ?? 0;
}

// Get server uptime
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
        // Use translations for days, hours, minutes
        return translate('uptime_format', '%d %s, %d %s, %d %s', 
            $days, translate('uptime_days'), 
            $hours, translate('uptime_hours'), 
            $minutes, translate('uptime_minutes')
        );
    }
    return translate('uptime_none');
}
?>

<div class="server-status">
    <h2><?php echo translate('server_status_title'); ?></h2>
    <ul>
        <?php foreach ($realmlist as $realm): ?>
            <li>
                <img src="<?php echo $realm['logo']; ?>" alt="<?php echo translate('realm_logo_alt'); ?>" height="40"><br>
                <strong><?php echo htmlspecialchars($realm['name']); ?>:</strong><br>
                <?php if (isRealmOnline($realm['address'], $realm['port'])): ?>
                    <span class="online"><?php echo translate('status_online'); ?></span><br>
                    <span class="players"><?php echo translate('players_online', '👥 Players Online: %d', getOnlinePlayers($char_db)); ?></span><br>
                    <span class="uptime"><?php echo translate('uptime', '⏱️ Uptime: %s', getServerUptime($auth_db, $realm['id'])); ?></span><br>
                <?php else: ?>
                    <span class="offline"><?php echo translate('status_offline'); ?></span><br>
                    <span class="players"><?php echo translate('players_online_none'); ?></span><br>
                    <span class="uptime"><?php echo translate('uptime_none'); ?></span><br>
                <?php endif; ?>
                <span class="realm-ip"><?php echo translate('realmlist', '🌐 Realmlist: %s', htmlspecialchars($realm['address'])); ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
</div>