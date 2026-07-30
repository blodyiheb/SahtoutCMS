<?php
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access to this file is not allowed.');
}

// Start session if not already active
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Supported languages
$supportedLangs = ['en', 'fr', 'es', 'de', 'ru','pt'];
$defaultLang = 'en';

// === 1. Priority: URL parameter ?lang=fr ===
if (isset($_GET['lang']) && in_array($_GET['lang'], $supportedLangs)) {
    $_SESSION['lang'] = $_GET['lang'];
}

// === 2. Fallback: Session language ===
elseif (!isset($_SESSION['lang'])) {
    // Try browser language
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $browserLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        if (in_array($browserLang, $supportedLangs)) {
            $_SESSION['lang'] = $browserLang;
        }
    }

    // Final fallback
    if (!isset($_SESSION['lang'])) {
        $_SESSION['lang'] = $defaultLang;
    }
}

// === 3. Set global $langCode ===
$langCode = $_SESSION['lang'];

// === 4. Load global language file ===
$globalFile = __DIR__ . "/{$langCode}/global.php";
$lang = [];

if (file_exists($globalFile) && is_readable($globalFile)) {
    $lang = include $globalFile;
} else {
    // Fallback to English if translation file missing
    $langCode = $defaultLang;
    $globalFile = __DIR__ . "/{$defaultLang}/global.php";
    $lang = file_exists($globalFile) && is_readable($globalFile) ? include $globalFile : [];
}

// === 5. Optional: Load page-specific translations (e.g., step2.php) ===
$basePath = realpath(__DIR__ . '/../');
$scriptPath = realpath($_SERVER['SCRIPT_FILENAME']);
$relativePath = str_replace($basePath, '', $scriptPath);
$pageFileName = pathinfo($relativePath, PATHINFO_FILENAME) . '.php';

$pageFile = __DIR__ . "/{$langCode}/{$pageFileName}";
if (file_exists($pageFile) && is_readable($pageFile)) {
    $pageLang = include $pageFile;
    if (is_array($pageLang)) {
        $lang = array_merge($lang, $pageLang);
    }
}

// === 6. Translation function ===


function translate($key, $default = '', ...$args) {
    global $lang;
    $string = $lang[$key] ?? $default;
    if (!empty($args)) {
        return vsprintf($string, $args); // Replace %d with provided arguments
    }
    return $string;
}
?>
