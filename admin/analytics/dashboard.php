<?php
/**
 * Admin Analytics Dashboard
 * Tablero de control interno – KPIs, presencia, feed de eventos, rankings
 */

session_start();
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../includes/db_helper.php';

setSecurityHeaders();

if (!isAdmin()) {
    header('Location: ../../auth/login.php');
    exit();
}

$db          = getDbConnection();
$hasTables   = $db
    && mapitaTableExists($db, 'analytics_events')
    && mapitaTableExists($db, 'user_presence');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📊 Analytics · Mapita Admin</title>
    <link rel="stylesheet" href="/css/variables-luxury.css">
    <link rel="stylesheet" href="/css/components-buttons.css">
    <link rel="stylesheet" href="/css/components-cards.css">
    <style>
        /* ── Base ─────────────────────────────────────────────── */
        body { background: var(--bg-tertiary); color: var(--text-primary);
               font-family: var(--font-family-base); margin: 0; padding: 0; }
        .container { max-width: 1200px; margin: 0 auto; padding: var(--space-lg); }
        a { color: inherit; }

        /* ── Header ───────────────────────────────────────────── */
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 28px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        .page-header h1 { margin: 0; font-size: 1.5rem; }
        .page-header p  { margin: 4px 0 0; opacity: .85; font-size: .85rem; }
        .header-nav { display: flex; gap: 10px; flex-wrap: wrap; }
        .header-nav a {
            background: rgba(255,255,255,.2); color: white;
            padding: 8px 16px; border-radius: 8px; text-decoration: none;
            font-size: .82rem; font-weight: 700;
            border: 1.5px solid rgba(255,255,255,.35);
        }
        .header-nav a:hover { background: rgba(255,255,255,.35); }

        /* ── Toolbar ──────────────────────────────────────────── */
        .toolbar {
            display: flex; gap: 12px; align-items: center; flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .toolbar label { font-size: 13px; font-weight: 600; }
        .toolbar select, .toolbar input[type=number] {
            padding: 7px 12px; border: 1px solid #d1d5db; border-radius: 7px;
            font-size: 13px; background: white;
        }
        .btn-refresh {
            padding: 8px 18px; background: #667eea; color: white; border: none;
            border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 700;
        }
        .btn-refresh:hover { background: #5a67d8; }
        .auto-label { font-size: 12px; color: #6b7280; }

        /* ── Section titles ───────────────────────────────────── */
        .section-title {
            font-size: 1rem; font-weight: 700; color: #1f2937;
            margin: 28px 0 12px; padding-bottom: 8px;
            border-bottom: 2px solid #e5e7eb;
        }

        /* ── KPI cards ────────────────────────────────────────── */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 14px;
            margin-bottom: 24px;
        }
        .kpi-card {
            background: white; border-radius: 10px; padding: 16px 18px;
            box-shadow: 0 1px 6px rgba(0,0,0,.07);
            border-top: 3px solid #667eea;
        }
        .kpi-card.green  { border-top-color: #10b981; }
        .kpi-card.yellow { border-top-color: #f59e0b; }
        .kpi-card.blue   { border-top-color: #3b82f6; }
        .kpi-card.purple { border-top-color: #8b5cf6; }
        .kpi-value { font-size: 2rem; font-weight: 800; color: #111827; line-height: 1; }
        .kpi-label { font-size: 12px; color: #6b7280; margin-top: 5px; }

        /* ── Table ────────────────────────────────────────────── */
        .data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .data-table th {
            text-align: left; padding: 10px 12px; background: #f9fafb;
            border-bottom: 2px solid #e5e7eb; font-weight: 700; color: #374151;
        }
        .data-table td {
            padding: 9px 12px; border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tr:hover td { background: #fafafa; }
        .table-wrap {
            background: white; border-radius: 10px; overflow: hidden;
            box-shadow: 0 1px 6px rgba(0,0,0,.07); overflow-x: auto;
        }

        /* ── Status badges ────────────────────────────────────── */
        .badge-online  { background: #d1fae5; color: #065f46; padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 700; }
        .badge-idle    { background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 700; }
        .badge-offline { background: #f3f4f6; color: #6b7280; padding: 2px 8px; border-radius: 9999px; font-size: 11px; }

        /* ── Event type badge ─────────────────────────────────── */
        .ev-badge {
            display: inline-block; padding: 2px 8px; border-radius: 9999px;
            font-size: 11px; font-weight: 600; background: #ede9fe; color: #5b21b6;
        }

        /* ── Tabs (period) ────────────────────────────────────── */
        .period-tabs { display: flex; gap: 8px; margin-bottom: 16px; }
        .period-tab {
            padding: 6px 16px; border-radius: 8px; border: 1.5px solid #d1d5db;
            background: white; cursor: pointer; font-size: 13px; font-weight: 600;
            color: #374151;
        }
        .period-tab.active { background: #667eea; color: white; border-color: #667eea; }

        /* ── Feed filters ─────────────────────────────────────── */
        .feed-filters { display: flex; gap: 10px; margin-bottom: 14px; flex-wrap: wrap; }
        .feed-filters select { padding: 7px 12px; border: 1px solid #d1d5db; border-radius: 7px; font-size: 13px; background: white; }

        /* ── Pagination ───────────────────────────────────────── */
        .pagination { display: flex; gap: 8px; margin-top: 12px; align-items: center; font-size: 13px; }
        .pagination button { padding: 6px 14px; border-radius: 7px; border: 1px solid #d1d5db; background: white; cursor: pointer; font-size: 13px; }
        .pagination button:hover { background: #f0f0f0; }
        .pagination button:disabled { opacity: .4; cursor: default; }

        /* ── Alert ────────────────────────────────────────────── */
        .alert { padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .alert-warning { background: #fef3c7; border: 1px solid #fcd34d; color: #92400e; }
        .alert-info    { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }

        /* ── Loading spinner ──────────────────────────────────── */
        .loading { color: #6b7280; font-size: 13px; padding: 16px 0; text-align: center; }

        /* ── Two column grid ──────────────────────────────────── */
        .col-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 768px) { .col-grid-2 { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="container">

    <!-- Header -->
    <div class="page-header">
        <div>
            <h1>📊 Analytics · Sistema</h1>
            <p>Tablero de control interno — KPIs, presencia, eventos, rankings</p>
        </div>
        <nav class="header-nav">
            <a href="/admin/index.php">🛠️ Panel Admin</a>
            <a href="/">🗺️ Mapa</a>
            <a href="/logout">🚪 Salir</a>
        </nav>
    </div>

    <?php if (!$hasTables): ?>
    <div class="alert alert-warning">
        ⚠️ Las tablas de analítica aún no existen. Ejecutá la migración
        <code>migrations/027_analytics_presence.sql</code> para activar este módulo.
    </div>
    <?php else: ?>

    <!-- Toolbar -->
    <div class="toolbar">
        <label>🔄 Auto-refresh</label>
        <select id="refresh-interval">
            <option value="0">Desactivado</option>
            <option value="15" selected>Cada 15 s</option>
            <option value="30">Cada 30 s</option>
            <option value="60">Cada 60 s</option>
        </select>
        <button class="btn-refresh" onclick="refreshAll()">⟳ Actualizar ahora</button>
        <span class="auto-label" id="last-updated"></span>
    </div>

    <!-- KPIs -->
    <div class="section-title">📈 KPIs</div>
    <div class="kpi-grid" id="kpi-grid">
        <div class="loading">⏳ Cargando…</div>
    </div>

    <!-- Online now -->
    <div class="section-title">🟢 En línea ahora</div>
    <div class="table-wrap" id="online-table-wrap">
        <div class="loading">⏳ Cargando…</div>
    </div>

    <!-- Events feed -->
    <div class="section-title">📋 Feed de eventos recientes</div>
    <div class="feed-filters">
        <select id="feed-event-type" onchange="loadFeed()">
            <option value="">Todos los tipos</option>
            <option value="map_open">map_open</option>
            <option value="business_open">business_open</option>
            <option value="search">search</option>
            <option value="filter_change">filter_change</option>
            <option value="whatsapp_click">whatsapp_click</option>
            <option value="phone_click">phone_click</option>
            <option value="website_click">website_click</option>
            <option value="directions_click">directions_click</option>
        </select>
    </div>
    <div class="table-wrap" id="feed-table-wrap">
        <div class="loading">⏳ Cargando…</div>
    </div>
    <div class="pagination" id="feed-pagination"></div>

    <!-- Rankings -->
    <div class="section-title">🏆 Rankings de usuarios</div>
    <div class="period-tabs">
        <button class="period-tab active" onclick="setRankingPeriod('today', this)">Hoy</button>
        <button class="period-tab" onclick="setRankingPeriod('7d', this)">7 días</button>
        <button class="period-tab" onclick="setRankingPeriod('30d', this)">30 días</button>
    </div>
    <div class="col-grid-2" id="rankings-grid">
        <div class="loading">⏳ Cargando…</div>
    </div>

    <!-- Top businesses -->
    <div class="section-title">🏢 Top Negocios</div>
    <div class="period-tabs">
        <button class="period-tab active" onclick="setBusinessPeriod('today', this)">Hoy</button>
        <button class="period-tab" onclick="setBusinessPeriod('7d', this)">7 días</button>
        <button class="period-tab" onclick="setBusinessPeriod('30d', this)">30 días</button>
    </div>
    <div class="col-grid-2" id="businesses-grid">
        <div class="loading">⏳ Cargando…</div>
    </div>

    <?php endif; ?>

</div><!-- /container -->

<?php if ($hasTables): ?>
<script>
/* ── State ─────────────────────────────────────────────────────── */
let feedOffset       = 0;
const FEED_LIMIT     = 30;
let feedTotal        = 0;
let rankingPeriod    = 'today';
let businessPeriod   = 'today';
let autoRefreshTimer = null;

/* ── Fetch helper ──────────────────────────────────────────────── */
async function apiFetch(section, params = {}) {
    const qs = new URLSearchParams({ section, ...params }).toString();
    const r  = await fetch('/api/analytics_dashboard.php?' + qs);
    return r.json();
}

/* ── Format helpers ────────────────────────────────────────────── */
function fmtDate(dt) {
    if (!dt) return '—';
    const d = new Date(dt.replace(' ', 'T') + 'Z');
    return d.toLocaleString('es-AR', { timeZone: 'America/Argentina/Buenos_Aires' });
}
function fmtAgo(sec) {
    if (sec < 60) return sec + 's';
    if (sec < 3600) return Math.floor(sec / 60) + 'm';
    return Math.floor(sec / 3600) + 'h';
}
function escHtml(s) {
    if (!s) return '—';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ── KPIs ──────────────────────────────────────────────────────── */
async function loadKPIs() {
    const resp = await apiFetch('kpis');
    const g    = document.getElementById('kpi-grid');
    if (!resp.ok) { g.innerHTML = '<p style="color:red;padding:10px;">Error al cargar KPIs</p>'; return; }
    const d = resp.data;
    g.innerHTML = `
        <div class="kpi-card">
            <div class="kpi-value">${d.total_users.toLocaleString()}</div>
            <div class="kpi-label">👤 Usuarios registrados</div>
        </div>
        <div class="kpi-card green">
            <div class="kpi-value">${d.online_now.toLocaleString()}</div>
            <div class="kpi-label">🟢 En línea ahora</div>
        </div>
        <div class="kpi-card yellow">
            <div class="kpi-value">${d.idle_now.toLocaleString()}</div>
            <div class="kpi-label">🟡 Inactivos (2-10 min)</div>
        </div>
        <div class="kpi-card blue">
            <div class="kpi-value">${d.events_today.toLocaleString()}</div>
            <div class="kpi-label">📊 Eventos hoy</div>
        </div>
        <div class="kpi-card purple">
            <div class="kpi-value">${d.total_events.toLocaleString()}</div>
            <div class="kpi-label">📊 Eventos totales</div>
        </div>
        <div class="kpi-card blue">
            <div class="kpi-value">${d.active_today.toLocaleString()}</div>
            <div class="kpi-label">👥 Activos hoy</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-value">${d.active_7d.toLocaleString()}</div>
            <div class="kpi-label">👥 Activos 7 días</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-value">${d.active_30d.toLocaleString()}</div>
            <div class="kpi-label">👥 Activos 30 días</div>
        </div>
    `;
}

/* ── Online table ──────────────────────────────────────────────── */
async function loadOnline() {
    const resp = await apiFetch('online');
    const wrap = document.getElementById('online-table-wrap');
    if (!resp.ok) { wrap.innerHTML = '<p style="color:red;padding:10px;">Error al cargar presencia</p>'; return; }
    if (!resp.data.length) {
        wrap.innerHTML = '<p style="color:#6b7280;padding:14px;">Sin usuarios activos actualmente</p>';
        return;
    }
    const rows = resp.data.map(r => `
        <tr>
            <td><span class="badge-${r.status}">${r.status === 'online' ? '🟢 Online' : '🟡 Inactivo'}</span></td>
            <td>${escHtml(r.username || r.visitor_id || '—')}</td>
            <td>${escHtml(r.current_path)}</td>
            <td>${fmtAgo(r.seconds_ago)} atrás</td>
        </tr>
    `).join('');
    wrap.innerHTML = `
        <table class="data-table">
            <thead><tr><th>Estado</th><th>Usuario</th><th>Ruta actual</th><th>Última act.</th></tr></thead>
            <tbody>${rows}</tbody>
        </table>
    `;
}

/* ── Feed ──────────────────────────────────────────────────────── */
async function loadFeed(resetOffset = false) {
    if (resetOffset) feedOffset = 0;
    const eventType = document.getElementById('feed-event-type').value;
    const params    = { limit: FEED_LIMIT, offset: feedOffset };
    if (eventType) params.event_type = eventType;

    const resp = await apiFetch('feed', params);
    const wrap = document.getElementById('feed-table-wrap');
    const pag  = document.getElementById('feed-pagination');

    if (!resp.ok) { wrap.innerHTML = '<p style="color:red;padding:10px;">Error al cargar feed</p>'; return; }
    feedTotal = resp.total;

    if (!resp.data.length) {
        wrap.innerHTML = '<p style="color:#6b7280;padding:14px;">Sin eventos registrados</p>';
        pag.innerHTML = '';
        return;
    }

    const rows = resp.data.map(r => {
        let meta = '';
        if (r.meta_json) {
            try {
                const m = JSON.parse(r.meta_json);
                meta = '<span style="color:#6b7280;font-size:11px;">' + escHtml(JSON.stringify(m).substring(0, 80)) + '</span>';
            } catch(_) { meta = escHtml(r.meta_json.substring(0, 80)); }
        }
        return `
            <tr>
                <td>${escHtml(r.created_at)}</td>
                <td><span class="ev-badge">${escHtml(r.event_type)}</span></td>
                <td>${escHtml(r.username || r.visitor_id || '—')}</td>
                <td>${escHtml(r.business_name || (r.business_id ? '#' + r.business_id : '—'))}</td>
                <td>${meta}</td>
                <td style="color:#9ca3af;font-size:11px;">${escHtml(r.ip)}</td>
            </tr>
        `;
    }).join('');

    wrap.innerHTML = `
        <table class="data-table">
            <thead><tr><th>Fecha</th><th>Tipo</th><th>Usuario</th><th>Negocio</th><th>Meta</th><th>IP</th></tr></thead>
            <tbody>${rows}</tbody>
        </table>
    `;

    // Pagination
    const totalPages = Math.ceil(feedTotal / FEED_LIMIT);
    const currPage   = Math.floor(feedOffset / FEED_LIMIT) + 1;
    pag.innerHTML    = `
        <button onclick="feedPrev()" ${feedOffset === 0 ? 'disabled' : ''}>← Ant.</button>
        <span>Página ${currPage} / ${totalPages} &nbsp;(${feedTotal} total)</span>
        <button onclick="feedNext()" ${feedOffset + FEED_LIMIT >= feedTotal ? 'disabled' : ''}>Sig. →</button>
    `;
}
function feedPrev() { feedOffset = Math.max(0, feedOffset - FEED_LIMIT); loadFeed(); }
function feedNext() { if (feedOffset + FEED_LIMIT < feedTotal) { feedOffset += FEED_LIMIT; loadFeed(); } }

/* ── Rankings ──────────────────────────────────────────────────── */
function setRankingPeriod(p, el) {
    rankingPeriod = p;
    document.querySelectorAll('.period-tab').forEach(b => {
        if (b.closest('#rankings-grid') === null && b.closest('#businesses-grid') === null)
            b.classList.toggle('active', b === el);
    });
    // Update only ranking tabs (first group)
    el.closest('.period-tabs').querySelectorAll('.period-tab').forEach(b => {
        b.classList.toggle('active', b === el);
    });
    loadRankings();
}

async function loadRankings() {
    const grid = document.getElementById('rankings-grid');
    grid.innerHTML = '<div class="loading">⏳ Cargando…</div>';
    const resp = await apiFetch('rankings', { period: rankingPeriod });
    if (!resp.ok) { grid.innerHTML = '<p style="color:red;">Error</p>'; return; }
    const d = resp.data;

    function rankTable(rows, valKey, valLabel) {
        if (!rows.length) return '<p style="color:#6b7280;padding:10px 14px;font-size:13px;">Sin datos</p>';
        return `<table class="data-table">
            <thead><tr><th>#</th><th>Usuario</th><th>${valLabel}</th></tr></thead>
            <tbody>${rows.map((r, i) => `
                <tr>
                    <td style="font-weight:700;color:#6b7280;">${i + 1}</td>
                    <td>${escHtml(r.username || '#' + r.user_id)}</td>
                    <td style="font-weight:700;">${Number(r[valKey]).toLocaleString()}</td>
                </tr>`).join('')}
            </tbody>
        </table>`;
    }

    grid.innerHTML = `
        <div>
            <div style="font-weight:700;font-size:13px;color:#374151;margin-bottom:8px;">📊 Por eventos</div>
            <div class="table-wrap">${rankTable(d.by_events, 'total_events', 'Eventos')}</div>
        </div>
        <div>
            <div style="font-weight:700;font-size:13px;color:#374151;margin-bottom:8px;">🏢 Por negocios únicos</div>
            <div class="table-wrap">${rankTable(d.by_businesses, 'unique_businesses', 'Negocios')}</div>
        </div>
        <div style="grid-column:1/-1">
            <div style="font-weight:700;font-size:13px;color:#374151;margin-bottom:8px;">💬 Por clics de contacto</div>
            <div class="table-wrap">${rankTable(d.by_clicks, 'valuable_clicks', 'Clics')}</div>
        </div>
    `;
}

/* ── Top businesses ────────────────────────────────────────────── */
function setBusinessPeriod(p, el) {
    businessPeriod = p;
    el.closest('.period-tabs').querySelectorAll('.period-tab').forEach(b => {
        b.classList.toggle('active', b === el);
    });
    loadBusinesses();
}

async function loadBusinesses() {
    const grid = document.getElementById('businesses-grid');
    grid.innerHTML = '<div class="loading">⏳ Cargando…</div>';
    const resp = await apiFetch('top_businesses', { period: businessPeriod });
    if (!resp.ok) { grid.innerHTML = '<p style="color:red;">Error</p>'; return; }
    const d = resp.data;

    function bizTable(rows, cols) {
        if (!rows.length) return '<p style="color:#6b7280;padding:10px 14px;font-size:13px;">Sin datos</p>';
        const headers = cols.map(c => `<th>${c.label}</th>`).join('');
        const body = rows.map((r, i) => {
            const cells = cols.map(c => {
                const v = r[c.key];
                const fmt = c.pct ? (v !== null && v !== undefined ? v + '%' : '—') :
                            (v !== null && v !== undefined ? Number(v).toLocaleString() : '—');
                const style = c.bold ? ' style="font-weight:700;"' : '';
                return `<td${style}>${c.name ? escHtml(v || '—') : fmt}</td>`;
            }).join('');
            return `<tr><td style="color:#6b7280;font-weight:700;">${i + 1}</td>${cells}</tr>`;
        }).join('');
        return `<table class="data-table"><thead><tr><th>#</th>${headers}</tr></thead><tbody>${body}</tbody></table>`;
    }

    grid.innerHTML = `
        <div>
            <div style="font-weight:700;font-size:13px;color:#374151;margin-bottom:8px;">👁️ Más vistos</div>
            <div class="table-wrap">${bizTable(d.top_views, [
                { key: 'business_name', label: 'Negocio', name: true },
                { key: 'views', label: 'Vistas', bold: true },
                { key: 'unique_visitors', label: 'Únicos' }
            ])}</div>
        </div>
        <div>
            <div style="font-weight:700;font-size:13px;color:#374151;margin-bottom:8px;">💬 Más contactados</div>
            <div class="table-wrap">${bizTable(d.top_contacts, [
                { key: 'business_name', label: 'Negocio', name: true },
                { key: 'total_contacts', label: 'Contactos', bold: true },
                { key: 'whatsapp_clicks', label: 'WA' },
                { key: 'phone_clicks', label: 'Tel' },
                { key: 'contact_rate', label: 'Tasa', pct: true }
            ])}</div>
        </div>
    `;
}

/* ── Refresh all ───────────────────────────────────────────────── */
async function refreshAll() {
    await Promise.all([loadKPIs(), loadOnline(), loadFeed(true), loadRankings(), loadBusinesses()]);
    document.getElementById('last-updated').textContent =
        'Actualizado: ' + new Date().toLocaleTimeString('es-AR');
}

/* ── Auto-refresh ──────────────────────────────────────────────── */
function updateAutoRefresh() {
    if (autoRefreshTimer) clearInterval(autoRefreshTimer);
    const secs = parseInt(document.getElementById('refresh-interval').value, 10);
    if (secs > 0) autoRefreshTimer = setInterval(refreshAll, secs * 1000);
}
document.getElementById('refresh-interval').addEventListener('change', updateAutoRefresh);

/* ── Init ──────────────────────────────────────────────────────── */
refreshAll();
updateAutoRefresh();
</script>
<?php endif; ?>

</body>
</html>
