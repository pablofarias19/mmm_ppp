<?php
// brand_detail.php
require_once __DIR__ . '/controllers/BrandController.php';
$controller = new BrandController();
if (isset($_GET['id'])) {
    $controller->show($_GET['id']);
} else {
    echo 'ID de marca no especificado.';
}
