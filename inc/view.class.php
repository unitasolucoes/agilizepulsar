<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginAgilizepulsarView extends CommonDBTM {
    static $rightname = 'ticket';

    public static function addView($tickets_id, $users_id) {
        global $DB;

        if (empty($tickets_id) || empty($users_id)) {
            return false;
        }

        $exists = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => [
                'tickets_id' => $tickets_id,
                'users_id'   => $users_id
            ],
            'LIMIT' => 1
        ]);

        if (count($exists) > 0) {
            return true;
        }

        $view = new self();
        return $view->add([
            'tickets_id' => $tickets_id,
            'users_id'   => $users_id,
            'viewed_at'  => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s')
        ]);
    }

    public static function countByTicket($tickets_id) {
        global $DB;

        $result = $DB->request([
            'SELECT' => [
                'views' => new QueryExpression('COUNT(DISTINCT users_id)')
            ],
            'FROM'  => self::getTable(),
            'WHERE' => ['tickets_id' => $tickets_id]
        ])->current();

        return (int)($result['views'] ?? 0);
    }

    public static function getByTicket($tickets_id) {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => ['users_id', 'viewed_at'],
            'FROM'   => self::getTable(),
            'WHERE'  => ['tickets_id' => $tickets_id],
            'ORDER'  => 'viewed_at DESC'
        ]);

        $views = [];
        foreach ($iterator as $data) {
            $user = new User();
            if ($user->getFromDB($data['users_id'])) {
                $data['user_name'] = $user->getFriendlyName();
                $views[] = $data;
            }
        }

        return $views;
    }
}
