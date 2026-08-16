<?php
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../includes/paths.php';
require_once $project_root . 'includes/session.php';
require_once $project_root . 'languages/language.php';
require_once $project_root . 'includes/item_tooltip.php';
$page_class = 'character';
require_once $project_root . 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo translate('meta_description', 'View your World of Warcraft character equipment, stats, and PvP details.'); ?>">
    <meta name="robots" content="index">
    <title><?php echo $site_title_name ." ". translate('page_title', 'Character Equipment'); ?></title>
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/tailwind.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&family=Cinzel:wght@600;700;900&display=swap" rel="stylesheet">
    
    <style>
        * { font-family: 'Inter', sans-serif; }

        body {
            min-height: 100vh;
            color: #d8d8d8;
            background: #05070b;
            background-image:
                radial-gradient(1000px 700px at -10% 35%, rgba(59,130,246,.14), transparent 65%),
                radial-gradient(800px 600px at -5% 85%, rgba(124,58,237,.10), transparent 70%),
                linear-gradient(180deg, #0a0e16 0%, #060810 45%, #03040a 100%);
            background-attachment: fixed;
            padding-top: 112px;
            margin: 0;
        }

        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image:
                radial-gradient(2px 2px at 10% 20%, rgba(242,207,82,.7), transparent 55%),
                radial-gradient(1.5px 1.5px at 30% 70%, rgba(242,207,82,.5), transparent 55%),
                radial-gradient(2px 2px at 55% 40%, rgba(255,160,60,.55), transparent 55%);
            background-size: 900px 700px;
            animation: emberDrift 45s linear infinite;
            opacity: .4;
            pointer-events: none;
            z-index: 0;
        }

        @keyframes emberDrift {
            from { background-position: 0 0; }
            to { background-position: 900px -700px; }
        }

        .panel {
            position: relative;
            background: linear-gradient(180deg, rgba(22,25,32,.92), rgba(8,10,14,.9));
            border: 1px solid rgba(201,162,39,.22);
            box-shadow: 0 12px 32px rgba(0,0,0,.55), inset 0 0 60px rgba(0,0,0,.45);
        }

        .panel::before {
            content: '';
            position: absolute;
            inset: 5px;
            border: 1px solid rgba(201,162,39,.14);
            pointer-events: none;
        }

        .panel::after {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                linear-gradient(#e8c552,#e8c552) left top / 18px 2px,
                linear-gradient(#e8c552,#e8c552) left top / 2px 18px,
                linear-gradient(#e8c552,#e8c552) right top / 18px 2px,
                linear-gradient(#e8c552,#e8c552) right top / 2px 18px,
                linear-gradient(#e8c552,#e8c552) left bottom / 18px 2px,
                linear-gradient(#e8c552,#e8c552) left bottom / 2px 18px,
                linear-gradient(#e8c552,#e8c552) right bottom / 18px 2px,
                linear-gradient(#e8c552,#e8c552) right bottom / 2px 18px;
            background-repeat: no-repeat;
        }

        .wow-title {
            font-family: 'Cinzel', serif;
            font-weight: 900;
            background: linear-gradient(180deg, #fff7d6 0%, #f2cf5b 35%, #c9a227 62%, #8a6a14 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 3px 6px rgba(0,0,0,.85));
            letter-spacing: .02em;
        }

        .section-title {
            font-family: 'Cinzel', serif;
            font-weight: 700;
            color: #f2cf5b;
            text-shadow: 0 0 12px rgba(201,162,39,.35), 0 2px 4px rgba(0,0,0,.8);
        }

        .slot {
            background: rgba(10, 14, 22, 0.7);
            border: 1px solid rgba(201,162,39,0.12);
            padding: 0.5rem 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s ease;
            min-height: 50px;
        }

        .slot:hover {
            border-color: rgba(201,162,39,0.3);
            background: rgba(10, 14, 22, 0.9);
        }

        .slot.has-item {
            border-left: 3px solid #f2cf5b;
        }

        .slot-icon {
            width: 36px;
            height: 36px;
            flex-shrink: 0;
            border: 1px solid rgba(201,162,39,0.15);
            background: rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .slot-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .slot-name {
            font-size: 0.65rem;
            text-transform: uppercase;
            color: #6b7280;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .slot-item {
            font-size: 0.85rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .empty-slot {
            color: #4b5563;
            font-size: 0.8rem;
            font-style: italic;
        }

        .character-name {
            font-family: 'Cinzel', serif;
            font-size: 2rem;
            font-weight: 700;
            color: #ffffff;
            text-shadow: 0 0 30px rgba(0,0,0,0.8);
        }

        .character-image {
            width: 100%;
            max-width: 350px;
            height: 400px;
            background: rgba(0,0,0,0.3);
            border: 1px solid rgba(201,162,39,0.15);
            position: relative;
            overflow: hidden;
        }

        .character-image .default-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .character-image canvas {
            width: 100% !important;
            height: 100% !important;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(0,0,0,0.3);
            padding: 0.25rem 0.75rem;
            border: 1px solid rgba(201,162,39,0.1);
        }

        .detail-item img {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 1px solid rgba(201,162,39,0.1);
            object-fit: cover;
        }

        .detail-item i {
            color: #f2cf5b;
            font-size: 0.8rem;
            width: 20px;
            text-align: center;
        }

        /* Tabs */
        .tab-btn {
            background: rgba(10, 14, 22, 0.6);
            border: 1px solid rgba(201,162,39,0.15);
            color: #9ca3af;
            padding: 0.6rem 1.8rem;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .tab-btn:hover {
            border-color: rgba(201,162,39,0.3);
            color: #ffffff;
            background: rgba(10, 14, 22, 0.8);
        }

        .tab-btn.active {
            background: rgba(242, 207, 82, 0.15);
            border-color: #f2cf5b;
            color: #f2cf5b;
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .stats-category {
            background: rgba(10, 14, 22, 0.6);
            border: 1px solid rgba(201,162,39,0.1);
            padding: 1rem;
        }

        .stats-category h3 {
            color: #f2cf5b;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid rgba(201,162,39,0.1);
        }

        .stats-item {
            display: flex;
            justify-content: space-between;
            padding: 0.25rem 0;
            color: #d1d5db;
            font-size: 0.85rem;
        }

        .stats-item span:last-child {
            color: #ffffff;
            font-weight: 600;
        }

        /* PVP - Redesigned */
        .pvp-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .pvp-card {
            background: rgba(10, 14, 22, 0.6);
            border: 1px solid rgba(201,162,39,0.12);
            padding: 1.25rem;
            transition: all 0.3s ease;
        }

        .pvp-card:hover {
            border-color: rgba(201,162,39,0.3);
            background: rgba(15, 20, 30, 0.7);
        }

        .pvp-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid rgba(201,162,39,0.1);
        }

        .pvp-card-title {
            font-family: 'Cinzel', serif;
            font-weight: 700;
            font-size: 1.1rem;
            color: #f2cf5b;
        }

        .pvp-card-rating {
            background: rgba(242, 207, 82, 0.1);
            padding: 0.25rem 0.75rem;
            border: 1px solid rgba(201,162,39,0.2);
            font-weight: 700;
            font-size: 0.9rem;
            color: #f2cf5b;
        }

        .pvp-team-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(201,162,39,0.1);
            border: 1px solid rgba(201,162,39,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            color: #f2cf5b;
            flex-shrink: 0;
        }

        .pvp-members-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .pvp-member {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(0,0,0,0.3);
            padding: 0.3rem 0.7rem;
            border: 1px solid rgba(201,162,39,0.08);
            transition: all 0.2s ease;
            font-size: 0.85rem;
            color: #d1d5db;
        }

        .pvp-member.current-player {
            border-color: #f2cf5b;
            background: rgba(242,207,82,0.1);
        }

        .pvp-member-link {
            text-decoration: none;
            color: inherit;
            cursor: pointer;
        }

        .pvp-member-link:hover .pvp-member {
            border-color: rgba(242, 207, 82, 0.3);
            background: rgba(242, 207, 82, 0.08);
        }

        .pvp-member img {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 1px solid rgba(201,162,39,0.1);
            flex-shrink: 0;
        }

        .pvp-member-name {
            font-weight: 500;
        }

        .pvp-empty {
            text-align: center;
            padding: 3rem 1rem;
            color: #6b7280;
        }

        .pvp-empty i {
            font-size: 3rem;
            color: rgba(201,162,39,0.2);
            display: block;
            margin-bottom: 1rem;
        }

        .pvp-kills-container {
            margin-top: 1.5rem;
            padding: 1.25rem;
            background: rgba(10, 14, 22, 0.6);
            border: 1px solid rgba(201,162,39,0.1);
            text-align: center;
        }

        .pvp-kills-container .kills-number {
            font-family: 'Cinzel', serif;
            font-size: 2.5rem;
            font-weight: 900;
            color: #f2cf5b;
            display: block;
            line-height: 1.2;
        }

        .pvp-kills-container .kills-label {
            color: #9ca3af;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Tooltip */
        .item-tooltip {
            display: none;
            position: fixed;
            z-index: 1000;
            background: rgba(5, 7, 11, 0.95);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(201,162,39,0.3);
            padding: 1rem;
            max-width: 350px;
            color: #d1d5db;
            font-size: 0.8rem;
            pointer-events: none;
            box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.8);
        }

        .item-tooltip .item-name {
            font-size: 0.9rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .item-tooltip .item-stats {
            margin-top: 0.25rem;
            border-top: 1px solid rgba(201,162,39,0.1);
            padding-top: 0.25rem;
        }

        @media (max-width: 768px) {
            body { padding-top: 96px; }
            .panel { padding: 1.5rem 0.75rem; }
            .character-image { height: 300px; }
            .tab-btn { flex: 1; min-width: 100px; text-align: center; padding: 0.5rem 1rem; font-size: 0.8rem; }
            .stats-container { grid-template-columns: 1fr; }
            .pvp-section { grid-template-columns: 1fr; }
        }

        @media (max-width: 480px) {
            .slot { padding: 0.35rem 0.5rem; min-height: 40px; }
            .slot-icon { width: 28px; height: 28px; }
            .slot-item { font-size: 0.75rem; }
            .character-name { font-size: 1.5rem; }
            .character-image { height: 250px; }
        }
    </style>
</head>
<body>

<div class="relative z-10 min-h-screen flex items-start justify-center px-4 md:px-8 py-8">
    <div class="container mx-auto max-w-7xl px-2 sm:px-4">
        
        <!-- Main Panel -->
        <div class="panel p-6 md:p-8">
            
            <?php
            $slotDefs = [
                0 => 'head', 1 => 'neck', 2 => 'shoulders', 3 => 'body', 4 => 'chest',
                5 => 'waist', 6 => 'legs', 7 => 'feet', 8 => 'wrists', 9 => 'hands',
                10 => 'finger', 11 => 'finger', 12 => 'trinket', 13 => 'trinket',
                14 => 'back', 15 => 'main_hand', 16 => 'off_hand', 17 => 'ranged', 18 => 'tabard'
            ];
            $slotLabels = [
                0 => translate('label_head', 'Head'),
                1 => translate('label_neck', 'Neck'),
                2 => translate('label_shoulders', 'Shoulders'),
                3 => translate('label_body', 'Body'),
                4 => translate('label_chest', 'Chest'),
                5 => translate('label_waist', 'Waist'),
                6 => translate('label_legs', 'Legs'),
                7 => translate('label_feet', 'Feet'),
                8 => translate('label_wrists', 'Wrists'),
                9 => translate('label_hands', 'Hands'),
                10 => translate('label_finger', 'Finger'),
                11 => translate('label_finger', 'Finger'),
                12 => translate('label_trinket', 'Trinket'),
                13 => translate('label_trinket', 'Trinket'),
                14 => translate('label_back', 'Back'),
                15 => translate('label_main_hand', 'Main Hand'),
                16 => translate('label_off_hand', 'Off Hand'),
                17 => translate('label_ranged', 'Ranged'),
                18 => translate('label_tabard', 'Tabard')
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
                1 => ['name' => translate('class_warrior', 'Warrior'), 'icon' => 'warrior'],
                2 => ['name' => translate('class_paladin', 'Paladin'), 'icon' => 'paladin'],
                3 => ['name' => translate('class_hunter', 'Hunter'), 'icon' => 'hunter'],
                4 => ['name' => translate('class_rogue', 'Rogue'), 'icon' => 'rogue'],
                5 => ['name' => translate('class_priest', 'Priest'), 'icon' => 'priest'],
                6 => ['name' => translate('class_death_knight', 'Death Knight'), 'icon' => 'deathknight'],
                7 => ['name' => translate('class_shaman', 'Shaman'), 'icon' => 'shaman'],
                8 => ['name' => translate('class_mage', 'Mage'), 'icon' => 'mage'],
                9 => ['name' => translate('class_warlock', 'Warlock'), 'icon' => 'warlock'],
                11 => ['name' => translate('class_druid', 'Druid'), 'icon' => 'druid']
            ];
            $powerTypes = [
                0 => translate('power_mana', 'Mana'),
                1 => translate('power_rage', 'Rage'),
                2 => translate('power_focus', 'Focus'),
                3 => translate('power_energy', 'Energy'),
                4 => translate('power_happiness', 'Happiness'),
                5 => translate('power_runes', 'Runes'),
                6 => translate('power_runic_power', 'Runic Power')
            ];
            $factions = [
                1 => ['name' => translate('faction_alliance', 'Alliance'), 'icon' => 'alliance'],
                3 => ['name' => translate('faction_alliance', 'Alliance'), 'icon' => 'alliance'],
                4 => ['name' => translate('faction_alliance', 'Alliance'), 'icon' => 'alliance'],
                7 => ['name' => translate('faction_alliance', 'Alliance'), 'icon' => 'alliance'],
                11 => ['name' => translate('faction_alliance', 'Alliance'), 'icon' => 'alliance'],
                2 => ['name' => translate('faction_horde', 'Horde'), 'icon' => 'horde'],
                5 => ['name' => translate('faction_horde', 'Horde'), 'icon' => 'horde'],
                6 => ['name' => translate('faction_horde', 'Horde'), 'icon' => 'horde'],
                8 => ['name' => translate('faction_horde', 'Horde'), 'icon' => 'horde'],
                10 => ['name' => translate('faction_horde', 'Horde'), 'icon' => 'horde']
            ];
            $qualityColors = [
                0 => '#9d9d9d', 1 => '#ffffff', 2 => '#1eff00', 3 => '#0070dd',
                4 => '#a335ee', 5 => '#ff8000', 6 => '#e5cc80', 7 => '#00ccff'
            ];
            $class_abbr = [
                translate('class_warrior', 'Warrior') => 'War',
                translate('class_paladin', 'Paladin') => 'Pal',
                translate('class_hunter', 'Hunter') => 'Hunt',
                translate('class_rogue', 'Rogue') => 'Rog',
                translate('class_priest', 'Priest') => 'Pri',
                translate('class_death_knight', 'Death Knight') => 'DK',
                translate('class_shaman', 'Shaman') => 'Sham',
                translate('class_mage', 'Mage') => 'Mag',
                translate('class_warlock', 'Warlock') => 'Lock',
                translate('class_druid', 'Druid') => 'Dru'
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
                    $error = translate('error_db_connection', 'Database connection is not available.');
                } else {
                    // Fetch character data
                    $stmt = $char_db->prepare("SELECT guid, name, race, class, level, totalKills, gender FROM characters WHERE guid = ?");
                    if ($stmt) {
                        $stmt->bind_param("i", $guid);
                        if ($stmt->execute()) {
                            $result = $stmt->get_result();
                            $character = $result->fetch_assoc();
                            if ($character) {
                                $total_kills = $character['totalKills'] ?? 0;
                            } else {
                                $error = translate('error_character_not_found', 'Character not found for GUID {guid}.');
                                $error = str_replace('{guid}', $guid, $error);
                            }
                        } else {
                            $error = translate('error_execute_character_query', 'Failed to execute character query.');
                        }
                        $stmt->close();
                    } else {
                        $error = translate('error_prepare_character_query', 'Failed to prepare character query.');
                    }
                    // Fetch stats
                    if (!$error) {
                        $stmt = $char_db->prepare("SELECT maxhealth, maxpower1, maxpower2, maxpower3, maxpower4, maxpower5, maxpower6, maxpower7, strength, agility, stamina, intellect, spirit, armor, resHoly, resFire, resNature, resFrost, resShadow, resArcane, blockPct, dodgePct, parryPct, critPct, rangedCritPct, spellCritPct, attackPower, rangedAttackPower, spellPower, resilience FROM character_stats WHERE guid = ?");
                        if ($stmt) {
                            $stmt->bind_param("i", $guid);
                            if ($stmt->execute()) {
                                $result = $stmt->get_result();
                                $stats = $result->fetch_assoc();
                            } else {
                                $error = translate('error_execute_stats_query', 'Failed to execute stats query.');
                            }
                            $stmt->close();
                        } else {
                            $error = translate('error_prepare_stats_query', 'Failed to prepare stats query.');
                        }
                    }
                    // Fetch arena teams
                    if (!$error) {
                        $stmt = $char_db->prepare("SELECT at.arenaTeamId, at.name, at.type, at.rating FROM arena_team_member atm JOIN arena_team at ON atm.arenaTeamId = at.arenaTeamId WHERE atm.guid = ?");
                        if ($stmt) {
                            $stmt->bind_param("i", $guid);
                            if ($stmt->execute()) {
                                $result = $stmt->get_result();
                                while ($team = $result->fetch_assoc()) {
                                    $pvp_teams[] = $team;
                                }
                            } else {
                                $error = translate('error_execute_arena_query', 'Failed to execute arena team query.');
                            }
                            $stmt->close();
                            foreach ($pvp_teams as &$team) {
                                $stmt = $char_db->prepare("SELECT c.guid, c.name, c.race, c.class, c.gender FROM arena_team_member atm JOIN characters c ON atm.guid = c.guid WHERE atm.arenaTeamId = ?");
                                if ($stmt) {
                                    $stmt->bind_param("i", $team['arenaTeamId']);
                                    if ($stmt->execute()) {
                                        $result = $stmt->get_result();
                                        $team['members'] = [];
                                        while ($row = $result->fetch_assoc()) {
                                            $row['faction'] = isset($factions[$row['race']]) ? $factions[$row['race']]['name'] : translate('faction_unknown', 'Unknown');
                                            $row['faction_icon'] = isset($factions[$row['race']]) ? $factions[$row['race']]['icon'] : 'unknown';
                                            $team['members'][] = $row;
                                        }
                                    }
                                    $stmt->close();
                                } else {
                                    $error = translate('error_prepare_arena_members_query', 'Failed to prepare arena team members query.');
                                }
                            }
                            unset($team);
                        } else {
                            $error = translate('error_prepare_arena_query', 'Failed to prepare arena team query.');
                        }
                    }
                    // Fetch inventory
                    if (!$error) {
                        $stmt = $char_db->prepare("SELECT ci.slot, ii.itemEntry FROM character_inventory ci JOIN item_instance ii ON ci.item = ii.guid WHERE ci.guid = ? AND ci.bag = 0 AND ci.slot IN (0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18)");
                        if ($stmt) {
                            $stmt->bind_param("i", $guid);
                            if ($stmt->execute()) {
                                $result = $stmt->get_result();
                                $itemEntries = [];
                                while ($row = $result->fetch_assoc()) {
                                    $itemEntries[$row['slot']] = $row['itemEntry'];
                                }
                                $stmt->close();
                                if (!empty($itemEntries) && isset($world_db) && $world_db) {
                                    $placeholders = implode(',', array_fill(0, count($itemEntries), '?'));
                                    $stmt = $world_db->prepare("SELECT it.entry, it.name, it.Quality, it.ItemLevel, it.RequiredLevel, it.SellPrice, it.MaxDurability, it.delay, it.bonding, it.class, it.subclass, it.InventoryType, it.dmg_min1, it.dmg_max1, it.armor, it.holy_res, it.fire_res, it.nature_res, it.frost_res, it.shadow_res, it.arcane_res, it.stat_type1, it.stat_value1, it.stat_type2, it.stat_value2, it.stat_type3, it.stat_value3, it.stat_type4, it.stat_value4, it.stat_type5, it.stat_value5, it.stat_type6, it.stat_value6, it.stat_type7, it.stat_value7, it.stat_type8, it.stat_value8, it.stat_type9, it.stat_value9, it.stat_type10, it.stat_value10, it.socketColor_1, it.socketColor_2, it.socketColor_3, it.socketBonus, it.spellid_1, it.spelltrigger_1, it.spellid_2, it.spelltrigger_2, it.spellid_3, it.spelltrigger_3, it.spellid_4, it.spelltrigger_4, it.spellid_5, it.spelltrigger_5, it.description, it.AllowableClass, it.displayid, idi.InventoryIcon_1 AS icon FROM item_template it LEFT JOIN itemdisplayinfo_dbc idi ON it.displayid = idi.ID WHERE it.entry IN ($placeholders)");
                                    if ($stmt) {
                                        $itemEntryValues = array_values($itemEntries);
                                        $stmt->bind_param(str_repeat('i', count($itemEntries)), ...$itemEntryValues);
                                        if ($stmt->execute()) {
                                            $result = $stmt->get_result();
                                            while ($row = $result->fetch_assoc()) {
                                                $slot = array_search($row['entry'], $itemEntries);
                                                if (empty($row['icon'])) {
                                                    $row['icon'] = 'inv_misc_questionmark';
                                                } else {
                                                    $row['icon'] = strtolower($row['icon']);
                                                }
                                                $items[$slot] = $row;
                                            }
                                        }
                                        $stmt->close();
                                    } else {
                                        $error = translate('error_prepare_item_query', 'Failed to prepare item template query.');
                                    }
                                }
                            } else {
                                $error = translate('error_execute_inventory_query', 'Failed to execute inventory query.');
                                $stmt->close();
                            }
                        } else {
                            $error = translate('error_prepare_inventory_query', 'Failed to prepare inventory query.');
                        }
                    }
                }
            } else {
                $error = translate('error_invalid_guid', 'Invalid or missing GUID parameter.');
            }
            ?>

            <?php if ($error): ?>
                <div class="text-center py-12 text-red-400">
                    <i class="fas fa-exclamation-triangle text-4xl block mb-3"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php else: ?>

            <!-- Character Container -->
            <div class="flex flex-wrap justify-center gap-6 mb-6">
                <!-- Left Equipment Column -->
                <div class="flex flex-col gap-1 min-w-[200px] flex-1 max-w-[280px]">
                    <?php foreach ([0, 1, 2, 14, 4, 3, 18, 8] as $slot): ?>
                        <div class="slot<?= isset($items[$slot]) ? ' has-item' : '' ?>" <?= isset($items[$slot]) ? 'data-tooltip="' . htmlspecialchars(generateTooltip($items[$slot])) . '"' : '' ?>>
                            <div class="slot-icon">
                                <?php
                                $icon = isset($items[$slot]) && !empty($items[$slot]['icon']) ? $items[$slot]['icon'] : ($defaultIcons[$slot] ?? 'inv_misc_questionmark');
                                $iconSrc = isset($items[$slot]) && !empty($items[$slot]['icon']) ? "https://wow.zamimg.com/images/wow/icons/large/$icon.jpg" : "{$base_path}img/characterarmor/$icon";
                                ?>
                                <img src="<?= htmlspecialchars($iconSrc) ?>" alt="<?= htmlspecialchars($slotLabels[$slot]) ?>" loading="lazy">
                            </div>
                            <div class="slot-info flex-1 min-w-0">
                                <div class="slot-name"><?= htmlspecialchars($slotLabels[$slot]) ?></div>
                                <?php if (isset($items[$slot])): ?>
                                    <div class="slot-item" style="color:<?= $qualityColors[$items[$slot]['Quality']] ?? '#ffffff' ?>">
                                        <?= htmlspecialchars($items[$slot]['name']) ?>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-slot"><?php echo translate('slot_empty', 'Empty'); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Character Center -->
                <div class="flex flex-col items-center gap-4 flex-1 min-w-[250px] max-w-[400px]">
                    <div class="character-name"><?= htmlspecialchars($character['name'] ?? 'Unknown') ?></div>
                    <div class="flex flex-wrap justify-center gap-2">
                        <span class="detail-item">
                            <i class="fas fa-arrow-up"></i>
                            <?php echo translate('level_label', 'Level'); ?> <?= $character['level'] ?? '??' ?>
                        </span>
                        <span class="detail-item">
                            <?php 
                            $classIcon = isset($classes[$character['class']]) ? $classes[$character['class']]['icon'] : 'unknown';
                            $classIconPath = "{$base_path}img/accountimg/class/{$classIcon}.webp";
                            ?>
                            <img src="<?= htmlspecialchars($classIconPath) ?>" alt="<?= isset($classes[$character['class']]) ? htmlspecialchars($classes[$character['class']]['name']) : translate('class_unknown', 'Unknown') ?>">
                            <?= isset($classes[$character['class']]) ? htmlspecialchars($classes[$character['class']]['name']) : translate('class_unknown', 'Unknown') ?>
                        </span>
                        <span class="detail-item">
                            <?php 
                            $raceIcon = isset($races[$character['race']]) ? $races[$character['race']]['icon'] : 'unknown';
                            $gender = ($character['gender'] ?? 0) == 0 ? 'male' : 'female';
                            $raceIconPath = "{$base_path}img/accountimg/race/{$gender}/{$raceIcon}.png";
                            ?>
                            <img src="<?= htmlspecialchars($raceIconPath) ?>" alt="<?= isset($races[$character['race']]) ? htmlspecialchars($races[$character['race']]['name']) : translate('race_unknown', 'Unknown') ?>">
                            <?= isset($races[$character['race']]) ? htmlspecialchars($races[$character['race']]['name']) : translate('race_unknown', 'Unknown') ?>
                        </span>
                    </div>
                    <div class="character-image">
                        <img src="<?php echo $base_path; ?>3dmodels/3d_default.gif" alt="<?php echo translate('default_character_image', 'Default Character Image'); ?>" class="default-image">
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

                            <?php
                            $raceIcon = isset($races[$character['race']]) ? $races[$character['race']]['icon'] : 'unknown';
                            $gender = ($character['gender'] ?? 0) == 0 ? 'male' : 'female';
                            $modelPath = "{$base_path}3dmodels/character/$raceIcon/$gender/$raceIcon.gltf";
                            ?>
                            const modelPath = <?= json_encode($modelPath) ?>;

                            const loader = new GLTFLoader();
                            loader.load(modelPath, (gltf) => {
                                const model = gltf.scene;
                                scene.add(model);
                                defaultImage.style.display = 'none';

                                const box = new THREE.Box3().setFromObject(model);
                                const center = box.getCenter(new THREE.Vector3());
                                const size = box.getSize(new THREE.Vector3());
                                camera.position.set(center.x + size.x, center.y + size.y / 2, center.z + size.z * 2);
                                camera.lookAt(center);
                                controls.target = center;

                                if (gltf.animations && gltf.animations.length > 0) {
                                    const mixer = new THREE.AnimationMixer(model);
                                    const action = mixer.clipAction(gltf.animations[0]);
                                    action.play();
                                    const clock = new THREE.Clock();
                                    function updateAnimations() {
                                        const delta = clock.getDelta();
                                        mixer.update(delta);
                                    }
                                    scene.userData.mixer = mixer;
                                    scene.userData.updateAnimations = updateAnimations;
                                }
                            }, undefined, (error) => {
                                console.error('Error loading model:', error);
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
                    <div class="flex gap-1 w-full max-w-[350px]">
                        <?php foreach ([15, 16, 17] as $slot): ?>
                            <div class="slot flex-1 min-h-[45px]<?= isset($items[$slot]) ? ' has-item' : '' ?>" <?= isset($items[$slot]) ? 'data-tooltip="' . htmlspecialchars(generateTooltip($items[$slot])) . '"' : '' ?>>
                                <div class="slot-icon">
                                    <?php
                                    $icon = isset($items[$slot]) && !empty($items[$slot]['icon']) ? $items[$slot]['icon'] : ($defaultIcons[$slot] ?? 'inv_misc_questionmark');
                                    $iconSrc = isset($items[$slot]) && !empty($items[$slot]['icon']) ? "https://wow.zamimg.com/images/wow/icons/large/$icon.jpg" : "{$base_path}img/characterarmor/$icon";
                                    ?>
                                    <img src="<?= htmlspecialchars($iconSrc) ?>" alt="<?= htmlspecialchars($slotLabels[$slot]) ?>" loading="lazy">
                                </div>
                                <div class="slot-info flex-1 min-w-0">
                                    <div class="slot-name"><?= htmlspecialchars($slotLabels[$slot]) ?></div>
                                    <?php if (isset($items[$slot])): ?>
                                        <div class="slot-item" style="color:<?= $qualityColors[$items[$slot]['Quality']] ?? '#ffffff' ?>">
                                            <?= htmlspecialchars($items[$slot]['name']) ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="empty-slot"><?php echo translate('slot_empty', 'Empty'); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Right Equipment Column -->
                <div class="flex flex-col gap-1 min-w-[200px] flex-1 max-w-[280px]">
                    <?php foreach ([9, 5, 6, 7, 10, 11, 12, 13] as $slot): ?>
                        <div class="slot<?= isset($items[$slot]) ? ' has-item' : '' ?>" <?= isset($items[$slot]) ? 'data-tooltip="' . htmlspecialchars(generateTooltip($items[$slot])) . '"' : '' ?>>
                            <div class="slot-icon">
                                <?php
                                $icon = isset($items[$slot]) && !empty($items[$slot]['icon']) ? $items[$slot]['icon'] : ($defaultIcons[$slot] ?? 'inv_misc_questionmark');
                                $iconSrc = isset($items[$slot]) && !empty($items[$slot]['icon']) ? "https://wow.zamimg.com/images/wow/icons/large/$icon.jpg" : "{$base_path}img/characterarmor/$icon";
                                ?>
                                <img src="<?= htmlspecialchars($iconSrc) ?>" alt="<?= htmlspecialchars($slotLabels[$slot]) ?>" loading="lazy">
                            </div>
                            <div class="slot-info flex-1 min-w-0">
                                <div class="slot-name"><?= htmlspecialchars($slotLabels[$slot]) ?></div>
                                <?php if (isset($items[$slot])): ?>
                                    <div class="slot-item" style="color:<?= $qualityColors[$items[$slot]['Quality']] ?? '#ffffff' ?>">
                                        <?= htmlspecialchars($items[$slot]['name']) ?>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-slot"><?php echo translate('slot_empty', 'Empty'); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex flex-wrap gap-1 border-b border-[rgba(201,162,39,0.15)] pb-2">
                <button data-tab="stats-tab" class="tab-btn active"><i class="fas fa-chart-bar mr-2"></i><?php echo translate('tab_stats', 'Stats'); ?></button>
                <button data-tab="talents-tab" class="tab-btn"><i class="fas fa-star mr-2"></i><?php echo translate('tab_talents', 'Talents'); ?></button>
                <button data-tab="pvp-tab" class="tab-btn"><i class="fas fa-crosshairs mr-2"></i><?php echo translate('tab_pvp', 'PVP'); ?></button>
            </div>

            <!-- Stats Tab -->
            <div id="stats-tab" class="tab-content active mt-6">
                <?php if ($stats): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="stats-category">
                            <h3><i class="fas fa-heart mr-2"></i><?php echo translate('stats_base', 'Base Stats'); ?></h3>
                            <div class="stats-item"><span><?php echo translate('stat_health', 'Health'); ?></span><span><?= number_format($stats['maxhealth']) ?></span></div>
                            <?php if ($stats['maxpower1'] > 0): ?>
                                <div class="stats-item"><span><?php echo translate('stat_mana', 'Mana'); ?></span><span><?= number_format($stats['maxpower1']) ?></span></div>
                            <?php else: ?>
                                <div class="stats-item"><span><?php echo translate('stat_mana', 'Mana'); ?></span><span><?php echo translate('stat_not_available', 'Not Available'); ?></span></div>
                            <?php endif; ?>
                            <div class="stats-item"><span><?php echo translate('stat_strength', 'Strength'); ?></span><span><?= number_format($stats['strength']) ?></span></div>
                            <div class="stats-item"><span><?php echo translate('stat_agility', 'Agility'); ?></span><span><?= number_format($stats['agility']) ?></span></div>
                            <div class="stats-item"><span><?php echo translate('stat_stamina', 'Stamina'); ?></span><span><?= number_format($stats['stamina']) ?></span></div>
                            <div class="stats-item"><span><?php echo translate('stat_intellect', 'Intellect'); ?></span><span><?= number_format($stats['intellect']) ?></span></div>
                            <div class="stats-item"><span><?php echo translate('stat_spirit', 'Spirit'); ?></span><span><?= number_format($stats['spirit']) ?></span></div>
                        </div>
                        <div class="stats-category">
                            <h3><i class="fas fa-shield mr-2"></i><?php echo translate('stats_defense', 'Defense'); ?></h3>
                            <div class="stats-item"><span><?php echo translate('stat_armor', 'Armor'); ?></span><span><?= number_format($stats['armor']) ?></span></div>
                            <div class="stats-item"><span><?php echo translate('stat_block', 'Block'); ?></span><span><?= number_format($stats['blockPct'], 2) ?>%</span></div>
                            <div class="stats-item"><span><?php echo translate('stat_dodge', 'Dodge'); ?></span><span><?= number_format($stats['dodgePct'], 2) ?>%</span></div>
                            <div class="stats-item"><span><?php echo translate('stat_parry', 'Parry'); ?></span><span><?= number_format($stats['parryPct'], 2) ?>%</span></div>
                            <div class="stats-item"><span><?php echo translate('stat_resilience', 'Resilience'); ?></span><span><?= number_format($stats['resilience']) ?></span></div>
                        </div>
                        <div class="stats-category">
                            <h3><i class="fas fa-sword mr-2"></i><?php echo translate('stats_melee', 'Melee'); ?></h3>
                            <div class="stats-item"><span><?php echo translate('stat_attack_power', 'Attack Power'); ?></span><span><?= number_format($stats['attackPower']) ?></span></div>
                            <div class="stats-item"><span><?php echo translate('stat_crit_chance', 'Crit Chance'); ?></span><span><?= number_format($stats['critPct'], 2) ?>%</span></div>
                            <div class="stats-item"><span><?php echo translate('stat_ranged_attack_power', 'Attack Power'); ?></span><span><?= number_format($stats['rangedAttackPower']) ?></span></div>
                            <div class="stats-item"><span><?php echo translate('stat_ranged_crit_chance', 'Crit Chance'); ?></span><span><?= number_format($stats['rangedCritPct'], 2) ?>%</span></div>
                            <div class="stats-item"><span><?php echo translate('stat_spell_crit_chance', 'Spell Crit'); ?></span><span><?= number_format($stats['spellCritPct'], 2) ?>%</span></div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-400"><?php echo translate('stats_none', 'No Stats Available'); ?></div>
                <?php endif; ?>
            </div>

            <!-- Talents Tab -->
            <div id="talents-tab" class="tab-content mt-6">
                <div class="text-center py-12 text-gray-400">
                    <i class="fas fa-star text-3xl block mb-3 text-[rgba(201,162,39,0.3)]"></i>
                    <?php echo translate('talents_coming_soon', 'Talents (Coming Soon)'); ?>
                </div>
            </div>

            <!-- PVP Tab -->
            <div id="pvp-tab" class="tab-content mt-6">
                <?php if (!empty($pvp_teams)): ?>
                    <div class="pvp-section">
                        <?php foreach ($pvp_teams as $team): 
                            $teamType = $team['type'] == 2 ? '2v2' : ($team['type'] == 3 ? '3v3' : '5v5');
                            $teamIcon = $team['type'] == 2 ? 'fa-people-arrows' : ($team['type'] == 3 ? 'fa-users' : 'fa-users-cog');
                        ?>
                            <div class="pvp-card">
                                <div class="pvp-card-header">
                                    <div class="flex items-center gap-2">
                                        <div class="pvp-team-icon">
                                            <i class="fas <?= $teamIcon ?>"></i>
                                        </div>
                                        <span class="pvp-card-title"><?= htmlspecialchars($team['name']) ?></span>
                                    </div>
                                    <span class="pvp-card-rating">
                                        <i class="fas fa-star text-xs mr-1"></i>
                                        <?= $team['rating'] ?>
                                    </span>
                                </div>
                                
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="text-xs text-gray-500 uppercase tracking-wider">
                                        <i class="fas fa-tag mr-1"></i>
                                        <?= $teamType ?>
                                    </span>
                                    <span class="text-xs text-gray-500">
                                        <i class="fas fa-users mr-1"></i>
                                        <?= count($team['members']) ?>
                                    </span>
                                </div>

                                <div class="pvp-members-grid">
                                    <?php foreach ($team['members'] as $member): 
                                        $name = htmlspecialchars($member['name']);
                                        $faction = htmlspecialchars($member['faction']);
                                        $faction_icon = isset($member['faction_icon']) ? $member['faction_icon'] : 'unknown';
                                        $race = isset($races[$member['race']]) ? htmlspecialchars($races[$member['race']]['name']) : translate('race_unknown', 'Unknown');
                                        $race_icon_name = isset($races[$member['race']]) ? $races[$member['race']]['icon'] : 'unknown';
                                        $class = isset($classes[$member['class']]) ? htmlspecialchars($classes[$member['class']]['name']) : translate('class_unknown', 'Unknown');
                                        $class_icon_name = isset($classes[$member['class']]) ? $classes[$member['class']]['icon'] : 'unknown';
                                        $faction_icon_path = "{$base_path}img/accountimg/faction/$faction_icon.png";
                                        $gender_dir = ($member['gender'] ?? 0) == 0 ? 'male' : 'female';
                                        $race_icon_path = "{$base_path}img/accountimg/race/$gender_dir/$race_icon_name.png";
                                        $class_icon_path = "{$base_path}img/accountimg/class/$class_icon_name.webp";
                                    ?>
                                        <?php if ($member['guid'] == $character['guid']): ?>
                                            <div class="pvp-member current-player">
                                                <img src="<?= $faction_icon_path ?>" alt="<?= $faction ?>">
                                                <img src="<?= $race_icon_path ?>" alt="<?= $race ?>">
                                                <img src="<?= $class_icon_path ?>" alt="<?= $class ?>">
                                                <span class="pvp-member-name"><?= $name ?></span>
                                                <span class="text-xs text-[#f2cf5b]"><i class="fas fa-star"></i></span>
                                            </div>
                                        <?php else: ?>
                                            <a href="<?php echo $base_path; ?>character?guid=<?= $member['guid'] ?>" class="pvp-member-link">
                                                <div class="pvp-member">
                                                    <img src="<?= $faction_icon_path ?>" alt="<?= $faction ?>">
                                                    <img src="<?= $race_icon_path ?>" alt="<?= $race ?>">
                                                    <img src="<?= $class_icon_path ?>" alt="<?= $class ?>">
                                                    <span class="pvp-member-name"><?= $name ?></span>
                                                    <i class="fas fa-external-link-alt text-[#f2cf5b] text-xs opacity-40"></i>
                                                </div>
                                            </a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="pvp-empty">
                        <i class="fas fa-crosshairs"></i>
                        <p class="text-gray-400"><?php echo translate('pvp_none', 'No PvP Teams'); ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- Total Kills -->
                <div class="pvp-kills-container">
                    <span class="kills-number"><?= number_format($total_kills) ?></span>
                    <span class="kills-label">
                        <i class="fas fa-skull mr-2"></i>
                        <?php echo translate('pvp_total_kills', 'Total PvP Kills'); ?>
                    </span>
                </div>
            </div>

            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Tooltip
        const tooltip = document.createElement('div');
        tooltip.className = 'item-tooltip';
        document.body.appendChild(tooltip);
        
        const slots = document.querySelectorAll('.slot.has-item');
        slots.forEach(slot => {
            slot.addEventListener('mouseenter', (e) => {
                const content = slot.dataset.tooltip;
                if (content) {
                    tooltip.innerHTML = content;
                    tooltip.style.display = 'block';
                    updateTooltipPosition(e);
                }
            });
            slot.addEventListener('mousemove', updateTooltipPosition);
            slot.addEventListener('mouseleave', () => {
                tooltip.style.display = 'none';
            });
        });
        
        function updateTooltipPosition(e) {
            const x = e.clientX + 10;
            const y = e.clientY + 10;
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

        // Tabs
        const tabs = document.querySelectorAll('.tab-btn');
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

<?php include_once $project_root . 'includes/footer.php'; ?>
</body>
</html>