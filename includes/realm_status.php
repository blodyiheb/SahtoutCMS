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

        $parts = [];
        if ($days > 0) $parts[] = $days . ' ' . translate('uptime_days');
        if ($hours > 0) $parts[] = $hours . ' ' . translate('uptime_hours');
        if ($minutes > 0 || empty($parts)) $parts[] = $minutes . ' ' . translate('uptime_minutes');

        return implode(', ', $parts);
    }
    return translate('uptime_none');
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

    /* Copy button feedback */
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
        $isOnline = isRealmOnline($realm['address'], $realm['port']);
        $onlineCount = $isOnline ? getOnlinePlayers($char_db) : 0;
        $uptime = $isOnline ? getServerUptime($auth_db, $realm['id']) : translate('uptime_none');
        $realmAddress = htmlspecialchars($realm['address'] . ':' . $realm['port'], ENT_QUOTES);
        ?>

        <div class="realm-card relative bg-gradient-to-b from-[rgba(18,21,28,.82)] to-[rgba(6,8,12,.85)] border border-[rgba(201,162,39,.22)] p-3 pb-2.5 clip-realm-card transition-all duration-300 hover:border-[rgba(242,207,82,.55)] hover:-translate-y-[1px] shadow-[inset_0_0_30px_rgba(0,0,0,.35),0_4px_12px_rgba(0,0,0,.35)]">

            <!-- Header: logo + name + status badge -->
            <div class="flex items-center gap-2.5 mb-2.5">

                <!-- Logo: 52x52, NO effect, fully original -->
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

                <!-- Realm name -->
                <div class="flex-1 min-w-0">
                    <span class="block truncate font-['Cinzel'] font-bold tracking-wider text-[#f2cf5b] text-sm glow-gold">
                        <?php echo htmlspecialchars($realm['name']); ?>
                    </span>
                </div>

                <!-- Status badge -->
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

            <!-- Stats: players + uptime -->
            <div class="grid grid-cols-2 gap-2 p-2 bg-black/45 border border-[rgba(201,162,39,.16)] clip-realm-inner">

                <div class="flex items-center gap-1.5">
                    <div class="w-[22px] h-[22px] flex items-center justify-center bg-[rgba(242,207,82,.08)] border border-[rgba(242,207,82,.25)] clip-realm-icon shrink-0">
                        <svg class="w-3 h-3 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="block font-['Cinzel'] text-[8px] font-bold tracking-[.14em] uppercase text-gray-400 leading-none">
                            <?php echo translate('players_label', 'Players'); ?>
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

            <!-- Address — IP centered, copy button pinned to the right -->
            <div class="relative mt-2 py-1.5 px-2 bg-black/35 border border-[rgba(201,162,39,.12)] clip-realm-addr">
                <div class="flex items-center justify-center gap-1.5 font-mono text-[10px] text-gray-400 text-center">
                    <svg class="w-3 h-3 text-[#f2cf5b] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/>
                    </svg>
                    <span class="text-[#c9a227] tracking-[.02em]"><?php echo $realmAddress; ?></span>
                </div>

                <!-- Copy button -->
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