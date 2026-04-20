-- ============================================================
-- Mapita Database Diagnostic Queries
-- Ejecuta estas queries en phpMyAdmin para verificar el estado
-- de las tablas necesarias para los APIs
-- ============================================================

-- 1. Ver si existen las tablas requeridas
SELECT
    TABLE_NAME,
    TABLE_TYPE,
    ENGINE,
    TABLE_ROWS,
    DATA_LENGTH
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME IN (
        'business_icons',
        'noticias',
        'trivias',
        'trivia_scores',
        'brand_gallery',
        'attachments',
        'encuestas',
        'encuesta_questions',
        'encuesta_responses',
        'eventos'
    )
ORDER BY TABLE_NAME;

-- ============================================================
-- 2. Verificar columnas de business_icons
SELECT
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_KEY,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'business_icons'
    AND TABLE_SCHEMA = DATABASE()
ORDER BY ORDINAL_POSITION;

-- ============================================================
-- 3. Contar registros en cada tabla
SELECT
    'business_icons' as table_name,
    COUNT(*) as row_count
FROM business_icons
UNION ALL
SELECT 'noticias', COUNT(*) FROM noticias
UNION ALL
SELECT 'trivias', COUNT(*) FROM trivias
UNION ALL
SELECT 'trivia_scores', COUNT(*) FROM trivia_scores
UNION ALL
SELECT 'brand_gallery', COUNT(*) FROM brand_gallery
UNION ALL
SELECT 'attachments', COUNT(*) FROM attachments
UNION ALL
SELECT 'encuestas', COUNT(*) FROM encuestas
UNION ALL
SELECT 'encuesta_questions', COUNT(*) FROM encuesta_questions
UNION ALL
SELECT 'encuesta_responses', COUNT(*) FROM encuesta_responses
UNION ALL
SELECT 'eventos', COUNT(*) FROM eventos;

-- ============================================================
-- 4. Verificar que business_icons tiene los datos correctos
SELECT
    COUNT(*) as total_icons,
    COUNT(DISTINCT business_type) as unique_types,
    COUNT(DISTINCT emoji) as unique_emojis
FROM business_icons;

-- ============================================================
-- 5. Ver primeros 5 registros de business_icons
SELECT * FROM business_icons LIMIT 5;

-- ============================================================
-- 6. Listar todas las tablas en la BD actual
SHOW TABLES;

-- ============================================================
-- 7. Verificar errores de integridad referencial
-- (si las foreign keys están bien configuradas)
SELECT
    CONSTRAINT_NAME,
    TABLE_NAME,
    REFERENCED_TABLE_NAME
FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = DATABASE()
ORDER BY TABLE_NAME;
