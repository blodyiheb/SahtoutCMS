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
        * { font-family: 'Inter', sans-serif; }

        body {
            min-height: 100vh;
            color: #d8d8d8;
            background: #05070b;
            background-image:
                radial-gradient(1000px 700px at -10% 35%, rgba(59,130,246,.14), transparent 65%),
                radial-gradient(800px 600px at -5% 85%, rgba(124,58,237,.10), transparent 70%),
                linear-gradient(180deg, #0a0e16 0%, #060810 45%, #03040a 100%);
            background-attachment: fixed;
        }

        .panel {
            position: relative;
            background: linear-gradient(180deg, rgba(22,25,32,.92), rgba(8,10,14,.9));
            border: 1px solid rgba(201,162,39,.22);
            box-shadow: 0 12px 32px rgba(0,0,0,.55), inset 0 0 60px rgba(0,0,0,.45);
        }

        .panel::before {
            content: '';
            position: absolute;
            inset: 5px;
            border: 1px solid rgba(201,162,39,.14);
            pointer-events: none;
        }

        .panel::after {
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

        .wow-title {
            font-family: 'Cinzel', serif;
            font-weight: 900;
            background: linear-gradient(180deg, #fff7d6 0%, #f2cf5b 35%, #c9a227 62%, #8a6a14 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 3px 6px rgba(0,0,0,.85));
        }

        .section-title {
            font-family: 'Cinzel', serif;
            font-weight: 700;
            color: #f2cf5b;
            text-shadow: 0 0 12px rgba(201,162,39,.35), 0 2px 4px rgba(0,0,0,.8);
        }

        .btn-gold {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            padding: .75rem 1.5rem;
            font-weight: 800;
            font-size: .85rem;
            letter-spacing: .04em;
            text-transform: uppercase;
            clip-path: polygon(10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%, 0 10px);
            transition: transform .2s ease;
            background: linear-gradient(180deg, #f6d478 0%, #c9a227 48%, #8a6a14 100%);
            color: #1a1200;
            text-shadow: 0 1px 0 rgba(255,255,255,.35);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.28), inset 0 -8px 14px rgba(0,0,0,.25);
            border: none;
            cursor: pointer;
        }
        .btn-gold:hover { transform: translateY(-2px) scale(1.02); }

        .btn-iron {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            padding: .75rem 1.5rem;
            font-weight: 800;
            font-size: .85rem;
            letter-spacing: .04em;
            text-transform: uppercase;
            clip-path: polygon(10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%, 0 10px);
            transition: transform .2s ease;
            background: linear-gradient(180deg, #3a404d 0%, #232833 55%, #141821 100%);
            color: #cfe1ff;
            box-shadow: inset 0 0 0 1px rgba(120,160,255,.25), inset 0 -8px 14px rgba(0,0,0,.4);
            border: none;
            cursor: pointer;
        }
        .btn-iron:hover { transform: translateY(-2px) scale(1.02); }

        .input-dark {
            background: rgba(10, 14, 22, 0.8);
            border: 1px solid rgba(201,162,39,.3);
            color: #e5e7eb;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            outline: none;
            width: 100%;
        }
        .input-dark:focus {
            border-color: #f2cf5b;
            box-shadow: 0 0 10px rgba(242,207,82,.2);
            background: rgba(15, 20, 30, 0.9);
        }
        .input-dark::placeholder { color: rgba(150, 170, 200, 0.4); }
        .input-dark option { background: #0a0e16; }

        .input-dark-textarea {
            background: rgba(10, 14, 22, 0.8);
            border: 1px solid rgba(201,162,39,.3);
            color: #e5e7eb;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            outline: none;
            width: 100%;
            resize: vertical;
            min-height: 80px;
        }
        .input-dark-textarea:focus {
            border-color: #f2cf5b;
            box-shadow: 0 0 10px rgba(242,207,82,.2);
            background: rgba(15, 20, 30, 0.9);
        }

        .upload-area {
            border: 2px dashed rgba(201,162,39,.2);
            background: rgba(10, 14, 22, 0.5);
            transition: all 0.3s ease;
            cursor: pointer;
            padding: 2rem 1.5rem;
            text-align: center;
        }
        .upload-area:hover {
            border-color: rgba(201,162,39,.4);
            background: rgba(15, 20, 30, 0.7);
        }
        .upload-area.dragover {
            border-color: #f2cf5b;
            background: rgba(201,162,39,.08);
        }

        .social-input-group {
            display: flex;
            align-items: stretch;
            margin-bottom: 0.5rem;
        }
        .social-input-group .social-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1rem;
            background: rgba(10, 14, 22, 0.9);
            border: 1px solid rgba(201,162,39,.3);
            border-right: none;
            color: #f2cf5b;
            font-size: 1.1rem;
            min-width: 48px;
            flex-shrink: 0;
        }
        .social-input-group .social-icon img {
            width: 18px;
            height: 18px;
            filter: brightness(0) invert(78%) sepia(58%) saturate(452%) hue-rotate(10deg) brightness(95%) contrast(88%);
        }
        .social-input-group .input-dark {
            border-left: none;
        }
        .social-input-group .input-dark:focus {
            border-color: #f2cf5b;
            box-shadow: 0 0 10px rgba(242,207,82,.2);
        }

        .alert-success-wow {
            background: rgba(46, 204, 113, 0.15);
            border: 1px solid rgba(46, 204, 113, 0.4);
            color: #2ecc71;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .alert-success-wow i {
            font-size: 1.2rem;
        }

        .alert-danger-wow {
            background: rgba(231, 76, 60, 0.15);
            border: 1px solid rgba(231, 76, 60, 0.4);
            color: #e74c3c;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .alert-danger-wow i {
            font-size: 1.2rem;
        }

        /* Main content area - works with sidebar */
        .main-content-area {
            transition: margin-left 0.3s ease;
            min-height: calc(100vh - 72px);
            width: 100%;
        }

        /* Content wrapper with proper spacing */
        .content-wrapper {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        @media (min-width: 640px) {
            .content-wrapper {
                padding: 0 1.5rem;
            }
        }

        @media (min-width: 1024px) {
            .content-wrapper {
                padding: 0 2rem;
            }
        }

        @media (min-width: 1280px) {
            .content-wrapper {
                padding: 0 2.5rem;
            }
        }

        @media (min-width: 1024px) {
            .main-content-area.lg\:ml-0 {
                margin-left: 0;
            }
            .main-content-area.lg\:ml-\[280px\] {
                margin-left: 280px;
            }
        }

        @media (max-width: 1023px) {
            .main-content-area {
                margin-left: 0 !important;
                padding: 1rem;
            }
            .content-wrapper {
                padding: 0 0.5rem;
            }
        }

        /* Logo preview centered */
        .logo-preview-container {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 0.5rem;
            background: rgba(10, 14, 22, 0.5);
            border: 1px solid rgba(201,162,39,.2);
            min-height: 140px;
        }
        .logo-preview {
            max-height: 120px;
            max-width: 100%;
            object-fit: contain;
        }

        .form-label {
            font-family: 'Cinzel', serif;
            font-weight: 700;
            color: #f2cf5b;
            text-shadow: 0 0 12px rgba(201,162,39,.15), 0 2px 4px rgba(0,0,0,.8);
            font-size: 0.9rem;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-text {
            color: #6a7a8a;
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }

        .hidden-input {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            border: 0;
        }
    </style>
</head>
<body>
    <?php include $project_root . 'includes/header.php'; ?>

    <!-- Main Content Area with Sidebar -->
    <div class="flex relative min-h-screen">
        
        <!-- Sidebar -->
        <?php include $project_root . 'includes/admin_sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content-area flex-1 p-3 sm:p-4 md:p-6 lg:p-8 transition-all duration-300 lg:ml-[280px]">
            <div class="content-wrapper">
                <div class="space-y-4 md:space-y-6 lg:space-y-8">
                    
                    <h1 class="wow-title text-2xl md:text-3xl lg:text-4xl"><?php echo translate('page_title_general', 'General Settings'); ?></h1>

                    <!-- Settings Navbar -->
                    <?php include $project_root . 'pages/admin/settings/settings_navbar.php'; ?>

                    <!-- Success / Error Messages -->
                    <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
                        <div class="alert-success-wow rounded-sm">
                            <i class="fas fa-check-circle"></i>
                            <span><?php echo translate('msg_settings_saved', 'Settings updated successfully!'); ?></span>
                        </div>
                    <?php elseif (isset($_GET['status']) && $_GET['status'] === 'error'): ?>
                        <div class="alert-danger-wow rounded-sm">
                            <i class="fas fa-exclamation-circle"></i>
                            <div>
                                <strong><?php echo translate('err_fix_errors', 'Error:'); ?></strong>
                                <span><?php echo htmlspecialchars(urldecode($_GET['message'])); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- General Settings Form -->
                    <div class="panel p-4 md:p-6 lg:p-8">
                        <h2 class="section-title text-lg md:text-xl mb-4 md:mb-6 flex items-center gap-3">
                            <i class="fas fa-cogs text-[#f2cf5b]"></i>
                            <?php echo translate('settings_general', 'General Settings'); ?>
                        </h2>

                        <form action="<?php echo $base_path; ?>pages/admin/settings/save_general.php" method="POST" enctype="multipart/form-data" class="space-y-4 md:space-y-6 max-w-3xl mx-auto">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="MAX_FILE_SIZE" value="3145728">

                            <!-- Website Title -->
                            <div>
                                <label for="site_title_name" class="form-label">
                                    <?php echo translate('label_website_title', 'Website Title'); ?>
                                </label>
                                <input type="text"
                                       id="site_title_name"
                                       name="site_title_name"
                                       class="input-dark rounded-sm"
                                       value="<?php echo htmlspecialchars($site_title_name); ?>"
                                       placeholder="<?php echo translate('placeholder_site_title', 'e.g. My Awesome Site'); ?>"
                                       required>
                                <div class="form-text">
                                    <?php echo translate('help_site_title', 'This title appears in the browser tab, site header, and SEO.'); ?>
                                </div>
                            </div>

                            <!-- Featured YouTube Video -->
                            <div>
                                <label for="youtube_embed_url" class="form-label"><?php echo translate('label_youtube_embed', 'Featured YouTube Embed Link'); ?></label>
                                <input type="url"
                                       id="youtube_embed_url"
                                       name="youtube_embed_url"
                                       class="input-dark rounded-sm"
                                       value="<?php echo htmlspecialchars($youtube_embed_url ?? ''); ?>"
                                       placeholder="https://www.youtube.com/embed/VIDEO_ID"
                                       required>
                                <div class="form-text">
                                    <?php echo translate('help_youtube_embed', 'Paste a YouTube watch, short, or embed link. The system will normalize it to an embed URL.'); ?>
                                </div>
                            </div>

                            <div>
                                <label for="youtube_title" class="form-label"><?php echo translate('label_youtube_title', 'Video Title'); ?></label>
                                <input type="text"
                                       id="youtube_title"
                                       name="youtube_title"
                                       class="input-dark rounded-sm"
                                       value="<?php echo htmlspecialchars($youtube_title ?? 'Featured Video'); ?>"
                                       placeholder="<?php echo translate('placeholder_youtube_title', 'Featured Video'); ?>"
                                       required>
                                <div class="form-text">
                                    <?php echo translate('help_youtube_title', 'This title appears centered above the video.'); ?>
                                </div>
                            </div>

                            <div>
                                <label for="youtube_description" class="form-label"><?php echo translate('label_youtube_description', 'Video Description'); ?></label>
                                <textarea id="youtube_description"
                                          name="youtube_description"
                                          class="input-dark-textarea rounded-sm"
                                          rows="3"
                                          placeholder="<?php echo translate('placeholder_youtube_description', 'Watch a featured video here...'); ?>"><?php echo htmlspecialchars($youtube_description ?? 'Watch a featured video here. Replace it with your own channel or highlight later.'); ?></textarea>
                                <div class="form-text">
                                    <?php echo translate('help_youtube_description', 'This text appears under the title in the video panel.'); ?>
                                </div>
                            </div>

                            <!-- Logo Upload - Centered -->
                            <div>
                                <label class="form-label text-center block"><?php echo translate('label_website_logo', 'Website Logo'); ?></label>
                                
                                <!-- Current Logo Preview - Centered -->
                                <div class="logo-preview-container rounded-sm mb-3">
                                    <img src="<?php echo $base_path . htmlspecialchars($site_logo); ?>" alt="Current Logo" class="logo-preview">
                                </div>
                                
                                <!-- Upload Area - Centered -->
                                <div class="upload-area rounded-sm max-w-md mx-auto" id="uploadArea">
                                    <input type="file" id="logo" name="logo" class="hidden-input" accept=".png,.jpg,.jpeg,.svg">
                                    <div id="uploadPlaceholder">
                                        <i class="fas fa-cloud-upload-alt text-4xl text-[#c9a227]/40 mb-3 block"></i>
                                        <p class="text-sm text-gray-400"><?php echo translate('label_website_logo', 'Upload Logo'); ?></p>
                                        <p class="text-xs text-gray-500 mt-1">PNG, JPG or SVG (max 3MB)</p>
                                    </div>
                                    <div id="file-name" class="text-sm text-[#f2cf5b] hidden mt-2 font-semibold"></div>
                                </div>
                                <div class="form-text text-center">
                                    <?php echo translate('help_logo', 'Upload a new logo image. PNG, JPG or SVG formats supported (max 3MB).'); ?>
                                </div>
                            </div>

                            <!-- Social Links -->
                            <div>
                                <label class="form-label"><?php echo translate('label_social_media', 'Social Media Links'); ?></label>
                                
                                <?php
                                $icons = [
                                    'facebook'  => 'fab fa-facebook-f',
                                    'twitter'   => 'fab fa-x-twitter',
                                    'tiktok'    => 'fab fa-tiktok',
                                    'youtube'   => 'fab fa-youtube',
                                    'discord'   => 'fab fa-discord',
                                    'twitch'    => 'fab fa-twitch',
                                    'kick'      => 'custom',
                                    'instagram' => 'fab fa-instagram',
                                    'github'    => 'fab fa-github',
                                    'linkedin'  => 'fab fa-linkedin-in',
                                ];

                                foreach ($icons as $platform => $icon): ?>
                                    <div class="social-input-group">
                                        <span class="social-icon rounded-l-sm">
                                            <?php if ($platform === 'kick'): ?>
                                                <img src="<?php echo $base_path; ?>img/icons/kick-logo.png" alt="Kick">
                                            <?php else: ?>
                                                <i class="<?php echo $icon; ?>"></i>
                                            <?php endif; ?>
                                        </span>
                                        <input type="url"
                                               name="<?php echo $platform; ?>"
                                               class="input-dark rounded-r-sm"
                                               placeholder="<?php echo translate("placeholder_{$platform}", ucfirst($platform) . ' URL'); ?>"
                                               value="<?php echo htmlspecialchars($social_links[$platform] ?? ''); ?>">
                                    </div>
                                <?php endforeach; ?>
                                <div class="form-text">
                                    <?php echo translate('help_social_links', 'Enter the full URLs for your social media profiles. Leave blank to hide.'); ?>
                                </div>
                            </div>

                            <!-- Save Button -->
                            <div class="pt-4 border-t border-[rgba(201,162,39,.1)] flex justify-end">
                                <button type="submit" class="btn-gold">
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
                    uploadArea.classList.add('dragover');
                });

                uploadArea.addEventListener('dragleave', () => {
                    uploadArea.classList.remove('dragover');
                });

                uploadArea.addEventListener('drop', (e) => {
                    e.preventDefault();
                    uploadArea.classList.remove('dragover');
                    if (e.dataTransfer.files.length) {
                        logoInput.files = e.dataTransfer.files;
                        logoInput.dispatchEvent(new Event('change'));
                    }
                });

                logoInput.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        const file = this.files[0];
                        const maxSize = 3 * 1024 * 1024; // 3MB
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