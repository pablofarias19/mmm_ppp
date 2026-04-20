<?php
echo "=== TEST MAP ===";
echo "<br>Fecha: " . date('Y-m-d H:i:s');
echo "<br>Archivo: " . __FILE__;

$file = __DIR__ . '/views/business/map.php';
$stat = stat($file);
echo "<br> map.php modified: " . date('Y-m-d H:i:s', $stat['mtime']);
echo "<br>Size: " . $stat['size'] . " bytes";