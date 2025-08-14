// Classe de orquestração multi-conta
class PluginGlpioauthimapazureMultiAccount {
    /**
     * Coleta e-mails de todas as contas ativas
     */
    public static function fetchAllAccountsEmails() {
        global $DB;
        $accounts = $DB->request(["FROM" => "glpi_plugin_glpioauthimapazure_accounts", "WHERE" => ["active" => 1]]);
        foreach ($accounts as $acc) {
            // Aqui você deve obter o accessToken via OAuth2 para cada conta (não implementado neste trecho)
            // Exemplo: $accessToken = PluginGlpioauthimapazureOAuth::getAccessTokenForAccount($acc);
            $accessToken = '';
            // Chama a coleta para a conta
            $result = PluginGlpioauthimapazureIMAP::fetchEmails($accessToken, $acc['email']);
            // Loga resultado por conta
            PluginGlpioauthimapazureLog::addLog('imap', '[Conta: ' . $acc['email'] . '] E-mails coletados: ' . (is_array($result) ? count($result) : 0));
        }
    }
}
<?php
/**
 * Classe para conexão IMAP OAuth2
 */

class PluginGlpioauthimapazureIMAP {
    /**
     * Coleta e-mails via IMAP OAuth2
     */
    public static function fetchEmails($accessToken, $mailbox, $folder = 'INBOX') {
        $mailboxString = sprintf('{outlook.office365.com:993/imap/ssl/authuser=%s}'.$folder, $mailbox);
        $imap = imap_open($mailboxString, $mailbox, $accessToken, 0, 1, [
            'DISABLE_AUTHENTICATOR' => 'PLAIN'
        ]);
        if (!$imap) {
            PluginGlpioauthimapazureLog::addLog('imap', 'Erro ao conectar IMAP: ' . imap_last_error());
            return false;
        }
        $emails = imap_search($imap, 'UNSEEN');
        $result = [];
        if ($emails) {
            foreach ($emails as $email_number) {
                $overview = imap_fetch_overview($imap, $email_number, 0);
                $message = imap_fetchbody($imap, $email_number, 1);
                $attachments = self::extractAttachments($imap, $email_number);
                $saved_attachments = [];
                foreach ($attachments as $att) {
                    $filename = uniqid('att_') . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $att['filename']);
                    $path = __DIR__ . '/../logs/attachments/' . $filename;
                    file_put_contents($path, $att['content']);
                    $saved_attachments[] = $filename;
                }
                $result[] = [
                    'overview' => $overview,
                    'body' => $message,
                    'attachments' => $saved_attachments
                ];
                // Marcar como lido
                imap_setflag_full($imap, $email_number, "\\Seen");
            }
        }
        imap_close($imap);
        return $result;
    }

    /**
     * Extrai anexos de um e-mail
     */
    public static function extractAttachments($imap, $email_number) {
        $attachments = [];
        $structure = imap_fetchstructure($imap, $email_number);
        if (isset($structure->parts) && count($structure->parts)) {
            for ($i = 0; $i < count($structure->parts); $i++) {
                $part = $structure->parts[$i];
                if (isset($part->disposition) && strtolower($part->disposition) == 'attachment') {
                    $filename = $part->dparameters[0]->value ?? 'anexo_' . $i;
                    $attachment = imap_fetchbody($imap, $email_number, $i+1);
                    if ($part->encoding == 3) {
                        $attachment = base64_decode($attachment);
                    } elseif ($part->encoding == 4) {
                        $attachment = quoted_printable_decode($attachment);
                    }
                    $attachments[] = [
                        'filename' => $filename,
                        'content' => $attachment
                    ];
                }
            }
        }
        return $attachments;
    }

    /**
     * Envia e-mail via SMTP OAuth2
     */
    public static function sendEmail($accessToken, $from, $to, $subject, $body) {
        // Requer PHPMailer com suporte a XOAUTH2
        require_once __DIR__ . '/../vendor/autoload.php';
        $config = PluginGlpioauthimapazureConfig::getConfig();
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.office365.com';
            $mail->Port = 587;
            $mail->SMTPSecure = 'tls';
            $mail->SMTPAuth = true;
            $mail->AuthType = 'XOAUTH2';
            $mail->setFrom($from);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->oauthUserEmail = $from;
            $mail->oauthClientId = $config['azure_client_id'];
            $mail->oauthClientSecret = $config['azure_client_secret'];
            $mail->oauthRefreshToken = '';
            $mail->oauthAccessToken = $accessToken;
            $mail->send();
            return true;
        } catch (\Exception $e) {
            PluginGlpioauthimapazureLog::addLog('smtp', 'Erro ao enviar e-mail: ' . $mail->ErrorInfo);
            return false;
        }
    }
}
