<?php
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access to this file is not allowed.');
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    exit('Access denied.');
}

require_once 'config.php';

// Realm list configuration
$realmlist = [
    [
        'id' => 1,
        'name' => 'Sahtout Realm',
        'address' => '127.0.0.1',
        'port' => 8085,
        'logo' => 'img/logos/realm1_logo.webp'
    ],
    [
        'id' => 2,
        'name' => 'Sahtout Realm 2 Test',
        'address' => '127.0.0.1',
        'port' => 3306,
        'logo' => 'img/logos/realm2_logo.webp'
    ]
];

// Check if realm is online
function isRealmOnline($address, $port, $timeout = 2) {
    $fp = @fsockopen($address, $port, $errCode, $errStr, $timeout);
    if ($fp) {
        fclose($fp);
        return true;
    } else {
        return false;
    }
}

// Get number of online players
function getOnlinePlayers($char_db) {
    $result = $char_db->query("SELECT COUNT(*) AS count FROM characters WHERE online = 1");
    $row = $result->fetch_assoc();
    return $row['count'];
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
        return "$days days, $hours hours, $minutes minutes";
    }

    return "Unknown";
}
?>

<div class="server-status">
    <h2>Server Status</h2>
    <ul>
        <?php foreach ($realmlist as $realm): ?>
            <li>
                <img src="<?php echo $realm['logo']; ?>" alt="Realm Logo" height="40"><br>
                <strong><?php echo htmlspecialchars($realm['name']); ?>:</strong><br>

                <?php if (isRealmOnline($realm['address'], $realm['port'])): ?>
                    <span class="online">🟢 Online</span><br>
                    <span class="players">👥 Players Online: <?php echo getOnlinePlayers($char_db); ?></span><br>
                    <span class="uptime">⏱️ Uptime: <?php echo getServerUptime($auth_db, $realm['id']); ?></span><br>
                <?php else: ?>
                    <span class="offline">🔴 Offline</span><br>
                    <span class="players">👥 Players Online: 0</span><br>
                    <span class="uptime">⏱️ Uptime: Unknown</span><br>
                <?php endif; ?>

                <span class="realm-ip">🌐 Realmlist: <?php echo htmlspecialchars($realm['address']); ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
