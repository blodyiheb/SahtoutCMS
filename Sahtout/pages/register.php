<?php
define('ALLOWED_ACCESS', true);
require_once '../includes/session.php';
require_once '../languages/language.php'; // Add for translate()
require_once '../includes/config.cap.php'; // reCAPTCHA keys
require_once '../includes/config.mail.php'; // PHPMailer configuration
require_once 'C:/xampp/htdocs/Sahtout/includes/srp6.php';
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
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';

    // Verify reCAPTCHA
    if (empty($recaptcha_response)) {
        $errors[] = translate('error_recaptcha_empty', 'Please complete the CAPTCHA.');
    } else {
        $verify = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . RECAPTCHA_SECRET_KEY . '&response=' . $recaptcha_response);
        $captcha_result = json_decode($verify);
        if (!$captcha_result->success) {
            $errors[] = translate('error_recaptcha_failed', 'CAPTCHA verification failed.');
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
        $stmt->bind_param('ss', $upper_username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = translate('error_account_pending', 'An account with this username or email is already pending or registered. Please use a different username or email, or activate your existing account.');
        }
        $stmt->close();
    }

    // Check for existing username and email in acore_auth.account
    if (empty($errors)) {
        if ($auth_db->connect_error) {
            die("Database connection failed: " . $auth_db->connect_error);
        }

        // Check if username exists
        $stmt = $auth_db->prepare("SELECT id FROM account WHERE username = ?");
        $stmt->bind_param('s', $upper_username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = translate('error_username_exists', 'Username already exists. Please choose a different username.');
        }
        $stmt->close();

        // Check if email exists
        $stmt = $auth_db->prepare("SELECT id FROM account WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = translate('error_email_exists', 'Email already in use. Please choose a different email.');
        }
        $stmt->close();
    }

    // Proceed with pending account creation and email sending
    if (empty($errors)) {
        $salt = SRP6::GenerateSalt();
        $verifier = SRP6::CalculateVerifier($username, $password, $salt);
        $token = bin2hex(random_bytes(32)); // Activation token

        // Insert into sahtout_site.pending_accounts
        $stmt = null; // Initialize the variable
        try {
            $stmt = $site_db->prepare("INSERT INTO pending_accounts (username, email, salt, verifier, token) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $upper_username, $email, $salt, $verifier, $token);

            if ($stmt->execute()) {
                // Detect protocol dynamically
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";

                // Get host dynamically from $_SERVER
                $host = $_SERVER['HTTP_HOST'];

                // Build activation link
                $activation_link = $protocol . $host . "/sahtout/activate?token=$token";

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
                        $success = "Account created. Check your email to activate your account.";
                    } else {
                        $errors[] = translate('error_email_failed', 'Failed to send activation email. Please contact support.');
                    }
                } catch (Exception $e) {
                    $errors[] = translate('error_email_failed', 'Failed to send activation email: ') . $mail->ErrorInfo;
                }
            } else {
                $errors[] = translate('error_registration_failed', 'Failed to store pending account.');
            }
        } catch (mysqli_sql_exception $e) {
            $errors[] = translate('error_account_pending', 'An account with this username or email is already pending or registered. Please use a different username or email, or activate your existing account.');
        } finally {
            if ($stmt instanceof mysqli_stmt) {
                $stmt->close();
            }
        }
    }
}

// Include header
require_once '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo translate('meta_description', 'Create an account to join our World of Warcraft server adventure!'); ?>">
    <meta name="robots" content="index">
    <title><?php echo translate('page_title', 'Create Account'); ?></title>
    <style>
        * {
            box-sizing: border-box; /* Prevent padding/margins from causing overflow */
        }

        html, body {
            width: 100%;
            overflow-x: hidden;
            margin: 0;
            background: url('/sahtout/img/backgrounds/bg-register.jpg') no-repeat center center fixed;
            background-size: cover;
            font-family: 'UnifrakturCook', 'Arial', sans-serif;
            color: #fff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        body::before {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5); /* Dark overlay with blur */
            backdrop-filter: blur(5px); /* Subtle blur effect */
            z-index: 1;
        }

        main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1rem;
            width: 100%;
            position: relative;
            z-index: 2;
        }

        .register-container {
            max-width: 480px;
            width: calc(100% - 2rem);
            background: #1a1a1a7c; /* Darker WoW-style background */
            border: 3px solid #f1c40f; /* Vibrant gold border */
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(241, 196, 15, 0.4), 0 0 40px rgba(0, 0, 0, 0.8);
            padding: 2rem;
            animation: pulse 3s infinite ease-in-out;
            transition: transform 0.3s ease;
        }

        .register-container:hover {
            transform: translateY(-5px) rotate(1deg); /* Subtle 3D hover effect */
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 8px 24px rgba(241, 196, 15, 0.4), 0 0 40px rgba(0, 0, 0, 0.8); }
            50% { box-shadow: 0 8px 32px rgba(241, 196, 15, 0.6), 0 0 48px rgba(0, 0, 0, 0.9); }
        }

        .register-form {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .register-title {
            font-size: 3rem;
            font-family: 'UnifrakturCook', sans-serif;
            color: #f1c40f;
            text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.9);
            margin-bottom: 1.5rem;
            text-align: center;
            letter-spacing: 1px;
        }

        .register-form form {
            display: flex;
            flex-direction: column;
            gap: 2rem; /* Increased from 1.2rem for more vertical space */
        }

        .register-form input {
            width: 100%;
            padding: 0.9rem;
            font-size: 1.1rem;
            font-family: 'Arial', sans-serif;
            background: #2c2c2c;
            color: #fff;
            border: 2px solid #f1c40f;
            border-radius: 6px;
            outline: none;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 0.7rem; /* Add margin for extra spacing */
        }

        .register-form input:focus {
            border-color: #ffe600;
            box-shadow: 0 0 8px rgba(255, 230, 0, 0.6);
        }

        .register-form input::placeholder {
            color: #999;
            font-size: 1rem;
        }

        .g-recaptcha {
            margin: 1.2rem auto 0.5rem; /* Add margin-bottom for spacing below reCAPTCHA */
            display: flex;
            justify-content: center;
        }

        .register-button {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); /* Fiery orange-red gradient */
            color: #fff;
            border: 2px solid #f1c40f;
            padding: 0.9rem 1.8rem;
            font-size: 1.3rem;
            border-radius: 6px;
            cursor: url('/Sahtout/img/hover_wow.gif') 16 16, auto;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .register-button:hover {
            background: linear-gradient(135deg, #0e65d6ff 0%, #26a938ff 100%);
            transform: scale(1.05);
            box-shadow: 0 4px 16px rgba(231, 76, 60, 0.6);
        }

        .register-form .error {
            color: #e74c3c;
            font-size: 1.1rem;
            font-family: 'Arial', sans-serif;
            text-align: center;
            margin: 0.6rem 0 0;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);
        }

        .register-form .success {
            color: #2ecc71;
            font-size: 1.1rem;
            font-family: 'Arial', sans-serif;
            text-align: center;
            margin: 0.6rem 0 0;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);
        }

        .login-link-container {
            text-align: center;
            font-size: 1.1rem;
            font-family: 'UnifrakturCook', sans-serif;
            color: #fff;
            margin-top: 1.2rem;
        }

        .login-link-container a {
            color: #f1c40f;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .login-link-container a:hover {
            color: #ffe600;
            text-decoration: underline;
        }

        @media (max-width: 767px) {
            html, body {
                width: 100%;
                overflow-x: hidden;
            }

            main {
                padding: 0;
                margin-top: 80px;
            }

            .register-container {
                max-width: 100%;
                width: calc(100% - 1.5rem);
                padding: 1.5rem;
                margin: 1.5rem auto;
                box-shadow: 0 6px 16px rgba(241, 196, 15, 0.3);
            }

            .register-container:hover {
                transform: translateY(-3px) rotate(0.5deg); /* Reduced for mobile */
            }

            .register-title {
                font-size: 2.4rem;
            }

            .register-form input {
                font-size: 1rem;
                padding: 0.8rem;
            }

            .register-button {
                font-size: 1.2rem;
                padding: 0.8rem 1.5rem;
            }

            .g-recaptcha {
                transform: scale(0.85);
                transform-origin: center;
            }

            .login-link-container {
                font-size: 1rem;
                margin-top: 1rem;
            }

            .register-form .error, .register-form .success {
                font-size: 1rem;
            }
        }

        @media (max-width: 576px) {
            .register-title {
                font-size: 2rem;
            }

            .register-form input {
                font-size: 0.95rem;
                padding: 0.7rem;
            }

            .register-button {
                font-size: 1.1rem;
                padding: 0.7rem 1.2rem;
            }

            .g-recaptcha {
                transform: scale(0.77);
            }

            .login-link-container {
                font-size: 0.95rem;
            }

            .register-form .error, .register-form .success {
                font-size: 0.95rem;
            }
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
                    <p class="login-link-container"><a href="/sahtout/login"><?php echo translate('login_link_text', 'Click here to login'); ?></a></p>
                </div>
            <?php endif; ?>

            <form class="register-form" method="POST" action="">
                <input type="text" name="username" placeholder="<?php echo translate('username_placeholder', 'Username'); ?>" required
                    value="<?php echo htmlspecialchars($username); ?>">
                <input type="email" name="email" placeholder="<?php echo translate('email_placeholder', 'Email'); ?>" required minlength="3" maxlength="36">
                <input type="password" name="password" placeholder="<?php echo translate('password_placeholder', 'Password'); ?>" required minlength="6" maxlength="32">
                <input type="password" name="confirm_password" placeholder="<?php echo translate('password_confirm_placeholder', 'Confirm Password'); ?>" required minlength="6" maxlength="32">
                <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                <button type="submit" class="register-button"><?php echo translate('register_button', 'Register'); ?></button>
            </form>

            <p class="login-link-container"><?php echo translate('login_link_text_alt', 'Already have an account? <a href="/sahtout/login">Login</a>'); ?></p>
        </section>
    </main>

    <?php include_once '../includes/footer.php'; ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</body>
</html>