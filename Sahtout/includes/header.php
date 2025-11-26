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
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/header.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=UnifrakturCook:wght@700&display=swap" rel="stylesheet">
    <?php if (file_exists($project_root . "assets/css/{$page_class}.css")): ?>
        <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/<?php echo $page_class; ?>.css">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<style>
    :root {
    --point-wow-gif: url('<?php echo $base_path; ?>img/pointer_wow.gif');
    --hover-wow-gif: url('<?php echo $base_path; ?>img/hover_wow.gif');
}
</style>


<body class="<?php echo $page_class; ?>">
    <header>
        <a href="<?php echo $base_path; ?>"><img src="<?php echo $base_path . $site_logo; ?>" alt="Sahtout Server Logo" height="80"></a>
        <button class="nav-toggle" aria-label="Toggle navigation">
            <span class="hamburger"></span>
        </button>
        <nav class="<?php echo empty($_SESSION['user_id']) ? 'no-session' : ''; ?>">
            <button class="nav-close" aria-label="Close navigation">✖</button>
            <a href=""><?php echo translate('nav_home', 'Home'); ?></a>
            <a href="<?php echo $base_path; ?>how_to_play"><?php echo translate('nav_how_to_play', 'How to Play'); ?></a>
            <a href="<?php echo $base_path; ?>news"><?php echo translate('nav_news', 'News'); ?></a>
            <a href="<?php echo $base_path; ?>armory/solo_pvp"><?php echo translate('nav_armory', 'Armory'); ?></a>
            <a href="<?php echo $base_path; ?>shop"><?php echo translate('nav_shop', 'Shop'); ?></a>
            <?php if (empty($_SESSION['user_id'])): ?>
                <a href="<?php echo $base_path; ?>register" class="register"><?php echo translate('nav_register', 'Register'); ?></a>
                <a href="<?php echo $base_path; ?>login" class="login"><?php echo translate('nav_login', 'Login'); ?></a>
            <?php else: ?>
                <a href="<?php echo $base_path; ?>account"><?php echo translate('nav_account', 'Account'); ?></a>
            <?php endif; ?>
        </nav>
        <?php if (!empty($_SESSION['user_id'])): ?>
            <div class="user-profile session">
                <div class="profile-info">
                    <div class="user-currency">
                        <span class="points"><i class="fas fa-coins"></i> <?php echo $points; ?></span>
                        <span class="tokens"><i class="fas fa-gem"></i> <?php echo $tokens; ?></span>
                    </div>
                </div>
                <div class="profile-dropdown">
                    <img src="<?php echo $avatar; ?>" alt="User Profile" class="user-image" id="profileToggle">
                    <div class="dropdown-menu" id="dropdownMenu">
                        <div class="dropdown-header">
                            <img src="<?php echo $avatar; ?>" alt="User Profile" class="dropdown-image">
                            <div class="user-info">
                                <span class="username"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User', ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="email"><?php echo $email; ?></span>
                                <div class="dropdown-currency">
                                    <span class="points"><i class="fas fa-coins"></i> <?php echo translate('points', 'Points'); ?>: <?php echo $points; ?></span>
                                    <span class="tokens"><i class="fas fa-gem"></i> <?php echo translate('tokens', 'Tokens'); ?>: <?php echo $tokens; ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a style="color: #ffffff;" href="<?php echo $base_path; ?>account" class="dropdown-item">
                            <i class="fas fa-user-circle"></i> <?php echo translate('account_settings', 'Account Settings'); ?>
                        </a>
                        <?php if ($gmlevel > 0 || $role === 'admin' || $role === 'moderator'): ?>
                            <a href="<?php echo $base_path; ?>admin/dashboard" class="dropdown-item admin-panel">
                                <i class="fas fa-cogs"></i> <?php echo translate('admin_panel', 'Admin Panel'); ?>
                            </a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="<?php echo $base_path; ?>vote" class="dropdown-item vote">
                            <i class="fas fa-vote-yea"></i> <?php echo translate('vote', 'Vote'); ?>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="<?php echo $base_path; ?>logout" class="dropdown-item logout">
                            <i class="fas fa-sign-out-alt"></i> <?php echo translate('logout', 'Logout'); ?>
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <div class="lang-dropdown">
            <div class="lang-selected" id="langSelected">
                <img src="<?php echo $current_lang_flag; ?>" alt="<?php echo $current_lang_name; ?>" id="flagIcon">
                <span id="langLabel"><?php echo $current_lang_name; ?></span>
            </div>
            <ul class="lang-options" id="langOptions">
                <li data-value="en" data-flag="<?php echo $languages['en']['flag_url']; ?>">
                    <img src="<?php echo $languages['en']['flag_url']; ?>" alt="English"> English
                </li>
                <li data-value="fr" data-flag="<?php echo $languages['fr']['flag_url']; ?>">
                    <img src="<?php echo $languages['fr']['flag_url']; ?>" alt="French"> French
                </li>
                <li data-value="es" data-flag="<?php echo $languages['es']['flag_url']; ?>">
                    <img src="<?php echo $languages['es']['flag_url']; ?>" alt="Spanish"> Spanish
                </li>
                <li data-value="de" data-flag="<?php echo $languages['de']['flag_url']; ?>">
                    <img src="<?php echo $languages['de']['flag_url']; ?>" alt="German"> German
                </li>
                <li data-value="ru" data-flag="<?php echo $languages['ru']['flag_url']; ?>">
                    <img src="<?php echo $languages['ru']['flag_url']; ?>" alt="Russian"> Russian
                </li>
                <li data-value="pt" data-flag="<?php echo $languages['pt']['flag_url']; ?>">
                    <img src="<?php echo $languages['pt']['flag_url']; ?>" alt="Português"> Português
            </ul>
        </div>
    </header>
    <script src="<?php echo $base_path; ?>assets/js/includes/header.js"></script>
</body>
</html>