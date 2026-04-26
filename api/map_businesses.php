<?php
/**
 * API de negocios para mapa con filtrado por bounding-box y zoom
 *
 * GET /api/map_businesses.php
 *   ?bbox=south,west,north,east   (lat/lng bounds del viewport)
 *   &zoom=N                       (nivel de zoom actual del mapa, 1–20)
 *   [&type=business_type]         (filtro opcional por tipo)
 *   [&with_brands=1]              (incluir marcas en la respuesta)
 *
 * Reglas de visibilidad:
 *   - Un negocio aparece si zoom >= visibility_min_zoom (o si es null/0 se usa
 *     el default del tipo en business_type_limits; si no existe, se usa 12).
 *   - Los negocios is_premium=1 usan min_zoom más bajo (3 por defecto si
 *     no tienen override).
 *   - Si bbox no se provee, devuelve todos los visibles respetando sólo zoom.
 *
 * Mantiene compatibilidad: devuelve mismo formato que /api/api_comercios.php
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../includes/db_helper.php';

header('Content-Type: application/json; charset=utf-8');

// ── Parámetros ────────────────────────────────────────────────────────────────
$zoom     = isset($_GET['zoom']) ? max(1, min(20, (int)$_GET['zoom'])) : null;
$bboxRaw  = trim($_GET['bbox'] ?? '');
$typeFilter = trim($_GET['type'] ?? '');
$withBrands = !empty($_GET['with_brands']);

// Parsear bbox: "south,west,north,east"
$bbox = null;
if ($bboxRaw !== '') {
    $parts = array_map('floatval', explode(',', $bboxRaw));
    if (count($parts) === 4) {
        [$south, $west, $north, $east] = $parts;
        // Sanity-check de rangos
        if ($south >= -90 && $north <= 90 && $west >= -180 && $east <= 180 && $south < $north) {
            $bbox = compact('south', 'west', 'north', 'east');
        }
    }
}

try {
    $db = getDbConnection();

    // ── Cargar defaults de zoom por tipo desde business_type_limits ──────────
    $typeZoomDefaults = [];
    try {
        $rows = $db->query("SELECT business_type, visibility_min_zoom_default FROM business_type_limits")->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $typeZoomDefaults[$row['business_type']] = (int)$row['visibility_min_zoom_default'];
        }
    } catch (Exception $e) {
        // tabla no existe aún — ignorar
    }

    // ── Construir query ───────────────────────────────────────────────────────
    $params = [];
    $sql = "
        SELECT b.id, b.name, b.address, b.lat, b.lng,
               b.business_type, b.phone, b.website,
               b.visible, b.description, b.price_range,
               b.visibility_min_zoom, b.is_premium, b.images_max,
               b.og_image_url,
               c.tipo_comercio, c.horario_apertura, c.horario_cierre,
               c.dias_cierre, c.timezone
        FROM businesses b
        LEFT JOIN comercios c ON b.id = c.business_id
        WHERE b.visible = 1
          AND b.lat IS NOT NULL
          AND b.lng IS NOT NULL
    ";

    // Filtro bbox
    if ($bbox !== null) {
        $sql .= " AND b.lat BETWEEN ? AND ? AND b.lng BETWEEN ? AND ?";
        $params[] = $bbox['south'];
        $params[] = $bbox['north'];
        $params[] = $bbox['west'];
        $params[] = $bbox['east'];
    }

    // Filtro tipo
    if ($typeFilter !== '') {
        $sql .= " AND b.business_type = ?";
        $params[] = $typeFilter;
    }

    $sql .= " ORDER BY b.is_premium DESC, b.id DESC LIMIT 1000";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $businesses = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    // ── Filtrar por zoom si se proporcionó ────────────────────────────────────
    if ($zoom !== null) {
        $businesses = array_values(array_filter($businesses, function ($b) use ($zoom, $typeZoomDefaults) {
            // Determinar min_zoom efectivo
            if ($b['visibility_min_zoom'] !== null) {
                $minZoom = (int)$b['visibility_min_zoom'];
            } elseif ($b['is_premium']) {
                $minZoom = 3; // premium sin override → visible desde zoom 3
            } elseif (isset($typeZoomDefaults[$b['business_type']])) {
                $minZoom = $typeZoomDefaults[$b['business_type']];
            } else {
                $minZoom = 12; // default global
            }
            return $zoom >= $minZoom;
        }));
    }

    // ── Agregar fotos (primary_photo) ─────────────────────────────────────────
    foreach ($businesses as &$b) {
        $dir   = __DIR__ . '/../uploads/businesses/' . $b['id'] . '/';
        $photo = null;
        if (is_dir($dir)) {
            $files = glob($dir . 'gallery_*.{jpg,jpeg,png,webp}', GLOB_BRACE);
            if ($files) {
                sort($files);
                $photo = '/uploads/businesses/' . $b['id'] . '/' . basename($files[0]);
            }
        }
        $b['primary_photo'] = $photo;
        $b['has_photo']     = $photo !== null;
        $b['photos']        = $photo ? [['url' => $photo]] : [];
    }
    unset($b);

    $response = [
        'success'    => true,
        'message'    => 'Negocios obtenidos correctamente.',
        'zoom'       => $zoom,
        'bbox'       => $bbox,
        'count'      => count($businesses),
        'data'       => $businesses,
    ];

    // ── Marcas (opcional) ─────────────────────────────────────────────────────
    if ($withBrands) {
        $brandParams = [];
        $brandSql    = "SELECT id, nombre, lat, lng, rubro, visible, is_premium,
                               visibility_min_zoom, og_image_url, logo_url
                        FROM brands
                        WHERE visible = 1 AND lat IS NOT NULL AND lng IS NOT NULL";

        // Agregar columnas opcionales si existen (degradación suave)
        // is_premium / visibility_min_zoom en brands (si migration fue ejecutada)

        if ($bbox !== null) {
            $brandSql .= " AND lat BETWEEN ? AND ? AND lng BETWEEN ? AND ?";
            $brandParams[] = $bbox['south'];
            $brandParams[] = $bbox['north'];
            $brandParams[] = $bbox['west'];
            $brandParams[] = $bbox['east'];
        }
        $brandSql .= " ORDER BY id DESC LIMIT 500";

        try {
            $bstmt  = $db->prepare($brandSql);
            $bstmt->execute($brandParams);
            $brands = $bstmt->fetchAll(\PDO::FETCH_ASSOC);

            if ($zoom !== null) {
                $brands = array_values(array_filter($brands, function ($br) use ($zoom) {
                    $minZ = isset($br['visibility_min_zoom']) && $br['visibility_min_zoom'] !== null
                          ? (int)$br['visibility_min_zoom']
                          : (isset($br['is_premium']) && $br['is_premium'] ? 3 : 12);
                    return $zoom >= $minZ;
                }));
            }

            $response['brands'] = $brands;
        } catch (Exception $e) {
            $response['brands'] = [];
        }
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    error_log('map_businesses.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
}
