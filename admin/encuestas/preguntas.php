<?php
/**
 * Admin - Gestionar preguntas de una encuesta
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

// Procesar agregar pregunta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pregunta = $_POST['pregunta'] ?? null;
    $tipo = $_POST['tipo'] ?? 'opcion_multiple';
    $opciones = $_POST['opciones'] ?? [];

    if (!$pregunta) {
        $error = "La pregunta es requerida";
    } else {
        try {
            $db = \Core\Database::getInstance()->getConnection();

            // Insertar pregunta
            $sql = "INSERT INTO preguntas_encuesta (encuesta_id, pregunta, tipo)
                    VALUES (?, ?, ?)";

            $stmt = $db->prepare($sql);
            $result = $stmt->execute([$id, $pregunta, $tipo]);

            if ($result) {
                $_SESSION['mensaje'] = "Pregunta agregada exitosamente";
            } else {
                $error = "Error al agregar la pregunta";
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }

    header("Location: /admin/encuestas/preguntas.php?id=$id");
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
    <title>Preguntas - <?= htmlspecialchars($encuesta['titulo']) ?></title>
    <link rel="stylesheet" href="/css/map-styles.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f6fa;
            color: #2c3e50;
        }
        .container {
            max-width: 900px;
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
        header p { font-size: 14px; opacity: 0.9; margin-top: 5px; }
        .btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn:hover {
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
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }

        .form-container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
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
            min-height: 80px;
        }
        .form-actions {
            display: flex;
            gap: 10px;
        }
        .form-actions button {
            flex: 1;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        .form-actions button:hover {
            background: #5568d3;
        }

        .preguntas-list {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .pregunta-item {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .pregunta-item:last-child {
            border-bottom: none;
        }
        .pregunta-info h3 {
            margin-bottom: 5px;
            color: #2c3e50;
        }
        .pregunta-info p {
            font-size: 13px;
            color: #6c757d;
        }
        .pregunta-actions {
            display: flex;
            gap: 10px;
        }
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-small.danger {
            background: #e74c3c;
        }
        .btn-small:hover {
            opacity: 0.9;
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        .empty-state p {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div>
                <h1>❓ Preguntas de Encuesta</h1>
                <p><?= htmlspecialchars($encuesta['titulo']) ?></p>
            </div>
            <a href="/admin/encuestas/dashboard.php" class="btn">← Volver</a>
        </header>

        <?php if ($mensaje): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="form-container">
            <h2>➕ Agregar Nueva Pregunta</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="pregunta">Pregunta *</label>
                    <textarea id="pregunta" name="pregunta" required
                              placeholder="Escribe la pregunta..."></textarea>
                </div>

                <div class="form-group">
                    <label for="tipo">Tipo de Respuesta</label>
                    <select id="tipo" name="tipo">
                        <option value="opcion_multiple">Opción Múltiple</option>
                        <option value="si_no">Sí/No</option>
                        <option value="texto_libre">Texto Libre</option>
                        <option value="escala">Escala (1-5)</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit">Agregar Pregunta</button>
                </div>
            </form>
        </div>

        <div class="preguntas-list">
            <?php if (empty($encuesta['preguntas'])): ?>
                <div class="empty-state">
                    <p style="font-size: 16px; margin-bottom: 10px;">📭 Sin preguntas aún</p>
                    <p>Agrega preguntas usando el formulario arriba</p>
                </div>
            <?php else: ?>
                <?php foreach ($encuesta['preguntas'] as $idx => $preg): ?>
                    <div class="pregunta-item">
                        <div class="pregunta-info">
                            <h3><?= ($idx + 1) ?>. <?= htmlspecialchars($preg['pregunta'] ?? 'Sin título') ?></h3>
                            <p>Tipo: <strong><?= ucfirst(str_replace('_', ' ', $preg['tipo'])) ?></strong></p>
                        </div>
                        <div class="pregunta-actions">
                            <form method="POST" style="display: inline;" onsubmit="return confirm('¿Eliminar esta pregunta?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="pregunta_id" value="<?= $preg['id'] ?>">
                                <button type="submit" class="btn-small danger">🗑️ Eliminar</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
