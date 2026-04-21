-- ============================================================
-- MIGRACIÓN 010: Sistema de moderación y seguridad
-- Ejecutar UNA SOLA VEZ en producción
-- Todas las sentencias son idempotentes (IF NOT EXISTS)
-- ============================================================

-- ─────────────────────────────────────────────────────────────
-- 1. TABLA content_reports
--    Permite a los usuarios reportar contenido inapropiado
--    (reseñas, negocios, noticias, etc.)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS content_reports (
    id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    reporter_user_id    INT             NULL,
    reporter_ip         VARCHAR(45)     NULL,
    content_type        VARCHAR(30)     NOT NULL COMMENT 'review|business|noticia|evento|oferta|trivia|encuesta|transmision',
    content_id          INT UNSIGNED    NOT NULL,
    reason              VARCHAR(60)     NOT NULL COMMENT 'spam|inappropriate|fake|harassment|other',
    description         TEXT            NULL,
    status              ENUM('pending','reviewing','resolved','dismissed')
                                        NOT NULL DEFAULT 'pending',
    resolved_by         INT             NULL     COMMENT 'user_id del admin que resolvió',
    resolved_at         TIMESTAMP       NULL     DEFAULT NULL,
    resolution_note     TEXT            NULL,
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_reports_status         (status),
    KEY idx_reports_content        (content_type, content_id),
    KEY idx_reports_reporter       (reporter_user_id),
    KEY idx_reports_created        (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- 2. TABLA audit_log
--    Registra acciones sensibles de administradores y usuarios
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS audit_log (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id         INT             NULL,
    username        VARCHAR(100)    NULL,
    action          VARCHAR(60)     NOT NULL COMMENT 'create|update|delete|login|logout|resolve_report|...',
    entity_type     VARCHAR(40)     NULL,
    entity_id       INT             NULL,
    details         TEXT            NULL     COMMENT 'JSON con datos adicionales',
    ip              VARCHAR(45)     NULL,
    user_agent      VARCHAR(255)    NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_user    (user_id),
    KEY idx_audit_action  (action),
    KEY idx_audit_entity  (entity_type, entity_id),
    KEY idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- 3. TABLA rate_limit_log
--    Registro liviano para rate limiting por IP + endpoint
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS rate_limit_log (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    ip          VARCHAR(45)     NOT NULL,
    endpoint    VARCHAR(100)    NOT NULL,
    hit_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_rl_ip_endpoint (ip, endpoint, hit_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- FIN DE MIGRACIÓN 010
-- ─────────────────────────────────────────────────────────────
