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

$page_class = 'register';

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
            $token = bin2hex(random_bytes(32)); // Activation token
            $stmt = null;
            try {
                $stmt = $site_db->prepare("INSERT INTO pending_accounts (username, email, salt, verifier, token) VALUES (?, ?, ?, ?, ?)");
                if (!$stmt) {
                    error_log("Register: Insert pending account prepare failed: " . $site_db->error);
                    $errors[] = translate('error_database', 'Database error. Please try again later.');
                } else {
                    $stmt->bind_param("sssss", $upper_username, $email, $salt, $verifier, $token);
                    if ($stmt->execute()) {
                        // Use $base_path for activation link
                        $activation_link = $base_path . "activate?token=$token";

                        // Send activation email
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

// Include header
require_once $project_root . 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo translate('meta_description', 'Create an account to join our World of Warcraft server adventure!'); ?>">
    <meta name="robots" content="index">
    <title><?php echo $site_title_name ." ". translate('page_title', 'Create Account'); ?></title>
    <style>
        :root{
            --bg-register:url('<?php echo $base_path; ?>img/backgrounds/bg-register.jpg');
            --hover-wow-gif: url('<?php echo $base_path; ?>img/hover_wow.gif');
        }
    </style>
</head>
<body class="register">
    <main>
        <section class="register-container">
            <h1 class="register-title"><?php echo translate('register_title', 'Create Your Account'); ?></h1>

            <?php if (!empty($errors)): ?>
                <div class="register-form">
                    <?php foreach ($errors as $error): ?>
                        <p class="error"><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($success): ?>
                <div class="register-form">
                    <p class="success"><?php echo htmlspecialchars($success); ?></p>
                    <p class="login-link-container"><a href="<?php echo $base_path; ?>login"><?php echo translate('login_link_text', 'Click here to login'); ?></a></p>
                </div>
            <?php endif; ?>

            <form class="register-form" method="POST" action="">
                <input type="text" name="username" placeholder="<?php echo translate('username_placeholder', 'Username'); ?>" required
                    value="<?php echo htmlspecialchars($username); ?>">
                <input type="email" name="email" placeholder="<?php echo translate('email_placeholder', 'Email'); ?>" required minlength="3" maxlength="36">
                <input type="password" name="password" placeholder="<?php echo translate('password_placeholder', 'Password'); ?>" required minlength="6" maxlength="32">
                <input type="password" name="confirm_password" placeholder="<?php echo translate('password_confirm_placeholder', 'Confirm Password'); ?>" required minlength="6" maxlength="32">
                <?php if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED): ?>
                    <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                <?php endif; ?>
                <button type="submit" class="register-button"><?php echo translate('register_button', 'Register'); ?></button>
            </form>

            <p class="login-link-container"><?php echo sprintf(translate('login_link_text_alt', 'Already have an account? <a href="%s">Login</a>'), htmlspecialchars($base_path . 'login')); ?></p>
        </section>
    </main>

    <?php if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED): ?>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>
    <?php include_once $project_root . 'includes/footer.php'; ?>
</body>
</html>