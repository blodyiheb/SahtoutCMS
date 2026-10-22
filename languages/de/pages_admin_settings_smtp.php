<?php
return [
    // Page meta
    'page_description_smtp' => 'SMTP-Einstellungen für den Sahtout WoW-Server',
    'page_title_smtp' => 'SMTP-Einstellungen',

    // Status
    'status' => 'Status:',
    'msg_smtp_enabled' => 'SMTP aktiviert',
    'msg_smtp_disabled' => 'SMTP deaktiviert',

    // Errors
    'err_fix_errors' => 'Bitte beheben Sie die folgenden Fehler:',
    'err_smtp_host_required' => 'SMTP-Host ist erforderlich.',
    'err_smtp_user_required' => 'SMTP-Benutzername ist erforderlich.',
    'err_smtp_pass_required' => 'SMTP-Passwort ist erforderlich.',
    'err_smtp_from_required' => 'Absender-E-Mail ist erforderlich.',
    'err_smtp_from_invalid' => 'Die Absender-E-Mail muss eine gültige E-Mail-Adresse sein.',
    'err_test_email_invalid' => 'Bitte geben Sie eine gültige E-Mail-Adresse für den Testempfänger ein.',
    'err_composer_missing' => 'PHPMailer ist nicht installiert. Bitte führen Sie "composer install" im Stammverzeichnis aus, um die SMTP-Funktionalität zu aktivieren.',
    'err_smtp_test_failed' => 'SMTP-Test fehlgeschlagen:',
    'err_invalid_csrf' => 'Ungültiges CSRF-Token.',
    'err_smtp_port_invalid' => 'Der Port muss zwischen 1 und 65535 liegen.',
    'err_smtp_secure_invalid' => 'Die Verschlüsselung muss tls, ssl oder leer sein.',
    'err_config_dir_not_writable' => 'Konfigurationsverzeichnis ist nicht beschreibbar: %s',
    'err_failed_write_config' => 'Konfigurationsdatei konnte nicht geschrieben werden: %s',
    'error_direct_access' => 'Direkter Zugriff auf diese Datei ist nicht erlaubt.',

    // Mail test
    'mail_test_subject' => 'Test-E-Mail - Sahtout CMS',
    'mail_test_body' => 'Dies ist eine Test-E-Mail aus den Sahtout CMS-Admin-Einstellungen.',

    // Success
    'msg_smtp_saved' => 'SMTP-Einstellungen erfolgreich gespeichert!',

    // Section titles
    'settings_smtp' => 'SMTP-Einstellungen',

    // Labels
    'label_smtp_enabled' => 'SMTP aktivieren',
    'help_smtp_enabled' => 'Aktivieren Sie diese Option, um E-Mails über einen SMTP-Server zu senden.',
    'label_smtp_host' => 'SMTP-Host',
    'placeholder_smtp_host' => 'z.B., smtp.gmail.com',
    'label_email_address' => 'E-Mail-Adresse',
    'placeholder_email' => 'z.B., ihr.name@gmail.com',
    'label_smtp_user' => 'SMTP-Benutzername / E-Mail',
    'placeholder_smtp_user' => 'z.B., ihrname@gmail.com, resend, apikey',
    'label_test_email' => 'Test-E-Mail senden an',
    'placeholder_test_email' => 'Leer = SMTP-Benutzername, wenn es eine E-Mail ist, sonst die Absenderadresse',
    'help_test_email' => 'Wohin die Test-E-Mail gesendet wird. Leer lassen, um Ihren SMTP-Benutzernamen zu verwenden, wenn es eine E-Mail-Adresse ist, andernfalls die Absenderadresse.',
    'label_app_password' => 'App-Passwort / SMTP-Passwort',
    'placeholder_app_password' => 'App-Passwort für Gmail/Outlook',
    'help_smtp_pass' => 'Leer lassen, um das aktuell gespeicherte Passwort beizubehalten. Für Gmail verwenden Sie ein App-Passwort; für andere Anbieter Ihr SMTP-Passwort oder API-Schlüssel.',
    'label_from_email' => 'Von E-Mail',
    'placeholder_from_email' => 'z.B., noreply@ihredomain.com',
    'label_from_name' => 'Von Name',
    'placeholder_from_name' => 'z.B., Sahtout-Konto',
    'label_port' => 'Port',
    'placeholder_port' => '587 (TLS), 465 (SSL), 25 (unverschlüsselt)',
    'help_smtp_port' => 'Häufige SMTP-Ports: 587 (TLS), 465 (SSL), 25 (ohne Verschlüsselung). Manche Anbieter nutzen auch 2525.',
    'label_encryption' => 'Verschlüsselung',
    'help_smtp_secure' => 'Die meisten Anbieter verwenden TLS auf Port 587.',
    'label_allow_self_signed' => 'Selbstsignierte Zertifikate zulassen',
    'help_allow_self_signed' => 'Nur für lokale Entwicklung oder private Testserver mit selbstsignierten SSL/TLS-Zertifikaten aktivieren. In der Produktion deaktiviert lassen.',

    // Buttons
    'btn_save_test_smtp' => 'Speichern & SMTP testen',
];
?>