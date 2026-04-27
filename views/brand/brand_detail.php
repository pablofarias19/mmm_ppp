<?php
// views/brand/brand_detail.php
// Detalle de una marca — vista mejorada

// Defensivo: asegurar que $brand sea array o null
if (isset($brand) && !is_array($brand)) {
    $brand = null;
}

// ── Open Graph ────────────────────────────────────────────────────────────────
if (!empty($brand)) {
    $og_title       = ($brand['nombre'] ?? 'Marca') . ' — Marca en Mapita';
    $og_description = 'Conocé la marca ' . ($brand['nombre'] ?? '')
        . (!empty($brand['rubro'])    ? ' · ' . $brand['rubro']    : '')
        . (!empty($brand['ubicacion']) ? ' · ' . $brand['ubicacion'] : '')
        . '. Disponible en el mapa de Mapita.';
} else {
    $og_title       = 'MAPITA - Mapa de Marcas y Negocios';
    $og_description = 'Descubrí marcas y negocios cerca tuyo.';
}

// marca_id para el grid de herramientas
$marca_id = (!empty($brand) && is_array($brand)) ? ((int)($brand['id'] ?? 0) ?: null) : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($og_title) ?></title>
    <link rel="stylesheet" href="/css/map-styles.css">
    <link rel="stylesheet" href="/css/tool-panels.css">
    <link rel="stylesheet" href="/css/brand.css">
    <?php require_once __DIR__ . '/../../includes/meta_og.php'; ?>
</head>
<body class="brand-page">

<header class="bd-topbar" role="banner">
    <a href="/marcas" class="bd-topbar-back" aria-label="Volver al listado de marcas">← Marcas</a>
    <?php if ($marca_id): ?>
    <a href="/brand_form?id=<?= $marca_id ?>" class="bd-topbar-edit" aria-label="Editar esta marca">✏️ Editar</a>
    <?php endif; ?>
</header>

<main class="bd-main" role="main">

    <?php if (!empty($brand) && is_array($brand)): ?>

    <!-- ── Hero ──────────────────────────────────────────── -->
    <div class="bd-hero" role="region" aria-label="Encabezado de marca">
        <div class="bd-hero-title-group">
            <h1 class="bd-brand-name"><?= htmlspecialchars($brand['nombre'] ?? 'Sin nombre') ?></h1>
            <?php if (!empty($brand['estado'])): ?>
            <span class="bd-status-badge bd-status-<?= htmlspecialchars(strtolower($brand['estado'])) ?>">
                <?= htmlspecialchars($brand['estado']) ?>
            </span>
            <?php endif; ?>
        </div>
        <div class="bd-hero-meta">
            <?php if (!empty($brand['rubro'])): ?>
            <span class="bd-meta-chip">📋 <?= htmlspecialchars($brand['rubro']) ?></span>
            <?php endif; ?>
            <?php if (!empty($brand['ubicacion'])): ?>
            <span class="bd-meta-chip">📍 <?= htmlspecialchars($brand['ubicacion']) ?></span>
            <?php endif; ?>
            <?php if (!empty($brand['created_at'])): ?>
            <span class="bd-meta-chip">🗓️ Desde <?= htmlspecialchars(substr($brand['created_at'], 0, 10)) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="bd-col-main">

        <!-- ── Información General ──────────────────────── -->
        <section class="bd-card" aria-labelledby="info-heading">
            <h2 id="info-heading" class="bd-card-title">📄 Información General</h2>
            <dl class="bd-info-list">
                <div class="bd-info-row">
                    <dt>ID de marca</dt>
                    <dd><?= htmlspecialchars((string)($brand['id'] ?? '—')) ?></dd>
                </div>
                <div class="bd-info-row">
                    <dt>Nombre</dt>
                    <dd><?= htmlspecialchars($brand['nombre'] ?? '—') ?></dd>
                </div>
                <div class="bd-info-row">
                    <dt>Rubro</dt>
                    <dd><?= $brand['rubro'] ? htmlspecialchars($brand['rubro']) : '<em>Sin especificar</em>' ?></dd>
                </div>
                <div class="bd-info-row">
                    <dt>Ubicación</dt>
                    <dd><?= $brand['ubicacion'] ? htmlspecialchars($brand['ubicacion']) : '<em>Sin especificar</em>' ?></dd>
                </div>
                <div class="bd-info-row">
                    <dt>Estado</dt>
                    <dd><?= htmlspecialchars($brand['estado'] ?? '—') ?></dd>
                </div>
                <div class="bd-info-row">
                    <dt>Creada</dt>
                    <dd><?= htmlspecialchars($brand['created_at'] ?? '—') ?></dd>
                </div>
            </dl>
        </section>

        <!-- ── Herramientas de Análisis ─────────────────── -->
        <?php require __DIR__ . '/_tools_grid.php'; ?>

    </div>

    <!-- ── Acciones ──────────────────────────────────────── -->
    <nav class="bd-actions" aria-label="Acciones de marca">
        <a href="/marcas" class="bd-btn bd-btn-ghost">← Volver al listado</a>
        <a href="/brand_form?id=<?= $marca_id ?>" class="bd-btn bd-btn-edit">✏️ Editar marca</a>
    </nav>

    <?php else: ?>

    <!-- ── Marca no encontrada ───────────────────────────── -->
    <div class="bd-not-found" role="region" aria-label="Marca no encontrada">
        <div class="bd-not-found-inner">
            <p class="bd-not-found-icon" aria-hidden="true">🔍</p>
            <h2>Marca no encontrada</h2>
            <p>La marca que buscás no existe o no está disponible.</p>
            <?php require __DIR__ . '/_tools_grid.php'; ?>
            <nav aria-label="Navegación de fallback">
                <a href="/marcas" class="bd-btn bd-btn-ghost">← Volver al listado</a>
            </nav>
        </div>
    </div>

    <?php endif; ?>

</main>

<footer class="bd-footer" role="contentinfo">
    <p>&copy; <?= date('Y') ?> Mapita — <a href="/">Volver al inicio</a></p>
</footer>

</body>
</html>
