<?php
// Языковой файл для account.php (Русский)
return [
    // Заголовок страницы
    'page_title' => ' - аккаунт : %s',

    // Разделы
    'dashboard_title' => 'Панель аккаунта',
    'section_account_info' => 'Информация об аккаунте',
    'section_quick_stats' => 'Быстрая статистика',
    'section_your_characters' => 'Ваши персонажи',
    'section_account_activity' => 'Активность аккаунта',
    'section_change_email' => 'Сменить Email',
    'section_change_password' => 'Сменить пароль',
    'section_change_avatar' => 'Сменить аватар',
    'section_account_actions' => 'Действия с аккаунтом',

    // Вкладки
    'tab_overview' => 'Обзор',
    'tab_characters' => 'Персонажи',
    'tab_activity' => 'Активность',
    'tab_security' => 'Безопасность',
    'tab_vote' => 'Голосовать',
    // Карточки
    'card_basic_info' => 'Основная информация',
    'card_contact' => 'Контакт',
    'card_activity' => 'Активность',
    'card_characters' => 'Персонажи',
    'card_wealth' => 'Богатство',

    // Метки
    'label_username' => 'Имя пользователя',
    'label_account_id' => 'ID аккаунта',
    'label_status' => 'Статус',
    'label_rank' => 'Ранг',
    'label_online' => 'Онлайн',
    'label_email' => 'Email',
    'label_expansion' => 'Аддон',
    'label_join_date' => 'Дата регистрации',
    'label_last_login' => 'Последний вход',
    'label_total_characters' => 'Всего',
    'label_highest_level' => 'Макс. уровень',
    'label_total_gold' => 'Всего золота',
    'label_points' => 'Очки',
    'label_tokens' => 'Токены',
    'label_level' => 'Уровень',
    'label_gold' => 'Золото',
    'label_select_city' => 'Выберите город',
    'label_current_password' => 'Текущий пароль',
    'label_new_email' => 'Новый Email',
    'label_new_password' => 'Новый пароль',
    'label_confirm_password' => 'Подтвердите новый пароль',
    'label_select_avatar' => 'Выберите аватар',

    // Подсказки
    'placeholder_current_password' => 'Введите текущий пароль',
    'placeholder_new_email' => 'Введите новый Email',
    'placeholder_new_password' => 'Введите новый пароль',
    'placeholder_confirm_password' => 'Подтвердите новый пароль',
    'select_city_placeholder' => 'Выберите город',

    // Кнопки и действия
    'button_admin_panel' => 'Админ-панель',
    'button_teleport' => 'Телепорт',
    'button_update_email' => 'Обновить Email',
    'button_change_password' => 'Сменить пароль',
    'button_update_avatar' => 'Обновить аватар',
    'action_logout' => 'Выйти',
    'action_request_deletion' => 'Запросить удаление аккаунта',
    'action_email_changed' => 'Email изменён',
    'action_password_changed' => 'Пароль изменён',
    'action_avatar_changed' => 'Аватар изменён',
    'action_teleport' => 'Телепорт',

    // Статусы
    'status_banned' => 'Забанен',
    'status_frozen' => 'Заморожен',
    'status_active' => 'Активен',
    'status_online' => 'Онлайн',
    'status_offline' => 'Оффлайн',
    'ban_no_reason' => 'Причина не указана',
    'ban_permanent' => 'Навсегда',

    // GM ранги
    'gm_rank_gm' => 'Гейммастер Уровень %s%s',
    'gm_rank_admin' => 'Админ',
    'gm_rank_moderator' => 'Модератор',
    'gm_rank_player' => 'Игрок',
    'gm_suffix_admin' => ' (S)',
    'gm_suffix_moderator' => ' (M)',
    'gm_suffix_administrator' => ' (A)',

    // Аддоны
    'expansion_0' => 'Классика',
    'expansion_1' => 'The Burning Crusade',
    'expansion_2' => 'Wrath of the Lich King',

    // Аватары
    'avatar_user.jpg' => 'Аватар по умолчанию',
    'avatar_default' => 'Аватар по умолчанию',

    // Сообщения
    'message_email_updated' => 'Email успешно обновлён!',
    'message_password_changed' => 'Пароль успешно изменён!',
    'message_avatar_updated' => 'Аватар успешно обновлён!',
    'message_character_teleported' => 'Персонаж телепортирован в %s!',

    // Ошибки
    'error_database_connection' => 'Ошибка подключения к базе данных',
    'error_invalid_form_submission' => 'Неверная отправка формы',
    'error_invalid_email_format' => 'Неверный формат Email',
    'error_email_in_use' => 'Email уже используется другим аккаунтом',
    'error_account_not_found' => 'Аккаунт не найден',
    'error_incorrect_password' => 'Неверный текущий пароль',
    'error_updating_email' => 'Ошибка при обновлении Email',
    'error_passwords_dont_match' => 'Новые пароли не совпадают',
    'error_password_too_short' => 'Пароль должен содержать минимум 6 символов',
    'error_updating_password' => 'Ошибка при обновлении пароля',
    'error_invalid_character_id' => 'Неверный ID персонажа',
    'error_rapid_submission' => 'Пожалуйста, подождите несколько секунд перед повтором',
    'error_teleport_cooldown' => 'Телепорт на перезарядке. Подождите %s минут%s',
    'error_character_not_found' => 'Персонаж не найден',
    'error_character_online' => 'Персонаж должен быть оффлайн для телепортации',
    'error_invalid_destination' => 'Неверная точка телепорта',
    'error_teleporting_character' => 'Ошибка при телепортации персонажа',
    'error_logging_teleport' => 'Ошибка при записи телепорта',
    'error_invalid_avatar' => 'Неверный аватар выбран',
    'error_updating_avatar' => 'Ошибка при обновлении аватара',

    // Разное
    'email_not_set' => 'Не указано',
    'never' => 'Никогда',
    'no_characters' => 'У вас пока нет персонажей.',
    'no_activity' => 'Нет недавней активности.',
    'none' => 'Н/Д',
    'debug_warnings' => 'Отладочные предупреждения',
    'confirm_teleport' => 'Телепортировать этого персонажа?',
    'teleport_cooldown' => 'Перезарядка телепорта: %s минут%s',
    'teleport_details' => 'В %s',
    'status_icon' => 'Иконка статуса',
    'race_icon' => 'Иконка расы',
    'class_icon' => 'Иконка класса',
    'faction_icon' => 'Иконка фракции',
    'gold_icon' => 'Иконка золота',
    'avatar_alt' => 'Аватар',
    'city_shattrath' => 'Шаттрат',
    'city_dalaran' => 'Даларан',
];
?>
