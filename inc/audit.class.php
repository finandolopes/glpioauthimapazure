<?php
/**
 * Classe para logs de auditoria do plugin
 */
class PluginGlpioauthimapazureAudit {
    public static function add($action, $details = '') {
        global $DB;
        $user = isset($_SESSION['glpiname']) ? $_SESSION['glpiname'] : 'system';
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'cli';
        $datetime = date('Y-m-d H:i:s');
        $context = 'IP: ' . $ip . ' | Data/Hora: ' . $datetime;
        if ($details) {
            $context .= ' | ' . $details;
        }
        $DB->insert('glpi_plugin_glpioauthimapazure_audit', [
            'user' => $user,
            'action' => $action,
            'details' => $context
        ]);
    }
    public static function get($limit = 100) {
        global $DB;
        return $DB->request(["FROM" => "glpi_plugin_glpioauthimapazure_audit", "ORDERBY" => "id DESC", "LIMIT" => $limit]);
    }
}
