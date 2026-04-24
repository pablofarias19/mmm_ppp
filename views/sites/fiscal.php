<?php
/**
 * views/sites/fiscal.php
 * Página: Fiscal y Contable — Módulo Avanzado de Mapita
 */
ini_set('display_errors', 0);
error_reporting(0);

$pageTitle     = 'Fiscal y Contable';
$pageIcon      = '📊';
$pageSubtitle  = 'Optimización tributaria legal, planificación fiscal y estructuración contable para tu actividad en Argentina.';
$activeSection = 'fiscal';

require __DIR__ . '/_layout.php';
?>

<!-- INTRO -->
<div class="adv-highlight">
    La correcta estructuración fiscal es uno de los factores de mayor impacto en la rentabilidad de cualquier negocio. En Argentina, un régimen inadecuado o una mala categorización pueden generar costos tributarios innecesarios, sanciones de AFIP y riesgos operativos significativos. La planificación fiscal lícita no es un lujo: es una herramienta de gestión.
</div>

<!-- REGÍMENES IMPOSITIVOS -->
<h2 class="adv-section-title">📋 Regímenes Impositivos en Argentina</h2>

<div class="adv-grid-3">
    <div class="adv-feature-card" style="border-top-color:#1B3B6F;">
        <div class="adv-feature-card-icon">🧾</div>
        <h3>Monotributo</h3>
        <p>Régimen simplificado para pequeños contribuyentes. Categorías A–K según facturación, superficie y energía. Incluye obra social y aportes jubilatorios.</p>
    </div>
    <div class="adv-feature-card" style="border-top-color:#0ea5e9;">
        <div class="adv-feature-card-icon">📑</div>
        <h3>Responsable Inscripto</h3>
        <p>Régimen general con IVA, Ganancias, Ingresos Brutos y SIPA. Mayor carga administrativa pero acceso a crédito fiscal y deducciones más amplias.</p>
    </div>
    <div class="adv-feature-card" style="border-top-color:#6366f1;">
        <div class="adv-feature-card-icon">🏢</div>
        <h3>Persona Jurídica</h3>
        <p>Sociedades tributan Impuesto a las Ganancias con alícuota proporcional. Permiten deducción de gastos de la empresa y separación de la carga personal del socio.</p>
    </div>
</div>

<!-- PLANIFICACIÓN FISCAL -->
<h2 class="adv-section-title">🎯 Planificación Fiscal Lícita</h2>

<div class="adv-card">
    <div class="adv-card-title">💡 Estrategias de optimización tributaria</div>
    <p>La planificación fiscal lícita consiste en estructurar la actividad de manera que se paguen los impuestos correctos en el momento adecuado, aprovechando los instrumentos que la ley permite:</p>
    <ul>
        <li><strong>Elección del régimen correcto:</strong> análisis de umbral de facturación, actividad y forma jurídica.</li>
        <li><strong>Deducción de gastos:</strong> identificar todos los gastos deducibles vinculados a la actividad.</li>
        <li><strong>Diferimiento impositivo:</strong> estructurar ingresos y gastos para diferir la carga tributaria dentro del marco legal.</li>
        <li><strong>Uso de amortizaciones:</strong> bienes de uso, mejoras e inversiones como escudo fiscal.</li>
        <li><strong>Retenciones y percepciones:</strong> gestión correcta para evitar saldos a favor acumulados innecesariamente.</li>
        <li><strong>Precios de transferencia:</strong> relevante para operaciones entre empresas vinculadas o con exterior.</li>
    </ul>
</div>

<!-- RIESGOS FISCALES -->
<h2 class="adv-section-title">⚠️ Riesgos Fiscales Frecuentes</h2>

<div class="adv-grid-2">
    <div class="adv-card" style="margin-bottom:0;border-top-color:#ef4444;">
        <div class="adv-card-title" style="color:#dc2626;">🚨 Señales de alerta AFIP</div>
        <ul>
            <li>Facturación inconsistente con la categoría de Monotributo</li>
            <li>Gastos personales imputados como gastos de empresa</li>
            <li>Discrepancias entre ventas declaradas y depósitos bancarios</li>
            <li>Uso de cuentas personales para operaciones comerciales</li>
            <li>Contratos de locación no declarados (alquileres)</li>
            <li>Empleados no registrados o relaciones encubiertas</li>
        </ul>
    </div>
    <div class="adv-card" style="margin-bottom:0;border-top-color:#22c55e;">
        <div class="adv-card-title" style="color:#16a34a;">✅ Medidas preventivas</div>
        <ul>
            <li>Revisión anual del régimen más conveniente</li>
            <li>Separación de cuentas bancarias personales y empresariales</li>
            <li>Registro contable ordenado desde el inicio</li>
            <li>Archivo de comprobantes por mínimo 10 años</li>
            <li>Declaraciones juradas presentadas en tiempo y forma</li>
            <li>Asesoramiento contable ante cualquier cambio de escala</li>
        </ul>
    </div>
</div>

<!-- CHECKLIST CONTABLE -->
<h2 class="adv-section-title">📝 Checklist Contable Mínimo</h2>

<div class="adv-card">
    <div class="adv-card-title">📌 Documentación y registros esenciales</div>
    <ul>
        <li>✅ Libro Diario y Libro Mayor actualizados (si corresponde)</li>
        <li>✅ Facturación electrónica habilitada en AFIP</li>
        <li>✅ Libro de IVA Ventas e IVA Compras</li>
        <li>✅ Declaraciones juradas mensuales presentadas</li>
        <li>✅ Conciliación bancaria mensual</li>
        <li>✅ Registro de activos fijos y amortizaciones</li>
        <li>✅ Soporte de gastos deducibles (facturas originales)</li>
        <li>✅ Liquidaciones de sueldos y aportes si hay personal</li>
        <li>✅ Estados contables anuales firmados por contador</li>
    </ul>
</div>

<!-- IMPUESTOS CLAVE -->
<h2 class="adv-section-title">🗂️ Principales Impuestos en Argentina</h2>

<div class="adv-grid-3">
    <div class="adv-feature-card" style="border-top-color:#f59e0b;">
        <div class="adv-feature-card-icon">💸</div>
        <h3>IVA</h3>
        <p>Alícuota general 21%. Crédito y débito fiscal. Gestión mensual de posición. Posición técnica y saldo técnico.</p>
    </div>
    <div class="adv-feature-card" style="border-top-color:#ef4444;">
        <div class="adv-feature-card-icon">📈</div>
        <h3>Ganancias</h3>
        <p>Personas humanas: escala progresiva. Sociedades: alícuota proporcional. Deducciones de gastos vinculados a la actividad.</p>
    </div>
    <div class="adv-feature-card" style="border-top-color:#8b5cf6;">
        <div class="adv-feature-card-icon">🏙️</div>
        <h3>Ingresos Brutos</h3>
        <p>Impuesto provincial. Varía según jurisdicción y actividad. Convenio Multilateral para operaciones en múltiples provincias.</p>
    </div>
</div>

<div class="adv-highlight adv-highlight-warning">
    <strong>Importante:</strong> las estrategias fiscales deben adaptarse al caso específico. La información presentada tiene carácter orientativo y no reemplaza el asesoramiento de un contador matriculado. Ante cualquier duda, consultá con un profesional.
</div>

<!-- CTA BANNER -->
<div class="adv-cta-banner">
    <h2>¿Querés optimizar tu estructura fiscal?</h2>
    <p>Analizamos tu régimen impositivo actual, identificamos oportunidades de ahorro y diseñamos un plan de acción concreto para tu actividad en Argentina.</p>
    <div class="adv-cta-buttons">
        <a href="/contacto?tema=fiscal" class="btn-cta-primary">📩 Solicitar asesoramiento fiscal</a>
        <a href="/inversion" class="btn-cta-secondary">💰 Ver área Inversión →</a>
    </div>
</div>

<?php require __DIR__ . '/_layout_footer.php'; ?>
