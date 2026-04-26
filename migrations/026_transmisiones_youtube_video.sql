-- ============================================================
-- MIGRACIÓN 026: Agregar tipo youtube_video a transmisiones
-- Ejecutar UNA SOLA VEZ en instalaciones existentes.
-- Es segura de re-ejecutar: usa MODIFY COLUMN con el ENUM completo.
-- ============================================================

-- Ampliar el ENUM de transmisiones.tipo para incluir youtube_video.
-- MODIFY COLUMN reemplaza el ENUM completo; si ya incluye youtube_video no hay pérdida de datos.
ALTER TABLE `transmisiones`
    MODIFY COLUMN `tipo` ENUM(
        'youtube_live',
        'youtube_video',
        'radio_stream',
        'audio_stream',
        'video_stream'
    ) NOT NULL DEFAULT 'youtube_live'
    COMMENT 'youtube_live=YouTube en vivo, youtube_video=YouTube grabado, radio_stream=radio online (Icecast/Shoutcast), audio_stream=audio generico, video_stream=HLS/RTMP';

-- ─────────────────────────────────────────────────────────────
-- FIN DE MIGRACIÓN 026
-- ─────────────────────────────────────────────────────────────
