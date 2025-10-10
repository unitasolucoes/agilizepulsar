<?php
// Teste ULTRA simples - só retornar JSON

header('Content-Type: application/json; charset=UTF-8');

echo json_encode([
    'success' => true,
    'message' => 'TESTE FUNCIONOU!',
    'liked' => true,
    'count' => 999
]);

exit;