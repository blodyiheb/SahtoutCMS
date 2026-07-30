<?php
// Disable error reporting for production
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);
require_once __DIR__ . '/../../includes/paths.php';
define('ALLOWED_ACCESS', true);

// Verify includes
try {
    require_once $project_root . '/includes/config.php';
    require_once $project_root . '/includes/session.php';
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Include failed: " . $e->getMessage()]);
    exit;
}

// Check database connection
if (!$site_db || !$site_db instanceof mysqli || $site_db->connect_error) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Database connection failed."]);
    exit;
}

// Handle claim_rewards action by redirecting to claim.php
if (isset($_POST['action']) && $_POST['action'] === 'claim_rewards') {
    header('Location: claim.php');
    exit;
}

// Process vote entry
function processEntry($voterIP, $success, $reason, $pingUsername, $pingbackkey, $siteid, $site_db) {
    // Validate required fields
    if (!$voterIP || !$pingUsername || !$siteid) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(["status" => "error", "message" => "Missing required fields: VoterIP, pingUsername, or siteid."]);
        exit;
    }

    // Validate pingUsername (alphanumeric, A-Z, a-z, 0-9, underscore, hyphen)
    if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $pingUsername)) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(["status" => "error", "message" => "Invalid pingUsername: Must be alphanumeric (A-Z, a-z, 0-9, _, -)."]);
        exit;
    }

    // Validate pingbackkey and siteid
    $stmt = $site_db->prepare("SELECT id, reward_points, cooldown_hours FROM vote_sites WHERE siteid = ? AND callback_secret = ? AND uses_callback = 1");
    if (!$stmt) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(["status" => "error", "message" => "Failed to prepare SQL query for vote_sites."]);
        exit;
    }
    $stmt->bind_param("ss", $siteid, $pingbackkey);
    if (!$stmt->execute()) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(["status" => "error", "message" => "Failed to execute vote_sites query."]);
        exit;
    }
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(["status" => "error", "message" => "Invalid pingback key or external site ID: $siteid."]);
        exit;
    }
    $site = $result->fetch_assoc();
    $internal_site_id = (int)$site['id'];
    $reward_points = (int)$site['reward_points'];
    $cooldown_hours = (int)$site['cooldown_hours'];
    $stmt->close();

    // Find user by pingUsername
    $stmt = $site_db->prepare("SELECT account_id FROM user_currencies WHERE username = ? OR account_id = ?");
    if (!$stmt) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(["status" => "error", "message" => "Failed to prepare SQL query for user_currencies."]);
        exit;
    }
    $stmt->bind_param("ss", $pingUsername, $pingUsername);
    if (!$stmt->execute()) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(["status" => "error", "message" => "Failed to execute user_currencies query."]);
        exit;
    }
    $result = $stmt->get_result();
    $user_id = 0;
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = (int)$user['account_id'];
    }
    $stmt->close();

    if ($user_id === 0) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(["status" => "error", "message" => "User not found for pingUsername: $pingUsername."]);
        exit;
    }

    // Check cooldown for successful votes
    $can_vote = true;
    if ($success == 0) {
        $stmt = $site_db->prepare("
            SELECT vote_timestamp 
            FROM vote_log 
            WHERE user_id = ? AND site_id = ?
            ORDER BY vote_timestamp DESC LIMIT 1
        ");
        if (!$stmt) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(["status" => "error", "message" => "Failed to prepare SQL query for vote_log."]);
            exit;
        }
        $stmt->bind_param("ii", $user_id, $internal_site_id);
        if (!$stmt->execute()) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(["status" => "error", "message" => "Failed to execute vote_log query."]);
            exit;
        }
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $last_vote = $result->fetch_assoc();
            $last_vote_time = (int)$last_vote['vote_timestamp'];
            $cooldown_seconds = $cooldown_hours * 3600;
            if (time() - $last_vote_time < $cooldown_seconds) {
                $can_vote = false;
            }
        }
        $stmt->close();
    }

    if (!$can_vote) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(["status" => "error", "message" => "User is on cooldown for this site."]);
        exit;
    }

    // Log the vote
    $reward_status = ($success == 0 && $user_id > 0) ? 0 : 1;
    $site_db->begin_transaction();
    try {
        $stmt = $site_db->prepare("
            INSERT INTO vote_log (site_id, user_id, ip_address, vote_timestamp, reward_status) 
            VALUES (?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            throw new Exception("Failed to prepare SQL query for vote_log insert.");
        }
        $now = time();
        $stmt->bind_param("iisii", $internal_site_id, $user_id, $voterIP, $now, $reward_status);
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute vote_log insert.");
        }
        $stmt->close();
        $site_db->commit();
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode([
            "status" => "success",
            "message" => "Vote processed successfully for $pingUsername (ID: $user_id).",
            "points_pending" => $reward_points
        ]);
    } catch (Exception $e) {
        $site_db->rollback();
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(["status" => "error", "message" => "Error logging vote: " . $e->getMessage()]);
    }
}

// Handle incoming request
try {
    // Handle JSON or POST data for voting
    $contentType = $_SERVER["CONTENT_TYPE"] ?? "";
    if (strpos($contentType, "application/json") !== false) {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || !isset($data["Common"])) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(["status" => "error", "message" => "Invalid JSON data."]);
            exit;
        }

        $siteid = $data["siteid"] ?? null;
        $pingbackkey = $data["pingbackkey"] ?? null;

        foreach ($data["Common"] as $entry) {
            $mappedData = [];
            foreach ($entry as $subEntry) {
                $mappedData = array_merge($mappedData, $subEntry);
            }

            if (!isset($mappedData["ip"], $mappedData["success"], $mappedData["reason"], $mappedData["pb_name"])) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(["status" => "error", "message" => "Invalid JSON entry: Missing required fields (ip, success, reason, pb_name)."]);
                exit;
            }

            $voterIP = $mappedData["ip"];
            $success = abs((int)$mappedData["success"]);
            $reason = $mappedData["reason"];
            $pingUsername = $mappedData["pb_name"];

            processEntry($voterIP, $success, $reason, $pingUsername, $pingbackkey, $siteid, $site_db);
        }

        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode(["status" => "success", "message" => "JSON data processed successfully."]);
    } else {
        $voterIP = $_POST["VoterIP"] ?? null;
        $success = abs((int)($_POST["Successful"] ?? 1));
        $reason = $_POST["Reason"] ?? "No reason provided";
        $pingUsername = $_POST["pingUsername"] ?? null;
        $pingbackkey = $_POST["pingbackkey"] ?? null;
        $siteid = $_POST["siteid"] ?? null;

        processEntry($voterIP, $success, $reason, $pingUsername, $pingbackkey, $siteid, $site_db);

        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode(["status" => "success", "message" => "POST data processed successfully."]);
    }
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}

// Close database connections
if ($site_db) $site_db->close();
if ($auth_db) $auth_db->close();
if ($world_db) $world_db->close();
if ($char_db) $char_db->close();
exit;

?>