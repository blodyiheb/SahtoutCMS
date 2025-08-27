<?php
define('ALLOWED_ACCESS', true);
require_once '../includes/session.php';
require_once '../includes/config.cap.php';
require_once '../includes/config.mail.php';
require_once '../languages/language.php'; // Add for translate()
$page_class = 'forgot_password'; // Use underscore for URL consistency
require_once '../includes/header.php';

// Redirect to account if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: /sahtout/account");
    exit();
}

$errors = [];
$success = '';
$username_or_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_or_email = trim($_POST['username_or_email'] ?? '');

    // Basic field validation
    if (empty($username_or_email)) {
        $errors[] = translate('error_username_or_email_required', 'Username or email is required');
    }

    // Google reCAPTCHA validation
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    $verify = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . RECAPTCHA_SECRET_KEY . '&response=' . $recaptchaResponse);
    $responseData = json_decode($verify);
    if (!$responseData->success) {
        $errors[] = translate('error_recaptcha_failed', 'reCAPTCHA verification failed.');
    }

    if (empty($errors)) {
        // Check if username or email exists in account table only
        $email = null;
        $username = null;
        $input_upper = strtoupper($username_or_email);

        // Check account table (case-sensitive username, case-insensitive email)
        $stmt = $auth_db->prepare("SELECT username, email FROM account WHERE username = ? OR LOWER(email) = LOWER(?)");
        $stmt->bind_param('ss', $input_upper, $username_or_email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $username = $row['username'];
            $email = $row['email'];
        }
        $stmt->close();

        if ($email && $username) {
            // Delete existing reset tokens for this email
            $stmt_delete = $site_db->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt_delete->bind_param('s', $email);
            $stmt_delete->execute();
            $stmt_delete->close();

            // Generate new reset token
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Store token in password_resets table
            $stmt_insert = $site_db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt_insert->bind_param('sss', $email, $token, $expires_at);
            if ($stmt_insert->execute()) {
                // Send reset email
                sendResetEmail($username, $email, $token);
            } else {
                $errors[] = translate('error_token_store_failed', 'Failed to store reset token.');
            }
            $stmt_insert->close();
        }

        // Always show success message to avoid leaking account existence
        $success = "If the provided username or email exists, a password reset link has been sent.";
        $username_or_email = '';
    }
}

function sendResetEmail($username, $email, $token) {
    global $errors;
    // Determine protocol dynamically
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    try {
        $mail = getMailer();
        $mail->addAddress($email, $username);
        $mail->AddEmbeddedImage('logo.png', 'logo_cid');
        $mail->Subject = translate('email_subject', 'Password Reset Request');
        $reset_link = $protocol . $_SERVER['HTTP_HOST'] . "/sahtout/reset_password?token=$token";
        $mail->Body = "<h2>" . str_replace('{username}', htmlspecialchars($username), translate('email_greeting', 'Welcome, {username}!')) . "</h2>
            <img src='cid:logo_cid' alt='Sahtout logo'>
            <p>" . translate('email_request', 'You requested a password reset. Please click the button below to reset your password:') . "</p>
            <p><a href='$reset_link' style='background-color:#ffd700;color:#000;padding:10px 20px;text-decoration:none;border-radius:4px;display:inline-block;'>" . translate('email_button', 'Reset Password') . "</a></p>
            <p>" . translate('email_expiry', 'This link will expire in 1 hour. If you didn\'t request this, please ignore this email.') . "</p>";
        if (!$mail->send()) {
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
    <meta name="description" content="<?php echo translate('meta_description', 'Request a password reset link for your World of Warcraft server account.'); ?>">
    <title><?php echo translate('page_title', 'Forgot Password'); ?></title>
    <link rel="stylesheet" href="../assets/css/forgot-password.css">
</head>
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
                    <input type="text" name="username_or_email" placeholder="<?php echo translate('username_or_email_placeholder', 'Username or Email'); ?>" required>
                    <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                    <button type="submit"><?php echo translate('send_button', 'Send Reset Link'); ?></button>
                    <div class="login-link">
                        <?php echo translate('login_link', 'Remembered your password?'); ?> <a href="/sahtout/login"><?php echo translate('login_link_text', 'Log in here'); ?></a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php include_once '../includes/footer.php'; ?>
</body>
</html>