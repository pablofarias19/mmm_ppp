<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
ob_start();

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../models/Brand.php';

ob_end_clean();
header('Content-Type: application/json');

try {
    $db     = \Core\Database::getInstance()->getConnection();
    $marcas = \Brand::allWithCoordinates($db);

    $uploadsBase = __DIR__ . '/../uploads/brands/';
    foreach ($marcas as &$m) {
        $id      = (int)$m['id'];
        $dir     = $uploadsBase . $id . '/';
        $logoUrl = null;
        foreach (['png','jpg','jpeg','webp'] as $ext) {
            $f = $dir . 'logo.' . $ext;
            if (file_exists($f)) {
                $logoUrl = '/uploads/brands/' . $id . '/logo.' . $ext . '?t=' . filemtime($f);
                break;
            }
        }
        $m['logo_url'] = $logoUrl;
    }
    unset($m);

    respond_success($marcas, "Marcas obtenidas correctamente.");
} catch (Throwable $e) {
    error_log('brands.php fatal: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    respond_error("Error: " . $e->getMessage() . " [" . basename($e->getFile()) . ":" . $e->getLine() . "]");
}
