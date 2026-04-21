<?php
namespace App\Models;

use Core\Database;
use Exception;
use PDO;

/**
 * Modelo para reportes de contenido.
 *
 * Requiere la tabla content_reports (migrations/010_moderation.sql).
 */
class Report {

    const VALID_TYPES   = ['review','business','noticia','evento','oferta','trivia','encuesta','transmision'];
    const VALID_REASONS = ['spam','inappropriate','fake','harassment','other'];
    const VALID_STATUS  = ['pending','reviewing','resolved','dismissed'];

    /**
     * Crea un nuevo reporte de contenido.
     */
    public static function create(
        ?int    $reporterUserId,
        string  $reporterIp,
        string  $contentType,
        int     $contentId,
        string  $reason,
        string  $description = ''
    ): array {
        try {
            if (!in_array($contentType, self::VALID_TYPES, true)) {
                return ['success' => false, 'message' => 'Tipo de contenido inválido.'];
            }
            if (!in_array($reason, self::VALID_REASONS, true)) {
                return ['success' => false, 'message' => 'Motivo de reporte inválido.'];
            }
            $description = mb_substr(trim($description), 0, 500);

            $db = Database::getInstance()->getConnection();

            // Prevenir reportes duplicados del mismo IP/usuario sobre el mismo contenido (últimas 24h)
            $dupSql = "SELECT id FROM content_reports
                       WHERE content_type = ? AND content_id = ? AND status IN ('pending','reviewing')
                         AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                         AND (reporter_ip = ?";
            $params = [$contentType, $contentId, $reporterIp];
            if ($reporterUserId !== null) {
                $dupSql .= " OR reporter_user_id = ?";
                $params[] = $reporterUserId;
            }
            $dupSql .= ") LIMIT 1";
            $dupStmt = $db->prepare($dupSql);
            $dupStmt->execute($params);
            if ($dupStmt->fetch()) {
                return ['success' => false, 'message' => 'Ya reportaste este contenido recientemente.'];
            }

            $db->prepare("
                INSERT INTO content_reports
                    (reporter_user_id, reporter_ip, content_type, content_id, reason, description)
                VALUES (?, ?, ?, ?, ?, ?)
            ")->execute([$reporterUserId, $reporterIp, $contentType, $contentId, $reason, $description]);

            return ['success' => true, 'message' => 'Reporte enviado. Lo revisaremos pronto.'];

        } catch (Exception $e) {
            error_log("Error en Report::create: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al enviar el reporte.'];
        }
    }

    /**
     * Lista reportes con filtros opcionales (solo para admins).
     */
    public static function list(string $status = 'pending', int $limit = 50, int $offset = 0): array {
        try {
            $db = Database::getInstance()->getConnection();
            $where  = $status !== 'all' ? "WHERE cr.status = ?" : "WHERE 1=1";
            $params = $status !== 'all' ? [$status] : [];
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $db->prepare("
                SELECT cr.*,
                       u.username AS reporter_name,
                       r.username AS resolver_name
                FROM content_reports cr
                LEFT JOIN users u  ON u.id  = cr.reporter_user_id
                LEFT JOIN users r  ON r.id  = cr.resolved_by
                $where
                ORDER BY cr.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en Report::list: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Cuenta reportes pendientes (para badge en admin).
     */
    public static function countPending(): int {
        try {
            $db   = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT COUNT(*) FROM content_reports WHERE status = 'pending'");
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("Error en Report::countPending: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Actualiza el estado de un reporte (admin).
     */
    public static function updateStatus(
        int     $reportId,
        string  $status,
        int     $resolvedBy,
        string  $note = ''
    ): array {
        try {
            if (!in_array($status, self::VALID_STATUS, true)) {
                return ['success' => false, 'message' => 'Estado inválido.'];
            }
            $note = mb_substr(trim($note), 0, 500);
            $db   = Database::getInstance()->getConnection();
            $db->prepare("
                UPDATE content_reports
                SET status = ?, resolved_by = ?, resolved_at = NOW(), resolution_note = ?
                WHERE id = ?
            ")->execute([$status, $resolvedBy, $note, $reportId]);

            return ['success' => true, 'message' => 'Reporte actualizado.'];
        } catch (Exception $e) {
            error_log("Error en Report::updateStatus: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al actualizar el reporte.'];
        }
    }
}
