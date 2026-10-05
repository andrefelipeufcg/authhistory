<?php

namespace GlpiPlugin\Authhistory;

use CommonDBTM;
use CommonGLPI;
use Html;
use Session;
use TemplateRenderer;
use User;

// GLPI 12 exige $rightname tipada (string); GLPI 10 proíbe o tipo.
if ((new \ReflectionProperty(CommonDBTM::class, 'rightname'))->hasType()) {
    abstract class LogBase extends CommonDBTM {
        public static string $rightname = 'user';
    }
} else {
    abstract class LogBase extends CommonDBTM {
        public static $rightname = 'user';
    }
}

class Log extends LogBase {

    static function getTypeName($nb = 0) {
        return __('Histórico de Acesso', 'authhistory');
    }

    static function getIcon() {
        return 'fas fa-history';
    }
    
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if ($item->getType() == 'User' && Session::haveRight('user', READ) && Session::haveAccessToEntity($item->fields['entities_id'] ?? 0)) {
            return self::createTabEntry(self::getTypeName(), 0, __CLASS__, self::getIcon());
        }
        return '';
    }
    
    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if (!Session::haveRight('user', READ) || !Session::haveAccessToEntity($item->fields['entities_id'] ?? 0)) {
            return false;
        }

        global $DB;
        $users_id = $item->getID();

        $username = '';
        $user = new User();
        if ($user->getFromDB($users_id)) {
            $username = $user->fields['name'];
        }

        $where = [
            'service' => 'login',
            'type'    => 'system',
        ];

        if ($username !== '') {
            $username_escaped = addcslashes($username, '%_');
            $where[] = [
                'OR' => [
                    'items_id' => $users_id,
                    'message'  => ['LIKE', $username_escaped . '%'],
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

        $events = [];
        foreach ($iterator as $data) {
            $events[] = [
                'date'        => Html::convDateTime($data['date']),
                'ip'          => self::extractIp($data['message']),
                'auth_method' => self::extractAuthMethod($data['message'])
            ];
        }

        \Glpi\Application\View\TemplateRenderer::getInstance()->display('@authhistory/log.html.twig', [
            'events' => $events
        ]);
        
        return true;
    }

    static function extractIp($message) {
        // Limita o tamanho da mensagem para evitar ReDoS
        $message = substr($message, 0, 255);

        // Extrai todas as palavras que contêm caracteres válidos de IP (v4, v6 ou v4-mapped)
        if (preg_match_all('/([A-Fa-f0-9\.:]+)/', $message, $matches)) {
            // Verifica de trás para frente, pois o IP costuma ficar no fim da mensagem nativa,
            // evitando falsos positivos caso o nome de usuário se pareça com um IP.
            $candidates = array_reverse($matches[1]);
            foreach ($candidates as $match) {
                if (filter_var($match, FILTER_VALIDATE_IP)) {
                    return $match;
                }
            }
        }
        
        return '-';
    }

    static $via_translations_cache = null;

    static function getViaTranslations() {
        if (self::$via_translations_cache !== null) {
            return self::$via_translations_cache;
        }

        // Garante o fallback básico
        self::$via_translations_cache = [' via '];

        $locales_dir = __DIR__ . '/../locales';
        if (is_dir($locales_dir)) {
            // Lê todos os arquivos PO disponíveis dinamicamente
            foreach (glob($locales_dir . '/*.po') as $file) {
                $content = file_get_contents($file);
                // Procura a tradução exata do msgid "via"
                if (preg_match('/msgid "via"\s*msgstr "([^"]+)"/i', $content, $matches)) {
                    $trans = ' ' . $matches[1] . ' ';
                    if (!in_array($trans, self::$via_translations_cache)) {
                        self::$via_translations_cache[] = $trans;
                    }
                }
            }
        }

        return self::$via_translations_cache;
    }

    static function extractAuthMethod($message) {
        $via_translations = self::getViaTranslations();

        // Busca o "via" em qualquer idioma lido dinamicamente
        foreach ($via_translations as $via_string) {
            $pos = strpos($message, $via_string);
            if ($pos !== false) {
                return trim(substr($message, $pos + strlen($via_string)));
            }
        }

        // Padrão se não for externo
        return __('Banco de Dados Local', 'authhistory');
    }
}
