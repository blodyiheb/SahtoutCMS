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
<link rel="stylesheet" href="<?php echo htmlspecialchars($base_path); ?>assets/css/footer.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<footer>
  <div class="footer-container">
    <!-- Logo -->
    <div class="footer-logo">
      <a href="<?php echo htmlspecialchars($base_path); ?>">
        <img src="<?php echo htmlspecialchars($base_path . ltrim($site_logo ?? 'img/logo.png', '/')); ?>"
             alt="<?php echo htmlspecialchars(translate('footer_logo_alt', 'Sahtout Server Logo')); ?>"
             class="footer-logo-img">
      </a>
    </div>

    <!-- Copyright -->
    <div class="footer-center">
      <p>© <?php echo date('Y') ." ". $site_title_name ;?>  by SahtoutCMS. All rights reserved.</p>
    </div>

    <!-- Socials -->
    <div class="footer-socials">
      <?php if (!empty($social_links['facebook'])): ?>
        <a href="<?php echo htmlspecialchars($social_links['facebook']); ?>" target="_blank" aria-label="<?php echo htmlspecialchars(translate('facebook_alt', 'Facebook')); ?>">
          <i class="fab fa-facebook-f"></i>
        </a>
      <?php endif; ?>

      <?php if (!empty($social_links['twitter'])): ?>
        <a href="<?php echo htmlspecialchars($social_links['twitter']); ?>" target="_blank" aria-label="<?php echo htmlspecialchars(translate('twitter_alt', 'Twitter (X)')); ?>">
          <i class="fab fa-x-twitter"></i>
        </a>
      <?php endif; ?>

      <?php if (!empty($social_links['tiktok'])): ?>
        <a href="<?php echo htmlspecialchars($social_links['tiktok']); ?>" target="_blank" aria-label="<?php echo htmlspecialchars(translate('tiktok_alt', 'TikTok')); ?>">
          <i class="fab fa-tiktok"></i>
        </a>
      <?php endif; ?>

      <?php if (!empty($social_links['youtube'])): ?>
        <a href="<?php echo htmlspecialchars($social_links['youtube']); ?>" target="_blank" aria-label="<?php echo htmlspecialchars(translate('youtube_alt', 'YouTube')); ?>">
          <i class="fab fa-youtube"></i>
        </a>
      <?php endif; ?>

      <?php if (!empty($social_links['discord'])): ?>
        <a href="<?php echo htmlspecialchars($social_links['discord']); ?>" target="_blank" aria-label="<?php echo htmlspecialchars(translate('discord_alt', 'Discord')); ?>">
          <i class="fab fa-discord"></i>
        </a>
      <?php endif; ?>

      <?php if (!empty($social_links['twitch'])): ?>
        <a href="<?php echo htmlspecialchars($social_links['twitch']); ?>" target="_blank" aria-label="<?php echo htmlspecialchars(translate('twitch_alt', 'Twitch')); ?>">
          <i class="fab fa-twitch"></i>
        </a>
      <?php endif; ?>

      <?php if (!empty($social_links['kick'])): ?>
        <a href="<?php echo htmlspecialchars($social_links['kick']); ?>" target="_blank" aria-label="<?php echo htmlspecialchars(translate('kick_alt', 'Kick')); ?>">
          <img src="<?php echo htmlspecialchars($base_path . 'img/icons/kick-logo.png'); ?>"
               alt="<?php echo htmlspecialchars(translate('kick_alt', 'Kick')); ?>"
               class="kick-icon">
        </a>
      <?php endif; ?>

      <?php if (!empty($social_links['instagram'])): ?>
        <a href="<?php echo htmlspecialchars($social_links['instagram']); ?>" target="_blank" aria-label="<?php echo htmlspecialchars(translate('instagram_alt', 'Instagram')); ?>">
          <i class="fab fa-instagram"></i>
        </a>
      <?php endif; ?>

      <?php if (!empty($social_links['github'])): ?>
        <a href="<?php echo htmlspecialchars($social_links['github']); ?>" target="_blank" aria-label="<?php echo htmlspecialchars(translate('github_alt', 'GitHub')); ?>">
          <i class="fab fa-github"></i>
        </a>
      <?php endif; ?>

      <?php if (!empty($social_links['linkedin'])): ?>
        <a href="<?php echo htmlspecialchars($social_links['linkedin']); ?>" target="_blank" aria-label="<?php echo htmlspecialchars(translate('linkedin_alt', 'LinkedIn')); ?>">
          <i class="fab fa-linkedin-in"></i>
        </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Back to Top Button -->
  <button id="backToTop" title="<?php echo htmlspecialchars(translate('back_to_top', 'Back to Top')); ?>">
    <i class="fas fa-arrow-up"></i>
  </button>
</footer>

<!-- Back to Top Script -->
 <script src="<?php echo $base_path; ?>assets/js/includes/footer.js"></script>
