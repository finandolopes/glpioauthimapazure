<?php
include_once('../inc/i18n.php');
include_once('../inc/log.class.php');

// Estatísticas rápidas
$totalEmails = PluginGlpioauthimapazureLog::countLogs('imap');
$totalTickets = PluginGlpioauthimapazureLog::countLogs('imap'); // Ajuste para contar chamados reais se desejar
$recentErrors = PluginGlpioauthimapazureLog::getLogs('', 5, 0);

// Dashboard

echo '<h2>' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_DASHBOARD_TITLE') . '</h2>';
echo '<ul>';
echo '<li>' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_DASHBOARD_EMAILS') . ': <b>' . $totalEmails . '</b></li>';
echo '<li>' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_DASHBOARD_TICKETS') . ': <b>' . $totalTickets . '</b></li>';
echo '</ul>';

echo '<h3>' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_DASHBOARD_ERRORS') . '</h3>';
echo '<table class="tab_cadre_fixe">';
echo '<tr><th>' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LOGS_DATE') . '</th><th>' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LOGS_TYPE') . '</th><th>' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_LOGS_MESSAGE') . '</th></tr>';
foreach ($recentErrors as $log) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($log['created_at']) . '</td>';
    echo '<td>' . htmlspecialchars($log['log_type']) . '</td>';
    echo '<td>' . nl2br(htmlspecialchars($log['message'])) . '</td>';
    echo '</tr>';
}
echo '</table>';
