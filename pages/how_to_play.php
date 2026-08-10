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
    /* Background setup */
    body {
        background: url('<?php echo $base_path; ?>img/backgrounds/bg-howto.jpg') no-repeat center center fixed;
        background-size: cover;
        position: relative;
        min-height: 100vh;
    }

    html {
        scroll-behavior: smooth;
    }

    /* Glassmorphic Cards - No Blur */
    .step-card {
        background: rgba(15, 23, 42, 0.75);
        border: 1px solid rgba(255, 255, 255, 0.12);
        transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
        position: relative;
    }
    
    .step-card:hover {
        transform: translateY(-6px);
        border-color: rgba(99, 102, 241, 0.4);
        background: rgba(15, 23, 42, 0.85);
        box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.7),
                    0 0 30px 0 rgba(99, 102, 241, 0.15);
    }

    /* Step Badge - Only Number */
    .step-badge {
        position: absolute;
        top: 1rem;
        left: 1rem;
        z-index: 10;
        background: rgba(15, 23, 42, 0.85);
        border: 1px solid rgba(255, 255, 255, 0.2);
        padding: 0.35rem 0.85rem;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
    }

    .step-number {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        box-shadow: 0 0 10px rgba(99, 102, 241, 0.6);
        width: 26px;
        height: 26px;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-weight: 700;
        font-size: 0.8rem;
    }

    /* Image Container - Fixed height for consistent sizing */
    .step-image-container {
        padding: 1rem 1rem 0.5rem 1rem;
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        position: relative;
        flex-shrink: 0;
        height: 220px;
    }
    
    .step-image {
        max-width: 240px;
        width: 100%;
        height: auto;
        max-height: 190px;
        object-fit: contain;
        border-radius: 0.75rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        margin: 0 auto;
    }
    
    .step-card:hover .step-image {
        transform: scale(1.02);
        border-color: rgba(129, 140, 248, 0.4);
    }

    /* Text Content Container - Fixed height and consistent spacing */
    .step-content {
        padding: 0.5rem 1.5rem 1.5rem 1.5rem;
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 200px;
    }

    .step-text-area {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .step-actions {
        margin-top: auto;
        padding-top: 0.75rem;
        border-top: 1px solid rgba(255, 255, 255, 0.05);
    }

    .step-content p {
        color: #cbd5e1;
        font-size: 0.9rem;
        line-height: 1.6;
        margin-bottom: 0.5rem;
    }

    .step-content p:last-child {
        margin-bottom: 0;
    }

    .step-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: #ffffff;
        margin-bottom: 0.5rem;
        letter-spacing: 0.025em;
    }

    /* Interactive Code Box */
    .code-box {
        background: rgba(3, 7, 18, 0.75);
        border: 1px solid rgba(99, 102, 241, 0.25);
        font-family: 'Fira Code', 'Courier New', monospace;
        transition: all 0.2s ease;
        padding: 0.6rem 0.8rem;
        border-radius: 0.75rem;
        margin-top: 0.5rem;
    }

    .code-box:hover {
        border-color: rgba(129, 140, 248, 0.5);
        box-shadow: inset 0 0 10px rgba(99, 102, 241, 0.1);
    }

    /* Text Glow & Gradient Animations */
    .glow-text {
        background: linear-gradient(135deg, #ffffff, #c084fc, #818cf8);
        background-size: 200% 200%;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        animation: shimmer 4s ease infinite;
    }
    
    @keyframes shimmer {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }

    /* CSS Animations */
    @keyframes fadeUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .fade-up {
        animation: fadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        opacity: 0;
    }
    
    .fade-up-delay-1 { animation-delay: 0.1s; }
    .fade-up-delay-2 { animation-delay: 0.2s; }
    .fade-up-delay-3 { animation-delay: 0.3s; }
    .fade-up-delay-4 { animation-delay: 0.4s; }

    /* Custom Scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
    }
    ::-webkit-scrollbar-track {
        background: rgba(3, 7, 18, 0.8);
    }
    ::-webkit-scrollbar-thumb {
        background: #4f46e5;
        border-radius: 4px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: #6366f1;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .step-image-container {
            height: 170px;
            padding: 0.75rem 0.75rem 0.25rem 0.75rem;
        }
        
        .step-image {
            max-width: 180px;
            max-height: 140px;
        }
        
        .step-content {
            padding: 0.25rem 1rem 1rem 1rem;
            min-height: 170px;
        }
        
        .step-content p {
            font-size: 0.8rem;
        }
        
        .step-title {
            font-size: 1rem;
        }
        
        .step-badge {
            top: 0.75rem;
            left: 0.75rem;
            padding: 0.25rem 0.6rem;
        }
        
        .step-number {
            width: 22px;
            height: 22px;
            font-size: 0.7rem;
        }
    }
</style>

<!-- Main Page Wrapper -->
<div class="relative z-10 min-h-screen flex flex-col">
    <main class="flex-1 flex flex-col items-center w-full px-4 py-12 md:py-20">
        <div class="container mx-auto max-w-6xl">
            
            <!-- Hero Header -->
            <div class="text-center mb-14 fade-up">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-slate-900/80 border border-indigo-500/30 mb-4 backdrop-blur-md">
                    <span class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span>
                    <span class="text-xs font-semibold uppercase tracking-wider text-indigo-300">
                        <?php echo translate('quickstart_guide', 'Quickstart Guide'); ?>
                    </span>
                </div>
                
                <h1 class="text-4xl md:text-6xl font-extrabold glow-text mb-4 tracking-tight drop-shadow-lg">
                    <?php echo translate('how_to_play_title', 'How to Play'); ?>
                </h1>
                
                <p class="text-base md:text-lg text-white max-w-2xl mx-auto leading-relaxed drop-shadow">
                    <?php echo translate('how_to_play_subtitle', 'Join our server in 4 simple steps and start your Wrath of the Lich King adventure today!'); ?>
                </p>
            </div>
            
            <!-- 2x2 Steps Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8">
                
                <!-- STEP 1 -->
                <div class="step-card fade-up fade-up-delay-1 rounded-2xl">
                    <!-- Image Container -->
                    <div class="step-image-container">
                        <div class="step-badge">
                            <span class="step-number">1</span>
                        </div>
                        <img class="step-image" 
                             src="<?php echo $base_path; ?>img/howtoplay/down_register.jpg" 
                             alt="<?php echo translate('create_account_alt', 'Create Account'); ?>"
                             loading="lazy">
                    </div>
                    
                    <!-- Text Content Container -->
                    <div class="step-content">
                        <div class="step-text-area">
                            <h2 class="step-title">
                                <?php echo translate('step_1_title', 'Create an Account'); ?>
                            </h2>
                            <p>
                                <?php echo translate('step_1_desc', 'Register a free account using our website:'); ?>
                            </p>
                        </div>
                        <div class="step-actions">
                            <a class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl transition-all duration-200 text-sm shadow-lg shadow-indigo-600/25" 
                               href="<?php echo $base_path; ?>register">
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
                <div class="step-card fade-up fade-up-delay-2 rounded-2xl">
                    <!-- Image Container -->
                    <div class="step-image-container">
                        <div class="step-badge">
                            <span class="step-number">2</span>
                        </div>
                        <img class="step-image" 
                             src="<?php echo $base_path; ?>img/howtoplay/down_download.png" 
                             alt="<?php echo translate('download_game_alt', 'Download Game'); ?>"
                             loading="lazy">
                    </div>
                    
                    <!-- Text Content Container -->
                    <div class="step-content">
                        <div class="step-text-area">
                            <h2 class="step-title">
                                <?php echo translate('step_2_title', 'Download the Game'); ?>
                            </h2>
                            <p>
                                <?php echo translate('step_2_desc', 'You need World of Warcraft: Wrath of the Lich King (3.3.5a). Choose your preferred download method:'); ?>
                            </p>
                        </div>
                        <div class="step-actions">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <a class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-white font-medium rounded-xl transition-all text-xs" 
                                   href="<?php echo $base_path; ?>download">
                                    <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                    <?php echo translate('direct_download', 'Direct Download'); ?>
                                </a>
                                <a class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-white font-medium rounded-xl transition-all text-xs" 
                                   href="<?php echo $base_path; ?>download">
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
                <div class="step-card fade-up fade-up-delay-3 rounded-2xl">
                    <!-- Image Container -->
                    <div class="step-image-container">
                        <div class="step-badge">
                            <span class="step-number">3</span>
                        </div>
                        <img id="down_img_realm" class="step-image" 
                             src="<?php echo $base_path; ?>img/howtoplay/down_realmlist.png" 
                             alt="<?php echo translate('edit_realmlist_alt', 'Edit Realmlist'); ?>"
                             loading="lazy">
                    </div>
                    
                    <!-- Text Content Container -->
                    <div class="step-content">
                        <div class="step-text-area">
                            <h2 class="step-title">
                                <?php echo translate('step_3_title', 'Set the Realmlist'); ?>
                            </h2>
                            <p>
                                <?php echo translate('step_3_desc_1', 'Open your World of Warcraft folder, go to Data/enUS or Data/enGB, and find realmlist.wtf.'); ?>
                            </p>
                            <p>
                                <?php echo translate('step_3_desc_2', 'Open it with Notepad and replace everything inside with:'); ?>
                            </p>
                        </div>
                        <div class="step-actions">
                            <div class="code-box flex items-center justify-between gap-2">
                                <code class="text-xs md:text-sm text-emerald-400 font-mono overflow-x-auto select-all">
                                    set realmlist <span id="realmlist-text" class="text-indigo-300 font-semibold"><?php echo htmlspecialchars($realmlistIP); ?></span>
                                </code>
                                <button onclick="copyRealmlist()" 
                                        id="copy-btn"
                                        class="px-2.5 py-1.5 bg-indigo-600/30 hover:bg-indigo-600/50 border border-indigo-500/30 text-indigo-200 text-xs rounded-lg transition-all flex items-center gap-1 flex-shrink-0">
                                    <svg id="copy-icon" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                    <span id="copy-status"><?php echo translate('copy', 'Copy'); ?></span>
                                </button>
                            </div>
                            <p class="text-xs text-slate-300 mt-2">
                                <?php echo translate('step_3_desc_3', 'Save the file and close it.'); ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- STEP 4 -->
                <div class="step-card fade-up fade-up-delay-4 rounded-2xl">
                    <!-- Image Container -->
                    <div class="step-image-container">
                        <div class="step-badge">
                            <span class="step-number">4</span>
                        </div>
                        <img class="step-image" 
                             src="<?php echo $base_path; ?>img/howtoplay/down_wow.png" 
                             alt="<?php echo translate('launch_wow_alt', 'Launch WoW'); ?>"
                             loading="lazy">
                    </div>
                    
                    <!-- Text Content Container -->
                    <div class="step-content">
                        <div class="step-text-area">
                            <h2 class="step-title">
                                <?php echo translate('step_4_title', 'Start Playing!'); ?>
                            </h2>
                            <p>
                                <?php echo translate('step_4_desc_1', 'Open Wow.exe (not Launcher.exe) and log in using your account credentials.'); ?>
                            </p>
                            <p>
                                <?php echo translate('step_4_desc_2', 'Enjoy your adventure on our server!'); ?>
                            </p>
                            <p class="mt-2 text-emerald-400 text-sm font-medium flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                <?php echo translate('ready_to_play', 'Ready to play! Enjoy your adventure.'); ?>
                            </p>
                        </div>
                    </div>
                </div>
                
            </div>
            
            <!-- Bottom CTA Section -->
            <div class="mt-14 text-center fade-up">
                <div class="bg-slate-900/60 backdrop-blur-md rounded-2xl border border-white/10 p-8 md:p-10 shadow-2xl relative overflow-hidden">
                    <h3 class="text-2xl md:text-3xl font-extrabold text-white mb-2">
                        <?php echo translate('cta_title', 'Ready to Begin Your Journey?'); ?>
                    </h3>
                    <p class="text-slate-200 text-sm md:text-base mb-6 max-w-xl mx-auto">
                        <?php echo translate('cta_desc', 'Create an account now and start your adventure in the world of Azeroth!'); ?>
                    </p>
                    <a class="inline-flex items-center gap-2 px-8 py-3.5 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-semibold rounded-xl shadow-lg shadow-indigo-500/25 transition-all duration-300 transform hover:-translate-y-0.5" 
                       href="<?php echo $base_path; ?>register">
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
        btn.classList.add('bg-emerald-600/40', 'border-emerald-500/50', 'text-emerald-200');
        
        setTimeout(() => {
            statusSpan.innerText = '<?php echo translate('copy', 'Copy'); ?>';
            btn.classList.remove('bg-emerald-600/40', 'border-emerald-500/50', 'text-emerald-200');
        }, 2000);
    });
}
</script>

<?php include($project_root . 'includes/footer.php'); ?>