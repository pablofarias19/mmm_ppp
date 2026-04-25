<?php
session_start();
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../includes/db_helper.php';
require_once __DIR__ . '/../../models/Oferta.php';

setSecurityHeaders();

if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header('Location: ../../auth/login.php');
    exit();
}

use App\Models\Oferta;

$db          = getDbConnection();
$message     = '';
$messageType = '';

// ── Helpers de permisos (con fallback si columnas aún no existen) ────────────
function column_exists_local(PDO $db, string $table, string $col): bool {
    try {
        $s = $db->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1");
        $s->execute([$table, $col]);
        return (bool)$s->fetchColumn();
    } catch (\Throwable $e) { return false; }
}
$hasPermisos = column_exists_local($db, 'businesses', 'ofertas_permitidas');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $nombre = trim($_POST['nombre'] ?? '');
        if ($nombre) {
            $bizId = $_POST['business_id'] !== '' ? (int)$_POST['business_id'] : null;
            if (Oferta::create([
                'nombre'           => $nombre,
                'descripcion'      => $_POST['descripcion']      ?? null,
                'precio_normal'    => $_POST['precio_normal']    ?? null,
                'precio_oferta'    => $_POST['precio_oferta']    ?? null,
                'fecha_inicio'     => $_POST['fecha_inicio']     ?: date('Y-m-d'),
                'fecha_expiracion' => $_POST['fecha_expiracion'] ?: null,
                'lat'              => $_POST['lat']              ?? '',
                'lng'              => $_POST['lng']              ?? '',
                'activo'           => isset($_POST['activo']) ? 1 : 0,
                'business_id'      => $bizId,
            ])) {
                $message = 'Oferta creada correctamente.'; $messageType = 'success';
            } else {
                $message = 'Error al crear la oferta.'; $messageType = 'error';
            }
        } else {
            $message = 'El nombre es requerido.'; $messageType = 'error';
        }
    }

    if ($action === 'delete' && !empty($_POST['id'])) {
        Oferta::delete((int)$_POST['id']);
        $message = 'Oferta eliminada.'; $messageType = 'success';
    }

    if ($action === 'toggle' && !empty($_POST['id'])) {
        $id  = (int)$_POST['id'];
        $row = Oferta::getById($id);
        if ($row) {
            $row['activo'] ? Oferta::deactivate($id) : Oferta::activate($id);
            $message = $row['activo'] ? 'Oferta desactivada.' : 'Oferta activada.';
            $messageType = 'success';
        }
    }

    // ── Gestión de permisos por negocio ───────────────────────────────────
    if ($action === 'set_permiso' && $hasPermisos && !empty($_POST['business_id'])) {
        $bizId   = (int)$_POST['business_id'];
        $permit  = isset($_POST['ofertas_permitidas']) ? 1 : 0;
        $maxOff  = max(0, (int)($_POST['ofertas_max'] ?? 0));
        try {
            $db->prepare("UPDATE businesses SET ofertas_permitidas = ?, ofertas_max = ? WHERE id = ?")
               ->execute([$permit, $maxOff, $bizId]);
            $message = 'Permisos actualizados.'; $messageType = 'success';
        } catch (\Exception $e) {
            $message = 'Error al actualizar permisos.'; $messageType = 'error';
        }
    }
}

$ofertas = Oferta::getAll();
$stats   = Oferta::getStats();

// Negocios con permisos (para la sección de administración)
$negocios_con_permiso = [];
$todos_negocios = [];
if ($hasPermisos) {
    try {
        $negocios_con_permiso = $db->query(
            "SELECT id, name, ofertas_permitidas, ofertas_max FROM businesses WHERE ofertas_permitidas = 1 ORDER BY name"
        )->fetchAll(\PDO::FETCH_ASSOC);
        $todos_negocios = $db->query(
            "SELECT id, name FROM businesses ORDER BY name LIMIT 200"
        )->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\Exception $e) {}
}

// Cupo actual por negocio (para mostrar X/Y)
$cupo_usado = [];
if (!empty($negocios_con_permiso)) {
    $ids = array_column($negocios_con_permiso, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    try {
        $stCupo = $db->prepare("SELECT business_id, COUNT(*) AS total FROM ofertas WHERE activo = 1 AND business_id IN ($placeholders) GROUP BY business_id");
        $stCupo->execute($ids);
        foreach ($stCupo->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $cupo_usado[$row['business_id']] = (int)$row['total'];
        }
    } catch (\Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Ofertas - Mapita</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; margin: 0; background: #f5f6fa; }

        header { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 12px rgba(0,0,0,.1); }
        header h1 { margin: 0; font-size: 1.5em; }
        header a { color: rgba(255,255,255,.8); text-decoration: none; font-size: .9em; transition: color .3s; }
        header a:hover { color: white; }

        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }

        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.08); border-left: 4px solid #e74c3c; }
        .stat-card .number { font-size: 2.5em; font-weight: bold; color: #e74c3c; }
        .stat-card .label  { color: #666; font-size: .9em; margin-top: 8px; }

        .section { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.08); margin-bottom: 30px; overflow: hidden; }
        .section-header { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white; padding: 20px; font-size: 1.2em; font-weight: 600; }

        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; color: #333; font-size: .9em; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 6px; font-family: inherit; font-size: .95em; }
        .form-group textarea { resize: vertical; min-height: 100px; }

        .form-grid { padding: 25px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-full  { grid-column: 1/-1; }

        .geo-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        #mini-map { height: 220px; border-radius: 8px; border: 2px solid #e0e0e0; margin-top: 8px; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 14px; text-align: left; border-bottom: 1px solid #f0f0f0; font-size: .9em; }
        th { background: #f8f9fa; font-weight: 600; color: #333; }
        tr:hover { background: #fafbfc; }

        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: .82em; font-weight: 600; }
        .badge-active   { background: #d4edda; color: #155724; }
        .badge-inactive { background: #fff3cd; color: #856404; }
        .badge-expired  { background: #f8d7da; color: #721c24; }
        .badge-biz      { background: #cfe2ff; color: #0a58ca; }
        .quota-badge    { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:20px; font-size:.82em; font-weight:600; background:#e2e3e5; color:#41464b; }
        .quota-badge.quota-ok   { background:#d1ecf1; color:#0c5460; }
        .quota-badge.quota-warn { background:#fff3cd; color:#856404; }
        .quota-badge.quota-full { background:#f8d7da; color:#721c24; }

        .btn { padding: 9px 14px; border: none; border-radius: 6px; cursor: pointer; font-size: .88em; font-weight: 600; transition: all .3s; }
        .btn-primary  { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(231,76,60,.3); }
        .btn-danger   { background: #e74c3c; color: white; }
        .btn-danger:hover { background: #c0392b; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        .btn-success  { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .inline-form { display: inline; }

        .message { padding: 14px 20px; margin-bottom: 20px; border-radius: 6px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error   { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .price-tag { font-weight: 700; color: #e74c3c; }
        .price-old { text-decoration: line-through; color: #999; font-size: .85em; margin-right: 6px; }

        .perm-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:16px; padding:20px; }
        .perm-card { border:1px solid #e0e0e0; border-radius:8px; padding:16px; background:#fafafa; }
        .perm-card h4 { margin:0 0 10px; font-size:.95em; color:#333; }
        .perm-card .quota-info { font-size:.82em; color:#666; margin-bottom:10px; }
        .perm-form-inline { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
        .perm-form-inline input[type=number] { width:80px; padding:6px; border:1px solid #ddd; border-radius:4px; }

        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
            .geo-row   { grid-template-columns: 1fr; }
            th, td { padding: 10px; font-size: .85em; }
        }
    </style>
</head>
<body>

<header>
    <h1>🏷️ Panel de Ofertas</h1>
    <div>
        <span style="margin-right:20px;">Usuario: <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        <a href="/">🗺️ Mapa</a> |
        <a href="/admin/dashboard.php">🛡️ Admin</a> |
        <a href="/admin/index.php">📋 Panel</a> |
        <a href="/logout">🚪 Salir</a>
    </div>
</header>

<div class="container">
    <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- Estadísticas -->
    <div class="stats">
        <div class="stat-card">
            <div class="number"><?php echo $stats['total']; ?></div>
            <div class="label">Total Ofertas</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $stats['activas']; ?></div>
            <div class="label">Activas/Vigentes</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $stats['vencidas']; ?></div>
            <div class="label">Vencidas/Inactivas</div>
        </div>
        <?php if ($hasPermisos): ?>
        <div class="stat-card">
            <div class="number"><?php echo count($negocios_con_permiso); ?></div>
            <div class="label">Negocios con permiso</div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Crear Oferta -->
    <div class="section">
        <div class="section-header">➕ Crear Nueva Oferta</div>
        <form method="post" class="form-grid">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="create">

            <div>
                <div class="form-group">
                    <label>Nombre de la oferta *</label>
                    <input type="text" name="nombre" required placeholder="Ej: 50% en pizzas los martes">
                </div>
                <div class="form-group">
                    <label>Negocio (ID)</label>
                    <?php if (!empty($todos_negocios)): ?>
                    <select name="business_id">
                        <option value="">— Sin negocio asociado —</option>
                        <?php foreach ($todos_negocios as $biz): ?>
                            <option value="<?= $biz['id'] ?>">#<?= $biz['id'] ?> – <?= htmlspecialchars($biz['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php else: ?>
                    <input type="number" name="business_id" min="1" placeholder="ID del negocio (opcional)">
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Precio normal</label>
                    <input type="number" name="precio_normal" step="0.01" min="0" placeholder="1500.00">
                </div>
                <div class="form-group">
                    <label>Precio de oferta</label>
                    <input type="number" name="precio_oferta" step="0.01" min="0" placeholder="750.00">
                </div>
            </div>

            <div>
                <div class="form-group">
                    <label>Fecha inicio</label>
                    <input type="date" name="fecha_inicio" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label>Fecha expiración</label>
                    <input type="date" name="fecha_expiracion">
                </div>
                <div class="form-group" style="display:flex;align-items:center;gap:8px;">
                    <input type="checkbox" name="activo" id="activo" checked style="width:auto;margin:0;">
                    <label for="activo" style="margin:0;font-weight:600;cursor:pointer;">Publicar inmediatamente</label>
                </div>
            </div>

            <div class="form-full">
                <div class="form-group">
                    <label>Descripción</label>
                    <textarea name="descripcion" placeholder="Describe la oferta, condiciones, productos incluidos..."></textarea>
                </div>
            </div>

            <div class="form-full">
                <div class="form-group">
                    <label>📍 Ubicación geográfica (click en el mapa para seleccionar)</label>
                    <div class="geo-row">
                        <input type="number" name="lat" id="input-lat" step="any" placeholder="Latitud" readonly style="background:#f8f9fa;">
                        <input type="number" name="lng" id="input-lng" step="any" placeholder="Longitud" readonly style="background:#f8f9fa;">
                    </div>
                    <div id="mini-map"></div>
                    <p style="font-size:12px;color:#888;margin-top:6px;">💡 Haz click en el mapa para fijar la ubicación de la oferta</p>
                </div>
            </div>

            <div class="form-full">
                <button type="submit" class="btn btn-primary" style="width:100%;padding:14px;font-size:1em;">🏷️ Crear Oferta</button>
            </div>
        </form>
    </div>

    <!-- Listado -->
    <div class="section">
        <div class="section-header">📋 Ofertas (<?php echo count($ofertas); ?>)</div>
        <?php if (empty($ofertas)): ?>
            <div style="padding:30px;text-align:center;color:#999;">No hay ofertas aún</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Negocio</th>
                        <th>Precio</th>
                        <th>Vence</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ofertas as $o):
                        $expired = $o['fecha_expiracion'] && $o['fecha_expiracion'] < date('Y-m-d');
                        $badgeClass = !$o['activo'] ? 'badge-inactive' : ($expired ? 'badge-expired' : 'badge-active');
                        $badgeLabel = !$o['activo'] ? 'Inactiva' : ($expired ? 'Vencida' : 'Activa');
                    ?>
                    <tr>
                        <td><?php echo $o['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($o['nombre']); ?></strong>
                            <?php if ($o['descripcion']): ?>
                                <br><small style="color:#888;"><?php echo htmlspecialchars(substr($o['descripcion'], 0, 60)); ?><?php echo strlen($o['descripcion']) > 60 ? '…' : ''; ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($o['business_id']): ?>
                                <span class="badge badge-biz">#<?php echo $o['business_id']; ?></span>
                            <?php else: ?>
                                <span style="color:#ccc;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($o['precio_normal']): ?>
                                <span class="price-old">$<?php echo number_format($o['precio_normal'], 0); ?></span>
                            <?php endif; ?>
                            <?php if ($o['precio_oferta']): ?>
                                <span class="price-tag">$<?php echo number_format($o['precio_oferta'], 0); ?></span>
                            <?php else: ?>
                                <span style="color:#999;">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $o['fecha_expiracion'] ? date('d/m/Y', strtotime($o['fecha_expiracion'])) : '<span style="color:#999;">Sin venc.</span>'; ?></td>
                        <td>
                            <span class="badge <?php echo $badgeClass; ?>"><?php echo $badgeLabel; ?></span>
                            <?php if ($o['lat'] && $o['lng']): ?>
                                <span title="Con geolocalización" style="color:#27ae60;">📍</span>
                            <?php endif; ?>
                        </td>
                        <td style="white-space:nowrap;">
                            <form method="post" class="inline-form">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?php echo $o['id']; ?>">
                                <button type="submit" class="btn btn-secondary" style="padding:6px 10px;font-size:.8em;">
                                    <?php echo $o['activo'] ? '⏸ Pausar' : '▶ Activar'; ?>
                                </button>
                            </form>
                            <form method="post" class="inline-form" onsubmit="return confirm('¿Eliminar esta oferta permanentemente?')">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $o['id']; ?>">
                                <button type="submit" class="btn btn-danger" style="padding:6px 10px;font-size:.8em;">🗑 Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php if ($hasPermisos): ?>
    <!-- Permisos de negocios para ofertas -->
    <div class="section">
        <div class="section-header">🔑 Permisos de Publicación por Negocio</div>

        <?php if (!empty($negocios_con_permiso)): ?>
        <div style="padding:16px 20px 4px;font-weight:600;color:#333;">Negocios con permiso activo:</div>
        <div class="perm-grid">
            <?php foreach ($negocios_con_permiso as $biz):
                $used = $cupo_usado[$biz['id']] ?? 0;
                $max  = (int)$biz['ofertas_max'];
                if ($max > 0) {
                    $pct = min(100, round($used / $max * 100));
                    $quotaCls = $pct >= 100 ? 'quota-full' : ($pct >= 80 ? 'quota-warn' : 'quota-ok');
                    $quotaTxt = "$used / $max";
                } else {
                    $quotaCls = 'quota-ok';
                    $quotaTxt = "$used / ∞";
                }
            ?>
            <div class="perm-card">
                <h4>#<?= $biz['id'] ?> — <?= htmlspecialchars($biz['name']) ?></h4>
                <div class="quota-info">Ofertas activas: <span class="quota-badge <?= $quotaCls ?>"><?= $quotaTxt ?></span></div>
                <form method="post" class="perm-form-inline">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="set_permiso">
                    <input type="hidden" name="business_id" value="<?= $biz['id'] ?>">
                    <input type="hidden" name="ofertas_permitidas" value="1">
                    <label style="font-size:.85em;">Máx:</label>
                    <input type="number" name="ofertas_max" value="<?= $max ?>" min="0" title="0 = sin límite">
                    <button type="submit" class="btn btn-success" style="padding:6px 12px;font-size:.82em;">💾 Guardar</button>
                    <button type="submit" form="revoke-<?= $biz['id'] ?>" class="btn btn-danger" style="padding:6px 12px;font-size:.82em;">🚫 Revocar</button>
                </form>
                <form id="revoke-<?= $biz['id'] ?>" method="post" onsubmit="return confirm('¿Revocar permiso a este negocio?')">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="set_permiso">
                    <input type="hidden" name="business_id" value="<?= $biz['id'] ?>">
                    <input type="hidden" name="ofertas_max" value="0">
                    <!-- no incluir ofertas_permitidas → queda 0 -->
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <div style="padding:16px 20px;color:#888;">Ningún negocio tiene permiso activo aún.</div>
        <?php endif; ?>

        <!-- Habilitar nuevo negocio -->
        <div style="padding:16px 20px;border-top:1px solid #f0f0f0;">
            <strong style="display:block;margin-bottom:10px;">➕ Habilitar nuevo negocio:</strong>
            <form method="post" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="set_permiso">
                <input type="hidden" name="ofertas_permitidas" value="1">
                <div class="form-group" style="margin:0;min-width:220px;">
                    <label style="font-size:.85em;">Negocio</label>
                    <?php if (!empty($todos_negocios)): ?>
                    <select name="business_id" required>
                        <option value="">— Seleccionar negocio —</option>
                        <?php foreach ($todos_negocios as $biz): ?>
                            <option value="<?= $biz['id'] ?>">#<?= $biz['id'] ?> – <?= htmlspecialchars($biz['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php else: ?>
                    <input type="number" name="business_id" min="1" placeholder="ID del negocio" required style="width:160px;">
                    <?php endif; ?>
                </div>
                <div class="form-group" style="margin:0;">
                    <label style="font-size:.85em;">Máx. ofertas (0 = sin límite)</label>
                    <input type="number" name="ofertas_max" value="5" min="0" style="width:90px;padding:10px;border:1px solid #e0e0e0;border-radius:6px;">
                </div>
                <button type="submit" class="btn btn-success" style="white-space:nowrap;">✅ Habilitar</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
// Mini-mapa para selección de ubicación
const mapInst = L.map('mini-map').setView([-34.6037, -58.3816], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors', maxZoom: 19
}).addTo(mapInst);

let marker = null;

mapInst.on('click', function(e) {
    const { lat, lng } = e.latlng;
    document.getElementById('input-lat').value = lat.toFixed(8);
    document.getElementById('input-lng').value = lng.toFixed(8);
    if (marker) marker.remove();
    marker = L.marker([lat, lng]).addTo(mapInst)
              .bindTooltip('📍 Ubicación de la oferta').openTooltip();
});
</script>
</body>
</html>
