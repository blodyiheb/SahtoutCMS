<?php
return [
    // Page meta
    'page_description_recaptcha' => 'Paramètres reCAPTCHA pour le serveur WoW Sahtout',
    'page_title_recaptcha' => 'Paramètres reCAPTCHA',

    // Status
    'status' => 'Statut :',
    'msg_recaptcha_enabled' => 'reCAPTCHA activé',
    'msg_recaptcha_disabled' => 'reCAPTCHA désactivé',

    // Errors
    'err_fix_errors' => 'Veuillez corriger les erreurs suivantes :',
    'err_invalid_captcha_type' => 'Type de CAPTCHA invalide sélectionné. Seul reCAPTCHA est pris en charge.',
    'err_recaptcha_keys_required' => 'La clé du site reCAPTCHA et la clé secrète sont requises lorsque reCAPTCHA est activé.',
    'err_cap_dir_not_writable' => 'Le répertoire de configuration reCAPTCHA n\'est pas accessible en écriture : %s',
    'err_failed_write_cap' => 'Échec de l\'écriture du fichier de configuration reCAPTCHA : %s',
    'error_direct_access' => 'Accès direct non autorisé.',

    // Success
    'msg_recaptcha_saved' => 'Paramètres reCAPTCHA enregistrés avec succès !',

    // Section titles
    'settings_recaptcha' => 'Paramètres reCAPTCHA',

    // Labels
    'label_captcha_type' => 'Type de CAPTCHA',
    'option_recaptcha' => 'reCAPTCHA',
    'option_hcaptcha' => 'hCaptcha (À venir)',
    'option_other' => 'Autre (À venir)',
    'help_captcha_type' => 'Actuellement, seul reCAPTCHA v2 est pris en charge.',
    'label_recaptcha_enabled' => 'Activer reCAPTCHA',
    'help_recaptcha_enabled' => 'Activez pour protéger les formulaires contre le spam et les robots.',
    'label_recaptcha_site_key' => 'Clé du site',
    'placeholder_recaptcha_default' => 'Laisser vide pour les clés de test par défaut',
    'help_recaptcha_site_key' => 'Votre clé de site reCAPTCHA depuis la console Google reCAPTCHA.',
    'label_recaptcha_secret_key' => 'Clé secrète',
    'help_recaptcha_secret_key' => 'Votre clé secrète reCAPTCHA depuis la console Google reCAPTCHA.',
    'note_recaptcha_empty' => 'Laissez les champs reCAPTCHA vides pour utiliser les clés de test par défaut lorsqu\'elles sont activées. (Elles fonctionnent pour les tests mais doivent être remplacées en production.)',

    // Buttons
    'btn_save_recaptcha' => 'Enregistrer les paramètres reCAPTCHA',
];
?>