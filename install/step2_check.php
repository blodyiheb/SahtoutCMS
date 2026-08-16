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

// Configurable XAMPP path for help content
$xamppPath = 'C:\\xampp';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($langCode ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= translate('installer_title', 'SahtoutCMS Installer') ?> - <?= translate('step2_title', 'Step 2: Environment Check') ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'cinzel': ['Cinzel', 'serif'],
                        'sans': ['Inter', 'sans-serif'],
                    },
                    colors: {
                        gold: {
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                        }
                    }
                }
            }
        }
    </script>

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
                    <i class="fas fa-check-double text-3xl text-gold-400"></i>
                </div>
                <h1 class="font-cinzel text-3xl md:text-4xl font-bold bg-gradient-to-b from-amber-100 to-gold-500 bg-clip-text text-transparent">
                    <?= translate('step2_title', 'Step 2: Environment Check') ?>
                </h1>
                <p class="text-slate-400 mt-2 text-sm"><?= translate('step2_description', 'Verifying server compatibility and required extensions.') ?></p>
            </div>

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
                        
                        <!-- Empty state filler if no apache modules are required to check -->
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
                    <!-- PHP Help -->
                    <div>
                        <h4 class="text-gold-400 font-bold text-base mb-2 flex items-center gap-2">
                            <i class="fas fa-code"></i> <?= translate('php_extensions', 'PHP Extensions') ?>
                        </h4>
                        <p class="text-slate-400 text-xs mb-2"><?= translate('to_enable_php', 'To enable a PHP extension:') ?></p>
                        <ul class="list-disc pl-5 text-slate-300 text-xs space-y-1.5">
                            <li><?= translate('go_to', 'Go to') ?> <code class="px-1.5 py-0.5 bg-sky-500/10 text-sky-400 rounded border border-sky-500/20 font-mono"><?= htmlspecialchars($xamppPath) ?>\php</code></li>
                            <li><?= translate('locate_php_ini', 'Locate your <code>php.ini</code> file.') ?></li>
                            <li><?= translate('find_extension_line', 'Find the line with the extension name, e.g., <code>;extension=curl</code>.') ?></li>
                            <li><?= translate('remove_semicolon', 'Remove the semicolon <code>;</code> to enable it: <code>extension=curl</code>.') ?></li>
                            <li><?= translate('restart_webserver', 'Restart your web server (Apache/Nginx).') ?></li>
                        </ul>
                    </div>
                    
                    <!-- Apache Help -->
                    <div>
                        <h4 class="text-gold-400 font-bold text-base mb-2 flex items-center gap-2">
                            <i class="fas fa-server"></i> <?= translate('apache_modules', 'Apache Modules') ?>
                        </h4>
                        <p class="text-slate-400 text-xs mb-2"><?= translate('to_enable_apache', 'To enable Apache modules:') ?></p>
                        <ul class="list-disc pl-5 text-slate-300 text-xs space-y-1.5">
                            <li><?= translate('go_to', 'Go to') ?> <code class="px-1.5 py-0.5 bg-sky-500/10 text-sky-400 rounded border border-sky-500/20 font-mono"><?= htmlspecialchars($xamppPath) ?>\apache\conf</code></li>
                            <li><?= translate('check_httpd_conf', 'For Windows XAMPP, check the <code>httpd.conf</code> file and uncomment the module lines.') ?></li>
                            <li><?= translate('restart_apache', 'Restart Apache.') ?></li>
                        </ul>
                    </div>
                    
                    <!-- Image -->
                    <?php if (!empty($base_path)): ?>
                    <div class="mt-4 border border-slate-700/50 rounded-lg overflow-hidden">
                        <img src="<?= htmlspecialchars($base_path) ?>install/phphttpd.png" alt="<?= translate('image_example', 'image example') ?>" class="w-full h-auto object-cover">
                    </div>
                    <?php endif; ?>
                </div>
            </details>

            <!-- Action Buttons -->
            <?php if ($allRequiredPass): ?>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mt-8">
                    <a href="<?= htmlspecialchars($base_path ?? '') ?>install/step3_db" class="inline-flex items-center px-8 py-3 bg-gold-500 hover:bg-gold-400 text-slate-900 font-bold rounded-lg shadow-lg shadow-gold-600/20 transition-all duration-300 transform hover:scale-105">
                        <?= translate('btn_proceed_to_db', 'Proceed to Database Setup') ?>
                        <i class="fas fa-arrow-right ml-3"></i>
                    </a>
                    <a href="<?= htmlspecialchars($base_path ?? '') ?>install/" class="inline-flex items-center px-6 py-3 bg-slate-700/50 hover:bg-slate-700/70 text-slate-300 font-semibold rounded-lg transition-all duration-300 border border-slate-600/30">
                        <i class="fas fa-arrow-left mr-2"></i>
                        <?= translate('btn_go_back', 'Go Back') ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="bg-red-900/30 border border-red-500/40 text-red-200 p-4 mt-8 rounded-lg flex items-center gap-3 justify-center">
                    <i class="fas fa-exclamation-triangle text-red-400 text-xl"></i>
                    <span class="font-medium"><?= translate('some_checks_failed', 'Some required checks failed. Fix them before continuing.') ?></span>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.inc.php'; ?>
</body>
</html>