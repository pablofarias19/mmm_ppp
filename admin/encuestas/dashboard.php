<?php
/**
 * Admin Dashboard - Encuestas
 * Permite crear, editar, ver y eliminar encuestas
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Verificar que es admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: /login');
    exit;
}

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../models/Encuesta.php';

use App\Models\Encuesta;

$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);

$encuesta = null;
$encuestas = [];

// Cargar encuesta si estamos editando
if ($action === 'edit' && $id > 0) {
    $encuesta = Encuesta::getById($id);
}

// Cargar lista de encuestas
if ($action === 'list' || !in_array($action, ['edit', 'create', 'stats'])) {
    $encuestas = Encuesta::getAll();
}

// Procesar formulario POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'] ?? null;
    $descripcion = $_POST['descripcion'] ?? null;
    $lat = $_POST['lat'] ?? null;
    $lng = $_POST['lng'] ?? null;
    $fecha_expiracion = $_POST['fecha_expiracion'] ?? null;

    if (!$titulo) {
        $error = "El título es requerido";
    } else {
        if ($action === 'create') {
            $result = Encuesta::create([
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'lat' => $lat,
                'lng' => $lng,
                'fecha_expiracion' => $fecha_expiracion
            ]);

            if ($result) {
                $_SESSION['mensaje'] = "Encuesta creada exitosamente";
                header("Location: /admin/encuestas/dashboard.php?action=edit&id=$result");
                exit;
            } else {
                $error = "Error al crear la encuesta";
            }
        } elseif ($action === 'edit' && $id > 0) {
            $result = Encuesta::update($id, [
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'lat' => $lat,
                'lng' => $lng,
                'fecha_expiracion' => $fecha_expiracion
            ]);

            if ($result) {
                $_SESSION['mensaje'] = "Encuesta actualizada exitosamente";
            } else {
                $error = "Error al actualizar la encuesta";
            }
        }
    }

    // Redirigir a lista
    header("Location: /admin/encuestas/dashboard.php");
    exit;
}

$mensaje = $_SESSION['mensaje'] ?? null;
unset($_SESSION['mensaje']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Encuestas</title>
    <link rel="stylesheet" href="/css/map-styles.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f6fa;
            color: #2c3e50;
        }
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        header h1 { font-size: 24px; }
        header a {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            transition: 0.3s;
        }
        header a:hover {
            background: rgba(255,255,255,0.3);
        }
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #d0d5dd;
        }
        .tabs a, .tabs button {
            padding: 12px 20px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            color: #6c757d;
            font-size: 14px;
            font-weight: 500;
            transition: 0.3s;
        }
        .tabs a.active, .tabs button.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
        .tabs a:hover, .tabs button:hover {
            color: #667eea;
        }

        /* LISTA DE ENCUESTAS */
        .encuestas-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #e9ecef;
        }
        td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-activo {
            background: #d4edda;
            color: #155724;
        }
        .badge-inactivo {
            background: #f8d7da;
            color: #721c24;
        }
        .badge-vigente {
            background: #cfe2ff;
            color: #084298;
        }
        .badge-expirado {
            background: #f5f6fa;
            color: #6c757d;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        .btn-danger:hover {
            background: #c0392b;
        }
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        /* FORMULARIO */
        .form-container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            max-width: 800px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d0d5dd;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
            transition: 0.3s;
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        .coords-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        #map-picker {
            height: 300px;
            width: 100%;
            border-radius: 6px;
            border: 1px solid #d0d5dd;
            margin-bottom: 15px;
        }
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        .form-actions .btn {
            flex: 1;
            padding: 12px;
            text-align: center;
        }

        /* ESTADÍSTICAS */
        .stats-container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
        }
        .stat-box h3 {
            font-size: 32px;
            margin-bottom: 5px;
        }
        .stat-box p {
            font-size: 14px;
            opacity: 0.9;
        }
        .pregunta-stats {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        .pregunta-stats h4 {
            margin-bottom: 10px;
            color: #2c3e50;
        }
        .respuesta-stat {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .respuesta-stat:last-child {
            border-bottom: none;
        }
        .respuesta-stat-bar {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            height: 20px;
            border-radius: 3px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <header>
            <h1>📊 Gestión de Encuestas</h1>
            <a href="/">← Volver al Mapa</a>
        </header>

        <?php if ($mensaje): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- TABS -->
        <div class="tabs">
            <a href="?action=list" class="<?= $action === 'list' ? 'active' : '' ?>">📋 Listado</a>
            <a href="?action=create" class="<?= $action === 'create' ? 'active' : '' ?>">➕ Nueva Encuesta</a>
        </div>

        <!-- VISTA: LISTADO -->
        <?php if ($action === 'list'): ?>
            <div class="encuestas-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Estado</th>
                            <th>Vigencia</th>
                            <th>Participantes</th>
                            <th>Ubicación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($encuestas as $enc): ?>
                            <?php
                                $es_activo = $enc['activo'] == 1;
                                $fecha_exp = $enc['fecha_expiracion'];
                                $es_vigente = !$fecha_exp || strtotime($fecha_exp) >= time();
                            ?>
                            <tr>
                                <td>#<?= $enc['id'] ?></td>
                                <td><?= htmlspecialchars($enc['titulo']) ?></td>
                                <td>
                                    <span class="badge <?= $es_activo ? 'badge-activo' : 'badge-inactivo' ?>">
                                        <?= $es_activo ? '✓ Activo' : '✗ Inactivo' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $es_vigente ? 'badge-vigente' : 'badge-expirado' ?>">
                                        <?= $es_vigente ? '✓ Vigente' : '✗ Expirado' ?>
                                    </span>
                                </td>
                                <td>—</td>
                                <td>
                                    <?php if ($enc['lat'] && $enc['lng']): ?>
                                        <small><?= number_format($enc['lat'], 4) ?>, <?= number_format($enc['lng'], 4) ?></small>
                                    <?php else: ?>
                                        <small>Sin ubicación</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="?action=edit&id=<?= $enc['id'] ?>" class="btn btn-primary btn-small">Editar</a>
                                        <a href="?action=stats&id=<?= $enc['id'] ?>" class="btn btn-secondary btn-small">Estadísticas</a>
                                        <a href="?action=delete&id=<?= $enc['id'] ?>" class="btn btn-danger btn-small" onclick="return confirm('¿Desactivar encuesta?')">Desactivar</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- VISTA: CREAR/EDITAR -->
        <?php if ($action === 'create' || ($action === 'edit' && $encuesta)): ?>
            <div class="form-container">
                <h2><?= $action === 'create' ? '➕ Nueva Encuesta' : '✏️ Editar Encuesta' ?></h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="titulo">Título *</label>
                        <input type="text" id="titulo" name="titulo" required
                               value="<?= $encuesta ? htmlspecialchars($encuesta['titulo']) : '' ?>"
                               placeholder="Ej: Opinión sobre nuevos productos">
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion"
                                  placeholder="Describe la encuesta para los usuarios..."><?= $encuesta ? htmlspecialchars($encuesta['descripcion']) : '' ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>📍 Ubicación en el Mapa</label>
                        <div id="map-picker"></div>
                        <div class="coords-row">
                            <div>
                                <label for="lat">Latitud</label>
                                <input type="number" id="lat" name="lat" step="any"
                                       value="<?= $encuesta && $encuesta['lat'] ? $encuesta['lat'] : '-34.6037' ?>"
                                       placeholder="Latitud">
                            </div>
                            <div>
                                <label for="lng">Longitud</label>
                                <input type="number" id="lng" name="lng" step="any"
                                       value="<?= $encuesta && $encuesta['lng'] ? $encuesta['lng'] : '-58.3816' ?>"
                                       placeholder="Longitud">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="fecha_expiracion">Fecha de Expiración</label>
                        <input type="datetime-local" id="fecha_expiracion" name="fecha_expiracion"
                               value="<?= $encuesta && $encuesta['fecha_expiracion'] ? str_replace(' ', 'T', substr($encuesta['fecha_expiracion'], 0, 16)) : '' ?>"
                               placeholder="Dejar vacío para sin expiración">
                        <small>Dejar vacío si no deseas que expire</small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">💾 Guardar Encuesta</button>
                        <a href="?action=list" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- VISTA: ESTADÍSTICAS -->
        <?php if ($action === 'stats' && $id > 0): ?>
            <?php $stats = Encuesta::getStats($id); ?>
            <div class="stats-container">
                <h2>📊 Estadísticas - <?= htmlspecialchars(Encuesta::getById($id)['titulo']) ?></h2>

                <div class="stat-box">
                    <h3><?= $stats['total_participantes'] ?></h3>
                    <p>Participantes Totales</p>
                </div>

                <?php foreach ($stats['preguntas'] as $pregunta): ?>
                    <div class="pregunta-stats">
                        <h4><?= htmlspecialchars($pregunta['pregunta'] ?? 'Pregunta ' . $pregunta['id']) ?></h4>
                        <p style="font-size: 12px; color: #6c757d; margin-bottom: 10px;">
                            <?= $pregunta['respuestas_totales'] ?> respuestas
                        </p>

                        <?php
                            $max_respuestas = max(array_column($pregunta['respuestas'], 'cantidad'), 1);
                        ?>
                        <?php foreach ($pregunta['respuestas'] as $resp): ?>
                            <div class="respuesta-stat">
                                <span><?= htmlspecialchars($resp['respuesta']) ?></span>
                                <span><?= $resp['cantidad'] ?></span>
                            </div>
                            <div class="respuesta-stat-bar" style="width: <?= ($resp['cantidad'] / $max_respuestas * 100) ?>%"></div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

                <div style="margin-top: 30px;">
                    <a href="?action=list" class="btn btn-secondary">← Volver</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Inicializar mapa
        var defaultLat = <?= isset($encuesta) && $encuesta['lat'] ? $encuesta['lat'] : '-34.6037' ?>;
        var defaultLng = <?= isset($encuesta) && $encuesta['lng'] ? $encuesta['lng'] : '-58.3816' ?>;

        if (document.getElementById('map-picker')) {
            var map = L.map('map-picker').setView([defaultLat, defaultLng], 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(map);

            var marker = null;
            if (defaultLat != -34.6037 || defaultLng != -58.3816) {
                marker = L.marker([defaultLat, defaultLng]).addTo(map);
            }

            map.on('click', function(e) {
                document.getElementById('lat').value = e.latlng.lat.toFixed(6);
                document.getElementById('lng').value = e.latlng.lng.toFixed(6);
                if (marker) map.removeLayer(marker);
                marker = L.marker(e.latlng).addTo(map);
            });
        }
    </script>
</body>
</html>
