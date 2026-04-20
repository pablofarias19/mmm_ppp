<?php
// views/brand/brand_form.php
// Formulario para crear o editar una marca
$editing = isset($brand);

// ── OG Cover detection ────────────────────────────────────────────────────────
$ogCoverPath   = null;
$ogCoverPublic = null;
if ($editing) {
    $brandUploadDir = __DIR__ . '/../../uploads/brands/' . $brand['id'] . '/';
    foreach (['jpg','jpeg','png','webp'] as $_ext) {
        $candidate = $brandUploadDir . 'og_cover.' . $_ext;
        if (file_exists($candidate)) {
            $ogCoverPublic = '/uploads/brands/' . $brand['id'] . '/og_cover.' . $_ext;
            $ogCoverPath   = $candidate;
            break;
        }
    }
}
$hasPersonalizedOg = ($ogCoverPublic !== null);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $editing ? 'Editar Marca' : 'Nueva Marca' ?></title>
    <link rel="stylesheet" href="/css/map-styles.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        #map-picker { height: 300px; width: 100%; margin-bottom: 15px; border-radius: 8px; border: 1px solid #ccc; }
        .coords-row { display: flex; gap: 10px; }
        .coords-row input { width: 50%; }

        /* Gallery Upload Styles */
        .gallery-section {
            margin-top: 30px;
            padding: 20px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(248, 250, 255, 1) 100%);
            border-radius: 12px;
            border: 2px dashed #667eea;
        }

        .gallery-section h3 {
            color: #667eea;
            margin: 0 0 15px 0;
            font-size: 1.2em;
            font-weight: 600;
        }

        .upload-area {
            border: 2px dashed #667eea;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
        }

        .upload-area:hover {
            background: rgba(102, 126, 234, 0.05);
            border-color: #764ba2;
        }

        .upload-area.dragover {
            background: rgba(102, 126, 234, 0.1);
            border-color: #764ba2;
            transform: scale(1.01);
        }

        .upload-area svg {
            width: 40px;
            height: 40px;
            margin-bottom: 10px;
            color: #667eea;
        }

        .upload-area p {
            margin: 10px 0 0 0;
            color: #667eea;
            font-weight: 500;
        }

        .upload-area small {
            display: block;
            color: #999;
            margin-top: 5px;
        }

        #imageInput {
            display: none;
        }

        .photo-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .photo-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .photo-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.2);
        }

        .photo-item img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            display: block;
        }

        .photo-item .photo-actions {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .photo-item:hover .photo-actions {
            opacity: 1;
        }

        .photo-actions button {
            background: white;
            border: none;
            padding: 6px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .photo-actions button:hover {
            transform: scale(1.05);
        }

        .btn-delete {
            background: #e74c3c !important;
            color: white !important;
        }

        .btn-primary {
            background: #667eea !important;
            color: white !important;
        }

        .photo-item.main-image {
            border: 3px solid #2ecc71;
        }

        .photo-item.main-image::after {
            content: '⭐ Principal';
            position: absolute;
            bottom: 0;
            right: 0;
            background: #2ecc71;
            color: white;
            padding: 4px 8px;
            font-size: 10px;
            font-weight: 600;
        }

        .gallery-loading {
            text-align: center;
            padding: 20px;
            color: #667eea;
        }

        .gallery-empty {
            text-align: center;
            padding: 20px;
            color: #999;
            font-size: 0.9em;
        }

        /* OG Cover Section */
        .og-section {
            margin-top: 24px;
            padding: 20px;
            background: linear-gradient(135deg, rgba(230,126,34,0.06) 0%, rgba(255,250,245,1) 100%);
            border-radius: 12px;
            border: 2px dashed #e67e22;
        }
        .og-section h3 { color:#e67e22; margin:0 0 4px 0; font-size:1.1em; font-weight:700; }
        .og-section p.og-subtitle { color:#888; font-size:.85em; margin:0 0 14px 0; }
        .og-badge {
            display:inline-block; padding:3px 10px; border-radius:20px; font-size:.78em;
            font-weight:600; margin-bottom:14px;
        }
        .og-badge.personalizada { background:#2ecc71; color:#fff; }
        .og-badge.automatica    { background:#95a5a6; color:#fff; }

        .og-preview-wrap {
            background:#000; border-radius:8px; overflow:hidden;
            aspect-ratio:1200/630; max-width:100%; margin-bottom:12px;
            position:relative;
        }
        .og-preview-wrap img {
            width:100%; height:100%; object-fit:cover; display:block;
            transition: opacity .3s;
        }

        .og-drop-zone {
            border:2px dashed #e67e22; border-radius:8px; padding:22px;
            text-align:center; cursor:pointer; transition: all .3s;
            background:rgba(255,255,255,.8); margin-bottom:10px;
        }
        .og-drop-zone:hover, .og-drop-zone.dragover {
            background:rgba(230,126,34,.07); border-color:#c0392b;
        }
        .og-drop-zone p { margin:6px 0 0; color:#e67e22; font-weight:500; font-size:.9em; }
        .og-drop-zone small { color:#aaa; }

        .og-actions { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:14px; }
        .og-actions button {
            padding:8px 16px; border:none; border-radius:6px; cursor:pointer;
            font-weight:600; font-size:.85em; transition: filter .2s;
        }
        .og-actions button:hover { filter:brightness(1.12); }
        .btn-og-delete { background:#e74c3c; color:#fff; }
        .btn-og-upload { background:#e67e22; color:#fff; }

        /* WhatsApp preview */
        .wa-simulation {
            background:#e5ddd5; border-radius:10px; padding:10px;
            max-width:320px; margin-top:10px;
        }
        .wa-simulation .wa-bubble {
            background:#fff; border-radius:8px; overflow:hidden;
            box-shadow:0 1px 3px rgba(0,0,0,.15);
        }
        .wa-bubble img { width:100%; aspect-ratio:16/9; object-fit:cover; display:block; }
        .wa-bubble .wa-text { padding:8px 10px; }
        .wa-bubble .wa-title { font-size:.8em; font-weight:700; color:#111; margin:0 0 2px; }
        .wa-bubble .wa-desc  { font-size:.72em; color:#555; margin:0 0 2px; }
        .wa-bubble .wa-site  { font-size:.68em; color:#888; text-transform:uppercase; margin:0; }
        .wa-label { font-size:.75em; color:#777; margin:4px 0 6px 2px; font-weight:600; }
    </style>
</head>
<body>
    <div style="max-width: 600px; margin: 0 auto; padding: 30px 0;">
        <h1><?= $editing ? 'Editar Marca' : 'Nueva Marca' ?></h1>
        <form method="post" action="<?= $editing ? 'brand_form.php?id=' . $brand['id'] : 'brand_form.php' ?>">
            <label>Nombre:
                <input type="text" name="nombre" required value="<?= $editing ? htmlspecialchars($brand['nombre']) : '' ?>">
            </label>
            <label>Rubro:
                <input type="text" name="rubro" value="<?= $editing ? htmlspecialchars($brand['rubro']) : '' ?>">
            </label>
            <label>Ubicación:
                <input type="text" name="ubicacion" value="<?= $editing ? htmlspecialchars($brand['ubicacion']) : '' ?>" placeholder="Dirección completa">
            </label>
            <label>Ubicación en el mapa:
                <div id="map-picker"></div>
                <div class="coords-row">
                    <input type="number" step="any" name="lat" id="lat" placeholder="Latitud" value="<?= $editing && $brand['lat'] ? htmlspecialchars($brand['lat']) : '' ?>">
                    <input type="number" step="any" name="lng" id="lng" placeholder="Longitud" value="<?= $editing && $brand['lng'] ? htmlspecialchars($brand['lng']) : '' ?>">
                </div>
                <small style="color:#666;">Haz clic en el mapa para seleccionar la ubicación</small>
            </label>
            <label>Estado:
                <select name="estado" required>
                    <option value="IDEA" <?= $editing && $brand['estado'] == 'IDEA' ? 'selected' : '' ?>>Idea</option>
                    <option value="activa" <?= $editing && $brand['estado'] == 'activa' ? 'selected' : '' ?>>Activa</option>
                    <option value="REGISTRADA" <?= $editing && $brand['estado'] == 'REGISTRADA' ? 'selected' : '' ?>>Registrada</option>
                    <option value="inactiva" <?= $editing && $brand['estado'] == 'inactiva' ? 'selected' : '' ?>>Inactiva</option>
                </select>
            </label>
            <button type="submit">Guardar Marca</button>
            <a href="/marcas" style="margin-left:12px; color:#e53e3e;">Cancelar</a>
        </form>

        <?php if ($editing): ?>
        <!-- Galería de Imágenes -->
        <div class="gallery-section">
            <h3>🖼️ Galería de Imágenes</h3>

            <div class="upload-area" id="uploadArea">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                <p>Arrastra imágenes aquí o haz clic para seleccionar</p>
                <small>JPG, PNG, WebP - Máximo 5MB cada una</small>
                <input type="file" id="imageInput" multiple accept="image/*">
            </div>

            <div id="photoPreview" class="photo-preview"></div>
            <div id="galleryLoading" class="gallery-loading" style="display:none;">Cargando galería...</div>
        </div>

        <!-- ── Foto para redes sociales (OG Cover) ── -->
        <div class="og-section">
            <h3>📲 Foto para redes sociales</h3>
            <p class="og-subtitle">Esta imagen se verá cuando alguien comparta tu marca en WhatsApp, Facebook o Twitter.</p>

            <?php if ($hasPersonalizedOg): ?>
                <span class="og-badge personalizada">✔ Imagen personalizada</span>
            <?php else: ?>
                <span class="og-badge automatica">⚙ Imagen auto-generada</span>
            <?php endif; ?>

            <!-- Preview 1200×630 -->
            <div class="og-preview-wrap">
                <img id="ogPreviewImg"
                     src="/api/og_image.php?type=brand&brand_id=<?= $brand['id'] ?>&t=<?= time() ?>"
                     alt="Vista previa OG">
            </div>

            <!-- Zona de drop/upload -->
            <div class="og-drop-zone" id="ogDropZone">
                <div style="font-size:2em;">🖼️</div>
                <p>Arrastrá una imagen aquí o hacé clic para elegir</p>
                <small>JPG, PNG o WebP · máximo 8 MB · ideal 1200×630 px</small>
                <input type="file" id="ogFileInput" accept="image/jpeg,image/png,image/webp" style="display:none;">
            </div>

            <!-- Acciones -->
            <div class="og-actions">
                <button type="button" class="btn-og-upload" onclick="document.getElementById('ogFileInput').click()">
                    📤 Subir imagen
                </button>
                <?php if ($hasPersonalizedOg): ?>
                <button type="button" class="btn-og-delete" id="btnOgDelete" onclick="deleteBrandOg()">
                    🗑️ Eliminar imagen personalizada
                </button>
                <?php else: ?>
                <button type="button" class="btn-og-delete" id="btnOgDelete" onclick="deleteBrandOg()" style="display:none;">
                    🗑️ Eliminar imagen personalizada
                </button>
                <?php endif; ?>
            </div>

            <!-- Simulación WhatsApp -->
            <p class="wa-label">📱 Así se verá en WhatsApp:</p>
            <div class="wa-simulation">
                <div class="wa-bubble">
                    <img id="ogWaImg"
                         src="/api/og_image.php?type=brand&brand_id=<?= $brand['id'] ?>&t=<?= time() ?>"
                         alt="Preview WhatsApp">
                    <div class="wa-text">
                        <p class="wa-title"><?= htmlspecialchars($brand['nombre']) ?> — Marca en Mapita</p>
                        <p class="wa-desc">Conocé la marca <?= htmlspecialchars($brand['nombre']) ?><?= $brand['rubro'] ? ' · ' . htmlspecialchars($brand['rubro']) : '' ?> disponible en Mapita.</p>
                        <p class="wa-site">mapita.com.ar</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <script>
        var defaultLat = <?= $editing && $brand['lat'] ? $brand['lat'] : '-34.6037' ?>;
        var defaultLng = <?= $editing && $brand['lng'] ? $brand['lng'] : '-58.3816' ?>;
        var map = L.map('map-picker').setView([defaultLat, defaultLng], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);
        var marker = null;
        if (defaultLat != -34.6037 || defaultLng != -58.3816) {
            marker = L.marker([defaultLat, defaultLng]).addTo(map);
        }
        map.on('click', function(e) {
            document.getElementById('lat').value = e.latlng.lat.toFixed(6);
            document.getElementById('lng').value = e.latlng.lng.toFixed(6);
            if (marker) map.removeLayer(marker);
            marker = L.marker(e.latlng).addTo(map);
        });

        // Gallery Management
        const brandId = new URLSearchParams(window.location.search).get('id');
        const uploadArea = document.getElementById('uploadArea');
        const imageInput = document.getElementById('imageInput');
        const photoPreview = document.getElementById('photoPreview');

        if (brandId) {
            // Cargar galería existente
            cargarGaleria();

            // Setup drag and drop
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                uploadArea.addEventListener(eventName, () => uploadArea.classList.add('dragover'), false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, () => uploadArea.classList.remove('dragover'), false);
            });

            uploadArea.addEventListener('drop', handleDrop, false);
            uploadArea.addEventListener('click', () => imageInput.click());
            imageInput.addEventListener('change', handleFileSelect);

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                imageInput.files = files;
                subirImagenes(files);
            }

            function handleFileSelect(e) {
                subirImagenes(e.target.files);
                e.target.value = '';
            }

            function subirImagenes(files) {
                if (files.length === 0) return;

                Array.from(files).forEach(file => {
                    if (!file.type.startsWith('image/')) {
                        alert('Solo se aceptan archivos de imagen');
                        return;
                    }

                    const formData = new FormData();
                    formData.append('brand_id', brandId);
                    formData.append('imagen', file);
                    formData.append('titulo', file.name);
                    formData.append('es_principal', photoPreview.children.length === 0 ? '1' : '0');

                    fetch('/api/brand-gallery.php?action=upload', {
                        method: 'POST',
                        body: formData
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            cargarGaleria();
                        } else {
                            alert('Error: ' + (data.message || 'No se pudo subir la imagen'));
                        }
                    })
                    .catch(err => {
                        console.error('Error:', err);
                        alert('Error al subir imagen');
                    });
                });
            }

            function cargarGaleria() {
                document.getElementById('galleryLoading').style.display = 'block';
                photoPreview.innerHTML = '';

                fetch('/api/brand-gallery.php?brand_id=' + brandId)
                    .then(r => r.json())
                    .then(data => {
                        document.getElementById('galleryLoading').style.display = 'none';

                        if (!data.success || !data.data || data.data.length === 0) {
                            photoPreview.innerHTML = '<div class="gallery-empty" style="grid-column:1/-1;">No hay imágenes. Sube algunas para mostrar en el popup.</div>';
                            return;
                        }

                        data.data.forEach(img => {
                            const item = document.createElement('div');
                            item.className = 'photo-item' + (img.es_principal ? ' main-image' : '');
                            item.innerHTML = `
                                <img src="/uploads/brands/${img.filename}" alt="${img.titulo || 'Imagen de marca'}">
                                <div class="photo-actions">
                                    ${!img.es_principal ? `<button class="btn-primary" onclick="establecerPrincipal(${img.id}, ${brandId})">⭐ Principal</button>` : ''}
                                    <button class="btn-delete" onclick="eliminarImagen(${img.id}, ${brandId})">🗑️ Eliminar</button>
                                </div>
                            `;
                            photoPreview.appendChild(item);
                        });
                    })
                    .catch(err => {
                        console.error('Error cargando galería:', err);
                        document.getElementById('galleryLoading').style.display = 'none';
                        photoPreview.innerHTML = '<div class="gallery-empty" style="grid-column:1/-1;">Error cargando galería</div>';
                    });
            }

            window.establecerPrincipal = function(imageId, bId) {
                fetch('/api/brand-gallery.php?action=set-main', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'image_id=' + imageId + '&brand_id=' + bId
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        cargarGaleria();
                    } else {
                        alert('Error al establecer imagen principal');
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Error al establecer imagen principal');
                });
            };

            window.eliminarImagen = function(imageId, bId) {
                if (!confirm('¿Eliminar esta imagen?')) return;

                fetch('/api/brand-gallery.php?action=delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'image_id=' + imageId + '&brand_id=' + bId
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        cargarGaleria();
                    } else {
                        alert('Error al eliminar imagen');
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Error al eliminar imagen');
                });
            };
        }

        // ── OG Cover para marcas ───────────────────────────────────────────────
        (function() {
            if (!brandId) return;

            const ogDrop     = document.getElementById('ogDropZone');
            const ogFile     = document.getElementById('ogFileInput');
            const ogPreview  = document.getElementById('ogPreviewImg');
            const ogWaImg    = document.getElementById('ogWaImg');
            const btnDel     = document.getElementById('btnOgDelete');

            if (!ogDrop) return;  // panel no renderizado (no $editing)

            // Drag & drop
            ['dragenter','dragover','dragleave','drop'].forEach(ev =>
                ogDrop.addEventListener(ev, e => { e.preventDefault(); e.stopPropagation(); })
            );
            ['dragenter','dragover'].forEach(ev =>
                ogDrop.addEventListener(ev, () => ogDrop.classList.add('dragover'))
            );
            ['dragleave','drop'].forEach(ev =>
                ogDrop.addEventListener(ev, () => ogDrop.classList.remove('dragover'))
            );

            ogDrop.addEventListener('click', () => ogFile.click());
            ogDrop.addEventListener('drop',  e => {
                const f = e.dataTransfer.files[0];
                if (f) uploadBrandOg(f);
            });
            ogFile.addEventListener('change', e => {
                if (e.target.files[0]) uploadBrandOg(e.target.files[0]);
                e.target.value = '';
            });

            function refreshOgPreviews() {
                const ts = '?t=' + Date.now();
                const base = '/api/og_image.php?type=brand&brand_id=' + brandId + ts;
                if (ogPreview) ogPreview.src = base;
                if (ogWaImg)   ogWaImg.src   = base;
            }

            function uploadBrandOg(file) {
                const fd = new FormData();
                fd.append('brand_id', brandId);
                fd.append('og_photo', file);
                fd.append('action', 'upload');

                ogDrop.innerHTML = '<p style="color:#e67e22;font-weight:600;">⏳ Subiendo…</p>';

                fetch('/api/upload_og_photo.php', { method:'POST', body: fd })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            ogDrop.innerHTML = '<div style="font-size:2em;">🖼️</div><p>Arrastrá una imagen aquí o hacé clic para elegir</p><small>JPG, PNG o WebP · máximo 8 MB · ideal 1200×630 px</small><input type="file" id="ogFileInput" accept="image/jpeg,image/png,image/webp" style="display:none;">';
                            // Re-bind input after innerHTML reset
                            document.getElementById('ogFileInput').addEventListener('change', e => {
                                if (e.target.files[0]) uploadBrandOg(e.target.files[0]);
                                e.target.value = '';
                            });
                            refreshOgPreviews();
                            if (btnDel) btnDel.style.display = '';
                            ogDrop.onclick = () => document.getElementById('ogFileInput').click();

                            // Update badge
                            const badge = document.querySelector('.og-badge');
                            if (badge) {
                                badge.className = 'og-badge personalizada';
                                badge.textContent = '✔ Imagen personalizada';
                            }
                        } else {
                            alert('Error: ' + (data.message || 'No se pudo subir'));
                            location.reload();
                        }
                    })
                    .catch(() => { alert('Error de red al subir.'); location.reload(); });
            }

            window.deleteBrandOg = function() {
                if (!confirm('¿Eliminar la foto personalizada? Se usará la imagen auto-generada.')) return;
                fetch('/api/upload_og_photo.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'brand_id=' + brandId + '&action=delete'
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        refreshOgPreviews();
                        if (btnDel) btnDel.style.display = 'none';
                        const badge = document.querySelector('.og-badge');
                        if (badge) {
                            badge.className = 'og-badge automatica';
                            badge.textContent = '⚙ Imagen auto-generada';
                        }
                    } else {
                        alert(data.message || 'No se pudo eliminar.');
                    }
                })
                .catch(() => alert('Error de red.'));
            };
        })();
    </script>
</html>
