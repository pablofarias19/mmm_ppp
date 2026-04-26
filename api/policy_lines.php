<?php
/**
 * API Líneas de Política — CRUD
 *
 * GET    /api/policy_lines.php                              → lista
 * GET    /api/policy_lines.php?id=N                        → detalle
 * GET    /api/policy_lines.php?sector_type=X&sector_id=N   → lineas de un sector
 * POST   /api/policy_lines.php?action=create               → crear (admin)
 * POST   /api/policy_lines.php?action=update               → actualizar (admin)
 * POST   /api/policy_lines.php?action=delete               → eliminar (admin)
 */
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');
session_start();

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../models/PolicyLine.php';

use App\Models\PolicyLine;

function pl_json(array $data, int $code = 200): void {
    http_response_code($code); echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit;
}
function pl_err(string $msg, int $code = 400): void { pl_json(['error' => $msg], $code); }
function pl_isAdmin(): bool { return !empty($_SESSION['is_admin']); }

try { \Core\Database::getInstance()->getConnection(); }
catch (Throwable $e) { pl_err('Base de datos no disponible', 503); }

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($method === 'GET') {
    if ($id > 0) {
        $row = PolicyLine::getById($id);
        if (!$row) pl_err('Linea no encontrada', 404);
        pl_json($row);
    }
    if (!empty($_GET['sector_type']) && !empty($_GET['sector_id'])) {
        $filters = [];
        if (!empty($_GET['line_type'])) $filters['line_type'] = $_GET['line_type'];
        if (!empty($_GET['status']))    $filters['status']    = $_GET['status'];
        $rows = PolicyLine::getBySector($_GET['sector_type'], (int)$_GET['sector_id'], $filters);
        pl_json(['data' => $rows]);
    }
    $filters = [];
    foreach (['source_type','source_id','line_type','status','area'] as $k) {
        if (!empty($_GET[$k])) $filters[$k] = $_GET[$k];
    }
    $rows = PolicyLine::getAll($filters);
    pl_json(['data' => $rows, 'total' => count($rows)]);
}

if ($method === 'POST') {
    if (!pl_isAdmin()) pl_err('Acceso denegado', 403);
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true) ?? $_POST;

    if ($action === 'create') {
        $errs = PolicyLine::validate($data);
        if ($errs) pl_err(implode('; ', $errs));
        $newId = PolicyLine::create($data);
        if (!$newId) pl_err('Error al crear linea', 500);
        pl_json(['ok' => true, 'id' => $newId], 201);
    }
    if ($action === 'update') {
        $rid = $id ?: (int)($data['id'] ?? 0);
        if (!$rid) pl_err('ID requerido');
        $errs = PolicyLine::validate($data, false);
        if ($errs) pl_err(implode('; ', $errs));
        pl_json(['ok' => PolicyLine::update($rid, $data)]);
    }
    if ($action === 'delete') {
        $rid = $id ?: (int)($data['id'] ?? 0);
        if (!$rid) pl_err('ID requerido');
        pl_json(['ok' => PolicyLine::delete($rid)]);
    }
    pl_err('Accion no reconocida');
}
pl_err('Metodo no permitido', 405);
