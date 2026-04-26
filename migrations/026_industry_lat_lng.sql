-- ============================================================
-- Migración 026: lat/lng para industrias
-- Agrega columnas de coordenadas geográficas a la tabla industries
-- Idempotente (ALTER TABLE … ADD COLUMN IF NOT EXISTS)
-- ============================================================

ALTER TABLE industries
    ADD COLUMN IF NOT EXISTS lat DECIMAL(10,7) NULL COMMENT 'Latitud geográfica'  AFTER ciiu_code,
    ADD COLUMN IF NOT EXISTS lng DECIMAL(10,7) NULL COMMENT 'Longitud geográfica' AFTER lat;
