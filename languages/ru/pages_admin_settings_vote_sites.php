<?php
return [
    // Page meta
    'page_description_vote_sites' => 'Управление сайтами голосования для сервера WoW Sahtout',
    'page_title_manage_vote_sites' => 'Управление сайтами голосования',

    // Errors
    'err_fix_errors' => 'Пожалуйста, исправьте следующие ошибки:',
    'err_invalid_csrf' => 'Неверный CSRF-токен.',
    'err_permission_denied' => 'Доступ запрещен.',
    'err_database' => 'Ошибка базы данных: ',
    'err_vote_site_not_found' => 'Сайт голосования не найден.',
    'err_callback_file_name_required' => 'Требуется имя файла обратного вызова.',
    'err_invalid_callback_file_name' => 'Имя файла обратного вызова должно быть буквенно-цифровым с подчеркиваниями или дефисами.',
    'err_callback_file_name_too_long' => 'Имя файла обратного вызова не должно превышать 50 символов.',
    'err_callback_file_name_exists' => 'Имя файла обратного вызова уже существует.',
    'err_siteid_required' => 'Требуется ID сайта.',
    'err_siteid_too_long' => 'ID сайта не должен превышать 255 символов.',
    'err_url_format_required' => 'Требуется формат URL для голосования.',
    'err_url_format_too_long' => 'Формат URL не должен превышать 255 символов.',
    'err_site_name_required' => 'Требуется название сайта.',
    'err_site_name_too_long' => 'Название сайта не должно превышать 50 символов.',
    'err_invalid_image_url' => 'URL изображения кнопки слишком длинный.',
    'err_invalid_cooldown' => 'Часы перезарядки должны быть от 1 до 999.',
    'err_invalid_reward' => 'Баллы вознаграждения должны быть от 1 до 255.',
    'err_callback_secret_too_long' => 'Секрет обратного вызова не должен превышать 64 символа.',
    'err_image_too_large' => 'Размер изображения не должен превышать 1 МБ.',
    'err_image_upload_failed' => 'Ошибка загрузки изображения: ',
    'err_invalid_image_type' => 'Разрешены только изображения JPEG, PNG и GIF.',

    // Success messages
    'msg_vote_site_saved' => 'Сайт голосования успешно сохранен!',
    'msg_vote_site_deleted' => 'Сайт голосования успешно удален!',
    'msg_image_deleted' => 'Изображение успешно удалено!',
    'msg_no_vote_sites' => 'Нет доступных сайтов голосования.',

    // Section titles
    'title_edit_vote_site' => 'Редактировать сайт голосования',
    'title_add_vote_site' => 'Добавить сайт голосования',
    'title_vote_sites_list' => 'Список сайтов голосования',

    // Labels
    'label_callback_file_name' => 'Имя файла обратного вызова',
    'label_callback_file_name_info' => 'Имя для идентификации сайта голосования в обратных вызовах (gtop100, top100arena и т.д.)',
    'placeholder_callback_file_name' => 'Введите имя файла обратного вызова (например, arenaTop100)',
    'label_site_name' => 'Название сайта',
    'placeholder_site_name' => 'Введите название сайта',
    'label_siteid' => 'ID сайта',
    'placeholder_siteid' => 'Введите ID сервера на сайте голосования',
    'label_siteid_info' => 'Уникальный ID вашего сервера на сайте голосования (например, SahtoutServer, 12345).',
    'label_url_format' => 'Формат URL для голосования',
    'placeholder_url_format' => 'например, https://site.com/vote/{siteid}/{userid}',
    'label_url_format_info' => 'Используйте {siteid}, {userid} или {username} в качестве заполнителей.',
    'label_button_image' => 'Загрузить изображение кнопки',
    'placeholder_button_image' => 'Нажмите или перетащите, чтобы загрузить изображение кнопки',
    'label_button_image_url' => 'URL изображения кнопки (Опционально)',
    'placeholder_button_image_url' => 'Введите URL изображения кнопки (опционально)',
    'label_image_url_info' => 'Введите URL изображения, если вы предпочитаете не загружать изображение. Оставьте пустым, чтобы удалить изображение.',
    'label_cooldown_hours' => 'Часы перезарядки',
    'placeholder_cooldown_hours' => 'Введите часы перезарядки',
    'label_reward_points' => 'Баллы вознаграждения',
    'placeholder_reward_points' => 'Введите баллы вознаграждения',
    'label_uses_callback' => 'Использует обратный вызов',
    'label_callback_secret' => 'Секрет обратного вызова',
    'placeholder_callback_secret' => 'Введите секрет обратного вызова (опционально)',
    'label_actions' => 'Действия',
    'label_no_image' => 'Нет изображения',

    // Options
    'option_yes' => 'Да',
    'option_no' => 'Нет',

    // Buttons
    'btn_save_vote_site' => 'Сохранить сайт голосования',
    'btn_reset' => 'Сбросить форму',
    'btn_edit' => 'Редактировать',
    'btn_delete' => 'Удалить',
    'btn_delete_image' => 'Удалить изображение',
    'btn_cancel' => 'Отмена',
    'btn_confirm_delete' => 'Подтвердить удаление',

    // Delete Modal
    'confirm_delete_title' => 'Подтверждение удаления',
    'confirm_delete_vote_site' => 'Вы уверены, что хотите удалить этот сайт голосования?',
    'confirm_delete_irreversible' => 'Это действие невозможно отменить.',
    'confirm_delete_image' => 'Вы уверены, что хотите удалить это изображение?',
];
?>