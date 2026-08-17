<?php
return [
    // Page meta
    'page_description_recaptcha' => 'reCAPTCHA Settings for Sahtout WoW Server',
    'page_title_recaptcha' => 'reCAPTCHA Settings',

    // Status
    'status' => 'Status:',
    'msg_recaptcha_enabled' => 'reCAPTCHA Enabled',
    'msg_recaptcha_disabled' => 'reCAPTCHA Disabled',

    // Errors
    'err_fix_errors' => 'Please fix the following errors:',
    'err_invalid_captcha_type' => 'Invalid CAPTCHA type selected. Only reCAPTCHA is supported.',
    'err_recaptcha_keys_required' => 'reCAPTCHA Site Key and Secret Key are required when reCAPTCHA is enabled.',
    'err_cap_dir_not_writable' => 'reCAPTCHA config directory is not writable: %s',
    'err_failed_write_cap' => 'Failed to write reCAPTCHA config file: %s',
    'error_direct_access' => 'Direct access not allowed.',

    // Success
    'msg_recaptcha_saved' => 'reCAPTCHA settings saved successfully!',

    // Section titles
    'settings_recaptcha' => 'reCAPTCHA Settings',

    // Labels
    'label_captcha_type' => 'CAPTCHA Type',
    'option_recaptcha' => 'reCAPTCHA',
    'option_hcaptcha' => 'hCaptcha (Coming Soon)',
    'option_other' => 'Other (Coming Soon)',
    'help_captcha_type' => 'Currently only reCAPTCHA v2 is supported.',
    'label_recaptcha_enabled' => 'Enable reCAPTCHA',
    'help_recaptcha_enabled' => 'Enable to protect forms from spam and bots.',
    'label_recaptcha_site_key' => 'Site Key',
    'placeholder_recaptcha_default' => 'Leave empty for default test keys',
    'help_recaptcha_site_key' => 'Your reCAPTCHA site key from Google reCAPTCHA console.',
    'label_recaptcha_secret_key' => 'Secret Key',
    'help_recaptcha_secret_key' => 'Your reCAPTCHA secret key from Google reCAPTCHA console.',
    'note_recaptcha_empty' => 'Leave reCAPTCHA fields empty to use default test keys when enabled. (These work for testing but should be replaced in production.)',

    // Buttons
    'btn_save_recaptcha' => 'Save reCAPTCHA Settings',
];
?>