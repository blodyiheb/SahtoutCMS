<?php
return [
    // Success Messages
    'msg_reward_claimed' => 'Belohnung erfolgreich für %s eingelöst. +%d Punkte wurden Ihrem Konto hinzugefügt.',

    // Error Messages
    'err_invalid_csrf' => 'Ungültiges CSRF-Token.',
    'err_invalid_user_id' => 'Ungültige Benutzer-ID.',
    'err_user_not_found' => 'Benutzer-ID nicht in user_currencies gefunden.',
    'err_site_not_found' => 'Seiten-ID nicht in vote_sites gefunden: %s.',
    'err_no_unclaimed_votes' => 'Keine nicht eingelösten Stimmen für Benutzer verfügbar: %s.',
    'err_database_generic' => 'Datenbankfehler: %s',
    'err_db_connection_failed' => 'Verbindung zur Datenbank fehlgeschlagen.',
];
?>