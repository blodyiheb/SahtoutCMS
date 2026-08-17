<?php
return [
    // Page meta
    'page_description_smtp' => 'Configurações SMTP para o servidor WoW Sahtout',
    'page_title_smtp' => 'Configurações SMTP',

    // Status
    'status' => 'Status:',
    'msg_smtp_enabled' => 'SMTP ativado',
    'msg_smtp_disabled' => 'SMTP desativado',

    // Errors
    'err_fix_errors' => 'Por favor, corrija os seguintes erros:',
    'err_smtp_host_required' => 'O host SMTP é obrigatório.',
    'err_smtp_user_required' => 'O usuário SMTP é obrigatório.',
    'err_smtp_pass_required' => 'A senha SMTP é obrigatória.',
    'err_smtp_test_failed' => 'O teste SMTP falhou:',
    'err_config_dir_not_writable' => 'O diretório de configuração não tem permissão de escrita: %s',
    'err_failed_write_config' => 'Falha ao escrever o arquivo de configuração: %s',
    'error_direct_access' => 'O acesso direto a este arquivo não é permitido.',

    // Mail test
    'mail_test_subject' => 'E-mail de teste - Sahtout CMS',
    'mail_test_body' => 'Este é um e-mail de teste das configurações de administração do Sahtout CMS.',

    // Success
    'msg_smtp_saved' => 'Configurações SMTP salvas com sucesso!',

    // Section titles
    'settings_smtp' => 'Configurações SMTP',

    // Labels
    'label_smtp_enabled' => 'Ativar SMTP',
    'help_smtp_enabled' => 'Ative para enviar e-mails via servidor SMTP.',
    'label_smtp_host' => 'Host SMTP',
    'placeholder_smtp_host' => 'ex., smtp.gmail.com',
    'label_email_address' => 'Endereço de e-mail',
    'placeholder_email' => 'ex., seu.nome@gmail.com',
    'label_app_password' => 'Senha de aplicativo / SMTP',
    'placeholder_app_password' => 'Senha de aplicativo para Gmail/Outlook',
    'help_smtp_pass' => 'Para Gmail, use uma senha de aplicativo. Para outros provedores, use sua senha de e-mail.',
    'label_from_email' => 'E-mail remetente',
    'placeholder_from_email' => 'ex., noreply@seudominio.com',
    'label_from_name' => 'Nome remetente',
    'placeholder_from_name' => 'ex., Conta Sahtout',
    'label_port' => 'Porta',
    'placeholder_port_tls_ssl' => '587 para TLS',
    'label_encryption' => 'Criptografia',
    'help_smtp_secure' => 'A maioria dos provedores usa TLS na porta 587.',

    // Buttons
    'btn_save_test_smtp' => 'Salvar e testar SMTP',
];
?>