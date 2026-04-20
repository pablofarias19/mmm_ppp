<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing individual files...<br><br>";

echo "1. database.php: ";
try {
    $c = require __DIR__ . '/config/database.php';
    echo "OK<br>";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
}

echo "2. Database.php: ";
try {
    require_once __DIR__ . '/core/Database.php';
    echo "OK<br>";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
}

echo "3. getInstance: ";
try {
    $db = \Core\Database::getInstance();
    echo "OK<br>";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
}

echo "<br>Done";