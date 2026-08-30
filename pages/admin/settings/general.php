<?php
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../../../includes/paths.php';
require_once $project_root . 'includes/session.php';
require_once $project_root . 'languages/language.php';
require_once $project_root . 'includes/config.settings.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'moderator'])) {
    header("Location: {$base_path}login");
    exit;
}

$page_class = 'general';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo translate('page_description_general', 'General Settings for Sahtout WoW Server'); ?>">
    <meta name="robots" content="noindex">
    <title><?php echo translate('page_title_general', 'General Settings'); ?></title>
    <link rel="icon" href="<?php echo $base_path . $site_logo; ?>" type="image/x-icon">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/tailwind.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&family=Cinzel:wght@600;700;900&display=swap" rel="stylesheet">
    
    <style>
        /* Only keep what Tailwind CANNOT do */
        
        /* Font families - Tailwind can't handle font-family well inline */
        * { font-family: 'Inter', sans-serif; }
        .wow-title, .section-title, .form-label { font-family: 'Cinzel', serif; }
        
        /* Panel with gold corners AND inner border */
        .panel-gold-corners {
            position: relative;
        }
        
        /* Outer gold corner decorations */
        .panel-gold-corners::after {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                linear-gradient(#e8c552,#e8c552) left top / 18px 2px,
                linear-gradient(#e8c552,#e8c552) left top / 2px 18px,
                linear-gradient(#e8c552,#e8c552) right top / 18px 2px,
                linear-gradient(#e8c552,#e8c552) right top / 2px 18px,
                linear-gradient(#e8c552,#e8c552) left bottom / 18px 2px,
                linear-gradient(#e8c552,#e8c552) left bottom / 2px 18px,
                linear-gradient(#e8c552,#e8c552) right bottom / 18px 2px,
                linear-gradient(#e8c552,#e8c552) right bottom / 2px 18px;
            background-repeat: no-repeat;
        }
        
        /* Inner border inset */
        .panel-gold-corners::before {
            content: '';
            position: absolute;
            inset: 5px;
            border: 1px solid rgba(201,162,39,.14);
            pointer-events: none;
        }
        
        /* Custom clip-path for buttons - Tailwind doesn't support this */
        .btn-clip {
            clip-path: polygon(10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%, 0 10px);
        }
    </style>
</head>
<body class="min-h-screen text-[#d8d8d8] bg-[#05070b] bg-fixed"
      style="background-image: 
        radial-gradient(1000px 700px at -10% 35%, rgba(59,130,246,.14), transparent 65%),
        radial-gradient(800px 600px at -5% 85%, rgba(124,58,237,.10), transparent 70%),
        linear-gradient(180deg, #0a0e16 0%, #060810 45%, #03040a 100%);">
    
    <?php include $project_root . 'includes/header.php'; ?>

    <!-- Main Content Area with Sidebar -->
    <div class="flex relative min-h-screen">
        
        <!-- Sidebar -->
        <?php include $project_root . 'includes/admin_sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content-area flex-1 p-3 sm:p-4 md:p-6 lg:p-8 transition-all duration-300 lg:ml-[280px]">
            <div class="max-w-[1400px] mx-auto px-1 sm:px-4 md:px-6 lg:px-8 xl:px-10">
                <div class="space-y-4 md:space-y-6 lg:space-y-8">
                    
                    <h1 class="wow-title text-2xl md:text-3xl lg:text-4xl font-black 
                               bg-gradient-to-b from-[#fff7d6] via-[#f2cf5b] via-[#c9a227] to-[#8a6a14] 
                               bg-clip-text text-transparent drop-shadow-[0_3px_6px_rgba(0,0,0,.85)]">
                        <?php echo translate('page_title_general', 'General Settings'); ?>
                    </h1>

                    <!-- Settings Navbar -->
                    <?php include $project_root . 'pages/admin/settings/settings_navbar.php'; ?>

                    <!-- Success / Error Messages -->
                    <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
                        <div class="bg-[#2ecc71]/15 border border-[#2ecc71]/40 text-[#2ecc71] 
                                    p-4 rounded-sm flex items-center gap-3">
                            <i class="fas fa-check-circle text-xl"></i>
                            <span><?php echo translate('msg_settings_saved', 'Settings updated successfully!'); ?></span>
                        </div>
                    <?php elseif (isset($_GET['status']) && $_GET['status'] === 'error'): ?>
                        <div class="bg-[#e74c3c]/15 border border-[#e74c3c]/40 text-[#e74c3c] 
                                    p-4 rounded-sm flex items-center gap-3">
                            <i class="fas fa-exclamation-circle text-xl"></i>
                            <div>
                                <strong><?php echo translate('err_fix_errors', 'Error:'); ?></strong>
                                <span><?php echo htmlspecialchars(urldecode($_GET['message'])); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- General Settings Form -->
                    <div class="relative bg-gradient-to-b from-[#161920]/92 to-[#080a0e]/90 
                                border border-[#c9a227]/[0.22] 
                                shadow-[0_12px_32px_rgba(0,0,0,.55),inset_0_0_60px_rgba(0,0,0,.45)]
                                p-4 md:p-6 lg:p-8 panel-gold-corners">
                        
                        <h2 class="section-title text-lg md:text-xl mb-4 md:mb-6 flex items-center gap-3 
                                   text-[#f2cf5b] font-bold drop-shadow-[0_0_12px_rgba(201,162,39,.35),0_2px_4px_rgba(0,0,0,.8)]">
                            <i class="fas fa-cogs text-[#f2cf5b]"></i>
                            <?php echo translate('settings_general', 'General Settings'); ?>
                        </h2>

                        <form action="<?php echo $base_path; ?>pages/admin/settings/save_general.php" 
                              method="POST" 
                              enctype="multipart/form-data" 
                              class="space-y-4 md:space-y-6 max-w-3xl mx-auto">
                            
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="MAX_FILE_SIZE" value="3145728">

                            <!-- Website Title -->
                            <div>
                                <label for="site_title_name" class="form-label text-[#f2cf5b] font-bold text-sm 
                                                                     tracking-wider block mb-2 
                                                                     drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                    <?php echo translate('label_website_title', 'Website Title'); ?>
                                </label>
                                <input type="text"
                                       id="site_title_name"
                                       name="site_title_name"
                                       class="w-full px-4 py-3 text-[0.95rem] text-[#e5e7eb] 
                                              bg-[#0a0e16]/80 border border-[#c9a227]/30 rounded-sm 
                                              focus:border-[#f2cf5b] focus:shadow-[0_0_10px_rgba(242,207,82,.2)] 
                                              focus:bg-[#0f141e]/90 outline-none transition-all duration-200 
                                              placeholder:text-[#96aac8]/40"
                                       maxlength="40"
                                       value="<?php echo htmlspecialchars($site_title_name); ?>"
                                       placeholder="<?php echo translate('placeholder_site_title', 'e.g. My Awesome Site'); ?>"
                                       required>
                                <div class="text-[#6a7a8a] text-xs mt-1">
                                    <?php echo translate('help_site_title', 'This title appears in the browser tab, site header, and SEO.'); ?>
                                </div>
                            </div>

                            <!-- Featured YouTube Video -->
                            <div>
                                <label for="youtube_embed_url" class="form-label text-[#f2cf5b] font-bold text-sm 
                                                                        tracking-wider block mb-2 
                                                                        drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                    <?php echo translate('label_youtube_embed', 'Featured YouTube Embed Link'); ?>
                                </label>
                                <input type="url"
                                       id="youtube_embed_url"
                                       name="youtube_embed_url"
                                       class="w-full px-4 py-3 text-[0.95rem] text-[#e5e7eb] 
                                              bg-[#0a0e16]/80 border border-[#c9a227]/30 rounded-sm 
                                              focus:border-[#f2cf5b] focus:shadow-[0_0_10px_rgba(242,207,82,.2)] 
                                              focus:bg-[#0f141e]/90 outline-none transition-all duration-200 
                                              placeholder:text-[#96aac8]/40"
                                       value="<?php echo htmlspecialchars($youtube_embed_url ?? ''); ?>"
                                       placeholder="https://www.youtube.com/embed/VIDEO_ID"
                                       required>
                                <div class="text-[#6a7a8a] text-xs mt-1">
                                    <?php echo translate('help_youtube_embed', 'Paste a YouTube watch, short, or embed link. The system will normalize it to an embed URL.'); ?>
                                </div>
                            </div>

                            <div>
                                <label for="youtube_title" class="form-label text-[#f2cf5b] font-bold text-sm 
                                                                     tracking-wider block mb-2 
                                                                     drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                    <?php echo translate('label_youtube_title', 'Video Title'); ?>
                                </label>
                                <input type="text"
                                       id="youtube_title"
                                       name="youtube_title"
                                       class="w-full px-4 py-3 text-[0.95rem] text-[#e5e7eb] 
                                              bg-[#0a0e16]/80 border border-[#c9a227]/30 rounded-sm 
                                              focus:border-[#f2cf5b] focus:shadow-[0_0_10px_rgba(242,207,82,.2)] 
                                              focus:bg-[#0f141e]/90 outline-none transition-all duration-200 
                                              placeholder:text-[#96aac8]/40"
                                       maxlength="64"
                                       value="<?php echo htmlspecialchars($youtube_title ?? 'Featured Video'); ?>"
                                       placeholder="<?php echo translate('placeholder_youtube_title', 'Featured Video'); ?>"
                                       required>
                                <div class="text-[#6a7a8a] text-xs mt-1">
                                    <?php echo translate('help_youtube_title', 'This title appears centered above the video.'); ?>
                                </div>
                            </div>

                            <div>
                                <label for="youtube_description" class="form-label text-[#f2cf5b] font-bold text-sm 
                                                                          tracking-wider block mb-2 
                                                                          drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                    <?php echo translate('label_youtube_description', 'Video Description'); ?>
                                </label>
                                <textarea id="youtube_description"
                                          name="youtube_description"
                                          class="w-full px-4 py-3 text-[0.95rem] text-[#e5e7eb] 
                                                 bg-[#0a0e16]/80 border border-[#c9a227]/30 rounded-sm 
                                                 focus:border-[#f2cf5b] focus:shadow-[0_0_10px_rgba(242,207,82,.2)] 
                                                 focus:bg-[#0f141e]/90 outline-none transition-all duration-200 
                                                 placeholder:text-[#96aac8]/40 resize-y min-h-[80px]"
                                          rows="3"
                                          maxlength="500"
                                          placeholder="<?php echo translate('placeholder_youtube_description', 'Watch a featured video here...'); ?>"><?php echo htmlspecialchars($youtube_description ?? 'Watch a featured video here. Replace it with your own channel or highlight later.'); ?></textarea>
                                <div class="text-[#6a7a8a] text-xs mt-1">
                                    <?php echo translate('help_youtube_description', 'This text appears under the title in the video panel.'); ?>
                                </div>
                            </div>

                            <!-- Logo Upload - Centered -->
                            <div>
                                <label class="form-label text-[#f2cf5b] font-bold text-sm tracking-wider 
                                               block mb-2 text-center drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                    <?php echo translate('label_website_logo', 'Website Logo'); ?>
                                </label>
                                
                                <!-- Current Logo Preview - Centered -->
                                <div class="flex justify-center items-center p-2 min-h-[140px] 
                                            bg-[#0a0e16]/50 border border-[#c9a227]/20 rounded-sm mb-3">
                                    <img src="<?php echo $base_path . htmlspecialchars($site_logo); ?>" 
                                         alt="Current Logo" 
                                         class="max-h-[120px] max-w-full object-contain">
                                </div>
                                
                                <!-- Upload Area - Centered -->
                                <div class="border-2 border-dashed border-[#c9a227]/20 
                                            bg-[#0a0e16]/50 hover:border-[#c9a227]/40 
                                            hover:bg-[#0f141e]/70 cursor-pointer transition-all duration-300 
                                            p-8 text-center rounded-sm max-w-md mx-auto" 
                                     id="uploadArea">
                                    <input type="file" id="logo" name="logo" class="absolute w-px h-px p-0 -m-px overflow-hidden clip-[rect(0,0,0,0)] border-0" accept=".png,.jpg,.jpeg,.svg">
                                    <div id="uploadPlaceholder">
                                        <i class="fas fa-cloud-upload-alt text-4xl text-[#c9a227]/40 block mb-3"></i>
                                        <p class="text-sm text-gray-400"><?php echo translate('label_website_logo', 'Upload Logo'); ?></p>
                                        <p class="text-xs text-gray-500 mt-1">PNG, JPG or SVG (max 3MB)</p>
                                    </div>
                                    <div id="file-name" class="text-sm text-[#f2cf5b] hidden mt-2 font-semibold"></div>
                                </div>
                                <div class="text-[#6a7a8a] text-xs mt-1 text-center">
                                    <?php echo translate('help_logo', 'Upload a new logo image. PNG, JPG or SVG formats supported (max 3MB).'); ?>
                                </div>
                            </div>

                            <!-- Social Links -->
                            <div>
                                <label class="form-label text-[#f2cf5b] font-bold text-sm tracking-wider 
                                               block mb-2 drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                    <?php echo translate('label_social_media', 'Social Media Links'); ?>
                                </label>
                                
                                <?php
                                $icons = [
                                    'facebook'  => 'fab fa-facebook-f',
                                    'twitter'   => 'fab fa-x-twitter',
                                    'tiktok'    => 'fab fa-tiktok',
                                    'youtube'   => 'fab fa-youtube',
                                    'discord'   => 'fab fa-discord',
                                    'twitch'    => 'fab fa-twitch',
                                    'kick'      => 'fab fa-kickstarter',
                                    'instagram' => 'fab fa-instagram',
                                    'github'    => 'fab fa-github',
                                    'linkedin'  => 'fab fa-linkedin-in',
                                ];

                                foreach ($icons as $platform => $icon): ?>
                                    <div class="flex items-stretch mb-2">
                                        <span class="flex items-center justify-center px-4 py-3 min-w-[48px] 
                                                     bg-[#0a0e16]/90 border border-[#c9a227]/30 border-r-0 
                                                     text-[#f2cf5b] rounded-l-sm text-base">
                                          
                                                <i class="<?php echo $icon; ?>"></i>
                                        </span>
                                        <input type="url"
                                               name="<?php echo $platform; ?>"
                                               class="flex-1 px-4 py-3 text-[0.95rem] text-[#e5e7eb] 
                                                      bg-[#0a0e16]/80 border border-[#c9a227]/30 border-l-0 rounded-r-sm 
                                                      focus:border-[#f2cf5b] focus:shadow-[0_0_10px_rgba(242,207,82,.2)] 
                                                      focus:bg-[#0f141e]/90 outline-none transition-all duration-200 
                                                      placeholder:text-[#96aac8]/40"
                                               maxlength="90"
                                               placeholder="<?php echo translate("placeholder_{$platform}", ucfirst($platform) . ' URL'); ?>"
                                               value="<?php echo htmlspecialchars($social_links[$platform] ?? ''); ?>">
                                    </div>
                                <?php endforeach; ?>
                                <div class="text-[#6a7a8a] text-xs mt-1">
                                    <?php echo translate('help_social_links', 'Enter the full URLs for your social media profiles. Leave blank to hide.'); ?>
                                </div>
                            </div>

                            <!-- Save Button -->
                            <div class="pt-4 border-t border-[rgba(201,162,39,.1)] flex justify-end">
                                <button type="submit" class="btn-clip inline-flex items-center gap-2 px-6 py-3 
                                                             font-extrabold text-xs uppercase tracking-wider
                                                             bg-gradient-to-b from-[#f6d478] via-[#c9a227] to-[#8a6a14] 
                                                             text-[#1a1200] shadow-[inset_0_0_0_1px_rgba(255,255,255,.28),inset_0_-8px_14px_rgba(0,0,0,.25)]
                                                             hover:scale-105 transition-transform duration-200">
                                    <i class="fas fa-save"></i>
                                    <?php echo translate('btn_save_settings', 'Save All Settings'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Logo upload area
            const uploadArea = document.getElementById('uploadArea');
            const logoInput = document.getElementById('logo');
            const uploadPlaceholder = document.getElementById('uploadPlaceholder');
            const fileName = document.getElementById('file-name');

            if (uploadArea && logoInput) {
                uploadArea.addEventListener('click', () => logoInput.click());

                uploadArea.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    uploadArea.classList.add('border-[#f2cf5b]', 'bg-[#c9a227]/10');
                });

                uploadArea.addEventListener('dragleave', () => {
                    uploadArea.classList.remove('border-[#f2cf5b]', 'bg-[#c9a227]/10');
                });

                uploadArea.addEventListener('drop', (e) => {
                    e.preventDefault();
                    uploadArea.classList.remove('border-[#f2cf5b]', 'bg-[#c9a227]/10');
                    if (e.dataTransfer.files.length) {
                        logoInput.files = e.dataTransfer.files;
                        logoInput.dispatchEvent(new Event('change'));
                    }
                });

                logoInput.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        const file = this.files[0];
                        const maxSize = 3 * 1024 * 1024;
                        const allowedTypes = ['image/png', 'image/jpeg', 'image/svg+xml', 'image/svg'];

                        if (!allowedTypes.includes(file.type) && !file.name.match(/\.(png|jpg|jpeg|svg)$/i)) {
                            alert('Invalid file type. Please upload PNG, JPG, or SVG.');
                            this.value = '';
                            return;
                        }
                        if (file.size > maxSize) {
                            alert('File size exceeds 3MB limit.');
                            this.value = '';
                            return;
                        }

                        fileName.textContent = 'Selected: ' + file.name;
                        fileName.classList.remove('hidden');
                        uploadPlaceholder.style.display = 'none';
                    } else {
                        fileName.classList.add('hidden');
                        uploadPlaceholder.style.display = 'block';
                    }
                });
            }
        });
    </script>
</body>
</html>