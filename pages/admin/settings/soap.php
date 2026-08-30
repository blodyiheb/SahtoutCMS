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
        
        /* Status badge */
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
        
        /* Info box toggle - Tailwind can't handle max-height transitions easily */
        .info-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease, padding 0.3s ease;
        }
        .info-content.open {
            max-height: 300px;
            padding: 0 1.25rem 1.25rem;
        }
        .info-title .fa-chevron-down {
            transition: transform 0.3s ease;
        }
        .info-title.open .fa-chevron-down {
            transform: rotate(180deg);
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
                        <?php echo translate('title_soap_settings', 'SOAP Settings'); ?>
                    </h1>

                    <!-- Settings Navbar -->
                    <?php include $project_root . 'pages/admin/settings/settings_navbar.php'; ?>

                    <!-- Status Badge -->
                    <div class="flex items-center gap-3">
                        <span class="text-gray-400 text-sm font-semibold"><?php echo translate('status', 'Status:'); ?></span>
                        <span class="status-badge <?php echo $soap_status === 'configured' ? 'configured' : 'not-configured'; ?> 
                                     inline-flex items-center gap-2 px-4 py-1.5 font-bold text-sm border rounded-sm">
                            <i class="fas <?php echo $soap_status === 'configured' ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                            <?php echo $soap_status === 'configured' 
                                ? translate('status_soap_configured', 'SOAP Configured') 
                                : translate('status_soap_not_configured', 'SOAP Not Configured'); ?>
                        </span>
                    </div>

                    <!-- Success / Error Messages -->
                    <?php if (!empty($errors)): ?>
                        <div class="bg-[#e74c3c]/15 border border-[#e74c3c]/40 text-[#e74c3c] 
                                    p-4 rounded-sm flex items-start gap-3">
                            <i class="fas fa-exclamation-circle text-xl mt-0.5"></i>
                            <div>
                                <strong><?php echo translate('error_box_title', 'Please fix the following errors:'); ?></strong>
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
                            <span><?php echo translate('success_soap_settings_saved', 'SOAP settings saved successfully!'); ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- SOAP Settings Form -->
                    <div class="relative bg-gradient-to-b from-[#161920]/92 to-[#080a0e]/90 
                                border border-[#c9a227]/[0.22] 
                                shadow-[0_12px_32px_rgba(0,0,0,.55),inset_0_0_60px_rgba(0,0,0,.45)]
                                p-4 md:p-6 lg:p-8 panel-gold-corners">
                        
                        <h2 class="section-title text-lg md:text-xl mb-4 md:mb-6 flex items-center gap-3 
                                   text-[#f2cf5b] font-bold drop-shadow-[0_0_12px_rgba(201,162,39,.35),0_2px_4px_rgba(0,0,0,.8)]">
                            <i class="fas fa-code text-[#f2cf5b]"></i>
                            <?php echo translate('header_soap_settings', 'SOAP Settings'); ?>
                        </h2>

                        <form method="POST" class="space-y-4 md:space-y-6 max-w-3xl mx-auto">
                            <div>
                                <label for="soap_url" class="form-label text-[#f2cf5b] font-bold text-sm 
                                                             tracking-wider block mb-2 
                                                             drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                    <?php echo translate('label_soap_url', 'SOAP URL'); ?>
                                </label>
                                <input type="text" id="soap_url" name="soap_url" 
                                       class="w-full px-4 py-3 text-[0.95rem] text-[#e5e7eb] 
                                              bg-[#0a0e16]/80 border border-[#c9a227]/30 rounded-sm 
                                              focus:border-[#f2cf5b] focus:shadow-[0_0_10px_rgba(242,207,82,.2)] 
                                              focus:bg-[#0f141e]/90 outline-none transition-all duration-200 
                                              placeholder:text-[#96aac8]/40"
                                       placeholder="<?php echo translate('placeholder_soap_url', 'e.g., http://127.0.0.1:7878'); ?>" 
                                       value="<?php echo htmlspecialchars($soapUrl); ?>" 
                                       required>
                                <div class="text-[#6a7a8a] text-xs mt-1"><?php echo translate('help_soap_url', 'The URL where your WoW server\'s SOAP service is running.'); ?></div>
                            </div>

                            <div>
                                <label for="soap_user" class="form-label text-[#f2cf5b] font-bold text-sm 
                                                             tracking-wider block mb-2 
                                                             drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                    <?php echo translate('label_soap_user', 'GM Account Username'); ?>
                                </label>
                                <input type="text" id="soap_user" name="soap_user" maxlength="17" 
                                       class="w-full px-4 py-3 text-[0.95rem] text-[#e5e7eb] 
                                              bg-[#0a0e16]/80 border border-[#c9a227]/30 rounded-sm 
                                              focus:border-[#f2cf5b] focus:shadow-[0_0_10px_rgba(242,207,82,.2)] 
                                              focus:bg-[#0f141e]/90 outline-none transition-all duration-200 
                                              placeholder:text-[#96aac8]/40"
                                       placeholder="<?php echo translate('placeholder_soap_user', 'Must be GM level 3'); ?>" 
                                       value="<?php echo htmlspecialchars($soapUser); ?>" 
                                       required>
                                <div class="text-[#6a7a8a] text-xs mt-1"><?php echo translate('help_soap_user', 'The account must have GM level 3 in the database.'); ?></div>
                            </div>

                            <div>
                                <label for="soap_pass" class="form-label text-[#f2cf5b] font-bold text-sm 
                                                             tracking-wider block mb-2 
                                                             drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                    <?php echo translate('label_soap_pass', 'SOAP Password'); ?>
                                </label>
                                <input type="password" id="soap_pass" name="soap_pass" maxlength="16" 
                                       class="w-full px-4 py-3 text-[0.95rem] text-[#e5e7eb] 
                                              bg-[#0a0e16]/80 border border-[#c9a227]/30 rounded-sm 
                                              focus:border-[#f2cf5b] focus:shadow-[0_0_10px_rgba(242,207,82,.2)] 
                                              focus:bg-[#0f141e]/90 outline-none transition-all duration-200 
                                              placeholder:text-[#96aac8]/40"
                                       placeholder="<?php echo translate('placeholder_soap_pass', 'SOAP password = Account password'); ?>" 
                                       value="<?php echo htmlspecialchars($soapPass); ?>" 
                                       required>
                                <div class="text-[#6a7a8a] text-xs mt-1"><?php echo translate('help_soap_pass', 'This is the password for the GM account above.'); ?></div>
                            </div>

                            <!-- Save Button -->
                            <div class="pt-4 border-t border-[rgba(201,162,39,.1)] flex justify-end">
                                <button type="submit" class="btn-clip inline-flex items-center gap-2 px-6 py-3 
                                                             font-extrabold text-xs uppercase tracking-wider
                                                             bg-gradient-to-b from-[#f6d478] via-[#c9a227] to-[#8a6a14] 
                                                             text-[#1a1200] shadow-[inset_0_0_0_1px_rgba(255,255,255,.28),inset_0_-8px_14px_rgba(0,0,0,.25)]
                                                             hover:scale-105 transition-transform duration-200">
                                    <i class="fas fa-save"></i>
                                    <?php echo translate('button_save_verify_soap', 'Save & Verify SOAP'); ?>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Information Box -->
                    <div class="bg-[rgba(201,162,39,0.05)] border border-[rgba(201,162,39,0.15)] rounded-sm overflow-hidden">
                        <div class="info-title px-5 py-3 text-[#f2cf5b] font-bold cursor-pointer 
                                    flex items-center justify-between font-['Cinzel'] text-sm 
                                    hover:bg-[rgba(201,162,39,0.05)] transition-all duration-300"
                             onclick="toggleInfo(this)">
                            <span><i class="fas fa-info-circle text-[#f2cf5b] mr-2"></i><?php echo translate('info_box_title', 'Important Steps'); ?></span>
                            <i class="fas fa-chevron-down text-[#f2cf5b]"></i>
                        </div>
                        <div class="info-content px-5">
                            <ul class="list-none p-0 m-0">
                                <li class="py-1.5 text-[#b8c8ff] text-sm border-b border-[rgba(201,162,39,0.05)] last:border-b-0">
                                    <?php echo translate('info_step_1', 'Make sure the GM account exists in your Auth DB and has GM level 3 in <code>account_access</code> with <code>RealmID = -1</code>.'); ?>
                                </li>
                                <li class="py-1.5 text-[#b8c8ff] text-sm border-b border-[rgba(201,162,39,0.05)] last:border-b-0">
                                    <?php echo translate('info_step_2', 'Open your <code>worldserver.conf</code> file and set: <strong>SOAP.Enabled = 1</strong>'); ?>
                                </li>
                                <li class="py-1.5 text-[#b8c8ff] text-sm border-b border-[rgba(201,162,39,0.05)] last:border-b-0">
                                    <?php echo translate('info_step_3', 'Ensure the SOAP port in <code>soap_url</code> is correct and accessible.'); ?>
                                </li>
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
            const icon = element.querySelector('.fa-chevron-down');
            
            if (content) {
                content.classList.toggle('open');
                element.classList.toggle('open');
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