-- Migration 021: Noticias and Trivias field additions
-- Date: 2026-04-24

-- ── Noticias: replace imagen with link, add resumen_popup and tags ─────────

-- Add link column (URL to the full article, replaces imagen)
ALTER TABLE noticias ADD COLUMN IF NOT EXISTS link         VARCHAR(500)  NULL    COMMENT 'URL a la noticia completa';
-- Add popup summary field
ALTER TABLE noticias ADD COLUMN IF NOT EXISTS resumen_popup TEXT         NULL    COMMENT 'Resumen breve para mostrar en popup del mapa';
-- Add tags field (comma-separated)
ALTER TABLE noticias ADD COLUMN IF NOT EXISTS tags          VARCHAR(500) NULL    COMMENT 'Etiquetas separadas por comas';

-- ── Trivias: new fields for popup and app ─────────────────────────────────

-- Illustrative SVG image (URL or path)
ALTER TABLE trivias ADD COLUMN IF NOT EXISTS svg          VARCHAR(500) NULL    COMMENT 'URL o path a imagen SVG ilustrativa';
-- Game reference
ALTER TABLE trivias ADD COLUMN IF NOT EXISTS referencia   VARCHAR(255) NULL    COMMENT 'Referencia del juego';
-- Game type
ALTER TABLE trivias ADD COLUMN IF NOT EXISTS tipo         VARCHAR(100) NULL    COMMENT 'Tipo de juego';
-- Age recommendation
ALTER TABLE trivias ADD COLUMN IF NOT EXISTS edad         VARCHAR(50)  NULL    COMMENT 'Edad recomendada';
-- Emojis for color and vibes
ALTER TABLE trivias ADD COLUMN IF NOT EXISTS emojis       VARCHAR(255) NULL    COMMENT 'Emojis decorativos del popup';
-- PHP app path (must be within allowed directory)
ALTER TABLE trivias ADD COLUMN IF NOT EXISTS app_path     VARCHAR(500) NULL    COMMENT 'Path relativo del archivo PHP de la app (dentro de apps/trivias/)';
