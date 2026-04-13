<?php
return [
    // Success Messages
    'msg_reward_claimed' => 'Reward successfully claimed for %s. +%d points have been added to your account.',

    // Error Messages
    'err_invalid_csrf' => 'Invalid CSRF token.',
    'err_invalid_user_id' => 'Invalid user ID.',
    'err_user_not_found' => 'User ID not found in user_currencies.',
    'err_site_not_found' => 'Site ID not found in vote_sites: %s.',
    'err_no_unclaimed_votes' => 'No unclaimed votes available for user: %s.',
    'err_database_generic' => 'Database error: %s',
    'err_db_connection_failed' => 'Database connection failed.',
];
?>