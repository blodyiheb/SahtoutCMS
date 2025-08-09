<?php
require_once '../includes/session.php';
require_once '../includes/config.cap.php';
require_once '../includes/config.mail.php';
$page_class = 'forgot-password';
require_once '../includes/header.php';

// Redirect to account if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: /Sahtout/pages/account.php");
    exit();
}

$errors = [];
$success = '';
$username_or_email = isset($_GET['username']) && !empty($_GET['username']) ? strtoupper(trim($_GET['username'])) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_or_email = trim($_POST['username_or_email'] ?? '');

    // Basic field validation
    if (empty($username_or_email)) {
        $errors[] = "Username or email is required";
    }

    // Google reCAPTCHA validation
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    $verify = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . RECAPTCHA_SECRET_KEY . '&response=' . $recaptchaResponse);
    $responseData = json_decode($verify);
    if (!$responseData->success) {
        $errors[] = "reCAPTCHA verification failed.";
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
                $errors[] = "Failed to store reset token.";
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
        $mail->Subject = 'Password Reset Request';
        $reset_link = $protocol . $_SERVER['HTTP_HOST'] . "/sahtout/pages/reset_password.php?token=$token";
        $mail->Body = "<h2>Welcome, $username!</h2>
            <img src='cid:logo_cid' alt='Sahtout logo'>
            <p>You requested a password reset. Please click the button below to reset your password:</p>
            <p><a href='$reset_link' style='background-color:#ffd700;color:#000;padding:10px 20px;text-decoration:none;border-radius:4px;display:inline-block;'>Reset Password</a></p>
            <p>This link will expire in 1 hour. If you didn't request this, please ignore this email.</p>";
        if (!$mail->send()) {
            $errors[] = "Failed to send email: " . $mail->ErrorInfo;
        }
    } catch (Exception $e) {
        $errors[] = "Email error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Forgot Password</title>
    <link rel="stylesheet" href="../assets/css/forgot-password.css">
</head>
<body class="forgot-password">
    <div class="wrapper">
        <div class="form-container">
            <div class="form-section">
                <h2>Forgot Password</h2>
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
                    <input type="text" name="username_or_email" placeholder="Username or Email" required value="<?php echo htmlspecialchars($username_or_email); ?>">
                    <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                    <button type="submit">Send Reset Link</button>
                    <div class="login-link">
                        Remembered your password? <a href="/sahtout/pages/login.php">Log in here</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php include_once '../includes/footer.php'; ?>
</body>
</html>