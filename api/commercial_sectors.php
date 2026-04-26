<?php
/**
 * API Sectores Comerciales — CRUD completo
 *
 * GET    /api/commercial_sectors.php            → lista sectores
 * GET    /api/commercial_sectors.php?id=N       → detalle
 * POST   /api/commercial_sectors.php?action=create → crear (admin)
 * POST   /api/commercial_sectors.php?action=update → actualizar (admin)
 * POST   /api/commercial_sectors.php?action=delete → eliminar (admin)
 */
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');
session_start();

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../models/CommercialSector.php';

use App\Models\CommercialSector;

function cs_json(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function cs_err(string $msg, int $code = 400): void { cs_json(['error' => $msg], $code); }
function cs_isAdmin(): bool { return !empty($_SESSION['is_admin']); }

try { \Core\Database::getInstance()->getConnection(); }
catch (Throwable $e) { cs_err('Base de datos no disponible', 503); }

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($method === 'GET') {
    if ($id > 0) {
        $row = CommercialSector::getById($id);
        if (!$row) cs_err('Sector no encontrado', 404);
        cs_json($row);
    }
    $filters = [];
    if (!empty($_GET['type']))   $filters['type']   = $_GET['type'];
    if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
    $limit  = min((int)($_GET['limit']  ?? 100), 200);
    $offset = max((int)($_GET['offset'] ?? 0), 0);
    $rows = CommercialSector::getAll($filters, $limit, $offset);
    cs_json(['data' => $rows, 'total' => count($rows)]);
}

if ($method === 'POST') {
    if (!cs_isAdmin()) cs_err('Acceso denegado', 403);
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true) ?? $_POST;

    if ($action === 'create') {
        $errs = CommercialSector::validate($data);
        if ($errs) cs_err(implode('; ', $errs));
        $newId = CommercialSector::create($data);
        if (!$newId) cs_err('Error al crear sector', 500);
        cs_json(['ok' => true, 'id' => $newId], 201);
    }
    if ($action === 'update') {
        $rid = $id ?: (int)($data['id'] ?? 0);
        if (!$rid) cs_err('ID requerido');
        $errs = CommercialSector::validate($data, false);
        if ($errs) cs_err(implode('; ', $errs));
        $ok = CommercialSector::update($rid, $data);
        cs_json(['ok' => $ok]);
    }
    if ($action === 'delete') {
        $rid = $id ?: (int)($data['id'] ?? 0);
        if (!$rid) cs_err('ID requerido');
        $ok = CommercialSector::delete($rid);
        cs_json(['ok' => $ok]);
    }
    cs_err('Acción no reconocida');
}
cs_err('Método no permitido', 405);
