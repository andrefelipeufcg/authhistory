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
        return 'Histórico de Acesso';
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

        TemplateRenderer::getInstance()->display('plugin_authhistory/log.html.twig', [
            'events' => $events
        ]);
        
        return true;
    }

    static function extractIp($message) {
        if (preg_match('/no IP\s+([\d.:a-fA-F]+)/', $message, $matches)) {
            return $matches[1];
        }
        return '-';
    }

    static function extractAuthMethod($message) {
        $pos = strpos($message, ' via ');
        if ($pos !== false) {
            return trim(substr($message, $pos + 5));
        }
        return 'Banco de Dados Local';
    }
}
