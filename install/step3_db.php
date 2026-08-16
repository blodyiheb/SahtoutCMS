<?php
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../includes/paths.php';
require_once __DIR__ . '/header.inc.php';
require_once __DIR__ . '/languages/language.php';

// Set current step for progress stepper
$current_step = 3;

$errors   = [];
$success  = false;
$dbStatus = [];

// Robust path handling for files that might not exist yet
$cfgDir = realpath(__DIR__ . '/../includes');
if (!$cfgDir) $cfgDir = __DIR__ . '/../includes';

$configFile    = $cfgDir . '/config.php';
$configCapFile = $cfgDir . '/config.cap.php';

$default_site_key   = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI';
$default_secret_key = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Define default DB groups so they are available on GET requests
$dbGroups = [
    'auth'  => ['label' => translate('db_auth', 'Auth DB')],
    'world' => ['label' => translate('db_world', 'World DB')],
    'char'  => ['label' => translate('db_char', 'Char DB')],
    'site'  => ['label' => translate('db_site', 'Site DB')],
];

function makeConnection(array $c): array {
    $conn  = null;
    $error = '';
    try {
        $conn = new mysqli($c['host'], $c['user'], $c['pass'], $c['name'], $c['port']);
        if ($conn->connect_error) $error = $conn->connect_error;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
    return [$conn, $error];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $recaptcha_enabled    = isset($_POST['recaptcha_enabled']) ? 1 : 0;
    $recaptcha_site_key   = $recaptcha_enabled ? trim($_POST['recaptcha_site_key'] ?? '') : '';
    $recaptcha_secret_key = $recaptcha_enabled ? trim($_POST['recaptcha_secret_key'] ?? '') : '';

    if ($recaptcha_enabled && empty($recaptcha_site_key))  $recaptcha_site_key   = $default_site_key;
    if ($recaptcha_enabled && empty($recaptcha_secret_key))$recaptcha_secret_key = $default_secret_key;

    if ($recaptcha_enabled && (empty($recaptcha_site_key) || empty($recaptcha_secret_key))) {
        $errors[] = translate('err_recaptcha_keys_required', 'reCAPTCHA Site Key and Secret Key are required when reCAPTCHA is enabled.');
    }

    // Overwrite with POST data
    $dbGroups = [
        'auth' => [
            'label' => translate('db_auth', 'Auth DB'),
            'name'  => trim($_POST['db_auth_name'] ?? 'acore_auth'),
            'host'  => trim($_POST['db_auth_host'] ?? ''),
            'port'  => trim($_POST['db_auth_port'] ?? '3306'),
            'user'  => trim($_POST['db_auth_user'] ?? ''),
            'pass'  => $_POST['db_auth_pass'] ?? '',
        ],
        'world' => [
            'label' => translate('db_world', 'World DB'),
            'name'  => trim($_POST['db_world_name'] ?? 'acore_world'),
            'host'  => trim($_POST['db_world_host'] ?? ''),
            'port'  => trim($_POST['db_world_port'] ?? '3306'),
            'user'  => trim($_POST['db_world_user'] ?? ''),
            'pass'  => $_POST['db_world_pass'] ?? '',
        ],
        'char' => [
            'label' => translate('db_char', 'Char DB'),
            'name'  => trim($_POST['db_char_name'] ?? 'acore_characters'),
            'host'  => trim($_POST['db_char_host'] ?? ''),
            'port'  => trim($_POST['db_char_port'] ?? '3306'),
            'user'  => trim($_POST['db_char_user'] ?? ''),
            'pass'  => $_POST['db_char_pass'] ?? '',
        ],
        'site' => [
            'label' => translate('db_site', 'Site DB'),
            'name'  => trim($_POST['db_site_name'] ?? 'sahtout_site'),
            'host'  => trim($_POST['db_site_host'] ?? ''),
            'port'  => trim($_POST['db_site_port'] ?? '3306'),
            'user'  => trim($_POST['db_site_user'] ?? ''),
            'pass'  => $_POST['db_site_pass'] ?? '',
        ],
    ];

    foreach ($dbGroups as $key => $g) {
        if (empty($g['host'])) $errors[] = translate('err_host_required', '[%s] Host is required', $g['label']);
        if (empty($g['user'])) $errors[] = translate('err_user_required', '[%s] Username is required', $g['label']);
        if (empty($g['port']) || !is_numeric($g['port']) || $g['port'] < 1 || $g['port'] > 65535) {
            $errors[] = translate('err_port_invalid', '[%s] Port must be 1–65535', $g['label']);
        }
        if (empty($g['name'])) $errors[] = translate('err_dbname_required', '[%s] Database name is required', $g['label']);
    }

    if (empty($errors)) {
        foreach ($dbGroups as $key => $g) {
            [$conn, $connError] = makeConnection($g);
            $status = ['success' => false, 'message' => ''];

            if ($connError) {
                $status['message'] = translate('err_connection_failed', 'Connection failed: %s', $connError);
            } else {
                $required = match ($key) {
                    'auth'  => ['account', 'realmcharacters'],
                    'world' => ['creature_template', 'item_template'],
                    'char'  => ['characters', 'character_inventory'],
                    default => [],
                };

                $missing = [];
                foreach ($required as $tbl) {
                    $res = $conn->query("SHOW TABLES LIKE '{$conn->real_escape_string($tbl)}'");
                    if (!$res || $res->num_rows === 0) $missing[] = $tbl;
                    $res?->free();
                }

                if ($missing) {
                    $status['message'] = translate('err_missing_tables', 'Missing tables: %s', implode(', ', $missing));
                } else {
                    $status['success'] = true;
                    $status['message'] = translate('msg_db_ok', 'Connected & tables OK');
                }
            }

            $dbStatus[$key] = $status;
            $dbGroups[$key]['_conn'] = $conn;

            if (!$status['success']) {
                $errors[] = "[{$g['label']}] " . $status['message'];
            }
        }
    }

    if (empty($errors)) {
        $cfg = "<?php\nif (!defined('ALLOWED_ACCESS')) exit('Direct access not allowed.');\n\n";
        foreach ($dbGroups as $key => $g) {
            $p = "db_{$key}_";
            // Using var_export is much safer than addslashes for generating PHP code
            $cfg .= "\${$p}host = " . var_export($g['host'], true) . ";\n";
            $cfg .= "\${$p}port = " . var_export($g['port'], true) . ";\n";
            $cfg .= "\${$p}user = " . var_export($g['user'], true) . ";\n";
            $cfg .= "\${$p}pass = " . var_export($g['pass'], true) . ";\n";
            $cfg .= "\${$p}name = " . var_export($g['name'], true) . ";\n\n";
        }

        $cfg .= "\$auth_db  = new mysqli(\$db_auth_host,  \$db_auth_user,  \$db_auth_pass,  \$db_auth_name,  \$db_auth_port);\n";
        $cfg .= "\$world_db = new mysqli(\$db_world_host, \$db_world_user, \$db_world_pass, \$db_world_name, \$db_world_port);\n";
        $cfg .= "\$char_db  = new mysqli(\$db_char_host,  \$db_char_user,  \$db_char_pass,  \$db_char_name,  \$db_char_port);\n";
        $cfg .= "\$site_db  = new mysqli(\$db_site_host,  \$db_site_user,  \$db_site_pass,  \$db_site_name,  \$db_site_port);\n\n";

        $cfg .= "if (\$auth_db->connect_error)  die('Auth DB Connection failed: '  . \$auth_db->connect_error);\n";
        $cfg .= "if (\$world_db->connect_error) die('World DB Connection failed: ' . \$world_db->connect_error);\n";
        $cfg .= "if (\$char_db->connect_error)  die('Char DB Connection failed: '  . \$char_db->connect_error);\n";
        $cfg .= "if (\$site_db->connect_error)  die('Site DB Connection failed: '  . \$site_db->connect_error);\n";
        $cfg .= "?>\n";

        $cap = "<?php\nif (!defined('ALLOWED_ACCESS')) exit('Direct access not allowed.');\n";
        $cap .= "\$recaptcha_enabled    = " . var_export((bool)$recaptcha_enabled, true) . ";\n";
        $cap .= "\$recaptcha_site_key   = " . var_export($recaptcha_site_key, true) . ";\n";
        $cap .= "\$recaptcha_secret_key = " . var_export($recaptcha_secret_key, true) . ";\n";
        $cap .= "define('RECAPTCHA_ENABLED', \$recaptcha_enabled);\n";
        $cap .= "define('RECAPTCHA_SITE_KEY', \$recaptcha_site_key);\n";
        $cap .= "define('RECAPTCHA_SECRET_KEY', \$recaptcha_secret_key);\n";
        $cap .= "?>\n";

        if (!is_writable($cfgDir)) {
            $errors[] = translate('err_config_dir_not_writable', 'Config directory not writable: %s', $cfgDir);
        } else {
            if (file_put_contents($configFile, $cfg) === false) {
                $errors[] = translate('err_failed_write_config', 'Failed to write config.php');
            }
            if (file_put_contents($configCapFile, $cap) === false) {
                $errors[] = translate('err_failed_write_cap', 'Failed to write config.cap.php');
            }
            if (empty($errors)) $success = true;
        }
    }

    foreach ($dbGroups as $g) {
        if (isset($g['_conn']) && $g['_conn'] instanceof mysqli) {
            $g['_conn']->close();
        }
    }
}

$db_icons = [
    'auth' => 'fa-shield-halved',
    'world' => 'fa-globe',
    'char' => 'fa-users',
    'site' => 'fa-database'
];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($langCode ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= translate('installer_title', 'SahtoutCMS Installer') ?> - <?= translate('step3_title', 'Step 3: Database & reCAPTCHA Setup') ?></title>
    
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
        
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #d97706; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #b45309; }

        /* Main content wrapper */
        .main-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding-top: 0px;
            padding-bottom: 80px;
        }

        .content-container {
            max-width: 1100px;
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
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            toggleRecaptchaFields();
        });

        function toggleRecaptchaFields() {
            const f = document.getElementById('recaptcha-fields');
            const e = document.getElementById('recaptcha_enabled').checked;
            if (e) {
                f.classList.remove('hidden');
            } else {
                f.classList.add('hidden');
            }
        }
    </script>
</head>
<body class="text-slate-200">

<div class="main-wrapper">
    <!-- Progress Stepper -->
    <?php include __DIR__ . '/progress_stepper.inc.php'; ?>

    <div class="content-container flex-grow">
        <div class="bg-slate-900/60 backdrop-blur-xl border border-slate-700/50 rounded-2xl shadow-2xl p-6 md:p-10 relative overflow-hidden">
            
            <!-- Decorative Corner Elements -->
            <div class="absolute top-0 left-0 w-16 h-16 border-t-2 border-l-2 border-gold-500/30 rounded-tl-2xl pointer-events-none"></div>
            <div class="absolute bottom-0 right-0 w-16 h-16 border-b-2 border-r-2 border-gold-500/30 rounded-br-2xl pointer-events-none"></div>

            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gold-500/10 border border-gold-500/30 rounded-full mb-4">
                    <i class="fas fa-database text-3xl text-gold-400"></i>
                </div>
                <h1 class="font-cinzel text-3xl md:text-4xl font-bold bg-gradient-to-b from-amber-100 to-gold-500 bg-clip-text text-transparent">
                    <?= translate('step3_title', 'Step 3: Database & reCAPTCHA Setup') ?>
                </h1>
                <p class="text-slate-400 mt-2 text-sm"><?= translate('step3_description', 'Configure your database connections and security settings.') ?></p>
            </div>

            <!-- Status Messages -->
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($dbStatus)): ?>
                <div class="mb-8 space-y-2">
                    <?php foreach ($dbGroups as $key => $g): ?>
                        <?php $st = $dbStatus[$key] ?? ['success' => false, 'message' => translate('unknown_status', 'Unknown status')]; ?>
                        <div class="flex items-center justify-between p-3 rounded-lg <?= $st['success'] ? 'bg-emerald-900/20 border border-emerald-500/30' : 'bg-red-900/20 border border-red-500/30' ?>">
                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                <i class="fas fa-<?= $db_icons[$key] ?? 'database' ?> text-gold-400 flex-shrink-0"></i>
                                <span class="text-slate-200 font-medium text-sm flex-shrink-0"><?= htmlspecialchars($g['label']) ?></span>
                                <span class="<?= $st['success'] ? 'text-emerald-400' : 'text-red-400' ?> font-bold text-sm flex-shrink-0">
                                    <?= $st['success'] ? '✅ ' . translate('success', 'Success') : '❌ ' . translate('error', 'Error') ?>
                                </span>
                            </div>
                            <span class="text-slate-300 text-sm break-words text-right max-w-[60%]" title="<?= htmlspecialchars($st['message']) ?>">
                                <?= htmlspecialchars($st['message']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Errors -->
            <?php if (!empty($errors)): ?>
                <div class="bg-red-900/30 border border-red-500/40 text-red-200 p-4 mb-8 rounded-lg">
                    <div class="flex items-center gap-2 mb-3 font-bold text-red-300">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?= translate('err_fix_errors', 'Please fix the following errors:') ?>
                        <span class="ml-auto text-xs bg-red-800/50 px-2 py-1 rounded-full">
                            <?= count($errors) ?> <?= translate('errors_found', 'errors found') ?>
                        </span>
                    </div>
                    <div class="space-y-2">
                        <?php foreach ($errors as $index => $e): ?>
                            <div class="flex items-start gap-2 bg-red-950/30 p-3 rounded-lg border border-red-500/20">
                                <span class="inline-flex items-center justify-center bg-red-800/50 text-red-300 font-mono text-xs w-6 h-6 rounded-full flex-shrink-0 mt-0.5"><?= $index + 1 ?></span>
                                <span class="text-sm leading-relaxed break-words text-red-100/90"><?= htmlspecialchars($e) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Success State -->
            <?php if ($success): ?>
                <div class="bg-emerald-900/30 border border-emerald-500/40 text-emerald-200 p-5 mb-8 rounded-lg flex items-center gap-3">
                    <i class="fas fa-check-circle text-emerald-400 text-2xl"></i>
                    <span class="font-medium"><?= translate('msg_config_saved', 'All databases connected successfully! Config and reCAPTCHA files created.') ?></span>
                </div>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mt-6">
                    <a href="<?= htmlspecialchars($base_path ?? '') ?>install/step4_realm" class="inline-flex items-center px-8 py-3 bg-gold-500 hover:bg-gold-400 text-slate-900 font-bold rounded-lg shadow-lg shadow-gold-600/20 transition-all duration-300 transform hover:scale-105">
                        <?= translate('btn_proceed_to_realm', 'Proceed to Step 4 Realm configuration') ?>
                        <i class="fas fa-arrow-right ml-3"></i>
                    </a>
                    <a href="<?= htmlspecialchars($base_path ?? '') ?>install/step2_check" class="inline-flex items-center px-6 py-3 bg-slate-700/50 hover:bg-slate-700/70 text-slate-300 font-semibold rounded-lg transition-all duration-300 border border-slate-600/30">
                        <i class="fas fa-arrow-left mr-2"></i>
                        <?= translate('btn_go_back', 'Go Back') ?>
                    </a>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <?php if (!$success): ?>
                <form method="post" class="space-y-8">
                    
                    <!-- DB Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach (['auth','world','char','site'] as $type): 
                            $icon = $db_icons[$type] ?? 'fa-database';
                            $label = $dbGroups[$type]['label'] ?? translate("db_{$type}", ucfirst($type) . ' DB');
                            
                            // Set default database names
                            $default_db_name = match($type) {
                                'auth' => 'acore_auth',
                                'world' => 'acore_world',
                                'char' => 'acore_characters',
                                'site' => 'sahtout_site',
                                default => ''
                            };
                        ?>
                            <div class="bg-slate-800/40 border border-slate-700/50 rounded-xl p-5 shadow-lg hover:border-gold-500/30 transition-all duration-300 flex flex-col">
                                <h3 class="font-cinzel text-gold-400 font-bold text-lg mb-4 flex items-center gap-2 border-b border-slate-700/50 pb-3">
                                    <i class="fas <?= $icon ?>"></i>
                                    <?= $label ?>
                                </h3>
                                
                                <div class="space-y-4 flex-grow">
                                    <!-- Host -->
                                    <div>
                                        <label for="db_<?= $type ?>_host" class="block text-xs font-medium text-slate-400 mb-1 uppercase tracking-wider"><?= translate('label_db_host', 'Host') ?></label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 pointer-events-none">
                                                <i class="fas fa-server text-sm"></i>
                                            </span>
                                            <input id="db_<?= $type ?>_host" type="text" name="db_<?= $type ?>_host"
                                                   value="<?= htmlspecialchars($_POST["db_{$type}_host"] ?? 'localhost') ?>"
                                                   class="w-full bg-slate-900/60 border border-slate-600 text-slate-200 rounded-lg pl-10 pr-4 py-2.5 focus:ring-2 focus:ring-gold-500 focus:border-gold-500 transition-all placeholder-slate-500 text-sm"
                                                   required placeholder="localhost">
                                        </div>
                                    </div>

                                    <!-- Port & Name Row -->
                                    <div class="grid grid-cols-3 gap-3">
                                        <div class="col-span-1">
                                            <label for="db_<?= $type ?>_port" class="block text-xs font-medium text-slate-400 mb-1 uppercase tracking-wider"><?= translate('label_db_port', 'Port') ?></label>
                                            <input id="db_<?= $type ?>_port" type="text" name="db_<?= $type ?>_port"
                                                   value="<?= htmlspecialchars($_POST["db_{$type}_port"] ?? '3306') ?>"
                                                   class="w-full bg-slate-900/60 border border-slate-600 text-slate-200 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-gold-500 focus:border-gold-500 transition-all text-sm"
                                                   required>
                                        </div>
                                        <div class="col-span-2">
                                            <label for="db_<?= $type ?>_name" class="block text-xs font-medium text-slate-400 mb-1 uppercase tracking-wider"><?= translate('label_db_name', 'Database') ?></label>
                                            <input id="db_<?= $type ?>_name" type="text" name="db_<?= $type ?>_name"
                                                   value="<?= htmlspecialchars($_POST["db_{$type}_name"] ?? $default_db_name) ?>"
                                                   class="w-full bg-slate-900/60 border border-slate-600 text-slate-200 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-gold-500 focus:border-gold-500 transition-all text-sm"
                                                   required>
                                        </div>
                                    </div>

                                    <!-- User -->
                                    <div>
                                        <label for="db_<?= $type ?>_user" class="block text-xs font-medium text-slate-400 mb-1 uppercase tracking-wider"><?= translate('label_db_user', 'Username') ?></label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 pointer-events-none">
                                                <i class="fas fa-user text-sm"></i>
                                            </span>
                                            <input id="db_<?= $type ?>_user" type="text" name="db_<?= $type ?>_user"
                                                   value="<?= htmlspecialchars($_POST["db_{$type}_user"] ?? '') ?>"
                                                   class="w-full bg-slate-900/60 border border-slate-600 text-slate-200 rounded-lg pl-10 pr-4 py-2.5 focus:ring-2 focus:ring-gold-500 focus:border-gold-500 transition-all placeholder-slate-500 text-sm"
                                                   required placeholder="root">
                                        </div>
                                    </div>

                                    <!-- Password -->
                                    <div>
                                        <label for="db_<?= $type ?>_pass" class="block text-xs font-medium text-slate-400 mb-1 uppercase tracking-wider"><?= translate('label_db_pass', 'Password') ?></label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 pointer-events-none">
                                                <i class="fas fa-lock text-sm"></i>
                                            </span>
                                            <input id="db_<?= $type ?>_pass" type="password" name="db_<?= $type ?>_pass"
                                                   value="<?= htmlspecialchars($_POST["db_{$type}_pass"] ?? '') ?>"
                                                   class="w-full bg-slate-900/60 border border-slate-600 text-slate-200 rounded-lg pl-10 pr-4 py-2.5 focus:ring-2 focus:ring-gold-500 focus:border-gold-500 transition-all placeholder-slate-500 text-sm"
                                                   placeholder="••••••••">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- reCAPTCHA Section -->
                    <div class="bg-slate-800/40 border border-slate-700/50 rounded-xl p-6 shadow-lg">
                        <h2 class="font-cinzel text-gold-400 font-bold text-xl mb-4 flex items-center gap-2">
                            <i class="fas fa-shield-halved"></i>
                            <?= translate('section_recaptcha', 'reCAPTCHA V2 Configuration') ?>
                        </h2>

                        <div class="flex items-center justify-between mb-4 pb-4 border-b border-slate-700/50">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="recaptcha_enabled" name="recaptcha_enabled" onchange="toggleRecaptchaFields()" class="sr-only peer" <?= isset($_POST['recaptcha_enabled']) ? 'checked' : '' ?>>
                                <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-gold-800/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gold-500"></div>
                                <span class="ml-3 text-sm font-medium text-slate-300"><?= translate('label_recaptcha_enabled', 'Enable reCAPTCHA') ?></span>
                            </label>
                            <span class="text-xs font-bold px-2 py-1 rounded <?= isset($_POST['recaptcha_enabled']) ? 'bg-emerald-900/50 text-emerald-400' : 'bg-slate-700 text-slate-400' ?>">
                                <?= isset($_POST['recaptcha_enabled']) ? translate('enabled', 'Enabled') : translate('disabled', 'Disabled') ?>
                            </span>
                        </div>

                        <div id="recaptcha-fields" class="<?= isset($_POST['recaptcha_enabled']) ? '' : 'hidden' ?> space-y-4">
                            <div>
                                <label for="recaptcha_site_key" class="block text-xs font-medium text-slate-400 mb-1 uppercase tracking-wider"><?= translate('label_recaptcha_site_key', 'Site Key') ?></label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 pointer-events-none">
                                        <i class="fas fa-key text-sm"></i>
                                    </span>
                                    <input id="recaptcha_site_key" type="text" name="recaptcha_site_key" 
                                           value="<?= htmlspecialchars($_POST['recaptcha_site_key'] ?? '') ?>"
                                           class="w-full bg-slate-900/60 border border-slate-600 text-slate-200 rounded-lg pl-10 pr-4 py-2.5 focus:ring-2 focus:ring-gold-500 focus:border-gold-500 transition-all placeholder-slate-500 text-sm"
                                           placeholder="<?= translate('placeholder_recaptcha_default', 'Leave empty for default') ?>">
                                </div>
                            </div>
                            
                            <div>
                                <label for="recaptcha_secret_key" class="block text-xs font-medium text-slate-400 mb-1 uppercase tracking-wider"><?= translate('label_recaptcha_secret_key', 'Secret Key') ?></label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 pointer-events-none">
                                        <i class="fas fa-key text-sm"></i>
                                    </span>
                                    <input id="recaptcha_secret_key" type="text" name="recaptcha_secret_key"
                                           value="<?= htmlspecialchars($_POST['recaptcha_secret_key'] ?? '') ?>"
                                           class="w-full bg-slate-900/60 border border-slate-600 text-slate-200 rounded-lg pl-10 pr-4 py-2.5 focus:ring-2 focus:ring-gold-500 focus:border-gold-500 transition-all placeholder-slate-500 text-sm"
                                           placeholder="<?= translate('placeholder_recaptcha_default', 'Leave empty for default') ?>">
                                </div>
                            </div>

                            <p class="text-gold-600 text-xs italic flex items-center gap-1 mt-2">
                                <i class="fas fa-info-circle"></i>
                                <?= translate('note_recaptcha_empty', 'Leave empty to use default test keys.') ?>
                            </p>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
                        <button type="submit" class="w-full sm:w-auto flex items-center justify-center px-10 py-3.5 bg-gold-500 hover:bg-gold-400 text-slate-900 font-bold rounded-lg shadow-lg shadow-gold-600/20 transition-all duration-300 transform hover:scale-105">
                            <i class="fas fa-save mr-2"></i>
                            <?= translate('btn_test_save_db', 'Test & Save Settings') ?>
                        </button>
                        <a href="<?= htmlspecialchars($base_path ?? '') ?>install/step2_check" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 bg-slate-700/50 hover:bg-slate-700/70 text-slate-300 font-semibold rounded-lg transition-all duration-300 border border-slate-600/30">
                            <i class="fas fa-arrow-left mr-2"></i>
                            <?= translate('btn_go_back', 'Go Back') ?>
                        </a>
                    </div>
                </form>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.inc.php'; ?>
</body>
</html>