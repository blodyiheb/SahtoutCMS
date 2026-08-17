<?php
return [
    // Page meta
    'page_description_soap' => 'Paramètres SOAP pour le serveur WoW Sahtout',
    'title_soap_settings' => 'Paramètres SOAP',

    // Status
    'status' => 'Statut :',
    'status_soap_configured' => 'SOAP configuré',
    'status_soap_not_configured' => 'SOAP non configuré',

    // Errors
    'error_box_title' => 'Veuillez corriger les erreurs suivantes :',
    'error_soap_url_required' => 'L\'URL SOAP est requise.',
    'error_soap_user_required' => 'Le nom d\'utilisateur du compte GM est requis.',
    'error_soap_pass_required' => 'Le mot de passe SOAP est requis.',
    'error_db_query' => 'Erreur de requête de base de données : %s',
    'error_account_not_exist' => 'Le compte %s n\'existe pas dans la base de données Auth.',
    'error_account_not_gm_level_3' => 'Le compte %s existe mais n\'est pas de niveau GM 3.',
    'error_config_dir_not_writable' => 'Le répertoire de configuration n\'est pas accessible en écriture : %s',
    'error_config_file_write_failed' => 'Échec de l\'écriture du fichier de configuration : %s',

    // Success
    'success_soap_settings_saved' => 'Paramètres SOAP enregistrés avec succès !',

    // Section titles
    'header_soap_settings' => 'Paramètres SOAP',

    // Labels
    'label_soap_url' => 'URL SOAP',
    'placeholder_soap_url' => 'ex., http://127.0.0.1:7878',
    'help_soap_url' => 'L\'URL où le service SOAP de votre serveur WoW est en cours d\'exécution.',
    'label_soap_user' => 'Nom d\'utilisateur du compte GM',
    'placeholder_soap_user' => 'Doit être de niveau GM 3',
    'help_soap_user' => 'Le compte doit avoir le niveau GM 3 dans la base de données.',
    'label_soap_pass' => 'Mot de passe SOAP',
    'placeholder_soap_pass' => 'Mot de passe SOAP = Mot de passe du compte',
    'help_soap_pass' => 'Il s\'agit du mot de passe du compte GM ci-dessus.',

    // Buttons
    'button_save_verify_soap' => 'Enregistrer et vérifier SOAP',

    // Info Box
    'info_box_title' => 'Étapes importantes',
    'info_step_1' => 'Assurez-vous que le compte GM existe dans votre base de données Auth et qu\'il a le niveau GM 3 dans <code>account_access</code> avec <code>RealmID = -1</code>.',
    'info_step_2' => 'Ouvrez votre fichier <code>worldserver.conf</code> et définissez : <strong>SOAP.Enabled = 1</strong>',
    'info_step_3' => 'Assurez-vous que le port SOAP dans <code>soap_url</code> est correct et accessible.',
];
?>