<?php
/**
 * Endpoint de callback OAuth2 para capturar o código de autorização do Azure
 */

include ('../inc/config.class.php');
include ('../inc/oauth.class.php');
include ('../inc/log.class.php');

if (isset($_GET['code'])) {
    $code = $_GET['code'];
    $tokenData = PluginGlpioauthimapazureOAuth::getAccessToken($code);
    if ($tokenData && isset($tokenData['refresh_token'])) {
        // Criptografa o refresh_token antes de salvar
        $key = hash('sha256', 'chave-secreta-do-plugin');
        $iv = substr($key, 0, 16);
        $encrypted = openssl_encrypt($tokenData['refresh_token'], 'AES-256-CBC', $key, 0, $iv);
        file_put_contents(__DIR__.'/../logs/refresh_token.txt', $encrypted);
        echo '<h2>Autorização concluída!</h2>';
        echo '<p>Refresh token salvo com sucesso.</p>';
    } else {
        echo '<h2>Erro ao obter token!</h2>';
        echo '<pre>' . htmlspecialchars(print_r($tokenData, true)) . '</pre>';
    }
    PluginGlpioauthimapazureLog::addLog('oauth', 'Callback OAuth2 executado.');
} else {
    echo '<h2>Callback inválido</h2>';
}
