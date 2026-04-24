<?php
/**
 * Mapita Admin Panel
 * Panel de administración - Noticias, Eventos, Trivias, Encuestas
 * Con soporte completo de geolocalización (lat/lng + mini-mapas)
 */

session_start();
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';

$validTabs = ['negocios','marcas','noticias','eventos','trivias','encuestas','ofertas','transmisiones','moderacion','sectores'];
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

        /* ── Rich Text Editor ───────────────────────── */
        .rte-toolbar {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
            margin-bottom: 4px;
        }
        .rte-toolbar button {
            padding: 4px 10px;
            border: 1px solid var(--color-gray-300);
            border-radius: 4px;
            background: white;
            cursor: pointer;
            font-size: 13px;
            font-family: inherit;
            line-height: 1.4;
            color: var(--text-primary);
            transition: background 0.15s;
        }
        .rte-toolbar button:hover { background: #f0f0f0; }
        .rte-editor {
            white-space: pre-wrap;
            word-break: break-word;
        }
        .rte-editor:focus { outline: 2px solid var(--primary); outline-offset: 1px; }

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
        <button id="tab-btn-sectores"       class="tab-btn <?php echo $tab==='sectores'       ? 'active' : ''; ?>" onclick="switchTab('sectores')">🏭 Catálogo: Sectores Ind.</button>
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

        <!-- Gateway al panel profesional -->
        <div style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);border-radius:12px;padding:18px 22px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div style="color:white;">
                <div style="font-size:1rem;font-weight:700;margin-bottom:3px;">🎓 Panel Profesional de Encuestas</div>
                <div style="font-size:0.82rem;opacity:.9;">Gestión avanzada: crear preguntas, ver estadísticas detalladas por respuesta, analizar participación y más.</div>
            </div>
            <a href="/admin/encuestas/dashboard.php" target="_blank"
               style="background:white;color:#764ba2;font-weight:700;padding:9px 18px;border-radius:8px;text-decoration:none;font-size:0.85rem;white-space:nowrap;flex-shrink:0;">
                Abrir panel pro →
            </a>
        </div>

        <!-- Métricas y gráfico inline -->
        <div id="encuestas-stats" style="margin-bottom:20px;">
            <p style="color:var(--text-tertiary);padding:8px 0;">⏳ Cargando métricas…</p>
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
        <div id="transmisiones-list"></div>
    </div>

    <!-- CATÁLOGO: SECTORES INDUSTRIALES (Admin) -->
    <div class="tab-content <?php echo $tab==='sectores' ? 'active' : ''; ?>" id="tab-sectores">
        <div class="section-header">
            <h2>🏭 Catálogo: Sectores Industriales</h2>
            <button class="btn btn-primary" onclick="openSectorModal()">+ Nuevo Sector</button>
        </div>
        <div style="margin-bottom:16px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <input type="text" id="search-sectores" placeholder="🔍 Buscar por nombre…"
                   oninput="filtrarSectores(this.value)"
                   style="flex:1;padding:10px 14px;border:1px solid var(--color-gray-300);border-radius:8px;font-size:14px;">
            <select id="filter-sector-type" onchange="filtrarSectores(document.getElementById('search-sectores').value)"
                    style="padding:10px 12px;border:1px solid var(--color-gray-300);border-radius:8px;font-size:14px;background:white;">
                <option value="">Todos los tipos</option>
                <option value="mineria">⛏ Minería</option>
                <option value="energia">⚡ Energía</option>
                <option value="agro">🌾 Agro</option>
                <option value="infraestructura">🏗 Infraestructura</option>
                <option value="inmobiliario">🏢 Inmobiliario</option>
                <option value="industrial">🏭 Industrial</option>
            </select>
            <select id="filter-sector-status" onchange="filtrarSectores(document.getElementById('search-sectores').value)"
                    style="padding:10px 12px;border:1px solid var(--color-gray-300);border-radius:8px;font-size:14px;background:white;">
                <option value="">Todos los estados</option>
                <option value="activo">✅ Activo</option>
                <option value="proyecto">📐 Proyecto</option>
                <option value="potencial">💡 Potencial</option>
            </select>
            <span id="count-sectores" style="font-size:12px;color:var(--text-tertiary);white-space:nowrap;"></span>
        </div>
        <div id="sectores-list"></div>
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

<!-- ── MODAL SECTORES INDUSTRIALES ──────────── -->
<div id="sector-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9000;align-items:center;justify-content:center;padding:20px;">
    <div style="background:white;border-radius:12px;width:100%;max-width:620px;max-height:90vh;overflow-y:auto;box-shadow:0 8px 32px rgba(0,0,0,.22);">
        <div style="display:flex;justify-content:space-between;align-items:center;padding:20px 24px 16px;border-bottom:1px solid #e5e7eb;">
            <h3 id="sector-modal-title" style="margin:0;font-size:1.1rem;">Nuevo Sector Industrial</h3>
            <button onclick="closeSectorModal()" style="background:none;border:none;font-size:1.4rem;cursor:pointer;color:#6b7280;">×</button>
        </div>
        <form id="sector-form" onsubmit="handleSectorSubmit(event)" style="padding:20px 24px;">
            <div class="form-group">
                <label>Nombre *</label>
                <input type="text" id="sector-form-name" required maxlength="255" placeholder="Ej: Parque Industrial Norte">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label>Tipo *</label>
                    <select id="sector-form-type">
                        <option value="industrial">🏭 Industrial</option>
                        <option value="mineria">⛏ Minería</option>
                        <option value="energia">⚡ Energía</option>
                        <option value="agro">🌾 Agro</option>
                        <option value="infraestructura">🏗 Infraestructura</option>
                        <option value="inmobiliario">🏢 Inmobiliario</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Subtipo</label>
                    <input type="text" id="sector-form-subtype" maxlength="100" placeholder="Ej: Parque tecnológico">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label>Estado *</label>
                    <select id="sector-form-status">
                        <option value="potencial">💡 Potencial</option>
                        <option value="proyecto">📐 Proyecto</option>
                        <option value="activo">✅ Activo</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nivel de inversión</label>
                    <select id="sector-form-investment">
                        <option value="bajo">🟢 Bajo</option>
                        <option value="medio">🟡 Medio</option>
                        <option value="alto">🔴 Alto</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nivel de riesgo</label>
                    <select id="sector-form-risk">
                        <option value="bajo">🟢 Bajo</option>
                        <option value="medio">🟡 Medio</option>
                        <option value="alto">🔴 Alto</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Jurisdicción</label>
                <input type="text" id="sector-form-jurisdiction" maxlength="255" placeholder="Ej: Provincia de Córdoba">
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <textarea id="sector-form-description" rows="3" placeholder="Descripción del sector…"></textarea>
            </div>
            <div class="form-group">
                <label>GeoJSON *
                    <span style="font-weight:400;font-size:12px;color:var(--text-tertiary);">— Pegá el objeto GeoJSON de la geometría (Polygon, Point, etc.)</span>
                </label>
                <textarea id="sector-form-geometry" rows="6" required
                          placeholder='{"type":"Polygon","coordinates":[[[lng,lat],[lng,lat],[lng,lat]]]}'
                          style="font-family:monospace;font-size:12px;"></textarea>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:4px;">
                <button type="button" class="btn btn-secondary" onclick="closeSectorModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="sector-btn-submit">Guardar</button>
            </div>
        </form>
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

// ── HTML helpers ─────────────────────────────────
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}
function escapeHtmlAttr(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;')
        .replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

// ── Rich text editor helpers ──────────────────────
function rteCmd(cmd, val) {
    document.execCommand(cmd, false, val || null);
    syncRteToHidden('noticia');
}

function plainTextLen(el) {
    return (el.innerText || el.textContent || '').trim().length;
}

function syncRteToHidden(name) {
    const rte     = document.getElementById('rte-' + name);
    const hidden  = document.getElementById('hidden-contenido-' + name);
    const counter = document.getElementById(name + '-char-count');
    if (!rte || !hidden) return;
    hidden.value = rte.innerHTML;
    const len = plainTextLen(rte);
    if (counter) {
        counter.textContent = '(' + len + '/500 caracteres)';
        counter.style.color = len > 500 ? '#dc2626' : '#6b7280';
    }
}

function limitRteChars(name, event, max) {
    const rte = document.getElementById('rte-' + name);
    if (!rte) return true;
    const len = plainTextLen(rte);
    const allowed = ['Backspace','Delete','ArrowLeft','ArrowRight','ArrowUp','ArrowDown',
                     'Tab','Enter','Home','End','PageUp','PageDown'];
    if (len >= max && !event.ctrlKey && !event.metaKey && !allowed.includes(event.key)) {
        event.preventDefault();
        return false;
    }
    return true;
}

// Initialise RTE char counter once the DOM content is in place
function initRteNoticia() {
    const rte = document.getElementById('rte-noticia');
    if (rte) {
        syncRteToHidden('noticia');
        rte.focus();
    }
}


// ── Tab management ──────────────────────────────
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    document.getElementById('tab-btn-' + tab).classList.add('active');
    if (tab === 'marcas')         loadMarcas();
    else if (tab === 'negocios')  loadNegocios();
    else if (tab === 'moderacion') { loadReports(); loadAuditLog(); }
    else if (tab === 'sectores')  loadSectores();
    else {
        loadData(tab);
        if (tab === 'encuestas') loadEncuestasStats();
    }
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
    const singularType = getSingularType(type);
    if (!items.length) {
        container.innerHTML = `
            <div style="text-align:center;padding:40px;color:var(--text-tertiary);">
                <p style="font-size:2rem;">📭</p>
                <p>No hay ${type} creados aún</p>
                <button class="btn btn-primary" onclick="openModal('${singularType}')">Crear primero</button>
            </div>`;
        return;
    }
    container.innerHTML = items.map(item => {
        const isActive = item.activa !== 0 && item.activo !== 0;
        const hasGeo   = (item.lat && item.lng);
        const hasYT    = item.youtube_link;
        const title    = item.titulo || item.nombre || '(sin título)';
        // Strip HTML tags for plain-text preview
        const plainDesc = (item.descripcion || item.contenido || '').replace(/<[^>]+>/g, '').substring(0, 120);
        const date     = item.fecha_publicacion || item.fecha || item.fecha_creacion || '';
        const geoLabel = hasGeo
            ? `<span class="geo-badge">📍 ${parseFloat(item.lat).toFixed(4)}, ${parseFloat(item.lng).toFixed(4)}</span>`
            : `<span class="no-geo-badge">Sin ubicación</span>`;
        const ytLabel = hasYT ? `<span class="yt-badge">▶ YouTube</span>` : '';
        const linkLabel = item.link ? `<a href="${escapeHtml(item.link)}" target="_blank" rel="noopener noreferrer" class="geo-badge" style="background:#3b82f6;color:white;text-decoration:none;">🔗 Ver noticia</a>` : '';
        const appLabel  = item.app_path ? `<span class="geo-badge" style="background:#7c3aed">🎮 App: ${escapeHtml(item.app_path)}</span>` : '';
        const tagsLabel = item.tags ? `<span style="color:#6b7280;font-size:11px;">🏷 ${escapeHtml(item.tags)}</span>` : '';

        return `
        <div class="admin-card ${hasGeo ? 'has-geo' : 'no-geo'}">
            <div class="card-content">
                <h3>${escapeHtml(title)}</h3>
                <p style="font-size:var(--font-size-sm);color:var(--text-secondary);margin:0 0 4px;">${escapeHtml(plainDesc)}${plainDesc.length===120?'…':''}</p>
                <div class="card-meta">
                    ${date ? `<span>📅 ${date}</span>` : ''}
                    <span class="badge ${isActive ? 'badge-success' : 'badge-danger'}">${isActive ? 'Activo' : 'Inactivo'}</span>
                    ${geoLabel} ${ytLabel} ${linkLabel} ${appLabel} ${tagsLabel}
                </div>
            </div>
            <div class="card-actions" style="display:flex;gap:8px;flex-shrink:0;">
                <button class="btn btn-sm btn-secondary" onclick="editItem('${singularType}',${item.id})">✏️ Editar</button>
                <button class="btn btn-sm btn-danger"    onclick="deleteItem('${singularType}',${item.id})">🗑 Eliminar</button>
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

    // Inicializar mini-mapas y editores después de que el DOM esté listo
    setTimeout(() => {
        if (type === 'evento')      initMiniMap('evento',      data);
        if (type === 'noticia')     { initMiniMap('noticia', data); initRteNoticia(); }
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
            <label>Contenido * <span id="noticia-char-count" style="font-size:11px;color:#6b7280;font-weight:400;">(0/500 caracteres)</span></label>
            <div class="rte-toolbar" id="rte-toolbar-noticia">
                <button type="button" title="Negrita" onclick="rteCmd('bold')" style="font-weight:bold;">B</button>
                <button type="button" title="Subrayado" onclick="rteCmd('underline')" style="text-decoration:underline;">U</button>
                <button type="button" title="Color de texto" onclick="document.getElementById('rte-color-noticia').click()">A</button>
                <input type="color" id="rte-color-noticia" style="width:0;height:0;opacity:0;position:absolute;"
                       onchange="rteCmd('foreColor', this.value)">
                <button type="button" title="Quitar formato" onclick="rteCmd('removeFormat')" style="font-size:10px;">✕fmt</button>
            </div>
            <div id="rte-noticia" contenteditable="true" class="rte-editor"
                 oninput="syncRteToHidden('noticia')"
                 onkeydown="return limitRteChars('noticia', event, 500)"
                 style="min-height:100px;max-height:200px;overflow-y:auto;border:1px solid var(--color-gray-300);border-radius:6px;padding:10px;font-size:14px;line-height:1.5;"
            >${v.contenido||''}</div>
            <input type="hidden" name="contenido" id="hidden-contenido-noticia" value="${escapeHtmlAttr(v.contenido||'')}">
            <p style="font-size:11px;color:#9ca3af;margin-top:4px;">Permitido: negrita, subrayado y colores. Máx. 500 caracteres (texto plano).</p>
        </div>
        <div class="form-group">
            <label>Resumen para popup <span style="font-size:11px;color:#6b7280;font-weight:400;">(texto breve que aparece en el mapa)</span></label>
            <textarea name="resumen_popup" maxlength="200" placeholder="Breve resumen visible en el popup del mapa..." rows="2">${escapeHtml(v.resumen_popup||'')}</textarea>
        </div>
        <div class="form-group">
            <label>🔗 Link a la noticia completa</label>
            <input type="url" name="link" placeholder="https://ejemplo.com/noticia-completa" value="${escapeHtmlAttr(v.link||'')}">
        </div>
        <div class="form-group">
            <label>Etiquetas / Tags <span style="font-size:11px;color:#6b7280;font-weight:400;">(separadas por coma)</span></label>
            <input type="text" name="tags" placeholder="Ej: economía, local, inauguración" value="${escapeHtmlAttr(v.tags||'')}">
        </div>
        <div class="form-group">
            <label>Categoría</label>
            <input type="text" name="categoria" placeholder="Ej: General, Economía, Cultura" value="${escapeHtmlAttr(v.categoria||'')}">
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
            <input type="text" name="ubicacion" placeholder="Ej: Plaza San Martín, Córdoba" value="${escapeHtmlAttr(v.ubicacion||'')}">
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
            <input type="text" name="titulo" required placeholder="Ej: Trivia de Tecnología" value="${escapeHtmlAttr(v.titulo||'')}">
        </div>
        <div class="form-group">
            <label>Descripción</label>
            <textarea name="descripcion" placeholder="De qué trata esta trivia...">${escapeHtml(v.descripcion||'')}</textarea>
        </div>
        <div class="form-group">
            <label>🖼 Imagen SVG ilustrativa (URL)</label>
            <input type="url" name="svg" placeholder="https://ejemplo.com/imagen.svg" value="${escapeHtmlAttr(v.svg||'')}">
            <p class="mini-map-hint">URL a una imagen SVG que ilustra la trivia (se muestra en el popup del mapa).</p>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="form-group">
                <label>Referencia del juego</label>
                <input type="text" name="referencia" placeholder="Ej: TRV-001" value="${escapeHtmlAttr(v.referencia||'')}">
            </div>
            <div class="form-group">
                <label>Tipo de juego</label>
                <input type="text" name="tipo" placeholder="Ej: Quiz, Adivinanza, Lógica" value="${escapeHtmlAttr(v.tipo||'')}">
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="form-group">
                <label>Edad recomendada</label>
                <input type="text" name="edad" placeholder="Ej: +8, Familiar, Adultos" value="${escapeHtmlAttr(v.edad||'')}">
            </div>
            <div class="form-group">
                <label>Emojis decorativos</label>
                <input type="text" name="emojis" placeholder="Ej: 🎯🧠🎲🕹️🏆" value="${escapeHtmlAttr(v.emojis||'')}">
            </div>
        </div>
        <div class="form-group">
            <label>🔗 App PHP (path relativo en apps/trivias/)</label>
            <input type="text" name="app_path" placeholder="Ej: mi_trivia.php" value="${escapeHtmlAttr(v.app_path||'')}">
            <p class="mini-map-hint">Nombre del archivo PHP dentro de la carpeta <code>apps/trivias/</code> del servidor. Se activará como link directo.</p>
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
            <input type="text" name="ubicacion" placeholder="Ej: Biblioteca Municipal" value="${escapeHtmlAttr(v.ubicacion||'')}">
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

function getSingularType(type) {
    return {
        noticias:'noticia',
        eventos:'evento',
        trivias:'trivia',
        encuestas:'encuesta',
        ofertas:'oferta',
        transmisiones:'transmision'
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
    // Sync RTE to hidden input before reading FormData
    syncRteToHidden('noticia');
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
        const endpoints = {
            noticia:'noticias',
            evento:'eventos',
            trivia:'trivias',
            encuesta:'encuestas',
            oferta:'ofertas',
            transmision:'transmisiones'
        };
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
    const endpoints = {
        noticia:'noticias',
        evento:'eventos',
        trivia:'trivias',
        encuesta:'encuestas',
        oferta:'ofertas',
        transmision:'transmisiones'
    };
    try {
        const res = await fetch('/api/' + endpoints[type] + '.php?action=delete&id=' + id, { method:'POST' });
        const result = await res.json();
        if (result.success) {
            const tabMap = {
                noticia:'noticias',
                evento:'eventos',
                trivia:'trivias',
                encuesta:'encuestas',
                oferta:'ofertas',
                transmision:'transmisiones'
            };
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

    // Tipos restringidos que requieren habilitación manual para consultas
    const RESTRICTED_TYPES = ['abogado', 'inmobiliaria', 'seguros', 'agente_inpi'];

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
                <th style="padding:10px 12px;text-align:center;font-weight:600;" title="Solo aplica a Estudio Jurídico, Inmobiliaria, Seguros y Agente INPI">Consulta Hab.</th>
                <th style="padding:10px 12px;text-align:center;font-weight:600;" title="Cualquier negocio: siempre entra en Consulta Masiva de su área">Masiva Siempre</th>
                <th style="padding:10px 12px;text-align:center;font-weight:600;" title="Solo negocios P: siempre entra en Consulta Proveedores de su rubro">P Siempre</th>
                <th style="padding:10px 12px;text-align:center;font-weight:600;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            ${items.map((n, i) => {
                const nombre       = n.name || n.nombre || '—';
                const tipo         = n.type || n.tipo || n.business_type || '—';
                const dir          = (n.address || n.direccion || '—').substring(0, 40);
                const owner        = n.owner_name || n.username || '—';
                const isRestricted = RESTRICTED_TYPES.includes(tipo);
                const habilitada   = n.consulta_habilitada == 1;
                const siempre      = n.consulta_siempre == 1;
                const pSiempre     = n.proveedor_siempre == 1;
                const isP          = n.es_proveedor == 1;

                const toggleHabCell = isRestricted
                    ? `<td style="padding:9px 12px;text-align:center;">
                         <button onclick="toggleConsultaHabilitada(${n.id}, this)"
                                 title="${habilitada ? 'Deshabilitar' : 'Habilitar'} consultas para este negocio"
                                 style="padding:4px 10px;border:none;border-radius:6px;cursor:pointer;font-size:11px;font-weight:700;
                                        background:${habilitada ? '#065f46' : '#9ca3af'};color:white;transition:background .2s;">
                           ${habilitada ? '✅ Hab.' : '⛔ No hab.'}
                         </button>
                       </td>`
                    : `<td style="padding:9px 12px;text-align:center;color:#d1d5db;font-size:11px;">—</td>`;

                const toggleSiempreCell = `<td style="padding:9px 12px;text-align:center;">
                     <button onclick="toggleConsultaSiempre(${n.id}, this)"
                             title="${siempre ? 'Quitar inclusión forzada en masivas' : 'Forzar inclusión en Consulta Masiva'}"
                             style="padding:4px 10px;border:none;border-radius:6px;cursor:pointer;font-size:11px;font-weight:700;
                                    background:${siempre ? '#1d4ed8' : '#9ca3af'};color:white;transition:background .2s;">
                       ${siempre ? '🔒 Siempre' : '🎲 A veces'}
                     </button>
                   </td>`;

                const togglePSiempreCell = isP
                    ? `<td style="padding:9px 12px;text-align:center;">
                         <button onclick="toggleProveedorSiempre(${n.id}, this)"
                                 title="${pSiempre ? 'Volver a modo aleatorio' : 'Forzar inclusión en Consulta Proveedores'}"
                                 style="padding:4px 10px;border:none;border-radius:6px;cursor:pointer;font-size:11px;font-weight:700;
                                        background:${pSiempre ? '#7e22ce' : '#9ca3af'};color:white;transition:background .2s;">
                           ${pSiempre ? '🔒 Siempre' : '🎲 Aleatorio'}
                         </button>
                       </td>`
                    : `<td style="padding:9px 12px;text-align:center;color:#d1d5db;font-size:11px;">—</td>`;

                return `
                <tr style="border-bottom:1px solid #f0f0f0;background:${i%2===0?'white':'#fafafa'};">
                    <td style="padding:9px 12px;color:#888;font-size:11px;">#${n.id}</td>
                    <td style="padding:9px 12px;font-weight:600;color:var(--text-primary);">${nombre}</td>
                    <td style="padding:9px 12px;color:var(--text-secondary);font-size:12px;">${tipo}</td>
                    <td style="padding:9px 12px;color:#4b5563;font-size:12px;">${n.mapita_id||'—'}</td>
                    <td style="padding:9px 12px;color:var(--text-secondary);font-size:12px;">${dir}</td>
                    <td style="padding:9px 12px;font-size:12px;color:#555;">${owner}</td>
                    ${toggleHabCell}
                    ${toggleSiempreCell}
                    ${togglePSiempreCell}
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

async function toggleConsultaHabilitada(businessId, btn) {
    const prevText = btn.textContent.trim();
    btn.disabled   = true;
    btn.textContent = '⏳';
    try {
        const res  = await fetch('/api/consultas.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ action: 'toggle_consulta_habilitada', business_id: businessId }),
        });
        const data = await res.json();
        if (data.success) {
            const on = data.data.consulta_habilitada === 1;
            btn.textContent      = on ? '✅ Hab.' : '⛔ No hab.';
            btn.style.background = on ? '#065f46' : '#9ca3af';
            btn.title            = (on ? 'Deshabilitar' : 'Habilitar') + ' consultas para este negocio';
            showToast(data.message, 'success');
        } else {
            btn.textContent = prevText;
            showToast('❌ ' + (data.error || 'Error'), 'error');
        }
    } catch {
        btn.textContent = prevText;
        showToast('❌ Error de red', 'error');
    } finally {
        btn.disabled = false;
    }
}

async function toggleConsultaSiempre(businessId, btn) {
    const prevText = btn.textContent.trim();
    btn.disabled   = true;
    btn.textContent = '⏳';
    try {
        const res  = await fetch('/api/consultas.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ action: 'toggle_consulta_siempre', business_id: businessId }),
        });
        const data = await res.json();
        if (data.success) {
            const on = data.data.consulta_siempre === 1;
            btn.textContent      = on ? '🔒 Siempre' : '🎲 A veces';
            btn.style.background = on ? '#1d4ed8' : '#9ca3af';
            btn.title            = on ? 'Quitar inclusión forzada en masivas' : 'Forzar inclusión en Consulta Masiva';
            showToast(data.message, 'success');
        } else {
            btn.textContent = prevText;
            showToast('❌ ' + (data.error || 'Error'), 'error');
        }
    } catch {
        btn.textContent = prevText;
        showToast('❌ Error de red', 'error');
    } finally {
        btn.disabled = false;
    }
}

async function toggleProveedorSiempre(businessId, btn) {
    const prevText = btn.textContent.trim();
    btn.disabled   = true;
    btn.textContent = '⏳';
    try {
        const res  = await fetch('/api/consultas.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ action: 'toggle_proveedor_siempre', business_id: businessId }),
        });
        const data = await res.json();
        if (data.success) {
            const on = data.data.proveedor_siempre === 1;
            btn.textContent      = on ? '🔒 Siempre' : '🎲 Aleatorio';
            btn.style.background = on ? '#7e22ce' : '#9ca3af';
            btn.title            = on ? 'Volver a modo aleatorio' : 'Forzar inclusión en Consulta Proveedores';
            showToast(data.message, 'success');
        } else {
            btn.textContent = prevText;
            showToast('❌ ' + (data.error || 'Error'), 'error');
        }
    } catch {
        btn.textContent = prevText;
        showToast('❌ Error de red', 'error');
    } finally {
        btn.disabled = false;
    }
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

// ── SECTORES INDUSTRIALES ─────────────────────────
let allSectores = [];
let editingSectorId = null;

const SECTOR_TYPE_LABELS = {
    mineria:'⛏ Minería', energia:'⚡ Energía', agro:'🌾 Agro',
    infraestructura:'🏗 Infraestructura', inmobiliario:'🏢 Inmobiliario', industrial:'🏭 Industrial'
};
const SECTOR_STATUS_LABELS = {
    activo:'✅ Activo', proyecto:'📐 Proyecto', potencial:'💡 Potencial'
};
const SECTOR_LEVEL_LABELS = {
    bajo:'🟢 Bajo', medio:'🟡 Medio', alto:'🔴 Alto'
};

async function loadSectores() {
    const el = document.getElementById('sectores-list');
    if (!el) return;
    el.innerHTML = '<p style="padding:16px;color:#888">⏳ Cargando sectores...</p>';
    try {
        const res    = await fetch('/api/industrial_sectors.php');
        const result = await res.json();
        allSectores = result.success ? (result.data || []) : [];
        renderSectores(allSectores);
    } catch(e) {
        el.innerHTML = '<p style="padding:16px;color:red">❌ Error cargando sectores: ' + e.message + '</p>';
    }
}

function filtrarSectores(q) {
    const s    = (q || '').toLowerCase();
    const type = document.getElementById('filter-sector-type')?.value || '';
    const stat = document.getElementById('filter-sector-status')?.value || '';
    const filtered = allSectores.filter(sec => {
        const matchQ    = !s    || (sec.name||'').toLowerCase().includes(s) || (sec.jurisdiction||'').toLowerCase().includes(s);
        const matchType = !type || sec.type === type;
        const matchStat = !stat || sec.status === stat;
        return matchQ && matchType && matchStat;
    });
    renderSectores(filtered);
}

function renderSectores(items) {
    const el    = document.getElementById('sectores-list');
    const count = document.getElementById('count-sectores');
    if (count) count.textContent = items.length + ' sector' + (items.length !== 1 ? 'es' : '');

    if (!items.length) {
        el.innerHTML = `
        <div style="text-align:center;padding:40px;color:var(--text-tertiary);">
            <p style="font-size:2rem;">🏭</p>
            <p>No hay sectores industriales creados aún</p>
            <button class="btn btn-primary" onclick="openSectorModal()">Crear primero</button>
        </div>`;
        return;
    }

    el.innerHTML = `
    <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;background:white;border-radius:8px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.08);">
        <thead>
            <tr style="background:var(--primary);color:white;">
                <th style="padding:10px 12px;text-align:left;">ID</th>
                <th style="padding:10px 12px;text-align:left;">Nombre</th>
                <th style="padding:10px 12px;text-align:left;">Tipo</th>
                <th style="padding:10px 12px;text-align:left;">Subtipo</th>
                <th style="padding:10px 12px;text-align:left;">Estado</th>
                <th style="padding:10px 12px;text-align:left;">Inversión</th>
                <th style="padding:10px 12px;text-align:left;">Riesgo</th>
                <th style="padding:10px 12px;text-align:left;">Jurisdicción</th>
                <th style="padding:10px 12px;text-align:center;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            ${items.map((s, i) => `
            <tr style="border-bottom:1px solid #f0f0f0;background:${i%2===0?'white':'#fafafa'};">
                <td style="padding:9px 12px;color:#888;font-size:11px;">#${s.id}</td>
                <td style="padding:9px 12px;font-weight:600;color:var(--text-primary);">${s.name||'—'}</td>
                <td style="padding:9px 12px;">${SECTOR_TYPE_LABELS[s.type]||s.type}</td>
                <td style="padding:9px 12px;color:#6b7280;font-size:12px;">${s.subtype||'—'}</td>
                <td style="padding:9px 12px;">${SECTOR_STATUS_LABELS[s.status]||s.status}</td>
                <td style="padding:9px 12px;">${SECTOR_LEVEL_LABELS[s.investment_level]||s.investment_level}</td>
                <td style="padding:9px 12px;">${SECTOR_LEVEL_LABELS[s.risk_level]||s.risk_level}</td>
                <td style="padding:9px 12px;color:#6b7280;font-size:12px;">${s.jurisdiction||'—'}</td>
                <td style="padding:9px 12px;text-align:center;">
                    <div style="display:flex;gap:6px;justify-content:center;">
                        <button class="btn btn-sm btn-secondary" onclick="editSector(${s.id})">✏️ Editar</button>
                        <button class="btn btn-sm btn-danger"    onclick="deleteSector(${s.id})">🗑 Eliminar</button>
                    </div>
                </td>
            </tr>`).join('')}
        </tbody>
    </table>
    </div>`;
}

function openSectorModal(data) {
    editingSectorId = data ? data.id : null;
    const v = data || {};
    document.getElementById('sector-modal-title').textContent = editingSectorId ? 'Editar Sector Industrial' : 'Nuevo Sector Industrial';
    document.getElementById('sector-form-name').value         = v.name || '';
    document.getElementById('sector-form-type').value         = v.type || 'industrial';
    document.getElementById('sector-form-subtype').value      = v.subtype || '';
    document.getElementById('sector-form-status').value       = v.status || 'potencial';
    document.getElementById('sector-form-investment').value   = v.investment_level || 'medio';
    document.getElementById('sector-form-risk').value         = v.risk_level || 'medio';
    document.getElementById('sector-form-jurisdiction').value = v.jurisdiction || '';
    document.getElementById('sector-form-description').value  = v.description || '';
    const geo = v.geometry ? (typeof v.geometry === 'string' ? v.geometry : JSON.stringify(v.geometry, null, 2)) : '';
    document.getElementById('sector-form-geometry').value = geo;
    document.getElementById('sector-modal').style.display = 'flex';
}

function closeSectorModal() {
    document.getElementById('sector-modal').style.display = 'none';
    editingSectorId = null;
}

async function editSector(id) {
    try {
        const res    = await fetch('/api/industrial_sectors.php?id=' + id);
        const result = await res.json();
        if (result.success && result.data) openSectorModal(result.data);
        else showToast('No se pudo cargar el sector', 'error');
    } catch(e) {
        showToast('Error: ' + e.message, 'error');
    }
}

async function deleteSector(id) {
    if (!confirm('¿Eliminar este sector industrial? Esta acción no se puede deshacer.')) return;
    try {
        const res    = await fetch('/api/industrial_sectors.php?action=delete&id=' + id, { method: 'POST' });
        const result = await res.json();
        if (result.success) { loadSectores(); showToast('✅ Sector eliminado', 'success'); }
        else showToast('❌ ' + (result.message || 'Error'), 'error');
    } catch(e) {
        showToast('❌ Error: ' + e.message, 'error');
    }
}

async function handleSectorSubmit(e) {
    e.preventDefault();
    const geoRaw = document.getElementById('sector-form-geometry').value.trim();
    let geo;
    try { geo = JSON.parse(geoRaw); } catch(err) {
        showToast('❌ El GeoJSON ingresado no es válido', 'error'); return;
    }
    if (!geo.type) { showToast('❌ El GeoJSON debe tener la clave "type"', 'error'); return; }

    const payload = {
        name:             document.getElementById('sector-form-name').value.trim(),
        type:             document.getElementById('sector-form-type').value,
        subtype:          document.getElementById('sector-form-subtype').value.trim() || null,
        status:           document.getElementById('sector-form-status').value,
        investment_level: document.getElementById('sector-form-investment').value,
        risk_level:       document.getElementById('sector-form-risk').value,
        jurisdiction:     document.getElementById('sector-form-jurisdiction').value.trim() || null,
        description:      document.getElementById('sector-form-description').value.trim() || null,
        geometry:         geo,
    };
    if (editingSectorId) payload.id = editingSectorId;

    const action = editingSectorId ? 'update' : 'create';
    const url    = '/api/industrial_sectors.php?action=' + action + (editingSectorId ? '&id=' + editingSectorId : '');

    const btn = document.getElementById('sector-btn-submit');
    btn.disabled = true; btn.textContent = 'Guardando...';
    try {
        const res    = await fetch(url, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
        const result = await res.json();
        if (result.success) {
            closeSectorModal(); loadSectores();
            showToast('✅ Sector industrial ' + (editingSectorId ? 'actualizado' : 'creado') + ' correctamente', 'success');
        } else {
            showToast('❌ ' + (result.message || 'Error al guardar'), 'error');
        }
    } catch(err) {
        showToast('❌ Error de red: ' + err.message, 'error');
    } finally {
        btn.disabled = false; btn.textContent = 'Guardar';
    }
}

// ── Encuestas stats ─────────────────────────────
async function loadEncuestasStats() {
    const el = document.getElementById('encuestas-stats');
    if (!el) return;
    try {
        const res = await fetch('/api/encuestas.php?action=stats_global');
        const result = await res.json();
        if (!result.success) { el.innerHTML = ''; return; }
        const s = result.data;
        const top = s.top_encuestas || [];
        const maxPart = top.reduce((m, e) => Math.max(m, e.participantes || 0), 0);

        const metricsHTML = `
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px;margin-bottom:16px;">
                <div style="background:white;border-radius:10px;padding:14px 16px;box-shadow:0 1px 4px rgba(0,0,0,.08);text-align:center;">
                    <div style="font-size:1.7rem;font-weight:700;color:var(--primary)">${s.activas}</div>
                    <div style="font-size:.75rem;color:var(--text-secondary);margin-top:2px;">Activas</div>
                </div>
                <div style="background:white;border-radius:10px;padding:14px 16px;box-shadow:0 1px 4px rgba(0,0,0,.08);text-align:center;">
                    <div style="font-size:1.7rem;font-weight:700;color:#6366f1">${s.total}</div>
                    <div style="font-size:.75rem;color:var(--text-secondary);margin-top:2px;">Total encuestas</div>
                </div>
                <div style="background:white;border-radius:10px;padding:14px 16px;box-shadow:0 1px 4px rgba(0,0,0,.08);text-align:center;">
                    <div style="font-size:1.7rem;font-weight:700;color:#10b981">${s.total_respuestas}</div>
                    <div style="font-size:.75rem;color:var(--text-secondary);margin-top:2px;">Respuestas</div>
                </div>
                <div style="background:white;border-radius:10px;padding:14px 16px;box-shadow:0 1px 4px rgba(0,0,0,.08);text-align:center;">
                    <div style="font-size:1.7rem;font-weight:700;color:#f59e0b">${s.total_participantes}</div>
                    <div style="font-size:.75rem;color:var(--text-secondary);margin-top:2px;">Participantes únicos</div>
                </div>
            </div>`;

        let chartHTML = '';
        if (top.length) {
            chartHTML = `
                <div style="background:white;border-radius:10px;padding:16px 18px;box-shadow:0 1px 4px rgba(0,0,0,.08);">
                    <div style="font-size:.8rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.05em;margin-bottom:12px;">🏆 Top encuestas por participación</div>
                    ${top.map(e => `
                        <div style="margin-bottom:10px;">
                            <div style="display:flex;justify-content:space-between;font-size:.8rem;margin-bottom:3px;">
                                <span style="color:var(--text-primary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:75%;">${e.titulo || ('Encuesta #' + e.id)}</span>
                                <span style="color:var(--text-secondary);flex-shrink:0;margin-left:8px;font-weight:600;">${e.participantes}</span>
                            </div>
                            <div style="background:#f3f4f6;border-radius:4px;height:8px;">
                                <div style="background:linear-gradient(90deg,#667eea,#764ba2);height:8px;border-radius:4px;width:${maxPart > 0 ? (e.participantes / maxPart * 100).toFixed(1) : 0}%;transition:width .4s ease;"></div>
                            </div>
                        </div>
                    `).join('')}
                </div>`;
        }

        el.innerHTML = metricsHTML + chartHTML;
    } catch(err) {
        el.innerHTML = '';
    }
}

// ── Init ─────────────────────────────────────────
document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeModal(); closeSectorModal(); } });
window.addEventListener('load', () => {
    const tab = '<?php echo $tab; ?>';
    if (tab === 'marcas')          loadMarcas();
    else if (tab === 'negocios')   loadNegocios();
    else if (tab === 'moderacion') { loadReports(); loadAuditLog(); }
    else if (tab === 'sectores')   loadSectores();
    else {
        loadData(tab);
        if (tab === 'encuestas') loadEncuestasStats();
    }
});
</script>
</body>
</html>
