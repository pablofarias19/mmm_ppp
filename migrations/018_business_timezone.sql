-- ============================================================
-- MIGRACIÓN 018: Zona horaria por negocio
-- Permite que cada negocio indique su timezone IANA para
-- calcular correctamente apertura/cierre independientemente
-- de dónde esté el servidor o el visitante.
-- ============================================================

-- Agregar columna timezone a la tabla comercios.
-- DEFAULT: America/Argentina/Buenos_Aires para compatibilidad
-- con todos los registros existentes (ninguno pierde datos).
ALTER TABLE comercios
    ADD COLUMN IF NOT EXISTS timezone VARCHAR(64) NOT NULL
    DEFAULT 'America/Argentina/Buenos_Aires'
    AFTER dias_cierre;
