<?php
/**
 * API Módulo Disponibles — solicitudes de usuarios
 *
 * POST /api/disponibles_solicitudes.php?action=crear     → crea solicitud + detalle
 * POST /api/disponibles_solicitudes.php?action=confirmar → confirma orden
 * POST /api/disponibles_solicitudes.php?action=desistir  → desiste de la solicitud
 * GET  /api/disponibles_solicitudes.php?business_id=N    → lista solicitudes (titular)
 */

ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');
session_start();

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../includes/db_helper.php';

function sol_ok($data, $msg = 'OK') {
    echo json_encode(['success' => true, 'data' => $data, 'message' => $msg]);
    exit;
}
function sol_err($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}
function sol_get_input() {
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($ct, 'application/json') !== false) {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }
    return $_POST;
}

$db = null;
try { $db = \Core\Database::getInstance()->getConnection(); }
catch (Throwable $e) { sol_err('Base de datos no disponible', 503); }

$method     = $_SERVER['REQUEST_METHOD'];
$action     = $_GET['action'] ?? ($_POST['action'] ?? 'list');
$businessId = (int)($_GET['business_id'] ?? 0);

// ── GET: lista de solicitudes (solo titular) ──────────────────────────────────
if ($method === 'GET') {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) sol_err('Se requiere autenticación', 401);
    if ($businessId <= 0) sol_err('business_id requerido');
    if (!canManageBusiness($userId, $businessId)) sol_err('Sin permiso', 403);

    $st = $db->prepare(
        "SELECT s.*, COUNT(si.id) AS total_items, SUM(si.seleccionado) AS items_seleccionados
         FROM disponibles_solicitudes s
         LEFT JOIN disponibles_solicitud_items si ON si.solicitud_id = s.id
         WHERE s.business_id = ?
         GROUP BY s.id
         ORDER BY s.created_at DESC"
    );
    $st->execute([$businessId]);
    $solicitudes = $st->fetchAll(\PDO::FETCH_ASSOC);

    // Para cada solicitud obtenemos el detalle de ítems
    foreach ($solicitudes as &$sol) {
        $stI = $db->prepare(
            "SELECT si.item_id, si.seleccionado,
                    di.tipo_bien, di.servicio, di.precio, di.precio_a_definir,
                    di.cantidad
             FROM disponibles_solicitud_items si
             JOIN disponibles_items di ON di.id = si.item_id
             WHERE si.solicitud_id = ?
             ORDER BY di.orden ASC, di.id ASC"
        );
        $stI->execute([$sol['id']]);
        $sol['items'] = $stI->fetchAll(\PDO::FETCH_ASSOC);
    }
    unset($sol);

    sol_ok($solicitudes);
}

// ── POST ──────────────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $input = sol_get_input();

    // ── Crear solicitud ───────────────────────────────────────────────────────
    if ($action === 'crear') {
        $businessId = (int)($input['business_id'] ?? 0);
        if ($businessId <= 0) sol_err('business_id requerido');

        $email = trim((string)($input['email'] ?? ''));
        if ($email === '') sol_err('El email es obligatorio');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
            sol_err('Email inválido');
        }

        $itemsIds = $input['items_seleccionados'] ?? [];
        if (!is_array($itemsIds) || count($itemsIds) === 0) {
            sol_err('Debe seleccionar al menos un ítem');
        }
        $itemsIds = array_map('intval', $itemsIds);

        // Verificar que el módulo está activo
        $stB = $db->prepare("SELECT disponibles_activo, name, email AS owner_email, user_id FROM businesses WHERE id = ? LIMIT 1");
        $stB->execute([$businessId]);
        $biz = $stB->fetch(\PDO::FETCH_ASSOC);
        if (!$biz) sol_err('Negocio no encontrado', 404);
        if (!$biz['disponibles_activo']) sol_err('El módulo de disponibles no está activo', 403);

        // Verificar que los ítems pertenecen al negocio y están activos
        $placeholders = implode(',', array_fill(0, count($itemsIds), '?'));
        $stItems = $db->prepare(
            "SELECT id FROM disponibles_items
             WHERE id IN ($placeholders) AND business_id = ? AND activo = 1"
        );
        $stItems->execute(array_merge($itemsIds, [$businessId]));
        $validIds = array_column($stItems->fetchAll(\PDO::FETCH_ASSOC), 'id');
        $validIds = array_map('intval', $validIds);
        if (count($validIds) === 0) sol_err('Ningún ítem seleccionado es válido');

        // Obtener TODOS los ítems activos del negocio para marcar "no" al resto
        $stAll = $db->prepare("SELECT id FROM disponibles_items WHERE business_id = ? AND activo = 1");
        $stAll->execute([$businessId]);
        $allIds = array_map('intval', array_column($stAll->fetchAll(\PDO::FETCH_ASSOC), 'id'));

        $userId = (int)($_SESSION['user_id'] ?? 0);

        $db->beginTransaction();
        try {
            // Crear cabecera de solicitud
            $db->prepare(
                "INSERT INTO disponibles_solicitudes (business_id, user_id, email, estado)
                 VALUES (?, ?, ?, 'pendiente')"
            )->execute([$businessId, $userId > 0 ? $userId : null, $email]);
            $solicitudId = (int)$db->lastInsertId();

            // Insertar detalle: sí para seleccionados, no para el resto
            $insItem = $db->prepare(
                "INSERT INTO disponibles_solicitud_items (solicitud_id, item_id, seleccionado) VALUES (?,?,?)"
            );
            foreach ($allIds as $iid) {
                $insItem->execute([$solicitudId, $iid, in_array($iid, $validIds) ? 1 : 0]);
            }

            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            error_log('disponibles_solicitudes crear: ' . $e->getMessage());
            sol_err('Error al crear la solicitud', 500);
        }

        // Notificaciones (best-effort; no fallan la solicitud si hay error)
        disp_notify_requester($email, $biz['name'], $solicitudId);
        disp_notify_owner($biz, $email, $solicitudId, count($validIds));

        sol_ok(['solicitud_id' => $solicitudId], 'Solicitud creada. Recibirás un email con la orden.');
    }

    // ── Confirmar solicitud (actualiza estado a 'confirmada') ─────────────────
    if ($action === 'confirmar') {
        $solicitudId = (int)($input['solicitud_id'] ?? 0);
        if ($solicitudId <= 0) sol_err('solicitud_id requerido');

        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) sol_err('Se requiere autenticación', 401);

        // Verificar que la solicitud pertenece a un negocio que puede manejar
        $stS = $db->prepare("SELECT * FROM disponibles_solicitudes WHERE id = ? LIMIT 1");
        $stS->execute([$solicitudId]);
        $sol = $stS->fetch(\PDO::FETCH_ASSOC);
        if (!$sol) sol_err('Solicitud no encontrada', 404);
        if (!canManageBusiness($userId, (int)$sol['business_id'])) sol_err('Sin permiso', 403);
        if ($sol['estado'] !== 'pendiente') sol_err('La solicitud ya fue procesada');

        $db->prepare("UPDATE disponibles_solicitudes SET estado = 'confirmada' WHERE id = ?")
           ->execute([$solicitudId]);

        sol_ok(['solicitud_id' => $solicitudId], 'Solicitud confirmada');
    }

    // ── Desistir de una solicitud ─────────────────────────────────────────────
    if ($action === 'desistir') {
        $solicitudId = (int)($input['solicitud_id'] ?? 0);
        if ($solicitudId <= 0) sol_err('solicitud_id requerido');

        $userId = (int)($_SESSION['user_id'] ?? 0);
        $email  = trim((string)($input['email'] ?? ''));

        // El usuario puede desistir si: es el dueño del negocio, o su email coincide, o está logueado
        $stS = $db->prepare("SELECT * FROM disponibles_solicitudes WHERE id = ? LIMIT 1");
        $stS->execute([$solicitudId]);
        $sol = $stS->fetch(\PDO::FETCH_ASSOC);
        if (!$sol) sol_err('Solicitud no encontrada', 404);
        if ($sol['estado'] !== 'pendiente') sol_err('La solicitud ya fue procesada');

        $isOwner = $userId > 0 && canManageBusiness($userId, (int)$sol['business_id']);
        $isRequester = ($userId > 0 && (int)$sol['user_id'] === $userId)
                    || ($email !== '' && strtolower($sol['email']) === strtolower($email));
        if (!$isOwner && !$isRequester) sol_err('Sin permiso para cancelar esta solicitud', 403);

        $db->prepare("UPDATE disponibles_solicitudes SET estado = 'desistida' WHERE id = ?")
           ->execute([$solicitudId]);

        sol_ok(['solicitud_id' => $solicitudId], 'Solicitud cancelada');
    }

    sol_err('Acción no reconocida', 400);
}

sol_err('Método no soportado', 405);

// ── Notificaciones ────────────────────────────────────────────────────────────

/**
 * Envía email de confirmación al solicitante.
 */
function disp_notify_requester(string $email, string $bizName, int $solicitudId): void {
    $subject = "Orden de solicitud #{$solicitudId} — {$bizName}";
    $body    = "Hola,\n\n"
             . "Tu solicitud #{$solicitudId} en «{$bizName}» fue registrada correctamente.\n\n"
             . "El titular del negocio se comunicará contigo a la brevedad para confirmar los detalles.\n\n"
             . "— Equipo Mapita";
    $headers = "From: noreply@mapita.com.ar\r\nContent-Type: text/plain; charset=UTF-8";
    @mail($email, $subject, $body, $headers);
}

/**
 * Notifica al titular por email y por WT.
 */
function disp_notify_owner(array $biz, string $requesterEmail, int $solicitudId, int $itemCount): void {
    $ownerEmail = trim((string)($biz['owner_email'] ?? ''));
    // Sanitizar el email para evitar inyección en cabeceras/cuerpo
    $safeRequesterEmail = str_replace(["\r", "\n", "%0a", "%0d"], '', $requesterEmail);
    if ($ownerEmail !== '') {
        $subject = "Nueva solicitud #{$solicitudId} en «{$biz['name']}»";
        $body    = "Hola,\n\n"
                 . "El usuario {$safeRequesterEmail} hizo una solicitud (#{$solicitudId}) "
                 . "con {$itemCount} ítem(s) en tu panel de disponibles.\n\n"
                 . "Accedé a tu panel de edición en Mapita para ver los detalles y confirmar.\n\n"
                 . "— Equipo Mapita";
        $headers = "From: noreply@mapita.com.ar\r\nContent-Type: text/plain; charset=UTF-8";
        @mail($ownerEmail, $subject, $body, $headers);
    }

    // Notificación WT al propietario del negocio
    $ownerId = (int)($biz['user_id'] ?? 0);
    if ($ownerId > 0) {
        try {
            $db2 = \Core\Database::getInstance()->getConnection();
            $db2->prepare(
                "INSERT INTO wt_messages
                 (entity_type, entity_id, user_id, user_name, sender_key, message)
                 VALUES ('negocio', ?, 0, 'Mapita', 'system', ?)"
            )->execute([
                $biz['id'] ?? 0,
                "📦 Nueva solicitud #{$solicitudId} de {$requesterEmail} ({$itemCount} ítem(s))"
            ]);
        } catch (Throwable $e) {
            error_log('disp_notify_owner WT: ' . $e->getMessage());
        }
    }
}
