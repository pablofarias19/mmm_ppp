<?php
/**
 * MICROWEB - Detalle Profesional de Marca
 * Página de presentación con historia, proyectos, novedades y enlaces
 */
session_start();
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../includes/db_helper.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

$brandId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($brandId <= 0) {
    header("Location: /marcas");
    exit();
}

$db = getDbConnection();
$stmt = $db->prepare("SELECT * FROM brands WHERE id = ? AND visible = 1");
$stmt->execute([$brandId]);
$brand = $stmt->fetch();

// Admin/owner puede ver marcas ocultas
if (!$brand) {
    $stmt2 = $db->prepare("SELECT * FROM brands WHERE id = ?");
    $stmt2->execute([$brandId]);
    $brand = $stmt2->fetch();
}

if (!$brand) {
    http_response_code(404);
    die("Marca no encontrada");
}

// ¿Puede editar? propietario o admin
$canEdit = isset($_SESSION['user_id']) && (
    (int)($brand['user_id'] ?? 0) === (int)$_SESSION['user_id'] ||
    !empty($_SESSION['is_admin'])
);

// Obtener fotos/logo
$photosStmt = $db->prepare("SELECT * FROM attachments WHERE brand_id = ? AND type IN ('photo', 'logo') ORDER BY uploaded_at DESC LIMIT 12");
$photosStmt->execute([$brandId]);
$photos = $photosStmt->fetchAll();

// Iconos por rubro
$rubros = [
    'Ropa Deportiva' => '👕',
    'Bebidas' => '🥤',
    'Farmacia' => '💊',
    'Tecnología' => '💻',
    'Alimentos' => '🍔',
    'Moda' => '👗',
    'Electrónica' => '📱',
];
$icon = $rubros[$brand['rubro']] ?? '🏷️';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($brand['nombre']); ?> - Mapita</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/favicon.svg">
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5568d3;
            --secondary: #00bfa5;
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
            height: 380px;
            background: linear-gradient(135deg, #667eea 0%, #00bfa5 100%);
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
            background: radial-gradient(circle at 80% 20%, rgba(255,255,255,0.15) 0%, transparent 60%);
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
            font-size: 48px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .hero-icon {
            font-size: 70px;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,0.2));
        }

        .hero-meta {
            display: flex;
            gap: 24px;
            margin-top: 20px;
            font-size: 15px;
            opacity: 0.95;
        }

        .meta-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(255,255,255,0.15);
            border-radius: 20px;
            font-weight: 600;
            backdrop-filter: blur(10px);
        }

        /* CONTAINER */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
        }

        /* SECTION */
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

        /* GALERÍA */
        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .gallery-item {
            position: relative;
            aspect-ratio: 1;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            transform: scale(1.08);
        }

        .gallery-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 64px;
        }

        /* TARJETA DE INFO */
        .info-card {
            background: white;
            border-radius: 12px;
            padding: 28px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            border-top: 4px solid #667eea;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            padding: 18px;
            background: #f9fafb;
            border-radius: 8px;
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }

        .info-item:hover {
            background: #f0f1f8;
            transform: translateX(4px);
        }

        .info-item-icon {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .info-item-label {
            font-size: 11px;
            font-weight: 700;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .info-item-value {
            color: #1f2937;
            font-weight: 500;
            font-size: 15px;
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

        /* HISTORIA */
        .history-box {
            background: white;
            border-radius: 12px;
            padding: 28px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-top: 30px;
            border-left: 4px solid #00bfa5;
        }

        .history-text {
            font-size: 16px;
            line-height: 1.8;
            color: #374151;
        }

        /* ENLACES/LINKS */
        .links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-top: 30px;
        }

        .link-card {
            background: white;
            border-radius: 10px;
            padding: 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            border: 2px solid transparent;
        }

        .link-card:hover {
            border-color: #667eea;
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.15);
        }

        .link-card-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .link-card-title {
            font-weight: 600;
            color: #1f2937;
            margin: 8px 0;
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

        .btn-edit {
            background: #0ea5e9;
            color: white;
            border: 2px solid #0ea5e9;
        }

        .btn-edit:hover {
            background: #0284c7;
            border-color: #0284c7;
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

        /* RESPONSIVO */
        @media (max-width: 768px) {
            .hero {
                height: 300px;
            }

            .hero-title {
                font-size: 32px;
                gap: 12px;
            }

            .hero-icon {
                font-size: 48px;
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

            .links-grid {
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
            <span><?php echo htmlspecialchars($brand['nombre']); ?></span>
        </h1>
        <div class="hero-meta">
            <span class="meta-tag">
                📋 <?php echo htmlspecialchars($brand['rubro']); ?>
            </span>
            <span class="meta-tag">
                📍 <?php echo htmlspecialchars($brand['ubicacion'] ?? 'Argentina'); ?>
            </span>
            <span class="meta-tag">
                ✅ <?php echo htmlspecialchars($brand['estado'] ?? 'Activa'); ?>
            </span>
        </div>
    </div>
</div>

<div class="container">

    <!-- GALERÍA -->
    <?php if (!empty($photos)): ?>
    <section class="section">
        <h2 class="section-title">🖼️ Galería</h2>
        <div class="gallery">
            <?php foreach ($photos as $photo): ?>
                <div class="gallery-item">
                    <img src="<?php echo htmlspecialchars($photo['file_path']); ?>" alt="<?php echo htmlspecialchars($brand['nombre']); ?>">
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php else: ?>
    <section class="section">
        <h2 class="section-title">🖼️ Galería</h2>
        <div class="gallery">
            <div class="gallery-item">
                <div class="gallery-placeholder"><?php echo $icon; ?></div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- INFORMACIÓN PRINCIPAL -->
    <section class="section">
        <h2 class="section-title">ℹ️ Información de la Marca</h2>

        <div class="info-card">
            <h3 style="margin: 0 0 12px 0; color: #1f2937; font-size: 18px;">Detalles Generales</h3>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-item-icon">🏷️</div>
                    <div class="info-item-label">Nombre Comercial</div>
                    <div class="info-item-value"><?php echo htmlspecialchars($brand['nombre']); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-item-icon">📋</div>
                    <div class="info-item-label">Rubro/Sector</div>
                    <div class="info-item-value"><?php echo htmlspecialchars($brand['rubro']); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-item-icon">📍</div>
                    <div class="info-item-label">Ubicación Geográfica</div>
                    <div class="info-item-value"><?php echo htmlspecialchars($brand['ubicacion'] ?? 'No especificada'); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-item-icon">✅</div>
                    <div class="info-item-label">Estado</div>
                    <div class="info-item-value"><?php echo htmlspecialchars($brand['estado'] ?? 'Activa'); ?></div>
                </div>
            </div>
        </div>
    </section>

    <!-- HISTORIA DE LA MARCA -->
    <section class="section">
        <h2 class="section-title">📖 Historia</h2>
        <div class="history-box">
            <div class="history-text">
                <p><strong><?php echo htmlspecialchars($brand['nombre']); ?></strong> es una marca líder en el sector de <strong><?php echo htmlspecialchars($brand['rubro']); ?></strong> con presencia en <?php echo htmlspecialchars($brand['ubicacion'] ?? 'Argentina'); ?>.</p>
                <p>Nos comprometemos a ofrecer productos y servicios de la más alta calidad, innovación y excelencia en la atención al cliente.</p>
                <p><em>Edita esta sección en tu panel de administración para agregar la historia completa de tu marca, hitos importantes, misión y visión.</em></p>
            </div>
        </div>
    </section>

    <!-- PROYECTOS Y NOVEDADES -->
    <section class="section">
        <h2 class="section-title">🚀 Proyectos y Novedades</h2>
        <div class="links-grid">
            <div class="link-card">
                <div class="link-card-icon">🎯</div>
                <div class="link-card-title">Último Proyecto</div>
                <p style="margin: 8px 0 0 0; color: #666; font-size: 13px;">Haz clic para configurar</p>
            </div>
            <div class="link-card">
                <div class="link-card-icon">📰</div>
                <div class="link-card-title">Últimas Novedades</div>
                <p style="margin: 8px 0 0 0; color: #666; font-size: 13px;">Haz clic para configurar</p>
            </div>
            <div class="link-card">
                <div class="link-card-icon">🌟</div>
                <div class="link-card-title">Destacados</div>
                <p style="margin: 8px 0 0 0; color: #666; font-size: 13px;">Haz clic para configurar</p>
            </div>
        </div>
    </section>

    <!-- ENLACES IMPORTANTES -->
    <section class="section">
        <h2 class="section-title">🔗 Enlaces Importantes</h2>
        <div class="info-card">
            <p style="color: #666; margin-top: 0;">Agrega aquí enlaces a tus plataformas digitales, redes sociales y sitios web de interés.</p>
            <div class="links-grid" style="margin-top: 20px;">
                <a href="#" class="link-card">
                    <div class="link-card-icon">🌐</div>
                    <div class="link-card-title">Sitio Web</div>
                </a>
                <a href="#" class="link-card">
                    <div class="link-card-icon">📱</div>
                    <div class="link-card-title">Instagram</div>
                </a>
                <a href="#" class="link-card">
                    <div class="link-card-icon">👍</div>
                    <div class="link-card-title">Facebook</div>
                </a>
                <a href="#" class="link-card">
                    <div class="link-card-icon">🎵</div>
                    <div class="link-card-title">TikTok</div>
                </a>
            </div>
        </div>
    </section>

    <!-- BOTONES DE ACCIÓN -->
    <section class="section">
        <div class="button-group">
            <a href="/" class="btn btn-secondary">← Volver al mapa</a>
            <a href="/marcas" class="btn btn-secondary">🗺️ Ver todas las marcas</a>
            <?php if ($canEdit): ?>
                <a href="/brand_edit?id=<?= $brandId ?>" class="btn btn-edit">✏️ Editar marca</a>
            <?php endif; ?>
        </div>
    </section>

</div>

<footer class="footer">
    <p>&copy; 2026 Mapita - Mapa de Negocios y Marcas | <a href="/">Volver al inicio</a></p>
</footer>

</body>
</html>
