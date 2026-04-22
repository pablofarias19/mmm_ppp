<?php
/**
 * Vista pública de negocio — diseño profesional
 * Muestra galería, redes, servicios, tags, mapa mini y reseñas
 */
session_start();
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../includes/db_helper.php';
require_once __DIR__ . '/../business/process_business.php';

setSecurityHeaders();

$businessId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($businessId <= 0) { header("Location: /"); exit(); }

$db   = getDbConnection();
$stmt = $db->prepare("SELECT * FROM businesses WHERE id = ? AND visible = 1");
$stmt->execute([$businessId]);
$business = $stmt->fetch();

// Admin o propietario pueden ver negocios ocultos
if (!$business && isset($_SESSION['user_id'])) {
    $stmt2 = $db->prepare("SELECT * FROM businesses WHERE id = ?");
    $stmt2->execute([$businessId]);
    $business = $stmt2->fetch();
    if ($business && (int)$business['user_id'] !== (int)$_SESSION['user_id'] && empty($_SESSION['is_admin'])) {
        $business = null;
    }
}
if (!$business) { header("Location: /"); exit(); }

$comercioData = getComercioData($businessId);

// ── Galería de fotos ────────────────────────────────────────────────────────
$uploadDir = __DIR__ . '/../uploads/businesses/' . $businessId . '/';
$galleryPhotos = [];
if (is_dir($uploadDir)) {
    foreach (glob($uploadDir . 'gallery_*.{jpg,jpeg,png,webp}', GLOB_BRACE) as $f) {
        $fname = basename($f);
        $galleryPhotos[] = '/uploads/businesses/' . $businessId . '/' . $fname . '?t=' . filemtime($f);
    }
    usort($galleryPhotos, fn($a, $b) => strcmp($a, $b));
}

// ── OG Cover ────────────────────────────────────────────────────────────────
$ogCoverUrl = null;
foreach (['jpg','jpeg','png','webp'] as $ext) {
    $f = $uploadDir . 'og_cover.' . $ext;
    if (file_exists($f)) {
        $ogCoverUrl = '/uploads/businesses/' . $businessId . '/og_cover.' . $ext . '?t=' . filemtime($f);
        break;
    }
}

// ── Servicios extra (flags en certifications) ───────────────────────────────
$svcFlags = [
    'WiFi'             => ['📶','blue'],
    'Estacionamiento'  => ['🅿️','blue'],
    'Acceso universal' => ['♿','blue'],
    'Reservas online'  => ['📱','blue'],
    'Factura fiscal'   => ['🧾','amber'],
    'Retiro en local'  => ['🛍️','amber'],
    'Mercado Pago'     => ['💙','blue'],
];
$certStr = $business['certifications'] ?? '';
$extraServices = [];
foreach ($svcFlags as $label => [$em, $cls]) {
    if (str_contains($certStr, $label)) $extraServices[] = [$em, $label, $cls];
}
// Texto real de certificaciones (sin las flags)
$certDisplay = preg_replace('/\s*\|\s*(WiFi|Estacionamiento|Acceso universal|Reservas online|Factura fiscal|Retiro en local|Mercado Pago)(,\s*[^|]*)?/', '', $certStr);
$certDisplay = trim(preg_replace('/^\s*\|\s*/', '', $certDisplay));

// ── Tags de productos/servicios ─────────────────────────────────────────────
$tags = array_filter(array_map('trim', explode(',', $comercioData['categorias_productos'] ?? '')));

// ── Tipo → icono y color ────────────────────────────────────────────────────
$typeIcons = [
    'restaurante'=>'🍽️','cafeteria'=>'☕','bar'=>'🍺','panaderia'=>'🥐',
    'heladeria'=>'🍦','pizzeria'=>'🍕',
    'supermercado'=>'🛒','comercio'=>'🛍️','indumentaria'=>'👕',
    'verduleria'=>'🥦','carniceria'=>'🥩','pastas'=>'🍝',
    'ferreteria'=>'🔧','electronica'=>'📱','muebleria'=>'🛋️',
    'floristeria'=>'💐','libreria'=>'📖','productora_audiovisual'=>'🎥','escuela_musicos'=>'🎼',
    'taller_artes'=>'🎨','biodecodificacion'=>'🧬','libreria_cristiana'=>'📚',
    'kiosco'=>'🏪','joyeria'=>'💍','optica'=>'👓',
    'farmacia'=>'💊','hospital'=>'🏥','medico_pediatra'=>'🧒','medico_traumatologo'=>'🦴',
    'laboratorio'=>'🧪','odontologia'=>'🦷','veterinaria'=>'🐾',
    'psicologo'=>'🧠','psicopedagogo'=>'📚','fonoaudiologo'=>'🗣️','grafologo'=>'✍️',
    'salon_belleza'=>'💇','barberia'=>'💈','spa'=>'💆','gimnasio'=>'💪','danza'=>'💃',
    'banco'=>'🏦','inmobiliaria'=>'🏠','seguros'=>'🛡️','abogado'=>'⚖️','contador'=>'📊',
    'ingenieria_civil'=>'🏗️','electricista'=>'💡','gasista'=>'🔥','gas_en_garrafa'=>'🛢️',
    'seguridad'=>'🛡️','grafica'=>'🖨️','astrologo'=>'🔮','zapatero'=>'👞','videojuegos'=>'🎮',
    'maestro_particular'=>'📘','asistencia_ancianos'=>'🧓','enfermeria'=>'🩺',
    'alquiler_mobiliario_fiestas'=>'🪑','propalacion_musica'=>'🔊','animacion_fiestas'=>'🎉',
    'arquitectura'=>'📐','ingenieria'=>'⚙️','taller'=>'🔩','herreria'=>'🔨',
    'carpinteria'=>'🪵','modista'=>'🧵','construccion'=>'🏗️',
    'centro_vecinal'=>'🏘️',
    'academia'=>'🎓','idiomas'=>'🌐','escuela'=>'🏫',
    'hotel'=>'🏨','turismo'=>'✈️','cine'=>'🎬',
    'automotriz'=>'🚗','transporte'=>'🚌','fotografia'=>'📷','eventos'=>'🎉',
    'otros'=>'📍',
];
$icon = $typeIcons[$business['business_type']] ?? '📍';

$typeColors = [
    'restaurante'   =>['#e74c3c','#c0392b'],'cafeteria'    =>['#8B5E3C','#6B3F1F'],
    'bar'           =>['#f39c12','#d68910'],'panaderia'    =>['#e67e22','#ca6f1e'],
    'heladeria'     =>['#3498db','#2980b9'],'pizzeria'     =>['#e74c3c','#922b21'],
    'supermercado'  =>['#27ae60','#1e8449'],'comercio'     =>['#e74c3c','#c0392b'],
    'indumentaria'  =>['#9b59b6','#7d3c98'],'verduleria'   =>['#2ecc71','#27ae60'],
    'carniceria'    =>['#c0392b','#922b21'],'pastas'       =>['#e67e22','#d35400'],
    'ferreteria'    =>['#7f8c8d','#6c7a89'],'electronica'  =>['#2980b9','#1a5276'],
    'productora_audiovisual' =>['#6c5ce7','#4b3fb0'],'escuela_musicos'=>['#8e44ad','#6c3483'],
    'taller_artes'  =>['#e67e22','#ca6f1e'],'biodecodificacion'=>['#16a085','#117864'],
    'libreria_cristiana'=>['#2d6a4f','#1b4332'],
    'farmacia'      =>['#9b59b6','#7d3c98'],'hospital'     =>['#2ecc71','#27ae60'],
    'medico_pediatra'=>['#0ea5e9','#0369a1'],'medico_traumatologo'=>['#2563eb','#1e3a8a'],
    'laboratorio'   =>['#14b8a6','#0f766e'],
    'odontologia'   =>['#3498db','#2980b9'],'veterinaria'  =>['#20c997','#12b886'],
    'psicologo'     =>['#8e44ad','#7d3c98'],'psicopedagogo'=>['#9b59b6','#7d3c98'],
    'fonoaudiologo' =>['#1abc9c','#16a085'],'grafologo'    =>['#7f8c8d','#6c7a89'],
    'salon_belleza' =>['#e91e63','#c2185b'],'barberia'     =>['#c0392b','#922b21'],
    'spa'           =>['#f06595','#e64980'],'gimnasio'     =>['#8e44ad','#7d3c98'],
    'danza'         =>['#e91e63','#c2185b'],
    'banco'         =>['#2c3e50','#1a252f'],'inmobiliaria' =>['#27ae60','#1e8449'],
    'seguros'       =>['#2980b9','#1a5276'],'abogado'      =>['#34495e','#2c3e50'],
    'contador'      =>['#2c3e50','#1a252f'],'arquitectura' =>['#2980b9','#1a5276'],
    'ingenieria'    =>['#7f8c8d','#6c7a89'],'taller'       =>['#7f8c8d','#6c7a89'],
    'herreria'      =>['#95a5a6','#7f8c8d'],'carpinteria'  =>['#8e6914','#7a5c10'],
    'modista'       =>['#e91e63','#c2185b'],'construccion' =>['#e67e22','#d35400'],
    'ingenieria_civil'=>['#f59e0b','#b45309'],'electricista'=>['#facc15','#ca8a04'],
    'gasista'       =>['#f97316','#c2410c'],'gas_en_garrafa'=>['#0ea5e9','#1d4ed8'],
    'seguridad'     =>['#334155','#1e293b'],'grafica'=>['#a855f7','#7e22ce'],
    'astrologo'     =>['#6366f1','#4338ca'],'zapatero'=>['#7c2d12','#451a03'],
    'videojuegos'   =>['#8b5cf6','#6d28d9'],'maestro_particular'=>['#0ea5e9','#075985'],
    'asistencia_ancianos'=>['#14b8a6','#0f766e'],'enfermeria'=>['#0ea5e9','#0369a1'],
    'alquiler_mobiliario_fiestas'=>['#f59e0b','#d97706'],'propalacion_musica'=>['#6366f1','#4338ca'],
    'animacion_fiestas'=>['#ec4899','#be185d'],
    'centro_vecinal'=>['#27ae60','#1e8449'],
    'academia'      =>['#2980b9','#1a5276'],'idiomas'      =>['#00b4d8','#0077b6'],
    'escuela'       =>['#3498db','#2980b9'],'hotel'        =>['#1B3B6F','#0f2444'],
    'turismo'       =>['#00b4d8','#0077b6'],'cine'         =>['#8e44ad','#7d3c98'],
];
$colors = $typeColors[$business['business_type']] ?? ['#1B3B6F','#0f2444'];

// ── OG / Meta ───────────────────────────────────────────────────────────────
$scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'mapita.com.ar';
$og_title = $business['name'] . ' — ' . ucfirst($business['business_type'] ?? 'Negocio') . ' en Mapita';
$og_desc  = $business['description'] ? mb_substr($business['description'], 0, 180) : 'Encontrá ' . $business['name'] . ' en el mapa — dirección, horarios, contacto y más en Mapita.';
$og_image = $ogCoverUrl ? ($scheme.'://'.$host.$ogCoverUrl) : ($scheme.'://'.$host.'/api/og_image.php?type=business&id='.$businessId);
$og_url   = $scheme.'://'.$host.'/business/view_business.php?id='.$businessId;

$isOwnerOrAdmin = isset($_SESSION['user_id']) && ((int)$business['user_id'] === (int)$_SESSION['user_id'] || !empty($_SESSION['is_admin']));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($business['name']) ?> · Mapita</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="<?= htmlspecialchars($og_desc) ?>">
<!-- Open Graph -->
<meta property="og:title" content="<?= htmlspecialchars($og_title) ?>">
<meta property="og:description" content="<?= htmlspecialchars($og_desc) ?>">
<meta property="og:image" content="<?= htmlspecialchars($og_image) ?>">
<meta property="og:url" content="<?= htmlspecialchars($og_url) ?>">
<meta property="og:type" content="business.business">
<meta name="twitter:card" content="summary_large_image">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
/* ── Base ─────────────────────────────────────────────────────────────────── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f0f2f5;color:#1a1a2e;min-height:100vh}
a{text-decoration:none;color:inherit}

/* ── Barra de navegación superior ─────────────────────────────────────────── */
.top-nav{position:fixed;top:0;left:0;right:0;z-index:1000;height:48px;
  background:rgba(255,255,255,.95);backdrop-filter:blur(10px);
  border-bottom:1px solid rgba(0,0,0,.08);
  display:flex;align-items:center;gap:10px;padding:0 14px}
.top-nav .back{display:flex;align-items:center;gap:6px;color:#1B3B6F;
  font-size:.88em;font-weight:700;padding:6px 10px;border-radius:8px;
  background:#eff6ff;transition:background .15s}
.top-nav .back:hover{background:#dbeafe}
.top-nav .nav-title{flex:1;font-size:.9em;font-weight:700;color:#333;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.top-nav .edit-btn{padding:6px 14px;background:#0ea5e9;color:white;
  border-radius:8px;font-size:.82em;font-weight:700;transition:background .15s}
.top-nav .edit-btn:hover{background:#0284c7}

/* ── Hero ─────────────────────────────────────────────────────────────────── */
.hero{position:relative;margin-top:48px;height:220px;overflow:hidden;
  background:linear-gradient(135deg,<?= $colors[0] ?> 0%,<?= $colors[1] ?> 100%)}
@media(min-width:640px){.hero{height:300px}}
.hero-cover{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.hero-overlay{position:absolute;inset:0;
  background:linear-gradient(to bottom,rgba(0,0,0,.05) 0%,rgba(0,0,0,.72) 100%)}
.hero-body{position:absolute;bottom:0;left:0;right:0;padding:18px 20px}
.hero-icon{font-size:2.6rem;line-height:1;margin-bottom:6px;filter:drop-shadow(0 2px 4px rgba(0,0,0,.4))}
.hero-name{font-size:1.65em;font-weight:900;color:#fff;line-height:1.15;
  text-shadow:0 1px 6px rgba(0,0,0,.5)}
@media(min-width:640px){.hero-name{font-size:2.1em}}
.hero-badges{display:flex;gap:7px;flex-wrap:wrap;margin-top:9px}
.hbadge{display:inline-flex;align-items:center;gap:4px;
  padding:3px 10px;border-radius:20px;font-size:.76em;font-weight:700}
.hbadge-type{background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.35)}
.hbadge-verified{background:#10b981;color:#fff}
.hbadge-hidden{background:#6b7280;color:#fff}
.hbadge-delivery{background:#f59e0b;color:#fff}
.hbadge-card{background:#6366f1;color:#fff}

/* ── Acciones rápidas ─────────────────────────────────────────────────────── */
.quick-bar{display:flex;gap:8px;padding:12px 14px;overflow-x:auto;
  background:#fff;border-bottom:1px solid #f0f0f0;
  -webkit-overflow-scrolling:touch;scrollbar-width:none}
.quick-bar::-webkit-scrollbar{display:none}
.qbtn{display:flex;flex-direction:column;align-items:center;gap:3px;
  min-width:64px;padding:8px 12px;border-radius:12px;border:none;cursor:pointer;
  font-family:inherit;font-size:.7em;font-weight:700;transition:all .15s;
  background:#f3f4f6;color:#374151;text-align:center}
.qbtn .qi{font-size:1.55rem;line-height:1}
.qbtn:hover{background:#e0e7ff;color:#1B3B6F}
.qbtn.call{background:#1B3B6F;color:#fff}
.qbtn.call:hover{background:#0f2444}
.qbtn.wa{background:#25D366;color:#fff}
.qbtn.wa:hover{background:#128C7E}

/* ── Contenedor central ───────────────────────────────────────────────────── */
.wrap{max-width:800px;margin:0 auto;padding:14px 12px 40px}
@media(min-width:640px){.wrap{padding:18px 20px 50px}}

/* ── Tarjetas ─────────────────────────────────────────────────────────────── */
.card{background:#fff;border-radius:18px;margin-bottom:14px;
  box-shadow:0 1px 6px rgba(0,0,0,.07);overflow:hidden}
.card-head{display:flex;align-items:center;gap:10px;
  padding:13px 18px;border-bottom:1px solid #f3f4f6}
.card-head h3{font-size:.95em;font-weight:800;color:#111}
.card-body{padding:16px 18px}

/* ── Descripción ──────────────────────────────────────────────────────────── */
.desc-txt{color:#4b5563;line-height:1.75;font-size:.93em}

/* ── Galería ──────────────────────────────────────────────────────────────── */
.gallery-row{display:flex;gap:8px;padding:0 18px 16px;
  overflow-x:auto;scrollbar-width:thin;scrollbar-color:#d1d5db transparent;
  -webkit-overflow-scrolling:touch}
.gallery-row::-webkit-scrollbar{height:4px}
.gallery-row::-webkit-scrollbar-thumb{background:#d1d5db;border-radius:2px}
.gthumb{flex:0 0 auto;width:130px;height:100px;border-radius:12px;overflow:hidden;
  cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,.12)}
@media(min-width:500px){.gthumb{width:160px;height:120px}}
.gthumb img{width:100%;height:100%;object-fit:cover;transition:transform .25s}
.gthumb:hover img{transform:scale(1.06)}

/* ── Filas de info ────────────────────────────────────────────────────────── */
.irow{display:flex;align-items:flex-start;gap:14px;
  padding:11px 0;border-bottom:1px solid #f3f4f6}
.irow:last-child{border-bottom:none}
.iico{font-size:1.2rem;flex:0 0 24px;text-align:center;margin-top:2px}
.icont{flex:1;min-width:0}
.ilabel{font-size:.7em;color:#9ca3af;font-weight:700;
  text-transform:uppercase;letter-spacing:.07em;margin-bottom:2px}
.ival{font-size:.91em;color:#374151;word-break:break-word;line-height:1.5}
.ival a{color:#1B3B6F;font-weight:600}
.ival a:hover{text-decoration:underline}

/* ── Precio ───────────────────────────────────────────────────────────────── */
.price-chip{display:inline-flex;align-items:center;gap:4px;
  background:#d1fae5;color:#065f46;padding:3px 12px;
  border-radius:20px;font-size:.92em;font-weight:800}

/* ── Chips de servicio ────────────────────────────────────────────────────── */
.svc-grid{display:flex;flex-wrap:wrap;gap:8px}
.svc-chip{display:inline-flex;align-items:center;gap:6px;
  padding:6px 14px;border-radius:20px;font-size:.81em;font-weight:700}
.svc-chip.blue{background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe}
.svc-chip.green{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0}
.svc-chip.amber{background:#fffbeb;color:#92400e;border:1px solid #fde68a}
.svc-chip.purple{background:#faf5ff;color:#7e22ce;border:1px solid #e9d5ff}

/* ── Tags ─────────────────────────────────────────────────────────────────── */
.tags-grid{display:flex;flex-wrap:wrap;gap:8px}
.tag-chip{padding:5px 13px;border-radius:20px;font-size:.81em;font-weight:600;
  background:#f8fafc;color:#475569;border:1px solid #e2e8f0}

/* ── Social ───────────────────────────────────────────────────────────────── */
.social-list{display:flex;gap:10px;flex-wrap:wrap}
.soc-btn{display:inline-flex;align-items:center;gap:8px;
  padding:9px 16px;border-radius:24px;font-size:.84em;font-weight:700;
  transition:opacity .15s}
.soc-btn:hover{opacity:.85}
.soc-ig{background:linear-gradient(135deg,#405de6,#5851db,#833ab4,#c13584,#e1306c,#fd1d1d);color:#fff}
.soc-fb{background:#1877f2;color:#fff}
.soc-tt{background:#010101;color:#fff}

/* ── Mapa mini ────────────────────────────────────────────────────────────── */
/* isolation:isolate evita que Leaflet (z-index hasta 700) tape el header     */
#map-mini{height:220px;isolation:isolate}
@media(min-width:640px){#map-mini{height:280px}}

/* ── Reseñas ──────────────────────────────────────────────────────────────── */
.rat-summary{display:flex;align-items:center;gap:16px;padding:14px 18px;
  border-bottom:1px solid #f3f4f6}
.rat-score{font-size:3em;font-weight:900;color:#1B3B6F;line-height:1}
.rat-stars{font-size:1.5em;letter-spacing:2px}
.rat-count{font-size:.8em;color:#6b7280;margin-top:4px}
.review-item{padding:14px 18px;border-top:1px solid #f3f4f6}
.rev-author{font-weight:700;font-size:.9em;color:#111}
.rev-stars{font-size:1em;margin-left:6px}
.rev-text{color:#555;font-size:.87em;line-height:1.55;margin:5px 0}
.rev-date{font-size:.73em;color:#bbb}
.rev-form{padding:16px 18px;border-top:1px solid #f3f4f6}
/* Star selector CSS-only */
.star-pick{display:flex;flex-direction:row-reverse;width:fit-content;gap:2px;margin:10px 0}
.star-pick input{display:none}
.star-pick label{font-size:2rem;cursor:pointer;color:#d1d5db;transition:color .1s}
.star-pick input:checked~label,.star-pick label:hover,.star-pick label:hover~label{color:#f59e0b}
.rev-txt{width:100%;padding:10px;border:1.5px solid #e5e7eb;border-radius:10px;
  font-family:inherit;font-size:.9em;resize:vertical;min-height:80px;transition:border-color .2s}
.rev-txt:focus{outline:none;border-color:#1B3B6F;box-shadow:0 0 0 3px rgba(27,59,111,.08)}
.btn-rev{background:#1B3B6F;color:#fff;border:none;padding:10px 24px;
  border-radius:10px;cursor:pointer;font-weight:800;font-size:.9em;margin-top:8px;transition:background .15s}
.btn-rev:hover{background:#0f2444}
.no-reviews{padding:18px;text-align:center;color:#9ca3af;font-size:.88em}

/* ── Barra de acciones finales ────────────────────────────────────────────── */
.act-bar{display:flex;gap:10px;flex-wrap:wrap;padding:6px 0 24px}
.act-btn{padding:11px 22px;border-radius:12px;font-size:.9em;font-weight:800;
  cursor:pointer;border:none;font-family:inherit;transition:all .15s}
.act-edit{background:#0ea5e9;color:#fff}
.act-edit:hover{background:#0284c7}
.act-map{background:#1B3B6F;color:#fff}
.act-map:hover{background:#0f2444}
.act-share{background:#f3f4f6;color:#374151}
.act-share:hover{background:#e5e7eb}

/* ── Lightbox ─────────────────────────────────────────────────────────────── */
.lb{display:none;position:fixed;inset:0;z-index:999;
  background:rgba(0,0,0,.93);align-items:center;justify-content:center}
.lb.open{display:flex}
.lb img{max-width:96vw;max-height:90vh;border-radius:10px;object-fit:contain}
.lb-close{position:absolute;top:14px;right:18px;color:#fff;
  font-size:2.2em;cursor:pointer;line-height:1;opacity:.8}
.lb-close:hover{opacity:1}
.lb-prev,.lb-next{position:absolute;top:50%;transform:translateY(-50%);
  color:#fff;font-size:2rem;cursor:pointer;padding:10px;opacity:.7;
  background:rgba(0,0,0,.3);border-radius:50%;line-height:1;border:none;font-family:inherit}
.lb-prev{left:12px}
.lb-next{right:12px}
.lb-prev:hover,.lb-next:hover{opacity:1;background:rgba(0,0,0,.6)}

/* ── Estado vacío ─────────────────────────────────────────────────────────── */
.empty-state{text-align:center;padding:28px 16px;color:#9ca3af;font-size:.88em}
.empty-state .ei{font-size:2.5rem;margin-bottom:10px;opacity:.5}
</style>
</head>
<body>

<!-- Barra de navegación superior -->
<nav class="top-nav">
    <a href="/" class="back">← Mapa</a>
    <span class="nav-title"><?= htmlspecialchars($business['name']) ?></span>
    <?php if ($isOwnerOrAdmin): ?>
        <a href="/edit?id=<?= $business['id'] ?>" class="edit-btn">✏️ Editar</a>
    <?php endif; ?>
</nav>

<!-- Hero / Portada -->
<div class="hero">
    <?php if ($ogCoverUrl): ?>
        <img class="hero-cover" src="<?= htmlspecialchars($ogCoverUrl) ?>" alt="<?= htmlspecialchars($business['name']) ?>">
    <?php endif; ?>
    <div class="hero-overlay"></div>
    <div class="hero-body">
        <div class="hero-icon"><?= $icon ?></div>
        <div class="hero-name"><?= htmlspecialchars($business['name']) ?></div>
        <div class="hero-badges">
            <span class="hbadge hbadge-type"><?= htmlspecialchars(ucwords(str_replace('_',' ',$business['business_type'] ?? 'Negocio'))) ?></span>
            <?php if (!empty($business['verified'])): ?><span class="hbadge hbadge-verified">✓ Verificado</span><?php endif; ?>
            <?php if (!empty($business['has_delivery'])): ?><span class="hbadge hbadge-delivery">🚚 Delivery</span><?php endif; ?>
            <?php if (!empty($business['has_card_payment'])): ?><span class="hbadge hbadge-card">💳 Tarjeta</span><?php endif; ?>
            <?php if (!$business['visible']): ?><span class="hbadge hbadge-hidden">👁 Oculto</span><?php endif; ?>
        </div>
    </div>
</div>

<!-- Acciones rápidas -->
<div class="quick-bar">
    <?php if (!empty($business['phone'])): ?>
        <a href="tel:<?= htmlspecialchars($business['phone']) ?>" class="qbtn call">
            <span class="qi">📞</span>Llamar
        </a>
        <a href="https://wa.me/<?= preg_replace('/\D/', '', $business['phone']) ?>" target="_blank" rel="noopener" class="qbtn wa">
            <span class="qi">💬</span>WhatsApp
        </a>
    <?php endif; ?>
    <?php if (!empty($business['website'])): ?>
        <a href="<?= htmlspecialchars($business['website']) ?>" target="_blank" rel="noopener" class="qbtn">
            <span class="qi">🌐</span>Sitio web
        </a>
    <?php endif; ?>
    <?php if ($business['lat'] && $business['lng']): ?>
        <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $business['lat'] ?>,<?= $business['lng'] ?>" target="_blank" rel="noopener" class="qbtn">
            <span class="qi">🗺️</span>Cómo llegar
        </a>
    <?php endif; ?>
    <?php if (!empty($business['instagram'])): ?>
        <?php $igUrl = str_starts_with($business['instagram'], 'http') ? $business['instagram'] : 'https://instagram.com/' . ltrim($business['instagram'], '@'); ?>
        <a href="<?= htmlspecialchars($igUrl) ?>" target="_blank" rel="noopener" class="qbtn">
            <span class="qi">📸</span>Instagram
        </a>
    <?php endif; ?>
    <button onclick="sharePage()" class="qbtn"><span class="qi">📤</span>Compartir</button>
</div>

<div class="wrap">

    <!-- Descripción -->
    <?php if (!empty($business['description'])): ?>
    <div class="card">
        <div class="card-head"><span>📝</span><h3>Descripción</h3></div>
        <div class="card-body">
            <p class="desc-txt"><?= nl2br(htmlspecialchars($business['description'])) ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Galería de fotos -->
    <?php if (!empty($galleryPhotos)): ?>
    <div class="card">
        <div class="card-head"><span>📸</span><h3>Galería — <?= count($galleryPhotos) ?> foto<?= count($galleryPhotos) > 1 ? 's' : '' ?></h3></div>
        <div class="gallery-row" style="padding-top:12px;">
            <?php foreach ($galleryPhotos as $i => $url): ?>
                <div class="gthumb" onclick="openLb(<?= $i ?>)">
                    <img src="<?= htmlspecialchars($url) ?>" alt="Foto <?= $i+1 ?>" loading="lazy">
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Información de contacto y horarios -->
    <div class="card">
        <div class="card-head"><span>📋</span><h3>Información</h3></div>
        <div class="card-body">
            <?php if (!empty($business['address'])): ?>
            <div class="irow">
                <span class="iico">📍</span>
                <div class="icont">
                    <div class="ilabel">Dirección</div>
                    <div class="ival"><?= htmlspecialchars($business['address']) ?>
                        <?php if (!empty($business['location_city'])): ?>
                            <span style="color:#9ca3af"> · <?= htmlspecialchars($business['location_city']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($comercioData['horario_apertura']) && !empty($comercioData['horario_cierre'])): ?>
            <div class="irow">
                <span class="iico">🕒</span>
                <div class="icont">
                    <div class="ilabel">Horario de atención</div>
                    <div class="ival">
                        <?= htmlspecialchars(substr($comercioData['horario_apertura'],0,5)) ?> — <?= htmlspecialchars(substr($comercioData['horario_cierre'],0,5)) ?>
                        <?php if (!empty($comercioData['dias_cierre'])): ?>
                            <span style="color:#9ca3af;font-size:.88em"> · Cierra: <?= htmlspecialchars($comercioData['dias_cierre']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($business['phone'])): ?>
            <div class="irow">
                <span class="iico">📞</span>
                <div class="icont">
                    <div class="ilabel">Teléfono / WhatsApp</div>
                    <div class="ival">
                        <a href="tel:<?= htmlspecialchars($business['phone']) ?>"><?= htmlspecialchars($business['phone']) ?></a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($business['email'])): ?>
            <div class="irow">
                <span class="iico">📧</span>
                <div class="icont">
                    <div class="ilabel">Correo electrónico</div>
                    <div class="ival"><a href="mailto:<?= htmlspecialchars($business['email']) ?>"><?= htmlspecialchars($business['email']) ?></a></div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($business['website'])): ?>
            <div class="irow">
                <span class="iico">🌐</span>
                <div class="icont">
                    <div class="ilabel">Sitio web</div>
                    <div class="ival"><a href="<?= htmlspecialchars($business['website']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars(preg_replace('/^https?:\/\//', '', $business['website'])) ?></a></div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($business['price_range'])): ?>
            <div class="irow">
                <span class="iico">💰</span>
                <div class="icont">
                    <div class="ilabel">Rango de precio</div>
                    <div class="ival"><span class="price-chip"><?= str_repeat('$', intval($business['price_range'])) ?></span></div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($certDisplay)): ?>
            <div class="irow">
                <span class="iico">🏆</span>
                <div class="icont">
                    <div class="ilabel">Premios y certificaciones</div>
                    <div class="ival"><?= htmlspecialchars($certDisplay) ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Servicios y características -->
    <?php
    $allServices = [];
    if (!empty($business['has_delivery']))     $allServices[] = ['🚚','Delivery / Envío','green'];
    if (!empty($business['has_card_payment'])) $allServices[] = ['💳','Pago con tarjeta','blue'];
    if (!empty($business['is_franchise']))     $allServices[] = ['🏷️','Franquicia','purple'];
    foreach ($extraServices as [$em,$lbl,$cls]) $allServices[] = [$em,$lbl,$cls];
    ?>
    <?php if (!empty($allServices)): ?>
    <div class="card">
        <div class="card-head"><span>✅</span><h3>Servicios y características</h3></div>
        <div class="card-body">
            <div class="svc-grid">
                <?php foreach ($allServices as [$em,$lbl,$cls]): ?>
                    <span class="svc-chip <?= $cls ?>"><?= $em ?> <?= htmlspecialchars($lbl) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Productos y tags -->
    <?php if (!empty($tags)): ?>
    <div class="card">
        <div class="card-head"><span>🏷️</span><h3>Productos y servicios</h3></div>
        <div class="card-body">
            <div class="tags-grid">
                <?php foreach ($tags as $tag): ?>
                    <span class="tag-chip"><?= htmlspecialchars($tag) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Redes sociales -->
    <?php $hasSocial = !empty($business['instagram']) || !empty($business['facebook']) || !empty($business['tiktok']); ?>
    <?php if ($hasSocial): ?>
    <div class="card">
        <div class="card-head"><span>📲</span><h3>Redes sociales</h3></div>
        <div class="card-body">
            <div class="social-list">
                <?php if (!empty($business['instagram'])): ?>
                    <?php $igUrl = str_starts_with($business['instagram'], 'http') ? $business['instagram'] : 'https://instagram.com/' . ltrim($business['instagram'], '@'); ?>
                    <a href="<?= htmlspecialchars($igUrl) ?>" target="_blank" rel="noopener" class="soc-btn soc-ig">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="white"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                        @<?= htmlspecialchars(ltrim($business['instagram'], '@')) ?>
                    </a>
                <?php endif; ?>
                <?php if (!empty($business['facebook'])): ?>
                    <?php $fbUrl = str_starts_with($business['facebook'], 'http') ? $business['facebook'] : 'https://facebook.com/' . $business['facebook']; ?>
                    <a href="<?= htmlspecialchars($fbUrl) ?>" target="_blank" rel="noopener" class="soc-btn soc-fb">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="white"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        <?= htmlspecialchars($business['facebook']) ?>
                    </a>
                <?php endif; ?>
                <?php if (!empty($business['tiktok'])): ?>
                    <?php $ttUrl = str_starts_with($business['tiktok'], 'http') ? $business['tiktok'] : 'https://tiktok.com/@' . ltrim($business['tiktok'], '@'); ?>
                    <a href="<?= htmlspecialchars($ttUrl) ?>" target="_blank" rel="noopener" class="soc-btn soc-tt">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="white"><path d="M12.53.02C13.84 0 15.14.01 16.44 0c.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                        @<?= htmlspecialchars(ltrim($business['tiktok'], '@')) ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Ubicación en el mapa -->
    <?php if ($business['lat'] && $business['lng']): ?>
    <div class="card" style="overflow:hidden">
        <div class="card-head"><span>📍</span><h3>Ubicación</h3></div>
        <div id="map-mini"></div>
        <div style="padding:10px 18px;font-size:.84em;color:#6b7280;display:flex;align-items:center;gap:6px;background:#fafafa;border-top:1px solid #f3f4f6">
            <span>📍</span>
            <?= htmlspecialchars($business['address'] ?: 'Ver en el mapa') ?>
            <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $business['lat'] ?>,<?= $business['lng'] ?>"
               target="_blank" rel="noopener"
               style="margin-left:auto;color:#1B3B6F;font-weight:700;font-size:.9em;white-space:nowrap">Cómo llegar →</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Reseñas -->
    <div class="card" id="reviews-section">
        <div class="card-head"><span>⭐</span><h3>Reseñas</h3></div>
        <div id="rat-summary" class="rat-summary" style="display:none">
            <div class="rat-score" id="avg-score">—</div>
            <div>
                <div class="rat-stars" id="avg-stars"></div>
                <div class="rat-count" id="avg-count"></div>
            </div>
        </div>
        <div id="reviews-list"></div>
        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="rev-form">
            <p style="font-size:.88em;font-weight:700;color:#374151;margin-bottom:4px">Tu calificación</p>
            <div class="star-pick">
                <input type="radio" name="star" id="s5" value="5"><label for="s5" title="Excelente">★</label>
                <input type="radio" name="star" id="s4" value="4"><label for="s4" title="Muy bueno">★</label>
                <input type="radio" name="star" id="s3" value="3" checked><label for="s3" title="Regular">★</label>
                <input type="radio" name="star" id="s2" value="2"><label for="s2" title="Malo">★</label>
                <input type="radio" name="star" id="s1" value="1"><label for="s1" title="Muy malo">★</label>
            </div>
            <textarea class="rev-txt" id="review-comment" rows="3" placeholder="Contá tu experiencia (opcional)..."></textarea>
            <button class="btn-rev" id="btn-review">Publicar reseña</button>
            <div id="review-msg" style="margin-top:8px;font-size:.84em;font-weight:600"></div>
        </div>
        <?php else: ?>
        <div style="padding:16px 18px;font-size:.88em;color:#6b7280">
            <a href="/login" style="color:#1B3B6F;font-weight:700">Iniciá sesión</a> para dejar una reseña.
        </div>
        <?php endif; ?>
    </div>

    <!-- Botones de acción al final -->
    <div class="act-bar">
        <?php if ($isOwnerOrAdmin): ?>
            <a href="/edit?id=<?= $business['id'] ?>" class="act-btn act-edit">✏️ Editar negocio</a>
        <?php endif; ?>
        <a href="/" class="act-btn act-map">🗺️ Ver en el mapa</a>
        <button onclick="sharePage()" class="act-btn act-share">📤 Compartir</button>
    </div>

</div><!-- /wrap -->

<!-- Lightbox de galería -->
<div class="lb" id="lb" onclick="handleLbClick(event)">
    <span class="lb-close" onclick="closeLb()">✕</span>
    <?php if (count($galleryPhotos) > 1): ?>
        <button class="lb-prev" onclick="navLb(-1);event.stopPropagation()">‹</button>
        <button class="lb-next" onclick="navLb(1);event.stopPropagation()">›</button>
    <?php endif; ?>
    <img id="lb-img" src="" alt="">
</div>

<!-- ── Scripts ──────────────────────────────────────────────────────────────── -->
<?php if ($business['lat'] && $business['lng']): ?>
<script>
(function(){
    var lat=<?= (float)$business['lat'] ?>,lng=<?= (float)$business['lng'] ?>;
    var map=L.map('map-mini',{zoomControl:true,dragging:true,scrollWheelZoom:false,doubleClickZoom:true}).setView([lat,lng],16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
        attribution:'© <a href="https://www.openstreetmap.org/copyright">OSM</a>'
    }).addTo(map);
    var icon=L.divIcon({
        html:'<div style="background:<?= $colors[0] ?>;color:#fff;width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;box-shadow:0 3px 10px rgba(0,0,0,.35);border:3px solid #fff;"><?= $icon ?></div>',
        className:'',iconSize:[40,40],iconAnchor:[20,20]
    });
    L.marker([lat,lng],{icon}).addTo(map)
     .bindPopup('<strong style="font-size:14px"><?= addslashes(htmlspecialchars($business['name'])) ?></strong>')
     .openPopup();
})();
</script>
<?php endif; ?>

<script>
var businessId=<?= (int)$business['id'] ?>;
var galleryUrls=<?= json_encode(array_values($galleryPhotos)) ?>;
var currentLbIdx=0;

// ── Reviews ──────────────────────────────────────────────────────────────────
function loadReviews(){
    fetch('/api/reviews.php?business_id='+businessId)
    .then(r=>r.json()).then(res=>{
        if(!res.success)return;
        const avg=res.data.average, list=res.data.reviews||[];
        if(avg&&avg.total>0){
            document.getElementById('rat-summary').style.display='flex';
            document.getElementById('avg-score').textContent=parseFloat(avg.avg).toFixed(1);
            document.getElementById('avg-stars').textContent='⭐'.repeat(Math.round(avg.avg));
            document.getElementById('avg-count').textContent=avg.total+' reseña'+(avg.total>1?'s':'');
        }
        const c=document.getElementById('reviews-list');
        if(!list.length){c.innerHTML='<div class="no-reviews">Sin reseñas aún. ¡Sé el primero!</div>';return;}
        c.innerHTML='';
        list.forEach(r=>{
            const d=document.createElement('div');
            d.className='review-item';
            d.innerHTML=`<span class="rev-author">${r.username}</span><span class="rev-stars">${'⭐'.repeat(r.rating)}</span>`
                +(r.comment?`<p class="rev-text">${r.comment}</p>`:'')
                +`<div class="rev-date">${r.created_at}</div>`;
            c.appendChild(d);
        });
    }).catch(console.error);
}
loadReviews();

<?php if (isset($_SESSION['user_id'])): ?>
document.getElementById('btn-review').addEventListener('click',function(){
    const rating=document.querySelector('input[name="star"]:checked')?.value||3;
    const comment=document.getElementById('review-comment').value;
    const msg=document.getElementById('review-msg');
    this.disabled=true;
    fetch('/api/reviews.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({business_id:businessId,rating,comment})
    }).then(r=>r.json()).then(res=>{
        msg.style.color=res.success?'#15803d':'#dc2626';
        msg.textContent=res.message;
        if(res.success){loadReviews();document.getElementById('review-comment').value='';}
        this.disabled=false;
    }).catch(()=>{msg.style.color='#dc2626';msg.textContent='Error al enviar.';this.disabled=false;});
});
<?php endif; ?>

// ── Lightbox ──────────────────────────────────────────────────────────────────
function openLb(i){
    currentLbIdx=i;
    document.getElementById('lb-img').src=galleryUrls[i];
    document.getElementById('lb').classList.add('open');
    document.body.style.overflow='hidden';
}
function closeLb(){
    document.getElementById('lb').classList.remove('open');
    document.body.style.overflow='';
}
function navLb(dir){
    currentLbIdx=(currentLbIdx+dir+galleryUrls.length)%galleryUrls.length;
    document.getElementById('lb-img').src=galleryUrls[currentLbIdx];
}
function handleLbClick(e){if(e.target===document.getElementById('lb'))closeLb();}
document.addEventListener('keydown',e=>{
    if(!document.getElementById('lb').classList.contains('open'))return;
    if(e.key==='Escape')closeLb();
    if(e.key==='ArrowLeft')navLb(-1);
    if(e.key==='ArrowRight')navLb(1);
});

// ── Share ─────────────────────────────────────────────────────────────────────
function sharePage(){
    if(navigator.share){
        navigator.share({
            title:'<?= addslashes(htmlspecialchars($business['name'])) ?> en Mapita',
            text:'Mirá este negocio en Mapita',
            url:window.location.href
        }).catch(()=>{});
    }else{
        navigator.clipboard?.writeText(window.location.href).then(()=>{
            alert('¡Enlace copiado al portapapeles!');
        });
    }
}
</script>
</body>
</html>
