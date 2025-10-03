<?php

include('../../../../inc/includes.php');

header('Content-Type: application/json');

Session::checkLoginUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && method_exists('Session', 'checkCSRF')) {
    if (!isset($_POST['_glpi_csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Token inválido']);
        exit;
    }

    Session::checkCSRF($_POST);
}

$user_profile = $_SESSION['glpiactiveprofile']['id'] ?? 0;
if (!PluginAgilizepulsarConfig::canLike($user_profile)) {
    echo json_encode(['success' => false, 'message' => 'Sem permissão']);
    exit;
}

if (!isset($_POST['action']) || !isset($_POST['ticket_id'])) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
    exit;
}

$action = $_POST['action'];
$ticket_id = (int)$_POST['ticket_id'];
$user_id = Session::getLoginUserID();

if (!PluginAgilizepulsarTicket::isIdea($ticket_id)
    && !PluginAgilizepulsarTicket::isCampaign($ticket_id)) {
    echo json_encode(['success' => false, 'message' => 'Ticket inválido']);
    exit;
}

$response = ['success' => false];

if ($action === 'toggle') {
    if (PluginAgilizepulsarLike::userHasLiked($ticket_id, $user_id)) {
        $result = PluginAgilizepulsarLike::remove($ticket_id, $user_id);
        if ($result) {
            $response = [
                'success' => true,
                'liked' => false,
                'count' => PluginAgilizepulsarLike::countByTicket($ticket_id)
            ];
        }
    } else {
        $result = PluginAgilizepulsarLike::addLike($ticket_id, $user_id); // MUDANÇA AQUI
        if ($result) {
            $response = [
                'success' => true,
                'liked' => true,
                'count' => PluginAgilizepulsarLike::countByTicket($ticket_id)
            ];
        }
    }
}

echo json_encode($response);
