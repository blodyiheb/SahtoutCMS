<?php
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../includes/paths.php';
include __DIR__ . '/header.inc.php';

// Set current step for progress stepper
$current_step = 2;

// Required PHP extensions
$requiredExtensions = ["mysqli", "curl", "openssl", "soap", "gd", "gmp", "mbstring", "xml"];
$optionalExtensions = ["intl", "zip", "json"];
$requiredApacheModules = ["mod_rewrite", "mod_headers"];
$optionalApacheModules = ["mod_expires", "mod_deflate"];

function isApacheModuleEnabled($module) {
    if (function_exists('apache_get_modules')) return in_array($module, apache_get_modules());
    return null;
}

// Helper functions for UI badges
function getStatusBadge($status, $trueText = 'Enabled', $falseText = 'Missing', $nullText = 'N/A') {
    if ($status === true) {
        return '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20"><i class="fas fa-check-circle mr-1.5"></i> ' . $trueText . '</span>';
    } elseif ($status === false) {
        return '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-500/10 text-red-400 border border-red-500/20"><i class="fas fa-times-circle mr-1.5"></i> ' . $falseText . '</span>';
    } else {
        return '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20"><i class="fas fa-question-circle mr-1.5"></i> ' . $nullText . '</span>';
    }
}

function getVersionBadge($pass, $version) {
    if ($pass) {
        return '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20"><i class="fas fa-check-circle mr-1.5"></i> ' . htmlspecialchars($version) . '</span>';
    } else {
        return '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-500/10 text-red-400 border border-red-500/20"><i class="fas fa-times-circle mr-1.5"></i> ' . htmlspecialchars($version) . '</span>';
    }
}

// Checks
$phpVersionPass = version_compare(PHP_VERSION, '8.0.0', '>=');

$requiredExtResults = [];
foreach ($requiredExtensions as $ext) $requiredExtResults[$ext] = extension_loaded($ext);

$optionalExtResults = [];
foreach ($optionalExtensions as $ext) $optionalExtResults[$ext] = extension_loaded($ext);

$requiredApacheResults = [];
foreach ($requiredApacheModules as $mod) $requiredApacheResults[$mod] = isApacheModuleEnabled($mod);

$optionalApacheResults = [];
foreach ($optionalApacheModules as $mod) $optionalApacheResults[$mod] = isApacheModuleEnabled($mod);

$allRequiredPass = $phpVersionPass 
    && !in_array(false, $requiredExtResults, true) 
    && !in_array(false, $requiredApacheResults, true);

// Check if skip was requested via GET parameter
$skipPressed = isset($_GET['skip']) && $_GET['skip'] === 'yes';

// If skip was pressed, redirect to next step
if ($skipPressed) {
    header('Location: ' . ($base_path ?? '') . 'install/step3_db');
    exit;
}

// Configurable XAMPP path for help content (keeping for reference)
$xamppPath = 'C:\\xampp';

// Find missing items for warning message
$missingItems = [];
if (!$phpVersionPass) $missingItems[] = 'PHP 8.0+';
foreach ($requiredExtResults as $ext => $status) {
    if (!$status) $missingItems[] = "PHP extension: $ext";
}
foreach ($requiredApacheResults as $mod => $status) {
    if (!$status) $missingItems[] = "Apache module: $mod";
}
$missingCount = count($missingItems);

// Detect OS for help display
$isLinux = (PHP_OS_FAMILY === 'Linux');
$isWindows = (PHP_OS_FAMILY === 'Windows');
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($langCode ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= translate('installer_title', 'SahtoutCMS Installer') ?> - <?= translate('step2_title', 'Step 2: Environment Check') ?></title>
    
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
        
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #d97706; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #b45309; }

        /* Main content wrapper */
        .main-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding-top: 0px;
            padding-bottom: 80px;
        }

        .content-container {
            max-width: 1100px;
            width: 100%;
            margin: 0 auto;
            padding: 0 1rem;
        }

        @media (max-width: 768px) {
            .main-wrapper {
                padding-bottom: 70px;
            }
        }
        
        /* Code block styling */
        .code-block {
            background: #0a0e1a;
            border: 1px solid #1e293b;
            border-radius: 8px;
            padding: 12px 16px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            color: #e2e8f0;
            overflow-x: auto;
            margin: 8px 0;
        }
        .code-block .cmd { color: #60a5fa; }
        .code-block .output { color: #a78bfa; }
        .code-block .comment { color: #64748b; font-style: italic; }
        .code-block .highlight { color: #fbbf24; }
    </style>
</head>
<body class="text-slate-200">

<div class="main-wrapper">
    <!-- Progress Stepper -->
    <?php include __DIR__ . '/progress_stepper.inc.php'; ?>

    <div class="content-container flex-grow">
        <div class="bg-slate-900/60 backdrop-blur-xl border border-slate-700/50 rounded-2xl shadow-2xl p-6 md:p-10 relative overflow-hidden">
            
            <!-- Decorative Corner Elements -->
            <div class="absolute top-0 left-0 w-16 h-16 border-t-2 border-l-2 border-gold-500/30 rounded-tl-2xl pointer-events-none"></div>
            <div class="absolute bottom-0 right-0 w-16 h-16 border-b-2 border-r-2 border-gold-500/30 rounded-br-2xl pointer-events-none"></div>

            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gold-500/10 border border-gold-500/30 rounded-full mb-4">
                    <img 
                        src="logo.png" 
                        alt="Logo"
                        class="w-12 h-12 object-contain"
                    >
                </div>

                <h1 class="font-cinzel text-3xl md:text-4xl font-bold bg-gradient-to-b from-amber-100 to-gold-500 bg-clip-text text-transparent">
                    <?= translate('step2_title', 'Step 2: Environment Check') ?>
                </h1>

                <p class="text-slate-400 mt-2 text-sm">
                    <?= translate('step2_description', 'Verifying server compatibility and required extensions.') ?>
                </p>
            </div>

            <!-- Warning: Missing Items -->
            <?php if (!$allRequiredPass): ?>
            <div class="bg-amber-900/30 border border-amber-500/40 text-amber-200 p-4 mb-6 rounded-lg flex items-start gap-3">
                <i class="fas fa-exclamation-triangle text-amber-400 text-xl mt-0.5"></i>
                <div>
                    <span class="font-medium"><?= translate('some_checks_failed', 'Some required checks failed:') ?></span>
                    <ul class="list-disc pl-5 mt-1 text-sm text-amber-200/80">
                        <?php foreach ($missingItems as $item): ?>
                        <li><?= htmlspecialchars($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="mt-2 text-sm text-amber-200/70">
                        <?= translate('skip_warning', 'You can skip these checks, but your CMS may not function correctly without these requirements.') ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Core Requirements Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- PHP Environment -->
                <div class="bg-slate-800/40 border border-slate-700/50 rounded-xl p-5 shadow-lg">
                    <h3 class="font-cinzel text-gold-400 font-bold text-lg mb-4 flex items-center gap-2 border-b border-slate-700/50 pb-3">
                        <i class="fas fa-microchip"></i> <?= translate('php_environment', 'PHP Environment') ?>
                    </h3>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between p-3 bg-slate-900/40 rounded-lg border border-slate-700/30">
                            <span class="text-slate-200 font-medium text-sm">PHP >= 8.0</span>
                            <?= getVersionBadge($phpVersionPass, PHP_VERSION) ?>
                        </div>
                        <?php foreach ($requiredExtResults as $ext => $status): ?>
                        <div class="flex items-center justify-between p-3 bg-slate-900/40 rounded-lg border border-slate-700/30">
                            <span class="text-slate-300 text-sm font-mono"><?= $ext ?></span>
                            <?= getStatusBadge($status, translate('installed', 'Installed'), translate('missing', 'Missing'), translate('unknown', 'Unknown')) ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Server Configuration -->
                <div class="bg-slate-800/40 border border-slate-700/50 rounded-xl p-5 shadow-lg">
                    <h3 class="font-cinzel text-gold-400 font-bold text-lg mb-4 flex items-center gap-2 border-b border-slate-700/50 pb-3">
                        <i class="fas fa-server"></i> <?= translate('server_configuration', 'Server Configuration') ?>
                    </h3>
                    <div class="space-y-2">
                        <?php foreach ($requiredApacheResults as $mod => $status): ?>
                        <div class="flex items-center justify-between p-3 bg-slate-900/40 rounded-lg border border-slate-700/30">
                            <span class="text-slate-300 text-sm font-mono"><?= $mod ?></span>
                            <?= getStatusBadge($status, translate('enabled', 'Enabled'), translate('missing', 'Missing'), translate('na', 'N/A')) ?>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($requiredApacheResults)): ?>
                            <div class="p-3 text-center text-slate-500 text-sm italic"><?= translate('no_specific_modules', 'No specific server modules required.') ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Optional Enhancements -->
            <div class="bg-slate-800/40 border border-slate-700/50 rounded-xl p-5 shadow-lg mb-8">
                <h3 class="font-cinzel text-gold-400 font-bold text-lg mb-4 flex items-center gap-2 border-b border-slate-700/50 pb-3">
                    <i class="fas fa-plus-circle"></i> <?= translate('optional_enhancements', 'Optional Enhancements') ?>
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                    <?php foreach ($optionalExtResults as $ext => $status): ?>
                    <div class="flex items-center justify-between p-2.5 bg-slate-900/40 rounded-lg border border-slate-700/30">
                        <span class="text-slate-400 text-xs font-mono"><?= $ext ?></span>
                        <?= getStatusBadge($status, translate('installed', 'Installed'), translate('missing', 'Missing'), translate('unknown', 'Unknown')) ?>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php foreach ($optionalApacheResults as $mod => $status): ?>
                    <div class="flex items-center justify-between p-2.5 bg-slate-900/40 rounded-lg border border-slate-700/30">
                        <span class="text-slate-400 text-xs font-mono"><?= $mod ?></span>
                        <?= getStatusBadge($status, translate('enabled', 'Enabled'), translate('missing', 'Missing'), translate('na', 'N/A')) ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Help Accordion -->
            <details class="group bg-slate-800/40 border border-slate-700/50 rounded-xl overflow-hidden mb-8">
                <summary class="flex items-center justify-between p-4 cursor-pointer list-none hover:bg-slate-800/60 transition-colors">
                    <span class="flex items-center gap-2 text-gold-400 font-semibold text-sm">
                        <i class="fas fa-lightbulb"></i> <?= translate('btn_how_to_enable', 'How to enable PHP & Apache modules?') ?>
                    </span>
                    <i class="fas fa-chevron-down text-slate-400 group-open:rotate-180 transition-transform duration-300"></i>
                </summary>
                <div class="p-5 border-t border-slate-700/50 text-sm text-slate-300 space-y-6">
                    
                    <?php if ($isLinux): ?>
                    <!-- LINUX INSTRUCTIONS -->
                    <div>
                        <h4 class="text-emerald-400 font-bold text-base mb-2 flex items-center gap-2">
                            <i class="fab fa-linux"></i> <?= translate('linux_instructions', 'Linux / Ubuntu Instructions') ?>
                        </h4>
                        
                        <!-- PHP Extensions -->
                        <div class="mb-4">
                            <p class="text-slate-400 text-xs mb-2 font-semibold">📦 <?= translate('php_extensions', 'PHP Extensions') ?></p>
                            
                            <p class="text-slate-300 text-xs mb-1"><?= translate('install_php_extension', 'Install a PHP extension:') ?></p>
                            <div class="code-block">
                                <span class="cmd">sudo apt update</span><br>
                                <span class="cmd">sudo apt install php8.3-<span class="highlight">soap</span></span>
                                <span class="comment">  # Replace 'soap' with any extension name</span>
                            </div>
                            
                            <p class="text-slate-300 text-xs mt-2 mb-1"><?= translate('enable_php_extension', 'Enable a PHP extension (if already installed):') ?></p>
                            <div class="code-block">
                                <span class="cmd">sudo phpenmod <span class="highlight">soap</span></span>
                                <span class="comment">  # Enable the extension</span><br>
                                <span class="cmd">sudo phpdismod <span class="highlight">soap</span></span>
                                <span class="comment">  # Disable the extension</span>
                            </div>
                            
                            <p class="text-slate-300 text-xs mt-2 mb-1"><?= translate('verify_php_extension', 'Verify extension is loaded:') ?></p>
                            <div class="code-block">
                                <span class="cmd">php -m | grep <span class="highlight">soap</span></span>
                                <span class="comment">  # Should show 'soap' if enabled</span>
                            </div>
                            
                            <p class="text-slate-300 text-xs mt-2 mb-1"><?= translate('php_ini_location', 'PHP configuration file location:') ?></p>
                            <div class="code-block">
                                <span class="comment"># For Apache (web)</span><br>
                                <span class="output">/etc/php/8.3/apache2/php.ini</span><br>
                                <span class="comment"># For CLI (command line)</span><br>
                                <span class="output">/etc/php/8.3/cli/php.ini</span>
                            </div>
                        </div>
                        
                        <!-- Apache Modules -->
                        <div>
                            <p class="text-slate-400 text-xs mb-2 font-semibold">🔄 <?= translate('apache_modules', 'Apache Modules') ?></p>
                            
                            <p class="text-slate-300 text-xs mb-1"><?= translate('enable_apache_module', 'Enable an Apache module:') ?></p>
                            <div class="code-block">
                                <span class="cmd">sudo a2enmod <span class="highlight">headers</span></span>
                                <span class="comment">  # Enable mod_headers</span><br>
                                <span class="cmd">sudo a2dismod <span class="highlight">headers</span></span>
                                <span class="comment">  # Disable mod_headers</span>
                            </div>
                            
                            <p class="text-slate-300 text-xs mt-2 mb-1"><?= translate('restart_apache', 'Restart Apache after changes:') ?></p>
                            <div class="code-block">
                                <span class="cmd">sudo systemctl restart apache2</span><br>
                                <span class="comment"># Or use reload for graceful restart</span><br>
                                <span class="cmd">sudo systemctl reload apache2</span>
                            </div>
                            
                            <p class="text-slate-300 text-xs mt-2 mb-1"><?= translate('verify_apache_module', 'Verify Apache modules:') ?></p>
                            <div class="code-block">
                                <span class="cmd">apache2ctl -M | grep <span class="highlight">headers</span></span>
                                <span class="comment">  # Should show 'headers_module (shared)'</span><br>
                                <span class="cmd">apache2ctl -M</span>
                                <span class="comment">  # List all enabled modules</span>
                            </div>
                            
                            <p class="text-slate-300 text-xs mt-2 mb-1"><?= translate('apache_config_location', 'Apache configuration locations:') ?></p>
                            <div class="code-block">
                                <span class="comment"># Virtual host configuration</span><br>
                                <span class="output">/etc/apache2/sites-available/</span><br>
                                <span class="comment"># Module configuration</span><br>
                                <span class="output">/etc/apache2/mods-available/</span>
                            </div>
                        </div>
                    </div>
                    
                    <?php elseif ($isWindows): ?>
                    <!-- WINDOWS INSTRUCTIONS (XAMPP) -->
                    <div>
                        <h4 class="text-sky-400 font-bold text-base mb-2 flex items-center gap-2">
                            <i class="fab fa-windows"></i> <?= translate('windows_instructions', 'Windows / XAMPP Instructions') ?>
                        </h4>
                        
                        <!-- PHP Extensions -->
                        <div class="mb-4">
                            <p class="text-slate-400 text-xs mb-2 font-semibold">📦 <?= translate('php_extensions', 'PHP Extensions') ?></p>
                            <ul class="list-disc pl-5 text-slate-300 text-xs space-y-1.5">
                                <li><?= translate('go_to', 'Go to') ?> <code class="px-1.5 py-0.5 bg-sky-500/10 text-sky-400 rounded border border-sky-500/20 font-mono"><?= htmlspecialchars($xamppPath) ?>\php</code></li>
                                <li><?= translate('locate_php_ini', 'Locate your <code>php.ini</code> file.') ?></li>
                                <li><?= translate('find_extension_line', 'Find the line with the extension name, e.g., <code>;extension=curl</code>.') ?></li>
                                <li><?= translate('remove_semicolon', 'Remove the semicolon <code>;</code> to enable it: <code>extension=curl</code>.') ?></li>
                                <li><?= translate('restart_webserver', 'Restart your web server (Apache/Nginx).') ?></li>
                            </ul>
                        </div>
                        
                        <!-- Apache Modules -->
                        <div>
                            <p class="text-slate-400 text-xs mb-2 font-semibold">🔄 <?= translate('apache_modules', 'Apache Modules') ?></p>
                            <ul class="list-disc pl-5 text-slate-300 text-xs space-y-1.5">
                                <li><?= translate('go_to', 'Go to') ?> <code class="px-1.5 py-0.5 bg-sky-500/10 text-sky-400 rounded border border-sky-500/20 font-mono"><?= htmlspecialchars($xamppPath) ?>\apache\conf</code></li>
                                <li><?= translate('check_httpd_conf', 'For Windows XAMPP, check the <code>httpd.conf</code> file and uncomment the module lines.') ?></li>
                                <li><?= translate('restart_apache', 'Restart Apache.') ?></li>
                            </ul>
                        </div>
                    </div>
                    
                    <?php else: ?>
                    <!-- GENERIC INSTRUCTIONS -->
                    <div>
                        <h4 class="text-amber-400 font-bold text-base mb-2 flex items-center gap-2">
                            <i class="fas fa-question-circle"></i> <?= translate('generic_instructions', 'General Instructions') ?>
                        </h4>
                        <p class="text-slate-300 text-xs">
                            <?= translate('generic_help', 'Please refer to your server documentation for enabling PHP extensions and Apache modules.') ?>
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Common Examples -->
                    <div class="border-t border-slate-700/50 pt-4 mt-2">
                        <h4 class="text-gold-400 font-bold text-sm mb-2 flex items-center gap-2">
                            <i class="fas fa-list-check"></i> <?= translate('common_examples', 'Common Examples') ?>
                        </h4>
                        
                        <?php if ($isLinux): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="bg-slate-900/40 border border-slate-700/30 rounded-lg p-3">
                                <p class="text-slate-400 text-xs font-semibold mb-1">📦 <?= translate('install_multiple_extensions', 'Install Multiple PHP Extensions') ?></p>
                                <div class="code-block text-xs">
                                    <span class="cmd">sudo apt install php8.3-{soap,curl,gd,mbstring,mysqli,xml,zip}</span>
                                </div>
                            </div>
                            <div class="bg-slate-900/40 border border-slate-700/30 rounded-lg p-3">
                                <p class="text-slate-400 text-xs font-semibold mb-1">🔄 <?= translate('enable_common_modules', 'Enable Common Apache Modules') ?></p>
                                <div class="code-block text-xs">
                                    <span class="cmd">sudo a2enmod rewrite headers expires deflate ssl</span>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="bg-slate-900/40 border border-slate-700/30 rounded-lg p-3">
                            <p class="text-slate-300 text-xs">
                                <?= translate('windows_example_note', 'For Windows XAMPP, edit php.ini and httpd.conf files, then restart Apache.') ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </details>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mt-8">
                <?php if ($allRequiredPass): ?>
                    <!-- All checks passed - normal proceed button -->
                    <a href="<?= htmlspecialchars($base_path ?? '') ?>install/step3_db" class="inline-flex items-center px-8 py-3 bg-gold-500 hover:bg-gold-400 text-slate-900 font-bold rounded-lg shadow-lg shadow-gold-600/20 transition-all duration-300 transform hover:scale-105">
                        <?= translate('btn_proceed_to_db', 'Proceed to Database Setup') ?>
                        <i class="fas fa-arrow-right ml-3"></i>
                    </a>
                <?php else: ?>
                    <!-- Some checks failed - show both Skip and Fix buttons -->
                    <a href="?skip=yes" class="inline-flex items-center px-8 py-3 bg-amber-600/80 hover:bg-amber-500 text-white font-bold rounded-lg shadow-lg shadow-amber-600/20 transition-all duration-300 transform hover:scale-105">
                        <i class="fas fa-forward mr-3"></i>
                        <?= translate('btn_skip_anyway', 'Skip & Continue Anyway') ?>
                        <i class="fas fa-exclamation-triangle ml-2 text-xs opacity-70"></i>
                    </a>
                    
                    <a href="<?= htmlspecialchars($base_path ?? '') ?>install/" class="inline-flex items-center px-6 py-3 bg-slate-700/50 hover:bg-slate-700/70 text-slate-300 font-semibold rounded-lg transition-all duration-300 border border-slate-600/30">
                        <i class="fas fa-arrow-left mr-2"></i>
                        <?= translate('btn_go_back', 'Go Back') ?>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Info note about skipping -->
            <?php if (!$allRequiredPass): ?>
            <div class="mt-4 text-center">
                <span class="inline-flex items-center px-3 py-1 bg-amber-500/10 text-amber-400/70 text-xs rounded-full border border-amber-500/20">
                    <i class="fas fa-info-circle mr-1.5"></i>
                    <?= translate('skip_info', 'Skipping will proceed to the next step without these requirements') ?>
                </span>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.inc.php'; ?>
</body>
</html>