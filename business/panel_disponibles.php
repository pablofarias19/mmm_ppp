<?php
/**
 * Panel de Disponibles — editor del titular del negocio
 * GET /panel-disponibles?id=N
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit();
}

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../includes/db_helper.php';

setSecurityHeaders();

$userId     = (int)$_SESSION['user_id'];
$businessId = (int)($_GET['id'] ?? 0);

if ($businessId <= 0) {
    header("Location: /mis-negocios");
    exit();
}

$db = getDbConnection();

// Verificar propiedad / delegación
if (!canManageBusiness($userId, $businessId)) {
    header("Location: /mis-negocios");
    exit();
}

// Datos del negocio
$stB = $db->prepare("SELECT id, name, disponibles_activo FROM businesses WHERE id = ? LIMIT 1");
$stB->execute([$businessId]);
$biz = $stB->fetch();

if (!$biz) {
    header("Location: /mis-negocios");
    exit();
}

// Ítems actuales
$stI = $db->prepare(
    "SELECT * FROM disponibles_items WHERE business_id = ? ORDER BY orden ASC, id ASC"
);
$stI->execute([$businessId]);
$items = $stI->fetchAll();

// Contador de solicitudes pendientes
$stO = $db->prepare(
    "SELECT COUNT(*) FROM disponibles_solicitudes WHERE business_id = ? AND estado = 'pendiente'"
);
$stO->execute([$businessId]);
$ordenesCount = (int)$stO->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Disponibles — <?php echo htmlspecialchars($biz['name']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/css/disponibles.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f0f2f7;
            color: #1a202c;
            min-height: 100vh;
        }
        .page-header {
            background: linear-gradient(135deg, #1B3B6F 0%, #0d2247 100%);
            color: #fff;
            padding: 0 28px;
            height: 64px;
            display: flex;
            align-items: center;
            gap: 16px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 20px rgba(0,0,0,.25);
        }
        .page-header h1 { font-size: 1.05em; font-weight: 800; }
        .page-header a  {
            margin-left: auto;
            color: rgba(255,255,255,.8);
            font-size: .84em;
            text-decoration: none;
            border: 1.5px solid rgba(255,255,255,.3);
            padding: 7px 14px;
            border-radius: 8px;
        }
        .page-header a:hover { background: rgba(255,255,255,.1); }
        .main {
            max-width: 1050px;
            margin: 0 auto;
            padding: 32px 20px 60px;
        }
        .card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,.06);
            padding: 24px;
            margin-bottom: 24px;
        }
        .section-title {
            font-size: 1em;
            font-weight: 700;
            color: #1B3B6F;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .toggle-row {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 10px;
        }
        .toggle-label {
            font-size: .9em;
            color: #374151;
            font-weight: 600;
        }
        /* Toggle switch */
        .switch { position: relative; display: inline-block; width: 48px; height: 26px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute; cursor: pointer; inset: 0;
            background: #d1d5db; border-radius: 26px; transition: .3s;
        }
        .slider::before {
            position: absolute; content: "";
            height: 20px; width: 20px;
            left: 3px; bottom: 3px;
            background: #fff; border-radius: 50%; transition: .3s;
        }
        input:checked + .slider { background: #10b981; }
        input:checked + .slider::before { transform: translateX(22px); }

        .orders-count {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
            border-radius: 20px;
            padding: 4px 14px;
            font-size: .82em;
            font-weight: 700;
        }
        .msg-box {
            padding: 12px 18px;
            border-radius: 10px;
            font-size: .88em;
            font-weight: 600;
            margin-bottom: 16px;
            display: none;
        }
        .msg-ok  { background: #d1fae5; color: #065f46; display: block; }
        .msg-err { background: #fee2e2; color: #991b1b; display: block; }

        /* Remove row button */
        .btn-remove-row {
            background: none;
            border: none;
            color: #ef4444;
            cursor: pointer;
            font-size: 1.1em;
            padding: 4px;
            border-radius: 6px;
            line-height: 1;
        }
        .btn-remove-row:hover { background: #fee2e2; }

        .actions-bar {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 16px;
        }
        .btn {
            padding: 10px 22px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: .9em;
            font-weight: 700;
            transition: filter .2s, transform .15s;
        }
        .btn:hover  { filter: brightness(1.08); transform: translateY(-1px); }
        .btn-save   { background: #10b981; color: #fff; }
        .btn-add    { background: #667eea; color: #fff; }
        .btn-back   { background: #e5e7eb; color: #374151; }

        /* Solicitudes section */
        .solicitud-card {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 14px 18px;
            margin-bottom: 12px;
        }
        .solicitud-card.pendiente { border-left: 4px solid #f59e0b; }
        .solicitud-card.confirmada { border-left: 4px solid #10b981; }
        .solicitud-card.desistida  { border-left: 4px solid #d1d5db; opacity: .7; }
        .sol-meta { font-size: .78em; color: #9ca3af; margin-bottom: 8px; }
        .sol-items { font-size: .82em; }
        .sol-items .si  { color: #059669; font-weight: 700; }
        .sol-items .no  { color: #9ca3af; }
    </style>
</head>
<body>

<header class="page-header">
    <h1>📦 Panel de Disponibles — <?php echo htmlspecialchars($biz['name']); ?></h1>
    <?php if ($ordenesCount > 0): ?>
    <span class="orders-count">🔔 <?php echo $ordenesCount; ?> solicitud<?php echo $ordenesCount > 1 ? 'es' : ''; ?> pendiente<?php echo $ordenesCount > 1 ? 's' : ''; ?></span>
    <?php endif; ?>
    <a href="/mis-negocios">← Mis Negocios</a>
</header>

<main class="main">

    <!-- ── Activar módulo ──────────────────────────────────────────────── -->
    <div class="card">
        <div class="section-title">⚙️ Configuración del módulo</div>
        <div class="toggle-row">
            <label class="switch">
                <input type="checkbox" id="toggle-modulo" <?php echo $biz['disponibles_activo'] ? 'checked' : ''; ?>>
                <span class="slider"></span>
            </label>
            <span class="toggle-label" id="toggle-label">
                <?php echo $biz['disponibles_activo'] ? 'Módulo activo — visible para usuarios' : 'Módulo inactivo — no visible aún'; ?>
            </span>
        </div>
        <p style="font-size:.82em;color:#9ca3af;">
            Cuando está activo, los usuarios verán el botón <strong>$$$</strong> en el popup del negocio en el mapa
            y podrán hacer solicitudes de los ítems disponibles.
        </p>
    </div>

    <!-- ── Editor de ítems ─────────────────────────────────────────────── -->
    <div class="card">
        <div class="section-title">📋 Ítems disponibles
            <span style="font-size:.75em;color:#9ca3af;font-weight:400;">(el titular puede modificar esto en cualquier momento)</span>
        </div>

        <div id="msg-items" class="msg-box"></div>

        <div class="disp-table-wrap">
            <table class="disp-table" id="items-table">
                <thead>
                    <tr>
                        <th style="min-width:130px;">Precio ($)</th>
                        <th style="min-width:80px;">Cantidad</th>
                        <th style="min-width:150px;">Tipo de bien</th>
                        <th style="min-width:110px;">Desde</th>
                        <th style="min-width:110px;">Hasta</th>
                        <th style="min-width:90px;">Horario inicio</th>
                        <th style="min-width:90px;">Horario fin</th>
                        <th style="min-width:160px;">Servicio</th>
                        <th style="min-width:50px;"></th>
                    </tr>
                </thead>
                <tbody id="items-body">
                    <?php foreach ($items as $idx => $it): ?>
                    <tr data-idx="<?php echo $idx; ?>">
                        <td>
                            <div style="display:flex;gap:6px;align-items:center;">
                                <input type="checkbox" class="precio-ad" title="A definir"
                                       <?php echo $it['precio_a_definir'] ? 'checked' : ''; ?>
                                       onchange="togglePrecio(this)">
                                <input type="number" class="precio-val" min="0" max="99999999.99" step="0.01"
                                       placeholder="0.00"
                                       value="<?php echo $it['precio'] !== null ? htmlspecialchars($it['precio']) : ''; ?>"
                                       style="<?php echo $it['precio_a_definir'] ? 'display:none;' : ''; ?>width:90px;">
                                <span class="precio-ad-label" style="font-size:.8em;color:#9ca3af;<?php echo !$it['precio_a_definir'] ? 'display:none;' : ''; ?>">a definir</span>
                            </div>
                        </td>
                        <td><input type="number" class="cantidad-val" min="0" max="999" placeholder="000" value="<?php echo htmlspecialchars($it['cantidad'] ?? ''); ?>" style="width:60px;"></td>
                        <td><input type="text" class="tipo-bien-val" maxlength="30" placeholder="máx 30 chars" value="<?php echo htmlspecialchars($it['tipo_bien'] ?? ''); ?>"></td>
                        <td><input type="date" class="desde-val" value="<?php echo htmlspecialchars($it['disponible_desde'] ?? ''); ?>"></td>
                        <td><input type="date" class="hasta-val" value="<?php echo htmlspecialchars($it['disponible_hasta'] ?? ''); ?>"></td>
                        <td><input type="time" class="horario-ini-val" value="<?php echo htmlspecialchars(substr($it['horario_inicio'] ?? '', 0, 5)); ?>"></td>
                        <td><input type="time" class="horario-fin-val" value="<?php echo htmlspecialchars(substr($it['horario_fin'] ?? '', 0, 5)); ?>"></td>
                        <td><input type="text" class="servicio-val" maxlength="45" placeholder="máx 45 chars" value="<?php echo htmlspecialchars($it['servicio'] ?? ''); ?>"></td>
                        <td><button type="button" class="btn-remove-row" onclick="removeRow(this)" title="Eliminar fila">✕</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <button type="button" class="disp-add-row" onclick="addRow()">➕ Agregar fila</button>

        <div class="actions-bar">
            <button type="button" class="btn btn-save" onclick="saveItems()">💾 Guardar ítems</button>
            <a href="/mis-negocios" class="btn btn-back">← Volver</a>
        </div>
    </div>

    <!-- ── Solicitudes recibidas ───────────────────────────────────────── -->
    <div class="card">
        <div class="section-title">📬 Solicitudes recibidas
            <?php if ($ordenesCount > 0): ?>
            <span class="orders-count">🔔 <?php echo $ordenesCount; ?> pendiente<?php echo $ordenesCount > 1 ? 's' : ''; ?></span>
            <?php endif; ?>
        </div>
        <div id="solicitudes-list"><p style="color:#9ca3af;font-size:.85em;">Cargando...</p></div>
    </div>

</main>

<script>
const BIZ_ID = <?php echo $businessId; ?>;

// ── Toggle módulo ──────────────────────────────────────────────────────────
document.getElementById('toggle-modulo').addEventListener('change', function() {
    const activo = this.checked ? 1 : 0;
    fetch('/api/disponibles.php?action=toggle_modulo', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ business_id: BIZ_ID, activo: activo })
    })
    .then(r => r.json())
    .then(d => {
        const lbl = document.getElementById('toggle-label');
        if (d.success) {
            lbl.textContent = activo
                ? 'Módulo activo — visible para usuarios'
                : 'Módulo inactivo — no visible aún';
        } else {
            showMsg('msg-items', d.message, false);
            // Revertir
            document.getElementById('toggle-modulo').checked = !activo;
        }
    })
    .catch(() => showMsg('msg-items', 'Error de conexión', false));
});

// ── Editor de ítems ────────────────────────────────────────────────────────
function togglePrecio(cb) {
    const row   = cb.closest('tr');
    const valIn = row.querySelector('.precio-val');
    const adLbl = row.querySelector('.precio-ad-label');
    if (cb.checked) {
        valIn.style.display = 'none';
        adLbl.style.display = '';
        valIn.value = '';
    } else {
        valIn.style.display = '';
        adLbl.style.display = 'none';
    }
}

function addRow() {
    const tbody = document.getElementById('items-body');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <div style="display:flex;gap:6px;align-items:center;">
                <input type="checkbox" class="precio-ad" title="A definir" onchange="togglePrecio(this)">
                <input type="number" class="precio-val" min="0" max="99999999.99" step="0.01" placeholder="0.00" style="width:90px;">
                <span class="precio-ad-label" style="font-size:.8em;color:#9ca3af;display:none;">a definir</span>
            </div>
        </td>
        <td><input type="number" class="cantidad-val" min="0" max="999" placeholder="000" style="width:60px;"></td>
        <td><input type="text"   class="tipo-bien-val" maxlength="30" placeholder="máx 30 chars"></td>
        <td><input type="date"   class="desde-val"></td>
        <td><input type="date"   class="hasta-val"></td>
        <td><input type="time"   class="horario-ini-val"></td>
        <td><input type="time"   class="horario-fin-val"></td>
        <td><input type="text"   class="servicio-val" maxlength="45" placeholder="máx 45 chars"></td>
        <td><button type="button" class="btn-remove-row" onclick="removeRow(this)" title="Eliminar fila">✕</button></td>`;
    tbody.appendChild(tr);
}

function removeRow(btn) {
    btn.closest('tr').remove();
}

function saveItems() {
    const rows  = document.querySelectorAll('#items-body tr');
    const items = [];
    let valid = true;

    rows.forEach((tr, idx) => {
        const precioAd  = tr.querySelector('.precio-ad').checked;
        const precioVal = tr.querySelector('.precio-val').value.trim();
        const cantidad  = tr.querySelector('.cantidad-val').value.trim();
        const tipoBien  = tr.querySelector('.tipo-bien-val').value.trim();
        const desde     = tr.querySelector('.desde-val').value;
        const hasta     = tr.querySelector('.hasta-val').value;
        const horIni    = tr.querySelector('.horario-ini-val').value;
        const horFin    = tr.querySelector('.horario-fin-val').value;
        const servicio  = tr.querySelector('.servicio-val').value.trim();

        if (!precioAd && precioVal !== '' && parseFloat(precioVal) < 0) {
            tr.querySelector('.precio-val').classList.add('error');
            valid = false;
        } else {
            tr.querySelector('.precio-val').classList.remove('error');
        }

        items.push({
            precio_a_definir: precioAd,
            precio:           (!precioAd && precioVal !== '') ? precioVal : null,
            cantidad:         cantidad !== '' ? parseInt(cantidad) : null,
            tipo_bien:        tipoBien || null,
            disponible_desde: desde || null,
            disponible_hasta: hasta || null,
            horario_inicio:   horIni || null,
            horario_fin:      horFin || null,
            servicio:         servicio || null,
            activo:           1,
            orden:            idx,
        });
    });

    if (!valid) { showMsg('msg-items', 'Verificá los valores marcados en rojo', false); return; }

    fetch('/api/disponibles.php?action=save_items', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ business_id: BIZ_ID, items })
    })
    .then(r => r.json())
    .then(d => showMsg('msg-items', d.message, d.success))
    .catch(() => showMsg('msg-items', 'Error de conexión', false));
}

// ── Solicitudes ────────────────────────────────────────────────────────────
function loadSolicitudes() {
    fetch('/api/disponibles_solicitudes.php?business_id=' + BIZ_ID)
    .then(r => r.json())
    .then(d => {
        const cont = document.getElementById('solicitudes-list');
        if (!d.success || !d.data.length) {
            cont.innerHTML = '<p style="color:#9ca3af;font-size:.85em;">Aún no hay solicitudes.</p>';
            return;
        }
        cont.innerHTML = d.data.map(sol => {
            const selItems = (sol.items || []).filter(i => i.seleccionado == 1);
            const noItems  = (sol.items || []).filter(i => i.seleccionado == 0);
            const fechaStr = new Date(sol.created_at).toLocaleString('es-AR');
            return `<div class="solicitud-card ${sol.estado}">
                <div class="sol-meta">
                    <strong>#${sol.id}</strong> · ${sol.email} · ${fechaStr} ·
                    <strong>${sol.estado.toUpperCase()}</strong>
                </div>
                <div class="sol-items">
                    ${selItems.map(i => `<span class="si">✅ ${i.tipo_bien || i.servicio || 'Ítem'}</span>`).join(' ')}
                    ${noItems.map(i => `<span class="no">✗ ${i.tipo_bien || i.servicio || 'Ítem'}</span>`).join(' ')}
                </div>
                ${sol.estado === 'pendiente' ? `
                <div style="margin-top:10px;display:flex;gap:8px;">
                    <button class="btn btn-save" onclick="confirmarSolicitud(${sol.id})" style="font-size:.8em;padding:7px 14px;">✔ Confirmar</button>
                </div>` : ''}
            </div>`;
        }).join('');
    })
    .catch(() => {
        document.getElementById('solicitudes-list').innerHTML =
            '<p style="color:#ef4444;font-size:.85em;">Error al cargar solicitudes.</p>';
    });
}

function confirmarSolicitud(id) {
    fetch('/api/disponibles_solicitudes.php?action=confirmar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ solicitud_id: id })
    })
    .then(r => r.json())
    .then(d => {
        showMsg('msg-items', d.message, d.success);
        if (d.success) loadSolicitudes();
    });
}

// ── Utils ──────────────────────────────────────────────────────────────────
function showMsg(id, text, ok) {
    const el = document.getElementById(id);
    if (!el) return;
    el.className = 'msg-box ' + (ok ? 'msg-ok' : 'msg-err');
    el.textContent = text;
    setTimeout(() => { el.className = 'msg-box'; }, 5000);
}

loadSolicitudes();
</script>
</body>
</html>
