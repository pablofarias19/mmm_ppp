
<?php
/**
 * API de Negocios con Fotos
 * GET /api/api_comercios.php → Devuelve todos los negocios con fotos
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../models/Business.php';

use App\Models\Business;

header('Content-Type: application/json');

try {
    // Obtener negocios con fotos incluidas
    $negocios = Business::getAllWithPhotos($onlyVisible = true);

    // Optimizar para API (remover campos innecesarios en algunos casos)
    foreach ($negocios as &$negocio) {
        // Asegurar que photos sea un array
        if (!isset($negocio['photos'])) {
            $negocio['photos'] = [];
        }

        // Agregar bandera si tiene foto
        $negocio['has_photo'] = !empty($negocio['primary_photo']);
    }

    respond_success($negocios, "Negocios obtenidos correctamente.");
} catch (Exception $e) {
    error_log("Error en API de comercios: " . $e->getMessage());
    respond_error("Error al obtener negocios: " . $e->getMessage());
}