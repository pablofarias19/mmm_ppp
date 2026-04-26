<?php
/**
 * views/sites/inversion.php
 * Módulo Avanzado — Inversiones y Financiamiento.
 */
require_once __DIR__ . '/_layout.php';
siteHeader(t('inversion_page_title'), 'inversion');
?>

<div class="card">
    <h2><?= htmlspecialchars(t('inversion_title'), ENT_QUOTES, 'UTF-8') ?></h2>
    <p><?= htmlspecialchars(t('inversion_desc'), ENT_QUOTES, 'UTF-8') ?></p>
    <ul class="feature-list">
        <li><strong><?= htmlspecialchars(t('inversion_li1_title'), ENT_QUOTES, 'UTF-8') ?></strong> — <?= htmlspecialchars(t('inversion_li1_desc'), ENT_QUOTES, 'UTF-8') ?></li>
        <li><strong><?= htmlspecialchars(t('inversion_li2_title'), ENT_QUOTES, 'UTF-8') ?></strong> — <?= htmlspecialchars(t('inversion_li2_desc'), ENT_QUOTES, 'UTF-8') ?></li>
        <li><strong><?= htmlspecialchars(t('inversion_li3_title'), ENT_QUOTES, 'UTF-8') ?></strong> — <?= htmlspecialchars(t('inversion_li3_desc'), ENT_QUOTES, 'UTF-8') ?></li>
        <li><strong><?= htmlspecialchars(t('inversion_li4_title'), ENT_QUOTES, 'UTF-8') ?></strong> — <?= htmlspecialchars(t('inversion_li4_desc'), ENT_QUOTES, 'UTF-8') ?></li>
        <li><strong><?= htmlspecialchars(t('inversion_li5_title'), ENT_QUOTES, 'UTF-8') ?></strong> — <?= htmlspecialchars(t('inversion_li5_desc'), ENT_QUOTES, 'UTF-8') ?></li>
    </ul>
</div>

<div class="section-grid">
    <div class="card">
        <h2>Inversión Nacional</h2>
        <p class="muted">
            Capital propio, rondas de inversores locales, fideicomisos de inversión y acceso a créditos bancarios.
        </p>
    </div>
    <div class="card">
        <h2>Inversión Internacional</h2>
        <p class="muted">
            Ingreso de capital extranjero, regulación cambiaria vigente, repatriación de utilidades y restricciones BCRA.
        </p>
    </div>
    <div class="card">
        <h2>Escenarios Estratégicos</h2>
        <p class="muted">
            Análisis de escenarios: conservador, expansivo y agresivo. Plan de acción con pasos concretos y prioridades.
        </p>
    </div>
    <div class="card">
        <h2>Valor del Activo</h2>
        <p class="muted">
            Valoración económica del negocio, valor de marca y potencial de mercado para atraer inversores.
        </p>
    </div>
</div>

<div class="card">
    <h2>Expansión Estratégica</h2>
    <p class="muted">
        Identificamos oportunidades de escalabilidad, diversificación y apertura de nuevas unidades de negocio.
        Incluye análisis de mercado, competencia y viabilidad financiera.
    </p>
</div>

<div class="card">
    <h2><?= htmlspecialchars(t('inversion_cta_title'), ENT_QUOTES, 'UTF-8') ?></h2>
    <p class="muted"><?= htmlspecialchars(t('inversion_cta_desc'), ENT_QUOTES, 'UTF-8') ?></p>
    <div class="cta-row">
        <a class="btn btn-primary" href="/contacto?tema=inversion"><?= htmlspecialchars(t('inversion_cta_btn'), ENT_QUOTES, 'UTF-8') ?></a>
        <a class="btn btn-secondary" href="https://fariasortiz.com.ar/contact.html" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars(t('adv_contact_external'), ENT_QUOTES, 'UTF-8') ?></a>
        <a class="btn btn-secondary" href="/avanzado"><?= htmlspecialchars(t('adv_see_all_modules'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>
</div>

<?php siteFooter(); ?>
