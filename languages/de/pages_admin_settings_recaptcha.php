<?php
return [
    // Page meta
    'page_description_recaptcha' => 'reCAPTCHA-Einstellungen für den Sahtout WoW-Server',
    'page_title_recaptcha' => 'reCAPTCHA-Einstellungen',

    // Status
    'status' => 'Status:',
    'msg_recaptcha_enabled' => 'reCAPTCHA aktiviert',
    'msg_recaptcha_disabled' => 'reCAPTCHA deaktiviert',

    // Errors
    'err_fix_errors' => 'Bitte beheben Sie die folgenden Fehler:',
    'err_invalid_captcha_type' => 'Ungültiger CAPTCHA-Typ ausgewählt. Nur reCAPTCHA wird unterstützt.',
    'err_recaptcha_keys_required' => 'reCAPTCHA Site-Key und Secret-Key sind erforderlich, wenn reCAPTCHA aktiviert ist.',
    'err_cap_dir_not_writable' => 'reCAPTCHA-Konfigurationsverzeichnis ist nicht beschreibbar: %s',
    'err_failed_write_cap' => 'Konfigurationsdatei für reCAPTCHA konnte nicht geschrieben werden: %s',
    'error_direct_access' => 'Direkter Zugriff nicht erlaubt.',

    // Success
    'msg_recaptcha_saved' => 'reCAPTCHA-Einstellungen erfolgreich gespeichert!',

    // Section titles
    'settings_recaptcha' => 'reCAPTCHA-Einstellungen',

    // Labels
    'label_captcha_type' => 'CAPTCHA-Typ',
    'option_recaptcha' => 'reCAPTCHA',
    'option_hcaptcha' => 'hCaptcha (Demnächst)',
    'option_other' => 'Andere (Demnächst)',
    'help_captcha_type' => 'Derzeit wird nur reCAPTCHA v2 unterstützt.',
    'label_recaptcha_enabled' => 'reCAPTCHA aktivieren',
    'help_recaptcha_enabled' => 'Aktivieren Sie diese Option, um Formulare vor Spam und Bots zu schützen.',
    'label_recaptcha_site_key' => 'Site-Key',
    'placeholder_recaptcha_default' => 'Leer lassen für Standard-Testkeys',
    'help_recaptcha_site_key' => 'Ihr reCAPTCHA Site-Key aus der Google reCAPTCHA-Konsole.',
    'label_recaptcha_secret_key' => 'Secret-Key',
    'help_recaptcha_secret_key' => 'Ihr reCAPTCHA Secret-Key aus der Google reCAPTCHA-Konsole.',
    'note_recaptcha_empty' => 'Lassen Sie die reCAPTCHA-Felder leer, um bei Aktivierung die Standard-Testkeys zu verwenden. (Diese funktionieren für Tests, sollten aber in der Produktion ersetzt werden.)',

    // Buttons
    'btn_save_recaptcha' => 'reCAPTCHA-Einstellungen speichern',
];
?>