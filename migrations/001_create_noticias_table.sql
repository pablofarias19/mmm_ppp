-- Migration: Create noticias table for FASE 4
-- Date: 2026-04-16

CREATE TABLE IF NOT EXISTS `noticias` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `titulo` VARCHAR(255) NOT NULL,
  `contenido` LONGTEXT NOT NULL,
  `imagen` VARCHAR(255),
  `categoria` VARCHAR(100) DEFAULT 'General',
  `user_id` INT,
  `vistas` INT DEFAULT 0,
  `activa` BOOLEAN DEFAULT 1,
  `fecha_publicacion` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_activa (activa),
  INDEX idx_fecha (fecha_publicacion),
  INDEX idx_categoria (categoria),
  INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
