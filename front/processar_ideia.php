<?php

include('../../../inc/includes.php');
require_once __DIR__ . '/../inc/ideia.creator.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    Session::checkLoginUser();

    $profile_id = $_SESSION['glpiactiveprofile']['id'] ?? 0;
    if (!PluginAgilizepulsarConfig::canView($profile_id)) {
        throw new RuntimeException(__('Você não tem permissão para criar ideias.', 'agilizepulsar'));
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException(__('Método não suportado.', 'agilizepulsar'));
    }

    if (method_exists('Session', 'checkCSRF')) {
        Session::checkCSRF($_POST);
    }

    $dados = [
        'titulo'               => trim($_POST['titulo'] ?? ''),
        'campanha_id'          => (int) ($_POST['campanha_id'] ?? 0),
        'area_impactada'       => trim($_POST['area_impactada'] ?? ''),
        'descricao'            => $_POST['descricao'] ?? '',
        'beneficios'           => $_POST['beneficios'] ?? '',
        'implementacao'        => trim($_POST['implementacao'] ?? ''),
        'ideia_existente'      => trim($_POST['ideia_existente'] ?? ''),
        'objetivo_estrategico' => trim($_POST['objetivo_estrategico'] ?? ''),
        'classificacao'        => trim($_POST['classificacao'] ?? '')
    ];

    $resultado = PluginAgilizepulsarIdeiaCreator::createIdeiaTicket($dados, $_FILES['anexos'] ?? []);

    echo json_encode($resultado);
} catch (Throwable $exception) {
    error_log('Plugin Agilizepulsar - Processar ideia: ' . $exception->getMessage());

    echo json_encode([
        'success' => false,
        'message' => $exception->getMessage()
    ]);
}
