<?php
/**
 * API de preferencias WT (Walkie Talkie) por usuario
 *
 * GET  /api/wt_preferences.php               → preferencias del usuario logueado
 * GET  /api/wt_preferences.php?action=blocks  → listar bloqueos activos
 * POST /api/wt_preferences.php action=save   → guardar modo + áreas
 * POST /api/wt_preferences.php action=block  → bloquear un usuario (user_id)
 * POST /api/wt_preferences.php action=unblock → desbloquear un usuario (user_id)
 *
 * Requiere sesión de usuario.
 */
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');
session_start();

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';

// ─── Áreas predefinidas del sistema ──────────────────────────────────────────
const WT_AREAS = [
    'salud'          => '🏥 Salud',
    'educacion'      => '📚 Educación',
    'tecnologia'     => '💻 Tecnología',
    'gastronomia'    => '🍽️ Gastronomía',
    'entretenimiento'=> '🎭 Entretenimiento',
    'deporte'        => '⚽ Deporte',
    'arte_cultura'   => '🎨 Arte y Cultura',
    'servicios'      => '🔧 Servicios',
    'comercio'       => '🛒 Comercio',
    'inmobiliaria'   => '🏠 Inmobiliaria',
    'manga_anime'    => '🎌 Manga / Anime',
    'turismo'        => '✈️ Turismo',
    'automotor'      => '🚗 Automotor',
    'moda'           => '👗 Moda',
    'musica'         => '🎵 Música',
    'juegos'         => '🎮 Juegos',
];

function wtpref_success($data = [], $msg = 'OK') {
    echo json_encode(['success' => true, 'data' => $data, 'message' => $msg]);
    exit;
}
function wtpref_error($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}
function wtpref_get_input() {
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($ct, 'application/json') !== false) {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }
    return $_POST;
}

// Requiere sesión
$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    wtpref_error('Sesión requerida', 401);
}

$db = null;
try {
    $db = \Core\Database::getInstance()->getConnection();
} catch (\Throwable $e) {
    wtpref_error('Base de datos no disponible', 503);
}

// Verificar que las tablas existen
try {
    $db->query("SELECT 1 FROM wt_user_preferences LIMIT 1");
    $db->query("SELECT 1 FROM wt_user_blocks LIMIT 1");
} catch (\PDOException $e) {
    wtpref_error('Tablas WT no inicializadas — ejecutar migrations/011_wt_preferences.sql', 503);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? ($_POST['action'] ?? 'get');

// ─── Función auxiliar: obtener preferencias de un usuario ────────────────────
function wt_load_prefs(\PDO $db, int $uid): array {
    $stmt = $db->prepare("SELECT wt_mode, areas FROM wt_user_preferences WHERE user_id = ?");
    $stmt->execute([$uid]);
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    if (!$row) {
        return ['wt_mode' => 'open', 'areas' => []];
    }
    $areas = [];
    if (!empty($row['areas'])) {
        $decoded = json_decode($row['areas'], true);
        $areas = is_array($decoded) ? $decoded : [];
    }
    return ['wt_mode' => $row['wt_mode'], 'areas' => $areas];
}

// ─── GET: preferencias del usuario actual ────────────────────────────────────
if ($method === 'GET') {
    if ($action === 'blocks') {
        $stmt = $db->prepare(
            "SELECT b.blocked_user_id, u.username
             FROM wt_user_blocks b
             JOIN users u ON u.id = b.blocked_user_id
             WHERE b.blocker_user_id = ?
             ORDER BY b.created_at DESC"
        );
        $stmt->execute([$userId]);
        $blocks = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        wtpref_success(['blocks' => $blocks], 'Lista de bloqueos');
    }

    $prefs = wt_load_prefs($db, $userId);
    wtpref_success([
        'prefs'  => $prefs,
        'areas'  => WT_AREAS,
    ], 'Preferencias WT');
}

// ─── POST ────────────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $input = wtpref_get_input();
    $action = $input['action'] ?? $action;

    // Guardar preferencias
    if ($action === 'save') {
        $mode = trim((string)($input['wt_mode'] ?? 'open'));
        if (!in_array($mode, ['open', 'selective', 'closed'], true)) {
            wtpref_error('Modo WT inválido. Valores: open, selective, closed');
        }

        $rawAreas = $input['areas'] ?? [];
        $validAreas = [];
        if (is_array($rawAreas)) {
            foreach ($rawAreas as $a) {
                $a = trim((string)$a);
                if (isset(WT_AREAS[$a])) {
                    $validAreas[] = $a;
                }
            }
        }
        // En modo 'selective' sin áreas → guardar igual (usuario puede refinar después)
        $areasJson = json_encode(array_values(array_unique($validAreas)));

        $stmt = $db->prepare(
            "INSERT INTO wt_user_preferences (user_id, wt_mode, areas, updated_at)
             VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE wt_mode = VALUES(wt_mode), areas = VALUES(areas), updated_at = NOW()"
        );
        $stmt->execute([$userId, $mode, $areasJson]);
        wtpref_success(['wt_mode' => $mode, 'areas' => $validAreas], 'Preferencias guardadas');
    }

    // Bloquear usuario
    if ($action === 'block') {
        $targetId = (int)($input['user_id'] ?? 0);
        if ($targetId <= 0) wtpref_error('user_id inválido');
        if ($targetId === $userId) wtpref_error('No puedes bloquearte a ti mismo');

        // Verificar que el usuario existe
        $chk = $db->prepare("SELECT id FROM users WHERE id = ?");
        $chk->execute([$targetId]);
        if (!$chk->fetch()) wtpref_error('Usuario no encontrado', 404);

        $stmt = $db->prepare(
            "INSERT IGNORE INTO wt_user_blocks (blocker_user_id, blocked_user_id, created_at)
             VALUES (?, ?, NOW())"
        );
        $stmt->execute([$userId, $targetId]);
        wtpref_success([], 'Usuario bloqueado');
    }

    // Desbloquear usuario
    if ($action === 'unblock') {
        $targetId = (int)($input['user_id'] ?? 0);
        if ($targetId <= 0) wtpref_error('user_id inválido');

        $stmt = $db->prepare(
            "DELETE FROM wt_user_blocks WHERE blocker_user_id = ? AND blocked_user_id = ?"
        );
        $stmt->execute([$userId, $targetId]);
        wtpref_success([], 'Usuario desbloqueado');
    }

    wtpref_error('Acción POST no válida', 405);
}

wtpref_error('Método no válido', 405);
