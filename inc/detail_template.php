<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginAgilizepulsarDetailTemplate {

    public static function render($tickets_id, $type = 'idea') {
        global $CFG_GLPI, $DB;

        Session::checkLoginUser();

        $user_profile = $_SESSION['glpiactiveprofile']['id'] ?? 0;

        if (!PluginAgilizepulsarConfig::canView($user_profile)) {
            Html::displayRightError();
            exit;
        }

        $config    = PluginAgilizepulsarConfig::getConfig();
        $menu_name = $config['menu_name'];

        $is_idea     = PluginAgilizepulsarTicket::isIdea($tickets_id);
        $is_campaign = PluginAgilizepulsarTicket::isCampaign($tickets_id);

        if (!$is_idea && !$is_campaign) {
            Html::displayErrorAndDie(__('Access denied'));
        }

        if ($type === 'auto') {
            $type = $is_idea ? 'idea' : 'campaign';
        }

        $ticket = new Ticket();
        if (!$ticket->getFromDB($tickets_id)) {
            Html::displayErrorAndDie(__('Item not found'));
        }

        PluginAgilizepulsarView::addView($tickets_id, Session::getLoginUserID());

        $data = PluginAgilizepulsarTicket::enrichTicketData($ticket->fields);

        // Buscar followups nativos do GLPI
        $comments = [];
        $followup_iterator = $DB->request([
            'FROM'  => 'glpi_itilfollowups',
            'WHERE' => [
                'itemtype'   => 'Ticket',
                'items_id'   => $tickets_id,
                'is_private' => 0
            ],
            'ORDER' => 'date_creation DESC'
        ]);

        foreach ($followup_iterator as $followup_data) {
            $user = new User();
            $user_name = 'Usuário';
            if ($user->getFromDB($followup_data['users_id'])) {
                $user_name = $user->getFriendlyName();
            }
            
            // Decodificar HTML entities
            $content = html_entity_decode($followup_data['content'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            
            $comments[] = [
                'id' => $followup_data['id'],
                'user_name' => $user_name,
                'date_creation' => $followup_data['date_creation'],
                'content' => $content
            ];
        }

        $specific_data = [];
        if ($type === 'campaign') {
            $specific_data['ideas'] = PluginAgilizepulsarTicket::getIdeasByCampaign($tickets_id);
        } else {
            $specific_data['form_answers'] = PluginAgilizepulsarTicket::getFormAnswers($tickets_id);
        }

        $can_admin = PluginAgilizepulsarConfig::canAdmin($user_profile);
        $can_like  = PluginAgilizepulsarConfig::canLike($user_profile);

        $author_name     = __('Não informado', 'agilizepulsar');
        $author_initials = '??';
        $user            = new User();
        if (!empty($data['users_id_recipient']) && $user->getFromDB($data['users_id_recipient'])) {
            $author_name    = $user->getFriendlyName();
            $initial_source = trim($user->fields['realname'] ?? '') ?: trim($user->fields['firstname'] ?? '');
            if ($initial_source === '') {
                $initial_source = trim($user->fields['name'] ?? '');
            }
            $author_initials = strtoupper(substr($initial_source, 0, 2));
            if ($author_initials === '') {
                $author_initials = '??';
            }
        }

        $title = sprintf(__('%s — %s', 'agilizepulsar'), $menu_name, $data['name']);
        if (Session::getCurrentInterface() === 'helpdesk') {
            Html::helpHeader($title, '', 'helpdesk', 'management');
        } else {
            Html::header($title, $_SERVER['PHP_SELF'], 'management', 'pulsar');
        }

        $template_dir  = dirname(__DIR__) . '/templates';
        $template_file = $template_dir . '/' . $type . '_detail.tpl.php';
        if (!file_exists($template_file)) {
            Html::displayErrorAndDie(__('Template not found'));
        }

        $tickets_id_local = $tickets_id;
        $tickets_id       = $tickets_id_local;

        include $template_file;

        Html::footer();
    }
}