<?php

include('../../../inc/includes.php');
require_once __DIR__ . '/../inc/campanha.creator.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    Session::checkLoginUser();

    $profile_id = $_SESSION['glpiactiveprofile']['id'] ?? 0;
    if (!PluginAgilizepulsarConfig::canAdmin($profile_id)) {
        throw new RuntimeException(__('Você não tem permissão para criar campanhas.', 'agilizepulsar'));
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException(__('Método não suportado.', 'agilizepulsar'));
    }

    if (method_exists('Session', 'checkCSRF')) {
        Session::checkCSRF();
    }

    $dados = [
        'titulo'           => trim($_POST['titulo'] ?? ''),
        'campanha_pai_id'  => (int) ($_POST['campanha_pai_id'] ?? 0),
        'descricao'        => $_POST['descricao'] ?? '',
        'publico_alvo'     => isset($_POST['publico_alvo']) ? (array) $_POST['publico_alvo'] : [],
        'beneficios'       => $_POST['beneficios'] ?? '',
        'canais'           => isset($_POST['canais']) ? (array) $_POST['canais'] : [],
        'areas_impactadas' => isset($_POST['areas_impactadas']) ? (array) $_POST['areas_impactadas'] : [],
        'prazo_estimado'   => trim($_POST['prazo_estimado'] ?? '')
    ];

    $resultado = PluginAgilizepulsarCampanhaCreator::createCampanhaTicket($dados, $_FILES['anexos'] ?? []);

    echo json_encode($resultado);
} catch (Throwable $exception) {
    error_log('Plugin Agilizepulsar - Processar campanha: ' . $exception->getMessage());

    echo json_encode([
        'success' => false,
        'message' => $exception->getMessage()
    ]);
}
