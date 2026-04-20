<?php
/**
 * TEST DB - Probar conexión a base de datos
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

$report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => []
];

// Test 1: session_start
try {
    session_start();
    $report['tests']['session'] = 'OK';
} catch (Throwable $e) {
    $report['tests']['session'] = 'ERROR: ' . $e->getMessage();
}

// Test 2: Database.php existe
$dbFile = __DIR__ . '/../core/Database.php';
$report['tests']['database_file_exists'] = file_exists($dbFile) ? 'OK' : 'MISSING: ' . $dbFile;

// Test 3: config/database.php existe
$configFile = __DIR__ . '/../config/database.php';
$report['tests']['config_file_exists'] = file_exists($configFile) ? 'OK' : 'MISSING: ' . $configFile;

// Test 4: Cargar Database.php
try {
    if (file_exists($dbFile)) {
        require_once $dbFile;
        $report['tests']['database_class_loaded'] = class_exists('\Core\Database') ? 'OK' : 'CLASS NOT FOUND';
    }
} catch (Throwable $e) {
    $report['tests']['database_class_loaded'] = 'ERROR: ' . $e->getMessage();
}

// Test 5: Conectar a BD
try {
    $db = \Core\Database::getInstance()->getConnection();
    $report['tests']['db_connection'] = 'OK';

    // Test 6: Listar tablas
    try {
        $stmt = $db->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $report['tests']['tables'] = $tables;
    } catch (Throwable $e) {
        $report['tests']['tables'] = 'ERROR: ' . $e->getMessage();
    }
} catch (Throwable $e) {
    $report['tests']['db_connection'] = 'ERROR: ' . $e->getMessage();
}

echo json_encode($report, JSON_PRETTY_PRINT);
