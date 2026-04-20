<?php
/**
 * API Eventos - VERSIÓN COMPLETA CON GEO
 * Usa todos los campos reales de la tabla eventos:
 * lat, lng, dest_lat, dest_lng, hora, organizador, youtube_link, categoria, fecha
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

session_start();

function respond_success($data, $message = "OK") {
    echo json_encode(['success' => true, 'data' => $data, 'message' => $message]);
    exit;
}

function respond_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

function column_exists_eventos(PDO $db, string $column): bool {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $column)) return false;
    try {
        $st = $db->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'eventos' AND column_name = ? LIMIT 1");
        $st->execute([$column]);
        return (bool)$st->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';

$eventosDefault = [
    [
        'id'          => 1,
        'titulo'      => '📅 Sistema de Eventos Activado',
        'descripcion' => 'El panel de administración está activo. Accede a /admin/ para crear eventos.',
        'fecha'       => date('Y-m-d', strtotime('+7 days')),
        'hora'        => '10:00:00',
        'organizador' => '',
        'lat'         => -34.6037,
        'lng'         => -58.3816,
        'dest_lat'    => null,
        'dest_lng'    => null,
        'ubicacion'   => 'Panel de Administración',
        'youtube_link'=> null,
        'categoria'   => 'General',
        'activo'      => 1
    ]
];

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

$dbAvailable = true;
$db = null;
try {
    $db = \Core\Database::getInstance()->getConnection();
} catch (Exception $e) {
    $dbAvailable = false;
    error_log("BD no disponible para eventos: " . $e->getMessage());
}

// ============ GET ============
if ($method === 'GET') {
    if (!$dbAvailable || !$db) {
        respond_success($eventosDefault, "Eventos (fallback - BD no disponible)");
    }
    try {
        // Evento por ID
        if ($id > 0) {
            $stmt = $db->prepare("SELECT * FROM eventos WHERE id = ?");
            $stmt->execute([$id]);
            $evento = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($evento) respond_success($evento, "Evento obtenido");
            respond_error("Evento no encontrado", 404);
        }

        // Próximos eventos (incluye hoy y futuros)
        if ($action === 'upcoming') {
            $stmt = $db->prepare("SELECT * FROM eventos WHERE activo = 1 AND fecha >= CURDATE() - INTERVAL 1 DAY ORDER BY fecha ASC, hora ASC LIMIT 50");
            $stmt->execute();
            respond_success($stmt->fetchAll(\PDO::FETCH_ASSOC), "Próximos eventos");
        }

        // Eventos cercanos (Haversine)
        if ($action === 'nearby') {
            $lat   = (float)($_GET['lat'] ?? 0);
            $lng   = (float)($_GET['lng'] ?? 0);
            $radio = (float)($_GET['radio'] ?? 10);
            if (!$lat || !$lng) respond_error("Coordenadas requeridas", 400);

            $sql = "SELECT *,
                    (6371 * ACOS(COS(RADIANS(?)) * COS(RADIANS(lat)) *
                     COS(RADIANS(lng) - RADIANS(?)) +
                     SIN(RADIANS(?)) * SIN(RADIANS(lat)))) AS distancia_km
                    FROM eventos
                    WHERE activo = 1 AND lat IS NOT NULL AND lng IS NOT NULL
                    HAVING distancia_km <= ?
                    ORDER BY distancia_km ASC";
            $stmt = $db->prepare($sql);
            $stmt->execute([$lat, $lng, $lat, $radio]);
            respond_success($stmt->fetchAll(\PDO::FETCH_ASSOC), "Eventos cercanos");
        }

        // Todos los eventos activos (por defecto)
        $stmt = $db->prepare("SELECT * FROM eventos WHERE activo = 1 ORDER BY fecha ASC, hora ASC");
        $stmt->execute();
        respond_success($stmt->fetchAll(\PDO::FETCH_ASSOC), "Eventos obtenidos");

    } catch (\PDOException $e) {
        error_log("Error BD eventos GET: " . $e->getMessage());
        respond_success($eventosDefault, "Eventos (fallback)");
    }
}

// ============ POST ============
if ($method === 'POST') {
    // Verificar admin
    if (!isAdmin()) {
        respond_error("Solo administradores pueden realizar esta acción", 403);
    }
    if (!$dbAvailable || !$db) {
        respond_error("Base de datos no disponible", 500);
    }

    // Parsear input JSON o POST
    $input = null;
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($ct, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
    } else {
        $input = $_POST;
    }
    if (!$input) respond_error("Datos inválidos", 400);

    try {
        $hasMapitaId = column_exists_eventos($db, 'mapita_id');
        // ─── CREATE ─────────────────────────────────────
        if ($action === 'create') {
            $titulo    = trim($input['titulo'] ?? '');
            $descripcion = $input['descripcion'] ?? null;
            $fecha     = $input['fecha'] ?? date('Y-m-d');
            $hora      = $input['hora'] ?? null;
            $organizador = $input['organizador'] ?? null;
            $lat       = isset($input['lat']) && $input['lat'] !== '' ? (float)$input['lat'] : null;
            $lng       = isset($input['lng']) && $input['lng'] !== '' ? (float)$input['lng'] : null;
            $dest_lat  = isset($input['dest_lat']) && $input['dest_lat'] !== '' ? (float)$input['dest_lat'] : null;
            $dest_lng  = isset($input['dest_lng']) && $input['dest_lng'] !== '' ? (float)$input['dest_lng'] : null;
            $ubicacion = $input['ubicacion'] ?? null;
            $youtube_link = $input['youtube_link'] ?? null;
            $categoria = $input['categoria'] ?? null;
            $mapita_id = $input['mapita_id'] ?? null;
            $activo    = isset($input['activo']) ? (int)(bool)$input['activo'] : 1;

            if (!$titulo) respond_error("El título es requerido", 400);

            $userId = (int)($_SESSION['user_id'] ?? 0);
            if ($hasMapitaId) {
                $stmt = $db->prepare("
                    INSERT INTO eventos
                        (titulo, descripcion, fecha, hora, organizador, lat, lng,
                         dest_lat, dest_lng, ubicacion, youtube_link, mapita_id, activo, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $result = $stmt->execute([
                    $titulo, $descripcion, $fecha, $hora, $organizador,
                    $lat, $lng, $dest_lat, $dest_lng, $ubicacion, $youtube_link, $mapita_id, $activo
                ]);
            } else {
                $stmt = $db->prepare("
                    INSERT INTO eventos
                        (titulo, descripcion, fecha, hora, organizador, lat, lng,
                         dest_lat, dest_lng, ubicacion, youtube_link, activo, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $result = $stmt->execute([
                    $titulo, $descripcion, $fecha, $hora, $organizador,
                    $lat, $lng, $dest_lat, $dest_lng, $ubicacion, $youtube_link, $activo
                ]);
            }

            if ($result) {
                respond_success(['id' => $db->lastInsertId()], "Evento creado correctamente");
            }
            respond_error("Error al crear evento", 500);
        }

        // ─── UPDATE ─────────────────────────────────────
        if ($action === 'update') {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) respond_error("ID inválido", 400);

            $updates = [];
            $values  = [];

            $campos = ['titulo', 'descripcion', 'fecha', 'hora', 'organizador',
                       'lat', 'lng', 'dest_lat', 'dest_lng', 'ubicacion',
                       'youtube_link', 'categoria'];
            if ($hasMapitaId) $campos[] = 'mapita_id';
            foreach ($campos as $c) {
                if (array_key_exists($c, $input)) {
                    $updates[] = "$c = ?";
                    $val = $input[$c];
                    if (in_array($c, ['lat','lng','dest_lat','dest_lng']) && $val === '') $val = null;
                    $values[] = $val;
                }
            }
            if (isset($input['activo'])) {
                $updates[] = "activo = ?";
                $values[] = (int)(bool)$input['activo'];
            }
            if (empty($updates)) respond_error("No hay datos para actualizar", 400);

            $updates[] = "updated_at = NOW()";
            $values[]  = $id;

            $sql = "UPDATE eventos SET " . implode(", ", $updates) . " WHERE id = ?";
            $stmt = $db->prepare($sql);
            if ($stmt->execute($values)) {
                respond_success([], "Evento actualizado");
            }
            respond_error("Error al actualizar", 500);
        }

        // ─── DELETE ─────────────────────────────────────
        if ($action === 'delete') {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) respond_error("ID inválido", 400);

            $stmt = $db->prepare("DELETE FROM eventos WHERE id = ?");
            if ($stmt->execute([$id])) {
                respond_success([], "Evento eliminado");
            }
            respond_error("Error al eliminar", 500);
        }

    } catch (\PDOException $e) {
        error_log("Error POST eventos: " . $e->getMessage());
        respond_error("Error al procesar: " . $e->getMessage(), 500);
    }
}

respond_error("Método o acción no válida", 405);
