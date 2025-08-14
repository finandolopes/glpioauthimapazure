<?php
/**
 * Tela de gestão de contas (multi-conta) para o plugin
 */
include_once('../inc/i18n.php');
include_once('../inc/config.class.php');
include_once('../inc/audit.class.php');
global $DB;

// Adicionar/editar/remover contas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $DB->queryPrepared("INSERT INTO glpi_plugin_glpioauthimapazure_accounts (email, azure_client_id, azure_client_secret, azure_tenant_id, azure_redirect_uri, refresh_token, active) VALUES (?, ?, ?, ?, ?, '', 1)", [
            $_POST['email'], $_POST['client_id'], $_POST['client_secret'], $_POST['tenant_id'], $_POST['redirect_uri']
        ]);
        PluginGlpioauthimapazureAudit::add('add_account', 'Conta adicionada: ' . $_POST['email']);
    } elseif (isset($_POST['delete']) && isset($_POST['id'])) {
        $acc = $DB->request(["FROM" => "glpi_plugin_glpioauthimapazure_accounts", "WHERE" => ["id" => $_POST['id']]])->current();
        $DB->queryPrepared("DELETE FROM glpi_plugin_glpioauthimapazure_accounts WHERE id=?", [$_POST['id']]);
        PluginGlpioauthimapazureAudit::add('delete_account', 'Conta removida: ' . ($acc ? $acc['email'] : $_POST['id']));
    } elseif (isset($_POST['toggle']) && isset($_POST['id'])) {
        $acc = $DB->request(["FROM" => "glpi_plugin_glpioauthimapazure_accounts", "WHERE" => ["id" => $_POST['id']]])->current();
        $DB->queryPrepared("UPDATE glpi_plugin_glpioauthimapazure_accounts SET active = 1-active WHERE id=?", [$_POST['id']]);
        PluginGlpioauthimapazureAudit::add('toggle_account', 'Conta ativada/desativada: ' . ($acc ? $acc['email'] : $_POST['id']));
    }
}

$accounts = $DB->request('glpi_plugin_glpioauthimapazure_accounts');
echo '<h2>' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_ACCOUNTS_TITLE') . '</h2>';
echo '<form method="post" style="margin-bottom:20px;">';
echo '<b>' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_ACCOUNTS_ADD') . '</b><br>';
echo 'E-mail: <input name="email" required> ';
echo 'Client ID: <input name="client_id" required> ';
echo 'Secret: <input name="client_secret" required> ';
echo 'Tenant ID: <input name="tenant_id" required> ';
echo 'Redirect URI: <input name="redirect_uri" required> ';
echo '<button type="submit" name="add">' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_ACCOUNTS_ADD_BTN') . '</button>';
echo '</form>';

echo '<table class="tab_cadre_fixe">';
echo '<tr><th>ID</th><th>E-mail</th><th>Client ID</th><th>Tenant</th><th>Ativa?</th><th>Ações</th></tr>';
foreach ($accounts as $acc) {
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
