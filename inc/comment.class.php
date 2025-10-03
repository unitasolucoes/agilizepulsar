<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginAgilizepulsarComment extends CommonDBTM {
    
    static $rightname = 'ticket';
    
    public static function getTypeName($nb = 0) {
        return __('Comment', 'agilizepulsar');
    }
    
    public static function addComment($tickets_id, $users_id, $content) {
        global $DB;
        
        if (!PluginAgilizepulsarTicket::isIdea($tickets_id)) {
            return false;
        }
        
        if (empty(trim($content))) {
            return false;
        }
        
        $comment = new self();
        $result = $comment->add([
            'tickets_id' => $tickets_id,
            'users_id' => $users_id,
            'content' => $content,
            'date_creation' => $_SESSION['glpi_currenttime']
        ]);
        
        if ($result) {
            PluginAgilizepulsarUserPoints::addPoints($users_id, 'comment', $tickets_id);
            PluginAgilizepulsarLog::add('comment_added', $users_id, [
                'tickets_id' => $tickets_id,
                'comment_id' => $result
            ]);
        }
        
        return $result;
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
    
    public static function getByTicket($tickets_id) {
        global $DB;
        
        $iterator = $DB->request([
            'FROM' => self::getTable(),
            'WHERE' => ['tickets_id' => $tickets_id],
            'ORDER' => 'date_creation DESC'
        ]);
        
        $comments = [];
        foreach ($iterator as $data) {
            $user = new User();
            if ($user->getFromDB($data['users_id'])) {
                $data['user_name'] = $user->getFriendlyName();
                $data['user_picture'] = $user->fields['picture'] ?? '';
                $comments[] = $data;
            }
        }
        
        return $comments;
    }
}