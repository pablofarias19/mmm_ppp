<?php
/**
 * Agregar Nuevo Negocio - Formulario Mejorado
 */
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit();
}

require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../includes/db_helper.php';

setSecurityHeaders();

$message     = '';
$messageType = '';

$currentUser     = $_SESSION['user_name'];
$currentDateTime = date('Y-m-d H:i:s');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verifyCsrfToken();
    require_once __DIR__ . '/../../business/process_business.php';

    $result = addBusiness($_POST, $_SESSION['user_id']);

    if ($result['success']) {
        $businessId = $result['business_id'] ?? null;

        // Procesar fotos si se cargaron
        if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
            $uploadDir = __DIR__ . '/../../uploads/businesses/' . $businessId . '/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
            $maxFileSize = 2 * 1024 * 1024; // 2MB

            foreach ($_FILES['photos']['tmp_name'] as $key => $tmpFile) {
                if (!$tmpFile) continue;

                $fileExt = strtolower(pathinfo($_FILES['photos']['name'][$key], PATHINFO_EXTENSION));
                if (!in_array($fileExt, $allowedExts) || $_FILES['photos']['size'][$key] > $maxFileSize) {
                    continue;
                }

                $filename = uniqid('photo_') . '.' . $fileExt;
                $filepath = $uploadDir . $filename;
                move_uploaded_file($tmpFile, $filepath);

                // Guardar en tabla attachments
                try {
                    $db = getDbConnection();
                    $stmt = $db->prepare("INSERT INTO attachments (business_id, file_path, type) VALUES (?, ?, 'photo')");
                    $stmt->execute([$businessId, '/uploads/businesses/' . $businessId . '/' . $filename]);
                } catch (Exception $e) {
                    error_log("Error guardando attachment: " . $e->getMessage());
                }
            }
        }

        $message     = $result['message'] . ' ✓ Fotos procesadas correctamente.';
        $messageType = 'success';
    } else {
        $message     = $result['message'];
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Nuevo Negocio</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5568d3;
            --primary-light: #8b9ef5;
            --secondary: #00bfa5;
            --success: #2ecc71;
            --warning: #f39c12;
            --danger: #e74c3c;
            --info: #3498db;
            --light-gray: #f5f6fa;
            --medium-gray: #d0d5dd;
            --dark-gray: #6c757d;
            --charcoal: #2c3e50;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
            background: linear-gradient(135deg, var(--light-gray) 0%, #ffffff 100%);
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 30px 40px;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 5px;
            letter-spacing: -0.5px;
        }

        .header p {
            opacity: 0.95;
            font-size: 14px;
        }

        .content {
            padding: 40px;
        }

        .message {
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: 8px;
            font-weight: 500;
            animation: slideDown 0.3s ease-out;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid var(--success);
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid var(--danger);
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--charcoal);
            margin-top: 30px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--light-gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .section-title:first-of-type {
            margin-top: 0;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-grid.full {
            grid-column: 1 / -1;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            font-weight: 600;
            font-size: 13px;
            color: var(--charcoal);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        input[type="text"],
        input[type="number"],
        input[type="url"],
        input[type="tel"],
        input[type="email"],
        input[type="time"],
        select,
        textarea,
        input[type="file"] {
            padding: 12px 14px;
            border: 1.5px solid var(--medium-gray);
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.2s ease;
            background: white;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        input[type="url"]:focus,
        input[type="tel"]:focus,
        input[type="email"]:focus,
        input[type="time"]:focus,
        select:focus,
        textarea:focus,
        input[type="file"]:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: #fafbff;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-hint {
            font-size: 12px;
            color: var(--dark-gray);
            margin-top: 6px;
            font-style: italic;
        }

        .map-container {
            width: 100%;
            height: 400px;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 30px;
            border: 1.5px solid var(--medium-gray);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        #map {
            width: 100%;
            height: 100%;
        }

        .map-hint {
            background: linear-gradient(135deg, rgba(0, 191, 165, 0.08) 0%, rgba(102, 126, 234, 0.08) 100%);
            border-left: 4px solid var(--secondary);
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 13px;
            color: var(--charcoal);
            margin-bottom: 25px;
            font-weight: 500;
        }

        .coordinates {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .photo-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 12px;
            margin-top: 12px;
        }

        .photo-item {
            position: relative;
            width: 100px;
            height: 100px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .photo-item:hover {
            transform: scale(1.05);
        }

        .photo-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-item .remove-btn {
            position: absolute;
            top: -25px;
            right: -25px;
            width: 50px;
            height: 50px;
            background: rgba(231, 76, 60, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .photo-item:hover .remove-btn {
            top: 5px;
            right: 5px;
            opacity: 1;
        }

        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 12px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--primary);
        }

        .checkbox-item label {
            margin: 0;
            cursor: pointer;
            font-weight: 500;
            font-size: 13px;
            text-transform: none;
            letter-spacing: normal;
        }

        .schedule-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: var(--light-gray);
            border-radius: 6px;
            margin-bottom: 10px;
        }

        .schedule-item label {
            margin: 0;
            min-width: 80px;
            text-transform: none;
        }

        .schedule-item input[type="time"] {
            flex: 1;
        }

        .buttons {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1.5px solid var(--light-gray);
        }

        button {
            padding: 12px 28px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        button:active {
            transform: scale(0.98);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--light-gray);
            color: var(--charcoal);
        }

        .btn-secondary:hover {
            background: var(--medium-gray);
            color: var(--charcoal);
        }

        .btn-back {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-back:hover {
            background: var(--primary);
            color: white;
        }

        .geolocation-btn {
            padding: 10px 14px;
            background: var(--secondary);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            margin-top: 8px;
            transition: all 0.2s ease;
        }

        .geolocation-btn:hover {
            background: darken(var(--secondary), 10%);
            box-shadow: 0 2px 8px rgba(0, 191, 165, 0.3);
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .coordinates {
                grid-template-columns: 1fr;
            }

            .buttons {
                flex-direction: column;
            }

            button {
                width: 100%;
            }

            .header {
                padding: 20px;
            }

            .header h1 {
                font-size: 22px;
            }

            .content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📍 Agregar Nuevo Negocio</h1>
            <p>Usuario: <?php echo htmlspecialchars($currentUser); ?> | <?php echo htmlspecialchars($currentDateTime); ?></p>
        </div>

        <div class="content">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="map-hint">
                <strong>💡 Consejo:</strong> Haz clic en el mapa para seleccionar la ubicación exacta de tu negocio
            </div>

            <form method="post" enctype="multipart/form-data">
                <?php echo csrfField(); ?>

                <!-- UBICACIÓN EN MAPA -->
                <h2 class="section-title">📍 Ubicación</h2>
                <div class="map-container">
                    <div id="map"></div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="address">Dirección *</label>
                        <input type="text" id="address" name="address" required placeholder="Calle, número, ciudad">
                        <span class="form-hint">La dirección completa de tu negocio</span>
                    </div>

                    <div class="form-group">
                        <label>Coordenadas *</label>
                        <div class="coordinates">
                            <div class="form-group" style="margin: 0;">
                                <input type="number" id="lat" name="lat" step="any" required readonly placeholder="Latitud">
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <input type="number" id="lng" name="lng" step="any" required readonly placeholder="Longitud">
                            </div>
                            <button type="button" class="geolocation-btn" onclick="geolocateMe()">📍 Auto-detectar</button>
                        </div>
                        <span class="form-hint">Haz clic en el mapa o usa auto-detectar</span>
                    </div>
                </div>

                <!-- INFORMACIÓN BÁSICA -->
                <h2 class="section-title">📋 Información Básica</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Nombre del Negocio *</label>
                        <input type="text" id="name" name="name" required placeholder="Nombre completo">
                    </div>

                    <div class="form-group">
                        <label for="business_type">Tipo de Negocio *</label>
                        <select id="business_type" name="business_type" required onchange="toggleComercioFields()">
                            <option value="">Selecciona un tipo</option>
                            <option value="comercio">Comercio</option>
                            <option value="hotel">Hotel</option>
                            <option value="restaurante">Restaurante</option>
                            <option value="inmobiliaria">Inmobiliaria</option>
                            <option value="farmacia">Farmacia</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="price_range">Rango de Precios</label>
                        <select id="price_range" name="price_range">
                            <option value="1">$ - Muy económico</option>
                            <option value="2">$$ - Económico</option>
                            <option value="3" selected>$$$ - Precio medio</option>
                            <option value="4">$$$$ - Costoso</option>
                            <option value="5">$$$$$ - Muy costoso</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="description">Descripción del Negocio</label>
                        <textarea id="description" name="description" placeholder="Cuéntanos sobre tu negocio, qué ofreces, especialidades..."></textarea>
                    </div>
                </div>

                <!-- CONTACTO -->
                <h2 class="section-title">📞 Contacto</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="phone">Teléfono</label>
                        <input type="tel" id="phone" name="phone" placeholder="+54 9 11 1234-5678">
                    </div>

                    <div class="form-group">
                        <label for="email">Correo Electrónico</label>
                        <input type="email" id="email" name="email" placeholder="contacto@negocio.com">
                    </div>

                    <div class="form-group">
                        <label for="website">Sitio Web</label>
                        <input type="url" id="website" name="website" placeholder="https://www.miwebsite.com">
                    </div>

                    <div class="form-group">
                        <label for="instagram">Instagram</label>
                        <input type="text" id="instagram" name="instagram" placeholder="@miusuario">
                    </div>

                    <div class="form-group">
                        <label for="facebook">Facebook</label>
                        <input type="text" id="facebook" name="facebook" placeholder="Nombre de la página">
                    </div>

                    <div class="form-group">
                        <label for="tiktok">TikTok</label>
                        <input type="text" id="tiktok" name="tiktok" placeholder="@miusuario">
                    </div>
                </div>

                <!-- FOTOS -->
                <h2 class="section-title">📸 Fotos del Negocio</h2>
                <div class="form-group form-group.full">
                    <label for="photos">Carga fotos (máximo 5, 2MB cada una)</label>
                    <input type="file" id="photos" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" onchange="previewPhotos(event)">
                    <span class="form-hint">Formatos: JPG, PNG, WebP. Las imágenes aparecerán en el mapa</span>
                    <div id="photo-preview" class="photo-preview"></div>
                </div>

                <!-- SERVICIOS Y CARACTERÍSTICAS -->
                <h2 class="section-title">✨ Servicios y Características</h2>
                <div class="form-group form-group.full">
                    <label>¿Qué servicios ofreces?</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="checkbox" id="has_delivery" name="has_delivery" value="1">
                            <label for="has_delivery">🚚 Delivery/Envío</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="has_card_payment" name="has_card_payment" value="1">
                            <label for="has_card_payment">💳 Acepta tarjeta</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="is_franchise" name="is_franchise" value="1">
                            <label for="is_franchise">🏢 Es franquicia</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="verified" name="verified" value="1">
                            <label for="verified">✅ Negocio verificado</label>
                        </div>
                    </div>
                </div>

                <!-- CERTIFICACIONES -->
                <div class="form-group form-group.full">
                    <label for="certifications">Certificaciones / Distinciones</label>
                    <input type="text" id="certifications" name="certifications" placeholder="ISO9001, BPA, Orgánico, Sustentable, etc." >
                    <span class="form-hint">Separadas por coma</span>
                </div>

                <!-- CAMPOS ESPECÍFICOS PARA COMERCIOS -->
                <div id="comercio-section" style="display: none;">
                    <h2 class="section-title">🏪 Detalles del Comercio</h2>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="tipo_comercio">Tipo de Comercio Específico</label>
                            <input type="text" id="tipo_comercio" name="tipo_comercio" placeholder="Ej: supermercado, panadería, verdulería">
                        </div>

                        <div class="form-group">
                            <label for="categorias_productos">Categorías de Productos/Servicios</label>
                            <input type="text" id="categorias_productos" name="categorias_productos" placeholder="Ej: ropa,accesorios,electrónica">
                            <span class="form-hint">Separadas por coma</span>
                        </div>
                    </div>

                    <h3 style="font-size: 14px; font-weight: 600; color: var(--charcoal); margin-top: 20px; margin-bottom: 15px;">🕐 Horarios de Atención</h3>
                    <div id="schedules-container"></div>
                </div>

                <!-- BOTONES -->
                <div class="buttons">
                    <a href="/"><button type="button" class="btn-back">← Volver</button></a>
                    <button type="reset" class="btn-secondary">🗑️ Limpiar</button>
                    <button type="submit" class="btn-primary">💾 Guardar Negocio</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Mapa
        var map = L.map('map').setView([-34.6037, -58.3816], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        var marker;
        var lat = document.getElementById('lat');
        var lng = document.getElementById('lng');

        map.on('click', function(e) {
            if (marker) map.removeLayer(marker);
            marker = L.marker(e.latlng).addTo(map);
            lat.value = e.latlng.lat.toFixed(6);
            lng.value = e.latlng.lng.toFixed(6);
        });

        // Geolocalización
        function geolocateMe() {
            if (!navigator.geolocation) {
                alert('Tu navegador no soporta geolocalización');
                return;
            }
            navigator.geolocation.getCurrentPosition(pos => {
                lat.value = pos.coords.latitude.toFixed(6);
                lng.value = pos.coords.longitude.toFixed(6);
                const latlng = [pos.coords.latitude, pos.coords.longitude];
                if (marker) map.removeLayer(marker);
                marker = L.marker(latlng).addTo(map);
                map.setView(latlng, 15);
                document.getElementById('address').focus();
            }, () => alert('No se pudo obtener la ubicación'));
        }

        // Toggle campos comercio
        function toggleComercioFields() {
            const section = document.getElementById('comercio-section');
            const type = document.getElementById('business_type').value;
            section.style.display = type === 'comercio' ? 'block' : 'none';
            if (type === 'comercio') generateSchedules();
        }

        // Generar horarios por día
        function generateSchedules() {
            const dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
            const container = document.getElementById('schedules-container');
            if (container.innerHTML) return; // Ya generado

            dias.forEach((dia, i) => {
                const row = document.createElement('div');
                row.className = 'schedule-item';
                row.innerHTML = `
                    <label>${dia}</label>
                    <input type="time" name="horario_apertura_${i}" placeholder="Abre">
                    <input type="time" name="horario_cierre_${i}" placeholder="Cierra">
                    <input type="checkbox" name="cerrado_${i}" title="Cerrado este día">
                `;
                container.appendChild(row);
            });
        }

        // Preview de fotos
        function previewPhotos(event) {
            const preview = document.getElementById('photo-preview');
            preview.innerHTML = '';
            const files = Array.from(event.target.files).slice(0, 5);

            files.forEach((file, i) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const item = document.createElement('div');
                    item.className = 'photo-item';
                    item.innerHTML = `
                        <img src="${e.target.result}" alt="Preview ${i + 1}">
                        <button type="button" class="remove-btn" onclick="removePhoto(${i}, event)">×</button>
                    `;
                    preview.appendChild(item);
                };
                reader.readAsDataURL(file);
            });
        }

        function removePhoto(index, event) {
            event.preventDefault();
            const input = document.getElementById('photos');
            const dt = new DataTransfer();
            Array.from(input.files).forEach((f, i) => {
                if (i !== index) dt.items.add(f);
            });
            input.files = dt.files;
            previewPhotos({ target: input });
        }
    </script>
</body>
</html>
