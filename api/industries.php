<?php
/**
 * API Módulo Industrias — CRUD para usuarios registrados
 *
 * GET    /api/industries.php                     → lista industrias del usuario (o todas si admin)
 * GET    /api/industries.php?id=N                → detalle de una industria
 * POST   /api/industries.php?action=create       → crear industria (usuario autenticado)
 * POST   /api/industries.php?action=update&id=N  → actualizar industria (owner o admin)
 * POST   /api/industries.php?action=delete&id=N  → eliminar industria (owner o admin)
 * POST   /api/industries.php?action=archive&id=N → archivar industria (owner o admin)
 *
 * Filtros GET: ?status=activa&sector_id=N&search=texto&limit=50&offset=0
 */

ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');
session_start();

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../models/Industry.php';

use App\Models\Industry;

// ── Helpers locales ───────────────────────────────────────────────────────────

function ind_ok($data, string $msg = 'OK'): void {
    echo json_encode(['success' => true, 'data' => $data, 'message' => $msg]);
    exit;
}

function ind_err(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

function ind_input(): array {
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($ct, 'application/json') !== false) {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }
    return $_POST;
}

function ind_require_auth(): int {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) ind_err('Se requiere autenticación', 401);
    return $userId;
}

// ── Verificar tabla ───────────────────────────────────────────────────────────

try {
    $db = \Core\Database::getInstance()->getConnection();
} catch (Throwable $e) {
    ind_err('Base de datos no disponible', 503);
}

try {
    $db->query("SELECT 1 FROM industries LIMIT 1");
} catch (Throwable $e) {
    ind_err('Tabla industries no encontrada. Ejecutar migrations/015_industries.sql', 503);
}

// ── Router ────────────────────────────────────────────────────────────────────

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ── GET ───────────────────────────────────────────────────────────────────────

if ($method === 'GET') {
    // Detalle por ID (sin autenticación requerida para ver)
    if ($id > 0) {
        $industry = Industry::getById($id);
        if (!$industry) ind_err('Industria no encontrada', 404);

        // Usuarios no admin solo pueden ver sus propias industrias
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId > 0 && !isAdmin() && (int)$industry['user_id'] !== $userId) {
            ind_err('Acceso no autorizado', 403);
        }

        ind_ok($industry);
    }

    // Lista: usuario ve solo las suyas; admin puede ver todas o filtrar por user_id
    $userId  = (int)($_SESSION['user_id'] ?? 0);
    $filters = [];

    if ($userId > 0 && !isAdmin()) {
        $filters['user_id'] = $userId;
    } elseif (!empty($_GET['user_id'])) {
        $filters['user_id'] = (int)$_GET['user_id'];
    }

    if (!empty($_GET['status']))    $filters['status']               = $_GET['status'];
    if (!empty($_GET['sector_id'])) $filters['industrial_sector_id'] = (int)$_GET['sector_id'];
    if (!empty($_GET['search']))    $filters['search']               = $_GET['search'];

    $limit  = max(1, min(200, (int)($_GET['limit']  ?? 100)));
    $offset = max(0, (int)($_GET['offset'] ?? 0));

    $industries = Industry::getAll($filters, $limit, $offset);
    $total      = Industry::count($filters);

    ind_ok(['items' => $industries, 'total' => $total, 'limit' => $limit, 'offset' => $offset]);
}

// ── POST ──────────────────────────────────────────────────────────────────────

if ($method === 'POST') {
    $userId = ind_require_auth();
    $input  = ind_input();

    // Soportar id por GET o body
    if ($id <= 0 && isset($input['id'])) $id = (int)$input['id'];

    // ── Crear ─────────────────────────────────────────────────────────────────
    if ($action === 'create') {
        $input['user_id'] = $userId;

        $input = ind_sanitize($input);
        $errors = Industry::validate($input, true);
        if ($errors) ind_err(implode(' ', $errors));

        $newId = Industry::create($input);
        if ($newId === false) ind_err('Error al crear la industria', 500);

        $industry = Industry::getById($newId);
        ind_ok($industry, 'Industria creada correctamente');
    }

    // ── Actualizar ────────────────────────────────────────────────────────────
    if ($action === 'update') {
        if ($id <= 0) ind_err('id requerido para actualizar');

        $existing = Industry::getById($id);
        if (!$existing) ind_err('Industria no encontrada', 404);

        if (!isAdmin() && (int)$existing['user_id'] !== $userId) {
            ind_err('No tenés permiso para editar esta industria', 403);
        }

        $input = ind_sanitize($input);
        unset($input['user_id']); // no permitir cambiar dueño vía API
        $errors = Industry::validate($input, false);
        if ($errors) ind_err(implode(' ', $errors));

        $ok = Industry::update($id, $input);
        if (!$ok) ind_err('Error al actualizar la industria', 500);

        $industry = Industry::getById($id);
        ind_ok($industry, 'Industria actualizada correctamente');
    }

    // ── Archivar ──────────────────────────────────────────────────────────────
    if ($action === 'archive') {
        if ($id <= 0) ind_err('id requerido para archivar');

        $existing = Industry::getById($id);
        if (!$existing) ind_err('Industria no encontrada', 404);

        if (!isAdmin() && (int)$existing['user_id'] !== $userId) {
            ind_err('No tenés permiso para archivar esta industria', 403);
        }

        $ok = Industry::archive($id);
        if (!$ok) ind_err('Error al archivar la industria', 500);

        ind_ok(['id' => $id], 'Industria archivada correctamente');
    }

    // ── Eliminar ──────────────────────────────────────────────────────────────
    if ($action === 'delete') {
        if ($id <= 0) ind_err('id requerido para eliminar');

        $existing = Industry::getById($id);
        if (!$existing) ind_err('Industria no encontrada', 404);

        if (!isAdmin() && (int)$existing['user_id'] !== $userId) {
            ind_err('No tenés permiso para eliminar esta industria', 403);
        }

        $ok = Industry::delete($id);
        if (!$ok) ind_err('Error al eliminar la industria', 500);

        ind_ok(['id' => $id], 'Industria eliminada correctamente');
    }

    ind_err('Acción no reconocida', 400);
}

ind_err('Método no soportado', 405);

// ── Sanitizar input ───────────────────────────────────────────────────────────

function ind_sanitize(array $d): array {
    $trim = static function (?string $v, int $max = 255): ?string {
        if ($v === null || $v === '') return null;
        return mb_substr(trim($v), 0, $max);
    };

    if (isset($d['name']))             $d['name']             = $trim($d['name'], 255);
    if (isset($d['description']))      $d['description']      = $trim($d['description'], 5000);
    if (isset($d['website']))          $d['website']          = $trim($d['website'], 500);
    if (isset($d['contact_email']))    $d['contact_email']    = $trim($d['contact_email'], 255);
    if (isset($d['contact_phone']))    $d['contact_phone']    = $trim($d['contact_phone'], 50);
    if (isset($d['country']))          $d['country']          = $trim($d['country'], 100);
    if (isset($d['region']))           $d['region']           = $trim($d['region'], 100);
    if (isset($d['city']))             $d['city']             = $trim($d['city'], 100);
    if (isset($d['certifications']))   $d['certifications']   = $trim($d['certifications'], 1000);
    if (isset($d['naics_code']))       $d['naics_code']       = $trim($d['naics_code'], 20);
    if (isset($d['isic_code']))        $d['isic_code']        = $trim($d['isic_code'], 20);
    if (isset($d['nace_code']))        $d['nace_code']        = $trim($d['nace_code'], 20);
    if (isset($d['ciiu_code']))        $d['ciiu_code']        = $trim($d['ciiu_code'], 20);
    if (isset($d['language_code']))    $d['language_code']    = $trim($d['language_code'], 5);
    if (isset($d['currency_code']))    $d['currency_code']    = $trim($d['currency_code'], 3);
    if (isset($d['country_code'])) {
        $cc = strtoupper(trim($d['country_code'] ?? ''));
        $d['country_code'] = (preg_match('/^[A-Z]{2}$/', $cc)) ? $cc : null;
    }
    if (isset($d['industrial_sector_id'])) $d['industrial_sector_id'] = ($d['industrial_sector_id'] !== '' && $d['industrial_sector_id'] !== null) ? (int)$d['industrial_sector_id'] : null;
    if (isset($d['business_id']))      $d['business_id']      = ($d['business_id'] !== '' && $d['business_id'] !== null) ? (int)$d['business_id'] : null;
    if (isset($d['brand_id']))         $d['brand_id']         = ($d['brand_id'] !== '' && $d['brand_id'] !== null) ? (int)$d['brand_id'] : null;

    return $d;
}
