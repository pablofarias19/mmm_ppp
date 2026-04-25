-- ============================================================
-- Migración 024: brand_gallery — FK hacia tabla brands
-- La tabla brand_gallery fue creada originalmente con FK
-- fk_gallery_brand → marcas(id). Esta migración la migra a
-- brands(id) para alinearse con el módulo Brands actual.
-- Idempotente: usa DROP FOREIGN KEY IF EXISTS + ADD CONSTRAINT.
-- NO requiere acceso a information_schema (compatible con
-- hosting compartido Hostinger y similares).
-- Requiere MySQL 8.0.17+ o MariaDB 10.2.4+.
-- ============================================================

-- ─────────────────────────────────────────────────────────────
-- 1. Eliminar FK antigua (fk_gallery_brand → marcas)
-- ─────────────────────────────────────────────────────────────
ALTER TABLE brand_gallery
    DROP FOREIGN KEY IF EXISTS fk_gallery_brand;

-- ─────────────────────────────────────────────────────────────
-- 2. Asegurar índice en brand_id
-- ─────────────────────────────────────────────────────────────
ALTER TABLE brand_gallery
    ADD INDEX IF NOT EXISTS idx_brand_gallery_brand_id (brand_id);

-- ─────────────────────────────────────────────────────────────
-- 3. Crear FK hacia brands(id)
-- ─────────────────────────────────────────────────────────────
ALTER TABLE brand_gallery
    ADD CONSTRAINT fk_gallery_brand
        FOREIGN KEY (brand_id) REFERENCES brands(id)
        ON DELETE CASCADE;
