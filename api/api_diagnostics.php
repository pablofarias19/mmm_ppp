<?php
/**
 * API Diagnóstico - Verificar estado de base de datos y APIs
 * GET /api/api_diagnostics.php
 *
 * Retorna información sobre:
 * - Conexión a BD
 * - Tablas existentes
 * - Problemas detectados
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';

$diagnostics = [
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'database' => [],
    'tables' => [],
    'errors' => [],
    'warnings' => [],
    'suggestions' => []
];

try {
    // 1. Verificar conexión a BD
    $db = \Core\Database::getInstance()->getConnection();
    $diagnostics['database']['connected'] = true;
    $diagnostics['database']['type'] = 'PDO MySQL';

    // 2. Obtener nombre de la BD
    try {
        $result = $db->query("SELECT DATABASE() as db_name");
        $row = $result->fetch(PDO::FETCH_ASSOC);
        $diagnostics['database']['name'] = $row['db_name'];
    } catch (Exception $e) {
        $diagnostics['errors'][] = "No se pudo obtener nombre de BD: " . $e->getMessage();
    }

    // 3. Verificar tablas requeridas
    $required_tables = [
        'business_icons',
        'noticias',
        'trivias',
        'trivia_scores',
        'brand_gallery',
        'attachments',
        'encuestas',
        'encuesta_questions',
        'encuesta_responses',
        'eventos'
    ];

    foreach ($required_tables as $table) {
        try {
            $result = $db->query("SELECT COUNT(*) as count FROM $table LIMIT 1");
            $row = $result->fetch(PDO::FETCH_ASSOC);
            $diagnostics['tables'][$table] = [
                'exists' => true,
                'rows' => $row['count'],
                'status' => '✅'
            ];
        } catch (PDOException $e) {
            $diagnostics['tables'][$table] = [
                'exists' => false,
                'rows' => 0,
                'status' => '❌',
                'error' => 'Tabla no existe o error de acceso'
            ];
            $diagnostics['errors'][] = "Tabla $table no existe o no es accesible";
            $diagnostics['suggestions'][] = "Ejecuta config/migration.sql en phpMyAdmin";
        }
    }

    // 4. Verificar estructura de business_icons
    if ($diagnostics['tables']['business_icons']['exists'] ?? false) {
        try {
            $result = $db->query("DESCRIBE business_icons");
            $columns = [];
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $columns[] = $row['Field'];
            }

            $required_columns = ['id', 'business_type', 'emoji', 'color', 'icon_class'];
            $missing = array_diff($required_columns, $columns);

            if (!empty($missing)) {
                $diagnostics['errors'][] = "Columnas faltantes en business_icons: " . implode(', ', $missing);
                $diagnostics['suggestions'][] = "Ejecuta migration.sql nuevamente (drop + recreate)";
            } else {
                $diagnostics['tables']['business_icons']['columns'] = $columns;
                $diagnostics['tables']['business_icons']['structure_ok'] = true;
            }
        } catch (Exception $e) {
            $diagnostics['errors'][] = "Error verificando estructura: " . $e->getMessage();
        }
    }

    // 5. Verificar archivos de API
    $api_files = [
        '/api/api_iconos.php',
        '/api/noticias.php',
        '/api/trivias.php',
        '/api/brand-gallery.php',
        '/api/encuestas.php',
        '/api/eventos.php'
    ];

    $diagnostics['files'] = [];
    foreach ($api_files as $file) {
        $path = __DIR__ . str_replace('/api', '', $file);
        $diagnostics['files'][$file] = [
            'exists' => file_exists($path),
            'readable' => is_readable($path),
            'size' => file_exists($path) ? filesize($path) : 0
        ];

        if (!file_exists($path)) {
            $diagnostics['errors'][] = "Archivo API faltante: $file";
        }
    }

    // 6. Verificar archivos CSS
    $css_files = [
        '/css/popup-redesign.css',
        '/css/brand-popup-premium.css'
    ];

    $diagnostics['css'] = [];
    foreach ($css_files as $file) {
        $path = __DIR__ . '/..' . $file;
        $diagnostics['css'][$file] = [
            'exists' => file_exists($path),
            'size' => file_exists($path) ? filesize($path) : 0
        ];
    }

    // 7. Resumir diagnóstico
    $error_count = count($diagnostics['errors']);
    $table_missing = array_filter($diagnostics['tables'], fn($t) => !($t['exists'] ?? false));

    if (empty($diagnostics['errors'])) {
        $diagnostics['status'] = 'OK - Todo funciona correctamente';
        $diagnostics['ready_for_production'] = true;
    } elseif (count($table_missing) > 0) {
        $diagnostics['status'] = 'CRITICAL - Falta ejecutar migración SQL';
        $diagnostics['ready_for_production'] = false;
        $diagnostics['action_required'] = 'Ejecutar config/migration.sql en phpMyAdmin';
    } else {
        $diagnostics['status'] = 'WARNING - Hay algunos problemas';
        $diagnostics['ready_for_production'] = false;
    }

    // Retornar resultado
    echo json_encode($diagnostics, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $error_response = [
        'status' => 'ERROR',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];

    http_response_code(500);
    echo json_encode($error_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
?>
