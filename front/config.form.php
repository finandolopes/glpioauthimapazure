<?php
/**
 * Tela de configuração do plugin
 */
include_once('../inc/i18n.php');
include ('../inc/config.class.php');

echo '<h2>' . plugin_glpioauthimapazure_translate('PLUGIN_OAUTHIMAPAZURE_MENU_CONFIG') . ' OAuth2</h2>';
PluginGlpioauthimapazureConfig::handleConfigForm();
PluginGlpioauthimapazureConfig::getConfigForm();
