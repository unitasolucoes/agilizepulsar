<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginAgilizepulsarIdeaCampaign {

    public static function getTable(): string {
        return 'glpi_plugin_agilizepulsar_idea_campaigns';
    }

    public static function linkIdeaToCampaign(int $idea_id, int $campaign_id, int $user_id): bool {
        global $DB;

        if ($idea_id <= 0 || $campaign_id <= 0 || $user_id <= 0) {
            return false;
        }

        if (!PluginAgilizepulsarTicket::isIdea($idea_id)) {
            return false;
        }

        if (!PluginAgilizepulsarTicket::isCampaign($campaign_id)) {
            return false;
        }

        if (self::isIdeaLinkedToCampaign($idea_id, $campaign_id)) {
            return true;
        }

        $data = [
            'idea_id'    => $idea_id,
            'campaign_id'=> $campaign_id,
            'linked_by'  => $user_id,
            'linked_at'  => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s')
        ];

        $result = $DB->insert(self::getTable(), $data);

        if ($result && class_exists('PluginAgilizepulsarLog')) {
            PluginAgilizepulsarLog::add('idea_campaign_linked', $user_id, $data);
        }

        return (bool) $result;
    }

    public static function unlinkIdeaFromCampaign(int $idea_id, int $campaign_id, int $user_id): bool {
        global $DB;

        if ($idea_id <= 0 || $campaign_id <= 0) {
            return false;
        }

        $deleted = $DB->delete(self::getTable(), [
            'idea_id'    => $idea_id,
            'campaign_id'=> $campaign_id
        ]);

        if ($deleted && class_exists('PluginAgilizepulsarLog')) {
            PluginAgilizepulsarLog::add('idea_campaign_unlinked', $user_id, [
                'idea_id'     => $idea_id,
                'campaign_id' => $campaign_id
            ]);
        }

        return (bool) $deleted;
    }

    public static function getIdeaCampaigns(int $idea_id): array {
        global $DB;

        if ($idea_id <= 0) {
            return [];
        }

        $iterator = $DB->request([
            'SELECT' => [
                'ic.campaign_id',
                'ic.linked_at',
                'ic.linked_by',
                't.name AS campaign_name',
                't.time_to_resolve'
            ],
            'FROM' => self::getTable() . ' AS ic',
            'LEFT JOIN' => [
                'glpi_tickets AS t' => [
                    'FKEY' => [
                        't' => 'id',
                        'ic' => 'campaign_id'
                    ]
                ]
            ],
            'WHERE' => ['ic.idea_id' => $idea_id]
        ]);

        $results = [];
        foreach ($iterator as $row) {
            $row['time_to_resolve'] = $row['time_to_resolve'] ? Html::convDateTime($row['time_to_resolve']) : null;
            $results[] = $row;
        }

        return $results;
    }

    public static function getCampaignIdeas(int $campaign_id): array {
        global $DB;

        if ($campaign_id <= 0) {
            return [];
        }

        $iterator = $DB->request([
            'SELECT' => [
                'ic.idea_id',
                'ic.linked_at',
                'ic.linked_by',
                't.name AS idea_name',
                't.status'
            ],
            'FROM' => self::getTable() . ' AS ic',
            'LEFT JOIN' => [
                'glpi_tickets AS t' => [
                    'FKEY' => [
                        't' => 'id',
                        'ic' => 'idea_id'
                    ]
                ]
            ],
            'WHERE' => ['ic.campaign_id' => $campaign_id]
        ]);

        $results = [];
        foreach ($iterator as $row) {
            $results[] = $row;
        }

        return $results;
    }

    public static function isIdeaLinkedToCampaign(int $idea_id, int $campaign_id): bool {
        global $DB;

        if ($idea_id <= 0 || $campaign_id <= 0) {
            return false;
        }

        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'idea_id'    => $idea_id,
                'campaign_id'=> $campaign_id
            ],
            'LIMIT'  => 1
        ]);

        return count($iterator) > 0;
    }

    public static function countIdeaCampaigns(int $idea_id): int {
        global $DB;

        if ($idea_id <= 0) {
            return 0;
        }

        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => self::getTable(),
            'WHERE'  => ['idea_id' => $idea_id]
        ]);

        $total = 0;
        foreach ($iterator as $_row) {
            $total++;
        }

        return $total;
    }

    public static function countCampaignIdeas(int $campaign_id): int {
        global $DB;

        if ($campaign_id <= 0) {
            return 0;
        }

        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => self::getTable(),
            'WHERE'  => ['campaign_id' => $campaign_id]
        ]);

        $total = 0;
        foreach ($iterator as $_row) {
            $total++;
        }

        return $total;
    }

    public static function getLinkForIdea(int $idea_id): array {
        global $DB;

        if ($idea_id <= 0) {
            return [];
        }

        $iterator = $DB->request([
            'SELECT' => [
                'ic.id AS link_id',
                'ic.idea_id',
                'ic.campaign_id',
                'ic.linked_at',
                'ic.linked_by',
                'campaign.name AS campaign_name',
                'campaign.time_to_resolve'
            ],
            'FROM' => self::getTable() . ' AS ic',
            'LEFT JOIN' => [
                'glpi_tickets AS campaign' => [
                    'FKEY' => [
                        'campaign' => 'id',
                        'ic'       => 'campaign_id'
                    ]
                ]
            ],
            'WHERE' => ['ic.idea_id' => $idea_id],
            'ORDER' => 'ic.linked_at DESC',
            'LIMIT' => 1
        ]);

        if (count($iterator) === 0) {
            return [];
        }

        $row = $iterator->current();
        $row['campaign_deadline'] = $row['time_to_resolve'] ? Html::convDateTime($row['time_to_resolve']) : null;
        unset($row['time_to_resolve']);

        return $row;
    }
}
