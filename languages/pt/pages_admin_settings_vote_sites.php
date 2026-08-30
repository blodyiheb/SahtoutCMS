<?php
return [
    // Page meta
    'page_description_vote_sites' => 'Gerenciamento de sites de votação para o servidor WoW Sahtout',
    'page_title_manage_vote_sites' => 'Gerenciar sites de votação',

    // Errors
    'err_fix_errors' => 'Por favor, corrija os seguintes erros:',
    'err_invalid_csrf' => 'Token CSRF inválido.',
    'err_permission_denied' => 'Permissão negada.',
    'err_database' => 'Erro no banco de dados: ',
    'err_vote_site_not_found' => 'Site de votação não encontrado.',
    'err_callback_file_name_required' => 'O nome do arquivo de retorno de chamada é obrigatório.',
    'err_invalid_callback_file_name' => 'O nome do arquivo de retorno deve ser alfanumérico com sublinhados ou hífens.',
    'err_callback_file_name_too_long' => 'O nome do arquivo de retorno não deve exceder 50 caracteres.',
    'err_callback_file_name_exists' => 'O nome do arquivo de retorno já existe.',
    'err_siteid_required' => 'O ID do site é obrigatório.',
    'err_siteid_too_long' => 'O ID do site não deve exceder 255 caracteres.',
    'err_url_format_required' => 'O formato da URL de votação é obrigatório.',
    'err_url_format_too_long' => 'O formato da URL não deve exceder 255 caracteres.',
    'err_site_name_required' => 'O nome do site é obrigatório.',
    'err_site_name_too_long' => 'O nome do site não deve exceder 50 caracteres.',
    'err_invalid_image_url' => 'A URL da imagem do botão é muito longa.',
    'err_invalid_cooldown' => 'As horas de recarga devem estar entre 1 e 999.',
    'err_invalid_reward' => 'Os pontos de recompensa devem estar entre 1 e 999.',
    'err_callback_secret_too_long' => 'O segredo de retorno não deve exceder 64 caracteres.',
    'err_image_too_large' => 'O tamanho da imagem não deve exceder 1 MB.',
    'err_image_upload_failed' => 'Falha no envio da imagem: ',
    'err_invalid_image_type' => 'Apenas imagens JPEG, PNG e GIF são permitidas.',

    // Success messages
    'msg_vote_site_saved' => 'Site de votação salvo com sucesso!',
    'msg_vote_site_deleted' => 'Site de votação excluído com sucesso!',
    'msg_image_deleted' => 'Imagem excluída com sucesso!',
    'msg_no_vote_sites' => 'Nenhum site de votação disponível.',

    // Section titles
    'title_edit_vote_site' => 'Editar site de votação',
    'title_add_vote_site' => 'Adicionar site de votação',
    'title_vote_sites_list' => 'Lista de sites de votação',

    // Labels
    'label_callback_file_name' => 'Nome do arquivo de retorno',
    'label_callback_file_name_info' => 'Nome para identificar o site de votação em retornos de chamada (gtop100, top100arena, etc.)',
    'placeholder_callback_file_name' => 'Digite o nome do arquivo de retorno (ex. arenaTop100)',
    'label_site_name' => 'Nome do site',
    'placeholder_site_name' => 'Digite o nome do site',
    'label_siteid' => 'ID do site',
    'placeholder_siteid' => 'Digite o ID do servidor no site de votação',
    'label_siteid_info' => 'O ID único do seu servidor no site de votação (ex. SahtoutServer, 12345).',
    'label_url_format' => 'Formato da URL de votação',
    'placeholder_url_format' => 'ex. https://site.com/vote/{siteid}/{userid}',
    'label_url_format_info' => 'Use {siteid}, {userid} ou {username} como marcadores de posição.',
    'label_button_image' => 'Enviar imagem do botão',
    'placeholder_button_image' => 'Clique ou arraste para enviar uma imagem de botão',
    'label_button_image_url' => 'URL da imagem do botão (Opcional)',
    'placeholder_button_image_url' => 'Digite a URL da imagem do botão (opcional)',
    'label_image_url_info' => 'Digite uma URL de imagem se preferir não enviar uma imagem. Deixe vazio para remover a imagem.',
    'label_cooldown_hours' => 'Horas de recarga',
    'placeholder_cooldown_hours' => 'Digite as horas de recarga',
    'label_reward_points' => 'Pontos de recompensa',
    'placeholder_reward_points' => 'Digite os pontos de recompensa',
    'label_uses_callback' => 'Usa retorno de chamada',
    'label_callback_secret' => 'Segredo de retorno',
    'placeholder_callback_secret' => 'Digite o segredo de retorno (opcional)',
    'label_actions' => 'Ações',
    'label_no_image' => 'Sem imagem',

    // Options
    'option_yes' => 'Sim',
    'option_no' => 'Não',

    // Buttons
    'btn_save_vote_site' => 'Salvar site de votação',
    'btn_reset' => 'Redefinir formulário',
    'btn_edit' => 'Editar',
    'btn_delete' => 'Excluir',
    'btn_delete_image' => 'Excluir imagem',
    'btn_cancel' => 'Cancelar',
    'btn_confirm_delete' => 'Confirmar exclusão',

    // Delete Modal
    'confirm_delete_title' => 'Confirmar exclusão',
    'confirm_delete_vote_site' => 'Tem certeza de que deseja excluir este site de votação?',
    'confirm_delete_irreversible' => 'Esta ação não pode ser desfeita.',
    'confirm_delete_image' => 'Tem certeza de que deseja excluir esta imagem?',
];
?>