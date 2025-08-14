session_start();
if (!isset($_SESSION['glpiactiveprofile']['interface']) || $_SESSION['glpiactiveprofile']['interface'] !== 'central') {
    die('Acesso restrito.');
}
<?php
/**
 * Tela de logs de auditoria do plugin
 */
include_once('../inc/i18n.php');
include_once('../inc/audit.class.php');
$audit = PluginGlpioauthimapazureAudit::get(200);
echo '<h2>Logs de Auditoria</h2>';
echo '<table class="tab_cadre_fixe">';
echo '<tr><th>Data/Hora</th><th>Usuário</th><th>Ação</th><th>Detalhes</th></tr>';
foreach ($audit as $log) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($log['created_at']) . '</td>';
    echo '<td>' . htmlspecialchars($log['user']) . '</td>';
    echo '<td>' . htmlspecialchars($log['action']) . '</td>';
    echo '<td>' . htmlspecialchars($log['details']) . '</td>';
    echo '</tr>';
}
echo '</table>';
