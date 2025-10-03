<?php

include('../../../../inc/includes.php');

header('Content-Type: application/json');

Session::checkLoginUser();

if (!isset($_POST['action']) || !isset($_POST['ticket_id'])) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
    exit;
}

$action = $_POST['action'];
$ticket_id = (int)$_POST['ticket_id'];
$user_id = Session::getLoginUserID();

if (!PluginAgilizepulsarTicket::isIdea($ticket_id)) {
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
    
    $result = PluginAgilizepulsarComment::addComment($ticket_id, $user_id, $content);
    
    if ($result) {
        $user = new User();
        $user->getFromDB($user_id);
        
        $response = [
            'success' => true,
            'comment' => [
                'id' => $result,
                'content' => nl2br(htmlspecialchars($content)),
                'user_name' => $user->getFriendlyName(),
                'user_initials' => strtoupper(substr($user->fields['realname'], 0, 2)),
                'date' => Html::convDateTime($_SESSION['glpi_currenttime'])
            ]
        ];
    }
}

echo json_encode($response);