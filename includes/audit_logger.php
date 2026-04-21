<?php
/**
 * Audit logger — registra acciones sensibles en la tabla audit_log.
 *
 * Uso:
 *   require_once __DIR__ . '/../includes/audit_logger.php';
 *   auditLog('create', 'business', $id, ['name' => 'Mi negocio']);
 */

require_once __DIR__ . '/db_helper.php';
require_once __DIR__ . '/rate_limiter.php';

/**
 * Escribe una entrada en audit_log.
 *
 * @param string     $action      Acción realizada (create|update|delete|login|logout|resolve_report|…).
 * @param string     $entityType  Tipo de entidad afectada (business|review|noticia|…).
 * @param int|null   $entityId    ID de la entidad afectada.
 * @param array      $details     Datos adicionales (se serializan como JSON).
 */
function auditLog(string $action, string $entityType = '', ?int $entityId = null, array $details = []): void {
    try {
        $db = getDbConnection();
        if (!$db) return;

        $userId    = isset($_SESSION['user_id'])   ? (int)$_SESSION['user_id']        : null;
        $username  = isset($_SESSION['user_name']) ? (string)$_SESSION['user_name']   : null;
        $ip        = getClientIp();
        $ua        = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
        $detailsJson = $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null;

        $db->prepare("
            INSERT INTO audit_log (user_id, username, action, entity_type, entity_id, details, ip, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([$userId, $username, $action, $entityType ?: null, $entityId, $detailsJson, $ip, $ua ?: null]);

    } catch (\Exception $e) {
        error_log("Audit log error: " . $e->getMessage());
    }
}
