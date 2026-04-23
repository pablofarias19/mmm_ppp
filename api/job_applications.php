<?php
/**
 * API Módulo Busco Empleados/as — postulaciones
 *
 * POST /api/job_applications.php?action=create         → crea postulación (login obligatorio + rate limit + dedupe)
 * POST /api/job_applications.php?action=update_status  → cambia estado (titular + auditoría)
 * GET  /api/job_applications.php?business_id=N         → lista postulaciones (titular)
 */

ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');
session_start();

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../includes/db_helper.php';
require_once __DIR__ . '/../includes/rate_limiter.php';
require_once __DIR__ . '/../includes/audit_logger.php';

function ja_ok($data, $msg = 'OK') {
    echo json_encode(['success' => true, 'data' => $data, 'message' => $msg]);
    exit;
}
function ja_err($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}
function ja_input() {
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($ct, 'application/json') !== false) {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }
    return $_POST;
}

$db = null;
try { $db = \Core\Database::getInstance()->getConnection(); }
catch (Throwable $e) { ja_err('Base de datos no disponible', 503); }

// Verificar que la tabla existe (migraciones)
if (!mapitaTableExists($db, 'job_applications')) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        ja_ok([], 'Módulo no inicializado (ejecutar migrations/013_job_offers.sql)');
    }
    ja_err('Módulo no inicializado. Ejecutá migrations/013_job_offers.sql', 503);
}

$method     = $_SERVER['REQUEST_METHOD'];
$action     = $_GET['action'] ?? ($_POST['action'] ?? 'list');
$businessId = (int)($_GET['business_id'] ?? 0);

$allowedEstados = ['pendiente', 'vista', 'aceptada', 'rechazada'];

// ── GET: lista de postulaciones (solo titular) ────────────────────────────────
if ($method === 'GET') {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) ja_err('Se requiere autenticación', 401);
    if ($businessId <= 0) ja_err('business_id requerido');
    if (!canManageBusiness($userId, $businessId)) ja_err('Sin permiso', 403);

    $estado = $_GET['estado'] ?? '';
    $whereExtra = '';
    $params = [$businessId];
    if ($estado !== '' && in_array($estado, $allowedEstados, true)) {
        $whereExtra = ' AND ja.estado = ?';
        $params[]   = $estado;
    }

    $st = $db->prepare(
        "SELECT ja.id, ja.user_id, ja.applicant_name,
                ja.applicant_email, ja.applicant_phone,
                ja.message, ja.estado, ja.consent,
                ja.created_at, ja.updated_at
         FROM job_applications ja
         WHERE ja.business_id = ?{$whereExtra}
         ORDER BY ja.created_at DESC"
    );
    $st->execute($params);
    $apps = $st->fetchAll(\PDO::FETCH_ASSOC);

    // Enmascarar email y teléfono (se desenmascaran desde el panel si se solicita)
    foreach ($apps as &$app) {
        $app['applicant_email_masked'] = ja_mask_email($app['applicant_email']);
        $app['applicant_phone_masked'] = $app['applicant_phone'] ? ja_mask_phone($app['applicant_phone']) : null;
    }
    unset($app);

    ja_ok($apps);
}

// ── POST ──────────────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $input = ja_input();

    // ── Crear postulación ─────────────────────────────────────────────────────
    if ($action === 'create') {
        // Login obligatorio
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) ja_err('Debés iniciar sesión para postularte.', 401);

        // Rate limit: máx 5 postulaciones por IP en 10 minutos
        checkRateLimit('job_application_create', 5, 600);

        $businessId = (int)($input['business_id'] ?? 0);
        if ($businessId <= 0) ja_err('business_id requerido');

        // Verificar que la oferta está activa
        $stB = $db->prepare(
            "SELECT id, name, job_offer_active, job_offer_position, email AS owner_email, user_id AS owner_id
             FROM businesses WHERE id = ? LIMIT 1"
        );
        $stB->execute([$businessId]);
        $biz = $stB->fetch(\PDO::FETCH_ASSOC);
        if (!$biz) ja_err('Negocio no encontrado', 404);
        if (!$biz['job_offer_active']) ja_err('No hay oferta laboral activa en este negocio.', 403);

        // Anti-duplicado: un usuario no puede postularse dos veces al mismo negocio
        $stDup = $db->prepare(
            "SELECT id FROM job_applications WHERE business_id = ? AND user_id = ? LIMIT 1"
        );
        $stDup->execute([$businessId, $userId]);
        if ($stDup->fetchColumn()) {
            ja_err('Ya te postulaste a este negocio. Solo se permite una postulación por oferta.', 409);
        }

        // Validar campos
        $name    = mb_substr(trim((string)($input['applicant_name']  ?? '')), 0, 255);
        $email   = trim((string)($input['applicant_email'] ?? ''));
        $phone   = mb_substr(trim((string)($input['applicant_phone'] ?? '')), 0, 50);
        $message = mb_substr(trim((string)($input['message']         ?? '')), 0, 2000);
        $consent = !empty($input['consent']);

        if ($name === '')  ja_err('El nombre es obligatorio.');
        if ($email === '') ja_err('El email es obligatorio.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
            ja_err('El email no es válido.');
        }
        if (!$consent) ja_err('Debés aceptar el consentimiento para postularte.');

        if ($phone !== '' && !preg_match('/^[\d\s\+\-\(\)\.]{1,50}$/', $phone)) {
            ja_err('El teléfono contiene caracteres no válidos.');
        }

        $db->prepare(
            "INSERT INTO job_applications
             (business_id, user_id, applicant_name, applicant_email, applicant_phone, message, consent)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        )->execute([
            $businessId, $userId, $name, $email,
            $phone ?: null, $message ?: null, 1,
        ]);
        $appId = (int)$db->lastInsertId();

        auditLog('job_application_create', 'job_application', $appId, [
            'business_id' => $businessId,
            'user_id'     => $userId,
        ]);

        // Notificación al titular (best-effort)
        ja_notify_owner($biz, $name, $appId);

        ja_ok(['application_id' => $appId], 'Postulación enviada correctamente. ¡Buena suerte!');
    }

    // ── Cambiar estado (titular) ──────────────────────────────────────────────
    if ($action === 'update_status') {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) ja_err('Se requiere autenticación', 401);

        $appId    = (int)($input['application_id'] ?? 0);
        $newState = trim((string)($input['estado'] ?? ''));

        if ($appId <= 0) ja_err('application_id requerido');
        if (!in_array($newState, $allowedEstados, true)) ja_err('Estado no válido');

        // Verificar que la postulación existe y pertenece a un negocio gestionable
        $stA = $db->prepare("SELECT * FROM job_applications WHERE id = ? LIMIT 1");
        $stA->execute([$appId]);
        $app = $stA->fetch(\PDO::FETCH_ASSOC);
        if (!$app) ja_err('Postulación no encontrada', 404);
        if (!canManageBusiness($userId, (int)$app['business_id'])) ja_err('Sin permiso', 403);

        $oldState = $app['estado'];
        $db->prepare("UPDATE job_applications SET estado = ? WHERE id = ?")
           ->execute([$newState, $appId]);

        auditLog('job_application_status', 'job_application', $appId, [
            'business_id' => $app['business_id'],
            'old_estado'  => $oldState,
            'new_estado'  => $newState,
        ]);

        ja_ok(['application_id' => $appId, 'estado' => $newState], 'Estado actualizado');
    }

    ja_err('Acción no reconocida', 400);
}

ja_err('Método no soportado', 405);

// ── Helpers ───────────────────────────────────────────────────────────────────

function ja_mask_email(string $email): string {
    $parts = explode('@', $email, 2);
    if (count($parts) !== 2) return '***@***';
    $local  = $parts[0];
    $domain = $parts[1];
    $masked = mb_substr($local, 0, min(2, mb_strlen($local))) . str_repeat('*', max(0, mb_strlen($local) - 2));
    return $masked . '@' . $domain;
}

function ja_mask_phone(?string $phone): ?string {
    if ($phone === null || $phone === '') return null;
    $len = mb_strlen($phone);
    if ($len <= 4) return str_repeat('*', $len);
    return mb_substr($phone, 0, 2) . str_repeat('*', $len - 4) . mb_substr($phone, -2);
}

function ja_notify_owner(array $biz, string $applicantName, int $appId): void {
    $ownerEmail = trim((string)($biz['owner_email'] ?? ''));
    if ($ownerEmail !== '') {
        $subject = "Nueva postulación #{$appId} en «{$biz['name']}»";
        $body    = "Hola,\n\n"
                 . "{$applicantName} se postuló para la oferta laboral en «{$biz['name']}» (#{$appId}).\n\n"
                 . "Ingresá a tu panel de trabajo en Mapita para gestionar las postulaciones.\n\n"
                 . "— Equipo Mapita";
        $headers = "From: noreply@mapita.com.ar\r\nContent-Type: text/plain; charset=UTF-8";
        @mail($ownerEmail, $subject, $body, $headers);
    }
}
