<?php
/**
 * API de Inmuebles (subitems de inmobiliarias)
 *
 * GET  /api/inmuebles.php?business_id=N  → lista inmuebles de una inmobiliaria
 * GET  /api/inmuebles.php?id=N           → detalle de un inmueble
 * GET  /api/inmuebles.php?all=1          → todos los inmuebles activos (para CERCA en mapa)
 * POST /api/inmuebles.php                → crear/actualizar (requiere sesión, propietario)
 * DELETE /api/inmuebles.php?id=N         → eliminar (requiere sesión, propietario)
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

require_once __DIR__ . '/../includes/db_helper.php';
require_once __DIR__ . '/../core/helpers.php';

function inm_ok($data = [], $msg = 'OK') {
    echo json_encode(['success' => true, 'data' => $data, 'message' => $msg]);
    exit;
}
function inm_err($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'data' => null, 'message' => $msg]);
    exit;
}

$db = getDbConnection();
if (!$db) inm_err('Sin conexión a la base de datos', 500);

// Verificar que la tabla existe
try {
    $db->query("SELECT 1 FROM inmuebles LIMIT 1");
} catch (\PDOException $e) {
    inm_err('Tabla inmuebles no existe. Ejecutar migración 022_cerca_convocar.sql', 503);
}

$method = $_SERVER['REQUEST_METHOD'];
$userId = (int)($_SESSION['user_id'] ?? 0);

if ($method === 'GET') {
    $id         = (int)($_GET['id'] ?? 0);
    $businessId = (int)($_GET['business_id'] ?? 0);
    $all        = !empty($_GET['all']);

    if ($id > 0) {
        $stmt = $db->prepare("SELECT i.*, b.name AS inmobiliaria_nombre, b.icon_url AS inmobiliaria_icon
                               FROM inmuebles i
                               JOIN businesses b ON b.id = i.business_id
                               WHERE i.id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) inm_err('Inmueble no encontrado', 404);
        inm_ok($row);
    }

    if ($all) {
        $stmt = $db->prepare("SELECT i.*, b.name AS inmobiliaria_nombre, b.icon_url AS inmobiliaria_icon,
                                      b.lat AS inm_lat_fallback, b.lng AS inm_lng_fallback
                               FROM inmuebles i
                               JOIN businesses b ON b.id = i.business_id
                               WHERE i.activo = 1
                               ORDER BY i.created_at DESC");
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        inm_ok($rows);
    }

    if ($businessId > 0) {
        $stmt = $db->prepare("SELECT * FROM inmuebles WHERE business_id = ? ORDER BY created_at DESC");
        $stmt->execute([$businessId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        inm_ok($rows);
    }

    inm_err('Parámetro requerido: id, business_id o all=1');
}

if ($method === 'POST') {
    if ($userId <= 0) inm_err('Sesión requerida', 401);

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $businessId = (int)($input['business_id'] ?? 0);
    if ($businessId <= 0) inm_err('business_id requerido');

    // Verificar propietario
    $stmt = $db->prepare("SELECT user_id, business_type FROM businesses WHERE id = ? LIMIT 1");
    $stmt->execute([$businessId]);
    $biz = $stmt->fetch(\PDO::FETCH_ASSOC);
    if (!$biz) inm_err('Negocio no encontrado', 404);
    if ((int)$biz['user_id'] !== $userId && !isAdmin()) inm_err('Sin permisos', 403);
    if ($biz['business_type'] !== 'inmobiliaria') inm_err('Solo inmobiliarias pueden publicar inmuebles', 403);

    $titulo     = trim((string)($input['titulo'] ?? ''));
    if ($titulo === '') inm_err('El título es obligatorio');
    if (mb_strlen($titulo) > 255) inm_err('Título demasiado largo (máx. 255 chars)');

    $operacion  = in_array($input['operacion'] ?? '', ['venta','alquiler'], true) ? $input['operacion'] : 'venta';
    $descripcion = mb_substr(trim((string)($input['descripcion'] ?? '')), 0, 2000) ?: null;
    $precio      = is_numeric($input['precio'] ?? null) ? (float)$input['precio'] : null;
    $moneda      = in_array($input['moneda'] ?? 'ARS', ['ARS','USD','EUR'], true) ? $input['moneda'] : 'ARS';
    $direccion   = mb_substr(trim((string)($input['direccion'] ?? '')), 0, 500) ?: null;
    $lat         = is_numeric($input['lat'] ?? null) ? (float)$input['lat'] : null;
    $lng         = is_numeric($input['lng'] ?? null) ? (float)$input['lng'] : null;
    $contacto    = mb_substr(trim((string)($input['contacto'] ?? '')), 0, 255) ?: null;

    $inmId = (int)($input['id'] ?? 0);

    if ($inmId > 0) {
        // Update
        $stmt2 = $db->prepare("SELECT id FROM inmuebles WHERE id = ? AND business_id = ? LIMIT 1");
        $stmt2->execute([$inmId, $businessId]);
        if (!$stmt2->fetch()) inm_err('Inmueble no encontrado o sin acceso', 404);

        $upd = $db->prepare("UPDATE inmuebles SET operacion=?,titulo=?,descripcion=?,precio=?,moneda=?,direccion=?,lat=?,lng=?,contacto=?,updated_at=NOW() WHERE id=?");
        $upd->execute([$operacion,$titulo,$descripcion,$precio,$moneda,$direccion,$lat,$lng,$contacto,$inmId]);
        inm_ok(['id' => $inmId], 'Inmueble actualizado');
    } else {
        // Insert
        $ins = $db->prepare("INSERT INTO inmuebles (business_id,operacion,titulo,descripcion,precio,moneda,direccion,lat,lng,contacto) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $ins->execute([$businessId,$operacion,$titulo,$descripcion,$precio,$moneda,$direccion,$lat,$lng,$contacto]);
        $newId = (int)$db->lastInsertId();
        inm_ok(['id' => $newId], 'Inmueble creado');
    }
}

if ($method === 'DELETE') {
    if ($userId <= 0) inm_err('Sesión requerida', 401);
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) inm_err('id requerido');

    $stmt = $db->prepare("SELECT i.id, b.user_id FROM inmuebles i JOIN businesses b ON b.id = i.business_id WHERE i.id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    if (!$row) inm_err('Inmueble no encontrado', 404);
    if ((int)$row['user_id'] !== $userId && !isAdmin()) inm_err('Sin permisos', 403);

    $db->prepare("DELETE FROM inmuebles WHERE id = ?")->execute([$id]);
    inm_ok([], 'Inmueble eliminado');
}

inm_err('Método no permitido', 405);
