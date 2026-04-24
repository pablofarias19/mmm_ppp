<?php
/**
 * views/sites/avanzado.php
 * Hub del Módulo Avanzado de Desarrollo Estratégico.
 */
require_once __DIR__ . '/_layout.php';
siteHeader('Avanzado — Desarrollo Estratégico', 'avanzado');
?>

<div class="card">
    <h2>¿Qué hace este panel?</h2>
    <p class="muted">
        Te guía para transformar un negocio, marca o industria en una unidad optimizable: estructura legal,
        fiscal, financiera, compliance, inversión y expansión de marca. Este panel es el punto de entrada
        desde <strong>"Avanzado →"</strong> en el mapa.
    </p>
    <div class="cta-row">
        <a class="btn btn-primary" href="/juridico">⚖️ Jurídico</a>
        <a class="btn btn-secondary" href="/fiscal">🧾 Fiscal</a>
        <a class="btn btn-secondary" href="/inversion">📈 Inversión</a>
        <a class="btn btn-secondary" href="/compliance">🛡️ Compliance</a>
        <a class="btn btn-secondary" href="/marca-expansion">🚀 Marca y Expansión</a>
        <a class="btn btn-secondary" href="/tasacion">💎 Tasación</a>
    </div>
</div>

<div class="section-grid">
    <div class="card">
        <h2>⚖️ Jurídico</h2>
        <p class="muted">Arquitectura legal, tipo societario, separación patrimonial, fideicomisos y protección frente a acreedores.</p>
        <div class="cta-row">
            <a class="btn btn-secondary" href="/juridico">Ver más</a>
        </div>
    </div>

    <div class="card">
        <h2>🧾 Fiscal</h2>
        <p class="muted">Régimen impositivo, optimización tributaria, riesgos fiscales y planificación contable.</p>
        <div class="cta-row">
            <a class="btn btn-secondary" href="/fiscal">Ver más</a>
        </div>
    </div>

    <div class="card">
        <h2>📈 Inversión</h2>
        <p class="muted">Capital propio, inversores, fideicomisos, capital extranjero y regulación cambiaria.</p>
        <div class="cta-row">
            <a class="btn btn-secondary" href="/inversion">Ver más</a>
        </div>
    </div>

    <div class="card">
        <h2>🛡️ Compliance</h2>
        <p class="muted">Programas internos, debida diligencia, prevención de lavado y responsabilidad penal empresaria.</p>
        <div class="cta-row">
            <a class="btn btn-secondary" href="/compliance">Ver más</a>
        </div>
    </div>

    <div class="card">
        <h2>🚀 Marca y Expansión</h2>
        <p class="muted">Escalabilidad, diversificación, expansión a nuevos mercados y valor de marca.</p>
        <div class="cta-row">
            <a class="btn btn-secondary" href="/marca-expansion">Ver más</a>
        </div>
    </div>

    <div class="card">
        <h2>💎 Tasación de Marcas</h2>
        <p class="muted">Valuación de activos intangibles, incremento de capital social, franquicias y negocios asociativos.</p>
        <div class="cta-row">
            <a class="btn btn-secondary" href="/tasacion">Ver más</a>
        </div>
    </div>
</div>

<div class="card">
    <h2>Acción recomendada</h2>
    <p class="muted">¿Querés profundizar en la estructura de tu negocio? Nuestro equipo te ayuda a diagnosticar riesgos y potencial.</p>
    <div class="cta-row">
        <a class="btn btn-primary" href="https://fariasortiz.com.ar/contact.html" target="_blank" rel="noopener noreferrer">
            📩 Contactar / Agendar consulta
        </a>
    </div>
</div>

<?php siteFooter(); ?>
