<?php
/**
 * API de CONSULTAS MASIVAS
 *
 * Acciones GET:
 *   preview               — cuántos negocios recibirían la consulta (sin guardar)
 *   thread                — hilo completo de una consulta (texto + respuestas)
 *   my_consultas          — consultas enviadas por el usuario actual (filtra status=active por defecto)
 *   pending               — consultas recibidas sin respuesta (para propietarios; excluye cerradas)
 *   reply_count           — número de respuestas nuevas desde un id dado (polling)
 *   rubros_proveedor      — lista de business_type que tienen negocios "P"
 *   influence_query       — busca inmobiliarias por zona de influencia
 *
 * Acciones POST:
 *   send                    — crea y envía una consulta masiva
 *   reply                   — el propietario de un negocio responde
 *   mark_read               — marca una consulta como leída por el negocio
 *   close_consulta          — cierra/archiva una consulta (remitente o admin)
 *   toggle_proveedor        — activa/desactiva flag es_proveedor (dueño)
 *   toggle_consulta_habilitada — activa/desactiva flag consulta_habilitada (admin)
 *   toggle_consulta_siempre    — admin marca negocio para siempre incluido en masivas
 *   toggle_proveedor_siempre   — admin marca negocio P para siempre incluido en proveedores
 */

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');
session_start();

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../includes/db_helper.php';
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

/** Máximo de destinatarios por consulta general (combinado forzados + radio). */
define('CQ_MAX_GENERAL',    20);

/** Máximo de destinatarios aleatorios por consulta masiva y proveedores. */
define('CQ_MAX_MASIVA',     20);

/** Radio por defecto en km para Consulta General (el cliente puede ajustarlo). */
define('CQ_GENERAL_DEFAULT_RADIUS_KM', 25);

/**
 * Tipos de negocio RESTRINGIDOS para consultas generales:
 * solo participan si el admin activó consulta_habilitada = 1 en ese negocio.
 * (Estudio Jurídico, Inmobiliaria, Productor de Seguros, Agente INPI)
 */
function cq_restricted_types(): array {
    return ['abogado', 'inmobiliaria', 'seguros', 'agente_inpi'];
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
        // Regla:
        //   1. Negocios FORZADOS dentro del área: consulta_siempre=1 ó creados por admin
        //   2. Del resto (excluidos los forzados), hasta CQ_MAX_MASIVA aleatorios
        //   3. Merge: forzados + aleatorios
        case 'masiva': {
            if (!$bounds || !isset($bounds['north'], $bounds['south'], $bounds['east'], $bounds['west'])) {
                cq_err('geo_bounds requerido para consulta masiva.');
            }

            // Parámetros base compartidos
            $baseWhere = "b.visible = 1
                           AND b.lat BETWEEN ? AND ?
                           AND b.lng BETWEEN ? AND ?
                           AND (wup.wt_mode IS NULL OR wup.wt_mode != 'closed')
                           AND $excludeRestricted";
            $baseParams = [
                (float)$bounds['south'], (float)$bounds['north'],
                (float)$bounds['west'],  (float)$bounds['east'],
            ];
            foreach ($restrictedTypes as $rt) $baseParams[] = $rt;

            $typeFilter = '';
            $typeParams = [];
            if ($bizType !== '') {
                $typeFilter = ' AND b.business_type = ?';
                $typeParams[] = $bizType;
            }

            // ① Negocios FORZADOS (consulta_siempre=1 O propietario admin)
            $sqlForced = "SELECT b.id, b.name, b.business_type
                            FROM businesses b
                            LEFT JOIN wt_user_preferences wup ON wup.user_id = b.user_id
                            JOIN users u ON u.id = b.user_id
                           WHERE $baseWhere
                             AND (b.consulta_siempre = 1 OR u.is_admin = 1)
                             $typeFilter";
            $stmtForced = $db->prepare($sqlForced);
            $stmtForced->execute(array_merge($baseParams, $typeParams));
            $forced = $stmtForced->fetchAll(\PDO::FETCH_ASSOC);
            $forcedIds = array_column($forced, 'id');

            // ② Negocios ALEATORIOS: excluir los forzados, random, max CQ_MAX_MASIVA
            $excludeForced = '';
            $excludeParams = [];
            if (!empty($forcedIds)) {
                $ph = implode(',', array_fill(0, count($forcedIds), '?'));
                $excludeForced = " AND b.id NOT IN ($ph)";
                $excludeParams = $forcedIds;
            }

            $sqlRandom = "SELECT b.id, b.name, b.business_type
                            FROM businesses b
                            LEFT JOIN wt_user_preferences wup ON wup.user_id = b.user_id
                           WHERE $baseWhere
                             $typeFilter
                             $excludeForced
                           ORDER BY RAND()
                           LIMIT " . CQ_MAX_MASIVA;
            $stmtRandom = $db->prepare($sqlRandom);
            $stmtRandom->execute(array_merge($baseParams, $typeParams, $excludeParams));
            $random = $stmtRandom->fetchAll(\PDO::FETCH_ASSOC);

            return array_merge($forced, $random);
        }

        // ── CONSULTA GENERAL: servicios especiales habilitados por admin ──────
        // - Solo tipos restringidos con consulta_habilitada = 1
        // - Negocios FORZADOS: propietario admin (sin restricción de radio)
        // - Radio configurable desde el cliente (default CQ_GENERAL_DEFAULT_RADIUS_KM)
        // - Máximo CQ_MAX_GENERAL destinatarios, ordenados por proximidad
        case 'general': {
            $userLat = isset($input['user_lat']) ? (float)$input['user_lat'] : null;
            $userLng = isset($input['user_lng']) ? (float)$input['user_lng'] : null;

            if ($userLat === null || $userLng === null) {
                cq_err('Se requiere la posición del mapa (user_lat, user_lng) para Consulta General.');
            }

            // Radio: tomado del cliente, con límites de seguridad
            $radiusKm = isset($input['radius_km']) ? (float)$input['radius_km'] : (float)CQ_GENERAL_DEFAULT_RADIUS_KM;
            if ($radiusKm < 5)   $radiusKm = 5;
            if ($radiusKm > 500) $radiusKm = 500;

            $restrictedForGeneral = cq_restricted_types();
            $phGeneral = implode(',', array_fill(0, count($restrictedForGeneral), '?'));

            // Haversine inline en SQL (MySQL)
            $havSql = "(6371 * ACOS(
                         COS(RADIANS(?)) * COS(RADIANS(b.lat))
                         * COS(RADIANS(b.lng) - RADIANS(?))
                         + SIN(RADIANS(?)) * SIN(RADIANS(b.lat))
                       ))";

            $typeFilterSql    = '';
            $typeFilterParams = [];
            if ($bizType !== '' && in_array($bizType, $restrictedForGeneral, true)) {
                $typeFilterSql    = ' AND b.business_type = ?';
                $typeFilterParams = [$bizType];
            }

            // ① Negocios FORZADOS: propietario admin + habilitado (sin límite de radio)
            $sqlForced = "SELECT b.id, b.name, b.business_type,
                                 $havSql AS distancia_km
                            FROM businesses b
                            JOIN users u ON u.id = b.user_id
                           WHERE b.visible = 1
                             AND b.consulta_habilitada = 1
                             AND b.business_type IN ($phGeneral)
                             AND b.lat IS NOT NULL AND b.lng IS NOT NULL
                             AND u.is_admin = 1
                             $typeFilterSql";
            $paramsForced = array_merge(
                [$userLat, $userLng, $userLat],
                $restrictedForGeneral,
                $typeFilterParams
            );
            $stmtForced = $db->prepare($sqlForced);
            $stmtForced->execute($paramsForced);
            $forced    = $stmtForced->fetchAll(\PDO::FETCH_ASSOC);
            $forcedIds = array_column($forced, 'id');

            // ② Negocios dentro del radio, excluyendo los forzados
            $excludeForced = '';
            $excludeParams = [];
            if (!empty($forcedIds)) {
                $ph            = implode(',', array_fill(0, count($forcedIds), '?'));
                $excludeForced = " AND b.id NOT IN ($ph)";
                $excludeParams = $forcedIds;
            }

            $remaining = CQ_MAX_GENERAL - count($forced);
            $regular   = [];
            if ($remaining > 0) {
                $sqlRegular = "SELECT b.id, b.name, b.business_type,
                                      $havSql AS distancia_km
                                 FROM businesses b
                                WHERE b.visible = 1
                                  AND b.consulta_habilitada = 1
                                  AND b.business_type IN ($phGeneral)
                                  AND b.lat IS NOT NULL AND b.lng IS NOT NULL
                                  AND $havSql <= ?
                                  $typeFilterSql
                                  $excludeForced
                                ORDER BY distancia_km ASC
                                LIMIT $remaining";
                $paramsRegular = array_merge(
                    [$userLat, $userLng, $userLat],
                    $restrictedForGeneral,
                    [$userLat, $userLng, $userLat, $radiusKm],
                    $typeFilterParams,
                    $excludeParams
                );
                $stmtRegular = $db->prepare($sqlRegular);
                $stmtRegular->execute($paramsRegular);
                $regular = $stmtRegular->fetchAll(\PDO::FETCH_ASSOC);
            }

            return array_merge($forced, $regular);
        }

        // ── CONSULTA GLOBAL PROVEEDORES: negocios P del rubro solicitado ──────
        // Regla:
        //   1. Negocios P FORZADOS del rubro: proveedor_siempre = 1
        //   2. Del resto (P, rubro, sin proveedor_siempre), hasta CQ_MAX_MASIVA aleatorios
        //   3. Merge: forzados + aleatorios
        case 'global_proveedor': {
            if ($rubro === '') cq_err('rubro requerido para consulta global proveedor.');

            // ① FORZADOS
            $stmtF = $db->prepare(
                "SELECT id, name, business_type FROM businesses
                  WHERE visible = 1 AND es_proveedor = 1 AND proveedor_siempre = 1
                    AND business_type = ?"
            );
            $stmtF->execute([$rubro]);
            $forced = $stmtF->fetchAll(\PDO::FETCH_ASSOC);
            $forcedIds = array_column($forced, 'id');

            // ② ALEATORIOS
            $excludeForced = '';
            $excludeParams = [];
            if (!empty($forcedIds)) {
                $ph = implode(',', array_fill(0, count($forcedIds), '?'));
                $excludeForced = " AND id NOT IN ($ph)";
                $excludeParams = $forcedIds;
            }
            $stmtR = $db->prepare(
                "SELECT id, name, business_type FROM businesses
                  WHERE visible = 1 AND es_proveedor = 1 AND proveedor_siempre = 0
                    AND business_type = ?
                    $excludeForced
                  ORDER BY RAND()
                  LIMIT " . CQ_MAX_MASIVA
            );
            $stmtR->execute(array_merge([$rubro], $excludeParams));
            $random = $stmtR->fetchAll(\PDO::FETCH_ASSOC);

            return array_merge($forced, $random);
        }

        // ── CONSULTA ENVIO: transportistas dentro del área ────────────────────
        case 'envio': {
            if (!$bounds || !isset($bounds['north'], $bounds['south'], $bounds['east'], $bounds['west'])) {
                cq_err('geo_bounds requerido para consulta envío.');
            }
            // Incluye tipos de transporte clásicos y nuevo subtipo 'envios' (transport_subtype)
            $hasSubtype = mapitaColumnExists($db, 'businesses', 'transport_subtype');
            $subtypeFilter = $hasSubtype
                ? " AND (b.business_type IN ('transporte','transportista','logistica','flota') OR (b.business_type IN ('transporte','transportista') AND (b.transport_subtype IS NULL OR b.transport_subtype = 'envios')))"
                : " AND b.business_type IN ('transporte','transportista','logistica','flota')";
            $stmt = $db->prepare(
                "SELECT b.id, b.name, b.business_type FROM businesses b
                  WHERE b.visible = 1
                    $subtypeFilter
                    AND b.lat BETWEEN ? AND ?
                    AND b.lng BETWEEN ? AND ?
                  LIMIT " . CQ_MAX_MASIVA
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
        $userLat  = isset($_GET['user_lat'])   ? (float)$_GET['user_lat']  : null;
        $userLng  = isset($_GET['user_lng'])   ? (float)$_GET['user_lng']  : null;
        $radiusKm = isset($_GET['radius_km'])  ? (float)$_GET['radius_km'] : null;
        $input = [
            'tipo'       => $tipo,
            'rubro'      => $rubro,
            'geo_bounds' => $bounds,
            'biz_type'   => $bizType,
            'user_lat'   => $userLat,
            'user_lng'   => $userLng,
            'radius_km'  => $radiusKm,
        ];
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
    // Parámetro opcional ?filter=active (default) o ?filter=all o ?filter=closed
    if ($action === 'my_consultas') {
        $uid = cq_require_login();
        $filter = trim($_GET['filter'] ?? 'active');

        // Incluir status en el SELECT solo si la columna existe
        $hasStatus = mapitaColumnExists($db, 'consultas_masivas', 'status');
        $statusCol = $hasStatus ? ", cm.status, DATE_FORMAT(cm.closed_at,'%d/%m/%Y %H:%i') AS closed_at_fmt" : "";
        $statusWhere = '';
        if ($hasStatus) {
            if ($filter === 'active') {
                $statusWhere = " AND cm.status IN ('open','answered')";
            } elseif ($filter === 'closed') {
                $statusWhere = " AND cm.status IN ('closed','archived')";
            }
            // filter='all' → sin filtro adicional
        }

        $stmt = $db->prepare(
            "SELECT cm.id, cm.tipo, cm.rubro, cm.texto,
                    DATE_FORMAT(cm.created_at,'%d/%m/%Y %H:%i') AS created_at,
                    (SELECT COUNT(*) FROM consultas_destinatarios WHERE consulta_id = cm.id) AS total_dest,
                    (SELECT COUNT(*) FROM consultas_respuestas    WHERE consulta_id = cm.id) AS total_resp
                    $statusCol
               FROM consultas_masivas cm
              WHERE cm.user_id = ?
                $statusWhere
              ORDER BY cm.id DESC
              LIMIT 50"
        );
        $stmt->execute([$uid]);
        cq_ok($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    // ── pending: consultas recibidas sin respuesta (para propietarios) ─────────
    // Excluye consultas que el remitente ya cerró/archivó
    if ($action === 'pending') {
        $uid = cq_require_login();
        $hasStatus    = mapitaColumnExists($db, 'consultas_masivas', 'status');
        $statusFilter = $hasStatus ? " AND cm.status IN ('open','answered')" : '';
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
                $statusFilter
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
        // Contar respuestas nuevas a consultas enviadas por el usuario (excluye cerradas)
        $hasStatus    = mapitaColumnExists($db, 'consultas_masivas', 'status');
        $statusFilter = $hasStatus ? " AND cm.status IN ('open','answered')" : '';
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM consultas_respuestas cr
              JOIN consultas_masivas cm ON cm.id = cr.consulta_id
             WHERE cm.user_id = ? AND cr.id > ? $statusFilter"
        );
        $stmt->execute([$uid, $sinceId]);
        $count = (int)$stmt->fetchColumn();

        // También contar consultas recibidas no leídas (para propietarios; excluye cerradas)
        $pendingStatusFilter = $hasStatus ? " AND cm.status IN ('open','answered')" : '';
        $stmtPending = $db->prepare(
            "SELECT COUNT(*) FROM consultas_destinatarios cd
              JOIN businesses b ON b.id = cd.business_id
              JOIN consultas_masivas cm ON cm.id = cd.consulta_id
             WHERE b.user_id = ? AND cd.leido_en IS NULL $pendingStatusFilter"
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

    // ── biz_types: business_type disponibles para filtrar masiva ──────────────
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

    // ── general_service_types: tipos disponibles solo para Consulta General ───
    // Solo los 4 rubros restringidos que tienen al menos un negocio habilitado
    if ($action === 'general_service_types') {
        cq_require_login();
        $restricted   = cq_restricted_types();
        $placeholders = implode(',', array_fill(0, count($restricted), '?'));
        $stmt = $db->prepare(
            "SELECT DISTINCT b.business_type FROM businesses b
              WHERE b.visible = 1
                AND b.consulta_habilitada = 1
                AND b.business_type IN ($placeholders)
              ORDER BY b.business_type"
        );
        $stmt->execute($restricted);
        cq_ok($stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    // ── influence_query: busca inmobiliarias por zona de influencia ───────────
    // GET ?action=influence_query&zona=barrio_o_ciudad
    // Devuelve inmobiliarias que tienen la zona en su influence_zones
    if ($action === 'influence_query') {
        cq_require_login();
        $zona = trim($_GET['zona'] ?? '');
        if ($zona === '') cq_err('Se requiere el parámetro zona.');
        if (strlen($zona) > 120) $zona = substr($zona, 0, 120);

        $hasInfluence = mapitaColumnExists($db, 'businesses', 'influence_zones');
        if (!$hasInfluence) {
            cq_ok([], 'Módulo de zonas de influencia no disponible aún.');
        }

        $inmTypes = ['inmobiliaria', 'inmobiliaria_venta', 'inmobiliaria_alquiler'];
        $ph       = implode(',', array_fill(0, count($inmTypes), '?'));

        // Búsqueda flexible: FULLTEXT si está disponible, LIKE como fallback
        $stmt = $db->prepare(
            "SELECT b.id, b.name, b.address, b.lat, b.lng, b.phone, b.email,
                    b.og_image_url, b.business_type, b.influence_zones
               FROM businesses b
              WHERE b.visible = 1
                AND b.business_type IN ($ph)
                AND b.influence_zones IS NOT NULL
                AND b.influence_zones != ''
                AND MATCH(b.influence_zones) AGAINST(? IN NATURAL LANGUAGE MODE)
              ORDER BY b.name ASC
              LIMIT 30"
        );
        $params = array_merge($inmTypes, [$zona]);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        // Si FULLTEXT no devuelve resultados, intentar LIKE como fallback
        if (empty($rows)) {
            $stmt2 = $db->prepare(
                "SELECT b.id, b.name, b.address, b.lat, b.lng, b.phone, b.email,
                        b.og_image_url, b.business_type, b.influence_zones
                   FROM businesses b
                  WHERE b.visible = 1
                    AND b.business_type IN ($ph)
                    AND b.influence_zones IS NOT NULL
                    AND b.influence_zones != ''
                    AND b.influence_zones LIKE ?
                  ORDER BY b.name ASC
                  LIMIT 30"
            );
            $params2 = array_merge($inmTypes, ['%' . $zona . '%']);
            $stmt2->execute($params2);
            $rows = $stmt2->fetchAll(\PDO::FETCH_ASSOC);
        }
        cq_ok($rows, count($rows) . ' inmobiliarias encontradas para zona "' . htmlspecialchars($zona, ENT_QUOTES) . '".');
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

        $rubro    = mb_substr(trim($input['rubro']    ?? ''), 0, 100) ?: null;
        $bizType  = mb_substr(trim($input['biz_type'] ?? ''), 0, 60)  ?: null;
        $userLat  = isset($input['user_lat'])  ? (float)$input['user_lat']  : null;
        $userLng  = isset($input['user_lng'])  ? (float)$input['user_lng']  : null;
        $radiusKm = isset($input['radius_km']) ? (float)$input['radius_km'] : null;
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
                'user_lat'   => $userLat,
                'user_lng'   => $userLng,
                'radius_km'  => $radiusKm,
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

        // Actualizar status de la consulta a 'answered' si todavía está 'open'
        if (mapitaColumnExists($db, 'consultas_masivas', 'status')) {
            $db->prepare(
                "UPDATE consultas_masivas SET status = 'answered', answered_at = NOW()
                  WHERE id = ? AND status = 'open'"
            )->execute([$consultaId]);
        }

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

    // ── toggle_consulta_siempre: admin marca negocio como siempre incluido en masivas ──
    if ($action === 'toggle_consulta_siempre') {
        if (!isAdmin()) cq_err('Solo administradores pueden cambiar este campo.', 403);
        $input      = cq_input();
        $businessId = (int)($input['business_id'] ?? ($_GET['business_id'] ?? 0));
        if ($businessId <= 0) cq_err('business_id requerido.');

        $stmtCur = $db->prepare("SELECT consulta_siempre FROM businesses WHERE id = ? LIMIT 1");
        $stmtCur->execute([$businessId]);
        $cur = $stmtCur->fetch(\PDO::FETCH_ASSOC);
        if (!$cur) cq_err('Negocio no encontrado.', 404);

        $newVal = $cur['consulta_siempre'] ? 0 : 1;
        $db->prepare("UPDATE businesses SET consulta_siempre = ? WHERE id = ?")->execute([$newVal, $businessId]);
        $msg = $newVal ? 'Negocio marcado como SIEMPRE en masivas.' : 'Negocio removido de inclusión forzada.';
        auditLog('toggle_consulta_siempre', 'business', $businessId, ['consulta_siempre' => $newVal]);
        cq_ok(['consulta_siempre' => $newVal], $msg);
    }

    // ── toggle_proveedor_siempre: admin marca negocio P como siempre incluido ─
    if ($action === 'toggle_proveedor_siempre') {
        if (!isAdmin()) cq_err('Solo administradores pueden cambiar este campo.', 403);
        $input      = cq_input();
        $businessId = (int)($input['business_id'] ?? ($_GET['business_id'] ?? 0));
        if ($businessId <= 0) cq_err('business_id requerido.');

        $stmtCur = $db->prepare("SELECT es_proveedor, proveedor_siempre FROM businesses WHERE id = ? LIMIT 1");
        $stmtCur->execute([$businessId]);
        $cur = $stmtCur->fetch(\PDO::FETCH_ASSOC);
        if (!$cur) cq_err('Negocio no encontrado.', 404);
        if (!$cur['es_proveedor']) cq_err('El negocio no tiene designación Proveedor (P).', 400);

        $newVal = $cur['proveedor_siempre'] ? 0 : 1;
        $db->prepare("UPDATE businesses SET proveedor_siempre = ? WHERE id = ?")->execute([$newVal, $businessId]);
        $msg = $newVal ? 'Proveedor marcado como SIEMPRE incluido.' : 'Proveedor vuelve a modo aleatorio.';
        auditLog('toggle_proveedor_siempre', 'business', $businessId, ['proveedor_siempre' => $newVal]);
        cq_ok(['proveedor_siempre' => $newVal], $msg);
    }

    // ── close_consulta: cierra o archiva una consulta (remitente o admin) ──────
    // POST {action:'close_consulta', consulta_id:N, mode:'close'|'archive'}
    // Solo el remitente original o un admin puede cerrar/archivar la consulta.
    if ($action === 'close_consulta') {
        $uid        = cq_require_login();
        $consultaId = (int)($input['consulta_id'] ?? 0);
        $mode       = in_array($input['mode'] ?? 'close', ['close','archive'], true)
                      ? ($input['mode'] ?? 'close') : 'close';
        if ($consultaId <= 0) cq_err('consulta_id requerido.');

        if (!mapitaColumnExists($db, 'consultas_masivas', 'status')) {
            cq_err('El módulo de ciclo de vida aún no está disponible.', 503);
        }

        // Verificar que existe y que pertenece al usuario (o es admin)
        $stmtCm = $db->prepare("SELECT user_id, status FROM consultas_masivas WHERE id = ? LIMIT 1");
        $stmtCm->execute([$consultaId]);
        $cm = $stmtCm->fetch(\PDO::FETCH_ASSOC);
        if (!$cm) cq_err('Consulta no encontrada.', 404);
        if ((int)$cm['user_id'] !== $uid && !isAdmin()) {
            cq_err('Solo el remitente o un administrador puede cerrar esta consulta.', 403);
        }
        if (in_array($cm['status'], ['closed','archived'], true)) {
            cq_ok(['status' => $cm['status']], 'La consulta ya estaba cerrada/archivada.');
        }

        $newStatus = ($mode === 'archive') ? 'archived' : 'closed';
        $db->prepare(
            "UPDATE consultas_masivas SET status = ?, closed_at = NOW(), closed_by = ? WHERE id = ?"
        )->execute([$newStatus, $uid, $consultaId]);

        $msg = ($newStatus === 'archived') ? 'Consulta archivada.' : 'Consulta cerrada correctamente.';
        auditLog('close_consulta', 'consulta_masiva', $consultaId, ['status' => $newStatus]);
        cq_ok(['status' => $newStatus], $msg);
    }
}

cq_err('Acción no reconocida.', 400);
