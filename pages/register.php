<?php
define('ALLOWED_ACCESS', true);

// Include paths.php using __DIR__ to access $project_root and $base_path
require_once __DIR__ . '/../includes/paths.php';

// Use $project_root for filesystem includes
require_once $project_root . 'includes/session.php';
require_once $project_root . 'languages/language.php';
require_once $project_root . 'includes/config.cap.php';
require_once $project_root . 'includes/config.mail.php';
require_once $project_root . 'includes/srp6.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$errors = [];
$success = '';
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = trim($_POST['email'] ?? '');

    // Verify reCAPTCHA only if enabled
    if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED) {
        $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
        if (empty($recaptcha_response)) {
            $errors[] = translate('error_recaptcha_empty', 'Please complete the CAPTCHA.');
        } else {
            $verify = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . RECAPTCHA_SECRET_KEY . '&response=' . $recaptcha_response);
            $captcha_result = json_decode($verify);
            if (!$captcha_result->success) {
                $errors[] = translate('error_recaptcha_failed', 'CAPTCHA verification failed.');
            }
        }
    }

    // Validation
    if (strlen($username) < 3 || strlen($username) > 16) {
        $errors[] = translate('error_username_invalid_length', 'Username must be between 3 and 16 characters.');
    }
    if (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
        $errors[] = translate('error_username_invalid_chars', 'Username can only contain letters and numbers.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = translate('error_email_invalid', 'Invalid email address.');
    }
    if (strlen($password) < 6) {
        $errors[] = translate('error_password_short', 'Password must be at least 6 characters.');
    }
    if ($password !== $confirm_password) {
        $errors[] = translate('error_password_mismatch', 'Passwords do not match.');
    }

    // Check for existing username and email in pending_accounts
    if (empty($errors)) {
        $upper_username = strtoupper($username);
        $stmt = $site_db->prepare("SELECT username, email FROM pending_accounts WHERE username = ? OR email = ?");
        if (!$stmt) {
            error_log("Register: Pending accounts prepare failed: " . $site_db->error);
            $errors[] = translate('error_database', 'Database error. Please try again later.');
        } else {
            $stmt->bind_param('ss', $upper_username, $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $errors[] = translate('error_account_pending', 'An account with this username or email is already pending or registered. Please use a different username or email, or activate your existing account.');
            }
            $stmt->close();
        }
    }

    // Check for existing username and email in acore_auth.account
    if (empty($errors)) {
        if ($auth_db->connect_error) {
            die("Database connection failed: " . $auth_db->connect_error);
        }

        // Check if username exists
        $stmt = $auth_db->prepare("SELECT id FROM account WHERE username = ?");
        if (!$stmt) {
            error_log("Register: Account username prepare failed: " . $auth_db->error);
            $errors[] = translate('error_database', 'Database error. Please try again later.');
        } else {
            $stmt->bind_param('s', $upper_username);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $errors[] = translate('error_username_exists', 'Username already exists. Please choose a different username.');
            }
            $stmt->close();
        }

        // Check if email exists
        $stmt = $auth_db->prepare("SELECT id FROM account WHERE email = ?");
        if (!$stmt) {
            error_log("Register: Account email prepare failed: " . $auth_db->error);
            $errors[] = translate('error_database', 'Database error. Please try again later.');
        } else {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $errors[] = translate('error_email_exists', 'Email already in use. Please choose a different email.');
            }
            $stmt->close();
        }
    }

    // Proceed with account creation based on SMTP_ENABLED
    if (empty($errors)) {
        $salt = SRP6::GenerateSalt();
        $verifier = SRP6::CalculateVerifier($username, $password, $salt);
        $account = [
            'username' => $username,
            'salt' => $salt,
            'verifier' => $verifier,
            'email' => $email
        ];

        if (defined('SMTP_ENABLED') && SMTP_ENABLED) {
            // SMTP enabled: Store in pending_accounts and send activation email
            $token = bin2hex(random_bytes(32));
            $stmt = null;
            try {
                $stmt = $site_db->prepare("INSERT INTO pending_accounts (username, email, salt, verifier, token) VALUES (?, ?, ?, ?, ?)");
                if (!$stmt) {
                    error_log("Register: Insert pending account prepare failed: " . $site_db->error);
                    $errors[] = translate('error_database', 'Database error. Please try again later.');
                } else {
                    $stmt->bind_param("sssss", $upper_username, $email, $salt, $verifier, $token);
                    if ($stmt->execute()) {
                        $activation_link = $site_url . "activate?token=$token";

                        try {
                            $mail = getMailer();
                            $mail->addAddress($email, $username);
                            $mail->AddEmbeddedImage('logo.png', 'logo_cid');
                            $mail->Subject = translate('email_subject', 'Activate Your Account');
                            $mail->Body = "
                                <h2>" . str_replace('{username}', htmlspecialchars($username), translate('email_greeting', 'Welcome, {username}!')) . "</h2>
                                <img src='cid:logo_cid' alt='Sahtout logo'>
                                <p>" . translate('email_body', 'Thank you for registering. Please click the button below to activate your account:') . "</p>
                                <p><a href='$activation_link' style='background-color:#6e4d15;color:white;padding:10px 20px;text-decoration:none;'>" . translate('email_activate_button', 'Activate Account') . "</a></p>
                                <p>" . translate('email_ignore', 'If you did not register, please ignore this email.') . "</p>
                            ";

                            if ($mail->send()) {
                                $success = translate('success_account_created', 'Account created. Check your email to activate your account.');
                            } else {
                                $errors[] = translate('error_email_failed', 'Failed to send activation email. Please contact support.');
                            }
                        } catch (Exception $e) {
                            $errors[] = translate('error_email_failed', 'Failed to send activation email: ') . $mail->ErrorInfo;
                        }
                    } else {
                        $errors[] = translate('error_registration_failed', 'Failed to store pending account.');
                    }
                }
            } catch (mysqli_sql_exception $e) {
                $errors[] = translate('error_account_pending', 'An account with this username or email is already pending or registered. Please use a different username or email, or activate your existing account.');
            } finally {
                if ($stmt instanceof mysqli_stmt) {
                    $stmt->close();
                }
            }
        } else {
            // SMTP disabled: Directly create account in acore_auth.account
            $upper_username = strtoupper($account['username']);
            $stmt_insert = $auth_db->prepare("INSERT INTO account (username, salt, verifier, email, reg_mail, expansion) VALUES (?, ?, ?, ?, ?, 2)");
            if (!$stmt_insert) {
                $errors[] = translate('error_database', 'Database query error: ') . $auth_db->error;
            } else {
                $stmt_insert->bind_param('sssss', $upper_username, $account['salt'], $account['verifier'], $account['email'], $account['email']);
                if ($stmt_insert->execute()) {
                    $success = translate('success_account_created_no_email', 'Account created successfully! You can now log in.');
                } else {
                    $errors[] = translate('error_registration_failed', 'Failed to create account.');
                }
                $stmt_insert->close();
            }
        }
    }
}

// Include header after processing form
include_once $project_root . 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="description" content="<?php echo translate('meta_description', 'Create an account to join our World of Warcraft server adventure!'); ?>">
    <title><?php echo $site_title_name ." ". translate('page_title', 'Create Account'); ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        /* Page background - Show full background image */
        body {
            background: url('<?php echo $base_path; ?>img/backgrounds/bg-register.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            padding-top: 112px;
            margin: 0;
            position: relative;
        }
        
        /* REMOVED: Dark overlay */
        body::before {
            display: none;
        }
        
        /* Main content wrapper */
        .register-content {
            position: relative;
            z-index: 1;
        }
        
        /* Glass effect container - Same as login */
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
        
        /* Wow title gradient - Same as login */
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
        
        /* Input fields - Same as login */
        .input-register {
            background: rgba(0, 0, 0, 0.5) !important;
            border: 2px solid rgba(201,162,39,0.3) !important;
            color: #ffffff !important;
            transition: all 0.3s ease;
            border-radius: 0;
        }
        
        .input-register:focus {
            border-color: #f2cf5b !important;
            box-shadow: 0 0 20px rgba(242,207,82,0.15) !important;
            outline: none;
        }
        
        .input-register::placeholder {
            color: #6b7280 !important;
        }
        
        /* Register Button - Same style as login but green */
        .btn-register {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: #ffffff;
            font-weight: 700;
            transition: all 0.3s ease;
            border: 2px solid #2ecc71;
            border-radius: 0;
            text-shadow: 0 1px 0 rgba(0,0,0,0.2);
        }
        
        .btn-register:hover {
            background: linear-gradient(135deg, #3498db, #2980b9);
            border-color: #3498db;
            transform: scale(1.02);
            box-shadow: 0 0 30px rgba(52, 152, 219, 0.3);
        }
        
        /* Error messages - Same as login */
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
<div class="register-content min-h-screen flex items-center justify-center px-4 py-8">
    <div class="container mx-auto max-w-7xl px-2 sm:px-4 flex items-center justify-center">
        
        <!-- Main Container - Same as login -->
        <div class="glass-container">
            
            <!-- Icon - Same as login -->
            <div class="text-center mb-4">
                <div class="w-20 h-20 mx-auto bg-[rgba(242,207,82,0.1)] border border-[rgba(201,162,39,0.3)] flex items-center justify-center">
                    <i class="fas fa-user-plus text-4xl text-[#f2cf5b]"></i>
                </div>
            </div>
            
            <!-- Title - Same as login -->
            <h1 class="wow-title text-4xl md:text-5xl font-bold text-center mb-6">
                <?php echo translate('register_title', 'Create Your Account'); ?>
            </h1>

            <!-- Errors - Same as login -->
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <?php foreach ($errors as $error): ?>
                        <p><i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($success): ?>
                <div class="success-message">
                    <p><i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?></p>
                    <p class="mt-3">
                        <a href="<?php echo $base_path; ?>login" class="success-login-btn">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            <?php echo translate('login_link_text', 'Click here to login'); ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>

            <!-- Form - Same as login -->
            <form method="POST" class="space-y-4">
                <div>
                    <i class="fas fa-user text-[rgba(201,162,39,0.4)] absolute mt-3.5 ml-3"></i>
                    <input type="text" name="username" placeholder="<?php echo translate('username_placeholder', 'Username'); ?>" required value="<?php echo htmlspecialchars($username); ?>" class="input-register w-full pl-10 pr-4 py-3 text-base">
                </div>
                
                <div>
                    <i class="fas fa-envelope text-[rgba(201,162,39,0.4)] absolute mt-3.5 ml-3"></i>
                    <input type="email" name="email" placeholder="<?php echo translate('email_placeholder', 'Email'); ?>" required minlength="3" maxlength="36" class="input-register w-full pl-10 pr-4 py-3 text-base">
                </div>
                
                <div>
                    <i class="fas fa-lock text-[rgba(201,162,39,0.4)] absolute mt-3.5 ml-3"></i>
                    <input type="password" name="password" placeholder="<?php echo translate('password_placeholder', 'Password'); ?>" required minlength="6" maxlength="32" class="input-register w-full pl-10 pr-4 py-3 text-base">
                </div>
                
                <div>
                    <i class="fas fa-check-circle text-[rgba(201,162,39,0.4)] absolute mt-3.5 ml-3"></i>
                    <input type="password" name="confirm_password" placeholder="<?php echo translate('password_confirm_placeholder', 'Confirm Password'); ?>" required minlength="6" maxlength="32" class="input-register w-full pl-10 pr-4 py-3 text-base">
                </div>

                <?php if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED): ?>
                    <div class="flex justify-center py-2">
                        <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                    </div>
                <?php endif; ?>
                
                <!-- Register Button - Green, Hover: Blue -->
                <button type="submit" class="btn-register w-full py-3 text-lg font-bold uppercase tracking-wider">
                    <i class="fas fa-user-plus mr-2"></i>
                    <?php echo translate('register_button', 'Register'); ?>
                </button>
                
                <!-- Login Link - Same as register link in login -->
                <div class="text-center pt-2 text-gray-300 text-sm">
                    <?php echo translate('already_have_account', 'Already have an account?'); ?>
                    <a href="<?php echo htmlspecialchars($base_path . 'login'); ?>" class="text-[#f2cf5b] font-bold hover:text-yellow-300 hover:underline transition-colors">
                        <?php echo translate('login_link_text_simple', 'Login'); ?>
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