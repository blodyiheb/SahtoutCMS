<?php
define('ALLOWED_ACCESS', true);

require_once __DIR__ . '/includes/paths.php';
require_once $project_root . 'includes/session.php';
require_once $project_root . 'languages/language.php';
require_once $project_root . 'includes/config.settings.php';

$page_class = "home";
$header_file = $project_root . 'includes/header.php';

if (file_exists($header_file)) {
    include $header_file;
} else {
    die(translate('error_header_not_found', 'Error: Header file not found.'));
}

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
    <title><?php echo $site_title_name . " " . translate('home_page_title', 'Home'); ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&family=Cinzel:wght@600;700;900&display=swap');

        * { font-family: 'Inter', sans-serif; }

        /* ============ PURE-CSS GAMING BACKGROUND (Lighter & Cleaner) ============ */
        body {
            min-height: 100vh;
            position: relative;
            color: #d8d8d8;
            background-color: #12161f; /* Lighter base color */
            background-image:
                radial-gradient(1000px 700px at -10% 35%, rgba(59,130,246,.25), transparent 65%), /* Stronger Blue */
                radial-gradient(800px 600px at -5% 85%, rgba(124,58,237,.20), transparent 70%),   /* Stronger Purple */
                linear-gradient(180deg, #181e2a 0%, #12161f 45%, #0a0d14 100%);                  /* Lighter Vignette */
            background-attachment: fixed;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image:
                radial-gradient(2px 2px at 10% 20%, rgba(242,207,82,.8), transparent 55%),
                radial-gradient(1.5px 1.5px at 30% 70%, rgba(242,207,82,.6), transparent 55%),
                radial-gradient(2px 2px at 55% 40%, rgba(255,160,60,.65), transparent 55%),
                radial-gradient(1.5px 1.5px at 75% 80%, rgba(242,207,82,.55), transparent 55%),
                radial-gradient(2px 2px at 90% 25%, rgba(255,120,50,.6), transparent 55%),
                radial-gradient(1.5px 1.5px at 45% 90%, rgba(120,160,255,.6), transparent 55%),
                radial-gradient(2px 2px at 65% 10%, rgba(120,160,255,.55), transparent 55%);
            background-size: 900px 700px;
            animation: emberDrift 45s linear infinite;
            opacity: .65; /* Slightly more visible against lighter bg */
            pointer-events: none;
            z-index: 0;
        }
        @keyframes emberDrift {
            from { background-position: 0 0; }
            to   { background-position: 900px -700px; }
        }

        body::after {
            content: '';
            position: fixed; inset: 0;
            background: radial-gradient(ellipse at center, transparent 45%, rgba(0,0,0,.40) 100%); /* Lighter vignette */
            pointer-events: none;
            z-index: 0;
        }

        main { position: relative; z-index: 1; }

        /* ============ GAMING DESIGN SYSTEM ============ */

        .wow-panel {
            position: relative;
            background: linear-gradient(180deg, rgba(22,25,32,.92), rgba(8,10,14,.9));
            border: 1px solid rgba(201,162,39,.22);
            box-shadow: 0 12px 32px rgba(0,0,0,.55), inset 0 0 60px rgba(0,0,0,.45);
        }
        .wow-panel::before {
            content: ''; position: absolute; inset: 5px;
            border: 1px solid rgba(201,162,39,.14);
            pointer-events: none;
        }
        .wow-panel::after {
            content: ''; position: absolute; inset: 0; pointer-events: none;
            background:
                linear-gradient(#e8c552,#e8c552) left top / 18px 2px,
                linear-gradient(#e8c552,#e8c552) left top / 2px 18px,
                linear-gradient(#e8c552,#e8c552) right top / 18px 2px,
                linear-gradient(#e8c552,#e8c552) right top / 2px 18px,
                linear-gradient(#e8c552,#e8c552) left bottom / 18px 2px,
                linear-gradient(#e8c552,#e8c552) left bottom / 2px 18px,
                linear-gradient(#e8c552,#e8c552) right bottom / 18px 2px,
                linear-gradient(#e8c552,#e8c552) right bottom / 2px 18px;
            background-repeat: no-repeat;
        }

        .wow-title {
            font-family: 'Cinzel', serif;
            font-weight: 900;
            background: linear-gradient(180deg, #fff7d6 0%, #f2cf5b 35%, #c9a227 62%, #8a6a14 100%);
            -webkit-background-clip: text; background-clip: text;
            color: transparent;
            filter: drop-shadow(0 3px 6px rgba(0,0,0,.85));
            letter-spacing: .02em;
        }

        .section-title {
            font-family: 'Cinzel', serif;
            font-weight: 700;
            color: #f2cf5b;
            text-shadow: 0 0 12px rgba(201,162,39,.35), 0 2px 4px rgba(0,0,0,.8);
            display: flex; align-items: center; gap: .6rem;
        }
        .section-title::after {
            content: ''; flex: 1; height: 1px;
            background: linear-gradient(90deg, rgba(201,162,39,.5), transparent);
        }

        .btn-game {
            display: inline-flex; align-items: center; gap: .55rem;
            padding: .95rem 2rem;
            font-weight: 800; font-size: .95rem; letter-spacing: .04em; text-transform: uppercase;
            clip-path: polygon(12px 0, 100% 0, 100% calc(100% - 12px), calc(100% - 12px) 100%, 0 100%, 0 12px);
            transition: transform .2s ease, filter .2s ease;
            text-decoration: none;
        }
        .btn-game:hover { transform: translateY(-2px) scale(1.02); }
        .btn-gold {
            background: linear-gradient(180deg, #f6d478 0%, #c9a227 48%, #8a6a14 100%);
            color: #1a1200;
            text-shadow: 0 1px 0 rgba(255,255,255,.35);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.28), inset 0 -8px 14px rgba(0,0,0,.25);
            animation: pulseGold 2.8s ease-in-out infinite;
        }
        @keyframes pulseGold {
            0%,100% { filter: drop-shadow(0 0 6px rgba(242,207,82,.35)); }
            50%     { filter: drop-shadow(0 0 18px rgba(242,207,82,.7)); }
        }
        .btn-iron {
            background: linear-gradient(180deg, #3a404d 0%, #232833 55%, #141821 100%);
            color: #cfe1ff;
            box-shadow: inset 0 0 0 1px rgba(120,160,255,.25), inset 0 -8px 14px rgba(0,0,0,.4);
            filter: drop-shadow(0 0 8px rgba(59,130,246,.25));
        }
        .btn-iron:hover { filter: drop-shadow(0 0 16px rgba(59,130,246,.5)); }

        .social-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 9px 18px;
            font-weight: 700; font-size: 13px; letter-spacing: .03em;
            color: #fff; text-decoration: none; border: none; cursor: pointer;
            clip-path: polygon(8px 0, 100% 0, 100% calc(100% - 8px), calc(100% - 8px) 100%, 0 100%, 0 8px);
            transition: transform .2s ease, filter .2s ease;
        }
        .social-btn:hover { transform: translateY(-3px); }
        .social-btn.youtube   { background: linear-gradient(180deg,#ff4d4d,#cc0000); filter: drop-shadow(0 0 6px rgba(255,0,0,.3)); }
        .social-btn.youtube:hover   { filter: drop-shadow(0 0 14px rgba(255,0,0,.55)); }
        .social-btn.discord   { background: linear-gradient(180deg,#7289ff,#4752c4); filter: drop-shadow(0 0 6px rgba(88,101,242,.3)); }
        .social-btn.discord:hover   { filter: drop-shadow(0 0 14px rgba(88,101,242,.55)); }
        .social-btn.instagram { background: linear-gradient(45deg,#405de6,#833ab4,#e1306c); filter: drop-shadow(0 0 6px rgba(225,48,108,.3)); }
        .social-btn.instagram:hover { filter: drop-shadow(0 0 14px rgba(225,48,108,.55)); }

        .slider-frame { border: 1px solid rgba(201,162,39,.3); padding: 6px; background: rgba(0,0,0,.45); }
        .slider-container { overflow: hidden; position: relative; }
        .slider-track { display: flex; transition: transform .5s ease-in-out; }
        .slide { min-width: 100%; }
        .slide img { filter: saturate(1.05) contrast(1.05); }
        .slider-nav {
            position: absolute; top: 50%; transform: translateY(-50%); z-index: 10;
            width: 44px; height: 44px;
            display: flex; align-items: center; justify-content: center;
            color: #f2cf5b; font-size: 18px;
            background: linear-gradient(180deg, rgba(30,34,42,.9), rgba(10,12,16,.9));
            border: 1px solid rgba(201,162,39,.45);
            clip-path: polygon(8px 0, 100% 0, 100% calc(100% - 8px), calc(100% - 8px) 100%, 0 100%, 0 8px);
            transition: all .2s ease;
        }
        .slider-nav:hover { color: #fff; border-color: #f2cf5b; filter: drop-shadow(0 0 10px rgba(242,207,82,.6)); }
        .slider-nav.prev { left: 10px; }
        .slider-nav.next { right: 10px; }

        .dot {
            width: 9px; height: 9px; transform: rotate(45deg);
            background: #4b5563; cursor: pointer; transition: all .3s ease;
            box-shadow: 0 0 0 1px rgba(0,0,0,.6);
        }
        .dot:hover { background: #9ca3af; }
        .dot.active {
            background: #f2cf5b;
            box-shadow: 0 0 10px rgba(242,207,82,.8), 0 0 0 1px rgba(0,0,0,.6);
            transform: rotate(45deg) scale(1.25);
        }

        .news-card {
            display: block; position: relative; overflow: hidden;
            background: linear-gradient(180deg, rgba(26,29,36,.9), rgba(10,12,16,.9));
            border: 1px solid rgba(201,162,39,.18);
            transition: transform .3s ease, border-color .3s ease, filter .3s ease;
            text-decoration: none;
        }
        .news-card:hover {
            transform: translateY(-6px);
            border-color: rgba(242,207,82,.6);
            filter: drop-shadow(0 10px 20px rgba(0,0,0,.6)) drop-shadow(0 0 10px rgba(242,207,82,.15));
        }
        .news-card .news-date {
            font-size: 11px; letter-spacing: .08em; text-transform: uppercase;
            color: #c9a227;
        }

        .tab-btn {
            position: relative;
            padding: .7rem 1.6rem;
            font-family: 'Cinzel', serif; font-weight: 700; font-size: .85rem; letter-spacing: .06em;
            color: #9ca3af; background: rgba(0,0,0,.35);
            border: 1px solid rgba(255,255,255,.08);
            clip-path: polygon(10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%, 0 10px);
            transition: all .25s ease;
            cursor: pointer;
        }
        .tab-btn:hover { color: #e5e7eb; border-color: rgba(201,162,39,.4); }
        .tab-btn.active {
            color: #f2cf5b;
            background: linear-gradient(180deg, rgba(201,162,39,.25), rgba(201,162,39,.08));
            border-color: rgba(242,207,82,.65);
            text-shadow: 0 0 10px rgba(242,207,82,.5);
        }
        .tab-panel { display: none; animation: fadeIn .4s ease; }
        .tab-panel.active { display: block; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .video-frame {
            border: 1px solid rgba(201,162,39,.35);
            padding: 6px; background: rgba(0,0,0,.5);
            filter: drop-shadow(0 10px 24px rgba(0,0,0,.6));
        }
        .video-frame iframe { width: 100%; aspect-ratio: 16/9; display: block; border: 0; }

        .status-online  { color: #4ade80; text-shadow: 0 0 8px rgba(74,222,128,.5); }
        .status-offline { color: #f87171; text-shadow: 0 0 8px rgba(248,113,113,.5); }

        .ornate-divider {
            border: 0; height: 12px; margin: 24px 0; position: relative;
            background: linear-gradient(90deg, transparent, rgba(201,162,39,.55), transparent);
        }
        .ornate-divider::after {
            content: '◆'; position: absolute; left: 50%; top: 50%;
            transform: translate(-50%,-50%);
            color: #f2cf5b; font-size: 8px; text-shadow: 0 0 8px rgba(242,207,82,.8);
        }

        /* ============ RESPONSIVE SIDEBAR FIX ============ */
        @media (max-width: 1024px) {
            .sidebar-sticky {
                position: relative;
                top: 0;
            }
        }
        
        @media (max-width: 640px) {
            .sidebar-sticky .wow-panel {
                padding: 1rem;
            }
            .sidebar-sticky .wow-title {
                font-size: 1.1rem;
            }
        }

        /* Discord widget responsive */
        .discord-widget-container {
            position: relative;
            width: 100%;
            overflow: hidden;
        }
        .discord-widget-container iframe {
            max-width: 100%;
            height: 340px;
        }
        @media (max-width: 480px) {
            .discord-widget-container iframe {
                height: 280px;
            }
        }

        /* ============ FORCE TEXT WRAPPING ============ */
        .force-wrap {
            min-width: 0;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
    <script>
        window.homeYoutubeSettings = <?php echo json_encode([
            'embedUrl' => $youtube_embed_url ?? 'https://www.youtube.com/embed/DjuN1dE50VI?rel=0&modestbranding=1',
            'title' => $youtube_title ?? 'Sahtout Server Trailer',
            'description' => $youtube_description ?? 'Lichking Trailer, Replace it with your own ....',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    </script>
</head>
<body>

    <main class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-10">

        <!-- Responsive grid: stacks on mobile, side-by-side on large screens -->
        <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_380px] gap-6 md:gap-8">

            <!-- ============ LEFT CONTENT ============ -->
            <div class="min-w-0 space-y-8 md:space-y-12">

                <!-- HERO -->
                <div class="wow-panel p-6 sm:p-8 md:p-12 text-center relative overflow-hidden">
                    <div class="absolute -top-24 -left-24 w-72 h-72 rounded-full blur-3xl opacity-20" style="background:#1e5aa8;"></div>
                    <div class="absolute -bottom-24 -right-24 w-72 h-72 rounded-full blur-3xl opacity-20" style="background:#a32626;"></div>

                    <div class="relative z-10">
                        <p class="text-xs md:text-sm font-semibold tracking-[.35em] uppercase text-gray-400 mb-4"><?php echo translate('home_intro_tagline', 'Join our epic World of Warcraft server adventure today!'); ?></p>

                        <h1 class="text-3xl sm:text-4xl md:text-6xl lg:text-7xl leading-tight force-wrap">
                            <span class="text-white font-extrabold" style="font-family:'Cinzel',serif; text-shadow:0 0 20px rgba(59,130,246,.35), 0 4px 8px rgba(0,0,0,.9);"><?php echo translate('home_intro_title', 'Welcome to '); ?></span><br class="hidden md:block">
                            <span class="wow-title"><?php echo $site_title_name; ?></span>
                        </h1>

                        <div class="flex items-center justify-center gap-3 mt-6">
                            <span class="h-px w-12 md:w-28" style="background:linear-gradient(90deg,transparent,#c9a227);"></span>
                            <span class="text-[#f2cf5b] text-xs" style="text-shadow:0 0 10px rgba(242,207,82,.8);">◆</span>
                            <span class="h-px w-12 md:w-28" style="background:linear-gradient(90deg,#c9a227,transparent);"></span>
                        </div>

                        <div class="flex flex-wrap justify-center gap-3 sm:gap-4 mt-6 sm:mt-8">
                            <?php if (!isset($_SESSION['user_id'])): ?>
                                <a href="<?php echo $base_path; ?>register" class="btn-game btn-gold text-sm sm:text-base px-4 sm:px-6">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                    <?php echo translate('home_create_account', 'Create Account'); ?>
                                </a>
                            <?php else: ?>
                                <a href="<?php echo $base_path; ?>account" class="btn-game btn-gold text-sm sm:text-base px-4 sm:px-6">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <?php echo translate('home_my_account', 'My Account'); ?>
                                </a>
                            <?php endif; ?>
                            <a href="<?php echo $base_path; ?>download" class="btn-game btn-iron text-sm sm:text-base px-4 sm:px-6">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                <?php echo translate('home_download', 'Download'); ?>
                            </a>
                        </div>

                        <div class="flex flex-wrap items-center justify-center gap-2 sm:gap-3 mt-6 sm:mt-8">
                            <a href="<?php echo $social_links['youtube']; ?>" class="social-btn youtube text-xs sm:text-sm px-3 sm:px-4" target="_blank" aria-label="<?php echo translate('youtube_alt', 'YouTube'); ?>">
                                <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.97 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                                YouTube
                            </a>
                            <a href="<?php echo $social_links['discord']; ?>" class="social-btn discord text-xs sm:text-sm px-3 sm:px-4" target="_blank" aria-label="<?php echo translate('discord_alt', 'Discord'); ?>">
                                <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M19.27 5.33C17.94 4.71 16.5 4.26 15 4a.09.09 0 0 0-.07.03c-.18.33-.39.76-.53 1.09a16.09 16.09 0 0 0-4.8 0c-.14-.34-.35-.76-.54-1.09c-.01-.02-.04-.03-.07-.03c-1.5.26-2.93.71-4.27 1.33c-.01 0-.02.01-.03.02c-2.72 4.07-3.47 8.03-3.1 11.95c0 .02.01.04.03.05c1.8 1.32 3.53 2.12 5.24 2.65c.03.01.06 0 .07-.02c.4-.55.76-1.13 1.07-1.74c.02-.04 0-.08-.04-.09c-.57-.22-1.11-.48-1.64-.78c-.04-.02-.04-.08-.01-.11c.11-.08.22-.17.33-.25c.02-.02.05-.02.07-.01c3.44 1.57 7.15 1.57 10.55 0c.02-.01.05-.01.07.01c.11.08.22.17.33.26c.04.03.04.09-.01.11c-.52.31-1.07.56-1.64.78c-.04.01-.05.06-.04.09c.32.61.68 1.19 1.07 1.74c.03.01.06.02.09.01c1.72-.53 3.45-1.33 5.25-2.65c.02-.01.03-.03.03-.05c.44-4.53-.73-8.46-3.1-11.95c-.01-.01-.02-.02-.04-.02zM8.52 14.91c-1.03 0-1.89-.95-1.89-2.12s.84-2.12 1.89-2.12c1.06 0 1.9.96 1.89 2.12c0 1.17-.83 2.12-1.89 2.12zm6.97 0c-1.03 0-1.89-.95-1.89-2.12s.84-2.12 1.89-2.12c1.06 0 1.9.96 1.89 2.12c0 1.17-.83 2.12-1.89 2.12z"/></svg>
                                Discord
                            </a>
                            <a href="<?php echo $social_links['instagram']; ?>" class="social-btn instagram text-xs sm:text-sm px-3 sm:px-4" target="_blank" aria-label="<?php echo translate('instagram_alt', 'Instagram'); ?>">
                                <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>
                                Instagram
                            </a>
                        </div>
                    </div>
                </div>

                <!-- GALLERY -->
                <section class="wow-panel p-3 sm:p-4 md:p-6">
                    <h2 class="section-title text-lg sm:text-xl md:text-2xl mb-4 sm:mb-5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <?php echo translate('home_gallery_title', 'Gallery'); ?>
                    </h2>
                    <div class="slider-frame">
                        <div class="slider-container relative">
                            <div class="slider-track" id="sliderTrack">
                                <?php
                                $slides = [
                                    ['img' => 'slide1.jpg', 'alt' => translate('slider_alt_1', 'World of Warcraft Scene 1')],
                                    ['img' => 'slide2.jpg', 'alt' => translate('slider_alt_2', 'World of Warcraft Scene 2')],
                                    ['img' => 'slide3.jpg', 'alt' => translate('slider_alt_3', 'World of Warcraft Scene 3')],
                                    ['img' => 'slide4.jpg', 'alt' => translate('slider_alt_4', 'World of Warcraft Scene 4')]
                                ];
                                foreach ($slides as $slide):
                                ?>
                                    <div class="slide">
                                        <img src="<?php echo $base_path; ?>img/homeimg/<?php echo $slide['img']; ?>"
                                             alt="<?php echo $slide['alt']; ?>"
                                             class="w-full h-48 sm:h-56 md:h-96 object-cover">
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <button class="slider-nav prev text-xs sm:text-base w-8 h-8 sm:w-11 sm:h-11" id="prevSlide" aria-label="<?php echo translate('slider_prev', 'Previous Slide'); ?>">❮</button>
                            <button class="slider-nav next text-xs sm:text-base w-8 h-8 sm:w-11 sm:h-11" id="nextSlide" aria-label="<?php echo translate('slider_next', 'Next Slide'); ?>">❯</button>
                        </div>
                    </div>
                    <div class="flex justify-center gap-2 sm:gap-3 mt-3 sm:mt-4" id="dotsContainer">
                        <span class="dot active" data-slide="0"></span>
                        <span class="dot" data-slide="1"></span>
                        <span class="dot" data-slide="2"></span>
                        <span class="dot" data-slide="3"></span>
                    </div>
                </section>

                <!-- NEWS -->
                <section class="wow-panel p-3 sm:p-4 md:p-6">
                    <div class="flex items-center justify-between mb-4 sm:mb-6 gap-4">
                        <h2 class="section-title text-lg sm:text-xl md:text-2xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                            <?php echo translate('home_news_title', 'Latest News'); ?>
                        </h2>
                        <a href="<?php echo $base_path; ?>news" class="text-xs sm:text-sm font-semibold text-[#f2cf5b] hover:text-white transition-colors flex items-center gap-1 whitespace-nowrap">
                            <?php echo translate('home_view_all', 'View All'); ?>
                            <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                        <?php if ($result->num_rows === 0): ?>
                            <p class="col-span-full text-gray-400 text-center py-8">
                                <?php echo translate('home_no_news', 'No news available at the time.'); ?>
                            </p>
                        <?php else: ?>
                            <?php while ($news = $result->fetch_assoc()): ?>
                                <a href="<?php echo $base_path; ?>news?slug=<?php echo htmlspecialchars($news['slug']); ?>" class="news-card group min-w-0">
                                    <div class="relative h-36 sm:h-44 overflow-hidden">
                                        <img src="<?php echo $base_path . htmlspecialchars($news['image_url']); ?>"
                                             alt="<?php echo htmlspecialchars($news['title']); ?>"
                                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                        <div class="absolute inset-0" style="background:linear-gradient(to top, rgba(0,0,0,.9), transparent 60%);"></div>
                                        <div class="absolute bottom-0 left-0 right-0 p-2 sm:p-3 min-w-0">
                                            <h3 class="text-xs sm:text-sm font-bold text-white min-w-0 break-words [overflow-wrap:anywhere] line-clamp-2"><?php echo htmlspecialchars($news['title']); ?></h3>
                                        </div>
                                    </div>
                                    <div class="p-2 sm:p-3">
                                        <p class="news-date text-[10px] sm:text-xs"><?php echo date('M j, Y', strtotime($news['post_date'])); ?></p>
                                    </div>
                                </a>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- TABS: YOUTUBE + BUGTRACKER -->
                <section class="wow-panel p-3 sm:p-4 md:p-6">
                    <div class="flex flex-wrap gap-1 sm:gap-2 mb-4 sm:mb-6">
                        <button class="tab-btn text-xs sm:text-sm px-3 sm:px-4 py-1.5 sm:py-2" data-tab="youtube"><?php echo translate('home_tab_youtube', 'YouTube'); ?></button>
                        <button class="tab-btn text-xs sm:text-sm px-3 sm:px-4 py-1.5 sm:py-2" data-tab="bugtracker"><?php echo translate('home_tab_bugtracker', 'Bugtracker'); ?></button>
                    </div>

                    <div class="tab-content">
                        <div class="tab-panel active" id="panel-youtube">
                            <div class="text-center mb-4 sm:mb-5">
                                <h2 class="section-title text-lg sm:text-xl md:text-2xl force-wrap" style="justify-content:center;">
                                    <?php echo htmlspecialchars($youtube_title ?? 'Sahtout Server Trailer', ENT_QUOTES, 'UTF-8'); ?>
                                </h2>
                                <p class="text-gray-400 mt-2 max-w-xl mx-auto text-xs sm:text-sm md:text-base force-wrap">
                                    <?php echo htmlspecialchars($youtube_description ?? 'Lichking Trailer, Replace it with your own ....', ENT_QUOTES, 'UTF-8'); ?>
                                </p>
                            </div>
                            <div class="video-frame max-w-2xl mx-auto">
                                <iframe
                                    src="<?php echo htmlspecialchars($youtube_embed_url ?? 'https://www.youtube.com/embed/DjuN1dE50VI?rel=0&modestbranding=1', ENT_QUOTES, 'UTF-8'); ?>"
                                    title="<?php echo htmlspecialchars($youtube_title ?? 'Sahtout Server Trailer', ENT_QUOTES, 'UTF-8'); ?>"
                                    loading="lazy"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                    allowfullscreen></iframe>
                            </div>
                        </div>

                        <div class="tab-panel" id="panel-bugtracker">
                            <div class="text-center py-8 sm:py-12">
                                <svg class="w-12 h-12 sm:w-16 sm:h-16 mx-auto text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <h3 class="text-lg sm:text-xl font-bold text-white mb-2 force-wrap" style="font-family:'Cinzel',serif;"><?php echo translate('home_bugtracker_title', 'Bugtracker Coming Soon'); ?></h3>
                                <p class="text-gray-400 text-sm sm:text-base force-wrap"><?php echo translate('home_bugtracker_desc', 'Report bugs and track issues here.'); ?></p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- ============ RIGHT SIDE PANEL - Now visible on all screen sizes ============ -->
            <div class="sidebar-sticky">
                <div class="lg:sticky lg:top-24">
                    <div class="wow-panel p-4 sm:p-5 md:p-6 space-y-4 sm:space-y-6">
                        
                        <!-- Server Status -->
                        <div>
                            <h3 class="wow-title text-base sm:text-lg mb-3 sm:mb-4 flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
                                <?php echo translate('home_server_status', 'Server Status'); ?>
                            </h3>
                            <?php
                            $realm_status_file = $project_root . 'includes/realm_status.php';
                            if (file_exists($realm_status_file)) {
                                ob_start();
                                include $realm_status_file;
                                $status_content = ob_get_clean();
                                echo '<div class="space-y-2 text-xs bg-black/30 p-2 sm:p-3 border border-[#c9a227]/20 rounded-sm force-wrap">';
                                echo $status_content;
                                echo '</div>';
                            } else {
                                echo '<p class="status-offline text-xs bg-black/30 p-2 sm:p-3 border border-red-500/20 rounded-sm force-wrap">' . translate('home_realm_status_error', 'Error: Realm status unavailable.') . '</p>';
                            }
                            ?>
                        </div>

                        <hr class="ornate-divider">

                        <!-- Discord -->
                        <div>
                            <h3 class="wow-title text-base sm:text-lg mb-3 sm:mb-4 flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M19.27 5.33C17.94 4.71 16.5 4.26 15 4a.09.09 0 0 0-.07.03c-.18.33-.39.76-.53 1.09a16.09 16.09 0 0 0-4.8 0c-.14-.34-.35-.76-.54-1.09c-.01-.02-.04-.03-.07-.03c-1.5.26-2.93.71-4.27 1.33c-.01 0-.02.01-.03.02c-2.72 4.07-3.47 8.03-3.1 11.95c0 .02.01.04.03.05c1.8 1.32 3.53 2.12 5.24 2.65c.03.01.06 0 .07-.02c.4-.55.76-1.13 1.07-1.74c.02-.04 0-.08-.04-.09c-.57-.22-1.11-.48-1.64-.78c-.04-.02-.04-.08-.01-.11c.11-.08.22-.17.33-.25c.02-.02.05-.02.07-.01c3.44 1.57 7.15 1.57 10.55 0c.02-.01.05-.01.07.01c.11.08.22.17.33.26c.04.03.04.09-.01.11c-.52.31-1.07.56-1.64.78c-.04.01-.05.06-.04.09c.32.61.68 1.19 1.07 1.74c.03.01.06.02.09.01c1.72-.53 3.45-1.33 5.25-2.65c.02-.01.03-.03.03-.05c.44-4.53-.73-8.46-3.1-11.95c-.01-.01-.02-.02-.04-.02zM8.52 14.91c-1.03 0-1.89-.95-1.89-2.12s.84-2.12 1.89-2.12c1.06 0 1.9.96 1.89 2.12c0 1.17-.83 2.12-1.89 2.12zm6.97 0c-1.03 0-1.89-.95-1.89-2.12s.84-2.12 1.89-2.12c1.06 0 1.9.96 1.89 2.12c0 1.17-.83 2.12-1.89 2.12z"/></svg>
                                <?php echo translate('home_discord_title', 'Join Our Discord'); ?>
                            </h3>
                            <div class="discord-widget-container bg-black/30 p-1 sm:p-2 border border-[#c9a227]/20 rounded-sm">
                                <iframe src="https://discord.com/widget?id=1405755152085815337&theme=dark"
                                        width="100%"
                                        height="340"
                                        allowtransparency="true"
                                        frameborder="0"
                                        sandbox="allow-popups allow-popups-to-escape-sandbox allow-same-origin allow-scripts"
                                        class="rounded-sm"></iframe>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
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

    <script>
        const track = document.getElementById('sliderTrack');
        const slides = track.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.dot');
        let currentSlide = 0;
        let totalSlides = slides.length;
        let autoSlideInterval;

        function goToSlide(index) {
            if (index < 0) index = totalSlides - 1;
            if (index >= totalSlides) index = 0;
            currentSlide = index;
            track.style.transform = `translateX(-${currentSlide * 100}%)`;
            dots.forEach((dot, i) => dot.classList.toggle('active', i === currentSlide));
        }

        function nextSlide() { goToSlide(currentSlide + 1); }
        function prevSlide() { goToSlide(currentSlide - 1); }

        document.getElementById('nextSlide').addEventListener('click', () => {
            clearInterval(autoSlideInterval); nextSlide(); startAutoSlide();
        });
        document.getElementById('prevSlide').addEventListener('click', () => {
            clearInterval(autoSlideInterval); prevSlide(); startAutoSlide();
        });
        dots.forEach(dot => {
            dot.addEventListener('click', () => {
                clearInterval(autoSlideInterval);
                goToSlide(parseInt(dot.dataset.slide));
                startAutoSlide();
            });
        });

        function startAutoSlide() {
            clearInterval(autoSlideInterval);
            autoSlideInterval = setInterval(nextSlide, 5000);
        }
        startAutoSlide();

        const tabs = document.querySelectorAll('.tab-btn');
        const panels = {
            youtube: document.getElementById('panel-youtube'),
            bugtracker: document.getElementById('panel-bugtracker')
        };
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                Object.keys(panels).forEach(key => panels[key].classList.remove('active'));
                panels[tab.dataset.tab].classList.add('active');
            });
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft')  { clearInterval(autoSlideInterval); prevSlide(); startAutoSlide(); }
            if (e.key === 'ArrowRight') { clearInterval(autoSlideInterval); nextSlide(); startAutoSlide(); }
        });
    </script>
    <script src="<?php echo $base_path; ?>assets/js/home.js"></script>
</body>
</html>
<?php
if (isset($site_db)) {
    $site_db->close();
}
?>