<?php
define('ALLOWED_ACCESS', true);
require_once '../includes/session.php';
// Redirect to account if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: /Sahtout/account");
    exit();
}

$page_class = 'login';
require_once '../includes/config.cap.php'; // reCAPTCHA keys
require_once '../includes/srp6.php';

$errors = [];
$username = '';
$show_resend_button = false;

// Check for error query parameter
if (isset($_GET['error']) && $_GET['error'] === 'invalid_session') {
    $errors[] = "Invalid session, please log in again.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic field validation
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    if (empty($password)) {
        $errors[] = "Password is required";
    }

    // Google reCAPTCHA validation
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    $verify = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . RECAPTCHA_SECRET_KEY . '&response=' . $recaptchaResponse);
    $responseData = json_decode($verify);
    if (!$responseData->success) {
        $errors[] = "reCAPTCHA verification failed.";
    }

    if (empty($errors)) {
        // Check if account is in pending_accounts and not activated
        $stmt = $site_db->prepare("SELECT username FROM pending_accounts WHERE username = ? AND activated = 0");
        $upper_username = strtoupper($username);
        $stmt->bind_param('s', $upper_username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Your account is not activated. Please check your email to activate your account.";
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
                $errors[] = "Invalid username or password";
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
                    $errors[] = "Invalid username or password";
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
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login</title>
  <style>
    html, body {
      width: 100%;
      overflow-x: hidden;
      margin: 0;
    }

    .wrapper {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 1rem;
      width: 100%;
    }

    .form-container {
      max-width: 500px;
      width: calc(100% - 2rem);
      background: rgba(0, 0, 0, 0.7);
      border: 2px solid #ffd700;
      border-radius: 8px;
      box-shadow: 0 0 15px rgba(255, 215, 0, 0.5);
      padding: 2.5rem;
    }

    .form-section {
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .form-section h2 {
      font-size: 2.8rem;
      font-family: 'UnifrakturCook', sans-serif;
      color: #ffd700;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
      margin-bottom: 1.2rem;
      text-align: center;
    }

    .form-section form {
      display: flex;
      flex-direction: column;
      gap: 1.2rem;
    }

    .form-section input {
      width: 100%;
      padding: 1rem;
      font-size: 1.1rem;
      font-family: 'Arial', sans-serif;
      background: #333;
      color: #fff;
      border: 1px solid #ffd700;
      border-radius: 4px;
      outline: none;
      transition: border-color 0.3s ease;
    }

    .form-section input:focus {
      border-color: #ffe600;
      box-shadow: 0 0 5px rgba(255, 230, 0, 0.5);
    }

    .form-section input::placeholder {
      color: #ccc;
    }

    .form-section button {
      background: #333;
      color: #ffd700;
      border: 2px solid #ffd700;
      padding: 1rem 2rem;
      font-family: 'UnifrakturCook', sans-serif;
      font-size: 1.2rem;
      border-radius: 4px;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .form-section button:hover {
      background: #ffd700;
      color: #000;
      transform: scale(1.05);
    }

    .form-section .error {
      color: #ff0000;
      font-size: 1.1rem;
      font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;
      text-align: center;
      margin: 0.5rem 0 0;
    }

    .form-section .resend-link {
      text-align: center;
      font-size: 1.1rem;
      font-family: 'UnifrakturCook', sans-serif;
      color: #fff;
      margin-bottom: 1rem;
    }

    .form-section .resend-link a {
      color: #002fffff;
      text-decoration: none;
      transition: all 0.3s ease;
    }

    .form-section .resend-link a:hover {
      color: #ffe600;
      text-decoration: underline;
    }

    .form-section .register-link, .form-section .forgot-password-link {
      text-align: center;
      font-size: 1.1rem;
      font-family: 'UnifrakturCook', sans-serif;
      color: #fff;
      margin-top: 1.5rem;
    }

    .form-section .register-link a, .form-section .forgot-password-link a {
      color: #ffd700;
      text-decoration: none;
      transition: all 0.3s ease;
    }

    .form-section .register-link a:hover, .form-section .forgot-password-link a:hover {
      color: #ffe600;
      text-decoration: underline;
    }

    @media (max-width: 767px) {
      .wrapper {
        padding: 0;
        margin-top: 100px;
      }

      .form-container {
        max-width: 90%;
        padding: 1.5rem;
      }

      .form-section h2 {
        font-size: 2.2rem;
      }

      .form-section input {
        font-size: 1rem;
        padding: 0.8rem;
      }

      .form-section button {
        font-size: 1.1rem;
        padding: 0.8rem 1.5rem;
      }

      .form-section .resend-link, .form-section .register-link, .form-section .forgot-password-link {
        font-size: 1rem;
        margin-top: 1rem;
      }
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="form-container">
      <div class="form-section">
        <h2>Login</h2>

        <?php if (!empty($errors)): ?>
          <div class="error">
            <?php foreach ($errors as $error): ?>
              <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
            <?php if ($show_resend_button): ?>
              <div class="resend-link">
                <a href="/sahtout/resend-activation?username=<?php echo htmlspecialchars($username); ?>"> <p style="font-family: 'Courier New', Courier, monospace;">CLICK here :</p>Resend Activation Code</a>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <form method="POST">
          <input type="text" name="username" placeholder="Username" required value="<?php echo htmlspecialchars($username); ?>">
          <input type="password" name="password" placeholder="Password" required>

          <!-- Google reCAPTCHA Widget -->
          <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>

          <button type="submit">Sign In</button>

          <div class="register-link">
            Don't have an account? <a href="/sahtout/register">Register now</a>
          </div>
          <div class="forgot-password-link">
            Forgot your password? <a href="/sahtout/forgot-password">Reset it here</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- reCAPTCHA Script -->
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  
</body>
</html>

<?php include_once '../includes/footer.php'; ?>