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

    // ── Acción: Duplicar ──────────────────────────────────────────────────────
    if (isset($_POST['action']) && $_POST['action'] === 'duplicar' && $editing) {
        $result = duplicateBusiness($businessId, $userId);
        if ($result['success']) {
            header("Location: /edit?id=" . $result['business_id'] . "&duplicado=1");
            exit();
        }
        $message     = $result['message'];
        $messageType = 'error';
    } else {
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
}

// ── Datos para pre-llenar ─────────────────────────────────────────────────────
function bv(array|null $b, string $k, mixed $def = ''): string {
    return htmlspecialchars(($b[$k] ?? $def), ENT_QUOTES, 'UTF-8');
}

$selectedType    = $business['business_type'] ?? '';
$currentTags     = array_filter(array_map('trim', explode(',', ($comercioData['categorias_productos'] ?? ''))));
$currentDias     = array_filter(array_map('trim', explode(',', ($comercioData['dias_cierre']          ?? ''))));
$currentTimezone = $comercioData['timezone'] ?? 'America/Argentina/Buenos_Aires';
$currentCountry  = $business['country_code']       ?? '';
$currentLang     = $business['language_code']      ?? '';
$currentCurrency = $business['currency_code']      ?? '';
$currentPhoneCC  = $business['phone_country_code'] ?? '';
$currentAddrFmt  = $business['address_format']     ?? '';
$certifVal       = $business['certifications'] ?? '';
$dispActivo      = !empty($business['disponibles_activo']);
$jobOfferActivo  = !empty($business['job_offer_active']);
$esProveedor     = !empty($business['es_proveedor']);

// Tipos de negocio que NO pueden ser Proveedor "P" (servicios puros)
$noProveedorTypes = [
    'agente_inpi', 'estudio_juridico', 'abogado', 'inmobiliaria', 'seguros',
    'banco', 'hospital', 'farmacia', 'medico_pediatra', 'medico_traumatologo',
    'laboratorio', 'enfermeria', 'asistencia_ancianos', 'psicologo', 'psicopedagogo',
    'fonoaudiologo', 'grafologo', 'academia', 'idiomas', 'escuela', 'maestro_particular',
    'arquitectura', 'ingenieria', 'ingenieria_civil', 'electricista', 'gasista', 'contador',
    'seguridad', 'obra_de_arte',
];
$puedeSerProveedor = $editing && !in_array($selectedType, $noProveedorTypes, true);

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
        'medico_pediatra' => ['🧒', 'Médico Pediatra'],
        'medico_traumatologo' => ['🦴', 'Médico Traumatólogo'],
        'laboratorio'     => ['🧪', 'Laboratorio'],
        'odontologia'     => ['🦷', 'Odontología'],
        'psicologo'       => ['🧠', 'Psicología'],
        'psicopedagogo'   => ['📚', 'Psicopedagogía'],
        'fonoaudiologo'   => ['🗣️', 'Fonoaudiología'],
        'grafologo'       => ['✍️', 'Grafología'],
        'enfermeria'      => ['🩺', 'Enfermería'],
        'asistencia_ancianos' => ['🧓', 'Asistencia a Ancianos'],
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
        'agente_inpi'    => ['📋', 'Agente INPI'],
        'contador'       => ['📊', 'Contaduría'],
        'arquitectura'   => ['📐', 'Arquitectura'],
        'ingenieria'     => ['⚙️', 'Ingeniería'],
        'ingenieria_civil' => ['🏗️', 'Ingeniería Civil'],
        'electricista'   => ['💡', 'Electricista'],
        'gasista'        => ['🔥', 'Gasista matriculado'],
        'gas_en_garrafa' => ['🛢️', 'Gas en garrafa'],
        'seguridad'      => ['🛡️', 'Seguridad'],
        'grafica'        => ['🖨️', 'Gráfica'],
        'astrologo'      => ['🔮', 'Astrólogo'],
        'zapatero'       => ['👞', 'Zapatero'],
        'videojuegos'    => ['🎮', 'Videojuegos'],
        'maestro_particular' => ['📘', 'Maestro particular'],
        'alquiler_mobiliario_fiestas' => ['🪑', 'Alquiler de mobiliario para fiestas'],
        'propalacion_musica' => ['🔊', 'Propalación (música)'],
        'animacion_fiestas' => ['🎉', 'Animación de fiestas'],
        'taller'         => ['🔩', 'Taller Mecánico'],
        'herreria'       => ['🔨', 'Herrería'],
        'carpinteria'    => ['🪵', 'Carpintería'],
        'modista'        => ['🧵', 'Modista / Costura'],
        'construccion'   => ['🏗️', 'Construcción'],
        'centro_vecinal' => ['🏘️', 'Centro Vecinal / ONG'],
        'remate'         => ['🔨', 'Remates / Subastas'],
    ],
    'Transporte' => [
        'transporte'          => ['🚛', 'Transporte (general)'],
        'transporte_envios'   => ['📦', 'Transporte – Envíos'],
        'transporte_pasajeros'=> ['🚌', 'Transporte – Pasajeros'],
        'transporte_carga'    => ['🏗️', 'Transporte – Carga'],
        'transportista'       => ['🚚', 'Transportista'],
        'logistica'           => ['🏭', 'Logística'],
        'flota'               => ['🚐', 'Flota'],
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
    'Arte & Cultura' => [
        'obra_de_arte'          => ['🎭', 'Obra de Arte / Proyecto Artístico'],
        'musico'                => ['🎸', 'Músico'],
        'cantante'              => ['🎤', 'Cantante'],
        'bailarin'              => ['💃', 'Bailarín/a'],
        'actor'                 => ['🎭', 'Actor'],
        'actriz'                => ['🎭', 'Actriz'],
        'director_artistico'    => ['🎬', 'Director/a artístico/a'],
        'guionista'             => ['📝', 'Guionista'],
        'escenografo'           => ['🖼️', 'Escenógrafo/a'],
        'fotografo_artistico'   => ['📷', 'Fotógrafo/a artístico/a'],
        'productor_artistico'   => ['🎙️', 'Productor/a artístico/a'],
        'maquillador'           => ['💄', 'Maquillador/a'],
        'pintor'                => ['🎨', 'Pintor/a'],
        'poeta'                 => ['📜', 'Poeta'],
        'musicalizador'         => ['🎵', 'Musicalizador/a'],
        'editor_grafico'        => ['🖥️', 'Editor/a gráfico/a'],
        'asistente_artistico'   => ['🤝', 'Asistente artístico/a'],
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
    'medico_pediatra' => 'Atención pediátrica (ej: control de niño sano, guardia, consultorio…)',
    'medico_traumatologo' => 'Especialidad traumatológica (ej: columna, deportiva, rehabilitación…)',
    'laboratorio'    => 'Tipo de análisis (ej: clínicos, hormonales, domiciliarios…)',
    'gimnasio'       => 'Disciplinas (ej: crossfit, yoga, artes marciales…)',
    'academia'       => 'Área de enseñanza (ej: música, informática, teatro…)',
    'idiomas'        => 'Idiomas que se enseñan (ej: inglés, francés, portugués…)',
    'turismo'        => 'Servicios (ej: excursiones, paquetes, receptivo…)',
    'taller'         => 'Especialidad (ej: chapa y pintura, electromecánica…)',
    'arquitectura'   => 'Especialidad (ej: diseño interior, urbanismo, paisajismo…)',
    'ingenieria'     => 'Rama (ej: civil, electrónica, industrial, agronómica…)',
    'ingenieria_civil' => 'Especialidad (ej: cálculo estructural, obra civil, dirección técnica…)',
    'electricista'   => 'Servicio eléctrico (ej: domiciliario, industrial, emergencias…)',
    'gasista'        => 'Servicio de gas (ej: instalación, mantenimiento, emergencias…)',
    'gas_en_garrafa' => 'Servicio (ej: reparto, recarga, comercialización…)',
    'seguridad'      => 'Especialidad (ej: vigilancia, monitoreo, alarmas, custodias…)',
    'grafica'        => 'Servicios gráficos (ej: impresiones, ploteos, cartelería…)',
    'astrologo'      => 'Tipo de consulta (ej: natal, compatibilidad, orientación…)',
    'zapatero'       => 'Servicios (ej: arreglo, suelas, restauración, confección…)',
    'videojuegos'    => 'Enfoque (ej: venta, alquiler, e-sports, reparación…)',
    'maestro_particular' => 'Materia o nivel (ej: matemática, idioma, apoyo escolar…)',
    'alquiler_mobiliario_fiestas' => 'Tipo de mobiliario (ej: mesas, sillas, livings, carpas…)',
    'propalacion_musica' => 'Servicio (ej: sonido, musicalización, DJ, eventos…)',
    'animacion_fiestas' => 'Tipo de animación (ej: infantil, eventos sociales, corporativos…)',
    'enfermeria'     => 'Modalidad (ej: domiciliaria, guardia, cuidados postoperatorios…)',
    'asistencia_ancianos' => 'Tipo de asistencia (ej: acompañamiento, higiene, medicación…)',
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
    'obra_de_arte'        => 'Tipo de proyecto (ej: teatro, danza, música, circo, performance…)',
    'musico'              => 'Instrumento o género (ej: guitarra, jazz, clásico, folk…)',
    'cantante'            => 'Estilo vocal (ej: lírico, pop, folklore, gospel…)',
    'bailarin'            => 'Estilo de danza (ej: contemporánea, tango, flamenco, ballet…)',
    'actor'               => 'Especialidad (ej: teatro, cine, doblaje, comedia…)',
    'actriz'              => 'Especialidad (ej: teatro, cine, doblaje, comedia…)',
    'director_artistico'  => 'Área (ej: teatro, ópera, danza, eventos…)',
    'guionista'           => 'Formato (ej: cine, teatro, publicidad, webserie…)',
    'escenografo'         => 'Especialidad (ej: teatro, cine, eventos, instalaciones…)',
    'fotografo_artistico' => 'Estilo (ej: retrato, documental, moda, arte…)',
    'productor_artistico' => 'Área (ej: música, teatro, cine, eventos…)',
    'maquillador'         => 'Especialidad (ej: artístico, cinematográfico, caracterización…)',
    'pintor'              => 'Estilo (ej: óleo, acuarela, mural, abstracto…)',
    'poeta'               => 'Género (ej: verso libre, haiku, slam, spoken word…)',
    'musicalizador'       => 'Contexto (ej: teatro, cine, eventos, radio…)',
    'editor_grafico'      => 'Especialidad (ej: identidad visual, editorial, multimedia…)',
    'asistente_artistico' => 'Área de asistencia (ej: producción, escenografía, dirección…)',
    'transporte'           => 'Tipo de servicio (ej: cargas, mudanzas, mensajería, distribución…)',
    'transporte_envios'    => 'Tipo de envío (ej: paquetería, mensajería, distribución, correspondencia…)',
    'transporte_pasajeros' => 'Modalidad (ej: escolar, turístico, corporativo, transfer aeropuerto…)',
    'transporte_carga'     => 'Tipo de carga (ej: materiales de construcción, mudanzas, agrícola, refrigerada…)',
    'transportista'        => 'Especialidad (ej: larga distancia, regional, urbano…)',
    'logistica'            => 'Servicio (ej: almacenamiento, distribución, última milla…)',
    'flota'                => 'Tipo de flota (ej: autos, combis, camiones, motos…)',
];

$diasSemana = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
$professionalLicensedTypes = [
    'gasista','electricista','enfermeria','medico_pediatra','medico_traumatologo','arquitectura','ingenieria_civil'
];
$restrictedApprovalTypes = ['abogado', 'seguros', 'inmobiliaria', 'agente_inpi'];

$descriptionPlaceholders = [
    'default' => 'Contá de forma clara y respetuosa qué servicio brindás, en qué zona trabajás y cómo contactarte.',
    'farmacia' => 'Ej: Farmacia con atención profesional, turnos y asesoramiento responsable para la comunidad.',
    'medico_pediatra' => 'Ej: Consultorio pediátrico con controles de niño sano y seguimiento integral de la infancia.',
    'medico_traumatologo' => 'Ej: Atención traumatológica para lesiones deportivas, rehabilitación y seguimiento profesional.',
    'laboratorio' => 'Ej: Laboratorio de análisis clínicos con turnos, extracciones y entrega de resultados.',
    'electricista' => 'Ej: Servicio eléctrico domiciliario e industrial con atención programada y de urgencias.',
    'gasista' => 'Ej: Servicio de instalaciones y mantenimiento de gas con trabajo seguro y responsable.',
    'gas_en_garrafa' => 'Ej: Distribución de gas en garrafa con entrega a domicilio y horarios de atención.',
    'alquiler_mobiliario_fiestas' => 'Ej: Alquiler de mobiliario para eventos con entrega, armado y retiro coordinado.',
    'animacion_fiestas' => 'Ej: Animación de fiestas infantiles y sociales con propuestas recreativas para cada edad.',
    'maestro_particular' => 'Ej: Clases particulares personalizadas por nivel, materia y modalidad presencial/virtual.',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo $editing ? 'Editar Negocio' : 'Publicar Negocio'; ?> — Mapita</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="/js/geo-search.js"></script>
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
        .geo-search-wrap { display: flex; gap: 8px; margin-bottom: 10px; }
        .geo-search-wrap input { flex: 1; padding: 9px 12px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: .88em; outline: none; transition: border-color .2s; }
        .geo-search-wrap input:focus { border-color: #1B3B6F; box-shadow: 0 0 0 3px rgba(27,59,111,.1); }
        .geo-search-wrap button { padding: 9px 15px; background: #1B3B6F; color: white; border: none; border-radius: 8px; font-size: .88em; font-weight: 600; cursor: pointer; white-space: nowrap; transition: background .15s; }
        .geo-search-wrap button:hover { background: #0d2247; }
        .geo-search-results { background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,.1); display: none; max-height: 220px; overflow-y: auto; margin-bottom: 10px; }
        /* Mini-mapa para la ubicación del inmueble */
        #inm-map-picker { height: 220px; border-radius: 8px; overflow: hidden; border: 1.5px solid #d1fae5; margin-bottom: 8px; isolation: isolate; }

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
        /* ── Web Help Popover ─────────────────────────────── */
        .web-help-wrap { position: relative; display: flex; align-items: center; gap: 8px; }
        .web-help-wrap input[type=url] { flex: 1; min-width: 0; }
        .web-help-btn {
            flex-shrink: 0; width: 32px; height: 32px;
            border: none; border-radius: 50%;
            background: #fef3c7; color: #d97706;
            font-size: 16px; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 1px 4px rgba(0,0,0,.12);
            transition: background .15s, transform .15s; padding: 0;
        }
        .web-help-btn:hover { background: #fde68a; transform: scale(1.1); }
        .web-help-btn:focus { outline: 2px solid #d97706; outline-offset: 2px; }
        .web-help-popover {
            display: none; position: fixed; z-index: 9999;
            width: min(340px, 90vw);
            background: #fff; border: 1px solid #e5e7eb;
            border-radius: 14px; box-shadow: 0 8px 32px rgba(0,0,0,.18);
            padding: 18px 20px 16px; font-size: 13.5px;
            color: #374151; line-height: 1.55;
        }
        .web-help-popover.open { display: block; }
        .web-help-popover-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 10px;
        }
        .web-help-popover-title { font-weight: 800; font-size: 14px; color: #1B3B6F; }
        .web-help-close {
            border: none; background: transparent; cursor: pointer;
            font-size: 18px; color: #9ca3af; line-height: 1; padding: 2px 6px;
            border-radius: 4px; transition: color .15s, background .15s;
        }
        .web-help-close:hover { color: #ef4444; background: #fef2f2; }
        .web-help-popover p { margin: 0 0 12px; }
        .alfo-link {
            font-weight: 900; color: #1d4ed8;
            background: #dbeafe; padding: 1px 5px; border-radius: 4px;
            text-decoration: none;
        }
        .alfo-link:hover { background: #bfdbfe; text-decoration: underline; }
        .web-help-cta {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 9px 16px; border-radius: 8px;
            background: #1B3B6F; color: #fff !important;
            text-decoration: none; font-weight: 700; font-size: 13px;
            transition: background .15s; margin-top: 2px;
        }
        .web-help-cta:hover { background: #0d2247; }
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
    <?php elseif (isset($_GET['duplicado']) && $_GET['duplicado'] == 1): ?>
        <div class="message msg-info">📋 ¡Negocio duplicado! Por favor, asigná la <strong>nueva ubicación</strong> en el mapa antes de guardar.</div>
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

                <div id="professional-license-note"
                     style="display:<?php echo in_array($selectedType, $professionalLicensedTypes, true) ? 'block' : 'none'; ?>;margin-top:12px;padding:10px 12px;border-radius:8px;border:1px solid #dbeafe;background:#eff6ff;color:#1e3a8a;font-size:.82em;">
                    Para brindar más tranquilidad a los usuarios, se aconseja subir copia del título o matrícula profesional.
                </div>
                <div id="restricted-approval-note"
                     style="display:<?php echo in_array($selectedType, $restrictedApprovalTypes, true) ? 'block' : 'none'; ?>;margin-top:10px;padding:10px 12px;border-radius:8px;border:1px solid #fde68a;background:#fffbeb;color:#92400e;font-size:.82em;">
                    Este rubro requiere aprobación expresa del administrador para habilitar su publicación en el mapa.
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
                                  placeholder="<?php echo htmlspecialchars($descriptionPlaceholders[$selectedType] ?? $descriptionPlaceholders['default']); ?>"
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
                        <label for="website">Sitio web 💡</label>
                        <div class="web-help-wrap">
                            <input type="url" id="website" name="website" maxlength="300"
                                   placeholder="https://www.minegocio.com"
                                   value="<?php echo bv($business, 'website'); ?>">
                            <button type="button" class="web-help-btn"
                                    onclick="toggleWebHelp('wh-negocio')"
                                    title="¿Sin página web? Ver opciones gratuitas"
                                    aria-label="Ayuda para el campo sitio web"
                                    aria-expanded="false" aria-controls="wh-negocio">💡</button>
                            <div id="wh-negocio" class="web-help-popover" role="tooltip">
                                <div class="web-help-popover-header">
                                    <span class="web-help-popover-title">💡 ¿Sin página web?</span>
                                    <button type="button" class="web-help-close"
                                            onclick="toggleWebHelp('wh-negocio')"
                                            aria-label="Cerrar ayuda">✕</button>
                                </div>
                                <p>
                                    Si no dispone de página web, utilice el servicio
                                    <a class="alfo-link" href="https://www.alfoweb.com.ar"
                                       target="_blank" rel="noopener noreferrer">ALFO</a>
                                    donde podrá obtener su sitio de forma <strong>libre y gratuita</strong>
                                    para empezar a trabajar de forma virtual.
                                </p>
                                <a class="web-help-cta"
                                 href="mailto:myweb@alfoweb.com.ar?subject=Consulta%20Mapita%20-%20Sitio%20web">
                                    ✉️ Consultar por email
                                </a>
                            </div>
                        </div>
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
                <div class="divider"></div>
                <div class="field">
                    <label for="timezone">Zona horaria del negocio <span class="hint">— para calcular apertura/cierre correctamente en cualquier país</span></label>
                    <select id="timezone" name="timezone" class="field-input">
                        <?php foreach (getTimezoneOptions() as $group => $zones): ?>
                        <optgroup label="<?= htmlspecialchars($group) ?>">
                            <?php foreach ($zones as $tz => $label): ?>
                            <option value="<?= htmlspecialchars($tz) ?>"<?= $currentTimezone === $tz ? ' selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="divider"></div>
                <!-- ── LOCALIZACIÓN ────────────────────────────────────────── -->
                <div class="field">
                    <label for="country_code">🌍 País del negocio
                        <span class="hint">— determina la moneda, el prefijo telefónico y el organismo registrador de marcas</span>
                    </label>
                    <select id="country_code" name="country_code" class="field-input" onchange="onCountryChange(this.value)">
                        <option value="">— Sin especificar —</option>
                        <?php foreach (getCountryOptions() as $regionLabel => $countries): ?>
                        <optgroup label="<?= htmlspecialchars($regionLabel) ?>">
                            <?php foreach ($countries as $cc => $cname): ?>
                            <option value="<?= htmlspecialchars($cc) ?>"<?= $currentCountry === $cc ? ' selected' : '' ?>>
                                <?= htmlspecialchars($cname) ?>
                            </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="language_code">🗣️ Idioma principal del negocio</label>
                    <select id="language_code" name="language_code" class="field-input">
                        <option value="">— Sin especificar —</option>
                        <?php foreach (getLanguageOptions() as $lc => $lname): ?>
                        <option value="<?= htmlspecialchars($lc) ?>"<?= $currentLang === $lc ? ' selected' : '' ?>>
                            <?= htmlspecialchars($lname) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field-row" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="field">
                        <label for="currency_code">💱 Moneda (ISO 4217)</label>
                        <input type="text" id="currency_code" name="currency_code" class="field-input"
                               maxlength="3" placeholder="Ej: ARS, USD, EUR"
                               value="<?= htmlspecialchars($currentCurrency) ?>">
                    </div>
                    <div class="field">
                        <label for="phone_country_code">📞 Prefijo internacional</label>
                        <input type="text" id="phone_country_code" name="phone_country_code" class="field-input"
                               maxlength="6" placeholder="Ej: +54, +1, +49"
                               value="<?= htmlspecialchars($currentPhoneCC) ?>">
                    </div>
                </div>
                <div class="field" id="addr-hint-wrap" style="display:none;">
                    <label>📬 Formato de dirección sugerido para este país</label>
                    <div id="addr-hint" style="font-size:.82em;color:#6b7280;background:#f3f4f6;padding:8px 12px;border-radius:8px;border:1px solid #e5e7eb;"></div>
                </div>
                <input type="hidden" id="address_format" name="address_format" value="<?= htmlspecialchars($currentAddrFmt) ?>">
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

        <!-- ══ MÓDULO DISPONIBLES ($$$) ══════════════════════════════════════ -->
        <div class="form-section">
            <div class="section-head">
                <span class="section-icon">💸</span>
                <div>
                    <div class="section-title">Panel de disponibles ($$$)</div>
                    <div class="section-desc">Opcional: activá el módulo y cargá ítems para solicitudes</div>
                </div>
            </div>
            <div class="section-body">
                <?php if ($editing): ?>
                    <label id="disp-toggle-row" style="display:flex;align-items:flex-start;gap:12px;cursor:pointer;padding:10px 12px;border:1px solid #e5e7eb;border-radius:10px;background:#f8fafc;">
                        <input type="checkbox" id="disp-modulo-toggle" <?php echo $dispActivo ? 'checked' : ''; ?> style="margin-top:3px;">
                        <div>
                            <div id="disp-modulo-label" style="font-weight:700;color:#1f2937;"><?php echo $dispActivo ? 'Módulo activo' : 'Módulo inactivo'; ?></div>
                            <div style="font-size:.8em;color:#6b7280;">Primero activalo y luego cargá/gestioná los ítems en el panel.</div>
                        </div>
                    </label>
                    <div id="disp-panel-msg" style="margin:12px 0 0;font-size:.82em;color:#6b7280;"></div>
                    <div style="margin-top:14px;">
                        <a href="/panel-disponibles?id=<?php echo $businessId; ?>" class="btn-save" style="display:inline-flex;text-decoration:none;width:auto;padding:10px 16px;">
                            📋 Abrir panel de disponibles
                        </a>
                    </div>
                <?php else: ?>
                    <p style="margin:0;font-size:.84em;color:#4b5563;line-height:1.6;">
                        Este módulo es <strong>opcional</strong>. Después de publicar el negocio, podrás activarlo y completar los ítems desde
                        <strong>Editar negocio → Panel de disponibles</strong>.
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- ══ MÓDULO BUSCO EMPLEADOS/AS ════════════════════════════════════ -->
        <div class="form-section">
            <div class="section-head">
                <span class="section-icon">💼</span>
                <div>
                    <div class="section-title">Busco Empleados/as</div>
                    <div class="section-desc">Publicá una oferta laboral y recibí postulaciones de usuarios registrados</div>
                </div>
            </div>
            <div class="section-body">
                <?php if ($editing): ?>
                    <label id="job-toggle-row" style="display:flex;align-items:flex-start;gap:12px;cursor:pointer;padding:10px 12px;border:1px solid #e5e7eb;border-radius:10px;background:#f8fafc;">
                        <input type="checkbox" id="job-offer-toggle" <?php echo $jobOfferActivo ? 'checked' : ''; ?> style="margin-top:3px;">
                        <div>
                            <div id="job-offer-label" style="font-weight:700;color:#1f2937;"><?php echo $jobOfferActivo ? 'Oferta activa' : 'Oferta inactiva'; ?></div>
                            <div style="font-size:.8em;color:#6b7280;">Completá el puesto y luego activá la oferta. Solo usuarios registrados pueden postularse.</div>
                        </div>
                    </label>

                    <div style="margin-top:18px;display:flex;flex-direction:column;gap:14px;">
                        <div class="field">
                            <label for="job-position">🔍 Puesto / Posición buscada <span style="color:#e74c3c">*</span></label>
                            <input type="text" id="job-position" maxlength="255" placeholder="Ej: Cajero/a, Atención al cliente, Desarrollador/a…"
                                   value="<?php echo htmlspecialchars($business['job_offer_position'] ?? ''); ?>">
                            <div style="font-size:.75em;color:#9ca3af;margin-top:3px;">Requerido para activar la oferta.</div>
                        </div>
                        <div class="field">
                            <label for="job-description">📝 Descripción del puesto</label>
                            <textarea id="job-description" rows="3" maxlength="3000" style="resize:vertical;"
                                      placeholder="Describí tareas, requisitos, horarios, condiciones…"><?php echo htmlspecialchars($business['job_offer_description'] ?? ''); ?></textarea>
                        </div>
                        <div class="field">
                            <label for="job-url">🔗 Link externo de postulación <em style="color:#9ca3af;font-weight:400;">(opcional)</em></label>
                            <input type="url" id="job-url" maxlength="500" placeholder="https://…"
                                   value="<?php echo htmlspecialchars($business['job_offer_url'] ?? ''); ?>">
                            <div style="font-size:.75em;color:#9ca3af;margin-top:3px;">Si tenés un formulario propio, pegá el link acá. Convive con las postulaciones internas.</div>
                        </div>
                    </div>

                    <div id="job-offer-msg" style="margin:12px 0 0;font-size:.82em;"></div>

                    <div style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap;">
                        <button type="button" id="job-save-btn" class="btn-save" style="width:auto;padding:10px 16px;" onclick="guardarOfertaTrabajo()">
                            💾 Guardar oferta laboral
                        </button>
                        <a href="/panel-trabajo?id=<?php echo $businessId; ?>" class="btn-save" style="display:inline-flex;text-decoration:none;width:auto;padding:10px 16px;background:#065f46;">
                            📋 Abrir panel de postulaciones
                        </a>
                    </div>
                <?php else: ?>
                    <p style="margin:0;font-size:.84em;color:#4b5563;line-height:1.6;">
                        Este módulo es <strong>opcional</strong>. Después de publicar el negocio, podrás activarlo y completar los datos desde
                        <strong>Editar negocio → Busco Empleados/as</strong>.
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <!-- ══ FIN MÓDULO BUSCO EMPLEADOS/AS ══════════════════════════════════ -->

        <!-- ══ MÓDULO PROVEEDOR "P" ══════════════════════════════════════════ -->
        <?php if ($puedeSerProveedor): ?>
        <div class="form-section">
            <div class="section-head">
                <span class="section-icon">📦</span>
                <div>
                    <div class="section-title">Designación Proveedor (P)</div>
                    <div class="section-desc">Al activarlo, tu negocio aparece en la <strong>Consulta Global Proveedores</strong> filtrada por rubro</div>
                </div>
            </div>
            <div class="section-body">
                <label id="proveedor-toggle-row" style="display:flex;align-items:flex-start;gap:12px;cursor:pointer;padding:10px 12px;border:1px solid #e5e7eb;border-radius:10px;background:#f8fafc;">
                    <input type="checkbox" id="proveedor-toggle" <?php echo $esProveedor ? 'checked' : ''; ?> style="margin-top:3px;">
                    <div>
                        <div id="proveedor-label" style="font-weight:700;color:#1f2937;"><?php echo $esProveedor ? '📦 Proveedor activo' : 'Proveedor inactivo'; ?></div>
                        <div style="font-size:.8em;color:#6b7280;">Solo para comercios e industrias. Al activar, otros usuarios pueden encontrarte en Consulta Global por tu rubro y enviarte consultas directas.</div>
                    </div>
                </label>
                <div id="proveedor-msg" style="margin:10px 0 0;font-size:.82em;"></div>
                <div style="margin-top:12px;">
                    <button type="button" id="proveedor-save-btn" class="btn-save" style="width:auto;padding:10px 16px;" onclick="guardarProveedor()">
                        💾 Guardar designación P
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <!-- ══ FIN MÓDULO PROVEEDOR "P" ══════════════════════════════════════ -->

        <!-- ══ MÓDULO INMUEBLES (solo inmobiliaria) ══════════════════════════════ -->
        <?php if ($editing && $selectedType === 'inmobiliaria'): ?>
        <div class="form-section" id="section-inmuebles">
            <div class="section-head">
                <span class="section-icon">🏘️</span>
                <div>
                    <div class="section-title">Inmuebles publicados</div>
                    <div class="section-desc">Publicá propiedades en venta o alquiler. Aparecen en el mapa al presionar CERCA.</div>
                </div>
            </div>
            <div class="section-body">
                <div id="inmuebles-list" style="margin-bottom:12px;"></div>
                <button type="button" class="btn-save" style="width:auto;padding:8px 14px;background:#16a34a;" onclick="abrirFormInmueble(null)">
                    ➕ Agregar inmueble
                </button>
                <div id="inmueble-form" style="display:none;margin-top:14px;padding:14px;border:1px solid #d1fae5;border-radius:10px;background:#f0fdf4;">
                    <input type="hidden" id="inm-id">
                    <!-- Fila 1: Tipo de inmueble -->
                    <div style="margin-bottom:12px;">
                        <label style="font-size:.82em;font-weight:600;display:block;margin-bottom:6px;">Tipo de inmueble *</label>
                        <div id="inm-tipo-btns" style="display:flex;flex-wrap:wrap;gap:6px;">
                            <label class="inm-tipo-btn" title="Casa"><input type="radio" name="inm_tipo" id="inm-tipo-casa" value="casa" checked>🏠 Casa</label>
                            <label class="inm-tipo-btn" title="Departamento"><input type="radio" name="inm_tipo" id="inm-tipo-departamento" value="departamento">🏢 Depto.</label>
                            <label class="inm-tipo-btn" title="Lote"><input type="radio" name="inm_tipo" id="inm-tipo-lote" value="lote">🌳 Lote</label>
                            <label class="inm-tipo-btn" title="Proyecto"><input type="radio" name="inm_tipo" id="inm-tipo-proyecto" value="proyecto">🏗️ Proyecto</label>
                            <label class="inm-tipo-btn" title="Local"><input type="radio" name="inm_tipo" id="inm-tipo-local" value="local">🏪 Local</label>
                            <label class="inm-tipo-btn" title="Oficina"><input type="radio" name="inm_tipo" id="inm-tipo-oficina" value="oficina">🖥️ Oficina</label>
                        </div>
                    </div>
                    <!-- Fila 2: Operación + Financiado -->
                    <div style="display:grid;grid-template-columns:1fr auto;gap:10px;margin-bottom:10px;align-items:end;">
                        <div>
                            <label style="font-size:.82em;font-weight:600;">Operación *</label>
                            <select id="inm-operacion" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;">
                                <option value="venta">🏠 Venta</option>
                                <option value="alquiler">🔑 Alquiler</option>
                            </select>
                        </div>
                        <div style="padding-bottom:4px;">
                            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:.82em;font-weight:600;white-space:nowrap;">
                                <input type="checkbox" id="inm-financiado" style="width:auto;">
                                💰 Acepta financiación
                            </label>
                        </div>
                    </div>
                    <!-- Fila 3: Precio + Moneda -->
                    <div style="display:grid;grid-template-columns:1fr auto;gap:10px;margin-bottom:10px;">
                        <div>
                            <label style="font-size:.82em;font-weight:600;">Precio</label>
                            <input type="number" id="inm-precio" min="0" step="0.01" placeholder="0.00" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;">
                        </div>
                        <div>
                            <label style="font-size:.82em;font-weight:600;">Moneda</label>
                            <select id="inm-moneda" style="padding:8px;border:1px solid #d1d5db;border-radius:6px;min-width:80px;">
                                <option value="ARS">🇦🇷 ARS</option>
                                <option value="USD">🇺🇸 USD</option>
                                <option value="EUR">🇪🇺 EUR</option>
                                <option value="UYU">🇺🇾 UYU</option>
                                <option value="BRL">🇧🇷 BRL</option>
                            </select>
                        </div>
                    </div>
                    <!-- Fila 4: Ambientes + Superficie -->
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                        <div>
                            <label style="font-size:.82em;font-weight:600;">Ambientes</label>
                            <input type="number" id="inm-ambientes" min="1" max="20" placeholder="Ej: 3" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;">
                        </div>
                        <div>
                            <label style="font-size:.82em;font-weight:600;">Superficie (m²)</label>
                            <input type="number" id="inm-superficie" min="1" step="0.01" placeholder="Ej: 65.00" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;">
                        </div>
                    </div>
                    <!-- Fila 5: Título -->
                    <div style="margin-bottom:10px;">
                        <label style="font-size:.82em;font-weight:600;">Título *</label>
                        <input type="text" id="inm-titulo" maxlength="255" placeholder="Ej: Departamento 2 ambientes en palermo" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;">
                    </div>
                    <!-- Fila 6: Descripción -->
                    <div style="margin-bottom:10px;">
                        <label style="font-size:.82em;font-weight:600;">Descripción</label>
                        <textarea id="inm-descripcion" rows="3" maxlength="2000" placeholder="Descripción del inmueble..." style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;resize:vertical;"></textarea>
                    </div>
                    <!-- Fila 7: Dirección -->
                    <div style="margin-bottom:10px;">
                        <label style="font-size:.82em;font-weight:600;">Dirección</label>
                        <input type="text" id="inm-direccion" maxlength="500" placeholder="Dirección del inmueble" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;">
                    </div>
                    <!-- Fila 7b: Ubicación en el mapa (mini-mapa para el inmueble) -->
                    <div style="margin-bottom:10px;">
                        <label style="font-size:.82em;font-weight:600;display:block;margin-bottom:6px;">
                            📍 Ubicación del inmueble en el mapa
                            <span style="font-weight:400;color:#6b7280;">(opcional — clic o buscar dirección)</span>
                        </label>
                        <div style="display:flex;gap:6px;margin-bottom:6px;">
                            <input type="text" id="inm-geo-search-input"
                                   placeholder="Buscar dirección del inmueble…" autocomplete="off"
                                   style="flex:1;padding:7px 10px;border:1.5px solid #d1d5db;border-radius:6px;font-size:.84em;">
                            <button type="button" id="inm-geo-search-btn"
                                    style="padding:7px 12px;background:#16a34a;color:white;border:none;border-radius:6px;font-size:.84em;font-weight:600;cursor:pointer;white-space:nowrap;">
                                🔍 Buscar
                            </button>
                        </div>
                        <div id="inm-geo-search-results" style="background:white;border:1px solid #d1fae5;border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,.1);display:none;max-height:180px;overflow-y:auto;margin-bottom:6px;"></div>
                        <div id="inm-map-picker"></div>
                        <p style="font-size:.76em;color:#6b7280;margin:4px 0 0;">Hacé clic en el mapa para fijar la ubicación exacta del inmueble. Esto es independiente de la ubicación de la inmobiliaria.</p>
                    </div>
                    <!-- Fila 8: Coordenadas -->
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                        <div>
                            <label style="font-size:.82em;font-weight:600;">Latitud</label>
                            <input type="number" id="inm-lat" step="any" placeholder="-34.6037" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;">
                        </div>
                        <div>
                            <label style="font-size:.82em;font-weight:600;">Longitud</label>
                            <input type="number" id="inm-lng" step="any" placeholder="-58.3816" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;">
                        </div>
                    </div>
                    <!-- Fila 9: Contacto -->
                    <div style="margin-bottom:12px;">
                        <label style="font-size:.82em;font-weight:600;">Contacto (teléfono o email)</label>
                        <input type="text" id="inm-contacto" maxlength="255" placeholder="Teléfono o email de contacto" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;">
                    </div>
                    <!-- Fila 10: Link externo del inmueble -->
                    <div style="margin-bottom:12px;">
                        <label for="inm-web-url" style="font-size:.82em;font-weight:600;">🔗 Link del inmueble</label>
                        <input type="url" id="inm-web-url" maxlength="500" placeholder="https://www.inmobiliaria.com/propiedad/123" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;">
                        <p style="font-size:.75em;color:#6b7280;margin:3px 0 0;">URL de la página del inmueble en la web de la inmobiliaria (opcional). Aparecerá como botón "Detalles" en el popup.</p>
                    </div>
                    <div id="inmueble-msg" style="margin-bottom:8px;font-size:.82em;"></div>
                    <div style="display:flex;gap:8px;">
                        <button type="button" class="btn-save" style="width:auto;padding:8px 14px;" onclick="guardarInmueble()">💾 Guardar</button>
                        <button type="button" style="padding:8px 14px;border:1px solid #d1d5db;border-radius:6px;background:white;cursor:pointer;" onclick="cerrarFormInmueble()">Cancelar</button>
                    </div>
                </div>

                <!-- Adjuntos (planos / proyecto de inversión) – se muestra al editar un inmueble -->
                <div id="inm-adjuntos-section" style="display:none;margin-top:16px;padding:14px;border:1px solid #bfdbfe;border-radius:10px;background:#eff6ff;">
                    <!-- Foto de portada del popup (max 120 KB) -->
                    <div style="margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid #bfdbfe;">
                        <div style="font-weight:700;font-size:.9em;margin-bottom:8px;color:#1e40af;">🖼️ Foto del popup (máx. 120 KB)</div>
                        <div id="inm-foto-preview" style="margin-bottom:8px;display:none;">
                            <img id="inm-foto-img" src="" alt="Foto del inmueble" style="max-width:100%;max-height:160px;border-radius:8px;border:1px solid #bfdbfe;object-fit:cover;">
                        </div>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
                            <div>
                                <label for="inm-foto-file" style="font-size:.78em;font-weight:600;">Archivo (JPG/PNG/WebP, máx 120 KB)</label>
                                <input type="file" id="inm-foto-file" accept=".jpg,.jpeg,.png,.webp" style="font-size:.82em;">
                            </div>
                            <button type="button" onclick="subirFotoInmueble()" style="padding:7px 14px;background:#16a34a;color:white;border:none;border-radius:6px;cursor:pointer;font-size:.85em;">📤 Subir foto</button>
                            <button type="button" id="inm-foto-del-btn" onclick="eliminarFotoInmueble()" style="display:none;padding:7px 14px;background:#dc2626;color:white;border:none;border-radius:6px;cursor:pointer;font-size:.85em;">🗑️ Eliminar</button>
                        </div>
                        <div id="inm-foto-msg" style="margin-top:6px;font-size:.78em;"></div>
                    </div>
                    <div style="font-weight:700;font-size:.9em;margin-bottom:8px;color:#1e40af;">📎 Adjuntos del inmueble</div>
                    <div id="inm-adjuntos-list" style="margin-bottom:10px;"></div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
                        <div>
                            <label style="font-size:.78em;font-weight:600;">Tipo</label>
                            <select id="adj-tipo" style="padding:6px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:.85em;">
                                <option value="plano">📐 Plano</option>
                                <option value="proyecto">📊 Proyecto de inversión</option>
                                <option value="foto">📷 Foto adicional</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:.78em;font-weight:600;">Nombre (opcional)</label>
                            <input type="text" id="adj-nombre" maxlength="255" placeholder="Ej: Planta baja" style="padding:6px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:.85em;width:180px;">
                        </div>
                        <div>
                            <label style="font-size:.78em;font-weight:600;">Archivo (JPG/PNG/PDF, máx 10MB)</label>
                            <input type="file" id="adj-file" accept=".jpg,.jpeg,.png,.webp,.pdf" style="font-size:.82em;">
                        </div>
                        <button type="button" onclick="subirAdjunto()" style="padding:7px 14px;background:#1d4ed8;color:white;border:none;border-radius:6px;cursor:pointer;font-size:.85em;">📤 Subir</button>
                    </div>
                    <div id="adj-msg" style="margin-top:6px;font-size:.78em;"></div>
                </div>

                <div id="inmuebles-msg" style="margin-top:8px;font-size:.82em;"></div>
            </div>
        </div>
        <style>
        .inm-tipo-btn {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 6px 11px; border: 1.5px solid #d1d5db; border-radius: 20px;
            cursor: pointer; font-size: .82em; background: white; transition: all .15s;
            user-select: none;
        }
        .inm-tipo-btn:has(input:checked) {
            border-color: #16a34a; background: #d1fae5; color: #065f46; font-weight: 700;
        }
        .inm-tipo-btn input[type=radio] { display: none; }
        </style>
        <?php endif; ?>
        <!-- ══ FIN MÓDULO INMUEBLES ══════════════════════════════════════════════ -->

        <!-- ══ MÓDULO ZONAS DE INFLUENCIA (solo inmobiliaria) ════════════════════ -->
        <?php
        $hasInfluenceZones = mapitaColumnExists(getDbConnection(), 'businesses', 'influence_zones');
        if ($editing && $selectedType === 'inmobiliaria' && $hasInfluenceZones):
        $currentInfluenceZones = htmlspecialchars($business['influence_zones'] ?? '', ENT_QUOTES, 'UTF-8');
        ?>
        <div class="form-section" id="section-zonas-influencia">
            <div class="section-head">
                <span class="section-icon">🗺️</span>
                <div>
                    <div class="section-title">Zonas de Influencia</div>
                    <div class="section-desc">Definí los barrios, zonas o áreas que atendés. Esto permite que usuarios te contacten directamente aunque no estés en su radio de cercanía.</div>
                </div>
            </div>
            <div class="section-body">
                <div style="margin-bottom:10px;">
                    <label style="font-size:.82em;font-weight:600;display:block;margin-bottom:4px;">Barrios / Zonas atendidas</label>
                    <textarea id="influence-zones-input" rows="3" maxlength="800"
                        placeholder="Ej: Palermo, Belgrano, Villa Urquiza, Recoleta, Nuñez (separados por coma)"
                        style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;resize:vertical;font-family:inherit;font-size:.9em;"
                    ><?php echo $currentInfluenceZones; ?></textarea>
                    <div style="font-size:.75em;color:#6b7280;margin-top:4px;">
                        Ingresá los barrios o zonas separados por coma. Los usuarios podrán encontrarte por zona incluso si están lejos.
                    </div>
                </div>
                <div id="influence-zones-msg" style="font-size:.82em;min-height:16px;"></div>
                <button type="button" id="influence-zones-save-btn" class="btn-save" style="width:auto;padding:10px 16px;" onclick="guardarZonasInfluencia()">
                    💾 Guardar zonas de influencia
                </button>
            </div>
        </div>
        <?php endif; ?>
        <!-- ══ FIN MÓDULO ZONAS DE INFLUENCIA ════════════════════════════════════ -->

        <!-- ══ MÓDULO OBRA DE ARTE ══════════════════════════════════════════════ -->
        <?php if ($editing && $selectedType === 'obra_de_arte'): ?>
        <div class="form-section" id="section-obra-arte">
            <div class="section-head">
                <span class="section-icon">🎭</span>
                <div>
                    <div class="section-title">Proyecto Artístico</div>
                    <div class="section-desc">Describí tu proyecto y los roles que buscás convocar.</div>
                </div>
            </div>
            <div class="section-body">
                <div style="margin-bottom:12px;">
                    <label style="font-size:.82em;font-weight:600;display:block;margin-bottom:4px;">Descripción del proyecto</label>
                    <textarea id="oda-descripcion" name="oda_descripcion_proyecto" rows="5" maxlength="5000"
                        placeholder="Describí tu proyecto artístico: de qué trata, cuándo y dónde será, qué buscás lograr..."
                        style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;resize:vertical;font-family:inherit;"
                    ><?php echo htmlspecialchars($business['oda_descripcion_proyecto'] ?? ''); ?></textarea>
                </div>
                <div style="margin-bottom:12px;">
                    <label style="font-size:.82em;font-weight:600;display:block;margin-bottom:4px;">Requisitos para ser parte</label>
                    <textarea id="oda-requisitos" name="oda_requisitos" rows="4" maxlength="3000"
                        placeholder="¿Qué perfil buscás? Experiencia, disponibilidad, herramientas necesarias..."
                        style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;resize:vertical;font-family:inherit;"
                    ><?php echo htmlspecialchars($business['oda_requisitos'] ?? ''); ?></textarea>
                </div>
                <div style="margin-bottom:12px;">
                    <label style="font-size:.82em;font-weight:600;display:block;margin-bottom:8px;">Roles que buscás convocar</label>
                    <?php
                    $odaRoles = json_decode($business['oda_roles_buscados'] ?? '[]', true) ?: [];
                    $artRoles = [
                        'musico'=>'🎸 Músico','cantante'=>'🎤 Cantante','bailarin'=>'💃 Bailarín/a',
                        'actor'=>'🎭 Actor','actriz'=>'🎭 Actriz','director_artistico'=>'🎬 Director/a artístico/a',
                        'guionista'=>'📝 Guionista','escenografo'=>'🖼️ Escenógrafo/a',
                        'fotografo_artistico'=>'📷 Fotógrafo/a artístico/a','productor_artistico'=>'🎙️ Productor/a artístico/a',
                        'maquillador'=>'💄 Maquillador/a','pintor'=>'🎨 Pintor/a','poeta'=>'📜 Poeta',
                        'musicalizador'=>'🎵 Musicalizador/a','editor_grafico'=>'🖥️ Editor/a gráfico/a',
                        'asistente_artistico'=>'🤝 Asistente artístico/a',
                    ];
                    ?>
                    <div style="display:flex;flex-wrap:wrap;gap:8px;">
                    <?php foreach ($artRoles as $roleKey => $roleLabel): ?>
                        <label style="display:flex;align-items:center;gap:5px;padding:5px 10px;border:1px solid #d1d5db;border-radius:20px;cursor:pointer;font-size:.82em;background:white;">
                            <input type="checkbox" name="oda_roles[]" value="<?php echo $roleKey; ?>"
                                <?php echo in_array($roleKey, $odaRoles, true) ? 'checked' : ''; ?>>
                            <?php echo $roleLabel; ?>
                        </label>
                    <?php endforeach; ?>
                    </div>
                </div>
                <div id="oda-msg" style="margin:8px 0;font-size:.82em;"></div>
                <button type="button" class="btn-save" style="width:auto;padding:10px 16px;" onclick="guardarObraArte()">
                    💾 Guardar datos del proyecto
                </button>
            </div>
        </div>
        <?php endif; ?>
        <!-- ══ FIN MÓDULO OBRA DE ARTE ══════════════════════════════════════════ -->

        <!-- ══ REDES SOCIALES ════════════════════════════════════════════════ -->        <div class="form-section">
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
                <div class="geo-search-wrap">
                    <input type="text" id="geo-search-input" placeholder="Buscar dirección (calle, número, localidad)…" autocomplete="off">
                    <button type="button" id="geo-search-btn">🔍 Buscar</button>
                </div>
                <div id="geo-search-results" class="geo-search-results"></div>
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
                <button type="button" class="btn-cancel" onclick="confirmarDuplicar()"
                        title="Clonar este negocio con los mismos datos (sin ubicación)">
                    📋 Duplicar
                </button>
            <?php else: ?>
                <a href="/" class="btn-cancel">← Cancelar</a>
                <button type="reset" class="btn-cancel" onclick="return confirm('¿Limpiar todos los datos?')">🗑️ Limpiar</button>
            <?php endif; ?>
            <button type="submit" class="btn-submit">
                <?php echo $editing ? '💾 Guardar cambios' : '🚀 Publicar negocio'; ?>
            </button>
        </div>

    </form>

    <!-- ══ PANEL AVANZADO — Asistencia Jurídica y Estratégica ════════════════ -->
    <?php
    $advBizType = '';
    if ($editing && !empty($business['business_type'])) {
        $bt = strtolower($business['business_type']);
        if (in_array($bt, ['comercio','tienda','kiosk','farmacia','supermercado','ferreteria','libreria','bazar','joyeria','optica','bicicleteria','jugueteria','muebleria','colchoneria','electrodomesticos','herramientas'], true)
            || preg_match('/comercio|tienda|kiosk|farmacia|supermercado|ferreteria|libreria|ropa|calzado|bazar|joyeria|optica/', $bt)) {
            $advBizType = 'comercio';
        } elseif (preg_match('/industria|fabrica|taller|manufactura|produccion|aserradero|metalurgica/', $bt)) {
            $advBizType = 'industria';
        } else {
            $advBizType = 'servicios';
        }
    }
    require_once __DIR__ . '/../includes/avanzado_panel.php';
    renderAvanzadoPanel('negocio', $advBizType);
    ?>

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

    <?php if ($isAdmin): ?>
    <!-- ══ PANEL ADMIN: Permisos especiales ═════════════════════════════════ -->
    <div class="form-section">
        <div class="section-head">
            <span class="section-icon">🔐</span>
            <div>
                <div class="section-title">Permisos Admin</div>
                <div class="section-desc">Configuración especial (solo visible para administradores)</div>
            </div>
        </div>
        <div class="section-body">
            <form method="post" id="admin-perms-form">
                <?php echo csrfField(); ?>
                <!-- Incluir todos los campos requeridos del formulario principal -->
                <input type="hidden" name="name"          value="<?php echo bv($business, 'name'); ?>">
                <input type="hidden" name="address"       value="<?php echo bv($business, 'address'); ?>">
                <input type="hidden" name="business_type" value="<?php echo bv($business, 'business_type'); ?>">
                <div class="field">
                    <label style="font-weight:600;">📋 Permiso de Encuestas</label>
                    <select name="encuestas_override"
                            style="padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;width:100%;max-width:360px;">
                        <option value="heredar"       <?php echo ($business['encuestas_override'] ?? 'heredar') === 'heredar'        ? 'selected' : ''; ?>>🔄 Heredar de la industria</option>
                        <option value="habilitada"    <?php echo ($business['encuestas_override'] ?? '') === 'habilitada'    ? 'selected' : ''; ?>>✅ Habilitada (override)</option>
                        <option value="deshabilitada" <?php echo ($business['encuestas_override'] ?? '') === 'deshabilitada' ? 'selected' : ''; ?>>❌ Deshabilitada (override)</option>
                    </select>
                    <small style="color:#6b7280;display:block;margin-top:4px;">
                        Si se hereda: el permiso viene de la industria. Override fuerza el valor sin importar la industria.
                    </small>
                </div>
                <div style="margin-top:12px;">
                    <button type="submit" class="btn-save" style="width:auto;padding:10px 18px;">Guardar permiso</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
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

// ── Duplicar negocio ─────────────────────────────────────────────────────────
function confirmarDuplicar() {
    if (!confirm('¿Duplicar este negocio?\n\nSe creará una copia con los mismos datos, pero SIN ubicación.\nDeberás asignar la nueva ubicación en el mapa antes de guardar.')) return;
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = window.location.href;
    var csrfEl = document.querySelector('#main-form [name="csrf_token"]');
    if (csrfEl) {
        var h = document.createElement('input'); h.type = 'hidden'; h.name = 'csrf_token'; h.value = csrfEl.value;
        form.appendChild(h);
    }
    var a = document.createElement('input'); a.type = 'hidden'; a.name = 'action'; a.value = 'duplicar';
    form.appendChild(a);
    document.body.appendChild(form);
    form.submit();
}

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

// ── Buscador de dirección (Nominatim) ─────────────────────────────────────────
var geoMarkerIcon = L.divIcon({
    html: '<div style="background:#1B3B6F;width:16px;height:16px;border-radius:50%;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,.4);"></div>',
    className: '', iconSize: [16,16], iconAnchor: [8,8]
});
initGeoSearch({
    map: mapa,
    getMarker: function() { return mapPin; },
    setMarker: function(m) {
        mapPin = m;
        m.setIcon(geoMarkerIcon);
        m.bindTooltip('📍 Ubicación seleccionada', {direction:'top', offset:[0,-10]}).openTooltip();
    },
    latInputId:    'lat',
    lngInputId:    'lng',
    searchInputId: 'geo-search-input',
    searchBtnId:   'geo-search-btn',
    resultsDivId:  'geo-search-results'
});

// ── Business type handler ─────────────────────────────────────────────────────
const subtypeHints = <?php echo json_encode($subtypeLabels); ?>;
const professionalLicensedTypes = <?php echo json_encode($professionalLicensedTypes); ?>;
const restrictedApprovalTypes   = <?php echo json_encode($restrictedApprovalTypes); ?>;
const descriptionPlaceholders   = <?php echo json_encode($descriptionPlaceholders); ?>;

function onTypeChange(val) {
    const sec   = document.getElementById('subtype-section');
    const hint  = document.getElementById('subtype-hint');
    const input = document.getElementById('tipo_comercio');
    const desc  = document.getElementById('description');
    const professionalNote = document.getElementById('professional-license-note');
    const restrictedNote   = document.getElementById('restricted-approval-note');
    sec.style.display = 'block';
    if (subtypeHints[val]) {
        hint.textContent  = '— ' + subtypeHints[val];
        input.placeholder = subtypeHints[val];
    } else {
        hint.textContent  = '(opcional)';
        input.placeholder = 'Especificá el rubro o especialidad…';
    }
    if (desc && !desc.value.trim()) {
        desc.placeholder = descriptionPlaceholders[val] || descriptionPlaceholders.default || '';
    }
    if (professionalNote) {
        professionalNote.style.display = professionalLicensedTypes.includes(val) ? 'block' : 'none';
    }
    if (restrictedNote) {
        restrictedNote.style.display = restrictedApprovalTypes.includes(val) ? 'block' : 'none';
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
    medico_pediatra: ['niños','controles','vacunación','crecimiento','consultorio','guardia'],
    medico_traumatologo: ['huesos','lesiones','rehabilitación','deporte','columna','yesos'],
    laboratorio: ['análisis clínicos','extracciones','sangre','orina','resultados online','turnos'],
    ingenieria_civil: ['obras civiles','estructuras','cálculo','dirección de obra','proyectos'],
    astrologo: ['carta natal','astrología','compatibilidad','tránsitos','orientación'],
    grafica: ['impresiones','cartelería','ploteo','diseño gráfico','folletería'],
    alquiler_mobiliario_fiestas: ['sillas','mesas','livings','carpas','mantelería','vajilla'],
    propalacion_musica: ['sonido','musicalización','audio','dj','eventos'],
    animacion_fiestas: ['animación infantil','show','juegos','eventos sociales','cumpleaños'],
    zapatero: ['arreglo de calzado','suelas','pegado','restauración','cuero'],
    gas_en_garrafa: ['garrafas','reparto','recarga','domicilio','urgencias'],
    videojuegos: ['consolas','gaming','reparación','alquiler','torneos'],
    seguridad: ['vigilancia','monitoreo','alarmas','cámaras','custodia'],
    electricista: ['instalaciones','tableros','cortocircuitos','emergencias','mantenimiento'],
    gasista: ['instalación de gas','matrícula','revisiones','fugas','emergencias'],
    maestro_particular: ['apoyo escolar','clases particulares','matemática','idiomas','nivel secundario'],
    asistencia_ancianos: ['acompañamiento','cuidados domiciliarios','higiene','medicación'],
    enfermeria: ['enfermería domiciliaria','curaciones','inyecciones','control de signos','postoperatorio'],
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

<?php if ($editing): ?>
// ── Módulo disponibles ──────────────────────────────────────────────────────────
function setDisponiblesVisual(active) {
    const row = document.getElementById('disp-toggle-row');
    const label = document.getElementById('disp-modulo-label');
    if (row) row.style.borderColor = active ? '#10b981' : '#e5e7eb';
    if (label) label.textContent = active ? 'Módulo activo' : 'Módulo inactivo';
}

async function refreshDisponiblesMeta() {
    const msg = document.getElementById('disp-panel-msg');
    if (!msg || !BIZ_ID) return;
    try {
        const r = await fetch('/api/disponibles.php?business_id=' + encodeURIComponent(BIZ_ID));
        const d = await r.json();
        if (d && d.success && d.data) {
            const pendientes = Number(d.data.ordenes_pendientes || 0);
            msg.textContent = pendientes > 0
                ? 'Tenés ' + pendientes + ' solicitud' + (pendientes === 1 ? '' : 'es') + ' pendiente' + (pendientes === 1 ? '' : 's') + '.'
                : 'No hay solicitudes pendientes por ahora.';
            setDisponiblesVisual(!!d.data.modulo_activo);
            const toggle = document.getElementById('disp-modulo-toggle');
            if (toggle) toggle.checked = !!d.data.modulo_activo;
        } else {
            msg.textContent = (d && d.message) ? d.message : 'No se pudo cargar el estado del módulo.';
        }
    } catch (e) {
        msg.textContent = 'No se pudo conectar con el módulo de disponibles.';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('disp-modulo-toggle');
    const msg = document.getElementById('disp-panel-msg');
    if (!toggle || !BIZ_ID) return;
    refreshDisponiblesMeta();
    toggle.addEventListener('change', async function() {
        const active = this.checked ? 1 : 0;
        setDisponiblesVisual(active);
        if (msg) msg.textContent = 'Guardando configuración...';
        try {
            const r = await fetch('/api/disponibles.php?action=toggle_modulo', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ business_id: BIZ_ID, activo: active })
            });
            const d = await r.json();
            if (!d || !d.success) {
                this.checked = !active;
                setDisponiblesVisual(!active);
                if (msg) msg.textContent = (d && d.message) ? d.message : 'No se pudo actualizar el módulo.';
                return;
            }
            if (msg) msg.textContent = d.message || 'Configuración actualizada.';
            refreshDisponiblesMeta();
        } catch (e) {
            this.checked = !active;
            setDisponiblesVisual(!active);
            if (msg) msg.textContent = 'No se pudo conectar para actualizar el módulo.';
        }
    });
});
<?php endif; ?>

// ── MÓDULO BUSCO EMPLEADOS/AS ─────────────────────────────────────────────────
<?php if ($editing): ?>
function jobOfferMsg(text, ok) {
    const el = document.getElementById('job-offer-msg');
    if (!el) return;
    if (!text) { el.style.display = 'none'; return; }
    el.style.display    = 'block';
    el.style.fontWeight = '600';
    el.style.color      = ok ? '#065f46' : '#991b1b';
    el.style.background = ok ? '#d1fae5' : '#fee2e2';
    el.style.padding    = '8px 12px';
    el.style.borderRadius = '8px';
    el.textContent = text;
    setTimeout(() => { el.style.display = 'none'; }, 4000);
}

async function guardarOfertaTrabajo() {
    const position    = (document.getElementById('job-position')?.value    || '').trim();
    const description = (document.getElementById('job-description')?.value || '').trim();
    const url         = (document.getElementById('job-url')?.value         || '').trim();
    const btn         = document.getElementById('job-save-btn');

    if (!position) { jobOfferMsg('El puesto/posición es obligatorio.', false); return; }

    if (btn) { btn.disabled = true; btn.textContent = '⏳ Guardando…'; }
    try {
        const r = await fetch('/api/job_offers.php?action=save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ business_id: BIZ_ID, job_offer_position: position, job_offer_description: description, job_offer_url: url })
        });
        const d = await r.json();
        if (d.success) {
            jobOfferMsg('✅ ' + (d.message || 'Oferta guardada'), true);
        } else {
            jobOfferMsg('❌ ' + (d.message || 'Error al guardar'), false);
        }
    } catch (e) {
        jobOfferMsg('Error de conexión al guardar la oferta.', false);
    } finally {
        if (btn) { btn.disabled = false; btn.textContent = '💾 Guardar oferta laboral'; }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('job-offer-toggle');
    if (!toggle || !BIZ_ID) return;
    toggle.addEventListener('change', async function() {
        const active = this.checked ? 1 : 0;
        const label  = document.getElementById('job-offer-label');
        // Al activar, exigir que se haya guardado la posición
        if (active) {
            const pos = (document.getElementById('job-position')?.value || '').trim();
            if (!pos) {
                this.checked = false;
                jobOfferMsg('Completá y guardá el puesto antes de activar la oferta.', false);
                return;
            }
        }
        try {
            const r = await fetch('/api/job_offers.php?action=toggle', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ business_id: BIZ_ID, activo: active })
            });
            const d = await r.json();
            if (d.success) {
                if (label) label.textContent = active ? 'Oferta activa' : 'Oferta inactiva';
                jobOfferMsg(d.message || (active ? 'Oferta activada' : 'Oferta desactivada'), true);
            } else {
                this.checked = !active;
                jobOfferMsg('❌ ' + (d.message || 'No se pudo cambiar el estado'), false);
            }
        } catch (e) {
            this.checked = !active;
            jobOfferMsg('Error de conexión.', false);
        }
    });
});
<?php endif; ?>

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

<?php if ($puedeSerProveedor): ?>
// ── Módulo Proveedor "P" ──────────────────────────────────────────────────────
document.getElementById('proveedor-toggle')?.addEventListener('change', function () {
    const label = document.getElementById('proveedor-label');
    if (label) label.textContent = this.checked ? '📦 Proveedor activo' : 'Proveedor inactivo';
});

async function guardarProveedor() {
    const toggle = document.getElementById('proveedor-toggle');
    const msgEl  = document.getElementById('proveedor-msg');
    const btn    = document.getElementById('proveedor-save-btn');
    if (!toggle) return;

    btn.disabled    = true;
    btn.textContent = '⏳ Guardando…';
    msgEl.textContent = '';

    try {
        const res = await fetch('/api/consultas.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                action:      'toggle_proveedor',
                business_id: BIZ_ID,
            }),
        });
        const data = await res.json();
        if (data.success) {
            // Sincronizar el checkbox con el nuevo estado real
            toggle.checked = data.data.es_proveedor === 1;
            const label = document.getElementById('proveedor-label');
            if (label) label.textContent = toggle.checked ? '📦 Proveedor activo' : 'Proveedor inactivo';
            msgEl.style.color   = '#065f46';
            msgEl.textContent   = '✅ ' + data.message;
        } else {
            msgEl.style.color   = '#c0392b';
            msgEl.textContent   = '❌ ' + (data.error || 'Error al guardar.');
        }
    } catch {
        msgEl.style.color   = '#c0392b';
        msgEl.textContent   = '❌ Error de red.';
    } finally {
        btn.disabled    = false;
        btn.textContent = '💾 Guardar designación P';
    }
}
<?php endif; ?>

// ── Web Help Popover ─────────────────────────────────────────────────────
function toggleWebHelp(id) {
    const pop = document.getElementById(id);
    if (!pop) return;
    const btn = pop.previousElementSibling;
    const isOpen = pop.classList.toggle('open');
    if (btn) btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    if (isOpen) {
        // Use fixed positioning to escape overflow:hidden parent containers
        const rect = btn.getBoundingClientRect();
        const popW = Math.min(340, window.innerWidth * 0.9);
        let left = rect.right - popW;
        if (left < 8) left = 8;
        pop.style.top   = (rect.bottom + 8) + 'px';
        pop.style.left  = left + 'px';
        pop.style.width = popW + 'px';
        const reposition = () => {
            const r = btn.getBoundingClientRect();
            pop.style.top = (r.bottom + 8) + 'px';
        };
        pop._reposition = reposition;
        window.addEventListener('scroll', reposition, { passive: true });
        // Close on outside click
        setTimeout(() => {
            document.addEventListener('click', function _close(e) {
                if (!pop.contains(e.target) && e.target !== btn) {
                    pop.classList.remove('open');
                    if (btn) btn.setAttribute('aria-expanded', 'false');
                    if (pop._reposition) {
                        window.removeEventListener('scroll', pop._reposition);
                        pop._reposition = null;
                    }
                }
                document.removeEventListener('click', _close);
            });
        }, 50);
    } else {
        if (pop._reposition) {
            window.removeEventListener('scroll', pop._reposition);
            pop._reposition = null;
        }
    }
}

// ── País → autocomplete de moneda, prefijo y hint de dirección ────────────
const COUNTRY_PROFILES = <?= json_encode(
    json_decode(file_get_contents(__DIR__ . '/../config/country_profiles.json'), true) ?? []
) ?>;

function onCountryChange(cc) {
    const profile = COUNTRY_PROFILES[cc] || null;
    const currInput  = document.getElementById('currency_code');
    const phoneInput = document.getElementById('phone_country_code');
    const fmtInput   = document.getElementById('address_format');
    const hintWrap   = document.getElementById('addr-hint-wrap');
    const hintDiv    = document.getElementById('addr-hint');

    if (!profile) {
        hintWrap.style.display = 'none';
        return;
    }

    // Auto-completar solo si el campo está vacío o tenía un valor anterior de país
    if (currInput && (!currInput.value || currInput.dataset.autofilled === '1')) {
        currInput.value = profile.currency_code || '';
        currInput.dataset.autofilled = '1';
    }
    if (phoneInput && (!phoneInput.value || phoneInput.dataset.autofilled === '1')) {
        phoneInput.value = profile.phone_code || '';
        phoneInput.dataset.autofilled = '1';
    }
    if (fmtInput) fmtInput.value = profile.address_format || '';

    // Mostrar hint de dirección
    if (profile.address_hint) {
        hintDiv.textContent = profile.address_hint;
        hintWrap.style.display = '';
    } else {
        hintWrap.style.display = 'none';
    }
}

// Disparar al cargar si ya hay un país seleccionado
(function() {
    const sel = document.getElementById('country_code');
    if (sel && sel.value) onCountryChange(sel.value);
})();

// ── MÓDULO INMUEBLES (inmobiliaria) ──────────────────────────────────────────
<?php if ($editing && $selectedType === 'inmobiliaria'): ?>
(function() {
    const BIZ = <?php echo (int)$businessId; ?>;
    const INM_TIPO_ICONS = {
        casa:'🏠', departamento:'🏢', lote:'🌳', proyecto:'🏗️', local:'🏪', oficina:'🖥️'
    };
    const INM_MONEDA_SYM = { ARS:'$', USD:'US$', EUR:'€', UYU:'$U', BRL:'R$' };

    function inmMsg(text, ok) {
        const el = document.getElementById('inmueble-msg');
        if (!el) return;
        el.textContent = text;
        el.style.color = ok ? '#065f46' : '#991b1b';
        el.style.background = ok ? '#d1fae5' : '#fee2e2';
        el.style.padding = '6px 10px';
        el.style.borderRadius = '6px';
        el.style.display = text ? 'block' : 'none';
    }
    function inmListMsg(text, ok) {
        const el = document.getElementById('inmuebles-msg');
        if (!el) return;
        el.textContent = text;
        el.style.color = ok ? '#065f46' : '#991b1b';
        el.style.display = text ? 'block' : 'none';
    }
    function adjMsg(text, ok) {
        const el = document.getElementById('adj-msg');
        if (!el) return;
        el.textContent = text;
        el.style.color = ok ? '#065f46' : '#991b1b';
        el.style.display = text ? 'block' : 'none';
    }

    let _currentInmId = null;

    window.abrirFormInmueble = function(id) {
        _currentInmId = id || null;
        document.getElementById('inm-id').value = id || '';
        document.getElementById('inm-titulo').value = '';
        document.getElementById('inm-operacion').value = 'venta';
        document.getElementById('inm-precio').value = '';
        document.getElementById('inm-moneda').value = 'ARS';
        document.getElementById('inm-descripcion').value = '';
        document.getElementById('inm-direccion').value = '';
        document.getElementById('inm-lat').value = '';
        document.getElementById('inm-lng').value = '';
        document.getElementById('inm-contacto').value = '';
        document.getElementById('inm-financiado').checked = false;
        document.getElementById('inm-ambientes').value = '';
        document.getElementById('inm-superficie').value = '';
        const webUrlEl = document.getElementById('inm-web-url');
        if (webUrlEl) webUrlEl.value = '';
        // Reset tipo
        const tipoR = document.getElementById('inm-tipo-casa');
        if (tipoR) tipoR.checked = true;
        inmMsg('', true);
        // Reset foto preview
        _setInmFotoPreview(null);

        // Adjuntos section
        const adjSec = document.getElementById('inm-adjuntos-section');
        if (adjSec) adjSec.style.display = id ? 'block' : 'none';

        if (id) {
            fetch('/api/inmuebles.php?id=' + id)
                .then(r => r.json()).then(d => {
                    if (d.success && d.data) {
                        const n = d.data;
                        document.getElementById('inm-id').value = n.id;
                        document.getElementById('inm-titulo').value = n.titulo || '';
                        document.getElementById('inm-operacion').value = n.operacion || 'venta';
                        document.getElementById('inm-precio').value = n.precio || '';
                        document.getElementById('inm-moneda').value = n.moneda || 'ARS';
                        document.getElementById('inm-descripcion').value = n.descripcion || '';
                        document.getElementById('inm-direccion').value = n.direccion || '';
                        document.getElementById('inm-lat').value = n.lat || '';
                        document.getElementById('inm-lng').value = n.lng || '';
                        document.getElementById('inm-contacto').value = n.contacto || '';
                        document.getElementById('inm-financiado').checked = !!parseInt(n.financiado, 10);
                        document.getElementById('inm-ambientes').value = n.ambientes || '';
                        document.getElementById('inm-superficie').value = n.superficie_m2 || '';
                        const webUrlEl2 = document.getElementById('inm-web-url');
                        if (webUrlEl2) webUrlEl2.value = n.web_url || '';
                        const tipoEl = document.getElementById('inm-tipo-' + (n.tipo || 'casa'));
                        if (tipoEl) tipoEl.checked = true;
                        // Foto de portada
                        _setInmFotoPreview(n.foto_url || null);
                        // Cargar adjuntos
                        renderAdjuntos(n.adjuntos || []);
                    }
                }).catch(() => {});
        } else {
            renderAdjuntos([]);
        }
        document.getElementById('inmueble-form').style.display = 'block';
    };
    // ── Mini-mapa para ubicación del inmueble ────────────────────────────────
    let _inmMap = null;
    let _inmMapMarker = null;

    var _inmMarkerIcon = null;
    function _getInmMarkerIcon() {
        if (!_inmMarkerIcon) {
            _inmMarkerIcon = L.divIcon({
                html: '<div style="background:#16a34a;width:14px;height:14px;border-radius:50%;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,.4);"></div>',
                className: '', iconSize: [14,14], iconAnchor: [7,7]
            });
        }
        return _inmMarkerIcon;
    }

    function _setInmMapMarker(lat, lng) {
        if (!_inmMap) return;
        if (_inmMapMarker) { _inmMap.removeLayer(_inmMapMarker); }
        _inmMapMarker = L.marker([lat, lng], { icon: _getInmMarkerIcon() }).addTo(_inmMap);
        _inmMap.setView([lat, lng], Math.max(_inmMap.getZoom(), 15));
        document.getElementById('inm-lat').value = parseFloat(lat).toFixed(7);
        document.getElementById('inm-lng').value = parseFloat(lng).toFixed(7);
    }

    function _initInmMap() {
        if (_inmMap) {
            // Ya inicializado; solo recalcular tamaño por si el div estuvo oculto
            _inmMap.invalidateSize();
            return;
        }
        const container = document.getElementById('inm-map-picker');
        if (!container) return;

        // Centro inicial: usar la ubicación del negocio o Argentina por defecto
        const bizLat = parseFloat(document.getElementById('lat')?.value) || -34.6037;
        const bizLng = parseFloat(document.getElementById('lng')?.value) || -58.3816;

        _inmMap = L.map(container, { zoomControl: true }).setView([bizLat, bizLng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(_inmMap);

        _inmMap.on('click', function(e) {
            _setInmMapMarker(e.latlng.lat, e.latlng.lng);
        });

        // Buscador de dirección para el inmueble
        initGeoSearch({
            map: _inmMap,
            getMarker: function() { return _inmMapMarker; },
            setMarker: function(m) {
                if (_inmMapMarker) { _inmMap.removeLayer(_inmMapMarker); }
                _inmMapMarker = m;
                m.setIcon(_getInmMarkerIcon());
            },
            latInputId:    'inm-lat',
            lngInputId:    'inm-lng',
            searchInputId: 'inm-geo-search-input',
            searchBtnId:   'inm-geo-search-btn',
            resultsDivId:  'inm-geo-search-results'
        });
    }

    // Sobreescribir abrirFormInmueble para inicializar/actualizar el mini-mapa
    const _origAbrirForm = window.abrirFormInmueble;
    window.abrirFormInmueble = function(id) {
        _origAbrirForm(id);
        // Inicializar el mapa una vez que el div sea visible
        requestAnimationFrame(function() {
            _initInmMap();
            if (id) {
                // Las coords se cargan async desde la API; esperar brevemente y luego actualizar el mapa
                setTimeout(function() {
                    var lat = document.getElementById('inm-lat').value;
                    var lng = document.getElementById('inm-lng').value;
                    if (lat && lng) {
                        _setInmMapMarker(parseFloat(lat), parseFloat(lng));
                    }
                }, 600);
            } else {
                // Formulario nuevo: limpiar marcador
                if (_inmMapMarker && _inmMap) {
                    _inmMap.removeLayer(_inmMapMarker);
                    _inmMapMarker = null;
                }
            }
        });
    };

    window.cerrarFormInmueble = function() {
        document.getElementById('inmueble-form').style.display = 'none';
        const adjSec = document.getElementById('inm-adjuntos-section');
        if (adjSec) adjSec.style.display = 'none';
        _currentInmId = null;
    };

    function fotoInmMsg(text, ok) {
        const el = document.getElementById('inm-foto-msg');
        if (!el) return;
        el.textContent = text;
        el.style.color = ok ? '#065f46' : '#991b1b';
        el.style.display = text ? 'block' : 'none';
    }

    function _setInmFotoPreview(url) {
        const preview = document.getElementById('inm-foto-preview');
        const img     = document.getElementById('inm-foto-img');
        const delBtn  = document.getElementById('inm-foto-del-btn');
        if (!preview || !img) return;
        if (url) {
            img.src = url;
            preview.style.display = 'block';
            if (delBtn) delBtn.style.display = 'inline-block';
        } else {
            img.src = '';
            preview.style.display = 'none';
            if (delBtn) delBtn.style.display = 'none';
        }
    }

    window.subirFotoInmueble = async function() {
        if (!_currentInmId) { fotoInmMsg('Guardá primero el inmueble para poder subir la foto.', false); return; }
        const fileInput = document.getElementById('inm-foto-file');
        if (!fileInput || !fileInput.files.length) { fotoInmMsg('Seleccioná un archivo.', false); return; }
        const file = fileInput.files[0];
        if (file.size > 120 * 1024) { fotoInmMsg('El archivo supera 120 KB. Reducí el tamaño de la imagen.', false); return; }
        const fd = new FormData();
        fd.append('inmueble_id', _currentInmId);
        fd.append('action', 'upload');
        fd.append('file', file);
        fotoInmMsg('Subiendo…', true);
        try {
            const r = await fetch('/api/upload_inmueble_foto.php', { method: 'POST', body: fd });
            const d = await r.json();
            if (d.success) {
                fotoInmMsg('✅ ' + (d.message || 'Foto subida'), true);
                _setInmFotoPreview(d.url);
                fileInput.value = '';
            } else {
                fotoInmMsg('❌ ' + (d.message || 'Error'), false);
            }
        } catch (e) { fotoInmMsg('Error de conexión.', false); }
    };

    window.eliminarFotoInmueble = async function() {
        if (!_currentInmId || !confirm('¿Eliminar la foto de portada?')) return;
        const fd = new FormData();
        fd.append('inmueble_id', _currentInmId);
        fd.append('action', 'delete');
        fotoInmMsg('Eliminando…', true);
        try {
            const r = await fetch('/api/upload_inmueble_foto.php', { method: 'POST', body: fd });
            const d = await r.json();
            if (d.success) { fotoInmMsg('✅ Eliminada', true); _setInmFotoPreview(null); }
            else fotoInmMsg('❌ ' + (d.message || 'Error'), false);
        } catch (e) { fotoInmMsg('Error de conexión.', false); }
    };
    window.guardarInmueble = async function() {
        const id      = document.getElementById('inm-id').value;
        const titulo  = document.getElementById('inm-titulo').value.trim();
        if (!titulo) { inmMsg('El título es obligatorio.', false); return; }
        const tipoSel = document.querySelector('input[name="inm_tipo"]:checked');
        const webUrlEl = document.getElementById('inm-web-url');
        const payload = {
            business_id:  BIZ,
            operacion:    document.getElementById('inm-operacion').value,
            titulo,
            descripcion:  document.getElementById('inm-descripcion').value.trim(),
            precio:       document.getElementById('inm-precio').value || null,
            moneda:       document.getElementById('inm-moneda').value || 'ARS',
            direccion:    document.getElementById('inm-direccion').value.trim(),
            lat:          document.getElementById('inm-lat').value || null,
            lng:          document.getElementById('inm-lng').value || null,
            contacto:     document.getElementById('inm-contacto').value.trim(),
            tipo:         tipoSel ? tipoSel.value : 'casa',
            financiado:   document.getElementById('inm-financiado').checked ? 1 : 0,
            ambientes:    document.getElementById('inm-ambientes').value || null,
            superficie_m2: document.getElementById('inm-superficie').value || null,
            web_url:      webUrlEl ? (webUrlEl.value.trim() || null) : null,
        };
        if (id) payload.id = parseInt(id, 10);
        try {
            const r = await fetch('/api/inmuebles.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const d = await r.json();
            if (d.success) {
                inmMsg('✅ ' + (d.message || 'Guardado'), true);
                // If new inmueble, update _currentInmId and show adjuntos section
                if (!id && d.data && d.data.id) {
                    _currentInmId = d.data.id;
                    document.getElementById('inm-id').value = d.data.id;
                    const adjSec = document.getElementById('inm-adjuntos-section');
                    if (adjSec) adjSec.style.display = 'block';
                }
                cargarInmuebles();
            } else {
                inmMsg('❌ ' + (d.message || 'Error'), false);
            }
        } catch (e) {
            inmMsg('Error de conexión.', false);
        }
    };
    window.eliminarInmueble = async function(id) {
        if (!confirm('¿Eliminar este inmueble?')) return;
        try {
            const r = await fetch('/api/inmuebles.php?id=' + id, { method: 'DELETE' });
            const d = await r.json();
            if (d.success) { inmListMsg('✅ Eliminado', true); cargarInmuebles(); }
            else inmListMsg('❌ ' + (d.message || 'Error'), false);
        } catch (e) { inmListMsg('Error de conexión.', false); }
    };
    window.cargarInmuebles = async function() {
        const cont = document.getElementById('inmuebles-list');
        if (!cont) return;
        try {
            const r = await fetch('/api/inmuebles.php?business_id=' + BIZ);
            const d = await r.json();
            if (!d.success || !d.data || !d.data.length) {
                cont.innerHTML = '<p style="font-size:.82em;color:#6b7280;">No hay inmuebles publicados aún.</p>';
                return;
            }
            cont.innerHTML = d.data.map(n => {
                const icon = INM_TIPO_ICONS[n.tipo] || '🏘️';
                const monSym = INM_MONEDA_SYM[n.moneda] || '$';
                const precioStr = n.precio ? ` — ${monSym}${Number(n.precio).toLocaleString()}` : '';
                const finBadge = parseInt(n.financiado, 10) ? ' <span style="background:#fef3c7;color:#92400e;padding:1px 5px;border-radius:10px;font-size:.75em;">💰 Financiado</span>' : '';
                const ambStr = n.ambientes ? ` · ${n.ambientes} amb.` : '';
                const supStr = n.superficie_m2 ? ` · ${n.superficie_m2}m²` : '';
                return `
                <div style="border:1px solid #d1d5db;border-radius:8px;padding:10px;margin-bottom:8px;background:white;">
                    <div style="font-weight:700;font-size:.9em;">${icon} ${n.titulo || 'Sin título'}${finBadge}</div>
                    <div style="font-size:.78em;color:#6b7280;">${n.operacion === 'alquiler' ? '🔑 Alquiler' : '🏠 Venta'}${precioStr}${ambStr}${supStr}</div>
                    <div style="margin-top:6px;display:flex;gap:6px;">
                        <button type="button" onclick="abrirFormInmueble(${n.id})" style="font-size:.78em;padding:4px 10px;border:1px solid #d1d5db;border-radius:4px;cursor:pointer;background:white;">✏️ Editar</button>
                        <button type="button" onclick="eliminarInmueble(${n.id})" style="font-size:.78em;padding:4px 10px;border:1px solid #fca5a5;border-radius:4px;cursor:pointer;background:#fff5f5;color:#991b1b;">🗑️ Eliminar</button>
                    </div>
                </div>`;
            }).join('');
        } catch (e) {
            cont.innerHTML = '<p style="font-size:.82em;color:#991b1b;">Error al cargar inmuebles.</p>';
        }
    };

    // ── Adjuntos ──────────────────────────────────────────────────────────────
    function renderAdjuntos(adjuntos) {
        const cont = document.getElementById('inm-adjuntos-list');
        if (!cont) return;
        const adjIcons = { plano:'📐', proyecto:'📊', foto:'📷' };
        if (!adjuntos || !adjuntos.length) {
            cont.innerHTML = '<p style="font-size:.78em;color:#6b7280;">Sin adjuntos aún.</p>';
            return;
        }
        cont.innerHTML = adjuntos.map(a => {
            const icon = adjIcons[a.tipo_adjunto] || '📎';
            const isPdf = (a.mime_type || '').includes('pdf');
            const preview = isPdf
                ? `<a href="${a.url}" target="_blank" style="font-size:.78em;color:#1d4ed8;">📄 Ver PDF</a>`
                : `<a href="${a.url}" target="_blank"><img src="${a.url}" style="height:40px;border-radius:4px;object-fit:cover;" alt="adjunto"></a>`;
            return `<div style="display:flex;align-items:center;gap:8px;padding:5px 0;border-bottom:1px solid #e5e7eb;font-size:.78em;">
                ${preview}
                <span>${icon} ${a.nombre || a.tipo_adjunto}</span>
                <button type="button" onclick="eliminarAdjunto(${a.id})" style="margin-left:auto;padding:2px 8px;border:1px solid #fca5a5;border-radius:4px;background:#fff5f5;color:#991b1b;cursor:pointer;font-size:.78em;">🗑️</button>
            </div>`;
        }).join('');
    }

    window.subirAdjunto = async function() {
        const inmId = document.getElementById('inm-id').value;
        if (!inmId) { adjMsg('Primero guardá el inmueble.', false); return; }
        const fileEl = document.getElementById('adj-file');
        if (!fileEl || !fileEl.files || !fileEl.files[0]) { adjMsg('Seleccioná un archivo.', false); return; }
        const fd = new FormData();
        fd.append('inmueble_id', inmId);
        fd.append('tipo_adjunto', document.getElementById('adj-tipo').value);
        fd.append('nombre', document.getElementById('adj-nombre').value.trim());
        fd.append('file', fileEl.files[0]);
        adjMsg('⏳ Subiendo...', true);
        try {
            const r = await fetch('/api/upload_inmueble_adjunto.php', { method: 'POST', body: fd });
            const d = await r.json();
            if (d.success) {
                adjMsg('✅ Adjunto subido', true);
                fileEl.value = '';
                document.getElementById('adj-nombre').value = '';
                await reloadAdjuntos(inmId);
            } else {
                adjMsg('❌ ' + (d.message || 'Error'), false);
            }
        } catch (e) { adjMsg('Error de conexión.', false); }
    };

    window.eliminarAdjunto = async function(adjId) {
        if (!confirm('¿Eliminar este adjunto?')) return;
        const inmId = document.getElementById('inm-id').value;
        try {
            const r = await fetch('/api/upload_inmueble_adjunto.php?id=' + adjId, { method: 'DELETE' });
            const d = await r.json();
            if (d.success) {
                adjMsg('✅ Eliminado', true);
                if (inmId) await reloadAdjuntos(inmId);
            } else {
                adjMsg('❌ ' + (d.message || 'Error'), false);
            }
        } catch (e) { adjMsg('Error.', false); }
    };

    async function reloadAdjuntos(inmId) {
        try {
            const rr = await fetch('/api/inmuebles.php?id=' + inmId);
            const dd = await rr.json();
            if (dd.success && dd.data) renderAdjuntos(dd.data.adjuntos || []);
        } catch (_) { /* silent */ }
    }

    document.addEventListener('DOMContentLoaded', cargarInmuebles);
})();
<?php endif; ?>

// ── MÓDULO ZONAS DE INFLUENCIA (inmobiliaria) ─────────────────────────────────
<?php
$_hasInfluenceZonesJs = function_exists('mapitaColumnExists') && mapitaColumnExists(getDbConnection(), 'businesses', 'influence_zones');
if ($editing && $selectedType === 'inmobiliaria' && $_hasInfluenceZonesJs):
?>
async function guardarZonasInfluencia() {
    const input  = document.getElementById('influence-zones-input');
    const msgEl  = document.getElementById('influence-zones-msg');
    const btn    = document.getElementById('influence-zones-save-btn');
    if (!input) return;

    btn.disabled    = true;
    btn.textContent = '⏳ Guardando…';
    if (msgEl) msgEl.textContent = '';

    try {
        const res = await fetch('/api/businesses.php?id=<?php echo (int)$businessId; ?>', {
            method:  'PUT',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ influence_zones: input.value.trim() }),
        });
        const data = await res.json();
        if (data.success) {
            if (msgEl) { msgEl.style.color = '#065f46'; msgEl.textContent = '✅ Zonas de influencia guardadas.'; }
        } else {
            if (msgEl) { msgEl.style.color = '#c0392b'; msgEl.textContent = '❌ ' + (data.error || 'Error al guardar.'); }
        }
    } catch {
        if (msgEl) { msgEl.style.color = '#c0392b'; msgEl.textContent = '❌ Error de red.'; }
    } finally {
        btn.disabled    = false;
        btn.textContent = '💾 Guardar zonas de influencia';
    }
}
<?php endif; ?>

// ── MÓDULO OBRA DE ARTE ──────────────────────────────────────────────────────
<?php if ($editing && $selectedType === 'obra_de_arte'): ?>
window.guardarObraArte = async function() {
    const desc = document.getElementById('oda-descripcion')?.value?.trim() || '';
    const req  = document.getElementById('oda-requisitos')?.value?.trim()  || '';
    const checkboxes = document.querySelectorAll('input[name="oda_roles[]"]:checked');
    const roles = Array.from(checkboxes).map(c => c.value);
    const msg = document.getElementById('oda-msg');
    const btn = document.querySelector('[onclick="guardarObraArte()"]');
    if (btn) { btn.disabled = true; btn.textContent = '⏳ Guardando…'; }
    try {
        const r = await fetch('/api/convocatorias.php?action=save_proyecto', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ business_id: <?php echo (int)$businessId; ?>, oda_descripcion_proyecto: desc, oda_requisitos: req, oda_roles_buscados: roles })
        });
        const d = await r.json();
        if (msg) {
            msg.textContent = d.success ? ('✅ ' + (d.message || 'Guardado')) : ('❌ ' + (d.message || 'Error'));
            msg.style.color = d.success ? '#065f46' : '#991b1b';
            msg.style.background = d.success ? '#d1fae5' : '#fee2e2';
            msg.style.padding = '8px 12px';
            msg.style.borderRadius = '6px';
            msg.style.display = 'block';
            setTimeout(() => { if (msg) msg.style.display = 'none'; }, 4000);
        }
    } catch (e) {
        if (msg) { msg.textContent = 'Error de conexión.'; msg.style.display = 'block'; }
    } finally {
        if (btn) { btn.disabled = false; btn.textContent = '💾 Guardar datos del proyecto'; }
    }
};
<?php endif; ?>
</script>
</body>
</html>
