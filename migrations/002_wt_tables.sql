-- WT MVP tables (Walkie Talkie): messages + presence

CREATE TABLE IF NOT EXISTS wt_messages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    entity_type ENUM('negocio','marca','evento','encuesta') NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    user_name VARCHAR(80) NOT NULL DEFAULT 'Invitado',
    sender_key VARCHAR(120) NOT NULL,
    message VARCHAR(140) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_wt_entity_time (entity_type, entity_id, created_at),
    KEY idx_wt_sender_time (sender_key, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wt_presence (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    entity_type ENUM('negocio','marca','evento','encuesta') NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    user_name VARCHAR(80) NOT NULL DEFAULT 'Invitado',
    sender_key VARCHAR(120) NOT NULL,
    last_seen DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_wt_presence (entity_type, entity_id, sender_key),
    KEY idx_wt_presence_seen (entity_type, entity_id, last_seen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
