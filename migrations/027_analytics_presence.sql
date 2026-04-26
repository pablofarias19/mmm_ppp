-- ============================================================
-- MIGRACIÓN 027: Analytics Events + User Presence
-- Tablero de control interno (Admin Analytics Dashboard)
-- Todas las sentencias son idempotentes (IF NOT EXISTS)
-- Compatible con MySQL / MariaDB
-- ============================================================

-- ─────────────────────────────────────────────────────────────
-- 1. analytics_events  —  telemetría de interacciones
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS analytics_events (
    id          BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    created_at  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    event_type  VARCHAR(60)      NOT NULL,
    user_id     INT              NULL,
    visitor_id  VARCHAR(64)      NULL   COMMENT 'Cookie anónimo para visitantes no logueados',
    business_id INT              NULL,
    meta_json   TEXT             NULL   COMMENT 'Detalles adicionales: query, filtro, etc.',
    ip          VARCHAR(45)      NULL,
    user_agent  VARCHAR(512)     NULL,
    PRIMARY KEY (id),
    INDEX idx_ae_created_at  (created_at),
    INDEX idx_ae_event_type  (event_type),
    INDEX idx_ae_user_id     (user_id),
    INDEX idx_ae_business_id (business_id),
    INDEX idx_ae_visitor_id  (visitor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- 2. user_presence  —  presencia / heartbeat en tiempo real
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS user_presence (
    id           BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    user_id      INT              NULL,
    visitor_id   VARCHAR(64)      NULL,
    session_id   VARCHAR(128)     NULL,
    current_path VARCHAR(255)     NULL,
    last_seen_at DATETIME         NOT NULL,
    ip           VARCHAR(45)      NULL,
    user_agent   VARCHAR(512)     NULL,
    PRIMARY KEY (id),
    INDEX idx_up_user_id      (user_id),
    INDEX idx_up_visitor_id   (visitor_id),
    INDEX idx_up_session_id   (session_id),
    INDEX idx_up_last_seen_at (last_seen_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- FIN DE MIGRACIÓN 027
-- ─────────────────────────────────────────────────────────────
