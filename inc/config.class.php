// ...existing code...
<?php
/**
 * Classe de configuração do plugin
 */

class PluginGlpioauthimapazureConfig extends CommonDBTM {
    static $rightname = 'config';

    public static function getConfigForm() {
        global $CFG_GLPI;
        $config = self::getConfig();
        echo '<form method="post" action="">';
        echo '<table class="tab_cadre_fixe">';
        echo '<tr><th colspan="2">Configuração Azure OAuth2</th></tr>';
        echo '<tr><td>Client ID:</td><td><input type="text" name="azure_client_id" value="'.htmlspecialchars($config['azure_client_id']).'" size="50"></td></tr>';
        echo '<tr><td>Client Secret:</td><td><input type="password" name="azure_client_secret" value="'.htmlspecialchars($config['azure_client_secret']).'" size="50"></td></tr>';
        echo '<tr><td>Tenant ID:</td><td><input type="text" name="azure_tenant_id" value="'.htmlspecialchars($config['azure_tenant_id']).'" size="50"></td></tr>';
        echo '<tr><td>Redirect URI:</td><td><input type="text" name="azure_redirect_uri" value="'.htmlspecialchars($config['azure_redirect_uri']).'" size="50"></td></tr>';
        echo '<tr><td colspan="2" class="center"><input type="submit" name="save_azure_config" value="Salvar"></td></tr>';
        echo '</table>';
        echo '</form>';
    }

    public static function handleConfigForm() {
        if (isset($_POST['save_azure_config'])) {
            $config = [
                'azure_client_id' => $_POST['azure_client_id'],
                'azure_client_secret' => $_POST['azure_client_secret'],
                'azure_tenant_id' => $_POST['azure_tenant_id'],
                'azure_redirect_uri' => $_POST['azure_redirect_uri']
            ];
            self::saveConfig($config);
            echo '<div class="center">Configuração salva com sucesso!</div>';
        }
    }

    public static function getConfig() {
        global $DB;
        $query = "SELECT * FROM glpi_plugin_glpioauthimapazure_configs LIMIT 1";
        $result = $DB->query($query);
        if ($row = $DB->fetch_assoc($result)) {
            return [
                'azure_client_id' => $row['azure_client_id'],
                'azure_client_secret' => $row['azure_client_secret'],
                'azure_tenant_id' => $row['azure_tenant_id'],
                'azure_redirect_uri' => $row['azure_redirect_uri']
            ];
        }
        return [
            'azure_client_id' => '',
            'azure_client_secret' => '',
            'azure_tenant_id' => '',
            'azure_redirect_uri' => ''
        ];
    }

    public static function saveConfig($config) {
        global $DB;
        $DB->query("DELETE FROM glpi_plugin_glpioauthimapazure_configs");
        $stmt = $DB->prepare(
            "INSERT INTO glpi_plugin_glpioauthimapazure_configs (azure_client_id, azure_client_secret, azure_tenant_id, azure_redirect_uri) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param('ssss', $config['azure_client_id'], $config['azure_client_secret'], $config['azure_tenant_id'], $config['azure_redirect_uri']);
        $stmt->execute();
        $stmt->close();
    }
}
