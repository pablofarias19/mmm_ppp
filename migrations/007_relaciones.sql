-- Mapita N6: IDs públicos + relaciones entre entidades
ALTER TABLE businesses
    ADD COLUMN IF NOT EXISTS mapita_id VARCHAR(64) NULL;

ALTER TABLE brands
    ADD COLUMN IF NOT EXISTS mapita_id VARCHAR(64) NULL;

-- Compatibilidad con instalaciones que usan tabla 'marcas'
ALTER TABLE marcas
    ADD COLUMN IF NOT EXISTS mapita_id VARCHAR(64) NULL;

ALTER TABLE eventos
    ADD COLUMN IF NOT EXISTS mapita_id VARCHAR(64) NULL;

CREATE TABLE IF NOT EXISTS entidad_relaciones (
    id                      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    source_entity_type      VARCHAR(20) NOT NULL,
    source_entity_id        INT NOT NULL,
    source_mapita_id        VARCHAR(64) NULL,
    target_entity_type      VARCHAR(20) NOT NULL,
    target_entity_id        INT NOT NULL,
    target_mapita_id        VARCHAR(64) NULL,
    relation_type           VARCHAR(50) NOT NULL DEFAULT 'relacionado',
    descripcion             VARCHAR(255) NULL,
    activo                  TINYINT(1) NOT NULL DEFAULT 1,
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_rel_source (source_entity_type, source_entity_id),
    KEY idx_rel_target (target_entity_type, target_entity_id),
    KEY idx_rel_source_mapita (source_mapita_id),
    KEY idx_rel_target_mapita (target_mapita_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
