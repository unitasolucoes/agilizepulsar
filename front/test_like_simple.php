<?php

define('GLPI_ROOT', dirname(__DIR__, 3));
include GLPI_ROOT . '/inc/includes.php';

// Limpar tudo
while (ob_get_level()) {
    ob_end_clean();
}

// Iniciar novo buffer
ob_start();

header('Content-Type: application/json; charset=UTF-8');

try {
    // LOG 1
    error_log('=== INICIO DO LIKE ===');
    
    // Verificar se está logado
    if (!Session::getLoginUserID()) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Não logado', 'step' => 1]);
        exit;
    }
    
    error_log('LOG 2: Usuário logado: ' . Session::getLoginUserID());

    // Pegar dados
    $action = $_POST['action'] ?? '';
    $ticket_id = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
    $user_id = Session::getLoginUserID();
    
    error_log('LOG 3: action=' . $action . ', ticket_id=' . $ticket_id);

    // Validações básicas
    if ($ticket_id <= 0) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Ticket inválido', 'step' => 2]);
        exit;
    }

    if ($action !== 'toggle') {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Ação inválida', 'step' => 3]);
        exit;
    }
    
    error_log('LOG 4: Validações OK');

    // Desabilitar mensagens de sessão
    $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
    
    error_log('LOG 5: Verificando se classe existe');
    
    // Verificar se a classe existe
    if (!class_exists('PluginAgilizepulsarLike')) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Classe PluginAgilizepulsarLike não existe', 'step' => 4]);
        exit;
    }
    
    error_log('LOG 6: Classe existe, chamando userHasLiked');
    
    // Verificar se já curtiu
    $has_liked = PluginAgilizepulsarLike::userHasLiked($ticket_id, $user_id);
    
    error_log('LOG 7: has_liked=' . ($has_liked ? 'true' : 'false'));
    
    $operation_success = false;
    $liked = false;
    $message = '';

    if ($has_liked) {
        error_log('LOG 8: Removendo curtida');
        $operation_success = PluginAgilizepulsarLike::remove($ticket_id, $user_id);
        $liked = false;
        $message = $operation_success ? 'Curtida removida' : 'Erro ao remover';
    } else {
        error_log('LOG 9: Adicionando curtida');
        $operation_success = PluginAgilizepulsarLike::addLike($ticket_id, $user_id);
        $liked = true;
        $message = $operation_success ? 'Curtida adicionada' : 'Erro ao adicionar';
    }
    
    error_log('LOG 10: operation_success=' . ($operation_success ? 'true' : 'false'));
    
    // Limpar mensagens novamente
    $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
    
    error_log('LOG 11: Contando curtidas');
    
    // Contar curtidas
    $count = PluginAgilizepulsarLike::countByTicket($ticket_id);
    
    error_log('LOG 12: count=' . $count);
    
    // Limpar buffer e retornar
    ob_clean();
    
    $result = [
        'success' => (bool) $operation_success,
        'liked' => $operation_success ? $liked : PluginAgilizepulsarLike::userHasLiked($ticket_id, $user_id),
        'count' => $count,
        'message' => $message,
        'debug' => 'OK'
    ];
    
    error_log('LOG 13: Retornando JSON: ' . json_encode($result));
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('LOG ERROR: ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'step' => 'exception'
    ]);
}

ob_end_flush();
exit;