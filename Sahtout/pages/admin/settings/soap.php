<?php
define('ALLOWED_ACCESS', true);
// Include paths.php using __DIR__ to access $project_root and $base_path
require_once __DIR__ . '/../../../includes/paths.php';
require_once $project_root . 'includes/session.php';
require_once $project_root . 'languages/language.php';

// Check if user is logged in and has admin/moderator role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'moderator'])) {
    header("Location: {$base_path}login");
    exit;
}

// Set page class for admin navigation highlighting
$page_class = 'admin_settings';
$sub_page_class = 'soap';

// Include the main site header BEFORE any HTML output
require_once $project_root . 'includes/header.php';

$errors = [];
$success = false;
$soapFile = $project_root . 'includes/soap_config.php';

// Load existing configuration if available
$soapConfig = [
    'url' => '',
    'username' => '',
    'password' => ''
];

if (file_exists($soapFile)) {
    require_once $soapFile;
    $soapConfig = [
        'url' => $soap_url ?? '',
        'username' => $gm_username ?? '',
        'password' => $soap_password ?? ''
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $soapUrl = trim($_POST['soap_url'] ?? '');
    $gmUsername = trim($_POST['gm_username'] ?? '');
    $soapPassword = trim($_POST['soap_password'] ?? '');

    // Validate inputs
    if (empty($soapUrl)) {
        $errors[] = "❌ " . translate('err_soap_url_required', 'SOAP URL is required.');
    }
    if (empty($gmUsername)) {
        $errors[] = "❌ " . translate('err_gm_username_required', 'GM Username is required.');
    }
    if (empty($soapPassword)) {
        $errors[] = "❌ " . translate('err_soap_password_required', 'SOAP Password is required.');
    }

    // Verify GM account exists in Auth DB
    if (empty($errors)) {
        // Load database configuration
        require_once $project_root . 'includes/config.php';
        
        // Check if account exists and has GM level 3
        $stmt = $auth_db->prepare("SELECT id, username FROM account WHERE username = ?");
        if ($stmt) {
            $stmt->bind_param("s", $gmUsername);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $errors[] = "❌ " . sprintf(
                    translate('err_soap_account_not_found', 'Account \'%s\' does not exist in Auth DB.'),
                    htmlspecialchars($gmUsername)
                );
            } else {
                $account = $result->fetch_assoc();
                $accountId = $account['id'];
                
                // Check GM level
                $gmStmt = $auth_db->prepare("SELECT gmlevel FROM account_access WHERE id = ? AND RealmID = -1");
                if ($gmStmt) {
                    $gmStmt->bind_param("i", $accountId);
                    $gmStmt->execute();
                    $gmResult = $gmStmt->get_result();
                    
                    if ($gmResult->num_rows === 0) {
                        $errors[] = "❌ " . sprintf(
                            translate('err_soap_gm_level', 'Account \'%s\' exists but is not GM level 3.'),
                            htmlspecialchars($gmUsername)
                        );
                    } else {
                        $gmData = $gmResult->fetch_assoc();
                        if ((int)$gmData['gmlevel'] < 3) {
                            $errors[] = "❌ " . sprintf(
                                translate('err_soap_gm_level', 'Account \'%s\' exists but is not GM level 3.'),
                                htmlspecialchars($gmUsername)
                            );
                        }
                    }
                    $gmStmt->close();
                }
            }
            $stmt->close();
        } else {
            $errors[] = "❌ " . translate('err_database_prepare', 'Database error occurred.');
        }
    }

    // Save SOAP configuration
    if (empty($errors)) {
        $configPhp = "<?php\n";
        $configPhp .= "if (!defined('ALLOWED_ACCESS')) { exit('Forbidden'); }\n\n";
        $configPhp .= '$soap_url = ' . var_export($soapUrl, true) . ";\n";
        $configPhp .= '$gm_username = ' . var_export($gmUsername, true) . ";\n";
        $configPhp .= '$soap_password = ' . var_export($soapPassword, true) . ";\n";

        $configDir = dirname($soapFile);

        if (!is_writable($configDir)) {
            $errors[] = "❌ " . sprintf(
                translate('err_config_dir_not_writable', 'Config directory is not writable: %s'),
                $configDir
            );
        } elseif (file_put_contents($soapFile, $configPhp) === false) {
            $errors[] = "❌ " . sprintf(
                translate('err_write_soap_config', 'Cannot write SOAP configuration file: %s'),
                $soapFile
            );
        } else {
            $success = true;
            // Update config array for display
            $soapConfig = [
                'url' => $soapUrl,
                'username' => $gmUsername,
                'password' => $soapPassword
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($langCode ?? 'en'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo translate('page_title_soap', 'SOAP Configuration'); ?></title>
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <!-- Roboto font -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=block" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Admin CSS files -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/admin/settings/soap.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/admin/admin_sidebar.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/admin/settings/settings_navbar.css">
    
    <style>
        /* Fix for z-index issues */
        .main-content {
            position: relative;
            z-index: 1;
        }
        .admin-sidebar {
            z-index: 100;
        }
        header {
            z-index: 999;
            position: sticky;
            top: 0;
        }
        .container-fluid {
            padding-top: 0;
        }
        .row {
            margin-top: 0;
        }
        /* Ensure buttons are clickable */
        .btn, button, input[type="submit"] {
            position: relative;
            z-index: 2;
            cursor: pointer !important;
            pointer-events: auto !important;
        }
        /* Fix overlapping elements */
        .content {
            position: relative;
            z-index: 1;
            padding-bottom: 50px;
        }
        /* Ensure dropdown menus appear above content */
        .dropdown-menu, .lang-options {
            z-index: 1000 !important;
        }
    </style>
</head>
<body>
    <!-- Main Content Area - Header is already included above -->
    <div class="container-fluid">
        <div class="row">
            <!-- Admin Sidebar -->
            <?php include $project_root . 'includes/admin_sidebar.php'; ?>
            
            <!-- Main Content with Settings Navbar -->
            <main class="col-md-10 main-content">
                <?php include $project_root . 'pages/admin/settings/settings_navbar.php'; ?>
                <div class="content">
                    <h2><?php echo translate('section_soap_config', 'SOAP Configuration'); ?></h2>

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
                            <span class="success"><?php echo translate('msg_soap_saved', 'SOAP configuration saved successfully! GM account verified.'); ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="post" style="position: relative; z-index: 2;">
                        <div class="mb-3 col-md-6 mx-auto">
                            <label for="soap_url" class="form-label"><?php echo translate('label_soap_url', 'SOAP URL'); ?></label>
                            <input type="text" class="form-control" id="soap_url" name="soap_url" 
                                   placeholder="http://127.0.0.1:7878" 
                                   value="<?php echo htmlspecialchars($_POST['soap_url'] ?? $soapConfig['url'] ?? ''); ?>" required>
                            <div class="form-text"><?php echo translate('info_soap_url', 'Format: http://ip:port (e.g., http://127.0.0.1:7878)'); ?></div>
                        </div>
                        
                        <div class="mb-3 col-md-6 mx-auto">
                            <label for="gm_username" class="form-label"><?php echo translate('label_gm_username', 'GM Account Username'); ?></label>
                            <input type="text" class="form-control" id="gm_username" name="gm_username" 
                                   placeholder="<?php echo translate('placeholder_gm_level3', 'Must be GM level 3'); ?>" 
                                   value="<?php echo htmlspecialchars($_POST['gm_username'] ?? $soapConfig['username'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3 col-md-6 mx-auto">
                            <label for="soap_password" class="form-label"><?php echo translate('label_soap_password', 'SOAP Password'); ?></label>
                            <input type="password" class="form-control" id="soap_password" name="soap_password" 
                                   placeholder="<?php echo translate('placeholder_soap_pass', 'SOAP password = Account password'); ?>" 
                                   value="<?php echo htmlspecialchars($_POST['soap_password'] ?? $soapConfig['password'] ?? ''); ?>" required>
                        </div>

                        <div class="info-box mb-3 col-md-6 mx-auto" style="position: relative; z-index: 2;">
                            <strong><?php echo translate('important_steps', 'Important Steps:'); ?></strong>
                            <ul>
                                <li><?php echo translate('info_soap_li1', 'Make sure the GM account exists in your Auth DB and has GM level 3 in <code>account_access</code> with <code>RealmID = -1</code>.'); ?></li>
                                <li><?php echo translate('info_soap_li2', 'Open your <code>worldserver.conf</code> file and set: <strong>SOAP.Enabled = 1</strong>'); ?></li>
                                <li><?php echo translate('info_soap_li3', 'Ensure the SOAP port in <code>soap_url</code> is correct and accessible.'); ?></li>
                            </ul>
                        </div>

                        <button type="submit" class="btn btn-primary" style="position: relative; z-index: 2; cursor: pointer !important;">
                            <?php echo translate('btn_save_verify_gm', 'Save & Verify GM'); ?>
                        </button>
                    </form>
                </div>
            </main>
        </div>
    </div>
    <?php require_once $project_root . 'includes/footer.php'; ?>
    
    <script>
        // Ensure buttons are clickable after page load
        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('button, input[type="submit"], .btn');
            buttons.forEach(function(btn) {
                btn.style.pointerEvents = 'auto';
                btn.style.cursor = 'pointer';
                btn.style.zIndex = '2';
            });
            
            // Fix for any overlay issues
            const overlays = document.querySelectorAll('.overlay, .dropdown-backdrop');
            overlays.forEach(function(overlay) {
                overlay.style.zIndex = '999';
            });
        });
    </script>
</body>
</html>