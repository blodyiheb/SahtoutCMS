<?php 
define('ALLOWED_ACCESS', true);
require_once '../includes/session.php';
$page_class = "how_to_play";
require_once '../includes/header.php'; 
?>

<style>
/* Container styling */
.container.how-to-play {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 2rem;
    background: linear-gradient(135deg, rgba(0, 0, 30, 0.9) 0%, rgba(10, 10, 40, 0.95) 100%);
    border: 2px solid #ecf0f1;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 50, 0.5);
    overflow: hidden; /* Prevent overflow */
}

/* Page title */
.how-to-play h1 {
    font-family: 'UnifrakturCook', cursive;
    font-size: 2.8rem;
    color: #ffd700;
    text-align: center;
    text-shadow: 0 0 10px rgba(255, 215, 0, 0.7);
    margin-bottom: 2rem;
    animation: fadeIn 1s ease-in-out;
}

/* Steps container */
.steps {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

/* Individual step */
.step {
    background: rgba(20, 20, 50, 0.85);
    border: 2px solid #3498db;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
    animation: slideIn 0.5s ease-in-out;
    transition: transform 0.3s ease;
    overflow: hidden; /* Prevent overflow */
}

.step:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 15px rgba(52, 152, 219, 0.5);
}

/* Step content (flexbox for text and image) */
.step-content {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex-wrap: wrap;
}

/* Step text */
.step-text {
    flex: 1;
    min-width: 250px; /* Reduced to prevent overflow */
    color: #ecf0f1;
}

.step-text h2 {
    font-family: 'UnifrakturCook', cursive;
    font-size: 2rem;
    color: #e67e22;
    margin-bottom: 1rem;
    text-shadow: 0 0 5px rgba(230, 126, 34, 0.5);
}

.step-text p {
    font-size: 1.1rem;
    line-height: 1.6;
    margin-bottom: 1rem;
    color: #d1d1d1;
}

.step-text p small {
    font-size: 0.9rem;
    color: #b0b0b0;
}

/* Code block styling */
.step-text pre {
    background: #1a1a1a;
    color: #00ff00;
    padding: 1rem;
    border-radius: 6px;
    font-family: 'Courier New', monospace;
    font-size: 1rem;
    margin: 1rem 0;
    border: 1px solid #3498db;
    box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
    white-space: pre-wrap; /* Ensure text wraps */
    overflow-x: auto; /* Allow horizontal scrolling if needed */
}

/* Images */
.step-content img,
.step-content img#down_img_realm {
    height: auto;
    border: 2px solid #ecf0f1;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(255, 255, 255, 0.2);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.step-content img:hover,
.step-content img#down_img_realm:hover {
    transform: scale(1.05);
    box-shadow: 0 0 15px rgba(52, 152, 219, 0.5);
}

/* Button styling */
.btn {
    display: inline-block;
    padding: 0.8rem 1.5rem;
    font-size: 1.1rem;
    font-weight: 600;
    text-decoration: none;
    color: #fff;
    border-radius: 6px;
    border: 2px solid #ecf0f1;
    transition: all 0.3s ease;
    cursor: url('/Sahtout/img/hover_wow.gif') 16 16, auto;
    box-sizing: border-box; /* Include padding in width */
}

/* Primary button */
.btn-primary {
    background: linear-gradient(135deg, #1b9bf0 0%, #2980b9 100%);
}

.btn-primary:hover {
    background: linear-gradient(135deg, #2980b9 0%, #1f618d 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(52, 152, 219, 0.5);
}

/* Secondary button */
.btn-secondary {
    background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);
}

.btn-secondary:hover {
    background: linear-gradient(135deg, #d35400 0%, #c0392b 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(211, 84, 0, 0.5);
}

/* Download options */
.download-options {
    display: flex;
    gap: 1rem;
    margin: 1rem 0;
    flex-wrap: wrap; /* Ensure buttons wrap */
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes slideIn {
    from { opacity: 0; transform: translateX(-20px); }
    to { opacity: 1; transform: translateX(0); }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .container.how-to-play {
        padding: 1rem;
        margin: 1rem;
    }

    .how-to-play h1 {
        font-size: 2rem;
    }

    .step {
        padding: 1rem;
    }

    .step-content {
        flex-direction: column;
        gap: 1rem;
        padding: 0.5rem; /* Reduced padding to prevent overflow */
    }

    .step-text {
        min-width: 100%; /* Full width on mobile */
    }

    .step-text h2 {
        font-size: 1.6rem;
    }

    .step-text p {
        font-size: 1rem;
    }

    .step-text pre {
        font-size: 0.9rem;
        padding: 0.8rem;
    }

    .step-content img,
    .step-content img#down_img_realm {
        max-width: 100%; /* Ensure no overflow */
        width: 100%; /* Fit container */
    }

    .download-options {
        flex-direction: column;
        gap: 0.5rem;
    }

    .btn {
        font-size: 1rem;
        padding: 0.6rem 1rem;
        width: 100%; /* Full width to prevent overflow */
        text-align: center;
    }
}

@media (max-width: 576px) {
    .how-to-play h1 {
        font-size: 1.8rem;
    }

    .step-text h2 {
        font-size: 1.4rem;
    }

    .step-text p {
        font-size: 0.9rem;
    }

    .step-text p small {
        font-size: 0.8rem;
    }

    .step-text pre {
        font-size: 0.8rem;
    }

    .btn {
        font-size: 0.9rem;
        padding: 0.5rem 0.8rem;
    }
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
                    <a class="btn" href="/sahtout/register"><?php echo translate('create_account', 'Create Account'); ?></a>
                </div>
                <img src="img/howtoplay/down_register.jpg" alt="<?php echo translate('create_account_alt', 'Create Account'); ?>">
            </div>
        </div>

        <!-- Step 2: Download the Game -->
        <div class="step">
            <div class="step-content">
                <div class="step-text">
                    <h2><?php echo translate('step_2_title', 'Step 2: Download the Game'); ?></h2>
                    <p><?php echo translate('step_2_desc', 'You need World of Warcraft: Wrath of the Lich King (3.3.5a). Choose your preferred download method:'); ?></p>
                    <div class="download-options">
                        <a class="btn btn-primary" href="download/"><?php echo translate('direct_download', 'Direct Download'); ?></a>
                        <a class="btn btn-secondary" href="download"><?php echo translate('torrent_download', 'Torrent Download'); ?></a>
                    </div>
                    <p><small><?php echo translate('download_note', 'Direct downloads are faster for most users, while torrents may be more reliable for slower connections.'); ?></small></p>
                </div>
                <img src="img/howtoplay/down_download.png" alt="<?php echo translate('download_game_alt', 'Download Game'); ?>">
            </div>
        </div>

        <!-- Step 3: Set the Realmlist -->
        <div class="step">
            <div class="step-content">
                <div class="step-text">
                    <h2><?php echo translate('step_3_title', 'Step 3: Set the Realmlist'); ?></h2>
                    <p><?php echo translate('step_3_desc_1', 'Open your World of Warcraft folder, go to <code><strong>Data/enUS</strong></code> or <code><strong>Data/enGB</strong></code>, and find <code>realmlist.wtf</code>.'); ?></p>
                    <p><?php echo translate('step_3_desc_2', 'Open it with Notepad and replace everything inside with:'); ?></p>
                    <pre>set realmlist 127.0.0.1</pre>
                    <p><?php echo translate('step_3_desc_3', 'Save the file and close it.'); ?></p>
                </div>
                <img id="down_img_realm" src="img/howtoplay/down_realmlist.png" alt="<?php echo translate('edit_realmlist_alt', 'Edit Realmlist'); ?>">
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
                <img src="img/howtoplay/down_wow.png" alt="<?php echo translate('launch_wow_alt', 'Launch WoW'); ?>">
            </div>
        </div>
    </div>
</div>

<?php include("../includes/footer.php"); ?>