<?php
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../includes/paths.php'; // Include paths.php
require_once $project_root . 'includes/session.php';
require_once $project_root . 'includes/item_tooltip.php';
require_once $project_root . 'languages/language.php';

$page_class = 'shop';
include_once $project_root . 'includes/header.php';

$selected_category = isset($_GET['category']) ? $_GET['category'] : 'All';
$valid_categories = ['All', 'Service', 'Mount', 'Pet', 'Gold', 'Stuff', 'Set'];
if (!in_array($selected_category, $valid_categories)) {
    $selected_category = 'All';
}

$category_images = [
    'Service' => 'img/shopimg/icons/category_service.gif',
    'Mount' => 'img/shopimg/icons/category_mount.jpg',
    'Pet' => 'img/shopimg/icons/category_pet.jpg',
    'Gold' => 'img/shopimg/icons/category_gold.webp',
    'Stuff' => 'img/shopimg/icons/category_stuff.jpg',
    'Set' => 'img/shopimg/icons/category_stuff.jpg'
];

// Categories that should show tooltips
$tooltip_categories = ['Mount', 'Pet', 'Stuff', 'Set'];

$query = "
    SELECT si.item_id, si.category, si.name, si.description, si.image, si.point_cost, si.token_cost, si.stock, si.level_boost, si.at_login_flags, si.is_set, si.itemset_id, sit.entry AS sit_entry, sis.set_item_count
    FROM shop_items si
    LEFT JOIN site_items sit ON si.entry = sit.entry
    LEFT JOIN (
        SELECT itemset, COUNT(*) AS set_item_count
        FROM site_items
        WHERE itemset > 0
        GROUP BY itemset
    ) sis ON si.itemset_id = sis.itemset
    ORDER BY si.category, si.name
";
$stmt = $site_db->prepare($query);
$items = [];
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $items[$row['category']][] = $row;
    }
    $stmt->close();
} else {
    error_log("Failed to prepare shop items query: " . $site_db->error);
}

$points = 0;
$tokens = 0;
if (!empty($_SESSION['user_id'])) {
    $stmt = $site_db->prepare("SELECT points, tokens FROM user_currencies WHERE account_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_currency = $result->fetch_assoc();
    $stmt->close();
    $points = $user_currency ? $user_currency['points'] : 0;
    $tokens = $user_currency ? $user_currency['tokens'] : 0;
} else {
    error_log("No user_id in session");
}

$characters = [];
if (!empty($_SESSION['user_id'])) {
    $stmt_chars = $char_db->prepare("SELECT guid, name FROM characters WHERE account = ?");
    $stmt_chars->bind_param("i", $_SESSION['user_id']);
    $stmt_chars->execute();
    $result_chars = $stmt_chars->get_result();
    while ($row = $result_chars->fetch_assoc()) {
        $characters[] = ['id' => $row['guid'], 'name' => $row['name']];
    }
    $stmt_chars->close();
} else {
    error_log("No characters fetched: user not logged in");
}

$status_message = '';
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'success':
            $status_message = '<div class="bg-green-900/40 border border-green-600/40 text-green-200 px-5 py-3 flex items-center gap-3 mb-4 force-wrap"><i class="fas fa-check-circle text-green-400 text-lg"></i><span>' . translate('shop_status_success', 'Purchase successful!') . '</span></div>';
            break;
        case 'insufficient_funds':
            $status_message = '<div class="bg-red-900/40 border border-red-600/40 text-red-200 px-5 py-3 flex items-center gap-3 mb-4 force-wrap"><i class="fas fa-exclamation-circle text-red-400 text-lg"></i><span>' . translate('shop_status_insufficient_funds', 'Insufficient points or tokens.') . '</span></div>';
            break;
        case 'out_of_stock':
            $status_message = '<div class="bg-red-900/40 border border-red-600/40 text-red-200 px-5 py-3 flex items-center gap-3 mb-4 force-wrap"><i class="fas fa-exclamation-circle text-red-400 text-lg"></i><span>' . translate('shop_status_out_of_stock', 'Item is out of stock.') . '</span></div>';
            break;
        case 'error':
        case 'Database query error':
            $status_message = '<div class="bg-red-900/40 border border-red-600/40 text-red-200 px-5 py-3 flex items-center gap-3 mb-4 force-wrap"><i class="fas fa-exclamation-circle text-red-400 text-lg"></i><span>' . translate('shop_status_error', 'An error occurred during purchase. Check server logs for details.') . '</span></div>';
            break;
        case 'character_online':
            $status_message = '<div class="bg-yellow-900/40 border border-yellow-600/40 text-yellow-200 px-5 py-3 flex items-center gap-3 mb-4 force-wrap"><i class="fas fa-exclamation-triangle text-yellow-400 text-lg"></i><span>' . translate('shop_status_character_online', 'Selected character must be logged out to complete the purchase.') . '</span></div>';
            break;
        case 'level_too_high':
            $status_message = '<div class="bg-yellow-900/40 border border-yellow-600/40 text-yellow-200 px-5 py-3 flex items-center gap-3 mb-4 force-wrap"><i class="fas fa-exclamation-triangle text-yellow-400 text-lg"></i><span>' . translate('shop_status_level_too_high', 'Your character\'s level is too high for this level boost.') . '</span></div>';
            break;
        case 'character_not_found':
            $status_message = '<div class="bg-red-900/40 border border-red-600/40 text-red-200 px-5 py-3 flex items-center gap-3 mb-4 force-wrap"><i class="fas fa-exclamation-circle text-red-400 text-lg"></i><span>' . translate('shop_status_character_not_found', 'Selected character not found or not owned.') . '</span></div>';
            break;
        case 'cooldown_active':
            $status_message = '<div class="bg-yellow-900/40 border border-yellow-600/40 text-yellow-200 px-5 py-3 flex items-center gap-3 mb-4 force-wrap"><i class="fas fa-exclamation-triangle text-yellow-400 text-lg"></i><span>' . translate('shop_status_cooldown_active', 'Please wait 5 seconds before making another purchase.') . '</span></div>';
            break;
    }
}

$cooldown_active = false;
$remaining_cooldown = 0;
if (!empty($_SESSION['user_id']) && isset($_SESSION['last_purchase_time'])) {
    $last_purchase_time = $_SESSION['last_purchase_time'];
    $current_time = time();
    $cooldown_duration = 5;
    if ($current_time - $last_purchase_time < $cooldown_duration) {
        $cooldown_active = true;
        $remaining_cooldown = $cooldown_duration - ($current_time - $last_purchase_time);
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo translate('shop_meta_description', 'Browse and purchase items, mounts, pets, gold, and services for '.$site_title_name . ' WoW Server'); ?>">
    <title><?php echo $site_title_name ." ".translate('shop_page_title', '- Shop'); ?></title>
    
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/tailwind.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    
    <style>
        /* Page background - Show full background image */
        body {
            background: url('<?php echo $base_path; ?>img/backgrounds/bg-shop.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            padding-top: 112px;
            margin: 0;
            position: relative;
        }
        
        /* Glass effect container */
        .glass-container {
            background: rgba(5, 7, 11, 0.65);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            border: 1px solid rgba(201,162,39,.22);
            border-radius: 0;
            padding: 2.5rem 2.5rem;
            box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.8), inset 0 0 60px rgba(0,0,0,.25);
            position: relative;
        }
        
        .glass-container::before {
            content: ''; position: absolute; inset: 5px;
            border: 1px solid rgba(201,162,39,.14);
            pointer-events: none;
        }
        
        .glass-container::after {
            content: ''; position: absolute; inset: 0; pointer-events: none;
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
        
        /* Wow title gradient */
        .wow-title {
            font-family: 'Cinzel', serif;
            font-weight: 900;
            background: linear-gradient(180deg, #fff7d6 0%, #f2cf5b 35%, #c9a227 62%, #8a6a14 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,.9));
            letter-spacing: .02em;
        }
        
        /* Item card hover effect */
        .item-card {
            transition: all 0.3s ease;
            position: relative;
        }
        
        .item-card:hover {
            transform: translateY(-4px);
            border-color: rgba(201,162,39,0.4);
            box-shadow: 0 12px 30px rgba(0,0,0,0.4);
            z-index: 20;
        }
        
        /* Tooltip positioned on the right side */
        .item-card .item-tooltip {
            display: none;
            position: absolute;
            z-index: 100;
            background: rgba(5,7,11,0.95);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(201,162,39,0.3);
            padding: 1rem;
            width: 320px;
            color: #d1d5db;
            font-size: 0.8rem;
            top: 0;
            left: calc(100% + 10px);
            box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.8);
        }
        
        /* Tooltip arrow pointing left */
        .item-card .item-tooltip::before {
            content: '';
            position: absolute;
            right: 100%;
            top: 20px;
            border: 8px solid transparent;
            border-right-color: rgba(201,162,39,0.3);
        }
        
        /* Show tooltip only for categories that have tooltips */
        .item-card.has-tooltip:hover .item-tooltip {
            display: block;
        }
        
        /* Limited stock badge animation */
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .limited-stock-badge {
            animation: pulse 2s infinite;
        }
        
        /* Custom alert overlay */
        .custom-alert-overlay {
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(5px);
            display: none;
            align-items: center;
            justify-content: center;
        }
        
        .custom-alert-overlay.show {
            display: flex;
        }
        
        .custom-alert-overlay.fade-out {
            animation: fadeOut 0.3s ease forwards;
        }
        
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        
        /* Tab content animation */
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
        
        /* ============ FORCE TEXT WRAPPING ============ */
        .force-wrap {
            min-width: 0;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        
        /* ============ RESPONSIVE ============ */
        @media (max-width: 1200px) {
            .item-card .item-tooltip {
                left: 50%;
                top: 100%;
                transform: translateX(-50%);
                margin-top: 10px;
                width: 280px;
            }
            
            .item-card .item-tooltip::before {
                right: auto;
                top: auto;
                bottom: 100%;
                left: 50%;
                transform: translateX(-50%);
                border-right-color: transparent;
                border-bottom-color: rgba(201,162,39,0.3);
            }
        }
        
        @media (max-width: 768px) {
            body {
                padding-top: 96px;
            }
            
            .glass-container {
                padding: 1.5rem 0.75rem;
            }
            
            .item-card .item-tooltip {
                width: 250px;
                left: 50%;
                top: 100%;
                transform: translateX(-50%);
                margin-top: 10px;
            }
            
            .item-card .item-tooltip::before {
                right: auto;
                top: auto;
                bottom: 100%;
                left: 50%;
                transform: translateX(-50%);
                border-right-color: transparent;
                border-bottom-color: rgba(201,162,39,0.3);
            }
        }
    </style>
</head>
<body>

<div class="shop-content relative z-10 min-h-screen flex items-start justify-center px-4 md:px-8 py-8">
    <div class="container mx-auto max-w-7xl px-2 sm:px-4">
        
        <!-- Main Container -->
        <div class="glass-container">
            
            <!-- Header -->
            <h1 class="wow-title text-3xl md:text-5xl font-bold text-center mb-6 force-wrap">
                <i class="fas fa-store text-[#f2cf5b] mr-2"></i>
                <?php echo $site_title_name ." ". translate('shop_title', 'Server Shop'); ?>
            </h1>
            
            <!-- User Balance -->
            <?php if (!empty($_SESSION['user_id'])): ?>
                <div class="flex flex-wrap justify-center gap-4 mb-6 p-4 bg-[rgba(0,0,0,0.4)] border border-[rgba(201,162,39,0.1)]">
                    <span class="inline-flex items-center gap-2 px-4 py-2 bg-[rgba(242,207,82,0.15)] border border-[rgba(201,162,39,0.3)] text-[#f2cf5b] font-semibold text-base force-wrap">
                        <i class="fas fa-coins"></i> <?php echo translate('shop_points', 'Points'); ?>: <?php echo $points; ?>
                    </span>
                    <span class="inline-flex items-center gap-2 px-4 py-2 bg-[rgba(139,92,246,0.15)] border border-[rgba(139,92,246,0.3)] text-[#8b5cf6] font-semibold text-base force-wrap">
                        <i class="fas fa-gem"></i> <?php echo translate('shop_tokens', 'Tokens'); ?>: <?php echo $tokens; ?>
                    </span>
                </div>
            <?php else: ?>
                <div class="text-center text-gray-300 text-sm md:text-base p-4 bg-[rgba(242,207,82,0.08)] border border-[rgba(201,162,39,0.2)] mb-6 force-wrap">
                    <i class="fas fa-info-circle text-[#f2cf5b] mr-2"></i>
                    <?php echo str_replace('{base_path}', $base_path, translate('shop_login_prompt', 'Please <a href="{base_path}login" class="text-[#f2cf5b] hover:text-yellow-300 underline">log in</a> to purchase items.')); ?>
                </div>
            <?php endif; ?>

            <!-- Status Messages -->
            <?php echo $status_message; ?>

            <!-- Category Navigation -->
            <nav class="flex flex-wrap justify-center gap-2 mb-6 p-3 bg-[rgba(0,0,0,0.3)] border border-[rgba(201,162,39,0.1)]">
                <?php foreach ($valid_categories as $category): ?>
                    <a href="#" 
                       class="category-button flex items-center gap-2 px-4 py-2.5 text-sm font-semibold border-2 rounded-none transition-all duration-300 <?php echo $selected_category === $category ? 'bg-[rgba(242,207,82,0.2)] border-[#f2cf5b] text-[#f2cf5b]' : 'bg-[rgba(10,14,22,0.6)] border-[rgba(201,162,39,0.15)] text-gray-400 hover:border-[#f2cf5b] hover:text-white hover:bg-[rgba(242,207,82,0.05)]'; ?>" 
                       data-category="<?php echo htmlspecialchars($category); ?>">
                        <?php if (isset($category_images[$category])): ?>
                            <img src="<?php echo $base_path . $category_images[$category]; ?>" alt="<?php echo translate('shop_category_' . strtolower($category) . '_icon', htmlspecialchars($category) . ' Icon'); ?>" class="w-8 h-8 rounded-full object-cover border border-[rgba(201,162,39,0.1)]">
                        <?php endif; ?>
                        <span class="force-wrap"><?php echo translate('shop_category_' . strtolower($category), htmlspecialchars($category)); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- Items -->
            <?php if (empty($items)): ?>
                <div class="text-center py-12 text-gray-400 force-wrap">
                    <i class="fas fa-box-open text-4xl block mb-3 text-[rgba(201,162,39,0.3)]"></i>
                    <?php echo translate('shop_no_items', 'No items available.'); ?>
                </div>
            <?php else: ?>
                <?php foreach ($items as $category => $category_items): ?>
                    <section class="tab-content <?php echo ($selected_category === 'All' || $selected_category === $category) ? 'active' : ''; ?>" data-category="<?php echo htmlspecialchars($category); ?>">
                        <h2 class="text-2xl font-bold text-[#f2cf5b] mb-4 pb-2 border-b border-[rgba(201,162,39,0.15)] force-wrap">
                            <i class="fas fa-tag mr-2"></i>
                            <?php echo translate('shop_category_' . strtolower($category), htmlspecialchars($category)); ?>
                        </h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                            <?php foreach ($category_items as $item): ?>
                                <?php 
                                $has_tooltip = in_array($category, $tooltip_categories) && $item['sit_entry'] && (int)$item['is_set'] !== 1;
                                ?>
                                <div class="item-card relative bg-[rgba(10,14,22,0.8)] border border-[rgba(201,162,39,0.12)] p-4 flex flex-col <?php echo $has_tooltip ? 'has-tooltip' : ''; ?> min-w-0" data-entry="<?php echo $item['sit_entry'] ? htmlspecialchars($item['sit_entry']) : ''; ?>">
                                    <!-- Image -->
                                    <div class="relative w-full">
                                        <img src="<?php echo $base_path . ($item['image'] ?? 'img/shop/placeholder.png'); ?>" alt="<?php echo str_replace('{name}', htmlspecialchars($item['name']), translate('shop_item_image_alt', '{name}')); ?>" class="w-full h-48 object-cover border border-[rgba(201,162,39,0.08)]">
                                        <?php if ($item['stock'] !== null && $item['stock'] < 10 && $item['stock'] > 0): ?>
                                            <span class="limited-stock-badge absolute top-2 right-2 bg-red-500 text-white text-xs font-bold px-2 py-1 border border-red-600 shadow-lg force-wrap">Limited Stock</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Details -->
                                    <h3 class="text-white text-lg font-bold mt-3 mb-1 force-wrap min-w-0 break-words [overflow-wrap:anywhere]"><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <p class="text-gray-400 text-sm flex-1 force-wrap min-w-0 break-words [overflow-wrap:anywhere]"><?php echo htmlspecialchars($item['description'] ?? translate('shop_no_description', 'No description available.')); ?></p>
                                    
                                    <?php if ((int)$item['is_set'] === 1 && !empty($item['itemset_id'])): ?>
                                        <p class="text-[#89d2ff] text-xs font-semibold mt-1 force-wrap">
                                            <i class="fas fa-cubes mr-1"></i>
                                            <?php echo translate('shop_set_contains', 'Set') . ' #' . (int)$item['itemset_id'] . ' - ' . (int)($item['set_item_count'] ?? 0) . ' ' . translate('shop_items', 'items'); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if ($category === 'Service'): ?>
                                        <?php if ($item['level_boost'] !== null): ?>
                                            <p class="text-emerald-400 text-xs font-bold mt-1 force-wrap"><i class="fas fa-arrow-up mr-1"></i><?php echo translate('shop_level_boost', 'Level Boost'); ?>: <?php echo $item['level_boost']; ?></p>
                                        <?php endif; ?>
                                        <?php if ($item['at_login_flags'] & 1): ?>
                                            <p class="text-emerald-400 text-xs font-bold mt-1 force-wrap"><i class="fas fa-user-edit mr-1"></i><?php echo translate('shop_rename_character', 'Character Rename'); ?></p>
                                        <?php endif; ?>
                                        <?php if ($item['at_login_flags'] & 2): ?>
                                            <p class="text-emerald-400 text-xs font-bold mt-1 force-wrap"><i class="fas fa-magic mr-1"></i><?php echo translate('shop_reset_spells', 'Reset Spells'); ?></p>
                                        <?php endif; ?>
                                        <?php if ($item['at_login_flags'] & 4): ?>
                                            <p class="text-emerald-400 text-xs font-bold mt-1 force-wrap"><i class="fas fa-star mr-1"></i><?php echo translate('shop_reset_talents', 'Reset Talents'); ?></p>
                                        <?php endif; ?>
                                        <?php if ($item['at_login_flags'] & 8): ?>
                                            <p class="text-emerald-400 text-xs font-bold mt-1 force-wrap"><i class="fas fa-user-astronaut mr-1"></i><?php echo translate('shop_customize_character', 'Character Customization'); ?></p>
                                        <?php endif; ?>
                                        <?php if ($item['at_login_flags'] & 16): ?>
                                            <p class="text-emerald-400 text-xs font-bold mt-1 force-wrap"><i class="fas fa-paw mr-1"></i><?php echo translate('shop_reset_pet_talents', 'Reset Pet Talents'); ?></p>
                                        <?php endif; ?>
                                        <?php if ($item['at_login_flags'] & 64): ?>
                                            <p class="text-emerald-400 text-xs font-bold mt-1 force-wrap"><i class="fas fa-flag mr-1"></i><?php echo translate('shop_faction_change', 'Faction Change'); ?></p>
                                        <?php endif; ?>
                                        <?php if ($item['at_login_flags'] & 128): ?>
                                            <p class="text-emerald-400 text-xs font-bold mt-1 force-wrap"><i class="fas fa-users mr-1"></i><?php echo translate('shop_race_change', 'Race Change'); ?></p>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <!-- Tooltip - Only for categories with tooltips -->
                                    <?php if ($has_tooltip): ?>
                                        <div class="item-tooltip force-wrap">
                                            <?php
                                            if (in_array($category, $tooltip_categories) && $item['sit_entry'] && (int)$item['is_set'] !== 1) {
                                                $stmt_tooltip = $site_db->prepare("SELECT * FROM site_items WHERE entry = ?");
                                                $stmt_tooltip->bind_param("i", $item['sit_entry']);
                                                $stmt_tooltip->execute();
                                                $result_tooltip = $stmt_tooltip->get_result();
                                                if ($tooltip_data = $result_tooltip->fetch_assoc()) {
                                                    echo generateTooltip($tooltip_data);
                                                }
                                                $stmt_tooltip->close();
                                            }
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Cost -->
                                    <div class="flex justify-center gap-4 mt-3 pt-2 border-t border-[rgba(201,162,39,0.08)]">
                                        <?php if ($item['point_cost'] > 0): ?>
                                            <span class="text-[#f2cf5b] font-semibold text-sm force-wrap"><i class="fas fa-coins mr-1"></i> <?php echo $item['point_cost']; ?></span>
                                        <?php endif; ?>
                                        <?php if ($item['token_cost'] > 0): ?>
                                            <span class="text-[#8b5cf6] font-semibold text-sm force-wrap"><i class="fas fa-gem mr-1"></i> <?php echo $item['token_cost']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Stock -->
                                    <div class="text-center text-gray-500 text-xs mt-1 force-wrap">
                                        <?php if ($item['stock'] !== null): ?>
                                            <span><?php echo translate('shop_stock', 'Stock'); ?>: <?php echo $item['stock']; ?></span>
                                        <?php else: ?>
                                            <span><?php echo translate('shop_unlimited_stock', 'Unlimited Stock'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Purchase -->
                                    <?php if (!empty($_SESSION['user_id'])): ?>
                                        <form action="<?php echo $base_path; ?>buy_item" method="POST" class="mt-3 purchase-form">
                                            <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                            <?php if (!empty($characters)): ?>
                                                <select name="character_id" class="w-full mb-2 px-3 py-2 bg-[rgba(0,0,0,0.4)] border border-[rgba(201,162,39,0.2)] text-gray-200 text-sm focus:border-[#f2cf5b] focus:outline-none transition-colors" required>
                                                    <option value=""><?php echo translate('shop_select_character', 'Select a Character'); ?></option>
                                                    <?php foreach ($characters as $char): ?>
                                                        <option value="<?php echo htmlspecialchars($char['id']); ?>">
                                                            <?php echo htmlspecialchars($char['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php else: ?>
                                                <p class="text-red-400 text-xs text-center mb-2 force-wrap"><?php echo translate('shop_no_characters', 'No characters available.'); ?></p>
                                            <?php endif; ?>
                                            <button type="submit" class="buy-button w-full py-2.5 bg-[rgba(242,207,82,0.15)] border border-[rgba(201,162,39,0.3)] text-[#f2cf5b] hover:bg-[rgba(242,207,82,0.25)] transition-all duration-300 font-semibold text-sm <?php echo ($item['stock'] === 0 && $item['stock'] !== null) || empty($characters) || $cooldown_active ? 'opacity-50 cursor-not-allowed hover:bg-[rgba(242,207,82,0.15)]' : ''; ?>" 
                                                    <?php echo ($item['stock'] === 0 && $item['stock'] !== null) || empty($characters) || $cooldown_active ? 'disabled' : ''; ?>
                                                    data-item-id="<?php echo $item['item_id']; ?>">
                                                <?php echo $cooldown_active ? str_replace('{seconds}', $remaining_cooldown, translate('shop_wait_cooldown', 'Wait {seconds}s')) : translate('shop_buy_now', 'Buy Now'); ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <a href="<?php echo $base_path; ?>login" class="login-to-buy-button block w-full text-center py-2.5 bg-[rgba(52,152,219,0.2)] border border-[rgba(52,152,219,0.3)] text-[#3498db] hover:bg-[rgba(52,152,219,0.3)] transition-all duration-300 font-semibold text-sm mt-3 force-wrap"><?php echo translate('shop_login_to_buy', 'Log in to Buy'); ?></a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            <?php endif; ?>
            
        </div>
    </div>
</div>

<!-- Custom Alert Overlay -->
<div class="custom-alert-overlay" id="customAlert">
    <div class="bg-[rgba(5,7,11,0.95)] border border-[rgba(201,162,39,0.3)] p-6 max-w-md w-full mx-4 text-center relative transform scale-90 transition-all duration-300" id="alertBox">
        <button class="absolute top-2 right-3 text-gray-500 hover:text-[#f2cf5b] transition-colors text-xl" id="alertClose">
            <i class="fas fa-times"></i>
        </button>
        <div class="w-16 h-16 mx-auto rounded-full bg-[rgba(242,207,82,0.15)] border border-[rgba(201,162,39,0.3)] text-[#f2cf5b] flex items-center justify-center text-3xl mb-3" id="alertIcon">
            <i class="fas fa-info-circle"></i>
        </div>
        <h3 class="text-[#f2cf5b] text-xl font-bold mb-2 force-wrap" id="alertTitle">Information</h3>
        <p class="text-gray-300 text-sm leading-relaxed force-wrap" id="alertMessage">Message</p>
        <div class="mt-4 flex flex-col sm:flex-row gap-2 justify-center" id="alertActions"></div>
    </div>
</div>

<?php include_once $project_root . 'includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Category filtering
        const buttons = document.querySelectorAll('.category-button');
        const categories = document.querySelectorAll('.tab-content');
        
        buttons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const selectedCategory = this.getAttribute('data-category');
                
                buttons.forEach(btn => {
                    btn.classList.remove('bg-[rgba(242,207,82,0.2)]', 'border-[#f2cf5b]', 'text-[#f2cf5b]');
                    btn.classList.add('bg-[rgba(10,14,22,0.6)]', 'border-[rgba(201,162,39,0.15)]', 'text-gray-400');
                });
                this.classList.add('bg-[rgba(242,207,82,0.2)]', 'border-[#f2cf5b]', 'text-[#f2cf5b]');
                this.classList.remove('bg-[rgba(10,14,22,0.6)]', 'border-[rgba(201,162,39,0.15)]', 'text-gray-400');
                
                categories.forEach(category => {
                    if (selectedCategory === 'All' || category.getAttribute('data-category') === selectedCategory) {
                        category.classList.add('active');
                        category.style.display = 'block';
                    } else {
                        category.classList.remove('active');
                        category.style.display = 'none';
                    }
                });
                
                const newUrl = window.location.pathname + '?category=' + encodeURIComponent(selectedCategory);
                window.history.pushState({}, '', newUrl);
            });
        });

        // Cooldown timer
        let remainingCooldown = <?php echo json_encode($remaining_cooldown); ?>;
        let isPurchaseBlocked = <?php echo json_encode($cooldown_active); ?>;

        if (isPurchaseBlocked) {
            updateBuyButtons(true, remainingCooldown);
            startCooldownTimer(remainingCooldown);
        }

        function updateBuyButtons(disabled, seconds) {
            document.querySelectorAll('.buy-button:not([href])').forEach(button => {
                if (!button.hasAttribute('disabled') || button.getAttribute('disabled') === '') {
                    button.disabled = disabled;
                    button.textContent = disabled ? '<?php echo translate('shop_wait_cooldown', 'Wait {seconds}s'); ?>'.replace('{seconds}', seconds) : '<?php echo translate('shop_buy_now', 'Buy Now'); ?>';
                    if (disabled) {
                        button.classList.add('opacity-50', 'cursor-not-allowed');
                    } else {
                        button.classList.remove('opacity-50', 'cursor-not-allowed');
                    }
                }
            });
        }

        function startCooldownTimer(seconds) {
            let timeLeft = seconds;
            const interval = setInterval(() => {
                timeLeft--;
                if (timeLeft <= 0) {
                    isPurchaseBlocked = false;
                    updateBuyButtons(false, 0);
                    clearInterval(interval);
                } else {
                    updateBuyButtons(true, timeLeft);
                }
            }, 1000);
        }

        // Purchase form validation
        document.querySelectorAll('.purchase-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const characterSelect = this.querySelector('.character-select');
                if (characterSelect && !characterSelect.value) {
                    e.preventDefault();
                    showCustomAlert('<?php echo translate('shop_js_select_character', 'Please select a character to purchase this item.'); ?>', 'info');
                    return;
                }
                if (isPurchaseBlocked) {
                    e.preventDefault();
                    showCustomAlert('<?php echo translate('shop_js_cooldown_active', 'Please wait {seconds} seconds before making another purchase.'); ?>'.replace('{seconds}', remainingCooldown), 'warning');
                    return;
                }
            });
        });

        // Login to buy buttons
        document.querySelectorAll('.login-to-buy-button').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                showLoginAlert();
            });
        });

        // Custom Alert System
        function showCustomAlert(message, type = 'info') {
            const overlay = document.getElementById('customAlert');
            const alertBox = document.getElementById('alertBox');
            const icon = document.getElementById('alertIcon');
            const title = document.getElementById('alertTitle');
            const msg = document.getElementById('alertMessage');
            const actions = document.getElementById('alertActions');
            
            const icons = {
                info: { class: 'fa-info-circle', title: 'Information' },
                warning: { class: 'fa-exclamation-triangle', title: 'Warning' },
                error: { class: 'fa-times-circle', title: 'Error' },
                success: { class: 'fa-check-circle', title: 'Success' }
            };
            
            const config = icons[type] || icons.info;
            icon.innerHTML = `<i class="fas ${config.class}"></i>`;
            title.textContent = config.title;
            msg.textContent = message;
            actions.innerHTML = '';
            
            overlay.classList.add('show');
            alertBox.classList.remove('scale-90');
            alertBox.classList.add('scale-100');
            
            // Auto close after 5 seconds for info
            if (type === 'info') {
                setTimeout(() => {
                    closeAlert();
                }, 5000);
            }
        }

        function showLoginAlert() {
            const overlay = document.getElementById('customAlert');
            const alertBox = document.getElementById('alertBox');
            const icon = document.getElementById('alertIcon');
            const title = document.getElementById('alertTitle');
            const msg = document.getElementById('alertMessage');
            const actions = document.getElementById('alertActions');
            
            icon.innerHTML = '<i class="fas fa-lock"></i>';
            title.textContent = 'Login Required';
            msg.textContent = 'Please log in to purchase items from the shop.';
            actions.innerHTML = `
                <a href="<?php echo $base_path; ?>login" class="px-6 py-2 bg-[rgba(242,207,82,0.15)] border border-[rgba(201,162,39,0.3)] text-[#f2cf5b] hover:bg-[rgba(242,207,82,0.25)] transition-all duration-300 font-semibold text-sm force-wrap">
                    <i class="fas fa-sign-in-alt mr-2"></i> Log In Now
                </a>
                <button class="px-6 py-2 bg-[rgba(255,255,255,0.05)] border border-[rgba(255,255,255,0.1)] text-gray-300 hover:bg-[rgba(255,255,255,0.1)] transition-all duration-300 font-semibold text-sm force-wrap" onclick="closeAlert()">
                    Continue Browsing
                </button>
            `;
            
            overlay.classList.add('show');
            alertBox.classList.remove('scale-90');
            alertBox.classList.add('scale-100');
        }

        window.closeAlert = function() {
            const overlay = document.getElementById('customAlert');
            const alertBox = document.getElementById('alertBox');
            alertBox.classList.remove('scale-100');
            alertBox.classList.add('scale-90');
            setTimeout(() => {
                overlay.classList.remove('show');
            }, 300);
        };

        document.getElementById('alertClose').addEventListener('click', closeAlert);
        document.getElementById('customAlert').addEventListener('click', function(e) {
            if (e.target === this) closeAlert();
        });

        // No characters alert
        document.querySelectorAll('.buy-button:not([href])').forEach(button => {
            if (<?php echo json_encode(empty($characters)); ?>) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    showCustomAlert('<?php echo translate('shop_js_no_characters', 'You have no characters available. Please create a character first.'); ?>', 'warning');
                });
            }
        });
    });
</script>

</body>
</html>
<?php 
$site_db->close();
$char_db->close();
?>