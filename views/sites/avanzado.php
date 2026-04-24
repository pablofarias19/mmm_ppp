<?php
/**
 * views/sites/avanzado.php
 * Hub del Módulo Avanzado — Mapita
 */
ini_set('display_errors', 0);
error_reporting(0);

$pageTitle     = 'Módulo Avanzado de Desarrollo Estratégico';
$pageIcon      = '🚀';
$pageSubtitle  = 'Transformá tu negocio, marca o industria en una unidad analizable, optimizable y escalable mediante un enfoque integral legal, fiscal y estratégico.';
$activeSection = 'avanzado';

require __DIR__ . '/_layout.php';
?>

<!-- INTRODUCCIÓN -->
<div class="adv-highlight">
    <strong>¿Qué es el Módulo Avanzado?</strong><br>
    Es el panel de desarrollo estratégico de Mapita. Cada negocio, marca o industria puede optimizarse bajo un plan integral que contempla los aspectos legales, fiscales, financieros, de compliance y de expansión. Seleccioná la sección que te interesa y encontrá información especializada para Argentina.
</div>

<!-- TEMAS PRINCIPALES -->
<h2 class="adv-section-title">🗂️ Temas del Módulo Avanzado</h2>

<div class="adv-grid-2">

    <a class="adv-topic-card" href="/juridico" style="border-left-color:#1B3B6F;">
        <span class="adv-topic-card-icon">⚖️</span>
        <h3>Jurídico y Patrimonial</h3>
        <p>Arquitectura societaria, separación de patrimonio, protección frente a acreedores, fideicomisos y vehículos jurídicos para negocios en Argentina.</p>
        <span class="adv-topic-card-arrow">Ver más →</span>
    </a>

    <a class="adv-topic-card" href="/fiscal" style="border-left-color:#0ea5e9;">
        <span class="adv-topic-card-icon">📊</span>
        <h3>Fiscal y Contable</h3>
        <p>Régimen impositivo, optimización tributaria legal, planificación fiscal, checklist contable y estrategias de diferimiento para tu actividad en Argentina.</p>
        <span class="adv-topic-card-arrow">Ver más →</span>
    </a>

    <a class="adv-topic-card" href="/inversion" style="border-left-color:#22c55e;">
        <span class="adv-topic-card-icon">💰</span>
        <h3>Inversión y Financiamiento</h3>
        <p>Opciones de capitalización, financiamiento estructurado, inversión extranjera, fideicomisos financieros e instrumentos de custodia de intereses.</p>
        <span class="adv-topic-card-arrow">Ver más →</span>
    </a>

    <a class="adv-topic-card" href="/compliance" style="border-left-color:#8b5cf6;">
        <span class="adv-topic-card-icon">🛡️</span>
        <h3>Compliance y Regulación</h3>
        <p>Programas de cumplimiento normativo, debida diligencia, prevención de lavado de activos, defensa del consumidor y responsabilidad penal empresaria.</p>
        <span class="adv-topic-card-arrow">Ver más →</span>
    </a>

    <a class="adv-topic-card" href="/marca-expansion" style="border-left-color:#f59e0b;">
        <span class="adv-topic-card-icon">🏷️</span>
        <h3>Expansión de Marca</h3>
        <p>La marca como activo estratégico: tasación, franquicias, licencias, agencias, protección marcaria y monetización del intangible.</p>
        <span class="adv-topic-card-arrow">Ver más →</span>
    </a>

    <a class="adv-topic-card" href="/contacto" style="border-left-color:#ef4444;">
        <span class="adv-topic-card-icon">📩</span>
        <h3>Contacto y Asesoramiento</h3>
        <p>Consultanos sobre estructuración legal, planificación fiscal, inversiones o expansión de marca. Te respondemos con un análisis personalizado.</p>
        <span class="adv-topic-card-arrow">Contactar →</span>
    </a>

</div>

<!-- CÓMO FUNCIONA -->
<h2 class="adv-section-title">⚡ ¿Cómo funciona el Módulo Avanzado?</h2>

<div class="adv-grid-3">
    <div class="adv-feature-card" style="border-top-color:#1B3B6F;">
        <div class="adv-feature-card-icon">🔍</div>
        <h3>1. Diagnóstico</h3>
        <p>Identificamos el estado actual de tu activo: nivel de formalización, riesgos visibles e invisibles y potencial de escalabilidad.</p>
    </div>
    <div class="adv-feature-card" style="border-top-color:#0ea5e9;">
        <div class="adv-feature-card-icon">🏗️</div>
        <h3>2. Estructuración</h3>
        <p>Definimos la arquitectura legal, fiscal y financiera más adecuada según el tipo de actividad y los objetivos del negocio.</p>
    </div>
    <div class="adv-feature-card" style="border-top-color:#22c55e;">
        <div class="adv-feature-card-icon">🎯</div>
        <h3>3. Plan de acción</h3>
        <p>Generamos un roadmap concreto con prioridades, quick wins y decisiones críticas para maximizar el valor del activo.</p>
    </div>
</div>

<!-- DIFERENCIACIÓN POR TIPO -->
<div class="adv-card">
    <div class="adv-card-title">🏢 Análisis diferenciado por tipo de entidad</div>
    <p>El módulo avanzado adapta el análisis según el tipo de activo que estás evaluando:</p>
    <ul>
        <li><strong>Negocio:</strong> foco en operación, rentabilidad, contratos, formalización y riesgo contractual.</li>
        <li><strong>Marca:</strong> foco en protección del intangible, posicionamiento, licencias y monetización.</li>
        <li><strong>Industria:</strong> foco en estructura, inversión, habilitaciones, regulación ambiental y logística.</li>
    </ul>
    <p style="margin-top:10px">En Mapita podés activar el análisis avanzado desde el popup del mapa o desde el panel de detalle de cualquier negocio, marca o industria haciendo clic en <strong>Avanzado →</strong>.</p>
</div>

<!-- CTA BANNER -->
<div class="adv-cta-banner">
    <h2>¿Listo para estructurar tu activo?</h2>
    <p>Nuestro equipo de especialistas en derecho empresarial, tributario y estratégico está disponible para asesorarte.</p>
    <div class="adv-cta-buttons">
        <a href="/contacto" class="btn-cta-primary">📩 Solicitar asesoramiento</a>
        <a href="/juridico" class="btn-cta-secondary">⚖️ Ver área Jurídica</a>
    </div>
</div>

<?php require __DIR__ . '/_layout_footer.php'; ?>
