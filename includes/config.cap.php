<?php
if (!defined('ALLOWED_ACCESS')) exit('Direct access not allowed.');
$recaptcha_enabled = false;
$recaptcha_site_key = '';
$recaptcha_secret_key = '';
define('RECAPTCHA_ENABLED', $recaptcha_enabled);
define('RECAPTCHA_SITE_KEY', $recaptcha_site_key);
define('RECAPTCHA_SECRET_KEY', $recaptcha_secret_key);
?>