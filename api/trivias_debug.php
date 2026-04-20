<?php
/**
 * DEBUG trivias - Con errores visibles
 */

// ACTIVAR ERRORES PARA DEBUGGING
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

$log = [];

try {
    $log[] = 'Step 1: Script iniciado';

    // Session
    session_start();
    $log[] = 'Step 2: Session iniciada';

    // Require Database
    require_once __DIR__ . '/../core/Database.php';
    $log[] = 'Step 3: Database.php cargado';

    // Conectar
    try {
        $db = \Core\Database::getInstance()->getConnection();
        $log[] = 'Step 4: BD conectada';

        // Query simple
        $stmt = $db->query("SELECT 1 as test");
        $result = $stmt->fetch();
        $log[] = 'Step 5: Query ejecutada';
        $log[] = 'Query result: ' . json_encode($result);

        // Verificar tabla trivias
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM trivias");
            $count = $stmt->fetchColumn();
            $log[] = 'Step 6: Tabla trivias existe, count: ' . $count;
        } catch (PDOException $e) {
            $log[] = 'Step 6: Tabla trivias NO existe: ' . $e->getMessage();
        }

    } catch (Throwable $e) {
        $log[] = 'ERROR en BD: ' . $e->getMessage();
    }

    echo json_encode([
        'success' => true,
        'log' => $log
    ], JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'log' => $log
    ], JSON_PRETTY_PRINT);
}
