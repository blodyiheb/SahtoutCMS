<?php
return [
    // Success Messages
    'msg_reward_claimed' => 'Награда успешно получена для %s. +%d очков добавлено на ваш счет.',

    // Error Messages
    'err_invalid_csrf' => 'Недействительный CSRF-токен.',
    'err_invalid_user_id' => 'Недействительный ID пользователя.',
    'err_user_not_found' => 'ID пользователя не найден в user_currencies.',
    'err_site_not_found' => 'ID сайта не найден в vote_sites: %s.',
    'err_no_unclaimed_votes' => 'Нет доступных неполученных голосов для пользователя: %s.',
    'err_database_generic' => 'Ошибка базы данных: %s',
    'err_db_connection_failed' => 'Ошибка подключения к базе данных.',
];
?>