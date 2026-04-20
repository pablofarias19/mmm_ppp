-- Migración: Agregar campos profesionales a la tabla businesses
-- Fecha: 2026-04-16
-- Descripción: Añade campos para redes sociales, certificaciones, servicios y verificación

-- ============================================================================
-- TABLA: businesses - Agregar nuevas columnas
-- ============================================================================

ALTER TABLE businesses ADD COLUMN IF NOT EXISTS instagram VARCHAR(100) AFTER website;
ALTER TABLE businesses ADD COLUMN IF NOT EXISTS facebook VARCHAR(100) AFTER instagram;
ALTER TABLE businesses ADD COLUMN IF NOT EXISTS tiktok VARCHAR(100) AFTER facebook;
ALTER TABLE businesses ADD COLUMN IF NOT EXISTS certifications TEXT AFTER tiktok;
ALTER TABLE businesses ADD COLUMN IF NOT EXISTS has_delivery BOOLEAN DEFAULT 0 AFTER certifications;
ALTER TABLE businesses ADD COLUMN IF NOT EXISTS has_card_payment BOOLEAN DEFAULT 0 AFTER has_delivery;
ALTER TABLE businesses ADD COLUMN IF NOT EXISTS is_franchise BOOLEAN DEFAULT 0 AFTER has_card_payment;
ALTER TABLE businesses ADD COLUMN IF NOT EXISTS verified BOOLEAN DEFAULT 0 AFTER is_franchise;

-- ============================================================================
-- TABLA: attachments - Crear si no existe (para fotos de negocios y marcas)
-- ============================================================================

CREATE TABLE IF NOT EXISTS attachments (
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
);

-- ============================================================================
-- TABLA: brands - Agregar nuevas columnas
-- ============================================================================

ALTER TABLE brands ADD COLUMN IF NOT EXISTS scope VARCHAR(100) AFTER estado;
ALTER TABLE brands ADD COLUMN IF NOT EXISTS channels VARCHAR(255) AFTER scope;
ALTER TABLE brands ADD COLUMN IF NOT EXISTS annual_revenue VARCHAR(50) AFTER channels;
ALTER TABLE brands ADD COLUMN IF NOT EXISTS founded_year INT AFTER annual_revenue;
ALTER TABLE brands ADD COLUMN IF NOT EXISTS extended_description LONGTEXT AFTER founded_year;

-- ============================================================================
-- Actualizar índices para mejor rendimiento
-- ============================================================================

ALTER TABLE businesses ADD INDEX IF NOT EXISTS idx_verified (verified);
ALTER TABLE businesses ADD INDEX IF NOT EXISTS idx_has_delivery (has_delivery);
ALTER TABLE businesses ADD INDEX IF NOT EXISTS idx_instagram (instagram);
ALTER TABLE brands ADD INDEX IF NOT EXISTS idx_scope (scope);
ALTER TABLE brands ADD INDEX IF NOT EXISTS idx_founded (founded_year);

-- ============================================================================
-- Confirmación
-- ============================================================================
-- Ejecutar con: mysql -u usuario -p base_datos < migrations/add_professional_fields.sql
-- O desde PHP usando getDbConnection()->exec()
