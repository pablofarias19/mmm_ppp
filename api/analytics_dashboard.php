<?php
/**
 * API: Datos del tablero de analítica (Admin only)
 * GET /api/analytics_dashboard.php?section=<name>&...
 *
 * Sections:
 *   kpis           — KPI cards (totals, today counts)
 *   online         — presence table (online/idle)
 *   feed           — recent events feed (paginated)
 *   rankings       — top users by events / unique businesses / clicks
 *   top_businesses — top businesses by views and contacts
 */

ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../includes/db_helper.php';

setSecurityHeaders();

// ── Auth: admin only ─────────────────────────────────────────────────────────
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit;
}

// ── DB ───────────────────────────────────────────────────────────────────────
$db = getDbConnection();
if (!$db) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'db_unavailable']);
    exit;
}

// ── Guard: tables must exist ─────────────────────────────────────────────────
$hasEvents   = mapitaTableExists($db, 'analytics_events');
$hasPresence = mapitaTableExists($db, 'user_presence');

if (!$hasEvents || !$hasPresence) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'tables_missing', 'hint' => 'Run migration 027_analytics_presence.sql']);
    exit;
}

// ── Presence thresholds ───────────────────────────────────────────────────────
// online  < 2 min   → green
// idle    2–10 min  → yellow
// offline > 10 min  → not listed unless requested
define('ONLINE_SECONDS', 120);
define('IDLE_SECONDS',   600);

$section = trim($_GET['section'] ?? 'kpis');

// ── Helper: safe int from GET ─────────────────────────────────────────────────
function _int(string $key, int $default = 0): int {
    return isset($_GET[$key]) ? max(0, (int)$_GET[$key]) : $default;
}

// ─────────────────────────────────────────────────────────────────────────────
// Section: kpis
// ─────────────────────────────────────────────────────────────────────────────
if ($section === 'kpis') {
    try {
        $todayStart = date('Y-m-d') . ' 00:00:00';
        $ago7d      = date('Y-m-d H:i:s', strtotime('-7 days'));
        $ago30d     = date('Y-m-d H:i:s', strtotime('-30 days'));
        $agoOnline  = date('Y-m-d H:i:s', time() - ONLINE_SECONDS);
        $agoIdle    = date('Y-m-d H:i:s', time() - IDLE_SECONDS);

        // total registered users
        $totalUsers = 0;
        if (mapitaTableExists($db, 'users')) {
            $totalUsers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        }

        $evToday = (int)$db->prepare(
            "SELECT COUNT(*) FROM analytics_events WHERE created_at >= ?"
        )->execute([$todayStart]) ? $db->query("SELECT COUNT(*) FROM analytics_events WHERE created_at >= '$todayStart'")->fetchColumn() : 0;

        // Redo with prepared statements properly
        $stEvToday = $db->prepare("SELECT COUNT(*) FROM analytics_events WHERE created_at >= ?");
        $stEvToday->execute([$todayStart]);
        $evToday = (int)$stEvToday->fetchColumn();

        $stOnline = $db->prepare("SELECT COUNT(*) FROM user_presence WHERE last_seen_at >= ?");
        $stOnline->execute([$agoOnline]);
        $onlineNow = (int)$stOnline->fetchColumn();

        $stIdle = $db->prepare("SELECT COUNT(*) FROM user_presence WHERE last_seen_at >= ? AND last_seen_at < ?");
        $stIdle->execute([$agoIdle, $agoOnline]);
        $idleNow = (int)$stIdle->fetchColumn();

        // Unique active visitors today
        $stActiveToday = $db->prepare(
            "SELECT COUNT(DISTINCT COALESCE(user_id, visitor_id))
             FROM analytics_events WHERE created_at >= ?"
        );
        $stActiveToday->execute([$todayStart]);
        $activeToday = (int)$stActiveToday->fetchColumn();

        $stActive7d = $db->prepare(
            "SELECT COUNT(DISTINCT COALESCE(user_id, visitor_id))
             FROM analytics_events WHERE created_at >= ?"
        );
        $stActive7d->execute([$ago7d]);
        $active7d = (int)$stActive7d->fetchColumn();

        $stActive30d = $db->prepare(
            "SELECT COUNT(DISTINCT COALESCE(user_id, visitor_id))
             FROM analytics_events WHERE created_at >= ?"
        );
        $stActive30d->execute([$ago30d]);
        $active30d = (int)$stActive30d->fetchColumn();

        // total events all time
        $totalEvents = (int)$db->query("SELECT COUNT(*) FROM analytics_events")->fetchColumn();

        echo json_encode([
            'ok'   => true,
            'data' => [
                'total_users'   => $totalUsers,
                'total_events'  => $totalEvents,
                'events_today'  => $evToday,
                'online_now'    => $onlineNow,
                'idle_now'      => $idleNow,
                'active_today'  => $activeToday,
                'active_7d'     => $active7d,
                'active_30d'    => $active30d,
            ],
        ]);
    } catch (Throwable $e) {
        error_log('[analytics_dashboard] kpis: ' . $e->getMessage());
        echo json_encode(['ok' => false, 'error' => 'query_error']);
    }
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// Section: online
// ─────────────────────────────────────────────────────────────────────────────
if ($section === 'online') {
    try {
        $agoIdle   = date('Y-m-d H:i:s', time() - IDLE_SECONDS);
        $agoOnline = date('Y-m-d H:i:s', time() - ONLINE_SECONDS);
        $now       = time();

        $stmt = $db->prepare(
            "SELECT p.id, p.user_id, p.visitor_id, p.session_id,
                    p.current_path, p.last_seen_at, p.ip,
                    u.username
               FROM user_presence p
               LEFT JOIN users u ON u.id = p.user_id
              WHERE p.last_seen_at >= ?
              ORDER BY p.last_seen_at DESC
              LIMIT 200"
        );
        $stmt->execute([$agoIdle]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $result = array_map(function ($r) use ($now, $agoOnline) {
            $lastTs  = strtotime($r['last_seen_at']);
            $diffSec = $now - $lastTs;
            $status  = ($diffSec < ONLINE_SECONDS) ? 'online' : 'idle';
            return [
                'user_id'      => $r['user_id'] ? (int)$r['user_id'] : null,
                'username'     => $r['username'] ?: null,
                'visitor_id'   => $r['visitor_id'] ? substr($r['visitor_id'], 0, 12) . '…' : null,
                'current_path' => $r['current_path'],
                'last_seen_at' => $r['last_seen_at'],
                'seconds_ago'  => $diffSec,
                'status'       => $status,
            ];
        }, $rows);

        echo json_encode(['ok' => true, 'data' => $result, 'count' => count($result)]);
    } catch (Throwable $e) {
        error_log('[analytics_dashboard] online: ' . $e->getMessage());
        echo json_encode(['ok' => false, 'error' => 'query_error']);
    }
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// Section: feed
// ─────────────────────────────────────────────────────────────────────────────
if ($section === 'feed') {
    try {
        $limit     = min(100, max(10, _int('limit', 50)));
        $offset    = _int('offset', 0);
        $typeFilter = trim($_GET['event_type'] ?? '');
        $userFilter = _int('user_id', 0);

        $where  = ['1=1'];
        $params = [];

        if ($typeFilter) {
            $where[]  = 'e.event_type = ?';
            $params[] = $typeFilter;
        }
        if ($userFilter) {
            $where[]  = 'e.user_id = ?';
            $params[] = $userFilter;
        }

        $whereStr = implode(' AND ', $where);

        $stmt = $db->prepare(
            "SELECT e.id, e.created_at, e.event_type, e.user_id, e.visitor_id,
                    e.business_id, e.meta_json, e.ip,
                    u.username,
                    b.name AS business_name
               FROM analytics_events e
               LEFT JOIN users u ON u.id = e.user_id
               LEFT JOIN businesses b ON b.id = e.business_id
              WHERE $whereStr
              ORDER BY e.created_at DESC
              LIMIT $limit OFFSET $offset"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Count for pagination
        $cStmt = $db->prepare("SELECT COUNT(*) FROM analytics_events e WHERE $whereStr");
        $cStmt->execute($params);
        $total = (int)$cStmt->fetchColumn();

        $result = array_map(function ($r) {
            return [
                'id'            => (int)$r['id'],
                'created_at'    => $r['created_at'],
                'event_type'    => $r['event_type'],
                'user_id'       => $r['user_id'] ? (int)$r['user_id'] : null,
                'username'      => $r['username'] ?: null,
                'visitor_id'    => $r['visitor_id'] ? substr($r['visitor_id'], 0, 12) . '…' : null,
                'business_id'   => $r['business_id'] ? (int)$r['business_id'] : null,
                'business_name' => $r['business_name'] ?: null,
                'meta_json'     => $r['meta_json'],
                'ip'            => $r['ip'],
            ];
        }, $rows);

        echo json_encode([
            'ok'     => true,
            'data'   => $result,
            'total'  => $total,
            'limit'  => $limit,
            'offset' => $offset,
        ]);
    } catch (Throwable $e) {
        error_log('[analytics_dashboard] feed: ' . $e->getMessage());
        echo json_encode(['ok' => false, 'error' => 'query_error']);
    }
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// Section: rankings
// ─────────────────────────────────────────────────────────────────────────────
if ($section === 'rankings') {
    try {
        $period = trim($_GET['period'] ?? 'today');
        switch ($period) {
            case '7d':   $since = date('Y-m-d H:i:s', strtotime('-7 days')); break;
            case '30d':  $since = date('Y-m-d H:i:s', strtotime('-30 days')); break;
            default:     $since = date('Y-m-d') . ' 00:00:00'; // today
        }

        // Top users by total events
        $stmt = $db->prepare(
            "SELECT e.user_id, u.username, COUNT(*) AS total_events
               FROM analytics_events e
               LEFT JOIN users u ON u.id = e.user_id
              WHERE e.user_id IS NOT NULL AND e.created_at >= ?
              GROUP BY e.user_id, u.username
              ORDER BY total_events DESC
              LIMIT 10"
        );
        $stmt->execute([$since]);
        $topByEvents = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Top users by unique businesses viewed
        $stmt2 = $db->prepare(
            "SELECT e.user_id, u.username, COUNT(DISTINCT e.business_id) AS unique_businesses
               FROM analytics_events e
               LEFT JOIN users u ON u.id = e.user_id
              WHERE e.user_id IS NOT NULL AND e.business_id IS NOT NULL AND e.created_at >= ?
              GROUP BY e.user_id, u.username
              ORDER BY unique_businesses DESC
              LIMIT 10"
        );
        $stmt2->execute([$since]);
        $topByBusinesses = $stmt2->fetchAll(\PDO::FETCH_ASSOC);

        // Top users by valuable clicks (whatsapp/phone/website/directions)
        $clickEvents = ['whatsapp_click', 'phone_click', 'website_click', 'directions_click'];
        $placeholders = implode(',', array_fill(0, count($clickEvents), '?'));
        $stmt3 = $db->prepare(
            "SELECT e.user_id, u.username, COUNT(*) AS valuable_clicks
               FROM analytics_events e
               LEFT JOIN users u ON u.id = e.user_id
              WHERE e.user_id IS NOT NULL
                AND e.event_type IN ($placeholders)
                AND e.created_at >= ?
              GROUP BY e.user_id, u.username
              ORDER BY valuable_clicks DESC
              LIMIT 10"
        );
        $stmt3->execute(array_merge($clickEvents, [$since]));
        $topByClicks = $stmt3->fetchAll(\PDO::FETCH_ASSOC);

        echo json_encode([
            'ok'     => true,
            'period' => $period,
            'data'   => [
                'by_events'     => $topByEvents,
                'by_businesses' => $topByBusinesses,
                'by_clicks'     => $topByClicks,
            ],
        ]);
    } catch (Throwable $e) {
        error_log('[analytics_dashboard] rankings: ' . $e->getMessage());
        echo json_encode(['ok' => false, 'error' => 'query_error']);
    }
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// Section: top_businesses
// ─────────────────────────────────────────────────────────────────────────────
if ($section === 'top_businesses') {
    try {
        $period = trim($_GET['period'] ?? 'today');
        switch ($period) {
            case '7d':  $since = date('Y-m-d H:i:s', strtotime('-7 days')); break;
            case '30d': $since = date('Y-m-d H:i:s', strtotime('-30 days')); break;
            default:    $since = date('Y-m-d') . ' 00:00:00';
        }

        // Top businesses by views (business_open event)
        $stmtViews = $db->prepare(
            "SELECT e.business_id, b.name AS business_name,
                    COUNT(*) AS views,
                    COUNT(DISTINCT COALESCE(e.user_id, e.visitor_id)) AS unique_visitors
               FROM analytics_events e
               LEFT JOIN businesses b ON b.id = e.business_id
              WHERE e.business_id IS NOT NULL
                AND e.event_type = 'business_open'
                AND e.created_at >= ?
              GROUP BY e.business_id, b.name
              ORDER BY views DESC
              LIMIT 15"
        );
        $stmtViews->execute([$since]);
        $topViews = $stmtViews->fetchAll(\PDO::FETCH_ASSOC);

        // Top businesses by contacts (whatsapp + phone clicks)
        $stmtContacts = $db->prepare(
            "SELECT e.business_id, b.name AS business_name,
                    SUM(e.event_type = 'whatsapp_click') AS whatsapp_clicks,
                    SUM(e.event_type = 'phone_click') AS phone_clicks,
                    COUNT(*) AS total_contacts
               FROM analytics_events e
               LEFT JOIN businesses b ON b.id = e.business_id
              WHERE e.business_id IS NOT NULL
                AND e.event_type IN ('whatsapp_click','phone_click')
                AND e.created_at >= ?
              GROUP BY e.business_id, b.name
              ORDER BY total_contacts DESC
              LIMIT 15"
        );
        $stmtContacts->execute([$since]);
        $topContacts = $stmtContacts->fetchAll(\PDO::FETCH_ASSOC);

        // Merge: compute contact/view ratio
        $viewsMap = [];
        foreach ($topViews as $r) {
            $viewsMap[(int)$r['business_id']] = (int)$r['views'];
        }
        foreach ($topContacts as &$r) {
            $v = $viewsMap[(int)$r['business_id']] ?? 0;
            $r['views'] = $v;
            $r['contact_rate'] = $v > 0 ? round($r['total_contacts'] / $v * 100, 1) : null;
        }
        unset($r);

        echo json_encode([
            'ok'     => true,
            'period' => $period,
            'data'   => [
                'top_views'    => $topViews,
                'top_contacts' => $topContacts,
            ],
        ]);
    } catch (Throwable $e) {
        error_log('[analytics_dashboard] top_businesses: ' . $e->getMessage());
        echo json_encode(['ok' => false, 'error' => 'query_error']);
    }
    exit;
}

// ── Unknown section ───────────────────────────────────────────────────────────
http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'unknown_section']);
