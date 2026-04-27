-- ============================================================
-- MIGRACIÓN 032: Ciclo de vida de consultas + zonas de influencia
--
-- 1. Agrega columnas de lifecycle a consultas_masivas:
--    status, answered_at, closed_at, closed_by
-- 2. Agrega influence_zones a businesses (para inmobiliarias)
-- 3. Agrega subtipo_transporte para negocios de transporte
--
-- Idempotente (IF NOT EXISTS / IF NOT EXISTS en columnas)
-- Ejecutar DESPUÉS de migración 031.
-- ============================================================

-- ──────────────────────────────────────────────────────────────
-- 1. Ciclo de vida de consultas_masivas
--    status: open (activa), answered (respondida), closed, archived
-- ──────────────────────────────────────────────────────────────
ALTER TABLE consultas_masivas
    ADD COLUMN IF NOT EXISTS status ENUM('open','answered','closed','archived')
        NOT NULL DEFAULT 'open'
        COMMENT 'Lifecycle: open=activa, answered=respondida, closed=cerrada, archived=archivada';

ALTER TABLE consultas_masivas
    ADD COLUMN IF NOT EXISTS answered_at DATETIME NULL
        COMMENT 'Cuándo fue respondida por primera vez';

ALTER TABLE consultas_masivas
    ADD COLUMN IF NOT EXISTS closed_at DATETIME NULL
        COMMENT 'Cuándo fue cerrada o archivada por el remitente';

ALTER TABLE consultas_masivas
    ADD COLUMN IF NOT EXISTS closed_by INT NULL
        COMMENT 'user_id que cerró la consulta';

-- Índice para facilitar filtros por status
CREATE INDEX IF NOT EXISTS idx_cm_status ON consultas_masivas (status);

-- ──────────────────────────────────────────────────────────────
-- 2. Zonas de influencia para negocios inmobiliarios
--    Texto libre de barrios/zonas que atiende la inmobiliaria.
--    Permite consultar inmobiliarias por zona sin depender de cercanía.
-- ──────────────────────────────────────────────────────────────
ALTER TABLE businesses
    ADD COLUMN IF NOT EXISTS influence_zones TEXT NULL
        COMMENT 'Zonas de influencia (barrios/zonas atendidas). Uso: inmobiliarias. Texto separado por comas.';

-- Índice FULLTEXT para búsqueda rápida por zona
ALTER TABLE businesses
    ADD FULLTEXT INDEX IF NOT EXISTS ft_influence_zones (influence_zones);

-- ──────────────────────────────────────────────────────────────
-- 3. Subtipo de transporte para negocios de transporte
--    Permite distinguir: envios / pasajeros / carga
--    y habilitar funciones específicas (Consulta Envío, etc.)
-- ──────────────────────────────────────────────────────────────
ALTER TABLE businesses
    ADD COLUMN IF NOT EXISTS transport_subtype ENUM('envios','pasajeros','carga') NULL
        COMMENT 'Subtipo de transporte: envios, pasajeros o carga. Solo para negocios de tipo transporte.';
