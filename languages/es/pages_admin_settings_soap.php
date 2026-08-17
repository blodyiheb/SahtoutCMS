<?php
return [
    // Page meta
    'page_description_soap' => 'Configuración SOAP para el servidor WoW Sahtout',
    'title_soap_settings' => 'Configuración SOAP',

    // Status
    'status' => 'Estado:',
    'status_soap_configured' => 'SOAP configurado',
    'status_soap_not_configured' => 'SOAP no configurado',

    // Errors
    'error_box_title' => 'Por favor, corrija los siguientes errores:',
    'error_soap_url_required' => 'La URL SOAP es obligatoria.',
    'error_soap_user_required' => 'El nombre de usuario de la cuenta GM es obligatorio.',
    'error_soap_pass_required' => 'La contraseña SOAP es obligatoria.',
    'error_db_query' => 'Error de consulta de base de datos: %s',
    'error_account_not_exist' => 'La cuenta %s no existe en la base de datos Auth.',
    'error_account_not_gm_level_3' => 'La cuenta %s existe pero no es de nivel GM 3.',
    'error_config_dir_not_writable' => 'El directorio de configuración no tiene permisos de escritura: %s',
    'error_config_file_write_failed' => 'Error al escribir el archivo de configuración: %s',

    // Success
    'success_soap_settings_saved' => '¡Configuración SOAP guardada con éxito!',

    // Section titles
    'header_soap_settings' => 'Configuración SOAP',

    // Labels
    'label_soap_url' => 'URL SOAP',
    'placeholder_soap_url' => 'ej., http://127.0.0.1:7878',
    'help_soap_url' => 'La URL donde se ejecuta el servicio SOAP de su servidor WoW.',
    'label_soap_user' => 'Nombre de usuario de la cuenta GM',
    'placeholder_soap_user' => 'Debe ser de nivel GM 3',
    'help_soap_user' => 'La cuenta debe tener nivel GM 3 en la base de datos.',
    'label_soap_pass' => 'Contraseña SOAP',
    'placeholder_soap_pass' => 'Contraseña SOAP = Contraseña de la cuenta',
    'help_soap_pass' => 'Esta es la contraseña de la cuenta GM anterior.',

    // Buttons
    'button_save_verify_soap' => 'Guardar y verificar SOAP',

    // Info Box
    'info_box_title' => 'Pasos importantes',
    'info_step_1' => 'Asegúrese de que la cuenta GM exista en su base de datos Auth y tenga nivel GM 3 en <code>account_access</code> con <code>RealmID = -1</code>.',
    'info_step_2' => 'Abra su archivo <code>worldserver.conf</code> y establezca: <strong>SOAP.Enabled = 1</strong>',
    'info_step_3' => 'Asegúrese de que el puerto SOAP en <code>soap_url</code> sea correcto y accesible.',
];
?>