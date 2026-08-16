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

$page_class = 'soap';

$errors = [];
$success = false;
$soapConfigFile = realpath($project_root . 'includes/soap.conf.php');

// Load current SOAP status
$soap_status = 'not_configured';
$soapUrl = 'http://127.0.0.1:7878';
$soapUser = '';
$soapPass = '';

if (file_exists($soapConfigFile)) {
    include $soapConfigFile;
    if (!empty($soap_url) && !empty($soap_user) && !empty($soap_pass)) {
        $soap_status = 'configured';
        $soapUrl = $soap_url;
        $soapUser = $soap_user;
        $soapPass = '';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $soapUrl = trim($_POST['soap_url'] ?? 'http://127.0.0.1:7878');
    $soapUser = trim($_POST['soap_user'] ?? '');
    $soapPass = trim($_POST['soap_pass'] ?? '');

    if (empty($soapUrl)) {
        $errors[] = translate('error_soap_url_required', 'SOAP URL is required.');
    }
    if (empty($soapUser)) {
        $errors[] = translate('error_soap_user_required', 'GM Account Username is required.');
    }
    if (empty($soapPass)) {
        $errors[] = translate('error_soap_pass_required', 'SOAP Password is required.');
    }

    // Validate GM account
    if (empty($errors)) {
        $stmt = $auth_db->prepare("SELECT id FROM account WHERE username = ?");
        if (!$stmt) {
            $errors[] = sprintf(translate('error_db_query', 'Database query error: %s'), $auth_db->error);
        } else {
            $stmt->bind_param('s', $soapUser);
            $stmt->execute();
            $stmt->bind_result($accountId);
            $stmt->fetch();
            $stmt->close();

            if (!$accountId) {
                $errors[] = sprintf(translate('error_account_not_exist', 'Account %s does not exist in Auth DB.'), $soapUser);
            } else {
                $stmt2 = $auth_db->prepare("SELECT gmlevel FROM account_access WHERE id = ? AND RealmID = -1");
                if (!$stmt2) {
                    $errors[] = sprintf(translate('error_db_query', 'Database query error: %s'), $auth_db->error);
                } else {
                    $stmt2->bind_param('i', $accountId);
                    $stmt2->execute();
                    $stmt2->bind_result($gmLevel);
                    $stmt2->fetch();
                    $stmt2->close();

                    if (!$gmLevel || $gmLevel < 3) {
                        $errors[] = sprintf(translate('error_account_not_gm_level_3', 'Account %s exists but is not GM level 3.'), $soapUser);
                    }
                }
            }
        }
    }

    // Save settings if no errors
    if (empty($errors)) {
        $configContent = "<?php
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access to this file is not allowed.');
}

\$soap_url  = '" . addslashes($soapUrl) . "';
\$soap_user = '" . addslashes($soapUser) . "';
\$soap_pass = '" . addslashes($soapPass) . "';
?>";

        $configDir = dirname($soapConfigFile);
        if (!is_writable($configDir)) {
            $errors[] = sprintf(translate('error_config_dir_not_writable', 'Config directory is not writable: %s'), $configDir);
        } elseif (file_put_contents($soapConfigFile, $configContent) === false) {
            $errors[] = sprintf(translate('error_config_file_write_failed', 'Failed to write config file: %s'), $soapConfigFile);
        } else {
            $success = true;
            $soap_status = 'configured';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo translate('page_description_soap', 'SOAP Settings for Sahtout WoW Server'); ?>">
    <meta name="robots" content="noindex">
    <title><?php echo translate('title_soap_settings', 'SOAP Settings'); ?></title>
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
        .status-badge.configured {
            background: rgba(46, 204, 113, 0.15);
            border-color: rgba(46, 204, 113, 0.4);
            color: #2ecc71;
        }
        .status-badge.not-configured {
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

        .info-box {
            background: rgba(201,162,39,0.05);
            border: 1px solid rgba(201,162,39,0.15);
            border-radius: 4px;
            overflow: hidden;
        }
        .info-box .info-title {
            padding: 0.75rem 1.25rem;
            color: #f2cf5b;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-family: 'Cinzel', serif;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        .info-box .info-title:hover {
            background: rgba(201,162,39,0.05);
        }
        .info-box .info-title i {
            transition: transform 0.3s ease;
        }
        .info-box .info-title.open i {
            transform: rotate(180deg);
        }
        .info-box .info-content {
            padding: 0 1.25rem;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease, padding 0.3s ease;
        }
        .info-box .info-content.open {
            max-height: 300px;
            padding: 0 1.25rem 1.25rem;
        }
        .info-box .info-content ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .info-box .info-content ul li {
            padding: 0.4rem 0;
            color: #b8c8ff;
            font-size: 0.9rem;
            border-bottom: 1px solid rgba(201,162,39,0.05);
        }
        .info-box .info-content ul li:last-child {
            border-bottom: none;
        }
        .info-box .info-content ul li code {
            color: #f2cf5b;
            background: rgba(10, 14, 22, 0.5);
            padding: 0.1rem 0.4rem;
            border-radius: 2px;
            font-size: 0.85rem;
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
                    
                    <h1 class="wow-title text-2xl md:text-3xl lg:text-4xl"><?php echo translate('title_soap_settings', 'SOAP Settings'); ?></h1>

                    <!-- Settings Navbar -->
                    <?php include $project_root . 'pages/admin/settings/settings_navbar.php'; ?>

                    <!-- Status Badge -->
                    <div class="flex items-center gap-3">
                        <span class="text-gray-400 text-sm font-semibold"><?php echo translate('status', 'Status:'); ?></span>
                        <span class="status-badge <?php echo $soap_status === 'configured' ? 'configured' : 'not-configured'; ?>">
                            <i class="fas <?php echo $soap_status === 'configured' ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                            <?php echo $soap_status === 'configured' 
                                ? translate('status_soap_configured', 'SOAP Configured') 
                                : translate('status_soap_not_configured', 'SOAP Not Configured'); ?>
                        </span>
                    </div>

                    <!-- Success / Error Messages -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert-danger-wow rounded-sm">
                            <i class="fas fa-exclamation-circle"></i>
                            <div>
                                <strong><?php echo translate('error_box_title', 'Please fix the following errors:'); ?></strong>
                                <?php foreach ($errors as $err): ?>
                                    <div class="text-sm mt-1">• <?php echo htmlspecialchars($err); ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert-success-wow rounded-sm">
                            <i class="fas fa-check-circle"></i>
                            <span><?php echo translate('success_soap_settings_saved', 'SOAP settings saved successfully!'); ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- SOAP Settings Form -->
                    <div class="panel p-4 md:p-6 lg:p-8">
                        <h2 class="section-title text-lg md:text-xl mb-4 md:mb-6 flex items-center gap-3">
                            <i class="fas fa-code text-[#f2cf5b]"></i>
                            <?php echo translate('header_soap_settings', 'SOAP Settings'); ?>
                        </h2>

                        <form method="POST" class="space-y-4 md:space-y-6 max-w-3xl mx-auto">
                            <div>
                                <label for="soap_url" class="form-label"><?php echo translate('label_soap_url', 'SOAP URL'); ?></label>
                                <input type="text" id="soap_url" name="soap_url" class="input-dark rounded-sm" placeholder="<?php echo translate('placeholder_soap_url', 'e.g., http://127.0.0.1:7878'); ?>" value="<?php echo htmlspecialchars($soapUrl); ?>" required>
                                <div class="form-text"><?php echo translate('help_soap_url', 'The URL where your WoW server\'s SOAP service is running.'); ?></div>
                            </div>

                            <div>
                                <label for="soap_user" class="form-label"><?php echo translate('label_soap_user', 'GM Account Username'); ?></label>
                                <input type="text" id="soap_user" name="soap_user" class="input-dark rounded-sm" placeholder="<?php echo translate('placeholder_soap_user', 'Must be GM level 3'); ?>" value="<?php echo htmlspecialchars($soapUser); ?>" required>
                                <div class="form-text"><?php echo translate('help_soap_user', 'The account must have GM level 3 in the database.'); ?></div>
                            </div>

                            <div>
                                <label for="soap_pass" class="form-label"><?php echo translate('label_soap_pass', 'SOAP Password'); ?></label>
                                <input type="password" id="soap_pass" name="soap_pass" class="input-dark rounded-sm" placeholder="<?php echo translate('placeholder_soap_pass', 'SOAP password = Account password'); ?>" value="<?php echo htmlspecialchars($soapPass); ?>" required>
                                <div class="form-text"><?php echo translate('help_soap_pass', 'This is the password for the GM account above.'); ?></div>
                            </div>

                            <!-- Save Button -->
                            <div class="pt-4 border-t border-[rgba(201,162,39,.1)] flex justify-end">
                                <button type="submit" class="btn-gold">
                                    <i class="fas fa-save"></i>
                                    <?php echo translate('button_save_verify_soap', 'Save & Verify SOAP'); ?>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Information Box -->
                    <div class="info-box">
                        <div class="info-title" onclick="toggleInfo(this)">
                            <span><i class="fas fa-info-circle text-[#f2cf5b] mr-2"></i><?php echo translate('info_box_title', 'Important Steps'); ?></span>
                            <i class="fas fa-chevron-down text-[#f2cf5b]"></i>
                        </div>
                        <div class="info-content">
                            <ul>
                                <li><?php echo translate('info_step_1', 'Make sure the GM account exists in your Auth DB and has GM level 3 in <code>account_access</code> with <code>RealmID = -1</code>.'); ?></li>
                                <li><?php echo translate('info_step_2', 'Open your <code>worldserver.conf</code> file and set: <strong>SOAP.Enabled = 1</strong>'); ?></li>
                                <li><?php echo translate('info_step_3', 'Ensure the SOAP port in <code>soap_url</code> is correct and accessible.'); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleInfo(element) {
            const content = element.parentElement.querySelector('.info-content');
            const icon = element.querySelector('i.fa-chevron-down');
            
            if (content) {
                content.classList.toggle('open');
                element.classList.toggle('open');
                if (icon) {
                    icon.style.transform = content.classList.contains('open') ? 'rotate(180deg)' : 'rotate(0deg)';
                }
            }
        }

        // Auto-expand on page load if there are errors
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($errors)): ?>
                const infoTitle = document.querySelector('.info-title');
                if (infoTitle) {
                    toggleInfo(infoTitle);
                }
            <?php endif; ?>
        });
    </script>
</body>
</html>