<?php
define('ALLOWED_ACCESS', true);

// Include paths.php using __DIR__ to access $project_root and $base_path
require_once __DIR__ . '/../includes/paths.php';

// Use $project_root for filesystem includes
require_once $project_root . 'includes/session.php';
require_once $project_root . 'languages/language.php';
require_once $project_root . 'includes/config.cap.php';

$errors = [];
$success = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Verify reCAPTCHA only if enabled
    if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED) {
        $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
        if (empty($recaptcha_response)) {
            $errors[] = translate('error_recaptcha_failed', 'reCAPTCHA verification failed.');
        } else {
            $verify = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . RECAPTCHA_SECRET_KEY . '&response=' . $recaptcha_response);
            $captcha_result = json_decode($verify);
            if (!$captcha_result->success) {
                $errors[] = translate('error_recaptcha_failed', 'reCAPTCHA verification failed.');
            }
        }
    }

    // Basic Input Validation
    if (empty($username)) {
        $errors[] = translate('error_username_required', 'Username is required.');
    }
    if (empty($password)) {
        $errors[] = translate('error_password_required', 'Password is required.');
    }

    // Process Login
    if (empty($errors)) {
        if ($auth_db->connect_error) {
            die("Database connection failed: " . $auth_db->connect_error);
        }

        $upper_username = strtoupper($username);
        $stmt = $auth_db->prepare("SELECT id, username, salt, verifier, email FROM account WHERE username = ?");
        
        if (!$stmt) {
            error_log("Login: Account query prepare failed: " . $auth_db->error);
            $errors[] = translate('error_database', 'Database error. Please try again later.');
        } else {
            $stmt->bind_param('s', $upper_username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($account = $result->fetch_assoc()) {
                // Verify password using SRP6
                require_once $project_root . 'includes/srp6.php';
                $calculated_verifier = SRP6::CalculateVerifier($username, $password, $account['salt']);

                if (hash_equals(strtoupper($account['verifier']), strtoupper($calculated_verifier))) {
                    // Password match - Set session vars
                    $_SESSION['user_id'] = $account['id'];
                    $_SESSION['username'] = $account['username'];
                    $_SESSION['email'] = $account['email'];

                    header("Location: " . $base_path . "account");
                    exit;
                } else {
                    $errors[] = translate('error_invalid_credentials', 'Invalid username or password.');
                }
            } else {
                $errors[] = translate('error_invalid_credentials', 'Invalid username or password.');
            }
            $stmt->close();
        }
    }
}

// Include header after processing form
include_once $project_root . 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="description" content="<?php echo translate('meta_description', 'Log in to your account to join the adventure on our World of Warcraft server!'); ?>">
    <title><?php echo $site_title_name ." ". translate('page_title', 'Login'); ?></title>
    <style>
        /* Page background - login background */
        body {
            background: url('<?php echo $base_path; ?>img/backgrounds/bg-login.jpg') no-repeat center center fixed;
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
        
        /* Form container - more transparent */
        .form-container {
            background: rgba(0, 0, 0, 0.35) !important;
            backdrop-filter: blur(1px);
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
    <div class="form-container relative z-10 w-[calc(100%-2rem)] max-w-120 rounded-xl border-[2px] border-[#f1c40f] p-8 shadow-[0_8px_24px_rgba(241,196,15,0.2),0_0_40px_rgba(0,0,0,0.5)] transition-transform duration-300 ease-in-out hover:-translate-y-1.25 hover:rotate-1 max-[767px]:mx-auto max-[767px]:my-6 max-[767px]:w-[calc(100%-1.5rem)] max-[767px]:max-w-full max-[767px]:p-6 max-[767px]:shadow-[0_6px_16px_rgba(241,196,15,0.15)] max-[767px]:hover:-translate-y-0.75 max-[767px]:hover:rotate-[0.5deg]">
        <div class="form-section flex flex-col justify-center">
            <h2 class="mb-6 text-center font-['UnifrakturCook',sans-serif] text-5xl tracking-[1px] text-[#f1c40f] [text-shadow:3px_3px_6px_rgba(0,0,0,0.9)] max-[767px]:text-[2.4rem] max-[576px]:text-[2rem]"><?php echo translate('login_title', 'Login'); ?></h2>

            <?php if (!empty($errors)): ?>
                <div class="error mt-[0.6rem] mb-0 text-center font-[Arial,sans-serif] text-[1.1rem] text-[#e74c3c] [text-shadow:1px_1px_2px_rgba(0,0,0,0.7)] max-[767px]:text-base max-[576px]:text-[0.95rem]">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="flex flex-col gap-[0.8rem]">
                <input type="text" name="username" placeholder="<?php echo translate('username_placeholder', 'Username'); ?>" required value="<?php echo htmlspecialchars($username); ?>" class="w-full rounded-md border-2 border-[rgba(241,196,15,0.4)] bg-[rgba(0,0,0,0.5)] p-[0.9rem] font-[Arial,sans-serif] text-[1.1rem] text-white outline-none transition-[border-color,box-shadow] duration-300 ease-in-out placeholder:text-base placeholder:text-[#aaa] focus:border-[#ffe600] focus:shadow-[0_0_8px_rgba(255,230,0,0.3)] max-[767px]:p-[0.8rem] max-[767px]:text-base max-[576px]:p-[0.7rem] max-[576px]:text-[0.95rem]">
                <input type="password" name="password" placeholder="<?php echo translate('password_placeholder', 'Password'); ?>" required class="w-full rounded-md border-2 border-[rgba(241,196,15,0.4)] bg-[rgba(0,0,0,0.5)] p-[0.9rem] font-[Arial,sans-serif] text-[1.1rem] text-white outline-none transition-[border-color,box-shadow] duration-300 ease-in-out placeholder:text-base placeholder:text-[#aaa] focus:border-[#ffe600] focus:shadow-[0_0_8px_rgba(255,230,0,0.3)] max-[767px]:p-[0.8rem] max-[767px]:text-base max-[576px]:p-[0.7rem] max-[576px]:text-[0.95rem]">

                <?php if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED): ?>
                    <div class="g-recaptcha mx-auto my-[1.2rem] flex justify-center max-[767px]:scale-[0.85] max-[576px]:scale-[0.77]" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                <?php endif; ?>
                
                <!-- LOGIN BUTTON - Red, Hover: Green-Blue -->
                <button type="submit" class="cursor-[var(--hover-wow-gif)_16_16,auto] rounded-md border-2 border-[#f1c40f] bg-gradient-to-r from-[#e74c3c] to-[#c0392b] px-[1.8rem] py-[0.9rem] text-[1.3rem] font-['Arial',sans-serif] font-bold tracking-[1px] text-white uppercase shadow-[0_4px_12px_rgba(231,76,60,0.3)] transition-all duration-300 ease-in-out hover:scale-105 hover:from-[#2ecc71] hover:to-[#3498db] hover:shadow-[0_6px_20px_rgba(46,204,113,0.4)] max-[767px]:px-6 max-[767px]:py-[0.8rem] max-[767px]:text-[1.2rem] max-[576px]:px-5 max-[576px]:py-[0.7rem] max-[576px]:text-[1.1rem]">
                    <?php echo translate('login_button', 'Sign In'); ?>
                </button>
                
                <!-- REGISTER TEXT - "Don't have an account? Register" -->
                <div class="register-link mt-[1rem] text-center font-['Arial',sans-serif] text-[1.05rem] text-gray-200 [text-shadow:1px_1px_2px_rgba(0,0,0,0.8)] max-[767px]:mt-3 max-[767px]:text-base">
                    <?php echo translate('dont_have_account', "Don't have an account?"); ?>
                    <a href="<?php echo htmlspecialchars($base_path . 'register'); ?>" class="font-bold text-[#f1c40f] transition-colors duration-200 hover:text-[#ffe600] hover:underline">
                        <?php echo translate('register_link_text_simple', 'Register'); ?>
                    </a>
                </div>
                
                <!-- FORGOT PASSWORD LINK - Under Register -->
                <div class="forgot-password-link mt-[0.5rem] text-center font-['Arial',sans-serif] text-[0.95rem] [text-shadow:1px_1px_2px_rgba(0,0,0,0.8)] max-[767px]:mt-2 max-[767px]:text-sm">
                    <a href="<?php echo htmlspecialchars($base_path . 'forgot_password'); ?>" class="text-[#f1c40f] transition-colors duration-200 hover:text-[#ffe600] hover:underline">
                        <?php echo translate('forgot_password_link', 'Forgot Password?'); ?>
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