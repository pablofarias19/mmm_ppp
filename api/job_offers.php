<?php
/**
 * API Módulo Busco Empleados/as — oferta laboral del negocio
 *
 * GET  /api/job_offers.php?business_id=N          → oferta activa (público)
 * POST /api/job_offers.php?action=save             → guarda/actualiza campos (titular)
 * POST /api/job_offers.php?action=toggle           → activa/desactiva oferta (titular)
 */

ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');
session_start();

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../includes/db_helper.php';

function job_ok($data, $msg = 'OK') {
    echo json_encode(['success' => true, 'data' => $data, 'message' => $msg]);
    exit;
}
function job_err($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}
function job_input() {
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($ct, 'application/json') !== false) {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }
    return $_POST;
}

$db = null;
try { $db = \Core\Database::getInstance()->getConnection(); }
catch (Throwable $e) { job_err('Base de datos no disponible', 503); }

$method     = $_SERVER['REQUEST_METHOD'];
$action     = $_GET['action'] ?? 'get';
$businessId = (int)($_GET['business_id'] ?? 0);

// Verificar que las columnas existen (migraciones)
if (!mapitaColumnExists($db, 'businesses', 'job_offer_active')) {
    if ($method === 'GET') {
        job_ok(['job_offer_active' => false, 'job_offer_position' => null,
                'job_offer_description' => null, 'job_offer_url' => null],
               'Módulo no inicializado (ejecutar migrations/013_job_offers.sql)');
    }
    job_err('Módulo no inicializado. Ejecutá migrations/013_job_offers.sql', 503);
}

// ── GET: oferta pública ───────────────────────────────────────────────────────
if ($method === 'GET') {
    if ($businessId <= 0) job_err('business_id requerido');

    $st = $db->prepare(
        "SELECT id, name, job_offer_active, job_offer_position, job_offer_description, job_offer_url
         FROM businesses WHERE id = ? LIMIT 1"
    );
    $st->execute([$businessId]);
    $biz = $st->fetch(\PDO::FETCH_ASSOC);
    if (!$biz) job_err('Negocio no encontrado', 404);

    $userId  = (int)($_SESSION['user_id'] ?? 0);
    $isOwner = canManageBusiness($userId, $businessId);

    if (!$biz['job_offer_active'] && !$isOwner) {
        job_err('No hay oferta laboral activa en este negocio', 404);
    }

    // Número de postulaciones (solo para titular)
    $appCount = null;
    if ($isOwner && mapitaTableExists($db, 'job_applications')) {
        $stC = $db->prepare("SELECT COUNT(*) FROM job_applications WHERE business_id = ?");
        $stC->execute([$businessId]);
        $appCount = (int)$stC->fetchColumn();
    }

    job_ok([
        'business_name'       => $biz['name'],
        'job_offer_active'    => (bool)$biz['job_offer_active'],
        'job_offer_position'  => $biz['job_offer_position'],
        'job_offer_description' => $biz['job_offer_description'],
        'job_offer_url'       => $biz['job_offer_url'],
        'app_count'           => $appCount,
    ]);
}

// ── POST: acciones del titular ────────────────────────────────────────────────
if ($method === 'POST') {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) job_err('Se requiere autenticación', 401);

    $input      = job_input();
    $businessId = (int)($input['business_id'] ?? $businessId);
    if ($businessId <= 0) job_err('business_id requerido');

    if (!canManageBusiness($userId, $businessId)) {
        job_err('Sin permiso para gestionar este negocio', 403);
    }

    // ── Toggle activo/inactivo ────────────────────────────────────────────────
    if ($action === 'toggle') {
        $activo = isset($input['activo']) ? ((int)(bool)$input['activo']) : null;
        if ($activo === null) job_err('Parámetro activo requerido');

        // Si se activa, verificar que haya posición
        if ($activo) {
            $stChk = $db->prepare("SELECT job_offer_position FROM businesses WHERE id = ? LIMIT 1");
            $stChk->execute([$businessId]);
            $row = $stChk->fetch(\PDO::FETCH_ASSOC);
            if (!$row || trim((string)($row['job_offer_position'] ?? '')) === '') {
                job_err('Antes de activar la oferta, completá al menos el puesto/posición buscada.');
            }
        }

        $db->prepare("UPDATE businesses SET job_offer_active = ? WHERE id = ?")
           ->execute([$activo, $businessId]);
        job_ok(['activo' => (bool)$activo], 'Oferta laboral ' . ($activo ? 'activada' : 'desactivada'));
    }

    // ── Guardar campos de oferta ──────────────────────────────────────────────
    if ($action === 'save') {
        $position    = mb_substr(trim((string)($input['job_offer_position']    ?? '')), 0, 255);
        $description = mb_substr(trim((string)($input['job_offer_description'] ?? '')), 0, 3000);
        $url         = trim((string)($input['job_offer_url'] ?? ''));

        if ($position === '') job_err('El puesto/posición es obligatorio.');

        if ($url !== '') {
            $parsed = filter_var($url, FILTER_VALIDATE_URL);
            if (!$parsed) job_err('La URL externa no es válida.');
            // Verificar protocolo seguro
            $scheme = parse_url($url, PHP_URL_SCHEME);
            if (!in_array(strtolower((string)$scheme), ['http', 'https'], true)) {
                job_err('La URL debe comenzar con http:// o https://');
            }
            if (mb_strlen($url) > 500) job_err('La URL no puede superar 500 caracteres.');
        }

        $db->prepare(
            "UPDATE businesses
             SET job_offer_position = ?, job_offer_description = ?, job_offer_url = ?
             WHERE id = ?"
        )->execute([
            $position ?: null,
            $description ?: null,
            $url ?: null,
            $businessId,
        ]);

        job_ok([
            'job_offer_position'    => $position ?: null,
            'job_offer_description' => $description ?: null,
            'job_offer_url'         => $url ?: null,
        ], 'Oferta laboral guardada correctamente');
    }

    job_err('Acción no reconocida', 400);
}

job_err('Método no soportado', 405);
