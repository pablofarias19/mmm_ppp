<?php
// views/brand/brand_detail.php
// Detalle de una marca

// ── Open Graph ────────────────────────────────────────────────────────────────
if (!empty($brand)) {
    $og_title       = $brand['nombre'] . ' — Marca en Mapita';
    $og_description = 'Conocé la marca ' . $brand['nombre']
        . ($brand['rubro'] ? ' · ' . $brand['rubro'] : '')
        . ($brand['ubicacion'] ? ' · ' . $brand['ubicacion'] : '')
        . '. Disponible en el mapa de Mapita.';
} else {
    $og_title       = 'MAPITA - Mapa de Marcas y Negocios';
    $og_description = 'Descubrí marcas y negocios cerca tuyo.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($og_title); ?></title>
    <link rel="stylesheet" href="/css/map-styles.css">
    <?php require_once __DIR__ . '/../../includes/meta_og.php'; ?>
</head>
<body>
    <div style="max-width: 600px; margin: 0 auto; padding: 30px 0;">
        <h1>Detalle de Marca</h1>
        <?php if ($brand): ?>
            <ul class="brand-detail-list">
                <li><strong>ID:</strong> <?= htmlspecialchars($brand['id']) ?></li>
                <li><strong>Nombre:</strong> <?= htmlspecialchars($brand['nombre']) ?></li>
                <li><strong>Rubro:</strong> <?= htmlspecialchars($brand['rubro']) ?></li>
                <li><strong>Ubicación:</strong> <?= htmlspecialchars($brand['ubicacion']) ?></li>
                <li><strong>Estado:</strong> <?= htmlspecialchars($brand['estado']) ?></li>
                <li><strong>Creada:</strong> <?= htmlspecialchars($brand['created_at']) ?></li>
            </ul>
            <div class="brand-nav">
                <a href="/brand_form?id=<?= $brand['id'] ?>">Editar</a>
                <a href="/brand_analysis?id=<?= $brand['id'] ?>">Análisis marcario</a>
                <a href="/niza_classification?id=<?= $brand['id'] ?>">Clasificación Niza</a>
                <a href="/business_model?id=<?= $brand['id'] ?>">Modelos de negocio</a>
                <a href="/monetization?id=<?= $brand['id'] ?>">Monetización</a>
                <a href="/legal_risk?id=<?= $brand['id'] ?>">Riesgo legal</a>
                <a href="/brand_report?id=<?= $brand['id'] ?>">Reporte ejecutivo</a>
                <a href="/marcas">Volver al listado</a>
            </div>
        <?php else: ?>
            <p>Marca no encontrada.</p>
            <div class="brand-nav">
                <a href="/marcas">Volver al listado</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
