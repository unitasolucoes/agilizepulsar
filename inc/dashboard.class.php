<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginAgilizepulsarDashboard extends CommonGLPI {

    public static function dashboardCards() {
        $cards = [];

        $cards['plugin_agilizepulsar_stats'] = [
            'widgettype' => ['bigNumber'],
            'label'      => __('Estatísticas Pulsar', 'agilizepulsar'),
            'provider'   => 'PluginAgilizepulsarDashboard::cardStats'
        ];

        return $cards;
    }

    public static function cardStats(array $params = []) {
        $ideas = PluginAgilizepulsarTicket::getIdeas();

        return [
            'number' => count($ideas),
            'label'  => sprintf(__('%d ideias ativas', 'agilizepulsar'), count($ideas)),
            'url'    => Plugin::getWebDir('agilizepulsar') . '/front/feed.php'
        ];
    }
}
