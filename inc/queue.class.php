<?php
/**
 * Classe para gerenciar a fila de e-mails
 */
class PluginGlpioauthimapazureQueue {
    public static function add($account_id, $from, $to, $subject, $body) {
        global $DB;
        $DB->insert('glpi_plugin_glpioauthimapazure_queue', [
            'account_id' => $account_id,
            'email_from' => $from,
            'email_to' => $to,
            'subject' => $subject,
            'body' => $body,
            'status' => 'pending'
        ]);
    }
    public static function getPending($limit = 50) {
        global $DB;
        return $DB->request([
            'FROM' => 'glpi_plugin_glpioauthimapazure_queue',
            'WHERE' => ['status' => 'pending'],
            'ORDERBY' => 'id ASC',
            'LIMIT' => $limit
        ]);
    }
    public static function updateStatus($id, $status, $error = null) {
        global $DB;
        $DB->update('glpi_plugin_glpioauthimapazure_queue', [
            'status' => $status,
            'error' => $error,
            'processed_at' => date('Y-m-d H:i:s')
        ], ['id' => $id]);
    }
    public static function getAll($limit = 100) {
        global $DB;
        return $DB->request([
            'FROM' => 'glpi_plugin_glpioauthimapazure_queue',
            'ORDERBY' => 'id DESC',
            'LIMIT' => $limit
        ]);
    }
}
