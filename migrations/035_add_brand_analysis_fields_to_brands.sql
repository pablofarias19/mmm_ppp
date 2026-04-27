-- ============================================================
-- Migración 035: Campos de análisis marcario en tabla brands
-- Agrega las columnas necesarias para que los módulos
--   /brand_analysis  y  /niza_classification
-- persistan sus datos directamente en `brands` en lugar de
-- depender de tablas separadas (analisis_marcario /
-- clasificacion_niza) que pueden no existir en producción.
--
-- ⚠️  Requiere MySQL ≥ 8.0 o MariaDB ≥ 10.0.2 (ADD COLUMN IF NOT EXISTS).
-- Idempotente: puede ejecutarse más de una vez sin error.
--
-- Columnas ya existentes en `brands` que se reutilizan:
--   · clase_principal   — usado por NizaClassification
--   · nivel_proteccion  — usado por BrandAnalysis
--   · riesgo_oposicion  — ya presente; no se toca aquí
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
