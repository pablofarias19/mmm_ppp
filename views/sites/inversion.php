<?php
/**
 * views/sites/inversion.php
 * Módulo Avanzado — Inversiones y Financiamiento.
 */
require_once __DIR__ . '/_layout.php';
siteHeader('Inversión — Estructuración Financiera', 'inversion');
?>

<div class="card">
    <h2>Inversiones y Financiamiento</h2>
    <p>
        Estructurar correctamente las fuentes de inversión y financiamiento es clave para el crecimiento
        sostenible. Este módulo analiza las opciones disponibles a nivel nacional e internacional.
    </p>
    <ul class="feature-list">
        <li><strong>Capital propio</strong> — Reinversión de utilidades y estrategia de autofinanciamiento.</li>
        <li><strong>Inversores</strong> — Estructuración de acuerdos de inversión, equity y deuda.</li>
        <li><strong>Fideicomisos</strong> — Vehículos de inversión y protección patrimonial.</li>
        <li><strong>Ingreso de capital extranjero</strong> — Regulación cambiaria y BCRA.</li>
        <li><strong>Repatriación de utilidades</strong> — Marco legal y condiciones vigentes.</li>
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
    <h2>¿Buscás estructurar tu inversión?</h2>
    <p class="muted">Nuestro equipo te ayuda a evaluar opciones de financiamiento y estructurar tu inversión de forma segura.</p>
    <div class="cta-row">
        <a class="btn btn-primary" href="/contacto?tema=inversion">📩 Consultar sobre inversión</a>
        <a class="btn btn-secondary" href="https://fariasortiz.com.ar/contact.html" target="_blank" rel="noopener noreferrer">🔗 Contacto externo</a>
        <a class="btn btn-secondary" href="/avanzado">Ver todos los módulos</a>
    </div>
</div>

<?php siteFooter(); ?>
