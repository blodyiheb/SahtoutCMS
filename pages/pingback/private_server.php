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
    echo json_encode(["status" => "error", "message" => "Database connection failed: " . $site_db->connect_error]);
    exit;
}

// Get and validate parameters
$secret = $_POST['secret'] ?? ($_GET['secret'] ?? null);
$userid = isset($_POST['userid']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['userid']) : (isset($_GET['userid']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['userid']) : null);
$userip = isset($_POST['userip']) ? filter_var($_POST['userip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) : (isset($_GET['userip']) ? filter_var($_GET['userip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) : null);
$voted  = isset($_POST['voted']) ? (int)$_POST['voted'] : (isset($_GET['voted']) ? (int)$_GET['voted'] : 0);

if (!$userid || !$userip || $voted !== 1) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Missing or invalid userid, userip, or voted."]);
    exit;
}

if (strlen($userid) > 32 || strlen($userid) === 0) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Invalid userid length."]);
    exit;
}

// Find Private-Server.ws site entry
$callback_file_name = 'private_server';
$stmt = $site_db->prepare("SELECT id, callback_secret, reward_points, cooldown_hours FROM vote_sites WHERE callback_file_name = ? AND uses_callback = 1");
if (!$stmt) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Failed to prepare vote_sites query: " . $site_db->error]);
    exit;
}
$stmt->bind_param("s", $callback_file_name);
if (!$stmt->execute()) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Failed to execute vote_sites query: " . $site_db->error]);
    $stmt->close();
    exit;
}
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Private-Server.ws site not found."]);
    $stmt->close();
    exit;
}
$site = $result->fetch_assoc();
$internal_site_id = (int)$site['id'];
$callback_secret  = $site['callback_secret'];
$reward_points    = (int)$site['reward_points'];
$cooldown_hours   = (int)$site['cooldown_hours'];
$stmt->close();

// Validate secret only if one is configured in the database
if (!empty($callback_secret)) {
    if ($secret !== $callback_secret) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(["status" => "error", "message" => "Invalid secret key."]);
        exit;
    }
}


// Validate user
$stmt = $site_db->prepare("SELECT account_id FROM user_currencies WHERE username = ? OR account_id = ?");
if (!$stmt) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Failed to prepare user_currencies query: " . $site_db->error]);
    exit;
}
$stmt->bind_param("ss", $userid, $userid);
if (!$stmt->execute()) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Failed to execute user_currencies query: " . $site_db->error]);
    $stmt->close();
    exit;
}
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "User not found."]);
    $stmt->close();
    exit;
}
$row = $result->fetch_assoc();
$user_id = (int)$row['account_id'];
$stmt->close();

// Check cooldown
$can_vote = true;
$stmt = $site_db->prepare("
    SELECT vote_timestamp
    FROM vote_log
    WHERE user_id = ? AND site_id = ?
    ORDER BY vote_timestamp DESC LIMIT 1
");
if (!$stmt) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Failed to prepare vote_log query: " . $site_db->error]);
    exit;
}
$stmt->bind_param("ii", $user_id, $internal_site_id);
if ($stmt->execute()) {
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
} else {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Failed to execute vote_log query: " . $site_db->error]);
    $stmt->close();
    exit;
}

if (!$can_vote) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "User is on cooldown for this site."]);
    exit;
}

// Log the vote and grant reward
$reward_status = 0; // Success (reward granted)
$now = time();
$site_db->begin_transaction();
try {
    // Log the vote
    $stmt = $site_db->prepare("
        INSERT INTO vote_log (site_id, user_id, ip_address, vote_timestamp, reward_status)
        VALUES (?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        throw new Exception("Failed to prepare vote_log insert: " . $site_db->error);
    }
    $stmt->bind_param("iisii", $internal_site_id, $user_id, $userip, $now, $reward_status);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute vote_log insert: " . $stmt->error);
    }
    $stmt->close();

    // Grant reward
    $stmt = $site_db->prepare("
        UPDATE user_currencies SET points = points + ? WHERE account_id = ?
    ");
    if (!$stmt) {
        throw new Exception("Failed to prepare points update: " . $site_db->error);
    }
    $stmt->bind_param("ii", $reward_points, $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute points update: " . $stmt->error);
    }
    $stmt->close();

    $site_db->commit();

    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode([
        "status" => "success",
        "message" => "Vote logged and reward granted for $userid (ID: $user_id).",
        "points_added" => $reward_points
    ]);
} catch (Exception $e) {
    $site_db->rollback();
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Error logging vote or granting reward: " . $e->getMessage()]);
}

// Close database connections
if ($site_db) $site_db->close();
if ($auth_db) $auth_db->close();
if ($world_db) $world_db->close();
if ($char_db) $char_db->close();
exit;

?>