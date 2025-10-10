<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginAgilizepulsarLike extends CommonDBTM {
    
    static $rightname = 'ticket';
    
    public static function getTypeName($nb = 0) {
        return __('Like', 'agilizepulsar');
    }
    
    public static function addLike($tickets_id, $users_id) {
        global $DB;

        if (!PluginAgilizepulsarTicket::isIdea($tickets_id)
            && !PluginAgilizepulsarTicket::isCampaign($tickets_id)) {
            return false;
        }

        if (self::userHasLiked($tickets_id, $users_id)) {
            return false;
        }

        $like   = new self();
        $result = $like->add([
            'tickets_id'    => $tickets_id,
            'users_id'      => $users_id,
            'date_creation' => $_SESSION['glpi_currenttime']
        ]);
        
        if ($result) {
            PluginAgilizepulsarUserPoints::addPoints($users_id, 'like', $tickets_id);
            PluginAgilizepulsarLog::add('like_added', $users_id, [
                'tickets_id' => $tickets_id
            ]);
        }
        
        return $result;
    }
    
    public static function remove($tickets_id, $users_id) {
        global $DB;
        
        $iterator = $DB->request([
            'FROM' => self::getTable(),
            'WHERE' => [
                'tickets_id' => $tickets_id,
                'users_id' => $users_id
            ]
        ]);
        
        if (count($iterator) > 0) {
            $data = $iterator->current();
            $like = new self();
            $result = $like->delete(['id' => $data['id']]);
            
            if ($result) {
                PluginAgilizepulsarUserPoints::removePoints($users_id, 'like', $tickets_id);
                PluginAgilizepulsarLog::add('like_removed', $users_id, [
                    'tickets_id' => $tickets_id
                ]);
            }
            
            return $result;
        }
        
        return false;
    }
    
    public static function countByTicket($tickets_id) {
        global $DB;
        
        $result = $DB->request([
            'COUNT' => 'id',
            'FROM' => self::getTable(),
            'WHERE' => ['tickets_id' => $tickets_id]
        ])->current();
        
        return $result['COUNT id'] ?? 0;
    }
    
    public static function userHasLiked($tickets_id, $users_id) {
        global $DB;
        
        $result = $DB->request([
            'COUNT' => 'id',
            'FROM' => self::getTable(),
            'WHERE' => [
                'tickets_id' => $tickets_id,
                'users_id' => $users_id
            ]
        ])->current();
        
        return ($result['COUNT id'] ?? 0) > 0;
    }
    
    public static function getByTicket($tickets_id) {
        global $DB;
        
        $iterator = $DB->request([
            'FROM' => self::getTable(),
            'WHERE' => ['tickets_id' => $tickets_id],
            'ORDER' => 'date_creation DESC'
        ]);
        
        $likes = [];
        foreach ($iterator as $data) {
            $user = new User();
            if ($user->getFromDB($data['users_id'])) {
                $data['user_name'] = $user->getFriendlyName();
                $likes[] = $data;
            }
        }
        
        return $likes;
    }
}