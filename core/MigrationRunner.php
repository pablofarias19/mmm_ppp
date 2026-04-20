<?php
/**
 * MigrationRunner - Ejecuta migraciones de base de datos
 * Uso: MigrationRunner::runMigration('add_professional_fields');
 */

namespace App\Core;

class MigrationRunner {
    private static $migrationsDir = __DIR__ . '/../migrations';
    private static $db = null;

    /**
     * Ejecuta una migración específica
     * @param string $migrationName Nombre del archivo (sin .sql)
     * @return array ['success' => bool, 'message' => string]
     */
    public static function runMigration($migrationName) {
        try {
            $filePath = self::$migrationsDir . '/' . $migrationName . '.sql';

            if (!file_exists($filePath)) {
                return [
                    'success' => false,
                    'message' => "Archivo de migración no encontrado: {$filePath}"
                ];
            }

            $sql = file_get_contents($filePath);
            if (!$sql) {
                return [
                    'success' => false,
                    'message' => "No se pudo leer el archivo de migración"
                ];
            }

            // Obtener conexión a la base de datos
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance();

            // Separar sentencias SQL por punto y coma
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                fn($s) => !empty($s) && !str_starts_with($s, '--')
            );

            foreach ($statements as $statement) {
                if (empty($statement)) continue;
                $db->exec($statement);
            }

            return [
                'success' => true,
                'message' => "Migración '{$migrationName}' ejecutada correctamente"
            ];

        } catch (\Exception $e) {
            error_log("Error en MigrationRunner: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Error ejecutando migración: " . $e->getMessage()
            ];
        }
    }

    /**
     * Crea las columnas de una tabla si no existen
     * @param string $tableName
     * @param array $columns
     * @return bool
     */
    public static function ensureColumns($tableName, $columns) {
        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance();

            foreach ($columns as $columnName => $columnDef) {
                $sql = "ALTER TABLE {$tableName} ADD COLUMN IF NOT EXISTS {$columnName} {$columnDef}";
                $db->exec($sql);
            }

            return true;
        } catch (\Exception $e) {
            error_log("Error en ensureColumns: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica si una columna existe en una tabla
     * @param string $tableName
     * @param string $columnName
     * @return bool
     */
    public static function columnExists($tableName, $columnName) {
        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance();

            $result = $db->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                                WHERE TABLE_NAME = '{$tableName}' AND COLUMN_NAME = '{$columnName}'");

            return $result->rowCount() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Crea la tabla attachments si no existe
     * @return bool
     */
    public static function createAttachmentsTable() {
        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance();

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
            )";

            $db->exec($sql);
            return true;
        } catch (\Exception $e) {
            error_log("Error creando tabla attachments: " . $e->getMessage());
            return false;
        }
    }
}
