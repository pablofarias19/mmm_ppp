<?php
/**
 * MICROWEB - Detalle Profesional de Negocio
 * Página de presentación con galería, historia, proyectos y contacto
 */
session_start();
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../includes/db_helper.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

$businessId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($businessId <= 0) {
    header("Location: /");
    exit();
}

$db = getDbConnection();
$stmt = $db->prepare("SELECT * FROM businesses WHERE id = ? AND visible = 1");
$stmt->execute([$businessId]);
$business = $stmt->fetch();

// Admin/owner puede ver negocios ocultos
if (!$business) {
    if (isset($_SESSION['user_id'])) {
        $stmt2 = $db->prepare("SELECT * FROM businesses WHERE id = ?");
        $stmt2->execute([$businessId]);
        $business = $stmt2->fetch();
        if ($business && (int)$business['user_id'] !== (int)$_SESSION['user_id'] && empty($_SESSION['is_admin'])) {
            $business = null;
        }
    }
}

if (!$business) {
    http_response_code(404);
    die("Negocio no encontrado");
}

// Obtener fotos del negocio
$photosStmt = $db->prepare("SELECT * FROM attachments WHERE business_id = ? AND type = 'photo' ORDER BY uploaded_at DESC LIMIT 12");
$photosStmt->execute([$businessId]);
$photos = $photosStmt->fetchAll();

// Iconos por tipo
$typeIcons = [
    'comercio' => '🛍️',
    'hotel' => '🏨',
    'restaurante' => '🍽️',
    'inmobiliaria' => '🏠',
    'farmacia' => '💊',
    'gimnasio' => '💪',
    'cafeteria' => '☕',
    'academia' => '📚',
    'bar' => '🍺',
    'medico_pediatra' => '🧒',
    'medico_traumatologo' => '🦴',
    'laboratorio' => '🧪',
    'electricista' => '💡',
    'gasista' => '🔥',
    'enfermeria' => '🩺',
    'asistencia_ancianos' => '🧓',
    'videojuegos' => '🎮',
];
$icon = $typeIcons[$business['business_type']] ?? '📍';

// Determinar si está abierto (simulado)
$isOpen = true;
$statusColor = $isOpen ? '#2ecc71' : '#e74c3c';
$statusText = $isOpen ? 'Abierto' : 'Cerrado';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($business['name']); ?> - Mapita</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/favicon.svg">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5568d3;
            --success: #2ecc71;
            --danger: #e74c3c;
            --light-gray: #f5f6fa;
            --charcoal: #2c3e50;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background: #fafbfc;
            color: #374151;
        }

        /* HEADER HERO */
        .hero {
            position: relative;
            height: 400px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: flex-end;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            padding: 60px 40px 40px;
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
        }

        .hero-title {
            display: flex;
            align-items: center;
            gap: 20px;
            margin: 0;
            font-size: 42px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .hero-icon {
            font-size: 60px;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,0.2));
        }

        .hero-meta {
            display: flex;
            gap: 20px;
            margin-top: 16px;
            font-size: 15px;
            opacity: 0.95;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            background: rgba(255,255,255,0.2);
            border-radius: 20px;
            font-weight: 600;
            backdrop-filter: blur(10px);
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: <?php echo $statusColor; ?>;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }

        /* CONTAINER */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
        }

        /* SECTION LAYOUT */
        .section {
            padding: 60px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .section:last-child {
            border-bottom: none;
        }

        .section-title {
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 30px 0;
            padding-bottom: 12px;
            border-bottom: 3px solid #667eea;
            display: inline-block;
            color: #1f2937;
        }

        /* GALERÍA DE FOTOS */
        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 16px;
            margin-top: 30px;
        }

        .gallery-item {
            position: relative;
            aspect-ratio: 4/3;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            background: #e5e7eb;
        }

        .gallery-item:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 28px rgba(0,0,0,0.2);
        }

        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .gallery-item:hover img {
            transform: scale(1.05);
        }

        .gallery-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
        }

        /* TARJETA DE INFO */
        .info-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-top: 30px;
        }

        .info-item {
            display: flex;
            gap: 12px;
            padding: 16px;
            background: #f9fafb;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .info-item-icon {
            font-size: 24px;
            flex-shrink: 0;
        }

        .info-item-content {
            flex: 1;
        }

        .info-item-label {
            font-size: 11px;
            font-weight: 700;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .info-item-value {
            color: #1f2937;
            font-weight: 500;
            word-break: break-word;
        }

        .info-item-value a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .info-item-value a:hover {
            text-decoration: underline;
        }

        /* DESCRIPCIÓN */
        .description {
            font-size: 16px;
            line-height: 1.8;
            color: #374151;
            margin-top: 20px;
        }

        /* BOTONES */
        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            background: #f0f1f8;
        }

        /* MAPA UBICACIÓN */
        .map-container {
            margin-top: 30px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            height: 400px;
        }

        #map {
            width: 100%;
            height: 100%;
        }

        /* FOOTER */
        .footer {
            text-align: center;
            padding: 40px 0;
            color: #999;
            font-size: 14px;
        }

        .footer a {
            color: #667eea;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        /* RESPONSIVO */
        @media (max-width: 768px) {
            .hero {
                height: 280px;
            }

            .hero-title {
                font-size: 28px;
                gap: 12px;
            }

            .hero-icon {
                font-size: 40px;
            }

            .hero-content {
                padding: 40px 20px 30px;
            }

            .container {
                padding: 0 20px;
            }

            .section {
                padding: 40px 0;
            }

            .section-title {
                font-size: 22px;
            }

            .gallery {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 12px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .button-group {
                flex-direction: column;
            }

            .btn {
                justify-content: center;
                width: 100%;
            }
        }
    </style>
</head>
<body>

<!-- HERO SECTION -->
<div class="hero">
    <div class="hero-content">
        <h1 class="hero-title">
            <span class="hero-icon"><?php echo $icon; ?></span>
            <span><?php echo htmlspecialchars($business['name']); ?></span>
        </h1>
        <div class="hero-meta">
            <div class="status-badge">
                <span class="status-dot"></span>
                <?php echo $statusText; ?>
            </div>
            <div><?php echo htmlspecialchars($business['business_type']); ?></div>
            <?php if ($business['price_range']): ?>
                <div><?php echo str_repeat('$', intval($business['price_range'])); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="container">

    <!-- GALERÍA -->
    <?php if (!empty($photos)): ?>
    <section class="section">
        <h2 class="section-title">📸 Galería</h2>
        <div class="gallery">
            <?php foreach ($photos as $photo): ?>
                <div class="gallery-item">
                    <img src="<?php echo htmlspecialchars($photo['file_path']); ?>" alt="<?php echo htmlspecialchars($business['name']); ?>">
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- INFORMACIÓN PRINCIPAL -->
    <section class="section">
        <h2 class="section-title">ℹ️ Información</h2>

        <div class="info-grid">
            <?php if (!empty($business['address'])): ?>
            <div class="info-item">
                <div class="info-item-icon">📍</div>
                <div class="info-item-content">
                    <div class="info-item-label">Dirección</div>
                    <div class="info-item-value"><?php echo htmlspecialchars($business['address']); ?></div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($business['phone'])): ?>
            <div class="info-item">
                <div class="info-item-icon">📞</div>
                <div class="info-item-content">
                    <div class="info-item-label">Teléfono</div>
                    <div class="info-item-value"><a href="tel:<?php echo htmlspecialchars($business['phone']); ?>"><?php echo htmlspecialchars($business['phone']); ?></a></div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($business['email'])): ?>
            <div class="info-item">
                <div class="info-item-icon">📧</div>
                <div class="info-item-content">
                    <div class="info-item-label">Email</div>
                    <div class="info-item-value"><a href="mailto:<?php echo htmlspecialchars($business['email']); ?>"><?php echo htmlspecialchars($business['email']); ?></a></div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($business['website'])): ?>
            <div class="info-item">
                <div class="info-item-icon">🌐</div>
                <div class="info-item-content">
                    <div class="info-item-label">Sitio Web</div>
                    <div class="info-item-value"><a href="<?php echo htmlspecialchars($business['website']); ?>" target="_blank">Visitar sitio</a></div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($business['description'])): ?>
        <div class="description">
            <p><?php echo nl2br(htmlspecialchars($business['description'])); ?></p>
        </div>
        <?php endif; ?>
    </section>

    <!-- MAPA -->
    <?php if (!empty($business['lat']) && !empty($business['lng'])): ?>
    <section class="section">
        <h2 class="section-title">🗺️ Ubicación</h2>
        <div class="map-container">
            <div id="map"></div>
        </div>
    </section>
    <?php endif; ?>

    <!-- BOTONES DE ACCIÓN -->
    <section class="section">
        <div class="button-group">
            <a href="/" class="btn btn-secondary">← Volver al mapa</a>
            <?php if (isset($_SESSION['user_id']) && (int)$business['user_id'] === (int)$_SESSION['user_id']): ?>
                <a href="/edit?id=<?php echo $business['id']; ?>" class="btn btn-primary">✏️ Editar</a>
            <?php endif; ?>
        </div>
    </section>

</div>

<footer class="footer">
    <p>&copy; 2026 Mapita - Mapa de Negocios y Marcas | <a href="/">Volver al inicio</a></p>
</footer>

<script>
<?php if (!empty($business['lat']) && !empty($business['lng'])): ?>
    const map = L.map('map').setView([<?php echo $business['lat']; ?>, <?php echo $business['lng']; ?>], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);

    L.marker([<?php echo $business['lat']; ?>, <?php echo $business['lng']; ?>], {
        icon: L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        })
    }).addTo(map).bindPopup('<strong><?php echo htmlspecialchars($business['name']); ?></strong>');
<?php endif; ?>
</script>

</body>
</html>
