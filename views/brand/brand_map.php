<?php
session_start();
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../includes/db_helper.php';

$_mapGlobalIconBoost = 1.0;
try {
    $_settingsDb = getDbConnection();
    if ($_settingsDb) {
        $_mapGlobalIconBoost = (float)mapitaGetSetting($_settingsDb, 'global_icon_boost', '1.0');
        $_mapGlobalIconBoost = max(0.5, min(3.0, $_mapGlobalIconBoost));
    }
} catch (Throwable $_e) { /* silencioso */ }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mapa de Marcas — Mapita</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="/css/map-styles.css">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: sans-serif; display: flex; height: 100vh; overflow: hidden; }

        /* ── Sidebar ── */
        #sidebar {
            width: 300px;
            min-width: 300px;
            display: flex;
            flex-direction: column;
            background: #f8f9fc;
            border-right: 1px solid #e0e4ef;
            z-index: 900;
            height: 100vh;
            overflow: hidden;
        }
        .sidebar-header {
            padding: 14px 16px 10px;
            background: #1B3B6F;
            color: white;
            flex-shrink: 0;
        }
        .sidebar-header h2 { margin: 0 0 10px; font-size: 1.05em; font-weight: 700; color: white; }

        /* Búsqueda */
        #search-count {
            display: none;
            font-size: 11px;
            color: #c8d4f5;
            margin-top: 5px;
        }
        #busqueda {
            width: 100%; padding: 8px 10px; border: none; border-radius: 6px;
            font-size: 13px; outline: none;
        }

        /* Controles de selección */
        .sel-controls {
            display: flex; align-items: center; gap: 6px;
            padding: 8px 12px;
            background: #eef0f8;
            border-bottom: 1px solid #dde0ee;
            flex-shrink: 0;
        }
        .sel-controls button {
            font-size: 11px; padding: 4px 9px; border: 1px solid #b0b8d8;
            border-radius: 4px; background: white; color: #333; cursor: pointer; font-weight: 600;
            transition: background .15s;
        }
        .sel-controls button:hover { background: #667eea; color: white; border-color: #667eea; }
        #sel-count {
            margin-left: auto; font-size: 11px; font-weight: 700;
            background: #667eea; color: white; padding: 3px 8px; border-radius: 10px;
        }
        #sel-count.none { background: #bbb; }

        /* Lista de marcas */
        #lista {
            flex: 1;
            overflow-y: auto;
            padding: 8px 10px;
        }
        .marca-item {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            padding: 9px 10px;
            margin-bottom: 5px;
            border-radius: 8px;
            background: white;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all .15s;
            box-shadow: 0 1px 3px rgba(0,0,0,.07);
        }
        .marca-item:hover { border-color: #667eea; box-shadow: 0 2px 8px rgba(102,126,234,.15); }
        .marca-item.selected { border-color: #667eea; background: #f0f3ff; }
        .marca-item input[type=checkbox] {
            margin-top: 2px; flex-shrink: 0;
            accent-color: #667eea; width: 15px; height: 15px; cursor: pointer;
        }
        .marca-info { flex: 1; min-width: 0; }
        .marca-nombre { font-weight: 700; font-size: 13px; color: #1B3B6F; }
        .marca-rubro  { font-size: 11px; color: #888; margin-top: 2px; }
        .marca-tags   { margin-top: 4px; display: flex; flex-wrap: wrap; gap: 3px; }
        .tag {
            font-size: 10px; padding: 2px 6px; border-radius: 10px;
            font-weight: 600; white-space: nowrap;
        }
        .tag-niza     { background: #e8f4fd; color: #2980b9; }
        .tag-valor    { background: #eafaf1; color: #27ae60; }
        .tag-riesgo   { background: #fdf2f8; color: #8e44ad; }
        .marca-center {
            font-size: 16px; cursor: pointer; color: #aaa; flex-shrink: 0;
            padding: 2px 4px; line-height: 1;
        }
        .marca-center:hover { color: #667eea; }

        /* Capas y datos */
        .layer-box {
            flex-shrink: 0;
            border-top: 1px solid #dde0ee;
            padding: 10px 12px;
            background: #fff;
        }
        .layer-box h4 { margin: 0 0 8px; font-size: 12px; color: #555; text-transform: uppercase; letter-spacing: .5px; }
        .layer-row {
            display: flex; align-items: center; gap: 8px;
            padding: 5px 0; font-size: 12px; color: #333;
            border-bottom: 1px solid #f0f0f0; cursor: pointer;
        }
        .layer-row:last-child { border-bottom: none; }
        .layer-row input[type=checkbox] { accent-color: #667eea; cursor: pointer; }
        .layer-dot {
            width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0;
        }

        /* Panel de datos seleccionados */
        .data-panel {
            flex-shrink: 0;
            border-top: 2px solid #667eea;
            padding: 10px 12px;
            background: #f0f3ff;
            display: none;
            max-height: 160px;
            overflow-y: auto;
        }
        .data-panel.visible { display: block; }
        .data-panel h4 { margin: 0 0 6px; font-size: 11px; color: #667eea; text-transform: uppercase; letter-spacing: .5px; }
        .data-row { font-size: 11px; color: #333; padding: 2px 0; display: flex; gap: 6px; }
        .data-row strong { color: #1B3B6F; }

        /* Footer navegación */
        .sidebar-footer {
            flex-shrink: 0;
            border-top: 1px solid #e0e4ef;
            padding: 10px 12px;
            background: #f8f9fc;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .sidebar-footer button {
            font-size: 11px; padding: 6px 10px; border: none; border-radius: 6px;
            cursor: pointer; font-weight: 600; flex: 1; min-width: 100px;
        }
        .btn-mapa    { background: #667eea; color: white; }
        .btn-loc     { background: #27ae60; color: white; }
        .btn-auth    { background: #e8eaf6; color: #1B3B6F; }
        .btn-logout  { background: #fdecea; color: #e53e3e; }
        .btn-nueva   { background: #1B3B6F; color: white; }
        .btn-admin   { background: #7c3aed; color: white; }  /* solo visible para admin */

        /* Mapa */
        #map { flex: 1; height: 100%; }

        /* Toggle mobile */
        #togglePanel {
            display: none;
            position: fixed;
            top: 10px; left: 10px;
            z-index: 1001;
            background: #1B3B6F; color: white;
            border: none; border-radius: 8px;
            padding: 8px 14px; font-size: 13px; font-weight: 700;
            cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,.2);
        }

        @media (max-width: 768px) {
            #sidebar {
                position: fixed; top: 0; left: 0;
                height: 100vh; z-index: 1000;
                transform: translateX(-100%);
                transition: transform .25s;
            }
            #sidebar.active { transform: translateX(0); }
            #togglePanel { display: block; }
            #map { width: 100%; }
        }
    </style>
</head>
<body>

<button id="togglePanel" onclick="toggleSidebar()">☰ Marcas</button>

<div id="sidebar">
    <!-- Header -->
    <div class="sidebar-header">
        <h2>🏷️ Mapa de Marcas</h2>
        <input type="text" id="busqueda" placeholder="🔍 Buscar marca por nombre o rubro..." oninput="filtrar()">
        <span id="search-count"></span>
    </div>

    <!-- Controles de selección -->
    <div class="sel-controls">
        <button onclick="seleccionarTodas()">✔ Todas</button>
        <button onclick="deseleccionarTodas()">✕ Ninguna</button>
        <button onclick="invertirSeleccion()">⇄ Invertir</button>
        <span id="sel-count" class="none">0 sel.</span>
    </div>

    <!-- Lista de marcas -->
    <div id="lista"></div>

    <!-- Panel de datos de marcas seleccionadas -->
    <div class="data-panel" id="dataPanel">
        <h4>📊 Datos de selección</h4>
        <div id="dataPanelContent"></div>
    </div>

    <!-- Capas -->
    <div class="layer-box">
        <h4>Capas visibles</h4>
        <label class="layer-row" onclick="filtrar()">
            <input type="checkbox" id="layer-niza" checked onchange="filtrar()">
            <span class="layer-dot" style="background:#3498db"></span>
            Clasificación Niza
        </label>
        <label class="layer-row">
            <input type="checkbox" id="layer-influencia" onchange="filtrar()">
            <span class="layer-dot" style="background:#27ae60; opacity:.5"></span>
            Zona de Influencia
        </label>
        <label class="layer-row">
            <input type="checkbox" id="layer-valor" onchange="filtrar()">
            <span class="layer-dot" style="background:#f1c40f"></span>
            Valor de Marca
        </label>
        <label class="layer-row">
            <input type="checkbox" id="layer-riesgo" onchange="filtrar()">
            <span class="layer-dot" style="background:#e74c3c"></span>
            Riesgo Legal
        </label>
    </div>

    <!-- Footer -->
    <div class="sidebar-footer">
        <button class="btn-mapa"  onclick="location.href='/'">🗺️ Mapa principal</button>
        <button class="btn-loc"   onclick="ubicarme()">📍 Mi ubicación</button>
        <?php if (!isset($_SESSION['user_id'])): ?>
            <button class="btn-auth"  onclick="location.href='/login'">👤 Iniciar Sesión</button>
        <?php else: ?>
            <button class="btn-nueva" onclick="location.href='/brand_form'">➕ Nueva Marca</button>
            <?php if (!empty($_SESSION['is_admin'])): ?>
            <button class="btn-admin" onclick="location.href='/admin'">⚙️ Panel Admin</button>
            <?php endif; ?>
            <button class="btn-logout" onclick="location.href='/logout'">🚪 Salir</button>
        <?php endif; ?>
    </div>
</div>

<div id="map"></div>

<script>
// Variables de sesión disponibles para el JS
const SESSION_USER_ID = <?= (int)($_SESSION['user_id'] ?? 0) ?>;
const IS_ADMIN        = <?= !empty($_SESSION['is_admin']) ? 'true' : 'false' ?>;
const GLOBAL_ICON_BOOST = <?= json_encode(round($_mapGlobalIconBoost, 2)) ?>;

let marcas      = [];
let mapa, marcadores = [], circulos = [];
let selectedIds = new Set();   // IDs de marcas seleccionadas
let listaFiltrada = [];        // marcas visibles según búsqueda

const nizaColors = {
    '1':'#e74c3c','2':'#e67e22','3':'#f1c40f','4':'#2ecc71','5':'#1abc9c',
    '6':'#3498db','7':'#9b59b6','8':'#34495e','9':'#16a085','10':'#8e44ad',
    '11':'#d35400','12':'#27ae60','13':'#2980b9','14':'#8e44ad','15':'#c0392b'
};

function getNizaClass(cls) {
    if (!cls) return '14';
    return cls.split(',')[0].trim();
}

// ── Selección ────────────────────────────────────────────────────────────────
function toggleSeleccion(id, cb) {
    if (cb.checked) {
        selectedIds.add(id);
    } else {
        selectedIds.delete(id);
    }
    actualizarSelCount();
    renderLista();   // actualiza clases selected
    actualizarMapa();
    mostrarDataPanel();
}

function seleccionarTodas() {
    listaFiltrada.forEach(m => selectedIds.add(m.id));
    renderLista();
    actualizarSelCount();
    actualizarMapa();
    mostrarDataPanel();
}

function deseleccionarTodas() {
    selectedIds.clear();
    renderLista();
    actualizarSelCount();
    actualizarMapa();
    mostrarDataPanel();
}

function invertirSeleccion() {
    listaFiltrada.forEach(m => {
        if (selectedIds.has(m.id)) selectedIds.delete(m.id);
        else selectedIds.add(m.id);
    });
    renderLista();
    actualizarSelCount();
    actualizarMapa();
    mostrarDataPanel();
}

function actualizarSelCount() {
    const el = document.getElementById('sel-count');
    const n  = selectedIds.size;
    el.textContent = n + ' sel.';
    el.className   = n > 0 ? '' : 'none';
}

// ── Normalizar texto (minúsculas + sin tildes/acentos) ───────────────────────
function normalizar(s) {
    return (s || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
}

// ── Formatear valor activo de marca (evita doble símbolo "$$") ───────────────
/**
 * Formatea un valor de activo marcario para mostrar un único símbolo de peso.
 * Elimina cualquier "$" inicial para evitar doble símbolo cuando el valor
 * ya viene formateado desde la base de datos.
 * @param {string|number} v - Valor del activo (ej: "50000", "$50000", 50000)
 * @returns {string} Valor formateado con un solo "$" (ej: "$50,000"),
 *                   o cadena vacía si el valor está vacío.
 */
function fmtValor(v) {
    const s = String(v).replace(/^\s*\$\s*/, '');
    if (s === '') return '';
    const n = Number(s);
    return '$' + (isNaN(n) ? s : n.toLocaleString());
}

// ── Filtrar por búsqueda ─────────────────────────────────────────────────────
function filtrar() {
    const texto = normalizar(document.getElementById('busqueda').value);
    listaFiltrada = marcas.filter(m => {
        if (!texto) return true;
        const nombre = normalizar(m.name || m.nombre);
        const rubro  = normalizar(m.rubro);
        const ubic   = normalizar(m.ubicacion);
        return nombre.includes(texto) || rubro.includes(texto) || ubic.includes(texto);
    });

    const counter = document.getElementById('search-count');
    if (counter) {
        if (texto) {
            counter.textContent = `${listaFiltrada.length} resultado${listaFiltrada.length !== 1 ? 's' : ''}`;
            counter.style.display = 'block';
        } else {
            counter.style.display = 'none';
        }
    }

    renderLista();
    actualizarMapa();
    mostrarDataPanel();
}

// ── Renderizar lista ─────────────────────────────────────────────────────────
function renderLista() {
    const contenedor = document.getElementById('lista');
    contenedor.innerHTML = '';

    if (listaFiltrada.length === 0) {
        contenedor.innerHTML = '<p style="color:#aaa;font-size:12px;text-align:center;padding:20px 0;">Sin resultados</p>';
        return;
    }

    listaFiltrada.forEach(m => {
        const esSel = selectedIds.has(m.id);
        const div   = document.createElement('div');
        div.className = 'marca-item' + (esSel ? ' selected' : '');
        div.dataset.id = m.id;

        let tags = '';
        if (m.clase_principal) tags += `<span class="tag tag-niza">Niza ${m.clase_principal}</span>`;
        if (m.valor_activo)    tags += `<span class="tag tag-valor">${fmtValor(m.valor_activo)}</span>`;
        if (m.riesgo_oposicion)    tags += `<span class="tag tag-riesgo">⚠ ${m.riesgo_oposicion}</span>`;

        div.innerHTML = `
            <input type="checkbox" ${esSel ? 'checked' : ''}
                   onclick="event.stopPropagation(); toggleSeleccion(${m.id}, this)">
            <div class="marca-info">
                <div class="marca-nombre">${m.name || m.nombre || ''}</div>
                ${m.rubro ? `<div class="marca-rubro">${m.rubro}${m.ubicacion ? ' · ' + m.ubicacion : ''}</div>` : ''}
                ${tags ? `<div class="marca-tags">${tags}</div>` : ''}
            </div>
            ${m.lat && m.lng ? `<span class="marca-center" title="Centrar mapa" onclick="event.stopPropagation(); centrarEn(${m.lat}, ${m.lng})">📍</span>` : ''}
        `;

        // Click en la card → seleccionar/deseleccionar
        div.addEventListener('click', () => {
            const cb = div.querySelector('input[type=checkbox]');
            cb.checked = !cb.checked;
            toggleSeleccion(m.id, cb);
        });

        contenedor.appendChild(div);
    });
}

// ── Centrar mapa en una marca ────────────────────────────────────────────────
function centrarEn(lat, lng) {
    if (mapa) mapa.setView([lat, lng], 15);
}

// ── Actualizar marcadores en el mapa ─────────────────────────────────────────
function actualizarMapa() {
    if (!mapa) return;

    const showNiza       = document.getElementById('layer-niza').checked;
    const showInfluencia = document.getElementById('layer-influencia').checked;

    // Limpiar
    marcadores.forEach(m => mapa.removeLayer(m));
    circulos.forEach(c => mapa.removeLayer(c));
    marcadores = [];
    circulos   = [];

    // ¿Hay selección activa?
    const haySeleccion = selectedIds.size > 0;

    // Mostrar todas las filtradas; si hay selección, resaltar las seleccionadas
    listaFiltrada.forEach(m => {
        if (!m.lat || !m.lng) return;

        const esSel  = selectedIds.has(m.id);
        const activa = !haySeleccion || esSel;  // sin selección → todas activas

        const nizaKey = getNizaClass(m.clase_principal);
        const color   = showNiza ? (nizaColors[nizaKey] || '#3498db') : '#667eea';

        let marker;
        if (m.logo_url && activa) {
            // ── Icono de foto circular ──────────────────────────────────────
            const size = Math.round(40 * GLOBAL_ICON_BOOST);
            const html = `<div style="
                width:${size}px;height:${size}px;border-radius:50%;
                border:3px solid ${color};
                box-shadow:0 2px 8px rgba(0,0,0,.3);
                background:url('${m.logo_url}') center/cover no-repeat #fff;
                opacity:1;transition:all .2s;"></div>`;
            marker = L.marker([m.lat, m.lng], {
                icon: L.divIcon({
                    html, className: '',
                    iconSize: [size, size], iconAnchor: [size/2, size], popupAnchor: [0, -size-4]
                })
            }).addTo(mapa);
        } else if (m.logo_url && !activa) {
            // Inactiva con logo → pequeño y gris
            const sizeInact = Math.round(26 * GLOBAL_ICON_BOOST);
            const html = `<div style="
                width:${sizeInact}px;height:${sizeInact}px;border-radius:50%;
                border:2px solid #ddd;
                background:url('${m.logo_url}') center/cover no-repeat #eee;
                opacity:0.4;filter:grayscale(1);"></div>`;
            marker = L.marker([m.lat, m.lng], {
                icon: L.divIcon({
                    html, className: '',
                    iconSize: [sizeInact, sizeInact], iconAnchor: [Math.round(sizeInact/2), sizeInact], popupAnchor: [0, -(sizeInact+2)]
                })
            }).addTo(mapa);
        } else {
            // ── Círculo de color (sin logo) ─────────────────────────────────
            marker = L.circleMarker([m.lat, m.lng], {
                radius:      activa ? Math.round(11 * GLOBAL_ICON_BOOST) : Math.round(7 * GLOBAL_ICON_BOOST),
                fillColor:   activa ? color : '#ccc',
                color:       activa ? '#fff' : '#ddd',
                weight:      activa ? 2 : 1,
                fillOpacity: activa ? 0.85 : 0.4,
            }).addTo(mapa);
        }

        const canEdit = IS_ADMIN || (SESSION_USER_ID && SESSION_USER_ID == m.user_id);

        let popup = `<div style="min-width:190px;max-width:240px;font-family:inherit">`;
        popup += `<strong style="font-size:14px;display:block;margin-bottom:4px">${m.name || m.nombre}</strong>`;
        if (m.rubro || m.clase_principal) {
            popup += `<div style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:6px">`;
            if (m.rubro)           popup += `<span style="background:#f0ecff;color:#6a2fa2;padding:2px 7px;border-radius:8px;font-size:11px;font-weight:600">🏷️ ${m.rubro}</span>`;
            if (m.clase_principal) popup += `<span style="background:#e0f2fe;color:#0369a1;padding:2px 7px;border-radius:8px;font-size:11px;font-weight:600">📋 Niza ${m.clase_principal}</span>`;
            popup += `</div>`;
        }
        if (m.valor_activo)      popup += `<div style="font-size:12px;color:#059669;font-weight:700;margin-bottom:3px">💰 ${fmtValor(m.valor_activo)}</div>`;
        if (m.riesgo_oposicion)  popup += `<div style="font-size:12px;margin-bottom:3px">⚠️ Riesgo: ${m.riesgo_oposicion}</div>`;
        if (m.ubicacion)         popup += `<div style="font-size:12px;color:#666;margin-bottom:6px">📍 ${m.ubicacion}</div>`;
        popup += `<div style="display:flex;gap:5px;margin-top:6px">`;
        popup += `<a href="/brand_detail?id=${m.id}" style="padding:6px 8px;background:#667eea;color:white;border-radius:7px;font-size:16px;text-decoration:none;line-height:1" title="Ver detalle de la marca" aria-label="Ver detalle de la marca">📋</a>`;
        if (canEdit) {
            popup += `<a href="/brand_edit?id=${m.id}" style="padding:6px 8px;background:#0ea5e9;color:white;border-radius:7px;font-size:16px;text-decoration:none;line-height:1" title="Editar marca" aria-label="Editar marca">✏️</a>`;
        }
        popup += `</div></div>`;
        marker.bindPopup(popup, { maxWidth: 240 });

        marcadores.push(marker);

        // Zona de influencia solo para activas
        if (showInfluencia && activa && m.clase_principal) {
            const radioKm = parseInt(nizaKey) * 2;
            const c = L.circle([m.lat, m.lng], {
                radius: radioKm * 1000,
                fillColor: color, color: color,
                fillOpacity: 0.08, weight: 1, dashArray: '4,4'
            }).addTo(mapa);
            circulos.push(c);
        }
    });
}

// ── Panel de datos de marcas seleccionadas ───────────────────────────────────
function mostrarDataPanel() {
    const panel   = document.getElementById('dataPanel');
    const content = document.getElementById('dataPanelContent');
    const showValor  = document.getElementById('layer-valor').checked;
    const showRiesgo = document.getElementById('layer-riesgo').checked;

    if (selectedIds.size === 0 || (!showValor && !showRiesgo)) {
        panel.classList.remove('visible');
        return;
    }

    const seleccionadas = marcas.filter(m => selectedIds.has(m.id));
    let html = '';

    seleccionadas.forEach(m => {
        let lineas = [`<strong>${m.name || m.nombre || ''}</strong>`];
        if (showValor  && m.valor_activo) lineas.push(`💰 Valor: <strong>${fmtValor(m.valor_activo)}</strong>`);
        if (showRiesgo && m.riesgo_oposicion) lineas.push(`⚠️ Riesgo legal: <strong>${m.riesgo_oposicion}</strong>`);
        if (showRiesgo && m.extended_description) lineas.push(`<span style="color:#888">${m.extended_description}</span>`);
        html += `<div class="data-row" style="flex-direction:column;border-bottom:1px solid #dde0ee;padding-bottom:6px;margin-bottom:6px;">${lineas.join('<br>')}</div>`;
    });

    content.innerHTML = html;
    panel.classList.add('visible');
}

// ── Toggle sidebar mobile ─────────────────────────────────────────────────────
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
}

// ── Geolocalización ───────────────────────────────────────────────────────────
function ubicarme() {
    if (!navigator.geolocation) { alert('Tu navegador no soporta geolocalización'); return; }
    navigator.geolocation.getCurrentPosition(pos => {
        L.circleMarker([pos.coords.latitude, pos.coords.longitude], {
            radius: 8, fillColor: '#3498db', color: '#fff', weight: 2, fillOpacity: 0.9
        }).addTo(mapa).bindPopup('📍 Estás aquí').openPopup();
        mapa.setView([pos.coords.latitude, pos.coords.longitude], 13);
    });
}

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    fetch('/api/brands.php')
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data) {
                marcas        = res.data;
                listaFiltrada = marcas;

                mapa = L.map('map').setView([-34.6037, -58.3816], 12);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(mapa);

                renderLista();
                actualizarMapa();
            }
        })
        .catch(err => console.error('Error cargando marcas:', err));

    // Re-aplicar capas al cambiar checkboxes
    ['layer-niza','layer-influencia','layer-valor','layer-riesgo'].forEach(id => {
        document.getElementById(id).addEventListener('change', () => {
            actualizarMapa();
            mostrarDataPanel();
        });
    });
});
</script>
</body>
</html>
