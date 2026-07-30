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
        return sprintf('<span class="text-danger">%s (Reason: %s, Until: %s)</span>', translate('status_banned', 'Banned'), $reason, $unbanDate);
    }
    switch ($locked) {
        case 1: return sprintf('<span class="text-danger">%s</span>', translate('status_banned', 'Banned'));
        case 2: return sprintf('<span class="text-info">%s</span>', translate('status_frozen', 'Frozen'));
        default: return sprintf('<span class="text-success">%s</span>', translate('status_active', 'Active'));
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
    
    return sprintf('<img src="%simg/accountimg/%s" alt="%s" class="account-icon"> <span style="color: %s">%s</span>', $base_path, $icon, translate('status_icon', 'Status Icon'), $color, $rank);
}

function getOnlineStatus($online) {
    return $online ? sprintf('<span class="text-success">%s</span>', translate('status_online', 'Online')) : sprintf('<span class="text-danger">%s</span>', translate('status_offline', 'Offline'));
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
    return sprintf('<img src="%simg/accountimg/race/%s/%s" alt="%s" class="account-icon">', $base_path, $gender_folder, $image, translate('race_icon', 'Race Icon'));
}

function getClassIcon($class) {
    global $base_path;
    $icons = [
        1 => 'warrior.webp', 2 => 'paladin.webp', 3 => 'hunter.webp', 4 => 'rogue.webp',
        5 => 'priest.webp', 6 => 'deathknight.webp', 7 => 'shaman.webp', 8 => 'mage.webp',
        9 => 'warlock.webp', 11 => 'druid.webp'
    ];
    return sprintf('<img src="%simg/accountimg/class/%s" alt="%s" class="account-icon">', $base_path, ($icons[$class] ?? 'default.jpg'), translate('class_icon', 'Class Icon'));
}

function getFactionIcon($race) {
    global $base_path;
    $allianceRaces = [1, 3, 4, 7, 11]; // Human, Dwarf, Night Elf, Gnome, Draenei
    $faction = in_array($race, $allianceRaces) ? 'alliance.png' : 'horde.png';
    return sprintf('<img src="%simg/accountimg/faction/%s" alt="%s" class="account-icon">', $base_path, $faction, translate('faction_icon', 'Faction Icon'));
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
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<style>
    :root{
            --bg-account:url('<?php echo $base_path; ?>img/backgrounds/bg-account.jpg');
            --hover-wow-gif: url('<?php echo $base_path; ?>img/hover_wow.gif');
        }
</style>
<body>
    <?php include_once $project_root . 'includes/header.php'; ?>
    <main>
        <div class="account-container">
            <h1 class="account-title mb-4"><?php echo translate('dashboard_title', 'Account Dashboard'); ?></h1>

            <?php if ($message): ?>
                <div class="alert alert-success account-message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger account-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if (!empty($debug_errors) && ($role === 'admin' || $gmlevel > 0)): ?>
                <div class="alert alert-warning account-message">
                    <strong><?php echo translate('debug_warnings', 'Debug Warnings'); ?>:</strong><br>
                    <?php echo htmlspecialchars(implode('<br>', array_unique($debug_errors))); ?>
                </div>
            <?php endif; ?>

            <ul class="nav nav-tabs account-tabs mb-4 justify-content-center" id="accountTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab"><?php echo translate('tab_overview', 'Overview'); ?></button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="characters-tab" data-bs-toggle="tab" data-bs-target="#characters" type="button" role="tab"><?php echo translate('tab_characters', 'Characters'); ?></button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" type="button" role="tab"><?php echo translate('tab_activity', 'Activity'); ?></button>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" href="<?php echo $base_path; ?>vote"><?php echo translate('tab_vote', 'Vote'); ?></a>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab"><?php echo translate('tab_security', 'Security'); ?></button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="overview" role="tabpanel">
                    <div class="mb-4">
                        <h2 class="h3 text-warning"><?php echo translate('section_account_info', 'Account Information'); ?></h2>
                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                            <div class="col">
                                <div class="card account-card h-100">
                                    <div class="card-body text-center">
                                        <h3 class="card-title"><?php echo translate('card_basic_info', 'Basic Info'); ?></h3>
                                        <?php
                                        $avatar_display = !empty($currencies['avatar']) ? $currencies['avatar'] : 'user.jpg';
                                        ?>
                                        <img src="<?php echo $base_path; ?>img/accountimg/profile_pics/<?php echo htmlspecialchars($avatar_display); ?>" alt="<?php echo translate('avatar_alt', 'Avatar'); ?>" class="account-profile-pic mb-3">
                                        <p><strong><?php echo translate('label_username', 'Username'); ?>:</strong> <?php echo htmlspecialchars($accountInfo['username'] ?? 'N/A'); ?></p>
                                        <p><strong><?php echo translate('label_account_id', 'Account ID'); ?>:</strong> <?php echo $accountInfo['id'] ?? 'N/A'; ?></p>
                                        <p><strong><?php echo translate('label_status', 'Status'); ?>:</strong> <?php echo getAccountStatus($accountInfo['locked'] ?? 0, $banInfo); ?></p>
                                        <p><strong><?php echo translate('label_rank', 'Rank'); ?>:</strong> <?php echo getGMStatus($gmlevel, $role); ?></p>
                                        <p><strong><?php echo translate('label_online', 'Online'); ?>:</strong> <?php echo getOnlineStatus($accountInfo['online'] ?? 0); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card account-card h-100">
                                    <div class="card-body text-center">
                                        <h3 class="card-title"><?php echo translate('card_contact', 'Contact'); ?></h3>
                                        <p><strong><?php echo translate('label_email', 'Email'); ?>:</strong> <?php echo htmlspecialchars($accountInfo['email'] ?? translate('email_not_set', 'Not set')); ?></p>
                                        <p><strong class="text-warning"><?php echo translate('label_expansion', 'Expansion'); ?>:</strong> <?php echo translate('expansion_' . ($accountInfo['expansion'] ?? 2), ($accountInfo['expansion'] ?? 2) == 2 ? 'Wrath of the Lich King' : ($accountInfo['expansion'] == 1 ? 'The Burning Crusade' : 'Classic')); ?></p>
                                        <?php if ($role === 'admin' || $role === 'moderator' || $gmlevel > 0): ?>
                                            <a href="<?php echo $base_path; ?>admin/dashboard" class="btn-account"><?php echo translate('button_admin_panel', 'Admin Panel'); ?></a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card account-card h-100">
                                    <div class="card-body text-center">
                                        <h3 class="card-title"><?php echo translate('card_activity', 'Activity'); ?></h3>
                                        <p><strong><?php echo translate('label_join_date', 'Join Date'); ?>:</strong> <?php echo $accountInfo['joindate'] ?? 'N/A'; ?></p>
                                        <p><strong><?php echo translate('label_last_login', 'Last Login'); ?>:</strong> <?php echo $accountInfo['last_login'] ?? translate('never', 'Never'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h2 class="h3 text-warning"><?php echo translate('section_quick_stats', 'Quick Stats'); ?></h2>
                        <div class="row row-cols-1 row-cols-md-2 g-4">
                            <div class="col">
                                <div class="card account-card h-100">
                                    <div class="card-body text-center">
                                        <h3 class="card-title"><?php echo translate('card_characters', 'Characters'); ?></h3>
                                        <p><strong><?php echo translate('label_total_characters', 'Total'); ?>:</strong> <?php echo count($characters); ?></p>
                                        <p><strong><?php echo translate('label_highest_level', 'Highest Level'); ?>:</strong> 
                                            <?php 
                                                $maxLevel = 0;
                                                foreach ($characters as $char) {
                                                    if ($char['level'] > $maxLevel) $maxLevel = $char['level'];
                                                }
                                                echo $maxLevel;
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card account-card h-100">
                                    <div class="card-body text-center">
                                        <h3 class="card-title"><?php echo translate('card_wealth', 'Wealth'); ?></h3>
                                        <p><strong><?php echo translate('label_total_gold', 'Total Gold'); ?>:</strong> 
                                            <?php 
                                                $totalGold = 0;
                                                foreach ($characters as $char) {
                                                    $totalGold += $char['money'];
                                                }
                                                echo sprintf('<span class="account-gold">%.2fg</span>', number_format($totalGold / 10000, 2));
                                            ?>
                                            <img src="<?php echo $base_path; ?>img/accountimg/gold_coin.png" alt="<?php echo translate('gold_icon', 'Gold Icon'); ?>" class="account-icon">
                                        </p>
                                        <p><strong><?php echo translate('label_points', 'Points'); ?>:</strong> <?php echo $currencies['points']; ?> P</p>
                                        <p><strong><?php echo translate('label_tokens', 'Tokens'); ?>:</strong> <?php echo $currencies['tokens']; ?> T</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="characters" role="tabpanel">
                    <h2 class="h3 text-warning mb-4"><?php echo translate('section_your_characters', 'Your Characters'); ?></h2>
                    <?php if (!empty($characters)): ?>
                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                            <?php foreach ($characters as $char): ?>
                                <div class="col">
                                    <div class="card account-card h-100">
                                        <div class="card-body text-center">
                                            <div class="d-flex justify-content-center align-items-center gap-2 mb-3 flex-wrap">
                                                <span><?php echo getFactionIcon($char['race']); ?></span>
                                                <span><?php echo getRaceIcon($char['race'], $char['gender']); ?></span>
                                                <span class="fw-bold text-warning"><?php echo htmlspecialchars($char['name']); ?></span>
                                            </div>
                                            <p><?php echo getClassIcon($char['class']); ?> <?php echo translate('label_level', 'Level'); ?> <?php echo $char['level']; ?></p>
                                            <p><?php echo translate('label_gold', 'Gold'); ?>: <span class="account-gold"><?php echo number_format($char['money'] / 10000, 2); ?>g</span></p>
                                            <p><?php echo translate('label_status', 'Status'); ?>: <?php echo getOnlineStatus($char['online']); ?></p>
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
                                                <div class="mb-3 form-group">
                                                    <label class="form-label" for="destination-<?php echo $char['guid']; ?>"><?php echo translate('label_select_city', 'Select a city'); ?></label>
                                                    <select class="form-select" id="destination-<?php echo $char['guid']; ?>" name="destination" required>
                                                        <option style="color: #000;" value="" selected><?php echo translate('select_city_placeholder', 'Select a city'); ?></option>
                                                        <option style="color: #000;" value="shattrath"><?php echo translate('city_shattrath', 'Shattrath'); ?></option>
                                                        <option style="color: #000;" value="dalaran"><?php echo translate('city_dalaran', 'Dalaran'); ?></option>
                                                    </select>
                                                </div>
                                                <button class="btn-account" type="submit" name="teleport_character" <?php echo $is_on_cooldown ? 'disabled' : ''; ?>><?php echo translate('button_teleport', 'Teleport'); ?></button>
                                                <?php if ($is_on_cooldown): ?>
                                                    <p class="mt-2 teleport-cooldown" data-cooldown="<?php echo $cooldown_remaining; ?>"><?php echo sprintf(translate('teleport_cooldown', 'Teleport Cooldown: %s minute%s'), $minutes, $minutes > 1 ? 's' : ''); ?></p>
                                                <?php endif; ?>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center"><?php echo translate('no_characters', 'You have no characters yet.'); ?></p>
                    <?php endif; ?>
                </div>

                <div class="tab-pane fade" id="activity" role="tabpanel">
                    <h2 class="h3 text-warning mb-4"><?php echo translate('section_account_activity', 'Account Activity'); ?></h2>
                    <?php if (!empty($activityLog)): ?>
                        <div class="table-responsive">
                            <table class="table account-table">
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
                                            <td data-label="<?php echo translate('table_action', 'Action'); ?>"><?php echo htmlspecialchars($log['action']); ?></td>
                                            <td data-label="<?php echo translate('table_character', 'Character'); ?>"><?php echo htmlspecialchars($log['character_name'] ?? translate('none', 'N/A')); ?></td>
                                            <td data-label="<?php echo translate('table_timestamp', 'Timestamp'); ?>"><?php echo date('Y-m-d H:i:s', $log['timestamp']); ?></td>
                                            <td data-label="<?php echo translate('table_details', 'Details'); ?>"><?php echo htmlspecialchars($log['details'] ?? translate('none', 'None')); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center"><?php echo translate('no_activity', 'No recent activity.'); ?></p>
                    <?php endif; ?>
                </div>

                <div class="tab-pane fade" id="security" role="tabpanel">
                    <div class="mb-4">
                        <h3 class="h4 text-warning"><?php echo translate('section_change_email', 'Change Email'); ?></h3>
                        <form method="post" class="row g-3 justify-content-center">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <div class="col-12 col-md-6 form-group">
                                <label class="form-label" for="current-password-email"><?php echo translate('label_current_password', 'Current Password'); ?></label>
                                <input class="form-control" type="password" id="current-password-email" name="current_password" required placeholder="<?php echo translate('placeholder_current_password', 'Enter current password'); ?>">
                            </div>
                            <div class="col-12 col-md-6 form-group">
                                <label class="form-label" for="new-email"><?php echo translate('label_new_email', 'New Email'); ?></label>
                                <input class="form-control" type="email" id="new-email" name="new_email" required minlength="3" maxlength="36" value="<?php echo htmlspecialchars($accountInfo['email'] ?? ''); ?>" placeholder="<?php echo translate('placeholder_new_email', 'Enter new email'); ?>">
                            </div>
                            <div class="col-12 text-center">
                                <button class="btn-account" type="submit" name="change_email"><?php echo translate('button_update_email', 'Update Email'); ?></button>
                            </div>
                        </form>
                    </div>

                    <div class="mb-4">
                        <h3 class="h4 text-warning"><?php echo translate('section_change_password', 'Change Password'); ?></h3>
                        <form method="post" class="row g-3 justify-content-center">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <div class="col-12 col-md-6 form-group">
                                <label class="form-label" for="current-password"><?php echo translate('label_current_password', 'Current Password'); ?></label>
                                <input class="form-control" type="password" id="current-password" name="current_password" required placeholder="<?php echo translate('placeholder_current_password', 'Enter current password'); ?>">
                            </div>
                            <div class="col-12 col-md-6 form-group">
                                <label class="form-label" for="new-password"><?php echo translate('label_new_password', 'New Password'); ?></label>
                                <input class="form-control" type="password" id="new-password" name="new_password" required minlength="6" maxlength="32" placeholder="<?php echo translate('placeholder_new_password', 'Enter new password'); ?>">
                            </div>
                            <div class="col-12 col-md-6 form-group">
                                <label class="form-label" for="confirm-password"><?php echo translate('label_confirm_password', 'Confirm New Password'); ?></label>
                                <input class="form-control" type="password" id="confirm-password" name="confirm_password" required minlength="6" maxlength="32" placeholder="<?php echo translate('placeholder_confirm_password', 'Confirm new password'); ?>">
                            </div>
                            <div class="col-12 text-center">
                                <button class="btn-account" type="submit" name="change_password"><?php echo translate('button_change_password', 'Change Password'); ?></button>
                            </div>
                        </form>
                    </div>

                    <div class="mb-4">
                        <h3 class="h4 text-warning"><?php echo translate('section_change_avatar', 'Change Avatar'); ?></h3>
                        <form method="post" class="row g-3 justify-content-center">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <div class="col-12">
                                <label class="form-label"><?php echo translate('label_select_avatar', 'Select Avatar'); ?></label>
                                <div class="row row-cols-2 row-cols-md-4 row-cols-lg-6 g-2 account-gallery">
                                    <?php foreach ($available_avatars as $avatar): ?>
                                        <div class="col text-center">
                                            <img src="<?php echo $base_path; ?>img/accountimg/profile_pics/<?php echo htmlspecialchars($avatar['filename']); ?>" 
                                                 class="<?php echo $currencies['avatar'] === $avatar['filename'] ? 'selected' : ''; ?>" 
                                                 onclick="selectAvatar('<?php echo htmlspecialchars($avatar['filename']); ?>')" 
                                                 alt="<?php echo htmlspecialchars(getAvatarDisplayName($avatar['filename'])); ?>">
                                            <span><?php echo htmlspecialchars(getAvatarDisplayName($avatar['filename'])); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="col text-center">
                                        <img src="<?php echo $base_path; ?>img/accountimg/profile_pics/user.jpg" 
                                             class="<?php echo empty($currencies['avatar']) ? 'selected' : ''; ?>" 
                                             onclick="selectAvatar('')" 
                                             alt="<?php echo translate('avatar_default', 'Default Avatar'); ?>">
                                        <span><?php echo translate('avatar_default', 'Default Avatar'); ?></span>
                                    </div>
                                </div>
                                <input type="hidden" name="avatar" id="avatar" value="<?php echo htmlspecialchars($currencies['avatar'] ?? ''); ?>">
                            </div>
                            <div class="col-12 text-center">
                                <button class="btn-account" type="submit" name="change_avatar"><?php echo translate('button_update_avatar', 'Update Avatar'); ?></button>
                            </div>
                        </form>
                    </div>

                    <div>
                        <h3 class="h4 text-warning"><?php echo translate('section_account_actions', 'Account Actions'); ?></h3>
                        <p class="text-center">
                            <a href="<?php echo $base_path; ?>logout" class="text-warning"><?php echo translate('action_logout', 'Logout'); ?></a> | 
                            <a href="#" class="text-danger"><?php echo translate('action_request_deletion', 'Request Account Deletion'); ?></a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php include_once $project_root . 'includes/footer.php'; ?>
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        function selectAvatar(filename) {
            document.getElementById('avatar').value = filename;
            document.querySelectorAll('.account-gallery img').forEach(img => {
                img.classList.remove('selected');
            });
            const selectedImg = document.querySelector(`.account-gallery img[onclick="selectAvatar('${filename}')"]`);
            if (selectedImg) {
                selectedImg.classList.add('selected');
            }
        }

        // Client-side countdown timer for teleport cooldown
        document.querySelectorAll('.teleport-cooldown').forEach(function(element) {
            let seconds = parseInt(element.dataset.cooldown);
            if (seconds > 0) {
                let timer = setInterval(function() {
                    seconds--;
                    let minutes = Math.ceil(seconds / 60);
                    let plural = minutes > 1 ? 's' : '';
                    element.textContent = '<?php echo translate('teleport_cooldown', 'Teleport Cooldown: %s minute%s'); ?>'.replace('%s', minutes).replace('%s', plural);
                    if (seconds <= 0) {
                        clearInterval(timer);
                        element.remove();
                        element.closest('form').querySelector('button').disabled = false;
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