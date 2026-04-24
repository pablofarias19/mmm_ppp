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

// ─── Timezone helpers ────────────────────────────────────────────────────────

/**
 * Lista de timezones IANA frecuentes para el selector del formulario.
 * Agrupadas por región.
 */
function getTimezoneOptions(): array {
    return [
        'América — Argentina' => [
            'America/Argentina/Buenos_Aires' => 'Buenos Aires (ART UTC-3)',
            'America/Argentina/Cordoba'      => 'Córdoba (ART UTC-3)',
            'America/Argentina/Mendoza'      => 'Mendoza (ART UTC-3)',
        ],
        'América — Latinoamérica' => [
            'America/Santiago'     => 'Santiago de Chile (CLT UTC-4)',
            'America/Montevideo'   => 'Montevideo (UYT UTC-3)',
            'America/Asuncion'     => 'Asunción (PYT UTC-4)',
            'America/La_Paz'       => 'La Paz (BOT UTC-4)',
            'America/Lima'         => 'Lima (PET UTC-5)',
            'America/Bogota'       => 'Bogotá (COT UTC-5)',
            'America/Caracas'      => 'Caracas (VET UTC-4)',
            'America/Mexico_City'  => 'Ciudad de México (CST UTC-6)',
            'America/Sao_Paulo'    => 'São Paulo (BRT UTC-3)',
        ],
        'América — EE.UU. / Canadá' => [
            'America/New_York'     => 'Nueva York (ET UTC-5)',
            'America/Chicago'      => 'Chicago (CT UTC-6)',
            'America/Denver'       => 'Denver (MT UTC-7)',
            'America/Los_Angeles'  => 'Los Ángeles (PT UTC-8)',
        ],
        'Europa' => [
            'Europe/Madrid'    => 'Madrid (CET UTC+1)',
            'Europe/Lisbon'    => 'Lisboa (WET UTC+0)',
            'Europe/London'    => 'Londres (GMT UTC+0)',
            'Europe/Paris'     => 'París (CET UTC+1)',
            'Europe/Berlin'    => 'Berlín (CET UTC+1)',
            'Europe/Rome'      => 'Roma (CET UTC+1)',
            'Europe/Moscow'    => 'Moscú (MSK UTC+3)',
        ],
        'Asia / Pacífico' => [
            'Asia/Dubai'     => 'Dubái (GST UTC+4)',
            'Asia/Kolkata'   => 'India (IST UTC+5:30)',
            'Asia/Bangkok'   => 'Bangkok (ICT UTC+7)',
            'Asia/Shanghai'  => 'China (CST UTC+8)',
            'Asia/Tokyo'     => 'Tokio (JST UTC+9)',
            'Asia/Seoul'     => 'Seúl (KST UTC+9)',
            'Australia/Sydney' => 'Sídney (AEST UTC+10)',
        ],
        'África / Oceanía' => [
            'Africa/Cairo'       => 'Cairo (EET UTC+2)',
            'Africa/Johannesburg'=> 'Johannesburgo (SAST UTC+2)',
            'Pacific/Auckland'   => 'Auckland (NZST UTC+12)',
        ],
        'UTC' => [
            'UTC' => 'UTC (UTC+0)',
        ],
    ];
}

/**
 * Valida que un string sea un identificador de timezone IANA válido.
 */
function isValidTimezone(string $tz): bool {
    return in_array($tz, DateTimeZone::listIdentifiers(), true);
}

/**
 * Formatea un horario HH:MM con la abreviatura de timezone del negocio.
 * Ejemplo: "09:00 – 18:00 (JST)"
 *
 * @param string $apertura Hora de apertura en formato HH:MM
 * @param string $cierre   Hora de cierre en formato HH:MM
 * @param string $tz       Timezone IANA del negocio
 * @return string          Cadena formateada lista para mostrar
 */
function formatHorarioLocal(string $apertura, string $cierre, string $tz = 'America/Argentina/Buenos_Aires'): string {
    $apertura = htmlspecialchars(substr($apertura, 0, 5), ENT_QUOTES, 'UTF-8');
    $cierre   = htmlspecialchars(substr($cierre, 0, 5), ENT_QUOTES, 'UTF-8');
    if (!$apertura && !$cierre) return '';
    try {
        $dttz = new DateTimeZone($tz);
        $dt   = new DateTime('now', $dttz);
        $abbr = $dt->format('T'); // e.g. ART, JST, CET
    } catch (\Exception $e) {
        $abbr = '';
    }
    $label = $apertura && $cierre ? "{$apertura} – {$cierre}" : ($apertura ?: $cierre);
    return $abbr ? "{$label} ({$abbr})" : $label;
}

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