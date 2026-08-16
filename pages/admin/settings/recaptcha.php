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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&family=Cinzel:wght@600;700;900&display=swap" rel="stylesheet">
    
    <style>
        * { font-family: 'Inter', sans-serif; }

        body {
            min-height: 100vh;
            color: #d8d8d8;
            background: #05070b;
            background-image:
                radial-gradient(1000px 700px at -10% 35%, rgba(59,130,246,.14), transparent 65%),
                radial-gradient(800px 600px at -5% 85%, rgba(124,58,237,.10), transparent 70%),
                linear-gradient(180deg, #0a0e16 0%, #060810 45%, #03040a 100%);
            background-attachment: fixed;
        }

        .panel {
            position: relative;
            background: linear-gradient(180deg, rgba(22,25,32,.92), rgba(8,10,14,.9));
            border: 1px solid rgba(201,162,39,.22);
            box-shadow: 0 12px 32px rgba(0,0,0,.55), inset 0 0 60px rgba(0,0,0,.45);
        }

        .panel::before {
            content: '';
            position: absolute;
            inset: 5px;
            border: 1px solid rgba(201,162,39,.14);
            pointer-events: none;
        }

        .panel::after {
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
            background: linear-gradient(180deg, #fff7d6 0%, #f2cf5b 35%, #c9a227 62%, #8a6a14 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 3px 6px rgba(0,0,0,.85));
        }

        .section-title {
            font-family: 'Cinzel', serif;
            font-weight: 700;
            color: #f2cf5b;
            text-shadow: 0 0 12px rgba(201,162,39,.35), 0 2px 4px rgba(0,0,0,.8);
        }

        .btn-gold {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            padding: .75rem 1.5rem;
            font-weight: 800;
            font-size: .85rem;
            letter-spacing: .04em;
            text-transform: uppercase;
            clip-path: polygon(10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%, 0 10px);
            transition: transform .2s ease;
            background: linear-gradient(180deg, #f6d478 0%, #c9a227 48%, #8a6a14 100%);
            color: #1a1200;
            text-shadow: 0 1px 0 rgba(255,255,255,.35);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.28), inset 0 -8px 14px rgba(0,0,0,.25);
            border: none;
            cursor: pointer;
        }
        .btn-gold:hover { transform: translateY(-2px) scale(1.02); }

        .btn-iron {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            padding: .75rem 1.5rem;
            font-weight: 800;
            font-size: .85rem;
            letter-spacing: .04em;
            text-transform: uppercase;
            clip-path: polygon(10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%, 0 10px);
            transition: transform .2s ease;
            background: linear-gradient(180deg, #3a404d 0%, #232833 55%, #141821 100%);
            color: #cfe1ff;
            box-shadow: inset 0 0 0 1px rgba(120,160,255,.25), inset 0 -8px 14px rgba(0,0,0,.4);
            border: none;
            cursor: pointer;
        }
        .btn-iron:hover { transform: translateY(-2px) scale(1.02); }

        .input-dark {
            background: rgba(10, 14, 22, 0.8);
            border: 1px solid rgba(201,162,39,.3);
            color: #e5e7eb;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            outline: none;
            width: 100%;
        }
        .input-dark:focus {
            border-color: #f2cf5b;
            box-shadow: 0 0 10px rgba(242,207,82,.2);
            background: rgba(15, 20, 30, 0.9);
        }
        .input-dark::placeholder { color: rgba(150, 170, 200, 0.4); }
        .input-dark option { background: #0a0e16; }
        .input-dark:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

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

        .recaptcha-fields {
            display: none;
        }
        .recaptcha-fields.active {
            display: block;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1rem;
            font-weight: 700;
            font-size: 0.85rem;
            border-radius: 3px;
            border: 1px solid transparent;
        }
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

        .alert-success-wow {
            background: rgba(46, 204, 113, 0.15);
            border: 1px solid rgba(46, 204, 113, 0.4);
            color: #2ecc71;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .alert-success-wow i {
            font-size: 1.2rem;
        }

        .alert-danger-wow {
            background: rgba(231, 76, 60, 0.15);
            border: 1px solid rgba(231, 76, 60, 0.4);
            color: #e74c3c;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .alert-danger-wow i {
            font-size: 1.2rem;
        }

        .form-label {
            font-family: 'Cinzel', serif;
            font-weight: 700;
            color: #f2cf5b;
            text-shadow: 0 0 12px rgba(201,162,39,.15), 0 2px 4px rgba(0,0,0,.8);
            font-size: 0.9rem;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-text {
            color: #6a7a8a;
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }

        .note-text {
            color: #6a7a8a;
            font-size: 0.8rem;
            padding: 0.5rem 0.75rem;
            background: rgba(10, 14, 22, 0.5);
            border: 1px solid rgba(201,162,39,0.1);
            border-radius: 3px;
        }

        /* Main content area - works with sidebar */
        .main-content-area {
            transition: margin-left 0.3s ease;
            min-height: calc(100vh - 72px);
            width: 100%;
        }

        .content-wrapper {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        @media (min-width: 640px) {
            .content-wrapper {
                padding: 0 1.5rem;
            }
        }

        @media (min-width: 1024px) {
            .content-wrapper {
                padding: 0 2rem;
            }
        }

        @media (min-width: 1280px) {
            .content-wrapper {
                padding: 0 2.5rem;
            }
        }

        @media (min-width: 1024px) {
            .main-content-area.lg\:ml-0 {
                margin-left: 0;
            }
            .main-content-area.lg\:ml-\[280px\] {
                margin-left: 280px;
            }
        }

        @media (max-width: 1023px) {
            .main-content-area {
                margin-left: 0 !important;
                padding: 1rem;
            }
            .content-wrapper {
                padding: 0 0.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include $project_root . 'includes/header.php'; ?>

    <div class="flex relative min-h-screen">
        
        <!-- Sidebar -->
        <?php include $project_root . 'includes/admin_sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content-area flex-1 p-3 sm:p-4 md:p-6 lg:p-8 transition-all duration-300 lg:ml-[280px]">
            <div class="content-wrapper">
                <div class="space-y-4 md:space-y-6 lg:space-y-8">
                    
                    <h1 class="wow-title text-2xl md:text-3xl lg:text-4xl"><?php echo translate('page_title_recaptcha', 'reCAPTCHA Settings'); ?></h1>

                    <!-- Settings Navbar -->
                    <?php include $project_root . 'pages/admin/settings/settings_navbar.php'; ?>

                    <!-- Status Badge -->
                    <div class="flex items-center gap-3">
                        <span class="text-gray-400 text-sm font-semibold"><?php echo translate('status', 'Status:'); ?></span>
                        <span class="status-badge <?php echo $recaptcha_status === 'enabled' ? 'enabled' : 'disabled'; ?>">
                            <i class="fas <?php echo $recaptcha_status === 'enabled' ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                            <?php echo translate(
                                $recaptcha_status === 'enabled' ? 'msg_recaptcha_enabled' : 'msg_recaptcha_disabled',
                                $recaptcha_status === 'enabled' ? 'reCAPTCHA Enabled' : 'reCAPTCHA Disabled'
                            ); ?>
                        </span>
                    </div>

                    <!-- Success / Error Messages -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert-danger-wow rounded-sm">
                            <i class="fas fa-exclamation-circle"></i>
                            <div>
                                <strong><?php echo translate('err_fix_errors', 'Please fix the following errors:'); ?></strong>
                                <?php foreach ($errors as $err): ?>
                                    <div class="text-sm mt-1">• <?php echo htmlspecialchars($err); ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert-success-wow rounded-sm">
                            <i class="fas fa-check-circle"></i>
                            <span><?php echo translate('msg_recaptcha_saved', 'reCAPTCHA settings saved successfully!'); ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- reCAPTCHA Settings Form -->
                    <div class="panel p-4 md:p-6 lg:p-8">
                        <h2 class="section-title text-lg md:text-xl mb-4 md:mb-6 flex items-center gap-3">
                            <i class="fas fa-shield-alt text-[#f2cf5b]"></i>
                            <?php echo translate('settings_recaptcha', 'reCAPTCHA Settings'); ?>
                        </h2>

                        <form method="POST" class="space-y-4 md:space-y-6 max-w-3xl mx-auto">
                            <!-- CAPTCHA Type -->
                            <div>
                                <label for="captcha_type" class="form-label"><?php echo translate('label_captcha_type', 'CAPTCHA Type'); ?></label>
                                <select id="captcha_type" name="captcha_type" class="input-dark rounded-sm">
                                    <option value="recaptcha" <?php echo (($_POST['captcha_type'] ?? 'recaptcha') === 'recaptcha') ? 'selected' : ''; ?>><?php echo translate('option_recaptcha', 'reCAPTCHA'); ?></option>
                                    <option value="hcaptcha" disabled><?php echo translate('option_hcaptcha', 'hCaptcha (Coming Soon)'); ?></option>
                                    <option value="other" disabled><?php echo translate('option_other', 'Other (Coming Soon)'); ?></option>
                                </select>
                                <div class="form-text"><?php echo translate('help_captcha_type', 'Currently only reCAPTCHA v2 is supported.'); ?></div>
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
                                    <label for="recaptcha_site_key" class="form-label"><?php echo translate('label_recaptcha_site_key', 'Site Key'); ?></label>
                                    <input type="text" id="recaptcha_site_key" name="recaptcha_site_key" class="input-dark rounded-sm" placeholder="<?php echo translate('placeholder_recaptcha_default', 'Leave empty for default test keys'); ?>" value="<?php echo htmlspecialchars($_POST['recaptcha_site_key'] ?? $recaptcha_site_key); ?>">
                                    <div class="form-text"><?php echo translate('help_recaptcha_site_key', 'Your reCAPTCHA site key from Google reCAPTCHA console.'); ?></div>
                                </div>

                                <div>
                                    <label for="recaptcha_secret_key" class="form-label"><?php echo translate('label_recaptcha_secret_key', 'Secret Key'); ?></label>
                                    <input type="text" id="recaptcha_secret_key" name="recaptcha_secret_key" class="input-dark rounded-sm" placeholder="<?php echo translate('placeholder_recaptcha_default', 'Leave empty for default test keys'); ?>" value="<?php echo htmlspecialchars($_POST['recaptcha_secret_key'] ?? $recaptcha_secret_key); ?>">
                                    <div class="form-text"><?php echo translate('help_recaptcha_secret_key', 'Your reCAPTCHA secret key from Google reCAPTCHA console.'); ?></div>
                                </div>

                                <div class="note-text">
                                    <i class="fas fa-info-circle text-[#f2cf5b] mr-2"></i>
                                    <?php echo translate('note_recaptcha_empty', 'Leave reCAPTCHA fields empty to use default test keys when enabled. (These work for testing but should be replaced in production.)'); ?>
                                </div>
                            </div>

                            <!-- Save Button -->
                            <div class="pt-4 border-t border-[rgba(201,162,39,.1)] flex justify-end">
                                <button type="submit" class="btn-gold">
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