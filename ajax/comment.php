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

if ($action === 'add' && isset($_POST['content'])) {
    $content = trim($_POST['content']);

    if (empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Comentário vazio']);
        exit;
    }

    if (strlen($content) > 5000) {
        echo json_encode(['success' => false, 'message' => 'Comentário muito longo (máximo 5000 caracteres)']);
        exit;
    }

    $content = strip_tags($content, '<p><br><b><i><u><strong><em><ul><ol><li><a>');

    $result = PluginAgilizepulsarComment::addComment($ticket_id, $user_id, $content);

    if ($result) {
        $user = new User();
        $user->getFromDB($user_id);

        $initial_source = trim($user->fields['realname'] ?? '') ?: trim($user->fields['firstname'] ?? '');
        if ($initial_source === '') {
            $initial_source = trim($user->fields['name'] ?? '');
        }
        $user_initials = strtoupper(substr($initial_source, 0, 2));
        if ($user_initials === '') {
            $user_initials = '??';
        }

        $response = [
            'success' => true,
            'comment' => [
                'id' => $result,
                'content' => nl2br(htmlspecialchars($content)),
                'user_name' => $user->getFriendlyName(),
                'user_initials' => $user_initials,
                'date' => Html::convDateTime($_SESSION['glpi_currenttime'])
            ]
        ];
    }
}

echo json_encode($response);
