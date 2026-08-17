<?php
define('ALLOWED_ACCESS', true);

// Include paths.php using __DIR__ to access $project_root and $base_path
require_once __DIR__ . '/../includes/paths.php';

// Use $project_root for filesystem includes
require_once $project_root . 'includes/session.php';
require_once $project_root . 'includes/config.cap.php';
require_once $project_root . 'includes/srp6.php';
require_once $project_root . 'includes/config.mail.php';
require_once $project_root . 'languages/language.php';
$page_class = 'reset_password';
require_once $project_root . 'includes/header.php';

if (isset($_SESSION['user_id'])) {
    header("Location: {$base_path}account");
    exit();
}

$errors = [];
$success = '';
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$valid_token = false;
$email = '';
$username = '';

// Make sure getMailer function exists (fallback)
if (!function_exists('getMailer')) {
    function getMailer() {
        global $smtp_enabled, $smtp_host, $smtp_user, $smtp_pass, $smtp_from, $smtp_name, $smtp_port, $smtp_secure;
        
        require_once __DIR__ . '/../vendor/autoload.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            $mail->CharSet = 'UTF-8';
            
            // Check if SMTP is enabled
            if (isset($smtp_enabled) && $smtp_enabled === true) {
                $mail->isSMTP();
                $mail->Host = $smtp_host ?? '';
                $mail->SMTPAuth = true;
                $mail->Username = $smtp_user ?? '';
                $mail->Password = $smtp_pass ?? '';
                $mail->SMTPSecure = $smtp_secure ?? 'tls';
                $mail->Port = isset($smtp_port) ? (int)$smtp_port : 587;
                $mail->setFrom($smtp_from ?? 'noreply@yourdomain.com', $smtp_name ?? 'Sahtout Account');
            } else {
                // Fallback to PHP mail() function
                $mail->isMail();
                $mail->setFrom('noreply@yourdomain.com', 'Sahtout Account');
            }
            
            $mail->isHTML(true);
        } catch (Exception $e) {
            error_log("Failed to create mailer: " . $e->getMessage());
        }
        
        return $mail;
    }
}

// Function to send confirmation email with improved template
function sendResetConfirmationEmail($username, $email) {
    global $errors, $project_root, $base_path;
    
    try {
        $mail = getMailer();
        $mail->addAddress($email, $username);
        
        // Add logo if exists
        $logo_path = __DIR__ . '/../img/logo.png';
        if (file_exists($logo_path)) {
            $mail->AddEmbeddedImage($logo_path, 'logo_cid');
        }
        
        $mail->Subject = translate('email_subject_confirmation', 'Password Reset Confirmation');
        
        // Build HTML email
        $mail->Body = "<html><body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #0a0e16; color: #d8d8d8;'>";
        $mail->Body .= "<div style='background: linear-gradient(135deg, #161920, #0a0e16); border: 1px solid rgba(201,162,39,0.22); padding: 30px; border-radius: 8px;'>";
        
        // Logo
        if (file_exists($logo_path)) {
            $mail->Body .= "<div style='text-align: center; margin-bottom: 20px;'>
                <img src='cid:logo_cid' alt='Sahtout logo' style='max-width: 200px;'>
            </div>";
        }
        
        // Greeting
        $greeting = translate('email_greeting', 'Welcome, {username}!');
        $mail->Body .= "<h2 style='color: #f2cf5b; font-family: Cinzel, serif; text-align: center;'>" . str_replace('{username}', htmlspecialchars($username), $greeting) . "</h2>";
        
        // Success message
        $mail->Body .= "<div style='text-align: center; background: rgba(46, 204, 113, 0.1); border: 1px solid rgba(46, 204, 113, 0.3); padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        $mail->Body .= "<p style='font-size: 18px; color: #2ecc71;'><i class='fas fa-check-circle'></i> " . translate('email_success', 'Your password has been successfully reset.') . "</p>";
        $mail->Body .= "<p style='font-size: 14px; color: #d8d8d8;'>" . translate('email_can_login', 'You can now log in to your account with your new password.') . "</p>";
        $mail->Body .= "</div>";
        
        // Security warning
        $mail->Body .= "<div style='background: rgba(231, 76, 60, 0.1); border: 1px solid rgba(231, 76, 60, 0.2); padding: 15px; border-radius: 8px; margin: 20px 0;'>";
        $mail->Body .= "<p style='font-size: 13px; color: #e74c3c; text-align: center;'>" . translate('email_contact_support', 'If you did not perform this action, please contact support immediately.') . "</p>";
        $mail->Body .= "</div>";
        
        // Login button
        $login_url = $base_path . 'login';
        $mail->Body .= "<div style='text-align: center; margin: 30px 0;'>
            <a href='$login_url' style='background: linear-gradient(180deg, #f6d478, #c9a227, #8a6a14); color: #1a1200; padding: 14px 35px; text-decoration: none; border-radius: 4px; display: inline-block; font-weight: bold; font-size: 16px; text-transform: uppercase; letter-spacing: 1px;'>
                " . translate('login_button', 'Login Now') . "
            </a>
        </div>";
        
        $mail->Body .= "</div></body></html>";
        
        // Alternative text for email clients that don't support HTML
        $alt_body = translate('email_success', 'Your password has been successfully reset.') . "\n\n";
        $alt_body .= translate('email_can_login', 'You can now log in to your account with your new password.') . "\n\n";
        $alt_body .= translate('email_contact_support', 'If you did not perform this action, please contact support immediately.') . "\n\n";
        $alt_body .= translate('login_button', 'Login Now') . ": " . $login_url;
        $mail->AltBody = $alt_body;
        
        if (!$mail->send()) {
            error_log("Failed to send confirmation email to {$email}: " . $mail->ErrorInfo);
            return false;
        }
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed for {$email}: " . $e->getMessage());
        return false;
    }
}

// Generate nonce for form submission
if (!isset($_SESSION['reset_nonce'])) {
    $_SESSION['reset_nonce'] = bin2hex(random_bytes(16));
}
$nonce = $_SESSION['reset_nonce'];

if ($token) {
    // Delete expired or used tokens
    $stmt_delete = $site_db->prepare("DELETE FROM password_resets WHERE expires_at < NOW() OR used = 1");
    $stmt_delete->execute();
    $stmt_delete->close();

    // Validate token
    $stmt = $site_db->prepare("SELECT email FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $email = $row['email'];

        // Check account table first
        $stmt2 = $auth_db->prepare("SELECT username FROM account WHERE LOWER(email) = LOWER(?)");
        $stmt2->bind_param('s', $email);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        if ($result2->num_rows > 0) {
            $username = $result2->fetch_assoc()['username'];
            $valid_token = true;
        } else {
            // Check pending_accounts table
            $stmt3 = $site_db->prepare("SELECT username FROM pending_accounts WHERE LOWER(email) = LOWER(?)");
            $stmt3->bind_param('s', $email);
            $stmt3->execute();
            $result3 = $stmt3->get_result();
            if ($result3->num_rows > 0) {
                $errors[] = translate('error_account_not_active', 'Your account is not active yet. Please activate your account to reset your password.');
            } else {
                $errors[] = translate('error_account_not_exist', 'Account does not exist.');
            }
            $stmt3->close();
        }
        $stmt2->close();
    } else {
        $errors[] = translate('error_token_invalid', 'The reset link is invalid or has expired.');
    }
    $stmt->close();
} else {
    $errors[] = translate('error_no_token', 'No reset token provided.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    // Validate nonce to prevent re-submission
    $submitted_nonce = $_POST['nonce'] ?? '';
    if ($submitted_nonce !== $_SESSION['reset_nonce']) {
        $errors[] = translate('error_invalid_nonce', 'Invalid or expired form submission. Please try again.');
    } else {
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validate password
        if (empty($password)) {
            $errors[] = translate('error_password_required', 'Password is required.');
        } elseif (strlen($password) < 8) {
            $errors[] = translate('error_password_short', 'Password must be at least 8 characters long.');
        } elseif ($password !== $confirm_password) {
            $errors[] = translate('error_password_mismatch', 'Passwords do not match.');
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

        if (empty($errors)) {
            // Generate new SRP-6a salt and verifier
            $salt = SRP6::generateSalt();
            $verifier = SRP6::calculateVerifier($username, $password, $salt);

            // Update account table
            $stmt = $auth_db->prepare("UPDATE account SET salt = ?, verifier = ? WHERE email = ?");
            $stmt->bind_param('sss', $salt, $verifier, $email);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                // Delete token from password_resets
                $stmt2 = $site_db->prepare("DELETE FROM password_resets WHERE token = ?");
                $stmt2->bind_param('s', $token);
                $stmt2->execute();
                $stmt2->close();
                
                // Send confirmation email if SMTP is enabled
                global $smtp_enabled;
                $is_smtp_enabled = isset($smtp_enabled) && $smtp_enabled === true;
                
                if ($is_smtp_enabled) {
                    $email_sent = sendResetConfirmationEmail($username, $email);
                    if (!$email_sent) {
                        // Email failed but password was reset - still show success with a warning
                        $success = translate('success_password_reset', 'Your password has been successfully reset. You can now log in.');
                    } else {
                        $success = translate('success_password_reset', 'Your password has been successfully reset. You can now log in.');
                    }
                } else {
                    $success = translate('success_password_reset', 'Your password has been successfully reset. You can now log in.');
                }
                
                // Clear nonce and token to prevent re-submission
                unset($_SESSION['reset_nonce']);
                $token = '';
                $valid_token = false;
            } else {
                $errors[] = translate('error_password_update_failed', 'Failed to update password.');
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="description" content="<?php echo translate('meta_description', 'Reset your password for our World of Warcraft server.'); ?>">
    <title><?php echo $site_title_name ." ". translate('page_title', 'Reset Password'); ?></title>
    
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/tailwind.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    
    <style>
        /* Page background - Show full background image */
        body {
            background: url('<?php echo $base_path; ?>img/backgrounds/bg-reset-password.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            padding-top: 112px;
            margin: 0;
            position: relative;
        }
        
        body::before {
            display: none;
        }
        
        /* Main content wrapper */
        .reset-content {
            position: relative;
            z-index: 1;
        }
        
        /* Glass effect container - Same as forgot_password */
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
        
        /* Wow title gradient - Same as forgot_password */
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
        
        /* Input fields - Same as forgot_password */
        .input-reset {
            background: rgba(0, 0, 0, 0.5) !important;
            border: 2px solid rgba(201,162,39,0.3) !important;
            color: #ffffff !important;
            transition: all 0.3s ease;
        }
        
        .input-reset:focus {
            border-color: #f2cf5b !important;
            box-shadow: 0 0 20px rgba(242,207,82,0.15) !important;
            outline: none;
        }
        
        .input-reset::placeholder {
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
<div class="reset-content relative z-10 min-h-screen flex items-center justify-center px-4 py-8">
    <div class="container mx-auto max-w-7xl px-2 sm:px-4 flex items-center justify-center">
        
        <!-- Main Container -->
        <div class="glass-container p-6 md:p-10">
            
            <!-- Icon -->
            <div class="text-center mb-4">
                <div class="w-20 h-20 mx-auto bg-[rgba(242,207,82,0.1)] border border-[rgba(201,162,39,0.3)] flex items-center justify-center">
                    <i class="fas fa-lock text-4xl text-[#f2cf5b]"></i>
                </div>
            </div>
            
            <!-- Title -->
            <h1 class="wow-title text-3xl md:text-5xl font-bold text-center mb-6">
                <?php echo translate('reset_title', 'Reset Password'); ?>
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
                    <p class="mt-3">
                        <a href="<?php echo $base_path; ?>login" class="inline-block px-6 py-2 bg-[rgba(242,207,82,0.15)] border border-[rgba(201,162,39,0.3)] text-[#f2cf5b] hover:bg-[rgba(242,207,82,0.25)] transition-all duration-300 font-semibold">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            <?php echo translate('login_link', 'Back to Login'); ?>
                        </a>
                    </p>
                </div>
            <?php elseif ($valid_token): ?>
                <!-- Form -->
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce); ?>">
                    
                    <div class="relative">
                        <i class="fas fa-lock text-[rgba(201,162,39,0.4)] absolute top-3.5 left-3"></i>
                        <input type="password" name="password" placeholder="<?php echo translate('password_placeholder', 'New Password'); ?>" required minlength="8" class="input-reset w-full pl-10 pr-4 py-3 text-base">
                    </div>
                    
                    <div class="relative">
                        <i class="fas fa-check-circle text-[rgba(201,162,39,0.4)] absolute top-3.5 left-3"></i>
                        <input type="password" name="confirm_password" placeholder="<?php echo translate('confirm_password_placeholder', 'Confirm Password'); ?>" required minlength="8" class="input-reset w-full pl-10 pr-4 py-3 text-base">
                    </div>

                    <?php if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED): ?>
                        <div class="flex justify-center py-2">
                            <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Reset Button - Red to Green-Blue (same as forgot_password) -->
                    <button type="submit" class="w-full py-3 text-lg font-bold uppercase tracking-wider bg-gradient-to-r from-red-500 to-red-700 hover:from-green-500 hover:to-blue-500 text-white border-2 border-red-500 hover:border-green-500 transition-all duration-300 hover:scale-[1.02] hover:shadow-[0_0_30px_rgba(46,204,113,0.3)]">
                        <i class="fas fa-key mr-2"></i>
                        <?php echo translate('reset_button', 'Reset Password'); ?>
                    </button>
                    
                    <!-- Login Link -->
                    <div class="text-center pt-2 text-gray-300 text-sm">
                        <a href="<?php echo htmlspecialchars($base_path . 'login'); ?>" class="text-[#f2cf5b] font-bold hover:text-yellow-300 hover:underline transition-colors">
                            <i class="fas fa-arrow-left mr-1"></i>
                            <?php echo translate('login_link_text_simple', 'Back to Login'); ?>
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>
<?php include_once $project_root . 'includes/footer.php'; ?>
</body>
</html>