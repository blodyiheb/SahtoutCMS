<?php
return [
    // Page meta
    'page_description_recaptcha' => 'Sahtout WoW 服务器reCAPTCHA设置',
    'page_title_recaptcha' => 'reCAPTCHA设置',

    // Status
    'status' => '状态：',
    'msg_recaptcha_enabled' => 'reCAPTCHA已启用',
    'msg_recaptcha_disabled' => 'reCAPTCHA已禁用',

    // Errors
    'err_fix_errors' => '请修复以下错误：',
    'err_invalid_captcha_type' => '选择了无效的CAPTCHA类型。仅支持reCAPTCHA。',
    'err_recaptcha_keys_required' => '启用reCAPTCHA时，需要reCAPTCHA站点密钥和密钥。',
    'err_cap_dir_not_writable' => 'reCAPTCHA配置目录不可写：%s',
    'err_failed_write_cap' => '写入reCAPTCHA配置文件失败：%s',
    'error_direct_access' => '不允许直接访问。',

    // Success
    'msg_recaptcha_saved' => 'reCAPTCHA设置已成功保存！',

    // Section titles
    'settings_recaptcha' => 'reCAPTCHA设置',

    // Labels
    'label_captcha_type' => 'CAPTCHA类型',
    'option_recaptcha' => 'reCAPTCHA',
    'option_hcaptcha' => 'hCaptcha（即将推出）',
    'option_other' => '其他（即将推出）',
    'help_captcha_type' => '目前仅支持reCAPTCHA v2。',
    'label_recaptcha_enabled' => '启用reCAPTCHA',
    'help_recaptcha_enabled' => '启用后可保护表单免受垃圾邮件和机器人的侵扰。',
    'label_recaptcha_site_key' => '站点密钥',
    'placeholder_recaptcha_default' => '留空使用默认测试密钥',
    'help_recaptcha_site_key' => '来自Google reCAPTCHA控制台的reCAPTCHA站点密钥。',
    'label_recaptcha_secret_key' => '密钥',
    'help_recaptcha_secret_key' => '来自Google reCAPTCHA控制台的reCAPTCHA密钥。',
    'note_recaptcha_empty' => '启用时留空reCAPTCHA字段以使用默认测试密钥。（这些可用于测试，但应在生产环境中替换。）',

    // Buttons
    'btn_save_recaptcha' => '保存reCAPTCHA设置',
];
?>