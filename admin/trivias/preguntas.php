<?php
/**
 * Admin - Gestionar preguntas de una trivia
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: /login');
    exit;
}

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../models/Trivia.php';

use App\Models\Trivia;

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: /admin/trivias/dashboard.php');
    exit;
}

$trivia = Trivia::getById($id);
if (!$trivia) {
    header('Location: /admin/trivias/dashboard.php');
    exit;
}

// Procesar agregar pregunta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pregunta = $_POST['pregunta'] ?? null;
    $opcion_a = $_POST['opcion_a'] ?? null;
    $opcion_b = $_POST['opcion_b'] ?? null;
    $opcion_c = $_POST['opcion_c'] ?? null;
    $opcion_d = $_POST['opcion_d'] ?? null;
    $respuesta_correcta = $_POST['respuesta_correcta'] ?? null;
    $puntos = (int)($_POST['puntos'] ?? 10);

    if (!$pregunta || !$opcion_a || !$opcion_b || !$opcion_c || !$opcion_d || !$respuesta_correcta) {
        $error = "Todos los campos son requeridos";
    } else {
        try {
            $db = \Core\Database::getInstance()->getConnection();

            $sql = "INSERT INTO trivia_preguntas
                    (trivia_id, pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, puntos)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $db->prepare($sql);
            $result = $stmt->execute([$id, $pregunta, $opcion_a, $opcion_b, $opcion_c, $opcion_d, $respuesta_correcta, $puntos]);

            if ($result) {
                $_SESSION['mensaje'] = "Pregunta agregada exitosamente";
            } else {
                $error = "Error al agregar la pregunta";
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }

    header("Location: /admin/trivias/preguntas.php?id=$id");
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
    <title>Preguntas - <?= htmlspecialchars($trivia['titulo']) ?></title>
    <link rel="stylesheet" href="/css/map-styles.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f6fa;
            color: #2c3e50;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        header {
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
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
        .form-group textarea:focus {
            outline: none;
            border-color: #9b59b6;
            box-shadow: 0 0 0 3px rgba(155, 89, 182, 0.1);
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        .opciones-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .form-actions {
            display: flex;
            gap: 10px;
        }
        .form-actions button {
            flex: 1;
            padding: 12px;
            background: #9b59b6;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        .form-actions button:hover {
            background: #8e44ad;
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
            margin-bottom: 10px;
            color: #2c3e50;
        }
        .pregunta-opciones {
            font-size: 13px;
            color: #6c757d;
            line-height: 1.6;
            margin-bottom: 10px;
        }
        .pregunta-opciones strong {
            color: #9b59b6;
        }
        .pregunta-actions {
            display: flex;
            gap: 10px;
        }
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
            background: #9b59b6;
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
        .badge {
            display: inline-block;
            background: #f8f9fa;
            color: #6c757d;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div>
                <h1>❓ Preguntas de Trivia</h1>
                <p><?= htmlspecialchars($trivia['titulo']) ?></p>
            </div>
            <a href="/admin/trivias/dashboard.php" class="btn">← Volver</a>
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
                    <label>Opciones de Respuesta *</label>
                    <div class="opciones-grid">
                        <div class="form-group" style="margin: 0;">
                            <label for="opcion_a">Opción A</label>
                            <input type="text" id="opcion_a" name="opcion_a" required placeholder="Respuesta A">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label for="opcion_b">Opción B</label>
                            <input type="text" id="opcion_b" name="opcion_b" required placeholder="Respuesta B">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label for="opcion_c">Opción C</label>
                            <input type="text" id="opcion_c" name="opcion_c" required placeholder="Respuesta C">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label for="opcion_d">Opción D</label>
                            <input type="text" id="opcion_d" name="opcion_d" required placeholder="Respuesta D">
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="respuesta_correcta">Respuesta Correcta *</label>
                        <select id="respuesta_correcta" name="respuesta_correcta" required>
                            <option value="">Selecciona la respuesta correcta</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="puntos">Puntos por Acierto</label>
                        <input type="number" id="puntos" name="puntos" min="1" max="100" value="10">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit">Agregar Pregunta</button>
                </div>
            </form>
        </div>

        <div class="preguntas-list">
            <?php if (empty($trivia['preguntas'])): ?>
                <div class="empty-state">
                    <p style="font-size: 16px; margin-bottom: 10px;">📭 Sin preguntas aún</p>
                    <p>Agrega preguntas usando el formulario arriba</p>
                </div>
            <?php else: ?>
                <?php foreach ($trivia['preguntas'] as $idx => $preg): ?>
                    <div class="pregunta-item">
                        <div class="pregunta-info">
                            <h3><?= ($idx + 1) ?>. <?= htmlspecialchars($preg['pregunta']) ?></h3>
                            <div class="pregunta-opciones">
                                <p>A) <?= htmlspecialchars($preg['opcion_a']) ?> <?php if ($preg['respuesta_correcta'] === 'A') echo '<span style="color: #27ae60;"> ✓ Correcta</span>'; ?></p>
                                <p>B) <?= htmlspecialchars($preg['opcion_b']) ?> <?php if ($preg['respuesta_correcta'] === 'B') echo '<span style="color: #27ae60;"> ✓ Correcta</span>'; ?></p>
                                <p>C) <?= htmlspecialchars($preg['opcion_c']) ?> <?php if ($preg['respuesta_correcta'] === 'C') echo '<span style="color: #27ae60;"> ✓ Correcta</span>'; ?></p>
                                <p>D) <?= htmlspecialchars($preg['opcion_d']) ?> <?php if ($preg['respuesta_correcta'] === 'D') echo '<span style="color: #27ae60;"> ✓ Correcta</span>'; ?></p>
                            </div>
                            <div style="margin-top: 10px;">
                                <span class="badge">⭐ <?= $preg['puntos'] ?> puntos</span>
                            </div>
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
