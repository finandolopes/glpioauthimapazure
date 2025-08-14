<?php
/**
 * Menu do plugin
 */
include_once('../inc/i18n.php');
echo '<div style="margin-bottom:20px;">';
echo '<a href="../front/dashboard.php" style="margin-right:20px;">Dashboard</a>';
echo '<a href="../front/config.form.php" style="margin-right:20px;">' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_MENU_CONFIG') . '</a>';
echo '<a href="../front/accounts.php" style="margin-right:20px;">' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_MENU_ACCOUNTS') . '</a>';
echo '<a href="../front/wizard.php" style="margin-right:20px;">Assistente</a>';
echo '<a href="../front/queue.php" style="margin-right:20px;">Fila</a>';
echo '<a href="../front/logs.php" style="margin-right:20px;">' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_MENU_LOGS') . '</a>';
echo '<a href="../front/audit.php">Logs de Auditoria</a>';
// Seletor de idioma
echo '<form method="get" style="display:inline;margin-left:20px;">';
echo plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_SELECT_LANGUAGE') . ' ';
echo '<select name="lang" onchange="this.form.submit()">';
foreach (["pt_BR"=>"Português","en_US"=>"English"] as $k=>$v) {
    $sel = (isset($_COOKIE['glpioauthimapazure_lang']) && $_COOKIE['glpioauthimapazure_lang']==$k)?'selected':'';
    echo "<option value='$k' $sel>$v</option>";
}
echo '</select>';
echo '</form>';
echo '</div>';
