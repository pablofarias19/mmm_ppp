-- ============================================================
-- CMS Multilingüe — Tablas de contenido técnico/avanzado
-- Ejecutar manualmente en MySQL/MariaDB.
-- Autor: Mapita CMS v1
-- ============================================================

-- ── 1) Páginas canónicas ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `cms_pages` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug`       VARCHAR(200) NOT NULL COMMENT 'Identificador URL único, ej: legal-assistance',
    `module`     VARCHAR(100) NOT NULL DEFAULT 'advanced' COMMENT 'Módulo: advanced, general, etc.',
    `status`     ENUM('draft','published') NOT NULL DEFAULT 'draft',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_cms_pages_slug` (`slug`),
    KEY `idx_cms_pages_module_status` (`module`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2) Traducciones por idioma/locale ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `cms_page_translations` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `page_id`          INT UNSIGNED NOT NULL,
    `lang`             VARCHAR(10)  NOT NULL COMMENT 'BCP-47 base o locale, ej: es, it, de-DE',
    `title`            VARCHAR(500) NOT NULL DEFAULT '',
    `body_md`          MEDIUMTEXT   NOT NULL COMMENT 'Contenido en Markdown',
    `summary`          TEXT                  COMMENT 'Resumen breve (opcional)',
    `is_machine_draft` TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1 = generado automáticamente, pendiente revisión',
    `review_status`    ENUM('needs_review','reviewed','legal_verified') NOT NULL DEFAULT 'needs_review',
    `updated_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_cpt_page_lang` (`page_id`, `lang`),
    KEY `idx_cpt_lang` (`lang`),
    KEY `idx_cpt_review` (`review_status`),
    CONSTRAINT `fk_cpt_page` FOREIGN KEY (`page_id`)
        REFERENCES `cms_pages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3) Glosario técnico por dominio e idioma ─────────────────────────────────
CREATE TABLE IF NOT EXISTS `cms_glossary_terms` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `domain`        VARCHAR(100) NOT NULL COMMENT 'legal | tax | strategy | web | branding',
    `term_key`      VARCHAR(200) NOT NULL COMMENT 'Clave estable, ej: nice_class, vat, opposition_period',
    `lang`          VARCHAR(10)  NOT NULL COMMENT 'BCP-47, ej: es, it, de',
    `term`          VARCHAR(500) NOT NULL COMMENT 'Término localizado',
    `definition_md` TEXT         NOT NULL COMMENT 'Definición completa en Markdown',
    `notes_md`      TEXT                  COMMENT 'Notas/matices adicionales (opcional)',
    `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_cgt_domain_key_lang` (`domain`, `term_key`, `lang`),
    KEY `idx_cgt_lang` (`lang`),
    KEY `idx_cgt_domain` (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
