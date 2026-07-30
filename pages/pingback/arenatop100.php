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
    $error = $site_db ? $site_db->connect_error : 'No database connection object';
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Database connection failed: " . $error]);
    exit;
}

// Get and validate parameters
$secret = isset($_POST['secret']) ? $_POST['secret'] : (isset($_GET['secret']) ? $_GET['secret'] : null);
$voted = isset($_POST['voted']) ? (int)$_POST['voted'] : (isset($_GET['voted']) ? (int)$_GET['voted'] : 0);
$userip = isset($_POST['userip']) ? preg_replace('/[^0-9\.]+/', '', $_POST['userip']) : (isset($_GET['userip']) ? preg_replace('/[^0-9\.]+/', '', $_GET['userip']) : ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
$parameter = null;
if (isset($_POST['userid']) || isset($_GET['userid'])) {
    $parameter = preg_replace('/[^a-zA-Z0-9_\-]+/', '', ($_POST['userid'] ?? $_GET['userid']));
} elseif (isset($_POST['postback']) || isset($_GET['postback'])) {
    $parameter = preg_replace('/[^a-zA-Z0-9_\-]+/', '', ($_POST['postback'] ?? $_GET['postback']));
} elseif (isset($_POST['incentive']) || isset($_GET['incentive'])) {
    $parameter = preg_replace('/[^a-zA-Z0-9_\-]+/', '', ($_POST['incentive'] ?? $_GET['incentive']));
}

if (!$secret || !$parameter || !$userip || $voted !== 1) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Missing or invalid parameters."]);
    exit;
}

if (strlen($parameter) > 25 || strlen($parameter) === 0) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Invalid user parameter length."]);
    exit;
}

// Find ArenaTop100 site entry
$callback_file_name = 'Arenatop100';
$stmt = $site_db->prepare("SELECT id, callback_secret, reward_points, cooldown_hours FROM vote_sites WHERE callback_file_name = ? AND uses_callback = 1");
if (!$stmt) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Failed to prepare SQL query for vote_sites: " . $site_db->error]);
    exit;
}
$stmt->bind_param("s", $callback_file_name);
if (!$stmt->execute()) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Failed to execute vote_sites query: " . $stmt->error]);
    exit;
}
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "ArenaTop100 site not found in vote_sites."]);
    exit;
}
$site = $result->fetch_assoc();
$internal_site_id = (int)$site['id'];
$callback_secret = $site['callback_secret'];
$reward_points = (int)$site['reward_points'];
$cooldown_hours = (int)$site['cooldown_hours'];
$stmt->close();

// Validate secret
if ($callback_secret && $secret !== $callback_secret) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Invalid secret key."]);
    exit;
}

// Validate user
$stmt = $site_db->prepare("SELECT account_id FROM user_currencies WHERE username = ? OR account_id = ?");
if (!$stmt) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Failed to prepare SQL query for user_currencies: " . $site_db->error]);
    exit;
}
$stmt->bind_param("ss", $parameter, $parameter);
if (!$stmt->execute()) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Failed to execute user_currencies query: " . $stmt->error]);
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
    WHERE user_id = ? AND site_id = ?
    ORDER BY vote_timestamp DESC LIMIT 1
");
if (!$stmt) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Failed to prepare SQL query for vote_log: " . $site_db->error]);
    exit;
}
$stmt->bind_param("ii", $user_id, $internal_site_id);
if (!$stmt->execute()) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Failed to execute vote_log query: " . $stmt->error]);
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

// Log the vote (reward pending, only in vote_log)
$reward_status = 0; // Pending, user can claim
$now = time();
$site_db->begin_transaction();
try {
    // Insert into vote_log
    $stmt = $site_db->prepare("
        INSERT INTO vote_log (site_id, user_id, ip_address, vote_timestamp, reward_status)
        VALUES (?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        throw new Exception("Failed to prepare SQL query for vote_log insert: " . $site_db->error);
    }
    $stmt->bind_param("iisii", $internal_site_id, $user_id, $userip, $now, $reward_status);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute vote_log insert: " . $stmt->error);
    }
    $stmt->close();

    $site_db->commit();
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(["status" => "success", "message" => "Vote logged successfully for $parameter (ID: $user_id). Reward pending.", "points_pending" => $reward_points]);
} catch (Exception $e) {
    $site_db->rollback();
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Error processing vote: " . $e->getMessage()]);
}


if ($site_db) $site_db->close();
if ($auth_db) $auth_db->close();
if ($world_db) $world_db->close();
if ($char_db) $char_db->close();
exit;
?>