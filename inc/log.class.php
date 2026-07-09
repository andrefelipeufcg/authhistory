<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginAuthhistoryLog extends CommonDBTM {
    
    // Garante que só quem tem direito de ler usuários pode ver a aba
    static $rightname = 'user';

    static function getTypeName($nb = 0) {
        return 'Histórico de Acesso';
    }

    static function getIcon() {
        return 'fas fa-history';
    }
    
    // Define que a aba aparecerá no formulário de Usuários
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if ($item->getType() == 'User' && Session::haveRight('user', READ)) {
            return self::createTabEntry(self::getTypeName(), 0, __CLASS__, self::getIcon());
        }
        return '';
    }
    
    // Renderiza o conteúdo da aba
    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        global $DB;
        $users_id = $item->getID();

        echo "<div class='spaced'>";
        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr><th>Data e Hora</th><th>IP</th><th>Método de Autenticação (Tipo)</th></tr>";

        $iterator = $DB->request([
            'FROM'  => 'glpi_plugin_authhistory_logs',
            'WHERE' => ['users_id' => $users_id],
            'ORDER' => 'date_creation DESC',
            'LIMIT' => 100 // Exibe os últimos 100 acessos
        ]);

        if (count($iterator)) {
            foreach ($iterator as $data) {
                $auth_name = self::getAuthName($data['authtype']); 
                
                echo "<tr class='tab_bg_1'>";
                echo "<td>" . Html::convDateTime($data['date_creation']) . "</td>";
                echo "<td>" . $data['ip_address'] . "</td>";
                // Mostra o nome amigável e também o ID para facilitar debug se for um plugin externo de SSO customizado
                echo "<td>" . $auth_name . " (ID: " . $data['authtype'] . ")</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr class='tab_bg_1'><td colspan='3' class='center'>Nenhum registro de acesso encontrado.</td></tr>";
        }
        
        echo "</table>";
        echo "</div>";
    }

    // Traduz o ID do tipo de autenticação para um nome legível
    static function getAuthName($authtype) {
        switch ($authtype) {
            case 99:
                return "Gov.BR (SSO)";
            case 98:
                return "Google Workspace (SSO)";
            case Auth::DB_GLPI: 
                return "Banco de Dados Local";
            case Auth::LDAP: 
                return "Diretório LDAP";
            case Auth::EXTERNAL: 
                return "Autenticação Externa (SSO Padrão)";
            case Auth::MAIL:
                return "Servidor de Email (IMAP/POP)";
            case Auth::CAS:
                return "CAS";
            case Auth::X509:
                return "Certificado X509";
            case 0:
                return "Desconhecido/Sessão Restaurada";
            default: 
                return "Outro Método Customizado";
        }
    }
}
