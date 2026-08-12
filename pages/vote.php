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
    <style>
        /* Lighter Blue/Purple Background - Only this changed */
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #2d1b69 100%);
            min-height: 100vh;
        }

        /* Glassmorphism Panels */
        .glass-panel {
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
        }

        .vote-card {
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            transition: all 0.3s ease;
        }

        .vote-card:hover {
            transform: translateY(-4px);
            background: rgba(15, 23, 42, 0.65);
            box-shadow: 0 16px 32px rgba(0, 0, 0, 0.5);
            border-color: rgba(251, 191, 36, 0.4);
        }

        .reward-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 16px;
            transition: all 0.3s ease;
        }

        .reward-card:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-3px);
            border-color: rgba(251, 191, 36, 0.3);
        }

        .btn-vote {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.95) 0%, rgba(217, 119, 6, 0.95) 100%);
            color: #ffffff;
            font-weight: 700;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35);
        }

        .btn-vote:hover:not([disabled]) {
            background: linear-gradient(135deg, rgba(251, 191, 36, 1) 0%, rgba(245, 158, 11, 1) 100%);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.5);
            transform: translateY(-1px);
        }

        .btn-vote[disabled] {
            background: rgba(255, 255, 255, 0.08);
            color: #6b7280;
            box-shadow: none;
            cursor: not-allowed;
        }

        .btn-claim {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.95) 0%, rgba(5, 150, 105, 0.95) 100%);
            color: #ffffff;
            font-weight: 700;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
        }

        .btn-claim:hover:not([disabled]) {
            background: linear-gradient(135deg, rgba(52, 211, 153, 1) 0%, rgba(16, 185, 129, 1) 100%);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5);
            transform: translateY(-1px);
        }

        .btn-claim[disabled] {
            background: rgba(255, 255, 255, 0.05);
            color: #4b5563;
            box-shadow: none;
            cursor: not-allowed;
        }

        .message-box {
            display: none;
            transition: all 0.3s ease;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .message-box.show {
            display: block;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 50;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.show {
            display: flex;
        }
    </style>
</head>
<body class="text-white min-h-screen relative selection:bg-amber-500 selection:text-slate-900">

    <!-- Main Content Wrapper -->
    <div class="vote-content relative z-10 min-h-screen flex items-start justify-center px-4 md:px-8 py-8">
        <div class="container mx-auto max-w-7xl px-2 sm:px-4">
            
            <!-- Glassmorphism Container -->
            <div class="glass-panel p-6 md:p-10 space-y-8">
                
                <!-- Page Header -->
                <div class="text-center space-y-3">
                    <a href="<?php echo htmlspecialchars($base_path, ENT_QUOTES, 'UTF-8'); ?>" class="inline-block transition-transform hover:scale-105">
                        <img src="<?php echo htmlspecialchars($base_path . $site_logo, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo translate('site_logo_alt', 'Sahtout Server Logo'); ?>" class="h-16 md:h-20 mx-auto object-contain drop-shadow-lg">
                    </a>
                    <h1 class="text-3xl md:text-5xl font-extrabold text-white tracking-tight drop-shadow-md">
                        <i class="fas fa-check-to-slot text-amber-400 mr-2"></i>
                        <?php echo translate('vote_title_h1', 'Vote for Epic Rewards'); ?>
                    </h1>
                    <p class="text-gray-300 text-sm md:text-base max-w-2xl mx-auto drop-shadow">
                        <?php echo translate('vote_subtitle', 'Support our server by voting on top sites and earn exclusive in-game rewards!'); ?>
                    </p>
                </div>

                <!-- Login Prompt Banner -->
                <?php if (empty($username)): ?>
                    <div class="bg-amber-500/10 backdrop-blur-md border border-amber-500/20 rounded-xl p-4 text-center text-amber-200 text-sm md:text-base flex items-center justify-center gap-2">
                        <i class="fas fa-info-circle text-amber-400 text-lg"></i>
                        <span>
                            <?php echo translate('vote_login_prompt', 'Please log in to vote and earn rewards.'); ?>
                            <a href="<?php echo htmlspecialchars($base_path . 'login', ENT_QUOTES, 'UTF-8'); ?>" class="font-bold underline text-amber-400 hover:text-amber-300 ml-1">Log in now</a>
                        </span>
                    </div>
                <?php endif; ?>

                <!-- Dynamic Message Box -->
                <div class="message-box p-4 rounded-xl border text-sm md:text-base text-center font-medium <?php echo htmlspecialchars($claim_message_type, ENT_QUOTES, 'UTF-8') === 'error' ? 'bg-red-500/20 border-red-500/30 text-red-200' : 'bg-emerald-500/20 border-emerald-500/30 text-emerald-200'; ?> <?php echo $claim_message ? 'show' : ''; ?>">
                    <?php echo $claim_message; ?>
                </div>

                <!-- Vote Sites Grid -->
                <div class="space-y-4">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2 drop-shadow">
                        <i class="fas fa-list-check text-amber-400"></i>
                        <span>Available Voting Sites</span>
                    </h2>

                    <?php if (empty($voteSites)): ?>
                        <div class="text-center py-12 text-gray-400 rounded-xl bg-white/5 backdrop-blur-md border border-white/10">
                            <i class="fas fa-ghost text-4xl mb-3 block text-gray-500"></i>
                            <p><?php echo translate('vote_no_sites', 'No vote sites available at the moment.'); ?></p>
                        </div>
                    <?php else: ?>
                        <!-- 3 columns grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($voteSites as $site): ?>
                                <div class="vote-card p-5 flex flex-col justify-between space-y-4">
                                    <!-- Card Header & Image -->
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between gap-2">
                                            <h3 class="font-bold text-lg text-white truncate drop-shadow" title="<?php echo htmlspecialchars($site['site_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars($site['site_name'], ENT_QUOTES, 'UTF-8'); ?>
                                            </h3>
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-400/10 backdrop-blur-md text-amber-300 border border-amber-400/20 whitespace-nowrap">
                                                <i class="fas fa-coins text-amber-400"></i>
                                                <?php echo htmlspecialchars($site['reward_points'], ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </div>

                                        <!-- Larger Image Container -->
                                        <div class="h-32 bg-black/30 rounded-lg p-3 border border-white/5 flex items-center justify-center overflow-hidden">
                                            <img src="<?php echo htmlspecialchars($site['button_image_url'] ?? $base_path . 'img/default.png', ENT_QUOTES, 'UTF-8'); ?>" 
                                                 alt="<?php echo translate('vote_site_image_alt', 'Voting Site') . ': ' . htmlspecialchars($site['site_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                                 class="max-h-full max-w-full object-contain filter drop-shadow-md">
                                        </div>
                                    </div>

                                    <!-- Action Controls -->
                                    <div class="space-y-3 pt-2 border-t border-white/10">
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
                                               class="vote-btn btn-vote py-2.5 px-4 rounded-lg text-center text-sm inline-flex items-center justify-center gap-2" 
                                               target="_blank" 
                                               data-site-name="<?php echo htmlspecialchars($site['site_name'], ENT_QUOTES, 'UTF-8'); ?>" 
                                               <?php echo (empty($username) && $site['uses_callback']) || $site['is_on_cooldown'] ? 'disabled' : ''; ?>>
                                                <i class="fas fa-external-link-alt text-xs"></i>
                                                <span><?php echo translate('vote_button', 'Vote'); ?></span>
                                            </a>

                                            <?php if ($account_id > 0): ?>
                                                <button class="claim-btn btn-claim py-2.5 px-4 rounded-lg text-center text-sm inline-flex items-center justify-center gap-2" 
                                                        onclick="claimRewards(<?php echo (int)$account_id; ?>, '<?php echo htmlspecialchars($site['callback_file_name'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>')" 
                                                        <?php echo $site['has_unclaimed_rewards'] ? '' : 'disabled'; ?>>
                                                    <i class="fas fa-gift text-xs"></i>
                                                    <span><?php echo translate('claim_button', 'Claim'); ?></span>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn-claim py-2.5 px-4 rounded-lg text-center text-sm inline-flex items-center justify-center gap-2" disabled>
                                                    <i class="fas fa-lock text-xs"></i>
                                                    <span><?php echo translate('claim_button', 'Claim'); ?></span>
                                                </button>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Status Info -->
                                        <div class="flex items-center justify-between text-xs text-gray-300 px-1 pt-1">
                                            <span class="flex items-center gap-1.5">
                                                <i class="far fa-clock text-amber-400"></i>
                                                <span><?php echo htmlspecialchars($site['cooldown_hours'], ENT_QUOTES, 'UTF-8'); ?>h <?php echo translate('vote_cooldown_label', 'cooldown'); ?></span>
                                            </span>

                                            <?php if ($account_id > 0): ?>
                                                <span class="cooldown-timer font-mono text-gray-200" 
                                                      data-site-id="<?php echo (int)$site['id']; ?>" 
                                                      data-remaining-seconds="<?php echo (int)$site['remaining_cooldown']; ?>" 
                                                      data-cooldown-hours="<?php echo (int)$site['cooldown_hours']; ?>">
                                                    <?php echo $site['is_on_cooldown'] ? translate('vote_cooldown_timer', 'Cooldown: Calculating...') : '<span class="text-emerald-400 font-sans font-semibold"><i class="fas fa-circle-check mr-1"></i>' . translate('vote_cooldown_ready', 'Ready to vote!') . '</span>'; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Rewards List Section -->
                <div class="pt-6 border-t border-white/10 space-y-6">
                    <div class="text-center space-y-1">
                        <h2 class="text-2xl font-extrabold text-white drop-shadow">
                            <?php echo translate('vote_rewards_title', 'Voting Rewards'); ?>
                        </h2>
                        <p class="text-xs md:text-sm text-gray-300">Claim valuable in-game perks every time you cast your vote</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="reward-card p-5 text-center space-y-3">
                            <div class="w-12 h-12 mx-auto rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center text-xl">
                                <i class="fas fa-coins"></i>
                            </div>
                            <h3 class="font-bold text-white"><?php echo translate('vote_reward_gold', 'Gold'); ?></h3>
                            <p class="text-xs text-gray-300 leading-relaxed"><?php echo translate('vote_reward_gold_desc', 'Receive up to 40 gold per vote to boost your in-game wealth.'); ?></p>
                        </div>

                        <div class="reward-card p-5 text-center space-y-3">
                            <div class="w-12 h-12 mx-auto rounded-full bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center text-xl">
                                <i class="fas fa-hat-wizard"></i>
                            </div>
                            <h3 class="font-bold text-white"><?php echo translate('vote_reward_enchants', 'Enchants'); ?></h3>
                            <p class="text-xs text-gray-300 leading-relaxed"><?php echo translate('vote_reward_enchants_desc', 'Unlock powerful weapon and armor enchants for your characters.'); ?></p>
                        </div>

                        <div class="reward-card p-5 text-center space-y-3">
                            <div class="w-12 h-12 mx-auto rounded-full bg-red-500/10 border border-red-500/20 text-red-400 flex items-center justify-center text-xl">
                                <i class="fas fa-dragon"></i>
                            </div>
                            <h3 class="font-bold text-white"><?php echo translate('vote_reward_mounts', 'Mounts'); ?></h3>
                            <p class="text-xs text-gray-300 leading-relaxed"><?php echo translate('vote_reward_mounts_desc', 'Gain access to exclusive mounts only available through voting.'); ?></p>
                        </div>

                        <div class="reward-card p-5 text-center space-y-3">
                            <div class="w-12 h-12 mx-auto rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center text-xl">
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

    <!-- Redirect Modal -->
    <div class="modal-overlay" id="voteModal">
        <div class="glass-panel p-8 max-w-md w-full mx-4 text-center space-y-5 border-amber-500/30">
            <div class="w-16 h-16 mx-auto rounded-full bg-amber-500/15 border border-amber-500/30 text-amber-400 flex items-center justify-center text-3xl">
                <i class="fas fa-circle-check"></i>
            </div>
            <div class="space-y-2">
                <h3 class="text-2xl font-bold text-white"><?php echo translate('vote_modal_title', 'Thank You for Voting!'); ?></h3>
                <p class="text-sm text-gray-300">
                    <?php echo translate('vote_modal_message', 'You\'re being redirected to <span class="site-name font-bold text-amber-400"></span> to complete your vote.'); ?>
                </p>
            </div>
            <button onclick="closeModal()" class="w-full py-3 px-6 rounded-xl bg-amber-500 hover:bg-amber-400 text-gray-950 font-bold transition-all shadow-lg shadow-amber-500/20">
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
                            messageBox.className = 'message-box bg-red-500/20 border border-red-500/30 text-red-200 p-4 rounded-xl text-center font-medium text-sm md:text-base show';
                            setTimeout(() => {
                                messageBox.classList.remove('show');
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
                            timer.innerHTML = '<span class="text-emerald-400 font-sans font-semibold"><i class="fas fa-circle-check mr-1"></i><?php echo translate('vote_cooldown_ready', 'Ready to vote!'); ?></span>';
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
                    messageBox.className = `message-box p-4 rounded-xl border text-sm md:text-base text-center font-medium show ${isError ? 'bg-red-500/20 border-red-500/30 text-red-200' : 'bg-emerald-500/20 border-emerald-500/30 text-emerald-200'}`;
                    
                    setTimeout(() => {
                        messageBox.classList.remove('show');
                        if (data.status === 'success') {
                            location.reload();
                        }
                    }, 3000);
                })
                .catch(error => {
                    console.error('Claim error:', error);
                    if (!messageBox) return;
                    messageBox.textContent = '<?php echo translate('vote_claim_error', 'Error claiming rewards: ') ?>' + error.message;
                    messageBox.className = 'message-box bg-red-500/20 border border-red-500/30 text-red-200 p-4 rounded-xl text-center font-medium text-sm md:text-base show';
                    setTimeout(() => {
                        messageBox.classList.remove('show');
                    }, 5000);
                });
            };
        });
    </script>
</body>
</html>