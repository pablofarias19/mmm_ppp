<?php
/**
 * API de Inmuebles para mapa – filtrado por bounding-box y zoom
 *
 * GET /api/map_inmuebles.php
 *   ?bbox=south,west,north,east   (lat/lng bounds del viewport)
 *   &zoom=N                       (nivel de zoom actual, 1–20)
 *   [&business_id=N]              (filtro por inmobiliaria)
 *   [&tipo=casa|departamento|…]   (filtro por subcategoría)
 *   [&operacion=venta|alquiler]   (filtro por operación)
 *   [&q=texto]                    (búsqueda libre en título/dirección)
 *
 * Devuelve inmuebles activos dentro del bbox.
 * Los negocios with inmuebles_destacado=1 aparecen primero.
 * Si no hay bbox, devuelve todos (hasta LIMIT).
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../includes/db_helper.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

// ── Parámetros ───────────────────────────────────────────────────────────────
$zoom      = isset($_GET['zoom']) ? max(1, min(20, (int)$_GET['zoom'])) : null;
$bboxRaw   = trim($_GET['bbox'] ?? '');
$bizFilter = (int)($_GET['business_id'] ?? 0);
$tipoRaw   = trim($_GET['tipo'] ?? '');
$operacion = in_array($_GET['operacion'] ?? '', ['venta','alquiler'], true) ? $_GET['operacion'] : '';
$q         = mb_substr(trim($_GET['q'] ?? ''), 0, 100);

$validTipos = ['casa','departamento','lote','proyecto','local','oficina'];
$tipo = in_array($tipoRaw, $validTipos, true) ? $tipoRaw : '';

// Parsear bbox
$bbox = null;
if ($bboxRaw !== '') {
    $parts = array_map('floatval', explode(',', $bboxRaw));
    if (count($parts) === 4) {
        [$south, $west, $north, $east] = $parts;
        if ($south >= -90 && $north <= 90 && $west >= -180 && $east <= 180 && $south < $north) {
            $bbox = compact('south', 'west', 'north', 'east');
        }
    }
}

try {
    $db = getDbConnection();
    if (!$db) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Sin conexión']);
        exit;
    }

    if (!mapitaTableExists($db, 'inmuebles')) {
        echo json_encode(['success' => true, 'data' => [], 'count' => 0, 'message' => 'Tabla no disponible']);
        exit;
    }

    // Columnas opcionales
    $hasExtended = mapitaColumnExists($db, 'inmuebles', 'tipo');
    $hasDestacado = mapitaColumnExists($db, 'businesses', 'inmuebles_destacado');

    $extCols    = $hasExtended  ? ", i.tipo, i.financiado, i.ambientes, i.superficie_m2" : "";
    $destCol    = $hasDestacado ? ", b.inmuebles_destacado" : "";
    $orderDest  = $hasDestacado ? "b.inmuebles_destacado DESC, " : "";

    // ── Construir query ──────────────────────────────────────────────────────
    $params = [];
    $sql = "
        SELECT i.id, i.business_id, i.operacion, i.titulo, i.descripcion,
               i.precio, i.moneda, i.direccion, i.lat, i.lng,
               i.foto_url, i.contacto, i.activo, i.created_at,
               b.name AS inmobiliaria_nombre, b.icon_url AS inmobiliaria_icon,
               b.lat AS inm_lat_fallback, b.lng AS inm_lng_fallback{$destCol}{$extCols}
        FROM inmuebles i
        JOIN businesses b ON b.id = i.business_id
        WHERE i.activo = 1
          AND (i.lat IS NOT NULL OR b.lat IS NOT NULL)
    ";

    if ($bbox !== null) {
        $sql .= " AND COALESCE(i.lat, b.lat) BETWEEN ? AND ?
                  AND COALESCE(i.lng, b.lng) BETWEEN ? AND ?";
        $params[] = $bbox['south'];
        $params[] = $bbox['north'];
        $params[] = $bbox['west'];
        $params[] = $bbox['east'];
    }

    if ($bizFilter > 0) {
        $sql .= " AND i.business_id = ?";
        $params[] = $bizFilter;
    }

    if ($tipo !== '') {
        $sql .= " AND i.tipo = ?";
        $params[] = $tipo;
    }

    if ($operacion !== '') {
        $sql .= " AND i.operacion = ?";
        $params[] = $operacion;
    }

    if ($q !== '') {
        $sql .= " AND (i.titulo LIKE ? OR i.direccion LIKE ?)";
        $params[] = '%' . $q . '%';
        $params[] = '%' . $q . '%';
    }

    $sql .= " ORDER BY {$orderDest}i.created_at DESC LIMIT 500";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Inmuebles obtenidos',
        'zoom'    => $zoom,
        'bbox'    => $bbox,
        'count'   => count($rows),
        'data'    => $rows,
    ]);

} catch (\Throwable $e) {
    error_log('map_inmuebles.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno', 'data' => []]);
}
