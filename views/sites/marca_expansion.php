<?php
/**
 * views/sites/marca_expansion.php
 * Módulo Avanzado — Marca, Expansión Estratégica y Valor.
 */
require_once __DIR__ . '/_layout.php';
siteHeader(t('marca_page_title'), 'marca');
?>

<div class="card">
    <h2><?= htmlspecialchars(t('marca_title'), ENT_QUOTES, 'UTF-8') ?></h2>
    <p><?= htmlspecialchars(t('marca_desc'), ENT_QUOTES, 'UTF-8') ?></p>
    <ul class="feature-list">
        <li><strong>Valor económico y de marca</strong> — Cuantificación del activo intangible.</li>
        <li><strong>Escalabilidad</strong> — Identificación de modelos replicables y crecimiento.</li>
        <li><strong>Diversificación</strong> — Nuevas líneas de negocio y unidades de ingreso.</li>
        <li><strong>Expansión internacional</strong> — Estrategia, estructura y marco legal.</li>
        <li><strong>Potencial de mercado</strong> — Análisis competitivo y posicionamiento.</li>
    </ul>
</div>

<div class="section-grid">
    <div class="card">
        <h2>Expansión para Comercios</h2>
        <p class="muted">
            Franquicias, e-commerce, importación/exportación. Estrategias para llevar tu comercio
            más allá de la ubicación actual.
        </p>
    </div>
    <div class="card">
        <h2>Escalabilidad de Servicios</h2>
        <p class="muted">
            Automatización, suscripciones, servicios digitales. Cómo pasar de facturar horas
            a construir un negocio de ingresos recurrentes.
        </p>
    </div>
    <div class="card">
        <h2>Expansión Industrial</h2>
        <p class="muted">
            Integración vertical, internacionalización, joint ventures. Estrategias para industrias
            de agro, energía, minería y manufactura.
        </p>
    </div>
    <div class="card">
        <h2>Empresas Extranjeras en Argentina</h2>
        <p class="muted">
            Sucursal vs. sociedad local, joint venture. Restricciones BCRA, repatriación de utilidades
            y regulación cambiaria vigente.
        </p>
    </div>
</div>

<div class="section-grid">
    <div class="card">
        <h2>📊 Escenario Conservador</h2>
        <p class="muted">Crecimiento orgánico, reinversión de utilidades, protección de lo ganado. Bajo riesgo, crecimiento gradual.</p>
    </div>
    <div class="card">
        <h2>🚀 Escenario Expansivo</h2>
        <p class="muted">Apertura de nuevas unidades, captación de inversores, expansión geográfica. Riesgo moderado, alto potencial.</p>
    </div>
    <div class="card">
        <h2>⚡ Escenario Agresivo</h2>
        <p class="muted">Crecimiento acelerado, internacionalización, fusiones y adquisiciones. Alto riesgo, máximo retorno potencial.</p>
    </div>
</div>

<div class="card" style="border-left:4px solid #1B3B6F;">
    <h2>💎 Tasación de Marcas</h2>
    <p>
        Las marcas son activos intangibles con valor patrimonial real. Conocé cómo tasar tu marca,
        incrementar el capital social de tu empresa y habilitarla para modelos asociativos como <strong>franquicias</strong>.
    </p>
    <div class="cta-row">
        <a class="btn btn-primary" href="/tasacion">💎 Ver módulo de tasación</a>
    </div>
</div>

<div class="card">
    <h2><?= htmlspecialchars(t('marca_cta_title'), ENT_QUOTES, 'UTF-8') ?></h2>
    <p class="muted"><?= htmlspecialchars(t('marca_cta_desc'), ENT_QUOTES, 'UTF-8') ?></p>
    <div class="cta-row">
        <a class="btn btn-primary" href="/contacto?tema=marca"><?= htmlspecialchars(t('marca_cta_btn'), ENT_QUOTES, 'UTF-8') ?></a>
        <a class="btn btn-secondary" href="https://fariasortiz.com.ar/contact.html" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars(t('adv_contact_external'), ENT_QUOTES, 'UTF-8') ?></a>
        <a class="btn btn-secondary" href="/avanzado"><?= htmlspecialchars(t('adv_see_all_modules'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>
</div>

<?php siteFooter(); ?>

