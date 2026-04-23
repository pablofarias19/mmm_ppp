-- ============================================================
-- MIGRACIÓN 016: Sistema de CONSULTAS MASIVAS
-- 3 modos: masiva (geo), general (servicios habilitados),
--           global_proveedor (por rubro P), envio (transportistas)
-- Idempotente (IF NOT EXISTS / IF EXISTS en columnas)
-- Ejecutar DESPUÉS de todas las migraciones anteriores.
-- ============================================================

-- ──────────────────────────────────────────────────────────────
-- 1. Columna es_proveedor en businesses
--    1 = negocio marcado como Proveedor "P"
--    Solo aplica a negocios comerciales/industriales
-- ──────────────────────────────────────────────────────────────
ALTER TABLE businesses
    ADD COLUMN IF NOT EXISTS es_proveedor TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '1 = negocio marcado como Proveedor (P); solo negocios comerciales/industriales';

-- ──────────────────────────────────────────────────────────────
-- 2. Columna consulta_habilitada en businesses
--    Admin designa cuáles servicios especiales pueden recibir
--    la CONSULTA GENERAL (agente_inpi, inmobiliaria, etc.)
-- ──────────────────────────────────────────────────────────────
ALTER TABLE businesses
    ADD COLUMN IF NOT EXISTS consulta_habilitada TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'Admin designa: 1 = habilitado para recibir CONSULTA GENERAL (servicios especiales)';

-- ──────────────────────────────────────────────────────────────
-- 3. Tabla consultas_masivas — hilo maestro de cada consulta
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS consultas_masivas (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT(11)         NOT NULL COMMENT 'Usuario que origina la consulta',
    tipo       ENUM('masiva','general','global_proveedor','envio') NOT NULL
               COMMENT 'masiva=geo+todos; general=servicios habilitados; global_proveedor=rubro P; envio=transportistas geo',
    rubro      VARCHAR(100)    NULL     COMMENT 'Para tipo=global_proveedor: business_type destino',
    geo_bounds JSON            NULL     COMMENT '{north,south,east,west} — para masiva y envio',
    texto      VARCHAR(500)    NOT NULL COMMENT 'Texto de la consulta enviada',
    created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_cm_user       (user_id),
    KEY idx_cm_tipo       (tipo),
    KEY idx_cm_created_at (created_at),
    CONSTRAINT fk_cm_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 4. Tabla consultas_destinatarios — qué negocios recibieron
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS consultas_destinatarios (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    consulta_id BIGINT UNSIGNED NOT NULL,
    business_id INT(11)         NOT NULL,
    notificado  TINYINT(1)      NOT NULL DEFAULT 0,
    leido_en    DATETIME        NULL     COMMENT 'Cuándo el propietario del negocio lo leyó',

    PRIMARY KEY (id),
    UNIQUE KEY uq_cd_consulta_negocio (consulta_id, business_id),
    KEY idx_cd_negocio  (business_id),
    CONSTRAINT fk_cd_consulta
        FOREIGN KEY (consulta_id) REFERENCES consultas_masivas(id) ON DELETE CASCADE,
    CONSTRAINT fk_cd_negocio
        FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 5. Tabla consultas_respuestas — respuestas de cada negocio
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS consultas_respuestas (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    consulta_id BIGINT UNSIGNED NOT NULL,
    business_id INT(11)         NOT NULL COMMENT 'Negocio que responde',
    user_id     INT(11)         NOT NULL COMMENT 'Propietario/responsable que escribe la respuesta',
    texto       VARCHAR(500)    NOT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_cr_consulta    (consulta_id),
    KEY idx_cr_business    (business_id),
    KEY idx_cr_created_at  (created_at),
    CONSTRAINT fk_cr_consulta
        FOREIGN KEY (consulta_id) REFERENCES consultas_masivas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
