-- ============================================================
-- Módulo SECTORES INDUSTRIALES — tabla independiente
-- Idempotente (CREATE TABLE IF NOT EXISTS)
-- ============================================================

CREATE TABLE IF NOT EXISTS industrial_sectors (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    name             VARCHAR(255)  NOT NULL,
    type             ENUM('mineria','energia','agro','infraestructura','inmobiliario','industrial') NOT NULL,
    subtype          VARCHAR(100)  NULL,
    geometry         JSON          NOT NULL COMMENT 'GeoJSON (Feature o Geometry)',
    status           ENUM('proyecto','activo','potencial') NOT NULL DEFAULT 'potencial',
    investment_level ENUM('bajo','medio','alto')           NOT NULL DEFAULT 'medio',
    risk_level       ENUM('bajo','medio','alto')           NOT NULL DEFAULT 'medio',
    jurisdiction     VARCHAR(255)  NULL,
    description      TEXT          NULL,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME      NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_is_type   (type),
    KEY idx_is_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
