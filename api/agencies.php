<?php
/**
 * API Agencias — CRUD + vincular sectores
 *
 * GET    /api/agencies.php                          → lista
 * GET    /api/agencies.php?id=N                     → detalle
 * GET    /api/agencies.php?sector_type=X&sector_id=N → agencias de un sector
 * POST   /api/agencies.php?action=create            → crear (admin)
 * POST   /api/agencies.php?action=update            → actualizar (admin)
 * POST   /api/agencies.php?action=delete            → eliminar (admin)
 * POST   /api/agencies.php?action=sync_sectors      → sincronizar sectores (admin)
 */
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');
session_start();

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../models/Agency.php';

use App\Models\Agency;

function ag_json(array $data, int $code = 200): void {
    http_response_code($code); echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit;
}
function ag_err(string $msg, int $code = 400): void { ag_json(['error' => $msg], $code); }
function ag_isAdmin(): bool { return !empty($_SESSION['is_admin']); }

try { \Core\Database::getInstance()->getConnection(); }
catch (Throwable $e) { ag_err('Base de datos no disponible', 503); }

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($method === 'GET') {
    if ($id > 0) {
        $row = Agency::getById($id);
        if (!$row) ag_err('Agencia no encontrada', 404);
        $row['sectors'] = Agency::getSectors($id);
        ag_json($row);
    }
    if (!empty($_GET['sector_type']) && !empty($_GET['sector_id'])) {
        $rows = Agency::getBySector($_GET['sector_type'], (int)$_GET['sector_id']);
        ag_json(['data' => $rows]);
    }
    $filters = [];
    if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
    if (!empty($_GET['area']))   $filters['area']   = $_GET['area'];
    $limit  = min((int)($_GET['limit']  ?? 100), 200);
    $offset = max((int)($_GET['offset'] ?? 0), 0);
    $rows = Agency::getAll($filters, $limit, $offset);
    ag_json(['data' => $rows, 'total' => count($rows)]);
}

if ($method === 'POST') {
    if (!ag_isAdmin()) ag_err('Acceso denegado', 403);
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true) ?? $_POST;

    if ($action === 'create') {
        $errs = Agency::validate($data);
        if ($errs) ag_err(implode('; ', $errs));
        $newId = Agency::create($data);
        if (!$newId) ag_err('Error al crear agencia', 500);
        if (!empty($data['sectors'])) Agency::syncSectors($newId, $data['sectors']);
        ag_json(['ok' => true, 'id' => $newId], 201);
    }
    if ($action === 'update') {
        $rid = $id ?: (int)($data['id'] ?? 0);
        if (!$rid) ag_err('ID requerido');
        $errs = Agency::validate($data, false);
        if ($errs) ag_err(implode('; ', $errs));
        $ok = Agency::update($rid, $data);
        if (array_key_exists('sectors', $data)) Agency::syncSectors($rid, $data['sectors'] ?? []);
        ag_json(['ok' => $ok]);
    }
    if ($action === 'delete') {
        $rid = $id ?: (int)($data['id'] ?? 0);
        if (!$rid) ag_err('ID requerido');
        ag_json(['ok' => Agency::delete($rid)]);
    }
    if ($action === 'sync_sectors') {
        $rid = $id ?: (int)($data['id'] ?? 0);
        if (!$rid) ag_err('ID requerido');
        ag_json(['ok' => Agency::syncSectors($rid, $data['sectors'] ?? [])]);
    }
    ag_err('Accion no reconocida');
}
ag_err('Metodo no permitido', 405);
