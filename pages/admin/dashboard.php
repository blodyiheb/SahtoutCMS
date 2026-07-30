<?php
define('ALLOWED_ACCESS', true);

// Include session, language, and paths
require_once __DIR__ . '/../../includes/paths.php';
require_once $project_root . 'includes/session.php'; // Includes config.php
require_once $project_root . 'languages/language.php'; // Include translation system
require_once $project_root . 'includes/config.settings.php';
$page_class = 'dashboard';
define('DB_AUTH', $db_auth_name);
define('DB_CHAR', $db_char_name);
define('DB_WORLD', $db_world_name);
define('DB_SITE', $db_site_name);
// Check if user is admin or moderator
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'moderator'])) {
    header("Location: {$base_path}login");
    exit;
}

// Function to get online status
function getOnlineStatus($online) {
    return $online ? "<span style='color: #55ff55'>" . translate('admin_dashboard_status_online', 'Online') . "</span>" : "<span style='color: #ff5555'>" . translate('admin_dashboard_status_offline', 'Offline') . "</span>";
}

// Function to get account status
function getAccountStatus($locked, $banInfo) {
    if (!empty($banInfo)) {
        $reason = htmlspecialchars($banInfo['banreason'] ?? translate('admin_dashboard_no_reason_provided', 'No reason provided'));
        $unbanDate = $banInfo['unbandate'] ? date('Y-m-d H:i:s', $banInfo['unbandate']) : translate('admin_dashboard_permanent', 'Permanent');
        return "<span style='color: #ff5555'>" . translate('admin_dashboard_status_banned', 'Banned') . " (" . translate('admin_dashboard_reason', 'Reason') . ": $reason, " . translate('admin_dashboard_until', 'Until') . ": $unbanDate)</span>";
    }
    switch ($locked) {
        case 1:
            return "<span style='color: #ff5555'>" . translate('admin_dashboard_status_banned', 'Banned') . "</span>";
        case 2:
            return "<span style='color: #55ffff'>" . translate('admin_dashboard_status_frozen', 'Frozen') . "</span>";
        default:
            return "<span style='color: #05f30594'>" . translate('admin_dashboard_status_active', 'Active') . "</span>";
    }
}

// Use databases
global $site_db, $auth_db, $char_db;

// Get quick stats
$total_users_query = "SELECT COUNT(*) AS count FROM " . DB_SITE . ".user_currencies";
$total_users_result = $site_db->query($total_users_query);
$total_users = $total_users_result->fetch_assoc()['count'];
$total_users_result->free();

$total_accounts_query = "SELECT COUNT(*) AS count FROM " . DB_AUTH . ".account";
$total_accounts_result = $auth_db->query($total_accounts_query);
$total_accounts = $total_accounts_result->fetch_assoc()['count'];
$total_accounts_result->free();

$total_chars_query = "SELECT COUNT(*) AS count FROM " . DB_CHAR . ".characters";
$total_chars_result = $char_db->query($total_chars_query);
$total_chars = $total_chars_result->fetch_assoc()['count'];
$total_chars_result->free();

$total_bans_query = "SELECT COUNT(*) AS count FROM " . DB_AUTH . ".account_banned WHERE active = 1";
$total_bans_result = $auth_db->query($total_bans_query);
$total_bans = $total_bans_result->fetch_assoc()['count'];
$total_bans_result->free();

// Handle search and filter for recent admins/moderators
$search_username = isset($_GET['search_username']) ? trim($_GET['search_username']) : '';
$search_email = isset($_GET['search_email']) ? trim($_GET['search_email']) : '';
$role_filter = isset($_GET['role_filter']) && in_array($_GET['role_filter'], ['admin', 'moderator', '']) ? $_GET['role_filter'] : '';

// Get recent admins/moderators – **NO CROSS‑DB JOIN**
$users_query = "SELECT account_id, username, points, tokens, role, last_updated
                FROM " . DB_SITE . ".user_currencies
                WHERE role IN ('admin', 'moderator')";
$params = [];
$types = '';

if ($search_username) {
    $users_query .= " AND username LIKE ?";
    $params[] = "%$search_username%";
    $types   .= 's';
}
if ($role_filter) {
    $users_query .= " AND role = ?";
    $params[] = $role_filter;
    $types   .= 's';
}
$users_query .= " ORDER BY last_updated DESC LIMIT 5";

$stmt = $site_db->prepare($users_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users_result = $stmt->get_result();

$users = [];
$account_ids = [];
while ($row = $users_result->fetch_assoc()) {
    $users[$row['account_id']] = $row;
    $account_ids[] = $row['account_id'];
}
$users_result->free();
$stmt->close();

/* ------------------------------------------------------------------
   Now pull the missing fields (email, online, locked) from the Auth DB
   ------------------------------------------------------------------ */
if (!empty($account_ids)) {
    $placeholders = implode(',', array_fill(0, count($account_ids), '?'));
    $auth_query   = "SELECT id, email, online, locked
                     FROM " . DB_AUTH . ".account
                     WHERE id IN ($placeholders)";

    $stmt = $auth_db->prepare($auth_query);
    $stmt->bind_param(str_repeat('i', count($account_ids)), ...$account_ids);
    $stmt->execute();
    $auth_result = $stmt->get_result();

    while ($auth = $auth_result->fetch_assoc()) {
        $aid = $auth['id'];
        if (isset($users[$aid])) {
            $users[$aid]['email']  = $auth['email'];
            $users[$aid]['online'] = $auth['online'];
            $users[$aid]['locked'] = $auth['locked'];
        }
    }
    $auth_result->free();
    $stmt->close();
}

/* ------------------------------------------------------------------
   Fetch ban info (still from Auth DB, unchanged)
   ------------------------------------------------------------------ */
if (!empty($account_ids)) {
    $placeholders = implode(',', array_fill(0, count($account_ids), '?'));
    $stmt = $auth_db->prepare("SELECT id, bandate, unbandate, banreason 
                               FROM " . DB_AUTH . ".account_banned 
                               WHERE id IN ($placeholders) AND active = 1");
    $stmt->bind_param(str_repeat('i', count($account_ids)), ...$account_ids);
    $stmt->execute();
    $ban_result = $stmt->get_result();
    while ($ban = $ban_result->fetch_assoc()) {
        $users[$ban['id']]['banInfo'] = $ban;
    }
    $stmt->close();
}

// Get recent bans
$bans_query = "SELECT ab.id, ab.bandate, ab.unbandate, ab.banreason, a.username 
               FROM " . DB_AUTH . ".account_banned ab 
               JOIN " . DB_AUTH . ".account a ON ab.id = a.id 
               WHERE ab.active = 1 
               ORDER BY ab.bandate DESC 
               LIMIT 5";
$bans_result = $auth_db->query($bans_query);
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo translate('admin_dashboard_meta_description', 'Admin and Moderator Dashboard for Sahtout WoW Server'); ?>">
    <meta name="robots" content="noindex">
    <title><?php echo translate('admin_dashboard_page_title', 'Admin & Moderator Dashboard'); ?></title>
    <link rel="icon" href="<?php echo $base_path . $site_logo; ?>" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/admin/dashboard.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/admin/admin_sidebar.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="dashboard">
    <div class="wrapper">
        <?php include $project_root . 'includes/header.php'; ?>
        <div class="dashboard-container">
            <div class="row">
                <!-- Sidebar -->
                <?php include $project_root . 'includes/admin_sidebar.php'; ?>
                <!-- Main Content -->
                <div class="col-md-9">
                    <h1 class="dashboard-title"><?php echo translate('admin_dashboard_title', 'Admin & Moderator Dashboard'); ?></h1>
                    <!-- Server Status -->
                    <div class="card server-status">
                        <div class="card-header"><?php echo translate('admin_dashboard_server_status_header', 'Server Status'); ?></div>
                        <div class="card-body">
                            <?php 
                            include $project_root . 'includes/realm_status.php'; 
                            ?>
                        </div>
                    </div>
                    <!-- Quick Stats -->
                    <div class="card quick-stats">
                        <div class="card-header"><?php echo translate('admin_dashboard_quick_stats_header', 'Quick Stats'); ?></div>
                        <div class="card-body">
                            <div class="quick-stats">
                                <ul>
                                    <li><span class="stat-label"><?php echo translate('admin_dashboard_total_website_users', 'Total Website Users'); ?>:</span> <span class="stat-value"><?php echo htmlspecialchars($total_users); ?></span></li>
                                    <li><span class="stat-label"><?php echo translate('admin_dashboard_total_ingame_accounts', 'Total In-Game Accounts'); ?>:</span> <span class="stat-value"><?php echo htmlspecialchars($total_accounts); ?></span></li>
                                    <li><span class="stat-label"><?php echo translate('admin_dashboard_total_characters', 'Total Characters'); ?>:</span> <span class="stat-value"><?php echo htmlspecialchars($total_chars); ?></span></li>
                                    <li><span class="stat-label"><?php echo translate('admin_dashboard_active_bans', 'Active Bans'); ?>:</span> <span class="stat-value"><?php echo htmlspecialchars($total_bans); ?></span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <!-- Recent Admins & Moderators -->
                    <div class="card">
                        <div class="card-header"><?php echo translate('admin_dashboard_recent_staff_header', 'Recent Admins & Moderators'); ?></div>
                        <div class="card-body">
                            <!-- Search and Filter Form -->
                            <form class="search-form" method="GET" action="<?php echo $base_path; ?>admin/dashboard">
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <input type="text" name="search_username" class="form-control" placeholder="<?php echo translate('admin_dashboard_search_username_placeholder', 'Search by username'); ?>" value="<?php echo htmlspecialchars($search_username); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" name="search_email" class="form-control" placeholder="<?php echo translate('admin_dashboard_search_email_placeholder', 'Search by email'); ?>" value="<?php echo htmlspecialchars($search_email); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <select name="role_filter" class="form-select">
                                            <option value="" <?php echo $role_filter === '' ? 'selected' : ''; ?>><?php echo translate('admin_dashboard_all_staff_roles', 'All Staff Roles'); ?></option>
                                            <option value="moderator" <?php echo $role_filter === 'moderator' ? 'selected' : ''; ?>><?php echo translate('admin_dashboard_role_moderator', 'Moderator'); ?></option>
                                            <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>><?php echo translate('admin_dashboard_role_admin', 'Admin'); ?></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="input-group mb-3 justify-content-center">
                                    <button class="btn" type="submit"><?php echo translate('admin_dashboard_apply_button', 'Apply'); ?></button>
                                </div>
                            </form>
                            <div class="table-wrapper">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th><?php echo translate('admin_dashboard_table_username', 'Username'); ?></th>
                                            <th><?php echo translate('admin_dashboard_table_email', 'Email'); ?></th>
                                            <th><?php echo translate('admin_dashboard_table_points', 'Points'); ?></th>
                                            <th><?php echo translate('admin_dashboard_table_tokens', 'Tokens'); ?></th>
                                            <th><?php echo translate('admin_dashboard_table_role', 'Role'); ?></th>
                                            <th><?php echo translate('admin_dashboard_table_online', 'Online'); ?></th>
                                            <th><?php echo translate('admin_dashboard_table_ban_status', 'Ban Status'); ?></th>
                                            <th><?php echo translate('admin_dashboard_table_last_updated', 'Last Updated'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($users)): ?>
                                            <tr>
                                                <td colspan="8"><?php echo translate('admin_dashboard_no_staff_found', 'No admins or moderators found.'); ?></td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($users as $user): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['email'] ?? translate('admin_dashboard_email_not_set', 'Not set')); ?></td>
                                                    <td><?php echo htmlspecialchars($user['points']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['tokens']); ?></td>
                                                    <td><span class="status-<?php echo htmlspecialchars($user['role']); ?>">
                                                        <?php echo ucfirst(translate('admin_dashboard_role_' . $user['role'], ucfirst($user['role']))); ?>
                                                    </span></td>
                                                    <td><?php echo getOnlineStatus($user['online'] ?? 0); ?></td>
                                                    <td><?php echo getAccountStatus($user['locked'] ?? 0, $user['banInfo'] ?? []); ?></td>
                                                    <td><?php echo $user['last_updated'] ? date('M j, Y H:i', strtotime($user['last_updated'])) : translate('admin_dashboard_never', 'Never'); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- Recent Bans -->
                    <div class="card">
                        <div class="card-header"><?php echo translate('admin_dashboard_recent_bans_header', 'Recent Bans'); ?></div>
                        <div class="card-body">
                            <div class="table-wrapper">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th><?php echo translate('admin_dashboard_table_account_id', 'Account ID'); ?></th>
                                            <th><?php echo translate('admin_dashboard_table_username', 'Username'); ?></th>
                                            <th><?php echo translate('admin_dashboard_table_ban_reason', 'Ban Reason'); ?></th>
                                            <th><?php echo translate('admin_dashboard_table_ban_date', 'Ban Date'); ?></th>
                                            <th><?php echo translate('admin_dashboard_table_unban_date', 'Unban Date'); ?></th>
                                            <th><?php echo translate('admin_dashboard_table_action', 'Action'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($bans_result->num_rows === 0): ?>
                                            <tr>
                                                <td colspan="6"><?php echo translate('admin_dashboard_no_bans_found', 'No bans found.'); ?></td>
                                            </tr>
                                        <?php else: ?>
                                            <?php while ($ban = $bans_result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($ban['id']); ?></td>
                                                    <td><?php echo htmlspecialchars($ban['username']); ?></td>
                                                    <td><?php echo htmlspecialchars($ban['banreason'] ?? translate('admin_dashboard_no_reason_provided', 'No reason provided')); ?></td>
                                                    <td><?php echo $ban['bandate'] ? date('M j, Y H:i', strtotime($ban['bandate'])) : translate('admin_dashboard_na', 'N/A'); ?></td>
                                                    <td><?php echo $ban['unbandate'] ? date('M j, Y H:i', strtotime($ban['unbandate'])) : translate('admin_dashboard_permanent', 'Permanent'); ?></td>
                                                    <td>
                                                        <a href="<?php echo $base_path; ?>admin/users#user-<?php echo $ban['id']; ?>" class="btn"><?php echo translate('admin_dashboard_manage_button', 'Manage'); ?></a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                        <?php $bans_result->free(); ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include $project_root . 'includes/footer.php'; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php 
$site_db->close();
$auth_db->close();
if (isset($char_db)) {
    $char_db->close();
}
?>