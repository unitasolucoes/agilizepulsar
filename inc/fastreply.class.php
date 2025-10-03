<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginAgilizepulsarFastReply extends CommonDBTM {
    
    static $rightname = 'config';
    
    public static function getTypeName($nb = 0) {
        return __('Fast Reply', 'agilizepulsar');
    }
    
    public static function getByStep($step_number) {
        global $DB;
        
        $iterator = $DB->request([
            'FROM' => self::getTable(),
            'WHERE' => ['step_number' => $step_number],
            'ORDER' => 'name ASC'
        ]);
        
        $replies = [];
        foreach ($iterator as $data) {
            $replies[] = $data;
        }
        
        return $replies;
    }
    
    public static function getAll() {
        global $DB;
        
        $iterator = $DB->request([
            'FROM' => self::getTable(),
            'ORDER' => ['step_number ASC', 'name ASC']
        ]);
        
        $replies = [];
        foreach ($iterator as $data) {
            $replies[] = $data;
        }
        
        return $replies;
    }
}