<?php
define('ALLOWED_ACCESS', true);

// Load session and language system
require_once '../../includes/session.php';
require_once '../../languages/language.php'; // This loads the translate() function

// Restrict access to admin or moderator roles
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'moderator'])) {
    header('Location: /sahtout/login');
    exit;
}

$page_class = 'gm_cmd';
include dirname(__DIR__) . '../../includes/header.php';

$result = null; // Initialize result

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $command = trim($_POST['command']);

    if (!empty($command)) {
        include dirname(__DIR__) . '../../includes/soap.conf.php';

        $xml = '<?xml version="1.0" encoding="utf-8"?>'
            . '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<SOAP-ENV:Body>'
            . '<ns1:executeCommand xmlns:ns1="urn:AC">'
            . '<command>' . htmlspecialchars($command, ENT_QUOTES) . '</command>'
            . '</ns1:executeCommand>'
            . '</SOAP-ENV:Body>'
            . '</SOAP-ENV:Envelope>';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $soap_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "$soap_user:$soap_pass");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: text/xml",
            "Content-Length: " . strlen($xml)
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $result = "cURL Error: " . curl_error($ch);
        } else {
            $result = htmlspecialchars($response);
        }
        curl_close($ch);
    } else {
        $result = translate('error_no_command', 'No command entered.');
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo translate('page_title_soap', 'SOAP Command Executor'); ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            font-family: Arial, sans-serif;
            color: #eee;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .d-flex { flex-wrap: wrap; }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: #f8f9fa;
            min-height: 100vh;
        }

        /* Main content */
        .main-content {
            flex: 1;
            padding: 20px;
        }

        /* Preformatted SOAP response */
        pre {
            padding: 15px;
            border-radius: 5px;
            white-space: pre-wrap;
            word-wrap: break-word;
            color: #eee;
            overflow-x: auto;
        }

        .form-container {
            max-width: 600px;
            margin: 0 auto;
        }

        /* Sidebar links */
        .admin-sidebar-nav .nav-link {
            color: #333;
            border-radius: 4px;
            margin-bottom: 0.25rem;
        }
        .admin-sidebar-nav .nav-link:hover,
        .admin-sidebar-nav .nav-link.active {
            color: #fff;
        }
        .admin-sidebar-nav .nav-link.active {
            background: #0d6efd;
        }
        .admin-sidebar-nav .nav-link.text-danger:hover {
            background: #dc3545;
            color: #fff !important;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                min-height: auto;
            }
            .main-content {
                padding: 10px;
            }
            .form-container {
                max-width: 100%;
                margin: 0 5px;
            }
            input[type="text"] {
                width: 100%;
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="d-flex flex-grow-1">
        <!-- Sidebar/Navbar -->
        <?php include dirname(__DIR__) . '../../includes/admin_sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-lg-8 form-container">
                        <h1 class="text-center mb-4"><?php echo translate('soap_title', 'Execute SOAP Command'); ?></h1>
                        
                        <form method="post" class="mb-4">
                            <div class="input-group">
                                <input 
                                    type="text" 
                                    name="command" 
                                    class="form-control" 
                                    style="color: #000000ff; background:#eee" 
                                    placeholder="<?php echo translate('command_placeholder', '.character name level 80'); ?>" 
                                    required
                                >
                                <button type="submit" class="btn btn-primary">
                                    <?php echo translate('run_command', 'Run'); ?>
                                </button>
                            </div>
                        </form>
                        
                        <?php if ($result !== null): ?>
                            <div class="card bg-dark border-secondary">
                                <div class="card-header">
                                    <h2 class="h5 mb-0"><?php echo translate('response_label', 'Response:'); ?></h2>
                                </div>
                                <div class="card-body p-0">
                                    <pre class="m-0 p-3"><?= $result ?></pre>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include dirname(__DIR__) . '../../includes/footer.php'; ?>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>