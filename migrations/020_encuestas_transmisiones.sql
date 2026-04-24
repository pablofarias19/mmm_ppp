-- ============================================================
-- MIGRACIÓN 020: Mejoras Encuestas y Transmisiones en Vivo
-- Ejecutar UNA SOLA VEZ en la base de datos
-- Todas las sentencias son idempotentes (IF NOT EXISTS / IF EXISTS)
-- ============================================================

-- ─────────────────────────────────────────────────────────────
-- 1. preguntas_encuesta
--    Asegurar columnas necesarias para el flujo con opciones
-- ─────────────────────────────────────────────────────────────

-- Asegurar que existe la columna 'texto_pregunta'
ALTER TABLE preguntas_encuesta
    MODIFY COLUMN texto_pregunta TEXT NULL;

-- Asegurar que existe la columna 'tipo'
ALTER TABLE preguntas_encuesta
    ADD COLUMN IF NOT EXISTS tipo VARCHAR(30) NOT NULL DEFAULT 'opcion_multiple';

-- Asegurar que existe la columna 'opciones' (almacena las opciones separadas por coma)
ALTER TABLE preguntas_encuesta
    ADD COLUMN IF NOT EXISTS opciones TEXT NULL
        COMMENT 'Opciones de respuesta separadas por coma. Max 5 opciones.';

-- Asegurar que existe la columna 'orden'
ALTER TABLE preguntas_encuesta
    ADD COLUMN IF NOT EXISTS orden INT NOT NULL DEFAULT 1;

-- ─────────────────────────────────────────────────────────────
-- 2. transmisiones
--    Ventana de tiempo para la transmisión programada
-- ─────────────────────────────────────────────────────────────

ALTER TABLE transmisiones
    ADD COLUMN IF NOT EXISTS fecha_inicio DATETIME NULL
        COMMENT 'Fecha y hora de inicio programada de la transmisión',
    ADD COLUMN IF NOT EXISTS fecha_fin DATETIME NULL
        COMMENT 'Fecha y hora de fin programada de la transmisión';

ALTER TABLE transmisiones
    ADD INDEX IF NOT EXISTS idx_trans_ventana (activo, fecha_inicio, fecha_fin);

-- ─────────────────────────────────────────────────────────────
-- FIN DE MIGRACIÓN 020
-- ─────────────────────────────────────────────────────────────
