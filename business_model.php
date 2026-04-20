<?php
// business_model.php
require_once __DIR__ . '/controllers/BusinessModelController.php';
$controller = new BusinessModelController();
$marca_id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$marca_id) {
    echo 'ID de marca no especificado.';
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_GET['delete'])) {
        $controller->delete($marca_id, $_GET['delete']);
    } else {
        $data = [
            'tipo' => $_POST['tipo'],
            'descripcion' => $_POST['descripcion']
        ];
        $controller->create($marca_id, $data);
    }
} else {
    $controller->index($marca_id);
}
