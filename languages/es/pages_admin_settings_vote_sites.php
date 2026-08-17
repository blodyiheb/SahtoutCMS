<?php
return [
    // Page meta
    'page_description_vote_sites' => 'Gestión de sitios de votación para el servidor WoW Sahtout',
    'page_title_manage_vote_sites' => 'Gestionar sitios de votación',

    // Errors
    'err_fix_errors' => 'Por favor, corrija los siguientes errores:',
    'err_invalid_csrf' => 'Token CSRF inválido.',
    'err_permission_denied' => 'Permiso denegado.',
    'err_database' => 'Error de base de datos: ',
    'err_vote_site_not_found' => 'Sitio de votación no encontrado.',
    'err_callback_file_name_required' => 'El nombre del archivo de devolución de llamada es obligatorio.',
    'err_invalid_callback_file_name' => 'El nombre del archivo de devolución debe ser alfanumérico con guiones bajos o guiones.',
    'err_callback_file_name_too_long' => 'El nombre del archivo de devolución no debe exceder los 50 caracteres.',
    'err_callback_file_name_exists' => 'El nombre del archivo de devolución ya existe.',
    'err_siteid_required' => 'El ID del sitio es obligatorio.',
    'err_siteid_too_long' => 'El ID del sitio no debe exceder los 255 caracteres.',
    'err_url_format_required' => 'El formato de URL de votación es obligatorio.',
    'err_url_format_too_long' => 'El formato de URL no debe exceder los 255 caracteres.',
    'err_site_name_required' => 'El nombre del sitio es obligatorio.',
    'err_site_name_too_long' => 'El nombre del sitio no debe exceder los 50 caracteres.',
    'err_invalid_image_url' => 'La URL de la imagen del botón es demasiado larga.',
    'err_invalid_cooldown' => 'Las horas de enfriamiento deben estar entre 1 y 999.',
    'err_invalid_reward' => 'Los puntos de recompensa deben estar entre 1 y 255.',
    'err_callback_secret_too_long' => 'El secreto de devolución no debe exceder los 64 caracteres.',
    'err_image_too_large' => 'El tamaño de la imagen no debe exceder 1 MB.',
    'err_image_upload_failed' => 'Error al cargar la imagen: ',
    'err_invalid_image_type' => 'Solo se permiten imágenes JPEG, PNG y GIF.',

    // Success messages
    'msg_vote_site_saved' => '¡Sitio de votación guardado con éxito!',
    'msg_vote_site_deleted' => '¡Sitio de votación eliminado con éxito!',
    'msg_image_deleted' => '¡Imagen eliminada con éxito!',
    'msg_no_vote_sites' => 'No hay sitios de votación disponibles.',

    // Section titles
    'title_edit_vote_site' => 'Editar sitio de votación',
    'title_add_vote_site' => 'Añadir sitio de votación',
    'title_vote_sites_list' => 'Lista de sitios de votación',

    // Labels
    'label_callback_file_name' => 'Nombre del archivo de devolución',
    'label_callback_file_name_info' => 'Nombre para identificar el sitio de votación en las devoluciones de llamada (gtop100, top100arena, etc.)',
    'placeholder_callback_file_name' => 'Ingrese el nombre del archivo de devolución (ej. arenaTop100)',
    'label_site_name' => 'Nombre del sitio',
    'placeholder_site_name' => 'Ingrese el nombre del sitio',
    'label_siteid' => 'ID del sitio',
    'placeholder_siteid' => 'Ingrese el ID del servidor en el sitio de votación',
    'label_siteid_info' => 'El ID único de su servidor en el sitio de votación (ej. SahtoutServer, 12345).',
    'label_url_format' => 'Formato de URL de votación',
    'placeholder_url_format' => 'ej. https://site.com/vote/{siteid}/{userid}',
    'label_url_format_info' => 'Use {siteid}, {userid} o {username} como marcadores de posición.',
    'label_button_image' => 'Subir imagen del botón',
    'placeholder_button_image' => 'Haga clic o arrastre para subir una imagen de botón',
    'label_button_image_url' => 'URL de la imagen del botón (Opcional)',
    'placeholder_button_image_url' => 'Ingrese la URL de la imagen del botón (opcional)',
    'label_image_url_info' => 'Ingrese una URL de imagen si prefiere no subir una imagen. Deje vacío para eliminar la imagen.',
    'label_cooldown_hours' => 'Horas de enfriamiento',
    'placeholder_cooldown_hours' => 'Ingrese las horas de enfriamiento',
    'label_reward_points' => 'Puntos de recompensa',
    'placeholder_reward_points' => 'Ingrese los puntos de recompensa',
    'label_uses_callback' => 'Usa devolución de llamada',
    'label_callback_secret' => 'Secreto de devolución',
    'placeholder_callback_secret' => 'Ingrese el secreto de devolución (opcional)',
    'label_actions' => 'Acciones',
    'label_no_image' => 'Sin imagen',

    // Options
    'option_yes' => 'Sí',
    'option_no' => 'No',

    // Buttons
    'btn_save_vote_site' => 'Guardar sitio de votación',
    'btn_reset' => 'Restablecer formulario',
    'btn_edit' => 'Editar',
    'btn_delete' => 'Eliminar',
    'btn_delete_image' => 'Eliminar imagen',
    'btn_cancel' => 'Cancelar',
    'btn_confirm_delete' => 'Confirmar eliminación',

    // Delete Modal
    'confirm_delete_title' => 'Confirmar eliminación',
    'confirm_delete_vote_site' => '¿Estás seguro de que deseas eliminar este sitio de votación?',
    'confirm_delete_irreversible' => 'Esta acción no se puede deshacer.',
    'confirm_delete_image' => '¿Estás seguro de que deseas eliminar esta imagen?',
];
?>