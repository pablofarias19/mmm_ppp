<?php
// business_model.php
$marca_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

try {
    require_once __DIR__ . '/controllers/BusinessModelController.php';
    $controller = new BusinessModelController();

    if (!$marca_id) {
        throw new \RuntimeException('Missing marca_id parameter');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_GET['delete'])) {
            $controller->delete($marca_id, (int)$_GET['delete']);
        } else {
            $data = [
                'tipo'        => $_POST['tipo'] ?? '',
                'descripcion' => $_POST['descripcion'] ?? '',
            ];
            $controller->create($marca_id, $data);
        }
    } else {
        $controller->index($marca_id);
    }
} catch (\Throwable $e) {
    error_log('[business_model] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    $brand_label = $marca_id ? "Marca #$marca_id (demo)" : 'Demo';
    $back_url    = $marca_id ? "/brand_detail?id=$marca_id" : '/marcas';

    $tool_title       = 'Modelos de Negocio';
    $tool_description = 'El módulo de Modelos de Negocio permite identificar y gestionar las distintas formas en que una marca puede generar valor económico. '
        . 'Desde la explotación directa hasta el licenciamiento, la franquicia o la creación de activos digitales, cada modelo se evalúa '
        . 'en función del potencial de crecimiento y los objetivos estratégicos del titular de la marca.';
    $tool_bullets = [
        'Registro y análisis de modelos de explotación directa.',
        'Licenciamiento de marca a terceros: condiciones y royalties.',
        'Estructuración de modelos de franquicia.',
        'Marca blanca y sublicencias: alcance y riesgos.',
        'Monetización como activo digital (NFT, plataformas online).',
        'Evaluación comparativa de modelos según mercado objetivo.',
    ];
    $tool_edit_note = 'Próximamente personalizable. Para editar el contenido de este panel, modificá el archivo business_model.php y views/brand/_tool_panel.php.';

    require __DIR__ . '/views/brand/_tool_panel.php';
}
