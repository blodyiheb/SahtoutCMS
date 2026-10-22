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
    'err_smtp_from_required' => 'Требуется email отправителя.',
    'err_smtp_from_invalid' => 'Email отправителя должен быть корректным адресом.',
    'err_test_email_invalid' => 'Пожалуйста, введите корректный email для тестового получателя.',
    'err_composer_missing' => 'PHPMailer не установлен. Пожалуйста, выполните "composer install" в корневой директории, чтобы включить функциональность SMTP.',
    'err_smtp_test_failed' => 'Тест SMTP не удался:',
    'err_invalid_csrf' => 'Недействительный CSRF-токен.',
    'err_smtp_port_invalid' => 'Порт должен быть от 1 до 65535.',
    'err_smtp_secure_invalid' => 'Шифрование должно быть tls, ssl или пустым.',
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
    'label_smtp_user' => 'Имя пользователя SMTP / Email',
    'placeholder_smtp_user' => 'например: вашеимя@gmail.com, resend, apikey',
    'label_test_email' => 'Отправить тестовое письмо на',
    'placeholder_test_email' => 'Пусто = имя пользователя SMTP, если это email, иначе адрес отправителя',
    'help_test_email' => 'Куда отправляется тестовое письмо. Оставьте пустым, чтобы использовать имя пользователя SMTP, если это адрес электронной почты, иначе адрес отправителя.',
    'label_app_password' => 'Пароль приложения / SMTP',
    'placeholder_app_password' => 'Пароль приложения для Gmail/Outlook',
    'help_smtp_pass' => 'Оставьте пустым, чтобы сохранить текущий сохранённый пароль. Для Gmail используйте пароль приложения; для других провайдеров — ваш пароль SMTP или API-ключ.',
    'label_from_email' => 'От кого (email)',
    'placeholder_from_email' => 'например, noreply@вашдомен.com',
    'label_from_name' => 'От кого (имя)',
    'placeholder_from_name' => 'например, Аккаунт Sahtout',
    'label_port' => 'Порт',
    'placeholder_port' => '587 (TLS), 465 (SSL), 25 (без шифрования)',
    'help_smtp_port' => 'Частые SMTP-порты: 587 (TLS), 465 (SSL), 25 (без шифрования). Некоторые провайдеры также используют 2525.',
    'label_encryption' => 'Шифрование',
    'help_smtp_secure' => 'Большинство провайдеров используют TLS на порту 587.',
    'label_allow_self_signed' => 'Разрешить самоподписанные сертификаты',
    'help_allow_self_signed' => 'Включайте только для локальной разработки или частных тестовых серверов с самоподписанными сертификатами SSL/TLS. В продакшене оставляйте выключенным.',

    // Buttons
    'btn_save_test_smtp' => 'Сохранить и протестировать SMTP',
];
?>