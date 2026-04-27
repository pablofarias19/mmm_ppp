-- ============================================================
-- MIGRACIÓN 033: Descarte de consultas recibidas
--
-- Agrega dismissed_at a consultas_destinatarios para que el
-- destinatario pueda descartar/cerrar una consulta recibida
-- sin necesidad de responderla.
--
-- Idempotente (IF NOT EXISTS).
-- Ejecutar DESPUÉS de migración 032.
-- ============================================================

-- Columna de descarte para el destinatario
ALTER TABLE consultas_destinatarios
    ADD COLUMN IF NOT EXISTS dismissed_at DATETIME NULL
        COMMENT 'Cuándo el destinatario descartó/cerró esta consulta recibida';

-- Índice para filtrar eficientemente las no descartadas
CREATE INDEX IF NOT EXISTS idx_cd_dismissed_at ON consultas_destinatarios (dismissed_at);
