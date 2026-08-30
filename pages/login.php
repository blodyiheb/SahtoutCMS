<?php
define('ALLOWED_ACCESS', true);

require_once __DIR__ . '/../includes/paths.php';
require_once $project_root . 'includes/session.php';
require_once $project_root . 'languages/language.php';
require_once $project_root . 'includes/config.cap.php';
require_once $project_root . 'includes/srp6.php';

define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900);
define('ATTEMPT_WINDOW', 3600);

if (isset($_SESSION['user_id'])) {
    header("Location: {$base_path}account");
    exit();
}

$page_class = 'login';
$errors = [];
$username = '';
$show_resend_button = false;
$remaining_attempts = MAX_LOGIN_ATTEMPTS;

function getUserIP()
{
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return trim($_SERVER['HTTP_CF_CONNECTING_IP']);
    }

    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function getAttemptCount($site_db, $ip_address, $username)
{
    $upper_username = strtoupper($username);

    $stmt = $site_db->prepare(
        "SELECT attempts, last_attempt
         FROM failed_logins
         WHERE ip_address = ? AND username = ?
         LIMIT 1"
    );

    if (!$stmt) {
        error_log("Login: failed_logins SELECT prepare failed: " . $site_db->error);
        return 0;
    }

    $stmt->bind_param('ss', $ip_address, $upper_username);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $stmt->close();

    if (!$row) {
        return 0;
    }

    if ((int)$row['last_attempt'] < time() - ATTEMPT_WINDOW) {
        return 0;
    }

    return (int)$row['attempts'];
}

function checkBruteForce($site_db, $ip_address, $username)
{
    global $errors, $remaining_attempts;

    $upper_username = strtoupper($username);

    $stmt = $site_db->prepare(
        "SELECT attempts, last_attempt, block_until
         FROM failed_logins
         WHERE ip_address = ? AND username = ?
         LIMIT 1"
    );

    if (!$stmt) {
        error_log("Login: brute-force SELECT prepare failed: " . $site_db->error);
        return true;
    }

    $stmt->bind_param('ss', $ip_address, $upper_username);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $stmt->close();

    if (!$row) {
        $remaining_attempts = MAX_LOGIN_ATTEMPTS;
        return true;
    }

    $current_time = time();

    if (!empty($row['block_until']) && (int)$row['block_until'] <= $current_time) {
        $stmt = $site_db->prepare(
            "DELETE FROM failed_logins
             WHERE ip_address = ? AND username = ?"
        );

        if ($stmt) {
            $stmt->bind_param('ss', $ip_address, $upper_username);
            $stmt->execute();
            $stmt->close();
        }

        $remaining_attempts = MAX_LOGIN_ATTEMPTS;
        return true;
    }

    if (
        empty($row['block_until']) &&
        (int)$row['last_attempt'] < $current_time - ATTEMPT_WINDOW
    ) {
        $stmt = $site_db->prepare(
            "DELETE FROM failed_logins
             WHERE ip_address = ? AND username = ?"
        );

        if ($stmt) {
            $stmt->bind_param('ss', $ip_address, $upper_username);
            $stmt->execute();
            $stmt->close();
        }

        $remaining_attempts = MAX_LOGIN_ATTEMPTS;
        return true;
    }

    if (!empty($row['block_until']) && (int)$row['block_until'] > $current_time) {
        $remaining_time = ceil(
            ((int)$row['block_until'] - $current_time) / 60
        );

        $errors[] = translate(
    'error_too_many_attempts',
    'Too many login attempts (%d made). Please try again in %d minutes.',
    (int)$row['attempts'],
    $remaining_time
);

        $remaining_attempts = 0;
        return false;
    }

    $attempts = (int)$row['attempts'];
    $remaining_attempts = max(0, MAX_LOGIN_ATTEMPTS - $attempts);

    if ($attempts >= MAX_LOGIN_ATTEMPTS) {
        $block_until = $current_time + LOCKOUT_DURATION;

        $stmt = $site_db->prepare(
            "UPDATE failed_logins
             SET block_until = ?
             WHERE ip_address = ? AND username = ?"
        );

        if ($stmt) {
            $stmt->bind_param(
                'iss',
                $block_until,
                $ip_address,
                $upper_username
            );
            $stmt->execute();
            $stmt->close();
        }

        $errors[] = translate(
            'error_too_many_attempts',
            'Too many login attempts (%d made). Please try again in %d minutes.',
            $attempts,
            ceil(LOCKOUT_DURATION / 60)
        );

        $remaining_attempts = 0;
        return false;
    }

    return true;
}

function logFailedAttempt($site_db, $ip_address, $username)
{
    $upper_username = strtoupper($username);

    $stmt = $site_db->prepare(
        "SELECT attempts, last_attempt
         FROM failed_logins
         WHERE ip_address = ? AND username = ?
         LIMIT 1"
    );

    if (!$stmt) {
        error_log("Login: failed_logins lookup failed: " . $site_db->error);
        return 0;
    }

    $stmt->bind_param('ss', $ip_address, $upper_username);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $stmt->close();

    if ($row) {
        $last_attempt = (int)$row['last_attempt'];

        if ($last_attempt < time() - ATTEMPT_WINDOW) {
            $attempts = 1;

            $stmt = $site_db->prepare(
                "UPDATE failed_logins
                 SET attempts = ?, last_attempt = UNIX_TIMESTAMP(), block_until = NULL
                 WHERE ip_address = ? AND username = ?"
            );

            if ($stmt) {
                $stmt->bind_param(
                    'iss',
                    $attempts,
                    $ip_address,
                    $upper_username
                );
                $stmt->execute();
                $stmt->close();
            }

            return 1;
        }

        $attempts = (int)$row['attempts'] + 1;

        if ($attempts >= MAX_LOGIN_ATTEMPTS) {
            $block_until = time() + LOCKOUT_DURATION;

            $stmt = $site_db->prepare(
                "UPDATE failed_logins
                 SET attempts = ?, last_attempt = UNIX_TIMESTAMP(), block_until = ?
                 WHERE ip_address = ? AND username = ?"
            );

            if ($stmt) {
                $stmt->bind_param(
                    'iiss',
                    $attempts,
                    $block_until,
                    $ip_address,
                    $upper_username
                );
                $stmt->execute();
                $stmt->close();
            }

            return $attempts;
        }

        $stmt = $site_db->prepare(
            "UPDATE failed_logins
             SET attempts = attempts + 1, last_attempt = UNIX_TIMESTAMP()
             WHERE ip_address = ? AND username = ?"
        );

        if ($stmt) {
            $stmt->bind_param('ss', $ip_address, $upper_username);
            $stmt->execute();
            $stmt->close();
        }

        return $attempts;
    }

    $attempts = 1;

    $stmt = $site_db->prepare(
        "INSERT INTO failed_logins
         (ip_address, username, attempts, last_attempt, block_until)
         VALUES (?, ?, ?, UNIX_TIMESTAMP(), NULL)"
    );

    if ($stmt) {
        $stmt->bind_param(
            'ssi',
            $ip_address,
            $upper_username,
            $attempts
        );
        $stmt->execute();
        $stmt->close();
    }

    return 1;
}

function clearFailedAttempts($site_db, $ip_address, $username)
{
    $upper_username = strtoupper($username);

    $stmt = $site_db->prepare(
        "DELETE FROM failed_logins
         WHERE ip_address = ? AND username = ?"
    );

    if (!$stmt) {
        error_log("Login: failed_logins DELETE prepare failed: " . $site_db->error);
        return;
    }

    $stmt->bind_param('ss', $ip_address, $upper_username);
    $stmt->execute();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip_address = getUserIP();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username)) {
        $errors[] = translate(
            'error_username_required',
            'Username is required.'
        );
    }

    if (empty($password)) {
        $errors[] = translate(
            'error_password_required',
            'Password is required.'
        );
    }

    if (
        empty($errors) &&
        defined('RECAPTCHA_ENABLED') &&
        RECAPTCHA_ENABLED
    ) {
        $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';

        if (empty($recaptcha_response)) {
            $errors[] = translate(
                'error_recaptcha_failed',
                'reCAPTCHA verification failed.'
            );
        } else {
            $verify_url =
                'https://www.google.com/recaptcha/api/siteverify?' .
                'secret=' . urlencode(RECAPTCHA_SECRET_KEY) .
                '&response=' . urlencode($recaptcha_response);

            $verify = @file_get_contents($verify_url);

            if ($verify === false) {
                $errors[] = translate(
                    'error_recaptcha_failed',
                    'reCAPTCHA verification failed.'
                );
            } else {
                $captcha_result = json_decode($verify);

                if (
                    !$captcha_result ||
                    empty($captcha_result->success)
                ) {
                    $errors[] = translate(
                        'error_recaptcha_failed',
                        'reCAPTCHA verification failed.'
                    );
                }
            }
        }
    }

    if (empty($errors)) {
        if (!checkBruteForce($site_db, $ip_address, $username)) {
            // Login blocked.
        } else {
            $upper_username = strtoupper($username);

            $stmt = $site_db->prepare(
                "SELECT username
                 FROM pending_accounts
                 WHERE username = ? AND activated = 0
                 LIMIT 1"
            );

            if (!$stmt) {
                error_log(
                    "Login: pending_accounts query prepare failed: " .
                    $site_db->error
                );

                $errors[] = translate(
                    'error_database',
                    'Database error. Please try again later.'
                );
            } else {
                $stmt->bind_param('s', $upper_username);
                $stmt->execute();

                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $errors[] = translate(
                        'error_account_not_activated',
                        'Your account is not activated. Please check your email to activate your account.'
                    );

                    $show_resend_button = true;

                    // Do not count unactivated accounts as failed logins.
                    $remaining_attempts = MAX_LOGIN_ATTEMPTS;
                }

                $stmt->close();
            }

            if (empty($errors)) {
                if ($auth_db->connect_error) {
                    error_log(
                        "Login: Authentication database connection failed: " .
                        $auth_db->connect_error
                    );

                    $errors[] = translate(
                        'error_database',
                        'Database error. Please try again later.'
                    );
                } else {
                    $stmt = $auth_db->prepare(
                        "SELECT id, username, salt, verifier, email
                         FROM account
                         WHERE username = ?
                         LIMIT 1"
                    );

                    if (!$stmt) {
                        error_log(
                            "Login: Account query prepare failed: " .
                            $auth_db->error
                        );

                        $errors[] = translate(
                            'error_database',
                            'Database error. Please try again later.'
                        );
                    } else {
                        $stmt->bind_param('s', $upper_username);
                        $stmt->execute();

                        $result = $stmt->get_result();

                        if ($result->num_rows === 0) {
                            $errors[] = translate(
                                'error_invalid_credentials',
                                'Invalid username or password.'
                            );
                        } else {
                            $account = $result->fetch_assoc();

                            if (
                                SRP6::VerifyPassword(
                                    $username,
                                    $password,
                                    $account['salt'],
                                    $account['verifier']
                                )
                            ) {
                                session_regenerate_id(true);

                                $_SESSION['user_id'] = $account['id'];
                                $_SESSION['username'] = $account['username'];
                                $_SESSION['email'] = $account['email'];
                                $_SESSION['last_regeneration'] = time();

                                $update = $auth_db->prepare(
                                    "UPDATE account
                                     SET last_login = NOW()
                                     WHERE id = ?"
                                );

                                if ($update) {
                                    $update->bind_param('i', $account['id']);
                                    $update->execute();
                                    $update->close();
                                } else {
                                    error_log(
                                        "Login: Failed to prepare last_login update: " .
                                        $auth_db->error
                                    );
                                }

                                clearFailedAttempts(
                                    $site_db,
                                    $ip_address,
                                    $username
                                );

                                header("Location: {$base_path}account");
                                exit();
                            } else {
                                $attempts = logFailedAttempt(
                                    $site_db,
                                    $ip_address,
                                    $username
                                );

                                $remaining_attempts = max(
                                    0,
                                    MAX_LOGIN_ATTEMPTS - $attempts
                                );

                               if ($attempts >= MAX_LOGIN_ATTEMPTS) {
    $errors[] = translate(
        'error_too_many_attempts',
        'Too many login attempts (%d made). Please try again in %d minutes.',
        $attempts,
        ceil(LOCKOUT_DURATION / 60)
    );

    $remaining_attempts = 0;
} else {
    $errors[] = translate(
        'error_invalid_credentials',
        'Invalid username or password.'
    );
}
                            }
                        }

                        $stmt->close();
                    }

                    $auth_db->close();
                }
            }
        }
    }

    if (!empty($username)) {
        $current_attempts = getAttemptCount(
            $site_db,
            $ip_address,
            $username
        );

        $remaining_attempts = min(
            $remaining_attempts,
            MAX_LOGIN_ATTEMPTS - $current_attempts
        );

        $remaining_attempts = max(0, $remaining_attempts);
    }
}

include_once $project_root . 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="<?php echo htmlspecialchars(translate('meta_description', 'Log in to your account to join the adventure on our World of Warcraft server!')); ?>" />
    <title><?php echo $site_title_name . " " . translate('page_title', 'Login'); ?></title>

    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/tailwind.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>node_modules/@fortawesome/fontawesome-free/css/all.min.css">

    <style>
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

        .glass-container {
            background: rgba(5, 7, 11, 0.75);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(201,162,39,.22);
            box-shadow: 0 20px 40px -12px rgba(0,0,0,.8), inset 0 0 60px rgba(0,0,0,.25);
            position: relative;
            max-width: 450px;
            width: 100%;
        }

        .glass-container::before {
            content: '';
            position: absolute;
            inset: 5px;
            border: 1px solid rgba(201,162,39,.14);
            pointer-events: none;
        }

        .glass-container::after {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
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

        .wow-title {
            font-family: 'Cinzel', serif;
            font-weight: 900;
            background: linear-gradient(180deg,#fff7d6 0%,#f2cf5b 35%,#c9a227 62%,#8a6a14 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,.9));
            letter-spacing: .02em;
        }

        .input-login {
            background: rgba(0,0,0,.5) !important;
            border: 2px solid rgba(201,162,39,.3) !important;
            color: #fff !important;
            transition: all .3s ease;
        }

        .input-login:focus {
            border-color: #f2cf5b !important;
            box-shadow: 0 0 20px rgba(242,207,82,.15) !important;
            outline: none;
        }

        .input-login::placeholder {
            color: #6b7280 !important;
        }

        @media (max-width: 767px) {
            body {
                padding-top: 96px;
            }

            .glass-container {
                max-width: 100%;
                margin: 0 .5rem;
            }
        }
    </style>
</head>

<body>
<div class="login-content relative z-10 min-h-screen flex items-center justify-center px-4 py-8">
    <div class="container mx-auto max-w-7xl px-2 sm:px-4 flex items-center justify-center">
        <div class="glass-container p-6 md:p-10">

            <div class="text-center mb-4">
                <div class="w-20 h-20 mx-auto bg-[rgba(242,207,82,0.1)] border border-[rgba(201,162,39,0.3)] flex items-center justify-center">
                    <i class="fas fa-shield-halved text-4xl text-[#f2cf5b]"></i>
                </div>
            </div>

            <h1 class="wow-title text-3xl md:text-5xl font-bold text-center mb-6">
                <?php echo translate('login_title', 'Login'); ?>
            </h1>

            <?php if (!empty($errors)): ?>
                <div class="bg-red-900/40 border border-red-600/40 text-red-200 px-4 py-3 mb-4 text-center text-sm">
                    <?php foreach ($errors as $error): ?>
                        <p class="mb-1 last:mb-0">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </p>
                    <?php endforeach; ?>

                    <?php if ($show_resend_button): ?>
                        <div class="mt-3 pt-3 border-t border-red-500/20">
                            <p class="mb-2 text-red-100">
                                <?php echo translate('resend_activation_prompt', 'Your account needs activation.'); ?>
                            </p>

                            <a href="<?php echo htmlspecialchars($base_path . 'resend_activation?username=' . urlencode($username)); ?>"
                               class="inline-block text-[#f2cf5b] font-bold hover:text-yellow-300 hover:underline">
                                <i class="fas fa-envelope mr-1"></i>
                                <?php echo translate('resend_activation_link', 'Resend Activation Code'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($remaining_attempts < MAX_LOGIN_ATTEMPTS && $remaining_attempts > 0): ?>
                <div class="bg-yellow-900/20 border border-yellow-600/30 text-yellow-200 px-4 py-2 mb-4 text-center text-sm">
                    <i class="fas fa-triangle-exclamation mr-2"></i>
                    <?php echo translate('remaining_attempts', 'You have %d login attempts remaining.', $remaining_attempts); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div class="relative">
                    <i class="fas fa-user text-[rgba(201,162,39,0.4)] absolute top-3.5 left-3"></i>
                    <input
                        type="text"
                        name="username"
                        maxlength="17"
                        placeholder="<?php echo translate('username_placeholder', 'Username'); ?>"
                        required
                        autocomplete="username"
                        value="<?php echo htmlspecialchars($username); ?>"
                        class="input-login w-full pl-10 pr-4 py-3 text-base"
                    >
                </div>

                <div class="relative">
                    <i class="fas fa-lock text-[rgba(201,162,39,0.4)] absolute top-3.5 left-3"></i>
                    <input
                        type="password"
                        name="password"
                        maxlength="16"
                        placeholder="<?php echo translate('password_placeholder', 'Password'); ?>"
                        required
                        autocomplete="current-password"
                        class="input-login w-full pl-10 pr-4 py-3 text-base"
                    >
                </div>

                <?php if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED): ?>
                    <div class="flex justify-center py-2">
                        <div
                            class="g-recaptcha"
                            data-sitekey="<?php echo htmlspecialchars(RECAPTCHA_SITE_KEY); ?>">
                        </div>
                    </div>
                <?php endif; ?>

                <button
                    type="submit"
                    class="w-full py-3 text-lg font-bold uppercase tracking-wider bg-gradient-to-r from-[#f2cf5b] to-[#c9a227] hover:from-[#f6d478] hover:to-[#d4b040] text-white border-2 border-[#f2cf5b] hover:border-[#f6d478] transition-all duration-300 hover:scale-[1.02] hover:shadow-[0_0_30px_rgba(242,207,82,0.3)]">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    <?php echo translate('login_button', 'Sign In'); ?>
                </button>

                <div class="text-center pt-2 text-gray-300 text-sm">
                    <?php echo translate('dont_have_account', "Don't have an account?"); ?>

                    <a
                        href="<?php echo htmlspecialchars($base_path . 'register'); ?>"
                        class="text-[#f2cf5b] font-bold hover:text-yellow-300 hover:underline transition-colors">
                        <?php echo translate('register_link_text_simple', 'Register'); ?>
                    </a>
                </div>

                <div class="text-center text-gray-400 text-sm">
                    <a
                        href="<?php echo htmlspecialchars($base_path . 'forgot_password'); ?>"
                        class="text-[#f2cf5b] hover:text-yellow-300 hover:underline transition-colors">
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