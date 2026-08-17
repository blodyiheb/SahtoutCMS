<?php
return [
    // Page meta
    'page_description_soap' => 'SOAP Settings for Sahtout WoW Server',
    'title_soap_settings' => 'SOAP Settings',

    // Status
    'status' => 'Status:',
    'status_soap_configured' => 'SOAP Configured',
    'status_soap_not_configured' => 'SOAP Not Configured',

    // Errors
    'error_box_title' => 'Please fix the following errors:',
    'error_soap_url_required' => 'SOAP URL is required.',
    'error_soap_user_required' => 'GM Account Username is required.',
    'error_soap_pass_required' => 'SOAP Password is required.',
    'error_db_query' => 'Database query error: %s',
    'error_account_not_exist' => 'Account %s does not exist in Auth DB.',
    'error_account_not_gm_level_3' => 'Account %s exists but is not GM level 3.',
    'error_config_dir_not_writable' => 'Config directory is not writable: %s',
    'error_config_file_write_failed' => 'Failed to write config file: %s',

    // Success
    'success_soap_settings_saved' => 'SOAP settings saved successfully!',

    // Section titles
    'header_soap_settings' => 'SOAP Settings',

    // Labels
    'label_soap_url' => 'SOAP URL',
    'placeholder_soap_url' => 'e.g., http://127.0.0.1:7878',
    'help_soap_url' => 'The URL where your WoW server\'s SOAP service is running.',
    'label_soap_user' => 'GM Account Username',
    'placeholder_soap_user' => 'Must be GM level 3',
    'help_soap_user' => 'The account must have GM level 3 in the database.',
    'label_soap_pass' => 'SOAP Password',
    'placeholder_soap_pass' => 'SOAP password = Account password',
    'help_soap_pass' => 'This is the password for the GM account above.',

    // Buttons
    'button_save_verify_soap' => 'Save & Verify SOAP',

    // Info Box
    'info_box_title' => 'Important Steps',
    'info_step_1' => 'Make sure the GM account exists in your Auth DB and has GM level 3 in <code>account_access</code> with <code>RealmID = -1</code>.',
    'info_step_2' => 'Open your <code>worldserver.conf</code> file and set: <strong>SOAP.Enabled = 1</strong>',
    'info_step_3' => 'Ensure the SOAP port in <code>soap_url</code> is correct and accessible.',
];
?>