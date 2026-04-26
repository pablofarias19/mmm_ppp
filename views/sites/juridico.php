<?php
/**
 * views/sites/juridico.php
 * Módulo Avanzado — Arquitectura Legal y Patrimonial.
 */
require_once __DIR__ . '/_layout.php';
siteHeader(t('juridico_page_title'), 'juridico');
?>

<div class="card">
    <h2><?= htmlspecialchars(t('juridico_title'), ENT_QUOTES, 'UTF-8') ?></h2>
    <p><?= htmlspecialchars(t('juridico_desc'), ENT_QUOTES, 'UTF-8') ?></p>
    <ul class="feature-list">
        <li><strong><?= htmlspecialchars(t('juridico_li1_title'), ENT_QUOTES, 'UTF-8') ?></strong> — <?= htmlspecialchars(t('juridico_li1_desc'), ENT_QUOTES, 'UTF-8') ?></li>
        <li><strong><?= htmlspecialchars(t('juridico_li2_title'), ENT_QUOTES, 'UTF-8') ?></strong> — <?= htmlspecialchars(t('juridico_li2_desc'), ENT_QUOTES, 'UTF-8') ?></li>
        <li><strong><?= htmlspecialchars(t('juridico_li3_title'), ENT_QUOTES, 'UTF-8') ?></strong> — <?= htmlspecialchars(t('juridico_li3_desc'), ENT_QUOTES, 'UTF-8') ?></li>
        <li><strong><?= htmlspecialchars(t('juridico_li4_title'), ENT_QUOTES, 'UTF-8') ?></strong> — <?= htmlspecialchars(t('juridico_li4_desc'), ENT_QUOTES, 'UTF-8') ?></li>
        <li><strong><?= htmlspecialchars(t('juridico_li5_title'), ENT_QUOTES, 'UTF-8') ?></strong> — <?= htmlspecialchars(t('juridico_li5_desc'), ENT_QUOTES, 'UTF-8') ?></li>
    </ul>
</div>

<div class="section-grid">
    <div class="card">
        <h2>Contratos y Bienes</h2>
        <p class="muted">Locación, compraventa, bienes registrables y su correcta instrumentación.</p>
    </div>
    <div class="card">
        <h2>Garantías y Seguros</h2>
        <p class="muted">Garantías reales, RC, ART y seguros de daños para proteger la operación.</p>
    </div>
    <div class="card">
        <h2>Habilitaciones</h2>
        <p class="muted">Licencias comerciales, permisos industriales y gestión de riesgos administrativos.</p>
    </div>
    <div class="card">
        <h2>Empresas Extranjeras</h2>
        <p class="muted">Sucursal, sociedad local, joint venture. Restricciones BCRA y regulatorias.</p>
    </div>
</div>

<div class="card">
    <h2>Defensa del Consumidor y Competencia</h2>
    <p class="muted">
        Normativa aplicable, obligaciones de información, publicidad legal y riesgos de sanción.
        Cumplir con la Ley 24.240 y la Ley 27.442 es obligatorio y evita multas significativas.
    </p>
</div>

<div class="card">
    <h2><?= htmlspecialchars(t('juridico_cta_title'), ENT_QUOTES, 'UTF-8') ?></h2>
    <p class="muted"><?= htmlspecialchars(t('juridico_cta_desc'), ENT_QUOTES, 'UTF-8') ?></p>
    <div class="cta-row">
        <a class="btn btn-primary" href="/contacto?tema=juridico"><?= htmlspecialchars(t('juridico_cta_btn'), ENT_QUOTES, 'UTF-8') ?></a>
        <a class="btn btn-secondary" href="https://fariasortiz.com.ar/contact.html" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars(t('adv_contact_external'), ENT_QUOTES, 'UTF-8') ?></a>
        <a class="btn btn-secondary" href="/avanzado"><?= htmlspecialchars(t('adv_see_all_modules'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>
</div>

<?php siteFooter(); ?>
