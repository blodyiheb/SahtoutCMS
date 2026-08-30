<?php
ob_start(); // Start output buffering to catch any unexpected output
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../includes/paths.php'; // Include paths.php
require_once $project_root . 'includes/session.php';
require_once $project_root . 'includes/srp6.php';
require_once $project_root . 'includes/config.mail.php';
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

function isSmtpEnabled(): bool {
    global $smtp_enabled;
    return isset($smtp_enabled) && $smtp_enabled === true;
}

function sendAccountSecurityEmail(string $username, string $email, string $eventType, ?string $newEmail = null): bool {
    global $base_path;

    if (!isSmtpEnabled()) {
        return false;
    }

    try {
        $mail = getMailer();
        $mail->addAddress($email, $username);

        $logo_path = __DIR__ . '/../img/logo.png';
        if (file_exists($logo_path)) {
            $mail->AddEmbeddedImage($logo_path, 'logo_cid');
        }

        $is_email_change = $eventType === 'email_changed';
        $subject = $is_email_change
            ? translate('email_subject_email_changed', 'Account Email Changed')
            : translate('email_subject_password_changed', 'Account Password Changed');
        $success_message = $is_email_change
            ? translate('email_account_email_changed', 'The email address on your account has been changed.')
            : translate('email_account_password_changed', 'The password on your account has been changed.');
        $details = $is_email_change && $newEmail
            ? sprintf(translate('email_account_new_email', 'New email address: %s'), htmlspecialchars($newEmail))
            : translate('email_account_password_notice', 'You can continue logging in with your new password.');
        $warning = translate('email_contact_support', 'If you did not perform this action, please contact support immediately.');

        $mail->Subject = $subject;
        $mail->Body = "<html><body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #0a0e16; color: #d8d8d8;'>";
        $mail->Body .= "<div style='background: linear-gradient(135deg, #161920, #0a0e16); border: 1px solid rgba(201,162,39,0.22); padding: 30px; border-radius: 8px;'>";

        if (file_exists($logo_path)) {
            $mail->Body .= "<div style='text-align: center; margin-bottom: 20px;'><img src='cid:logo_cid' alt='Sahtout logo' style='max-width: 200px;'></div>";
        }

        $greeting = translate('email_greeting', 'Welcome, {username}!');
        $mail->Body .= "<h2 style='color: #f2cf5b; font-family: Cinzel, serif; text-align: center;'>" . str_replace('{username}', htmlspecialchars($username), $greeting) . "</h2>";
        $mail->Body .= "<div style='text-align: center; background: rgba(46, 204, 113, 0.1); border: 1px solid rgba(46, 204, 113, 0.3); padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        $mail->Body .= "<p style='font-size: 18px; color: #2ecc71;'>" . $success_message . "</p>";
        $mail->Body .= "<p style='font-size: 14px; color: #d8d8d8;'>" . $details . "</p>";
        $mail->Body .= "</div>";
        $mail->Body .= "<div style='background: rgba(231, 76, 60, 0.1); border: 1px solid rgba(231, 76, 60, 0.2); padding: 15px; border-radius: 8px; margin: 20px 0;'>";
        $mail->Body .= "<p style='font-size: 13px; color: #e74c3c; text-align: center;'>" . $warning . "</p>";
        $mail->Body .= "</div>";
        $mail->Body .= "</div></body></html>";

        $mail->AltBody = $success_message . "\n\n" . html_entity_decode(strip_tags($details), ENT_QUOTES, 'UTF-8') . "\n\n" . $warning . "\n\n" . translate('login_button', 'Login Now') . ': ' . $base_path . 'login';

        if (!$mail->send()) {
            error_log("Failed to send account security email to {$email}: " . $mail->ErrorInfo);
            return false;
        }

        return true;
    } catch (Exception $e) {
        error_log("Account security email failed for {$email}: " . $e->getMessage());
        return false;
    }
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
            $stmt_current = $auth_db->prepare("SELECT email FROM account WHERE id = ?");
            $stmt_current->bind_param('i', $_SESSION['user_id']);
            $stmt_current->execute();
            $result_current = $stmt_current->get_result();
            $current_email = $result_current->num_rows === 1 ? $result_current->fetch_assoc()['email'] : '';
            $stmt_current->close();

            if (strcasecmp(trim($new_email), trim($current_email)) === 0) {
                throw new Exception(translate('error_email_same_as_current', 'New email must be different from your current email'));
            }

            // Check if email is used by another account
            $stmt_check_email = $auth_db->prepare("SELECT id FROM account WHERE email = ? AND id != ?");
            $stmt_check_email->bind_param('si', $new_email, $_SESSION['user_id']);
            $stmt_check_email->execute();
            $result = $stmt_check_email->get_result();
            if ($result->num_rows > 0) {
                throw new Exception(translate('error_email_in_use', 'Email already in use by another account'));
            }
            $stmt_check_email->close();

            // Verify current password
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
            $stmt_update = $auth_db->prepare("UPDATE account SET email = ?, reg_mail = ? WHERE id = ?");
            $stmt_update->bind_param('ssi', $new_email, $new_email, $_SESSION['user_id']);
            if (!$stmt_update->execute()) {
                throw new Exception(translate('error_updating_email', 'Error updating email'));
            }
            $stmt_update->close();

            // Log action
            $stmt_log = $site_db->prepare("INSERT INTO website_activity_log (account_id, character_name, action, timestamp, details) VALUES (?, NULL, ?, UNIX_TIMESTAMP(), ?)");
            $action = translate('action_email_changed', 'Email Changed');
            $stmt_log->bind_param('iss', $_SESSION['user_id'], $action, $new_email);
            $stmt_log->execute();
            $stmt_log->close();

            if ($current_email !== '') {
                sendAccountSecurityEmail($_SESSION['username'], $current_email, 'email_changed', $new_email);
            }

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
            if (strlen($new_password) < 6 || strlen($new_password) > 16) {
                throw new Exception(translate('error_password_length', 'Password must be between 6 and 16 characters'));
            }

            $stmt = $auth_db->prepare("SELECT salt, verifier, email FROM account WHERE id = ?");
            $stmt->bind_param('i', $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows !== 1) {
                throw new Exception(translate('error_account_not_found', 'Account not found'));
            }
            
            $row = $result->fetch_assoc();
            $account_email = $row['email'] ?? '';
            if (!SRP6::VerifyPassword($_SESSION['username'], $current_password, $row['salt'], $row['verifier'])) {
                throw new Exception(translate('error_incorrect_password', 'Current password is incorrect'));
            }
            $stmt->close();

            $new_salt = SRP6::GenerateSalt();
            $new_verifier = SRP6::CalculateVerifier($_SESSION['username'], $new_password, $new_salt);
            
            $update = $auth_db->prepare("UPDATE account SET salt = ?, verifier = ? WHERE id = ?");
            $update->bind_param('ssi', $new_salt, $new_verifier, $_SESSION['user_id']);
            if (!$update->execute()) {
                throw new Exception(translate('error_updating_password', 'Error updating password'));
            }
            $update->close();

            // Log action
            $stmt_log = $site_db->prepare("INSERT INTO website_activity_log (account_id, character_name, action, timestamp) VALUES (?, NULL, ?, UNIX_TIMESTAMP())");
            $action = translate('action_password_changed', 'Password Changed');
            $stmt_log->bind_param('is', $_SESSION['user_id'], $action);
            $stmt_log->execute();
            $stmt_log->close();

            if ($account_email !== '') {
                sendAccountSecurityEmail($_SESSION['username'], $account_email, 'password_changed');
            }

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
            $stmt_teleport = $char_db->prepare("UPDATE characters SET map = ?, position_x = ?, position_y = ?, position_z = ?, orientation = ? WHERE guid = ?");
            $stmt_teleport->bind_param('iddddi', $data['map'], $data['x'], $data['y'], $data['z'], $data['o'], $guid);
            if (!$stmt_teleport->execute()) {
                throw new Exception(translate('error_teleporting_character', 'Error teleporting character'));
            }
            $stmt_teleport->close();

            // Log teleport
            $stmt_cooldown = $site_db->prepare("INSERT INTO character_teleport_log (account_id, character_guid, character_name, teleport_timestamp) VALUES (?, ?, ?, UNIX_TIMESTAMP()) ON DUPLICATE KEY UPDATE teleport_timestamp = UNIX_TIMESTAMP(), character_name = ?");
            $stmt_cooldown->bind_param('iiss', $_SESSION['user_id'], $guid, $character_name, $character_name);
            if (!$stmt_cooldown->execute()) {
                throw new Exception(translate('error_logging_teleport', 'Error logging teleport'));
            }
            $stmt_cooldown->close();

            // Update session cooldown
            $_SESSION['teleport_cooldowns'][$guid] = $current_time;

            // Log action
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

            $stmt = $site_db->prepare("UPDATE user_currencies SET avatar = ? WHERE account_id = ?");
            $stmt->bind_param('si', $avatar, $_SESSION['user_id']);
            if (!$stmt->execute()) {
                throw new Exception(translate('error_updating_avatar', 'Error updating avatar'));
            }
            $stmt->close();

            // Update session avatar for header.php
            $_SESSION['avatar'] = $avatar;

            // Log action
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
    $stmt = $auth_db->prepare("SELECT id, username, email, joindate, last_login, locked, online, expansion FROM account WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $accountInfo = $result->fetch_assoc();
    }
    $stmt->close();

    // Check ban status
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
    $stmt = $site_db->prepare("SELECT action, timestamp, details, character_name FROM website_activity_log WHERE account_id = ? ORDER BY timestamp DESC LIMIT 10");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $activityLog = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Get Points, Tokens, and Avatar
    $stmt = $site_db->prepare("SELECT points, tokens, avatar FROM user_currencies WHERE account_id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $currencies = $result->fetch_assoc();
    }
    $stmt->close();

    // Get available avatars
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
        return sprintf('<span class="bg-red-900/50 text-red-300 border border-red-700/50 px-3 py-1 rounded-full text-xs font-semibold inline-block">%s (Reason: %s, Until: %s)</span>', translate('status_banned', 'Banned'), $reason, $unbanDate);
    }
    switch ($locked) {
        case 1: return sprintf('<span class="bg-red-900/50 text-red-300 border border-red-700/50 px-3 py-1 rounded-full text-xs font-semibold inline-block">%s</span>', translate('status_banned', 'Banned'));
        case 2: return sprintf('<span class="bg-blue-900/50 text-blue-300 border border-blue-700/50 px-3 py-1 rounded-full text-xs font-semibold inline-block">%s</span>', translate('status_frozen', 'Frozen'));
        default: return sprintf('<span class="bg-green-900/50 text-green-300 border border-green-700/50 px-3 py-1 rounded-full text-xs font-semibold inline-block">%s</span>', translate('status_active', 'Active'));
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
    return $online ? sprintf('<span class="text-green-400 font-semibold text-sm"><i class="fas fa-circle text-[8px] mr-1"></i>%s</span>', translate('status_online', 'Online')) : sprintf('<span class="text-red-400 font-semibold text-sm"><i class="fas fa-circle text-[8px] mr-1"></i>%s</span>', translate('status_offline', 'Offline'));
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
    return sprintf('<img src="%simg/accountimg/race/%s/%s" alt="%s" class="w-6 h-6 inline-block rounded-full">', $base_path, $gender_folder, $image, translate('race_icon', 'Race Icon'));
}

function getClassIcon($class) {
    global $base_path;
    $icons = [
        1 => 'warrior.webp', 2 => 'paladin.webp', 3 => 'hunter.webp', 4 => 'rogue.webp',
        5 => 'priest.webp', 6 => 'deathknight.webp', 7 => 'shaman.webp', 8 => 'mage.webp',
        9 => 'warlock.webp', 11 => 'druid.webp'
    ];
    return sprintf('<img src="%simg/accountimg/class/%s" alt="%s" class="w-6 h-6 inline-block rounded-full">', $base_path, ($icons[$class] ?? 'default.jpg'), translate('class_icon', 'Class Icon'));
}

function getFactionIcon($race) {
    global $base_path;
    $allianceRaces = [1, 3, 4, 7, 11];
    $faction = in_array($race, $allianceRaces) ? 'alliance.png' : 'horde.png';
    return sprintf('<img src="%simg/accountimg/faction/%s" alt="%s" class="w-5 h-5 inline-block">', $base_path, $faction, translate('faction_icon', 'Faction Icon'));
}

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
    
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/tailwind.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    
    <style>
        /* Page background - Show full background image without overlay */
        body {
            background: url('<?php echo $base_path; ?>img/backgrounds/bg-account.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            padding-top: 112px;
            margin: 0;
            position: relative;
        }
        
        /* REMOVED: Dark overlay that was hiding the background */
        
        /* Main content wrapper */
        .account-content {
            position: relative;
            z-index: 1;
        }
        
        /* Glass effect container - Darker to improve readability */
        .glass-container {
            background: rgba(5, 7, 11, 0.85);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(201,162,39,.22);
            border-radius: 0;
            padding: 2.5rem 2.5rem;
            box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.8), inset 0 0 60px rgba(0,0,0,.25);
            position: relative;
        }
        
        .glass-container::before {
            content: ''; position: absolute; inset: 5px;
            border: 1px solid rgba(201,162,39,.14);
            pointer-events: none;
        }
        
        .glass-container::after {
            content: ''; position: absolute; inset: 0; pointer-events: none;
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
        
        /* Wow title gradient */
        .wow-title {
            font-family: 'Cinzel', serif;
            font-weight: 900;
            background: linear-gradient(180deg, #fff7d6 0%, #f2cf5b 35%, #c9a227 62%, #8a6a14 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,.9));
            letter-spacing: .02em;
        }
        
        /* Glass cards */
        .glass-card {
            background: rgba(10, 14, 22, 0.75);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            border: 1px solid rgba(201,162,39,.15);
            border-radius: 0;
            transition: all 0.3s ease;
        }
        
        .glass-card:hover {
            border-color: rgba(201,162,39,.3);
        }
        
        /* Profile avatar */
        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid #f2cf5b;
            object-fit: cover;
        }
        
        /* Avatar selector */
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
            border-color: #f2cf5b;
            box-shadow: 0 0 20px rgba(242, 207, 82, 0.4);
            transform: scale(1.05);
        }
        
        .avatar-selector img:hover {
            transform: scale(1.08);
            border-color: #7a8aa0;
        }
        
        /* Cooldown timer */
        .cooldown-timer {
            color: #f5a842;
            font-size: 0.8rem;
            background: rgba(31, 42, 54, 0.6);
            padding: 0.2rem 0.8rem;
            border-radius: 40px;
            display: inline-block;
        }
        
        /* Force text wrapping to prevent overflow */
        .force-wrap {
            min-width: 0;            /* Prevents flex/grid items from expanding */
            overflow-wrap: anywhere; /* Forces breaks anywhere if needed */
            word-break: break-word;  /* Breaks words to prevent overflow */
        }

        /* Responsive */
        @media (max-width: 767px) {
            body {
                padding-top: 96px;
            }
            
            .glass-container {
                padding: 1.5rem 0.75rem;
            }
        }
    </style>
</head>
<body>

<div class="account-content min-h-screen flex items-start justify-center px-4 md:px-8 py-8">
    <div class="container mx-auto max-w-7xl px-2 sm:px-4">
        
        <!-- Main Container -->
        <div class="glass-container">
            
            <!-- Page Title -->
            <div class="flex flex-wrap items-center gap-4 mb-6">
                <i class="fas fa-shield-halved text-4xl text-[#f2cf5b]"></i>
                <h1 class="wow-title text-3xl md:text-5xl font-bold">
                    Account <span class="text-[#f2cf5b]">Dashboard</span>
                </h1>
                <?php if (!empty($accountInfo['last_login'])): ?>
                    <span class="ml-auto text-sm bg-black/40 px-4 py-1.5 border border-[rgba(201,162,39,0.15)] text-gray-300">
                        <i class="far fa-clock mr-1"></i> Last login: <?php echo htmlspecialchars($accountInfo['last_login']); ?>
                    </span>
                <?php endif; ?>
            </div>

            <!-- Alert Messages -->
            <?php if ($message): ?>
                <div class="bg-green-900/40 border border-green-600/40 text-green-200 px-5 py-3 flex items-center gap-3 mb-4">
                    <i class="fas fa-check-circle text-green-400 text-lg"></i>
                    <span><?php echo htmlspecialchars($message); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-900/40 border border-red-600/40 text-red-200 px-5 py-3 flex items-center gap-3 mb-4">
                    <i class="fas fa-exclamation-circle text-red-400 text-lg"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($debug_errors) && ($role === 'admin' || $gmlevel > 0)): ?>
                <div class="bg-yellow-900/30 border border-yellow-600/30 text-yellow-200 px-5 py-3 flex items-start gap-3 mb-4">
                    <i class="fas fa-exclamation-triangle text-yellow-400 mt-1"></i>
                    <div>
                        <strong><?php echo translate('debug_warnings', 'Debug Warnings'); ?>:</strong><br>
                        <?php echo htmlspecialchars(implode('<br>', array_unique($debug_errors))); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Navigation Tabs -->
            <div class="grid grid-cols-2 sm:flex sm:flex-wrap sm:items-center gap-2 pb-4 mb-6 border-b border-[rgba(201,162,39,0.15)]">
                <button class="nav-tab-gaming active px-5 py-2.5 font-semibold text-sm border-2 rounded-none transition-all duration-300 bg-[rgba(10,14,22,0.6)] border-[rgba(201,162,39,0.2)] text-gray-300 hover:border-[#f2cf5b] hover:text-[#f2cf5b] hover:bg-[rgba(242,207,82,0.05)] flex items-center justify-center gap-2 min-w-0 force-wrap" data-tab="overview">
                    <i class="fas fa-chart-pie"></i> <?php echo translate('tab_overview', 'Overview'); ?>
                </button>
                <button class="nav-tab-gaming px-5 py-2.5 font-semibold text-sm border-2 rounded-none transition-all duration-300 bg-[rgba(10,14,22,0.6)] border-[rgba(201,162,39,0.2)] text-gray-300 hover:border-[#f2cf5b] hover:text-[#f2cf5b] hover:bg-[rgba(242,207,82,0.05)] flex items-center justify-center gap-2 min-w-0 force-wrap" data-tab="characters">
                    <i class="fas fa-users"></i> <?php echo translate('tab_characters', 'Characters'); ?>
                </button>
                <button class="nav-tab-gaming px-5 py-2.5 font-semibold text-sm border-2 rounded-none transition-all duration-300 bg-[rgba(10,14,22,0.6)] border-[rgba(201,162,39,0.2)] text-gray-300 hover:border-[#f2cf5b] hover:text-[#f2cf5b] hover:bg-[rgba(242,207,82,0.05)] flex items-center justify-center gap-2 min-w-0 force-wrap" data-tab="activity">
                    <i class="fas fa-history"></i> <?php echo translate('tab_activity', 'Activity'); ?>
                </button>
                <button class="nav-tab-gaming px-5 py-2.5 font-semibold text-sm border-2 rounded-none transition-all duration-300 bg-[rgba(10,14,22,0.6)] border-[rgba(201,162,39,0.2)] text-gray-300 hover:border-[#f2cf5b] hover:text-[#f2cf5b] hover:bg-[rgba(242,207,82,0.05)] flex items-center justify-center gap-2 min-w-0 force-wrap" data-tab="security">
                    <i class="fas fa-shield-alt"></i> <?php echo translate('tab_security', 'Security'); ?>
                </button>
                <a href="<?php echo $base_path; ?>vote" class="ml-auto col-span-2 px-5 py-2.5 font-semibold text-sm border-2 rounded-none transition-all duration-300 bg-[rgba(10,14,22,0.6)] border-[rgba(201,162,39,0.2)] text-gray-300 hover:border-[#f2cf5b] hover:text-[#f2cf5b] hover:bg-[rgba(242,207,82,0.05)] flex items-center justify-center gap-2 min-w-0 force-wrap">
                    <i class="fas fa-vote-yea"></i> <?php echo translate('tab_vote', 'Vote'); ?>
                </a>
            </div>

            <!-- Tab Content -->
            <div>
                
                <!-- OVERVIEW TAB -->
                <div class="tab-pane active" id="tab-overview">
                    <!-- Account Information -->
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-white flex items-center gap-3 mb-4">
                            <i class="fas fa-info-circle text-[#f2cf5b]"></i>
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
                                <i class="fas fa-envelope text-3xl text-[#f2cf5b] mb-2"></i>
                                <h3 class="text-xl font-bold text-white"><?php echo translate('card_contact', 'Contact'); ?></h3>
                                <p class="text-sm text-gray-300 mt-2">
                                    <i class="far fa-envelope mr-1 text-gray-400"></i>
                                    <?php echo htmlspecialchars($accountInfo['email'] ?? translate('email_not_set', 'Not set')); ?>
                                </p>
                                <p class="text-sm mt-1">
                                    <span class="text-[#f2cf5b]">
                                        <i class="fas fa-expand-alt mr-1"></i>
                                        <?php echo translate('expansion_' . ($accountInfo['expansion'] ?? 2), ($accountInfo['expansion'] ?? 2) == 2 ? 'Wrath of the Lich King' : ($accountInfo['expansion'] == 1 ? 'The Burning Crusade' : 'Classic')); ?>
                                    </span>
                                </p>
                                <?php if ($role === 'admin' || $role === 'moderator' || $gmlevel > 0): ?>
                                    <div class="mt-4">
                                        <a href="<?php echo $base_path; ?>admin/dashboard" class="inline-block px-6 py-2 bg-[rgba(242,207,82,0.15)] border border-[rgba(201,162,39,0.3)] text-[#f2cf5b] hover:bg-[rgba(242,207,82,0.25)] transition-all duration-300 text-sm font-semibold">
                                            <i class="fas fa-crown mr-1"></i> <?php echo translate('button_admin_panel', 'Admin Panel'); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Activity Card -->
                            <div class="glass-card p-6 text-center">
                                <i class="fas fa-calendar-alt text-3xl text-[#f2cf5b] mb-2"></i>
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
                            <i class="fas fa-chart-simple text-[#f2cf5b]"></i>
                            <?php echo translate('section_quick_stats', 'Quick Stats'); ?>
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="glass-card p-6 grid grid-cols-2 md:flex md:items-center md:justify-between gap-4 md:gap-0">
                                <div class="col-span-2 md:col-span-1 flex items-center justify-center md:justify-start"><i class="fas fa-user-friends text-3xl text-[#f2cf5b]"></i></div>
                                <div class="text-center min-w-0">
                                    <p class="text-2xl font-bold text-white force-wrap"><?php echo count($characters); ?></p>
                                    <p class="text-gray-400 text-sm"><?php echo translate('label_total_characters', 'Total Characters'); ?></p>
                                </div>
                                <div class="text-center min-w-0">
                                    <?php 
                                        $maxLevel = 0;
                                        foreach ($characters as $char) {
                                            if ($char['level'] > $maxLevel) $maxLevel = $char['level'];
                                        }
                                    ?>
                                    <p class="text-2xl font-bold text-white force-wrap"><?php echo $maxLevel; ?></p>
                                    <p class="text-gray-400 text-sm"><?php echo translate('label_highest_level', 'Highest Level'); ?></p>
                                </div>
                            </div>
                            <div class="glass-card p-6 grid grid-cols-2 md:flex md:items-center md:justify-between gap-4 md:gap-0">
                                <div class="flex items-center justify-center md:justify-start"><i class="fas fa-coins text-3xl text-[#f2cf5b]"></i></div>
                                <div class="text-center min-w-0">
                                    <?php 
                                        $totalGold = 0;
                                        foreach ($characters as $char) {
                                            $totalGold += $char['money'];
                                        }
                                    ?>
                                    <p class="text-2xl font-bold text-white force-wrap"><?php echo number_format($totalGold / 10000, 2); ?><span class="text-sm text-gray-400 ml-1">g</span></p>
                                    <p class="text-gray-400 text-sm"><?php echo translate('label_total_gold', 'Total Gold'); ?></p>
                                </div>
                                <div class="text-center min-w-0">
                                    <p class="text-2xl font-bold text-white force-wrap"><?php echo $currencies['points']; ?> <span class="text-sm text-gray-400">P</span></p>
                                    <p class="text-gray-400 text-sm"><?php echo translate('label_points', 'Points'); ?></p>
                                </div>
                                <div class="text-center min-w-0">
                                    <p class="text-2xl font-bold text-white force-wrap"><?php echo $currencies['tokens']; ?> <span class="text-sm text-gray-400">T</span></p>
                                    <p class="text-gray-400 text-sm"><?php echo translate('label_tokens', 'Tokens'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CHARACTERS TAB -->
                <div class="tab-pane hidden" id="tab-characters">
                    <h2 class="text-2xl font-bold text-white flex items-center gap-3 mb-4">
                        <i class="fas fa-users text-[#f2cf5b]"></i>
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
                                        <span class="text-[#f2cf5b]"><?php echo number_format($char['money'] / 10000, 2); ?>g</span>
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
                                        <div class="flex flex-wrap gap-2">
                                            <select class="flex-1 min-w-[100px] px-3 py-1.5 bg-[rgba(0,0,0,0.4)] border border-[rgba(201,162,39,0.2)] text-gray-200 text-sm focus:border-[#f2cf5b] focus:outline-none" name="destination" required>
                                                <option value=""><?php echo translate('select_city_placeholder', 'Select city'); ?></option>
                                                <option value="shattrath"><?php echo translate('city_shattrath', 'Shattrath'); ?></option>
                                                <option value="dalaran"><?php echo translate('city_dalaran', 'Dalaran'); ?></option>
                                            </select>
                                            <button class="px-4 py-1.5 bg-[rgba(242,207,82,0.15)] border border-[rgba(201,162,39,0.3)] text-[#f2cf5b] hover:bg-[rgba(242,207,82,0.25)] transition-all duration-300 text-sm font-semibold whitespace-nowrap" type="submit" name="teleport_character" <?php echo $is_on_cooldown ? 'disabled' : ''; ?>>
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
                <div class="tab-pane hidden" id="tab-activity">
                    <h2 class="text-2xl font-bold text-white flex items-center gap-3 mb-4">
                        <i class="fas fa-history text-[#f2cf5b]"></i>
                        <?php echo translate('section_account_activity', 'Account Activity'); ?>
                    </h2>
                    
                    <?php if (!empty($activityLog)): ?>
                        <div class="glass-card overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-[rgba(201,162,39,0.1)] border-b border-[rgba(201,162,39,0.15)] text-[#f2cf5b] uppercase text-xs font-semibold">
                                            <th class="py-3 px-4 text-left whitespace-nowrap"><?php echo translate('table_action', 'Action'); ?></th>
                                            <th class="py-3 px-4 text-left whitespace-nowrap"><?php echo translate('table_character', 'Character'); ?></th>
                                            <th class="py-3 px-4 text-left whitespace-nowrap"><?php echo translate('table_timestamp', 'Timestamp'); ?></th>
                                            <th class="py-3 px-4 text-left whitespace-nowrap"><?php echo translate('table_details', 'Details'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[rgba(201,162,39,0.05)]">
                                        <?php foreach ($activityLog as $log): ?>
                                            <tr class="hover:bg-[rgba(242,207,82,0.05)] transition-colors">
                                                <td class="py-3 px-4 text-gray-300 whitespace-nowrap"><?php echo htmlspecialchars($log['action']); ?></td>
                                                <td class="py-3 px-4 text-gray-300 whitespace-nowrap"><?php echo htmlspecialchars($log['character_name'] ?? translate('none', 'N/A')); ?></td>
                                                <td class="py-3 px-4 text-gray-300 whitespace-nowrap"><?php echo date('Y-m-d H:i:s', $log['timestamp']); ?></td>
                                                <td class="py-3 px-4 text-gray-300"><?php echo htmlspecialchars($log['details'] ?? translate('none', 'None')); ?></td>
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
                <div class="tab-pane hidden" id="tab-security">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Change Email -->
                        <div class="glass-card p-6">
                            <h3 class="text-xl font-bold text-[#f2cf5b] flex items-center gap-2">
                                <i class="fas fa-envelope"></i> <?php echo translate('section_change_email', 'Change Email'); ?>
                            </h3>
                            <form method="post" class="mt-4 space-y-4">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <div>
                                    <label class="block text-sm text-gray-300 mb-1"><?php echo translate('label_current_password', 'Current Password'); ?></label>
                                    <input type="password" class="w-full px-4 py-2 bg-[rgba(0,0,0,0.4)] border border-[rgba(201,162,39,0.2)] text-gray-200 focus:border-[#f2cf5b] focus:outline-none transition-colors" name="current_password" required maxlength="16" placeholder="<?php echo translate('placeholder_current_password', 'Enter current password'); ?>">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-300 mb-1"><?php echo translate('label_new_email', 'New Email'); ?></label>
                                    <input type="email" class="w-full px-4 py-2 bg-[rgba(0,0,0,0.4)] border border-[rgba(201,162,39,0.2)] text-gray-200 focus:border-[#f2cf5b] focus:outline-none transition-colors" name="new_email" required value="<?php echo htmlspecialchars($accountInfo['email'] ?? ''); ?>" placeholder="<?php echo translate('placeholder_new_email', 'Enter new email'); ?>">
                                </div>
                                <button class="px-6 py-2 bg-[rgba(242,207,82,0.15)] border border-[rgba(201,162,39,0.3)] text-[#f2cf5b] hover:bg-[rgba(242,207,82,0.25)] transition-all duration-300 font-semibold" type="submit" name="change_email">
                                    <i class="fas fa-save mr-2"></i><?php echo translate('button_update_email', 'Update Email'); ?>
                                </button>
                            </form>
                        </div>

                        <!-- Change Password -->
                        <div class="glass-card p-6">
                            <h3 class="text-xl font-bold text-[#f2cf5b] flex items-center gap-2">
                                <i class="fas fa-key"></i> <?php echo translate('section_change_password', 'Change Password'); ?>
                            </h3>
                            <form method="post" class="mt-4 space-y-4">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <div>
                                    <label class="block text-sm text-gray-300 mb-1"><?php echo translate('label_current_password', 'Current Password'); ?></label>
                                    <input type="password" class="w-full px-4 py-2 bg-[rgba(0,0,0,0.4)] border border-[rgba(201,162,39,0.2)] text-gray-200 focus:border-[#f2cf5b] focus:outline-none transition-colors" name="current_password" required maxlength="16" placeholder="<?php echo translate('placeholder_current_password', 'Enter current password'); ?>">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-300 mb-1"><?php echo translate('label_new_password', 'New Password'); ?></label>
                                    <input type="password" class="w-full px-4 py-2 bg-[rgba(0,0,0,0.4)] border border-[rgba(201,162,39,0.2)] text-gray-200 focus:border-[#f2cf5b] focus:outline-none transition-colors" name="new_password" required minlength="6" maxlength="16" placeholder="<?php echo translate('placeholder_new_password', 'Enter new password'); ?>">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-300 mb-1"><?php echo translate('label_confirm_password', 'Confirm New Password'); ?></label>
                                    <input type="password" class="w-full px-4 py-2 bg-[rgba(0,0,0,0.4)] border border-[rgba(201,162,39,0.2)] text-gray-200 focus:border-[#f2cf5b] focus:outline-none transition-colors" name="confirm_password" required minlength="6" maxlength="16" placeholder="<?php echo translate('placeholder_confirm_password', 'Confirm new password'); ?>">
                                </div>
                                <button class="px-6 py-2 bg-[rgba(242,207,82,0.15)] border border-[rgba(201,162,39,0.3)] text-[#f2cf5b] hover:bg-[rgba(242,207,82,0.25)] transition-all duration-300 font-semibold" type="submit" name="change_password">
                                    <i class="fas fa-lock mr-2"></i><?php echo translate('button_change_password', 'Change Password'); ?>
                                </button>
                            </form>
                        </div>

                        <!-- Change Avatar -->
                        <div class="lg:col-span-2 glass-card p-6">
                            <h3 class="text-xl font-bold text-[#f2cf5b] flex items-center gap-2">
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
                                <button class="px-6 py-2 bg-[rgba(242,207,82,0.15)] border border-[rgba(201,162,39,0.3)] text-[#f2cf5b] hover:bg-[rgba(242,207,82,0.25)] transition-all duration-300 font-semibold mt-4" type="submit" name="change_avatar">
                                    <i class="fas fa-check mr-2"></i><?php echo translate('button_update_avatar', 'Update Avatar'); ?>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Account Actions -->
                    <div class="glass-card p-6 mt-8">
                        <h3 class="text-xl font-bold text-[#f2cf5b] flex items-center gap-2">
                            <i class="fas fa-cog"></i> <?php echo translate('section_account_actions', 'Account Actions'); ?>
                        </h3>
                        <div class="flex flex-wrap justify-center gap-4 mt-4">
                            <a href="<?php echo $base_path; ?>logout" class="text-[#f2cf5b] hover:text-yellow-400 transition">
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
            document.querySelectorAll('.nav-tab-gaming').forEach(t => {
                t.classList.remove('active');
                t.style.background = 'rgba(10,14,22,0.6)';
                t.style.borderColor = 'rgba(201,162,39,0.2)';
                t.style.color = '#d1d5db';
            });
            
            // Add active class to clicked tab
            this.classList.add('active');
            this.style.background = 'rgba(242,207,82,0.15)';
            this.style.borderColor = '#f2cf5b';
            this.style.color = '#f2cf5b';
            
            // Get tab ID
            const tabId = this.getAttribute('data-tab');
            
            // Hide all tab panes
            document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.add('hidden'));
            document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
            
            // Show the corresponding tab pane
            const targetPane = document.getElementById('tab-' + tabId);
            if (targetPane) {
                targetPane.classList.remove('hidden');
                targetPane.classList.add('active');
            }
        });
    });

    // Set initial active tab state
    document.querySelectorAll('.nav-tab-gaming').forEach(tab => {
        if (tab.classList.contains('active')) {
            tab.style.background = 'rgba(242,207,82,0.15)';
            tab.style.borderColor = '#f2cf5b';
            tab.style.color = '#f2cf5b';
        }
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
