<?php
// brand_form.php
require_once __DIR__ . '/controllers/BrandController.php';
$controller = new BrandController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nombre' => $_POST['nombre'],
        'rubro' => $_POST['rubro'],
        'ubicacion' => $_POST['ubicacion'],
        'estado' => $_POST['estado'],
        'usuario_id' => 1 // Ajustar según autenticación
    ];
    if (isset($_GET['id'])) {
        $controller->update($_GET['id'], $data);
    } else {
        $controller->create($data);
    }
} else {
    $brand = null;
    if (isset($_GET['id'])) {
        $brand = Brand::find((new Database())->getConnection(), $_GET['id']);
    }
    include __DIR__ . '/views/brand/brand_form.php';
}
