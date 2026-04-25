-- ============================================================
-- MIGRACIÓN 025: Permisos de publicación de ofertas por negocio
-- Ejecutar UNA SOLA VEZ en la base de datos
-- Todas las sentencias son idempotentes (IF NOT EXISTS)
-- ============================================================

-- ─────────────────────────────────────────────────────────────
-- 1. businesses: permiso y cupo de ofertas
-- ─────────────────────────────────────────────────────────────
ALTER TABLE businesses
    ADD COLUMN IF NOT EXISTS ofertas_permitidas TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1 = el negocio puede publicar sus propias ofertas; 0 = solo admin',
    ADD COLUMN IF NOT EXISTS ofertas_max INT NOT NULL DEFAULT 0
        COMMENT 'Máximo de ofertas activas permitidas (0 = sin límite, solo aplica si ofertas_permitidas=1)';

-- ─────────────────────────────────────────────────────────────
-- 2. Índice para filtrar negocios con permiso
-- ─────────────────────────────────────────────────────────────
ALTER TABLE businesses
    ADD INDEX IF NOT EXISTS idx_businesses_ofertas_permitidas (ofertas_permitidas);

-- ─────────────────────────────────────────────────────────────
-- FIN DE MIGRACIÓN 025
-- ─────────────────────────────────────────────────────────────
