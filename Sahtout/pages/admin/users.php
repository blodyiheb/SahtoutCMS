<?php
define('ALLOWED_ACCESS', true);

// Include session, language, and paths
require_once __DIR__ . '/../../includes/paths.php';
require_once $project_root . 'includes/session.php'; // Includes config.php
require_once $project_root . 'languages/language.php'; // Include translation system
require_once $project_root . 'includes/config.settings.php';
$page_class = 'users';
define('DB_AUTH', $db_auth_name);
define('DB_CHAR', $db_char_name);
define('DB_WORLD', $db_world_name);
define('DB_SITE', $db_site_name);
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: {$base_path}login");
    exit;
}

// Use databases from config.php
global $site_db, $auth_db, $char_db;

// Check user role from user_currencies
$user_id = $_SESSION['user_id'];
$role_query = "SELECT role FROM " . DB_SITE . ".user_currencies WHERE account_id = ?";
$stmt = $site_db->prepare($role_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $_SESSION['role'] = $row['role'];
} else {
    $_SESSION['role'] = 'player';
}
$stmt->close();

// Restrict access to admin or moderator only
if (!in_array($_SESSION['role'], ['admin', 'moderator'])) {
    header("Location: {$base_path}login");
    exit;
}

// Handle search, sort, role filter, ban filter, and gmlevel filter
$search_username = '';
$search_email = '';
$role_filter = '';
$ban_filter = '';
$gmlevel_filter = '';
$sort_id = 'asc';
if (isset($_GET['search_username']) && !empty(trim($_GET['search_username']))) {
    $search_username = trim($_GET['search_username']);
}
if (isset($_GET['search_email']) && !empty(trim($_GET['search_email']))) {
    $search_email = trim($_GET['search_email']);
}
if (isset($_GET['role_filter']) && in_array($_GET['role_filter'], ['player', 'moderator', 'admin', ''])) {
    $role_filter = $_GET['role_filter'];
}
if (isset($_GET['ban_filter']) && in_array($_GET['ban_filter'], ['banned', ''])) {
    $ban_filter = $_GET['ban_filter'];
}
if (isset($_GET['gmlevel_filter']) && in_array($_GET['gmlevel_filter'], ['player', '1', '2', '3', ''])) {
    $gmlevel_filter = $_GET['gmlevel_filter'];
}
if (isset($_GET['sort_id']) && in_array($_GET['sort_id'], ['asc', 'desc'])) {
    $sort_id = $_GET['sort_id'];
}
$users_per_page = 10; // Limit to 10 users per page
$website_page = isset($_GET['website_page']) ? max(1, (int)$_GET['website_page']) : 1;
$ingame_page = isset($_GET['ingame_page']) ? max(1, (int)$_GET['ingame_page']) : 1;
$website_offset = ($website_page - 1) * $users_per_page;
$ingame_offset = ($ingame_page - 1) * $users_per_page;

// Handle form submissions
$update_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update') {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $update_message = '<div class="alert alert-danger">' . translate('admin_users_csrf_error', 'CSRF token validation failed.') . '</div>';
        } else {
            $account_id = (int)$_POST['account_id'];
            $points = (int)$_POST['points'];
            $tokens = (int)$_POST['tokens'];
            $role = in_array($_POST['role'], ['player', 'moderator', 'admin']) ? $_POST['role'] : 'player';
            $email = trim($_POST['email']);
            // Update user_currencies (points, tokens, role)
            $stmt = $site_db->prepare("UPDATE " . DB_SITE . ".user_currencies SET points = ?, tokens = ?, role = ? WHERE account_id = ?");
            $stmt->bind_param("iiss", $points, $tokens, $role, $account_id);
            $success = $stmt->execute();
            $stmt->close();
            // Update email in account table
            $stmt = $auth_db->prepare("UPDATE " . DB_AUTH . ".account SET email = ? WHERE id = ?");
            $stmt->bind_param("si", $email, $account_id);
            $success = $success && $stmt->execute();
            $stmt->close();
            if ($success) {
                $update_message = '<div class="alert alert-success">' . translate('admin_users_update_success', 'User updated successfully.') . '</div>';
            } else {
                $update_message = '<div class="alert alert-danger">' . translate('admin_users_update_failed', 'Failed to update user.') . '</div>';
            }
        }
    } elseif ($_POST['action'] === 'manage_account') {
        // Handle ban/unban or GM role change
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $update_message = '<div class="alert alert-danger">' . translate('admin_users_csrf_error', 'CSRF token validation failed.') . '</div>';
        } else {
            $account_id = (int)$_POST['account_id'];
            $ban_action = $_POST['ban_action'] ?? '';
            $gmlevel = isset($_POST['gmlevel']) && in_array($_POST['gmlevel'], ['player', '1', '2', '3']) ? $_POST['gmlevel'] : '';
            $success = true;
            if ($ban_action === 'ban') {
                $ban_reason = trim($_POST['ban_reason']);
                $ban_duration = $_POST['ban_duration'];
                $ban_time = time();
                $unban_time = ($ban_duration === 'permanent') ? 0 : $ban_time + (int)$ban_duration;
                $stmt = $auth_db->prepare("INSERT INTO " . DB_AUTH . ".account_banned (id, bandate, unbandate, bannedby, banreason, active) VALUES (?, ?, ?, ?, ?, 1)");
                $banned_by = $_SESSION['username'];
                $stmt->bind_param("iiiss", $account_id, $ban_time, $unban_time, $banned_by, $ban_reason);
                $success = $stmt->execute();
                $stmt->close();
            } elseif ($ban_action === 'unban') {
                $stmt = $auth_db->prepare("UPDATE " . DB_AUTH . ".account_banned SET active = 0 WHERE id = ? AND active = 1");
                $stmt->bind_param("i", $account_id);
                $success = $stmt->execute();
                $stmt->close();
            } elseif ($ban_action === 'change_gm_role' && $gmlevel !== '') {
                // Handle GM role change
                if ($gmlevel === 'player') {
                    $stmt = $auth_db->prepare("DELETE FROM " . DB_AUTH . ".account_access WHERE id = ?");
                    $stmt->bind_param("i", $account_id);
                    $success = $stmt->execute();
                    $stmt->close();
                } else {
                    $gmlevel_value = (int)$gmlevel;
                    $stmt = $auth_db->prepare("SELECT COUNT(*) as count FROM " . DB_AUTH . ".account_access WHERE id = ?");
                    $stmt->bind_param("i", $account_id);
                    $stmt->execute();
                    $result = $stmt->get_result()->fetch_assoc();
                    $exists = $result['count'] > 0;
                    $stmt->close();
                    if ($exists) {
                        $stmt = $auth_db->prepare("UPDATE " . DB_AUTH . ".account_access SET gmlevel = ? WHERE id = ?");
                        $stmt->bind_param("ii", $gmlevel_value, $account_id);
                    } else {
                        $stmt = $auth_db->prepare("INSERT INTO " . DB_AUTH . ".account_access (id, gmlevel) VALUES (?, ?)");
                        $stmt->bind_param("ii", $account_id, $gmlevel_value);
                    }
                    $success = $stmt->execute();
                    $stmt->close();
                }
            }
            if ($success && empty($update_message)) {
                $update_message = '<div class="alert alert-success">' . translate('admin_users_action_success', 'Action completed successfully.') . '</div>';
            } elseif (empty($update_message)) {
                $update_message = '<div class="alert alert-danger">' . translate('admin_users_action_failed', 'Failed to complete action.') . '</div>';
            }
        }
    }
}

// Count total website users for pagination
$count_query = "SELECT COUNT(*) as total FROM " . DB_SITE . ".user_currencies uc JOIN " . DB_AUTH . ".account a ON uc.account_id = a.id WHERE 1=1";
$params = [];
$types = '';
if ($search_username) {
    $count_query .= " AND uc.username LIKE ?";
    $params[] = "%$search_username%";
    $types .= 's';
}
if ($search_email) {
    $count_query .= " AND a.email LIKE ?";
    $params[] = "%$search_email%";
    $types .= 's';
}
if ($role_filter) {
    $count_query .= " AND uc.role = ?";
    $params[] = $role_filter;
    $types .= 's';
}
$stmt = $site_db->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_website_users = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();
$total_website_pages = ceil($total_website_users / $users_per_page);

// Fetch website users with email from account table
$users_query = "SELECT uc.account_id, uc.username, uc.avatar, uc.points, uc.tokens, uc.role, uc.last_updated, a.email
                FROM " . DB_SITE . ".user_currencies uc JOIN " . DB_AUTH . ".account a ON uc.account_id = a.id WHERE 1=1";
$params = [];
$types = '';
if ($search_username) {
    $users_query .= " AND uc.username LIKE ?";
    $params[] = "%$search_username%";
    $types .= 's';
}
if ($search_email) {
    $users_query .= " AND a.email LIKE ?";
    $params[] = "%$search_email%";
    $types .= 's';
}
if ($role_filter) {
    $users_query .= " AND uc.role = ?";
    $params[] = $role_filter;
    $types .= 's';
}
$users_query .= " ORDER BY uc.account_id " . ($sort_id === 'desc' ? 'DESC' : 'ASC') . " LIMIT ? OFFSET ?";
$params[] = $users_per_page;
$params[] = $website_offset;
$types .= 'ii';
$stmt = $site_db->prepare($users_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users_result = $stmt->get_result();
$stmt->close();

// Count total in-game accounts for pagination
$count_query = "SELECT COUNT(*) as total FROM " . DB_AUTH . ".account a LEFT JOIN " . DB_AUTH . ".account_access aa ON a.id = aa.id WHERE 1=1";
$params = [];
$types = '';
if ($search_username) {
    $count_query .= " AND a.username LIKE ?";
    $params[] = "%$search_username%";
    $types .= 's';
}
if ($search_email) {
    $count_query .= " AND a.email LIKE ?";
    $params[] = "%$search_email%";
    $types .= 's';
}
if ($ban_filter === 'banned') {
    $count_query .= " AND EXISTS (SELECT 1 FROM " . DB_AUTH . ".account_banned ab WHERE ab.id = a.id AND ab.active = 1)";
}
if ($gmlevel_filter !== '') {
    if ($gmlevel_filter === 'player') {
        $count_query .= " AND aa.gmlevel IS NULL";
    } else {
        $count_query .= " AND aa.gmlevel = ?";
        $params[] = (int)$gmlevel_filter;
        $types .= 'i';
    }
}
$stmt = $auth_db->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_ingame_accounts = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();
$total_ingame_pages = ceil($total_ingame_accounts / $users_per_page);

// Fetch in-game accounts and their status
$accounts_query = "SELECT a.id, a.username, a.email, a.joindate, a.last_login, a.online, aa.gmlevel
                  FROM " . DB_AUTH . ".account a
                  LEFT JOIN " . DB_AUTH . ".account_access aa ON a.id = aa.id
                  WHERE 1=1";
$params = [];
$types = '';
if ($search_username) {
    $accounts_query .= " AND a.username LIKE ?";
    $params[] = "%$search_username%";
    $types .= 's';
}
if ($search_email) {
    $accounts_query .= " AND a.email LIKE ?";
    $params[] = "%$search_email%";
    $types .= 's';
}
if ($ban_filter === 'banned') {
    $accounts_query .= " AND EXISTS (SELECT 1 FROM " . DB_AUTH . ".account_banned ab WHERE ab.id = a.id AND ab.active = 1)";
}
if ($gmlevel_filter !== '') {
    if ($gmlevel_filter === 'player') {
        $accounts_query .= " AND aa.gmlevel IS NULL";
    } else {
        $accounts_query .= " AND aa.gmlevel = ?";
        $params[] = (int)$gmlevel_filter;
        $types .= 'i';
    }
}
$accounts_query .= " ORDER BY a.id " . ($sort_id === 'desc' ? 'DESC' : 'ASC') . " LIMIT ? OFFSET ?";
$params[] = $users_per_page;
$params[] = $ingame_offset;
$types .= 'ii';
$stmt = $auth_db->prepare($accounts_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$accounts_result = $stmt->get_result();
$accounts = [];
while ($account = $accounts_result->fetch_assoc()) {
    $accounts[$account['id']] = $account;
}
$stmt->close();

// Fetch ban information for accounts
$account_ids = array_keys($accounts);
if (!empty($account_ids)) {
    $placeholders = implode(',', array_fill(0, count($account_ids), '?'));
    $stmt = $auth_db->prepare("SELECT id, bandate, unbandate, banreason
                               FROM " . DB_AUTH . ".account_banned
                               WHERE id IN ($placeholders) AND active = 1");
    $stmt->bind_param(str_repeat('i', count($account_ids)), ...$account_ids);
    $stmt->execute();
    $ban_result = $stmt->get_result();
    while ($ban = $ban_result->fetch_assoc()) {
        $accounts[$ban['id']]['banInfo'] = $ban;
    }
    $stmt->close();
    // Fetch characters for the accounts
    $stmt = $char_db->prepare("SELECT guid, account, name, race, class, gender, level
                               FROM " . DB_CHAR . ".characters
                               WHERE account IN ($placeholders)
                               ORDER BY account, name");
    $stmt->bind_param(str_repeat('i', count($account_ids)), ...$account_ids);
    $stmt->execute();
    $characters_result = $stmt->get_result();
    while ($char = $characters_result->fetch_assoc()) {
        $accounts[$char['account']]['characters'][] = $char;
    }
    $stmt->close();
}

// Helper functions for character and status display
function getRaceIcon($race, $gender) {
    global $base_path;
    $races = [
        1 => 'human', 2 => 'orc', 3 => 'dwarf', 4 => 'nightelf',
        5 => 'undead', 6 => 'tauren', 7 => 'gnome', 8 => 'troll',
        10 => 'bloodelf', 11 => 'draenei'
    ];
    $gender_folder = ($gender == 1) ? 'female' : 'male';
    $race_name = $races[$race] ?? 'default';
    $image = $race_name . '.png';
    return '<img src="' . $base_path . 'img/accountimg/race/' . $gender_folder . '/' . $image . '" alt="' . translate('admin_users_race_icon_alt', 'Race Icon') . '" class="account-sahtout-icon">';
}
function getClassIcon($class) {
    global $base_path;
    $icons = [
        1 => 'warrior.webp', 2 => 'paladin.webp', 3 => 'hunter.webp', 4 => 'rogue.webp',
        5 => 'priest.webp', 6 => 'deathknight.webp', 7 => 'shaman.webp', 8 => 'mage.webp',
        9 => 'warlock.webp', 11 => 'druid.webp'
    ];
    return '<img src="' . $base_path . 'img/accountimg/class/' . ($icons[$class] ?? 'default.jpg') . '" alt="' . translate('admin_users_class_icon_alt', 'Class Icon') . '" class="account-sahtout-icon">';
}
function getFactionIcon($race) {
    global $base_path;
    $allianceRaces = [1, 3, 4, 7, 11]; // Human, Dwarf, Night Elf, Gnome, Draenei
    $faction = in_array($race, $allianceRaces) ? 'alliance.png' : 'horde.png';
    return '<img src="' . $base_path . 'img/accountimg/faction/' . $faction . '" alt="' . translate('admin_users_faction_icon_alt', 'Faction Icon') . '" class="account-sahtout-icon">';
}
function getOnlineStatus($online) {
    return $online ? "<span style='color: #55ff55'>" . translate('admin_users_status_online', 'Online') . "</span>" : "<span style='color: #ff5555'>" . translate('admin_users_status_offline', 'Offline') . "</span>";
}
function getAccountStatus($banInfo) {
    if (!empty($banInfo)) {
        $reason = htmlspecialchars($banInfo['banreason'] ?? translate('admin_users_no_reason_provided', 'No reason provided'));
        $unbanDate = $banInfo['unbandate'] ? date('Y-m-d H:i:s', $banInfo['unbandate']) : translate('admin_users_permanent', 'Permanent');
        return "<span style='color: #ff5555'>" . translate('admin_users_status_banned', 'Banned') . " (" . translate('admin_users_reason', 'Reason') . ": $reason, " . translate('admin_users_until', 'Until') . ": $unbanDate)</span>";
    }
    return "<span style='color: #05f30594'>" . translate('admin_users_status_active', 'Active') . "</span>";
}
function getGMLevel($gmlevel) {
    if (is_null($gmlevel)) {
        return "<span style='color: #6c757d'>" . translate('admin_users_gmlevel_player', 'Player') . "</span>";
    }
    return "<span style='color: #17a2b8'>" . translate('admin_users_gmlevel_prefix', 'GM Level') . " $gmlevel</span>";
}

// Determine active tab: default to In-Game tab if ban_filter or gmlevel_filter is set
$active_tab = (isset($_GET['ingame_page']) && $_GET['ingame_page'] > 1) || $ban_filter || $gmlevel_filter ? 'ingame' : 'website';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo translate('admin_users_meta_description', 'User Management for Sahtout WoW Server'); ?>">
    <meta name="robots" content="noindex">
    <title><?php echo translate('admin_users_page_title', 'User Management'); ?></title>
    <link rel="icon" href="<?php echo $base_path . $site_logo; ?>" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/admin/users.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/admin/admin_sidebar.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="users">
    <div class="wrapper">
        <?php include $project_root . 'includes/header.php'; ?>
        <div class="dashboard-container">
            <div class="row">
                <!-- Sidebar -->
                <?php include $project_root . 'includes/admin_sidebar.php'; ?>
                <!-- Main Content -->
                <div class="col-md-9">
                    <h1 class="dashboard-title"><?php echo translate('admin_users_title', 'User Management'); ?></h1>
                    <?php echo $update_message; ?>
                    <!-- Search and Sort Form -->
                    <form class="search-form" method="GET" action="<?php echo $base_path; ?>admin/users">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <input type="text" name="search_username" class="form-control" placeholder="<?php echo translate('admin_users_search_username_placeholder', 'Search by username'); ?>" value="<?php echo htmlspecialchars($search_username); ?>">
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="search_email" class="form-control" placeholder="<?php echo translate('admin_users_search_email_placeholder', 'Search by email'); ?>" value="<?php echo htmlspecialchars($search_email); ?>">
                            </div>
                            <div class="col-md-4">
                                <select name="role_filter" class="form-select">
                                    <option value="" <?php echo $role_filter === '' ? 'selected' : ''; ?>><?php echo translate('admin_users_all_roles', 'All Roles'); ?></option>
                                    <option value="player" <?php echo $role_filter === 'player' ? 'selected' : ''; ?>><?php echo translate('admin_users_role_player', 'Player'); ?></option>
                                    <option value="moderator" <?php echo $role_filter === 'moderator' ? 'selected' : ''; ?>><?php echo translate('admin_users_role_moderator', 'Moderator'); ?></option>
                                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>><?php echo translate('admin_users_role_admin', 'Admin'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <select name="ban_filter" class="form-select">
                                    <option value="" <?php echo $ban_filter === '' ? 'selected' : ''; ?>><?php echo translate('admin_users_all_accounts', 'All Accounts'); ?></option>
                                    <option value="banned" <?php echo $ban_filter === 'banned' ? 'selected' : ''; ?>><?php echo translate('admin_users_banned', 'Banned'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select name="gmlevel_filter" class="form-select">
                                    <option value="" <?php echo $gmlevel_filter === '' ? 'selected' : ''; ?>><?php echo translate('admin_users_all_gm_levels', 'All GM Levels'); ?></option>
                                    <option value="player" <?php echo $gmlevel_filter === 'player' ? 'selected' : ''; ?>><?php echo translate('admin_users_gmlevel_player', 'Player'); ?></option>
                                    <option value="1" <?php echo $gmlevel_filter === '1' ? 'selected' : ''; ?>><?php echo translate('admin_users_gmlevel_1', 'GM Level 1'); ?></option>
                                    <option value="2" <?php echo $gmlevel_filter === '2' ? 'selected' : ''; ?>><?php echo translate('admin_users_gmlevel_2', 'GM Level 2'); ?></option>
                                    <option value="3" <?php echo $gmlevel_filter === '3' ? 'selected' : ''; ?>><?php echo translate('admin_users_gmlevel_3', 'GM Level 3'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select name="sort_id" class="form-select">
                                    <option value="asc" <?php echo $sort_id === 'asc' ? 'selected' : ''; ?>><?php echo translate('admin_users_sort_id_asc', 'Sort by ID: Ascending'); ?></option>
                                    <option value="desc" <?php echo $sort_id === 'desc' ? 'selected' : ''; ?>><?php echo translate('admin_users_sort_id_desc', 'Sort by ID: Descending'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <button class="btn btn-primary" type="submit"><?php echo translate('admin_users_apply_button', 'Apply'); ?></button>
                            </div>
                        </div>
                        <input type="hidden" name="website_page" value="<?php echo htmlspecialchars($website_page); ?>">
                        <input type="hidden" name="ingame_page" value="<?php echo htmlspecialchars($ingame_page); ?>">
                    </form>
                    <!-- Tabs -->
                    <ul class="nav nav-tabs">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $active_tab === 'website' ? 'active' : ''; ?>" href="#website-tab" data-bs-toggle="tab"><?php echo translate('admin_users_tab_website', 'Website'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $active_tab === 'ingame' ? 'active' : ''; ?>" href="#ingame-tab" data-bs-toggle="tab"><?php echo translate('admin_users_tab_ingame', 'In-Game'); ?></a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <!-- Website Tab -->
                        <div class="tab-pane fade <?php echo $active_tab === 'website' ? 'show active' : ''; ?>" id="website-tab">
                            <div class="card">
                                <div class="card-header"><?php echo translate('admin_users_website_users_header', 'Website Users'); ?></div>
                                <div class="card-body">
                                    <div class="table-wrapper">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th><?php echo translate('admin_users_table_account_id', 'Account ID'); ?></th>
                                                    <th><?php echo translate('admin_users_table_username', 'Username'); ?></th>
                                                    <th><?php echo translate('admin_users_table_email', 'Email'); ?></th>
                                                    <th><?php echo translate('admin_users_table_avatar', 'Avatar'); ?></th>
                                                    <th><?php echo translate('admin_users_table_points', 'Points'); ?></th>
                                                    <th><?php echo translate('admin_users_table_tokens', 'Tokens'); ?></th>
                                                    <th><?php echo translate('admin_users_table_role', 'Role'); ?></th>
                                                    <th><?php echo translate('admin_users_table_last_updated', 'Last Updated'); ?></th>
                                                    <th><?php echo translate('admin_users_table_action', 'Action'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if ($users_result->num_rows === 0): ?>
                                                    <tr>
                                                        <td colspan="9"><?php echo translate('admin_users_no_users_found', 'No users found.'); ?></td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php while ($user = $users_result->fetch_assoc()): ?>
                                                        <tr id="user-<?php echo $user['account_id']; ?>">
                                                            <td><?php echo htmlspecialchars($user['account_id']); ?></td>
                                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                            <td><?php echo htmlspecialchars($user['email'] ?? translate('admin_users_email_not_set', 'Not set')); ?></td>
                                                            <td><?php echo !empty($user['avatar']) ? '<img src="' . $base_path . 'img/accountimg/profile_pics/' . htmlspecialchars($user['avatar']) . '" class="rounded-circle" style="width: 40px; height: 40px;" alt="' . translate('admin_users_avatar_alt', 'Avatar') . '">' : '<img src="' . $base_path . 'img/accountimg/profile_pics/user.jpg" class="rounded-circle" style="width: 40px; height: 40px;" alt="' . translate('admin_users_default_avatar_alt', 'Default Avatar') . '">'; ?></td>
                                                            <td><?php echo htmlspecialchars($user['points']); ?></td>
                                                            <td><?php echo htmlspecialchars($user['tokens']); ?></td>
                                                            <td><span class="status-<?php echo htmlspecialchars($user['role']); ?>">
                                                                <?php echo ucfirst(translate('admin_users_role_' . $user['role'], ucfirst($user['role']))); ?>
                                                            </span></td>
                                                            <td><?php echo $user['last_updated'] ? date('M j, Y H:i', strtotime($user['last_updated'])) : translate('admin_users_never', 'Never'); ?></td>
                                                            <td>
                                                                <button class="btn btn-edit" data-bs-toggle="modal" data-bs-target="#editModal-<?php echo $user['account_id']; ?>"><?php echo translate('admin_users_edit_button', 'Edit'); ?></button>
                                                            </td>
                                                        </tr>
                                                        <!-- Edit Modal -->
                                                        <div class="modal fade" id="editModal-<?php echo $user['account_id']; ?>" tabindex="-1" aria-labelledby="editModalLabel-<?php echo $user['account_id']; ?>" aria-hidden="true">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title" id="editModalLabel-<?php echo $user['account_id']; ?>"><?php echo translate('admin_users_edit_modal_title', 'Edit User: ') . htmlspecialchars($user['username']); ?></h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo translate('admin_users_close_button', 'Close'); ?>"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <form method="POST" action="<?php echo $base_path; ?>admin/users">
                                                                            <input type="hidden" name="action" value="update">
                                                                            <input type="hidden" name="account_id" value="<?php echo $user['account_id']; ?>">
                                                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                                            <div class="mb-3">
                                                                                <label class="form-label"><?php echo translate('admin_users_label_username', 'Username'); ?></label>
                                                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                                                            </div>
                                                                            <div class="mb-3">
                                                                                <label class="form-label"><?php echo translate('admin_users_label_email', 'Email'); ?></label>
                                                                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                                                            </div>
                                                                            <div class="mb-3">
                                                                                <label class="form-label"><?php echo translate('admin_users_label_points', 'Points'); ?></label>
                                                                                <input type="number" name="points" class="form-control" value="<?php echo htmlspecialchars($user['points']); ?>" required>
                                                                            </div>
                                                                            <div class="mb-3">
                                                                                <label class="form-label"><?php echo translate('admin_users_label_tokens', 'Tokens'); ?></label>
                                                                                <input type="number" name="tokens" class="form-control" value="<?php echo htmlspecialchars($user['tokens']); ?>" required>
                                                                            </div>
                                                                            <div class="mb-3">
                                                                                <label class="form-label"><?php echo translate('admin_users_label_role', 'Role'); ?></label>
                                                                                <select name="role" class="form-select">
                                                                                    <option value="player" <?php echo $user['role'] === 'player' ? 'selected' : ''; ?>><?php echo translate('admin_users_role_player', 'Player'); ?></option>
                                                                                    <option value="moderator" <?php echo $user['role'] === 'moderator' ? 'selected' : ''; ?>><?php echo translate('admin_users_role_moderator', 'Moderator'); ?></option>
                                                                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>><?php echo translate('admin_users_role_admin', 'Admin'); ?></option>
                                                                                </select>
                                                                            </div>
                                                                            <div class="modal-footer">
                                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo translate('admin_users_cancel_button', 'Cancel'); ?></button>
                                                                                <button type="submit" class="btn btn-primary"><?php echo translate('admin_users_save_button', 'Save'); ?></button>
                                                                            </div>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endwhile; ?>
                                                <?php endif; ?>
                                                <?php $users_result->free(); ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <!-- Website Users Pagination -->
                                    <?php if ($total_website_pages > 1): ?>
                                        <div class="pagination-label"><?php echo translate('admin_users_website_pagination_label', 'Website Users Pagination'); ?></div>
                                        <nav aria-label="<?php echo translate('admin_users_website_pagination_aria', 'Website users pagination'); ?>">
                                            <ul class="pagination">
                                                <li class="page-item <?php echo $website_page <= 1 ? 'disabled' : ''; ?>">
                                                    <a class="page-link" href="<?php echo $base_path; ?>admin/users?<?php echo ($search_username ? 'search_username=' . urlencode($search_username) . '&' : '') . ($search_email ? 'search_email=' . urlencode($search_email) . '&' : '') . ($role_filter ? 'role_filter=' . urlencode($role_filter) . '&' : '') . ($ban_filter ? 'ban_filter=' . urlencode($ban_filter) . '&' : '') . ($gmlevel_filter ? 'gmlevel_filter=' . urlencode($gmlevel_filter) . '&' : '') . ($sort_id ? 'sort_id=' . urlencode($sort_id) . '&' : '') . 'website_page=' . ($website_page - 1) . '&ingame_page=' . $ingame_page; ?>" aria-label="<?php echo translate('admin_users_previous', 'Previous'); ?>">
                                                        <span aria-hidden="true">&laquo;</span>
                                                    </a>
                                                </li>
                                                <?php for ($i = 1; $i <= $total_website_pages; $i++): ?>
                                                    <li class="page-item <?php echo $i === $website_page ? 'active' : ''; ?>">
                                                        <a class="page-link" href="<?php echo $base_path; ?>admin/users?<?php echo ($search_username ? 'search_username=' . urlencode($search_username) . '&' : '') . ($search_email ? 'search_email=' . urlencode($search_email) . '&' : '') . ($role_filter ? 'role_filter=' . urlencode($role_filter) . '&' : '') . ($ban_filter ? 'ban_filter=' . urlencode($ban_filter) . '&' : '') . ($gmlevel_filter ? 'gmlevel_filter=' . urlencode($gmlevel_filter) . '&' : '') . ($sort_id ? 'sort_id=' . urlencode($sort_id) . '&' : '') . 'website_page=' . $i . '&ingame_page=' . $ingame_page; ?>"><?php echo $i; ?></a>
                                                    </li>
                                                <?php endfor; ?>
                                                <li class="page-item <?php echo $website_page >= $total_website_pages ? 'disabled' : ''; ?>">
                                                    <a class="page-link" href="<?php echo $base_path; ?>admin/users?<?php echo ($search_username ? 'search_username=' . urlencode($search_username) . '&' : '') . ($search_email ? 'search_email=' . urlencode($search_email) . '&' : '') . ($role_filter ? 'role_filter=' . urlencode($role_filter) . '&' : '') . ($ban_filter ? 'ban_filter=' . urlencode($ban_filter) . '&' : '') . ($gmlevel_filter ? 'gmlevel_filter=' . urlencode($gmlevel_filter) . '&' : '') . ($sort_id ? 'sort_id=' . urlencode($sort_id) . '&' : '') . 'website_page=' . ($website_page + 1) . '&ingame_page=' . $ingame_page; ?>" aria-label="<?php echo translate('admin_users_next', 'Next'); ?>">
                                                        <span aria-hidden="true">&raquo;</span>
                                                    </a>
                                                </li>
                                            </ul>
                                        </nav>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <!-- In-Game Tab -->
                        <div class="tab-pane fade <?php echo $active_tab === 'ingame' ? 'show active' : ''; ?>" id="ingame-tab">
                            <div class="card">
                                <div class="card-header"><?php echo translate('admin_users_ingame_accounts_header', 'In-Game Accounts'); ?></div>
                                <div class="card-body">
                                    <div class="table-wrapper">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th><?php echo translate('admin_users_table_account_id', 'Account ID'); ?></th>
                                                    <th><?php echo translate('admin_users_table_username', 'Username'); ?></th>
                                                    <th><?php echo translate('admin_users_table_email', 'Email'); ?></th>
                                                    <th><?php echo translate('admin_users_table_join_date', 'Join Date'); ?></th>
                                                    <th><?php echo translate('admin_users_table_last_login', 'Last Login'); ?></th>
                                                    <th><?php echo translate('admin_users_table_online', 'Online'); ?></th>
                                                    <th><?php echo translate('admin_users_table_ban_status', 'Ban Status'); ?></th>
                                                    <th><?php echo translate('admin_users_table_gm_level', 'GM Level'); ?></th>
                                                    <th><?php echo translate('admin_users_table_characters', 'Characters'); ?></th>
                                                    <th><?php echo translate('admin_users_table_action', 'Action'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($accounts)): ?>
                                                    <tr>
                                                        <td colspan="10"><?php echo translate('admin_users_no_accounts_found', 'No accounts found.'); ?></td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($accounts as $account): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($account['id']); ?></td>
                                                            <td><?php echo htmlspecialchars($account['username']); ?></td>
                                                            <td><?php echo htmlspecialchars($account['email'] ?? translate('admin_users_email_not_set', 'Not set')); ?></td>
                                                            <td><?php echo $account['joindate'] ? date('M j, Y H:i', strtotime($account['joindate'])) : translate('admin_users_na', 'N/A'); ?></td>
                                                            <td><?php echo $account['last_login'] ? date('M j, Y H:i', strtotime($account['last_login'])) : translate('admin_users_never', 'Never'); ?></td>
                                                            <td><?php echo getOnlineStatus($account['online']); ?></td>
                                                            <td><?php echo getAccountStatus($account['banInfo'] ?? []); ?></td>
                                                            <td><?php echo getGMLevel($account['gmlevel'] ?? null); ?></td>
                                                            <td>
                                                                <?php if (!empty($account['characters'])): ?>
                                                                    <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#characters-<?php echo $account['id']; ?>" aria-expanded="false" aria-controls="characters-<?php echo $account['id']; ?>">
                                                                        <?php echo translate('admin_users_show_characters_button', 'Show Characters') . ' (' . count($account['characters']) . ')'; ?>
                                                                    </button>
                                                                    <div class="collapse character-collapse" id="characters-<?php echo $account['id']; ?>">
                                                                        <div class="character-grid">
                                                                            <?php foreach ($account['characters'] as $char): ?>
                                                                                <div class="character-item">
                                                                                    <?php echo getFactionIcon($char['race']); ?>
                                                                                    <?php echo getRaceIcon($char['race'], $char['gender']); ?>
                                                                                    <?php echo getClassIcon($char['class']); ?>
                                                                                    <?php echo htmlspecialchars($char['name']); ?> (<?php echo translate('admin_users_level_prefix', 'Lv') . ' ' . $char['level']; ?>)
                                                                                </div>
                                                                            <?php endforeach; ?>
                                                                        </div>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <?php echo translate('admin_users_no_characters', 'No characters'); ?>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <button class="btn btn-manage" data-bs-toggle="modal" data-bs-target="#manageModal-<?php echo $account['id']; ?>"><?php echo translate('admin_users_manage_button', 'Manage'); ?></button>
                                                            </td>
                                                        </tr>
                                                        <!-- Manage Account Modal -->
                                                        <div class="modal fade" id="manageModal-<?php echo $account['id']; ?>" tabindex="-1" aria-labelledby="manageModalLabel-<?php echo $account['id']; ?>" aria-hidden="true">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title" id="manageModalLabel-<?php echo $account['id']; ?>"><?php echo translate('admin_users_manage_modal_title', 'Manage Account: ') . htmlspecialchars($account['username']); ?></h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo translate('admin_users_close_button', 'Close'); ?>"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <form method="POST" action="<?php echo $base_path; ?>admin/users">
                                                                            <input type="hidden" name="action" value="manage_account">
                                                                            <input type="hidden" name="account_id" value="<?php echo $account['id']; ?>">
                                                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                                            <div class="mb-3">
                                                                                <label class="form-label"><?php echo translate('admin_users_label_action', 'Action'); ?></label>
                                                                                <select name="ban_action" class="form-select" id="banAction-<?php echo $account['id']; ?>">
                                                                                    <option value="change_gm_role"><?php echo translate('admin_users_action_change_gm_role', 'Change GM Role'); ?></option>
                                                                                    <option value="ban"><?php echo translate('admin_users_action_ban', 'Ban Account'); ?></option>
                                                                                    <?php if (!empty($account['banInfo'])): ?>
                                                                                        <option value="unban"><?php echo translate('admin_users_action_unban', 'Unban Account'); ?></option>
                                                                                    <?php endif; ?>
                                                                                </select>
                                                                            </div>
                                                                            <div id="banFields-<?php echo $account['id']; ?>" style="display: none;">
                                                                                <div class="mb-3">
                                                                                    <label class="form-label"><?php echo translate('admin_users_label_ban_reason', 'Ban Reason'); ?></label>
                                                                                    <input type="text" name="ban_reason" class="form-control" placeholder="<?php echo translate('admin_users_ban_reason_placeholder', 'Enter ban reason'); ?>">
                                                                                </div>
                                                                                <div class="mb-3">
                                                                                    <label class="form-label"><?php echo translate('admin_users_label_ban_duration', 'Ban Duration'); ?></label>
                                                                                    <select name="ban_duration" class="form-select">
                                                                                        <option value="3600"><?php echo translate('admin_users_ban_duration_1hour', '1 Hour'); ?></option>
                                                                                        <option value="86400"><?php echo translate('admin_users_ban_duration_1day', '1 Day'); ?></option>
                                                                                        <option value="604800"><?php echo translate('admin_users_ban_duration_7days', '7 Days'); ?></option>
                                                                                        <option value="2592000"><?php echo translate('admin_users_ban_duration_30days', '30 Days'); ?></option>
                                                                                        <option value="permanent"><?php echo translate('admin_users_ban_duration_permanent', 'Permanent'); ?></option>
                                                                                    </select>
                                                                                </div>
                                                                            </div>
                                                                            <div id="gmFields-<?php echo $account['id']; ?>" style="display: block;">
                                                                                <div class="mb-3">
                                                                                    <label class="form-label"><?php echo translate('admin_users_label_gm_level', 'GM Level'); ?></label>
                                                                                    <select name="gmlevel" class="form-select">
                                                                                        <option value="player" <?php echo is_null($account['gmlevel']) ? 'selected' : ''; ?>><?php echo translate('admin_users_gmlevel_player', 'Player'); ?></option>
                                                                                        <option value="1" <?php echo $account['gmlevel'] === 1 ? 'selected' : ''; ?>><?php echo translate('admin_users_gmlevel_1', 'GM Level 1'); ?></option>
                                                                                        <option value="2" <?php echo $account['gmlevel'] === 2 ? 'selected' : ''; ?>><?php echo translate('admin_users_gmlevel_2', 'GM Level 2'); ?></option>
                                                                                        <option value="3" <?php echo $account['gmlevel'] === 3 ? 'selected' : ''; ?>><?php echo translate('admin_users_gmlevel_3', 'GM Level 3'); ?></option>
                                                                                    </select>
                                                                                </div>
                                                                            </div>
                                                                            <div class="modal-footer">
                                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo translate('admin_users_cancel_button', 'Cancel'); ?></button>
                                                                                <button type="submit" class="btn btn-primary"><?php echo translate('admin_users_apply_button', 'Apply'); ?></button>
                                                                            </div>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <!-- In-Game Accounts Pagination -->
                                    <?php if ($total_ingame_pages > 1): ?>
                                        <div class="pagination-label"><?php echo translate('admin_users_ingame_pagination_label', 'In-Game Accounts Pagination'); ?></div>
                                        <nav aria-label="<?php echo translate('admin_users_ingame_pagination_aria', 'In-game accounts pagination'); ?>">
                                            <ul class="pagination">
                                                <li class="page-item <?php echo $ingame_page <= 1 ? 'disabled' : ''; ?>">
                                                    <a class="page-link" href="<?php echo $base_path; ?>admin/users?<?php echo ($search_username ? 'search_username=' . urlencode($search_username) . '&' : '') . ($search_email ? 'search_email=' . urlencode($search_email) . '&' : '') . ($role_filter ? 'role_filter=' . urlencode($role_filter) . '&' : '') . ($ban_filter ? 'ban_filter=' . urlencode($ban_filter) . '&' : '') . ($gmlevel_filter ? 'gmlevel_filter=' . urlencode($gmlevel_filter) . '&' : '') . ($sort_id ? 'sort_id=' . urlencode($sort_id) . '&' : '') . 'ingame_page=' . ($ingame_page - 1) . '&website_page=' . $website_page; ?>" aria-label="<?php echo translate('admin_users_previous', 'Previous'); ?>">
                                                        <span aria-hidden="true">&laquo;</span>
                                                    </a>
                                                </li>
                                                <?php for ($i = 1; $i <= $total_ingame_pages; $i++): ?>
                                                    <li class="page-item <?php echo $i === $ingame_page ? 'active' : ''; ?>">
                                                        <a class="page-link" href="<?php echo $base_path; ?>admin/users?<?php echo ($search_username ? 'search_username=' . urlencode($search_username) . '&' : '') . ($search_email ? 'search_email=' . urlencode($search_email) . '&' : '') . ($role_filter ? 'role_filter=' . urlencode($role_filter) . '&' : '') . ($ban_filter ? 'ban_filter=' . urlencode($ban_filter) . '&' : '') . ($gmlevel_filter ? 'gmlevel_filter=' . urlencode($gmlevel_filter) . '&' : '') . ($sort_id ? 'sort_id=' . urlencode($sort_id) . '&' : '') . 'ingame_page=' . $i . '&website_page=' . $website_page; ?>"><?php echo $i; ?></a>
                                                    </li>
                                                <?php endfor; ?>
                                                <li class="page-item <?php echo $ingame_page >= $total_ingame_pages ? 'disabled' : ''; ?>">
                                                    <a class="page-link" href="<?php echo $base_path; ?>admin/users?<?php echo ($search_username ? 'search_username=' . urlencode($search_username) . '&' : '') . ($search_email ? 'search_email=' . urlencode($search_email) . '&' : '') . ($role_filter ? 'role_filter=' . urlencode($role_filter) . '&' : '') . ($ban_filter ? 'ban_filter=' . urlencode($ban_filter) . '&' : '') . ($gmlevel_filter ? 'gmlevel_filter=' . urlencode($gmlevel_filter) . '&' : '') . ($sort_id ? 'sort_id=' . urlencode($sort_id) . '&' : '') . 'ingame_page=' . ($ingame_page + 1) . '&website_page=' . $website_page; ?>" aria-label="<?php echo translate('admin_users_next', 'Next'); ?>">
                                                        <span aria-hidden="true">&raquo;</span>
                                                    </a>
                                                </li>
                                            </ul>
                                        </nav>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include $project_root . 'includes/footer.php'; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo $base_path; ?>assets/js/pages/admin/users.js"></script>
</body>
</html>
<?php
$site_db->close();
$auth_db->close();
$char_db->close();
?>