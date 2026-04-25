-- MIGRACIÓN 022: CERCA (inmuebles) y CONVOCAR (obra_de_arte)
-- Idempotente (IF NOT EXISTS)

-- 1. Tabla inmuebles (subitems de inmobiliaria)
CREATE TABLE IF NOT EXISTS inmuebles (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id     INT(11) NOT NULL COMMENT 'ID de la inmobiliaria',
    operacion       ENUM('venta','alquiler') NOT NULL DEFAULT 'venta',
    titulo          VARCHAR(255) NOT NULL,
    descripcion     TEXT NULL,
    precio          DECIMAL(15,2) NULL,
    moneda          VARCHAR(10) NOT NULL DEFAULT 'ARS',
    direccion       VARCHAR(500) NULL,
    lat             DECIMAL(10,7) NULL,
    lng             DECIMAL(10,7) NULL,
    foto_url        VARCHAR(500) NULL,
    contacto        VARCHAR(255) NULL,
    activo          TINYINT(1) NOT NULL DEFAULT 1,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_inm_business (business_id),
    KEY idx_inm_operacion (operacion),
    KEY idx_inm_activo (activo),
    CONSTRAINT fk_inm_business
        FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Columnas para obra_de_arte en businesses
ALTER TABLE businesses
    ADD COLUMN IF NOT EXISTS oda_descripcion_proyecto TEXT NULL
    COMMENT 'Descripcion del proyecto (obra_de_arte)';

ALTER TABLE businesses
    ADD COLUMN IF NOT EXISTS oda_requisitos TEXT NULL
    COMMENT 'Requisitos para participar (obra_de_arte)';

ALTER TABLE businesses
    ADD COLUMN IF NOT EXISTS oda_roles_buscados TEXT NULL
    COMMENT 'JSON array de roles que busca (obra_de_arte)';

-- 3. Tabla convocatorias
CREATE TABLE IF NOT EXISTS convocatorias (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id         INT(11) NOT NULL COMMENT 'Negocio OBRA DE ARTE convocante',
    user_id             INT(11) NOT NULL COMMENT 'Usuario titular',
    fecha_inicio        DATETIME NOT NULL,
    fecha_fin           DATETIME NOT NULL,
    roles_requeridos    TEXT NOT NULL COMMENT 'JSON array de business_type roles',
    estado              ENUM('activa','cerrada','cancelada') NOT NULL DEFAULT 'activa',
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_conv_business (business_id),
    KEY idx_conv_user (user_id),
    KEY idx_conv_estado (estado),
    CONSTRAINT fk_conv_business
        FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    CONSTRAINT fk_conv_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabla convocatoria_destinatarios
CREATE TABLE IF NOT EXISTS convocatoria_destinatarios (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    convocatoria_id BIGINT UNSIGNED NOT NULL,
    business_id     INT(11) NOT NULL COMMENT 'Negocio/servicio convocado',
    notificado_wt   TINYINT(1) NOT NULL DEFAULT 0,
    notificado_mail TINYINT(1) NOT NULL DEFAULT 0,
    leido_en        DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_conv_dest (convocatoria_id, business_id),
    KEY idx_cd_business (business_id),
    CONSTRAINT fk_cd_conv
        FOREIGN KEY (convocatoria_id) REFERENCES convocatorias(id) ON DELETE CASCADE,
    CONSTRAINT fk_cd_business
        FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
