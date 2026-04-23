<?php
/**
 * Panel de Trabajo — gestión de postulaciones para "Busco Empleados/as"
 * GET /panel-trabajo?id=N
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

if (!canManageBusiness($userId, $businessId)) {
    header("Location: /mis-negocios");
    exit();
}

// Datos del negocio
$stB = $db->prepare(
    "SELECT id, name, job_offer_active, job_offer_position, job_offer_description, job_offer_url
     FROM businesses WHERE id = ? LIMIT 1"
);
$stB->execute([$businessId]);
$biz = $stB->fetch();

if (!$biz) {
    header("Location: /mis-negocios");
    exit();
}

$tableExists = mapitaTableExists($db, 'job_applications');
$apps        = [];
if ($tableExists) {
    $stA = $db->prepare(
        "SELECT id, user_id, applicant_name, applicant_email, applicant_phone,
                message, estado, consent, created_at
         FROM job_applications
         WHERE business_id = ?
         ORDER BY created_at DESC"
    );
    $stA->execute([$businessId]);
    $apps = $stA->fetchAll();
}

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Trabajo — <?php echo htmlspecialchars($biz['name']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        .page-header h1 { font-size: 1.05em; font-weight: 800; flex: 1; }
        .page-header a {
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
        .offer-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,.07);
            padding: 22px 28px;
            margin-bottom: 28px;
            border-left: 4px solid <?php echo !empty($biz['job_offer_active']) ? '#10b981' : '#d1d5db'; ?>;
        }
        .offer-card h2 { font-size: 1.05em; font-weight: 800; color: #1B3B6F; margin-bottom: 8px; }
        .offer-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: .75em;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .offer-badge.active   { background: #d1fae5; color: #065f46; }
        .offer-badge.inactive { background: #f3f4f6; color: #6b7280; }
        .apps-section h2 { font-size: 1em; font-weight: 800; color: #1B3B6F; margin-bottom: 18px; }
        .filter-row { display: flex; gap: 10px; margin-bottom: 18px; flex-wrap: wrap; align-items: center; }
        .filter-row select {
            padding: 7px 12px;
            border: 1.5px solid #d1d5db;
            border-radius: 8px;
            font-size: .875em;
            background: #fff;
        }
        .apps-table-wrap { overflow-x: auto; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,.06); }
        .apps-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            font-size: .875em;
        }
        .apps-table th {
            background: #1B3B6F;
            color: white;
            padding: 11px 14px;
            text-align: left;
            font-weight: 700;
            white-space: nowrap;
        }
        .apps-table td {
            padding: 11px 14px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }
        .apps-table tr:last-child td { border-bottom: none; }
        .apps-table tr:hover td { background: #f8faff; }
        .estado-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: .78em;
            font-weight: 700;
            white-space: nowrap;
        }
        .estado-pendiente { background: #fef3c7; color: #92400e; }
        .estado-vista      { background: #dbeafe; color: #1e40af; }
        .estado-aceptada   { background: #d1fae5; color: #065f46; }
        .estado-rechazada  { background: #fee2e2; color: #991b1b; }
        .btn-estado {
            padding: 5px 11px;
            border: none;
            border-radius: 7px;
            cursor: pointer;
            font-size: .8em;
            font-weight: 600;
            transition: filter .2s;
            margin: 2px;
        }
        .btn-estado:hover { filter: brightness(1.1); }
        .btn-vista     { background: #dbeafe; color: #1e40af; }
        .btn-aceptar   { background: #d1fae5; color: #065f46; }
        .btn-rechazar  { background: #fee2e2; color: #991b1b; }
        .reveal-btn {
            background: none;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            cursor: pointer;
            font-size: .78em;
            padding: 2px 7px;
            color: #6b7280;
        }
        .reveal-btn:hover { background: #f3f4f6; }
        .empty-msg { text-align: center; padding: 48px 20px; color: #9ca3af; font-size: .9em; }
        .stats-row { display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 24px; }
        .stat-box { background: #fff; border-radius: 10px; padding: 14px 20px; flex: 1; min-width: 110px;
                    box-shadow: 0 2px 8px rgba(0,0,0,.06); text-align: center; }
        .stat-box .num { font-size: 1.8em; font-weight: 900; color: #1B3B6F; }
        .stat-box .lbl { font-size: .78em; color: #6b7280; margin-top: 2px; }
        .msg-panel { padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; font-size: .875em; font-weight: 600; display: none; }
        .msg-panel.ok  { background: #d1fae5; color: #065f46; }
        .msg-panel.err { background: #fee2e2; color: #991b1b; }
        @media (max-width: 600px) {
            .page-header { padding: 0 16px; }
            .main { padding: 20px 12px 40px; }
            .offer-card { padding: 16px; }
        }
    </style>
</head>
<body>
<div class="page-header">
    <span style="font-size:1.4em;">💼</span>
    <h1>Panel de Trabajo — <?php echo htmlspecialchars($biz['name']); ?></h1>
    <a href="/edit?id=<?php echo $businessId; ?>">✏️ Editar negocio</a>
    <a href="/mis-negocios" style="margin-left:8px;">← Mis Negocios</a>
</div>
<div class="main">

    <!-- Oferta activa -->
    <div class="offer-card">
        <h2>💼 Oferta laboral</h2>
        <span class="offer-badge <?php echo !empty($biz['job_offer_active']) ? 'active' : 'inactive'; ?>">
            <?php echo !empty($biz['job_offer_active']) ? '● Activa' : '○ Inactiva'; ?>
        </span>
        <?php if (!empty($biz['job_offer_position'])): ?>
        <div style="font-weight:700;font-size:.95em;margin-bottom:6px;">
            🔍 <?php echo htmlspecialchars($biz['job_offer_position']); ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($biz['job_offer_description'])): ?>
        <div style="color:#555;font-size:.875em;line-height:1.6;margin-bottom:8px;">
            <?php echo nl2br(htmlspecialchars($biz['job_offer_description'])); ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($biz['job_offer_url'])): ?>
        <div style="margin-top:8px;">
            <a href="<?php echo htmlspecialchars($biz['job_offer_url']); ?>" target="_blank" rel="noopener"
               style="color:#1B3B6F;font-size:.84em;">🔗 Link externo de postulación</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Stats -->
    <?php
    $counts = ['total' => 0, 'pendiente' => 0, 'vista' => 0, 'aceptada' => 0, 'rechazada' => 0];
    foreach ($apps as $a) {
        $counts['total']++;
        $counts[$a['estado']] = ($counts[$a['estado']] ?? 0) + 1;
    }
    ?>
    <div class="stats-row">
        <div class="stat-box"><div class="num"><?php echo $counts['total']; ?></div><div class="lbl">Total</div></div>
        <div class="stat-box"><div class="num" style="color:#92400e;"><?php echo $counts['pendiente']; ?></div><div class="lbl">Pendiente</div></div>
        <div class="stat-box"><div class="num" style="color:#1e40af;"><?php echo $counts['vista']; ?></div><div class="lbl">Vista</div></div>
        <div class="stat-box"><div class="num" style="color:#065f46;"><?php echo $counts['aceptada']; ?></div><div class="lbl">Aceptada</div></div>
        <div class="stat-box"><div class="num" style="color:#991b1b;"><?php echo $counts['rechazada']; ?></div><div class="lbl">Rechazada</div></div>
    </div>

    <!-- Postulaciones -->
    <div class="apps-section">
        <h2>📋 Postulaciones</h2>

        <div id="panel-msg" class="msg-panel"></div>

        <div class="filter-row">
            <label style="font-size:.875em;color:#6b7280;">Filtrar por estado:</label>
            <select id="filter-estado" onchange="filtrarApps()">
                <option value="">Todos</option>
                <option value="pendiente">Pendiente</option>
                <option value="vista">Vista</option>
                <option value="aceptada">Aceptada</option>
                <option value="rechazada">Rechazada</option>
            </select>
        </div>

        <?php if (empty($apps)): ?>
        <div class="empty-msg">🤷 Todavía no hay postulaciones para este negocio.</div>
        <?php else: ?>
        <div class="apps-table-wrap">
            <table class="apps-table" id="apps-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Mensaje</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($apps as $app): ?>
                    <?php
                    $emailMasked = ja_mask_email($app['applicant_email']);
                    $phoneMasked = $app['applicant_phone'] ? ja_mask_phone($app['applicant_phone']) : '—';
                    $estadoCls   = 'estado-' . ($app['estado'] ?? 'pendiente');
                    ?>
                    <tr data-estado="<?php echo htmlspecialchars($app['estado']); ?>"
                        data-app-id="<?php echo (int)$app['id']; ?>">
                        <td><?php echo (int)$app['id']; ?></td>
                        <td><?php echo htmlspecialchars($app['applicant_name']); ?></td>
                        <td>
                            <span class="masked-email" data-real="<?php echo htmlspecialchars($app['applicant_email']); ?>">
                                <?php echo htmlspecialchars($emailMasked); ?>
                            </span>
                            <button class="reveal-btn" onclick="revelarEmail(this)" title="Ver email completo">👁</button>
                        </td>
                        <td>
                            <?php if ($app['applicant_phone']): ?>
                            <span class="masked-phone" data-real="<?php echo htmlspecialchars($app['applicant_phone']); ?>">
                                <?php echo htmlspecialchars($phoneMasked); ?>
                            </span>
                            <button class="reveal-btn" onclick="revelarPhone(this)" title="Ver teléfono completo">👁</button>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo htmlspecialchars($app['message'] ?? ''); ?>">
                            <?php echo htmlspecialchars(mb_substr($app['message'] ?? '', 0, 80)) ?: '<em style="color:#9ca3af">—</em>'; ?>
                        </td>
                        <td>
                            <span class="estado-badge <?php echo $estadoCls; ?>" id="estado-badge-<?php echo (int)$app['id']; ?>">
                                <?php echo ucfirst($app['estado']); ?>
                            </span>
                        </td>
                        <td style="white-space:nowrap;font-size:.8em;color:#6b7280;">
                            <?php echo date('d/m/Y H:i', strtotime($app['created_at'])); ?>
                        </td>
                        <td style="white-space:nowrap;">
                            <button class="btn-estado btn-vista"    onclick="cambiarEstado(<?php echo (int)$app['id']; ?>,'vista')">👁 Vista</button>
                            <button class="btn-estado btn-aceptar"  onclick="cambiarEstado(<?php echo (int)$app['id']; ?>,'aceptada')">✅ Aceptar</button>
                            <button class="btn-estado btn-rechazar" onclick="cambiarEstado(<?php echo (int)$app['id']; ?>,'rechazada')">✖ Rechazar</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div>

<script>
const BIZ_ID = <?php echo $businessId; ?>;
const CSRF_TOKEN = <?php echo json_encode($csrfToken); ?>;

function panelMsg(text, type) {
    const el = document.getElementById('panel-msg');
    if (!el) return;
    if (!text) { el.style.display = 'none'; return; }
    el.className = 'msg-panel ' + (type === 'ok' ? 'ok' : 'err');
    el.textContent = text;
    el.style.display = 'block';
    setTimeout(() => { el.style.display = 'none'; }, 4000);
}

function cambiarEstado(appId, nuevoEstado) {
    fetch('/api/job_applications.php?action=update_status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ application_id: appId, estado: nuevoEstado, csrf_token: CSRF_TOKEN })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            const badge = document.getElementById('estado-badge-' + appId);
            if (badge) {
                badge.className = 'estado-badge estado-' + nuevoEstado;
                badge.textContent = nuevoEstado.charAt(0).toUpperCase() + nuevoEstado.slice(1);
            }
            const row = document.querySelector('tr[data-app-id="' + appId + '"]');
            if (row) row.setAttribute('data-estado', nuevoEstado);
            panelMsg('Estado actualizado a: ' + nuevoEstado, 'ok');
            filtrarApps();
        } else {
            panelMsg('❌ ' + (d.message || 'Error al actualizar'), 'err');
        }
    })
    .catch(() => panelMsg('Error de conexión', 'err'));
}

function revelarEmail(btn) {
    const span = btn.previousElementSibling;
    if (span && span.dataset.real) {
        span.textContent = span.dataset.real;
        btn.remove();
    }
}

function revelarPhone(btn) {
    const span = btn.previousElementSibling;
    if (span && span.dataset.real) {
        span.textContent = span.dataset.real;
        btn.remove();
    }
}

function filtrarApps() {
    const estadoFiltro = document.getElementById('filter-estado').value;
    document.querySelectorAll('#apps-table tbody tr').forEach(row => {
        if (!estadoFiltro || row.dataset.estado === estadoFiltro) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>
</body>
</html>
<?php
// Helpers de enmascarado (igual que en la API)
function ja_mask_email(string $email): string {
    $parts = explode('@', $email, 2);
    if (count($parts) !== 2) return '***@***';
    $local  = $parts[0];
    $domain = $parts[1];
    $masked = mb_substr($local, 0, min(2, mb_strlen($local))) . str_repeat('*', max(0, mb_strlen($local) - 2));
    return $masked . '@' . $domain;
}

function ja_mask_phone(?string $phone): ?string {
    if ($phone === null || $phone === '') return null;
    $len = mb_strlen($phone);
    if ($len <= 4) return str_repeat('*', $len);
    return mb_substr($phone, 0, 2) . str_repeat('*', $len - 4) . mb_substr($phone, -2);
}
?>
