<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit();
}

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../includes/db_helper.php';
require_once __DIR__ . '/../business/process_business.php';

setSecurityHeaders();

$userId          = (int)$_SESSION['user_id'];
$currentUser     = $_SESSION['user_name'];
$currentDateTime = date('Y-m-d H:i:s');
$message         = '';
$messageType     = '';

// Obtener ID del negocio desde la URL
$businessId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($businessId <= 0) {
    header("Location: /mis-negocios");
    exit();
}

// Verificar propiedad
$db = getDbConnection();
$stmt = $db->prepare("SELECT * FROM businesses WHERE id = ? AND user_id = ?");
$stmt->execute([$businessId, $userId]);
$business = $stmt->fetch();

if (!$business) {
    header("Location: /mis-negocios");
    exit();
}

// Obtener datos adicionales de comercio si aplica
$comercioData = getComercioData($businessId);

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verifyCsrfToken();

    $result = updateBusiness($businessId, $_POST, $userId);

    if ($result['success']) {
        $message     = $result['message'];
        $messageType = 'success';
        // Recargar datos actualizados
        $stmt->execute([$businessId, $userId]);
        $business     = $stmt->fetch();
        $comercioData = getComercioData($businessId);
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
    <title>Editar Negocio</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { color: #333; text-align: center; margin-bottom: 30px; }
        .user-info { text-align: right; font-size: 0.9em; color: #666; margin-bottom: 20px; }
        .message { padding: 15px; margin-bottom: 20px; border-radius: 4px; text-align: center; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error   { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .map-container { grid-column: 1 / -1; height: 400px; margin-bottom: 20px; }
        #map { height: 100%; border-radius: 8px; }
        .map-hint { background-color: #e8f4fc; border-left: 4px solid #007bff; padding: 10px 15px; border-radius: 4px; margin-top: 10px; margin-bottom: 20px; color: #333; font-size: 0.95em; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="number"], input[type="url"], input[type="tel"],
        input[type="email"], input[type="time"], select, textarea {
            width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; box-sizing: border-box;
        }
        .coordinates { display: flex; gap: 15px; }
        .coordinates .form-group { flex: 1; }
        .buttons { grid-column: 1 / -1; display: flex; justify-content: space-between; margin-top: 20px; }
        button { padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; }
        .submit-btn { background-color: #28a745; color: white; }
        .submit-btn:hover { background-color: #218838; }
        .cancel-btn { background-color: #dc3545; color: white; }
        .cancel-btn:hover { background-color: #c82333; }
        .back-btn { background-color: #6c757d; color: white; }
        .back-btn:hover { background-color: #5a6268; }
        .comercio-details { grid-column: 1 / -1; border: 1px solid #ddd; padding: 15px; border-radius: 8px; margin-top: 10px; display: none; }
        .comercio-details h3 { margin-top: 0; color: #333; }
        @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="container">
        <h1>Editar Negocio</h1>

        <div class="user-info">
            Usuario: <?php echo htmlspecialchars($currentUser); ?> |
            Fecha: <?php echo htmlspecialchars($currentDateTime); ?>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="map-hint">
            <strong>¡Importante!</strong> Haz clic en el mapa para actualizar la ubicación del negocio.
        </div>

        <form method="post" action="">
            <?php echo csrfField(); ?>

            <div class="map-container">
                <div id="map"></div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="name">Nombre del Negocio *</label>
                    <input type="text" id="name" name="name" required
                           value="<?php echo htmlspecialchars($business['name']); ?>">
                </div>

                <div class="form-group">
                    <label for="business_type">Tipo de Negocio *</label>
                    <select id="business_type" name="business_type" required onchange="toggleComercioFields()">
                        <option value="">Selecciona un tipo</option>
                        <?php foreach (['comercio','hotel','restaurante','inmobiliaria','farmacia'] as $t): ?>
                            <option value="<?php echo $t; ?>"
                                <?php echo $business['business_type'] === $t ? 'selected' : ''; ?>>
                                <?php echo ucfirst($t); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="address">Dirección *</label>
                    <input type="text" id="address" name="address" required
                           value="<?php echo htmlspecialchars($business['address']); ?>">
                </div>

                <div class="form-group">
                    <label for="phone">Teléfono</label>
                    <input type="tel" id="phone" name="phone"
                           value="<?php echo htmlspecialchars($business['phone'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email"
                           value="<?php echo htmlspecialchars($business['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="website">Sitio Web</label>
                    <input type="url" id="website" name="website" placeholder="https://"
                           value="<?php echo htmlspecialchars($business['website'] ?? ''); ?>">
                </div>

                <div class="coordinates">
                    <div class="form-group">
                        <label for="lat">Latitud *</label>
                        <input type="number" id="lat" name="lat" step="any" required readonly
                               value="<?php echo htmlspecialchars($business['lat'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="lng">Longitud *</label>
                        <input type="number" id="lng" name="lng" step="any" required readonly
                               value="<?php echo htmlspecialchars($business['lng'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Descripción</label>
                    <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($business['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="price_range">Rango de Precio (1-5)</label>
                    <select id="price_range" name="price_range">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?php echo $i; ?>"
                                <?php echo (int)($business['price_range'] ?? 3) === $i ? 'selected' : ''; ?>>
                                <?php echo $i; ?> - <?php echo ['','Muy económico','Económico','Precio medio','Costoso','Muy costoso'][$i]; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <!-- Campos específicos para comercios -->
                <div id="comercio-details" class="comercio-details">
                    <h3>Detalles específicos para Comercios</h3>

                    <div class="form-group">
                        <label for="tipo_comercio">Tipo de Comercio</label>
                        <input type="text" id="tipo_comercio" name="tipo_comercio"
                               placeholder="Ej: supermercado, panadería, ferretería"
                               value="<?php echo htmlspecialchars($comercioData['tipo_comercio'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="horario_apertura">Horario de Apertura</label>
                        <input type="time" id="horario_apertura" name="horario_apertura"
                               value="<?php echo htmlspecialchars(substr($comercioData['horario_apertura'] ?? '', 0, 5)); ?>">
                    </div>

                    <div class="form-group">
                        <label for="horario_cierre">Horario de Cierre</label>
                        <input type="time" id="horario_cierre" name="horario_cierre"
                               value="<?php echo htmlspecialchars(substr($comercioData['horario_cierre'] ?? '', 0, 5)); ?>">
                    </div>

                    <div class="form-group">
                        <label for="dias_cierre">Días de Cierre</label>
                        <input type="text" id="dias_cierre" name="dias_cierre"
                               placeholder="Ej: lunes,martes"
                               value="<?php echo htmlspecialchars($comercioData['dias_cierre'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="categorias_productos">Categorías de Productos</label>
                        <input type="text" id="categorias_productos" name="categorias_productos"
                               placeholder="Ej: alimentos,bebidas,limpieza"
                               value="<?php echo htmlspecialchars($comercioData['categorias_productos'] ?? ''); ?>">
                    </div>
                </div>

                <div class="buttons">
                    <a href="/mis-negocios"><button type="button" class="back-btn">← Mis Negocios</button></a>
                    <button type="reset" class="cancel-btn" onclick="resetCoords()">Deshacer cambios</button>
                    <button type="submit" class="submit-btn">Guardar Cambios</button>
                </div>
            </div>
        </form>

        <!-- ══ MÓDULO DISPONIBLES ════════════════════════════════════════════ -->
        <div style="margin-top:32px;border:2px solid #e8ecf5;border-radius:12px;overflow:hidden;">
            <div style="background:linear-gradient(135deg,#1B3B6F,#0d2247);padding:18px 24px;display:flex;align-items:center;gap:12px;">
                <span style="font-size:1.5em;">📦</span>
                <div style="flex:1;">
                    <div style="color:white;font-weight:700;font-size:1em;">Módulo Disponibles</div>
                    <div style="color:rgba(255,255,255,.65);font-size:.8em;">Publicá ítems de bienes o servicios y recibí solicitudes de usuarios</div>
                </div>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <span style="color:rgba(255,255,255,.8);font-size:.85em;font-weight:600;" id="disp-modulo-label">
                        <?php echo !empty($business['disponibles_activo']) ? 'Activo' : 'Inactivo'; ?>
                    </span>
                    <span style="position:relative;display:inline-block;width:44px;height:24px;">
                        <input type="checkbox" id="disp-modulo-toggle"
                               <?php echo !empty($business['disponibles_activo']) ? 'checked' : ''; ?>
                               style="opacity:0;width:0;height:0;position:absolute;">
                        <span id="disp-toggle-slider" style="
                            position:absolute;cursor:pointer;inset:0;
                            background:<?php echo !empty($business['disponibles_activo']) ? '#10b981' : '#d1d5db'; ?>;
                            border-radius:24px;transition:.3s;">
                            <span style="position:absolute;content:'';height:18px;width:18px;
                                left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;
                                transform:<?php echo !empty($business['disponibles_activo']) ? 'translateX(20px)' : 'translateX(0)'; ?>;"
                                id="disp-toggle-knob">
                            </span>
                        </span>
                    </span>
                </label>
            </div>
            <div style="padding:20px 24px;background:#f8faff;">
                <p style="font-size:.85em;color:#555;margin-bottom:16px;">
                    Cuando el módulo está <strong>activo</strong>, los usuarios verán el botón <strong>$$$</strong>
                    en el popup del negocio en el mapa y podrán seleccionar ítems y enviar una orden de solicitud.
                </p>
                <div id="disp-cnt-ordenes" style="display:none;margin-bottom:14px;"></div>
                <a href="/panel-disponibles?id=<?php echo $businessId; ?>"
                   style="display:inline-flex;align-items:center;gap:8px;
                          padding:11px 22px;background:#1B3B6F;color:white;
                          border-radius:9px;font-weight:700;font-size:.9em;
                          text-decoration:none;transition:filter .2s;"
                   onmouseover="this.style.filter='brightness(1.15)'"
                   onmouseout="this.style.filter=''">
                    📋 Abrir Panel de Disponibles
                </a>
            </div>
        </div>
        <!-- ══ FIN MÓDULO DISPONIBLES ════════════════════════════════════════ -->

        <script>
        (function() {
            const toggle  = document.getElementById('disp-modulo-toggle');
            const slider  = document.getElementById('disp-toggle-slider');
            const knob    = document.getElementById('disp-toggle-knob');
            const label   = document.getElementById('disp-modulo-label');
            const bizId   = <?php echo $businessId; ?>;

            function setVisual(on) {
                slider.style.background = on ? '#10b981' : '#d1d5db';
                knob.style.transform    = on ? 'translateX(20px)' : 'translateX(0)';
                label.textContent       = on ? 'Activo' : 'Inactivo';
            }

            toggle.addEventListener('change', function() {
                const activo = this.checked ? 1 : 0;
                fetch('/api/disponibles.php?action=toggle_modulo', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ business_id: bizId, activo: activo })
                })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        setVisual(activo);
                    } else {
                        toggle.checked = !activo;
                        setVisual(!activo);
                        alert('Error: ' + d.message);
                    }
                })
                .catch(() => { toggle.checked = !activo; setVisual(!activo); alert('Error de conexión'); });
            });

            // Cargar contador de órdenes pendientes
            fetch('/api/disponibles.php?business_id=' + bizId)
            .then(r => r.json())
            .then(d => {
                const cntEl = document.getElementById('disp-cnt-ordenes');
                if (d.success && d.data && d.data.ordenes_pendientes > 0) {
                    cntEl.style.display = '';
                    cntEl.innerHTML = '<span style="background:#fef3c7;color:#92400e;border:1px solid #fcd34d;border-radius:20px;padding:4px 14px;font-size:.82em;font-weight:700;">🔔 '
                        + d.data.ordenes_pendientes + ' solicitud' + (d.data.ordenes_pendientes > 1 ? 'es' : '') + ' pendiente' + (d.data.ordenes_pendientes > 1 ? 's' : '') + '</span>';
                }
            }).catch(() => {});
        })();
        </script>

        <!-- ══ FOTO PARA COMPARTIR EN REDES ═══════════════════════════════ -->
        <?php
        // Buscar og_cover existente
        $ogCoverUrl = null;
        foreach (['jpg','jpeg','png','webp'] as $ext) {
            $f = __DIR__ . '/../uploads/businesses/' . $businessId . '/og_cover.' . $ext;
            if (file_exists($f)) {
                $ogCoverUrl = '/uploads/businesses/' . $businessId . '/og_cover.' . $ext . '?t=' . filemtime($f);
                break;
            }
        }
        ?>
        <div style="margin-top:32px;border:2px solid #e8ecf5;border-radius:12px;overflow:hidden;">

            <div style="background:linear-gradient(135deg,#1B3B6F,#0d2247);padding:18px 24px;display:flex;align-items:center;gap:12px;">
                <span style="font-size:1.5em;">📲</span>
                <div>
                    <div style="color:white;font-weight:700;font-size:1em;">Foto para compartir en redes</div>
                    <div style="color:rgba(255,255,255,.65);font-size:.8em;">Esta imagen aparece en WhatsApp, Facebook, Twitter e Instagram al compartir el link del negocio</div>
                </div>
            </div>

            <div style="padding:24px;background:#f8faff;">

                <!-- Preview actual -->
                <div style="margin-bottom:20px;">
                    <div style="font-size:.75em;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;margin-bottom:10px;">Vista previa actual</div>
                    <div id="og-preview-wrap" style="position:relative;width:100%;max-width:480px;border-radius:10px;overflow:hidden;border:2px solid #e5e7eb;background:#1B3B6F;">
                        <img id="og-preview-img"
                             src="<?php echo $ogCoverUrl ?? ('/api/og_image.php?type=business&id=' . $businessId . '&t=' . time()); ?>"
                             alt="Preview OG"
                             style="width:100%;display:block;aspect-ratio:1200/630;object-fit:cover;">
                        <?php if ($ogCoverUrl): ?>
                        <div style="position:absolute;top:8px;right:8px;background:#27ae60;color:white;font-size:.7em;font-weight:700;padding:3px 8px;border-radius:10px;">
                            ✓ Foto personalizada
                        </div>
                        <?php else: ?>
                        <div style="position:absolute;top:8px;right:8px;background:#e67e22;color:white;font-size:.7em;font-weight:700;padding:3px 8px;border-radius:10px;">
                            Generada automáticamente
                        </div>
                        <?php endif; ?>
                    </div>
                    <p style="font-size:.75em;color:#9ca3af;margin:6px 0 0;">
                        Tamaño recomendado: <strong>1200 × 630 px</strong> · Formatos: JPG, PNG, WebP · Máx. 8 MB
                    </p>
                </div>

                <!-- Zona de carga -->
                <div id="og-dropzone"
                     style="border:2px dashed #c7d2fe;border-radius:10px;padding:28px 20px;text-align:center;cursor:pointer;transition:all .2s;background:white;"
                     onclick="document.getElementById('og-file-input').click()"
                     ondragover="event.preventDefault();this.style.borderColor='#667eea';this.style.background='#f0f4ff';"
                     ondragleave="this.style.borderColor='#c7d2fe';this.style.background='white';"
                     ondrop="handleOgDrop(event)">
                    <div style="font-size:2.2em;margin-bottom:8px;">🖼️</div>
                    <div style="font-weight:700;color:#374151;margin-bottom:4px;">Subir foto para redes sociales</div>
                    <div style="font-size:.82em;color:#9ca3af;">Arrastrá una imagen acá o hacé clic para seleccionar</div>
                    <input type="file" id="og-file-input" accept="image/jpeg,image/png,image/webp"
                           style="display:none;" onchange="uploadOgPhoto(this.files[0])">
                </div>

                <!-- Botón eliminar (solo si hay foto personalizada) -->
                <?php if ($ogCoverUrl): ?>
                <div style="margin-top:12px;text-align:right;">
                    <button onclick="deleteOgPhoto()" type="button"
                            style="padding:7px 16px;border:1.5px solid #e74c3c;background:white;color:#e74c3c;border-radius:8px;cursor:pointer;font-size:.82em;font-weight:600;">
                        🗑️ Quitar foto personalizada
                    </button>
                </div>
                <?php endif; ?>

                <!-- Mensaje de estado -->
                <div id="og-msg" style="display:none;margin-top:12px;padding:10px 16px;border-radius:8px;font-size:.85em;font-weight:600;"></div>

                <!-- Simulador de cómo se ve en WhatsApp -->
                <div style="margin-top:24px;">
                    <div style="font-size:.75em;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;margin-bottom:10px;">Simulación — cómo se ve al compartir por WhatsApp</div>
                    <div style="max-width:320px;border-radius:10px;overflow:hidden;border:1px solid #e5e7eb;box-shadow:0 2px 8px rgba(0,0,0,.08);">
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

            </div>
        </div>
        <!-- ══ FIN FOTO REDES ════════════════════════════════════════════ -->

    </div><!-- /container -->

    <script>
    var initialLat = <?php echo (float)($business['lat'] ?? -34.6037); ?>;
    var initialLng = <?php echo (float)($business['lng'] ?? -58.3816); ?>;

    var map    = L.map('map').setView([initialLat, initialLng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    var marker = L.marker([initialLat, initialLng]).addTo(map);
    var latInput = document.getElementById('lat');
    var lngInput = document.getElementById('lng');

    map.on('click', function(e) {
        if (marker) map.removeLayer(marker);
        marker = L.marker(e.latlng).addTo(map);
        latInput.value = e.latlng.lat.toFixed(6);
        lngInput.value = e.latlng.lng.toFixed(6);
    });

    function resetCoords() {
        latInput.value = initialLat;
        lngInput.value = initialLng;
        if (marker) map.removeLayer(marker);
        marker = L.marker([initialLat, initialLng]).addTo(map);
        map.setView([initialLat, initialLng], 15);
    }

    function toggleComercioFields() {
        var businessType  = document.getElementById('business_type').value;
        var comercioBlock = document.getElementById('comercio-details');
        comercioBlock.style.display = (businessType === 'comercio') ? 'block' : 'none';
    }

    // Mostrar el bloque si el tipo actual es comercio
    toggleComercioFields();

    // ── OG Photo upload ────────────────────────────────────────────────────────
    const BIZ_ID = <?php echo $businessId; ?>;

    function ogMsg(text, ok) {
        const el = document.getElementById('og-msg');
        el.style.display  = 'block';
        el.style.background = ok ? '#d1fae5' : '#fee2e2';
        el.style.color      = ok ? '#065f46' : '#991b1b';
        el.textContent = text;
        setTimeout(() => el.style.display = 'none', 4500);
    }

    function refreshOgPreviews(src) {
        const ts  = '?t=' + Date.now();
        const url = src || '/api/og_image.php?type=business&id=' + BIZ_ID + ts;
        document.getElementById('og-preview-img').src = url + (src ? ts : '');
        document.getElementById('og-wa-thumb').src    = url + (src ? ts : '');
    }

    function uploadOgPhoto(file) {
        if (!file) return;
        const dz = document.getElementById('og-dropzone');
        dz.style.opacity = '.5';
        dz.style.pointerEvents = 'none';

        const fd = new FormData();
        fd.append('business_id', BIZ_ID);
        fd.append('og_photo', file);
        fd.append('action', 'upload');

        fetch('/api/upload_og_photo.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                dz.style.opacity = '1';
                dz.style.pointerEvents = 'auto';
                if (data.success) {
                    ogMsg('✅ ' + data.message, true);
                    refreshOgPreviews(data.preview);
                    // Actualizar badge
                    const badge = document.querySelector('#og-preview-wrap div[style*="position:absolute"]');
                    if (badge) { badge.style.background = '#27ae60'; badge.textContent = '✓ Foto personalizada'; }
                } else {
                    ogMsg('❌ ' + data.message, false);
                }
            })
            .catch(() => {
                dz.style.opacity = '1';
                dz.style.pointerEvents = 'auto';
                ogMsg('❌ Error de conexión. Intentá de nuevo.', false);
            });
    }

    function handleOgDrop(e) {
        e.preventDefault();
        document.getElementById('og-dropzone').style.borderColor = '#c7d2fe';
        document.getElementById('og-dropzone').style.background  = 'white';
        const file = e.dataTransfer.files[0];
        if (file && file.type.startsWith('image/')) uploadOgPhoto(file);
    }

    function deleteOgPhoto() {
        if (!confirm('¿Eliminar la foto personalizada? Se volverá a usar la imagen generada automáticamente.')) return;
        fetch('/api/upload_og_photo.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'business_id=' + BIZ_ID + '&action=delete'
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                ogMsg('🗑️ Foto personalizada eliminada.', true);
                refreshOgPreviews(null);
                const badge = document.querySelector('#og-preview-wrap div[style*="position:absolute"]');
                if (badge) { badge.style.background = '#e67e22'; badge.textContent = 'Generada automáticamente'; }
                // Ocultar botón eliminar
                const btn = document.querySelector('button[onclick="deleteOgPhoto()"]');
                if (btn) btn.closest('div').style.display = 'none';
            }
        });
    }
    </script>
</body>
</html>
