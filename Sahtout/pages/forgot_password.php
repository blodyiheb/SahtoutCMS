<?php
define('ALLOWED_ACCESS', true);

// Include paths.php using __DIR__ to access $project_root and $base_path
require_once __DIR__ . '/../includes/paths.php';

// Use $project_root for filesystem includes
require_once $project_root . 'includes/session.php';
require_once $project_root . 'includes/config.cap.php';
require_once $project_root . 'includes/config.mail.php';
require_once $project_root . 'languages/language.php';
$page_class = 'forgot_password';
require_once $project_root . 'includes/header.php';

// Redirect to account if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: {$base_path}account");
    exit();
}

$errors = [];
$success = '';
$username_or_email = '';

// Get client IP address
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}
$ip_address = getUserIP();

// Clean up expired or used tokens
$stmt_delete = $site_db->prepare("DELETE FROM password_resets WHERE expires_at < NOW() OR used = 1");
if (!$stmt_delete->execute()) {
    error_log("Failed to delete expired password_resets: " . $site_db->error);
}
$stmt_delete->close();

// Clean up expired reset attempts
$stmt_cleanup = $site_db->prepare("DELETE FROM reset_attempts WHERE blocked_until < NOW()");
if (!$stmt_cleanup->execute()) {
    error_log("Cleanup failed: " . $site_db->error);
}
$stmt_cleanup->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_or_email = trim($_POST['username_or_email'] ?? '');

    // Basic field validation
    if (empty($username_or_email)) {
        $errors[] = translate('error_username_or_email_required', 'Username or email is required');
    }

    // Google reCAPTCHA validation
    if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED) {
        $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
        $verify = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . RECAPTCHA_SECRET_KEY . '&response=' . $recaptchaResponse);
        $responseData = json_decode($verify);
        if (!$responseData->success) {
            $errors[] = translate('error_recaptcha_failed', 'reCAPTCHA verification failed.');
        }
    }

    // Check email-based reset attempt limit
    if (empty($errors)) {
        // Normalize email or username for consistency
        $lookup_value = filter_var($username_or_email, FILTER_VALIDATE_EMAIL) ? strtolower($username_or_email) : strtoupper($username_or_email);
        $is_email = filter_var($username_or_email, FILTER_VALIDATE_EMAIL) !== false;

        // Check if the email/username is blocked
        $stmt_block_check = $site_db->prepare("SELECT id, blocked_until FROM reset_attempts WHERE email = ? AND blocked_until > NOW()");
        $stmt_block_check->bind_param('s', $lookup_value);
        if (!$stmt_block_check->execute()) {
            error_log("Block check failed for {$lookup_value}: " . $site_db->error);
            $errors[] = translate('error_database', 'Database error occurred. Please try again.');
        } else {
            $result_block_check = $stmt_block_check->get_result();
            $block_row = $result_block_check->fetch_assoc();
            $stmt_block_check->close();

            $is_blocked = false;
            if ($block_row) {
                $is_blocked = true;
                error_log("Blocked attempt for {$lookup_value}, blocked until {$block_row['blocked_until']}");
                $errors[] = translate('error_reset_limit_exceeded', 'You have reached the maximum number of password reset attempts. Please try again later.');
            }

            if (!$is_blocked) {
                // Fetch the attempt record
                $stmt_check = $site_db->prepare("SELECT id, ip_address, attempts, blocked_until, last_attempt FROM reset_attempts WHERE email = ?");
                $stmt_check->bind_param('s', $lookup_value);
                if (!$stmt_check->execute()) {
                    error_log("Attempt check failed for {$lookup_value}: " . $site_db->error);
                    $errors[] = translate('error_database', 'Database error occurred. Please try again.');
                } else {
                    $result_check = $stmt_check->get_result();
                    $attempt_row = $result_check->fetch_assoc();
                    $stmt_check->close();

                    $attempts = 0; // Default to 0 for new or reset records

                    if ($attempt_row) {
                        $attempts = $attempt_row['attempts'];
                        // Reset attempts if blocked_until has expired
                        if ($attempt_row['blocked_until'] && strtotime($attempt_row['blocked_until']) < time()) {
                            $stmt_reset = $site_db->prepare("UPDATE reset_attempts SET attempts = 0, blocked_until = NULL WHERE id = ?");
                            $stmt_reset->bind_param('i', $attempt_row['id']);
                            if ($stmt_reset->execute()) {
                                error_log("Reset attempts for {$lookup_value}, id {$attempt_row['id']}");
                                $attempts = 0;
                            } else {
                                error_log("Reset attempts failed for {$lookup_value}: " . $site_db->error);
                                $errors[] = translate('error_database', 'Failed to reset attempt record. Please try again.');
                            }
                            $stmt_reset->close();
                        }
                    }

                    // Process the reset request if not blocked
                    if (empty($errors)) {
                        // Check attempt limit
                        if ($attempts >= 3) {
                            // Block for 1 minute
                            $stmt_block = $site_db->prepare("INSERT INTO reset_attempts (ip_address, email, attempts, blocked_until, last_attempt) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 1 MINUTE), NOW()) ON DUPLICATE KEY UPDATE ip_address = VALUES(ip_address), attempts = LEAST(attempts + 1, 3), blocked_until = DATE_ADD(NOW(), INTERVAL 1 MINUTE), last_attempt = NOW()");
                            $new_attempts = $attempts + 1;
                            $stmt_block->bind_param('ssi', $ip_address, $lookup_value, $new_attempts);
                            if ($stmt_block->execute()) {
                                error_log("Blocked {$lookup_value} after {$new_attempts} attempts, affected rows: " . $stmt_block->affected_rows);
                                $errors[] = translate('error_reset_limit_exceeded', 'You have reached the maximum number of password reset attempts. Please try again later.');
                            } else {
                                error_log("Block update failed for {$lookup_value}: " . $site_db->error);
                                $errors[] = translate('error_database', 'Failed to update attempt record. Please try again.');
                            }
                            $stmt_block->close();
                        } else {
                            // Log state before upsert
                            $stmt_check = $site_db->prepare("SELECT id, attempts, email FROM reset_attempts WHERE email = ?");
                            $stmt_check->bind_param('s', $lookup_value);
                            $stmt_check->execute();
                            $result_check = $stmt_check->get_result();
                            $row = $result_check->fetch_assoc();
                            error_log("Before upsert for {$lookup_value}: id = " . ($row['id'] ?? 'none') . ", attempts = " . ($row['attempts'] ?? 'none') . ", email = " . ($row['email'] ?? 'none'));
                            $stmt_check->close();

                            // Update or insert attempt record
                            $stmt_upsert = $site_db->prepare("INSERT INTO reset_attempts (ip_address, email, attempts, last_attempt) VALUES (?, ?, 1, NOW()) ON DUPLICATE KEY UPDATE ip_address = VALUES(ip_address), attempts = attempts + 1, last_attempt = NOW()");
                            $stmt_upsert->bind_param('ss', $ip_address, $lookup_value);
                            if ($stmt_upsert->execute()) {
                                error_log("Upsert successful for {$lookup_value}, affected rows: " . $stmt_upsert->affected_rows);
                                // Log state after upsert
                                $stmt_check = $site_db->prepare("SELECT id, attempts, email FROM reset_attempts WHERE email = ?");
                                $stmt_check->bind_param('s', $lookup_value);
                                $stmt_check->execute();
                                $result_check = $stmt_check->get_result();
                                $row = $result_check->fetch_assoc();
                                error_log("After upsert for {$lookup_value}: id = " . ($row['id'] ?? 'none') . ", attempts = " . ($row['attempts'] ?? 'none') . ", email = " . ($row['email'] ?? 'none'));
                                $stmt_check->close();
                            } else {
                                error_log("Upsert failed for {$lookup_value}: " . $site_db->error);
                                $errors[] = translate('error_database', 'Failed to update attempt record. Please try again.');
                            }
                            $stmt_upsert->close();

                            // Proceed with checking username/email and sending reset email
                            $email = null;
                            $username = null;

                            // Check account table (case-sensitive username, case-insensitive email)
                            $stmt = $auth_db->prepare("SELECT username, email FROM account WHERE username = ? OR LOWER(email) = LOWER(?)");
                            $stmt->bind_param('ss', $username_or_email, $username_or_email);
                            if (!$stmt->execute()) {
                                error_log("Account check failed for {$username_or_email}: " . $auth_db->error);
                                $errors[] = translate('error_database', 'Database error occurred. Please try again.');
                            } else {
                                $result = $stmt->get_result();
                                if ($result->num_rows > 0) {
                                    $row = $result->fetch_assoc();
                                    $username = $row['username'];
                                    $email = $row['email'];
                                }
                                $stmt->close();

                                if ($email && $username) {
                                    // Generate new reset token
                                    $token = bin2hex(random_bytes(32));

                                    // Delete existing reset tokens for this email
                                    $stmt_delete = $site_db->prepare("DELETE FROM password_resets WHERE email = ?");
                                    $stmt_delete->bind_param('s', $email);
                                    if (!$stmt_delete->execute()) {
                                        error_log("Delete existing tokens failed for email {$email}: " . $site_db->error);
                                    }
                                    $stmt_delete->close();

                                    // Store token with 1-minute expiration
                                    $stmt_insert = $site_db->prepare("INSERT INTO password_resets (email, token, expires_at, used) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 MINUTE), 0)");
                                    $stmt_insert->bind_param('ss', $email, $token);
                                    if ($stmt_insert->execute()) {
                                        if (defined('SMTP_ENABLED') && SMTP_ENABLED) {
                                            // Send reset email
                                            $email_sent = sendResetEmail($username, $email, $token);
                                            if ($email_sent) {
                                                $success = translate('success_email_sent', 'If the provided username or email exists, a password reset link has been sent.');
                                            } else {
                                                $errors[] = translate('error_email_failed', 'Failed to send reset email. Please contact support.');
                                            }
                                        } else {
                                            $success = translate('success_no_email', 'A reset password token has been created. Contact the admin to provide you the link to change your password.');
                                        }
                                    } else {
                                        error_log("Token insert failed for email {$email}: " . $site_db->error);
                                        $errors[] = translate('error_token_store_failed', 'Failed to store reset token.');
                                    }
                                    $stmt_insert->close();
                                } else {
                                    // Show success to avoid leaking account existence
                                    $success = translate('success_email_sent', 'If the provided username or email exists, a password reset link has been sent.');
                                }
                                $username_or_email = '';
                            }
                        }
                    }
                }
            }
        }
    }
}

function sendResetEmail($username, $email, $token) {
    global $errors, $base_path;
    try {
        $mail = getMailer();
        $mail->addAddress($email, $username);
        $mail->AddEmbeddedImage('logo.png', 'logo_cid');
        $mail->Subject = translate('email_subject', 'Password Reset Request');
        $reset_link = $base_path . "reset_password?token=$token";
        $mail->Body = "<h2>" . str_replace('{username}', htmlspecialchars($username), translate('email_greeting', 'Welcome, {username}!')) . "</h2>
            <img src='cid:logo_cid' alt='Sahtout logo'>
            <p>" . translate('email_request', 'You requested a password reset. Please click the button below to reset your password:') . "</p>
            <p><a href='$reset_link' style='background-color:#ffd700;color:#000;padding:10px 20px;text-decoration:none;border-radius:4px;display:inline-block;'>" . translate('email_button', 'Reset Password') . "</a></p>
            <p>" . translate('email_expiry', 'This link will expire in 1 minute. If you didn\'t request this, please ignore this email.') . "</p>";
        return $mail->send();
    } catch (Exception $e) {
        error_log("Email sending failed for {$email}: " . $e->getMessage());
        $errors[] = translate('error_email_failed', 'Failed to send email: ') . $e->getMessage();
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="description" content="<?php echo translate('meta_description', 'Request a password reset link for your World of Warcraft server account.'); ?>">
    <title><?php echo $site_title_name ." ". translate('page_title', 'Forgot Password'); ?></title>
</head>
    <style>
        :root{
            --bg-forgot-pw:url('<?php echo $base_path; ?>img/backgrounds/bg-password.jpg');
        }
    </style>
</style>
<body class="forgot_password">
    <div class="wrapper">
        <div class="form-container">
            <div class="form-section">
                <h2><?php echo translate('forgot_title', 'Forgot Password'); ?></h2>
                <?php if (!empty($errors)): ?>
                    <div class="error">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="success">
                        <p><?php echo htmlspecialchars($success); ?></p>
                    </div>
                <?php endif; ?>
                <form method="POST">
                    <input type="text" name="username_or_email" placeholder="<?php echo translate('username_or_email_placeholder', 'Username or Email'); ?>" required value="<?php echo htmlspecialchars($username_or_email); ?>">
                    <?php if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED): ?>
                        <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                    <?php endif; ?>
                    <button type="submit"><?php echo translate('send_button', 'Send Reset Link'); ?></button>
                    <p class="login-link">
                        <?php echo translate('login_link', 'Remembered your password?'); ?>
                        <?php echo sprintf(translate('login_link_text', '<a href="%s">Log in here</a>'), htmlspecialchars($base_path . 'login')); ?>
                    </p>
                </form>
            </div>
        </div>
    </div>
    <?php if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED): ?>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>
    <?php include_once $project_root . 'includes/footer.php'; ?>
</body>
</html>