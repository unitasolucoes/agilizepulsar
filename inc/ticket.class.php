<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginAgilizepulsarTicket {

    public static function isIdea($tickets_id) {
        $config = PluginAgilizepulsarConfig::getConfig();
        $CATEGORY_IDEA = $config['idea_category_id'];

        $ticket = new Ticket();
        if (!$ticket->getFromDB($tickets_id)) {
            return false;
        }

        return $ticket->fields['itilcategories_id'] == $CATEGORY_IDEA;
    }

    public static function isCampaign($tickets_id) {
        $config = PluginAgilizepulsarConfig::getConfig();
        $CATEGORY_CAMPAIGN = $config['campaign_category_id'];

        $ticket = new Ticket();
        if (!$ticket->getFromDB($tickets_id)) {
            return false;
        }

        return $ticket->fields['itilcategories_id'] == $CATEGORY_CAMPAIGN;
    }

    public static function getIdeas($filters = []) {
        global $DB;

        $config = PluginAgilizepulsarConfig::getConfig();
        $CATEGORY_IDEA = $config['idea_category_id'];

        $where = ['itilcategories_id' => $CATEGORY_IDEA];
        
        if (isset($filters['campaign_id'])) {
            $where['id'] = new QuerySubQuery([
                'SELECT' => 'tickets_id',
                'FROM' => 'glpi_items_tickets',
                'WHERE' => [
                    'itemtype' => 'Ticket',
                    'items_id' => $filters['campaign_id']
                ]
            ]);
        }
        
        if (isset($filters['status'])) {
            $where['status'] = $filters['status'];
        }
        
        if (isset($filters['users_id'])) {
            $where['users_id_recipient'] = $filters['users_id'];
        }
        
        $iterator = $DB->request([
            'FROM' => 'glpi_tickets',
            'WHERE' => $where,
            'ORDER' => 'date DESC'
        ]);
        
        $ideas = [];
        foreach ($iterator as $data) {
            $ideas[] = self::enrichTicketData($data);
        }
        
        return $ideas;
    }
    
    public static function getCampaigns($filters = []) {
        global $DB;

        $config = PluginAgilizepulsarConfig::getConfig();
        $CATEGORY_CAMPAIGN = $config['campaign_category_id'];

        $where = ['itilcategories_id' => $CATEGORY_CAMPAIGN];
        
        if (isset($filters['is_active'])) {
            $where['status'] = [Ticket::INCOMING, Ticket::ASSIGNED, Ticket::PLANNED, Ticket::WAITING];
        }
        
        $iterator = $DB->request([
            'FROM' => 'glpi_tickets',
            'WHERE' => $where,
            'ORDER' => 'date DESC'
        ]);
        
        $campaigns = [];
        foreach ($iterator as $data) {
            $campaigns[] = self::enrichTicketData($data);
        }
        
        return $campaigns;
    }
    
    public static function getIdeasByCampaign($campaign_id) {
        global $DB;

        $config = PluginAgilizepulsarConfig::getConfig();
        $CATEGORY_IDEA = $config['idea_category_id'];
        
        $iterator = $DB->request([
            'SELECT' => 'tickets_id',
            'FROM' => 'glpi_items_tickets',
            'WHERE' => [
                'itemtype' => 'Ticket',
                'items_id' => $campaign_id
            ]
        ]);
        
        $ideas = [];
        foreach ($iterator as $data) {
            $ticket = new Ticket();
            if ($ticket->getFromDB($data['tickets_id'])
                && $ticket->fields['itilcategories_id'] == $CATEGORY_IDEA) {
                $ideas[] = self::enrichTicketData($ticket->fields);
            }
        }

        return $ideas;
    }

    public static function enrichTicketData($ticket_data) {
        $ticket_data['likes_count'] = PluginAgilizepulsarLike::countByTicket($ticket_data['id']);
        $ticket_data['comments_count'] = PluginAgilizepulsarComment::countByTicket($ticket_data['id']);
        $ticket_data['views_count'] = PluginAgilizepulsarView::countByTicket($ticket_data['id']);
        $ticket_data['has_liked'] = PluginAgilizepulsarLike::userHasLiked($ticket_data['id'], Session::getLoginUserID());

        $ticket_data['campaign_id'] = null;
        $ticket_data['campaign_name'] = null;
        $ticket_data['campaign_deadline'] = null;

        $config = PluginAgilizepulsarConfig::getConfig();
        $idea_category_id = (int) ($config['idea_category_id'] ?? 0);

        if ((int) ($ticket_data['itilcategories_id'] ?? 0) === $idea_category_id) {
            $campaign_link = self::getCampaignForIdea($ticket_data['id']);
            if (!empty($campaign_link)) {
                $ticket_data['campaign_id'] = (int) $campaign_link['campaign_id'];
                $ticket_data['campaign_name'] = $campaign_link['campaign_name'];
                $ticket_data['campaign_deadline'] = $campaign_link['campaign_deadline'];
            }
        }

        return $ticket_data;
    }

    public static function getCampaignForIdea($tickets_id) {
        global $DB;

        if ($tickets_id <= 0) {
            return [];
        }

        $iterator = $DB->request([
            'SELECT' => [
                'link_id' => 'it.id',
                'campaign_id' => 'it.items_id',
                'campaign_name' => 't.name',
                'campaign_deadline' => 't.time_to_resolve'
            ],
            'FROM' => 'glpi_items_tickets AS it',
            'LEFT JOIN' => [
                'glpi_tickets AS t' => [
                    'FKEY' => [
                        't' => 'id',
                        'it' => 'items_id'
                    ]
                ]
            ],
            'WHERE' => [
                'it.tickets_id' => $tickets_id,
                'it.itemtype' => 'Ticket'
            ],
            'LIMIT' => 1
        ]);

        if (count($iterator) === 0) {
            return [];
        }

        $data = $iterator->current();

        if (!empty($data['campaign_deadline'])) {
            $data['campaign_deadline'] = Html::convDateTime($data['campaign_deadline']);
        } else {
            $data['campaign_deadline'] = null;
        }

        return $data;
    }
    
    public static function getFormAnswers($tickets_id) {
        global $DB;
        
        $ticket = new Ticket();
        if (!$ticket->getFromDB($tickets_id)) {
            return [];
        }
        
        return [
            'Descrição' => $ticket->fields['content']
        ];
    }
    
    public static function getCoauthors($tickets_id) {
        global $DB;
        
        $iterator = $DB->request([
            'SELECT' => 'users_id',
            'FROM' => 'glpi_tickets_users',
            'WHERE' => [
                'tickets_id' => $tickets_id,
                'type' => CommonITILActor::OBSERVER
            ]
        ]);
        
        $coauthors = [];
        foreach ($iterator as $data) {
            $user = new User();
            if ($user->getFromDB($data['users_id'])) {
                $coauthors[] = $user->fields;
            }
        }
        
        return $coauthors;
    }
}