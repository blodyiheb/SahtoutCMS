<?php
define('ALLOWED_ACCESS', true);

// Include paths.php using __DIR__ to access $project_root and $base_path
require_once __DIR__ . '/../includes/paths.php';

// Use $project_root for filesystem includes
require_once $project_root . 'includes/session.php';
require_once $project_root . 'includes/config.cap.php';
require_once $project_root . 'includes/srp6.php';
require_once $project_root . 'includes/config.mail.php';
require_once $project_root . 'languages/language.php';
$page_class = 'reset_password';
require_once $project_root . 'includes/header.php';

if (isset($_SESSION['user_id'])) {
    header("Location: {$base_path}account");
    exit();
}

$errors = [];
$success = '';
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$valid_token = false;
$email = '';
$username = '';

// Generate nonce for form submission
if (!isset($_SESSION['reset_nonce'])) {
    $_SESSION['reset_nonce'] = bin2hex(random_bytes(16));
}
$nonce = $_SESSION['reset_nonce'];

// Function to send confirmation email
function sendResetConfirmationEmail($username, $email) {
    global $errors, $project_root;
    try {
        $mail = getMailer();
        $mail->addAddress($email, $username);
        $mail->AddEmbeddedImage('logo.png', 'logo_cid');
        $mail->Subject = translate('email_subject_confirmation', 'Password Reset Confirmation');
        $mail->Body = "<h2>" . str_replace('{username}', htmlspecialchars($username), translate('email_greeting', 'Welcome, {username}!')) . "</h2>
            <img src='cid:logo_cid' alt='Sahtout logo'>
            <p>" . translate('email_success', 'Your password has been successfully reset.') . "</p>
            <p>" . translate('email_contact_support', 'If you did not perform this action, please contact support immediately.') . "</p>";
        if (!$mail->send()) {
            $errors[] = translate('error_email_failed', 'Failed to send confirmation email: ') . $mail->ErrorInfo;
        }
    } catch (Exception $e) {
        $errors[] = translate('error_email_failed', 'Email error: ') . $e->getMessage();
    }
}

if ($token) {
    // Delete expired or used tokens
    $stmt_delete = $site_db->prepare("DELETE FROM password_resets WHERE expires_at < NOW() OR used = 1");
    $stmt_delete->execute();
    $stmt_delete->close();

    // Validate token
    $stmt = $site_db->prepare("SELECT email FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $email = $row['email'];

        // Check account table first
        $stmt2 = $auth_db->prepare("SELECT username FROM account WHERE LOWER(email) = LOWER(?)");
        $stmt2->bind_param('s', $email);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        if ($result2->num_rows > 0) {
            $username = $result2->fetch_assoc()['username'];
            $valid_token = true;
        } else {
            // Check pending_accounts table
            $stmt3 = $site_db->prepare("SELECT username FROM pending_accounts WHERE LOWER(email) = LOWER(?)");
            $stmt3->bind_param('s', $email);
            $stmt3->execute();
            $result3 = $stmt3->get_result();
            if ($result3->num_rows > 0) {
                $errors[] = translate('error_account_not_active', 'Your account is not active yet. Please activate your account to reset your password.');
            } else {
                $errors[] = translate('error_account_not_exist', 'Account does not exist.');
            }
            $stmt3->close();
        }
        $stmt2->close();
    } else {
        $errors[] = translate('error_token_invalid', 'The reset link is invalid or has expired.');
    }
    $stmt->close();
} else {
    $errors[] = translate('error_no_token', 'No reset token provided.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    // Validate nonce to prevent re-submission
    $submitted_nonce = $_POST['nonce'] ?? '';
    if ($submitted_nonce !== $_SESSION['reset_nonce']) {
        $errors[] = translate('error_invalid_nonce', 'Invalid or expired form submission. Please try again.');
    } else {
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validate password
        if (empty($password)) {
            $errors[] = translate('error_password_required', 'Password is required.');
        } elseif (strlen($password) < 8) {
            $errors[] = translate('error_password_short', 'Password must be at least 8 characters long.');
        } elseif ($password !== $confirm_password) {
            $errors[] = translate('error_password_mismatch', 'Passwords do not match.');
        }

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
            // Generate new SRP-6a salt and verifier
            $salt = SRP6::generateSalt();
            $verifier = SRP6::calculateVerifier($username, $password, $salt);

            // Update account table
            $stmt = $auth_db->prepare("UPDATE account SET salt = ?, verifier = ? WHERE email = ?");
            $stmt->bind_param('sss', $salt, $verifier, $email);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                // Delete token from password_resets
                $stmt2 = $site_db->prepare("DELETE FROM password_resets WHERE token = ?");
                $stmt2->bind_param('s', $token);
                $stmt2->execute();
                $stmt2->close();
                // Send confirmation email only if SMTP is enabled
                if (defined('SMTP_ENABLED') && SMTP_ENABLED) {
                    sendResetConfirmationEmail($username, $email);
                }
                $success = translate('success_password_reset', 'Your password has been successfully reset. You can now log in.');
                // Clear nonce and token to prevent re-submission
                unset($_SESSION['reset_nonce']);
                $token = '';
                $valid_token = false;
            } else {
                $errors[] = translate('error_password_update_failed', 'Failed to update password.');
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="description" content="<?php echo translate('meta_description', 'Reset your password for our World of Warcraft server.'); ?>">
    <title><?php echo $site_title_name ." ". translate('page_title', 'Reset Password'); ?></title>
    <style>
        /* Page background */
        body {
            background: url('<?php echo $base_path; ?>img/backgrounds/bg-reset-password.jpg') no-repeat center center fixed;
            background-size: cover;
            position: relative;
            min-height: 100vh;
            padding-top: 112px;
        }
        
        /* No overlay on the page - let the image show through */
        body::before {
            display: none;
        }
        
        /* Main content wrapper */
        .wrapper {
            position: relative;
            z-index: 1;
        }
        
        /* Form container - transparent like other pages */
        .form-container {
            background: rgba(0, 0, 0, 0.35) !important;
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            border: 2px solid rgba(241, 196, 15, 0.4);
        }
        
        /* Input fields - semi-transparent dark */
        .form-container input {
            background: rgba(0, 0, 0, 0.5) !important;
            border-color: rgba(241, 196, 15, 0.4);
        }
        
        .form-container input:focus {
            border-color: #ffe600 !important;
        }
        
        @media (max-width: 767px) {
            body {
                padding-top: 96px;
            }
        }
    </style>
</head>
<body>
<div class="wrapper relative flex min-h-screen w-full items-center justify-center overflow-x-hidden px-4 py-4 text-white max-[767px]:mt-0 max-[767px]:p-0">
    <div class="form-container relative z-10 w-[calc(100%-2rem)] max-w-[500px] rounded-xl border-[2px] border-[#f1c40f] p-8 shadow-[0_8px_24px_rgba(241,196,15,0.2),0_0_40px_rgba(0,0,0,0.5)] transition-transform duration-300 ease-in-out hover:-translate-y-1.25 hover:rotate-1 max-[767px]:mx-auto max-[767px]:my-6 max-[767px]:w-[calc(100%-1.5rem)] max-[767px]:max-w-full max-[767px]:p-6 max-[767px]:shadow-[0_6px_16px_rgba(241,196,15,0.15)] max-[767px]:hover:-translate-y-0.75 max-[767px]:hover:rotate-[0.5deg]">
        <div class="form-section flex flex-col justify-center">
            <h2 class="mb-6 text-center font-['UnifrakturCook',sans-serif] text-5xl tracking-[1px] text-[#f1c40f] [text-shadow:3px_3px_6px_rgba(0,0,0,0.9)] max-[767px]:text-[2.4rem] max-[576px]:text-[2rem]">
                <?php echo translate('reset_title', 'Reset Password'); ?>
            </h2>

            <?php if (!empty($errors)): ?>
                <div class="error mt-[0.6rem] mb-0 text-center font-[Arial,sans-serif] text-[1.1rem] text-[#e74c3c] [text-shadow:1px_1px_2px_rgba(0,0,0,0.7)] max-[767px]:text-base max-[576px]:text-[0.95rem]">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success mt-[0.6rem] mb-0 text-center font-[Arial,sans-serif] text-[1.1rem] text-[#2ecc71] [text-shadow:1px_1px_2px_rgba(0,0,0,0.7)] max-[767px]:text-base max-[576px]:text-[0.95rem]">
                    <p><?php echo htmlspecialchars($success); ?></p>
                    <p class="mt-4 text-center text-lg font-['UnifrakturCook',sans-serif] text-white max-[767px]:mt-4 max-[767px]:text-base max-[576px]:text-[0.95rem]">
                        <a href="<?php echo $base_path; ?>login" class="inline-block rounded-md bg-[#f1c40f] px-6 py-2 text-black font-bold no-underline transition-all duration-300 ease-in-out hover:bg-[#f39c12]">
                            <?php echo translate('login_link', 'Back to Login'); ?>
                        </a>
                    </p>
                </div>
                <script>
                    setTimeout(() => {
                        window.location.href = '<?php echo $base_path; ?>login';
                    }, 3000);
                </script>
            <?php else: ?>
                <?php if ($valid_token): ?>
                    <form method="POST" class="flex flex-col gap-[0.8rem]">
                        <input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce); ?>">
                        
                        <input type="password" name="password" placeholder="<?php echo translate('password_placeholder', 'New Password'); ?>" required class="w-full rounded-md border-2 border-[rgba(241,196,15,0.4)] bg-[rgba(0,0,0,0.5)] p-[0.9rem] font-[Arial,sans-serif] text-[1.1rem] text-white outline-none transition-[border-color,box-shadow] duration-300 ease-in-out placeholder:text-base placeholder:text-[#aaa] focus:border-[#ffe600] focus:shadow-[0_0_8px_rgba(255,230,0,0.3)] max-[767px]:p-[0.8rem] max-[767px]:text-base max-[576px]:p-[0.7rem] max-[576px]:text-[0.95rem]">
                        
                        <input type="password" name="confirm_password" placeholder="<?php echo translate('confirm_password_placeholder', 'Confirm Password'); ?>" required class="w-full rounded-md border-2 border-[rgba(241,196,15,0.4)] bg-[rgba(0,0,0,0.5)] p-[0.9rem] font-[Arial,sans-serif] text-[1.1rem] text-white outline-none transition-[border-color,box-shadow] duration-300 ease-in-out placeholder:text-base placeholder:text-[#aaa] focus:border-[#ffe600] focus:shadow-[0_0_8px_rgba(255,230,0,0.3)] max-[767px]:p-[0.8rem] max-[767px]:text-base max-[576px]:p-[0.7rem] max-[576px]:text-[0.95rem]">
                        
                        <?php if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED): ?>
                            <div class="g-recaptcha mx-auto my-[1.2rem] flex justify-center max-[767px]:scale-[0.85] max-[576px]:scale-[0.77]" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                        <?php endif; ?>
                        
                        <!-- RESET BUTTON - Red, Hover: Green-Blue -->
                        <button type="submit" class="cursor-[var(--hover-wow-gif)_16_16,auto] rounded-md border-2 border-[#f1c40f] bg-gradient-to-r from-[#e74c3c] to-[#c0392b] px-[1.8rem] py-[0.9rem] text-[1.3rem] font-['Arial',sans-serif] font-bold tracking-[1px] text-white uppercase shadow-[0_4px_12px_rgba(231,76,60,0.3)] transition-all duration-300 ease-in-out hover:scale-105 hover:from-[#2ecc71] hover:to-[#3498db] hover:shadow-[0_6px_20px_rgba(46,204,113,0.4)] max-[767px]:px-6 max-[767px]:py-[0.8rem] max-[767px]:text-[1.2rem] max-[576px]:px-5 max-[576px]:py-[0.7rem] max-[576px]:text-[1.1rem]">
                            <?php echo translate('reset_button', 'Reset Password'); ?>
                        </button>
                        
                        <!-- BACK TO LOGIN LINK - Yellow -->
                        <div class="login-link mt-[1rem] text-center font-['Arial',sans-serif] text-[1.05rem] text-gray-200 [text-shadow:1px_1px_2px_rgba(0,0,0,0.8)] max-[767px]:mt-3 max-[767px]:text-base">
                            <a href="<?php echo htmlspecialchars($base_path . 'login'); ?>" class="font-bold text-[#f1c40f] transition-colors duration-200 hover:text-[#ffe600] hover:underline">
                                <?php echo translate('login_link_text_simple', 'Back to Login'); ?>
                            </a>
                        </div>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>
<?php include_once $project_root . 'includes/footer.php'; ?>
</body>
</html>