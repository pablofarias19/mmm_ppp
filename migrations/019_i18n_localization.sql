-- ============================================================
-- Migración 019: Internacionalización (i18n / l10n)
-- Agrega campos de país, idioma, moneda y formato de dirección
-- a las tablas businesses, brands e industries.
-- Idempotente: usa ADD COLUMN IF NOT EXISTS.
-- ============================================================

-- ─────────────────────────────────────────────────────────────
-- 1. Tabla BUSINESSES
-- ─────────────────────────────────────────────────────────────
ALTER TABLE businesses
    ADD COLUMN IF NOT EXISTS country_code      CHAR(2)       NULL COMMENT 'ISO 3166-1 alpha-2 (AR, US, DE…)'           AFTER timezone,
    ADD COLUMN IF NOT EXISTS language_code     CHAR(5)       NULL COMMENT 'BCP 47 (es-AR, en-US, ja-JP…)'             AFTER country_code,
    ADD COLUMN IF NOT EXISTS currency_code     CHAR(3)       NULL COMMENT 'ISO 4217 (ARS, USD, EUR, JPY…)'            AFTER language_code,
    ADD COLUMN IF NOT EXISTS phone_country_code VARCHAR(6)   NULL COMMENT 'Prefijo internacional (+54, +1, +81…)'     AFTER currency_code,
    ADD COLUMN IF NOT EXISTS address_format    VARCHAR(20)   NULL COMMENT 'Perfil de formato de dirección: ar|us|jp|eu' AFTER phone_country_code;

-- Índice para filtrar por país en el mapa
ALTER TABLE businesses
    ADD INDEX IF NOT EXISTS idx_businesses_country_code (country_code);

-- ─────────────────────────────────────────────────────────────
-- 2. Tabla BRANDS
-- ─────────────────────────────────────────────────────────────
ALTER TABLE brands
    ADD COLUMN IF NOT EXISTS country_code       CHAR(2)       NULL COMMENT 'ISO 3166-1 alpha-2 del país de registro'   AFTER mapita_id,
    ADD COLUMN IF NOT EXISTS language_code      CHAR(5)       NULL COMMENT 'BCP 47 del idioma principal de la marca'   AFTER country_code,
    ADD COLUMN IF NOT EXISTS currency_code      CHAR(3)       NULL COMMENT 'Moneda del valor_activo (ISO 4217)'        AFTER language_code,
    ADD COLUMN IF NOT EXISTS registry_authority VARCHAR(50)   NULL COMMENT 'Organismo registrador: INPI, USPTO, EUIPO, JPO…' AFTER currency_code,
    ADD COLUMN IF NOT EXISTS registry_number    VARCHAR(100)  NULL COMMENT 'Número de expediente genérico'             AFTER registry_authority,
    ADD COLUMN IF NOT EXISTS registry_date      DATE          NULL COMMENT 'Fecha de registro (genérico)'              AFTER registry_number,
    ADD COLUMN IF NOT EXISTS registry_expiry    DATE          NULL COMMENT 'Fecha de vencimiento (genérico)'           AFTER registry_date,
    ADD COLUMN IF NOT EXISTS registry_type      VARCHAR(20)   NULL COMMENT 'national|madrid_protocol|eu_trademark|us_federal' AFTER registry_expiry;

ALTER TABLE brands
    ADD INDEX IF NOT EXISTS idx_brands_country_code (country_code);

-- ─────────────────────────────────────────────────────────────
-- 3. Tabla INDUSTRIES
-- ─────────────────────────────────────────────────────────────
ALTER TABLE industries
    ADD COLUMN IF NOT EXISTS country_code   CHAR(2)     NULL COMMENT 'ISO 3166-1 alpha-2'                         AFTER country,
    ADD COLUMN IF NOT EXISTS language_code  CHAR(5)     NULL COMMENT 'BCP 47 del idioma principal'                AFTER country_code,
    ADD COLUMN IF NOT EXISTS currency_code  CHAR(3)     NULL COMMENT 'ISO 4217 — moneda de referencia'            AFTER language_code,
    ADD COLUMN IF NOT EXISTS nace_code      VARCHAR(20) NULL COMMENT 'Clasificador NACE Rev. 2 (Europa)'          AFTER isic_code,
    ADD COLUMN IF NOT EXISTS ciiu_code      VARCHAR(20) NULL COMMENT 'Clasificador CIIU/ISIC Rev. 4 (OIT/LATAM)'  AFTER nace_code;

ALTER TABLE industries
    ADD INDEX IF NOT EXISTS idx_industries_country_code (country_code);
