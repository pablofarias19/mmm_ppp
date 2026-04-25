-- ============================================================
-- Migración 023: Integridad referencial + límites de imágenes
-- Agrega FK constraints a la tabla industries para business_id
-- y brand_id, crea índices faltantes, y crea la tabla
-- industry_images para imágenes de industrias (máx 2 × 120 KB).
-- Idempotente: usa DROP ... IF EXISTS + ADD CONSTRAINT.
-- NO requiere acceso a information_schema (compatible con
-- hosting compartido Hostinger y similares).
-- Requiere MySQL 8.0.17+ o MariaDB 10.2.4+.
-- Ejecutar DESPUÉS de migrations/015_industries.sql
-- ============================================================

-- ─────────────────────────────────────────────────────────────
-- 1. Índices de FK en industries (necesarios antes de
--    agregar los constraints)
-- ─────────────────────────────────────────────────────────────
ALTER TABLE industries
    ADD INDEX IF NOT EXISTS idx_industries_business_id (business_id),
    ADD INDEX IF NOT EXISTS idx_industries_brand_id    (brand_id);

-- ─────────────────────────────────────────────────────────────
-- 2. FK industries → businesses
--    DROP IF EXISTS hace la sentencia idempotente sin necesitar
--    information_schema.
-- ─────────────────────────────────────────────────────────────
ALTER TABLE industries
    DROP FOREIGN KEY IF EXISTS fk_industries_business;

ALTER TABLE industries
    ADD CONSTRAINT fk_industries_business
        FOREIGN KEY (business_id) REFERENCES businesses(id)
        ON DELETE SET NULL ON UPDATE CASCADE;

-- ─────────────────────────────────────────────────────────────
-- 3. FK industries → brands
-- ─────────────────────────────────────────────────────────────
ALTER TABLE industries
    DROP FOREIGN KEY IF EXISTS fk_industries_brand;

ALTER TABLE industries
    ADD CONSTRAINT fk_industries_brand
        FOREIGN KEY (brand_id) REFERENCES brands(id)
        ON DELETE SET NULL ON UPDATE CASCADE;

-- ─────────────────────────────────────────────────────────────
-- 4. Tabla industry_images — imágenes de industrias
--    Máx 2 imágenes por industria, cada una ≤ 120 KB.
--    file_path guarda la ruta relativa desde la raíz del proyecto
--    (p.ej. uploads/industries/5/gallery_1234.jpg).
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS industry_images (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    industry_id INT UNSIGNED    NOT NULL COMMENT 'FK a industries',
    file_path   VARCHAR(500)    NOT NULL COMMENT 'Ruta relativa: uploads/industries/{id}/...',
    mime_type   VARCHAR(30)     NULL     COMMENT 'MIME real validado al subir',
    size_bytes  INT UNSIGNED    NULL     COMMENT 'Tamaño en bytes al momento de la subida',
    uploaded_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ind_images_industry (industry_id),
    CONSTRAINT fk_ind_images_industry
        FOREIGN KEY (industry_id) REFERENCES industries(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Imágenes de industrias. Máx 2 por industria, ≤ 120 KB cada una.';

-- ─────────────────────────────────────────────────────────────
-- 4. Recordatorio de límites vigentes (comentario)
--    Businesses : máx 2 fotos  · ≤ 120 KB c/u  (upload_business_gallery.php)
--    Industries : máx 2 fotos  · ≤ 120 KB c/u  (upload_industry_gallery.php)
--    Brands logo: máx 1 logo   · ≤ 120 KB       (upload_brand_logo.php)
--    Brands img : máx 1 imagen · ≤ 120 KB       (brand-gallery.php / BrandGallery.php)
-- ─────────────────────────────────────────────────────────────
