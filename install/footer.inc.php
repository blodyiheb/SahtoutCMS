<?php
// Ensure this file is not accessed directly
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access not allowed.');
}
?>

<!-- Installer Footer -->
<footer class="glass-footer w-full border-t border-[rgba(160,130,60,0.3)] py-4 px-4 text-center shadow-[0_-2px_20px_rgba(139,115,60,0.3)] font-['Cinzel'] mt-auto">
    
    <!-- Support Button -->
    <div class="mb-3">
        <a href="https://blodyiheb.vercel.app/#payment-methods" 
           target="_blank" 
           rel="noopener noreferrer" 
           class="inline-block hover:opacity-80 transition-opacity duration-300">
            <img 
                src="support-button.png"
                alt="<?= translate('support_alt', 'Support SahtoutCMS') ?>"
                class="h-16 md:h-18 w-auto object-contain"
            >
        </a>
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
    <p class="text-[#8fbc8f] text-xs md:text-sm mt-2 m-0">
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
    
    /* Responsive adjustments */
    @media (max-width: 640px) {
        .glass-footer p {
            font-size: 0.8rem;
        }
        
        .glass-footer a {
            margin: 0 6px;
        }
        
        .glass-footer img {
            height: 2.25rem; /* 36px */
        }
    }
    
    @media (max-width: 480px) {
        .glass-footer p {
            font-size: 0.7rem;
        }
        
        .glass-footer a {
            margin: 0 4px;
            font-size: 0.7rem;
        }
        
        .glass-footer img {
            height: 2rem; /* 32px */
        }
    }
</style>