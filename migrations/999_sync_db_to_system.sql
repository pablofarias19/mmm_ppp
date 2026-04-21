-- ============================================================
-- MIGRACIÓN: Sincronizar base de datos con el sistema actual
-- Ejecutar UNA SOLA VEZ en u580580751_mapita
-- Todas las sentencias son idempotentes (IF NOT EXISTS / IF EXISTS)
-- ============================================================

-- ─────────────────────────────────────────────────────────────
-- 1. TABLA remates
--    Usada por: api/remates.php, models/Business.php
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS remates (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id     INT NOT NULL,
    titulo          VARCHAR(255) NULL,
    descripcion     TEXT NULL,
    fecha_inicio    DATETIME NOT NULL,
    fecha_fin       DATETIME NULL,
    fecha_cierre    DATETIME NULL,
    activo          TINYINT(1) NOT NULL DEFAULT 1,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_remates_business (business_id),
    KEY idx_remates_activo_fechas (activo, fecha_inicio, fecha_fin, fecha_cierre),
    CONSTRAINT fk_remates_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- 2. TABLA vehiculos_venta
--    Usada por: api/vehiculos.php, models/Business.php
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS vehiculos_venta (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id     INT NOT NULL,
    tipo_vehiculo   VARCHAR(30) NULL,
    marca           VARCHAR(100) NULL,
    modelo          VARCHAR(120) NULL,
    anio            SMALLINT NULL,
    km              INT NULL,
    precio          DECIMAL(14,2) NULL,
    contacto        VARCHAR(255) NULL,
    activo          TINYINT(1) NOT NULL DEFAULT 1,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_vehiculos_business (business_id),
    KEY idx_vehiculos_tipo (tipo_vehiculo),
    CONSTRAINT fk_vehiculos_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- 3. TABLA entidad_relaciones  +  columnas mapita_id
--    Usada por: api/relaciones.php
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS entidad_relaciones (
    id                      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    source_entity_type      VARCHAR(20) NOT NULL,
    source_entity_id        INT NOT NULL,
    source_mapita_id        VARCHAR(64) NULL,
    target_entity_type      VARCHAR(20) NOT NULL,
    target_entity_id        INT NOT NULL,
    target_mapita_id        VARCHAR(64) NULL,
    relation_type           VARCHAR(50) NOT NULL DEFAULT 'relacionado',
    descripcion             VARCHAR(255) NULL,
    activo                  TINYINT(1) NOT NULL DEFAULT 1,
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_rel_source (source_entity_type, source_entity_id),
    KEY idx_rel_target (target_entity_type, target_entity_id),
    KEY idx_rel_source_mapita (source_mapita_id),
    KEY idx_rel_target_mapita (target_mapita_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- mapita_id en businesses
ALTER TABLE businesses
    ADD COLUMN IF NOT EXISTS mapita_id VARCHAR(64) NULL AFTER style;

-- mapita_id en brands
ALTER TABLE brands
    ADD COLUMN IF NOT EXISTS mapita_id VARCHAR(64) NULL;

-- mapita_id en marcas
ALTER TABLE marcas
    ADD COLUMN IF NOT EXISTS mapita_id VARCHAR(64) NULL;

-- mapita_id en eventos
ALTER TABLE eventos
    ADD COLUMN IF NOT EXISTS mapita_id VARCHAR(64) NULL;

-- ─────────────────────────────────────────────────────────────
-- 4. Columnas de oferta destacada
--    Usada por: api/ofertas.php, models/Business.php
-- ─────────────────────────────────────────────────────────────
ALTER TABLE businesses
    ADD COLUMN IF NOT EXISTS oferta_activa_id INT UNSIGNED NULL AFTER mapita_id;

ALTER TABLE ofertas
    ADD COLUMN IF NOT EXISTS es_destacada TINYINT(1) NOT NULL DEFAULT 0;

-- Índices (se agregan solo si no existen — compatible con MySQL 8.0+/MariaDB 10.4+)
-- Descomentar si la versión de MySQL no soporta IF NOT EXISTS en índices:
-- ALTER TABLE businesses ADD INDEX idx_business_oferta_activa (oferta_activa_id);
-- ALTER TABLE ofertas ADD INDEX idx_ofertas_destacada (business_id, activo, es_destacada);
ALTER TABLE businesses
    ADD INDEX IF NOT EXISTS idx_business_oferta_activa (oferta_activa_id);

ALTER TABLE ofertas
    ADD INDEX IF NOT EXISTS idx_ofertas_destacada (business_id, activo, es_destacada);

-- ─────────────────────────────────────────────────────────────
-- 5. TABLAS wt_messages y wt_presence (Walkie Talkie)
--    Usada por: api/wt.php
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS wt_messages (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    entity_type ENUM('negocio','marca','evento','encuesta') NOT NULL,
    entity_id   BIGINT UNSIGNED NOT NULL,
    user_id     BIGINT UNSIGNED NULL,
    user_name   VARCHAR(80) NOT NULL DEFAULT 'Invitado',
    sender_key  VARCHAR(120) NOT NULL,
    message     VARCHAR(140) NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_wt_entity_time (entity_type, entity_id, created_at),
    KEY idx_wt_sender_time (sender_key, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wt_presence (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    entity_type ENUM('negocio','marca','evento','encuesta') NOT NULL,
    entity_id   BIGINT UNSIGNED NOT NULL,
    user_id     BIGINT UNSIGNED NULL,
    user_name   VARCHAR(80) NOT NULL DEFAULT 'Invitado',
    sender_key  VARCHAR(120) NOT NULL,
    last_seen   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_wt_presence (entity_type, entity_id, sender_key),
    KEY idx_wt_presence_seen (entity_type, entity_id, last_seen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- 6. Columnas faltantes en brand_gallery
--    Usada por: models/BrandGallery.php
--    El modelo inserta/lee: filename, orden, created_at
--    El dump actual tiene: file_path, uploaded_at (sin filename/orden)
-- ─────────────────────────────────────────────────────────────
ALTER TABLE brand_gallery
    ADD COLUMN IF NOT EXISTS filename VARCHAR(255) NULL AFTER file_path,
    ADD COLUMN IF NOT EXISTS orden INT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;

-- Poblar 'filename' con el valor de 'file_path' donde filename esté vacío
UPDATE brand_gallery SET filename = file_path WHERE filename IS NULL;

-- ─────────────────────────────────────────────────────────────
-- 7. Columna 'descripcion' en tabla marcas
--    Usada por: api/og_image.php (query: SELECT ... descripcion FROM marcas)
--    La tabla marcas tiene 'extended_description' pero no 'descripcion' corta
-- ─────────────────────────────────────────────────────────────
ALTER TABLE marcas
    ADD COLUMN IF NOT EXISTS descripcion TEXT NULL AFTER extended_description;

-- ─────────────────────────────────────────────────────────────
-- 8. Módulo DISPONIBLES
--    Usada por: api/disponibles.php, api/disponibles_solicitudes.php
--    business/panel_disponibles.php
-- ─────────────────────────────────────────────────────────────

-- Columna flag en businesses
ALTER TABLE businesses
    ADD COLUMN IF NOT EXISTS disponibles_activo TINYINT(1) NOT NULL DEFAULT 0 AFTER oferta_activa_id;

-- Tabla de ítems disponibles
CREATE TABLE IF NOT EXISTS disponibles_items (
    id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    business_id         INT             NOT NULL,
    precio              DECIMAL(10,2)   NULL,
    precio_a_definir    TINYINT(1)      NOT NULL DEFAULT 0,
    cantidad            SMALLINT UNSIGNED NULL,
    tipo_bien           VARCHAR(30)     NULL,
    disponible_desde    DATE            NULL,
    disponible_hasta    DATE            NULL,
    horario_inicio      TIME            NULL,
    horario_fin         TIME            NULL,
    servicio            VARCHAR(45)     NULL,
    activo              TINYINT(1)      NOT NULL DEFAULT 1,
    orden               SMALLINT        NOT NULL DEFAULT 0,
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_disp_business (business_id),
    KEY idx_disp_activo (business_id, activo),
    CONSTRAINT fk_dispitems_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de solicitudes
CREATE TABLE IF NOT EXISTS disponibles_solicitudes (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    business_id     INT             NOT NULL,
    user_id         INT             NULL,
    email           VARCHAR(255)    NOT NULL,
    estado          ENUM('pendiente','confirmada','desistida') NOT NULL DEFAULT 'pendiente',
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_dispsol_business (business_id),
    KEY idx_dispsol_user (user_id),
    KEY idx_dispsol_estado (business_id, estado),
    CONSTRAINT fk_dispsol_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de detalle de selección por solicitud
CREATE TABLE IF NOT EXISTS disponibles_solicitud_items (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    solicitud_id    INT UNSIGNED    NOT NULL,
    item_id         INT UNSIGNED    NOT NULL,
    seleccionado    TINYINT(1)      NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_sol_item (solicitud_id, item_id),
    KEY idx_dispsolitem_item (item_id),
    CONSTRAINT fk_dispsolitem_sol  FOREIGN KEY (solicitud_id) REFERENCES disponibles_solicitudes(id) ON DELETE CASCADE,
    CONSTRAINT fk_dispsolitem_item FOREIGN KEY (item_id)      REFERENCES disponibles_items(id)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- FIN DE MIGRACIÓN
-- Nota: ejecutar también migrations/010_moderation.sql
--       para el sistema de moderación y seguridad.
-- ─────────────────────────────────────────────────────────────
