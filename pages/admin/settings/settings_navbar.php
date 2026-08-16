<?php
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'moderator'])) {
    header('HTTP/1.1 403 Forbidden');
    exit(translate('error_direct_access', 'Direct access to this file is not allowed.'));
}

$page_class = $page_class ?? '';

// Determine active page based on URL path for settings
$current_path = $_SERVER['REQUEST_URI'];
$active_setting = '';

if (strpos($current_path, 'admin/settings/general') !== false) {
    $active_setting = 'general';
} elseif (strpos($current_path, 'admin/settings/smtp') !== false) {
    $active_setting = 'smtp';
} elseif (strpos($current_path, 'admin/settings/recaptcha') !== false) {
    $active_setting = 'recaptcha';
} elseif (strpos($current_path, 'admin/settings/realm') !== false) {
    $active_setting = 'realm';
} elseif (strpos($current_path, 'admin/settings/soap') !== false) {
    $active_setting = 'soap';
} elseif (strpos($current_path, 'admin/settings/vote_sites') !== false) {
    $active_setting = 'vote-sites';
} elseif (strpos($current_path, 'admin/settings/page_manager') !== false) {
    $active_setting = 'page_manager';
}

$is_active = function($setting) use ($page_class, $active_setting) {
    return ($page_class === $setting || $active_setting === $setting);
};

// Base Tailwind classes for the <a> tag
$link_base = 'group relative flex items-center gap-2 px-4 py-2.5 font-semibold text-xs tracking-wide transition-all duration-300 box-border w-full ';
$link_base .= '[clip-path:polygon(8px_0,100%_0,100%_calc(100%-8px),calc(100%-8px)_100%,0_100%,0_8px)] ';

// Mobile overrides for <a>
$link_mobile = 'max-md:whitespace-normal max-md:justify-start max-md:px-4 max-md:py-3 max-md:border-b max-md:border-[rgba(201,162,39,0.1)] max-md:border-l-[3px] max-md:border-l-transparent max-md:[clip-path:polygon(6px_0,100%_0,100%_calc(100%-6px),calc(100%-6px)_100%,0_100%,0_6px)] max-md:min-h-[44px] max-md:text-sm ';

// Desktop overrides for <a>
$link_desktop = 'md:justify-center md:px-2 md:py-2.5 md:whitespace-nowrap md:min-h-[42px] md:border-b-2 md:border-transparent ';

$icon_base = 'w-4 text-center text-sm transition-all duration-300 shrink-0 group-hover:text-[#f2cf5b] group-hover:scale-110 max-md:w-5 ';

$get_link_classes = function($setting) use ($is_active, $link_base, $link_mobile, $link_desktop) {
    $active = $is_active($setting);
    
    $active_classes = $active 
        ? 'text-[#f2cf5b] bg-gradient-to-b from-[rgba(201,162,39,0.15)] to-[rgba(201,162,39,0.04)] border-b-[#f2cf5b] [text-shadow:0_0_12px_rgba(242,207,82,0.3)] shadow-[inset_0_-2px_20px_rgba(201,162,39,0.05)] max-md:border-l-[#f2cf5b] max-md:border-b-[rgba(201,162,39,0.1)]' 
        : 'text-gray-400 bg-black/20 hover:text-gray-200 hover:bg-[rgba(201,162,39,0.08)] hover:border-b-[rgba(201,162,39,0.3)]';
        
    return $link_base . $link_mobile . $link_desktop . $active_classes;
};

$get_icon_classes = function($setting) use ($is_active, $icon_base) {
    $active = $is_active($setting);
    return $icon_base . ($active ? 'text-[#f2cf5b] [filter:drop-shadow(0_0_6px_rgba(242,207,82,0.4))]' : '');
};

$links = [
    'general'      => ['icon' => 'fa-cog',        'label' => translate('settings_nav_general', 'General')],
    'smtp'         => ['icon' => 'fa-envelope',   'label' => translate('settings_nav_smtp', 'SMTP')],
    'recaptcha'    => ['icon' => 'fa-shield-alt', 'label' => translate('settings_nav_recaptcha', 'reCAPTCHA')],
    'realm'        => ['icon' => 'fa-server',     'label' => translate('settings_nav_realm', 'Realm')],
    'soap'         => ['icon' => 'fa-code',       'label' => translate('settings_nav_soap', 'SOAP')],
    'vote-sites'   => ['icon' => 'fa-vote-yea',   'label' => translate('settings_nav_vote_sites', 'Vote Sites')],
    'page_manager' => ['icon' => 'fa-file-alt',   'label' => translate('settings_nav_page_manager', 'Page Manager')],
];
?>

<!-- Settings Navbar -->
<nav class="settings-nav relative z-10 w-full">
    <div class="panel px-3 md:px-4 py-2 md:py-3">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            
            <h5 class="section-title text-sm md:text-base flex items-center gap-2 m-0 shrink-0">
                <i class="fas fa-sliders-h text-[#f2cf5b]"></i>
                <?php echo translate('settings_nav_menu', 'Settings Menu'); ?>
            </h5>

            <!-- Mobile Toggle Button -->
            <button class="settings-mobile-toggle flex md:hidden items-center justify-center min-w-[42px] min-h-[42px] px-2.5 py-1.5 text-lg text-[#f2cf5b] bg-transparent border border-[rgba(201,162,39,0.3)] cursor-pointer transition-all duration-300 [clip-path:polygon(4px_0,100%_0,100%_calc(100%-4px),calc(100%-4px)_100%,0_100%,0_4px)] shrink-0 hover:bg-[rgba(201,162,39,0.15)] hover:border-[#f2cf5b]" aria-label="Toggle settings navigation">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Navigation Tabs (100% Width Flex Layout) -->
            <ul id="settingsNavTabs" data-open="false" class="
                flex flex-col w-full gap-1 max-h-0 overflow-hidden transition-all duration-300 ease-in-out opacity-0 pointer-events-none
                data-[open=true]:max-h-[600px] data-[open=true]:opacity-100 data-[open=true]:pt-3 data-[open=true]:pb-2 data-[open=true]:pointer-events-auto
                
                md:flex-row md:flex-wrap md:justify-center md:gap-2 md:max-h-none md:opacity-100 md:overflow-visible md:pointer-events-auto md:py-0 md:transition-none
                [&::-webkit-scrollbar]:h-1 [&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar-thumb]:bg-[rgba(201,162,39,0.3)] [&::-webkit-scrollbar-thumb:hover]:bg-[rgba(201,162,39,0.5)]
            ">
                <?php foreach ($links as $key => $data): ?>
                    <!-- flex-1 forces buttons to share the 100% width equally -->
                    <li class="flex-1 min-w-[110px] flex">
                        <a class="<?php echo $get_link_classes($key); ?>" 
                           href="<?php echo $base_path; ?>admin/settings/<?php echo str_replace('-', '_', $key); ?>">
                           <i class="fas <?php echo $data['icon']; ?> <?php echo $get_icon_classes($key); ?>"></i> 
                           <span><?php echo $data['label']; ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.querySelector('.settings-mobile-toggle');
    const navTabs = document.getElementById('settingsNavTabs');
    const nav = document.querySelector('.settings-nav');
    
    if (toggleBtn && navTabs) {
        function toggleMenu(e) {
            if (e) e.stopPropagation();
            // Toggle the data-open attribute to trigger Tailwind's data-[open=true] variants
            const isOpen = navTabs.getAttribute('data-open') === 'true';
            navTabs.setAttribute('data-open', !isOpen);
            
            const icon = toggleBtn.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-bars');
                icon.classList.toggle('fa-times');
            }
        }
        
        function closeMenu() {
            navTabs.setAttribute('data-open', 'false');
            const icon = toggleBtn.querySelector('i');
            if (icon) {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        }
        
        toggleBtn.addEventListener('click', toggleMenu);
        
        // Close on outside click (mobile)
        document.addEventListener('click', function(e) {
            if (window.innerWidth < 768 && nav && !nav.contains(e.target)) {
                closeMenu();
            }
        });
        
        // Close on nav link click (mobile)
        navTabs.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 768) {
                    closeMenu();
                }
            });
        });
        
        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && window.innerWidth < 768) {
                closeMenu();
            }
        });
        
        // Handle window resize
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (window.innerWidth >= 768 && navTabs.getAttribute('data-open') === 'true') {
                    closeMenu();
                }
            }, 250);
        });
    }
});
</script>