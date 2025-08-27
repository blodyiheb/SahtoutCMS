<?php
define('ALLOWED_ACCESS', true);
require_once '../includes/session.php';
require_once '../languages/language.php'; // Add this to define translate()
require_once '../includes/config.cap.php'; // reCAPTCHA keys
require_once '../includes/srp6.php';

// Redirect to account if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: /Sahtout/account");
    exit();
}

$page_class = 'login';

$errors = [];
$username = '';
$show_resend_button = false;

// Check for error query parameter
if (isset($_GET['error']) && $_GET['error'] === 'invalid_session') {
    $errors[] = translate('error_invalid_session', 'Invalid session, please log in again.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic field validation
    if (empty($username)) {
        $errors[] = translate('error_username_required', 'Username is required');
    }
    if (empty($password)) {
        $errors[] = translate('error_password_required', 'Password is required');
    }

    // Google reCAPTCHA validation
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    $verify = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . RECAPTCHA_SECRET_KEY . '&response=' . $recaptchaResponse);
    $responseData = json_decode($verify);
    if (!$responseData->success) {
        $errors[] = translate('error_recaptcha_failed', 'reCAPTCHA verification failed.');
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
            $show_resend_button = true; // Show resend button
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
            } else {
                $account = $result->fetch_assoc();

                if (SRP6::VerifyPassword($username, $password, $account['salt'], $account['verifier'])) {
                    $_SESSION['user_id'] = $account['id'];
                    $_SESSION['username'] = $account['username'];

                    $update = $auth_db->prepare("UPDATE account SET last_login = NOW() WHERE id = ?");
                    $update->bind_param('i', $account['id']);
                    $update->execute();
                    $update->close();

                    header("Location: /Sahtout/account");
                    exit();
                } else {
                    $errors[] = translate('error_invalid_credentials', 'Invalid username or password');
                }
            }

            $stmt->close();
            $auth_db->close();
        }
    }
}

// Include header after processing form
include_once '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="description" content="<?php echo translate('meta_description', 'Log in to your account to join our World of Warcraft server adventure!'); ?>">
  <title><?php echo translate('page_title', 'Login'); ?></title>
  <style>
    * {
      box-sizing: border-box; /* Prevent padding/margins from causing overflow */
    }

    html, body {
      width: 100%;
      overflow-x: hidden;
      margin: 0;
      background: url('/sahtout/img/backgrounds/bg-login.jpg') no-repeat center center fixed;
      background-size: cover;
      font-family: 'UnifrakturCook', 'Arial', sans-serif;
      color: #fff;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      position: relative;
    }

    body::before {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5); /* Dark overlay with blur */
      backdrop-filter: blur(5px); /* Subtle blur effect */
      z-index: 1;
    }

    .wrapper {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 1rem;
      width: 100%;
      position: relative;
      z-index: 2;
    }

    .form-container {
      max-width: 480px;
      width: calc(100% - 2rem);
      background: #1a1a1a88; /* Darker WoW-style background */
      border: 3px solid #f1c40f; /* Vibrant gold border */
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(241, 196, 15, 0.4), 0 0 40px rgba(0, 0, 0, 0.8);
      padding: 2rem;
      animation: pulse 3s infinite ease-in-out;
      transition: transform 0.3s ease;
    }

    .form-container:hover {
      transform: translateY(-5px) rotate(1deg); /* Subtle 3D hover effect */
    }

    @keyframes pulse {
      0%, 100% { box-shadow: 0 8px 24px rgba(241, 196, 15, 0.4), 0 0 40px rgba(0, 0, 0, 0.8); }
      50% { box-shadow: 0 8px 32px rgba(241, 196, 15, 0.6), 0 0 48px rgba(0, 0, 0, 0.9); }
    }

    .form-section {
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .form-section h2 {
      font-size: 3rem;
      font-family: 'UnifrakturCook', sans-serif;
      color: #f1c40f;
      text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.9);
      margin-bottom: 1.5rem;
      text-align: center;
      letter-spacing: 1px;
    }

    .form-section form {
      display: flex;
      flex-direction: column;
      gap: 0.8rem;
    }

    .form-section input {
      width: 100%;
      padding: 0.9rem;
      font-size: 1.1rem;
      font-family: 'Arial', sans-serif;
      background: #2c2c2c;
      color: #fff;
      border: 2px solid #f1c40f;
      border-radius: 6px;
      outline: none;
      transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }

    .form-section input:focus {
      border-color: #ffe600;
      box-shadow: 0 0 8px rgba(255, 230, 0, 0.6);
    }

    .form-section input::placeholder {
      color: #999;
      font-size: 1rem;
    }

    .g-recaptcha {
      margin: 1.2rem auto;
      display: flex;
      justify-content: center;
    }

    .form-section button {
      background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); /* Fiery orange-red gradient */
      color: #fff;
      border: 2px solid #f1c40f;
      padding: 0.9rem 1.8rem;
      font-size: 1.3rem;
      border-radius: 6px;
      cursor: url('/Sahtout/img/hover_wow.gif') 16 16, auto;
      transition: all 0.3s ease;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .form-section button:hover {
      background: linear-gradient(135deg, #0e65d6ff 0%, #26a938ff 100%);
      transform: scale(1.05);
      box-shadow: 0 4px 16px rgba(230, 247, 3, 0.6);
    }

    .form-section .error {
      color: #e74c3c;
      font-size: 1.1rem;
      font-family: 'Arial', sans-serif;
      text-align: center;
      margin: 0.6rem 0 0;
      text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);
    }

    .form-section .resend-link {
      text-align: center;
      font-size: 1.1rem;
      font-family: 'UnifrakturCook', sans-serif;
      color: #fff;
      margin-bottom: 1.2rem;
    }

    .form-section .resend-link a {
      color: #f1c40f;
      text-decoration: none;
      transition: all 0.3s ease;
    }

    .form-section .resend-link a:hover {
      color: #ffe600;
      text-decoration: underline;
    }

    .form-section .resend-link p {
      font-family: 'Courier New', Courier, monospace;
      display: inline;
      margin-right: 0.6rem;
      color: #fff;
    }

    .form-section .register-link, .form-section .forgot-password-link {
      text-align: center;
      font-size: 1.1rem;
      font-family: 'UnifrakturCook', sans-serif;
      color: #fff;
      margin-top: 1.2rem;
    }

    .form-section .register-link a, .form-section .forgot-password-link a {
      color: #f1c40f;
      text-decoration: none;
      transition: all 0.3s ease;
    }

    .form-section .register-link a:hover, .form-section .forgot-password-link a:hover {
      color: #ffe600;
      text-decoration: underline;
    }

    @media (max-width: 767px) {
      html, body {
        width: 100%;
        overflow-x: hidden;
      }

      .wrapper {
        padding: 0;
        margin-top: 80px;
      }

      .form-container {
        max-width: 100%;
        width: calc(100% - 1.5rem);
        padding: 1.5rem;
        margin: 1.5rem auto;
        box-shadow: 0 6px 16px rgba(241, 196, 15, 0.3);
      }

      .form-container:hover {
        transform: translateY(-3px) rotate(0.5deg); /* Reduced for mobile */
      }

      .form-section h2 {
        font-size: 2.4rem;
      }

      .form-section input {
        font-size: 1rem;
        padding: 0.8rem;
      }

      .form-section button {
        font-size: 1.2rem;
        padding: 0.8rem 1.5rem;
      }

      .g-recaptcha {
        transform: scale(0.85);
        transform-origin: center;
      }

      .form-section .resend-link, .form-section .register-link, .form-section .forgot-password-link {
        font-size: 1rem;
        margin-top: 1rem;
      }

      .form-section .error {
        font-size: 1rem;
      }
    }

    @media (max-width: 576px) {
      .form-section h2 {
        font-size: 2rem;
      }

      .form-section input {
        font-size: 0.95rem;
        padding: 0.7rem;
      }

      .form-section button {
        font-size: 1.1rem;
        padding: 0.7rem 1.2rem;
      }

      .g-recaptcha {
        transform: scale(0.77);
      }

      .form-section .resend-link, .form-section .register-link, .form-section .forgot-password-link {
        font-size: 0.95rem;
      }

      .form-section .error {
        font-size: 0.95rem;
      }
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
                <p><?php echo translate('resend_activation_prompt', 'CLICK here:'); ?></p>
                <a href="/sahtout/resend_activation?username=<?php echo htmlspecialchars($username); ?>">
                  <?php echo translate('resend_activation_link', 'Resend Activation Code'); ?>
                </a>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <form method="POST">
          <input type="text" name="username" placeholder="<?php echo translate('username_placeholder', 'Username'); ?>" required value="<?php echo htmlspecialchars($username); ?>">
          <br>
          <input type="password" name="password" placeholder="<?php echo translate('password_placeholder', 'Password'); ?>" required>
          <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
          <button type="submit"><?php echo translate('login_button', 'Sign In'); ?></button>
          <div class="register-link">
            <?php echo translate('register_link_text', 'Don\'t have an account? <a href="/sahtout/register">Register now</a>'); ?>
          </div>
          <div class="forgot-password-link">
            <?php echo translate('forgot_password_link_text', 'Forgot your password? <a href="/sahtout/forgot_password">Reset it here</a>'); ?>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</body>
</html>

<?php include_once '../includes/footer.php'; ?>