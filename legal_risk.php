<?php
// legal_risk.php
require_once __DIR__ . '/controllers/LegalRiskController.php';
$controller = new LegalRiskController();
$marca_id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$marca_id) {
    echo 'ID de marca no especificado.';
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'riesgo_oposicion' => $_POST['riesgo_oposicion'],
        'riesgo_nulidad' => $_POST['riesgo_nulidad'],
        'riesgo_infraccion' => $_POST['riesgo_infraccion'],
        'estrategias_defensivas' => $_POST['estrategias_defensivas']
    ];
    $controller->save($marca_id, $data);
} else {
    $controller->show($marca_id);
}
