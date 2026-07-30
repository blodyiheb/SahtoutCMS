<?php
define('ALLOWED_ACCESS', true);

// Include paths.php using __DIR__ to access $project_root and $base_path
require_once __DIR__ . '/../includes/paths.php';

// Use $project_root for filesystem includes
require_once $project_root . 'includes/config.settings.php';
require_once $project_root . 'includes/session.php';
$page_class='vote';
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

// Fetch unclaimed rewards and latest vote timestamps (from both vote_log and vote_log_history)
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

        // Fetch latest vote timestamp for each site (from both vote_log and vote_log_history)
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    
</head>
<body>
    <div class="container">
        <div class="page-header">
            <a href="<?php echo htmlspecialchars($base_path, ENT_QUOTES, 'UTF-8'); ?>"><img src="<?php echo htmlspecialchars($base_path . $site_logo, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo translate('site_logo_alt', 'Sahtout Server Logo'); ?>" class="logo"></a>
            <h1><?php echo translate('vote_title_h1', 'Vote for Epic Rewards'); ?></h1>
            <p class="subtitle"><?php echo translate('vote_subtitle', 'Support our server by voting on top sites and earn exclusive in-game rewards!'); ?></p>
        </div>
        <?php if (empty($username)): ?>
            <p style="text-align: center; color: var(--light); font-size: 1rem; margin-bottom: 20px;">
                <?php echo translate('vote_login_prompt', 'Please log in to vote and earn rewards.'); ?>
                <a href="<?php echo htmlspecialchars($base_path . 'login', ENT_QUOTES, 'UTF-8'); ?>" style="color: var(--accent); text-decoration: underline;">Log in now</a>
            </p>
        <?php endif; ?>
        <div class="message-box <?php echo htmlspecialchars($claim_message_type, ENT_QUOTES, 'UTF-8'); ?> <?php echo $claim_message ? 'show' : ''; ?>">
            <?php echo $claim_message; ?>
        </div>
        <div class="vote-sites">
            <?php if (empty($voteSites)): ?>
                <p style="text-align: center; color: var(--light); font-size: 1rem;"><?php echo translate('vote_no_sites', 'No vote sites available at the moment.'); ?></p>
            <?php else: ?>
                <?php foreach ($voteSites as $site): ?>
                    <div class="vote-site">
                        <div class="vote-site-header">
                            <h3 class="vote-site-name"><?php echo htmlspecialchars($site['site_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        </div>
                        <div class="vote-site-image">
                            <img src="<?php echo htmlspecialchars($site['button_image_url'] ?? $base_path . 'img/default.png', ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo translate('vote_site_image_alt', 'Voting Site') . ': ' . htmlspecialchars($site['site_name'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="vote-site-content">
                            <div class="vote-site-points-container">
                                <span class="vote-site-points"><?php echo htmlspecialchars($site['reward_points'], ENT_QUOTES, 'UTF-8'); ?> <?php echo translate('vote_points_label', 'Vote Points'); ?></span>
                            </div>
                            <div class="vote-site-buttons">
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
                                <a href="<?php echo $vote_url; ?>" class="vote-btn" target="_blank" data-site-name="<?php echo htmlspecialchars($site['site_name'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo (empty($username) && $site['uses_callback']) || $site['is_on_cooldown'] ? 'disabled' : ''; ?>><?php echo translate('vote_button', 'Vote'); ?></a>
                                <?php if ($account_id > 0): ?>
                                    <button class="claim-btn" onclick="claimRewards(<?php echo (int)$account_id; ?>, '<?php echo htmlspecialchars($site['callback_file_name'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>')" <?php echo $site['has_unclaimed_rewards'] ? '' : 'disabled'; ?>><?php echo translate('claim_button', 'Claim'); ?></button>
                                <?php endif; ?>
                            </div>
                            <p class="cooldown"><?php echo htmlspecialchars($site['cooldown_hours'], ENT_QUOTES, 'UTF-8'); ?> <?php echo translate('vote_cooldown_label', 'h cooldown'); ?></p>
                            <?php if ($account_id > 0): ?>
                                <p class="cooldown-timer" data-site-id="<?php echo (int)$site['id']; ?>" data-remaining-seconds="<?php echo (int)$site['remaining_cooldown']; ?>" data-cooldown-hours="<?php echo (int)$site['cooldown_hours']; ?>">
                                    <?php echo $site['is_on_cooldown'] ? translate('vote_cooldown_timer', 'Cooldown: Calculating...') : translate('vote_cooldown_ready', 'Ready to vote!'); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="rewards-section">
            <h2 class="section-title"><?php echo translate('vote_rewards_title', 'Voting Rewards'); ?></h2>
            <div class="reward-list">
                <div class="reward-item">
                    <div class="reward-icon"><i class="fas fa-coins"></i></div>
                    <h3><?php echo translate('vote_reward_gold', 'Gold'); ?></h3>
                    <p><?php echo translate('vote_reward_gold_desc', 'Receive up to 40 gold per vote to boost your in-game wealth.'); ?></p>
                </div>
                <div class="reward-item">
                    <div class="reward-icon"><i class="fas fa-hat-wizard"></i></div>
                    <h3><?php echo translate('vote_reward_enchants', 'Enchants'); ?></h3>
                    <p><?php echo translate('vote_reward_enchants_desc', 'Unlock powerful weapon and armor enchants for your characters.'); ?></p>
                </div>
                <div class="reward-item">
                    <div class="reward-icon"><i class="fas fa-dragon"></i></div>
                    <h3><?php echo translate('vote_reward_mounts', 'Mounts'); ?></h3>
                    <p><?php echo translate('vote_reward_mounts_desc', 'Gain access to exclusive mounts only available through voting.'); ?></p>
                </div>
                <div class="reward-item">
                    <div class="reward-icon"><i class="fas fa-gem"></i></div>
                    <h3><?php echo translate('vote_reward_vip_points', 'VIP Points'); ?></h3>
                    <p><?php echo translate('vote_reward_vip_points_desc', 'Earn points to redeem for special items and perks.'); ?></p>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-overlay">
        <div class="modal-content">
            <i class="fas fa-check-circle"></i>
            <h2><?php echo translate('vote_modal_title', 'Thank You for Voting!'); ?></h2>
            <p><?php echo translate('vote_modal_message', 'You\'re being redirected to <span class="site-name"></span> to complete your vote.'); ?></p>
            <button onclick="closeModal()"><?php echo translate('vote_modal_button', 'Continue'); ?></button>
        </div>
    </div>
    <?php include_once $project_root . 'includes/footer.php'; ?>
    <script>
        // Pass PHP $base_path to JavaScript
        const basePath = '<?php echo addslashes($base_path); ?>';

        document.addEventListener('DOMContentLoaded', function() {
            const voteButtons = document.querySelectorAll('.vote-btn');
            const modalOverlay = document.querySelector('.modal-overlay');
            const modalSiteName = modalOverlay?.querySelector('.site-name');
            const messageBox = document.querySelector('.message-box');

            // Handle vote button clicks
            voteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (this.disabled) {
                        e.preventDefault();
                        if (messageBox) {
                            messageBox.textContent = '<?php echo translate('vote_unauthorized', 'Cannot vote during cooldown.'); ?>';
                            messageBox.className = 'message-box error show';
                            messageBox.style.display = 'block';
                            setTimeout(() => {
                                messageBox.classList.remove('show');
                                messageBox.style.display = 'none';
                            }, 5000);
                        }
                        return;
                    }
                    e.preventDefault();
                    if (!modalOverlay || !modalSiteName) return;
                    const siteName = this.getAttribute('data-site-name');
                    const siteUrl = this.getAttribute('href');
                    modalSiteName.textContent = siteName;
                    modalOverlay.classList.add('show');
                    setTimeout(() => {
                        window.open(siteUrl, '_blank');
                        modalOverlay.classList.remove('show');
                    }, 2000);
                });
            });

            // Close modal
            function closeModal() {
                const modalOverlay = document.querySelector('.modal-overlay');
                if (modalOverlay) {
                    modalOverlay.classList.remove('show');
                }
            }

            // Cooldown timer logic
            const timers = document.querySelectorAll('.cooldown-timer');
            timers.forEach(timer => {
                const siteId = timer.getAttribute('data-site-id');
                let remainingSeconds = parseInt(timer.getAttribute('data-remaining-seconds'));
                const cooldownHours = parseInt(timer.getAttribute('data-cooldown-hours'));

                if (remainingSeconds > 0) {
                    const interval = setInterval(() => {
                        if (remainingSeconds <= 0) {
                            clearInterval(interval);
                            timer.textContent = '<?php echo translate('vote_cooldown_ready', 'Ready to vote!'); ?>';
                            const voteBtn = timer.closest('.vote-site').querySelector('.vote-btn');
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
            function claimRewards(userId, siteId, csrfToken) {
                console.log('Claiming rewards for user:', userId, 'site:', siteId, 'CSRF:', csrfToken);
                fetch(`${basePath}pages/pingback/claim.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `user_id=${encodeURIComponent(userId)}&site_id=${encodeURIComponent(siteId)}&csrf_token=${encodeURIComponent(csrfToken)}`
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    // Log raw response text for debugging
                    return response.text().then(text => {
                        console.log('Raw response:', text);
                        try {
                            const data = JSON.parse(text);
                            return { data, response };
                        } catch (e) {
                            // If JSON parsing fails but status is 200, assume success
                            if (response.status === 200) {
                                return {
                                    data: {
                                        status: 'success',
                                        message: '<?php echo translate('vote_claim_success', 'Rewards claimed successfully!'); ?>'
                                    },
                                    response
                                };
                            }
                            throw new Error(`Invalid JSON: ${text}`);
                        }
                    });
                })
                .then(({ data, response }) => {
                    console.log('Parsed data:', data);
                    const messageBox = document.querySelector('.message-box');
                    if (!messageBox) {
                        console.error('Message box not found in DOM');
                        if (data.status === 'success') {
                            console.log('Reloading page after successful claim');
                            location.reload();
                        }
                        return;
                    }
                    messageBox.textContent = data.message || '<?php echo translate('vote_claim_success', 'Rewards claimed successfully!'); ?>';
                    messageBox.className = `message-box ${data.status} show`;
                    messageBox.style.display = 'block';
                    setTimeout(() => {
                        messageBox.classList.remove('show');
                        messageBox.style.display = 'none';
                        if (data.status === 'success') {
                            console.log('Reloading page after successful claim');
                            location.reload();
                        }
                    }, 3000);
                })
                .catch(error => {
                    console.error('Claim error:', error);
                    const messageBox = document.querySelector('.message-box');
                    if (!messageBox) {
                        console.error('Message box not found in DOM');
                        return;
                    }
                    messageBox.textContent = '<?php echo translate('vote_claim_error', 'Error claiming rewards: ') ?>' + error.message;
                    messageBox.className = 'message-box error show';
                    messageBox.style.display = 'block';
                    setTimeout(() => {
                        messageBox.classList.remove('show');
                        messageBox.style.display = 'none';
                    }, 5000);
                });
            }

            // Expose claimRewards to global scope
            window.claimRewards = claimRewards;
        });
    </script>
</body>
</html>