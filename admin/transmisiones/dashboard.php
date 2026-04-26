<?php
session_start();
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../includes/db_helper.php';
require_once __DIR__ . '/../../models/Transmision.php';

setSecurityHeaders();

if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header('Location: ../../auth/login.php');
    exit();
}

use App\Models\Transmision;

$db          = getDbConnection();
$message     = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $titulo = trim($_POST['titulo'] ?? '');
        if (!$titulo) {
            $message = 'El título es requerido.'; $messageType = 'error';
        } else {
            // Construir datetimes a partir de los campos del formulario
            $fecha_inicio_raw = trim($_POST['fecha_inicio'] ?? '');
            $hora_inicio_raw  = trim($_POST['hora_inicio']  ?? '00:00');
            $fecha_fin_raw    = trim($_POST['fecha_fin']    ?? '');
            $hora_fin_raw     = trim($_POST['hora_fin']     ?? '23:59');

            $datetime_inicio = $fecha_inicio_raw ? ($fecha_inicio_raw . ' ' . $hora_inicio_raw . ':00') : null;
            $datetime_fin    = $fecha_fin_raw    ? ($fecha_fin_raw    . ' ' . $hora_fin_raw    . ':00') : null;

            // Validar que inicio < fin si ambos están presentes
            $fecha_error = null;
            if ($datetime_inicio && $datetime_fin) {
                $ts_inicio = strtotime($datetime_inicio);
                $ts_fin    = strtotime($datetime_fin);
                if ($ts_inicio === false || $ts_fin === false) {
                    $fecha_error = 'Formato de fecha/hora inválido.';
                } elseif ($ts_inicio >= $ts_fin) {
                    $fecha_error = 'La fecha/hora de inicio debe ser anterior a la de fin.';
                }
            }

            if ($fecha_error) {
                $message = $fecha_error; $messageType = 'error';
            } elseif (Transmision::create([
                'titulo'       => $titulo,
                'descripcion'  => $_POST['descripcion'] ?? null,
                'tipo'         => $_POST['tipo']        ?? 'youtube_live',
                'stream_url'   => trim($_POST['stream_url'] ?? ''),
                'lat'          => $_POST['lat'] ?? '',
                'lng'          => $_POST['lng'] ?? '',
                'en_vivo'      => isset($_POST['en_vivo'])  ? 1 : 0,
                'activo'       => isset($_POST['activo'])   ? 1 : 0,
                'fecha_inicio' => $datetime_inicio,
                'fecha_fin'    => $datetime_fin,
            ])) {
                $message = 'Transmisión creada correctamente.'; $messageType = 'success';
            } else {
                $message = 'Error al crear la transmisión.'; $messageType = 'error';
            }
        }
    }

    if ($action === 'delete' && !empty($_POST['id'])) {
        Transmision::delete((int)$_POST['id']);
        $message = 'Transmisión eliminada.'; $messageType = 'success';
    }

    if ($action === 'toggle' && !empty($_POST['id'])) {
        $id  = (int)$_POST['id'];
        $row = Transmision::getById($id);
        if ($row) {
            $row['activo'] ? Transmision::deactivate($id) : Transmision::activate($id);
            $message = $row['activo'] ? 'Transmisión desactivada.' : 'Transmisión activada.';
            $messageType = 'success';
        }
    }

    if ($action === 'toggle_live' && !empty($_POST['id'])) {
        $id  = (int)$_POST['id'];
        $row = Transmision::getById($id);
        if ($row) {
            Transmision::setLive($id, !$row['en_vivo']);
            $message = $row['en_vivo'] ? 'Transmisión marcada como finalizada.' : '¡Transmisión marcada como EN VIVO!';
            $messageType = 'success';
        }
    }
}

$transmisiones = Transmision::getAll();
$stats         = Transmision::getStats();

$tiposLabel = [
    'youtube_live'  => '▶ YouTube Live',
    'youtube_video' => '📼 Video (YouTube)',
    'radio_stream'  => '📻 Radio Online',
    'audio_stream'  => '🎵 Audio Stream',
    'video_stream'  => '🎬 Video Stream',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Transmisiones - Mapita</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; margin: 0; background: #f5f6fa; }

        header { background: linear-gradient(135deg, #c0392b 0%, #922b21 100%); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 12px rgba(0,0,0,.1); }
        header h1 { margin: 0; font-size: 1.5em; }
        header a { color: rgba(255,255,255,.8); text-decoration: none; font-size: .9em; }
        header a:hover { color: white; }

        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }

        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 22px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.08); border-left: 4px solid #c0392b; }
        .stat-card.live { border-left-color: #e74c3c; }
        .stat-card .number { font-size: 2.3em; font-weight: bold; color: #c0392b; }
        .stat-card.live .number { color: #e74c3c; }
        .stat-card .label { color: #666; font-size: .88em; margin-top: 8px; }

        .section { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.08); margin-bottom: 30px; overflow: hidden; }
        .section-header { background: linear-gradient(135deg, #c0392b 0%, #922b21 100%); color: white; padding: 20px; font-size: 1.2em; font-weight: 600; }

        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; color: #333; font-size: .9em; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 6px; font-family: inherit; font-size: .95em; }
        .form-group textarea { resize: vertical; min-height: 90px; }

        .form-grid { padding: 25px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-full  { grid-column: 1/-1; }
        .geo-row    { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        #mini-map   { height: 200px; border-radius: 8px; border: 2px solid #e0e0e0; margin-top: 8px; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 14px; text-align: left; border-bottom: 1px solid #f0f0f0; font-size: .88em; }
        th { background: #f8f9fa; font-weight: 600; color: #333; }
        tr:hover { background: #fafbfc; }

        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: .8em; font-weight: 600; }
        .badge-active   { background: #d4edda; color: #155724; }
        .badge-inactive { background: #f0f0f0; color: #666; }
        .badge-live     { background: #fde8e8; color: #c0392b; animation: pulse 1.4s infinite; }

        @keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: .6; } }

        .btn { padding: 8px 12px; border: none; border-radius: 6px; cursor: pointer; font-size: .85em; font-weight: 600; transition: all .3s; }
        .btn-primary  { background: linear-gradient(135deg, #c0392b 0%, #922b21 100%); color: white; }
        .btn-primary:hover  { transform: translateY(-2px); }
        .btn-live     { background: #e74c3c; color: white; }
        .btn-live:hover { background: #c0392b; }
        .btn-danger   { background: #e74c3c; color: white; }
        .btn-danger:hover { background: #c0392b; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        .inline-form { display: inline; }

        .message { padding: 14px 20px; margin-bottom: 20px; border-radius: 6px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error   { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .tipo-badge { font-size: .8em; padding: 2px 8px; border-radius: 12px; background: #f0f0f0; color: #555; }

        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
            th, td { padding: 8px; font-size: .8em; }
        }
    </style>
</head>
<body>

<header>
    <h1>📡 Panel de Transmisiones en Vivo</h1>
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
            <div class="label">Total</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $stats['activas']; ?></div>
            <div class="label">Activas</div>
        </div>
        <div class="stat-card live">
            <div class="number"><?php echo $stats['en_vivo']; ?></div>
            <div class="label">🔴 En Vivo Ahora</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $stats['inactivas']; ?></div>
            <div class="label">Inactivas</div>
        </div>
    </div>

    <!-- Crear Transmisión -->
    <div class="section">
        <div class="section-header">➕ Crear Nueva Transmisión</div>
        <form method="post" class="form-grid" id="form-create-trans">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="create">

            <div>
                <div class="form-group">
                    <label>Título *</label>
                    <input type="text" name="titulo" required placeholder="Ej: Radio Municipal en Vivo">
                </div>
                <div class="form-group">
                    <label>Tipo de transmisión</label>
                    <select name="tipo">
                        <option value="youtube_live">▶ YouTube Live</option>
                        <option value="youtube_video">📼 Video (YouTube)</option>
                        <option value="radio_stream">📻 Radio Online (Icecast/Shoutcast)</option>
                        <option value="audio_stream">🎵 Audio Stream</option>
                        <option value="video_stream">🎬 Video Stream (HLS/RTMP)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>URL del stream *</label>
                    <input type="url" name="stream_url" placeholder="https://youtu.be/VIDEO_ID o https://stream.radio.com/live">
                </div>
            </div>

            <div>
                <div class="form-group">
                    <label>Descripción</label>
                    <textarea name="descripcion" placeholder="Descripción de la transmisión..."></textarea>
                </div>
                <div class="form-group" style="display:flex;gap:20px;align-items:center;">
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:600;">
                        <input type="checkbox" name="activo" checked style="width:auto;"> Activa
                    </label>
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:600;color:#e74c3c;">
                        <input type="checkbox" name="en_vivo" style="width:auto;"> 🔴 En vivo ahora
                    </label>
                </div>
            </div>

            <!-- Ventana de tiempo -->
            <div class="form-full">
                <div style="background:#fff8f0;border:1px solid #fad7a0;border-radius:8px;padding:18px;margin-bottom:4px;">
                    <div style="font-weight:700;color:#784212;margin-bottom:12px;">🕐 Ventana de tiempo del vivo <small style="font-weight:400;color:#7f8c8d;">(opcional — si se definen, el vivo sólo estará activo dentro de ese rango)</small></div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <div class="form-group" style="margin:0;">
                            <label>Fecha de inicio</label>
                            <input type="date" name="fecha_inicio" id="fecha_inicio">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label>Hora de inicio</label>
                            <input type="time" name="hora_inicio" id="hora_inicio" value="08:00">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label>Fecha de fin</label>
                            <input type="date" name="fecha_fin" id="fecha_fin">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label>Hora de fin</label>
                            <input type="time" name="hora_fin" id="hora_fin" value="23:59">
                        </div>
                    </div>
                    <p style="font-size:11px;color:#888;margin-top:8px;">Si se completan, la fecha de inicio debe ser anterior a la de fin.</p>
                </div>
            </div>

            <div class="form-full">
                <div class="form-group">
                    <label>📍 Ubicación (click en mapa)</label>
                    <div class="geo-row">
                        <input type="number" name="lat" id="input-lat" step="any" placeholder="Latitud" readonly style="background:#f8f9fa;">
                        <input type="number" name="lng" id="input-lng" step="any" placeholder="Longitud" readonly style="background:#f8f9fa;">
                    </div>
                    <div id="mini-map"></div>
                    <p style="font-size:11px;color:#888;margin-top:6px;">Opcional. Permite mostrar el origen de la transmisión en el mapa.</p>
                </div>
            </div>

            <div class="form-full">
                <button type="submit" class="btn btn-primary" style="width:100%;padding:14px;font-size:1em;">📡 Crear Transmisión</button>
            </div>
        </form>
    </div>

    <!-- Listado -->
    <div class="section">
        <div class="section-header">📋 Transmisiones (<?php echo count($transmisiones); ?>)</div>
        <?php if (empty($transmisiones)): ?>
            <div style="padding:30px;text-align:center;color:#999;">No hay transmisiones aún</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Título</th>
                        <th>Tipo</th>
                        <th>URL</th>
                        <th>Ventana</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transmisiones as $t): ?>
                    <?php
                        $now = time();
                        $fi  = !empty($t['fecha_inicio']) ? strtotime($t['fecha_inicio']) : null;
                        $ff  = !empty($t['fecha_fin'])    ? strtotime($t['fecha_fin'])    : null;
                        $en_ventana = (!$fi || $fi <= $now) && (!$ff || $ff >= $now);
                    ?>
                    <tr>
                        <td><?php echo $t['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($t['titulo']); ?></strong>
                            <?php if ($t['descripcion']): ?>
                                <br><small style="color:#888;"><?php echo htmlspecialchars(substr($t['descripcion'], 0, 55)); ?><?php echo strlen($t['descripcion']) > 55 ? '…' : ''; ?></small>
                            <?php endif; ?>
                        </td>
                        <td><span class="tipo-badge"><?php echo htmlspecialchars($tiposLabel[$t['tipo']] ?? $t['tipo']); ?></span></td>
                        <td>
                            <?php if ($t['stream_url']): ?>
                                <a href="<?php echo htmlspecialchars($t['stream_url']); ?>" target="_blank" rel="noopener noreferrer" style="color:#c0392b;font-size:.82em;word-break:break-all;">
                                    🔗 <?php echo htmlspecialchars(substr($t['stream_url'], 0, 35)); ?>…
                                </a>
                            <?php else: ?>
                                <span style="color:#ccc;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:.8em;">
                            <?php if ($fi || $ff): ?>
                                <?php if ($fi): ?><div>▶ <?php echo date('d/m/Y H:i', $fi); ?></div><?php endif; ?>
                                <?php if ($ff): ?><div>⏹ <?php echo date('d/m/Y H:i', $ff); ?></div><?php endif; ?>
                                <?php if (!$en_ventana): ?><small style="color:#e74c3c;">Fuera de ventana</small><?php endif; ?>
                            <?php else: ?>
                                <span style="color:#ccc;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($t['en_vivo'] && $en_ventana): ?>
                                <span class="badge badge-live">🔴 En Vivo</span>
                            <?php elseif ($t['en_vivo']): ?>
                                <span class="badge badge-live" style="opacity:.5;">🔴 (fuera de ventana)</span>
                            <?php elseif ($t['activo']): ?>
                                <span class="badge badge-active">Activa</span>
                            <?php else: ?>
                                <span class="badge badge-inactive">Inactiva</span>
                            <?php endif; ?>
                        </td>
                        <td style="white-space:nowrap;display:flex;gap:4px;flex-wrap:wrap;">
                            <!-- Ver en vivo (nueva pestaña) -->
                            <?php if ($t['stream_url'] && $t['en_vivo']): ?>
                                <a href="<?php echo htmlspecialchars($t['stream_url']); ?>"
                                   target="_blank" rel="noopener noreferrer"
                                   class="btn btn-live" style="font-size:.78em;padding:5px 8px;text-decoration:none;">
                                    📺 Ver
                                </a>
                            <?php endif; ?>
                            <!-- Toggle En Vivo -->
                            <form method="post" class="inline-form">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="toggle_live">
                                <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                <button type="submit" class="btn btn-live" style="font-size:.78em;padding:5px 8px;">
                                    <?php echo $t['en_vivo'] ? '⏹ Fin' : '🔴 Live'; ?>
                                </button>
                            </form>
                            <!-- Toggle activo -->
                            <form method="post" class="inline-form">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                <button type="submit" class="btn btn-secondary" style="font-size:.78em;padding:5px 8px;">
                                    <?php echo $t['activo'] ? '⏸' : '▶'; ?>
                                </button>
                            </form>
                            <!-- Eliminar -->
                            <form method="post" class="inline-form" onsubmit="return confirm('¿Eliminar esta transmisión?')">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                <button type="submit" class="btn btn-danger" style="font-size:.78em;padding:5px 8px;">🗑</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
const mapInst = L.map('mini-map').setView([-34.6037, -58.3816], 12);
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
              .bindTooltip('📡 Origen de la transmisión').openTooltip();
});

// Validar ventana de tiempo al crear transmisión
document.getElementById('form-create-trans').addEventListener('submit', function(e) {
    var fi = document.getElementById('fecha_inicio').value;
    var hi = document.getElementById('hora_inicio').value;
    var ff = document.getElementById('fecha_fin').value;
    var hf = document.getElementById('hora_fin').value;

    if (fi && ff) {
        var inicio = new Date(fi + 'T' + (hi || '00:00'));
        var fin    = new Date(ff + 'T' + (hf || '23:59'));
        if (inicio >= fin) {
            e.preventDefault();
            alert('La fecha/hora de inicio debe ser estrictamente anterior a la de fin.');
            return;
        }
    }
    // Si solo se completa uno de los dos, advertir (no bloquear)
    if ((fi && !ff) || (!fi && ff)) {
        // Ambos son opcionales; no bloquear pero los datos se envían igualmente
    }
});
</script>
</body>
</html>
