<?php
return [
    // Page meta
    'page_description_vote_sites' => 'Verwaltung von Abstimmungsseiten für den Sahtout WoW-Server',
    'page_title_manage_vote_sites' => 'Abstimmungsseiten verwalten',

    // Errors
    'err_fix_errors' => 'Bitte beheben Sie die folgenden Fehler:',
    'err_invalid_csrf' => 'Ungültiges CSRF-Token.',
    'err_permission_denied' => 'Zugriff verweigert.',
    'err_database' => 'Datenbankfehler: ',
    'err_vote_site_not_found' => 'Abstimmungsseite nicht gefunden.',
    'err_callback_file_name_required' => 'Callback-Dateiname ist erforderlich.',
    'err_invalid_callback_file_name' => 'Callback-Dateiname muss alphanumerisch mit Unterstrichen oder Bindestrichen sein.',
    'err_callback_file_name_too_long' => 'Callback-Dateiname darf 50 Zeichen nicht überschreiten.',
    'err_callback_file_name_exists' => 'Callback-Dateiname existiert bereits.',
    'err_siteid_required' => 'Site-ID ist erforderlich.',
    'err_siteid_too_long' => 'Site-ID darf 255 Zeichen nicht überschreiten.',
    'err_url_format_required' => 'URL-Format für Abstimmung ist erforderlich.',
    'err_url_format_too_long' => 'URL-Format darf 255 Zeichen nicht überschreiten.',
    'err_site_name_required' => 'Seitenname ist erforderlich.',
    'err_site_name_too_long' => 'Seitenname darf 50 Zeichen nicht überschreiten.',
    'err_invalid_image_url' => 'Button-Bild-URL ist zu lang.',
    'err_invalid_cooldown' => 'Abklingzeit muss zwischen 1 und 999 Stunden liegen.',
    'err_invalid_reward' => 'Belohnungspunkte müssen zwischen 1 und 999 liegen.',
    'err_callback_secret_too_long' => 'Callback-Geheimnis darf 64 Zeichen nicht überschreiten.',
    'err_image_too_large' => 'Bildgröße darf 1 MB nicht überschreiten.',
    'err_image_upload_failed' => 'Bild-Upload fehlgeschlagen: ',
    'err_invalid_image_type' => 'Nur JPEG-, PNG- und GIF-Bilder sind erlaubt.',

    // Success messages
    'msg_vote_site_saved' => 'Abstimmungsseite erfolgreich gespeichert!',
    'msg_vote_site_deleted' => 'Abstimmungsseite erfolgreich gelöscht!',
    'msg_image_deleted' => 'Bild erfolgreich gelöscht!',
    'msg_no_vote_sites' => 'Keine Abstimmungsseiten verfügbar.',

    // Section titles
    'title_edit_vote_site' => 'Abstimmungsseite bearbeiten',
    'title_add_vote_site' => 'Abstimmungsseite hinzufügen',
    'title_vote_sites_list' => 'Liste der Abstimmungsseiten',

    // Labels
    'label_callback_file_name' => 'Callback-Dateiname',
    'label_callback_file_name_info' => 'Name zur Identifizierung der Abstimmungsseite in Callbacks (gtop100, top100arena, etc.)',
    'placeholder_callback_file_name' => 'Callback-Dateinamen eingeben (z.B. arenaTop100)',
    'label_site_name' => 'Seitenname',
    'placeholder_site_name' => 'Seitenname eingeben',
    'label_siteid' => 'Site-ID',
    'placeholder_siteid' => 'Server-ID auf der Abstimmungsseite eingeben',
    'label_siteid_info' => 'Die eindeutige ID Ihres Servers auf der Abstimmungsseite (z.B. SahtoutServer, 12345).',
    'label_url_format' => 'URL-Format für Abstimmung',
    'placeholder_url_format' => 'z.B. https://site.com/vote/{siteid}/{userid}',
    'label_url_format_info' => 'Verwenden Sie {siteid}, {userid} oder {username} als Platzhalter.',
    'label_button_image' => 'Button-Bild hochladen',
    'placeholder_button_image' => 'Klicken oder ziehen Sie, um ein Button-Bild hochzuladen',
    'label_button_image_url' => 'Button-Bild-URL (Optional)',
    'placeholder_button_image_url' => 'Button-Bild-URL eingeben (optional)',
    'label_image_url_info' => 'Geben Sie eine Bild-URL ein, wenn Sie kein Bild hochladen möchten. Leer lassen, um das Bild zu entfernen.',
    'label_cooldown_hours' => 'Abklingzeit (Stunden)',
    'placeholder_cooldown_hours' => 'Abklingzeit in Stunden eingeben',
    'label_reward_points' => 'Belohnungspunkte',
    'placeholder_reward_points' => 'Belohnungspunkte eingeben',
    'label_uses_callback' => 'Verwendet Callback',
    'label_callback_secret' => 'Callback-Geheimnis',
    'placeholder_callback_secret' => 'Callback-Geheimnis eingeben (optional)',
    'label_actions' => 'Aktionen',
    'label_no_image' => 'Kein Bild',

    // Options
    'option_yes' => 'Ja',
    'option_no' => 'Nein',

    // Buttons
    'btn_save_vote_site' => 'Abstimmungsseite speichern',
    'btn_reset' => 'Formular zurücksetzen',
    'btn_edit' => 'Bearbeiten',
    'btn_delete' => 'Löschen',
    'btn_delete_image' => 'Bild löschen',
    'btn_cancel' => 'Abbrechen',
    'btn_confirm_delete' => 'Löschen bestätigen',

    // Delete Modal
    'confirm_delete_title' => 'Löschen bestätigen',
    'confirm_delete_vote_site' => 'Sind Sie sicher, dass Sie diese Abstimmungsseite löschen möchten?',
    'confirm_delete_irreversible' => 'Diese Aktion kann nicht rückgängig gemacht werden.',
    'confirm_delete_image' => 'Sind Sie sicher, dass Sie dieses Bild löschen möchten?',
];
?>