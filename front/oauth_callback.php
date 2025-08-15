<?php
/**
 * Endpoint de callback OAuth2 para capturar o código de autorização do Azure
 */

include ('../inc/config.class.php');
include ('../inc/oauth.class.php');
include ('../inc/log.class.php');

if (isset($_GET['code']) && isset($_GET['email'])) {
    $code = $_GET['code'];
    $email = $_GET['email'];
    $tokenData = PluginGlpioauthimapazureOAuth::getAccessToken($code);
    if ($tokenData && isset($tokenData['refresh_token'])) {
        // Salva o refresh_token na tabela de contas
        global $DB;
        $stmt = $DB->prepare("UPDATE glpi_plugin_glpioauthimapazure_accounts SET refresh_token=? WHERE email=?");
        $stmt->bind_param('ss', $tokenData['refresh_token'], $email);
        $stmt->execute();
        $stmt->close();
        echo '<h2>Autorização concluída!</h2>';
        echo '<p>Refresh token salvo na conta: ' . htmlspecialchars($email) . '.</p>';
    } else {
        echo '<h2>Erro ao obter token!</h2>';
        echo '<pre>' . htmlspecialchars(print_r($tokenData, true)) . '</pre>';
    }
    PluginGlpioauthimapazureLog::addLog('oauth', 'Callback OAuth2 executado para ' . $email);
} else {
    echo '<h2>Callback inválido</h2>';
}
