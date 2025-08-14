<?php
// Função utilitária para internacionalização
if (!function_exists('plugin_glpioauthimapazure_translate')) {
    function plugin_glpioauthimapazure_translate($key) {
        static $lang = null;
        if ($lang === null) {
            $selected = 'pt_BR';
            if (isset($_GET['lang'])) {
                $selected = $_GET['lang'];
                setcookie('glpioauthimapazure_lang', $selected, time()+3600*24*30, '/');
            } elseif (isset($_COOKIE['glpioauthimapazure_lang'])) {
                $selected = $_COOKIE['glpioauthimapazure_lang'];
            }
            $file = __DIR__ . '/../locales/' . $selected . '.php';
            if (file_exists($file)) {
                $lang = include $file;
            } else {
                $lang = include __DIR__ . '/../locales/pt_BR.php';
            }
        }
        return $lang[$key] ?? $key;
    }
}
