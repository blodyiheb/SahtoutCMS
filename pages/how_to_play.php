<?php 
define('ALLOWED_ACCESS', true);

// Include paths.php to access $project_root and $base_path
require_once __DIR__ . '/../includes/paths.php';

// Use $project_root for filesystem includes
require_once $project_root . 'includes/session.php';
$page_class = "how_to_play";
require_once $project_root . 'includes/header.php'; 
$realmsFile = $project_root . 'includes/realm_config.php';
$realmlistIP = '127.0.0.1'; // fallback if file missing
if (file_exists($realmsFile)) {
    include $realmsFile; // this defines $realmlist
    if (!empty($realmlist[0]['address'])) {
        $realmlistIP = $realmlist[0]['address'];
    }
}
?>

<style>
     :root{
            --bg-how-to:url('<?php echo $base_path; ?>img/backgrounds/bg-howto.jpg');
            --hover-wow-gif: url('<?php echo $base_path; ?>img/hover_wow.gif');
        }
</style>
<div class="container how-to-play">
    <h1><?php echo translate('how_to_play_title', 'How to Play'); ?></h1>
    <div class="steps">

        <!-- Step 1: Create an Account -->
        <div class="step">
            <div class="step-content">
                <div class="step-text">
                    <h2><?php echo translate('step_1_title', 'Step 1: Create an Account'); ?></h2>
                    <p><?php echo translate('step_1_desc', 'Register a free account using our website:'); ?></p>
                    <a class="btn" href="<?php echo $base_path; ?>register"><?php echo translate('create_account', 'Create Account'); ?></a>
                </div>
                <img src="<?php echo $base_path; ?>img/howtoplay/down_register.jpg" alt="<?php echo translate('create_account_alt', 'Create Account'); ?>">
            </div>
        </div>

        <!-- Step 2: Download the Game -->
        <div class="step">
            <div class="step-content">
                <div class="step-text">
                    <h2><?php echo translate('step_2_title', 'Step 2: Download the Game'); ?></h2>
                    <p><?php echo translate('step_2_desc', 'You need World of Warcraft: Wrath of the Lich King (3.3.5a). Choose your preferred download method:'); ?></p>
                    <div class="download-options">
                        <a class="btn btn-primary" href="<?php echo $base_path; ?>download/"><?php echo translate('direct_download', 'Direct Download'); ?></a>
                        <a class="btn btn-secondary" href="<?php echo $base_path; ?>download"><?php echo translate('torrent_download', 'Torrent Download'); ?></a>
                    </div>
                    <p><small><?php echo translate('download_note', 'Direct downloads are faster for most users, while torrents may be more reliable for slower connections.'); ?></small></p>
                </div>
                <img src="<?php echo $base_path; ?>img/howtoplay/down_download.png" alt="<?php echo translate('download_game_alt', 'Download Game'); ?>">
            </div>
        </div>

        <!-- Step 3: Set the Realmlist -->
        <div class="step">
            <div class="step-content">
                <div class="step-text">
                    <h2><?php echo translate('step_3_title', 'Step 3: Set the Realmlist'); ?></h2>
                    <p><?php echo translate('step_3_desc_1', 'Open your World of Warcraft folder, go to <code><strong>Data/enUS</strong></code> or <code><strong>Data/enGB</strong></code>, and find <code>realmlist.wtf</code>.'); ?></p>
                    <p><?php echo translate('step_3_desc_2', 'Open it with Notepad and replace everything inside with:'); ?></p>
                    <pre>set realmlist <?php echo htmlspecialchars($realmlistIP); ?></pre>
                    <p><?php echo translate('step_3_desc_3', 'Save the file and close it.'); ?></p>
                </div>
                <img id="down_img_realm" src="<?php echo $base_path; ?>img/howtoplay/down_realmlist.png" alt="<?php echo translate('edit_realmlist_alt', 'Edit Realmlist'); ?>">
            </div>
        </div>
        

        <!-- Step 4: Launch the Game -->
        <div class="step">
            <div class="step-content">
                <div class="step-text">
                    <h2><?php echo translate('step_4_title', 'Step 4: Start Playing!'); ?></h2>
                    <p><?php echo translate('step_4_desc_1', 'Open <code>Wow.exe</code> (not Launcher.exe) and log in using your account credentials.'); ?></p>
                    <p><?php echo translate('step_4_desc_2', 'Enjoy your adventure on our server!'); ?></p>
                </div>
                <img src="<?php echo $base_path; ?>img/howtoplay/down_wow.png" alt="<?php echo translate('launch_wow_alt', 'Launch WoW'); ?>">
            </div>
        </div>
    </div>
</div>

<?php include($project_root . 'includes/footer.php'); ?>