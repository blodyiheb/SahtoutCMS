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
    'err_smtp_test_failed' => 'SMTP测试失败：',
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
    'label_app_password' => '应用密码 / SMTP密码',
    'placeholder_app_password' => 'Gmail/Outlook的应用密码',
    'help_smtp_pass' => '对于Gmail，请使用应用密码。对于其他提供商，请使用您的邮箱密码。',
    'label_from_email' => '发件人邮箱',
    'placeholder_from_email' => '例如：noreply@yourdomain.com',
    'label_from_name' => '发件人名称',
    'placeholder_from_name' => '例如：Sahtout账户',
    'label_port' => '端口',
    'placeholder_port_tls_ssl' => 'TLS使用587',
    'label_encryption' => '加密方式',
    'help_smtp_secure' => '大多数提供商在端口587上使用TLS。',

    // Buttons
    'btn_save_test_smtp' => '保存并测试SMTP',
];
?>