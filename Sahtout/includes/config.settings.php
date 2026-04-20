<?php
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access not allowed.');
}

// Site Title (Editable from Admin Panel)
$site_title_name = 'SahtoutCMS';

// Featured YouTube video (Editable from Admin Panel)
$youtube_embed_url = 'https://www.youtube.com/embed/DjuN1dE50VI?rel=0&modestbranding=1';
$youtube_title = 'Sahtout Server Trailer';
$youtube_description = 'Lichking Trailer, Replace it with your own ....';

// Logo
$site_logo = 'img/logo.png';

// Social links
$social_links = [
    'facebook' => 'https://facebook.com/blodyiheb',
    'twitter' => 'https://x.com/blodyiheb',
    'tiktok' => 'https://tiktok.com/blodyiheb',
    'youtube' => 'https://www.youtube.com/@Blodyone',
    'discord' => 'https://discord.gg/chxXTXXQ6M',
    'twitch' => 'https://twitch.tv',
    'kick' => 'https://kick.com/blodyiheb',
    'instagram' => 'https://instagram.com',
    'github' => 'https://github.com/blodyiheb/SahtoutCMS',
    'linkedin' => 'https://linkedin.com',
];
