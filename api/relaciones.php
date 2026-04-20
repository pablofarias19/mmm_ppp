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

$db = null;
try {
    $db = \Core\Database::getInstance()->getConnection();
} catch (Exception $e) {
    error_log('relaciones db: ' . $e->getMessage());
}

function getEntityByMapita(PDO $db, string $mapitaId): ?array {
    try {
        $st = $db->prepare('SELECT id, mapita_id FROM businesses WHERE mapita_id = ? LIMIT 1');
        $st->execute([$mapitaId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row) return ['entity_type' => 'business', 'entity_id' => (int)$row['id'], 'mapita_id' => $row['mapita_id']];
    } catch (Throwable $e) {}

    try {
        $st = $db->prepare('SELECT id, mapita_id FROM brands WHERE mapita_id = ? LIMIT 1');
        $st->execute([$mapitaId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row) return ['entity_type' => 'brand', 'entity_id' => (int)$row['id'], 'mapita_id' => $row['mapita_id']];
    } catch (Throwable $e) {}

    try {
        $st = $db->prepare('SELECT id, mapita_id FROM eventos WHERE mapita_id = ? LIMIT 1');
        $st->execute([$mapitaId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row) return ['entity_type' => 'evento', 'entity_id' => (int)$row['id'], 'mapita_id' => $row['mapita_id']];
    } catch (Throwable $e) {}

    try {
        $st = $db->prepare('SELECT id, mapita_id FROM marcas WHERE mapita_id = ? LIMIT 1');
        $st->execute([$mapitaId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row) return ['entity_type' => 'brand', 'entity_id' => (int)$row['id'], 'mapita_id' => $row['mapita_id']];
    } catch (Throwable $e) {}

    return null;
}

if ($method === 'GET') {
    if (!$db) respond_success([], 'Relaciones (fallback - BD no disponible)');

    $entityType = trim((string)($_GET['entity_type'] ?? ''));
    $entityId = (int)($_GET['entity_id'] ?? 0);
    $mapitaId = trim((string)($_GET['mapita_id'] ?? ''));

    try {
        if ($action === 'lookup' && $mapitaId !== '') {
            $entity = getEntityByMapita($db, $mapitaId);
            if (!$entity) respond_error('Entidad no encontrada', 404);
            respond_success($entity, 'Entidad encontrada');
        }

        if ($id > 0) {
            $st = $db->prepare('SELECT * FROM entidad_relaciones WHERE id = ?');
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row) respond_success($row, 'Relación obtenida');
            respond_error('Relación no encontrada', 404);
        }

        if ($mapitaId !== '' && (!$entityType || $entityId <= 0)) {
            $entity = getEntityByMapita($db, $mapitaId);
            if ($entity) {
                $entityType = $entity['entity_type'];
                $entityId = (int)$entity['entity_id'];
            }
        }

        $sql = 'SELECT * FROM entidad_relaciones WHERE activo = 1';
        $params = [];
        if ($entityType && $entityId > 0) {
            $sql .= ' AND ((source_entity_type = ? AND source_entity_id = ?) OR (target_entity_type = ? AND target_entity_id = ?))';
            array_push($params, $entityType, $entityId, $entityType, $entityId);
        }
        $sql .= ' ORDER BY created_at DESC, id DESC';

        $st = $db->prepare($sql);
        $st->execute($params);
        respond_success($st->fetchAll(PDO::FETCH_ASSOC), 'Relaciones obtenidas');
    } catch (PDOException $e) {
        error_log('relaciones get: ' . $e->getMessage());
        respond_success([], 'Relaciones (fallback - tabla no disponible)');
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
            $sourceType = trim((string)($input['source_entity_type'] ?? ''));
            $sourceId = (int)($input['source_entity_id'] ?? 0);
            $targetType = trim((string)($input['target_entity_type'] ?? ''));
            $targetId = (int)($input['target_entity_id'] ?? 0);
            if (!$sourceType || !$sourceId || !$targetType || !$targetId) {
                respond_error('source/target requeridos');
            }
            $st = $db->prepare('INSERT INTO entidad_relaciones (source_entity_type, source_entity_id, source_mapita_id, target_entity_type, target_entity_id, target_mapita_id, relation_type, descripcion, activo, created_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())');
            $st->execute([
                $sourceType,
                $sourceId,
                $input['source_mapita_id'] ?: null,
                $targetType,
                $targetId,
                $input['target_mapita_id'] ?: null,
                $input['relation_type'] ?: 'relacionado',
                $input['descripcion'] ?: null,
                isset($input['activo']) ? (int)(bool)$input['activo'] : 1,
            ]);
            respond_success(['id' => (int)$db->lastInsertId()], 'Relación creada');
        }

        if ($action === 'update') {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) respond_error('ID inválido');
            $updates = [];
            $values = [];
            foreach (['source_entity_type','source_entity_id','source_mapita_id','target_entity_type','target_entity_id','target_mapita_id','relation_type','descripcion'] as $f) {
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
            $st = $db->prepare('UPDATE entidad_relaciones SET ' . implode(', ', $updates) . ' WHERE id = ?');
            $st->execute($values);
            respond_success([], 'Relación actualizada');
        }

        if ($action === 'delete') {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) respond_error('ID inválido');
            $st = $db->prepare('DELETE FROM entidad_relaciones WHERE id = ?');
            $st->execute([$id]);
            respond_success([], 'Relación eliminada');
        }

        respond_error('Acción no válida', 405);
    } catch (PDOException $e) {
        error_log('relaciones post: ' . $e->getMessage());
        respond_error('Error al procesar relaciones', 500);
    }
}

respond_error('Método no válido', 405);
