<?php
// Data definitions
$qualityColors = [
    0 => '#9d9d9d', 1 => '#ffffff', 2 => '#1eff00',
    3 => '#0070dd', 4 => '#a335ee', 5 => '#ff8000',
    6 => '#e6cc80', 7 => '#e6cc80'
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
    2 => [0 => 'Axe', 1 => 'Axe (2H)', 2 => 'Bow', 3 => 'Gun', 4 => 'Mace', 5 => 'Mace (2H)',
        6 => 'Polearm', 7 => 'Sword', 8 => 'Sword (2H)', 10 => 'Staff', 13 => 'Fist Weapon',
        14 => 'Miscellaneous', 15 => 'Dagger', 16 => 'Thrown', 17 => 'Spear',
        18 => 'Crossbow', 19 => 'Wand', 20 => 'Fishing Pole'],
    4 => [0 => 'Miscellaneous', 1 => 'Cloth', 2 => 'Leather', 3 => 'Mail', 4 => 'Plate',
        6 => 'Shield', 7 => 'Libram', 8 => 'Idol', 9 => 'Totem', 10 => 'Sigil']
];
$statTypes = [
    3 => 'Agility', 4 => 'Strength', 5 => 'Intellect', 6 => 'Spirit', 7 => 'Stamina',
    12 => 'Defense Rating', 13 => 'Dodge Rating', 14 => 'Parry Rating', 15 => 'Block Rating',
    32 => 'Spell Power', 35 => 'Resilience', 36 => 'Haste Rating'
];
$socketColors = [
    1 => ['name' => 'Meta', 'icon' => '/Sahtout/img/shopimg/items/socketicons/socket_meta.gif'],
    2 => ['name' => 'Red', 'icon' => '/Sahtout/img/shopimg/items/socketicons/socket_red.gif'],
    4 => ['name' => 'Yellow', 'icon' => '/Sahtout/img/shopimg/items/socketicons/socket_yellow.gif'],
    8 => ['name' => 'Blue', 'icon' => '/Sahtout/img/shopimg/items/socketicons/socket_blue.gif']
];
$classRestrictions = [
    1 => 'Warrior', 2 => 'Paladin', 4 => 'Hunter', 8 => 'Rogue', 16 => 'Priest',
    32 => 'Death Knight', 64 => 'Shaman', 128 => 'Mage', 256 => 'Warlock',
    512 => 'Monk', 1024 => 'Druid', 2048 => 'Demon Hunter'
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
    global $qualityColors, $bondingTypes, $inventoryTypes, $classNames, $subclassNames, $statTypes, $socketColors, $classRestrictions;

    $color = $qualityColors[$item['Quality']] ?? '#ffffff';
    $name = htmlspecialchars($item['name']);
    $desc = htmlspecialchars($item['description']);
    $level = $item['ItemLevel'];
    $reqLevel = $item['RequiredLevel'];
    $sell = $item['SellPrice'] ?? 0;
    $dur = $item['MaxDurability'] ?? 0;
    $speed = $item['delay'] > 0 ? round($item['delay'] / 1000, 2) : null;
    $bonding = $bondingTypes[$item['bonding']] ?? null;
    $className = $classNames[$item['class']] ?? 'Unknown';
    $subclassName = $subclassNames[$item['class']][$item['subclass']] ?? null;
    $invType = $inventoryTypes[$item['InventoryType']] ?? null;

    // Class restrictions - only show if there are actual restrictions
    $requiredClasses = [];
    if (isset($item['AllowableClass']) && $item['AllowableClass'] > 0) {
        foreach ($classRestrictions as $bit => $class) {
            if ($item['AllowableClass'] & $bit) {
                $requiredClasses[] = $class;
            }
        }
    }
    $requiredClassesText = !empty($requiredClasses) ? 'Classes: ' . implode(', ', $requiredClasses) : null;

    ob_start();
    ?>
    <style>
        .socket-icon {
            width: 16px;
            height: 16px;
            object-fit: contain;
            vertical-align: middle;
        }
    </style>

    <div style="background:#1a1a1a;border:1px solid #444;padding:8px;width:300px;color:#ccc;font:12px Arial;border-radius:4px;font-family:FrizQuadrata,Arial,sans-serif;">
        <div style="display:flex;justify-content:space-between;gap:8px;">
            <div>
                <div style="color:<?= $color ?>;font-weight:bold;font-size:14px;"><?= $name ?></div>
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
            if ($type > 0 && $value != 0):
                $stat = $statTypes[$type] ?? "Stat $type"; ?>
                <div style="color:#00ff00;">+<?= $value ?> <?= $stat ?></div>
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
        <?php if ($requiredClassesText): ?><div style="color:#eb0505;"><?= $requiredClassesText ?></div><?php endif; ?>
        <?php if ($reqLevel): ?><div>Requires Level <?= $reqLevel ?></div><?php endif; ?>
        <?php if ($sell > 0): ?><div>Sell: <?= goldSilverCopper($sell) ?></div><?php endif; ?>
        <?php if ($desc): ?><div style="margin-top:6px;color:#eee;font-style:italic;"><?= $desc ?></div><?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
?>