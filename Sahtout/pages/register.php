<?php
require_once '../includes/session.php';
require_once '../includes/config.cap.php'; // reCAPTCHA keys
require_once '../includes/config.mail.php'; // PHPMailer configuration
require_once 'C:/xampp/htdocs/Sahtout/includes/srp6.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$page_class = 'register';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';

    // Verify reCAPTCHA
    if (empty($recaptcha_response)) {
        $errors[] = 'Please complete the CAPTCHA.';
    } else {
        $verify = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . RECAPTCHA_SECRET_KEY . '&response=' . $recaptcha_response);
        $captcha_result = json_decode($verify);
        if (!$captcha_result->success) {
            $errors[] = 'CAPTCHA verification failed.';
        }
    }

    // Validation
    if (strlen($username) < 3 || strlen($username) > 16) {
        $errors[] = "Username must be between 3 and 16 characters.";
    }
    if (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
        $errors[] = "Username can only contain letters and numbers.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Check for existing username and email in pending_accounts
    if (empty($errors)) {
        $upper_username = strtoupper($username);
        $stmt = $site_db->prepare("SELECT username, email FROM pending_accounts WHERE username = ? OR email = ?");
        $stmt->bind_param('ss', $upper_username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "An account with this username or email is already pending or registered. Please use a different username or email, or activate your existing account.";
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
            $errors[] = "Username already exists. Please choose a different username.";
        }
        $stmt->close();

        // Check if email exists
        $stmt = $auth_db->prepare("SELECT id FROM account WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Email already in use. Please choose a different email.";
        }
        $stmt->close();
    }

    // Proceed with pending account creation and email sending
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
        $activation_link = $protocol . $host . "/sahtout/pages/activate.php?token=$token";

        // Send activation email
        try {
            $mail = getMailer();
            $mail->addAddress($email, $username);
            $mail->AddEmbeddedImage('logo.png', 'logo_cid');
            $mail->Subject = 'Activate Your Account';
            $mail->Body = "
                <h2>Welcome, $username!</h2>
                <img src='cid:logo_cid' alt='Sahtout logo'>
                <p>Thank you for registering. Please click the button below to activate your account:</p>
                <p><a href='$activation_link' style='background-color:#6e4d15;color:white;padding:10px 20px;text-decoration:none;'>Activate Account</a></p>
                <p>If you did not register, please ignore this email.</p>
            ";

            if ($mail->send()) {
                $success = "Account created. Check your email to activate your account.";
            } else {
                $errors[] = "Failed to send activation email. Please contact support.";
            }
        } catch (Exception $e) {
            $errors[] = "Failed to send activation email: {$mail->ErrorInfo}";
        }
    } else {
        $errors[] = "Failed to store pending account.";
    }
} catch (mysqli_sql_exception $e) {
    $errors[] = "An account with this username or email is already pending or registered. Please use a different username or email, or activate your existing account.";
} finally {
    if ($stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
}

}
}

// Include header
$header_file = "../includes/header.php";
if (file_exists($header_file)) {
    include $header_file;
} else {
    die("Error: Header file not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Create an account to join our World of Warcraft server adventure!">
    <meta name="robots" content="index">
    <title>Create Account</title>
    <style>
        html, body {
            width: 100%;
            overflow-x: hidden;
            margin: 0;
        }

        body.register {
            background: url('/sahtout/img/backgrounds/bg-register.jpg') no-repeat center center fixed;
            background-size: cover;
            font-family: 'UnifrakturCook', 'Arial', sans-serif;
            color: #fff;
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0;
            box-sizing: border-box;
        }

        .register-container {
            max-width: 500px; /* Changed from 700px */
            width: calc(100% - 2rem);
            margin: 2rem auto;
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid #ffd700;
            border-radius: 8px;
            padding: 2.5rem;
            text-align: center;
            box-shadow: 0 0 15px rgba(255, 215, 0, 0.5);
        }

        .register-title {
            color: #ffd700;
            font-size: 2.8rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
            margin-bottom: 1.2rem;
        }

        .register-form {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }

        .register-form input {
            padding: 1rem;
            font-size: 1.1rem;
            font-family: 'Arial', sans-serif;
            background: #333;
            color: #fff;
            border: 1px solid #ffd700;
            border-radius: 4px;
            outline: none;
            transition: border-color 0.3s ease;
        }

        .register-form input:focus {
            border-color: #ffe600;
            box-shadow: 0 0 5px rgba(255, 230, 0, 0.5);
        }

        .register-form input::placeholder {
            color: #ccc;
        }

        .register-button {
            background: #333;
            color: #ffd700;
            border: 2px solid #ffd700;
            padding: 1rem 2rem;
            font-family: 'UnifrakturCook', sans-serif;
            font-size: 1.2rem;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .register-button:hover {
            background: #ffd700;
            color: #000;
            transform: scale(1.05);
        }

        .register-form p.error {
            color: #ff0000;
            font-family: 'Arial', sans-serif;
            font-size: 1.1rem;
            margin: 0.5rem 0 0;
        }

        .login-link-container {
            margin-top: 1.5rem;
            font-size: 1.1rem;
            font-family: 'UnifrakturCook', sans-serif;
        }

        .login-link-container a {
            color: #ffd700;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .login-link-container a:hover {
            color: #ffe600;
            text-decoration: underline;
        }

        footer {
            width: 100%;
            margin: 0;
            padding: 1rem 0;
            box-sizing: border-box;
        }

        @media (max-width: 768px) {
            html, body {
                width: 100%;
                overflow-x: hidden;
            }

            header {
                padding: 1rem 0;
            }

            main {
                padding: 0;
                margin-top: 100px;
            }

            .register-container {
                max-width: 100%; /* Ensure responsiveness on smaller screens */
                padding: 1.5rem;
            }

            .register-title {
                font-size: 2.2rem;
            }

            .register-form input {
                font-size: 1rem;
                padding: 0.8rem;
            }

            .register-button {
                font-size: 1.1rem;
                padding: 0.8rem 1.5rem;
            }

            .login-link-container {
                font-size: 1rem;
                margin-top: 1rem;
            }

            footer {
                padding: 1rem 0;
            }
        }
    </style>
</head>
<body class="register">
    <main>
        <section class="register-container">
            <h1 class="register-title">Create Your Account</h1>

            <?php if (!empty($errors)): ?>
                <div class="register-form">
                    <?php foreach ($errors as $error): ?>
                        <p class="error"><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($success): ?>
                <div class="register-form">
                    <p style="color:#00ff00;"><?php echo htmlspecialchars($success); ?></p>
                    <p><a href="/sahtout/pages/login.php" style="color:#ffd700;">Click here to login</a></p>
                </div>
            <?php endif; ?>

            <form class="register-form" method="POST" action="">
                <input type="text" name="username" placeholder="Username" required
                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                <input type="email" name="email" placeholder="Email" required minlength="3" maxlength="36"
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                <input type="password" name="password" placeholder="Password" required minlength="6" maxlength="32">
                <input type="password" name="confirm_password" placeholder="Confirm Password" required minlength="6" maxlength="32">
                <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                <button type="submit" class="register-button">Register</button>
            </form>

            <p class="login-link-container">Already have an account? <a href="/sahtout/pages/login.php">Login</a></p>
        </section>
    </main>

    <?php
    $footer_file = __DIR__ . "/../includes/footer.php";
    if (file_exists($footer_file)) {
        include $footer_file;
    } else {
        die("Error: Footer file not found.");
    }
    ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</body>
</html>