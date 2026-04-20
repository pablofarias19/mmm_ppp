<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$uri = $_SERVER['REQUEST_URI'] ?? '/';

// Simple test route
if ($uri === '/test') {
    echo "TEST OK";
    exit;
}

// Try to load helpers
echo "Loading index.php...<br>";
echo "URI: $uri<br>";

try {
    require_once __DIR__ . '/core/helpers.php';
    echo "helpers.php loaded<br>";
} catch (Throwable $e) {
    echo "helpers.php ERROR: " . $e->getMessage() . "<br>";
}

try {
    require_once __DIR__ . '/core/Database.php';
    echo "Database.php loaded<br>";
} catch (Throwable $e) {
    echo "Database.php ERROR: " . $e->getMessage() . "<br>";
}

echo "Done";