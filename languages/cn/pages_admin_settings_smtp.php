<?php
return [
    // Page meta
    'page_description_smtp' => 'Sahtout WoW 服务器SMTP设置',
    'page_title_smtp' => 'SMTP设置',

    // Status
    'status' => '状态：',
    'msg_smtp_enabled' => 'SMTP已启用',
    'msg_smtp_disabled' => 'SMTP已禁用',

    // Errors
    'err_fix_errors' => '请修复以下错误：',
    'err_smtp_host_required' => 'SMTP主机是必填项。',
    'err_smtp_user_required' => 'SMTP用户名是必填项。',
    'err_smtp_pass_required' => 'SMTP密码是必填项。',
    'err_smtp_from_required' => '发件人邮箱是必填项。',
    'err_smtp_from_invalid' => '发件人邮箱必须是有效的邮箱地址。',
    'err_test_email_invalid' => '请输入有效的测试收件人邮箱地址。',
    'err_composer_missing' => '未安装 PHPMailer。请在根目录中运行 "composer install" 以启用 SMTP 功能。',
    'err_smtp_test_failed' => 'SMTP测试失败：',
    'err_invalid_csrf' => 'CSRF 令牌无效。',
    'err_smtp_port_invalid' => '端口必须在 1 到 65535 之间。',
    'err_smtp_secure_invalid' => '加密方式必须为 tls、ssl 或留空。',
    'err_config_dir_not_writable' => '配置目录不可写：%s',
    'err_failed_write_config' => '写入配置文件失败：%s',
    'error_direct_access' => '不允许直接访问此文件。',

    // Mail test
    'mail_test_subject' => '测试邮件 - Sahtout CMS',
    'mail_test_body' => '这是来自Sahtout CMS管理员设置的测试邮件。',

    // Success
    'msg_smtp_saved' => 'SMTP设置已成功保存！',

    // Section titles
    'settings_smtp' => 'SMTP设置',

    // Labels
    'label_smtp_enabled' => '启用SMTP',
    'help_smtp_enabled' => '启用后通过SMTP服务器发送邮件。',
    'label_smtp_host' => 'SMTP主机',
    'placeholder_smtp_host' => '例如：smtp.gmail.com',
    'label_email_address' => '邮箱地址',
    'placeholder_email' => '例如：yourname@gmail.com',
    'label_smtp_user' => 'SMTP 用户名 / 邮箱',
    'placeholder_smtp_user' => '例如：you@gmail.com、resend、apikey',
    'label_test_email' => '测试邮件发送至',
    'placeholder_test_email' => '留空 = 若 SMTP 用户名是邮箱则使用它，否则使用发件人邮箱',
    'help_test_email' => '测试邮件的发送地址。留空则使用 SMTP 用户名（如果是邮箱地址），否则使用发件人邮箱。',
    'label_app_password' => '应用密码 / SMTP密码',
    'placeholder_app_password' => 'Gmail/Outlook的应用密码',
    'help_smtp_pass' => '留空则保留当前保存的密码。对于 Gmail，请使用应用密码；对于其他提供商，请使用 SMTP 密码或 API 密钥。',
    'label_from_email' => '发件人邮箱',
    'placeholder_from_email' => '例如：noreply@yourdomain.com',
    'label_from_name' => '发件人名称',
    'placeholder_from_name' => '例如：Sahtout账户',
    'label_port' => '端口',
    'placeholder_port' => '587 (TLS), 465 (SSL), 25 (明文)',
    'help_smtp_port' => '常用 SMTP 端口：587（TLS）、465（SSL）、25（不加密）。部分服务商也使用 2525。',
    'label_encryption' => '加密方式',
    'help_smtp_secure' => '大多数提供商在端口587上使用TLS。',
    'label_allow_self_signed' => '允许自签名证书',
    'help_allow_self_signed' => '仅在本地开发或使用自签名 SSL/TLS 证书的私有测试服务器上启用。在生产环境中请保持关闭。',

    // Buttons
    'btn_save_test_smtp' => '保存并测试SMTP',
];
?>