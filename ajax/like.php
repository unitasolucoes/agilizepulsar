<?php

define('GLPI_ROOT', dirname(__DIR__, 3));
include GLPI_ROOT . '/inc/includes.php';

// ✅ Limpar output buffer (igual ao add_comment.php)
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => __('Método não permitido', 'agilizepulsar')
    ]);
    exit;
}

if (!Session::getLoginUserID()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => __('Não autorizado', 'agilizepulsar')
    ]);
    exit;
}

// ✅ Validação CSRF opcional (igual ao add_comment.php)
if (isset($_POST['_glpi_csrf_token']) && !empty($_POST['_glpi_csrf_token'])) {
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
        echo json_encode([
            'success' => false,
            'message' => __('Token CSRF inválido', 'agilizepulsar')
        ]);
        exit;
    }
}

$action    = $_POST['action'] ?? '';
$ticket_id = (int) ($_POST['ticket_id'] ?? 0);
$user_id   = Session::getLoginUserID();
$user_profile = $_SESSION['glpiactiveprofile']['id'] ?? 0;

if (!PluginAgilizepulsarConfig::canLike($user_profile)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => __('Sem permissão para curtir', 'agilizepulsar')
    ]);
    exit;
}

if ($ticket_id <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => __('ID do ticket inválido', 'agilizepulsar')
    ]);
    exit;
}

if (!PluginAgilizepulsarTicket::isIdea($ticket_id)
    && !PluginAgilizepulsarTicket::isCampaign($ticket_id)) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => __('Ticket inválido para curtidas', 'agilizepulsar')
    ]);
    exit;
}

if ($action !== 'toggle') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => __('Ação inválida', 'agilizepulsar')
    ]);
    exit;
}

try {
    // ✅ Desabilitar mensagens de sessão (igual ao add_comment.php)
    $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
    
    $has_liked = PluginAgilizepulsarLike::userHasLiked($ticket_id, $user_id);
    $operation_success = false;

    if ($has_liked) {
        $operation_success = PluginAgilizepulsarLike::remove($ticket_id, $user_id);
        $liked = false;
        $message = $operation_success
            ? __('Curtida removida com sucesso', 'agilizepulsar')
            : __('Erro ao remover curtida', 'agilizepulsar');
    } else {
        $operation_success = PluginAgilizepulsarLike::addLike($ticket_id, $user_id);
        $liked = true;
        $message = $operation_success
            ? __('Curtida registrada com sucesso', 'agilizepulsar')
            : __('Erro ao registrar curtida', 'agilizepulsar');
    }
    
    // ✅ Limpar mensagens novamente
    $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];

    if (!$operation_success) {
        http_response_code(500);
    }

    echo json_encode([
        'success' => (bool) $operation_success,
        'liked'   => $operation_success ? $liked : PluginAgilizepulsarLike::userHasLiked($ticket_id, $user_id),
        'count'   => PluginAgilizepulsarLike::countByTicket($ticket_id),
        'message' => $message
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => __('Erro ao processar curtida', 'agilizepulsar') . ': ' . $e->getMessage()
    ]);
}

exit; // ✅ Garantir saída limpa