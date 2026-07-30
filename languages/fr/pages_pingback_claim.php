<?php
return [
    // Success Messages
    'msg_reward_claimed' => 'Récompense réclamée avec succès pour %s. +%d points ont été ajoutés à votre compte.',

    // Error Messages
    'err_invalid_csrf' => 'Jeton CSRF invalide.',
    'err_invalid_user_id' => 'ID utilisateur invalide.',
    'err_user_not_found' => 'ID utilisateur non trouvé dans user_currencies.',
    'err_site_not_found' => 'ID du site non trouvé dans vote_sites : %s.',
    'err_no_unclaimed_votes' => 'Aucun vote non réclamé disponible pour l\'utilisateur : %s.',
    'err_database_generic' => 'Erreur de base de données : %s',
    'err_db_connection_failed' => 'Échec de la connexion à la base de données.',
];
?>