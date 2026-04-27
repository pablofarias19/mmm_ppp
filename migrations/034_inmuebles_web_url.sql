-- ============================================================
-- MIGRACIÓN 034: Agregar web_url a inmuebles
-- Idempotente (ADD COLUMN IF NOT EXISTS)
-- ============================================================

-- ── 1. Campo web_url para el link externo del inmueble ───────────────────────
ALTER TABLE inmuebles
    ADD COLUMN IF NOT EXISTS web_url VARCHAR(500) NULL
        COMMENT 'URL externa del inmueble en la web de la inmobiliaria';

-- ── FIN DE MIGRACIÓN 034 ─────────────────────────────────────────────────────
