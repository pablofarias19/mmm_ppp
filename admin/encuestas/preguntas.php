<?php
/**
 * Admin - Gestionar preguntas de una encuesta
 * Permite agregar/eliminar preguntas con opciones predefinidas (sin texto libre).
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

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: /admin/encuestas/dashboard.php');
    exit;
}

$encuesta = Encuesta::getById($id);
if (!$encuesta) {
    header('Location: /admin/encuestas/dashboard.php');
    exit;
}

$error  = null;

// -- Procesar POST ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['action'] ?? 'add';

    // Eliminar pregunta
    if ($post_action === 'delete') {
        $pregunta_id = (int)($_POST['pregunta_id'] ?? 0);
        if ($pregunta_id > 0) {
            try {
                $db   = \Core\Database::getInstance()->getConnection();
                $stmt = $db->prepare("DELETE FROM preguntas_encuesta WHERE id = ? AND encuesta_id = ?");
                $stmt->execute([$pregunta_id, $id]);
                $_SESSION['mensaje'] = "Pregunta eliminada";
            } catch (Exception $e) {
                $_SESSION['mensaje_error'] = "Error al eliminar: " . $e->getMessage();
            }
        }
        header("Location: /admin/encuestas/preguntas.php?id=$id");
        exit;
    }

    // Agregar pregunta
    $pregunta_texto = trim($_POST['pregunta'] ?? '');
    $opciones_raw   = $_POST['opciones'] ?? [];

    if (!$pregunta_texto) {
        $error = "El texto de la pregunta es requerido";
    } else {
        $opts = array_values(array_filter(array_map('trim', (array)$opciones_raw), 'strlen'));
        $opts = array_slice($opts, 0, 5);

        if (count($opts) < 2) {
            $error = "Debes agregar al menos 2 opciones de respuesta.";
        } else {
            $opts_str = implode(',', $opts);
            try {
                $db       = \Core\Database::getInstance()->getConnection();
                $stmt_ord = $db->prepare("SELECT COALESCE(MAX(orden),0)+1 AS prox FROM preguntas_encuesta WHERE encuesta_id = ?");
                $stmt_ord->execute([$id]);
                $orden = (int)($stmt_ord->fetchColumn() ?: 1);

                $stmt = $db->prepare(
                    "INSERT INTO preguntas_encuesta (encuesta_id, texto_pregunta, tipo, opciones, orden)
                     VALUES (?, ?, 'opcion_multiple', ?, ?)"
                );
                $stmt->execute([$id, $pregunta_texto, $opts_str, $orden]);
                $_SESSION['mensaje'] = "Pregunta agregada exitosamente";
                header("Location: /admin/encuestas/preguntas.php?id=$id");
                exit;
            } catch (Exception $e) {
                $error = "Error al guardar: " . $e->getMessage();
            }
        }
    }
}

$mensaje       = $_SESSION['mensaje']       ?? null;
$mensaje_error = $_SESSION['mensaje_error'] ?? null;
unset($_SESSION['mensaje'], $_SESSION['mensaje_error']);

// Recargar con preguntas actualizadas
$encuesta = Encuesta::getById($id);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preguntas — <?= htmlspecialchars($encuesta['titulo']) ?></title>
    <link rel="stylesheet" href="/css/map-styles.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f6fa; color: #2c3e50; }
        .container { max-width: 900px; margin: 0 auto; padding: 20px; }
        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; padding: 30px 20px; border-radius: 8px;
            margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;
        }
        header h1 { font-size: 24px; }
        header p  { font-size: 14px; opacity: .9; margin-top: 5px; }
        .btn { background: rgba(255,255,255,.2); color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; border: none; cursor: pointer; transition: .3s; font-size: 14px; }
        .btn:hover { background: rgba(255,255,255,.3); }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error   { background: #f8d7da; color: #721c24; }
        .form-container { background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,.1); margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; }
        .form-group input, .form-group textarea {
            width: 100%; padding: 10px 12px; border: 1px solid #d0d5dd;
            border-radius: 6px; font-size: 14px; font-family: inherit;
        }
        .form-group input:focus, .form-group textarea:focus {
            outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,.1);
        }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .form-actions { display: flex; gap: 10px; }
        .form-actions button {
            flex: 1; padding: 12px; background: #667eea; color: white;
            border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: .3s;
        }
        .form-actions button:hover { background: #5568d3; }
        .opciones-list { margin-top: 10px; }
        .opcion-row { display: flex; gap: 8px; align-items: center; margin-bottom: 8px; }
        .opcion-row input { flex: 1; }
        .btn-remove-opcion {
            background: none; border: 1px solid #e74c3c; color: #e74c3c;
            border-radius: 4px; padding: 5px 10px; cursor: pointer; font-size: 13px; transition: .2s;
        }
        .btn-remove-opcion:hover { background: #e74c3c; color: white; }
        .btn-add-opcion {
            background: none; border: 1px dashed #667eea; color: #667eea;
            border-radius: 6px; padding: 7px 14px; cursor: pointer; font-size: 13px; margin-top: 4px;
        }
        .btn-add-opcion:hover { background: rgba(102,126,234,.06); }
        .btn-add-opcion:disabled { opacity: .45; cursor: not-allowed; }
        .opciones-counter { font-size: 11px; color: #888; margin-top: 4px; }
        .info-box { background: #e8f4ff; border: 1px solid #b8d8f5; border-radius: 6px; padding: 12px 16px; font-size: 13px; color: #1a5276; margin-bottom: 20px; }
        .preguntas-list { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
        .pregunta-item { padding: 20px; border-bottom: 1px solid #e9ecef; }
        .pregunta-item:last-child { border-bottom: none; }
        .pregunta-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
        .pregunta-info h3 { margin-bottom: 6px; color: #2c3e50; font-size: 15px; }
        .opciones-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
        .opcion-tag { background: #eef2ff; color: #5568d3; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .btn-del { background: #e74c3c; color: white; border: none; border-radius: 4px; padding: 6px 12px; font-size: 12px; cursor: pointer; transition: .2s; }
        .btn-del:hover { background: #c0392b; }
        .empty-state { text-align: center; padding: 40px 20px; color: #6c757d; }
    </style>
</head>
<body>
<div class="container">
    <header>
        <div>
            <h1>❓ Preguntas de la Encuesta</h1>
            <p><?= htmlspecialchars($encuesta['titulo']) ?></p>
        </div>
        <a href="/admin/encuestas/dashboard.php?action=edit&id=<?= $id ?>" class="btn">← Volver</a>
    </header>

    <?php if ($mensaje): ?>
        <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>
    <?php if ($mensaje_error ?? null): ?>
        <div class="alert alert-error"><?= htmlspecialchars($mensaje_error) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="form-container">
        <h2>Agregar Nueva Pregunta</h2>
        <div class="info-box">
            Las preguntas son de <strong>opcion multiple cerrada</strong> (sin texto libre).
            Define entre 2 y 5 opciones para que los usuarios elijan.
        </div>
        <form method="POST" id="form-pregunta" novalidate>
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label for="pregunta">Texto de la Pregunta *</label>
                <textarea id="pregunta" name="pregunta" required
                          placeholder="Ej: Con que frecuencia usas el transporte publico?"></textarea>
            </div>
            <div class="form-group">
                <label>Opciones de Respuesta * <small style="font-weight:400;color:#6c757d;">(2 minimo, 5 maximo)</small></label>
                <div class="opciones-list" id="opciones-list">
                    <div class="opcion-row">
                        <input type="text" name="opciones[]" placeholder="Opcion 1" required>
                        <button type="button" class="btn-remove-opcion" onclick="eliminarOpcion(this)">X</button>
                    </div>
                    <div class="opcion-row">
                        <input type="text" name="opciones[]" placeholder="Opcion 2" required>
                        <button type="button" class="btn-remove-opcion" onclick="eliminarOpcion(this)">X</button>
                    </div>
                </div>
                <button type="button" class="btn-add-opcion" id="btn-add-opcion" onclick="agregarOpcion()">+ Agregar opcion</button>
                <div class="opciones-counter" id="opciones-counter">2/5 opciones</div>
            </div>
            <div class="form-actions">
                <button type="submit">Agregar Pregunta</button>
            </div>
        </form>
    </div>

    <div class="preguntas-list">
        <?php if (empty($encuesta['preguntas'])): ?>
            <div class="empty-state">
                <p style="font-size:16px;margin-bottom:10px;">Sin preguntas aun</p>
                <p>Usa el formulario de arriba para agregar preguntas</p>
            </div>
        <?php else: ?>
            <?php foreach ($encuesta['preguntas'] as $idx => $preg): ?>
                <div class="pregunta-item">
                    <div class="pregunta-header">
                        <div class="pregunta-info">
                            <h3><?= ($idx + 1) ?>. <?= htmlspecialchars($preg['pregunta'] ?? $preg['texto_pregunta'] ?? 'Sin texto') ?></h3>
                            <?php $opts = $preg['opciones_array'] ?? Encuesta::parseOpciones($preg['opciones'] ?? ''); ?>
                            <?php if (!empty($opts)): ?>
                                <div class="opciones-tags">
                                    <?php foreach ($opts as $opt): ?>
                                        <span class="opcion-tag"><?= htmlspecialchars($opt) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <form method="POST" onsubmit="return confirm('Eliminar esta pregunta?')">
                            <input type="hidden" name="action"      value="delete">
                            <input type="hidden" name="pregunta_id" value="<?= (int)$preg['id'] ?>">
                            <button type="submit" class="btn-del">Eliminar</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function agregarOpcion() {
    var list = document.getElementById('opciones-list');
    var rows = list.querySelectorAll('.opcion-row');
    if (rows.length >= 5) return;
    var n = rows.length + 1;
    var div = document.createElement('div');
    div.className = 'opcion-row';
    div.innerHTML = '<input type="text" name="opciones[]" placeholder="Opcion ' + n + '" required>' +
                    '<button type="button" class="btn-remove-opcion" onclick="eliminarOpcion(this)">X</button>';
    list.appendChild(div);
    actualizarContador();
}
function eliminarOpcion(btn) {
    var list = document.getElementById('opciones-list');
    var rows = list.querySelectorAll('.opcion-row');
    if (rows.length <= 2) { alert('Debe haber al menos 2 opciones.'); return; }
    btn.closest('.opcion-row').remove();
    list.querySelectorAll('.opcion-row input').forEach(function(inp, i) { inp.placeholder = 'Opcion ' + (i + 1); });
    actualizarContador();
}
function actualizarContador() {
    var count   = document.getElementById('opciones-list').querySelectorAll('.opcion-row').length;
    document.getElementById('opciones-counter').textContent = count + '/5 opciones';
    document.getElementById('btn-add-opcion').disabled = (count >= 5);
}
document.getElementById('form-pregunta').addEventListener('submit', function(e) {
    var pregTxt = document.getElementById('pregunta').value.trim();
    if (!pregTxt) { e.preventDefault(); alert('El texto de la pregunta no puede estar vacio.'); return; }
    var inputs = document.querySelectorAll('#opciones-list input');
    var vacias = 0;
    inputs.forEach(function(inp) { if (!inp.value.trim()) vacias++; });
    if (vacias > 0) { e.preventDefault(); alert('Las opciones no pueden estar vacias.'); return; }
    if (inputs.length < 2) { e.preventDefault(); alert('Debe haber al menos 2 opciones.'); return; }
});
</script>
</body>
</html>
