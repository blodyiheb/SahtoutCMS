<?php
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit(translate('error_direct_access', 'Direct access to this file is not allowed.'));
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

$smtp_enabled = false;
define('SMTP_ENABLED', $smtp_enabled);

function getMailer(): PHPMailer {
    $mail = new PHPMailer(true);
    return $mail;
}
?>