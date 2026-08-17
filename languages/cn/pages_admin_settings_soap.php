<?php
return [
    // Page meta
    'page_description_soap' => 'Sahtout WoW 服务器SOAP设置',
    'title_soap_settings' => 'SOAP设置',

    // Status
    'status' => '状态：',
    'status_soap_configured' => 'SOAP已配置',
    'status_soap_not_configured' => 'SOAP未配置',

    // Errors
    'error_box_title' => '请修复以下错误：',
    'error_soap_url_required' => 'SOAP URL是必填项。',
    'error_soap_user_required' => 'GM账户用户名是必填项。',
    'error_soap_pass_required' => 'SOAP密码是必填项。',
    'error_db_query' => '数据库查询错误：%s',
    'error_account_not_exist' => '账户 %s 在Auth数据库中不存在。',
    'error_account_not_gm_level_3' => '账户 %s 存在，但不是GM等级3。',
    'error_config_dir_not_writable' => '配置目录不可写：%s',
    'error_config_file_write_failed' => '写入配置文件失败：%s',

    // Success
    'success_soap_settings_saved' => 'SOAP设置已成功保存！',

    // Section titles
    'header_soap_settings' => 'SOAP设置',

    // Labels
    'label_soap_url' => 'SOAP URL',
    'placeholder_soap_url' => '例如：http://127.0.0.1:7878',
    'help_soap_url' => '您的WoW服务器SOAP服务运行的URL。',
    'label_soap_user' => 'GM账户用户名',
    'placeholder_soap_user' => '必须是GM等级3',
    'help_soap_user' => '账户在数据库中必须具有GM等级3。',
    'label_soap_pass' => 'SOAP密码',
    'placeholder_soap_pass' => 'SOAP密码 = 账户密码',
    'help_soap_pass' => '这是上面GM账户的密码。',

    // Buttons
    'button_save_verify_soap' => '保存并验证SOAP',

    // Info Box
    'info_box_title' => '重要步骤',
    'info_step_1' => '确保GM账户存在于您的Auth数据库中，并在<code>account_access</code>中具有GM等级3，且<code>RealmID = -1</code>。',
    'info_step_2' => '打开您的<code>worldserver.conf</code>文件并设置：<strong>SOAP.Enabled = 1</strong>',
    'info_step_3' => '确保<code>soap_url</code>中的SOAP端口正确且可访问。',
];
?>