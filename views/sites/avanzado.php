<?php
/**
 * views/sites/avanzado.php
 * Hub del Módulo Avanzado de Desarrollo Estratégico.
 */
require_once __DIR__ . '/_layout.php';
siteHeader(t('adv_hub_title') . ' — ' . t('advice_offer'), 'avanzado');
?>

<div class="card">
    <h2><?= htmlspecialchars(t('adv_hub_title'), ENT_QUOTES, 'UTF-8') ?></h2>
    <p class="muted"><?= htmlspecialchars(t('adv_hub_desc'), ENT_QUOTES, 'UTF-8') ?></p>
    <div class="cta-row">
        <a class="btn btn-primary" href="/juridico"><?= htmlspecialchars(t('adv_mod_juridico'), ENT_QUOTES, 'UTF-8') ?></a>
        <a class="btn btn-secondary" href="/fiscal"><?= htmlspecialchars(t('adv_mod_fiscal'), ENT_QUOTES, 'UTF-8') ?></a>
        <a class="btn btn-secondary" href="/inversion"><?= htmlspecialchars(t('adv_mod_inversion'), ENT_QUOTES, 'UTF-8') ?></a>
        <a class="btn btn-secondary" href="/compliance"><?= htmlspecialchars(t('adv_mod_compliance'), ENT_QUOTES, 'UTF-8') ?></a>
        <a class="btn btn-secondary" href="/marca-expansion"><?= htmlspecialchars(t('adv_mod_marca'), ENT_QUOTES, 'UTF-8') ?></a>
        <a class="btn btn-secondary" href="/tasacion"><?= htmlspecialchars(t('adv_mod_tasacion'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>
</div>

<div class="section-grid">
    <div class="card">
        <h2><?= htmlspecialchars(t('adv_mod_juridico'), ENT_QUOTES, 'UTF-8') ?></h2>
        <p class="muted"><?= htmlspecialchars(t('adv_juridico_short'), ENT_QUOTES, 'UTF-8') ?></p>
        <div class="cta-row">
            <a class="btn btn-secondary" href="/juridico"><?= htmlspecialchars(t('adv_see_more'), ENT_QUOTES, 'UTF-8') ?></a>
        </div>
    </div>

    <div class="card">
        <h2><?= htmlspecialchars(t('adv_mod_fiscal'), ENT_QUOTES, 'UTF-8') ?></h2>
        <p class="muted"><?= htmlspecialchars(t('adv_fiscal_short'), ENT_QUOTES, 'UTF-8') ?></p>
        <div class="cta-row">
            <a class="btn btn-secondary" href="/fiscal"><?= htmlspecialchars(t('adv_see_more'), ENT_QUOTES, 'UTF-8') ?></a>
        </div>
    </div>

    <div class="card">
        <h2><?= htmlspecialchars(t('adv_mod_inversion'), ENT_QUOTES, 'UTF-8') ?></h2>
        <p class="muted"><?= htmlspecialchars(t('adv_inversion_short'), ENT_QUOTES, 'UTF-8') ?></p>
        <div class="cta-row">
            <a class="btn btn-secondary" href="/inversion"><?= htmlspecialchars(t('adv_see_more'), ENT_QUOTES, 'UTF-8') ?></a>
        </div>
    </div>

    <div class="card">
        <h2><?= htmlspecialchars(t('adv_mod_compliance'), ENT_QUOTES, 'UTF-8') ?></h2>
        <p class="muted"><?= htmlspecialchars(t('adv_compliance_short'), ENT_QUOTES, 'UTF-8') ?></p>
        <div class="cta-row">
            <a class="btn btn-secondary" href="/compliance"><?= htmlspecialchars(t('adv_see_more'), ENT_QUOTES, 'UTF-8') ?></a>
        </div>
    </div>

    <div class="card">
        <h2><?= htmlspecialchars(t('adv_mod_marca'), ENT_QUOTES, 'UTF-8') ?></h2>
        <p class="muted"><?= htmlspecialchars(t('adv_marca_short'), ENT_QUOTES, 'UTF-8') ?></p>
        <div class="cta-row">
            <a class="btn btn-secondary" href="/marca-expansion"><?= htmlspecialchars(t('adv_see_more'), ENT_QUOTES, 'UTF-8') ?></a>
        </div>
    </div>

    <div class="card">
        <h2><?= htmlspecialchars(t('adv_mod_tasacion'), ENT_QUOTES, 'UTF-8') ?></h2>
        <p class="muted"><?= htmlspecialchars(t('adv_tasacion_short'), ENT_QUOTES, 'UTF-8') ?></p>
        <div class="cta-row">
            <a class="btn btn-secondary" href="/tasacion"><?= htmlspecialchars(t('adv_see_more'), ENT_QUOTES, 'UTF-8') ?></a>
        </div>
    </div>
</div>

<div class="card">
    <h2><?= htmlspecialchars(t('adv_hub_action_title'), ENT_QUOTES, 'UTF-8') ?></h2>
    <p class="muted"><?= htmlspecialchars(t('adv_hub_action_desc'), ENT_QUOTES, 'UTF-8') ?></p>
    <div class="cta-row">
        <a class="btn btn-primary" href="/contacto"><?= htmlspecialchars(t('adv_hub_contact_btn'), ENT_QUOTES, 'UTF-8') ?></a>
        <a class="btn btn-secondary" href="https://fariasortiz.com.ar/contact.html" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars(t('adv_contact_external'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>
</div>

<?php siteFooter(); ?>
