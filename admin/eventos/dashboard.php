<?php
/**
 * Admin Dashboard - Eventos
 * Permite crear, editar, ver y eliminar eventos
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
require_once __DIR__ . '/../../models/Evento.php';

use App\Models\Evento;

$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);

$evento = null;
$eventos = [];

// Cargar evento si estamos editando
if ($action === 'edit' && $id > 0) {
    $evento = Evento::getById($id);
}

// Cargar lista de eventos
if ($action === 'list' || !in_array($action, ['edit', 'create', 'stats'])) {
    $eventos = Evento::getAll();
}

// Procesar formulario POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'] ?? null;
    $descripcion = $_POST['descripcion'] ?? null;
    $lat = $_POST['lat'] ?? null;
    $lng = $_POST['lng'] ?? null;
    $fecha = $_POST['fecha'] ?? null;
    $hora = $_POST['hora'] ?? null;
    $organizador = $_POST['organizador'] ?? null;
    $youtube_link = $_POST['youtube_link'] ?? null;
    $ubicacion = $_POST['ubicacion'] ?? null;
    $categoria = $_POST['categoria'] ?? null;
    $mapita_id = $_POST['mapita_id'] ?? null;

    if (!$titulo) {
        $error = "El título es requerido";
    } else {
        if ($action === 'create') {
            $result = Evento::create([
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'lat' => $lat,
                'lng' => $lng,
                'fecha' => $fecha,
                'hora' => $hora,
                'organizador' => $organizador,
                'youtube_link' => $youtube_link,
                'ubicacion' => $ubicacion,
                'categoria' => $categoria,
                'mapita_id' => $mapita_id
            ]);

            if ($result) {
                $_SESSION['mensaje'] = "Evento creado exitosamente";
                header("Location: /admin/eventos/dashboard.php?action=edit&id=$result");
                exit;
            } else {
                $error = "Error al crear el evento";
            }
        } elseif ($action === 'edit' && $id > 0) {
            $result = Evento::update($id, [
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'lat' => $lat,
                'lng' => $lng,
                'fecha' => $fecha,
                'hora' => $hora,
                'organizador' => $organizador,
                'youtube_link' => $youtube_link,
                'ubicacion' => $ubicacion,
                'categoria' => $categoria,
                'mapita_id' => $mapita_id
            ]);

            if ($result) {
                $_SESSION['mensaje'] = "Evento actualizado exitosamente";
            } else {
                $error = "Error al actualizar el evento";
            }
        }
    }

    header("Location: /admin/eventos/dashboard.php");
    exit;
}

$mensaje = $_SESSION['mensaje'] ?? null;
unset($_SESSION['mensaje']);

function formatearFecha($fecha) {
    if (!$fecha) return '-';
    try {
        $dt = new DateTime($fecha);
        return $dt->format('d/m/Y H:i');
    } catch (Exception $e) {
        return $fecha;
    }
}

function estaProximo($fecha) {
    if (!$fecha) return false;
    try {
        $dt = new DateTime($fecha);
        $ahora = new DateTime();
        $diff = $dt->getTimestamp() - $ahora->getTimestamp();
        return $diff > 0 && $diff < (7 * 24 * 60 * 60); // próximos 7 días
    } catch (Exception $e) {
        return false;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Eventos</title>
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
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
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
            color: #e74c3c;
            border-bottom-color: #e74c3c;
        }
        .tabs a:hover, .tabs button:hover {
            color: #e74c3c;
        }

        .eventos-table {
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
        .badge-proximo {
            background: #fff3cd;
            color: #856404;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
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
            background: #e74c3c;
            color: white;
        }
        .btn-primary:hover {
            background: #c0392b;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
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

        .form-container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            max-width: 900px;
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
            border-color: #e74c3c;
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        .coords-row, .misc-row {
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

        .event-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            border-left: 4px solid #e74c3c;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .event-card h3 {
            margin: 0 0 10px 0;
            color: #e74c3c;
        }
        .event-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 13px;
        }
        .event-info strong { color: #2c3e50; }
        .event-info span { color: #6c757d; }
    </style>
</head>
<body>
    <div class="admin-container">
        <header>
            <h1>🎉 Gestión de Eventos</h1>
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
            <a href="?action=create" class="<?= $action === 'create' ? 'active' : '' ?>">➕ Nuevo Evento</a>
        </div>

        <!-- VISTA: LISTADO -->
        <?php if ($action === 'list'): ?>
            <div class="eventos-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Fecha</th>
                            <th>Ubicación</th>
                            <th>Estado</th>
                            <th>Categoría</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($eventos as $evt): ?>
                            <?php
                                $es_proximo = estaProximo($evt['fecha'] ?? null);
                                $es_activo = $evt['activo'] == 1;
                            ?>
                            <tr>
                                <td>#<?= $evt['id'] ?></td>
                                <td><?= htmlspecialchars($evt['titulo']) ?></td>
                                <td>
                                    <?php if (!empty($evt['fecha'])): ?>
                                        <strong><?= formatearFecha($evt['fecha']) ?></strong>
                                        <?php if (!empty($evt['hora'])): ?>
                                            <br><?= htmlspecialchars(substr($evt['hora'], 0, 5)) ?>
                                        <?php endif; ?>
                                        <?php if ($es_proximo): ?>
                                            <br><span class="badge badge-proximo">⏰ Próximo</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <em>Sin fecha</em>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($evt['ubicacion'] ?? '-') ?></td>
                                <td>
                                    <span class="badge <?= $es_activo ? 'badge-activo' : 'badge-inactivo' ?>">
                                        <?= $es_activo ? '✓ Activo' : '✗ Inactivo' ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($evt['categoria'] ?? '-') ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="?action=edit&id=<?= $evt['id'] ?>" class="btn btn-primary btn-small">Editar</a>
                                        <?php if ($evt['youtube_link']): ?>
                                            <a href="<?= htmlspecialchars($evt['youtube_link']) ?>" target="_blank" class="btn btn-secondary btn-small">🎥 YouTube</a>
                                        <?php endif; ?>
                                        <a href="?action=delete&id=<?= $evt['id'] ?>" class="btn btn-danger btn-small" onclick="return confirm('¿Desactivar evento?')">Desactivar</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- VISTA: CREAR/EDITAR -->
        <?php if ($action === 'create' || ($action === 'edit' && $evento)): ?>
            <div class="form-container">
                <h2><?= $action === 'create' ? '➕ Nuevo Evento' : '✏️ Editar Evento' ?></h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="titulo">Título *</label>
                        <input type="text" id="titulo" name="titulo" required
                               value="<?= $evento ? htmlspecialchars($evento['titulo']) : '' ?>"
                               placeholder="Ej: Festival de Música 2026">
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion"
                                  placeholder="Describe el evento..."><?= $evento ? htmlspecialchars($evento['descripcion']) : '' ?></textarea>
                    </div>

                    <div class="misc-row">
                        <div class="form-group">
                            <label for="ubicacion">Ubicación/Lugar</label>
                            <input type="text" id="ubicacion" name="ubicacion"
                                   value="<?= $evento ? htmlspecialchars($evento['ubicacion']) : '' ?>"
                                   placeholder="Ej: Centro Cultural">
                        </div>
                        <div class="form-group">
                            <label for="categoria">Categoría</label>
                            <select id="categoria" name="categoria">
                                <option value="">Selecciona una categoría</option>
                                <option value="musica" <?= $evento && $evento['categoria'] === 'musica' ? 'selected' : '' ?>>🎵 Música</option>
                                <option value="deportes" <?= $evento && $evento['categoria'] === 'deportes' ? 'selected' : '' ?>>⚽ Deportes</option>
                                <option value="cultura" <?= $evento && $evento['categoria'] === 'cultura' ? 'selected' : '' ?>>🎭 Cultura</option>
                                <option value="gastronom" <?= $evento && $evento['categoria'] === 'gastronom' ? 'selected' : '' ?>>🍽️ Gastronomía</option>
                                <option value="negocios" <?= $evento && $evento['categoria'] === 'negocios' ? 'selected' : '' ?>>💼 Negocios</option>
                                <option value="educacion" <?= $evento && $evento['categoria'] === 'educacion' ? 'selected' : '' ?>>📚 Educación</option>
                                <option value="otro" <?= $evento && $evento['categoria'] === 'otro' ? 'selected' : '' ?>>🎪 Otro</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="youtube_link">Link de YouTube (Transmisión en Vivo)</label>
                        <input type="url" id="youtube_link" name="youtube_link"
                               value="<?= $evento ? htmlspecialchars($evento['youtube_link']) : '' ?>"
                               placeholder="https://www.youtube.com/watch?v=...">
                    </div>

                    <div class="form-group">
                        <label for="mapita_id">Mapita ID</label>
                        <input type="text" id="mapita_id" name="mapita_id" maxlength="64"
                               value="<?= $evento ? htmlspecialchars($evento['mapita_id'] ?? '') : '' ?>"
                               placeholder="Ej: EVT-001">
                    </div>

                    <div class="form-group">
                        <label>📍 Ubicación en el Mapa</label>
                        <div id="map-picker"></div>
                        <div class="coords-row">
                            <div>
                                <label for="lat">Latitud</label>
                                <input type="number" id="lat" name="lat" step="any"
                                       value="<?= $evento && $evento['lat'] ? $evento['lat'] : '-34.6037' ?>"
                                       placeholder="Latitud">
                            </div>
                            <div>
                                <label for="lng">Longitud</label>
                                <input type="number" id="lng" name="lng" step="any"
                                       value="<?= $evento && $evento['lng'] ? $evento['lng'] : '-58.3816' ?>"
                                       placeholder="Longitud">
                            </div>
                        </div>
                    </div>

                    <div class="misc-row">
                        <div class="form-group">
                            <label for="fecha">📅 Fecha del Evento</label>
                            <input type="date" id="fecha" name="fecha"
                                   value="<?= $evento ? htmlspecialchars($evento['fecha'] ?? '') : '' ?>">
                        </div>
                        <div class="form-group">
                            <label for="hora">🕐 Hora</label>
                            <input type="time" id="hora" name="hora"
                                   value="<?= $evento ? htmlspecialchars(substr($evento['hora'] ?? '', 0, 5)) : '' ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="organizador">👤 Organizador</label>
                        <input type="text" id="organizador" name="organizador"
                               value="<?= $evento ? htmlspecialchars($evento['organizador'] ?? '') : '' ?>"
                               placeholder="Ej: Club Deportivo Central">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">💾 Guardar Evento</button>
                        <a href="?action=list" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Inicializar mapa
        var defaultLat = <?= isset($evento) && $evento['lat'] ? $evento['lat'] : '-34.6037' ?>;
        var defaultLng = <?= isset($evento) && $evento['lng'] ? $evento['lng'] : '-58.3816' ?>;

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
