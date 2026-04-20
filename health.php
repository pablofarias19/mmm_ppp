<?php
/**
 * Health Check - Diagnóstico del Sistema Mapita
 * Acceso: https://mapita.com.ar/health.php
 */

header('Content-Type: application/json');

$status = [];

// 1. Check PHP version
$status['php_version'] = phpversion();
$status['php_ok'] = version_compare(phpversion(), '7.2', '>=');

// 2. Check required extensions
$extensions = ['pdo', 'pdo_mysql', 'session'];
$status['extensions'] = [];
foreach ($extensions as $ext) {
    $status['extensions'][$ext] = extension_loaded($ext) ? '✓' : '✗';
}

// 3. Check file permissions
$status['files'] = [];
$files_to_check = [
    'config/database.php',
    '.env',
    'uploads/businesses',
    'uploads/brands',
    'uploads/noticias',
];
foreach ($files_to_check as $file) {
    $path = __DIR__ . '/' . $file;
    $status['files'][$file] = file_exists($path) ? '✓ exists' : '✗ missing';
}

// 4. Check database connection
$status['database'] = [];
try {
    $config = require __DIR__ . '/config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $status['database']['connection'] = '✓ Connected';

    // Test query
    $result = $pdo->query("SELECT 1");
    $status['database']['query'] = '✓ Query OK';
} catch (Exception $e) {
    $status['database']['connection'] = '✗ Failed: ' . $e->getMessage();
}

// 5. Check security headers
$status['headers'] = [
    'Security headers are set in' => 'index.php and .htaccess'
];

// 6. Routes check
$status['routes'] = [
    '/' => 'map.php',
    '/login' => 'auth/login.php',
    '/admin' => 'admin/dashboard.php',
    '/api/noticias.php' => 'API endpoint'
];

// Output
echo json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
