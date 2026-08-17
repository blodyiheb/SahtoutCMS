<?php
return [
    // Page meta
    'page_description_smtp' => 'SMTP Settings for Sahtout WoW Server',
    'page_title_smtp' => 'SMTP Settings',

    // Status
    'status' => 'Status:',
    'msg_smtp_enabled' => 'SMTP Enabled',
    'msg_smtp_disabled' => 'SMTP Disabled',

    // Errors
    'err_fix_errors' => 'Please fix the following errors:',
    'err_smtp_host_required' => 'SMTP Host is required.',
    'err_smtp_user_required' => 'SMTP Username is required.',
    'err_smtp_pass_required' => 'SMTP Password is required.',
    'err_smtp_test_failed' => 'SMTP test failed:',
    'err_config_dir_not_writable' => 'Config directory is not writable: %s',
    'err_failed_write_config' => 'Failed to write config file: %s',
    'error_direct_access' => 'Direct access to this file is not allowed.',

    // Mail test
    'mail_test_subject' => 'Test Email - Sahtout CMS',
    'mail_test_body' => 'This is a test email from your Sahtout CMS admin settings.',

    // Success
    'msg_smtp_saved' => 'SMTP settings saved successfully!',

    // Section titles
    'settings_smtp' => 'SMTP Settings',

    // Labels
    'label_smtp_enabled' => 'Enable SMTP',
    'help_smtp_enabled' => 'Enable to send emails via SMTP server.',
    'label_smtp_host' => 'SMTP Host',
    'placeholder_smtp_host' => 'e.g., smtp.gmail.com',
    'label_email_address' => 'Email Address',
    'placeholder_email' => 'e.g., yourname@gmail.com',
    'label_app_password' => 'App Password / SMTP Password',
    'placeholder_app_password' => 'App password for Gmail/Outlook',
    'help_smtp_pass' => 'For Gmail, use an App Password. For other providers, use your email password.',
    'label_from_email' => 'From Email',
    'placeholder_from_email' => 'e.g., noreply@yourdomain.com',
    'label_from_name' => 'From Name',
    'placeholder_from_name' => 'e.g., Sahtout Account',
    'label_port' => 'Port',
    'placeholder_port_tls_ssl' => '587 for TLS',
    'label_encryption' => 'Encryption',
    'help_smtp_secure' => 'Most providers use TLS on port 587.',

    // Buttons
    'btn_save_test_smtp' => 'Save & Test SMTP',
];
?>