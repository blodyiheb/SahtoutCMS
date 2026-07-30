<?php
// Ensure session is started
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../../includes/paths.php';
if (session_status() === PHP_SESSION_NONE) {
    require_once $project_root . 'includes/session.php'; // Includes config.php and starts session
} else {
    require_once $project_root . 'includes/config.php'; // Include config for consistency
}

// Clear session data
session_unset();
session_destroy();

// Redirect to login page
header("Location: {$base_path}login");
exit;
?>