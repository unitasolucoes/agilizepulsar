<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginAgilizepulsarPointsHistory extends CommonDBTM {
    
    static $rightname = 'ticket';
    
    public static function getTypeName($nb = 0) {
        return __('Points History', 'agilizepulsar');
    }
    
    public static function add($data) {
        $history = new self();
        
        $data['date_creation'] = $_SESSION['glpi_currenttime'];
        
        return $history->add($data);
    }
    
    public static function getByUser($users_id, $limit = 50) {
        global $DB;
        
        $iterator = $DB->request([
            'FROM' => self::getTable(),
            'WHERE' => ['users_id' => $users_id],
            'ORDER' => 'date_creation DESC',
            'LIMIT' => $limit
        ]);
        
        $history = [];
        foreach ($iterator as $data) {
            $history[] = $data;
        }
        
        return $history;
    }
    
    public static function getByTicket($tickets_id, $limit = 50) {
        global $DB;
        
        $iterator = $DB->request([
            'FROM' => self::getTable(),
            'WHERE' => [
                'reference_id' => $tickets_id,
                'reference_type' => 'Ticket'
            ],
            'ORDER' => 'date_creation DESC',
            'LIMIT' => $limit
        ]);
        
        $history = [];
        foreach ($iterator as $data) {
            $user = new User();
            if ($user->getFromDB($data['users_id'])) {
                $data['user_name'] = $user->getFriendlyName();
                $history[] = $data;
            }
        }
        
        return $history;
    }
}