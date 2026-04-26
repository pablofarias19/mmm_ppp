<?php
/**
 * API Radar Legal — lectura de catálogos + configuración por sector
 *
 * GET  /api/radar_legal.php?resource=transport_modes
 * GET  /api/radar_legal.php?resource=ports[&transport_mode_id=N]
 * GET  /api/radar_legal.php?resource=destinations[&direction=importacion|exportacion]
 * GET  /api/radar_legal.php?resource=restrictions[&type=...]
 * GET  /api/radar_legal.php?resource=disputes[&type=...]
 * GET  /api/radar_legal.php?resource=contract_types[&category=...]
 * GET  /api/radar_legal.php?resource=settings&sector_type=X&sector_id=N
 * POST /api/radar_legal.php?action=set_enabled (admin)
 */
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');
session_start();

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../models/RadarLegal.php';

use App\Models\RadarLegal;

function rl_json(array $data, int $code = 200): void {
    http_response_code($code); echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit;
}
function rl_err(string $msg, int $code = 400): void { rl_json(['error' => $msg], $code); }
function rl_isAdmin(): bool { return !empty($_SESSION['is_admin']); }

try { \Core\Database::getInstance()->getConnection(); }
catch (Throwable $e) { rl_err('Base de datos no disponible', 503); }

$method   = $_SERVER['REQUEST_METHOD'];
$resource = $_GET['resource'] ?? '';
$action   = $_GET['action']   ?? '';

if ($method === 'GET') {
    switch ($resource) {
        case 'transport_modes':
            rl_json(['data' => RadarLegal::getTransportModes()]);
        case 'ports':
            $tmId = !empty($_GET['transport_mode_id']) ? (int)$_GET['transport_mode_id'] : null;
            rl_json(['data' => RadarLegal::getPorts($tmId)]);
        case 'destinations':
            $dir = $_GET['direction'] ?? null;
            rl_json(['data' => RadarLegal::getDestinations($dir)]);
        case 'restrictions':
            $type = $_GET['type'] ?? null;
            rl_json(['data' => RadarLegal::getRestrictions($type)]);
        case 'disputes':
            $type = $_GET['type'] ?? null;
            rl_json(['data' => RadarLegal::getDisputes($type)]);
        case 'contract_types':
            $cat = $_GET['category'] ?? null;
            rl_json(['data' => RadarLegal::getContractTypes($cat)]);
        case 'settings':
            $st = $_GET['sector_type'] ?? '';
            $si = (int)($_GET['sector_id'] ?? 0);
            if (!$st || !$si) rl_err('sector_type y sector_id requeridos');
            $settings = RadarLegal::getSettings($st, $si);
            $enabled  = RadarLegal::isEnabled($st, $si);
            rl_json(['settings' => $settings, 'enabled' => $enabled]);
        default:
            rl_err('resource no reconocido. Opciones: transport_modes, ports, destinations, restrictions, disputes, contract_types, settings');
    }
}

if ($method === 'POST') {
    if (!rl_isAdmin()) rl_err('Acceso denegado', 403);
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true) ?? $_POST;

    if ($action === 'set_enabled') {
        $st      = $data['sector_type'] ?? '';
        $si      = (int)($data['sector_id'] ?? 0);
        $enabled = isset($data['enabled']) ? (bool)$data['enabled'] : true;
        $notes   = $data['notes'] ?? null;
        if (!$st || !$si) rl_err('sector_type y sector_id requeridos');
        $ok = RadarLegal::setEnabled($st, $si, $enabled, $notes);
        rl_json(['ok' => $ok]);
    }
    rl_err('Accion no reconocida');
}
rl_err('Metodo no permitido', 405);
