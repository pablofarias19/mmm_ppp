<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== ERROR LOG ===<br><br>";

error_clear_last();

echo "1. INCLUDES:<br>";

// Test includes
$files = [
    '/config/database.php',
    '/core/Database.php',
    '/core/helpers.php',
    '/models/Brand.php'
];

foreach ($files as $f) {
    $path = __DIR__ . $f;
    if (file_exists($path)) {
        try {
            require_once $path;
            echo "✓ $f<br>";
        } catch (Exception $e) {
            echo "✗ $f: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "✗ $f: NOT FOUND<br>";
    }
}

echo "<br>2. DATABASE:<br>";
try {
    $db = \Core\Database::getInstance()->getConnection();
    echo "✓ Connection OK<br>";
    
    $s = $db->query("SELECT COUNT(*) as c FROM businesses");
    $r = $s->fetch(PDO::FETCH_ASSOC);
    echo "Negocios: " . $r['c'] . "<br>";
    
    $s = $db->query("SELECT COUNT(*) as c FROM marcas");
    $r = $s->fetch(PDO::FETCH_ASSOC);
    echo "Marcas: " . $r['c'] . "<br>";
} catch (Exception $e) {
    echo "✗ Database: " . $e->getMessage() . "<br>";
}

echo "<br>=== END ===";