<?php
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit(translate('error_direct_access'));
}
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    exit(translate('error_access_denied'));
}
require_once __DIR__ . '/paths.php';
require_once __DIR__ . '/realm_config.php';

/*
|--------------------------------------------------------------------------
| Realm Status Cache Configuration
|--------------------------------------------------------------------------
*/
define('REALM_CACHE_DIR', __DIR__ . '/../cache/');
define('REALM_CACHE_TTL', 30);          // Refresh every 30 seconds
define('REALM_CACHE_TIMEOUT', 1.0);     // Socket timeout in seconds
define('REALM_CACHE_STALE_MAX', 300);   // Max age of stale fallback data: 5 minutes
define('REALM_CACHE_VERSION', 2);       // Schema version to invalidate outdated caches

// Ensure cache directory exists and is safe
if (!is_dir(REALM_CACHE_DIR)) {
    @mkdir(REALM_CACHE_DIR, 0755, true);
}

/*
|--------------------------------------------------------------------------
| Cache & Lock Helpers
|--------------------------------------------------------------------------
*/
function getRealmCacheFile($realmId) {
    return REALM_CACHE_DIR . 'realm_' . (int)$realmId . '.php';
}

function getRealmLockFile($realmId) {
    return REALM_CACHE_DIR . 'realm_' . (int)$realmId . '.lock';
}

function readRealmCache($realmId) {
    $file = getRealmCacheFile($realmId);
    if (!is_file($file) || !is_readable($file)) {
        return null;
    }

    $contents = @file_get_contents($file);
    if ($contents === false) {
        return null;
    }

    $prefix = "<?php exit; ?>\n";
    if (strpos($contents, $prefix) === 0) {
        $contents = substr($contents, strlen($prefix));
    }

    $data = json_decode($contents, true);
    if (!is_array($data) || !isset($data['version']) || $data['version'] !== REALM_CACHE_VERSION) {
        return null; // Invalidate old structure or version mismatches
    }

    if (!isset($data['online'], $data['players'], $data['alliance'], $data['horde'], $data['uptime_seconds'], $data['checked_at'])) {
        return null;
    }

    return $data;
}

function writeRealmCache($realmId, array $data) {
    $file = getRealmCacheFile($realmId);
    $data['version'] = REALM_CACHE_VERSION;
    
    $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return false;
    }

    $tmpFile = @tempnam(REALM_CACHE_DIR, 'realm_tmp_');
    if ($tmpFile === false) {
        return false;
    }

    $contents = "<?php exit; ?>\n" . $json;
    if (@file_put_contents($tmpFile, $contents, LOCK_EX) === false) {
        @unlink($tmpFile);
        return false;
    }

    if (!@rename($tmpFile, $file)) {
        @unlink($tmpFile);
        return false;
    }

    return true;
}

function acquireRealmLock($realmId) {
    $lockFile = getRealmLockFile($realmId);
    $handle = @fopen($lockFile, 'c');
    if ($handle === false) {
        return false;
    }
    if (!@flock($handle, LOCK_EX | LOCK_NB)) {
        @fclose($handle);
        return false;
    }
    return $handle;
}

function releaseRealmLock($handle) {
    if (is_resource($handle)) {
        @flock($handle, LOCK_UN);
        @fclose($handle);
    }
}

/*
|--------------------------------------------------------------------------
| Realm Diagnostic & Database Checks
|--------------------------------------------------------------------------
*/
function checkRealmOnline($address, $port) {
    $errno = 0;
    $errstr = '';
    $fp = @fsockopen($address, (int)$port, $errno, $errstr, REALM_CACHE_TIMEOUT);
    if ($fp !== false) {
        @fclose($fp);
        return true;
    }
    return false;
}

/**
 * Optimized single query with COALESCE to guarantee integer returns instead of NULL.
 */
function getOnlinePlayerStats($char_db) {
    if (!$char_db) {
        return ['players' => 0, 'alliance' => 0, 'horde' => 0];
    }

    $sql = "
        SELECT
            COUNT(*) AS total,
            COALESCE(SUM(race IN (1, 3, 4, 7, 11)), 0) AS alliance,
            COALESCE(SUM(race IN (2, 5, 6, 8, 10)), 0) AS horde
        FROM characters
        WHERE online = 1
    ";

    $result = @$char_db->query($sql);
    if (!$result) {
        return ['players' => 0, 'alliance' => 0, 'horde' => 0];
    }

    $row = $result->fetch_assoc();
    return [
        'players'  => isset($row['total']) ? (int)$row['total'] : 0,
        'alliance' => isset($row['alliance']) ? (int)$row['alliance'] : 0,
        'horde'    => isset($row['horde']) ? (int)$row['horde'] : 0
    ];
}

function getServerUptimeSeconds($auth_db, $realmId) {
    if (!$auth_db) return 0;
    $stmt = @$auth_db->prepare("SELECT uptime FROM uptime WHERE realmid = ? ORDER BY starttime DESC LIMIT 1");
    if (!$stmt) return 0;
    
    $realmId = (int)$realmId;
    $stmt->bind_param('i', $realmId);
    if (!$stmt->execute()) {
        $stmt->close();
        return 0;
    }

    $result = $stmt->get_result();
    if (!$result) {
        $stmt->close();
        return 0;
    }

    $row = $result->fetch_assoc();
    $stmt->close();
    return isset($row['uptime']) ? max(0, (int)$row['uptime']) : 0;
}

/*
|--------------------------------------------------------------------------
| Core Status Loader (with Stale Fallback & Locks)
|--------------------------------------------------------------------------
*/
function getRealmStatusData($realm, $char_db, $auth_db) {
    $realmId = isset($realm['id']) ? (int)$realm['id'] : 1;
    $address = $realm['address'] ?? '';
    $port = (int)($realm['port'] ?? 0);
    $now = time();

    $cache = readRealmCache($realmId);

    // 1. Fresh cache -> return immediately (Zero Socket, Zero SQL)
    if ($cache !== null && ($now - (int)$cache['checked_at']) < REALM_CACHE_TTL) {
        return $cache;
    }

    // 2. Try acquiring a non-blocking refresh lock
    $lockHandle = acquireRealmLock($realmId);

    if ($lockHandle === false) {
        // Another worker is refreshing -> return stale cache instantly if available
        if ($cache !== null && ($now - (int)$cache['checked_at']) <= REALM_CACHE_STALE_MAX) {
            return $cache;
        }
        // Fallback default if no usable cache exists
        return [
            'online' => false,
            'players' => 0,
            'alliance' => 0,
            'horde' => 0,
            'uptime_seconds' => 0,
            'checked_at' => $now
        ];
    }

    // Refresh $now timestamp after lock acquisition for precision accuracy
    $now = time();

    try {
        // Re-check cache in case another worker just finished writing it right before lock acquisition
        $cache = readRealmCache($realmId);
        if ($cache !== null && ($now - (int)$cache['checked_at']) < REALM_CACHE_TTL) {
            return $cache;
        }

        // Perform actual quick check
        $online = ($address !== '' && $port > 0) ? checkRealmOnline($address, $port) : false;

        $players = 0;
        $allianceCount = 0;
        $hordeCount = 0;
        $uptimeSeconds = 0;

        if ($online) {
            $playerStats = getOnlinePlayerStats($char_db);
            $players = $playerStats['players'];
            $allianceCount = $playerStats['alliance'];
            $hordeCount = $playerStats['horde'];
            $uptimeSeconds = getServerUptimeSeconds($auth_db, $realmId);
        }

        $newCache = [
            'online' => $online,
            'players' => $players,
            'alliance' => $allianceCount,
            'horde' => $hordeCount,
            'uptime_seconds' => $uptimeSeconds,
            'checked_at' => $now
        ];

        writeRealmCache($realmId, $newCache);
        return $newCache;

    } finally {
        // Guarantee lock release even if an exception or database error occurs
        releaseRealmLock($lockHandle);
    }
}

/*
|--------------------------------------------------------------------------
| Uptime Formatting
|--------------------------------------------------------------------------
*/
function formatServerUptime($seconds) {
    $seconds = max(0, (int)$seconds);
    if ($seconds <= 0) {
        return translate('uptime_none');
    }

    $days = floor($seconds / 86400);
    $seconds %= 86400;
    $hours = floor($seconds / 3600);
    $seconds %= 3600;
    $minutes = floor($seconds / 60);

    $parts = [];
    if ($days > 0) $parts[] = $days . ' ' . translate('uptime_days');
    if ($hours > 0) $parts[] = $hours . ' ' . translate('uptime_hours');
    if ($minutes > 0 || empty($parts)) $parts[] = $minutes . ' ' . translate('uptime_minutes');

    return implode(', ', $parts);
}
?>

<style>
    /* ====== Only what Tailwind can't do natively ====== */
    .clip-realm-card  { clip-path: polygon(8px 0, 100% 0, 100% calc(100% - 8px), calc(100% - 8px) 100%, 0 100%, 0 8px); }
    .clip-realm-inner { clip-path: polygon(6px 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%, 0 6px); }
    .clip-realm-badge { clip-path: polygon(6px 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%, 0 6px); }
    .clip-realm-icon  { clip-path: polygon(4px 0, 100% 0, 100% calc(100% - 4px), calc(100% - 4px) 100%, 0 100%, 0 4px); }
    .clip-realm-addr  { clip-path: polygon(5px 0, 100% 0, 100% calc(100% - 5px), calc(100% - 5px) 100%, 0 100%, 0 5px); }
    .clip-diamond     { clip-path: polygon(50% 0, 100% 50%, 50% 100%, 0 50%); }

    .bg-badge-online  { background: linear-gradient(180deg, rgba(34,197,94,.22), rgba(22,163,74,.12)); }
    .bg-badge-offline { background: linear-gradient(180deg, rgba(239,68,68,.22), rgba(185,28,28,.12)); }

    .realm-card::before {
        content: ''; position: absolute; inset: 3px; pointer-events: none;
        border: 1px solid rgba(201,162,39,.08);
        clip-path: polygon(6px 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%, 0 6px);
    }
    .realm-card::after {
        content: ''; position: absolute; inset: 0; pointer-events: none;
        background:
            linear-gradient(#e8c552,#e8c552) left top / 8px 1px,
            linear-gradient(#e8c552,#e8c552) left top / 1px 8px,
            linear-gradient(#e8c552,#e8c552) right top / 8px 1px,
            linear-gradient(#e8c552,#e8c552) right top / 1px 8px,
            linear-gradient(#e8c552,#e8c552) left bottom / 8px 1px,
            linear-gradient(#e8c552,#e8c552) left bottom / 1px 8px,
            linear-gradient(#e8c552,#e8c552) right bottom / 8px 1px,
            linear-gradient(#e8c552,#e8c552) right bottom / 1px 8px;
        background-repeat: no-repeat;
    }

    .glow-gold        { text-shadow: 0 0 10px rgba(242,207,82,.25), 0 1px 2px rgba(0,0,0,.9); }
    .glow-gold-strong { text-shadow: 0 0 12px rgba(242,207,82,.7), 0 2px 4px rgba(0,0,0,.9); }
    .glow-green       { text-shadow: 0 0 8px rgba(74,222,128,.5); }
    .glow-red         { text-shadow: 0 0 8px rgba(248,113,113,.5); }
    .shadow-text-dark { text-shadow: 0 1px 2px rgba(0,0,0,.8); }

    .realm-card:hover { filter: drop-shadow(0 0 10px rgba(242,207,82,.18)); }

    .copy-btn .icon-clipboard { display: block; }
    .copy-btn .icon-check     { display: none; }
    .copy-btn.copied .icon-clipboard { display: none; }
    .copy-btn.copied .icon-check     { display: block; }
    .copy-btn.copied { color: #f2cf5b; }
    .copy-btn.copied .copy-bg { background: rgba(242,207,82,.12); border-color: rgba(242,207,82,.5); }
</style>

<div class="space-y-3">
    <?php foreach ($realmlist as $realm): ?>
        <?php
        $realmStatus = getRealmStatusData($realm, $char_db, $auth_db);

        $isOnline = (bool)$realmStatus['online'];
        $onlineCount = (int)$realmStatus['players'];
        $allianceCount = (int)$realmStatus['alliance'];
        $hordeCount = (int)$realmStatus['horde'];
        $uptime = $isOnline ? formatServerUptime($realmStatus['uptime_seconds']) : translate('uptime_none');
        $realmAddress = htmlspecialchars($realm['address'] . ':' . $realm['port'], ENT_QUOTES);
        
        $totalFaction = $allianceCount + $hordeCount;
        $alliancePct = ($totalFaction > 0) ? ($allianceCount / $totalFaction) * 100 : 50;
        $hordePct = ($totalFaction > 0) ? ($hordeCount / $totalFaction) * 100 : 50;
        ?>

        <div class="realm-card relative bg-gradient-to-b from-[rgba(18,21,28,.82)] to-[rgba(6,8,12,.85)] border border-[rgba(201,162,39,.22)] p-3 pb-2.5 clip-realm-card transition-all duration-300 hover:border-[rgba(242,207,82,.55)] hover:-translate-y-[1px] shadow-[inset_0_0_30px_rgba(0,0,0,.35),0_4px_12px_rgba(0,0,0,.35)]">

            <!-- Header: logo + name + status badge -->
            <div class="flex items-center gap-2.5 mb-2.5">
                <div class="w-[52px] h-[52px] flex items-center justify-center shrink-0">
                    <?php if (!empty($realm['logo'])): ?>
                        <img src="<?php echo htmlspecialchars($realm['logo']); ?>"
                             alt="<?php echo translate('realm_logo_alt', 'Realm Logo'); ?>"
                             class="w-full h-full object-contain">
                    <?php else: ?>
                        <span class="font-['Cinzel'] font-black text-[#f2cf5b] text-2xl glow-gold-strong">
                            <?php echo mb_substr($realm['name'], 0, 1); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="flex-1 min-w-0">
                    <span class="block truncate font-['Cinzel'] font-bold tracking-wider text-[#f2cf5b] text-sm glow-gold">
                        <?php echo htmlspecialchars($realm['name']); ?>
                    </span>
                </div>

                <span class="inline-flex items-center gap-1 py-0.5 pl-1.5 pr-2 text-[9px] font-bold tracking-[.1em] uppercase whitespace-nowrap clip-realm-badge border
                    <?php echo $isOnline
                        ? 'bg-badge-online text-green-400 border-[rgba(74,222,128,.35)] glow-green'
                        : 'bg-badge-offline text-red-400 border-[rgba(248,113,113,.35)] glow-red'; ?>">
                    <span class="w-1.5 h-1.5 shrink-0 clip-diamond
                        <?php echo $isOnline
                            ? 'bg-green-400 shadow-[0_0_8px_rgba(74,222,128,.8)]'
                            : 'bg-red-400 shadow-[0_0_6px_rgba(248,113,113,.6)]'; ?>"></span>
                    <?php echo $isOnline ? translate('status_online', 'Online') : translate('status_offline', 'Offline'); ?>
                </span>
            </div>

            <!-- Stats: Total Players + Uptime -->
            <div class="grid grid-cols-2 gap-2 p-2 bg-black/45 border border-[rgba(201,162,39,.16)] clip-realm-inner mb-2">
                <div class="flex items-center gap-1.5">
                    <div class="w-[22px] h-[22px] flex items-center justify-center bg-[rgba(242,207,82,.08)] border border-[rgba(242,207,82,.25)] clip-realm-icon shrink-0">
                        <svg class="w-3 h-3 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="block font-['Cinzel'] text-[8px] font-bold tracking-[.14em] uppercase text-gray-400 leading-none">
                            <?php echo translate('players_label', 'Total Online'); ?>
                        </span>
                        <span class="text-xs font-bold text-neutral-100 leading-tight shadow-text-dark">
                            <?php echo $isOnline ? number_format($onlineCount) : '—'; ?>
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-1.5">
                    <div class="w-[22px] h-[22px] flex items-center justify-center bg-[rgba(242,207,82,.08)] border border-[rgba(242,207,82,.25)] clip-realm-icon shrink-0">
                        <svg class="w-3 h-3 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="block font-['Cinzel'] text-[8px] font-bold tracking-[.14em] uppercase text-gray-400 leading-none">
                            <?php echo translate('uptime_label', 'Uptime'); ?>
                        </span>
                        <span class="text-xs font-bold text-neutral-100 leading-tight shadow-text-dark">
                            <?php echo $uptime; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Alliance vs Horde Faction Ratio Bar -->
            <div class="px-2.5 py-2 bg-black/40 border border-[rgba(201,162,39,.12)] clip-realm-inner mb-2 space-y-1.5">
                <div class="flex justify-between text-[10px] font-semibold tracking-wider uppercase">
                    <span class="text-blue-400">Alliance: <?php echo $isOnline ? number_format($allianceCount) : '—'; ?></span>
                    <span class="text-red-400">Horde: <?php echo $isOnline ? number_format($hordeCount) : '—'; ?></span>
                </div>
                <div class="h-2 w-full bg-red-950/80 overflow-hidden flex clip-realm-icon border border-[rgba(201,162,39,.15)]">
                    <div class="bg-blue-600 h-full shadow-[0_0_8px_rgba(37,99,235,0.6)] transition-all duration-500" style="width: <?php echo $isOnline ? $alliancePct : 50; ?>%;"></div>
                    <div class="bg-red-600 h-full shadow-[0_0_8px_rgba(220,38,38,0.6)] transition-all duration-500" style="width: <?php echo $isOnline ? $hordePct : 50; ?>%;"></div>
                </div>
            </div>

            <!-- Address — IP centered, copy button pinned to the right -->
            <div class="relative mt-2 py-1.5 px-2 bg-black/35 border border-[rgba(201,162,39,.12)] clip-realm-addr">
                <div class="flex items-center justify-center gap-1.5 font-mono text-[10px] text-gray-400 text-center">
                    <svg class="w-3 h-3 text-[#f2cf5b] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/>
                    </svg>
                    <span class="text-[#c9a227] tracking-[.02em]"><?php echo $realmAddress; ?></span>
                </div>

                <button type="button"
                        class="copy-btn absolute right-1.5 top-1/2 -translate-y-1/2 p-1 text-gray-500 hover:text-[#f2cf5b] transition-colors group"
                        data-copy="<?php echo $realmAddress; ?>"
                        aria-label="Copy address">
                    <span class="copy-bg absolute inset-0 border border-transparent rounded-sm transition-all group-hover:border-[rgba(242,207,82,.3)]"></span>
                    <svg class="icon-clipboard relative w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <svg class="icon-check relative w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </button>
            </div>

        </div>
    <?php endforeach; ?>
</div>

<script>
(function () {
    document.querySelectorAll('.copy-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const text = this.getAttribute('data-copy');
            const fallbackCopy = function () {
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                try { document.execCommand('copy'); } catch (_) {}
                document.body.removeChild(ta);
            };

            const done = function () {
                btn.classList.add('copied');
                setTimeout(function () { btn.classList.remove('copied'); }, 1500);
            };

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(done).catch(function () {
                    fallbackCopy(); done();
                });
            } else {
                fallbackCopy(); done();
            }
        });
    });
})();
</script>