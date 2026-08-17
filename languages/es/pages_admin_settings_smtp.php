<?php
return [
    // Page meta
    'page_description_smtp' => 'Configuración SMTP para el servidor WoW Sahtout',
    'page_title_smtp' => 'Configuración SMTP',

    // Status
    'status' => 'Estado:',
    'msg_smtp_enabled' => 'SMTP activado',
    'msg_smtp_disabled' => 'SMTP desactivado',

    // Errors
    'err_fix_errors' => 'Por favor, corrija los siguientes errores:',
    'err_smtp_host_required' => 'El host SMTP es obligatorio.',
    'err_smtp_user_required' => 'El usuario SMTP es obligatorio.',
    'err_smtp_pass_required' => 'La contraseña SMTP es obligatoria.',
    'err_smtp_test_failed' => 'La prueba SMTP falló:',
    'err_config_dir_not_writable' => 'El directorio de configuración no tiene permisos de escritura: %s',
    'err_failed_write_config' => 'Error al escribir el archivo de configuración: %s',
    'error_direct_access' => 'No se permite el acceso directo a este archivo.',

    // Mail test
    'mail_test_subject' => 'Correo de prueba - Sahtout CMS',
    'mail_test_body' => 'Este es un correo de prueba desde la configuración de administración de Sahtout CMS.',

    // Success
    'msg_smtp_saved' => '¡Configuración SMTP guardada con éxito!',

    // Section titles
    'settings_smtp' => 'Configuración SMTP',

    // Labels
    'label_smtp_enabled' => 'Activar SMTP',
    'help_smtp_enabled' => 'Active para enviar correos mediante servidor SMTP.',
    'label_smtp_host' => 'Host SMTP',
    'placeholder_smtp_host' => 'ej., smtp.gmail.com',
    'label_email_address' => 'Correo electrónico',
    'placeholder_email' => 'ej., tunombre@gmail.com',
    'label_app_password' => 'Contraseña de aplicación / SMTP',
    'placeholder_app_password' => 'Contraseña de aplicación para Gmail/Outlook',
    'help_smtp_pass' => 'Para Gmail, use una contraseña de aplicación. Para otros proveedores, use su contraseña de correo.',
    'label_from_email' => 'Correo remitente',
    'placeholder_from_email' => 'ej., noreply@tudominio.com',
    'label_from_name' => 'Nombre remitente',
    'placeholder_from_name' => 'ej., Cuenta Sahtout',
    'label_port' => 'Puerto',
    'placeholder_port_tls_ssl' => '587 para TLS',
    'label_encryption' => 'Cifrado',
    'help_smtp_secure' => 'La mayoría de los proveedores usan TLS en el puerto 587.',

    // Buttons
    'btn_save_test_smtp' => 'Guardar y probar SMTP',
];
?>