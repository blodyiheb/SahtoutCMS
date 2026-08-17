<?php
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../../../includes/paths.php';
require_once $project_root . 'includes/session.php';
require_once $project_root . 'languages/language.php';
require_once $project_root . 'includes/config.settings.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'moderator'])) {
    header("Location: {$base_path}login");
    exit;
}

$page_class = 'smtp';

$errors = [];
$success = false;
$configMailFile = realpath($project_root . 'includes/config.mail.php');

// Load current SMTP settings
$smtp_status = 'disabled';
$current_smtp_host = '';
$current_smtp_user = '';
$current_smtp_pass = '';
$current_smtp_from = 'noreply@yourdomain.com';
$current_smtp_name = 'Sahtout Account';
$current_smtp_port = '587';
$current_smtp_secure = 'tls';

if (file_exists($configMailFile)) {
    // Include the config file to load variables
    include $configMailFile;
    
    // Check if SMTP is enabled using the variable from config.mail.php
    $smtp_status = (isset($smtp_enabled) && $smtp_enabled === true) ? 'enabled' : 'disabled';
    
    if ($smtp_status === 'enabled') {
        // Require autoload if not already loaded
        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            require_once $project_root . 'vendor/autoload.php';
        }
        
        // Check if getMailer function exists, if not, create it
        if (!function_exists('getMailer')) {
            // Define the function if it doesn't exist
            function getMailer() {
                global $smtp_host, $smtp_user, $smtp_pass, $smtp_from, $smtp_name, $smtp_port, $smtp_secure;
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                try {
                    $mail->CharSet = 'UTF-8';
                    $mail->isSMTP();
                    $mail->Host = $smtp_host;
                    $mail->SMTPAuth = true;
                    $mail->Username = $smtp_user;
                    $mail->Password = $smtp_pass;
                    $mail->SMTPSecure = $smtp_secure;
                    $mail->Port = (int)$smtp_port;
                    $mail->setFrom($smtp_from, $smtp_name);
                    $mail->isHTML(true);
                } catch (Exception $e) {}
                return $mail;
            }
        }
        
        // Now call getMailer() safely
        if (function_exists('getMailer')) {
            $mail = getMailer();
            $current_smtp_host = $mail->Host;
            $current_smtp_user = $mail->Username;
            $current_smtp_pass = '';
            $current_smtp_from = $mail->From;
            $current_smtp_name = $mail->FromName;
            $current_smtp_port = $mail->Port;
            $current_smtp_secure = $mail->SMTPSecure;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $smtp_enabled = isset($_POST['smtp_enabled']);
    $smtpHost = trim($_POST['smtp_host'] ?? '');
    $smtpUser = trim($_POST['smtp_user'] ?? '');
    $smtpPass = trim($_POST['smtp_pass'] ?? '');
    $smtpFrom = trim($_POST['smtp_from'] ?? 'noreply@yourdomain.com');
    $smtpName = trim($_POST['smtp_name'] ?? 'Sahtout Account');
    $smtpPort = trim($_POST['smtp_port'] ?? '587');
    $smtpSecure = trim($_POST['smtp_secure'] ?? 'tls');

    if ($smtp_enabled) {
        if (empty($smtpHost)) {
            $errors[] = translate('err_smtp_host_required', 'SMTP Host is required.');
        }
        if (empty($smtpUser)) {
            $errors[] = translate('err_smtp_user_required', 'SMTP Username is required.');
        }
        if (empty($smtpPass)) {
            $errors[] = translate('err_smtp_pass_required', 'SMTP Password is required.');
        }
        if (empty($smtpFrom)) {
            $errors[] = translate('err_smtp_from_required', 'From Email is required.');
        }
        
        // Validate port
        if (!is_numeric($smtpPort) || $smtpPort < 1 || $smtpPort > 65535) {
            $errors[] = translate('err_smtp_port_invalid', 'Port must be between 1 and 65535.');
        }
        
        // Validate encryption
        if (!in_array($smtpSecure, ['tls', 'ssl', ''])) {
            $errors[] = translate('err_smtp_secure_invalid', 'Encryption must be tls, ssl, or empty.');
        }
    }

    // Test SMTP connection if enabled and no errors
    if (empty($errors) && $smtp_enabled) {
        require_once $project_root . 'vendor/autoload.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;
            $mail->SMTPSecure = $smtpSecure;
            $mail->Port = (int)$smtpPort;
            $mail->setFrom($smtpFrom, $smtpName);
            $mail->addAddress($smtpUser);
            $mail->Subject = translate('mail_test_subject', 'Test Email - Sahtout CMS');
            $mail->Body = translate('mail_test_body', 'This is a test email from your Sahtout CMS admin settings.');
            $mail->send();
        } catch (Exception $e) {
            $errors[] = translate('err_smtp_test_failed', 'SMTP test failed:') . ' ' . $mail->ErrorInfo;
        }
    }

    if (empty($errors)) {
        if ($smtp_enabled) {
            $configContent = "<?php
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit(translate('error_direct_access', 'Direct access to this file is not allowed.'));
}

\$smtp_enabled = true;

use PHPMailer\\PHPMailer\\PHPMailer;
use PHPMailer\\PHPMailer\\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function getMailer(): PHPMailer {
    \$mail = new PHPMailer(true);
    try {
        \$mail->CharSet = 'UTF-8';
        \$mail->isSMTP();
        \$mail->Host       = '" . addslashes($smtpHost) . "';
        \$mail->SMTPAuth   = true;
        \$mail->Username   = '" . addslashes($smtpUser) . "';
        \$mail->Password   = '" . addslashes($smtpPass) . "';
        \$mail->SMTPSecure = '" . addslashes($smtpSecure) . "';
        \$mail->Port       = " . (int)$smtpPort . ";
        \$mail->setFrom('" . addslashes($smtpFrom) . "', '" . addslashes($smtpName) . "');
        \$mail->isHTML(true);
    } catch (Exception \$e) {}
    return \$mail;
}
?>";
        } else {
            $configContent = "<?php
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit(translate('error_direct_access', 'Direct access to this file is not allowed.'));
}

\$smtp_enabled = false;

use PHPMailer\\PHPMailer\\PHPMailer;
use PHPMailer\\PHPMailer\\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function getMailer(): PHPMailer {
    \$mail = new PHPMailer(true);
    return \$mail;
}
?>";
        }

        $configDir = dirname($configMailFile);
        if (!is_writable($configDir)) {
            $errors[] = sprintf(translate('err_config_dir_not_writable', 'Config directory is not writable: %s'), $configDir);
        } elseif (file_put_contents($configMailFile, $configContent) === false) {
            $errors[] = sprintf(translate('err_failed_write_config', 'Failed to write config file: %s'), $configMailFile);
        } else {
            $success = true;
            $smtp_status = $smtp_enabled ? 'enabled' : 'disabled';
            $current_smtp_host = $smtpHost;
            $current_smtp_user = $smtpUser;
            $current_smtp_pass = '';
            $current_smtp_from = $smtpFrom;
            $current_smtp_name = $smtpName;
            $current_smtp_port = $smtpPort;
            $current_smtp_secure = $smtpSecure;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo translate('page_description_smtp', 'SMTP Settings for Sahtout WoW Server'); ?>">
    <meta name="robots" content="noindex">
    <title><?php echo translate('page_title_smtp', 'SMTP Settings'); ?></title>
    <link rel="icon" href="<?php echo $base_path . $site_logo; ?>" type="image/x-icon">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/tailwind.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&family=Cinzel:wght@600;700;900&display=swap" rel="stylesheet">
    
    <style>
        /* Only keep what Tailwind CANNOT do */
        
        /* Font families */
        * { font-family: 'Inter', sans-serif; }
        .wow-title, .section-title, .form-label { font-family: 'Cinzel', serif; }
        
        /* Panel with gold corners AND inner border */
        .panel-gold-corners {
            position: relative;
        }
        
        /* Outer gold corner decorations */
        .panel-gold-corners::after {
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
        
        /* Inner border inset */
        .panel-gold-corners::before {
            content: '';
            position: absolute;
            inset: 5px;
            border: 1px solid rgba(201,162,39,.14);
            pointer-events: none;
        }
        
        /* Custom clip-path for buttons */
        .btn-clip {
            clip-path: polygon(10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%, 0 10px);
        }
        
        /* Custom toggle switch - Tailwind doesn't have built-in toggle */
        .toggle-switch {
            position: relative;
            width: 48px;
            height: 28px;
            flex-shrink: 0;
            display: inline-block;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(30, 35, 45, 0.8);
            border: 1px solid rgba(201,162,39,.2);
            transition: 0.3s;
            border-radius: 34px;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background: #6a7a8a;
            transition: 0.3s;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        .toggle-switch input:checked + .toggle-slider {
            background: linear-gradient(180deg, #2ecc71 0%, #27ae60 48%, #1a8a4a 100%);
            border-color: rgba(46, 204, 113, .3);
        }
        .toggle-switch input:checked + .toggle-slider:before {
            transform: translateX(20px);
            background: white;
        }
        
        /* SMTP fields toggle */
        .smtp-fields {
            display: none;
        }
        .smtp-fields.active {
            display: block;
        }
        
        /* Status badge */
        .status-badge.enabled {
            background: rgba(46, 204, 113, 0.15);
            border-color: rgba(46, 204, 113, 0.4);
            color: #2ecc71;
        }
        .status-badge.disabled {
            background: rgba(231, 76, 60, 0.15);
            border-color: rgba(231, 76, 60, 0.4);
            color: #e74c3c;
        }
    </style>
</head>
<body class="min-h-screen text-[#d8d8d8] bg-[#05070b] bg-fixed"
      style="background-image: 
        radial-gradient(1000px 700px at -10% 35%, rgba(59,130,246,.14), transparent 65%),
        radial-gradient(800px 600px at -5% 85%, rgba(124,58,237,.10), transparent 70%),
        linear-gradient(180deg, #0a0e16 0%, #060810 45%, #03040a 100%);">
    
    <?php include $project_root . 'includes/header.php'; ?>

    <div class="flex relative min-h-screen">
        
        <!-- Sidebar -->
        <?php include $project_root . 'includes/admin_sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content-area flex-1 p-3 sm:p-4 md:p-6 lg:p-8 transition-all duration-300 lg:ml-[280px]">
            <div class="max-w-[1400px] mx-auto px-1 sm:px-4 md:px-6 lg:px-8 xl:px-10">
                <div class="space-y-4 md:space-y-6 lg:space-y-8">
                    
                    <h1 class="wow-title text-2xl md:text-3xl lg:text-4xl font-black 
                               bg-gradient-to-b from-[#fff7d6] via-[#f2cf5b] via-[#c9a227] to-[#8a6a14] 
                               bg-clip-text text-transparent drop-shadow-[0_3px_6px_rgba(0,0,0,.85)]">
                        <?php echo translate('page_title_smtp', 'SMTP Settings'); ?>
                    </h1>

                    <!-- Settings Navbar -->
                    <?php include $project_root . 'pages/admin/settings/settings_navbar.php'; ?>

                    <!-- Status Badge -->
                    <div class="flex items-center gap-3">
                        <span class="text-gray-400 text-sm font-semibold"><?php echo translate('status', 'Status:'); ?></span>
                        <span class="status-badge <?php echo $smtp_status === 'enabled' ? 'enabled' : 'disabled'; ?> 
                                     inline-flex items-center gap-2 px-4 py-1.5 font-bold text-sm border rounded-sm">
                            <i class="fas <?php echo $smtp_status === 'enabled' ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                            <?php echo translate(
                                $smtp_status === 'enabled' ? 'msg_smtp_enabled' : 'msg_smtp_disabled',
                                $smtp_status === 'enabled' ? 'SMTP Enabled' : 'SMTP Disabled'
                            ); ?>
                        </span>
                    </div>

                    <!-- Success / Error Messages -->
                    <?php if (!empty($errors)): ?>
                        <div class="bg-[#e74c3c]/15 border border-[#e74c3c]/40 text-[#e74c3c] 
                                    p-4 rounded-sm flex items-start gap-3">
                            <i class="fas fa-exclamation-circle text-xl mt-0.5"></i>
                            <div>
                                <strong><?php echo translate('err_fix_errors', 'Please fix the following errors:'); ?></strong>
                                <?php foreach ($errors as $err): ?>
                                    <div class="text-sm mt-1">• <?php echo htmlspecialchars($err); ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="bg-[#2ecc71]/15 border border-[#2ecc71]/40 text-[#2ecc71] 
                                    p-4 rounded-sm flex items-center gap-3">
                            <i class="fas fa-check-circle text-xl"></i>
                            <span><?php echo translate('msg_smtp_saved', 'SMTP settings saved successfully!'); ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- SMTP Settings Form -->
                    <div class="relative bg-gradient-to-b from-[#161920]/92 to-[#080a0e]/90 
                                border border-[#c9a227]/[0.22] 
                                shadow-[0_12px_32px_rgba(0,0,0,.55),inset_0_0_60px_rgba(0,0,0,.45)]
                                p-4 md:p-6 lg:p-8 panel-gold-corners">
                        
                        <h2 class="section-title text-lg md:text-xl mb-4 md:mb-6 flex items-center gap-3 
                                   text-[#f2cf5b] font-bold drop-shadow-[0_0_12px_rgba(201,162,39,.35),0_2px_4px_rgba(0,0,0,.8)]">
                            <i class="fas fa-envelope text-[#f2cf5b]"></i>
                            <?php echo translate('settings_smtp', 'SMTP Settings'); ?>
                        </h2>

                        <form method="POST" class="space-y-4 md:space-y-6 max-w-3xl mx-auto">
                            <!-- Enable SMTP Toggle -->
                            <div class="flex items-center gap-4 p-4 rounded-sm bg-[rgba(201,162,39,0.05)] border border-[rgba(201,162,39,0.1)]">
                                <label class="toggle-switch">
                                    <input type="checkbox" id="smtp_enabled" name="smtp_enabled" <?php echo (isset($_POST['smtp_enabled']) || $smtp_status === 'enabled') ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                                <div>
                                    <label for="smtp_enabled" class="text-sm font-semibold text-[#f2cf5b] cursor-pointer flex items-center gap-2">
                                        <i class="fas fa-envelope"></i>
                                        <?php echo translate('label_smtp_enabled', 'Enable SMTP'); ?>
                                    </label>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo translate('help_smtp_enabled', 'Enable to send emails via SMTP server.'); ?></p>
                                </div>
                            </div>

                            <!-- SMTP Fields -->
                            <div class="smtp-fields <?php echo (isset($_POST['smtp_enabled']) || $smtp_status === 'enabled') ? 'active' : ''; ?> space-y-4">
                                <div>
                                    <label for="smtp_host" class="form-label text-[#f2cf5b] font-bold text-sm 
                                                                     tracking-wider block mb-2 
                                                                     drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                        <?php echo translate('label_smtp_host', 'SMTP Host'); ?>
                                    </label>
                                    <input type="text" id="smtp_host" name="smtp_host" 
                                           class="w-full px-4 py-3 text-[0.95rem] text-[#e5e7eb] 
                                                  bg-[#0a0e16]/80 border border-[#c9a227]/30 rounded-sm 
                                                  focus:border-[#f2cf5b] focus:shadow-[0_0_10px_rgba(242,207,82,.2)] 
                                                  focus:bg-[#0f141e]/90 outline-none transition-all duration-200 
                                                  placeholder:text-[#96aac8]/40"
                                           placeholder="<?php echo translate('placeholder_smtp_host', 'e.g., smtp.gmail.com'); ?>" 
                                           value="<?php echo htmlspecialchars($_POST['smtp_host'] ?? $current_smtp_host); ?>">
                                </div>

                                <div>
                                    <label for="smtp_user" class="form-label text-[#f2cf5b] font-bold text-sm 
                                                                     tracking-wider block mb-2 
                                                                     drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                        <?php echo translate('label_email_address', 'Email Address'); ?>
                                    </label>
                                    <input type="email" id="smtp_user" name="smtp_user" 
                                           class="w-full px-4 py-3 text-[0.95rem] text-[#e5e7eb] 
                                                  bg-[#0a0e16]/80 border border-[#c9a227]/30 rounded-sm 
                                                  focus:border-[#f2cf5b] focus:shadow-[0_0_10px_rgba(242,207,82,.2)] 
                                                  focus:bg-[#0f141e]/90 outline-none transition-all duration-200 
                                                  placeholder:text-[#96aac8]/40"
                                           placeholder="<?php echo translate('placeholder_email', 'e.g., yourname@gmail.com'); ?>" 
                                           value="<?php echo htmlspecialchars($_POST['smtp_user'] ?? $current_smtp_user); ?>">
                                </div>

                                <div>
                                    <label for="smtp_pass" class="form-label text-[#f2cf5b] font-bold text-sm 
                                                                     tracking-wider block mb-2 
                                                                     drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                        <?php echo translate('label_app_password', 'App Password / SMTP Password'); ?>
                                    </label>
                                    <input type="password" id="smtp_pass" name="smtp_pass" 
                                           class="w-full px-4 py-3 text-[0.95rem] text-[#e5e7eb] 
                                                  bg-[#0a0e16]/80 border border-[#c9a227]/30 rounded-sm 
                                                  focus:border-[#f2cf5b] focus:shadow-[0_0_10px_rgba(242,207,82,.2)] 
                                                  focus:bg-[#0f141e]/90 outline-none transition-all duration-200 
                                                  placeholder:text-[#96aac8]/40"
                                           placeholder="<?php echo translate('placeholder_app_password', 'App password for Gmail/Outlook'); ?>" 
                                           value="<?php echo htmlspecialchars($_POST['smtp_pass'] ?? $current_smtp_pass); ?>">
                                    <div class="text-[#6a7a8a] text-xs mt-1"><?php echo translate('help_smtp_pass', 'For Gmail, use an App Password. For other providers, use your email password.'); ?></div>
                                </div>

                                <div>
                                    <label for="smtp_from" class="form-label text-[#f2cf5b] font-bold text-sm 
                                                                     tracking-wider block mb-2 
                                                                     drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                        <?php echo translate('label_from_email', 'From Email'); ?>
                                    </label>
                                    <input type="email" id="smtp_from" name="smtp_from" 
                                           class="w-full px-4 py-3 text-[0.95rem] text-[#e5e7eb] 
                                                  bg-[#0a0e16]/80 border border-[#c9a227]/30 rounded-sm 
                                                  focus:border-[#f2cf5b] focus:shadow-[0_0_10px_rgba(242,207,82,.2)] 
                                                  focus:bg-[#0f141e]/90 outline-none transition-all duration-200 
                                                  placeholder:text-[#96aac8]/40"
                                           placeholder="<?php echo translate('placeholder_from_email', 'e.g., noreply@yourdomain.com'); ?>" 
                                           value="<?php echo htmlspecialchars($_POST['smtp_from'] ?? $current_smtp_from); ?>">
                                </div>

                                <div>
                                    <label for="smtp_name" class="form-label text-[#f2cf5b] font-bold text-sm 
                                                                     tracking-wider block mb-2 
                                                                     drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                        <?php echo translate('label_from_name', 'From Name'); ?>
                                    </label>
                                    <input type="text" id="smtp_name" name="smtp_name" 
                                           class="w-full px-4 py-3 text-[0.95rem] text-[#e5e7eb] 
                                                  bg-[#0a0e16]/80 border border-[#c9a227]/30 rounded-sm 
                                                  focus:border-[#f2cf5b] focus:shadow-[0_0_10px_rgba(242,207,82,.2)] 
                                                  focus:bg-[#0f141e]/90 outline-none transition-all duration-200 
                                                  placeholder:text-[#96aac8]/40"
                                           placeholder="<?php echo translate('placeholder_from_name', 'e.g., Sahtout Account'); ?>" 
                                           value="<?php echo htmlspecialchars($_POST['smtp_name'] ?? $current_smtp_name); ?>">
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="smtp_port" class="form-label text-[#f2cf5b] font-bold text-sm 
                                                                         tracking-wider block mb-2 
                                                                         drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                            <?php echo translate('label_port', 'Port'); ?>
                                        </label>
                                        <input type="number" id="smtp_port" name="smtp_port" 
                                               class="w-full px-4 py-3 text-[0.95rem] text-[#e5e7eb] 
                                                      bg-[#0a0e16]/80 border border-[#c9a227]/30 rounded-sm 
                                                      focus:border-[#f2cf5b] focus:shadow-[0_0_10px_rgba(242,207,82,.2)] 
                                                      focus:bg-[#0f141e]/90 outline-none transition-all duration-200 
                                                      placeholder:text-[#96aac8]/40"
                                               placeholder="<?php echo translate('placeholder_port_tls_ssl', '587 for TLS'); ?>" 
                                               value="<?php echo htmlspecialchars($_POST['smtp_port'] ?? $current_smtp_port); ?>"
                                               min="1" max="65535">
                                    </div>
                                    <div>
                                        <label for="smtp_secure" class="form-label text-[#f2cf5b] font-bold text-sm 
                                                                         tracking-wider block mb-2 
                                                                         drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                            <?php echo translate('label_encryption', 'Encryption'); ?>
                                        </label>
                                        <select id="smtp_secure" name="smtp_secure" 
                                                class="w-full px-4 py-3 text-[0.95rem] text-[#e5e7eb] 
                                                       bg-[#0a0e16]/80 border border-[#c9a227]/30 rounded-sm 
                                                       focus:border-[#f2cf5b] focus:shadow-[0_0_10px_rgba(242,207,82,.2)] 
                                                       focus:bg-[#0f141e]/90 outline-none transition-all duration-200 
                                                       placeholder:text-[#96aac8]/40">
                                            <option value="tls" <?php echo (isset($_POST['smtp_secure']) ? $_POST['smtp_secure'] : $current_smtp_secure) === 'tls' ? 'selected' : ''; ?>>TLS (Recommended)</option>
                                            <option value="ssl" <?php echo (isset($_POST['smtp_secure']) ? $_POST['smtp_secure'] : $current_smtp_secure) === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                            <option value="" <?php echo (isset($_POST['smtp_secure']) ? $_POST['smtp_secure'] : $current_smtp_secure) === '' ? 'selected' : ''; ?>>None</option>
                                        </select>
                                        <div class="text-[#6a7a8a] text-xs mt-1"><?php echo translate('help_smtp_secure', 'Most providers use TLS on port 587.'); ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Save Button -->
                            <div class="pt-4 border-t border-[rgba(201,162,39,.1)] flex justify-end">
                                <button type="submit" class="btn-clip inline-flex items-center gap-2 px-6 py-3 
                                                             font-extrabold text-xs uppercase tracking-wider
                                                             bg-gradient-to-b from-[#f6d478] via-[#c9a227] to-[#8a6a14] 
                                                             text-[#1a1200] shadow-[inset_0_0_0_1px_rgba(255,255,255,.28),inset_0_-8px_14px_rgba(0,0,0,.25)]
                                                             hover:scale-105 transition-transform duration-200">
                                    <i class="fas fa-save"></i>
                                    <?php echo translate('btn_save_test_smtp', 'Save & Test SMTP'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggle = document.getElementById('smtp_enabled');
            const fields = document.querySelector('.smtp-fields');

            function toggleFields() {
                if (toggle && fields) {
                    if (toggle.checked) {
                        fields.classList.add('active');
                        fields.querySelectorAll('input, select').forEach(input => {
                            input.disabled = false;
                        });
                    } else {
                        fields.classList.remove('active');
                        fields.querySelectorAll('input, select').forEach(input => {
                            input.disabled = true;
                        });
                    }
                }
            }

            if (toggle) {
                toggle.addEventListener('change', toggleFields);
                toggleFields();
            }
        });
    </script>
</body>
</html>