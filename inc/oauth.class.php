<?php
/**
 * Classe para autenticação OAuth2 com Azure
 */

class PluginGlpioauthimapazureOAuth {
    /**
     * Obtém o token de acesso do Azure usando o código de autorização
     */
    public static function getAccessToken($code) {
        $config = PluginGlpioauthimapazureConfig::getConfig();
        $url = 'https://login.microsoftonline.com/' . $config['azure_tenant_id'] . '/oauth2/v2.0/token';
        $data = [
            'client_id' => $config['azure_client_id'],
            'scope' => 'https://outlook.office365.com/.default offline_access',
            'code' => $code,
            'redirect_uri' => $config['azure_redirect_uri'],
            'grant_type' => 'authorization_code',
            'client_secret' => $config['azure_client_secret']
        ];
        return self::requestToken($url, $data);
    }

    /**
     * Renova o token de acesso do Azure usando o refresh token
     */
    public static function refreshToken($refreshToken) {
        // Cache de access_token em arquivo
        $cacheFile = __DIR__ . '/../logs/access_token_cache.json';
        if (file_exists($cacheFile)) {
            $cache = json_decode(file_get_contents($cacheFile), true);
            if ($cache && isset($cache['access_token'], $cache['expires_at']) && $cache['expires_at'] > time() + 60) {
                return $cache;
            }
        }
        $config = PluginGlpioauthimapazureConfig::getConfig();
        $url = 'https://login.microsoftonline.com/' . $config['azure_tenant_id'] . '/oauth2/v2.0/token';
        $data = [
            'client_id' => $config['azure_client_id'],
            'scope' => 'https://outlook.office365.com/.default offline_access',
            'refresh_token' => $refreshToken,
            'redirect_uri' => $config['azure_redirect_uri'],
            'grant_type' => 'refresh_token',
            'client_secret' => $config['azure_client_secret']
        ];
        $result = self::requestToken($url, $data);
        if ($result && isset($result['access_token'], $result['expires_in'])) {
            $result['expires_at'] = time() + $result['expires_in'];
            file_put_contents($cacheFile, json_encode($result));
        }
        return $result;
    }

    /**
     * Faz a requisição ao endpoint de token do Azure
     */
    private static function requestToken($url, $data) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            PluginGlpioauthimapazureLog::addLog('oauth', 'Erro cURL: ' . $error);
            return false;
        }
        $result = json_decode($response, true);
        if (isset($result['error'])) {
            PluginGlpioauthimapazureLog::addLog('oauth', 'Erro OAuth: ' . $result['error_description']);
            return false;
        }
        return $result;
    }
}
