<?php
define('ALLOWED_ACCESS', true);
require_once 'includes/session.php';
require_once 'languages/language.php'; // Include language file for translations
$page_class = "home";

$header_file = "includes/header.php";
// Ensure header file exists before including
if (file_exists($header_file)) {
    include $header_file;
} else {
    die(translate('error_header_not_found', 'Error: Header file not found.'));
}

// Include database configuration

// Query to fetch the 4 most recent news items
$base_url = '/sahtout/';
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
    <title><?php echo translate('home_page_title', 'Home'); ?></title>
    <style>
        html, body {
            width: 100%;
            overflow-x: hidden;
            margin: 0;
        }

        body.home {
            background: url('/sahtout/img/backgrounds/bg-home.jpg') no-repeat center center fixed;
            background-size: cover;
            font-family: 'UnifrakturCook', 'Arial', sans-serif;
            color: #fff;
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0;
            box-sizing: border-box;
        }

        /* Discord Widget */
        .discord-widget {
            position: fixed;
            top: 145px;
            left: 5px;
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid #ffd700;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(255, 215, 0, 0.5);
            z-index: 1000;
            overflow: hidden;
        }

        .discord-widget iframe {
            border: none;
        }

        .discord-widget h2 {
            text-align: center;
            color: #5865F2;
            font-family: 'UnifrakturCook', cursive;
            margin: 10px 0;
            font-size: 1.5rem;
        }

        /* Intro Container */
        .intro-container {
            max-width: 800px;
            width: calc(100% - 2rem);
            margin: 2rem auto;
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid #ffd700;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 0 15px rgba(255, 215, 0, 0.5);
        }

        .intro-title {
            color: #ffd700;
            font-size: 2.5rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
            margin-bottom: 1rem;
        }

        .intro-tagline {
            color: #ccc;
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
        }

        .intro-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }

        .intro-button {
            background: #333;
            color: #ffd700;
            border: 2px solid #ffd700;
            padding: 0.8rem 1.5rem;
            font-family: 'UnifrakturCook', sans-serif;
            font-size: 1.1rem;
            border-radius: 4px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .intro-button:hover {
            background: #ffd700;
            color: #000;
            transform: scale(1.05);
        }

        .social-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .social-line {
            flex: 1;
            max-width: 100px;
            height: 2px;
            background: linear-gradient(to right, transparent, #ffd700, transparent);
        }

        .youtube-button,
        .discord-button,
        .instagram-button {
            padding: 0.8rem;
            border-radius: 32px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .youtube-button {
            background: #FF0000;
            box-shadow: 0 0 10px rgba(255, 0, 0, 0.5);
        }

        .youtube-button:hover {
            background: #CC0000;
            transform: scale(1.1);
            box-shadow: 0 0 15px rgba(255, 0, 0, 0.8);
        }

        .discord-button {
            background: #5865F2;
            box-shadow: 0 0 10px rgba(88, 101, 242, 0.5);
        }

        .discord-button:hover {
            background: #7289DA;
            transform: scale(1.1);
            box-shadow: 0 0 15px rgba(88, 101, 242, 0.8);
        }

        .instagram-button {
            background: #E1306C;
            box-shadow: 0 0 10px rgba(225, 48, 108, 0.5);
        }

        .instagram-button:hover {
            background: #C13584;
            transform: scale(1.1);
            box-shadow: 0 0 15px rgba(225, 48, 108, 0.8);
        }

        .youtube-logo,
        .discord-logo,
        .instagram-logo {
            width: 42px;
            height: 42px;
        }

        /* News Preview Section */
        .news-preview {
            max-width: 800px;
            width: calc(100% - 2rem);
            margin: 2rem auto;
        }

        .news-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .news-item {
            position: relative;
        }

        .news-item a {
            text-decoration: none;
            display: block;
        }

        .news-image {
            position: relative;
            overflow: hidden;
            border: 2px solid #ffd700;
            border-radius: 8px;
            margin: 5px;
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
            transition: transform 0.3s ease;
        }

        .news-image img {
            width: 100%;
            height: auto;
            display: block;
        }

        .news-image:hover {
            transform: scale(1.05);
        }

        .news-title {
            display: block;
            position: absolute;
            top: 80%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 100%;
            text-align: center;
            color: #ffffff;
            font-family: Georgia, 'Times New Roman', Times, serif;
            font-size: 1.5rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
            padding: 0.5rem 1rem;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 0;
        }

        .news-date {
            text-align: center;
            color: #ffffffff;
            font-family: 'UnifrakturCook', sans-serif;
            text-shadow: #000000ff 2px 2px 2px;
            font-size: 1rem;
            margin-top: 0.5rem;
        }

        /* Hero Gallery Slider */
        .hero-gallery {
            position: relative;
            max-width: 800px;
            width: calc(100% - 2rem);
            margin: 2rem auto;
            overflow: hidden;
            border: 2px solid #ffd700;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(255, 215, 0, 0.5);
        }

        .slider {
            display: flex;
            transition: transform 0.5s ease-in-out;
            width: 100%;
        }

        .slide {
            flex: 0 0 100%;
            width: 100%;
        }

        .slide img {
            width: 100%;
            height: auto;
            display: block;
        }

        .slider-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.7);
            color: #ffd700;
            border: none;
            padding: 10px;
            cursor: pointer;
            font-size: 1.5rem;
            border-radius: 4px;
        }

        .slider-nav.prev {
            left: 10px;
        }

        .slider-nav.next {
            right: 10px;
        }

        .slider-dots {
            position: absolute;
            bottom: 10px;
            width: 100%;
            text-align: center;
        }

        .dot {
            display: inline-block;
            width: 12px;
            height: 12px;
            background: #ccc;
            border-radius: 50%;
            margin: 0 5px;
            cursor: pointer;
        }

        .dot.active {
            background: #ffd700;
        }

        /* Tabs Container */
        .tabs-container {
            max-width: 800px;
            width: calc(100% - 2rem);
            margin: 2rem auto;
            background: rgba(0, 0, 0, 0.8);
            padding: 1rem;
            border: 2px solid #ffd700;
            border-radius: 8px;
        }

        .tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .tab {
            background: #333;
            color: #ffd700;
            border: none;
            padding: 0.8rem 1.5rem;
            margin: 0 0.5rem;
            cursor: pointer;
            font-family: 'UnifrakturCook', sans-serif;
            font-size: 1.1rem;
            border-radius: 4px;
        }

        .tab.active {
            background: #ffd700;
            color: #000;
        }

        .tab-content {
            padding: 1rem;
            color: #fff;
        }

        .tab-content h2 {
            color: #ffd700;
            margin-bottom: 1rem;
        }

        .tab-content a {
            color: #ffd700;
            text-decoration: none;
        }

        .tab-content a:hover {
            text-decoration: underline;
        }

        /* Server Status */
        .server-status {
            position: fixed;
            top: 125px;
            right: 15px;
            width: 300px;
            background: #0a0e14;
            border: 2px solid #00f7ff;
            border-radius: 10px;
            padding: 15px;
            font-family: 'Orbitron', 'Arial', sans-serif;
            color: #e0e0e0;
            box-shadow: 0 0 20px rgba(0, 247, 255, 0.5), 0 0 10px rgba(255, 0, 255, 0.3);
            box-sizing: border-box;
            text-align: center;
            max-height: calc(100vh - 255px); /* Prevent touching footer */
            overflow-y: auto; /* Enable scrollbar if content overflows */
            z-index: 900; /* Below discord-widget (z-index: 1000) */
        }

        /* Custom Scrollbar Styling */
        .server-status::-webkit-scrollbar {
            width: 10px;
        }

        .server-status::-webkit-scrollbar-track {
            background: #1a1e2a;
            border-radius: 5px;
            box-shadow: inset 0 0 5px rgba(0, 247, 255, 0.3);
        }

        .server-status::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #00f7ff, #ff00ff);
            border-radius: 5px;
            box-shadow: 0 0 8px rgba(0, 247, 255, 0.8), 0 0 8px rgba(255, 0, 255, 0.8);
        }

        .server-status::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #00ccff, #cc00cc);
        }

        .server-status:hover {
            box-shadow: 0 0 25px rgba(0, 247, 255, 0.7), 0 0 15px rgba(255, 0, 255, 0.5);
        }

        .server-status h2 {
            color: #00f7ff;
            font-family: 'Orbitron', sans-serif;
            font-size: 1.2rem;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 0 0 10px rgba(0, 247, 255, 0.8);
        }

        .server-status ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .server-status li {
            background: rgba(20, 20, 30, 0.8);
            border: 1px solid #ff00ff;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 6px;
            text-align: center;
            transition: box-shadow 0.3s ease, background 0.3s ease;
        }

        .server-status li:hover {
            box-shadow: 0 0 10px rgba(0, 247, 255, 0.8);
            background: rgba(30, 30, 50, 0.9);
        }

        .server-status img {
            margin: 0 auto 10px;
            max-width: 50%;
            height: auto;
            border-radius: 4px;
            filter: drop-shadow(0 0 5px rgba(0, 247, 255, 0.5));
        }

        .server-status strong {
            font-size: 1rem;
            color: #ff00ff;
            display: block;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .server-status .online {
            color: #00ff00;
            font-weight: bold;
            font-family: 'Orbitron', sans-serif;
            font-size: 1rem;
            text-shadow: 0 0 5px rgba(0, 255, 0, 0.8);
        }

        .server-status .offline {
            color: #ff0000;
            font-weight: bold;
            font-family: 'Orbitron', sans-serif;
            font-size: 1rem;
            text-shadow: 0 0 5px rgba(255, 0, 0, 0.8);
        }

        .server-status .players,
        .server-status .uptime,
        .server-status .realm-ip {
            font-size: 0.9rem;
            color: #e0e0e0;
            font-family: 'Orbitron', sans-serif;
        }

        /* Footer adjustments */
        footer {
            width: 100%;
            margin: 0;
            padding: 1rem 0;
            box-sizing: border-box;
        }

        /* Responsive Design */
        @media (max-width: 1400px) and (min-width: 1200px) {
            .discord-widget {
                position: fixed;
                width: 100%;
                max-width: 235px;
                margin: 1rem auto;
            }

            .discord-widget iframe {
                width: 100%;
                height: 400px;
            }

            .server-status {
                width: 100%;
                max-width: 235px;
                max-height: calc(100vh - 150px); /* Prevent touching footer */
                overflow-y: auto;
            }

            main {
                padding-bottom: 2rem;
            }
        }

        @media (max-width: 1200px) and (min-width: 769px) {
            .discord-widget {
                position: static;
                width: 100%;
                max-width: 300px;
                margin: 1rem auto;
            }

            .discord-widget iframe {
                width: 100%;
                height: 400px;
            }

            .server-status {
                position: static;
                width: 100%;
                max-width: 300px;
                margin: 1rem auto;
                max-height: calc(100vh - 150px); /* Adjusted for smaller screens */
                overflow-y: auto;
            }

            main {
                padding-bottom: 2rem;
            }
        }

        @media (max-width: 768px) {
            html, body {
                width: 100%;
                overflow-x: hidden;
            }

            header {
                padding: 1rem 0;
            }

            main {
                padding: 0;
                margin-top: 100px;
            }

            .discord-widget {
                position: static;
                width: 100%;
                max-width: 340px;
                margin: 1rem auto;
            }

            .intro-container {
                max-width: 800px;
                width: calc(100% - 2rem);
                margin: 1rem auto;
                padding: 1.5rem;
            }

            .intro-title {
                font-size: 2rem;
            }

            .intro-buttons {
                flex-direction: column;
                gap: 0.5rem;
            }

            .social-container {
                flex-direction: column;
                gap: 0.5rem;
            }

            .social-line {
                width: 50%;
                margin: 0.5rem auto;
            }

            .news-grid {
                grid-template-columns: 1fr;
            }

            .hero-gallery {
                max-width: 100%;
                width: 100%;
                margin: 1rem 0;
                border-radius: 0;
            }

            .tabs-container {
                max-width: 100%;
                width: 100%;
                margin: 1rem 0;
            }

            .server-status {
                position: static;
                width: 100%;
                max-width: 300px;
                margin: 1rem auto;
                max-height: calc(100vh - 150px); /* Adjusted for mobile */
                overflow-y: auto;
            }

            footer {
                padding: 1rem 0;
            }
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
            <h1 class="intro-title"><?php echo translate('home_intro_title', 'Welcome to Sahtout'); ?></h1>
            <p class="intro-tagline"><?php echo translate('home_intro_tagline', 'Join our epic World of Warcraft server adventure today!'); ?></p>
            <div class="intro-buttons">
                <a href="/sahtout/register" class="intro-button"><?php echo translate('home_create_account', 'Create Account'); ?></a>
                <a href="/sahtout/download" class="intro-button"><?php echo translate('home_download', 'Download'); ?></a>
            </div>
            <div class="social-container">
                <hr class="social-line">
                <a href="https://www.youtube.com/@Blodyone" class="youtube-button">
                    <img src="/sahtout/img/homeimg/youtube-logo1.png" alt="<?php echo translate('youtube_alt', 'YouTube'); ?>" class="youtube-logo">
                </a>
                <hr class="social-line">
                <a href="https://discord.gg/chxXTXXQ6M" class="discord-button">
                    <img src="/sahtout/img/homeimg/discordlogo.png" alt="<?php echo translate('discord_alt', 'Discord'); ?>" class="discord-logo">
                </a>
                <hr class="social-line">
                <a href="https://instagram.com/your-profile" class="instagram-button">
                    <img src="/sahtout/img/homeimg/insta-logo.png" alt="<?php echo translate('instagram_alt', 'Instagram'); ?>" class="instagram-logo">
                </a>
                <hr class="social-line">
            </div>
        </section>

        <!-- 🔁 Image Gallery Slider -->
        <section class="hero-gallery">
            <div class="slider" id="slider">
                <div class="slide"><img src="/sahtout/img/homeimg/slide1.jpg" alt="<?php echo translate('slider_alt_1', 'World of Warcraft Scene 1'); ?>"></div>
                <div class="slide"><img src="/sahtout/img/homeimg/slide2.jpg" alt="<?php echo translate('slider_alt_2', 'World of Warcraft Scene 2'); ?>"></div>
                <div class="slide"><img src="/sahtout/img/homeimg/slide3.jpg" alt="<?php echo translate('slider_alt_3', 'World of Warcraft Scene 3'); ?>"></div>
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
                            <a href="<?php echo $base_url; ?>news?slug=<?php echo htmlspecialchars($news['slug']); ?>">
                                <div class="news-image">
                                    <img src="<?php echo $base_url . htmlspecialchars($news['image_url']); ?>" 
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
            $realm_status_file = "includes/realm_status.php";
            if (file_exists($realm_status_file)) {
                include $realm_status_file;
            } else {
                echo "<p>" . translate('home_realm_status_error', 'Error: Realm status unavailable.') . "</p>";
            }
            ?>
        </div>
    </main>
    
    <?php
    $footer_file = "includes/footer.php";
    if (file_exists($footer_file)) {
        include $footer_file;
    } else {
        die(translate('error_footer_not_found', 'Error: Footer file not found.'));
    }
    ?>
    <script src="assets/js/home.js"></script>
</body>
</html>