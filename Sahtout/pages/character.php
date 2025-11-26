<?php
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../includes/paths.php'; // Include paths.php
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
    
    <style>
    :root{
            --bg-character:url('<?php echo $base_path; ?>img/backgrounds/bg-character.jpg');
            --hover-wow-gif: url('<?php echo $base_path; ?>img/hover_wow.gif');
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
    0 => 'head',
    1 => 'neck',
    2 => 'shoulders',
    3 => 'body',
    4 => 'chest',
    5 => 'waist',
    6 => 'legs',
    7 => 'feet',
    8 => 'wrists',
    9 => 'hands',
    10 => 'finger',
    11 => 'finger',
    12 => 'trinket',
    13 => 'trinket',
    14 => 'back',
    15 => 'main_hand',
    16 => 'off_hand',
    17 => 'ranged',
    18 => 'tabard'
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
    0 => 'head.gif',
    1 => 'neck.gif',
    2 => 'shoulders.gif',
    3 => 'body.gif',
    4 => 'chest.gif',
    5 => 'waist.gif',
    6 => 'legs.gif',
    7 => 'feet.gif',
    8 => 'wrists.gif',
    9 => 'hands.gif',
    10 => 'finger.gif',
    11 => 'finger.gif',
    12 => 'trinket.gif',
    13 => 'trinket.gif',
    14 => 'back.gif',
    15 => 'mainhand.gif',
    16 => 'offhand.gif',
    17 => 'ranged.gif',
    18 => 'tabard.gif'
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
        error_log("character.php: Database connection ($char_db) not initialized for guid=$guid");
    } else {
        // Fetch character data
        $stmt = $char_db->prepare("SELECT guid, name, race, class, level, totalKills, gender FROM characters WHERE guid = ?");
        if (!$stmt) {
            $error = translate('error_prepare_character_query', 'Failed to prepare character query.');
            error_log("character.php: Failed to prepare character query for guid=$guid: " . $char_db->error);
        } else {
            $stmt->bind_param("i", $guid);
            if (!$stmt->execute()) {
                $error = translate('error_execute_character_query', 'Failed to execute character query.');
                error_log("character.php: Character query execution failed for guid=$guid: " . $stmt->error);
            } else {
                $result = $stmt->get_result();
                $character = $result->fetch_assoc();
                if (!$character) {
                    $error = translate('error_character_not_found', 'Character not found for GUID {guid}.');
                    $error = str_replace('{guid}', $guid, $error);
                    error_log("character.php: No character found for guid=$guid");
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
                $error = translate('error_prepare_stats_query', 'Failed to prepare stats query.');
                error_log("character.php: Failed to prepare stats query for guid=$guid: " . $char_db->error);
            } else {
                $stmt->bind_param("i", $guid);
                if (!$stmt->execute()) {
                    $error = translate('error_execute_stats_query', 'Failed to execute stats query.');
                    error_log("character.php: Stats query execution failed for guid=$guid: " . $stmt->error);
                } else {
                    $result = $stmt->get_result();
                    $stats = $result->fetch_assoc();
                    if (!$stats) {
                        $error = translate('error_stats_not_found', 'No stats found for GUID {guid}.');
                        $error = str_replace('{guid}', $guid, $error);
                        error_log("character.php: No stats found for guid=$guid");
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
                $error = translate('error_prepare_arena_query', 'Failed to prepare arena team query.');
                error_log("character.php: Failed to prepare arena team query for guid=$guid: " . $char_db->error);
            } else {
                $stmt->bind_param("i", $guid);
                if (!$stmt->execute()) {
                    $error = translate('error_execute_arena_query', 'Failed to execute arena team query.');
                    error_log("character.php: Arena team query execution failed for guid=$guid: " . $stmt->error);
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
                            $error = translate('error_prepare_arena_members_query', 'Failed to prepare arena team members query.');
                            error_log("character.php: Failed to prepare arena team members query for arenaTeamId={$team['arenaTeamId']}: " . $char_db->error);
                        } else {
                            $stmt->bind_param("i", $team['arenaTeamId']);
                            if (!$stmt->execute()) {
                                $error = translate('error_execute_arena_members_query', 'Failed to execute arena team members query.');
                                error_log("character.php: Arena team members query execution failed for arenaTeamId={$team['arenaTeamId']}: " . $stmt->error);
                            } else {
                                $result = $stmt->get_result();
                                $team['members'] = [];
                                while ($row = $result->fetch_assoc()) {
                                    $row['faction'] = isset($factions[$row['race']]) ? $factions[$row['race']]['name'] : translate('faction_unknown', 'Unknown');
                                    $row['faction_icon'] = isset($factions[$row['race']]) ? $factions[$row['race']]['icon'] : 'unknown';
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
                $error = translate('error_prepare_inventory_query', 'Failed to prepare inventory query.');
                error_log("character.php: Failed to prepare inventory query for guid=$guid: " . $char_db->error);
            } else {
                $stmt->bind_param("i", $guid);
                if (!$stmt->execute()) {
                    $error = translate('error_execute_inventory_query', 'Failed to execute inventory query.');
                    error_log("character.php: Inventory query execution failed for guid=$guid: " . $stmt->error);
                } else {
                    $result = $stmt->get_result();
                    $itemEntries = [];
                    while ($row = $result->fetch_assoc()) {
                        $itemEntries[$row['slot']] = $row['itemEntry'];
                    }
                    $stmt->close();
                    if (empty($itemEntries)) {
                        error_log("character.php: No equipped items found for guid=$guid in character_inventory with bag=0");
                    } else {
                        if (!isset($world_db) || !$world_db) {
                            $error = translate('error_db_connection_world', 'Database connection (world) is not available.');
                            error_log("character.php: Database connection ($world_db) not initialized for guid=$guid");
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
                                $error = translate('error_prepare_item_query', 'Failed to prepare item template query.');
                                error_log("character.php: Failed to prepare item_template query for guid=$guid: " . $world_db->error);
                            } else {
                                $itemEntryValues = array_values($itemEntries);
                                $stmt->bind_param(str_repeat('i', count($itemEntries)), ...$itemEntryValues);
                                if (!$stmt->execute()) {
                                    $error = translate('error_execute_item_query', 'Failed to execute item template query.');
                                    error_log("character.php: item_template query execution failed for guid=$guid: " . $stmt->error);
                                } else {
                                    $result = $stmt->get_result();
                                    while ($row = $result->fetch_assoc()) {
                                        $slot = array_search($row['entry'], $itemEntries);
                                        if (empty($row['icon'])) {
                                            error_log("character.php: No icon in itemdisplayinfo_dbc for itemEntry={$row['entry']} (slot=$slot, displayid={$row['displayid']})");
                                        } else {
                                            $row['icon'] = strtolower($row['icon']);
                                        }
                                        $items[$slot] = $row;
                                    }
                                    $stmt->close();
                                    foreach ($itemEntries as $slot => $entry) {
                                        if (!isset($items[$slot])) {
                                            error_log("character.php: No item_template entry found for itemEntry=$entry in slot=$slot for guid=$guid");
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
    $error = translate('error_invalid_guid', 'Invalid or missing GUID parameter.');
    error_log("character.php: Invalid or missing guid parameter: " . ($_GET['guid'] ?? 'none'));
}
?>
<main>
    <div class="character-container">
        <div class="equipment-column equipment-left">
            <?php foreach ([0, 1, 2, 14, 4, 3, 18, 8] as $slot): ?>
                <div class="slot<?= isset($items[$slot]) ? ' has-item' : '' ?>" <?= isset($items[$slot]) ? 'data-tooltip="' . htmlspecialchars(generateTooltip($items[$slot])) . '"' : '' ?>>
                    <div class="slot-icon">
                        <?php
                        $icon = isset($items[$slot]) && !empty($items[$slot]['icon']) ? $items[$slot]['icon'] : ($defaultIcons[$slot] ?? 'inv_misc_questionmark');
                        $iconSrc = isset($items[$slot]) && !empty($items[$slot]['icon']) ? "https://wow.zamimg.com/images/wow/icons/large/$icon.jpg" : "{$base_path}img/characterarmor/$icon";
                        ?>
                        <img src="<?= htmlspecialchars($iconSrc) ?>" alt="<?= htmlspecialchars($slotLabels[$slot]) ?>" loading="lazy">
                    </div>
                    <div class="slot-info">
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
        <div class="character-center">
            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php else: ?>
                <div class="character-name"><?= htmlspecialchars($character['name'] ?? 'Unknown') ?></div>
                <div class="character-details">
                    <span class="character-level"><?php echo translate('level_label', 'Level'); ?> <?= $character['level'] ?? '??' ?> <?= isset($classes[$character['class']]) ? htmlspecialchars($classes[$character['class']]['name']) : translate('class_unknown', 'Unknown') ?></span>
                    <span class="character-race"><?= isset($races[$character['race']]) ? htmlspecialchars($races[$character['race']]['name']) : translate('race_unknown', 'Unknown') ?></span>
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

                        // Dynamic model path based on race and gender
                        <?php
                        $raceIcon = isset($races[$character['race']]) ? $races[$character['race']]['icon'] : 'unknown';
                        $gender = ($character['gender'] ?? 0) == 0 ? 'male' : 'female';
                        $modelPath = "$base_path/3dmodels/character/$raceIcon/$gender/$raceIcon.gltf";
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
                                $iconSrc = isset($items[$slot]) && !empty($items[$slot]['icon']) ? "https://wow.zamimg.com/images/wow/icons/large/$icon.jpg" : "{$base_path}img/characterarmor/$icon";
                                ?>
                                <img src="<?= htmlspecialchars($iconSrc) ?>" alt="<?= htmlspecialchars($slotLabels[$slot]) ?>" loading="lazy">
                            </div>
                            <div class="slot-info">
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
            <?php endif; ?>
        </div>
        <div class="equipment-column equipment-right">
            <?php foreach ([9, 5, 6, 7, 10, 11, 12, 13] as $slot): ?>
                <div class="slot<?= isset($items[$slot]) ? ' has-item' : '' ?>" <?= isset($items[$slot]) ? 'data-tooltip="' . htmlspecialchars(generateTooltip($items[$slot])) . '"' : '' ?>>
                    <div class="slot-icon">
                        <?php
                        $icon = isset($items[$slot]) && !empty($items[$slot]['icon']) ? $items[$slot]['icon'] : ($defaultIcons[$slot] ?? 'inv_misc_questionmark');
                        $iconSrc = isset($items[$slot]) && !empty($items[$slot]['icon']) ? "https://wow.zamimg.com/images/wow/icons/large/$icon.jpg" : "{$base_path}img/characterarmor/$icon";
                        ?>
                        <img src="<?= htmlspecialchars($iconSrc) ?>" alt="<?= htmlspecialchars($slotLabels[$slot]) ?>" loading="lazy">
                    </div>
                    <div class="slot-info">
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
    <?php if (!$error): ?>
        <div class="tab-nav">
            <button data-tab="stats-tab" class="active"><?php echo translate('tab_stats', 'Stats'); ?></button>
            <button data-tab="talents-tab" class=""><?php echo translate('tab_talents', 'Talents'); ?></button>
            <button data-tab="pvp-tab" class=""><?php echo translate('tab_pvp', 'PVP'); ?></button>
        </div>
        <div id="stats-tab" class="tab-content active">
            <?php if ($stats): ?>
                <div class="stats-container">
                    <div class="stats-category">
                        <h3><?php echo translate('stats_base', 'Base Stats'); ?></h3>
                        <div class="stats-item"><span><?php echo translate('stat_health', 'Health'); ?></span><span><?= number_format($stats['maxhealth']) ?></span></div>
                        <?php
                        // Always display Mana if available
                        if ($stats['maxpower1'] > 0):
                        ?>
                            <div class="stats-item"><span><?php echo translate('stat_mana', 'Mana'); ?></span><span><?= number_format($stats['maxpower1']) ?></span></div>
                        <?php else: ?>
                            <div class="stats-item"><span><?php echo translate('stat_mana', 'Mana'); ?></span><span><?php echo translate('stat_not_available', 'Not Available'); ?></span></div>
                        <?php endif; ?>
                        <?php
                        // Map class IDs to their primary power type indices (using PowerType IDs)
                        $classPowerMap = [
                            1 => 1, // Warrior: Rage (maxpower2, PowerType 1)
                            2 => 0, // Paladin: Mana (maxpower1, PowerType 0)
                            3 => 2, // Hunter: Focus (maxpower3, PowerType 2)
                            4 => 3, // Rogue: Energy (maxpower4, PowerType 3)
                            5 => 0, // Priest: Mana (maxpower1, PowerType 0)
                            6 => 6, // Death Knight: Runic Power (maxpower7, PowerType 6)
                            7 => 0, // Shaman: Mana (maxpower1, PowerType 0)
                            8 => 0, // Mage: Mana (maxpower1, PowerType 0)
                            9 => 0, // Warlock: Mana (maxpower1, PowerType 0)
                            11 => 0 // Druid: Mana (maxpower1, PowerType 0)
                        ];
                        $powerIndex = isset($classPowerMap[$character['class']]) ? $classPowerMap[$character['class']] : 0;
                        // Adjust Rage and Runic Power for display (divide by 10)
                        $displayPowerValue = $stats["maxpower" . ($powerIndex + 1)];
                        if ($powerIndex == 1 && $stats['maxpower2'] > 0) { // Warrior: Rage
                            $displayPowerValue = $stats['maxpower2'] / 10;
                            if ($stats['maxpower2'] > 1000) {
                                error_log("character.php: High Rage value for Warrior guid=$guid: maxpower2={$stats['maxpower2']} (displayed as " . number_format($displayPowerValue) . ")");
                            }
                        } elseif ($powerIndex == 6 && $stats['maxpower7'] > 0) { // Death Knight: Runic Power
                            $displayPowerValue = $stats['maxpower7'] / 10;
                            if ($stats['maxpower7'] > 1000) {
                                error_log("character.php: High Runic Power value for Death Knight guid=$guid: maxpower7={$stats['maxpower7']} (displayed as " . number_format($displayPowerValue) . ")");
                            }
                        }
                        // Only display class-specific power type if it's not Mana (to avoid duplication)
                        if ($powerIndex > 0 && $stats["maxpower" . ($powerIndex + 1)] > 0):
                        ?>
                            <div class="stats-item"><span><?= htmlspecialchars($powerTypes[$powerIndex]) ?></span><span><?= number_format($displayPowerValue) ?></span></div>
                        <?php endif; ?>
                        <div class="stats-item"><span><?php echo translate('stat_strength', 'Strength'); ?></span><span><?= number_format($stats['strength']) ?></span></div>
                        <div class="stats-item"><span><?php echo translate('stat_agility', 'Agility'); ?></span><span><?= number_format($stats['agility']) ?></span></div>
                        <div class="stats-item"><span><?php echo translate('stat_stamina', 'Stamina'); ?></span><span><?= number_format($stats['stamina']) ?></span></div>
                        <div class="stats-item"><span><?php echo translate('stat_intellect', 'Intellect'); ?></span><span><?= number_format($stats['intellect']) ?></span></div>
                        <div class="stats-item"><span><?php echo translate('stat_spirit', 'Spirit'); ?></span><span><?= number_format($stats['spirit']) ?></span></div>
                    </div>
                    <div class="stats-category">
                        <h3><?php echo translate('stats_defense', 'Defense'); ?></h3>
                        <div class="stats-item"><span><?php echo translate('stat_armor', 'Armor'); ?></span><span><?= number_format($stats['armor']) ?></span></div>
                        <div class="stats-item"><span><?php echo translate('stat_block', 'Block'); ?></span><span><?= number_format($stats['blockPct'], 2) ?>%</span></div>
                        <div class="stats-item"><span><?php echo translate('stat_dodge', 'Dodge'); ?></span><span><?= number_format($stats['dodgePct'], 2) ?>%</span></div>
                        <div class="stats-item"><span><?php echo translate('stat_parry', 'Parry'); ?></span><span><?= number_format($stats['parryPct'], 2) ?>%</span></div>
                        <div class="stats-item"><span><?php echo translate('stat_resilience', 'Resilience'); ?></span><span><?= number_format($stats['resilience']) ?></span></div>
                    </div>
                    <div class="stats-category">
                        <h3><?php echo translate('stats_melee', 'Melee'); ?></h3>
                        <div class="stats-item"><span><?php echo translate('stat_attack_power', 'Attack Power'); ?></span><span><?= number_format($stats['attackPower']) ?></span></div>
                        <div class="stats-item"><span><?php echo translate('stat_crit_chance', 'Crit Chance'); ?></span><span><?= number_format($stats['critPct'], 2) ?>%</span></div>
                    </div>
                    <div class="stats-category">
                        <h3><?php echo translate('stats_ranged', 'Ranged'); ?></h3>
                        <div class="stats-item"><span><?php echo translate('stat_ranged_attack_power', 'Attack Power'); ?></span><span><?= number_format($stats['rangedAttackPower']) ?></span></div>
                        <div class="stats-item"><span><?php echo translate('stat_ranged_crit_chance', 'Crit Chance'); ?></span><span><?= number_format($stats['rangedCritPct'], 2) ?>%</span></div>
                    </div>
                    <div class="stats-category">
                        <h3><?php echo translate('stats_resistances', 'Resistances'); ?></h3>
                        <div class="stats-item"><span><?php echo translate('stat_holy_resistance', 'Holy Resistance'); ?></span><span><?= number_format($stats['resHoly']) ?></span></div>
                        <div class="stats-item"><span><?php echo translate('stat_fire_resistance', 'Fire Resistance'); ?></span><span><?= number_format($stats['resFire']) ?></span></div>
                        <div class="stats-item"><span><?php echo translate('stat_nature_resistance', 'Nature Resistance'); ?></span><span><?= number_format($stats['resNature']) ?></span></div>
                        <div class="stats-item"><span><?php echo translate('stat_frost_resistance', 'Frost Resistance'); ?></span><span><?= number_format($stats['resFrost']) ?></span></div>
                        <div class="stats-item"><span><?php echo translate('stat_shadow_resistance', 'Shadow Resistance'); ?></span><span><?= number_format($stats['resShadow']) ?></span></div>
                        <div class="stats-item"><span><?php echo translate('stat_arcane_resistance', 'Arcane Resistance'); ?></span><span><?= number_format($stats['resArcane']) ?></span></div>
                    </div>
                </div>
            <?php else: ?>
                <div class="pvp-team"><?php echo translate('stats_none', 'No Stats Available'); ?></div>
            <?php endif; ?>
        </div>
        <div id="talents-tab" class="tab-content">
            <div class="pvp-team"><?php echo translate('talents_coming_soon', 'Talents (Coming Soon)'); ?></div>
        </div>
        <div id="pvp-tab" class="tab-content">
            <?php if (!empty($pvp_teams)): ?>
                <?php foreach ($pvp_teams as $team): ?>
                    <div class="pvp-team-item">
                        <div class="pvp-team">
                            <?= htmlspecialchars($team['name']) ?> (<?= $team['type'] == 2 ? '2v2' : ($team['type'] == 3 ? '3v3' : '5v5') ?>, <?php echo translate('pvp_rating', 'Rating'); ?>: <?= $team['rating'] ?>)
                        </div>
                        <div class="pvp-members">
                            <ul>
                                <?php
                                foreach ($team['members'] as $member) {
                                    $name = htmlspecialchars($member['name']);
                                    $faction = htmlspecialchars($member['faction']);
                                    $faction_icon = isset($member['faction_icon']) ? $member['faction_icon'] : 'unknown';
                                    $race = isset($races[$member['race']]) ? htmlspecialchars($races[$member['race']]['name']) : translate('race_unknown', 'Unknown');
                                    $race_icon_name = isset($races[$member['race']]) ? $races[$member['race']]['icon'] : 'unknown';
                                    $class = isset($classes[$member['class']]) ? htmlspecialchars($classes[$member['class']]['name']) : translate('class_unknown', 'Unknown');
                                    $class_icon_name = isset($classes[$member['class']]) ? $classes[$member['class']]['icon'] : 'unknown';
                                    $class_abbr = isset($class_abbr[$class]) ? $class_abbr[$class] : substr($class, 0, 3);
                                    $faction_icon = "{$base_path}img/accountimg/faction/$faction_icon.png";
                                    $gender_dir = ($member['gender'] ?? 0) == 0 ? 'male' : 'female';
                                    $race_icon = "{$base_path}img/accountimg/race/$gender_dir/$race_icon_name.png";
                                    $class_icon = "{$base_path}img/accountimg/class/$class_icon_name.webp";
                                    if ($member['guid'] == $character['guid']) {
                                        echo "<li class=\"current-player\">";
                                        echo "$name <span class=\"member-details $faction\">";
                                        echo "<img src=\"$faction_icon\" alt=\"$faction\" title=\"$faction\" class=\"inline-block\">";
                                        echo "<img src=\"$race_icon\" alt=\"$race\" title=\"$race\" class=\"inline-block\">";
                                        echo "<img src=\"$class_icon\" alt=\"$class\" title=\"$class\" class=\"inline-block\">";
                                        echo "</span></li>";
                                    } else {
                                        echo "<a href=\"{$base_path}character?guid={$member['guid']}\" class=\"pvp-members-link\">";
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
                <div class="pvp-team"><?php echo translate('pvp_none', 'No PvP Teams'); ?></div>
            <?php endif; ?>
            <div class="pvp-kills"><?php echo translate('pvp_total_kills', 'Total PvP Kills'); ?>: <span><?= number_format($total_kills) ?></span></div>
        </div>
    <?php endif; ?>
    <div style="clear: both;"></div>
</main>
<?php include_once $project_root . 'includes/footer.php'; ?>
</body>
</html>