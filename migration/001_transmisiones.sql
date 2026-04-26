-- ============================================================
-- MIGRATION 001: Tabla transmisiones en vivo
-- Ejecutar UNA SOLA VEZ en la base de datos
-- ============================================================

CREATE TABLE IF NOT EXISTS `transmisiones` (
  `id`           INT(11)         NOT NULL AUTO_INCREMENT,
  `titulo`       VARCHAR(255)    NOT NULL,
  `descripcion`  TEXT            DEFAULT NULL,
  `tipo`         ENUM(
                   'youtube_live',
                   'youtube_video',
                   'radio_stream',
                   'audio_stream',
                   'video_stream'
                 )               NOT NULL DEFAULT 'youtube_live'
                 COMMENT 'youtube_live=YouTube en vivo, youtube_video=YouTube grabado, radio_stream=radio online (Icecast/Shoutcast), audio_stream=audio generico, video_stream=HLS/RTMP',
  `stream_url`   VARCHAR(500)    NOT NULL
                 COMMENT 'URL completa: https://youtu.be/LIVE_ID o https://stream.radio.ejemplo.com/live',
  `lat`          DECIMAL(10,8)   DEFAULT NULL COMMENT 'Latitud de origen de la transmision',
  `lng`          DECIMAL(11,8)   DEFAULT NULL COMMENT 'Longitud de origen de la transmision',
  `business_id`  INT(11)         DEFAULT NULL COMMENT 'Negocio o medio asociado',
  `evento_id`    INT(11)         DEFAULT NULL COMMENT 'Evento asociado (si es parte de un evento)',
  `en_vivo`      TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '1 = transmitiendo ahora mismo',
  `activo`       TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_trans_activo`   (`activo`),
  KEY `idx_trans_en_vivo`  (`en_vivo`),
  KEY `idx_trans_coords`   (`lat`, `lng`),
  KEY `idx_trans_tipo`     (`tipo`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Transmisiones en vivo: YouTube Live, radios online, audio streams';

-- Datos de ejemplo
INSERT IGNORE INTO `transmisiones`
  (`id`, `titulo`, `descripcion`, `tipo`, `stream_url`, `lat`, `lng`, `en_vivo`, `activo`)
VALUES
  (1, 'Radio Local en Vivo',
   'Transmision de radio local las 24hs con noticias y musica regional.',
   'radio_stream',
   'https://stream.zeno.fm/ejemplo',
   -34.6037, -58.3816, 0, 1),

  (2, 'Canal Municipal YouTube',
   'Sesiones del Consejo Municipal y eventos oficiales en vivo.',
   'youtube_live',
   'https://www.youtube.com/@municipio/live',
   -34.6050, -58.3900, 0, 1);
