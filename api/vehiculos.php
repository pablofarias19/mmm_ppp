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
    error_log('vehiculos db: ' . $e->getMessage());
}

if ($method === 'GET') {
    if (!$db) respond_success([], 'Vehículos (fallback - BD no disponible)');
    try {
        if ($id > 0) {
            $st = $db->prepare('SELECT * FROM vehiculos_venta WHERE id = ?');
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row) respond_success($row, 'Vehículo obtenido');
            respond_error('Vehículo no encontrado', 404);
        }

        $sql = 'SELECT * FROM vehiculos_venta WHERE 1=1';
        $params = [];
        if ($businessId > 0) {
            $sql .= ' AND business_id = ?';
            $params[] = $businessId;
        }
        if ($action === 'active') {
            $sql .= ' AND activo = 1';
        }
        $sql .= ' ORDER BY created_at DESC, id DESC';
        $st = $db->prepare($sql);
        $st->execute($params);
        respond_success($st->fetchAll(PDO::FETCH_ASSOC), 'Vehículos obtenidos');
    } catch (PDOException $e) {
        error_log('vehiculos get: ' . $e->getMessage());
        respond_success([], 'Vehículos (fallback)');
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
            if ($businessId <= 0) respond_error('business_id requerido');
            if (($input['anio'] ?? '') !== '' && !is_numeric($input['anio'])) respond_error('anio debe ser numérico');
            if (($input['km'] ?? '') !== '' && !is_numeric($input['km'])) respond_error('km debe ser numérico');
            $st = $db->prepare('INSERT INTO vehiculos_venta (business_id, tipo_vehiculo, marca, modelo, anio, km, precio, contacto, activo, created_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())');
            $st->execute([
                $businessId,
                $input['tipo_vehiculo'] ?? null,
                $input['marca'] ?? null,
                $input['modelo'] ?? null,
                ($input['anio'] ?? '') === '' ? null : (int)$input['anio'],
                ($input['km'] ?? '') === '' ? null : (int)$input['km'],
                ($input['precio'] ?? '') === '' ? null : $input['precio'],
                $input['contacto'] ?? null,
                isset($input['activo']) ? (int)(bool)$input['activo'] : 1,
            ]);
            respond_success(['id' => (int)$db->lastInsertId()], 'Vehículo creado');
        }

        if ($action === 'update') {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) respond_error('ID inválido');
            if (array_key_exists('anio', $input) && $input['anio'] !== '' && !is_numeric($input['anio'])) respond_error('anio debe ser numérico');
            if (array_key_exists('km', $input) && $input['km'] !== '' && !is_numeric($input['km'])) respond_error('km debe ser numérico');
            $updates = [];
            $values = [];
            foreach (['business_id','tipo_vehiculo','marca','modelo','anio','km','precio','contacto'] as $f) {
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
            $st = $db->prepare('UPDATE vehiculos_venta SET ' . implode(', ', $updates) . ' WHERE id = ?');
            $st->execute($values);
            respond_success([], 'Vehículo actualizado');
        }

        if ($action === 'delete') {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) respond_error('ID inválido');
            $st = $db->prepare('DELETE FROM vehiculos_venta WHERE id = ?');
            $st->execute([$id]);
            respond_success([], 'Vehículo eliminado');
        }

        respond_error('Acción no válida', 405);
    } catch (PDOException $e) {
        error_log('vehiculos post: ' . $e->getMessage());
        respond_error('Error al procesar vehículos', 500);
    }
}

respond_error('Método no válido', 405);
