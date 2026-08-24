<?php
require_once __DIR__ . '/paths.php';

if (!defined('ALLOWED_ACCESS')) {
    if (file_exists($project_root . 'languages/language.php')) {
        require_once $project_root . 'languages/language.php';
    }
    header('HTTP/1.1 403 Forbidden');
    exit(function_exists('translate') ? translate('error_direct_access', 'Direct access to this file is not allowed.') : 'Direct access to this file is not allowed.');
}

// Load settings (if exists)
if (file_exists($project_root . 'includes/config.settings.php')) {
    require_once $project_root . 'includes/config.settings.php';
}
?>
<link rel="stylesheet" href="<?php echo $base_path; ?>node_modules/@fortawesome/fontawesome-free/css/all.min.css">

<style>
    /* ============ FOOTER THEME VARIABLES ============ */
    :root {
        --gold-primary: #f2cf5b;
        --gold-dark: #c9a227;
        --gold-deep: #8a6a14;
    }

    /* ============ FOOTER CONTAINER ============ */
    footer.wow-footer {
        background: linear-gradient(180deg, rgba(5,7,11,0.95) 0%, rgba(10,14,22,0.98) 100%);
        border-top: 1px solid rgba(201,162,39,.25);
        box-shadow: 0 -8px 24px rgba(0,0,0,.6);
        position: relative;
        margin-top: 4rem;
    }

    footer.wow-footer::before {
        content: '';
        position: absolute;
        top: 0; left: 50%; transform: translateX(-50%);
        width: 300px; height: 2px;
        background: linear-gradient(90deg, transparent, var(--gold-primary), transparent);
        box-shadow: 0 0 15px var(--gold-primary);
    }

    /* ============ FOOTER LINKS ============ */
    .footer-nav-link {
        color: #9ca3af;
        transition: all 0.3s ease;
        font-weight: 600;
        font-size: 0.875rem;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        text-decoration: none;
    }
    .footer-nav-link:hover {
        color: var(--gold-primary);
        text-shadow: 0 0 8px rgba(242,207,82,.4);
    }

    /* ============ SOCIAL ICONS (SMALLER) ============ */
    .social-icon-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px; height: 36px;
        background: rgba(0,0,0,.4);
        border: 1px solid rgba(201,162,39,.2);
        color: #d8d8d8;
        font-size: 0.95rem;
        clip-path: polygon(6px 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%, 0 6px);
        transition: all 0.3s ease;
        text-decoration: none;
    }
    .social-icon-btn:hover {
        background: linear-gradient(180deg, rgba(201,162,39,.25), rgba(201,162,39,.08));
        border-color: var(--gold-primary);
        color: var(--gold-primary);
        text-shadow: 0 0 10px rgba(242,207,82,.6);
        transform: translateY(-4px);
        box-shadow: 0 8px 16px rgba(0,0,0,.5);
    }
    .social-icon-btn img {
        transition: all 0.3s ease;
    }
    .social-icon-btn:hover img {
        /* Gold tint for the Kick image on hover */
        filter: brightness(0) invert(78%) sepia(58%) saturate(452%) hue-rotate(10deg) brightness(95%) contrast(88%);
    }

    /* ============ BACK TO TOP BUTTON (LEFT SIDE) ============ */
    #backToTop {
        position: fixed;
        bottom: 2rem; left: 2rem;
        width: 48px; height: 48px;
        background: linear-gradient(180deg, #f6d478 0%, var(--gold-dark) 48%, var(--gold-deep) 100%);
        color: #1a1200;
        border: none;
        font-size: 1.2rem;
        font-weight: 900;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        clip-path: polygon(10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%, 0 10px);
        box-shadow: 0 8px 20px rgba(0,0,0,.6), inset 0 0 0 1px rgba(255,255,255,.3);
        opacity: 0;
        pointer-events: none;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 999;
    }
    #backToTop:hover {
        transform: translateY(-4px) scale(1.05);
        box-shadow: 0 12px 24px rgba(242,207,82,.4), inset 0 0 0 1px rgba(255,255,255,.5);
        filter: drop-shadow(0 0 12px rgba(242,207,82,.5));
    }
    /* Support common JS class names for visibility toggling */
    #backToTop.show, #backToTop.active, #backToTop.visible, #backToTop.op-100 {
        opacity: 1;
        pointer-events: auto;
    }

    @media (max-width: 768px) {
        #backToTop {
            bottom: 1.5rem; left: 1.5rem;
            width: 42px; height: 42px;
            font-size: 1rem;
        }
    }
</style>

<footer class="wow-footer">
    <div class="max-w-[1600px] mx-auto px-4 md:px-8 py-8 md:py-12">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-center text-center md:text-left">
            
            <!-- Logo & Tagline -->
            <div class="flex flex-col items-center md:items-start gap-4">
                <a href="<?php echo htmlspecialchars($base_path); ?>" class="transition-transform duration-300 hover:scale-105 hover:drop-shadow-[0_0_12px_rgba(242,207,82,0.5)]">
                    <img src="<?php echo htmlspecialchars($base_path . ltrim($site_logo ?? 'img/logo.png', '/')); ?>"
                         alt="<?php echo htmlspecialchars(translate('footer_logo_alt', 'Server Logo')); ?>"
                         class="h-16 md:h-20 rounded">
                </a>
                <p class="text-gray-400 text-sm max-w-xs leading-relaxed">
                    <?php echo translate('footer_tagline', 'Join our epic World of Warcraft server adventure today!'); ?>
                </p>
            </div>

            <!-- Quick Links / Copyright -->
            <div class="flex flex-col items-center gap-4">
                <div class="flex flex-wrap justify-center gap-x-6 gap-y-2">
                    <a href="<?php echo $base_path; ?>" class="footer-nav-link"><?php echo translate('nav_home', 'Home'); ?></a>
                    <a href="<?php echo $base_path; ?>news" class="footer-nav-link"><?php echo translate('nav_news', 'News'); ?></a>
                    <a href="<?php echo $base_path; ?>shop" class="footer-nav-link"><?php echo translate('nav_shop', 'Shop'); ?></a>
                    <a href="<?php echo $base_path; ?>armory/solo_pvp" class="footer-nav-link"><?php echo translate('nav_armory', 'Armory'); ?></a>
                </div>
                <div class="h-px w-48 bg-gradient-to-r from-transparent via-[#c9a227] to-transparent opacity-50"></div>
                <p class="text-gray-500 text-xs tracking-wider uppercase font-semibold">
                    &copy; <?php echo date('Y') . " " . $site_title_name; ?>. <?php echo translate('footer_rights', 'All rights reserved.'); ?>
                </p>
            </div>

            <!-- Socials -->
            <div class="flex flex-col items-center md:items-end gap-4">
                <h4 class="text-[#f2cf5b] font-bold text-sm uppercase tracking-widest" style="font-family:'Cinzel',serif; text-shadow: 0 0 8px rgba(242,207,82,.3);">
                    <?php echo translate('footer_follow_us', 'Follow Us'); ?>
                </h4>
                <div class="flex flex-wrap justify-center md:justify-end gap-3">
                    <?php
                    $socials = [
                        'facebook'  => ['icon' => 'fab fa-facebook-f', 'alt' => 'Facebook'],
                        'twitter'   => ['icon' => 'fab fa-x-twitter', 'alt' => 'Twitter (X)'],
                        'tiktok'    => ['icon' => 'fab fa-tiktok', 'alt' => 'TikTok'],
                        'youtube'   => ['icon' => 'fab fa-youtube', 'alt' => 'YouTube'],
                        'discord'   => ['icon' => 'fab fa-discord', 'alt' => 'Discord'],
                        'twitch'    => ['icon' => 'fab fa-twitch', 'alt' => 'Twitch'],
                        'instagram' => ['icon' => 'fab fa-instagram', 'alt' => 'Instagram'],
                        'github'    => ['icon' => 'fab fa-github', 'alt' => 'GitHub'],
                        'kick'      => ['icon' => 'fab fa-kickstarter', 'alt' => 'Kick'],
                        'linkedin'  => ['icon' => 'fab fa-linkedin-in', 'alt' => 'LinkedIn']
                    ];

                    foreach ($socials as $key => $data) {
                        if (!empty($social_links[$key])) {
                            echo '<a href="' . htmlspecialchars($social_links[$key]) . '" target="_blank" aria-label="' . htmlspecialchars(translate($key . '_alt', $data['alt'])) . '" class="social-icon-btn">';
                            echo '<i class="' . $data['icon'] . '"></i>';
                            echo '</a>';
                        }
                    }

                    ?>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<button id="backToTop" title="<?php echo htmlspecialchars(translate('back_to_top', 'Back to Top')); ?>">
    <i class="fas fa-arrow-up"></i>
</button>

<!-- Back to Top Script -->
<script src="<?php echo $base_path; ?>assets/js/includes/footer.js"></script>