<?php
/**
 * API: Telemetría de eventos y heartbeat de presencia
 * POST /api/analytics_track.php
 *
 * Acciones:
 *   action=event      — registrar evento de analítica
 *   action=heartbeat  — actualizar presencia del usuario
 *
 * No requiere autenticación (permite visitantes anónimos vía visitor_id).
 */

ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../includes/db_helper.php';

// ── Allowlist de event_type ──────────────────────────────────────────────────
const ALLOWED_EVENTS = [
    'map_open', 'business_open', 'brand_open', 'search',
    'filter_change', 'whatsapp_click', 'phone_click',
    'website_click', 'directions_click', 'email_click',
    'detail_click', 'page_view',
];

// ── Helpers ──────────────────────────────────────────────────────────────────

function _track_get_ip(): string {
    foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '';
}

function _track_visitor_id(): string {
    $name = 'mapita_vid';
    if (!empty($_COOKIE[$name])) {
        $v = preg_replace('/[^a-z0-9]/i', '', $_COOKIE[$name]);
        if (strlen($v) >= 16) return substr($v, 0, 64);
    }
    $v = bin2hex(random_bytes(16));
    setcookie($name, $v, [
        'expires'  => time() + 60 * 60 * 24 * 365 * 2,
        'path'     => '/',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    return $v;
}

// ── Input ────────────────────────────────────────────────────────────────────

$action     = trim($_POST['action'] ?? '');
$visitorId  = _track_visitor_id();
$userId     = (int)($_SESSION['user_id'] ?? 0) ?: null;
$ip         = _track_get_ip();
$ua         = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 512);
$sessionId  = session_id() ?: null;

// ── Rate-limiting per session (in-session counter) ───────────────────────────
// heartbeat: min 20s between updates per session
if ($action === 'heartbeat') {
    $lastHb = $_SESSION['_track_last_hb'] ?? 0;
    if ((time() - $lastHb) < 20) {
        echo json_encode(['ok' => true, 'skipped' => true]);
        exit;
    }
    $_SESSION['_track_last_hb'] = time();
}

// event: max 60 events per session per minute (simple counter)
if ($action === 'event') {
    $evMinute   = (int)($_SESSION['_track_ev_min'] ?? 0);
    $evCount    = (int)($_SESSION['_track_ev_cnt'] ?? 0);
    $currentMin = (int)(time() / 60);
    if ($evMinute !== $currentMin) {
        $_SESSION['_track_ev_min'] = $currentMin;
        $_SESSION['_track_ev_cnt'] = 0;
        $evCount = 0;
    }
    if ($evCount >= 60) {
        echo json_encode(['ok' => false, 'error' => 'rate_limited']);
        exit;
    }
    $_SESSION['_track_ev_cnt'] = $evCount + 1;
}

// ── DB connection ────────────────────────────────────────────────────────────
$db = getDbConnection();
if (!$db) {
    echo json_encode(['ok' => false, 'error' => 'db_unavailable']);
    exit;
}

// ── Guard: tables must exist (silent fail if migration not run) ──────────────
if (!mapitaTableExists($db, 'analytics_events') || !mapitaTableExists($db, 'user_presence')) {
    echo json_encode(['ok' => false, 'error' => 'tables_missing']);
    exit;
}

// ── Action handlers ───────────────────────────────────────────────────────────

if ($action === 'event') {

    $rawType    = trim($_POST['event_type'] ?? '');
    $eventType  = in_array($rawType, ALLOWED_EVENTS, true) ? $rawType : null;
    if (!$eventType) {
        echo json_encode(['ok' => false, 'error' => 'invalid_event_type']);
        exit;
    }

    $businessId = isset($_POST['business_id']) ? ((int)$_POST['business_id'] ?: null) : null;
    $rawMeta    = $_POST['meta'] ?? null;
    $metaJson   = null;
    if ($rawMeta !== null) {
        // accept either a JSON string or plain string; store as-is (trim to 4096)
        $decoded = json_decode($rawMeta, true);
        $metaJson = ($decoded !== null)
            ? substr(json_encode($decoded), 0, 4096)
            : substr(json_encode(['v' => substr($rawMeta, 0, 512)]), 0, 4096);
    }

    try {
        $stmt = $db->prepare(
            "INSERT INTO analytics_events
                 (event_type, user_id, visitor_id, business_id, meta_json, ip, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$eventType, $userId, $visitorId, $businessId, $metaJson, $ip, $ua]);
        echo json_encode(['ok' => true, 'id' => (int)$db->lastInsertId()]);
    } catch (Throwable $e) {
        error_log('[analytics_track] event insert: ' . $e->getMessage());
        echo json_encode(['ok' => false, 'error' => 'db_error']);
    }

} elseif ($action === 'heartbeat') {

    $currentPath = substr(trim($_POST['path'] ?? '/'), 0, 255) ?: '/';
    $now         = date('Y-m-d H:i:s');

    try {
        // Upsert by session_id (update if exists, otherwise insert)
        if ($sessionId) {
            $check = $db->prepare(
                "SELECT id FROM user_presence WHERE session_id = ? LIMIT 1"
            );
            $check->execute([$sessionId]);
            $existingId = $check->fetchColumn();

            if ($existingId) {
                $upd = $db->prepare(
                    "UPDATE user_presence
                        SET last_seen_at = ?, current_path = ?, user_id = ?,
                            visitor_id = ?, ip = ?, user_agent = ?
                      WHERE id = ?"
                );
                $upd->execute([$now, $currentPath, $userId, $visitorId, $ip, $ua, $existingId]);
            } else {
                $ins = $db->prepare(
                    "INSERT INTO user_presence
                         (user_id, visitor_id, session_id, current_path, last_seen_at, ip, user_agent)
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                $ins->execute([$userId, $visitorId, $sessionId, $currentPath, $now, $ip, $ua]);
            }
        } else {
            // No session ID: upsert by visitor_id
            $check = $db->prepare(
                "SELECT id FROM user_presence WHERE visitor_id = ? AND session_id IS NULL LIMIT 1"
            );
            $check->execute([$visitorId]);
            $existingId = $check->fetchColumn();

            if ($existingId) {
                $upd = $db->prepare(
                    "UPDATE user_presence
                        SET last_seen_at = ?, current_path = ?, user_id = ?, ip = ?, user_agent = ?
                      WHERE id = ?"
                );
                $upd->execute([$now, $currentPath, $userId, $ip, $ua, $existingId]);
            } else {
                $ins = $db->prepare(
                    "INSERT INTO user_presence
                         (user_id, visitor_id, session_id, current_path, last_seen_at, ip, user_agent)
                     VALUES (?, ?, NULL, ?, ?, ?, ?)"
                );
                $ins->execute([$userId, $visitorId, $currentPath, $now, $ip, $ua]);
            }
        }
        echo json_encode(['ok' => true]);
    } catch (Throwable $e) {
        error_log('[analytics_track] heartbeat: ' . $e->getMessage());
        echo json_encode(['ok' => false, 'error' => 'db_error']);
    }

} else {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'unknown_action']);
}
