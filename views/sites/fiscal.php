<?php
/**
 * views/sites/fiscal.php
 * Módulo Avanzado — Estructura Fiscal y Contable.
 */
require_once __DIR__ . '/_layout.php';
siteHeader(t('fiscal_page_title'), 'fiscal');
?>

<div class="card">
    <h2><?= htmlspecialchars(t('fiscal_title'), ENT_QUOTES, 'UTF-8') ?></h2>
    <p><?= htmlspecialchars(t('fiscal_desc'), ENT_QUOTES, 'UTF-8') ?></p>
    <ul class="feature-list">
        <li><strong><?= htmlspecialchars(t('fiscal_li1_title'), ENT_QUOTES, 'UTF-8') ?></strong> — <?= htmlspecialchars(t('fiscal_li1_desc'), ENT_QUOTES, 'UTF-8') ?></li>
        <li><strong><?= htmlspecialchars(t('fiscal_li2_title'), ENT_QUOTES, 'UTF-8') ?></strong> — <?= htmlspecialchars(t('fiscal_li2_desc'), ENT_QUOTES, 'UTF-8') ?></li>
        <li><strong><?= htmlspecialchars(t('fiscal_li3_title'), ENT_QUOTES, 'UTF-8') ?></strong> — <?= htmlspecialchars(t('fiscal_li3_desc'), ENT_QUOTES, 'UTF-8') ?></li>
        <li><strong><?= htmlspecialchars(t('fiscal_li4_title'), ENT_QUOTES, 'UTF-8') ?></strong> — <?= htmlspecialchars(t('fiscal_li4_desc'), ENT_QUOTES, 'UTF-8') ?></li>
    </ul>
</div>

<div class="section-grid">
    <div class="card">
        <h2>Sistema Bancario y Financiero</h2>
        <p class="muted">
            Bancarización, flujo de fondos, créditos disponibles. Riesgos UIF, bloqueos e inconsistencias bancarias.
        </p>
    </div>
    <div class="card">
        <h2>Sistema de Cobranzas</h2>
        <p class="muted">
            Políticas de crédito, gestión de mora, ejecución judicial. Pagaré, cheque y contratos ejecutivos.
        </p>
    </div>
    <div class="card">
        <h2>Prevención de Fraude</h2>
        <p class="muted">
            Riesgos internos, control de caja, auditoría interna y señales de alerta.
        </p>
    </div>
</div>

<div class="card">
    <h2>Diagnóstico Fiscal Integral</h2>
    <p class="muted">
        Analizamos el estado fiscal actual de tu negocio, el nivel de formalización, los riesgos visibles e
        invisibles, y el potencial de optimización. Un diagnóstico a tiempo evita contingencias costosas.
    </p>
</div>

<div class="card">
    <h2><?= htmlspecialchars(t('fiscal_cta_title'), ENT_QUOTES, 'UTF-8') ?></h2>
    <p class="muted"><?= htmlspecialchars(t('fiscal_cta_desc'), ENT_QUOTES, 'UTF-8') ?></p>
    <div class="cta-row">
        <a class="btn btn-primary" href="/contacto?tema=fiscal"><?= htmlspecialchars(t('fiscal_cta_btn'), ENT_QUOTES, 'UTF-8') ?></a>
        <a class="btn btn-secondary" href="https://fariasortiz.com.ar/contact.html" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars(t('adv_contact_external'), ENT_QUOTES, 'UTF-8') ?></a>
        <a class="btn btn-secondary" href="/avanzado"><?= htmlspecialchars(t('adv_see_all_modules'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>
</div>

<?php siteFooter(); ?>
