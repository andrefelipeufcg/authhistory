<?php
define('AUTHHISTORY_VERSION', '1.0.0');

function plugin_version_authhistory() {
    return [
        'name'           => 'Histórico de Autenticação',
        'version'        => AUTHHISTORY_VERSION,
        'author'         => 'andrefelipeufcg',
        'license'        => 'GPLv2+',
        'homepage'       => 'https://github.com/andrefelipeufcg/authhistory',
        'requirements'   => [
            'glpi' => [
                'min' => '10.0.0',
                'max' => '11.1.0' // Compatível com as versões recentes do GLPI 10 e 11
            ]
        ]
    ];
}

function plugin_authhistory_check_prerequisites() {
    if (version_compare(GLPI_VERSION, '10.0.0', '<')) {
        echo 'Este plugin requer GLPI >= 10.0.0';
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
    Plugin::registerClass('PluginAuthhistoryLog', ['addtabon' => 'User']);
}
