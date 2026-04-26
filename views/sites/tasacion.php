<?php
/**
 * views/sites/tasacion.php
 * Módulo Avanzado — Tasación de Marcas y Valor Patrimonial.
 */
require_once __DIR__ . '/_layout.php';
siteHeader(t('tasacion_page_title'), 'tasacion');
?>

<div class="card">
    <h2><?= htmlspecialchars(t('tasacion_title'), ENT_QUOTES, 'UTF-8') ?></h2>
    <p><?= htmlspecialchars(t('tasacion_desc'), ENT_QUOTES, 'UTF-8') ?></p>
    <ul class="feature-list">
        <li><strong><?= htmlspecialchars(t('tasacion_li1_title'), ENT_QUOTES, 'UTF-8') ?></strong> — <?= htmlspecialchars(t('tasacion_li1_desc'), ENT_QUOTES, 'UTF-8') ?></li>
        <li><strong><?= htmlspecialchars(t('tasacion_li2_title'), ENT_QUOTES, 'UTF-8') ?></strong> — <?= htmlspecialchars(t('tasacion_li2_desc'), ENT_QUOTES, 'UTF-8') ?></li>
        <li><strong><?= htmlspecialchars(t('tasacion_li3_title'), ENT_QUOTES, 'UTF-8') ?></strong> — <?= htmlspecialchars(t('tasacion_li3_desc'), ENT_QUOTES, 'UTF-8') ?></li>
        <li><strong><?= htmlspecialchars(t('tasacion_li4_title'), ENT_QUOTES, 'UTF-8') ?></strong> — <?= htmlspecialchars(t('tasacion_li4_desc'), ENT_QUOTES, 'UTF-8') ?></li>
        <li><strong><?= htmlspecialchars(t('tasacion_li5_title'), ENT_QUOTES, 'UTF-8') ?></strong> — <?= htmlspecialchars(t('tasacion_li5_desc'), ENT_QUOTES, 'UTF-8') ?></li>
    </ul>
</div>

<div class="section-grid">
    <div class="card">
        <h2>📊 ¿Por qué tasar una marca?</h2>
        <p class="muted">
            La valuación profesional de una marca permite conocer su peso real dentro del patrimonio
            empresarial. Es un insumo clave para decisiones estratégicas: fusiones, adquisiciones,
            aporte de capital, garantías crediticias y planificación sucesoria.
        </p>
    </div>
    <div class="card">
        <h2>🏦 Impacto en el Capital Social</h2>
        <p class="muted">
            Incorporar la marca al activo mediante una tasación certificada puede incrementar
            de forma significativa el capital social de la empresa, mejorando su posición
            ante inversores, entidades financieras y socios comerciales.
        </p>
    </div>
    <div class="card">
        <h2>🤝 Franquicias y Asociatividad</h2>
        <p class="muted">
            Una marca correctamente valuada es la base para estructurar modelos de franquicia
            o acuerdos de licencia. Transmite solidez y seriedad a quienes deseen asociarse
            o replicar el modelo de negocio.
        </p>
    </div>
    <div class="card">
        <h2>🔒 Disponibilidad y Transferencia</h2>
        <p class="muted">
            El activo marcario puede transferirse, cederse en garantía o licenciarse parcialmente.
            Contar con una tasación vigente simplifica estos procesos y los dota de respaldo legal.
        </p>
    </div>
</div>

<div class="card" style="border-left: 4px solid #1B3B6F;">
    <h2><?= htmlspecialchars(t('tasacion_cta_title'), ENT_QUOTES, 'UTF-8') ?></h2>
    <p><?= htmlspecialchars(t('tasacion_cta_desc'), ENT_QUOTES, 'UTF-8') ?></p>
    <p>
        <a href="https://www.mariacelesteortiz.com.ar" target="_blank" rel="noopener noreferrer"
           style="font-weight:800;font-size:1.05em;color:#1B3B6F;text-decoration:underline;">
            <?= htmlspecialchars(t('tasacion_specialist'), ENT_QUOTES, 'UTF-8') ?>
        </a>
    </p>
    <p class="muted">
        Profesional especializada en propiedad industrial, marcas e intangibles.
        Valuaciones con respaldo técnico y legal para empresas de todos los rubros.
    </p>
    <div class="cta-row">
        <a class="btn btn-primary"
           href="https://www.mariacelesteortiz.com.ar" target="_blank" rel="noopener noreferrer">
            <?= htmlspecialchars(t('tasacion_cta_btn'), ENT_QUOTES, 'UTF-8') ?>
        </a>
        <a class="btn btn-secondary" href="/contacto?tema=tasacion">
            <?= htmlspecialchars(t('tasacion_consult_btn'), ENT_QUOTES, 'UTF-8') ?>
        </a>
    </div>
</div>

<?php siteFooter(); ?>
