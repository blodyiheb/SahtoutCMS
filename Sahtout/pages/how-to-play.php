<?php 
define('ALLOWED_ACCESS', true);
require_once '../includes/session.php';
$page_class = "how-to-play";
include("../includes/header.php"); 
?>

<link rel="stylesheet" href="../assets/css/how-to-play.css">

<div class="container how-to-play">
    <h1>How to Play</h1>
    <div class="steps">

        <!-- Step 1: Create an Account -->
        <div class="step">
            <div class="step-content">
                <div class="step-text">
                    <h2>Step 1: Create an Account</h2>
                    <p>Register a free account using our website:</p>
                    <a class="btn" href="/sahtout/register">Create Account</a>
                </div>
                <img src="img/howtoplay/down_register.jpg" alt="Create Account">
            </div>
        </div>

        <!-- Step 2: Download the Game -->
        <div class="step">
            <div class="step-content">
                <img src="img/howtoplay/down_download.png" alt="Download Game">
                <div class="step-text">
                    <h2>Step 2: Download the Game</h2>
                    <p>You need World of Warcraft: Wrath of the Lich King (3.3.5a). Choose your preferred download method:</p>
                    <div class="download-options">
                        <a class="btn btn-primary" href="download/">Direct Download</a>
                        <a class="btn btn-secondary" href="download">Torrent Download</a>
                    </div>
                    <p><small>Direct downloads are faster for most users, while torrents may be more reliable for slower connections.</small></p>
                </div>
            </div>
        </div>

        <!-- Step 3: Set the Realmlist -->
        <div class="step">
            <div class="step-content">
                <div class="step-text">
                    <h2>Step 3: Set the Realmlist</h2>
                    <p>Open your World of Warcraft folder, go to <code><strong>Data/enUS</strong></code> or <code><strong>Data/enGB</strong></code>, and find <code>realmlist.wtf</code>.</p>
                    <p>Open it with Notepad and replace everything inside with:</p>
                    <pre>set realmlist logon.sahtout.tn</pre>
                    <p>Save the file and close it.</p>
                </div>
                <img id="down_img_realm" src="img/howtoplay/down_realmlist.png" alt="Edit Realmlist">
            </div>
        </div>

        <!-- Step 4: Launch the Game -->
        <div class="step">
            <div class="step-content">
                <div class="step-text">
                    <h2>Step 4: Start Playing!</h2>
                    <p>Open <code>Wow.exe</code> (not Launcher.exe) and log in using your account credentials.</p>
                    <p>Enjoy your adventure on our server!</p>
                </div>
                <img src="img/howtoplay/down_wow.png" alt="Launch WoW">
            </div>
        </div>
    </div>
</div>

<?php include("../includes/footer.php"); ?>