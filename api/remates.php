<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');
session_start();

function respond_success($data = null, $message = 'OK') {
    echo json_encode(['success' => true, 'data' => $data, 'message' => $message]);
    exit;
}
function respond_error($message = 'Error', $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);
$businessId = (int)($_GET['business_id'] ?? 0);

$db = null;
try {
    $db = \Core\Database::getInstance()->getConnection();
} catch (Exception $e) {
    error_log('remates db: ' . $e->getMessage());
}

if ($method === 'GET') {
    if (!$db) respond_success([], 'Remates (fallback - BD no disponible)');
    try {
        if ($id > 0) {
            $st = $db->prepare('SELECT * FROM remates WHERE id = ?');
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row) respond_success($row, 'Remate obtenido');
            respond_error('Remate no encontrado', 404);
        }

        $sql = 'SELECT * FROM remates WHERE 1=1';
        $params = [];
        if ($businessId > 0) {
            $sql .= ' AND business_id = ?';
            $params[] = $businessId;
        }
        if ($action === 'active' || $action === 'upcoming') {
            $sql .= ' AND activo = 1';
        }
        $sql .= ' ORDER BY fecha_inicio ASC, id DESC';
        $st = $db->prepare($sql);
        $st->execute($params);
        respond_success($st->fetchAll(PDO::FETCH_ASSOC), 'Remates obtenidos');
    } catch (PDOException $e) {
        error_log('remates get: ' . $e->getMessage());
        respond_success([], 'Remates (fallback)');
    }
}

if ($method === 'POST') {
    if (!isAdmin()) respond_error('Solo admin', 403);
    if (!$db) respond_error('BD no disponible', 500);

    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    $input = strpos($ct, 'application/json') !== false
        ? json_decode(file_get_contents('php://input'), true)
        : $_POST;
    if (!is_array($input)) respond_error('Datos inválidos');

    try {
        if ($action === 'create') {
            $businessId = (int)($input['business_id'] ?? 0);
            $fechaInicio = trim((string)($input['fecha_inicio'] ?? ''));
            if ($businessId <= 0 || $fechaInicio === '') respond_error('business_id y fecha_inicio son requeridos');
            $st = $db->prepare('INSERT INTO remates (business_id, titulo, descripcion, fecha_inicio, fecha_fin, fecha_cierre, activo, created_at) VALUES (?,?,?,?,?,?,?,NOW())');
            $st->execute([
                $businessId,
                $input['titulo'] ?? null,
                $input['descripcion'] ?? null,
                $fechaInicio,
                $input['fecha_fin'] ?: null,
                $input['fecha_cierre'] ?: null,
                isset($input['activo']) ? (int)(bool)$input['activo'] : 1,
            ]);
            respond_success(['id' => (int)$db->lastInsertId()], 'Remate creado');
        }

        if ($action === 'update') {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) respond_error('ID inválido');

            $updates = [];
            $values = [];
            foreach (['business_id','titulo','descripcion','fecha_inicio','fecha_fin','fecha_cierre'] as $f) {
                if (array_key_exists($f, $input)) {
                    $updates[] = "$f = ?";
                    $values[] = ($input[$f] === '' ? null : $input[$f]);
                }
            }
            if (isset($input['activo'])) {
                $updates[] = 'activo = ?';
                $values[] = (int)(bool)$input['activo'];
            }
            if (!$updates) respond_error('Sin datos para actualizar');
            $updates[] = 'updated_at = NOW()';
            $values[] = $id;
            $st = $db->prepare('UPDATE remates SET ' . implode(', ', $updates) . ' WHERE id = ?');
            $st->execute($values);
            respond_success([], 'Remate actualizado');
        }

        if ($action === 'delete') {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) respond_error('ID inválido');
            $st = $db->prepare('DELETE FROM remates WHERE id = ?');
            $st->execute([$id]);
            respond_success([], 'Remate eliminado');
        }

        respond_error('Acción no válida', 405);
    } catch (PDOException $e) {
        error_log('remates post: ' . $e->getMessage());
        respond_error('Error al procesar remates', 500);
    }
}

respond_error('Método no válido', 405);
