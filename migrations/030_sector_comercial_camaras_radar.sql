-- ============================================================
-- Módulo SECTOR COMERCIAL + CÁMARAS/AGENCIAS + RADAR LEGAL
-- Idempotente (CREATE TABLE IF NOT EXISTS)
-- ============================================================

-- ── 1. Sectores Comerciales ──────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS commercial_sectors (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    name             VARCHAR(255)  NOT NULL,
    type             ENUM('retail','servicios','gastronomia','tecnologia','salud','educacion','finanzas','transporte','turismo','otro') NOT NULL,
    subtype          VARCHAR(100)  NULL,
    status           ENUM('proyecto','activo','potencial') NOT NULL DEFAULT 'potencial',
    jurisdiction     VARCHAR(255)  NULL,
    description      TEXT          NULL,
    radar_enabled    TINYINT(1)    NOT NULL DEFAULT 0 COMMENT '1 = Radar Legal habilitado',
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME      NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_cs_type   (type),
    KEY idx_cs_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar radar_enabled a industrial_sectors si no existe
ALTER TABLE industrial_sectors
    ADD COLUMN IF NOT EXISTS radar_enabled TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '1 = Radar Legal habilitado';

-- ── 2. Cámaras ───────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS chambers (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    name        VARCHAR(255)  NOT NULL,
    area        VARCHAR(150)  NOT NULL COMMENT 'Area tematica: energia, transporte, etc.',
    description TEXT          NULL,
    website     VARCHAR(500)  NULL,
    email       VARCHAR(255)  NULL,
    phone       VARCHAR(60)   NULL,
    status      ENUM('activa','inactiva') NOT NULL DEFAULT 'activa',
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME      NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ch_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. Agencias ──────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS agencies (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    name        VARCHAR(255)  NOT NULL,
    area        VARCHAR(150)  NOT NULL COMMENT 'Area: turismo, comercio exterior, etc.',
    description TEXT          NULL,
    website     VARCHAR(500)  NULL,
    email       VARCHAR(255)  NULL,
    phone       VARCHAR(60)   NULL,
    status      ENUM('activa','inactiva') NOT NULL DEFAULT 'activa',
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME      NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ag_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 4. Pivot: Camara <-> Sector ───────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS chamber_sector (
    chamber_id  INT UNSIGNED NOT NULL,
    sector_type ENUM('industrial','commercial') NOT NULL,
    sector_id   INT UNSIGNED NOT NULL,
    PRIMARY KEY (chamber_id, sector_type, sector_id),
    KEY idx_csec_sid (sector_type, sector_id),
    CONSTRAINT fk_cs_chamber FOREIGN KEY (chamber_id) REFERENCES chambers (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 5. Pivot: Agencia <-> Sector ──────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS agency_sector (
    agency_id   INT UNSIGNED NOT NULL,
    sector_type ENUM('industrial','commercial') NOT NULL,
    sector_id   INT UNSIGNED NOT NULL,
    PRIMARY KEY (agency_id, sector_type, sector_id),
    KEY idx_asec_sid (sector_type, sector_id),
    CONSTRAINT fk_as_agency FOREIGN KEY (agency_id) REFERENCES agencies (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 6. Lineas de Politica (documentos con metadata) ──────────────────────────

CREATE TABLE IF NOT EXISTS policy_lines (
    id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    source_type  ENUM('chamber','agency') NOT NULL,
    source_id    INT UNSIGNED  NOT NULL,
    title        VARCHAR(500)  NOT NULL,
    summary      TEXT          NULL,
    line_type    ENUM('propia','gobierno') NOT NULL DEFAULT 'propia',
    jurisdiction VARCHAR(255)  NULL,
    source_link  VARCHAR(1000) NULL,
    published_at DATE          NULL,
    valid_from   DATE          NULL,
    valid_until  DATE          NULL,
    tags         VARCHAR(500)  NULL,
    area         VARCHAR(150)  NULL,
    status       ENUM('vigente','vencida','derogada') NOT NULL DEFAULT 'vigente',
    created_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME      NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pl_source (source_type, source_id),
    KEY idx_pl_type   (line_type),
    KEY idx_pl_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 7. Competencias / Mapa de Facultades ─────────────────────────────────────

CREATE TABLE IF NOT EXISTS competencies (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    source_type ENUM('chamber','agency') NOT NULL,
    source_id   INT UNSIGNED  NOT NULL,
    role        ENUM('aprobar','rechazar','controlar','auditar','sancionar','dictamen','emitir','fiscalizar') NOT NULL,
    organism    VARCHAR(255)  NOT NULL,
    organ       VARCHAR(255)  NULL,
    responsible VARCHAR(255)  NULL,
    scope       TEXT          NULL,
    legal_basis VARCHAR(500)  NULL,
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME      NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_comp_source (source_type, source_id),
    KEY idx_comp_role   (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 8. Configuracion Radar Legal por Sector ───────────────────────────────────

CREATE TABLE IF NOT EXISTS sector_radar_settings (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sector_type ENUM('industrial','commercial') NOT NULL,
    sector_id   INT UNSIGNED NOT NULL,
    enabled     TINYINT(1)   NOT NULL DEFAULT 1,
    notes       TEXT         NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_srs (sector_type, sector_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 9. Modos de Transporte (Radar Legal) ─────────────────────────────────────

CREATE TABLE IF NOT EXISTS radar_transport_modes (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(100) NOT NULL,
    mode        ENUM('maritimo','aereo','terrestre','multimodal') NOT NULL,
    description TEXT         NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_rtm_mode (mode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 10. Puertos (Radar Legal - modo maritimo) ────────────────────────────────

CREATE TABLE IF NOT EXISTS radar_ports (
    id                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    transport_mode_id INT UNSIGNED  NOT NULL,
    name              VARCHAR(255)  NOT NULL,
    country           VARCHAR(100)  NULL,
    un_locode         VARCHAR(10)   NULL,
    notes             TEXT          NULL,
    created_at        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_rp_mode (transport_mode_id),
    CONSTRAINT fk_rp_mode FOREIGN KEY (transport_mode_id) REFERENCES radar_transport_modes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 11. Tipos de Destinacion (Radar Legal) ───────────────────────────────────

CREATE TABLE IF NOT EXISTS radar_destinations (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    direction   ENUM('importacion','exportacion') NOT NULL,
    name        VARCHAR(255) NOT NULL,
    code        VARCHAR(20)  NULL,
    description TEXT         NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_rd_dir (direction)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 12. Restricciones (Radar Legal) ─────────────────────────────────────────

CREATE TABLE IF NOT EXISTS radar_restrictions (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    restriction_type ENUM('prohibicion','dumping','licencia_automatica','licencia_no_automatica','cuota','otro') NOT NULL,
    name             VARCHAR(255) NOT NULL,
    destination_id   INT UNSIGNED NULL,
    legal_basis      VARCHAR(500) NULL,
    description      TEXT         NULL,
    valid_from       DATE         NULL,
    valid_until      DATE         NULL,
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_rr_type (restriction_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 13. Controversias / Delitos (Radar Legal) ───────────────────────────────

CREATE TABLE IF NOT EXISTS radar_disputes (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    dispute_type   ENUM('infraccion_aduanera','incumplimiento_normativo','delito_aduanero','otro') NOT NULL,
    name           VARCHAR(255) NOT NULL,
    legal_basis    VARCHAR(500) NULL,
    description    TEXT         NULL,
    sanction_range VARCHAR(255) NULL,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_rdis_type (dispute_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 14. Tipos de Contrato Internacional (Radar Legal) ────────────────────────

CREATE TABLE IF NOT EXISTS radar_contract_types (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(255) NOT NULL,
    category    ENUM('compraventa','llave_en_mano','agencia','distribucion','inversion_activos','inversion_financiera','joint_venture','otro') NOT NULL,
    description TEXT         NULL,
    key_points  TEXT         NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_rct_cat (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 15. Seeds minimos ────────────────────────────────────────────────────────

INSERT IGNORE INTO commercial_sectors (id, name, type, subtype, status, jurisdiction, description, radar_enabled) VALUES
    (1, 'Comercio Minorista General',  'retail',    'Indumentaria y calzado',   'activo',    'Nacional', 'Sector de comercio minorista de alcance nacional.', 1),
    (2, 'Servicios Financieros',       'finanzas',  'Seguros y banca',          'activo',    'Nacional', 'Sector de servicios financieros y bancarios.',       0),
    (3, 'Turismo y Hospitalidad',      'turismo',   'Alojamiento y gastronomia','activo',    'Nacional', 'Sector turistico y hotelero.',                       1);

INSERT IGNORE INTO chambers (id, name, area, description, status) VALUES
    (1, 'Camara de Comercio',                   'comercio',          'Camara de comercio general.',                         'activa'),
    (2, 'Camara de Importadores y Exportadores','comercio_exterior', 'Camara de comercio exterior e importadores.',         'activa'),
    (3, 'Camara de la Industria del Turismo',   'turismo',           'Camara sectorial del turismo y hospitalidad.',        'activa');

INSERT IGNORE INTO agencies (id, name, area, description, status) VALUES
    (1, 'Agencia de Turismo Nacional',  'turismo',          'Agencia gubernamental de promocion turistica.',        'activa'),
    (2, 'Agencia de Comercio Exterior', 'comercio_exterior','Agencia de promocion de exportaciones e inversiones.', 'activa');

INSERT IGNORE INTO chamber_sector (chamber_id, sector_type, sector_id) VALUES
    (1, 'commercial', 1),
    (2, 'commercial', 1),
    (2, 'commercial', 2),
    (3, 'commercial', 3);

INSERT IGNORE INTO agency_sector (agency_id, sector_type, sector_id) VALUES
    (1, 'commercial', 3),
    (2, 'commercial', 1),
    (2, 'commercial', 2);

INSERT IGNORE INTO policy_lines (id, source_type, source_id, title, summary, line_type, jurisdiction, published_at, valid_from, status, area) VALUES
    (1, 'chamber', 1, 'Codigo de Etica Comercial',          'Normas de conducta para socios.',         'propia',   'Nacional', '2023-01-01', '2023-01-01', 'vigente', 'comercio'),
    (2, 'chamber', 2, 'Reg. de Licencias de Importacion',   'Resolucion 123/2023 sobre LNA.',          'gobierno', 'Nacional', '2023-06-01', '2023-07-01', 'vigente', 'comercio_exterior'),
    (3, 'agency',  2, 'Marco Legal de Exportaciones',       'Ley 24.425 - OMC - Regimen general.',     'gobierno', 'Nacional', '1995-01-01', '1995-01-01', 'vigente', 'comercio_exterior');

INSERT IGNORE INTO competencies (id, source_type, source_id, role, organism, organ, responsible, scope, legal_basis) VALUES
    (1, 'agency',  2, 'aprobar',   'Ministerio de Comercio', 'Direccion de Comercio Exterior', 'Director/a Nacional', 'Aprobacion de licencias de importacion/exportacion', 'Dec. 1299/2010'),
    (2, 'agency',  2, 'controlar', 'AFIP - Aduana',          'Division de Operaciones',        'Jefe/a de Division',  'Control aduanero de mercancias', 'Codigo Aduanero'),
    (3, 'chamber', 1, 'dictamen',  'Camara de Comercio',     'Comision de Etica',              'Secretario/a',        'Emision de dictamenes sobre conflictos entre socios', NULL);

INSERT IGNORE INTO radar_transport_modes (id, name, mode, description) VALUES
    (1, 'Maritimo',   'maritimo',   'Transporte de carga por via maritima'),
    (2, 'Aereo',      'aereo',      'Transporte de carga por via aerea'),
    (3, 'Terrestre',  'terrestre',  'Transporte de carga por via terrestre'),
    (4, 'Multimodal', 'multimodal', 'Combinacion de dos o mas modos de transporte');

INSERT IGNORE INTO radar_ports (id, transport_mode_id, name, country, un_locode) VALUES
    (1, 1, 'Puerto de Buenos Aires', 'Argentina', 'ARBUE'),
    (2, 1, 'Puerto de Rosario',      'Argentina', 'ARROS'),
    (3, 1, 'Puerto de Bahia Blanca', 'Argentina', 'ARBHI'),
    (4, 1, 'Puerto de Santos',       'Brasil',    'BRSSZ'),
    (5, 1, 'Puerto de Valparaiso',   'Chile',     'CLVAP');

INSERT IGNORE INTO radar_destinations (id, direction, name, code, description) VALUES
    (1, 'importacion', 'Despacho a plaza',       'IM4', 'Importacion definitiva a consumo'),
    (2, 'importacion', 'Importacion temporal',   'IM5', 'Admision temporaria para perfeccionamiento activo'),
    (3, 'exportacion', 'Exportacion definitiva', 'EX1', 'Exportacion definitiva para consumo'),
    (4, 'exportacion', 'Exportacion temporaria', 'EX2', 'Exportacion temporal con reimportacion prevista');

INSERT IGNORE INTO radar_restrictions (id, restriction_type, name, legal_basis, description) VALUES
    (1, 'licencia_no_automatica', 'Licencias No Automaticas de Importacion', 'Res. 5/2015 MINCETUR', 'Requieren aprobacion previa segun producto y origen.'),
    (2, 'dumping',                'Derecho Antidumping',                     'Ley 24.425',            'Proteccion contra importacion a precios inferiores al costo.'),
    (3, 'prohibicion',            'Prohibicion de importacion (lista roja)', 'Decreto 509/2007',      'Lista de productos con prohibicion de ingreso al territorio.');

INSERT IGNORE INTO radar_contract_types (id, name, category, description, key_points) VALUES
    (1, 'Compraventa Internacional',         'compraventa',         'Contrato regido por la CISG o derecho local',               'Incoterms, forma de pago, entrega, inspeccion'),
    (2, 'Contrato Llave en Mano',            'llave_en_mano',       'El proveedor entrega la obra terminada y en funcionamiento', 'Precio global, plazo, penalidades, transferencia tecnologica'),
    (3, 'Contrato de Agencia Internacional', 'agencia',             'Representacion comercial internacional',                    'Exclusividad, territorio, comisiones, resolucion'),
    (4, 'Inversion en Activos (FDI)',         'inversion_activos',   'Adquisicion de bienes o empresas en el extranjero',         'Marco cambiario, giro de utilidades, proteccion de inversion'),
    (5, 'Inversion Financiera Internacional','inversion_financiera', 'Participacion en instrumentos financieros extranjeros',     'Regimen cambiario, tratados, riesgos de volatilidad'),
    (6, 'Joint Venture Internacional',       'joint_venture',       'Empresa conjunta con socios extranjeros',                   'Aporte de capital, gobierno, exit, derecho aplicable');
