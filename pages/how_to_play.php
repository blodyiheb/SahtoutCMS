<?php 
define('ALLOWED_ACCESS', true);

// Include paths.php to access $project_root and $base_path
require_once __DIR__ . '/../includes/paths.php';

// Use $project_root for filesystem includes
require_once $project_root . 'includes/session.php';
$page_class = "how_to_play";
require_once $project_root . 'includes/header.php'; 

$realmsFile = $project_root . 'includes/realm_config.php';
$realmlistIP = '127.0.0.1'; // fallback if file missing

if (file_exists($realmsFile)) {
    include $realmsFile; // defines $realmlist
    if (!empty($realmlist[0]['address'])) {
        $realmlistIP = $realmlist[0]['address'];
    }
}
?>

<style>
    /* ============ CORE THEME VARIABLES ============ */
    :root {
        --gold-primary: #f2cf5b;
        --gold-dark: #c9a227;
        --gold-deep: #8a6a14;
        --iron-bg: rgba(10,14,22,.85);
    }

    html { scroll-behavior: smooth; }

    /* ============ BACKGROUND IMAGE ONLY ============ */
    body.how_to_play {
        background: url('<?php echo $base_path; ?>img/backgrounds/bg-howto.jpg') no-repeat center center fixed !important;
        background-size: cover !important;
        position: relative;
        min-height: 100vh;
    }
    
    /* Remove the dark overlay completely */
    body.how_to_play::before {
        display: none !important;
        content: none !important;
        background: none !important;
    }

    /* Remove any other overlays */
    body.how_to_play::after {
        display: none !important;
        content: none !important;
        background: none !important;
    }

    /* Make sure all content sits above the background */
    body.how_to_play .relative.z-10 {
        position: relative;
        z-index: 10;
    }

    /* ============ TYPOGRAPHY & HERO ============ */
    .wow-title {
        font-family: 'Cinzel', serif;
        font-weight: 900;
        background: linear-gradient(180deg, #fff7d6 0%, #f2cf5b 35%, #c9a227 62%, #8a6a14 100%);
        -webkit-background-clip: text; background-clip: text;
        color: transparent;
        filter: drop-shadow(0 4px 12px rgba(0,0,0,.9));
        letter-spacing: .02em;
    }

    .tag-pill {
        display: inline-flex; align-items: center; gap: .5rem;
        padding: .4rem 1.2rem;
        background: rgba(0,0,0,.6);
        border: 1px solid rgba(201,162,39,.4);
        clip-path: polygon(10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%, 0 10px);
        color: var(--gold-primary);
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.15em;
        text-transform: uppercase;
        text-shadow: 0 0 8px rgba(242,207,82,.3);
    }

    /* ============ STEP CARDS (PANELS) ============ */
    .wow-step-card {
        position: relative;
        background: linear-gradient(180deg, rgba(22,25,32,.92), rgba(8,10,14,.9));
        border: 1px solid rgba(201,162,39,.22);
        box-shadow: 0 12px 32px rgba(0,0,0,.55), inset 0 0 60px rgba(0,0,0,.45);
        clip-path: polygon(16px 0, 100% 0, 100% calc(100% - 16px), calc(100% - 16px) 100%, 0 100%, 0 16px);
        transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    .wow-step-card::before {
        content: ''; position: absolute; inset: 6px;
        border: 1px solid rgba(201,162,39,.15);
        pointer-events: none;
        clip-path: polygon(12px 0, 100% 0, 100% calc(100% - 12px), calc(100% - 12px) 100%, 0 100%, 0 12px);
    }

    .wow-step-card:hover {
        transform: translateY(-8px);
        border-color: rgba(242,207,82,.6);
        box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.7), 0 0 30px 0 rgba(242,207,82,.15);
    }

    /* Step Badge */
    .step-badge {
        position: absolute;
        top: 1.25rem; left: 1.25rem;
        z-index: 10;
        width: 38px; height: 38px;
        display: flex; align-items: center; justify-content: center;
        background: linear-gradient(180deg, #f6d478 0%, var(--gold-dark) 48%, var(--gold-deep) 100%);
        color: #1a1200;
        font-weight: 900; font-size: 1.1rem; font-family: 'Cinzel', serif;
        clip-path: polygon(6px 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%, 0 6px);
        box-shadow: 0 4px 12px rgba(0,0,0,.6);
        text-shadow: 0 1px 0 rgba(255,255,255,.35);
    }

    /* Image Container */
    .step-image-container {
        padding: 2rem 1.5rem 1rem 1.5rem;
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        position: relative;
        flex-shrink: 0;
        height: 240px;
    }
    
    .step-image {
        max-width: 240px;
        width: 100%;
        height: auto;
        max-height: 200px;
        object-fit: contain;
        border: 2px solid rgba(201,162,39,.3);
        padding: 4px;
        background: rgba(0,0,0,.5);
        transition: all 0.5s ease;
    }
    
    .wow-step-card:hover .step-image {
        transform: scale(1.03);
        border-color: rgba(242,207,82,.6);
        box-shadow: 0 8px 20px rgba(0,0,0,.5);
    }

    /* Text Content */
    .step-content {
        padding: 0.5rem 1.75rem 1.75rem 1.75rem;
        flex: 1;
        display: flex;
        flex-direction: column;
        position: relative;
        z-index: 1;
    }

    .step-title {
        font-family: 'Cinzel', serif;
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--gold-primary);
        margin-bottom: 0.75rem;
        letter-spacing: 0.02em;
        text-shadow: 0 2px 6px rgba(0,0,0,.9);
    }

    .step-content p {
        color: #d1d5db;
        font-size: 0.9rem;
        line-height: 1.6;
        margin-bottom: 0.5rem;
    }

    /* Code Box */
    .code-box {
        background: rgba(0,0,0,.7);
        border: 1px solid rgba(201,162,39,.35);
        font-family: 'Fira Code', 'Courier New', monospace;
        padding: 0.85rem 1.1rem;
        clip-path: polygon(8px 0, 100% 0, 100% calc(100% - 8px), calc(100% - 8px) 100%, 0 100%, 0 8px);
        margin-top: 0.75rem;
        position: relative;
        z-index: 1;
    }

    .code-box code {
        color: #4ade80;
        font-size: 0.875rem;
    }

    .code-box code span {
        color: var(--gold-primary);
        font-weight: 700;
    }

    .copy-btn {
        padding: 0.4rem 0.85rem;
        background: linear-gradient(180deg, #3a404d 0%, #232833 55%, #141821 100%);
        color: #cfe1ff;
        border: 1px solid rgba(120,160,255,.25);
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        clip-path: polygon(4px 0, 100% 0, 100% calc(100% - 4px), calc(100% - 4px) 100%, 0 100%, 0 4px);
        transition: all 0.2s ease;
    }
    .copy-btn:hover { filter: drop-shadow(0 0 8px rgba(59,130,246,.4)); }

    /* ============ BUTTONS ============ */
    .btn-game {
        display: inline-flex; align-items: center; justify-content: center; gap: .55rem;
        padding: .85rem 1.5rem;
        font-weight: 800; font-size: .85rem; letter-spacing: .04em; text-transform: uppercase;
        clip-path: polygon(10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%, 0 10px);
        transition: transform .2s ease, filter .2s ease;
        text-decoration: none;
        width: 100%;
    }
    .btn-game:hover { transform: translateY(-2px) scale(1.02); }

    .btn-gold {
        background: linear-gradient(180deg, #f6d478 0%, #c9a227 48%, #8a6a14 100%);
        color: #1a1200;
        text-shadow: 0 1px 0 rgba(255,255,255,.35);
        box-shadow: inset 0 0 0 1px rgba(255,255,255,.28), inset 0 -8px 14px rgba(0,0,0,.25);
    }
    .btn-gold:hover { filter: drop-shadow(0 0 12px rgba(242,207,82,.5)); }

    .btn-iron {
        background: linear-gradient(180deg, #3a404d 0%, #232833 55%, #141821 100%);
        color: #cfe1ff;
        box-shadow: inset 0 0 0 1px rgba(120,160,255,.25), inset 0 -8px 14px rgba(0,0,0,.4);
    }
    .btn-iron:hover { filter: drop-shadow(0 0 12px rgba(59,130,246,.4)); }

    /* ============ CTA PANEL ============ */
    .cta-panel {
        position: relative;
        background: linear-gradient(180deg, rgba(22,25,32,.92), rgba(8,10,14,.9));
        border: 1px solid rgba(201,162,39,.3);
        box-shadow: 0 12px 32px rgba(0,0,0,.55), inset 0 0 60px rgba(0,0,0,.45);
        clip-path: polygon(24px 0, 100% 0, 100% calc(100% - 24px), calc(100% - 24px) 100%, 0 100%, 0 24px);
        padding: 3.5rem 2rem;
    }
    .cta-panel::before {
        content: ''; position: absolute; inset: 6px;
        border: 1px solid rgba(201,162,39,.15);
        pointer-events: none;
        clip-path: polygon(20px 0, 100% 0, 100% calc(100% - 20px), calc(100% - 20px) 100%, 0 100%, 0 20px);
    }

    /* ============ ANIMATIONS ============ */
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .fade-up { animation: fadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
    .fade-up-delay-1 { animation-delay: 0.1s; }
    .fade-up-delay-2 { animation-delay: 0.2s; }
    .fade-up-delay-3 { animation-delay: 0.3s; }
    .fade-up-delay-4 { animation-delay: 0.4s; }

    /* ============ RESPONSIVE ============ */
    @media (max-width: 768px) {
        .step-image-container { height: 200px; padding: 1.5rem 1rem 0.5rem 1rem; }
        .step-image { max-height: 160px; }
        .step-content { padding: 0.5rem 1.25rem 1.25rem 1.25rem; }
        .step-title { font-size: 1.15rem; }
        .step-badge { width: 32px; height: 32px; font-size: 0.95rem; top: 1rem; left: 1rem; }
    }
</style>

<!-- Main Page Wrapper -->
<div class="relative z-10 min-h-screen flex flex-col">
    <main class="flex-1 flex flex-col items-center w-full px-4 py-12 md:py-20">
        <div class="container mx-auto max-w-6xl">
            
            <!-- Hero Header -->
            <div class="text-center mb-16 fade-up relative z-10">
                <div class="tag-pill mb-6">
                    <span class="w-2 h-2 bg-[#f2cf5b] animate-pulse" style="clip-path: polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%);"></span>
                    <span><?php echo translate('quickstart_guide', 'Quickstart Guide'); ?></span>
                </div>
                
                <h1 class="text-4xl md:text-6xl lg:text-7xl mb-6 wow-title">
                    <?php echo translate('how_to_play_title', 'How to Play'); ?>
                </h1>
                
                <p class="text-base md:text-lg text-gray-300 max-w-2xl mx-auto leading-relaxed drop-shadow-lg">
                    <?php echo translate('how_to_play_subtitle', 'Join our server in 4 simple steps and start your Wrath of the Lich King adventure today!'); ?>
                </p>
            </div>
            
            <!-- 2x2 Steps Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-10 relative z-10">
                
                <!-- STEP 1 -->
                <div class="wow-step-card fade-up fade-up-delay-1">
                    <div class="step-badge">1</div>
                    <div class="step-image-container">
                        <img class="step-image" 
                             src="<?php echo $base_path; ?>img/howtoplay/down_register.jpg" 
                             alt="<?php echo translate('create_account_alt', 'Create Account'); ?>"
                             loading="lazy"
                             onerror="this.style.display='none'">
                    </div>
                    
                    <div class="step-content">
                        <div class="flex-1">
                            <h2 class="step-title"><?php echo translate('step_1_title', 'Create an Account'); ?></h2>
                            <p><?php echo translate('step_1_desc', 'Register a free account using our website:'); ?></p>
                        </div>
                        <div class="mt-4 pt-4 border-t border-white/5">
                            <a class="btn-game btn-gold" href="<?php echo $base_path; ?>register">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                </svg>
                                <?php echo translate('create_account', 'Create Account'); ?>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- STEP 2 -->
                <div class="wow-step-card fade-up fade-up-delay-2">
                    <div class="step-badge">2</div>
                    <div class="step-image-container">
                        <img class="step-image" 
                             src="<?php echo $base_path; ?>img/howtoplay/down_download.png" 
                             alt="<?php echo translate('download_game_alt', 'Download Game'); ?>"
                             loading="lazy"
                             onerror="this.style.display='none'">
                    </div>
                    
                    <div class="step-content">
                        <div class="flex-1">
                            <h2 class="step-title"><?php echo translate('step_2_title', 'Download the Game'); ?></h2>
                            <p><?php echo translate('step_2_desc', 'You need World of Warcraft: Wrath of the Lich King (3.3.5a). Choose your preferred download method:'); ?></p>
                        </div>
                        <div class="mt-4 pt-4 border-t border-white/5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <a class="btn-game btn-iron !text-xs !py-2.5" href="<?php echo $base_path; ?>download">
                                    <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                    <?php echo translate('direct_download', 'Direct Download'); ?>
                                </a>
                                <a class="btn-game btn-iron !text-xs !py-2.5" href="<?php echo $base_path; ?>download">
                                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                    <?php echo translate('torrent_download', 'Torrent Download'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- STEP 3 -->
                <div class="wow-step-card fade-up fade-up-delay-3">
                    <div class="step-badge">3</div>
                    <div class="step-image-container">
                        <img id="down_img_realm" class="step-image" 
                             src="<?php echo $base_path; ?>img/howtoplay/down_realmlist.png" 
                             alt="<?php echo translate('edit_realmlist_alt', 'Edit Realmlist'); ?>"
                             loading="lazy"
                             onerror="this.style.display='none'">
                    </div>
                    
                    <div class="step-content">
                        <div class="flex-1">
                            <h2 class="step-title"><?php echo translate('step_3_title', 'Set the Realmlist'); ?></h2>
                            <p><?php echo translate('step_3_desc_1', 'Open your World of Warcraft folder, go to Data/enUS or Data/enGB, and find realmlist.wtf.'); ?></p>
                            <p><?php echo translate('step_3_desc_2', 'Open it with Notepad and replace everything inside with:'); ?></p>
                        </div>
                        <div class="mt-4 pt-4 border-t border-white/5">
                            <div class="code-box flex items-center justify-between gap-3">
                                <code class="overflow-x-auto select-all whitespace-nowrap">
                                    set realmlist <span id="realmlist-text"><?php echo htmlspecialchars($realmlistIP); ?></span>
                                </code>
                                <button onclick="copyRealmlist()" 
                                        id="copy-btn"
                                        class="copy-btn flex items-center gap-1.5 flex-shrink-0">
                                    <svg id="copy-icon" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                    <span id="copy-status"><?php echo translate('copy', 'Copy'); ?></span>
                                </button>
                            </div>
                            <p class="text-xs text-gray-400 mt-3 italic">
                                <?php echo translate('step_3_desc_3', 'Save the file and close it.'); ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- STEP 4 -->
                <div class="wow-step-card fade-up fade-up-delay-4">
                    <div class="step-badge">4</div>
                    <div class="step-image-container">
                        <img class="step-image" 
                             src="<?php echo $base_path; ?>img/howtoplay/down_wow.png" 
                             alt="<?php echo translate('launch_wow_alt', 'Launch WoW'); ?>"
                             loading="lazy"
                             onerror="this.style.display='none'">
                    </div>
                    
                    <div class="step-content">
                        <div class="flex-1">
                            <h2 class="step-title"><?php echo translate('step_4_title', 'Start Playing!'); ?></h2>
                            <p><?php echo translate('step_4_desc_1', 'Open Wow.exe (not Launcher.exe) and log in using your account credentials.'); ?></p>
                            <p><?php echo translate('step_4_desc_2', 'Enjoy your adventure on our server!'); ?></p>
                            <p class="mt-4 text-[#4ade80] text-sm font-semibold flex items-center gap-2" style="text-shadow: 0 0 8px rgba(74,222,128,.4);">
                                <span class="w-2 h-2 bg-[#4ade80] animate-pulse" style="clip-path: polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%);"></span>
                                <?php echo translate('ready_to_play', 'Ready to play! Enjoy your adventure.'); ?>
                            </p>
                        </div>
                    </div>
                </div>
                
            </div>
            
            <!-- Bottom CTA Section -->
            <div class="mt-20 text-center fade-up relative z-10">
                <div class="cta-panel max-w-3xl mx-auto">
                    <h3 class="text-2xl md:text-3xl font-extrabold text-white mb-3" style="font-family:'Cinzel',serif; text-shadow: 0 2px 8px rgba(0,0,0,.8);">
                        <?php echo translate('cta_title', 'Ready to Begin Your Journey?'); ?>
                    </h3>
                    <p class="text-gray-300 text-sm md:text-base mb-8 max-w-xl mx-auto">
                        <?php echo translate('cta_desc', 'Create an account now and start your adventure in the world of Azeroth!'); ?>
                    </p>
                    <a class="btn-game btn-gold !w-auto !px-10 !py-4 text-base mx-auto" href="<?php echo $base_path; ?>register">
                        <span><?php echo translate('cta_button', 'Get Started Now'); ?></span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </a>
                </div>
            </div>
            
        </div>
    </main>
</div>

<script>
function copyRealmlist() {
    const ip = document.getElementById('realmlist-text').innerText;
    const textToCopy = 'set realmlist ' + ip;
    
    navigator.clipboard.writeText(textToCopy).then(() => {
        const statusSpan = document.getElementById('copy-status');
        const btn = document.getElementById('copy-btn');
        
        statusSpan.innerText = '<?php echo translate('copied', 'Copied!'); ?>';
        
        // Success state styling
        btn.style.background = 'linear-gradient(180deg, #4ade80 0%, #16a34a 100%)';
        btn.style.color = '#052e16';
        btn.style.borderColor = 'rgba(74,222,128,.5)';
        
        setTimeout(() => {
            statusSpan.innerText = '<?php echo translate('copy', 'Copy'); ?>';
            btn.style.background = '';
            btn.style.color = '';
            btn.style.borderColor = '';
        }, 2000);
    });
}
</script>

<?php include($project_root . 'includes/footer.php'); ?>