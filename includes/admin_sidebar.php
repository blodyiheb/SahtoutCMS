<?php
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access to this file is not allowed.');
}
require_once __DIR__ . '/paths.php';
?>
<?php
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'moderator'])) {
    exit;
}
$page_class = $page_class ?? '';
?>

<aside class="col-md-2 admin-sidebar">
    <div class="card admin-sidebar-card">
        <div class="card-header admin-sidebar-header">
            <h5 class="mb-0"><?php echo translate('admin_menu', 'Admin Menu'); ?></h5>
            <button class="mobile-toggle" aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        <div class="card-body p-2 admin-sidebar-menu">
            <ul class="nav flex-column admin-sidebar-nav">
                <li class="nav-item">
                    <a class="nav-link <?php echo $page_class === 'dashboard' ? 'active' : ''; ?>" href="<?php echo $base_path; ?>admin/dashboard">
                        <i class="fas fa-tachometer-alt me-2"></i> <?php echo translate('admin_dashboard', 'Dashboard'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $page_class === 'users' ? 'active' : ''; ?>" href="<?php echo $base_path; ?>admin/users">
                        <i class="fas fa-users me-2"></i> <?php echo translate('admin_users', 'User Management'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $page_class === 'anews' ? 'active' : ''; ?>" href="<?php echo $base_path; ?>admin/anews">
                        <i class="fas fa-newspaper me-2"></i> <?php echo translate('admin_news', 'News Management'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $page_class === 'characters' ? 'active' : ''; ?>" href="<?php echo $base_path; ?>admin/characters">
                        <i class="fas fa-user-edit me-2"></i> <?php echo translate('admin_characters', 'Character Management'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $page_class === 'shop' ? 'active' : ''; ?>" href="<?php echo $base_path; ?>admin/ashop">
                        <i class="fas fa-shopping-cart me-2"></i> <?php echo translate('admin_shop', 'Shop Management'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $page_class === 'gm_cmd' ? 'active' : ''; ?>" href="<?php echo $base_path; ?>admin/gm_cmd">
                        <i class="fas fa-terminal me-2"></i> <?php echo translate('admin_gm_commands', 'GM Commands'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $page_class === 'settings' ? 'active' : ''; ?>" href="<?php echo $base_path; ?>admin/settings/general">
                        <i class="fas fa-cogs me-2"></i> <?php echo translate('admin_settings', 'Settings'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-danger" href="<?php echo $base_path; ?>logout">
                        <i class="fas fa-sign-out-alt me-2"></i> <?php echo translate('logout', 'Logout'); ?>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</aside>
<script src="<?php echo $base_path; ?>assets/js/includes/admin_sidebar.js"></script>
