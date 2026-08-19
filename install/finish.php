<?php
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../includes/paths.php'; // Include paths.php
include __DIR__ . '/header.inc.php';

// Set current step for progress stepper
$current_step = 7;

// Check required config files
$configFiles = [
    'Database config' => __DIR__ . '/../includes/config.php',
    'reCAPTCHA config' => __DIR__ . '/../includes/config.cap.php',
    'SOAP config' => __DIR__ . '/../includes/soap.conf.php',
    'Mail config' => __DIR__ . '/../includes/config.mail.php',
];

$errors = [];
foreach ($configFiles as $name => $path) {
    if (!file_exists($path)) {
        $errors[] = translate('err_config_missing', 'Configuration file missing:') . ' ' . basename($path);
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($langCode ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= translate('installer_title') ?> - <?= translate('finish_title', 'Finish') ?></title>
    
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
            max-width: 700px;
            width: 100%;
            margin: 0 auto;
            padding: 0 1rem;
        }

        /* Confetti-like decoration */
        .celebration-icon {
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
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

            <!-- Header with Logo -->
            <div class="text-center mb-8">
                <div class="mb-4">
                    <img
                        src="logo.png"
                        alt="SahtoutCMS"
                        class="w-22 h-22 md:w-36 md:h-36 object-contain mx-auto drop-shadow-2xl celebration-icon"
                    >
                </div>
                <h1 class="font-cinzel text-3xl md:text-5xl font-bold bg-gradient-to-b from-amber-100 to-gold-500 bg-clip-text text-transparent">
                    <?= translate('finish_title', 'Installer Complete') ?> ⚔️
                </h1>
                <p class="text-slate-400 mt-2 text-sm"><?= translate('finish_description', 'SahtoutCMS is ready for action!') ?></p>
            </div>

            <!-- Errors -->
            <?php if (!empty($errors)): ?>
                <div class="bg-red-900/30 border border-red-500/40 text-red-200 p-4 mb-6 rounded-lg">
                    <div class="flex items-center gap-2 mb-2 font-bold text-red-300">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?= translate('finish_errors_msg', 'Some required configuration files are missing. Please make sure all steps are completed.') ?>
                    </div>
                    <ul class="list-disc list-inside text-sm space-y-1 text-red-100/90">
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <!-- Success -->
                <div class="bg-emerald-900/30 border border-emerald-500/40 text-emerald-200 p-5 mb-6 rounded-lg flex items-center gap-3">
                    <i class="fas fa-check-circle text-emerald-400 text-2xl"></i>
                    <span class="font-medium"><?= translate('finish_all_present', 'All configuration files are present!') ?></span>
                </div>
                
                <!-- Congratulations Message -->
                <div class="text-center mb-6">
                    <p class="text-slate-300 text-base leading-relaxed">
                        <?= translate('finish_congrats', 'Congratulations, SahtoutCMS is fully installed and ready to use.') ?>
                    </p>
                </div>

                <!-- Support SahtoutCMS -->
                <div class="relative overflow-hidden bg-gradient-to-br from-amber-950/40 via-slate-900/70 to-red-950/30 border border-amber-500/30 rounded-xl p-6 mb-6 text-center shadow-lg">
                    
                    <!-- Decorative glow -->
                    <div class="absolute -top-20 -right-20 w-40 h-40 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="absolute -bottom-20 -left-20 w-40 h-40 bg-red-500/10 rounded-full blur-3xl pointer-events-none"></div>
                    
                    <div class="relative z-10">
                        
                        <div class="flex justify-center mb-3">
                            <i class="fas fa-heart text-red-400 text-3xl"></i>
                        </div>
                        
                        <h2 class="font-cinzel text-xl md:text-2xl font-bold text-amber-300 mb-2">
                            Support SahtoutCMS
                        </h2>
                        
                        <p class="text-slate-300 text-sm leading-relaxed max-w-xl mx-auto mb-5">
                            Enjoying SahtoutCMS? ❤️
                            If this project has helped you or your WoW server,
                            consider supporting its continued development.
                        </p>
                        
                        <a 
                            href="https://github.com/blodyiheb/SahtoutCMS#-support-sahtoutcms"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-gradient-to-r from-red-600 to-amber-500 hover:from-red-500 hover:to-amber-400 text-white font-bold rounded-lg shadow-lg shadow-red-900/30 transition-all duration-300 transform hover:scale-105"
                        >
                            <i class="fas fa-heart"></i>
                            Support the Project
                            <i class="fas fa-external-link-alt text-xs"></i>
                        </a>
                        
                        <p class="text-xs text-slate-500 mt-3">
                            Donations are completely optional and help keep SahtoutCMS alive and growing.
                        </p>
                        
                    </div>
                </div>

                <!-- Security Note -->
                <div class="bg-amber-900/20 border border-amber-500/30 text-amber-200 p-4 mb-6 rounded-lg flex items-start gap-3">
                    <i class="fas fa-shield-alt text-amber-400 text-xl mt-0.5"></i>
                    <div>
                        <p class="font-semibold text-sm"><?= translate('finish_security_note_title', 'Security Recommendation') ?></p>
                        <p class="text-sm text-amber-200/80">
                            <?= translate('finish_security_note', 'For security, it is strongly recommended to <strong class="text-amber-400">delete the "install" folder</strong> from your server.') ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Button -->
            <div class="text-center mt-6">
                <a href="<?php echo $base_path; ?>" class="inline-flex items-center px-8 py-3.5 bg-gold-500 hover:bg-gold-400 text-slate-900 font-bold rounded-lg shadow-lg shadow-gold-600/20 transition-all duration-300 transform hover:scale-105">
                    <i class="fas fa-home mr-2"></i>
                    <?= translate('btn_go_to_homepage', 'Go to SahtoutCMS Homepage') ?>
                    <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>

            <!-- Optional: Go Back Button (if errors) -->
            <?php if (!empty($errors)): ?>
                <div class="text-center mt-4">
                    <a href="<?= htmlspecialchars($base_path ?? '') ?>install/step6_soap" class="inline-flex items-center px-6 py-3 bg-slate-700/50 hover:bg-slate-700/70 text-slate-300 font-semibold rounded-lg transition-all duration-300 border border-slate-600/30">
                        <i class="fas fa-arrow-left mr-2"></i>
                        <?= translate('btn_go_back', 'Go Back') ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.inc.php'; ?>
</body>
</html>