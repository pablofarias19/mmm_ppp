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
    $apertura = substr($apertura, 0, 5);
    $cierre   = substr($cierre,   0, 5);
    if (!$apertura && !$cierre) return '';
    $apertura = htmlspecialchars($apertura, ENT_QUOTES, 'UTF-8');
    $cierre   = htmlspecialchars($cierre,   ENT_QUOTES, 'UTF-8');
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

// ─── i18n / l10n helpers ─────────────────────────────────────────────────────

/**
 * Lista de países agrupados por región para el selector de formulario.
 * Cada entrada: 'CC' => 'Nombre del país'
 */
function getCountryOptions(): array {
    return [
        'América del Sur' => [
            'AR' => 'Argentina',
            'BO' => 'Bolivia',
            'BR' => 'Brasil',
            'CL' => 'Chile',
            'CO' => 'Colombia',
            'EC' => 'Ecuador',
            'GY' => 'Guyana',
            'PY' => 'Paraguay',
            'PE' => 'Perú',
            'SR' => 'Surinam',
            'UY' => 'Uruguay',
            'VE' => 'Venezuela',
        ],
        'América Central y Caribe' => [
            'CU' => 'Cuba',
            'DO' => 'República Dominicana',
            'GT' => 'Guatemala',
            'HN' => 'Honduras',
            'MX' => 'México',
            'NI' => 'Nicaragua',
            'PA' => 'Panamá',
            'SV' => 'El Salvador',
            'CR' => 'Costa Rica',
        ],
        'América del Norte' => [
            'CA' => 'Canadá',
            'US' => 'Estados Unidos',
        ],
        'Europa' => [
            'AT' => 'Austria',
            'BE' => 'Bélgica',
            'CH' => 'Suiza',
            'DE' => 'Alemania',
            'ES' => 'España',
            'FR' => 'Francia',
            'GB' => 'Reino Unido',
            'IT' => 'Italia',
            'NL' => 'Países Bajos',
            'NO' => 'Noruega',
            'PL' => 'Polonia',
            'PT' => 'Portugal',
            'RU' => 'Rusia',
            'SE' => 'Suecia',
        ],
        'Asia' => [
            'AE' => 'Emiratos Árabes Unidos',
            'CN' => 'China',
            'HK' => 'Hong Kong',
            'IL' => 'Israel',
            'IN' => 'India',
            'JP' => 'Japón',
            'KR' => 'Corea del Sur',
            'SA' => 'Arabia Saudita',
            'SG' => 'Singapur',
            'TH' => 'Tailandia',
            'TR' => 'Turquía',
        ],
        'África' => [
            'EG' => 'Egipto',
            'MA' => 'Marruecos',
            'NG' => 'Nigeria',
            'ZA' => 'Sudáfrica',
        ],
        'Oceanía' => [
            'AU' => 'Australia',
            'NZ' => 'Nueva Zelanda',
        ],
    ];
}

/**
 * Lista de idiomas soportados (BCP 47) para el selector de formulario.
 */
function getLanguageOptions(): array {
    return [
        'es'    => 'Español',
        'es-AR' => 'Español (Argentina)',
        'es-MX' => 'Español (México)',
        'es-ES' => 'Español (España)',
        'es-CL' => 'Español (Chile)',
        'es-CO' => 'Español (Colombia)',
        'es-PE' => 'Español (Perú)',
        'en'    => 'English',
        'en-US' => 'English (US)',
        'en-GB' => 'English (UK)',
        'en-AU' => 'English (Australia)',
        'pt'    => 'Português',
        'pt-BR' => 'Português (Brasil)',
        'pt-PT' => 'Português (Portugal)',
        'fr'    => 'Français',
        'fr-FR' => 'Français (France)',
        'de'    => 'Deutsch',
        'de-DE' => 'Deutsch (Deutschland)',
        'no'    => 'Norsk',
        'no-NO' => 'Norsk (Bokmål)',
        'zh'    => '中文',
        'zh-CN' => '中文（简体）',
        'zh-TW' => '中文（繁體）',
        'ar'    => 'العربية',
        'ar-SA' => 'العربية (المملكة العربية السعودية)',
        'ja'    => '日本語',
        'ja-JP' => '日本語（日本）',
        'ko'    => '한국어',
        'it'    => 'Italiano',
        'ru'    => 'Русский',
        'nl'    => 'Nederlands',
    ];
}

/**
 * Devuelve el código ISO 4217 de moneda para un country_code dado.
 */
function getCurrencyByCountry(string $cc): string {
    $map = [
        'AR' => 'ARS', 'BO' => 'BOB', 'BR' => 'BRL', 'CL' => 'CLP',
        'CO' => 'COP', 'EC' => 'USD', 'PY' => 'PYG', 'PE' => 'PEN',
        'UY' => 'UYU', 'VE' => 'VES', 'MX' => 'MXN', 'GT' => 'GTQ',
        'CR' => 'CRC', 'PA' => 'PAB', 'DO' => 'DOP', 'CU' => 'CUP',
        'US' => 'USD', 'CA' => 'CAD',
        'GB' => 'GBP', 'DE' => 'EUR', 'FR' => 'EUR', 'ES' => 'EUR',
        'IT' => 'EUR', 'PT' => 'EUR', 'NL' => 'EUR', 'BE' => 'EUR',
        'AT' => 'EUR', 'CH' => 'CHF', 'SE' => 'SEK', 'NO' => 'NOK',
        'PL' => 'PLN', 'RU' => 'RUB',
        'JP' => 'JPY', 'CN' => 'CNY', 'KR' => 'KRW', 'IN' => 'INR',
        'SG' => 'SGD', 'HK' => 'HKD', 'TH' => 'THB', 'AE' => 'AED',
        'SA' => 'SAR', 'IL' => 'ILS', 'TR' => 'TRY',
        'ZA' => 'ZAR', 'EG' => 'EGP', 'NG' => 'NGN', 'MA' => 'MAD',
        'AU' => 'AUD', 'NZ' => 'NZD',
    ];
    return $map[strtoupper($cc)] ?? 'USD';
}

/**
 * Devuelve el prefijo telefónico internacional para un country_code dado.
 */
function getPhoneCodeByCountry(string $cc): string {
    $map = [
        'AR' => '+54',  'BO' => '+591', 'BR' => '+55',  'CL' => '+56',
        'CO' => '+57',  'EC' => '+593', 'PY' => '+595', 'PE' => '+51',
        'UY' => '+598', 'VE' => '+58',  'MX' => '+52',  'GT' => '+502',
        'CR' => '+506', 'PA' => '+507', 'DO' => '+1',   'CU' => '+53',
        'US' => '+1',   'CA' => '+1',
        'GB' => '+44',  'DE' => '+49',  'FR' => '+33',  'ES' => '+34',
        'IT' => '+39',  'PT' => '+351', 'NL' => '+31',  'BE' => '+32',
        'AT' => '+43',  'CH' => '+41',  'SE' => '+46',  'NO' => '+47',
        'PL' => '+48',  'RU' => '+7',
        'JP' => '+81',  'CN' => '+86',  'KR' => '+82',  'IN' => '+91',
        'SG' => '+65',  'HK' => '+852', 'TH' => '+66',  'AE' => '+971',
        'SA' => '+966', 'IL' => '+972', 'TR' => '+90',
        'ZA' => '+27',  'EG' => '+20',  'NG' => '+234', 'MA' => '+212',
        'AU' => '+61',  'NZ' => '+64',
    ];
    return $map[strtoupper($cc)] ?? '';
}

/**
 * Devuelve el organismo registrador de marcas para un country_code dado.
 */
function getRegistryByCountry(string $cc): string {
    $map = [
        'AR' => 'INPI',    'BR' => 'INPI Brasil', 'MX' => 'IMPI',
        'CL' => 'INAPI',   'CO' => 'SIC',         'PE' => 'INDECOPI',
        'UY' => 'DNPI',    'PY' => 'DINAPI',
        'US' => 'USPTO',   'CA' => 'CIPO',
        'GB' => 'IPO',     'DE' => 'DPMA',        'FR' => 'INPI France',
        'ES' => 'OEPM',    'IT' => 'UIBM',        'PT' => 'INPI Portugal',
        'NL' => 'BBIE',    'BE' => 'BOIP',        'AT' => 'PPA',
        'CH' => 'IGE-IPI', 'SE' => 'PRV',         'NO' => 'Patentstyret',
        'PL' => 'UPRP',    'RU' => 'Rospatent',
        'JP' => 'JPO',     'CN' => 'CNIPA',       'KR' => 'KIPO',
        'IN' => 'Trade Marks Registry',
        'AU' => 'IP Australia',                    'NZ' => 'IPONZ',
        'ZA' => 'CIPC',    'EG' => 'ITDA',
    ];
    return $map[strtoupper($cc)] ?? '';
}

// ─── UI translation helpers ───────────────────────────────────────────────────

/** Idiomas soportados y sus archivos de traducción. */
const MAPITA_SUPPORTED_LANGS = ['es', 'en', 'pt', 'fr', 'de', 'no', 'zh', 'ar'];

/** Caché en memoria para los arrays de strings cargados. */
$_mapitaLangCache = [];

/**
 * Guarda el idioma de interfaz elegido por el usuario en la sesión.
 */
function setUILanguage(string $langCode): void {
    $base = strtolower(explode('-', $langCode)[0]);
    if (in_array($base, MAPITA_SUPPORTED_LANGS, true)) {
        $_SESSION['ui_lang'] = $base;
    }
}

/**
 * Devuelve el idioma de interfaz activo.
 * Orden de prioridad: sesión → parámetro GET → 'es'.
 */
function getUILanguage(): string {
    $lang = $_SESSION['ui_lang'] ?? ($_GET['lang'] ?? 'es');
    $base = strtolower(explode('-', (string)$lang)[0]);
    return in_array($base, MAPITA_SUPPORTED_LANGS, true) ? $base : 'es';
}

/**
 * Traduce una clave al idioma activo de la interfaz.
 * Si no existe la clave, hace fallback a español.
 * Admite sustitución de variables: t('lbl_foo', ['name' => 'Bar']) → reemplaza {name}.
 */
function t(string $key, array $vars = []): string {
    global $_mapitaLangCache;

    $lang = getUILanguage();
    // La whitelist es la fuente de verdad para los archivos permitidos
    if (!in_array($lang, MAPITA_SUPPORTED_LANGS, true)) {
        $lang = 'es';
    }

    if (!isset($_mapitaLangCache[$lang])) {
        $file = __DIR__ . '/../lang/' . $lang . '.php';
        $_mapitaLangCache[$lang] = (file_exists($file)) ? (require $file) : [];
    }

    // Fallback a español si la clave no existe en el idioma activo
    $str = $_mapitaLangCache[$lang][$key] ?? null;
    if ($str === null) {
        if (!isset($_mapitaLangCache['es'])) {
            $esFile = __DIR__ . '/../lang/es.php';
            $_mapitaLangCache['es'] = file_exists($esFile) ? (require $esFile) : [];
        }
        $str = $_mapitaLangCache['es'][$key] ?? $key;
    }

    foreach ($vars as $k => $v) {
        $str = str_replace('{' . $k . '}', htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'), $str);
    }

    return $str;
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