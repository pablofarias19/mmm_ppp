<?php
echo "=== TEST SERVER ===";
echo "<br>Time: " . date('Y-m-d H:i:s');
echo "<br>PHP: " . phpversion();

$test = __DIR__ . '/views/business/map_new.php';
echo "<br>map_new.php exists: " . (file_exists($test) ? 'YES' : 'NO');
echo "<br>size: " . filesize($test) . " bytes";