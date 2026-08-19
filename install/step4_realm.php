<?php 
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../includes/paths.php'; // Include paths.php
include __DIR__ . '/header.inc.php';

// Set current step for progress stepper
$current_step = 4;

$errors = [];
$success = false;
$realmsFile = $project_root . 'includes/realm_config.php';
$defaultLogo = 'img/logos/realm1_logo.webp'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $realmName = trim($_POST['realm_name'] ?? '');
    $realmIP = trim($_POST['realm_ip'] ?? '');
    $realmPort = (int) ($_POST['realm_port'] ?? 0);
    $logo_path = $defaultLogo;

    // Validate inputs
    if (empty($realmName)) {
        $errors[] = "❌ " . translate('err_realm_name_required', 'realm name is mandatory.');
    }
    if (empty($realmIP)) {
        $errors[] = "❌ " . translate('err_realm_ip_required', 'realm address is mandatory.');
    }
    if ($realmPort <= 0 || $realmPort > 65535) {
        $errors[] = "❌ " . translate('err_realm_port_invalid', 'realm port must be a valid number (1-65535).');
    }

    // Handle logo upload
    if (isset($_FILES['realm_logo']) && $_FILES['realm_logo']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['realm_logo']['tmp_name'];
        $file_name = $_FILES['realm_logo']['name'];
        $file_size = $_FILES['realm_logo']['size'];
        $file_type = $_FILES['realm_logo']['type'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_exts = ['png', 'svg', 'jpg', 'jpeg', 'webp'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if ($file_size > $max_size) {
            $errors[] = "❌ " . translate('error_realm_logo_too_large', 'realm emblem size exceeds 2MB.');
        } elseif (!in_array($file_ext, $allowed_exts)) {
            $errors[] = "❌ " . translate('error_invalid_realm_logo_type', 'Invalid emblem format. Only PNG, SVG, JPG, or WebP permitted.');
        } else {
            // Validate MIME
            $mimeValid = false;
            switch ($file_ext) {
                case 'png': $mimeValid = $file_type === 'image/png'; break;
                case 'jpg':
                case 'jpeg': $mimeValid = in_array($file_type, ['image/jpeg','image/jpg']); break;
                case 'svg': $mimeValid = $file_type === 'image/svg+xml'; break;
                case 'webp': $mimeValid = $file_type === 'image/webp'; break;
            }
            if (!$mimeValid) {
                $errors[] = "❌ " . translate('error_invalid_realm_logo_type', 'Invalid emblem MIME type for ' . strtoupper($file_ext));
            } else {
                $upload_dir = $project_root . 'img/logos/';
                if (!is_dir($upload_dir) || !is_writable($upload_dir)) {
                    $errors[] = "❌ " . translate('error_realm_logo_upload_failed', 'Emblem upload directory is inaccessible or not writable.');
                } else {
                    $new_file_name = 'realm_logo.' . $file_ext;
                    $destination = $upload_dir . $new_file_name;
                    if (move_uploaded_file($file_tmp, $destination)) {
                        $logo_path = "img/logos/$new_file_name"; 
                    } else {
                        $errors[] = "❌ " . translate('error_realm_logo_upload_failed', 'Failed to upload realm emblem. Verify server permissions.');
                    }
                }
            }
        }
    }

    // Save realm_config.php if no errors
    if (empty($errors)) {
        $newRealmList = [
            [
                'id' => 1,
                'name' => $realmName,
                'address' => $realmIP,
                'port' => $realmPort,
                'logo' => $logo_path
            ]
        ];

        $configPhp  = "<?php\n";
        $configPhp .= "if (!defined('ALLOWED_ACCESS')) { exit('Forbidden'); }\n\n";
        $configPhp .= '$realmlist = ' . var_export($newRealmList, true) . ";\n";

        $configDir = dirname($realmsFile);
        if (!is_writable($configDir)) {
            $errors[] = "❌ " . sprintf(translate('err_config_dir_not_writable_realm', 'Configuration directory is not writable: %s'), $configDir);
        } elseif (file_put_contents($realmsFile, $configPhp) === false) {
            $errors[] = "❌ " . sprintf(translate('err_write_realm_config', 'Cannot write realm configuration file: %s'), $realmsFile);
        } else {
            $success = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($langCode ?? 'en'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo translate('forge_title', 'Sahtout RealmForge'); ?> - <?php echo translate('step4_title', 'Phase 4: realm Setup'); ?></title>
    
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="<?= htmlspecialchars($base_path ?? '/') ?>assets/css/tailwind.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="<?= htmlspecialchars($base_path ?? '/') ?>node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: 
                linear-gradient(135deg, rgba(10, 8, 15, 0.95), rgba(20, 12, 8, 0.95)),
                url('https://www.wallpaperflare.com/static/955/944/93/fantasy-art-dark-knight-artwork-wallpaper.jpg') 
                no-repeat center center fixed;
            background-size: cover;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .font-cinzel { font-family: 'Cinzel', serif; }
        
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #d97706; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #b45309; }
        
        .file-upload-btn {
            display: inline-block;
            padding: 12px 24px;
            background: rgba(11, 113, 230, 0.15);
            border: 1px solid rgba(11, 113, 230, 0.3);
            color: #60a5fa;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            width: 100%;
            text-align: center;
        }
        
        .file-upload-btn:hover {
            background: rgba(11, 113, 230, 0.25);
            border-color: rgba(11, 113, 230, 0.5);
            box-shadow: 0 0 30px rgba(11, 113, 230, 0.1);
        }
        
        .file-upload-btn i {
            margin-right: 8px;
        }

        /* Main content wrapper - FIXED spacing */
        .main-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding-top: 0px;
            padding-bottom: 80px;
        }

        .content-container {
            max-width: 700px;
            width: 100%;
            margin: 0 auto;
            padding: 0 1rem;
        }

        @media (max-width: 768px) {
            .main-wrapper {
                padding-bottom: 70px;
            }
        }
    </style>
</head>
<body class="text-slate-200">

<div class="main-wrapper">
    <!-- Progress Stepper -->
    <?php include __DIR__ . '/progress_stepper.inc.php'; ?>

    <!-- Main Container -->
    <div class="content-container flex-grow">
        <div class="bg-slate-900/60 backdrop-blur-xl border border-slate-700/50 rounded-2xl shadow-2xl p-6 md:p-10 relative overflow-hidden">
            
            <!-- Decorative Corner Elements -->
            <div class="absolute top-0 left-0 w-16 h-16 border-t-2 border-l-2 border-gold-500/30 rounded-tl-2xl pointer-events-none"></div>
            <div class="absolute bottom-0 right-0 w-16 h-16 border-b-2 border-r-2 border-gold-500/30 rounded-br-2xl pointer-events-none"></div>

            <!-- Header -->
          <div class="text-center mb-8">
    <div class="inline-flex items-center justify-center w-16 h-16 bg-gold-500/10 border border-gold-500/30 rounded-full mb-4">
        <img 
            src="logo.png" 
            alt="Logo"
            class="w-12 h-12 object-contain"
        >
    </div>

    <h1 class="font-cinzel text-3xl md:text-4xl font-bold bg-gradient-to-b from-amber-100 to-gold-500 bg-clip-text text-transparent">
        <?php echo translate('step4_title', 'Phase 4: Realm Setup'); ?>
    </h1>

    <p class="text-slate-400 mt-2 text-sm">
        <?php echo translate('step4_description', 'Configure your server realm settings.'); ?>
    </p>
</div>

            <!-- Errors -->
            <?php if (!empty($errors)): ?>
                <div class="bg-red-900/30 border border-red-500/40 text-red-200 p-4 mb-6 rounded-lg">
                    <div class="flex items-center gap-2 mb-2 font-bold text-red-300">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo translate('err_fix_errors_realm', 'Resolve the following issues:'); ?>
                    </div>
                    <ul class="list-disc list-inside text-sm space-y-1 text-red-100/90">
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Success -->
            <?php if ($success): ?>
                <div class="bg-emerald-900/30 border border-emerald-500/40 text-emerald-200 p-5 mb-6 rounded-lg flex items-center gap-3">
                    <i class="fas fa-check-circle text-emerald-400 text-2xl"></i>
                    <span class="font-medium"><?php echo translate('msg_realm_saved', 'Realm configuration stored successfully!'); ?></span>
                </div>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mt-6">
                    <a href="<?php echo $base_path; ?>install/step5_mail" class="inline-flex items-center px-8 py-3 bg-gold-500 hover:bg-gold-400 text-slate-900 font-bold rounded-lg shadow-lg shadow-gold-600/20 transition-all duration-300 transform hover:scale-105">
                        <?php echo translate('btn_proceed_to_mail', 'Advance to Email Configuration'); ?>
                        <i class="fas fa-arrow-right ml-3"></i>
                    </a>
                    <a href="<?php echo $base_path; ?>install/step3_db" class="inline-flex items-center px-6 py-3 bg-slate-700/50 hover:bg-slate-700/70 text-slate-300 font-semibold rounded-lg transition-all duration-300 border border-slate-600/30">
                        <i class="fas fa-arrow-left mr-2"></i>
                        <?php echo translate('btn_go_back', 'Go Back'); ?>
                    </a>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <?php if (!$success): ?>
                <form method="post" enctype="multipart/form-data" class="space-y-5">
                    
                    <!-- Realm Name -->
                    <div>
                        <label for="realm_name" class="block text-xs font-medium text-slate-400 mb-1 uppercase tracking-wider">
                            <i class="fas fa-tag text-gold-400 mr-1"></i>
                            <?php echo translate('label_realm_name', 'Realm Name'); ?>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 pointer-events-none">
                                <i class="fas fa-tag text-sm"></i>
                            </span>
                            <input id="realm_name" type="text" name="realm_name" 
                                   value="<?php echo htmlspecialchars($_POST['realm_name'] ?? 'Sahtout Realm'); ?>"
                                   class="w-full bg-slate-900/60 border border-slate-600 text-slate-200 rounded-lg pl-10 pr-4 py-2.5 focus:ring-2 focus:ring-gold-500 focus:border-gold-500 transition-all placeholder-slate-500 text-sm"
                                   required placeholder="Enter realm name">
                        </div>
                    </div>

                    <!-- Realm IP -->
                    <div>
                        <label for="realm_ip" class="block text-xs font-medium text-slate-400 mb-1 uppercase tracking-wider">
                            <i class="fas fa-network-wired text-gold-400 mr-1"></i>
                            <?php echo translate('label_realm_ip', 'Realm Address / Host'); ?>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 pointer-events-none">
                                <i class="fas fa-network-wired text-sm"></i>
                            </span>
                            <input id="realm_ip" type="text" name="realm_ip" 
                                   value="<?php echo htmlspecialchars($_POST['realm_ip'] ?? '127.0.0.1'); ?>"
                                   class="w-full bg-slate-900/60 border border-slate-600 text-slate-200 rounded-lg pl-10 pr-4 py-2.5 focus:ring-2 focus:ring-gold-500 focus:border-gold-500 transition-all placeholder-slate-500 text-sm"
                                   required placeholder="127.0.0.1">
                        </div>
                    </div>

                    <!-- Realm Port -->
                    <div>
                        <label for="realm_port" class="block text-xs font-medium text-slate-400 mb-1 uppercase tracking-wider">
                            <i class="fas fa-plug text-gold-400 mr-1"></i>
                            <?php echo translate('label_realm_port', 'Realm Port'); ?>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 pointer-events-none">
                                <i class="fas fa-plug text-sm"></i>
                            </span>
                            <input id="realm_port" type="number" name="realm_port" 
                                   value="<?php echo htmlspecialchars($_POST['realm_port'] ?? '8085'); ?>"
                                   class="w-full bg-slate-900/60 border border-slate-600 text-slate-200 rounded-lg pl-10 pr-4 py-2.5 focus:ring-2 focus:ring-gold-500 focus:border-gold-500 transition-all placeholder-slate-500 text-sm"
                                   required placeholder="8085">
                        </div>
                    </div>

                    <!-- Realm Logo -->
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1 uppercase tracking-wider">
                            <i class="fas fa-image text-gold-400 mr-1"></i>
                            <?php echo translate('label_realm_logo', 'Realm Emblem'); ?>
                        </label>
                        <div class="custom-file-upload">
                            <input type="file" id="realm_logo" name="realm_logo" accept="image/png,image/svg+xml,image/jpeg,image/webp" class="hidden">
                            <button type="button" class="file-upload-btn" onclick="document.getElementById('realm_logo').click();">
                                <i class="fas fa-upload"></i>
                                <?php echo translate('btn_choose_file', 'Select Emblem'); ?>
                            </button>
                            <div id="file-name-realm" class="text-slate-500 text-xs mt-2 text-center">
                                <?php echo translate('placeholder_realm_logo', 'Upload a PNG, SVG, JPG, or WebP emblem (max 2MB).'); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Note -->
                    <div class="flex items-start gap-2 p-3 bg-slate-800/30 border border-slate-700/50 rounded-lg">
                        <i class="fas fa-info-circle text-gold-400 mt-0.5"></i>
                        <p class="text-slate-400 text-xs">
                            <?php echo translate('note_realm_config', 'Note: This configures the realm settings.'); ?>
                        </p>
                    </div>

                    <!-- Buttons -->
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-2">
                        <button type="submit" class="w-full sm:w-auto flex items-center justify-center px-10 py-3.5 bg-gold-500 hover:bg-gold-400 text-slate-900 font-bold rounded-lg shadow-lg shadow-gold-600/20 transition-all duration-300 transform hover:scale-105">
                            <i class="fas fa-save mr-2"></i>
                            <?php echo translate('btn_save_realm', 'Store Realm Configuration'); ?>
                        </button>
                        <a href="<?php echo $base_path; ?>install/step3_db" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 bg-slate-700/50 hover:bg-slate-700/70 text-slate-300 font-semibold rounded-lg transition-all duration-300 border border-slate-600/30">
                            <i class="fas fa-arrow-left mr-2"></i>
                            <?php echo translate('btn_go_back', 'Go Back'); ?>
                        </a>
                    </div>
                </form>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.inc.php'; ?>

<script>
    document.getElementById('realm_logo').addEventListener('change', function() {
        const fileName = this.files.length > 0 ? this.files[0].name : '<?php echo translate('placeholder_realm_logo', 'Upload a PNG, SVG, JPG, or WebP emblem (max 2MB).'); ?>';
        document.getElementById('file-name-realm').textContent = fileName;
    });
</script>
</body>
</html>