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

        .file-info-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.6rem 0;
            color: #b8c8ff;
            border-bottom: 1px solid rgba(201,162,39,.05);
        }
        .file-info-item:last-child {
            border-bottom: none;
        }
        .file-info-item i {
            color: #f2cf5b;
            width: 1.2rem;
            text-align: center;
        }

        .main-content-area {
            padding-top: 2rem;
        }
    </style>
</head>
<body>
    <?php include_once $project_root . 'includes/header.php'; ?>

    <main class="main-content-area max-w-[1800px] mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-10">
        <div class="max-w-2xl mx-auto">
            
            <h1 class="wow-title text-3xl md:text-4xl text-center mb-8"><?php echo translate('download_title_h1', 'Choose a file to download'); ?></h1>
            
            <?php if (isset($_SESSION['download_error'])): ?>
                <div class="error-box rounded-sm mb-6">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($_SESSION['download_error'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <?php unset($_SESSION['download_error']); ?>
            <?php endif; ?>
            
            <!-- Download Card -->
            <div class="panel p-6 md:p-8">
                <div class="flex flex-col items-center text-center">
                    
                    <!-- File Icon -->
                    <div class="text-6xl text-[#f2cf5b] mb-4">
                        <i class="fas fa-dragon"></i>
                    </div>
                    
                    <h2 class="text-xl md:text-2xl font-bold text-white mb-4 font-['Cinzel']">
                        <?php echo translate('download_file_name', 'Wrath of the Lich King Client'); ?>
                    </h2>
                    
                    <!-- File Info -->
                    <div class="w-full max-w-sm space-y-1 mb-6">
                        <div class="file-info-item">
                            <i class="fas fa-file-archive"></i>
                            <span><?php echo translate('download_file_name', 'Wrath of the Lich King Client'); ?></span>
                        </div>
                        <div class="file-info-item">
                            <i class="fas fa-download"></i>
                            <span><?php echo translate('download_file_size', 'Size'); ?>: <?php 
                                echo file_exists($project_root . 'download/files/wow_woltk.zip') ? 
                                round(filesize($project_root . 'download/files/wow_woltk.zip') / (1024 * 1024), 2) . ' MB' : 
                                translate('download_size_unknown', 'Unknown'); 
                            ?></span>
                        </div>
                        <div class="file-info-item">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span><?php echo translate('download_space_required', 'Requires 35GB free space'); ?></span>
                        </div>
                    </div>
                    
                    <!-- Download Button -->
                    <form method="get" action="<?php echo $base_path; ?>download">
                        <input type="hidden" name="file" value="wow_woltk.zip">
                        <button type="submit" class="download-btn">
                            <i class="fas fa-download"></i> 
                            <?php echo translate('download_button', 'DOWNLOAD NOW'); ?>
                        </button>
                    </form>
                    
                    <!-- Note -->
                    <p class="text-gray-500 text-xs mt-4">
                        <i class="fas fa-shield-alt text-[#f2cf5b] mr-1"></i>
                        <?php echo translate('download_note', 'This download is secured and verified.'); ?>
                    </p>
                </div>
            </div>
            
            <!-- System Requirements -->
            <div class="panel p-4 md:p-6 mt-6">
                <h3 class="text-sm font-bold text-[#f2cf5b] mb-3 font-['Cinzel'] tracking-wide">
                    <i class="fas fa-info-circle mr-2"></i>
                    <?php echo translate('system_requirements', 'System Requirements'); ?>
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-gray-400">
                    <div>
                        <span class="text-[#f2cf5b] font-semibold"><?php echo translate('os', 'OS'); ?>:</span>
                        Windows 7 / 8 / 10 / 11
                    </div>
                    <div>
                        <span class="text-[#f2cf5b] font-semibold"><?php echo translate('cpu', 'CPU'); ?>:</span>
                        Intel Core 2 Duo or better
                    </div>
                    <div>
                        <span class="text-[#f2cf5b] font-semibold"><?php echo translate('ram', 'RAM'); ?>:</span>
                        4GB RAM (8GB recommended)
                    </div>
                    <div>
                        <span class="text-[#f2cf5b] font-semibold"><?php echo translate('gpu', 'GPU'); ?>:</span>
                        DirectX 11 compatible
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include_once $project_root . 'includes/footer.php'; ?>
</body>
</html>