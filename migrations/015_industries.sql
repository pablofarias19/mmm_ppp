-- ============================================================
-- Módulo INDUSTRIAS — tabla propiedad del usuario
-- Relacionada con industrial_sectors (catálogo admin)
-- Idempotente (CREATE TABLE IF NOT EXISTS)
-- Ejecutar DESPUÉS de migrations/014_industrial_sectors.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS industries (
    id                   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    user_id              INT UNSIGNED  NOT NULL COMMENT 'Usuario propietario',
    industrial_sector_id INT UNSIGNED  NULL     COMMENT 'FK a industrial_sectors (catálogo)',
    business_id          INT UNSIGNED  NULL     COMMENT 'Negocio asociado (opcional)',
    brand_id             INT UNSIGNED  NULL     COMMENT 'Marca asociada (opcional)',
    name                 VARCHAR(255)  NOT NULL,
    description          TEXT          NULL,
    website              VARCHAR(500)  NULL,
    contact_email        VARCHAR(255)  NULL,
    contact_phone        VARCHAR(50)   NULL,
    country              VARCHAR(100)  NULL,
    region               VARCHAR(100)  NULL,
    city                 VARCHAR(100)  NULL,
    employees_range      ENUM('1-10','11-50','51-200','201-500','500+') NULL COMMENT 'Rango de empleados',
    annual_revenue       ENUM('micro','pequeña','mediana','grande','corporación') NULL COMMENT 'Escala de la industria',
    certifications       TEXT          NULL     COMMENT 'Certificaciones separadas por coma',
    naics_code           VARCHAR(20)   NULL     COMMENT 'Código NAICS (opcional)',
    isic_code            VARCHAR(20)   NULL     COMMENT 'Código ISIC (opcional)',
    status               ENUM('borrador','activa','archivada') NOT NULL DEFAULT 'borrador',
    created_at           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME      NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_ind_user_id              (user_id),
    KEY idx_ind_industrial_sector_id (industrial_sector_id),
    KEY idx_ind_name                 (name),
    KEY idx_ind_status               (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
