-- ============================================================
-- Módulo WT Canales Selectivos — preferencias de usuario y bloqueos
-- Idempotente (IF NOT EXISTS en tablas y columnas)
-- ============================================================

-- ──────────────────────────────────────────────────────────────
-- Tabla: wt_user_preferences
-- Preferencias WT por usuario registrado
--   wt_mode:
--     'open'      → acepta mensajes de cualquier usuario (comportamiento por defecto)
--     'selective' → solo acepta mensajes de usuarios con al menos un área en común
--     'closed'    → WT deshabilitado; nadie puede enviarle mensajes
--   areas: array JSON de slugs de áreas elegidas (solo relevante en modo 'selective')
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS wt_user_preferences (
    user_id    INT UNSIGNED NOT NULL,
    wt_mode    ENUM('open','selective','closed') NOT NULL DEFAULT 'open',
    areas      JSON NULL COMMENT 'Array de slugs de áreas, usado cuando wt_mode=selective',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    CONSTRAINT fk_wt_prefs_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- Tabla: wt_user_blocks
-- Bloqueos mutuos entre usuarios en el sistema WT
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS wt_user_blocks (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    blocker_user_id  INT UNSIGNED NOT NULL,
    blocked_user_id  INT UNSIGNED NOT NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_wt_block (blocker_user_id, blocked_user_id),
    KEY idx_wt_block_blocker (blocker_user_id),
    KEY idx_wt_block_blocked (blocked_user_id),
    CONSTRAINT fk_wt_block_blocker
        FOREIGN KEY (blocker_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_wt_block_blocked
        FOREIGN KEY (blocked_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
