<?php
/**
 * DatabaseSetup - Inicializa la base de datos con columnas necesarias
 * Se ejecuta automáticamente desde el index.php
 */

namespace App\Core;

use PDO;

class DatabaseSetup {
    private static $initialized = false;

    /**
     * Ejecuta la configuración inicial de la base de datos
     * @return bool true si fue exitoso o ya estaba inicializado
     */
    public static function initialize() {
        if (self::$initialized) {
            return true;
        }

        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance();

            // Crear tabla attachments si no existe
            self::createAttachmentsTable($db);

            // Agregar columnas a businesses si no existen
            self::addBusinessesColumns($db);

            // Agregar columnas a brands si no existen
            self::addBrandsColumns($db);

            self::$initialized = true;
            return true;

        } catch (\Exception $e) {
            error_log("Error en DatabaseSetup::initialize: " . $e->getMessage());
            // No lanzar excepción, solo registrar el error
            return false;
        }
    }

    /**
     * Crea la tabla attachments para fotos
     */
    private static function createAttachmentsTable($db) {
        $sql = "CREATE TABLE IF NOT EXISTS attachments (
            id INT PRIMARY KEY AUTO_INCREMENT,
            business_id INT,
            brand_id INT,
            file_path VARCHAR(255) NOT NULL UNIQUE,
            type ENUM('photo', 'document', 'logo') DEFAULT 'photo',
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
            FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE,
            INDEX idx_business (business_id),
            INDEX idx_brand (brand_id),
            INDEX idx_type (type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            $db->exec($sql);
        } catch (\Exception $e) {
            // Tabla probablemente ya existe
            error_log("Tabla attachments: " . $e->getMessage());
        }
    }

    /**
     * Agrega columnas a la tabla businesses
     */
    private static function addBusinessesColumns($db) {
        $columnsToAdd = [
            'instagram' => "VARCHAR(100) DEFAULT NULL",
            'facebook' => "VARCHAR(100) DEFAULT NULL",
            'tiktok' => "VARCHAR(100) DEFAULT NULL",
            'certifications' => "TEXT DEFAULT NULL",
            'has_delivery' => "BOOLEAN DEFAULT 0",
            'has_card_payment' => "BOOLEAN DEFAULT 0",
            'is_franchise' => "BOOLEAN DEFAULT 0",
            'verified' => "BOOLEAN DEFAULT 0"
        ];

        foreach ($columnsToAdd as $columnName => $columnDef) {
            try {
                $checkSql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                            WHERE TABLE_NAME = 'businesses' AND COLUMN_NAME = ?";
                $stmt = $db->prepare($checkSql);
                $stmt->execute([$columnName]);

                if ($stmt->rowCount() === 0) {
                    $addSql = "ALTER TABLE businesses ADD COLUMN $columnName $columnDef AFTER `updated_at`";
                    $db->exec($addSql);
                }
            } catch (\Exception $e) {
                error_log("Error agregando columna $columnName a businesses: " . $e->getMessage());
            }
        }

        // Agregar índices
        try {
            $db->exec("ALTER TABLE businesses ADD INDEX IF NOT EXISTS idx_verified (verified)");
            $db->exec("ALTER TABLE businesses ADD INDEX IF NOT EXISTS idx_has_delivery (has_delivery)");
            $db->exec("ALTER TABLE businesses ADD INDEX IF NOT EXISTS idx_instagram (instagram)");
        } catch (\Exception $e) {
            // Los índices pueden ya existir
        }
    }

    /**
     * Agrega columnas a la tabla brands
     */
    private static function addBrandsColumns($db) {
        $columnsToAdd = [
            'scope' => "VARCHAR(100) DEFAULT NULL",
            'channels' => "VARCHAR(255) DEFAULT NULL",
            'annual_revenue' => "VARCHAR(50) DEFAULT NULL",
            'founded_year' => "INT DEFAULT NULL",
            'extended_description' => "LONGTEXT DEFAULT NULL"
        ];

        foreach ($columnsToAdd as $columnName => $columnDef) {
            try {
                $checkSql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                            WHERE TABLE_NAME = 'brands' AND COLUMN_NAME = ?";
                $stmt = $db->prepare($checkSql);
                $stmt->execute([$columnName]);

                if ($stmt->rowCount() === 0) {
                    $addSql = "ALTER TABLE brands ADD COLUMN $columnName $columnDef AFTER `estado`";
                    $db->exec($addSql);
                }
            } catch (\Exception $e) {
                error_log("Error agregando columna $columnName a brands: " . $e->getMessage());
            }
        }

        // Agregar índices
        try {
            $db->exec("ALTER TABLE brands ADD INDEX IF NOT EXISTS idx_scope (scope)");
            $db->exec("ALTER TABLE brands ADD INDEX IF NOT EXISTS idx_founded (founded_year)");
        } catch (\Exception $e) {
            // Los índices pueden ya existir
        }
    }
}

// Inicializar automáticamente
DatabaseSetup::initialize();
