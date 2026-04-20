<?php
// niza_classification.php
require_once __DIR__ . '/controllers/NizaClassificationController.php';
$controller = new NizaClassificationController();
$marca_id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$marca_id) {
    echo 'ID de marca no especificado.';
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'clase_principal' => $_POST['clase_principal'],
        'clases_complementarias' => $_POST['clases_complementarias'],
        'riesgo_colision' => $_POST['riesgo_colision']
    ];
    $controller->save($marca_id, $data);
} else {
    $controller->show($marca_id);
}
