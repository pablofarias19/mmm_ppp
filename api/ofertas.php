<?php
/**
 * API Ofertas — con soporte geo completo
 * Tabla: ofertas (id, nombre, descripcion, precio_normal, precio_oferta,
 *        fecha_inicio, fecha_expiracion, imagen_url, lat, lng, business_id, activo)
 */
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');
session_start();

function respond_success($data, $msg = 'OK') {
    echo json_encode(['success' => true, 'data' => $data, 'message' => $msg]); exit;
}
function respond_error($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]); exit;
}

function table_exists(PDO $db, string $table): bool {
    try {
        $s = $db->prepare("SHOW TABLES LIKE ?");
        $s->execute([$table]);
        return (bool)$s->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}
function column_exists(PDO $db, string $table, string $column): bool {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) return false;
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $column)) return false;
    try {
        $s = $db->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1");
        $s->execute([$table, $column]);
        return (bool)$s->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function ensure_single_destacada(PDO $db, int $businessId, int $currentId): void {
    if ($businessId <= 0) return;
    $db->prepare("UPDATE ofertas SET es_destacada = 0 WHERE business_id = ? AND id <> ?")->execute([$businessId, $currentId]);
}

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';

$fallback = [[
    'id' => 0, 'nombre' => '🏷️ Sistema de Ofertas Activado',
    'descripcion' => 'Accede a /admin/ para crear ofertas geolocalizadas.',
    'precio_normal' => null, 'precio_oferta' => null,
    'fecha_inicio' => date('Y-m-d'), 'fecha_expiracion' => null,
    'lat' => -34.6037, 'lng' => -58.3816, 'activo' => 1
]];

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);
$businessId = (int)($_GET['business_id'] ?? 0);

$db = null;
try { $db = \Core\Database::getInstance()->getConnection(); }
catch (Exception $e) { error_log('Ofertas BD: ' . $e->getMessage()); }

// ── GET ──────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    if (!$db) respond_success($fallback, 'Ofertas (fallback)');
    try {
        if ($id > 0) {
            $s = $db->prepare("SELECT * FROM ofertas WHERE id = ?");
            $s->execute([$id]);
            $o = $s->fetch(\PDO::FETCH_ASSOC);
            if ($o) respond_success($o, 'Oferta obtenida');
            respond_error('No encontrada', 404);
        }

        if ($businessId > 0 && $action === 'destacada') {
            $hasOfertaActiva = column_exists($db, 'businesses', 'oferta_activa_id');
            $hasDestacada = column_exists($db, 'ofertas', 'es_destacada');
            if (!$hasOfertaActiva) respond_success(null, 'Sin configuración de oferta destacada');

            if ($hasDestacada) {
                $s = $db->prepare("SELECT o.* FROM businesses b
                                   LEFT JOIN ofertas o ON o.id = b.oferta_activa_id
                                   WHERE b.id = ? AND o.activo = 1 AND o.es_destacada = 1
                                   LIMIT 1");
            } else {
                $s = $db->prepare("SELECT o.* FROM businesses b
                                   LEFT JOIN ofertas o ON o.id = b.oferta_activa_id
                                   WHERE b.id = ? AND o.activo = 1
                                   LIMIT 1");
            }
            $s->execute([$businessId]);
            respond_success($s->fetch(\PDO::FETCH_ASSOC) ?: null, 'Oferta destacada obtenida');
        }

        // Nearby (Haversine)
        if ($action === 'nearby') {
            $lat   = (float)($_GET['lat'] ?? 0);
            $lng   = (float)($_GET['lng'] ?? 0);
            $radio = (float)($_GET['radio'] ?? 10);
            if (!$lat || !$lng) respond_error('Coordenadas requeridas');
            $sql = "SELECT *, (6371 * ACOS(COS(RADIANS(?)) * COS(RADIANS(lat)) *
                    COS(RADIANS(lng)-RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(lat)))) AS dist_km
                    FROM ofertas
                    WHERE activo = 1 AND (fecha_expiracion IS NULL OR fecha_expiracion >= CURDATE())
                    HAVING dist_km <= ? ORDER BY dist_km ASC";
            $s = $db->prepare($sql);
            $s->execute([$lat, $lng, $lat, $radio]);
            respond_success($s->fetchAll(\PDO::FETCH_ASSOC), 'Ofertas cercanas');
        }

        // Activas vigentes (default)
        $sql = "SELECT * FROM ofertas
                WHERE activo = 1 AND (fecha_expiracion IS NULL OR fecha_expiracion >= CURDATE())";
        $params = [];
        if ($businessId > 0) {
            $sql .= " AND business_id = ?";
            $params[] = $businessId;
        }
        $sql .= " ORDER BY created_at DESC";
        $s = $db->prepare($sql);
        $s->execute($params);
        respond_success($s->fetchAll(\PDO::FETCH_ASSOC), 'Ofertas obtenidas');

    } catch (\PDOException $e) {
        error_log('Ofertas GET: ' . $e->getMessage());
        respond_success($fallback, 'Fallback');
    }
}

// ── POST ─────────────────────────────────────────────────────────────────────
if ($method === 'POST') {
    if (!isAdmin()) respond_error('Solo admin', 403);
    if (!$db) respond_error('BD no disponible', 500);

    $ct    = $_SERVER['CONTENT_TYPE'] ?? '';
    $input = strpos($ct, 'application/json') !== false
           ? json_decode(file_get_contents('php://input'), true)
           : $_POST;
    if (!$input) respond_error('Datos inválidos');

    try {
        $hasOfertaActiva = table_exists($db, 'businesses') && column_exists($db, 'businesses', 'oferta_activa_id');
        $hasDestacada = column_exists($db, 'ofertas', 'es_destacada');
        $requestDestacada = ($action === 'upsert_destacada') || ((int)($input['es_destacada'] ?? 0) === 1);

        if ($action === 'upsert_destacada') {
            $businessId = (int)($input['business_id'] ?? 0);
            if ($businessId <= 0) respond_error('business_id requerido');
            $st = $db->prepare("SELECT id FROM ofertas WHERE business_id = ? AND activo = 1 ORDER BY created_at DESC LIMIT 1");
            $st->execute([$businessId]);
            $existing = (int)($st->fetchColumn() ?: 0);
            if ($existing > 0) {
                $action = 'update';
                $input['id'] = $existing;
            } else {
                $action = 'create';
            }
            $input['es_destacada'] = 1;
            $input['activo'] = 1;
        }

        if ($action === 'create') {
            $nombre = trim($input['nombre'] ?? '');
            if (!$nombre) respond_error('Nombre requerido');
            $businessId = ($input['business_id'] ?? '') !== '' ? (int)$input['business_id'] : null;
            $activo = (int)(bool)($input['activo'] ?? true);
            $esDestacada = $requestDestacada ? 1 : 0;

            $cols = ['nombre','descripcion','precio_normal','precio_oferta','fecha_inicio','fecha_expiracion','imagen_url','lat','lng','business_id','activo','created_at'];
            $vals = ['?','?','?','?','?','?','?','?','?','?','?','NOW()'];
            $params = [
                $nombre,
                $input['descripcion'] ?? null,
                ($input['precio_normal'] ?? '') !== '' ? $input['precio_normal'] : null,
                ($input['precio_oferta'] ?? '') !== '' ? $input['precio_oferta'] : null,
                $input['fecha_inicio'] ?? date('Y-m-d'),
                ($input['fecha_expiracion'] ?? '') !== '' ? $input['fecha_expiracion'] : null,
                $input['imagen_url'] ?? null,
                ($input['lat'] ?? '') !== '' ? (float)$input['lat'] : 0,
                ($input['lng'] ?? '') !== '' ? (float)$input['lng'] : 0,
                $businessId,
                $activo
            ];
            if ($hasDestacada) {
                $cols[] = 'es_destacada';
                $vals[] = '?';
                $params[] = $esDestacada;
            }

            $db->beginTransaction();
            $sql = "INSERT INTO ofertas (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ")";
            $s = $db->prepare($sql);
            $s->execute($params);
            $newId = (int)$db->lastInsertId();

            if ($hasDestacada && $esDestacada === 1 && $businessId) {
                ensure_single_destacada($db, (int)$businessId, $newId);
            }
            if ($hasOfertaActiva && $businessId && $activo === 1 && ($requestDestacada || !$hasDestacada)) {
                $db->prepare("UPDATE businesses SET oferta_activa_id = ? WHERE id = ?")->execute([$newId, $businessId]);
            }
            $db->commit();
            respond_success(['id' => $newId], $requestDestacada ? 'Oferta destacada creada' : 'Oferta creada');
        }

        if ($action === 'update') {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) respond_error('ID inválido');

            $upd = [];
            $vals = [];
            foreach (['nombre','descripcion','precio_normal','precio_oferta','fecha_inicio','fecha_expiracion','imagen_url','lat','lng','business_id'] as $c) {
                if (array_key_exists($c, $input)) {
                    $upd[] = "$c = ?";
                    $vals[] = ($input[$c] === '') ? null : $input[$c];
                }
            }
            if (isset($input['activo'])) {
                $upd[] = 'activo = ?';
                $vals[] = (int)(bool)$input['activo'];
            }
            if ($hasDestacada && (array_key_exists('es_destacada', $input) || $requestDestacada)) {
                $upd[] = 'es_destacada = ?';
                $vals[] = 1;
            }
            if (empty($upd)) respond_error('Sin datos');

            $upd[] = 'updated_at = NOW()';
            $vals[] = $id;

            $db->beginTransaction();
            $s = $db->prepare("UPDATE ofertas SET " . implode(', ', $upd) . " WHERE id = ?");
            $s->execute($vals);

            $st = $db->prepare("SELECT id, business_id, activo" . ($hasDestacada ? ", es_destacada" : "") . " FROM ofertas WHERE id = ?");
            $st->execute([$id]);
            $current = $st->fetch(PDO::FETCH_ASSOC) ?: [];
            $currBusinessId = (int)($current['business_id'] ?? 0);
            $currDestacada = $hasDestacada ? (int)($current['es_destacada'] ?? 0) : ($requestDestacada ? 1 : 0);
            $currActivo = (int)($current['activo'] ?? 0);

            if ($hasDestacada && $currBusinessId > 0 && $currDestacada === 1) {
                ensure_single_destacada($db, $currBusinessId, $id);
            }
            if ($hasOfertaActiva && $currBusinessId > 0) {
                if ($currActivo === 1 && ($requestDestacada || $currDestacada === 1 || !$hasDestacada)) {
                    $db->prepare("UPDATE businesses SET oferta_activa_id = ? WHERE id = ?")->execute([$id, $currBusinessId]);
                } else {
                    $db->prepare("UPDATE businesses SET oferta_activa_id = NULL WHERE id = ? AND oferta_activa_id = ?")->execute([$currBusinessId, $id]);
                }
            }
            $db->commit();
            respond_success(['id' => $id], $requestDestacada ? 'Oferta destacada actualizada' : 'Oferta actualizada');
        }

        if ($action === 'delete') {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) respond_error('ID inválido');
            if ($hasOfertaActiva) {
                $db->prepare("UPDATE businesses SET oferta_activa_id = NULL WHERE oferta_activa_id = ?")->execute([$id]);
            }
            $s = $db->prepare("DELETE FROM ofertas WHERE id = ?");
            if ($s->execute([$id])) respond_success([], 'Oferta eliminada');
            respond_error('Error al eliminar', 500);
        }

    } catch (\PDOException $e) {
        if ($db && $db->inTransaction()) {
            $db->rollBack();
        }
        error_log('Ofertas POST: ' . $e->getMessage());
        respond_error('Error: ' . $e->getMessage(), 500);
    }
}
respond_error('Método no válido', 405);
