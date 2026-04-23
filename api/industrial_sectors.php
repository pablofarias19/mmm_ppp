<?php
/**
 * API Módulo Sectores Industriales — CRUD completo
 *
 * GET    /api/industrial_sectors.php                   → lista sectores (público)
 * GET    /api/industrial_sectors.php?id=N              → detalle de un sector
 * POST   /api/industrial_sectors.php?action=create     → crear sector (admin)
 * POST   /api/industrial_sectors.php?action=update     → actualizar sector (admin)
 * POST   /api/industrial_sectors.php?action=delete     → eliminar sector (admin)
 *
 * Filtros GET: ?type=mineria&status=activo&limit=50&offset=0
 */

ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');
session_start();

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';

// Autoload simple del modelo
require_once __DIR__ . '/../models/IndustrialSector.php';

use App\Models\IndustrialSector;

// ── Helpers ──────────────────────────────────────────────────────────────────

function is_ok($data, string $msg = 'OK'): void {
    echo json_encode(['success' => true, 'data' => $data, 'message' => $msg]);
    exit;
}

function is_err(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

function is_input(): array {
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($ct, 'application/json') !== false) {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }
    return $_POST;
}

function is_require_admin(): void {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) is_err('Se requiere autenticación', 401);
    if (!isAdmin()) is_err('Acceso restringido a administradores', 403);
}

// ── Verificar tabla ───────────────────────────────────────────────────────────

try {
    $db = \Core\Database::getInstance()->getConnection();
} catch (Throwable $e) {
    is_err('Base de datos no disponible', 503);
}

// Verificar que la tabla existe
try {
    $chk = $db->query("SELECT 1 FROM industrial_sectors LIMIT 1");
} catch (Throwable $e) {
    is_err('Tabla industrial_sectors no encontrada. Ejecutar migrations/014_industrial_sectors.sql', 503);
}

// ── Router ────────────────────────────────────────────────────────────────────

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ── GET: lista o detalle ──────────────────────────────────────────────────────
if ($method === 'GET') {
    if ($id > 0) {
        $sector = IndustrialSector::getById($id);
        if (!$sector) is_err('Sector industrial no encontrado', 404);
        // Decodificar geometry para devolver como objeto
        if (isset($sector['geometry']) && is_string($sector['geometry'])) {
            $sector['geometry'] = json_decode($sector['geometry'], true);
        }
        is_ok($sector);
    }

    // Lista con filtros opcionales
    $filters = [];
    if (!empty($_GET['type']))   $filters['type']   = $_GET['type'];
    if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
    $limit  = max(1, min(500, (int)($_GET['limit']  ?? 100)));
    $offset = max(0, (int)($_GET['offset'] ?? 0));

    $sectors = IndustrialSector::getAll($filters, $limit, $offset);

    // Decodificar geometry en cada sector
    foreach ($sectors as &$s) {
        if (isset($s['geometry']) && is_string($s['geometry'])) {
            $s['geometry'] = json_decode($s['geometry'], true);
        }
    }
    unset($s);

    is_ok($sectors);
}

// ── POST: operaciones de escritura (solo admin) ───────────────────────────────
if ($method === 'POST') {
    is_require_admin();

    $input = is_input();
    // Soportar id por GET o por body
    if ($id <= 0 && isset($input['id'])) $id = (int)$input['id'];

    // ── Crear ─────────────────────────────────────────────────────────────────
    if ($action === 'create') {
        $errors = IndustrialSector::validate($input, true);
        if ($errors) is_err(implode(' ', $errors));

        // Normalizar geometry a string JSON
        $geo = $input['geometry'];
        if (is_array($geo)) $geo = json_encode($geo);
        $input['geometry'] = $geo;

        // Limpiar campos opcionales
        $input['subtype']      = mb_substr(trim((string)($input['subtype']      ?? '')), 0, 100) ?: null;
        $input['jurisdiction'] = mb_substr(trim((string)($input['jurisdiction'] ?? '')), 0, 255) ?: null;
        $input['description']  = trim((string)($input['description'] ?? '')) ?: null;
        $input['name']         = mb_substr(trim((string)$input['name']), 0, 255);

        $newId = IndustrialSector::create($input);
        if ($newId === false) is_err('Error al crear el sector industrial', 500);

        $sector = IndustrialSector::getById($newId);
        if ($sector && is_string($sector['geometry'])) {
            $sector['geometry'] = json_decode($sector['geometry'], true);
        }
        is_ok($sector, 'Sector industrial creado correctamente');
    }

    // ── Actualizar ────────────────────────────────────────────────────────────
    if ($action === 'update') {
        if ($id <= 0) is_err('id requerido para actualizar');

        $existing = IndustrialSector::getById($id);
        if (!$existing) is_err('Sector industrial no encontrado', 404);

        $errors = IndustrialSector::validate($input, false);
        if ($errors) is_err(implode(' ', $errors));

        // Normalizar geometry si viene
        if (isset($input['geometry'])) {
            $geo = $input['geometry'];
            if (is_array($geo)) $geo = json_encode($geo);
            $input['geometry'] = $geo;
        }

        // Limpiar strings
        if (isset($input['name']))         $input['name']         = mb_substr(trim((string)$input['name']), 0, 255);
        if (isset($input['subtype']))      $input['subtype']      = mb_substr(trim((string)$input['subtype']), 0, 100) ?: null;
        if (isset($input['jurisdiction'])) $input['jurisdiction'] = mb_substr(trim((string)$input['jurisdiction']), 0, 255) ?: null;
        if (isset($input['description']))  $input['description']  = trim((string)$input['description']) ?: null;

        $ok = IndustrialSector::update($id, $input);
        if (!$ok) is_err('Error al actualizar el sector industrial', 500);

        $sector = IndustrialSector::getById($id);
        if ($sector && is_string($sector['geometry'])) {
            $sector['geometry'] = json_decode($sector['geometry'], true);
        }
        is_ok($sector, 'Sector industrial actualizado correctamente');
    }

    // ── Eliminar ──────────────────────────────────────────────────────────────
    if ($action === 'delete') {
        if ($id <= 0) is_err('id requerido para eliminar');

        $existing = IndustrialSector::getById($id);
        if (!$existing) is_err('Sector industrial no encontrado', 404);

        $ok = IndustrialSector::delete($id);
        if (!$ok) is_err('Error al eliminar el sector industrial', 500);

        is_ok(['id' => $id], 'Sector industrial eliminado correctamente');
    }

    is_err('Acción no reconocida', 400);
}

is_err('Método no soportado', 405);
