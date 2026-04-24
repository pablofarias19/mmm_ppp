<?php
/**
 * views/sites/juridico.php
 * Módulo Avanzado — Arquitectura Legal y Patrimonial.
 */
require_once __DIR__ . '/_layout.php';
siteHeader('Jurídico — Arquitectura Legal y Patrimonial', 'juridico');
?>

<div class="card">
    <h2>Arquitectura Legal y Patrimonial</h2>
    <p>
        Una estructura jurídica sólida es la base de cualquier negocio sustentable. Este módulo analiza
        los aspectos legales clave para proteger el patrimonio, minimizar riesgos y facilitar el crecimiento.
    </p>
    <ul class="feature-list">
        <li><strong>Tipo societario recomendado</strong> — SRL, SA, SAS u otras formas según el negocio.</li>
        <li><strong>Separación patrimonial</strong> — Blindar el patrimonio personal del empresarial.</li>
        <li><strong>Fideicomisos</strong> — Protección, inversión y planificación sucesoria.</li>
        <li><strong>Protección frente a acreedores</strong> — Estrategias legales preventivas.</li>
        <li><strong>Riesgos legales</strong> — Identificación y mitigación de contingencias.</li>
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
    <h2>¿Necesitás asesoramiento jurídico?</h2>
    <p class="muted">Nuestro equipo analiza la estructura legal de tu negocio y propone soluciones concretas.</p>
    <div class="cta-row">
        <a class="btn btn-primary" href="/contacto?tema=juridico">📩 Consultar con un abogado</a>
        <a class="btn btn-secondary" href="https://fariasortiz.com.ar/contact.html" target="_blank" rel="noopener noreferrer">🔗 Contacto externo</a>
        <a class="btn btn-secondary" href="/avanzado">Ver todos los módulos</a>
    </div>
</div>

<?php siteFooter(); ?>
