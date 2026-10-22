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
    'err_smtp_from_required' => 'From Email is required.',
    'err_smtp_from_invalid' => 'From Email must be a valid email address.',
    'err_test_email_invalid' => 'Please enter a valid email address for the test recipient.',
    'err_composer_missing' => 'PHPMailer is not installed. Please run "composer install" in the root directory to enable SMTP functionality.',
    'err_smtp_test_failed' => 'SMTP test failed:',
    'err_invalid_csrf' => 'Invalid CSRF token.',
    'err_smtp_port_invalid' => 'Port must be between 1 and 65535.',
    'err_smtp_secure_invalid' => 'Encryption must be tls, ssl, or empty.',
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
    'label_smtp_user' => 'SMTP Username / Email',
    'placeholder_smtp_user' => 'e.g., you@gmail.com, resend, apikey',
    'label_test_email' => 'Send Test Email To',
    'placeholder_test_email' => 'Blank = SMTP username if it is an email, otherwise From address',
    'help_test_email' => 'Where the test email is sent. Leave blank to use your SMTP username if it is an email address, otherwise the From address.',
    'label_app_password' => 'App Password / SMTP Password',
    'placeholder_app_password' => 'App password for Gmail/Outlook',
    'help_smtp_pass' => 'Leave blank to keep the currently saved password. For Gmail, use an App Password; for other providers, use your SMTP password or API key.',
    'label_from_email' => 'From Email',
    'placeholder_from_email' => 'e.g., noreply@yourdomain.com',
    'label_from_name' => 'From Name',
    'placeholder_from_name' => 'e.g., Sahtout Account',
    'label_port' => 'Port',
    'placeholder_port' => '587 (TLS), 465 (SSL), 25 (Plain)',
    'help_smtp_port' => 'Common SMTP ports: 587 (TLS), 465 (SSL), 25 (no encryption). Some providers also use 2525.',
    'label_encryption' => 'Encryption',
    'help_smtp_secure' => 'Most providers use TLS on port 587.',
    'label_allow_self_signed' => 'Allow Self-Signed Certificates',
    'help_allow_self_signed' => 'Only enable this for local development or private test servers with self-signed SSL/TLS certificates. Keep disabled in production.',

    // Buttons
    'btn_save_test_smtp' => 'Save & Test SMTP',
];
?>