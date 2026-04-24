<?php
/**
 * API Noticias - VERSIÓN ROBUSTA SIN DEPENDENCIA DE MODELO
 * Funciona incluso si tabla no existe
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=3600');
session_start();

// Helper functions FIRST
function respond_success($data, $message = "OK") {
    echo json_encode(['success' => true, 'data' => $data, 'message' => $message]);
    exit;
}

function respond_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

/**
 * Sanitize rich HTML content: allow only safe subset.
 * Allowed: <b>, <strong>, <u>, <em>, <i>, <span style="color:...">
 * Strips everything else and limits to 500 plain-text characters.
 */
function sanitizeNoticiaContenido(string $html): string {
    // Strip dangerous tags/attributes; keep safe subset
    // Remove all tags except allowed
    $allowed = '<b><strong><u><em><i><span><br>';
    $clean = strip_tags($html, $allowed);

    // Allow only safe style on <span>: only color/font-weight/text-decoration
    $clean = preg_replace_callback(
        '/<span([^>]*)>/i',
        function ($m) {
            $attrs = $m[1];
            $style = '';
            if (preg_match('/style\s*=\s*["\']([^"\']*)["\']/', $attrs, $sm)) {
                // Only allow color, font-weight, text-decoration
                $allowed_props = [];
                foreach (explode(';', $sm[1]) as $prop) {
                    $prop = trim($prop);
                    if (preg_match('/^(color|font-weight|text-decoration)\s*:/i', $prop)) {
                        // Validate color value: no url(), no expression
                        if (strpos(strtolower($prop), 'url') === false &&
                            strpos(strtolower($prop), 'expression') === false &&
                            strpos(strtolower($prop), 'javascript') === false) {
                            $allowed_props[] = $prop;
                        }
                    }
                }
                if ($allowed_props) {
                    $style = ' style="' . htmlspecialchars(implode(';', $allowed_props), ENT_QUOTES) . '"';
                }
            }
            return '<span' . $style . '>';
        },
        $clean
    );

    // Enforce 500 plain-text character limit (strip tags for counting)
    $plainText = html_entity_decode(strip_tags($clean), ENT_QUOTES, 'UTF-8');
    if (mb_strlen($plainText) > 500) {
        // Truncate plain text and rebuild (simple approach: strip and re-wrap)
        $clean = htmlspecialchars(mb_substr($plainText, 0, 500), ENT_QUOTES, 'UTF-8');
    }

    return $clean;
}

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';

// Datos fallback
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

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);

$dbAvailable = true;
$db = null;

try {
    $db = \Core\Database::getInstance()->getConnection();
} catch (Exception $e) {
    $dbAvailable = false;
    error_log("BD no disponible para noticias: " . $e->getMessage());
}

// ============ GET ACTIONS ============
if ($method === 'GET') {
    if ($dbAvailable && $db) {
        try {
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

            // GET todas las noticias activas
            $stmt = $db->prepare("SELECT * FROM noticias WHERE activa = 1 ORDER BY fecha_publicacion DESC");
            $stmt->execute();
            $noticias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            respond_success($noticias, "Noticias obtenidas");
            exit;

        } catch (PDOException $e) {
            error_log("Error BD noticias: " . $e->getMessage());
            respond_success($noticiasDefault, "Noticias (modo fallback - tabla no existe)");
            exit;
        }
    } else {
        respond_success($noticiasDefault, "Noticias (fallback - BD no disponible)");
        exit;
    }
}

// ============ POST ACTIONS ============
if ($method === 'POST') {
    if (!in_array($action, ['view'])) {
        if (!isAdmin()) {
            respond_error("Solo administradores pueden realizar esta acción", 403);
        }
    }

    if (!$dbAvailable || !$db) {
        respond_error("Base de datos no disponible", 500);
    }

    // Obtener datos de JSON o POST
    $input = null;
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
    } else {
        $input = $_POST;
    }

    try {
        if ($action === 'create') {
            $titulo        = trim($input['titulo']    ?? '');
            $contenido     = sanitizeNoticiaContenido($input['contenido'] ?? '');
            $categoria     = $input['categoria']     ?? 'General';
            $link          = $input['link']          ?? null;
            $resumen_popup = $input['resumen_popup'] ?? null;
            $tags          = $input['tags']          ?? null;
            $activa        = (bool)($input['activa'] ?? 1);
            $lat           = isset($input['lat']) && $input['lat'] !== '' ? (float)$input['lat'] : null;
            $lng           = isset($input['lng']) && $input['lng'] !== '' ? (float)$input['lng'] : null;
            $ubicacion     = $input['ubicacion'] ?? null;

            // Validate plain-text length
            $plainLen = mb_strlen(html_entity_decode(strip_tags($contenido), ENT_QUOTES, 'UTF-8'));
            if (!$titulo || !$contenido) {
                respond_error("Título y contenido son requeridos", 400);
            }
            if ($plainLen > 500) {
                respond_error("El contenido supera el límite de 500 caracteres de texto plano", 400);
            }

            // Validate link URL if provided
            if ($link !== null && $link !== '' && !filter_var($link, FILTER_VALIDATE_URL)) {
                respond_error("El link no es una URL válida", 400);
            }

            // Use INSERT with IF EXISTS column check pattern via try/catch
            try {
                $stmt = $db->prepare("
                    INSERT INTO noticias (titulo, contenido, categoria, link, resumen_popup, tags, activa, user_id, lat, lng, ubicacion, fecha_publicacion)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $result = $stmt->execute([
                    $titulo, $contenido, $categoria, $link ?: null, $resumen_popup ?: null, $tags ?: null,
                    $activa ? 1 : 0, $_SESSION['user_id'] ?? 1,
                    $lat, $lng, $ubicacion
                ]);
            } catch (\PDOException $e2) {
                // Fallback: columns may not exist yet (migration not run)
                $stmt = $db->prepare("
                    INSERT INTO noticias (titulo, contenido, categoria, activa, user_id, lat, lng, ubicacion, fecha_publicacion)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $result = $stmt->execute([
                    $titulo, $contenido, $categoria,
                    $activa ? 1 : 0, $_SESSION['user_id'] ?? 1,
                    $lat, $lng, $ubicacion
                ]);
            }

            if ($result) {
                respond_success(['id' => $db->lastInsertId()], "Noticia creada correctamente");
            } else {
                respond_error("Error al crear noticia", 500);
            }
            exit;
        }

        if ($action === 'update') {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) respond_error("ID inválido", 400);

            $updates = [];
            $values = [];

            if (isset($input['titulo']))    { $updates[] = "titulo = ?";    $values[] = trim($input['titulo']); }
            if (isset($input['contenido'])) {
                $safe = sanitizeNoticiaContenido($input['contenido']);
                $plainLen = mb_strlen(html_entity_decode(strip_tags($safe), ENT_QUOTES, 'UTF-8'));
                if ($plainLen > 500) respond_error("El contenido supera el límite de 500 caracteres de texto plano", 400);
                $updates[] = "contenido = ?"; $values[] = $safe;
            }
            if (isset($input['categoria'])) { $updates[] = "categoria = ?"; $values[] = $input['categoria']; }
            if (array_key_exists('link', $input)) {
                $lv = $input['link'];
                if ($lv !== '' && $lv !== null && !filter_var($lv, FILTER_VALIDATE_URL)) respond_error("El link no es una URL válida", 400);
                $updates[] = "link = ?"; $values[] = $lv ?: null;
            }
            if (array_key_exists('resumen_popup', $input)) { $updates[] = "resumen_popup = ?"; $values[] = $input['resumen_popup'] ?: null; }
            if (array_key_exists('tags', $input))          { $updates[] = "tags = ?";          $values[] = $input['tags'] ?: null; }
            if (isset($input['activa']))    { $updates[] = "activa = ?";    $values[] = $input['activa'] ? 1 : 0; }
            if (array_key_exists('lat', $input)) { $updates[] = "lat = ?"; $values[] = $input['lat'] !== '' ? (float)$input['lat'] : null; }
            if (array_key_exists('lng', $input)) { $updates[] = "lng = ?"; $values[] = $input['lng'] !== '' ? (float)$input['lng'] : null; }
            if (isset($input['ubicacion'])) { $updates[] = "ubicacion = ?"; $values[] = $input['ubicacion']; }

            if (empty($updates)) respond_error("No hay datos para actualizar", 400);

            $values[] = $id;
            $sql = "UPDATE noticias SET " . implode(", ", $updates) . " WHERE id = ?";
            $stmt = $db->prepare($sql);
            if ($stmt->execute($values)) {
                respond_success([], "Noticia actualizada");
            } else {
                respond_error("Error al actualizar", 500);
            }
            exit;
        }

        if ($action === 'delete') {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
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
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) respond_error("ID inválido", 400);

            $stmt = $db->prepare("UPDATE noticias SET vistas = vistas + 1 WHERE id = ?");
            $stmt->execute([$id]);
            respond_success([], "Vista registrada");
            exit;
        }

    } catch (PDOException $e) {
        error_log("Error POST noticias: " . $e->getMessage());
        respond_error("Error al procesar: " . $e->getMessage(), 500);
    }
}

respond_error("Acción no válida", 405);
