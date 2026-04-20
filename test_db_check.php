<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TEST ERROR ===<br><br>";

try {
    require_once __DIR__ . '/config/database.php';
    echo "1. Config loaded OK<br>";
} catch (Exception $e) {
    echo "1. Config ERROR: " . $e->getMessage() . "<br>";
}

try {
    require_once __DIR__ . '/core/Database.php';
    echo "2. Database loaded OK<br>";
} catch (Exception $e) {
    echo "2. Database ERROR: " . $e->getMessage() . "<br>";
}

echo "<br>=== END ===";