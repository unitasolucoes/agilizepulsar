<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginAgilizepulsarMenu extends CommonGLPI {
    
    static $rightname = 'ticket';
    
    public static function getMenuName() {
        return __('Pulsar', 'agilizepulsar');
    }
    
    public static function getMenuContent() {
        global $CFG_GLPI;
        
        $menu = [];
        $menu['title'] = self::getMenuName();
        $menu['page'] = Plugin::getWebDir('agilizepulsar') . '/front/feed.php';
        $menu['icon'] = 'ti ti-bulb';
        
        $menu['options']['feed'] = [
            'title' => __('Feed', 'agilizepulsar'),
            'page' => Plugin::getWebDir('agilizepulsar') . '/front/feed.php',
            'icon' => 'ti ti-bolt'
        ];
        
        $menu['options']['myideas'] = [
            'title' => __('Minhas Ideias', 'agilizepulsar'),
            'page' => Plugin::getWebDir('agilizepulsar') . '/front/my_ideas.php',
            'icon' => 'ti ti-lightbulb'
        ];
        
        $menu['options']['dashboard'] = [
            'title' => __('Dashboard', 'agilizepulsar'),
            'page' => Plugin::getWebDir('agilizepulsar') . '/front/dashboard.php',
            'icon' => 'ti ti-chart-bar'
        ];
        
        if (Session::haveRight('config', UPDATE)) {
            $menu['options']['settings'] = [
                'title' => __('Configurações', 'agilizepulsar'),
                'page' => Plugin::getWebDir('agilizepulsar') . '/front/settings.php',
                'icon' => 'ti ti-settings'
            ];
        }
        
        return $menu;
    }
    
    public static function removeRightsFromSession() {
        return true;
    }
}