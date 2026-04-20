<?php
/**
 * Admin Dashboard - Trivias
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

$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);

$trivia = null;
$trivias = [];

if ($action === 'edit' && $id > 0) {
    $trivia = Trivia::getById($id);
}

if ($action === 'list' || !in_array($action, ['edit', 'create', 'stats'])) {
    $trivias = Trivia::getAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'] ?? null;
    $descripcion = $_POST['descripcion'] ?? null;
    $dificultad = $_POST['dificultad'] ?? 'medio';
    $tiempo_limite = (int)($_POST['tiempo_limite'] ?? 30);

    if (!$titulo) {
        $error = "El título es requerido";
    } else {
        if ($action === 'create') {
            $result = Trivia::create([
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'dificultad' => $dificultad,
                'tiempo_limite' => $tiempo_limite
            ]);

            if ($result) {
                $_SESSION['mensaje'] = "Trivia creada exitosamente";
                header("Location: /admin/trivias/dashboard.php?action=edit&id=$result");
                exit;
            } else {
                $error = "Error al crear la trivia";
            }
        } elseif ($action === 'edit' && $id > 0) {
            $result = Trivia::update($id, [
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'dificultad' => $dificultad,
                'tiempo_limite' => $tiempo_limite
            ]);

            if ($result) {
                $_SESSION['mensaje'] = "Trivia actualizada exitosamente";
            } else {
                $error = "Error al actualizar la trivia";
            }
        }
    }

    header("Location: /admin/trivias/dashboard.php");
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
    <title>Admin - Trivias</title>
    <link rel="stylesheet" href="/css/map-styles.css">
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
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #d0d5dd;
        }
        .tabs a {
            padding: 12px 20px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            color: #6c757d;
            font-size: 14px;
            font-weight: 500;
            transition: 0.3s;
            text-decoration: none;
        }
        .tabs a.active {
            color: #9b59b6;
            border-bottom-color: #9b59b6;
        }
        .tabs a:hover {
            color: #9b59b6;
        }

        .trivias-table {
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
        .badge-facil {
            background: #d4edda;
            color: #155724;
        }
        .badge-medio {
            background: #fff3cd;
            color: #856404;
        }
        .badge-dificil {
            background: #f8d7da;
            color: #721c24;
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
            background: #9b59b6;
            color: white;
        }
        .btn-primary:hover {
            background: #8e44ad;
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
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #9b59b6;
            box-shadow: 0 0 0 3px rgba(155, 89, 182, 0.1);
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
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
    </style>
</head>
<body>
    <div class="admin-container">
        <header>
            <h1>🎯 Gestión de Trivias</h1>
            <a href="/">← Volver al Mapa</a>
        </header>

        <?php if ($mensaje): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="tabs">
            <a href="?action=list" class="<?= $action === 'list' ? 'active' : '' ?>">📋 Listado</a>
            <a href="?action=create" class="<?= $action === 'create' ? 'active' : '' ?>">➕ Nueva Trivia</a>
        </div>

        <?php if ($action === 'list'): ?>
            <div class="trivias-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Dificultad</th>
                            <th>Preguntas</th>
                            <th>Tiempo Límite</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trivias as $tri): ?>
                            <tr>
                                <td>#<?= $tri['id'] ?></td>
                                <td><?= htmlspecialchars($tri['titulo']) ?></td>
                                <td>
                                    <span class="badge badge-<?= $tri['dificultad'] ?>">
                                        <?= ucfirst($tri['dificultad']) ?>
                                    </span>
                                </td>
                                <td>—</td>
                                <td><?= $tri['tiempo_limite'] ?> seg</td>
                                <td>
                                    <span class="badge" style="background: #d4edda; color: #155724;">
                                        ✓ Activo
                                    </span>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="?action=edit&id=<?= $tri['id'] ?>" class="btn btn-primary btn-small">Editar</a>
                                        <a href="/admin/trivias/preguntas.php?id=<?= $tri['id'] ?>" class="btn btn-secondary btn-small">❓ Preguntas</a>
                                        <a href="?action=stats&id=<?= $tri['id'] ?>" class="btn btn-secondary btn-small">📊 Stats</a>
                                        <a href="?action=delete&id=<?= $tri['id'] ?>" class="btn btn-danger btn-small" onclick="return confirm('¿Desactivar trivia?')">Desactivar</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($action === 'create' || ($action === 'edit' && $trivia)): ?>
            <div class="form-container">
                <h2><?= $action === 'create' ? '➕ Nueva Trivia' : '✏️ Editar Trivia' ?></h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="titulo">Título *</label>
                        <input type="text" id="titulo" name="titulo" required
                               value="<?= $trivia ? htmlspecialchars($trivia['titulo']) : '' ?>"
                               placeholder="Ej: ¿Cuánto sabes de tecnología?">
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion"
                                  placeholder="Describe la trivia..."><?= $trivia ? htmlspecialchars($trivia['descripcion']) : '' ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="dificultad">Dificultad</label>
                            <select id="dificultad" name="dificultad">
                                <option value="facil" <?= $trivia && $trivia['dificultad'] === 'facil' ? 'selected' : '' ?>>⭐ Fácil</option>
                                <option value="medio" <?= !$trivia || $trivia['dificultad'] === 'medio' ? 'selected' : '' ?>>⭐⭐ Medio</option>
                                <option value="dificil" <?= $trivia && $trivia['dificultad'] === 'dificil' ? 'selected' : '' ?>>⭐⭐⭐ Difícil</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="tiempo_limite">Tiempo Límite (segundos)</label>
                            <input type="number" id="tiempo_limite" name="tiempo_limite" min="10" max="300"
                                   value="<?= $trivia ? $trivia['tiempo_limite'] : '30' ?>">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">💾 Guardar Trivia</button>
                        <a href="?action=list" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
