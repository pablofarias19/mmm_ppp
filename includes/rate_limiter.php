<?php
/**
 * Rate limiter basado en base de datos.
 *
 * Uso:
 *   require_once __DIR__ . '/../includes/rate_limiter.php';
 *   checkRateLimit('review_create', 5, 60);  // máx 5 hits en 60 segundos
 */

require_once __DIR__ . '/db_helper.php';

/**
 * Verifica y registra el uso de un endpoint por IP.
 * Si se supera el límite, termina la ejecución con HTTP 429.
 *
 * @param string $endpoint   Identificador del endpoint (ej: 'review_create').
 * @param int    $maxHits    Número máximo de peticiones permitidas.
 * @param int    $windowSec  Ventana de tiempo en segundos.
 */
function checkRateLimit(string $endpoint, int $maxHits = 10, int $windowSec = 60): void {
    $ip = getClientIp();

    try {
        $db = getDbConnection();
        if (!$db) return; // Fallo silencioso si no hay BD

        // Purgar entradas antiguas (limpieza oportunista, 5 % del tiempo)
        if (mt_rand(1, 20) === 1) {
            $db->prepare("DELETE FROM rate_limit_log WHERE hit_at < DATE_SUB(NOW(), INTERVAL ? SECOND)")
               ->execute([$windowSec * 2]);
        }

        // Contar hits recientes
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM rate_limit_log
            WHERE ip = ? AND endpoint = ?
              AND hit_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$ip, $endpoint, $windowSec]);
        $hits = (int)$stmt->fetchColumn();

        if ($hits >= $maxHits) {
            http_response_code(429);
            header('Content-Type: application/json');
            header('Retry-After: ' . $windowSec);
            echo json_encode([
                'success' => false,
                'message' => 'Demasiadas solicitudes. Intenta más tarde.',
            ]);
            exit;
        }

        // Registrar el hit actual
        $db->prepare("INSERT INTO rate_limit_log (ip, endpoint) VALUES (?, ?)")
           ->execute([$ip, $endpoint]);

    } catch (\Exception $e) {
        error_log("Rate limiter error: " . $e->getMessage());
        // Fallo silencioso para no bloquear la aplicación si hay problema de BD
    }
}

/**
 * Devuelve la IP real del cliente respetando proxies de confianza.
 */
function getClientIp(): string {
    // Usar solo cabeceras de proxy si viene de un proxy interno (configurable)
    $trustedProxy = $_ENV['TRUSTED_PROXY'] ?? getenv('TRUSTED_PROXY') ?? '';

    $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    if ($trustedProxy && $remoteAddr === $trustedProxy) {
        // Solo confiar en X-Forwarded-For si el cliente viene del proxy configurado
        $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        if ($forwarded) {
            $ips = array_map('trim', explode(',', $forwarded));
            $ip  = filter_var($ips[0], FILTER_VALIDATE_IP);
            if ($ip) return $ip;
        }
    }

    return $remoteAddr;
}
