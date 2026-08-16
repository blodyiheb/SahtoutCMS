<?php
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'moderator'])) {
    header('HTTP/1.1 403 Forbidden');
    exit(translate('error_direct_access', 'Direct access to this file is not allowed.'));
}

$page_class = $page_class ?? '';
?>

<style>
    .settings-nav-link {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.6rem 1.2rem;
        font-weight: 600;
        font-size: 0.85rem;
        letter-spacing: .04em;
        color: #9ca3af;
        text-decoration: none;
        background: rgba(0,0,0,.2);
        border-bottom: 2px solid transparent;
        clip-path: polygon(8px 0, 100% 0, 100% calc(100% - 8px), calc(100% - 8px) 100%, 0 100%, 0 8px);
        transition: all .25s ease;
        white-space: nowrap;
        font-family: 'Inter', sans-serif;
    }

    .settings-nav-link:hover {
        color: #e5e7eb;
        background: rgba(201,162,39,.08);
        border-bottom-color: rgba(201,162,39,.3);
    }

    .settings-nav-link.active {
        color: #f2cf5b;
        background: linear-gradient(180deg, rgba(201,162,39,.15), rgba(201,162,39,.04));
        border-bottom-color: #f2cf5b;
        text-shadow: 0 0 12px rgba(242,207,82,.3);
        box-shadow: inset 0 -2px 20px rgba(201,162,39,.05);
    }

    .settings-nav-link i {
        width: 18px;
        text-align: center;
        font-size: 0.9rem;
        transition: all .3s ease;
    }

    .settings-nav-link:hover i {
        color: #f2cf5b;
        transform: scale(1.1);
    }

    .settings-nav-link.active i {
        color: #f2cf5b;
        filter: drop-shadow(0 0 6px rgba(242,207,82,.4));
    }

    .settings-nav-scroll {
        scrollbar-width: thin;
        scrollbar-color: rgba(201,162,39,.3) transparent;
    }

    .settings-nav-scroll::-webkit-scrollbar {
        height: 4px;
    }
    .settings-nav-scroll::-webkit-scrollbar-track {
        background: transparent;
    }
    .settings-nav-scroll::-webkit-scrollbar-thumb {
        background: rgba(201,162,39,.3);
        border-radius: 2px;
    }
    .settings-nav-scroll::-webkit-scrollbar-thumb:hover {
        background: rgba(201,162,39,.5);
    }

    .settings-mobile-toggle {
        display: none;
        background: transparent;
        border: 1px solid rgba(201,162,39,.3);
        color: #f2cf5b;
        padding: 0.4rem 0.7rem;
        cursor: pointer;
        transition: all 0.3s ease;
        clip-path: polygon(4px 0, 100% 0, 100% calc(100% - 4px), calc(100% - 4px) 100%, 0 100%, 0 4px);
        font-size: 1.1rem;
    }

    .settings-mobile-toggle:hover {
        background: rgba(201,162,39,.15);
        border-color: #f2cf5b;
    }

    @media (max-width: 767px) {
        .settings-mobile-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .settings-nav-tabs {
            display: none;
            flex-direction: column;
            width: 100%;
            gap: 0.25rem;
            padding-top: 0.75rem;
        }

        .settings-nav-tabs.open {
            display: flex;
        }

        .settings-nav-link {
            white-space: normal;
            justify-content: center;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid rgba(201,162,39,.1);
            border-left: 3px solid transparent;
            clip-path: polygon(6px 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%, 0 6px);
        }

        .settings-nav-link.active {
            border-left-color: #f2cf5b;
            border-bottom-color: rgba(201,162,39,.1);
        }
    }

    @media (min-width: 768px) {
        .settings-nav-tabs {
            display: flex !important;
            flex-wrap: nowrap;
            gap: 0.25rem;
        }
    }
</style>

<!-- Settings Navbar -->
<nav class="relative z-10 w-full">
    <div class="panel px-3 md:px-4 py-2 md:py-3 flex items-center justify-between gap-3 flex-wrap">
        
        <h5 class="section-title text-sm md:text-base flex items-center gap-2 m-0">
            <i class="fas fa-sliders-h text-[#f2cf5b]"></i>
            <?php echo translate('settings_nav_menu', 'Settings Menu'); ?>
        </h5>

        <button class="settings-mobile-toggle md:hidden" aria-label="Toggle settings navigation">
            <i class="fas fa-bars"></i>
        </button>

        <ul class="settings-nav-tabs settings-nav-scroll flex items-center gap-0.5 overflow-x-auto flex-nowrap md:flex-wrap list-none p-0 m-0">
            <li class="flex-shrink-0">
                <a class="settings-nav-link <?php echo $page_class === 'general' ? 'active' : ''; ?>" 
                   href="<?php echo $base_path; ?>admin/settings/general">
                   <i class="fas fa-cog"></i> 
                   <?php echo translate('settings_nav_general', 'General'); ?>
                </a>
            </li>
            <li class="flex-shrink-0">
                <a class="settings-nav-link <?php echo $page_class === 'smtp' ? 'active' : ''; ?>" 
                   href="<?php echo $base_path; ?>admin/settings/smtp">
                   <i class="fas fa-envelope"></i> 
                   <?php echo translate('settings_nav_smtp', 'SMTP'); ?>
                </a>
            </li>
            <li class="flex-shrink-0">
                <a class="settings-nav-link <?php echo $page_class === 'recaptcha' ? 'active' : ''; ?>" 
                   href="<?php echo $base_path; ?>admin/settings/recaptcha">
                   <i class="fas fa-shield-alt"></i> 
                   <?php echo translate('settings_nav_recaptcha', 'reCAPTCHA'); ?>
                </a>
            </li>
            <li class="flex-shrink-0">
                <a class="settings-nav-link <?php echo $page_class === 'realm' ? 'active' : ''; ?>" 
                   href="<?php echo $base_path; ?>admin/settings/realm">
                   <i class="fas fa-server"></i> 
                   <?php echo translate('settings_nav_realm', 'Realm'); ?>
                </a>
            </li>
            <li class="flex-shrink-0">
                <a class="settings-nav-link <?php echo $page_class === 'soap' ? 'active' : ''; ?>" 
                   href="<?php echo $base_path; ?>admin/settings/soap">
                   <i class="fas fa-code"></i> 
                   <?php echo translate('settings_nav_soap', 'SOAP'); ?>
                </a>
            </li>
            <li class="flex-shrink-0">
                <a class="settings-nav-link <?php echo $page_class === 'vote-sites' ? 'active' : ''; ?>" 
                   href="<?php echo $base_path; ?>admin/settings/vote_sites">
                   <i class="fas fa-vote-yea"></i> 
                   <?php echo translate('settings_nav_vote_sites', 'Vote Sites'); ?>
                </a>
            </li>
            <li class="flex-shrink-0">
                <a class="settings-nav-link <?php echo $page_class === 'page_manager' ? 'active' : ''; ?>" 
                   href="<?php echo $base_path; ?>admin/settings/page_manager">
                   <i class="fas fa-file-alt"></i> 
                   <?php echo translate('settings_nav_page_manager', 'Page Manager'); ?>
                </a>
            </li>
        </ul>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.querySelector('.settings-mobile-toggle');
    const navTabs = document.querySelector('.settings-nav-tabs');
    
    if (toggleBtn && navTabs) {
        toggleBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            navTabs.classList.toggle('open');
            const icon = this.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-bars');
                icon.classList.toggle('fa-times');
            }
        });
        
        // Close on outside click (mobile)
        document.addEventListener('click', function(e) {
            const nav = document.querySelector('.settings-nav');
            if (window.innerWidth < 768 && nav && !nav.contains(e.target)) {
                navTabs.classList.remove('open');
                const icon = toggleBtn.querySelector('i');
                if (icon) {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            }
        });
        
        // Close on nav click (mobile)
        navTabs.querySelectorAll('.settings-nav-link').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 768) {
                    navTabs.classList.remove('open');
                    const icon = toggleBtn.querySelector('i');
                    if (icon) {
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    }
                }
            });
        });
    }
});
</script>