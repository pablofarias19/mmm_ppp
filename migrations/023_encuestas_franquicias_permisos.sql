-- ============================================================
-- MIGRACIÓN 023: Encuestas (gráficos), Franquicias, Permisos Encuestas
-- Ejecutar UNA SOLA VEZ en la base de datos
-- Todas las sentencias son idempotentes (IF NOT EXISTS / IF EXISTS)
-- ============================================================

-- ─────────────────────────────────────────────────────────────
-- 1. encuestas: configuración del panel detalle y gráficos
-- ─────────────────────────────────────────────────────────────
ALTER TABLE encuestas
    ADD COLUMN IF NOT EXISTS detalle_activo TINYINT(1) NOT NULL DEFAULT 1
        COMMENT '1 = habilitar panel Detalle con gráficos; 0 = solo popup',
    ADD COLUMN IF NOT EXISTS graficos_config VARCHAR(255) NOT NULL DEFAULT 'barras,torta,tendencia'
        COMMENT 'Lista CSV de tipos de gráfico habilitados: barras, torta, tendencia';

-- ─────────────────────────────────────────────────────────────
-- 2. respuestas_encuesta: timestamp por respuesta (para tendencias)
-- ─────────────────────────────────────────────────────────────
ALTER TABLE respuestas_encuesta
    ADD COLUMN IF NOT EXISTS fecha_respuesta DATETIME NULL DEFAULT NULL
        COMMENT 'Fecha y hora en que se emitió la respuesta';

-- Poblar fecha_respuesta con la fecha de participación donde sea posible
-- (solo si la columna quedó vacía y existe la tabla de participaciones)
UPDATE respuestas_encuesta r
    JOIN preguntas_encuesta p ON p.id = r.pregunta_id
    JOIN encuesta_participaciones ep ON ep.encuesta_id = p.encuesta_id AND ep.user_id = r.user_id
SET r.fecha_respuesta = ep.fecha_participacion
WHERE r.fecha_respuesta IS NULL;

-- ─────────────────────────────────────────────────────────────
-- 3. industries: permiso para crear encuestas
-- ─────────────────────────────────────────────────────────────
ALTER TABLE industries
    ADD COLUMN IF NOT EXISTS encuestas_permitidas TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1 = la industria puede crear encuestas; 0 = no puede';

-- ─────────────────────────────────────────────────────────────
-- 4. businesses: override de permiso de encuestas
-- ─────────────────────────────────────────────────────────────
ALTER TABLE businesses
    ADD COLUMN IF NOT EXISTS encuestas_override ENUM('heredar','habilitada','deshabilitada') NOT NULL DEFAULT 'heredar'
        COMMENT 'Override de permiso de encuestas: heredar de industria, o forzar habilitada/deshabilitada';

-- ─────────────────────────────────────────────────────────────
-- 5. brands: campos de franquicia (panel CREAR FRANQUICIA)
-- ─────────────────────────────────────────────────────────────
ALTER TABLE brands
    ADD COLUMN IF NOT EXISTS crear_franquicia TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1 = la marca ofrece franquicias (habilita panel Franquicias)',
    ADD COLUMN IF NOT EXISTS franquicia_descripcion TEXT NULL
        COMMENT 'Texto explicativo de la franquicia',
    ADD COLUMN IF NOT EXISTS franquicia_condiciones TEXT NULL
        COMMENT 'Condiciones generales de la franquicia',
    ADD COLUMN IF NOT EXISTS franquicia_exclusividad TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1 = con exclusividad territorial',
    ADD COLUMN IF NOT EXISTS franquicia_territorio TEXT NULL
        COMMENT 'Ámbito territorial de la franquicia',
    ADD COLUMN IF NOT EXISTS franquicia_productos TEXT NULL
        COMMENT 'Productos o servicios incluidos en la franquicia',
    ADD COLUMN IF NOT EXISTS franquicia_garantias TEXT NULL
        COMMENT 'Garantías ofrecidas al franquiciado',
    ADD COLUMN IF NOT EXISTS franquicia_url VARCHAR(500) NULL
        COMMENT 'URL con más información sobre la franquicia';

-- Índice para filtrar marcas con franquicia activa
ALTER TABLE brands
    ADD INDEX IF NOT EXISTS idx_brands_crear_franquicia (crear_franquicia);

-- ─────────────────────────────────────────────────────────────
-- FIN DE MIGRACIÓN 023
-- ─────────────────────────────────────────────────────────────
