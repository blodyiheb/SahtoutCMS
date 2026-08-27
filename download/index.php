<?php
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../includes/paths.php';
require_once $project_root . 'includes/session.php';
require_once $project_root . 'languages/language.php';
require_once $project_root . 'includes/config.settings.php';

// Handle download request if submitted
if (isset($_GET['file'])) {
    // Immediately clear any existing output buffers
    while (ob_get_level()) ob_end_clean();
    
    $file = basename($_GET['file']);
    $path = $project_root . 'download/files/' . $file;
    
    if (file_exists($path)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($path));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        readfile($path);
        exit;
    } else {
        $_SESSION['download_error'] = translate('download_error_file_not_found', 'File not found');
        header("Location: {$base_path}download/woltk.php");
        exit;
    }
}

// Get all local files dynamically
$download_dir = $project_root . 'download/files/';
$files = [];

if (is_dir($download_dir)) {
    foreach (scandir($download_dir) as $file) {
        if ($file !== '.' && $file !== '..' && $file !== 'index.php' && is_file($download_dir . $file)) {
            $file_size = filesize($download_dir . $file);
            $size_mb = round($file_size / (1024 * 1024), 2);
            $size_gb = round($file_size / (1024 * 1024 * 1024), 2);
            $recommended_space_gb = max(ceil($size_gb * 3), 1);

            $files[] = [
                'name' => $file,
                'size_mb' => $size_mb,
                'size_gb' => $size_gb,
                'size_formatted' => $size_mb < 1024 ? $size_mb . ' MB' : $size_gb . ' GB',
                'recommended_space' => $recommended_space_gb
            ];
        }
    }
}

$page_class = 'download';
include_once $project_root . 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo translate('download_meta_description', 'Download Wrath of the Lich King client for Sahtout WoW Server'); ?>">
    <title><?php echo $site_title_name . " " . translate('download_title', 'Download'); ?></title>
    <link rel="icon" href="<?php echo $base_path . $site_logo; ?>" type="image/x-icon">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/tailwind.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&family=Cinzel:wght@600;700;900&display=swap" rel="stylesheet">
    
    <style>
        * { font-family: 'Inter', sans-serif; }

        /* Fix footer to bottom */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            color: #d8d8d8;
            background: #05070b;
            background-image:
                radial-gradient(1000px 700px at -10% 35%, rgba(59,130,246,.14), transparent 65%),
                radial-gradient(800px 600px at -5% 85%, rgba(124,58,237,.10), transparent 70%),
                linear-gradient(180deg, #0a0e16 0%, #060810 45%, #03040a 100%);
            background-attachment: fixed;
            padding-top: 80px; /* Add padding to push content below header */
        }

        /* Header stays at top - fixed position */
        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            flex-shrink: 0;
            width: 100%;
            background: rgba(10, 14, 22, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(201,162,39,0.2);
        }

        /* Main content wrapper */
        .main-wrapper {
            flex: 1 0 auto;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 1rem;
        }

        /* Footer stays at bottom */
        footer {
            flex-shrink: 0;
            width: 100%;
            margin-top: auto;
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
            letter-spacing: .02em;
        }

        .download-btn {
            display: inline-flex;
            align-items: center;
            gap: .75rem;
            padding: 1rem 2.5rem;
            font-weight: 800;
            font-size: 1rem;
            letter-spacing: .06em;
            text-transform: uppercase;
            clip-path: polygon(12px 0, 100% 0, 100% calc(100% - 12px), calc(100% - 12px) 100%, 0 100%, 0 12px);
            transition: transform .3s ease, box-shadow .3s ease;
            background: linear-gradient(180deg, #f6d478 0%, #c9a227 48%, #8a6a14 100%);
            color: #1a1200;
            text-shadow: 0 1px 0 rgba(255,255,255,.35);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.28), inset 0 -8px 14px rgba(0,0,0,.25), 0 8px 24px rgba(201,162,39,.3);
            border: none;
            cursor: pointer;
        }
        .download-btn:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.4), inset 0 -8px 14px rgba(0,0,0,.25), 0 12px 32px rgba(201,162,39,.5);
        }

        .error-box {
            background: rgba(231, 76, 60, 0.15);
            border: 1px solid rgba(231, 76, 60, 0.4);
            color: #e74c3c;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .error-box i {
            font-size: 1.2rem;
        }

        /* Navigation tabs */
        .nav-tabs {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 2rem;
        }
        .nav-tab {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(201,162,39,0.2);
            color: #b8c8ff;
            padding: 0.7rem 1.8rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .nav-tab:hover {
            background: rgba(201,162,39,0.1);
            border-color: rgba(201,162,39,0.5);
            color: #fff;
        }
        .nav-tab.active {
            background: rgba(201,162,39,0.15);
            border-color: #c9a227;
            color: #f2cf5b;
            box-shadow: 0 0 20px rgba(201,162,39,0.1);
        }
        .nav-tab i {
            color: #f2cf5b;
        }

        .section-panel {
            display: none;
            animation: fadeIn 0.4s ease;
        }
        .section-panel.active {
            display: block;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .file-card {
            transition: all 0.3s ease;
        }
        .file-card:hover {
            transform: translateY(-5px);
            border-color: rgba(201,162,39,0.4);
        }

        .external-link-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1.8rem;
            background: rgba(201,162,39,0.12);
            border: 1px solid rgba(201,162,39,0.25);
            color: #f2cf5b;
            border-radius: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
            font-weight: 600;
        }
        .external-link-btn:hover {
            background: rgba(201,162,39,0.2);
            border-color: #c9a227;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(201,162,39,0.2);
        }

        .addon-card {
            text-align: center;
            transition: all 0.3s ease;
        }
        .addon-card:hover {
            transform: translateY(-5px);
            border-color: rgba(201,162,39,0.4);
        }
        .addon-card .icon-wrap {
            font-size: 3rem;
            color: #f2cf5b;
            margin-bottom: 0.5rem;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            body {
                padding-top: 70px;
            }
            .main-wrapper {
                padding-top: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header is included at the top via include_once -->

    <!-- Main content wrapper -->
    <div class="main-wrapper">
        <main class="flex-1 flex flex-col items-center w-full px-4 py-6 md:py-12">
            <div class="container mx-auto max-w-6xl">
                
                <h1 class="wow-title text-3xl md:text-4xl text-center mb-8">
                    <?php echo translate('download_title_h1', 'Choose a file to download'); ?>
                </h1>
                
                <?php if (isset($_SESSION['download_error'])): ?>
                    <div class="error-box rounded-sm mb-6">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($_SESSION['download_error'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <?php unset($_SESSION['download_error']); ?>
                <?php endif; ?>

                <!-- Navigation Tabs -->
                <div class="nav-tabs">
                    <button class="nav-tab active" data-section="direct">
                        <i class="fas fa-cloud-download-alt"></i> Direct Downloads
                    </button>
                    <button class="nav-tab" data-section="external">
                        <i class="fas fa-link"></i> External Mirrors
                    </button>
                    <button class="nav-tab" data-section="addons">
                        <i class="fas fa-puzzle-piece"></i> Addons
                    </button>
                </div>

                <!-- SECTION: Direct Downloads -->
                <div id="section-direct" class="section-panel active">
                    <?php if (empty($files)): ?>
                        <div class="panel p-8 text-center">
                            <div class="text-6xl text-[#f2cf5b] mb-4">
                                <i class="fas fa-folder-open"></i>
                            </div>
                            <p class="text-gray-400 text-lg"><?php echo translate('no_files_available', 'No files available right now.'); ?></p>
                            <button onclick="switchTab('external')" class="download-btn mt-4">
                                <i class="fas fa-external-link-alt"></i> View External Mirrors
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($files as $file): ?>
                                <div class="panel p-6 file-card">
                                    <div class="flex flex-col h-full">
                                        <div class="flex items-start justify-between mb-3">
                                            <div class="text-3xl text-[#f2cf5b]">
                                                <i class="fas fa-file-archive"></i>
                                            </div>
                                            <span class="text-xs px-3 py-1 rounded-full bg-[#f2cf5b]/10 text-[#f2cf5b] border border-[#f2cf5b]/20">
                                                <?php echo $file['size_formatted']; ?>
                                            </span>
                                        </div>
                                        <h3 class="text-lg font-bold text-white mb-2 font-['Cinzel'] truncate">
                                            <?php echo htmlspecialchars($file['name']); ?>
                                        </h3>
                                        <div class="space-y-1 text-sm text-gray-400 mb-4">
                                            <div class="flex items-center gap-2">
                                                <i class="fas fa-hdd text-[#f2cf5b] w-4"></i>
                                                <span>Size: <?php echo $file['size_formatted']; ?></span>
                                            </div>
                                            <?php if ($file['size_gb'] >= 1): ?>
                                                <div class="flex items-center gap-2">
                                                    <i class="fas fa-exclamation-triangle text-[#f2cf5b] w-4"></i>
                                                    <span>Needs <?php echo $file['recommended_space']; ?>GB free space</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mt-auto">
                                            <form method="get" action="<?php echo $base_path; ?>download">
                                                <input type="hidden" name="file" value="<?php echo htmlspecialchars($file['name']); ?>">
                                                <button type="submit" class="download-btn w-full justify-center text-sm">
                                                    <i class="fas fa-download"></i> 
                                                    <?php echo translate('download_button', 'DOWNLOAD'); ?>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- SECTION: External Mirrors -->
                <div id="section-external" class="section-panel">
                    <div class="panel p-6 md:p-8">
                        <h2 class="text-2xl font-bold text-center mb-6 font-['Cinzel'] text-white">
                            <i class="fas fa-globe text-[#f2cf5b] mr-2"></i>
                            <?php echo translate('external_mirrors', 'External Mirrors'); ?>
                        </h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div class="text-center p-6 bg-black/20 rounded-lg border border-[#f2cf5b]/10">
                                <div class="text-5xl text-[#f2cf5b] mb-3">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <h3 class="text-xl font-bold text-white font-['Cinzel']">MEGA</h3>
                                <p class="text-gray-400 text-sm mt-1">Fast & secure cloud hosting</p>
                                <a href="https://mega.nz" target="_blank" class="external-link-btn mt-4">
                                    <i class="fas fa-external-link-alt"></i> Open MEGA
                                </a>
                            </div>
                            <div class="text-center p-6 bg-black/20 rounded-lg border border-[#f2cf5b]/10">
                                <div class="text-5xl text-[#f2cf5b] mb-3">
                                    <i class="fas fa-fire"></i>
                                </div>
                                <h3 class="text-xl font-bold text-white font-['Cinzel']">MediaFire</h3>
                                <p class="text-gray-400 text-sm mt-1">Reliable file sharing</p>
                                <a href="https://mediafire.com" target="_blank" class="external-link-btn mt-4">
                                    <i class="fas fa-external-link-alt"></i> Open MediaFire
                                </a>
                            </div>
                            <div class="text-center p-6 bg-black/20 rounded-lg border border-[#f2cf5b]/10">
                                <div class="text-5xl text-[#f2cf5b] mb-3">
                                    <i class="fas fa-file-archive"></i>
                                </div>
                                <h3 class="text-xl font-bold text-white font-['Cinzel']">Gofile</h3>
                                <p class="text-gray-400 text-sm mt-1">Instant free downloads</p>
                                <a href="https://gofile.io" target="_blank" class="external-link-btn mt-4">
                                    <i class="fas fa-external-link-alt"></i> Open Gofile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION: Addons -->
                <div id="section-addons" class="section-panel">
                    <div class="panel p-6 md:p-8">
                        <h2 class="text-2xl font-bold text-center mb-6 font-['Cinzel'] text-white">
                            <i class="fas fa-puzzle-piece text-[#f2cf5b] mr-2"></i>
                            <?php echo translate('essential_addons', 'Essential Addons'); ?>
                        </h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div class="addon-card panel p-6">
                                <div class="icon-wrap">
                                    <i class="fas fa-skull"></i>
                                </div>
                                <h3 class="text-xl font-bold text-white font-['Cinzel']">Deadly Boss Mods</h3>
                                <p class="text-gray-400 text-sm mt-1">Raid & dungeon alerts</p>
                                <button onclick="window.open('https://curseforge.com/wow/addons/deadly-boss-mods-dbm', '_blank')" class="external-link-btn mt-4">
                                    <i class="fas fa-download"></i> Get DBM
                                </button>
                            </div>
                            <div class="addon-card panel p-6">
                                <div class="icon-wrap">
                                    <i class="fas fa-magic"></i>
                                </div>
                                <h3 class="text-xl font-bold text-white font-['Cinzel']">WeakAuras</h3>
                                <p class="text-gray-400 text-sm mt-1">Custom UI triggers</p>
                                <button onclick="window.open('https://wago.io', '_blank')" class="external-link-btn mt-4">
                                    <i class="fas fa-eye"></i> Browse Auras
                                </button>
                            </div>
                            <div class="addon-card panel p-6">
                                <div class="icon-wrap">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                                <h3 class="text-xl font-bold text-white font-['Cinzel']">Details!</h3>
                                <p class="text-gray-400 text-sm mt-1">DPS & combat analytics</p>
                                <button onclick="window.open('https://curseforge.com/wow/addons/details', '_blank')" class="external-link-btn mt-4">
                                    <i class="fas fa-rocket"></i> Install
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script>
        // Tab switching
        document.querySelectorAll('.nav-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active from all tabs
                document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                // Hide all sections
                document.querySelectorAll('.section-panel').forEach(s => s.classList.remove('active'));

                // Show selected section
                const sectionId = 'section-' + this.dataset.section;
                document.getElementById(sectionId).classList.add('active');
            });
        });

        // Switch to external tab (used by "View External Mirrors" button)
        function switchTab(tabName) {
            document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.section-panel').forEach(s => s.classList.remove('active'));
            
            const tab = document.querySelector(`.nav-tab[data-section="${tabName}"]`);
            if (tab) tab.classList.add('active');
            
            const section = document.getElementById('section-' + tabName);
            if (section) section.classList.add('active');
        }
    </script>

    <?php include_once $project_root . 'includes/footer.php'; ?>
</body>
</html>