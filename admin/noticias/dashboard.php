<?php
session_start();
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../includes/db_helper.php';
require_once __DIR__ . '/../../models/Noticia.php';

setSecurityHeaders();

// Solo administradores
if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header("Location: ../../auth/login.php");
    exit();
}

use App\Models\Noticia;

$db = getDbConnection();
$message = '';
$messageType = '';

// Acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $action = $_POST['action'] ?? '';

    // Crear noticia
    if ($action === 'create') {
        $titulo = $_POST['titulo'] ?? '';
        $contenido = $_POST['contenido'] ?? '';
        $categoria = $_POST['categoria'] ?? 'General';
        $activa = isset($_POST['activa']) ? 1 : 0;

        if ($titulo && $contenido) {
            $data = [
                'titulo' => $titulo,
                'contenido' => $contenido,
                'categoria' => $categoria,
                'user_id' => $_SESSION['user_id'],
                'activa' => $activa
            ];

            if (isset($_FILES['imagen']) && $_FILES['imagen']['size'] > 0) {
                $imagen = Noticia::uploadImage($_FILES['imagen']);
                if ($imagen) {
                    $data['imagen'] = $imagen;
                }
            }

            if (Noticia::create($data)) {
                $message = 'Noticia creada correctamente.';
                $messageType = 'success';
            } else {
                $message = 'Error al crear la noticia.';
                $messageType = 'error';
            }
        } else {
            $message = 'Título y contenido son requeridos.';
            $messageType = 'error';
        }
    }

    // Eliminar noticia
    if ($action === 'delete' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        if (Noticia::delete($id)) {
            $message = 'Noticia eliminada.';
            $messageType = 'success';
        } else {
            $message = 'Error al eliminar noticia.';
            $messageType = 'error';
        }
    }

    // Cambiar estado
    if ($action === 'toggle' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        $noticia = Noticia::getById($id);
        if ($noticia) {
            $result = $noticia['activa']
                ? Noticia::deactivate($id)
                : Noticia::activate($id);
            if ($result) {
                $message = $noticia['activa'] ? 'Noticia desactivada.' : 'Noticia activada.';
                $messageType = 'success';
            } else {
                $message = 'Error al cambiar estado.';
                $messageType = 'error';
            }
        }
    }
}

// Obtener noticias
$noticias = Noticia::getAll();
$stats = Noticia::getStats();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Noticias - Mapita</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; margin: 0; background: #f5f6fa; }

        header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        header h1 { margin: 0; font-size: 1.5em; }
        header a { color: rgba(255,255,255,0.8); text-decoration: none; font-size: 0.9em; transition: color 0.3s; }
        header a:hover { color: white; }

        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }

        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #667eea; }
        .stat-card .number { font-size: 2.5em; font-weight: bold; color: #667eea; }
        .stat-card .label { color: #666; font-size: 0.9em; margin-top: 8px; }

        .section { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 30px; overflow: hidden; }
        .section-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; font-size: 1.2em; font-weight: 600; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 6px; font-family: inherit; }
        .form-group textarea { resize: vertical; min-height: 120px; }

        .form-container { padding: 25px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-container.full { grid-template-columns: 1fr; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        th { background: #f8f9fa; font-weight: 600; color: #333; }
        tr:hover { background: #fafbfc; }

        .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.85em; font-weight: 600; }
        .badge-active { background: #d4edda; color: #155724; }
        .badge-inactive { background: #fff3cd; color: #856404; }

        .btn { padding: 10px 16px; border: none; border-radius: 6px; cursor: pointer; font-size: 0.9em; font-weight: 600; transition: all 0.3s; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3); }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-danger:hover { background: #c0392b; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }

        .inline-form { display: inline; }

        .message { padding: 15px 20px; margin: 0 0 20px 0; border-radius: 6px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal.show { display: flex; align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 30px; border-radius: 12px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .modal-header { margin-bottom: 20px; }
        .modal-header h2 { margin: 0 0 10px 0; }
        .modal-close { float: right; font-size: 28px; cursor: pointer; color: #999; }

        .file-input-label {
            display: block;
            padding: 20px;
            border: 2px dashed #667eea;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: rgba(102, 126, 234, 0.02);
        }

        .file-input-label:hover {
            background: rgba(102, 126, 234, 0.05);
            border-color: #764ba2;
        }

        #imagen { display: none; }

        @media (max-width: 768px) {
            .form-container { grid-template-columns: 1fr; }
            th, td { padding: 10px; font-size: 0.9em; }
        }
    </style>
</head>
<body>

<header>
    <h1>📰 Panel de Noticias</h1>
    <div>
        <span style="margin-right: 20px;">Usuario: <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        <a href="/">🗺️ Mapa</a> |
        <a href="/admin/dashboard.php">🛡️ Admin</a> |
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
            <div class="number"><?php echo $stats['total_activas'] ?? 0; ?></div>
            <div class="label">Noticias Activas</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo (int)($stats['total_inactivas'] ?? 0); ?></div>
            <div class="label">Noticias Inactivas</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo number_format($stats['total_vistas'] ?? 0); ?></div>
            <div class="label">Total de Vistas</div>
        </div>
    </div>

    <!-- Nueva Noticia -->
    <div class="section">
        <div class="section-header">➕ Crear Nueva Noticia</div>
        <form method="post" enctype="multipart/form-data" class="form-container">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="create">

            <div>
                <div class="form-group">
                    <label for="titulo">Título *</label>
                    <input type="text" id="titulo" name="titulo" required placeholder="Título de la noticia">
                </div>

                <div class="form-group">
                    <label for="categoria">Categoría</label>
                    <select id="categoria" name="categoria">
                        <option value="General">General</option>
                        <option value="Negocios">Negocios</option>
                        <option value="Marcas">Marcas</option>
                        <option value="Eventos">Eventos</option>
                        <option value="Tendencias">Tendencias</option>
                        <option value="Educación">Educación</option>
                    </select>
                </div>
            </div>

            <div>
                <div class="form-group">
                    <label for="imagen">Imagen (Portada)</label>
                    <label for="imagen" class="file-input-label">
                        📸 Haz clic para seleccionar una imagen
                    </label>
                    <input type="file" id="imagen" name="imagen" accept="image/*">
                </div>

                <div class="form-group">
                    <label for="activa">
                        <input type="checkbox" id="activa" name="activa" checked>
                        Publicar inmediatamente
                    </label>
                </div>
            </div>

            <div class="form-container full" style="margin-top: 20px;">
                <div class="form-group">
                    <label for="contenido">Contenido *</label>
                    <textarea id="contenido" name="contenido" required placeholder="Contenido de la noticia..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">📤 Crear Noticia</button>
            </div>
        </form>
    </div>

    <!-- Listado de Noticias -->
    <div class="section">
        <div class="section-header">📋 Noticias (<?php echo count($noticias); ?>)</div>
        <?php if (empty($noticias)): ?>
            <div style="padding: 30px; text-align: center; color: #999;">No hay noticias aún</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Categoría</th>
                        <th>Vistas</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($noticias as $n): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars(substr($n['titulo'], 0, 40)); ?></strong>
                                <?php if (strlen($n['titulo']) > 40) echo '...'; ?>
                            </td>
                            <td><?php echo htmlspecialchars($n['categoria']); ?></td>
                            <td><strong><?php echo number_format($n['vistas']); ?></strong></td>
                            <td>
                                <span class="badge <?php echo $n['activa'] ? 'badge-active' : 'badge-inactive'; ?>">
                                    <?php echo $n['activa'] ? '✓ Activa' : '✗ Inactiva'; ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($n['fecha_publicacion'])); ?></td>
                            <td>
                                <form class="inline-form" method="post">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?php echo $n['id']; ?>">
                                    <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8em;">
                                        <?php echo $n['activa'] ? '👁️ Ocultar' : '👁️ Mostrar'; ?>
                                    </button>
                                </form>
                                <form class="inline-form" method="post" onsubmit="return confirm('¿Eliminar esta noticia?')">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $n['id']; ?>">
                                    <button class="btn btn-danger" style="padding: 6px 12px; font-size: 0.8em;">🗑️ Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
