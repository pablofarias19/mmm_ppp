-- ============================================================
-- Módulo BUSCO EMPLEADOS/AS — columnas en businesses + tabla job_applications
-- Idempotente (IF NOT EXISTS en tablas y columnas)
-- ============================================================

-- Columnas de oferta laboral en businesses
ALTER TABLE businesses
    ADD COLUMN IF NOT EXISTS job_offer_active      TINYINT(1)   NOT NULL DEFAULT 0                   AFTER disponibles_activo,
    ADD COLUMN IF NOT EXISTS job_offer_position    VARCHAR(255) NULL                                   AFTER job_offer_active,
    ADD COLUMN IF NOT EXISTS job_offer_description TEXT         NULL                                   AFTER job_offer_position,
    ADD COLUMN IF NOT EXISTS job_offer_url         VARCHAR(500) NULL COMMENT 'Link externo opcional'  AFTER job_offer_description;

-- ──────────────────────────────────────────────────────────────
-- Tabla: job_applications
-- Una postulación por usuario por negocio (UNIQUE user+business)
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS job_applications (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    business_id      INT           NOT NULL,
    user_id          INT           NOT NULL  COMMENT 'Login obligatorio — NOT NULL',
    applicant_name   VARCHAR(255)  NOT NULL,
    applicant_email  VARCHAR(255)  NOT NULL,
    applicant_phone  VARCHAR(50)   NULL,
    message          TEXT          NULL,
    estado           ENUM('pendiente','vista','aceptada','rechazada') NOT NULL DEFAULT 'pendiente',
    consent          TINYINT(1)    NOT NULL DEFAULT 1,
    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP     NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_jobapp_user_biz (business_id, user_id),
    KEY idx_jobapp_business (business_id),
    KEY idx_jobapp_user (user_id),
    KEY idx_jobapp_estado (business_id, estado),
    CONSTRAINT fk_jobapp_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
