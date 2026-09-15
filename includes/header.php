<?php
// ===== Production-safe error handling =====
// Errors are logged server-side, never displayed to visitors.
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
// If your host has a specific error log path, set it here, e.g.:
// ini_set('error_log', $project_root . 'logs/php-error.log');

// Include paths.php to access $project_root and $base_path
require_once __DIR__ . '/paths.php';

if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access to this file is not allowed.');
}

// Use $project_root for including config.settings.php
require_once $project_root . 'includes/config.settings.php'; // load logo + socials

// Include language detection
require_once $project_root . 'languages/language.php';

// Check if session is started; warn in source code if not
if (session_status() !== PHP_SESSION_ACTIVE) {
    echo "<!-- WARNING: Session not started. Ensure session_start() is called in the parent script. -->\n";
}

// Debug: Check if session variable is set (visible in source code only)
if (!isset($_SESSION['user_id'])) {
    echo "<!-- DEBUG: No user session detected. Ensure login script sets \$_SESSION['user_id']. -->\n";
}

// Ensure $page_class is defined in the including page; default to 'default'
$page_class = isset($page_class) ? $page_class : 'default';

// Optional page-specific overrides set by the including page before this include:
// $page_title, $page_meta_description, $page_meta_robots, $page_head (raw HTML for <head>), $page_body_class
//
// SECURITY NOTE: $page_head is a raw developer-controlled HTML block. It must ONLY be
// set by trusted server-side page code (never from GET/POST/COOKIE/user/DB input).
// Verified: nothing in this header file populates $page_head from user input.
$page_title            = isset($page_title) ? $page_title : ($site_title_name ?? '');
$page_meta_description = isset($page_meta_description) ? $page_meta_description : '';
$page_meta_robots      = isset($page_meta_robots) ? $page_meta_robots : 'index';
$page_head             = isset($page_head) ? $page_head : '';
$page_body_class       = isset($page_body_class) ? $page_body_class : '';

// Get current URL without query string
$currentUrl = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$currentUrl = rtrim($currentUrl, '/');

// Function to generate language URLs
function getLanguageUrl($lang) {
    global $currentUrl;

    $query = $_GET;
    $query['lang'] = $lang;

    $queryString = http_build_query($query);

    return $currentUrl . '?' . $queryString;
}

// Fetch user points, tokens, email, avatar, gmlevel, and role if logged in
$points = 0;
$tokens = 0;
$email = 'user@example.com';
$avatar = $base_path . 'img/accountimg/profile_pics/user.jpg'; // Default avatar
$gmlevel = 0;
$role = 'player';

if (isset($_SESSION['user_id'])) {
    // Query site_db for points, tokens, avatar, and role
    $stmt_site = $site_db->prepare("
        SELECT points, tokens, avatar, role 
        FROM user_currencies 
        WHERE account_id = ?
    ");

    // Query auth_db for email
    $stmt_auth = $auth_db->prepare("
        SELECT email 
        FROM account 
        WHERE id = ?
    ");

    if ($stmt_site && $stmt_auth) {
        // Bind and execute site_db query
        $stmt_site->bind_param('i', $_SESSION['user_id']);
        $stmt_site->execute();
        $result_site = $stmt_site->get_result();

        // Bind and execute auth_db query
        $stmt_auth->bind_param('i', $_SESSION['user_id']);
        $stmt_auth->execute();
        $result_auth = $stmt_auth->get_result();

        if ($result_site && $result_site->num_rows > 0 && $result_auth && $result_auth->num_rows > 0) {
            $row_site = $result_site->fetch_assoc();
            $row_auth = $result_auth->fetch_assoc();

            $points = (int)$row_site['points'];
            $tokens = (int)$row_site['tokens'];
            $email = htmlspecialchars($row_auth['email'] ?? 'user@example.com', ENT_QUOTES, 'UTF-8');
            $role = $row_site['role'] ?? 'player';

            // ---- Avatar resolution (hardened) ----
            // Source of truth: user_currencies.avatar, then validated against
            // profile_avatars WHERE filename = ? AND active = 1.
            // $_SESSION['avatar'] is intentionally NOT used as a fallback:
            // only the DB value, validated against profile_avatars, is used.
            $candidate_avatar = null;

            if (!empty($row_site['avatar'])) {
                $candidate_avatar = $row_site['avatar'];
            }

            if ($candidate_avatar !== null) {
                $stmt_check = $site_db->prepare("
                    SELECT filename 
                    FROM profile_avatars 
                    WHERE filename = ? AND active = 1
                ");

                if ($stmt_check) {
                    $stmt_check->bind_param('s', $candidate_avatar);
                    $stmt_check->execute();
                    $check_result = $stmt_check->get_result();

                    if ($check_result && $check_result->num_rows > 0) {
                        $valid_row = $check_result->fetch_assoc();
                        // Use ONLY the filename returned by the validated
                        // profile_avatars row, safely encoded for the URL, so path
                        // traversal / URL injection cannot reach the output.
                        $safe_filename = basename((string)$valid_row['filename']);
                        if ($safe_filename !== '' && $safe_filename !== '.' && $safe_filename !== '..') {
                            $avatar = $base_path . 'img/accountimg/profile_pics/'
                                    . rawurlencode($safe_filename);
                        } else {
                            $avatar = $base_path . 'img/accountimg/profile_pics/user.jpg';
                        }
                    } else {
                        $avatar = $base_path . 'img/accountimg/profile_pics/user.jpg';
                    }

                    $stmt_check->close();
                } else {
                    error_log("Failed to prepare avatar validation statement in header.");
                    $avatar = $base_path . 'img/accountimg/profile_pics/user.jpg';
                }
            } else {
                $avatar = $base_path . 'img/accountimg/profile_pics/user.jpg';
            }
            // ---- End avatar resolution ----
        } else {
            error_log("No user data found for user_id: {$_SESSION['user_id']} in user_currencies or account tables.");
        }

        $stmt_site->close();
        $stmt_auth->close();
    } else {
        error_log("Failed to prepare statement for fetching user data in header.");
    }

    // Fetch GM level
    $stmt = $auth_db->prepare("SELECT gmlevel FROM account_access WHERE id = ?");

    if ($stmt) {
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $gmData = $result->fetch_assoc();
            $gmlevel = (int)$gmData['gmlevel'];
        }

        $stmt->close();
    } else {
        error_log("Failed to prepare statement for fetching gmlevel in header.");
    }
}

// Get current language and flag
$current_lang = $_SESSION['lang'] ?? 'en';

$languages = [
    'en' => [
        'name' => 'English',
        'flag_url' => $base_path . 'languages/flags/en.png',
        'flag_path' => $project_root . 'languages/flags/en.png'
    ],
    'fr' => [
        'name' => 'Français',
        'flag_url' => $base_path . 'languages/flags/fr.png',
        'flag_path' => $project_root . 'languages/flags/fr.png'
    ],
    'es' => [
        'name' => 'Español',
        'flag_url' => $base_path . 'languages/flags/es.png',
        'flag_path' => $project_root . 'languages/flags/es.png'
    ],
    'de' => [
        'name' => 'Deutsch',
        'flag_url' => $base_path . 'languages/flags/de.png',
        'flag_path' => $project_root . 'languages/flags/de.png'
    ],
    'ru' => [
        'name' => 'Русский',
        'flag_url' => $base_path . 'languages/flags/ru.png',
        'flag_path' => $project_root . 'languages/flags/ru.png'
    ],
    'pt' => [
        'name' => 'Português',
        'flag_url' => $base_path . 'languages/flags/pt.png',
        'flag_path' => $project_root . 'languages/flags/pt.png'
    ],
    'cn' => [
        'name' => '中文',
        'flag_url' => $base_path . 'languages/flags/cn.png',
        'flag_path' => $project_root . 'languages/flags/cn.png'
    ],
];

// Fallback flag image if not found
$fallback_flag_url = $base_path . 'languages/flags/world.png';
$fallback_flag_path = $project_root . 'languages/flags/world.png';

foreach ($languages as $code => &$lang_data) {
    if (!file_exists($lang_data['flag_path'])) {
        error_log("Flag image not found: {$lang_data['flag_path']}. Using fallback: {$fallback_flag_url}");
        $lang_data['flag_url'] = $fallback_flag_url;
        $lang_data['flag_path'] = $fallback_flag_path;
    }
}
unset($lang_data);

// Safety fallback if language is invalid
if (!isset($languages[$current_lang])) {
    $current_lang = 'en';
}

$current_lang_name = $languages[$current_lang]['name'];
$current_lang_code = $current_lang;
$current_lang_flag = $languages[$current_lang]['flag_url'];

// Check if on auth page for transparent header
$is_auth_page = in_array($page_class, ['login', 'register']);

// ---- Escaped output helpers for HTML attributes ----
$esc_base = htmlspecialchars($base_path, ENT_QUOTES, 'UTF-8');
$esc_logo = htmlspecialchars($site_logo ?? '', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang_code, ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?php echo $esc_base; ?>">

    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>

    <?php if ($page_meta_description !== ''): ?>
    <meta name="description" content="<?php echo htmlspecialchars($page_meta_description, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>

    <meta name="robots" content="<?php echo htmlspecialchars($page_meta_robots, ENT_QUOTES, 'UTF-8'); ?>">

    <link rel="icon" href="<?php echo $esc_base . $esc_logo; ?>" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;900&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $esc_base; ?>assets/css/tailwind.css">
    <link rel="stylesheet" href="<?php echo $esc_base; ?>node_modules/@fortawesome/fontawesome-free/css/all.min.css">

    <style>
        /* ============ CORE THEME VARIABLES & BASE ============ */
        :root {
            --point-wow-gif: url('<?php echo $esc_base; ?>img/pointer_wow.gif');
            --hover-wow-gif: url('<?php echo $esc_base; ?>img/hover_wow.gif');
            --gold-primary: #f2cf5b;
            --gold-dark: #c9a227;
            --gold-deep: #8a6a14;
            --iron-bg: rgba(0,0,0,.4);
        }

        body {
            cursor: var(--point-wow-gif) 16 16, auto;
            padding-top: 72px;
        }

        @media (min-width: 1024px) {
            body {
                padding-top: 84px;
            }
        }

        /* ============ FORCE TEXT WRAPPING ============ */
        .force-wrap {
            min-width: 0;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        /* ============ HEADER CONTAINER ============ */
        header.main-header {
            background: linear-gradient(180deg, rgba(10,14,22,.96), rgba(5,7,11,.96));
            border-bottom: 1px solid rgba(201,162,39,.25);
            box-shadow: 0 8px 24px rgba(0,0,0,.6);
            z-index: 10000;
            overflow: visible;
        }

        header.transparent-header {
            background: rgba(5,7,11,0.55) !important;
        }

        .header-inner {
            position: relative;
            z-index: 10020;
        }

        /* ============ YELLOW PARALLELOGRAM ============ */
        .parallelogram {
            position: absolute;
            left: 0;
            top: 0;
            width: 180px;
            height: 100%;
            background: linear-gradient(135deg, #f2cf5b 0%, #c9a227 100%, #8a6a14 100%);
            clip-path: polygon(0 0, 85% 0, 100% 100%, 0 100%);
            opacity: 0.15;
            pointer-events: none;
            z-index: 0;
        }

        .parallelogram::after {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(180deg, rgba(255,215,0,0.1), rgba(255,215,0,0.3));
            clip-path: polygon(0 0, 85% 0, 100% 100%, 0 100%);
        }

        /* ============ NAVIGATION LINKS ============ */
        .nav-link {
            position: relative;
            padding: .45rem .8rem;
            font-family: 'Cinzel', serif;
            font-weight: 700;
            font-size: .72rem;
            letter-spacing: .03em;
            color: #d8d8d8;
            background: var(--iron-bg);
            border: 1px solid rgba(255,255,255,.08);
            clip-path: polygon(6px 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%, 0 6px);
            transition: all .25s ease;
            text-decoration: none;
            text-transform: uppercase;
            white-space: nowrap;
            line-height: 1.2;
        }

        .nav-link:hover {
            color: var(--gold-primary);
            border-color: rgba(201,162,39,.5);
            background: linear-gradient(180deg, rgba(201,162,39,.15), rgba(201,162,39,.05));
            text-shadow: 0 0 8px rgba(242,207,82,.4);
            transform: translateY(-2px);
        }

        .nav-link.active {
            color: var(--gold-primary);
            background: linear-gradient(180deg, rgba(201,162,39,.25), rgba(201,162,39,.08));
            border-color: rgba(242,207,82,.65);
            text-shadow: 0 0 10px rgba(242,207,82,.5);
        }

        @media (min-width: 1024px) {
            header nav.nav-menu {
                flex-wrap: nowrap;
            }
            header nav.nav-menu .nav-link {
                padding: .45rem .7rem;
                font-size: .7rem;
            }
        }

        /* ============ DROPDOWNS ============ */
        #profileDropdownContainer,
        #langDropdownContainer {
            position: relative;
        }

        .wow-dropdown {
            background: linear-gradient(180deg, rgba(22,25,32,.98), rgba(8,10,14,.98));
            border: 1px solid rgba(201,162,39,.35);
            box-shadow: 0 12px 32px rgba(0,0,0,.7), inset 0 0 40px rgba(0,0,0,.5);
            clip-path: polygon(12px 0, 100% 0, 100% calc(100% - 12px), calc(100% - 12px) 100%, 0 100%, 0 12px);
            filter: drop-shadow(0 10px 20px rgba(0,0,0,.5));
        }

        .wow-dropdown::before {
            content: '';
            position: absolute;
            inset: 4px;
            border: 1px solid rgba(201,162,39,.15);
            pointer-events: none;
            clip-path: polygon(10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%, 0 10px);
            z-index: 0;
        }

        .dropdown-menu,
        .lang-options {
            position: absolute !important;
            right: 0 !important;
            left: auto !important;
            top: 100% !important;
            z-index: 10050 !important;
            min-width: 0;
            max-width: calc(100vw - 1rem);
        }

        .dropdown-menu {
            width: min(14rem, calc(100vw - 1rem)) !important;
        }

        .lang-options {
            width: min(10rem, calc(100vw - 1rem)) !important;
        }

        @media (max-width: 479px) {
            .dropdown-menu {
                width: min(14rem, calc(100vw - 7rem)) !important;
            }

            .lang-options {
                width: min(10rem, calc(100vw - 1.5rem)) !important;
            }
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: .55rem;
            padding: .5rem .9rem;
            color: #d8d8d8;
            font-size: .8rem;
            font-weight: 600;
            transition: all .2s ease;
            text-decoration: none;
            position: relative;
            z-index: 1;
            min-width: 0;
            line-height: 1.3;
        }

        .dropdown-item > i,
        .dropdown-item > img {
            flex-shrink: 0;
        }

        .lang-options .dropdown-item > span {
            flex: 1 1 auto;
            min-width: 0;
            white-space: normal;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .dropdown-item:hover {
            background: linear-gradient(90deg, rgba(201,162,39,.15), transparent);
            color: var(--gold-primary);
            text-shadow: 0 0 8px rgba(242,207,82,.3);
        }

        .dropdown-item.danger {
            color: #f87171;
        }

        .dropdown-item.danger:hover {
            background: linear-gradient(90deg, rgba(220,38,38,.15), transparent);
            color: #fca5a5;
        }

        .dropdown-divider {
            height: 1px;
            margin: .2rem 0;
            background: linear-gradient(90deg, transparent, rgba(201,162,39,.4), transparent);
            position: relative;
            z-index: 1;
        }

        /* ============ CURRENCY BADGES ============ */
        .badge-gold {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .25rem .55rem;
            background: linear-gradient(180deg, #f6d478 0%, var(--gold-dark) 48%, var(--gold-deep) 100%);
            color: #1a1200;
            font-weight: 800;
            font-size: .7rem;
            letter-spacing: .02em;
            text-shadow: 0 1px 0 rgba(255,255,255,.35);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.28), inset 0 -4px 8px rgba(0,0,0,.25);
            clip-path: polygon(5px 0, 100% 0, 100% calc(100% - 5px), calc(100% - 5px) 100%, 0 100%, 0 5px);
        }

        .badge-iron {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .25rem .55rem;
            background: linear-gradient(180deg, #5a4070 0%, #3a284d 55%, #1e1428 100%);
            color: #e2d5f0;
            font-weight: 800;
            font-size: .7rem;
            letter-spacing: .02em;
            box-shadow: inset 0 0 0 1px rgba(160,120,255,.25), inset 0 -4px 8px rgba(0,0,0,.4);
            clip-path: polygon(5px 0, 100% 0, 100% calc(100% - 5px), calc(100% - 5px) 100%, 0 100%, 0 5px);
        }

        /* ============ AVATAR ============ */
        .avatar-frame {
            width: 40px;
            height: 40px;
            border: 2px solid var(--gold-dark);
            box-shadow: 0 0 12px rgba(201,162,39,.4), inset 0 0 8px rgba(0,0,0,.8);
            object-fit: cover;
            cursor: pointer;
            transition: all .3s ease;
            clip-path: polygon(15% 0, 85% 0, 100% 15%, 100% 85%, 85% 100%, 15% 100%, 0 85%, 0 15%);
        }

        .avatar-frame:hover {
            border-color: var(--gold-primary);
            box-shadow: 0 0 20px rgba(242,207,82,.6), inset 0 0 8px rgba(0,0,0,.8);
            transform: scale(1.05);
        }

        /* ============ LANGUAGE SWITCHER ============ */
        .lang-btn {
            display: flex;
            align-items: center;
            gap: .4rem;
            padding: .4rem .65rem;
            background: var(--iron-bg);
            border: 1px solid rgba(201,162,39,.3);
            color: var(--gold-primary);
            font-weight: 700;
            font-size: .78rem;
            clip-path: polygon(6px 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%, 0 6px);
            transition: all .2s ease;
            cursor: pointer;
            white-space: nowrap;
        }

        .lang-btn:hover {
            border-color: var(--gold-primary);
            background: linear-gradient(180deg, rgba(201,162,39,.2), rgba(201,162,39,.05));
        }

        #langLabel {
            display: none;
        }

        @media (min-width: 480px) {
            #langLabel {
                display: inline;
            }
        }

        /* ============ HAMBURGER ============ */
        .hamburger {
            display: block;
            width: 24px;
            height: 2px;
            background: var(--gold-primary);
            position: relative;
            transition: all 0.3s ease;
        }

        .hamburger::before,
        .hamburger::after {
            content: '';
            position: absolute;
            width: 24px;
            height: 2px;
            background: var(--gold-primary);
            left: 0;
            transition: all 0.3s ease;
        }

        .hamburger::before {
            top: -7px;
        }

        .hamburger::after {
            top: 7px;
        }

        .hamburger.active {
            background: transparent;
        }

        .hamburger.active::before {
            transform: rotate(45deg);
            top: 0;
        }

        .hamburger.active::after {
            transform: rotate(-45deg);
            top: 0;
        }

        /* ============ MOBILE NAV ============ */
        @media (max-width: 1023px) {
            header nav.nav-menu {
                display: none;
            }

            header nav.nav-menu.nav-open {
                display: flex !important;
                flex-direction: column;
                position: absolute !important;
                top: 100% !important;
                left: 0 !important;
                right: 0 !important;
                max-height: calc(100vh - 80px);
                overflow-y: auto;
                background: rgba(5,7,11,0.98);
                border-bottom: 1px solid rgba(201,162,39,.35);
                box-shadow: 0 20px 40px rgba(0,0,0,.75);
                padding: 1.5rem;
                gap: .75rem;
                z-index: 10010;
            }

            header nav.nav-menu.nav-open .nav-link {
                width: 100%;
                text-align: center;
                font-size: 1.05rem;
                padding: 1rem;
                white-space: normal;
            }

            .nav-close {
                display: flex !important;
                align-self: flex-end;
                margin-bottom: .5rem;
                z-index: 10015;
            }
        }

        .dropdown-menu.show,
        .lang-options.show {
            display: block !important;
        }
    </style>

    <?php
    // $page_head is a raw, developer-controlled HTML fragment.
    // Do NOT escape it. Do NOT ever populate it from user input.
    echo $page_head;
    ?>
</head>
<body class="<?php echo htmlspecialchars(trim($page_class . ' ' . $page_body_class), ENT_QUOTES, 'UTF-8'); ?>">

    <header class="main-header fixed top-0 left-0 right-0 <?php echo $is_auth_page ? 'transparent-header' : ''; ?>">
        <div class="header-inner max-w-[1600px] mx-auto px-3 md:px-6 py-2 flex items-center justify-between gap-3" style="position: relative;">

            <!-- Yellow Parallelogram -->
            <div class="parallelogram"></div>

            <!-- Logo -->
            <a href="<?php echo $esc_base; ?>" class="flex-shrink-0 transition-transform duration-300 hover:scale-105 hover:drop-shadow-[0_0_12px_rgba(242,207,82,0.5)]" style="position: relative; z-index: 1;">
                <img src="<?php echo $esc_base . $esc_logo; ?>" alt="Server Logo" class="h-12 md:h-16 align-middle">
            </a>

            <!-- Nav Toggle Button (Mobile) -->
            <button class="nav-toggle lg:hidden p-2" aria-label="Toggle navigation" style="z-index: 10025; position: relative;">
                <span class="hamburger"></span>
            </button>

            <!-- Navigation -->
            <nav class="nav-menu flex items-center gap-1.5 flex-wrap lg:flex-nowrap" style="position: relative; z-index: 1;">
                <!-- Close Button (Mobile) -->
                <button class="nav-close hidden bg-red-600/80 border border-red-400 text-white w-10 h-10 rounded-full items-center justify-center hover:bg-red-700 transition text-xl" aria-label="Close navigation">
                    ✖
                </button>

                <a href="<?php echo $esc_base; ?>" class="nav-link <?php echo $page_class === 'home' ? 'active' : ''; ?>">
                    <?php echo translate('nav_home', 'Home'); ?>
                </a>

                <a href="<?php echo $esc_base; ?>how_to_play" class="nav-link <?php echo $page_class === 'how_to_play' ? 'active' : ''; ?>">
                    <?php echo translate('nav_how_to_play', 'How to Play'); ?>
                </a>

                <a href="<?php echo $esc_base; ?>news" class="nav-link <?php echo $page_class === 'news' ? 'active' : ''; ?>">
                    <?php echo translate('nav_news', 'News'); ?>
                </a>

                <a href="<?php echo $esc_base; ?>armory/solo_pvp" class="nav-link <?php echo strpos($page_class, 'armory') !== false ? 'active' : ''; ?>">
                    <?php echo translate('nav_armory', 'Armory'); ?>
                </a>

                <a href="<?php echo $esc_base; ?>shop" class="nav-link <?php echo $page_class === 'shop' ? 'active' : ''; ?>">
                    <?php echo translate('nav_shop', 'Shop'); ?>
                </a>

                <?php if (empty($_SESSION['user_id'])): ?>
                    <a href="<?php echo $esc_base; ?>register" class="nav-link <?php echo $page_class === 'register' ? 'active' : ''; ?>">
                        <?php echo translate('nav_register', 'Register'); ?>
                    </a>

                    <a href="<?php echo $esc_base; ?>login" class="nav-link <?php echo $page_class === 'login' ? 'active' : ''; ?>">
                        <?php echo translate('nav_login', 'Login'); ?>
                    </a>
                <?php else: ?>
                    <a href="<?php echo $esc_base; ?>account" class="nav-link <?php echo $page_class === 'account' ? 'active' : ''; ?>">
                        <?php echo translate('nav_account', 'Account'); ?>
                    </a>
                <?php endif; ?>
            </nav>

            <!-- Right Side Controls -->
            <div class="flex items-center gap-2 md:gap-3" style="position: relative; z-index: 1; min-width: 0;">

                <!-- User Profile (Logged In) -->
                <?php if (!empty($_SESSION['user_id'])): ?>
                    <div class="hidden md:flex items-center gap-2 force-wrap">
                        <span class="badge-gold"><i class="fas fa-coins"></i> <?php echo $points; ?></span>
                        <span class="badge-iron"><i class="fas fa-gem"></i> <?php echo $tokens; ?></span>
                    </div>

                    <div id="profileDropdownContainer">
                        <img src="<?php echo htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8'); ?>" alt="User Profile" class="avatar-frame" id="profileToggle">

                        <div class="wow-dropdown dropdown-menu mt-3 hidden" id="dropdownMenu">
                            <div class="p-3 border-b border-[rgba(201,162,39,0.2)] relative z-10">
                                <div class="flex items-center gap-2.5">
                                    <img src="<?php echo htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8'); ?>" alt="User" class="w-10 h-10 object-cover border border-[#c9a227]" style="clip-path: polygon(15% 0, 85% 0, 100% 15%, 100% 85%, 85% 100%, 15% 100%, 0 85%, 0 15%);">

                                    <div class="flex-1 min-w-0 force-wrap">
                                        <p class="text-[#f2cf5b] font-bold text-xs truncate force-wrap" style="font-family:'Cinzel',serif;">
                                            <?php echo htmlspecialchars($_SESSION['username'] ?? 'User', ENT_QUOTES, 'UTF-8'); ?>
                                        </p>

                                        <p class="text-gray-400 text-[11px] truncate force-wrap"><?php echo $email; ?></p>

                                        <div class="flex gap-1.5 mt-1.5 flex-wrap">
                                            <span class="badge-gold text-[10px] !py-0.5 !px-1.5"><i class="fas fa-coins"></i> <?php echo $points; ?></span>
                                            <span class="badge-iron text-[10px] !py-0.5 !px-1.5"><i class="fas fa-gem"></i> <?php echo $tokens; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="py-1.5 relative z-10">
                                <a href="<?php echo $esc_base; ?>account" class="dropdown-item force-wrap">
                                    <i class="fas fa-user-circle w-4 text-center text-[#f2cf5b]"></i>
                                    <?php echo translate('account_settings', 'Account Settings'); ?>
                                </a>

                                <?php
                                // NOTE: This is a UI hint only, NOT an authorization control.
                                // The admin dashboard MUST enforce its own permission checks server-side.
                                if ($gmlevel > 0 || $role === 'admin' || $role === 'moderator'): ?>
                                    <a href="<?php echo $esc_base; ?>admin/dashboard" class="dropdown-item force-wrap">
                                        <i class="fas fa-cogs w-4 text-center text-[#f2cf5b]"></i>
                                        <?php echo translate('admin_panel', 'Admin Panel'); ?>
                                    </a>
                                <?php endif; ?>

                                <div class="dropdown-divider"></div>

                                <a href="<?php echo $esc_base; ?>vote" class="dropdown-item force-wrap">
                                    <i class="fas fa-vote-yea w-4 text-center text-[#4ade80]"></i>
                                    <?php echo translate('vote', 'Vote'); ?>
                                </a>

                                <div class="dropdown-divider"></div>

                                <a href="<?php echo $esc_base; ?>logout" class="dropdown-item danger force-wrap">
                                    <i class="fas fa-sign-out-alt w-4 text-center"></i>
                                    <?php echo translate('logout', 'Logout'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Language Dropdown -->
                <div id="langDropdownContainer">
                    <div class="lang-btn" id="langSelected">
                        <img src="<?php echo htmlspecialchars($current_lang_flag, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($current_lang_name, ENT_QUOTES, 'UTF-8'); ?>" class="w-5 h-3.5 rounded-sm object-cover" id="flagIcon">
                        <span id="langLabel"><?php echo htmlspecialchars($current_lang_name, ENT_QUOTES, 'UTF-8'); ?></span>
                        <i class="fas fa-chevron-down text-[10px] ml-0.5"></i>
                    </div>

                    <ul class="wow-dropdown lang-options mt-3 hidden list-none p-0 m-0" id="langOptions">
                        <?php foreach ($languages as $code => $lang_data): ?>
                            <li data-value="<?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?>"
                                data-flag="<?php echo htmlspecialchars($lang_data['flag_url'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-name="<?php echo htmlspecialchars($lang_data['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                class="dropdown-item !py-2 !px-3 text-sm force-wrap">
                                <img src="<?php echo htmlspecialchars($lang_data['flag_url'], ENT_QUOTES, 'UTF-8'); ?>"
                                     alt="<?php echo htmlspecialchars($lang_data['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                     class="w-5 h-3.5 rounded-sm object-cover">
                                <span><?php echo htmlspecialchars($lang_data['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <script src="<?php echo $esc_base; ?>assets/js/includes/header.js"></script>