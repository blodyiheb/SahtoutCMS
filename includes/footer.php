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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<footer class="bg-[#0e0e0e9f] text-white py-5 px-4 font-sans w-full clear-both">
    <div class="flex flex-wrap justify-between items-center max-w-7xl mx-auto max-[768px]:flex-col max-[768px]:text-center">
        <!-- Logo -->
        <div class="max-[768px]:order-1 max-[768px]:mb-4">
            <a href="<?php echo htmlspecialchars($base_path); ?>">
                <img src="<?php echo htmlspecialchars($base_path . ltrim($site_logo ?? 'img/logo.png', '/')); ?>"
                     alt="<?php echo htmlspecialchars(translate('footer_logo_alt', 'Sahtout Server Logo')); ?>"
                     class="w-[120px] max-[480px]:h-[70px]">
            </a>
        </div>

        <!-- Copyright -->
        <div class="flex-1 text-center max-[768px]:order-3 max-[768px]:mt-4">
            <p>© <?php echo date('Y') ." ". $site_title_name ;?>  by SahtoutCMS. All rights reserved.</p>
        </div>

        <!-- Socials -->
        <div class="flex gap-[15px] items-center max-[768px]:order-2 max-[768px]:my-4">
            <?php if (!empty($social_links['facebook'])): ?>
                <a href="<?php echo htmlspecialchars($social_links['facebook']); ?>" target="_blank" aria-label="<?php echo htmlspecialchars(translate('facebook_alt', 'Facebook')); ?>" class="text-white text-xl transition-all duration-300 hover:text-[#e5ff00] hover:scale-110 max-[480px]:text-lg">
                    <i class="fab fa-facebook-f"></i>
                </a>
            <?php endif; ?>

            <?php if (!empty($social_links['twitter'])): ?>
                <a href="<?php echo htmlspecialchars($social_links['twitter']); ?>" target="_blank" aria-label="<?php echo htmlspecialchars(translate('twitter_alt', 'Twitter (X)')); ?>" class="text-white text-xl transition-all duration-300 hover:text-[#e5ff00] hover:scale-110 max-[480px]:text-lg">
                    <i class="fab fa-x-twitter"></i>
                </a>
            <?php endif; ?>

            <?php if (!empty($social_links['tiktok'])): ?>
                <a href="<?php echo htmlspecialchars($social_links['tiktok']); ?>" target="_blank" aria-label="<?php echo htmlspecialchars(translate('tiktok_alt', 'TikTok')); ?>" class="text-white text-xl transition-all duration-300 hover:text-[#e5ff00] hover:scale-110 max-[480px]:text-lg">
                    <i class="fab fa-tiktok"></i>
                </a>
            <?php endif; ?>

            <?php if (!empty($social_links['youtube'])): ?>
                <a href="<?php echo htmlspecialchars($social_links['youtube']); ?>" target="_blank" aria-label="<?php echo htmlspecialchars(translate('youtube_alt', 'YouTube')); ?>" class="text-white text-xl transition-all duration-300 hover:text-[#e5ff00] hover:scale-110 max-[480px]:text-lg">
                    <i class="fab fa-youtube"></i>
                </a>
            <?php endif; ?>

            <?php if (!empty($social_links['discord'])): ?>
                <a href="<?php echo htmlspecialchars($social_links['discord']); ?>" target="_blank" aria-label="<?php echo htmlspecialchars(translate('discord_alt', 'Discord')); ?>" class="text-white text-xl transition-all duration-300 hover:text-[#e5ff00] hover:scale-110 max-[480px]:text-lg">
                    <i class="fab fa-discord"></i>
                </a>
            <?php endif; ?>

            <?php if (!empty($social_links['twitch'])): ?>
                <a href="<?php echo htmlspecialchars($social_links['twitch']); ?>" target="_blank" aria-label="<?php echo htmlspecialchars(translate('twitch_alt', 'Twitch')); ?>" class="text-white text-xl transition-all duration-300 hover:text-[#e5ff00] hover:scale-110 max-[480px]:text-lg">
                    <i class="fab fa-twitch"></i>
                </a>
            <?php endif; ?>

            <?php if (!empty($social_links['kick'])): ?>
                <a href="<?php echo htmlspecialchars($social_links['kick']); ?>" target="_blank" aria-label="<?php echo htmlspecialchars(translate('kick_alt', 'Kick')); ?>" class="text-white transition-all duration-300 hover:scale-110">
                    <img src="<?php echo htmlspecialchars($base_path . 'img/icons/kick-logo.png'); ?>"
                         alt="<?php echo htmlspecialchars(translate('kick_alt', 'Kick')); ?>"
                         class="w-5 h-[14px] brightness-0 invert transition-transform duration-300 hover:scale-110 max-[480px]:w-[18px] max-[480px]:h-[14px] max-[480px]:mb-1.5">
                </a>
            <?php endif; ?>

            <?php if (!empty($social_links['instagram'])): ?>
                <a href="<?php echo htmlspecialchars($social_links['instagram']); ?>" target="_blank" aria-label="<?php echo htmlspecialchars(translate('instagram_alt', 'Instagram')); ?>" class="text-white text-xl transition-all duration-300 hover:text-[#e5ff00] hover:scale-110 max-[480px]:text-lg">
                    <i class="fab fa-instagram"></i>
                </a>
            <?php endif; ?>

            <?php if (!empty($social_links['github'])): ?>
                <a href="<?php echo htmlspecialchars($social_links['github']); ?>" target="_blank" aria-label="<?php echo htmlspecialchars(translate('github_alt', 'GitHub')); ?>" class="text-white text-xl transition-all duration-300 hover:text-[#e5ff00] hover:scale-110 max-[480px]:text-lg">
                    <i class="fab fa-github"></i>
                </a>
            <?php endif; ?>

            <?php if (!empty($social_links['linkedin'])): ?>
                <a href="<?php echo htmlspecialchars($social_links['linkedin']); ?>" target="_blank" aria-label="<?php echo htmlspecialchars(translate('linkedin_alt', 'LinkedIn')); ?>" class="text-white text-xl transition-all duration-300 hover:text-[#e5ff00] hover:scale-110 max-[480px]:text-lg">
                    <i class="fab fa-linkedin-in"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Back to Top Button -->
    <button id="backToTop" title="<?php echo htmlspecialchars(translate('back_to_top', 'Back to Top')); ?>" 
            class="fixed bottom-10 left-5 bg-gradient-to-br from-[#88eb06] to-[#fbff00] text-white border-none rounded-full w-[50px] h-[50px] text-xl cursor-pointer flex items-center justify-center opacity-0 pointer-events-none z-[1000] shadow-[0_4px_8px_rgba(0,0,0,0.3)] transition-all duration-300 ease-in-out hover:bg-gradient-to-br hover:from-[#00cc66] hover:to-[#00ff88] hover:scale-110 active:scale-90 max-[768px]:bottom-5 max-[768px]:left-3.5 max-[768px]:w-10 max-[768px]:h-10 max-[768px]:text-base max-[480px]:bottom-3.5 max-[480px]:left-2.5 max-[480px]:w-9 max-[480px]:h-9 max-[480px]:text-sm">
        <i class="fas fa-arrow-up"></i>
    </button>
</footer>

<!-- Back to Top Script -->
<script src="<?php echo $base_path; ?>assets/js/includes/footer.js"></script>