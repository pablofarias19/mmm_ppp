<?php
/**
 * TEST JSON - Archivo de prueba ULTRA SIMPLE
 * Sin dependencias, sin BD, sin nada
 * Solo para verificar que el servidor puede devolver JSON
 */

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'success' => true,
    'message' => 'Si ves esto, el servidor PHP funciona correctamente',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => phpversion(),
    'server' => $_SERVER['SERVER_NAME'] ?? 'unknown'
]);
