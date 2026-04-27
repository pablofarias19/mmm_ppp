<?php
// monetization.php
$marca_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

try {
    require_once __DIR__ . '/controllers/MonetizationController.php';
    $controller = new MonetizationController();

    if (!$marca_id) {
        throw new \RuntimeException('no_id');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = [
            'fuentes_ingresos' => $_POST['fuentes_ingresos'] ?? '',
            'escalabilidad'    => $_POST['escalabilidad'] ?? '',
            'margen_potencial' => $_POST['margen_potencial'] ?? '',
            'valor_activo'     => $_POST['valor_activo'] ?? '',
        ];
        $controller->save($marca_id, $data);
    } else {
        $controller->show($marca_id);
    }
} catch (\Throwable $e) {
    $brand_label = $marca_id ? "Marca #$marca_id (demo)" : 'Demo';
    $back_url    = $marca_id ? "/brand_detail?id=$marca_id" : '/marcas';

    $tool_title       = 'Monetización';
    $tool_description = 'El módulo de Monetización analiza el potencial económico de la marca como activo intangible. '
        . 'Se identifican las fuentes de ingresos actuales y futuras, se evalúa la escalabilidad del modelo, '
        . 'el margen potencial y el valor de la marca en el mercado, brindando herramientas para maximizar el retorno de la inversión marcaria.';
    $tool_bullets = [
        'Identificación y diversificación de fuentes de ingresos de la marca.',
        'Análisis de escalabilidad del modelo de negocio.',
        'Estimación del margen potencial por canal de monetización.',
        'Valoración de la marca como activo intangible (goodwill).',
        'Estrategias de pricing y posicionamiento para maximizar ingresos.',
        'Proyección financiera a mediano y largo plazo.',
    ];
    $tool_edit_note = 'Próximamente personalizable. Para editar el contenido de este panel, modificá el archivo monetization.php y views/brand/_tool_panel.php.';

    require __DIR__ . '/views/brand/_tool_panel.php';
}
