<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginAgilizepulsarMenu extends CommonGLPI {
    
    static $rightname = 'ticket';

    public static function getMenuName() {
        $config = PluginAgilizepulsarConfig::getConfig();
        return $config['menu_name'];
    }
    
    public static function getMenuContent() {
        global $CFG_GLPI;
        
        $menu = [];
        $menu['title'] = self::getMenuName();
        $menu['page'] = Plugin::getWebDir('agilizepulsar') . '/front/feed.php';
        $menu['icon'] = 'fa-solid fa-lightbulb';
        
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
        
        $profile_id = $_SESSION['glpiactiveprofile']['id'] ?? 0;
        if (PluginAgilizepulsarConfig::canAdmin($profile_id)) {
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