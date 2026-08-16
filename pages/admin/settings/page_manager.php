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

$page_class = 'page_manager';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo translate('page_description_page_manager', 'Page Manager for Sahtout WoW Server'); ?>">
    <meta name="robots" content="noindex">
    <title><?php echo translate('title_page_manager', 'Page Manager'); ?></title>
    <link rel="icon" href="<?php echo $base_path . $site_logo; ?>" type="image/x-icon">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/tailwind.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&family=Cinzel:wght@600;700;900&display=swap" rel="stylesheet">
    
    <style>
        /* Only custom CSS for things Tailwind can't do */
        body {
            min-height: 100vh;
            color: #d8d8d8;
            background: #05070b;
            background-image: radial-gradient(1000px 700px at -10% 35%, rgba(59,130,246,.14), transparent 65%), radial-gradient(800px 600px at -5% 85%, rgba(124,58,237,.10), transparent 70%), linear-gradient(180deg, #0a0e16 0%, #060810 45%, #03040a 100%);
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

        .main-content-area {
            transition: margin-left 0.3s ease;
            min-height: calc(100vh - 72px);
        }

        .construction-icon {
            font-size: 5rem;
            color: #f2cf5b;
            filter: drop-shadow(0 0 20px rgba(242,207,82,.3));
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.05);
                opacity: 0.8;
            }
        }

        .coming-soon-title {
            font-family: 'Cinzel', serif;
            font-weight: 900;
            background: linear-gradient(180deg, #fff7d6 0%, #f2cf5b 35%, #c9a227 62%, #8a6a14 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 3px 6px rgba(0,0,0,.85));
        }
    </style>
</head>
<body>
    <?php include $project_root . 'includes/header.php'; ?>

    <div class="flex relative min-h-screen">
        
        <!-- Sidebar -->
        <?php include $project_root . 'includes/admin_sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content-area flex-1 p-3 sm:p-4 md:p-6 lg:p-8 transition-all duration-300 lg:ml-[280px]">
            <div class="max-w-[1400px] mx-auto px-2 sm:px-4 md:px-6 lg:px-8">
                <div class="space-y-4 md:space-y-6 lg:space-y-8">
                    
                    <h1 class="wow-title text-2xl md:text-3xl lg:text-4xl"><?php echo translate('title_page_manager', 'Page Manager'); ?></h1>

                    <!-- Settings Navbar -->
                    <?php include $project_root . 'pages/admin/settings/settings_navbar.php'; ?>

                    <!-- Coming Soon Content -->
                    <div class="panel p-6 md:p-10 lg:p-12">
                        <div class="flex flex-col items-center justify-center text-center py-8 md:py-12 lg:py-16">
                            <!-- Construction Icon -->
                            <div class="construction-icon mb-6">
                                <i class="fas fa-hard-hat"></i>
                            </div>
                            
                            <!-- Title -->
                            <h2 class="coming-soon-title text-2xl md:text-3xl lg:text-4xl mb-4">
                                <?php echo translate('page_under_construction', 'Page Under Construction'); ?>
                            </h2>
                            
                            <!-- Message -->
                            <p class="text-gray-400 text-base md:text-lg max-w-md mx-auto">
                                <?php echo translate('coming_soon_message', 'This feature is coming soon. Stay tuned!'); ?>
                            </p>
                            
                            <!-- Decorative Line -->
                            <div class="w-24 h-px bg-gradient-to-r from-transparent via-[#f2cf5b] to-transparent my-6"></div>
                            
                            <!-- Additional Info -->
                            <p class="text-gray-500 text-sm">
                                <i class="fas fa-code text-[#f2cf5b] mr-2"></i>
                                <?php echo translate('page_manager_info', 'The Page Manager will allow you to create and manage custom pages for your website.'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>