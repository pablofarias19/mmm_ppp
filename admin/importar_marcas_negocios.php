<?php
/**
 * admin/importar_marcas_negocios.php
 *
 * Módulo de importación masiva de negocios y marcas para Mapita.
 *
 * Flujo:
 *   1. El admin sube un archivo .json con un array de objetos.
 *   2. El servidor valida reglas de negocio y geocodifica direcciones faltantes
 *      usando la API pública de OpenStreetMap Nominatim (gratuita).
 *   3. Se muestra un resumen: registros válidos, errores y duplicados detectados.
 *   4. El admin confirma la importación para escribir en la base de datos.
 *
 * Uso:
 *   Acceder a /admin/importar_marcas_negocios.php desde el navegador (requiere rol admin).
 *   Subir el JSON generado con el prompt de admin/bulk_import_templates.md.
 *
 * Mejoras futuras:
 *   - Paginación del reporte cuando los arrays son muy grandes (> 500 registros).
 *   - Soporte para archivos CSV/Excel con conversión automática.
 *   - Cola asíncrona para geocodificación en volúmenes altos (> 100 registros).
 *   - Vista previa de mapa con pins antes de confirmar la importación.
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';

// ── Auth ─────────────────────────────────────────────────────────────────────
if (!isAdmin()) {
    http_response_code(403);
    echo '<!DOCTYPE html><html><body><p>Acceso denegado. Solo administradores.</p></body></html>';
    exit;
}

// ── Constantes de validación ─────────────────────────────────────────────────

/** Campos obligatorios para marcas */
const BRAND_REQUIRED = ['nombre', 'rubro', 'inpi_registrada', 'es_franquicia', 'tiene_zona', 'tiene_licencia', 'estado'];

/** Valores válidos para el campo estado */
const BRAND_ESTADO_VALID = ['Activa', 'Inactiva'];

/** Campos booleanos (0 o 1) para marcas */
const BRAND_BOOL_FIELDS = ['inpi_registrada', 'es_franquicia', 'tiene_zona', 'tiene_licencia'];

/** Campos de fecha INPI que deben seguir formato YYYY-MM-DD */
const BRAND_DATE_FIELDS = ['inpi_fecha_registro', 'inpi_vencimiento'];

/** Coordenadas de fallback cuando no hay dirección geocodificable (centro de Argentina) */
const ARGENTINA_LAT = -34.6037;
const ARGENTINA_LNG = -58.3816;

/** Límite de tamaño de archivo en bytes (2 MB) */
const MAX_FILE_SIZE = 2 * 1024 * 1024;

// ── Helpers de validación ─────────────────────────────────────────────────────

/**
 * Valida que un valor sea 0 o 1 (boolean entero para DB).
 * Acepta tanto entero como string.
 */
function isValidBoolInt(mixed $v): bool {
    return $v === 0 || $v === 1 || $v === '0' || $v === '1';
}

/**
 * Valida formato de fecha YYYY-MM-DD.
 */
function isValidDate(mixed $v): bool {
    if (!is_string($v) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) return false;
    [$y, $m, $d] = explode('-', $v);
    return checkdate((int)$m, (int)$d, (int)$y);
}

/**
 * Geocodifica una dirección usando Nominatim (OpenStreetMap).
 * Devuelve ['lat' => float, 'lng' => float] o null si no se pudo geocodificar.
 *
 * Nota: Nominatim exige una pausa de al menos 1 segundo entre requests según sus ToS.
 * Este helper NO incluye la pausa — el llamador debe manejar el rate limiting
 * (ver bucle de procesamiento más abajo).
 *
 * Mejora futura: Cachear resultados por dirección normalizada para evitar
 * requests duplicados cuando el mismo titular aparece en varias marcas.
 */
function geocodeAddress(string $address): ?array {
    // Timeout de 5 s para no bloquear la importación si Nominatim tarda
    $ctx = stream_context_create([
        'http' => [
            'timeout'     => 5,
            'method'      => 'GET',
            'header'      => "User-Agent: Mapita-BulkImport/1.0 (contact@mapita.app)\r\n",
        ],
    ]);

    $url  = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' . urlencode($address);
    $resp = @file_get_contents($url, false, $ctx);

    if ($resp === false) return null;

    $data = json_decode($resp, true);
    if (!is_array($data) || empty($data)) return null;

    $lat = (float)$data[0]['lat'];
    $lng = (float)$data[0]['lon'];

    // Sanity-check: coordenadas fuera de rango mundial
    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) return null;

    return ['lat' => $lat, 'lng' => $lng];
}

/**
 * Construye la cadena de dirección a geocodificar a partir de los campos del objeto.
 * Aplica la regla de ubicación del sistema:
 *   1. domicilio del titular
 *   2. domicilio declarado en Argentina (si titular es extranjero)
 *   3. Fallback a 'Argentina'
 */
function buildGeoAddress(array $row): ?string {
    // Campo dirección explícita (negocios y marcas)
    if (!empty($row['address'])) return (string)$row['address'];
    if (!empty($row['ubicacion']) && strtolower(trim($row['ubicacion'])) !== 'argentina') {
        return (string)$row['ubicacion'];
    }
    return null; // Sin dirección geocodificable → usaremos fallback
}

/**
 * Valida un objeto de marca y devuelve lista de errores.
 * Devuelve array vacío si el objeto es válido.
 */
function validateBrand(array $row, int $rowNum): array {
    $errs = [];

    // ── Campos obligatorios ───────────────────────────────────────────────────
    foreach (BRAND_REQUIRED as $field) {
        if (!array_key_exists($field, $row) || $row[$field] === '' || $row[$field] === null) {
            $errs[] = "Fila {$rowNum}: campo obligatorio ausente o vacío: '{$field}'.";
        }
    }

    // Detener si ya hay errores de campos obligatorios para evitar cascada
    if ($errs) return $errs;

    // ── nombre ────────────────────────────────────────────────────────────────
    if (!is_string($row['nombre'])) {
        $errs[] = "Fila {$rowNum}: 'nombre' debe ser string.";
    } elseif (mb_strlen($row['nombre']) > 255) {
        $errs[] = "Fila {$rowNum}: 'nombre' supera 255 caracteres.";
    }

    // ── rubro ─────────────────────────────────────────────────────────────────
    if (!is_string($row['rubro'])) {
        $errs[] = "Fila {$rowNum}: 'rubro' debe ser string.";
    }

    // ── booleanos enteros ─────────────────────────────────────────────────────
    foreach (BRAND_BOOL_FIELDS as $bf) {
        if (array_key_exists($bf, $row) && !isValidBoolInt($row[$bf])) {
            $errs[] = "Fila {$rowNum}: '{$bf}' debe ser 0 o 1.";
        }
    }

    // ── estado ────────────────────────────────────────────────────────────────
    if (!in_array($row['estado'], BRAND_ESTADO_VALID, true)) {
        $errs[] = "Fila {$rowNum}: 'estado' debe ser 'Activa' o 'Inactiva'.";
    }

    // ── fechas INPI ───────────────────────────────────────────────────────────
    foreach (BRAND_DATE_FIELDS as $df) {
        if (!empty($row[$df]) && !isValidDate($row[$df])) {
            $errs[] = "Fila {$rowNum}: '{$df}' debe tener formato YYYY-MM-DD (ej: 2023-05-20).";
        }
    }

    // ── lat / lng opcionales ──────────────────────────────────────────────────
    if (!empty($row['lat']) && filter_var($row['lat'], FILTER_VALIDATE_FLOAT) === false) {
        $errs[] = "Fila {$rowNum}: 'lat' no es un número decimal válido.";
    }
    if (!empty($row['lng']) && filter_var($row['lng'], FILTER_VALIDATE_FLOAT) === false) {
        $errs[] = "Fila {$rowNum}: 'lng' no es un número decimal válido.";
    }

    return $errs;
}

/**
 * Valida un objeto de negocio y devuelve lista de errores.
 */
function validateBusiness(array $row, int $rowNum): array {
    $errs = [];
    $required = ['name', 'business_type'];

    foreach ($required as $field) {
        if (!array_key_exists($field, $row) || $row[$field] === '' || $row[$field] === null) {
            $errs[] = "Fila {$rowNum}: campo obligatorio ausente: '{$field}'.";
        }
    }

    if ($errs) return $errs;

    if (!is_string($row['name'])) {
        $errs[] = "Fila {$rowNum}: 'name' debe ser string.";
    } elseif (mb_strlen($row['name']) > 255) {
        $errs[] = "Fila {$rowNum}: 'name' supera 255 caracteres.";
    }

    foreach (['has_delivery', 'has_card_payment', 'is_franchise'] as $bf) {
        if (array_key_exists($bf, $row) && !isValidBoolInt($row[$bf])) {
            $errs[] = "Fila {$rowNum}: '{$bf}' debe ser 0 o 1.";
        }
    }

    return $errs;
}

/**
 * Detecta duplicados dentro del array de marcas por campo 'nombre'.
 * Cuando un nombre aparece más de una vez, consolida inpi_clases_registradas
 * y agrega nota en extended_description. Devuelve el array deduplicado.
 *
 * Mejora futura: considerar también campo 'inpi_numero' como discriminador.
 */
function deduplicateBrands(array $items): array {
    $seen   = []; // nombre_normalizado => índice en $result
    $result = [];
    $dupes  = 0;

    foreach ($items as $row) {
        $key = mb_strtolower(trim($row['nombre'] ?? ''));
        if ($key === '') {
            $result[] = $row;
            continue;
        }

        if (!isset($seen[$key])) {
            $seen[$key]  = count($result);
            $result[]    = $row;
        } else {
            // Consolidar clases NIZA
            $idx     = $seen[$key];
            $existing = $result[$idx];

            // Unir inpi_clases_registradas
            $classesA = array_filter(array_map('trim', explode(',', $existing['inpi_clases_registradas'] ?? '')));
            $classesB = array_filter(array_map('trim', explode(',', $row['inpi_clases_registradas'] ?? '')));
            $merged   = array_unique(array_merge($classesA, $classesB));
            sort($merged, SORT_NUMERIC);
            $result[$idx]['inpi_clases_registradas'] = implode(',', $merged);

            // Agregar nota en extended_description
            $extra = "Clases NIZA adicionales consolidadas: " . implode(',', array_diff($classesB, $classesA)) . ".";
            if (!empty($result[$idx]['extended_description'])) {
                $result[$idx]['extended_description'] .= ' ' . $extra;
            } else {
                $result[$idx]['extended_description'] = $extra;
            }

            $dupes++;
        }
    }

    return [$result, $dupes];
}

// ── Procesamiento del formulario ──────────────────────────────────────────────

$step       = 'form';   // 'form' | 'preview' | 'done'
$type       = '';
$errors     = [];
$warnings   = [];
$validItems = [];
$report     = [];
$importDone = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? 'validate';

    // ── PASO 1: Validar y previsualizar ───────────────────────────────────────
    if ($action === 'validate') {

        $type = trim($_POST['type'] ?? '');
        if (!in_array($type, ['brands', 'businesses'], true)) {
            $errors[] = 'Seleccioná un tipo de importación válido (Marcas o Negocios).';
        }

        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $errCode = $_FILES['file']['error'] ?? -1;
            $errors[] = match($errCode) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo supera el tamaño máximo (2MB).',
                UPLOAD_ERR_NO_FILE => 'No se recibió ningún archivo.',
                default            => 'Error al recibir el archivo (código ' . $errCode . ').',
            };
        } elseif ($_FILES['file']['size'] > MAX_FILE_SIZE) {
            $errors[] = 'El archivo supera el límite de 2MB.';
        }

        if (empty($errors)) {
            $raw = file_get_contents($_FILES['file']['tmp_name']);
            if ($raw === false) {
                $errors[] = 'No se pudo leer el archivo.';
            } else {
                // Validar JSON
                $decoded = json_decode($raw, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $errors[] = 'El archivo no es JSON válido: ' . json_last_error_msg();
                } elseif (!is_array($decoded) || (count($decoded) > 0 && array_keys($decoded) !== range(0, count($decoded) - 1))) {
                    // Verificar que la raíz sea un array indexado (no objeto)
                    $errors[] = 'La raíz del JSON debe ser un array [ { ... }, { ... } ], no un objeto.';
                } elseif (empty($decoded)) {
                    $errors[] = 'El JSON está vacío.';
                } else {
                    $rowErrors     = [];
                    $validated     = [];
                    $dupesCount    = 0;

                    if ($type === 'brands') {
                        // Deduplicar primero, luego validar
                        [$decoded, $dupesCount] = deduplicateBrands($decoded);

                        foreach ($decoded as $i => $row) {
                            $rowNum   = $i + 1;
                            $rowErrs  = validateBrand($row, $rowNum);
                            if ($rowErrs) {
                                $rowErrors = array_merge($rowErrors, $rowErrs);
                            } else {
                                $validated[] = $row;
                            }
                        }
                    } else {
                        // businesses
                        foreach ($decoded as $i => $row) {
                            $rowNum  = $i + 1;
                            $rowErrs = validateBusiness($row, $rowNum);
                            if ($rowErrs) {
                                $rowErrors = array_merge($rowErrors, $rowErrs);
                            } else {
                                $validated[] = $row;
                            }
                        }
                    }

                    // ── Geocodificación ───────────────────────────────────────
                    $geoOk      = 0;
                    $geoFail    = 0;
                    $geoFallback = 0;

                    foreach ($validated as &$item) {
                        $hasLat = !empty($item['lat']) && filter_var($item['lat'], FILTER_VALIDATE_FLOAT) !== false;
                        $hasLng = !empty($item['lng']) && filter_var($item['lng'], FILTER_VALIDATE_FLOAT) !== false;

                        if ($hasLat && $hasLng) {
                            // Ya tiene coordenadas, no es necesario geocodificar
                            $geoOk++;
                            continue;
                        }

                        $geoAddr = buildGeoAddress($item);

                        if ($geoAddr !== null) {
                            // Pausa de 1 s requerida por las ToS de Nominatim
                            sleep(1);
                            $coords = geocodeAddress($geoAddr);
                            if ($coords !== null) {
                                $item['lat'] = $coords['lat'];
                                $item['lng'] = $coords['lng'];
                                $geoOk++;
                            } else {
                                // Fallo en geocodificación → fallback
                                $item['lat'] = ARGENTINA_LAT;
                                $item['lng'] = ARGENTINA_LNG;
                                $geoFail++;
                                $warnings[] = "Fila (nombre: " . ($item['nombre'] ?? $item['name'] ?? '?') . "): no se pudo geocodificar '{$geoAddr}'; se usó coordenadas de Argentina.";
                            }
                        } else {
                            // Sin dirección → fallback directo
                            $item['lat'] = ARGENTINA_LAT;
                            $item['lng'] = ARGENTINA_LNG;
                            $geoFallback++;
                        }
                    }
                    unset($item);

                    $validItems = $validated;

                    $report = [
                        'total'       => count($decoded),
                        'valid'       => count($validItems),
                        'error_count' => count($rowErrors),
                        'duplicates'  => $dupesCount,
                        'geo_ok'      => $geoOk,
                        'geo_fail'    => $geoFail,
                        'geo_fallback'=> $geoFallback,
                        'row_errors'  => $rowErrors,
                    ];

                    // Guardar datos validados en sesión para el paso de importación
                    $_SESSION['bulk_import_data']  = $validItems;
                    $_SESSION['bulk_import_type']  = $type;
                    $_SESSION['bulk_import_report'] = $report;

                    $step = 'preview';
                }
            }
        }
    }

    // ── PASO 2: Confirmar importación ─────────────────────────────────────────
    if ($action === 'import') {
        $validItems = $_SESSION['bulk_import_data']  ?? [];
        $type       = $_SESSION['bulk_import_type']  ?? '';
        $report     = $_SESSION['bulk_import_report'] ?? [];

        if (empty($validItems) || !in_array($type, ['brands', 'businesses'], true)) {
            $errors[] = 'No hay datos válidos en sesión para importar. Volvé al paso anterior.';
        } else {
            try {
                $db     = \Core\Database::getInstance()->getConnection();
                $userId = (int)$_SESSION['user_id'];

                $imported = 0;
                $importErrors = [];

                if ($type === 'brands') {
                    $stmt = $db->prepare("
                        INSERT INTO brands
                            (user_id, nombre, rubro, website, ubicacion, lat, lng,
                             description, extended_description, clase_principal,
                             founded_year, annual_revenue,
                             instagram, facebook, tiktok, twitter, linkedin, youtube, whatsapp,
                             historia_marca, target_audience, propuesta_valor,
                             inpi_registrada, inpi_numero, inpi_fecha_registro, inpi_vencimiento,
                             inpi_clases_registradas, inpi_tipo,
                             es_franquicia, tiene_zona, zona_radius_km, tiene_licencia, estado,
                             visible, created_at, updated_at)
                        VALUES
                            (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1,NOW(),NOW())
                    ");

                    foreach ($validItems as $i => $row) {
                        $rowNum = $i + 1;
                        try {
                            $stmt->execute([
                                $userId,
                                mb_substr((string)$row['nombre'], 0, 255),
                                mb_substr((string)$row['rubro'],  0, 255),
                                isset($row['website'])           ? mb_substr((string)$row['website'], 0, 500)           : null,
                                isset($row['ubicacion'])         ? mb_substr((string)$row['ubicacion'], 0, 255)         : 'Argentina',
                                (float)($row['lat'] ?? ARGENTINA_LAT),
                                (float)($row['lng'] ?? ARGENTINA_LNG),
                                isset($row['description'])       ? mb_substr((string)$row['description'], 0, 2000)      : null,
                                isset($row['extended_description'])? mb_substr((string)$row['extended_description'], 0, 5000) : null,
                                isset($row['clase_principal'])   ? mb_substr((string)$row['clase_principal'], 0, 50)    : null,
                                isset($row['founded_year'])      ? (int)$row['founded_year']                            : null,
                                isset($row['annual_revenue'])    ? mb_substr((string)$row['annual_revenue'], 0, 100)    : null,
                                isset($row['instagram'])         ? mb_substr((string)$row['instagram'], 0, 100)         : null,
                                isset($row['facebook'])          ? mb_substr((string)$row['facebook'], 0, 255)          : null,
                                isset($row['tiktok'])            ? mb_substr((string)$row['tiktok'], 0, 100)            : null,
                                isset($row['twitter'])           ? mb_substr((string)$row['twitter'], 0, 100)           : null,
                                isset($row['linkedin'])          ? mb_substr((string)$row['linkedin'], 0, 255)          : null,
                                isset($row['youtube'])           ? mb_substr((string)$row['youtube'], 0, 255)           : null,
                                isset($row['whatsapp'])          ? mb_substr((string)$row['whatsapp'], 0, 50)           : null,
                                isset($row['historia_marca'])    ? mb_substr((string)$row['historia_marca'], 0, 10000)  : null,
                                isset($row['target_audience'])   ? mb_substr((string)$row['target_audience'], 0, 1000)  : null,
                                isset($row['propuesta_valor'])   ? mb_substr((string)$row['propuesta_valor'], 0, 1000)  : null,
                                (int)($row['inpi_registrada'] ?? 0),
                                isset($row['inpi_numero'])       ? mb_substr((string)$row['inpi_numero'], 0, 50)        : null,
                                !empty($row['inpi_fecha_registro']) ? $row['inpi_fecha_registro']  : null,
                                !empty($row['inpi_vencimiento'])    ? $row['inpi_vencimiento']      : null,
                                isset($row['inpi_clases_registradas'])? mb_substr((string)$row['inpi_clases_registradas'], 0, 255) : null,
                                isset($row['inpi_tipo'])         ? mb_substr((string)$row['inpi_tipo'], 0, 100)         : null,
                                (int)($row['es_franquicia']  ?? 0),
                                (int)($row['tiene_zona']     ?? 0),
                                isset($row['zona_radius_km']) ? (int)$row['zona_radius_km'] : null,
                                (int)($row['tiene_licencia'] ?? 0),
                                in_array($row['estado'] ?? '', ['Activa','Inactiva'], true) ? $row['estado'] : 'Activa',
                            ]);
                            $imported++;
                        } catch (Throwable $e) {
                            error_log("importar_marcas_negocios marca fila {$rowNum}: " . $e->getMessage());
                            $importErrors[] = "Fila {$rowNum} ('{$row['nombre']}'): error al insertar.";
                        }
                    }
                } else {
                    // businesses
                    $stmt = $db->prepare("
                        INSERT INTO businesses
                            (user_id, name, business_type, address, lat, lng,
                             phone, email, website, instagram, facebook, tiktok,
                             description, certifications,
                             has_delivery, has_card_payment, is_franchise,
                             price_range, company_size, location_city, style,
                             visible, status, created_at, updated_at)
                        VALUES
                            (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1,'active',NOW(),NOW())
                    ");

                    foreach ($validItems as $i => $row) {
                        $rowNum = $i + 1;
                        $lat = filter_var($row['lat'] ?? ARGENTINA_LAT, FILTER_VALIDATE_FLOAT);
                        $lng = filter_var($row['lng'] ?? ARGENTINA_LNG, FILTER_VALIDATE_FLOAT);
                        try {
                            $stmt->execute([
                                $userId,
                                mb_substr((string)$row['name'], 0, 255),
                                $row['business_type'],
                                isset($row['address'])         ? mb_substr((string)$row['address'], 0, 500)          : 'Argentina',
                                $lat !== false ? $lat : ARGENTINA_LAT,
                                $lng !== false ? $lng : ARGENTINA_LNG,
                                isset($row['phone'])           ? mb_substr((string)$row['phone'], 0, 50)             : null,
                                isset($row['email'])           ? mb_substr((string)$row['email'], 0, 255)            : null,
                                isset($row['website'])         ? mb_substr((string)$row['website'], 0, 500)          : null,
                                isset($row['instagram'])       ? mb_substr((string)$row['instagram'], 0, 100)        : null,
                                isset($row['facebook'])        ? mb_substr((string)$row['facebook'], 0, 255)         : null,
                                isset($row['tiktok'])          ? mb_substr((string)$row['tiktok'], 0, 100)           : null,
                                isset($row['description'])     ? mb_substr((string)$row['description'], 0, 2000)     : null,
                                isset($row['certifications'])  ? mb_substr((string)$row['certifications'], 0, 500)   : null,
                                isset($row['has_delivery'])    ? (int)(bool)$row['has_delivery']                     : 0,
                                isset($row['has_card_payment'])? (int)(bool)$row['has_card_payment']                 : 0,
                                isset($row['is_franchise'])    ? (int)(bool)$row['is_franchise']                     : 0,
                                isset($row['price_range'])     ? min(5, max(1, (int)$row['price_range']))            : null,
                                isset($row['company_size'])    ? mb_substr((string)$row['company_size'], 0, 50)      : null,
                                isset($row['location_city'])   ? mb_substr((string)$row['location_city'], 0, 100)   : null,
                                isset($row['style'])           ? mb_substr((string)$row['style'], 0, 255)            : null,
                            ]);
                            $imported++;
                        } catch (Throwable $e) {
                            error_log("importar_marcas_negocios negocio fila {$rowNum}: " . $e->getMessage());
                            $importErrors[] = "Fila {$rowNum} ('{$row['name']}'): error al insertar.";
                        }
                    }
                }

                // Limpiar sesión
                unset($_SESSION['bulk_import_data'], $_SESSION['bulk_import_type'], $_SESSION['bulk_import_report']);

                $report['imported']      = $imported;
                $report['import_errors'] = $importErrors;
                $importDone = true;
                $step = 'done';

            } catch (Throwable $e) {
                error_log('importar_marcas_negocios db: ' . $e->getMessage());
                $errors[] = 'Error de conexión a la base de datos. Verificá la configuración.';
            }
        }
    }
}

// ── Vista (HTML) ──────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📥 Importar Masiva — Mapita Admin</title>
    <link rel="stylesheet" href="/css/variables-luxury.css">
    <link rel="stylesheet" href="/css/components-buttons.css">
    <link rel="stylesheet" href="/css/components-forms.css">
    <style>
        body {
            background: var(--bg-tertiary, #f3f4f6);
            color: var(--text-primary, #111827);
            font-family: var(--font-family-base, sans-serif);
            margin: 0; padding: 0;
        }
        .container { max-width: 820px; margin: 0 auto; padding: 24px 16px; }
        header {
            background: linear-gradient(135deg, #1e40af 0%, #1d4ed8 100%);
            color: #fff; padding: 24px 28px; border-radius: 12px;
            margin-bottom: 28px; box-shadow: 0 4px 16px rgba(30,64,175,.25);
        }
        header h1 { font-size: 1.5rem; margin: 0 0 4px; }
        header p  { margin: 0; opacity: .85; font-size: .875rem; }
        .back-link {
            display: inline-block; margin-bottom: 16px;
            font-size: .85rem; color: #1d4ed8; text-decoration: none;
        }
        .back-link:hover { text-decoration: underline; }

        /* ── Tarjeta ── */
        .card {
            background: #fff; border-radius: 10px;
            box-shadow: 0 1px 6px rgba(0,0,0,.08);
            padding: 24px; margin-bottom: 20px;
        }
        .card h2 { font-size: 1.1rem; margin: 0 0 16px; color: #1e40af; }

        /* ── Formulario ── */
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: .875rem; font-weight: 600; margin-bottom: 6px; }
        select, input[type=file] {
            width: 100%; padding: 9px 12px;
            border: 1px solid #d1d5db; border-radius: 7px;
            font-size: .9rem; background: #fff;
        }
        .btn {
            display: inline-block; padding: 10px 20px; border-radius: 7px;
            font-size: .9rem; font-weight: 600; cursor: pointer;
            border: none; transition: background .15s;
        }
        .btn-primary { background: #1d4ed8; color: #fff; }
        .btn-primary:hover { background: #1e40af; }
        .btn-success { background: #059669; color: #fff; }
        .btn-success:hover { background: #047857; }
        .btn-secondary { background: #6b7280; color: #fff; }
        .btn-secondary:hover { background: #4b5563; }

        /* ── Alertas ── */
        .alert { padding: 12px 16px; border-radius: 7px; margin-bottom: 16px; font-size: .875rem; }
        .alert-error   { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; }
        .alert-warning { background: #fffbeb; border: 1px solid #fcd34d; color: #92400e; }
        .alert-success { background: #f0fdf4; border: 1px solid #86efac; color: #065f46; }
        .alert-info    { background: #eff6ff; border: 1px solid #93c5fd; color: #1e3a8a; }
        .alert ul { margin: 6px 0 0; padding-left: 18px; }
        .alert li { margin-bottom: 3px; }

        /* ── Reporte ── */
        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 12px; margin-bottom: 20px;
        }
        .stat-box {
            background: #f9fafb; border: 1px solid #e5e7eb;
            border-radius: 8px; padding: 14px 12px; text-align: center;
        }
        .stat-box .num { font-size: 1.6rem; font-weight: 700; color: #1d4ed8; }
        .stat-box .lbl { font-size: .75rem; color: #6b7280; margin-top: 2px; }
        .stat-box.ok  .num { color: #059669; }
        .stat-box.err .num { color: #dc2626; }
        .stat-box.warn .num { color: #d97706; }

        /* ── Tabla de errores ── */
        .error-list { max-height: 240px; overflow-y: auto; font-size: .82rem; }
        .error-list li { padding: 4px 0; border-bottom: 1px solid #f3f4f6; }

        /* ── JSON preview ── */
        .json-preview {
            background: #1e293b; color: #e2e8f0; border-radius: 8px;
            padding: 14px; font-size: .75rem; font-family: monospace;
            max-height: 220px; overflow-y: auto; white-space: pre-wrap;
        }
    </style>
</head>
<body>
<div class="container">

    <a href="/admin/" class="back-link">← Volver al Panel Admin</a>

    <header>
        <h1>📥 Importación Masiva de Negocios y Marcas</h1>
        <p>Subí un archivo JSON generado con IA para importar múltiples registros de una vez.
           Las coordenadas faltantes se geocodifican automáticamente vía OpenStreetMap.</p>
    </header>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <strong>❌ Errores detectados:</strong>
        <ul><?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($warnings)): ?>
    <div class="alert alert-warning">
        <strong>⚠️ Advertencias de geocodificación:</strong>
        <ul><?php foreach ($warnings as $w) echo '<li>' . htmlspecialchars($w) . '</li>'; ?></ul>
    </div>
    <?php endif; ?>

    <!-- ══════════════════════════════ PASO 1: FORMULARIO ══════════════════════════════ -->
    <?php if ($step === 'form' || !empty($errors)): ?>
    <div class="card">
        <h2>Paso 1 — Seleccionar archivo JSON</h2>

        <div class="alert alert-info">
            <strong>📄 Formato requerido:</strong> Array JSON en la raíz con objetos de marcas o negocios.
            Consultá <code>admin/bulk_import_templates.md</code> para el prompt de IA y ejemplos completos.
        </div>

        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="validate">

            <div class="form-group">
                <label for="type">Tipo de importación</label>
                <select name="type" id="type" required>
                    <option value="">— Seleccioná —</option>
                    <option value="brands"     <?php echo ($type === 'brands'     ? 'selected' : ''); ?>>🏷️ Marcas</option>
                    <option value="businesses" <?php echo ($type === 'businesses' ? 'selected' : ''); ?>>🏪 Negocios</option>
                </select>
            </div>

            <div class="form-group">
                <label for="file">Archivo JSON (máx. 2MB · UTF-8)</label>
                <input type="file" name="file" id="file" accept=".json" required>
            </div>

            <button type="submit" class="btn btn-primary">🔍 Validar y previsualizar</button>
        </form>
    </div>

    <!-- Guía rápida de reglas -->
    <div class="card">
        <h2>Reglas de validación</h2>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;font-size:.875rem;">
            <div>
                <strong>Marcas (campos obligatorios)</strong>
                <ul style="margin:8px 0;padding-left:18px;line-height:1.8;">
                    <li><code>nombre</code> — string, máx. 255</li>
                    <li><code>rubro</code> — string</li>
                    <li><code>inpi_registrada</code> — 0 o 1</li>
                    <li><code>es_franquicia</code> — 0 o 1</li>
                    <li><code>tiene_zona</code> — 0 o 1</li>
                    <li><code>tiene_licencia</code> — 0 o 1</li>
                    <li><code>estado</code> — "Activa" o "Inactiva"</li>
                </ul>
            </div>
            <div>
                <strong>Reglas especiales</strong>
                <ul style="margin:8px 0;padding-left:18px;line-height:1.8;">
                    <li>Fechas INPI: <code>YYYY-MM-DD</code></li>
                    <li>Duplicados: se consolidan clases NIZA</li>
                    <li>Ubicación: titular → Argentina declarado → "Argentina"</li>
                    <li>Lat/lng sin datos: geocodificación automática</li>
                    <li>Fallback coordenadas: Argentina (-34.60, -58.38)</li>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ══════════════════════════════ PASO 2: PREVIEW ══════════════════════════════ -->
    <?php if ($step === 'preview' && !empty($report)): ?>
    <div class="card">
        <h2>Paso 2 — Resultado de validación</h2>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="num"><?php echo $report['total']; ?></div>
                <div class="lbl">Total en archivo</div>
            </div>
            <div class="stat-box ok">
                <div class="num"><?php echo $report['valid']; ?></div>
                <div class="lbl">Registros válidos</div>
            </div>
            <div class="stat-box err">
                <div class="num"><?php echo $report['error_count']; ?></div>
                <div class="lbl">Con errores</div>
            </div>
            <div class="stat-box warn">
                <div class="num"><?php echo $report['duplicates']; ?></div>
                <div class="lbl">Duplicados consolidados</div>
            </div>
            <div class="stat-box ok">
                <div class="num"><?php echo $report['geo_ok']; ?></div>
                <div class="lbl">Geo OK</div>
            </div>
            <div class="stat-box warn">
                <div class="num"><?php echo $report['geo_fail'] + $report['geo_fallback']; ?></div>
                <div class="lbl">Geo fallback</div>
            </div>
        </div>

        <!-- Errores de filas -->
        <?php if (!empty($report['row_errors'])): ?>
        <div class="alert alert-error" style="margin-bottom:16px;">
            <strong>❌ Errores de validación por fila:</strong>
            <ul class="error-list">
                <?php foreach ($report['row_errors'] as $re): ?>
                <li><?php echo htmlspecialchars($re); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if ($report['valid'] === 0): ?>
        <div class="alert alert-error">
            No hay registros válidos para importar. Corregí el JSON y volvé a intentar.
        </div>
        <a href="/admin/importar_marcas_negocios.php" class="btn btn-secondary">↩ Volver al formulario</a>
        <?php else: ?>

        <div class="alert alert-success">
            ✅ <strong><?php echo $report['valid']; ?></strong> registro(s) válido(s) listo(s) para importar.
            <?php if ($report['duplicates'] > 0): ?>
            Se consolidaron <strong><?php echo $report['duplicates']; ?></strong> entrada(s) duplicada(s) (clases NIZA unificadas).
            <?php endif; ?>
        </div>

        <!-- Preview JSON de los primeros 3 válidos -->
        <details style="margin-bottom:16px;">
            <summary style="cursor:pointer;font-size:.875rem;font-weight:600;color:#1d4ed8;">
                Ver JSON enriquecido (primeros <?php echo min(3, count($validItems)); ?> registros)
            </summary>
            <div class="json-preview" style="margin-top:8px;">
                <?php echo htmlspecialchars(json_encode(array_slice($validItems, 0, 3), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?>
            </div>
        </details>

        <!-- Confirmar importación -->
        <form method="post">
            <input type="hidden" name="action" value="import">
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <button type="submit" class="btn btn-success">
                    ✅ Confirmar importación (<?php echo $report['valid']; ?> registros)
                </button>
                <a href="/admin/importar_marcas_negocios.php" class="btn btn-secondary">↩ Cancelar</a>
            </div>
        </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ══════════════════════════════ PASO 3: RESULTADO ══════════════════════════════ -->
    <?php if ($step === 'done'): ?>
    <div class="card">
        <h2>Paso 3 — Importación completada</h2>

        <div class="stats-grid">
            <div class="stat-box ok">
                <div class="num"><?php echo $report['imported'] ?? 0; ?></div>
                <div class="lbl">Importados</div>
            </div>
            <div class="stat-box err">
                <div class="num"><?php echo count($report['import_errors'] ?? []); ?></div>
                <div class="lbl">Errores al insertar</div>
            </div>
        </div>

        <?php if (!empty($report['import_errors'])): ?>
        <div class="alert alert-error">
            <strong>Errores al insertar:</strong>
            <ul>
                <?php foreach ($report['import_errors'] as $ie) echo '<li>' . htmlspecialchars($ie) . '</li>'; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="alert alert-success">
            🎉 Proceso finalizado. Podés ver los registros en el
            <a href="/admin/" style="color:#065f46;font-weight:600;">Panel Admin</a>.
        </div>

        <div style="display:flex;gap:12px;">
            <a href="/admin/importar_marcas_negocios.php" class="btn btn-primary">📥 Nueva importación</a>
            <a href="/admin/" class="btn btn-secondary">← Ir al Panel Admin</a>
        </div>
    </div>
    <?php endif; ?>

</div>
</body>
</html>
