<?php
return [
    // Page meta
    'page_description_vote_sites' => 'Vote Sites Management for Sahtout WoW Server',
    'page_title_manage_vote_sites' => 'Manage Vote Sites',

    // Errors
    'err_fix_errors' => 'Please fix the following errors:',
    'err_invalid_csrf' => 'Invalid CSRF token.',
    'err_permission_denied' => 'Permission denied.',
    'err_database' => 'Database error: ',
    'err_vote_site_not_found' => 'Vote site not found.',
    'err_callback_file_name_required' => 'Callback File Name is required.',
    'err_invalid_callback_file_name' => 'Callback File Name must be alphanumeric with underscores or hyphens.',
    'err_callback_file_name_too_long' => 'Callback File Name must not exceed 50 characters.',
    'err_callback_file_name_exists' => 'Callback File Name already exists.',
    'err_siteid_required' => 'Site ID is required.',
    'err_siteid_too_long' => 'Site ID must not exceed 255 characters.',
    'err_url_format_required' => 'Vote URL Format is required.',
    'err_url_format_too_long' => 'URL format must not exceed 255 characters.',
    'err_site_name_required' => 'Site name is required.',
    'err_site_name_too_long' => 'Site name must not exceed 50 characters.',
    'err_invalid_image_url' => 'Button image URL too long.',
    'err_invalid_cooldown' => 'Cooldown hours must be between 1 and 999.',
    'err_invalid_reward' => 'Reward points must be between 1 and 999.',
    'err_callback_secret_too_long' => 'Callback secret must not exceed 64 characters.',
    'err_image_too_large' => 'Image size must not exceed 1MB.',
    'err_image_upload_failed' => 'Image upload failed: ',
    'err_invalid_image_type' => 'Only JPEG, PNG, and GIF images are allowed.',

    // Success messages
    'msg_vote_site_saved' => 'Vote site saved successfully!',
    'msg_vote_site_deleted' => 'Vote site deleted successfully!',
    'msg_image_deleted' => 'Image deleted successfully!',
    'msg_no_vote_sites' => 'No vote sites available.',

    // Section titles
    'title_edit_vote_site' => 'Edit Vote Site',
    'title_add_vote_site' => 'Add Vote Site',
    'title_vote_sites_list' => 'Vote Sites List',

    // Labels
    'label_callback_file_name' => 'Callback File Name',
    'label_callback_file_name_info' => 'Name for identifying the voting site in callbacks (gtop100, top100arena, etc)',
    'placeholder_callback_file_name' => 'Enter callback file name (e.g., arenaTop100)',
    'label_site_name' => 'Site Name',
    'placeholder_site_name' => 'Enter site name',
    'label_siteid' => 'Site ID',
    'placeholder_siteid' => 'Enter server ID on the voting site',
    'label_siteid_info' => 'Your server\'s unique ID on the voting site (e.g., SahtoutServer, 12345).',
    'label_url_format' => 'Vote URL Format',
    'placeholder_url_format' => 'e.g., https://site.com/vote/{siteid}/{userid}',
    'label_url_format_info' => 'Use {siteid}, {userid}, or {username} as placeholders.',
    'label_button_image' => 'Upload Button Image',
    'placeholder_button_image' => 'Click or drag to upload a button image',
    'label_button_image_url' => 'Button Image URL (Optional)',
    'placeholder_button_image_url' => 'Enter button image URL (optional)',
    'label_image_url_info' => 'Enter an image URL if you prefer not to upload an image. Leave empty to clear the image.',
    'label_cooldown_hours' => 'Cooldown Hours',
    'placeholder_cooldown_hours' => 'Enter cooldown hours',
    'label_reward_points' => 'Reward Points',
    'placeholder_reward_points' => 'Enter reward points',
    'label_uses_callback' => 'Uses Callback',
    'label_callback_secret' => 'Callback Secret',
    'placeholder_callback_secret' => 'Enter callback secret (optional)',
    'label_actions' => 'Actions',
    'label_no_image' => 'No Image',

    // Options
    'option_yes' => 'Yes',
    'option_no' => 'No',

    // Buttons
    'btn_save_vote_site' => 'Save Vote Site',
    'btn_reset' => 'Reset Form',
    'btn_edit' => 'Edit',
    'btn_delete' => 'Delete',
    'btn_delete_image' => 'Delete Image',
    'btn_cancel' => 'Cancel',
    'btn_confirm_delete' => 'Confirm Delete',

    // Delete Modal
    'confirm_delete_title' => 'Confirm Delete',
    'confirm_delete_vote_site' => 'Are you sure you want to delete this vote site?',
    'confirm_delete_irreversible' => 'This action cannot be undone.',
    'confirm_delete_image' => 'Are you sure you want to delete this image?',
];
?>