<?php
return [
    // Page meta
    'page_description_smtp' => 'Настройки SMTP для сервера WoW Sahtout',
    'page_title_smtp' => 'Настройки SMTP',

    // Status
    'status' => 'Статус:',
    'msg_smtp_enabled' => 'SMTP включен',
    'msg_smtp_disabled' => 'SMTP отключен',

    // Errors
    'err_fix_errors' => 'Пожалуйста, исправьте следующие ошибки:',
    'err_smtp_host_required' => 'Требуется хост SMTP.',
    'err_smtp_user_required' => 'Требуется имя пользователя SMTP.',
    'err_smtp_pass_required' => 'Требуется пароль SMTP.',
    'err_smtp_test_failed' => 'Тест SMTP не удался:',
    'err_config_dir_not_writable' => 'Каталог конфигурации недоступен для записи: %s',
    'err_failed_write_config' => 'Не удалось записать файл конфигурации: %s',
    'error_direct_access' => 'Прямой доступ к этому файлу запрещен.',

    // Mail test
    'mail_test_subject' => 'Тестовое письмо - Sahtout CMS',
    'mail_test_body' => 'Это тестовое письмо из настроек администратора Sahtout CMS.',

    // Success
    'msg_smtp_saved' => 'Настройки SMTP успешно сохранены!',

    // Section titles
    'settings_smtp' => 'Настройки SMTP',

    // Labels
    'label_smtp_enabled' => 'Включить SMTP',
    'help_smtp_enabled' => 'Включите для отправки писем через SMTP-сервер.',
    'label_smtp_host' => 'Хост SMTP',
    'placeholder_smtp_host' => 'например, smtp.gmail.com',
    'label_email_address' => 'Адрес электронной почты',
    'placeholder_email' => 'например, ваше.имя@gmail.com',
    'label_app_password' => 'Пароль приложения / SMTP',
    'placeholder_app_password' => 'Пароль приложения для Gmail/Outlook',
    'help_smtp_pass' => 'Для Gmail используйте пароль приложения. Для других провайдеров используйте пароль от почты.',
    'label_from_email' => 'От кого (email)',
    'placeholder_from_email' => 'например, noreply@вашдомен.com',
    'label_from_name' => 'От кого (имя)',
    'placeholder_from_name' => 'например, Аккаунт Sahtout',
    'label_port' => 'Порт',
    'placeholder_port_tls_ssl' => '587 для TLS',
    'label_encryption' => 'Шифрование',
    'help_smtp_secure' => 'Большинство провайдеров используют TLS на порту 587.',

    // Buttons
    'btn_save_test_smtp' => 'Сохранить и протестировать SMTP',
];
?>