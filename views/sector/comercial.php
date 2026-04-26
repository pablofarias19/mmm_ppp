<?php
/**
 * views/sector/comercial.php
 * Hub del Sector Comercial — Resumen, Institucional & Normativo, Radar Legal.
 */
session_start();
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../models/CommercialSector.php';
require_once __DIR__ . '/../../models/Chamber.php';
require_once __DIR__ . '/../../models/Agency.php';
require_once __DIR__ . '/../../models/PolicyLine.php';
require_once __DIR__ . '/../../models/Competency.php';
require_once __DIR__ . '/../../models/RadarLegal.php';
require_once __DIR__ . '/_layout.php';

use App\Models\CommercialSector;
use App\Models\Chamber;
use App\Models\Agency;
use App\Models\PolicyLine;
use App\Models\Competency;
use App\Models\RadarLegal;

$id = (int)($_GET['id'] ?? 0);
$tab = $_GET['tab'] ?? 'overview';

// Sin id: listar sectores comerciales
if (!$id) {
    $sectors = CommercialSector::getAll([], 100, 0);
    ?><!doctype html>
    <html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Sectores Comerciales — Mapita</title>
        <link rel="stylesheet" href="/css/map-styles.css">
        <style>
            body { font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; margin:0; background:#f3f4f6; color:#111827; }
            .wrap { max-width:900px; margin:0 auto; padding:28px 16px; }
            .topbar { display:flex; align-items:center; gap:12px; margin-bottom:20px; }
            .back-btn { display:inline-flex; align-items:center; gap:6px; padding:9px 14px; background:#111827; color:#fff; border-radius:10px; text-decoration:none; font-weight:800; font-size:14px; }
            .back-btn:hover { background:#374151; }
            h1 { margin:0; font-size:24px; }
            .grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:16px; }
            .card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:18px; }
            .card h3 { margin:0 0 6px; font-size:16px; }
            .card p { margin:0 0 12px; font-size:13px; color:#6b7280; }
            .badge { display:inline-block; padding:2px 9px; border-radius:9999px; font-size:11px; font-weight:700; margin-right:6px; }
            .badge-activo { background:#d1fae5; color:#065f46; }
            .badge-proyecto { background:#fef3c7; color:#78350f; }
            .badge-potencial { background:#e0e7ff; color:#3730a3; }
            .badge-radar { background:#fce7f3; color:#831843; }
            .btn-link { display:inline-block; padding:8px 14px; background:#4f46e5; color:#fff; border-radius:10px; text-decoration:none; font-weight:700; font-size:13px; }
            .btn-link:hover { background:#4338ca; }
            .empty { color:#6b7280; text-align:center; padding:40px; }
        </style>
    </head>
    <body>
    <div class="wrap">
        <div class="topbar">
            <a class="back-btn" href="/map">← Volver al mapa</a>
            <h1>🏪 Sectores Comerciales</h1>
        </div>
        <?php if (empty($sectors)): ?>
            <p class="empty">No hay sectores comerciales disponibles aún.</p>
        <?php else: ?>
        <div class="grid">
            <?php foreach ($sectors as $s): ?>
            <div class="card">
                <h3><?= htmlspecialchars($s['name']) ?></h3>
                <p><?= htmlspecialchars($s['description'] ?? '') ?></p>
                <span class="badge badge-<?= htmlspecialchars($s['status']) ?>"><?= htmlspecialchars(ucfirst($s['status'])) ?></span>
                <?php if ($s['radar_enabled']): ?><span class="badge badge-radar">🌐 Radar Legal</span><?php endif; ?>
                <div style="margin-top:12px;">
                    <a class="btn-link" href="/sector-comercial?id=<?= $s['id'] ?>">Ver sector →</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// Sector específico
$sector = CommercialSector::getById($id);
if (!$sector) {
    http_response_code(404);
    echo '<p>Sector no encontrado.</p>';
    exit;
}

$pageTitle = htmlspecialchars($sector['name']);

sectorHeader($pageTitle, 'commercial', $id, $tab);

// ── TAB: OVERVIEW ──────────────────────────────────────────────────────────────
if ($tab === 'overview' || !$tab):
?>
<div class="s-card">
    <div class="section-hdr">
        <h2>🏪 <?= htmlspecialchars($sector['name']) ?></h2>
        <span class="badge badge-<?= htmlspecialchars($sector['status']) ?>"><?= htmlspecialchars(ucfirst($sector['status'])) ?></span>
    </div>
    <p><?= nl2br(htmlspecialchars($sector['description'] ?? '')) ?></p>
    <div class="cta-row">
        <span class="badge badge-blue">Tipo: <?= htmlspecialchars(ucfirst($sector['type'])) ?></span>
        <?php if ($sector['subtype']): ?><span class="badge badge-gray"><?= htmlspecialchars($sector['subtype']) ?></span><?php endif; ?>
        <?php if ($sector['jurisdiction']): ?><span class="badge badge-purple">📍 <?= htmlspecialchars($sector['jurisdiction']) ?></span><?php endif; ?>
        <?php if ($sector['radar_enabled']): ?><span class="badge badge-amber">🌐 Radar Legal habilitado</span><?php endif; ?>
    </div>
</div>

<div class="grid-2">
    <div class="s-card">
        <h3>🏛️ Institucional &amp; Normativo</h3>
        <p class="muted">Cámaras, Agencias, Líneas de política y Mapa de facultades vinculadas a este sector.</p>
        <div class="cta-row">
            <a class="btn btn-primary" href="/sector-comercial?id=<?= $id ?>&tab=institucional">Ver módulo →</a>
        </div>
    </div>
    <?php if ($sector['radar_enabled']): ?>
    <div class="s-card">
        <h3>🌐 Radar Legal</h3>
        <p class="muted">Comercio internacional, aduana, transporte, destinaciones, restricciones y contratos.</p>
        <div class="cta-row">
            <a class="btn btn-primary" href="/sector-comercial?id=<?= $id ?>&tab=radar">Ver Radar Legal →</a>
        </div>
    </div>
    <?php else: ?>
    <div class="s-card" style="opacity:.6;">
        <h3>🌐 Radar Legal</h3>
        <p class="muted">No habilitado para este sector. Contactá al administrador para activarlo.</p>
    </div>
    <?php endif; ?>
</div>

<?php
// ── TAB: INSTITUCIONAL ─────────────────────────────────────────────────────────
elseif ($tab === 'institucional'):
    $chambers     = Chamber::getBySector('commercial', $id);
    $agencies     = Agency::getBySector('commercial', $id);
    $linesPropia  = PolicyLine::getBySector('commercial', $id, ['line_type' => 'propia',   'status' => 'vigente']);
    $linesGob     = PolicyLine::getBySector('commercial', $id, ['line_type' => 'gobierno', 'status' => 'vigente']);
    $competencias = Competency::getBySector('commercial', $id);

    $roleLabels = [
        'aprobar'    => '✅ Aprobar',
        'rechazar'   => '❌ Rechazar',
        'controlar'  => '🔍 Controlar',
        'auditar'    => '📋 Auditar',
        'sancionar'  => '⚖️ Sancionar',
        'dictamen'   => '📄 Dictamen',
        'emitir'     => '📤 Emitir',
        'fiscalizar' => '🏛️ Fiscalizar',
    ];
?>
<!-- CAMARAS -->
<div class="s-card">
    <h2>🏛️ Cámaras vinculadas</h2>
    <?php if (empty($chambers)): ?>
        <p class="muted">No hay cámaras vinculadas a este sector.</p>
    <?php else: ?>
    <div class="grid-2">
        <?php foreach ($chambers as $ch): ?>
        <div style="border:1px solid #e5e7eb;border-radius:10px;padding:14px;">
            <h3 style="margin:0 0 4px;"><?= htmlspecialchars($ch['name']) ?></h3>
            <span class="badge badge-<?= $ch['status']==='activa' ? 'green' : 'gray' ?>"><?= htmlspecialchars(ucfirst($ch['status'])) ?></span>
            <span class="badge badge-blue" style="margin-left:4px;"><?= htmlspecialchars($ch['area']) ?></span>
            <p class="muted" style="margin-top:8px;"><?= htmlspecialchars($ch['description'] ?? '') ?></p>
            <?php if ($ch['website']): ?>
                <a href="<?= htmlspecialchars($ch['website']) ?>" target="_blank" rel="noopener noreferrer" class="muted" style="font-size:12px;">🔗 Sitio web</a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- AGENCIAS -->
<div class="s-card">
    <h2>🏢 Agencias vinculadas</h2>
    <?php if (empty($agencies)): ?>
        <p class="muted">No hay agencias vinculadas a este sector.</p>
    <?php else: ?>
    <div class="grid-2">
        <?php foreach ($agencies as $ag): ?>
        <div style="border:1px solid #e5e7eb;border-radius:10px;padding:14px;">
            <h3 style="margin:0 0 4px;"><?= htmlspecialchars($ag['name']) ?></h3>
            <span class="badge badge-<?= $ag['status']==='activa' ? 'green' : 'gray' ?>"><?= htmlspecialchars(ucfirst($ag['status'])) ?></span>
            <span class="badge badge-purple" style="margin-left:4px;"><?= htmlspecialchars($ag['area']) ?></span>
            <p class="muted" style="margin-top:8px;"><?= htmlspecialchars($ag['description'] ?? '') ?></p>
            <?php if ($ag['website']): ?>
                <a href="<?= htmlspecialchars($ag['website']) ?>" target="_blank" rel="noopener noreferrer" class="muted" style="font-size:12px;">🔗 Sitio web</a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- LINEAS PROPIAS -->
<div class="s-card">
    <h2>📋 Líneas Propias (Cámaras / Agencias)</h2>
    <?php if (empty($linesPropia)): ?>
        <p class="muted">No hay líneas propias vigentes para este sector.</p>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>Título</th><th>Área</th><th>Origen</th><th>Jurisdicción</th><th>Publicación</th><th>Vigencia</th></tr></thead>
        <tbody>
        <?php foreach ($linesPropia as $l): ?>
        <tr>
            <td><?php if ($l['source_link']): ?><a href="<?= htmlspecialchars($l['source_link']) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($l['title']) ?></a><?php else: ?><?= htmlspecialchars($l['title']) ?><?php endif; ?>
                <?php if ($l['summary']): ?><br><span class="muted"><?= htmlspecialchars($l['summary']) ?></span><?php endif; ?></td>
            <td><?= htmlspecialchars($l['area'] ?? '') ?></td>
            <td><span class="badge badge-blue"><?= htmlspecialchars(ucfirst($l['source_type'])) ?></span></td>
            <td><?= htmlspecialchars($l['jurisdiction'] ?? '') ?></td>
            <td><?= $l['published_at'] ? htmlspecialchars($l['published_at']) : '' ?></td>
            <td><?= $l['valid_from'] ? htmlspecialchars($l['valid_from']) : '' ?><?= $l['valid_until'] ? ' → ' . htmlspecialchars($l['valid_until']) : '' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- LINEAS GOBIERNO -->
<div class="s-card">
    <h2>🏛️ Líneas de Gobierno (Leyes / Políticas)</h2>
    <?php if (empty($linesGob)): ?>
        <p class="muted">No hay líneas de gobierno vigentes para este sector.</p>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>Título</th><th>Área</th><th>Jurisdicción</th><th>Publicación</th><th>Vigencia</th><th>Tags</th></tr></thead>
        <tbody>
        <?php foreach ($linesGob as $l): ?>
        <tr>
            <td><?php if ($l['source_link']): ?><a href="<?= htmlspecialchars($l['source_link']) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($l['title']) ?></a><?php else: ?><?= htmlspecialchars($l['title']) ?><?php endif; ?>
                <?php if ($l['summary']): ?><br><span class="muted"><?= htmlspecialchars($l['summary']) ?></span><?php endif; ?></td>
            <td><?= htmlspecialchars($l['area'] ?? '') ?></td>
            <td><?= htmlspecialchars($l['jurisdiction'] ?? '') ?></td>
            <td><?= $l['published_at'] ? htmlspecialchars($l['published_at']) : '' ?></td>
            <td><?= $l['valid_from'] ? htmlspecialchars($l['valid_from']) : '' ?><?= $l['valid_until'] ? ' → ' . htmlspecialchars($l['valid_until']) : '' ?></td>
            <td><span class="muted"><?= htmlspecialchars($l['tags'] ?? '') ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- MAPA DE FACULTADES -->
<div class="s-card">
    <h2>⚖️ Mapa de Facultades / Competencias</h2>
    <?php if (empty($competencias)): ?>
        <p class="muted">No hay competencias definidas para este sector.</p>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>Facultad</th><th>Organismo</th><th>Órgano</th><th>Responsable</th><th>Alcance</th><th>Fundamento</th></tr></thead>
        <tbody>
        <?php foreach ($competencias as $c): ?>
        <tr>
            <td><span class="badge badge-amber"><?= $roleLabels[$c['role']] ?? htmlspecialchars(ucfirst($c['role'])) ?></span></td>
            <td><?= htmlspecialchars($c['organism']) ?></td>
            <td><?= htmlspecialchars($c['organ'] ?? '') ?></td>
            <td><?= htmlspecialchars($c['responsible'] ?? '') ?></td>
            <td class="muted"><?= htmlspecialchars($c['scope'] ?? '') ?></td>
            <td class="muted"><?= htmlspecialchars($c['legal_basis'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<div class="s-card" style="background:linear-gradient(135deg,#4f46e5 0%,#7c3aed 100%);color:white;">
    <h2 style="color:white;margin-bottom:8px;">📩 ¿Necesitás asesoramiento legal?</h2>
    <p style="color:rgba(255,255,255,.9);">Nuestro equipo puede orientarte sobre líneas normativas, facultades y procedimientos aplicables a tu sector.</p>
    <div class="cta-row">
        <a class="btn btn-secondary" href="/contacto?tema=juridico">Consultar ahora</a>
    </div>
</div>

<?php
// ── TAB: RADAR LEGAL ────────────────────────────────────────────────────────────
elseif ($tab === 'radar'):
    if (!RadarLegal::isEnabled('commercial', $id)):
?>
<div class="s-card" style="text-align:center;padding:40px;">
    <h2>🌐 Radar Legal</h2>
    <p class="muted">El módulo Radar Legal no está habilitado para este sector.<br>Contactá al administrador para activarlo.</p>
    <div class="cta-row" style="justify-content:center;">
        <a class="btn btn-primary" href="/contacto">Solicitar activación</a>
    </div>
</div>
<?php else:
    $transportModes = RadarLegal::getTransportModes();
    $ports          = RadarLegal::getPorts();
    $destinations   = RadarLegal::getDestinations();
    $restrictions   = RadarLegal::getRestrictions();
    $disputes       = RadarLegal::getDisputes();
    $contracts      = RadarLegal::getContractTypes();
    $portsByMode    = [];
    foreach ($ports as $p) $portsByMode[$p['transport_mode_id']][] = $p;
    $destByDir = ['importacion' => [], 'exportacion' => []];
    foreach ($destinations as $d) $destByDir[$d['direction']][] = $d;
?>
<div class="s-card">
    <h2>🌐 Radar Legal — Comercio Internacional &amp; Aduana</h2>
    <p class="muted">Módulo de consulta sobre operaciones de comercio exterior, destinaciones aduaneras, restricciones, controversias y contratos internacionales.</p>
</div>

<!-- TABS internas Radar -->
<div id="radar-tabs" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:16px;">
    <button class="btn btn-primary" data-tab="transporte" onclick="showRadarTab('transporte')">🚢 Transporte</button>
    <button class="btn btn-secondary" data-tab="destinaciones" onclick="showRadarTab('destinaciones')">📦 Destinaciones</button>
    <button class="btn btn-secondary" data-tab="restricciones" onclick="showRadarTab('restricciones')">🚫 Restricciones</button>
    <button class="btn btn-secondary" data-tab="controversias" onclick="showRadarTab('controversias')">⚠️ Controversias</button>
    <button class="btn btn-secondary" data-tab="contratos" onclick="showRadarTab('contratos')">📝 Contratos</button>
</div>

<!-- TRANSPORTE -->
<div id="rt-transporte" class="radar-tab-content s-card">
    <h2>🚢 Modos de Transporte</h2>
    <div class="grid-2">
    <?php foreach ($transportModes as $tm):
        $modeIcon = ['maritimo'=>'🚢','aereo'=>'✈️','terrestre'=>'🚛','multimodal'=>'🔀'][$tm['mode']] ?? '🚚';
    ?>
    <div style="border:1px solid #e5e7eb;border-radius:10px;padding:14px;">
        <h3><?= $modeIcon ?> <?= htmlspecialchars($tm['name']) ?></h3>
        <p class="muted"><?= htmlspecialchars($tm['description'] ?? '') ?></p>
        <?php if (!empty($portsByMode[$tm['id']])): ?>
        <p style="font-size:13px;font-weight:700;margin-top:10px;margin-bottom:4px;">Puertos asignados:</p>
        <ul style="margin:0;padding-left:18px;font-size:13px;">
            <?php foreach ($portsByMode[$tm['id']] as $po): ?>
            <li><?= htmlspecialchars($po['name']) ?><?= $po['country'] ? ' — ' . htmlspecialchars($po['country']) : '' ?><?= $po['un_locode'] ? ' <span class="badge badge-gray">' . htmlspecialchars($po['un_locode']) . '</span>' : '' ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    </div>
</div>

<!-- DESTINACIONES -->
<div id="rt-destinaciones" class="radar-tab-content s-card" style="display:none;">
    <h2>📦 Tipos de Destinación Aduanera</h2>
    <div class="grid-2">
        <div>
            <h3 style="color:#065f46;">📥 Importación</h3>
            <?php foreach ($destByDir['importacion'] as $d): ?>
            <div style="border-bottom:1px solid #f3f4f6;padding:10px 0;">
                <strong><?= htmlspecialchars($d['name']) ?></strong>
                <?php if ($d['code']): ?><span class="badge badge-blue" style="margin-left:6px;"><?= htmlspecialchars($d['code']) ?></span><?php endif; ?>
                <p class="muted"><?= htmlspecialchars($d['description'] ?? '') ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <div>
            <h3 style="color:#1e40af;">📤 Exportación</h3>
            <?php foreach ($destByDir['exportacion'] as $d): ?>
            <div style="border-bottom:1px solid #f3f4f6;padding:10px 0;">
                <strong><?= htmlspecialchars($d['name']) ?></strong>
                <?php if ($d['code']): ?><span class="badge badge-blue" style="margin-left:6px;"><?= htmlspecialchars($d['code']) ?></span><?php endif; ?>
                <p class="muted"><?= htmlspecialchars($d['description'] ?? '') ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- RESTRICCIONES -->
<div id="rt-restricciones" class="radar-tab-content s-card" style="display:none;">
    <h2>🚫 Restricciones &amp; Medidas Parancelarias</h2>
    <?php if (empty($restrictions)): ?><p class="muted">Sin datos.</p><?php else: ?>
    <table class="data-table">
        <thead><tr><th>Tipo</th><th>Nombre</th><th>Fundamento Legal</th><th>Descripción</th><th>Vigencia</th></tr></thead>
        <tbody>
        <?php
        $rlabels = [
            'prohibicion'             => '🚫 Prohibición',
            'dumping'                 => '⬇️ Dumping',
            'licencia_automatica'     => '✅ Lic. Automática',
            'licencia_no_automatica'  => '⏳ Lic. No Automática',
            'cuota'                   => '📊 Cuota',
            'otro'                    => '📌 Otro',
        ];
        foreach ($restrictions as $r): ?>
        <tr>
            <td><span class="badge badge-red"><?= $rlabels[$r['restriction_type']] ?? htmlspecialchars($r['restriction_type']) ?></span></td>
            <td><?= htmlspecialchars($r['name']) ?></td>
            <td class="muted"><?= htmlspecialchars($r['legal_basis'] ?? '') ?></td>
            <td class="muted"><?= htmlspecialchars($r['description'] ?? '') ?></td>
            <td class="muted"><?= $r['valid_from'] ? htmlspecialchars($r['valid_from']) : '' ?><?= $r['valid_until'] ? ' → ' . htmlspecialchars($r['valid_until']) : '' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- CONTROVERSIAS -->
<div id="rt-controversias" class="radar-tab-content s-card" style="display:none;">
    <h2>⚠️ Controversias &amp; Delitos Aduaneros</h2>
    <?php if (empty($disputes)): ?><p class="muted">Sin datos.</p><?php else: ?>
    <table class="data-table">
        <thead><tr><th>Tipo</th><th>Nombre</th><th>Fundamento</th><th>Descripción</th><th>Sanciones</th></tr></thead>
        <tbody>
        <?php
        $dlabels = [
            'infraccion_aduanera'     => '📋 Infracción Aduanera',
            'incumplimiento_normativo'=> '⚠️ Incumplimiento',
            'delito_aduanero'         => '🚔 Delito Aduanero',
            'otro'                    => '📌 Otro',
        ];
        foreach ($disputes as $d): ?>
        <tr>
            <td><span class="badge badge-amber"><?= $dlabels[$d['dispute_type']] ?? htmlspecialchars($d['dispute_type']) ?></span></td>
            <td><?= htmlspecialchars($d['name']) ?></td>
            <td class="muted"><?= htmlspecialchars($d['legal_basis'] ?? '') ?></td>
            <td class="muted"><?= htmlspecialchars($d['description'] ?? '') ?></td>
            <td class="muted"><?= htmlspecialchars($d['sanction_range'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- CONTRATOS -->
<div id="rt-contratos" class="radar-tab-content s-card" style="display:none;">
    <h2>📝 Tipos de Contrato Internacional</h2>
    <div class="grid-2">
    <?php
    $clabels = [
        'compraventa'          => '🛒 Compraventa',
        'llave_en_mano'        => '🔑 Llave en Mano',
        'agencia'              => '🤝 Agencia',
        'distribucion'         => '📦 Distribución',
        'inversion_activos'    => '🏭 Inversión Activos',
        'inversion_financiera' => '💰 Inversión Financiera',
        'joint_venture'        => '🤝 Joint Venture',
        'otro'                 => '📄 Otro',
    ];
    foreach ($contracts as $ct): ?>
    <div style="border:1px solid #e5e7eb;border-radius:10px;padding:14px;">
        <h3><?= $clabels[$ct['category']] ?? '' ?> <?= htmlspecialchars($ct['name']) ?></h3>
        <p class="muted"><?= htmlspecialchars($ct['description'] ?? '') ?></p>
        <?php if ($ct['key_points']): ?>
        <p style="font-size:13px;font-weight:700;margin-bottom:4px;">Puntos clave:</p>
        <p class="muted"><?= htmlspecialchars($ct['key_points']) ?></p>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    </div>
</div>

<div class="s-card" style="background:linear-gradient(135deg,#1e40af 0%,#1d4ed8 100%);color:white;">
    <h2 style="color:white;margin-bottom:8px;">⚖️ ¿Necesitás asesoramiento en Comercio Internacional?</h2>
    <p style="color:rgba(255,255,255,.9);">Nuestro equipo te guía en operaciones de importación/exportación, contratos internacionales y cumplimiento aduanero.</p>
    <div class="cta-row">
        <a class="btn btn-secondary" href="/contacto?tema=juridico">Consultar ahora</a>
    </div>
</div>

<script>
function showRadarTab(tab) {
    document.querySelectorAll('.radar-tab-content').forEach(el => el.style.display = 'none');
    document.getElementById('rt-' + tab).style.display = 'block';
    document.querySelectorAll('#radar-tabs .btn').forEach(b => {
        b.className = (b.dataset.tab === tab) ? 'btn btn-primary' : 'btn btn-secondary';
    });
}
</script>
<?php endif; ?>
<?php endif; ?>

<?php sectorFooter(); ?>
