<?php
/**
 * views/sites/inversion.php
 * Página: Inversión y Financiamiento — Módulo Avanzado de Mapita
 */
ini_set('display_errors', 0);
error_reporting(0);

$pageTitle     = 'Inversión y Financiamiento';
$pageIcon      = '💰';
$pageSubtitle  = 'Opciones de capitalización, financiamiento estructurado e inversión local e internacional para negocios en Argentina.';
$activeSection = 'inversion';

require __DIR__ . '/_layout.php';
?>

<!-- INTRO -->
<div class="adv-highlight">
    Identificar las fuentes correctas de financiamiento y estructurar adecuadamente la inversión son decisiones que determinan la velocidad de crecimiento y la solidez de cualquier empresa. En Argentina, el contexto regulatorio y cambiario requiere un conocimiento preciso de los instrumentos disponibles y sus implicancias legales.
</div>

<!-- OPCIONES DE FINANCIAMIENTO -->
<h2 class="adv-section-title">🏦 Fuentes de Financiamiento</h2>

<div class="adv-grid-2">
    <div class="adv-feature-card" style="border-top-color:#1B3B6F;">
        <div class="adv-feature-card-icon">💼</div>
        <h3>Capital propio y reinversión</h3>
        <p>La reinversión de utilidades es la fuente más accesible y sin costo financiero. Requiere disciplina en la separación de caja personal y empresarial, y proyección de flujo de fondos.</p>
    </div>
    <div class="adv-feature-card" style="border-top-color:#0ea5e9;">
        <div class="adv-feature-card-icon">🏛️</div>
        <h3>Crédito bancario</h3>
        <p>Líneas de crédito productivas, préstamos comerciales, descuento de cheques y factoring. Acceso condicionado a historial crediticio, garantías y estados contables actualizados.</p>
    </div>
    <div class="adv-feature-card" style="border-top-color:#22c55e;">
        <div class="adv-feature-card-icon">🤝</div>
        <h3>Inversores privados</h3>
        <p>Socios inversores, family offices y fondos de capital de riesgo. Requiere due diligence, pacto de accionistas y estructura societaria clara para la entrada y salida del inversor.</p>
    </div>
    <div class="adv-feature-card" style="border-top-color:#f59e0b;">
        <div class="adv-feature-card-icon">📋</div>
        <h3>Leasing y financiamiento de activos</h3>
        <p>Leasing financiero y operativo para bienes de capital. Permite incorporar equipamiento sin inmovilizar capital. Tratamiento fiscal específico en Ganancias e IVA.</p>
    </div>
    <div class="adv-feature-card" style="border-top-color:#8b5cf6;">
        <div class="adv-feature-card-icon">📦</div>
        <h3>Fideicomisos financieros</h3>
        <p>Estructuras de securitización para negocios con flujo de fondos predecible. Permiten acceder al mercado de capitales y separar el riesgo del fiduciante.</p>
    </div>
    <div class="adv-feature-card" style="border-top-color:#ef4444;">
        <div class="adv-feature-card-icon">🌐</div>
        <h3>Inversión extranjera</h3>
        <p>Ingreso de capital del exterior con registro en el BCRA. Sujeto a regulación cambiaria, plazos de permanencia y repatriación según el régimen vigente.</p>
    </div>
</div>

<!-- INSTRUMENTOS DE DEUDA -->
<h2 class="adv-section-title">📜 Instrumentos de Deuda y Garantías</h2>

<div class="adv-card">
    <div class="adv-card-title">🔐 Instrumentos para estructurar deuda</div>
    <p>La correcta documentación de las obligaciones financieras protege tanto al acreedor como al deudor y facilita la ejecución en caso de incumplimiento:</p>
    <ul>
        <li><strong>Pagarés:</strong> título ejecutivo de fácil ejecución judicial. Requiere firma, fecha y lugar de pago.</li>
        <li><strong>Cheques diferidos:</strong> instrumento de pago a plazo ampliamente utilizado en operaciones comerciales.</li>
        <li><strong>Contratos de mutuo:</strong> formalización de préstamos entre personas físicas o jurídicas.</li>
        <li><strong>Hipoteca:</strong> garantía real sobre inmuebles para operaciones de mayor envergadura.</li>
        <li><strong>Prenda:</strong> garantía sobre bienes muebles registrables (automotores, maquinaria).</li>
        <li><strong>Fianza personal:</strong> garantía personal del socio o tercero para operaciones con bancos.</li>
    </ul>
</div>

<!-- INVERSIÓN EXTRANJERA -->
<h2 class="adv-section-title">🌍 Inversión Internacional</h2>

<div class="adv-grid-2">
    <div class="adv-card" style="margin-bottom:0;border-top-color:#0ea5e9;">
        <div class="adv-card-title">🔵 Ingreso de capital extranjero en Argentina</div>
        <ul>
            <li>Registro obligatorio ante el BCRA para inversiones directas</li>
            <li>Período mínimo de permanencia según el tipo de inversión</li>
            <li>Restricciones a la repatriación de dividendos y capital</li>
            <li>Convenios de doble imposición con países seleccionados</li>
            <li>Joint ventures y sucursales como alternativas a la sociedad local</li>
        </ul>
    </div>
    <div class="adv-card" style="margin-bottom:0;border-top-color:#22c55e;">
        <div class="adv-card-title" style="color:#16a34a;">🟢 Inversión argentina en el exterior</div>
        <ul>
            <li>Restricciones cambiarias vigentes del BCRA</li>
            <li>Declaración de activos en el exterior (Bienes Personales)</li>
            <li>Estructuras holding internacionales: requisitos y costos</li>
            <li>Custodia de fondos en el exterior: cuentas y activos financieros</li>
            <li>Declaración de beneficiario final (GAFI/FATF)</li>
        </ul>
    </div>
</div>

<!-- DUE DILIGENCE -->
<h2 class="adv-section-title">🔎 Due Diligence para Inversores</h2>

<div class="adv-card">
    <div class="adv-card-title">📂 Checklist de preparación para recibir inversión</div>
    <p>Antes de presentar el negocio a inversores, es fundamental tener en orden la documentación que permite evaluar el activo con confianza:</p>
    <ul>
        <li>✅ Estados contables de los últimos 3 años firmados por contador</li>
        <li>✅ Contratos vigentes (proveedores, clientes, locación)</li>
        <li>✅ Libro de actas societario actualizado</li>
        <li>✅ Registro de propiedad intelectual (marcas, patentes si aplica)</li>
        <li>✅ Nómina de personal y modalidades de contratación</li>
        <li>✅ Declaraciones juradas impositivas al día</li>
        <li>✅ Detalle de pasivos contingentes (juicios, reclamos pendientes)</li>
        <li>✅ Proyecciones financieras con supuestos explicitados</li>
    </ul>
</div>

<div class="adv-highlight adv-highlight-warning">
    <strong>Advertencia regulatoria:</strong> el marco cambiario y de inversiones en Argentina está sujeto a cambios frecuentes. Toda operación de ingreso o egreso de capitales debe consultarse con un especialista antes de ejecutarse para verificar la normativa vigente del BCRA y AFIP.
</div>

<!-- CTA BANNER -->
<div class="adv-cta-banner">
    <h2>¿Buscás financiamiento o inversores?</h2>
    <p>Te ayudamos a estructurar la propuesta de inversión, preparar el due diligence y seleccionar los instrumentos financieros más adecuados para tu negocio.</p>
    <div class="adv-cta-buttons">
        <a href="/contacto?tema=inversion" class="btn-cta-primary">📩 Consultar sobre inversión</a>
        <a href="/compliance" class="btn-cta-secondary">🛡️ Ver área Compliance →</a>
    </div>
</div>

<?php require __DIR__ . '/_layout_footer.php'; ?>
