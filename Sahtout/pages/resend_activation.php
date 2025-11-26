<?php
define('ALLOWED_ACCESS', true);

// Include paths.php using __DIR__ to access $project_root and $base_path
require_once __DIR__ . '/../includes/paths.php';

// Use $project_root for filesystem includes
require_once $project_root . 'includes/session.php';
require_once $project_root . 'includes/config.mail.php';
require_once $project_root . 'includes/config.cap.php'; // reCAPTCHA keys
require_once $project_root . 'languages/language.php'; // Add for translate()
$page_class = 'resend_activation'; // Underscore for URL consistency
require_once $project_root . 'includes/header.php';


if (isset($_SESSION['user_id'])) {
    header("Location: {$base_path}account");
    exit();
}

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
    if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED) {
        $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
        $verify = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . RECAPTCHA_SECRET_KEY . '&response=' . $recaptchaResponse);
        $responseData = json_decode($verify);
        if (!$responseData->success) {
            $errors[] = translate('error_recaptcha_failed', 'reCAPTCHA verification failed.');
        }
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
    global $errors, $success, $base_path;

    try {
        $mail = getMailer();
        $mail->addAddress($email, $username);
        $mail->AddEmbeddedImage('logo.png', 'logo_cid');
        $mail->Subject = translate('email_subject', '[RESEND] Activate Your Account');

        $activation_link = $base_path . "activate?token=$token";

        $mail->Body = "<h2>" . str_replace('{username}', htmlspecialchars($username), translate('email_greeting', 'Welcome, {username}!')) . "</h2>
            <img src='cid:logo_cid' alt='Sahtout logo'>
            <p>" . translate('email_thanks', 'Thank you for registering. Please click the button below to activate your account:') . "</p>
            <p><a href='$activation_link' style='background-color:#ffd700;color:#000;padding:10px 20px;text-decoration:none;border-radius:4px;display:inline-block;'>" . translate('email_button', 'Activate Account') . "</a></p>
            <p>" . translate('email_ignore', 'If you didn\'t request this, please ignore this email.') . "</p>";

        if ($mail->send()) {
            $success = translate('success_email_sent', 'Activation email sent successfully to %s', htmlspecialchars($email));
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
    <title><?php echo $site_title_name ." ". translate('page_title', 'Resend Activation Email'); ?></title>
    <style>
        :root{
            --bg-resend-act:url('<?php echo $base_path; ?>img/backgrounds/bg-register.jpg');
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
                    <?php if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED): ?>
                        <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                    <?php endif; ?>
                    <button type="submit"><?php echo translate('resend_button', 'Resend Activation Email'); ?></button>
                    <div class="login-link">
                        <?php echo translate('login_link', 'Already activated?'); ?> <?php echo sprintf(translate('login_link_text', '<a href="%s">Log in here</a>'), htmlspecialchars($base_path . 'login')); ?>
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