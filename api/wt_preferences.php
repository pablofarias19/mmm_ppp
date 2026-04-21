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
    $db->query("SELECT 1 FROM wt_user_areas LIMIT 1");
} catch (\PDOException $e) {
    wtpref_error('Tablas WT no inicializadas — ejecutar migrations/011_wt_preferences.sql y 012_wt_user_areas.sql', 503);
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

    // Leer áreas desde la tabla normalizada
    $aStmt = $db->prepare("SELECT area_slug FROM wt_user_areas WHERE user_id = ? ORDER BY area_slug");
    $aStmt->execute([$uid]);
    $areas = $aStmt->fetchAll(\PDO::FETCH_COLUMN);

    // Migración transparente: si la tabla normalizada está vacía pero el JSON antiguo
    // tiene datos, migrar en caliente para que el usuario no pierda su configuración.
    if (empty($areas) && !empty($row['areas'])) {
        $decoded = json_decode($row['areas'], true);
        if (is_array($decoded) && !empty($decoded)) {
            $toMigrate = array_values(array_unique(array_filter($decoded, fn($s) => isset(WT_AREAS[trim((string)$s)]))));
            foreach ($toMigrate as $slug) {
                try {
                    $ins = $db->prepare("INSERT IGNORE INTO wt_user_areas (user_id, area_slug) VALUES (?, ?)");
                    $ins->execute([$uid, $slug]);
                } catch (\PDOException $e) { /* ignorar */ }
            }
            $areas = $toMigrate;
        }
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
        $validAreas = array_values(array_unique($validAreas));

        $db->beginTransaction();
        try {
            // Guardar modo en wt_user_preferences (sin tocar la columna areas legacy)
            $stmt = $db->prepare(
                "INSERT INTO wt_user_preferences (user_id, wt_mode, updated_at)
                 VALUES (?, ?, NOW())
                 ON DUPLICATE KEY UPDATE wt_mode = VALUES(wt_mode), updated_at = NOW()"
            );
            $stmt->execute([$userId, $mode]);

            // Reemplazar áreas en la tabla normalizada
            $del = $db->prepare("DELETE FROM wt_user_areas WHERE user_id = ?");
            $del->execute([$userId]);

            if (!empty($validAreas)) {
                $placeholders = implode(',', array_fill(0, count($validAreas), '(?,?)'));
                $params = [];
                foreach ($validAreas as $slug) {
                    $params[] = $userId;
                    $params[] = $slug;
                }
                $ins = $db->prepare("INSERT INTO wt_user_areas (user_id, area_slug) VALUES $placeholders");
                $ins->execute($params);
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            wtpref_error('Error al guardar preferencias', 500);
        }

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
