<?php
require_once '../includes/session.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("Purchase attempt without login. Session: " . print_r($_SESSION, true));
    header("Location: /Sahtout/pages/login.php");
    exit;
}

// Check cooldown first
$cooldown_duration = 5; // 5 seconds
if (isset($_SESSION['last_purchase_time']) && (time() - $_SESSION['last_purchase_time']) < $cooldown_duration) {
    error_log("Purchase blocked due to cooldown: User ID: {$_SESSION['user_id']}");
    header("Location: /Sahtout/pages/shop.php?category=All&status=cooldown_active");
    exit;
}

// Validate POST data
if (!isset($_POST['item_id'], $_POST['character_id']) || !is_numeric($_POST['item_id']) || !is_numeric($_POST['character_id'])) {
    error_log("Invalid POST data: " . print_r($_POST, true));
    header("Location: /Sahtout/pages/shop.php?category=All&status=error");
    exit;
}

$item_id = (int)$_POST['item_id'];
$character_guid = (int)$_POST['character_id'];
$account_id = (int)$_SESSION['user_id'];

error_log("Buy Item Form Submitted: Item ID: $item_id, Character GUID: $character_guid, User ID: $account_id");

// Verify database connections
if ($site_db->connect_error || $char_db->connect_error || $world_db->connect_error) {
    error_log("Database connection failed: Site DB: {$site_db->connect_error}, Char DB: {$char_db->connect_error}, World DB: {$world_db->connect_error}");
    header("Location: /Sahtout/pages/shop.php?category=All&status=Database%20query%20error");
    exit;
}

// Begin transaction
$site_db->begin_transaction();
$char_db->begin_transaction();
$world_db->begin_transaction();

try {
    // Fetch item details, including is_item and entry
    $stmt = $site_db->prepare("SELECT name, point_cost, token_cost, stock, gold_amount, category, level_boost, at_login_flags, is_item, entry FROM shop_items WHERE item_id = ?");
    if (!$stmt) {
        error_log("Failed to prepare item query: " . $site_db->error);
        throw new Exception('Database query error');
    }
    $stmt->bind_param('i', $item_id);
    if (!$stmt->execute()) {
        error_log("Item query execution failed: " . $stmt->error);
        throw new Exception('Database query error');
    }
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        error_log("Item not found for item_id: $item_id");
        throw new Exception('Item not found');
    }
    $item = $result->fetch_assoc();
    $stmt->close();
    error_log("Item Details: " . print_r($item, true));

    // Check for excessive gold_amount to prevent overflow
    if ($item['gold_amount'] > 0) {
        $max_copper = 4294967295; // Max for INT UNSIGNED
        $gold_in_copper = $item['gold_amount'] * 10000;
        if ($gold_in_copper > $max_copper) {
            error_log("Gold amount too large: gold_amount={$item['gold_amount']}, copper=$gold_in_copper, max=$max_copper");
            throw new Exception('Gold amount too large');
        }
    }

    // Fetch user currency
    $stmt = $site_db->prepare("SELECT points, tokens FROM user_currencies WHERE account_id = ?");
    if (!$stmt) {
        error_log("Failed to prepare currency query: " . $site_db->error);
        throw new Exception('Database query error');
    }
    $stmt->bind_param('i', $account_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        error_log("User currency not found for account_id: $account_id");
        throw new Exception('User currency not found');
    }
    $user = $result->fetch_assoc();
    $stmt->close();
    error_log("User Currency: Points: {$user['points']}, Tokens: {$user['tokens']}");

    // Check stock
    if ($item['stock'] !== null && $item['stock'] <= 0) {
        error_log("Item out of stock: item_id=$item_id");
        throw new Exception('out_of_stock');
    }

    // Check user currency
    if ($user['points'] < $item['point_cost'] || $user['tokens'] < $item['token_cost']) {
        error_log("Insufficient funds: Points needed={$item['point_cost']}, Available={$user['points']}; Tokens needed={$item['token_cost']}, Available={$user['tokens']}");
        throw new Exception('insufficient_funds');
    }

    // Check if character is offline and fetch level
    $stmt = $char_db->prepare("SELECT online, money, name, level FROM characters WHERE guid = ? AND account = ?");
    $stmt->bind_param('ii', $character_guid, $account_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        error_log("Character not found or not owned: guid=$character_guid, account=$account_id");
        throw new Exception('character_not_found');
    }
    $character = $result->fetch_assoc();
    $stmt->close();
    error_log("Character Details: " . print_r($character, true));

    if ($character['online'] == 1) {
        error_log("Character is online: guid=$character_guid");
        throw new Exception('character_online');
    }

    // Check level for Service category with level_boost
    if (strtolower($item['category']) === 'service' && $item['level_boost'] !== null) {
        if ($character['level'] >= $item['level_boost']) {
            error_log("Character level too high: current_level={$character['level']}, level_boost={$item['level_boost']}");
            throw new Exception('level_too_high');
        }
    }

    // Update user currency
    $new_points = $user['points'] - $item['point_cost'];
    $new_tokens = $user['tokens'] - $item['token_cost'];
    $stmt = $site_db->prepare("UPDATE user_currencies SET points = ?, tokens = ?, last_updated = NOW() WHERE account_id = ?");
    $stmt->bind_param('iii', $new_points, $new_tokens, $account_id);
    $stmt->execute();
    $stmt->close();
    error_log("Updated Currency: New Points: $new_points, New Tokens: $new_tokens");

    // Update stock
    if ($item['stock'] !== null) {
        $new_stock = $item['stock'] - 1;
        $stmt = $site_db->prepare("UPDATE shop_items SET stock = ?, last_updated = NOW() WHERE item_id = ?");
        $stmt->bind_param('ii', $new_stock, $item_id);
        $stmt->execute();
        $stmt->close();
        error_log("Updated Stock: New Stock: $new_stock");
    }

    // Record the purchase
    $stmt = $site_db->prepare("INSERT INTO purchases (account_id, item_id) VALUES (?, ?)");
    $stmt->bind_param('ii', $account_id, $item_id);
    $stmt->execute();
    $stmt->close();
    error_log("Purchase Recorded: Item ID: $item_id");

    // Set last purchase time in session
    $_SESSION['last_purchase_time'] = time();
    error_log("Set last_purchase_time: " . $_SESSION['last_purchase_time']);

    // Handle different item categories
    if (strtolower($item['category']) === 'gold' && $item['gold_amount'] > 0) {
        // Check if adding gold would exceed max
        $new_money = $character['money'] + $gold_in_copper;
        if ($new_money > $max_copper) {
            error_log("Total money would exceed limit: current={$character['money']}, adding=$gold_in_copper, total=$new_money, max=$max_copper");
            throw new Exception('Total money exceeds limit');
        }

        // Update character's money
        $stmt = $char_db->prepare("UPDATE characters SET money = ? WHERE guid = ?");
        $stmt->bind_param('ii', $new_money, $character_guid);
        $stmt->execute();
        $stmt->close();
        error_log("Gold Added: $gold_in_copper copper to Character GUID: $character_guid, New Money: $new_money");

        // Log purchase in website_activity_log
        $stmt = $site_db->prepare("INSERT INTO website_activity_log (account_id, character_name, action, timestamp, details) VALUES (?, ?, 'Purchase Gold', UNIX_TIMESTAMP(), ?)");
        $character_name = $character['name'];
        $details = "Purchased {$item['gold_amount']} gold for character GUID $character_guid";
        $stmt->bind_param("iss", $account_id, $character_name, $details);
        $stmt->execute();
        $stmt->close();
        error_log("Logged Purchase: $details");
    } elseif (strtolower($item['category']) === 'service' && $item['level_boost'] !== null) {
        // Update character level
        $stmt = $char_db->prepare("UPDATE characters SET level = ? WHERE guid = ?");
        $stmt->bind_param('ii', $item['level_boost'], $character_guid);
        $stmt->execute();
        $stmt->close();
        error_log("Level Updated: New Level: {$item['level_boost']} for Character GUID: $character_guid");

        // Log level-up purchase in website_activity_log
        $stmt = $site_db->prepare("INSERT INTO website_activity_log (account_id, character_name, action, timestamp, details) VALUES (?, ?, 'Purchase Level Boost', UNIX_TIMESTAMP(), ?)");
        $character_name = $character['name'];
        $details = "Leveled character GUID $character_guid to level {$item['level_boost']} via item {$item['name']} (ID: $item_id)";
        $stmt->bind_param("iss", $account_id, $character_name, $details);
        $stmt->execute();
        $stmt->close();
        error_log("Logged Level Boost Purchase: $details");
    } elseif (strtolower($item['category']) === 'service' && $item['at_login_flags'] > 0) {
        // Apply at_login flags for character customization
        $stmt = $char_db->prepare("UPDATE characters SET at_login = ? WHERE guid = ?");
        $stmt->bind_param('ii', $item['at_login_flags'], $character_guid);
        $stmt->execute();
        $stmt->close();
        error_log("Character Customization Enabled: at_login set to {$item['at_login_flags']} for Character GUID: $character_guid");

        // Determine actions for logging
        $actions = [];
        if ($item['at_login_flags'] & 1) $actions[] = "Rename";
        if ($item['at_login_flags'] & 2) $actions[] = "Reset Spells";
        if ($item['at_login_flags'] & 4) $actions[] = "Reset Talents";
        if ($item['at_login_flags'] & 8) $actions[] = "Customize";
        if ($item['at_login_flags'] & 16) $actions[] = "Reset Pet Talents";
        if ($item['at_login_flags'] & 32) $actions[] = "First Login";
        if ($item['at_login_flags'] & 64) $actions[] = "Faction Change";
        if ($item['at_login_flags'] & 128) $actions[] = "Race Change";
        $action_list = implode(", ", $actions);

        // Log customization purchase in website_activity_log
        $stmt = $site_db->prepare("INSERT INTO website_activity_log (account_id, character_name, action, timestamp, details) VALUES (?, ?, 'Purchase Character Customization', UNIX_TIMESTAMP(), ?)");
        $character_name = $character['name'];
        $details = "Applied customization ($action_list) for character GUID $character_guid via item {$item['name']} (ID: $item_id)";
        $stmt->bind_param("iss", $account_id, $character_name, $details);
        $stmt->execute();
        $stmt->close();
        error_log("Logged Customization Purchase: $details");
    } elseif ($item['is_item'] == 1 && $item['entry'] !== null) {
        // Send item via stored procedure
        $stmt = $world_db->prepare("CALL acore_characters.SendStoreItem(?, ?)");
        $stmt->bind_param('ii', $character_guid, $item['entry']);
        if (!$stmt->execute()) {
            error_log("Failed to execute SendStoreItem: Character GUID: $character_guid, Item Entry: {$item['entry']}, Error: {$stmt->error}");
            throw new Exception('Database query error');
        }
        $stmt->close();
        error_log("Item Sent via Stored Procedure: Character GUID: $character_guid, Item Entry: {$item['entry']}");

        // Log item purchase in website_activity_log
        $stmt = $site_db->prepare("INSERT INTO website_activity_log (account_id, character_name, action, timestamp, details) VALUES (?, ?, 'Purchase Item', UNIX_TIMESTAMP(), ?)");
        $character_name = $character['name'];
        $details = "Purchased item {$item['name']} (ID: $item_id, Entry: {$item['entry']}) sent via mail to character GUID $character_guid";
        $stmt->bind_param("iss", $account_id, $character_name, $details);
        $stmt->execute();
        $stmt->close();
        error_log("Logged Item Purchase: $details");
    } else {
        // Fallback for non-service, non-gold, non-item purchases (e.g., send mail with gold)
        $mailSubject = "Web Shop Purchase";
        $mailBody = "Thank you for your purchase of {$item['name']}.";
        $stationery = 61; // Or 0 if no design
        $money = $item['gold_amount'] * 10000; // Convert to copper

        $stmt = $world_db->prepare("INSERT INTO mail (messageType, stationery, sender, receiver, subject, body, has_items, has_money, money, cod, checked, deliver_time)
                                    VALUES (0, ?, 1, ?, ?, ?, 0, 1, ?, 0, 1, UNIX_TIMESTAMP())");
        $stmt->bind_param('iisssi', $stationery, $character_guid, $mailSubject, $mailBody, $money);
        $stmt->execute();
        $stmt->close();
        error_log("Mail Sent: $money copper to Character GUID: $character_guid");

        // Log non-service purchase in website_activity_log
        $stmt = $site_db->prepare("INSERT INTO website_activity_log (account_id, character_name, action, timestamp, details) VALUES (?, ?, 'Purchase Item', UNIX_TIMESTAMP(), ?)");
        $character_name = $character['name'];
        $details = "Purchased item {$item['name']} (ID: $item_id) for character GUID $character_guid";
        $stmt->bind_param("iss", $account_id, $character_name, $details);
        $stmt->execute();
        $stmt->close();
        error_log("Logged Purchase: $details");
    }

    // Commit changes
    $site_db->commit();
    $char_db->commit();
    $world_db->commit();
    error_log("Transaction Committed");

    // Redirect to success
    header("Location: /Sahtout/pages/shop.php?category=All&status=success");
    exit;

} catch (Exception $e) {
    $site_db->rollback();
    $char_db->rollback();
    $world_db->rollback();
    $error = $e->getMessage();
    error_log("Purchase Error: $error, Item ID: $item_id, Character GUID: $character_guid, User ID: $account_id");
    $status = in_array($error, ['out_of_stock', 'insufficient_funds', 'character_not_found', 'Database query error', 'Gold amount too large', 'Total money exceeds limit', 'character_online', 'level_too_high']) ? $error : 'error';
    error_log("Redirecting to shop with status: $status");
    header("Location: /Sahtout/pages/shop.php?category=All&status=$status");
    exit;
}
?>