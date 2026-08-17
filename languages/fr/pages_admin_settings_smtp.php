<?php
return [
    // Page meta
    'page_description_smtp' => 'Paramètres SMTP pour le serveur WoW Sahtout',
    'page_title_smtp' => 'Paramètres SMTP',

    // Status
    'status' => 'Statut :',
    'msg_smtp_enabled' => 'SMTP activé',
    'msg_smtp_disabled' => 'SMTP désactivé',

    // Errors
    'err_fix_errors' => 'Veuillez corriger les erreurs suivantes :',
    'err_smtp_host_required' => 'L\'hôte SMTP est requis.',
    'err_smtp_user_required' => 'Le nom d\'utilisateur SMTP est requis.',
    'err_smtp_pass_required' => 'Le mot de passe SMTP est requis.',
    'err_smtp_test_failed' => 'Le test SMTP a échoué :',
    'err_config_dir_not_writable' => 'Le répertoire de configuration n\'est pas accessible en écriture : %s',
    'err_failed_write_config' => 'Échec de l\'écriture du fichier de configuration : %s',
    'error_direct_access' => 'L\'accès direct à ce fichier n\'est pas autorisé.',

    // Mail test
    'mail_test_subject' => 'Email de test - Sahtout CMS',
    'mail_test_body' => 'Ceci est un email de test depuis les paramètres d\'administration de Sahtout CMS.',

    // Success
    'msg_smtp_saved' => 'Paramètres SMTP enregistrés avec succès !',

    // Section titles
    'settings_smtp' => 'Paramètres SMTP',

    // Labels
    'label_smtp_enabled' => 'Activer SMTP',
    'help_smtp_enabled' => 'Activez pour envoyer des emails via un serveur SMTP.',
    'label_smtp_host' => 'Hôte SMTP',
    'placeholder_smtp_host' => 'ex., smtp.gmail.com',
    'label_email_address' => 'Adresse email',
    'placeholder_email' => 'ex., votre.nom@gmail.com',
    'label_app_password' => 'Mot de passe d\'application / SMTP',
    'placeholder_app_password' => 'Mot de passe d\'application pour Gmail/Outlook',
    'help_smtp_pass' => 'Pour Gmail, utilisez un mot de passe d\'application. Pour d\'autres fournisseurs, utilisez votre mot de passe email.',
    'label_from_email' => 'Email d\'expédition',
    'placeholder_from_email' => 'ex., noreply@votredomaine.com',
    'label_from_name' => 'Nom d\'expédition',
    'placeholder_from_name' => 'ex., Compte Sahtout',
    'label_port' => 'Port',
    'placeholder_port_tls_ssl' => '587 pour TLS',
    'label_encryption' => 'Chiffrement',
    'help_smtp_secure' => 'La plupart des fournisseurs utilisent TLS sur le port 587.',

    // Buttons
    'btn_save_test_smtp' => 'Enregistrer et tester SMTP',
];
?>