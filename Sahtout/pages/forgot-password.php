<?php
require_once '../includes/session.php';

// Redirect to account if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: /Sahtout/pages/account.php");
    exit();
}

$page_class = 'forgot-password';
require_once '../includes/config.cap.php'; // ✅ reCAPTCHA keys

$errors = [];
$success = '';
$username_or_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_or_email = trim($_POST['username_or_email'] ?? '');

    // Basic field validation
    if (empty($username_or_email)) {
        $errors[] = "Username or email is required";
    }

    // ✅ Google reCAPTCHA validation
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    $verify = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . RECAPTCHA_SECRET_KEY . '&response=' . $recaptchaResponse);
    $responseData = json_decode($verify);
    if (!$responseData->success) {
        $errors[] = "reCAPTCHA verification failed.";
    }

    if (empty($errors)) {
        // Here you would typically:
        // 1. Check if the username or email exists in the database
        // 2. Generate a reset token and store it
        // 3. Send a reset email to the user
        // For this example, we'll assume success and display a message
        $success = "If the provided username or email exists, a password reset link has been sent.";
        $username_or_email = ''; // Clear the input
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
  <title>Forgot Password</title>

</head>
<body>
  <div class="wrapper">
    <div class="form-container">
      <div class="form-section">
        <h2>Forgot Password</h2>

        <?php if (!empty($errors)): ?>
          <div class="error">
            <?php foreach ($errors as $error): ?>
              <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
          <div class="success">
            <p><?php echo htmlspecialchars($success); ?></p>
          </div>
        <?php endif; ?>

        <form method="POST">
          <input type="text" name="username_or_email" placeholder="Username or Email" required value="<?php echo htmlspecialchars($username_or_email); ?>">

          <!-- ✅ Google reCAPTCHA Widget -->
          <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>

          <button type="submit">Send Reset Link</button>

          <div class="login-link">
            Remembered your password? <a href="/sahtout/pages/login.php">Log in here</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ✅ reCAPTCHA Script -->
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  
</body>
</html>

<?php include_once '../includes/footer.php'; ?>