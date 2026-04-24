<?php
/**
 * Admin Dashboard - Encuestas
 * Permite crear, editar, ver y eliminar encuestas con preguntas y opciones predefinidas.
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
$error = null;

// Cargar encuesta si estamos editando
if (($action === 'edit' || $action === 'stats') && $id > 0) {
    $encuesta = Encuesta::getById($id);
}

// Cargar lista de encuestas
if ($action === 'list' || !in_array($action, ['edit', 'create', 'stats'])) {
    $encuestas = Encuesta::getAll();
}

// ── Desactivar encuesta ───────────────────────────────────────────────────────
if ($action === 'delete' && $id > 0) {
    Encuesta::deactivate($id);
    $_SESSION['mensaje'] = "Encuesta desactivada";
    header("Location: /admin/encuestas/dashboard.php");
    exit;
}

// ── Procesar formulario POST ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo           = trim($_POST['titulo'] ?? '');
    $descripcion      = $_POST['descripcion'] ?? null;
    $lat              = $_POST['lat'] ?? null;
    $lng              = $_POST['lng'] ?? null;
    $fecha_expiracion = $_POST['fecha_expiracion'] ?? null;

    if (!$titulo) {
        $error = "El título es requerido";
    } else {

        // --- Recopilar preguntas y opciones enviadas ---
        $preguntas_texto  = $_POST['preguntas'] ?? [];
        $todas_opciones   = $_POST['opciones']  ?? [];

        // Construir array limpio [ ['texto'=>..., 'opciones'=>[...]], ... ]
        $preguntas_data = [];
        foreach ($preguntas_texto as $idx => $texto) {
            $texto = trim($texto);
            if ($texto === '') continue;
            $opts_raw = isset($todas_opciones[$idx]) ? (array)$todas_opciones[$idx] : [];
            $opts = array_values(array_filter(array_map('trim', $opts_raw), 'strlen'));
            $opts = array_slice($opts, 0, 5); // Máximo 5 opciones
            $preguntas_data[] = ['texto' => $texto, 'opciones' => $opts];
        }

        // --- Validar opciones ---
        foreach ($preguntas_data as $i => $p) {
            if (count($p['opciones']) < 2) {
                $error = "La pregunta " . ($i + 1) . " debe tener al menos 2 opciones de respuesta.";
                break;
            }
        }

        if (!$error) {
            if ($action === 'create') {
                $result = Encuesta::create([
                    'titulo'           => $titulo,
                    'descripcion'      => $descripcion,
                    'lat'              => $lat,
                    'lng'              => $lng,
                    'fecha_expiracion' => $fecha_expiracion,
                ]);

                if ($result) {
                    if (!empty($preguntas_data)) {
                        Encuesta::savePreguntas($result, $preguntas_data);
                    }
                    $_SESSION['mensaje'] = "Encuesta creada exitosamente";
                    header("Location: /admin/encuestas/dashboard.php?action=edit&id=$result");
                    exit;
                } else {
                    $error = "Error al crear la encuesta";
                }

            } elseif ($action === 'edit' && $id > 0) {
                $result = Encuesta::update($id, [
                    'titulo'           => $titulo,
                    'descripcion'      => $descripcion,
                    'lat'              => $lat,
                    'lng'              => $lng,
                    'fecha_expiracion' => $fecha_expiracion,
                ]);

                if ($result) {
                    // Reemplazar preguntas si se enviaron nuevas
                    if (!empty($preguntas_data)) {
                        Encuesta::deletePreguntas($id);
                        Encuesta::savePreguntas($id, $preguntas_data);
                    }
                    $_SESSION['mensaje'] = "Encuesta actualizada exitosamente";
                    header("Location: /admin/encuestas/dashboard.php?action=edit&id=$id");
                    exit;
                } else {
                    $error = "Error al actualizar la encuesta";
                }
            }
        }

        // Si hubo error en create/edit, recargar encuesta para edición
        if ($action === 'edit' && $id > 0) {
            $encuesta = Encuesta::getById($id);
        }
    }
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
        .admin-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; padding: 30px 20px; border-radius: 8px;
            margin-bottom: 30px; display: flex;
            justify-content: space-between; align-items: center;
        }
        header h1 { font-size: 24px; }
        header a {
            background: rgba(255,255,255,0.2); color: white;
            padding: 10px 20px; border-radius: 6px; text-decoration: none; transition: 0.3s;
        }
        header a:hover { background: rgba(255,255,255,0.3); }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error   { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #d0d5dd; }
        .tabs a, .tabs button {
            padding: 12px 20px; background: none; border: none;
            border-bottom: 3px solid transparent; cursor: pointer;
            color: #6c757d; font-size: 14px; font-weight: 500; transition: 0.3s;
        }
        .tabs a.active, .tabs button.active { color: #667eea; border-bottom-color: #667eea; }
        .tabs a:hover, .tabs button:hover { color: #667eea; }

        /* LISTA */
        .encuestas-table { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8f9fa; padding: 15px; text-align: left; font-weight: 600; color: #2c3e50; border-bottom: 2px solid #e9ecef; }
        td { padding: 15px; border-bottom: 1px solid #e9ecef; }
        tr:hover { background: #f8f9fa; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge-activo   { background: #d4edda; color: #155724; }
        .badge-inactivo { background: #f8d7da; color: #721c24; }
        .badge-vigente  { background: #cfe2ff; color: #084298; }
        .badge-expirado { background: #f5f6fa; color: #6c757d; }
        .btn {
            display: inline-block; padding: 8px 16px; border: none; border-radius: 6px;
            font-size: 13px; font-weight: 500; cursor: pointer; transition: 0.3s; text-decoration: none;
        }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5568d3; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(102,126,234,.3); }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-danger:hover { background: #c0392b; }
        .btn-small { padding: 6px 12px; font-size: 12px; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }

        /* FORMULARIO */
        .form-container { background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); max-width: 860px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #2c3e50; }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%; padding: 10px 12px; border: 1px solid #d0d5dd;
            border-radius: 6px; font-size: 14px; font-family: inherit; transition: 0.3s;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,.1);
        }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .coords-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        #map-picker { height: 300px; width: 100%; border-radius: 6px; border: 1px solid #d0d5dd; margin-bottom: 15px; }
        .form-actions { display: flex; gap: 10px; margin-top: 30px; }
        .form-actions .btn { flex: 1; padding: 12px; text-align: center; }

        /* ── PREGUNTAS ────────────────────────────────────── */
        .preguntas-section { margin-top: 30px; }
        .preguntas-section h3 {
            font-size: 16px; font-weight: 700; color: #2c3e50;
            margin-bottom: 16px; display: flex; align-items: center; gap: 8px;
        }
        .pregunta-block {
            background: #f8f9ff; border: 1px solid #d0d5ff; border-radius: 8px;
            padding: 20px; margin-bottom: 16px; position: relative;
        }
        .pregunta-block-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 14px; font-weight: 600; color: #5568d3; font-size: 13px;
        }
        .pregunta-block input[type="text"],
        .pregunta-block textarea {
            width: 100%; padding: 9px 12px; border: 1px solid #c8cff9;
            border-radius: 6px; font-size: 14px; font-family: inherit;
            background: white; transition: 0.3s;
        }
        .pregunta-block input:focus, .pregunta-block textarea:focus {
            outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,.1);
        }
        .opciones-list { margin-top: 12px; }
        .opcion-row {
            display: flex; gap: 8px; align-items: center; margin-bottom: 8px;
        }
        .opcion-row input { flex: 1; }
        .btn-remove-opcion {
            background: none; border: 1px solid #e74c3c; color: #e74c3c;
            border-radius: 4px; padding: 4px 10px; cursor: pointer; font-size: 13px;
            transition: 0.2s; white-space: nowrap;
        }
        .btn-remove-opcion:hover { background: #e74c3c; color: white; }
        .btn-add-opcion {
            background: none; border: 1px dashed #667eea; color: #667eea;
            border-radius: 6px; padding: 7px 14px; cursor: pointer; font-size: 13px;
            margin-top: 6px; transition: 0.2s; width: 100%;
        }
        .btn-add-opcion:hover { background: rgba(102,126,234,.08); }
        .btn-add-opcion:disabled { opacity: .45; cursor: not-allowed; }
        .btn-remove-pregunta {
            background: none; border: none; color: #e74c3c; cursor: pointer;
            font-size: 18px; padding: 0 4px; line-height: 1;
        }
        .btn-remove-pregunta:hover { color: #c0392b; }
        .btn-add-pregunta {
            background: none; border: 2px dashed #667eea; color: #667eea;
            border-radius: 8px; padding: 12px; cursor: pointer; font-size: 14px;
            font-weight: 600; width: 100%; margin-top: 4px; transition: 0.2s;
        }
        .btn-add-pregunta:hover { background: rgba(102,126,234,.06); }
        .opciones-counter { font-size: 11px; color: #888; margin-top: 4px; }
        .info-box {
            background: #e8f4ff; border: 1px solid #b8d8f5; border-radius: 6px;
            padding: 12px 16px; font-size: 13px; color: #1a5276; margin-bottom: 20px;
        }

        /* ESTADÍSTICAS */
        .stats-container { background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .stat-box { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
        .stat-box h3 { font-size: 32px; margin-bottom: 5px; }
        .stat-box p { font-size: 14px; opacity: 0.9; }
        .pregunta-stats { background: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 15px; }
        .pregunta-stats h4 { margin-bottom: 10px; color: #2c3e50; }
        .respuesta-stat { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #e9ecef; gap: 12px; }
        .respuesta-stat:last-child { border-bottom: none; }
        .respuesta-stat-label { flex: 1; font-size: 14px; }
        .respuesta-stat-count { font-weight: 700; color: #667eea; white-space: nowrap; }
        .respuesta-stat-bar-wrap { flex: 2; background: #e9ecef; border-radius: 3px; height: 14px; overflow: hidden; }
        .respuesta-stat-bar { background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); height: 14px; border-radius: 3px; transition: width .5s; }
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

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- TABS -->
        <div class="tabs">
            <a href="?action=list"   class="<?= $action === 'list'   ? 'active' : '' ?>">📋 Listado</a>
            <a href="?action=create" class="<?= $action === 'create' ? 'active' : '' ?>">➕ Nueva Encuesta</a>
        </div>

        <!-- ── VISTA: LISTADO ──────────────────────────────────────────────── -->
        <?php if ($action === 'list'): ?>
            <div class="encuestas-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Estado</th>
                            <th>Vigencia</th>
                            <th>Preguntas</th>
                            <th>Ubicación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($encuestas as $enc): ?>
                            <?php
                                $es_activo  = $enc['activo'] == 1;
                                $fecha_exp  = $enc['fecha_expiracion'];
                                $es_vigente = !$fecha_exp || strtotime($fecha_exp) >= time();
                                $encuesta_full = Encuesta::getById($enc['id']);
                                $num_preg = count($encuesta_full['preguntas'] ?? []);
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
                                <td><?= $num_preg ?> pregunta<?= $num_preg !== 1 ? 's' : '' ?></td>
                                <td>
                                    <?php if ($enc['lat'] && $enc['lng']): ?>
                                        <small><?= number_format($enc['lat'], 4) ?>, <?= number_format($enc['lng'], 4) ?></small>
                                    <?php else: ?>
                                        <small style="color:#aaa;">Sin ubicación</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="?action=edit&id=<?= $enc['id'] ?>"  class="btn btn-primary btn-small">✏️ Editar</a>
                                        <a href="?action=stats&id=<?= $enc['id'] ?>" class="btn btn-secondary btn-small">📊 Stats</a>
                                        <a href="?action=delete&id=<?= $enc['id'] ?>" class="btn btn-danger btn-small"
                                           onclick="return confirm('¿Desactivar esta encuesta?')">Desactivar</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- ── VISTA: CREAR / EDITAR ──────────────────────────────────────── -->
        <?php if ($action === 'create' || ($action === 'edit' && $encuesta)): ?>
            <div class="form-container">
                <h2><?= $action === 'create' ? '➕ Nueva Encuesta' : '✏️ Editar Encuesta' ?></h2>

                <form method="POST" id="form-encuesta" novalidate>

                    <!-- ── Datos básicos ──────────────────────────────────── -->
                    <div class="form-group">
                        <label for="titulo">Título *</label>
                        <input type="text" id="titulo" name="titulo" required
                               value="<?= $encuesta ? htmlspecialchars($encuesta['titulo']) : '' ?>"
                               placeholder="Ej: ¿Cuál es tu servicio preferido?">
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion"
                                  placeholder="Describe el objetivo de la encuesta..."><?= $encuesta ? htmlspecialchars($encuesta['descripcion']) : '' ?></textarea>
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

                    <!-- ── Preguntas con opciones ─────────────────────────── -->
                    <div class="preguntas-section">
                        <h3>❓ Preguntas de la Encuesta</h3>

                        <div class="info-box">
                            Agrega preguntas con opciones predefinidas (mínimo 2, máximo 5 opciones por pregunta).
                            Los usuarios elegirán una opción y los resultados se podrán graficar.
                        </div>

                        <div id="preguntas-container">
                            <?php if ($action === 'edit' && !empty($encuesta['preguntas'])): ?>
                                <?php foreach ($encuesta['preguntas'] as $pi => $preg): ?>
                                    <div class="pregunta-block" id="pregunta-<?= $pi ?>">
                                        <div class="pregunta-block-header">
                                            <span>Pregunta <?= $pi + 1 ?></span>
                                            <button type="button" class="btn-remove-pregunta"
                                                    onclick="eliminarPregunta(<?= $pi ?>)" title="Eliminar pregunta">✕</button>
                                        </div>
                                        <input type="text" name="preguntas[<?= $pi ?>]"
                                               value="<?= htmlspecialchars($preg['pregunta'] ?? $preg['texto_pregunta'] ?? '') ?>"
                                               placeholder="Ej: ¿Qué servicio utilizás más?" required>

                                        <div class="opciones-list" id="opciones-list-<?= $pi ?>">
                                            <?php $opts = $preg['opciones_array'] ?? Encuesta::parseOpciones($preg['opciones'] ?? ''); ?>
                                            <?php foreach ($opts as $oi => $opt): ?>
                                                <div class="opcion-row" id="opcion-<?= $pi ?>-<?= $oi ?>">
                                                    <input type="text" name="opciones[<?= $pi ?>][]"
                                                           value="<?= htmlspecialchars($opt) ?>"
                                                           placeholder="Opción <?= $oi + 1 ?>" required>
                                                    <button type="button" class="btn-remove-opcion"
                                                            onclick="eliminarOpcion(<?= $pi ?>, this)">✕</button>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <button type="button" class="btn-add-opcion" id="btn-add-opcion-<?= $pi ?>"
                                                onclick="agregarOpcion(<?= $pi ?>)"
                                                <?= count($opts) >= 5 ? 'disabled' : '' ?>>
                                            + Agregar opción
                                        </button>
                                        <div class="opciones-counter" id="counter-<?= $pi ?>">
                                            <?= count($opts) ?>/5 opciones
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <button type="button" class="btn-add-pregunta" onclick="agregarPregunta()">
                            ➕ Agregar Pregunta
                        </button>
                    </div>

                    <div class="form-actions" style="margin-top: 30px;">
                        <button type="submit" class="btn btn-primary">💾 Guardar Encuesta</button>
                        <a href="?action=list" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- ── VISTA: ESTADÍSTICAS ────────────────────────────────────────── -->
        <?php if ($action === 'stats' && $id > 0 && $encuesta): ?>
            <?php $stats = Encuesta::getStats($id); ?>
            <div class="stats-container">
                <h2>📊 Estadísticas — <?= htmlspecialchars($encuesta['titulo']) ?></h2>

                <div class="stat-box">
                    <h3><?= (int)($stats['total_participantes'] ?? 0) ?></h3>
                    <p>Participantes Totales</p>
                </div>

                <?php foreach (($stats['preguntas'] ?? []) as $pregunta): ?>
                    <div class="pregunta-stats">
                        <h4><?= htmlspecialchars($pregunta['pregunta'] ?? 'Pregunta ' . $pregunta['id']) ?></h4>
                        <p style="font-size:12px;color:#6c757d;margin-bottom:12px;">
                            <?= (int)($pregunta['respuestas_totales'] ?? 0) ?> respuestas
                        </p>
                        <?php
                            $max_r = max(array_column($pregunta['respuestas'] ?? [], 'cantidad') ?: [1]);
                            $max_r = max((int)$max_r, 1);
                        ?>
                        <?php foreach (($pregunta['respuestas'] ?? []) as $resp): ?>
                            <div class="respuesta-stat">
                                <span class="respuesta-stat-label"><?= htmlspecialchars($resp['respuesta']) ?></span>
                                <div class="respuesta-stat-bar-wrap">
                                    <div class="respuesta-stat-bar"
                                         style="width:<?= round($resp['cantidad'] / $max_r * 100) ?>%"></div>
                                </div>
                                <span class="respuesta-stat-count"><?= (int)$resp['cantidad'] ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

                <div style="margin-top:30px;display:flex;gap:10px;">
                    <a href="?action=edit&id=<?= $id ?>"  class="btn btn-primary">✏️ Editar</a>
                    <a href="?action=list" class="btn btn-secondary">← Volver</a>
                </div>
            </div>
        <?php endif; ?>

    </div><!-- /.admin-container -->

    <script>
    // ── Índice incremental para bloques de pregunta ─────────────────────────
    var preguntaIndex = <?= ($action === 'edit' && !empty($encuesta['preguntas']))
        ? count($encuesta['preguntas'])
        : 0 ?>;

    function agregarPregunta() {
        var idx = preguntaIndex++;
        var container = document.getElementById('preguntas-container');
        var div = document.createElement('div');
        div.className = 'pregunta-block';
        div.id = 'pregunta-' + idx;
        div.innerHTML =
            '<div class="pregunta-block-header">' +
                '<span>Pregunta ' + (container.querySelectorAll('.pregunta-block').length + 1) + '</span>' +
                '<button type="button" class="btn-remove-pregunta" onclick="eliminarPregunta(' + idx + ')" title="Eliminar pregunta">✕</button>' +
            '</div>' +
            '<input type="text" name="preguntas[' + idx + ']" placeholder="Ej: ¿Cuál es tu opinión?" required>' +
            '<div class="opciones-list" id="opciones-list-' + idx + '">' +
                '<div class="opcion-row" id="opcion-' + idx + '-0">' +
                    '<input type="text" name="opciones[' + idx + '][]" placeholder="Opción 1" required>' +
                    '<button type="button" class="btn-remove-opcion" onclick="eliminarOpcion(' + idx + ', this)">✕</button>' +
                '</div>' +
                '<div class="opcion-row" id="opcion-' + idx + '-1">' +
                    '<input type="text" name="opciones[' + idx + '][]" placeholder="Opción 2" required>' +
                    '<button type="button" class="btn-remove-opcion" onclick="eliminarOpcion(' + idx + ', this)">✕</button>' +
                '</div>' +
            '</div>' +
            '<button type="button" class="btn-add-opcion" id="btn-add-opcion-' + idx + '" onclick="agregarOpcion(' + idx + ')">+ Agregar opción</button>' +
            '<div class="opciones-counter" id="counter-' + idx + '">2/5 opciones</div>';
        container.appendChild(div);
    }

    function eliminarPregunta(idx) {
        var el = document.getElementById('pregunta-' + idx);
        if (el) el.remove();
        renumerarPreguntas();
    }

    function renumerarPreguntas() {
        var bloques = document.querySelectorAll('#preguntas-container .pregunta-block');
        bloques.forEach(function(b, i) {
            var header = b.querySelector('.pregunta-block-header span');
            if (header) header.textContent = 'Pregunta ' + (i + 1);
        });
    }

    function agregarOpcion(pregIdx) {
        var list = document.getElementById('opciones-list-' + pregIdx);
        var rows = list.querySelectorAll('.opcion-row');
        if (rows.length >= 5) return;
        var newIdx = rows.length;
        var div = document.createElement('div');
        div.className = 'opcion-row';
        div.id = 'opcion-' + pregIdx + '-' + newIdx;
        div.innerHTML =
            '<input type="text" name="opciones[' + pregIdx + '][]" placeholder="Opción ' + (newIdx + 1) + '" required>' +
            '<button type="button" class="btn-remove-opcion" onclick="eliminarOpcion(' + pregIdx + ', this)">✕</button>';
        list.appendChild(div);
        actualizarContador(pregIdx);
    }

    function eliminarOpcion(pregIdx, btn) {
        var list = document.getElementById('opciones-list-' + pregIdx);
        var rows = list.querySelectorAll('.opcion-row');
        if (rows.length <= 2) { alert('Cada pregunta debe tener al menos 2 opciones.'); return; }
        btn.closest('.opcion-row').remove();
        actualizarContador(pregIdx);
        // Re-numerar placeholders
        list.querySelectorAll('.opcion-row input').forEach(function(inp, i) {
            inp.placeholder = 'Opción ' + (i + 1);
        });
    }

    function actualizarContador(pregIdx) {
        var list    = document.getElementById('opciones-list-' + pregIdx);
        var count   = list ? list.querySelectorAll('.opcion-row').length : 0;
        var counter = document.getElementById('counter-' + pregIdx);
        var btn     = document.getElementById('btn-add-opcion-' + pregIdx);
        if (counter) counter.textContent = count + '/5 opciones';
        if (btn) btn.disabled = (count >= 5);
    }

    // ── Validación antes de enviar ──────────────────────────────────────────
    document.getElementById('form-encuesta').addEventListener('submit', function(e) {
        var bloques = document.querySelectorAll('#preguntas-container .pregunta-block');
        for (var i = 0; i < bloques.length; i++) {
            var txtInput = bloques[i].querySelector('input[type="text"]');
            if (txtInput && txtInput.value.trim() === '') {
                e.preventDefault();
                alert('El texto de la Pregunta ' + (i + 1) + ' no puede estar vacío.');
                txtInput.focus();
                return;
            }
            var opcInputs = bloques[i].querySelectorAll('.opcion-row input');
            var vacias = 0;
            opcInputs.forEach(function(inp) { if (!inp.value.trim()) vacias++; });
            if (vacias > 0) {
                e.preventDefault();
                alert('Las opciones de la Pregunta ' + (i + 1) + ' no pueden estar vacías.');
                return;
            }
            if (opcInputs.length < 2) {
                e.preventDefault();
                alert('La Pregunta ' + (i + 1) + ' debe tener al menos 2 opciones.');
                return;
            }
        }
    });

    // ── Mapa de ubicación ───────────────────────────────────────────────────
    var defaultLat = <?= isset($encuesta) && $encuesta && $encuesta['lat'] ? (float)$encuesta['lat'] : -34.6037 ?>;
    var defaultLng = <?= isset($encuesta) && $encuesta && $encuesta['lng'] ? (float)$encuesta['lng'] : -58.3816 ?>;

    if (document.getElementById('map-picker')) {
        var map    = L.map('map-picker').setView([defaultLat, defaultLng], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
        var marker = null;
        if (defaultLat !== -34.6037 || defaultLng !== -58.3816) {
            marker = L.marker([defaultLat, defaultLng]).addTo(map);
        }
        map.on('click', function(e) {
            document.getElementById('lat').value = e.latlng.lat.toFixed(6);
            document.getElementById('lng').value = e.latlng.lng.toFixed(6);
            if (marker) map.removeLayer(marker);
            marker = L.marker(e.latlng).addTo(map);
        });
    }

    // Inicializar contadores para preguntas precargadas (edición)
    document.querySelectorAll('#preguntas-container .pregunta-block').forEach(function(b) {
        var id = b.id.replace('pregunta-', '');
        actualizarContador(parseInt(id));
    });
    </script>
</body>
</html>
