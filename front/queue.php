session_start();
if (!isset($_SESSION['glpiactiveprofile']['interface']) || $_SESSION['glpiactiveprofile']['interface'] !== 'central') {
    die('Acesso restrito.');
}

$msg = '';
// Validação do filtro de status
$valid_status = ['','pending','processed','error'];
$filter_status = isset($_GET['filter_status']) ? trim($_GET['filter_status']) : '';
if (!in_array($filter_status, $valid_status, true)) {
    $msg = '<div style="color:red">Status de filtro inválido.</div>';
    $filter_status = '';
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


// Filtro e paginação
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 30;
$offset = ($page - 1) * $perPage;
$where = [];
if ($filter_status) {
    $where[] = "status = '" . $DB->escape($filter_status) . "'";
}
$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
$total = $DB->result($DB->query("SELECT COUNT(*) FROM glpi_plugin_glpioauthimapazure_queue $where_sql"), 0, 0);
$queue = $DB->query("SELECT * FROM glpi_plugin_glpioauthimapazure_queue $where_sql ORDER BY id DESC LIMIT $perPage OFFSET $offset");


if ($msg) echo $msg;
// Filtro
echo '<form method="get" style="margin-bottom:10px;">Filtrar por status: <select name="filter_status"><option value="">Todos</option><option value="pending"' . ($filter_status=='pending'?' selected':'') . '>Pendente</option><option value="processed"' . ($filter_status=='processed'?' selected':'') . '>Processado</option><option value="error"' . ($filter_status=='error'?' selected':'') . '>Erro</option></select> <button type="submit">Filtrar</button></form>';

echo '<table class="tab_cadre_fixe">';
echo '<tr><th>ID</th><th>Conta</th><th>De</th><th>Para</th><th>Assunto</th><th>Status</th><th>Data</th><th>Erro</th></tr>';
while ($item = $queue->fetch_assoc()) {
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
// Paginação
$totalPages = ceil($total / $perPage);
if ($totalPages > 1) {
    echo '<div style="margin-top:10px;">Página: ';
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i == $page) {
            echo " <b>$i</b> ";
        } else {
            $url = '?filter_status=' . urlencode($filter_status) . '&page=' . $i;
            echo " <a href='" . htmlspecialchars($url) . "'>$i</a> ";
        }
    }
    echo '</div>';
}
