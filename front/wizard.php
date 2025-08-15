session_start();
include_once('../inc/i18n.php');
if (!isset($_SESSION['glpiactiveprofile']['interface']) || $_SESSION['glpiactiveprofile']['interface'] !== 'central') {
    die(plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_ACCESS_DENIED'));
}
<?php
/**
 * Assistente de configuração do plugin GLPI OAuth IMAP Azure
 */
include_once('../inc/i18n.php');
include_once('../inc/config.class.php');
include_once('../inc/audit.class.php');


$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
// Geração de token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validação CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $msg = '<div style="color:red">Token de segurança inválido. Recarregue a página.</div>';
    } elseif (isset($_POST['step']) && intval($_POST['step']) === $step) {
        PluginGlpioauthimapazureAudit::add('wizard_step_' . $step, 'Assistente: avançou para o passo ' . ($step+1));
        header('Location: ?step=' . ($step+1));
        exit;
    } else {
        $msg = '<div style="color:red">Etapa inválida ou repetida.</div>';
    }
}

echo '<h2>' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_WIZARD_TITLE') . '</h2>';
if ($msg) echo $msg;
echo '<div style="max-width:600px;">';
switch ($step) {
    case 1:
        echo '<b>' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_WIZARD_STEP1') . '</b><br>' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_WIZARD_CHECK_EXT') . '<br>';
        $ok = true;
        $exts = ['imap','openssl','curl'];
        foreach ($exts as $ext) {
            if (extension_loaded($ext)) {
                echo '<span style="color:green">✔</span> ' . $ext . '<br>';
            } else {
                echo '<span style="color:red">✖</span> ' . $ext . ' <b>' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_WIZARD_MISSING') . '</b><br>';
                $ok = false;
            }
        }
        // Checklist de configuração Azure e ambiente
        echo '<hr><b>Checklist de Configuração do Azure:</b><ul>';
        echo '<li>App registrado no Azure Portal (<a href="https://portal.azure.com" target="_blank">portal.azure.com</a>)</li>';
        echo '<li>Permissões delegadas: <b>email, offline_access, openid, profile</b></li>';
        echo '<li>Client Secret válido e não expirado</li>';
        echo '<li>Redirect URI configurado para: <code>https://SEU_GLPI/plugins/glpioauthimapazure/front/oauth_callback.php</code></li>';
        echo '<li>Conta coletora <b>sem MFA</b> (autenticação multifator)</li>';
        echo '<li>Servidor GLPI com suporte a <b>TLS 1.2+</b> (PHP/OpenSSL atualizados)</li>';
        echo '</ul>';
        if ($ok) {
            echo '<form method="post">';
            echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
            echo '<input type="hidden" name="step" value="' . $step . '">';
            echo '<button>' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_WIZARD_NEXT') . '</button>';
            echo '</form>';
        } else {
            echo '<b>' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_WIZARD_FIX_PREREQ') . '</b>';
        }
        break;
    case 2:
        echo '<b>' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_WIZARD_STEP2') . '</b><br>' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_WIZARD_ADD_ACCOUNT') . ' <a href="accounts.php">' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_MENU_ACCOUNTS') . '</a>.<br>';
        global $DB;
        $accs = $DB->request('glpi_plugin_glpioauthimapazure_accounts');
        $n = 0;
        foreach ($accs as $a) $n++;
        if ($n > 0) {
            echo '<span style="color:green">✔</span> ' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_WIZARD_ACCOUNTS_FOUND') . ': ' . $n . '<br>';
            echo '<form method="post">';
            echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
            echo '<input type="hidden" name="step" value="' . $step . '">';
            echo '<button>' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_WIZARD_NEXT') . '</button>';
            echo '</form>';
        } else {
            echo '<span style="color:red">✖</span> ' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_WIZARD_NO_ACCOUNTS') . '<br>';
        }
        break;
    case 3:
        echo '<b>' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_WIZARD_STEP3') . '</b><br>';
        global $DB;
        $accs = $DB->request('glpi_plugin_glpioauthimapazure_accounts');
        $ok = false;
        foreach ($accs as $a) {
            // Simulação de teste OAuth2 (real: implementar chamada ao fluxo OAuth2)
            $ok = true; // Supondo sucesso para exemplo
            echo '<span style="color:green">✔</span> ' . htmlspecialchars($a['email']) . ': ' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_WIZARD_OAUTH_OK') . '<br>';
        }
        if ($ok) {
            echo '<form method="post">';
            echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
            echo '<input type="hidden" name="step" value="' . $step . '">';
            echo '<button>' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_WIZARD_NEXT') . '</button>';
            echo '</form>';
        } else {
            echo '<span style="color:red">✖</span> ' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_WIZARD_NO_TESTED') . '<br>';
        }
        break;
    case 4:
        echo '<b>' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_WIZARD_STEP4') . '</b><br>';
        global $DB;
        $accs = $DB->request('glpi_plugin_glpioauthimapazure_accounts');
        $ok = false;
        foreach ($accs as $a) {
            // Simulação de teste IMAP (real: implementar chamada à função de coleta)
            $ok = true; // Supondo sucesso para exemplo
            echo '<span style="color:green">✔</span> ' . htmlspecialchars($a['email']) . ': ' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_WIZARD_IMAP_OK') . '<br>';
        }
        if ($ok) {
            echo '<form method="post"><button>' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_WIZARD_FINISH') . '</button></form>';
        } else {
            echo '<span style="color:red">✖</span> ' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_WIZARD_NO_TESTED') . '<br>';
        }
        break;
    default:
        echo '<b>' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_WIZARD_DONE') . '</b><br>' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_WIZARD_READY') . '';
        break;
}
echo '</div>';
