<?php
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../includes/paths.php'; // Include paths.php
require_once __DIR__ . '/header.inc.php';
require_once __DIR__ . '/../includes/config.php';

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
        $error = sprintf(translate('err_soap_account_not_found', 'Account %s does not exist in Auth DB.'), htmlspecialchars($soapUser));
    } else {
        $stmt2 = $auth_db->prepare("SELECT gmlevel FROM account_access WHERE id = ? AND RealmID = -1");
        $stmt2->bind_param('i', $accountId);
        $stmt2->execute();
        $stmt2->bind_result($gmLevel);
        $stmt2->fetch();
        $stmt2->close();

        if (!$gmLevel || $gmLevel < 3) {
            $error = sprintf(translate('err_soap_gm_level', 'Account %s exists but is not GM level 3.'), htmlspecialchars($soapUser));
        } else {
            $configContent = "<?php
\$soap_url  = '$soapUrl';
\$soap_user = '$soapUser'; // Must be GM level 3
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
    <title><?= translate('installer_title') ?> - <?= translate('step6_title', 'SOAP Setup') ?></title>
    <style>
        body {margin:0;padding:0;font-family:'Cinzel', serif;background:#0a0a0a;color:#f0e6d2;}
        .overlay {background: rgba(0,0,0,0.9); inset:0; display:flex; align-items:center; justify-content:center; padding:20px;}
        .container {text-align:center; max-width:700px; width:100%;min-height: 70vh; max-height:90vh; overflow-y:auto; padding:30px 20px; border:2px solid #6b4226; background: rgba(20,10,5,0.95); border-radius:12px; box-shadow:0 0 30px #6b4226;}
        h1 {font-size:2.5em; margin-bottom:20px; color:#d4af37; text-shadow:0 0 10px #000;}
        label {display:block; text-align:left; margin:10px 0 5px;}
        input {width:100%; padding:10px; border-radius:6px; border:1px solid #6b4226; background:rgba(30,15,5,0.9); color:#f0e6d2;}
        .btn {display:inline-block; padding:12px 30px; font-size:1.2em; font-weight:bold; color:#fff; background: linear-gradient(135deg,#6b4226,#a37e2c); border:none; border-radius:8px; cursor:pointer; text-decoration:none; box-shadow:0 0 15px #a37e2c; transition:0.3s ease; margin-top:15px;}
        .btn:hover {background: linear-gradient(135deg,#a37e2c,#d4af37); box-shadow:0 0 25px #d4af37;}
        .error {color:#ff4040; font-weight:bold; margin-top:15px;}
        .success {color:#7CFC00; font-weight:bold; margin-top:15px;}
        .section-title {margin-top:30px; font-size:1.5em; color:#d4af37; text-decoration: underline;}
        .info-box {margin-top:25px; text-align:left; background:rgba(30,15,5,0.85); padding:15px; border-radius:10px; border:1px solid #6b4226;}
        .info-box strong {color:#d4af37;}
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="overlay">
        <div class="container">
            <h1><?= translate('step6_title', 'Step 6: SOAP Setup') ?></h1>

            <?php if ($error): ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php elseif ($success): ?>
                <p class="success">✔ <?= translate('msg_soap_saved', 'SOAP configuration saved! GM account verified.') ?></p>
                <p><?= translate('msg_soap_note', 'Make sure its gm') ?></p>
                <a href="<?php echo $base_path; ?>install/finish.php" class="btn"><?= translate('btn_proceed_to_finish', 'Proceed to Finish Installation ➡️') ?></a>
            <?php else: ?>
                <form method="post">
                    <div class="section-title"><?= translate('section_soap_config', 'SOAP Configuration') ?></div>

                    <label for="soap_url"><?= translate('label_soap_url', 'SOAP URL') ?></label>
                    <input type="text" id="soap_url" name="soap_url" value="http://127.0.0.1:7878" required>

                    <label for="soap_user"><?= translate('label_gm_username', 'GM Account Username') ?></label>
                    <input type="text" id="soap_user" name="soap_user" placeholder="<?= translate('placeholder_gm_level3', 'Must be GM level 3') ?>" required>

                    <label for="soap_pass"><?= translate('label_soap_password', 'SOAP Password') ?></label>
                    <input type="password" id="soap_pass" name="soap_pass" placeholder="<?= translate('placeholder_soap_pass', 'SOAP password=Account password') ?>" required>

                    <button type="submit" class="btn"><?= translate('btn_save_verify_gm', 'Save & Verify GM') ?></button>
                </form>
            <?php endif; ?>

            <div class="info-box">
                <strong><?= translate('important_steps', 'Important Steps:') ?></strong>
                <ul>
                    <li><?= translate('info_soap_li1', 'Make sure the GM account exists in your Auth DB and has GM level 3 in <code>account_access</code> with <code>RealmID = -1</code>.') ?></li>
                    <li><?= translate('info_soap_li2', 'Open your <code>worldserver.conf</code> file and set: <strong>SOAP.Enabled = 1</strong>') ?></li>
                    <li><?= translate('info_soap_li3', 'Ensure the SOAP port in <code>soap_url</code> is correct and accessible.') ?></li>
                </ul>
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/footer.inc.php'; ?>
</body>
</html>