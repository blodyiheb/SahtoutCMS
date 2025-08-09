<?php
require_once '../includes/session.php';
require_once '../includes/config.cap.php';
require_once '../includes/srp6.php';
require_once '../includes/config.mail.php';
$page_class = 'reset-password';
require_once '../includes/header.php';

if (isset($_SESSION['user_id'])) {
    header("Location: /Sahtout/pages/account.php");
    exit();
}

$errors = [];
$success = '';
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$valid_token = false;
$email = '';
$username = '';

// Debug logging function
function debug_log($message) {
    $log_file = 'C:/xampp/htdocs/Sahtout/logs/reset_password.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

// Function to send confirmation email
function sendResetConfirmationEmail($username, $email) {
    global $errors;
    try {
        $mail = getMailer();
        $mail->addAddress($email, $username);
        $mail->AddEmbeddedImage('logo.png', 'logo_cid');
        $mail->Subject = '[Password Reset] Confirmation';
        $mail->Body = "<h2>Password Reset Confirmation</h2>
            <img src='cid:logo_cid' alt='Sahtout logo'>
            <p>Dear $username,</p>
            <p>Your password has been successfully reset.</p>
            <p>If you did not perform this action, please contact support immediately.</p>";
        if (!$mail->send()) {
            $errors[] = "Failed to send confirmation email: " . $mail->ErrorInfo;
            debug_log("Failed to send confirmation email: " . $mail->ErrorInfo);
        } else {
            debug_log("Confirmation email sent to: $email");
        }
    } catch (Exception $e) {
        $errors[] = "Email error: " . $e->getMessage();
        debug_log("Confirmation email error: " . $e->getMessage());
    }
}

if ($token) {
    // Validate token
    $stmt = $site_db->prepare("SELECT email, expires_at, used FROM password_resets WHERE token = ?");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $expires_at = strtotime($row['expires_at']);
        $current_time = time();
        if ($row['used'] == 0 && $expires_at > $current_time) {
            $email = $row['email'];
            debug_log("Token valid, email found: $email");

            // Check account table first
            $stmt2 = $auth_db->prepare("SELECT username FROM account WHERE LOWER(email) = LOWER(?)");
            $stmt2->bind_param('s', $email);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            if ($result2->num_rows > 0) {
                $username = $result2->fetch_assoc()['username'];
                $valid_token = true;
                debug_log("Username found in account: $username");
            } else {
                // Check pending_accounts table
                $stmt3 = $site_db->prepare("SELECT username FROM pending_accounts WHERE LOWER(email) = LOWER(?)");
                $stmt3->bind_param('s', $email);
                $stmt3->execute();
                $result3 = $stmt3->get_result();
                if ($result3->num_rows > 0) {
                    $errors[] = "Your account is not active yet. Please activate your account to reset your password.";
                    debug_log("Email found in pending_accounts, not active: $email");
                } else {
                    $errors[] = "Account does not exist.";
                    debug_log("No account found for email: $email");
                }
                $stmt3->close();
            }
            $stmt2->close();
        } else {
            $errors[] = "The reset link is invalid or has expired.";
            debug_log("Token invalid or expired for token: $token");
        }
    } else {
        $errors[] = "Invalid reset token.";
        debug_log("No token found: $token");
    }
    $stmt->close();
} else {
    $errors[] = "No reset token provided.";
    debug_log("No token provided in URL");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate password
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Google reCAPTCHA validation
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    $verify = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . RECAPTCHA_SECRET_KEY . '&response=' . $recaptchaResponse);
    $responseData = json_decode($verify);
    if (!$responseData->success) {
        $errors[] = "reCAPTCHA verification failed.";
    }

    if (empty($errors)) {
        // Generate new SRP-6a salt and verifier
        $salt = SRP6::GenerateSalt();
        $verifier = SRP6::calculateVerifier($username, $password, $salt);
        debug_log("Generated new salt and verifier for username: $username");

        // Update account table
        $stmt = $auth_db->prepare("UPDATE account SET salt = ?, verifier = ? WHERE email = ?");
        $stmt->bind_param('sss', $salt, $verifier, $email);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            // Delete token from password_resets
            $stmt2 = $site_db->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt2->bind_param('s', $token);
            $stmt2->execute();
            $stmt2->close();
            // Send confirmation email
            sendResetConfirmationEmail($username, $email);
            $success = "Your password has been successfully reset. You can now log in.";
            debug_log("Password reset successful for email: $email");
        } else {
            $errors[] = "Failed to update password.";
            debug_log("Password update failed for email: $email");
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Reset Password</title>
    <style>
        body.reset-password {
            color: #fff;
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        html, body {
            width: 100%;
            overflow-x: hidden;
            margin: 0;
        }
        .wrapper {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1rem;
            width: 100%;
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
            font-size: 0.9rem;
            text-align: center;
            margin: 0.5rem 0 0;
        }
        .form-section .success {
            color: #00ff00;
            font-size: 0.9rem;
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
                padding: 0;
                margin-top: 100px;
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
<body class="reset-password">
    <div class="wrapper">
        <div class="form-container">
            <div class="form-section">
                <h2>Reset Password</h2>
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
                <?php else: ?>
                    <?php if ($valid_token): ?>
                        <form method="POST">
                            <input type="password" name="password" placeholder="New Password" required>
                            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                            <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                            <button type="submit">Reset Password</button>
                            <div class="login-link">
                                <a href="/sahtout/pages/login.php">Back to Login</a>
                            </div>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php include_once '../includes/footer.php'; ?>
</body>
</html>