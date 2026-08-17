<?php
define('ALLOWED_ACCESS', true);

// Include paths.php using __DIR__ to access $project_root and $base_path
require_once __DIR__ . '/../includes/paths.php';

// Use $project_root for filesystem includes
require_once $project_root . 'includes/session.php'; // Includes config.php for DB
require_once $project_root . 'includes/config.mail.php'; // Email config
require_once $project_root . 'languages/language.php'; // Translations

$page_class = 'activate';

$errors = [];
$success = '';
$token = $_GET['token'] ?? '';

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

// Function to send activation confirmation email with improved template
function sendActivationConfirmationEmail($username, $email) {
    global $errors, $site_url, $project_root, $base_path;
    
    try {
        $mail = getMailer();
        $mail->addAddress($email, $username);
        
        // Add logo if exists
        $logo_path = __DIR__ . '/../img/logo.png';
        if (file_exists($logo_path)) {
            $mail->AddEmbeddedImage($logo_path, 'logo_cid');
        }
        
        $mail->Subject = translate('email_subject', 'Account Activation Confirmation');
        $login_link = $site_url . 'login';
        
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
        $mail->Body .= "<p style='font-size: 18px; color: #2ecc71;'><i class='fas fa-check-circle'></i> " . translate('email_success', 'Your account has been successfully activated.') . "</p>";
        $mail->Body .= "</div>";
        
        // Login message
        $mail->Body .= "<p style='text-align: center; font-size: 16px;'>" . translate('email_login', 'You can now log in to start your adventure by clicking the button below:') . "</p>";
        
        // Login button
        $mail->Body .= "<div style='text-align: center; margin: 30px 0;'>
            <a href='$login_link' style='background: linear-gradient(180deg, #f6d478, #c9a227, #8a6a14); color: #1a1200; padding: 14px 35px; text-decoration: none; border-radius: 4px; display: inline-block; font-weight: bold; font-size: 16px; text-transform: uppercase; letter-spacing: 1px;'>
                " . translate('email_button', 'Log In') . "
            </a>
        </div>";
        
        // Security warning
        $mail->Body .= "<div style='background: rgba(231, 76, 60, 0.1); border: 1px solid rgba(231, 76, 60, 0.2); padding: 15px; border-radius: 8px; margin: 20px 0;'>";
        $mail->Body .= "<p style='font-size: 13px; color: #e74c3c; text-align: center;'>" . translate('email_contact_support', 'If you did not activate this account, please contact support immediately.') . "</p>";
        $mail->Body .= "</div>";
        
        $mail->Body .= "</div></body></html>";
        
        // Alternative text for email clients that don't support HTML
        $alt_body = translate('email_success', 'Your account has been successfully activated.') . "\n\n";
        $alt_body .= translate('email_login', 'You can now log in to start your adventure by clicking the link below:') . "\n\n";
        $alt_body .= translate('email_button', 'Log In') . ": " . $login_link . "\n\n";
        $alt_body .= translate('email_contact_support', 'If you did not activate this account, please contact support immediately.');
        $mail->AltBody = $alt_body;
        
        if (!$mail->send()) {
            error_log("Failed to send activation confirmation email to {$email}: " . $mail->ErrorInfo);
            $errors[] = translate('error_email_failed', 'Failed to send confirmation email: ') . $mail->ErrorInfo;
            return false;
        }
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed for {$email}: " . $e->getMessage());
        $errors[] = translate('error_email_failed', 'Failed to send confirmation email: ') . $e->getMessage();
        return false;
    }
}

// ==========================
// Activation Logic
// ==========================
if (!$token) {
    $errors[] = translate('error_no_token', 'Invalid activation link.');
} else {
    // Look for pending account
    $stmt_select = $site_db->prepare("SELECT username, email, salt, verifier FROM pending_accounts WHERE token = ? AND activated = 0");
    if (!$stmt_select) {
        $errors[] = translate('error_database', 'Database query error: ') . $site_db->error;
    } else {
        $stmt_select->bind_param('s', $token);
        $stmt_select->execute();
        $result = $stmt_select->get_result();

        if ($result->num_rows === 0) {
            $errors[] = translate('error_token_invalid', 'Invalid or expired activation link.');
        } else {
            $account = $result->fetch_assoc();
            $stmt_select->close();

            // Insert into acore_auth.account
            $upper_username = strtoupper($account['username']);
            $stmt_insert = $auth_db->prepare("INSERT INTO account (username, salt, verifier, email, reg_mail, expansion) VALUES (?, ?, ?, ?, ?, 2)");
            if (!$stmt_insert) {
                $errors[] = translate('error_database', 'Database query error: ') . $auth_db->error;
            } else {
                $stmt_insert->bind_param('sssss', $upper_username, $account['salt'], $account['verifier'], $account['email'], $account['email']);
                if ($stmt_insert->execute()) {
                    $stmt_insert->close();

                    // Delete from pending_accounts
                    $stmt_delete = $site_db->prepare("DELETE FROM pending_accounts WHERE token = ?");
                    if (!$stmt_delete) {
                        $errors[] = translate('error_database', 'Database query error: ') . $site_db->error;
                    } else {
                        $stmt_delete->bind_param('s', $token);
                        if ($stmt_delete->execute()) {
                            // Send confirmation email if SMTP is enabled
                            global $smtp_enabled;
                            $is_smtp_enabled = isset($smtp_enabled) && $smtp_enabled === true;
                            
                            if ($is_smtp_enabled) {
                                $email_sent = sendActivationConfirmationEmail($account['username'], $account['email']);
                                if (!$email_sent) {
                                    // Email failed but account was activated - still show success
                                    $success = translate('success_account_activated', 'Your account has been activated! You will be redirected to the login page shortly.');
                                } else {
                                    $success = translate('success_account_activated', 'Your account has been activated! You will be redirected to the login page shortly.');
                                }
                            } else {
                                $success = translate('success_account_activated', 'Your account has been activated! You will be redirected to the login page shortly.');
                            }
                            
                            header("Refresh: 3; url={$base_path}login");
                        } else {
                            $errors[] = translate('error_delete_failed', 'Failed to delete pending account: ') . $site_db->error;
                        }
                        $stmt_delete->close();
                    }
                } else {
                    $errors[] = translate('error_activation_failed', 'Failed to activate account: ') . $auth_db->error;
                }
            }
        }
    }
}

// Include header.php after logic to avoid headers-already-sent error
require_once $project_root . 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo translate('meta_description', 'Activate your account to join our World of Warcraft server adventure!'); ?>">
    <meta name="robots" content="index">
    <title><?php echo $site_title_name ." ". translate('page_title', '- Activate Account'); ?></title>
    
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/tailwind.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    
    <style>
        /* Page background */
        body {
            background: url('<?php echo $base_path; ?>img/backgrounds/bg-register.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            padding-top: 112px;
            margin: 0;
            position: relative;
        }
        
        /* Main content wrapper */
        .activate-content {
            position: relative;
            z-index: 1;
        }
        
        /* Glass effect container - Same as login/register */
        .glass-container {
            background: rgba(5, 7, 11, 0.75);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(201,162,39,.22);
            border-radius: 0;
            padding: 2.5rem 2.5rem;
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
        
        /* Wow title gradient - Same as login/register */
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
        
        /* Error messages */
        .error-message {
            color: #ef4444;
            font-size: 0.9rem;
            text-align: center;
            padding: 0.5rem;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            margin-bottom: 1rem;
        }
        
        /* Success message */
        .success-message {
            color: #2ecc71;
            font-size: 0.9rem;
            text-align: center;
            padding: 0.5rem;
            background: rgba(46, 204, 113, 0.1);
            border: 1px solid rgba(46, 204, 113, 0.2);
            margin-bottom: 1rem;
        }
        
        /* Success login button */
        .success-login-btn {
            display: inline-block;
            padding: 0.5rem 1.5rem;
            background: linear-gradient(135deg, #f2cf5b, #c9a227);
            color: #1a1200;
            font-weight: 700;
            border: 2px solid #f2cf5b;
            border-radius: 0;
            transition: all 0.3s ease;
            text-shadow: 0 1px 0 rgba(255,255,255,0.2);
            text-decoration: none;
        }
        
        .success-login-btn:hover {
            background: linear-gradient(135deg, #f6d478, #d4b040);
            transform: scale(1.05);
            box-shadow: 0 0 20px rgba(242, 207, 82, 0.3);
        }
        
        /* Responsive */
        @media (max-width: 767px) {
            body {
                padding-top: 96px;
            }
            
            .glass-container {
                padding: 1.5rem 1rem;
                max-width: 100%;
                margin: 0 0.5rem;
            }
        }
    </style>
</head>
<body>
<div class="activate-content min-h-screen flex items-center justify-center px-4 py-8">
    <div class="container mx-auto max-w-7xl px-2 sm:px-4 flex items-center justify-center">
        
        <!-- Main Container -->
        <div class="glass-container">
            
            <!-- Icon -->
            <div class="text-center mb-4">
                <div class="w-20 h-20 mx-auto bg-[rgba(242,207,82,0.1)] border border-[rgba(201,162,39,0.3)] flex items-center justify-center">
                    <i class="fas fa-check-circle text-4xl text-[#f2cf5b]"></i>
                </div>
            </div>
            
            <!-- Title -->
            <h1 class="wow-title text-4xl md:text-5xl font-bold text-center mb-6">
                <?php echo translate('activate_title', 'Activate Your Account'); ?>
            </h1>

            <!-- Errors -->
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <?php foreach ($errors as $error): ?>
                        <p><i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Success -->
            <?php if ($success): ?>
                <div class="success-message">
                    <p><i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?></p>
                    <p class="mt-3">
                        <a href="<?php echo htmlspecialchars($base_path . 'login'); ?>" class="success-login-btn">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            <?php echo translate('login_link_text_simple', 'Click here to login'); ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>
            
            <!-- Show a message if no errors and no success (shouldn't happen normally) -->
            <?php if (empty($errors) && !$success): ?>
                <div class="text-center text-gray-300 text-sm">
                    <p><?php echo translate('processing_activation', 'Processing your account activation...'); ?></p>
                    <p class="mt-3">
                        <a href="<?php echo htmlspecialchars($base_path . 'login'); ?>" class="text-[#f2cf5b] font-bold hover:text-yellow-300 hover:underline transition-colors">
                            <i class="fas fa-arrow-left mr-1"></i>
                            <?php echo translate('login_link_text_simple', 'Back to Login'); ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once $project_root . 'includes/footer.php'; ?>
</body>
</html>