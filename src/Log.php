<?php

namespace GlpiPlugin\Authhistory;

use CommonDBTM;
use CommonGLPI;
use Html;
use Session;
use TemplateRenderer;
use User;

class Log extends CommonDBTM {
    
    static $rightname = 'user';

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
        // Expressão regular para encontrar qualquer IPv4 ou IPv6 na string, ignorando o idioma do log
        $ipv4 = '\b(?:\d{1,3}\.){3}\d{1,3}\b';
        $ipv6 = '\b(?:[A-Fa-f0-9]{1,4}:){1,7}[A-Fa-f0-9]{1,4}::?\b|\b(?:[A-Fa-f0-9]{1,4}:){1,7}:|\b::(?:[A-Fa-f0-9]{1,4}:){0,7}[A-Fa-f0-9]{1,4}\b';
        
        if (preg_match("/($ipv4)/", $message, $matches)) {
            return $matches[1];
        } elseif (preg_match("/([A-Fa-f0-9:]+:[A-Fa-f0-9:]+)/", $message, $matches) && filter_var($matches[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $matches[1]; // Fallback seguro para IPv6 usando o validador nativo do PHP
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
