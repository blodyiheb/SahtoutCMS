<?php
// Start session with secure settings
ini_set('session.cookie_httponly', 1); // Prevent JavaScript access to session cookie
ini_set('session.use_only_cookies', 1); // Use cookies only for session ID
ini_set('session.cookie_secure', isset($_SERVER['HTTPS'])); // Use secure cookies if HTTPS
session_start();

// Include database configuration
require_once __DIR__ . '/config.php';

// Initialize debug errors
$_SESSION['debug_errors'] = $_SESSION['debug_errors'] ?? [];

// Regenerate session ID periodically to prevent session fixation
if (!isset($_SESSION['last_regeneration']) || (time() - $_SESSION['last_regeneration']) > 1800) { // Every 30 minutes
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Define protected and admin pages
$protected_pages = ['account.php', 'buy_item.php'];
$admin_pages = ['dashboard.php', 'users.php', 'anews.php','characters.php','ashop.php','gm_cmd.php', 'logout.php'];
$current_page = basename($_SERVER['PHP_SELF']);

// Validate session for protected and admin pages
if (in_array($current_page, $protected_pages) || in_array($current_page, $admin_pages)) {
    if (empty($_SESSION['user_id']) || empty($_SESSION['username'])) {
        $_SESSION['debug_errors'] = array_diff($_SESSION['debug_errors'], ["No user session detected. Ensure login script sets \$_SESSION['user_id'] and \$_SESSION['username']."]);
        $_SESSION['debug_errors'][] = "No user session detected. Ensure login script sets \$_SESSION['user_id'] and \$_SESSION['username'].";
        session_unset();
        session_destroy();
        header('Location: /Sahtout/login?error=invalid_session');
        exit();
    }

    // Validate user_id and username against auth database
    /** @var \mysqli_stmt|false $stmt */
    $stmt = $auth_db->prepare("SELECT id, username FROM account WHERE id = ? AND username = ?");
    $stmt->bind_param('is', $_SESSION['user_id'], $_SESSION['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows !== 1) {
        $_SESSION['debug_errors'] = ["Invalid session data. User ID or username mismatch."];
        session_unset();
        session_destroy();
        header('Location: /Sahtout/login?error=invalid_session');
        exit();
    }
    $stmt->close();

    // Load user role
    /** @var \mysqli_stmt|false $stmt */
    $stmt = $site_db->prepare("SELECT role FROM user_currencies WHERE account_id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $_SESSION['role'] = $result->num_rows === 1 ? $result->fetch_assoc()['role'] : 'player';
    $stmt->close();

    // Load GM level
    /** @var \mysqli_stmt|false $stmt */
    $stmt = $auth_db->prepare("SELECT gmlevel FROM account_access WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $_SESSION['gmlevel'] = $result->num_rows > 0 ? $result->fetch_assoc()['gmlevel'] : 0;
    $stmt->close();

    // Restrict admin pages to admin or moderator
    if (in_array($current_page, $admin_pages) && !in_array($_SESSION['role'], ['admin', 'moderator'])) {
        $_SESSION['debug_errors'] = ["Unauthorized access to admin page."];
        header('Location: /Sahtout/login?error=unauthorized');
        exit();
    }
}

// For login, register, and activate pages, redirect to account if already logged in
$public_pages = ['login.php', 'register.php', 'activate.php','forgot-password.php', 'reset_password.php','resend-activation.php'];
if (in_array($current_page, $public_pages) && !empty($_SESSION['user_id']) && !empty($_SESSION['username'])) {
    /** @var \mysqli_stmt|false $stmt */
    $stmt = $auth_db->prepare("SELECT id FROM account WHERE id = ? AND username = ?");
    $stmt->bind_param('is', $_SESSION['user_id'], $_SESSION['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        header('Location: /Sahtout/account');
        exit();
    }
    $_SESSION['debug_errors'] = ["Invalid session data on public page."];
    session_unset();
    session_destroy();
    $stmt->close();
}
?>