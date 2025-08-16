<?php
define('ALLOWED_ACCESS', true);
include '../includes/session.php';
$page_class = 'character';
include '../includes/header.php';
include '../includes/item_tooltip.php';
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Character Equipment</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <?php include_once '../includes/arenanavbar.php'; ?>
    <style>
        .nav-container {
            border: 2px double #056602ff;
            margin-top: 20px;
        }
        .character-container {
            display: flex;
            flex-wrap: wrap;
            max-width: 1280px;
            margin-left: auto;
            margin-right: auto;
            margin-top: 30px;
            margin-bottom: 20px;
            background-color: #1a1a1a93;
            border: 2px solid #444;
            border-radius: 5px;
            overflow: hidden;
        }
        .equipment-column {
            width: 220px;
            padding: 20px;
            box-sizing: border-box;
        }
        .equipment-left {
            order: 1;
        }
        .equipment-right {
            order: 3;
        }
        .character-center {
            order: 2;
            flex: 1;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            border-left: 1px solid #444;
            border-right: 1px solid #444;
            min-width: 280px;
        }
        .character-image {
            width: 100%;
            max-width: 380px;
            height: 420px;
            margin: 20px 0;
            position: relative;
            overflow: hidden;
        }
        .character-image canvas {
            width: 100% !important;
            height: 100% !important;
        }
        .character-image img.default-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
            z-index: 1;
        }
        .weapons-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
            margin-top: 90px;
            width: 100%;
            padding-top: 20px;
        }
        .tab-nav {
            display: flex;
            justify-content: center;
            width: 100%;
            max-width: 1280px;
            margin: 20px auto;
            background: linear-gradient(180deg, #141414e6 0%, #0a0a0ae6 100%);
            border: 3px solid #666;
            border-radius: 8px;
            overflow: hidden;
        }
        .tab-nav button {
            flex: 1;
            padding: 12px 20px;
            background: #222222cc;
            border: none;
            border-right: 1px solid #666;
            color: #ffcc00;
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            cursor: pointer;
            transition: background 0.2s ease, color 0.2s ease;
        }
        .tab-nav button:last-child {
            border-right: none;
        }
        .tab-nav button:hover {
            background: #333;
            color: #ffee58;
            cursor: url('/Sahtout/img/hover_wow.gif') 16 16, auto;
        }
        .tab-nav button.active {
            background: #444;
            color: #ffee58;
            text-shadow: 0 0 5px rgba(255, 204, 0, 0.5);
        }
        .tab-content {
            max-width: 1280px;
            margin-left: auto;
            margin-right: auto;
            margin-top: 20px;
            margin-bottom: 20px;
            background: linear-gradient(180deg, #141414e6 0%, #0a0a0ae6 100%);
            border: 3px solid #666;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            color: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.7);
            animation: fadeIn 0.5s ease-in;
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .stats-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            padding: 16px;
        }
        .stats-category {
            background: #222222cc;
            border: 1px solid #555;
            border-radius: 4px;
            padding: 12px;
            width: 250px;
            text-align: left;
        }
        .stats-category h3 {
            font-size: 18px;
            color: #ffcc00;
            margin-bottom: 10px;
            text-align: center;
        }
        .stats-item {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            margin-bottom: 6px;
            color: #fff;
        }
        .stats-item span:first-child {
            color: #ccc;
        }
        .stats-item span:last-child {
            color: #ffcc00;
            font-weight: bold;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        .pvp-team-item {
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #666;
        }
        .pvp-team-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .pvp-team-item:hover {
            animation: pulse 0.3s ease-in-out;
        }
        .pvp-team {
            font-size: 20px;
            font-weight: bold;
            color: #ffcc00;
            margin-bottom: 8px;
            transition: color 0.2s ease;
        }
        .pvp-team:hover {
            color: #ffee58;
        }
        .pvp-members {
            font-size: 14px;
            color: #fff;
            margin-bottom: 8px;
        }
        .pvp-members ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: inline-flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
        }
        .pvp-members li {
            display: inline-flex;
            align-items: center;
            background: #222222cc;
            border: 1px solid #555;
            border-radius: 4px;
            padding: 8px 12px;
            transition: background 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
        }
        .pvp-members a {
            display: inline-flex;
            align-items: center;
            color: #ffcc00;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .pvp-members a:hover li {
            background: #333;
            border-color: #777;
            transform: scale(1.03);
        }
        .pvp-members li.current-player {
            color: #fff;
            font-weight: bold;
            text-shadow: 0 0 5px rgba(255, 204, 0, 0.5);
        }
        .pvp-members a:hover {
            color: #ffee58;
            cursor: url('/Sahtout/img/hover_wow.gif') 16 16, auto;
        }
        .member-details {
            color: #ccc;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
        }
        .member-details img {
            width: 20px;
            height: 20px;
            vertical-align: middle;
            margin-right: 4px;
        }
        .pvp-kills {
            font-size: 14px;
            color: #fff;
            padding-top: 8px;
        }
        .pvp-kills span {
            color: #ffcc00;
            font-weight: bold;
            text-shadow: 0 0 5px rgba(255, 204, 0, 0.5);
        }
        .slot {
            display: flex;
            align-items: center;
            background-color: #2222229f;
            border: 1px solid #444;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 10px;
            transition: all 0.2s;
            width: 100%;
            box-sizing: border-box;
        }
        .slot.has-item {
            cursor: pointer;
        }
        .weapon-slot {
            width: 200px;
            max-width: 100%;
        }
        .slot:hover {
            background-color: #333;
            border-color: #666;
            cursor: url('/Sahtout/img/hover_wow.gif') 16 16, auto;
        }
        .slot-icon {
            width: 40px;
            height: 40px;
            background-color: #333;
            border: 1px solid #555;
            border-radius: 3px;
            margin-right: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
        }
        .slot-icon img {
            max-width: 100%;
            max-height: 100%;
        }
        .slot-info {
            flex: 1;
            overflow: hidden;
        }
        .slot-name {
            font-weight: bold;
            color: #fff;
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .slot-item {
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .empty-slot {
            color: #777;
            font-style: italic;
        }
        .character-name {
            font-size: 24px;
            color: #fff;
            margin-bottom: 5px;
            text-align: center;
        }
        .character-details {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 10px;
            font-size: 14px;
            gap: 5px;
        }
        .character-level {
            color: #fff;
            font-weight: bold;
        }
        .character-race {
            color: #ffd100;
        }
        .error-message {
            color: #ff5555;
            font-style: italic;
            text-align: center;
            padding: 20px;
            width: 100%;
            max-width: 1280px;
            margin-left: auto;
            margin-right: auto;
        }
        .item-tooltip {
            position: fixed;
            z-index: 1000;
            pointer-events: none;
            max-width: 90vw;
            padding: 10px;
            color: #fff;
            border-radius: 4px;
            font-size: 14px;
        }
        footer {
            width: 100%;
            max-width: 1280px;
            margin-left: auto;
            margin-right: auto;
        }
        @media (max-width: 768px) {
            .character-container {
                max-width: 100%;
                margin-left: 0;
                margin-right: auto;
                padding: 4px;
                min-height: unset;
            }
            .equipment-column {
                width: 80px;
                padding: 10px;
            }
            .character-center {
                border-left: none;
                border-right: none;
                min-width: 160px;
                padding: 10px;
            }
            .character-image {
                max-width: 200px;
                height: auto;
                aspect-ratio: 7 / 10;
                margin: 10px 0;
            }
            .weapons-container {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-top: 60px;
                padding-top: 10px;
            }
            .tab-nav {
                max-width: 100%;
                margin: 10px auto;
                padding: 4px;
            }
            .tab-nav button {
                padding: 10px;
                font-size: 14px;
            }
            .tab-content {
                max-width: 100%;
                margin: auto;
                padding: 10px;
                border: 2px solid #666;
                box-shadow: none;
            }
            .stats-container {
                flex-direction: row;
                align-items: center;
                padding: 10px;
            }
            .stats-category {
                width: 100%;
                max-width: 300px;
            }
            .pvp-team-item {
                margin-bottom: 8px;
                padding-bottom: 6px;
            }
            .pvp-team {
                font-size: 16px;
            }
            .pvp-members {
                font-size: 12px;
            }
            .pvp-members ul {
                gap: 6px;
            }
            .pvp-members li {
                padding: 6px 10px;
            }
            .member-details img {
                width: 16px;
                height: 16px;
                margin-right: 3px;
            }
            .member-details::before {
                display: inline;
            }
            .member-details.horde::before {
                content: 'H, ';
            }
            .pvp-kills {
                font-size: 13px;
                padding-top: 6px;
            }
            .weapon-slot {
                width: 48px;
                max-width: 100%;
            }
            .slot {
                padding: 4px;
                width: 48px;
            }
            .slot-icon {
                width: 36px;
                height: 36px;
                margin-right: 0;
            }
            .slot-info {
                display: none;
            }
            .character-name {
                font-size: 20px;
            }
            .character-details {
                font-size: 13px;
            }
            .error-message {
                padding: 10px;
            }
            .item-tooltip {
                font-size: 13px;
                padding: 8px;
            }
        }
        @media (max-width: 480px) {
            .character-container {
                margin: auto;
                padding: 4px;
                min-height: unset;
            }
            .equipment-column {
                width: 80px;
                padding: 10px;
            }
            .character-center {
                padding: 10px;
                min-width: 160px;
            }
            .character-image {
                max-width: 160px;
                height: auto;
                aspect-ratio: 7 / 10;
                margin: 10px 0;
            }
            .weapons-container {
                margin: auto;
                padding-top: 10px;
            }
            .tab-nav {
                padding: 3px;
            }
            .tab-nav button {
                padding: 8px;
                font-size: 12px;
            }
            .tab-content {
                padding: 8px;
                border: 2px solid #666;
            }
            .stats-category {
                padding: 8px;
            }
            .stats-category h3 {
                font-size: 16px;
            }
            .stats-item {
                font-size: 13px;
            }
            .pvp-team-item {
                margin-bottom: 6px;
                padding-bottom: 5px;
            }
            .pvp-team {
                font-size: 15px;
            }
            .pvp-members {
                font-size: 11px;
            }
            .pvp-members ul {
                gap: 5px;
            }
            .pvp-members li {
                padding: 4px 8px;
            }
            .member-details img {
                width: 16px;
                height: 16px;
                margin-right: 3px;
            }
            .member-details span {
                display: none;
            }
            .pvp-kills {
                font-size: 12px;
                padding-top: 5px;
            }
            .slot {
                padding: 3px;
                width: 48px;
            }
            .slot-icon {
                width: 32px;
                height: 32px;
            }
            .slot-info {
                display: none;
            }
            .character-name {
                font-size: 18px;
            }
            .character-details {
                font-size: 12px;
            }
            .error-message {
                padding: 10px;
            }
            .item-tooltip {
                font-size: 12px;
                padding: 8px;
            }
        }
        @media (max-width: 360px) {
            .character-container {
                padding: 2px;
                min-height: unset;
            }
            .equipment-column {
                width: 80px;
                padding: 8px;
            }
            .character-center {
                padding: 8px;
                min-width: 160px;
            }
            .character-image {
                max-width: 140px;
                height: auto;
                aspect-ratio: 7 / 10;
                margin: 10px 0;
            }
            .weapons-container {
                margin: auto;
                padding-top: 10px;
            }
            .tab-nav {
                padding: 2px;
            }
            .tab-nav button {
                padding: 6px;
                font-size: 11px;
            }
            .tab-content {
                padding: 6px;
                border: 2px solid #666;
            }
            .stats-category {
                padding: 6px;
            }
            .stats-category h3 {
                font-size: 14px;
            }
            .stats-item {
                font-size: 12px;
            }
            .pvp-team-item {
                margin-bottom: 6px;
                padding-bottom: 4px;
            }
            .pvp-team {
                font-size: 14px;
            }
            .pvp-members {
                font-size: 10px;
            }
            .pvp-members ul {
                gap: 4px;
            }
            .pvp-members li {
                padding: 3px 6px;
            }
            .member-details img {
                width: 12px;
                height: 12px;
                margin-right: 2px;
            }
            .member-details span {
                display: none;
            }
            .pvp-kills {
                font-size: 11px;
                padding-top: 4px;
            }
            .slot {
                padding: 2px;
                width: 48px;
            }
            .slot-icon {
                width: 32px;
                height: 32px;
            }
            .slot-info {
                display: none;
            }
            .character-name {
                font-size: 18px;
            }
            .character-details {
                font-size: 12px;
            }
            .error-message {
                padding: 10px;
            }
            .item-tooltip {
                font-size: 12px;
                padding: 8px;
            }
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tooltip = document.createElement('div');
            tooltip.className = 'item-tooltip';
            document.body.appendChild(tooltip);

            const slots = document.querySelectorAll('.slot.has-item');
            slots.forEach(slot => {
                slot.addEventListener('mouseenter', (e) => {
                    showTooltip(e, slot);
                });
                slot.addEventListener('mousemove', updateTooltipPosition);
                slot.addEventListener('mouseleave', hideTooltip);

                slot.addEventListener('touchstart', (e) => {
                    e.preventDefault();
                    showTooltip(e, slot);
                    setTimeout(hideTooltip, 3000);
                });
                slot.addEventListener('touchmove', updateTooltipPosition);
            });

            function showTooltip(e, slot) {
                const tooltipContent = slot.dataset.tooltip;
                if (tooltipContent) {
                    tooltip.innerHTML = tooltipContent;
                    tooltip.style.display = 'block';
                    updateTooltipPosition(e);
                }
            }

            function hideTooltip() {
                tooltip.style.display = 'none';
            }

            function updateTooltipPosition(e) {
                const tooltip = document.querySelector('.item-tooltip');
                const x = (e.clientX || (e.touches && e.touches[0].clientX)) + 10;
                const y = (e.clientY || (e.touches && e.touches[0].clientY)) + 10;
                tooltip.style.left = `${x}px`;
                tooltip.style.top = `${y}px`;

                const rect = tooltip.getBoundingClientRect();
                if (rect.right > window.innerWidth) {
                    tooltip.style.left = `${window.innerWidth - rect.width - 10}px`;
                }
                if (rect.bottom > window.innerHeight) {
                    tooltip.style.top = `${window.innerHeight - rect.height - 10}px`;
                }
            }

            // Tab navigation
            const tabs = document.querySelectorAll('.tab-nav button');
            const tabContents = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    tab.classList.add('active');
                    document.getElementById(tab.dataset.tab).classList.add('active');
                });
            });
        });
    </script>
</head>
<body>
<?php
$slotDefs = [
    0 => 'Head', 1 => 'Neck', 2 => 'Shoulders', 3 => 'Body', 4 => 'Chest',
    5 => 'Waist', 6 => 'Legs', 7 => 'Feet', 8 => 'Wrists', 9 => 'Hands',
    10 => 'Finger', 11 => 'Finger', 12 => 'Trinket', 13 => 'Trinket',
    14 => 'Back', 15 => 'Main Hand', 16 => 'Off Hand', 17 => 'Ranged', 18 => 'Tabard'
];
$defaultIcons = [
    0 => 'head.gif', 1 => 'neck.gif', 2 => 'shoulders.gif', 3 => 'body.gif', 4 => 'chest.gif',
    5 => 'waist.gif', 6 => 'legs.gif', 7 => 'feet.gif', 8 => 'wrists.gif', 9 => 'hands.gif',
    10 => 'finger.gif', 11 => 'finger.gif', 12 => 'trinket.gif', 13 => 'trinket.gif',
    14 => 'back.gif', 15 => 'mainhand.gif', 16 => 'offhand.gif', 17 => 'ranged.gif', 18 => 'tabard.gif'
];
$races = [
    1 => ['name' => 'Human', 'icon' => 'human'],
    2 => ['name' => 'Orc', 'icon' => 'orc'],
    3 => ['name' => 'Dwarf', 'icon' => 'dwarf'],
    4 => ['name' => 'Night Elf', 'icon' => 'nightelf'],
    5 => ['name' => 'Undead', 'icon' => 'undead'],
    6 => ['name' => 'Tauren', 'icon' => 'tauren'],
    7 => ['name' => 'Gnome', 'icon' => 'gnome'],
    8 => ['name' => 'Troll', 'icon' => 'troll'],
    10 => ['name' => 'Blood Elf', 'icon' => 'bloodelf'],
    11 => ['name' => 'Draenei', 'icon' => 'draenei']
];
$classes = [
    1 => 'Warrior', 2 => 'Paladin', 3 => 'Hunter', 4 => 'Rogue', 5 => 'Priest',
    6 => 'Death Knight', 7 => 'Shaman', 8 => 'Mage', 9 => 'Warlock', 11 => 'Druid'
];
$powerTypes = [
    0 => 'Mana', 1 => 'Rage', 2 => 'Focus', 3 => 'Energy', 4 => 'Happiness',
    5 => 'Runes', 6 => 'Runic Power'
];
$factions = [
    1 => 'Alliance', 3 => 'Alliance', 4 => 'Alliance', 7 => 'Alliance', 11 => 'Alliance',
    2 => 'Horde', 5 => 'Horde', 6 => 'Horde', 8 => 'Horde', 10 => 'Horde'
];
$class_abbr = [
    'Warrior' => 'War', 'Paladin' => 'Pal', 'Hunter' => 'Hunt', 'Rogue' => 'Rog',
    'Priest' => 'Pri', 'Death Knight' => 'DK', 'Shaman' => 'Sham', 'Mage' => 'Mag',
    'Warlock' => 'Lock', 'Druid' => 'Dru'
];
$guid = isset($_GET['guid']) ? (int)$_GET['guid'] : 0;
$character = null;
$items = [];
$pvp_teams = [];
$stats = null;
$total_kills = 0;
$error = '';
if ($guid > 0) {
    if (!isset($char_db) || !$char_db) {
        $error = 'Database connection ($char_db) is not available.';
        error_log("armory.php: Database connection ($char_db) not initialized for guid=$guid");
    } else {
        // Fetch character data
        $stmt = $char_db->prepare("SELECT guid, name, race, class, level, totalKills, gender FROM characters WHERE guid = ?");
        if (!$stmt) {
            $error = 'Failed to prepare character query.';
            error_log("armory.php: Failed to prepare character query for guid=$guid: " . $char_db->error);
        } else {
            $stmt->bind_param("i", $guid);
            if (!$stmt->execute()) {
                $error = 'Failed to execute character query.';
                error_log("armory.php: Character query execution failed for guid=$guid: " . $stmt->error);
            } else {
                $result = $stmt->get_result();
                $character = $result->fetch_assoc();
                if (!$character) {
                    $error = 'Character not found for GUID ' . $guid . '.';
                    error_log("armory.php: No character found for guid=$guid");
                } else {
                    $total_kills = $character['totalKills'] ?? 0;
                }
                $stmt->close();
            }
        }
        // Fetch stats data
        if (!$error) {
            $stmt = $char_db->prepare("
                SELECT maxhealth, maxpower1, maxpower2, maxpower3, maxpower4, maxpower5, maxpower6, maxpower7,
                       strength, agility, stamina, intellect, spirit, armor, resHoly, resFire, resNature,
                       resFrost, resShadow, resArcane, blockPct, dodgePct, parryPct, critPct, rangedCritPct,
                       spellCritPct, attackPower, rangedAttackPower, spellPower, resilience
                FROM character_stats WHERE guid = ?
            ");
            if (!$stmt) {
                $error = 'Failed to prepare stats query.';
                error_log("armory.php: Failed to prepare stats query for guid=$guid: " . $char_db->error);
            } else {
                $stmt->bind_param("i", $guid);
                if (!$stmt->execute()) {
                    $error = 'Failed to execute stats query.';
                    error_log("armory.php: Stats query execution failed for guid=$guid: " . $stmt->error);
                } else {
                    $result = $stmt->get_result();
                    $stats = $result->fetch_assoc();
                    if (!$stats) {
                        $error = 'No stats found for GUID ' . $guid . '.';
                        error_log("armory.php: No stats found for guid=$guid");
                    }
                    $stmt->close();
                }
            }
        }
        // Fetch arena team data
        if (!$error) {
            $stmt = $char_db->prepare("
                SELECT at.arenaTeamId, at.name, at.type, at.rating
                FROM arena_team_member atm
                JOIN arena_team at ON atm.arenaTeamId = at.arenaTeamId
                WHERE atm.guid = ?
            ");
            if (!$stmt) {
                $error = 'Failed to prepare arena team query.';
                error_log("armory.php: Failed to prepare arena team query for guid=$guid: " . $char_db->error);
            } else {
                $stmt->bind_param("i", $guid);
                if (!$stmt->execute()) {
                    $error = 'Failed to execute arena team query.';
                    error_log("armory.php: Arena team query execution failed for guid=$guid: " . $stmt->error);
                } else {
                    $result = $stmt->get_result();
                    while ($team = $result->fetch_assoc()) {
                        $pvp_teams[] = $team;
                    }
                    $stmt->close();
                    foreach ($pvp_teams as &$team) {
                        $stmt = $char_db->prepare("
                            SELECT c.guid, c.name, c.race, c.class, c.gender
                            FROM arena_team_member atm
                            JOIN characters c ON atm.guid = c.guid
                            WHERE atm.arenaTeamId = ?
                        ");
                        if (!$stmt) {
                            $error = 'Failed to prepare arena team members query.';
                            error_log("armory.php: Failed to prepare arena team members query for arenaTeamId={$team['arenaTeamId']}: " . $char_db->error);
                        } else {
                            $stmt->bind_param("i", $team['arenaTeamId']);
                            if (!$stmt->execute()) {
                                $error = 'Failed to execute arena team members query.';
                                error_log("armory.php: Arena team members query execution failed for arenaTeamId={$team['arenaTeamId']}: " . $stmt->error);
                            } else {
                                $result = $stmt->get_result();
                                $team['members'] = [];
                                while ($row = $result->fetch_assoc()) {
                                    $row['faction'] = $factions[$row['race']] ?? 'Unknown';
                                    $team['members'][] = $row;
                                }
                                $stmt->close();
                            }
                        }
                    }
                    unset($team);
                }
            }
        }
        // Fetch inventory data
        if (!$error) {
            $stmt = $char_db->prepare("
                SELECT ci.slot, ii.itemEntry
                FROM character_inventory ci
                JOIN item_instance ii ON ci.item = ii.guid
                WHERE ci.guid = ? AND ci.bag = 0 AND ci.slot IN (0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18)
            ");
            if (!$stmt) {
                $error = 'Failed to prepare inventory query.';
                error_log("armory.php: Failed to prepare inventory query for guid=$guid: " . $char_db->error);
            } else {
                $stmt->bind_param("i", $guid);
                if (!$stmt->execute()) {
                    $error = 'Failed to execute inventory query.';
                    error_log("armory.php: Inventory query execution failed for guid=$guid: " . $stmt->error);
                } else {
                    $result = $stmt->get_result();
                    $itemEntries = [];
                    while ($row = $result->fetch_assoc()) {
                        $itemEntries[$row['slot']] = $row['itemEntry'];
                    }
                    $stmt->close();
                    if (empty($itemEntries)) {
                        error_log("armory.php: No equipped items found for guid=$guid in character_inventory with bag=0");
                    } else {
                        if (!isset($world_db) || !$world_db) {
                            $error = 'Database connection ($world_db) is not available.';
                            error_log("armory.php: Database connection ($world_db) not initialized for guid=$guid");
                        } else {
                            $placeholders = implode(',', array_fill(0, count($itemEntries), '?'));
                            $stmt = $world_db->prepare("
                                SELECT it.entry, it.name, it.Quality, it.ItemLevel, it.RequiredLevel, it.SellPrice, 
                                       it.MaxDurability, it.delay, it.bonding, it.class, it.subclass, it.InventoryType, 
                                       it.dmg_min1, it.dmg_max1, it.armor, it.holy_res, it.fire_res, it.nature_res, 
                                       it.frost_res, it.shadow_res, it.arcane_res, it.stat_type1, it.stat_value1, 
                                       it.stat_type2, it.stat_value2, it.stat_type3, it.stat_value3, it.stat_type4, 
                                       it.stat_value4, it.stat_type5, it.stat_value5, it.stat_type6, it.stat_value6, 
                                       it.stat_type7, it.stat_value7, it.stat_type8, it.stat_value8, it.stat_type9, 
                                       it.stat_value9, it.stat_type10, it.stat_value10, it.socketColor_1, 
                                       it.socketColor_2, it.socketColor_3, it.socketBonus, it.spellid_1, 
                                       it.spelltrigger_1, it.spellid_2, it.spelltrigger_2, it.spellid_3, 
                                       it.spelltrigger_3, it.spellid_4, it.spelltrigger_4, it.spellid_5, 
                                       it.spelltrigger_5, it.description, it.AllowableClass, it.displayid,
                                       idi.InventoryIcon_1 AS icon
                                FROM item_template it
                                LEFT JOIN itemdisplayinfo_dbc idi ON it.displayid = idi.ID
                                WHERE it.entry IN ($placeholders)
                            ");
                            if (!$stmt) {
                                $error = 'Failed to prepare item_template query.';
                                error_log("armory.php: Failed to prepare item_template query for guid=$guid: " . $world_db->error);
                            } else {
                                $itemEntryValues = array_values($itemEntries);
                                $stmt->bind_param(str_repeat('i', count($itemEntries)), ...$itemEntryValues);
                                if (!$stmt->execute()) {
                                    $error = 'Failed to execute item_template query.';
                                    error_log("armory.php: item_template query execution failed for guid=$guid: " . $stmt->error);
                                } else {
                                    $result = $stmt->get_result();
                                    while ($row = $result->fetch_assoc()) {
                                        $slot = array_search($row['entry'], $itemEntries);
                                        if (empty($row['icon'])) {
                                            error_log("armory.php: No icon in itemdisplayinfo_dbc for itemEntry={$row['entry']} (slot=$slot, displayid={$row['displayid']})");
                                        } else {
                                            $row['icon'] = strtolower($row['icon']);
                                        }
                                        $items[$slot] = $row;
                                    }
                                    $stmt->close();
                                    foreach ($itemEntries as $slot => $entry) {
                                        if (!isset($items[$slot])) {
                                            error_log("armory.php: No item_template entry found for itemEntry=$entry in slot=$slot for guid=$guid");
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    } else {
        $error = 'Invalid or missing GUID parameter.';
        error_log("armory.php: Invalid or missing guid parameter: " . ($_GET['guid'] ?? 'none'));
    }
?>
    <div class="character-container">
        <div class="equipment-column equipment-left">
            <?php foreach ([0, 1, 2, 14, 4, 3, 18, 8] as $slot): ?>
                <div class="slot<?= isset($items[$slot]) ? ' has-item' : '' ?>" <?= isset($items[$slot]) ? 'data-tooltip="' . htmlspecialchars(generateTooltip($items[$slot])) . '"' : '' ?>>
                    <div class="slot-icon">
                        <?php
                        $icon = isset($items[$slot]) && !empty($items[$slot]['icon']) ? $items[$slot]['icon'] : ($defaultIcons[$slot] ?? 'inv_misc_questionmark');
                        $iconSrc = isset($items[$slot]) && !empty($items[$slot]['icon']) ? "https://wow.zamimg.com/images/wow/icons/large/$icon.jpg" : "/Sahtout/img/characterarmor/$icon";
                        ?>
                        <img src="<?= htmlspecialchars($iconSrc) ?>" alt="<?= $slotDefs[$slot] ?>" loading="lazy">
                    </div>
                    <div class="slot-info">
                        <div class="slot-name"><?= $slotDefs[$slot] ?></div>
                        <?php if (isset($items[$slot])): ?>
                            <div class="slot-item" style="color:<?= $qualityColors[$items[$slot]['Quality']] ?? '#ffffff' ?>">
                                <?= htmlspecialchars($items[$slot]['name']) ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-slot">Empty</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="character-center">
            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php else: ?>
                <div class="character-name"><?= htmlspecialchars($character['name'] ?? 'Unknown') ?></div>
                <div class="character-details">
                    <span class="character-level">Level <?= $character['level'] ?? '??' ?> <?= isset($classes[$character['class']]) ? $classes[$character['class']] : 'Unknown' ?></span>
                    <span class="character-race"><?= isset($races[$character['race']]) ? $races[$character['race']]['name'] : 'Unknown' ?></span>
                </div>
                <div class="character-image">
                    <img src="/Sahtout/3dmodels/3d_default.gif" alt="Default Character Image" class="default-image">
                    <script type="importmap">
                        {
                            "imports": {
                                "three": "https://esm.sh/three@0.167.1",
                                "three/addons/": "https://esm.sh/three@0.167.1/examples/jsm/"
                            }
                        }
                    </script>
                    <script type="module">
                        import * as THREE from 'three';
                        import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
                        import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';

                        const container = document.querySelector('.character-image');
                        const defaultImage = container.querySelector('.default-image');
                        const scene = new THREE.Scene();
                        const camera = new THREE.PerspectiveCamera(75, container.clientWidth / container.clientHeight, 0.1, 1000);
                        const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
                        renderer.setSize(container.clientWidth, container.clientHeight);
                        container.appendChild(renderer.domElement);

                        const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
                        scene.add(ambientLight);
                        const directionalLight = new THREE.DirectionalLight(0xffffff, 1);
                        directionalLight.position.set(5, 5, 5);
                        scene.add(directionalLight);

                        const controls = new OrbitControls(camera, renderer.domElement);

                        // Dynamic model path based on race and gender
                        <?php
                        $raceIcon = isset($races[$character['race']]) ? $races[$character['race']]['icon'] : 'unknown';
                        $gender = ($character['gender'] ?? 0) == 0 ? 'male' : 'female';
                        $modelPath = "/Sahtout/3dmodels/character/$raceIcon/$gender/$raceIcon.gltf";
                        ?>
                        const modelPath = <?= json_encode($modelPath) ?>;

                        const loader = new GLTFLoader();
                        loader.load(modelPath, (gltf) => {
                            console.log('Model loaded successfully:', gltf);
                            const model = gltf.scene;
                            scene.add(model);

                            // Hide default image on successful model load
                            defaultImage.style.display = 'none';

                            model.traverse((child) => {
                                if (child.isMesh && child.material && child.material.map) {
                                    console.log('Mesh texture:', child.material.map.name || 'Unnamed texture');
                                } else if (child.isMesh) {
                                    console.log('Mesh missing texture:', child.name);
                                }
                            });

                            const box = new THREE.Box3().setFromObject(model);
                            const center = box.getCenter(new THREE.Vector3());
                            const size = box.getSize(new THREE.Vector3());
                            const initialDistance = size.z * 0.8;
                            camera.position.set(center.x + size.x, center.y + size.y / 2, center.z + size.z * 2);
                            camera.lookAt(center);
                            controls.target = center;
                            controls.minDistance = initialDistance * 0.5;
                            controls.maxDistance = initialDistance * 2.0;

                            if (gltf.animations && gltf.animations.length > 0) {
                                const mixer = new THREE.AnimationMixer(model);
                                const action = mixer.clipAction(gltf.animations[0]);
                                action.play();
                                console.log('Available animations:', gltf.animations);
                                const clock = new THREE.Clock();
                                function updateAnimations() {
                                    const delta = clock.getDelta();
                                    mixer.update(delta);
                                }
                                scene.userData.mixer = mixer;
                                scene.userData.updateAnimations = updateAnimations;
                            }
                        }, (progress) => {
                            console.log(`Loading: ${progress.loaded / progress.total * 100}%`);
                        }, (error) => {
                            console.error('Error loading model:', error);
                            // Default image remains visible if model fails to load
                        });

                        function animate() {
                            requestAnimationFrame(animate);
                            controls.update();
                            if (scene.userData.mixer) {
                                scene.userData.updateAnimations();
                            }
                            renderer.render(scene, camera);
                        }
                        animate();

                        window.addEventListener('resize', () => {
                            const width = container.clientWidth;
                            const height = container.clientHeight;
                            camera.aspect = width / height;
                            camera.updateProjectionMatrix();
                            renderer.setSize(width, height);
                        });
                    </script>
                </div>
                <div class="weapons-container">
                    <?php foreach ([15, 16, 17] as $slot): ?>
                        <div class="slot weapon-slot<?= isset($items[$slot]) ? ' has-item' : '' ?>" <?= isset($items[$slot]) ? 'data-tooltip="' . htmlspecialchars(generateTooltip($items[$slot])) . '"' : '' ?>>
                            <div class="slot-icon">
                                <?php
                                $icon = isset($items[$slot]) && !empty($items[$slot]['icon']) ? $items[$slot]['icon'] : ($defaultIcons[$slot] ?? 'inv_misc_questionmark');
                                $iconSrc = isset($items[$slot]) && !empty($items[$slot]['icon']) ? "https://wow.zamimg.com/images/wow/icons/large/$icon.jpg" : "/Sahtout/img/characterarmor/$icon";
                                ?>
                                <img src="<?= htmlspecialchars($iconSrc) ?>" alt="<?= $slotDefs[$slot] ?>" loading="lazy">
                            </div>
                            <div class="slot-info">
                                <div class="slot-name"><?= $slotDefs[$slot] ?></div>
                                <?php if (isset($items[$slot])): ?>
                                    <div class="slot-item" style="color:<?= $qualityColors[$items[$slot]['Quality']] ?? '#ffffff' ?>">
                                        <?= htmlspecialchars($items[$slot]['name']) ?>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-slot">Empty</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="equipment-column equipment-right">
            <?php foreach ([9, 5, 6, 7, 10, 11, 12, 13] as $slot): ?>
                <div class="slot<?= isset($items[$slot]) ? ' has-item' : '' ?>" <?= isset($items[$slot]) ? 'data-tooltip="' . htmlspecialchars(generateTooltip($items[$slot])) . '"' : '' ?>>
                    <div class="slot-icon">
                        <?php
                        $icon = isset($items[$slot]) && !empty($items[$slot]['icon']) ? $items[$slot]['icon'] : ($defaultIcons[$slot] ?? 'inv_misc_questionmark');
                        $iconSrc = isset($items[$slot]) && !empty($items[$slot]['icon']) ? "https://wow.zamimg.com/images/wow/icons/large/$icon.jpg" : "/Sahtout/img/characterarmor/$icon";
                        ?>
                        <img src="<?= htmlspecialchars($iconSrc) ?>" alt="<?= $slotDefs[$slot] ?>" loading="lazy">
                    </div>
                    <div class="slot-info">
                        <div class="slot-name"><?= $slotDefs[$slot] ?></div>
                        <?php if (isset($items[$slot])): ?>
                            <div class="slot-item" style="color:<?= $qualityColors[$items[$slot]['Quality']] ?? '#ffffff' ?>">
                                <?= htmlspecialchars($items[$slot]['name']) ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-slot">Empty</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php if (!$error): ?>
        <div class="tab-nav">
            <button data-tab="stats-tab" class="active">Stats</button>
            <button data-tab="talents-tab" class="">Talents</button>
            <button data-tab="pvp-tab" class="">PVP</button>
        </div>
        <div id="stats-tab" class="tab-content active">
            <?php if ($stats): ?>
                <div class="stats-container">
                    <div class="stats-category">
                        <h3>Base Stats</h3>
                        <div class="stats-item"><span>Health</span><span><?= number_format($stats['maxhealth']) ?></span></div>
                        <?php
                        // Always display Mana if available
                        if ($stats['maxpower1'] > 0):
                        ?>
                            <div class="stats-item"><span>Mana</span><span><?= number_format($stats['maxpower1']) ?></span></div>
                        <?php else: ?>
                            <div class="stats-item"><span>Mana</span><span>Not Available</span></div>
                        <?php endif; ?>
                        <?php
                        // Map class IDs to their primary power type indices (using PowerType IDs)
                        $classPowerMap = [
                            1 => 1,  // Warrior: Rage (maxpower2, PowerType 1)
                            2 => 0,  // Paladin: Mana (maxpower1, PowerType 0)
                            3 => 2,  // Hunter: Focus (maxpower3, PowerType 2)
                            4 => 3,  // Rogue: Energy (maxpower4, PowerType 3)
                            5 => 0,  // Priest: Mana (maxpower1, PowerType 0)
                            6 => 6,  // Death Knight: Runic Power (maxpower7, PowerType 6)
                            7 => 0,  // Shaman: Mana (maxpower1, PowerType 0)
                            8 => 0,  // Mage: Mana (maxpower1, PowerType 0)
                            9 => 0,  // Warlock: Mana (maxpower1, PowerType 0)
                            11 => 0  // Druid: Mana (maxpower1, PowerType 0)
                        ];
                        $powerIndex = isset($classPowerMap[$character['class']]) ? $classPowerMap[$character['class']] : 0;
                        // Adjust Rage and Runic Power for display (divide by 10)
                        $displayPowerValue = $stats["maxpower" . ($powerIndex + 1)];
                        if ($powerIndex == 1 && $stats['maxpower2'] > 0) { // Warrior: Rage
                            $displayPowerValue = $stats['maxpower2'] / 10;
                            if ($stats['maxpower2'] > 1000) {
                                error_log("armory.php: High Rage value for Warrior guid=$guid: maxpower2={$stats['maxpower2']} (displayed as " . number_format($displayPowerValue) . ")");
                            }
                        } elseif ($powerIndex == 6 && $stats['maxpower7'] > 0) { // Death Knight: Runic Power
                            $displayPowerValue = $stats['maxpower7'] / 10;
                            if ($stats['maxpower7'] > 1000) {
                                error_log("armory.php: High Runic Power value for Death Knight guid=$guid: maxpower7={$stats['maxpower7']} (displayed as " . number_format($displayPowerValue) . ")");
                            }
                        }
                        // Only display class-specific power type if it's not Mana (to avoid duplication)
                        if ($powerIndex > 0 && $stats["maxpower" . ($powerIndex + 1)] > 0):
                        ?>
                            <div class="stats-item"><span><?= htmlspecialchars($powerTypes[$powerIndex]) ?></span><span><?= number_format($displayPowerValue) ?></span></div>
                        <?php endif; ?>
                        <div class="stats-item"><span>Strength</span><span><?= number_format($stats['strength']) ?></span></div>
                        <div class="stats-item"><span>Agility</span><span><?= number_format($stats['agility']) ?></span></div>
                        <div class="stats-item"><span>Stamina</span><span><?= number_format($stats['stamina']) ?></span></div>
                        <div class="stats-item"><span>Intellect</span><span><?= number_format($stats['intellect']) ?></span></div>
                        <div class="stats-item"><span>Spirit</span><span><?= number_format($stats['spirit']) ?></span></div>
                    </div>
                    <div class="stats-category">
                        <h3>Defense</h3>
                        <div class="stats-item"><span>Armor</span><span><?= number_format($stats['armor']) ?></span></div>
                        <div class="stats-item"><span>Block</span><span><?= number_format($stats['blockPct'], 2) ?>%</span></div>
                        <div class="stats-item"><span>Dodge</span><span><?= number_format($stats['dodgePct'], 2) ?>%</span></div>
                        <div class="stats-item"><span>Parry</span><span><?= number_format($stats['parryPct'], 2) ?>%</span></div>
                        <div class="stats-item"><span>Resilience</span><span><?= number_format($stats['resilience']) ?></span></div>
                    </div>
                    <div class="stats-category">
                        <h3>Melee</h3>
                        <div class="stats-item"><span>Attack Power</span><span><?= number_format($stats['attackPower']) ?></span></div>
                        <div class="stats-item"><span>Crit Chance</span><span><?= number_format($stats['critPct'], 2) ?>%</span></div>
                    </div>
                    <div class="stats-category">
                        <h3>Ranged</h3>
                        <div class="stats-item"><span>Attack Power</span><span><?= number_format($stats['rangedAttackPower']) ?></span></div>
                        <div class="stats-item"><span>Crit Chance</span><span><?= number_format($stats['rangedCritPct'], 2) ?>%</span></div>
                    </div>
                    <div class="stats-category">
                        <h3>Resistances</h3>
                        <div class="stats-item"><span>Holy Resistance</span><span><?= number_format($stats['resHoly']) ?></span></div>
                        <div class="stats-item"><span>Fire Resistance</span><span><?= number_format($stats['resFire']) ?></span></div>
                        <div class="stats-item"><span>Nature Resistance</span><span><?= number_format($stats['resNature']) ?></span></div>
                        <div class="stats-item"><span>Frost Resistance</span><span><?= number_format($stats['resFrost']) ?></span></div>
                        <div class="stats-item"><span>Shadow Resistance</span><span><?= number_format($stats['resShadow']) ?></span></div>
                        <div class="stats-item"><span>Arcane Resistance</span><span><?= number_format($stats['resArcane']) ?></span></div>
                    </div>
                </div>
            <?php else: ?>
                <div class="pvp-team">No Stats Available</div>
            <?php endif; ?>
        </div>
        <div id="talents-tab" class="tab-content">
            <div class="pvp-team">Talents (Coming Soon)</div>
        </div>
        <div id="pvp-tab" class="tab-content">
            <?php if (!empty($pvp_teams)): ?>
                <?php foreach ($pvp_teams as $team): ?>
                    <div class="pvp-team-item">
                        <div class="pvp-team">
                            <?= htmlspecialchars($team['name']) ?> (<?= $team['type'] == 2 ? '2v2' : ($team['type'] == 3 ? '3v3' : '5v5') ?>, Rating: <?= $team['rating'] ?>)
                        </div>
                        <div class="pvp-members">
                            <ul>
                                <?php
                                foreach ($team['members'] as $member) {
                                    $name = htmlspecialchars($member['name']);
                                    $faction = $member['faction'];
                                    $race = isset($races[$member['race']]) ? $races[$member['race']]['name'] : 'Unknown';
                                    $race_icon_name = isset($races[$member['race']]) ? $races[$member['race']]['icon'] : 'unknown';
                                    $class = isset($classes[$member['class']]) ? $classes[$member['class']] : 'Unknown';
                                    $class_abbr = isset($class_abbr[$class]) ? $class_abbr[$class] : substr($class, 0, 3);
                                    $faction_icon = "/Sahtout/img/accountimg/faction/" . strtolower($faction) . ".png";
                                    $gender_dir = ($member['gender'] ?? 0) == 0 ? 'male' : 'female';
                                    $race_icon = "/Sahtout/img/accountimg/race/$gender_dir/$race_icon_name.png";
                                    $class_icon = "/Sahtout/img/accountimg/class/$class.webp";
                                    if ($member['guid'] == $character['guid']) {
                                        echo "<li class=\"current-player\">";
                                        echo "$name <span class=\"member-details $faction\">";
                                        echo "<img src=\"$faction_icon\" alt=\"$faction\" title=\"$faction\" class=\"inline-block\">";
                                        echo "<img src=\"$race_icon\" alt=\"$race\" title=\"$race\" class=\"inline-block\">";
                                        echo "<img src=\"$class_icon\" alt=\"$class\" title=\"$class\" class=\"inline-block\">";
                                        echo "</span></li>";
                                    } else {
                                        echo "<a href=\"/Sahtout/character?guid={$member['guid']}\" class=\"pvp-members-link\">";
                                        echo "<li>";
                                        echo "$name <span class=\"member-details $faction\">";
                                        echo "<img src=\"$faction_icon\" alt=\"$faction\" title=\"$faction\" class=\"inline-block\">";
                                        echo "<img src=\"$race_icon\" alt=\"$race\" title=\"$race\" class=\"inline-block\">";
                                        echo "<img src=\"$class_icon\" alt=\"$class\" title=\"$class\" class=\"inline-block\">";
                                        echo "</span></li>";
                                        echo "</a>";
                                    }
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="pvp-team">No PvP Teams</div>
            <?php endif; ?>
            <div class="pvp-kills">Total PvP Kills: <span><?= number_format($total_kills) ?></span></div>
        </div>
    <?php endif; ?>
    <div style="clear: both;"></div>
<?php include '../includes/footer.php'; ?>
</body>
</html>