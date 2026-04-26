<?php
/**
 * Búsqueda global admin (interfaz de usuario)
 * Interfaz para el endpoint /admin/api/search.php
 */

session_start();
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../../core/helpers.php';

setSecurityHeaders();

if (!isAdmin()) {
    header('Location: ../../auth/login.php');
    exit;
}

$q = trim($_GET['q'] ?? '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Búsqueda Global Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/css/variables-luxury.css">
    <style>
        body { background: var(--bg-tertiary); color: var(--text-primary); font-family: var(--font-family-base); margin: 0; }
        header { background: var(--primary-dark); color: #fff; padding: 14px 28px; display: flex; justify-content: space-between; align-items: center; }
        header h1 { margin: 0; font-size: 1.1rem; }
        header a { color: #aec6ff; text-decoration: none; font-size: 13px; }
        header a:hover { color: #fff; }
        .container { max-width: 1000px; margin: 28px auto; padding: 0 18px; }
        .search-bar { display: flex; gap: 10px; margin-bottom: 24px; }
        .search-bar input { flex: 1; padding: 10px 16px; border: 1px solid #ced4da; border-radius: 8px; font-size: 15px; }
        .search-bar button { padding: 10px 22px; background: #3d56c9; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 700; }
        .search-bar button:hover { background: #2c44b8; }
        .section { background: #fff; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,.08); margin-bottom: 22px; overflow: hidden; }
        .section-header { background: #2d3748; color: #fff; padding: 10px 16px; font-weight: 700; font-size: 13px; display: flex; justify-content: space-between; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #f0f0f0; }
        th { background: #f8f9fa; font-weight: 700; color: #555; }
        tr:hover { background: #f8f9fa; }
        .empty { padding: 14px 16px; color: #888; font-size: 13px; }
        #loading { display: none; text-align: center; padding: 20px; font-size: 14px; color: #888; }
        .badge { padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 700; }
        .badge-admin { background: #ffc107; color: #333; }
        .badge-user  { background: #e9ecef; color: #555; }
    </style>
</head>
<body>
<header>
    <h1>🔍 Búsqueda Global Anti-Fraude</h1>
    <div>
        <a href="/admin/dashboard.php">← Dashboard</a>
        &nbsp;|&nbsp;
        <a href="/admin/limits/dashboard.php">⚙️ Límites</a>
    </div>
</header>

<div class="container">
    <div class="search-bar">
        <input type="text" id="search-input" placeholder="Buscar: nombre, apellido, email, negocio, marca... (ej: farias, fa, far)" value="<?php echo htmlspecialchars($q); ?>" autofocus>
        <button onclick="doSearch()">Buscar</button>
    </div>
    <div style="font-size:12px;color:#888;margin-bottom:18px;">
        Busca en usuarios (username, email, nombre/apellido), negocios (nombre, tipo, dirección, propietario) y marcas (nombre, rubro).
    </div>

    <div id="loading">⏳ Buscando...</div>
    <div id="results"></div>
</div>

<script>
const searchInput = document.getElementById('search-input');

searchInput.addEventListener('keydown', e => {
    if (e.key === 'Enter') doSearch();
});

async function doSearch() {
    const q = searchInput.value.trim();
    if (q.length < 2) {
        document.getElementById('results').innerHTML = '<p style="color:#888;font-size:13px;">Ingresá al menos 2 caracteres.</p>';
        return;
    }
    document.getElementById('loading').style.display = 'block';
    document.getElementById('results').innerHTML = '';
    try {
        const res = await fetch('/admin/api/search.php?q=' + encodeURIComponent(q) + '&type=all');
        const data = await res.json();
        document.getElementById('loading').style.display = 'none';
        if (!data.success) {
            document.getElementById('results').innerHTML = '<p style="color:red;">Error: ' + escHtml(data.message) + '</p>';
            return;
        }
        renderResults(data.results, data.total, q);
    } catch(e) {
        document.getElementById('loading').style.display = 'none';
        document.getElementById('results').innerHTML = '<p style="color:red;">Error de red.</p>';
    }
}

function renderResults(results, total, q) {
    let html = '<p style="font-size:13px;color:#555;margin-bottom:16px;">Se encontraron <strong>' + total + '</strong> resultado(s) para <em>"' + escHtml(q) + '"</em></p>';

    // Usuarios
    if (results.users && results.users.length > 0) {
        html += '<div class="section"><div class="section-header"><span>👤 Usuarios (' + results.users.length + ')</span></div><table>';
        html += '<thead><tr><th>ID</th><th>Username</th><th>Titular</th><th>Email</th><th>Negocios</th><th>Rol</th><th>Registrado</th></tr></thead><tbody>';
        results.users.forEach(u => {
            const titular = [u.first_name, u.last_name].filter(Boolean).join(' ') || '—';
            const role    = u.is_admin ? '<span class="badge badge-admin">Admin</span>' : '<span class="badge badge-user">Usuario</span>';
            html += `<tr>
                <td>${esc(u.id)}</td>
                <td><strong>${esc(u.username)}</strong></td>
                <td>${esc(titular)}</td>
                <td>${esc(u.email || '—')}</td>
                <td style="font-size:12px;">${esc(u.business_names || '—')}</td>
                <td>${role}</td>
                <td>${esc((u.created_at||'').substring(0,10))}</td>
            </tr>`;
        });
        html += '</tbody></table></div>';
    }

    // Negocios
    if (results.businesses && results.businesses.length > 0) {
        html += '<div class="section"><div class="section-header"><span>🏢 Negocios (' + results.businesses.length + ')</span></div><table>';
        html += '<thead><tr><th>ID</th><th>Nombre</th><th>Tipo</th><th>Dirección</th><th>Propietario</th><th>Visible</th><th>Registrado</th></tr></thead><tbody>';
        results.businesses.forEach(b => {
            const owner = [b.owner_first_name, b.owner_last_name].filter(Boolean).join(' ')
                        || b.owner_username || '—';
            const vis   = b.visible ? '✅' : '⛔';
            html += `<tr>
                <td>${esc(b.id)}</td>
                <td><a href="/business/view_business.php?id=${esc(b.id)}" target="_blank">${esc(b.name)}</a></td>
                <td>${esc(b.business_type)}</td>
                <td>${esc(b.address||'')}</td>
                <td>${esc(owner)}</td>
                <td>${vis}</td>
                <td>${esc((b.created_at||'').substring(0,10))}</td>
            </tr>`;
        });
        html += '</tbody></table></div>';
    }

    // Brands
    if (results.brands && results.brands.length > 0) {
        html += '<div class="section"><div class="section-header"><span>🏷️ Marcas (brands) (' + results.brands.length + ')</span></div><table>';
        html += '<thead><tr><th>ID</th><th>Nombre</th><th>Rubro</th><th>Ubicación</th><th>Propietario</th><th>Visible</th></tr></thead><tbody>';
        results.brands.forEach(b => {
            const vis = b.visible ? '✅' : '⛔';
            html += `<tr>
                <td>${esc(b.id)}</td>
                <td>${esc(b.nombre)}</td>
                <td>${esc(b.rubro||'—')}</td>
                <td>${esc(b.ubicacion||'—')}</td>
                <td>${esc(b.owner_username||'—')}</td>
                <td>${vis}</td>
            </tr>`;
        });
        html += '</tbody></table></div>';
    }

    // Marcas (legacy)
    if (results.marcas && results.marcas.length > 0) {
        html += '<div class="section"><div class="section-header"><span>🔖 Marcas (registro) (' + results.marcas.length + ')</span></div><table>';
        html += '<thead><tr><th>ID</th><th>Nombre</th><th>Rubro</th><th>Ubicación</th><th>Estado</th><th>Propietario</th></tr></thead><tbody>';
        results.marcas.forEach(m => {
            html += `<tr>
                <td>${esc(m.id)}</td>
                <td>${esc(m.nombre)}</td>
                <td>${esc(m.rubro||'—')}</td>
                <td>${esc(m.ubicacion||'—')}</td>
                <td>${esc(m.estado||'—')}</td>
                <td>${esc(m.owner_username||'—')}</td>
            </tr>`;
        });
        html += '</tbody></table></div>';
    }

    if (total === 0) {
        html = '<div class="section"><p class="empty">No se encontraron resultados para <em>"' + escHtml(q) + '"</em>.</p></div>';
    }

    document.getElementById('results').innerHTML = html;
}

function esc(v) { return escHtml(String(v ?? '')); }
function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Auto-ejecutar si ya hay query en la URL
<?php if ($q !== ''): ?>
doSearch();
<?php endif; ?>
</script>
</body>
</html>
