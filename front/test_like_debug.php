<?php

define('GLPI_ROOT', dirname(__DIR__, 3));
include GLPI_ROOT . '/inc/includes.php';

while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json; charset=UTF-8');

try {
    global $DB;
    
    $ticket_id = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
    $user_id = Session::getLoginUserID();
    
    if ($ticket_id <= 0 || !$user_id) {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
        exit;
    }
    
    // Desabilitar mensagens
    $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
    
    // Verificar se já curtiu
    $check = $DB->request([
        'FROM' => 'glpi_plugin_agilizepulsar_likes',
        'WHERE' => [
            'tickets_id' => $ticket_id,
            'users_id' => $user_id
        ]
    ]);
    
    $has_liked = count($check) > 0;
    
    if ($has_liked) {
        // Remover curtida DIRETO no banco
        $deleted = $DB->delete('glpi_plugin_agilizepulsar_likes', [
            'tickets_id' => $ticket_id,
            'users_id' => $user_id
        ]);
        
        $success = (bool) $deleted;
        $liked = false;
        $message = $success ? 'Curtida removida (DIRETO)' : 'Erro ao remover (DIRETO)';
        
    } else {
        // Adicionar curtida DIRETO no banco
        $inserted = $DB->insert('glpi_plugin_agilizepulsar_likes', [
            'tickets_id' => $ticket_id,
            'users_id' => $user_id,
            'date_creation' => $_SESSION['glpi_currenttime']
        ]);
        
        $success = (bool) $inserted;
        $liked = true;
        $message = $success ? 'Curtida adicionada (DIRETO)' : 'Erro ao adicionar (DIRETO)';
    }
    
    // Contar DIRETO
    $count_result = $DB->request([
        'COUNT' => 'id',
        'FROM' => 'glpi_plugin_agilizepulsar_likes',
        'WHERE' => ['tickets_id' => $ticket_id]
    ])->current();
    
    $count = (int) ($count_result['COUNT id'] ?? 0);
    
    // Limpar mensagens
    $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
    
    echo json_encode([
        'success' => $success,
        'liked' => $liked,
        'count' => $count,
        'message' => $message,
        'method' => 'DIRETO NO BANCO'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Exception: ' . $e->getMessage()
    ]);
}

exit;