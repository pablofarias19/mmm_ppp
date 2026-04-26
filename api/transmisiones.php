<?php
/**
 * API Transmisiones en Vivo — video y radio
 * Tabla: transmisiones (creada por migration/001_transmisiones.sql)
 */
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');
session_start();

function respond_success($data, $msg = 'OK') {
    echo json_encode(['success' => true, 'data' => $data, 'message' => $msg]); exit;
}
function respond_error($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]); exit;
}

require_once __DIR__ . '/../core/Database.php';

$fallback = [[
    'id'          => 0,
    'titulo'      => '📡 Sistema de Transmisiones Activado',
    'descripcion' => 'Accede a /admin/ para crear transmisiones de video o radio en vivo.',
    'tipo'        => 'youtube_live',
    'stream_url'  => null,
    'lat'         => -34.6037,
    'lng'         => -58.3816,
    'activo'      => 1
]];

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

$db = null;
try { $db = \Core\Database::getInstance()->getConnection(); }
catch (Exception $e) { error_log('Transmisiones BD: ' . $e->getMessage()); }

// ── GET ──────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    if (!$db) respond_success($fallback, 'Transmisiones (fallback)');
    try {
        // Verificar que la tabla existe
        $db->query("SELECT 1 FROM transmisiones LIMIT 1");

        if ($id > 0) {
            $s = $db->prepare("SELECT * FROM transmisiones WHERE id = ?");
            $s->execute([$id]);
            $t = $s->fetch(\PDO::FETCH_ASSOC);
            if ($t) respond_success($t);
            respond_error('No encontrada', 404);
        }

        // Nearby
        if ($action === 'nearby') {
            $lat   = (float)($_GET['lat'] ?? 0);
            $lng   = (float)($_GET['lng'] ?? 0);
            $radio = (float)($_GET['radio'] ?? 50);
            if (!$lat || !$lng) respond_error('Coordenadas requeridas');
            $sql = "SELECT *, (6371 * ACOS(COS(RADIANS(?)) * COS(RADIANS(lat)) *
                    COS(RADIANS(lng)-RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(lat)))) AS dist_km
                    FROM transmisiones
                    WHERE activo = 1
                    HAVING dist_km <= ? ORDER BY dist_km ASC";
            $s = $db->prepare($sql);
            $s->execute([$lat, $lng, $lat, $radio]);
            respond_success($s->fetchAll(\PDO::FETCH_ASSOC), 'Transmisiones cercanas');
        }

        // Solo en vivo
        if ($action === 'live') {
            $s = $db->prepare("SELECT * FROM transmisiones WHERE activo = 1 AND en_vivo = 1 ORDER BY created_at DESC");
            $s->execute();
            respond_success($s->fetchAll(\PDO::FETCH_ASSOC), 'Transmisiones en vivo');
        }

        // Search with optional type/text/location filters + pagination
        if ($action === 'search') {
            $tipo   = $_GET['tipo']   ?? '';
            $q      = trim($_GET['q'] ?? '');
            $lat    = (float)($_GET['lat']   ?? 0);
            $lng    = (float)($_GET['lng']   ?? 0);
            $radio  = (float)($_GET['radio'] ?? 100);
            $limit  = min(max((int)($_GET['limit']  ?? 2), 1), 50);
            $offset = max((int)($_GET['offset'] ?? 0), 0);

            $where  = ['activo = 1'];
            $params = [];

            // Type filter
            if ($tipo === 'youtube') {
                $where[] = "tipo IN ('youtube_live', 'youtube_video')";
            } elseif ($tipo === 'en_vivo') {
                $where[] = 'en_vivo = 1';
            }

            // Text search
            if ($q !== '') {
                $where[] = "(titulo LIKE ? OR descripcion LIKE ?)";
                $params[] = '%' . $q . '%';
                $params[] = '%' . $q . '%';
            }

            $useLocation = ($lat != 0 && $lng != 0 && $radio > 0);

            if ($useLocation) {
                // Bounding-box pre-filter for performance (rough rectangle check before Haversine).
                // Rows without coordinates (lat IS NULL or lat = 0) bypass the bounding box so they
                // still appear in results; the HAVING clause later keeps them unconditionally.
                $dlat = $radio / 111.0;
                $dlng = $radio / (111.0 * cos(deg2rad($lat)));
                $where[] = "(lat IS NULL OR lat = 0 OR (lat BETWEEN ? AND ? AND lng BETWEEN ? AND ?))";
                $params[] = $lat - $dlat;
                $params[] = $lat + $dlat;
                $params[] = $lng - $dlng;
                $params[] = $lng + $dlng;
            }

            $wStr = implode(' AND ', $where);

            if ($useLocation) {
                // Haversine great-circle distance formula.
                // Parameters in $distExpr: ? = $lat (COS outer), ? = $lng (COS inner), ? = $lat (SIN).
                // Rows with no coordinates (lat IS NULL / lat = 0) get dist_km = NULL and are
                // intentionally included by "HAVING dist_km IS NULL OR dist_km <= ?" so that
                // global content (no specific location) always surfaces alongside nearby items.
                $distExpr = "IF(lat IS NOT NULL AND lat != 0,
                    6371 * ACOS(GREATEST(-1, LEAST(1,
                        COS(RADIANS(?)) * COS(RADIANS(lat)) *
                        COS(RADIANS(lng) - RADIANS(?)) +
                        SIN(RADIANS(?)) * SIN(RADIANS(lat))
                    ))),
                    NULL) AS dist_km";
                $sql = "SELECT *, $distExpr
                        FROM transmisiones
                        WHERE $wStr
                        HAVING dist_km IS NULL OR dist_km <= ?
                        ORDER BY en_vivo DESC, IFNULL(dist_km, 999999) ASC
                        LIMIT ? OFFSET ?";
                $allParams = array_merge([$lat, $lng, $lat], $params, [$radio, $limit, $offset]);
            } else {
                $sql = "SELECT * FROM transmisiones
                        WHERE $wStr
                        ORDER BY en_vivo DESC, created_at DESC
                        LIMIT ? OFFSET ?";
                $allParams = array_merge($params, [$limit, $offset]);
            }

            $s = $db->prepare($sql);
            $s->execute($allParams);
            respond_success($s->fetchAll(\PDO::FETCH_ASSOC), 'Búsqueda de transmisiones');
        }

        // Default: todas activas
        $s = $db->prepare("SELECT * FROM transmisiones WHERE activo = 1 ORDER BY en_vivo DESC, created_at DESC");
        $s->execute();
        respond_success($s->fetchAll(\PDO::FETCH_ASSOC), 'Transmisiones obtenidas');

    } catch (\PDOException $e) {
        error_log('Transmisiones GET: ' . $e->getMessage());
        // Tabla puede no existir aún — devolver fallback con mensaje claro
        respond_success($fallback, 'Tabla transmisiones no existe aún. Ejecutar migration/001_transmisiones.sql');
    }
}

// ── POST ─────────────────────────────────────────────────────────────────────
if ($method === 'POST') {
    if (empty($_SESSION['is_admin'])) respond_error('Solo admin', 403);
    if (!$db) respond_error('BD no disponible', 500);

    $ct    = $_SERVER['CONTENT_TYPE'] ?? '';
    $input = strpos($ct, 'application/json') !== false
           ? json_decode(file_get_contents('php://input'), true)
           : $_POST;
    // Para 'delete' el id puede venir sólo en la query string (POST sin body)
    if (!$input) {
        if ($action === 'delete' && isset($_GET['id'])) {
            $input = [];
        } else {
            respond_error('Datos inválidos');
        }
    }

    try {
        if ($action === 'create') {
            $titulo = trim($input['titulo'] ?? '');
            $tipo   = $input['tipo'] ?? 'youtube_live';
            $url    = $input['stream_url'] ?? null;
            if (!$titulo) respond_error('Título requerido');
            if (!in_array($tipo, ['youtube_live','youtube_video','radio_stream','audio_stream','video_stream'])) {
                $tipo = 'youtube_live';
            }
            $s = $db->prepare("INSERT INTO transmisiones
                (titulo, descripcion, tipo, stream_url, lat, lng,
                 business_id, evento_id, en_vivo, activo,
                 fecha_inicio, fecha_fin, created_at)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW())");
            $ok = $s->execute([
                $titulo,
                $input['descripcion'] ?? null,
                $tipo, $url,
                isset($input['lat']) && $input['lat'] !== '' ? (float)$input['lat'] : null,
                isset($input['lng']) && $input['lng'] !== '' ? (float)$input['lng'] : null,
                isset($input['business_id']) && $input['business_id'] !== '' ? (int)$input['business_id'] : null,
                isset($input['evento_id'])   && $input['evento_id']   !== '' ? (int)$input['evento_id']   : null,
                (int)(bool)($input['en_vivo'] ?? false),
                (int)(bool)($input['activo']  ?? true),
                !empty($input['fecha_inicio']) ? str_replace('T', ' ', $input['fecha_inicio']) : null,
                !empty($input['fecha_fin'])    ? str_replace('T', ' ', $input['fecha_fin'])    : null,
            ]);
            if ($ok) respond_success(['id' => $db->lastInsertId()], 'Transmisión creada');
            respond_error('Error al crear', 500);
        }

        if ($action === 'update') {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) respond_error('ID inválido');
            $upd = []; $vals = [];
            foreach (['titulo','descripcion','tipo','stream_url','lat','lng',
                      'business_id','evento_id'] as $c) {
                if (array_key_exists($c, $input)) {
                    $upd[] = "$c = ?";
                    $vals[] = ($input[$c] === '') ? null : $input[$c];
                }
            }
            foreach (['en_vivo','activo'] as $b) {
                if (isset($input[$b])) { $upd[] = "$b = ?"; $vals[] = (int)(bool)$input[$b]; }
            }
            foreach (['fecha_inicio','fecha_fin'] as $dt) {
                if (array_key_exists($dt, $input)) {
                    $upd[]  = "$dt = ?";
                    $vals[] = ($input[$dt] === '' || $input[$dt] === null)
                        ? null
                        : str_replace('T', ' ', $input[$dt]);
                }
            }
            if (empty($upd)) respond_error('Sin datos');
            $upd[] = 'updated_at = NOW()'; $vals[] = $id;
            $s = $db->prepare("UPDATE transmisiones SET " . implode(', ', $upd) . " WHERE id = ?");
            if ($s->execute($vals)) respond_success([], 'Actualizada');
            respond_error('Error al actualizar', 500);
        }

        if ($action === 'delete') {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) respond_error('ID inválido');
            $s = $db->prepare("DELETE FROM transmisiones WHERE id = ?");
            if ($s->execute([$id])) respond_success([], 'Eliminada');
            respond_error('Error al eliminar', 500);
        }

    } catch (\PDOException $e) {
        error_log('Transmisiones POST: ' . $e->getMessage());
        respond_error('Error: ' . $e->getMessage(), 500);
    }
}
respond_error('Método no válido', 405);
