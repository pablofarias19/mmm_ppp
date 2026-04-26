<?php
/**
 * API Competencias / Mapa de Facultades — CRUD
 *
 * GET    /api/competencies.php                             → lista
 * GET    /api/competencies.php?id=N                       → detalle
 * GET    /api/competencies.php?sector_type=X&sector_id=N  → competencias de un sector
 * POST   /api/competencies.php?action=create              → crear (admin)
 * POST   /api/competencies.php?action=update              → actualizar (admin)
 * POST   /api/competencies.php?action=delete              → eliminar (admin)
 */
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');
session_start();

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../models/Competency.php';

use App\Models\Competency;

function comp_json(array $data, int $code = 200): void {
    http_response_code($code); echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit;
}
function comp_err(string $msg, int $code = 400): void { comp_json(['error' => $msg], $code); }
function comp_isAdmin(): bool { return !empty($_SESSION['is_admin']); }

try { \Core\Database::getInstance()->getConnection(); }
catch (Throwable $e) { comp_err('Base de datos no disponible', 503); }

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($method === 'GET') {
    if ($id > 0) {
        $row = Competency::getById($id);
        if (!$row) comp_err('Competencia no encontrada', 404);
        comp_json($row);
    }
    if (!empty($_GET['sector_type']) && !empty($_GET['sector_id'])) {
        $rows = Competency::getBySector($_GET['sector_type'], (int)$_GET['sector_id']);
        comp_json(['data' => $rows]);
    }
    $filters = [];
    foreach (['source_type','source_id','role'] as $k) {
        if (!empty($_GET[$k])) $filters[$k] = $_GET[$k];
    }
    $rows = Competency::getAll($filters);
    comp_json(['data' => $rows, 'total' => count($rows)]);
}

if ($method === 'POST') {
    if (!comp_isAdmin()) comp_err('Acceso denegado', 403);
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true) ?? $_POST;

    if ($action === 'create') {
        $errs = Competency::validate($data);
        if ($errs) comp_err(implode('; ', $errs));
        $newId = Competency::create($data);
        if (!$newId) comp_err('Error al crear competencia', 500);
        comp_json(['ok' => true, 'id' => $newId], 201);
    }
    if ($action === 'update') {
        $rid = $id ?: (int)($data['id'] ?? 0);
        if (!$rid) comp_err('ID requerido');
        $errs = Competency::validate($data, false);
        if ($errs) comp_err(implode('; ', $errs));
        comp_json(['ok' => Competency::update($rid, $data)]);
    }
    if ($action === 'delete') {
        $rid = $id ?: (int)($data['id'] ?? 0);
        if (!$rid) comp_err('ID requerido');
        comp_json(['ok' => Competency::delete($rid)]);
    }
    comp_err('Accion no reconocida');
}
comp_err('Metodo no permitido', 405);
