<?php
/**
 * Plugin hooks for GLPI OAuth IMAP Azure
 */

// Hook para coletar e-mails e criar chamados automaticamente
function plugin_cron_glpioauthimapazure() {
    include_once(__DIR__.'/inc/oauth.class.php');
    include_once(__DIR__.'/inc/imap.class.php');
    if (!class_exists('Ticket')) {
        include_once(GLPI_ROOT . '/inc/ticket.class.php');
    }
    $config = PluginGlpioauthimapazureConfig::getConfig();
    // Recupera o refresh_token salvo pelo callback
    $refreshTokenFile = __DIR__.'/logs/refresh_token.txt';
    if (!file_exists($refreshTokenFile)) {
        PluginGlpioauthimapazureLog::addLog('imap', 'Refresh token não encontrado. Execute o fluxo OAuth2.');
        return;
    }
    // Descriptografa o refresh_token
    $key = hash('sha256', 'chave-secreta-do-plugin');
    $iv = substr($key, 0, 16);
    $encrypted = trim(file_get_contents($refreshTokenFile));
    $refreshToken = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    $tokenData = PluginGlpioauthimapazureOAuth::refreshToken($refreshToken);
    if (!$tokenData || !isset($tokenData['access_token'])) {
        PluginGlpioauthimapazureLog::addLog('imap', 'Falha ao renovar access_token.');
        return;
    }
    $accessToken = $tokenData['access_token'];
    $userEmail = 'usuario@dominio.com'; // Troque pelo e-mail correto
    $emails = PluginGlpioauthimapazureIMAP::fetchEmails($accessToken, $userEmail);
    if ($emails === false) {
        PluginGlpioauthimapazureLog::addLog('imap', 'Erro ao coletar e-mails.');
        return;
    }
    foreach ($emails as $email) {
        // Criação de chamado a partir do e-mail
        $anexos = [];
        if (!empty($email['attachments'])) {
            foreach ($email['attachments'] as $fname) {
                $anexos[] = '<a href="../front/download_attachment.php?file=' . urlencode($fname) . '" target="_blank">' . htmlspecialchars($fname) . '</a>';
            }
        }
        $logMsg = 'E-mail coletado.';
        if ($anexos) {
            $logMsg .= ' Anexos: ' . implode(', ', $anexos);
        }
        if (class_exists('Ticket')) {
            $ticket = new Ticket();
            $input = [
                'name'        => isset($email['overview'][0]->subject) ? $email['overview'][0]->subject : 'Novo chamado via e-mail',
                'content'     => $email['body'],
                'status'      => 1, // Novo
                'requesttypes_id' => 7, // E-mail
                'users_id_recipient' => 0,
                'date'        => isset($email['overview'][0]->date) ? date('Y-m-d H:i:s', strtotime($email['overview'][0]->date)) : date('Y-m-d H:i:s'),
                // Adicione outros campos conforme necessário
            ];
            $newID = $ticket->add($input);
            if ($newID) {
                $logMsg .= ' Chamado criado: ID ' . $newID;
            } else {
                $logMsg .= ' Falha ao criar chamado.';
            }
        } else {
            $logMsg .= ' Classe Ticket não encontrada.';
        }
        PluginGlpioauthimapazureLog::addLog('imap', $logMsg);
    }
}
