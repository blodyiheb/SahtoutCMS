<?php
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../includes/paths.php';
require_once $project_root . 'includes/session.php';
require_once $project_root . 'languages/language.php';
require_once $project_root . 'includes/item_tooltip.php';
$page_class = 'character';
require_once $project_root . 'includes/config.settings.php';
$page_title = $site_title_name . ' ' . translate('page_title', 'Character Equipment');
$page_meta_description = translate('meta_description', 'View your World of Warcraft character equipment, stats, and PvP details.');
$page_meta_robots = 'index';

ob_start();
?>
<style>
    * { font-family: 'Inter', sans-serif; }
    :root {
        --gold: #f2cf5b; --gold-dim: #c9a227; --gold-line: rgba(201,162,39,.18);
        --ink: #05070b; --card: rgba(12,15,22,.78); --muted: #7b8494; --text: #d8dbe2;
    }
    body {
        min-height: 100vh; color: var(--text); margin: 0; padding-top: 112px; background: var(--ink);
        background-image:
            radial-gradient(1000px 700px at -10% 35%, rgba(59,130,246,.14), transparent 65%),
            radial-gradient(800px 600px at -5% 85%, rgba(124,58,237,.10), transparent 70%),
            linear-gradient(180deg, #0a0e16 0%, #060810 45%, #03040a 100%);
        background-attachment: fixed;
    }
    body::before {
        content: ''; position: fixed; inset: 0; pointer-events: none; z-index: 0; opacity: .35;
        background-image:
            radial-gradient(2px 2px at 10% 20%, rgba(242,207,82,.7), transparent 55%),
            radial-gradient(1.5px 1.5px at 30% 70%, rgba(242,207,82,.5), transparent 55%),
            radial-gradient(2px 2px at 55% 40%, rgba(255,160,60,.55), transparent 55%);
        background-size: 900px 700px; animation: emberDrift 45s linear infinite;
    }
    @keyframes emberDrift { to { background-position: 900px -700px; } }
    @media (prefers-reduced-motion: reduce) { body::before { animation: none; } .tab-content { animation: none !important; } }

    .panel {
        position: relative; background: linear-gradient(180deg, rgba(22,25,32,.94), rgba(8,10,14,.92));
        border: 1px solid rgba(201,162,39,.25); box-shadow: 0 12px 32px rgba(0,0,0,.55), inset 0 0 60px rgba(0,0,0,.45);
    }
    .panel::before { content:''; position:absolute; inset:5px; border:1px solid rgba(201,162,39,.12); pointer-events:none; }
    .panel::after {
        content:''; position:absolute; inset:0; pointer-events:none; background-repeat:no-repeat;
        background:
            linear-gradient(#e8c552,#e8c552) left top / 18px 2px, linear-gradient(#e8c552,#e8c552) left top / 2px 18px,
            linear-gradient(#e8c552,#e8c552) right top / 18px 2px, linear-gradient(#e8c552,#e8c552) right top / 2px 18px,
            linear-gradient(#e8c552,#e8c552) left bottom / 18px 2px, linear-gradient(#e8c552,#e8c552) left bottom / 2px 18px,
            linear-gradient(#e8c552,#e8c552) right bottom / 18px 2px, linear-gradient(#e8c552,#e8c552) right bottom / 2px 18px;
        background-repeat: no-repeat;
    }

    /* Hero */
    .hero { text-align: center; margin-bottom: 1.5rem; --cc: #f2cf5b; }
    .character-name {
        font-family: 'Cinzel', serif; font-weight: 900; font-size: clamp(2rem, 5vw, 3.2rem); line-height: 1.1;
        color: var(--cc); text-shadow: 0 0 28px color-mix(in srgb, var(--cc) 45%, transparent), 0 3px 6px rgba(0,0,0,.85);
    }
    .chips { display: flex; flex-wrap: wrap; justify-content: center; gap: .5rem; margin-top: .85rem; }
    .chip {
        display: inline-flex; align-items: center; gap: .45rem; padding: .3rem .8rem; font-size: .82rem; font-weight: 600;
        background: rgba(0,0,0,.4); border: 1px solid var(--gold-line); color: #e5e7eb; border-radius: 999px;
    }
    .chip img { width: 20px; height: 20px; border-radius: 50%; object-fit: cover; }
    .chip i { color: var(--gold); font-size: .75rem; }
    .chip.ilvl { border-color: rgba(242,207,82,.45); color: var(--gold); }

    /* Layout */
    .paper-doll { display: grid; grid-template-columns: minmax(0,1fr) minmax(260px,380px) minmax(0,1fr); gap: 1.5rem; align-items: start; }
    .gear-col { display: flex; flex-direction: column; gap: .35rem; min-width: 0; }
    @media (max-width: 960px) {
        .paper-doll { grid-template-columns: 1fr 1fr; }
        .center-col { grid-column: 1 / -1; order: -1; }
    }
    @media (max-width: 560px) { .paper-doll { grid-template-columns: 1fr; } }

    /* Equipment slots */
    .slot {
        --q: #3b4252; display: flex; align-items: center; gap: .75rem; min-height: 54px; width: 100%; padding: .4rem .75rem .4rem .45rem;
        background: var(--card); border: 1px solid rgba(201,162,39,.1); border-left: 3px solid var(--q); transition: background .2s, border-color .2s;
    }
    .slot.right { flex-direction: row-reverse; text-align: right; border-left: 1px solid rgba(201,162,39,.1); border-right: 3px solid var(--q); padding: .4rem .45rem .4rem .75rem; }
    .slot.has-item:hover { background: rgba(20,26,38,.95); border-color: color-mix(in srgb, var(--q) 70%, transparent); }
    .slot-icon {
        position: relative; width: 40px; height: 40px; flex-shrink: 0; overflow: hidden; border-radius: 4px; background: #000;
        border: 1px solid var(--q); box-shadow: 0 0 10px color-mix(in srgb, var(--q) 35%, transparent);
    }
    .slot-icon img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .slot:not(.has-item) .slot-icon img { opacity: .35; filter: grayscale(1); }
    .slot-info { min-width: 0; flex: 1; display: flex; flex-direction: column; }
    .slot-name { font-size: .68rem; color: var(--muted); font-weight: 600; letter-spacing: .03em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .slot-item { font-size: .86rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .slot-ilvl { font-size: .68rem; color: var(--muted); }
    .empty-slot { color: #4b5563; font-size: .8rem; font-style: italic; }

    .weapons { display: grid; grid-template-columns: repeat(3, 1fr); gap: .5rem; width: 100%; margin-top: 2rem; }
.slot.compact { flex-direction: column; justify-content: center; gap: .35rem; padding: .6rem .4rem; text-align: center; border-left: 1px solid rgba(201,162,39,.1); border-bottom: 3px solid var(--q); min-height: 0; }
    .slot.compact .slot-info { align-items: center; width: 100%; }

    /* Viewer */
    .center-col { display: flex; flex-direction: column; align-items: center; gap: .85rem; }
    .character-image {
        --cc: #f2cf5b; position: relative; overflow: hidden; width: 100%; height: 430px;
        background: radial-gradient(ellipse at 50% 35%, color-mix(in srgb, var(--cc) 20%, transparent), transparent 65%), rgba(0,0,0,.35);
        border: 1px solid var(--gold-line);
    }
    .character-image .default-image { display: block; width: 100%; height: 100%; object-fit: contain; padding: 1rem; }
    .character-image canvas { position: absolute; inset: 0; width: 100% !important; height: 100% !important; }
    .model-loading, .model-error {
        position: absolute; z-index: 2; left: .75rem; right: .75rem; bottom: .75rem; padding: .6rem; text-align: center; font-size: .85rem;
        background: rgba(3,4,10,.9); border: 1px solid rgba(201,162,39,.4); color: var(--gold);
    }
    .model-error { color: #fca5a5; border-color: rgba(248,113,113,.5); }
    [hidden] { display: none !important; }

    /* Tabs */
    .tabs { display: flex; gap: .25rem; margin-top: 2rem; border-bottom: 1px solid var(--gold-line); }
    .tab-btn {
        flex: 0 0 auto; padding: .7rem 1.5rem; font-weight: 600; font-size: .9rem; color: var(--muted); cursor: pointer;
        background: transparent; border: 0; border-bottom: 2px solid transparent; margin-bottom: -1px; transition: color .2s, border-color .2s;
    }
    .tab-btn:hover { color: #fff; }
    .tab-btn.active { color: var(--gold); border-bottom-color: var(--gold); background: linear-gradient(180deg, transparent, rgba(242,207,82,.07)); }
    .tab-btn:focus-visible { outline: 2px solid var(--gold); outline-offset: 2px; }
    .tab-content { display: none; }
    .tab-content.active { display: block; animation: fadeIn .25s ease; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

    /* Stats */
    .vitals { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: .6rem; margin-bottom: 1rem; }
    .vital { background: var(--card); border: 1px solid var(--gold-line); padding: .7rem .5rem; text-align: center; }
    .vital b { display: block; font-family: 'Cinzel', serif; font-size: 1.35rem; color: #fff; line-height: 1.15; }
    .vital span { font-size: .72rem; color: var(--muted); }
    .vital.hp b { color: #4ade80; } .vital.pw b { color: #60a5fa; }
    .vital.pw.rage b { color: #f87171; } .vital.pw.energy b { color: #fde047; } .vital.pw.runic b { color: #67e8f9; }
    .stats-category { background: var(--card); border: 1px solid rgba(201,162,39,.1); padding: 1rem 1.1rem; }
    .stats-category h3 {
        display: flex; align-items: center; gap: .5rem; color: var(--gold); font-family: 'Cinzel', serif; font-size: .95rem; font-weight: 700;
        margin-bottom: .6rem; padding-bottom: .5rem; border-bottom: 1px solid var(--gold-line);
    }
    .stats-item { display: flex; justify-content: space-between; gap: 1rem; padding: .28rem 0; font-size: .85rem; color: #aeb4c0; border-bottom: 1px dashed rgba(255,255,255,.04); }
    .stats-item:last-child { border-bottom: 0; }
    .stats-item span:last-child { color: #fff; font-weight: 600; text-align: right; }
    .stats-item small { color: var(--muted); font-weight: 500; margin-left: .3rem; }
    .stats-note { margin-top: 1rem; font-size: .75rem; color: var(--muted); text-align: center; }

    /* PvP */
    .pvp-section { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem; }
    .pvp-card { background: var(--card); border: 1px solid rgba(201,162,39,.12); padding: 1.1rem; }
    .pvp-card-header { display: flex; align-items: center; justify-content: space-between; gap: .75rem; margin-bottom: .75rem; padding-bottom: .7rem; border-bottom: 1px solid var(--gold-line); }
    .pvp-card-title { font-family: 'Cinzel', serif; font-weight: 700; font-size: 1.05rem; color: var(--gold); }
    .pvp-card-rating { padding: .2rem .7rem; border: 1px solid rgba(201,162,39,.3); background: rgba(242,207,82,.08); font-weight: 700; font-size: .9rem; color: var(--gold); white-space: nowrap; }
    .pvp-team-icon { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--gold); background: rgba(201,162,39,.1); border: 1px solid var(--gold-line); flex-shrink: 0; }
    .pvp-members-grid { display: flex; flex-wrap: wrap; gap: .5rem; }
    .pvp-member { display: flex; align-items: center; gap: .4rem; padding: .3rem .7rem; font-size: .85rem; color: #d1d5db; background: rgba(0,0,0,.3); border: 1px solid rgba(201,162,39,.08); }
    .pvp-member img { width: 18px; height: 18px; border-radius: 50%; flex-shrink: 0; }
    .pvp-member.current-player { border-color: var(--gold); background: rgba(242,207,82,.1); }
    .pvp-member-link { text-decoration: none; color: inherit; }
    .pvp-member-link:hover .pvp-member { border-color: rgba(242,207,82,.4); background: rgba(242,207,82,.08); }
    .pvp-empty { text-align: center; padding: 2.5rem 1rem; color: var(--muted); }
    .pvp-empty i { font-size: 2.5rem; color: rgba(201,162,39,.25); display: block; margin-bottom: .75rem; }
    .pvp-kills-container { margin-top: 1rem; padding: 1.1rem; text-align: center; background: var(--card); border: 1px solid var(--gold-line); }
    .kills-number { display: block; font-family: 'Cinzel', serif; font-size: 2.4rem; font-weight: 900; color: var(--gold); line-height: 1.2; }
    .kills-label { color: var(--muted); font-size: .85rem; }

    /* Tooltip */
    .item-tooltip {
        display: none; position: fixed; z-index: 1000; max-width: 350px; padding: 1rem; pointer-events: none; font-size: .8rem; color: #d1d5db;
        background: rgba(5,7,11,.96); backdrop-filter: blur(8px); border: 1px solid rgba(201,162,39,.35); box-shadow: 0 20px 40px -12px rgba(0,0,0,.8);
    }
    .item-tooltip .item-name { font-size: .9rem; font-weight: 700; margin-bottom: .25rem; }
    .item-tooltip .item-stats { margin-top: .25rem; border-top: 1px solid rgba(201,162,39,.1); padding-top: .25rem; }

    @media (max-width: 768px) {
        body { padding-top: 96px; }
        .panel { padding: 1.25rem .75rem !important; }
        .character-image { height: 320px; }
        .tab-btn { flex: 1; padding: .6rem .5rem; font-size: .82rem; }
    }
    /* ===== Round 2 additions ===== */
    .slot { min-width: 0; text-decoration: none; color: inherit; }
    a.slot:focus-visible { outline: 2px solid var(--gold); outline-offset: 2px; }
    .tabs { overflow-x: auto; scrollbar-width: none; }
    .tabs::-webkit-scrollbar { display: none; }
    .tab-content:focus-visible { outline: 1px dashed var(--gold-line); outline-offset: 4px; }

    .copy-link {
        margin-top: .85rem; display: inline-flex; align-items: center; gap: .5rem; padding: .35rem .9rem; font-size: .8rem; font-weight: 600;
        color: var(--muted); background: transparent; border: 1px solid var(--gold-line); border-radius: 999px; cursor: pointer; transition: color .2s, border-color .2s;
    }
    .copy-link:hover { color: var(--gold); border-color: rgba(242,207,82,.45); }
    .copy-link:focus-visible { outline: 2px solid var(--gold); outline-offset: 2px; }
    .notice {
        display: flex; align-items: center; gap: .6rem; margin: 0 0 1rem; padding: .6rem .9rem; font-size: .85rem; color: #fcd34d;
        background: rgba(245,158,11,.08); border: 1px solid rgba(245,158,11,.3);
    }

    /* 3D model toggle button */
    .toggle-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: .55rem; width: 100%; padding: .7rem 1rem;
        font-weight: 700; font-size: .9rem; letter-spacing: .04em; color: var(--gold); cursor: pointer;
        background: var(--card); border: 1px solid var(--gold-line); transition: background .2s, color .2s, border-color .2s;
    }
    .toggle-btn:hover:not(:disabled) { border-color: rgba(242,207,82,.5); background: rgba(242,207,82,.08); }
    .toggle-btn[aria-pressed="true"] { color: #17130a; border-color: var(--gold-dim); background: linear-gradient(180deg, #f2cf5b, #a77d19); }
    .toggle-btn:disabled { cursor: default; opacity: .7; }
    .toggle-btn:focus-visible { outline: 2px solid var(--gold); outline-offset: 2px; }
    .toggle-hint { font-size: .7rem; color: var(--muted); text-align: center; }
    .toggle-hint i { color: var(--gold); margin-right: .25rem; }

    /* Arena */
    .pvp-section { grid-template-columns: repeat(auto-fit, minmax(min(300px, 100%), 1fr)); }
    .pvp-card { min-width: 0; }
    .pvp-card-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .4rem; min-height: 140px; text-align: center; border-style: dashed; color: var(--muted); }
    .pvp-card-empty i { font-size: 1.5rem; color: rgba(201,162,39,.3); }
    .pvp-card-empty b { color: #9ca3af; font-family: 'Cinzel', serif; }
    .pvp-card-empty span { font-size: .8rem; }
    .pvp-meta { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem .9rem; margin-bottom: .8rem; font-size: .75rem; color: var(--muted); }
    .pvp-meta .rec-w { color: #4ade80; font-weight: 700; } .pvp-meta .rec-l { color: #f87171; font-weight: 700; }
    .pvp-member { min-width: 0; max-width: 100%; }
    .pvp-member .m-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pvp-member .m-rating { margin-left: .2rem; padding-left: .5rem; border-left: 1px solid var(--gold-line); font-size: .75rem; font-weight: 700; color: var(--gold); }
    .pvp-member.current-player { box-shadow: 0 0 0 1px rgba(242,207,82,.35), 0 0 14px rgba(242,207,82,.18); }
    /* Avatar sits on the bottom edge of its frame */
    .character-image .default-image { padding: 1rem 1rem 0; object-fit: contain; object-position: center bottom; }

    /* Arena member rows: fixed height, name truncates, nothing resizes */
    .pvp-members-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: .4rem; }
    .pvp-member-link { display: block; width: 100%; min-width: 0; }
    .pvp-member { display: flex; align-items: center; gap: .5rem; width: 100%; height: 38px; padding: 0 .7rem; box-sizing: border-box; overflow: hidden; }
    .pvp-member img { width: 18px; height: 18px; flex: 0 0 18px; }
    .pvp-member .m-name { flex: 1 1 auto; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pvp-member .m-rating { flex: 0 0 auto; min-width: 44px; margin-left: 0; text-align: right; }
    .pvp-member > i { flex: 0 0 14px; width: 14px; text-align: center; }
</style>
<script>
/* Image fallbacks: missing icons swap to data-fallback, otherwise are hidden (no broken-image boxes). */
document.addEventListener('error', function (e) {
    var img = e.target;
    if (!(img instanceof HTMLImageElement) || img.hasAttribute('onerror') || img.dataset.fbDone) return;
    img.dataset.fbDone = '1';
    if (img.dataset.fallback) { img.src = img.dataset.fallback; } else { img.style.display = 'none'; }
}, true);
</script>
<?php
$page_head = ob_get_clean();
require_once $project_root . 'includes/header.php';
?>

<div class="relative z-10 min-h-screen flex items-start justify-center px-4 md:px-8 py-8">
    <div class="container mx-auto max-w-7xl px-2 sm:px-4">
        <div class="panel p-6 md:p-8">

            <?php
            $slotLabels = [
                0 => translate('label_head', 'Head'), 1 => translate('label_neck', 'Neck'), 2 => translate('label_shoulders', 'Shoulders'),
                3 => translate('label_body', 'Body'), 4 => translate('label_chest', 'Chest'), 5 => translate('label_waist', 'Waist'),
                6 => translate('label_legs', 'Legs'), 7 => translate('label_feet', 'Feet'), 8 => translate('label_wrists', 'Wrists'),
                9 => translate('label_hands', 'Hands'), 10 => translate('label_finger', 'Finger'), 11 => translate('label_finger', 'Finger'),
                12 => translate('label_trinket', 'Trinket'), 13 => translate('label_trinket', 'Trinket'), 14 => translate('label_back', 'Back'),
                15 => translate('label_main_hand', 'Main Hand'), 16 => translate('label_off_hand', 'Off Hand'),
                17 => translate('label_ranged', 'Ranged'), 18 => translate('label_tabard', 'Tabard')
            ];
            $defaultIcons = [
                0 => 'head.gif', 1 => 'neck.gif', 2 => 'shoulders.gif', 3 => 'body.gif', 4 => 'chest.gif',
                5 => 'waist.gif', 6 => 'legs.gif', 7 => 'feet.gif', 8 => 'wrists.gif', 9 => 'hands.gif',
                10 => 'finger.gif', 11 => 'finger.gif', 12 => 'trinket.gif', 13 => 'trinket.gif',
                14 => 'back.gif', 15 => 'mainhand.gif', 16 => 'offhand.gif', 17 => 'ranged.gif', 18 => 'tabard.gif'
            ];
            $races = [
                1 => ['name' => translate('race_human', 'Human'), 'icon' => 'human'],
                2 => ['name' => translate('race_orc', 'Orc'), 'icon' => 'orc'],
                3 => ['name' => translate('race_dwarf', 'Dwarf'), 'icon' => 'dwarf'],
                4 => ['name' => translate('race_night_elf', 'Night Elf'), 'icon' => 'nightelf'],
                5 => ['name' => translate('race_undead', 'Undead'), 'icon' => 'undead'],
                6 => ['name' => translate('race_tauren', 'Tauren'), 'icon' => 'tauren'],
                7 => ['name' => translate('race_gnome', 'Gnome'), 'icon' => 'gnome'],
                8 => ['name' => translate('race_troll', 'Troll'), 'icon' => 'troll'],
                10 => ['name' => translate('race_blood_elf', 'Blood Elf'), 'icon' => 'bloodelf'],
                11 => ['name' => translate('race_draenei', 'Draenei'), 'icon' => 'draenei']
            ];
            $classes = [
                1 => ['name' => translate('class_warrior', 'Warrior'), 'icon' => 'warrior', 'color' => '#C69B6D'],
                2 => ['name' => translate('class_paladin', 'Paladin'), 'icon' => 'paladin', 'color' => '#F48CBA'],
                3 => ['name' => translate('class_hunter', 'Hunter'), 'icon' => 'hunter', 'color' => '#AAD372'],
                4 => ['name' => translate('class_rogue', 'Rogue'), 'icon' => 'rogue', 'color' => '#FFF468'],
                5 => ['name' => translate('class_priest', 'Priest'), 'icon' => 'priest', 'color' => '#FFFFFF'],
                6 => ['name' => translate('class_death_knight', 'Death Knight'), 'icon' => 'deathknight', 'color' => '#C41E3A'],
                7 => ['name' => translate('class_shaman', 'Shaman'), 'icon' => 'shaman', 'color' => '#0070DD'],
                8 => ['name' => translate('class_mage', 'Mage'), 'icon' => 'mage', 'color' => '#3FC7EB'],
                9 => ['name' => translate('class_warlock', 'Warlock'), 'icon' => 'warlock', 'color' => '#8788EE'],
                11 => ['name' => translate('class_druid', 'Druid'), 'icon' => 'druid', 'color' => '#FF7C0A']
            ];
            $powerTypes = [
                0 => translate('power_mana', 'Mana'), 1 => translate('power_rage', 'Rage'), 2 => translate('power_focus', 'Focus'),
                3 => translate('power_energy', 'Energy'), 4 => translate('power_happiness', 'Happiness'),
                5 => translate('power_runes', 'Runes'), 6 => translate('power_runic_power', 'Runic Power')
            ];
            $alliance = [1, 3, 4, 7, 11];
            $factionOf = function ($raceId) use ($alliance) {
                return in_array((int)$raceId, $alliance, true)
                    ? ['name' => translate('faction_alliance', 'Alliance'), 'icon' => 'alliance']
                    : ['name' => translate('faction_horde', 'Horde'), 'icon' => 'horde'];
            };
            $qualityColors = [0 => '#9d9d9d', 1 => '#ffffff', 2 => '#1eff00', 3 => '#0070dd', 4 => '#a335ee', 5 => '#ff8000', 6 => '#e5cc80', 7 => '#00ccff'];

            $guid = isset($_GET['guid']) ? (int)$_GET['guid'] : 0;
            $character = null; $items = []; $arena_by_type = []; $stats = null; $total_kills = 0; $error = ''; $warnings = [];

            // Prepared query -> all rows. Throws on any failure so each section can fail on its own.
           $query = function ($db, $sql, $types = '', $params = []) {
    // get_result() requires the MySQL Native Driver.
    if (!function_exists('mysqli_get_client_stats') || !method_exists('mysqli_result', 'fetch_all')) {
        throw new RuntimeException('mysqli mysqlnd driver is required (get_result/fetch_all unavailable).');
    }
    $stmt = $db->prepare($sql);
    if (!$stmt) { throw new RuntimeException($db->error); }
    if ($types !== '') {
        // Ref-strict bind_param: build references so PHP 8 does not warn.
        $bind = [$types];
        foreach ($params as $k => $v) { $bind[] = &$params[$k]; }
        if (!call_user_func_array([$stmt, 'bind_param'], $bind)) {
            $msg = $stmt->error; $stmt->close();
            throw new RuntimeException($msg);
        }
    }
    if (!$stmt->execute()) { $msg = $stmt->error; $stmt->close(); throw new RuntimeException($msg); }
    $res = $stmt->get_result();
    if ($res === false) { $msg = $stmt->error; $stmt->close(); throw new RuntimeException($msg); }
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
};

            if ($guid <= 0) {
                $error = translate('error_invalid_guid', 'Invalid or missing GUID parameter.');
            } elseif (!isset($char_db) || !$char_db) {
                $error = translate('error_db_connection', 'Database connection is not available.');
            } else {
                // Character: the only fatal query
                try {
                    $rows = $query($char_db, "SELECT guid, name, race, class, level, totalKills, gender FROM characters WHERE guid = ?", 'i', [$guid]);
                    if ($rows) {
                        $character = $rows[0];
                        $total_kills = (int)($character['totalKills'] ?? 0);
                    } else {
                        $error = str_replace('{guid}', $guid, translate('error_character_not_found', 'Character not found for GUID {guid}.'));
                    }
                } catch (Throwable $e) {
                    error_log('[character.php] character query: ' . $e->getMessage());
                    $error = translate('error_execute_character_query', 'Could not load this character. Please try again later.');
                }
            }

            if (!$error) {
                // Stats (optional)
                try {
                    $rows = $query($char_db, "SELECT maxhealth, maxpower1, maxpower2, maxpower3, maxpower4, maxpower5, maxpower6, maxpower7, strength, agility, stamina, intellect, spirit, armor, resHoly, resFire, resNature, resFrost, resShadow, resArcane, blockPct, dodgePct, parryPct, critPct, rangedCritPct, spellCritPct, attackPower, rangedAttackPower, spellPower, resilience FROM character_stats WHERE guid = ?", 'i', [$guid]);
                    $stats = $rows[0] ?? null;
                } catch (Throwable $e) {
                    error_log('[character.php] stats query: ' . $e->getMessage());
                    $warnings[] = translate('warn_stats_unavailable', 'Character stats are temporarily unavailable.');
                }

                // Arena teams (optional): 1 query for the teams + 1 query for ALL members
                try {
                    $teams = $query($char_db, "SELECT t.arenaTeamId, t.name, t.type, t.rating, t.seasonGames, t.seasonWins, t.`rank` AS team_rank FROM arena_team_member atm JOIN arena_team t ON atm.arenaTeamId = t.arenaTeamId WHERE atm.guid = ? ORDER BY t.type", 'i', [$guid]);
                    if ($teams) {
                        $teamIds = array_map('intval', array_column($teams, 'arenaTeamId'));
                        $ph = implode(',', array_fill(0, count($teamIds), '?'));
                        $memberRows = $query($char_db, "SELECT atm.arenaTeamId, atm.personalRating, c.guid, c.name, c.race, c.class, c.gender FROM arena_team_member atm JOIN characters c ON atm.guid = c.guid WHERE atm.arenaTeamId IN ($ph) ORDER BY atm.personalRating DESC, c.name", str_repeat('i', count($teamIds)), $teamIds);
                        $membersByTeam = [];
                        foreach ($memberRows as $m) {
                            $f = $factionOf($m['race']);
                            $m['faction'] = $f['name'];
                            $m['faction_icon'] = $f['icon'];
                            $membersByTeam[(int)$m['arenaTeamId']][] = $m;
                        }
                        foreach ($teams as $t) {
                            $t['members'] = $membersByTeam[(int)$t['arenaTeamId']] ?? [];
                            $arena_by_type[(int)$t['type']] = $t;
                        }
                    }
                } catch (Throwable $e) {
                    error_log('[character.php] arena query: ' . $e->getMessage());
                    $warnings[] = translate('warn_arena_unavailable', 'Arena data is temporarily unavailable.');
                }

                // Equipment (optional)
                try {
                    if (!isset($world_db) || !$world_db) { throw new RuntimeException('world database unavailable'); }
                    $slotRows = $query($char_db, "SELECT ci.slot, ii.itemEntry FROM character_inventory ci JOIN item_instance ii ON ci.item = ii.guid WHERE ci.guid = ? AND ci.bag = 0 AND ci.slot BETWEEN 0 AND 18", 'i', [$guid]);
                    $itemEntries = [];
                    foreach ($slotRows as $r) { $itemEntries[(int)$r['slot']] = (int)$r['itemEntry']; }
                    if ($itemEntries) {
                        $entryList = array_values(array_unique($itemEntries));
                        $ph = implode(',', array_fill(0, count($entryList), '?'));
                        $tplRows = $query($world_db, "SELECT it.entry, it.name, it.Quality, it.ItemLevel, it.RequiredLevel, it.SellPrice, it.MaxDurability, it.delay, it.bonding, it.class, it.subclass, it.InventoryType, it.dmg_min1, it.dmg_max1, it.armor, it.holy_res, it.fire_res, it.nature_res, it.frost_res, it.shadow_res, it.arcane_res, it.stat_type1, it.stat_value1, it.stat_type2, it.stat_value2, it.stat_type3, it.stat_value3, it.stat_type4, it.stat_value4, it.stat_type5, it.stat_value5, it.stat_type6, it.stat_value6, it.stat_type7, it.stat_value7, it.stat_type8, it.stat_value8, it.stat_type9, it.stat_value9, it.stat_type10, it.stat_value10, it.socketColor_1, it.socketColor_2, it.socketColor_3, it.socketBonus, it.spellid_1, it.spelltrigger_1, it.spellid_2, it.spelltrigger_2, it.spellid_3, it.spelltrigger_3, it.spellid_4, it.spelltrigger_4, it.spellid_5, it.spelltrigger_5, it.description, it.AllowableClass, it.displayid, idi.InventoryIcon_1 AS icon FROM item_template it LEFT JOIN itemdisplayinfo_dbc idi ON it.displayid = idi.ID WHERE it.entry IN ($ph)", str_repeat('i', count($entryList)), $entryList);
                        $byEntry = [];
                        foreach ($tplRows as $row) {
                            $row['icon'] = empty($row['icon']) ? 'inv_misc_questionmark' : strtolower($row['icon']);
                            $byEntry[(int)$row['entry']] = $row;
                        }
                        foreach ($itemEntries as $slotId => $entry) {
                            if (isset($byEntry[$entry])) { $items[$slotId] = $byEntry[$entry]; }
                        }
                    }
                } catch (Throwable $e) {
                    error_log('[character.php] equipment query: ' . $e->getMessage());
                    $warnings[] = translate('warn_equipment_unavailable', 'Equipment data is temporarily unavailable.');
                }
            }

            // Slot renderer. Equipped items link to their item page (Wowhead WotLK; swap the URL for your own item page if you have one).
            $renderSlot = function ($slot, $variant = '') use (&$items, $slotLabels, $defaultIcons, $qualityColors, $base_path) {
                $has = isset($items[$slot]);
                $q = $has ? ($qualityColors[(int)$items[$slot]['Quality']] ?? '#ffffff') : '#3b4252';
                $icon = $has ? $items[$slot]['icon'] : ($defaultIcons[$slot] ?? 'inv_misc_questionmark');
                $src = $has ? "https://wow.zamimg.com/images/wow/icons/large/$icon.jpg" : "{$base_path}img/characterarmor/$icon";
                $fallback = 'https://wow.zamimg.com/images/wow/icons/large/inv_misc_questionmark.jpg';
                $cls = 'slot' . ($has ? ' has-item' : '') . ($variant ? " $variant" : '');
                $label = htmlspecialchars($slotLabels[$slot]);
                if ($has) {
                    $name = htmlspecialchars($items[$slot]['name']);
                    $ilvl = (int)$items[$slot]['ItemLevel'];
                    echo '<a class="' . $cls . '" style="--q:' . $q . '" href="https://www.wowhead.com/wotlk/item=' . (int)$items[$slot]['entry'] . '" target="_blank" rel="noopener noreferrer"'
                        . ' aria-label="' . $label . ': ' . $name . ', ' . translate('ilvl_label', 'iLvl') . ' ' . $ilvl . '"'
                        . ' data-tooltip="' . htmlspecialchars(generateTooltip($items[$slot])) . '">';
                } else {
                    echo '<div class="' . $cls . '" style="--q:' . $q . '">';
                }
                echo '<div class="slot-icon"><img src="' . htmlspecialchars($src) . '" alt="" loading="lazy"' . ($has ? ' data-fallback="' . $fallback . '"' : '') . '></div>';
                if ($variant !== 'compact' || !$has) {
                    echo '<div class="slot-info"><div class="slot-name">' . $label . '</div>';
                    if ($has) {
                        echo '<div class="slot-item" style="color:' . $q . '">' . $name . '</div>';
if ($ilvl > 0) {
    echo '<div class="slot-ilvl">' . translate('ilvl_label', 'iLvl') . ' ' . $ilvl . '</div>';
}                    } else {
                        echo '<div class="empty-slot">' . translate('slot_empty', 'Empty') . '</div>';
                    }
                    echo '</div>';
                }
                echo $has ? '</a>' : '</div>';
            };
            ?>

            <?php if ($error): ?>
                <div class="text-center py-12 text-red-400">
                    <i class="fas fa-exclamation-triangle text-4xl block mb-3"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php else:
                $cls = $classes[$character['class']] ?? null;
                $race = $races[$character['race']] ?? null;
                $genderId = ((int)($character['gender'] ?? 0)) === 0 ? 0 : 1;
                $genderDir = $genderId === 0 ? 'male' : 'female';
                $classColor = $cls['color'] ?? '#f2cf5b';
                $raceIcon = $race['icon'] ?? 'unknown';
                $faction = $factionOf($character['race']);

                // Average item level (ignores shirt & tabard)
                $ilvlSum = 0; $ilvlCount = 0;
                // Average item level (ignores shirt & tabard, and items with no iLvl)
$ilvlSum = 0; $ilvlCount = 0;
foreach ($items as $s => $it) {
    if ($s === 3 || $s === 18) continue;                 // shirt & tabard
    $lvl = (int)($it['ItemLevel'] ?? 0);
    if ($lvl <= 0) continue;                             // no iLvl → don't count it
    $ilvlSum += $lvl;
    $ilvlCount++;
}
$avgIlvl = $ilvlCount ? round($ilvlSum / $ilvlCount) : 0;
                $avgIlvl = $ilvlCount ? round($ilvlSum / $ilvlCount) : 0;

                // Avatar: img/characterarmor/avatar/{raceId}-{gender}.png  (e.g. 1-0.png = Human male)
                $avatarPath = "{$base_path}img/characterarmor/avatar/{$character['race']}-{$genderId}.png";
                $avatarFallback = "{$base_path}img/accountimg/race/{$genderDir}/{$raceIcon}.png";
                $modelPath = "{$base_path}3dmodels/character/$raceIcon/$genderDir/$raceIcon.gltf";
            ?>

            <!-- Hero -->
            <div class="hero" style="--cc: <?= $classColor ?>">
                <h1 class="character-name"><?= htmlspecialchars($character['name'] ?? 'Unknown') ?></h1>
                <div class="chips">
                    <span class="chip"><i class="fas fa-arrow-up"></i><?= translate('level_label', 'Level') ?> <?= (int)$character['level'] ?></span>
                    <span class="chip" style="color:<?= $classColor ?>">
                        <img src="<?= htmlspecialchars("{$base_path}img/accountimg/class/" . ($cls['icon'] ?? 'unknown') . ".webp") ?>" alt="">
                        <?= htmlspecialchars($cls['name'] ?? translate('class_unknown', 'Unknown')) ?>
                    </span>
                    <span class="chip">
                        <img src="<?= htmlspecialchars($avatarFallback) ?>" alt="">
                        <?= htmlspecialchars($race['name'] ?? translate('race_unknown', 'Unknown')) ?>
                    </span>
                    <span class="chip">
                        <img src="<?= htmlspecialchars("{$base_path}img/accountimg/faction/{$faction['icon']}.png") ?>" alt="">
                        <?= htmlspecialchars($faction['name']) ?>
                    </span>
                    <?php if ($avgIlvl): ?>
                        <span class="chip ilvl" title="<?= htmlspecialchars(translate('avg_ilvl_hint', 'Average item level of equipped items (shirt and tabard excluded). Not the in-game number.')) ?>">
                            <i class="fas fa-shield-alt"></i><?= translate('avg_ilvl', 'Equipped avg. iLvl') ?> ~<?= $avgIlvl ?>
                        </span>
                    <?php endif; ?>
                </div>
                <button type="button" class="copy-link" id="copy-link"
                        data-copied="<?= htmlspecialchars(translate('link_copied', 'Link copied')) ?>"
                        data-failed="<?= htmlspecialchars(translate('link_copy_failed', 'Copy failed - use the address bar')) ?>">
                    <i class="fas fa-link"></i><span><?= translate('copy_profile_link', 'Copy profile link') ?></span>
                </button>
            </div>

            <?php foreach ($warnings as $w): ?>
                <div class="notice" role="status"><i class="fas fa-info-circle"></i><?= htmlspecialchars($w) ?></div>
            <?php endforeach; ?>

            <!-- Paper doll -->
            <div class="paper-doll">
                <div class="gear-col">
                    <?php foreach ([0, 1, 2, 14, 4, 3, 18, 8] as $slot) $renderSlot($slot); ?>
                </div>

                <div class="center-col">
                    <div class="character-image" id="character-viewer" style="--cc: <?= $classColor ?>">
                        <img src="<?= htmlspecialchars($avatarPath) ?>"
                             alt="<?= htmlspecialchars(($race['name'] ?? 'Character') . ' avatar') ?>"
                             class="default-image"
                             onerror="this.onerror=function(){this.onerror=null;this.src='<?= htmlspecialchars($base_path . '3dmodels/3d_default.gif', ENT_QUOTES) ?>';};this.src='<?= htmlspecialchars($avatarFallback, ENT_QUOTES) ?>';">
                        <div class="model-loading" id="model-loading" role="status" aria-live="polite" hidden>
                            <i class="fas fa-circle-notch fa-spin"></i> <span><?= translate('model_loading', 'Loading 3D character…') ?></span>
                        </div>
                        <div class="model-error" id="model-error" role="alert" hidden><?= translate('model_error', 'Unable to load this 3D model. Check Your 3D Files or Please try again.') ?></div>
                    </div>

                    <button type="button" class="toggle-btn" id="model-toggle" aria-pressed="false" aria-label="<?= htmlspecialchars(translate('model_show_aria', 'Show 3D model')) ?>">
                        <i class="fa-solid fa-eye"></i><span><?= translate('model_show', 'Show') ?></span>
                        
                    </button>
                    <p class="toggle-hint" id="toggle-hint" hidden><i class="fas fa-arrows-alt"></i><?= translate('model_hint', 'Drag to rotate. Shows the race and gender model, not equipped gear.') ?></p>

                    <div class="weapons">
                        <?php foreach ([15, 16, 17] as $slot) $renderSlot($slot, 'compact'); ?>
                    </div>
                </div>

                <div class="gear-col">
                    <?php foreach ([9, 5, 6, 7, 10, 11, 12, 13] as $slot) $renderSlot($slot, 'right'); ?>
                </div>
            </div>

            <script type="importmap">
                { "imports": { "three": "https://esm.sh/three@0.167.1", "three/addons/": "https://esm.sh/three@0.167.1/examples/jsm/" } }
            </script>
            <script type="module">
                import * as THREE from 'three';
                import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
                import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';

                const container = document.getElementById('character-viewer');
                const toggle = document.getElementById('model-toggle');
                const loading = document.getElementById('model-loading');
                const errorMessage = document.getElementById('model-error');
                const hint = document.getElementById('toggle-hint');
                const defaultImage = container.querySelector('.default-image');
                const modelPath = <?= json_encode($modelPath) ?>;

                // Translated UI strings (i18n via PHP)
                const T = {
                    show: <?= json_encode(translate('model_show', 'Show')) ?>,
                    hide: <?= json_encode(translate('model_hide', 'Hide')) ?>,
                    showAria: <?= json_encode(translate('model_show_aria', 'Show 3D model')) ?>,
                    hideAria: <?= json_encode(translate('model_hide_aria', 'Hide 3D model')) ?>,
                    canvasAria: <?= json_encode(translate('model_canvas_aria', 'Interactive 3D character model')) ?>
                };

                // One lifecycle for everything three.js creates, so retries never stack listeners or loops.
                let renderer = null, scene = null, camera = null, controls = null, mixer = null, clock = null;
                let frame = null, resizeObserver = null, modelReady = false, busy = false, errorTimer = null;

                function setUI(on) {
                    toggle.setAttribute('aria-pressed', on ? 'true' : 'false');
                    hint.hidden = !(on && modelReady);
                }

                function stopLoop() {
                    if (frame !== null) { cancelAnimationFrame(frame); frame = null; }
                }

                function startLoop() {
                    if (frame !== null || !renderer) return;
                    clock.getDelta(); // discard time spent paused so animations don't jump
                    const tick = () => {
                        frame = requestAnimationFrame(tick);
                        controls.update();
                        if (mixer) mixer.update(clock.getDelta());
                        renderer.render(scene, camera);
                    };
                    tick();
                }

                function resize() {
    if (!renderer) return;
    const w = container.clientWidth, h = container.clientHeight;
    if (!w || !h) return;
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 3));
    camera.aspect = w / h;
    camera.updateProjectionMatrix();
    renderer.setSize(w, h, false);
    renderer.domElement.style.width = '100%';
    renderer.domElement.style.height = '100%';
}

   function teardown() {
    stopLoop();
    if (resizeObserver) { resizeObserver.disconnect(); resizeObserver = null; }
    if (controls) { controls.dispose(); controls = null; }
    if (renderer) { renderer.dispose(); renderer.domElement.remove(); renderer = null; }
    scene = camera = mixer = clock = null;
    modelReady = false;
    // Reset UI so a bfcache restore never shows a stale "Hide" button
    if (defaultImage) defaultImage.style.display = 'block';
    if (hint) hint.hidden = true;
    if (toggle) {
        toggle.setAttribute('aria-pressed', 'false');
        toggle.setAttribute('aria-label', T.showAria);
        const s = toggle.querySelector('span');
        if (s) s.textContent = T.show;
    }
}

                function setup() {
                    const w = container.clientWidth, h = container.clientHeight;
                    scene = new THREE.Scene();
                    camera = new THREE.PerspectiveCamera(35, w / h, 0.1, 1000);
renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance' });                    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 3));
                    renderer.setSize(w, h);
                    renderer.outputColorSpace = THREE.SRGBColorSpace;
                    renderer.domElement.setAttribute('aria-label', T.canvasAria);
                    renderer.domElement.style.display = 'none';
                    container.appendChild(renderer.domElement);

                    scene.add(new THREE.AmbientLight(0xffffff, 1.5));
                    const light = new THREE.DirectionalLight(0xffffff, 2);
                    light.position.set(5, 8, 6);
                    scene.add(light);

                    controls = new OrbitControls(camera, renderer.domElement);
                    controls.enableDamping = true;
                    clock = new THREE.Clock();

                    resizeObserver = new ResizeObserver(resize); // the only resize handler
                    resizeObserver.observe(container);
                }

                function frameModel(model) {
                    const box = new THREE.Box3().setFromObject(model);
                    const center = box.getCenter(new THREE.Vector3());
                    const size = box.getSize(new THREE.Vector3());
                    const max = Math.max(size.x, size.y, size.z) || 1;
                    const dist = max / (2 * Math.tan(THREE.MathUtils.degToRad(camera.fov / 2)));
                    camera.position.set(center.x + dist * 0.15, center.y + size.y * 0.08, center.z + dist * 1.8);
                    camera.near = Math.max(max / 1000, 0.01);
                    camera.far = max * 100;
                    camera.updateProjectionMatrix();
                    controls.target.copy(center);
                    // Keep the wheel from zooming the model too far in or out; no panning away either
                    const d = camera.position.distanceTo(center);
                    controls.enablePan = false;
                    controls.zoomSpeed = 0.5;
                    controls.minDistance = d * 0.75;
                    controls.maxDistance = d * 1.25;
                    controls.update();
                }
function show3D() {
    defaultImage.style.display = 'none';
    renderer.domElement.style.display = 'block';
    setUI(true);
    resize();
    renderer.render(scene, camera);
    startLoop();

    toggle.setAttribute('aria-pressed', 'true');
    toggle.setAttribute('aria-label', T.hideAria);
    toggle.querySelector('span').textContent = T.hide;
}

function show2D() {
    stopLoop(); // no rendering while hidden
    if (renderer) renderer.domElement.style.display = 'none';
    defaultImage.style.display = 'block';
    setUI(false);

    toggle.setAttribute('aria-pressed', 'false');
    toggle.setAttribute('aria-label', T.showAria);
    toggle.querySelector('span').textContent = T.show;
}
                async function enable3D() {
                    if (busy) return;
                    if (modelReady) { show3D(); return; }
                    busy = true;
                    toggle.disabled = true;
                    clearTimeout(errorTimer);
                    errorMessage.hidden = true;
                    loading.hidden = false;
                    try {
                        setup();
                        const gltf = await new Promise((res, rej) => new GLTFLoader().load(modelPath, res, undefined, rej));
                        scene.add(gltf.scene);
                        frameModel(gltf.scene);
                        if (gltf.animations?.length) {
                            mixer = new THREE.AnimationMixer(gltf.scene);
                            mixer.clipAction(gltf.animations[0]).play();
                        }
                        modelReady = true;
                        show3D();
                    } catch (err) {
                        console.error('3D viewer failed:', err);
                        teardown(); // full cleanup so the next attempt starts clean
                        defaultImage.style.display = 'block';
                        setUI(false);
                        errorMessage.hidden = false;
                        errorTimer = setTimeout(() => { errorMessage.hidden = true; }, 5000);
                    } finally {
                        busy = false;
                        toggle.disabled = false;
                        loading.hidden = true;
                    }
                }

               toggle.addEventListener('click', () => {
    if (busy) return;
    if (toggle.getAttribute('aria-pressed') === 'true') show2D();
    else enable3D();
});
                document.addEventListener('visibilitychange', () => {
                    if (!modelReady) return;
                    if (document.hidden) stopLoop(); else if (toggle.getAttribute('aria-pressed') === 'true') startLoop();
                });
                // bfcache: pagehide fires on navigate away, pageshow restores it.
// teardown() resets UI; on restore we simply start from a clean 2D state.
window.addEventListener('pagehide', teardown);
window.addEventListener('pageshow', (e) => {
    if (e.persisted) teardown();
});
            </script>

            <!-- Tabs -->
            <div class="tabs" role="tablist" aria-label="<?= htmlspecialchars(translate('tabs_label', 'Character details')) ?>">
                <button type="button" id="tab-stats" data-tab="stats-tab" class="tab-btn active" role="tab" aria-selected="true" aria-controls="stats-tab" tabindex="0"><i class="fas fa-chart-bar mr-2"></i><?= translate('tab_stats', 'Stats') ?></button>
                <button type="button" id="tab-talents" data-tab="talents-tab" class="tab-btn" role="tab" aria-selected="false" aria-controls="talents-tab" tabindex="-1"><i class="fas fa-star mr-2"></i><?= translate('tab_talents', 'Talents') ?></button>
                <button type="button" id="tab-pvp" data-tab="pvp-tab" class="tab-btn" role="tab" aria-selected="false" aria-controls="pvp-tab" tabindex="-1"><i class="fas fa-crosshairs mr-2"></i><?= translate('tab_pvp', 'PVP') ?></button>
            </div>

            <!-- Stats Tab -->
            <div id="stats-tab" class="tab-content active mt-6" role="tabpanel" aria-labelledby="tab-stats" tabindex="0">
                <?php if ($stats):
                    // Primary resource per class. FIX: hunters use Mana in WotLK (Focus is pet-only).
                    $classPowerMap = [1 => 1, 2 => 0, 3 => 0, 4 => 3, 5 => 0, 6 => 6, 7 => 0, 8 => 0, 9 => 0, 11 => 0];
                    $powerIndex = $classPowerMap[$character['class']] ?? 0;
                    $powerRaw = (float)($stats['maxpower' . ($powerIndex + 1)] ?? 0);
                    // Rage and Runic Power are stored x10
                    $powerValue = in_array($powerIndex, [1, 6], true) ? $powerRaw / 10 : $powerRaw;
                    $powerClass = [1 => 'rage', 3 => 'energy', 6 => 'runic'][$powerIndex] ?? '';

                    // Gear ratings from item_template.
                    // FIX: real 3.3.5 ITEM_MOD ids -> 31 Hit, 32 Crit, 36 Haste, 37 Expertise, 44 Armor Pen,
                    // 43 MP5, 47 Spell Pen, 41/42 old bonus healing/damage. (30 = spell haste, 49 does not exist)
                    $g = ['hit' => 0, 'crit' => 0, 'haste' => 0, 'expertise' => 0, 'armorPen' => 0, 'spellPen' => 0, 'mp5' => 0, 'bonusHeal' => 0, 'bonusSpell' => 0];
// WotLK 3.3.5 ITEM_MOD_* ids (see SharedDefines.h):
//   31 Hit, 32 Crit, 36 Haste, 37 Expertise, 44 Armor Pen, 47 Spell Pen, 43 MP5,
//   41 Bonus Healing, 42 Bonus Spell Damage.
//   16-18 / 19-21 / 28-30 are the legacy melee/ranged/spell split ratings and still
//   appear on some pre-WotLK items. They map to the same unified buckets.
$map = [
    16 => 'hit', 17 => 'hit', 18 => 'hit', 31 => 'hit',
    19 => 'crit', 20 => 'crit', 21 => 'crit', 32 => 'crit',
    28 => 'haste', 29 => 'haste', 30 => 'haste', 36 => 'haste',
    37 => 'expertise', 44 => 'armorPen', 47 => 'spellPen', 43 => 'mp5',
    41 => 'bonusHeal', 42 => 'bonusSpell',
];
                    foreach ($items as $item) {
                        for ($i = 1; $i <= 10; $i++) {
                            $t = (int)($item["stat_type$i"] ?? 0);
                            $v = (int)($item["stat_value$i"] ?? 0);
                            if ($v > 0 && isset($map[$t])) { $g[$map[$t]] += $v; }
                        }
                    }
                    // Rating -> % conversions only valid at level 80
                    $is80 = (int)$character['level'] === 80;
                    $pct = function ($rating, $per) use ($is80) { return $is80 ? ' <small>(≈' . number_format($rating / $per, 2) . '%)</small>' : ''; };
                    $hasGear = array_sum($g) > 0;

                    $row = function ($label, $value) { echo '<div class="stats-item"><span>' . $label . '</span><span>' . $value . '</span></div>'; };
                ?>
                    <div class="vitals">
                        <div class="vital hp"><b><?= number_format($stats['maxhealth']) ?></b><span><?= translate('stat_health', 'Health') ?></span></div>
                        <?php if ($powerValue > 0): ?>
                            <div class="vital pw <?= $powerClass ?>"><b><?= number_format($powerValue) ?></b><span><?= htmlspecialchars($powerTypes[$powerIndex]) ?></span></div>
                        <?php endif; ?>
                        <div class="vital"><b><?= number_format($stats['armor']) ?></b><span><?= translate('stat_armor', 'Armor') ?></span></div>
                        <div class="vital"><b><?= number_format($stats['attackPower']) ?></b><span><?= translate('stat_attack_power', 'Attack Power') ?></span></div>
                        <div class="vital"><b><?= number_format($stats['spellPower']) ?></b><span><?= translate('stat_spell_power', 'Spell Power') ?></span></div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="stats-category">
                            <h3><i class="fas fa-heart"></i><?= translate('stats_base', 'Base Stats') ?></h3>
                            <?php
                            $row(translate('stat_strength', 'Strength'), number_format($stats['strength']));
                            $row(translate('stat_agility', 'Agility'), number_format($stats['agility']));
                            $row(translate('stat_stamina', 'Stamina'), number_format($stats['stamina']));
                            $row(translate('stat_intellect', 'Intellect'), number_format($stats['intellect']));
                            $row(translate('stat_spirit', 'Spirit'), number_format($stats['spirit']));
                            if ($stats['maxpower1'] > 0 && $powerIndex !== 0) { $row(translate('stat_mana', 'Mana'), number_format($stats['maxpower1'])); }
                            ?>
                        </div>

                        <div class="stats-category">
                            <h3><i class="fas fa-shield-alt"></i><?= translate('stats_defense', 'Defense') ?></h3>
                            <?php
                            $row(translate('stat_armor', 'Armor'), number_format($stats['armor']));
                            $row(translate('stat_block', 'Block'), number_format($stats['blockPct'], 2) . '%');
                            $row(translate('stat_dodge', 'Dodge'), number_format($stats['dodgePct'], 2) . '%');
                            $row(translate('stat_parry', 'Parry'), number_format($stats['parryPct'], 2) . '%');
                            $row(translate('stat_resilience', 'Resilience'), number_format($stats['resilience']));
                            ?>
                        </div>

                        <div class="stats-category">
                            <h3><i class="fas fa-fist-raised"></i><?= translate('stats_melee', 'Melee') ?></h3>
                            <?php
                            $row(translate('stat_attack_power', 'Attack Power'), number_format($stats['attackPower']));
                            $row(translate('stat_crit_chance', 'Crit Chance'), number_format($stats['critPct'], 2) . '%');
                            ?>
                        </div>

                        <div class="stats-category">
                            <h3><i class="fas fa-bullseye"></i><?= translate('stats_ranged', 'Ranged') ?></h3>
                            <?php
                            $row(translate('stat_ranged_attack_power', 'Attack Power'), number_format($stats['rangedAttackPower']));
                            $row(translate('stat_ranged_crit_chance', 'Crit Chance'), number_format($stats['rangedCritPct'], 2) . '%');
                            ?>
                        </div>

                        <div class="stats-category">
                            <h3><i class="fas fa-magic"></i><?= translate('stats_spell', 'Spell') ?></h3>
                            <?php
                            $row(translate('stat_spell_power', 'Spell Power'), number_format($stats['spellPower']));
                            $row(translate('stat_spell_crit_chance', 'Spell Crit'), number_format($stats['spellCritPct'], 2) . '%');
                            ?>
                        </div>

                        <div class="stats-category">
                            <h3><i class="fas fa-fire"></i><?= translate('stats_resistances', 'Resistances') ?></h3>
                            <?php
                            $row(translate('stat_holy_resistance', 'Holy'), number_format($stats['resHoly']));
                            $row(translate('stat_fire_resistance', 'Fire'), number_format($stats['resFire']));
                            $row(translate('stat_nature_resistance', 'Nature'), number_format($stats['resNature']));
                            $row(translate('stat_frost_resistance', 'Frost'), number_format($stats['resFrost']));
                            $row(translate('stat_shadow_resistance', 'Shadow'), number_format($stats['resShadow']));
                            $row(translate('stat_arcane_resistance', 'Arcane'), number_format($stats['resArcane']));
                            ?>
                        </div>

                        <?php if ($hasGear): ?>
                        <div class="stats-category sm:col-span-2 lg:col-span-3">
                            <h3><i class="fas fa-gem"></i><?= translate('stats_gear_bonus', 'Gear Ratings (base item stats)') ?></h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8">
                                <?php
                                if ($g['hit'] > 0)        $row(translate('stat_hit', 'Hit Rating'), number_format($g['hit']) . $pct($g['hit'], 32.79));
                                if ($g['crit'] > 0)       $row(translate('stat_crit_rating', 'Crit Rating'), number_format($g['crit']) . $pct($g['crit'], 45.91));
                                if ($g['haste'] > 0)      $row(translate('stat_haste', 'Haste Rating'), number_format($g['haste']) . $pct($g['haste'], 32.79));
                                if ($g['expertise'] > 0)  $row(translate('stat_expertise', 'Expertise Rating'), number_format($g['expertise']));
                                if ($g['armorPen'] > 0)   $row(translate('stat_armor_pen', 'Armor Penetration'), number_format($g['armorPen']) . $pct($g['armorPen'], 13.99));
                                if ($g['spellPen'] > 0)   $row(translate('stat_spell_pen', 'Spell Penetration'), number_format($g['spellPen']));
                                if ($g['mp5'] > 0)        $row(translate('stat_mp5', 'Mana per 5 sec'), number_format($g['mp5']));
                                if ($g['bonusHeal'] > 0)  $row(translate('stat_bonus_healing', 'Bonus Healing'), number_format($g['bonusHeal']));
                                if ($g['bonusSpell'] > 0) $row(translate('stat_bonus_spell_damage', 'Bonus Spell Damage'), number_format($g['bonusSpell']));
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <p class="stats-note"><?= translate('stats_note', 'Main stats come from the last saved character state (updated on logout). Gear ratings add up base item stats only: gems, enchants, buffs and talents are not included, and % values are estimates for level 80.') ?></p>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-400"><?= translate('stats_none', 'No Stats Available') ?></div>
                <?php endif; ?>
            </div>

            <!-- Talents Tab -->
            <div id="talents-tab" class="tab-content mt-6" role="tabpanel" aria-labelledby="tab-talents" tabindex="0">
                <div class="text-center py-12 text-gray-400">
                    <i class="fas fa-star text-3xl block mb-3 text-[rgba(201,162,39,0.3)]"></i>
                    <?= translate('talents_coming_soon', 'Talents (Coming Soon)') ?>
                </div>
            </div>

            <!-- PVP Tab -->
            <div id="pvp-tab" class="tab-content mt-6" role="tabpanel" aria-labelledby="tab-pvp" tabindex="0">
                <div class="pvp-section">
                    <?php foreach ([2 => '2v2', 3 => '3v3', 5 => '5v5'] as $type => $bracket):
                        $team = $arena_by_type[$type] ?? null;
                        if (!$team): ?>
                            <div class="pvp-card pvp-card-empty">
                                <i class="fas fa-users-slash"></i>
                                <b><?= $bracket ?></b>
                                <span><?= str_replace('{bracket}', $bracket, translate('pvp_no_team_bracket', 'Not in a {bracket} arena team')) ?></span>
                            </div>
                        <?php else:
                            $games = (int)$team['seasonGames'];
                            $wins = min((int)$team['seasonWins'], $games);
                            $losses = $games - $wins;
                            $winPct = $games > 0 ? round($wins / $games * 100) : 0;
                            $teamIcon = $type == 2 ? 'fa-people-arrows' : ($type == 3 ? 'fa-users' : 'fa-users-cog');
                        ?>
                            <div class="pvp-card">
                                <div class="pvp-card-header">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <div class="pvp-team-icon"><i class="fas <?= $teamIcon ?>"></i></div>
                                        <span class="pvp-card-title truncate"><?= htmlspecialchars($team['name']) ?></span>
                                    </div>
                                    <span class="pvp-card-rating" title="<?= htmlspecialchars(translate('pvp_team_rating', 'Team rating')) ?>"><i class="fas fa-star text-xs mr-1"></i><?= (int)$team['rating'] ?></span>
                                </div>
                                <div class="pvp-meta">
                                    <span><i class="fas fa-tag mr-1"></i><?= $bracket ?></span>
                                    <span><i class="fas fa-users mr-1"></i><?= count($team['members']) ?></span>
                                    <?php if ((int)$team['team_rank'] > 0): ?>
                                        <span><i class="fas fa-trophy mr-1"></i><?= translate('pvp_rank', 'Rank') ?> #<?= (int)$team['team_rank'] ?></span>
                                    <?php endif; ?>
                                    <?php if ($games > 0): ?>
                                        <span title="<?= htmlspecialchars(translate('pvp_season_record', 'Season record')) ?>"><span class="rec-w"><?= $wins ?>W</span> <span class="rec-l"><?= $losses ?>L</span> · <?= $winPct ?>%</span>
                                    <?php else: ?>
                                        <span><?= translate('pvp_no_games', 'No games this season') ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="pvp-members-grid">
                                    <?php foreach ($team['members'] as $member):
                                        $name = htmlspecialchars($member['name']);
                                        $faction_n = htmlspecialchars($member['faction']);
                                        $mRace = $races[$member['race']] ?? null;
                                        $mClass = $classes[$member['class']] ?? null;
                                        $raceN = htmlspecialchars($mRace['name'] ?? translate('race_unknown', 'Unknown'));
                                        $classN = htmlspecialchars($mClass['name'] ?? translate('class_unknown', 'Unknown'));
                                        $fIcon = "{$base_path}img/accountimg/faction/{$member['faction_icon']}.png";
                                        $rIcon = "{$base_path}img/accountimg/race/" . (($member['gender'] ?? 0) == 0 ? 'male' : 'female') . '/' . ($mRace['icon'] ?? 'unknown') . '.png';
                                        $cIcon = "{$base_path}img/accountimg/class/" . ($mClass['icon'] ?? 'unknown') . '.webp';
                                        $cColor = $mClass['color'] ?? '#d1d5db';
                                        $inner = '<img src="' . $fIcon . '" alt="' . $faction_n . '"><img src="' . $rIcon . '" alt="' . $raceN . '"><img src="' . $cIcon . '" alt="' . $classN . '">'
                                               . '<span class="m-name" style="color:' . $cColor . ';font-weight:500">' . $name . '</span>'
                                               . '<span class="m-rating" title="' . htmlspecialchars(translate('pvp_personal_rating', 'Personal rating')) . '">' . (int)$member['personalRating'] . '</span>';
                                    ?>
                                        <?php if ($member['guid'] == $character['guid']): ?>
                                            <div class="pvp-member current-player" aria-current="true" title="<?= htmlspecialchars(translate('pvp_this_character', 'This character')) ?>"><?= $inner ?><i class="fas fa-star text-xs text-[#f2cf5b]"></i></div>
                                        <?php else: ?>
                                            <a href="<?= $base_path ?>character?guid=<?= (int)$member['guid'] ?>" class="pvp-member-link" style="min-width:0;max-width:100%">
                                                <div class="pvp-member"><?= $inner ?><i class="fas fa-external-link-alt text-[#f2cf5b] text-xs opacity-40"></i></div>
                                            </a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <div class="pvp-kills-container">
                    <span class="kills-number"><?= number_format($total_kills) ?></span>
                    <span class="kills-label"><i class="fas fa-skull mr-2"></i><?= translate('pvp_total_kills', 'Total PvP Kills') ?></span>
                </div>
            </div>

            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Catch images that already failed before the fallback handler could act
    document.querySelectorAll('img').forEach(img => {
        if (img.complete && img.naturalWidth === 0 && img.getAttribute('src')) img.dispatchEvent(new Event('error'));
    });

    // Tooltip
    const tooltip = document.createElement('div');
    tooltip.className = 'item-tooltip';
    document.body.appendChild(tooltip);
    function placeTooltip(e) {
        tooltip.style.left = `${e.clientX + 14}px`;
        tooltip.style.top = `${e.clientY + 14}px`;
        const r = tooltip.getBoundingClientRect();
        if (r.right > window.innerWidth) tooltip.style.left = `${window.innerWidth - r.width - 10}px`;
        if (r.bottom > window.innerHeight) tooltip.style.top = `${window.innerHeight - r.height - 10}px`;
    }
    document.querySelectorAll('.slot.has-item').forEach(slot => {
        slot.addEventListener('mouseenter', e => {
            if (!slot.dataset.tooltip) return;
            tooltip.innerHTML = slot.dataset.tooltip;
            tooltip.style.display = 'block';
            placeTooltip(e);
        });
        slot.addEventListener('mousemove', placeTooltip);
        slot.addEventListener('mouseleave', () => { tooltip.style.display = 'none'; });
    });

    // Tabs: ARIA, arrow/Home/End keys, #stats / #talents / #pvp deep links
    const tabs = [...document.querySelectorAll('.tab-btn')];
    const panes = [...document.querySelectorAll('.tab-content')];
    function activate(tab, focus) {
        tabs.forEach(t => {
            const on = t === tab;
            t.classList.toggle('active', on);
            t.setAttribute('aria-selected', on ? 'true' : 'false');
            t.tabIndex = on ? 0 : -1;
        });
        panes.forEach(p => p.classList.toggle('active', p.id === tab.dataset.tab));
        if (focus) tab.focus();
        if (history.replaceState) history.replaceState(null, '', '#' + tab.dataset.tab.replace('-tab', ''));
    }
    tabs.forEach((tab, i) => {
        tab.addEventListener('click', () => activate(tab, false));
        tab.addEventListener('keydown', e => {
            let next = null;
            if (e.key === 'ArrowRight') next = tabs[(i + 1) % tabs.length];
            else if (e.key === 'ArrowLeft') next = tabs[(i - 1 + tabs.length) % tabs.length];
            else if (e.key === 'Home') next = tabs[0];
            else if (e.key === 'End') next = tabs[tabs.length - 1];
            if (next) { e.preventDefault(); activate(next, true); }
        });
    });
    const fromHash = tabs.find(t => t.dataset.tab === location.hash.slice(1) + '-tab');
    if (fromHash) activate(fromHash, false);

    // Copy profile link
    const copyBtn = document.getElementById('copy-link');
    if (copyBtn) {
        const label = copyBtn.querySelector('span');
        const original = label.textContent;
        copyBtn.addEventListener('click', async () => {
            let ok = false;
            try { await navigator.clipboard.writeText(location.href); ok = true; }
            catch (_) {
                const ta = document.createElement('textarea');
                ta.value = location.href; ta.style.cssText = 'position:fixed;opacity:0';
                document.body.appendChild(ta); ta.select();
                try { ok = document.execCommand('copy'); } catch (__) {}
                ta.remove();
            }
            label.textContent = ok ? copyBtn.dataset.copied : copyBtn.dataset.failed;
            setTimeout(() => { label.textContent = original; }, 2200);
        });
    }
});
</script>

<?php include_once $project_root . 'includes/footer.php'; ?>
