<?php
/**
 * includes/avanzado_panel.php
 * Panel "Avanzado →" — Acordeón de Asistencia Jurídica y Estratégica.
 * Reutilizable en formularios de negocio, marca e industria.
 *
 * @param string $entityType  'negocio' | 'marca' | 'industria'
 * @param string $activeBizType  Tipo de negocio preseleccionado: 'comercio'|'servicios'|'industria'|''
 */
function renderAvanzadoPanel(string $entityType = 'negocio', string $activeBizType = ''): void
{
    $uniq = 'advpanel_' . substr(md5($entityType . mt_rand()), 0, 6);
    $initialType = htmlspecialchars($activeBizType, ENT_QUOTES);
    ?>
<style>
/* ── Avanzado Panel ─────────────────────────────────────────────── */
.adv-panel-wrap {
    margin: 24px 0 8px;
    border-radius: 14px;
    border: 1.5px solid #c7d2fe;
    background: #f8f9ff;
    overflow: visible;
}
.adv-panel-toggle {
    width: 100%; display: flex; align-items: center; justify-content: space-between;
    gap: 12px; padding: 14px 20px;
    background: linear-gradient(135deg, #1B3B6F 0%, #0d2247 100%);
    color: #fff; border: none; border-radius: 12px;
    cursor: pointer; text-align: left;
    transition: background .2s;
}
.adv-panel-toggle:hover { background: linear-gradient(135deg, #2d4f8f 0%, #1B3B6F 100%); }
.adv-panel-toggle-label { display: flex; align-items: center; gap: 10px; }
.adv-panel-toggle-label .adv-icon { font-size: 1.3em; }
.adv-panel-toggle-label strong { font-size: 1em; letter-spacing: .2px; }
.adv-panel-toggle-label .adv-sub { font-size: .8em; opacity: .8; }
.adv-chevron { font-size: .85em; transition: transform .25s; flex-shrink: 0; }
.adv-panel-toggle[aria-expanded="true"] .adv-chevron { transform: rotate(180deg); }
.adv-panel-body {
    padding: 18px 20px 20px;
    background: #fff;
    border-radius: 0 0 12px 12px;
    border-top: 1.5px solid #e0e7ff;
}
.adv-intro {
    margin: 0 0 16px; font-size: .88em; color: #374151; line-height: 1.55;
    padding: 10px 14px; background: #eef2ff; border-radius: 8px;
    border-left: 3px solid #6366f1;
}
/* Type selector */
.adv-type-row {
    display: flex; flex-wrap: wrap; gap: 8px; margin: 0 0 18px;
}
.adv-type-btn {
    padding: 7px 14px; border-radius: 20px;
    border: 1.5px solid #d1d5db; background: #f9fafb;
    color: #374151; font-size: .83em; font-weight: 700;
    cursor: pointer; transition: all .18s;
}
.adv-type-btn:hover { border-color: #a5b4fc; background: #f0f4ff; }
.adv-type-btn.active { border-color: #1B3B6F; background: #eef2ff; color: #1B3B6F; }
/* Accordion sections */
.adv-section {
    border: 1px solid #e5e7eb; border-radius: 10px;
    margin-bottom: 8px; overflow: visible;
}
.adv-section-toggle {
    width: 100%; display: flex; align-items: center; justify-content: space-between;
    gap: 8px; padding: 11px 14px;
    background: #f8fafc; border: none; border-radius: 10px;
    cursor: pointer; text-align: left;
    font-size: .87em; font-weight: 700; color: #1f2937;
    transition: background .15s;
}
.adv-section-toggle:hover { background: #f0f4ff; }
.adv-section-toggle .adv-s-chevron { font-size: .75em; transition: transform .2s; flex-shrink: 0; color: #9ca3af; }
.adv-section-toggle[aria-expanded="true"] .adv-s-chevron { transform: rotate(180deg); }
.adv-section-body { display: none; padding: 12px 14px 14px; }
.adv-section-body.open { display: block; }
.adv-section-body ul { margin: 0; padding-left: 18px; }
.adv-section-body li { font-size: .84em; color: #374151; margin-bottom: 4px; line-height: 1.5; }
.adv-section-body .adv-tag {
    display: inline-block; padding: 2px 8px; border-radius: 10px;
    font-size: .75em; font-weight: 700; margin-right: 4px; margin-bottom: 4px;
    background: #fef3c7; color: #92400e;
}
/* Type-specific block */
.adv-type-block { display: none; margin-top: 14px; padding: 12px 14px; border-radius: 10px; background: #f0fdf4; border: 1px solid #bbf7d0; }
.adv-type-block.visible { display: block; }
.adv-type-block h4 { margin: 0 0 8px; font-size: .88em; color: #14532d; }
.adv-type-block ul { margin: 0; padding-left: 18px; }
.adv-type-block li { font-size: .83em; color: #166534; margin-bottom: 3px; }
/* CTA row */
.adv-cta-row {
    display: flex; flex-wrap: wrap; gap: 10px;
    margin-top: 18px; padding-top: 14px;
    border-top: 1px solid #e5e7eb;
}
.adv-cta-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 9px 16px; border-radius: 10px;
    background: #1B3B6F; color: #fff;
    text-decoration: none; font-weight: 700; font-size: .83em;
    transition: background .15s;
}
.adv-cta-btn:hover { background: #0d2247; }
.adv-cta-btn.sec {
    background: #f3f4f6; color: #1f2937;
    border: 1px solid #e5e7eb;
}
.adv-cta-btn.sec:hover { background: #e5e7eb; }
@media (max-width: 480px) {
    .adv-panel-toggle-label .adv-sub { display: none; }
    .adv-panel-body { padding: 14px 14px 16px; }
}
</style>

<div class="adv-panel-wrap">
    <button type="button" class="adv-panel-toggle"
            id="<?= $uniq ?>_toggle"
            aria-expanded="false"
            aria-controls="<?= $uniq ?>_body"
            onclick="advTogglePanel('<?= $uniq ?>')">
        <span class="adv-panel-toggle-label">
            <span class="adv-icon">📊</span>
            <strong>Avanzado →</strong>
            <span class="adv-sub">Asistencia jurídica, fiscal y estratégica</span>
        </span>
        <span class="adv-chevron">▼</span>
    </button>

    <div class="adv-panel-body" id="<?= $uniq ?>_body" hidden>
        <p class="adv-intro">
            Este negocio<?php if ($entityType === 'marca') echo ', marca'; elseif ($entityType === 'industria') echo ', industria'; ?> puede desarrollarse bajo un buen plan estratégico.
            Los aspectos legales, financieros y operativos son fundamentales para su crecimiento, protección y escalabilidad.
        </p>

        <?php if ($entityType !== 'marca'): ?>
        <!-- Tipo de clasificación -->
        <div style="margin-bottom:12px;">
            <div style="font-size:.8em;font-weight:700;color:#6b7280;margin-bottom:6px;text-transform:uppercase;letter-spacing:.3px;">Clasificación</div>
            <div class="adv-type-row">
                <button type="button" class="adv-type-btn <?= $initialType === 'comercio' ? 'active' : '' ?>"
                        onclick="advSetType('<?= $uniq ?>', 'comercio', this)">🏪 Comercio</button>
                <button type="button" class="adv-type-btn <?= $initialType === 'servicios' ? 'active' : '' ?>"
                        onclick="advSetType('<?= $uniq ?>', 'servicios', this)">🔧 Servicios</button>
                <button type="button" class="adv-type-btn <?= ($initialType === 'industria' || $entityType === 'industria') ? 'active' : '' ?>"
                        onclick="advSetType('<?= $uniq ?>', 'industria', this)">🏭 Industria</button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Secciones del módulo -->

        <!-- 4.1 Diagnóstico Integral -->
        <div class="adv-section">
            <button type="button" class="adv-section-toggle"
                    aria-expanded="false"
                    onclick="advToggleSection(this)">
                <span>🔍 Diagnóstico Integral</span>
                <span class="adv-s-chevron">▼</span>
            </button>
            <div class="adv-section-body">
                <ul>
                    <li>Estado actual del negocio y nivel de formalización</li>
                    <li>Riesgos visibles e invisibles</li>
                    <li>Potencial de crecimiento y escalabilidad</li>
                </ul>
            </div>
        </div>

        <!-- 4.2 Arquitectura Legal y Patrimonial -->
        <div class="adv-section">
            <button type="button" class="adv-section-toggle"
                    aria-expanded="false"
                    onclick="advToggleSection(this)">
                <span>⚖️ Arquitectura Legal y Patrimonial</span>
                <span class="adv-s-chevron">▼</span>
            </button>
            <div class="adv-section-body">
                <ul>
                    <li>Tipo societario recomendado (SA, SRL, SAS, monotributo…)</li>
                    <li>Separación patrimonial personal/empresarial</li>
                    <li>Uso de fideicomisos para protección de activos</li>
                    <li>Protección frente a acreedores</li>
                    <li>Identificación de riesgos legales</li>
                </ul>
                <div style="margin-top:10px;">
                    <a href="/juridico" target="_blank" rel="noopener noreferrer"
                       style="font-size:.8em;color:#1B3B6F;font-weight:700;">⚖️ Ver módulo Jurídico →</a>
                </div>
            </div>
        </div>

        <!-- 4.3 Estructura Fiscal y Contable -->
        <div class="adv-section">
            <button type="button" class="adv-section-toggle"
                    aria-expanded="false"
                    onclick="advToggleSection(this)">
                <span>🧾 Estructura Fiscal y Contable</span>
                <span class="adv-s-chevron">▼</span>
            </button>
            <div class="adv-section-body">
                <ul>
                    <li>Régimen impositivo aplicable (Monotributo, Responsable Inscripto…)</li>
                    <li>Optimización tributaria y planificación impositiva</li>
                    <li>Riesgos fiscales y contingencias</li>
                </ul>
                <div style="margin-top:10px;">
                    <a href="/fiscal" target="_blank" rel="noopener noreferrer"
                       style="font-size:.8em;color:#1B3B6F;font-weight:700;">🧾 Ver módulo Fiscal →</a>
                </div>
            </div>
        </div>

        <!-- 4.4 Sistema Bancario y Financiero -->
        <div class="adv-section">
            <button type="button" class="adv-section-toggle"
                    aria-expanded="false"
                    onclick="advToggleSection(this)">
                <span>🏦 Sistema Bancario y Financiero</span>
                <span class="adv-s-chevron">▼</span>
            </button>
            <div class="adv-section-body">
                <ul>
                    <li>Bancarización y apertura de cuentas empresariales</li>
                    <li>Flujo de fondos y gestión de liquidez</li>
                    <li>Acceso a créditos y líneas de financiamiento</li>
                    <li>Riesgos: UIF, bloqueos, inconsistencias</li>
                </ul>
                <div style="margin-top:10px;">
                    <a href="/inversion" target="_blank" rel="noopener noreferrer"
                       style="font-size:.8em;color:#1B3B6F;font-weight:700;">📈 Ver módulo Inversión →</a>
                </div>
            </div>
        </div>

        <!-- 4.5 Cobranzas -->
        <div class="adv-section">
            <button type="button" class="adv-section-toggle"
                    aria-expanded="false"
                    onclick="advToggleSection(this)">
                <span>💰 Sistema de Cobranzas</span>
                <span class="adv-s-chevron">▼</span>
            </button>
            <div class="adv-section-body">
                <ul>
                    <li>Políticas de crédito a clientes y gestión de mora</li>
                    <li>Ejecución judicial de deudas</li>
                    <li>Instrumentos: pagaré, cheque, contrato ejecutivo</li>
                </ul>
            </div>
        </div>

        <!-- 4.8 Compliance -->
        <div class="adv-section">
            <button type="button" class="adv-section-toggle"
                    aria-expanded="false"
                    onclick="advToggleSection(this)">
                <span>🛡️ Compliance y Prevención</span>
                <span class="adv-s-chevron">▼</span>
            </button>
            <div class="adv-section-body">
                <ul>
                    <li>Programas internos de cumplimiento normativo</li>
                    <li>Debida diligencia con clientes y proveedores</li>
                    <li>Prevención de lavado de activos (UIF)</li>
                    <li>Responsabilidad penal empresaria (Ley 27.401)</li>
                </ul>
                <div style="margin-top:10px;">
                    <a href="/compliance" target="_blank" rel="noopener noreferrer"
                       style="font-size:.8em;color:#1B3B6F;font-weight:700;">🛡️ Ver módulo Compliance →</a>
                </div>
            </div>
        </div>

        <!-- 4.15-4.16 Expansión y Valor -->
        <div class="adv-section">
            <button type="button" class="adv-section-toggle"
                    aria-expanded="false"
                    onclick="advToggleSection(this)">
                <span>🚀 Expansión Estratégica y Valor de Marca</span>
                <span class="adv-s-chevron">▼</span>
            </button>
            <div class="adv-section-body">
                <ul>
                    <li>Escalabilidad, diversificación y nuevas unidades</li>
                    <li>Valor económico del activo marcario e intangibles</li>
                    <li>Franquicias, licencias de uso y joint ventures</li>
                    <li>Escenarios estratégicos: conservador, expansivo, agresivo</li>
                </ul>
                <div style="margin-top:10px;">
                    <a href="/marca-expansion" target="_blank" rel="noopener noreferrer"
                       style="font-size:.8em;color:#1B3B6F;font-weight:700;">🚀 Ver módulo Marca y Expansión →</a>
                    &nbsp;
                    <a href="/tasacion" target="_blank" rel="noopener noreferrer"
                       style="font-size:.8em;color:#1B3B6F;font-weight:700;">💎 Tasación de Marcas →</a>
                </div>
            </div>
        </div>

        <!-- Especialización por tipo -->
        <div class="adv-type-block <?= $initialType === 'comercio' ? 'visible' : '' ?>" id="<?= $uniq ?>_t_comercio" style="background:#fffbeb;border-color:#fde68a;">
            <h4>🏪 COMERCIO — Claves específicas</h4>
            <ul>
                <li>Defensa del consumidor: ley 24.240 y regulaciones provinciales</li>
                <li>Gestión de stock, logística y devoluciones</li>
                <li>Cobranza y medios de pago: POS, billeteras, crédito</li>
                <li>Riesgos: reclamos, fraude comercial, habilitaciones municipales</li>
            </ul>
        </div>
        <div class="adv-type-block <?= $initialType === 'servicios' ? 'visible' : '' ?>" id="<?= $uniq ?>_t_servicios" style="background:#f0f9ff;border-color:#bae6fd;">
            <h4>🔧 SERVICIOS — Claves específicas</h4>
            <ul>
                <li>Contrato de servicios y responsabilidad profesional</li>
                <li>Matrícula profesional y habilitaciones</li>
                <li>Cobranza de honorarios y gestión de mora</li>
                <li>Riesgos: mala praxis, incumplimiento contractual</li>
            </ul>
        </div>
        <div class="adv-type-block <?= ($initialType === 'industria' || $entityType === 'industria') ? 'visible' : '' ?>" id="<?= $uniq ?>_t_industria">
            <h4>🏭 INDUSTRIA — Claves específicas</h4>
            <ul>
                <li>Permisos industriales y habilitación de planta</li>
                <li>Impacto ambiental: regulación provincial y municipal</li>
                <li>Contratos de locación industrial y bienes registrables</li>
                <li>Riesgos: clausura, multas, ART, seguros de daños</li>
            </ul>
        </div>

        <!-- CTAs -->
        <div class="adv-cta-row">
            <a href="/avanzado" target="_blank" rel="noopener noreferrer" class="adv-cta-btn">
                📊 Módulo Avanzado
            </a>
            <a href="https://fariasortiz.com.ar/contact.html" target="_blank" rel="noopener noreferrer" class="adv-cta-btn sec">
                📩 Consultar con especialista
            </a>
        </div>
    </div><!-- /adv-panel-body -->
</div><!-- /adv-panel-wrap -->

<script>
(function() {
    function advTogglePanel(id) {
        var toggle = document.getElementById(id + '_toggle');
        var body   = document.getElementById(id + '_body');
        if (!toggle || !body) return;
        var isOpen = toggle.getAttribute('aria-expanded') === 'true';
        toggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
        body.hidden = isOpen;
    }
    function advToggleSection(btn) {
        var body = btn.nextElementSibling;
        if (!body) return;
        var isOpen = btn.getAttribute('aria-expanded') === 'true';
        btn.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
        body.classList.toggle('open', !isOpen);
    }
    function advSetType(panelId, type, btn) {
        // Update button active state
        var row = btn.parentElement;
        row.querySelectorAll('.adv-type-btn').forEach(function(b) { b.classList.remove('active'); });
        btn.classList.add('active');
        // Show/hide type blocks
        ['comercio','servicios','industria'].forEach(function(t) {
            var block = document.getElementById(panelId + '_t_' + t);
            if (block) block.classList.toggle('visible', t === type);
        });
    }
    // Expose globally so onclick attributes work
    window.advTogglePanel    = advTogglePanel;
    window.advToggleSection  = advToggleSection;
    window.advSetType        = advSetType;

    // Hook into business type radio changes if present (add_business.php)
    document.addEventListener('DOMContentLoaded', function() {
        var radios = document.querySelectorAll('input[name="business_type"]');
        radios.forEach(function(r) {
            r.addEventListener('change', function() {
                var val = r.value ? r.value.toLowerCase() : '';
                var type = 'servicios';
                if (/comercio|tienda|kiosk|farmacia|supermercado|ferreteria|libreria|ropa|calzado|bazar|joyeria|optica|bicicleteria|jugueteria|muebler|colchon|electrodomestico|herramientas/.test(val)) {
                    type = 'comercio';
                } else if (/industria|fabrica|taller|manufactura|produccion|aserradero|frigorífico|imprenta|metalurgica/.test(val)) {
                    type = 'industria';
                }
                // Update all adv panels on page
                document.querySelectorAll('.adv-type-btn').forEach(function(btn) {
                    var btnType = btn.textContent.toLowerCase();
                    if (btnType.includes(type)) {
                        btn.click();
                    }
                });
            });
        });
    });
})();
</script>
<?php } ?>
