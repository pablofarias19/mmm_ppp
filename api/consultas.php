<?php
/**
 * API de CONSULTAS MASIVAS
 *
 * Acciones GET:
 *   preview          — cuántos negocios recibirían la consulta (sin guardar)
 *   thread           — hilo completo de una consulta (texto + respuestas)
 *   my_consultas     — consultas enviadas por el usuario actual
 *   pending          — consultas recibidas sin respuesta (para propietarios)
 *   reply_count      — número de respuestas nuevas desde un id dado (polling)
 *   rubros_proveedor — lista de business_type que tienen negocios "P"
 *
 * Acciones POST:
 *   send                    — crea y envía una consulta masiva
 *   reply                   — el propietario de un negocio responde
 *   mark_read               — marca una consulta como leída por el negocio
 *   toggle_proveedor        — activa/desactiva flag es_proveedor (dueño)
 *   toggle_consulta_habilitada — activa/desactiva flag consulta_habilitada (admin)
 */

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');
session_start();

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../includes/rate_limiter.php';
require_once __DIR__ . '/../includes/audit_logger.php';

// ─── Helpers ──────────────────────────────────────────────────────────────────

function cq_ok($data = [], string $msg = 'OK'): void {
    echo json_encode(['success' => true, 'data' => $data, 'message' => $msg]);
    exit;
}

function cq_err(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

function cq_input(): array {
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($ct, 'application/json') !== false) {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }
    return $_POST;
}

function cq_require_login(): int {
    $uid = (int)($_SESSION['user_id'] ?? 0);
    if ($uid <= 0) cq_err('Requiere registro para usar consultas masivas.', 401);
    return $uid;
}

/** Máximo de destinatarios por consulta. */
define('CQ_MAX_RECIPIENTS', 200);

/**
 * Tipos de negocio RESTRINGIDOS: solo participan en consultas masivas/generales
function cq_restricted_types(): array {
    return ['abogado', 'inmobiliaria', 'seguros'];
}

/**
 * Tipos de negocio que NO pueden activar la designación "P" Proveedor.
 * (servicios puros, salud, educación, etc.)
 */
function cq_non_supplier_types(): array {
    return [
        'agente_inpi', 'estudio_juridico', 'abogado', 'inmobiliaria', 'seguros',
        'productor_seguros', 'banco', 'clinica', 'hospital', 'farmacia',
        'medico_pediatra', 'medico_traumatologo', 'laboratorio', 'enfermeria',
        'asistencia_ancianos', 'psicologo', 'psicopedagogo', 'fonoaudiologo',
        'grafologo', 'educacion', 'academia', 'idiomas', 'escuela',
        'maestro_particular', 'arquitectura', 'ingenieria', 'ingenieria_civil',
        'electricista', 'gasista', 'contador', 'seguridad',
    ];
}

/**
 * Verifica que un negocio le pertenezca al usuario.
 */
function cq_own_business(\PDO $db, int $userId, int $businessId): bool {
    if ($userId <= 0 || $businessId <= 0) return false;
    $stmt = $db->prepare("SELECT 1 FROM businesses WHERE id = ? AND user_id = ? LIMIT 1");
    $stmt->execute([$businessId, $userId]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Resuelve la lista de business_id destinatarios según el tipo de consulta.
 *
 * Filtros soportados en $input:
 *   tipo        — 'masiva' | 'general' | 'global_proveedor' | 'envio'
 *   geo_bounds  — {north,south,east,west}  (masiva, envio)
 *   rubro       — business_type exacto     (global_proveedor)
 *   biz_type    — filtro opcional por business_type (masiva, general)
 *
 * Regla de tipos restringidos (masiva y general):
 *   abogado, inmobiliaria, seguros → excluidos SALVO que consulta_habilitada = 1
 */
function cq_resolve_recipients(\PDO $db, array $input): array {
    $tipo    = $input['tipo']    ?? '';
    $bounds  = $input['geo_bounds'] ?? null;
    $rubro   = trim($input['rubro']    ?? '');
    $bizType = trim($input['biz_type'] ?? '');   // filtro opcional

    // Cláusula para excluir tipos restringidos sin habilitación admin
    $restrictedTypes  = cq_restricted_types();
    $placeholders     = implode(',', array_fill(0, count($restrictedTypes), '?'));
    // La condición excluye los tipos restringidos salvo que consulta_habilitada = 1
    $excludeRestricted = "(b.business_type NOT IN ($placeholders) OR b.consulta_habilitada = 1)";

    switch ($tipo) {
        // ── CONSULTA MASIVA: negocios visibles dentro del área ────────────────
        case 'masiva': {
            if (!$bounds || !isset($bounds['north'], $bounds['south'], $bounds['east'], $bounds['west'])) {
                cq_err('geo_bounds requerido para consulta masiva.');
            }
            $params = [];
            $sql  = "SELECT b.id, b.name, b.business_type FROM businesses b
                     LEFT JOIN wt_user_preferences wup ON wup.user_id = b.user_id
                     WHERE b.visible = 1
                       AND b.lat BETWEEN ? AND ?
                       AND b.lng BETWEEN ? AND ?
                       AND (wup.wt_mode IS NULL OR wup.wt_mode != 'closed')
                       AND $excludeRestricted";
            $params = [
                (float)$bounds['south'],
                (float)$bounds['north'],
                (float)$bounds['west'],
                (float)$bounds['east'],
            ];
            foreach ($restrictedTypes as $rt) $params[] = $rt;

            if ($bizType !== '') {
                $sql .= ' AND b.business_type = ?';
                $params[] = $bizType;
            }
            $sql .= ' LIMIT ' . CQ_MAX_RECIPIENTS . '';
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        // ── CONSULTA GENERAL: servicios especiales habilitados por admin ──────
        // También respeta filtro opcional de business_type
        case 'general': {
            $params = [];
            $sql  = "SELECT b.id, b.name, b.business_type FROM businesses b
                     WHERE b.visible = 1
                       AND $excludeRestricted";
            foreach ($restrictedTypes as $rt) $params[] = $rt;

            if ($bizType !== '') {
                $sql .= ' AND b.business_type = ?';
                $params[] = $bizType;
            }
            // Para tipos restringidos ya filtramos por consulta_habilitada en excludeRestricted.
            // Para el resto, todos los negocios visibles del tipo elegido son destinatarios.
            $sql .= ' LIMIT ' . CQ_MAX_RECIPIENTS . '';
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        // ── CONSULTA GLOBAL PROVEEDORES: negocios P del rubro solicitado ──────
        case 'global_proveedor': {
            if ($rubro === '') cq_err('rubro requerido para consulta global proveedor.');
            $stmt = $db->prepare(
                "SELECT id, name, business_type FROM businesses
                  WHERE visible = 1 AND es_proveedor = 1 AND business_type = ?
                  LIMIT " . CQ_MAX_RECIPIENTS
            );
            $stmt->execute([$rubro]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        // ── CONSULTA ENVIO: transportistas dentro del área ────────────────────
        case 'envio': {
            if (!$bounds || !isset($bounds['north'], $bounds['south'], $bounds['east'], $bounds['west'])) {
                cq_err('geo_bounds requerido para consulta envío.');
            }
            $stmt = $db->prepare(
                "SELECT b.id, b.name, b.business_type FROM businesses b
                  WHERE b.visible = 1
                    AND b.business_type IN ('transporte','transportista','logistica','flota')
                    AND b.lat BETWEEN ? AND ?
                    AND b.lng BETWEEN ? AND ?
                  LIMIT " . CQ_MAX_RECIPIENTS
            );
            $stmt->execute([
                (float)$bounds['south'],
                (float)$bounds['north'],
                (float)$bounds['west'],
                (float)$bounds['east'],
            ]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        default:
            cq_err('Tipo de consulta no válido.');
    }
}

// ─── Bootstrap DB ─────────────────────────────────────────────────────────────
$db = null;
try {
    $db = \Core\Database::getInstance()->getConnection();
} catch (\Throwable $e) {
    cq_err('Base de datos no disponible.', 503);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? ($_POST['action'] ?? (cq_input()['action'] ?? 'ping'));

// ─── GET ──────────────────────────────────────────────────────────────────────
if ($method === 'GET') {

    // ── ping ──────────────────────────────────────────────────────────────────
    if ($action === 'ping') {
        cq_ok(['module' => 'consultas_masivas', 'status' => 'ok']);
    }

    // ── preview: cuántos negocios recibirían la consulta ──────────────────────
    if ($action === 'preview') {
        cq_require_login();
        $tipo    = trim($_GET['tipo'] ?? '');
        $rubro   = trim($_GET['rubro'] ?? '');
        $bizType = trim($_GET['biz_type'] ?? '');
        $bounds  = null;
        if (!empty($_GET['geo_bounds'])) {
            $bounds = json_decode($_GET['geo_bounds'], true);
        }
        $input = ['tipo' => $tipo, 'rubro' => $rubro, 'geo_bounds' => $bounds, 'biz_type' => $bizType];
        try {
            $recipients = cq_resolve_recipients($db, $input);
            cq_ok(['count' => count($recipients)]);
        } catch (\Throwable $e) {
            cq_err($e->getMessage());
        }
    }

    // ── thread: hilo completo de una consulta ─────────────────────────────────
    if ($action === 'thread') {
        $uid        = cq_require_login();
        $consultaId = (int)($_GET['consulta_id'] ?? 0);
        if ($consultaId <= 0) cq_err('consulta_id requerido.');

        // Verificar acceso: propietario de la consulta, admin, o negocio destinatario
        $stmtCm = $db->prepare("SELECT * FROM consultas_masivas WHERE id = ? LIMIT 1");
        $stmtCm->execute([$consultaId]);
        $consulta = $stmtCm->fetch(\PDO::FETCH_ASSOC);
        if (!$consulta) cq_err('Consulta no encontrada.', 404);

        $isOwner = ((int)$consulta['user_id'] === $uid);
        $isAdmin = isAdmin();

        // Verificar si es destinatario
        $isRecipient = false;
        if (!$isOwner && !$isAdmin) {
            $stmtD = $db->prepare(
                "SELECT 1 FROM consultas_destinatarios cd
                  JOIN businesses b ON b.id = cd.business_id
                  WHERE cd.consulta_id = ? AND b.user_id = ? LIMIT 1"
            );
            $stmtD->execute([$consultaId, $uid]);
            $isRecipient = (bool)$stmtD->fetchColumn();
        }
        if (!$isOwner && !$isAdmin && !$isRecipient) {
            cq_err('Sin acceso a esta consulta.', 403);
        }

        // Respuestas
        $stmtR = $db->prepare(
            "SELECT cr.id, cr.business_id, b.name AS business_name,
                    cr.texto, DATE_FORMAT(cr.created_at,'%d/%m/%Y %H:%i') AS created_at
               FROM consultas_respuestas cr
               JOIN businesses b ON b.id = cr.business_id
              WHERE cr.consulta_id = ?
              ORDER BY cr.id ASC"
        );
        $stmtR->execute([$consultaId]);
        $respuestas = $stmtR->fetchAll(\PDO::FETCH_ASSOC);

        // Destinatarios (solo para el remitente/admin)
        $destinatarios = [];
        if ($isOwner || $isAdmin) {
            $stmtDest = $db->prepare(
                "SELECT cd.business_id, b.name AS business_name,
                        cd.notificado, cd.leido_en
                   FROM consultas_destinatarios cd
                   JOIN businesses b ON b.id = cd.business_id
                  WHERE cd.consulta_id = ?"
            );
            $stmtDest->execute([$consultaId]);
            $destinatarios = $stmtDest->fetchAll(\PDO::FETCH_ASSOC);
        }

        $geo = $consulta['geo_bounds'] ? json_decode($consulta['geo_bounds'], true) : null;
        cq_ok([
            'consulta'      => [
                'id'         => (int)$consulta['id'],
                'tipo'       => $consulta['tipo'],
                'rubro'      => $consulta['rubro'],
                'texto'      => $consulta['texto'],
                'created_at' => date('d/m/Y H:i', strtotime($consulta['created_at'])),
                'geo_bounds' => $geo,
            ],
            'respuestas'     => $respuestas,
            'destinatarios'  => $destinatarios,
        ]);
    }

    // ── my_consultas: consultas enviadas por el usuario ────────────────────────
    if ($action === 'my_consultas') {
        $uid = cq_require_login();
        $stmt = $db->prepare(
            "SELECT cm.id, cm.tipo, cm.rubro, cm.texto,
                    DATE_FORMAT(cm.created_at,'%d/%m/%Y %H:%i') AS created_at,
                    (SELECT COUNT(*) FROM consultas_destinatarios WHERE consulta_id = cm.id) AS total_dest,
                    (SELECT COUNT(*) FROM consultas_respuestas    WHERE consulta_id = cm.id) AS total_resp
               FROM consultas_masivas cm
              WHERE cm.user_id = ?
              ORDER BY cm.id DESC
              LIMIT 50"
        );
        $stmt->execute([$uid]);
        cq_ok($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    // ── pending: consultas recibidas sin respuesta (para propietarios) ─────────
    if ($action === 'pending') {
        $uid = cq_require_login();
        $stmt = $db->prepare(
            "SELECT cm.id, cm.tipo, cm.rubro, cm.texto,
                    DATE_FORMAT(cm.created_at,'%d/%m/%Y %H:%i') AS created_at,
                    cd.business_id, b.name AS business_name,
                    cd.leido_en
               FROM consultas_destinatarios cd
               JOIN consultas_masivas cm ON cm.id  = cd.consulta_id
               JOIN businesses        b  ON b.id   = cd.business_id
              WHERE b.user_id = ?
                AND NOT EXISTS (
                    SELECT 1 FROM consultas_respuestas cr
                     WHERE cr.consulta_id  = cd.consulta_id
                       AND cr.business_id  = cd.business_id
                )
              ORDER BY cm.id DESC
              LIMIT 50"
        );
        $stmt->execute([$uid]);
        cq_ok($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    // ── reply_count: polling ligero para el panel minimizado ──────────────────
    if ($action === 'reply_count') {
        $uid     = cq_require_login();
        $sinceId = (int)($_GET['since_id'] ?? 0);
        // Contar respuestas nuevas a consultas enviadas por el usuario
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM consultas_respuestas cr
              JOIN consultas_masivas cm ON cm.id = cr.consulta_id
             WHERE cm.user_id = ? AND cr.id > ?"
        );
        $stmt->execute([$uid, $sinceId]);
        $count = (int)$stmt->fetchColumn();

        // También contar consultas recibidas no leídas (para propietarios)
        $stmtPending = $db->prepare(
            "SELECT COUNT(*) FROM consultas_destinatarios cd
              JOIN businesses b ON b.id = cd.business_id
             WHERE b.user_id = ? AND cd.leido_en IS NULL"
        );
        $stmtPending->execute([$uid]);
        $pendingCount = (int)$stmtPending->fetchColumn();

        cq_ok(['new_replies' => $count, 'pending_received' => $pendingCount]);
    }

    // ── rubros_proveedor: business_type que tienen al menos un negocio "P" ────
    if ($action === 'rubros_proveedor') {
        $stmt = $db->query(
            "SELECT DISTINCT business_type FROM businesses
              WHERE es_proveedor = 1 AND visible = 1
              ORDER BY business_type"
        );
        cq_ok($stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    // ── biz_types: business_type disponibles para filtrar masiva/general ──────
    if ($action === 'biz_types') {
        cq_require_login();
        // Devuelve tipos que tienen al menos 1 negocio visible
        // Los tipos restringidos se incluyen solo si tienen alguno con consulta_habilitada=1
        $restricted   = cq_restricted_types();
        $placeholders = implode(',', array_fill(0, count($restricted), '?'));
        $params = $restricted;
        $stmt = $db->prepare(
            "SELECT DISTINCT b.business_type FROM businesses b
              WHERE b.visible = 1
                AND (b.business_type NOT IN ($placeholders) OR b.consulta_habilitada = 1)
              ORDER BY b.business_type"
        );
        $stmt->execute($params);
        cq_ok($stmt->fetchAll(\PDO::FETCH_COLUMN));
    }
}

// ─── POST ─────────────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $input = cq_input();
    $action = $input['action'] ?? ($_GET['action'] ?? $action);

    // ── send: crear y enviar consulta ─────────────────────────────────────────
    if ($action === 'send') {
        // Rate limit: máx 5 consultas masivas por IP por hora
        checkRateLimit('consulta_send', 5, 3600);

        $uid  = cq_require_login();
        $tipo = trim($input['tipo'] ?? '');
        if (!in_array($tipo, ['masiva', 'general', 'global_proveedor', 'envio'], true)) {
            cq_err('Tipo de consulta no válido.');
        }

        $texto = mb_substr(trim($input['texto'] ?? ''), 0, 500);
        if ($texto === '') cq_err('El texto de la consulta es obligatorio.');

        $rubro   = mb_substr(trim($input['rubro']    ?? ''), 0, 100) ?: null;
        $bizType = mb_substr(trim($input['biz_type'] ?? ''), 0, 60)  ?: null;
        $bounds = $input['geo_bounds'] ?? null;
        if ($bounds && !is_array($bounds)) {
            $bounds = json_decode($bounds, true);
        }

        // Resolver destinatarios
        try {
            $recipients = cq_resolve_recipients($db, [
                'tipo'       => $tipo,
                'rubro'      => $rubro ?? '',
                'geo_bounds' => $bounds,
                'biz_type'   => $bizType ?? '',
            ]);
        } catch (\Throwable $e) {
            cq_err($e->getMessage());
        }

        if (empty($recipients)) {
            cq_err('No se encontraron negocios destinatarios para esta consulta.');
        }

        $db->beginTransaction();
        try {
            // Insertar cabecera de consulta
            $stmtIns = $db->prepare(
                "INSERT INTO consultas_masivas (user_id, tipo, rubro, geo_bounds, texto)
                  VALUES (?, ?, ?, ?, ?)"
            );
            $stmtIns->execute([
                $uid,
                $tipo,
                $rubro,
                $bounds ? json_encode($bounds) : null,
                $texto,
            ]);
            $consultaId = (int)$db->lastInsertId();

            // Insertar destinatarios
            $stmtDest = $db->prepare(
                "INSERT IGNORE INTO consultas_destinatarios (consulta_id, business_id) VALUES (?, ?)"
            );
            foreach ($recipients as $r) {
                $stmtDest->execute([$consultaId, (int)$r['id']]);
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            error_log('consultas.php send error: ' . $e->getMessage());
            cq_err('Error al guardar la consulta.');
        }

        auditLog('consulta_send', 'consulta_masiva', $consultaId, [
            'tipo'     => $tipo,
            'dest'     => count($recipients),
            'rubro'    => $rubro,
            'biz_type' => $bizType,
        ]);

        cq_ok([
            'consulta_id'  => $consultaId,
            'destinatarios' => $recipients,
        ], 'Consulta enviada a ' . count($recipients) . ' negocios.');
    }

    // ── reply: el propietario de un negocio responde ──────────────────────────
    if ($action === 'reply') {
        checkRateLimit('consulta_reply', 20, 3600);
        $uid        = cq_require_login();
        $consultaId = (int)($input['consulta_id'] ?? 0);
        $businessId = (int)($input['business_id'] ?? 0);
        $texto      = mb_substr(trim($input['texto'] ?? ''), 0, 500);

        if ($consultaId <= 0) cq_err('consulta_id requerido.');
        if ($businessId <= 0) cq_err('business_id requerido.');
        if ($texto === '')    cq_err('El texto de la respuesta es obligatorio.');

        // Verificar que el usuario es propietario del negocio
        if (!cq_own_business($db, $uid, $businessId)) {
            cq_err('Solo el propietario del negocio puede responder.', 403);
        }

        // Verificar que el negocio es destinatario de la consulta
        $stmtChk = $db->prepare(
            "SELECT 1 FROM consultas_destinatarios
              WHERE consulta_id = ? AND business_id = ? LIMIT 1"
        );
        $stmtChk->execute([$consultaId, $businessId]);
        if (!$stmtChk->fetchColumn()) {
            cq_err('Este negocio no es destinatario de la consulta.', 403);
        }

        $stmtR = $db->prepare(
            "INSERT INTO consultas_respuestas (consulta_id, business_id, user_id, texto)
              VALUES (?, ?, ?, ?)"
        );
        $stmtR->execute([$consultaId, $businessId, $uid, $texto]);
        $replyId = (int)$db->lastInsertId();

        // Marcar como leído al mismo tiempo
        $db->prepare(
            "UPDATE consultas_destinatarios SET leido_en = NOW()
              WHERE consulta_id = ? AND business_id = ? AND leido_en IS NULL"
        )->execute([$consultaId, $businessId]);

        cq_ok(['reply_id' => $replyId], 'Respuesta enviada.');
    }

    // ── mark_read: marca la consulta como leída por el negocio ────────────────
    if ($action === 'mark_read') {
        $uid        = cq_require_login();
        $consultaId = (int)($input['consulta_id'] ?? 0);
        $businessId = (int)($input['business_id'] ?? 0);
        if ($consultaId <= 0 || $businessId <= 0) cq_err('Parámetros inválidos.');
        if (!cq_own_business($db, $uid, $businessId)) cq_err('Sin permiso.', 403);
        $db->prepare(
            "UPDATE consultas_destinatarios SET leido_en = NOW()
              WHERE consulta_id = ? AND business_id = ? AND leido_en IS NULL"
        )->execute([$consultaId, $businessId]);
        cq_ok([], 'Marcado como leído.');
    }

    // ── toggle_proveedor: dueño activa/desactiva flag P ───────────────────────
    if ($action === 'toggle_proveedor') {
        $uid        = cq_require_login();
        $businessId = (int)($input['business_id'] ?? 0);
        if ($businessId <= 0) cq_err('business_id requerido.');
        if (!cq_own_business($db, $uid, $businessId)) cq_err('Sin permiso.', 403);

        // Verificar que no sea un tipo de servicio puro
        $stmtBt = $db->prepare("SELECT business_type, es_proveedor FROM businesses WHERE id = ? LIMIT 1");
        $stmtBt->execute([$businessId]);
        $biz = $stmtBt->fetch(\PDO::FETCH_ASSOC);
        if (!$biz) cq_err('Negocio no encontrado.', 404);

        if (in_array($biz['business_type'], cq_non_supplier_types(), true)) {
            cq_err('Los negocios de tipo servicio no pueden activar la designación P.');
        }

        $newVal = $biz['es_proveedor'] ? 0 : 1;
        $db->prepare("UPDATE businesses SET es_proveedor = ? WHERE id = ?")->execute([$newVal, $businessId]);
        $msg = $newVal ? 'Designación P activada.' : 'Designación P desactivada.';
        auditLog('toggle_proveedor', 'business', $businessId, ['es_proveedor' => $newVal]);
        cq_ok(['es_proveedor' => $newVal], $msg);
    }

    // ── toggle_consulta_habilitada: admin activa consulta general ─────────────
    if ($action === 'toggle_consulta_habilitada') {
        if (!isAdmin()) cq_err('Solo administradores pueden cambiar este campo.', 403);
        $input      = cq_input();
        $businessId = (int)($input['business_id'] ?? ($_GET['business_id'] ?? 0));
        if ($businessId <= 0) cq_err('business_id requerido.');

        $stmtCur = $db->prepare("SELECT consulta_habilitada FROM businesses WHERE id = ? LIMIT 1");
        $stmtCur->execute([$businessId]);
        $cur = $stmtCur->fetch(\PDO::FETCH_ASSOC);
        if (!$cur) cq_err('Negocio no encontrado.', 404);

        $newVal = $cur['consulta_habilitada'] ? 0 : 1;
        $db->prepare("UPDATE businesses SET consulta_habilitada = ? WHERE id = ?")->execute([$newVal, $businessId]);
        $msg = $newVal ? 'Consulta General habilitada.' : 'Consulta General deshabilitada.';
        auditLog('toggle_consulta_habilitada', 'business', $businessId, ['consulta_habilitada' => $newVal]);
        cq_ok(['consulta_habilitada' => $newVal], $msg);
    }
}

cq_err('Acción no reconocida.', 400);
