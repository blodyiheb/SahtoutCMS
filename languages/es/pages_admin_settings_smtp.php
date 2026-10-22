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
    'err_smtp_from_required' => 'El correo de origen es obligatorio.',
    'err_smtp_from_invalid' => 'El correo de origen debe ser una dirección de correo válida.',
    'err_test_email_invalid' => 'Por favor, introduzca una dirección de correo válida para el destinatario de prueba.',
    'err_composer_missing' => 'PHPMailer no está instalado. Por favor, ejecute "composer install" en el directorio raíz para habilitar la funcionalidad SMTP.',
    'err_smtp_test_failed' => 'La prueba SMTP falló:',
    'err_invalid_csrf' => 'Token CSRF no válido.',
    'err_smtp_port_invalid' => 'El puerto debe estar entre 1 y 65535.',
    'err_smtp_secure_invalid' => 'El cifrado debe ser tls, ssl o vacío.',
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
    'label_smtp_user' => 'Nombre de usuario SMTP / Correo',
    'placeholder_smtp_user' => 'ej: tunombre@gmail.com, resend, apikey',
    'label_test_email' => 'Enviar correo de prueba a',
    'placeholder_test_email' => 'Vacío = nombre de usuario SMTP si es un correo, de lo contrario la dirección de origen',
    'help_test_email' => 'A dónde se envía el correo de prueba. Déjelo en blanco para usar su nombre de usuario SMTP si es una dirección de correo, de lo contrario la dirección de origen.',
    'label_app_password' => 'Contraseña de aplicación / SMTP',
    'placeholder_app_password' => 'Contraseña de aplicación para Gmail/Outlook',
    'help_smtp_pass' => 'Déjelo en blanco para mantener la contraseña guardada actualmente. Para Gmail, use una Contraseña de aplicación; para otros proveedores, use su contraseña SMTP o clave API.',
    'label_from_email' => 'Correo remitente',
    'placeholder_from_email' => 'ej., noreply@tudominio.com',
    'label_from_name' => 'Nombre remitente',
    'placeholder_from_name' => 'ej., Cuenta Sahtout',
    'label_port' => 'Puerto',
    'placeholder_port' => '587 (TLS), 465 (SSL), 25 (sin cifrar)',
    'help_smtp_port' => 'Puertos SMTP habituales: 587 (TLS), 465 (SSL), 25 (sin cifrado). Algunos proveedores también usan 2525.',
    'label_encryption' => 'Cifrado',
    'help_smtp_secure' => 'La mayoría de los proveedores usan TLS en el puerto 587.',
    'label_allow_self_signed' => 'Permitir certificados autofirmados',
    'help_allow_self_signed' => 'Actívelo solo para desarrollo local o servidores de prueba privados con certificados SSL/TLS autofirmados. Manténgalo desactivado en producción.',

    // Buttons
    'btn_save_test_smtp' => 'Guardar y probar SMTP',
];
?>