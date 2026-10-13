<?php
// Ensure this file is not accessed directly
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access not allowed.');
}
?>

<!-- Installer Footer -->
<footer class="glass-footer w-full border-t border-[rgba(160,130,60,0.3)] py-6 px-4 text-center shadow-[0_-2px_20px_rgba(139,115,60,0.3)] font-['Cinzel'] mt-auto">
    
    <!-- Support Section -->
    <div class="mb-5 flex flex-col items-center">
        <!-- Main Support Image (Non-clickable Header Badge) -->
        <img 
            src="support-button.png"
            alt="<?= translate('support_alt', 'Support SahtoutCMS') ?>"
            class="h-14 md:h-18 w-auto object-contain mb-3 drop-shadow-[0_2px_8px_rgba(0,0,0,0.5)]"
        >

        <!-- Platform Image Badges -->
        <div class="flex items-center justify-center gap-3 flex-wrap">
            <!-- Buy Me a Coffee -->
            <a href="https://www.buymeacoffee.com/sahtoutdev" 
               target="_blank" 
               rel="noopener noreferrer" 
               title="Support on Buy Me a Coffee" 
               aria-label="Buy Me a Coffee" 
               class="support-img-btn">
                <img src="<?= $base_path ?? '' ?>img/extra/bmc-button.svg" 
                     alt="Buy Me a Coffee" 
                     class="h-10 w-auto rounded-xl">
            </a>

            <!-- Ko-fi -->
            <a href="https://ko-fi.com/sahtoutdev" 
               target="_blank" 
               rel="noopener noreferrer" 
               title="Support on Ko-fi" 
               aria-label="Ko-fi" 
               class="support-img-btn">
                <img src="<?= $base_path ?? '' ?>img/extra/support_me_on_kofi_blue.png" 
                     alt="Support on Ko-fi" 
                     class="h-10 w-auto rounded-xl">
            </a>
        </div>
    </div>
    
    <!-- Social Links -->
    <p class="text-[#e8dcc8] text-sm md:text-base m-0">
        <span class="text-[#d4af37]">🌟 <?= translate('footer_connect', 'Connect with Me:') ?></span>
        
        <a href="https://github.com/blodyiheb/SahtoutCMS" target="_blank" rel="noopener noreferrer" class="text-[#d4af37] hover:text-[#e8c84a] transition-colors duration-300 mx-3 no-underline">
            <i class="fab fa-github mr-1"></i> <?= translate('footer_github', 'GitHub') ?>
        </a>
        
        <span class="text-[#6b5a3e]">|</span>
        
        <a href="https://www.youtube.com/@Blodyone" target="_blank" rel="noopener noreferrer" class="text-[#d4af37] hover:text-[#e8c84a] transition-colors duration-300 mx-3 no-underline">
            <i class="fab fa-youtube mr-1"></i> <?= translate('footer_youtube', 'YouTube') ?>
        </a>
        
        <span class="text-[#6b5a3e]">|</span>
        
        <a href="https://discord.gg/chxXTXXQ6M" target="_blank" rel="noopener noreferrer" class="text-[#d4af37] hover:text-[#e8c84a] transition-colors duration-300 mx-3 no-underline">
            <i class="fab fa-discord mr-1"></i> <?= translate('footer_discord', 'Discord') ?>
        </a>
    </p>
    
    <!-- Copyright -->
    <p class="text-[#8fbc8f] text-xs md:text-sm mt-3 m-0">
        <i class="far fa-copyright mr-1"></i>
        <?= (int)date('Y') ?> Sahtout CMS. <?= translate('footer_all_rights', 'All rights reserved.') ?>
    </p>
</footer>

<style>
    /* Glass-morphism effect with warm brown tones */
    .glass-footer {
        background: rgba(60, 40, 25, 0.75);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        position: relative;
        margin-top: auto;
    }

    /* Support Button Hover Effects */
    .support-img-btn {
        display: inline-block;
        transition: transform 0.25s ease, opacity 0.25s ease, box-shadow 0.25s ease;
        border-radius: 0.75rem;
    }

    .support-img-btn:hover {
        transform: translateY(-2px);
        opacity: 0.9;
        box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);
    }
    
    /* Responsive adjustments */
    @media (max-width: 640px) {
        .glass-footer p {
            font-size: 0.8rem;
        }
        
        .glass-footer a:not(.support-img-btn) {
            margin: 0 6px;
        }

        .support-img-btn img {
            height: 2.25rem; /* 36px on mobile */
        }
    }
    
    @media (max-width: 480px) {
        .glass-footer p {
            font-size: 0.7rem;
        }
        
        .glass-footer a:not(.support-img-btn) {
            margin: 0 4px;
            font-size: 0.7rem;
        }

        .support-img-btn img {
            height: 2rem; /* 32px on smaller mobile screens */
        }
    }
</style>