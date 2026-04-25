<?php
/**
 * API de Convocatorias (OBRA DE ARTE → servicios artísticos)
 *
 * GET  ?action=mis_obras           → mis negocios obra_de_arte
 * POST ?action=save_proyecto       → guarda campos oda_* en businesses
 * POST ?action=convocar            → lanza convocatoria y notifica
 * GET  ?action=mis_convocatorias   → mis convocatorias
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

require_once __DIR__ . '/../includes/db_helper.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../includes/mapita_notifications.php';

function conv_ok($data = [], $msg = 'OK') {
    echo json_encode(['success' => true, 'data' => $data, 'message' => $msg]);
    exit;
}
function conv_err($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'data' => null, 'message' => $msg]);
    exit;
}

$db = getDbConnection();
if (!$db) conv_err('Sin conexión a la base de datos', 500);

$userId = (int)($_SESSION['user_id'] ?? 0);
$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$method = $_SERVER['REQUEST_METHOD'];

// ── GET: mis obras de arte ────────────────────────────────────────────────────
if ($method === 'GET' && $action === 'mis_obras') {
    if ($userId <= 0) conv_err('Sesión requerida', 401);
    $stmt = $db->prepare("SELECT id, name, oda_descripcion_proyecto, oda_requisitos, oda_roles_buscados
                           FROM businesses WHERE user_id = ? AND business_type = 'obra_de_arte' AND visible = 1
                           ORDER BY name ASC");
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        $r['oda_roles_buscados'] = json_decode($r['oda_roles_buscados'] ?? '[]', true) ?: [];
    }
    conv_ok($rows);
}

// ── GET: mis convocatorias ────────────────────────────────────────────────────
if ($method === 'GET' && $action === 'mis_convocatorias') {
    if ($userId <= 0) conv_err('Sesión requerida', 401);
    $stmt = $db->prepare("SELECT c.*, b.name AS obra_nombre
                           FROM convocatorias c
                           JOIN businesses b ON b.id = c.business_id
                           WHERE c.user_id = ?
                           ORDER BY c.created_at DESC LIMIT 20");
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        $r['roles_requeridos'] = json_decode($r['roles_requeridos'] ?? '[]', true) ?: [];
    }
    conv_ok($rows);
}

// ── POST: guardar campos proyecto obra_de_arte ────────────────────────────────
if ($method === 'POST' && $action === 'save_proyecto') {
    if ($userId <= 0) conv_err('Sesión requerida', 401);
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $bizId = (int)($input['business_id'] ?? 0);
    if ($bizId <= 0) conv_err('business_id requerido');

    $stmt = $db->prepare("SELECT user_id, business_type FROM businesses WHERE id = ? LIMIT 1");
    $stmt->execute([$bizId]);
    $biz = $stmt->fetch(\PDO::FETCH_ASSOC);
    if (!$biz) conv_err('Negocio no encontrado', 404);
    if ((int)$biz['user_id'] !== $userId && !isAdmin()) conv_err('Sin permisos', 403);
    if ($biz['business_type'] !== 'obra_de_arte') conv_err('Solo negocios tipo OBRA DE ARTE', 403);

    $desc  = mb_substr(trim((string)($input['oda_descripcion_proyecto'] ?? '')), 0, 5000) ?: null;
    $req   = mb_substr(trim((string)($input['oda_requisitos'] ?? '')), 0, 3000) ?: null;
    $roles = is_array($input['oda_roles_buscados'] ?? null) ? $input['oda_roles_buscados'] : [];

    // Sanitize roles
    $allowedRoles = ['musico','cantante','bailarin','actor','actriz','director_artistico','guionista',
                     'escenografo','fotografo_artistico','productor_artistico','maquillador','pintor',
                     'poeta','musicalizador','editor_grafico','asistente_artistico'];
    $roles = array_values(array_intersect($roles, $allowedRoles));
    $rolesJson = json_encode($roles, JSON_UNESCAPED_UNICODE);

    // Check columns exist
    try {
        $upd = $db->prepare("UPDATE businesses SET oda_descripcion_proyecto=?, oda_requisitos=?, oda_roles_buscados=? WHERE id=?");
        $upd->execute([$desc, $req, $rolesJson, $bizId]);
        conv_ok([], 'Proyecto guardado');
    } catch (\PDOException $e) {
        if ($e->getCode() === '42S22' || (int)($e->errorInfo[1] ?? 0) === 1054) {
            conv_err('Columnas oda_* no existen. Ejecutar migración 022_cerca_convocar.sql', 503);
        }
        conv_err('Error al guardar', 500);
    }
}

// ── POST: lanzar convocatoria ────────────────────────────────────────────────
if ($method === 'POST' && $action === 'convocar') {
    if ($userId <= 0) conv_err('Sesión requerida', 401);
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $bizId       = (int)($input['business_id'] ?? 0);
    $fechaInicio = trim((string)($input['fecha_inicio'] ?? ''));
    $fechaFin    = trim((string)($input['fecha_fin'] ?? ''));

    if ($bizId <= 0)       conv_err('business_id requerido');
    if ($fechaInicio === '') conv_err('fecha_inicio requerida');
    if ($fechaFin === '')   conv_err('fecha_fin requerida');

    // Validate dates
    $ts_inicio = strtotime($fechaInicio);
    $ts_fin    = strtotime($fechaFin);
    if (!$ts_inicio || !$ts_fin) conv_err('Fechas inválidas');
    if ($ts_fin <= $ts_inicio)   conv_err('fecha_fin debe ser posterior a fecha_inicio');

    // Verify ownership
    $stmt = $db->prepare("SELECT id, name, user_id, business_type, oda_roles_buscados FROM businesses WHERE id = ? LIMIT 1");
    $stmt->execute([$bizId]);
    $biz = $stmt->fetch(\PDO::FETCH_ASSOC);
    if (!$biz) conv_err('Negocio no encontrado', 404);
    if ((int)$biz['user_id'] !== $userId && !isAdmin()) conv_err('Sin permisos', 403);
    if ($biz['business_type'] !== 'obra_de_arte') conv_err('Solo negocios OBRA DE ARTE pueden convocar', 403);

    $roles = json_decode($biz['oda_roles_buscados'] ?? '[]', true) ?: [];
    if (empty($roles)) conv_err('El proyecto no tiene roles definidos. Configurá los roles en Editar Negocio primero.', 422);

    // Find matching service businesses
    $placeholders = implode(',', array_fill(0, count($roles), '?'));
    $stmtServ = $db->prepare("SELECT b.id, b.name, b.business_type, b.user_id, u.email
                               FROM businesses b
                               LEFT JOIN users u ON u.id = b.user_id
                               WHERE b.business_type IN ($placeholders) AND b.visible = 1");
    $stmtServ->execute($roles);
    $servicios = $stmtServ->fetchAll(\PDO::FETCH_ASSOC);

    // Create convocatoria
    $insConv = $db->prepare("INSERT INTO convocatorias (business_id, user_id, fecha_inicio, fecha_fin, roles_requeridos, estado) VALUES (?,?,?,?,?,?)");
    $insConv->execute([
        $bizId,
        $userId,
        date('Y-m-d H:i:s', $ts_inicio),
        date('Y-m-d H:i:s', $ts_fin),
        json_encode($roles, JSON_UNESCAPED_UNICODE),
        'activa'
    ]);
    $convId = (int)$db->lastInsertId();

    // Get obra name
    $obraName = $biz['name'];

    // Notify each service
    $notificados = 0;
    foreach ($servicios as $srv) {
        $srvUserId  = (int)($srv['user_id'] ?? 0);
        $srvBizId   = (int)$srv['id'];
        $srvEmail   = trim((string)($srv['email'] ?? ''));

        // Insert into destinatarios
        try {
            $insDest = $db->prepare("INSERT IGNORE INTO convocatoria_destinatarios (convocatoria_id, business_id) VALUES (?,?)");
            $insDest->execute([$convId, $srvBizId]);
        } catch (\PDOException $e) { /* ignorar duplicados */ }

        // WT message
        if ($srvUserId > 0) {
            try {
                $wtMsg = "Convocatoria: \"{$obraName}\" te convoca para participar en su proyecto artístico. " .
                         "Plazo: " . date('d/m/Y', $ts_inicio) . " al " . date('d/m/Y', $ts_fin) . ". " .
                         "Contactá al titular para más información.";
                $wtMsg = mb_substr($wtMsg, 0, 500);
                $insWt = $db->prepare("INSERT INTO wt_messages (entity_type, entity_id, user_id, user_name, sender_key, message) VALUES (?,?,?,?,?,?)");
                $insWt->execute(['negocio', $srvBizId, $userId, $obraName, 'uid:' . $userId, $wtMsg]);
                $updDest = $db->prepare("UPDATE convocatoria_destinatarios SET notificado_wt=1 WHERE convocatoria_id=? AND business_id=?");
                $updDest->execute([$convId, $srvBizId]);
            } catch (\PDOException $e) { /* WT puede no estar disponible */ }
        }

        // Email notification
        if ($srvEmail !== '') {
            $subject = "Convocatoria: {$obraName} — MAPITA";
            mapitaSendUserNotificationEmail($srvEmail, $subject, 'CONVOCATORIA ARTÍSTICA', [
                'Proyecto'    => $obraName,
                'Tu servicio' => $srv['name'],
                'Plazo'       => date('d/m/Y', $ts_inicio) . ' al ' . date('d/m/Y', $ts_fin),
                'Plataforma'  => 'https://mapita.com.ar',
            ]);
            try {
                $updDestMail = $db->prepare("UPDATE convocatoria_destinatarios SET notificado_mail=1 WHERE convocatoria_id=? AND business_id=?");
                $updDestMail->execute([$convId, $srvBizId]);
            } catch (\PDOException $e) {}
            $notificados++;
        }
    }

    conv_ok(['convocatoria_id' => $convId, 'notificados' => count($servicios), 'emails_enviados' => $notificados],
            'Convocatoria lanzada. Se notificaron ' . count($servicios) . ' servicio(s).');
}

conv_err('Acción no reconocida', 404);
