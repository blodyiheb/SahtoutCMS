<?php
// Ensure this file is not accessed directly
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access not allowed.');
}
?>

<footer style="
    background: rgba(20,10,5,0.95);
    border-top: 2px solid #6b4226;
    padding: 15px 0;
    text-align: center;
    box-shadow: 0 -2px 20px #6b4226;
    position: relative;
    z-index: 10;
    font-family: 'Cinzel', serif;
">
    <p style="
        margin: 0;
        color: #f0e6d2;
        font-size: 1em;
    ">
        <?= translate('footer_connect', '🌟 Connect with Me:') ?>
        <a href="https://github.com/blodyiheb/SahtoutCMS" target="_blank" rel="noopener noreferrer" style="
            color: #d4af37;
            text-decoration: none;
            margin: 0 12px;
            transition: color 0.3s;
        "><?= translate('footer_github', 'GitHub') ?></a>
        |
        <a href="https://www.youtube.com/@Blodyone" target="_blank" rel="noopener noreferrer" style="
            color: #d4af37;
            text-decoration: none;
            margin: 0 12px;
            transition: color 0.3s;
        "><?= translate('footer_youtube', 'YouTube') ?></a>
        |
        <a href="https://discord.gg/chxXTXXQ6M" target="_blank" rel="noopener noreferrer" style="
            color: #d4af37;
            text-decoration: none;
            margin: 0 12px;
            transition: color 0.3s;
        "><?= translate('footer_discord', 'Discord') ?></a>
    </p>
    <p style="
        margin: 5px 0 0 0;
        font-size: 0.9em;
        color: #7CFC00;
    ">
        &copy; <?= (int)date('Y') ?> Sahtout CMS. <?= translate('footer_all_rights', 'All rights reserved.') ?>
    </p>
</footer>