<?php
require_once '../includes/session.php';

// Restrict access to admin or moderator roles
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'moderator'])) {
    header('Location: /sahtout/pages/login.php');
    exit;
}

$page_class ='gm_cmd';

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// SOAP configuration
$soap_url = 'http://127.0.0.1:7878';
$soap_user = 'topadmin'; // Replace with secure username in production
$soap_pass = '123456';   // Replace with secure password in production

// Process form submission
$result = null;
$error = null;
$success = false;
$show_help = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['command'], $_POST['csrf_token'])) {
    // Verify CSRF token
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid CSRF token.';
    } else {
        $command = trim($_POST['command']);
        if (!empty($command)) {
            // Build SOAP XML
            $xml = '
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
               <SOAP-ENV:Body>
                  <ns1:executeCommand xmlns:ns1="urn:AC">
                     <command>' . htmlspecialchars($command, ENT_QUOTES, 'UTF-8') . '</command>
                  </ns1:executeCommand>
               </SOAP-ENV:Body>
            </SOAP-ENV:Envelope>';

            // Send via cURL
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $soap_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: text/xml',
                    'Authorization: Basic ' . base64_encode("$soap_user:$soap_pass")
                ],
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $xml
            ]);

            $response = curl_exec($ch);
            $curl_error = curl_error($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Parse response
            if ($response) {
                $xmlResponse = simplexml_load_string($response);
                
                // Check if it's a SOAP fault (incomplete command response)
                if (isset($xmlResponse->Body->Fault)) {
                    $fault_string = (string)$xmlResponse->Body->Fault->faultstring;
                    $detail = (string)$xmlResponse->Body->Fault->detail;
                    
                    // Clean up the help text
                    $help_text = str_replace("### USAGE:", "<strong>Usage:</strong>", $detail);
                    $help_text = str_replace("Possible subcommands:", "<strong>Possible subcommands:</strong>", $help_text);
                    $help_text = nl2br(htmlspecialchars($help_text));
                    
                    $result = $help_text;
                    $show_help = true;
                } else {
                    // Normal successful response
                    $result = $xmlResponse ? ($xmlResponse->xpath('//result')[0] ?? 'Command executed successfully (no output).') : $response;
                    $success = true;
                }
            } else {
                $error = $curl_error ?: "HTTP Error: $http_code";
                // Log error
                $log_message = "[" . date('Y-m-d H:i:s') . "] Error: $error\nRequest: " . htmlentities($xml) . "\nResponse: " . ($response ?: 'None') . "\n";
                file_put_contents('../logs/soap_errors.log', $log_message, FILE_APPEND);
            }
        } else {
            $error = 'Command cannot be empty.';
        }
        // Regenerate CSRF token
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

include dirname(__DIR__) . '/includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Game Master Commands for Sahtout WoW Server">
    <meta name="robots" content="noindex">
    <title>GM Commands</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/sahtout/assets/css/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; }
        .wrapper { display: flex; flex-direction: column; min-height: 100vh; }
        .content-wrapper { flex: 1 0 auto; }
        .gm-content { max-width: 800px; }
        .gm-content textarea { width: 100%; height: 100px; }
        .gm-content pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
        .gm-content pre.error { color: red; }
        .gm-content .command-help { 
            background: #e7f1ff; 
            padding: 15px; 
            border-radius: 5px; 
            border-left: 4px solid #0d6efd;
            margin-bottom: 20px;
        }
        footer { flex-shrink: 0; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="content-wrapper">
            <div class="row w-100">
                <?php include dirname(__DIR__) . '/includes/admin_sidebar.php'; ?>
                <div class="col-md-9 gm-content">
                    <h1><i class="fas fa-terminal me-2"></i>SOAP Command Executor</h1>
                    <form method="POST" class="mb-4">
                        <div class="mb-3">
                            <label for="command" class="form-label">GM Command:</label>
                            <textarea name="command" id="command" class="form-control" placeholder=".server info" required><?php if (isset($_POST['command'])) echo htmlspecialchars($_POST['command'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                            <div class="form-text">Enter commands like .kick playername, .server info, or .character rename playername.</div>
                        </div>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-play me-2"></i>Execute</button>
                    </form>

                    <?php if ($show_help): ?>
                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle me-2"></i>Command help displayed below
                        </div>
                        <div class="command-help">
                            <?php echo $result; ?>
                        </div>
                    <?php elseif ($success): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle me-2"></i>Command executed successfully.
                        </div>
                        <h2>Result:</h2>
                        <pre><?php echo htmlspecialchars($result, ENT_QUOTES, 'UTF-8'); ?></pre>
                    <?php elseif (isset($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>Error occurred
                        </div>
                        <h2>Error:</h2>
                        <pre class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></pre>
                    <?php endif; ?>

                    <h3>Command Examples:</h3>
                    <ul>
                        <li><code>.character level [$playername] [#level]</code> (character level sahtout 80)</li>
                        <li><code>.send money #playername "#subject" "#text" #money</code> (send money sahtout admin test 10000)=1 gold</li>
                        <li><code>.teleport name [#playername] #location</code> (teleport name sors gmisland)</li>
                        <li><code>.server info</code> (Show server status)</li>
                        <li><code>.account set gmlevel AccountName 3 -1</code> (Make account GM)</li>
                        <li><code>.character rename [$name] [reserveName] [$newName]</code> (character rename sahtout blody)</li>
                    </ul>
                </div>
            </div>
        </div>
        <?php include dirname(__DIR__) . '/includes/footer.php'; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>