<?php
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../includes/paths.php';
require_once __DIR__ . '/header.inc.php';
require_once __DIR__ . '/../includes/config.php';

// Set current step for progress stepper
$current_step = 6;

$error = '';
$success = false;
$soapConfigFile = __DIR__ . '/../includes/soap.conf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $soapUrl  = $_POST['soap_url'] ?? 'http://127.0.0.1:7878';
    $soapUser = $_POST['soap_user'] ?? '';
    $soapPass = $_POST['soap_pass'] ?? '';

    $stmt = $auth_db->prepare("SELECT id FROM account WHERE username = ?");
    $stmt->bind_param('s', $soapUser);
    $stmt->execute();
    $stmt->bind_result($accountId);
    $stmt->fetch();
    $stmt->close();

    if (!$accountId) {
        $error = sprintf(translate('err_soap_account_not_found', 'Account \'%s\' does not exist in Auth DB.'), htmlspecialchars($soapUser));
    } else {
        $stmt2 = $auth_db->prepare("SELECT gmlevel FROM account_access WHERE id = ? AND RealmID = -1");
        $stmt2->bind_param('i', $accountId);
        $stmt2->execute();
        $stmt2->bind_result($gmLevel);
        $stmt2->fetch();
        $stmt2->close();

        if (!$gmLevel || $gmLevel < 3) {
            $error = sprintf(translate('err_soap_gm_level', 'Account \'%s\' exists but is not GM level 3.'), htmlspecialchars($soapUser));
        } else {
            $configContent = "<?php
if (!defined('ALLOWED_ACCESS')) { exit('Forbidden'); }

\$soap_url  = '$soapUrl';
\$soap_user = '$soapUser';
\$soap_pass = '$soapPass';
?>";

            if (file_put_contents($soapConfigFile, $configContent)) {
                $success = true;
            } else {
                $error = translate('err_write_soap_config', 'Cannot write to') . " $soapConfigFile. " . translate('err_check_permissions', 'Check folder permissions.');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($langCode ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= translate('installer_title') ?> - <?= translate('step6_title', 'SOAP Setup') ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'cinzel': ['Cinzel', 'serif'],
                        'sans': ['Inter', 'sans-serif'],
                    },
                    colors: {
                        gold: {
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                        }
                    }
                }
            }
        }
    </script>

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

        .main-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding-top: 20px;
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
                padding-top: 10px;
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
                    <i class="fas fa-code text-3xl text-gold-400"></i>
                </div>
                <h1 class="font-cinzel text-3xl md:text-4xl font-bold bg-gradient-to-b from-amber-100 to-gold-500 bg-clip-text text-transparent">
                    <?= translate('step6_title', 'Step 6: SOAP Setup') ?>
                </h1>
                <p class="text-slate-400 mt-2 text-sm"><?= translate('step6_description', 'Configure SOAP for server communication and admin commands.') ?></p>
            </div>

            <!-- Error -->
            <?php if ($error): ?>
                <div class="bg-red-900/30 border border-red-500/40 text-red-200 p-4 mb-6 rounded-lg">
                    <div class="flex items-center gap-2 font-bold text-red-300">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Success -->
            <?php if ($success): ?>
                <div class="bg-emerald-900/30 border border-emerald-500/40 text-emerald-200 p-5 mb-6 rounded-lg flex items-center gap-3">
                    <i class="fas fa-check-circle text-emerald-400 text-2xl"></i>
                    <span class="font-medium"><?= translate('msg_soap_saved', 'SOAP configuration saved! GM account verified.') ?></span>
                </div>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mt-6">
                    <a href="<?php echo $base_path; ?>install/finish.php" class="inline-flex items-center px-8 py-3 bg-gold-500 hover:bg-gold-400 text-slate-900 font-bold rounded-lg shadow-lg shadow-gold-600/20 transition-all duration-300 transform hover:scale-105">
                        <?= translate('btn_proceed_to_finish', 'Proceed to Finish Installation') ?>
                        <i class="fas fa-arrow-right ml-3"></i>
                    </a>
                    <a href="<?php echo $base_path; ?>install/step5_mail" class="inline-flex items-center px-6 py-3 bg-slate-700/50 hover:bg-slate-700/70 text-slate-300 font-semibold rounded-lg transition-all duration-300 border border-slate-600/30">
                        <i class="fas fa-arrow-left mr-2"></i>
                        <?= translate('btn_go_back', 'Go Back') ?>
                    </a>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <?php if (!$success && !$error): ?>
                <form method="post" class="space-y-5">
                    <h2 class="font-cinzel text-gold-400 font-bold text-lg flex items-center gap-2 border-b border-slate-700/50 pb-3">
                        <i class="fas fa-cogs"></i>
                        <?= translate('section_soap_config', 'SOAP Configuration') ?>
                    </h2>

                    <div>
                        <label for="soap_url" class="block text-xs font-medium text-slate-400 mb-1 uppercase tracking-wider">
                            <i class="fas fa-link text-gold-400 mr-1"></i>
                            <?= translate('label_soap_url', 'SOAP URL') ?>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 pointer-events-none">
                                <i class="fas fa-globe text-sm"></i>
                            </span>
                            <input id="soap_url" type="text" name="soap_url" 
                                   value="http://127.0.0.1:7878"
                                   class="w-full bg-slate-900/60 border border-slate-600 text-slate-200 rounded-lg pl-10 pr-4 py-2.5 focus:ring-2 focus:ring-gold-500 focus:border-gold-500 transition-all text-sm"
                                   required>
                        </div>
                    </div>

                    <div>
                        <label for="soap_user" class="block text-xs font-medium text-slate-400 mb-1 uppercase tracking-wider">
                            <i class="fas fa-user-shield text-gold-400 mr-1"></i>
                            <?= translate('label_gm_username', 'GM Account Username') ?>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 pointer-events-none">
                                <i class="fas fa-user text-sm"></i>
                            </span>
                            <input id="soap_user" type="text" name="soap_user" 
                                   value="<?= htmlspecialchars($_POST['soap_user'] ?? '') ?>"
                                   class="w-full bg-slate-900/60 border border-slate-600 text-slate-200 rounded-lg pl-10 pr-4 py-2.5 focus:ring-2 focus:ring-gold-500 focus:border-gold-500 transition-all placeholder-slate-500 text-sm"
                                   placeholder="<?= translate('placeholder_gm_level3', 'Must be GM level 3') ?>" required>
                        </div>
                    </div>

                    <div>
                        <label for="soap_pass" class="block text-xs font-medium text-slate-400 mb-1 uppercase tracking-wider">
                            <i class="fas fa-lock text-gold-400 mr-1"></i>
                            <?= translate('label_soap_password', 'SOAP Password') ?>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 pointer-events-none">
                                <i class="fas fa-key text-sm"></i>
                            </span>
                            <input id="soap_pass" type="password" name="soap_pass" 
                                   value="<?= htmlspecialchars($_POST['soap_pass'] ?? '') ?>"
                                   class="w-full bg-slate-900/60 border border-slate-600 text-slate-200 rounded-lg pl-10 pr-4 py-2.5 focus:ring-2 focus:ring-gold-500 focus:border-gold-500 transition-all placeholder-slate-500 text-sm"
                                   placeholder="<?= translate('placeholder_soap_pass', 'SOAP password = Account password') ?>" required>
                        </div>
                    </div>

                    <!-- Info Box -->
                    <div class="bg-slate-800/30 border border-slate-700/50 rounded-lg p-4">
                        <div class="flex items-center gap-2 mb-3">
                            <i class="fas fa-info-circle text-gold-400"></i>
                            <span class="text-gold-400 font-semibold text-sm"><?= translate('important_steps', 'Important Steps:') ?></span>
                        </div>
                        <ul class="text-slate-300 text-sm space-y-2 list-disc list-inside">
                            <li><?= translate('info_soap_li1', 'Make sure the GM account exists in your Auth DB and has GM level 3 in <code class="text-gold-400">account_access</code> with <code class="text-gold-400">RealmID = -1</code>.') ?></li>
                            <li><?= translate('info_soap_li2', 'Open your <code class="text-gold-400">worldserver.conf</code> file and set: <strong class="text-gold-400">SOAP.Enabled = 1</strong>') ?></li>
                            <li><?= translate('info_soap_li3', 'Ensure the SOAP port in <code class="text-gold-400">soap_url</code> is correct and accessible.') ?></li>
                        </ul>
                    </div>

                    <!-- Buttons -->
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-2">
                        <button type="submit" class="w-full sm:w-auto flex items-center justify-center px-8 py-3.5 bg-gold-500 hover:bg-gold-400 text-slate-900 font-bold rounded-lg shadow-lg shadow-gold-600/20 transition-all duration-300 transform hover:scale-105">
                            <i class="fas fa-save mr-2"></i>
                            <?= translate('btn_save_verify_gm', 'Save & Verify GM') ?>
                        </button>
                        <a href="<?php echo $base_path; ?>install/step5_mail" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 bg-slate-700/50 hover:bg-slate-700/70 text-slate-300 font-semibold rounded-lg transition-all duration-300 border border-slate-600/30">
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