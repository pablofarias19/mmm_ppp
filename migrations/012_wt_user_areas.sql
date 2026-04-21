-- ============================================================
-- MIGRACIÓN 012: WT — Áreas normalizadas y mejoras en bloqueos
-- Ejecutar UNA SOLA VEZ en producción.
-- Las tablas usan IF NOT EXISTS (seguro si se re-ejecuta).
-- Los ALTER TABLE sobre wt_user_blocks deben ejecutarse solo una vez.
-- ============================================================

-- ──────────────────────────────────────────────────────────────
-- 1. Tabla: wt_user_areas
--    Reemplaza el campo JSON `areas` de wt_user_preferences.
--    Cada fila representa un área de interés de un usuario.
--    Ventaja: la base de datos puede indexar y buscar eficientemente
--    qué usuarios comparten una misma área, sin abrir JSON.
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS wt_user_areas (
    user_id    INT(11)     NOT NULL,
    area_slug  VARCHAR(64) NOT NULL,
    created_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, area_slug),
    -- Índice inverso: permite buscar "todos los usuarios con área X"
    KEY idx_wt_area_slug_user (area_slug, user_id),
    CONSTRAINT fk_wt_user_areas_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 2. Mejoras en wt_user_blocks
-- ──────────────────────────────────────────────────────────────

-- Índice compuesto inverso: acelera la consulta "¿alguien me bloqueó a mí?"
-- que antes dependía solo del índice idx_wt_block_blocked (una sola columna).
-- Con (blocked_user_id, blocker_user_id) la búsqueda bidireccional con OR
-- puede resolverse con dos range scans en lugar de uno parcial.
ALTER TABLE wt_user_blocks
    ADD INDEX idx_wt_block_blocked_blocker (blocked_user_id, blocker_user_id);

-- Restricción que impide que un usuario se bloquee a sí mismo a nivel DB.
-- Antes sólo lo frenaba el código PHP; ahora la base de datos también lo garantiza.
-- Requiere MySQL 8.0.16+ o MariaDB 10.2.1+
ALTER TABLE wt_user_blocks
    ADD CONSTRAINT chk_wt_no_self_block
        CHECK (blocker_user_id <> blocked_user_id);

-- ──────────────────────────────────────────────────────────────
-- Nota sobre migración de datos existentes:
-- Si ya existen usuarios con áreas guardadas en JSON en
-- wt_user_preferences.areas, esos datos se migran automáticamente
-- la primera vez que cada usuario abre su perfil WT.
-- Esto ocurre en api/wt_preferences.php (migración transparente en caliente).
-- No se requiere ningún script adicional.
-- ──────────────────────────────────────────────────────────────
