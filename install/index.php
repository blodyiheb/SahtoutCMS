<?php
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../includes/paths.php'; // Include paths.php
include __DIR__ . '/header.inc.php';

// Set current step for progress stepper
$current_step = 1;
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($langCode ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= translate('installer_title', 'SahtoutCMS Installer') ?></title>
    
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

          <!-- Logo -->
<div class="text-center mb-4">
    <div class="w-20 h-20 mx-auto bg-gold-500/10 border border-gold-500/30 flex items-center justify-center rounded-full">
        <img 
            src="logo.png" 
            alt="Logo"
            class="w-16 h-16 object-contain"
        >
    </div>
</div>
            
            <!-- Title -->
            <h1 class="font-cinzel text-3xl md:text-5xl font-bold text-center bg-gradient-to-b from-amber-100 to-gold-500 bg-clip-text text-transparent mb-2">
                <?= translate('installer_name', 'SahtoutCMS Installer') ?>
            </h1>
            
            <div class="text-4xl text-center mb-4 text-gold-400">⚔️</div>
            
            <!-- Description -->
            <p class="text-slate-300 text-base leading-relaxed text-center mb-4">
                <?= translate('welcome_message_line1', 'Welcome, adventurer.') ?><br>
                <?= translate('welcome_message_line2', 'This installer will guide you through the setup of <strong class="text-gold-400">SahtoutCMS</strong>, for World of Warcraft private servers.') ?><br><br>
                <?= translate('welcome_message_line3', 'Prepare your database credentials and your server, for the journey begins now.') ?>
            </p>
            
            <!-- Note -->
            <p class="text-slate-400 text-sm italic mb-6 border-t border-slate-700/50 pt-4 text-center">
                <i class="fas fa-info-circle text-gold-400 mr-2"></i>
                <?= translate('note_dev_info', 'Note: I created this project alone for fun, learning, and testing. While I\'ve tried to make it look and feel professional, it\'s not a team project, and some bugs may still exist. Enjoy exploring and give feedback if you find issues!') ?>
            </p>
            
            <!-- Button -->
            <div class="text-center">
                <a href="<?php echo $base_path; ?>install/step2_check" class="inline-flex items-center px-8 py-3.5 bg-gold-500 hover:bg-gold-400 text-slate-900 font-bold rounded-lg shadow-lg shadow-gold-600/20 transition-all duration-300 transform hover:scale-105">
                    <i class="fas fa-rocket mr-2"></i>
                    <?= translate('btn_begin_install', 'Begin Installation') ?>
                    <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.inc.php'; ?>
</body>
</html>