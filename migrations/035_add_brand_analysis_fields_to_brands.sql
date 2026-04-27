-- ============================================================
-- Migración 035: Campos de análisis marcario en tabla brands
-- Agrega las columnas necesarias para que los módulos
--   /brand_analysis, /niza_classification, /monetization
--   y /legal_risk persistan sus datos directamente en `brands`
-- en lugar de depender de tablas separadas que pueden no
-- existir en producción.
--
-- ⚠️  Requiere MySQL ≥ 8.0 o MariaDB ≥ 10.0.2 (ADD COLUMN IF NOT EXISTS).
-- Idempotente: puede ejecutarse más de una vez sin error.
--
-- Columnas ya existentes en `brands` que se reutilizan:
--   · clase_principal   — usado por NizaClassification
--   · nivel_proteccion  — usado por BrandAnalysis
--   · riesgo_oposicion  — usado por LegalRisk
--   · valor_activo      — usado por Monetization
-- ============================================================

-- ─────────────────────────────────────────────────────────────
-- Campos de Análisis Marcario (BrandAnalysis)
-- ─────────────────────────────────────────────────────────────
ALTER TABLE brands
    ADD COLUMN IF NOT EXISTS distintividad           VARCHAR(10)   NULL COMMENT 'ALTA | MEDIA | BAJA — grado de distintividad del signo'    AFTER nivel_proteccion,
    ADD COLUMN IF NOT EXISTS riesgo_confusion        TEXT          NULL COMMENT 'Descripción del riesgo de confusión con marcas existentes'  AFTER distintividad,
    ADD COLUMN IF NOT EXISTS conflictos_clases       TEXT          NULL COMMENT 'Conflictos potenciales en clases Niza relevantes'           AFTER riesgo_confusion,
    ADD COLUMN IF NOT EXISTS expansion_internacional TEXT          NULL COMMENT 'Posibilidades y estrategia de expansión internacional'      AFTER conflictos_clases;

-- ─────────────────────────────────────────────────────────────
-- Campos de Clasificación Niza (NizaClassification)
-- ─────────────────────────────────────────────────────────────
ALTER TABLE brands
    ADD COLUMN IF NOT EXISTS clases_complementarias  VARCHAR(255)  NULL COMMENT 'Clases Niza complementarias (ej: "9,35,42")'               AFTER clase_principal,
    ADD COLUMN IF NOT EXISTS riesgo_colision         TEXT          NULL COMMENT 'Riesgo de colisión con marcas registradas en cada clase'    AFTER clases_complementarias;

-- ─────────────────────────────────────────────────────────────
-- Campos de Monetización (Monetization)
-- ─────────────────────────────────────────────────────────────
ALTER TABLE brands
    ADD COLUMN IF NOT EXISTS fuentes_ingresos        TEXT          NULL COMMENT 'Fuentes de ingresos actuales y futuras de la marca'         AFTER valor_activo,
    ADD COLUMN IF NOT EXISTS escalabilidad           VARCHAR(100)  NULL COMMENT 'Evaluación de la escalabilidad del modelo de negocio'      AFTER fuentes_ingresos,
    ADD COLUMN IF NOT EXISTS margen_potencial        VARCHAR(100)  NULL COMMENT 'Estimación del margen potencial por canal de monetización'  AFTER escalabilidad;

-- ─────────────────────────────────────────────────────────────
-- Campos de Riesgo Legal (LegalRisk)
-- ─────────────────────────────────────────────────────────────
ALTER TABLE brands
    ADD COLUMN IF NOT EXISTS riesgo_nulidad          TEXT          NULL COMMENT 'Riesgo de nulidad absoluta o relativa del registro'        AFTER riesgo_oposicion,
    ADD COLUMN IF NOT EXISTS riesgo_infraccion       TEXT          NULL COMMENT 'Riesgo de infracción o uso no autorizado por terceros'     AFTER riesgo_nulidad,
    ADD COLUMN IF NOT EXISTS estrategias_defensivas  TEXT          NULL COMMENT 'Estrategias defensivas y plan de vigilancia marcaria'      AFTER riesgo_infraccion;
