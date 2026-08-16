<?php
define('ALLOWED_ACCESS', true);

require_once __DIR__ . '/../../includes/paths.php';
require_once $project_root . 'includes/session.php';
require_once $project_root . 'languages/language.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'moderator'])) {
    header("Location: {$base_path}login");
    exit;
}

$page_class = 'gm_cmd';

$response_output = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $command = trim($_POST['command']);

    if (!empty($command)) {
        include $project_root . 'includes/soap.conf.php';

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
            $response_output = '<div class="bg-red-900/40 border border-red-500/50 text-red-300 px-4 py-3 rounded-sm flex items-center gap-3">
                <i class="fas fa-exclamation-triangle"></i>
                <span>cURL Error: ' . htmlspecialchars(curl_error($ch)) . '</span>
            </div>';
        } else {
            $response_output = '<div class="bg-black/30 p-4 rounded-sm border border-[rgba(201,162,39,0.1)] overflow-x-auto">
                <pre class="text-green-400 text-sm font-mono whitespace-pre-wrap break-words m-0">' . htmlspecialchars($response) . '</pre>
            </div>';
        }
        curl_close($ch);
    } else {
        $response_output = '<div class="bg-yellow-900/40 border border-yellow-500/50 text-yellow-300 px-4 py-3 rounded-sm flex items-center gap-3">
            <i class="fas fa-exclamation-triangle"></i>
            <span>' . translate('error_no_command', 'No command entered.') . '</span>
        </div>';
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo translate('page_description_soap', 'Execute SOAP GM commands for Sahtout WoW Server'); ?>">
    <meta name="robots" content="noindex">
    <title><?php echo translate('page_title_soap', 'SOAP Command Executor'); ?></title>
    <link rel="icon" href="<?php echo $base_path . $site_logo; ?>" type="image/x-icon">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/tailwind.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&family=Cinzel:wght@600;700;900&display=swap" rel="stylesheet">
    
    <style>
        * { font-family: 'Inter', sans-serif; }

        body {
            min-height: 100vh;
            color: #d8d8d8;
            background: #05070b;
            background-image:
                radial-gradient(1000px 700px at -10% 35%, rgba(59,130,246,.14), transparent 65%),
                radial-gradient(800px 600px at -5% 85%, rgba(124,58,237,.10), transparent 70%),
                linear-gradient(180deg, #0a0e16 0%, #060810 45%, #03040a 100%);
            background-attachment: fixed;
        }

        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image:
                radial-gradient(2px 2px at 10% 20%, rgba(242,207,82,.7), transparent 55%),
                radial-gradient(1.5px 1.5px at 30% 70%, rgba(242,207,82,.5), transparent 55%),
                radial-gradient(2px 2px at 55% 40%, rgba(255,160,60,.55), transparent 55%);
            background-size: 900px 700px;
            animation: emberDrift 45s linear infinite;
            opacity: .4;
            pointer-events: none;
            z-index: 0;
        }

        @keyframes emberDrift {
            from { background-position: 0 0; }
            to { background-position: 900px -700px; }
        }

        .panel {
            position: relative;
            background: linear-gradient(180deg, rgba(22,25,32,.92), rgba(8,10,14,.9));
            border: 1px solid rgba(201,162,39,.22);
            box-shadow: 0 12px 32px rgba(0,0,0,.55), inset 0 0 60px rgba(0,0,0,.45);
        }

        .panel::before {
            content: '';
            position: absolute;
            inset: 5px;
            border: 1px solid rgba(201,162,39,.14);
            pointer-events: none;
        }

        .panel::after {
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

        .wow-title {
            font-family: 'Cinzel', serif;
            font-weight: 900;
            background: linear-gradient(180deg, #fff7d6 0%, #f2cf5b 35%, #c9a227 62%, #8a6a14 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 3px 6px rgba(0,0,0,.85));
            letter-spacing: .02em;
        }

        .section-title {
            font-family: 'Cinzel', serif;
            font-weight: 700;
            color: #f2cf5b;
            text-shadow: 0 0 12px rgba(201,162,39,.35), 0 2px 4px rgba(0,0,0,.8);
        }

        .btn-gold {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            padding: .75rem 1.5rem;
            font-weight: 800;
            font-size: .85rem;
            letter-spacing: .04em;
            text-transform: uppercase;
            clip-path: polygon(10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%, 0 10px);
            transition: transform .2s ease;
            background: linear-gradient(180deg, #f6d478 0%, #c9a227 48%, #8a6a14 100%);
            color: #1a1200;
            text-shadow: 0 1px 0 rgba(255,255,255,.35);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.28), inset 0 -8px 14px rgba(0,0,0,.25);
            border: none;
            cursor: pointer;
        }
        .btn-gold:hover { transform: translateY(-2px) scale(1.02); }

        .input-dark {
            background: rgba(10, 14, 22, 0.8);
            border: 1px solid rgba(201,162,39,.3);
            color: #e5e7eb;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            outline: none;
            width: 100%;
        }
        .input-dark:focus {
            border-color: #f2cf5b;
            box-shadow: 0 0 10px rgba(242,207,82,.2);
            background: rgba(15, 20, 30, 0.9);
        }
        .input-dark::placeholder { color: rgba(150, 170, 200, 0.4); }

        .command-history-item {
            background: rgba(10, 14, 22, 0.5);
            border: 1px solid rgba(201,162,39,0.08);
            padding: 0.5rem 0.75rem;
            transition: all 0.2s ease;
            cursor: pointer;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            color: #b8c8ff;
        }
        .command-history-item:hover {
            border-color: rgba(201,162,39,0.3);
            background: rgba(15, 20, 30, 0.7);
        }
        .command-history-item .cmd-icon {
            color: #f2cf5b;
            margin-right: 0.5rem;
        }

        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            line-height: 1.6;
            color: #b8c8ff;
        }

        /* Main content area - works with sidebar */
        .main-content-area {
            transition: margin-left 0.3s ease;
            min-height: calc(100vh - 72px);
            width: 100%;
        }

        /* Content wrapper with proper spacing */
        .content-wrapper {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        @media (min-width: 640px) {
            .content-wrapper {
                padding: 0 1.5rem;
            }
        }

        @media (min-width: 1024px) {
            .content-wrapper {
                padding: 0 2rem;
            }
        }

        @media (min-width: 1280px) {
            .content-wrapper {
                padding: 0 2.5rem;
            }
        }

        @media (min-width: 1024px) {
            .main-content-area.lg\:ml-0 {
                margin-left: 0;
            }
            .main-content-area.lg\:ml-\[280px\] {
                margin-left: 280px;
            }
        }

        @media (max-width: 1023px) {
            .main-content-area {
                margin-left: 0 !important;
                padding: 1rem;
            }
            .content-wrapper {
                padding: 0 0.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include $project_root . 'includes/header.php'; ?>

    <!-- Main Content Area with Sidebar -->
    <div class="flex relative min-h-screen">
        
        <!-- Sidebar -->
        <?php include $project_root . 'includes/admin_sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content-area flex-1 p-3 sm:p-4 md:p-6 lg:p-8 transition-all duration-300 lg:ml-[280px]">
            <div class="content-wrapper">
                <div class="space-y-4 md:space-y-6 lg:space-y-8">
                    
                    <h1 class="wow-title text-2xl md:text-3xl lg:text-4xl"><?php echo translate('soap_title', 'Execute SOAP Command'); ?></h1>

                    <!-- Command Form -->
                    <div class="panel p-4 md:p-6 lg:p-8">
                        <h2 class="section-title text-lg md:text-xl mb-4 md:mb-6 flex items-center gap-3">
                            <i class="fas fa-terminal text-[#f2cf5b]"></i>
                            <?php echo translate('soap_title', 'Execute SOAP Command'); ?>
                        </h2>

                        <!-- Instructions -->
                        <div class="mb-4 md:mb-6 p-3 md:p-4 border border-[rgba(201,162,39,0.1)] bg-[rgba(10,14,22,0.4)] rounded-sm">
                            <div class="flex items-start gap-2 md:gap-3">
                                <i class="fas fa-circle-info text-[#f2cf5b] text-lg md:text-xl mt-1"></i>
                                <div>
                                    <p class="text-gray-300 text-xs md:text-sm"><?php echo translate('gm_command_instructions', 'Enter your GM commands in the box below. For example: <code>.character level PlayerName 80</code>.'); ?></p>
                                    <p class="text-gray-400 text-[10px] md:text-xs mt-1">
                                        <?php echo translate('gm_command_docs', 'For a full list of commands, check the <a href="https://www.azerothcore.org/wiki/gm-commands" target="_blank" class="text-[#f2cf5b] hover:underline">AzerothCore GM Commands Wiki</a>.'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <form method="post" class="space-y-3 md:space-y-4">
                            <div class="flex flex-col sm:flex-row gap-2 md:gap-3">
                                <input 
                                    type="text" 
                                    name="command" 
                                    class="input-dark flex-1" 
                                    placeholder="<?php echo translate('command_placeholder', '.character level PlayerName 80'); ?>" 
                                    required
                                    autofocus
                                >
                                <button type="submit" class="btn-gold flex-shrink-0 justify-center">
                                    <i class="fas fa-play"></i>
                                    <?php echo translate('run_command', 'Run'); ?>
                                </button>
                            </div>
                        </form>

                        <!-- Command History Suggestions -->
                        <div class="mt-3 md:mt-4 pt-3 md:pt-4 border-t border-[rgba(201,162,39,0.08)]">
                            <p class="text-xs text-gray-500 mb-2 flex items-center gap-2">
                                <i class="fas fa-clock text-[#f2cf5b]"></i>
                                <?php echo translate('quick_commands', 'Quick Commands'); ?>
                            </p>
                            <div class="flex flex-wrap gap-1.5 md:gap-2">
                                <span class="command-history-item text-xs md:text-sm" onclick="document.querySelector('input[name=command]').value='.character level '; document.querySelector('input[name=command]').focus();">
                                    <i class="fas fa-chevron-right cmd-icon"></i>.character level
                                </span>
                                <span class="command-history-item text-xs md:text-sm" onclick="document.querySelector('input[name=command]').value='.modify money '; document.querySelector('input[name=command]').focus();">
                                    <i class="fas fa-chevron-right cmd-icon"></i>.modify money
                                </span>
                                <span class="command-history-item text-xs md:text-sm" onclick="document.querySelector('input[name=command]').value='.tele '; document.querySelector('input[name=command]').focus();">
                                    <i class="fas fa-chevron-right cmd-icon"></i>.tele
                                </span>
                                <span class="command-history-item text-xs md:text-sm" onclick="document.querySelector('input[name=command]').value='.npc add '; document.querySelector('input[name=command]').focus();">
                                    <i class="fas fa-chevron-right cmd-icon"></i>.npc add
                                </span>
                                <span class="command-history-item text-xs md:text-sm" onclick="document.querySelector('input[name=command]').value='.go '; document.querySelector('input[name=command]').focus();">
                                    <i class="fas fa-chevron-right cmd-icon"></i>.go
                                </span>
                                <span class="command-history-item text-xs md:text-sm" onclick="document.querySelector('input[name=command]').value='.learn '; document.querySelector('input[name=command]').focus();">
                                    <i class="fas fa-chevron-right cmd-icon"></i>.learn
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Response -->
                    <?php if ($response_output !== null): ?>
                        <div class="panel p-4 md:p-6 lg:p-8">
                            <h2 class="section-title text-lg md:text-xl mb-4 md:mb-6 flex items-center gap-3">
                                <i class="fas fa-reply text-[#f2cf5b]"></i>
                                <?php echo translate('response_label', 'Response:'); ?>
                            </h2>
                            <?php echo $response_output; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.querySelector('input[name=command]');
            if (input) {
                input.focus();
            }
        });

        document.querySelector('input[name=command]')?.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });
    </script>
</body>
</html>