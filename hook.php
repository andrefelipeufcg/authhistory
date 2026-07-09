<?php
function plugin_authhistory_install() {
    global $DB;

    $migration = new Migration(100);

    if (!$DB->tableExists("glpi_plugin_authhistory_logs")) {
        $query = "CREATE TABLE `glpi_plugin_authhistory_logs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `users_id` int(11) NOT NULL DEFAULT '0',
            `authtype` int(11) NOT NULL DEFAULT '0',
            `ip_address` varchar(255) DEFAULT NULL,
            `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `users_id` (`users_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $stmt = $DB->prepare($query);
        $DB->executeStatement($stmt);
    }

    $migration->executeMigration();
    return true;
}

function plugin_authhistory_uninstall() {
    global $DB;

    $tables = [
        "glpi_plugin_authhistory_logs"
    ];

    foreach ($tables as $table) {
        $DB->dropTable($table);
    }

    return true;
}

function plugin_authhistory_init_session() {
    // Pega o ID do usuário que acabou de logar
    $users_id = Session::getLoginUserID();
    if (!$users_id) {
        return;
    }
    
    // No GLPI, a variável 'glpiauthtype' guarda o método padrão do usuário (geralmente 1 = Local), 
    // mesmo que ele faça login por SSO. Precisamos identificar o fluxo exato:
    $authtype = isset($_SESSION['glpiauthtype']) ? $_SESSION['glpiauthtype'] : 0;
    $uri = $_SERVER['REQUEST_URI'] ?? '';

    // Verifica se a requisição de login atual veio de um callback de SSO conhecido
    if (strpos($uri, 'govbrsso') !== false) {
        $authtype = 99; // ID inventado para Gov.BR
    } elseif (strpos($uri, 'google') !== false || strpos($uri, 'oauth') !== false) {
        $authtype = 98; // ID inventado para Google
    } elseif (!empty($_SESSION['glpiextauth'])) {
        // Se a sessão diz que foi auth externa, mas o ID do banco é Local, forçamos como Externo genérico
        $authtype = Auth::EXTERNAL;
    }

    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    
    // Fallback caso a variável global de tempo do GLPI não esteja preenchida
    $currentTime = isset($_SESSION['glpi_currenttime']) ? $_SESSION['glpi_currenttime'] : date('Y-m-d H:i:s');

    // Instancia nossa classe e insere o registro
    $log = new PluginAuthhistoryLog();
    $log->add([
        'users_id'      => $users_id,
        'authtype'      => $authtype,
        'ip_address'    => $ip_address,
        'date_creation' => $currentTime
    ]);
}
