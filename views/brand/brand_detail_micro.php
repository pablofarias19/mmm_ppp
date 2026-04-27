<?php
/**
 * views/brand/brand_detail_micro.php
 * Panel profesional de detalle de marca.
 * Muestra TODOS los campos del formulario de creación/edición.
 * Ruta: /brand_detail?id=N
 */
if (session_status() === PHP_SESSION_NONE) session_start();
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../includes/db_helper.php';

setSecurityHeaders();

$brandId  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$marca_id = $brandId ?: null;
if ($brandId <= 0) {
    header('Location: /marcas');
    exit;
}

try {

$db = getDbConnection();

// Usuario puede ver su propia marca aunque esté oculta
$userId  = (int)($_SESSION['user_id'] ?? 0);
$isAdmin = !empty($_SESSION['is_admin']);

$stmt = $db->prepare('SELECT * FROM brands WHERE id = ? AND visible = 1 LIMIT 1');
$stmt->execute([$brandId]);
$brand = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$brand && ($userId > 0 || $isAdmin)) {
    $stmt2 = $db->prepare('SELECT * FROM brands WHERE id = ? LIMIT 1');
    $stmt2->execute([$brandId]);
    $brand = $stmt2->fetch(PDO::FETCH_ASSOC);
}

if (!$brand) {
    throw new \RuntimeException('Marca no encontrada');
}

$canEdit = $userId > 0 && ((int)($brand['user_id'] ?? 0) === $userId || $isAdmin);

// ── Logo ─────────────────────────────────────────────────────────────────────
$logoUrl = null;
foreach (['png','jpg','jpeg','webp'] as $ext) {
    $path = __DIR__ . '/../../uploads/brands/' . $brandId . '/logo.' . $ext;
    if (file_exists($path)) {
        $logoUrl = '/uploads/brands/' . $brandId . '/logo.' . $ext . '?t=' . filemtime($path);
        break;
    }
}

// ── Galería ──────────────────────────────────────────────────────────────────
$photos = [];
try {
    $ps = $db->prepare("SELECT * FROM attachments WHERE brand_id = ? AND type IN ('photo','logo') ORDER BY uploaded_at DESC LIMIT 20");
    $ps->execute([$brandId]);
    $photos = $ps->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// ── Helpers de presentación ───────────────────────────────────────────────────
function bv(array $b, string $k, string $fb = '—'): string {
    $v = $b[$k] ?? '';
    return $v !== '' && $v !== null ? htmlspecialchars((string)$v) : $fb;
}
function bFlag(array $b, string $k): bool {
    return !empty($b[$k]);
}

// Mapeo rubro → emoji
$rubroIcons = [
    'Ropa Deportiva' => '👕', 'Bebidas' => '🥤', 'Farmacia' => '💊',
    'Tecnología' => '💻', 'Alimentos' => '🍔', 'Moda' => '👗',
    'Electrónica' => '📱', 'Construcción' => '🏗️', 'Turismo' => '✈️',
    'Educación' => '📚', 'Salud' => '⚕️', 'Finanzas' => '💰',
    'Automotriz' => '🚗', 'Agro' => '🌾', 'Arte' => '🎨',
    'Gastronomía' => '🍽️', 'Servicios' => '🔧', 'Inmobiliaria' => '🏠',
];
$icon = $rubroIcons[$brand['rubro']] ?? '🏷️';

// Scope & channels
$scopeLabels    = ['local'=>'🏘️ Local','regional'=>'🗺️ Regional','nacional'=>'🇦🇷 Nacional','internacional'=>'🌍 Internacional'];
$channelLabels  = ['tienda_fisica'=>'🏪 Tienda Física','ecommerce'=>'🛒 E-commerce','wholesale'=>'📦 Mayorista','marketplace'=>'🏬 Marketplace','redes_sociales'=>'📱 Redes Sociales','distribuidores'=>'🚚 Distribuidores'];
$scopeActive    = array_filter(explode(',', $brand['scope']    ?? ''));
$channelsActive = array_filter(explode(',', $brand['channels'] ?? ''));

// INPI expiry
$inpiStatus = '';
$inpiColor  = '#6b7280';
if (!empty($brand['inpi_vencimiento'])) {
    $dias = (int)((strtotime($brand['inpi_vencimiento']) - time()) / 86400);
    $inpiColor  = $dias > 365 ? '#16a34a' : ($dias > 90 ? '#d97706' : '#dc2626');
    $inpiStatus = $dias > 0 ? "Vence en $dias días" : 'VENCIDA hace ' . abs($dias) . ' días';
}

// Redes con datos
$redesActivas = [];
foreach ([
    'instagram' => ['url' => 'https://instagram.com/',  'label' => 'Instagram',  'color' => '#e1306c', 'icon' => '📸'],
    'facebook'  => ['url' => 'https://facebook.com/',   'label' => 'Facebook',   'color' => '#1877f2', 'icon' => '👍'],
    'tiktok'    => ['url' => 'https://tiktok.com/@',    'label' => 'TikTok',     'color' => '#000',    'icon' => '🎵'],
    'twitter'   => ['url' => 'https://x.com/',          'label' => 'X / Twitter','color' => '#1da1f2', 'icon' => '🐦'],
    'linkedin'  => ['url' => 'https://linkedin.com/',   'label' => 'LinkedIn',   'color' => '#0077b5', 'icon' => '💼'],
    'youtube'   => ['url' => 'https://youtube.com/',    'label' => 'YouTube',    'color' => '#ff0000', 'icon' => '▶️'],
] as $k => $r) {
    if (!empty($brand[$k])) {
        $redesActivas[$k] = array_merge($r, ['handle' => $brand[$k]]);
    }
}

} catch (\Throwable $e) {
    $brand_label      = $marca_id ? "Marca #$marca_id" : 'Demo';
    $back_url         = '/marcas';
    $show_detail_link = false;
    $tool_title       = 'Detalle de Marca';
    $tool_description = 'El Detalle de Marca centraliza toda la información registral, legal, financiera y estratégica de su marca. '
        . 'Acceda a datos de clasificación NIZA, situación INPI, canales de distribución, redes sociales y herramientas de análisis avanzado.';
    $tool_bullets     = [
        'Información registral completa (nombre, rubro, fundación, estado).',
        'Situación ante el INPI y vencimiento de registros.',
        'Canales de distribución y alcance geográfico.',
        'Presencia digital: redes sociales, web y WhatsApp.',
        'Acceso a herramientas de análisis: Niza, modelo de negocio, legal y reporte.',
        'Panel gestionado bajo consulta profesional por Farias Ortiz.',
    ];
    $tool_edit_note   = 'Si el detalle de marca no está disponible, puede acceder igualmente a las herramientas de análisis usando los botones más abajo.';
    $tool_links       = $marca_id ? [
        ['url' => "/brand_analysis?id=$marca_id",      'label' => '📊 Análisis Marcario'],
        ['url' => "/niza_classification?id=$marca_id", 'label' => '📋 Clasificación Niza'],
        ['url' => "/business_model?id=$marca_id",      'label' => '♟️ Modelos de Negocio'],
        ['url' => "/monetization?id=$marca_id",        'label' => '💰 Monetización'],
        ['url' => "/legal_risk?id=$marca_id",          'label' => '⚖️ Riesgo Legal'],
        ['url' => "/brand_report?id=$marca_id",        'label' => '📑 Reporte Ejecutivo'],
    ] : [];
    require __DIR__ . '/_tool_panel.php';
    exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($brand['nombre']); ?> — Mapita</title>
    <meta name="description" content="<?php echo htmlspecialchars($brand['description'] ?? 'Conocé la marca ' . $brand['nombre'] . ' en Mapita'); ?>">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <style>
        :root {
            --brand:  #1B3B6F;
            --accent: #667eea;
            --teal:   #00bfa5;
            --bg:     #f5f7ff;
            --card:   #ffffff;
            --text:   #1e2535;
            --gray4:  #6c7a8d;
            --gray2:  #e8eaf0;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        /* ── TOP NAV ────────────────────────────────────────── */
        .topnav {
            position: sticky; top: 0; z-index: 200;
            background: var(--brand);
            display: flex; align-items: center; gap: 14px;
            padding: 0 24px; height: 56px;
            box-shadow: 0 2px 12px rgba(0,0,0,.22);
        }
        .topnav-logo { font-size: 1.1em; font-weight: 800; color: white; text-decoration: none; }
        .topnav-logo span { opacity: .55; font-weight: 400; font-size: .8em; margin-left: 6px; }
        .topnav-right { margin-left: auto; display: flex; gap: 8px; }
        .tnbtn {
            padding: 7px 16px; border-radius: 7px; font-size: .82em; font-weight: 700;
            text-decoration: none; display: inline-flex; align-items: center; gap: 5px;
            transition: all .15s;
        }
        .tnbtn-ghost { color: rgba(255,255,255,.8); border: 1.5px solid rgba(255,255,255,.3); }
        .tnbtn-ghost:hover { background: rgba(255,255,255,.1); color: white; }
        .tnbtn-edit  { background: #0ea5e9; color: white; }
        .tnbtn-edit:hover { background: #0284c7; }

        /* ── HERO ───────────────────────────────────────────── */
        .hero {
            position: relative;
            background: linear-gradient(135deg, var(--brand) 0%, var(--accent) 100%);
            color: white; overflow: hidden;
            padding: 48px 32px 40px;
        }
        .hero::before {
            content: '';
            position: absolute; inset: 0;
            background: radial-gradient(circle at 80% 20%, rgba(255,255,255,.13) 0%, transparent 55%);
            pointer-events: none;
        }
        .hero-inner {
            position: relative; z-index: 1;
            max-width: 960px; margin: 0 auto;
            display: flex; align-items: center; gap: 28px; flex-wrap: wrap;
        }
        .hero-logo {
            width: 100px; height: 100px; border-radius: 16px;
            border: 3px solid rgba(255,255,255,.4);
            box-shadow: 0 6px 24px rgba(0,0,0,.3);
            background: rgba(255,255,255,.12);
            display: flex; align-items: center; justify-content: center;
            font-size: 3.5rem; flex-shrink: 0; overflow: hidden;
        }
        .hero-logo img { width: 100%; height: 100%; object-fit: cover; border-radius: 13px; }
        .hero-info { flex: 1; min-width: 200px; }
        .hero-name { font-size: 2.2rem; font-weight: 800; letter-spacing: -.5px; line-height: 1.1; }
        .hero-tags { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }
        .hero-tag {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 13px;
            background: rgba(255,255,255,.15);
            backdrop-filter: blur(8px);
            border-radius: 20px; font-size: .82em; font-weight: 600;
        }
        .hero-tag.visible-on  { background: rgba(34,197,94,.25); }
        .hero-tag.visible-off { background: rgba(239,68,68,.25); }

        /* ── MAIN LAYOUT ────────────────────────────────────── */
        .main { max-width: 960px; margin: 32px auto 60px; padding: 0 20px; }

        /* ── SECTION CARD ───────────────────────────────────── */
        .sec {
            background: var(--card);
            border-radius: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,.07);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .sec-head {
            display: flex; align-items: center; gap: 12px;
            padding: 16px 22px;
            border-bottom: 1px solid var(--gray2);
            background: #fafbff;
        }
        .sec-icon {
            width: 34px; height: 34px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; flex-shrink: 0;
            background: var(--accent); color: white;
        }
        .sec-title { font-size: .95em; font-weight: 800; color: var(--brand); }
        .sec-sub   { font-size: .78em; color: var(--gray4); margin-top: 1px; }
        .sec-body  { padding: 20px 22px; }

        /* ── INFO GRID ──────────────────────────────────────── */
        .igrid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 14px; }
        .iitem {
            padding: 14px 16px;
            background: var(--bg);
            border-radius: 10px;
            border-left: 4px solid var(--accent);
            transition: transform .15s;
        }
        .iitem:hover { transform: translateX(3px); }
        .iitem-label {
            font-size: .7em; font-weight: 700; color: var(--gray4);
            text-transform: uppercase; letter-spacing: .4px; margin-bottom: 5px;
        }
        .iitem-val {
            font-size: .92em; font-weight: 600; color: var(--text); word-break: break-word;
        }
        .iitem-val a { color: var(--accent); text-decoration: none; }
        .iitem-val a:hover { text-decoration: underline; }

        /* ── PILLS ──────────────────────────────────────────── */
        .pill-row { display: flex; flex-wrap: wrap; gap: 8px; }
        .pill-active {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 5px 13px;
            background: var(--accent); color: white;
            border-radius: 20px; font-size: .82em; font-weight: 700;
        }
        .pill-empty {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 5px 13px;
            background: var(--gray2); color: var(--gray4);
            border-radius: 20px; font-size: .82em; font-weight: 600;
            text-decoration: line-through; opacity: .7;
        }

        /* ── TEXT BLOCKS ────────────────────────────────────── */
        .text-block {
            font-size: .9em; line-height: 1.75; color: #374151;
            white-space: pre-wrap; word-break: break-word;
        }
        .text-label {
            font-size: .75em; font-weight: 800; color: var(--accent);
            text-transform: uppercase; letter-spacing: .5px;
            margin-bottom: 8px;
        }
        .text-sep { border: none; border-top: 1px solid var(--gray2); margin: 16px 0; }

        /* ── INPI BANNER ────────────────────────────────────── */
        .inpi-banner {
            display: flex; align-items: center; gap: 14px;
            padding: 14px 18px;
            background: linear-gradient(135deg, #003f87, #0058c0);
            border-radius: 10px; color: white; margin-bottom: 16px;
        }
        .inpi-banner-text { font-weight: 700; font-size: .9em; }
        .inpi-banner-sub  { font-size: .78em; opacity: .8; margin-top: 2px; }

        /* ── REDES ──────────────────────────────────────────── */
        .redes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 12px; }
        .red-card {
            display: flex; align-items: center; gap: 10px;
            padding: 12px 14px;
            border-radius: 10px; border: 1.5px solid var(--gray2);
            text-decoration: none; color: var(--text);
            transition: all .15s;
        }
        .red-card:hover { border-color: var(--accent); box-shadow: 0 4px 12px rgba(102,126,234,.15); transform: translateY(-2px); }
        .red-icon { font-size: 1.4rem; flex-shrink: 0; }
        .red-info { overflow: hidden; }
        .red-name { font-size: .8em; font-weight: 700; color: var(--gray4); }
        .red-handle { font-size: .85em; font-weight: 600; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* ── FLAGS ROW ──────────────────────────────────────── */
        .flags-row { display: flex; flex-wrap: wrap; gap: 10px; }
        .flag-on {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 14px;
            background: #f0fdf4; color: #166534;
            border: 1.5px solid #bbf7d0;
            border-radius: 8px; font-size: .84em; font-weight: 700;
        }
        .flag-off {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 14px;
            background: #f9fafb; color: var(--gray4);
            border: 1.5px solid var(--gray2);
            border-radius: 8px; font-size: .84em; font-weight: 600;
            opacity: .7;
        }

        /* ── GALLERY ────────────────────────────────────────── */
        .gal-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; }
        .gal-item { border-radius: 10px; overflow: hidden; aspect-ratio: 1; box-shadow: 0 2px 8px rgba(0,0,0,.1); cursor: pointer; transition: transform .2s; }
        .gal-item:hover { transform: scale(1.04); }
        .gal-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .gal-placeholder { display: flex; align-items: center; justify-content: center; font-size: 3rem; background: linear-gradient(135deg, var(--accent), var(--teal)); }

        /* ── TOOLS ──────────────────────────────────────────── */
        .tools-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; }
        .tool-card {
            display: flex; align-items: center; gap: 10px;
            padding: 13px 16px;
            background: var(--bg); border-radius: 10px;
            border: 1.5px solid var(--gray2);
            text-decoration: none; color: var(--text);
            transition: all .15s;
        }
        .tool-card:hover { border-color: var(--accent); background: #f0f4ff; }
        .tool-icon { font-size: 1.4rem; flex-shrink: 0; }
        .tool-name { font-size: .86em; font-weight: 700; color: var(--brand); }
        .tool-desc { font-size: .74em; color: var(--gray4); margin-top: 1px; }

        /* ── ACTION BAR ─────────────────────────────────────── */
        .action-bar {
            display: flex; flex-wrap: wrap; gap: 10px;
            padding: 18px 22px;
            border-top: 1.5px solid var(--gray2);
            background: #fafbff;
        }
        .abtn {
            padding: 10px 20px; border-radius: 8px; font-size: .86em; font-weight: 700;
            text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
            border: none; cursor: pointer; transition: all .15s;
        }
        .abtn-primary { background: var(--accent); color: white; }
        .abtn-primary:hover { background: var(--brand); }
        .abtn-ghost  { background: white; color: var(--brand); border: 1.5px solid var(--gray2); }
        .abtn-ghost:hover { border-color: var(--brand); }
        .abtn-edit   { background: #0ea5e9; color: white; }
        .abtn-edit:hover { background: #0284c7; }
        .abtn-share  { background: #25d366; color: white; }
        .abtn-share:hover { background: #16a34a; }

        /* ── FOOTER ─────────────────────────────────────────── */
        .footer { text-align: center; padding: 30px 0; color: var(--gray4); font-size: .82em; }
        .footer a { color: var(--accent); text-decoration: none; }

        /* ── RESPONSIVE ─────────────────────────────────────── */
        @media (max-width: 640px) {
            .hero { padding: 28px 16px 28px; }
            .hero-name { font-size: 1.5rem; }
            .hero-logo { width: 72px; height: 72px; font-size: 2.4rem; }
            .main { margin-top: 20px; padding: 0 12px; }
            .igrid { grid-template-columns: 1fr 1fr; }
            .redes-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>

<!-- TOP NAV -->
<nav class="topnav">
    <a href="/" class="topnav-logo">🗺️ Mapita <span>marcas</span></a>
    <div class="topnav-right">
        <a href="/marcas" class="tnbtn tnbtn-ghost">← Volver al mapa</a>
        <?php if ($canEdit): ?>
        <a href="/brand_form?id=<?= $brandId ?>" class="tnbtn tnbtn-edit">✏️ Editar</a>
        <?php endif; ?>
    </div>
</nav>

<!-- HERO -->
<div class="hero">
    <div class="hero-inner">
        <div class="hero-logo">
            <?php if ($logoUrl): ?>
                <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Logo <?= htmlspecialchars($brand['nombre']) ?>">
            <?php else: ?>
                <?= $icon ?>
            <?php endif; ?>
        </div>
        <div class="hero-info">
            <h1 class="hero-name"><?= htmlspecialchars($brand['nombre']) ?></h1>
            <div class="hero-tags">
                <?php if (!empty($brand['rubro'])): ?>
                <span class="hero-tag">📋 <?= htmlspecialchars($brand['rubro']) ?></span>
                <?php endif; ?>
                <?php if (!empty($brand['ubicacion'])): ?>
                <span class="hero-tag">📍 <?= htmlspecialchars($brand['ubicacion']) ?></span>
                <?php endif; ?>
                <?php if (!empty($brand['founded_year'])): ?>
                <span class="hero-tag">📅 Desde <?= (int)$brand['founded_year'] ?></span>
                <?php endif; ?>
                <?php if (!empty($brand['estado'])): ?>
                <span class="hero-tag">🏛️ <?= htmlspecialchars($brand['estado']) ?></span>
                <?php endif; ?>
                <span class="hero-tag <?= $brand['visible'] ? 'visible-on' : 'visible-off' ?>">
                    <?= $brand['visible'] ? '✅ Visible en mapa' : '🔒 Oculta' ?>
                </span>
                <?php if (bFlag($brand,'inpi_registrada')): ?>
                <span class="hero-tag" style="background:rgba(0,63,135,.4);">🏛️ Marca Reg. INPI</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="main">

    <!-- ── DESCRIPCIÓN ─────────────────────────────────────── -->
    <?php if (!empty($brand['description']) || !empty($brand['extended_description'])): ?>
    <div class="sec">
        <div class="sec-head">
            <div class="sec-icon" style="background:#8b5cf6;">📝</div>
            <div>
                <div class="sec-title">Descripción</div>
                <div class="sec-sub">Resumen y misión de la marca</div>
            </div>
        </div>
        <div class="sec-body">
            <?php if (!empty($brand['description'])): ?>
            <div class="text-label">Descripción breve</div>
            <div class="text-block"><?= htmlspecialchars($brand['description']) ?></div>
            <?php endif; ?>
            <?php if (!empty($brand['extended_description'])): ?>
            <?php if (!empty($brand['description'])): ?><hr class="text-sep"><?php endif; ?>
            <div class="text-label">Historia / Misión / Visión</div>
            <div class="text-block"><?= htmlspecialchars($brand['extended_description']) ?></div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── IDENTIDAD & CLASIFICACIÓN ───────────────────────── -->
    <div class="sec">
        <div class="sec-head">
            <div class="sec-icon">🏷️</div>
            <div>
                <div class="sec-title">Identidad y Clasificación</div>
                <div class="sec-sub">Datos principales, Niza, alcance y canales</div>
            </div>
        </div>
        <div class="sec-body">
            <div class="igrid">
                <div class="iitem">
                    <div class="iitem-label">🏷️ Nombre Comercial</div>
                    <div class="iitem-val"><?= bv($brand,'nombre') ?></div>
                </div>
                <div class="iitem">
                    <div class="iitem-label">📋 Rubro / Sector</div>
                    <div class="iitem-val"><?= bv($brand,'rubro') ?></div>
                </div>
                <div class="iitem">
                    <div class="iitem-label">📅 Año de Fundación</div>
                    <div class="iitem-val"><?= bv($brand,'founded_year') ?></div>
                </div>
                <div class="iitem">
                    <div class="iitem-label">📋 Clase NIZA Principal</div>
                    <div class="iitem-val"><?= $brand['clase_principal'] ? 'Clase ' . (int)$brand['clase_principal'] : '—' ?></div>
                </div>
                <div class="iitem">
                    <div class="iitem-label">📍 Ubicación</div>
                    <div class="iitem-val"><?= bv($brand,'ubicacion') ?></div>
                </div>
                <div class="iitem">
                    <div class="iitem-label">🛡️ Nivel de Protección</div>
                    <div class="iitem-val"><?= bv($brand,'nivel_proteccion') ?></div>
                </div>
                <?php if (!empty($brand['website'])): ?>
                <div class="iitem">
                    <div class="iitem-label">🌐 Sitio Web</div>
                    <div class="iitem-val">
                        <a href="<?= htmlspecialchars($brand['website']) ?>" target="_blank" rel="noopener">
                            <?= htmlspecialchars($brand['website']) ?> ↗
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($scopeActive)): ?>
            <div style="margin-top:18px;">
                <div class="text-label">🌍 Alcance Geográfico</div>
                <div class="pill-row" style="margin-top:8px;">
                    <?php foreach ($scopeLabels as $k => $l): ?>
                    <span class="<?= in_array($k, $scopeActive) ? 'pill-active' : 'pill-empty' ?>"><?= $l ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($channelsActive)): ?>
            <div style="margin-top:14px;">
                <div class="text-label">🚚 Canales de Distribución</div>
                <div class="pill-row" style="margin-top:8px;">
                    <?php foreach ($channelLabels as $k => $l): ?>
                    <span class="<?= in_array($k, $channelsActive) ? 'pill-active' : 'pill-empty' ?>"><?= $l ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── FINANCIERO ──────────────────────────────────────── -->
    <?php if (!empty($brand['annual_revenue']) || !empty($brand['valor_activo']) || !empty($brand['riesgo_oposicion'])): ?>
    <div class="sec">
        <div class="sec-head">
            <div class="sec-icon" style="background:#d97706;">💰</div>
            <div>
                <div class="sec-title">Datos Financieros</div>
                <div class="sec-sub">Ingresos, activo marcario y riesgo</div>
            </div>
        </div>
        <div class="sec-body">
            <div class="igrid">
                <?php $revenueLabels = ['0-50k'=>'Menor a $50k','50k-500k'=>'$50k – $500k','500k-1m'=>'$500k – $1M','1m-5m'=>'$1M – $5M','5m+'=>'Mayor a $5M']; ?>
                <?php if (!empty($brand['annual_revenue'])): ?>
                <div class="iitem" style="border-left-color:#d97706;">
                    <div class="iitem-label">💵 Ingresos Anuales Est.</div>
                    <div class="iitem-val"><?= $revenueLabels[$brand['annual_revenue']] ?? htmlspecialchars($brand['annual_revenue']) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($brand['valor_activo'])): ?>
                <div class="iitem" style="border-left-color:#d97706;">
                    <div class="iitem-label">💎 Valor del Activo Marcario</div>
                    <div class="iitem-val">$<?= htmlspecialchars($brand['valor_activo']) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($brand['riesgo_oposicion'])): ?>
                <?php
                $riesgoColor = ['Muy Bajo'=>'#16a34a','Bajo'=>'#2563eb','Medio'=>'#d97706','Alto'=>'#dc2626','Muy Alto'=>'#7f1d1d'][$brand['riesgo_oposicion']] ?? '#6b7280';
                ?>
                <div class="iitem" style="border-left-color:<?= $riesgoColor ?>;">
                    <div class="iitem-label">⚠️ Riesgo de Oposición</div>
                    <div class="iitem-val" style="color:<?= $riesgoColor ?>;"><?= htmlspecialchars($brand['riesgo_oposicion']) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── SITUACIÓN LEGAL — INPI ──────────────────────────── -->
    <?php if (bFlag($brand,'inpi_registrada')): ?>
    <div class="sec">
        <div class="sec-head">
            <div class="sec-icon" style="background:#003f87;">⚖️</div>
            <div>
                <div class="sec-title">Situación Legal — INPI</div>
                <div class="sec-sub">Registro ante el Instituto Nacional de la Propiedad Industrial</div>
            </div>
        </div>
        <div class="sec-body">
            <div class="inpi-banner">
                <div style="font-size:2rem;">🏛️</div>
                <div>
                    <div class="inpi-banner-text">Marca Registrada — INPI Argentina</div>
                    <div class="inpi-banner-sub">Instituto Nacional de la Propiedad Industrial · República Argentina</div>
                </div>
            </div>
            <div class="igrid">
                <?php if (!empty($brand['inpi_numero'])): ?>
                <div class="iitem" style="border-left-color:#003f87;">
                    <div class="iitem-label">📑 N° Resolución / Acta</div>
                    <div class="iitem-val"><?= bv($brand,'inpi_numero') ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($brand['inpi_tipo'])): ?>
                <div class="iitem" style="border-left-color:#003f87;">
                    <div class="iitem-label">🏷️ Tipo de Marca</div>
                    <div class="iitem-val"><?= bv($brand,'inpi_tipo') ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($brand['inpi_fecha_registro'])): ?>
                <div class="iitem" style="border-left-color:#003f87;">
                    <div class="iitem-label">📅 Fecha de Registro</div>
                    <div class="iitem-val"><?= date('d/m/Y', strtotime($brand['inpi_fecha_registro'])) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($brand['inpi_vencimiento'])): ?>
                <div class="iitem" style="border-left-color:<?= $inpiColor ?>;">
                    <div class="iitem-label">⏱️ Vencimiento</div>
                    <div class="iitem-val" style="color:<?= $inpiColor ?>;">
                        <?= date('d/m/Y', strtotime($brand['inpi_vencimiento'])) ?>
                        <?= $inpiStatus ? ' · ' . $inpiStatus : '' ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (!empty($brand['inpi_clases_registradas'])): ?>
                <div class="iitem" style="border-left-color:#003f87;grid-column:1/-1;">
                    <div class="iitem-label">📋 Clases NIZA Registradas</div>
                    <div class="iitem-val"><?= bv($brand,'inpi_clases_registradas') ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── HISTORIA Y CLIENTELA ────────────────────────────── -->
    <?php if (!empty($brand['historia_marca']) || !empty($brand['target_audience']) || !empty($brand['propuesta_valor'])): ?>
    <div class="sec">
        <div class="sec-head">
            <div class="sec-icon" style="background:#8e44ad;">📖</div>
            <div>
                <div class="sec-title">Historia y Clientela</div>
                <div class="sec-sub">Origen, público objetivo y propuesta de valor</div>
            </div>
        </div>
        <div class="sec-body">
            <?php if (!empty($brand['historia_marca'])): ?>
            <div class="text-label">📖 Historia de la Marca</div>
            <div class="text-block"><?= htmlspecialchars($brand['historia_marca']) ?></div>
            <?php endif; ?>
            <?php if (!empty($brand['target_audience'])): ?>
            <?php if (!empty($brand['historia_marca'])): ?><hr class="text-sep"><?php endif; ?>
            <div class="text-label">🎯 Perfil de la Clientela / Público Objetivo</div>
            <div class="text-block"><?= htmlspecialchars($brand['target_audience']) ?></div>
            <?php endif; ?>
            <?php if (!empty($brand['propuesta_valor'])): ?>
            <hr class="text-sep">
            <div class="text-label">💡 Propuesta de Valor</div>
            <div class="text-block"><?= htmlspecialchars($brand['propuesta_valor']) ?></div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── REDES SOCIALES ──────────────────────────────────── -->
    <?php if (!empty($redesActivas) || !empty($brand['whatsapp']) || !empty($brand['website'])): ?>
    <div class="sec">
        <div class="sec-head">
            <div class="sec-icon" style="background:#e1306c;">📱</div>
            <div>
                <div class="sec-title">Redes Sociales y Presencia Digital</div>
                <div class="sec-sub">Canales digitales activos</div>
            </div>
        </div>
        <div class="sec-body">
            <div class="redes-grid">
                <?php foreach ($redesActivas as $k => $r): ?>
                <a href="<?= htmlspecialchars($r['url'] . $r['handle']) ?>" target="_blank" rel="noopener" class="red-card">
                    <div class="red-icon"><?= $r['icon'] ?></div>
                    <div class="red-info">
                        <div class="red-name"><?= $r['label'] ?></div>
                        <div class="red-handle">@<?= htmlspecialchars($r['handle']) ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
                <?php if (!empty($brand['whatsapp'])): ?>
                <a href="https://wa.me/<?= preg_replace('/[^0-9]/','', $brand['whatsapp']) ?>" target="_blank" rel="noopener" class="red-card">
                    <div class="red-icon">💬</div>
                    <div class="red-info">
                        <div class="red-name">WhatsApp Business</div>
                        <div class="red-handle"><?= htmlspecialchars($brand['whatsapp']) ?></div>
                    </div>
                </a>
                <?php endif; ?>
                <?php if (!empty($brand['website'])): ?>
                <a href="<?= htmlspecialchars($brand['website']) ?>" target="_blank" rel="noopener" class="red-card">
                    <div class="red-icon">🌐</div>
                    <div class="red-info">
                        <div class="red-name">Sitio Web</div>
                        <div class="red-handle"><?= htmlspecialchars(preg_replace('#^https?://#','', $brand['website'])) ?></div>
                    </div>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── PROTECCIÓN Y CONDICIONES ───────────────────────── -->
    <?php
    $hasProteccion = bFlag($brand,'tiene_zona') || bFlag($brand,'tiene_licencia') || bFlag($brand,'es_franquicia') || bFlag($brand,'zona_exclusiva');
    ?>
    <?php if ($hasProteccion): ?>
    <div class="sec">
        <div class="sec-head">
            <div class="sec-icon" style="background:#0f766e;">🛡️</div>
            <div>
                <div class="sec-title">Protección y Condiciones</div>
                <div class="sec-sub">Zona de influencia, licencias y modelo de expansión</div>
            </div>
        </div>
        <div class="sec-body">
            <div class="flags-row">
                <?php if (bFlag($brand,'tiene_zona')): ?>
                <span class="flag-on">🌐 Zona de Influencia · <?= (int)($brand['zona_radius_km'] ?? 10) ?> km</span>
                <?php endif; ?>
                <?php if (bFlag($brand,'tiene_licencia')): ?>
                <span class="flag-on">📜 Opera con Licencia</span>
                <?php endif; ?>
                <?php if (bFlag($brand,'es_franquicia')): ?>
                <span class="flag-on">🏢 Modelo de Franquicia</span>
                <?php endif; ?>
                <?php if (bFlag($brand,'zona_exclusiva')): ?>
                <span class="flag-on">🎯 Zona Exclusiva · <?= (int)($brand['zona_exclusiva_radius_km'] ?? 2) ?> km</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── GALERÍA DE FOTOS ─────────────────────────────────── -->
    <div class="sec">
        <div class="sec-head">
            <div class="sec-icon" style="background:#db2777;">🖼️</div>
            <div>
                <div class="sec-title">Galería</div>
                <div class="sec-sub"><?= count($photos) ?> imagen<?= count($photos) !== 1 ? 'es' : '' ?></div>
            </div>
        </div>
        <div class="sec-body">
            <?php if (!empty($photos)): ?>
            <div class="gal-grid">
                <?php foreach ($photos as $p): ?>
                <div class="gal-item" onclick="window.open('<?= htmlspecialchars($p['file_path']) ?>','_blank')">
                    <img src="<?= htmlspecialchars($p['file_path']) ?>" alt="<?= htmlspecialchars($brand['nombre']) ?>">
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="gal-grid">
                <div class="gal-item gal-placeholder"><?= $icon ?></div>
            </div>
            <p style="margin-top:10px;font-size:.84em;color:var(--gray4);">Sin imágenes aún.<?= $canEdit ? ' <a href="/brand_form?id=' . $brandId . '#sec-galeria">Subí fotos</a>' : '' ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── HERRAMIENTAS DE ANÁLISIS ────────────────────────── -->
    <?php if ($canEdit): ?>
    <div class="sec">
        <div class="sec-head">
            <div class="sec-icon" style="background:#0369a1;">🔬</div>
            <div>
                <div class="sec-title">Herramientas de Análisis</div>
                <div class="sec-sub">Profundizá el estudio de tu marca</div>
            </div>
        </div>
        <div class="sec-body">
            <div class="tools-grid">
                <a href="/brand_analysis?id=<?= $brandId ?>" class="tool-card">
                    <div class="tool-icon">📊</div>
                    <div>
                        <div class="tool-name">Análisis Marcario</div>
                        <div class="tool-desc">Fortalezas, debilidades, oportunidades</div>
                    </div>
                </a>
                <a href="/niza_classification?id=<?= $brandId ?>" class="tool-card">
                    <div class="tool-icon">📋</div>
                    <div>
                        <div class="tool-name">Clasificación Niza</div>
                        <div class="tool-desc">Clases de productos y servicios</div>
                    </div>
                </a>
                <a href="/business_model?id=<?= $brandId ?>" class="tool-card">
                    <div class="tool-icon">♟️</div>
                    <div>
                        <div class="tool-name">Modelos de Negocio</div>
                        <div class="tool-desc">Canvas, estrategia, monetización</div>
                    </div>
                </a>
                <a href="/monetization?id=<?= $brandId ?>" class="tool-card">
                    <div class="tool-icon">💰</div>
                    <div>
                        <div class="tool-name">Monetización</div>
                        <div class="tool-desc">Estrategias de ingresos</div>
                    </div>
                </a>
                <a href="/legal_risk?id=<?= $brandId ?>" class="tool-card">
                    <div class="tool-icon">⚖️</div>
                    <div>
                        <div class="tool-name">Riesgo Legal</div>
                        <div class="tool-desc">Exposición y vulnerabilidades legales</div>
                    </div>
                </a>
                <a href="/brand_report?id=<?= $brandId ?>" class="tool-card">
                    <div class="tool-icon">📑</div>
                    <div>
                        <div class="tool-name">Reporte Ejecutivo</div>
                        <div class="tool-desc">Resumen completo descargable</div>
                    </div>
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── ACCIONES ─────────────────────────────────────────── -->
    <div class="sec">
        <div class="action-bar">
            <a href="/marcas" class="abtn abtn-ghost">← Volver al mapa</a>
            <a href="/dashboard_brands" class="abtn abtn-ghost">🏷️ Mis Marcas</a>
            <?php if ($canEdit): ?>
            <a href="/brand_form?id=<?= $brandId ?>" class="abtn abtn-edit">✏️ Editar marca</a>
            <?php endif; ?>
            <button class="abtn abtn-share" onclick="compartirMarca()">📤 Compartir</button>
        </div>
    </div>

</div><!-- /main -->

<footer class="footer">
    <p>&copy; <?= date('Y') ?> Mapita — <a href="/">Volver al inicio</a></p>
</footer>

<script>
function compartirMarca() {
    const url  = window.location.href;
    const name = <?= json_encode($brand['nombre']) ?>;
    const txt  = `¡Mirá esta marca: ${name} en Mapita! ${url}`;
    if (navigator.share) {
        navigator.share({ title: name, text: txt, url }).catch(() => {});
    } else {
        navigator.clipboard.writeText(txt).then(() => alert('¡Enlace copiado al portapapeles!'));
    }
}
</script>
</body>
</html>
