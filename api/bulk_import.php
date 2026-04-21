<?php
/**
 * POST /api/bulk_import.php
 *
 * Importa negocios o marcas desde un archivo JSON generado por IA.
 * Se espera un multipart/form-data con:
 *   - file: archivo .json (máx 2MB)
 *   - type: "businesses" | "brands"
 *
 * Responde con {success, imported, errors[]} por cada fila del array JSON.
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Solo administradores pueden usar la importación masiva.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no válido.']);
    exit;
}

$type = trim($_POST['type'] ?? '');
if (!in_array($type, ['businesses', 'brands'], true)) {
    echo json_encode(['success' => false, 'error' => 'El parámetro "type" debe ser "businesses" o "brands".']);
    exit;
}

// ── File validation ──────────────────────────────
if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errMsg = match($_FILES['file']['error'] ?? -1) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo supera el tamaño máximo (2MB).',
        UPLOAD_ERR_NO_FILE => 'No se recibió ningún archivo.',
        default => 'Error al recibir el archivo.',
    };
    echo json_encode(['success' => false, 'error' => $errMsg]);
    exit;
}

if ($_FILES['file']['size'] > 2 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'El archivo supera el límite de 2MB.']);
    exit;
}

$raw = file_get_contents($_FILES['file']['tmp_name']);
if ($raw === false) {
    echo json_encode(['success' => false, 'error' => 'No se pudo leer el archivo.']);
    exit;
}

$items = json_decode($raw, true);
if (!is_array($items) || empty($items)) {
    echo json_encode(['success' => false, 'error' => 'El archivo no contiene un array JSON válido.']);
    exit;
}

// ── DB connection ────────────────────────────────
try {
    $db = \Core\Database::getInstance()->getConnection();
} catch (Throwable $e) {
    error_log('bulk_import db: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos.']);
    exit;
}

$userId   = (int)$_SESSION['user_id'];
$imported = 0;
$errors   = [];

// ── NEGOCIOS ─────────────────────────────────────
if ($type === 'businesses') {
    $REQUIRED = ['name', 'business_type', 'address', 'lat', 'lng'];
    $VALID_TYPES = [
        'restaurante','cafeteria','bar','panaderia','heladeria','pizzeria',
        'supermercado','comercio','autos_venta','motos_venta','indumentaria','ferreteria',
        'electronica','muebleria','floristeria','libreria',
        'productora_audiovisual','escuela_musicos','taller_artes','biodecodificacion','libreria_cristiana',
        'farmacia','hospital','odontologia','veterinaria','optica',
        'salon_belleza','barberia','spa','gimnasio',
        'banco','inmobiliaria','seguros','abogado','contador','taller','construccion','remate',
        'academia','escuela','hotel','turismo','cine','otros'
    ];

    $stmt = $db->prepare("
        INSERT INTO businesses
            (user_id, name, business_type, address, lat, lng, phone, email, website,
             instagram, facebook, tiktok, description, certifications,
             has_delivery, has_card_payment, is_franchise, price_range,
             company_size, location_city, style, visible, status, created_at, updated_at)
        VALUES
            (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1,'active',NOW(),NOW())
    ");

    foreach ($items as $i => $row) {
        $rowNum = $i + 1;

        // Required field check
        $missing = [];
        foreach ($REQUIRED as $f) {
            if (!isset($row[$f]) || (string)$row[$f] === '') $missing[] = $f;
        }
        if ($missing) {
            $errors[] = "Fila {$rowNum}: faltan campos obligatorios: " . implode(', ', $missing);
            continue;
        }

        if (!in_array($row['business_type'], $VALID_TYPES, true)) {
            $errors[] = "Fila {$rowNum}: business_type '{$row['business_type']}' no es válido.";
            continue;
        }

        $lat = filter_var($row['lat'], FILTER_VALIDATE_FLOAT);
        $lng = filter_var($row['lng'], FILTER_VALIDATE_FLOAT);
        if ($lat === false || $lng === false) {
            $errors[] = "Fila {$rowNum}: lat/lng no son números válidos.";
            continue;
        }

        try {
            $stmt->execute([
                $userId,
                mb_substr((string)$row['name'], 0, 255),
                $row['business_type'],
                mb_substr((string)$row['address'], 0, 500),
                $lat,
                $lng,
                isset($row['phone'])          ? mb_substr((string)$row['phone'], 0, 50)           : null,
                isset($row['email'])          ? mb_substr((string)$row['email'], 0, 255)          : null,
                isset($row['website'])        ? mb_substr((string)$row['website'], 0, 500)        : null,
                isset($row['instagram'])      ? mb_substr((string)$row['instagram'], 0, 100)      : null,
                isset($row['facebook'])       ? mb_substr((string)$row['facebook'], 0, 255)       : null,
                isset($row['tiktok'])         ? mb_substr((string)$row['tiktok'], 0, 100)         : null,
                isset($row['description'])    ? mb_substr((string)$row['description'], 0, 2000)   : null,
                isset($row['certifications']) ? mb_substr((string)$row['certifications'], 0, 500) : null,
                isset($row['has_delivery'])   ? (int)(bool)$row['has_delivery']                   : 0,
                isset($row['has_card_payment'])? (int)(bool)$row['has_card_payment']              : 0,
                isset($row['is_franchise'])   ? (int)(bool)$row['is_franchise']                   : 0,
                isset($row['price_range'])    ? min(5, max(1, (int)$row['price_range']))           : null,
                isset($row['company_size'])   ? mb_substr((string)$row['company_size'], 0, 50)    : null,
                isset($row['location_city'])  ? mb_substr((string)$row['location_city'], 0, 100)  : null,
                isset($row['style'])          ? mb_substr((string)$row['style'], 0, 255)           : null,
            ]);
            $imported++;
        } catch (Throwable $e) {
            error_log("bulk_import negocio fila {$rowNum}: " . $e->getMessage());
            $errors[] = "Fila {$rowNum}: error al insertar (" . $e->getMessage() . ')';
        }
    }
}

// ── MARCAS ───────────────────────────────────────
if ($type === 'brands') {
    $REQUIRED = ['nombre', 'rubro'];

    $stmt = $db->prepare("
        INSERT INTO brands
            (user_id, nombre, rubro, website, ubicacion, lat, lng, description, extended_description,
             clase_principal, founded_year, annual_revenue,
             instagram, facebook, tiktok, twitter, linkedin, youtube, whatsapp,
             historia_marca, target_audience, propuesta_valor,
             inpi_registrada, inpi_numero, inpi_fecha_registro, inpi_vencimiento,
             inpi_clases_registradas, inpi_tipo,
             es_franquicia, tiene_zona, zona_radius_km, tiene_licencia, estado,
             visible, created_at, updated_at)
        VALUES
            (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1,NOW(),NOW())
    ");

    foreach ($items as $i => $row) {
        $rowNum = $i + 1;

        $missing = [];
        foreach ($REQUIRED as $f) {
            if (!isset($row[$f]) || (string)$row[$f] === '') $missing[] = $f;
        }
        if ($missing) {
            $errors[] = "Fila {$rowNum}: faltan campos obligatorios: " . implode(', ', $missing);
            continue;
        }

        $lat = isset($row['lat']) ? filter_var($row['lat'], FILTER_VALIDATE_FLOAT) : null;
        $lng = isset($row['lng']) ? filter_var($row['lng'], FILTER_VALIDATE_FLOAT) : null;

        try {
            $stmt->execute([
                $userId,
                mb_substr((string)$row['nombre'], 0, 255),
                mb_substr((string)$row['rubro'], 0, 255),
                isset($row['website'])          ? mb_substr((string)$row['website'], 0, 500)          : null,
                isset($row['ubicacion'])        ? mb_substr((string)$row['ubicacion'], 0, 255)        : 'Argentina',
                ($lat !== false && $lat !== null) ? $lat : null,
                ($lng !== false && $lng !== null) ? $lng : null,
                isset($row['description'])      ? mb_substr((string)$row['description'], 0, 2000)     : null,
                isset($row['extended_description'])? mb_substr((string)$row['extended_description'], 0, 5000) : null,
                isset($row['clase_principal'])  ? mb_substr((string)$row['clase_principal'], 0, 50)   : null,
                isset($row['founded_year'])     ? (int)$row['founded_year']                           : null,
                isset($row['annual_revenue'])   ? mb_substr((string)$row['annual_revenue'], 0, 100)   : null,
                isset($row['instagram'])        ? mb_substr((string)$row['instagram'], 0, 100)        : null,
                isset($row['facebook'])         ? mb_substr((string)$row['facebook'], 0, 255)         : null,
                isset($row['tiktok'])           ? mb_substr((string)$row['tiktok'], 0, 100)           : null,
                isset($row['twitter'])          ? mb_substr((string)$row['twitter'], 0, 100)          : null,
                isset($row['linkedin'])         ? mb_substr((string)$row['linkedin'], 0, 255)         : null,
                isset($row['youtube'])          ? mb_substr((string)$row['youtube'], 0, 255)          : null,
                isset($row['whatsapp'])         ? mb_substr((string)$row['whatsapp'], 0, 50)          : null,
                isset($row['historia_marca'])   ? mb_substr((string)$row['historia_marca'], 0, 10000) : null,
                isset($row['target_audience'])  ? mb_substr((string)$row['target_audience'], 0, 1000) : null,
                isset($row['propuesta_valor'])  ? mb_substr((string)$row['propuesta_valor'], 0, 1000) : null,
                isset($row['inpi_registrada'])  ? (int)(bool)$row['inpi_registrada']                  : 0,
                isset($row['inpi_numero'])      ? mb_substr((string)$row['inpi_numero'], 0, 50)       : null,
                isset($row['inpi_fecha_registro'])? $row['inpi_fecha_registro']                       : null,
                isset($row['inpi_vencimiento']) ? $row['inpi_vencimiento']                            : null,
                isset($row['inpi_clases_registradas'])? mb_substr((string)$row['inpi_clases_registradas'], 0, 255) : null,
                isset($row['inpi_tipo'])        ? mb_substr((string)$row['inpi_tipo'], 0, 100)        : null,
                isset($row['es_franquicia'])    ? (int)(bool)$row['es_franquicia']                    : 0,
                isset($row['tiene_zona'])       ? (int)(bool)$row['tiene_zona']                       : 0,
                isset($row['zona_radius_km'])   ? (int)$row['zona_radius_km']                         : null,
                isset($row['tiene_licencia'])   ? (int)(bool)$row['tiene_licencia']                   : 0,
                isset($row['estado'])           ? mb_substr((string)$row['estado'], 0, 50)            : 'Activa',
            ]);
            $imported++;
        } catch (Throwable $e) {
            error_log("bulk_import marca fila {$rowNum}: " . $e->getMessage());
            $errors[] = "Fila {$rowNum}: error al insertar (" . $e->getMessage() . ')';
        }
    }
}

echo json_encode([
    'success'  => true,
    'imported' => $imported,
    'total'    => count($items),
    'errors'   => $errors,
    'message'  => "{$imported} de " . count($items) . " importados correctamente." . (count($errors) ? ' ' . count($errors) . ' con errores.' : ''),
]);
