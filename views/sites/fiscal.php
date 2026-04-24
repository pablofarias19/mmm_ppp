<?php
/**
 * views/sites/fiscal.php
 * Módulo Avanzado — Estructura Fiscal y Contable.
 */
require_once __DIR__ . '/_layout.php';
siteHeader('Fiscal — Estructura Fiscal y Contable', 'fiscal');
?>

<div class="card">
    <h2>Estructura Fiscal y Contable</h2>
    <p>
        Una correcta estructura impositiva puede marcar la diferencia entre la rentabilidad y la pérdida.
        Este módulo analiza el régimen fiscal óptimo, identifica riesgos tributarios y propone
        estrategias de planificación impositiva.
    </p>
    <ul class="feature-list">
        <li><strong>Régimen impositivo</strong> — Monotributo, Responsable Inscripto, IVA, Ingresos Brutos.</li>
        <li><strong>Optimización tributaria</strong> — Reducción legal de la carga impositiva.</li>
        <li><strong>Riesgos fiscales</strong> — Inconsistencias, determinaciones de oficio, multas.</li>
        <li><strong>Planificación impositiva</strong> — Estrategias a corto y largo plazo.</li>
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
    <h2>¿Querés optimizar tu estructura fiscal?</h2>
    <p class="muted">Nuestro equipo te ayuda a reducir la carga impositiva de forma legal y a ordenar tu contabilidad.</p>
    <div class="cta-row">
        <a class="btn btn-primary" href="https://fariasortiz.com.ar/contact.html" target="_blank" rel="noopener noreferrer">
            📩 Consultar con un contador / asesor fiscal
        </a>
        <a class="btn btn-secondary" href="/avanzado">Ver todos los módulos</a>
    </div>
</div>

<?php siteFooter(); ?>
