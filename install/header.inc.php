<?php
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access to this file is not allowed.');
}
require_once __DIR__ . '/../includes/paths.php'; // Include paths.php
include 'languages/language.php';
// Ensure language.php is included
if (!function_exists('translate')) {
    error_log('translate() function not defined. Ensure language.php is included before header.inc.php.');
    die('Internal server error: Translation function not available. Please contact the administrator.');
}

// Ensure $langCode is set from session or URL, with fallback
global $langCode;
$langCode = isset($_SESSION['lang']) ? $_SESSION['lang'] : ($_GET['lang'] ?? 'en');

// Supported languages
$supported = ['en', 'fr', 'es', 'de', 'ru', 'pt', 'cn'];
if (!in_array($langCode, $supported)) {
    $langCode = 'en';
}

// Language display names
$langNames = [
    'en' => 'English',
    'fr' => 'Français',
    'es' => 'Español',
    'de' => 'Deutsch',
    'ru' => 'Русский',
    'pt' => 'Português',
    'cn' => '中文'
];

// Current language data
$currentFlag = $base_path . "languages/flags/{$langCode}.png";
$currentLabel = htmlspecialchars($langNames[$langCode] ?? 'English');
$currentFlagEsc = htmlspecialchars($currentFlag);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($langCode) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= translate('installer_title', 'Sahtout CMS Installer') ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        /* Only essential custom CSS for glass-morphism effects */
        .glass-nav {
            background: rgba(5, 7, 11, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(201,162,39,0.3);
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.6);
            position: relative;
            z-index: 999;
        }
        
        .lang-selected {
            background: rgba(10, 14, 22, 0.7);
            transition: all 0.3s ease;
            clip-path: polygon(6px 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%, 0 6px);
        }
        
        .lang-selected:hover {
            background: rgba(10, 14, 22, 0.9);
        }
        
        .lang-options {
            background: rgba(5, 7, 11, 0.95);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(201,162,39,0.25);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.6);
            clip-path: polygon(8px 0, 100% 0, 100% calc(100% - 8px), calc(100% - 8px) 100%, 0 100%, 0 8px);
        }
        
        .lang-options li {
            transition: all 0.2s ease;
        }
        
        .lang-options li:hover {
            background: rgba(242, 207, 82, 0.08);
        }
        
        .flag-icon {
            width: 20px;
            height: 15px;
            border-radius: 2px;
            object-fit: cover;
        }
        
        @media (max-width: 768px) {
            .flag-icon {
                width: 16px;
                height: 12px;
            }
        }
    </style>
    
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;900&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navbar - Glass-morphism with Tailwind -->
    <nav class="glass-nav flex flex-wrap items-center justify-between px-4 md:px-8 py-3 gap-2">
        
        <!-- Logo and Title -->
        <div class="flex items-center">
            <img src="<?php echo $base_path; ?>install/logo.png" alt="<?= translate('logo_alt', 'Sahtout Logo') ?>" class="h-10 md:h-12 mr-3 md:mr-4 rounded transition-transform duration-300 hover:scale-105">
            <span class="font-['Cinzel'] text-xl md:text-2xl font-bold text-[#f2cf5b] tracking-wide drop-shadow-[0_0_20px_rgba(242,207,82,0.2)]">
                <?= translate('installer_title', 'Sahtout CMS Installer') ?>
            </span>
        </div>
        
        <!-- Language Dropdown -->
        <div class="relative inline-block min-w-[120px] md:min-w-[150px] font-['Cinzel'] lang-dropdown">
            <div class="lang-selected flex items-center gap-2 px-3 py-2 md:px-4 md:py-2.5 border border-[rgba(201,162,39,0.3)] text-[#f2cf5b] cursor-pointer hover:border-[rgba(201,162,39,0.6)] hover:shadow-[0_0_20px_rgba(242,207,82,0.1)]" id="langSelected">
                <img src="<?= $currentFlagEsc ?>" alt="<?= htmlspecialchars($currentLabel) ?>" class="flag-icon" id="flagIcon">
                <span class="text-sm md:text-base" id="langLabel"><?= htmlspecialchars($currentLabel) ?></span>
                <i class="fas fa-chevron-down text-[10px] text-[rgba(242,207,82,0.6)] ml-1"></i>
            </div>
            
            <ul class="lang-options absolute top-full right-0 min-w-full mt-1 p-0 list-none opacity-0 invisible translate-y-[-8px] transition-all duration-300" style="z-index:1000;">
                <li data-value="en" data-flag="<?php echo $base_path; ?>languages/flags/en.png" class="flex items-center gap-2.5 px-3 py-2.5 md:px-4 md:py-3 text-gray-300 hover:text-[#f2cf5b] cursor-pointer text-sm md:text-base">
                    <img src="<?php echo $base_path; ?>languages/flags/en.png" alt="English" class="flag-icon"> English
                </li>
                <li data-value="fr" data-flag="<?php echo $base_path; ?>languages/flags/fr.png" class="flex items-center gap-2.5 px-3 py-2.5 md:px-4 md:py-3 text-gray-300 hover:text-[#f2cf5b] cursor-pointer text-sm md:text-base">
                    <img src="<?php echo $base_path; ?>languages/flags/fr.png" alt="Français" class="flag-icon"> Français
                </li>
                <li data-value="es" data-flag="<?php echo $base_path; ?>languages/flags/es.png" class="flex items-center gap-2.5 px-3 py-2.5 md:px-4 md:py-3 text-gray-300 hover:text-[#f2cf5b] cursor-pointer text-sm md:text-base">
                    <img src="<?php echo $base_path; ?>languages/flags/es.png" alt="Español" class="flag-icon"> Español
                </li>
                <li data-value="de" data-flag="<?php echo $base_path; ?>languages/flags/de.png" class="flex items-center gap-2.5 px-3 py-2.5 md:px-4 md:py-3 text-gray-300 hover:text-[#f2cf5b] cursor-pointer text-sm md:text-base">
                    <img src="<?php echo $base_path; ?>languages/flags/de.png" alt="Deutsch" class="flag-icon"> Deutsch
                </li>
                <li data-value="ru" data-flag="<?php echo $base_path; ?>languages/flags/ru.png" class="flex items-center gap-2.5 px-3 py-2.5 md:px-4 md:py-3 text-gray-300 hover:text-[#f2cf5b] cursor-pointer text-sm md:text-base">
                    <img src="<?php echo $base_path; ?>languages/flags/ru.png" alt="Русский" class="flag-icon"> Русский
                </li>
                <li data-value="pt" data-flag="<?php echo $base_path; ?>languages/flags/pt.png" class="flex items-center gap-2.5 px-3 py-2.5 md:px-4 md:py-3 text-gray-300 hover:text-[#f2cf5b] cursor-pointer text-sm md:text-base">
                    <img src="<?php echo $base_path; ?>languages/flags/pt.png" alt="Português" class="flag-icon"> Português
                </li>
                <li data-value="cn" data-flag="<?php echo $base_path; ?>languages/flags/cn.png" class="flex items-center gap-2.5 px-3 py-2.5 md:px-4 md:py-3 text-gray-300 hover:text-[#f2cf5b] cursor-pointer text-sm md:text-base">
                    <img src="<?php echo $base_path; ?>languages/flags/cn.png" alt="中文" class="flag-icon"> 中文
                </li>
            </ul>
        </div>
    </nav>
    
    <script>
        // Hover functionality for dropdown
        const dropdown = document.querySelector('.lang-dropdown');
        if (dropdown) {
            const options = dropdown.querySelector('.lang-options');
            
            dropdown.addEventListener('mouseenter', function() {
                options.classList.add('opacity-100', 'visible', 'translate-y-0');
                options.classList.remove('opacity-0', 'invisible', '-translate-y-2');
            });
            
            dropdown.addEventListener('mouseleave', function() {
                options.classList.remove('opacity-100', 'visible', 'translate-y-0');
                options.classList.add('opacity-0', 'invisible', '-translate-y-2');
            });
        }
        
        document.querySelectorAll('.lang-options li').forEach(option => {
            option.addEventListener('click', function () {
                const lang = this.getAttribute('data-value');
                const flagSrc = this.getAttribute('data-flag');
                const langLabel = this.textContent.trim();

                // Update displayed flag and label
                const flagIcon = document.getElementById('flagIcon');
                flagIcon.src = flagSrc;
                flagIcon.alt = langLabel;
                document.getElementById('langLabel').textContent = langLabel;

                // Update URL with lang parameter and reload
                const url = new URL(window.location);
                url.searchParams.set('lang', lang);
                window.location.href = url.toString();
            });
        });
    </script>
</body>
</html>