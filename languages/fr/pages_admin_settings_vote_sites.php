<?php
return [
    // Page meta
    'page_description_vote_sites' => 'Gestion des sites de vote pour le serveur WoW Sahtout',
    'page_title_manage_vote_sites' => 'Gérer les sites de vote',

    // Errors
    'err_fix_errors' => 'Veuillez corriger les erreurs suivantes :',
    'err_invalid_csrf' => 'Jeton CSRF invalide.',
    'err_permission_denied' => 'Permission refusée.',
    'err_database' => 'Erreur de base de données : ',
    'err_vote_site_not_found' => 'Site de vote non trouvé.',
    'err_callback_file_name_required' => 'Le nom du fichier de rappel est requis.',
    'err_invalid_callback_file_name' => 'Le nom du fichier de rappel doit être alphanumérique avec des underscores ou des tirets.',
    'err_callback_file_name_too_long' => 'Le nom du fichier de rappel ne doit pas dépasser 50 caractères.',
    'err_callback_file_name_exists' => 'Le nom du fichier de rappel existe déjà.',
    'err_siteid_required' => 'L\'ID du site est requis.',
    'err_siteid_too_long' => 'L\'ID du site ne doit pas dépasser 255 caractères.',
    'err_url_format_required' => 'Le format de l\'URL de vote est requis.',
    'err_url_format_too_long' => 'Le format de l\'URL ne doit pas dépasser 255 caractères.',
    'err_site_name_required' => 'Le nom du site est requis.',
    'err_site_name_too_long' => 'Le nom du site ne doit pas dépasser 50 caractères.',
    'err_invalid_image_url' => 'L\'URL de l\'image du bouton est trop longue.',
    'err_invalid_cooldown' => 'Les heures de récupération doivent être comprises entre 1 et 999.',
    'err_invalid_reward' => 'Les points de récompense doivent être compris entre 1 et 255.',
    'err_callback_secret_too_long' => 'Le secret de rappel ne doit pas dépasser 64 caractères.',
    'err_image_too_large' => 'La taille de l\'image ne doit pas dépasser 1 Mo.',
    'err_image_upload_failed' => 'Échec du téléchargement de l\'image : ',
    'err_invalid_image_type' => 'Seules les images JPEG, PNG et GIF sont autorisées.',

    // Success messages
    'msg_vote_site_saved' => 'Site de vote enregistré avec succès !',
    'msg_vote_site_deleted' => 'Site de vote supprimé avec succès !',
    'msg_image_deleted' => 'Image supprimée avec succès !',
    'msg_no_vote_sites' => 'Aucun site de vote disponible.',

    // Section titles
    'title_edit_vote_site' => 'Modifier un site de vote',
    'title_add_vote_site' => 'Ajouter un site de vote',
    'title_vote_sites_list' => 'Liste des sites de vote',

    // Labels
    'label_callback_file_name' => 'Nom du fichier de rappel',
    'label_callback_file_name_info' => 'Nom pour identifier le site de vote dans les rappels (gtop100, top100arena, etc.)',
    'placeholder_callback_file_name' => 'Entrez le nom du fichier de rappel (ex. arenaTop100)',
    'label_site_name' => 'Nom du site',
    'placeholder_site_name' => 'Entrez le nom du site',
    'label_siteid' => 'ID du site',
    'placeholder_siteid' => 'Entrez l\'ID du serveur sur le site de vote',
    'label_siteid_info' => 'L\'ID unique de votre serveur sur le site de vote (ex. SahtoutServer, 12345).',
    'label_url_format' => 'Format de l\'URL de vote',
    'placeholder_url_format' => 'ex. https://site.com/vote/{siteid}/{userid}',
    'label_url_format_info' => 'Utilisez {siteid}, {userid} ou {username} comme espaces réservés.',
    'label_button_image' => 'Télécharger l\'image du bouton',
    'placeholder_button_image' => 'Cliquez ou glissez pour télécharger une image de bouton',
    'label_button_image_url' => 'URL de l\'image du bouton (Optionnel)',
    'placeholder_button_image_url' => 'Entrez l\'URL de l\'image du bouton (optionnel)',
    'label_image_url_info' => 'Entrez une URL d\'image si vous préférez ne pas télécharger d\'image. Laissez vide pour effacer l\'image.',
    'label_cooldown_hours' => 'Heures de récupération',
    'placeholder_cooldown_hours' => 'Entrez les heures de récupération',
    'label_reward_points' => 'Points de récompense',
    'placeholder_reward_points' => 'Entrez les points de récompense',
    'label_uses_callback' => 'Utilise le rappel',
    'label_callback_secret' => 'Secret de rappel',
    'placeholder_callback_secret' => 'Entrez le secret de rappel (optionnel)',
    'label_actions' => 'Actions',
    'label_no_image' => 'Pas d\'image',

    // Options
    'option_yes' => 'Oui',
    'option_no' => 'Non',

    // Buttons
    'btn_save_vote_site' => 'Enregistrer le site de vote',
    'btn_reset' => 'Réinitialiser le formulaire',
    'btn_edit' => 'Modifier',
    'btn_delete' => 'Supprimer',
    'btn_delete_image' => 'Supprimer l\'image',
    'btn_cancel' => 'Annuler',
    'btn_confirm_delete' => 'Confirmer la suppression',

    // Delete Modal
    'confirm_delete_title' => 'Confirmer la suppression',
    'confirm_delete_vote_site' => 'Voulez-vous vraiment supprimer ce site de vote ?',
    'confirm_delete_irreversible' => 'Cette action est irréversible.',
    'confirm_delete_image' => 'Voulez-vous vraiment supprimer cette image ?',
];
?>