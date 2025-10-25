<?php
return [
    // Mensagens de Sucesso
    'msg_reward_claimed' => 'Recompensa reivindicada com sucesso para %s. +%d pontos foram adicionados à sua conta.',

    // Mensagens de Erro
    'err_invalid_csrf' => 'Token CSRF inválido.',
    'err_invalid_user_id' => 'ID de usuário inválido.',
    'err_user_not_found' => 'ID de usuário não encontrado em user_currencies.',
    'err_site_not_found' => 'ID do site não encontrado em vote_sites: %s.',
    'err_no_unclaimed_votes' => 'Nenhum voto não reivindicado disponível para o usuário: %s.',
    'err_database_generic' => 'Erro no banco de dados: %s',
    'err_db_connection_failed' => 'Falha na conexão com o banco de dados.',
];
