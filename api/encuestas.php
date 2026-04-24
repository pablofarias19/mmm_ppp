<?php
/**
 * API Encuestas - VERSIÓN COMPLETA CON GEO
 * Usa todos los campos reales de la tabla encuestas:
 * lat, lng, link, fecha_expiracion, activo
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
const MAX_AGGREGATE_IDS = 100;
function parse_aggregate_ids($idsRaw) {
    $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', (string)$idsRaw)), function($v) {
        return $v > 0;
    })));
    if (count($ids) > MAX_AGGREGATE_IDS) respond_error("Máximo " . MAX_AGGREGATE_IDS . " IDs por consulta", 400);
    return $ids;
}

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';

$encuestasDefault = [
    [
        'id'               => 1,
        'titulo'           => '📋 Sistema de Encuestas Activado',
        'descripcion'      => 'El panel de administración está activo. Accede a /admin/ para crear encuestas.',
        'lat'              => -34.6037,
        'lng'              => -58.3816,
        'link'             => null,
        'fecha_creacion'   => date('Y-m-d'),
        'fecha_expiracion' => null,
        'activo'           => 1
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
    error_log("BD no disponible para encuestas: " . $e->getMessage());
}

// ============ GET ============
if ($method === 'GET') {
    if (!$dbAvailable || !$db) {
        if ($action === 'aggregate') {
            $idsRaw = trim((string)($_GET['ids'] ?? ''));
            $ids = parse_aggregate_ids($idsRaw);
            $surveys = array_map(function($surveyId) {
                return [
                    'id' => $surveyId,
                    'titulo' => 'Encuesta #' . $surveyId,
                    'total_respuestas' => 0,
                    'total_participantes' => 0
                ];
            }, $ids);
            respond_success([
                'surveys' => $surveys,
                'summary' => [
                    'encuestas' => count($surveys),
                    'total_respuestas' => 0,
                    'total_participantes' => 0
                ]
            ], "Agregación (fallback)");
        }
        if ($action === 'stats_global') {
            respond_success([
                'total' => 0,
                'activas' => 0,
                'total_respuestas' => 0,
                'total_participantes' => 0,
                'top_encuestas' => []
            ], "Stats globales (fallback)");
        }
        respond_success($encuestasDefault, "Encuestas (fallback)");
    }
    try {
        if ($action === 'aggregate') {
            $idsRaw = trim((string)($_GET['ids'] ?? ''));
            if ($idsRaw === '') respond_error("IDs requeridos", 400);
            $ids = parse_aggregate_ids($idsRaw);
            if (empty($ids)) respond_error("IDs inválidos", 400);

            $idsPlaceholder = implode(',', array_fill(0, count($ids), '?'));
            $surveys = [];
            foreach ($ids as $surveyId) {
                $surveys[$surveyId] = [
                    'id' => $surveyId,
                    'titulo' => 'Encuesta #' . $surveyId,
                    'total_respuestas' => 0,
                    'total_participantes' => 0
                ];
            }

            try {
                $sEnc = $db->prepare("SELECT id, titulo FROM encuestas WHERE id IN ($idsPlaceholder)");
                $sEnc->execute($ids);
                foreach ($sEnc->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                    $surveyId = (int)$row['id'];
                    if (isset($surveys[$surveyId])) {
                        $surveys[$surveyId]['titulo'] = $row['titulo'] ?: ('Encuesta #' . $surveyId);
                    }
                }
            } catch (\PDOException $e) {
                respond_success([
                    'surveys' => array_values($surveys),
                    'summary' => [
                        'encuestas' => count($surveys),
                        'total_respuestas' => 0,
                        'total_participantes' => 0
                    ]
                ], "Tabla encuestas no disponible (fallback)");
            }

            $queryCandidates = [
                "SELECT encuesta_id, COUNT(*) AS total_respuestas, COUNT(DISTINCT user_id) AS total_participantes
                 FROM encuesta_responses WHERE encuesta_id IN ($idsPlaceholder) GROUP BY encuesta_id",
                "SELECT encuesta_id, COUNT(*) AS total_respuestas, COUNT(DISTINCT user_id) AS total_participantes
                 FROM respuestas_encuesta WHERE encuesta_id IN ($idsPlaceholder) GROUP BY encuesta_id",
                "SELECT p.encuesta_id AS encuesta_id, COUNT(*) AS total_respuestas, COUNT(DISTINCT r.id_usuario) AS total_participantes
                 FROM respuestas_encuesta r
                 INNER JOIN preguntas_encuesta p ON p.id = r.id_pregunta
                 WHERE p.encuesta_id IN ($idsPlaceholder)
                 GROUP BY p.encuesta_id"
            ];
            foreach ($queryCandidates as $query) {
                try {
                    $sResp = $db->prepare($query);
                    $sResp->execute($ids);
                    foreach ($sResp->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                        $surveyId = (int)$row['encuesta_id'];
                        if (!isset($surveys[$surveyId])) continue;
                        $surveys[$surveyId]['total_respuestas'] = (int)($row['total_respuestas'] ?? 0);
                        $surveys[$surveyId]['total_participantes'] = (int)($row['total_participantes'] ?? 0);
                    }
                    break;
                } catch (\PDOException $e) {
                    continue;
                }
            }

            $totalRespuestas = 0;
            $totalParticipantes = 0;
            foreach ($surveys as $row) {
                $totalRespuestas += (int)$row['total_respuestas'];
                $totalParticipantes += (int)$row['total_participantes'];
            }
            respond_success([
                'surveys' => array_values($surveys),
                'summary' => [
                    'encuestas' => count($surveys),
                    'total_respuestas' => $totalRespuestas,
                    'total_participantes' => $totalParticipantes
                ]
            ], "Agregación completada");
        }

        // Stats globales para el panel admin
        if ($action === 'stats_global') {
            $stats = [
                'total' => 0,
                'activas' => 0,
                'total_respuestas' => 0,
                'total_participantes' => 0,
                'top_encuestas' => []
            ];

            try {
                $r = $db->query("SELECT COUNT(*) AS total,
                    SUM(CASE WHEN activo=1 AND (fecha_expiracion IS NULL OR fecha_expiracion >= CURDATE()) THEN 1 ELSE 0 END) AS activas
                    FROM encuestas");
                $row = $r->fetch(\PDO::FETCH_ASSOC);
                $stats['total']   = (int)($row['total']   ?? 0);
                $stats['activas'] = (int)($row['activas'] ?? 0);
            } catch (\PDOException $e) { /* tabla aún no existe */ }

            $globalCandidates = [
                [
                    'counts' => "SELECT COUNT(*) AS total_respuestas, COUNT(DISTINCT user_id) AS total_participantes FROM encuesta_responses",
                    'top'    => "SELECT e.id, e.titulo, COUNT(r.id) AS participantes FROM encuestas e LEFT JOIN encuesta_responses r ON r.encuesta_id=e.id GROUP BY e.id, e.titulo ORDER BY participantes DESC LIMIT 5"
                ],
                [
                    'counts' => "SELECT COUNT(*) AS total_respuestas, COUNT(DISTINCT user_id) AS total_participantes FROM respuestas_encuesta",
                    'top'    => "SELECT e.id, e.titulo, COUNT(r.id) AS participantes FROM encuestas e LEFT JOIN respuestas_encuesta r ON r.encuesta_id=e.id GROUP BY e.id, e.titulo ORDER BY participantes DESC LIMIT 5"
                ],
                [
                    'counts' => "SELECT COUNT(*) AS total_respuestas, COUNT(DISTINCT r.id_usuario) AS total_participantes FROM respuestas_encuesta r INNER JOIN preguntas_encuesta p ON p.id = r.id_pregunta",
                    'top'    => "SELECT e.id, e.titulo, COUNT(DISTINCT r.id_usuario) AS participantes FROM encuestas e LEFT JOIN preguntas_encuesta p ON p.encuesta_id=e.id LEFT JOIN respuestas_encuesta r ON r.id_pregunta=p.id GROUP BY e.id, e.titulo ORDER BY participantes DESC LIMIT 5"
                ]
            ];
            foreach ($globalCandidates as $candidate) {
                try {
                    $rc = $db->query($candidate['counts']);
                    $counts = $rc->fetch(\PDO::FETCH_ASSOC);
                    $stats['total_respuestas']    = (int)($counts['total_respuestas']    ?? 0);
                    $stats['total_participantes'] = (int)($counts['total_participantes'] ?? 0);
                    $rt = $db->query($candidate['top']);
                    $top = $rt->fetchAll(\PDO::FETCH_ASSOC);
                    foreach ($top as &$t) { $t['participantes'] = (int)$t['participantes']; }
                    unset($t);
                    $stats['top_encuestas'] = $top;
                    break;
                } catch (\PDOException $e) {
                    continue;
                }
            }

            respond_success($stats, "Stats globales");
        }

        // Por ID
        if ($id > 0) {
            $stmt = $db->prepare("SELECT * FROM encuestas WHERE id = ?");
            $stmt->execute([$id]);
            $enc = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($enc) respond_success($enc, "Encuesta obtenida");
            respond_error("Encuesta no encontrada", 404);
        }

        // Encuestas cercanas usando Haversine
        if ($action === 'nearby') {
            $lat   = (float)($_GET['lat'] ?? 0);
            $lng   = (float)($_GET['lng'] ?? 0);
            $radio = (float)($_GET['radio'] ?? 5);
            if (!$lat || !$lng) respond_error("Coordenadas requeridas", 400);

            $sql = "SELECT *,
                    (6371 * ACOS(COS(RADIANS(?)) * COS(RADIANS(lat)) *
                     COS(RADIANS(lng) - RADIANS(?)) +
                     SIN(RADIANS(?)) * SIN(RADIANS(lat)))) AS distancia_km
                    FROM encuestas
                    WHERE activo = 1
                    AND (fecha_expiracion IS NULL OR fecha_expiracion >= CURDATE())
                    AND lat IS NOT NULL
                    HAVING distancia_km <= ?
                    ORDER BY distancia_km ASC";
            $stmt = $db->prepare($sql);
            $stmt->execute([$lat, $lng, $lat, $radio]);
            respond_success($stmt->fetchAll(\PDO::FETCH_ASSOC), "Encuestas cercanas");
        }

        // Todas las activas
        $stmt = $db->prepare("
            SELECT * FROM encuestas
            WHERE activo = 1
            AND (fecha_expiracion IS NULL OR fecha_expiracion >= CURDATE())
            ORDER BY fecha_creacion DESC
        ");
        $stmt->execute();
        respond_success($stmt->fetchAll(\PDO::FETCH_ASSOC), "Encuestas obtenidas");

    } catch (\PDOException $e) {
        error_log("Error BD encuestas GET: " . $e->getMessage());
        respond_success($encuestasDefault, "Encuestas (fallback)");
    }
}

// ============ POST ============
if ($method === 'POST') {
    if (!in_array($action, ['respond'])) {
        if (!isAdmin()) {
            respond_error("Solo administradores pueden realizar esta acción", 403);
        }
    }
    if (!$dbAvailable || !$db) {
        respond_error("Base de datos no disponible", 500);
    }

    $input = null;
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($ct, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
    } else {
        $input = $_POST;
    }
    if (!$input) respond_error("Datos inválidos", 400);

    try {
        // ─── CREATE ─────────────────────────────────────
        if ($action === 'create') {
            $titulo           = trim($input['titulo'] ?? '');
            $descripcion      = $input['descripcion'] ?? null;
            $lat              = isset($input['lat']) && $input['lat'] !== '' ? (float)$input['lat'] : 0;
            $lng              = isset($input['lng']) && $input['lng'] !== '' ? (float)$input['lng'] : 0;
            $link             = $input['link'] ?? null;
            $fecha_creacion   = $input['fecha_creacion'] ?? date('Y-m-d');
            $fecha_expiracion = $input['fecha_expiracion'] ?? null;
            $activo           = isset($input['activo']) ? (int)(bool)$input['activo'] : 1;

            if (!$titulo) respond_error("El título es requerido", 400);

            $stmt = $db->prepare("
                INSERT INTO encuestas
                    (titulo, descripcion, lat, lng, link, fecha_creacion, fecha_expiracion, activo, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $result = $stmt->execute([
                $titulo, $descripcion, $lat, $lng,
                $link, $fecha_creacion, $fecha_expiracion ?: null, $activo
            ]);

            if ($result) {
                respond_success(['id' => $db->lastInsertId()], "Encuesta creada correctamente");
            }
            respond_error("Error al crear encuesta", 500);
        }

        // ─── UPDATE ─────────────────────────────────────
        if ($action === 'update') {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) respond_error("ID inválido", 400);

            $updates = [];
            $values  = [];

            $campos = ['titulo', 'descripcion', 'lat', 'lng', 'link',
                       'fecha_creacion', 'fecha_expiracion'];
            foreach ($campos as $c) {
                if (array_key_exists($c, $input)) {
                    $updates[] = "$c = ?";
                    $val = $input[$c];
                    if ($val === '') $val = null;
                    $values[] = $val;
                }
            }
            if (isset($input['activo'])) {
                $updates[] = "activo = ?";
                $values[]  = (int)(bool)$input['activo'];
            }
            if (empty($updates)) respond_error("No hay datos para actualizar", 400);

            $updates[] = "updated_at = NOW()";
            $values[]  = $id;

            $sql = "UPDATE encuestas SET " . implode(", ", $updates) . " WHERE id = ?";
            $stmt = $db->prepare($sql);
            if ($stmt->execute($values)) {
                respond_success([], "Encuesta actualizada");
            }
            respond_error("Error al actualizar", 500);
        }

        // ─── DELETE ─────────────────────────────────────
        if ($action === 'delete') {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) respond_error("ID inválido", 400);

            $stmt = $db->prepare("DELETE FROM encuestas WHERE id = ?");
            if ($stmt->execute([$id])) {
                respond_success([], "Encuesta eliminada");
            }
            respond_error("Error al eliminar", 500);
        }

        // ─── RESPOND (usuario responde encuesta) ─────────
        if ($action === 'respond') {
            if (!isset($_SESSION['user_id'])) {
                respond_error("Usuario no logueado", 401);
            }
            $encuesta_id = (int)($input['encuesta_id'] ?? 0);
            $respuestas  = $input['respuestas'] ?? [];
            if ($encuesta_id <= 0) respond_error("ID inválido", 400);
            if (empty($respuestas)) respond_error("Se requieren respuestas", 400);

            $stmt = $db->prepare("
                INSERT INTO encuesta_responses (encuesta_id, user_id, respuestas, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            if ($stmt->execute([$encuesta_id, $_SESSION['user_id'], json_encode($respuestas)])) {
                respond_success([], "Respuestas registradas");
            }
            respond_error("Error al registrar respuestas", 500);
        }

    } catch (\PDOException $e) {
        error_log("Error POST encuestas: " . $e->getMessage());
        respond_error("Error al procesar: " . $e->getMessage(), 500);
    }
}

respond_error("Método o acción no válida", 405);
