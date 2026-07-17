<?php
define('AUTHHISTORY_VERSION', '1.0.2');
define('AUTHHISTORY_GLPI_MIN', '10.0.0');
//define('AUTHHISTORY_GLPI_MAX', '11.1.0');

function plugin_version_authhistory() {
    return [
        'name'           => 'Histórico de Autenticação',
        'version'        => AUTHHISTORY_VERSION,
        'author'         => 'andrefelipeufcg',
        'license'        => 'GPLv2+',
        'homepage'       => 'https://github.com/andrefelipeufcg/authhistory',
        'requirements'   => [
            'glpi' => [
                'min' => AUTHHISTORY_GLPI_MIN,
                //'max' => AUTHHISTORY_GLPI_MAX
            ]
        ]
    ];
}

function plugin_authhistory_check_prerequisites() {
    if (version_compare(GLPI_VERSION, AUTHHISTORY_GLPI_MIN, '<')) {
        echo 'Este plugin requer GLPI >= ' . AUTHHISTORY_GLPI_MIN;
        return false;
    }
    if (version_compare(PHP_VERSION, '7.4.0', '<')) {
        echo 'Este plugin requer PHP >= 7.4.0';
        return false;
    }
    return true;
}

function plugin_authhistory_check_config() {
    return true;
}

function plugin_init_authhistory() {
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['authhistory'] = true;
    
    // Dispara a nossa função logo após o GLPI autenticar o usuário com sucesso
    $PLUGIN_HOOKS['init_session']['authhistory'] = 'plugin_authhistory_init_session';
    
    // Registra a classe para adicionar uma aba no formulário de Usuário
    Plugin::registerClass('GlpiPlugin\Authhistory\Log', ['addtabon' => 'User']);
}
