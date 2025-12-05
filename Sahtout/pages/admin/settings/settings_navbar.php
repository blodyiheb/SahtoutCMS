<?php
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'moderator'])) {
    header('HTTP/1.1 403 Forbidden');
    exit(translate('error_direct_access', 'Direct access to this file is not allowed.'));
}

$page_class = $page_class ?? '';
?>

<!-- Settings Navbar -->
<nav class="settings-nav">
    <div class="settings-nav-container">
        <h5 class="settings-title"><?php echo translate('settings_nav_menu', 'Settings Menu'); ?></h5>
        <button class="mobile-toggle" aria-label="Toggle settings navigation">
            <i class="fas fa-bars"></i>
        </button>
        <ul class="settings-nav-tabs">
            <li>
                <a class="nav-link <?php echo $page_class === 'general' ? 'active' : ''; ?>" 
                   href="<?php echo $base_path; ?>admin/settings/general">
                   <i class="fas fa-cog me-1"></i> 
                   <?php echo translate('settings_nav_general', 'General'); ?>
                </a>
            </li>
            <li>
                <a class="nav-link <?php echo $page_class === 'smtp' ? 'active' : ''; ?>" 
                   href="<?php echo $base_path; ?>admin/settings/smtp">
                   <i class="fas fa-envelope me-1"></i> 
                   <?php echo translate('settings_nav_smtp', 'SMTP'); ?>
                </a>
            </li>
            <li>
                <a class="nav-link <?php echo $page_class === 'recaptcha' ? 'active' : ''; ?>" 
                   href="<?php echo $base_path; ?>admin/settings/recaptcha">
                   <i class="fas fa-shield-alt me-1"></i> 
                   <?php echo translate('settings_nav_recaptcha', 'reCAPTCHA'); ?>
                </a>
            </li>
            <li>
                <a class="nav-link <?php echo $page_class === 'realm' ? 'active' : ''; ?>" 
                   href="<?php echo $base_path; ?>admin/settings/realm">
                   <i class="fas fa-server me-1"></i> 
                   <?php echo translate('settings_nav_realm', 'Realm'); ?>
                </a>
            </li>
            <li>
                <a class="nav-link <?php echo $page_class === 'soap' ? 'active' : ''; ?>" 
                   href="<?php echo $base_path; ?>admin/settings/soap">
                   <i class="fas fa-code me-1"></i> 
                   <?php echo translate('settings_nav_soap', 'SOAP'); ?>
                </a>
            </li>
            <li>
                <a class="nav-link <?php echo $page_class === 'vote-sites' ? 'active' : ''; ?>" 
                   href="<?php echo $base_path; ?>admin/settings/vote_sites">
                   <i class="fas fa-vote-yea me-1"></i> 
                   <?php echo translate('settings_nav_vote_sites', 'Vote Sites'); ?>
                </a>
            </li>
            <li>
                <a class="nav-link <?php echo $page_class === 'page_manager' ? 'active' : ''; ?>" 
                   href="<?php echo $base_path; ?>admin/settings/page_manager">
                   <i class="fas fa-file-alt me-1"></i> 
                   <?php echo translate('settings_nav_page_manager', 'Page Manager'); ?>
                </a>
            </li>
        </ul>
    </div>
</nav>

<script src="<?php echo $base_path; ?>assets/js/pages/admin/settings/settings_navbar.js"></script>
