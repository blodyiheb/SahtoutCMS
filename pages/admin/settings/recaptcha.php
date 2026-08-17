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

$page_class = 'recaptcha';

$errors = [];
$success = false;
$configCapFile = realpath($project_root . 'includes/config.cap.php');
$default_site_key = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI';
$default_secret_key = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe';

// Load current reCAPTCHA settings
$recaptcha_status = 'disabled';
$recaptcha_site_key = '';
$recaptcha_secret_key = '';

if (file_exists($configCapFile)) {
    include $configCapFile;
    $recaptcha_status = defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED ? 'enabled' : 'disabled';
    if ($recaptcha_status === 'enabled') {
        $recaptcha_site_key = defined('RECAPTCHA_SITE_KEY') ? RECAPTCHA_SITE_KEY : '';
        $recaptcha_secret_key = defined('RECAPTCHA_SECRET_KEY') ? RECAPTCHA_SECRET_KEY : '';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $captcha_type = trim($_POST['captcha_type'] ?? 'recaptcha');
    $recaptcha_enabled = isset($_POST['recaptcha_enabled']) ? 1 : 0;
    $recaptcha_site_key = $recaptcha_enabled ? trim($_POST['recaptcha_site_key'] ?? '') : '';
    $recaptcha_secret_key = $recaptcha_enabled ? trim($_POST['recaptcha_secret_key'] ?? '') : '';

    if ($recaptcha_enabled && empty($recaptcha_site_key)) {
        $recaptcha_site_key = $default_site_key;
    }
    if ($recaptcha_enabled && empty($recaptcha_secret_key)) {
        $recaptcha_secret_key = $default_secret_key;
    }

    if ($captcha_type !== 'recaptcha') {
        $errors[] = translate('err_invalid_captcha_type', 'Invalid CAPTCHA type selected. Only reCAPTCHA is supported.');
    }
    if ($recaptcha_enabled && (empty($recaptcha_site_key) || empty($recaptcha_secret_key))) {
        $errors[] = translate('err_recaptcha_keys_required', 'reCAPTCHA Site Key and Secret Key are required when reCAPTCHA is enabled.');
    }

    if (empty($errors)) {
        $capConfigContent = "<?php
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit(translate('error_direct_access', 'Direct access not allowed.'));
}
\$captcha_type = '" . addslashes($captcha_type) . "';
\$recaptcha_enabled = " . ($recaptcha_enabled ? 'true' : 'false') . ";
\$recaptcha_site_key = '" . addslashes($recaptcha_site_key) . "';
\$recaptcha_secret_key = '" . addslashes($recaptcha_secret_key) . "';
define('CAPTCHA_TYPE', \$captcha_type);
define('RECAPTCHA_ENABLED', \$recaptcha_enabled);
define('RECAPTCHA_SITE_KEY', \$recaptcha_site_key);
define('RECAPTCHA_SECRET_KEY', \$recaptcha_secret_key);
?>";

        $capConfigDir = dirname($configCapFile);
        if (!is_writable($capConfigDir)) {
            $errors[] = sprintf(translate('err_cap_dir_not_writable', 'reCAPTCHA config directory is not writable: %s'), $capConfigDir);
        } elseif (file_put_contents($configCapFile, $capConfigContent) === false) {
            $errors[] = sprintf(translate('err_failed_write_cap', 'Failed to write reCAPTCHA config file: %s'), $configCapFile);
        } else {
            $success = true;
            $recaptcha_status = $recaptcha_enabled ? 'enabled' : 'disabled';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo translate('page_description_recaptcha', 'reCAPTCHA Settings for Sahtout WoW Server'); ?>">
    <meta name="robots" content="noindex">
    <title><?php echo translate('page_title_recaptcha', 'reCAPTCHA Settings'); ?></title>
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
        
        /* reCAPTCHA fields toggle */
        .recaptcha-fields {
            display: none;
        }
        .recaptcha-fields.active {
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
                        <?php echo translate('page_title_recaptcha', 'reCAPTCHA Settings'); ?>
                    </h1>

                    <!-- Settings Navbar -->
                    <?php include $project_root . 'pages/admin/settings/settings_navbar.php'; ?>

                    <!-- Status Badge -->
                    <div class="flex items-center gap-3">
                        <span class="text-gray-400 text-sm font-semibold"><?php echo translate('status', 'Status:'); ?></span>
                        <span class="status-badge <?php echo $recaptcha_status === 'enabled' ? 'enabled' : 'disabled'; ?> 
                                     inline-flex items-center gap-2 px-4 py-1.5 font-bold text-sm border rounded-sm">
                            <i class="fas <?php echo $recaptcha_status === 'enabled' ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                            <?php echo translate(
                                $recaptcha_status === 'enabled' ? 'msg_recaptcha_enabled' : 'msg_recaptcha_disabled',
                                $recaptcha_status === 'enabled' ? 'reCAPTCHA Enabled' : 'reCAPTCHA Disabled'
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
                            <span><?php echo translate('msg_recaptcha_saved', 'reCAPTCHA settings saved successfully!'); ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- reCAPTCHA Settings Form -->
                    <div class="relative bg-gradient-to-b from-[#161920]/92 to-[#080a0e]/90 
                                border border-[#c9a227]/[0.22] 
                                shadow-[0_12px_32px_rgba(0,0,0,.55),inset_0_0_60px_rgba(0,0,0,.45)]
                                p-4 md:p-6 lg:p-8 panel-gold-corners">
                        
                        <h2 class="section-title text-lg md:text-xl mb-4 md:mb-6 flex items-center gap-3 
                                   text-[#f2cf5b] font-bold drop-shadow-[0_0_12px_rgba(201,162,39,.35),0_2px_4px_rgba(0,0,0,.8)]">
                            <i class="fas fa-shield-alt text-[#f2cf5b]"></i>
                            <?php echo translate('settings_recaptcha', 'reCAPTCHA Settings'); ?>
                        </h2>

                        <form method="POST" class="space-y-4 md:space-y-6 max-w-3xl mx-auto">
                            <!-- CAPTCHA Type -->
                            <div>
                                <label for="captcha_type" class="form-label text-[#f2cf5b] font-bold text-sm 
                                                                     tracking-wider block mb-2 
                                                                     drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                    <?php echo translate('label_captcha_type', 'CAPTCHA Type'); ?>
                                </label>
                                <select id="captcha_type" name="captcha_type" 
                                        class="w-full px-4 py-3 text-[0.95rem] text-[#e5e7eb] 
                                               bg-[#0a0e16]/80 border border-[#c9a227]/30 rounded-sm 
                                               focus:border-[#f2cf5b] focus:shadow-[0_0_10px_rgba(242,207,82,.2)] 
                                               focus:bg-[#0f141e]/90 outline-none transition-all duration-200 
                                               placeholder:text-[#96aac8]/40">
                                    <option value="recaptcha" <?php echo (($_POST['captcha_type'] ?? 'recaptcha') === 'recaptcha') ? 'selected' : ''; ?>><?php echo translate('option_recaptcha', 'reCAPTCHA'); ?></option>
                                    <option value="hcaptcha" disabled><?php echo translate('option_hcaptcha', 'hCaptcha (Coming Soon)'); ?></option>
                                    <option value="other" disabled><?php echo translate('option_other', 'Other (Coming Soon)'); ?></option>
                                </select>
                                <div class="text-[#6a7a8a] text-xs mt-1"><?php echo translate('help_captcha_type', 'Currently only reCAPTCHA v2 is supported.'); ?></div>
                            </div>

                            <!-- Enable reCAPTCHA Toggle -->
                            <div class="flex items-center gap-4 p-4 rounded-sm bg-[rgba(201,162,39,0.05)] border border-[rgba(201,162,39,0.1)]">
                                <label class="toggle-switch">
                                    <input type="checkbox" id="recaptcha_enabled" name="recaptcha_enabled" <?php echo isset($_POST['recaptcha_enabled']) || $recaptcha_status === 'enabled' ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                                <div>
                                    <label for="recaptcha_enabled" class="text-sm font-semibold text-[#f2cf5b] cursor-pointer flex items-center gap-2">
                                        <i class="fas fa-shield-alt"></i>
                                        <?php echo translate('label_recaptcha_enabled', 'Enable reCAPTCHA'); ?>
                                    </label>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo translate('help_recaptcha_enabled', 'Enable to protect forms from spam and bots.'); ?></p>
                                </div>
                            </div>

                            <!-- reCAPTCHA Fields -->
                            <div class="recaptcha-fields <?php echo (isset($_POST['recaptcha_enabled']) || $recaptcha_status === 'enabled') ? 'active' : ''; ?> space-y-4">
                                <div>
                                    <label for="recaptcha_site_key" class="form-label text-[#f2cf5b] font-bold text-sm 
                                                                             tracking-wider block mb-2 
                                                                             drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                        <?php echo translate('label_recaptcha_site_key', 'Site Key'); ?>
                                    </label>
                                    <input type="text" id="recaptcha_site_key" name="recaptcha_site_key" 
                                           class="w-full px-4 py-3 text-[0.95rem] text-[#e5e7eb] 
                                                  bg-[#0a0e16]/80 border border-[#c9a227]/30 rounded-sm 
                                                  focus:border-[#f2cf5b] focus:shadow-[0_0_10px_rgba(242,207,82,.2)] 
                                                  focus:bg-[#0f141e]/90 outline-none transition-all duration-200 
                                                  placeholder:text-[#96aac8]/40"
                                           placeholder="<?php echo translate('placeholder_recaptcha_default', 'Leave empty for default test keys'); ?>" 
                                           value="<?php echo htmlspecialchars($_POST['recaptcha_site_key'] ?? $recaptcha_site_key); ?>">
                                    <div class="text-[#6a7a8a] text-xs mt-1"><?php echo translate('help_recaptcha_site_key', 'Your reCAPTCHA site key from Google reCAPTCHA console.'); ?></div>
                                </div>

                                <div>
                                    <label for="recaptcha_secret_key" class="form-label text-[#f2cf5b] font-bold text-sm 
                                                                             tracking-wider block mb-2 
                                                                             drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                        <?php echo translate('label_recaptcha_secret_key', 'Secret Key'); ?>
                                    </label>
                                    <input type="text" id="recaptcha_secret_key" name="recaptcha_secret_key" 
                                           class="w-full px-4 py-3 text-[0.95rem] text-[#e5e7eb] 
                                                  bg-[#0a0e16]/80 border border-[#c9a227]/30 rounded-sm 
                                                  focus:border-[#f2cf5b] focus:shadow-[0_0_10px_rgba(242,207,82,.2)] 
                                                  focus:bg-[#0f141e]/90 outline-none transition-all duration-200 
                                                  placeholder:text-[#96aac8]/40"
                                           placeholder="<?php echo translate('placeholder_recaptcha_default', 'Leave empty for default test keys'); ?>" 
                                           value="<?php echo htmlspecialchars($_POST['recaptcha_secret_key'] ?? $recaptcha_secret_key); ?>">
                                    <div class="text-[#6a7a8a] text-xs mt-1"><?php echo translate('help_recaptcha_secret_key', 'Your reCAPTCHA secret key from Google reCAPTCHA console.'); ?></div>
                                </div>

                                <div class="text-[#6a7a8a] text-xs p-3 bg-[#0a0e16]/50 border border-[rgba(201,162,39,0.1)] rounded-sm">
                                    <i class="fas fa-info-circle text-[#f2cf5b] mr-2"></i>
                                    <?php echo translate('note_recaptcha_empty', 'Leave reCAPTCHA fields empty to use default test keys when enabled. (These work for testing but should be replaced in production.)'); ?>
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
                                    <?php echo translate('btn_save_recaptcha', 'Save reCAPTCHA Settings'); ?>
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
            const toggle = document.getElementById('recaptcha_enabled');
            const fields = document.querySelector('.recaptcha-fields');

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