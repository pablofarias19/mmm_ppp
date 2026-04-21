<?php
/**
 * API de reportes de contenido
 *
 * POST /api/reports.php
 *   Crea un reporte (usuarios autenticados o anónimos con rate limit).
 *   Body JSON: { content_type, content_id, reason, description? }
 *
 * GET  /api/reports.php?status=pending&limit=50&offset=0
 *   Lista reportes (solo admins).
 *
 * PUT  /api/reports.php?id=N
 *   Actualiza estado de un reporte (solo admins).
 *   Body JSON: { status, resolution_note? }
 */

session_start();

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../includes/db_helper.php';
require_once __DIR__ . '/../includes/rate_limiter.php';
require_once __DIR__ . '/../includes/audit_logger.php';
require_once __DIR__ . '/../models/Report.php';

use App\Models\Report;

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// ─── POST: crear reporte ────────────────────────────────────────────────────
if ($method === 'POST') {
    // Rate limit: máx 5 reportes por IP cada 10 minutos
    checkRateLimit('report_create', 5, 600);

    $input       = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $contentType = trim($input['content_type'] ?? '');
    $contentId   = (int)($input['content_id']  ?? 0);
    $reason      = trim($input['reason']        ?? '');
    $description = trim($input['description']   ?? '');

    if (!$contentType || !$contentId || !$reason) {
        respond_error('Se requiere content_type, content_id y reason.');
    }

    $ip             = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $reporterUserId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    $result = Report::create($reporterUserId, $ip, $contentType, $contentId, $reason, $description);

    if ($result['success']) {
        auditLog('report_create', $contentType, $contentId, ['reason' => $reason]);
        respond_success(null, $result['message']);
    } else {
        respond_error($result['message']);
    }
}

// ─── GET: listar reportes (admin) ───────────────────────────────────────────
if ($method === 'GET') {
    if (!isAdmin()) {
        respond_error('Acceso denegado.', 403);
    }

    $status = in_array($_GET['status'] ?? 'pending', ['pending','reviewing','resolved','dismissed','all'], true)
        ? ($_GET['status'] ?? 'pending')
        : 'pending';
    $limit  = min((int)($_GET['limit']  ?? 50), 100);
    $offset = max((int)($_GET['offset'] ?? 0), 0);

    $reports  = Report::list($status, $limit, $offset);
    $pending  = Report::countPending();
    respond_success(['reports' => $reports, 'pending_count' => $pending], 'Reportes obtenidos.');
}

// ─── PUT: resolver/cambiar estado (admin) ───────────────────────────────────
if ($method === 'PUT') {
    if (!isAdmin()) {
        respond_error('Acceso denegado.', 403);
    }

    $reportId = (int)($_GET['id'] ?? 0);
    if (!$reportId) {
        respond_error('Se requiere el parámetro id.');
    }

    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $status = trim($input['status']          ?? '');
    $note   = trim($input['resolution_note'] ?? '');

    if (!$status) {
        respond_error('Se requiere el campo status.');
    }

    $result = Report::updateStatus($reportId, $status, (int)$_SESSION['user_id'], $note);

    if ($result['success']) {
        auditLog('resolve_report', 'content_report', $reportId, ['status' => $status, 'note' => $note]);
        respond_success(null, $result['message']);
    } else {
        respond_error($result['message']);
    }
}

respond_error('Método HTTP no soportado.', 405);
