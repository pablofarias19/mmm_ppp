-- ============================================================
-- Módulo DISPONIBLES — tablas nuevas + columna en businesses
-- Idempotente (IF NOT EXISTS en tablas y columnas)
-- ============================================================

-- Columna para activar el módulo por negocio
ALTER TABLE businesses
    ADD COLUMN IF NOT EXISTS disponibles_activo TINYINT(1) NOT NULL DEFAULT 0 AFTER oferta_activa_id;

-- ──────────────────────────────────────────────────────────────
-- Tabla: disponibles_items
-- Cada fila es un ítem de oferta/disponibilidad del negocio
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS disponibles_items (
    id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    business_id         INT             NOT NULL,
    -- Precio: NULL = "a definir", valor numérico = precio fijo
    precio              DECIMAL(10,2)   NULL        COMMENT 'NULL = a definir',
    precio_a_definir    TINYINT(1)      NOT NULL DEFAULT 0,
    -- Bien: cantidad + descripción
    cantidad            SMALLINT UNSIGNED NULL,
    tipo_bien           VARCHAR(30)     NULL        COMMENT 'máx 30 chars',
    -- Disponibilidad
    disponible_desde    DATE            NULL,
    disponible_hasta    DATE            NULL,
    horario_inicio      TIME            NULL,
    horario_fin         TIME            NULL,
    -- Servicio
    servicio            VARCHAR(45)     NULL        COMMENT 'máx 45 chars',
    -- Control
    activo              TINYINT(1)      NOT NULL DEFAULT 1,
    orden               SMALLINT        NOT NULL DEFAULT 0,
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_disp_business (business_id),
    KEY idx_disp_activo (business_id, activo),
    CONSTRAINT fk_dispitems_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- Tabla: disponibles_solicitudes
-- Cabecera de cada solicitud de un usuario
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS disponibles_solicitudes (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    business_id     INT             NOT NULL,
    user_id         INT             NULL        COMMENT 'NULL si el usuario no está registrado',
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

-- ──────────────────────────────────────────────────────────────
-- Tabla: disponibles_solicitud_items
-- Detalle de selección (sí/no) por ítem en cada solicitud
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS disponibles_solicitud_items (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    solicitud_id    INT UNSIGNED    NOT NULL,
    item_id         INT UNSIGNED    NOT NULL,
    seleccionado    TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '1=sí, 0=no',
    PRIMARY KEY (id),
    UNIQUE KEY uq_sol_item (solicitud_id, item_id),
    KEY idx_dispsolitem_item (item_id),
    CONSTRAINT fk_dispsolitem_sol  FOREIGN KEY (solicitud_id) REFERENCES disponibles_solicitudes(id) ON DELETE CASCADE,
    CONSTRAINT fk_dispsolitem_item FOREIGN KEY (item_id)      REFERENCES disponibles_items(id)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
