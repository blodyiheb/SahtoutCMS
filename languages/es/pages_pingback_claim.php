<?php
return [
    // Success Messages
    'msg_reward_claimed' => 'Recompensa reclamada con éxito para %s. +%d puntos han sido añadidos a tu cuenta.',

    // Error Messages
    'err_invalid_csrf' => 'Token CSRF inválido.',
    'err_invalid_user_id' => 'ID de usuario inválido.',
    'err_user_not_found' => 'ID de usuario no encontrado en user_currencies.',
    'err_site_not_found' => 'ID del sitio no encontrado en vote_sites: %s.',
    'err_no_unclaimed_votes' => 'No hay votos no reclamados disponibles para el usuario: %s.',
    'err_database_generic' => 'Error de base de datos: %s',
    'err_db_connection_failed' => 'Fallo en la conexión a la base de datos.',
];
?>