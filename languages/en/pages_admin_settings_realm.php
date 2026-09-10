<?php
return [
    // Page meta
    'page_description_realm' => 'Realm Configuration for Sahtout WoW Server',
    'page_title_realm' => 'Realm Configuration',

    // Errors
    'err_fix_errors' => 'Please fix the following errors:',
    'err_invalid_csrf' => 'Invalid CSRF token.',
    'err_realm_name_required' => 'Realm Name is required.',
    'err_realm_ip_required' => 'Realm Address / Host is required.',
    'err_realm_ip_invalid' => 'Realm Address / Host is invalid.',
    'err_realm_check_address_required' => 'Status Check Address is required.',
    'err_realm_check_address_invalid' => 'Status Check Address contains invalid characters.',
    'err_realm_port_invalid' => 'Realm Port must be a valid number (1-65535).',
    'error_realm_logo_too_large' => 'Realm logo size exceeds 2MB.',
    'error_invalid_realm_logo_type' => 'Invalid file type. Only PNG, JPG, or WebP allowed.',
    'error_realm_logo_upload_failed' => 'Upload directory is not accessible or writable.',
    'err_config_dir_not_writable' => 'Config directory is not writable: %s',
    'err_write_realm_config' => 'Cannot write realm configuration file: %s',

    // Success
    'msg_realm_saved' => 'Realm configuration saved successfully!',

    // Section titles
    'section_realm_config' => 'Realm Configuration',

    // Labels
    'label_current_logo' => 'Current Logo',
    'label_realm_name' => 'Realm Name',
    'placeholder_realm_name' => 'Enter realm name',
    'label_realm_ip' => 'Realm Address / Host',
    'label_realm_check_address' => 'Status Check Address',
    'placeholder_realm_address' => 'play.example.com',
    'placeholder_realm_check_address' => '127.0.0.1',
    'note_realm_check_address' => 'Used internally by the website to check if the server is online. Usually 127.0.0.1 when the website and game server run on the same machine.',
    'label_realm_port' => 'Realm Port',
    'label_player_display' => 'Player Display',
    'player_display_all' => 'All Players',
    'player_display_all_desc' => 'Show humans and bots together as one player count.',
    'player_display_separate' => 'Humans / Bots',
    'player_display_separate_desc' => 'Show real players and playerbots as separate counts.',
    'label_realm_logo' => 'Realm Logo',
    'placeholder_realm_logo' => 'Click or drag to upload a new logo',
    'note_realm_logo' => 'Upload a new logo for your realm. Leave empty to keep the current logo.',
    'note_realm_config' => 'This configures the settings for a single realm.',

    // Buttons
    'btn_save_realm' => 'Save Realm Configuration',
];
?>