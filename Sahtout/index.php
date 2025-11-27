<?php
define('ALLOWED_ACCESS', true);

require_once __DIR__ . '/includes/paths.php';
require_once $project_root . 'includes/session.php';
require_once $project_root . 'languages/language.php'; // Include language file for translations
require_once $project_root . 'includes/config.settings.php'; // Load socials
$page_class = "home";
$header_file = $project_root . 'includes/header.php';

// Ensure header file exists before including
if (file_exists($header_file)) {
    include $header_file;
} else {
    die(translate('error_header_not_found', 'Error: Header file not found.'));
}

// Query to fetch the 4 most recent news items
$query = "SELECT id, title, slug, image_url, post_date 
          FROM server_news 
          ORDER BY is_important DESC, post_date DESC 
          LIMIT 4";
$result = $site_db->query($query);
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo translate('home_meta_description', 'Welcome to our World of Warcraft server. Join our Discord, YouTube, Instagram, create an account, or download the game now!'); ?>">
    <meta name="robots" content="index">
    <title><?php echo $site_title_name ." ". translate('home_page_title', 'Home'); ?></title>
    <style>
    :root {
        --bg-home: url("<?php echo $base_path; ?>img/backgrounds/bg-home.jpg");
        }

    </style>
</head>
<body class="home">
    <main>
        <!-- Discord Widget -->
        <section class="discord-widget">
            <h2><?php echo translate('home_discord_title', 'Join Our Discord'); ?></h2>
            <iframe src="https://discord.com/widget?id=1405755152085815337&theme=dark" width="350" height="400" allowtransparency="true" frameborder="0" sandbox="allow-popups allow-popups-to-escape-sandbox allow-same-origin allow-scripts"></iframe>
        </section>

        <!-- Intro Container -->
        <section class="intro-container">
            <h1 class="intro-title"><?php echo translate('home_intro_title', 'Welcome to ')." ".$site_title_name ; ?></h1>
            <p class="intro-tagline"><?php echo translate('home_intro_tagline', 'Join our epic World of Warcraft server adventure today!'); ?></p>
            <div class="intro-buttons">
                <a href="<?php echo $base_path; ?>register" class="intro-button"><?php echo translate('home_create_account', 'Create Account'); ?></a>
                <a href="<?php echo $base_path; ?>download" class="intro-button"><?php echo translate('home_download', 'Download'); ?></a>
            </div>
            <div class="social-container">
                <hr class="social-line">
                <a href="<?php echo $social_links['youtube']; ?>" class="youtube-button" aria-label="<?php echo translate('youtube_alt', 'YouTube'); ?>">
                    <img src="<?php echo $base_path; ?>img/homeimg/youtube-logo1.png" alt="<?php echo translate('youtube_alt', 'YouTube'); ?>" class="youtube-logo">
                </a>
                <hr class="social-line">
                <a href="<?php echo $social_links['discord']; ?>" class="discord-button" aria-label="<?php echo translate('discord_alt', 'Discord'); ?>">
                    <img src="<?php echo $base_path; ?>img/homeimg/discordlogo.png" alt="<?php echo translate('discord_alt', 'Discord'); ?>" class="discord-logo">
                </a>
                <hr class="social-line">
                <a href="<?php echo $social_links['instagram']; ?>" class="instagram-button" aria-label="<?php echo translate('instagram_alt', 'Instagram'); ?>">
                    <img src="<?php echo $base_path; ?>img/homeimg/insta-logo.png" alt="<?php echo translate('instagram_alt', 'Instagram'); ?>" class="instagram-logo">
                </a>
                <hr class="social-line">
            </div>
        </section>

        <!-- 🔁 Image Gallery Slider -->
        <section class="hero-gallery">
            <div class="slider" id="slider">
                <div class="slide"><img src="<?php echo $base_path; ?>img/homeimg/slide1.jpg" alt="<?php echo translate('slider_alt_1', 'World of Warcraft Scene 1'); ?>"></div>
                <div class="slide"><img src="<?php echo $base_path; ?>img/homeimg/slide2.jpg" alt="<?php echo translate('slider_alt_2', 'World of Warcraft Scene 2'); ?>"></div>
                <div class="slide"><img src="<?php echo $base_path; ?>img/homeimg/slide3.jpg" alt="<?php echo translate('slider_alt_3', 'World of Warcraft Scene 3'); ?>"></div>
            </div>
            <button class="slider-nav prev" aria-label="<?php echo translate('slider_prev', 'Previous Slide'); ?>">❮</button>
            <button class="slider-nav next" aria-label="<?php echo translate('slider_next', 'Next Slide'); ?>">❯</button>
            <div class="slider-dots">
                <span class="dot active" data-slide="0"></span>
                <span class="dot" data-slide="1"></span>
                <span class="dot" data-slide="2"></span>
            </div>
        </section>

        <!-- 📰 News Preview Section -->
        <section class="news-preview">
            <div class="news-grid">
                <?php if ($result->num_rows === 0): ?>
                    <p><?php echo translate('home_no_news', 'No news available at the time.'); ?></p>
                <?php else: ?>
                    <?php while ($news = $result->fetch_assoc()): ?>
                        <div class="news-item">
                            <a href="<?php echo $base_path; ?>news?slug=<?php echo htmlspecialchars($news['slug']); ?>">
                                <div class="news-image">
                                    <img src="<?php echo $base_path . htmlspecialchars($news['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($news['title']); ?>">
                                    <span class="news-title"><?php echo htmlspecialchars($news['title']); ?></span>
                                </div>
                                <p class="news-date"><?php echo date('M j, Y', strtotime($news['post_date'])); ?></p>
                            </a>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- 🔲 Menubar Tabs -->
        <section class="tabs-container">
            <div class="tabs">
                <button class="tab active" data-tab="bugtracker"><?php echo translate('home_tab_bugtracker', 'Bugtracker'); ?></button>
                <button class="tab" data-tab="stream"><?php echo translate('home_tab_stream', 'Stream'); ?></button>
            </div>
            <div class="tab-content" id="tab-content">
                <h2><?php echo translate('home_bugtracker_title', 'Bugtracker'); ?></h2>
                <p><?php echo translate('home_bugtracker_content', 'View and report issues with the server to help us improve your experience.'); ?></p>
            </div>
        </section>

        <!-- Server Status -->
        <div class="server-status">
            <?php
            $realm_status_file = $project_root . 'includes/realm_status.php';
            if (file_exists($realm_status_file)) {
                include $realm_status_file;
            } else {
                echo "<p>" . translate('home_realm_status_error', 'Error: Realm status unavailable.') . "</p>";
            }
            ?>
        </div>
    </main>
    
    <?php
    $footer_file = $project_root . 'includes/footer.php';
    if (file_exists($footer_file)) {
        include $footer_file;
    } else {
        die(translate('error_footer_not_found', 'Error: Footer file not found.'));
    }
    ?>
    <script src="<?php echo $base_path; ?>assets/js/home.js"></script>
</body>
</html>
<?php
// Close database connection
if (isset($site_db)) {
    $site_db->close();
}
?>