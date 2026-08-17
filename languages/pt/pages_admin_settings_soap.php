<?php
return [
    // Page meta
    'page_description_soap' => 'Configurações SOAP para o servidor WoW Sahtout',
    'title_soap_settings' => 'Configurações SOAP',

    // Status
    'status' => 'Status:',
    'status_soap_configured' => 'SOAP configurado',
    'status_soap_not_configured' => 'SOAP não configurado',

    // Errors
    'error_box_title' => 'Por favor, corrija os seguintes erros:',
    'error_soap_url_required' => 'A URL SOAP é obrigatória.',
    'error_soap_user_required' => 'O nome de usuário da conta GM é obrigatório.',
    'error_soap_pass_required' => 'A senha SOAP é obrigatória.',
    'error_db_query' => 'Erro de consulta no banco de dados: %s',
    'error_account_not_exist' => 'A conta %s não existe no banco de dados Auth.',
    'error_account_not_gm_level_3' => 'A conta %s existe, mas não é de nível GM 3.',
    'error_config_dir_not_writable' => 'O diretório de configuração não tem permissão de escrita: %s',
    'error_config_file_write_failed' => 'Falha ao escrever o arquivo de configuração: %s',

    // Success
    'success_soap_settings_saved' => 'Configurações SOAP salvas com sucesso!',

    // Section titles
    'header_soap_settings' => 'Configurações SOAP',

    // Labels
    'label_soap_url' => 'URL SOAP',
    'placeholder_soap_url' => 'ex., http://127.0.0.1:7878',
    'help_soap_url' => 'A URL onde o serviço SOAP do seu servidor WoW está em execução.',
    'label_soap_user' => 'Nome de usuário da conta GM',
    'placeholder_soap_user' => 'Deve ser de nível GM 3',
    'help_soap_user' => 'A conta deve ter nível GM 3 no banco de dados.',
    'label_soap_pass' => 'Senha SOAP',
    'placeholder_soap_pass' => 'Senha SOAP = Senha da conta',
    'help_soap_pass' => 'Esta é a senha da conta GM acima.',

    // Buttons
    'button_save_verify_soap' => 'Salvar e verificar SOAP',

    // Info Box
    'info_box_title' => 'Passos importantes',
    'info_step_1' => 'Certifique-se de que a conta GM exista em seu banco de dados Auth e tenha nível GM 3 em <code>account_access</code> com <code>RealmID = -1</code>.',
    'info_step_2' => 'Abra seu arquivo <code>worldserver.conf</code> e defina: <strong>SOAP.Enabled = 1</strong>',
    'info_step_3' => 'Certifique-se de que a porta SOAP em <code>soap_url</code> esteja correta e acessível.',
];
?>