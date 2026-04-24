<?php
/**
 * views/sites/compliance.php
 * Página: Compliance y Regulación — Módulo Avanzado de Mapita
 */
ini_set('display_errors', 0);
error_reporting(0);

$pageTitle     = 'Compliance y Regulación';
$pageIcon      = '🛡️';
$pageSubtitle  = 'Cumplimiento normativo, debida diligencia, defensa del consumidor y prevención de riesgos regulatorios para empresas en Argentina.';
$activeSection = 'compliance';

require __DIR__ . '/_layout.php';
?>

<!-- INTRO -->
<div class="adv-highlight">
    El compliance ya no es opcional. En Argentina, el marco normativo de prevención de lavado de activos, defensa del consumidor, protección de datos y responsabilidad penal empresaria impone obligaciones concretas a negocios de todos los tamaños. Un programa de cumplimiento proporcional reduce riesgos legales, mejora la reputación y habilita el acceso a mercados más exigentes.
</div>

<!-- COMPLIANCE GENERAL -->
<h2 class="adv-section-title">📋 Programa de Compliance Proporcional</h2>

<div class="adv-card">
    <div class="adv-card-title">🏗️ Componentes de un programa mínimo viable</div>
    <p>Un programa de compliance no necesita ser complejo para ser efectivo. Los elementos esenciales son:</p>
    <ul>
        <li><strong>Código de conducta:</strong> valores, prohibiciones y expectativas de comportamiento para todos los integrantes.</li>
        <li><strong>Políticas internas:</strong> conflicto de intereses, regalos, uso de información confidencial, compras.</li>
        <li><strong>Canal de denuncias:</strong> mecanismo para reportar irregularidades de forma confidencial.</li>
        <li><strong>Capacitación periódica:</strong> todos los colaboradores deben conocer sus obligaciones de compliance.</li>
        <li><strong>Monitoreo y auditoría:</strong> revisión periódica del cumplimiento de las políticas.</li>
        <li><strong>Actualizaciones normativas:</strong> seguimiento de cambios en la regulación aplicable.</li>
    </ul>
</div>

<!-- DEFENSA DEL CONSUMIDOR -->
<h2 class="adv-section-title">🛒 Defensa del Consumidor (Ley 24.240)</h2>

<div class="adv-grid-2">
    <div class="adv-feature-card" style="border-top-color:#ef4444;">
        <div class="adv-feature-card-icon">⚠️</div>
        <h3>Obligaciones de información</h3>
        <p>El consumidor tiene derecho a información clara, veraz y detallada sobre el producto/servicio, precio, condiciones, garantías y forma de devolución. La omisión puede generar responsabilidad.</p>
    </div>
    <div class="adv-feature-card" style="border-top-color:#f59e0b;">
        <div class="adv-feature-card-icon">📢</div>
        <h3>Publicidad veraz</h3>
        <p>La publicidad no puede ser engañosa ni inducir a error. Las promociones y descuentos deben poder cumplirse. Las condiciones deben estar siempre disponibles para el consumidor.</p>
    </div>
    <div class="adv-feature-card" style="border-top-color:#22c55e;">
        <div class="adv-feature-card-icon">🔄</div>
        <h3>Garantías y devoluciones</h3>
        <p>Garantía mínima legal de 6 meses para productos nuevos. Derecho de arrepentimiento en ventas a distancia (10 días). Obligación de reparar, reponer o devolver.</p>
    </div>
    <div class="adv-feature-card" style="border-top-color:#8b5cf6;">
        <div class="adv-feature-card-icon">⚖️</div>
        <h3>Prácticas abusivas</h3>
        <p>Están prohibidas: ventas atadas, cláusulas abusivas en contratos de adhesión, condicionamiento de crédito y cobro de cargos no informados. Las sanciones pueden ser cuantiosas.</p>
    </div>
</div>

<!-- DEFENSA DE LA COMPETENCIA -->
<h2 class="adv-section-title">🏆 Defensa de la Competencia (Ley 27.442)</h2>

<div class="adv-card">
    <div class="adv-card-title">🚫 Prácticas anticompetitivas prohibidas</div>
    <p>La Ley de Defensa de la Competencia prohíbe conductas que restrinjan, limiten o distorsionen la competencia:</p>
    <ul>
        <li><strong>Acuerdos de precios</strong> entre competidores (cartelización)</li>
        <li><strong>Abuso de posición dominante:</strong> precios predatorios, ventas atadas, exclusividades abusivas</li>
        <li><strong>Concentraciones económicas</strong> que superen los umbrales de notificación obligatoria</li>
        <li><strong>Prácticas colusorias</strong> en licitaciones (bid rigging)</li>
    </ul>
    <p>Las sanciones incluyen multas de hasta el 30% de la facturación del ejercicio anterior y, en casos graves, responsabilidad penal de los directivos.</p>
</div>

<!-- PREVENCIÓN DE LAVADO -->
<h2 class="adv-section-title">🔍 Prevención de Lavado de Activos (UIF)</h2>

<div class="adv-card">
    <div class="adv-card-title">📌 Obligaciones según el tipo de actividad</div>
    <p>La Unidad de Información Financiera (UIF) establece obligaciones de reporte y due diligence para sujetos obligados, que incluyen:</p>
    <ul>
        <li>Entidades financieras y cambiarias</li>
        <li>Escribanos y contadores en operaciones específicas</li>
        <li>Inmobiliarias y agentes de bienes raíces</li>
        <li>Casinos y juegos de azar</li>
        <li>Empresas de transporte de caudales</li>
        <li>Otros sectores designados por la UIF</li>
    </ul>
    <p>Incluso los no obligados deben implementar controles básicos para evitar ser utilizados como vehículo de lavado, lo que puede generar responsabilidad penal.</p>
</div>

<div class="adv-grid-2">
    <div class="adv-card" style="margin-bottom:0;border-top-color:#1B3B6F;">
        <div class="adv-card-title">📋 Checklist de debida diligencia de clientes</div>
        <ul>
            <li>Identificación documentada del cliente (DNI/CUIT)</li>
            <li>Verificación del origen de fondos en operaciones significativas</li>
            <li>Consulta de listas de inhabilitados (OFAC, ONU, UIF)</li>
            <li>Monitoreo de operaciones inusuales o sospechosas</li>
            <li>Reporte de Operación Sospechosa (ROS) cuando corresponda</li>
        </ul>
    </div>
    <div class="adv-card" style="margin-bottom:0;border-top-color:#8b5cf6;">
        <div class="adv-card-title">🔒 Protección de datos personales (Ley 25.326)</div>
        <ul>
            <li>Consentimiento informado para la recopilación de datos</li>
            <li>Registro de bases de datos ante AAIP si aplica</li>
            <li>Política de privacidad visible y accesible</li>
            <li>Derecho de acceso, rectificación y supresión de datos</li>
            <li>Medidas de seguridad para evitar accesos no autorizados</li>
        </ul>
    </div>
</div>

<!-- RESPONSABILIDAD PENAL EMPRESARIA -->
<h2 class="adv-section-title">⚖️ Responsabilidad Penal Empresaria (Ley 27.401)</h2>

<div class="adv-card">
    <div class="adv-card-title">🚨 ¿Cuándo responde penalmente una empresa?</div>
    <p>La Ley 27.401 establece la responsabilidad penal de personas jurídicas privadas por delitos de cohecho, tráfico de influencias y lavado de activos, entre otros. Las consecuencias incluyen:</p>
    <ul>
        <li>Multas de 2 a 5 veces el beneficio indebido obtenido</li>
        <li>Suspensión o inhabilitación para contratar con el Estado</li>
        <li>Disolución o suspensión de actividades</li>
        <li>Supervisión judicial de hasta 4 años</li>
    </ul>
    <p>Un programa de integridad certificado y efectivo puede ser eximente o atenuante de responsabilidad.</p>
</div>

<div class="adv-highlight adv-highlight-success">
    <strong>Beneficio del compliance:</strong> más allá de evitar sanciones, un programa de cumplimiento sólido genera confianza en clientes, proveedores, financistas e inversores, y es un diferenciador competitivo real en el mercado.
</div>

<!-- CTA BANNER -->
<div class="adv-cta-banner">
    <h2>¿Querés implementar un programa de compliance?</h2>
    <p>Diseñamos programas de cumplimiento proporcionales al tamaño y riesgo de tu empresa, con foco en los requisitos normativos argentinos y las mejores prácticas internacionales.</p>
    <div class="adv-cta-buttons">
        <a href="/contacto?tema=compliance" class="btn-cta-primary">📩 Consultar sobre compliance</a>
        <a href="/marca-expansion" class="btn-cta-secondary">🏷️ Ver Expansión de Marca →</a>
    </div>
</div>

<?php require __DIR__ . '/_layout_footer.php'; ?>
