-- ============================================================
-- MIGRACIÓN 031: Tabla de configuraciones globales del sistema
-- Idempotente (IF NOT EXISTS / ON DUPLICATE KEY)
-- ============================================================

-- ── 1. Tabla de configuraciones clave-valor ──────────────────────────────────
CREATE TABLE IF NOT EXISTS mapita_settings (
    setting_key   VARCHAR(100)  NOT NULL
        COMMENT 'Clave única de la configuración',
    setting_value TEXT          NOT NULL DEFAULT ''
        COMMENT 'Valor de la configuración (se convierte al tipo necesario en código)',
    updated_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Configuraciones globales del sistema administradas desde el panel admin';

-- ── 2. Valores por defecto ───────────────────────────────────────────────────
-- global_icon_boost: multiplicador global del tamaño de iconos en el mapa
--   1.0 = tamaño normal; 1.5 = 50% más grande; 0.8 = 20% más pequeño
INSERT INTO mapita_settings (setting_key, setting_value)
VALUES ('global_icon_boost', '1.0')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- ── FIN DE MIGRACIÓN 031 ─────────────────────────────────────────────────────
