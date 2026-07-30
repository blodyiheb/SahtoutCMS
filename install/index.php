<?php
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/../includes/paths.php'; // Include paths.php
include __DIR__ . '/header.inc.php';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($langCode ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <title><?= translate('installer_title', 'SahtoutCMS Installer') ?></title>
    <style>
        html, body {
    height: 100%;
    margin: 0;
    padding: 0;
    font-family: 'Cinzel', serif;
    background: 
        linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
        url('https://www.wallpaperflare.com/static/955/944/93/fantasy-art-dark-knight-artwork-wallpaper.jpg') 
        no-repeat center center fixed;
    background-size: cover;
    color: #f0e6d2;
    display: flex;
    flex-direction: column;
}

main {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
}

.container {
    text-align: center;
    max-width: 700px;
    padding: 40px;
    border: 2px solid #d4af37;
    background: rgba(25, 15, 10, 0.85);
    border-radius: 15px;
    box-shadow: 0 0 30px rgba(212, 175, 55, 0.6);
    animation: fadeIn 1.5s ease;
}

h1 {
    font-size: 3em;
    margin-bottom: 10px;
    color: #d4af37;
    text-shadow: 0 0 20px #000, 0 0 10px #d4af37;
    animation: glow 2s infinite alternate;
}

p {
    font-size: 1.2em;
    margin-bottom: 30px;
    line-height: 1.6;
}

.btn {
    display: inline-block;
    padding: 15px 40px;
    font-size: 1.2em;
    font-weight: bold;
    color: #fff;
    background: linear-gradient(135deg, #6b4226, #a37e2c);
    border: none;
    border-radius: 10px;
    cursor: pointer;
    text-decoration: none;
    box-shadow: 0 0 15px #a37e2c;
    transition: all 0.3s ease;
}

.btn:hover {
    background: linear-gradient(135deg, #a37e2c, #d4af37);
    box-shadow: 0 0 25px #d4af37, 0 0 40px #d4af37;
    transform: scale(1.05);
}

/* Subtle fade-in effect */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Magical glowing text */
@keyframes glow {
    from { text-shadow: 0 0 10px #d4af37, 0 0 20px #a37e2c; }
    to { text-shadow: 0 0 20px #ffd700, 0 0 40px #ffea70; }
}

    </style>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600&display=swap" rel="stylesheet">
</head>
<body>
    <main>
        <div class="container">
            <h1><?= translate('installer_name', 'SahtoutCMS Installer') ?></h1>
            <h1>⚔️</h1>
            <p>
                <?= translate('welcome_message_line1', 'Welcome, adventurer.') ?><br>
                <?= translate('welcome_message_line2', 'This installer will guide you through the setup of <strong>SahtoutCMS</strong>, for World of Warcraft private servers.') ?><br><br>
                <?= translate('welcome_message_line3', 'Prepare your database credentials and your server, for the journey begins now.') ?>
            </p>
            <p style="margin-top:20px; font-size:0.9em; color:#fff; font-style:italic;">
                ⚔️ <?= translate('note_dev_info', 'Note: I created this project alone for fun, learning, and testing. While I’ve tried to make it look and feel professional, it’s not a team project, and some bugs may still exist. Enjoy exploring and give feedback if you find issues!') ?>
            </p>
            <a href="<?php echo $base_path; ?>install/step2_check" class="btn"><?= translate('btn_begin_install', 'Begin Installation') ?></a>
        </div>
    </main>

    <?php include __DIR__ . '/footer.inc.php'; ?>
</body>
</html>