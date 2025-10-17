<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginAgilizepulsarMenu extends CommonGLPI {

    static $rightname = 'ticket';

    public static function getMenuName() {
        $config = PluginAgilizepulsarConfig::getConfig();
        return $config['menu_name'] ?? __('Pulsar', 'agilizepulsar');
    }

    public static function getMenuContent() {
        $profile_id = $_SESSION['glpiactiveprofile']['id'] ?? 0;

        if (!PluginAgilizepulsarConfig::canView($profile_id)) {
            return false;
        }

        $config = PluginAgilizepulsarConfig::getConfig();
        $menu_name = $config['menu_name'] ?? __('Pulsar', 'agilizepulsar');
        $menu_icon = $config['menu_icon'] ?? 'fa-solid fa-rocket';

        $menu = [
            'title' => $menu_name,
            'page'  => Plugin::getWebDir('agilizepulsar') . '/front/feed.php',
            'icon'  => $menu_icon,
        ];

        $menu['options'] = [];

        $menu['options']['feed'] = [
            'title' => __('Feed de Ideias', 'agilizepulsar'),
            'page'  => Plugin::getWebDir('agilizepulsar') . '/front/feed.php',
            'icon'  => 'ti ti-home'
        ];

        $menu['options']['nova_ideia'] = [
            'title' => __('Nova Ideia', 'agilizepulsar'),
            'page'  => Plugin::getWebDir('agilizepulsar') . '/front/nova_ideia.php',
            'icon'  => 'ti ti-bulb'
        ];

        $menu['options']['minhas_ideias'] = [
            'title' => __('Minhas Ideias', 'agilizepulsar'),
            'page'  => Plugin::getWebDir('agilizepulsar') . '/front/my_ideas.php',
            'icon'  => 'ti ti-user'
        ];

        $menu['options']['campanhas'] = [
            'title' => __('Campanhas', 'agilizepulsar'),
            'page'  => Plugin::getWebDir('agilizepulsar') . '/front/campaigns.php',
            'icon'  => 'ti ti-flag'
        ];

        if (PluginAgilizepulsarConfig::canAdmin($profile_id)) {
            $menu['options']['nova_campanha'] = [
                'title' => __('Nova Campanha', 'agilizepulsar'),
                'page'  => Plugin::getWebDir('agilizepulsar') . '/front/nova_campanha.php',
                'icon'  => 'ti ti-flag-plus'
            ];

            $menu['options']['config'] = [
                'title' => __('Configurações', 'agilizepulsar'),
                'page'  => Plugin::getWebDir('agilizepulsar') . '/front/settings.php',
                'icon'  => 'ti ti-settings'
            ];
        }

        return $menu;
    }

    public static function removeRightsFromSession() {
        return true;
    }
}
