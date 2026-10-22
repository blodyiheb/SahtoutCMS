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
    'err_smtp_from_required' => 'L\'email d\'expédition est requis.',
    'err_smtp_from_invalid' => 'L\'email d\'expédition doit être une adresse email valide.',
    'err_test_email_invalid' => 'Veuillez saisir une adresse email valide pour le destinataire du test.',
    'err_composer_missing' => 'PHPMailer n\'est pas installé. Veuillez exécuter "composer install" à la racine du projet pour activer la fonctionnalité SMTP.',
    'err_smtp_test_failed' => 'Le test SMTP a échoué :',
    'err_invalid_csrf' => 'Jeton CSRF invalide.',
    'err_smtp_port_invalid' => 'Le port doit être compris entre 1 et 65535.',
    'err_smtp_secure_invalid' => 'Le chiffrement doit être tls, ssl ou vide.',
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
    'label_smtp_user' => 'Nom d\'utilisateur SMTP / Email',
    'placeholder_smtp_user' => 'ex : votrenom@gmail.com, resend, apikey',
    'label_test_email' => 'Envoyer l\'email de test à',
    'placeholder_test_email' => 'Vide = nom d\'utilisateur SMTP si c\'est un email, sinon l\'adresse d\'expédition',
    'help_test_email' => 'Où l\'email de test est envoyé. Laissez vide pour utiliser votre nom d\'utilisateur SMTP s\'il s\'agit d\'une adresse email, sinon l\'adresse d\'expédition.',
    'label_app_password' => 'Mot de passe d\'application / SMTP',
    'placeholder_app_password' => 'Mot de passe d\'application pour Gmail/Outlook',
    'help_smtp_pass' => 'Laissez vide pour conserver le mot de passe actuellement enregistré. Pour Gmail, utilisez un mot de passe d\'application ; pour les autres fournisseurs, votre mot de passe SMTP ou votre clé API.',
    'label_from_email' => 'Email d\'expédition',
    'placeholder_from_email' => 'ex., noreply@votredomaine.com',
    'label_from_name' => 'Nom d\'expédition',
    'placeholder_from_name' => 'ex., Compte Sahtout',
    'label_port' => 'Port',
    'placeholder_port' => '587 (TLS), 465 (SSL), 25 (non chiffré)',
    'help_smtp_port' => 'Ports SMTP courants : 587 (TLS), 465 (SSL), 25 (sans chiffrement). Certains fournisseurs utilisent aussi 2525.',
    'label_encryption' => 'Chiffrement',
    'help_smtp_secure' => 'La plupart des fournisseurs utilisent TLS sur le port 587.',
    'label_allow_self_signed' => 'Autoriser les certificats auto-signés',
    'help_allow_self_signed' => 'À activer uniquement pour le développement local ou les serveurs de test privés utilisant des certificats SSL/TLS auto-signés. Laissez désactivé en production.',

    // Buttons
    'btn_save_test_smtp' => 'Enregistrer et tester SMTP',
];
?>