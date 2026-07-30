<?php
define('ALLOWED_ACCESS', true);
// Include paths.php using __DIR__ to access $project_root and $base_path
require_once __DIR__ . '/../../../includes/paths.php';
require_once $project_root . 'includes/session.php';
require_once $project_root . 'languages/language.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'moderator'])) {
    header("Location: {$base_path}login");
    exit;
}

$page_class = 'realm';
require_once $project_root . 'includes/header.php';

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
    $logo_path = $defaultLogo; // Default to existing logo

    // Validate realm inputs
    if (empty($realmName)) {
        $errors[] = "❌ " . translate('err_realm_name_required', 'Realm Name is required.');
    }
    if (empty($realmIP)) {
        $errors[] = "❌ " . translate('err_realm_ip_required', 'Realm IP is required.');
    }
    if ($realmPort <= 0 || $realmPort > 65535) {
        $errors[] = "❌ " . translate('err_realm_port_invalid', 'Realm Port must be a valid number (1-65535).');
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

        // Validate file size
        if ($file_size > $max_size) {
            $errors[] = "❌ " . translate('error_realm_logo_too_large', 'Realm logo size exceeds 2MB.');
        }
        // Validate extension
        elseif (!in_array($file_ext, $allowed_exts)) {
            $errors[] = "❌ " . translate('error_invalid_realm_logo_type', 'Invalid file type. Only PNG, SVG, JPG, or WebP allowed.');
        }
        // Validate MIME type
        elseif ($file_ext === 'png' && $file_type !== 'image/png') {
            $errors[] = "❌ " . translate('error_invalid_realm_logo_type', 'Invalid PNG file. MIME type must be image/png.');
        }
        elseif (in_array($file_ext, ['jpg', 'jpeg']) && !in_array($file_type, ['image/jpeg', 'image/jpg'])) {
            $errors[] = "❌ " . translate('error_invalid_realm_logo_type', 'Invalid JPG file. MIME type must be image/jpeg or image/jpg.');
        }
        elseif ($file_ext === 'svg' && $file_type !== 'image/svg+xml') {
            $errors[] = "❌ " . translate('error_invalid_realm_logo_type', 'Invalid SVG file. MIME type must be image/svg+xml.');
        }
        elseif ($file_ext === 'webp' && $file_type !== 'image/webp') {
            $errors[] = "❌ " . translate('error_invalid_realm_logo_type', 'Invalid WebP file. MIME type must be image/webp.');
        }
        else {
            $upload_dir = $project_root . 'img/logos/';
            if (!is_dir($upload_dir) || !is_writable($upload_dir)) {
                $errors[] = "❌ " . translate('error_realm_logo_upload_failed', 'Upload directory is not accessible or writable.');
            } else {
                $new_file_name = 'realm_logo.' . $file_ext;
                $destination = $upload_dir . $new_file_name;

                if (move_uploaded_file($file_tmp, $destination)) {
                    $logo_path = "img/logos/$new_file_name";
                } else {
                    $errors[] = "❌ " . translate('error_realm_logo_upload_failed', 'Failed to upload realm logo. Check server permissions.');
                }
            }
        }
    } elseif (isset($_FILES['realm_logo']) && $_FILES['realm_logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        switch ($_FILES['realm_logo']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errors[] = "❌ " . translate('error_realm_logo_too_large', 'Realm logo file exceeds 2MB limit.');
                break;
            case UPLOAD_ERR_PARTIAL:
                $errors[] = "❌ " . translate('error_realm_logo_upload_failed', 'Realm logo file was only partially uploaded.');
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $errors[] = "❌ " . translate('error_realm_logo_upload_failed', 'Server error: Temporary directory missing.');
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $errors[] = "❌ " . translate('error_realm_logo_upload_failed', 'Server error: Failed to write file to disk.');
                break;
            default:
                $errors[] = "❌ " . sprintf(translate('error_realm_logo_upload_failed', 'Error uploading realm logo: Code %s'), $_FILES['realm_logo']['error']);
        }
    }

   // Save realm config 
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
        $errors[] = "❌ " . sprintf(
            translate('err_config_dir_not_writable', 'Config directory is not writable: %s'),
            $configDir
        );
    } elseif (file_put_contents($realmsFile, $configPhp) === false) {
        $errors[] = "❌ " . sprintf(
            translate('err_write_realm_config', 'Cannot write realm configuration file: %s'),
            $realmsFile
        );
    } else {
        $success = true;
    }
}

}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($langCode); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo translate('page_title_realm', 'Realm Configuration'); ?></title>
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <!-- Roboto font -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=block" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/admin/settings/realm.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/admin/admin_sidebar.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/admin/settings/settings_navbar.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Admin Sidebar -->
            <?php include $project_root . 'includes/admin_sidebar.php'; ?>
            
            <!-- Main Content with Settings Navbar -->
            <main class="col-md-10 main-content">
                <?php include $project_root . 'pages/admin/settings/settings_navbar.php'; ?>
                <div class="content">
                    <h2><?php echo translate('section_realm_config', 'Realm Configuration'); ?></h2>

                    <?php if (!empty($errors)): ?>
                        <div class="error-box mb-3 col-md-6 mx-auto">
                            <strong><?php echo translate('err_fix_errors', 'Please fix the following errors:'); ?></strong>
                            <?php foreach ($errors as $err): ?>
                                <div class="db-status">
                                    <span class="db-status-icon db-status-error">❌</span>
                                    <span class="error"><?php echo htmlspecialchars($err); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="success-box mb-3 col-md-6 mx-auto">
                            <span class="db-status-icon db-status-success">✔</span>
                            <span class="success"><?php echo translate('msg_realm_saved', 'Realm configuration saved successfully!'); ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data">
                        <div class="mb-3 col-md-6 mx-auto">
                            <label for="realm_name" class="form-label"><?php echo translate('label_realm_name', 'Realm Name'); ?></label>
                            <input type="text" class="form-control" id="realm_name" name="realm_name" placeholder="<?php echo translate('placeholder_realm_name', 'Enter realm name'); ?>" value="<?php echo htmlspecialchars($_POST['realm_name'] ?? $currentRealm['name']); ?>" required>
                        </div>
                        <div class="mb-3 col-md-6 mx-auto">
                            <label for="realm_ip" class="form-label"><?php echo translate('label_realm_ip', 'Realm IP / Host'); ?></label>
                            <input type="text" class="form-control" id="realm_ip" name="realm_ip" placeholder="127.0.0.1" value="<?php echo htmlspecialchars($_POST['realm_ip'] ?? $currentRealm['address']); ?>" required>
                        </div>
                        <div class="mb-3 col-md-6 mx-auto">
                            <label for="realm_port" class="form-label"><?php echo translate('label_realm_port', 'Realm Port'); ?></label>
                            <input type="number" class="form-control" id="realm_port" name="realm_port" placeholder="8085" value="<?php echo htmlspecialchars($_POST['realm_port'] ?? $currentRealm['port']); ?>" required>
                        </div>
                        <div class="mb-3 col-md-6 mx-auto">
                            <label for="realm_logo" class="form-label"><?php echo translate('label_realm_logo', 'Realm Logo'); ?></label>
                            <div class="custom-file-upload">
                                <input type="file" id="realm_logo" name="realm_logo" accept="image/png,image/svg+xml,image/jpeg,image/webp">
                                <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('realm_logo').click();"><?php echo translate('btn_choose_file', 'Choose File'); ?></button>
                                <div class="file-name" id="file-name-realm"><?php echo translate('placeholder_realm_logo', 'Upload a PNG, SVG, JPG, or WebP image (max 2MB) for the realm logo.'); ?></div>
                            </div>
                        </div>
                        <div class="form-text mb-3"><?php echo translate('note_realm_config', 'Note: This configures the settings for a single realm.'); ?></div>
                        <button type="submit" class="btn btn-primary"><?php echo translate('btn_save_realm', 'Save Realm Configuration'); ?></button>
                    </form>
                </div>
            </main>
        </div>
    </div>
    <?php require_once $project_root . 'includes/footer.php'; ?>
    <script>
        // Update file name display when a file is selected
        document.getElementById('realm_logo').addEventListener('change', function() {
            const fileName = this.files.length > 0 ? this.files[0].name : '<?php echo translate('placeholder_realm_logo', 'Upload a PNG, SVG, JPG, or WebP image (max 2MB) for the realm logo.'); ?>';
            document.getElementById('file-name-realm').textContent = fileName;
        });
    </script>
</body>
</html>