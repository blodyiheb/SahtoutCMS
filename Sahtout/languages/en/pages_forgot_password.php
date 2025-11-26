<?php
return [
    'page_title' => '- Forgot Password',
    'meta_description' => 'Request a password reset link for your World of Warcraft server account.',
    'forgot_title' => 'Forgot Password',
    'username_or_email_placeholder' => 'Username or Email',
    'send_button' => 'Send Reset Link',
    'login_link' => 'Remembered your password?',
    'login_link_text' => '<a href="%s">Log in here</a>',
    'email_subject' => 'Password Reset Request',
    'email_greeting' => 'Welcome, {username}!',
    'email_request' => 'You requested a password reset. Please click the button below to reset your password:',
    'email_button' => 'Reset Password',
    'email_expiry' => 'This link will expire in 1 minute. If you didn\'t request this, please ignore this email.',
    'error_username_or_email_required' => 'Username or email is required',
    'error_invalid_email' => 'Please provide a valid email address.',
    'error_recaptcha_failed' => 'reCAPTCHA verification failed.',
    'error_token_store_failed' => 'Failed to store reset token.',
    'error_email_failed' => 'Failed to send email: ',
    'error_database' => 'Database error occurred. Please try again.',
    'error_reset_limit_exceeded' => 'You have reached the maximum number of password reset attempts. Please try again later.',
    'success_no_email' => 'A reset password token has been created. Contact the admin to provide you the link to change your password.',
    'success_email_sent' => 'If the provided username or email exists, a password reset link has been sent.'
];
?>