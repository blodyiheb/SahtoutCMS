<?php
define('ALLOWED_ACCESS', true);
require_once '../includes/session.php';
require_once '../includes/config.mail.php';
require_once '../includes/config.cap.php'; // reCAPTCHA keys
require_once '../languages/language.php'; // Add for translate()
$page_class = 'resend_activation'; // Underscore for URL consistency
require_once '../includes/header.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$errors = [];
$success = '';
$test_username = isset($_GET['username']) && !empty($_GET['username']) ? strtoupper(trim($_GET['username'])) : '';
$test_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_username = strtoupper(trim($_POST['username'] ?? ''));
    $test_email = trim($_POST['email'] ?? '');

    // Basic validation
    if (empty($test_username)) $errors[] = translate('error_username_required', 'Username is required');
    if (empty($test_email)) $errors[] = translate('error_email_required', 'Email is required');
    elseif (!filter_var($test_email, FILTER_VALIDATE_EMAIL)) $errors[] = translate('error_email_invalid', 'Invalid email address');

    // Google reCAPTCHA validation
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    $verify = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . RECAPTCHA_SECRET_KEY . '&response=' . $recaptchaResponse);
    $responseData = json_decode($verify);
    if (!$responseData->success) {
        $errors[] = translate('error_recaptcha_failed', 'reCAPTCHA verification failed.');
    }

    if (empty($errors)) {
        $new_token = bin2hex(random_bytes(32));
        if (updateToken($site_db, $test_username, $test_email, $new_token)) {
            sendActivationEmail($test_username, $test_email, $new_token);
        }
    }
}

function updateToken($db, $username, $email, $new_token) {
    global $errors;
    $stmt = $db->prepare("UPDATE pending_accounts SET token = ?, created_at = NOW() WHERE username = ? AND email = ? AND activated = 0");
    if (!$stmt) {
        $errors[] = translate('error_database', 'Database error: ') . $db->error;
        return false;
    }
    $stmt->bind_param('sss', $new_token, $username, $email);
    if ($stmt->execute()) {
        if ($stmt->affected_rows === 0) {
            $errors[] = translate('error_no_account', 'No matching unactivated account found');
            return false;
        }
        return true;
    } else {
        $errors[] = translate('error_update_failed', 'Update failed: ') . $stmt->error;
        return false;
    }
}

function sendActivationEmail($username, $email, $token) {
    global $errors, $success;

    // Determine protocol dynamically
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";

    try {
        $mail = getMailer();
        $mail->addAddress($email, $username);
        $mail->AddEmbeddedImage('logo.png', 'logo_cid');
        $mail->Subject = translate('email_subject', '[RESEND] Activate Your Account');

        $activation_link = $protocol . $_SERVER['HTTP_HOST'] . "/sahtout/activate?token=$token";

        $mail->Body = "<h2>" . str_replace('{username}', htmlspecialchars($username), translate('email_greeting', 'Welcome, {username}!')) . "</h2>
            <img src='cid:logo_cid' alt='Sahtout logo'>
            <p>" . translate('email_thanks', 'Thank you for registering. Please click the button below to activate your account:') . "</p>
            <p><a href='$activation_link' style='background-color:#ffd700;color:#000;padding:10px 20px;text-decoration:none;border-radius:4px;display:inline-block;'>" . translate('email_button', 'Activate Account') . "</a></p>
            <p>" . translate('email_ignore', 'If you didn\'t request this, please ignore this email.') . "</p>";

        if ($mail->send()) {
            $success = "Activation email sent successfully to $email";
        } else {
            $errors[] = translate('error_email_failed', 'Failed to send email: ') . $mail->ErrorInfo;
        }
    } catch (Exception $e) {
        $errors[] = translate('error_email_failed', 'Email error: ') . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="description" content="<?php echo translate('meta_description', 'Resend the activation email for your World of Warcraft server account.'); ?>">
    <title><?php echo translate('page_title', 'Resend Activation Email'); ?></title>
    <style>
        body.resend_activation {
            color: #fff;
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: url('/sahtout/img/backgrounds/bg-resend.jpg') no-repeat center center fixed;
            background-size: cover;
            font-family: 'UnifrakturCook', 'Arial', sans-serif;
            position: relative;
        }
        html, body {
            width: 100%;
            overflow-x: hidden;
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .wrapper {
            width: 100%;
            padding: 2rem 1rem;
            display: flex;
            justify-content: center;
            flex: 1 0 auto;
        }
        .form-container {
            max-width: 500px;
            width: calc(100% - 2rem);
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid #ffd700;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(255, 215, 0, 0.5);
            padding: 2.5rem;
        }
        .form-section {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .form-section h2 {
            font-size: 2.8rem;
            font-family: 'UnifrakturCook', sans-serif;
            color: #ffd700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
            margin-bottom: 1.2rem;
            text-align: center;
        }
        .form-section form {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }
        .form-section input {
            width: 100%;
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
        .form-section input:focus {
            border-color: #ffe600;
            box-shadow: 0 0 5px rgba(255, 230, 0, 0.5);
        }
        .form-section input::placeholder {
            color: #ccc;
        }
        .form-section button {
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
        .form-section button:hover {
            background: #ffd700;
            color: #000;
            transform: scale(1.05);
        }
        .form-section .error {
            color: #ff0000;
            font-size: 1.1rem;
            font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;
            text-align: center;
            margin: 0.5rem 0 0;
        }
        .form-section .success {
            color: #00ff00;
            font-size: 1.1rem;
            font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;
            text-align: center;
            margin: 0.5rem 0 0;
        }
        .form-section .login-link {
            text-align: center;
            font-size: 1.1rem;
            font-family: 'UnifrakturCook', sans-serif;
            color: #fff;
            margin-top: 1.5rem;
        }
        .form-section .login-link a {
            color: #ffd700;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .form-section .login-link a:hover {
            color: #ffe600;
            text-decoration: underline;
        }
        footer {
            flex-shrink: 0;
            width: 100%;
            text-align: center;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.7);
            border-top: 2px solid #ffd700;
            color: #fff;
            font-family: 'Arial', sans-serif;
            font-size: 0.9rem;
        }
        @media (max-width: 767px) {
            .wrapper {
                padding: 1.5rem 0.5rem;
            }
            .form-container {
                max-width: 90%;
                padding: 1.5rem;
            }
            .form-section h2 {
                font-size: 2.2rem;
            }
            .form-section input {
                font-size: 1rem;
                padding: 0.8rem;
            }
            .form-section button {
                font-size: 1.1rem;
                padding: 0.8rem 1.5rem;
            }
            .form-section .login-link {
                font-size: 1rem;
                margin-top: 1rem;
            }
        }
    </style>
</head>
<body class="resend_activation">
    <div class="wrapper">
        <div class="form-container">
            <div class="form-section">
                <h2><?php echo translate('resend_title', 'Resend Activation Email'); ?></h2>
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
                    <input type="text" name="username" placeholder="<?php echo translate('username_placeholder', 'Username'); ?>" required value="<?php echo htmlspecialchars($test_username); ?>">
                    <input type="email" name="email" placeholder="<?php echo translate('email_placeholder', 'Email'); ?>" required value="<?php echo htmlspecialchars($test_email); ?>">
                    <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                    <button type="submit"><?php echo translate('resend_button', 'Resend Activation Email'); ?></button>
                    <div class="login-link">
                        <?php echo translate('login_link', 'Already activated?'); ?> <a href="/sahtout/login"><?php echo translate('login_link_text', 'Log in here'); ?></a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php include_once '../includes/footer.php'; ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</body>
</html>