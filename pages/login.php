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
    
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/tailwind.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    
    <style>
        /* Page background - Only essential custom CSS */
        body {
            background: url('<?php echo $base_path; ?>img/backgrounds/bg-login.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            padding-top: 112px;
            margin: 0;
            position: relative;
        }
        
        body::before {
            display: none;
        }
        
        /* Glass effect container - Minimal custom CSS */
        .glass-container {
            background: rgba(5, 7, 11, 0.75);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(201,162,39,.22);
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
        
        /* Wow title gradient - Essential */
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
        
        /* Input fields - Essential */
        .input-login {
            background: rgba(0, 0, 0, 0.5) !important;
            border: 2px solid rgba(201,162,39,0.3) !important;
            color: #ffffff !important;
            transition: all 0.3s ease;
        }
        
        .input-login:focus {
            border-color: #f2cf5b !important;
            box-shadow: 0 0 20px rgba(242,207,82,0.15) !important;
            outline: none;
        }
        
        .input-login::placeholder {
            color: #6b7280 !important;
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
<div class="login-content relative z-10 min-h-screen flex items-center justify-center px-4 py-8">
    <div class="container mx-auto max-w-7xl px-2 sm:px-4 flex items-center justify-center">
        
        <!-- Main Container -->
        <div class="glass-container p-6 md:p-10">
            
            <!-- Icon -->
            <div class="text-center mb-4">
                <div class="w-20 h-20 mx-auto bg-[rgba(242,207,82,0.1)] border border-[rgba(201,162,39,0.3)] flex items-center justify-center">
                    <i class="fas fa-shield-halved text-4xl text-[#f2cf5b]"></i>
                </div>
            </div>
            
            <!-- Title -->
            <h1 class="wow-title text-3xl md:text-5xl font-bold text-center mb-6">
                <?php echo translate('login_title', 'Login'); ?>
            </h1>

            <!-- Errors -->
            <?php if (!empty($errors)): ?>
                <div class="bg-red-900/40 border border-red-600/40 text-red-200 px-4 py-3 mb-4 text-center text-sm">
                    <?php foreach ($errors as $error): ?>
                        <p><i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" class="space-y-4">
                <div class="relative">
                    <i class="fas fa-user text-[rgba(201,162,39,0.4)] absolute top-3.5 left-3"></i>
                    <input type="text" name="username" placeholder="<?php echo translate('username_placeholder', 'Username'); ?>" required value="<?php echo htmlspecialchars($username); ?>" class="input-login w-full pl-10 pr-4 py-3 text-base">
                </div>
                
                <div class="relative">
                    <i class="fas fa-lock text-[rgba(201,162,39,0.4)] absolute top-3.5 left-3"></i>
                    <input type="password" name="password" placeholder="<?php echo translate('password_placeholder', 'Password'); ?>" required class="input-login w-full pl-10 pr-4 py-3 text-base">
                </div>

                <?php if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED): ?>
                    <div class="flex justify-center py-2">
                        <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                    </div>
                <?php endif; ?>
                
                <!-- Login Button - Gold with white text -->
                <button type="submit" class="w-full py-3 text-lg font-bold uppercase tracking-wider bg-gradient-to-r from-[#f2cf5b] to-[#c9a227] hover:from-[#f6d478] hover:to-[#d4b040] text-white border-2 border-[#f2cf5b] hover:border-[#f6d478] transition-all duration-300 hover:scale-[1.02] hover:shadow-[0_0_30px_rgba(242,207,82,0.3)]">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    <?php echo translate('login_button', 'Sign In'); ?>
                </button>
                
                <!-- Register Link -->
                <div class="text-center pt-2 text-gray-300 text-sm">
                    <?php echo translate('dont_have_account', "Don't have an account?"); ?>
                    <a href="<?php echo htmlspecialchars($base_path . 'register'); ?>" class="text-[#f2cf5b] font-bold hover:text-yellow-300 hover:underline transition-colors">
                        <?php echo translate('register_link_text_simple', 'Register'); ?>
                    </a>
                </div>
                
                <!-- Forgot Password -->
                <div class="text-center text-gray-400 text-sm">
                    <a href="<?php echo htmlspecialchars($base_path . 'forgot_password'); ?>" class="text-[#f2cf5b] hover:text-yellow-300 hover:underline transition-colors">
                        <i class="fas fa-key mr-1"></i>
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