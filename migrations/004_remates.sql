-- Mapita N3: Remates / Subastas
CREATE TABLE IF NOT EXISTS remates (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id     INT NOT NULL,
    titulo          VARCHAR(255) NULL,
    descripcion     TEXT NULL,
    fecha_inicio    DATETIME NOT NULL,
    fecha_fin       DATETIME NULL,
    fecha_cierre    DATETIME NULL,
    activo          TINYINT(1) NOT NULL DEFAULT 1,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_remates_business (business_id),
    KEY idx_remates_activo_fechas (activo, fecha_inicio, fecha_fin, fecha_cierre),
    CONSTRAINT fk_remates_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
