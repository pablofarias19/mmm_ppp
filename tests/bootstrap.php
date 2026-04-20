<?php
/**
 * Bootstrap para PHPUnit
 */

// Simular entorno sin servidor web
$_SERVER['HTTP_HOST']      = 'localhost';
$_SERVER['REQUEST_URI']    = '/tests/';
$_SERVER['REQUEST_METHOD'] = 'GET';

define('TESTING', true);

// Iniciar sesión para que los tests puedan usar $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION = [];

// Cargar autoloader de Composer si existe
$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

// Cargar archivos del núcleo manualmente si no hay vendor
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/Business.php';
require_once __DIR__ . '/../includes/db_helper.php';
