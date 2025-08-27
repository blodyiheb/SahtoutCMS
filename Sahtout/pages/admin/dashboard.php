<?php
define('ALLOWED_ACCESS', true);
// Include session, language, and config
require_once __DIR__ . '/../../includes/session.php'; // Includes config.php
require_once __DIR__ . '/../../languages/language.php'; // Include translation system

$page_class = 'dashboard';

// Check if user is admin or moderator
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'moderator'])) {
    header('Location: /sahtout/login');
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
if (!isset($char_db)) {
    $char_db = new mysqli('localhost', 'root', '12345', 'acore_characters');
    if ($char_db->connect_error) {
        die(translate('admin_dashboard_error_db_connection', 'Characters DB connection failed: ') . $char_db->connect_error);
    }
}

// Get quick stats
$total_users_query = "SELECT COUNT(*) AS count FROM user_currencies";
$total_users_result = $site_db->query($total_users_query);
$total_users = $total_users_result->fetch_assoc()['count'];
$total_users_result->free();

$total_accounts_query = "SELECT COUNT(*) AS count FROM acore_auth.account";
$total_accounts_result = $auth_db->query($total_accounts_query);
$total_accounts = $total_accounts_result->fetch_assoc()['count'];
$total_accounts_result->free();

$total_chars_query = "SELECT COUNT(*) AS count FROM characters";
$total_chars_result = $char_db->query($total_chars_query);
$total_chars = $total_chars_result->fetch_assoc()['count'];
$total_chars_result->free();

$total_bans_query = "SELECT COUNT(*) AS count FROM acore_auth.account_banned WHERE active = 1";
$total_bans_result = $auth_db->query($total_bans_query);
$total_bans = $total_bans_result->fetch_assoc()['count'];
$total_bans_result->free();

// Handle search and filter for recent admins/moderators
$search_username = isset($_GET['search_username']) ? trim($_GET['search_username']) : '';
$search_email = isset($_GET['search_email']) ? trim($_GET['search_email']) : '';
$role_filter = isset($_GET['role_filter']) && in_array($_GET['role_filter'], ['admin', 'moderator', '']) ? $_GET['role_filter'] : '';

// Get recent admins/moderators with email, online status, and ban info
$users_query = "SELECT uc.account_id, uc.username, uc.points, uc.tokens, uc.role, uc.last_updated, a.email, a.online, a.locked 
                FROM user_currencies uc 
                JOIN acore_auth.account a ON uc.account_id = a.id 
                WHERE uc.role IN ('admin', 'moderator')";
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
$users_query .= " ORDER BY uc.last_updated DESC LIMIT 5";
$stmt = $site_db->prepare($users_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users_result = $stmt->get_result();

// Fetch ban info for users
$users = [];
while ($user = $users_result->fetch_assoc()) {
    $users[$user['account_id']] = $user;
}
$users_result->free();

$account_ids = array_keys($users);
if (!empty($account_ids)) {
    $placeholders = implode(',', array_fill(0, count($account_ids), '?'));
    $stmt = $auth_db->prepare("SELECT id, bandate, unbandate, banreason 
                               FROM acore_auth.account_banned 
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
               FROM acore_auth.account_banned ab 
               JOIN acore_auth.account a ON ab.id = a.id 
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .account-sahtout-icon {
            width: 24px;
            height: 24px;
            vertical-align: middle;
        }
        .table-wrapper {
            overflow-x: auto;
        }
        .alert {
            margin-bottom: 1rem;
        }
        .search-form {
            margin-bottom: 1.5rem;
        }
        .pagination {
            justify-content: center;
            margin-top: 1.5rem;
        }
        .dashboard-container {
            flex-grow: 1;
        }
        html, body {
            height: 100%;
            margin: 0;
            display: flex;
            flex-direction: column;
        }
        body {
            background-color: #ffffff;
            color: #000;
            font-family: Arial, sans-serif;
        }
        .wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .dashboard-title {
            color: #333;
            font-size: 2.5rem;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .card {
            text-align: center;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid #ccc;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .card-header {
            background: rgba(230, 230, 230, 0.9);
            border-bottom: 1px solid #ccc;
            color: #333;
            font-size: 1.25rem;
            padding: 0.75rem 1rem;
        }
        .card-body {
            padding: 1rem;
        }
        .table {
            color: #333;
            background: none;
            width: 100%;
        }
        .table th, .table td {
            border: 1px solid #ccc;
            padding: 0.5rem;
        }
        .table th {
            background: rgba(240, 240, 240, 0.9);
            color: #000;
        }
        .table .btn {
            background: #f0f0f0;
            border: 2px solid #999;
            color: #333;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }
        .table .btn:hover {
            background: #ddd;
            color: #000;
        }
        .form-control, .form-select {
            background: #f9f9f9;
            color: #333;
            border: 1px solid #999;
        }
        .form-control:focus, .form-select:focus {
            background: #fff;
            color: #000;
            border-color: #666;
            box-shadow: none;
        }
        .form-control::placeholder {
            color: #666;
            font-weight: bold;
            opacity: 1;
        }
        /* Server Status Styling */
        .card.server-status {
            background: linear-gradient(135deg, #e0f7fa, #b2ebf2);
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .card.server-status:hover {
            transform: translateY(-5px);
            cursor: pointer;
        }
        .server-status ul {
            padding-left: 0;
            list-style: none;
        }
        .server-status li {
            padding: 10px;
            border-bottom: 1px solid #b2ebf2;
        }
        .server-status li:last-child {
            border-bottom: none;
        }
        .server-status li:hover {
            background: #b2ebf2;
            border-radius: 5px;
        }
        .server-status .realm-name {
            color: #0066cc;
            font-weight: bold;
        }
        .server-status .online {
            color: #55ff55;
        }
        .server-status .offline {
            color: #ff5555;
        }
        .server-status .players, .server-status .realm-ip {
            color: #333;
        }
        /* Quick Stats Styling */
        .card.quick-stats {
            background: linear-gradient(135deg, #f0f4c3, #dcedc8);
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .card.quick-stats:hover {
            transform: translateY(-5px);
        }
        .quick-stats ul {
            padding-left: 0;
            list-style: none;
            display: grid;
            gap: 10px;
        }
        .quick-stats li {
            padding: 10px;
            transition: transform 0.2s;
        }
        .quick-stats li:hover {
            transform: scale(1.05);
        }
        .quick-stats .stat-label {
            color: #333;
            font-weight: bold;
            text-decoration: underline;
            text-underline-offset: 4px;
        }
        .quick-stats .stat-value {
            color: #000;
            font-size: 1.1rem;
        }
        @media (max-width: 768px) {
            .dashboard-container {
                width: calc(100% - 2rem);
                margin: 1rem auto;
                padding: 0 1rem;
            }
            .dashboard-title {
                font-size: 2rem;
            }
            .card-header {
                font-size: 1.1rem;
            }
            .table {
                font-size: 0.9rem;
            }
            .server-status li, .quick-stats li {
                padding: 5px;
            }
        }
    </style>
</head>
<body class="dashboard">
    <div class="wrapper">
        <?php include dirname(__DIR__) . '../../includes/header.php'; ?>
        <div class="dashboard-container">
            <div class="row">
                <!-- Sidebar -->
                <?php include dirname(__DIR__) . '../../includes/admin_sidebar.php'; ?>
                <!-- Main Content -->
                <div class="col-md-9">
                    <h1 class="dashboard-title"><?php echo translate('admin_dashboard_title', 'Admin & Moderator Dashboard'); ?></h1>

                    <!-- Server Status -->
                    <div class="card server-status">
                        <div class="card-header"><?php echo translate('admin_dashboard_server_status_header', 'Server Status'); ?></div>
                        <div class="card-body">
                            <?php 
                            include __DIR__ . '/../../includes/realm_status.php'; 
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
                            <form class="search-form" method="GET" action="/Sahtout/admin/dashboard">
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
                                <div class="input-group mb-3">
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
                                                    <td><?php echo getOnlineStatus($user['online']); ?></td>
                                                    <td><?php echo getAccountStatus($user['locked'], $user['banInfo'] ?? []); ?></td>
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
                                                        <a href="/Sahtout/admin/users#user-<?php echo $ban['id']; ?>" class="btn"><?php echo translate('admin_dashboard_manage_button', 'Manage'); ?></a>
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
        <?php include dirname(__DIR__) . '../../includes/footer.php'; ?>
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