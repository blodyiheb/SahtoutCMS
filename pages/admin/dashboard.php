<?php
define('ALLOWED_ACCESS', true);

require_once __DIR__ . '/../../includes/paths.php';
require_once $project_root . 'includes/session.php';
require_once $project_root . 'languages/language.php';
require_once $project_root . 'includes/config.settings.php';

$page_class = 'dashboard';
define('DB_AUTH', $db_auth_name);
define('DB_CHAR', $db_char_name);
define('DB_WORLD', $db_world_name);
define('DB_SITE', $db_site_name);

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'moderator'])) {
    header("Location: {$base_path}login");
    exit;
}

function getOnlineStatus($online) {
    return $online 
        ? '<span class="text-green-400 font-semibold"><i class="fas fa-circle text-green-400 text-xs mr-1"></i> ' . translate('admin_dashboard_status_online', 'Online') . '</span>' 
        : '<span class="text-red-400 font-semibold"><i class="fas fa-circle text-red-400 text-xs mr-1"></i> ' . translate('admin_dashboard_status_offline', 'Offline') . '</span>';
}

function getAccountStatus($locked, $banInfo) {
    if (!empty($banInfo)) {
        return '<span class="text-red-400 font-semibold"><i class="fas fa-ban mr-1"></i> ' . translate('admin_dashboard_status_banned', 'Banned') . '</span>';
    }
    switch ($locked) {
        case 1:
            return '<span class="text-red-400 font-semibold"><i class="fas fa-ban mr-1"></i> ' . translate('admin_dashboard_status_banned', 'Banned') . '</span>';
        case 2:
            return '<span class="text-cyan-400 font-semibold"><i class="fas fa-snowflake mr-1"></i> ' . translate('admin_dashboard_status_frozen', 'Frozen') . '</span>';
        default:
            return '<span class="text-green-400 font-semibold"><i class="fas fa-check-circle mr-1"></i> ' . translate('admin_dashboard_status_active', 'Active') . '</span>';
    }
}

global $site_db, $auth_db, $char_db;

// Quick Stats
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

// Handle search and filter
$search_username = isset($_GET['search_username']) ? trim($_GET['search_username']) : '';
$search_email = isset($_GET['search_email']) ? trim($_GET['search_email']) : '';
$role_filter = isset($_GET['role_filter']) && in_array($_GET['role_filter'], ['admin', 'moderator', '']) ? $_GET['role_filter'] : '';

// Get recent admins/moderators
$users_query = "SELECT account_id, username, points, tokens, role, last_updated
                FROM " . DB_SITE . ".user_currencies
                WHERE role IN ('admin', 'moderator')";
$params = [];
$types = '';

if ($search_username) {
    $users_query .= " AND username LIKE ?";
    $params[] = "%$search_username%";
    $types .= 's';
}
if ($role_filter) {
    $users_query .= " AND role = ?";
    $params[] = $role_filter;
    $types .= 's';
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

if (!empty($account_ids)) {
    $placeholders = implode(',', array_fill(0, count($account_ids), '?'));
    $auth_query = "SELECT id, email, online, locked FROM " . DB_AUTH . ".account WHERE id IN ($placeholders)";
    $stmt = $auth_db->prepare($auth_query);
    $stmt->bind_param(str_repeat('i', count($account_ids)), ...$account_ids);
    $stmt->execute();
    $auth_result = $stmt->get_result();
    while ($auth = $auth_result->fetch_assoc()) {
        $aid = $auth['id'];
        if (isset($users[$aid])) {
            $users[$aid]['email'] = $auth['email'];
            $users[$aid]['online'] = $auth['online'];
            $users[$aid]['locked'] = $auth['locked'];
        }
    }
    $auth_result->free();
    $stmt->close();
}

if (!empty($account_ids)) {
    $placeholders = implode(',', array_fill(0, count($account_ids), '?'));
    $stmt = $auth_db->prepare("SELECT id, bandate, unbandate, banreason FROM " . DB_AUTH . ".account_banned WHERE id IN ($placeholders) AND active = 1");
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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&family=Cinzel:wght@600;700;900&display=swap" rel="stylesheet">
    
    <style>
        * { font-family: 'Inter', sans-serif; }

        body {
            min-height: 100vh;
            color: #d8d8d8;
            background: #05070b;
            background-image:
                radial-gradient(1000px 700px at -10% 35%, rgba(59,130,246,.14), transparent 65%),
                radial-gradient(800px 600px at -5% 85%, rgba(124,58,237,.10), transparent 70%),
                linear-gradient(180deg, #0a0e16 0%, #060810 45%, #03040a 100%);
            background-attachment: fixed;
        }

        .wow-title {
            font-family: 'Cinzel', serif;
            font-weight: 900;
            background: linear-gradient(180deg, #fff7d6 0%, #f2cf5b 35%, #c9a227 62%, #8a6a14 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 3px 6px rgba(0,0,0,.85));
        }

        .section-title {
            font-family: 'Cinzel', serif;
            font-weight: 700;
            color: #f2cf5b;
            text-shadow: 0 0 12px rgba(201,162,39,.35), 0 2px 4px rgba(0,0,0,.8);
        }

        .stat-number {
            font-family: 'Cinzel', serif;
            font-weight: 900;
            font-size: 2rem;
            background: linear-gradient(180deg, #fff7d6 0%, #f2cf5b 35%, #c9a227 62%, #8a6a14 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .panel {
            position: relative;
            background: linear-gradient(180deg, rgba(22,25,32,.92), rgba(8,10,14,.9));
            border: 1px solid rgba(201,162,39,.22);
            box-shadow: 0 12px 32px rgba(0,0,0,.55), inset 0 0 60px rgba(0,0,0,.45);
        }

        .panel::before {
            content: '';
            position: absolute;
            inset: 5px;
            border: 1px solid rgba(201,162,39,.14);
            pointer-events: none;
        }

        .panel::after {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                linear-gradient(#e8c552,#e8c552) left top / 18px 2px,
                linear-gradient(#e8c552,#e8c552) left top / 2px 18px,
                linear-gradient(#e8c552,#e8c552) right top / 18px 2px,
                linear-gradient(#e8c552,#e8c552) right top / 2px 18px,
                linear-gradient(#e8c552,#e8c552) left bottom / 18px 2px,
                linear-gradient(#e8c552,#e8c552) left bottom / 2px 18px,
                linear-gradient(#e8c552,#e8c552) right bottom / 18px 2px,
                linear-gradient(#e8c552,#e8c552) right bottom / 2px 18px;
            background-repeat: no-repeat;
        }

        .btn-gold {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            padding: .75rem 1.5rem;
            font-weight: 800;
            font-size: .85rem;
            letter-spacing: .04em;
            text-transform: uppercase;
            clip-path: polygon(10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%, 0 10px);
            transition: transform .2s ease;
            background: linear-gradient(180deg, #f6d478 0%, #c9a227 48%, #8a6a14 100%);
            color: #1a1200;
            text-shadow: 0 1px 0 rgba(255,255,255,.35);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.28), inset 0 -8px 14px rgba(0,0,0,.25);
            border: none;
            cursor: pointer;
        }

        .btn-gold:hover { transform: translateY(-2px) scale(1.02); }

        .btn-iron {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            padding: .75rem 1.5rem;
            font-weight: 800;
            font-size: .85rem;
            letter-spacing: .04em;
            text-transform: uppercase;
            clip-path: polygon(10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%, 0 10px);
            transition: transform .2s ease;
            background: linear-gradient(180deg, #3a404d 0%, #232833 55%, #141821 100%);
            color: #cfe1ff;
            box-shadow: inset 0 0 0 1px rgba(120,160,255,.25), inset 0 -8px 14px rgba(0,0,0,.4);
            border: none;
            cursor: pointer;
        }

        .btn-iron:hover { transform: translateY(-2px) scale(1.02); }

        .input-dark {
            background: rgba(10, 14, 22, 0.8);
            border: 1px solid rgba(201,162,39,.3);
            color: #e5e7eb;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            outline: none;
            width: 100%;
        }

        .input-dark:focus {
            border-color: #f2cf5b;
            box-shadow: 0 0 10px rgba(242,207,82,.2);
            background: rgba(15, 20, 30, 0.9);
        }

        .input-dark::placeholder { color: rgba(150, 170, 200, 0.4); }
        .input-dark option { background: #0a0e16; }

        .table-dark th {
            background: rgba(10, 14, 22, 0.9);
            color: #f2cf5b;
            font-family: 'Cinzel', serif;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            padding: 1rem;
            border-bottom: 2px solid rgba(201,162,39,.4);
            text-align: left;
        }

        .table-dark td {
            padding: 1rem;
            border-bottom: 1px solid rgba(201,162,39,.1);
            color: #d8d8d8;
            background: rgba(22, 25, 32, 0.6);
        }

        .table-dark tr:hover td {
            background: rgba(30, 35, 45, 0.8);
        }

        .realm-wrapper {
            font-size: 1.05rem !important;
        }

        .realm-wrapper * {
            font-size: inherit !important;
        }

        .realm-wrapper .realm-name,
        .realm-wrapper [class*="realm"]:not([class*="status"]),
        .realm-wrapper strong,
        .realm-wrapper b,
        .realm-wrapper .name {
            font-size: 1.2rem !important;
            color: #f2cf5b !important;
            font-family: 'Cinzel', serif !important;
            font-weight: 700 !important;
        }

        .realm-wrapper .players,
        .realm-wrapper [class*="player"],
        .realm-wrapper [class*="count"] {
            font-size: 1.1rem !important;
            font-weight: 600 !important;
            color: #b8c8ff !important;
        }

        .realm-wrapper .online,
        .realm-wrapper .offline,
        .realm-wrapper .status {
            font-size: 1rem !important;
            font-weight: 700 !important;
        }

        .realm-wrapper .online { color: #55ff55 !important; }
        .realm-wrapper .offline { color: #ff5555 !important; }

        .realm-wrapper ul,
        .realm-wrapper .realm-list {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) !important;
            gap: 1rem !important;
            list-style: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .realm-wrapper li,
        .realm-wrapper .realm-item,
        .realm-wrapper .server-item {
            background: rgba(10, 14, 22, 0.5) !important;
            padding: 1.2rem !important;
            border: 1px solid rgba(201,162,39,.15) !important;
            text-align: center !important;
            border-radius: 4px !important;
            transition: all 0.3s ease !important;
        }

        .realm-wrapper li:hover,
        .realm-wrapper .realm-item:hover,
        .realm-wrapper .server-item:hover {
            border-color: rgba(201,162,39,.4) !important;
            background: rgba(15, 20, 30, 0.7) !important;
        }

        .realm-wrapper > div:not([class*="wrapper"]):not([class*="container"]) {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) !important;
            gap: 1rem !important;
        }

        .realm-wrapper p,
        .realm-wrapper .realm-entry {
            background: rgba(10, 14, 22, 0.5) !important;
            padding: 1.2rem !important;
            margin: 0 !important;
            border: 1px solid rgba(201,162,39,.15) !important;
            text-align: center !important;
            border-radius: 4px !important;
        }

        .realm-wrapper p:hover,
        .realm-wrapper .realm-entry:hover {
            border-color: rgba(201,162,39,.4) !important;
            background: rgba(15, 20, 30, 0.7) !important;
        }

        .status-admin { color: #f2cf5b; font-weight: 700; }
        .status-moderator { color: #6a8cff; font-weight: 700; }

        /* Main content area - works with sidebar */
        .main-content-area {
            transition: margin-left 0.3s ease, width 0.3s ease;
            min-height: calc(100vh - 72px);
            width: 100%;
        }

        /* Content wrapper with proper spacing */
        .content-wrapper {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        @media (min-width: 640px) {
            .content-wrapper {
                padding: 0 1.5rem;
            }
        }

        @media (min-width: 1024px) {
            .content-wrapper {
                padding: 0 2rem;
            }
        }

        @media (min-width: 1280px) {
            .content-wrapper {
                padding: 0 2.5rem;
            }
        }

        @media (min-width: 1024px) {
            .main-content-area.lg\:ml-0 {
                margin-left: 0;
            }
            .main-content-area.lg\:ml-\[280px\] {
                margin-left: 280px;
            }
        }

        /* Mobile fix */
        @media (max-width: 1023px) {
            .main-content-area {
                margin-left: 0 !important;
                padding: 1rem;
            }
            .content-wrapper {
                padding: 0 0.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include $project_root . 'includes/header.php'; ?>

    <!-- Main Content Area with Sidebar -->
    <div class="flex relative min-h-screen">
        
        <!-- Sidebar -->
        <?php include $project_root . 'includes/admin_sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content-area flex-1 p-3 sm:p-4 md:p-6 lg:p-8 transition-all duration-300 lg:ml-[280px]">
            <div class="content-wrapper">
                <div class="space-y-4 md:space-y-6 lg:space-y-8">
                    
                    <h1 class="wow-title text-2xl md:text-3xl lg:text-4xl"><?php echo translate('admin_dashboard_title', 'Admin & Moderator Dashboard'); ?></h1>

                    <!-- Server Status -->
                    <div class="panel p-4 md:p-6 lg:p-8">
                        <h2 class="section-title text-lg md:text-xl mb-4 md:mb-6 flex items-center gap-3">
                            <i class="fas fa-server text-[#f2cf5b]"></i>
                            <?php echo translate('admin_dashboard_server_status_header', 'Server Status'); ?>
                        </h2>
                        <div class="realm-wrapper">
                            <?php include $project_root . 'includes/realm_status.php'; ?>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
                        <div class="panel p-4 md:p-6 text-center">
                            <div class="stat-number text-xl md:text-2xl lg:text-3xl"><?php echo htmlspecialchars($total_users); ?></div>
                            <div class="text-[#b8c8ff] text-xs md:text-sm font-semibold uppercase tracking-wider mt-1 md:mt-2"><?php echo translate('admin_dashboard_total_website_users', 'Website Users'); ?></div>
                            <i class="fas fa-users text-[#f2cf5b]/30 text-lg md:text-2xl mt-1 md:mt-2"></i>
                        </div>
                        <div class="panel p-4 md:p-6 text-center">
                            <div class="stat-number text-xl md:text-2xl lg:text-3xl"><?php echo htmlspecialchars($total_accounts); ?></div>
                            <div class="text-[#b8c8ff] text-xs md:text-sm font-semibold uppercase tracking-wider mt-1 md:mt-2"><?php echo translate('admin_dashboard_total_ingame_accounts', 'Game Accounts'); ?></div>
                            <i class="fas fa-user-circle text-[#f2cf5b]/30 text-lg md:text-2xl mt-1 md:mt-2"></i>
                        </div>
                        <div class="panel p-4 md:p-6 text-center">
                            <div class="stat-number text-xl md:text-2xl lg:text-3xl"><?php echo htmlspecialchars($total_chars); ?></div>
                            <div class="text-[#b8c8ff] text-xs md:text-sm font-semibold uppercase tracking-wider mt-1 md:mt-2"><?php echo translate('admin_dashboard_total_characters', 'Characters'); ?></div>
                            <i class="fas fa-chess-king text-[#f2cf5b]/30 text-lg md:text-2xl mt-1 md:mt-2"></i>
                        </div>
                        <div class="panel p-4 md:p-6 text-center">
                            <div class="stat-number text-xl md:text-2xl lg:text-3xl text-red-400" style="-webkit-text-fill-color: #ff4d4d;"><?php echo htmlspecialchars($total_bans); ?></div>
                            <div class="text-[#b8c8ff] text-xs md:text-sm font-semibold uppercase tracking-wider mt-1 md:mt-2"><?php echo translate('admin_dashboard_active_bans', 'Active Bans'); ?></div>
                            <i class="fas fa-gavel text-red-400/30 text-lg md:text-2xl mt-1 md:mt-2"></i>
                        </div>
                    </div>

                    <!-- Recent Admins & Moderators -->
                    <div class="panel p-4 md:p-6 lg:p-8">
                        <h2 class="section-title text-lg md:text-xl mb-4 md:mb-6 flex items-center gap-3">
                            <i class="fas fa-user-shield text-[#f2cf5b]"></i>
                            <?php echo translate('admin_dashboard_recent_staff_header', 'Recent Admins & Moderators'); ?>
                        </h2>

                        <form method="GET" action="<?php echo $base_path; ?>admin/dashboard" class="mb-4 md:mb-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
                                <input type="text" name="search_username" class="input-dark" placeholder="<?php echo translate('admin_dashboard_search_username_placeholder', 'Search username...'); ?>" value="<?php echo htmlspecialchars($search_username); ?>">
                                <input type="text" name="search_email" class="input-dark" placeholder="<?php echo translate('admin_dashboard_search_email_placeholder', 'Search email...'); ?>" value="<?php echo htmlspecialchars($search_email); ?>">
                                <select name="role_filter" class="input-dark">
                                    <option value=""><?php echo translate('admin_dashboard_all_staff_roles', 'All Staff Roles'); ?></option>
                                    <option value="moderator" <?php echo $role_filter === 'moderator' ? 'selected' : ''; ?>><?php echo translate('admin_dashboard_role_moderator', 'Moderator'); ?></option>
                                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>><?php echo translate('admin_dashboard_role_admin', 'Admin'); ?></option>
                                </select>
                                <button type="submit" class="btn-gold justify-center">
                                    <i class="fas fa-search"></i> <?php echo translate('admin_dashboard_apply_button', 'Apply'); ?>
                                </button>
                            </div>
                        </form>

                        <div class="overflow-x-auto -mx-4 md:-mx-6 lg:-mx-8 px-4 md:px-6 lg:px-8">
                            <table class="w-full table-dark">
                                <thead>
                                    <tr>
                                        <th><?php echo translate('admin_dashboard_table_username', 'Username'); ?></th>
                                        <th class="hidden md:table-cell"><?php echo translate('admin_dashboard_table_email', 'Email'); ?></th>
                                        <th><?php echo translate('admin_dashboard_table_points', 'Points'); ?></th>
                                        <th class="hidden sm:table-cell"><?php echo translate('admin_dashboard_table_tokens', 'Tokens'); ?></th>
                                        <th><?php echo translate('admin_dashboard_table_role', 'Role'); ?></th>
                                        <th class="hidden lg:table-cell"><?php echo translate('admin_dashboard_table_online', 'Online'); ?></th>
                                        <th class="hidden xl:table-cell"><?php echo translate('admin_dashboard_table_ban_status', 'Ban Status'); ?></th>
                                        <th class="hidden 2xl:table-cell"><?php echo translate('admin_dashboard_table_last_updated', 'Updated'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-gray-400 py-6 md:py-8">
                                                <i class="fas fa-users-slash text-2xl md:text-3xl text-gray-600 block mb-2"></i>
                                                <?php echo translate('admin_dashboard_no_staff_found', 'No admins or moderators found.'); ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td class="font-semibold text-white text-sm md:text-base"><?php echo htmlspecialchars($user['username']); ?></td>
                                                <td class="hidden md:table-cell text-gray-400 text-sm"><?php echo htmlspecialchars($user['email'] ?? translate('admin_dashboard_email_not_set', 'Not set')); ?></td>
                                                <td class="text-sm md:text-base"><?php echo htmlspecialchars($user['points']); ?></td>
                                                <td class="hidden sm:table-cell text-sm md:text-base"><?php echo htmlspecialchars($user['tokens']); ?></td>
                                                <td><span class="status-<?php echo htmlspecialchars($user['role']); ?> text-sm md:text-base"><?php echo ucfirst(translate('admin_dashboard_role_' . $user['role'], ucfirst($user['role']))); ?></span></td>
                                                <td class="hidden lg:table-cell"><?php echo getOnlineStatus($user['online'] ?? 0); ?></td>
                                                <td class="hidden xl:table-cell text-sm"><?php echo getAccountStatus($user['locked'] ?? 0, $user['banInfo'] ?? []); ?></td>
                                                <td class="hidden 2xl:table-cell text-sm text-gray-400"><?php echo $user['last_updated'] ? date('M j, Y', strtotime($user['last_updated'])) : translate('admin_dashboard_never', 'Never'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Recent Bans -->
                    <div class="panel p-4 md:p-6 lg:p-8">
                        <h2 class="section-title text-lg md:text-xl mb-4 md:mb-6 flex items-center gap-3">
                            <i class="fas fa-gavel text-[#f2cf5b]"></i>
                            <?php echo translate('admin_dashboard_recent_bans_header', 'Recent Bans'); ?>
                        </h2>

                        <div class="overflow-x-auto -mx-4 md:-mx-6 lg:-mx-8 px-4 md:px-6 lg:px-8">
                            <table class="w-full table-dark">
                                <thead>
                                    <tr>
                                        <th><?php echo translate('admin_dashboard_table_account_id', 'ID'); ?></th>
                                        <th><?php echo translate('admin_dashboard_table_username', 'Username'); ?></th>
                                        <th class="hidden md:table-cell"><?php echo translate('admin_dashboard_table_ban_reason', 'Reason'); ?></th>
                                        <th class="hidden sm:table-cell"><?php echo translate('admin_dashboard_table_ban_date', 'Ban Date'); ?></th>
                                        <th class="hidden lg:table-cell"><?php echo translate('admin_dashboard_table_unban_date', 'Unban Date'); ?></th>
                                        <th><?php echo translate('admin_dashboard_table_action', 'Action'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($bans_result->num_rows === 0): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-gray-400 py-6 md:py-8">
                                                <i class="fas fa-check-circle text-2xl md:text-3xl text-green-400/30 block mb-2"></i>
                                                <?php echo translate('admin_dashboard_no_bans_found', 'No bans found.'); ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php while ($ban = $bans_result->fetch_assoc()): ?>
                                            <tr>
                                                <td class="text-sm md:text-base"><?php echo htmlspecialchars($ban['id']); ?></td>
                                                <td class="font-semibold text-white text-sm md:text-base"><?php echo htmlspecialchars($ban['username']); ?></td>
                                                <td class="hidden md:table-cell text-gray-400 text-sm"><?php echo htmlspecialchars($ban['banreason'] ?? translate('admin_dashboard_no_reason_provided', 'No reason provided')); ?></td>
                                                <td class="hidden sm:table-cell text-sm text-gray-400"><?php echo $ban['bandate'] ? date('M j, Y', strtotime($ban['bandate'])) : translate('admin_dashboard_na', 'N/A'); ?></td>
                                                <td class="hidden lg:table-cell text-sm text-gray-400"><?php echo $ban['unbandate'] ? date('M j, Y', strtotime($ban['unbandate'])) : translate('admin_dashboard_permanent', 'Permanent'); ?></td>
                                                <td>
                                                    <a href="<?php echo $base_path; ?>admin/users#user-<?php echo $ban['id']; ?>" class="btn-iron text-xs py-1.5 px-2 md:py-2 md:px-3">
                                                        <i class="fas fa-user-cog"></i> <span class="hidden sm:inline"><?php echo translate('admin_dashboard_manage_button', 'Manage'); ?></span>
                                                    </a>
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
        </main>
    </div>

    <?php $site_db->close(); ?>
    <?php $auth_db->close(); ?>
    <?php if (isset($char_db)) $char_db->close(); ?>
</body>
</html>