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

// Optional: Validate Top100Arena IP (uncomment if required)
/*
define('TOP100ARENA_IP', '3.86.48.116');
if ($_SERVER['REMOTE_ADDR'] !== TOP100ARENA_IP) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Invalid request: Not from Top100Arena IP."]);
    exit;
}
*/

// Get and sanitize parameter
$parameter = null;
if (isset($_GET['postback'])) {
    $parameter = preg_replace('/[^a-zA-Z0-9_\-]+/', '', $_GET['postback']);
} elseif (isset($_GET['incentive'])) {
    $parameter = preg_replace('/[^a-zA-Z0-9_\-]+/', '', $_GET['incentive']);
} elseif (isset($_GET['userid'])) {
    $parameter = preg_replace('/[^a-zA-Z0-9_\-]+/', '', $_GET['userid']);
} else {
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    $path = parse_url($request_uri, PHP_URL_PATH);
    $path_parts = explode('/', $path);
    $last_part = end($path_parts);
    if ($last_part !== 'top100arena.php' && !empty($last_part)) {
        $parameter = preg_replace('/[^a-zA-Z0-9_\-]+/', '', $last_part);
    }
}

$voter_ip = preg_replace('/[^0-9\.]+/', '', $_SERVER['REMOTE_ADDR'] ?? 'unknown');

if (!$parameter || strlen($parameter) > 25 || strlen($parameter) === 0) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Invalid or missing user parameter."]);
    exit;
}

// Find Top100Arena site entry
$callback_file_name = 'top100arena';
$stmt = $site_db->prepare("SELECT id, reward_points, cooldown_hours FROM vote_sites WHERE callback_file_name = ? AND uses_callback = 1");
if (!$stmt) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Failed to prepare SQL query for vote_sites."]);
    exit;
}
$stmt->bind_param("s", $callback_file_name);
if (!$stmt->execute()) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Failed to execute vote_sites query."]);
    exit;
}
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Top100Arena site not found in vote_sites."]);
    exit;
}
$site = $result->fetch_assoc();
$internal_site_id = (int)$site['id'];
$reward_points = (int)$site['reward_points'];
$cooldown_hours = (int)$site['cooldown_hours'];
$stmt->close();

// Validate user
$stmt = $site_db->prepare("SELECT account_id FROM user_currencies WHERE username = ? OR account_id = ?");
if (!$stmt) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Failed to prepare SQL query for user_currencies."]);
    exit;
}
$stmt->bind_param("ss", $parameter, $parameter);
if (!$stmt->execute()) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Failed to execute user_currencies query."]);
    exit;
}
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "User/Parameter not found in user_currencies."]);
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
    WHERE user_id = ? AND site_id = ? AND reward_status = 0
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

if (!$can_vote) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "User is on cooldown for this site."]);
    exit;
}

// Log the vote
$reward_status = 0; // Unclaimed
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
    $stmt->bind_param("iisii", $internal_site_id, $user_id, $voter_ip, $now, $reward_status);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute vote_log insert.");
    }
    $stmt->close();
    $site_db->commit();
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode([
        "status" => "success",
        "message" => "Vote logged successfully for $parameter (ID: $user_id).",
        "points_pending" => $reward_points
    ]);
} catch (Exception $e) {
    $site_db->rollback();
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Error logging vote: " . $e->getMessage()]);
}

// Close database connections
if ($site_db) $site_db->close();
if ($auth_db) $auth_db->close();
if ($world_db) $world_db->close();
if ($char_db) $char_db->close();
exit;

?>