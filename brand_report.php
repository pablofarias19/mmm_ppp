<?php
// brand_report.php
$marca_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

try {
    require_once __DIR__ . '/core/Database.php';
    require_once __DIR__ . '/models/Brand.php';
    require_once __DIR__ . '/models/BrandAnalysis.php';

    if (!$marca_id) {
        throw new \RuntimeException('Missing marca_id parameter');
    }

    $db       = \Core\Database::getInstance()->getConnection();
    $brand    = Brand::find($db, $marca_id);
    $analysis = BrandAnalysis::findByMarca($db, $marca_id);

    // Diagnóstico ejecutivo narrativo
    function generarResumen($brand, $analysis) {
        if (!$brand) return 'Marca no encontrada.';
        $res  = "Diagnóstico ejecutivo para la marca '{$brand['nombre']}':\n";
        $res .= "Rubro: {$brand['rubro']}, Ubicación: {$brand['ubicacion']}, Estado: {$brand['estado']}.\n";
        if ($analysis) {
            $res .= "Distintividad: {$analysis['distintividad']}. Riesgo de confusión: {$analysis['riesgo_confusion']}. ";
            $res .= "Conflictos en clases Niza: {$analysis['conflictos_clases']}. Nivel de protección: {$analysis['nivel_proteccion']}. ";
            $res .= "Expansión internacional: {$analysis['expansion_internacional']}.";
        } else {
            $res .= "No hay análisis marcario registrado.";
        }
        return $res;
    }

    $output  = ['marca' => $brand, 'analisis_marcario' => $analysis];
    $resumen = generarResumen($brand, $analysis);

    if (isset($_GET['json'])) {
        header('Content-Type: application/json');
        echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Ejecutivo de Marca — Mapita</title>
    <link rel="stylesheet" href="/css/map-styles.css">
    <link rel="stylesheet" href="/css/tool-panels.css">
</head>
<body>
<div class="tp-wrap">
    <div class="tp-header">
        <h1>Reporte Ejecutivo de Marca</h1>
        <span class="tp-header-meta">
            Bajo consulta profesional &nbsp;·&nbsp;
            <a href="https://www.fariasortiz.com.ar/marcas.html" target="_blank" rel="noopener noreferrer"
               style="color:#93c5fd; text-decoration:underline;">fariasortiz.com.ar</a>
        </span>
    </div>
    <div class="tp-card">
        <?php if ($brand): ?>
        <span class="tp-brand-badge">📌 <?= htmlspecialchars($brand['nombre']) ?></span>
        <?php endif; ?>
        <div class="tp-notice">
            <strong>⚠️ Servicio bajo consulta profesional</strong>
            Este reporte es de carácter orientativo y no sustituye el asesoramiento profesional.
            Para un <strong>análisis pormenorizado</strong> de su marca le recomendamos consultar con
            <a href="https://fariasortiz.com.ar/marcas.html" target="_blank" rel="noopener noreferrer"
               style="color:#92400e; font-weight:600;">Estudio Farias Ortiz — Asesoría de Marcas</a>.
        </div>
        <div style="background:#fff; border-radius:8px; border:1px solid #e2e8f0; padding:22px 26px; font-size:16px; color:#1a365d; margin-bottom:24px;">
            <pre style="background:none; border:none; font-family:inherit; color:inherit; font-size:inherit; margin:0; padding:0; white-space:pre-line; line-height:1.7;"><?= htmlspecialchars($resumen) ?></pre>
        </div>
        <nav class="tp-nav">
            <a class="tp-nav-detail" href="/brand_report?id=<?= (int)$marca_id ?>&amp;json=1" target="_blank">Ver JSON</a>
            <?php if ($marca_id): ?>
            <a class="tp-nav-back" href="/brand_detail?id=<?= $marca_id ?>">← Volver al detalle</a>
            <?php endif; ?>
            <a class="tp-nav-back" href="/marcas">Listado de marcas</a>
        </nav>
    </div>
</div>
</body>
</html>
    <?php
} catch (\Throwable $e) {
    error_log('[brand_report] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    $brand_label = $marca_id ? "Marca #$marca_id (demo)" : 'Demo';
    $back_url    = $marca_id ? "/brand_detail?id=$marca_id" : '/marcas';

    $tool_title       = 'Reporte Ejecutivo de Marca';
    $tool_description = 'El Reporte Ejecutivo consolida en un único documento todos los análisis disponibles para la marca: '
        . 'análisis marcario, clasificación Niza, modelos de negocio, estrategias de monetización y evaluación de riesgos legales. '
        . 'Está diseñado para ser presentado ante inversores, socios estratégicos o para uso interno de la organización.';
    $tool_bullets = [
        'Resumen ejecutivo de la situación marcaria actual.',
        'Análisis FODA de la marca (fortalezas, oportunidades, debilidades, amenazas).',
        'Consolidación de datos de clasificación Niza y análisis marcario.',
        'Síntesis de modelos de negocio y estrategias de monetización.',
        'Evaluación integrada de riesgos legales y recomendaciones.',
        'Exportación en formatos PDF y JSON para uso externo.',
    ];
    $tool_edit_note = 'Próximamente personalizable. Para editar el contenido de este panel, modificá el archivo brand_report.php y views/brand/_tool_panel.php.';

    require __DIR__ . '/views/brand/_tool_panel.php';
}
