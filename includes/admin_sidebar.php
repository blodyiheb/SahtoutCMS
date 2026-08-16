<?php
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access to this file is not allowed.');
}
require_once __DIR__ . '/paths.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'moderator'])) {
    exit;
}
$page_class = $page_class ?? '';

// Determine active page based on URL path
$current_path = $_SERVER['REQUEST_URI'];
$active_page = '';

// Extract the page identifier from the URL
if (strpos($current_path, 'admin/dashboard') !== false) {
    $active_page = 'dashboard';
} elseif (strpos($current_path, 'admin/users') !== false) {
    $active_page = 'users';
} elseif (strpos($current_path, 'admin/anews') !== false) {
    $active_page = 'anews';
} elseif (strpos($current_path, 'admin/characters') !== false) {
    $active_page = 'characters';
} elseif (strpos($current_path, 'admin/ashop') !== false) {
    $active_page = 'shop';
} elseif (strpos($current_path, 'admin/gm_cmd') !== false) {
    $active_page = 'gm_cmd';
} elseif (strpos($current_path, 'admin/settings') !== false) {
    $active_page = 'settings';
}
?>

<!-- External Assets -->
<link rel="stylesheet" href="<?php echo $base_path; ?>node_modules/@fortawesome/fontawesome-free/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

<style>
    /* Keep only complex animations and specific effects in custom CSS */
    .text-gold-gradient {
        font-family: 'Cinzel', serif;
        font-weight: 900;
        background: linear-gradient(180deg, #fff7d6 0%, #f2cf5b 35%, #c9a227 62%, #8a6a14 100%);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        filter: drop-shadow(0 2px 4px rgba(0,0,0,.9));
        letter-spacing: .02em;
    }

    .custom-scroll::-webkit-scrollbar { width: 6px; }
    .custom-scroll::-webkit-scrollbar-track { background: rgba(0,0,0,0.2); }
    .custom-scroll::-webkit-scrollbar-thumb { background: rgba(201,162,39,0.3); border-radius: 3px; }
    .custom-scroll::-webkit-scrollbar-thumb:hover { background: rgba(201,162,39,0.5); }

    .clip-gaming {
        clip-path: polygon(6px 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%, 0 6px);
    }

    .active-link {
        color: #f2cf5b !important;
        background: linear-gradient(90deg, rgba(201,162,39,.15), rgba(201,162,39,.04)) !important;
        border-left-color: #f2cf5b !important;
        text-shadow: 0 0 12px rgba(242,207,82,.3);
        box-shadow: inset 0 0 20px rgba(201,162,39,.05);
    }
    .active-link::after {
        content: '';
        position: absolute;
        right: 0;
        top: 0;
        height: 100%;
        width: 2px;
        background: linear-gradient(180deg, transparent, #f2cf5b, transparent);
        box-shadow: 0 0 12px rgba(242,207,82,.4);
    }
    .active-link i {
        color: #f2cf5b !important;
        filter: drop-shadow(0 0 6px rgba(242,207,82,.4));
    }

    @keyframes emberDrift {
        from { background-position: 0 0; }
        to { background-position: 600px -500px; }
    }
    .ember-bg::before {
        content: '';
        position: absolute;
        inset: 0;
        pointer-events: none;
        z-index: 0;
        background-image:
            radial-gradient(1.5px 1.5px at 20% 30%, rgba(242,207,82,.3), transparent 55%),
            radial-gradient(1.5px 1.5px at 60% 70%, rgba(242,207,82,.2), transparent 55%),
            radial-gradient(2px 2px at 80% 20%, rgba(255,160,60,.2), transparent 55%);
        background-size: 600px 500px;
        animation: emberDrift 35s linear infinite;
    }

    /* Sidebar container - fixed position */
    #adminSidebar {
        position: fixed;
        top: 72px;
        left: 0;
        bottom: 0;
        width: 280px;
        z-index: 40;
        transform: translateX(0);
        transition: transform 0.3s ease-in-out;
    }

    #adminSidebar.sidebar-closed {
        transform: translateX(-100%);
    }

    /* Toggle button position */
    #sidebarToggleBtn {
        transition: left 0.3s ease-in-out;
    }

    /* Mobile overlay */
    #sidebarOverlay {
        transition: opacity 0.3s ease-in-out, pointer-events 0.3s ease-in-out;
    }

    @media (max-width: 1023px) {
        #adminSidebar {
            top: 0;
        }
    }
</style>

<!-- Mobile Hamburger Button -->
<button id="hamburgerBtn" aria-label="Toggle sidebar"
    class="lg:hidden fixed top-20 left-4 z-50 flex items-center justify-center w-11 h-11 
           bg-[#0a0e16]/95 border border-[rgba(201,162,39,.3)] rounded-lg shadow-lg shadow-black/50 
           text-[#f2cf5b] hover:bg-[rgba(201,162,39,.15)] hover:text-white hover:shadow-[0_0_15px_rgba(201,162,39,.2)]
           transition-all duration-300 backdrop-blur-sm group">
    <i class="fas fa-bars text-base transition-transform duration-300 group-hover:scale-110" id="hamburgerIcon"></i>
</button>

<!-- Mobile Sidebar Overlay -->
<div id="sidebarOverlay" class="fixed top-[72px] left-0 right-0 bottom-0 bg-black/60 backdrop-blur-sm z-30 opacity-0 pointer-events-none transition-opacity duration-300 lg:hidden"></div>

<!-- Desktop Toggle Button -->
<button id="sidebarToggleBtn" aria-label="Toggle sidebar" 
    class="hidden lg:flex fixed top-1/4 -translate-y-1/2 z-50 items-center justify-center w-7 h-24 
           bg-[#0a0e16]/95 border border-l-0 border-[rgba(201,162,39,.3)] 
           rounded-r-lg shadow-[4px_0_15px_rgba(0,0,0,.5)] 
           text-[#f2cf5b] hover:bg-[rgba(201,162,39,.15)] hover:text-white hover:shadow-[0_0_20px_rgba(201,162,39,.2)]
           transition-all duration-300 ease-in-out group backdrop-blur-sm"
    style="left: 280px;">
    <div class="absolute top-2 bottom-2 left-0 w-px bg-gradient-to-b from-transparent via-[rgba(201,162,39,.4)] to-transparent"></div>
    <i class="fas fa-chevron-left text-xs transition-transform duration-300 group-hover:scale-125" id="toggleIcon"></i>
    <div class="absolute inset-0 rounded-r-lg bg-[rgba(201,162,39,.1)] opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
</button>

<!-- Sidebar -->
<aside id="adminSidebar" 
       class="fixed top-[72px] left-0 bottom-0 w-[280px] z-40 bg-gradient-to-b from-[#0a0e16] via-[#060810] to-[#03040a] border-r border-[rgba(201,162,39,.22)] shadow-[4px_0_32px_rgba(0,0,0,.55)] flex flex-col custom-scroll">
    
    <!-- Ember Effect -->
    <div class="absolute inset-0 pointer-events-none z-0 ember-bg"></div>
    
    <!-- Header -->
    <div class="relative z-10 px-5 py-4 flex items-center justify-between min-h-[56px] flex-shrink-0 bg-gradient-to-b from-[rgba(201,162,39,.12)] to-[rgba(201,162,39,.04)] border-b-2 border-[rgba(201,162,39,.3)]">
        <div class="text-gold-gradient text-[0.95rem] flex items-center gap-2">
            <span class="text-[#f2cf5b] text-xl drop-shadow-[0_0_8px_rgba(242,207,82,.5)]">⚔</span>
            <?php echo translate('admin_menu', 'Admin Panel'); ?>
        </div>
    </div>

    <!-- Menu -->
    <nav class="relative z-10 px-3 py-3 flex-1 overflow-y-auto custom-scroll">
        <ul class="list-none p-0 m-0 flex flex-col gap-1">
            
            <li>
                <a class="group flex items-center gap-3 px-3 py-2.5 text-[0.85rem] font-semibold tracking-wide text-gray-400 bg-black/20 border-l-[3px] border-transparent hover:text-gray-200 hover:bg-[rgba(201,162,39,.08)] hover:border-[rgba(201,162,39,.4)] hover:translate-x-1 transition-all duration-200 relative clip-gaming <?php echo ($page_class === 'dashboard' || $active_page === 'dashboard') ? 'active-link' : ''; ?>" 
                   href="<?php echo $base_path; ?>admin/dashboard">
                    <i class="fas fa-tachometer-alt w-5 text-center text-[0.9rem] group-hover:scale-110 group-hover:text-[#f2cf5b] transition-all duration-300"></i> 
                    <?php echo translate('admin_dashboard', 'Dashboard'); ?>
                </a>
            </li>
            
            <li>
                <a class="group flex items-center gap-3 px-3 py-2.5 text-[0.85rem] font-semibold tracking-wide text-gray-400 bg-black/20 border-l-[3px] border-transparent hover:text-gray-200 hover:bg-[rgba(201,162,39,.08)] hover:border-[rgba(201,162,39,.4)] hover:translate-x-1 transition-all duration-200 relative clip-gaming <?php echo ($page_class === 'users' || $active_page === 'users') ? 'active-link' : ''; ?>" 
                   href="<?php echo $base_path; ?>admin/users">
                    <i class="fas fa-users w-5 text-center text-[0.9rem] group-hover:scale-110 group-hover:text-[#f2cf5b] transition-all duration-300"></i> 
                    <?php echo translate('admin_users', 'User Management'); ?>
                </a>
            </li>
            
            <li>
                <a class="group flex items-center gap-3 px-3 py-2.5 text-[0.85rem] font-semibold tracking-wide text-gray-400 bg-black/20 border-l-[3px] border-transparent hover:text-gray-200 hover:bg-[rgba(201,162,39,.08)] hover:border-[rgba(201,162,39,.4)] hover:translate-x-1 transition-all duration-200 relative clip-gaming <?php echo ($page_class === 'anews' || $active_page === 'anews') ? 'active-link' : ''; ?>" 
                   href="<?php echo $base_path; ?>admin/anews">
                    <i class="fas fa-newspaper w-5 text-center text-[0.9rem] group-hover:scale-110 group-hover:text-[#f2cf5b] transition-all duration-300"></i> 
                    <?php echo translate('admin_news', 'News Management'); ?>
                </a>
            </li>
            
            <li>
                <a class="group flex items-center gap-3 px-3 py-2.5 text-[0.85rem] font-semibold tracking-wide text-gray-400 bg-black/20 border-l-[3px] border-transparent hover:text-gray-200 hover:bg-[rgba(201,162,39,.08)] hover:border-[rgba(201,162,39,.4)] hover:translate-x-1 transition-all duration-200 relative clip-gaming <?php echo ($page_class === 'characters' || $active_page === 'characters') ? 'active-link' : ''; ?>" 
                   href="<?php echo $base_path; ?>admin/characters">
                    <i class="fas fa-user-edit w-5 text-center text-[0.9rem] group-hover:scale-110 group-hover:text-[#f2cf5b] transition-all duration-300"></i> 
                    <?php echo translate('admin_characters', 'Character Management'); ?>
                </a>
            </li>
            
            <li>
                <a class="group flex items-center gap-3 px-3 py-2.5 text-[0.85rem] font-semibold tracking-wide text-gray-400 bg-black/20 border-l-[3px] border-transparent hover:text-gray-200 hover:bg-[rgba(201,162,39,.08)] hover:border-[rgba(201,162,39,.4)] hover:translate-x-1 transition-all duration-200 relative clip-gaming <?php echo ($page_class === 'shop' || $active_page === 'shop') ? 'active-link' : ''; ?>" 
                   href="<?php echo $base_path; ?>admin/ashop">
                    <i class="fas fa-shopping-cart w-5 text-center text-[0.9rem] group-hover:scale-110 group-hover:text-[#f2cf5b] transition-all duration-300"></i> 
                    <?php echo translate('admin_shop', 'Shop Management'); ?>
                </a>
            </li>
            
            <li>
                <a class="group flex items-center gap-3 px-3 py-2.5 text-[0.85rem] font-semibold tracking-wide text-gray-400 bg-black/20 border-l-[3px] border-transparent hover:text-gray-200 hover:bg-[rgba(201,162,39,.08)] hover:border-[rgba(201,162,39,.4)] hover:translate-x-1 transition-all duration-200 relative clip-gaming <?php echo ($page_class === 'gm_cmd' || $active_page === 'gm_cmd') ? 'active-link' : ''; ?>" 
                   href="<?php echo $base_path; ?>admin/gm_cmd">
                    <i class="fas fa-terminal w-5 text-center text-[0.9rem] group-hover:scale-110 group-hover:text-[#f2cf5b] transition-all duration-300"></i> 
                    <?php echo translate('admin_gm_commands', 'GM Commands'); ?>
                </a>
            </li>
            
            <li>
                <a class="group flex items-center gap-3 px-3 py-2.5 text-[0.85rem] font-semibold tracking-wide text-gray-400 bg-black/20 border-l-[3px] border-transparent hover:text-gray-200 hover:bg-[rgba(201,162,39,.08)] hover:border-[rgba(201,162,39,.4)] hover:translate-x-1 transition-all duration-200 relative clip-gaming <?php echo ($page_class === 'settings' || $active_page === 'settings') ? 'active-link' : ''; ?>" 
                   href="<?php echo $base_path; ?>admin/settings/general">
                    <i class="fas fa-cogs w-5 text-center text-[0.9rem] group-hover:scale-110 group-hover:text-[#f2cf5b] transition-all duration-300"></i> 
                    <?php echo translate('admin_settings', 'Settings'); ?>
                </a>
            </li>

            <!-- Divider -->
            <li class="my-3 px-2">
                <div class="relative flex items-center">
                    <div class="flex-grow border-t border-[rgba(201,162,39,.2)]"></div>
                    <span class="flex-shrink-0 mx-3 text-[10px] text-[rgba(201,162,39,.6)]">◆</span>
                    <div class="flex-grow border-t border-[rgba(201,162,39,.2)]"></div>
                </div>
            </li>

            <!-- Logout -->
            <li>
                <a class="group flex items-center gap-3 px-3 py-2.5 text-[0.85rem] font-semibold tracking-wide text-red-400 bg-black/20 border-l-[3px] border-red-500/30 hover:text-red-300 hover:bg-red-500/10 hover:border-red-500 hover:translate-x-1 transition-all duration-200 relative clip-gaming" 
                   href="<?php echo $base_path; ?>logout">
                    <i class="fas fa-sign-out-alt w-5 text-center text-[0.9rem] group-hover:scale-110 transition-all duration-300"></i> 
                    <?php echo translate('logout', 'Logout'); ?>
                </a>
            </li>
            
        </ul>
    </nav>

    <!-- Footer -->
    <div class="relative z-10 px-4 py-3 border-t border-[rgba(201,162,39,.1)] flex-shrink-0 text-center text-[0.65rem] text-gray-500 font-['Inter'] tracking-widest uppercase">
        <span>© <?php echo date('Y'); ?> Sahtout WoW</span>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const toggleBtn = document.getElementById('sidebarToggleBtn');
    const toggleIcon = document.getElementById('toggleIcon');
    const hamburgerIcon = document.getElementById('hamburgerIcon');
    const mainContent = document.querySelector('.main-content-area');
    
    let isSidebarOpen = localStorage.getItem('sidebarOpen') !== 'false';
    
    function updateDesktopState(open) {
        isSidebarOpen = open;
        localStorage.setItem('sidebarOpen', open);
        
        if (window.innerWidth >= 1024) {
            if (open) {
                sidebar.classList.remove('sidebar-closed');
                toggleBtn.style.left = '280px';
                toggleIcon.className = 'fas fa-chevron-left text-xs transition-transform duration-300 group-hover:scale-125';
                if (mainContent) {
                    mainContent.classList.remove('lg:ml-0');
                    mainContent.classList.add('lg:ml-[280px]');
                }
            } else {
                sidebar.classList.add('sidebar-closed');
                toggleBtn.style.left = '0px';
                toggleIcon.className = 'fas fa-chevron-right text-xs transition-transform duration-300 group-hover:scale-125';
                if (mainContent) {
                    mainContent.classList.remove('lg:ml-[280px]');
                    mainContent.classList.add('lg:ml-0');
                }
            }
        }
    }
    
    function openMobileSidebar() {
        sidebar.classList.remove('sidebar-closed');
        overlay.classList.remove('opacity-0', 'pointer-events-none');
        overlay.classList.add('opacity-100', 'pointer-events-auto');
        document.body.style.overflow = 'hidden';
        if (hamburgerIcon) hamburgerIcon.className = 'fas fa-times text-base transition-transform duration-300 group-hover:scale-110';
    }
    
    function closeMobileSidebar() {
        sidebar.classList.add('sidebar-closed');
        overlay.classList.remove('opacity-100', 'pointer-events-auto');
        overlay.classList.add('opacity-0', 'pointer-events-none');
        document.body.style.overflow = '';
        if (hamburgerIcon) hamburgerIcon.className = 'fas fa-bars text-base transition-transform duration-300 group-hover:scale-110';
    }
    
    function handleToggle() {
        if (window.innerWidth < 1024) {
            if (sidebar.classList.contains('sidebar-closed')) {
                openMobileSidebar();
            } else {
                closeMobileSidebar();
            }
        } else {
            updateDesktopState(!isSidebarOpen);
        }
    }
    
    // Initialize
    if (window.innerWidth < 1024) {
        sidebar.classList.add('sidebar-closed');
    }
    updateDesktopState(isSidebarOpen);
    
    if (hamburgerBtn) hamburgerBtn.addEventListener('click', handleToggle);
    if (toggleBtn) toggleBtn.addEventListener('click', handleToggle);
    if (overlay) overlay.addEventListener('click', closeMobileSidebar);
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && window.innerWidth < 1024 && !sidebar.classList.contains('sidebar-closed')) {
            closeMobileSidebar();
        }
    });
    
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth >= 1024) {
                if (!sidebar.classList.contains('sidebar-closed') && !isSidebarOpen) {
                    closeMobileSidebar();
                }
                updateDesktopState(isSidebarOpen);
            } else {
                if (!sidebar.classList.contains('sidebar-closed')) {
                    closeMobileSidebar();
                }
                sidebar.classList.add('sidebar-closed');
                if (mainContent) {
                    mainContent.classList.remove('lg:ml-[280px]');
                    mainContent.classList.add('lg:ml-0');
                }
            }
        }, 250);
    });
    
    sidebar.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth < 1024) {
                closeMobileSidebar();
            }
        });
    });
});
</script>