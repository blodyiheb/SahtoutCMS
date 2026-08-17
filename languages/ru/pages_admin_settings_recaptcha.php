<?php
return [
    // Page meta
    'page_description_recaptcha' => 'Настройки reCAPTCHA для сервера WoW Sahtout',
    'page_title_recaptcha' => 'Настройки reCAPTCHA',

    // Status
    'status' => 'Статус:',
    'msg_recaptcha_enabled' => 'reCAPTCHA включен',
    'msg_recaptcha_disabled' => 'reCAPTCHA отключен',

    // Errors
    'err_fix_errors' => 'Пожалуйста, исправьте следующие ошибки:',
    'err_invalid_captcha_type' => 'Выбран неверный тип CAPTCHA. Поддерживается только reCAPTCHA.',
    'err_recaptcha_keys_required' => 'Требуются ключи сайта и секретный ключ reCAPTCHA, когда reCAPTCHA включен.',
    'err_cap_dir_not_writable' => 'Каталог конфигурации reCAPTCHA недоступен для записи: %s',
    'err_failed_write_cap' => 'Не удалось записать файл конфигурации reCAPTCHA: %s',
    'error_direct_access' => 'Прямой доступ запрещен.',

    // Success
    'msg_recaptcha_saved' => 'Настройки reCAPTCHA успешно сохранены!',

    // Section titles
    'settings_recaptcha' => 'Настройки reCAPTCHA',

    // Labels
    'label_captcha_type' => 'Тип CAPTCHA',
    'option_recaptcha' => 'reCAPTCHA',
    'option_hcaptcha' => 'hCaptcha (Скоро)',
    'option_other' => 'Другое (Скоро)',
    'help_captcha_type' => 'В настоящее время поддерживается только reCAPTCHA v2.',
    'label_recaptcha_enabled' => 'Включить reCAPTCHA',
    'help_recaptcha_enabled' => 'Включите для защиты форм от спама и ботов.',
    'label_recaptcha_site_key' => 'Ключ сайта',
    'placeholder_recaptcha_default' => 'Оставьте пустым для стандартных тестовых ключей',
    'help_recaptcha_site_key' => 'Ваш ключ сайта reCAPTCHA из консоли Google reCAPTCHA.',
    'label_recaptcha_secret_key' => 'Секретный ключ',
    'help_recaptcha_secret_key' => 'Ваш секретный ключ reCAPTCHA из консоли Google reCAPTCHA.',
    'note_recaptcha_empty' => 'Оставьте поля reCAPTCHA пустыми, чтобы использовать стандартные тестовые ключи при включении. (Они работают для тестирования, но должны быть заменены в продакшене.)',

    // Buttons
    'btn_save_recaptcha' => 'Сохранить настройки reCAPTCHA',
];
?>