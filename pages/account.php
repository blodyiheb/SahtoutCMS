<?php
ob_start(); // Start output buffering to catch any unexpected output
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../includes/paths.php'; // Include paths.php
require_once $project_root . 'includes/session.php';
require_once $project_root . 'includes/srp6.php';
require_once $project_root . 'includes/config.mail.php';
require_once $project_root . 'languages/language.php'; // Include language file for translations
require_once $project_root . 'includes/config.settings.php';

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
    return !empty($smtp_enabled);
}

function sendAccountSecurityEmail(string $username, string $email, string $eventType, ?string $targetEmail = null): bool {
    global $base_path;

    if (!isSmtpEnabled() || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    try {
        $mail = getMailer();
        $mail->addAddress($email, $username);

        $logo_path = __DIR__ . '/../img/logo.png';
        if (file_exists($logo_path)) {
            $mail->AddEmbeddedImage($logo_path, 'logo_cid');
        }

        $is_old_email_alert = ($eventType === 'email_changed_old');
        $is_new_email_welcome = ($eventType === 'email_changed_new');
        $is_password_change = ($eventType === 'password_changed');

        if ($is_old_email_alert) {
            $subject = translate('email_subject_email_changed', 'Account Email Changed');
            $success_message = translate('email_account_email_changed', 'The email address on your account has been changed.');
            $details = $targetEmail ? sprintf(translate('email_account_new_email', 'New email address: %s'), htmlspecialchars($targetEmail)) : '';
            $warning = translate('email_contact_support', 'If you did not perform this action, please contact support immediately.');
        } elseif ($is_new_email_welcome) {
            $subject = translate('email_subject_new_email_added', 'SahtoutCMS Account Added to Your Email');
            $success_message = translate('email_account_added_welcome', 'This email address has been successfully linked to your SahtoutCMS account.');
            $details = sprintf(translate('email_account_username_notice', 'Account Username: %s'), htmlspecialchars($username));
            $warning = translate('email_contact_support_new', 'If you did not request this change, please contact support immediately.');
        } else {
            $subject = translate('email_subject_password_changed', 'Account Password Changed');
            $success_message = translate('email_account_password_changed', 'The password on your account has been changed.');
            $details = translate('email_account_password_notice', 'You can continue logging in with your new password.');
            $warning = translate('email_contact_support', 'If you did not perform this action, please contact support immediately.');
        }

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
        if (!empty($details)) {
            $mail->Body .= "<p style='font-size: 14px; color: #d8d8d8;'>" . $details . "</p>";
        }
        $mail->Body .= "</div>";
        $mail->Body .= "<div style='background: rgba(231, 76, 60, 0.1); border: 1px solid rgba(231, 76, 60, 0.2); padding: 15px; border-radius: 8px; margin: 20px 0;'>";
        $mail->Body .= "<p style='font-size: 13px; color: #e74c3c; text-align: center;'>" . $warning . "</p>";
        $mail->Body .= "</div>";
        $mail->Body .= "</div></body></html>";

        $mail->AltBody = $success_message . "\n\n" . (!empty($details) ? html_entity_decode(strip_tags($details), ENT_QUOTES, 'UTF-8') . "\n\n" : '') . $warning . "\n\n" . translate('login_button', 'Login Now') . ': ' . $base_path . 'login';

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
    $submitted_csrf = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
    $session_csrf = is_string($_SESSION['csrf_token'] ?? null) ? $_SESSION['csrf_token'] : '';
    if ($submitted_csrf === '' || !hash_equals($session_csrf, $submitted_csrf)) {
        $_SESSION['error'] = translate('error_invalid_form_submission', 'Invalid form submission');
        header("Location: {$base_path}account");
        exit();
    }

    // Handle email change
    if (isset($_POST['change_email'])) {
        $new_email = trim($_POST['new_email'] ?? '');
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
                sendAccountSecurityEmail($_SESSION['username'], $current_email, 'email_changed_old', $new_email);
            }

            // Welcome notification to the newly linked email address
            sendAccountSecurityEmail($_SESSION['username'], $new_email, 'email_changed_new');

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

    // Handle character teleport - fires ONLY when the teleport trigger, a valid
    // guid and a destination are all explicitly present in the POST payload.
    if (isset($_POST['teleport_character'])
        && isset($_POST['guid'], $_POST['destination'])
        && $_POST['guid'] !== ''
        && $_POST['destination'] !== ''
        && filter_var($_POST['guid'], FILTER_VALIDATE_INT) !== false) {
        $guid = (int) filter_var($_POST['guid'], FILTER_VALIDATE_INT);
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

// NOTE: Database connections are intentionally left open here.
// includes/header.php (included below) re-queries $site_db and $auth_db when a
// user session exists, so the connections must stay open until the end of the
// request. They are closed after the footer include at the bottom of this file.

// Helper functions
function getAccountStatus($locked, $banInfo) {
    if (!empty($banInfo)) {
        $reason = htmlspecialchars($banInfo['banreason'] ?? translate('ban_no_reason', 'No reason provided'));
        $unbanDate = $banInfo['unbandate'] ? date('Y-m-d H:i:s', $banInfo['unbandate']) : translate('ban_permanent', 'Permanent');
        return sprintf('<span class="bg-red-950/70 text-red-300 border border-red-700/60 px-2.5 py-0.5 rounded-full text-xs font-semibold inline-flex items-center gap-1.5 shadow-[0_0_8px_rgba(239,68,68,0.25)]"><i class="fas fa-ban text-[11px] text-red-400"></i> %s (Reason: %s, Until: %s)</span>', translate('status_banned', 'Banned'), $reason, $unbanDate);
    }
    switch ($locked) {
        case 1: return sprintf('<span class="bg-red-950/70 text-red-300 border border-red-700/60 px-2.5 py-0.5 rounded-full text-xs font-semibold inline-flex items-center gap-1.5 shadow-[0_0_8px_rgba(239,68,68,0.25)]"><i class="fas fa-ban text-[11px] text-red-400"></i> %s</span>', translate('status_banned', 'Banned'));
        case 2: return sprintf('<span class="bg-blue-950/70 text-blue-300 border border-blue-700/60 px-2.5 py-0.5 rounded-full text-xs font-semibold inline-flex items-center gap-1.5 shadow-[0_0_8px_rgba(59,130,246,0.25)]"><i class="fas fa-snowflake text-[11px] text-blue-300"></i> %s</span>', translate('status_frozen', 'Frozen'));
        default: return sprintf('<span class="bg-emerald-950/70 text-emerald-300 border border-emerald-600/50 px-2.5 py-0.5 rounded-full text-xs font-semibold inline-flex items-center gap-1.5 shadow-[0_0_8px_rgba(16,185,129,0.2)]"><i class="fas fa-shield-alt text-[11px] text-emerald-400"></i> %s</span>', translate('status_active', 'Active'));
    }
}

function getGMStatus($gmlevel, $role) {
    global $base_path;
    $icon = ($gmlevel > 0 || $role !== 'player') ? 'gm_icon.gif' : 'player_icon.jpg';
    $color = ($gmlevel > 0 || $role !== 'player') ? '#f0a500' : '#d1d5db';
    
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
    
    return sprintf('<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[rgba(201,162,39,0.12)] border border-[rgba(201,162,39,0.3)] shadow-[0_0_8px_rgba(201,162,39,0.15)]"><img src="%simg/accountimg/%s" alt="%s" class="w-4 h-4 inline-block"> <span style="color: %s">%s</span></span>', $base_path, $icon, translate('status_icon', 'Status Icon'), $color, $rank);
}

function getOnlineStatus($online) {
    if ($online) {
        // Modern glowing emerald badge with a pulsing animation ring
        return sprintf(
            '<span class="status-badge status-badge-online"><span class="status-dot"><span class="animate-ping"></span><span class="status-dot-core"></span></span>%s</span>',
            translate('status_online', 'Online')
        );
    }

    // Muted neutral badge for offline characters / accounts
    return sprintf(
        '<span class="status-badge status-badge-offline"><span class="status-dot"><span class="status-dot-core" style="background:#9ca3af;"></span></span>%s</span>',
        translate('status_offline', 'Offline')
    );
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

// Now proceed with page rendering
$page_class = 'account';
$page_title = $site_title_name . ' ' . sprintf(translate('page_title', 'Account - %s'), htmlspecialchars($accountInfo['username'] ?? ''));

ob_start();
?>
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
            background: linear-gradient(135deg, rgba(14, 19, 30, 0.8), rgba(8, 11, 17, 0.9));
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            border: 1px solid rgba(201,162,39,.15);
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .glass-card:hover {
            border-color: rgba(251, 191, 36, 0.4);
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.45), 0 8px 10px -6px rgba(0, 0, 0, 0.45);
        }
        
        /* Floating-pill navigation tabs */
        .nav-tab-gaming {
            border-radius: 8px;
            background: rgba(10, 14, 22, 0.6);
            border: 1px solid rgba(201, 162, 39, 0.2);
            color: #9ca3af;
            transition: all 0.3s ease;
        }

        .nav-tab-gaming:hover {
            background: rgba(242, 207, 82, 0.08);
            border-color: rgba(242, 207, 82, 0.3);
            color: #f2cf5b;
            transform: translateY(-2px);
        }

        .nav-tab-gaming.active {
            background: rgba(242, 207, 82, 0.15);
            border-color: #f2cf5b;
            color: #f2cf5b;
            box-shadow: 0 0 14px rgba(242, 207, 82, 0.12);
        }

        .nav-tab-gaming.active:hover {
            background: rgba(242, 207, 82, 0.2);
            border-color: #f2cf5b;
            color: #f2cf5b;
        }

        /* Keep the static active-state utility classes in the markup from
           "sticking" to a button after the .active class moves to another tab:
           this page's <style> block is declared before assets/css/tailwind.css,
           so equal-specificity utilities would otherwise win. (0,2,0) beats
           utility classes regardless of declaration order. */
        .nav-tab-gaming:not(.active) {
            background: rgba(10, 14, 22, 0.6);
            border-color: rgba(201, 162, 39, 0.2);
            color: #d1d5db;
        }

        /* Navigation Tabs Hover & Cursor Polish */
        .nav-tab-gaming {
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .nav-tab-gaming:hover {
            border-color: rgba(242, 207, 82, 0.5) !important;
            color: #f2cf5b !important;
            background: rgba(242, 207, 82, 0.08);
        }

        /* Force pointer cursor on all buttons and interactive triggers */
        button,
        input[type="submit"],
        .avatar-selector img,
        [data-teleport-cancel],
        #teleport-modal-confirm {
            cursor: pointer !important;
        }

        /* General Button Hover Glow Expansion */
        button[type="submit"]:hover,
        .btn-gaming:hover {
            box-shadow: 0 0 15px rgba(242, 207, 82, 0.25);
            border-color: #f2cf5b !important;
        }

        /* Live online / offline status badges (pulsing emerald ring) */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.2rem 0.6rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1.2;
        }

        .status-badge-online {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(52, 211, 153, 0.35);
            box-shadow: 0 0 10px rgba(16, 185, 129, 0.25);
        }

        .status-badge-offline {
            background: rgba(156, 163, 175, 0.12);
            color: #9ca3af;
            border: 1px solid rgba(156, 163, 175, 0.3);
        }

        .status-dot {
            position: relative;
            display: inline-flex;
            width: 8px;
            height: 8px;
        }

        .status-dot .status-dot-core {
            position: relative;
            display: inline-flex;
            width: 8px;
            height: 8px;
            border-radius: 9999px;
            background: #34d399;
        }

        /* animate-ping is not present in the compiled assets/css/tailwind.css
           build, so the keyframes are defined here for the online pulse ring. */
        .animate-ping {
            animation: ping 1s cubic-bezier(0, 0, 0.2, 1) infinite;
        }

        .status-dot .animate-ping {
            position: absolute;
            inset: 0;
            border-radius: 9999px;
            background: #34d399;
            opacity: 0.75;
        }

        @keyframes ping {
            75%, 100% {
                transform: scale(2);
                opacity: 0;
            }
        }

        /* ============ GAMING PROFILE HUD ============ */
        .profile-hud-card {
            position: relative;
            background: linear-gradient(135deg, rgba(14, 19, 30, 0.9) 0%, rgba(8, 11, 18, 0.96) 100%);
            border: 1px solid rgba(201, 162, 39, 0.24);
            border-radius: 8px;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.7), inset 0 1px 0 rgba(242, 207, 82, 0.18);
            overflow: hidden;
        }

        .profile-hud-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(242, 207, 82, 0.7), transparent);
            pointer-events: none;
        }

        .profile-avatar-frame {
            position: relative;
            width: 88px;
            height: 88px;
            flex-shrink: 0;
            padding: 3px;
            background: linear-gradient(135deg, #f2cf5b 0%, #c9a227 50%, #5a4010 100%);
            border-radius: 50%;
            box-shadow: 0 0 20px rgba(201, 162, 39, 0.4), inset 0 0 10px rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            background: #0a0e16;
            border: 2px solid rgba(10, 14, 22, 0.95);
        }

        .profile-hud-cell {
            background: rgba(10, 14, 22, 0.6);
            border: 1px solid rgba(201, 162, 39, 0.12);
            border-radius: 6px;
            padding: 0.65rem 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s ease;
        }

        .profile-hud-cell:hover {
            background: rgba(242, 207, 82, 0.05);
            border-color: rgba(242, 207, 82, 0.3);
            transform: translateY(-1px);
        }

        .profile-hud-icon {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            border: 1px solid;
            font-size: 0.95rem;
            flex-shrink: 0;
        }

        /* ============ QUICK STATS HUD CARDS ============ */
        .stat-hud-card {
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 0.9rem;
            background: linear-gradient(135deg, rgba(14, 19, 30, 0.88) 0%, rgba(8, 11, 18, 0.94) 100%);
            border: 1px solid rgba(201, 162, 39, 0.18);
            border-radius: 6px;
            backdrop-filter: blur(6px);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stat-hud-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.6);
        }

        .stat-hud-icon {
            width: 40px;
            height: 40px;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            font-size: 1.1rem;
            clip-path: polygon(4px 0, 100% 0, 100% calc(100% - 4px), calc(100% - 4px) 100%, 0 100%, 0 4px);
            transition: transform 0.25s ease;
        }

        .stat-hud-card:hover .stat-hud-icon {
            transform: scale(1.08);
        }

        .stat-hud-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #9ca3af;
        }

        /* Currency Icon Variants */
        .stat-icon-cyan {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.2), rgba(8, 145, 178, 0.06));
            border: 1px solid rgba(6, 182, 212, 0.35);
            color: #38bdf8;
            text-shadow: 0 0 10px rgba(56, 189, 248, 0.5);
        }

        .stat-icon-amber {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.22), rgba(217, 119, 6, 0.06));
            border: 1px solid rgba(245, 158, 11, 0.4);
            color: #fbbf24;
            text-shadow: 0 0 10px rgba(251, 191, 36, 0.5);
        }

        .stat-icon-gold {
            background: linear-gradient(135deg, rgba(234, 179, 8, 0.22), rgba(202, 138, 4, 0.06));
            border: 1px solid rgba(234, 179, 8, 0.4);
            color: #fde047;
            text-shadow: 0 0 10px rgba(253, 224, 71, 0.5);
        }

        .stat-icon-purple {
            background: linear-gradient(135deg, rgba(168, 85, 247, 0.25), rgba(126, 34, 206, 0.08));
            border: 1px solid rgba(168, 85, 247, 0.4);
            color: #c084fc;
            text-shadow: 0 0 10px rgba(192, 132, 252, 0.5);
        }

        /* Centered empty states */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 0.75rem;
            padding: 3rem 1.5rem;
        }

        .empty-state-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            border-radius: 9999px;
            background: rgba(242, 207, 82, 0.1);
            border: 1px solid rgba(201, 162, 39, 0.25);
            color: #f2cf5b;
            font-size: 1.5rem;
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

        /* Custom teleport confirmation popup (replaces the native confirm() dialog) */
        .teleport-modal {
            display: none;
            z-index: 9999;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }

        .teleport-modal.is-open {
            display: flex;
        }

        .teleport-modal-dialog {
            background: rgba(10, 14, 22, 0.97);
            border: 1px solid rgba(201, 162, 39, 0.45);
            box-shadow: 0 20px 45px -15px rgba(0, 0, 0, 0.9), inset 0 0 40px rgba(0, 0, 0, 0.25);
        }

        /* Busy label is only rendered while the confirmed teleport is in flight */
        .teleport-form .teleport-btn-busy {
            display: none;
        }

        /* Locked state: applied by JS only after the teleport was confirmed.
           The cooldown-disabled button never gets this class, so its look is untouched. */
        .teleport-form button[type="submit"].is-submitting,
        .teleport-form button[type="submit"].is-submitting:hover,
        .teleport-form button[type="submit"].is-submitting:active {
            background: rgba(0, 0, 0, 0.45);
            border-color: rgba(125, 131, 140, 0.35);
            color: #8b929b;
            opacity: 0.6;
            cursor: not-allowed;
            box-shadow: none;
            filter: grayscale(1);
        }

        .teleport-form button[type="submit"].is-submitting .teleport-btn-idle {
            display: none;
        }

        .teleport-form button[type="submit"].is-submitting .teleport-btn-busy {
            display: inline-flex;
            align-items: center;
        }

        @media (max-width: 767px) {
            .teleport-modal-dialog {
                width: 100%;
            }
        }
    </style>
<?php
$page_head = ob_get_clean();

include_once $project_root . 'includes/header.php';
?>

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
                        <i class="far fa-clock mr-1 text-[#f2cf5b]"></i> <?php echo translate('label_last_login', 'Last login'); ?>: <span class="text-gray-200 font-semibold"><?php echo htmlspecialchars($accountInfo['last_login']); ?></span>
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
            <div class="flex flex-wrap items-center gap-2 pb-4 mb-8 border-b border-[rgba(201,162,39,0.15)]">
                <button type="button" class="nav-tab-gaming active px-6 py-2.5 font-semibold text-sm rounded-lg transition-all duration-300 bg-[rgba(242,207,82,0.15)] border border-[#f2cf5b] text-[#f2cf5b] shadow-sm flex items-center justify-center gap-2 cursor-pointer" data-tab="overview">
                    <i class="fas fa-chart-pie"></i> <?php echo translate('tab_overview', 'Overview'); ?>
                </button>
                <button type="button" class="nav-tab-gaming px-6 py-2.5 font-semibold text-sm rounded-lg transition-all duration-300 bg-[rgba(10,14,22,0.6)] border border-[rgba(201,162,39,0.2)] text-gray-300 hover:border-[#f2cf5b]/50 hover:text-white flex items-center justify-center gap-2 cursor-pointer" data-tab="characters">
                    <i class="fas fa-users"></i> <?php echo translate('tab_characters', 'Characters'); ?>
                </button>
                <button type="button" class="nav-tab-gaming px-6 py-2.5 font-semibold text-sm rounded-lg transition-all duration-300 bg-[rgba(10,14,22,0.6)] border border-[rgba(201,162,39,0.2)] text-gray-300 hover:border-[#f2cf5b]/50 hover:text-white flex items-center justify-center gap-2 cursor-pointer" data-tab="activity">
                    <i class="fas fa-history"></i> <?php echo translate('tab_activity', 'Activity'); ?>
                </button>
                <button type="button" class="nav-tab-gaming px-6 py-2.5 font-semibold text-sm rounded-lg transition-all duration-300 bg-[rgba(10,14,22,0.6)] border border-[rgba(201,162,39,0.2)] text-gray-300 hover:border-[#f2cf5b]/50 hover:text-white flex items-center justify-center gap-2 cursor-pointer" data-tab="security">
                    <i class="fas fa-shield-alt"></i> <?php echo translate('tab_security', 'Security'); ?>
                </button>
                <a href="<?php echo $base_path; ?>vote" class="nav-tab-gaming ml-auto px-6 py-2.5 font-semibold text-sm rounded-lg transition-all duration-300 bg-[rgba(10,14,22,0.6)] border border-[rgba(201,162,39,0.2)] text-gray-300 hover:border-[#f2cf5b]/50 hover:text-white flex items-center justify-center gap-2 cursor-pointer">
                    <i class="fas fa-vote-yea text-[#f2cf5b]"></i> <?php echo translate('tab_vote', 'Vote'); ?>
                </a>
            </div>

            <!-- Tab Content -->
            <div>
                
                <!-- OVERVIEW TAB -->
                <div class="tab-pane active" id="tab-overview">
                    <!-- Unified Gamer Command Profile & Quick Stats Container -->
                    <div class="profile-hud-card mb-8 p-5 md:p-6">
                        <!-- Top: Profile Identity & Tactical Data Matrix -->
                        <div class="flex flex-col lg:flex-row items-center lg:items-stretch gap-6">
                            
                            <!-- Left: Gamer Identity & Status -->
                            <div class="flex flex-col sm:flex-row items-center sm:items-start lg:items-center gap-4 sm:gap-5 flex-shrink-0 text-center sm:text-left">
                                <?php
                                $avatar_display = !empty($currencies['avatar']) ? $currencies['avatar'] : 'user.jpg';
                                ?>
                                <div class="profile-avatar-frame">
                                    <img src="<?php echo $base_path; ?>img/accountimg/profile_pics/<?php echo htmlspecialchars($avatar_display); ?>" 
                                         alt="<?php echo translate('avatar_alt', 'Avatar'); ?>" 
                                         class="profile-avatar-img">
                                </div>

                                <div class="space-y-1.5 min-w-0">
                                    <div class="flex items-center justify-center sm:justify-start gap-2 flex-wrap">
                                        <h3 class="text-2xl font-black text-white tracking-wide" style="font-family:'Cinzel',serif;">
                                            <?php echo htmlspecialchars($accountInfo['username'] ?? 'N/A'); ?>
                                        </h3>
                                        <span class="text-xs px-2 py-0.5 rounded bg-[rgba(201,162,39,0.12)] border border-[rgba(201,162,39,0.25)] text-[#f2cf5b] font-mono font-bold">
                                            #<?php echo $accountInfo['id'] ?? 'N/A'; ?>
                                        </span>
                                    </div>

                                    <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 pt-1">
                                        <?php echo getOnlineStatus($accountInfo['online'] ?? 0); ?>
                                        <?php echo getGMStatus($gmlevel, $role); ?>
                                        <?php echo getAccountStatus($accountInfo['locked'] ?? 0, $banInfo); ?>
                                    </div>

                                    <?php if ($role === 'admin' || $role === 'moderator' || $gmlevel > 0): ?>
                                        <div class="pt-2">
                                            <a href="<?php echo $base_path; ?>admin/dashboard" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-[rgba(242,207,82,0.15)] hover:bg-[rgba(242,207,82,0.25)] border border-[rgba(201,162,39,0.4)] text-[#f2cf5b] hover:text-white transition-all text-xs font-bold uppercase tracking-wider shadow-[0_0_12px_rgba(242,207,82,0.15)]">
                                                <i class="fas fa-crown text-amber-400"></i> <?php echo translate('button_admin_panel', 'Admin Panel'); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Divider on desktop -->
                            <div class="hidden lg:block w-px bg-gradient-to-b from-transparent via-[rgba(201,162,39,0.25)] to-transparent"></div>

                            <!-- Right: Tactical Data Matrix Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 w-full flex-1">
                                <!-- Email -->
                                <div class="profile-hud-cell">
                                    <div class="profile-hud-icon text-cyan-400 bg-cyan-500/10 border-cyan-500/25">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400">
                                            <?php echo translate('label_email', 'Email'); ?>
                                        </div>
                                        <div class="text-sm font-semibold text-gray-200 truncate force-wrap" title="<?php echo htmlspecialchars($accountInfo['email'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($accountInfo['email'] ?? translate('email_not_set', 'Not set')); ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Expansion -->
                                <div class="profile-hud-cell">
                                    <div class="profile-hud-icon text-amber-400 bg-amber-500/10 border-amber-500/25">
                                        <i class="fas fa-dragon"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400">
                                            <?php echo translate('label_expansion', 'Expansion'); ?>
                                        </div>
                                        <div class="text-sm font-semibold text-[#f2cf5b] truncate force-wrap">
                                            <?php echo translate('expansion_' . ($accountInfo['expansion'] ?? 2), ($accountInfo['expansion'] ?? 2) == 2 ? 'Wrath of the Lich King' : ($accountInfo['expansion'] == 1 ? 'The Burning Crusade' : 'Classic')); ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Join Date -->
                                <div class="profile-hud-cell">
                                    <div class="profile-hud-icon text-emerald-400 bg-emerald-500/10 border-emerald-500/25">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400">
                                            <?php echo translate('label_join_date', 'Join Date'); ?>
                                        </div>
                                        <div class="text-sm font-semibold text-gray-200 truncate force-wrap">
                                            <?php echo htmlspecialchars($accountInfo['joindate'] ?? 'N/A'); ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Last Login -->
                                <div class="profile-hud-cell">
                                    <div class="profile-hud-icon text-purple-400 bg-purple-500/10 border-purple-500/25">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400">
                                            <?php echo translate('label_last_login', 'Last Login'); ?>
                                        </div>
                                        <div class="text-sm font-semibold text-gray-200 truncate force-wrap">
                                            <?php echo htmlspecialchars($accountInfo['last_login'] ?? translate('never', 'Never')); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Sleek Divider separating Profile Info and Quick Stats -->
                        <div class="my-5 border-t border-[rgba(201,162,39,0.18)]"></div>

                        <!-- Integrated Quick Stats Section -->
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-bold uppercase tracking-wider text-gray-400 flex items-center gap-2" style="font-family:'Cinzel',serif;">
                                    <i class="fas fa-chart-simple text-[#f2cf5b]"></i>
                                    <?php echo translate('section_quick_stats', 'Quick Stats'); ?>
                                </span>
                            </div>
                            <?php
                                $maxLevel = 0;
                                $totalGold = 0;
                                foreach ($characters as $char) {
                                    if ($char['level'] > $maxLevel) {
                                        $maxLevel = $char['level'];
                                    }
                                    $totalGold += $char['money'];
                                }
                            ?>
                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                                <!-- Characters -->
                                <div class="stat-hud-card group hover:border-cyan-500/40">
                                    <div class="stat-hud-icon stat-icon-cyan">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-baseline justify-between gap-1">
                                            <span class="text-xl md:text-2xl font-black text-white tracking-tight" style="font-family:'Cinzel',serif;">
                                                <?php echo count($characters); ?>
                                            </span>
                                            <span class="text-[10px] px-1.5 py-0.2 font-semibold text-cyan-300 bg-cyan-950/60 border border-cyan-800/60 rounded">
                                                Lv.<?php echo $maxLevel; ?>
                                            </span>
                                        </div>
                                        <div class="stat-hud-label truncate">
                                            <?php echo translate('label_total_characters', 'Characters'); ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Total Gold -->
                                <div class="stat-hud-card group hover:border-amber-500/40">
                                    <div class="stat-hud-icon stat-icon-amber">
                                        <i class="fas fa-coins"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-baseline gap-1">
                                            <span class="text-xl md:text-2xl font-black text-white tracking-tight" style="font-family:'Cinzel',serif;">
                                                <?php echo number_format($totalGold / 10000, 2); ?>
                                            </span>
                                            <span class="text-xs font-bold text-amber-400">g</span>
                                        </div>
                                        <div class="stat-hud-label truncate">
                                            <?php echo translate('label_total_gold', 'Total Gold'); ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Points -->
                                <div class="stat-hud-card group hover:border-yellow-500/40">
                                    <div class="stat-hud-icon stat-icon-gold">
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-baseline justify-between gap-1">
                                            <span class="text-xl md:text-2xl font-black text-white tracking-tight" style="font-family:'Cinzel',serif;">
                                                <?php echo number_format((int)($currencies['points'] ?? 0)); ?>
                                            </span>
                                            <a href="<?php echo $base_path; ?>vote" class="text-[10px] text-yellow-400 hover:text-white font-semibold flex items-center gap-0.5 transition-colors">
                                                <i class="fas fa-vote-yea text-[9px]"></i> <?php echo translate('tab_vote', 'Vote'); ?>
                                            </a>
                                        </div>
                                        <div class="stat-hud-label truncate">
                                            <?php echo translate('label_points', 'Points'); ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tokens -->
                                <div class="stat-hud-card group hover:border-purple-500/40">
                                    <div class="stat-hud-icon stat-icon-purple">
                                        <i class="fas fa-gem"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-baseline justify-between gap-1">
                                            <span class="text-xl md:text-2xl font-black text-white tracking-tight" style="font-family:'Cinzel',serif;">
                                                <?php echo number_format((int)($currencies['tokens'] ?? 0)); ?>
                                            </span>
                                            <a href="<?php echo $base_path; ?>shop" class="text-[10px] text-purple-400 hover:text-white font-semibold flex items-center gap-0.5 transition-colors">
                                                <i class="fas fa-store text-[9px]"></i> <?php echo translate('shop_title', 'Shop'); ?>
                                            </a>
                                        </div>
                                        <div class="stat-hud-label truncate">
                                            <?php echo translate('label_tokens', 'Tokens'); ?>
                                        </div>
                                    </div>
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
                                        <span class="text-gray-300"><?php echo getClassIcon($char['class']); ?> <?php echo translate('label_level', 'Level'); ?> <?php echo $char['level']; ?></span>
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
                                    
                                    <form method="post" class="mt-3 teleport-form">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="guid" value="<?php echo $char['guid']; ?>">
                                        <div class="flex flex-wrap gap-2">
                                            <select class="flex-1 min-w-25 px-3 py-1.5 bg-[rgba(0,0,0,0.4)] border border-[rgba(201,162,39,0.2)] text-gray-200 text-sm focus:border-[#f2cf5b] focus:outline-none" name="destination" required>
                                                <option value=""><?php echo translate('select_city_placeholder', 'Select city'); ?></option>
                                                <option value="shattrath"><?php echo translate('city_shattrath', 'Shattrath'); ?></option>
                                                <option value="dalaran"><?php echo translate('city_dalaran', 'Dalaran'); ?></option>
                                            </select>
                                            <button class="px-4 py-1.5 bg-[rgba(242,207,82,0.15)] border border-[rgba(201,162,39,0.3)] text-[#f2cf5b] hover:bg-[rgba(242,207,82,0.25)] transition-all duration-300 text-sm font-semibold whitespace-nowrap cursor-pointer shadow-sm hover:border-[#f2cf5b]" type="submit" name="teleport_character" <?php echo $is_on_cooldown ? 'disabled' : ''; ?>>
                                                <span class="teleport-btn-idle"><i class="fas fa-arrow-right mr-1"></i><?php echo translate('button_teleport', 'Teleport'); ?></span><span class="teleport-btn-busy"><i class="fas fa-circle-notch fa-spin mr-1"></i><?php echo translate('button_teleporting', 'Teleporting...'); ?></span>
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
                        <div class="glass-card empty-state">
                            <span class="empty-state-icon"><i class="fas fa-user-plus"></i></span>
                            <p class="text-gray-300 font-semibold"><?php echo translate('no_characters', 'You have no characters yet.'); ?></p>
                            <p class="text-gray-400 text-sm"><?php echo translate('empty_no_characters_hint', 'Create a character in-game, then reload this page to see them here.'); ?></p>
                        </div>
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
                        <div class="glass-card empty-state">
                            <span class="empty-state-icon"><i class="fas fa-scroll"></i></span>
                            <p class="text-gray-300 font-semibold"><?php echo translate('no_activity', 'No recent activity.'); ?></p>
                            <p class="text-gray-400 text-sm"><?php echo translate('empty_no_activity_hint', 'Actions like logins, teleports and shop purchases will appear here.'); ?></p>
                        </div>
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
                                <button class="px-6 py-2 bg-[rgba(242,207,82,0.15)] border border-[rgba(201,162,39,0.3)] text-[#f2cf5b] hover:bg-[rgba(242,207,82,0.25)] transition-all duration-300 font-semibold cursor-pointer shadow-sm hover:border-[#f2cf5b]" type="submit" name="change_email">
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
                                <button class="px-6 py-2 bg-[rgba(242,207,82,0.15)] border border-[rgba(201,162,39,0.3)] text-[#f2cf5b] hover:bg-[rgba(242,207,82,0.25)] transition-all duration-300 font-semibold cursor-pointer shadow-sm hover:border-[#f2cf5b]" type="submit" name="change_password">
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
                                <button class="px-6 py-2 bg-[rgba(242,207,82,0.15)] border border-[rgba(201,162,39,0.3)] text-[#f2cf5b] hover:bg-[rgba(242,207,82,0.25)] transition-all duration-300 font-semibold mt-4 cursor-pointer shadow-sm hover:border-[#f2cf5b]" type="submit" name="change_avatar">
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

<!-- Custom teleport confirmation popup (frontend only - the POST form, CSRF token and all server-side validation stay unchanged) -->
<div id="teleport-modal" class="teleport-modal fixed inset-0 items-center justify-center px-4" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="teleport-modal-title" aria-describedby="teleport-modal-message">
    <div class="absolute inset-0" data-teleport-cancel></div>
    <div class="teleport-modal-dialog glass-card relative w-full max-w-md p-6 text-center">
        <i class="fas fa-arrow-right text-3xl text-[#f2cf5b] mb-3"></i>
        <h3 id="teleport-modal-title" class="text-xl font-bold text-[#f2cf5b]"><?php echo translate('button_teleport', 'Teleport'); ?></h3>
        <p id="teleport-modal-message" class="text-sm text-gray-300 mt-2"><?php echo translate('confirm_teleport', 'Teleport this character?'); ?></p>
        <div class="flex flex-wrap items-center justify-center gap-3 mt-6">
            <button type="button" class="px-6 py-2 border border-[rgba(201,162,39,0.3)] text-gray-300 hover:text-white hover:border-[#f2cf5b] transition-all duration-300 font-semibold cursor-pointer" data-teleport-cancel>
                <?php echo translate('button_cancel', 'Cancel'); ?>
            </button>
            <button type="button" id="teleport-modal-confirm" class="px-6 py-2 bg-[rgba(242,207,82,0.15)] border border-[rgba(201,162,39,0.3)] text-[#f2cf5b] hover:bg-[rgba(242,207,82,0.25)] transition-all duration-300 font-semibold cursor-pointer">
                <i class="fas fa-arrow-right mr-1"></i><?php echo translate('button_teleport', 'Teleport'); ?>
            </button>
        </div>
    </div>
</div>

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
            });
            
            // Add active class to clicked tab
            this.classList.add('active');
            
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

    // Initial active styling comes from the .active class in the markup,
    // styled by .nav-tab-gaming.active in the <style> block (no inline JS styles).

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

    // Custom teleport confirmation popup fix
    (function () {
        const modal = document.getElementById('teleport-modal');
        if (!modal) return;

        const confirmButton = document.getElementById('teleport-modal-confirm');
        const cancelTargets = modal.querySelectorAll('[data-teleport-cancel]');
        let pendingForm = null;
        let lastTrigger = null;

        function openTeleportModal(form) {
            pendingForm = form;
            lastTrigger = document.activeElement;
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            if (confirmButton) {
                confirmButton.focus();
            }
        }

        function closeTeleportModal() {
            pendingForm = null;
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            if (lastTrigger && typeof lastTrigger.focus === 'function') {
                lastTrigger.focus();
            }
            lastTrigger = null;
        }

        function confirmTeleport() {
            const form = pendingForm;
            if (!form) return;

            form.dataset.teleportConfirmed = '1';
            form.dataset.teleportSubmitting = '1';
            closeTeleportModal();

            const submitter = form.querySelector('button[type="submit"][name="teleport_character"]');
            if (typeof form.requestSubmit === 'function') {
                if (submitter) {
                    form.requestSubmit(submitter);
                } else {
                    form.requestSubmit();
                }
            } else {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'teleport_character';
                hidden.value = '1';
                form.appendChild(hidden);
                form.submit();
            }

            if (submitter) {
                submitter.disabled = true;
                submitter.classList.add('is-submitting');
                submitter.setAttribute('aria-busy', 'true');
            }

            window.setTimeout(function () {
                form.dataset.teleportConfirmed = '';
            }, 1000);
        }

        // Bind ONLY to explicit teleport buttons to prevent stray form triggers
        document.querySelectorAll('form.teleport-form button[type="submit"][name="teleport_character"]').forEach(function (button) {
            button.addEventListener('click', function (event) {
                const form = button.closest('form');
                if (!form) return;

                if (form.dataset.teleportConfirmed === '1') {
                    return; // already confirmed
                }

                // Check HTML5 validity (e.g. dropdown selection required)
                const select = form.querySelector('select[name="destination"]');
                if (select && !select.value) {
                    return; // let native select validation handle the error message
                }

                event.preventDefault();
                if (form.dataset.teleportSubmitting === '1') {
                    return;
                }

                openTeleportModal(form);
            });
        });

        if (confirmButton) {
            confirmButton.addEventListener('click', confirmTeleport);
        }

        cancelTargets.forEach(function (element) {
            element.addEventListener('click', function () {
                closeTeleportModal();
            });
        });

        document.addEventListener('keydown', function (event) {
            if ((event.key === 'Escape' || event.key === 'Esc') && modal.classList.contains('is-open')) {
                closeTeleportModal();
            }
        });
    })();
</script>

<?php include_once $project_root . 'includes/footer.php'; ?>
<?php
ob_end_flush(); // Flush the output buffer
?>
<?php
// Close the database connections at the true end of the request.
// header.php (included above) queries $site_db and $auth_db whenever a user
// session exists, so nothing may be closed before the header and footer have
// finished rendering. POST/redirect paths exit earlier and rely on PHP's
// end-of-script connection teardown, exactly as before this refactor.
if (isset($auth_db)) {
    $auth_db->close();
}
if (isset($char_db)) {
    $char_db->close();
}
if (isset($site_db)) {
    $site_db->close();
}
?>
