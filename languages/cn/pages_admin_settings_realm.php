<?php
return [
    // Page meta
    'page_description_realm' => 'Sahtout WoW 服务器领域配置',
    'page_title_realm' => '领域配置',

    // Errors
    'err_fix_errors' => '请修复以下错误：',
    'err_invalid_csrf' => '无效的CSRF令牌。',
    'err_realm_name_required' => '领域名称是必填项。',
    'err_realm_ip_required' => '领域地址 / 主机为必填项。',
    'err_realm_ip_invalid' => '领域地址 / 主机无效。',
    'err_realm_check_address_required' => '状态检查地址为必填项。',
    'err_realm_check_address_invalid' => '状态检查地址包含无效字符。',
    'err_realm_port_invalid' => '领域端口必须是有效数字（1-65535）。',
    'error_realm_logo_too_large' => '领域标志大小超过2MB。',
    'error_invalid_realm_logo_type' => '无效文件类型。仅允许PNG、JPG或WebP。',
    'error_realm_logo_upload_failed' => '上传目录无法访问或不可写。',
    'err_config_dir_not_writable' => '配置目录不可写：%s',
    'err_write_realm_config' => '无法写入领域配置文件：%s',

    // Success
    'msg_realm_saved' => '领域配置已成功保存！',

    // Section titles
    'section_realm_config' => '领域配置',

    // Labels
    'label_current_logo' => '当前标志',
    'label_realm_name' => '领域名称',
    'placeholder_realm_name' => '输入领域名称',
    'label_realm_ip' => '领域地址 / 主机',
    'label_realm_check_address' => '状态检查地址',
    'placeholder_realm_address' => 'play.example.com',
    'placeholder_realm_check_address' => '127.0.0.1',
    'note_realm_check_address' => '网站内部用于检查服务器是否在线。网站与游戏服务器在同一台机器上时通常为 127.0.0.1。',
    'label_realm_port' => '领域端口',
    'label_player_display' => '玩家显示',
    'player_display_all' => '所有玩家',
    'player_display_all_desc' => '将玩家和机器人合并显示为一个玩家总数。',
    'player_display_separate' => '人类 / 机器人',
    'player_display_separate_desc' => '分别显示真实玩家和机器人的数量。',
    'label_realm_logo' => '领域标志',
    'placeholder_realm_logo' => '点击或拖拽上传新标志',
    'note_realm_logo' => '为您的领域上传新标志。留空以保留当前标志。',
    'note_realm_config' => '此配置适用于单个领域的设置。',

    // Buttons
    'btn_save_realm' => '保存领域配置',
];
?>