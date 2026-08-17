<?php
return [
    // Page meta
    'page_description_soap' => 'SOAP-Einstellungen für den Sahtout WoW-Server',
    'title_soap_settings' => 'SOAP-Einstellungen',

    // Status
    'status' => 'Status:',
    'status_soap_configured' => 'SOAP konfiguriert',
    'status_soap_not_configured' => 'SOAP nicht konfiguriert',

    // Errors
    'error_box_title' => 'Bitte beheben Sie die folgenden Fehler:',
    'error_soap_url_required' => 'SOAP-URL ist erforderlich.',
    'error_soap_user_required' => 'GM-Konto-Benutzername ist erforderlich.',
    'error_soap_pass_required' => 'SOAP-Passwort ist erforderlich.',
    'error_db_query' => 'Datenbankabfragefehler: %s',
    'error_account_not_exist' => 'Konto %s existiert nicht in der Auth-Datenbank.',
    'error_account_not_gm_level_3' => 'Konto %s existiert, ist aber nicht GM-Level 3.',
    'error_config_dir_not_writable' => 'Konfigurationsverzeichnis ist nicht beschreibbar: %s',
    'error_config_file_write_failed' => 'Konfigurationsdatei konnte nicht geschrieben werden: %s',

    // Success
    'success_soap_settings_saved' => 'SOAP-Einstellungen erfolgreich gespeichert!',

    // Section titles
    'header_soap_settings' => 'SOAP-Einstellungen',

    // Labels
    'label_soap_url' => 'SOAP-URL',
    'placeholder_soap_url' => 'z.B., http://127.0.0.1:7878',
    'help_soap_url' => 'Die URL, unter der der SOAP-Dienst Ihres WoW-Servers läuft.',
    'label_soap_user' => 'GM-Konto-Benutzername',
    'placeholder_soap_user' => 'Muss GM-Level 3 sein',
    'help_soap_user' => 'Das Konto muss in der Datenbank GM-Level 3 haben.',
    'label_soap_pass' => 'SOAP-Passwort',
    'placeholder_soap_pass' => 'SOAP-Passwort = Kontopasswort',
    'help_soap_pass' => 'Dies ist das Passwort für das oben genannte GM-Konto.',

    // Buttons
    'button_save_verify_soap' => 'SOAP speichern und verifizieren',

    // Info Box
    'info_box_title' => 'Wichtige Schritte',
    'info_step_1' => 'Stellen Sie sicher, dass das GM-Konto in Ihrer Auth-Datenbank existiert und GM-Level 3 in <code>account_access</code> mit <code>RealmID = -1</code> hat.',
    'info_step_2' => 'Öffnen Sie Ihre <code>worldserver.conf</code> und setzen Sie: <strong>SOAP.Enabled = 1</strong>',
    'info_step_3' => 'Stellen Sie sicher, dass der SOAP-Port in <code>soap_url</code> korrekt und erreichbar ist.',
];
?>