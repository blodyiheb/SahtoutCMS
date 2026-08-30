<?php
// 账号页面语言文件 (简体中文)
return [
    // Page title - 页面标题
    'page_title' => '- 账号 : %s',

    // Dashboard and section titles - 控制面板与区块标题
    'dashboard_title' => '账号控制面板',
    'section_account_info' => '账号信息',
    'section_quick_stats' => '快速统计',
    'section_your_characters' => '我的角色',
    'section_account_activity' => '账号动态',
    'section_change_email' => '修改邮箱',
    'section_change_password' => '修改密码',
    'section_change_avatar' => '更换头像',
    'section_account_actions' => '账号操作',

    // Tabs - 标签页
    'tab_overview' => '概览',
    'tab_characters' => '角色',
    'tab_activity' => '动态',
    'tab_security' => '安全',
    'tab_vote' => '投票',
    
    // Card titles - 卡片标题
    'card_basic_info' => '基本信息',
    'card_contact' => '联系方式',
    'card_activity' => '活动',
    'card_characters' => '角色',
    'card_wealth' => '财富',

    // Labels - 标签
    'label_username' => '用户名',
    'label_account_id' => '账号 ID',
    'label_status' => '状态',
    'label_rank' => '等级/权限',
    'label_online' => '在线状态',
    'label_email' => '电子邮箱',
    'label_expansion' => '资料片',
    'label_join_date' => '注册日期',
    'label_last_login' => '最后登录',
    'label_total_characters' => '角色总数',
    'label_highest_level' => '最高等级',
    'label_total_gold' => '金币总额',
    'label_points' => '积分',
    'label_tokens' => '代币',
    'label_level' => '等级',
    'label_gold' => '金币',
    'label_select_city' => '选择城市',
    'label_current_password' => '当前密码',
    'label_new_email' => '新邮箱',
    'label_new_password' => '新密码',
    'label_confirm_password' => '确认新密码',
    'label_select_avatar' => '选择头像',

    // Placeholders - 占位符
    'placeholder_current_password' => '请输入当前密码',
    'placeholder_new_email' => '请输入新邮箱地址',
    'placeholder_new_password' => '请输入新密码',
    'placeholder_confirm_password' => '请再次输入新密码',
    'select_city_placeholder' => '请选择一个城市',

    // Buttons and actions - 按钮与操作
    'button_admin_panel' => '管理面板',
    'button_teleport' => '传送',
    'button_update_email' => '更新邮箱',
    'button_change_password' => '确认修改密码',
    'button_update_avatar' => '更新头像',
    'action_logout' => '退出登录',
    'action_request_deletion' => '申请删除账号',
    'action_email_changed' => '邮箱已修改',
    'action_password_changed' => '密码已修改',
    'action_avatar_changed' => '头像已更换',
    'action_teleport' => '传送',

    // Statuses - 状态
    'status_banned' => '封禁',
    'status_frozen' => '冻结',
    'status_active' => '激活',
    'status_online' => '在线',
    'status_offline' => '离线',
    'ban_no_reason' => '未提供原因',
    'ban_permanent' => '永久',

    // GM ranks - GM 权限等级
    'gm_rank_gm' => '游戏管理员 (GM) 等级 %s%s',
    'gm_rank_admin' => '管理员',
    'gm_rank_moderator' => '版主/协调员',
    'gm_rank_player' => '玩家',
    'gm_suffix_admin' => ' (管)',
    'gm_suffix_moderator' => ' (助)',
    'gm_suffix_administrator' => ' (总)',

    // Expansions - 资料片
    'expansion_0' => '经典旧世 (Classic)',
    'expansion_1' => '燃烧的远征 (TBC)',
    'expansion_2' => '巫妖王之怒 (WotLK)',

    // Avatars - 头像
    'avatar_user.jpg' => '默认头像',
    'avatar_default' => '默认头像',

    // Messages - 提示消息
    'message_email_updated' => '邮箱更新成功！',
    'message_password_changed' => '密码修改成功！',
    'message_avatar_updated' => '头像更新成功！',
    'message_character_teleported' => '角色已传送到 %s！',

    // Errors - 错误信息
    'error_database_connection' => '数据库连接失败',
    'error_invalid_form_submission' => '表单提交无效',
    'error_invalid_email_format' => '邮箱格式不正确',
    'error_email_in_use' => '该邮箱已被其他账号使用',
    'error_account_not_found' => '未找到账号',
    'error_incorrect_password' => '当前密码错误',
    'error_updating_email' => '更新邮箱时出错',
    'error_passwords_dont_match' => '两次输入的新密码不匹配',
    'error_password_too_short' => '密码长度必须至少为 6 位',
    'error_password_length' => '密码必须介于6到16个字符之间',
    'error_updating_password' => '更新密码时出错',
    'error_invalid_character_id' => '无效的角色 ID',
    'error_rapid_submission' => '操作频繁，请几秒后再试',
    'error_teleport_cooldown' => '传送冷却中。请等待 %s 分钟%s',
    'error_character_not_found' => '未找到角色',
    'error_character_online' => '角色必须处于离线状态才能传送',
    'error_invalid_destination' => '无效的传送目的地',
    'error_teleporting_character' => '传送角色时出错',
    'error_logging_teleport' => '记录传送日志时出错',
    'error_invalid_avatar' => '所选头像无效',
    'error_updating_avatar' => '更新头像时出错',

    // Misc - 其他
    'email_not_set' => '未设置',
    'never' => '从未',
    'no_characters' => '你还没有创建角色。',
    'no_activity' => '暂无近期动态。',
    'none' => '无',
    'debug_warnings' => '调试警告',
    'confirm_teleport' => '确定要传送该角色吗？',
    'teleport_cooldown' => '传送冷却时间：%s 分钟%s',
    'teleport_details' => '去往 %s',
    'status_icon' => '状态图标',
    'race_icon' => '种族图标',
    'class_icon' => '职业图标',
    'faction_icon' => '阵营图标',
    'gold_icon' => '金币图标',
    'avatar_alt' => '头像',
    'city_shattrath' => '沙塔斯',
    'city_dalaran' => '达拉然',
];
?>