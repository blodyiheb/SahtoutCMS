<?php
return [
    // Page meta
    'page_description_recaptcha' => 'Configuración de reCAPTCHA para el servidor WoW Sahtout',
    'page_title_recaptcha' => 'Configuración de reCAPTCHA',

    // Status
    'status' => 'Estado:',
    'msg_recaptcha_enabled' => 'reCAPTCHA activado',
    'msg_recaptcha_disabled' => 'reCAPTCHA desactivado',

    // Errors
    'err_fix_errors' => 'Por favor, corrija los siguientes errores:',
    'err_invalid_captcha_type' => 'Tipo de CAPTCHA inválido seleccionado. Solo se admite reCAPTCHA.',
    'err_recaptcha_keys_required' => 'La clave del sitio y la clave secreta de reCAPTCHA son obligatorias cuando reCAPTCHA está habilitado.',
    'err_cap_dir_not_writable' => 'El directorio de configuración de reCAPTCHA no tiene permisos de escritura: %s',
    'err_failed_write_cap' => 'Error al escribir el archivo de configuración de reCAPTCHA: %s',
    'error_direct_access' => 'Acceso directo no permitido.',

    // Success
    'msg_recaptcha_saved' => '¡Configuración de reCAPTCHA guardada con éxito!',

    // Section titles
    'settings_recaptcha' => 'Configuración de reCAPTCHA',

    // Labels
    'label_captcha_type' => 'Tipo de CAPTCHA',
    'option_recaptcha' => 'reCAPTCHA',
    'option_hcaptcha' => 'hCaptcha (Próximamente)',
    'option_other' => 'Otro (Próximamente)',
    'help_captcha_type' => 'Actualmente solo se admite reCAPTCHA v2.',
    'label_recaptcha_enabled' => 'Activar reCAPTCHA',
    'help_recaptcha_enabled' => 'Active para proteger formularios contra spam y bots.',
    'label_recaptcha_site_key' => 'Clave del sitio',
    'placeholder_recaptcha_default' => 'Dejar vacío para claves de prueba predeterminadas',
    'help_recaptcha_site_key' => 'Su clave de sitio reCAPTCHA desde la consola de Google reCAPTCHA.',
    'label_recaptcha_secret_key' => 'Clave secreta',
    'help_recaptcha_secret_key' => 'Su clave secreta reCAPTCHA desde la consola de Google reCAPTCHA.',
    'note_recaptcha_empty' => 'Deje los campos reCAPTCHA vacíos para usar las claves de prueba predeterminadas cuando estén habilitadas. (Funcionan para pruebas, pero deben reemplazarse en producción.)',

    // Buttons
    'btn_save_recaptcha' => 'Guardar configuración de reCAPTCHA',
];
?>