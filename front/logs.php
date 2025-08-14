<?php
// --- Segurança: só admins GLPI central ---
session_start();
if (!isset($_SESSION['glpiactiveprofile']['interface']) || $_SESSION['glpiactiveprofile']['interface'] !== 'central') {
    die(plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_ACCESS_DENIED'));
}
/**
 * Tela de exibição de logs/erros do plugin
 * @author github copilot
 * Pontos críticos: exportação, escapes, links de anexo, paginação
 */

include_once('../inc/i18n.php');
include_once('../inc/config.class.php');
include ('../inc/log.class.php');

$filter = isset($_GET['type']) ? $_GET['type'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date = isset($_GET['date']) ? trim($_GET['date']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;
// Log de auditoria ao filtrar
if ($filter || $search || $date) {
    include_once('../inc/audit.class.php');
    $det = [];
    if ($filter) $det[] = plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LOGS_TYPE') . '=' . htmlspecialchars($filter);
    if ($search) $det[] = plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LOGS_MESSAGE') . '=' . htmlspecialchars($search);
    if ($date) $det[] = plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LOGS_DATE') . '=' . htmlspecialchars($date);
    PluginGlpioauthimapazureAudit::add('filter_logs', 'Filtro aplicado: ' . implode(', ', $det));
}
$logs = PluginGlpioauthimapazureLog::getLogs($filter, $perPage, $offset, $search, $date);
$total = PluginGlpioauthimapazureLog::countLogs($filter, $search, $date);

// Mensagem de feedback
if (isset($_GET['msg'])) {
    echo '<div style="color:green;">' . htmlspecialchars($_GET['msg']) . '</div>';
}

echo '<h2>' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LOGS_TITLE') . '</h2>';
echo '<form method="get" action="" style="margin-bottom:10px;">';
echo plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LOGS_FILTER') . ' ';
echo '<select name="type">';
echo '<option value="">' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LOGS_ALL') . '</option>';
foreach (["oauth"=>plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LOGS_TYPE_OAUTH'), "imap"=>plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LOGS_TYPE_IMAP'), "smtp"=>plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LOGS_TYPE_SMTP')] as $k=>$v) {
    $sel = ($filter==$k)?'selected':'';
    echo "<option value='" . htmlspecialchars($k) . "' $sel>" . htmlspecialchars($v) . "</option>";
}
echo '</select> ';
echo plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LOGS_DATE') . ': <input type="date" name="date" value="' . htmlspecialchars($date) . '"> ';
echo plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LOGS_MESSAGE') . ': <input type="text" name="search" value="' . htmlspecialchars($search) . '" size="20"> ';
echo '<input type="submit" value="' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LOGS_FILTER_BTN') . '"> ';
echo '<button type="submit" name="export" value="csv">' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LOGS_EXPORT_BTN') . '</button>';
echo '</form>';

// Exportação CSV segura
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    include_once('../inc/audit.class.php');
    PluginGlpioauthimapazureAudit::add('export_logs', 'Exportação de logs CSV');
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="logs.csv"');
    echo "Data,Tipo,Mensagem\n";
    foreach ($logs as $log) {
        echo '"' . str_replace('"', '""', $log['created_at']) . '","' . str_replace('"', '""', $log['log_type']) . '","' . str_replace('"', '""', $log['message']) . '"' . "\n";
    }
    exit;
}

echo '<table class="tab_cadre_fixe">';
echo '<tr><th>' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LOGS_DATE') . '</th><th>' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LOGS_TYPE') . '</th><th>' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LOGS_MESSAGE') . '</th></tr>';
foreach ($logs as $log) {
    $msg = $log['message'];
    // Detecta e converte links de anexo (UX: destaque visual)
    if (preg_match_all('/<a href=\?(\["\"])..\/front\/download_attachment.php\?file=([^"\"]+)\1[^>]*>([^<]+)<\/a>/', $msg, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $file = $m[2];
            $label = $m[3];
            $link = '<a href="../front/download_attachment.php?file=' . urlencode($file) . '" target="_blank" style="color:blue;font-weight:bold;">' . htmlspecialchars($label) . '</a>';
            $msg = str_replace($m[0], $link, $msg);
        }
    }
    $rowStyle = (strpos($msg, 'download_attachment.php?file=') !== false) ? ' style="background:#eef;"' : '';
    echo '<tr' . $rowStyle . '>';
    echo '<td>' . htmlspecialchars($log['created_at']) . '</td>';
    echo '<td>' . htmlspecialchars($log['log_type']) . '</td>';
    echo '<td>' . nl2br($msg) . '</td>';
    echo '</tr>';
}
echo '</table>';

// Paginação
$totalPages = ceil($total / $perPage);
if ($totalPages > 1) {
    echo '<div style="margin-top:10px;">' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LOGS_PAGE') . ' ';
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i == $page) {
            echo " <b>$i</b> ";
        } else {
            $url = '?type=' . urlencode($filter) . '&page=' . $i;
            echo " <a href='" . htmlspecialchars($url) . "'>$i</a> ";
        }
    }
    echo '</div>';
}
