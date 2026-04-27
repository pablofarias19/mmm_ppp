<?php
// brand_report.php
$marca_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$format   = isset($_GET['format']) ? strtolower(trim($_GET['format'])) : '';

try {
    require_once __DIR__ . '/core/Database.php';
    require_once __DIR__ . '/models/Brand.php';
    require_once __DIR__ . '/models/BrandAnalysis.php';

    if (!$marca_id) {
        // Respond with a clear error instead of silently falling through
        if ($format === 'txt') {
            header('Content-Type: text/plain; charset=utf-8');
            http_response_code(400);
            echo "Error: el parámetro 'id' es obligatorio.";
            exit;
        }
        if ($format === 'pdf') {
            header('Content-Type: text/plain; charset=utf-8');
            http_response_code(400);
            echo "Error: el parámetro 'id' es obligatorio.";
            exit;
        }
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

    // ── JSON export ──────────────────────────────────────────────────────────
    if (isset($_GET['json'])) {
        header('Content-Type: application/json');
        echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── TXT export ───────────────────────────────────────────────────────────
    if ($format === 'txt') {
        $nombreSeguro = $brand ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $brand['nombre']) : 'marca';
        $filename     = 'reporte_' . $nombreSeguro . '_' . $marca_id . '.txt';
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache');

        $linea = str_repeat('=', 60);
        echo "REPORTE EJECUTIVO DE MARCA — Mapita\n";
        echo $linea . "\n";
        if ($brand) {
            echo "Marca    : " . $brand['nombre'] . "\n";
            echo "Rubro    : " . ($brand['rubro'] ?? '-') . "\n";
            echo "Ubicación: " . ($brand['ubicacion'] ?? '-') . "\n";
            echo "Estado   : " . ($brand['estado'] ?? '-') . "\n";
        }
        echo $linea . "\n\n";
        echo $resumen . "\n\n";
        if ($analysis) {
            echo str_repeat('-', 40) . "\n";
            echo "ANÁLISIS MARCARIO\n";
            echo str_repeat('-', 40) . "\n";
            echo "Distintividad          : " . ($analysis['distintividad'] ?? '-') . "\n";
            echo "Riesgo de confusión    : " . ($analysis['riesgo_confusion'] ?? '-') . "\n";
            echo "Conflictos clases Niza : " . ($analysis['conflictos_clases'] ?? '-') . "\n";
            echo "Nivel de protección    : " . ($analysis['nivel_proteccion'] ?? '-') . "\n";
            echo "Expansión internacional: " . ($analysis['expansion_internacional'] ?? '-') . "\n";
        }
        echo "\n" . $linea . "\n";
        echo "Generado: " . date('Y-m-d H:i:s') . " UTC\n";
        echo "Este reporte es orientativo y no sustituye asesoramiento profesional.\n";
        echo "Consultas: https://fariasortiz.com.ar/marcas.html\n";
        exit;
    }

    // ── PDF export ───────────────────────────────────────────────────────────
    if ($format === 'pdf') {
        $autoload = __DIR__ . '/vendor/autoload.php';
        if (!file_exists($autoload)) {
            header('Content-Type: text/plain; charset=utf-8');
            http_response_code(503);
            echo "Error: las dependencias de Composer no están instaladas.\n";
            echo "Ejecutá 'composer install' en el directorio raíz del proyecto.\n";
            exit;
        }
        require_once $autoload;

        $nombreSeguro = $brand ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $brand['nombre']) : 'marca';
        $filename     = 'reporte_' . $nombreSeguro . '_' . $marca_id . '.pdf';

        $resumenHtml = nl2br(htmlspecialchars($resumen));

        $html  = '<!DOCTYPE html><html lang="es"><head>';
        $html .= '<meta charset="UTF-8">';
        $html .= '<style>';
        $html .= 'body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a365d; margin: 30px; }';
        $html .= 'h1   { font-size: 20px; color: #1B3B6F; border-bottom: 2px solid #1B3B6F; padding-bottom: 6px; margin-bottom: 16px; }';
        $html .= 'h2   { font-size: 14px; color: #2d3748; margin-top: 20px; margin-bottom: 8px; }';
        $html .= '.meta { font-size: 11px; color: #718096; margin-bottom: 20px; }';
        $html .= '.campo { margin-bottom: 6px; }';
        $html .= '.label { font-weight: bold; color: #4a5568; }';
        $html .= '.resumen { background: #f7fafc; border-left: 4px solid #1B3B6F; padding: 12px 16px; margin: 16px 0; line-height: 1.7; }';
        $html .= '.footer { margin-top: 30px; font-size: 10px; color: #a0aec0; border-top: 1px solid #e2e8f0; padding-top: 10px; }';
        $html .= '.advertencia { background: #fffbeb; border: 1px solid #f6e05e; padding: 10px 14px; border-radius: 4px; font-size: 11px; color: #744210; margin-bottom: 16px; }';
        $html .= '</style></head><body>';

        $html .= '<h1>Reporte Ejecutivo de Marca</h1>';
        $html .= '<div class="meta">Generado: ' . htmlspecialchars(date('Y-m-d H:i:s')) . ' UTC &nbsp;·&nbsp; Mapita</div>';

        if ($brand) {
            $html .= '<h2>Datos de la Marca</h2>';
            $html .= '<div class="campo"><span class="label">Nombre:</span> ' . htmlspecialchars($brand['nombre']) . '</div>';
            $html .= '<div class="campo"><span class="label">Rubro:</span> ' . htmlspecialchars($brand['rubro'] ?? '-') . '</div>';
            $html .= '<div class="campo"><span class="label">Ubicación:</span> ' . htmlspecialchars($brand['ubicacion'] ?? '-') . '</div>';
            $html .= '<div class="campo"><span class="label">Estado:</span> ' . htmlspecialchars($brand['estado'] ?? '-') . '</div>';
        }

        $html .= '<h2>Diagnóstico Ejecutivo</h2>';
        $html .= '<div class="resumen">' . $resumenHtml . '</div>';

        if ($analysis) {
            $html .= '<h2>Análisis Marcario</h2>';
            $fields = [
                'Distintividad'           => 'distintividad',
                'Riesgo de confusión'     => 'riesgo_confusion',
                'Conflictos clases Niza'  => 'conflictos_clases',
                'Nivel de protección'     => 'nivel_proteccion',
                'Expansión internacional' => 'expansion_internacional',
            ];
            foreach ($fields as $label => $key) {
                $val = $analysis[$key] ?? '-';
                $html .= '<div class="campo"><span class="label">' . htmlspecialchars($label) . ':</span> ' . htmlspecialchars($val) . '</div>';
            }
        }

        $html .= '<div class="advertencia">⚠️ Este reporte es de carácter orientativo y no sustituye el asesoramiento profesional. '
               . 'Para un análisis pormenorizado consultá con Estudio Farias Ortiz — fariasortiz.com.ar/marcas.html</div>';
        $html .= '<div class="footer">Reporte generado por Mapita · ' . htmlspecialchars(date('Y')) . '</div>';
        $html .= '</body></html>';

        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', false);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache');
        echo $dompdf->output();
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
            <a class="tp-nav-detail" href="/brand_report?id=<?= (int)$marca_id ?>&amp;format=pdf" style="background:#e53e3e;color:#fff;padding:8px 16px;border-radius:6px;text-decoration:none;font-weight:600;">⬇ Descargar PDF</a>
            <a class="tp-nav-detail" href="/brand_report?id=<?= (int)$marca_id ?>&amp;format=txt" style="background:#2b6cb0;color:#fff;padding:8px 16px;border-radius:6px;text-decoration:none;font-weight:600;">⬇ Descargar TXT</a>
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
        'Exportación en formatos PDF y TXT.',
    ];
    $tool_edit_note = 'Próximamente personalizable. Para editar el contenido de este panel, modificá el archivo brand_report.php y views/brand/_tool_panel.php.';

    require __DIR__ . '/views/brand/_tool_panel.php';
}
