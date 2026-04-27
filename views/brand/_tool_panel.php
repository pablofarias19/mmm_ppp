<?php
/**
 * views/brand/_tool_panel.php
 *
 * Panel genérico reutilizable para los módulos de herramientas de marca.
 *
 * Variables opcionales (con valores por defecto) que pueden definirse antes de incluir:
 *   $tool_title       (string)  Título del módulo                  (default: 'Módulo')
 *   $tool_description (string)  Descripción breve del módulo       (default: '')
 *   $tool_bullets     (array)   Ítems de "qué incluirá" el módulo  (default: [])
 *   $tool_edit_note   (string)  Nota de edición/personalización    (default: mensaje genérico)
 *   $brand_label      (string)  Etiqueta de la marca/proyecto      (default: 'Demo')
 *   $marca_id         (int|null) ID de la marca                    (default: null)
 *   $back_url         (string)  URL del botón "Volver"             (default: '/marcas')
 */
$tool_title       = $tool_title       ?? 'Módulo';
$tool_description = $tool_description ?? '';
$tool_bullets     = $tool_bullets     ?? [];
$tool_edit_note   = $tool_edit_note   ?? 'Este panel es editable directamente desde su plantilla PHP. Próximamente personalizable desde el panel de administración.';
$brand_label      = $brand_label      ?? 'Demo';
$marca_id         = $marca_id         ?? null;
$back_url         = $back_url         ?? '/marcas';
$show_detail_link = $show_detail_link ?? true;
$tool_links       = $tool_links       ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tool_title) ?> — Mapita Marcas</title>
    <link rel="stylesheet" href="/css/map-styles.css">
    <link rel="stylesheet" href="/css/tool-panels.css">
</head>
<body>
<div class="tp-wrap">

    <!-- Header -->
    <div class="tp-header">
        <h1><?= htmlspecialchars($tool_title) ?></h1>
        <span class="tp-header-meta">
            Bajo consulta profesional &nbsp;·&nbsp;
            <a href="https://www.fariasortiz.com.ar/marcas.html"
               target="_blank" rel="noopener noreferrer"
               style="color:#93c5fd; text-decoration:underline;">fariasortiz.com.ar</a>
        </span>
    </div>

    <!-- Main card -->
    <div class="tp-card">

        <?php if ($brand_label): ?>
        <span class="tp-brand-badge">📌 <?= htmlspecialchars($brand_label) ?></span>
        <?php endif; ?>

        <!-- Disclaimer -->
        <div class="tp-notice">
            <strong>⚠️ Servicio bajo consulta profesional</strong>
            Todos los análisis, diagnósticos y servicios disponibles en este módulo son desarrollados
            <strong>bajo consulta profesional</strong> por el equipo de
            <a href="https://www.fariasortiz.com.ar/marcas.html" target="_blank" rel="noopener noreferrer"
               style="color:#92400e; font-weight:600;">Farias Ortiz — Asesoría de Marcas</a>.
            La información presentada aquí es de carácter general y orientativo, y
            <strong>no sustituye el asesoramiento profesional individualizado</strong>.
        </div>

        <!-- Description -->
        <p class="tp-description"><?= nl2br(htmlspecialchars($tool_description)) ?></p>

        <!-- Future bullets -->
        <?php if (!empty($tool_bullets)): ?>
        <h3 class="tp-section-title">¿Qué incluirá este módulo?</h3>
        <ul class="tp-bullets">
            <?php foreach ($tool_bullets as $bullet): ?>
            <li><?= htmlspecialchars($bullet) ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <!-- Edit note -->
        <div class="tp-edit-note">
            ✏️ <strong>Editar contenido:</strong>
            <?= htmlspecialchars($tool_edit_note) ?>
        </div>

        <!-- More info -->
        <div class="tp-more-info">
            <strong>Más información:</strong><br>
            Visitá
            <a href="https://www.fariasortiz.com.ar/marcas.html" target="_blank" rel="noopener noreferrer">
                www.fariasortiz.com.ar/marcas.html
            </a>
            para conocer todos los servicios de asesoría de marcas disponibles.
        </div>

        <!-- Tools links (optional, shown when $tool_links is provided) -->
        <?php if (!empty($tool_links)): ?>
        <h3 class="tp-section-title">🔬 Herramientas de Análisis</h3>
        <div class="tp-tools-grid">
            <?php foreach ($tool_links as $tl): ?>
            <a href="<?= htmlspecialchars($tl['url']) ?>" class="tp-tool-link">
                <?= htmlspecialchars($tl['label']) ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Navigation -->
        <nav class="tp-nav" aria-label="Navegación del módulo">
            <a class="tp-nav-back" href="<?= htmlspecialchars($back_url) ?>" aria-label="Volver a la página anterior">← Volver</a>
            <?php if ($marca_id && $show_detail_link): ?>
            <a class="tp-nav-detail" href="/brand_detail?id=<?= (int)$marca_id ?>" aria-label="Ver detalle de la marca">Ver detalle de marca</a>
            <?php endif; ?>
            <a class="tp-nav-back" href="/marcas" aria-label="Ir al listado de marcas">Listado de marcas</a>
        </nav>

    </div>
</div>
</body>
</html>
