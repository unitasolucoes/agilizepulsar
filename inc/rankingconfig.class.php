<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginAgilizepulsarRankingConfig extends CommonDBTM {

    static $rightname = 'config';

    public static function getTable($classname = null) {
        return 'glpi_plugin_agilizepulsar_rankingconfig';
    }

    public static function getTypeName($nb = 0) {
        return __('Ranking Configuration', 'agilizepulsar');
    }

    public static function getPointsValue($action_type) {
        global $DB;

        $iterator = $DB->request([
            'FROM' => self::getTable(),
            'WHERE' => [
                'action_type' => $action_type,
                'is_active'   => 1
            ],
            'LIMIT' => 1
        ]);

        if (count($iterator) > 0) {
            $data = $iterator->current();
            return (int) $data['points_value'];
        }

        return 0;
    }

    public static function getAllConfig() {
        global $DB;

        $iterator = $DB->request([
            'FROM'  => self::getTable(),
            'ORDER' => 'action_type ASC'
        ]);

        $configs = [];
        foreach ($iterator as $data) {
            $configs[$data['action_type']] = $data;
        }

        return $configs;
    }

    public static function updatePointsValue($action_type, $points_value, $is_active = 1) {
        global $DB;

        $iterator = $DB->request([
            'FROM' => self::getTable(),
            'WHERE' => ['action_type' => $action_type],
            'LIMIT' => 1
        ]);

        if (count($iterator) > 0) {
            $data   = $iterator->current();
            $config = new self();

            return $config->update([
                'id'           => $data['id'],
                'points_value' => $points_value,
                'is_active'    => $is_active
            ]);
        }

        $config = new self();
        return $config->add([
            'action_type'  => $action_type,
            'points_value' => $points_value,
            'is_active'    => $is_active
        ]);
    }
}
