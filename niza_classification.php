<?php
// niza_classification.php
$marca_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

try {
    require_once __DIR__ . '/controllers/NizaClassificationController.php';
    $controller = new NizaClassificationController();

    if (!$marca_id) {
        throw new \RuntimeException('Missing marca_id parameter');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = [
            'clase_principal'        => $_POST['clase_principal'] ?? '',
            'clases_complementarias' => $_POST['clases_complementarias'] ?? '',
            'riesgo_colision'        => $_POST['riesgo_colision'] ?? '',
        ];
        $controller->save($marca_id, $data);
    } else {
        $controller->show($marca_id);
    }
} catch (\Throwable $e) {
    error_log('[niza_classification] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    // Show generic informational panel when DB is unavailable or record missing
    $brand_label = $marca_id ? "Marca #$marca_id (demo)" : 'Demo';
    $back_url    = $marca_id ? "/brand_detail?id=$marca_id" : '/marcas';

    $tool_title       = 'Clasificación Niza';
    $tool_description = 'La Clasificación Internacional de Niza organiza productos y servicios en 45 clases para el registro de marcas. '
        . 'Identificar correctamente la clase principal y las clases complementarias es fundamental para obtener una protección sólida y evitar conflictos con marcas existentes.';
    $tool_bullets = [
        'Determinación de la clase Niza principal para su marca.',
        'Identificación de clases complementarias estratégicas.',
        'Análisis del riesgo de colisión con marcas registradas en cada clase.',
        'Recomendaciones para ampliar la cobertura territorial e internacional.',
        'Informe de similitud con marcas competidoras dentro de la misma clase.',
    ];
    $tool_edit_note = 'Próximamente personalizable. Para editar el contenido de este panel, modificá el archivo niza_classification.php y views/brand/_tool_panel.php.';

    require __DIR__ . '/views/brand/_tool_panel.php';
}
