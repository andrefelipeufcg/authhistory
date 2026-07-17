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
        // Verifica se é o perfil Super-Admin (ID 4 padrão ou pelo nome)
        $is_superadmin = (isset($_SESSION['glpiactiveprofile']['id']) && $_SESSION['glpiactiveprofile']['id'] == 4) 
                      || (isset($_SESSION['glpiactiveprofile']['name']) && $_SESSION['glpiactiveprofile']['name'] === 'Super-Admin');

        if ($item->getType() == 'User' && $is_superadmin) {
            return self::createTabEntry(self::getTypeName(), 0, __CLASS__, self::getIcon());
        }
        return '';
    }
    
    /**
     * Renderiza o conteúdo da aba "Histórico de Acesso".
     * Lê diretamente da tabela nativa glpi_events (service='login').
     */
    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        $is_superadmin = (isset($_SESSION['glpiactiveprofile']['id']) && $_SESSION['glpiactiveprofile']['id'] == 4) 
                      || (isset($_SESSION['glpiactiveprofile']['name']) && $_SESSION['glpiactiveprofile']['name'] === 'Super-Admin');

        if (!$is_superadmin) {
            return false;
        }

        global $DB;
        $users_id = $item->getID();

        // Obtém o username para buscar eventos antigos (antes do plugin setar items_id).
        $username = '';
        $user = new User();
        if ($user->getFromDB($users_id)) {
            $username = $user->fields['name'];
        }

        echo "<div class='spaced'>";
        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr><th>Data e Hora</th><th>IP</th><th>Método de Autenticação (Tipo)</th></tr>";

        // Busca eventos de login deste usuário:
        // - items_id = users_id (eventos enriquecidos pelo plugin)
        // - OU message contém o username (eventos anteriores à instalação do plugin)
        $where = [
            'service' => 'login',
            'type'    => 'system',
        ];

        if ($username !== '') {
            $where[] = [
                'OR' => [
                    'items_id' => $users_id,
                    'message'  => ['LIKE', $username . '%'],
                ],
            ];
        } else {
            $where['items_id'] = $users_id;
        }

        $iterator = $DB->request([
            'FROM'  => 'glpi_events',
            'WHERE' => $where,
            'ORDER' => 'date DESC',
            'LIMIT' => 100,
        ]);

        if (count($iterator)) {
            foreach ($iterator as $data) {
                $message = $data['message'];
                $ip = self::extractIp($message);
                $auth_method = self::extractAuthMethod($message);
                
                echo "<tr class='tab_bg_1'>";
                echo "<td>" . Html::convDateTime($data['date']) . "</td>";
                echo "<td>" . htmlspecialchars($ip) . "</td>";
                echo "<td>" . htmlspecialchars($auth_method) . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr class='tab_bg_1'><td colspan='3' class='center'>Nenhum registro de acesso encontrado.</td></tr>";
        }
        
        echo "</table>";
        echo "</div>";
    }

    /**
     * Extrai o endereço IP da message do glpi_events.
     * Formato esperado: "... no IP 150.165.99.48 ..."
     */
    static function extractIp($message) {
        if (preg_match('/no IP\s+([\d.:a-fA-F]+)/', $message, $matches)) {
            return $matches[1];
        }
        return '-';
    }

    /**
     * Extrai o método de autenticação da message do glpi_events.
     * Logins SSO enriquecidos por plugins contêm um sufixo como " via NomeDoSSO".
     * Logins locais padrão não possuem esse sufixo.
     */
    static function extractAuthMethod($message) {
        $pos = strpos($message, ' via ');
        if ($pos !== false) {
            return trim(substr($message, $pos + 5));
        }
        return 'Banco de Dados Local';
    }
}

