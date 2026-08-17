<?php
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../includes/paths.php';
require_once __DIR__ . '/header.inc.php';
require_once __DIR__ . '/languages/language.php';

// Set current step for progress stepper
$current_step = 5;

$errors = [];
$success = false;
$test_result = null;
$configMailFile = __DIR__ . '/../includes/config.mail.php';

// SMTP Configuration handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $smtp_enabled = isset($_POST['smtp_enabled']) ? 1 : 0;
    $smtp_host = trim($_POST['smtp_host'] ?? '');
    $smtp_user = trim($_POST['smtp_user'] ?? '');
    $smtp_pass = $_POST['smtp_pass'] ?? '';
    $smtp_from = trim($_POST['smtp_from'] ?? '');
    $smtp_name = trim($_POST['smtp_name'] ?? '');
    $smtp_port = (int) ($_POST['smtp_port'] ?? 587);
    $smtp_secure = trim($_POST['smtp_secure'] ?? 'tls');

    if ($smtp_enabled) {
        if (empty($smtp_host)) {
            $errors[] = translate('err_smtp_host_required', 'SMTP Host is required.');
        }
        if (empty($smtp_user)) {
            $errors[] = translate('err_smtp_user_required', 'SMTP Username is required.');
        }
        if (empty($smtp_pass)) {
            $errors[] = translate('err_smtp_pass_required', 'SMTP Password is required.');
        }
        if (empty($smtp_from)) {
            $errors[] = translate('err_smtp_from_required', 'From Email is required.');
        }
        
        // Validate port
        if ($smtp_port < 1 || $smtp_port > 65535) {
            $errors[] = translate('err_smtp_port_invalid', 'Port must be between 1 and 65535.');
        }
        
        // Validate encryption
        if (!in_array($smtp_secure, ['tls', 'ssl', ''])) {
            $errors[] = translate('err_smtp_secure_invalid', 'Encryption must be tls, ssl, or empty.');
        }
    }

    if (empty($errors)) {
        // Test SMTP connection if enabled
        if ($smtp_enabled) {
            try {
                // Load PHPMailer
                require_once __DIR__ . '/../vendor/autoload.php';
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                
                // Server settings
                $mail->CharSet = 'UTF-8';
                $mail->isSMTP();
                $mail->Host = $smtp_host;
                $mail->SMTPAuth = true;
                $mail->Username = $smtp_user;
                $mail->Password = $smtp_pass;
                $mail->SMTPSecure = $smtp_secure;
                $mail->Port = $smtp_port;
                
                // Set timeout (prevent hanging)
                $mail->Timeout = 30;
                
                // Sender
                $mail->setFrom($smtp_from, $smtp_name);
                
                // Recipient (send to the SMTP user email)
                $mail->addAddress($smtp_user);
                
                // Email content
                $mail->Subject = translate('mail_test_subject', 'Test Email - Sahtout CMS Installer');
                $mail->Body = translate('mail_test_body', 
                    'This is a test email from your Sahtout CMS installer.<br><br>
                    <strong>Configuration Details:</strong><br>
                    Host: ' . $smtp_host . '<br>
                    Port: ' . $smtp_port . '<br>
                    Encryption: ' . ($smtp_secure ?: 'None') . '<br>
                    From: ' . $smtp_from . ' (' . $smtp_name . ')<br><br>
                    If you received this email, your SMTP configuration is working correctly!'
                );
                $mail->isHTML(true);
                
                // Send test email
                $mail->send();
                $test_result = 'success';
                $success = true;
                
            } catch (Exception $e) {
                $test_result = 'failed';
                $errors[] = sprintf(translate('err_smtp_test_failed', 'SMTP test failed: %s'), $e->getMessage());
                $success = false;
            }
        } else {
            // SMTP disabled - just save without testing
            $success = true;
        }
        
        // Save configuration if SMTP test passed or SMTP is disabled
        if ($success) {
            $configContent = "<?php\n";
            $configContent .= "if (!defined('ALLOWED_ACCESS')) { exit('Forbidden'); }\n\n";
            $configContent .= "\$smtp_enabled = " . var_export((bool)$smtp_enabled, true) . ";\n";
            $configContent .= "\$smtp_host = " . var_export($smtp_host, true) . ";\n";
            $configContent .= "\$smtp_user = " . var_export($smtp_user, true) . ";\n";
            $configContent .= "\$smtp_pass = " . var_export($smtp_pass, true) . ";\n";
            $configContent .= "\$smtp_from = " . var_export($smtp_from, true) . ";\n";
            $configContent .= "\$smtp_name = " . var_export($smtp_name, true) . ";\n";
            $configContent .= "\$smtp_port = " . var_export($smtp_port, true) . ";\n";
            $configContent .= "\$smtp_secure = " . var_export($smtp_secure, true) . ";\n";
            $configContent .= "?>";

            $configDir = dirname($configMailFile);
            if (!is_writable($configDir)) {
                $errors[] = sprintf(translate('err_write_mail_config', 'Cannot write to %s.'), $configDir);
                $success = false;
            } elseif (file_put_contents($configMailFile, $configContent) === false) {
                $errors[] = sprintf(translate('err_write_mail_config', 'Cannot write to %s.'), $configMailFile);
                $success = false;
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($langCode ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= translate('installer_title', 'SahtoutCMS Installer') ?> - <?= translate('step5_title', 'Step 5: Email Setup') ?></title>
    
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="<?= htmlspecialchars($base_path ?? '/') ?>assets/css/tailwind.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="<?= htmlspecialchars($base_path ?? '/') ?>node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: 
                linear-gradient(135deg, rgba(10, 8, 15, 0.95), rgba(20, 12, 8, 0.95)),
                url('https://www.wallpaperflare.com/static/955/944/93/fantasy-art-dark-knight-artwork-wallpaper.jpg') 
                no-repeat center center fixed;
            background-size: cover;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .font-cinzel { font-family: 'Cinzel', serif; }
        
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #d97706; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #b45309; }

        .toggle-switch {
            position: relative;
            width: 48px;
            height: 24px;
            display: inline-block;
            flex-shrink: 0;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background: #4b5563;
            transition: .3s;
            border-radius: 24px;
        }
        .toggle-slider::before {
            content: '';
            position: absolute;
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background: white;
            transition: .3s;
            border-radius: 50%;
        }
        .toggle-switch input:checked + .toggle-slider {
            background: #2ecc71;
        }
        .toggle-switch input:checked + .toggle-slider::before {
            transform: translateX(24px);
        }

        .smtp-field {
            display: none;
        }
        .smtp-field.visible {
            display: block;
        }

        .main-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding-top: 20px;
            padding-bottom: 80px;
        }
        .content-container {
            max-width: 700px;
            width: 100%;
            margin: 0 auto;
            padding: 0 1rem;
        }
        @media (max-width: 768px) {
            .main-wrapper {
                padding-top: 10px;
                padding-bottom: 70px;
            }
        }
        
        .test-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
    
    <script>
        function toggleSmtpFields() {
            const enabled = document.getElementById('smtp_enabled').checked;
            const fields = document.getElementById('smtpFields');
            const status = document.getElementById('smtpStatus');
            
            if (enabled) {
                fields.classList.add('visible');
                status.textContent = '<?= translate('smtp_mail_enabled', 'Enabled') ?>';
                status.className = 'text-emerald-400 text-sm font-bold';
            } else {
                fields.classList.remove('visible');
                status.textContent = '<?= translate('smtp_mail_missing', 'Disabled') ?>';
                status.className = 'text-slate-400 text-sm font-bold';
            }
        }

        function toggleHelper(el) {
            const content = el.nextElementSibling;
            const icon = el.querySelector('.fa-chevron-down');
            content.classList.toggle('hidden');
            icon.classList.toggle('rotate-180');
        }
        
        function handleFormSubmit() {
            const btn = document.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="test-spinner mr-2"></span> Testing...';
            btn.disabled = true;
            return true;
        }
    </script>
</head>
<body onload="toggleSmtpFields()" class="text-slate-200">

<div class="main-wrapper">
    <!-- Progress Stepper -->
    <?php include __DIR__ . '/progress_stepper.inc.php'; ?>

    <!-- Main Container -->
    <div class="content-container flex-grow">
        <div class="bg-slate-900/60 backdrop-blur-xl border border-slate-700/50 rounded-2xl shadow-2xl p-6 md:p-10 relative overflow-hidden">
            
            <!-- Decorative Corner Elements -->
            <div class="absolute top-0 left-0 w-16 h-16 border-t-2 border-l-2 border-gold-500/30 rounded-tl-2xl pointer-events-none"></div>
            <div class="absolute bottom-0 right-0 w-16 h-16 border-b-2 border-r-2 border-gold-500/30 rounded-br-2xl pointer-events-none"></div>

            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gold-500/10 border border-gold-500/30 rounded-full mb-4">
                    <i class="fas fa-envelope text-3xl text-gold-400"></i>
                </div>
                <h1 class="font-cinzel text-3xl md:text-4xl font-bold bg-gradient-to-b from-amber-100 to-gold-500 bg-clip-text text-transparent">
                    <?= translate('step5_title', 'Step 5: Email Setup') ?>
                </h1>
                <p class="text-slate-400 mt-2 text-sm"><?= translate('step5_description', 'Configure SMTP for sending emails from your site.') ?></p>
            </div>

            <!-- Errors -->
            <?php if (!empty($errors)): ?>
                <div class="bg-red-900/30 border border-red-500/40 text-red-200 p-4 mb-6 rounded-lg">
                    <div class="flex items-center gap-2 mb-2 font-bold text-red-300">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?= translate('err_fix_errors', 'Please fix the following errors:') ?>
                    </div>
                    <ul class="list-disc list-inside text-sm space-y-1 text-red-100/90">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Success with Test Result -->
            <?php if ($success && $test_result === 'success'): ?>
                <div class="bg-emerald-900/30 border border-emerald-500/40 text-emerald-200 p-5 mb-6 rounded-lg">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-check-circle text-emerald-400 text-2xl"></i>
                        <div>
                            <span class="font-medium"><?= translate('msg_mail_saved', 'Email configuration saved! Test email sent successfully.') ?></span>
                            <p class="text-sm text-emerald-300/70 mt-1">
                                <i class="fas fa-envelope mr-1"></i>
                                <?= sprintf(translate('msg_test_email_sent_to', 'Test email sent to %s'), htmlspecialchars($smtp_user)) ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mt-6">
                    <a href="<?php echo $base_path; ?>install/step6_soap" class="inline-flex items-center px-8 py-3 bg-gold-500 hover:bg-gold-400 text-slate-900 font-bold rounded-lg shadow-lg shadow-gold-600/20 transition-all duration-300 transform hover:scale-105">
                        <?= translate('btn_proceed_to_soap', 'Proceed to Soap Configuration') ?>
                        <i class="fas fa-arrow-right ml-3"></i>
                    </a>
                    <a href="<?php echo $base_path; ?>install/step4_realm" class="inline-flex items-center px-6 py-3 bg-slate-700/50 hover:bg-slate-700/70 text-slate-300 font-semibold rounded-lg transition-all duration-300 border border-slate-600/30">
                        <i class="fas fa-arrow-left mr-2"></i>
                        <?= translate('btn_go_back', 'Go Back') ?>
                    </a>
                </div>
            <?php elseif ($success && !$smtp_enabled): ?>
                <div class="bg-emerald-900/30 border border-emerald-500/40 text-emerald-200 p-5 mb-6 rounded-lg flex items-center gap-3">
                    <i class="fas fa-check-circle text-emerald-400 text-2xl"></i>
                    <span class="font-medium"><?= translate('msg_mail_saved_disabled', 'Email configuration saved with SMTP disabled.') ?></span>
                </div>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mt-6">
                    <a href="<?php echo $base_path; ?>install/step6_soap" class="inline-flex items-center px-8 py-3 bg-gold-500 hover:bg-gold-400 text-slate-900 font-bold rounded-lg shadow-lg shadow-gold-600/20 transition-all duration-300 transform hover:scale-105">
                        <?= translate('btn_proceed_to_soap', 'Proceed to Soap Configuration') ?>
                        <i class="fas fa-arrow-right ml-3"></i>
                    </a>
                    <a href="<?php echo $base_path; ?>install/step4_realm" class="inline-flex items-center px-6 py-3 bg-slate-700/50 hover:bg-slate-700/70 text-slate-300 font-semibold rounded-lg transition-all duration-300 border border-slate-600/30">
                        <i class="fas fa-arrow-left mr-2"></i>
                        <?= translate('btn_go_back', 'Go Back') ?>
                    </a>
                </div>
            <?php endif; ?>

            <?php if (!$success || (isset($test_result) && $test_result === 'failed')): ?>
                <form method="post" class="space-y-5" onsubmit="return handleFormSubmit()">
                    <!-- Toggle Switch -->
                    <div class="flex items-center gap-4 p-4 bg-slate-800/30 border border-slate-700/50 rounded-lg">
                        <label class="toggle-switch">
                            <input type="checkbox" id="smtp_enabled" name="smtp_enabled" onclick="toggleSmtpFields()" <?= isset($_POST['smtp_enabled']) ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                        <span class="text-slate-300 text-sm font-medium"><?= translate('label_enable_smtp', 'Enable SMTP Mailer') ?></span>
                        <span id="smtpStatus" class="text-slate-400 text-sm font-bold"><?= isset($_POST['smtp_enabled']) ? translate('smtp_mail_enabled', 'Enabled') : translate('smtp_mail_missing', 'Disabled') ?></span>
                    </div>

                    <!-- SMTP Fields -->
                    <div id="smtpFields" class="smtp-field <?= isset($_POST['smtp_enabled']) ? 'visible' : '' ?> space-y-4">
                        <h2 class="font-cinzel text-gold-400 font-bold text-lg flex items-center gap-2 border-b border-slate-700/50 pb-3">
                            <i class="fas fa-cogs"></i>
                            <?= translate('section_smtp_config', 'SMTP Configuration') ?>
                        </h2>

                        <div>
                            <label for="smtp_host" class="block text-xs font-medium text-slate-400 mb-1 uppercase tracking-wider">
                                <i class="fas fa-server text-gold-400 mr-1"></i>
                                <?= translate('label_smtp_host', 'SMTP Host') ?>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 pointer-events-none">
                                    <i class="fas fa-server text-sm"></i>
                                </span>
                                <input id="smtp_host" type="text" name="smtp_host" 
                                       value="<?= htmlspecialchars($_POST['smtp_host'] ?? '') ?>"
                                       class="w-full bg-slate-900/60 border border-slate-600 text-slate-200 rounded-lg pl-10 pr-4 py-2.5 focus:ring-2 focus:ring-gold-500 focus:border-gold-500 transition-all placeholder-slate-500 text-sm"
                                       placeholder="<?= translate('placeholder_smtp_host', 'e.g., smtp.gmail.com') ?>">
                            </div>
                        </div>

                        <div>
                            <label for="smtp_user" class="block text-xs font-medium text-slate-400 mb-1 uppercase tracking-wider">
                                <i class="fas fa-user text-gold-400 mr-1"></i>
                                <?= translate('label_email_address', 'Email Address') ?>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 pointer-events-none">
                                    <i class="fas fa-envelope text-sm"></i>
                                </span>
                                <input id="smtp_user" type="email" name="smtp_user" 
                                       value="<?= htmlspecialchars($_POST['smtp_user'] ?? '') ?>"
                                       class="w-full bg-slate-900/60 border border-slate-600 text-slate-200 rounded-lg pl-10 pr-4 py-2.5 focus:ring-2 focus:ring-gold-500 focus:border-gold-500 transition-all placeholder-slate-500 text-sm"
                                       placeholder="<?= translate('placeholder_email', 'e.g., yourname@gmail.com') ?>">
                            </div>
                        </div>

                        <div>
                            <label for="smtp_pass" class="block text-xs font-medium text-slate-400 mb-1 uppercase tracking-wider">
                                <i class="fas fa-lock text-gold-400 mr-1"></i>
                                <?= translate('label_app_password', 'App Password / SMTP Password') ?>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 pointer-events-none">
                                    <i class="fas fa-key text-sm"></i>
                                </span>
                                <input id="smtp_pass" type="password" name="smtp_pass" 
                                       value="<?= htmlspecialchars($_POST['smtp_pass'] ?? '') ?>"
                                       class="w-full bg-slate-900/60 border border-slate-600 text-slate-200 rounded-lg pl-10 pr-4 py-2.5 focus:ring-2 focus:ring-gold-500 focus:border-gold-500 transition-all placeholder-slate-500 text-sm"
                                       placeholder="<?= translate('placeholder_app_password', 'App password for Gmail/Outlook') ?>">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="smtp_from" class="block text-xs font-medium text-slate-400 mb-1 uppercase tracking-wider">
                                    <?= translate('label_from_email', 'From Email') ?>
                                </label>
                                <input id="smtp_from" type="email" name="smtp_from" 
                                       value="<?= htmlspecialchars($_POST['smtp_from'] ?? 'noreply@yourdomain.com') ?>"
                                       class="w-full bg-slate-900/60 border border-slate-600 text-slate-200 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-gold-500 focus:border-gold-500 transition-all text-sm">
                            </div>
                            <div>
                                <label for="smtp_name" class="block text-xs font-medium text-slate-400 mb-1 uppercase tracking-wider">
                                    <?= translate('label_from_name', 'From Name') ?>
                                </label>
                                <input id="smtp_name" type="text" name="smtp_name" 
                                       value="<?= htmlspecialchars($_POST['smtp_name'] ?? 'Sahtout Account') ?>"
                                       class="w-full bg-slate-900/60 border border-slate-600 text-slate-200 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-gold-500 focus:border-gold-500 transition-all text-sm">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="smtp_port" class="block text-xs font-medium text-slate-400 mb-1 uppercase tracking-wider">
                                    <?= translate('label_port', 'Port') ?>
                                </label>
                                <input id="smtp_port" type="number" name="smtp_port" 
                                       value="<?= htmlspecialchars($_POST['smtp_port'] ?? '587') ?>"
                                       min="1" max="65535"
                                       class="w-full bg-slate-900/60 border border-slate-600 text-slate-200 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-gold-500 focus:border-gold-500 transition-all text-sm">
                            </div>
                            <div>
                                <label for="smtp_secure" class="block text-xs font-medium text-slate-400 mb-1 uppercase tracking-wider">
                                    <?= translate('label_encryption', 'Encryption (tls or ssl)') ?>
                                </label>
                                <select id="smtp_secure" name="smtp_secure" 
                                        class="w-full bg-slate-900/60 border border-slate-600 text-slate-200 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-gold-500 focus:border-gold-500 transition-all text-sm">
                                    <option value="tls" <?= (isset($_POST['smtp_secure']) && $_POST['smtp_secure'] === 'tls') ? 'selected' : '' ?>>TLS (Recommended)</option>
                                    <option value="ssl" <?= (isset($_POST['smtp_secure']) && $_POST['smtp_secure'] === 'ssl') ? 'selected' : '' ?>>SSL</option>
                                    <option value="" <?= (isset($_POST['smtp_secure']) && $_POST['smtp_secure'] === '') ? 'selected' : '' ?>>None</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Test Info Box -->
                        <div class="bg-slate-800/30 border border-slate-700/50 rounded-lg p-3 mt-2">
                            <p class="text-xs text-slate-400 flex items-center gap-2">
                                <i class="fas fa-info-circle text-gold-400"></i>
                                <?= translate('info_test_email', 'A test email will be sent to your email address to verify the configuration.') ?>
                            </p>
                        </div>
                    </div>

                    <!-- Helper Box -->
                    <div class="bg-slate-800/30 border border-slate-700/50 rounded-lg p-4">
                        <div class="flex items-center gap-2 cursor-pointer" onclick="toggleHelper(this)">
                            <i class="fas fa-question-circle text-gold-400"></i>
                            <span class="text-gold-400 font-semibold text-sm"><?= translate('helper_title_smtp', 'How to get your SMTP info / App Password (Click to expand)') ?></span>
                            <i class="fas fa-chevron-down text-slate-500 text-xs ml-auto"></i>
                        </div>
                        <div class="helper-content hidden mt-3 text-slate-300 text-sm space-y-1">
                            <ol class="list-decimal list-inside space-y-1">
                                <li><?= translate('helper_smtp_li1', 'Use a real email account (Gmail, Outlook, or your own domain).') ?></li>
                                <li><?= translate('helper_smtp_li2', 'For Gmail, enable 2FA and generate an <strong class="text-gold-400">App Password</strong>.') ?></li>
                                <li><?= translate('helper_smtp_li3', 'SMTP Host examples:') ?><br>
                                    <span class="text-gold-400">Gmail:</span> smtp.gmail.com<br>
                                    <span class="text-gold-400">Outlook:</span> smtp.office365.com<br>
                                    <span class="text-gold-400"><?= translate('helper_smtp_custom_domain', 'Custom domain:') ?></span> <?= translate('helper_smtp_custom_domain_example', 'usually mail.yourdomain.com') ?>
                                </li>
                                <li><?= translate('helper_smtp_li4', 'Use port <strong class="text-gold-400">587</strong> with <strong class="text-gold-400">TLS</strong> or port <strong class="text-gold-400">465</strong> with <strong class="text-gold-400">SSL</strong>.') ?></li>
                                <li><?= translate('helper_smtp_li5', 'Enter your email address as the username and your App Password (or regular password if allowed).') ?></li>
                                <li><?= translate('helper_smtp_li6', 'The "From Email" can be the same as your SMTP user or a different sender you own.') ?></li>
                            </ol>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-2">
                        <button type="submit" class="w-full sm:w-auto flex items-center justify-center px-8 py-3.5 bg-gold-500 hover:bg-gold-400 text-slate-900 font-bold rounded-lg shadow-lg shadow-gold-600/20 transition-all duration-300 transform hover:scale-105">
                            <i class="fas fa-save mr-2"></i>
                            <?= translate('btn_save_test_smtp', 'Save & Test SMTP') ?>
                        </button>
                        <a href="<?php echo $base_path; ?>install/step4_realm" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 bg-slate-700/50 hover:bg-slate-700/70 text-slate-300 font-semibold rounded-lg transition-all duration-300 border border-slate-600/30">
                            <i class="fas fa-arrow-left mr-2"></i>
                            <?= translate('btn_go_back', 'Go Back') ?>
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.inc.php'; ?>
</body>
</html>