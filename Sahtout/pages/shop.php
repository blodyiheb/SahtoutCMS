<?php
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../includes/paths.php'; // Include paths.php
require_once $project_root . 'includes/session.php';
require_once $project_root . 'includes/item_tooltip.php';
require_once $project_root . 'languages/language.php';

$page_class = 'shop';
include_once $project_root . 'includes/header.php';

$selected_category = isset($_GET['category']) ? $_GET['category'] : 'All';
$valid_categories = ['All', 'Service', 'Mount', 'Pet', 'Gold', 'Stuff'];
if (!in_array($selected_category, $valid_categories)) {
    $selected_category = 'All';
}

$category_images = [
    'Service' => 'img/shopimg/icons/category_service.gif',
    'Mount' => 'img/shopimg/icons/category_mount.jpg',
    'Pet' => 'img/shopimg/icons/category_pet.jpg',
    'Gold' => 'img/shopimg/icons/category_gold.webp',
    'Stuff' => 'img/shopimg/icons/category_stuff.jpg'
];

$query = "
    SELECT si.item_id, si.category, si.name, si.description, si.image, si.point_cost, si.token_cost, si.stock, si.level_boost, si.at_login_flags, sit.entry AS sit_entry
    FROM shop_items si
    LEFT JOIN site_items sit ON si.entry = sit.entry
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
            $status_message = '<p class="status success">' . translate('shop_status_success', 'Purchase successful!') . '</p>';
            break;
        case 'insufficient_funds':
            $status_message = '<p class="status error">' . translate('shop_status_insufficient_funds', 'Insufficient points or tokens.') . '</p>';
            break;
        case 'out_of_stock':
            $status_message = '<p class="status error">' . translate('shop_status_out_of_stock', 'Item is out of stock.') . '</p>';
            break;
        case 'error':
            $status_message = '<p class="status error">' . translate('shop_status_error', 'An error occurred during purchase. Check server logs for details.') . '</p>';
            break;
        case 'Database query error':
            $status_message = '<p class="status error">' . translate('shop_status_db_error', 'Database error occurred. Contact support.') . '</p>';
            break;
        case 'character_online':
            $status_message = '<p class="status error">' . translate('shop_status_character_online', 'Selected character must be logged out to complete the purchase.') . '</p>';
            break;
        case 'level_too_high':
            $status_message = '<p class="status error">' . translate('shop_status_level_too_high', 'Your character\'s level is too high for this level boost.') . '</p>';
            break;
        case 'character_not_found':
            $status_message = '<p class="status error">' . translate('shop_status_character_not_found', 'Selected character not found or not owned.') . '</p>';
            break;
        case 'cooldown_active':
            $status_message = '<p class="status error">' . translate('shop_status_cooldown_active', 'Please wait 5 seconds before making another purchase.') . '</p>';
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="shop">
    <div class="shop-container">
        <h1><?php echo $site_title_name ." ". translate('shop_title', 'Server Shop'); ?></h1>
        <?php if (!empty($_SESSION['user_id'])): ?>
            <div class="user-balance">
                <span class="points"><i class="fas fa-coins"></i> <?php echo translate('shop_points', 'Points'); ?>: <?php echo $points; ?></span>
                <span class="tokens"><i class="fas fa-gem"></i> <?php echo translate('shop_tokens', 'Tokens'); ?>: <?php echo $tokens; ?></span>
            </div>
        <?php else: ?>
            <p class="login-prompt"><?php echo str_replace('{base_path}', $base_path, translate('shop_login_prompt', 'Please <a href="{base_path}login">log in</a> to purchase items.')); ?></p>
        <?php endif; ?>

        <?php echo $status_message; ?>

        <nav class="shop-nav">
            <?php foreach ($valid_categories as $category): ?>
                <a href="#" 
                   class="category-button <?php echo $selected_category === $category ? 'active' : ''; ?>" 
                   data-category="<?php echo htmlspecialchars($category); ?>">
                    <?php if (isset($category_images[$category])): ?>
                        <img src="<?php echo $base_path . $category_images[$category]; ?>" alt="<?php echo translate('shop_category_' . strtolower($category) . '_icon', htmlspecialchars($category) . ' Icon'); ?>" class="category-icon">
                    <?php endif; ?>
                    <?php echo translate('shop_category_' . strtolower($category), htmlspecialchars($category)); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php if (empty($items)): ?>
            <p class="no-items"><?php echo translate('shop_no_items', 'No items available.'); ?></p>
        <?php else: ?>
            <?php foreach ($items as $category => $category_items): ?>
                <section class="shop-category" data-category="<?php echo htmlspecialchars($category); ?>" 
                         style="display: <?php echo ($selected_category === 'All' || $selected_category === $category) ? 'block' : 'none'; ?>">
                    <h2><?php echo translate('shop_category_' . strtolower($category), htmlspecialchars($category)); ?></h2>
                    <div class="item-grid">
                        <?php foreach ($category_items as $item): ?>
                            <div class="item-card" data-entry="<?php echo $item['sit_entry'] ? htmlspecialchars($item['sit_entry']) : ''; ?>">
                                <img src="<?php echo $base_path . ($item['image'] ?? 'img/shop/placeholder.png'); ?>" alt="<?php echo str_replace('{name}', htmlspecialchars($item['name']), translate('shop_item_image_alt', '{name}')); ?>">
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p><?php echo htmlspecialchars($item['description'] ?? translate('shop_no_description', 'No description available.')); ?></p>
                                <?php if ($category === 'Service'): ?>
                                    <?php if ($item['level_boost'] !== null): ?>
                                        <p class="level-boost"><?php echo translate('shop_level_boost', 'Level Boost'); ?>: <?php echo $item['level_boost']; ?></p>
                                    <?php endif; ?>
                                    <?php if ($item['at_login_flags'] & 1): ?>
                                        <p class="rename-character"><?php echo translate('shop_rename_character', 'Character Rename'); ?></p>
                                    <?php endif; ?>
                                    <?php if ($item['at_login_flags'] & 2): ?>
                                        <p class="reset-spells"><?php echo translate('shop_reset_spells', 'Reset Spells'); ?></p>
                                    <?php endif; ?>
                                    <?php if ($item['at_login_flags'] & 4): ?>
                                        <p class="reset-talents"><?php echo translate('shop_reset_talents', 'Reset Talents'); ?></p>
                                    <?php endif; ?>
                                    <?php if ($item['at_login_flags'] & 8): ?>
                                        <p class="customize-character"><?php echo translate('shop_customize_character', 'Character Customization'); ?></p>
                                    <?php endif; ?>
                                    <?php if ($item['at_login_flags'] & 16): ?>
                                        <p class="reset-pet-talents"><?php echo translate('shop_reset_pet_talents', 'Reset Pet Talents'); ?></p>
                                    <?php endif; ?>
                                    <?php if ($item['at_login_flags'] & 32): ?>
                                        <p class="first-login"><?php echo translate('shop_first_login', 'First Login'); ?></p>
                                    <?php endif; ?>
                                    <?php if ($item['at_login_flags'] & 64): ?>
                                        <p class="faction-change"><?php echo translate('shop_faction_change', 'Faction Change'); ?></p>
                                    <?php endif; ?>
                                    <?php if ($item['at_login_flags'] & 128): ?>
                                        <p class="race-change"><?php echo translate('shop_race_change', 'Race Change'); ?></p>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <div class="item-tooltip">
                                    <?php
                                    if (in_array($category, ['Stuff', 'Pet', 'Mount']) && $item['sit_entry']) {
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
                                <div class="item-cost">
                                    <?php if ($item['point_cost'] > 0): ?>
                                        <span class="points"><i class="fas fa-coins"></i> <?php echo $item['point_cost']; ?></span>
                                    <?php endif; ?>
                                    <?php if ($item['token_cost'] > 0): ?>
                                        <span class="tokens"><i class="fas fa-gem"></i> <?php echo $item['token_cost']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="item-stock">
                                    <?php if ($item['stock'] !== null): ?>
                                        <span><?php echo translate('shop_stock', 'Stock'); ?>: <?php echo $item['stock']; ?></span>
                                    <?php else: ?>
                                        <span><?php echo translate('shop_unlimited_stock', 'Unlimited Stock'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($_SESSION['user_id'])): ?>
                                    <form action="<?php echo $base_path; ?>buy_item" method="POST" class="purchase-form">
                                        <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                        <?php if (!empty($characters)): ?>
                                            <select name="character_id" class="character-select" required>
                                                <option value=""><?php echo translate('shop_select_character', 'Select a Character'); ?></option>
                                                <?php foreach ($characters as $char): ?>
                                                    <option value="<?php echo htmlspecialchars($char['id']); ?>">
                                                        <?php echo htmlspecialchars($char['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php else: ?>
                                            <p class="no-characters"><?php echo translate('shop_no_characters', 'No characters available.'); ?></p>
                                        <?php endif; ?>
                                        <button type="submit" class="buy-button" 
                                                <?php echo ($item['stock'] === 0 && $item['stock'] !== null) || empty($characters) || $cooldown_active ? 'disabled' : ''; ?>
                                                data-item-id="<?php echo $item['item_id']; ?>">
                                            <?php echo $cooldown_active ? str_replace('{seconds}', $remaining_cooldown, translate('shop_wait_cooldown', 'Wait {seconds}s')) : translate('shop_buy_now', 'Buy Now'); ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a href="<?php echo $base_path; ?>login" class="buy-button"><?php echo translate('shop_login_to_buy', 'Log in to Buy'); ?></a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const buttons = document.querySelectorAll('.category-button');
        const categories = document.querySelectorAll('.shop-category');
        const noItemsMessage = document.querySelector('.no-items');

        buttons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const selectedCategory = this.getAttribute('data-category');
                buttons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                let hasItems = false;
                categories.forEach(category => {
                    if (selectedCategory === 'All' || category.getAttribute('data-category') === selectedCategory) {
                        category.style.display = 'block';
                        hasItems = true;
                    } else {
                        category.style.display = 'none';
                    }
                });
                if (noItemsMessage) {
                    noItemsMessage.style.display = hasItems ? 'none' : 'block';
                }
                const newUrl = window.location.pathname + '?category=' + encodeURIComponent(selectedCategory);
                window.history.pushState({}, '', newUrl);
            });
        });

        const purchaseForms = document.querySelectorAll('.purchase-form');
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
                    button.style.background = disabled ? '#666' : '#ffd700';
                    button.style.cursor = disabled ? 'not-allowed' : 'pointer';
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

        purchaseForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const characterSelect = this.querySelector('.character-select');
                if (characterSelect && !characterSelect.value) {
                    e.preventDefault();
                    alert('<?php echo translate('shop_js_select_character', 'Please select a character to purchase this item.'); ?>');
                    return;
                }
                if (isPurchaseBlocked) {
                    e.preventDefault();
                    alert('<?php echo translate('shop_js_cooldown_active', 'Please wait {seconds} seconds before making another purchase.'); ?>'.replace('{seconds}', remainingCooldown));
                    return;
                }
            });
        });

        const loginButtons = document.querySelectorAll('.buy-button[href]');
        loginButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                alert('<?php echo translate('shop_js_login_required', 'Please log in to purchase items.'); ?>');
            });
        });

        const buyButtons = document.querySelectorAll('.buy-button:not([href])');
        buyButtons.forEach(button => {
            if (<?php echo json_encode(empty($characters)); ?>) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    alert('<?php echo translate('shop_js_no_characters', 'You have no characters available. Please create a character first.'); ?>');
                });
            }
        });
    });
    </script>

    <style>
    body {
        background: url('<?php echo $base_path; ?>img/backgrounds/bg-shop.jpg') no-repeat center center fixed;
        background-size: cover;
    }
   .shop-container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 1rem;
        min-height: calc(100vh - 150px); /* Adjust 150px based on header/footer height */
        display: flex;
        flex-direction: column;
    }
    .shop-container h1 {
        text-align: center;
        color: #ffd700;
        font-family: 'UnifrakturCook', cursive;
        font-size: 2.5rem;
        margin-bottom: 2rem;
    }
    .user-balance {
        display: flex;
        justify-content: center;
        gap: 1.5rem;
        margin-bottom: 2rem;
        padding: 1rem;
        background: linear-gradient(135deg, rgba(0, 0, 0, 0.8) 0%, rgba(20, 20, 20, 0.9) 100%);
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
    }
    .user-balance span {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.6rem 1.2rem;
        border-radius: 8px;
        font-size: 1.2rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .user-balance .points {
        background: linear-gradient(135deg, #ffd700 0%, #f1c40f 100%);
        color: #1a1a1a;
        border: 2px solid #e6c200;
    }
    .user-balance .points:hover {
        background: linear-gradient(135deg, #e6c200 0%, #d4ac0d 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(241, 196, 15, 0.5);
    }
    .user-balance .tokens {
        background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
        color: #fff;
        border: 2px solid #8e44ad;
    }
    .user-balance .tokens:hover {
        background: linear-gradient(135deg, #8e44ad 0%, #7d3c98 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(155, 89, 182, 0.5);
    }
    .user-balance .points i, .user-balance .tokens i {
        font-size: 1.3rem;
    }
    .login-prompt {
        text-align: center;
        color: #fff;
        font-size: 1.1rem;
        margin-bottom: 2rem;
        padding: 1rem;
        background: rgba(0, 0, 0, 0.8);
        border-radius: 8px;
    }
    .login-prompt a {
        color: #ffd700;
        text-decoration: none;
    }
    .login-prompt a:hover {
        text-decoration: underline;
    }
    .shop-nav {
        display: flex;
        justify-content: center;
        gap: 1rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        background: rgba(0, 0, 0, 0.7);
        padding: 1rem;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
    }
    .shop-nav a.category-button {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        text-decoration: none;
        font-size: 1.1rem;
        font-weight: 600;
        padding: 0.8rem 1.5rem;
        border-radius: 8px;
        background: linear-gradient(135deg, #ffd700 0%, #f1c40f 100%);
        color: #1a1a1a;
        border: 2px solid #e6c200;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    .shop-nav a.category-button:hover {
        background: linear-gradient(135deg, #e6c200 0%, #d4ac0d 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(241, 196, 15, 0.5);
        color: #fff;
    }
    .shop-nav a.category-button.active {
        background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);
        color: #fff;
        border-color: #d35400;
        box-shadow: 0 4px 15px rgba(211, 84, 0, 0.5);
        transform: scale(1.05);
    }
    .shop-nav a.category-button .category-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #fff;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        transition: transform 0.3s ease;
    }
    .shop-nav a.category-button:hover .category-icon {
        transform: scale(1.1);
    }
    .shop-nav a.category-button::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        transform: translate(-50%, -50%);
        transition: width 0.4s ease, height 0.4s ease;
    }
    .shop-nav a.category-button:hover::after {
        width: 200%;
        height: 200%;
    }
     .no-items {
        text-align: center;
        color: #fff;
        font-size: 1.2rem;
        padding: 1rem;
        background: rgba(0, 0, 0, 0.8);
        border-radius: 8px;
        flex-grow: 1; /* Ensure no-items expands to push footer */
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 200px; /* Minimum height for visibility */
    }
    .status {
        text-align: center;
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 1.5rem;
        padding: 0.8rem 1.5rem;
        border-radius: 8px;
        animation: fadeIn 0.5s ease-in-out;
    }
    .status.success {
        background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
        color: #fff;
        border: 2px solid #27ae60;
        box-shadow: 0 4px 15px rgba(46, 204, 113, 0.5);
    }
    .status.error {
        background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        border: 2px solid #c0392b;
        box-shadow: 0 4px 15px rgba(231, 76, 60, 0.5);
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .shop-category {
        margin-bottom: 3rem;
    }
    .shop-category h2 {
        color: #ffd700;
        font-size: 1.8rem;
        margin-bottom: 1rem;
        border-bottom: 1px solid #ffd700;
        padding-bottom: 0.5rem;
    }
    .item-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1.5rem;
    }
    .item-card {
        background: rgba(0, 0, 0, 0.8);
        border: 1px solid #ffd700;
        border-radius: 8px;
        padding: 1rem;
        text-align: center;
        transition: transform 0.3s ease;
        position: relative;
    }
    .item-card:hover {
        border: #00ff00 solid 2px;
        cursor: pointer;
    }
    .item-card:hover .item-tooltip {
        display: block;
    }
    .item-card img {
        width: 100%;
        max-width: 110%;
        max-height: 250px;
        min-height: 250px;
        object-fit: cover;
        border-radius: 5px;
        margin-bottom: 1rem;
    }
    .item-card h3 {
        color: #fff;
        font-size: 1.2rem;
        margin: 0.5rem 0;
    }
    .item-card p {
        color: #ccc;
        font-size: 0.9rem;
        margin-bottom: 1rem;
    }
    .item-card .level-boost, .item-card .rename-character, .item-card .reset-spells, 
    .item-card .reset-talents, .item-card .customize-character, .item-card .reset-pet-talents, 
    .item-card .first-login, .item-card .faction-change, .item-card .race-change {
        color: #00ff00;
        font-size: 0.9rem;
        font-weight: bold;
        margin-bottom: 1rem;
    }
    .item-cost {
        display: flex;
        justify-content: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    .item-cost .points {
        color: #f1c40f;
    }
    .item-cost .tokens {
        color: #9b59b6;
    }
    .item-stock {
        color: #fff;
        font-size: 0.9rem;
        margin-bottom: 1rem;
    }
    .buy-button {
        background: #ffd700;
        color: #000;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1rem;
        transition: background 0.3s;
        width: 100%;
        margin-top: 0.5rem;
    }
    .buy-button:hover {
        background: #e6c200;
    }
    .buy-button:disabled {
        background: #666;
        cursor: not-allowed;
    }
    .character-select {
        width: 100%;
        margin-bottom: 0.75rem;
        padding: 0.4rem;
        font-size: 0.95rem;
        border-radius: 5px;
        border: 1px solid #ccc;
    }
    .no-characters {
        color: #e74c3c;
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }
    .item-tooltip {
        display: none;
        position: absolute;
        top: 0;
        left: 100%;
        width: 300px;
        z-index: 100;
        margin-left: 10px;
    }
    @media (max-width: 768px) {
        .item-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        }
        .shop-container h1 {
            font-size: 2rem;
        }
        .shop-category h2 {
            font-size: 1.5rem;
        }
        .user-balance {
            flex-direction: column;
            gap: 0.8rem;
            padding: 0.8rem;
        }
        .user-balance span {
            font-size: 1rem;
            padding: 0.5rem 1rem;
        }
        .user-balance .points i, .user-balance .tokens i {
            font-size: 1.1rem;
        }
        .shop-nav {
            gap: 0.8rem;
            padding: 0.8rem;
        }
        .shop-nav a.category-button {
            font-size: 1rem;
            padding: 0.6rem 1.2rem;
        }
        .shop-nav .category-icon {
            width: 32px;
            height: 32px;
        }
        .status {
            font-size: 1rem;
            padding: 0.6rem 1rem;
        }
        .item-tooltip {
            width: 250px;
            left: 50%;
            top: 100%;
            transform: translateX(-50%);
            margin-left: 0;
            margin-top: 10px;
        }
    }
    </style>

    <?php include_once $project_root . 'includes/footer.php'; ?>
</body>
</html>
<?php 
$site_db->close();
$char_db->close();
?>