<?php
/**
 * views/brand/_tools_grid.php
 *
 * Componente reutilizable: grilla de "Herramientas de Análisis" de marca.
 * Fuente de verdad única para el orden, labels e íconos de las 6 herramientas.
 *
 * Variables opcionales:
 *   $marca_id        (int|null)   ID de la marca — agrega ?id= a cada link.
 *   $brand           (array|null) Datos de la marca — se usa para inferir $marca_id si no viene.
 *   $tools           (array|null) Override de herramientas. Si no se pasa, se usan las 6 estándar.
 *   $tools_wrap_card (bool)       Envolver en tarjeta (.bd-tools-section). Default: true.
 *                                 Pasar false al incluir desde un panel que ya tiene card (tp-card).
 */

// Inferir marca_id desde $brand si no viene explícito
if (empty($marca_id) && !empty($brand) && is_array($brand)) {
    $marca_id = (int)($brand['id'] ?? 0) ?: null;
}
$marca_id = isset($marca_id) ? ((int)$marca_id ?: null) : null;

// Envolver en tarjeta por defecto, salvo que el caller lo deshabilite
$_wrap_card = $tools_wrap_card ?? true;

// Definición canónica de las 6 herramientas (orden fijo)
$_default_tools = [
    ['url' => '/brand_analysis',      'label' => 'Análisis Marcario',  'icon' => '📊', 'desc' => 'Fortalezas, debilidades, oportunidades'],
    ['url' => '/niza_classification', 'label' => 'Clasificación Niza', 'icon' => '📋', 'desc' => 'Clases de productos y servicios'],
    ['url' => '/business_model',      'label' => 'Modelos de Negocio', 'icon' => '♟️', 'desc' => 'Canvas, estrategia, monetización'],
    ['url' => '/monetization',        'label' => 'Monetización',       'icon' => '💰', 'desc' => 'Estrategias de ingresos y valor'],
    ['url' => '/legal_risk',          'label' => 'Riesgo Legal',       'icon' => '⚖️', 'desc' => 'Análisis jurídico y registral'],
    ['url' => '/brand_report',        'label' => 'Reporte Ejecutivo',  'icon' => '📑', 'desc' => 'Informe consolidado de la marca'],
];
$_render_tools = $tools ?? $_default_tools;
?>
<?php if ($_wrap_card): ?>
<section class="bd-tools-section" aria-labelledby="tools-grid-heading">
<?php endif; ?>
    <h2 id="tools-grid-heading" class="bd-section-title">🔬 Herramientas de Análisis</h2>
    <p class="bd-section-subtitle">Profundizá el estudio de tu marca</p>
    <div class="tp-tools-grid">
        <?php foreach ($_render_tools as $_tool): ?>
        <?php $_href = $_tool['url'] . ($marca_id ? '?id=' . $marca_id : ''); ?>
        <a href="<?= htmlspecialchars($_href) ?>" class="tp-tool-link bd-tool-card">
            <?php if (!empty($_tool['icon'])): ?>
            <span class="bd-tool-icon" aria-hidden="true"><?= $_tool['icon'] ?></span>
            <?php endif; ?>
            <span class="bd-tool-info">
                <span class="bd-tool-name"><?= htmlspecialchars($_tool['label']) ?></span>
                <?php if (!empty($_tool['desc'])): ?>
                <span class="bd-tool-desc"><?= htmlspecialchars($_tool['desc']) ?></span>
                <?php endif; ?>
            </span>
        </a>
        <?php endforeach; ?>
    </div>
<?php if ($_wrap_card): ?>
</section>
<?php endif; ?>
<?php
// Limpiar variables locales para no contaminar el scope externo
unset($_default_tools, $_render_tools, $_tool, $_href, $_wrap_card);
?>
