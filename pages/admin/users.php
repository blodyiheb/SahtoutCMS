<?php
define('ALLOWED_ACCESS', true);

require_once __DIR__ . '/../../includes/paths.php';
require_once $project_root . 'includes/session.php';
require_once $project_root . 'languages/language.php';
require_once $project_root . 'includes/config.settings.php';

$page_class = 'users';
define('DB_AUTH', $db_auth_name);
define('DB_CHAR', $db_char_name);
define('DB_WORLD', $db_world_name);
define('DB_SITE', $db_site_name);

if (!isset($_SESSION['user_id'])) {
    header("Location: {$base_path}login");
    exit;
}

global $site_db, $auth_db, $char_db;

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

if (!in_array($_SESSION['role'], ['admin', 'moderator'])) {
    header("Location: {$base_path}login");
    exit;
}

// Handle search, sort, role filter, ban filter, and gmlevel filter
$search_username = isset($_GET['search_username']) && $_GET['search_username'] !== '' ? trim($_GET['search_username']) : '';
$search_email = isset($_GET['search_email']) && $_GET['search_email'] !== '' ? trim($_GET['search_email']) : '';
$role_filter = isset($_GET['role_filter']) && in_array($_GET['role_filter'], ['player', 'moderator', 'admin']) ? $_GET['role_filter'] : '';
$ban_filter = isset($_GET['ban_filter']) && in_array($_GET['ban_filter'], ['banned']) ? $_GET['ban_filter'] : '';
$gmlevel_filter = isset($_GET['gmlevel_filter']) && in_array($_GET['gmlevel_filter'], ['player', '1', '2', '3']) ? $_GET['gmlevel_filter'] : '';
$sort_id = isset($_GET['sort_id']) && in_array($_GET['sort_id'], ['asc', 'desc']) ? $_GET['sort_id'] : 'asc';

$users_per_page = 10;
$website_page = isset($_GET['website_page']) ? max(1, (int)$_GET['website_page']) : 1;
$ingame_page = isset($_GET['ingame_page']) ? max(1, (int)$_GET['ingame_page']) : 1;
$website_offset = ($website_page - 1) * $users_per_page;
$ingame_offset = ($ingame_page - 1) * $users_per_page;

// Determine active tab - check if tab is set in URL
$active_tab = isset($_GET['tab']) && $_GET['tab'] === 'ingame' ? 'ingame' : 'website';

// Handle form submissions
$update_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $update_message = '<div class="bg-red-900/40 border border-red-500/50 text-red-300 px-4 py-3 rounded-sm mb-6 flex items-center gap-3">' . translate('admin_users_csrf_error', 'CSRF token validation failed.') . '</div>';
        } else {
            $account_id = (int)$_POST['account_id'];
            $points = (int)$_POST['points'];
            $tokens = (int)$_POST['tokens'];
            $role = in_array($_POST['role'], ['player', 'moderator', 'admin']) ? $_POST['role'] : 'player';
            $email = trim($_POST['email']);
            
            $stmt = $site_db->prepare("UPDATE " . DB_SITE . ".user_currencies SET points = ?, tokens = ?, role = ? WHERE account_id = ?");
            $stmt->bind_param("iiss", $points, $tokens, $role, $account_id);
            $success = $stmt->execute();
            $stmt->close();
            
            $stmt = $auth_db->prepare("UPDATE " . DB_AUTH . ".account SET email = ? WHERE id = ?");
            $stmt->bind_param("si", $email, $account_id);
            $success = $success && $stmt->execute();
            $stmt->close();
            
            if ($success) {
                $update_message = '<div class="bg-green-900/40 border border-green-500/50 text-green-300 px-4 py-3 rounded-sm mb-6 flex items-center gap-3">' . translate('admin_users_update_success', 'User updated successfully.') . '</div>';
            } else {
                $update_message = '<div class="bg-red-900/40 border border-red-500/50 text-red-300 px-4 py-3 rounded-sm mb-6 flex items-center gap-3">' . translate('admin_users_update_failed', 'Failed to update user.') . '</div>';
            }
        }
    } elseif ($_POST['action'] === 'manage_account') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $update_message = '<div class="bg-red-900/40 border border-red-500/50 text-red-300 px-4 py-3 rounded-sm mb-6 flex items-center gap-3">' . translate('admin_users_csrf_error', 'CSRF token validation failed.') . '</div>';
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
                $update_message = '<div class="bg-green-900/40 border border-green-500/50 text-green-300 px-4 py-3 rounded-sm mb-6 flex items-center gap-3">' . translate('admin_users_action_success', 'Action completed successfully.') . '</div>';
            } elseif (empty($update_message)) {
                $update_message = '<div class="bg-red-900/40 border border-red-500/50 text-red-300 px-4 py-3 rounded-sm mb-6 flex items-center gap-3">' . translate('admin_users_action_failed', 'Failed to complete action.') . '</div>';
            }
        }
    }
}

// Count total website users
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

// Fetch website users
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

// Count total in-game accounts
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

// Fetch in-game accounts
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

// Fetch ban info
$account_ids = array_keys($accounts);
if (!empty($account_ids)) {
    $placeholders = implode(',', array_fill(0, count($account_ids), '?'));
    $stmt = $auth_db->prepare("SELECT id, bandate, unbandate, banreason FROM " . DB_AUTH . ".account_banned WHERE id IN ($placeholders) AND active = 1");
    $stmt->bind_param(str_repeat('i', count($account_ids)), ...$account_ids);
    $stmt->execute();
    $ban_result = $stmt->get_result();
    while ($ban = $ban_result->fetch_assoc()) {
        $accounts[$ban['id']]['banInfo'] = $ban;
    }
    $stmt->close();
    
    $stmt = $char_db->prepare("SELECT guid, account, name, race, class, gender, level FROM " . DB_CHAR . ".characters WHERE account IN ($placeholders) ORDER BY account, name");
    $stmt->bind_param(str_repeat('i', count($account_ids)), ...$account_ids);
    $stmt->execute();
    $characters_result = $stmt->get_result();
    while ($char = $characters_result->fetch_assoc()) {
        $accounts[$char['account']]['characters'][] = $char;
    }
    $stmt->close();
}

// Helper functions for character icons
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
    return '<img src="' . $base_path . 'img/accountimg/race/' . $gender_folder . '/' . $image . '" alt="Race" class="w-5 h-5 inline-block">';
}

function getClassIcon($class) {
    global $base_path;
    $icons = [
        1 => 'warrior.webp', 2 => 'paladin.webp', 3 => 'hunter.webp', 4 => 'rogue.webp',
        5 => 'priest.webp', 6 => 'deathknight.webp', 7 => 'shaman.webp', 8 => 'mage.webp',
        9 => 'warlock.webp', 11 => 'druid.webp'
    ];
    return '<img src="' . $base_path . 'img/accountimg/class/' . ($icons[$class] ?? 'default.jpg') . '" alt="Class" class="w-5 h-5 inline-block">';
}

function getFactionIcon($race) {
    global $base_path;
    $allianceRaces = [1, 3, 4, 7, 11];
    $faction = in_array($race, $allianceRaces) ? 'alliance.png' : 'horde.png';
    return '<img src="' . $base_path . 'img/accountimg/faction/' . $faction . '" alt="Faction" class="w-4 h-4 inline-block">';
}

function getOnlineStatus($online) {
    return $online 
        ? '<span class="text-green-400 font-semibold"><i class="fas fa-circle text-green-400 text-xs mr-1"></i> ' . translate('admin_users_status_online', 'Online') . '</span>' 
        : '<span class="text-red-400 font-semibold"><i class="fas fa-circle text-red-400 text-xs mr-1"></i> ' . translate('admin_users_status_offline', 'Offline') . '</span>';
}

function getAccountStatus($banInfo) {
    if (!empty($banInfo)) {
        return '<span class="text-red-400 font-semibold"><i class="fas fa-ban mr-1"></i> ' . translate('admin_users_status_banned', 'Banned') . '</span>';
    }
    return '<span class="text-green-400 font-semibold"><i class="fas fa-check-circle mr-1"></i> ' . translate('admin_users_status_active', 'Active') . '</span>';
}

function getGMLevel($gmlevel) {
    if (is_null($gmlevel)) {
        return '<span class="text-gray-400">' . translate('admin_users_gmlevel_player', 'Player') . '</span>';
    }
    return '<span class="text-cyan-400 font-semibold">' . translate('admin_users_gmlevel_prefix', 'GM Level') . ' ' . $gmlevel . '</span>';
}

// Build query string for pagination (only include non-empty values)
function buildQueryString($exclude = []) {
    $params = [];
    $fields = ['search_username', 'search_email', 'role_filter', 'ban_filter', 'gmlevel_filter', 'sort_id', 'tab'];
    foreach ($fields as $field) {
        if (isset($_GET[$field]) && $_GET[$field] !== '' && !in_array($field, $exclude)) {
            $params[$field] = $_GET[$field];
        }
    }
    // Always include tab if set
    if (isset($_GET['tab']) && !in_array('tab', $exclude)) {
        $params['tab'] = $_GET['tab'];
    }
    return http_build_query($params);
}
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
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/tailwind.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>node_modules/@fortawesome/fontawesome-free/css/all.min.css">
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
        .btn-iron-sm { padding: .4rem 1rem; font-size: .75rem; }

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
        .input-dark:disabled { opacity: 0.5; cursor: not-allowed; }

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

        .status-admin { color: #f2cf5b; font-weight: 700; }
        .status-moderator { color: #6a8cff; font-weight: 700; }

        .tab-link {
            color: #b8c8ff;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            text-decoration: none;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
            display: inline-block;
            cursor: pointer;
        }
        .tab-link:hover { color: #f2cf5b; }
        .tab-link.active { color: #f2cf5b; border-bottom-color: #f2cf5b; }

        .modal-backdrop {
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
        }

        .avatar-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }

        .character-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.75rem;
        }
        .character-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            background: rgba(10, 14, 22, 0.5);
            border: 1px solid rgba(201,162,39,.1);
            border-radius: 4px;
        }
        .character-item img {
            width: 24px;
            height: 24px;
            flex-shrink: 0;
        }
        .character-item .char-name {
            font-weight: 600;
            color: #fff;
        }
        .character-item .char-level {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-left: auto;
        }

        /* Main content area - works with sidebar */
        .main-content-area {
            transition: margin-left 0.3s ease;
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
                    
                    <h1 class="wow-title text-2xl md:text-3xl lg:text-4xl"><?php echo translate('admin_users_title', 'User Management'); ?></h1>
                    
                    <?php echo $update_message; ?>

                    <!-- Search Form -->
                    <div class="panel p-4 md:p-6 lg:p-8">
                        <form method="GET" action="<?php echo $base_path; ?>admin/users" id="searchForm">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
                                <input type="text" name="search_username" class="input-dark" placeholder="<?php echo translate('admin_users_search_username_placeholder', 'Search username...'); ?>" value="<?php echo htmlspecialchars($search_username); ?>">
                                <input type="text" name="search_email" class="input-dark" placeholder="<?php echo translate('admin_users_search_email_placeholder', 'Search email...'); ?>" value="<?php echo htmlspecialchars($search_email); ?>">
                                <select name="role_filter" class="input-dark">
                                    <option value="" <?php echo $role_filter === '' ? 'selected' : ''; ?>><?php echo translate('admin_users_all_roles', 'All Roles'); ?></option>
                                    <option value="player" <?php echo $role_filter === 'player' ? 'selected' : ''; ?>><?php echo translate('admin_users_role_player', 'Player'); ?></option>
                                    <option value="moderator" <?php echo $role_filter === 'moderator' ? 'selected' : ''; ?>><?php echo translate('admin_users_role_moderator', 'Moderator'); ?></option>
                                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>><?php echo translate('admin_users_role_admin', 'Admin'); ?></option>
                                </select>
                                <button type="submit" class="btn-gold justify-center">
                                    <i class="fas fa-search"></i> <?php echo translate('admin_users_apply_button', 'Apply'); ?>
                                </button>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4 mt-4">
                                <select name="ban_filter" class="input-dark">
                                    <option value="" <?php echo $ban_filter === '' ? 'selected' : ''; ?>><?php echo translate('admin_users_all_accounts', 'All Accounts'); ?></option>
                                    <option value="banned" <?php echo $ban_filter === 'banned' ? 'selected' : ''; ?>><?php echo translate('admin_users_banned', 'Banned'); ?></option>
                                </select>
                                <select name="gmlevel_filter" class="input-dark">
                                    <option value="" <?php echo $gmlevel_filter === '' ? 'selected' : ''; ?>><?php echo translate('admin_users_all_gm_levels', 'All GM Levels'); ?></option>
                                    <option value="player" <?php echo $gmlevel_filter === 'player' ? 'selected' : ''; ?>><?php echo translate('admin_users_gmlevel_player', 'Player'); ?></option>
                                    <option value="1" <?php echo $gmlevel_filter === '1' ? 'selected' : ''; ?>><?php echo translate('admin_users_gmlevel_1', 'GM Level 1'); ?></option>
                                    <option value="2" <?php echo $gmlevel_filter === '2' ? 'selected' : ''; ?>><?php echo translate('admin_users_gmlevel_2', 'GM Level 2'); ?></option>
                                    <option value="3" <?php echo $gmlevel_filter === '3' ? 'selected' : ''; ?>><?php echo translate('admin_users_gmlevel_3', 'GM Level 3'); ?></option>
                                </select>
                                <select name="sort_id" class="input-dark">
                                    <option value="asc" <?php echo $sort_id === 'asc' ? 'selected' : ''; ?>><?php echo translate('admin_users_sort_id_asc', 'Sort: Ascending'); ?></option>
                                    <option value="desc" <?php echo $sort_id === 'desc' ? 'selected' : ''; ?>><?php echo translate('admin_users_sort_id_desc', 'Sort: Descending'); ?></option>
                                </select>
                            </div>
                            <input type="hidden" name="tab" id="tabInput" value="<?php echo $active_tab; ?>">
                        </form>
                    </div>

                    <!-- Tabs -->
                    <div class="border-b border-[rgba(201,162,39,.2)]">
                        <nav class="flex gap-0 overflow-x-auto">
                            <a class="tab-link whitespace-nowrap <?php echo $active_tab === 'website' ? 'active' : ''; ?>" href="<?php echo $base_path; ?>admin/users?tab=website<?php echo $search_username ? '&search_username=' . urlencode($search_username) : ''; ?><?php echo $search_email ? '&search_email=' . urlencode($search_email) : ''; ?><?php echo $role_filter ? '&role_filter=' . urlencode($role_filter) : ''; ?><?php echo $ban_filter ? '&ban_filter=' . urlencode($ban_filter) : ''; ?><?php echo $gmlevel_filter ? '&gmlevel_filter=' . urlencode($gmlevel_filter) : ''; ?><?php echo $sort_id ? '&sort_id=' . urlencode($sort_id) : ''; ?>">
                                <i class="fas fa-globe mr-2"></i><?php echo translate('admin_users_tab_website', 'Website Users'); ?>
                            </a>
                            <a class="tab-link whitespace-nowrap <?php echo $active_tab === 'ingame' ? 'active' : ''; ?>" href="<?php echo $base_path; ?>admin/users?tab=ingame<?php echo $search_username ? '&search_username=' . urlencode($search_username) : ''; ?><?php echo $search_email ? '&search_email=' . urlencode($search_email) : ''; ?><?php echo $role_filter ? '&role_filter=' . urlencode($role_filter) : ''; ?><?php echo $ban_filter ? '&ban_filter=' . urlencode($ban_filter) : ''; ?><?php echo $gmlevel_filter ? '&gmlevel_filter=' . urlencode($gmlevel_filter) : ''; ?><?php echo $sort_id ? '&sort_id=' . urlencode($sort_id) : ''; ?>">
                                <i class="fas fa-gamepad mr-2"></i><?php echo translate('admin_users_tab_ingame', 'In-Game Accounts'); ?>
                            </a>
                        </nav>
                    </div>

                    <!-- Website Tab -->
                    <div id="website-tab" class="tab-content <?php echo $active_tab === 'website' ? 'active' : ''; ?>">
                        <div class="panel p-4 md:p-6 lg:p-8">
                            <h2 class="section-title text-lg md:text-xl mb-4 md:mb-6 flex items-center gap-3">
                                <i class="fas fa-users text-[#f2cf5b]"></i>
                                <?php echo translate('admin_users_website_users_header', 'Website Users'); ?>
                            </h2>
                            <div class="overflow-x-auto -mx-4 md:-mx-6 lg:-mx-8 px-4 md:px-6 lg:px-8">
                                <table class="w-full table-dark">
                                    <thead>
                                        <tr>
                                            <th><?php echo translate('admin_users_table_account_id', 'ID'); ?></th>
                                            <th><?php echo translate('admin_users_table_username', 'Username'); ?></th>
                                            <th class="hidden md:table-cell"><?php echo translate('admin_users_table_email', 'Email'); ?></th>
                                            <th><?php echo translate('admin_users_table_avatar', 'Avatar'); ?></th>
                                            <th><?php echo translate('admin_users_table_points', 'Points'); ?></th>
                                            <th class="hidden sm:table-cell"><?php echo translate('admin_users_table_tokens', 'Tokens'); ?></th>
                                            <th><?php echo translate('admin_users_table_role', 'Role'); ?></th>
                                            <th class="hidden lg:table-cell"><?php echo translate('admin_users_table_last_updated', 'Updated'); ?></th>
                                            <th><?php echo translate('admin_users_table_action', 'Action'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($users_result->num_rows === 0): ?>
                                            <tr>
                                                <td colspan="9" class="text-center text-gray-400 py-6 md:py-8">
                                                    <i class="fas fa-users-slash text-2xl md:text-3xl text-gray-600 block mb-2"></i>
                                                    <?php echo translate('admin_users_no_users_found', 'No users found.'); ?>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php while ($user = $users_result->fetch_assoc()): ?>
                                                <tr id="user-<?php echo $user['account_id']; ?>">
                                                    <td class="text-sm md:text-base"><?php echo htmlspecialchars($user['account_id']); ?></td>
                                                    <td class="font-semibold text-white text-sm md:text-base"><?php echo htmlspecialchars($user['username']); ?></td>
                                                    <td class="hidden md:table-cell text-gray-400 text-sm"><?php echo htmlspecialchars($user['email'] ?? translate('admin_users_email_not_set', 'Not set')); ?></td>
                                                    <td>
                                                        <img src="<?php echo $base_path . 'img/accountimg/profile_pics/' . (!empty($user['avatar']) ? htmlspecialchars($user['avatar']) : 'user.jpg'); ?>" class="avatar-img" alt="Avatar">
                                                    </td>
                                                    <td class="text-sm md:text-base"><?php echo htmlspecialchars($user['points']); ?></td>
                                                    <td class="hidden sm:table-cell text-sm md:text-base"><?php echo htmlspecialchars($user['tokens']); ?></td>
                                                    <td><span class="status-<?php echo htmlspecialchars($user['role']); ?> text-sm md:text-base"><?php echo ucfirst(translate('admin_users_role_' . $user['role'], ucfirst($user['role']))); ?></span></td>
                                                    <td class="hidden lg:table-cell text-sm text-gray-400"><?php echo $user['last_updated'] ? date('M j, Y', strtotime($user['last_updated'])) : translate('admin_users_never', 'Never'); ?></td>
                                                    <td>
                                                        <button class="btn-iron btn-iron-sm" onclick="openModal('editModal-<?php echo $user['account_id']; ?>')">
                                                            <i class="fas fa-edit"></i> <?php echo translate('admin_users_edit_button', 'Edit'); ?>
                                                        </button>
                                                    </td>
                                                </tr>

                                                <!-- Edit Modal -->
                                                <div id="editModal-<?php echo $user['account_id']; ?>" class="fixed inset-0 z-50 hidden items-center justify-center p-4 modal-backdrop">
                                                    <div class="panel w-full max-w-lg p-6 md:p-8 relative max-h-[90vh] overflow-y-auto">
                                                        <button class="absolute top-4 right-4 text-gray-400 hover:text-white text-2xl" onclick="closeModal('editModal-<?php echo $user['account_id']; ?>')">&times;</button>
                                                        <h3 class="wow-title text-2xl mb-6"><?php echo translate('admin_users_edit_modal_title', 'Edit User: ') . htmlspecialchars($user['username']); ?></h3>
                                                        <form method="POST" action="<?php echo $base_path; ?>admin/users">
                                                            <input type="hidden" name="action" value="update">
                                                            <input type="hidden" name="account_id" value="<?php echo $user['account_id']; ?>">
                                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                            
                                                            <div class="mb-4">
                                                                <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_users_label_username', 'Username'); ?></label>
                                                                <input type="text" class="input-dark" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                                            </div>
                                                            <div class="mb-4">
                                                                <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_users_label_email', 'Email'); ?></label>
                                                                <input type="email" name="email" class="input-dark" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                                            </div>
                                                            <div class="mb-4">
                                                                <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_users_label_points', 'Points'); ?></label>
                                                                <input type="number" name="points" class="input-dark" value="<?php echo htmlspecialchars($user['points']); ?>" required>
                                                            </div>
                                                            <div class="mb-4">
                                                                <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_users_label_tokens', 'Tokens'); ?></label>
                                                                <input type="number" name="tokens" class="input-dark" value="<?php echo htmlspecialchars($user['tokens']); ?>" required>
                                                            </div>
                                                            <div class="mb-4">
                                                                <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_users_label_role', 'Role'); ?></label>
                                                                <select name="role" class="input-dark">
                                                                    <option value="player" <?php echo $user['role'] === 'player' ? 'selected' : ''; ?>><?php echo translate('admin_users_role_player', 'Player'); ?></option>
                                                                    <option value="moderator" <?php echo $user['role'] === 'moderator' ? 'selected' : ''; ?>><?php echo translate('admin_users_role_moderator', 'Moderator'); ?></option>
                                                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>><?php echo translate('admin_users_role_admin', 'Admin'); ?></option>
                                                                </select>
                                                            </div>
                                                            <div class="flex justify-end gap-4 pt-4">
                                                                <button type="button" class="btn-iron" onclick="closeModal('editModal-<?php echo $user['account_id']; ?>')"><?php echo translate('admin_users_cancel_button', 'Cancel'); ?></button>
                                                                <button type="submit" class="btn-gold"><?php echo translate('admin_users_save_button', 'Save'); ?></button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                        <?php $users_result->free(); ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Pagination -->
                            <?php if ($total_website_pages > 1): ?>
                                <nav class="flex justify-center gap-2 mt-6 md:mt-8 flex-wrap">
                                    <?php if ($website_page > 1): ?>
                                        <a href="<?php echo $base_path; ?>admin/users?tab=website&website_page=<?php echo $website_page - 1; ?><?php echo $search_username ? '&search_username=' . urlencode($search_username) : ''; ?><?php echo $search_email ? '&search_email=' . urlencode($search_email) : ''; ?><?php echo $role_filter ? '&role_filter=' . urlencode($role_filter) : ''; ?><?php echo $ban_filter ? '&ban_filter=' . urlencode($ban_filter) : ''; ?><?php echo $gmlevel_filter ? '&gmlevel_filter=' . urlencode($gmlevel_filter) : ''; ?><?php echo $sort_id ? '&sort_id=' . urlencode($sort_id) : ''; ?>" class="btn-iron btn-iron-sm">
                                            <i class="fas fa-chevron-left"></i> <?php echo translate('admin_users_previous', 'Previous'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php for ($i = 1; $i <= $total_website_pages; $i++): ?>
                                        <?php if ($i === $website_page): ?>
                                            <span class="btn-gold btn-iron-sm cursor-default"><?php echo $i; ?></span>
                                        <?php else: ?>
                                            <a href="<?php echo $base_path; ?>admin/users?tab=website&website_page=<?php echo $i; ?><?php echo $search_username ? '&search_username=' . urlencode($search_username) : ''; ?><?php echo $search_email ? '&search_email=' . urlencode($search_email) : ''; ?><?php echo $role_filter ? '&role_filter=' . urlencode($role_filter) : ''; ?><?php echo $ban_filter ? '&ban_filter=' . urlencode($ban_filter) : ''; ?><?php echo $gmlevel_filter ? '&gmlevel_filter=' . urlencode($gmlevel_filter) : ''; ?><?php echo $sort_id ? '&sort_id=' . urlencode($sort_id) : ''; ?>" class="btn-iron btn-iron-sm"><?php echo $i; ?></a>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    <?php if ($website_page < $total_website_pages): ?>
                                        <a href="<?php echo $base_path; ?>admin/users?tab=website&website_page=<?php echo $website_page + 1; ?><?php echo $search_username ? '&search_username=' . urlencode($search_username) : ''; ?><?php echo $search_email ? '&search_email=' . urlencode($search_email) : ''; ?><?php echo $role_filter ? '&role_filter=' . urlencode($role_filter) : ''; ?><?php echo $ban_filter ? '&ban_filter=' . urlencode($ban_filter) : ''; ?><?php echo $gmlevel_filter ? '&gmlevel_filter=' . urlencode($gmlevel_filter) : ''; ?><?php echo $sort_id ? '&sort_id=' . urlencode($sort_id) : ''; ?>" class="btn-iron btn-iron-sm">
                                            <?php echo translate('admin_users_next', 'Next'); ?> <i class="fas fa-chevron-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </nav>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- In-Game Tab -->
                    <div id="ingame-tab" class="tab-content <?php echo $active_tab === 'ingame' ? 'active' : ''; ?>">
                        <div class="panel p-4 md:p-6 lg:p-8">
                            <h2 class="section-title text-lg md:text-xl mb-4 md:mb-6 flex items-center gap-3">
                                <i class="fas fa-gamepad text-[#f2cf5b]"></i>
                                <?php echo translate('admin_users_ingame_accounts_header', 'In-Game Accounts'); ?>
                            </h2>
                            <div class="overflow-x-auto -mx-4 md:-mx-6 lg:-mx-8 px-4 md:px-6 lg:px-8">
                                <table class="w-full table-dark">
                                    <thead>
                                        <tr>
                                            <th><?php echo translate('admin_users_table_account_id', 'ID'); ?></th>
                                            <th><?php echo translate('admin_users_table_username', 'Username'); ?></th>
                                            <th class="hidden md:table-cell"><?php echo translate('admin_users_table_email', 'Email'); ?></th>
                                            <th class="hidden lg:table-cell"><?php echo translate('admin_users_table_join_date', 'Joined'); ?></th>
                                            <th class="hidden xl:table-cell"><?php echo translate('admin_users_table_last_login', 'Last Login'); ?></th>
                                            <th><?php echo translate('admin_users_table_online', 'Online'); ?></th>
                                            <th><?php echo translate('admin_users_table_ban_status', 'Status'); ?></th>
                                            <th><?php echo translate('admin_users_table_gm_level', 'GM Level'); ?></th>
                                            <th><?php echo translate('admin_users_table_characters', 'Chars'); ?></th>
                                            <th><?php echo translate('admin_users_table_action', 'Action'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($accounts)): ?>
                                            <tr>
                                                <td colspan="10" class="text-center text-gray-400 py-6 md:py-8">
                                                    <i class="fas fa-database text-2xl md:text-3xl text-gray-600 block mb-2"></i>
                                                    <?php echo translate('admin_users_no_accounts_found', 'No accounts found.'); ?>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($accounts as $account): ?>
                                                <tr>
                                                    <td class="text-sm md:text-base"><?php echo htmlspecialchars($account['id']); ?></td>
                                                    <td class="font-semibold text-white text-sm md:text-base"><?php echo htmlspecialchars($account['username']); ?></td>
                                                    <td class="hidden md:table-cell text-gray-400 text-sm"><?php echo htmlspecialchars($account['email'] ?? translate('admin_users_email_not_set', 'Not set')); ?></td>
                                                    <td class="hidden lg:table-cell text-sm text-gray-400"><?php echo $account['joindate'] ? date('M j, Y', strtotime($account['joindate'])) : translate('admin_users_na', 'N/A'); ?></td>
                                                    <td class="hidden xl:table-cell text-sm text-gray-400"><?php echo $account['last_login'] ? date('M j, Y', strtotime($account['last_login'])) : translate('admin_users_never', 'Never'); ?></td>
                                                    <td><?php echo getOnlineStatus($account['online']); ?></td>
                                                    <td><?php echo getAccountStatus($account['banInfo'] ?? []); ?></td>
                                                    <td><?php echo getGMLevel($account['gmlevel'] ?? null); ?></td>
                                                    <td>
                                                        <?php if (!empty($account['characters'])): ?>
                                                            <button class="btn-iron btn-iron-sm" onclick="openModal('charsModal-<?php echo $account['id']; ?>')">
                                                                <i class="fas fa-users"></i> <?php echo count($account['characters']); ?>
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="text-gray-500 text-sm">—</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <button class="btn-iron btn-iron-sm" onclick="openModal('manageModal-<?php echo $account['id']; ?>')">
                                                            <i class="fas fa-cog"></i> <?php echo translate('admin_users_manage_button', 'Manage'); ?>
                                                        </button>
                                                    </td>
                                                </tr>

                                                <!-- Characters Modal -->
                                                <div id="charsModal-<?php echo $account['id']; ?>" class="fixed inset-0 z-50 hidden items-center justify-center p-4 modal-backdrop">
                                                    <div class="panel w-full max-w-2xl p-6 md:p-8 relative max-h-[90vh] overflow-y-auto">
                                                        <button class="absolute top-4 right-4 text-gray-400 hover:text-white text-2xl" onclick="closeModal('charsModal-<?php echo $account['id']; ?>')">&times;</button>
                                                        <h3 class="wow-title text-2xl mb-6 flex items-center gap-3">
                                                            <i class="fas fa-users text-[#f2cf5b]"></i>
                                                            <?php echo translate('admin_users_characters_title', 'Characters for') . ' ' . htmlspecialchars($account['username']); ?>
                                                            <span class="text-sm text-gray-400">(<?php echo count($account['characters']); ?> <?php echo translate('admin_users_total', 'total'); ?>)</span>
                                                        </h3>
                                                        <div class="character-grid">
                                                            <?php foreach ($account['characters'] as $char): ?>
                                                                <div class="character-item">
                                                                    <?php echo getFactionIcon($char['race']); ?>
                                                                    <?php echo getRaceIcon($char['race'], $char['gender']); ?>
                                                                    <?php echo getClassIcon($char['class']); ?>
                                                                    <span class="char-name"><?php echo htmlspecialchars($char['name']); ?></span>
                                                                    <span class="char-level">Lv <?php echo $char['level']; ?></span>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                        <div class="flex justify-end pt-4 mt-4 border-t border-[rgba(201,162,39,.1)]">
                                                            <button class="btn-iron" onclick="closeModal('charsModal-<?php echo $account['id']; ?>')"><?php echo translate('admin_users_close_button', 'Close'); ?></button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Manage Modal -->
                                                <div id="manageModal-<?php echo $account['id']; ?>" class="fixed inset-0 z-50 hidden items-center justify-center p-4 modal-backdrop">
                                                    <div class="panel w-full max-w-lg p-6 md:p-8 relative max-h-[90vh] overflow-y-auto">
                                                        <button class="absolute top-4 right-4 text-gray-400 hover:text-white text-2xl" onclick="closeModal('manageModal-<?php echo $account['id']; ?>')">&times;</button>
                                                        <h3 class="wow-title text-2xl mb-6"><?php echo translate('admin_users_manage_modal_title', 'Manage Account: ') . htmlspecialchars($account['username']); ?></h3>
                                                        <form method="POST" action="<?php echo $base_path; ?>admin/users">
                                                            <input type="hidden" name="action" value="manage_account">
                                                            <input type="hidden" name="account_id" value="<?php echo $account['id']; ?>">
                                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                            
                                                            <div class="mb-4">
                                                                <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_users_label_action', 'Action'); ?></label>
                                                                <select name="ban_action" class="input-dark" id="banAction-<?php echo $account['id']; ?>" onchange="toggleBanFields('<?php echo $account['id']; ?>')">
                                                                    <option value="change_gm_role"><?php echo translate('admin_users_action_change_gm_role', 'Change GM Role'); ?></option>
                                                                    <option value="ban"><?php echo translate('admin_users_action_ban', 'Ban Account'); ?></option>
                                                                    <?php if (!empty($account['banInfo'])): ?>
                                                                        <option value="unban"><?php echo translate('admin_users_action_unban', 'Unban Account'); ?></option>
                                                                    <?php endif; ?>
                                                                </select>
                                                            </div>
                                                            
                                                            <div id="banFields-<?php echo $account['id']; ?>" class="hidden">
                                                                <div class="mb-4">
                                                                    <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_users_label_ban_reason', 'Ban Reason'); ?></label>
                                                                    <input type="text" name="ban_reason" class="input-dark" placeholder="<?php echo translate('admin_users_ban_reason_placeholder', 'Enter ban reason'); ?>">
                                                                </div>
                                                                <div class="mb-4">
                                                                    <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_users_label_ban_duration', 'Ban Duration'); ?></label>
                                                                    <select name="ban_duration" class="input-dark">
                                                                        <option value="3600"><?php echo translate('admin_users_ban_duration_1hour', '1 Hour'); ?></option>
                                                                        <option value="86400"><?php echo translate('admin_users_ban_duration_1day', '1 Day'); ?></option>
                                                                        <option value="604800"><?php echo translate('admin_users_ban_duration_7days', '7 Days'); ?></option>
                                                                        <option value="2592000"><?php echo translate('admin_users_ban_duration_30days', '30 Days'); ?></option>
                                                                        <option value="permanent"><?php echo translate('admin_users_ban_duration_permanent', 'Permanent'); ?></option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="mb-4">
                                                                <label class="block text-sm font-semibold text-[#f2cf5b] mb-2 font-['Cinzel'] tracking-wide"><?php echo translate('admin_users_label_gm_level', 'GM Level'); ?></label>
                                                                <select name="gmlevel" class="input-dark">
                                                                    <option value="player" <?php echo is_null($account['gmlevel']) ? 'selected' : ''; ?>><?php echo translate('admin_users_gmlevel_player', 'Player'); ?></option>
                                                                    <option value="1" <?php echo isset($account['gmlevel']) && $account['gmlevel'] === 1 ? 'selected' : ''; ?>><?php echo translate('admin_users_gmlevel_1', 'GM Level 1'); ?></option>
                                                                    <option value="2" <?php echo isset($account['gmlevel']) && $account['gmlevel'] === 2 ? 'selected' : ''; ?>><?php echo translate('admin_users_gmlevel_2', 'GM Level 2'); ?></option>
                                                                    <option value="3" <?php echo isset($account['gmlevel']) && $account['gmlevel'] === 3 ? 'selected' : ''; ?>><?php echo translate('admin_users_gmlevel_3', 'GM Level 3'); ?></option>
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="flex justify-end gap-4 pt-4">
                                                                <button type="button" class="btn-iron" onclick="closeModal('manageModal-<?php echo $account['id']; ?>')"><?php echo translate('admin_users_cancel_button', 'Cancel'); ?></button>
                                                                <button type="submit" class="btn-gold"><?php echo translate('admin_users_apply_button', 'Apply'); ?></button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Pagination -->
                            <?php if ($total_ingame_pages > 1): ?>
                                <nav class="flex justify-center gap-2 mt-6 md:mt-8 flex-wrap">
                                    <?php if ($ingame_page > 1): ?>
                                        <a href="<?php echo $base_path; ?>admin/users?tab=ingame&ingame_page=<?php echo $ingame_page - 1; ?><?php echo $search_username ? '&search_username=' . urlencode($search_username) : ''; ?><?php echo $search_email ? '&search_email=' . urlencode($search_email) : ''; ?><?php echo $role_filter ? '&role_filter=' . urlencode($role_filter) : ''; ?><?php echo $ban_filter ? '&ban_filter=' . urlencode($ban_filter) : ''; ?><?php echo $gmlevel_filter ? '&gmlevel_filter=' . urlencode($gmlevel_filter) : ''; ?><?php echo $sort_id ? '&sort_id=' . urlencode($sort_id) : ''; ?>" class="btn-iron btn-iron-sm">
                                            <i class="fas fa-chevron-left"></i> <?php echo translate('admin_users_previous', 'Previous'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php for ($i = 1; $i <= $total_ingame_pages; $i++): ?>
                                        <?php if ($i === $ingame_page): ?>
                                            <span class="btn-gold btn-iron-sm cursor-default"><?php echo $i; ?></span>
                                        <?php else: ?>
                                            <a href="<?php echo $base_path; ?>admin/users?tab=ingame&ingame_page=<?php echo $i; ?><?php echo $search_username ? '&search_username=' . urlencode($search_username) : ''; ?><?php echo $search_email ? '&search_email=' . urlencode($search_email) : ''; ?><?php echo $role_filter ? '&role_filter=' . urlencode($role_filter) : ''; ?><?php echo $ban_filter ? '&ban_filter=' . urlencode($ban_filter) : ''; ?><?php echo $gmlevel_filter ? '&gmlevel_filter=' . urlencode($gmlevel_filter) : ''; ?><?php echo $sort_id ? '&sort_id=' . urlencode($sort_id) : ''; ?>" class="btn-iron btn-iron-sm"><?php echo $i; ?></a>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    <?php if ($ingame_page < $total_ingame_pages): ?>
                                        <a href="<?php echo $base_path; ?>admin/users?tab=ingame&ingame_page=<?php echo $ingame_page + 1; ?><?php echo $search_username ? '&search_username=' . urlencode($search_username) : ''; ?><?php echo $search_email ? '&search_email=' . urlencode($search_email) : ''; ?><?php echo $role_filter ? '&role_filter=' . urlencode($role_filter) : ''; ?><?php echo $ban_filter ? '&ban_filter=' . urlencode($ban_filter) : ''; ?><?php echo $gmlevel_filter ? '&gmlevel_filter=' . urlencode($gmlevel_filter) : ''; ?><?php echo $sort_id ? '&sort_id=' . urlencode($sort_id) : ''; ?>" class="btn-iron btn-iron-sm">
                                            <?php echo translate('admin_users_next', 'Next'); ?> <i class="fas fa-chevron-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
            document.getElementById(id).classList.add('flex');
        }
        
        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
            document.getElementById(id).classList.remove('flex');
        }

        function toggleBanFields(id) {
            const action = document.getElementById('banAction-' + id);
            const banFields = document.getElementById('banFields-' + id);
            if (action && banFields) {
                banFields.style.display = action.value === 'ban' ? 'block' : 'none';
            }
        }

        // Initialize ban fields on page load
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('[id^="banAction-"]').forEach(function(el) {
                const id = el.id.replace('banAction-', '');
                toggleBanFields(id);
            });
        });
    </script>
</body>
</html>
<?php
$site_db->close();
$auth_db->close();
$char_db->close();
?>