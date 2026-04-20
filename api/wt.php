<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');
session_start();

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';

function wt_success($data = [], $message = 'OK') {
    echo json_encode(['success' => true, 'data' => $data, 'message' => $message]);
    exit;
}
function wt_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}
function wt_get_input() {
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($ct, 'application/json') !== false) {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }
    return $_POST;
}
function wt_is_valid_entity($entityType, $entityId) {
    $allowed = ['negocio', 'marca', 'evento', 'encuesta'];
    return in_array($entityType, $allowed, true) && $entityId > 0;
}
function wt_get_identity() {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $userName = trim((string)($_SESSION['user_name'] ?? 'Invitado'));
    $sessionId = session_id();
    if (!$sessionId) {
        try {
            $sessionId = bin2hex(random_bytes(24));
        } catch (\Throwable $e) {
            $sessionId = uniqid('wt_fallback_', true);
        }
    }
    $senderKey = $userId > 0
        ? ('uid:' . $userId)
        : ('sid:' . substr(hash('sha256', $sessionId), 0, 40));

    return [$userId, $userName, $senderKey];
}
function wt_get_business_owner(\PDO $db, $entityType, $entityId) {
    if ($entityType !== 'negocio' || $entityId <= 0) {
        return null;
    }
    $stmt = $db->prepare("SELECT b.id AS business_id, b.user_id, COALESCE(u.username, 'Propietario') AS owner_name
                          FROM businesses b
                          LEFT JOIN users u ON u.id = b.user_id
                          WHERE b.id = ?
                          LIMIT 1");
    $stmt->execute([$entityId]);
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    if (!$row) return null;
    return [
        'business_id' => (int)$row['business_id'],
        'user_id' => (int)($row['user_id'] ?? 0),
        'owner_name' => (string)($row['owner_name'] ?? 'Propietario')
    ];
}
function wt_is_preset($message, array $presets) {
    return in_array((string)$message, $presets, true);
}

$db = null;
try {
    $db = \Core\Database::getInstance()->getConnection();
} catch (\Throwable $e) {
    $db = null;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');

$presets = ['Hola 👋', 'Estoy cerca 📍', '¿Hay novedades?', 'Gracias 🙌'];
const WT_MAX_MESSAGE_LEN = 140;
const WT_RATE_LIMIT_PER_MINUTE = 5;
const WT_PRESENCE_TIMEOUT_SECONDS = 25;
const WT_MAX_USERNAME_LEN = 80;
const WT_MAX_ACTIVE_MESSAGES_PER_ENTITY = 3;

if (!$db) {
    wt_success([
        'messages' => [],
        'presence_count' => 0,
        'presets' => $presets
    ], 'WT fallback: base de datos no disponible');
}

try {
    $db->query("SELECT 1 FROM wt_messages LIMIT 1");
    $db->query("SELECT 1 FROM wt_presence LIMIT 1");
} catch (\PDOException $e) {
    wt_success([
        'messages' => [],
        'presence_count' => 0,
        'presets' => $presets
    ], 'WT fallback: ejecutar migrations/002_wt_tables.sql');
}

if ($method === 'GET') {
    if ($action === 'presets') {
        wt_success(['presets' => $presets], 'Presets WT');
    }

    if ($action === 'list') {
        $entityType = trim((string)($_GET['entity_type'] ?? ''));
        $entityId = (int)($_GET['entity_id'] ?? 0);
        $sinceId = (int)($_GET['since_id'] ?? 0);
        if (!wt_is_valid_entity($entityType, $entityId)) wt_error('Entidad inválida', 400);
        [$userId, , $senderKey] = wt_get_identity();
        $isAdminViewer = isAdmin();
        $owner = wt_get_business_owner($db, $entityType, $entityId);
        $isOwnerViewer = $owner && $owner['user_id'] > 0 && $userId === $owner['user_id'];
        $canViewAllMessages = $isAdminViewer || ($entityType === 'negocio' && $isOwnerViewer);

        $params = [$entityType, $entityId];
        $extraSql = '';
        $privacySql = '';
        if (!$canViewAllMessages) {
            $privacySql = ' AND sender_key = ? ';
            $params[] = $senderKey;
        }
        if ($sinceId > 0) {
            $extraSql = ' AND id > ? ';
            $params[] = $sinceId;
        }
        $sql = "SELECT id, entity_type, entity_id, user_id, user_name, sender_key, message,
                       DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') AS created_at
                 FROM wt_messages
                WHERE entity_type = ? AND entity_id = ? $privacySql $extraSql
                ORDER BY id ASC LIMIT 50";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $messages = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($messages as &$msg) {
            $msg['is_preset'] = wt_is_preset($msg['message'] ?? '', $presets);
            if (!$msg['is_preset']) {
                $msg['recipient_name'] = ($owner && $owner['owner_name']) ? $owner['owner_name'] : 'Propietario';
            }
            $isOwnMessage = ($msg['sender_key'] ?? '') === $senderKey;
            $canDismissAsOwner = (bool)$isOwnerViewer && ($msg['entity_type'] ?? '') === 'negocio' && !$isOwnMessage;
            $msg['can_dismiss'] = $isAdminViewer || $isOwnMessage || $canDismissAsOwner;
            unset($msg['sender_key']);
        }
        unset($msg);

        $presenceStmt = $db->prepare("SELECT COUNT(*) FROM wt_presence WHERE entity_type = ? AND entity_id = ? AND last_seen >= DATE_SUB(NOW(), INTERVAL " . WT_PRESENCE_TIMEOUT_SECONDS . " SECOND)");
        $presenceStmt->execute([$entityType, $entityId]);
        $presenceCount = (int)$presenceStmt->fetchColumn();

        wt_success([
            'messages' => $messages,
            'presence_count' => $presenceCount,
            'presets' => $presets
        ], 'Mensajes WT');
    }

    wt_error('Acción GET no válida', 405);
}

if ($method === 'POST') {
    $input = wt_get_input();
    $entityType = trim((string)($input['entity_type'] ?? $_GET['entity_type'] ?? ''));
    $entityId = (int)($input['entity_id'] ?? $_GET['entity_id'] ?? 0);
    $action = $input['action'] ?? $action;

    if ($action === 'moderate') {
        if (!isAdmin()) wt_error('Solo admin', 403);
        wt_success([], 'Moderación WT pendiente');
    }

    if (!wt_is_valid_entity($entityType, $entityId)) wt_error('Entidad inválida', 400);

    [$userId, $userName, $senderKey] = wt_get_identity();

    if ($action === 'heartbeat') {
        $stmt = $db->prepare("INSERT INTO wt_presence (entity_type, entity_id, user_id, user_name, sender_key, last_seen, updated_at)
                              VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                              ON DUPLICATE KEY UPDATE user_name = VALUES(user_name), last_seen = NOW(), updated_at = NOW()");
        $stmt->execute([$entityType, $entityId, $userId ?: null, mb_substr($userName, 0, WT_MAX_USERNAME_LEN), $senderKey]);
        wt_success([], 'Heartbeat registrado');
    }

    if ($action === 'send') {
        $message = trim((string)($input['message'] ?? ''));
        if ($message === '') wt_error('Mensaje vacío', 400);
        if (mb_strlen($message) > WT_MAX_MESSAGE_LEN) wt_error('Máximo ' . WT_MAX_MESSAGE_LEN . ' caracteres', 400);

        $rateStmt = $db->prepare("SELECT COUNT(*) FROM wt_messages WHERE sender_key = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
        $rateStmt->execute([$senderKey]);
        $sentLastMinute = (int)$rateStmt->fetchColumn();
        if ($sentLastMinute >= WT_RATE_LIMIT_PER_MINUTE) wt_error('Límite WT: ' . WT_RATE_LIMIT_PER_MINUTE . ' mensajes por minuto', 429);

        $activeLimitStmt = $db->prepare("SELECT COUNT(*) FROM wt_messages WHERE entity_type = ? AND entity_id = ? AND sender_key = ?");
        $activeLimitStmt->execute([$entityType, $entityId, $senderKey]);
        $activeMessages = (int)$activeLimitStmt->fetchColumn();
        if ($activeMessages >= WT_MAX_ACTIVE_MESSAGES_PER_ENTITY) {
            wt_error('Límite WT: máximo ' . WT_MAX_ACTIVE_MESSAGES_PER_ENTITY . ' mensajes por conversación. Eliminá uno para poder enviar otro.', 429);
        }

        $stmt = $db->prepare("INSERT INTO wt_messages (entity_type, entity_id, user_id, user_name, sender_key, message, created_at, updated_at)
                              VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([
            $entityType,
            $entityId,
            $userId ?: null,
            mb_substr($userName ?: 'Invitado', 0, WT_MAX_USERNAME_LEN),
            $senderKey,
            mb_substr($message, 0, WT_MAX_MESSAGE_LEN)
        ]);

        wt_success(['id' => (int)$db->lastInsertId()], 'Mensaje WT enviado');
    }

    if ($action === 'dismiss') {
        $messageId = (int)($input['message_id'] ?? 0);
        if ($messageId <= 0) wt_error('Mensaje inválido', 400);
        $isAdminUser = isAdmin();

        $stmt = $db->prepare("SELECT wm.id, wm.entity_type, wm.entity_id, wm.sender_key, b.user_id AS owner_user_id
                              FROM wt_messages wm
                              LEFT JOIN businesses b ON wm.entity_type = 'negocio' AND b.id = wm.entity_id
                              WHERE wm.id = ?
                              LIMIT 1");
        $stmt->execute([$messageId]);
        $msg = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$msg) wt_error('Mensaje no encontrado', 404);

        $canDismissAsRecipient = ($msg['entity_type'] ?? '') === 'negocio'
            && (int)($msg['owner_user_id'] ?? 0) > 0
            && $userId === (int)$msg['owner_user_id']
            && ($msg['sender_key'] ?? '') !== $senderKey;
        $canDismissAsSender = ($msg['sender_key'] ?? '') === $senderKey;
        if (!$isAdminUser && !$canDismissAsRecipient && !$canDismissAsSender) wt_error('No autorizado para cerrar este mensaje', 403);

        $deleteStmt = $db->prepare("DELETE FROM wt_messages WHERE id = ?");
        $deleteStmt->execute([$messageId]);
        wt_success([], 'Mensaje eliminado');
    }

    wt_error('Acción POST no válida', 405);
}

wt_error('Método no válido', 405);
