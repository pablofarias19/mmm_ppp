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
    if (!$input) respond_error('Datos inválidos');

    try {
        if ($action === 'create') {
            $titulo = trim($input['titulo'] ?? '');
            $tipo   = $input['tipo'] ?? 'youtube_live';
            $url    = $input['stream_url'] ?? null;
            if (!$titulo) respond_error('Título requerido');
            if (!in_array($tipo, ['youtube_live','radio_stream','audio_stream','video_stream'])) {
                $tipo = 'youtube_live';
            }
            $s = $db->prepare("INSERT INTO transmisiones
                (titulo, descripcion, tipo, stream_url, lat, lng,
                 business_id, evento_id, en_vivo, activo, created_at)
                VALUES (?,?,?,?,?,?,?,?,?,?,NOW())");
            $ok = $s->execute([
                $titulo,
                $input['descripcion'] ?? null,
                $tipo, $url,
                $input['lat'] !== '' ? (float)$input['lat'] : null,
                $input['lng'] !== '' ? (float)$input['lng'] : null,
                $input['business_id'] !== '' ? (int)$input['business_id'] : null,
                $input['evento_id']   !== '' ? (int)$input['evento_id']   : null,
                (int)(bool)($input['en_vivo'] ?? false),
                (int)(bool)($input['activo']  ?? true),
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
