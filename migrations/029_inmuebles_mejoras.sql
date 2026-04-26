-- ============================================================
-- MIGRACIÓN 029: Mejoras módulo Inmuebles (Inmobiliarias)
-- Idempotente (IF NOT EXISTS / IF EXISTS / ON DUPLICATE KEY)
-- ============================================================

-- ── 1. Columnas adicionales en inmuebles ────────────────────────────────────
-- IMPORTANTE: los valores del ENUM deben coincidir con INM_TIPOS en api/inmuebles.php
ALTER TABLE inmuebles
    ADD COLUMN IF NOT EXISTS tipo            ENUM('casa','departamento','lote','proyecto','local','oficina')
                                             NOT NULL DEFAULT 'casa'
        COMMENT 'Subcategoría del inmueble',
    ADD COLUMN IF NOT EXISTS financiado      TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1 = acepta financiación',
    ADD COLUMN IF NOT EXISTS ambientes       TINYINT UNSIGNED NULL
        COMMENT 'Cantidad de ambientes (NULL = no aplica)',
    ADD COLUMN IF NOT EXISTS superficie_m2   DECIMAL(10,2) UNSIGNED NULL
        COMMENT 'Superficie en m²',
    ADD INDEX  IF NOT EXISTS idx_inm_tipo    (tipo),
    ADD INDEX  IF NOT EXISTS idx_inm_lat_lng (lat, lng);

-- ── 2. Tabla adjuntos por inmueble (planos, proyecto de inversión, fotos) ────
CREATE TABLE IF NOT EXISTS inmueble_adjuntos (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    inmueble_id     BIGINT UNSIGNED NOT NULL,
    tipo_adjunto    ENUM('plano','proyecto','foto') NOT NULL DEFAULT 'foto'
        COMMENT 'Tipo de archivo adjunto',
    url             VARCHAR(500)    NOT NULL
        COMMENT 'Ruta relativa o URL del archivo',
    nombre          VARCHAR(255)    NULL
        COMMENT 'Nombre descriptivo del adjunto',
    mime_type       VARCHAR(100)    NULL,
    file_size       INT UNSIGNED    NULL
        COMMENT 'Tamaño en bytes',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ia_inmueble (inmueble_id),
    KEY idx_ia_tipo     (tipo_adjunto),
    CONSTRAINT fk_ia_inmueble
        FOREIGN KEY (inmueble_id) REFERENCES inmuebles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Adjuntos (planos, proyectos) por inmueble';

-- ── 3. Columnas en businesses para inmobiliarias ────────────────────────────
ALTER TABLE businesses
    ADD COLUMN IF NOT EXISTS inmuebles_max       INT NULL
        COMMENT 'Override: máx inmuebles activos. NULL = usar default por tipo o global (10)',
    ADD COLUMN IF NOT EXISTS inmuebles_destacado TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1 = inmobiliaria destacada: mayor visibilidad en CERCA',
    ADD INDEX  IF NOT EXISTS idx_businesses_inm_dest (inmuebles_destacado);

-- ── 4. Columna inmuebles_max_default en business_type_limits ─────────────────
ALTER TABLE business_type_limits
    ADD COLUMN IF NOT EXISTS inmuebles_max_default INT NOT NULL DEFAULT 10
        COMMENT 'Máximo de inmuebles activos para todos los negocios de este tipo';

-- ── FIN DE MIGRACIÓN 029 ─────────────────────────────────────────────────────
