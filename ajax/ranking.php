<?php

include('../../../../inc/includes.php');

header('Content-Type: application/json');

Session::checkLoginUser();

$period = isset($_GET['period']) ? $_GET['period'] : 'total';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

$valid_periods = ['total', 'month', 'year'];
if (!in_array($period, $valid_periods)) {
    $period = 'total';
}

$ranking = PluginAgilizepulsarUserPoints::getRanking($period, $limit);

echo json_encode([
    'success' => true,
    'period' => $period,
    'ranking' => $ranking
]);