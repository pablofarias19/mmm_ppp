<?php
// brand_analysis.php
require_once __DIR__ . '/controllers/BrandAnalysisController.php';
$controller = new BrandAnalysisController();
$marca_id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$marca_id) {
    echo 'ID de marca no especificado.';
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'distintividad' => $_POST['distintividad'],
        'riesgo_confusion' => $_POST['riesgo_confusion'],
        'conflictos_clases' => $_POST['conflictos_clases'],
        'nivel_proteccion' => $_POST['nivel_proteccion'],
        'expansion_internacional' => $_POST['expansion_internacional']
    ];
    $controller->save($marca_id, $data);
} else {
    $controller->show($marca_id);
}
