<?php
include '../includes/session.php';
$page_class = 'character';
include '../includes/header.php';
include '../includes/item_tooltip.php'; // Include tooltip logic
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Character Equipment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>

        .character-container {
            display: flex;
            flex-wrap: wrap;
            max-width: 1280px; /* Matches arenateam.php max-w-6xl */
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
            max-width: 280px;
            height: 400px;
            background: url('/Sahtout/img/characterarmor/background.jpg') no-repeat center center;
            background-size: cover;
            margin: 20px 0;
            border: 1px solid #444;
            position: relative;
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
            cursor: url('/Sahtout/img/hover_wow.gif')16 16, auto;
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
            max-width: 1280px; /* Matches max-w-6xl */
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
            margin-top: auto;
            max-width: 1280px; /* Matches max-w-6xl */
            margin-left: auto;
            margin-right: auto;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .character-container {
                max-width: 100%;
                margin-left: 0;
                margin-right: auto;
                padding: 4px;
                min-height: unset; /* Allow natural height for scrolling */
            }
            
            .equipment-column {
                width: 80px; /* Fits smaller slots */
                padding: 10px;
            }
            
            .character-center {
                border-left: none;
                border-right: none;
                min-width: 160px; /* Fits smaller character-image */
                padding: 10px;
            }
            
            .character-image {
                max-width: 200px; /* Reduced size */
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
            
            .weapon-slot {
                width: 48px; /* Match slot size */
                max-width: 100%;
            }
            
            .slot {
                padding: 4px;
                width: 48px; /* Fits icon (36px) + padding/border */
            }
            
            .slot-icon {
                width: 36px;
                height: 36px;
                margin-right: 0; /* No margin since slot-info is hidden */
            }
            
            .slot-info {
                display: none; /* Hide slot-name and slot-item/empty-slot */
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
                max-width: 160px; /* Further reduced */
                height: auto;
                aspect-ratio: 7 / 10;
                margin: 10px 0;
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
                max-width: 140px; /* Smallest size for very small screens */
                height: auto;
                aspect-ratio: 7 / 10;
                margin: 10px 0;
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
                // Handle mouse events for desktop
                slot.addEventListener('mouseenter', (e) => {
                    showTooltip(e, slot);
                });
                slot.addEventListener('mousemove', updateTooltipPosition);
                slot.addEventListener('mouseleave', hideTooltip);

                // Handle touch events for mobile
                slot.addEventListener('touchstart', (e) => {
                    e.preventDefault(); // Prevent default touch behavior
                    showTooltip(e, slot);
                    setTimeout(hideTooltip, 3000); // Auto-hide after 3 seconds
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

                // Adjust if tooltip goes off-screen
                const rect = tooltip.getBoundingClientRect();
                if (rect.right > window.innerWidth) {
                    tooltip.style.left = `${window.innerWidth - rect.width - 10}px`;
                }
                if (rect.bottom > window.innerHeight) {
                    tooltip.style.top = `${window.innerHeight - rect.height - 10}px`;
                }
            }
        });
    </script>
</head>
<body>
<?php
// Slot definitions (matches provided schema)
$slotDefs = [
    0 => 'Head', 1 => 'Neck', 2 => 'Shoulders', 3 => 'Body', 4 => 'Chest',
    5 => 'Waist', 6 => 'Legs', 7 => 'Feet', 8 => 'Wrists', 9 => 'Hands',
    10 => 'Finger', 11 => 'Finger', 12 => 'Trinket', 13 => 'Trinket',
    14 => 'Back', 15 => 'Main Hand', 16 => 'Off Hand', 17 => 'Ranged', 18 => 'Tabard'
];

// Default icons for empty slots
$defaultIcons = [
    0 => 'head.gif', 1 => 'neck.gif', 2 => 'shoulders.gif', 3 => 'body.gif', 4 => 'chest.gif',
    5 => 'waist.gif', 6 => 'legs.gif', 7 => 'feet.gif', 8 => 'wrists.gif', 9 => 'hands.gif',
    10 => 'finger.gif', 11 => 'finger.gif', 12 => 'trinket.gif', 13 => 'trinket.gif',
    14 => 'back.gif', 15 => 'mainhand.gif', 16 => 'offhand.gif', 17 => 'ranged.gif', 18 => 'tabard.gif'
];

// Race and class mappings
$races = [
    1 => 'Human', 2 => 'Orc', 3 => 'Dwarf', 4 => 'Night Elf', 5 => 'Undead',
    6 => 'Tauren', 7 => 'Gnome', 8 => 'Troll', 10 => 'Blood Elf', 11 => 'Draenei'
];
$classes = [
    1 => 'Warrior', 2 => 'Paladin', 3 => 'Hunter', 4 => 'Rogue', 5 => 'Priest',
    6 => 'Death Knight', 7 => 'Shaman', 8 => 'Mage', 9 => 'Warlock', 11 => 'Druid'
];

// Fetch character data from $char_db
$guid = isset($_GET['guid']) ? (int)$_GET['guid'] : 0;
$character = null;
$items = [];
$error = '';

if ($guid > 0) {
    // Check $char_db connection
    if (!isset($char_db) || !$char_db) {
        $error = 'Database connection ($char_db) is not available.';
        error_log("armory.php: Database connection ($char_db) not initialized for guid=$guid");
    } else {
        $stmt = $char_db->prepare("SELECT guid, name, race, class, level FROM characters WHERE guid = ?");
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
                }
                $stmt->close();
            }
        }

        // Fetch equipped items from $char_db
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

                    // Log if no items found
                    if (empty($itemEntries)) {
                        error_log("armory.php: No equipped items found for guid=$guid in character_inventory with bag=0");
                    } else {
                        // Fetch item details and icon from $world_db.item_template and itemdisplayinfo_dbc
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
                                            // Ensure icon name is lowercase for Wowhead compatibility
                                            $row['icon'] = strtolower($row['icon']);
                                        }
                                        $items[$slot] = $row;
                                    }
                                    $stmt->close();

                                    // Log missing item_template entries
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
        <!-- Left Equipment Column -->
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
        
        <!-- Center Character Info with Weapons -->
        <div class="character-center">
            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php else: ?>
                <div class="character-name"><?= htmlspecialchars($character['name'] ?? 'Unknown') ?></div>
                <div class="character-details">
                    <span class="character-level">Level <?= $character['level'] ?? '??' ?> <?= isset($classes[$character['class']]) ? $classes[$character['class']] : 'Unknown' ?></span>
                    <span class="character-race"><?= isset($races[$character['race']]) ? $races[$character['race']] : 'Unknown' ?></span>
                </div>
                <div class="character-image">
                    <!-- Character background image set via CSS -->
                </div>
                
                <!-- Weapons under the image -->
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
        
        <!-- Right Equipment Column -->
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
<?php include '../includes/footer.php'; ?>
</body>
</html>