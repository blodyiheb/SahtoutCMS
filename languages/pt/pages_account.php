<?php
// Arquivo de idioma para account.php (Português)
return [
    // Título da página
    'page_title' => ' - Conta : %s',

    // Painel e títulos de seção
    'dashboard_title' => 'Painel da Conta',
    'section_account_info' => 'Informações da Conta',
    'section_quick_stats' => 'Estatísticas Rápidas',
    'section_your_characters' => 'Seus Personagens',
    'section_account_activity' => 'Atividade da Conta',
    'section_change_email' => 'Alterar Email',
    'section_change_password' => 'Alterar Senha',
    'section_change_avatar' => 'Alterar Avatar',
    'section_account_actions' => 'Ações da Conta',

    // Abas
    'tab_overview' => 'Visão Geral',
    'tab_characters' => 'Personagens',
    'tab_activity' => 'Atividade',
    'tab_security' => 'Segurança',
    'tab_vote' => 'Votar',
    
    // Títulos dos cartões
    'card_basic_info' => 'Informações Básicas',
    'card_contact' => 'Contato',
    'card_activity' => 'Atividade',
    'card_characters' => 'Personagens',
    'card_wealth' => 'Riqueza',

    // Rótulos
    'label_username' => 'Nome de Usuário',
    'label_account_id' => 'ID da Conta',
    'label_status' => 'Status',
    'label_rank' => 'Cargo',
    'label_online' => 'Online',
    'label_email' => 'Email',
    'label_expansion' => 'Expansão',
    'label_join_date' => 'Data de Registro',
    'label_last_login' => 'Último Login',
    'label_total_characters' => 'Total',
    'label_highest_level' => 'Maior Nível',
    'label_total_gold' => 'Total de Ouro',
    'label_points' => 'Pontos',
    'label_tokens' => 'Fichas',
    'label_level' => 'Nível',
    'label_gold' => 'Ouro',
    'label_select_city' => 'Selecionar uma cidade',
    'label_current_password' => 'Senha Atual',
    'label_new_email' => 'Novo Email',
    'label_new_password' => 'Nova Senha',
    'label_confirm_password' => 'Confirmar Nova Senha',
    'label_select_avatar' => 'Selecionar Avatar',

    // Campos de entrada (placeholders)
    'placeholder_current_password' => 'Insira a senha atual',
    'placeholder_new_email' => 'Insira o novo email',
    'placeholder_new_password' => 'Insira a nova senha',
    'placeholder_confirm_password' => 'Confirme a nova senha',
    'select_city_placeholder' => 'Selecione uma cidade',

    // Botões e ações
    'button_admin_panel' => 'Painel Administrativo',
    'button_teleport' => 'Teleportar',
    'button_update_email' => 'Atualizar Email',
    'button_change_password' => 'Alterar Senha',
    'button_update_avatar' => 'Atualizar Avatar',
    'action_logout' => 'Sair',
    'action_request_deletion' => 'Solicitar Exclusão da Conta',
    'action_email_changed' => 'Email Alterado',
    'action_password_changed' => 'Senha Alterada',
    'action_avatar_changed' => 'Avatar Alterado',
    'action_teleport' => 'Teleportar',

    // Status
    'status_banned' => 'Banido',
    'status_frozen' => 'Congelado',
    'status_active' => 'Ativo',
    'status_online' => 'Online',
    'status_offline' => 'Offline',
    'ban_no_reason' => 'Sem motivo informado',
    'ban_permanent' => 'Permanente',

    // Cargos de GM
    'gm_rank_gm' => 'Game Master Nível %s%s',
    'gm_rank_admin' => 'Administrador',
    'gm_rank_moderator' => 'Moderador',
    'gm_rank_player' => 'Jogador',
    'gm_suffix_admin' => ' (S)',
    'gm_suffix_moderator' => ' (M)',
    'gm_suffix_administrator' => ' (A)',

    // Expansões
    'expansion_0' => 'Clássico',
    'expansion_1' => 'The Burning Crusade',
    'expansion_2' => 'Wrath of the Lich King',

    // Avatares (nomes de arquivo, ajustar conforme necessário)
    'avatar_user.jpg' => 'Avatar Padrão',
    'avatar_default' => 'Avatar Padrão',
    // Adicione mais traduções de avatares conforme necessário, ex: 'avatar_custom1.png' => 'Avatar Personalizado 1'

    // Mensagens
    'message_email_updated' => 'Email atualizado com sucesso!',
    'message_password_changed' => 'Senha alterada com sucesso!',
    'message_avatar_updated' => 'Avatar atualizado com sucesso!',
    'message_character_teleported' => 'Personagem teleportado para %s!',

    // Erros
    'error_database_connection' => 'Falha na conexão com o banco de dados',
    'error_invalid_form_submission' => 'Envio de formulário inválido',
    'error_invalid_email_format' => 'Formato de email inválido',
    'error_email_in_use' => 'O email já está em uso por outra conta',
    'error_account_not_found' => 'Conta não encontrada',
    'error_incorrect_password' => 'Senha atual incorreta',
    'error_updating_email' => 'Erro ao atualizar o email',
    'error_passwords_dont_match' => 'As novas senhas não coincidem',
    'error_password_too_short' => 'A senha deve ter pelo menos 6 caracteres',
    'error_updating_password' => 'Erro ao atualizar a senha',
    'error_invalid_character_id' => 'ID de personagem inválido',
    'error_rapid_submission' => 'Aguarde alguns segundos antes de tentar novamente',
    'error_teleport_cooldown' => 'Teleport em recarga. Por favor, aguarde %s minuto%s',
    'error_character_not_found' => 'Personagem não encontrado',
    'error_character_online' => 'O personagem deve estar offline para teleportar',
    'error_invalid_destination' => 'Destino de teleporte inválido',
    'error_teleporting_character' => 'Erro ao teleportar o personagem',
    'error_logging_teleport' => 'Erro ao registrar o teleporte',
    'error_invalid_avatar' => 'Avatar selecionado inválido',
    'error_updating_avatar' => 'Erro ao atualizar o avatar',

    // Diversos
    'email_not_set' => 'Não definido',
    'never' => 'Nunca',
    'no_characters' => 'Você ainda não tem personagens.',
    'no_activity' => 'Sem atividade recente.',
    'none' => 'N/D',
    'debug_warnings' => 'Avisos de Depuração',
    'confirm_teleport' => 'Teleportar este personagem?',
    'teleport_cooldown' => 'Recarga de Teleporte: %s minuto%s',
    'teleport_details' => 'Para %s',
    'status_icon' => 'Ícone de Status',
    'race_icon' => 'Ícone de Raça',
    'class_icon' => 'Ícone de Classe',
    'faction_icon' => 'Ícone de Facção',
    'gold_icon' => 'Ícone de Ouro',
    'avatar_alt' => 'Avatar',
    'city_shattrath' => 'Shattrath',
    'city_dalaran' => 'Dalaran',
];
?>
