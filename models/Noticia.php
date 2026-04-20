<?php
namespace App\Models;

use Core\Database;
use Exception;
use PDO;

class Noticia {
    protected $table = 'noticias';

    /**
     * Obtiene todas las noticias activas
     * @return array
     */
    public static function getAllActive() {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT * FROM noticias
                    WHERE activa = 1
                    ORDER BY fecha_publicacion DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en Noticia::getAllActive - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene todas las noticias (activas e inactivas)
     * @return array
     */
    public static function getAll() {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT * FROM noticias ORDER BY fecha_publicacion DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en Noticia::getAll - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene una noticia por ID
     * @param int $id
     * @return array|null
     */
    public static function getById($id) {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT * FROM noticias WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en Noticia::getById - " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene noticias por categoría
     * @param string $categoria
     * @return array
     */
    public static function getByCategoria($categoria) {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT * FROM noticias
                    WHERE categoria = ? AND activa = 1
                    ORDER BY fecha_publicacion DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute([$categoria]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en Noticia::getByCategoria - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene noticias recientes (últimas N)
     * @param int $limit
     * @return array
     */
    public static function getRecent($limit = 10) {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT * FROM noticias
                    WHERE activa = 1
                    ORDER BY fecha_publicacion DESC
                    LIMIT ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en Noticia::getRecent - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene noticias por usuario/autor
     * @param int $user_id
     * @return array
     */
    public static function getByAutor($user_id) {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT * FROM noticias
                    WHERE user_id = ?
                    ORDER BY fecha_publicacion DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en Noticia::getByAutor - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene estadísticas de noticias
     * @return array
     */
    public static function getStats() {
        try {
            $db = Database::getInstance()->getConnection();

            $stats = [];

            // Total de noticias activas
            $stmt = $db->query("SELECT COUNT(*) as count FROM noticias WHERE activa = 1");
            $stats['total_activas'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Total de noticias inactivas
            $stmt = $db->query("SELECT COUNT(*) as count FROM noticias WHERE activa = 0");
            $stats['total_inactivas'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Noticias por categoría
            $stmt = $db->query("SELECT categoria, COUNT(*) as count FROM noticias WHERE activa = 1 GROUP BY categoria");
            $stats['por_categoria'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Total de vistas
            $stmt = $db->query("SELECT SUM(vistas) as total FROM noticias");
            $stats['total_vistas'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

            return $stats;
        } catch (Exception $e) {
            error_log("Error en Noticia::getStats - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Crea una nueva noticia
     * @param array $data
     * @return int|false ID insertado o false
     */
    public static function create($data) {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "INSERT INTO noticias
                    (titulo, contenido, categoria, imagen, user_id, activa, fecha_publicacion, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                $data['titulo'] ?? '',
                $data['contenido'] ?? '',
                $data['categoria'] ?? 'General',
                $data['imagen'] ?? null,
                $data['user_id'] ?? null,
                $data['activa'] ?? 1,
                $data['fecha_publicacion'] ?? date('Y-m-d H:i:s')
            ]);

            return $result ? $db->lastInsertId() : false;
        } catch (Exception $e) {
            error_log("Error en Noticia::create - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza una noticia
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update($id, $data) {
        try {
            $db = Database::getInstance()->getConnection();

            $update = [];
            $params = [];

            if (isset($data['titulo'])) {
                $update[] = "titulo = ?";
                $params[] = $data['titulo'];
            }

            if (isset($data['contenido'])) {
                $update[] = "contenido = ?";
                $params[] = $data['contenido'];
            }

            if (isset($data['categoria'])) {
                $update[] = "categoria = ?";
                $params[] = $data['categoria'];
            }

            if (isset($data['imagen'])) {
                $update[] = "imagen = ?";
                $params[] = $data['imagen'];
            }

            if (isset($data['activa'])) {
                $update[] = "activa = ?";
                $params[] = $data['activa'] ? 1 : 0;
            }

            if (isset($data['fecha_publicacion'])) {
                $update[] = "fecha_publicacion = ?";
                $params[] = $data['fecha_publicacion'];
            }

            if (empty($update)) return false;

            $update[] = "updated_at = NOW()";
            $params[] = $id;

            $sql = "UPDATE noticias SET " . implode(", ", $update) . " WHERE id = ?";

            $stmt = $db->prepare($sql);
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("Error en Noticia::update - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Desactiva una noticia (soft delete)
     * @param int $id
     * @return bool
     */
    public static function deactivate($id) {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "UPDATE noticias SET activa = 0, updated_at = NOW() WHERE id = ?";
            $stmt = $db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Error en Noticia::deactivate - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Activa una noticia
     * @param int $id
     * @return bool
     */
    public static function activate($id) {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "UPDATE noticias SET activa = 1, updated_at = NOW() WHERE id = ?";
            $stmt = $db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Error en Noticia::activate - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Incrementa el contador de vistas
     * @param int $id
     * @return bool
     */
    public static function incrementVistas($id) {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "UPDATE noticias SET vistas = vistas + 1 WHERE id = ?";
            $stmt = $db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Error en Noticia::incrementVistas - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina una noticia permanentemente
     * @param int $id
     * @return bool
     */
    public static function delete($id) {
        try {
            $db = Database::getInstance()->getConnection();

            // Obtener imagen para eliminarla
            $sql_get = "SELECT imagen FROM noticias WHERE id = ?";
            $stmt_get = $db->prepare($sql_get);
            $stmt_get->execute([$id]);
            $noticia = $stmt_get->fetch(PDO::FETCH_ASSOC);

            if ($noticia && $noticia['imagen']) {
                $filepath = __DIR__ . '/../uploads/noticias/' . $noticia['imagen'];
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
            }

            // Eliminar registro
            $sql_delete = "DELETE FROM noticias WHERE id = ?";
            $stmt_delete = $db->prepare($sql_delete);
            return $stmt_delete->execute([$id]);
        } catch (Exception $e) {
            error_log("Error en Noticia::delete - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Procesa y guarda una imagen de noticia
     * @param array $file $_FILES['imagen']
     * @return string|false Nombre del archivo o false
     */
    public static function uploadImage($file) {
        try {
            if (!isset($file['tmp_name']) || !$file['tmp_name']) {
                return false;
            }

            // Validar tipo de archivo
            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (!in_array($file['type'], $allowed)) {
                error_log("Tipo de archivo no permitido: " . $file['type']);
                return false;
            }

            // Validar tamaño (máximo 5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                error_log("Archivo demasiado grande: " . $file['size']);
                return false;
            }

            // Crear directorio si no existe
            $upload_dir = __DIR__ . '/../uploads/noticias';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Generar nombre único
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'noticia_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $filepath = $upload_dir . '/' . $filename;

            // Mover archivo
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                error_log("Error moviendo archivo uploadado");
                return false;
            }

            return $filename;
        } catch (Exception $e) {
            error_log("Error en Noticia::uploadImage - " . $e->getMessage());
            return false;
        }
    }
}
