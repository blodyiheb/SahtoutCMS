<?php
define('ALLOWED_ACCESS', true);
require_once '../includes/session.php'; // Includes config.php for DB
require_once '../includes/config.mail.php'; // Email config
require_once '../languages/language.php'; // Translations

$page_class = 'activate_account'; // For CSS body class

$errors = [];
$success = '';
$token = $_GET['token'] ?? '';

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
                            // Send confirmation email
                            sendActivationConfirmationEmail($account['username'], $account['email']);
                            $success = translate('success_account_activated', 'Your account has been activated! You will be redirected to the login page shortly.');
                            header("Refresh: 3; url=/sahtout/login");
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

// ==========================
// Function to send confirmation email
// ==========================
function sendActivationConfirmationEmail($username, $email) {
    global $errors;
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    try {
        $mail = getMailer();
        $mail->addAddress($email, $username);
        $mail->AddEmbeddedImage('logo.png', 'logo_cid');
        $mail->Subject = translate('email_subject', 'Account Activation Confirmation');
        $login_link = $protocol . $_SERVER['HTTP_HOST'] . "/sahtout/login";
        $mail->Body = "<h2>" . str_replace('{username}', htmlspecialchars($username), translate('email_greeting', 'Welcome, {username}!')) . "</h2>
            <img src='cid:logo_cid' alt='Sahtout logo'>
            <p>" . translate('email_success', 'Your account has been successfully activated.') . "</p>
            <p>" . translate('email_login', 'You can now log in to start your adventure by clicking the button below:') . "</p>
            <p><a href='$login_link' style='background-color:#ffd700;color:#000;padding:10px 20px;text-decoration:none;border-radius:4px;display:inline-block;'>" . translate('email_button', 'Log In') . "</a></p>
            <p>" . translate('email_contact_support', 'If you did not activate this account, please contact support immediately.') . "</p>";
        if (!$mail->send()) {
            $errors[] = translate('error_email_failed', 'Failed to send confirmation email: ') . $mail->ErrorInfo;
        }
    } catch (Exception $e) {
        $errors[] = translate('error_email_failed', 'Email error: ') . $e->getMessage();
    }
}

// ==========================
// Output (AFTER logic)
// ==========================
require_once '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo translate('meta_description', 'Activate your account to join our World of Warcraft server adventure!'); ?>">
    <meta name="robots" content="index">
    <title><?php echo translate('page_title', 'Activate Account'); ?></title>
    <style>
        body {
            background: url('/sahtout/img/backgrounds/bg-register.jpg') no-repeat center center fixed;
            background-size: cover;
            color: #fff;
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        main { flex: 1; display: flex; justify-content: center; align-items: center; }
        .register-container {
            max-width: 700px;
            width: calc(100% - 2rem);
            margin: 2rem auto;
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid #ffd700;
            border-radius: 8px;
            padding: 2.5rem;
            text-align: center;
            box-shadow: 0 0 15px rgba(255, 215, 0, 0.5);
        }
        .register-title { color: #ffd700; font-size: 2.8rem; margin-bottom: 1.2rem; }
        .register-form p.error { color: #ff0000; font-size: 1.2rem; }
        .register-form p.success { color: #00ff00; font-size: 1.2rem; }
        .login-link-container { margin-top: 1.5rem; font-size: 1.1rem; }
        .login-link-container a { color: #ffd700; text-decoration: none; }
        .login-link-container a:hover { color: #ffe600; text-decoration: underline; }
    </style>
</head>
<body class="activate_account">
    <main>
        <section class="register-container">
            <h1 class="register-title"><?php echo translate('activate_title', 'Activate Your Account'); ?></h1>
            <?php if (!empty($errors)): ?>
                <div class="register-form">
                    <?php foreach ($errors as $error): ?>
                        <p class="error"><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($success): ?>
                <div class="register-form">
                    <p class="success"><?php echo htmlspecialchars($success); ?></p>
                    <p class="login-link-container"><a href="/sahtout/login"><?php echo translate('login_link', 'Click here to login'); ?></a></p>
                </div>
            <?php endif; ?>
        </section>
    </main>
    <?php include_once '../includes/footer.php'; ?>
</body>
</html>
