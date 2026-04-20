<?php
// Iniciar sesión solo si no hay una sesión activa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// CORS: usar origen configurado en variables de entorno; omitir cabecera si no está configurado
$allowedOrigin = $_ENV['ALLOWED_ORIGIN'] ?? getenv('ALLOWED_ORIGIN') ?: '';
if (!empty($allowedOrigin)) {
    header('Access-Control-Allow-Origin: ' . $allowedOrigin);
}

date_default_timezone_set('America/Argentina/Buenos_Aires');

// ─── Admin helper ────────────────────────────────────────────────────────────

/**
 * Devuelve true si el usuario logueado es administrador.
 * Se considera admin si tiene is_admin=1 en sesión O si su username es 'Pablo_Farias'.
 */
if (!function_exists('isAdmin')) {
    function isAdmin(): bool {
        if (!isset($_SESSION['user_id'])) return false;
        if (!empty($_SESSION['is_admin'])) return true;
        if (($_SESSION['user_name'] ?? '') === 'Pablo_Farias') return true;
        return false;
    }
}

// ─── Security helpers ────────────────────────────────────────────────────────

/**
 * Emite cabeceras HTTP de seguridad recomendadas.
 * Debe llamarse antes de cualquier salida HTML.
 */
function setSecurityHeaders() {
    header("X-Frame-Options: SAMEORIGIN");
    header("X-Content-Type-Options: nosniff");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Content-Security-Policy: default-src 'self'; "
         . "script-src 'self' 'unsafe-inline' https://unpkg.com https://cdnjs.cloudflare.com; "
         . "style-src 'self' 'unsafe-inline' https://unpkg.com; "
         . "img-src 'self' data: https://*.tile.openstreetmap.org; "
         . "connect-src 'self'; "
         . "font-src 'self';");
}

// ─── CSRF helpers ─────────────────────────────────────────────────────────────

/**
 * Genera (o recupera) el token CSRF de la sesión actual.
 * @return string Token CSRF de 32 bytes en hexadecimal.
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica que el token CSRF enviado coincida con el de la sesión.
 * Termina la ejecución con HTTP 403 si no coincide.
 * @param string|null $token Token recibido del formulario.
 */
function verifyCsrfToken($token = null) {
    $token = $token ?? ($_POST['csrf_token'] ?? '');
    if (empty($token) || empty($_SESSION['csrf_token'])
            || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => 'Token CSRF inválido.']));
    }
}

/**
 * Devuelve un campo <input> oculto con el token CSRF listo para insertar en formularios.
 * @return string HTML del campo oculto.
 */
function csrfField() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

// ─── Response helpers ─────────────────────────────────────────────────────────

if (!function_exists('respond_success')) {
    function respond_success($data = null, $message = "Operación exitosa") {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ]);
        exit;
    }
}

if (!function_exists('respond_error')) {
    function respond_error($message = "Error en la operación", $code = 400) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $message,
        ]);
        exit;
    }
}

if (!function_exists('log_api')) {
    function log_api($message, $level = 'INFO') {
        $logFile    = __DIR__ . '/business_log.txt';
        $logMessage = "[" . $level . "] " . date('Y-m-d H:i:s') . " - $message\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}