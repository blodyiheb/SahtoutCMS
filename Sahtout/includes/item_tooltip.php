<?php
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access to this file is not allowed.');
}
?>
<?php
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
    1 => 'Binds when picked up',
    2 => 'Binds when equipped',
    3 => 'Binds when used',
    4 => 'Quest Item',
    5 => 'Quest Item',
    6 => 'Binds to account'
];

$inventoryTypes = [
    0 => null,
    1 => 'Head', 2 => 'Neck', 3 => 'Shoulder', 4 => 'Shirt', 5 => 'Chest',
    6 => 'Waist', 7 => 'Legs', 8 => 'Feet', 9 => 'Wrist', 10 => 'Hands',
    11 => 'Finger', 12 => 'Trinket', 13 => 'One-Hand', 14 => 'Shield',
    15 => 'Ranged', 16 => 'Back', 17 => 'Two-Hand', 18 => 'Bag', 19 => 'Tabard',
    20 => 'Robe', 21 => 'Main Hand', 22 => 'Off Hand', 23 => 'Holdable',
    25 => 'Thrown', 26 => 'Ranged', 28 => 'Relic'
];

$classNames = [
    0 => 'Consumable', 1 => 'Container', 2 => 'Weapon', 3 => 'Gem', 4 => 'Armor',
    5 => 'Reagent', 6 => 'Projectile', 7 => 'Trade Goods', 8 => 'Generic', 9 => 'Recipe',
    10 => 'Money', 11 => 'Quiver', 12 => 'Quest', 13 => 'Key', 14 => 'Permanent',
    15 => 'Miscellaneous', 16 => 'Glyph'
];

$subclassNames = [
    2 => [
        0 => 'Axe', 1 => 'Axe (2H)', 2 => 'Bow', 3 => 'Gun', 4 => 'Mace', 5 => 'Mace (2H)',
        6 => 'Polearm', 7 => 'Sword', 8 => 'Sword (2H)', 10 => 'Staff', 13 => 'Fist Weapon',
        14 => 'Miscellaneous', 15 => 'Dagger', 16 => 'Thrown', 17 => 'Spear',
        18 => 'Crossbow', 19 => 'Wand', 20 => 'Fishing Pole'
    ],
    4 => [
        0 => 'Miscellaneous', 1 => 'Cloth', 2 => 'Leather', 3 => 'Mail', 4 => 'Plate',
        6 => 'Shield', 7 => 'Libram', 8 => 'Idol', 9 => 'Totem', 10 => 'Sigil'
    ]
];

$triggerFlags = [
    0 => "Use",
    1 => "Equip",
    2 => "Chance on hit",
    4 => "Soulstone"
];

$normalStats = [
    0 => "Mana",
    1 => "Health",
    3 => "Agility",
    4 => "Strength",
    5 => "Intellect",
    6 => "Spirit",
    7 => "Stamina"
];

$specialStats = [
    12 => "Defense Rating",
    13 => "Dodge Rating",
    14 => "Parry Rating",
    15 => "Block Rating",
    16 => "Hit (Melee) Rating",
    17 => "Hit (Ranged) Rating",
    18 => "Hit (Spell) Rating",
    19 => "Crit (Melee) Rating",
    20 => "Crit (Ranged) Rating",
    21 => "Crit (Spell) Rating",
    22 => "Hit Taken (Melee) Rating",
    23 => "Hit Taken (Ranged) Rating",
    24 => "Hit Taken (Spell) Rating",
    25 => "Crit Taken (Melee) Rating",
    26 => "Crit Taken (Ranged) Rating",
    27 => "Crit Taken (Spell) Rating",
    28 => "Haste (Melee) Rating",
    29 => "Haste (Ranged) Rating",
    30 => "Haste (Spell) Rating",
    31 => "Hit Rating",
    32 => "Crit Rating",
    33 => "Hit Taken Rating",
    34 => "Crit Taken Rating",
    35 => "Resilience Rating",
    36 => "Haste Rating",
    37 => "Expertise Rating",
    38 => "Attack Power",
    39 => "Ranged Attack Power",
    40 => "Feral Attack Power",
    41 => "Healing Power",
    42 => "Spell Damage",
    43 => "Mana Regen",
    44 => "Armor Penetration Rating",
    45 => "Spell Power",
    46 => "Health Regen",
    47 => "Spell Penetration",
    48 => "Block Value"
];

$socketColors = [
    1 => ['name' => 'Meta', 'icon' => '/Sahtout/img/shopimg/items/socketicons/socket_meta.gif'],
    2 => ['name' => 'Red', 'icon' => '/Sahtout/img/shopimg/items/socketicons/socket_red.gif'],
    4 => ['name' => 'Yellow', 'icon' => '/Sahtout/img/shopimg/items/socketicons/socket_yellow.gif'],
    8 => ['name' => 'Blue', 'icon' => '/Sahtout/img/shopimg/items/socketicons/socket_blue.gif']
];

$classRestrictions = [
    1 => 'Warrior',
    2 => 'Paladin',
    4 => 'Hunter',
    8 => 'Rogue',
    16 => 'Priest',
    32 => 'Death Knight',
    64 => 'Shaman',
    128 => 'Mage',
    256 => 'Warlock',
    1024 => 'Druid'
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
    return "$g <span style='color:#ffd700;'>g</span> $s <span style='color:#c0c0c0;'>s</span> $c <span style='color:#b87333;'>c</span>";
}

function formatDPS($min, $max, $delay) {
    if ($delay <= 0) return '';
    $dps = ($min + $max) / 2 / ($delay / 1000);
    return number_format($dps, 1);
}

// Tooltip function
function generateTooltip($item) {
    global $qualityColors, $bondingTypes, $inventoryTypes, $classNames, $subclassNames, $normalStats, $specialStats, $socketColors, $classRestrictions, $classColors, $triggerFlags, $world_db;

    // Set item name color based on quality
    $itemColor = $qualityColors[$item['Quality']] ?? '#ffffff';
    if ($item['Quality'] == 7 && ($item['flags'] & 134221824) == 134221824) {
        $itemColor = '#e6cc80';
    }
    // Log item color for debugging
    error_log("item_tooltip.php: Item {$item['entry']} ({$item['name']}) Quality={$item['Quality']}, Color=$itemColor");

    $name = htmlspecialchars($item['name']);
    $desc = htmlspecialchars($item['description']);
    $level = $item['ItemLevel'];
    $reqLevel = $item['RequiredLevel'];
    $sell = $item['SellPrice'] ?? 0;
    $dur = $item['MaxDurability'] ?? 0;
    // Only calculate speed for weapons (class = 2)
    $speed = ($item['class'] == 2 && $item['delay'] > 0) ? round($item['delay'] / 1000, 2) : null;
    $bonding = $bondingTypes[$item['bonding']] ?? null;
    $className = $classNames[$item['class']] ?? 'Unknown';
    $subclassName = $subclassNames[$item['class']][$item['subclass']] ?? null;
    $invType = $inventoryTypes[$item['InventoryType']] ?? null;

    // Class restrictions with colors
    $requiredClasses = [];
    if (isset($item['AllowableClass']) && $item['AllowableClass'] > 0) {
        foreach ($classRestrictions as $bit => $class) {
            if ($item['AllowableClass'] & $bit) {
                $color = $classColors[$bit] ?? '#ffffff';
                $requiredClasses[] = "<span style='color:$color;'>$class</span>";
                // Log class color for debugging
                error_log("item_tooltip.php: Item {$item['entry']} class $class (bit $bit) assigned color $color");
            }
        }
    }
    $requiredClassesText = !empty($requiredClasses) ? 'Classes: ' . implode(', ', $requiredClasses) : null;

    // Fetch spell effects for Use, Equip, Chance on Hit, and Soulstone triggers
    $spellEffects = [];
    $tableCheck = $world_db->query("SHOW TABLES LIKE 'armory_spell'");
    if ($tableCheck->num_rows > 0) {
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
                        $triggerText = $triggerFlags[$trigger] ?? 'Unknown';
                        $description = !empty($spell['Description_en_gb']) ? htmlspecialchars($spell['Description_en_gb']) : htmlspecialchars($spell['ToolTip_1'] ?? '');
                        if (!empty($description)) {
                            $spellEffects[] = "$triggerText: $description";
                        } else {
                            error_log("No description for spell ID $spellId (trigger $trigger) in item " . ($item['entry'] ?? 'unknown'));
                        }
                    } else {
                        error_log("Spell ID $spellId not found in armory_spell for item " . ($item['entry'] ?? 'unknown'));
                    }
                    $stmt->close();
                } else {
                    error_log("Invalid or unhandled trigger $trigger for spell ID $spellId in item " . ($item['entry'] ?? 'unknown'));
                }
            }
        }
    } else {
        error_log("Table 'armory_spell' does not exist in database for item " . ($item['entry'] ?? 'unknown'));
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
    </style>

    <div style="background:#1a1a1a;border:1px solid #444;padding:8px;width:300px;color:#ccc;font:12px Arial;border-radius:4px;font-family:FrizQuadrata,Arial,sans-serif;">
        <div style="display:flex;justify-content:space-between;gap:8px;">
            <div>
                <div class="item-name" style="font-weight:bold;font-size:14px;"><?= $name ?></div>
                <?php if ($level): ?><div style="color:#e0b802;">Item Level <?= $level ?></div><?php endif; ?>
            </div>
            <div style="text-align:right;">
                <div><?= $subclassName ?? '' ?></div>
                <?php if ($speed): ?><div>Speed <?= $speed ?></div><?php endif; ?>
            </div>
        </div>

        <?php if ($bonding): ?><div><?= $bonding ?></div><?php endif; ?>
        <?php if ($invType): ?><div><?= $invType ?></div><?php endif; ?>
        <?php if ($className): ?><div><?= $className ?></div><?php endif; ?>

        <?php
        if ($item['dmg_min1'] > 0 && $item['dmg_max1'] > 0):
            $min = $item['dmg_min1'];
            $max = $item['dmg_max1'];
        ?>
            <div><?= $min ?> - <?= $max ?> Damage</div>
            <div style="color:#ffd100;">(<?= formatDPS($min, $max, $item['delay']) ?> damage per second)</div>
        <?php endif; ?>

        <?php if ($item['armor'] > 0): ?><div>+<?= $item['armor'] ?> Armor</div><?php endif; ?>

        <?php for ($i = 1; $i <= 10; $i++):
            $type = $item["stat_type$i"];
            $value = $item["stat_value$i"];
            if ($type > 0 && $value != 0 && isset($normalStats[$type])): ?>
                <div style="color:#ffffff;">+<?= $value ?> <?= $normalStats[$type] ?></div>
        <?php endif; endfor; ?>

        <?php
        $resistances = ['Holy' => $item['holy_res'], 'Fire' => $item['fire_res'], 'Nature' => $item['nature_res'],
                        'Frost' => $item['frost_res'], 'Shadow' => $item['shadow_res'], 'Arcane' => $item['arcane_res']];
        foreach ($resistances as $school => $val):
            if ($val > 0): ?>
                <div style="color:#1eff00;">+<?= $val ?> <?= $school ?> Resistance</div>
        <?php endif; endforeach; ?>

        <!-- Sockets -->
        <div style="display: flex; align-items: center; gap: 8px;">
            <?php for ($i = 1; $i <= 3; $i++): ?>
                <?php
                $colorCode = $item["socketColor_$i"] ?? null;
                if (isset($socketColors[$colorCode])):
                    $colorData = $socketColors[$colorCode];
                ?>
                    <div style="display: flex; align-items: center; gap: 4px;">
                        <img src="<?= $colorData['icon'] ?>"
                             alt="<?= $colorData['name'] ?> socket"
                             style="width: 16px; height: 16px; object-fit: contain;">
                        <span style="font-size: 12px; color: <?= strtolower($colorData['name']) ?>;">
                            <?= $colorData['name'] ?>
                        </span>
                    </div>
                <?php endif; ?>
            <?php endfor; ?>
        </div>

        <?php if (!empty($item['socketBonus'])): ?>
            <div style="color:#888;">Socket Bonus: Spell ID <?= htmlspecialchars($item['socketBonus']) ?></div>
        <?php endif; ?>

        <?php if ($dur > 0): ?><div>Durability <?= $dur ?>/<?= $dur ?></div><?php endif; ?>
        <?php if ($reqLevel): ?><div>Requires Level <?= $reqLevel ?></div><?php endif; ?>
        <?php if ($requiredClassesText): ?><div><?= $requiredClassesText ?></div><?php endif; ?>

        <?php for ($i = 1; $i <= 10; $i++):
            $type = $item["stat_type$i"];
            $value = $item["stat_value$i"];
            if ($type > 0 && $value != 0 && isset($specialStats[$type])): ?>
                <div style="color:#00ff00;">Equip: Increases +<?= $value ?> <?= $specialStats[$type] ?></div>
        <?php endif; endfor; ?>

        <?php foreach ($spellEffects as $effect): ?>
            <div style="color:#00ff00;"><?= $effect ?></div>
        <?php endforeach; ?>

        <?php if ($sell > 0): ?><div>Sell: <?= goldSilverCopper($sell) ?></div><?php endif; ?>
        <?php if ($desc): ?><div style="margin-top:6px;color:#eee;font-style:italic;"><?= $desc ?></div><?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
?>