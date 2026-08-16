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
    global $errors, $success, $site_url;

    try {
        $mail = getMailer();
        $mail->addAddress($email, $username);
        $mail->AddEmbeddedImage('logo.png', 'logo_cid');
        $mail->Subject = translate('email_subject', '[RESEND] Activate Your Account');

        $activation_link = $site_url . "activate?token=$token";

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
    
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/tailwind.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    
    <style>
        /* Page background - Show full background image */
        body {
            background: url('<?php echo $base_path; ?>img/backgrounds/bg-register.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            padding-top: 112px;
            margin: 0;
            position: relative;
        }
        
        body::before {
            display: none;
        }
        
        /* Main content wrapper */
        .resend-content {
            position: relative;
            z-index: 1;
        }
        
        /* Glass effect container - Same as login/register */
        .glass-container {
            background: rgba(5, 7, 11, 0.75);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(201,162,39,.22);
            border-radius: 0;
            padding: 2.5rem 2.5rem;
            box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.8), inset 0 0 60px rgba(0,0,0,.25);
            position: relative;
            max-width: 450px;
            width: 100%;
        }
        
        .glass-container::before {
            content: ''; position: absolute; inset: 5px;
            border: 1px solid rgba(201,162,39,.14);
            pointer-events: none;
        }
        
        .glass-container::after {
            content: ''; position: absolute; inset: 0; pointer-events: none;
            background:
                linear-gradient(#e8c552,#e8c552) left top / 18px 2px,
                linear-gradient(#e8c552,#e8c552) left top / 2px 18px,
                linear-gradient(#e8c552,#e8c552) right top / 18px 2px,
                linear-gradient(#e8c552,#e8c552) right top / 2px 18px,
                linear-gradient(#e8c552,#e8c552) left bottom / 18px 2px,
                linear-gradient(#e8c552,#e8c552) left bottom / 2px 18px,
                linear-gradient(#e8c552,#e8c552) right bottom / 18px 2px,
                linear-gradient(#e8c552,#e8c552) right bottom / 2px 18px;
            background-repeat: no-repeat;
        }
        
        /* Wow title gradient - Same as login */
        .wow-title {
            font-family: 'Cinzel', serif;
            font-weight: 900;
            background: linear-gradient(180deg, #fff7d6 0%, #f2cf5b 35%, #c9a227 62%, #8a6a14 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,.9));
            letter-spacing: .02em;
        }
        
        /* Input fields - Same as login */
        .input-resend {
            background: rgba(20, 30, 50, 0.7) !important;
            border: 2px solid rgba(201,162,39,0.4) !important;
            color: #ffffff !important;
            transition: all 0.3s ease;
        }
        
        .input-resend:focus {
            border-color: #f2cf5b !important;
            box-shadow: 0 0 25px rgba(242,207,82,0.2) !important;
            outline: none;
            background: rgba(20, 30, 50, 0.85) !important;
        }
        
        .input-resend::placeholder {
            color: #9ca3af !important;
        }
        
        /* Responsive - Essential */
        @media (max-width: 767px) {
            body {
                padding-top: 96px;
            }
            
            .glass-container {
                max-width: 100%;
                margin: 0 0.5rem;
            }
        }
    </style>
</head>
<body>
<div class="resend-content relative z-10 min-h-screen flex items-center justify-center px-4 py-8">
    <div class="container mx-auto max-w-7xl px-2 sm:px-4 flex items-center justify-center">
        
        <!-- Main Container -->
        <div class="glass-container p-6 md:p-10">
            
            <!-- Icon -->
            <div class="text-center mb-4">
                <div class="w-20 h-20 mx-auto bg-[rgba(242,207,82,0.15)] border-2 border-[rgba(201,162,39,0.4)] flex items-center justify-center">
                    <i class="fas fa-envelope text-4xl text-[#f2cf5b]"></i>
                </div>
            </div>
            
            <!-- Title -->
            <h1 class="wow-title text-3xl md:text-5xl font-bold text-center mb-6">
                <?php echo translate('resend_title', 'Resend Activation Email'); ?>
            </h1>

            <!-- Errors -->
            <?php if (!empty($errors)): ?>
                <div class="bg-red-900/40 border border-red-600/40 text-red-200 px-4 py-3 mb-4 text-center text-sm">
                    <?php foreach ($errors as $error): ?>
                        <p><i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Success -->
            <?php if ($success): ?>
                <div class="bg-green-900/40 border border-green-600/40 text-green-200 px-4 py-3 mb-4 text-center text-sm">
                    <p><i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?></p>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" class="space-y-4">
                <div class="relative">
                    <i class="fas fa-user text-[rgba(242,207,82,0.5)] absolute top-3.5 left-3 text-sm"></i>
                    <input type="text" name="username" placeholder="<?php echo translate('username_placeholder', 'Username'); ?>" required value="<?php echo htmlspecialchars($test_username); ?>" class="input-resend w-full pl-10 pr-4 py-3 text-base">
                </div>
                
                <div class="relative">
                    <i class="fas fa-envelope text-[rgba(242,207,82,0.5)] absolute top-3.5 left-3 text-sm"></i>
                    <input type="email" name="email" placeholder="<?php echo translate('email_placeholder', 'Email'); ?>" required value="<?php echo htmlspecialchars($test_email); ?>" class="input-resend w-full pl-10 pr-4 py-3 text-base">
                </div>

                <?php if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED): ?>
                    <div class="flex justify-center py-2">
                        <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                    </div>
                <?php endif; ?>
                
                <!-- Resend Button - Gold (same as login/register) -->
                <button type="submit" class="w-full py-3 text-lg font-bold uppercase tracking-wider bg-gradient-to-r from-[#f2cf5b] via-[#f6d478] to-[#c9a227] hover:from-[#f6d478] hover:via-[#f2cf5b] hover:to-[#d4b040] text-white border-2 border-[#f2cf5b] hover:border-[#f6d478] transition-all duration-300 hover:scale-[1.03] hover:shadow-[0_0_40px_rgba(242,207,82,0.4)] rounded-md">
                    <i class="fas fa-paper-plane mr-2"></i>
                    <?php echo translate('resend_button', 'Resend Activation Email'); ?>
                </button>
                
                <!-- Login Link -->
                <div class="text-center pt-2 text-gray-300 text-sm">
                    <?php echo translate('login_link', 'Already activated?'); ?>
                    <a href="<?php echo htmlspecialchars($base_path . 'login'); ?>" class="text-[#f2cf5b] font-bold hover:text-yellow-300 hover:underline transition-colors">
                        <?php echo translate('login_link_text_simple', 'Log in here'); ?>
                    </a>
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