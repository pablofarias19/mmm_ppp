<?php
session_start();
ini_set('display_errors', 0);
error_reporting(0);

if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit();
}

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../includes/db_helper.php';
require_once __DIR__ . '/../business/process_business.php';

setSecurityHeaders();

$message     = '';
$messageType = '';
$currentUser = $_SESSION['user_name'];
$userId      = (int)$_SESSION['user_id'];

// ── Modo añadir vs. editar ────────────────────────────────────────────────────
$businessId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editing    = ($businessId > 0);
$business   = null;
$comercioData  = null;
$ogCoverUrl    = null;
$galleryPhotos = [];

$isAdmin = isAdmin();

if ($editing) {
    $db   = getDbConnection();
    if (!$isAdmin && !canManageBusiness($userId, $businessId)) {
        header("Location: /mis-negocios");
        exit();
    }
    $stmt = $db->prepare("SELECT * FROM businesses WHERE id = ?");
    $stmt->execute([$businessId]);
    $business = $stmt->fetch();
    if (!$business) {
        header("Location: /mis-negocios");
        exit();
    }
    $comercioData = getComercioData($businessId);

    // OG cover
    foreach (['jpg','jpeg','png','webp'] as $_e) {
        $f = __DIR__ . '/../uploads/businesses/' . $businessId . '/og_cover.' . $_e;
        if (file_exists($f)) { $ogCoverUrl = '/uploads/businesses/' . $businessId . '/og_cover.' . $_e . '?t=' . filemtime($f); break; }
    }

    // Gallery photos (gallery_* files)
    $galleryDir = __DIR__ . '/../uploads/businesses/' . $businessId . '/';
    if (is_dir($galleryDir)) {
        foreach (glob($galleryDir . 'gallery_*.{jpg,jpeg,png,webp}', GLOB_BRACE) as $gf) {
            $fn = basename($gf);
            $galleryPhotos[] = ['filename' => $fn, 'url' => '/uploads/businesses/' . $businessId . '/' . $fn . '?t=' . filemtime($gf)];
        }
    }
}

// ── Procesar POST ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    if ($editing) {
        $result = updateBusiness($businessId, $_POST, $userId);
        if ($result['success']) {
            $db   = getDbConnection();
            $stmt = $db->prepare("SELECT * FROM businesses WHERE id = ?");
            $stmt->execute([$businessId]);
            $business     = $stmt->fetch();
            $comercioData = getComercioData($businessId);
        }
    } else {
        $result = addBusiness($_POST, $userId);
        if ($result['success']) {
            // Redirigir a modo edición para que el usuario agregue fotos
            header("Location: /edit?id=" . $result['business_id'] . "&nuevo=1");
            exit();
        }
    }
    $message     = $result['message'];
    $messageType = $result['success'] ? 'success' : 'error';
}

// ── Datos para pre-llenar ─────────────────────────────────────────────────────
function bv(array|null $b, string $k, mixed $def = ''): string {
    return htmlspecialchars(($b[$k] ?? $def), ENT_QUOTES, 'UTF-8');
}

$selectedType    = $business['business_type'] ?? '';
$currentTags     = array_filter(array_map('trim', explode(',', ($comercioData['categorias_productos'] ?? ''))));
$currentDias     = array_filter(array_map('trim', explode(',', ($comercioData['dias_cierre']          ?? ''))));
$certifVal       = $business['certifications'] ?? '';

// Tipos de negocio agrupados
$tipos = [
    'Gastronomía' => [
        'restaurante' => ['🍽️', 'Restaurante'],
        'cafeteria'   => ['☕', 'Cafetería'],
        'bar'         => ['🍺', 'Bar / Pub'],
        'panaderia'   => ['🥐', 'Panadería'],
        'heladeria'   => ['🍦', 'Heladería'],
        'pizzeria'    => ['🍕', 'Pizzería'],
    ],
    'Comercio' => [
        'supermercado' => ['🛒', 'Supermercado'],
        'comercio'     => ['🛍️', 'Tienda / Local'],
        'autos_venta'  => ['🚗', 'Autos a la venta'],
        'motos_venta'  => ['🏍️', 'Motos a la venta'],
        'indumentaria' => ['👕', 'Indumentaria'],
        'verduleria'   => ['🥦', 'Verdulería / Frutería'],
        'carniceria'   => ['🥩', 'Carnicería'],
        'pastas'       => ['🍝', 'Fábrica de Pastas'],
        'ferreteria'   => ['🔧', 'Ferretería'],
        'electronica'  => ['📱', 'Tecnología'],
        'muebleria'    => ['🛋️', 'Mueblería'],
        'floristeria'  => ['💐', 'Floristería'],
        'libreria'     => ['📖', 'Librería'],
        'productora_audiovisual' => ['🎥', 'Productora audiovisual'],
        'escuela_musicos'        => ['🎼', 'Escuela de músicos'],
        'taller_artes'           => ['🎨', 'Taller de artes'],
        'biodecodificacion'      => ['🧬', 'Biodecodificación'],
        'libreria_cristiana'     => ['📚', 'Librería cristiana'],
    ],
    'Salud' => [
        'farmacia'        => ['💊', 'Farmacia'],
        'hospital'        => ['🏥', 'Clínica / Hospital'],
        'odontologia'     => ['🦷', 'Odontología'],
        'psicologo'       => ['🧠', 'Psicología'],
        'psicopedagogo'   => ['📚', 'Psicopedagogía'],
        'fonoaudiologo'   => ['🗣️', 'Fonoaudiología'],
        'grafologo'       => ['✍️', 'Grafología'],
        'veterinaria'     => ['🐾', 'Veterinaria'],
        'optica'          => ['👓', 'Óptica'],
    ],
    'Belleza & Bienestar' => [
        'salon_belleza' => ['💇', 'Peluquería / Salón'],
        'barberia'      => ['💈', 'Barbería'],
        'spa'           => ['💆', 'Spa / Estética'],
        'gimnasio'      => ['💪', 'Gimnasio'],
        'danza'         => ['💃', 'Danza / Ballet'],
    ],
    'Servicios' => [
        'banco'          => ['🏦', 'Banco / Financiera'],
        'inmobiliaria'   => ['🏠', 'Inmobiliaria'],
        'seguros'        => ['🛡️', 'Seguros'],
        'abogado'        => ['⚖️', 'Estudio Jurídico'],
        'contador'       => ['📊', 'Contaduría'],
        'arquitectura'   => ['📐', 'Arquitectura'],
        'ingenieria'     => ['⚙️', 'Ingeniería'],
        'taller'         => ['🔩', 'Taller Mecánico'],
        'herreria'       => ['🔨', 'Herrería'],
        'carpinteria'    => ['🪵', 'Carpintería'],
        'modista'        => ['🧵', 'Modista / Costura'],
        'construccion'   => ['🏗️', 'Construcción'],
        'centro_vecinal' => ['🏘️', 'Centro Vecinal / ONG'],
        'remate'         => ['🔨', 'Remates / Subastas'],
    ],
    'Educación & Turismo' => [
        'academia'  => ['🎓', 'Academia / Instituto'],
        'idiomas'   => ['🌐', 'Instituto de Idiomas'],
        'escuela'   => ['🏫', 'Escuela / Jardín'],
        'hotel'     => ['🏨', 'Hotel / Alojamiento'],
        'turismo'   => ['✈️', 'Turismo / Agencia'],
        'cine'      => ['🎬', 'Cine / Teatro / Arte'],
    ],
    'Otros' => [
        'otros' => ['📍', 'Otro tipo'],
    ],
];

$subtypeLabels = [
    'restaurante'    => 'Especialidad culinaria (ej: italiana, parrilla, vegana…)',
    'cafeteria'      => 'Tipo de cafetería (ej: specialty coffee, artesanal…)',
    'bar'            => 'Tipo de bar (ej: cocktails, cervecería, karaoke…)',
    'hotel'          => 'Categoría / estrellas (ej: boutique, hostel, 4★…)',
    'comercio'       => 'Rubro principal (ej: ropa, electrodomésticos, juguetes…)',
    'productora_audiovisual' => 'Especialidad (ej: cine, TV, spots, reels, streaming…)',
    'escuela_musicos' => 'Instrumentos o enfoque (ej: guitarra, piano, canto, producción musical…)',
    'taller_artes'   => 'Disciplina artística (ej: pintura, cerámica, dibujo, escultura…)',
    'biodecodificacion' => 'Área de trabajo (ej: sesiones individuales, talleres, formación…)',
    'libreria_cristiana' => 'Línea editorial (ej: biblias, devocionales, música, regalos…)',
    'supermercado'   => 'Tamaño / cadena (ej: mini market, mayorista…)',
    'farmacia'       => 'Tipo (ej: magistral, turno permanente…)',
    'hospital'       => 'Especialidad (ej: pediatría, odontología, clínica general…)',
    'gimnasio'       => 'Disciplinas (ej: crossfit, yoga, artes marciales…)',
    'academia'       => 'Área de enseñanza (ej: música, informática, teatro…)',
    'idiomas'        => 'Idiomas que se enseñan (ej: inglés, francés, portugués…)',
    'turismo'        => 'Servicios (ej: excursiones, paquetes, receptivo…)',
    'taller'         => 'Especialidad (ej: chapa y pintura, electromecánica…)',
    'arquitectura'   => 'Especialidad (ej: diseño interior, urbanismo, paisajismo…)',
    'ingenieria'     => 'Rama (ej: civil, electrónica, industrial, agronómica…)',
    'carpinteria'    => 'Tipo (ej: muebles a medida, aberturas, obra…)',
    'modista'        => 'Especialidad (ej: alta costura, ajustes, uniformes…)',
    'danza'          => 'Estilo (ej: tango, ballet, folklore, salsa, contemporánea…)',
    'psicologo'      => 'Enfoque (ej: cognitivo, gestalt, sistémico, infanto-juvenil…)',
    'psicopedagogo'  => 'Área (ej: dificultades de aprendizaje, orientación vocacional…)',
    'fonoaudiologo'  => 'Especialidad (ej: lenguaje, voz, deglución, audiología…)',
    'grafologo'      => 'Área (ej: pericial, orientación, grafoterapia…)',
    'verduleria'     => 'Tipo (ej: frutería, orgánicos, agroecológico…)',
    'carniceria'     => 'Tipo (ej: vacuna, porcina, pollos, embutidos…)',
    'pastas'         => 'Variedades (ej: frescas, rellenas, caseras, sin TACC…)',
    'centro_vecinal' => 'Tipo de organización (ej: sociedad de fomento, club de barrio…)',
    'autos_venta'    => 'Tipo de vehículo (ej: sedán, SUV, utilitario, 0km, usado…)',
    'motos_venta'    => 'Tipo de moto (ej: urbana, enduro, scooter, cilindrada…)',
    'remate'         => 'Tipo de subasta (ej: judicial, ganadera, online, loteo…)',
];

$diasSemana = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo $editing ? 'Editar Negocio' : 'Publicar Negocio'; ?> — Mapita</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="/css/variables-luxury.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        body {
            margin: 0;
            background: #f0f2f7;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: #1a202c;
        }

        /* ── HEADER ─────────────────────────────────────── */
        .page-header {
            background: linear-gradient(135deg, #1B3B6F 0%, #0d2247 100%);
            color: white;
            padding: 18px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0,0,0,.25);
            position: sticky;
            top: 0;
            z-index: 1000; /* por encima de Leaflet (máx 700) */
        }
        .page-header h1 { margin: 0; font-size: 1.25em; font-weight: 700; }
        .page-header .sub { font-size: .8em; opacity: .7; margin-top: 2px; }
        .page-header nav a {
            color: rgba(255,255,255,.8);
            text-decoration: none;
            font-size: .85em;
            margin-left: 16px;
            transition: color .2s;
        }
        .page-header nav a:hover { color: white; }

        /* ── LAYOUT ──────────────────────────────────────── */
        .main { max-width: 860px; margin: 32px auto; padding: 0 16px 60px; }

        /* ── SECTION CARDS ───────────────────────────────── */
        .form-section {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,.06);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .section-head {
            padding: 18px 24px 14px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-icon { font-size: 1.4em; }
        .section-title { font-size: 1em; font-weight: 700; color: #1a202c; margin: 0; }
        .section-desc  { font-size: .8em; color: #6b7280; margin: 2px 0 0; }
        .section-body  { padding: 20px 24px; }

        /* ── BUSINESS TYPE GRID ──────────────────────────── */
        .type-category-label {
            font-size: .72em;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #9ca3af;
            margin: 14px 0 6px;
        }
        .type-category-label:first-child { margin-top: 0; }
        .type-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 8px;
        }
        .type-card { position: relative; cursor: pointer; }
        .type-card input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
        .type-card label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            padding: 10px 6px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            cursor: pointer;
            font-size: .78em;
            font-weight: 600;
            color: #4b5563;
            text-align: center;
            background: #fafafa;
            min-height: 72px;
            user-select: none;
            transition: all .18s;
        }
        .type-card label .t-emoji { font-size: 1.6em; }
        .type-card input:checked + label {
            border-color: #1B3B6F;
            background: #eef2ff;
            color: #1B3B6F;
            box-shadow: 0 0 0 3px rgba(27,59,111,.12);
        }
        .type-card label:hover { border-color: #a5b4fc; background: #f5f7ff; }

        /* ── FORM FIELDS ─────────────────────────────────── */
        .field-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .field-grid .full { grid-column: 1 / -1; }

        .field { display: flex; flex-direction: column; gap: 6px; }
        .field label { font-size: .82em; font-weight: 700; color: #374151; }
        .field label .req { color: #e74c3c; margin-left: 2px; }
        .field label .hint { font-weight: 400; color: #9ca3af; font-size: .9em; }
        .metadata-hint { display:block; margin-top:6px; color:#6b7280; font-size:.78em; }
        .field input, .field select, .field textarea {
            padding: 10px 12px;
            border: 1.5px solid #d1d5db;
            border-radius: 8px;
            font-size: .9em;
            font-family: inherit;
            color: #1a202c;
            background: white;
            transition: border-color .2s, box-shadow .2s;
            outline: none;
        }
        .field input:focus, .field select:focus, .field textarea:focus {
            border-color: #1B3B6F;
            box-shadow: 0 0 0 3px rgba(27,59,111,.1);
        }
        .field textarea { resize: vertical; min-height: 90px; }
        .field input[readonly] { background: #f9fafb; color: #6b7280; cursor: default; }

        .char-count { font-size: .72em; color: #9ca3af; text-align: right; margin-top: -4px; }

        /* ── SOCIAL MEDIA INPUTS ─────────────────────────── */
        .social-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .social-field { position: relative; }
        .social-field .prefix {
            position: absolute; left: 10px; top: 50%;
            transform: translateY(-50%);
            font-size: .9em; color: #9ca3af; pointer-events: none;
        }
        .social-field input { padding-left: 26px; }

        /* ── CHECKBOX PILLS ──────────────────────────────── */
        .pills-grid { display: flex; flex-wrap: wrap; gap: 10px; }
        .pill { position: relative; }
        .pill input[type="checkbox"] { position: absolute; opacity: 0; width: 0; height: 0; }
        .pill label {
            display: flex; align-items: center; gap: 6px;
            padding: 8px 14px;
            border: 1.5px solid #d1d5db;
            border-radius: 24px;
            cursor: pointer;
            font-size: .84em; font-weight: 600; color: #4b5563;
            background: #fafafa;
            transition: all .18s;
            user-select: none;
        }
        .pill input:checked + label { border-color: #1B3B6F; background: #eef2ff; color: #1B3B6F; }
        .pill label:hover { border-color: #a5b4fc; background: #f5f7ff; }

        /* ── DAYS CHECKBOXES ─────────────────────────────── */
        .days-grid { display: flex; flex-wrap: wrap; gap: 8px; }
        .day-pill { position: relative; }
        .day-pill input[type="checkbox"] { position: absolute; opacity: 0; width: 0; height: 0; }
        .day-pill label {
            display: block; padding: 6px 12px;
            border: 1.5px solid #d1d5db; border-radius: 20px;
            cursor: pointer; font-size: .82em; font-weight: 600;
            color: #6b7280; background: #fafafa;
            transition: all .18s; user-select: none;
        }
        .day-pill input:checked + label { border-color: #e74c3c; background: #fff5f5; color: #c0392b; }

        /* ── MAP ─────────────────────────────────────────── */
        /* isolation:isolate crea un stacking context propio,
           evitando que los z-index internos de Leaflet
           compitan con el header sticky de la página       */
        #map-container { height: 340px; border-radius: 10px; overflow: hidden; border: 2px solid #e5e7eb; margin-bottom: 12px; isolation: isolate; }
        #map { width: 100%; height: 100%; }
        .map-tip { font-size: .8em; color: #6b7280; display: flex; align-items: center; gap: 6px; margin-bottom: 12px; }
        .coords-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

        /* ── TAGS ────────────────────────────────────────── */
        .tags-input-wrap {
            border: 1.5px solid #d1d5db; border-radius: 8px;
            padding: 8px; display: flex; flex-wrap: wrap; gap: 6px;
            min-height: 48px; cursor: text;
            transition: border-color .2s;
        }
        .tags-input-wrap:focus-within { border-color: #1B3B6F; box-shadow: 0 0 0 3px rgba(27,59,111,.1); }
        .tag-chip {
            display: flex; align-items: center; gap: 4px;
            background: #eef2ff; color: #1B3B6F;
            border-radius: 14px; padding: 3px 10px;
            font-size: .8em; font-weight: 600;
        }
        .tag-chip .del { cursor: pointer; font-size: 1em; line-height: 1; color: #9ca3af; }
        .tag-chip .del:hover { color: #e74c3c; }
        .tags-input-wrap input {
            border: none; outline: none; font-size: .86em;
            min-width: 140px; flex: 1; padding: 2px 4px;
            color: #1a202c; background: transparent;
        }
        .tag-suggestions { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
        .tag-sug {
            padding: 4px 10px; background: #f3f4f6;
            border: 1px solid #e5e7eb; border-radius: 14px;
            font-size: .78em; cursor: pointer; color: #4b5563; font-weight: 600;
            transition: all .15s;
        }
        .tag-sug:hover { background: #eef2ff; border-color: #c7d2fe; color: #1B3B6F; }

        /* ── GALLERY ─────────────────────────────────────── */
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; }
        .gallery-item {
            position: relative; border-radius: 10px; overflow: hidden;
            aspect-ratio: 4/3; background: #f3f4f6;
            border: 2px solid #e5e7eb;
        }
        .gallery-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .gallery-item .del-btn {
            position: absolute; top: 6px; right: 6px;
            background: rgba(220,38,38,.85); color: white;
            border: none; border-radius: 50%; width: 26px; height: 26px;
            font-size: 14px; cursor: pointer; display: flex;
            align-items: center; justify-content: center;
            opacity: 0; transition: opacity .2s;
        }
        .gallery-item:hover .del-btn { opacity: 1; }
        .gallery-dropzone {
            border: 2px dashed #c7d2fe; border-radius: 10px;
            padding: 24px 16px; text-align: center;
            cursor: pointer; transition: all .2s; background: white;
            aspect-ratio: 4/3; display: flex; flex-direction: column;
            align-items: center; justify-content: center; gap: 6px;
        }
        .gallery-dropzone:hover, .gallery-dropzone.drag-over {
            border-color: #1B3B6F; background: #f0f4ff;
        }
        .gallery-count-badge {
            display: inline-block; padding: 3px 10px;
            background: #f3f4f6; border-radius: 20px;
            font-size: .75em; font-weight: 700; color: #6b7280;
        }

        /* ── OG PHOTO ────────────────────────────────────── */
        .og-preview-wrap {
            position: relative; width: 100%; max-width: 420px;
            border-radius: 10px; overflow: hidden;
            border: 2px solid #e5e7eb; background: #1B3B6F;
        }
        .og-preview-wrap img { width: 100%; display: block; aspect-ratio: 1200/630; object-fit: cover; }
        .og-badge {
            position: absolute; top: 8px; right: 8px;
            font-size: .7em; font-weight: 700; padding: 3px 8px; border-radius: 10px;
        }
        .og-dropzone {
            border: 2px dashed #c7d2fe; border-radius: 10px; padding: 28px 20px;
            text-align: center; cursor: pointer; transition: all .2s; background: white;
        }
        .og-dropzone:hover, .og-dropzone.drag-over { border-color: #1B3B6F; background: #f0f4ff; }

        /* ── SUBMIT AREA ─────────────────────────────────── */
        .submit-bar {
            display: flex; gap: 12px; justify-content: flex-end;
            padding: 20px 24px; background: white;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,.06);
        }
        .btn-cancel {
            padding: 12px 24px; border: 1.5px solid #d1d5db; border-radius: 8px;
            background: white; color: #374151; font-weight: 600; font-size: .9em;
            cursor: pointer; text-decoration: none;
            display: inline-flex; align-items: center; gap: 6px; transition: all .2s;
        }
        .btn-cancel:hover { border-color: #9ca3af; background: #f9fafb; }
        .btn-submit {
            padding: 12px 32px;
            background: linear-gradient(135deg, #1B3B6F 0%, #0d2247 100%);
            color: white; border: none; border-radius: 8px;
            font-weight: 700; font-size: .95em; cursor: pointer;
            display: inline-flex; align-items: center; gap: 8px;
            transition: all .2s; box-shadow: 0 4px 14px rgba(27,59,111,.3);
        }
        .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(27,59,111,.4); }
        .btn-back {
            padding: 12px 24px; border: 1.5px solid #d1d5db; border-radius: 8px;
            background: white; color: #374151; font-weight: 600; font-size: .9em;
            cursor: pointer; text-decoration: none;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-back:hover { background: #f9fafb; }

        /* ── MESSAGE ─────────────────────────────────────── */
        .message { padding: 14px 20px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; font-size: .9em; }
        .msg-success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
        .msg-error   { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .msg-info    { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }
        .deleg-list { display: grid; gap: 10px; }
        .deleg-item { border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 12px; display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; }
        .deleg-item strong { color: #111827; font-size: .92em; }
        .deleg-item small { color: #6b7280; }
        .deleg-empty { color: #6b7280; font-size: .88em; }
        .deleg-btn { border: 1px solid #fca5a5; background: #fff; color: #b91c1c; border-radius: 8px; padding: 7px 10px; font-size: .8em; font-weight: 700; cursor: pointer; }
        .deleg-btn:hover { background: #fff1f2; }

        #subtype-section { transition: all .3s; }
        .divider { height: 1px; background: #f0f0f0; margin: 18px 0; }

        @media (max-width: 600px) {
            .field-grid { grid-template-columns: 1fr; }
            .social-row { grid-template-columns: 1fr; }
            .coords-row { grid-template-columns: 1fr; }
            .type-grid  { grid-template-columns: repeat(3, 1fr); }
            .page-header { flex-direction: column; gap: 8px; text-align: center; }
            .submit-bar { flex-direction: column; }
        }
    </style>
</head>
<body>

<div class="page-header">
    <div>
        <h1><?php echo $editing ? '✏️ Editar Negocio' : '🏪 Publicar mi Negocio'; ?></h1>
        <div class="sub"><?php echo $editing ? htmlspecialchars($business['name']) . ' · ID #' . $businessId : 'Completá el perfil de tu negocio y aparecé en el mapa'; ?></div>
    </div>
    <nav>
        <a href="/">🗺️ Mapa</a>
        <a href="/mis-negocios">📋 Mis negocios</a>
        <?php if ($editing): ?>
        <a href="/view?id=<?php echo $businessId; ?>">👁️ Ver perfil</a>
        <?php endif; ?>
    </nav>
</div>

<div class="main">

    <?php if (isset($_GET['nuevo']) && $_GET['nuevo'] == 1): ?>
        <div class="message msg-info">🎉 ¡Negocio publicado! Ahora podés agregar fotos y completar los detalles.</div>
    <?php endif; ?>

    <?php if ($message): ?>
        <div class="message <?php echo $messageType === 'success' ? 'msg-success' : 'msg-error'; ?>">
            <?php echo $messageType === 'success' ? '✅ ' : '❌ '; echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form method="post" id="main-form">
        <?php echo csrfField(); ?>

        <!-- ══ TIPO DE NEGOCIO ══════════════════════════════════════════════ -->
        <div class="form-section">
            <div class="section-head">
                <span class="section-icon">🏢</span>
                <div>
                    <div class="section-title">Tipo de negocio <span style="color:#e74c3c">*</span></div>
                    <div class="section-desc">Seleccioná la categoría que mejor describe tu negocio</div>
                </div>
            </div>
            <div class="section-body">
                <?php foreach ($tipos as $categoria => $opciones): ?>
                    <div class="type-category-label"><?php echo $categoria; ?></div>
                    <div class="type-grid">
                        <?php foreach ($opciones as $val => [$emoji, $nombre]): ?>
                        <div class="type-card">
                            <input type="radio" name="business_type" id="bt_<?php echo $val; ?>"
                                   value="<?php echo $val; ?>" required
                                   <?php echo $selectedType === $val ? 'checked' : ''; ?>
                                   onchange="onTypeChange('<?php echo $val; ?>')">
                            <label for="bt_<?php echo $val; ?>">
                                <span class="t-emoji"><?php echo $emoji; ?></span>
                                <?php echo $nombre; ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

                <div id="subtype-section" style="margin-top:16px; <?php echo $selectedType ? '' : 'display:none;'; ?>">
                    <div class="field">
                        <label for="tipo_comercio">
                            Especialidad / Sub-categoría
                            <span class="hint" id="subtype-hint">(opcional)</span>
                        </label>
                        <input type="text" id="tipo_comercio" name="tipo_comercio"
                               placeholder="Especificá el rubro o especialidad…" maxlength="100"
                               value="<?php echo htmlspecialchars($comercioData['tipo_comercio'] ?? ''); ?>">
                        <small class="metadata-hint">Este dato se muestra como metadato destacado en el selector del mapa.</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ DATOS BÁSICOS ════════════════════════════════════════════════ -->
        <div class="form-section">
            <div class="section-head">
                <span class="section-icon">📝</span>
                <div>
                    <div class="section-title">Datos del negocio</div>
                    <div class="section-desc">Información principal visible en el mapa</div>
                </div>
            </div>
            <div class="section-body">
                <div class="field-grid">
                    <div class="field full">
                        <label for="name">Nombre del negocio <span class="req">*</span></label>
                        <input type="text" id="name" name="name" required maxlength="120"
                               placeholder="Nombre visible en el mapa"
                               value="<?php echo bv($business, 'name'); ?>"
                               oninput="updateCount(this,'count-name',120)">
                        <div class="char-count"><span id="count-name"><?php echo mb_strlen($business['name'] ?? ''); ?></span>/120</div>
                    </div>

                    <div class="field">
                        <label for="mapita_id">Mapita ID <span class="hint">— identificador opcional</span></label>
                        <input type="text" id="mapita_id" name="mapita_id" maxlength="64"
                               placeholder="ej: NEG-001"
                               value="<?php echo bv($business, 'mapita_id'); ?>">
                    </div>

                    <div class="field full">
                        <label for="address">Dirección <span class="req">*</span></label>
                        <input type="text" id="address" name="address" required maxlength="200"
                               placeholder="Calle, número, localidad…"
                               value="<?php echo bv($business, 'address'); ?>">
                    </div>

                    <div class="field full">
                        <label for="description">Descripción / Propuesta de valor
                            <span class="hint">— ¿Por qué elegirían tu negocio?</span>
                        </label>
                        <textarea id="description" name="description" maxlength="1200"
                                  placeholder="Ej: Somos una panadería artesanal con más de 20 años de historia…"
                                  oninput="updateCount(this,'count-desc',1200)"><?php echo htmlspecialchars($business['description'] ?? ''); ?></textarea>
                        <div class="char-count"><span id="count-desc"><?php echo mb_strlen($business['description'] ?? ''); ?></span>/1200</div>
                    </div>

                    <div class="field">
                        <label for="phone">Teléfono / WhatsApp</label>
                        <input type="tel" id="phone" name="phone" maxlength="30"
                               placeholder="+54 11 1234-5678"
                               value="<?php echo bv($business, 'phone'); ?>">
                    </div>

                    <div class="field">
                        <label for="email">Correo electrónico</label>
                        <input type="email" id="email" name="email" maxlength="120"
                               placeholder="contacto@negocio.com"
                               value="<?php echo bv($business, 'email'); ?>">
                    </div>

                    <div class="field full">
                        <label for="website">Sitio web</label>
                        <input type="url" id="website" name="website" maxlength="300"
                               placeholder="https://www.minegocio.com"
                               value="<?php echo bv($business, 'website'); ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ HORARIOS ════════════════════════════════════════════════════ -->
        <div class="form-section">
            <div class="section-head">
                <span class="section-icon">🕐</span>
                <div>
                    <div class="section-title">Horarios de atención</div>
                    <div class="section-desc">Los usuarios verán cuándo estás abierto</div>
                </div>
            </div>
            <div class="section-body">
                <div class="field-grid">
                    <div class="field">
                        <label for="horario_apertura">Apertura</label>
                        <input type="time" id="horario_apertura" name="horario_apertura"
                               value="<?php echo htmlspecialchars(substr($comercioData['horario_apertura'] ?? '09:00', 0, 5)); ?>">
                    </div>
                    <div class="field">
                        <label for="horario_cierre">Cierre</label>
                        <input type="time" id="horario_cierre" name="horario_cierre"
                               value="<?php echo htmlspecialchars(substr($comercioData['horario_cierre'] ?? '18:00', 0, 5)); ?>">
                    </div>
                </div>
                <div class="divider"></div>
                <div class="field">
                    <label>Días que NO abrís <span class="hint">— seleccioná los días de descanso</span></label>
                    <div class="days-grid">
                        <?php foreach ($diasSemana as $dia): ?>
                        <div class="day-pill">
                            <input type="checkbox" id="day_<?php echo $dia; ?>"
                                   name="dias_cierre[]" value="<?php echo $dia; ?>"
                                   <?php echo in_array($dia, $currentDias) ? 'checked' : ''; ?>>
                            <label for="day_<?php echo $dia; ?>"><?php echo $dia; ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ SERVICIOS Y CARACTERÍSTICAS ═════════════════════════════════ -->
        <div class="form-section">
            <div class="section-head">
                <span class="section-icon">⭐</span>
                <div>
                    <div class="section-title">Servicios y características</div>
                    <div class="section-desc">Marcá todo lo que ofrece tu negocio</div>
                </div>
            </div>
            <div class="section-body">
                <div class="pills-grid">
                    <div class="pill">
                        <input type="checkbox" name="has_delivery" id="f_delivery" value="1"
                               <?php echo !empty($business['has_delivery']) ? 'checked' : ''; ?>>
                        <label for="f_delivery">🚚 Delivery / Envío</label>
                    </div>
                    <div class="pill">
                        <input type="checkbox" name="has_card_payment" id="f_card" value="1"
                               <?php echo !empty($business['has_card_payment']) ? 'checked' : ''; ?>>
                        <label for="f_card">💳 Pago con tarjeta</label>
                    </div>
                    <div class="pill">
                        <input type="checkbox" name="is_franchise" id="f_franchise" value="1"
                               <?php echo !empty($business['is_franchise']) ? 'checked' : ''; ?>>
                        <label for="f_franchise">🔗 Franquicia / Cadena</label>
                    </div>
                    <?php
                    // Parsear flags de servicios desde certifications
                    $certStr = $business['certifications'] ?? '';
                    $svcMap  = [
                        'svc_wifi'      => 'WiFi',
                        'svc_parking'   => 'Estacionamiento',
                        'svc_accessible'=> 'Acceso universal',
                        'svc_reservas'  => 'Reservas online',
                        'svc_facturas'  => 'Factura fiscal',
                        'svc_pickup'    => 'Retiro en local',
                        'svc_mp'        => 'Mercado Pago',
                    ];
                    $svcChecked = [];
                    foreach ($svcMap as $key => $label) {
                        $svcChecked[$key] = str_contains($certStr, $label);
                    }
                    $svcLabels = [
                        'svc_wifi'       => '📶 WiFi gratuito',
                        'svc_parking'    => '🅿️ Estacionamiento',
                        'svc_accessible' => '♿ Acceso universal',
                        'svc_reservas'   => '📅 Reservas online',
                        'svc_facturas'   => '🧾 Factura fiscal',
                        'svc_pickup'     => '🏃 Retiro en local',
                        'svc_mp'         => '💙 Mercado Pago',
                    ];
                    foreach ($svcLabels as $key => $label):
                        $inputId = 'f_' . str_replace('svc_', '', $key);
                    ?>
                    <div class="pill">
                        <input type="checkbox" name="<?php echo $key; ?>" id="<?php echo $inputId; ?>" value="1"
                               <?php echo ($svcChecked[$key] ?? false) ? 'checked' : ''; ?>>
                        <label for="<?php echo $inputId; ?>"><?php echo $label; ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="divider"></div>
                <div class="field">
                    <label for="certifications">Premios, certificaciones o reconocimientos <span class="hint">— opcional</span></label>
                    <?php
                    // Limpiar flags de servicios del valor de certifications para mostrar solo el texto real
                    $certDisplay = preg_replace('/\s*\|\s*(WiFi|Estacionamiento|Acceso universal|Reservas online|Factura fiscal|Retiro en local|Mercado Pago)(,\s*[^|]*)?/', '', $certStr);
                    $certDisplay = trim(preg_replace('/^\s*\|\s*/', '', $certDisplay));
                    ?>
                    <input type="text" id="certifications" name="certifications" maxlength="400"
                           placeholder="Ej: ISO 9001, Premio Mejor Emprendimiento 2024…"
                           value="<?php echo htmlspecialchars($certDisplay); ?>">
                </div>
            </div>
        </div>

        <!-- ══ CATEGORÍAS / PRODUCTOS ═══════════════════════════════════════ -->
        <div class="form-section">
            <div class="section-head">
                <span class="section-icon">🏷️</span>
                <div>
                    <div class="section-title">Productos y servicios que ofrecés</div>
                    <div class="section-desc">Etiquetas para que los usuarios encuentren tu negocio</div>
                </div>
            </div>
            <div class="section-body">
                <div class="tags-input-wrap" id="tags-wrap" onclick="document.getElementById('tags-raw').focus()">
                    <input type="text" id="tags-raw" placeholder="Escribí y presioná Enter o coma…"
                           onkeydown="handleTagKey(event)" oninput="filterSuggestions(this.value)">
                </div>
                <input type="hidden" name="categorias_productos" id="tags-hidden">
                <div class="tag-suggestions" id="tag-suggestions"></div>
                <p style="font-size:.75em;color:#9ca3af;margin:8px 0 0;">
                    Sugerencias: <span id="suggestions-base"></span>
                </p>
            </div>
        </div>

        <!-- ══ REDES SOCIALES ════════════════════════════════════════════════ -->
        <div class="form-section">
            <div class="section-head">
                <span class="section-icon">📱</span>
                <div>
                    <div class="section-title">Redes sociales</div>
                    <div class="section-desc">Conectá tu perfil para que los clientes te sigan</div>
                </div>
            </div>
            <div class="section-body">
                <div class="social-row">
                    <div class="field">
                        <label for="instagram">📸 Instagram</label>
                        <div class="social-field">
                            <span class="prefix">@</span>
                            <input type="text" id="instagram" name="instagram" maxlength="60"
                                   placeholder="usuario"
                                   value="<?php echo bv($business, 'instagram'); ?>">
                        </div>
                    </div>
                    <div class="field">
                        <label for="facebook">👥 Facebook</label>
                        <div class="social-field">
                            <span class="prefix">@</span>
                            <input type="text" id="facebook" name="facebook" maxlength="60"
                                   placeholder="pagina"
                                   value="<?php echo bv($business, 'facebook'); ?>">
                        </div>
                    </div>
                    <div class="field">
                        <label for="tiktok">🎵 TikTok</label>
                        <div class="social-field">
                            <span class="prefix">@</span>
                            <input type="text" id="tiktok" name="tiktok" maxlength="60"
                                   placeholder="usuario"
                                   value="<?php echo bv($business, 'tiktok'); ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ UBICACIÓN EN EL MAPA ══════════════════════════════════════════ -->
        <div class="form-section">
            <div class="section-head">
                <span class="section-icon">📍</span>
                <div>
                    <div class="section-title">Ubicación en el mapa <span style="color:#e74c3c">*</span></div>
                    <div class="section-desc">Hacé clic en el mapa para fijar la ubicación exacta</div>
                </div>
            </div>
            <div class="section-body">
                <div class="map-tip">
                    <span>💡</span> Podés hacer zoom y mover el mapa antes de hacer clic. El marcador se puede reubicar.
                </div>
                <div id="map-container"><div id="map"></div></div>
                <div class="coords-row">
                    <div class="field">
                        <label for="lat">Latitud <span class="req">*</span></label>
                        <input type="number" id="lat" name="lat" step="any" required readonly
                               placeholder="Click en el mapa"
                               value="<?php echo htmlspecialchars($business['lat'] ?? ''); ?>">
                    </div>
                    <div class="field">
                        <label for="lng">Longitud <span class="req">*</span></label>
                        <input type="number" id="lng" name="lng" step="any" required readonly
                               placeholder="Click en el mapa"
                               value="<?php echo htmlspecialchars($business['lng'] ?? ''); ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ BOTONES PRINCIPALES ══════════════════════════════════════════ -->
        <div class="submit-bar">
            <?php if ($editing): ?>
                <a href="/mis-negocios" class="btn-cancel">← Mis negocios</a>
            <?php else: ?>
                <a href="/" class="btn-cancel">← Cancelar</a>
                <button type="reset" class="btn-cancel" onclick="return confirm('¿Limpiar todos los datos?')">🗑️ Limpiar</button>
            <?php endif; ?>
            <button type="submit" class="btn-submit">
                <?php echo $editing ? '💾 Guardar cambios' : '🚀 Publicar negocio'; ?>
            </button>
        </div>

    </form>

    <!-- ══ SECCIONES SOLO EN MODO EDICIÓN ════════════════════════════════════ -->
    <?php if ($editing): ?>

    <!-- ── GALERÍA DE FOTOS ──────────────────────────────────────────────── -->
    <div class="form-section" style="margin-top:24px;">
        <div class="section-head">
            <span class="section-icon">🖼️</span>
            <div>
                <div class="section-title">
                    Galería de fotos
                    <span class="gallery-count-badge" id="gallery-badge"><?php echo count($galleryPhotos); ?>/3</span>
                </div>
                <div class="section-desc">Hasta 3 fotos · máx. 300 KB cada una · JPG, PNG o WebP</div>
            </div>
        </div>
        <div class="section-body">
            <?php if ($businessId > 0): ?>
            <div class="gallery-grid" id="gallery-grid">
                <?php foreach ($galleryPhotos as $gp): ?>
                <div class="gallery-item" id="gi_<?php echo htmlspecialchars($gp['filename']); ?>">
                    <img src="<?php echo htmlspecialchars($gp['url']); ?>" alt="Foto">
                    <button type="button" class="del-btn" onclick="deleteGalleryPhoto('<?php echo htmlspecialchars($gp['filename']); ?>')" title="Eliminar foto">×</button>
                </div>
                <?php endforeach; ?>

                <?php if (count($galleryPhotos) < 3): ?>
                <div class="gallery-dropzone" id="gallery-drop"
                     onclick="document.getElementById('gallery-input').click()"
                     ondragover="event.preventDefault();this.classList.add('drag-over')"
                     ondragleave="this.classList.remove('drag-over')"
                     ondrop="handleGalleryDrop(event)">
                    <span style="font-size:2em;">📷</span>
                    <span style="font-size:.8em;font-weight:600;color:#4b5563;">Agregar foto</span>
                    <span style="font-size:.72em;color:#9ca3af;">Arrastrá o hacé clic</span>
                </div>
                <?php endif; ?>
            </div>
            <input type="file" id="gallery-input" accept="image/jpeg,image/png,image/webp"
                   style="display:none;" onchange="uploadGalleryPhoto(this.files[0])">
            <div id="gallery-msg" style="display:none;margin-top:12px;padding:10px 16px;border-radius:8px;font-size:.85em;font-weight:600;"></div>
            <?php else: ?>
            <p style="color:#9ca3af;font-size:.85em;margin:0;">Guardá el negocio primero para poder subir fotos de galería.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── FOTO PARA COMPARTIR EN REDES ─────────────────────────────────── -->
    <div class="form-section" style="margin-top:8px;">
        <div class="section-head" style="background:linear-gradient(135deg,#1B3B6F,#0d2247);">
            <span class="section-icon">📲</span>
            <div>
                <div class="section-title" style="color:white;">Foto para compartir en redes</div>
                <div class="section-desc" style="color:rgba(255,255,255,.65);">Aparece en WhatsApp, Facebook e Instagram al compartir el link del negocio</div>
            </div>
        </div>
        <div class="section-body" style="background:#f8faff;">
            <?php if ($businessId <= 0): ?>
            <p style="color:#9ca3af;font-size:.85em;margin:0;">Guardá el negocio primero para poder subir la foto de redes.</p>
            <?php else: ?>
            <div style="margin-bottom:20px;">
                <div style="font-size:.75em;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;margin-bottom:10px;">Vista previa actual</div>
                <div class="og-preview-wrap">
                    <img id="og-preview-img"
                         src="<?php echo $ogCoverUrl ?? ('/api/og_image.php?type=business&id=' . $businessId . '&t=' . time()); ?>"
                         alt="Preview OG">
                    <div class="og-badge" style="background:<?php echo $ogCoverUrl ? '#27ae60' : '#e67e22'; ?>; color:white;">
                        <?php echo $ogCoverUrl ? '✓ Foto personalizada' : 'Generada automáticamente'; ?>
                    </div>
                </div>
                <p style="font-size:.75em;color:#9ca3af;margin:6px 0 0;">Tamaño recomendado: <strong>1200 × 630 px</strong> · Formatos: JPG, PNG, WebP · <strong>Máx. 200 KB</strong></p>
            </div>

            <div class="og-dropzone" id="og-dropzone"
                 onclick="document.getElementById('og-file-input').click()"
                 ondragover="event.preventDefault();this.classList.add('drag-over')"
                 ondragleave="this.classList.remove('drag-over')"
                 ondrop="handleOgDrop(event)">
                <div style="font-size:2.2em;margin-bottom:8px;">🖼️</div>
                <div style="font-weight:700;color:#374151;margin-bottom:4px;">Subir foto para redes sociales</div>
                <div style="font-size:.82em;color:#9ca3af;">Arrastrá una imagen acá o hacé clic para seleccionar</div>
                <div style="font-size:.75em;color:#b0b8cc;margin-top:6px;">JPG · PNG · WebP · máx. 200 KB · ideal 1200×630 px</div>
                <div style="font-size:.72em;color:#d1d5db;margin-top:3px;">¿Imagen muy grande? Comprimila gratis en <strong>squoosh.app</strong></div>
                <input type="file" id="og-file-input" accept="image/jpeg,image/png,image/webp"
                       style="display:none;" onchange="uploadOgPhoto(this.files[0])">
            </div>

            <?php if ($ogCoverUrl): ?>
            <div style="margin-top:12px;text-align:right;">
                <button onclick="deleteOgPhoto()" type="button"
                        style="padding:7px 16px;border:1.5px solid #e74c3c;background:white;color:#e74c3c;border-radius:8px;cursor:pointer;font-size:.82em;font-weight:600;">
                    🗑️ Quitar foto personalizada
                </button>
            </div>
            <?php endif; ?>

            <div id="og-msg" style="display:none;margin-top:12px;padding:10px 16px;border-radius:8px;font-size:.85em;font-weight:600;"></div>

            <!-- Simulación WhatsApp -->
            <div style="margin-top:24px;">
                <div style="font-size:.75em;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;margin-bottom:10px;">Simulación — cómo se ve en WhatsApp</div>
                <div style="max-width:300px;border-radius:10px;overflow:hidden;border:1px solid #e5e7eb;box-shadow:0 2px 8px rgba(0,0,0,.08);">
                    <img id="og-wa-thumb"
                         src="<?php echo $ogCoverUrl ?? ('/api/og_image.php?type=business&id=' . $businessId . '&t=' . time()); ?>"
                         style="width:100%;display:block;aspect-ratio:16/9;object-fit:cover;">
                    <div style="padding:10px 12px;background:white;">
                        <div style="font-weight:700;font-size:.85em;color:#111;margin-bottom:2px;">
                            <?php echo htmlspecialchars(mb_substr($business['name'], 0, 50)); ?>
                        </div>
                        <div style="font-size:.75em;color:#667eea;">mapita.com.ar</div>
                    </div>
                </div>
            </div>
            <?php endif; /* fin businessId > 0 para OG */ ?>
        </div>
    </div>

    <?php else: ?>
    <!-- ── Aviso foto (solo en modo alta) ──────────────────────────────── -->
    <div class="form-section" style="margin-top:8px;">
        <div class="section-head"><span class="section-icon">📲</span>
            <div><div class="section-title">Fotos del negocio</div>
                <div class="section-desc">Galería y foto para compartir en redes</div></div>
        </div>
        <div class="section-body" style="display:flex;align-items:flex-start;gap:16px;">
            <div style="font-size:2.5em;flex-shrink:0;">🖼️</div>
            <div>
                <p style="margin:0 0 8px;color:#374151;font-size:.9em;line-height:1.6;">
                    Podés subir hasta <strong>3 fotos de galería</strong> y una <strong>foto para compartir en redes</strong> (WhatsApp, Facebook, etc.).
                </p>
                <p style="margin:0;font-size:.82em;color:#6b7280;background:#f3f4f6;padding:10px 14px;border-radius:8px;border-left:3px solid #667eea;">
                    📌 Esta opción estará disponible en <strong>"Editar negocio"</strong> una vez que guardes el registro.
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($editing): ?>
    <div class="form-section">
        <div class="section-head">
            <span class="section-icon">👥</span>
            <div>
                <div class="section-title">Delegación</div>
                <div class="section-desc">Administradores delegados (nivel A/admin)</div>
            </div>
        </div>
        <div class="section-body">
            <p class="hint" style="margin:0 0 12px;">Ingresá username o email del destinatario y confirmá con tu password para delegar o revocar.</p>
            <div id="biz-deleg-msg" class="message msg-info" style="display:none;margin:0 0 12px;"></div>
            <form id="biz-deleg-form" onsubmit="event.preventDefault(); delegateBusinessAdmin();">
                <div class="field-grid">
                    <div class="field">
                        <label for="biz-delegate-query">Username o email del destinatario</label>
                        <input type="text" id="biz-delegate-query" maxlength="120" placeholder="ej: usuario o mail@dominio.com">
                    </div>
                    <div class="field">
                        <label for="biz-delegate-password">Tu password de confirmación</label>
                        <input type="password" id="biz-delegate-password" name="password" maxlength="255" autocomplete="current-password" placeholder="••••••••">
                    </div>
                </div>
                <div style="margin-top:12px;">
                    <button type="submit" class="btn-save" style="width:auto;padding:10px 18px;">Delegar</button>
                </div>
            </form>
            <div class="divider"></div>
            <div id="biz-deleg-empty" class="deleg-empty">Cargando delegados…</div>
            <div id="biz-deleg-list" class="deleg-list"></div>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /main -->

<script>
// ── Datos desde PHP ───────────────────────────────────────────────────────────
const EDITING        = <?php echo $editing ? 'true' : 'false'; ?>;
const BIZ_ID         = <?php echo $businessId ?: 'null'; ?>;
const INITIAL_LAT    = <?php echo $editing ? (float)($business['lat'] ?? -34.6037) : -34.6037; ?>;
const INITIAL_LNG    = <?php echo $editing ? (float)($business['lng'] ?? -58.3816) : -58.3816; ?>;
const GALLERY_COUNT  = <?php echo count($galleryPhotos); ?>;
const MAX_GALLERY    = 3;

// Tags iniciales (pre-cargados en edición)
let currentTags = <?php echo json_encode(array_values($currentTags)); ?>;

// ── Mapa ──────────────────────────────────────────────────────────────────────
var mapa   = L.map('map').setView([INITIAL_LAT, INITIAL_LNG], EDITING ? 15 : 13);
var mapPin = null;

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors', maxZoom: 19
}).addTo(mapa);

// Si está editando, mostrar el pin en la posición actual
if (EDITING && INITIAL_LAT !== -34.6037) {
    mapPin = L.marker([INITIAL_LAT, INITIAL_LNG], {
        icon: L.divIcon({
            html: '<div style="background:#1B3B6F;width:16px;height:16px;border-radius:50%;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,.4);"></div>',
            className: '', iconSize: [16,16], iconAnchor: [8,8]
        })
    }).addTo(mapa).bindTooltip('📍 Ubicación actual', {direction:'top', offset:[0,-10]});
}

mapa.on('click', function(e) {
    if (mapPin) mapa.removeLayer(mapPin);
    mapPin = L.marker(e.latlng, {
        icon: L.divIcon({
            html: '<div style="background:#1B3B6F;width:16px;height:16px;border-radius:50%;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,.4);"></div>',
            className: '', iconSize: [16,16], iconAnchor: [8,8]
        })
    }).addTo(mapa).bindTooltip('📍 Ubicación seleccionada', {direction:'top', offset:[0,-10]}).openTooltip();
    document.getElementById('lat').value = e.latlng.lat.toFixed(7);
    document.getElementById('lng').value = e.latlng.lng.toFixed(7);
});

// Geolocalización automática (solo al agregar)
if (!EDITING && navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(pos => {
        mapa.setView([pos.coords.latitude, pos.coords.longitude], 15);
    }, () => {});
}

// ── Business type handler ─────────────────────────────────────────────────────
const subtypeHints = <?php echo json_encode($subtypeLabels); ?>;

function onTypeChange(val) {
    const sec   = document.getElementById('subtype-section');
    const hint  = document.getElementById('subtype-hint');
    const input = document.getElementById('tipo_comercio');
    sec.style.display = 'block';
    if (subtypeHints[val]) {
        hint.textContent  = '— ' + subtypeHints[val];
        input.placeholder = subtypeHints[val];
    } else {
        hint.textContent  = '(opcional)';
        input.placeholder = 'Especificá el rubro o especialidad…';
    }
    updateTagSuggestions(val);
}

// Si hay tipo seleccionado al cargar, actualizar sugerencias
<?php if ($selectedType): ?>
document.addEventListener('DOMContentLoaded', () => {
    onTypeChange('<?php echo $selectedType; ?>');
    // No re-limpiar el subtype input que ya viene pre-llenado
});
<?php endif; ?>

// ── Char count ────────────────────────────────────────────────────────────────
function updateCount(el, countId, max) {
    document.getElementById(countId).textContent = el.value.length;
}

// ── Tags system ───────────────────────────────────────────────────────────────
const tagsByType = {
    restaurante:    ['pizza','burger','sushi','pasta','parrilla','vegetariano','vegano','delivery','almuerzo','cena','brunch','menú ejecutivo'],
    cafeteria:      ['specialty coffee','medialunas','tortas','desayuno','merienda','brunch','wifi','coworking','frappé'],
    bar:            ['cerveza artesanal','cocktails','tragos','tapas','picadas','karaoke','djs','vino','happy hour'],
    panaderia:      ['pan artesanal','masa madre','medialunas','facturas','tortas','macarons','sin gluten','pastelería'],
    farmacia:       ['medicamentos','cosméticos','perfumería','pañales','suplementos','test covid','turno permanente','inyecciones'],
    supermercado:   ['frutas','verduras','carnicería','fiambrería','lácteos','limpieza','mayorista','bebidas'],
    comercio:       ['electrónica','ropa','calzado','hogar','juguetes','libros','accesorios','moda','deportes'],
    productora_audiovisual: ['video institucional','spots publicitarios','fotografía','edición','streaming','drone','podcast'],
    escuela_musicos: ['guitarra','piano','canto','batería','violín','teoría musical','ensamble'],
    taller_artes: ['pintura','dibujo','escultura','cerámica','arte infantil','arte terapéutico','acuarela'],
    biodecodificacion: ['sesiones individuales','talleres grupales','formación','acompañamiento emocional','bienestar'],
    libreria_cristiana: ['biblias','devocionales','música cristiana','regalería','libros infantiles','estudio bíblico'],
    autos_venta:    ['0km','usados','financiación','permuta','SUV','pickup','sedán'],
    motos_venta:    ['scooter','urbana','enduro','financiación','permuta','baja cilindrada','alta cilindrada'],
    verduleria:     ['verduras frescas','frutas','orgánicos','agroecológico','almacén','temporada','a granel'],
    carniceria:     ['vacuno','cerdo','pollo','cordero','embutidos','achuras','hamburguesas artesanales','fiambrería'],
    pastas:         ['fideos frescos','rellenas','ñoquis','ravioles','canelones','sin TACC','caseras','salsas'],
    gimnasio:       ['musculación','yoga','crossfit','pilates','natación','spinning','artes marciales','boxing'],
    hotel:          ['desayuno incluido','estacionamiento','pileta','pet friendly','wifi','spa','traslados'],
    inmobiliaria:   ['alquiler','venta','tasaciones','departamentos','casas','locales','oficinas','PH'],
    academia:       ['música','teatro','programación','arte','matemáticas','física','apoyo escolar'],
    idiomas:        ['inglés','portugués','francés','alemán','italiano','chino','japonés','conversación','certificaciones'],
    turismo:        ['excursiones','paquetes','vuelos','traslados','cruceros','visas','seguro de viaje'],
    hospital:       ['emergencias','clínica general','pediatría','cardiología','traumatología','análisis clínicos'],
    salon_belleza:  ['corte','coloración','keratina','manicura','pedicura','extensiones','maquillaje'],
    banco:          ['cuentas','préstamos','inversiones','seguros','tarjetas','cajero automático'],
    taller:         ['chapa y pintura','mecánica general','electricidad','cambio de aceite','frenos'],
    remate:         ['subasta pública','lote','online','judicial','ganadera','inmuebles','vehículos'],
    construccion:   ['plomería','electricidad','albañilería','pintura','pisos','techos','refacción'],
    arquitectura:   ['diseño arquitectónico','planos','permisos','refacción','interior','paisajismo','urbanismo'],
    ingenieria:     ['civil','electrónica','industrial','agronómica','ambiental','asesoramiento','proyectos'],
    herreria:       ['rejas','portones','escaleras','barandas','estructuras metálicas','cerrajería','soldadura'],
    carpinteria:    ['muebles a medida','aberturas','puertas','ventanas','placard','deck','restauración'],
    modista:        ['alta costura','ajustes','confección','uniformes','disfraces','bordados','telas'],
    danza:          ['tango','ballet','folklore','salsa','contemporánea','jazz','hip-hop','clases','espectáculos'],
    psicologo:      ['adultos','adolescentes','infanto-juvenil','pareja','cognitivo','gestalt','sistémico','duelo','ansiedad'],
    psicopedagogo:  ['dificultades de aprendizaje','dislexia','TEA','TDAH','orientación vocacional','evaluación'],
    fonoaudiologo:  ['lenguaje','voz','deglución','audiología','tartamudez','niños','adultos mayores'],
    grafologo:      ['peritaje','orientación','grafoterapia','firma','análisis de escritura'],
    centro_vecinal: ['actividades culturales','talleres','deportes','merendero','apoyo escolar','vecinos','fomento'],
};

function updateTagSuggestions(type) {
    const container = document.getElementById('tag-suggestions');
    container.innerHTML = '';
    const list = tagsByType[type] || [];
    list.forEach(tag => {
        if (currentTags.includes(tag)) return;
        const btn = document.createElement('span');
        btn.className   = 'tag-sug';
        btn.textContent = '+ ' + tag;
        btn.onclick     = () => addTag(tag);
        container.appendChild(btn);
    });
    document.getElementById('suggestions-base').textContent = list.length ? 'clickeá las etiquetas para agregarlas' : '';
}

function addTag(tag) {
    tag = tag.trim().toLowerCase();
    if (!tag || currentTags.includes(tag) || currentTags.length >= 20) return;
    currentTags.push(tag);
    renderTags();
    document.querySelectorAll('.tag-sug').forEach(el => {
        if (el.textContent.replace('+ ','') === tag) el.remove();
    });
}

function removeTag(tag) {
    currentTags = currentTags.filter(t => t !== tag);
    renderTags();
    const checked = document.querySelector('input[name="business_type"]:checked');
    if (checked) updateTagSuggestions(checked.value);
}

function renderTags() {
    const wrap   = document.getElementById('tags-wrap');
    const input  = document.getElementById('tags-raw');
    const hidden = document.getElementById('tags-hidden');
    wrap.querySelectorAll('.tag-chip').forEach(el => el.remove());
    currentTags.forEach(tag => {
        const chip      = document.createElement('div');
        chip.className  = 'tag-chip';
        chip.innerHTML  = tag + ' <span class="del" onclick="removeTag(\'' + tag.replace(/'/g,'') + '\')">×</span>';
        wrap.insertBefore(chip, input);
    });
    hidden.value = currentTags.join(',');
}

function handleTagKey(e) {
    const val = e.target.value.trim();
    if ((e.key === 'Enter' || e.key === ',') && val) {
        e.preventDefault();
        addTag(val.replace(/,/g,''));
        e.target.value = '';
    } else if (e.key === 'Backspace' && !e.target.value && currentTags.length) {
        removeTag(currentTags[currentTags.length - 1]);
    }
}
function filterSuggestions() {}

// ── Form submit: collect services into certifications ─────────────────────────
document.getElementById('main-form').addEventListener('submit', function() {
    const svcFields = ['svc_wifi','svc_parking','svc_accessible','svc_reservas','svc_facturas','svc_pickup','svc_mp'];
    const labels    = {svc_wifi:'WiFi', svc_parking:'Estacionamiento', svc_accessible:'Acceso universal',
                       svc_reservas:'Reservas online', svc_facturas:'Factura fiscal',
                       svc_pickup:'Retiro en local', svc_mp:'Mercado Pago'};
    const checked   = [];
    const certInput = document.getElementById('certifications');
    svcFields.forEach(id => {
        const el = document.querySelector('input[name="' + id + '"]');
        if (el && el.checked) checked.push(labels[id]);
    });
    if (checked.length > 0) {
        const existing = certInput.value.trim();
        certInput.value = (existing ? existing + ' | ' : '') + checked.join(', ');
    }
    // dias_cierre → hidden field
    const days   = Array.from(document.querySelectorAll('input[name="dias_cierre[]"]:checked')).map(c => c.value);
    let dField   = document.querySelector('input[name="dias_cierre"]');
    if (!dField) {
        dField      = document.createElement('input');
        dField.type = 'hidden';
        dField.name = 'dias_cierre';
        this.appendChild(dField);
    }
    dField.value = days.join(',');
});

// Inicializar tags al cargar
document.addEventListener('DOMContentLoaded', () => {
    renderTags();
    if (EDITING) {
        const checked = document.querySelector('input[name="business_type"]:checked');
        if (checked) updateTagSuggestions(checked.value);
    }
});

// ── GALERÍA ───────────────────────────────────────────────────────────────────
<?php if ($editing): ?>
let galleryCount = GALLERY_COUNT;

function galleryMsg(text, ok) {
    const el = document.getElementById('gallery-msg');
    el.style.display    = 'block';
    el.style.background = ok ? '#d1fae5' : '#fee2e2';
    el.style.color      = ok ? '#065f46' : '#991b1b';
    el.textContent      = text;
    setTimeout(() => el.style.display = 'none', 5000);
}

function updateGalleryBadge() {
    const badge = document.getElementById('gallery-badge');
    if (badge) badge.textContent = galleryCount + '/' + MAX_GALLERY;
}

function ensureGalleryDropzone() {
    const grid = document.getElementById('gallery-grid');
    if (!grid) return null;

    let drop = document.getElementById('gallery-drop');
    if (!drop && galleryCount < MAX_GALLERY) {
        drop = document.createElement('div');
        drop.className = 'gallery-dropzone';
        drop.id = 'gallery-drop';
        drop.onclick = () => document.getElementById('gallery-input').click();
        drop.ondragover = (event) => { event.preventDefault(); drop.classList.add('drag-over'); };
        drop.ondragleave = () => drop.classList.remove('drag-over');
        drop.ondrop = handleGalleryDrop;
        drop.innerHTML = '<span style="font-size:2em;">📷</span>' +
            '<span style="font-size:.8em;font-weight:600;color:#4b5563;">Agregar foto</span>' +
            '<span style="font-size:.72em;color:#9ca3af;">Arrastrá o hacé clic</span>';
        grid.appendChild(drop);
    }

    if (drop) {
        drop.style.display = galleryCount >= MAX_GALLERY ? 'none' : '';
    }
    return drop;
}

function uploadGalleryPhoto(file) {
    if (!file) return;
    if (galleryCount >= MAX_GALLERY) {
        galleryMsg('❌ Ya alcanzaste el máximo de ' + MAX_GALLERY + ' fotos.', false);
        return;
    }
    const fd = new FormData();
    fd.append('business_id', BIZ_ID);
    fd.append('action', 'upload');
    fd.append('photo', file);

    const drop = ensureGalleryDropzone();
    if (drop) { drop.style.opacity = '.5'; drop.style.pointerEvents = 'none'; }

    fetch('/api/upload_business_gallery.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (drop) { drop.style.opacity = '1'; drop.style.pointerEvents = 'auto'; }
            if (data.success) {
                galleryMsg('✅ Foto subida.', true);
                galleryCount++;
                updateGalleryBadge();
                addGalleryItem(data.filename, data.url);
                ensureGalleryDropzone();
            } else {
                galleryMsg('❌ ' + data.message, false);
            }
        })
        .catch(() => {
            if (drop) { drop.style.opacity = '1'; drop.style.pointerEvents = 'auto'; }
            galleryMsg('❌ Error de conexión.', false);
        });
}

function addGalleryItem(filename, url) {
    const grid = document.getElementById('gallery-grid');
    const drop  = document.getElementById('gallery-drop');
    const item  = document.createElement('div');
    item.className = 'gallery-item';
    item.id        = 'gi_' + filename;
    item.innerHTML = '<img src="' + url + '" alt="Foto">' +
        '<button type="button" class="del-btn" onclick="deleteGalleryPhoto(\'' + filename + '\')" title="Eliminar">×</button>';
    if (drop) {
        grid.insertBefore(item, drop);
    } else {
        grid.appendChild(item);
    }
}

function deleteGalleryPhoto(filename) {
    if (!confirm('¿Eliminar esta foto?')) return;
    const fd = new FormData();
    fd.append('business_id', BIZ_ID);
    fd.append('action', 'delete');
    fd.append('filename', filename);
    fetch('/api/upload_business_gallery.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                galleryMsg('🗑️ Foto eliminada.', true);
                const item = document.getElementById('gi_' + filename);
                if (item) item.remove();
                galleryCount = Math.max(0, galleryCount - 1);
                updateGalleryBadge();
                ensureGalleryDropzone();
            } else {
                galleryMsg('❌ ' + data.message, false);
            }
        })
        .catch(() => galleryMsg('❌ Error de conexión.', false));
}

function handleGalleryDrop(e) {
    e.preventDefault();
    const drop = document.getElementById('gallery-drop');
    if (drop) drop.classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) uploadGalleryPhoto(file);
}

// ── OG PHOTO ──────────────────────────────────────────────────────────────────
function ogMsg(text, ok) {
    const el = document.getElementById('og-msg');
    el.style.display    = 'block';
    el.style.background = ok ? '#d1fae5' : '#fee2e2';
    el.style.color      = ok ? '#065f46' : '#991b1b';
    el.textContent      = text;
    setTimeout(() => el.style.display = 'none', 4500);
}

function refreshOgPreviews(src) {
    const ts  = '?t=' + Date.now();
    const url = src || ('/api/og_image.php?type=business&id=' + BIZ_ID + ts);
    document.getElementById('og-preview-img').src = url + (src ? ts : '');
    document.getElementById('og-wa-thumb').src    = url + (src ? ts : '');
}

function uploadOgPhoto(file) {
    if (!file) return;
    const dz = document.getElementById('og-dropzone');
    dz.style.opacity = '.5'; dz.style.pointerEvents = 'none';
    const fd = new FormData();
    fd.append('business_id', BIZ_ID);
    fd.append('og_photo', file);
    fd.append('action', 'upload');
    fetch('/api/upload_og_photo.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            dz.style.opacity = '1'; dz.style.pointerEvents = 'auto';
            if (data.success) {
                ogMsg('✅ ' + data.message, true);
                refreshOgPreviews(data.preview);
                const badge = document.querySelector('.og-badge');
                if (badge) { badge.style.background = '#27ae60'; badge.textContent = '✓ Foto personalizada'; }
            } else {
                ogMsg('❌ ' + data.message, false);
            }
        })
        .catch(() => { dz.style.opacity = '1'; dz.style.pointerEvents = 'auto'; ogMsg('❌ Error de conexión.', false); });
}

function handleOgDrop(e) {
    e.preventDefault();
    document.getElementById('og-dropzone').classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) uploadOgPhoto(file);
}

function deleteOgPhoto() {
    if (!confirm('¿Eliminar la foto personalizada?')) return;
    fetch('/api/upload_og_photo.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'business_id=' + BIZ_ID + '&action=delete'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            ogMsg('🗑️ Foto eliminada.', true);
            refreshOgPreviews(null);
            const badge = document.querySelector('.og-badge');
            if (badge) { badge.style.background = '#e67e22'; badge.textContent = 'Generada automáticamente'; }
            const btn = document.querySelector('button[onclick="deleteOgPhoto()"]');
            if (btn) btn.closest('div').style.display = 'none';
        }
    });
}

function delegationCsrfToken() {
    const field = document.querySelector('input[name="csrf_token"]');
    return field ? field.value : '';
}

function bizDelegMsg(text, ok) {
    const el = document.getElementById('biz-deleg-msg');
    if (!el) return;
    el.style.display = 'block';
    el.className = 'message ' + (ok ? 'msg-success' : 'msg-error');
    el.textContent = (ok ? '✅ ' : '❌ ') + text;
}

function bizDelegPassword() {
    const field = document.getElementById('biz-delegate-password');
    const value = (field?.value || '').trim();
    if (!value) {
        bizDelegMsg('Debés ingresar tu password para confirmar.', false);
        return null;
    }
    return value;
}

async function lookupDelegateUser(query) {
    const r = await fetch('/api/users/lookup.php?query=' + encodeURIComponent(query));
    const data = await r.json();
    if (!r.ok || !data.success || !data.data?.user) {
        throw new Error(data.message || 'No se pudo encontrar el usuario.');
    }
    return data.data.user;
}

function renderBusinessDelegations(items) {
    const list  = document.getElementById('biz-deleg-list');
    const empty = document.getElementById('biz-deleg-empty');
    if (!list || !empty) return;

    list.innerHTML = '';
    if (!items.length) {
        empty.style.display = '';
        empty.textContent = 'No hay delegados administrativos.';
        return;
    }

    empty.style.display = 'none';
    items.forEach(item => {
        const row = document.createElement('div');
        row.className = 'deleg-item';

        const meta = document.createElement('div');
        const username = document.createElement('strong');
        username.textContent = item.username || ('Usuario #' + item.user_id);
        const email = document.createElement('small');
        email.textContent = item.email || '';
        meta.appendChild(username);
        meta.appendChild(document.createElement('br'));
        meta.appendChild(email);

        const action = document.createElement('button');
        action.type = 'button';
        action.className = 'deleg-btn';
        action.textContent = 'Revocar';
        action.onclick = () => revokeBusinessDelegation(item.user_id, item.username || item.email || ('#' + item.user_id));

        row.appendChild(meta);
        row.appendChild(action);
        list.appendChild(row);
    });
}

async function loadBusinessDelegations() {
    try {
        const r = await fetch('/api/business_delegations/list.php?business_id=' + encodeURIComponent(BIZ_ID));
        const data = await r.json();
        if (!r.ok || !data.success) {
            throw new Error(data.message || 'No se pudieron cargar las delegaciones.');
        }
        renderBusinessDelegations(data.data?.delegations || []);
    } catch (error) {
        renderBusinessDelegations([]);
        bizDelegMsg(error.message || 'No se pudieron cargar las delegaciones.', false);
    }
}

async function delegateBusinessAdmin() {
    const query = (document.getElementById('biz-delegate-query')?.value || '').trim();
    if (!query) {
        bizDelegMsg('Ingresá username o email.', false);
        return;
    }
    const password = bizDelegPassword();
    if (!password) return;

    try {
        const user = await lookupDelegateUser(query);
        if (Number(user.id) === Number(<?php echo json_encode($userId); ?>)) {
            bizDelegMsg('No podés delegarte a vos mismo.', false);
            return;
        }

        const payload = new URLSearchParams();
        payload.append('business_id', String(BIZ_ID));
        payload.append('user_id', String(user.id));
        payload.append('password', password);
        payload.append('csrf_token', delegationCsrfToken());

        const r = await fetch('/api/business_delegations/create.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: payload.toString()
        });
        const data = await r.json();
        if (!r.ok || !data.success) {
            throw new Error(data.message || 'No se pudo delegar.');
        }

        bizDelegMsg(data.message || 'Delegación creada.', true);
        document.getElementById('biz-delegate-query').value = '';
        document.getElementById('biz-delegate-password').value = '';
        await loadBusinessDelegations();
    } catch (error) {
        bizDelegMsg(error.message || 'No se pudo delegar.', false);
    }
}

async function revokeBusinessDelegation(userId, label) {
    const password = bizDelegPassword();
    if (!password) return;
    if (!confirm('¿Revocar delegación de ' + label + '?')) return;

    try {
        const payload = new URLSearchParams();
        payload.append('business_id', String(BIZ_ID));
        payload.append('user_id', String(userId));
        payload.append('password', password);
        payload.append('csrf_token', delegationCsrfToken());

        const r = await fetch('/api/business_delegations/revoke.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: payload.toString()
        });
        const data = await r.json();
        if (!r.ok || !data.success) {
            throw new Error(data.message || 'No se pudo revocar.');
        }

        bizDelegMsg(data.message || 'Delegación revocada.', true);
        document.getElementById('biz-delegate-password').value = '';
        await loadBusinessDelegations();
    } catch (error) {
        bizDelegMsg(error.message || 'No se pudo revocar.', false);
    }
}

if (EDITING && BIZ_ID) {
    loadBusinessDelegations();
}
<?php endif; ?>
</script>
</body>
</html>
