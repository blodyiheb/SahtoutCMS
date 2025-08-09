<?php
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'moderator'])) {
    exit;
}
$page_class = $page_class ?? '';
?>

<aside class="col-md-3">
    <div class="card admin-sidebar-card">
        <div class="card-header admin-sidebar-header">
            <h5 class="mb-0">Admin Menu</h5>
        </div>
        <div class="card-body p-2">
            <ul class="nav flex-column admin-sidebar-nav">
                <li class="nav-item">
                    <a class="nav-link <?php echo $page_class === 'dashboard' ? 'active' : ''; ?>" href="/Sahtout/admin/index.php">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $page_class === 'users' ? 'active' : ''; ?>" href="/Sahtout/admin/users.php">
                        <i class="fas fa-users me-2"></i> User Management
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $page_class === 'anews' ? 'active' : ''; ?>" href="/Sahtout/admin/anews.php">
                        <i class="fas fa-newspaper me-2"></i> News Management
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $page_class === 'characters' ? 'active' : ''; ?>" href="/Sahtout/admin/characters.php">
                        <i class="fas fa-user-edit me-2"></i> Character Management
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $page_class === 'shop' ? 'active' : ''; ?>" href="/Sahtout/admin/ashop.php">
                        <i class="fas fa-shopping-cart me-2"></i> Shop Management
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $page_class === 'gm_cmd' ? 'active' : ''; ?>" href="/Sahtout/admin/gm_cmd.php">
                        <i class="fas fa-terminal me-2"></i> GM Commands
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-danger" href="/Sahtout/pages/logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</aside>

<style>
    /* Main sidebar card */
    .admin-sidebar-card {
        text-align: center;
        background: rgba(255, 255, 255, 0.9);
        border: 2px solid #ccc;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    /* Card header */
    .admin-sidebar-header {
        background: rgba(230, 230, 230, 0.9);
        border-bottom: 1px solid #ccc;
        color: #333;
        padding: 1rem;
        font-weight: bold;
    }
    
    /* Navigation links */
    .admin-sidebar-nav .nav-link {
        color: #333;
        padding: 0.75rem 1rem;
        border-radius: 4px;
        margin-bottom: 0.5rem;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
    }
    
    .admin-sidebar-nav .nav-link:hover {
        background: #e9ecef;
        color: #000;
        font-weight: bold;
    }
    
    .admin-sidebar-nav .nav-link.active {
        background: #007bff;
        color: #fff;
        font-weight: bold;
    }
    
    .admin-sidebar-nav .nav-link i {
        width: 20px;
        text-align: center;
    }
    
    /* Logout link */
    .admin-sidebar-nav .nav-link.text-danger {
        color: #dc3545 !important;
    }
    
    .admin-sidebar-nav .nav-link.text-danger:hover {
        background: #f8d7da;
        color: #c82333 !important;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .admin-sidebar-nav .nav-link {
            padding: 0.6rem 0.8rem;
            font-size: 0.9rem;
        }
        
        .admin-sidebar-header {
            padding: 0.75rem;
            font-size: 1.1rem;
        }
        
        .admin-sidebar-card {
            margin-bottom: 1rem;
        }
    }
</style>