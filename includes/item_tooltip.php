<?php
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access to this file is not allowed.');
}
require_once __DIR__ . '/paths.php'; // Include paths.php

// Include the language system
require_once $project_root . 'languages/language.php';

// Data definitions
$qualityColors = [
    0 => '#9d9d9d', // Poor (Grey)
    1 => '#ffffff', // Common (White)
    2 => '#1eff00', // Uncommon (Green)
    3 => '#0070dd', // Rare (Blue)
    4 => '#a335ee', // Epic (Purple)
    5 => '#ff8000', // Legendary (Orange)
    6 => '#e6cc80', // Artifact (Red)
    7 => '#e6cc80'  // Bind to Account (Gold)
];

$bondingTypes = [
    0 => null,
    1 => translate('bonding_pickup', 'Binds when picked up'),
    2 => translate('bonding_equip', 'Binds when equipped'),
    3 => translate('bonding_use', 'Binds when used'),
    4 => translate('bonding_quest', 'Quest Item'),
    5 => translate('bonding_quest', 'Quest Item'),
    6 => translate('bonding_account', 'Binds to account')
];

$inventoryTypes = [
    0 => null,
    1 => translate('slot_head', 'Head'), 
    2 => translate('slot_neck', 'Neck'), 
    3 => translate('slot_shoulder', 'Shoulder'), 
    4 => translate('slot_shirt', 'Shirt'), 
    5 => translate('slot_chest', 'Chest'),
    6 => translate('slot_waist', 'Waist'), 
    7 => translate('slot_legs', 'Legs'), 
    8 => translate('slot_feet', 'Feet'), 
    9 => translate('slot_wrist', 'Wrist'), 
    10 => translate('slot_hands', 'Hands'),
    11 => translate('slot_finger', 'Finger'), 
    12 => translate('slot_trinket', 'Trinket'), 
    13 => translate('slot_onehand', 'One-Hand'), 
    14 => translate('slot_shield', 'Shield'),
    15 => translate('slot_ranged', 'Ranged'), 
    16 => translate('slot_back', 'Back'), 
    17 => translate('slot_twohand', 'Two-Hand'), 
    18 => translate('slot_bag', 'Bag'), 
    19 => translate('slot_tabard', 'Tabard'),
    20 => translate('slot_robe', 'Robe'), 
    21 => translate('slot_mainhand', 'Main Hand'), 
    22 => translate('slot_offhand', 'Off Hand'), 
    23 => translate('slot_holdable', 'Holdable'),
    25 => translate('slot_thrown', 'Thrown'), 
    26 => translate('slot_ranged', 'Ranged'), 
    28 => translate('slot_relic', 'Relic')
];

$classNames = [
    0 => translate('class_consumable', 'Consumable'), 
    1 => translate('class_container', 'Container'), 
    2 => translate('class_weapon', 'Weapon'), 
    3 => translate('class_gem', 'Gem'), 
    4 => translate('class_armor', 'Armor'),
    5 => translate('class_reagent', 'Reagent'), 
    6 => translate('class_projectile', 'Projectile'), 
    7 => translate('class_tradegoods', 'Trade Goods'), 
    8 => translate('class_generic', 'Generic'), 
    9 => translate('class_recipe', 'Recipe'),
    10 => translate('class_money', 'Money'), 
    11 => translate('class_quiver', 'Quiver'), 
    12 => translate('class_quest', 'Quest'), 
    13 => translate('class_key', 'Key'), 
    14 => translate('class_permanent', 'Permanent'),
    15 => translate('class_misc', 'Miscellaneous'), 
    16 => translate('class_glyph', 'Glyph')
];

$subclassNames = [
    2 => [
        0 => translate('weapon_axe', 'Axe'), 
        1 => translate('weapon_axe2h', 'Axe (2H)'), 
        2 => translate('weapon_bow', 'Bow'), 
        3 => translate('weapon_gun', 'Gun'), 
        4 => translate('weapon_mace', 'Mace'), 
        5 => translate('weapon_mace2h', 'Mace (2H)'),
        6 => translate('weapon_polearm', 'Polearm'), 
        7 => translate('weapon_sword', 'Sword'), 
        8 => translate('weapon_sword2h', 'Sword (2H)'), 
        10 => translate('weapon_staff', 'Staff'), 
        13 => translate('weapon_fist', 'Fist Weapon'),
        14 => translate('weapon_misc', 'Miscellaneous'), 
        15 => translate('weapon_dagger', 'Dagger'), 
        16 => translate('weapon_thrown', 'Thrown'),
        17 => translate('weapon_spear', 'Spear'),
        18 => translate('weapon_crossbow', 'Crossbow'), 
        19 => translate('weapon_wand', 'Wand'), 
        20 => translate('weapon_fishingpole', 'Fishing Pole')
    ],
    4 => [
        0 => translate('armor_misc', 'Miscellaneous'), 
        1 => translate('armor_cloth', 'Cloth'), 
        2 => translate('armor_leather', 'Leather'), 
        3 => translate('armor_mail', 'Mail'), 
        4 => translate('armor_plate', 'Plate'),
        6 => translate('armor_shield', 'Shield'), 
        7 => translate('armor_libram', 'Libram'), 
        8 => translate('armor_idol', 'Idol'), 
        9 => translate('armor_totem', 'Totem'), 
        10 => translate('armor_sigil', 'Sigil')
    ]
];

$triggerFlags = [
    0 => translate('trigger_use', 'Use'),
    1 => translate('trigger_equip', 'Equip'),
    2 => translate('trigger_chance', 'Chance on hit'),
    4 => translate('trigger_soulstone', 'Soulstone')
];

$normalStats = [
    0 => translate('stat_mana', 'Mana'),
    1 => translate('stat_health', 'Health'),
    3 => translate('stat_agility', 'Agility'),
    4 => translate('stat_strength', 'Strength'),
    5 => translate('stat_intellect', 'Intellect'),
    6 => translate('stat_spirit', 'Spirit'),
    7 => translate('stat_stamina', 'Stamina')
];

$specialStats = [
    12 => translate('stat_defense', 'Defense Rating'),
    13 => translate('stat_dodge', 'Dodge Rating'),
    14 => translate('stat_parry', 'Parry Rating'),
    15 => translate('stat_block', 'Block Rating'),
    16 => translate('stat_hit_melee', 'Hit (Melee) Rating'),
    17 => translate('stat_hit_ranged', 'Hit (Ranged) Rating'),
    18 => translate('stat_hit_spell', 'Hit (Spell) Rating'),
    19 => translate('stat_crit_melee', 'Crit (Melee) Rating'),
    20 => translate('stat_crit_ranged', 'Crit (Ranged) Rating'),
    21 => translate('stat_crit_spell', 'Crit (Spell) Rating'),
    22 => translate('stat_hit_taken_melee', 'Hit Taken (Melee) Rating'),
    23 => translate('stat_hit_taken_ranged', 'Hit Taken (Ranged) Rating'),
    24 => translate('stat_hit_taken_spell', 'Hit Taken (Spell) Rating'),
    25 => translate('stat_crit_taken_melee', 'Crit Taken (Melee) Rating'),
    26 => translate('stat_crit_taken_ranged', 'Crit Taken (Ranged) Rating'),
    27 => translate('stat_crit_taken_spell', 'Crit Taken (Spell) Rating'),
    28 => translate('stat_haste_melee', 'Haste (Melee) Rating'),
    29 => translate('stat_haste_ranged', 'Haste (Ranged) Rating'),
    30 => translate('stat_haste_spell', 'Haste (Spell) Rating'),
    31 => translate('stat_hit', 'Hit Rating'),
    32 => translate('stat_crit', 'Crit Rating'),
    33 => translate('stat_hit_taken', 'Hit Taken Rating'),
    34 => translate('stat_crit_taken', 'Crit Taken Rating'),
    35 => translate('stat_resilience', 'Resilience Rating'),
    36 => translate('stat_haste', 'Haste Rating'),
    37 => translate('stat_expertise', 'Expertise Rating'),
    38 => translate('stat_attack_power', 'Attack Power'),
    39 => translate('stat_ranged_attack_power', 'Ranged Attack Power'),
    40 => translate('stat_feral_attack_power', 'Feral Attack Power'),
    41 => translate('stat_healing_power', 'Healing Power'),
    42 => translate('stat_spell_damage', 'Spell Damage'),
    43 => translate('stat_mana_regen', 'Mana Regen'),
    44 => translate('stat_armor_pen', 'Armor Penetration Rating'),
    45 => translate('stat_spell_power', 'Spell Power'),
    46 => translate('stat_health_regen', 'Health Regen'),
    47 => translate('stat_spell_pen', 'Spell Penetration'),
    48 => translate('stat_block_value', 'Block Value')
];

$socketColors = [
    1 => ['name' => translate('socket_meta', 'Meta'), 'icon' => $base_path . 'img/shopimg/items/socketicons/socket_meta.gif'],
    2 => ['name' => translate('socket_red', 'Red'), 'icon' => $base_path . 'img/shopimg/items/socketicons/socket_red.gif'],
    4 => ['name' => translate('socket_yellow', 'Yellow'), 'icon' => $base_path . 'img/shopimg/items/socketicons/socket_yellow.gif'],
    8 => ['name' => translate('socket_blue', 'Blue'), 'icon' => $base_path . 'img/shopimg/items/socketicons/socket_blue.gif']
];

$classRestrictions = [
    1 => translate('class_warrior', 'Warrior'),
    2 => translate('class_paladin', 'Paladin'),
    4 => translate('class_hunter', 'Hunter'),
    8 => translate('class_rogue', 'Rogue'),
    16 => translate('class_priest', 'Priest'),
    32 => translate('class_dk', 'Death Knight'),
    64 => translate('class_shaman', 'Shaman'),
    128 => translate('class_mage', 'Mage'),
    256 => translate('class_warlock', 'Warlock'),
    1024 => translate('class_druid', 'Druid')
];

$classColors = [
    1 => '#C69B6D', // Warrior: Tan
    2 => '#F48CBA', // Paladin: Pink
    4 => '#AAD372', // Hunter: Pistachio
    8 => '#FFF468', // Rogue: Yellow
    16 => '#FFFFFF', // Priest: White
    32 => '#C41E3A', // Death Knight: Red
    64 => '#0070DD', // Shaman: Blue
    128 => '#3FC7EB', // Mage: Light Blue
    256 => '#8788EE', // Warlock: Purple
    1024 => '#FF7C0A' // Druid: Orange
];

// Helpers
function goldSilverCopper($amount) {
    $g = floor($amount / 10000);
    $s = floor(($amount % 10000) / 100);
    $c = $amount % 100;
    return "$g <span style='color:#ffd700;'>" . translate('gold_abbr', 'g') . "</span> $s <span style='color:#c0c0c0;'>" . translate('silver_abbr', 's') . "</span> $c <span style='color:#b87333;'>" . translate('copper_abbr', 'c') . "</span>";
}

function formatDPS($min, $max, $delay) {
    if ($delay <= 0) return '';
    $dps = ($min + $max) / 2 / ($delay / 1000);
    return number_format($dps, 1);
}

// Tooltip function
function generateTooltip($item) {
    global $qualityColors, $bondingTypes, $inventoryTypes, $classNames, $subclassNames, $normalStats, $specialStats, $socketColors, $classRestrictions, $classColors, $triggerFlags, $world_db, $base_path;

    // Set item name color based on quality
    $itemColor = $qualityColors[$item['Quality']] ?? '#ffffff';
    if ($item['Quality'] == 7 && ($item['flags'] & 134221824) == 134221824) {
        $itemColor = '#e6cc80';
    }

    $name = htmlspecialchars($item['name']);
    $desc = htmlspecialchars($item['description']);
    $level = $item['ItemLevel'];
    $reqLevel = $item['RequiredLevel'];
    $sell = $item['SellPrice'] ?? 0;
    $dur = $item['MaxDurability'] ?? 0;
    $speed = ($item['class'] == 2 && $item['delay'] > 0) ? round($item['delay'] / 1000, 2) : null;
    $bonding = $bondingTypes[$item['bonding']] ?? null;
    $className = $classNames[$item['class']] ?? translate('unknown', 'Unknown');
    $subclassName = $subclassNames[$item['class']][$item['subclass']] ?? null;
    $invType = $inventoryTypes[$item['InventoryType']] ?? null;

    // Class restrictions with colors
    $requiredClasses = [];
    if (isset($item['AllowableClass']) && $item['AllowableClass'] > 0) {
        foreach ($classRestrictions as $bit => $class) {
            if ($item['AllowableClass'] & $bit) {
                $color = $classColors[$bit] ?? '#ffffff';
                $requiredClasses[] = "<span style='color:$color;'>$class</span>";
            }
        }
    }
    $requiredClassesText = !empty($requiredClasses) ? translate('classes_label', 'Classes: ') . implode(', ', $requiredClasses) : null;

    // Fetch spell effects
    $spellEffects = [];
    $tableCheck = $world_db->query("SHOW TABLES LIKE 'armory_spell'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        for ($i = 1; $i <= 5; $i++) {
            $spellId = $item["spellid_$i"];
            $trigger = $item["spelltrigger_$i"];
            if ($spellId > 0) {
                if (in_array($trigger, [0, 1, 2, 4])) {
                    $stmt = $world_db->prepare("SELECT id, Description_en_gb, ToolTip_1 FROM armory_spell WHERE id = ?");
                    if ($stmt === false) {
                        error_log("Failed to prepare query for spell ID $spellId in item " . ($item['entry'] ?? 'unknown') . ": " . $world_db->error);
                        continue;
                    }
                    $stmt->bind_param("i", $spellId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($spell = $result->fetch_assoc()) {
                        $triggerText = $triggerFlags[$trigger] ?? translate('trigger_unknown', 'Unknown');
                        $description = !empty($spell['Description_en_gb']) ? htmlspecialchars($spell['Description_en_gb']) : htmlspecialchars($spell['ToolTip_1'] ?? '');
                        if (!empty($description)) {
                            $spellEffects[] = "$triggerText: $description";
                        }
                    }
                    $stmt->close();
                }
            }
        }
    }

    ob_start();
    ?>
    <style>
        .socket-icon {
            width: 10px;
            height: 10px;
            object-fit: contain;
            vertical-align: middle;
        }
        .item-name {
            color: <?= $itemColor ?> !important;
        }
        .tooltip-container {
            background: rgba(5, 7, 11, 0.95);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            border: 1px solid rgba(201,162,39,0.3);
            padding: 12px;
            width: 320px;
            color: #d1d5db;
            font-size: 12px;
            font-family: 'FrizQuadrata', Arial, sans-serif;
            box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.8);
        }
        .tooltip-container .item-name {
            font-weight: bold;
            font-size: 14px;
        }
        .tooltip-container .item-level {
            color: #e0b802;
        }
        .tooltip-container .item-bonding {
            color: #ffd700;
        }
        .tooltip-container .item-stat {
            color: #ffffff;
        }
        .tooltip-container .item-stat-green {
            color: #1eff00;
        }
        .tooltip-container .item-damage {
            color: #ffd100;
        }
        .tooltip-container .item-spell-effect {
            color: #00ff00;
        }
        .tooltip-container .item-socket-bonus {
            color: #888;
        }
        .tooltip-container .item-description {
            color: #eee;
            font-style: italic;
        }
        .tooltip-container .item-resistance {
            color: #1eff00;
        }
        .tooltip-container .item-requires {
            color: #ff8a8a;
        }
    </style>

    <div class="tooltip-container">
        <!-- Header: Name and Level -->
        <div class="flex justify-between items-start gap-2">
            <div>
                <div class="item-name"><?= $name ?></div>
                <?php if ($level): ?>
                    <div class="item-level text-xs"><?= translate('item_level', 'Item Level') ?> <?= $level ?></div>
                <?php endif; ?>
            </div>
            <div class="text-right text-xs">
                <?php if ($subclassName): ?>
                    <div><?= $subclassName ?></div>
                <?php endif; ?>
                <?php if ($speed): ?>
                    <div><?= translate('speed', 'Speed') ?> <?= $speed ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Bonding -->
        <?php if ($bonding): ?>
            <div class="item-bonding text-xs mt-1"><?= $bonding ?></div>
        <?php endif; ?>

        <!-- Item Type -->
        <div class="text-xs text-gray-400">
            <?php if ($invType): ?><?= $invType ?><?php endif; ?>
            <?php if ($className): ?><?= $className ?><?php endif; ?>
        </div>

        <!-- Damage -->
        <?php
        if ($item['dmg_min1'] > 0 && $item['dmg_max1'] > 0):
            $min = $item['dmg_min1'];
            $max = $item['dmg_max1'];
        ?>
            <div class="text-sm mt-1"><?= $min ?> - <?= $max ?> <?= translate('damage', 'Damage') ?></div>
            <div class="item-damage text-sm">(<?= formatDPS($min, $max, $item['delay']) ?> <?= translate('dps', 'damage per second') ?>)</div>
        <?php endif; ?>

        <!-- Armor -->
        <?php if ($item['armor'] > 0): ?>
            <div class="text-sm">+<?= $item['armor'] ?> <?= translate('armor', 'Armor') ?></div>
        <?php endif; ?>

        <!-- Normal Stats -->
        <?php for ($i = 1; $i <= 10; $i++):
            $type = $item["stat_type$i"];
            $value = $item["stat_value$i"];
            if ($type > 0 && $value != 0 && isset($normalStats[$type])): ?>
                <div class="item-stat text-sm">+<?= $value ?> <?= $normalStats[$type] ?></div>
        <?php endif; endfor; ?>

        <!-- Resistances -->
        <?php
        $resistances = [
            translate('resistance_holy', 'Holy') => $item['holy_res'], 
            translate('resistance_fire', 'Fire') => $item['fire_res'], 
            translate('resistance_nature', 'Nature') => $item['nature_res'],
            translate('resistance_frost', 'Frost') => $item['frost_res'], 
            translate('resistance_shadow', 'Shadow') => $item['shadow_res'], 
            translate('resistance_arcane', 'Arcane') => $item['arcane_res']
        ];
        foreach ($resistances as $school => $val):
            if ($val > 0): ?>
                <div class="item-resistance text-sm">+<?= $val ?> <?= $school ?> <?= translate('resistance', 'Resistance') ?></div>
        <?php endif; endforeach; ?>

        <!-- Sockets -->
        <div class="flex items-center gap-2 mt-1 flex-wrap">
            <?php for ($i = 1; $i <= 3; $i++): ?>
                <?php
                $colorCode = $item["socketColor_$i"] ?? null;
                if (isset($socketColors[$colorCode])):
                    $colorData = $socketColors[$colorCode];
                ?>
                    <div class="flex items-center gap-1">
                        <img src="<?= $colorData['icon'] ?>"
                             alt="<?= $colorData['name'] ?> <?= translate('socket', 'socket') ?>"
                             class="w-4 h-4 object-contain">
                        <span class="text-xs" style="color: <?= strtolower($colorData['name']) ?>;">
                            <?= $colorData['name'] ?>
                        </span>
                    </div>
                <?php endif; ?>
            <?php endfor; ?>
        </div>

        <!-- Socket Bonus -->
        <?php if (!empty($item['socketBonus'])): ?>
            <div class="item-socket-bonus text-xs mt-1"><?= translate('socket_bonus', 'Socket Bonus') ?>: <?= translate('spell_id', 'Spell ID') ?> <?= htmlspecialchars($item['socketBonus']) ?></div>
        <?php endif; ?>

        <!-- Durability -->
        <?php if ($dur > 0): ?>
            <div class="text-xs text-gray-400 mt-1"><?= translate('durability', 'Durability') ?> <?= $dur ?>/<?= $dur ?></div>
        <?php endif; ?>

        <!-- Required Level -->
        <?php if ($reqLevel): ?>
            <div class="item-requires text-sm"><?= translate('requires_level', 'Requires Level') ?> <?= $reqLevel ?></div>
        <?php endif; ?>

        <!-- Class Restrictions -->
        <?php if ($requiredClassesText): ?>
            <div class="text-sm"><?= $requiredClassesText ?></div>
        <?php endif; ?>

        <!-- Special Stats -->
        <?php for ($i = 1; $i <= 10; $i++):
            $type = $item["stat_type$i"];
            $value = $item["stat_value$i"];
            if ($type > 0 && $value != 0 && isset($specialStats[$type])): ?>
                <div class="item-spell-effect text-sm"><?= translate('equip', 'Equip') ?>: <?= translate('increases', 'Increases') ?> +<?= $value ?> <?= $specialStats[$type] ?></div>
        <?php endif; endfor; ?>

        <!-- Spell Effects -->
        <?php foreach ($spellEffects as $effect): ?>
            <div class="item-spell-effect text-sm"><?= $effect ?></div>
        <?php endforeach; ?>

        <!-- Sell Price -->
        <?php if ($sell > 0): ?>
            <div class="text-xs text-gray-400 mt-1"><?= translate('sell_price', 'Sell Price') ?>: <?= goldSilverCopper($sell) ?></div>
        <?php endif; ?>

        <!-- Description -->
        <?php if ($desc): ?>
            <div class="item-description text-sm mt-2 pt-2 border-t border-[rgba(201,162,39,0.1)]">"<?= $desc ?>"</div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
?>