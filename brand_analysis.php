<?php
// brand_analysis.php
$marca_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

try {
    require_once __DIR__ . '/controllers/BrandAnalysisController.php';
    $controller = new BrandAnalysisController();

    if (!$marca_id) {
        throw new \RuntimeException('no_id');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = [
            'distintividad'           => $_POST['distintividad'] ?? '',
            'riesgo_confusion'        => $_POST['riesgo_confusion'] ?? '',
            'conflictos_clases'       => $_POST['conflictos_clases'] ?? '',
            'nivel_proteccion'        => $_POST['nivel_proteccion'] ?? '',
            'expansion_internacional' => $_POST['expansion_internacional'] ?? '',
        ];
        $controller->save($marca_id, $data);
    } else {
        $controller->show($marca_id);
    }
} catch (\Throwable $e) {
    $brand_label = $marca_id ? "Marca #$marca_id (demo)" : 'Demo';
    $back_url    = $marca_id ? "/brand_detail?id=$marca_id" : '/marcas';

    $tool_title       = 'Análisis Marcario';
    $tool_description = 'El Análisis Marcario evalúa la fortaleza y viabilidad de su marca desde una perspectiva estratégica y legal. '
        . 'Se estudia la distintividad del signo, los riesgos de confusión con marcas preexistentes, los conflictos potenciales en clases Niza '
        . 'y el nivel de protección alcanzable tanto en el mercado local como en el internacional.';
    $tool_bullets = [
        'Evaluación del grado de distintividad del signo marcario.',
        'Análisis del riesgo de confusión con marcas registradas.',
        'Detección de conflictos en clases Niza relevantes.',
        'Determinación del nivel de protección alcanzable.',
        'Posibilidades de expansión internacional de la marca.',
        'Informe ejecutivo con recomendaciones accionables.',
    ];
    $tool_edit_note = 'Próximamente personalizable. Para editar el contenido de este panel, modificá el archivo brand_analysis.php y views/brand/_tool_panel.php.';

    require __DIR__ . '/views/brand/_tool_panel.php';
}
