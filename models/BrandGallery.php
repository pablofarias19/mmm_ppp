<?php
namespace App\Models;

use Core\Database;
use Exception;
use PDO;

class BrandGallery {
    protected $table = 'brand_gallery';
    public const MAX_IMAGES_PER_BRAND = 1;
    public const MAX_FILE_BYTES = 120 * 1024; // 120 KB

    /**
     * Obtiene todas las imágenes de una marca
     * @param int $brand_id
     * @return array
     */
    public static function getByBrand($brand_id) {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "SELECT * FROM brand_gallery
                    WHERE brand_id = ?
                    ORDER BY orden ASC, created_at DESC";

            $stmt = $db->prepare($sql);
            $stmt->execute([$brand_id]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en BrandGallery::getByBrand - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene imagen principal (logo) de una marca
     * @param int $brand_id
     * @return array|null
     */
    public static function getMainImage($brand_id) {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "SELECT * FROM brand_gallery
                    WHERE brand_id = ? AND es_principal = 1
                    LIMIT 1";

            $stmt = $db->prepare($sql);
            $stmt->execute([$brand_id]);

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en BrandGallery::getMainImage - " . $e->getMessage());
            return null;
        }
    }

    /**
     * Agrega una nueva imagen a la galería de marca
     * @param int $brand_id
     * @param string $filename
     * @param string $titulo
     * @param bool $es_principal
     * @return int|false ID insertado o false
     */
    public static function addImage($brand_id, $filename, $titulo = '', $es_principal = false) {
        try {
            $db = Database::getInstance()->getConnection();

            // Si es principal, desmarcar otras imágenes como principal
            if ($es_principal) {
                $sql_update = "UPDATE brand_gallery SET es_principal = 0 WHERE brand_id = ?";
                $stmt_update = $db->prepare($sql_update);
                $stmt_update->execute([$brand_id]);
            }

            $sql = "INSERT INTO brand_gallery
                    (brand_id, filename, titulo, es_principal, created_at)
                    VALUES (?, ?, ?, ?, NOW())";

            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                $brand_id,
                $filename,
                $titulo,
                $es_principal ? 1 : 0
            ]);

            return $result ? $db->lastInsertId() : false;
        } catch (Exception $e) {
            error_log("Error en BrandGallery::addImage - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina una imagen de la galería
     * @param int $id
     * @param int $brand_id
     * @return bool
     */
    public static function deleteImage($id, $brand_id) {
        try {
            $db = Database::getInstance()->getConnection();

            // Obtener filename para eliminar archivo
            $sql_get = "SELECT filename FROM brand_gallery WHERE id = ? AND brand_id = ?";
            $stmt_get = $db->prepare($sql_get);
            $stmt_get->execute([$id, $brand_id]);
            $image = $stmt_get->fetch(PDO::FETCH_ASSOC);

            if ($image) {
                // Eliminar archivo
                $filepath = __DIR__ . '/../uploads/brands/' . $image['filename'];
                if (file_exists($filepath)) {
                    unlink($filepath);
                }

                // Eliminar registro
                $sql_delete = "DELETE FROM brand_gallery WHERE id = ? AND brand_id = ?";
                $stmt_delete = $db->prepare($sql_delete);
                return $stmt_delete->execute([$id, $brand_id]);
            }

            return false;
        } catch (Exception $e) {
            error_log("Error en BrandGallery::deleteImage - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza información de una imagen
     * @param int $id
     * @param int $brand_id
     * @param array $data
     * @return bool
     */
    public static function updateImage($id, $brand_id, $data) {
        try {
            $db = Database::getInstance()->getConnection();

            $update = [];
            $params = [];

            if (isset($data['titulo'])) {
                $update[] = "titulo = ?";
                $params[] = $data['titulo'];
            }

            if (isset($data['es_principal']) && $data['es_principal']) {
                // Desmarcar otras imágenes como principal
                $sql_update = "UPDATE brand_gallery SET es_principal = 0 WHERE brand_id = ?";
                $stmt_update = $db->prepare($sql_update);
                $stmt_update->execute([$brand_id]);

                $update[] = "es_principal = 1";
            }

            if (empty($update)) return false;

            $params[] = $id;
            $params[] = $brand_id;

            $sql = "UPDATE brand_gallery SET " . implode(", ", $update) . " WHERE id = ? AND brand_id = ?";

            $stmt = $db->prepare($sql);
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("Error en BrandGallery::updateImage - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Procesa y guarda una imagen subida
     * @param int $brand_id
     * @param array $file $_FILES['imagen']
     * @param string $titulo
     * @param bool $es_principal
     * @return string|false Nombre del archivo o false
     */
    public static function uploadImage($brand_id, $file, $titulo = '', $es_principal = false) {
        try {
            if (!isset($file['tmp_name']) || !$file['tmp_name']) {
                return false;
            }

            $existing = self::getByBrand($brand_id);
            if (count($existing) >= self::MAX_IMAGES_PER_BRAND) {
                error_log("Se alcanzó el máximo de imágenes para la marca: " . $brand_id);
                return false;
            }

            // Validar tamaño (máximo 200KB)
            if (($file['size'] ?? 0) > self::MAX_FILE_BYTES) {
                error_log("Archivo demasiado grande: " . ($file['size'] ?? 0));
                return false;
            }

            // Validar tipo de archivo por MIME real
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if (!$finfo) {
                error_log("No se pudo inicializar finfo para validar MIME en BrandGallery::uploadImage");
                return false;
            }
            $realMime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            $allowed = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
            ];
            if (!$realMime || !isset($allowed[$realMime])) {
                error_log("Tipo de archivo no permitido: " . ($realMime ?: 'desconocido'));
                return false;
            }

            // Crear directorio si no existe
            $upload_dir = __DIR__ . '/../uploads/brands';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Generar nombre único
            $ext = $allowed[$realMime];
            $filename = 'brand_' . $brand_id . '_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $filepath = $upload_dir . '/' . $filename;

            // Mover archivo
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                error_log("Error moviendo archivo uploadado");
                return false;
            }

            // Optimizar imagen
            self::optimizeImage($filepath);

            // Guardar en BD
            $result = self::addImage($brand_id, $filename, $titulo, $es_principal);

            return $result ? $filename : false;
        } catch (Exception $e) {
            error_log("Error en BrandGallery::uploadImage - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Optimiza imagen (redimensiona si es muy grande)
     * @param string $filepath
     * @return void
     */
    private static function optimizeImage($filepath) {
        try {
            if (!extension_loaded('gd')) {
                return; // GD no disponible
            }

            $image_info = getimagesize($filepath);
            if (!$image_info) return;

            $width = $image_info[0];
            $height = $image_info[1];
            $type = $image_info[2];

            // Si la imagen es muy grande, redimensionar
            if ($width > 2000 || $height > 2000) {
                $max_width = 2000;
                $max_height = 2000;

                if ($width > $height) {
                    $new_width = $max_width;
                    $new_height = intval($height * ($max_width / $width));
                } else {
                    $new_height = $max_height;
                    $new_width = intval($width * ($max_height / $height));
                }

                // Crear imagen redimensionada
                $src = null;
                switch ($type) {
                    case IMAGETYPE_JPEG:
                        $src = imagecreatefromjpeg($filepath);
                        break;
                    case IMAGETYPE_PNG:
                        $src = imagecreatefrompng($filepath);
                        break;
                    case IMAGETYPE_WEBP:
                        $src = imagecreatefromwebp($filepath);
                        break;
                    case IMAGETYPE_GIF:
                        $src = imagecreatefromgif($filepath);
                        break;
                }

                if ($src) {
                    $dest = imagecreatetruecolor($new_width, $new_height);
                    imagecopyresampled($dest, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

                    switch ($type) {
                        case IMAGETYPE_JPEG:
                            imagejpeg($dest, $filepath, 85);
                            break;
                        case IMAGETYPE_PNG:
                            imagepng($dest, $filepath, 8);
                            break;
                        case IMAGETYPE_WEBP:
                            imagewebp($dest, $filepath, 80);
                            break;
                    }

                    imagedestroy($src);
                    imagedestroy($dest);
                }
            }
        } catch (Exception $e) {
            error_log("Error optimizando imagen: " . $e->getMessage());
        }
    }
}
