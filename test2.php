<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TEST ===";
echo "<br>PHP: " . phpversion();

echo "<br><br>Testing map.php...";
$map = __DIR__ . '/views/business/map.php';
if (file_exists($map)) {
    echo "<br>EXISTS - " . filesize($map) . " bytes";
} else {
    echo "<br>NOT FOUND";
}

echo "<br><br>Testing map_new.php...";
$map2 = __DIR__ . '/views/business/map_new.php';
if (file_exists($map2)) {
    echo "<br>EXISTS - " . filesize($map2) . " bytes";
} else {
    echo "<br>NOT FOUND";
}

echo "<br><br>=== END ===";