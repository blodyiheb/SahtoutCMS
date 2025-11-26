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

// ==========================
// Function to send confirmation email
// ==========================
function sendActivationConfirmationEmail($username, $email) {
    global $errors, $base_path, $project_root;
    try {
        $mail = getMailer();
        $mail->addAddress($email, $username);
        $mail->AddEmbeddedImage('logo.png', 'logo_cid');
        $mail->Subject = translate('email_subject', 'Account Activation Confirmation');
        $login_link = $base_path . 'login';
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
    <title><?php echo $site_title_name ." ". translate('page_title', 'Activate Account'); ?></title>
    <style>
       :root{
            --bg-activate:url('<?php echo $base_path; ?>img/backgrounds/bg-register.jpg');
        }
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
                    <p class="login-link-container"><?php echo sprintf(translate('login_link', '<a href="%s">Click here to login</a>'), htmlspecialchars($base_path . 'login')); ?></p>
                </div>
            <?php endif; ?>
        </section>
    </main>
    <?php include_once $project_root . 'includes/footer.php'; ?>
</body>
</html>