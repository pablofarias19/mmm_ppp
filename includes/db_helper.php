<?php
/**
 * Archivo auxiliar de base de datos
 * Proporciona funciones simplificadas para acceder a la base de datos
 */

// Incluir correctamente los archivos necesarios con la estructura existente
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';

/**
 * Obtiene una conexión a la base de datos utilizando la clase Database existente
 * @return PDO La conexión PDO a la base de datos
 */
if (!function_exists('getDbConnection')) {
    function getDbConnection() {
        try {
            return \Core\Database::getInstance()->getConnection();
        } catch (Exception $e) {
            error_log("Error de conexión a la base de datos: " . $e->getMessage());
            return null;
        }
    }
}

/**
 * Obtiene la fecha y hora actual en formato UTC
 * @return string Fecha y hora actual formateada
 */
function getCurrentDateTime() {
    return date('Y-m-d H:i:s');
}

/**
 * Obtiene el nombre de usuario actual de la sesión activa.
 * @return string Nombre de usuario actual
 */
function getCurrentUsername() {
    return $_SESSION['user_name'] ?? '';
}

/**
 * Función para responder con éxito (usando la función existente si está disponible)
 */
function respondSuccess($data, $message = "Operación exitosa") {
    if (function_exists('respond_success')) {
        return respond_success($data, $message);
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }
}

/**
 * Función para responder con error (usando la función existente si está disponible)
 */
function respondError($message = "Ha ocurrido un error") {
    if (function_exists('respond_error')) {
        return respond_error($message);
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
        exit;
    }
}

if (!function_exists('mapitaEnsureSettingsTable')) {
    /**
     * Crea la tabla mapita_settings si no existe (idempotente).
     * Llamado automáticamente desde mapitaGetSetting y mapitaSetSetting.
     */
    function mapitaEnsureSettingsTable(PDO $db): bool {
        try {
            $db->exec("
                CREATE TABLE IF NOT EXISTS mapita_settings (
                    setting_key   VARCHAR(100)  NOT NULL,
                    setting_value TEXT          NOT NULL DEFAULT '',
                    updated_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (setting_key)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            return true;
        } catch (Throwable $e) {
            error_log('mapitaEnsureSettingsTable error: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('mapitaGetSetting')) {
    /**
     * Obtiene el valor de una configuración global desde la tabla mapita_settings.
     * Devuelve $default si la tabla no existe o la clave no fue encontrada.
     */
    function mapitaGetSetting(PDO $db, string $key, $default = null) {
        try {
            if (!mapitaTableExists($db, 'mapita_settings')) {
                mapitaEnsureSettingsTable($db);
            }
            $stmt = $db->prepare('SELECT setting_value FROM mapita_settings WHERE setting_key = ? LIMIT 1');
            $stmt->execute([$key]);
            $val = $stmt->fetchColumn();
            return ($val !== false) ? $val : $default;
        } catch (Throwable $e) {
            return $default;
        }
    }
}

if (!function_exists('mapitaSetSetting')) {
    /**
     * Guarda (INSERT o UPDATE) una configuración global en mapita_settings.
     * Crea la tabla si no existe (auto-migración idempotente).
     */
    function mapitaSetSetting(PDO $db, string $key, string $value): void {
        try {
            if (!mapitaTableExists($db, 'mapita_settings')) {
                if (!mapitaEnsureSettingsTable($db)) {
                    return;
                }
            }
            $db->prepare("
                INSERT INTO mapita_settings (setting_key, setting_value)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ")->execute([$key, $value]);
        } catch (Throwable $e) {
            error_log('mapitaSetSetting error: ' . $e->getMessage());
        }
    }
}

if (!function_exists('mapitaTableExists')) {
    function mapitaTableExists(PDO $db, string $table): bool {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return false;
        }
        try {
            $stmt = $db->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1');
            $stmt->execute([$table]);
            return (bool)$stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('mapitaColumnExists')) {
    function mapitaColumnExists(PDO $db, string $table, string $column): bool {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table) || !preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
            return false;
        }
        try {
            $stmt = $db->prepare('SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1');
            $stmt->execute([$table, $column]);
            return (bool)$stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('isBusinessOwner')) {
    function isBusinessOwner(int $currentUserId, int $businessId): bool {
        $db = getDbConnection();
        if (!$db || $currentUserId <= 0 || $businessId <= 0 || !mapitaTableExists($db, 'businesses')) {
            return false;
        }
        $stmt = $db->prepare('SELECT 1 FROM businesses WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$businessId, $currentUserId]);
        return (bool)$stmt->fetchColumn();
    }
}

if (!function_exists('isBrandOwner')) {
    function isBrandOwner(int $currentUserId, int $brandId): bool {
        $db = getDbConnection();
        if (!$db || $currentUserId <= 0 || $brandId <= 0) {
            return false;
        }

        if (mapitaTableExists($db, 'brands')) {
            $stmt = $db->prepare('SELECT 1 FROM brands WHERE id = ? AND user_id = ? LIMIT 1');
            $stmt->execute([$brandId, $currentUserId]);
            if ($stmt->fetchColumn()) {
                return true;
            }
        }

        if (mapitaTableExists($db, 'marcas')) {
            $stmt = $db->prepare('SELECT 1 FROM marcas WHERE id = ? AND usuario_id = ? LIMIT 1');
            $stmt->execute([$brandId, $currentUserId]);
            if ($stmt->fetchColumn()) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('canManageBusiness')) {
    function canManageBusiness(int $currentUserId, int $businessId): bool {
        if ($currentUserId <= 0 || $businessId <= 0) {
            return false;
        }
        if (isAdmin()) {
            return true;
        }
        if (isBusinessOwner($currentUserId, $businessId)) {
            return true;
        }

        $db = getDbConnection();
        if (!$db || !mapitaTableExists($db, 'business_delegations')) {
            return false;
        }

        $stmt = $db->prepare("SELECT 1 FROM business_delegations WHERE business_id = ? AND user_id = ? AND role = 'admin' LIMIT 1");
        $stmt->execute([$businessId, $currentUserId]);
        return (bool)$stmt->fetchColumn();
    }
}

if (!function_exists('canManageBrand')) {
    function canManageBrand(int $currentUserId, int $brandId): bool {
        if ($currentUserId <= 0 || $brandId <= 0) {
            return false;
        }
        if (isAdmin()) {
            return true;
        }
        if (isBrandOwner($currentUserId, $brandId)) {
            return true;
        }

        $db = getDbConnection();
        if (!$db || !mapitaTableExists($db, 'brand_delegations')) {
            return false;
        }

        $stmt = $db->prepare("SELECT 1 FROM brand_delegations WHERE brand_id = ? AND user_id = ? AND role = 'admin' LIMIT 1");
        $stmt->execute([$brandId, $currentUserId]);
        return (bool)$stmt->fetchColumn();
    }
}
