<?php

define('GLPI_ROOT', dirname(__DIR__, 3));
include GLPI_ROOT . '/inc/includes.php';

while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json; charset=UTF-8');

if (!Session::getLoginUserID()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => __('Não autorizado', 'agilizepulsar')]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => __('Método não permitido', 'agilizepulsar')]);
    exit;
}

$csrf_valid = true;
if (method_exists('Session', 'validateCSRF')) {
    $csrf_valid = Session::validateCSRF($_POST);
} elseif (method_exists('Session', 'checkCSRF')) {
    try {
        Session::checkCSRF($_POST);
    } catch (Throwable $e) {
        $csrf_valid = false;
    }
}

if (!$csrf_valid) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => __('Token CSRF inválido', 'agilizepulsar')]);
    exit;
}

$action      = $_POST['action'] ?? '';
$idea_id     = (int) ($_POST['idea_id'] ?? 0);
$campaign_id = (int) ($_POST['campaign_id'] ?? 0);
$user_id     = Session::getLoginUserID();

$config = PluginAgilizepulsarConfig::getConfig();
$idea_category_id = (int) ($config['idea_category_id'] ?? 0);
$campaign_category_id = (int) ($config['campaign_category_id'] ?? 0);

$idea = null;
if ($action !== 'get_campaigns') {
    if ($idea_id <= 0) {
        echo json_encode(['success' => false, 'message' => __('ID da ideia inválido', 'agilizepulsar')]);
        exit;
    }

    $idea = new Ticket();
    if (!$idea->getFromDB($idea_id)) {
        echo json_encode(['success' => false, 'message' => __('Ideia não encontrada', 'agilizepulsar')]);
        exit;
    }

    if ((int) $idea->fields['itilcategories_id'] !== $idea_category_id) {
        echo json_encode(['success' => false, 'message' => __('Ticket informado não é uma ideia válida', 'agilizepulsar')]);
        exit;
    }
}

try {
    global $DB;

    if ($action === 'link') {
        if ($campaign_id <= 0) {
            echo json_encode(['success' => false, 'message' => __('Campanha inválida', 'agilizepulsar')]);
            exit;
        }

        $campaign = new Ticket();
        if (!$campaign->getFromDB($campaign_id)) {
            echo json_encode(['success' => false, 'message' => __('Campanha não encontrada', 'agilizepulsar')]);
            exit;
        }

        if ((int) $campaign->fields['itilcategories_id'] !== $campaign_category_id) {
            echo json_encode(['success' => false, 'message' => __('Ticket informado não é uma campanha válida', 'agilizepulsar')]);
            exit;
        }

        $existing_link = PluginAgilizepulsarTicket::getCampaignForIdea($idea_id);
        if (!empty($existing_link) && (int) $existing_link['campaign_id'] === $campaign_id) {
            echo json_encode([
                'success' => true,
                'message' => __('Ideia já vinculada a esta campanha.', 'agilizepulsar'),
                'campaign' => [
                    'id' => $campaign_id,
                    'name' => $campaign->fields['name'],
                    'deadline' => $existing_link['campaign_deadline']
                ]
            ]);
            exit;
        }

        if (!empty($existing_link)) {
            $DB->delete('glpi_items_tickets', ['id' => $existing_link['link_id']]);
        }

        $result = $DB->insert('glpi_items_tickets', [
            'items_id' => $campaign_id,
            'itemtype' => 'Ticket',
            'tickets_id' => $idea_id,
            'date_creation' => date('Y-m-d H:i:s')
        ]);

        if ($result) {
            PluginAgilizepulsarLog::add('idea_linked_campaign', $user_id, [
                'idea_id' => $idea_id,
                'campaign_id' => $campaign_id
            ]);

            echo json_encode([
                'success' => true,
                'message' => __('Ideia vinculada com sucesso!', 'agilizepulsar'),
                'campaign' => [
                    'id' => $campaign_id,
                    'name' => $campaign->fields['name'],
                    'deadline' => $campaign->fields['time_to_resolve'] ? Html::convDateTime($campaign->fields['time_to_resolve']) : null
                ]
            ]);
            exit;
        }

        http_response_code(500);
        echo json_encode(['success' => false, 'message' => __('Erro ao vincular ideia à campanha', 'agilizepulsar')]);
        exit;
    }

    if ($action === 'unlink') {
        $existing_link = PluginAgilizepulsarTicket::getCampaignForIdea($idea_id);
        if (empty($existing_link)) {
            echo json_encode(['success' => true, 'message' => __('Nenhuma campanha vinculada', 'agilizepulsar')]);
            exit;
        }

        $deleted = $DB->delete('glpi_items_tickets', ['id' => $existing_link['link_id']]);

        if ($deleted) {
            PluginAgilizepulsarLog::add('idea_unlinked_campaign', $user_id, [
                'idea_id' => $idea_id,
                'campaign_id' => $existing_link['campaign_id']
            ]);

            echo json_encode(['success' => true, 'message' => __('Ideia desvinculada com sucesso!', 'agilizepulsar')]);
            exit;
        }

        http_response_code(500);
        echo json_encode(['success' => false, 'message' => __('Erro ao desvincular ideia da campanha', 'agilizepulsar')]);
        exit;
    }

    if ($action === 'get_campaigns') {
        if ($campaign_category_id <= 0) {
            echo json_encode([
                'success' => false,
                'message' => __('Categoria de campanha não configurada.', 'agilizepulsar')
            ]);
            exit;
        }

        $where = [
            'glpi_tickets.itilcategories_id' => $campaign_category_id,
            'glpi_tickets.is_deleted' => 0,
            'glpi_tickets.is_template' => 0
        ];

        // Restringe às entidades visíveis para o usuário atual.
        if (class_exists('Entity')) {
            $restrict = Entity::getEntitiesRestrictCriteria('glpi_tickets', '', true);
            if (!empty($restrict)) {
                $where[] = $restrict;
            }
        }

        // Considera apenas campanhas ativas (tickets não resolvidos/fechados).
        $where['glpi_tickets.status'] = [
            Ticket::INCOMING,
            Ticket::ASSIGNED,
            Ticket::PLANNED,
            Ticket::WAITING
        ];

        $iterator = $DB->request([
            'SELECT' => ['id', 'name', 'time_to_resolve', 'status'],
            'FROM' => 'glpi_tickets',
            'WHERE' => $where,
            'ORDER' => 'date DESC'
        ]);

        $campaigns_payload = [];
        foreach ($iterator as $row) {
            $campaigns_payload[] = [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'deadline' => !empty($row['time_to_resolve']) ? Html::convDateTime($row['time_to_resolve']) : null,
                'status' => Ticket::getStatus($row['status'])
            ];
        }

        echo json_encode([
            'success' => true,
            'campaigns' => $campaigns_payload
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => __('Ação inválida', 'agilizepulsar')]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $exception->getMessage()
    ]);
}
