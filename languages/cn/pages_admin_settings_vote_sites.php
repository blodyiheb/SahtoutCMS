<?php
return [
    // Page meta
    'page_description_vote_sites' => 'Sahtout WoW 服务器投票站点管理',
    'page_title_manage_vote_sites' => '管理投票站点',

    // Errors
    'err_fix_errors' => '请修复以下错误：',
    'err_invalid_csrf' => '无效的CSRF令牌。',
    'err_permission_denied' => '权限被拒绝。',
    'err_database' => '数据库错误：',
    'err_vote_site_not_found' => '未找到投票站点。',
    'err_callback_file_name_required' => '回调文件名是必填项。',
    'err_invalid_callback_file_name' => '回调文件名必须为字母数字，可包含下划线或连字符。',
    'err_callback_file_name_too_long' => '回调文件名不得超过50个字符。',
    'err_callback_file_name_exists' => '回调文件名已存在。',
    'err_siteid_required' => '站点ID是必填项。',
    'err_siteid_too_long' => '站点ID不得超过255个字符。',
    'err_url_format_required' => '投票URL格式是必填项。',
    'err_url_format_too_long' => 'URL格式不得超过255个字符。',
    'err_site_name_required' => '站点名称是必填项。',
    'err_site_name_too_long' => '站点名称不得超过50个字符。',
    'err_invalid_image_url' => '按钮图片URL过长。',
    'err_invalid_cooldown' => '冷却时间必须在1到999小时之间。',
    'err_invalid_reward' => '奖励积分必须在1到999之间。',
    'err_callback_secret_too_long' => '回调密钥不得超过64个字符。',
    'err_image_too_large' => '图片大小不得超过1MB。',
    'err_image_upload_failed' => '图片上传失败：',
    'err_invalid_image_type' => '仅允许JPEG、PNG和GIF图片。',

    // Success messages
    'msg_vote_site_saved' => '投票站点已成功保存！',
    'msg_vote_site_deleted' => '投票站点已成功删除！',
    'msg_image_deleted' => '图片已成功删除！',
    'msg_no_vote_sites' => '没有可用的投票站点。',

    // Section titles
    'title_edit_vote_site' => '编辑投票站点',
    'title_add_vote_site' => '添加投票站点',
    'title_vote_sites_list' => '投票站点列表',

    // Labels
    'label_callback_file_name' => '回调文件名',
    'label_callback_file_name_info' => '用于在回调中标识投票站点的名称（gtop100、top100arena等）',
    'placeholder_callback_file_name' => '输入回调文件名（例如：arenaTop100）',
    'label_site_name' => '站点名称',
    'placeholder_site_name' => '输入站点名称',
    'label_siteid' => '站点ID',
    'placeholder_siteid' => '输入投票站点上的服务器ID',
    'label_siteid_info' => '您的服务器在投票站点上的唯一ID（例如：SahtoutServer、12345）。',
    'label_url_format' => '投票URL格式',
    'placeholder_url_format' => '例如：https://site.com/vote/{siteid}/{userid}',
    'label_url_format_info' => '使用{siteid}、{userid}或{username}作为占位符。',
    'label_button_image' => '上传按钮图片',
    'placeholder_button_image' => '点击或拖拽上传按钮图片',
    'label_button_image_url' => '按钮图片URL（可选）',
    'placeholder_button_image_url' => '输入按钮图片URL（可选）',
    'label_image_url_info' => '如果不想上传图片，请输入图片URL。留空将清除图片。',
    'label_cooldown_hours' => '冷却时间（小时）',
    'placeholder_cooldown_hours' => '输入冷却小时数',
    'label_reward_points' => '奖励积分',
    'placeholder_reward_points' => '输入奖励积分',
    'label_uses_callback' => '使用回调',
    'label_callback_secret' => '回调密钥',
    'placeholder_callback_secret' => '输入回调密钥（可选）',
    'label_actions' => '操作',
    'label_no_image' => '无图片',

    // Options
    'option_yes' => '是',
    'option_no' => '否',

    // Buttons
    'btn_save_vote_site' => '保存投票站点',
    'btn_reset' => '重置表单',
    'btn_edit' => '编辑',
    'btn_delete' => '删除',
    'btn_delete_image' => '删除图片',
    'btn_cancel' => '取消',
    'btn_confirm_delete' => '确认删除',

    // Delete Modal
    'confirm_delete_title' => '确认删除',
    'confirm_delete_vote_site' => '您确定要删除此投票站点吗？',
    'confirm_delete_irreversible' => '此操作无法撤销。',
    'confirm_delete_image' => '您确定要删除此图片吗？',
];
?>