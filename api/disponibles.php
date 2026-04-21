<?php
/**
 * API Módulo Disponibles — ítems del panel de disponibilidad de negocios
 *
 * GET  /api/disponibles.php?business_id=N          → lista ítems activos (público)
 * POST /api/disponibles.php?action=save_items       → guarda/reemplaza ítems (titular)
 * POST /api/disponibles.php?action=toggle_modulo    → activa/desactiva módulo (titular)
 */

ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');
session_start();

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../includes/db_helper.php';

function disp_ok($data, $msg = 'OK') {
    echo json_encode(['success' => true, 'data' => $data, 'message' => $msg]);
    exit;
}
function disp_err($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}
function disp_get_input() {
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($ct, 'application/json') !== false) {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }
    return $_POST;
}

$db = null;
try { $db = \Core\Database::getInstance()->getConnection(); }
catch (Throwable $e) { disp_err('Base de datos no disponible', 503); }

$method     = $_SERVER['REQUEST_METHOD'];
$action     = $_GET['action'] ?? 'list';
$businessId = (int)($_GET['business_id'] ?? 0);

// ── Verificar tablas disponibles ──────────────────────────────────────────────
if (!mapitaTableExists($db, 'disponibles_items')) {
    // Tablas aún no migradas — devolver estado vacío sin error fatal
    if ($method === 'GET') {
        disp_ok(['business_name' => '', 'modulo_activo' => false, 'items' => [], 'ordenes_pendientes' => null],
                'Módulo no inicializado (ejecutar migrations/009_disponibles.sql)');
    }
    disp_err('Módulo no inicializado. Ejecutá migrations/009_disponibles.sql', 503);
}

// ── GET: lista pública de ítems ───────────────────────────────────────────────
if ($method === 'GET') {
    if ($businessId <= 0) disp_err('business_id requerido');

    // Verificar que el módulo esté activo para lecturas públicas
    // La columna disponibles_activo puede no existir en instancias antiguas
    $hasCol = mapitaColumnExists($db, 'businesses', 'disponibles_activo');
    $colSel = $hasCol ? 'disponibles_activo, name' : 'name';
    $stB = $db->prepare("SELECT {$colSel} FROM businesses WHERE id = ? LIMIT 1");
    $stB->execute([$businessId]);
    $biz = $stB->fetch(\PDO::FETCH_ASSOC);
    if (!$biz) disp_err('Negocio no encontrado', 404);
    if (!$hasCol) $biz['disponibles_activo'] = 0;

    $userId = (int)($_SESSION['user_id'] ?? 0);
    $isOwner = canManageBusiness($userId, $businessId);

    if (!$biz['disponibles_activo'] && !$isOwner) {
        disp_err('El módulo de disponibles no está activo', 403);
    }

    $st = $db->prepare(
        "SELECT * FROM disponibles_items
         WHERE business_id = ? AND activo = 1
         ORDER BY orden ASC, id ASC"
    );
    $st->execute([$businessId]);
    $items = $st->fetchAll(\PDO::FETCH_ASSOC);

    // Contador de solicitudes (solo para el titular)
    $ordenesCount = 0;
    if ($isOwner) {
        $stO = $db->prepare(
            "SELECT COUNT(*) FROM disponibles_solicitudes
             WHERE business_id = ? AND estado = 'pendiente'"
        );
        $stO->execute([$businessId]);
        $ordenesCount = (int)$stO->fetchColumn();
    }

    disp_ok([
        'business_name'    => $biz['name'],
        'modulo_activo'    => (bool)$biz['disponibles_activo'],
        'items'            => $items,
        'ordenes_pendientes' => $isOwner ? $ordenesCount : null,
    ]);
}

// ── POST: acciones del titular ────────────────────────────────────────────────
if ($method === 'POST') {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) disp_err('Se requiere autenticación', 401);

    $input      = disp_get_input();
    $businessId = (int)($input['business_id'] ?? $businessId);
    if ($businessId <= 0) disp_err('business_id requerido');

    if (!canManageBusiness($userId, $businessId)) {
        disp_err('Sin permiso para gestionar este negocio', 403);
    }

    // ── Activar/desactivar módulo ─────────────────────────────────────────────
    if ($action === 'toggle_modulo') {
        $activo = isset($input['activo']) ? ((int)(bool)$input['activo']) : null;
        if ($activo === null) disp_err('Parámetro activo requerido');
        $db->prepare("UPDATE businesses SET disponibles_activo = ? WHERE id = ?")
           ->execute([$activo, $businessId]);
        disp_ok(['activo' => (bool)$activo], 'Módulo ' . ($activo ? 'activado' : 'desactivado'));
    }

    // ── Guardar ítems (reemplaza todos los ítems del negocio) ─────────────────
    if ($action === 'save_items') {
        $items = $input['items'] ?? [];
        if (!is_array($items)) disp_err('items debe ser un arreglo');

        // Validar cada ítem
        $clean = [];
        foreach ($items as $idx => $item) {
            $row = [];

            // precio
            $precioADefinir = !empty($item['precio_a_definir']);
            $precio = null;
            if (!$precioADefinir) {
                $precioRaw = trim((string)($item['precio'] ?? ''));
                if ($precioRaw !== '') {
                    $precioF = filter_var($precioRaw, FILTER_VALIDATE_FLOAT);
                    if ($precioF === false || $precioF < 0 || $precioF > 99999999.99) {
                        disp_err("Ítem #" . ($idx + 1) . ": precio inválido (máx $99,999,999.99)");
                    }
                    $precio = round($precioF, 2);
                }
            }
            $row['precio']           = $precio;
            $row['precio_a_definir'] = $precioADefinir ? 1 : 0;

            // cantidad
            $cantRaw = trim((string)($item['cantidad'] ?? ''));
            $row['cantidad'] = ($cantRaw !== '') ? max(0, (int)$cantRaw) : null;

            // tipo_bien (máx 30 chars)
            $tipoBien = mb_substr(trim((string)($item['tipo_bien'] ?? '')), 0, 30);
            $row['tipo_bien'] = $tipoBien !== '' ? $tipoBien : null;

            // disponibilidad
            $row['disponible_desde'] = disp_parse_date($item['disponible_desde'] ?? '');
            $row['disponible_hasta'] = disp_parse_date($item['disponible_hasta'] ?? '');
            $row['horario_inicio']   = disp_parse_time($item['horario_inicio'] ?? '');
            $row['horario_fin']      = disp_parse_time($item['horario_fin'] ?? '');

            // servicio (máx 45 chars)
            $servicio = mb_substr(trim((string)($item['servicio'] ?? '')), 0, 45);
            $row['servicio'] = $servicio !== '' ? $servicio : null;

            $row['activo'] = isset($item['activo']) ? ((int)(bool)$item['activo']) : 1;
            $row['orden']  = (int)($item['orden'] ?? $idx);

            $clean[] = $row;
        }

        $db->beginTransaction();
        try {
            // Eliminar todos los ítems previos
            $db->prepare("DELETE FROM disponibles_items WHERE business_id = ?")
               ->execute([$businessId]);

            // Insertar ítems nuevos
            $ins = $db->prepare(
                "INSERT INTO disponibles_items
                 (business_id, precio, precio_a_definir, cantidad, tipo_bien,
                  disponible_desde, disponible_hasta, horario_inicio, horario_fin,
                  servicio, activo, orden)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)"
            );
            foreach ($clean as $r) {
                $ins->execute([
                    $businessId,
                    $r['precio'], $r['precio_a_definir'],
                    $r['cantidad'], $r['tipo_bien'],
                    $r['disponible_desde'], $r['disponible_hasta'],
                    $r['horario_inicio'], $r['horario_fin'],
                    $r['servicio'], $r['activo'], $r['orden'],
                ]);
            }
            $db->commit();
            disp_ok(['saved' => count($clean)], 'Ítems guardados correctamente');
        } catch (Throwable $e) {
            $db->rollBack();
            error_log('disponibles save_items: ' . $e->getMessage());
            disp_err('Error al guardar los ítems', 500);
        }
    }

    disp_err('Acción no reconocida', 400);
}

disp_err('Método no soportado', 405);

// ── Helpers ───────────────────────────────────────────────────────────────────
function disp_parse_date(string $v): ?string {
    $v = trim($v);
    if ($v === '') return null;
    $d = \DateTime::createFromFormat('Y-m-d', $v);
    return $d ? $d->format('Y-m-d') : null;
}
function disp_parse_time(string $v): ?string {
    $v = trim($v);
    if ($v === '') return null;
    if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $v)) {
        $parts = explode(':', $v);
        return sprintf('%02d:%02d:00', (int)$parts[0], (int)$parts[1]);
    }
    return null;
}
