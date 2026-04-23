<?php
/**
 * views/industry/dashboard_industries.php
 * Dashboard de Industrias para usuario registrado
 * Similar a "Mis Negocios" / "Gestión de Marcas"
 */
if (session_status() === PHP_SESSION_NONE) session_start();

ini_set('display_errors', 0);
error_reporting(0);

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

require_once __DIR__ . '/../../core/helpers.php';

setSecurityHeaders();

$userId  = (int)$_SESSION['user_id'];
$isAdmin = isAdmin();
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'Usuario');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🏭 Mis Industrias — Mapita</title>

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f0f2f7;
            color: #1a202c;
            min-height: 100vh;
        }

        /* ── HEADER ─────────────────────────────────────────── */
        .header {
            background: linear-gradient(135deg, #1B3B6F 0%, #0d2247 100%);
            color: white;
            padding: 0 28px;
            display: flex;
            align-items: center;
            gap: 16px;
            height: 64px;
            box-shadow: 0 4px 20px rgba(0,0,0,.25);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .header-logo { font-size: 1.4em; font-weight: 800; letter-spacing: -0.5px; }
        .header-logo span { opacity: .6; font-weight: 400; font-size: .7em; margin-left: 6px; }
        .header-nav {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .header-nav a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: .84em;
            font-weight: 600;
            text-decoration: none;
            transition: all .2s;
            white-space: nowrap;
        }
        .btn-ghost  { color: rgba(255,255,255,.85); border: 1.5px solid rgba(255,255,255,.3); }
        .btn-ghost:hover { background: rgba(255,255,255,.1); color: white; }
        .btn-amber  { background: #f59e0b; color: #1a202c; }
        .btn-amber:hover { background: #d97706; }
        .btn-blue   { background: #0ea5e9; color: white; }
        .btn-blue:hover { background: #0284c7; }

        /* ── MAIN ───────────────────────────────────────────── */
        .main { max-width: 1100px; margin: 0 auto; padding: 32px 20px 60px; }

        /* ── PAGE HEADER ────────────────────────────────────── */
        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 28px;
            flex-wrap: wrap;
        }
        .page-title { font-size: 1.7em; font-weight: 800; color: #1B3B6F; }
        .page-subtitle { color: #64748b; font-size: .9em; margin-top: 4px; }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 11px 22px;
            background: #1B3B6F;
            color: white;
            border-radius: 10px;
            font-size: .9em;
            font-weight: 700;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: background .2s;
            white-space: nowrap;
        }
        .btn-primary:hover { background: #0d2247; }

        /* ── MIGRATION NOTICE ───────────────────────────────── */
        .migration-notice {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #92400e;
        }

        /* ── FILTERS BAR ────────────────────────────────────── */
        .filters-bar {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
            background: white;
            padding: 14px 18px;
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0,0,0,.07);
            margin-bottom: 20px;
        }
        .filters-bar input,
        .filters-bar select {
            padding: 9px 13px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            background: #f9fafb;
            transition: border-color .2s;
        }
        .filters-bar input:focus,
        .filters-bar select:focus { border-color: #1B3B6F; background: white; }
        .filters-bar input { flex: 1; min-width: 200px; }
        .count-badge {
            margin-left: auto;
            background: #e0e7ff;
            color: #3730a3;
            padding: 5px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        /* ── CARDS GRID ─────────────────────────────────────── */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 16px;
        }

        .industry-card {
            background: white;
            border-radius: 14px;
            box-shadow: 0 1px 6px rgba(0,0,0,.08);
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            transition: box-shadow .2s, transform .15s;
            border-left: 4px solid #e5e7eb;
        }
        .industry-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,.13); transform: translateY(-2px); }
        .industry-card.status-activa    { border-left-color: #22c55e; }
        .industry-card.status-borrador  { border-left-color: #f59e0b; }
        .industry-card.status-archivada { border-left-color: #9ca3af; opacity: .7; }

        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 8px;
        }
        .card-icon { font-size: 2rem; line-height: 1; flex-shrink: 0; }
        .card-name  { font-size: 1.05em; font-weight: 700; color: #1B3B6F; line-height: 1.3; }
        .card-sector { font-size: .82em; color: #6366f1; font-weight: 600; margin-top: 2px; }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 700;
            flex-shrink: 0;
        }
        .badge-activa    { background: #dcfce7; color: #166534; }
        .badge-borrador  { background: #fef9c3; color: #854d0e; }
        .badge-archivada { background: #f3f4f6; color: #6b7280; }

        .card-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            font-size: 12px;
            color: #64748b;
        }
        .card-meta span { display: inline-flex; align-items: center; gap: 3px; }

        .card-desc {
            font-size: 13px;
            color: #475569;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: auto;
        }
        .btn-sm {
            padding: 6px 13px;
            border-radius: 7px;
            font-size: .8em;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all .15s;
        }
        .btn-edit    { background: #eff6ff; color: #1d4ed8; }
        .btn-edit:hover { background: #dbeafe; }
        .btn-archive { background: #fef3c7; color: #92400e; }
        .btn-archive:hover { background: #fde68a; }
        .btn-delete  { background: #fef2f2; color: #b91c1c; }
        .btn-delete:hover { background: #fee2e2; }
        .btn-view    { background: #f0fdf4; color: #166534; }
        .btn-view:hover { background: #dcfce7; }

        /* ── EMPTY STATE ────────────────────────────────────── */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }
        .empty-state-icon { font-size: 4rem; margin-bottom: 16px; }
        .empty-state h3   { font-size: 1.2em; color: #1B3B6F; margin-bottom: 8px; }
        .empty-state p    { color: #64748b; margin-bottom: 20px; font-size: .95em; }

        /* ── LOADING ────────────────────────────────────────── */
        #loading-state { text-align: center; padding: 40px; color: #64748b; }

        /* ── ALERT ──────────────────────────────────────────── */
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-size: 14px;
            display: none;
        }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
        .alert-error   { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
        .alert.show    { display: block; }

        /* ── RESPONSIVE ─────────────────────────────────────── */
        @media (max-width: 640px) {
            .header { padding: 0 14px; }
            .main   { padding: 20px 12px 40px; }
            .page-header { flex-direction: column; }
            .cards-grid  { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- HEADER -->
<header class="header">
    <div class="header-logo">🗺️ Mapita <span>Industrias</span></div>
    <nav class="header-nav">
        <a href="/" class="btn-ghost">🗺️ Mapa</a>
        <a href="/mis-negocios" class="btn-ghost">🏢 Negocios</a>
        <a href="/dashboard_brands" class="btn-ghost">🏷️ Marcas</a>
        <?php if ($isAdmin): ?>
            <a href="/admin?tab=sectores" class="btn-ghost">🏭 Catálogo Sectores</a>
        <?php endif; ?>
        <a href="/logout" class="btn-ghost">🔓 Salir</a>
    </nav>
</header>

<main class="main">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div>
            <h1 class="page-title">🏭 Mis Industrias</h1>
            <p class="page-subtitle">Gestioná tus industrias: creá, editá y archivá tus registros industriales.</p>
        </div>
        <a href="/industry_new" class="btn-primary">+ Nueva Industria</a>
    </div>

    <!-- MIGRATION NOTICE -->
    <div class="migration-notice">
        ⚠️ <strong>Nota:</strong> Para usar este módulo asegurate de haber ejecutado
        <code>migrations/014_industrial_sectors.sql</code> y <code>migrations/015_industries.sql</code>
        en tu base de datos.
    </div>

    <!-- ALERTS -->
    <div id="alert-box" class="alert"></div>

    <!-- FILTERS -->
    <div class="filters-bar">
        <input type="text" id="search-input" placeholder="🔍 Buscar por nombre…" oninput="debounceLoad()">
        <select id="filter-sector" onchange="loadIndustries()">
            <option value="">Todos los sectores</option>
        </select>
        <select id="filter-status" onchange="loadIndustries()">
            <option value="">Todos los estados</option>
            <option value="activa">✅ Activa</option>
            <option value="borrador">📝 Borrador</option>
            <option value="archivada">📦 Archivada</option>
        </select>
        <span class="count-badge" id="count-badge">—</span>
    </div>

    <!-- LIST -->
    <div id="loading-state">⏳ Cargando industrias…</div>
    <div id="industries-grid" class="cards-grid" style="display:none;"></div>
    <div id="empty-state" class="empty-state" style="display:none;">
        <div class="empty-state-icon">🏭</div>
        <h3>Aún no tenés industrias registradas</h3>
        <p>Creá tu primera industria para empezar a gestionarla en Mapita.</p>
        <a href="/industry_new" class="btn-primary">+ Crear primera industria</a>
    </div>

</main>

<script>
const IS_ADMIN = <?php echo $isAdmin ? 'true' : 'false'; ?>;
let allIndustries = [];
let debounceTimer = null;

const STATUS_LABELS = {
    activa:    { label: '✅ Activa',    cls: 'badge-activa' },
    borrador:  { label: '📝 Borrador',  cls: 'badge-borrador' },
    archivada: { label: '📦 Archivada', cls: 'badge-archivada' },
};
const SECTOR_ICONS = {
    mineria: '⛏️', energia: '⚡', agro: '🌾',
    infraestructura: '🏗️', inmobiliario: '🏢', industrial: '🏭',
};

// ── Load sectors dropdown ──────────────────────────────────────────────────────
async function loadSectors() {
    try {
        const r = await fetch('/api/industrial_sectors.php');
        const j = await r.json();
        if (!j.success) return;
        const sel = document.getElementById('filter-sector');
        (j.data || []).forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = (SECTOR_ICONS[s.type] || '🏭') + ' ' + s.name;
            sel.appendChild(opt);
        });
    } catch(e) { /* silencio */ }
}

// ── Load industries ────────────────────────────────────────────────────────────
async function loadIndustries() {
    document.getElementById('loading-state').style.display  = 'block';
    document.getElementById('industries-grid').style.display = 'none';
    document.getElementById('empty-state').style.display    = 'none';

    const search   = document.getElementById('search-input').value.trim();
    const sectorId = document.getElementById('filter-sector').value;
    const status   = document.getElementById('filter-status').value;

    const params = new URLSearchParams({ limit: 200 });
    if (search)   params.set('search', search);
    if (sectorId) params.set('sector_id', sectorId);
    if (status)   params.set('status', status);

    try {
        const r = await fetch('/api/industries.php?' + params);
        const j = await r.json();
        if (!j.success) {
            showAlert('Error al cargar industrias: ' + (j.message || ''), 'error');
            document.getElementById('loading-state').style.display = 'none';
            return;
        }
        allIndustries = j.data.items || [];
        renderIndustries(allIndustries, j.data.total || 0);
    } catch(e) {
        showAlert('Error de red: ' + e.message, 'error');
        document.getElementById('loading-state').style.display = 'none';
    }
}

function debounceLoad() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(loadIndustries, 350);
}

// ── Render ─────────────────────────────────────────────────────────────────────
function renderIndustries(list, total) {
    document.getElementById('loading-state').style.display  = 'none';
    document.getElementById('count-badge').textContent = total + ' industria' + (total !== 1 ? 's' : '');

    if (!list.length) {
        document.getElementById('industries-grid').style.display = 'none';
        document.getElementById('empty-state').style.display     = 'block';
        return;
    }

    document.getElementById('empty-state').style.display     = 'none';
    document.getElementById('industries-grid').style.display  = 'grid';

    document.getElementById('industries-grid').innerHTML = list.map(i => {
        const st  = STATUS_LABELS[i.status] || { label: i.status, cls: '' };
        const ico = SECTOR_ICONS[i.sector_type] || '🏭';
        const sec = i.sector_name ? `${ico} ${esc(i.sector_name)}` : '—';
        const loc = [i.city, i.region, i.country].filter(Boolean).join(', ') || '';
        const emp = i.employees_range ? `👥 ${esc(i.employees_range)}` : '';
        const rev = i.annual_revenue  ? `💼 ${esc(i.annual_revenue)}`  : '';

        return `<div class="industry-card status-${esc(i.status)}">
            <div class="card-top">
                <div>
                    <div class="card-name">${esc(i.name)}</div>
                    <div class="card-sector">${sec}</div>
                </div>
                <span class="status-badge ${st.cls}">${st.label}</span>
            </div>
            ${i.description ? `<div class="card-desc">${esc(i.description)}</div>` : ''}
            <div class="card-meta">
                ${loc ? `<span>📍 ${esc(loc)}</span>` : ''}
                ${emp ? `<span>${emp}</span>` : ''}
                ${rev ? `<span>${rev}</span>` : ''}
                ${i.naics_code ? `<span>NAICS: ${esc(i.naics_code)}</span>` : ''}
            </div>
            <div class="card-actions">
                <a href="/industry_edit?id=${i.id}" class="btn-sm btn-edit">✏️ Editar</a>
                ${i.status !== 'archivada'
                    ? `<button class="btn-sm btn-archive" onclick="archiveIndustry(${i.id})">📦 Archivar</button>`
                    : ''}
                <button class="btn-sm btn-delete" onclick="deleteIndustry(${i.id}, '${esc(i.name)}')">🗑️ Eliminar</button>
            </div>
        </div>`;
    }).join('');
}

// ── Archive ────────────────────────────────────────────────────────────────────
async function archiveIndustry(id) {
    if (!confirm('¿Archivar esta industria?')) return;
    try {
        const r = await fetch(`/api/industries.php?action=archive&id=${id}`, { method: 'POST',
            headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({}) });
        const j = await r.json();
        if (j.success) { showAlert('Industria archivada.', 'success'); loadIndustries(); }
        else showAlert(j.message || 'Error al archivar.', 'error');
    } catch(e) { showAlert('Error de red.', 'error'); }
}

// ── Delete ─────────────────────────────────────────────────────────────────────
async function deleteIndustry(id, name) {
    if (!confirm(`¿Eliminar la industria "${name}"? Esta acción no se puede deshacer.`)) return;
    try {
        const r = await fetch(`/api/industries.php?action=delete&id=${id}`, { method: 'POST',
            headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({}) });
        const j = await r.json();
        if (j.success) { showAlert('Industria eliminada.', 'success'); loadIndustries(); }
        else showAlert(j.message || 'Error al eliminar.', 'error');
    } catch(e) { showAlert('Error de red.', 'error'); }
}

// ── Helpers ────────────────────────────────────────────────────────────────────
function esc(s) {
    if (s == null) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function showAlert(msg, type) {
    const el = document.getElementById('alert-box');
    el.className = `alert alert-${type} show`;
    el.textContent = msg;
    setTimeout(() => { el.className = 'alert'; }, 4000);
}

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadSectors();
    loadIndustries();

    // Show flash message if redirected after save
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('saved') === '1') showAlert('Industria guardada correctamente.', 'success');
    if (urlParams.get('deleted') === '1') showAlert('Industria eliminada.', 'success');
});
</script>
</body>
</html>
