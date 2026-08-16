<?php
define('ALLOWED_ACCESS', true);

// Include paths.php using __DIR__ to access $project_root and $base_path
require_once __DIR__ . '/../includes/paths.php';

// Use $project_root for filesystem includes
require_once $project_root . 'includes/config.settings.php';
require_once $project_root . 'includes/session.php';
$page_class = 'vote';
require_once $project_root . 'includes/header.php';

// Check database connection
if (!isset($site_db) || !$site_db instanceof mysqli) {
    error_log("Database error: Connection not established.");
    die("Internal server error.");
}

// Fetch vote sites with callback_file_name, siteid, and url_format
$voteSites = [];
try {
    $stmt = $site_db->prepare("SELECT id, callback_file_name, site_name, siteid, url_format, button_image_url, cooldown_hours, reward_points, uses_callback FROM vote_sites");
    $stmt->execute();
    $voteSites = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    error_log("Database error fetching vote sites: " . $e->getMessage());
}

// Get logged-in user's username and account_id
$username = isset($_SESSION['username']) ? preg_replace('/[^a-zA-Z0-9_\.]/', '', $_SESSION['username']) : '';
$account_id = 0;
if ($username) {
    try {
        $stmt = $site_db->prepare("SELECT account_id FROM user_currencies WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $account_id = (int)$result->fetch_assoc()['account_id'];
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Database error fetching user: " . $e->getMessage());
    }
}

// Fetch unclaimed rewards and latest vote timestamps
$unclaimed_rewards = [];
$last_votes = [];
if ($account_id > 0) {
    $expiration_time = time() - (24 * 3600);
    try {
        // Fetch unclaimed rewards
        $stmt = $site_db->prepare("
            SELECT site_id, COUNT(*) as unclaimed_count
            FROM vote_log
            WHERE user_id = ? AND reward_status = 0 AND vote_timestamp >= ?
            GROUP BY site_id
        ");
        $stmt->bind_param("ii", $account_id, $expiration_time);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $unclaimed_rewards[$row['site_id']] = $row['unclaimed_count'] > 0;
        }
        $stmt->close();

        // Fetch latest vote timestamp for each site
        $stmt = $site_db->prepare("
            SELECT site_id, MAX(vote_timestamp) as last_vote
            FROM (
                SELECT site_id, vote_timestamp
                FROM vote_log
                WHERE user_id = ?
                UNION
                SELECT site_id, vote_timestamp
                FROM vote_log_history
                WHERE user_id = ?
            ) combined
            GROUP BY site_id
        ");
        $stmt->bind_param("ii", $account_id, $account_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $last_votes[$row['site_id']] = (int)$row['last_vote'];
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Database error fetching vote data: " . $e->getMessage());
    }
}

// Add unclaimed rewards and cooldown status to vote sites
foreach ($voteSites as &$site) {
    $site['has_unclaimed_rewards'] = isset($unclaimed_rewards[$site['id']]) ? $unclaimed_rewards[$site['id']] : false;
    $site['is_on_cooldown'] = false;
    $site['remaining_cooldown'] = 0;
    if (isset($last_votes[$site['id']])) {
        $cooldown_seconds = $site['cooldown_hours'] * 3600;
        $last_vote_time = $last_votes[$site['id']];
        $time_since_vote = time() - $last_vote_time;
        if ($time_since_vote < $cooldown_seconds) {
            $site['is_on_cooldown'] = true;
            $site['remaining_cooldown'] = $cooldown_seconds - $time_since_vote;
        }
    }
}
unset($site);

// Handle claim message
$claim_message = isset($_SESSION['claim_message']) ? htmlspecialchars($_SESSION['claim_message'], ENT_QUOTES, 'UTF-8') : '';
$claim_message_type = isset($_SESSION['claim_message_type']) ? htmlspecialchars($_SESSION['claim_message_type'], ENT_QUOTES, 'UTF-8') : '';
unset($_SESSION['claim_message'], $_SESSION['claim_message_type']);

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Fallback for translate function
if (!function_exists('translate')) {
    function translate($key, $default) {
        return $default;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en', ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_title_name ." ". translate('vote_title', 'Vote for Epic Rewards'); ?></title>
    
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/tailwind.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    
    <style>
        /* Page background - Lighter vibrant gradient for voting page */
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 25%, #0f3460 50%, #533483 75%, #1a1a2e 100%);
            min-height: 100vh;
            padding-top: 112px;
            margin: 0;
            position: relative;
        }
        
        /* Animated star particles overlay */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: 
                radial-gradient(2px 2px at 10% 20%, rgba(255,215,0,0.2), transparent),
                radial-gradient(2px 2px at 30% 60%, rgba(255,215,0,0.15), transparent),
                radial-gradient(2px 2px at 50% 10%, rgba(255,215,0,0.25), transparent),
                radial-gradient(2px 2px at 70% 80%, rgba(255,215,0,0.12), transparent),
                radial-gradient(2px 2px at 90% 40%, rgba(255,215,0,0.18), transparent),
                radial-gradient(1px 1px at 15% 85%, rgba(255,215,0,0.3), transparent),
                radial-gradient(1px 1px at 45% 45%, rgba(255,215,0,0.2), transparent),
                radial-gradient(1px 1px at 75% 25%, rgba(255,215,0,0.25), transparent),
                radial-gradient(1px 1px at 85% 70%, rgba(255,215,0,0.15), transparent),
                radial-gradient(1px 1px at 25% 35%, rgba(255,215,0,0.2), transparent);
            background-size: 300px 300px;
            pointer-events: none;
            z-index: 0;
            animation: twinkle 5s ease-in-out infinite alternate;
        }
        
        @keyframes twinkle {
            0% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        /* Subtle floating glow orbs */
        body::after {
            content: '';
            position: fixed;
            inset: 0;
            background: 
                radial-gradient(ellipse at 20% 50%, rgba(242,207,82,0.03), transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(139,92,246,0.04), transparent 50%),
                radial-gradient(ellipse at 50% 80%, rgba(52,152,219,0.03), transparent 50%);
            pointer-events: none;
            z-index: 0;
            animation: pulseGlow 8s ease-in-out infinite alternate;
        }
        
        @keyframes pulseGlow {
            0% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        /* Main content wrapper - UNCHANGED */
        .vote-content {
            position: relative;
            z-index: 1;
        }
        
        /* Glass effect container - UNCHANGED */
        .glass-container {
            background: rgba(5, 7, 11, 0.85);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(201,162,39,.22);
            border-radius: 0;
            padding: 2.5rem 2.5rem;
            box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.8), inset 0 0 60px rgba(0,0,0,.25);
            position: relative;
        }
        
        .glass-container::before {
            content: ''; position: absolute; inset: 5px;
            border: 1px solid rgba(201,162,39,.14);
            pointer-events: none;
        }
        
        .glass-container::after {
            content: ''; position: absolute; inset: 0; pointer-events: none;
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
        
        /* Wow title gradient - UNCHANGED */
        .wow-title {
            font-family: 'Cinzel', serif;
            font-weight: 900;
            background: linear-gradient(180deg, #fff7d6 0%, #f2cf5b 35%, #c9a227 62%, #8a6a14 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,.9));
            letter-spacing: .02em;
        }
        
        /* Glass cards - UNCHANGED */
        .glass-card {
            background: rgba(10, 14, 22, 0.75);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            border: 1px solid rgba(201,162,39,.15);
            border-radius: 0;
            transition: all 0.3s ease;
        }
        
        .glass-card:hover {
            border-color: rgba(201,162,39,.3);
            transform: translateY(-4px);
        }
        
        /* Vote card - UNCHANGED */
        .vote-card {
            background: rgba(10, 14, 22, 0.7);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            border: 1px solid rgba(201,162,39,.12);
            border-radius: 0;
            transition: all 0.3s ease;
        }
        
        .vote-card:hover {
            transform: translateY(-4px);
            border-color: rgba(201,162,39,.3);
            box-shadow: 0 12px 30px rgba(0,0,0,0.4);
        }
        
        /* Button styles - UNCHANGED */
        .btn-vote {
            background: linear-gradient(135deg, #f2cf5b, #c9a227);
            color: #1a1200;
            font-weight: 700;
            transition: all 0.3s ease;
            border: 2px solid #f2cf5b;
        }
        
        .btn-vote:hover:not([disabled]) {
            background: linear-gradient(135deg, #f6d478, #d4b040);
            box-shadow: 0 0 30px rgba(242, 207, 82, 0.3);
            transform: translateY(-2px);
        }
        
        .btn-vote[disabled] {
            background: rgba(255, 255, 255, 0.08);
            color: #6b7280;
            border-color: rgba(255, 255, 255, 0.1);
            cursor: not-allowed;
        }
        
        .btn-claim {
            background: linear-gradient(135deg, #2ecc71, #15803d);
            color: #ffffff;
            font-weight: 700;
            transition: all 0.3s ease;
            border: 2px solid #2ecc71;
        }
        
        .btn-claim:hover:not([disabled]) {
            background: linear-gradient(135deg, #4ade80, #16a34a);
            box-shadow: 0 0 30px rgba(46, 204, 113, 0.3);
            transform: translateY(-2px);
        }
        
        .btn-claim[disabled] {
            background: rgba(255, 255, 255, 0.05);
            color: #4b5563;
            border-color: rgba(255, 255, 255, 0.05);
            cursor: not-allowed;
        }
        
        /* Reward card - UNCHANGED */
        .reward-card {
            background: rgba(10, 14, 22, 0.6);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            border: 1px solid rgba(201,162,39,.1);
            border-radius: 0;
            transition: all 0.3s ease;
        }
        
        .reward-card:hover {
            transform: translateY(-3px);
            border-color: rgba(201,162,39,.25);
        }
        
        /* Cooldown timer - UNCHANGED */
        .cooldown-timer {
            font-family: 'Courier New', monospace;
        }
        
        /* Modal overlay - UNCHANGED */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 50;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            align-items: center;
            justify-content: center;
        }
        
        .modal-overlay.show {
            display: flex;
        }
        
        .modal-content {
            background: rgba(5, 7, 11, 0.95);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(201,162,39,.3);
            border-radius: 0;
            padding: 2rem;
            max-width: 400px;
            width: 100%;
            margin: 1rem;
            text-align: center;
            position: relative;
        }
        
        .modal-content::before {
            content: ''; position: absolute; inset: 5px;
            border: 1px solid rgba(201,162,39,.1);
            pointer-events: none;
        }
        
        /* Responsive - UNCHANGED */
        @media (max-width: 767px) {
            body {
                padding-top: 96px;
            }
            
            .glass-container {
                padding: 1.5rem 0.75rem;
            }
        }
    </style>
</head>
<body>

<div class="vote-content min-h-screen flex items-start justify-center px-4 md:px-8 py-8">
    <div class="container mx-auto max-w-7xl px-2 sm:px-4">
        
        <!-- Main Container - UNCHANGED -->
        <div class="glass-container">
            
            <!-- Page Header - UNCHANGED -->
            <div class="text-center space-y-3 mb-8">
                <a href="<?php echo htmlspecialchars($base_path, ENT_QUOTES, 'UTF-8'); ?>" class="inline-block transition-transform hover:scale-105">
                    <img src="<?php echo htmlspecialchars($base_path . $site_logo, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo translate('site_logo_alt', 'Sahtout Server Logo'); ?>" class="h-16 md:h-20 mx-auto object-contain drop-shadow-lg">
                </a>
                <h1 class="wow-title text-3xl md:text-5xl font-bold">
                    <i class="fas fa-check-to-slot text-[#f2cf5b] mr-2"></i>
                    <?php echo translate('vote_title_h1', 'Vote for Epic Rewards'); ?>
                </h1>
                <p class="text-gray-300 text-sm md:text-base max-w-2xl mx-auto">
                    <?php echo translate('vote_subtitle', 'Support our server by voting on top sites and earn exclusive in-game rewards!'); ?>
                </p>
            </div>

            <!-- Login Prompt Banner - UNCHANGED -->
            <?php if (empty($username)): ?>
                <div class="bg-[rgba(242,207,82,0.08)] border border-[rgba(201,162,39,0.2)] p-4 text-center text-[#f2cf5b] text-sm md:text-base flex items-center justify-center gap-2 mb-6">
                    <i class="fas fa-info-circle text-[#f2cf5b] text-lg"></i>
                    <span>
                        <?php echo translate('vote_login_prompt', 'Please log in to vote and earn rewards.'); ?>
                        <a href="<?php echo htmlspecialchars($base_path . 'login', ENT_QUOTES, 'UTF-8'); ?>" class="font-bold underline text-[#f2cf5b] hover:text-yellow-300 ml-1">Log in now</a>
                    </span>
                </div>
            <?php endif; ?>

            <!-- Dynamic Message Box - UNCHANGED -->
            <div class="message-box p-4 border text-sm md:text-base text-center font-medium mb-6 <?php echo $claim_message ? 'block' : 'hidden'; ?> <?php echo htmlspecialchars($claim_message_type, ENT_QUOTES, 'UTF-8') === 'error' ? 'bg-red-900/40 border-red-600/40 text-red-200' : 'bg-green-900/40 border-green-600/40 text-green-200'; ?>">
                <?php echo $claim_message; ?>
            </div>

            <!-- Vote Sites Grid - UNCHANGED -->
            <div class="space-y-4">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <i class="fas fa-list-check text-[#f2cf5b]"></i>
                    <span><?php echo translate('vote_available_sites', 'Available Voting Sites'); ?></span>
                </h2>

                <?php if (empty($voteSites)): ?>
                    <div class="text-center py-12 text-gray-400 border border-[rgba(201,162,39,0.1)] bg-[rgba(10,14,22,0.4)]">
                        <i class="fas fa-ghost text-4xl mb-3 block text-[rgba(201,162,39,0.3)]"></i>
                        <p><?php echo translate('vote_no_sites', 'No vote sites available at the moment.'); ?></p>
                    </div>
                <?php else: ?>
                    <!-- 3 columns grid - UNCHANGED -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($voteSites as $site): ?>
                            <div class="vote-card p-5 flex flex-col justify-between space-y-4">
                                <!-- Card Header & Image - UNCHANGED -->
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between gap-2">
                                        <h3 class="font-bold text-lg text-white truncate" title="<?php echo htmlspecialchars($site['site_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($site['site_name'], ENT_QUOTES, 'UTF-8'); ?>
                                        </h3>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold bg-[rgba(242,207,82,0.1)] text-[#f2cf5b] border border-[rgba(201,162,39,0.2)] whitespace-nowrap">
                                            <i class="fas fa-coins text-[#f2cf5b]"></i>
                                            <?php echo htmlspecialchars($site['reward_points'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </div>

                                    <!-- Image Container - UNCHANGED -->
                                    <div class="h-32 bg-[rgba(0,0,0,0.4)] p-3 border border-[rgba(201,162,39,0.08)] flex items-center justify-center overflow-hidden">
                                        <img src="<?php echo htmlspecialchars($site['button_image_url'] ?? $base_path . 'img/default.png', ENT_QUOTES, 'UTF-8'); ?>" 
                                             alt="<?php echo translate('vote_site_image_alt', 'Voting Site') . ': ' . htmlspecialchars($site['site_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                             class="max-h-full max-w-full object-contain">
                                    </div>
                                </div>

                                <!-- Action Controls - UNCHANGED -->
                                <div class="space-y-3 pt-2 border-t border-[rgba(201,162,39,0.08)]">
                                    <?php
                                    // Construct the voting URL
                                    $vote_url = $site['url_format'] ? htmlspecialchars($site['url_format'], ENT_QUOTES, 'UTF-8') : '#';
                                    if ($username && $site['url_format']) {
                                        $vote_url = str_replace(
                                            ['{siteid}', '{userid}', '{username}'],
                                            [urlencode($site['siteid']), urlencode($account_id), urlencode($username)],
                                            htmlspecialchars($site['url_format'], ENT_QUOTES, 'UTF-8')
                                        );
                                    } elseif ($site['uses_callback'] && $username) {
                                        $vote_url .= (parse_url($vote_url, PHP_URL_QUERY) ? '&' : '?') . 'vote=1&pingUsername=' . urlencode($username);
                                    }
                                    ?>

                                    <div class="grid grid-cols-2 gap-2">
                                        <a href="<?php echo $vote_url; ?>" 
                                           class="vote-btn btn-vote py-2.5 px-4 text-center text-sm inline-flex items-center justify-center gap-2" 
                                           target="_blank" 
                                           data-site-name="<?php echo htmlspecialchars($site['site_name'], ENT_QUOTES, 'UTF-8'); ?>" 
                                           <?php echo (empty($username) && $site['uses_callback']) || $site['is_on_cooldown'] ? 'disabled' : ''; ?>>
                                            <i class="fas fa-external-link-alt text-xs"></i>
                                            <span><?php echo translate('vote_button', 'Vote'); ?></span>
                                        </a>

                                        <?php if ($account_id > 0): ?>
                                            <button class="claim-btn btn-claim py-2.5 px-4 text-center text-sm inline-flex items-center justify-center gap-2" 
                                                    onclick="claimRewards(<?php echo (int)$account_id; ?>, '<?php echo htmlspecialchars($site['callback_file_name'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>')" 
                                                    <?php echo $site['has_unclaimed_rewards'] ? '' : 'disabled'; ?>>
                                                <i class="fas fa-gift text-xs"></i>
                                                <span><?php echo translate('claim_button', 'Claim'); ?></span>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn-claim py-2.5 px-4 text-center text-sm inline-flex items-center justify-center gap-2 bg-[rgba(255,255,255,0.05)] text-gray-500 border border-[rgba(255,255,255,0.05)] cursor-not-allowed" disabled>
                                                <i class="fas fa-lock text-xs"></i>
                                                <span><?php echo translate('claim_button', 'Claim'); ?></span>
                                            </button>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Status Info - UNCHANGED -->
                                    <div class="flex items-center justify-between text-xs text-gray-300 px-1 pt-1">
                                        <span class="flex items-center gap-1.5">
                                            <i class="far fa-clock text-[#f2cf5b]"></i>
                                            <span><?php echo htmlspecialchars($site['cooldown_hours'], ENT_QUOTES, 'UTF-8'); ?>h <?php echo translate('vote_cooldown_label', 'cooldown'); ?></span>
                                        </span>

                                        <?php if ($account_id > 0): ?>
                                            <span class="cooldown-timer text-gray-200" 
                                                  data-site-id="<?php echo (int)$site['id']; ?>" 
                                                  data-remaining-seconds="<?php echo (int)$site['remaining_cooldown']; ?>" 
                                                  data-cooldown-hours="<?php echo (int)$site['cooldown_hours']; ?>">
                                                <?php echo $site['is_on_cooldown'] ? translate('vote_cooldown_timer', 'Cooldown: Calculating...') : '<span class="text-[#2ecc71] font-semibold"><i class="fas fa-circle-check mr-1"></i>' . translate('vote_cooldown_ready', 'Ready to vote!') . '</span>'; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Rewards List Section - UNCHANGED -->
            <div class="pt-6 mt-6 border-t border-[rgba(201,162,39,0.1)] space-y-6">
                <div class="text-center space-y-1">
                    <h2 class="text-2xl font-extrabold text-white">
                        <?php echo translate('vote_rewards_title', 'Voting Rewards'); ?>
                    </h2>
                    <p class="text-xs md:text-sm text-gray-300"><?php echo translate('vote_rewards_subtitle', 'Claim valuable in-game perks every time you cast your vote'); ?></p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="reward-card p-5 text-center space-y-3">
                        <div class="w-12 h-12 mx-auto bg-[rgba(242,207,82,0.1)] border border-[rgba(201,162,39,0.2)] text-[#f2cf5b] flex items-center justify-center text-xl">
                            <i class="fas fa-coins"></i>
                        </div>
                        <h3 class="font-bold text-white"><?php echo translate('vote_reward_gold', 'Gold'); ?></h3>
                        <p class="text-xs text-gray-300 leading-relaxed"><?php echo translate('vote_reward_gold_desc', 'Receive up to 40 gold per vote to boost your in-game wealth.'); ?></p>
                    </div>

                    <div class="reward-card p-5 text-center space-y-3">
                        <div class="w-12 h-12 mx-auto bg-[rgba(139,92,246,0.1)] border border-[rgba(139,92,246,0.2)] text-[#8b5cf6] flex items-center justify-center text-xl">
                            <i class="fas fa-hat-wizard"></i>
                        </div>
                        <h3 class="font-bold text-white"><?php echo translate('vote_reward_enchants', 'Enchants'); ?></h3>
                        <p class="text-xs text-gray-300 leading-relaxed"><?php echo translate('vote_reward_enchants_desc', 'Unlock powerful weapon and armor enchants for your characters.'); ?></p>
                    </div>

                    <div class="reward-card p-5 text-center space-y-3">
                        <div class="w-12 h-12 mx-auto bg-[rgba(239,68,68,0.1)] border border-[rgba(239,68,68,0.2)] text-[#ef4444] flex items-center justify-center text-xl">
                            <i class="fas fa-dragon"></i>
                        </div>
                        <h3 class="font-bold text-white"><?php echo translate('vote_reward_mounts', 'Mounts'); ?></h3>
                        <p class="text-xs text-gray-300 leading-relaxed"><?php echo translate('vote_reward_mounts_desc', 'Gain access to exclusive mounts only available through voting.'); ?></p>
                    </div>

                    <div class="reward-card p-5 text-center space-y-3">
                        <div class="w-12 h-12 mx-auto bg-[rgba(52,152,219,0.1)] border border-[rgba(52,152,219,0.2)] text-[#3498db] flex items-center justify-center text-xl">
                            <i class="fas fa-gem"></i>
                        </div>
                        <h3 class="font-bold text-white"><?php echo translate('vote_reward_vip_points', 'VIP Points'); ?></h3>
                        <p class="text-xs text-gray-300 leading-relaxed"><?php echo translate('vote_reward_vip_points_desc', 'Earn points to redeem for special items and perks.'); ?></p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Redirect Modal - UNCHANGED -->
<div class="modal-overlay" id="voteModal">
    <div class="modal-content">
        <div class="w-16 h-16 mx-auto rounded-full bg-[rgba(242,207,82,0.15)] border border-[rgba(201,162,39,0.3)] text-[#f2cf5b] flex items-center justify-center text-3xl mb-4">
            <i class="fas fa-circle-check"></i>
        </div>
        <div class="space-y-2">
            <h3 class="text-2xl font-bold text-white"><?php echo translate('vote_modal_title', 'Thank You for Voting!'); ?></h3>
            <p class="text-sm text-gray-300">
                <?php echo translate('vote_modal_message', 'You\'re being redirected to <span class="site-name font-bold text-[#f2cf5b]"></span> to complete your vote.'); ?>
            </p>
        </div>
        <button onclick="closeModal()" class="w-full py-3 px-6 bg-[rgba(242,207,82,0.15)] border border-[rgba(201,162,39,0.3)] text-[#f2cf5b] hover:bg-[rgba(242,207,82,0.25)] transition-all duration-300 font-bold mt-4">
            <?php echo translate('vote_modal_button', 'Continue'); ?>
        </button>
    </div>
</div>

<?php include_once $project_root . 'includes/footer.php'; ?>

<script>
    const basePath = '<?php echo addslashes($base_path); ?>';

    document.addEventListener('DOMContentLoaded', function() {
        const voteButtons = document.querySelectorAll('.vote-btn');
        const modalOverlay = document.getElementById('voteModal');
        const modalSiteName = modalOverlay?.querySelector('.site-name');
        const messageBox = document.querySelector('.message-box');

        voteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                // Check if button is disabled
                if (this.hasAttribute('disabled')) {
                    e.preventDefault();
                    if (messageBox) {
                        messageBox.textContent = '<?php echo translate('vote_unauthorized', 'Cannot vote during cooldown.'); ?>';
                        messageBox.className = 'message-box p-4 border text-sm md:text-base text-center font-medium block bg-red-900/40 border-red-600/40 text-red-200 mb-6';
                        setTimeout(() => {
                            messageBox.classList.add('hidden');
                            messageBox.classList.remove('block');
                        }, 5000);
                    }
                    return;
                }
                
                // Show modal popup
                e.preventDefault();
                if (!modalOverlay || !modalSiteName) return;
                
                const siteName = this.getAttribute('data-site-name');
                const siteUrl = this.getAttribute('href');
                
                // Set site name in modal
                modalSiteName.textContent = siteName;
                
                // Show modal
                modalOverlay.classList.add('show');
                
                // Open vote URL in new tab after delay
                setTimeout(() => {
                    window.open(siteUrl, '_blank');
                    modalOverlay.classList.remove('show');
                }, 2000);
            });
        });

        // Close modal function
        window.closeModal = function() {
            const modalOverlay = document.getElementById('voteModal');
            if (modalOverlay) {
                modalOverlay.classList.remove('show');
            }
        };

        // Close modal on overlay click
        document.addEventListener('click', function(e) {
            const modalOverlay = document.getElementById('voteModal');
            if (modalOverlay && e.target === modalOverlay) {
                modalOverlay.classList.remove('show');
            }
        });

        // Cooldown timer logic
        const timers = document.querySelectorAll('.cooldown-timer');
        timers.forEach(timer => {
            let remainingSeconds = parseInt(timer.getAttribute('data-remaining-seconds'));

            if (remainingSeconds > 0) {
                const interval = setInterval(() => {
                    if (remainingSeconds <= 0) {
                        clearInterval(interval);
                        timer.innerHTML = '<span class="text-[#2ecc71] font-semibold"><i class="fas fa-circle-check mr-1"></i><?php echo translate('vote_cooldown_ready', 'Ready to vote!'); ?></span>';
                        const voteCard = timer.closest('.vote-card');
                        const voteBtn = voteCard ? voteCard.querySelector('.vote-btn') : null;
                        if (voteBtn) {
                            voteBtn.removeAttribute('disabled');
                        }
                        return;
                    }

                    const hours = Math.floor(remainingSeconds / 3600);
                    const minutes = Math.floor((remainingSeconds % 3600) / 60);
                    const seconds = remainingSeconds % 60;
                    timer.textContent = `Cooldown: ${hours}h ${minutes}m ${seconds}s`;
                    remainingSeconds--;
                }, 1000);
            }
        });

        // Claim rewards function
        window.claimRewards = function(userId, siteId, csrfToken) {
            const messageBox = document.querySelector('.message-box');
            
            fetch(`${basePath}pages/pingback/claim.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `user_id=${encodeURIComponent(userId)}&site_id=${encodeURIComponent(siteId)}&csrf_token=${encodeURIComponent(csrfToken)}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        if (response.status === 200) {
                            return {
                                status: 'success',
                                message: '<?php echo translate('vote_claim_success', 'Rewards claimed successfully!'); ?>'
                            };
                        }
                        throw new Error(`Invalid response: ${text}`);
                    }
                });
            })
            .then(data => {
                if (!messageBox) {
                    if (data.status === 'success') location.reload();
                    return;
                }
                messageBox.textContent = data.message || '<?php echo translate('vote_claim_success', 'Rewards claimed successfully!'); ?>';
                const isError = data.status === 'error';
                messageBox.className = `message-box p-4 border text-sm md:text-base text-center font-medium block mb-6 ${isError ? 'bg-red-900/40 border-red-600/40 text-red-200' : 'bg-green-900/40 border-green-600/40 text-green-200'}`;
                
                setTimeout(() => {
                    messageBox.classList.add('hidden');
                    messageBox.classList.remove('block');
                    if (data.status === 'success') {
                        location.reload();
                    }
                }, 3000);
            })
            .catch(error => {
                console.error('Claim error:', error);
                if (!messageBox) return;
                messageBox.textContent = '<?php echo translate('vote_claim_error', 'Error claiming rewards: ') ?>' + error.message;
                messageBox.className = 'message-box p-4 border text-sm md:text-base text-center font-medium block bg-red-900/40 border-red-600/40 text-red-200 mb-6';
                setTimeout(() => {
                    messageBox.classList.add('hidden');
                    messageBox.classList.remove('block');
                }, 5000);
            });
        };
    });
</script>
</body>
</html>