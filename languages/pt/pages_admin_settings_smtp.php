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
    'err_smtp_from_required' => 'O e-mail de origem é obrigatório.',
    'err_smtp_from_invalid' => 'O e-mail de origem deve ser um endereço de e-mail válido.',
    'err_test_email_invalid' => 'Por favor, insira um endereço de e-mail válido para o destinatário do teste.',
    'err_composer_missing' => 'PHPMailer não está instalado. Por favor, execute "composer install" no diretório raiz para ativar a funcionalidade SMTP.',
    'err_smtp_test_failed' => 'O teste SMTP falhou:',
    'err_invalid_csrf' => 'Token CSRF inválido.',
    'err_smtp_port_invalid' => 'A porta deve estar entre 1 e 65535.',
    'err_smtp_secure_invalid' => 'A criptografia deve ser tls, ssl ou vazia.',
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
    'label_smtp_user' => 'Nome de usuário SMTP / E-mail',
    'placeholder_smtp_user' => 'ex: seunome@gmail.com, resend, apikey',
    'label_test_email' => 'Enviar e-mail de teste para',
    'placeholder_test_email' => 'Vazio = nome de usuário SMTP se for um e-mail, caso contrário o endereço de origem',
    'help_test_email' => 'Para onde o e-mail de teste é enviado. Deixe em branco para usar seu nome de usuário SMTP se for um endereço de e-mail, caso contrário o endereço de origem.',
    'label_app_password' => 'Senha de aplicativo / SMTP',
    'placeholder_app_password' => 'Senha de aplicativo para Gmail/Outlook',
    'help_smtp_pass' => 'Deixe em branco para manter a senha atualmente salva. Para o Gmail, use uma Senha de Aplicativo; para outros provedores, use sua senha SMTP ou chave de API.',
    'label_from_email' => 'E-mail remetente',
    'placeholder_from_email' => 'ex., noreply@seudominio.com',
    'label_from_name' => 'Nome remetente',
    'placeholder_from_name' => 'ex., Conta Sahtout',
    'label_port' => 'Porta',
    'placeholder_port' => '587 (TLS), 465 (SSL), 25 (sem cifração)',
    'help_smtp_port' => 'Portas SMTP comuns: 587 (TLS), 465 (SSL), 25 (sem criptografia). Alguns provedores também usam 2525.',
    'label_encryption' => 'Criptografia',
    'help_smtp_secure' => 'A maioria dos provedores usa TLS na porta 587.',
    'label_allow_self_signed' => 'Permitir certificados autoassinados',
    'help_allow_self_signed' => 'Ative apenas para desenvolvimento local ou servidores de teste privados com certificados SSL/TLS autoassinados. Mantenha desativado em produção.',

    // Buttons
    'btn_save_test_smtp' => 'Salvar e testar SMTP',
];
?>