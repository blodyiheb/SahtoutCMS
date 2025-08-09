<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - Buy Gold</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        select, button {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }
        button {
            background-color: #28a745;
            color: #fff;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #218838;
        }
        .error {
            color: red;
            text-align: center;
        }
        .success {
            color: green;
            text-align: center;
        }
        .debug {
            color: blue;
            text-align: center;
            margin-bottom: 15px;
        }
        .item-list {
            margin-top: 15px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .item-list table {
            width: 100%;
            border-collapse: collapse;
        }
        .item-list th, .item-list td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Shop - Purchase 1000 Gold</h1>
        <?php
        // Start session
        session_start();

        // Include database configuration
        require_once '../includes/config.php';

        // Check database connections
        if ($site_db->connect_error || $char_db->connect_error) {
            die("<p class='error'>Connection failed: " . $site_db->connect_error . "</p>");
        }

        // Debug session information
        $debug_message = "";
        if (!isset($_SESSION)) {
            $debug_message .= "<p class='error'>Session not started properly.</p>";
        } else {
            $debug_message .= "<p class='debug'>Session status: " . (session_status() == PHP_SESSION_ACTIVE ? "Active" : "Inactive") . "</p>";
            $debug_message .= "<p class='debug'>Session ID: " . session_id() . "</p>";
            $debug_message .= "<p class='debug'>Session user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : "Not set") . "</p>";
        }

        // Check if user is logged in
        $account_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
        if ($account_id == 0) {
            echo "<p class='error'>Please log in to continue. <a href='/sahtout/pages/login.php'>Login here</a>.</p>";
            echo $debug_message;
            exit;
        }

        // Fetch user's characters
        $characters = [];
        $sql = "SELECT guid, name FROM characters WHERE account = ?";
        $stmt = $char_db->prepare($sql);
        if (!$stmt) {
            echo "<p class='error'>Error preparing character query: " . $char_db->error . "</p>";
            exit;
        }
        $stmt->bind_param("i", $account_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $characters[] = $row;
        }
        $stmt->close();

        // Fetch user currencies
        $sql = "SELECT points, tokens FROM user_currencies WHERE account_id = ?";
        $stmt = $site_db->prepare($sql);
        if (!$stmt) {
            echo "<p class='error'>Error preparing currency query: " . $site_db->error . "</p>";
            exit;
        }
        $stmt->bind_param("i", $account_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_currency = $result->fetch_assoc();
        $stmt->close();

        $points = $user_currency ? $user_currency['points'] : 0;
        $tokens = $user_currency ? $user_currency['tokens'] : 0;

        // Fetch available gold items for debugging
        $available_items = [];
        $sql = "SELECT item_id, name, category, point_cost, token_cost, gold_amount FROM shop_items WHERE category = 'gold'";
        $result = $site_db->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $available_items[] = $row;
            }
        }

        // Handle purchase
        $message = "";
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['buy_gold']) && isset($_POST['character_guid'])) {
            $character_guid = (int)$_POST['character_guid'];
            $item_id = 4; // Assuming item_id for 1000 gold is 1, adjust as needed

            // Fetch item details
            $sql = "SELECT point_cost, token_cost, gold_amount FROM shop_items WHERE item_id = ? AND category = 'gold'";
            $stmt = $site_db->prepare($sql);
            if (!$stmt) {
                echo "<p class='error'>Error preparing item query: " . $site_db->error . "</p>";
                exit;
            }
            $stmt->bind_param("i", $item_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $item = $result->fetch_assoc();
            $stmt->close();

            if ($item) {
                $point_cost = $item['point_cost'];
                $token_cost = $item['token_cost'];
                $gold_amount = $item['gold_amount'];

                // Check if user has enough points or tokens
                if ($points >= $point_cost && $tokens >= $token_cost) {
                    // Start transaction
                    $site_db->begin_transaction();
                    $char_db->begin_transaction();
                    try {
                        // Update user currencies
                        $sql = "UPDATE user_currencies SET points = points - ?, tokens = tokens - ?, last_updated = NOW() WHERE account_id = ?";
                        $stmt = $site_db->prepare($sql);
                        $stmt->bind_param("iii", $point_cost, $token_cost, $account_id);
                        $stmt->execute();
                        $stmt->close();

                        // Update character gold
                        $sql = "UPDATE characters SET money = money + ? WHERE guid = ? AND account = ?";
                        $stmt = $char_db->prepare($sql);
                        $stmt->bind_param("iii", $gold_amount, $character_guid, $account_id);
                        $stmt->execute();
                        $stmt->close();

                        // Log purchase in website_activity_log
                        $sql = "INSERT INTO website_activity_log (account_id, character_name, action, timestamp, details) VALUES (?, ?, 'Purchase Gold', UNIX_TIMESTAMP(), ?)";
                        $stmt = $site_db->prepare($sql);
                        $character_name = "";
                        foreach ($characters as $char) {
                            if ($char['guid'] == $character_guid) {
                                $character_name = $char['name'];
                                break;
                            }
                        }
                        $details = "Purchased $gold_amount gold for character GUID $character_guid";
                        $stmt->bind_param("iss", $account_id, $character_name, $details);
                        $stmt->execute();
                        $stmt->close();

                        // Commit transactions
                        $site_db->commit();
                        $char_db->commit();
                        $message = "<p class='success'>Successfully purchased $gold_amount gold for your character!</p>";
                    } catch (Exception $e) {
                        $site_db->rollback();
                        $char_db->rollback();
                        $message = "<p class='error'>Purchase failed: " . $e->getMessage() . "</p>";
                    }
                } else {
                    $message = "<p class='error'>Insufficient points or tokens.</p>";
                }
            } else {
                $message = "<p class='error'>Item not found or invalid category.</p>";
                if (empty($available_items)) {
                    $message .= "<p class='error'>No items found in shop_items with category 'gold'.</p>";
                } else {
                    $message .= "<p class='debug'>Available gold items in shop:</p>";
                    $message .= "<div class='item-list'><table>";
                    $message .= "<tr><th>Item ID</th><th>Name</th><th>Category</th><th>Point Cost</th><th>Token Cost</th><th>Gold Amount</th></tr>";
                    foreach ($available_items as $item) {
                        $message .= "<tr>";
                        $message .= "<td>" . htmlspecialchars($item['item_id']) . "</td>";
                        $message .= "<td>" . htmlspecialchars($item['name']) . "</td>";
                        $message .= "<td>" . htmlspecialchars($item['category']) . "</td>";
                        $message .= "<td>" . htmlspecialchars($item['point_cost']) . "</td>";
                        $message .= "<td>" . htmlspecialchars($item['token_cost']) . "</td>";
                        $message .= "<td>" . htmlspecialchars($item['gold_amount']) . "</td>";
                        $message .= "</tr>";
                    }
                    $message .= "</table></div>";
                }
            }
        }

        $site_db->close();
        $char_db->close();
        ?>

        <?php echo $debug_message; ?>
        <?php echo $message; ?>
        <p>Points: <?php echo $points; ?> | Tokens: <?php echo $tokens; ?></p>
        <form method="POST">
            <div class="form-group">
                <label for="character_guid">Select Character:</label>
                <select id="character_guid" name="character_guid" required>
                    <option value="">Select a character</option>
                    <?php foreach ($characters as $char): ?>
                        <option value="<?php echo $char['guid']; ?>">
                            <?php echo htmlspecialchars($char['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="buy_gold">Buy 1000 Gold</button>
        </form>
    </div>

    <script>
        // Basic client-side validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const character = document.getElementById('character_guid').value;
            if (!character) {
                e.preventDefault();
                alert('Please select a character.');
            }
        });
    </script>
</body>
</html>