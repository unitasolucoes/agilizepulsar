<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginAgilizepulsarIdeaCampaign {

    public static function getTable(): string {
        return 'glpi_plugin_agilizepulsar_idea_campaigns';
    }

    public static function linkIdeaToCampaign(int $idea_id, int $campaign_id, int $users_id = 0): bool {
        global $DB;

        if ($idea_id <= 0 || $campaign_id <= 0) {
            return false;
        }

        $existing = self::getRawLinkForIdea($idea_id);

        $data = [
            'ideas_id'      => $idea_id,
            'campaigns_id'  => $campaign_id,
            'users_id'      => $users_id > 0 ? $users_id : null,
            'date_creation' => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s')
        ];

        if (!empty($existing)) {
            return (bool) $DB->update(
                self::getTable(),
                [
                    'campaigns_id'  => $data['campaigns_id'],
                    'users_id'      => $data['users_id'],
                    'date_creation' => $data['date_creation']
                ],
                ['id' => (int) $existing['id']]
            );
        }

        return (bool) $DB->insert(self::getTable(), $data);
    }

    public static function unlinkIdea(int $idea_id): bool {
        global $DB;

        if ($idea_id <= 0) {
            return false;
        }

        return (bool) $DB->delete(self::getTable(), ['ideas_id' => $idea_id]);
    }

    public static function getLinkForIdea(int $idea_id): array {
        global $DB;

        if ($idea_id <= 0) {
            return [];
        }

        $iterator = $DB->request([
            'SELECT'   => [
                'link_id'          => 'ic.id',
                'campaign_id'      => 'ic.campaigns_id',
                'linked_at'        => 'ic.date_creation',
                'linked_by'        => 'ic.users_id',
                'campaign_name'    => 'campaign.name',
                'campaign_deadline'=> 'campaign.time_to_resolve'
            ],
            'FROM'     => self::getTable() . ' AS ic',
            'LEFT JOIN'=> [
                'glpi_tickets AS campaign' => [
                    'FKEY' => [
                        'campaign' => 'id',
                        'ic'       => 'campaigns_id'
                    ]
                ]
            ],
            'WHERE'    => ['ic.ideas_id' => $idea_id],
            'LIMIT'    => 1
        ]);

        if (count($iterator) === 0) {
            return [];
        }

        $row = $iterator->current();

        return [
            'link_id'           => (int) ($row['link_id'] ?? 0),
            'campaign_id'       => (int) ($row['campaign_id'] ?? 0),
            'campaign_name'     => $row['campaign_name'] ?? null,
            'campaign_deadline' => $row['campaign_deadline'] ?? null,
            'linked_at'         => $row['linked_at'] ?? null,
            'linked_by'         => (int) ($row['linked_by'] ?? 0)
        ];
    }

    public static function getIdeasIdsByCampaign(int $campaign_id): array {
        global $DB;

        if ($campaign_id <= 0) {
            return [];
        }

        $iterator = $DB->request([
            'SELECT' => 'ideas_id',
            'FROM'   => self::getTable(),
            'WHERE'  => ['campaigns_id' => $campaign_id]
        ]);

        $ideas = [];
        foreach ($iterator as $row) {
            $ideas[] = (int) $row['ideas_id'];
        }

        return $ideas;
    }

    private static function getRawLinkForIdea(int $idea_id): array {
        global $DB;

        if ($idea_id <= 0) {
            return [];
        }

        $iterator = $DB->request([
            'SELECT' => '*',
            'FROM'   => self::getTable(),
            'WHERE'  => ['ideas_id' => $idea_id],
            'LIMIT'  => 1
        ]);

        if (count($iterator) === 0) {
            return [];
        }

        return $iterator->current();
    }
}
