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
    'err_smtp_test_failed' => 'SMTP-Test fehlgeschlagen:',
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
    'label_app_password' => 'App-Passwort / SMTP-Passwort',
    'placeholder_app_password' => 'App-Passwort für Gmail/Outlook',
    'help_smtp_pass' => 'Verwenden Sie für Gmail ein App-Passwort. Für andere Anbieter verwenden Sie Ihr E-Mail-Passwort.',
    'label_from_email' => 'Von E-Mail',
    'placeholder_from_email' => 'z.B., noreply@ihredomain.com',
    'label_from_name' => 'Von Name',
    'placeholder_from_name' => 'z.B., Sahtout-Konto',
    'label_port' => 'Port',
    'placeholder_port_tls_ssl' => '587 für TLS',
    'label_encryption' => 'Verschlüsselung',
    'help_smtp_secure' => 'Die meisten Anbieter verwenden TLS auf Port 587.',

    // Buttons
    'btn_save_test_smtp' => 'Speichern & SMTP testen',
];
?>