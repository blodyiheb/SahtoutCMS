<?php
if (!defined('ALLOWED_ACCESS')) { exit('Forbidden'); }

$smtp_enabled = false;
$smtp_host = '';
$smtp_user = '';
$smtp_pass = '';
$smtp_from = 'noreply@yourdomain.com';
$smtp_name = 'Sahtout Account';
$smtp_port = 587;
$smtp_secure = 'tls';
$smtp_allow_self_signed = false;

if (!function_exists('getMailer')) {
    /**
     * Build a configured PHPMailer instance from the SMTP settings above.
     *
     * Falls back to the PHP mail() function when SMTP is disabled.
     *
     * @return \PHPMailer\PHPMailer\PHPMailer
     */
    function getMailer(): \PHPMailer\PHPMailer\PHPMailer
    {
        global $smtp_enabled, $smtp_host, $smtp_user, $smtp_pass, $smtp_from, $smtp_name, $smtp_port, $smtp_secure, $smtp_allow_self_signed;

        // Lazy-load PHPMailer so including this file never fatals when the
        // Composer dependencies have not been installed yet.
        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (!is_file($autoload)) {
            throw new \RuntimeException(
                'PHPMailer is not installed. Run "composer install" in the project root to enable email features.'
            );
        }
        require_once $autoload;

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->CharSet = 'UTF-8';
            $mail->isHTML(true);

            if (!empty($smtp_enabled)) {
                // SMTP transport (Gmail, Resend, SendGrid, Amazon SES, cPanel, ...)
                $mail->isSMTP();
                $mail->Host = $smtp_host;
                $mail->SMTPAuth = true;
                $mail->Username = $smtp_user;
                $mail->Password = $smtp_pass;
                $mail->Port = (int) $smtp_port;
                $mail->SMTPSecure = $smtp_secure === 'ssl'
                    ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                    : ($smtp_secure === 'tls'
                        ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS
                        : '');
                // Never force opportunistic TLS when encryption is set to "None"
                $mail->SMTPAutoTLS = !empty($smtp_secure);
                $mail->Timeout = 15;

                // Only bypass TLS certificate verification for local development or when explicitly enabled
                $isLocalHost = in_array(strtolower((string)$smtp_host), ['localhost', '127.0.0.1', '::1'], true)
                    || (is_string($smtp_host) && (str_ends_with(strtolower($smtp_host), '.local') || str_ends_with(strtolower($smtp_host), '.test')));
                if (!empty($smtp_allow_self_signed) || $isLocalHost) {
                    $mail->SMTPOptions = [
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true,
                        ],
                    ];
                }
            } else {
                // Fallback to the PHP mail() function when SMTP is disabled
                $mail->isMail();
            }

            // Resolve sender address: prefer valid $smtp_from unless it is the unconfigured default placeholder and $smtp_user is a valid mailbox
            $isPlaceholderFrom = empty($smtp_from) || $smtp_from === 'noreply@yourdomain.com';
            if ($isPlaceholderFrom && filter_var($smtp_user ?? '', FILTER_VALIDATE_EMAIL)) {
                $fromAddress = $smtp_user;
            } elseif (filter_var($smtp_from ?? '', FILTER_VALIDATE_EMAIL)) {
                $fromAddress = $smtp_from;
            } elseif (filter_var($smtp_user ?? '', FILTER_VALIDATE_EMAIL)) {
                $fromAddress = $smtp_user;
            } else {
                $fromAddress = !empty($smtp_from) ? $smtp_from : 'noreply@yourdomain.com';
            }
            $fromName = !empty($smtp_name) ? $smtp_name : 'Sahtout Account';
            $mail->setFrom($fromAddress, $fromName);
        } catch (\Exception $e) {
            $mail->ErrorInfo = $e->getMessage();
        }

        return $mail;
    }
}
?>