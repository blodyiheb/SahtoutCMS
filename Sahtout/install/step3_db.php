<?php
define('ALLOWED_ACCESS', true);
include __DIR__ . '/header.inc.php';

// step3_db.php – Database setup step
$errors = [];
$success = false;
$configFile = realpath(__DIR__ . '/../includes/config.php');

// Force mysqli to throw exceptions instead of silent fails
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost  = trim($_POST['db_host'] ?? '');
    $dbUser  = trim($_POST['db_user'] ?? '');
    $dbPass  = trim($_POST['db_pass'] ?? '');
    $dbAuth  = trim($_POST['db_auth'] ?? '');
    $dbWorld = trim($_POST['db_world'] ?? '');
    $dbChar  = trim($_POST['db_char'] ?? '');
    $dbSite  = trim($_POST['db_site'] ?? 'sahtout_site');

    // Validate required fields
    if (empty($dbHost)) $errors[] = "Database host is required";
    if (empty($dbUser)) $errors[] = "Database username is required";
    if (empty($dbAuth)) $errors[] = "Auth database name is required";
    if (empty($dbWorld)) $errors[] = "World database name is required";
    if (empty($dbChar)) $errors[] = "Character database name is required";
    if (empty($dbSite)) $errors[] = "Site database name is required";

    if (empty($errors)) {
        // Test connections individually with more detailed error checking
        $dbConns = [
            'Auth DB' => [$dbAuth, null, 'auth'],
            'World DB' => [$dbWorld, null, 'world'],
            'Char DB' => [$dbChar, null, 'char'],
            'Site DB' => [$dbSite, null, 'site'],
        ];

        foreach ($dbConns as $name => $connInfo) {
            try {
                $conn = new mysqli($dbHost, $dbUser, $dbPass, $connInfo[0]);
                $dbConns[$name][1] = $conn; // Update array directly without reference
                
                // Verify the database has required tables (basic check)
                $requiredTables = [];
                switch ($connInfo[2]) {
                    case 'auth':
                        $requiredTables = ['account', 'realmcharacters'];
                        break;
                    case 'world':
                        $requiredTables = ['creature_template', 'item_template'];
                        break;
                    case 'char':
                        $requiredTables = ['characters', 'character_inventory'];
                        break;
                    case 'site':
                        // Site DB will be empty at first install
                        break;
                }
                
                foreach ($requiredTables as $table) {
                    $result = $conn->query("SHOW TABLES LIKE '$table'");
                    if (!$result || $result->num_rows === 0) {
                        $errors[] = "❌ {$name} is missing required table: {$table}";
                    }
                    if ($result) $result->free();
                }
            } catch (Exception $e) {
                $errors[] = "❌ {$name} Connection failed: " . $e->getMessage();
                $dbConns[$name][1] = null; // Explicitly set to null on failure
            }
        }

        // If all OK → write config file
        if (empty($errors)) {
            $configContent = "<?php
if (!defined('ALLOWED_ACCESS')) exit('Direct access not allowed.');
\$db_host = '".addslashes($dbHost)."';
\$db_user = '".addslashes($dbUser)."';
\$db_pass = '".addslashes($dbPass)."';
\$db_auth = '".addslashes($dbAuth)."';
\$db_world = '".addslashes($dbWorld)."';
\$db_char = '".addslashes($dbChar)."';
\$db_site = '".addslashes($dbSite)."';

\$auth_db = new mysqli(\$db_host,\$db_user,\$db_pass,\$db_auth);
\$world_db = new mysqli(\$db_host,\$db_user,\$db_pass,\$db_world);
\$char_db = new mysqli(\$db_host,\$db_user,\$db_pass,\$db_char);
\$site_db = new mysqli(\$db_host,\$db_user,\$db_pass,\$db_site);

// Verify connections
if (\$auth_db->connect_error) die('Auth DB Connection failed: ' . \$auth_db->connect_error);
if (\$world_db->connect_error) die('World DB Connection failed: ' . \$world_db->connect_error);
if (\$char_db->connect_error) die('Char DB Connection failed: ' . \$char_db->connect_error);
if (\$site_db->connect_error) die('Site DB Connection failed: ' . \$site_db->connect_error);
?>";

            // Verify directory is writable
            $configDir = dirname($configFile);
            if (!is_writable($configDir)) {
                $errors[] = "⚠️ Config directory is not writable: {$configDir}";
            } else {
                if (file_put_contents($configFile, $configContent) === false) {
                    $errors[] = "⚠️ Failed to write config file: {$configFile}";
                }
                
                if (empty($errors)) {
                    $success = true;
                }
            }
        }

        // Close all connections
        foreach ($dbConns as $name => $connInfo) {
            if ($connInfo[1] instanceof mysqli && !is_null($connInfo[1]) && !$connInfo[1]->connect_error) {
                try {
                    $connInfo[1]->close();
                } catch (Exception $e) {
                    $errors[] = "Failed to close {$name} connection: " . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SahtoutCMS Installer - Step 3</title>
<style>
body {margin:0;padding:0;font-family:'Cinzel', serif;background:#0a0a0a;color:#f0e6d2;}
.overlay {background: rgba(0,0,0,0.9); inset:0; display:flex; align-items:center; justify-content:center; padding:20px;}
.container {text-align:center; max-width:700px; width:100%;min-height: 70vh; max-height:90vh; overflow-y:auto; padding:30px 20px; border:2px solid #6b4226; background: rgba(20,10,5,0.95); border-radius:12px; box-shadow:0 0 30px #6b4226;}
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
.db-status-success {color: #7CFC00;}
.db-status-error {color: #ff4040;}
.note {font-size:0.9em; color:#a37e2c; margin-top:10px; text-align:left;}
</style>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="overlay">
<div class="container">
<h1>⚔️ SahtoutCMS Installer</h1>
<h2 class="section-title">Step 3: Database Setup</h2>

<?php if(!empty($errors)): ?>
    <div class="error-box">
        <strong>Please fix the following errors:</strong>
        <?php foreach($errors as $err): ?>
            <div class="db-status">
                <span class="db-status-icon db-status-error">❌</span>
                <span class="error"><?= htmlspecialchars($err) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
<?php elseif($success): ?>
    <div class="db-status">
        <span class="db-status-icon db-status-success">✔</span>
        <span class="success">All databases connected successfully! Config file created.</span>
    </div>
    <a href="step4_realm.php" class="btn">Proceed to Step 4 Email configuration SMTP➡️</a>
<?php endif; ?>

<?php if(!$success): ?>
<form method="post">
    <div class="section-title">Database Credentials</div>
    <label for="db_host">Database Host</label>
    <input type="text" id="db_host" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>

    <label for="db_user">Database Username</label>
    <input type="text" id="db_user" name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" required>

    <label for="db_pass">Database Password</label>
    <input type="password" id="db_pass" name="db_pass" value="<?= htmlspecialchars($_POST['db_pass'] ?? '') ?>">

    <label for="db_auth">Auth DB Name</label>
    <input type="text" id="db_auth" name="db_auth" value="<?= htmlspecialchars($_POST['db_auth'] ?? '') ?>" required>

    <label for="db_world">World DB Name</label>
    <input type="text" id="db_world" name="db_world" value="<?= htmlspecialchars($_POST['db_world'] ?? '') ?>" required>

    <label for="db_char">Char DB Name</label>
    <input type="text" id="db_char" name="db_char" value="<?= htmlspecialchars($_POST['db_char'] ?? '') ?>" required>

    <label for="db_site">Site DB Name</label>
    <input type="text" id="db_site" name="db_site" value="<?= htmlspecialchars($_POST['db_site'] ?? 'sahtout_site') ?>" required>
    <p class="note">"sahtout_site" is recommended for the site database name</p>

    <button type="submit" class="btn">Test & Save Database Settings</button>
</form>
<?php endif; ?>

</div>
</div>
<?php include __DIR__ . '/footer.inc.php'; ?>
</body>
</html>