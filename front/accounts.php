<?php
/**
 * Tela de gestão de contas (multi-conta) para o plugin
 */
include_once('../inc/i18n.php');
include_once('../inc/config.class.php');
include_once('../inc/audit.class.php');
global $DB;


// Adicionar/editar/remover contas com feedback visual
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    // CSRF token check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $msg = '<div style="color:red">Token de segurança inválido. Recarregue a página.</div>';
    } elseif (isset($_POST['add'])) {
        // Validação dos campos obrigatórios
        $required = ['email','client_id','client_secret','tenant_id','redirect_uri'];
        $missing = [];
        foreach ($required as $f) if (empty($_POST[$f])) $missing[] = $f;
        if ($missing) {
            $msg = '<div style="color:red">Preencha todos os campos obrigatórios: ' . implode(', ', $missing) . '</div>';
        } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $msg = '<div style="color:red">E-mail inválido.</div>';
        } else {
            $ok = $DB->queryPrepared("INSERT INTO glpi_plugin_glpioauthimapazure_accounts (email, azure_client_id, azure_client_secret, azure_tenant_id, azure_redirect_uri, refresh_token, active) VALUES (?, ?, ?, ?, ?, '', 1)", [
                $_POST['email'], $_POST['client_id'], $_POST['client_secret'], $_POST['tenant_id'], $_POST['redirect_uri']
            ]);
            if ($ok) {
                $msg = '<div style="color:green">Conta adicionada com sucesso!</div>';
            } else {
                $msg = '<div style="color:red">Erro ao adicionar conta.</div>';
            }
            PluginGlpioauthimapazureAudit::add('add_account', 'Conta adicionada: ' . $_POST['email']);
        }
    } elseif (isset($_POST['delete']) && isset($_POST['id'])) {
        $acc = $DB->request(["FROM" => "glpi_plugin_glpioauthimapazure_accounts", "WHERE" => ["id" => $_POST['id']]])->current();
        $ok = $DB->queryPrepared("DELETE FROM glpi_plugin_glpioauthimapazure_accounts WHERE id=?", [$_POST['id']]);
        if ($ok) {
            $msg = '<div style="color:green">Conta removida com sucesso!</div>';
        } else {
            $msg = '<div style="color:red">Erro ao remover conta.</div>';
        }
        PluginGlpioauthimapazureAudit::add('delete_account', 'Conta removida: ' . ($acc ? $acc['email'] : $_POST['id']));
    } elseif (isset($_POST['toggle']) && isset($_POST['id'])) {
        $acc = $DB->request(["FROM" => "glpi_plugin_glpioauthimapazure_accounts", "WHERE" => ["id" => $_POST['id']]])->current();
        $ok = $DB->queryPrepared("UPDATE glpi_plugin_glpioauthimapazure_accounts SET active = 1-active WHERE id=?", [$_POST['id']]);
        if ($ok) {
            $msg = '<div style="color:green">Status da conta alterado!</div>';
        } else {
            $msg = '<div style="color:red">Erro ao alterar status da conta.</div>';
        }
        PluginGlpioauthimapazureAudit::add('toggle_account', 'Conta ativada/desativada: ' . ($acc ? $acc['email'] : $_POST['id']));
    }
}



// Filtro e paginação
$filter_email = isset($_GET['filter_email']) ? trim($_GET['filter_email']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;
$where = [];
if ($filter_email) {
    $where[] = "email LIKE '%" . $DB->escape($filter_email) . "%'";
}
$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
$total = $DB->result($DB->query("SELECT COUNT(*) FROM glpi_plugin_glpioauthimapazure_accounts $where_sql"), 0, 0);
$accounts = $DB->query("SELECT * FROM glpi_plugin_glpioauthimapazure_accounts $where_sql ORDER BY id DESC LIMIT $perPage OFFSET $offset");

echo '<h2>' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_ACCOUNTS_TITLE') . '</h2>';
if ($msg) echo $msg;
// Filtro
echo '<form method="get" style="margin-bottom:10px;">Filtrar por e-mail: <input name="filter_email" value="' . htmlspecialchars($filter_email) . '"> <button type="submit">Filtrar</button></form>';
// Geração de token CSRF
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
// Formulário de adição
echo '<form method="post" style="margin-bottom:20px;">';
echo '<b>' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_ACCOUNTS_ADD') . '</b><br>';
echo 'E-mail: <input name="email" required> ';
echo 'Client ID: <input name="client_id" required> ';
echo 'Secret: <input name="client_secret" required> ';
echo 'Tenant ID: <input name="tenant_id" required> ';
echo 'Redirect URI: <input name="redirect_uri" required> ';
echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
echo '<button type="submit" name="add">' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_ACCOUNTS_ADD_BTN') . '</button>';
echo '</form>';

echo '<table class="tab_cadre_fixe">';
echo '<tr><th>ID</th><th>E-mail</th><th>Client ID</th><th>Tenant</th><th>Ativa?</th><th>Ações</th></tr>';
while ($acc = $accounts->fetch_assoc()) {
    echo '<tr>';
    echo '<td>' . $acc['id'] . '</td>';
    echo '<td>' . htmlspecialchars($acc['email']) . '</td>';
    echo '<td>' . htmlspecialchars($acc['azure_client_id']) . '</td>';
    echo '<td>' . htmlspecialchars($acc['azure_tenant_id']) . '</td>';
    echo '<td>' . ($acc['active'] ? '<span style="color:green">Sim</span>' : '<span style="color:red">Não</span>') . '</td>';
    echo '<td>';
    echo '<form method="post" style="display:inline;"><input type="hidden" name="id" value="' . $acc['id'] . '"><button name="toggle">' . ($acc['active'] ? 'Desativar' : 'Ativar') . '</button></form> ';
    echo '<form method="post" style="display:inline;" onsubmit="return confirm(\'Remover?\');"><input type="hidden" name="id" value="' . $acc['id'] . '"><button name="delete">Remover</button></form>';
    echo '</td>';
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
            $url = '?filter_email=' . urlencode($filter_email) . '&page=' . $i;
            echo " <a href='" . htmlspecialchars($url) . "'>$i</a> ";
        }
    }
    echo '</div>';
}
