<?php
define('ALLOWED_ACCESS', true);

// Include paths.php to access $project_root and $base_path
require_once __DIR__ . '/../includes/paths.php';

// Use $project_root for filesystem includes
require_once $project_root . 'includes/session.php';
require_once $project_root . 'languages/language.php';
require_once $project_root . 'includes/config.cap.php';
require_once $project_root . 'includes/srp6.php';

// Brute force prevention settings
define('MAX_LOGIN_ATTEMPTS', 5); // Maximum allowed attempts
define('LOCKOUT_DURATION', 900); // 15 minutes in seconds
define('ATTEMPT_WINDOW', 3600); // 1 hour window for attempt counting

// Redirect to account if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: {$base_path}account");
    exit();
}

$page_class = 'login';

$errors = [];
$username = '';
$show_resend_button = false;
$remaining_attempts = MAX_LOGIN_ATTEMPTS; // Default to max attempts

// Function to get client IP address
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

// Function to get current attempt count
function getAttemptCount($site_db, $ip_address, $username) {
    $stmt = $site_db->prepare("SELECT attempts, last_attempt 
        FROM failed_logins 
        WHERE ip_address = ? AND username = ?");
    $upper_username = strtoupper($username);
    $stmt->bind_param('ss', $ip_address, $upper_username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if ($row && (int)$row['last_attempt'] >= time() - ATTEMPT_WINDOW) {
        return $row['attempts'];
    }
    return 0;
}

// Function to check and update login attempts
function checkBruteForce($site_db, $ip_address, $username) {
    global $errors, $remaining_attempts;
    
    // Check if IP and username combo exists in failed_logins
    $stmt = $site_db->prepare("SELECT attempts, last_attempt, block_until 
        FROM failed_logins 
        WHERE ip_address = ? AND username = ?");
    $upper_username = strtoupper($username);
    $stmt->bind_param('ss', $ip_address, $upper_username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    // Delete expired block records
    if ($row && $row['block_until'] && (int)$row['block_until'] <= time()) {
        $stmt = $site_db->prepare("DELETE FROM failed_logins 
            WHERE ip_address = ? AND username = ? AND block_until <= ?");
        $current_time = time();
        $stmt->bind_param('ssi', $ip_address, $upper_username, $current_time);
        $stmt->execute();
        $stmt->close();
        $row = null; // Reset row as it no longer exists
    }
    
    // Reset attempts if outside the attempt window
    if ($row && (int)$row['last_attempt'] < time() - ATTEMPT_WINDOW) {
        $stmt = $site_db->prepare("UPDATE failed_logins 
            SET attempts = 0, block_until = NULL 
            WHERE ip_address = ? AND username = ?");
        $stmt->bind_param('ss', $ip_address, $upper_username);
        $stmt->execute();
        $stmt->close();
        $row['attempts'] = 0;
        $row['block_until'] = null;
    }
    
    // Update remaining attempts
    $remaining_attempts = MAX_LOGIN_ATTEMPTS - ($row['attempts'] ?? 0);
    
    // Check if currently blocked
    if ($row && $row['block_until'] && (int)$row['block_until'] > time()) {
        $remaining_time = ceil(((int)$row['block_until'] - time()) / 60);
        $errors[] = translate('error_too_many_attempts', 'Too many login attempts (%d made). Please try again in %d minutes.', $row['attempts'], $remaining_time);
        return false;
    }
    
    // Check if max attempts reached
    if ($row && $row['attempts'] >= MAX_LOGIN_ATTEMPTS) {
        $block_until = time() + LOCKOUT_DURATION;
        $stmt = $site_db->prepare("UPDATE failed_logins 
            SET block_until = ? 
            WHERE ip_address = ? AND username = ?");
        $stmt->bind_param('iss', $block_until, $ip_address, $upper_username);
        $stmt->execute();
        $stmt->close();
        
        $remaining_time = ceil(LOCKOUT_DURATION / 60);
        $errors[] = translate('error_too_many_attempts', 'Too many login attempts (%d made). Please try again in %d minutes.', $row['attempts'], $remaining_time);
        return false;
    }
    
    return true;
}

// Function to log failed login attempt
function logFailedAttempt($site_db, $ip_address, $username) {
    $upper_username = strtoupper($username);
    // Check if IP and username combo exists
    $stmt = $site_db->prepare("SELECT id FROM failed_logins WHERE ip_address = ? AND username = ?");
    $stmt->bind_param('ss', $ip_address, $upper_username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing record
        $stmt = $site_db->prepare("UPDATE failed_logins 
            SET attempts = attempts + 1, last_attempt = UNIX_TIMESTAMP() 
            WHERE ip_address = ? AND username = ?");
        $stmt->bind_param('ss', $ip_address, $upper_username);
        $stmt->execute();
    } else {
        // Insert new record
        $stmt = $site_db->prepare("INSERT INTO failed_logins (ip_address, username, attempts, last_attempt) 
            VALUES (?, ?, 1, UNIX_TIMESTAMP())");
        $stmt->bind_param('ss', $ip_address, $upper_username);
        $stmt->execute();
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip_address = getUserIP();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Check for brute force attempts
    if (!checkBruteForce($site_db, $ip_address, $username)) {
        // Skip further processing if locked out
    } else {
        // Basic field validation
        if (empty($username)) {
            $errors[] = translate('error_username_required', 'Username is required');
        }
        if (empty($password)) {
            $errors[] = translate('error_password_required', 'Password is required');
        }

        // Google reCAPTCHA validation (always required)
        if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED) {
            $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
            if (empty($recaptchaResponse)) {
                $errors[] = translate('error_recaptcha_failed', 'reCAPTCHA verification failed.');
                // Do not log failed attempt here as account existence is not yet verified
            } else {
                $verify = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . RECAPTCHA_SECRET_KEY . '&response=' . $recaptchaResponse);
                $responseData = json_decode($verify);
                if (!$responseData->success) {
                    $errors[] = translate('error_recaptcha_failed', 'reCAPTCHA verification failed.');
                    // Do not log failed attempt here as account existence is not yet verified
                }
            }
        }

        if (empty($errors)) {
            // Check if account is in pending_accounts and not activated
            $stmt = $site_db->prepare("SELECT username FROM pending_accounts WHERE username = ? AND activated = 0");
            $upper_username = strtoupper($username);
            $stmt->bind_param('s', $upper_username);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $errors[] = translate('error_account_not_activated', 'Your account is not activated. Please check your email to activate your account.');
                $show_resend_button = true;
                logFailedAttempt($site_db, $ip_address, $username);
            }
            $stmt->close();

            // Proceed with login if no errors
            if (empty($errors)) {
                if ($auth_db->connect_error) {
                    die("Connection failed: " . $auth_db->connect_error);
                }

                $stmt = $auth_db->prepare("SELECT id, username, salt, verifier FROM account WHERE username = ?");
                $stmt->bind_param('s', $upper_username);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 0) {
                    $errors[] = translate('error_invalid_credentials', 'Invalid username or password');
                    // Do not log failed attempt for non-existent account
                } else {
                    $account = $result->fetch_assoc();

                    if (SRP6::VerifyPassword($username, $password, $account['salt'], $account['verifier'])) {
                        $_SESSION['user_id'] = $account['id'];
                        $_SESSION['username'] = $account['username'];

                        $update = $auth_db->prepare("UPDATE account SET last_login = NOW() WHERE id = ?");
                        $update->bind_param('i', $account['id']);
                        $update->execute();
                        $update->close();

                        // Clear failed attempts on successful login
                        $stmt = $site_db->prepare("DELETE FROM failed_logins WHERE ip_address = ? AND username = ?");
                        $stmt->bind_param('ss', $ip_address, $upper_username);
                        $stmt->execute();
                        $stmt->close();

                        header("Location: {$base_path}account");
                        exit();
                    } else {
                        $errors[] = translate('error_invalid_credentials', 'Invalid username or password');
                        logFailedAttempt($site_db, $ip_address, $username);
                    }
                }

                $stmt->close();
                $auth_db->close();
            }
        }
    }
    // Update remaining attempts after processing
    $remaining_attempts = MAX_LOGIN_ATTEMPTS - getAttemptCount($site_db, $ip_address, $username);
}

// Get remaining attempts for display (even on GET request)
if (!empty($username)) {
    $remaining_attempts = MAX_LOGIN_ATTEMPTS - getAttemptCount($site_db, getUserIP(), $username);
}

// Include header after processing form
include_once $project_root . 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="description" content="<?php echo translate('meta_description', 'Log in to your account to join our World of Warcraft server adventure!'); ?>">
    <title><?php echo $site_title_name ." ". translate('page_title', 'Login'); ?></title>
    <style>
        :root{
            --bg-login:url('<?php echo $base_path; ?>img/backgrounds/bg-login.jpg');
            --hover-wow-gif: url('<?php echo $base_path; ?>img/hover_wow.gif');
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="form-container">
        <div class="form-section">
            <h2><?php echo translate('login_title', 'Login'); ?></h2>

            <?php if (!empty($errors)): ?>
                <div class="error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                    <?php if ($show_resend_button): ?>
                        <div class="resend-link">
                            <p><?php echo translate('resend_activation_prompt', 'CLICK HERE:'); ?></p>
                            <a href="<?php echo $base_path; ?>resend_activation?username=<?php echo htmlspecialchars($username); ?>">
                                <?php echo translate('resend_activation_link', 'Resend Activation Code'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($remaining_attempts < MAX_LOGIN_ATTEMPTS && $remaining_attempts > 0): ?>
                <div class="attempts-info">
                    <p><?php echo translate('remaining_attempts', 'You have %d login attempts remaining.', $remaining_attempts); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="text" name="username" placeholder="<?php echo translate('username_placeholder', 'Username'); ?>" required value="<?php echo htmlspecialchars($username); ?>">
                <br>
                <input type="password" name="password" placeholder="<?php echo translate('password_placeholder', 'Password'); ?>" required>
                <?php if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED): ?>
                    <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                <?php endif; ?>
                <button type="submit"><?php echo translate('login_button', 'Sign In'); ?></button>
                <div class="register-link">
                    <?php echo sprintf(translate('register_link_text', 'Don\'t have an account? <a href="%s">Register now</a>'), htmlspecialchars($base_path . 'register')); ?>
                </div>
                <div class="forgot-password-link">
                    <?php echo sprintf(translate('forgot_password_link_text', 'Forgot your password? <a href="%s">Reset it here</a>'), htmlspecialchars($base_path . 'forgot_password')); ?>
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