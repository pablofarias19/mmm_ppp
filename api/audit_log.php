<?php
/**
 * API del log de auditoría (solo lectura, solo admins).
 *
 * GET /api/audit_log.php?limit=100&offset=0&action=X&user_id=Y
 */

session_start();

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    respond_error('Acceso denegado.', 403);
}

$limit   = min((int)($_GET['limit']   ?? 100), 200);
$offset  = max((int)($_GET['offset']  ?? 0), 0);
$action  = trim($_GET['action']  ?? '');
$userId  = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

try {
    $db     = \Core\Database::getInstance()->getConnection();
    $where  = [];
    $params = [];

    if ($action !== '') {
        $where[]  = 'action = ?';
        $params[] = $action;
    }
    if ($userId !== null) {
        $where[]  = 'user_id = ?';
        $params[] = $userId;
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $db->prepare("
        SELECT id, user_id, username, action, entity_type, entity_id, details, ip, created_at
        FROM audit_log
        $whereSql
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute($params);
    $logs = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    respond_success($logs, 'Log obtenido.');
} catch (\Exception $e) {
    error_log("Error en audit_log API: " . $e->getMessage());
    respond_error('Error al obtener el log de auditoría.');
}
