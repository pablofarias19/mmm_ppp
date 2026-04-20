<?php
// brand_report.php
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/models/Brand.php';
require_once __DIR__ . '/models/BrandAnalysis.php';

$db = Database::getInstance()->getConnection();
$marca_id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$marca_id) {
    echo 'ID de marca no especificado.';
    exit;
}
$brand = Brand::find($db, $marca_id);
$analysis = BrandAnalysis::findByMarca($db, $marca_id);

// Diagnóstico ejecutivo narrativo
function generarResumen($brand, $analysis) {
    if (!$brand) return 'Marca no encontrada.';
    $res = "Diagnóstico ejecutivo para la marca '{$brand['nombre']}':\n";
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

// Output JSON
$output = [
    'marca' => $brand,
    'analisis_marcario' => $analysis
];

$resumen = generarResumen($brand, $analysis);

$as_json = isset($_GET['json']);
if ($as_json) {
    header('Content-Type: application/json');
    echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Ejecutivo de Marca</title>
    <link rel="stylesheet" href="/css/map-styles.css">
</head>
<body>
    <div style="max-width: 700px; margin: 0 auto; padding: 30px 0;">
        <h1>Reporte Ejecutivo de Marca</h1>
        <div style="background:#fff; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.04); padding:28px 32px; font-size:17px; color:#1a365d; margin-bottom:24px;">
            <pre style="background:none; border:none; font-family:inherit; color:inherit; font-size:inherit; margin:0; padding:0; white-space:pre-line; line-height:1.6;">
<?= htmlspecialchars($resumen) ?>
            </pre>
        </div>
        <div style="text-align:center; margin-top:18px;">
            <a href="brand_report.php?id=<?= $marca_id ?>&json=1" target="_blank" style="background:#2563eb; color:#fff; padding:8px 18px; border-radius:5px; font-size:15px; font-weight:500; margin-right:12px;">Ver JSON</a>
            <a href="brand_detail.php?id=<?= $marca_id ?>" style="color:#e53e3e;">Volver al detalle</a>
        </div>
    </div>
</body>
</html>
