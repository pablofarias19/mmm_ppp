<?php
/**
 * API Galería de Marcas
 * GET    /api/brand-gallery.php?brand_id=X        - Obtener imágenes de marca
 * GET    /api/brand-gallery.php?brand_id=X&main=1 - Obtener imagen principal
 * POST   /api/brand-gallery.php?action=upload      - Subir imagen
 * POST   /api/brand-gallery.php?action=delete      - Eliminar imagen
 * POST   /api/brand-gallery.php?action=set-main    - Marcar como principal
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../models/BrandGallery.php';

use App\Models\BrandGallery;

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? 'list';
    $brand_id = (int)($_GET['brand_id'] ?? 0);

    // ============ GET ACTIONS ============
    if ($method === 'GET') {
        if ($brand_id <= 0) {
            respond_error("ID de marca inválido", 400);
        }

        // GET imagen principal
        if (isset($_GET['main'])) {
            $image = BrandGallery::getMainImage($brand_id);
            $data = ['image' => $image];
            respond_success($data, "Imagen principal obtenida");
            exit;
        }

        // GET todas las imágenes
        $images = BrandGallery::getByBrand($brand_id);
        respond_success($images, "Imágenes obtenidas");
        exit;
    }

    // ============ POST ACTIONS ============
    if ($method === 'POST') {

        // POST subir imagen
        if ($action === 'upload') {
            if (!isset($_SESSION['user_id']) || !$_SESSION['user_id']) {
                respond_error("Usuario no logueado", 401);
            }

            $brand_id = (int)($_POST['brand_id'] ?? 0);
            if ($brand_id <= 0) {
                respond_error("ID de marca inválido", 400);
            }

            // Verificar que el usuario es propietario de la marca
            // (opcional: agregar verificación de propiedad)

            if (!isset($_FILES['imagen'])) {
                respond_error("Imagen no proporcionada", 400);
            }

            $existing = BrandGallery::getByBrand($brand_id);
            if (count($existing) >= BrandGallery::MAX_IMAGES_PER_BRAND) {
                respond_error("Ya alcanzaste el máximo de " . BrandGallery::MAX_IMAGES_PER_BRAND . " imágenes.", 400);
            }

            $file = $_FILES['imagen'];
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                respond_error("Error al subir la imagen.", 400);
            }

            if (($file['size'] ?? 0) > BrandGallery::MAX_FILE_BYTES) {
                respond_error("La imagen no puede superar 120 KB. Comprimila antes de subir.", 400);
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedMime = $finfo ? finfo_file($finfo, $file['tmp_name']) : null;
            if ($finfo) {
                finfo_close($finfo);
            }
            $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
            if (!$detectedMime || !in_array($detectedMime, $allowedMime, true)) {
                respond_error("Formato no permitido. Usá JPG, PNG o WebP.", 400);
            }

            // Normalizar type en una copia local para que el modelo use el MIME validado por servidor.
            $validatedFile = array_merge((array)$file, ['type' => $detectedMime]);

            $titulo = $_POST['titulo'] ?? '';
            $es_principal = (bool)($_POST['es_principal'] ?? false);

            $filename = BrandGallery::uploadImage($brand_id, $validatedFile, $titulo, $es_principal);

            if (!$filename) {
                respond_error("Error al subir la imagen", 500);
            }

            respond_success(
                ['filename' => $filename, 'url' => '/uploads/brands/' . $filename],
                "Imagen subida correctamente"
            );
            exit;
        }

        // POST eliminar imagen
        if ($action === 'delete') {
            if (!isset($_SESSION['user_id']) || !$_SESSION['user_id']) {
                respond_error("Usuario no logueado", 401);
            }

            $image_id = (int)($_POST['image_id'] ?? 0);
            $brand_id = (int)($_POST['brand_id'] ?? 0);

            if ($image_id <= 0 || $brand_id <= 0) {
                respond_error("IDs inválidos", 400);
            }

            $result = BrandGallery::deleteImage($image_id, $brand_id);

            if (!$result) {
                respond_error("Error al eliminar la imagen", 500);
            }

            respond_success([], "Imagen eliminada correctamente");
            exit;
        }

        // POST marcar como principal
        if ($action === 'set-main') {
            if (!isset($_SESSION['user_id']) || !$_SESSION['user_id']) {
                respond_error("Usuario no logueado", 401);
            }

            $image_id = (int)($_POST['image_id'] ?? 0);
            $brand_id = (int)($_POST['brand_id'] ?? 0);

            if ($image_id <= 0 || $brand_id <= 0) {
                respond_error("IDs inválidos", 400);
            }

            $result = BrandGallery::updateImage($image_id, $brand_id, ['es_principal' => true]);

            if (!$result) {
                respond_error("Error al actualizar la imagen principal", 500);
            }

            respond_success([], "Imagen marcada como principal");
            exit;
        }
    }

    respond_error("Acción no válida o método no permitido", 405);

} catch (Exception $e) {
    error_log("Error en API brand-gallery: " . $e->getMessage());
    respond_error("Error del servidor: " . $e->getMessage(), 500);
}
