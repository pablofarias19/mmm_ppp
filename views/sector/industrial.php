<?php
/**
 * views/sector/industrial.php
 * Hub del Sector Industrial ampliado — Institucional & Normativo, Radar Legal.
 */
session_start();
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../models/IndustrialSector.php';
require_once __DIR__ . '/../../models/Chamber.php';
require_once __DIR__ . '/../../models/Agency.php';
require_once __DIR__ . '/../../models/PolicyLine.php';
require_once __DIR__ . '/../../models/Competency.php';
require_once __DIR__ . '/../../models/RadarLegal.php';
require_once __DIR__ . '/_layout.php';

use App\Models\IndustrialSector;
use App\Models\Chamber;
use App\Models\Agency;
use App\Models\PolicyLine;
use App\Models\Competency;
use App\Models\RadarLegal;

$id  = (int)($_GET['id'] ?? 0);
$tab = $_GET['tab'] ?? 'overview';

if (!$id) {
    $sectors = IndustrialSector::getAll([], 100, 0);
    ?><!doctype html>
    <html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Sectores Industriales — Mapita</title>
    <link rel="stylesheet" href="/css/map-styles.css">
    <style>
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;margin:0;background:#f3f4f6;color:#111827}
        .wrap{max-width:900px;margin:0 auto;padding:28px 16px}
        .topbar{display:flex;align-items:center;gap:12px;margin-bottom:20px}
        .back-btn{display:inline-flex;align-items:center;gap:6px;padding:9px 14px;background:#111827;color:#fff;border-radius:10px;text-decoration:none;font-weight:800;font-size:14px}
        .back-btn:hover{background:#374151}
        h1{margin:0;font-size:24px}
        .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px}
        .card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:18px}
        .card h3{margin:0 0 6px;font-size:16px}
        .card p{margin:0 0 12px;font-size:13px;color:#6b7280}
        .badge{display:inline-block;padding:2px 9px;border-radius:9999px;font-size:11px;font-weight:700;margin-right:6px}
        .badge-activo{background:#d1fae5;color:#065f46}
        .badge-proyecto{background:#fef3c7;color:#78350f}
        .badge-potencial{background:#e0e7ff;color:#3730a3}
        .badge-radar{background:#fce7f3;color:#831843}
        .btn-link{display:inline-block;padding:8px 14px;background:#059669;color:#fff;border-radius:10px;text-decoration:none;font-weight:700;font-size:13px}
        .btn-link:hover{background:#047857}
        .empty{color:#6b7280;text-align:center;padding:40px}
    </style></head>
    <body><div class="wrap">
        <div class="topbar">
            <a class="back-btn" href="/map">← Volver al mapa</a>
            <h1>🏭 Sectores Industriales</h1>
        </div>
        <?php if (empty($sectors)): ?>
            <p class="empty">No hay sectores industriales disponibles aún.</p>
        <?php else: ?>
        <div class="grid">
            <?php foreach ($sectors as $s): ?>
            <div class="card">
                <h3><?= htmlspecialchars($s['name']) ?></h3>
                <p><?= htmlspecialchars($s['description'] ?? '') ?></p>
                <span class="badge badge-<?= htmlspecialchars($s['status']) ?>"><?= htmlspecialchars(ucfirst($s['status'])) ?></span>
                <?php if (!empty($s['radar_enabled'])): ?><span class="badge badge-radar">🌐 Radar Legal</span><?php endif; ?>
                <div style="margin-top:12px;"><a class="btn-link" href="/sector-industrial?id=<?= $s['id'] ?>">Ver sector →</a></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div></body></html>
    <?php exit;
}

$sector = IndustrialSector::getById($id);
if (!$sector) { http_response_code(404); echo '<p>Sector no encontrado.</p>'; exit; }

$pageTitle = htmlspecialchars($sector['name']);
sectorHeader($pageTitle, 'industrial', $id, $tab);

if ($tab === 'overview' || !$tab):
?>
<div class="s-card">
    <div class="section-hdr">
        <h2>🏭 <?= htmlspecialchars($sector['name']) ?></h2>
        <span class="badge badge-<?= htmlspecialchars($sector['status']) ?>"><?= htmlspecialchars(ucfirst($sector['status'])) ?></span>
    </div>
    <p><?= nl2br(htmlspecialchars($sector['description'] ?? '')) ?></p>
    <div class="cta-row">
        <span class="badge badge-blue">Tipo: <?= htmlspecialchars(ucfirst($sector['type'])) ?></span>
        <?php if ($sector['jurisdiction']): ?><span class="badge badge-purple">📍 <?= htmlspecialchars($sector['jurisdiction']) ?></span><?php endif; ?>
        <?php if (!empty($sector['radar_enabled'])): ?><span class="badge badge-amber">🌐 Radar Legal habilitado</span><?php endif; ?>
    </div>
</div>
<div class="grid-2">
    <div class="s-card">
        <h3>🏛️ Institucional &amp; Normativo</h3>
        <p class="muted">Cámaras, Agencias, Líneas de política y Mapa de facultades.</p>
        <div class="cta-row"><a class="btn btn-primary" href="/sector-industrial?id=<?= $id ?>&tab=institucional">Ver módulo →</a></div>
    </div>
    <?php if (!empty($sector['radar_enabled'])): ?>
    <div class="s-card">
        <h3>🌐 Radar Legal</h3>
        <p class="muted">Comercio internacional, aduana, transporte y contratos.</p>
        <div class="cta-row"><a class="btn btn-primary" href="/sector-industrial?id=<?= $id ?>&tab=radar">Ver Radar Legal →</a></div>
    </div>
    <?php else: ?>
    <div class="s-card" style="opacity:.6;">
        <h3>🌐 Radar Legal</h3>
        <p class="muted">No habilitado. Contactá al administrador.</p>
    </div>
    <?php endif; ?>
</div>

<?php elseif ($tab === 'institucional'):
    $chambers     = Chamber::getBySector('industrial', $id);
    $agencies     = Agency::getBySector('industrial', $id);
    $linesPropia  = PolicyLine::getBySector('industrial', $id, ['line_type' => 'propia',   'status' => 'vigente']);
    $linesGob     = PolicyLine::getBySector('industrial', $id, ['line_type' => 'gobierno', 'status' => 'vigente']);
    $competencias = Competency::getBySector('industrial', $id);
    $roleLabels = [
        'aprobar'=>'✅ Aprobar','rechazar'=>'❌ Rechazar','controlar'=>'🔍 Controlar',
        'auditar'=>'📋 Auditar','sancionar'=>'⚖️ Sancionar','dictamen'=>'📄 Dictamen',
        'emitir'=>'📤 Emitir','fiscalizar'=>'🏛️ Fiscalizar',
    ];
?>
<div class="s-card">
    <h2>🏛️ Cámaras vinculadas</h2>
    <?php if (empty($chambers)): ?><p class="muted">No hay cámaras vinculadas.</p><?php else: ?>
    <div class="grid-2">
        <?php foreach ($chambers as $ch): ?>
        <div style="border:1px solid #e5e7eb;border-radius:10px;padding:14px;">
            <h3><?= htmlspecialchars($ch['name']) ?></h3>
            <span class="badge badge-<?= $ch['status']==='activa'?'green':'gray' ?>"><?= htmlspecialchars(ucfirst($ch['status'])) ?></span>
            <span class="badge badge-blue"><?= htmlspecialchars($ch['area']) ?></span>
            <p class="muted"><?= htmlspecialchars($ch['description'] ?? '') ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<div class="s-card">
    <h2>🏢 Agencias vinculadas</h2>
    <?php if (empty($agencies)): ?><p class="muted">No hay agencias vinculadas.</p><?php else: ?>
    <div class="grid-2">
        <?php foreach ($agencies as $ag): ?>
        <div style="border:1px solid #e5e7eb;border-radius:10px;padding:14px;">
            <h3><?= htmlspecialchars($ag['name']) ?></h3>
            <span class="badge badge-<?= $ag['status']==='activa'?'green':'gray' ?>"><?= htmlspecialchars(ucfirst($ag['status'])) ?></span>
            <span class="badge badge-purple"><?= htmlspecialchars($ag['area']) ?></span>
            <p class="muted"><?= htmlspecialchars($ag['description'] ?? '') ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<div class="s-card">
    <h2>📋 Líneas Propias</h2>
    <?php if (empty($linesPropia)): ?><p class="muted">Sin líneas propias vigentes.</p><?php else: ?>
    <table class="data-table"><thead><tr><th>Título</th><th>Área</th><th>Origen</th><th>Jurisdicción</th><th>Publicación</th></tr></thead><tbody>
    <?php foreach ($linesPropia as $l): ?>
    <tr><td><?= $l['source_link'] ? '<a href="'.htmlspecialchars($l['source_link']).'" target="_blank" rel="noopener noreferrer">'.htmlspecialchars($l['title']).'</a>' : htmlspecialchars($l['title']) ?>
        <?php if ($l['summary']): ?><br><span class="muted"><?= htmlspecialchars($l['summary']) ?></span><?php endif; ?></td>
    <td><?= htmlspecialchars($l['area'] ?? '') ?></td>
    <td><span class="badge badge-blue"><?= htmlspecialchars(ucfirst($l['source_type'])) ?></span></td>
    <td><?= htmlspecialchars($l['jurisdiction'] ?? '') ?></td>
    <td><?= $l['published_at'] ? htmlspecialchars($l['published_at']) : '' ?></td></tr>
    <?php endforeach; ?></tbody></table>
    <?php endif; ?>
</div>

<div class="s-card">
    <h2>🏛️ Líneas de Gobierno</h2>
    <?php if (empty($linesGob)): ?><p class="muted">Sin líneas de gobierno vigentes.</p><?php else: ?>
    <table class="data-table"><thead><tr><th>Título</th><th>Área</th><th>Jurisdicción</th><th>Publicación</th><th>Tags</th></tr></thead><tbody>
    <?php foreach ($linesGob as $l): ?>
    <tr><td><?= $l['source_link'] ? '<a href="'.htmlspecialchars($l['source_link']).'" target="_blank" rel="noopener noreferrer">'.htmlspecialchars($l['title']).'</a>' : htmlspecialchars($l['title']) ?>
        <?php if ($l['summary']): ?><br><span class="muted"><?= htmlspecialchars($l['summary']) ?></span><?php endif; ?></td>
    <td><?= htmlspecialchars($l['area'] ?? '') ?></td>
    <td><?= htmlspecialchars($l['jurisdiction'] ?? '') ?></td>
    <td><?= $l['published_at'] ? htmlspecialchars($l['published_at']) : '' ?></td>
    <td class="muted"><?= htmlspecialchars($l['tags'] ?? '') ?></td></tr>
    <?php endforeach; ?></tbody></table>
    <?php endif; ?>
</div>

<div class="s-card">
    <h2>⚖️ Mapa de Facultades / Competencias</h2>
    <?php if (empty($competencias)): ?><p class="muted">Sin competencias definidas.</p><?php else: ?>
    <table class="data-table"><thead><tr><th>Facultad</th><th>Organismo</th><th>Órgano</th><th>Responsable</th><th>Alcance</th></tr></thead><tbody>
    <?php foreach ($competencias as $c): ?>
    <tr>
        <td><span class="badge badge-amber"><?= $roleLabels[$c['role']] ?? htmlspecialchars(ucfirst($c['role'])) ?></span></td>
        <td><?= htmlspecialchars($c['organism']) ?></td>
        <td><?= htmlspecialchars($c['organ'] ?? '') ?></td>
        <td><?= htmlspecialchars($c['responsible'] ?? '') ?></td>
        <td class="muted"><?= htmlspecialchars($c['scope'] ?? '') ?></td>
    </tr>
    <?php endforeach; ?></tbody></table>
    <?php endif; ?>
</div>

<?php elseif ($tab === 'radar'):
    if (!RadarLegal::isEnabled('industrial', $id)): ?>
<div class="s-card" style="text-align:center;padding:40px;">
    <h2>🌐 Radar Legal</h2>
    <p class="muted">El módulo Radar Legal no está habilitado para este sector.</p>
    <div class="cta-row" style="justify-content:center;"><a class="btn btn-primary" href="/contacto">Solicitar activación</a></div>
</div>
<?php else:
    $transportModes = RadarLegal::getTransportModes();
    $ports          = RadarLegal::getPorts();
    $portsByMode    = [];
    foreach ($ports as $p) $portsByMode[$p['transport_mode_id']][] = $p;
    $destinations = RadarLegal::getDestinations();
    $destByDir    = ['importacion' => [], 'exportacion' => []];
    foreach ($destinations as $d) $destByDir[$d['direction']][] = $d;
    $restrictions = RadarLegal::getRestrictions();
    $disputes     = RadarLegal::getDisputes();
    $contracts    = RadarLegal::getContractTypes();
?>
<div class="s-card">
    <h2>🌐 Radar Legal — Comercio Internacional &amp; Aduana</h2>
    <p class="muted">Consulta de parámetros de comercio exterior aplicables al sector industrial.</p>
</div>
<div id="radar-tabs" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:16px;">
    <button class="btn btn-primary" onclick="showRadarTab('transporte')">🚢 Transporte</button>
    <button class="btn btn-secondary" onclick="showRadarTab('destinaciones')">📦 Destinaciones</button>
    <button class="btn btn-secondary" onclick="showRadarTab('restricciones')">🚫 Restricciones</button>
    <button class="btn btn-secondary" onclick="showRadarTab('controversias')">⚠️ Controversias</button>
    <button class="btn btn-secondary" onclick="showRadarTab('contratos')">📝 Contratos</button>
</div>
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
        <p style="font-size:13px;font-weight:700;margin:10px 0 4px;">Puertos:</p>
        <ul style="margin:0;padding-left:18px;font-size:13px;">
            <?php foreach ($portsByMode[$tm['id']] as $po): ?><li><?= htmlspecialchars($po['name']) ?><?= $po['country'] ? ' — '.htmlspecialchars($po['country']) : '' ?></li><?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    </div>
</div>
<div id="rt-destinaciones" class="radar-tab-content s-card" style="display:none;">
    <h2>📦 Destinaciones Aduaneras</h2>
    <div class="grid-2">
        <div><h3 style="color:#065f46;">📥 Importación</h3>
        <?php foreach ($destByDir['importacion'] as $d): ?><div style="border-bottom:1px solid #f3f4f6;padding:10px 0;"><strong><?= htmlspecialchars($d['name']) ?></strong><?= $d['code'] ? ' <span class="badge badge-blue">'.htmlspecialchars($d['code']).'</span>' : '' ?><p class="muted"><?= htmlspecialchars($d['description'] ?? '') ?></p></div><?php endforeach; ?>
        </div>
        <div><h3 style="color:#1e40af;">📤 Exportación</h3>
        <?php foreach ($destByDir['exportacion'] as $d): ?><div style="border-bottom:1px solid #f3f4f6;padding:10px 0;"><strong><?= htmlspecialchars($d['name']) ?></strong><?= $d['code'] ? ' <span class="badge badge-blue">'.htmlspecialchars($d['code']).'</span>' : '' ?><p class="muted"><?= htmlspecialchars($d['description'] ?? '') ?></p></div><?php endforeach; ?>
        </div>
    </div>
</div>
<div id="rt-restricciones" class="radar-tab-content s-card" style="display:none;">
    <h2>🚫 Restricciones</h2>
    <?php if (empty($restrictions)): ?><p class="muted">Sin datos.</p><?php else: ?>
    <table class="data-table"><thead><tr><th>Tipo</th><th>Nombre</th><th>Fundamento</th><th>Descripción</th></tr></thead><tbody>
    <?php $rlabels=['prohibicion'=>'🚫 Prohibición','dumping'=>'⬇️ Dumping','licencia_automatica'=>'✅ Lic. Automática','licencia_no_automatica'=>'⏳ Lic. No Automática','cuota'=>'📊 Cuota','otro'=>'📌 Otro'];
    foreach ($restrictions as $r): ?><tr><td><span class="badge badge-red"><?= $rlabels[$r['restriction_type']] ?? htmlspecialchars($r['restriction_type']) ?></span></td><td><?= htmlspecialchars($r['name']) ?></td><td class="muted"><?= htmlspecialchars($r['legal_basis'] ?? '') ?></td><td class="muted"><?= htmlspecialchars($r['description'] ?? '') ?></td></tr><?php endforeach; ?>
    </tbody></table>
    <?php endif; ?>
</div>
<div id="rt-controversias" class="radar-tab-content s-card" style="display:none;">
    <h2>⚠️ Controversias</h2>
    <?php if (empty($disputes)): ?><p class="muted">Sin datos.</p><?php else: ?>
    <table class="data-table"><thead><tr><th>Tipo</th><th>Nombre</th><th>Descripción</th><th>Sanciones</th></tr></thead><tbody>
    <?php $dlabels=['infraccion_aduanera'=>'📋 Infracción','incumplimiento_normativo'=>'⚠️ Incumplimiento','delito_aduanero'=>'🚔 Delito','otro'=>'📌 Otro'];
    foreach ($disputes as $d): ?><tr><td><span class="badge badge-amber"><?= $dlabels[$d['dispute_type']] ?? htmlspecialchars($d['dispute_type']) ?></span></td><td><?= htmlspecialchars($d['name']) ?></td><td class="muted"><?= htmlspecialchars($d['description'] ?? '') ?></td><td class="muted"><?= htmlspecialchars($d['sanction_range'] ?? '') ?></td></tr><?php endforeach; ?>
    </tbody></table>
    <?php endif; ?>
</div>
<div id="rt-contratos" class="radar-tab-content s-card" style="display:none;">
    <h2>📝 Contratos Internacionales</h2>
    <div class="grid-2">
    <?php $clabels=['compraventa'=>'🛒','llave_en_mano'=>'🔑','agencia'=>'🤝','distribucion'=>'📦','inversion_activos'=>'🏭','inversion_financiera'=>'💰','joint_venture'=>'🤝','otro'=>'📄'];
    foreach ($contracts as $ct): ?>
    <div style="border:1px solid #e5e7eb;border-radius:10px;padding:14px;">
        <h3><?= ($clabels[$ct['category']] ?? '') ?> <?= htmlspecialchars($ct['name']) ?></h3>
        <p class="muted"><?= htmlspecialchars($ct['description'] ?? '') ?></p>
        <?php if ($ct['key_points']): ?><p style="font-size:13px;font-weight:700;margin-bottom:4px;">Puntos clave:</p><p class="muted"><?= htmlspecialchars($ct['key_points']) ?></p><?php endif; ?>
    </div>
    <?php endforeach; ?>
    </div>
</div>
<script>
function showRadarTab(tab) {
    document.querySelectorAll('.radar-tab-content').forEach(el => el.style.display='none');
    document.getElementById('rt-'+tab).style.display='block';
}
</script>
<?php endif; ?>
<?php endif; ?>

<?php sectorFooter(); ?>
