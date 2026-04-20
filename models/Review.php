<?php
namespace App\Models;

use Core\Database;
use Exception;
use PDO;

/**
 * Modelo para reseñas y valoraciones de negocios.
 *
 * Requiere la siguiente tabla en la base de datos:
 *
 * CREATE TABLE IF NOT EXISTS reviews (
 *     id           INT          PRIMARY KEY AUTO_INCREMENT,
 *     business_id  INT          NOT NULL,
 *     user_id      INT          NOT NULL,
 *     rating       TINYINT      NOT NULL CHECK (rating BETWEEN 1 AND 5),
 *     comment      TEXT,
 *     created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
 *     FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
 *     FOREIGN KEY (user_id)     REFERENCES users(id)      ON DELETE CASCADE,
 *     UNIQUE KEY unique_review  (business_id, user_id)
 * );
 */
class Review {

    /**
     * Agrega o actualiza una reseña de un usuario para un negocio.
     */
    public static function upsert(int $businessId, int $userId, int $rating, string $comment = ''): array {
        try {
            if ($rating < 1 || $rating > 5) {
                return ['success' => false, 'message' => 'La valoración debe estar entre 1 y 5.'];
            }
            $comment = mb_substr(trim($comment), 0, 1000);

            $db = Database::getInstance()->getConnection();
            $db->prepare("
                INSERT INTO reviews (business_id, user_id, rating, comment, created_at)
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), created_at = NOW()
            ")->execute([$businessId, $userId, $rating, $comment]);

            return ['success' => true, 'message' => 'Reseña guardada.'];

        } catch (Exception $e) {
            error_log("Error en Review::upsert: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al guardar la reseña.'];
        }
    }

    /**
     * Obtiene todas las reseñas de un negocio.
     */
    public static function getByBusiness(int $businessId): array {
        try {
            $db   = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                SELECT r.id, r.user_id, r.rating, r.comment, r.created_at, u.username
                FROM reviews r
                INNER JOIN users u ON r.user_id = u.id
                WHERE r.business_id = ?
                ORDER BY r.created_at DESC
            ");
            $stmt->execute([$businessId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en Review::getByBusiness: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene la valoración promedio de un negocio.
     * @return array|null ['avg' => float, 'total' => int] o null si hay error.
     */
    public static function getAverage(int $businessId): ?array {
        try {
            $db   = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM reviews WHERE business_id = ?");
            $stmt->execute([$businessId]);
            $row  = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? ['avg' => round((float)$row['avg_rating'], 1), 'total' => (int)$row['total']] : null;
        } catch (Exception $e) {
            error_log("Error en Review::getAverage: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Elimina la reseña propia de un usuario para un negocio.
     */
    public static function delete(int $businessId, int $userId): array {
        try {
            $db = Database::getInstance()->getConnection();
            $db->prepare("DELETE FROM reviews WHERE business_id = ? AND user_id = ?")
               ->execute([$businessId, $userId]);
            return ['success' => true, 'message' => 'Reseña eliminada.'];
        } catch (Exception $e) {
            error_log("Error en Review::delete: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al eliminar la reseña.'];
        }
    }

    /**
     * Elimina una reseña por ID.
     * Permitido si: el userId es el autor, el userId es dueño del negocio, o isAdmin=true.
     */
    public static function deleteById(int $reviewId, int $userId, bool $isAdmin = false): array {
        try {
            $db = Database::getInstance()->getConnection();

            // Fetch the review to check ownership
            $stmt = $db->prepare("SELECT r.id, r.user_id, b.user_id AS owner_id FROM reviews r JOIN businesses b ON b.id = r.business_id WHERE r.id = ?");
            $stmt->execute([$reviewId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$row) {
                return ['success' => false, 'message' => 'Reseña no encontrada.'];
            }

            $isAuthor = (int)$row['user_id'] === $userId;
            $isOwner  = (int)$row['owner_id'] === $userId;

            if (!$isAdmin && !$isAuthor && !$isOwner) {
                return ['success' => false, 'message' => 'Sin permiso para eliminar esta reseña.'];
            }

            $db->prepare("DELETE FROM reviews WHERE id = ?")->execute([$reviewId]);
            return ['success' => true, 'message' => 'Reseña eliminada.'];
        } catch (Exception $e) {
            error_log("Error en Review::deleteById: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al eliminar la reseña.'];
        }
    }

    /**
     * Actualiza el comentario y/o rating de una reseña.
     * Solo el autor o un admin pueden editar.
     */
    public static function updateById(int $reviewId, int $userId, bool $isAdmin, int $rating, string $comment): array {
        try {
            if ($rating < 1 || $rating > 5) {
                return ['success' => false, 'message' => 'La valoración debe estar entre 1 y 5.'];
            }
            $comment = mb_substr(trim($comment), 0, 1000);
            $db = Database::getInstance()->getConnection();

            $stmt = $db->prepare("SELECT user_id FROM reviews WHERE id = ?");
            $stmt->execute([$reviewId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$row) {
                return ['success' => false, 'message' => 'Reseña no encontrada.'];
            }
            if (!$isAdmin && (int)$row['user_id'] !== $userId) {
                return ['success' => false, 'message' => 'Sin permiso para editar esta reseña.'];
            }

            $db->prepare("UPDATE reviews SET rating = ?, comment = ?, created_at = NOW() WHERE id = ?")
               ->execute([$rating, $comment, $reviewId]);
            return ['success' => true, 'message' => 'Reseña actualizada.'];
        } catch (Exception $e) {
            error_log("Error en Review::updateById: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al actualizar la reseña.'];
        }
    }
}
