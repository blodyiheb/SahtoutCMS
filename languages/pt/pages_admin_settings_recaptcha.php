<?php
return [
    // Page meta
    'page_description_recaptcha' => 'Configurações de reCAPTCHA para o servidor WoW Sahtout',
    'page_title_recaptcha' => 'Configurações de reCAPTCHA',

    // Status
    'status' => 'Status:',
    'msg_recaptcha_enabled' => 'reCAPTCHA ativado',
    'msg_recaptcha_disabled' => 'reCAPTCHA desativado',

    // Errors
    'err_fix_errors' => 'Por favor, corrija os seguintes erros:',
    'err_invalid_captcha_type' => 'Tipo de CAPTCHA inválido selecionado. Apenas reCAPTCHA é suportado.',
    'err_recaptcha_keys_required' => 'A chave do site e a chave secreta do reCAPTCHA são obrigatórias quando o reCAPTCHA está ativado.',
    'err_cap_dir_not_writable' => 'O diretório de configuração do reCAPTCHA não tem permissão de escrita: %s',
    'err_failed_write_cap' => 'Falha ao escrever o arquivo de configuração do reCAPTCHA: %s',
    'error_direct_access' => 'Acesso direto não permitido.',

    // Success
    'msg_recaptcha_saved' => 'Configurações de reCAPTCHA salvas com sucesso!',

    // Section titles
    'settings_recaptcha' => 'Configurações de reCAPTCHA',

    // Labels
    'label_captcha_type' => 'Tipo de CAPTCHA',
    'option_recaptcha' => 'reCAPTCHA',
    'option_hcaptcha' => 'hCaptcha (Em breve)',
    'option_other' => 'Outro (Em breve)',
    'help_captcha_type' => 'Atualmente apenas reCAPTCHA v2 é suportado.',
    'label_recaptcha_enabled' => 'Ativar reCAPTCHA',
    'help_recaptcha_enabled' => 'Ative para proteger formulários contra spam e bots.',
    'label_recaptcha_site_key' => 'Chave do site',
    'placeholder_recaptcha_default' => 'Deixe vazio para chaves de teste padrão',
    'help_recaptcha_site_key' => 'Sua chave do site reCAPTCHA do console do Google reCAPTCHA.',
    'label_recaptcha_secret_key' => 'Chave secreta',
    'help_recaptcha_secret_key' => 'Sua chave secreta reCAPTCHA do console do Google reCAPTCHA.',
    'note_recaptcha_empty' => 'Deixe os campos reCAPTCHA vazios para usar as chaves de teste padrão quando ativadas. (Elas funcionam para testes, mas devem ser substituídas em produção.)',

    // Buttons
    'btn_save_recaptcha' => 'Salvar configurações de reCAPTCHA',
];
?>