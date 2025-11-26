<?php
// Language file for account.php (Spanish)
return [
    // Page title
    'page_title' => ' - Cuenta : %s',

    // Dashboard and section titles
    'dashboard_title' => 'Panel de control de la cuenta',
    'section_account_info' => 'Información de la cuenta',
    'section_quick_stats' => 'Estadísticas rápidas',
    'section_your_characters' => 'Tus personajes',
    'section_account_activity' => 'Actividad de la cuenta',
    'section_change_email' => 'Cambiar correo electrónico',
    'section_change_password' => 'Cambiar contraseña',
    'section_change_avatar' => 'Cambiar avatar',
    'section_account_actions' => 'Acciones de la cuenta',

    // Tabs
    'tab_overview' => 'Resumen',
    'tab_characters' => 'Personajes',
    'tab_activity' => 'Actividad',
    'tab_security' => 'Seguridad',
    'tab_vote' => 'Votar',
    
    // Card titles
    'card_basic_info' => 'Información básica',
    'card_contact' => 'Contacto',
    'card_activity' => 'Actividad',
    'card_characters' => 'Personajes',
    'card_wealth' => 'Riqueza',

    // Labels
    'label_username' => 'Nombre de usuario',
    'label_account_id' => 'ID de la cuenta',
    'label_status' => 'Estado',
    'label_rank' => 'Rango',
    'label_online' => 'En línea',
    'label_email' => 'Correo electrónico',
    'label_expansion' => 'Expansión',
    'label_join_date' => 'Fecha de registro',
    'label_last_login' => 'Último inicio de sesión',
    'label_total_characters' => 'Total',
    'label_highest_level' => 'Nivel más alto',
    'label_total_gold' => 'Oro total',
    'label_points' => 'Puntos',
    'label_tokens' => 'Fichas',
    'label_level' => 'Nivel',
    'label_gold' => 'Oro',
    'label_select_city' => 'Seleccionar una ciudad',
    'label_current_password' => 'Contraseña actual',
    'label_new_email' => 'Nuevo correo electrónico',
    'label_new_password' => 'Nueva contraseña',
    'label_confirm_password' => 'Confirmar nueva contraseña',
    'label_select_avatar' => 'Seleccionar avatar',

    // Placeholders
    'placeholder_current_password' => 'Ingresa la contraseña actual',
    'placeholder_new_email' => 'Ingresa el nuevo correo electrónico',
    'placeholder_new_password' => 'Ingresa la nueva contraseña',
    'placeholder_confirm_password' => 'Confirma la nueva contraseña',
    'select_city_placeholder' => 'Seleccionar una ciudad',

    // Buttons and actions
    'button_admin_panel' => 'Panel de administración',
    'button_teleport' => 'Teletransportar',
    'button_update_email' => 'Actualizar correo electrónico',
    'button_change_password' => 'Cambiar contraseña',
    'button_update_avatar' => 'Actualizar avatar',
    'action_logout' => 'Cerrar sesión',
    'action_request_deletion' => 'Solicitar eliminación de cuenta',
    'action_email_changed' => 'Correo electrónico cambiado',
    'action_password_changed' => 'Contraseña cambiada',
    'action_avatar_changed' => 'Avatar cambiado',
    'action_teleport' => 'Teletransportación',

    // Statuses
    'status_banned' => 'Baneado',
    'status_frozen' => 'Congelado',
    'status_active' => 'Activo',
    'status_online' => 'En línea',
    'status_offline' => 'Desconectado',
    'ban_no_reason' => 'Sin motivo proporcionado',
    'ban_permanent' => 'Permanente',

    // GM ranks
    'gm_rank_gm' => 'Maestro de juego nivel %s%s',
    'gm_rank_admin' => 'Administrador',
    'gm_rank_moderator' => 'Moderador',
    'gm_rank_player' => 'Jugador',
    'gm_suffix_admin' => ' (S)',
    'gm_suffix_moderator' => ' (M)',
    'gm_suffix_administrator' => ' (A)',

    // Expansions
    'expansion_0' => 'Clásico',
    'expansion_1' => 'The Burning Crusade',
    'expansion_2' => 'Wrath of the Lich King',

    // Avatars (example filenames, adjust as needed)
    'avatar_user.jpg' => 'Avatar predeterminado',
    'avatar_default' => 'Avatar predeterminado',
    // Add more avatar translations as needed, e.g., 'avatar_custom1.png' => 'Avatar personalizado 1'

    // Messages
    'message_email_updated' => '¡Correo electrónico actualizado con éxito!',
    'message_password_changed' => '¡Contraseña cambiada con éxito!',
    'message_avatar_updated' => '¡Avatar actualizado con éxito!',
    'message_character_teleported' => '¡Personaje teletransportado a %s!',

    // Errors
    'error_database_connection' => 'Fallo en la conexión a la base de datos',
    'error_invalid_form_submission' => 'Envío de formulario inválido',
    'error_invalid_email_format' => 'Formato de correo electrónico inválido',
    'error_email_in_use' => 'El correo electrónico ya está en uso por otra cuenta',
    'error_account_not_found' => 'Cuenta no encontrada',
    'error_incorrect_password' => 'Contraseña actual incorrecta',
    'error_updating_email' => 'Error al actualizar el correo electrónico',
    'error_passwords_dont_match' => 'Las nuevas contraseñas no coinciden',
    'error_password_too_short' => 'La contraseña debe tener al menos 6 caracteres',
    'error_updating_password' => 'Error al actualizar la contraseña',
    'error_invalid_character_id' => 'ID de personaje inválido',
    'error_rapid_submission' => 'Por favor espera unos segundos antes de intentar de nuevo',
    'error_teleport_cooldown' => 'Teletransportación en espera. Por favor espera %s minuto%s',
    'error_character_not_found' => 'Personaje no encontrado',
    'error_character_online' => 'El personaje debe estar desconectado para teletransportarse',
    'error_invalid_destination' => 'Destino de teletransportación inválido',
    'error_teleporting_character' => 'Error al teletransportar el personaje',
    'error_logging_teleport' => 'Error al registrar la teletransportación',
    'error_invalid_avatar' => 'Avatar seleccionado inválido',
    'error_updating_avatar' => 'Error al actualizar el avatar',

    // Misc
    'email_not_set' => 'No establecido',
    'never' => 'Nunca',
    'no_characters' => 'Aún no tienes personajes.',
    'no_activity' => 'No hay actividad reciente.',
    'none' => 'N/A',
    'debug_warnings' => 'Advertencias de depuración',
    'confirm_teleport' => '¿Teletransportar este personaje?',
    'teleport_cooldown' => 'Enfriamiento de teletransportación: %s minuto%s',
    'teleport_details' => 'A %s',
    'status_icon' => 'Ícono de estado',
    'race_icon' => 'Ícono de raza',
    'class_icon' => 'Ícono de clase',
    'faction_icon' => 'Ícono de facción',
    'gold_icon' => 'Ícono de oro',
    'avatar_alt' => 'Avatar',
    'city_shattrath' => 'Shattrath',
    'city_dalaran' => 'Dalaran',
];
?>