<?php
/**
 * Mapita Admin Panel
 * Panel de administración - Noticias, Eventos, Trivias, Encuestas
 * Con soporte completo de geolocalización (lat/lng + mini-mapas)
 */

session_start();
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';

$validTabs = ['negocios','marcas','noticias','eventos','trivias','encuestas','ofertas','transmisiones','moderacion'];
$tab = in_array($_GET['tab'] ?? '', $validTabs) ? $_GET['tab'] : 'negocios';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🛠️ Panel de Administración - Mapita</title>

    <!-- Leaflet para mini-mapas -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- Sistema de diseño -->
    <link rel="stylesheet" href="/css/variables-luxury.css">
    <link rel="stylesheet" href="/css/components-buttons.css">
    <link rel="stylesheet" href="/css/components-cards.css">
    <link rel="stylesheet" href="/css/components-forms.css">

    <style>
        body {
            background-color: var(--bg-tertiary);
            color: var(--text-primary);
            font-family: var(--font-family-base);
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: var(--space-lg);
        }
        header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--text-inverse);
            padding: var(--space-xl);
            margin-bottom: var(--space-xl);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
        }
        header h1 { font-size: var(--font-size-2xl); margin-bottom: var(--space-sm); }
        header p  { opacity: 0.9; font-size: var(--font-size-sm); }

        /* ── Tabs ──────────────────────────────────── */
        .tabs {
            display: flex;
            gap: var(--space-sm);
            margin-bottom: var(--space-xl);
            border-bottom: var(--border-width-normal) solid var(--color-gray-200);
            overflow-x: auto;
            padding-bottom: var(--space-xs);
        }
        .tab-btn {
            padding: var(--space-md) var(--space-lg);
            border: none;
            background: none;
            color: var(--text-secondary);
            cursor: pointer;
            font-weight: var(--font-weight-semibold);
            font-size: var(--font-size-sm);
            border-bottom: 3px solid transparent;
            transition: all var(--transition-base);
            white-space: nowrap;
        }
        .tab-btn.active       { color: var(--primary); border-bottom-color: var(--primary); }
        .tab-btn:hover:not(.active) {
            color: var(--primary-light);
            background: rgba(102,126,234,0.05);
        }
        .tab-content          { display: none; }
        .tab-content.active   { display: block; }

        /* ── Section header ────────────────────────── */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-lg);
            padding-bottom: var(--space-sm);
            border-bottom: var(--border-width-thin) solid var(--color-gray-200);
        }

        /* ── Admin cards ───────────────────────────── */
        .admin-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-md);
            padding: var(--space-md) var(--space-lg);
            background: white;
            border-radius: var(--border-radius-md);
            box-shadow: var(--shadow-sm);
            border-left: 4px solid transparent;
        }
        .admin-card.has-geo   { border-left-color: var(--success); }
        .admin-card.no-geo    { border-left-color: var(--color-gray-300); }
        .card-content h3 {
            color: var(--primary);
            margin: 0 0 var(--space-xs);
            font-size: var(--font-size-md);
        }
        .card-meta {
            display: flex;
            gap: var(--space-md);
            flex-wrap: wrap;
            font-size: var(--font-size-xs);
            color: var(--text-tertiary);
            margin-top: var(--space-sm);
        }
        .card-meta .geo-badge {
            background: var(--success);
            color: white;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 11px;
        }
        .card-meta .no-geo-badge {
            background: var(--color-gray-300);
            color: var(--text-secondary);
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 11px;
        }
        .card-meta .yt-badge {
            background: #ff0000;
            color: white;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 11px;
        }

        /* ── Modal ─────────────────────────────────── */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: var(--bg-overlay);
            z-index: var(--z-modal);
            justify-content: center;
            align-items: flex-start;
            overflow-y: auto;
            padding: var(--space-xl) var(--space-md);
            backdrop-filter: blur(4px);
        }
        .modal.active         { display: flex; }
        .modal-content {
            width: 100%;
            max-width: 680px;
            background: white;
            border-radius: var(--border-radius-lg);
            padding: var(--space-xl);
            box-shadow: var(--shadow-xl);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--color-gray-100);
            margin-bottom: var(--space-lg);
            padding-bottom: var(--space-sm);
        }
        .modal-header h3 { margin: 0; color: var(--primary); }
        .modal-close {
            background: none;
            border: none;
            font-size: 1.6rem;
            cursor: pointer;
            color: var(--text-tertiary);
            line-height: 1;
        }
        .modal-footer {
            display: flex;
            gap: var(--space-md);
            justify-content: flex-end;
            padding-top: var(--space-lg);
            margin-top: var(--space-lg);
            border-top: 1px solid var(--color-gray-100);
        }

        /* ── Mini-mapa ─────────────────────────────── */
        .mini-map-wrapper {
            border: 2px solid var(--color-gray-200);
            border-radius: var(--border-radius-md);
            overflow: hidden;
            margin-top: var(--space-sm);
        }
        .mini-map {
            height: 220px;
            width: 100%;
        }
        .mini-map-hint {
            font-size: var(--font-size-xs);
            color: var(--text-tertiary);
            margin-top: var(--space-xs);
        }
        .coords-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-sm);
            margin-top: var(--space-sm);
        }

        /* ── YouTube preview ───────────────────────── */
        .yt-preview {
            display: none;
            margin-top: var(--space-sm);
            border-radius: var(--border-radius-md);
            overflow: hidden;
            aspect-ratio: 16/9;
        }
        .yt-preview iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        /* ── Badge ─────────────────────────────────── */
        .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger  { background: #f8d7da; color: #721c24; }

        /* ── Responsive ────────────────────────────── */
        @media (max-width: 768px) {
            .admin-card { flex-direction: column; align-items: flex-start; gap: var(--space-sm); }
            .card-actions { width: 100%; display: flex; gap: var(--space-sm); }
            .card-actions .btn { flex: 1; }
            .coords-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="container">
    <header style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
        <div>
            <h1>🛠️ Panel de Administración</h1>
            <p>Gestiona noticias, eventos, trivias, encuestas, ofertas y transmisiones en vivo</p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <a href="/" style="background:rgba(255,255,255,.2);color:white;padding:9px 18px;border-radius:8px;font-size:.85em;font-weight:700;text-decoration:none;border:2px solid rgba(255,255,255,.4);">🗺️ Ir al Mapa</a>
            <a href="/mis-negocios" style="background:rgba(255,255,255,.15);color:white;padding:9px 18px;border-radius:8px;font-size:.85em;font-weight:700;text-decoration:none;border:2px solid rgba(255,255,255,.3);">📋 Mis Negocios</a>
        </div>
    </header>

    <!-- Tabs -->
    <div class="tabs">
        <button id="tab-btn-negocios"       class="tab-btn <?php echo $tab==='negocios'       ? 'active' : ''; ?>" onclick="switchTab('negocios')">🏢 Negocios</button>
        <button id="tab-btn-marcas"         class="tab-btn <?php echo $tab==='marcas'         ? 'active' : ''; ?>" onclick="switchTab('marcas')">🏷️ Marcas</button>
        <button id="tab-btn-noticias"       class="tab-btn <?php echo $tab==='noticias'       ? 'active' : ''; ?>" onclick="switchTab('noticias')">📰 Noticias</button>
        <button id="tab-btn-eventos"        class="tab-btn <?php echo $tab==='eventos'        ? 'active' : ''; ?>" onclick="switchTab('eventos')">📅 Eventos</button>
        <button id="tab-btn-trivias"        class="tab-btn <?php echo $tab==='trivias'        ? 'active' : ''; ?>" onclick="switchTab('trivias')">🎯 Trivias</button>
        <button id="tab-btn-encuestas"      class="tab-btn <?php echo $tab==='encuestas'      ? 'active' : ''; ?>" onclick="switchTab('encuestas')">📋 Encuestas</button>
        <button id="tab-btn-ofertas"        class="tab-btn <?php echo $tab==='ofertas'        ? 'active' : ''; ?>" onclick="switchTab('ofertas')">🏷️ Ofertas</button>
        <button id="tab-btn-transmisiones"  class="tab-btn <?php echo $tab==='transmisiones'  ? 'active' : ''; ?>" onclick="switchTab('transmisiones')">📡 En Vivo</button>
        <button id="tab-btn-moderacion"     class="tab-btn <?php echo $tab==='moderacion'     ? 'active' : ''; ?>" onclick="switchTab('moderacion')">🚨 Moderación</button>
    </div>

    <!-- NEGOCIOS -->
    <div class="tab-content <?php echo $tab==='negocios' ? 'active' : ''; ?>" id="tab-negocios">
        <div class="section-header">
            <h2>🏢 Gestión de Negocios</h2>
            <a href="/add" class="btn btn-primary" style="text-decoration:none;">+ Nuevo Negocio</a>
        </div>
        <div style="margin-bottom:16px;display:flex;gap:10px;align-items:center;">
            <input type="text" id="search-negocios" placeholder="🔍 Buscar por nombre, dirección o tipo…"
                   oninput="filtrarNegocios(this.value)"
                   style="flex:1;padding:10px 14px;border:1px solid var(--color-gray-300);border-radius:8px;font-size:14px;">
            <span id="count-negocios" style="font-size:12px;color:var(--text-tertiary);white-space:nowrap;"></span>
        </div>
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:12px;margin-bottom:16px;">
            <strong style="font-size:13px;color:#1f2937;">🔗 Relaciones por Mapita ID</strong>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:8px;">
                <input type="text" id="rel-source-mapita" placeholder="Origen (ej: NEG-001)" style="padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;">
                <input type="text" id="rel-target-mapita" placeholder="Destino (ej: BR-015)" style="padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-top:8px;">
                <select id="rel-type" style="padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;background:white;">
                    <option value="relacionado">Relacionado</option>
                    <option value="FRANQUICIA">FRANQUICIA</option>
                    <option value="AGENCIA">AGENCIA</option>
                    <option value="REPRESENTANTE">REPRESENTANTE</option>
                    <option value="SOCIEDAD">SOCIEDAD</option>
                    <option value="aliado">Aliado</option>
                    <option value="sucursal">Sucursal</option>
                    <option value="distribuidor">Distribuidor</option>
                    <option value="proveedor">Proveedor</option>
                </select>
                <input type="text" id="rel-desc" placeholder="Descripción (opcional)" style="padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;">
                <button type="button" class="btn btn-primary" onclick="crearRelacionPorMapita()" style="width:100%;">Crear relación</button>
            </div>
            <div id="rel-search-result" style="margin-top:8px;font-size:12px;color:#6b7280;"></div>
        </div>
        <!-- Bulk import: Negocios -->
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px;margin-bottom:16px;">
            <strong style="font-size:13px;color:#065f46;">📥 Importar en masa (JSON)</strong>
            <p style="font-size:12px;color:#374151;margin:4px 0 8px;">Generá el JSON con una IA usando el prompt de <code>admin/bulk_import_templates.md</code> y subilo acá.</p>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <input type="file" id="bulk-file-negocios" accept=".json" style="font-size:13px;">
                <button type="button" class="btn btn-primary" style="font-size:13px;" onclick="bulkImport('businesses','bulk-file-negocios','bulk-result-negocios')">Importar Negocios</button>
            </div>
            <div id="bulk-result-negocios" style="margin-top:8px;font-size:12px;"></div>
        </div>
        <div id="negocios-list"></div>
    </div>

    <!-- MARCAS -->
    <div class="tab-content <?php echo $tab==='marcas' ? 'active' : ''; ?>" id="tab-marcas">
        <div class="section-header">
            <h2>🏷️ Gestión de Marcas</h2>
            <a href="/brand_form" class="btn btn-primary" style="text-decoration:none;">+ Nueva Marca</a>
        </div>
        <div style="margin-bottom:16px;display:flex;gap:10px;align-items:center;">
            <input type="text" id="search-marcas" placeholder="🔍 Buscar por nombre o rubro…"
                   oninput="filtrarMarcas(this.value)"
                   style="flex:1;padding:10px 14px;border:1px solid var(--color-gray-300);border-radius:8px;font-size:14px;">
            <span id="count-marcas" style="font-size:12px;color:var(--text-tertiary);white-space:nowrap;"></span>
        </div>
        <!-- Bulk import: Marcas -->
        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px;margin-bottom:16px;">
            <strong style="font-size:13px;color:#1e40af;">📥 Importar en masa (JSON)</strong>
            <p style="font-size:12px;color:#374151;margin:4px 0 8px;">Generá el JSON con una IA usando el prompt de <code>admin/bulk_import_templates.md</code> y subilo acá.</p>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <input type="file" id="bulk-file-marcas" accept=".json" style="font-size:13px;">
                <button type="button" class="btn btn-primary" style="font-size:13px;" onclick="bulkImport('brands','bulk-file-marcas','bulk-result-marcas')">Importar Marcas</button>
            </div>
            <div id="bulk-result-marcas" style="margin-top:8px;font-size:12px;"></div>
        </div>
        <div id="marcas-list"></div>
    </div>

    <!-- NOTICIAS -->
    <div class="tab-content <?php echo $tab==='noticias' ? 'active' : ''; ?>" id="tab-noticias">
        <div class="section-header">
            <h2>📰 Noticias y Artículos</h2>
            <button class="btn btn-primary" onclick="openModal('noticia')">+ Nueva Noticia</button>
        </div>
        <div id="noticias-list"></div>
    </div>

    <!-- EVENTOS -->
    <div class="tab-content <?php echo $tab==='eventos' ? 'active' : ''; ?>" id="tab-eventos">
        <div class="section-header">
            <h2>📅 Eventos y Promociones</h2>
            <button class="btn btn-primary" onclick="openModal('evento')">+ Nuevo Evento</button>
        </div>
        <div id="eventos-list"></div>
    </div>

    <!-- TRIVIAS -->
    <div class="tab-content <?php echo $tab==='trivias' ? 'active' : ''; ?>" id="tab-trivias">
        <div class="section-header">
            <h2>🎯 Trivias</h2>
            <button class="btn btn-primary" onclick="openModal('trivia')">+ Nueva Trivia</button>
        </div>
        <div id="trivias-list"></div>
    </div>

    <!-- ENCUESTAS -->
    <div class="tab-content <?php echo $tab==='encuestas' ? 'active' : ''; ?>" id="tab-encuestas">
        <div class="section-header">
            <h2>📋 Encuestas Georreferenciadas</h2>
            <button class="btn btn-primary" onclick="openModal('encuesta')">+ Nueva Encuesta</button>
        </div>
        <div id="encuestas-list"></div>
    </div>

    <!-- OFERTAS -->
    <div class="tab-content <?php echo $tab==='ofertas' ? 'active' : ''; ?>" id="tab-ofertas">
        <div class="section-header">
            <h2>🏷️ Ofertas y Descuentos</h2>
            <button class="btn btn-primary" onclick="openModal('oferta')">+ Nueva Oferta</button>
        </div>
        <div id="ofertas-list"></div>
    </div>

    <!-- TRANSMISIONES -->
    <div class="tab-content <?php echo $tab==='transmisiones' ? 'active' : ''; ?>" id="tab-transmisiones">
        <div class="section-header">
            <h2>📡 Transmisiones en Vivo</h2>
            <button class="btn btn-primary" onclick="openModal('transmision')">+ Nueva Transmisión</button>
        </div>
        <div style="background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:12px;margin-bottom:16px;font-size:13px;">
            ⚠️ <strong>Requiere migración:</strong> Ejecutar <code>migration/001_transmisiones.sql</code> en la base de datos antes de usar este módulo.
        </div>
        <div id="transmisiones-list"></div>
    </div>

    <!-- MODERACIÓN -->
    <div class="tab-content <?php echo $tab==='moderacion' ? 'active' : ''; ?>" id="tab-moderacion">
        <div class="section-header">
            <h2>🚨 Moderación y Seguridad</h2>
            <span id="pending-badge" style="display:none;background:#ef4444;color:white;padding:3px 10px;border-radius:9999px;font-size:12px;font-weight:700;"></span>
        </div>

        <!-- Filtros -->
        <div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;align-items:center;">
            <label style="font-size:13px;font-weight:600;">Estado:</label>
            <select id="mod-status-filter" onchange="loadReports()" style="padding:8px 12px;border:1px solid #d1d5db;border-radius:6px;background:white;font-size:13px;">
                <option value="pending">⏳ Pendientes</option>
                <option value="reviewing">🔍 En revisión</option>
                <option value="resolved">✅ Resueltos</option>
                <option value="dismissed">🚫 Descartados</option>
                <option value="all">📋 Todos</option>
            </select>
            <button class="btn btn-secondary" style="font-size:13px;" onclick="loadReports()">🔄 Actualizar</button>
        </div>

        <div id="reports-list"></div>

        <hr style="margin:32px 0;border-color:#e5e7eb;">

        <!-- Log de auditoría -->
        <div class="section-header" style="margin-bottom:12px;">
            <h3 style="margin:0;">🔍 Log de Auditoría <span style="font-size:13px;font-weight:400;color:#6b7280;">(últimas 100 acciones)</span></h3>
            <button class="btn btn-secondary" style="font-size:13px;" onclick="loadAuditLog()">🔄 Actualizar</button>
        </div>
        <div id="audit-log-list"></div>
    </div>
</div>

<!-- ── MODAL ─────────────────────────────────── -->
<div class="modal" id="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modal-title">Crear</h3>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <form id="form" onsubmit="handleSubmit(event)">
            <div id="form-content"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btn-submit">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentType = null;
let editId = null;
let miniMap = null;
let miniMapMarker = null;
let miniMapDest = null;
let miniMapDestMarker = null;

// ── Tab management ──────────────────────────────
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    document.getElementById('tab-btn-' + tab).classList.add('active');
    if (tab === 'marcas')         loadMarcas();
    else if (tab === 'negocios')  loadNegocios();
    else if (tab === 'moderacion') { loadReports(); loadAuditLog(); }
    else                          loadData(tab);
    window.history.pushState({tab}, '', '?tab=' + tab);
}

// ── Load data ───────────────────────────────────
async function loadData(type) {
    const el = document.getElementById(type + '-list');
    if (!el) return;
    el.innerHTML = '<p style="color:var(--text-tertiary);padding:16px;">⏳ Cargando...</p>';
    try {
        const endpoints = {
            noticias:       '/api/noticias.php',
            eventos:        '/api/eventos.php',
            trivias:        '/api/trivias.php',
            encuestas:      '/api/encuestas.php',
            ofertas:        '/api/ofertas.php',
            transmisiones:  '/api/transmisiones.php'
        };
        const res = await fetch(endpoints[type]);
        const result = await res.json();
        if (!result.success) {
            el.innerHTML = `<div class="alert alert-error">Error cargando ${type}</div>`;
            return;
        }
        renderList(type, result.data || []);
    } catch(err) {
        el.innerHTML = `<div class="alert alert-error">Error de red: ${err.message}</div>`;
    }
}

// ── Render list ─────────────────────────────────
function renderList(type, items) {
    const container = document.getElementById(type + '-list');
    if (!items.length) {
        container.innerHTML = `
            <div style="text-align:center;padding:40px;color:var(--text-tertiary);">
                <p style="font-size:2rem;">📭</p>
                <p>No hay ${type} creados aún</p>
                <button class="btn btn-primary" onclick="openModal('${type.slice(0,-1)}')">Crear primero</button>
            </div>`;
        return;
    }
    container.innerHTML = items.map(item => {
        const isActive = item.activa !== 0 && item.activo !== 0;
        const hasGeo   = (item.lat && item.lng);
        const hasYT    = item.youtube_link;
        const title    = item.titulo || item.nombre || '(sin título)';
        const desc     = (item.descripcion || item.contenido || '').substring(0, 120);
        const date     = item.fecha_publicacion || item.fecha || item.fecha_creacion || '';
        const geoLabel = hasGeo
            ? `<span class="geo-badge">📍 ${parseFloat(item.lat).toFixed(4)}, ${parseFloat(item.lng).toFixed(4)}</span>`
            : `<span class="no-geo-badge">Sin ubicación</span>`;
        const ytLabel = hasYT ? `<span class="yt-badge">▶ YouTube</span>` : '';
        const linkLabel = item.link ? `<span class="geo-badge" style="background:#3b82f6">🔗 Link</span>` : '';

        return `
        <div class="admin-card ${hasGeo ? 'has-geo' : 'no-geo'}">
            <div class="card-content">
                <h3>${title}</h3>
                <p style="font-size:var(--font-size-sm);color:var(--text-secondary);margin:0 0 4px;">${desc}${desc.length===120?'…':''}</p>
                <div class="card-meta">
                    ${date ? `<span>📅 ${date}</span>` : ''}
                    <span class="badge ${isActive ? 'badge-success' : 'badge-danger'}">${isActive ? 'Activo' : 'Inactivo'}</span>
                    ${geoLabel} ${ytLabel} ${linkLabel}
                </div>
            </div>
            <div class="card-actions" style="display:flex;gap:8px;flex-shrink:0;">
                <button class="btn btn-sm btn-secondary" onclick="editItem('${type.slice(0,-1)}',${item.id})">✏️ Editar</button>
                <button class="btn btn-sm btn-danger"    onclick="deleteItem('${type.slice(0,-1)}',${item.id})">🗑 Eliminar</button>
            </div>
        </div>`;
    }).join('');
}

// ── Open modal ──────────────────────────────────
function openModal(type, data) {
    currentType = type;
    editId = data ? data.id : null;
    document.getElementById('modal-title').textContent = (editId ? 'Editar ' : 'Crear ') + getTypeName(type);
    document.getElementById('form').action = '/api/' + getApiEndpoint(type) + '?action=' + (editId ? 'update' : 'create');
    document.getElementById('form-content').innerHTML = getFormHTML(type, data);
    document.getElementById('modal').classList.add('active');

    // Inicializar mini-mapas después de que el DOM esté listo
    setTimeout(() => {
        if (type === 'evento')      initMiniMap('evento',      data);
        if (type === 'noticia')     initMiniMap('noticia',     data);
        if (type === 'trivia')      initMiniMap('trivia',      data);
        if (type === 'encuesta')    initMiniMap('encuesta',    data);
        if (type === 'oferta')      initMiniMap('oferta',      data);
        if (type === 'transmision') initMiniMap('transmision', data);
    }, 100);
}

function closeModal() {
    document.getElementById('modal').classList.remove('active');
    // Destruir mapas
    if (miniMap)      { miniMap.remove();     miniMap = null; }
    if (miniMapDest)  { miniMapDest.remove();  miniMapDest = null; }
    miniMapMarker = null;
    miniMapDestMarker = null;
    currentType = null;
    editId = null;
}

// ── Mini-map init ───────────────────────────────
function initMiniMap(type, data) {
    const defaultLat = data?.lat  || -34.6037;
    const defaultLng = data?.lng  || -58.3816;

    // Mapa origen
    const mapEl = document.getElementById('mini-map-' + type);
    if (!mapEl) return;

    miniMap = L.map(mapEl).setView([defaultLat, defaultLng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(miniMap);

    miniMapMarker = L.marker([defaultLat, defaultLng], {draggable: true}).addTo(miniMap);
    document.getElementById('lat-' + type).value = defaultLat;
    document.getElementById('lng-' + type).value = defaultLng;

    miniMap.on('click', e => {
        miniMapMarker.setLatLng(e.latlng);
        document.getElementById('lat-' + type).value = e.latlng.lat.toFixed(7);
        document.getElementById('lng-' + type).value = e.latlng.lng.toFixed(7);
    });
    miniMapMarker.on('drag', e => {
        document.getElementById('lat-' + type).value = e.latlng.lat.toFixed(7);
        document.getElementById('lng-' + type).value = e.latlng.lng.toFixed(7);
    });

    // Mapa destino solo para eventos
    if (type === 'evento') {
        const destEl = document.getElementById('mini-map-dest');
        if (!destEl) return;
        const dLat = data?.dest_lat || defaultLat;
        const dLng = data?.dest_lng || defaultLng;

        miniMapDest = L.map(destEl).setView([dLat, dLng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(miniMapDest);

        miniMapDestMarker = L.marker([dLat, dLng], {draggable: true}).addTo(miniMapDest);
        document.getElementById('dest_lat').value = dLat;
        document.getElementById('dest_lng').value = dLng;

        miniMapDest.on('click', e => {
            miniMapDestMarker.setLatLng(e.latlng);
            document.getElementById('dest_lat').value = e.latlng.lat.toFixed(7);
            document.getElementById('dest_lng').value = e.latlng.lng.toFixed(7);
        });
        miniMapDestMarker.on('drag', e => {
            document.getElementById('dest_lat').value = e.latlng.lat.toFixed(7);
            document.getElementById('dest_lng').value = e.latlng.lng.toFixed(7);
        });
    }
}

// ── YouTube preview ─────────────────────────────
function updateYTPreview() {
    const url = document.getElementById('youtube_link').value.trim();
    const preview = document.getElementById('yt-preview');
    if (!url) { preview.style.display = 'none'; return; }

    let videoId = null;
    const m1 = url.match(/youtu\.be\/([^?]+)/);
    const m2 = url.match(/[?&]v=([^&]+)/);
    if (m1) videoId = m1[1];
    else if (m2) videoId = m2[1];

    if (videoId) {
        preview.innerHTML = `<iframe src="https://www.youtube.com/embed/${videoId}" allowfullscreen></iframe>`;
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }
}

// ── Form HTML ───────────────────────────────────
function getFormHTML(type, data) {
    const v = data || {};
    const chkActiva  = (v.activa  !== 0 && v.activa  !== '0') ? 'checked' : '';
    const chkActivo  = (v.activo  !== 0 && v.activo  !== '0') ? 'checked' : '';

    if (type === 'noticia') return `
        ${v.id ? `<input type="hidden" name="id" value="${v.id}">` : ''}
        <div class="form-group">
            <label>Título *</label>
            <input type="text" name="titulo" required placeholder="Ej: Nueva tienda abierta" value="${v.titulo||''}">
        </div>
        <div class="form-group">
            <label>Contenido *</label>
            <textarea name="contenido" required placeholder="Contenido de la noticia...">${v.contenido||''}</textarea>
        </div>
        <div class="form-group">
            <label>Categoría</label>
            <input type="text" name="categoria" placeholder="Ej: General, Economía, Cultura" value="${v.categoria||''}">
        </div>
        <div class="form-group">
            <label>Imagen (URL)</label>
            <input type="url" name="imagen" placeholder="https://ejemplo.com/imagen.jpg" value="${v.imagen||''}">
        </div>
        <hr style="margin:16px 0;border-color:var(--color-gray-200);">
        <p style="font-weight:600;margin-bottom:8px;">📍 Ubicación en el Mapa <span style="font-weight:400;font-size:12px;color:var(--text-tertiary)">(opcional — clic para marcar)</span></p>
        <div class="mini-map-wrapper">
            <div class="mini-map" id="mini-map-noticia"></div>
        </div>
        <div class="coords-row">
            <div class="form-group" style="margin:0">
                <label>Latitud</label>
                <input type="number" step="any" name="lat" id="lat-noticia" placeholder="(opcional)" value="${v.lat||''}">
            </div>
            <div class="form-group" style="margin:0">
                <label>Longitud</label>
                <input type="number" step="any" name="lng" id="lng-noticia" placeholder="(opcional)" value="${v.lng||''}">
            </div>
        </div>
        <div class="form-group" style="margin-top:8px;">
            <label>Lugar (texto)</label>
            <input type="text" name="ubicacion" placeholder="Ej: Plaza San Martín, Córdoba" value="${v.ubicacion||''}">
        </div>
        <div class="form-group">
            <label><input type="checkbox" name="activa" ${chkActiva}> Publicada</label>
        </div>`;

    if (type === 'evento') return `
        ${v.id ? `<input type="hidden" name="id" value="${v.id}">` : ''}
        <div class="form-group">
            <label>Título *</label>
            <input type="text" name="titulo" required placeholder="Ej: Feria Gastronómica" value="${v.titulo||''}">
        </div>
        <div class="form-group">
            <label>Descripción</label>
            <textarea name="descripcion" placeholder="Detalles del evento...">${v.descripcion||''}</textarea>
        </div>
        <div class="form-group">
            <label>Organizador</label>
            <input type="text" name="organizador" placeholder="Nombre del organizador" value="${v.organizador||''}">
        </div>
        <div class="form-group">
            <label>Mapita ID</label>
            <input type="text" name="mapita_id" maxlength="64" placeholder="Ej: EVT-001" value="${v.mapita_id||''}">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="form-group">
                <label>Fecha *</label>
                <input type="date" name="fecha" required value="${v.fecha||''}">
            </div>
            <div class="form-group">
                <label>Hora</label>
                <input type="time" name="hora" value="${v.hora||''}">
            </div>
        </div>

        <hr style="margin:16px 0;border-color:var(--color-gray-200);">
        <p style="font-weight:600;margin-bottom:8px;">📍 Ubicación principal <span style="font-weight:400;font-size:12px;color:var(--text-tertiary)">(clic en el mapa para seleccionar)</span></p>
        <div class="mini-map-wrapper">
            <div class="mini-map" id="mini-map-evento"></div>
        </div>
        <div class="coords-row">
            <div class="form-group" style="margin:0">
                <label>Latitud</label>
                <input type="number" step="any" name="lat" id="lat-evento" placeholder="-34.6037" value="${v.lat||''}">
            </div>
            <div class="form-group" style="margin:0">
                <label>Longitud</label>
                <input type="number" step="any" name="lng" id="lng-evento" placeholder="-58.3816" value="${v.lng||''}">
            </div>
        </div>
        <div class="form-group">
            <label>Dirección / Lugar (texto)</label>
            <input type="text" name="ubicacion" placeholder="Ej: Av. Corrientes 1234, CABA" value="${v.ubicacion||''}">
        </div>

        <details style="margin:8px 0;">
            <summary style="cursor:pointer;font-weight:600;color:var(--text-secondary);">📍 Destino / Punto de llegada (opcional)</summary>
            <div style="margin-top:12px;">
                <div class="mini-map-wrapper">
                    <div class="mini-map" id="mini-map-dest"></div>
                </div>
                <div class="coords-row">
                    <div class="form-group" style="margin:0">
                        <label>Lat. destino</label>
                        <input type="number" step="any" name="dest_lat" id="dest_lat" value="${v.dest_lat||''}">
                    </div>
                    <div class="form-group" style="margin:0">
                        <label>Lng. destino</label>
                        <input type="number" step="any" name="dest_lng" id="dest_lng" value="${v.dest_lng||''}">
                    </div>
                </div>
            </div>
        </details>

        <hr style="margin:16px 0;border-color:var(--color-gray-200);">
        <div class="form-group">
            <label>🎬 Link de YouTube (video o transmisión)</label>
            <input type="url" name="youtube_link" id="youtube_link"
                   placeholder="https://youtu.be/XXXXXX o https://www.youtube.com/watch?v=XXXXXX"
                   value="${v.youtube_link||''}"
                   oninput="updateYTPreview()">
            <p class="mini-map-hint">Podés pegar un link de video grabado o una transmisión en vivo de YouTube</p>
        </div>
        <div class="yt-preview" id="yt-preview"></div>

        <div class="form-group">
            <label><input type="checkbox" name="activo" ${chkActivo}> Activo</label>
        </div>`;

    if (type === 'trivia') return `
        ${v.id ? `<input type="hidden" name="id" value="${v.id}">` : ''}
        <div class="form-group">
            <label>Título *</label>
            <input type="text" name="titulo" required placeholder="Ej: Trivia de Tecnología" value="${v.titulo||''}">
        </div>
        <div class="form-group">
            <label>Descripción</label>
            <textarea name="descripcion" placeholder="De qué trata esta trivia...">${v.descripcion||''}</textarea>
        </div>
        <div class="form-group">
            <label>Dificultad</label>
            <select name="dificultad">
                <option value="facil"   ${v.dificultad==='facil'   ?'selected':''}>Fácil</option>
                <option value="medio"   ${(!v.dificultad||v.dificultad==='medio')?'selected':''}>Medio</option>
                <option value="dificil" ${v.dificultad==='dificil' ?'selected':''}>Difícil</option>
            </select>
        </div>
        <div class="form-group">
            <label>Tiempo límite (segundos)</label>
            <input type="number" name="tiempo_limite" value="${v.tiempo_limite||30}" min="10" max="300">
        </div>
        <hr style="margin:16px 0;border-color:var(--color-gray-200);">
        <p style="font-weight:600;margin-bottom:8px;">📍 Ubicación en el Mapa <span style="font-weight:400;font-size:12px;color:var(--text-tertiary)">(opcional — clic para marcar)</span></p>
        <div class="mini-map-wrapper">
            <div class="mini-map" id="mini-map-trivia"></div>
        </div>
        <div class="coords-row">
            <div class="form-group" style="margin:0">
                <label>Latitud</label>
                <input type="number" step="any" name="lat" id="lat-trivia" placeholder="(opcional)" value="${v.lat||''}">
            </div>
            <div class="form-group" style="margin:0">
                <label>Longitud</label>
                <input type="number" step="any" name="lng" id="lng-trivia" placeholder="(opcional)" value="${v.lng||''}">
            </div>
        </div>
        <div class="form-group" style="margin-top:8px;">
            <label>Lugar (texto)</label>
            <input type="text" name="ubicacion" placeholder="Ej: Biblioteca Municipal" value="${v.ubicacion||''}">
        </div>
        <div class="form-group">
            <label><input type="checkbox" name="activa" ${chkActivo || 'checked'}> Activa</label>
        </div>`;

    if (type === 'encuesta') return `
        ${v.id ? `<input type="hidden" name="id" value="${v.id}">` : ''}
        <div class="form-group">
            <label>Título *</label>
            <input type="text" name="titulo" required placeholder="Ej: ¿Qué servicio necesitas?" value="${v.titulo||''}">
        </div>
        <div class="form-group">
            <label>Descripción</label>
            <textarea name="descripcion" placeholder="Contexto de la encuesta...">${v.descripcion||''}</textarea>
        </div>
        <div class="form-group">
            <label>🔗 Link externo a la encuesta</label>
            <input type="url" name="link" placeholder="https://forms.google.com/..." value="${v.link||''}">
            <p class="mini-map-hint">Podés usar Google Forms, Typeform, o cualquier herramienta externa</p>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="form-group">
                <label>Fecha de inicio</label>
                <input type="date" name="fecha_creacion" value="${v.fecha_creacion||new Date().toISOString().split('T')[0]}">
            </div>
            <div class="form-group">
                <label>Fecha de expiración</label>
                <input type="date" name="fecha_expiracion" value="${v.fecha_expiracion||''}">
            </div>
        </div>

        <hr style="margin:16px 0;border-color:var(--color-gray-200);">
        <p style="font-weight:600;margin-bottom:8px;">📍 Ubicación geográfica <span style="font-weight:400;font-size:12px;color:var(--text-tertiary)">(clic en el mapa)</span></p>
        <div class="mini-map-wrapper">
            <div class="mini-map" id="mini-map-encuesta"></div>
        </div>
        <div class="coords-row">
            <div class="form-group" style="margin:0">
                <label>Latitud</label>
                <input type="number" step="any" name="lat" id="lat-encuesta" value="${v.lat||''}">
            </div>
            <div class="form-group" style="margin:0">
                <label>Longitud</label>
                <input type="number" step="any" name="lng" id="lng-encuesta" value="${v.lng||''}">
            </div>
        </div>

        <div class="form-group" style="margin-top:12px;">
            <label><input type="checkbox" name="activo" ${chkActivo || 'checked'}> Activa</label>
        </div>`;

    if (type === 'oferta') return `
        ${v.id ? '<input type="hidden" name="id" value="' + v.id + '">' : ''}
        <div class="form-group">
            <label>Nombre *</label>
            <input type="text" name="nombre" required placeholder="Ej: 2x1 en Pizzas" value="${v.nombre||''}">
        </div>
        <div class="form-group">
            <label>Descripción</label>
            <textarea name="descripcion" placeholder="Detalle de la oferta...">${v.descripcion||''}</textarea>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="form-group">
                <label>Precio Normal</label>
                <input type="number" step="0.01" name="precio_normal" placeholder="1200.00" value="${v.precio_normal||''}">
            </div>
            <div class="form-group">
                <label>Precio Oferta 💸</label>
                <input type="number" step="0.01" name="precio_oferta" placeholder="600.00" value="${v.precio_oferta||''}">
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="form-group">
                <label>Fecha inicio</label>
                <input type="date" name="fecha_inicio" value="${v.fecha_inicio||new Date().toISOString().split('T')[0]}">
            </div>
            <div class="form-group">
                <label>Fecha expiración</label>
                <input type="date" name="fecha_expiracion" value="${v.fecha_expiracion||''}">
            </div>
        </div>
        <div class="form-group">
            <label>Imagen (URL)</label>
            <input type="url" name="imagen_url" placeholder="https://ejemplo.com/imagen.jpg" value="${v.imagen_url||''}">
        </div>
        <hr style="margin:16px 0;border-color:var(--color-gray-200);">
        <p style="font-weight:600;margin-bottom:8px;">📍 Ubicación de la oferta <span style="font-weight:400;font-size:12px;color:var(--text-tertiary)">(clic en el mapa)</span></p>
        <div class="mini-map-wrapper">
            <div class="mini-map" id="mini-map-oferta"></div>
        </div>
        <div class="coords-row">
            <div class="form-group" style="margin:0">
                <label>Latitud</label>
                <input type="number" step="any" name="lat" id="lat-oferta" value="${v.lat||''}">
            </div>
            <div class="form-group" style="margin:0">
                <label>Longitud</label>
                <input type="number" step="any" name="lng" id="lng-oferta" value="${v.lng||''}">
            </div>
        </div>
        <div class="form-group" style="margin-top:12px;">
            <label><input type="checkbox" name="activo" ${chkActivo||'checked'}> Activa</label>
        </div>`;

    if (type === 'transmision') return `
        ${v.id ? '<input type="hidden" name="id" value="' + v.id + '">' : ''}
        <div class="form-group">
            <label>Título *</label>
            <input type="text" name="titulo" required placeholder="Ej: Radio Local en Vivo" value="${v.titulo||''}">
        </div>
        <div class="form-group">
            <label>Tipo de Transmisión</label>
            <select name="tipo">
                <option value="youtube_live"  ${v.tipo==='youtube_live'  ?'selected':''}>&#9654; YouTube Live</option>
                <option value="radio_stream"  ${v.tipo==='radio_stream'  ?'selected':''}>&#128251; Radio Online (Icecast/SHOUTcast)</option>
                <option value="audio_stream"  ${v.tipo==='audio_stream'  ?'selected':''}>&#127909; Audio Stream genérico</option>
                <option value="video_stream"  ${v.tipo==='video_stream'  ?'selected':''}>&#127897;&#65039; Video Stream (HLS/RTMP)</option>
            </select>
        </div>
        <div class="form-group">
            <label>URL del Stream *</label>
            <input type="url" name="stream_url" required
                   placeholder="https://youtu.be/LIVE_ID ó https://stream.servidor.com/radio"
                   value="${v.stream_url||''}">
            <p class="mini-map-hint">YouTube Live: pega la URL del video en vivo. Radio: URL del stream Icecast/MP3.</p>
        </div>
        <div class="form-group">
            <label>Descripción</label>
            <textarea name="descripcion" placeholder="Descripción del contenido...">${v.descripcion||''}</textarea>
        </div>
        <hr style="margin:16px 0;border-color:var(--color-gray-200);">
        <p style="font-weight:600;margin-bottom:8px;">📍 Ubicación geográfica <span style="font-weight:400;font-size:12px;color:var(--text-tertiary)">(clic en el mapa)</span></p>
        <div class="mini-map-wrapper">
            <div class="mini-map" id="mini-map-transmision"></div>
        </div>
        <div class="coords-row">
            <div class="form-group" style="margin:0">
                <label>Latitud</label>
                <input type="number" step="any" name="lat" id="lat-transmision" value="${v.lat||''}">
            </div>
            <div class="form-group" style="margin:0">
                <label>Longitud</label>
                <input type="number" step="any" name="lng" id="lng-transmision" value="${v.lng||''}">
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px;">
            <div class="form-group" style="margin:0">
                <label><input type="checkbox" name="en_vivo" ${v.en_vivo?'checked':''}> 🟢 Transmitiendo AHORA</label>
            </div>
            <div class="form-group" style="margin:0">
                <label><input type="checkbox" name="activo" ${chkActivo||'checked'}> Activo</label>
            </div>
        </div>`;

    return '';
}

// ── Helpers ───────────────────────────────
function getTypeName(type) {
    return {
        noticia:'Noticia', evento:'Evento', trivia:'Trivia',
        encuesta:'Encuesta', oferta:'Oferta', transmision:'Transmisión en Vivo'
    }[type] || type;
}
function getApiEndpoint(type) {
    return {
        noticia:'noticias.php', evento:'eventos.php', trivia:'trivias.php',
        encuesta:'encuestas.php', oferta:'ofertas.php', transmision:'transmisiones.php'
    }[type];
}

// ── Submit ──────────────────────────────────────
async function handleSubmit(e) {
    e.preventDefault();
    const form = document.getElementById('form');
    const formData = new FormData(form);
    const data = {};
    formData.forEach((value, key) => {
        if (['activa','activo'].includes(key)) { data[key] = true; }
        else { data[key] = value; }
    });
    // Checkboxes no enviadas = false
    ['activa','activo'].forEach(k => { if (!data[k]) data[k] = false; });

    const btn = document.getElementById('btn-submit');
    btn.disabled = true;
    btn.textContent = 'Guardando...';

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();

        if (result.success) {
            closeModal();
            const tabMap = {
                noticia:'noticias', evento:'eventos', trivia:'trivias',
                encuesta:'encuestas', oferta:'ofertas', transmision:'transmisiones'
            };
            if (tabMap[currentType]) loadData(tabMap[currentType]);
            showToast('✅ ' + getTypeName(currentType) + ' guardado correctamente', 'success');
        } else {
            showToast('❌ Error: ' + (result.error || result.message || 'No se pudo guardar'), 'error');
        }
    } catch (err) {
        showToast('❌ Error de red: ' + err.message, 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Guardar';
    }
}

// ── Edit ─────────────────────────────────────────
async function editItem(type, id) {
    try {
        const endpoints = {noticia:'noticias', evento:'eventos', trivia:'trivias', encuesta:'encuestas'};
        const res  = await fetch('/api/' + endpoints[type] + '.php?id=' + id);
        const result = await res.json();
        if (result.success && result.data) {
            openModal(type, result.data);
        } else {
            showToast('No se pudo cargar el item', 'error');
        }
    } catch(err) {
        showToast('Error: ' + err.message, 'error');
    }
}

// ── Delete ───────────────────────────────────────
async function deleteItem(type, id) {
    if (!confirm('¿Seguro que deseas eliminar esto? Esta acción no se puede deshacer.')) return;
    const endpoints = {noticia:'noticias', evento:'eventos', trivia:'trivias', encuesta:'encuestas'};
    try {
        const res = await fetch('/api/' + endpoints[type] + '.php?action=delete&id=' + id, { method:'POST' });
        const result = await res.json();
        if (result.success) {
            const tabMap = {noticia:'noticias',evento:'eventos',trivia:'trivias',encuesta:'encuestas'};
            loadData(tabMap[type]);
            showToast('✅ Eliminado correctamente', 'success');
        } else {
            showToast('❌ ' + result.error, 'error');
        }
    } catch(err) {
        showToast('❌ Error: ' + err.message, 'error');
    }
}

// ── Toast ────────────────────────────────────────
function showToast(msg, type='success') {
    const t = document.createElement('div');
    t.textContent = msg;
    t.style.cssText = `
        position:fixed;bottom:24px;right:24px;z-index:9999;
        padding:12px 20px;border-radius:8px;font-size:14px;font-weight:600;
        background:${type==='success'?'#155724':'#721c24'};
        color:white;box-shadow:0 4px 12px rgba(0,0,0,.2);
        animation:slideUp .3s ease`;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}

async function buscarEntidadPorMapita(mapitaId) {
    const res = await fetch('/api/relaciones.php?action=lookup&mapita_id=' + encodeURIComponent(mapitaId));
    const json = await res.json();
    if (!json.success) throw new Error(json.error || 'No encontrado');
    return json.data;
}

async function crearRelacionPorMapita() {
    const sourceMapita = (document.getElementById('rel-source-mapita').value || '').trim();
    const targetMapita = (document.getElementById('rel-target-mapita').value || '').trim();
    const relationType = (document.getElementById('rel-type').value || 'relacionado').trim();
    const descripcion = (document.getElementById('rel-desc').value || '').trim();
    const resultEl = document.getElementById('rel-search-result');

    if (!sourceMapita || !targetMapita) {
        showToast('Completá mapita IDs de origen y destino', 'error');
        return;
    }

    try {
        resultEl.textContent = 'Buscando entidades...';
        const [source, target] = await Promise.all([
            buscarEntidadPorMapita(sourceMapita),
            buscarEntidadPorMapita(targetMapita),
        ]);

        const payload = {
            source_entity_type: source.entity_type,
            source_entity_id: source.entity_id,
            source_mapita_id: source.mapita_id,
            target_entity_type: target.entity_type,
            target_entity_id: target.entity_id,
            target_mapita_id: target.mapita_id,
            relation_type: relationType || 'relacionado',
            descripcion: descripcion || null,
            activo: true
        };
        const resp = await fetch('/api/relaciones.php?action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const json = await resp.json();
        if (!json.success) throw new Error(json.error || json.message || 'No se pudo crear');

        resultEl.textContent = `✅ Relación creada (${source.mapita_id} → ${target.mapita_id})`;
        showToast('Relación creada correctamente', 'success');
    } catch (err) {
        resultEl.textContent = '❌ ' + err.message;
        showToast('Error creando relación: ' + err.message, 'error');
    }
}

// ── MARCAS ───────────────────────────────────────
let allMarcas = [];

async function loadMarcas() {
    const el = document.getElementById('marcas-list');
    el.innerHTML = '<p style="padding:16px;color:#888">⏳ Cargando marcas…</p>';
    try {
        const res    = await fetch('/api/brands.php');
        const result = await res.json();
        allMarcas = result.success ? (result.data || []) : [];
        renderMarcas(allMarcas);
    } catch(e) {
        el.innerHTML = '<p style="padding:16px;color:red">❌ Error cargando marcas: ' + e.message + '</p>';
    }
}

function filtrarMarcas(q) {
    const s = q.toLowerCase();
    const filtered = s
        ? allMarcas.filter(m =>
            (m.name||m.nombre||'').toLowerCase().includes(s) ||
            (m.rubro||'').toLowerCase().includes(s) ||
            (m.ubicacion||'').toLowerCase().includes(s))
        : allMarcas;
    renderMarcas(filtered);
}

function renderMarcas(items) {
    const el    = document.getElementById('marcas-list');
    const count = document.getElementById('count-marcas');
    if (count) count.textContent = items.length + ' marca' + (items.length !== 1 ? 's' : '');

    if (!items.length) {
        el.innerHTML = '<p style="text-align:center;padding:40px;color:#888">No se encontraron marcas</p>';
        return;
    }

    el.innerHTML = `
    <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;background:white;border-radius:8px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.08);">
        <thead>
            <tr style="background:var(--primary);color:white;">
                <th style="padding:10px 12px;text-align:left;font-weight:600;">ID</th>
                <th style="padding:10px 12px;text-align:left;font-weight:600;">Nombre</th>
                <th style="padding:10px 12px;text-align:left;font-weight:600;">Rubro</th>
                <th style="padding:10px 12px;text-align:left;font-weight:600;">Mapita ID</th>
                <th style="padding:10px 12px;text-align:left;font-weight:600;">Ubicación</th>
                <th style="padding:10px 12px;text-align:left;font-weight:600;">📍 Coords</th>
                <th style="padding:10px 12px;text-align:center;font-weight:600;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            ${items.map((m, i) => {
                const nombre = m.name || m.nombre || '—';
                const hasGeo = m.lat && m.lng && parseFloat(m.lat) !== 0;
                return `
                <tr style="border-bottom:1px solid #f0f0f0;background:${i%2===0?'white':'#fafafa'};">
                    <td style="padding:9px 12px;color:#888;font-size:11px;">#${m.id}</td>
                    <td style="padding:9px 12px;font-weight:600;color:var(--text-primary);">${nombre}</td>
                    <td style="padding:9px 12px;color:var(--text-secondary);">${m.rubro||'—'}</td>
                    <td style="padding:9px 12px;color:#4b5563;font-size:12px;">${m.mapita_id||'—'}</td>
                    <td style="padding:9px 12px;color:var(--text-secondary);font-size:12px;">${m.ubicacion||'—'}</td>
                    <td style="padding:9px 12px;">
                        ${hasGeo
                            ? `<span style="background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;">✓ ${parseFloat(m.lat).toFixed(3)}, ${parseFloat(m.lng).toFixed(3)}</span>`
                            : `<span style="background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:10px;font-size:11px;">Sin coords</span>`
                        }
                    </td>
                    <td style="padding:9px 12px;text-align:center;">
                        <div style="display:flex;gap:6px;justify-content:center;">
                            <a href="/brand_edit?id=${m.id}" style="padding:5px 12px;background:#0ea5e9;color:white;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none;">✏️ Editar</a>
                            <a href="/brand_detail?id=${m.id}" target="_blank" style="padding:5px 12px;background:#6b7280;color:white;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none;">👁 Ver</a>
                        </div>
                    </td>
                </tr>`;
            }).join('')}
        </tbody>
    </table>
    </div>`;
}

// ── NEGOCIOS ─────────────────────────────────────
let allNegocios = [];

async function loadNegocios() {
    const el = document.getElementById('negocios-list');
    el.innerHTML = '<p style="padding:16px;color:#888">⏳ Cargando negocios…</p>';
    try {
        const res    = await fetch('/api/businesses.php');
        const result = await res.json();
        // businesses API puede devolver array directo o {success, data}
        allNegocios = Array.isArray(result) ? result : (result.data || result.businesses || []);
        renderNegocios(allNegocios);
    } catch(e) {
        el.innerHTML = '<p style="padding:16px;color:red">❌ Error cargando negocios: ' + e.message + '</p>';
    }
}

function filtrarNegocios(q) {
    const s = q.toLowerCase();
    const filtered = s
        ? allNegocios.filter(n =>
            (n.name||n.nombre||'').toLowerCase().includes(s) ||
            (n.address||n.direccion||'').toLowerCase().includes(s) ||
            (n.type||n.tipo||'').toLowerCase().includes(s) ||
            (n.owner_name||'').toLowerCase().includes(s))
        : allNegocios;
    renderNegocios(filtered);
}

function renderNegocios(items) {
    const el    = document.getElementById('negocios-list');
    const count = document.getElementById('count-negocios');
    if (count) count.textContent = items.length + ' negocio' + (items.length !== 1 ? 's' : '');

    if (!items.length) {
        el.innerHTML = '<p style="text-align:center;padding:40px;color:#888">No se encontraron negocios</p>';
        return;
    }

    el.innerHTML = `
    <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;background:white;border-radius:8px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.08);">
        <thead>
            <tr style="background:var(--primary);color:white;">
                <th style="padding:10px 12px;text-align:left;font-weight:600;">ID</th>
                <th style="padding:10px 12px;text-align:left;font-weight:600;">Nombre</th>
                <th style="padding:10px 12px;text-align:left;font-weight:600;">Tipo</th>
                <th style="padding:10px 12px;text-align:left;font-weight:600;">Mapita ID</th>
                <th style="padding:10px 12px;text-align:left;font-weight:600;">Dirección</th>
                <th style="padding:10px 12px;text-align:left;font-weight:600;">Propietario</th>
                <th style="padding:10px 12px;text-align:center;font-weight:600;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            ${items.map((n, i) => {
                const nombre = n.name || n.nombre || '—';
                const tipo   = n.type || n.tipo || '—';
                const dir    = (n.address || n.direccion || '—').substring(0, 40);
                const owner  = n.owner_name || n.username || '—';
                return `
                <tr style="border-bottom:1px solid #f0f0f0;background:${i%2===0?'white':'#fafafa'};">
                    <td style="padding:9px 12px;color:#888;font-size:11px;">#${n.id}</td>
                    <td style="padding:9px 12px;font-weight:600;color:var(--text-primary);">${nombre}</td>
                    <td style="padding:9px 12px;color:var(--text-secondary);font-size:12px;">${tipo}</td>
                    <td style="padding:9px 12px;color:#4b5563;font-size:12px;">${n.mapita_id||'—'}</td>
                    <td style="padding:9px 12px;color:var(--text-secondary);font-size:12px;">${dir}</td>
                    <td style="padding:9px 12px;font-size:12px;color:#555;">${owner}</td>
                    <td style="padding:9px 12px;text-align:center;">
                        <div style="display:flex;gap:6px;justify-content:center;">
                            <a href="/edit?id=${n.id}" style="padding:5px 12px;background:#0ea5e9;color:white;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none;">✏️ Editar</a>
                            <a href="/view?id=${n.id}" target="_blank" style="padding:5px 12px;background:#6b7280;color:white;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none;">👁 Ver</a>
                        </div>
                    </td>
                </tr>`;
            }).join('')}
        </tbody>
    </table>
    </div>`;
}

// ── Bulk import ──────────────────────────────────
async function bulkImport(type, fileInputId, resultId) {
    const fileInput = document.getElementById(fileInputId);
    const resultEl  = document.getElementById(resultId);

    if (!fileInput.files || !fileInput.files[0]) {
        resultEl.innerHTML = '<span style="color:red;">Seleccioná un archivo .json primero.</span>';
        return;
    }

    const file = fileInput.files[0];
    if (file.size > 2 * 1024 * 1024) {
        resultEl.innerHTML = '<span style="color:red;">El archivo supera 2MB.</span>';
        return;
    }

    resultEl.innerHTML = '<span style="color:#6b7280;">⏳ Importando...</span>';

    const fd = new FormData();
    fd.append('file', file);
    fd.append('type', type);

    try {
        const res  = await fetch('/api/bulk_import.php', { method: 'POST', body: fd });
        const json = await res.json();

        if (!json.success && !json.imported) {
            resultEl.innerHTML = '<span style="color:red;">❌ ' + (json.error || 'Error desconocido') + '</span>';
            return;
        }

        let html = '<span style="color:#065f46;">✅ ' + json.message + '</span>';
        if (json.errors && json.errors.length) {
            html += '<ul style="margin:4px 0 0;padding-left:16px;color:#b45309;">'
                  + json.errors.map(e => '<li>' + e + '</li>').join('')
                  + '</ul>';
        }
        resultEl.innerHTML = html;

        // Reload the list after import
        if (type === 'businesses') loadNegocios();
        else                        loadMarcas();
    } catch (err) {
        resultEl.innerHTML = '<span style="color:red;">❌ Error: ' + err.message + '</span>';
    }
}

// ── Moderación ────────────────────────────────────
const REASON_LABELS = {
    spam:          '🗑 Spam',
    inappropriate: '🔞 Inapropiado',
    fake:          '🤥 Falso',
    harassment:    '😡 Acoso',
    other:         '❓ Otro'
};
const STATUS_LABELS = {
    pending:    '⏳ Pendiente',
    reviewing:  '🔍 En revisión',
    resolved:   '✅ Resuelto',
    dismissed:  '🚫 Descartado'
};

async function loadReports() {
    const el     = document.getElementById('reports-list');
    const status = document.getElementById('mod-status-filter')?.value || 'pending';
    if (!el) return;
    el.innerHTML = '<p style="padding:16px;color:#6b7280;">⏳ Cargando reportes...</p>';
    try {
        const res    = await fetch(`/api/reports.php?status=${status}&limit=50`);
        const result = await res.json();
        if (!result.success) { el.innerHTML = '<p style="color:red;">Error al cargar reportes.</p>'; return; }

        const badge = document.getElementById('pending-badge');
        if (badge) {
            const n = result.data?.pending_count ?? 0;
            badge.textContent = n + ' pendiente' + (n !== 1 ? 's' : '');
            badge.style.display = n > 0 ? '' : 'none';
        }

        const reports = result.data?.reports ?? [];
        if (!reports.length) {
            el.innerHTML = '<p style="padding:24px;text-align:center;color:#6b7280;">✅ No hay reportes en este estado.</p>';
            return;
        }
        el.innerHTML = `
        <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;background:white;border-radius:8px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.08);">
            <thead>
                <tr style="background:var(--primary);color:white;">
                    <th style="padding:9px 12px;text-align:left;">ID</th>
                    <th style="padding:9px 12px;text-align:left;">Tipo</th>
                    <th style="padding:9px 12px;text-align:left;">Contenido #</th>
                    <th style="padding:9px 12px;text-align:left;">Motivo</th>
                    <th style="padding:9px 12px;text-align:left;">Descripción</th>
                    <th style="padding:9px 12px;text-align:left;">Reportador</th>
                    <th style="padding:9px 12px;text-align:left;">Estado</th>
                    <th style="padding:9px 12px;text-align:left;">Fecha</th>
                    <th style="padding:9px 12px;text-align:center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                ${reports.map((r, i) => `
                <tr style="border-bottom:1px solid #f0f0f0;background:${i%2===0?'white':'#fafafa'};">
                    <td style="padding:8px 12px;color:#888;font-size:11px;">#${r.id}</td>
                    <td style="padding:8px 12px;font-size:12px;">${r.content_type}</td>
                    <td style="padding:8px 12px;font-size:12px;">#${r.content_id}</td>
                    <td style="padding:8px 12px;font-size:12px;">${REASON_LABELS[r.reason] || r.reason}</td>
                    <td style="padding:8px 12px;font-size:12px;max-width:200px;word-break:break-word;">${r.description ? r.description.substring(0,80) : '—'}</td>
                    <td style="padding:8px 12px;font-size:12px;">${r.reporter_name || r.reporter_ip || '—'}</td>
                    <td style="padding:8px 12px;font-size:12px;">${STATUS_LABELS[r.status] || r.status}</td>
                    <td style="padding:8px 12px;font-size:11px;color:#6b7280;">${(r.created_at||'').substring(0,16)}</td>
                    <td style="padding:8px 12px;text-align:center;">
                        <div style="display:flex;gap:4px;justify-content:center;flex-wrap:wrap;">
                            ${r.status === 'pending' || r.status === 'reviewing' ? `
                            <button onclick="resolveReport(${r.id},'reviewing')"  style="padding:4px 8px;background:#f59e0b;color:white;border:none;border-radius:5px;font-size:11px;cursor:pointer;">🔍 Revisar</button>
                            <button onclick="resolveReport(${r.id},'resolved')"   style="padding:4px 8px;background:#10b981;color:white;border:none;border-radius:5px;font-size:11px;cursor:pointer;">✅ Resolver</button>
                            <button onclick="resolveReport(${r.id},'dismissed')"  style="padding:4px 8px;background:#6b7280;color:white;border:none;border-radius:5px;font-size:11px;cursor:pointer;">🚫 Descartar</button>
                            ` : '—'}
                        </div>
                    </td>
                </tr>`).join('')}
            </tbody>
        </table>
        </div>`;
    } catch(e) {
        el.innerHTML = '<p style="color:red;">Error de red: ' + e.message + '</p>';
    }
}

async function resolveReport(id, status) {
    const note = status === 'resolved'
        ? (prompt('Nota de resolución (opcional):') ?? '')
        : '';
    try {
        const res    = await fetch(`/api/reports.php?id=${id}`, {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({status, resolution_note: note})
        });
        const result = await res.json();
        if (result.success) loadReports();
        else alert('Error: ' + result.message);
    } catch(e) {
        alert('Error de red: ' + e.message);
    }
}

async function loadAuditLog() {
    const el = document.getElementById('audit-log-list');
    if (!el) return;
    el.innerHTML = '<p style="padding:16px;color:#6b7280;">⏳ Cargando log...</p>';
    try {
        const res    = await fetch('/api/audit_log.php?limit=100');
        const result = await res.json();
        if (!result.success) { el.innerHTML = '<p style="color:red;">Error al cargar log de auditoría.</p>'; return; }
        const logs = result.data ?? [];
        if (!logs.length) {
            el.innerHTML = '<p style="padding:24px;text-align:center;color:#6b7280;">No hay entradas en el log.</p>';
            return;
        }
        el.innerHTML = `
        <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:12px;background:white;border-radius:8px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.08);">
            <thead>
                <tr style="background:#374151;color:white;">
                    <th style="padding:8px 12px;text-align:left;">Fecha</th>
                    <th style="padding:8px 12px;text-align:left;">Usuario</th>
                    <th style="padding:8px 12px;text-align:left;">Acción</th>
                    <th style="padding:8px 12px;text-align:left;">Entidad</th>
                    <th style="padding:8px 12px;text-align:left;">ID</th>
                    <th style="padding:8px 12px;text-align:left;">IP</th>
                    <th style="padding:8px 12px;text-align:left;">Detalles</th>
                </tr>
            </thead>
            <tbody>
                ${logs.map((l, i) => `
                <tr style="border-bottom:1px solid #f0f0f0;background:${i%2===0?'white':'#fafafa'};">
                    <td style="padding:7px 12px;color:#6b7280;">${(l.created_at||'').substring(0,16)}</td>
                    <td style="padding:7px 12px;">${l.username || ('ID '+l.user_id) || '—'}</td>
                    <td style="padding:7px 12px;font-weight:600;">${l.action}</td>
                    <td style="padding:7px 12px;">${l.entity_type || '—'}</td>
                    <td style="padding:7px 12px;">${l.entity_id || '—'}</td>
                    <td style="padding:7px 12px;color:#6b7280;">${l.ip || '—'}</td>
                    <td style="padding:7px 12px;max-width:220px;word-break:break-word;color:#374151;">${l.details ? l.details.substring(0,120) : '—'}</td>
                </tr>`).join('')}
            </tbody>
        </table>
        </div>`;
    } catch(e) {
        el.innerHTML = '<p style="color:red;">Error de red: ' + e.message + '</p>';
    }
}

// ── Init ─────────────────────────────────────────
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
window.addEventListener('load', () => {
    const tab = '<?php echo $tab; ?>';
    if (tab === 'marcas')          loadMarcas();
    else if (tab === 'negocios')   loadNegocios();
    else if (tab === 'moderacion') { loadReports(); loadAuditLog(); }
    else                           loadData(tab);
});
</script>
</body>
</html>
