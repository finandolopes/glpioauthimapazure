<?php
/**
 * Classe para registro de logs/erros do plugin
 */

class PluginGlpioauthimapazureLog extends CommonDBTM {
    public static function addLog($type, $message) {
        global $DB;
        // Rotaciona logs se exceder 5000 registros
        $count = $DB->result($DB->query("SELECT COUNT(*) FROM glpi_plugin_glpioauthimapazure_logs"), 0, 0);
        if ($count > 5000) {
            $DB->query("DELETE FROM glpi_plugin_glpioauthimapazure_logs ORDER BY created_at ASC LIMIT 500");
        }
        $stmt = $DB->prepare("INSERT INTO glpi_plugin_glpioauthimapazure_logs (log_type, message) VALUES (?, ?)");
        $stmt->bind_param('ss', $type, $message);
        $stmt->execute();
        $stmt->close();
        // Notificação para admins em caso de erro crítico
        if (in_array($type, ['imap', 'smtp', 'oauth']) && stripos($message, 'erro') !== false) {
            self::notifyAdmins($type, $message);
        }
    }

    public static function notifyAdmins($type, $message) {
        global $DB;
        $emails = [];
        $res = $DB->query("SELECT email FROM glpi_users WHERE is_active=1 AND is_admin=1 AND email != ''");
        while ($row = $DB->fetch_assoc($res)) {
            $emails[] = $row['email'];
        }
        if ($emails) {
            $subject = '[GLPI OAuth IMAP Azure] Erro crítico: ' . $type;
            $body = "Ocorreu um erro crítico no plugin OAuth IMAP Azure:\n\n" . $message;
            foreach ($emails as $to) {
                @mail($to, $subject, $body);
            }
        }
    }

    public static function getLogs($type = '', $limit = 50, $offset = 0, $search = '', $date = '') {
        global $DB;
        $logs = [];
        $where = [];
        if ($type) {
            $where[] = "log_type = '" . $DB->escape($type) . "'";
        }
        if ($search) {
            $where[] = "message LIKE '%" . $DB->escape($search) . "%'";
        }
        if ($date) {
            $where[] = "DATE(created_at) = '" . $DB->escape($date) . "'";
        }
        $query = "SELECT * FROM glpi_plugin_glpioauthimapazure_logs";
        if ($where) {
            $query .= " WHERE " . implode(' AND ', $where);
        }
        $query .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
        $result = $DB->query($query);
        if ($result) {
            while ($row = $DB->fetch_assoc($result)) {
                $logs[] = $row;
            }
        }
        return $logs;
    }

    public static function countLogs($type = '', $search = '', $date = '') {
        global $DB;
        $where = [];
        if ($type) {
            $where[] = "log_type = '" . $DB->escape($type) . "'";
        }
        if ($search) {
            $where[] = "message LIKE '%" . $DB->escape($search) . "%'";
        }
        if ($date) {
            $where[] = "DATE(created_at) = '" . $DB->escape($date) . "'";
        }
        $query = "SELECT COUNT(*) AS total FROM glpi_plugin_glpioauthimapazure_logs";
        if ($where) {
            $query .= " WHERE " . implode(' AND ', $where);
        }
        $result = $DB->query($query);
        if ($result && $row = $DB->fetch_assoc($result)) {
            return intval($row['total']);
        }
        return 0;
    }
}
