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
        $lookup_value = filter_var($username_or_email, FILTER_VALIDATE_EMAIL) ? strtolower($username_or_email) : strtoupper($username_or_email);
        $is_email = filter_var($username_or_email, FILTER_VALIDATE_EMAIL) !== false;

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
                $stmt_check = $site_db->prepare("SELECT id, ip_address, attempts, blocked_until, last_attempt FROM reset_attempts WHERE email = ?");
                $stmt_check->bind_param('s', $lookup_value);
                if (!$stmt_check->execute()) {
                    error_log("Attempt check failed for {$lookup_value}: " . $site_db->error);
                    $errors[] = translate('error_database', 'Database error occurred. Please try again.');
                } else {
                    $result_check = $stmt_check->get_result();
                    $attempt_row = $result_check->fetch_assoc();
                    $stmt_check->close();

                    $attempts = 0;

                    if ($attempt_row) {
                        $attempts = $attempt_row['attempts'];
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

                    if (empty($errors)) {
                        if ($attempts >= 3) {
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
                            $stmt_upsert = $site_db->prepare("INSERT INTO reset_attempts (ip_address, email, attempts, last_attempt) VALUES (?, ?, 1, NOW()) ON DUPLICATE KEY UPDATE ip_address = VALUES(ip_address), attempts = attempts + 1, last_attempt = NOW()");
                            $stmt_upsert->bind_param('ss', $ip_address, $lookup_value);
                            if ($stmt_upsert->execute()) {
                                error_log("Upsert successful for {$lookup_value}, affected rows: " . $stmt_upsert->affected_rows);
                            } else {
                                error_log("Upsert failed for {$lookup_value}: " . $site_db->error);
                                $errors[] = translate('error_database', 'Failed to update attempt record. Please try again.');
                            }
                            $stmt_upsert->close();

                            $email = null;
                            $username = null;

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
                                    $token = bin2hex(random_bytes(32));

                                    $stmt_delete = $site_db->prepare("DELETE FROM password_resets WHERE email = ?");
                                    $stmt_delete->bind_param('s', $email);
                                    if (!$stmt_delete->execute()) {
                                        error_log("Delete existing tokens failed for email {$email}: " . $site_db->error);
                                    }
                                    $stmt_delete->close();

                                    $stmt_insert = $site_db->prepare("INSERT INTO password_resets (email, token, expires_at, used) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 MINUTE), 0)");
                                    $stmt_insert->bind_param('ss', $email, $token);
                                    if ($stmt_insert->execute()) {
                                        if (defined('SMTP_ENABLED') && SMTP_ENABLED) {
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
    global $errors, $site_url;
    try {
        $mail = getMailer();
        $mail->addAddress($email, $username);
        $mail->AddEmbeddedImage('logo.png', 'logo_cid');
        $mail->Subject = translate('email_subject', 'Password Reset Request');
        $reset_link = $site_url. "reset_password?token=$token";
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
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        /* Page background - Only essential custom CSS */
        body {
            background: url('<?php echo $base_path; ?>img/backgrounds/bg-password.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            padding-top: 112px;
            margin: 0;
            position: relative;
        }
        
        body::before {
            display: none;
        }
        
        /* Glass effect container - Minimal custom CSS */
        .glass-container {
            background: rgba(5, 7, 11, 0.75);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(201,162,39,.22);
            box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.8), inset 0 0 60px rgba(0,0,0,.25);
            position: relative;
            max-width: 450px;
            width: 100%;
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
        
        /* Wow title gradient - Essential */
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
        
        /* Input fields - Essential */
        .input-forgot {
            background: rgba(0, 0, 0, 0.5) !important;
            border: 2px solid rgba(201,162,39,0.3) !important;
            color: #ffffff !important;
            transition: all 0.3s ease;
        }
        
        .input-forgot:focus {
            border-color: #f2cf5b !important;
            box-shadow: 0 0 20px rgba(242,207,82,0.15) !important;
            outline: none;
        }
        
        .input-forgot::placeholder {
            color: #6b7280 !important;
        }
        
        /* Responsive - Essential */
        @media (max-width: 767px) {
            body {
                padding-top: 96px;
            }
            
            .glass-container {
                max-width: 100%;
                margin: 0 0.5rem;
            }
        }
    </style>
</head>
<body>
<div class="forgot-content relative z-10 min-h-screen flex items-center justify-center px-4 py-8">
    <div class="container mx-auto max-w-7xl px-2 sm:px-4 flex items-center justify-center">
        
        <!-- Main Container -->
        <div class="glass-container p-6 md:p-10">
            
            <!-- Icon -->
            <div class="text-center mb-4">
                <div class="w-20 h-20 mx-auto bg-[rgba(242,207,82,0.1)] border border-[rgba(201,162,39,0.3)] flex items-center justify-center">
                    <i class="fas fa-key text-4xl text-[#f2cf5b]"></i>
                </div>
            </div>
            
            <!-- Title -->
            <h1 class="wow-title text-3xl md:text-5xl font-bold text-center mb-6">
                <?php echo translate('forgot_title', 'Forgot Password'); ?>
            </h1>

            <!-- Errors -->
            <?php if (!empty($errors)): ?>
                <div class="bg-red-900/40 border border-red-600/40 text-red-200 px-4 py-3 mb-4 text-center text-sm">
                    <?php foreach ($errors as $error): ?>
                        <p><i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Success -->
            <?php if ($success): ?>
                <div class="bg-green-900/40 border border-green-600/40 text-green-200 px-4 py-3 mb-4 text-center text-sm">
                    <p><i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?></p>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" class="space-y-4">
                <div class="relative">
                    <i class="fas fa-user text-[rgba(201,162,39,0.4)] absolute top-3.5 left-3"></i>
                    <input type="text" name="username_or_email" placeholder="<?php echo translate('username_or_email_placeholder', 'Username or Email'); ?>" required value="<?php echo htmlspecialchars($username_or_email); ?>" class="input-forgot w-full pl-10 pr-4 py-3 text-base">
                </div>

                <?php if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED): ?>
                    <div class="flex justify-center py-2">
                        <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                    </div>
                <?php endif; ?>
                
                <!-- Send Button -->
                <button type="submit" class="w-full py-3 text-lg font-bold uppercase tracking-wider bg-gradient-to-r from-red-500 to-red-700 hover:from-green-500 hover:to-blue-500 text-white border-2 border-red-500 hover:border-green-500 transition-all duration-300 hover:scale-[1.02] hover:shadow-[0_0_30px_rgba(46,204,113,0.3)]">
                    <i class="fas fa-paper-plane mr-2"></i>
                    <?php echo translate('send_button', 'Send Reset Link'); ?>
                </button>
                
                <!-- Login Link -->
                <div class="text-center pt-2 text-gray-300 text-sm">
                    <?php echo translate('remembered_password', 'Remembered your password?'); ?>
                    <a href="<?php echo htmlspecialchars($base_path . 'login'); ?>" class="text-[#f2cf5b] font-bold hover:text-yellow-300 hover:underline transition-colors">
                        <?php echo translate('login_link_text_simple', 'Log in here'); ?>
                    </a>
                </div>
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