<?php
/**
 * Plugin hooks for GLPI OAuth IMAP Azure
 * Checagem de versão do GLPI e requisitos mínimos
 */

function plugin_check_requirements_glpioauthimapazure() {
    global $CFG_GLPI;
    $minGlpiVersion = '9.5.5';
    $minPhpVersion = '7.2.0';
    $opensslOk = defined('OPENSSL_VERSION_TEXT') && preg_match('/OpenSSL\s([\d.]+)/', OPENSSL_VERSION_TEXT, $m) && version_compare($m[1], '1.0.1', '>=');
    $phpOk = version_compare(PHP_VERSION, $minPhpVersion, '>=');
    $glpiVer = '';
    if (defined('GLPI_VERSION')) {
        $glpiVer = constant('GLPI_VERSION');
    } else if (function_exists('getGlpiVersion')) {
        $glpiVer = @getGlpiVersion();
    } else if (isset($CFG_GLPI['version'])) {
        $glpiVer = $CFG_GLPI['version'];
    }
    $glpiOk = ($glpiVer && version_compare($glpiVer, $minGlpiVersion, '>='));
    $glpiIsLegacy = ($glpiVer && version_compare($glpiVer, '10.0.0', '<'));
    if (!$phpOk || !$opensslOk || !$glpiOk) {
        echo '<div style="color:red;font-weight:bold;">';
        echo 'Requisitos mínimos não atendidos para o plugin OAuth IMAP Azure:<br>';
        if (!$phpOk) echo 'PHP >= ' . $minPhpVersion . ' necessário. Versão atual: ' . PHP_VERSION . '<br>';
        if (!$opensslOk) echo 'OpenSSL >= 1.0.1 necessário.<br>';
        if (!$glpiOk) echo 'GLPI >= ' . $minGlpiVersion . ' necessário.';
        echo '</div>';
        return false;
    }
    if ($glpiIsLegacy) {
        echo '<div style="color:orange;font-weight:bold;">';
        echo 'Atenção: Você está instalando o plugin em uma versão do GLPI anterior à 10.x.<br>';
        echo 'Esta operação é opcional e pode apresentar riscos e limitações:<ul>';
        echo '<li>Recursos visuais e de interface podem não funcionar corretamente (ex: tabelas responsivas, ícones, temas escuros).</li>';
        echo '<li>Algumas APIs e métodos de integração podem não estar disponíveis ou apresentar comportamento diferente.</li>';
        echo '<li>Funcionalidades de segurança, CSRF e sessões podem ser menos robustas.</li>';
        echo '<li>O suporte a plugins e hooks pode ser limitado ou exigir adaptações manuais.</li>';
        echo '<li>Testes automatizados e integração contínua não são garantidos para GLPI < 10.</li>';
        echo '</ul>';
        echo 'Se possível, recomenda-se atualizar o GLPI para a versão 10.x ou superior.<br>';
        echo 'Deseja prosseguir mesmo assim? <form method="post" style="display:inline;"><button name="legacy_confirm" value="1">Sim, instalar mesmo assim</button></form>';
        echo '</div>';
        if (!isset($_POST['legacy_confirm']) || $_POST['legacy_confirm'] != '1') {
            return false;
        }
    }
    return true;
}

if (!plugin_check_requirements_glpioauthimapazure()) {
    return;
}

// Hook para coletar e-mails e criar chamados automaticamente
function plugin_cron_glpioauthimapazure() {
    include_once(__DIR__.'/inc/oauth.class.php');
    include_once(__DIR__.'/inc/imap.class.php');
    if (!class_exists('Ticket')) {
        $glpiRoot = defined('GLPI_ROOT') ? GLPI_ROOT : (isset($GLOBALS['GLPI_ROOT']) ? $GLOBALS['GLPI_ROOT'] : (isset($GLOBALS['CFG_GLPI']['root_doc']) ? $GLOBALS['CFG_GLPI']['root_doc'] : null));
        if ($glpiRoot && file_exists($glpiRoot . '/inc/ticket.class.php')) {
            include_once($glpiRoot . '/inc/ticket.class.php');
        }
    }
    global $DB;
    // Paginação e filtro por e-mail
    $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 20;
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $offset = ($page - 1) * $limit;
    $emailFilter = isset($_GET['email']) ? trim($_GET['email']) : '';
    $where = ["active" => 1];
    if ($emailFilter !== '') {
        $where['email'] = $emailFilter;
    }
    $accounts = $DB->request([
        "FROM" => "glpi_plugin_glpioauthimapazure_accounts",
        "WHERE" => $where,
        "ORDERBY" => "email ASC",
        "LIMIT" => $limit,
        "START" => $offset
    ]);
    $total = $DB->request([
        "FROM" => "glpi_plugin_glpioauthimapazure_accounts",
        "WHERE" => $where,
        "COUNT" => true
    ]);
    foreach ($accounts as $acc) {
        if (empty($acc['refresh_token'])) {
            PluginGlpioauthimapazureLog::addLog('imap', plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LOG_TOKEN_NOT_FOUND') . ' ' . $acc['email']);
            continue;
        }
        $tokenData = PluginGlpioauthimapazureOAuth::refreshToken($acc['refresh_token']);
        if (!$tokenData || !isset($tokenData['access_token'])) {
            PluginGlpioauthimapazureLog::addLog('imap', plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LOG_TOKEN_RENEW_FAIL') . ' ' . $acc['email']);
            continue;
        }
        $accessToken = $tokenData['access_token'];
        $emails = PluginGlpioauthimapazureIMAP::fetchEmails($accessToken, $acc['email']);
        if ($emails === false) {
            PluginGlpioauthimapazureLog::addLog('imap', plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LOG_FETCH_FAIL') . ' ' . $acc['email']);
            continue;
        }
        foreach ($emails as $email) {
            // Criação de chamado a partir do e-mail
            $anexos = [];
            if (!empty($email['attachments'])) {
                foreach ($email['attachments'] as $fname) {
                    $anexos[] = '<a href="../front/download_attachment.php?file=' . urlencode($fname) . '" target="_blank">' . htmlspecialchars($fname) . '</a>';
                }
            }
            $logMsg = plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LOG_EMAIL_COLLECTED');
            if ($anexos) {
                $logMsg .= ' ' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LOG_ATTACHMENTS') . ': ' . implode(', ', $anexos);
            }
            if (class_exists('Ticket')) {
                $ticket = new Ticket();
                $input = [
                    'name'        => isset($email['overview'][0]->subject) ? $email['overview'][0]->subject : plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LOG_NEW_TICKET'),
                    'content'     => $email['body'],
                    'status'      => 1, // Novo
                    'requesttypes_id' => 7, // E-mail
                    'users_id_recipient' => 0,
                    'date'        => isset($email['overview'][0]->date) ? date('Y-m-d H:i:s', strtotime($email['overview'][0]->date)) : date('Y-m-d H:i:s'),
                    // Adicione outros campos conforme necessário
                ];
                $newID = $ticket->add($input);
                if ($newID) {
                    $logMsg .= ' ' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LOG_TICKET_CREATED') . ' ' . $newID;
                } else {
                    $logMsg .= ' ' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LOG_TICKET_FAIL');
                }
            } else {
                $logMsg .= ' ' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LOG_TICKET_CLASS_NOT_FOUND');
            }
            PluginGlpioauthimapazureLog::addLog('imap', $logMsg);
        }
    }
    // Exibir paginação e filtro (apenas se chamado via interface/admin)
    if (php_sapi_name() !== 'cli') {
        echo '<form method="get" style="margin-bottom:10px;">';
        echo plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LABEL_EMAIL') . ': <input type="text" name="email" value="' . htmlspecialchars($emailFilter) . '"> ';
        echo plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LABEL_LIMIT') . ': <input type="number" name="limit" value="' . $limit . '" min="1" max="100"> ';
        echo '<input type="submit" value="' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LABEL_FILTER') . '">';
        echo '</form>';
        $totalRows = 0;
        if ($total && $total->numrows()) {
            $row = $total->next();
            $totalRows = isset($row['COUNT(*)']) ? intval($row['COUNT(*)']) : 0;
        }
        $totalPages = $limit > 0 ? ceil($totalRows / $limit) : 1;
        echo '<div style="margin-bottom:10px;">';
        echo plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LABEL_PAGES') . ': ';
        for ($i = 1; $i <= $totalPages; $i++) {
            if ($i == $page) {
                echo '<b>' . $i . '</b> ';
            } else {
                $params = $_GET;
                $params['page'] = $i;
                echo '<a href="?' . http_build_query($params) . '">' . $i . '</a> ';
            }
        }
        echo '</div>';
        if ($totalRows === 0) {
            echo '<div style="color:gray;">' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LABEL_NO_RESULTS') . '</div>';
        }
    }
}
