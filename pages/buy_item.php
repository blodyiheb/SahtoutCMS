<?php
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../includes/paths.php'; // Include paths.php
require_once $project_root . 'includes/session.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("Purchase attempt without login");
    header("Location: {$base_path}login");
    exit;
}

// Check cooldown first
$cooldown_duration = 5; // 5 seconds
if (isset($_SESSION['last_purchase_time']) && (time() - $_SESSION['last_purchase_time']) < $cooldown_duration) {
    error_log("Purchase blocked due to cooldown: User ID: {$_SESSION['user_id']}");
    header("Location: {$base_path}shop?category=All&status=cooldown_active");
    exit;
}

// Validate POST data
if (!isset($_POST['item_id'], $_POST['character_id']) || !is_numeric($_POST['item_id']) || !is_numeric($_POST['character_id'])) {
    error_log("Invalid POST data");
    header("Location: {$base_path}shop?category=All&status=error");
    exit;
}

$item_id = (int)$_POST['item_id'];
$character_guid = (int)$_POST['character_id'];
$account_id = (int)$_SESSION['user_id'];

error_log("Buy Item Form Submitted: Item ID: $item_id, Character GUID: $character_guid, User ID: $account_id");

// Verify database connections (site and character DBs)
if ($site_db->connect_error || $char_db->connect_error) {
    error_log("Database connection failed: Site DB: {$site_db->connect_error}, Char DB: {$char_db->connect_error}");
    header("Location: {$base_path}shop?category=All&status=Database%20query%20error");
    exit;
}

// Initialize transaction state tracking flags
$site_transaction_started = false;
$char_transaction_started = false;

if (!$site_db->begin_transaction()) {
    error_log("Failed to start site_db transaction");
    header("Location: {$base_path}shop?category=All&status=Database%20query%20error");
    exit;
}
$site_transaction_started = true;

if (!$char_db->begin_transaction()) {
    error_log("Failed to start char_db transaction");
    if ($site_transaction_started) {
        $site_db->rollback();
    }
    header("Location: {$base_path}shop?category=All&status=Database%20query%20error");
    exit;
}
$char_transaction_started = true;

try {
    // Fetch item details, including set metadata.
    $stmt = $site_db->prepare("SELECT name, point_cost, token_cost, stock, gold_amount, category, level_boost, at_login_flags, is_item, is_set, entry, itemset_id FROM shop_items WHERE item_id = ?");
    if (!$stmt) {
        error_log("Failed to prepare item query: " . $site_db->error);
        throw new Exception('Database query error');
    }
    $stmt->bind_param('i', $item_id);
    if (!$stmt->execute()) {
        error_log("Item query execution failed: " . $stmt->error);
        $stmt->close();
        throw new Exception('Database query error');
    }
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $stmt->close();
        error_log("Item not found for item_id: $item_id");
        throw new Exception('Item not found');
    }
    $item = $result->fetch_assoc();
    $stmt->close();
    error_log("Item Details: " . print_r($item, true));

    // Check for excessive gold_amount to prevent overflow
    $max_copper = 4294967295; // Max for INT UNSIGNED
    $gold_in_copper = 0;
    if ($item['gold_amount'] > 0) {
        $gold_in_copper = $item['gold_amount'] * 10000;
        if ($gold_in_copper > $max_copper) {
            error_log("Gold amount too large: gold_amount={$item['gold_amount']}, copper=$gold_in_copper, max=$max_copper");
            throw new Exception('Gold amount too large');
        }
    }

    // Check if character is offline, fetch level, and lock row via FOR UPDATE
    $stmt = $char_db->prepare("SELECT online, money, name, level FROM characters WHERE guid = ? AND account = ? FOR UPDATE");
    if (!$stmt) {
        error_log("Failed to prepare character query: " . $char_db->error);
        throw new Exception('Database query error');
    }
    $stmt->bind_param('ii', $character_guid, $account_id);
    if (!$stmt->execute()) {
        error_log("Character query execution failed: " . $stmt->error);
        $stmt->close();
        throw new Exception('Database query error');
    }
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $stmt->close();
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

    // 1. ATOMICALLY Update user currency & verify sufficient balance
    $stmt = $site_db->prepare("
        UPDATE user_currencies 
        SET points = points - ?, 
            tokens = tokens - ?, 
            last_updated = NOW() 
        WHERE account_id = ? 
          AND points >= ? 
          AND tokens >= ?
    ");
    if (!$stmt) {
        error_log("Failed to prepare currency update: " . $site_db->error);
        throw new Exception('Database query error');
    }
    $stmt->bind_param('iiiii', $item['point_cost'], $item['token_cost'], $account_id, $item['point_cost'], $item['token_cost']);
    
    if (!$stmt->execute()) {
        error_log("Currency update execution failed: " . $stmt->error);
        $stmt->close();
        throw new Exception('Database query error');
    }

    if ($stmt->affected_rows !== 1) {
        $stmt->close();
        error_log("Insufficient funds or concurrent update failure for account_id: $account_id");
        throw new Exception('insufficient_funds');
    }
    $stmt->close();
    error_log("Currency deducted atomically for account_id: $account_id");

    // 2. ATOMICALLY Update stock
    if ($item['stock'] !== null) {
        $stmt = $site_db->prepare("
            UPDATE shop_items 
            SET stock = stock - 1, 
                last_updated = NOW() 
            WHERE item_id = ? 
              AND stock IS NOT NULL 
              AND stock > 0
        ");
        if (!$stmt) {
            error_log("Failed to prepare stock update: " . $site_db->error);
            throw new Exception('Database query error');
        }
        $stmt->bind_param('i', $item_id);
        
        if (!$stmt->execute()) {
            error_log("Stock update execution failed: " . $stmt->error);
            $stmt->close();
            throw new Exception('Database query error');
        }

        if ($stmt->affected_rows !== 1) {
            $stmt->close();
            error_log("Item out of stock during concurrent purchase for item_id: $item_id");
            throw new Exception('out_of_stock');
        }
        $stmt->close();
        error_log("Stock deducted atomically for item_id: $item_id");
    }

    // Record the purchase
    $stmt = $site_db->prepare("INSERT INTO purchases (account_id, item_id) VALUES (?, ?)");
    if (!$stmt) {
        error_log("Failed to prepare purchase insert: " . $site_db->error);
        throw new Exception('Database query error');
    }
    $stmt->bind_param('ii', $account_id, $item_id);
    if (!$stmt->execute()) {
        error_log("Purchase insert execution failed: " . $stmt->error);
        $stmt->close();
        throw new Exception('Database query error');
    }
    $stmt->close();
    error_log("Purchase Recorded: Item ID: $item_id");

    $sendStoreItem = function ($characterGuid, $entry) use ($char_db, $db_char_name) {
        $query = "CALL `" . $db_char_name . "`.`SendStoreItem`(?, ?)";
        $send_stmt = $char_db->prepare($query);
        if (!$send_stmt) {
            error_log("SendStoreItem prepare failed: " . $char_db->error);
            throw new Exception('Database query error');
        }

        $send_stmt->bind_param('ii', $characterGuid, $entry);
        if (!$send_stmt->execute()) {
            error_log("SendStoreItem failed: errno={$send_stmt->errno}, error={$send_stmt->error}, character={$characterGuid}, entry={$entry}");
            $send_stmt->close();
            throw new Exception('Database query error');
        }

        $send_stmt->close();

        while ($char_db->more_results() && $char_db->next_result()) {
            $flush = $char_db->use_result();
            if ($flush instanceof mysqli_result) {
                $flush->free();
            }
        }
    };

    // Handle different item categories
    if (strtolower($item['category']) === 'gold' && $item['gold_amount'] > 0) {
        $new_money = $character['money'] + $gold_in_copper;
        if ($new_money > $max_copper) {
            error_log("Total money would exceed limit: current={$character['money']}, adding=$gold_in_copper, total=$new_money, max=$max_copper");
            throw new Exception('Total money exceeds limit');
        }

        // Update character's money
        $stmt = $char_db->prepare("UPDATE characters SET money = ? WHERE guid = ?");
        if (!$stmt) {
            error_log("Failed to prepare gold update: " . $char_db->error);
            throw new Exception('Database query error');
        }
        $stmt->bind_param('ii', $new_money, $character_guid);
        if (!$stmt->execute()) {
            error_log("Gold update execution failed: " . $stmt->error);
            $stmt->close();
            throw new Exception('Database query error');
        }
        $stmt->close();
        error_log("Gold Added: $gold_in_copper copper to Character GUID: $character_guid, New Money: $new_money");

        // Log purchase in website_activity_log
        $stmt = $site_db->prepare("INSERT INTO website_activity_log (account_id, character_name, action, timestamp, details) VALUES (?, ?, 'Purchase Gold', UNIX_TIMESTAMP(), ?)");
        if ($stmt) {
            $character_name = $character['name'];
            $details = "Purchased {$item['gold_amount']} gold for character GUID $character_guid";
            $stmt->bind_param("iss", $account_id, $character_name, $details);
            if (!$stmt->execute()) {
                error_log("Activity log execution failed: " . $stmt->error);
            }
            $stmt->close();
        }
    } elseif (strtolower($item['category']) === 'service' && $item['level_boost'] !== null) {
        // Update character level
        $stmt = $char_db->prepare("UPDATE characters SET level = ? WHERE guid = ?");
        if (!$stmt) {
            error_log("Failed to prepare level update: " . $char_db->error);
            throw new Exception('Database query error');
        }
        $stmt->bind_param('ii', $item['level_boost'], $character_guid);
        if (!$stmt->execute()) {
            error_log("Level update execution failed: " . $stmt->error);
            $stmt->close();
            throw new Exception('Database query error');
        }
        $stmt->close();
        error_log("Level Updated: New Level: {$item['level_boost']} for Character GUID: $character_guid");

        // Log level-up purchase in website_activity_log
        $stmt = $site_db->prepare("INSERT INTO website_activity_log (account_id, character_name, action, timestamp, details) VALUES (?, ?, 'Purchase Level Boost', UNIX_TIMESTAMP(), ?)");
        if ($stmt) {
            $character_name = $character['name'];
            $details = "Leveled character GUID $character_guid to level {$item['level_boost']} via item {$item['name']} (ID: $item_id)";
            $stmt->bind_param("iss", $account_id, $character_name, $details);
            if (!$stmt->execute()) {
                error_log("Activity log execution failed: " . $stmt->error);
            }
            $stmt->close();
        }
    } elseif (strtolower($item['category']) === 'service' && $item['at_login_flags'] > 0) {
        // Apply at_login flags for character customization
        $stmt = $char_db->prepare("UPDATE characters SET at_login = ? WHERE guid = ?");
        if (!$stmt) {
            error_log("Failed to prepare at_login update: " . $char_db->error);
            throw new Exception('Database query error');
        }
        $stmt->bind_param('ii', $item['at_login_flags'], $character_guid);
        if (!$stmt->execute()) {
            error_log("at_login update execution failed: " . $stmt->error);
            $stmt->close();
            throw new Exception('Database query error');
        }
        $stmt->close();
        error_log("Character Customization Enabled: at_login set to {$item['at_login_flags']} for Character GUID: $character_guid");

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

        $stmt = $site_db->prepare("INSERT INTO website_activity_log (account_id, character_name, action, timestamp, details) VALUES (?, ?, 'Purchase Character Customization', UNIX_TIMESTAMP(), ?)");
        if ($stmt) {
            $character_name = $character['name'];
            $details = "Applied customization ($action_list) for character GUID $character_guid via item {$item['name']} (ID: $item_id)";
            $stmt->bind_param("iss", $account_id, $character_name, $details);
            if (!$stmt->execute()) {
                error_log("Activity log execution failed: " . $stmt->error);
            }
            $stmt->close();
        }
    } elseif ((int)$item['is_set'] === 1) {
        if (empty($item['itemset_id'])) {
            error_log("Set purchase missing itemset_id: item_id=$item_id");
            throw new Exception('Database query error');
        }

        $set_stmt = $site_db->prepare("SELECT entry, name FROM site_items WHERE itemset = ? ORDER BY entry");
        if (!$set_stmt) {
            error_log("Failed to prepare set items query: " . $site_db->error);
            throw new Exception('Database query error');
        }

        $set_stmt->bind_param('i', $item['itemset_id']);
        if (!$set_stmt->execute()) {
            error_log("Set items query execution failed: " . $set_stmt->error);
            $set_stmt->close();
            throw new Exception('Database query error');
        }

        $set_result = $set_stmt->get_result();
        $set_entries = [];
        while ($set_row = $set_result->fetch_assoc()) {
            $set_entries[] = (int)$set_row['entry'];
        }
        $set_stmt->close();

        if (empty($set_entries)) {
            error_log("No items found for itemset_id={$item['itemset_id']} (item_id=$item_id)");
            throw new Exception('Database query error');
        }

        foreach ($set_entries as $set_entry) {
            $sendStoreItem($character_guid, $set_entry);
            error_log("Set item sent: Character GUID: $character_guid, Item Entry: $set_entry");
        }

        $stmt = $site_db->prepare("INSERT INTO website_activity_log (account_id, character_name, action, timestamp, details) VALUES (?, ?, 'Purchase Item Set', UNIX_TIMESTAMP(), ?)");
        if ($stmt) {
            $character_name = $character['name'];
            $details = "Purchased set {$item['name']} (ID: $item_id, ItemSet: {$item['itemset_id']}) with " . count($set_entries) . " items for character GUID $character_guid";
            $stmt->bind_param("iss", $account_id, $character_name, $details);
            if (!$stmt->execute()) {
                error_log("Activity log execution failed: " . $stmt->error);
            }
            $stmt->close();
        }
    } elseif ($item['is_item'] == 1 && $item['entry'] !== null) {
        // Send single item via stored procedure
        $sendStoreItem($character_guid, (int)$item['entry']);
        error_log("Item Sent via Stored Procedure: Character GUID: $character_guid, Item Entry: {$item['entry']}");

        $stmt = $site_db->prepare("INSERT INTO website_activity_log (account_id, character_name, action, timestamp, details) VALUES (?, ?, 'Purchase Item', UNIX_TIMESTAMP(), ?)");
        if ($stmt) {
            $character_name = $character['name'];
            $details = "Purchased item {$item['name']} (ID: $item_id, Entry: {$item['entry']}) sent via mail to character GUID $character_guid";
            $stmt->bind_param("iss", $account_id, $character_name, $details);
            if (!$stmt->execute()) {
                error_log("Activity log execution failed: " . $stmt->error);
            }
            $stmt->close();
        }
    } else {
        error_log(
            "Invalid shop item configuration: " .
            "item_id={$item_id}, " .
            "name={$item['name']}, " .
            "category={$item['category']}, " .
            "is_item={$item['is_item']}, " .
            "is_set={$item['is_set']}, " .
            "entry={$item['entry']}, " .
            "itemset_id={$item['itemset_id']}"
        );
        throw new Exception('invalid_item_configuration');
    }

    // Commit changes across active connections with explicit error checks
    if (!$site_db->commit()) {
        throw new Exception('Database query error');
    }
    $site_transaction_started = false;

    if (!$char_db->commit()) {
        throw new Exception('Database query error');
    }
    $char_transaction_started = false;

    error_log("Transaction Committed");

    // Set last purchase time in session ONLY after successful commit
    $_SESSION['last_purchase_time'] = time();
    error_log("Set last_purchase_time: " . $_SESSION['last_purchase_time']);

    // Redirect to success
    header("Location: {$base_path}shop?category=All&status=success");
    exit;

} catch (Exception $e) {
    if ($site_transaction_started) {
        $site_db->rollback();
    }
    if ($char_transaction_started) {
        $char_db->rollback();
    }
    
    $error = $e->getMessage();
    error_log("Purchase Error: $error, Item ID: $item_id, Character GUID: $character_guid, User ID: $account_id");
    $status = in_array($error, ['out_of_stock', 'insufficient_funds', 'character_not_found', 'Database query error', 'Gold amount too large', 'Total money exceeds limit', 'character_online', 'level_too_high', 'invalid_item_configuration']) ? $error : 'error';
    error_log("Redirecting to shop with status: $status");
    header("Location: {$base_path}shop?category=All&status=$status");
    exit;
}
?>