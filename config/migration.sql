-- ============================================================
-- Mapita - Database Migration
-- Run this script once against the production database to add
-- tables and columns required by new features.
-- ============================================================

-- 1. Add reset_token columns to users (for password reset flow)
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS reset_token        VARCHAR(64)  NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS reset_token_expiry DATETIME     NULL DEFAULT NULL;

-- 2. Create reviews table (for ratings & reviews feature)
CREATE TABLE IF NOT EXISTS reviews (
    id           INT          NOT NULL AUTO_INCREMENT,
    business_id  INT          NOT NULL,
    user_id      INT          NOT NULL,
    rating       TINYINT      NOT NULL,
    comment      TEXT,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT chk_rating CHECK (rating BETWEEN 1 AND 5),
    CONSTRAINT unique_review UNIQUE (business_id, user_id),
    CONSTRAINT fk_review_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    CONSTRAINT fk_review_user     FOREIGN KEY (user_id)     REFERENCES users(id)      ON DELETE CASCADE
);

-- 3. Ensure businesses.visible column defaults to 1
ALTER TABLE businesses
    MODIFY COLUMN visible TINYINT(1) NOT NULL DEFAULT 1;

-- 4. Add geolocation to marcas table
ALTER TABLE marcas
    ADD COLUMN IF NOT EXISTS lat  DECIMAL(10, 8) NULL,
    ADD COLUMN IF NOT EXISTS lng  DECIMAL(11, 8) NULL;

-- 5. Add brand conditions (zones, licenses, franchises, exclusive)
ALTER TABLE marcas
    ADD COLUMN IF NOT EXISTS tiene_zona        TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS zona_radius_km    INT        NULL DEFAULT 10,
    ADD COLUMN IF NOT EXISTS tiene_licencia    TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS licencia_detalle VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS es_franquicia    TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS franchise_details VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS zona_exclusiva   TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS zona_exclusiva_radius_km INT NULL DEFAULT 2;

-- 6. Create or recreate business_icons table (for dynamic icon loading)
-- Drop existing table if it's missing columns
DROP TABLE IF EXISTS business_icons;

-- Create business_icons table with all required columns
CREATE TABLE business_icons (
    id                INT           NOT NULL AUTO_INCREMENT,
    business_type    VARCHAR(100)  NOT NULL UNIQUE,
    emoji            VARCHAR(10)   NOT NULL,
    icon_class       VARCHAR(100)  NULL,
    color            VARCHAR(7)    NOT NULL,
    created_at       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default business type icons
INSERT INTO business_icons (business_type, emoji, color, icon_class) VALUES
('comercio', '🛍️', '#e74c3c', 'icon-comercio'),
('hotel', '🏨', '#3498db', 'icon-hotel'),
('restaurante', '🍽️', '#e67e22', 'icon-restaurante'),
('inmobiliaria', '🏠', '#27ae60', 'icon-inmobiliaria'),
('farmacia', '💊', '#9b59b6', 'icon-farmacia'),
('gimnasio', '💪', '#1abc9c', 'icon-gimnasio'),
('cafeteria', '☕', '#d35400', 'icon-cafeteria'),
('academia', '📚', '#2980b9', 'icon-academia'),
('bar', '🍺', '#8e44ad', 'icon-bar'),
('salon_belleza', '💇', '#e91e63', 'icon-salon'),
('banco', '🏦', '#16a085', 'icon-banco'),
('tienda_ropa', '👕', '#c0392b', 'icon-ropa'),
('supermercado', '🛒', '#8e44ad', 'icon-super'),
('cine', '🎬', '#2980b9', 'icon-cine'),
('biblioteca', '📖', '#27ae60', 'icon-biblioteca'),
('parque', '🌳', '#16a085', 'icon-parque'),
('hospital', '🏥', '#e74c3c', 'icon-hospital'),
('escuela', '🎓', '#3498db', 'icon-escuela'),
('estacion', '🚂', '#34495e', 'icon-estacion'),
('gasolinera', '⛽', '#f39c12', 'icon-gasolina'),
('estacionamiento', '🅿️', '#95a5a6', 'icon-parking'),
('taxi', '🚕', '#f1c40f', 'icon-taxi'),
('carne', '🥩', '#e74c3c', 'icon-carne'),
('pescaderia', '🐟', '#3498db', 'icon-pescado'),
('panaderia', '🥐', '#d35400', 'icon-pan'),
('pasteleria', '🎂', '#e91e63', 'icon-pastel'),
('heladeria', '🍦', '#3498db', 'icon-helado'),
('fruteria', '🍎', '#27ae60', 'icon-frutas'),
('verduleria', '🥬', '#27ae60', 'icon-verduras'),
('bebidas', '🥤', '#9b59b6', 'icon-bebidas'),
('otros', '📍', '#667eea', 'icon-otros');

-- Agregar tipos nuevos sin pisar los existentes
INSERT IGNORE INTO business_icons (business_type, emoji, color, icon_class) VALUES
('pizzeria',     '🍕', '#e74c3c', 'icon-pizzeria'),
('indumentaria', '👕', '#9b59b6', 'icon-indumentaria'),
('muebleria',    '🛋️','#8e6914', 'icon-muebleria'),
('floristeria',  '💐', '#e91e63', 'icon-floristeria'),
('libreria',     '📖', '#1abc9c', 'icon-libreria'),
('productora_audiovisual', '🎥', '#6c5ce7', 'icon-productora-audiovisual'),
('escuela_musicos', '🎼', '#8e44ad', 'icon-escuela-musicos'),
('taller_artes', '🎨', '#e67e22', 'icon-taller-artes'),
('biodecodificacion', '🧬', '#16a085', 'icon-biodecodificacion'),
('libreria_cristiana', '📚', '#2d6a4f', 'icon-libreria-cristiana'),
('odontologia',  '🦷', '#3498db', 'icon-odontologia'),
('veterinaria',  '🐾', '#27ae60', 'icon-veterinaria'),
('optica',       '👓', '#2980b9', 'icon-optica'),
('barberia',     '💈', '#c0392b', 'icon-barberia'),
('spa',          '💆', '#9b59b6', 'icon-spa'),
('seguros',      '🛡️','#2980b9', 'icon-seguros'),
('abogado',      '⚖️','#34495e', 'icon-abogado'),
('contador',     '📊', '#2c3e50', 'icon-contador'),
('taller',       '🔩', '#7f8c8d', 'icon-taller'),
('remate',       '🔨', '#d35400', 'icon-remate'),
('construccion', '🏗️','#e67e22', 'icon-construccion'),
('turismo',      '✈️','#16a085', 'icon-turismo'),
('electronica',  '📱', '#2980b9', 'icon-electronica'),
('autos_venta',  '🚗', '#2980b9', 'icon-autos-venta'),
('motos_venta',  '🏍️', '#8e44ad', 'icon-motos-venta'),
('medico_pediatra','🧒','#0ea5e9','icon-medico-pediatra'),
('medico_traumatologo','🦴','#2563eb','icon-medico-traumatologo'),
('laboratorio','🧪','#14b8a6','icon-laboratorio'),
('ingenieria_civil','🏗️','#f59e0b','icon-ingenieria-civil'),
('astrologo','🔮','#6366f1','icon-astrologo'),
('grafica','🖨️','#a855f7','icon-grafica'),
('alquiler_mobiliario_fiestas','🪑','#f59e0b','icon-alquiler-mobiliario-fiestas'),
('propalacion_musica','🔊','#6366f1','icon-propalacion-musica'),
('animacion_fiestas','🎉','#ec4899','icon-animacion-fiestas'),
('zapatero','👞','#7c2d12','icon-zapatero'),
('gas_en_garrafa','🛢️','#0ea5e9','icon-gas-en-garrafa'),
('videojuegos','🎮','#8b5cf6','icon-videojuegos'),
('seguridad','🛡️','#334155','icon-seguridad'),
('electricista','💡','#facc15','icon-electricista'),
('gasista','🔥','#f97316','icon-gasista'),
('maestro_particular','📘','#0ea5e9','icon-maestro-particular'),
('asistencia_ancianos','🧓','#14b8a6','icon-asistencia-ancianos'),
('enfermeria','🩺','#0ea5e9','icon-enfermeria');

-- ============================================================
-- 7. Add missing columns to brands table (formulario unificado)
-- ============================================================
ALTER TABLE brands
    ADD COLUMN IF NOT EXISTS description             TEXT          NULL AFTER extended_description,
    ADD COLUMN IF NOT EXISTS ubicacion               VARCHAR(255)  NULL AFTER website,
    -- Situación legal INPI
    ADD COLUMN IF NOT EXISTS inpi_registrada         TINYINT(1)    NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS inpi_numero             VARCHAR(100)  NULL,
    ADD COLUMN IF NOT EXISTS inpi_fecha_registro     DATE          NULL,
    ADD COLUMN IF NOT EXISTS inpi_vencimiento        DATE          NULL,
    ADD COLUMN IF NOT EXISTS inpi_clases_registradas VARCHAR(255)  NULL,
    ADD COLUMN IF NOT EXISTS inpi_tipo               VARCHAR(100)  NULL,
    -- Historia y clientela
    ADD COLUMN IF NOT EXISTS historia_marca          LONGTEXT      NULL,
    ADD COLUMN IF NOT EXISTS target_audience         TEXT          NULL,
    ADD COLUMN IF NOT EXISTS propuesta_valor         TEXT          NULL,
    -- Redes sociales
    ADD COLUMN IF NOT EXISTS instagram               VARCHAR(255)  NULL,
    ADD COLUMN IF NOT EXISTS facebook                VARCHAR(255)  NULL,
    ADD COLUMN IF NOT EXISTS tiktok                  VARCHAR(255)  NULL,
    ADD COLUMN IF NOT EXISTS twitter                 VARCHAR(255)  NULL,
    ADD COLUMN IF NOT EXISTS linkedin                VARCHAR(255)  NULL,
    ADD COLUMN IF NOT EXISTS youtube                 VARCHAR(255)  NULL,
    ADD COLUMN IF NOT EXISTS whatsapp                VARCHAR(50)   NULL;

-- ============================================================
-- 8. Create noticias (news) table for /api/noticias.php
-- ============================================================
CREATE TABLE IF NOT EXISTS noticias (
    id                  INT          NOT NULL AUTO_INCREMENT,
    titulo              VARCHAR(255) NOT NULL,
    contenido           LONGTEXT     NOT NULL,
    categoria           VARCHAR(100) NOT NULL DEFAULT 'General',
    imagen              VARCHAR(255) NULL,
    user_id             INT          NOT NULL,
    activa              TINYINT(1)   NOT NULL DEFAULT 1,
    fecha_publicacion   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    vistas              INT          NOT NULL DEFAULT 0,
    created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_noticia_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_activa (activa),
    INDEX idx_categoria (categoria),
    INDEX idx_fecha (fecha_publicacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. Create trivias table for /api/trivias.php
-- ============================================================
CREATE TABLE IF NOT EXISTS trivias (
    id              INT          NOT NULL AUTO_INCREMENT,
    titulo          VARCHAR(255) NOT NULL,
    descripcion     TEXT         NULL,
    dificultad      ENUM('facil', 'medio', 'dificil') NOT NULL DEFAULT 'medio',
    tiempo_limite   INT          NOT NULL DEFAULT 30,
    activa          TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_activa (activa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. Create trivia_scores table for tracking user scores
-- ============================================================
CREATE TABLE IF NOT EXISTS trivia_scores (
    id                      INT          NOT NULL AUTO_INCREMENT,
    trivia_id               INT          NOT NULL,
    user_id                 INT          NOT NULL,
    puntos                  INT          NOT NULL DEFAULT 0,
    respuestas_correctas    INT          NOT NULL DEFAULT 0,
    created_at              TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_score_trivia FOREIGN KEY (trivia_id) REFERENCES trivias(id) ON DELETE CASCADE,
    CONSTRAINT fk_score_user   FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    INDEX idx_trivia (trivia_id),
    INDEX idx_user (user_id),
    INDEX idx_puntos (puntos)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 10. Create brand_gallery table for /api/brand-gallery.php
-- ============================================================
CREATE TABLE IF NOT EXISTS brand_gallery (
    id              INT          NOT NULL AUTO_INCREMENT,
    brand_id        INT          NOT NULL,
    file_path       VARCHAR(255) NOT NULL,
    titulo          VARCHAR(255) NULL,
    es_principal    TINYINT(1)   NOT NULL DEFAULT 0,
    type            ENUM('photo', 'logo', 'document') DEFAULT 'photo',
    uploaded_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_gallery_brand FOREIGN KEY (brand_id) REFERENCES marcas(id) ON DELETE CASCADE,
    INDEX idx_brand (brand_id),
    INDEX idx_principal (es_principal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 11. Create business_attachments table for business photos
-- ============================================================
CREATE TABLE IF NOT EXISTS attachments (
    id              INT          NOT NULL AUTO_INCREMENT,
    business_id     INT          NULL,
    brand_id        INT          NULL,
    file_path       VARCHAR(255) NOT NULL,
    type            ENUM('photo', 'document', 'logo') DEFAULT 'photo',
    uploaded_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_attach_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    CONSTRAINT fk_attach_brand     FOREIGN KEY (brand_id)     REFERENCES marcas(id)     ON DELETE CASCADE,
    INDEX idx_business (business_id),
    INDEX idx_brand (brand_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 12. Create encuestas (surveys) table for /api/encuestas.php
-- ============================================================
CREATE TABLE IF NOT EXISTS encuestas (
    id              INT          NOT NULL AUTO_INCREMENT,
    titulo          VARCHAR(255) NOT NULL,
    descripcion     TEXT         NULL,
    activa          TINYINT(1)   NOT NULL DEFAULT 1,
    user_id         INT          NOT NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_encuesta_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 13. Create encuesta_questions table
-- ============================================================
CREATE TABLE IF NOT EXISTS encuesta_questions (
    id              INT          NOT NULL AUTO_INCREMENT,
    encuesta_id     INT          NOT NULL,
    question_text   TEXT         NOT NULL,
    tipo            ENUM('text', 'multiple', 'rating') DEFAULT 'multiple',
    orden           INT          NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    CONSTRAINT fk_question_encuesta FOREIGN KEY (encuesta_id) REFERENCES encuestas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 14. Create encuesta_responses table
-- ============================================================
CREATE TABLE IF NOT EXISTS encuesta_responses (
    id              INT          NOT NULL AUTO_INCREMENT,
    question_id     INT          NOT NULL,
    user_id         INT          NULL,
    response_text   TEXT         NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_response_question FOREIGN KEY (question_id) REFERENCES encuesta_questions(id) ON DELETE CASCADE,
    CONSTRAINT fk_response_user      FOREIGN KEY (user_id)     REFERENCES users(id)           ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 15. Create eventos (events) table for /api/eventos.php
-- ============================================================
CREATE TABLE IF NOT EXISTS eventos (
    id              INT          NOT NULL AUTO_INCREMENT,
    titulo          VARCHAR(255) NOT NULL,
    descripcion     LONGTEXT     NULL,
    fecha           DATETIME     NOT NULL,
    ubicacion       VARCHAR(255) NULL,
    business_id     INT          NULL,
    brand_id        INT          NULL,
    imagen          VARCHAR(255) NULL,
    activo          TINYINT(1)   NOT NULL DEFAULT 1,
    user_id         INT          NOT NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_evento_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE SET NULL,
    CONSTRAINT fk_evento_brand     FOREIGN KEY (brand_id)     REFERENCES marcas(id)     ON DELETE SET NULL,
    CONSTRAINT fk_evento_user      FOREIGN KEY (user_id)      REFERENCES users(id)      ON DELETE CASCADE,
    INDEX idx_fecha (fecha),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 16. Create ofertas (deals) table for /api/ofertas.php
-- ============================================================
CREATE TABLE IF NOT EXISTS ofertas (
    id                  INT             NOT NULL AUTO_INCREMENT,
    nombre              VARCHAR(255)    NOT NULL,
    descripcion         TEXT            NULL,
    precio_normal       DECIMAL(10,2)   NULL,
    precio_oferta       DECIMAL(10,2)   NULL,
    fecha_inicio        DATE            NOT NULL DEFAULT (CURDATE()),
    fecha_expiracion    DATE            NULL,
    imagen_url          VARCHAR(500)    NULL,
    lat                 DECIMAL(10,8)   NULL,
    lng                 DECIMAL(11,8)   NULL,
    business_id         INT             NULL,
    activo              TINYINT(1)      NOT NULL DEFAULT 1,
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_oferta_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE SET NULL,
    INDEX idx_activo        (activo),
    INDEX idx_expiracion    (fecha_expiracion),
    INDEX idx_coords        (lat, lng)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 17. Create transmisiones (live streams) table for /api/transmisiones.php
-- ============================================================
CREATE TABLE IF NOT EXISTS transmisiones (
    id              INT             NOT NULL AUTO_INCREMENT,
    titulo          VARCHAR(255)    NOT NULL,
    descripcion     TEXT            NULL,
    tipo            ENUM('youtube_live','radio_stream','audio_stream','video_stream')
                                    NOT NULL DEFAULT 'youtube_live',
    stream_url      VARCHAR(500)    NULL,
    lat             DECIMAL(10,8)   NULL,
    lng             DECIMAL(11,8)   NULL,
    business_id     INT             NULL,
    evento_id       INT             NULL,
    en_vivo         TINYINT(1)      NOT NULL DEFAULT 0,
    activo          TINYINT(1)      NOT NULL DEFAULT 1,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_activo    (activo),
    INDEX idx_en_vivo   (en_vivo),
    INDEX idx_coords    (lat, lng),
    INDEX idx_tipo      (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 13. Crear tabla brand_gallery correcta (para tabla brands)
--     La versión anterior tenía FK a marcas(id) — incorrecto.
-- ============================================================
CREATE TABLE IF NOT EXISTS brand_gallery_v2 (
    id           INT           NOT NULL AUTO_INCREMENT,
    brand_id     INT           NOT NULL,
    filename     VARCHAR(255)  NOT NULL,
    titulo       VARCHAR(255)  NULL,
    es_principal TINYINT(1)    NOT NULL DEFAULT 0,
    orden        INT           NOT NULL DEFAULT 0,
    created_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_bgv2_brand FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE,
    INDEX idx_bgv2_brand (brand_id),
    INDEX idx_bgv2_principal (es_principal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columna logo_url en brands (cache rápida, opcional)
ALTER TABLE brands
    ADD COLUMN IF NOT EXISTS logo_url VARCHAR(255) NULL COMMENT 'Ruta pública del logo del mapa';

ALTER TABLE marcas
    ADD COLUMN IF NOT EXISTS logo_url VARCHAR(255) NULL COMMENT 'Ruta pública del logo del mapa';

-- ============================================================
-- 14. Geolocalización para eventos, trivias y noticias
--     Permite que aparezcan como marcadores en el mapa
-- ============================================================

-- Eventos: columnas geo + extras que usa la API pero faltan en CREATE TABLE
ALTER TABLE eventos ADD COLUMN IF NOT EXISTS lat          DECIMAL(10,6)  NULL    COMMENT 'Latitud del evento';
ALTER TABLE eventos ADD COLUMN IF NOT EXISTS lng          DECIMAL(10,6)  NULL    COMMENT 'Longitud del evento';
ALTER TABLE eventos ADD COLUMN IF NOT EXISTS dest_lat     DECIMAL(10,6)  NULL    COMMENT 'Latitud destino (ruta)';
ALTER TABLE eventos ADD COLUMN IF NOT EXISTS dest_lng     DECIMAL(10,6)  NULL    COMMENT 'Longitud destino (ruta)';
ALTER TABLE eventos ADD COLUMN IF NOT EXISTS hora         TIME           NULL    COMMENT 'Hora del evento';
ALTER TABLE eventos ADD COLUMN IF NOT EXISTS organizador  VARCHAR(255)   NULL    COMMENT 'Organizador o responsable';
ALTER TABLE eventos ADD COLUMN IF NOT EXISTS youtube_link VARCHAR(500)   NULL    COMMENT 'URL de YouTube en vivo';
ALTER TABLE eventos ADD COLUMN IF NOT EXISTS categoria    VARCHAR(100)   NULL    DEFAULT 'General';
ALTER TABLE eventos ADD INDEX IF NOT EXISTS idx_coords (lat, lng);

-- Trivias: geolocalización opcional (ej: trivia de bar, museo, etc.)
ALTER TABLE trivias ADD COLUMN IF NOT EXISTS lat         DECIMAL(10,6)  NULL    COMMENT 'Latitud donde se realiza la trivia';
ALTER TABLE trivias ADD COLUMN IF NOT EXISTS lng         DECIMAL(10,6)  NULL    COMMENT 'Longitud donde se realiza la trivia';
ALTER TABLE trivias ADD COLUMN IF NOT EXISTS ubicacion   VARCHAR(255)   NULL    COMMENT 'Nombre del lugar';
ALTER TABLE trivias ADD COLUMN IF NOT EXISTS business_id INT            NULL    COMMENT 'Negocio que la organiza';
ALTER TABLE trivias ADD INDEX IF NOT EXISTS idx_tri_coords (lat, lng);

-- Noticias: geolocalización opcional (noticias locales con pin en el mapa)
ALTER TABLE noticias ADD COLUMN IF NOT EXISTS lat       DECIMAL(10,6)  NULL    COMMENT 'Latitud de la noticia';
ALTER TABLE noticias ADD COLUMN IF NOT EXISTS lng       DECIMAL(10,6)  NULL    COMMENT 'Longitud de la noticia';
ALTER TABLE noticias ADD COLUMN IF NOT EXISTS ubicacion VARCHAR(255)   NULL    COMMENT 'Lugar al que refiere la noticia';
ALTER TABLE noticias ADD INDEX IF NOT EXISTS idx_not_coords (lat, lng);
