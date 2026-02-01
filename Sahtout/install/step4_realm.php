<?php 
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../includes/paths.php'; // Include paths.php
include __DIR__ . '/header.inc.php';

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
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {margin:0;padding:0;font-family:'Cinzel', serif;background:#0a0a0a;color:#f0e6d2;}
        .overlay {background: rgba(0,0,0,0.9); inset:0; display:flex; align-items:center; justify-content:center; padding:20px;}
        .container {text-align:center; max-width:700px; width:100%; min-height:70vh; max-height:90vh; overflow-y:auto; padding:30px 20px; border:2px solid #6b4226; background: rgba(20,10,5,0.95); border-radius:12px; box-shadow:0 0 30px #6b4226;}
        h1 {font-size:2.5em; margin-bottom:20px; color:#d4af37; text-shadow:0 0 10px #000;}
        label {display:block; text-align:left; margin:10px 0 5px;}
        input {width:100%; padding:10px; border-radius:6px; border:1px solid #6b4226; background:rgba(30,15,5,0.9); color:#f0e6d2;}
        .btn {display:inline-block; padding:12px 30px; font-size:1.2em; font-weight:bold; color:#fff; background: linear-gradient(135deg,#6b4226,#a37e2c); border:none; border-radius:8px; cursor:pointer; text-decoration:none; box-shadow:0 0 15px #a37e2c; transition:0.3s ease; margin-top:15px;}
        .btn:hover {background: linear-gradient(135deg,#a37e2c,#d4af37); box-shadow:0 0 25px #d4af37;}
        .error-box {background:rgba(100,0,0,0.4); padding:10px; border:1px solid #ff4040; border-radius:6px; margin-bottom:20px; text-align:left;}
        .error {color:#ff4040; font-weight:bold; margin-top:5px;}
        .success {color:#7CFC00; font-weight:bold; margin-top:15px;}
        .section-title {margin-top:30px; font-size:1.5em; color:#d4af37; text-decoration: underline;}
        .db-status {display: flex; align-items: center; margin: 5px 0;}
        .db-status-icon {margin-right: 10px; font-size: 1.2em;}
        .db-status-error {color: #ff4040;}
        .db-status-success {color: #7CFC00;}
        .note {font-size:0.9em; color:#a37e2c; margin-top:10px; text-align:left;}
        .custom-file-upload {display: inline-block; width: 100%; text-align: center;}
        .custom-file-upload input[type="file"] {display: none;}
        .custom-file-upload .btn {
            width: 200px; padding: 10px; text-align: center;
            background: #0b71e6ff; border: 1px solid #6b4226; color: #fff;
            border-radius: 6px; cursor: pointer; font-family: 'Cinzel', serif; font-size: 1em;
            box-shadow: none; /* Override default .btn shadow */
        }
        .custom-file-upload .btn:hover {
            background: #0a5bb4; /* Slightly darker blue on hover */
            box-shadow: 0 0 15px #a37e2c; /* Match theme hover effect */
        }
        .custom-file-upload .file-name {
            margin-top: 5px; color: #a37e2c; font-size: 0.9em; text-align: center;
        }
    </style>
</head>
<body>
<div class="overlay">
    <div class="container">
        <h1><?php echo translate('forge_name', '⚔️ RealmForge Setup'); ?></h1>
        <h2 class="section-title"><?php echo translate('step4_title', 'Phase 4: realm Setup'); ?></h2>

        <?php if (!empty($errors)): ?>
            <div class="error-box">
                <strong><?php echo translate('err_fix_errors_realm', 'Resolve the following issues:'); ?></strong>
                <?php foreach ($errors as $err): ?>
                    <div class="db-status">
                        <span class="db-status-icon db-status-error">❌</span>
                        <span class="error"><?php echo htmlspecialchars($err); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif ($success): ?>
            <div class="db-status">
                <span class="db-status-icon db-status-success">✔</span>
                <span class="success"><?php echo translate('msg_realm_saved', 'realm configuration stored successfully!'); ?></span>
            </div>
            <a href="<?php echo $base_path; ?>install/step5_mail" class="btn"><?php echo translate('btn_proceed_to_mail', 'Advance to Email Configuration ➡️'); ?></a>
        <?php endif; ?>

        <?php if (!$success): ?>
            <form method="post" enctype="multipart/form-data">
                <div class="section-title"><?php echo translate('section_realm_config', 'realm Configuration'); ?></div>
                <label for="realm_name"><?php echo translate('label_realm_name', 'realm Name'); ?></label>
                <input type="text" id="realm_name" name="realm_name" placeholder="<?php echo translate('placeholder_realm_name', 'Enter realm name'); ?>" value="<?php echo htmlspecialchars($_POST['realm_name'] ?? 'Sahtout realm'); ?>" required>

                <label for="realm_ip"><?php echo translate('label_realm_ip', 'realm Address / Host'); ?></label>
                <input type="text" id="realm_ip" name="realm_ip" placeholder="127.0.0.1" value="<?php echo htmlspecialchars($_POST['realm_ip'] ?? '127.0.0.1'); ?>" required>

                <label for="realm_port"><?php echo translate('label_realm_port', 'realm Port'); ?></label>
                <input type="number" id="realm_port" name="realm_port" placeholder="8085" value="<?php echo htmlspecialchars($_POST['realm_port'] ?? '8085'); ?>" required>

                <label for="realm_logo"><?php echo translate('label_realm_logo', 'realm Emblem'); ?></label>
                <div class="custom-file-upload">
                    <input type="file" id="realm_logo" name="realm_logo" accept="image/png,image/svg+xml,image/jpeg,image/webp">
                    <button type="button" class="btn" onclick="document.getElementById('realm_logo').click();"><?php echo translate('btn_choose_file', 'Select Emblem'); ?></button>
                    <div class="file-name" id="file-name-realm"><?php echo translate('placeholder_realm_logo', 'Upload a PNG, SVG, JPG, or WebP emblem (max 2MB).'); ?></div>
                </div>

                <p class="note"><?php echo translate('note_realm_config', 'Note: This configures the realm settings.'); ?></p>
                <button type="submit" class="btn"><?php echo translate('btn_save_realm', 'Store realm Configuration'); ?></button>
            </form>
        <?php endif; ?>
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