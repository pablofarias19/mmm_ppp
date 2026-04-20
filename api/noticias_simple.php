<?php
/**
 * API Noticias - VERSIÓN SIMPLE CON FALLBACK
 * Funciona incluso si la tabla no existe
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=3600');

session_start();

require_once __DIR__ . '/../core/Database.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);

// Datos por defecto (fallback)
$noticiasDefault = [
    [
        'id' => 1,
        'titulo' => '📰 Sistema de Noticias Activado',
        'contenido' => 'El panel de administración está activo. Accede a /admin/ para crear noticias.',
        'categoria' => 'General',
        'imagen' => null,
        'activa' => 1,
        'fecha_publicacion' => date('Y-m-d H:i:s'),
        'vistas' => 0
    ]
];

try {
    // ============ GET ACTIONS ============
    if ($method === 'GET') {
        try {
            $db = \Core\Database::getInstance()->getConnection();

            // Intentar obtener de BD
            if ($id > 0) {
                $stmt = $db->prepare("SELECT * FROM noticias WHERE id = ? AND activa = 1");
                $stmt->execute([$id]);
                $noticia = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($noticia) {
                    respond_success($noticia, "Noticia obtenida");
                    exit;
                }
            }

            if ($action === 'recent') {
                $limit = (int)($_GET['limit'] ?? 10);
                $stmt = $db->prepare("SELECT * FROM noticias WHERE activa = 1 ORDER BY fecha_publicacion DESC LIMIT ?");
                $stmt->bindValue(1, $limit, PDO::PARAM_INT);
                $stmt->execute();
                $noticias = $stmt->fetchAll(PDO::FETCH_ASSOC);
                respond_success($noticias, "Noticias recientes obtenidas");
                exit;
            }

            if ($action === 'categoria') {
                $categoria = $_GET['cat'] ?? 'General';
                $stmt = $db->prepare("SELECT * FROM noticias WHERE categoria = ? AND activa = 1");
                $stmt->execute([$categoria]);
                $noticias = $stmt->fetchAll(PDO::FETCH_ASSOC);
                respond_success($noticias, "Noticias de categoría obtenidas");
                exit;
            }

            // GET todas las noticias activas (default)
            $stmt = $db->prepare("SELECT * FROM noticias WHERE activa = 1 ORDER BY fecha_publicacion DESC");
            $stmt->execute();
            $noticias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            respond_success($noticias, "Noticias obtenidas");
            exit;

        } catch (PDOException $e) {
            // Si BD falla, usar datos por defecto
            error_log("BD noticias falla, usando fallback: " . $e->getMessage());
            respond_success($noticiasDefault, "Noticias (modo fallback)");
            exit;
        }
    }

    // ============ POST ACTIONS ============
    if ($method === 'POST') {
        // Verificar admin
        if (!in_array($action, ['view'])) {
            if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
                respond_error("Solo administradores pueden realizar esta acción", 403);
            }
        }

        try {
            $db = \Core\Database::getInstance()->getConnection();

            if ($action === 'create') {
                $titulo = $_POST['titulo'] ?? '';
                $contenido = $_POST['contenido'] ?? '';
                $categoria = $_POST['categoria'] ?? 'General';
                $activa = (bool)($_POST['activa'] ?? 1);

                if (!$titulo || !$contenido) {
                    respond_error("Título y contenido son requeridos", 400);
                }

                $stmt = $db->prepare("
                    INSERT INTO noticias (titulo, contenido, categoria, activa, user_id, fecha_publicacion)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $result = $stmt->execute([
                    $titulo,
                    $contenido,
                    $categoria,
                    $activa ? 1 : 0,
                    $_SESSION['user_id'] ?? 1
                ]);

                if ($result) {
                    respond_success(['id' => $db->lastInsertId()], "Noticia creada correctamente");
                } else {
                    respond_error("Error al crear noticia", 500);
                }
                exit;
            }

            if ($action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) respond_error("ID inválido", 400);

                $titulo = $_POST['titulo'] ?? null;
                $contenido = $_POST['contenido'] ?? null;
                $categoria = $_POST['categoria'] ?? null;
                $activa = isset($_POST['activa']) ? (bool)$_POST['activa'] : null;

                $updates = [];
                $values = [];

                if ($titulo !== null) { $updates[] = "titulo = ?"; $values[] = $titulo; }
                if ($contenido !== null) { $updates[] = "contenido = ?"; $values[] = $contenido; }
                if ($categoria !== null) { $updates[] = "categoria = ?"; $values[] = $categoria; }
                if ($activa !== null) { $updates[] = "activa = ?"; $values[] = $activa ? 1 : 0; }

                if (empty($updates)) respond_error("No hay datos para actualizar", 400);

                $values[] = $id;
                $sql = "UPDATE noticias SET " . implode(", ", $updates) . " WHERE id = ?";
                $stmt = $db->prepare($sql);
                $result = $stmt->execute($values);

                if ($result) {
                    respond_success([], "Noticia actualizada");
                } else {
                    respond_error("Error al actualizar", 500);
                }
                exit;
            }

            if ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) respond_error("ID inválido", 400);

                $stmt = $db->prepare("DELETE FROM noticias WHERE id = ?");
                if ($stmt->execute([$id])) {
                    respond_success([], "Noticia eliminada");
                } else {
                    respond_error("Error al eliminar", 500);
                }
                exit;
            }

            if ($action === 'view') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) respond_error("ID inválido", 400);

                $stmt = $db->prepare("UPDATE noticias SET vistas = vistas + 1 WHERE id = ?");
                $stmt->execute([$id]);
                respond_success([], "Vista registrada");
                exit;
            }
        } catch (PDOException $e) {
            error_log("Error BD noticias POST: " . $e->getMessage());
            respond_error("Error del servidor: " . $e->getMessage(), 500);
            exit;
        }
    }

    respond_error("Acción no válida", 405);

} catch (Exception $e) {
    error_log("Error general en noticias: " . $e->getMessage());
    respond_error("Error: " . $e->getMessage(), 500);
}
?>
