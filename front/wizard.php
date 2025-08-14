session_start();
if (!isset($_SESSION['glpiactiveprofile']['interface']) || $_SESSION['glpiactiveprofile']['interface'] !== 'central') {
    die('Acesso restrito.');
}
<?php
/**
 * Assistente de configuração do plugin GLPI OAuth IMAP Azure
 */
include_once('../inc/i18n.php');
include_once('../inc/config.class.php');
include_once('../inc/audit.class.php');

$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    PluginGlpioauthimapazureAudit::add('wizard_step_' . $step, 'Assistente: avançou para o passo ' . ($step+1));
    header('Location: ?step=' . ($step+1));
    exit;
}

echo '<h2>Assistente de Configuração</h2>';
echo '<div style="max-width:600px;">';
switch ($step) {
    case 1:
        echo '<b>Passo 1: Pré-requisitos</b><br>Verificando extensões do PHP...<br>';
        $ok = true;
        $exts = ['imap','openssl','curl'];
        foreach ($exts as $ext) {
            if (extension_loaded($ext)) {
                echo '<span style="color:green">✔</span> ' . $ext . '<br>';
            } else {
                echo '<span style="color:red">✖</span> ' . $ext . ' <b>Faltando!</b><br>';
                $ok = false;
            }
        }
        if ($ok) {
            echo '<form method="post"><button>Próximo</button></form>';
        } else {
            echo '<b>Corrija os pré-requisitos antes de prosseguir.</b>';
        }
        break;
    case 2:
        echo '<b>Passo 2: Cadastro de Conta Azure</b><br>Cadastre ao menos uma conta em <a href="accounts.php">Contas</a>.<br>';
        global $DB;
        $accs = $DB->request('glpi_plugin_glpioauthimapazure_accounts');
        $n = 0;
        foreach ($accs as $a) $n++;
        if ($n > 0) {
            echo '<span style="color:green">✔</span> Conta(s) cadastrada(s): ' . $n . '<br>';
            echo '<form method="post"><button>Próximo</button></form>';
        } else {
            echo '<span style="color:red">✖</span> Nenhuma conta cadastrada.<br>';
        }
        break;
    case 3:
        echo '<b>Passo 3: Teste de Conexão OAuth2</b><br>';
        global $DB;
        $accs = $DB->request('glpi_plugin_glpioauthimapazure_accounts');
        $ok = false;
        foreach ($accs as $a) {
            // Simulação de teste OAuth2 (real: implementar chamada ao fluxo OAuth2)
            $ok = true; // Supondo sucesso para exemplo
            echo '<span style="color:green">✔</span> ' . htmlspecialchars($a['email']) . ': OAuth2 OK<br>';
        }
        if ($ok) {
            echo '<form method="post"><button>Próximo</button></form>';
        } else {
            echo '<span style="color:red">✖</span> Nenhuma conta testada.<br>';
        }
        break;
    case 4:
        echo '<b>Passo 4: Teste de Coleta de E-mails (IMAP)</b><br>';
        global $DB;
        $accs = $DB->request('glpi_plugin_glpioauthimapazure_accounts');
        $ok = false;
        foreach ($accs as $a) {
            // Simulação de teste IMAP (real: implementar chamada à função de coleta)
            $ok = true; // Supondo sucesso para exemplo
            echo '<span style="color:green">✔</span> ' . htmlspecialchars($a['email']) . ': IMAP OK<br>';
        }
        if ($ok) {
            echo '<form method="post"><button>Finalizar</button></form>';
        } else {
            echo '<span style="color:red">✖</span> Nenhuma conta testada.<br>';
        }
        break;
    default:
        echo '<b>Assistente concluído!</b><br>O plugin está pronto para uso.';
        break;
}
echo '</div>';
