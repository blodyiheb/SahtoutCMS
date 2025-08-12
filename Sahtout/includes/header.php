<?php
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access to this file is not allowed.');
}

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
// Base path for XAMPP setup at C:\xampp\htdocs\Sahtout
$base_path = '/sahtout/';

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
    
    // Query database for avatar, points, tokens, email, and role
    $stmt = $site_db->prepare("
        SELECT uc.points, uc.tokens, uc.avatar, uc.role, a.email 
        FROM sahtout_site.user_currencies uc 
        JOIN acore_auth.account a ON uc.account_id = a.id 
        WHERE uc.account_id = ?
    ");
    if ($stmt) {
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $points = (int)$row['points'];
            $tokens = (int)$row['tokens'];
            $email = htmlspecialchars($row['email'] ?? 'user@example.com', ENT_QUOTES, 'UTF-8');
            $role = $row['role'] ?? 'player';
            // Check if avatar is valid in profile_avatars
            if (!empty($row['avatar'])) {
                $stmt_check = $site_db->prepare("SELECT filename FROM sahtout_site.profile_avatars WHERE filename = ? AND active = 1");
                $stmt_check->bind_param('s', $row['avatar']);
                $stmt_check->execute();
                $check_result = $stmt_check->get_result();
                if ($check_result->num_rows > 0) {
                    $avatar = $base_path . 'img/accountimg/profile_pics/' . htmlspecialchars($row['avatar'], ENT_QUOTES, 'UTF-8');
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
        $stmt->close();
    } else {
        error_log("Failed to prepare statement for fetching user data in header.php.");
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
        error_log("Failed to prepare statement for fetching gmlevel in header.php.");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sahtout Server - <?php echo ucfirst($page_class); ?></title>
    <base href="<?php echo $base_path; ?>">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/header.css">
    <link href="https://fonts.googleapis.com/css2?family=UnifrakturCook:wght@700&display=swap" rel="stylesheet">
    <?php if (file_exists(__DIR__ . "/../assets/css/{$page_class}.css")): ?>
        <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/<?php echo $page_class; ?>.css">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<style>
header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: linear-gradient(135deg, rgba(0, 0, 30, 0.57) 0%, rgba(10, 10, 40, 0.36) 100%);
    padding: 1rem 2rem;
    box-shadow: 0 4px 15px rgba(0, 0, 50, 0.5);
    border-bottom: 2px solid #ecf0f1;
    position: relative;
}
body{
     cursor: url('/Sahtout/img/pointer_wow.gif')16 16, auto;
}

header img {
    height: 80px;
    transition: transform 0.3s ease, filter 0.3s ease;
}

header img:hover {
    transform: scale(1.05);
    filter: drop-shadow(0 0 8px rgba(52, 152, 219, 0.5));
    cursor: url('/Sahtout/img/hover_wow.gif')16 16, auto;
}

header nav {
    display: flex;
    gap: 1.5rem;
    align-items: center;
}

header nav a {
    text-decoration: none;
    font-size: 1.5rem;
    font-weight: 600;
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    background: linear-gradient(135deg, #1b9bf0ff 0%, rgba(25, 159, 185, 0.58));
    color: #fff;
    border: 2px solid #ecf0f1;
    transition: all 0.3s ease;
    position: relative;
    margin-right: 25px;
    cursor: url('/Sahtout/img/hover_wow.gif')16 16, auto;
}

header nav a:hover {
    background: linear-gradient(135deg, #2980b9 0%, #1f618d 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(52, 152, 219, 0.5);
}

header nav a.active {
    background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);
    border-color: #d35400;
    box-shadow: 0 4px 12px rgba(211, 84, 0, 0.5);
}

.nav-toggle {
    display: none;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
}

.nav-toggle .hamburger {
    display: block;
    width: 30px;
    height: 3px;
    background: #ecf0f1;
    position: relative;
    transition: all 0.3s ease;
}

.nav-toggle .hamburger::before,
.nav-toggle .hamburger::after {
    content: '';
    position: absolute;
    width: 30px;
    height: 3px;
    background: #ecf0f1;
    transition: all 0.3s ease;
}

.nav-toggle .hamburger::before {
    top: -10px;
}

.nav-toggle .hamburger::after {
    bottom: -10px;
}

.nav-toggle.nav-open .hamburger {
    background: transparent;
}

.nav-toggle.nav-open .hamburger::before {
    transform: rotate(45deg);
    top: 0;
    background: #3498db;
}

.nav-toggle.nav-open .hamburger::after {
    transform: rotate(-45deg);
    bottom: 0;
    background: #3498db;
}

.nav-close {
    position: absolute;
    top: 0.3rem;
    right: 1rem;
    background: #e74c3c;
    border: none;
    color: #fff;
    font-size: 1.2rem;
    padding: 0.5rem;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s ease;
}

.nav-close:hover {
    background: #c0392b;
    transform: scale(1.1);
    box-shadow: 0 2px 8px rgba(231, 76, 60, 0.5);
}

/* User profile styles */
.user-profile {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-left: 1rem;
    position: relative;
}

.profile-info {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.user-currency {
    display: flex;
    gap: 1rem;
    padding: 0.6rem 1.2rem;
    background: linear-gradient(135deg, rgba(0, 0, 30, 0.8) 0%, rgba(10, 10, 40, 0.9) 100%);
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0, 0, 50, 0.5);
}

.user-currency span {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    font-size: 1.1rem;
    font-weight: 600;
    transition: all 0.3s ease;
}

.user-currency .points {
    background: linear-gradient(135deg, #ffd700 0%, #f1c40f 100%);
    color: #1a1a1a;
    border: 2px solid #ecf0f1;
}

.user-currency .points:hover {
    background: linear-gradient(135deg, #e6c200 0%, #d4ac0d 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(241, 196, 15, 0.5);
}

.user-currency .tokens {
    background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
    color: #fff;
    border: 2px solid #8e44ad;
}

.user-currency .tokens:hover {
    background: linear-gradient(135deg, #8e44ad 0%, #7d3c98 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(155, 89, 182, 0.5);
}

/* Dropdown styles */
.profile-dropdown {
    position: relative;
    display: inline-block;
}

.user-image {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    border: 2px solid #ecf0f1;
    box-shadow: 0 2px 5px rgba(0, 0, 50, 0.3);
    object-fit: cover;
    cursor: pointer;
    transition: all 0.3s ease;
}

.user-image:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 10px rgba(52, 152, 219, 0.5);
}

.dropdown-menu {
    position: absolute;
    right: 0;
    top: 100%;
    background: linear-gradient(135deg, rgba(0, 0, 30, 0.9) 0%, rgba(10, 10, 40, 0.95) 100%);
    border: 2px solid #ecf0f1;
    border-radius: 8px;
    padding: 0.5rem 0;
    z-index: 1001;
    display: none;
    box-shadow: 0 6px 15px rgba(0, 0, 50, 0.5);
    animation: fadeIn 0.3s ease-in-out;
}

.dropdown-menu.show {
    display: block;
}

.dropdown-header {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    background: rgba(52, 152, 219, 0.1);
    border-bottom: 1px solid #ecf0f1;
}

.dropdown-image {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    border: 2px solid #ecf0f1;
    box-shadow: 0 2px 5px rgba(0, 0, 50, 0.3);
    margin-right: 1rem;
}

.user-info {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    color: #fff;
}

.user-info .username {
    font-weight: 600;
    font-size: 1.1rem;
}

.user-info .email {
    font-size: 0.9rem;
    color: #ccc;
}

.dropdown-currency {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    margin-top: 0.5rem;
    font-size: 1rem;
}

.dropdown-currency span {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.dropdown-currency .points {
    color: #ffd700;
}

.dropdown-currency .tokens {
    color: #9b59b6;
}

.dropdown-divider {
    height: 1px;
    background: linear-gradient(to right, transparent, #ecf0f1, transparent);
    margin: 0.5rem 0;
}

.dropdown-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    color: #8c17beff;
    text-decoration: none;
    font-size: 1.3rem;
    transition: all 0.3s ease;
}

.dropdown-item:hover {
    background: linear-gradient(135deg, #158edfff 0%, #0544ceff 100%);
    color: #ffffffff;
}

.dropdown-item i {
    margin-right: 0.75rem;
    width: 20px;
    text-align: center;
}

.dropdown-item.admin-panel {
    color: #f1c40f;
}

.dropdown-item.admin-panel:hover {
    background: linear-gradient(135deg, #f1c40f 0%, #e67e22 100%);
    color: #fff;
}

.dropdown-item.logout {
    color: #ff6b6b;
}

.dropdown-item.logout:hover {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    color: #fff;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Desktop adjustments */
@media (min-width: 769px) {
    .nav-close {
        display: none !important;
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    header {
        flex-wrap: wrap;
        padding: 1rem;
    }

    header nav {
        display: none;
        width: 100%;
        flex-direction: column;
        background: linear-gradient(135deg, rgba(0, 0, 30, 0.9) 0%, rgba(10, 10, 40, 0.95) 100%);
        position: absolute;
        top: 100%;
        left: 0;
        padding: 1rem;
        box-shadow: 0 4px 15px rgba(0, 0, 50, 0.5);
        border-bottom: 2px solid #ecf0f1;
    }

    header nav.nav-open {
        display: flex;
    }

    header nav a {
        font-size: 1rem;
        padding: 0.5rem 1rem;
        margin: 0.5rem 0;
    }

    .nav-toggle {
        display: block;
        z-index: 1002;
    }

    .nav-close {
        display: block;
        margin-right: -10px;
    }

    .user-profile {
        position: absolute;
        top: 1.5rem;
        right: 3.5rem;
    }

    .profile-info {
        display: none;
    }

    .dropdown-menu {
        right: -50px;
        width: 250px;
    }

    .user-currency {
        padding: 0.5rem 1rem;
    }

    .user-currency span {
        font-size: 1rem;
        padding: 0.3rem 0.6rem;
    }

    .dropdown-header {
        padding: 0.5rem 0.75rem;
    }

    .dropdown-image {
        width: 40px;
        height: 40px;
    }

    .user-info .username {
        font-size: 1rem;
    }

    .user-info .email {
        font-size: 0.8rem;
    }

    .dropdown-currency {
        font-size: 0.9rem;
    }

    .dropdown-item {
        font-size: 0.9rem;
        padding: 0.5rem 0.75rem;
    }
}
</style>
<body class="<?php echo $page_class; ?>">
    <header>
        <a href="<?php echo $base_path; ?>pages/home.php"><img src="<?php echo $base_path; ?>img/logo.png" alt="Sahtout Server Logo" height="80"></a>
        <button class="nav-toggle" aria-label="Toggle navigation">
            <span class="hamburger"></span>
        </button>
        <nav>
            <button class="nav-close" aria-label="Close navigation">✖</button>
            <a href="<?php echo $base_path; ?>pages/home.php">Home</a>
            <a href="<?php echo $base_path; ?>pages/how-to-play.php">How to Play</a>
            <a href="<?php echo $base_path; ?>pages/news.php">News</a>
            <a href="<?php echo $base_path; ?>pages/armory">armory</a>
            <a href="<?php echo $base_path; ?>pages/shop.php">Shop</a>
            <?php if (empty($_SESSION['user_id'])): ?>
                <a href="<?php echo $base_path; ?>pages/register.php">Register</a>
                <a href="<?php echo $base_path; ?>pages/login.php">Login</a>
            <?php else: ?>
                <a href="<?php echo $base_path; ?>pages/account.php">Account</a>
            <?php endif; ?>
        </nav>
        <?php if (!empty($_SESSION['user_id'])): ?>
            <div class="user-profile">
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
                                    <span class="points"><i class="fas fa-coins"></i> Points: <?php echo $points; ?></span>
                                    <span class="tokens"><i class="fas fa-gem"></i> Tokens: <?php echo $tokens; ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a style="color: #8c17beff;" href="<?php echo $base_path; ?>pages/account.php" class="dropdown-item">
                            <i class="fas fa-user-circle"></i> Account Settings
                        </a>
                        <?php if ($gmlevel > 0 || $role === 'admin' || $role === 'moderator'): ?>
                            <a href="<?php echo $base_path; ?>admin/" class="dropdown-item admin-panel">
                                <i class="fas fa-cogs"></i> Admin Panel
                            </a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="<?php echo $base_path; ?>pages/logout.php" class="dropdown-item logout">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </header>
    <script>
        // Mobile menu toggle
        const toggleButton = document.querySelector('.nav-toggle');
        const closeButton = document.querySelector('.nav-close');
        const nav = document.querySelector('header nav');

        toggleButton.addEventListener('click', () => {
            nav.classList.toggle('nav-open');
        });

        closeButton.addEventListener('click', () => {
            nav.classList.remove('nav-open');
        });

        // Profile dropdown toggle
        const profileToggle = document.getElementById('profileToggle');
        const dropdownMenu = document.getElementById('dropdownMenu');

        if (profileToggle && dropdownMenu) {
            profileToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdownMenu.classList.toggle('show');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.profile-dropdown')) {
                    dropdownMenu.classList.remove('show');
                }
            });

            // Handle viewport changes
            function handleViewportChange() {
                if (window.matchMedia('(min-width: 769px)').matches) {
                    // Desktop view - hide dropdown
                    dropdownMenu.classList.remove('show');
                }
            }

            // Add event listener for viewport changes
            window.matchMedia('(min-width: 769px)').addEventListener('change', handleViewportChange);
            
            // Initial check
            handleViewportChange();
        }
    </script>
</body>
</html>