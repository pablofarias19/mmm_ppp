<?php
/**
 * API Cámaras — CRUD + vincular sectores
 *
 * GET    /api/chambers.php                         → lista
 * GET    /api/chambers.php?id=N                    → detalle
 * GET    /api/chambers.php?sector_type=X&sector_id=N → camaras de un sector
 * POST   /api/chambers.php?action=create           → crear (admin)
 * POST   /api/chambers.php?action=update           → actualizar (admin)
 * POST   /api/chambers.php?action=delete           → eliminar (admin)
 * POST   /api/chambers.php?action=sync_sectors     → sincronizar sectores (admin)
 */
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');
session_start();

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../models/Chamber.php';

use App\Models\Chamber;

function ch_json(array $data, int $code = 200): void {
    http_response_code($code); echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit;
}
function ch_err(string $msg, int $code = 400): void { ch_json(['error' => $msg], $code); }
function ch_isAdmin(): bool { return !empty($_SESSION['is_admin']); }

try { \Core\Database::getInstance()->getConnection(); }
catch (Throwable $e) { ch_err('Base de datos no disponible', 503); }

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($method === 'GET') {
    if ($id > 0) {
        $row = Chamber::getById($id);
        if (!$row) ch_err('Camara no encontrada', 404);
        $row['sectors'] = Chamber::getSectors($id);
        ch_json($row);
    }
    if (!empty($_GET['sector_type']) && !empty($_GET['sector_id'])) {
        $rows = Chamber::getBySector($_GET['sector_type'], (int)$_GET['sector_id']);
        ch_json(['data' => $rows]);
    }
    $filters = [];
    if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
    if (!empty($_GET['area']))   $filters['area']   = $_GET['area'];
    $limit  = min((int)($_GET['limit']  ?? 100), 200);
    $offset = max((int)($_GET['offset'] ?? 0), 0);
    $rows = Chamber::getAll($filters, $limit, $offset);
    ch_json(['data' => $rows, 'total' => count($rows)]);
}

if ($method === 'POST') {
    if (!ch_isAdmin()) ch_err('Acceso denegado', 403);
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true) ?? $_POST;

    if ($action === 'create') {
        $errs = Chamber::validate($data);
        if ($errs) ch_err(implode('; ', $errs));
        $newId = Chamber::create($data);
        if (!$newId) ch_err('Error al crear camara', 500);
        if (!empty($data['sectors'])) Chamber::syncSectors($newId, $data['sectors']);
        ch_json(['ok' => true, 'id' => $newId], 201);
    }
    if ($action === 'update') {
        $rid = $id ?: (int)($data['id'] ?? 0);
        if (!$rid) ch_err('ID requerido');
        $errs = Chamber::validate($data, false);
        if ($errs) ch_err(implode('; ', $errs));
        $ok = Chamber::update($rid, $data);
        if (array_key_exists('sectors', $data)) Chamber::syncSectors($rid, $data['sectors'] ?? []);
        ch_json(['ok' => $ok]);
    }
    if ($action === 'delete') {
        $rid = $id ?: (int)($data['id'] ?? 0);
        if (!$rid) ch_err('ID requerido');
        ch_json(['ok' => Chamber::delete($rid)]);
    }
    if ($action === 'sync_sectors') {
        $rid = $id ?: (int)($data['id'] ?? 0);
        if (!$rid) ch_err('ID requerido');
        $ok = Chamber::syncSectors($rid, $data['sectors'] ?? []);
        ch_json(['ok' => $ok]);
    }
    ch_err('Accion no reconocida');
}
ch_err('Metodo no permitido', 405);
