<?php
return [
    // Page meta
    'page_description_realm' => 'Configuração do reino para o servidor WoW Sahtout',
    'page_title_realm' => 'Configuração do reino',

    // Errors
    'err_fix_errors' => 'Por favor, corrija os seguintes erros:',
    'err_invalid_csrf' => 'Token CSRF inválido.',
    'err_realm_name_required' => 'O nome do reino é obrigatório.',
    'err_realm_ip_required' => 'O endereço do reino / Host é obrigatório.',
    'err_realm_ip_invalid' => 'O endereço do reino / Host é inválido.',
    'err_realm_check_address_required' => 'O endereço de verificação de status é obrigatório.',
    'err_realm_check_address_invalid' => 'O endereço de verificação de status contém caracteres inválidos.',
    'err_realm_port_invalid' => 'A porta do reino deve ser um número válido (1-65535).',
    'error_realm_logo_too_large' => 'O tamanho do logo do reino excede 2 MB.',
    'error_invalid_realm_logo_type' => 'Tipo de arquivo inválido. Apenas PNG, JPG ou WebP são permitidos.',
    'error_realm_logo_upload_failed' => 'O diretório de upload não está acessível ou não tem permissão de escrita.',
    'err_config_dir_not_writable' => 'O diretório de configuração não tem permissão de escrita: %s',
    'err_write_realm_config' => 'Não foi possível escrever o arquivo de configuração do reino: %s',

    // Success
    'msg_realm_saved' => 'Configuração do reino salva com sucesso!',

    // Section titles
    'section_realm_config' => 'Configuração do reino',

    // Labels
    'label_current_logo' => 'Logo atual',
    'label_realm_name' => 'Nome do reino',
    'placeholder_realm_name' => 'Digite o nome do reino',
    'label_realm_ip' => 'Endereço do reino / Host',
    'label_realm_check_address' => 'Endereço de verificação de status',
    'placeholder_realm_address' => 'play.example.com',
    'placeholder_realm_check_address' => '127.0.0.1',
    'note_realm_check_address' => 'Usado internamente pelo site para verificar se o servidor está online. Normalmente 127.0.0.1 quando o site e o servidor do jogo estão na mesma máquina.',
    'label_realm_port' => 'Porta do reino',
    'label_player_display' => 'Exibição de jogadores',
    'player_display_all' => 'Todos os jogadores',
    'player_display_all_desc' => 'Mostrar humanos e bots juntos como uma única contagem de jogadores.',
    'player_display_separate' => 'Humanos / Bots',
    'player_display_separate_desc' => 'Mostrar jogadores reais e playerbots como contagens separadas.',
    'label_realm_logo' => 'Logo do reino',
    'placeholder_realm_logo' => 'Clique ou arraste para enviar um novo logo',
    'note_realm_logo' => 'Envie um novo logo para seu reino. Deixe vazio para manter o logo atual.',
    'note_realm_config' => 'Isso configura as definições para um único reino.',

    // Buttons
    'btn_save_realm' => 'Salvar configuração do reino',
];
?>