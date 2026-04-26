-- ============================================================
-- MIGRACIÓN 028: Capacidades administrativas avanzadas
-- Ejecutar UNA SOLA VEZ en la base de datos
-- Todas las sentencias son idempotentes (IF NOT EXISTS)
-- ============================================================

-- ── 1. users: agregar campos de titular (nombre y apellido) ──────────────────
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS first_name VARCHAR(100) NULL
        COMMENT 'Nombre del titular de la cuenta',
    ADD COLUMN IF NOT EXISTS last_name  VARCHAR(100) NULL
        COMMENT 'Apellido del titular de la cuenta',
    ADD INDEX IF NOT EXISTS idx_users_first_name (first_name),
    ADD INDEX IF NOT EXISTS idx_users_last_name  (last_name);

-- ── 2. businesses: límite de imágenes + visibilidad por zoom + premium ────────
ALTER TABLE businesses
    ADD COLUMN IF NOT EXISTS images_max          INT NULL
        COMMENT 'Override: máx imágenes de galería. NULL = usar default por tipo o global (2)',
    ADD COLUMN IF NOT EXISTS visibility_min_zoom TINYINT UNSIGNED NULL DEFAULT 12
        COMMENT 'Zoom mínimo del mapa para que este negocio sea visible. NULL = usar default del tipo.',
    ADD COLUMN IF NOT EXISTS is_premium          TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1 = negocio premium: visible desde zoom bajo (ej. 3)',
    ADD INDEX IF NOT EXISTS idx_businesses_visibility_zoom (visibility_min_zoom),
    ADD INDEX IF NOT EXISTS idx_businesses_premium         (is_premium);

-- ─────────────────────────────────────────────────────────────
-- 3. Tabla business_type_limits: defaults por tipo de negocio
--    Usada por: admin/limits/dashboard.php,
--               api/upload_business_gallery.php
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS business_type_limits (
    business_type           VARCHAR(80)      NOT NULL,
    images_max_default      INT              NOT NULL DEFAULT 2
        COMMENT 'Máximo de imágenes para todos los negocios de este tipo',
    visibility_min_zoom_default TINYINT UNSIGNED NOT NULL DEFAULT 12
        COMMENT 'Zoom mínimo por defecto para este tipo de negocio',
    updated_at              TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                             ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (business_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Límites y configuraciones por tipo de negocio (gestionados desde admin)';

-- ─────────────────────────────────────────────────────────────
-- FIN DE MIGRACIÓN 028
-- Ejecutar también las migraciones anteriores si no se han
-- aplicado aún (001 → 027).
-- ─────────────────────────────────────────────────────────────
