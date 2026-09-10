<?php
return [
    // Page meta
    'page_description_realm' => 'Настройка игрового мира для сервера WoW Sahtout',
    'page_title_realm' => 'Настройка игрового мира',

    // Errors
    'err_fix_errors' => 'Пожалуйста, исправьте следующие ошибки:',
    'err_invalid_csrf' => 'Недействительный CSRF-токен.',
    'err_realm_name_required' => 'Требуется название игрового мира.',
    'err_realm_ip_required' => 'Требуется адрес игрового мира / хост.',
    'err_realm_ip_invalid' => 'Адрес игрового мира / хост недействителен.',
    'err_realm_check_address_required' => 'Требуется адрес проверки статуса.',
    'err_realm_check_address_invalid' => 'Адрес проверки статуса содержит недопустимые символы.',
    'err_realm_port_invalid' => 'Порт игрового мира должен быть допустимым числом (1-65535).',
    'error_realm_logo_too_large' => 'Размер логотипа игрового мира превышает 2 МБ.',
    'error_invalid_realm_logo_type' => 'Неверный тип файла. Разрешены только PNG, JPG или WebP.',
    'error_realm_logo_upload_failed' => 'Каталог загрузки недоступен или недоступен для записи.',
    'err_config_dir_not_writable' => 'Каталог конфигурации недоступен для записи: %s',
    'err_write_realm_config' => 'Не удалось записать файл конфигурации игрового мира: %s',

    // Success
    'msg_realm_saved' => 'Настройки игрового мира успешно сохранены!',

    // Section titles
    'section_realm_config' => 'Настройка игрового мира',

    // Labels
    'label_current_logo' => 'Текущий логотип',
    'label_realm_name' => 'Название игрового мира',
    'placeholder_realm_name' => 'Введите название игрового мира',
    'label_realm_ip' => 'Адрес игрового мира / Хост',
    'label_realm_check_address' => 'Адрес проверки статуса',
    'placeholder_realm_address' => 'play.example.com',
    'placeholder_realm_check_address' => '127.0.0.1',
    'note_realm_check_address' => 'Используется сайтом только для внутренней проверки, онлайн ли сервер. Обычно 127.0.0.1, если сайт и игровой сервер находятся на одной машине.',
    'label_realm_port' => 'Порт игрового мира',
    'label_player_display' => 'Отображение игроков',
    'player_display_all' => 'Все игроки',
    'player_display_all_desc' => 'Показывать людей и ботов вместе как одно число игроков.',
    'player_display_separate' => 'Люди / Боты',
    'player_display_separate_desc' => 'Показывать реальных игроков и ботов отдельными числами.',
    'label_realm_logo' => 'Логотип игрового мира',
    'placeholder_realm_logo' => 'Нажмите или перетащите, чтобы загрузить новый логотип',
    'note_realm_logo' => 'Загрузите новый логотип для вашего игрового мира. Оставьте пустым, чтобы сохранить текущий логотип.',
    'note_realm_config' => 'Это настраивает параметры для одного игрового мира.',

    // Buttons
    'btn_save_realm' => 'Сохранить настройки игрового мира',
];
?>