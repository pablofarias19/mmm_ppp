-- Mapita N4: Vehículos en venta vinculados a negocios
CREATE TABLE IF NOT EXISTS vehiculos_venta (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id     INT NOT NULL,
    tipo_vehiculo   VARCHAR(30) NULL,
    marca           VARCHAR(100) NULL,
    modelo          VARCHAR(120) NULL,
    anio            SMALLINT NULL,
    km              INT NULL,
    precio          DECIMAL(14,2) NULL,
    contacto        VARCHAR(255) NULL,
    activo          TINYINT(1) NOT NULL DEFAULT 1,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_vehiculos_business (business_id),
    KEY idx_vehiculos_tipo (tipo_vehiculo),
    CONSTRAINT fk_vehiculos_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
