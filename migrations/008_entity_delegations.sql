-- Delegaciones por entidad y transferencias de titularidad
-- Idempotente: usa CREATE TABLE IF NOT EXISTS

CREATE TABLE IF NOT EXISTS business_delegations (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id INT             NOT NULL,
    user_id     INT             NOT NULL,
    role        ENUM('admin')   NOT NULL DEFAULT 'admin',
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by  INT             NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_business_delegate (business_id, user_id),
    KEY idx_business_delegations_business (business_id),
    KEY idx_business_delegations_user (user_id),
    CONSTRAINT fk_business_delegations_business
        FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    CONSTRAINT fk_business_delegations_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_business_delegations_created_by
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS brand_delegations (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    brand_id    INT             NOT NULL,
    user_id     INT             NOT NULL,
    role        ENUM('admin')   NOT NULL DEFAULT 'admin',
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by  INT             NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_brand_delegate (brand_id, user_id),
    KEY idx_brand_delegations_brand (brand_id),
    KEY idx_brand_delegations_user (user_id),
    CONSTRAINT fk_brand_delegations_brand
        FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE,
    CONSTRAINT fk_brand_delegations_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_brand_delegations_created_by
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ownership_transfers (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    entity_type   ENUM('business','brand') NOT NULL,
    entity_id     INT             NOT NULL,
    from_user_id  INT             NOT NULL,
    to_user_id    INT             NOT NULL,
    status        ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
    created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    accepted_at   DATETIME        NULL DEFAULT NULL,
    rejected_at   DATETIME        NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_ownership_transfers_entity (entity_type, entity_id),
    KEY idx_ownership_transfers_status (status),
    KEY idx_ownership_transfers_to_user (to_user_id, status),
    CONSTRAINT fk_ownership_transfers_from_user
        FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ownership_transfers_to_user
        FOREIGN KEY (to_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
