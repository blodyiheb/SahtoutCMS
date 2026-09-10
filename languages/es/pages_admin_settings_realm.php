<?php
return [
    // Page meta
    'page_description_realm' => 'Configuración del reino para el servidor WoW Sahtout',
    'page_title_realm' => 'Configuración del reino',

    // Errors
    'err_fix_errors' => 'Por favor, corrija los siguientes errores:',
    'err_invalid_csrf' => 'Token CSRF inválido.',
    'err_realm_name_required' => 'El nombre del reino es obligatorio.',
    'err_realm_ip_required' => 'La dirección del reino / Host es obligatoria.',
    'err_realm_ip_invalid' => 'La dirección del reino / Host no es válida.',
    'err_realm_check_address_required' => 'La dirección de verificación de estado es obligatoria.',
    'err_realm_check_address_invalid' => 'La dirección de verificación de estado contiene caracteres no válidos.',
    'err_realm_port_invalid' => 'El puerto del reino debe ser un número válido (1-65535).',
    'error_realm_logo_too_large' => 'El tamaño del logo del reino excede 2 MB.',
    'error_invalid_realm_logo_type' => 'Tipo de archivo inválido. Solo se permiten PNG, JPG o WebP.',
    'error_realm_logo_upload_failed' => 'El directorio de carga no es accesible o no tiene permisos de escritura.',
    'err_config_dir_not_writable' => 'El directorio de configuración no tiene permisos de escritura: %s',
    'err_write_realm_config' => 'No se puede escribir el archivo de configuración del reino: %s',

    // Success
    'msg_realm_saved' => '¡Configuración del reino guardada con éxito!',

    // Section titles
    'section_realm_config' => 'Configuración del reino',

    // Labels
    'label_current_logo' => 'Logo actual',
    'label_realm_name' => 'Nombre del reino',
    'placeholder_realm_name' => 'Ingrese el nombre del reino',
    'label_realm_ip' => 'Dirección del reino / Host',
    'label_realm_check_address' => 'Dirección de verificación de estado',
    'placeholder_realm_address' => 'play.example.com',
    'placeholder_realm_check_address' => '127.0.0.1',
    'note_realm_check_address' => 'Se usa internamente en el sitio web para comprobar si el servidor está en línea. Normalmente 127.0.0.1 cuando el sitio y el servidor del juego están en la misma máquina.',
    'label_realm_port' => 'Puerto del reino',
    'label_player_display' => 'Visualización de jugadores',
    'player_display_all' => 'Todos los jugadores',
    'player_display_all_desc' => 'Mostrar humanos y bots juntos como un único recuento de jugadores.',
    'player_display_separate' => 'Humanos / Bots',
    'player_display_separate_desc' => 'Mostrar jugadores reales y playerbots como recuentos separados.',
    'label_realm_logo' => 'Logo del reino',
    'placeholder_realm_logo' => 'Haga clic o arrastre para subir un nuevo logo',
    'note_realm_logo' => 'Suba un nuevo logo para su reino. Deje vacío para mantener el logo actual.',
    'note_realm_config' => 'Esto configura los ajustes para un solo reino.',

    // Buttons
    'btn_save_realm' => 'Guardar configuración del reino',
];
?>