-- ============================================================
-- Migración 036: Tabla modelos_negocio
-- Crea la tabla que usa el módulo /business_model para
-- almacenar múltiples modelos de negocio por marca
-- (relación one-to-many; no puede aplanarse en `brands`).
--
-- ⚠️  Requiere MySQL ≥ 5.6 o MariaDB ≥ 10.0.
-- Idempotente: usa CREATE TABLE IF NOT EXISTS.
-- ============================================================

CREATE TABLE IF NOT EXISTS modelos_negocio (
    id          INT             NOT NULL AUTO_INCREMENT,
    marca_id    INT             NOT NULL COMMENT 'FK a brands.id',
    tipo        VARCHAR(40)     NOT NULL COMMENT 'EXPLOTACION_DIRECTA | LICENCIAMIENTO | FRANQUICIA | MARCA_BLANCA | ACTIVO_DIGITAL',
    descripcion TEXT            NULL     COMMENT 'Descripción libre del modelo',
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_modelos_negocio_marca (marca_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Modelos de negocio asociados a cada marca (módulo /business_model)';
