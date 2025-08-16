<?php
// Ensure session is started
define('ALLOWED_ACCESS', true);
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../../includes/session.php'; // Includes config.php and starts session
} else {
    require_once __DIR__ . '/../../includes/config.php'; // Include config for consistency
}

// Clear session data
session_unset();
session_destroy();

// Redirect to login page
header('Location: /Sahtout/login');
exit;
?>