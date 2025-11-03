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
$supported = ['en', 'fr', 'es', 'de', 'ru','pt'];
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
    'pt' => 'Português'
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
    <style>
        /* Navbar styles */
        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(135deg, #0a0a0a, #1a0a0a);
            padding: 15px 30px;
            border-bottom: 3px solid #cccf22;
            box-shadow: 0 0 20px rgba(212, 175, 55, 0.7);
            position: sticky;
            top: 0;
            z-index: 999;
        }
        .navbar img {
            height: 60px;
            margin-right: 20px;
            border-radius: 8px;
        }
        .navbar .title {
            font-family: 'Cinzel', serif;
            font-size: 2em;
            color: #ffd700;
            text-shadow: 0 0 10px #000, 0 0 20px #d4af37;
            font-weight: 700;
            margin: 0;
        }
        /* Language Dropdown */
        .lang-dropdown {
            position: relative;
            display: inline-block;
            width: 160px;
            font-family: 'Cinzel', serif;
        }
        .lang-selected {
            background: #1a0a0a;
            color: #ffd700;
            padding: 8px 12px;
            border: 1px solid #cccf22;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 0 10px rgba(212, 175, 55, 0.5);
            transition: all 0.3s ease;
        }
        .lang-selected:hover {
            background: #2a2a2a;
            box-shadow: 0 0 15px rgba(212, 175, 55, 0.6);
        }
        .lang-selected img {
            width: 20px;
            height: 15px;
            border-radius: 2px;
        }
        .lang-options {
            position: absolute;
            top: 100%;
            right: 0;
            width: 100%;
            background: #1a0a0a;
            border: 1px solid #cccf22;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(212, 175, 55, 0.6);
            list-style: none;
            margin: 5px 0 0 0;
            padding: 0;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        .lang-dropdown:hover .lang-options {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .lang-options li {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            color: #ffd700;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        .lang-options li:hover {
            background: #2a2a2a;
        }
        .lang-options li img {
            width: 20px;
            height: 15px;
            border-radius: 2px;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="navbar">
        <!-- Logo and Title -->
        <div style="display: flex; align-items: center;">
            <img src="<?php echo $base_path; ?>install/logo.png" alt="<?= translate('logo_alt', 'Sahtout Logo') ?>">
            <div class="title"><?= translate('installer_title', 'Sahtout CMS Installer') ?></div>
        </div>
        <!-- Language Dropdown -->
        <div class="lang-dropdown">
            <div class="lang-selected" id="langSelected">
                <img src="<?= $currentFlagEsc ?>" alt="<?= htmlspecialchars($currentLabel) ?>" id="flagIcon">
                <span id="langLabel"><?= htmlspecialchars($currentLabel) ?></span>
            </div>
            <ul class="lang-options">
                <li data-value="en" data-flag="<?php echo $base_path; ?>languages/flags/en.png">
                    <img src="<?php echo $base_path; ?>languages/flags/en.png" alt="English"> English
                </li>
                <li data-value="fr" data-flag="<?php echo $base_path; ?>languages/flags/fr.png">
                    <img src="<?php echo $base_path; ?>languages/flags/fr.png" alt="Français"> Français
                </li>
                <li data-value="es" data-flag="<?php echo $base_path; ?>languages/flags/es.png">
                    <img src="<?php echo $base_path; ?>languages/flags/es.png" alt="Español"> Español
                </li>
                <li data-value="de" data-flag="<?php echo $base_path; ?>languages/flags/de.png">
                    <img src="<?php echo $base_path; ?>languages/flags/de.png" alt="Deutsch"> Deutsch
                </li>
                <li data-value="ru" data-flag="<?php echo $base_path; ?>languages/flags/ru.png">
                    <img src="<?php echo $base_path; ?>languages/flags/ru.png" alt="Русский"> Русский
                </li>
                <li data-value="pt" data-flag="<?php echo $base_path; ?>languages/flags/pt.png">
                    <img src="<?php echo $base_path; ?>languages/flags/pt.png" alt="Português"> Português
                </li>
            </ul>
        </div>
    </div>
    <script>
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