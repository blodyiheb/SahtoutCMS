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

$page_class = 'realm';

$errors = [];
$success = false;
$realmsFile = $project_root . 'includes/realm_config.php';

$defaultLogo = 'img/logos/realm1_logo.webp';

require_once $project_root . 'includes/realm_config.php';

$currentRealm = $realmlist[0] ?? [
    'name' => 'Sahtout Realm',
    'address' => '127.0.0.1',
    'port' => 8085,
    'logo' => $defaultLogo
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $realmName = trim($_POST['realm_name'] ?? '');
    $realmIP = trim($_POST['realm_ip'] ?? '');
    $realmPort = (int) ($_POST['realm_port'] ?? 0);
    $logo_path = $defaultLogo;

    if (empty($realmName)) {
        $errors[] = translate('err_realm_name_required', 'Realm Name is required.');
    }
    if (empty($realmIP)) {
        $errors[] = translate('err_realm_ip_required', 'Realm IP is required.');
    }
    if ($realmPort <= 0 || $realmPort > 65535) {
        $errors[] = translate('err_realm_port_invalid', 'Realm Port must be a valid number (1-65535).');
    }

    // Handle logo upload
    if (isset($_FILES['realm_logo']) && $_FILES['realm_logo']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['realm_logo']['tmp_name'];
        $file_name = $_FILES['realm_logo']['name'];
        $file_size = $_FILES['realm_logo']['size'];
        $file_type = $_FILES['realm_logo']['type'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_exts = ['png', 'svg', 'jpg', 'jpeg', 'webp'];
        $max_size = 2 * 1024 * 1024;

        if ($file_size > $max_size) {
            $errors[] = translate('error_realm_logo_too_large', 'Realm logo size exceeds 2MB.');
        } elseif (!in_array($file_ext, $allowed_exts)) {
            $errors[] = translate('error_invalid_realm_logo_type', 'Invalid file type. Only PNG, SVG, JPG, or WebP allowed.');
        } else {
            $upload_dir = $project_root . 'img/logos/';
            if (!is_dir($upload_dir) || !is_writable($upload_dir)) {
                $errors[] = translate('error_realm_logo_upload_failed', 'Upload directory is not accessible or writable.');
            } else {
                $new_file_name = 'realm_logo.' . $file_ext;
                $destination = $upload_dir . $new_file_name;

                if (move_uploaded_file($file_tmp, $destination)) {
                    $logo_path = "img/logos/$new_file_name";
                } else {
                    $errors[] = translate('error_realm_logo_upload_failed', 'Failed to upload realm logo.');
                }
            }
        }
    }

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
            $errors[] = sprintf(translate('err_config_dir_not_writable', 'Config directory is not writable: %s'), $configDir);
        } elseif (file_put_contents($realmsFile, $configPhp) === false) {
            $errors[] = sprintf(translate('err_write_realm_config', 'Cannot write realm configuration file: %s'), $realmsFile);
        } else {
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo translate('page_description_realm', 'Realm Configuration for Sahtout WoW Server'); ?>">
    <meta name="robots" content="noindex">
    <title><?php echo translate('page_title_realm', 'Realm Configuration'); ?></title>
    <link rel="icon" href="<?php echo $base_path . $site_logo; ?>" type="image/x-icon">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/tailwind.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&family=Cinzel:wght@600;700;900&display=swap" rel="stylesheet">
    
    <style>
        /* Only keep what Tailwind CANNOT do */
        
        /* Font families */
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
        
        /* Custom clip-path for buttons */
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
                        <?php echo translate('page_title_realm', 'Realm Configuration'); ?>
                    </h1>

                    <!-- Settings Navbar -->
                    <?php include $project_root . 'pages/admin/settings/settings_navbar.php'; ?>

                    <!-- Success / Error Messages -->
                    <?php if (!empty($errors)): ?>
                        <div class="bg-[#e74c3c]/15 border border-[#e74c3c]/40 text-[#e74c3c] 
                                    p-4 rounded-sm flex items-start gap-3">
                            <i class="fas fa-exclamation-circle text-xl mt-0.5"></i>
                            <div>
                                <strong><?php echo translate('err_fix_errors', 'Please fix the following errors:'); ?></strong>
                                <?php foreach ($errors as $err): ?>
                                    <div class="text-sm mt-1">• <?php echo htmlspecialchars($err); ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="bg-[#2ecc71]/15 border border-[#2ecc71]/40 text-[#2ecc71] 
                                    p-4 rounded-sm flex items-center gap-3">
                            <i class="fas fa-check-circle text-xl"></i>
                            <span><?php echo translate('msg_realm_saved', 'Realm configuration saved successfully!'); ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Realm Configuration Form -->
                    <div class="relative bg-gradient-to-b from-[#161920]/92 to-[#080a0e]/90 
                                border border-[#c9a227]/[0.22] 
                                shadow-[0_12px_32px_rgba(0,0,0,.55),inset_0_0_60px_rgba(0,0,0,.45)]
                                p-4 md:p-6 lg:p-8 panel-gold-corners">
                        
                        <h2 class="section-title text-lg md:text-xl mb-4 md:mb-6 flex items-center gap-3 
                                   text-[#f2cf5b] font-bold drop-shadow-[0_0_12px_rgba(201,162,39,.35),0_2px_4px_rgba(0,0,0,.8)]">
                            <i class="fas fa-server text-[#f2cf5b]"></i>
                            <?php echo translate('section_realm_config', 'Realm Configuration'); ?>
                        </h2>

                        <form method="POST" enctype="multipart/form-data" class="space-y-4 md:space-y-6 max-w-3xl mx-auto">
                            <!-- Current Logo Preview -->
                            <div>
                                <label class="form-label text-[#f2cf5b] font-bold text-sm 
                                               tracking-wider block mb-2 
                                               drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                    <?php echo translate('label_current_logo', 'Current Logo'); ?>
                                </label>
                                <div class="flex justify-center p-4 bg-[#0a0e16]/50 border border-[rgba(201,162,39,.15)] rounded-sm">
                                    <img src="<?php echo $base_path . htmlspecialchars($currentRealm['logo'] ?? $defaultLogo); ?>" 
                                         alt="Realm Logo" 
                                         class="max-h-[80px] max-w-full object-contain">
                                </div>
                            </div>

                            <!-- Realm Name -->
                            <div>
                                <label for="realm_name" class="form-label text-[#f2cf5b] font-bold text-sm 
                                                                 tracking-wider block mb-2 
                                                                 drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                    <?php echo translate('label_realm_name', 'Realm Name'); ?>
                                </label>
                                <input type="text" id="realm_name" name="realm_name" 
                                       class="w-full px-4 py-3 text-[0.95rem] text-[#e5e7eb] 
                                              bg-[#0a0e16]/80 border border-[#c9a227]/30 rounded-sm 
                                              focus:border-[#f2cf5b] focus:shadow-[0_0_10px_rgba(242,207,82,.2)] 
                                              focus:bg-[#0f141e]/90 outline-none transition-all duration-200 
                                              placeholder:text-[#96aac8]/40"
                                       placeholder="<?php echo translate('placeholder_realm_name', 'Enter realm name'); ?>" 
                                       value="<?php echo htmlspecialchars($_POST['realm_name'] ?? $currentRealm['name']); ?>" 
                                       required>
                            </div>

                            <!-- Realm IP -->
                            <div>
                                <label for="realm_ip" class="form-label text-[#f2cf5b] font-bold text-sm 
                                                             tracking-wider block mb-2 
                                                             drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                    <?php echo translate('label_realm_ip', 'Realm IP / Host'); ?>
                                </label>
                                <input type="text" id="realm_ip" name="realm_ip" 
                                       class="w-full px-4 py-3 text-[0.95rem] text-[#e5e7eb] 
                                              bg-[#0a0e16]/80 border border-[#c9a227]/30 rounded-sm 
                                              focus:border-[#f2cf5b] focus:shadow-[0_0_10px_rgba(242,207,82,.2)] 
                                              focus:bg-[#0f141e]/90 outline-none transition-all duration-200 
                                              placeholder:text-[#96aac8]/40"
                                       placeholder="127.0.0.1" 
                                       value="<?php echo htmlspecialchars($_POST['realm_ip'] ?? $currentRealm['address']); ?>" 
                                       required>
                            </div>

                            <!-- Realm Port -->
                            <div>
                                <label for="realm_port" class="form-label text-[#f2cf5b] font-bold text-sm 
                                                               tracking-wider block mb-2 
                                                               drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                    <?php echo translate('label_realm_port', 'Realm Port'); ?>
                                </label>
                                <input type="number" id="realm_port" name="realm_port" 
                                       class="w-full px-4 py-3 text-[0.95rem] text-[#e5e7eb] 
                                              bg-[#0a0e16]/80 border border-[#c9a227]/30 rounded-sm 
                                              focus:border-[#f2cf5b] focus:shadow-[0_0_10px_rgba(242,207,82,.2)] 
                                              focus:bg-[#0f141e]/90 outline-none transition-all duration-200 
                                              placeholder:text-[#96aac8]/40"
                                       placeholder="8085" 
                                       value="<?php echo htmlspecialchars($_POST['realm_port'] ?? $currentRealm['port']); ?>" 
                                       required>
                            </div>

                            <!-- Realm Logo Upload -->
                            <div>
                                <label for="realm_logo" class="form-label text-[#f2cf5b] font-bold text-sm 
                                                               tracking-wider block mb-2 
                                                               drop-shadow-[0_0_12px_rgba(201,162,39,.15),0_2px_4px_rgba(0,0,0,.8)]">
                                    <?php echo translate('label_realm_logo', 'Realm Logo'); ?>
                                </label>
                                <div class="border-2 border-dashed border-[#c9a227]/20 
                                            bg-[#0a0e16]/50 hover:border-[#c9a227]/40 
                                            hover:bg-[#0f141e]/70 cursor-pointer transition-all duration-300 
                                            p-8 text-center rounded-sm" 
                                     id="uploadArea">
                                    <input type="file" id="realm_logo" name="realm_logo" 
                                           class="absolute w-px h-px p-0 -m-px overflow-hidden clip-[rect(0,0,0,0)] border-0" 
                                           accept="image/png,image/svg+xml,image/jpeg,image/webp">
                                    <div id="uploadPlaceholder">
                                        <i class="fas fa-cloud-upload-alt text-3xl text-[#c9a227]/40 block mb-2"></i>
                                        <p class="text-sm text-gray-400"><?php echo translate('placeholder_realm_logo', 'Click or drag to upload a new logo'); ?></p>
                                        <p class="text-xs text-gray-500 mt-1">PNG, SVG, JPG, or WebP (max 2MB)</p>
                                    </div>
                                    <div id="file-name" class="text-sm text-[#f2cf5b] hidden mt-2 font-semibold"></div>
                                </div>
                                <div class="text-[#6a7a8a] text-xs mt-1">
                                    <?php echo translate('note_realm_logo', 'Upload a new logo for your realm. Leave empty to keep the current logo.'); ?>
                                </div>
                            </div>

                            <!-- Info Note -->
                            <div class="text-[#6a7a8a] text-sm p-3 bg-[#0a0e16]/50 border border-[rgba(201,162,39,0.1)] rounded-sm">
                                <i class="fas fa-info-circle text-[#f2cf5b] mr-2"></i>
                                <?php echo translate('note_realm_config', 'This configures the settings for a single realm.'); ?>
                            </div>

                            <!-- Save Button -->
                            <div class="pt-4 border-t border-[rgba(201,162,39,.1)] flex justify-end">
                                <button type="submit" class="btn-clip inline-flex items-center gap-2 px-6 py-3 
                                                             font-extrabold text-xs uppercase tracking-wider
                                                             bg-gradient-to-b from-[#f6d478] via-[#c9a227] to-[#8a6a14] 
                                                             text-[#1a1200] shadow-[inset_0_0_0_1px_rgba(255,255,255,.28),inset_0_-8px_14px_rgba(0,0,0,.25)]
                                                             hover:scale-105 transition-transform duration-200">
                                    <i class="fas fa-save"></i>
                                    <?php echo translate('btn_save_realm', 'Save Realm Configuration'); ?>
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
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('realm_logo');
            const uploadPlaceholder = document.getElementById('uploadPlaceholder');
            const fileName = document.getElementById('file-name');

            if (uploadArea && fileInput) {
                uploadArea.addEventListener('click', () => fileInput.click());

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
                        fileInput.files = e.dataTransfer.files;
                        fileInput.dispatchEvent(new Event('change'));
                    }
                });

                fileInput.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        const file = this.files[0];
                        const maxSize = 2 * 1024 * 1024;
                        const validExtensions = ['png', 'svg', 'jpg', 'jpeg', 'webp'];
                        const ext = file.name.split('.').pop().toLowerCase();

                        if (!validExtensions.includes(ext)) {
                            alert('Invalid file type. Please upload PNG, SVG, JPG, or WebP.');
                            this.value = '';
                            return;
                        }
                        if (file.size > maxSize) {
                            alert('File size exceeds 2MB limit.');
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