session_start();
if (!isset($_SESSION['glpiactiveprofile']['interface']) || $_SESSION['glpiactiveprofile']['interface'] !== 'central') {
    die('Acesso restrito.');
}
<?php
/**
 * Integração com fila: tela para visualizar e gerenciar fila de e-mails coletados/pendentes
 */
include_once('../inc/queue.class.php');
include_once('../inc/config.class.php');
include_once('../inc/audit.class.php');
global $DB;
echo '<h2>Fila de Processamento de E-mails</h2>';
$queue = PluginGlpioauthimapazureQueue::getAll(100);
echo '<table class="tab_cadre_fixe">';
echo '<tr><th>ID</th><th>Conta</th><th>De</th><th>Para</th><th>Assunto</th><th>Status</th><th>Data</th><th>Erro</th></tr>';
foreach ($queue as $item) {
    $acc = $DB->request(["FROM" => "glpi_plugin_glpioauthimapazure_accounts", "WHERE" => ["id" => $item['account_id']]])->current();
    echo '<tr>';
    echo '<td>' . $item['id'] . '</td>';
    echo '<td>' . ($acc ? htmlspecialchars($acc['email']) : $item['account_id']) . '</td>';
    echo '<td>' . htmlspecialchars($item['email_from']) . '</td>';
    echo '<td>' . htmlspecialchars($item['email_to']) . '</td>';
    echo '<td>' . htmlspecialchars($item['subject']) . '</td>';
    echo '<td>' . htmlspecialchars($item['status']) . '</td>';
    echo '<td>' . htmlspecialchars($item['created_at']) . '</td>';
    echo '<td>' . ($item['error'] ? '<span style="color:red">' . htmlspecialchars($item['error']) . '</span>' : '') . '</td>';
    echo '</tr>';
}
echo '</table>';
