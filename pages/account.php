<?php
ob_start(); // Start output buffering to catch any unexpected output
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../includes/paths.php'; // Include paths.php
require_once $project_root . 'includes/session.php';
require_once $project_root . 'includes/srp6.php';
require_once $project_root . 'languages/language.php'; // Include language file for translations

// Early session validation
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: {$base_path}login?error=invalid_session");
    exit();
}

// Initialize variables
$accountInfo = [];
$banInfo = [];
$message = '';
$error = '';
$characters = [];
$activityLog = [];
$teleport_cooldowns = [];
$currencies = ['points' => 0, 'tokens' => 0, 'avatar' => NULL];
$available_avatars = [];
$gmlevel = $_SESSION['gmlevel'] ?? 0;
$role = $_SESSION['role'] ?? 'player';
$debug_errors = $_SESSION['debug_errors'] ?? [];

// Retrieve and clear session messages
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
if (isset($_SESSION['debug_errors'])) {
    $debug_errors = $_SESSION['debug_errors'];
    unset($_SESSION['debug_errors']);
}

// Handle form submissions before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($auth_db->connect_error || $char_db->connect_error || $site_db->connect_error) {
        $_SESSION['error'] = translate('error_database_connection', 'Database connection failed');
        header("Location: {$base_path}account");
        exit();
    }

    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = translate('error_invalid_form_submission', 'Invalid form submission');
        header("Location: {$base_path}account");
        exit();
    }

    // Handle email change
    if (isset($_POST['change_email'])) {
        $new_email = filter_var($_POST['new_email'], FILTER_SANITIZE_EMAIL);
        $current_password = $_POST['current_password'];
        
        try {
            if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception(translate('error_invalid_email_format', 'Invalid email format'));
            }

            // Fetch current email to check if it's the same
            /** @var \mysqli_stmt|false $stmt_current */
            $stmt_current = $auth_db->prepare("SELECT email FROM account WHERE id = ?");
            $stmt_current->bind_param('i', $_SESSION['user_id']);
            $stmt_current->execute();
            $result_current = $stmt_current->get_result();
            $current_email = $result_current->num_rows === 1 ? $result_current->fetch_assoc()['email'] : '';
            $stmt_current->close();

            // If new email is the same as current, allow update (no-op)
            if ($new_email !== $current_email) {
                // Check if email is used by another account
                /** @var \mysqli_stmt|false $stmt_check_email */
                $stmt_check_email = $auth_db->prepare("SELECT id FROM account WHERE email = ? AND id != ?");
                $stmt_check_email->bind_param('si', $new_email, $_SESSION['user_id']);
                $stmt_check_email->execute();
                $result = $stmt_check_email->get_result();
                if ($result->num_rows > 0) {
                    throw new Exception(translate('error_email_in_use', 'Email already in use by another account'));
                }
                $stmt_check_email->close();
            }

            // Verify current password
            /** @var \mysqli_stmt|false $stmt_verify */
            $stmt_verify = $auth_db->prepare("SELECT salt, verifier FROM account WHERE id = ?");
            $stmt_verify->bind_param('i', $_SESSION['user_id']);
            $stmt_verify->execute();
            $result = $stmt_verify->get_result();
            
            if ($result->num_rows !== 1) {
                throw new Exception(translate('error_account_not_found', 'Account not found'));
            }
            
            $row = $result->fetch_assoc();
            if (!SRP6::VerifyPassword($_SESSION['username'], $current_password, $row['salt'], $row['verifier'])) {
                throw new Exception(translate('error_incorrect_password', 'Incorrect current password'));
            }
            $stmt_verify->close();

            // Update email
            /** @var \mysqli_stmt|false $stmt_update */
            $stmt_update = $auth_db->prepare("UPDATE account SET email = ?, reg_mail = ? WHERE id = ?");
            $stmt_update->bind_param('ssi', $new_email, $new_email, $_SESSION['user_id']);
            if (!$stmt_update->execute()) {
                throw new Exception(translate('error_updating_email', 'Error updating email'));
            }
            $stmt_update->close();

            // Log action
            /** @var \mysqli_stmt|false $stmt_log */
            $stmt_log = $site_db->prepare("INSERT INTO website_activity_log (account_id, character_name, action, timestamp, details) VALUES (?, NULL, ?, UNIX_TIMESTAMP(), ?)");
            $action = translate('action_email_changed', 'Email Changed');
            $stmt_log->bind_param('iss', $_SESSION['user_id'], $action, $new_email);
            $stmt_log->execute();
            $stmt_log->close();

            $_SESSION['message'] = translate('message_email_updated', 'Email updated successfully!');
            header("Location: {$base_path}account");
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header("Location: {$base_path}account");
            exit();
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        try {
            if ($new_password !== $confirm_password) {
                throw new Exception(translate('error_passwords_dont_match', 'New passwords don\'t match'));
            }
            if (strlen($new_password) < 6) {
                throw new Exception(translate('error_password_too_short', 'Password must be at least 6 characters'));
            }

            /** @var \mysqli_stmt|false $stmt */
            $stmt = $auth_db->prepare("SELECT salt, verifier FROM account WHERE id = ?");
            $stmt->bind_param('i', $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows !== 1) {
                throw new Exception(translate('error_account_not_found', 'Account not found'));
            }
            
            $row = $result->fetch_assoc();
            if (!SRP6::VerifyPassword($_SESSION['username'], $current_password, $row['salt'], $row['verifier'])) {
                throw new Exception(translate('error_incorrect_password', 'Current password is incorrect'));
            }
            $stmt->close();

            $new_salt = SRP6::GenerateSalt();
            $new_verifier = SRP6::CalculateVerifier($_SESSION['username'], $new_password, $new_salt);
            
            /** @var \mysqli_stmt|false $update */
            $update = $auth_db->prepare("UPDATE account SET salt = ?, verifier = ? WHERE id = ?");
            $update->bind_param('ssi', $new_salt, $new_verifier, $_SESSION['user_id']);
            if (!$update->execute()) {
                throw new Exception(translate('error_updating_password', 'Error updating password'));
            }
            $update->close();

            // Log action
            /** @var \mysqli_stmt|false $stmt_log */
            $stmt_log = $site_db->prepare("INSERT INTO website_activity_log (account_id, character_name, action, timestamp) VALUES (?, NULL, ?, UNIX_TIMESTAMP())");
            $action = translate('action_password_changed', 'Password Changed');
            $stmt_log->bind_param('is', $_SESSION['user_id'], $action);
            $stmt_log->execute();
            $stmt_log->close();

            $_SESSION['message'] = translate('message_password_changed', 'Password changed successfully!');
            header("Location: {$base_path}account");
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header("Location: {$base_path}account");
            exit();
        }
    }

    // Handle character teleport
    if (isset($_POST['teleport_character'])) {
        $guid = filter_var($_POST['guid'], FILTER_VALIDATE_INT);
        $destination = filter_var($_POST['destination']);
        
        try {
            if (!$guid) {
                throw new Exception(translate('error_invalid_character_id', 'Invalid character ID'));
            }

            // Prevent rapid resubmissions
            if (isset($_SESSION['last_teleport_attempt']) && (time() - $_SESSION['last_teleport_attempt']) < 5) {
                throw new Exception(translate('error_rapid_submission', 'Please wait a few seconds before trying again'));
            }
            $_SESSION['last_teleport_attempt'] = time();

            // Check session-based cooldown
            if (isset($_SESSION['teleport_cooldowns'][$guid]) && ($_SESSION['teleport_cooldowns'][$guid] + 900) > time()) {
                $minutes = ceil(($_SESSION['teleport_cooldowns'][$guid] + 900 - time()) / 60);
                throw new Exception(sprintf(translate('error_teleport_cooldown', 'Teleport on cooldown. Please wait %s minute%s'), $minutes, $minutes > 1 ? 's' : ''));
            }

            // Fetch character name and online status
            /** @var \mysqli_stmt|false $stmt_check */
            $stmt_check = $char_db->prepare("SELECT online, name FROM characters WHERE guid = ? AND account = ?");
            $stmt_check->bind_param('ii', $guid, $_SESSION['user_id']);
            $stmt_check->execute();
            $result = $stmt_check->get_result();
            if ($result->num_rows !== 1) {
                throw new Exception(translate('error_character_not_found', 'Character not found'));
            }
            
            $char = $result->fetch_assoc();
            $character_name = $char['name'];
            if ($char['online'] == 1) {
                throw new Exception(translate('error_character_online', 'Character must be offline to teleport'));
            }
            $stmt_check->close();

            // Fetch teleport cooldown from database
            /** @var \mysqli_stmt|false $stmt_cooldown */
            $stmt_cooldown = $site_db->prepare("SELECT teleport_timestamp FROM character_teleport_log WHERE character_guid = ?");
            $stmt_cooldown->bind_param('i', $guid);
            $stmt_cooldown->execute();
            $result_cooldown = $stmt_cooldown->get_result();
            $last_teleport = $result_cooldown->num_rows > 0 ? $result_cooldown->fetch_assoc()['teleport_timestamp'] : 0;
            $stmt_cooldown->close();

            // Validate timestamp
            if (!is_numeric($last_teleport) || $last_teleport < 0) {
                $last_teleport = 0;
            }

            $current_time = time();
            $cooldown_duration = 900; // 15 minutes in seconds
            $cooldown_remaining = ($last_teleport + $cooldown_duration) - $current_time;
            if ($cooldown_remaining > 0) {
                $minutes = ceil($cooldown_remaining / 60);
                throw new Exception(sprintf(translate('error_teleport_cooldown', 'Teleport on cooldown. Please wait %s minute%s'), $minutes, $minutes > 1 ? 's' : ''));
            }

            $teleportData = [
                'shattrath' => ['map' => 530, 'x' => -1832.9, 'y' => 5370.1, 'z' => -12.4, 'o' => 2.0],
                'dalaran' => ['map' => 571, 'x' => 5804.2, 'y' => 624.8, 'z' => 647.8, 'o' => 3.1]
            ];
            
            if (!isset($teleportData[$destination])) {
                throw new Exception(translate('error_invalid_destination', 'Invalid teleport destination'));
            }
            
            $data = $teleportData[$destination];
            /** @var \mysqli_stmt|false $stmt_teleport */
            $stmt_teleport = $char_db->prepare("UPDATE characters SET map = ?, position_x = ?, position_y = ?, position_z = ?, orientation = ? WHERE guid = ?");
            $stmt_teleport->bind_param('iddddi', $data['map'], $data['x'], $data['y'], $data['z'], $data['o'], $guid);
            if (!$stmt_teleport->execute()) {
                throw new Exception(translate('error_teleporting_character', 'Error teleporting character'));
            }
            $stmt_teleport->close();

            // Log teleport in sahtout_site.character_teleport_log
            /** @var \mysqli_stmt|false $stmt_cooldown */
            $stmt_cooldown = $site_db->prepare("INSERT INTO character_teleport_log (account_id, character_guid, character_name, teleport_timestamp) VALUES (?, ?, ?, UNIX_TIMESTAMP()) ON DUPLICATE KEY UPDATE teleport_timestamp = UNIX_TIMESTAMP(), character_name = ?");
            $stmt_cooldown->bind_param('iiss', $_SESSION['user_id'], $guid, $character_name, $character_name);
            if (!$stmt_cooldown->execute()) {
                throw new Exception(translate('error_logging_teleport', 'Error logging teleport'));
            }
            $stmt_cooldown->close();

            // Update session cooldown
            $_SESSION['teleport_cooldowns'][$guid] = $current_time;

            // Log action in sahtout_site.website_activity_log
            /** @var \mysqli_stmt|false $stmt_log */
            $stmt_log = $site_db->prepare("INSERT INTO website_activity_log (account_id, character_name, action, timestamp, details) VALUES (?, ?, ?, UNIX_TIMESTAMP(), ?)");
            $action = translate('action_teleport', 'Teleport');
            $details = sprintf(translate('teleport_details', 'To %s'), ucfirst($destination));
            $stmt_log->bind_param('isss', $_SESSION['user_id'], $character_name, $action, $details);
            $stmt_log->execute();
            $stmt_log->close();

            $_SESSION['message'] = sprintf(translate('message_character_teleported', 'Character teleported to %s!'), ucfirst($destination));
            header("Location: {$base_path}account");
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header("Location: {$base_path}account");
            exit();
        }
    }

    // Handle avatar change
    if (isset($_POST['change_avatar'])) {
        $avatar = $_POST['avatar'] !== '' ? $_POST['avatar'] : NULL;
        
        try {
            // Validate avatar
            /** @var \mysqli_stmt|false $stmt */
            $stmt = $site_db->prepare("SELECT filename FROM profile_avatars WHERE active = 1");
            $stmt->execute();
            $result = $stmt->get_result();
            $valid_avatars = [];
            while ($row = $result->fetch_assoc()) {
                $valid_avatars[] = $row['filename'];
            }
            $stmt->close();

            $valid_avatar = $avatar === NULL || in_array($avatar, $valid_avatars);
            if (!$valid_avatar) {
                throw new Exception(translate('error_invalid_avatar', 'Invalid avatar selected'));
            }

            /** @var \mysqli_stmt|false $stmt */
            $stmt = $site_db->prepare("UPDATE user_currencies SET avatar = ? WHERE account_id = ?");
            $stmt->bind_param('si', $avatar, $_SESSION['user_id']);
            if (!$stmt->execute()) {
                throw new Exception(translate('error_updating_avatar', 'Error updating avatar'));
            }
            $stmt->close();

            // Update session avatar for header.php
            $_SESSION['avatar'] = $avatar;

            // Log action
            /** @var \mysqli_stmt|false $stmt_log */
            $stmt_log = $site_db->prepare("INSERT INTO website_activity_log (account_id, character_name, action, timestamp, details) VALUES (?, NULL, ?, UNIX_TIMESTAMP(), ?)");
            $action = translate('action_avatar_changed', 'Avatar Changed');
            $details = $avatar !== NULL ? $avatar : translate('avatar_default', 'Default avatar');
            $stmt_log->bind_param('iss', $_SESSION['user_id'], $action, $details);
            $stmt_log->execute();
            $stmt_log->close();

            $_SESSION['message'] = translate('message_avatar_updated', 'Avatar updated successfully!');
            header("Location: {$base_path}account");
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header("Location: {$base_path}account");
            exit();
        }
    }
}

// Now proceed with page rendering
$page_class = 'account';
include_once $project_root . 'includes/header.php';

// Database queries for page content
if ($auth_db->connect_error || $char_db->connect_error || $site_db->connect_error) {
    $error = translate('error_database_connection', 'Database connection failed');
} else {
    // Get account info
    /** @var \mysqli_stmt|false $stmt */
    $stmt = $auth_db->prepare("SELECT id, username, email, joindate, last_login, locked, online, expansion FROM account WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $accountInfo = $result->fetch_assoc();
    }
    $stmt->close();

    // Check ban status
    /** @var \mysqli_stmt|false $stmt */
    $stmt = $auth_db->prepare("SELECT bandate, unbandate, banreason FROM account_banned WHERE id = ? AND active = 1");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $banInfo = $result->fetch_assoc();
    }
    $stmt->close();

    // Get characters
    if (!empty($accountInfo)) {
        /** @var \mysqli_stmt|false $stmt */
        $stmt = $char_db->prepare("SELECT guid, name, race, class, gender, level, money, online FROM characters WHERE account = ?");
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $characters = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

    // Get teleport cooldowns
    if (!empty($characters)) {
        $guids = array_column($characters, 'guid');
        $placeholders = implode(',', array_fill(0, count($guids), '?'));
        /** @var \mysqli_stmt|false $stmt */
        $stmt = $site_db->prepare("SELECT character_guid, teleport_timestamp FROM character_teleport_log WHERE character_guid IN ($placeholders)");
        $stmt->bind_param(str_repeat('i', count($guids)), ...$guids);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $teleport_cooldowns[$row['character_guid']] = $row['teleport_timestamp'];
        }
        $stmt->close();
    }

    // Get activity log
    /** @var \mysqli_stmt|false $stmt */
    $stmt = $site_db->prepare("SELECT action, timestamp, details, character_name FROM website_activity_log WHERE account_id = ? ORDER BY timestamp DESC LIMIT 10");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $activityLog = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Get Points, Tokens, and Avatar
    /** @var \mysqli_stmt|false $stmt */
    $stmt = $site_db->prepare("SELECT points, tokens, avatar FROM user_currencies WHERE account_id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $currencies = $result->fetch_assoc();
    }
    $stmt->close();

    // Get available avatars
    /** @var \mysqli_stmt|false $stmt */
    $stmt = $site_db->prepare("SELECT filename, display_name FROM profile_avatars WHERE active = 1");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $available_avatars[] = $row;
    }
    $stmt->close();
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$auth_db->close();
$char_db->close();
$site_db->close();

// Helper functions
function getAccountStatus($locked, $banInfo) {
    if (!empty($banInfo)) {
        $reason = htmlspecialchars($banInfo['banreason'] ?? translate('ban_no_reason', 'No reason provided'));
        $unbanDate = $banInfo['unbandate'] ? date('Y-m-d H:i:s', $banInfo['unbandate']) : translate('ban_permanent', 'Permanent');
        return sprintf('<span class="badge-status bg-red-900/50 text-red-300 border border-red-700/50">%s (Reason: %s, Until: %s)</span>', translate('status_banned', 'Banned'), $reason, $unbanDate);
    }
    switch ($locked) {
        case 1: return sprintf('<span class="badge-status bg-red-900/50 text-red-300 border border-red-700/50">%s</span>', translate('status_banned', 'Banned'));
        case 2: return sprintf('<span class="badge-status bg-blue-900/50 text-blue-300 border border-blue-700/50">%s</span>', translate('status_frozen', 'Frozen'));
        default: return sprintf('<span class="badge-status bg-green-900/50 text-green-300 border border-green-700/50">%s</span>', translate('status_active', 'Active'));
    }
}

function getGMStatus($gmlevel, $role) {
    global $base_path;
    $icon = ($gmlevel > 0 || $role !== 'player') ? 'gm_icon.gif' : 'player_icon.jpg';
    $color = ($gmlevel > 0 || $role !== 'player') ? '#f0a500' : '#aaa';
    
    if ($gmlevel > 0) {
        $suffix = '';
        if ($role === 'admin') {
            $suffix = translate('gm_suffix_admin', ' (S)');
        } elseif ($role === 'moderator') {
            $suffix = ($gmlevel == 1) ? translate('gm_suffix_moderator', ' (M)') : translate('gm_suffix_administrator', ' (A)');
        }
        $rank = sprintf(translate('gm_rank_gm', 'Game Master Level %s%s'), $gmlevel, $suffix);
    } elseif ($role === 'admin') {
        $rank = translate('gm_rank_admin', 'Admin');
    } elseif ($role === 'moderator') {
        $rank = translate('gm_rank_moderator', 'Moderator');
    } else {
        $rank = translate('gm_rank_player', 'Player');
    }
    
    return sprintf('<img src="%simg/accountimg/%s" alt="%s" class="w-5 h-5 inline-block mr-1"> <span style="color: %s" class="font-semibold">%s</span>', $base_path, $icon, translate('status_icon', 'Status Icon'), $color, $rank);
}

function getOnlineStatus($online) {
    return $online ? sprintf('<span class="text-green-400 font-semibold"><i class="fas fa-circle text-[8px] mr-1"></i>%s</span>', translate('status_online', 'Online')) : sprintf('<span class="text-red-400 font-semibold"><i class="fas fa-circle text-[8px] mr-1"></i>%s</span>', translate('status_offline', 'Offline'));
}

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
    return sprintf('<img src="%simg/accountimg/race/%s/%s" alt="%s" class="w-6 h-6 inline-block">', $base_path, $gender_folder, $image, translate('race_icon', 'Race Icon'));
}

function getClassIcon($class) {
    global $base_path;
    $icons = [
        1 => 'warrior.webp', 2 => 'paladin.webp', 3 => 'hunter.webp', 4 => 'rogue.webp',
        5 => 'priest.webp', 6 => 'deathknight.webp', 7 => 'shaman.webp', 8 => 'mage.webp',
        9 => 'warlock.webp', 11 => 'druid.webp'
    ];
    return sprintf('<img src="%simg/accountimg/class/%s" alt="%s" class="w-6 h-6 inline-block">', $base_path, ($icons[$class] ?? 'default.jpg'), translate('class_icon', 'Class Icon'));
}

function getFactionIcon($race) {
    global $base_path;
    $allianceRaces = [1, 3, 4, 7, 11]; // Human, Dwarf, Night Elf, Gnome, Draenei
    $faction = in_array($race, $allianceRaces) ? 'alliance.png' : 'horde.png';
    return sprintf('<img src="%simg/accountimg/faction/%s" alt="%s" class="w-5 h-5 inline-block">', $base_path, $faction, translate('faction_icon', 'Faction Icon'));
}

// Helper function to get avatar display name translation
function getAvatarDisplayName($filename) {
    return translate('avatar_' . str_replace('.', '_', $filename), $filename);
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_title_name ." ". sprintf(translate('page_title', 'Account - %s'), htmlspecialchars($accountInfo['username'] ?? '')); ?></title>
    <!-- Font Awesome for icons (local first, CDN fallback) -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>node_modules/@fortawesome/fontawesome-free/css/all.min.css" onerror="this.onerror=null;this.href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css';">
    <style>
        /* Page background */
        body {
            background: url('<?php echo $base_path; ?>img/backgrounds/bg-account.jpg') no-repeat center center fixed;
            background-size: cover;
            position: relative;
            min-height: 100vh;
            padding-top: 112px;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        
        /* No overlay on the page - let the image show through */
        body::before {
            display: none;
        }
        
        /* Main content wrapper */
        .account-content {
            position: relative;
            z-index: 1;
        }
        
        /* Glass effect container - without blur */
        .glass-container {
            background: rgba(0, 0, 0, 0.75);
            border: 1px solid rgba(255, 215, 0, 0.30);
            border-radius: 16px;
            box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.8);
        }
        
        /* Navigation tabs - Improved visibility on transparent background */
        .nav-tabs-gaming {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid rgba(245, 200, 66, 0.3);
        }
        
        .nav-tab-gaming {
            padding: 0.7rem 1.8rem;
            color: #c8d6e5;
            font-weight: 600;
            font-size: 0.95rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            background: rgba(0, 0, 0, 0.5);
            border: 2px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }
        
        .nav-tab-gaming:hover {
            color: #ffffff;
            background: rgba(255, 215, 0, 0.2);
            border-color: rgba(255, 215, 0, 0.4);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 215, 0, 0.15);
        }
        
        .nav-tab-gaming.active {
            color: #f5c842;
            background: rgba(255, 215, 0, 0.25);
            border-color: #f5c842;
            box-shadow: 0 0 20px rgba(245, 200, 66, 0.2), inset 0 0 20px rgba(245, 200, 66, 0.05);
        }
        
        .nav-tab-gaming i {
            font-size: 1.1rem;
        }
        
        /* Glass cards - without blur */
        .glass-card {
            background: rgba(18, 24, 34, 0.9);
            border: 1px solid rgba(255, 215, 0, 0.15);
            box-shadow: 0 8px 24px -8px rgba(0,0,0,0.8);
            transition: all 0.3s ease;
            border-radius: 12px;
        }
        
        .glass-card:hover {
            border-color: rgba(255, 215, 0, 0.3);
            box-shadow: 0 12px 32px -8px rgba(255, 215, 0, 0.1);
        }
        
        .gold-glow {
            color: #f5c842;
            text-shadow: 0 0 12px rgba(245, 200, 66, 0.3);
        }
        
        .btn-gaming {
            background: linear-gradient(145deg, #2c3a4b, #1d2633);
            border: 1px solid #4a5b70;
            color: #e8edf2;
            font-weight: 600;
            padding: 0.6rem 1.8rem;
            border-radius: 40px;
            transition: all 0.15s ease;
            box-shadow: 0 4px 0 #0f141c;
            letter-spacing: 0.3px;
            cursor: pointer;
            display: inline-block;
            text-decoration: none;
        }
        
        .btn-gaming:hover {
            background: linear-gradient(145deg, #3e5066, #28323f);
            border-color: #f5c842;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 6px 0 #0f141c;
            text-decoration: none;
        }
        
        .btn-gaming:disabled {
            opacity: 0.5;
            transform: translateY(0);
            box-shadow: 0 4px 0 #0f141c;
            pointer-events: none;
        }
        
        .tab-pane {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        .tab-pane.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .badge-status {
            padding: 0.25rem 1rem;
            border-radius: 40px;
            font-weight: 600;
            font-size: 0.75rem;
            letter-spacing: 0.3px;
            display: inline-block;
        }
        
        .input-gaming {
            background: #11181f;
            border: 1px solid #2e3c4b;
            color: #e8edf2;
            border-radius: 30px;
            padding: 0.6rem 1.2rem;
            width: 100%;
            transition: 0.2s;
        }
        
        .input-gaming:focus {
            border-color: #f5c842;
            outline: none;
            box-shadow: 0 0 0 3px #f5c84233;
        }
        
        .input-gaming::placeholder {
            color: #6b7d93;
        }
        
        .select-gaming {
            background: #11181f;
            border: 1px solid #2e3c4b;
            color: #e8edf2;
            border-radius: 30px;
            padding: 0.5rem 1.2rem;
            width: 100%;
            cursor: pointer;
        }
        
        .select-gaming:focus {
            border-color: #f5c842;
            outline: none;
        }
        
        .select-gaming option {
            background: #1a212b;
            color: #e8edf2;
        }
        
        .avatar-selector img {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid transparent;
            cursor: pointer;
            transition: all 0.15s;
            background: #1f2937;
            padding: 2px;
        }
        
        .avatar-selector img.selected {
            border-color: #f5c842;
            box-shadow: 0 0 16px #f5c84266;
            transform: scale(1.05);
        }
        
        .avatar-selector img:hover {
            transform: scale(1.08);
            border-color: #7a8aa0;
        }
        
        .table-gaming {
            border-collapse: separate;
            border-spacing: 0 6px;
            width: 100%;
        }
        
        .table-gaming thead th {
            background: #1a212b;
            color: #b8ccdd;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 1px;
            padding: 0.9rem 0.5rem;
            border-bottom: 2px solid #2f3d4e;
        }
        
        .table-gaming tbody td {
            background: #141b24;
            color: #d4e0ed;
            padding: 0.8rem 0.5rem;
            border-bottom: 1px solid #28323f;
        }
        
        .table-gaming tbody tr:hover td {
            background: #1c2633;
        }
        
        .cooldown-timer {
            color: #f5a842;
            font-size: 0.8rem;
            background: #1f2a36;
            padding: 0.2rem 0.8rem;
            border-radius: 40px;
            display: inline-block;
        }
        
        .alert-gaming {
            border-radius: 12px;
            padding: 1rem 1.5rem;
        }
        
        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid #f5c842;
            object-fit: cover;
        }
        
        @media (max-width: 767px) {
            body {
                padding-top: 96px;
            }
        }
    </style>
</head>
<body>

<div class="account-content min-h-screen flex items-start justify-center px-4 md:px-8 py-8">
    <div class="container mx-auto max-w-7xl px-2 sm:px-4">
        
        <!-- Main Container - Glass Effect -->
        <div class="glass-container p-6 md:p-10">
            
            <!-- Page Title -->
            <div class="flex items-center gap-4 mb-6">
                <i class="fas fa-shield-halved text-4xl text-[#f5c842]"></i>
                <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight text-white">
                    Account <span class="text-[#f5c842]">Dashboard</span>
                </h1>
                <?php if (!empty($accountInfo['last_login'])): ?>
                    <span class="ml-auto text-sm bg-black/40 px-4 py-1.5 rounded-full border border-[#2f3d4e] text-gray-300">
                        <i class="far fa-clock mr-1"></i> Last login: <?php echo htmlspecialchars($accountInfo['last_login']); ?>
                    </span>
                <?php endif; ?>
            </div>

            <!-- Alert Messages -->
            <?php if ($message): ?>
                <div class="alert-gaming bg-green-900/40 border border-green-600/40 text-green-200 px-5 py-3 rounded-xl flex items-center gap-3 mb-4">
                    <i class="fas fa-check-circle text-green-400 text-lg"></i>
                    <span><?php echo htmlspecialchars($message); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert-gaming bg-red-900/40 border border-red-600/40 text-red-200 px-5 py-3 rounded-xl flex items-center gap-3 mb-4">
                    <i class="fas fa-exclamation-circle text-red-400 text-lg"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($debug_errors) && ($role === 'admin' || $gmlevel > 0)): ?>
                <div class="alert-gaming bg-yellow-900/30 border border-yellow-600/30 text-yellow-200 px-5 py-3 rounded-xl flex items-start gap-3 mb-4">
                    <i class="fas fa-exclamation-triangle text-yellow-400 mt-1"></i>
                    <div>
                        <strong><?php echo translate('debug_warnings', 'Debug Warnings'); ?>:</strong><br>
                        <?php echo htmlspecialchars(implode('<br>', array_unique($debug_errors))); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Navigation Tabs - IMPROVED VISIBILITY -->
            <div class="nav-tabs-gaming">
                <button class="nav-tab-gaming active" data-tab="overview">
                    <i class="fas fa-chart-pie"></i> <?php echo translate('tab_overview', 'Overview'); ?>
                </button>
                <button class="nav-tab-gaming" data-tab="characters">
                    <i class="fas fa-users"></i> <?php echo translate('tab_characters', 'Characters'); ?>
                </button>
                <button class="nav-tab-gaming" data-tab="activity">
                    <i class="fas fa-history"></i> <?php echo translate('tab_activity', 'Activity'); ?>
                </button>
                <button class="nav-tab-gaming" data-tab="security">
                    <i class="fas fa-shield-alt"></i> <?php echo translate('tab_security', 'Security'); ?>
                </button>
                <a href="<?php echo $base_path; ?>vote" class="nav-tab-gaming ml-auto">
                    <i class="fas fa-vote-yea"></i> <?php echo translate('tab_vote', 'Vote'); ?>
                </a>
            </div>

            <!-- Tab Content -->
            <div class="space-y-8">
                
                <!-- OVERVIEW TAB -->
                <div class="tab-pane active" id="tab-overview">
                    <!-- Account Information -->
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-white flex items-center gap-3 mb-4">
                            <i class="fas fa-info-circle text-[#f5c842]"></i>
                            <?php echo translate('section_account_info', 'Account Information'); ?>
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Basic Info Card -->
                            <div class="glass-card p-6 text-center">
                                <?php
                                $avatar_display = !empty($currencies['avatar']) ? $currencies['avatar'] : 'user.jpg';
                                ?>
                                <img src="<?php echo $base_path; ?>img/accountimg/profile_pics/<?php echo htmlspecialchars($avatar_display); ?>" 
                                     alt="<?php echo translate('avatar_alt', 'Avatar'); ?>" 
                                     class="profile-avatar mx-auto mb-4">
                                <h3 class="text-xl font-bold text-white"><?php echo htmlspecialchars($accountInfo['username'] ?? 'N/A'); ?></h3>
                                <p class="text-sm text-gray-400">Account ID: #<?php echo $accountInfo['id'] ?? 'N/A'; ?></p>
                                <div class="flex flex-wrap justify-center gap-2 mt-3">
                                    <?php echo getAccountStatus($accountInfo['locked'] ?? 0, $banInfo); ?>
                                </div>
                                <div class="mt-2">
                                    <?php echo getGMStatus($gmlevel, $role); ?>
                                </div>
                                <p class="mt-2"><?php echo getOnlineStatus($accountInfo['online'] ?? 0); ?></p>
                            </div>

                            <!-- Contact Card -->
                            <div class="glass-card p-6 text-center">
                                <i class="fas fa-envelope text-3xl text-[#f5c842] mb-2"></i>
                                <h3 class="text-xl font-bold text-white"><?php echo translate('card_contact', 'Contact'); ?></h3>
                                <p class="text-sm text-gray-300 mt-2">
                                    <i class="far fa-envelope mr-1 text-gray-400"></i>
                                    <?php echo htmlspecialchars($accountInfo['email'] ?? translate('email_not_set', 'Not set')); ?>
                                </p>
                                <p class="text-sm mt-1">
                                    <span class="gold-glow">
                                        <i class="fas fa-expand-alt mr-1"></i>
                                        <?php echo translate('expansion_' . ($accountInfo['expansion'] ?? 2), ($accountInfo['expansion'] ?? 2) == 2 ? 'Wrath of the Lich King' : ($accountInfo['expansion'] == 1 ? 'The Burning Crusade' : 'Classic')); ?>
                                    </span>
                                </p>
                                <?php if ($role === 'admin' || $role === 'moderator' || $gmlevel > 0): ?>
                                    <div class="mt-4">
                                        <a href="<?php echo $base_path; ?>admin/dashboard" class="btn-gaming text-sm py-1.5 px-5 inline-block">
                                            <i class="fas fa-crown mr-1"></i> <?php echo translate('button_admin_panel', 'Admin Panel'); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Activity Card -->
                            <div class="glass-card p-6 text-center">
                                <i class="fas fa-calendar-alt text-3xl text-[#f5c842] mb-2"></i>
                                <h3 class="text-xl font-bold text-white"><?php echo translate('card_activity', 'Activity'); ?></h3>
                                <p class="text-sm text-gray-300 mt-2">
                                    <i class="far fa-calendar-plus mr-1"></i>
                                    <?php echo translate('label_join_date', 'Join Date'); ?>: <?php echo $accountInfo['joindate'] ?? 'N/A'; ?>
                                </p>
                                <p class="text-sm">
                                    <i class="far fa-clock mr-1"></i>
                                    <?php echo translate('label_last_login', 'Last Login'); ?>: <?php echo $accountInfo['last_login'] ?? translate('never', 'Never'); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div>
                        <h2 class="text-2xl font-bold text-white flex items-center gap-3 mb-4">
                            <i class="fas fa-chart-simple text-[#f5c842]"></i>
                            <?php echo translate('section_quick_stats', 'Quick Stats'); ?>
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="glass-card p-6 flex items-center justify-between">
                                <div><i class="fas fa-user-friends text-3xl text-[#f5c842]"></i></div>
                                <div class="text-center">
                                    <p class="text-2xl font-bold text-white"><?php echo count($characters); ?></p>
                                    <p class="text-gray-400 text-sm"><?php echo translate('label_total_characters', 'Total Characters'); ?></p>
                                </div>
                                <div class="text-center">
                                    <?php 
                                        $maxLevel = 0;
                                        foreach ($characters as $char) {
                                            if ($char['level'] > $maxLevel) $maxLevel = $char['level'];
                                        }
                                    ?>
                                    <p class="text-2xl font-bold text-white"><?php echo $maxLevel; ?></p>
                                    <p class="text-gray-400 text-sm"><?php echo translate('label_highest_level', 'Highest Level'); ?></p>
                                </div>
                            </div>
                            <div class="glass-card p-6 flex items-center justify-between">
                                <div><i class="fas fa-coins text-3xl gold-glow"></i></div>
                                <div class="text-center">
                                    <?php 
                                        $totalGold = 0;
                                        foreach ($characters as $char) {
                                            $totalGold += $char['money'];
                                        }
                                    ?>
                                    <p class="text-2xl font-bold text-white"><?php echo number_format($totalGold / 10000, 2); ?><span class="text-sm text-gray-400 ml-1">g</span></p>
                                    <p class="text-gray-400 text-sm"><?php echo translate('label_total_gold', 'Total Gold'); ?></p>
                                </div>
                                <div class="text-center">
                                    <p class="text-2xl font-bold text-white"><?php echo $currencies['points']; ?> <span class="text-sm text-gray-400">P</span></p>
                                    <p class="text-gray-400 text-sm"><?php echo translate('label_points', 'Points'); ?></p>
                                </div>
                                <div class="text-center">
                                    <p class="text-2xl font-bold text-white"><?php echo $currencies['tokens']; ?> <span class="text-sm text-gray-400">T</span></p>
                                    <p class="text-gray-400 text-sm"><?php echo translate('label_tokens', 'Tokens'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CHARACTERS TAB -->
                <div class="tab-pane" id="tab-characters">
                    <h2 class="text-2xl font-bold text-white flex items-center gap-3 mb-4">
                        <i class="fas fa-users text-[#f5c842]"></i>
                        <?php echo translate('section_your_characters', 'Your Characters'); ?>
                    </h2>
                    
                    <?php if (!empty($characters)): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                            <?php foreach ($characters as $char): ?>
                                <div class="glass-card p-5">
                                    <div class="flex items-center gap-3">
                                        <span><?php echo getFactionIcon($char['race']); ?></span>
                                        <span><?php echo getRaceIcon($char['race'], $char['gender']); ?></span>
                                        <span class="text-lg font-bold text-white"><?php echo htmlspecialchars($char['name']); ?></span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-3 mt-2 text-sm">
                                        <span><?php echo getClassIcon($char['class']); ?> <?php echo translate('label_level', 'Level'); ?> <?php echo $char['level']; ?></span>
                                        <span class="gold-glow"><?php echo number_format($char['money'] / 10000, 2); ?>g</span>
                                        <span><?php echo getOnlineStatus($char['online']); ?></span>
                                    </div>
                                    
                                    <?php
                                    $cooldown_remaining = max(
                                        isset($teleport_cooldowns[$char['guid']]) ? ($teleport_cooldowns[$char['guid']] + 900 - time()) : 0,
                                        isset($_SESSION['teleport_cooldowns'][$char['guid']]) ? ($_SESSION['teleport_cooldowns'][$char['guid']] + 900 - time()) : 0
                                    );
                                    $is_on_cooldown = $cooldown_remaining > 0;
                                    $minutes = ceil($cooldown_remaining / 60);
                                    ?>
                                    
                                    <form method="post" class="mt-3" onsubmit="return confirm('<?php echo translate('confirm_teleport', 'Teleport this character?'); ?>');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="guid" value="<?php echo $char['guid']; ?>">
                                        <div class="flex gap-2">
                                            <select class="select-gaming text-sm py-1.5 px-3" name="destination" required>
                                                <option value=""><?php echo translate('select_city_placeholder', 'Select city'); ?></option>
                                                <option value="shattrath"><?php echo translate('city_shattrath', 'Shattrath'); ?></option>
                                                <option value="dalaran"><?php echo translate('city_dalaran', 'Dalaran'); ?></option>
                                            </select>
                                            <button class="btn-gaming text-sm py-1.5 px-4 whitespace-nowrap" type="submit" name="teleport_character" <?php echo $is_on_cooldown ? 'disabled' : ''; ?>>
                                                <i class="fas fa-arrow-right mr-1"></i><?php echo translate('button_teleport', 'Teleport'); ?>
                                            </button>
                                        </div>
                                        <?php if ($is_on_cooldown): ?>
                                            <p class="mt-2 cooldown-timer text-xs" data-cooldown="<?php echo $cooldown_remaining; ?>">
                                                <i class="fas fa-hourglass-half mr-1"></i>
                                                <?php echo sprintf(translate('teleport_cooldown', 'Cooldown: %s minute%s'), $minutes, $minutes > 1 ? 's' : ''); ?>
                                            </p>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-gray-400 py-8"><?php echo translate('no_characters', 'You have no characters yet.'); ?></p>
                    <?php endif; ?>
                </div>

                <!-- ACTIVITY TAB -->
                <div class="tab-pane" id="tab-activity">
                    <h2 class="text-2xl font-bold text-white flex items-center gap-3 mb-4">
                        <i class="fas fa-history text-[#f5c842]"></i>
                        <?php echo translate('section_account_activity', 'Account Activity'); ?>
                    </h2>
                    
                    <?php if (!empty($activityLog)): ?>
                        <div class="glass-card p-0 overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="table-gaming w-full text-sm">
                                    <thead>
                                        <tr>
                                            <th><?php echo translate('table_action', 'Action'); ?></th>
                                            <th><?php echo translate('table_character', 'Character'); ?></th>
                                            <th><?php echo translate('table_timestamp', 'Timestamp'); ?></th>
                                            <th><?php echo translate('table_details', 'Details'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activityLog as $log): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($log['action']); ?></td>
                                                <td><?php echo htmlspecialchars($log['character_name'] ?? translate('none', 'N/A')); ?></td>
                                                <td><?php echo date('Y-m-d H:i:s', $log['timestamp']); ?></td>
                                                <td><?php echo htmlspecialchars($log['details'] ?? translate('none', 'None')); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-gray-400 py-8"><?php echo translate('no_activity', 'No recent activity.'); ?></p>
                    <?php endif; ?>
                </div>

                <!-- SECURITY TAB -->
                <div class="tab-pane" id="tab-security">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Change Email -->
                        <div class="glass-card p-6">
                            <h3 class="text-xl font-bold text-[#f5c842] flex items-center gap-2">
                                <i class="fas fa-envelope"></i> <?php echo translate('section_change_email', 'Change Email'); ?>
                            </h3>
                            <form method="post" class="mt-4 space-y-4">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <div>
                                    <label class="block text-sm text-gray-300 mb-1"><?php echo translate('label_current_password', 'Current Password'); ?></label>
                                    <input type="password" class="input-gaming" name="current_password" required placeholder="<?php echo translate('placeholder_current_password', 'Enter current password'); ?>">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-300 mb-1"><?php echo translate('label_new_email', 'New Email'); ?></label>
                                    <input type="email" class="input-gaming" name="new_email" required value="<?php echo htmlspecialchars($accountInfo['email'] ?? ''); ?>" placeholder="<?php echo translate('placeholder_new_email', 'Enter new email'); ?>">
                                </div>
                                <button class="btn-gaming w-full sm:w-auto" type="submit" name="change_email">
                                    <i class="fas fa-save mr-2"></i><?php echo translate('button_update_email', 'Update Email'); ?>
                                </button>
                            </form>
                        </div>

                        <!-- Change Password -->
                        <div class="glass-card p-6">
                            <h3 class="text-xl font-bold text-[#f5c842] flex items-center gap-2">
                                <i class="fas fa-key"></i> <?php echo translate('section_change_password', 'Change Password'); ?>
                            </h3>
                            <form method="post" class="mt-4 space-y-4">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <div>
                                    <label class="block text-sm text-gray-300 mb-1"><?php echo translate('label_current_password', 'Current Password'); ?></label>
                                    <input type="password" class="input-gaming" name="current_password" required placeholder="<?php echo translate('placeholder_current_password', 'Enter current password'); ?>">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-300 mb-1"><?php echo translate('label_new_password', 'New Password'); ?></label>
                                    <input type="password" class="input-gaming" name="new_password" required minlength="6" placeholder="<?php echo translate('placeholder_new_password', 'Enter new password'); ?>">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-300 mb-1"><?php echo translate('label_confirm_password', 'Confirm New Password'); ?></label>
                                    <input type="password" class="input-gaming" name="confirm_password" required minlength="6" placeholder="<?php echo translate('placeholder_confirm_password', 'Confirm new password'); ?>">
                                </div>
                                <button class="btn-gaming w-full sm:w-auto" type="submit" name="change_password">
                                    <i class="fas fa-lock mr-2"></i><?php echo translate('button_change_password', 'Change Password'); ?>
                                </button>
                            </form>
                        </div>

                        <!-- Change Avatar -->
                        <div class="lg:col-span-2 glass-card p-6">
                            <h3 class="text-xl font-bold text-[#f5c842] flex items-center gap-2">
                                <i class="fas fa-user-astronaut"></i> <?php echo translate('section_change_avatar', 'Change Avatar'); ?>
                            </h3>
                            <form method="post" class="mt-4">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <div class="avatar-selector flex flex-wrap gap-4 mt-2 items-center">
                                    <?php foreach ($available_avatars as $avatar): ?>
                                        <div class="text-center">
                                            <img src="<?php echo $base_path; ?>img/accountimg/profile_pics/<?php echo htmlspecialchars($avatar['filename']); ?>" 
                                                 class="<?php echo $currencies['avatar'] === $avatar['filename'] ? 'selected' : ''; ?>" 
                                                 onclick="selectAvatar('<?php echo htmlspecialchars($avatar['filename']); ?>')" 
                                                 alt="<?php echo htmlspecialchars(getAvatarDisplayName($avatar['filename'])); ?>">
                                            <span class="text-xs text-gray-400 block mt-1"><?php echo htmlspecialchars(getAvatarDisplayName($avatar['filename'])); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="text-center">
                                        <img src="<?php echo $base_path; ?>img/accountimg/profile_pics/user.jpg" 
                                             class="<?php echo empty($currencies['avatar']) ? 'selected' : ''; ?>" 
                                             onclick="selectAvatar('')" 
                                             alt="<?php echo translate('avatar_default', 'Default Avatar'); ?>">
                                        <span class="text-xs text-gray-400 block mt-1"><?php echo translate('avatar_default', 'Default'); ?></span>
                                    </div>
                                </div>
                                <input type="hidden" name="avatar" id="avatar" value="<?php echo htmlspecialchars($currencies['avatar'] ?? ''); ?>">
                                <button class="btn-gaming mt-4" type="submit" name="change_avatar">
                                    <i class="fas fa-check mr-2"></i><?php echo translate('button_update_avatar', 'Update Avatar'); ?>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Account Actions -->
                    <div class="glass-card p-6 mt-8">
                        <h3 class="text-xl font-bold text-[#f5c842] flex items-center gap-2">
                            <i class="fas fa-cog"></i> <?php echo translate('section_account_actions', 'Account Actions'); ?>
                        </h3>
                        <div class="flex flex-wrap justify-center gap-4 mt-4">
                            <a href="<?php echo $base_path; ?>logout" class="text-[#f5c842] hover:text-yellow-400 transition">
                                <i class="fas fa-sign-out-alt mr-1"></i> <?php echo translate('action_logout', 'Logout'); ?>
                            </a>
                            <span class="text-gray-600">|</span>
                            <a href="#" class="text-red-400 hover:text-red-300 transition">
                                <i class="fas fa-trash-alt mr-1"></i> <?php echo translate('action_request_deletion', 'Request Account Deletion'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once $project_root . 'includes/footer.php'; ?>

<!-- JavaScript for Tabs and Avatar Selection -->
<script>
    // Tab switching functionality
    document.querySelectorAll('.nav-tab-gaming').forEach(tab => {
        tab.addEventListener('click', function(e) {
            // If it's a link, let it navigate normally
            if (this.tagName === 'A') return;
            
            // Remove active class from all tabs
            document.querySelectorAll('.nav-tab-gaming').forEach(t => t.classList.remove('active'));
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Get tab ID
            const tabId = this.getAttribute('data-tab');
            
            // Hide all tab panes
            document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
            
            // Show the corresponding tab pane
            const targetPane = document.getElementById('tab-' + tabId);
            if (targetPane) {
                targetPane.classList.add('active');
            }
        });
    });

    // Avatar selection
    function selectAvatar(filename) {
        document.getElementById('avatar').value = filename;
        document.querySelectorAll('.avatar-selector img').forEach(img => {
            img.classList.remove('selected');
        });
        const selectedImg = document.querySelector(`.avatar-selector img[onclick="selectAvatar('${filename}')"]`);
        if (selectedImg) {
            selectedImg.classList.add('selected');
        }
    }

    // Client-side countdown timer for teleport cooldown
    document.querySelectorAll('.cooldown-timer').forEach(function(element) {
        let seconds = parseInt(element.dataset.cooldown);
        if (seconds > 0) {
            let timer = setInterval(function() {
                seconds--;
                let minutes = Math.ceil(seconds / 60);
                let plural = minutes > 1 ? 's' : '';
                element.innerHTML = '<i class="fas fa-hourglass-half mr-1"></i> ' + 
                    '<?php echo translate('teleport_cooldown', 'Cooldown: %s minute%s'); ?>'
                    .replace('%s', minutes).replace('%s', plural);
                if (seconds <= 0) {
                    clearInterval(timer);
                    element.remove();
                    const form = element.closest('form');
                    if (form) {
                        const button = form.querySelector('button[type="submit"]');
                        if (button) button.disabled = false;
                    }
                }
            }, 1000);
        }
    });
</script>
</body>
</html>
<?php
ob_end_flush(); // Flush the output buffer
?>