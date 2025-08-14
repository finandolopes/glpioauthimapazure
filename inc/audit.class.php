<?php
/**
 * Classe para logs de auditoria do plugin
 */
class PluginGlpioauthimapazureAudit {
    public static function add($action, $details = '') {
        global $DB;
        $user = isset($_SESSION['glpiname']) ? $_SESSION['glpiname'] : 'system';
        $DB->insert('glpi_plugin_glpioauthimapazure_audit', [
            'user' => $user,
            'action' => $action,
            'details' => $details
        ]);
    }
    public static function get($limit = 100) {
        global $DB;
        return $DB->request(["FROM" => "glpi_plugin_glpioauthimapazure_audit", "ORDERBY" => "id DESC", "LIMIT" => $limit]);
    }
}
