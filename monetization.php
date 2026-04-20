<?php
// monetization.php
require_once __DIR__ . '/controllers/MonetizationController.php';
$controller = new MonetizationController();
$marca_id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$marca_id) {
    echo 'ID de marca no especificado.';
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'fuentes_ingresos' => $_POST['fuentes_ingresos'],
        'escalabilidad' => $_POST['escalabilidad'],
        'margen_potencial' => $_POST['margen_potencial'],
        'valor_activo' => $_POST['valor_activo']
    ];
    $controller->save($marca_id, $data);
} else {
    $controller->show($marca_id);
}
