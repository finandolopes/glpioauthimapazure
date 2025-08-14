    $query5 = "CREATE TABLE IF NOT EXISTS glpi_plugin_glpioauthimapazure_queue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        account_id INT,
        email_from VARCHAR(255),
        email_to VARCHAR(255),
        subject VARCHAR(255),
        body TEXT,
        status VARCHAR(50) DEFAULT 'pending',
        error TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        processed_at TIMESTAMP NULL DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $DB->query($query5);
<?php
/**
 * Plugin setup file for GLPI OAuth IMAP Azure
 */

function plugin_init_glpioauthimapazure() {
    global $PLUGIN_HOOKS;
    $PLUGIN_HOOKS['csrf_compliant']['glpioauthimapazure'] = true;
}

function plugin_version_glpioauthimapazure() {
    return [
        'name'           => 'OAuth IMAP Azure',
        'version'        => '1.0.0',
        'author'         => 'Seu Nome',
        'license'        => 'GPLv2+',
        'homepage'       => 'https://seusite.com',
        'minGlpiVersion' => '10.0.0'
    ];
}



function plugin_glpiinstall_glpioauthimapazure() {
    global $DB;
    $query1 = "CREATE TABLE IF NOT EXISTS glpi_plugin_glpioauthimapazure_configs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        azure_client_id VARCHAR(255),
        azure_client_secret VARCHAR(255),
        azure_tenant_id VARCHAR(255),
        azure_redirect_uri VARCHAR(255)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $DB->query($query1);

    $query2 = "CREATE TABLE IF NOT EXISTS glpi_plugin_glpioauthimapazure_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        log_type VARCHAR(50),
        message TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $DB->query($query2);

    $query3 = "CREATE TABLE IF NOT EXISTS glpi_plugin_glpioauthimapazure_accounts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        azure_client_id VARCHAR(255) NOT NULL,
        azure_client_secret VARCHAR(255) NOT NULL,
        azure_tenant_id VARCHAR(255) NOT NULL,
        azure_redirect_uri VARCHAR(255) NOT NULL,
        refresh_token TEXT,
        active TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $DB->query($query3);
    $query4 = "CREATE TABLE IF NOT EXISTS glpi_plugin_glpioauthimapazure_audit (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user VARCHAR(255),
        action VARCHAR(255),
        details TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $DB->query($query4);
    return true;
}



function plugin_glpiuninstall_glpioauthimapazure() {
    global $DB;
    $DB->query("DROP TABLE IF EXISTS glpi_plugin_glpioauthimapazure_configs");
    $DB->query("DROP TABLE IF EXISTS glpi_plugin_glpioauthimapazure_logs");
    return true;
}
