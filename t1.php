<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== ERROR CHECK ===<br><br>";

echo "Testing plain text output...<br>";
echo "If you see this, PHP works.<br>";

$file = __DIR__ . '/views/business/simple.php';
echo "<br>simple.php exists: " . (file_exists($file) ? 'YES' : 'NO');

echo "<br><br>=== END ===";