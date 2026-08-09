<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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
    // phpcs:disable
    echo "<!-- WARNING: Session not started. Ensure session_start() is called in the parent script. -->\n";
    // phpcs:enable
}

// Debug: Check if session variable is set (visible in source code only)
if (!isset($_SESSION['user_id'])) {
    // phpcs:disable
    echo "<!-- DEBUG: No user session detected. Ensure login script sets \$_SESSION['user_id']. -->\n";
    // phpcs:enable
}

// Ensure $page_class is defined in the including page; default to 'default'
$page_class = isset($page_class) ? $page_class : 'default';

// Get current URL without query string
$currentUrl = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$currentUrl = rtrim($currentUrl, '/');

// Function to generate language URLs
function getLanguageUrl($lang) {
    global $currentUrl;

    // Get current query parameters (excluding the path)
    $query = $_GET; // This contains all current GET parameters

    // Update or add the 'lang' parameter
    $query['lang'] = $lang;

    // Build the new query string
    $queryString = http_build_query($query);

    // Return full URL with updated query
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
    // Check if avatar is stored in session
    if (isset($_SESSION['avatar'])) {
        $avatar_filename = $_SESSION['avatar'] !== '' ? $_SESSION['avatar'] : 'user.jpg';
        $avatar = $base_path . 'img/accountimg/profile_pics/' . $avatar_filename;
    }
    
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
            
            // Check if avatar is valid in profile_avatars
            if (!empty($row_site['avatar'])) {
                $stmt_check = $site_db->prepare("SELECT filename FROM profile_avatars WHERE filename = ? AND active = 1");
                $stmt_check->bind_param('s', $row_site['avatar']);
                $stmt_check->execute();
                $check_result = $stmt_check->get_result();
                if ($check_result->num_rows > 0) {
                    $avatar = $base_path . 'img/accountimg/profile_pics/' . htmlspecialchars($row_site['avatar'], ENT_QUOTES, 'UTF-8');
                } else {
                    $avatar = $base_path . 'img/accountimg/profile_pics/user.jpg';
                }
                $stmt_check->close();
            } else {
                $avatar = $base_path . 'img/accountimg/profile_pics/user.jpg';
            }
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

$current_lang_name = $languages[$current_lang]['name'];
$current_lang_code = $current_lang;

// Fallback flag image if not found
$fallback_flag_url = $base_path . 'languages/flags/world.png';
$fallback_flag_path = $project_root . 'languages/flags/world.png';
foreach ($languages as $code => &$lang_data) {
    // Check if the flag file exists on the filesystem
    if (!file_exists($lang_data['flag_path'])) {
        error_log("Flag image not found: {$lang_data['flag_path']}. Using fallback: {$fallback_flag_url}");
        $lang_data['flag_url'] = $fallback_flag_url;
        $lang_data['flag_path'] = $fallback_flag_path;
    }
}
$current_lang_flag = $languages[$current_lang]['flag_url'];

// Check if on auth page for transparent header
$is_auth_page = in_array($page_class, ['login', 'register']);
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?php echo $base_path; ?>">
    <?php if ($page_class === "how_to_play"): ?>
        <title><?php echo $site_title_name . translate('how_to_play_title', 'How to Play');?> </title> 
    <?php endif; ?>
    <link rel="icon" href="<?php echo $base_path . $site_logo; ?>" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=UnifrakturCook:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<style>
    /* ONLY what Tailwind CANNOT do */
    :root {
        --point-wow-gif: url('<?php echo $base_path; ?>img/pointer_wow.gif');
        --hover-wow-gif: url('<?php echo $base_path; ?>img/hover_wow.gif');
    }
    
    body {
        cursor: var(--point-wow-gif) 16 16, auto;
        padding-top: 112px;
    }
    
    /* Transparent header for auth pages */
    header.transparent-header {
        background: rgba(0, 0, 0, 0.33) !important;
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
    }
    
    /* Nav toggle - hamburger icon (Tailwind can't do pseudo-elements easily) */
    .hamburger {
        display: block;
        width: 25px;
        height: 3px;
        background: #ffd700;
        position: relative;
        transition: all 0.3s ease;
    }
    .hamburger::before,
    .hamburger::after {
        content: '';
        position: absolute;
        width: 25px;
        height: 3px;
        background: #ffd700;
        left: 0;
        transition: all 0.3s ease;
    }
    .hamburger::before {
        top: -8px;
    }
    .hamburger::after {
        top: 8px;
    }
    
    /* Mobile nav open state */
    @media (max-width: 768px) {
        header nav {
            display: none;
        }
        header nav.nav-open {
            display: flex !important;
        }
        body {
            padding-top: 96px;
        }
        .nav-toggle {
            display: block !important;
        }
        .nav-close {
            display: block !important;
        }
    }
    
    @media (min-width: 769px) {
        .nav-close {
            display: none !important;
        }
    }
</style>

<body class="<?php echo $page_class; ?>">
    <header class="fixed top-0 left-0 right-0 z-[1000] flex items-center justify-between px-4 md:px-8 py-4 border-b-3 border-[#1b9bf0] shadow-[0_4px_15px_rgba(0,0,50,0.5)] <?php echo $is_auth_page ? 'transparent-header' : 'bg-black/30'; ?>">
        <!-- Logo -->
        <a href="<?php echo $base_path; ?>" class="transition-transform duration-300 hover:scale-105 hover:drop-shadow-[0_0_8px_rgba(52,152,219,0.7)]">
            <img src="<?php echo $base_path . $site_logo; ?>" alt="Sahtout Server Logo" class="h-20 align-middle">
        </a>

        <!-- Nav Toggle Button (Mobile) -->
        <button class="nav-toggle hidden md:hidden bg-transparent border-none cursor-pointer p-2 z-[1002]" aria-label="Toggle navigation">
            <span class="hamburger"></span>
        </button>

        <!-- Navigation -->
        <nav class="flex items-center gap-2 flex-wrap max-md:flex-col max-md:w-full max-md:absolute max-md:top-full max-md:left-0 max-md:p-4 max-md:bg-gradient-to-br max-md:from-[rgba(10,10,10,0.8)] max-md:to-[rgba(26,10,10,0.8)] max-md:shadow-[0_4px_15px_rgba(52,152,219,0.5)] max-md:border-b-3 max-md:border-[#1b9bf0] <?php echo empty($_SESSION['user_id']) ? 'max-md:mx-auto' : ''; ?>">
            <!-- Close Button (Mobile) -->
            <button class="nav-close hidden max-md:block bg-[#e74c3c] border-none text-white text-xl p-2 rounded-full cursor-pointer transition-all duration-300 hover:bg-[#c0392b] hover:scale-110 hover:shadow-[0_2px_8px_rgba(231,76,60,0.5)]" aria-label="Close navigation">✖</button>
            
            <a href="<?php echo $base_path; ?>" class="font-['UnifrakturCook',sans-serif] text-white text-base font-bold px-3 py-1.5 rounded border border-[#1b9bf0] bg-gradient-to-br from-[rgba(27,155,240,0.85)] to-[rgba(25,158,185,0.75)] shadow-[0_2px_5px_rgba(0,0,0,0.3)] transition-all duration-300 hover:translate-y-[-2px] hover:shadow-[0_4px_12px_rgba(52,152,219,0.6)] hover:bg-gradient-to-br hover:from-[rgba(41,128,185,0.9)] hover:to-[rgba(31,97,141,0.85)] max-md:w-4/5 max-md:text-center max-md:my-2 max-md:px-4 max-md:py-2">
                <?php echo translate('nav_home', 'Home'); ?>
            </a>
            <a href="<?php echo $base_path; ?>how_to_play" class="font-['UnifrakturCook',sans-serif] text-white text-base font-bold px-3 py-1.5 rounded border border-[#1b9bf0] bg-gradient-to-br from-[rgba(27,155,240,0.85)] to-[rgba(25,158,185,0.75)] shadow-[0_2px_5px_rgba(0,0,0,0.3)] transition-all duration-300 hover:translate-y-[-2px] hover:shadow-[0_4px_12px_rgba(52,152,219,0.6)] hover:bg-gradient-to-br hover:from-[rgba(41,128,185,0.9)] hover:to-[rgba(31,97,141,0.85)] max-md:w-4/5 max-md:text-center max-md:my-2 max-md:px-4 max-md:py-2">
                <?php echo translate('nav_how_to_play', 'How to Play'); ?>
            </a>
            <a href="<?php echo $base_path; ?>news" class="font-['UnifrakturCook',sans-serif] text-white text-base font-bold px-3 py-1.5 rounded border border-[#1b9bf0] bg-gradient-to-br from-[rgba(27,155,240,0.85)] to-[rgba(25,158,185,0.75)] shadow-[0_2px_5px_rgba(0,0,0,0.3)] transition-all duration-300 hover:translate-y-[-2px] hover:shadow-[0_4px_12px_rgba(52,152,219,0.6)] hover:bg-gradient-to-br hover:from-[rgba(41,128,185,0.9)] hover:to-[rgba(31,97,141,0.85)] max-md:w-4/5 max-md:text-center max-md:my-2 max-md:px-4 max-md:py-2">
                <?php echo translate('nav_news', 'News'); ?>
            </a>
            <a href="<?php echo $base_path; ?>armory/solo_pvp" class="font-['UnifrakturCook',sans-serif] text-white text-base font-bold px-3 py-1.5 rounded border border-[#1b9bf0] bg-gradient-to-br from-[rgba(27,155,240,0.85)] to-[rgba(25,158,185,0.75)] shadow-[0_2px_5px_rgba(0,0,0,0.3)] transition-all duration-300 hover:translate-y-[-2px] hover:shadow-[0_4px_12px_rgba(52,152,219,0.6)] hover:bg-gradient-to-br hover:from-[rgba(41,128,185,0.9)] hover:to-[rgba(31,97,141,0.85)] max-md:w-4/5 max-md:text-center max-md:my-2 max-md:px-4 max-md:py-2">
                <?php echo translate('nav_armory', 'Armory'); ?>
            </a>
            <a href="<?php echo $base_path; ?>shop" class="font-['UnifrakturCook',sans-serif] text-white text-base font-bold px-3 py-1.5 rounded border border-[#1b9bf0] bg-gradient-to-br from-[rgba(27,155,240,0.85)] to-[rgba(25,158,185,0.75)] shadow-[0_2px_5px_rgba(0,0,0,0.3)] transition-all duration-300 hover:translate-y-[-2px] hover:shadow-[0_4px_12px_rgba(52,152,219,0.6)] hover:bg-gradient-to-br hover:from-[rgba(41,128,185,0.9)] hover:to-[rgba(31,97,141,0.85)] max-md:w-4/5 max-md:text-center max-md:my-2 max-md:px-4 max-md:py-2">
                <?php echo translate('nav_shop', 'Shop'); ?>
            </a>
            <?php if (empty($_SESSION['user_id'])): ?>
                <a href="<?php echo $base_path; ?>register" class="register font-['UnifrakturCook',sans-serif] text-white text-base font-bold px-3 py-1.5 rounded border border-[#1b9bf0] bg-gradient-to-br from-[rgba(27,155,240,0.85)] to-[rgba(25,158,185,0.75)] shadow-[0_2px_5px_rgba(0,0,0,0.3)] transition-all duration-300 hover:translate-y-[-2px] hover:shadow-[0_4px_12px_rgba(52,152,219,0.6)] hover:bg-gradient-to-br hover:from-[rgba(41,128,185,0.9)] hover:to-[rgba(31,97,141,0.85)] max-md:w-4/5 max-md:text-center max-md:my-2 max-md:px-4 max-md:py-2 max-md:mr-0">
                    <?php echo translate('nav_register', 'Register'); ?>
                </a>
                <a href="<?php echo $base_path; ?>login" class="login font-['UnifrakturCook',sans-serif] text-white text-base font-bold px-3 py-1.5 rounded border border-[#1b9bf0] bg-gradient-to-br from-[rgba(27,155,240,0.85)] to-[rgba(25,158,185,0.75)] shadow-[0_2px_5px_rgba(0,0,0,0.3)] transition-all duration-300 hover:translate-y-[-2px] hover:shadow-[0_4px_12px_rgba(52,152,219,0.6)] hover:bg-gradient-to-br hover:from-[rgba(41,128,185,0.9)] hover:to-[rgba(31,97,141,0.85)] max-md:w-4/5 max-md:text-center max-md:my-2 max-md:px-4 max-md:py-2 max-md:mr-0">
                    <?php echo translate('nav_login', 'Login'); ?>
                </a>
            <?php else: ?>
                <a href="<?php echo $base_path; ?>account" class="font-['UnifrakturCook',sans-serif] text-white text-base font-bold px-3 py-1.5 rounded border border-[#1b9bf0] bg-gradient-to-br from-[rgba(27,155,240,0.85)] to-[rgba(25,158,185,0.75)] shadow-[0_2px_5px_rgba(0,0,0,0.3)] transition-all duration-300 hover:translate-y-[-2px] hover:shadow-[0_4px_12px_rgba(52,152,219,0.6)] hover:bg-gradient-to-br hover:from-[rgba(41,128,185,0.9)] hover:to-[rgba(31,97,141,0.85)] max-md:w-4/5 max-md:text-center max-md:my-2 max-md:px-4 max-md:py-2">
                    <?php echo translate('nav_account', 'Account'); ?>
                </a>
            <?php endif; ?>
        </nav>

        <!-- User Profile (Logged In) -->
        <?php if (!empty($_SESSION['user_id'])): ?>
            <div class="user-profile flex items-center gap-4 ml-4 relative">
                <div class="profile-info flex flex-col items-end max-md:hidden">
                    <div class="user-currency flex gap-2 px-3 py-1 rounded shadow-[0_2px_6px_rgba(52,152,219,0.4)] text-sm">
                        <span class="points inline-flex items-center gap-1.5 px-2 py-1 rounded text-sm font-semibold bg-gradient-to-br from-[#1b9bf0] to-[#199fb9] text-[#ffee00] border border-[#1b9bf0] transition-all duration-300 hover:translate-y-[-1px] hover:shadow-[0_2px_6px_rgba(52,152,219,0.5)]">
                            <i class="fas fa-coins"></i> <?php echo $points; ?>
                        </span>
                        <span class="tokens inline-flex items-center gap-1.5 px-2 py-1 rounded text-sm font-semibold bg-gradient-to-br from-[#9b59b6] to-[#8e44ad] text-white border border-[#1b9bf0] transition-all duration-300 hover:translate-y-[-1px] hover:shadow-[0_2px_6px_rgba(155,89,182,0.5)]">
                            <i class="fas fa-gem"></i> <?php echo $tokens; ?>
                        </span>
                    </div>
                </div>
                <div class="profile-dropdown relative inline-block">
                    <img src="<?php echo $avatar; ?>" alt="User Profile" class="user-image w-15 h-15 rounded-full border-2 border-[#1b9bf0] shadow-[0_2px_5px_rgba(52,152,219,0.5)] object-cover cursor-pointer transition-all duration-300 hover:scale-110 hover:shadow-[0_4px_10px_rgba(52,152,219,0.6)] max-md:mr-[-20px]" id="profileToggle">
                    <div class="dropdown-menu absolute right-0 top-full bg-gradient-to-br from-[rgba(10,10,10,0.8)] to-[rgba(26,10,10,0.8)] border-2 border-[#1b9bf0] rounded-lg py-2 z-[1001] hidden shadow-[0_6px_15px_rgba(52,152,219,0.6)] animate-[fadeIn_0.3s_ease-in-out] min-w-[220px] max-md:right-[-50px] max-md:w-[250px]" id="dropdownMenu">
                        <div class="dropdown-header flex items-center px-4 py-3 bg-[rgba(52,152,219,0.1)] border-b border-[#1b9bf0]">
                            <img src="<?php echo $avatar; ?>" alt="User Profile" class="dropdown-image w-12.5 h-12.5 rounded-full border-2 border-[#1b9bf0] shadow-[0_2px_5px_rgba(52,152,219,0.5)] mr-4 max-md:w-10 max-md:h-10">
                            <div class="user-info flex flex-col flex-1 text-white">
                                <span class="username font-semibold text-base max-md:text-base"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User', ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="email text-sm text-gray-300 max-md:text-sm"><?php echo $email; ?></span>
                                <div class="dropdown-currency flex flex-col gap-1 mt-2 text-sm max-md:text-sm">
                                    <span class="points flex items-center gap-2 text-white"><i class="fas fa-coins"></i> <?php echo translate('points', 'Points'); ?>: <?php echo $points; ?></span>
                                    <span class="tokens flex items-center gap-2 text-[#9b59b6]"><i class="fas fa-gem"></i> <?php echo translate('tokens', 'Tokens'); ?>: <?php echo $tokens; ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="dropdown-divider h-[1px] bg-gradient-to-r from-transparent via-[#1b9bf0] to-transparent my-2"></div>
                        <a href="<?php echo $base_path; ?>account" class="dropdown-item flex items-center px-4 py-3 text-white no-underline text-base transition-all duration-300 hover:bg-gradient-to-br hover:from-[rgba(41,128,185,0.9)] hover:to-[rgba(31,97,141,0.7)] max-md:text-sm max-md:px-3 max-md:py-2">
                            <i class="fas fa-user-circle mr-3 w-5 text-center"></i> <?php echo translate('account_settings', 'Account Settings'); ?>
                        </a>
                        <?php if ($gmlevel > 0 || $role === 'admin' || $role === 'moderator'): ?>
                            <a href="<?php echo $base_path; ?>admin/dashboard" class="dropdown-item admin-panel flex items-center px-4 py-3 text-white no-underline text-base transition-all duration-300 hover:bg-gradient-to-br hover:from-[#1b9bf0] hover:to-[#199fb9] max-md:text-sm max-md:px-3 max-md:py-2">
                                <i class="fas fa-cogs mr-3 w-5 text-center"></i> <?php echo translate('admin_panel', 'Admin Panel'); ?>
                            </a>
                        <?php endif; ?>
                        <div class="dropdown-divider h-[1px] bg-gradient-to-r from-transparent via-[#1b9bf0] to-transparent my-2"></div>
                        <a href="<?php echo $base_path; ?>vote" class="dropdown-item vote flex items-center px-4 py-3 text-white no-underline text-base transition-all duration-300 hover:bg-gradient-to-br hover:from-[#2ecc71] hover:to-[#27ae60] max-md:text-sm max-md:px-3 max-md:py-2">
                            <i class="fas fa-vote-yea mr-3 w-5 text-center"></i> <?php echo translate('vote', 'Vote'); ?>
                        </a>
                        <div class="dropdown-divider h-[1px] bg-gradient-to-r from-transparent via-[#1b9bf0] to-transparent my-2"></div>
                        <a href="<?php echo $base_path; ?>logout" class="dropdown-item logout flex items-center px-4 py-3 text-[#ff6b6b] no-underline text-base transition-all duration-300 hover:bg-gradient-to-br hover:from-[#e74c3c] hover:to-[#c0392b] hover:text-white max-md:text-sm max-md:px-3 max-md:py-2">
                            <i class="fas fa-sign-out-alt mr-3 w-5 text-center"></i> <?php echo translate('logout', 'Logout'); ?>
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Language Dropdown -->
        <div class="lang-dropdown relative inline-block w-[150px] font-['Cinzel',serif] max-md:absolute max-md:top-2 max-md:right-2 max-md:w-10">
            <div class="lang-selected flex items-center gap-2 px-3 py-2 border-2 border-[#1b9bf0] rounded-lg cursor-pointer bg-gradient-to-br from-[rgba(27,155,240,0.8)] to-[rgba(25,158,185,0.6)] text-white shadow-[0_0_12px_rgba(52,152,219,0.7)] transition-all duration-300 hover:scale-105 hover:shadow-[0_0_18px_rgba(52,152,219,0.8)] max-md:p-1 max-md:justify-center" id="langSelected">
                <img src="<?php echo $current_lang_flag; ?>" alt="<?php echo $current_lang_name; ?>" class="w-5 h-3.5 rounded max-md:w-4 max-md:h-3" id="flagIcon">
                <span id="langLabel" class="max-md:hidden"><?php echo $current_lang_name; ?></span>
            </div>
            <ul class="lang-options absolute top-full right-0 w-full bg-gradient-to-br from-[rgba(10,10,10,0.8)] to-[rgba(26,10,10,0.8)] border-2 border-[#1b9bf0] rounded-lg overflow-hidden shadow-[0_0_20px_rgba(52,152,219,0.7)] list-none m-1 p-0 hidden transition-all duration-300 z-[1000] max-md:mt-3 max-md:w-[124px]" id="langOptions">
                <li data-value="en" data-flag="<?php echo $languages['en']['flag_url']; ?>" class="flex items-center gap-2.5 px-3 py-2.5 text-white cursor-pointer transition-all duration-300 hover:bg-gradient-to-br hover:from-[rgba(41,128,185,0.9)] hover:to-[rgba(31,97,141,0.7)] hover:translate-x-1 max-md:text-sm max-md:px-1.5 max-md:py-1">
                    <img src="<?php echo $languages['en']['flag_url']; ?>" alt="English" class="w-5 h-3.5 rounded max-md:w-4 max-md:h-3 max-md:mr-1"> English
                </li>
                <li data-value="fr" data-flag="<?php echo $languages['fr']['flag_url']; ?>" class="flex items-center gap-2.5 px-3 py-2.5 text-white cursor-pointer transition-all duration-300 hover:bg-gradient-to-br hover:from-[rgba(41,128,185,0.9)] hover:to-[rgba(31,97,141,0.7)] hover:translate-x-1 max-md:text-sm max-md:px-1.5 max-md:py-1">
                    <img src="<?php echo $languages['fr']['flag_url']; ?>" alt="French" class="w-5 h-3.5 rounded max-md:w-4 max-md:h-3 max-md:mr-1"> French
                </li>
                <li data-value="es" data-flag="<?php echo $languages['es']['flag_url']; ?>" class="flex items-center gap-2.5 px-3 py-2.5 text-white cursor-pointer transition-all duration-300 hover:bg-gradient-to-br hover:from-[rgba(41,128,185,0.9)] hover:to-[rgba(31,97,141,0.7)] hover:translate-x-1 max-md:text-sm max-md:px-1.5 max-md:py-1">
                    <img src="<?php echo $languages['es']['flag_url']; ?>" alt="Spanish" class="w-5 h-3.5 rounded max-md:w-4 max-md:h-3 max-md:mr-1"> Spanish
                </li>
                <li data-value="de" data-flag="<?php echo $languages['de']['flag_url']; ?>" class="flex items-center gap-2.5 px-3 py-2.5 text-white cursor-pointer transition-all duration-300 hover:bg-gradient-to-br hover:from-[rgba(41,128,185,0.9)] hover:to-[rgba(31,97,141,0.7)] hover:translate-x-1 max-md:text-sm max-md:px-1.5 max-md:py-1">
                    <img src="<?php echo $languages['de']['flag_url']; ?>" alt="German" class="w-5 h-3.5 rounded max-md:w-4 max-md:h-3 max-md:mr-1"> German
                </li>
                <li data-value="ru" data-flag="<?php echo $languages['ru']['flag_url']; ?>" class="flex items-center gap-2.5 px-3 py-2.5 text-white cursor-pointer transition-all duration-300 hover:bg-gradient-to-br hover:from-[rgba(41,128,185,0.9)] hover:to-[rgba(31,97,141,0.7)] hover:translate-x-1 max-md:text-sm max-md:px-1.5 max-md:py-1">
                    <img src="<?php echo $languages['ru']['flag_url']; ?>" alt="Russian" class="w-5 h-3.5 rounded max-md:w-4 max-md:h-3 max-md:mr-1"> Russian
                </li>
                <li data-value="pt" data-flag="<?php echo $languages['pt']['flag_url']; ?>" class="flex items-center gap-2.5 px-3 py-2.5 text-white cursor-pointer transition-all duration-300 hover:bg-gradient-to-br hover:from-[rgba(41,128,185,0.9)] hover:to-[rgba(31,97,141,0.7)] hover:translate-x-1 max-md:text-sm max-md:px-1.5 max-md:py-1">
                    <img src="<?php echo $languages['pt']['flag_url']; ?>" alt="Português" class="w-5 h-3.5 rounded max-md:w-4 max-md:h-3 max-md:mr-1"> Português
                </li>
                <li data-value="cn" data-flag="<?php echo $languages['cn']['flag_url']; ?>" class="flex items-center gap-2.5 px-3 py-2.5 text-white cursor-pointer transition-all duration-300 hover:bg-gradient-to-br hover:from-[rgba(41,128,185,0.9)] hover:to-[rgba(31,97,141,0.7)] hover:translate-x-1 max-md:text-sm max-md:px-1.5 max-md:py-1">
                    <img src="<?php echo $languages['cn']['flag_url']; ?>" alt="中文" class="w-5 h-3.5 rounded max-md:w-4 max-md:h-3 max-md:mr-1"> 中文
                </li>
            </ul>
        </div>
    </header>

    <style>
        /* Animation for dropdown */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .dropdown-menu.show {
            display: block !important;
        }
        .lang-options.show {
            display: block !important;
        }
    </style>
    <script src="<?php echo $base_path; ?>assets/js/includes/header.js"></script>
</body>
</html>