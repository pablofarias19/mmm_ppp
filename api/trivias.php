<?php
/**
 * API Trivias - VERSIÓN ROBUSTA SIN DEPENDENCIA DE MODELO
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

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';

// Datos fallback
$triviasDefault = [
    [
        'id' => 1,
        'titulo' => '🎯 Sistema de Trivias Activado',
        'descripcion' => 'El panel de administración está activo. Accede a /admin/ para crear trivias.',
        'dificultad' => 'medio',
        'tiempo_limite' => 30,
        'activa' => 1
    ]
];

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

$dbAvailable = true;
$db = null;

try {
    $db = \Core\Database::getInstance()->getConnection();
} catch (Exception $e) {
    $dbAvailable = false;
    error_log("BD no disponible para trivias: " . $e->getMessage());
}

// ============ GET ACTIONS ============
if ($method === 'GET') {
    if ($dbAvailable && $db) {
        try {
            $id = (int)($_GET['id'] ?? 0);

            if ($id > 0) {
                $stmt = $db->prepare("SELECT * FROM trivias WHERE id = ? AND activa = 1");
                $stmt->execute([$id]);
                $trivia = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($trivia) {
                    respond_success($trivia, "Trivia obtenida");
                    exit;
                }
            }

            if ($action === 'ranking') {
                $limit = (int)($_GET['limit'] ?? 50);
                $stmt = $db->prepare("SELECT * FROM trivia_scores ORDER BY puntos DESC LIMIT ?");
                $stmt->bindValue(1, $limit, PDO::PARAM_INT);
                $stmt->execute();
                $ranking = $stmt->fetchAll(PDO::FETCH_ASSOC);
                respond_success($ranking, "Ranking obtenido");
                exit;
            }

            // GET todas las trivias activas
            $stmt = $db->prepare("SELECT * FROM trivias WHERE activa = 1 ORDER BY id DESC");
            $stmt->execute();
            $trivias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            respond_success($trivias, "Trivias obtenidas");
            exit;

        } catch (PDOException $e) {
            error_log("Error BD trivias: " . $e->getMessage());
            respond_success($triviasDefault, "Trivias (modo fallback - tabla no existe)");
            exit;
        }
    } else {
        respond_success($triviasDefault, "Trivias (fallback - BD no disponible)");
        exit;
    }
}

// ============ POST ACTIONS ============
if ($method === 'POST') {
    if (!isAdmin()) {
        respond_error("Solo administradores pueden realizar esta acción", 403);
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
            $titulo        = $input['titulo']       ?? '';
            $descripcion   = $input['descripcion']  ?? '';
            $dificultad    = $input['dificultad']   ?? 'medio';
            $tiempo_limite = (int)($input['tiempo_limite'] ?? 30);
            $lat           = isset($input['lat']) && $input['lat'] !== '' ? (float)$input['lat'] : null;
            $lng           = isset($input['lng']) && $input['lng'] !== '' ? (float)$input['lng'] : null;
            $ubicacion     = $input['ubicacion'] ?? null;

            if (!$titulo) {
                respond_error("Título requerido", 400);
            }

            if (!in_array($dificultad, ['facil', 'medio', 'dificil'])) {
                $dificultad = 'medio';
            }

            $stmt = $db->prepare("
                INSERT INTO trivias (titulo, descripcion, dificultad, tiempo_limite, activa, lat, lng, ubicacion, created_at)
                VALUES (?, ?, ?, ?, 1, ?, ?, ?, NOW())
            ");
            $result = $stmt->execute([$titulo, $descripcion, $dificultad, $tiempo_limite, $lat, $lng, $ubicacion]);

            if ($result) {
                respond_success(['id' => $db->lastInsertId()], "Trivia creada correctamente");
            } else {
                respond_error("Error al crear trivia", 500);
            }
            exit;
        }

        if ($action === 'delete') {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) respond_error("ID inválido", 400);

            $stmt = $db->prepare("DELETE FROM trivias WHERE id = ?");
            if ($stmt->execute([$id])) {
                respond_success([], "Trivia eliminada");
            } else {
                respond_error("Error al eliminar", 500);
            }
            exit;
        }

        if ($action === 'update') {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) respond_error("ID inválido", 400);

            $updates = [];
            $values = [];

            if (isset($input['titulo']))       { $updates[] = "titulo = ?";       $values[] = $input['titulo']; }
            if (isset($input['descripcion']))  { $updates[] = "descripcion = ?";  $values[] = $input['descripcion']; }
            if (isset($input['dificultad']))   { $updates[] = "dificultad = ?";   $values[] = $input['dificultad']; }
            if (isset($input['tiempo_limite'])){ $updates[] = "tiempo_limite = ?";$values[] = (int)$input['tiempo_limite']; }
            if (isset($input['activa']))       { $updates[] = "activa = ?";       $values[] = $input['activa'] ? 1 : 0; }
            if (array_key_exists('lat', $input)) { $updates[] = "lat = ?"; $values[] = $input['lat'] !== '' ? (float)$input['lat'] : null; }
            if (array_key_exists('lng', $input)) { $updates[] = "lng = ?"; $values[] = $input['lng'] !== '' ? (float)$input['lng'] : null; }
            if (isset($input['ubicacion']))    { $updates[] = "ubicacion = ?";    $values[] = $input['ubicacion']; }

            if (empty($updates)) respond_error("No hay datos para actualizar", 400);

            $values[] = $id;
            $sql = "UPDATE trivias SET " . implode(", ", $updates) . " WHERE id = ?";
            $stmt = $db->prepare($sql);
            if ($stmt->execute($values)) {
                respond_success([], "Trivia actualizada");
            } else {
                respond_error("Error al actualizar", 500);
            }
            exit;
        }

        if ($action === 'score') {
            if (!isset($_SESSION['user_id'])) {
                respond_error("Usuario no logueado", 401);
            }

            $trivia_id = (int)($input['trivia_id'] ?? 0);
            $puntos = (int)($input['puntos'] ?? 0);

            if ($trivia_id <= 0) respond_error("ID inválido", 400);

            $stmt = $db->prepare("
                INSERT INTO trivia_scores (trivia_id, user_id, puntos, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            if ($stmt->execute([$trivia_id, $_SESSION['user_id'], $puntos])) {
                respond_success([], "Puntuación registrada");
            } else {
                respond_error("Error al registrar puntuación", 500);
            }
            exit;
        }

    } catch (PDOException $e) {
        error_log("Error POST trivias: " . $e->getMessage());
        respond_error("Error al procesar: " . $e->getMessage(), 500);
    }
}

respond_error("Acción no válida", 405);
