<?php
// legal_risk.php
$marca_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

try {
    require_once __DIR__ . '/controllers/LegalRiskController.php';
    $controller = new LegalRiskController();

    if (!$marca_id) {
        throw new \RuntimeException('Missing marca_id parameter');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = [
            'riesgo_oposicion'       => $_POST['riesgo_oposicion'] ?? '',
            'riesgo_nulidad'         => $_POST['riesgo_nulidad'] ?? '',
            'riesgo_infraccion'      => $_POST['riesgo_infraccion'] ?? '',
            'estrategias_defensivas' => $_POST['estrategias_defensivas'] ?? '',
        ];
        $controller->save($marca_id, $data);
    } else {
        $controller->show($marca_id);
    }
} catch (\Throwable $e) {
    $brand_label = $marca_id ? "Marca #$marca_id (demo)" : 'Demo';
    $back_url    = $marca_id ? "/brand_detail?id=$marca_id" : '/marcas';

    $tool_title       = 'Riesgo Legal';
    $tool_description = 'El módulo de Riesgo Legal evalúa las amenazas jurídicas que pueden afectar la vigencia y el valor de su marca. '
        . 'Se analizan los riesgos de oposición durante el proceso de registro, la posibilidad de nulidad por vicios formales o de fondo, '
        . 'los riesgos de infracción por terceros y se diseñan estrategias defensivas para proteger el signo marcario.';
    $tool_bullets = [
        'Evaluación del riesgo de oposición al registro por marcas previas.',
        'Análisis del riesgo de nulidad (absoluta y relativa).',
        'Identificación de riesgos de infracción y uso no autorizado.',
        'Diseño de estrategias defensivas y plan de vigilancia marcaria.',
        'Monitoreo de publicaciones oficiales en boletines de marcas.',
        'Asesoría ante acciones legales de terceros.',
    ];
    $tool_edit_note = 'Próximamente personalizable. Para editar el contenido de este panel, modificá el archivo legal_risk.php y views/brand/_tool_panel.php.';

    require __DIR__ . '/views/brand/_tool_panel.php';
}
