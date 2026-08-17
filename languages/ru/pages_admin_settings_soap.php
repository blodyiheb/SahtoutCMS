<?php
return [
    // Page meta
    'page_description_soap' => 'Настройки SOAP для сервера WoW Sahtout',
    'title_soap_settings' => 'Настройки SOAP',

    // Status
    'status' => 'Статус:',
    'status_soap_configured' => 'SOAP настроен',
    'status_soap_not_configured' => 'SOAP не настроен',

    // Errors
    'error_box_title' => 'Пожалуйста, исправьте следующие ошибки:',
    'error_soap_url_required' => 'Требуется URL SOAP.',
    'error_soap_user_required' => 'Требуется имя пользователя учетной записи GM.',
    'error_soap_pass_required' => 'Требуется пароль SOAP.',
    'error_db_query' => 'Ошибка запроса к базе данных: %s',
    'error_account_not_exist' => 'Учетная запись %s не существует в базе данных Auth.',
    'error_account_not_gm_level_3' => 'Учетная запись %s существует, но не имеет уровня GM 3.',
    'error_config_dir_not_writable' => 'Каталог конфигурации недоступен для записи: %s',
    'error_config_file_write_failed' => 'Не удалось записать файл конфигурации: %s',

    // Success
    'success_soap_settings_saved' => 'Настройки SOAP успешно сохранены!',

    // Section titles
    'header_soap_settings' => 'Настройки SOAP',

    // Labels
    'label_soap_url' => 'URL SOAP',
    'placeholder_soap_url' => 'например, http://127.0.0.1:7878',
    'help_soap_url' => 'URL-адрес, по которому работает SOAP-сервис вашего сервера WoW.',
    'label_soap_user' => 'Имя пользователя учетной записи GM',
    'placeholder_soap_user' => 'Должен быть уровня GM 3',
    'help_soap_user' => 'Учетная запись должна иметь уровень GM 3 в базе данных.',
    'label_soap_pass' => 'Пароль SOAP',
    'placeholder_soap_pass' => 'Пароль SOAP = Пароль учетной записи',
    'help_soap_pass' => 'Это пароль для указанной выше учетной записи GM.',

    // Buttons
    'button_save_verify_soap' => 'Сохранить и проверить SOAP',

    // Info Box
    'info_box_title' => 'Важные шаги',
    'info_step_1' => 'Убедитесь, что учетная запись GM существует в вашей базе данных Auth и имеет уровень GM 3 в <code>account_access</code> с <code>RealmID = -1</code>.',
    'info_step_2' => 'Откройте ваш файл <code>worldserver.conf</code> и установите: <strong>SOAP.Enabled = 1</strong>',
    'info_step_3' => 'Убедитесь, что порт SOAP в <code>soap_url</code> правильный и доступен.',
];
?>