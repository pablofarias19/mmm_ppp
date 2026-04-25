-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 25-04-2026 a las 13:40:17
-- Versión del servidor: 11.8.6-MariaDB-log
-- Versión de PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u580580751_map`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `analisis_marcario`
--

CREATE TABLE `analisis_marcario` (
  `id` int(11) NOT NULL,
  `marca_id` int(11) NOT NULL,
  `distintividad` enum('ALTA','MEDIA','BAJA') DEFAULT NULL,
  `riesgo_confusion` text DEFAULT NULL,
  `conflictos_clases` text DEFAULT NULL,
  `nivel_proteccion` varchar(255) DEFAULT NULL,
  `expansion_internacional` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `articulos`
--

CREATE TABLE `articulos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `contenido` longtext NOT NULL,
  `resumen` text DEFAULT NULL,
  `imagen_portada` varchar(255) DEFAULT NULL,
  `autor_id` int(11) DEFAULT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `fecha_publicacion` datetime DEFAULT NULL,
  `publicado` tinyint(1) DEFAULT 1,
  `vistas` int(11) DEFAULT 0,
  `tags` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `attachments`
--

CREATE TABLE `attachments` (
  `id` int(11) NOT NULL,
  `business_id` int(11) DEFAULT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `type` enum('photo','document','logo') DEFAULT 'photo',
  `uploaded_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `action` varchar(60) NOT NULL COMMENT 'create|update|delete|login|logout|resolve_report|...',
  `entity_type` varchar(40) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL COMMENT 'JSON con datos adicionales',
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `audit_log`
--

INSERT INTO `audit_log` (`id`, `user_id`, `username`, `action`, `entity_type`, `entity_id`, `details`, `ip`, `user_agent`, `created_at`) VALUES
(1, 5, 'Pablo_Farias', 'login', 'user', 5, '{\"username\":\"Pablo_Farias\"}', '201.235.95.238', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 18:19:16'),
(2, 5, 'Pablo_Farias', 'login', 'user', 5, '{\"username\":\"Pablo_Farias\"}', '201.235.95.238', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 18:13:38'),
(3, 5, 'Pablo_Farias', 'login', 'user', 5, '{\"username\":\"Pablo_Farias\"}', '201.235.95.238', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-23 22:22:09'),
(4, 5, 'Pablo_Farias', 'login', 'user', 5, '{\"username\":\"Pablo_Farias\"}', '201.235.95.238', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 15:54:51'),
(5, 5, 'Pablo_Farias', 'login', 'user', 5, '{\"username\":\"Pablo_Farias\"}', '201.235.95.238', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 18:43:46'),
(6, 5, 'Pablo_Farias', 'login', 'user', 5, '{\"username\":\"Pablo_Farias\"}', '181.9.226.160', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36', '2026-04-24 21:33:07'),
(7, 5, 'Pablo_Farias', 'logout', 'user', 5, NULL, '201.235.95.238', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 02:49:09'),
(8, 5, 'Pablo_Farias', 'login', 'user', 5, '{\"username\":\"Pablo_Farias\"}', '201.235.95.238', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 02:49:30'),
(9, 5, 'Pablo_Farias', 'toggle_consulta_habilitada', 'business', 9150, '{\"consulta_habilitada\":1}', '201.235.95.238', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 02:49:52'),
(10, 5, 'Pablo_Farias', 'toggle_consulta_siempre', 'business', 9150, '{\"consulta_siempre\":1}', '201.235.95.238', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 02:49:55'),
(11, 5, 'Pablo_Farias', 'login', 'user', 5, '{\"username\":\"Pablo_Farias\"}', '201.235.95.238', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-25 12:35:38');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `brands`
--

CREATE TABLE `brands` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `nombre` varchar(255) NOT NULL,
  `clase_principal` varchar(50) DEFAULT NULL,
  `rubro` varchar(255) DEFAULT NULL,
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  `website` varchar(500) DEFAULT NULL,
  `nivel_proteccion` varchar(50) DEFAULT NULL,
  `riesgo_oposicion` varchar(100) DEFAULT NULL,
  `valor_activo` varchar(50) DEFAULT NULL,
  `tiene_zona` tinyint(1) DEFAULT 0,
  `zona_radius_km` int(11) DEFAULT 10,
  `tiene_licencia` tinyint(1) DEFAULT 0,
  `licencia_detalle` varchar(255) DEFAULT NULL,
  `es_franquicia` tinyint(1) DEFAULT 0,
  `franchise_details` varchar(500) DEFAULT NULL,
  `zona_exclusiva` tinyint(1) DEFAULT 0,
  `zona_exclusiva_radius_km` int(11) DEFAULT 2,
  `scope` varchar(255) DEFAULT NULL,
  `channels` varchar(255) DEFAULT NULL,
  `annual_revenue` varchar(50) DEFAULT NULL,
  `founded_year` int(11) DEFAULT NULL,
  `extended_description` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `visible` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ubicacion` varchar(255) DEFAULT 'Argentina',
  `estado` varchar(50) DEFAULT 'Activa',
  `inpi_registrada` tinyint(1) NOT NULL DEFAULT 0,
  `inpi_numero` varchar(100) DEFAULT NULL,
  `inpi_fecha_registro` date DEFAULT NULL,
  `inpi_vencimiento` date DEFAULT NULL,
  `inpi_clases_registradas` varchar(255) DEFAULT NULL,
  `inpi_tipo` varchar(100) DEFAULT NULL,
  `historia_marca` longtext DEFAULT NULL,
  `target_audience` text DEFAULT NULL,
  `propuesta_valor` text DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `tiktok` varchar(255) DEFAULT NULL,
  `twitter` varchar(255) DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL,
  `youtube` varchar(255) DEFAULT NULL,
  `whatsapp` varchar(50) DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL COMMENT 'Ruta pública del logo del mapa',
  `mapita_id` varchar(64) DEFAULT NULL,
  `country_code` char(2) DEFAULT NULL COMMENT 'ISO 3166-1 alpha-2 del país de registro',
  `language_code` char(5) DEFAULT NULL COMMENT 'BCP 47 del idioma principal de la marca',
  `currency_code` char(3) DEFAULT NULL COMMENT 'Moneda del valor_activo (ISO 4217)',
  `registry_authority` varchar(50) DEFAULT NULL COMMENT 'Organismo registrador: INPI, USPTO, EUIPO, JPO…',
  `registry_number` varchar(100) DEFAULT NULL COMMENT 'Número de expediente genérico',
  `registry_date` date DEFAULT NULL COMMENT 'Fecha de registro (genérico)',
  `registry_expiry` date DEFAULT NULL COMMENT 'Fecha de vencimiento (genérico)',
  `registry_type` varchar(20) DEFAULT NULL COMMENT 'national|madrid_protocol|eu_trademark|us_federal',
  `crear_franquicia` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = la marca ofrece franquicias (habilita panel Franquicias)',
  `franquicia_descripcion` text DEFAULT NULL COMMENT 'Texto explicativo de la franquicia',
  `franquicia_condiciones` text DEFAULT NULL COMMENT 'Condiciones generales de la franquicia',
  `franquicia_exclusividad` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = con exclusividad territorial',
  `franquicia_territorio` text DEFAULT NULL COMMENT 'Ámbito territorial de la franquicia',
  `franquicia_productos` text DEFAULT NULL COMMENT 'Productos o servicios incluidos en la franquicia',
  `franquicia_garantias` text DEFAULT NULL COMMENT 'Garantías ofrecidas al franquiciado',
  `franquicia_url` varchar(500) DEFAULT NULL COMMENT 'URL con más información sobre la franquicia'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `brands`
--

INSERT INTO `brands` (`id`, `user_id`, `nombre`, `clase_principal`, `rubro`, `lat`, `lng`, `website`, `nivel_proteccion`, `riesgo_oposicion`, `valor_activo`, `tiene_zona`, `zona_radius_km`, `tiene_licencia`, `licencia_detalle`, `es_franquicia`, `franchise_details`, `zona_exclusiva`, `zona_exclusiva_radius_km`, `scope`, `channels`, `annual_revenue`, `founded_year`, `extended_description`, `description`, `visible`, `created_at`, `updated_at`, `ubicacion`, `estado`, `inpi_registrada`, `inpi_numero`, `inpi_fecha_registro`, `inpi_vencimiento`, `inpi_clases_registradas`, `inpi_tipo`, `historia_marca`, `target_audience`, `propuesta_valor`, `instagram`, `facebook`, `tiktok`, `twitter`, `linkedin`, `youtube`, `whatsapp`, `logo_url`, `mapita_id`, `country_code`, `language_code`, `currency_code`, `registry_authority`, `registry_number`, `registry_date`, `registry_expiry`, `registry_type`, `crear_franquicia`, `franquicia_descripcion`, `franquicia_condiciones`, `franquicia_exclusividad`, `franquicia_territorio`, `franquicia_productos`, `franquicia_garantias`, `franquicia_url`) VALUES
(1, 1, 'Nike Argentina', '25', 'Ropa Deportiva', -34.60370000, -58.38160000, NULL, NULL, NULL, NULL, 0, 10, 0, NULL, 0, NULL, 0, 2, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-16 07:07:40', '2026-04-16 07:07:40', 'Argentina', 'Activa', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, NULL, NULL, NULL, NULL),
(2, 1, 'Coca-Cola', '32', 'Bebidas', -34.60100000, -58.37300000, NULL, NULL, NULL, NULL, 0, 10, 0, NULL, 0, NULL, 0, 2, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-16 07:07:40', '2026-04-16 07:07:40', 'Argentina', 'Activa', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, NULL, NULL, NULL, NULL),
(3, 1, 'Quilmes', '32', 'Bebidas', -34.60800000, -58.38500000, NULL, NULL, NULL, NULL, 0, 10, 0, NULL, 0, NULL, 0, 2, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-16 07:07:40', '2026-04-16 07:07:40', 'Argentina', 'Activa', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, NULL, NULL, NULL, NULL),
(4, 1, 'Farmacity', '5', 'Farmacia', -34.59500000, -58.39000000, NULL, NULL, NULL, NULL, 0, 10, 0, NULL, 0, NULL, 0, 2, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-16 07:07:40', '2026-04-16 07:07:40', 'Argentina', 'Activa', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, NULL, NULL, NULL, NULL),
(5, 1, 'Adidas', '25', 'Ropa Deportiva', -34.60200000, -58.37600000, NULL, NULL, NULL, NULL, 0, 10, 0, NULL, 0, NULL, 0, 2, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-16 07:07:40', '2026-04-16 07:07:40', 'Argentina', 'Activa', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `brand_delegations`
--

CREATE TABLE `brand_delegations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `brand_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('admin') NOT NULL DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `brand_gallery`
--

CREATE TABLE `brand_gallery` (
  `id` int(11) NOT NULL,
  `brand_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `es_principal` tinyint(1) NOT NULL DEFAULT 0,
  `type` enum('photo','logo','document') DEFAULT 'photo',
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `orden` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `brand_gallery_v2`
--

CREATE TABLE `brand_gallery_v2` (
  `id` int(11) NOT NULL,
  `brand_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `es_principal` tinyint(1) NOT NULL DEFAULT 0,
  `orden` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `businesses`
--

CREATE TABLE `businesses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `lat` decimal(10,6) DEFAULT NULL,
  `lng` decimal(10,6) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `instagram` varchar(100) DEFAULT NULL,
  `facebook` varchar(100) DEFAULT NULL,
  `tiktok` varchar(100) DEFAULT NULL,
  `certifications` text DEFAULT NULL,
  `has_delivery` tinyint(1) DEFAULT 0,
  `has_card_payment` tinyint(1) DEFAULT 0,
  `is_franchise` tinyint(1) DEFAULT 0,
  `verified` tinyint(1) DEFAULT 0,
  `business_type` varchar(50) DEFAULT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum('active','inactive','pending') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `price_range` int(1) DEFAULT NULL COMMENT 'Rango de precio: 1-5 (económico a caro)',
  `description` text DEFAULT NULL,
  `subcategory_id` int(11) DEFAULT NULL,
  `company_size` enum('familiar','pyme','grande','multinacional') DEFAULT NULL,
  `location_city` varchar(50) DEFAULT NULL,
  `style` varchar(100) DEFAULT NULL,
  `mapita_id` varchar(64) DEFAULT NULL,
  `oferta_activa_id` int(10) UNSIGNED DEFAULT NULL,
  `disponibles_activo` tinyint(1) NOT NULL DEFAULT 0,
  `job_offer_active` tinyint(1) NOT NULL DEFAULT 0,
  `job_offer_position` varchar(255) DEFAULT NULL,
  `job_offer_description` text DEFAULT NULL,
  `job_offer_url` varchar(500) DEFAULT NULL COMMENT 'Link externo opcional',
  `og_image_url` varchar(255) DEFAULT NULL COMMENT 'URL pública de la imagen Open Graph del negocio',
  `es_proveedor` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = negocio marcado como Proveedor (P); solo negocios comerciales/industriales',
  `consulta_habilitada` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Admin designa: 1 = habilitado para recibir CONSULTA GENERAL (servicios especiales)',
  `consulta_siempre` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Admin: 1 = este negocio siempre entra en Consulta Masiva dentro del área',
  `proveedor_siempre` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Admin: 1 = este negocio P siempre entra en Consulta Global Proveedores',
  `timezone` varchar(64) NOT NULL DEFAULT 'America/Argentina/Buenos_Aires',
  `country_code` char(2) DEFAULT NULL COMMENT 'ISO 3166-1 alpha-2 (AR, US, DE…)',
  `language_code` char(5) DEFAULT NULL COMMENT 'BCP 47 (es-AR, en-US, ja-JP…)',
  `currency_code` char(3) DEFAULT NULL COMMENT 'ISO 4217 (ARS, USD, EUR, JPY…)',
  `phone_country_code` varchar(6) DEFAULT NULL COMMENT 'Prefijo internacional (+54, +1, +81…)',
  `address_format` varchar(20) DEFAULT NULL COMMENT 'Perfil de formato de dirección: ar|us|jp|eu',
  `oda_descripcion_proyecto` text DEFAULT NULL COMMENT 'Descripcion del proyecto (obra_de_arte)',
  `oda_requisitos` text DEFAULT NULL COMMENT 'Requisitos para participar (obra_de_arte)',
  `oda_roles_buscados` text DEFAULT NULL COMMENT 'JSON array de roles que busca (obra_de_arte)',
  `encuestas_override` enum('heredar','habilitada','deshabilitada') NOT NULL DEFAULT 'heredar' COMMENT 'Override de permiso de encuestas: heredar de industria, o forzar habilitada/deshabilitada'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `businesses`
--

INSERT INTO `businesses` (`id`, `user_id`, `name`, `address`, `lat`, `lng`, `phone`, `email`, `website`, `instagram`, `facebook`, `tiktok`, `certifications`, `has_delivery`, `has_card_payment`, `is_franchise`, `verified`, `business_type`, `visible`, `status`, `created_at`, `updated_at`, `price_range`, `description`, `subcategory_id`, `company_size`, `location_city`, `style`, `mapita_id`, `oferta_activa_id`, `disponibles_activo`, `job_offer_active`, `job_offer_position`, `job_offer_description`, `job_offer_url`, `og_image_url`, `es_proveedor`, `consulta_habilitada`, `consulta_siempre`, `proveedor_siempre`, `timezone`, `country_code`, `language_code`, `currency_code`, `phone_country_code`, `address_format`, `oda_descripcion_proyecto`, `oda_requisitos`, `oda_roles_buscados`, `encuestas_override`) VALUES
(9150, 5, 'María Celeste Ortiz', '6 de Agosto 331, CP 5000, Córdoba Capital', -31.371407, -64.178861, '+549-11-1566311985', 'propiedades@mariacelesteortiz.com.ar', 'https://www.mariacelesteortiz.com.ar', NULL, NULL, NULL, 'Estacionamiento, Acceso universal, Reservas online, Factura fiscal, Mercado Pago', 0, 1, 0, 0, 'inmobiliaria', 1, 'active', '2026-04-25 02:13:50', '2026-04-25 02:53:40', 3, 'Ofrecemos un análisis exhaustivo del valor de mercado de tus propiedades, garantizando la precisión y confiabilidad.', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 0, 1, 1, 0, 'America/Argentina/Buenos_Aires', 'AR', 'es', 'ARS', '+54', 'ar', NULL, NULL, NULL, 'heredar');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `business_categories`
--

CREATE TABLE `business_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `emoji` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `business_delegations`
--

CREATE TABLE `business_delegations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('admin') NOT NULL DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `business_emoji_groups`
--

CREATE TABLE `business_emoji_groups` (
  `id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `business_emoji_links`
--

CREATE TABLE `business_emoji_links` (
  `id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `emoji_id` int(11) NOT NULL,
  `relation_type` varchar(50) DEFAULT 'location',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `business_icons`
--

CREATE TABLE `business_icons` (
  `id` int(11) NOT NULL,
  `business_type` varchar(100) NOT NULL,
  `emoji` varchar(10) NOT NULL,
  `icon_class` varchar(100) DEFAULT NULL,
  `color` varchar(7) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `business_icons`
--

INSERT INTO `business_icons` (`id`, `business_type`, `emoji`, `icon_class`, `color`, `created_at`) VALUES
(1, 'comercio', '🛍️', 'icon-comercio', '#e74c3c', '2026-04-20 22:42:49'),
(2, 'hotel', '🏨', 'icon-hotel', '#3498db', '2026-04-20 22:42:49'),
(3, 'restaurante', '🍽️', 'icon-restaurante', '#e67e22', '2026-04-20 22:42:49'),
(4, 'inmobiliaria', '🏠', 'icon-inmobiliaria', '#27ae60', '2026-04-20 22:42:49'),
(5, 'farmacia', '💊', 'icon-farmacia', '#9b59b6', '2026-04-20 22:42:49'),
(6, 'gimnasio', '💪', 'icon-gimnasio', '#1abc9c', '2026-04-20 22:42:49'),
(7, 'cafeteria', '☕', 'icon-cafeteria', '#d35400', '2026-04-20 22:42:49'),
(8, 'academia', '📚', 'icon-academia', '#2980b9', '2026-04-20 22:42:49'),
(9, 'bar', '🍺', 'icon-bar', '#8e44ad', '2026-04-20 22:42:49'),
(10, 'salon_belleza', '💇', 'icon-salon', '#e91e63', '2026-04-20 22:42:49'),
(11, 'banco', '🏦', 'icon-banco', '#16a085', '2026-04-20 22:42:49'),
(12, 'tienda_ropa', '👕', 'icon-ropa', '#c0392b', '2026-04-20 22:42:49'),
(13, 'supermercado', '🛒', 'icon-super', '#8e44ad', '2026-04-20 22:42:49'),
(14, 'cine', '🎬', 'icon-cine', '#2980b9', '2026-04-20 22:42:49'),
(15, 'biblioteca', '📖', 'icon-biblioteca', '#27ae60', '2026-04-20 22:42:49'),
(16, 'parque', '🌳', 'icon-parque', '#16a085', '2026-04-20 22:42:49'),
(17, 'hospital', '🏥', 'icon-hospital', '#e74c3c', '2026-04-20 22:42:49'),
(18, 'escuela', '🎓', 'icon-escuela', '#3498db', '2026-04-20 22:42:49'),
(19, 'estacion', '🚂', 'icon-estacion', '#34495e', '2026-04-20 22:42:49'),
(20, 'gasolinera', '⛽', 'icon-gasolina', '#f39c12', '2026-04-20 22:42:49'),
(21, 'estacionamiento', '🅿️', 'icon-parking', '#95a5a6', '2026-04-20 22:42:49'),
(22, 'taxi', '🚕', 'icon-taxi', '#f1c40f', '2026-04-20 22:42:49'),
(23, 'carne', '🥩', 'icon-carne', '#e74c3c', '2026-04-20 22:42:49'),
(24, 'pescaderia', '🐟', 'icon-pescado', '#3498db', '2026-04-20 22:42:49'),
(25, 'panaderia', '🥐', 'icon-pan', '#d35400', '2026-04-20 22:42:49'),
(26, 'pasteleria', '🎂', 'icon-pastel', '#e91e63', '2026-04-20 22:42:49'),
(27, 'heladeria', '🍦', 'icon-helado', '#3498db', '2026-04-20 22:42:49'),
(28, 'fruteria', '🍎', 'icon-frutas', '#27ae60', '2026-04-20 22:42:49'),
(29, 'verduleria', '🥬', 'icon-verduras', '#27ae60', '2026-04-20 22:42:49'),
(30, 'bebidas', '🥤', 'icon-bebidas', '#9b59b6', '2026-04-20 22:42:49'),
(31, 'otros', '📍', 'icon-otros', '#667eea', '2026-04-20 22:42:49'),
(32, 'pizzeria', '🍕', 'icon-pizzeria', '#e74c3c', '2026-04-20 22:42:49'),
(33, 'indumentaria', '👕', 'icon-indumentaria', '#9b59b6', '2026-04-20 22:42:49'),
(34, 'muebleria', '🛋️', 'icon-muebleria', '#8e6914', '2026-04-20 22:42:49'),
(35, 'floristeria', '💐', 'icon-floristeria', '#e91e63', '2026-04-20 22:42:49'),
(36, 'libreria', '📖', 'icon-libreria', '#1abc9c', '2026-04-20 22:42:49'),
(37, 'odontologia', '🦷', 'icon-odontologia', '#3498db', '2026-04-20 22:42:49'),
(38, 'veterinaria', '🐾', 'icon-veterinaria', '#27ae60', '2026-04-20 22:42:49'),
(39, 'optica', '👓', 'icon-optica', '#2980b9', '2026-04-20 22:42:49'),
(40, 'barberia', '💈', 'icon-barberia', '#c0392b', '2026-04-20 22:42:49'),
(41, 'spa', '💆', 'icon-spa', '#9b59b6', '2026-04-20 22:42:49'),
(42, 'seguros', '🛡️', 'icon-seguros', '#2980b9', '2026-04-20 22:42:49'),
(43, 'abogado', '⚖️', 'icon-abogado', '#34495e', '2026-04-20 22:42:49'),
(44, 'contador', '📊', 'icon-contador', '#2c3e50', '2026-04-20 22:42:49'),
(45, 'taller', '🔩', 'icon-taller', '#7f8c8d', '2026-04-20 22:42:49'),
(46, 'remate', '🔨', 'icon-remate', '#d35400', '2026-04-20 22:42:49'),
(47, 'construccion', '🏗️', 'icon-construccion', '#e67e22', '2026-04-20 22:42:49'),
(48, 'turismo', '✈️', 'icon-turismo', '#16a085', '2026-04-20 22:42:49'),
(49, 'electronica', '📱', 'icon-electronica', '#2980b9', '2026-04-20 22:42:49'),
(50, 'autos_venta', '🚗', 'icon-autos-venta', '#2980b9', '2026-04-20 22:42:49'),
(51, 'motos_venta', '🏍️', 'icon-motos-venta', '#8e44ad', '2026-04-20 22:42:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `business_images`
--

CREATE TABLE `business_images` (
  `id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `business_subcategories`
--

CREATE TABLE `business_subcategories` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Generales'),
(2, 'Gastronomía'),
(3, 'Bienestar'),
(4, 'Servicios Profesionales'),
(5, 'Salud'),
(6, 'Hogar'),
(7, 'Entretenimiento');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `certificaciones_profesionales`
--

CREATE TABLE `certificaciones_profesionales` (
  `id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `nombre_certificacion` varchar(200) NOT NULL,
  `institucion_emisora` varchar(200) DEFAULT NULL,
  `pais_emision` varchar(100) DEFAULT NULL,
  `fecha_obtencion` date DEFAULT NULL,
  `fecha_expiracion` date DEFAULT NULL COMMENT 'NULL si no expira',
  `numero_credencial` varchar(100) DEFAULT NULL COMMENT 'Número de matrícula o credencial',
  `url_verificacion` varchar(255) DEFAULT NULL COMMENT 'Link para verificar la certificación',
  `imagen_certificado` varchar(255) DEFAULT NULL COMMENT 'Path a imagen del certificado escaneado',
  `tipo` enum('titulo_universitario','posgrado','certificacion_tecnica','curso_especializado','licencia_profesional','otro') NOT NULL,
  `area` varchar(150) DEFAULT NULL COMMENT 'Área de especialización',
  `verificado` tinyint(1) DEFAULT 0 COMMENT 'Si fue verificado por administrador',
  `verificado_por` int(11) DEFAULT NULL COMMENT 'ID del admin que verificó',
  `fecha_verificacion` timestamp NULL DEFAULT NULL,
  `notas_verificacion` text DEFAULT NULL,
  `destacada` tinyint(1) DEFAULT 0 COMMENT 'Mostrar como destacada en el perfil',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Certificaciones y credenciales profesionales';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clasificacion_niza`
--

CREATE TABLE `clasificacion_niza` (
  `id` int(11) NOT NULL,
  `marca_id` int(11) NOT NULL,
  `clase_principal` int(11) DEFAULT NULL,
  `clases_complementarias` varchar(255) DEFAULT NULL,
  `riesgo_colision` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comercios`
--

CREATE TABLE `comercios` (
  `id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `tipo_comercio` varchar(100) DEFAULT NULL,
  `horario_apertura` time DEFAULT NULL,
  `horario_cierre` time DEFAULT NULL,
  `dias_cierre` varchar(100) DEFAULT NULL,
  `timezone` varchar(64) NOT NULL DEFAULT 'America/Argentina/Buenos_Aires',
  `categorias_productos` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `comercios`
--

INSERT INTO `comercios` (`id`, `business_id`, `tipo_comercio`, `horario_apertura`, `horario_cierre`, `dias_cierre`, `timezone`, `categorias_productos`) VALUES
(9, 9150, 'Alquiler - Venta - Tasación', '09:00:00', '16:00:00', 'Sábado,Domingo', 'America/Argentina/Buenos_Aires', 'alquiler,venta,tasaciones,departamentos,casas,locales,oficinas,ph');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compras_paquetes`
--

CREATE TABLE `compras_paquetes` (
  `id` int(11) NOT NULL,
  `paquete_id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL COMMENT 'ID del usuario comprador (NULL si compró sin login)',
  `business_id` int(11) NOT NULL,
  `nombre_cliente` varchar(150) DEFAULT NULL,
  `email_cliente` varchar(150) DEFAULT NULL,
  `telefono_cliente` varchar(20) DEFAULT NULL,
  `precio_pagado` decimal(10,2) NOT NULL,
  `fecha_compra` timestamp NULL DEFAULT current_timestamp(),
  `fecha_inicio_vigencia` date NOT NULL,
  `fecha_fin_vigencia` date DEFAULT NULL,
  `sesiones_totales` int(11) NOT NULL,
  `sesiones_consumidas` int(11) DEFAULT 0,
  `sesiones_restantes` int(11) GENERATED ALWAYS AS (`sesiones_totales` - `sesiones_consumidas`) STORED COMMENT 'Campo calculado',
  `estado` enum('activo','pausado','completado','expirado','cancelado') DEFAULT 'activo',
  `fecha_pausa` timestamp NULL DEFAULT NULL COMMENT 'Fecha en que se pausó',
  `motivo_pausa` text DEFAULT NULL,
  `fecha_completado` timestamp NULL DEFAULT NULL,
  `fecha_expiracion` timestamp NULL DEFAULT NULL,
  `fecha_cancelacion` timestamp NULL DEFAULT NULL,
  `motivo_cancelacion` text DEFAULT NULL,
  `metodo_pago` enum('efectivo','transferencia','tarjeta','mercadopago','otro') DEFAULT NULL,
  `comprobante_pago` varchar(255) DEFAULT NULL COMMENT 'URL o ID del comprobante',
  `renovacion_automatica` tinyint(1) DEFAULT 0,
  `fecha_proxima_renovacion` date DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Compras de paquetes por clientes';

--
-- Disparadores `compras_paquetes`
--
DELIMITER $$
CREATE TRIGGER `tr_actualizar_estado_paquete` BEFORE UPDATE ON `compras_paquetes` FOR EACH ROW BEGIN
    -- Si se consumieron todas las sesiones, marcar como completado
    IF NEW.sesiones_consumidas >= NEW.sesiones_totales AND OLD.estado = 'activo' THEN
        SET NEW.estado = 'completado';
        SET NEW.fecha_completado = CURRENT_TIMESTAMP;
    END IF;

    -- Si se pasó la fecha de vigencia, marcar como expirado
    IF NEW.fecha_fin_vigencia IS NOT NULL
       AND NEW.fecha_fin_vigencia < CURDATE()
       AND NEW.estado = 'activo' THEN
        SET NEW.estado = 'expirado';
        SET NEW.fecha_expiracion = CURRENT_TIMESTAMP;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `consultas_destinatarios`
--

CREATE TABLE `consultas_destinatarios` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `consulta_id` bigint(20) UNSIGNED NOT NULL,
  `business_id` int(11) NOT NULL,
  `notificado` tinyint(1) NOT NULL DEFAULT 0,
  `leido_en` datetime DEFAULT NULL COMMENT 'Cuándo el propietario del negocio lo leyó'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `consultas_masivas`
--

CREATE TABLE `consultas_masivas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Usuario que origina la consulta',
  `tipo` enum('masiva','general','global_proveedor','envio') NOT NULL COMMENT 'masiva=geo+todos; general=servicios habilitados; global_proveedor=rubro P; envio=transportistas geo',
  `rubro` varchar(100) DEFAULT NULL COMMENT 'Para tipo=global_proveedor: business_type destino',
  `geo_bounds` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '{north,south,east,west} — para masiva y envio' CHECK (json_valid(`geo_bounds`)),
  `texto` varchar(500) NOT NULL COMMENT 'Texto de la consulta enviada',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `consultas_respuestas`
--

CREATE TABLE `consultas_respuestas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `consulta_id` bigint(20) UNSIGNED NOT NULL,
  `business_id` int(11) NOT NULL COMMENT 'Negocio que responde',
  `user_id` int(11) NOT NULL COMMENT 'Propietario/responsable que escribe la respuesta',
  `texto` varchar(500) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `content_reports`
--

CREATE TABLE `content_reports` (
  `id` int(10) UNSIGNED NOT NULL,
  `reporter_user_id` int(11) DEFAULT NULL,
  `reporter_ip` varchar(45) DEFAULT NULL,
  `content_type` varchar(30) NOT NULL COMMENT 'review|business|noticia|evento|oferta|trivia|encuesta|transmision',
  `content_id` int(10) UNSIGNED NOT NULL,
  `reason` varchar(60) NOT NULL COMMENT 'spam|inappropriate|fake|harassment|other',
  `description` text DEFAULT NULL,
  `status` enum('pending','reviewing','resolved','dismissed') NOT NULL DEFAULT 'pending',
  `resolved_by` int(11) DEFAULT NULL COMMENT 'user_id del admin que resolvió',
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolution_note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `convocatorias`
--

CREATE TABLE `convocatorias` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_id` int(11) NOT NULL COMMENT 'Negocio OBRA DE ARTE convocante',
  `user_id` int(11) NOT NULL COMMENT 'Usuario titular',
  `fecha_inicio` datetime NOT NULL,
  `fecha_fin` datetime NOT NULL,
  `roles_requeridos` text NOT NULL COMMENT 'JSON array de business_type roles',
  `estado` enum('activa','cerrada','cancelada') NOT NULL DEFAULT 'activa',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `convocatoria_destinatarios`
--

CREATE TABLE `convocatoria_destinatarios` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `convocatoria_id` bigint(20) UNSIGNED NOT NULL,
  `business_id` int(11) NOT NULL COMMENT 'Negocio/servicio convocado',
  `notificado_wt` tinyint(1) NOT NULL DEFAULT 0,
  `notificado_mail` tinyint(1) NOT NULL DEFAULT 0,
  `leido_en` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cursos`
--

CREATE TABLE `cursos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` longtext DEFAULT NULL,
  `instructor_id` int(11) DEFAULT NULL,
  `zoom_meeting_id` varchar(255) DEFAULT NULL,
  `zoom_start_time` datetime DEFAULT NULL,
  `zoom_duration_minutes` int(11) DEFAULT NULL,
  `max_participantes` int(11) DEFAULT NULL,
  `estado` enum('programado','en_vivo','finalizado','cancelado') DEFAULT NULL,
  `grabacion_url` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `curso_inscripciones`
--

CREATE TABLE `curso_inscripciones` (
  `id` int(11) NOT NULL,
  `curso_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `estado` enum('inscrito','asistio','certificado','abandono') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `disponibles_items`
--

CREATE TABLE `disponibles_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `business_id` int(11) NOT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `precio_a_definir` tinyint(1) NOT NULL DEFAULT 0,
  `cantidad` smallint(5) UNSIGNED DEFAULT NULL,
  `tipo_bien` varchar(30) DEFAULT NULL,
  `disponible_desde` date DEFAULT NULL,
  `disponible_hasta` date DEFAULT NULL,
  `horario_inicio` time DEFAULT NULL,
  `horario_fin` time DEFAULT NULL,
  `servicio` varchar(45) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `orden` smallint(6) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `disponibles_solicitudes`
--

CREATE TABLE `disponibles_solicitudes` (
  `id` int(10) UNSIGNED NOT NULL,
  `business_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `estado` enum('pendiente','confirmada','desistida') NOT NULL DEFAULT 'pendiente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `disponibles_solicitud_items`
--

CREATE TABLE `disponibles_solicitud_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `solicitud_id` int(10) UNSIGNED NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `seleccionado` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `emoji_favorites`
--

CREATE TABLE `emoji_favorites` (
  `id` int(11) NOT NULL,
  `user_id` varchar(100) NOT NULL,
  `emoji_id` int(11) NOT NULL,
  `added_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `emoji_groups`
--

CREATE TABLE `emoji_groups` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(20) DEFAULT '#3388ff' COMMENT 'Color para visualización del grupo',
  `is_public` tinyint(1) DEFAULT 0 COMMENT 'Indica si el grupo es público',
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `emoji_groups`
--

INSERT INTO `emoji_groups` (`id`, `name`, `description`, `color`, `is_public`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Comida', 'Lugares para comer', '#FF5722', 0, 'pablofarias19', '2025-03-18 21:22:53', '2025-03-18 21:22:53'),
(2, 'Educación', 'Instituciones educativas', '#2196F3', 0, 'pablofarias19', '2025-03-18 21:22:53', '2025-03-18 21:22:53'),
(3, 'Recreación', 'Lugares para divertirse', '#4CAF50', 0, 'pablofarias19', '2025-03-18 21:22:53', '2025-03-18 21:22:53');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `emoji_group_members`
--

CREATE TABLE `emoji_group_members` (
  `group_id` int(11) NOT NULL,
  `emoji_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `emoji_history`
--

CREATE TABLE `emoji_history` (
  `id` int(11) NOT NULL,
  `entity_type` enum('emoji','group','relation') NOT NULL,
  `entity_id` int(11) NOT NULL,
  `action` enum('create','update','delete') NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `user` varchar(100) DEFAULT NULL,
  `action_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `emoji_images`
--

CREATE TABLE `emoji_images` (
  `id` int(11) NOT NULL,
  `emoji_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `created_by_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `emoji_markers`
--

CREATE TABLE `emoji_markers` (
  `id` int(11) NOT NULL,
  `symbol` varchar(10) NOT NULL DEFAULT '?',
  `title` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `lat` decimal(10,6) NOT NULL,
  `lng` decimal(10,6) NOT NULL,
  `style` varchar(50) DEFAULT 'sin_fondo',
  `protection` varchar(20) DEFAULT 'public',
  `password` varchar(255) DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `price_range` int(1) DEFAULT NULL COMMENT 'Rango de precio: 1-5 (económico a caro)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `emoji_markers`
--

INSERT INTO `emoji_markers` (`id`, `symbol`, `title`, `description`, `lat`, `lng`, `style`, `protection`, `password`, `created_by`, `created_at`, `updated_at`, `price_range`) VALUES
(3, '🏫', 'Universidad', 'Campus universitario', -34.617841, -58.368101, 'luminoso_celeste', 'public', NULL, 'pablofarias19', '2025-03-18 21:24:00', '2025-03-18 21:24:00', NULL),
(4, '🏞️', 'Parque', 'Parque para pasear', -34.606714, -58.391188, 'sin_fondo', 'public', NULL, 'pablofarias19', '2025-03-18 21:24:00', '2025-03-18 21:24:00', NULL),
(5, '📍', '25 de agosto 3828', 'lululull', -34.376312, -58.841743, 'sin_fondo', 'public', NULL, NULL, '2025-03-19 01:20:36', '2025-03-19 01:20:36', NULL),
(6, '📍', 'Zapatos de Vestir', '¿que lindos zapatos?', -34.633208, -58.651886, 'sin_fondo', 'public', NULL, NULL, '2025-03-19 15:33:39', '2025-03-19 15:33:39', NULL),
(7, '📍', 'ACA VIVIA LUCIA', 'LA LUCIA', -34.463117, -58.512111, 'sin_fondo', 'public', NULL, NULL, '2025-03-19 15:35:40', '2025-03-19 15:35:40', NULL),
(8, '😍', 'Emoji 😍', 'Emoji colocado en -34.626428, -59.030914', -34.626428, -59.030914, 'sin_fondo', 'public', NULL, NULL, '2025-03-19 21:44:14', '2025-03-19 21:44:14', NULL),
(9, '📍', 'VENTA CASA BARRIO PANAMERICANO', 'hjhjhk', -31.248911, -64.501419, 'sin_fondo', 'public', NULL, NULL, '2025-03-19 22:22:53', '2025-03-19 22:22:53', NULL),
(10, '📍', 'PRUEBA 2', 'eSTAMOS TRABAJANADO', -31.232472, -64.496269, 'sin_fondo', 'public', NULL, NULL, '2025-03-20 01:37:57', '2025-03-20 01:37:57', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `emoji_relations`
--

CREATE TABLE `emoji_relations` (
  `id` int(11) NOT NULL,
  `from_id` int(11) NOT NULL,
  `to_id` int(11) NOT NULL,
  `group_id` int(11) DEFAULT NULL,
  `label` varchar(100) DEFAULT NULL COMMENT 'Etiqueta descriptiva de la relación',
  `line_style` varchar(50) DEFAULT NULL COMMENT 'Estilo visual para la línea de relación',
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `encuestas`
--

CREATE TABLE `encuestas` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `lat` decimal(10,8) NOT NULL COMMENT 'Latitud de la encuesta',
  `lng` decimal(11,8) NOT NULL COMMENT 'Longitud de la encuesta',
  `fecha_creacion` date NOT NULL,
  `fecha_expiracion` date DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL COMMENT 'Link externo a la encuesta',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `detalle_activo` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = habilitar panel Detalle con gráficos; 0 = solo popup',
  `graficos_config` varchar(255) NOT NULL DEFAULT 'barras,torta,tendencia' COMMENT 'Lista CSV de tipos de gráfico habilitados: barras, torta, tendencia'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `encuestas`
--

INSERT INTO `encuestas` (`id`, `titulo`, `descripcion`, `lat`, `lng`, `fecha_creacion`, `fecha_expiracion`, `link`, `activo`, `created_at`, `updated_at`, `detalle_activo`, `graficos_config`) VALUES
(1, 'Opinión sobre transporte público', '¿Qué opinas sobre el servicio de transporte en la zona?', -34.60123400, -58.37567800, '2025-03-01', '2025-05-01', 'https://forms.example.com/transportesurvey', 0, '2025-03-30 06:03:57', '2026-04-25 00:29:00', 1, 'barras,torta,tendencia'),
(2, 'Encuesta de satisfacción comercial', 'Ayúdanos a mejorar la experiencia comercial', -34.60876500, -58.39234500, '2025-03-15', '2025-04-30', 'https://forms.example.com/comerciosurvey', 1, '2025-03-30 06:03:57', '2025-03-30 06:03:57', 1, 'barras,torta,tendencia'),
(3, 'Preferencias de ocio', '¿Qué actividades te gustaría ver en el barrio?', -34.59543200, -58.41098700, '2025-03-20', '2025-05-15', 'https://forms.example.com/ociosurvey', 1, '2025-03-30 06:03:57', '2025-03-30 06:03:57', 1, 'barras,torta,tendencia'),
(4, 'Opinión sobre candidatos imaginarios', '¿Qué opinás de los candidatos A y B? Respondé esta breve encuesta.', -34.60000000, -58.37000000, '2025-03-30', '2025-12-31', 'https://tusitio.com/encuesta.php?id=2', 1, '2025-03-30 07:00:05', '2025-03-30 07:00:05', 1, 'barras,torta,tendencia'),
(5, 'Votaciones Legislativas', 'Vamos a decir lo que pensamos', -31.23834350, -64.47077750, '2025-03-30', '2025-04-08', 'https://mapita.com.ar/submapita/encuestas/encuesta.php?id=5', 1, '2025-03-30 07:18:11', '2025-04-07 02:39:53', 1, 'barras,torta,tendencia');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `encuestas_zona`
--

CREATE TABLE `encuestas_zona` (
  `id` int(11) NOT NULL,
  `zona` varchar(100) NOT NULL,
  `lat` decimal(10,6) NOT NULL,
  `lng` decimal(10,6) NOT NULL,
  `radio_m` int(11) DEFAULT 300,
  `pregunta` text NOT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `link` varchar(255) NOT NULL,
  `activa` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `encuesta_participaciones`
--

CREATE TABLE `encuesta_participaciones` (
  `id` int(11) NOT NULL,
  `encuesta_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `fecha_participacion` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `encuesta_participaciones`
--

INSERT INTO `encuesta_participaciones` (`id`, `encuesta_id`, `user_id`, `fecha_participacion`) VALUES
(1, 5, 2, '2025-03-31 06:29:04'),
(5, 5, 4, '2025-04-01 03:45:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `encuesta_questions`
--

CREATE TABLE `encuesta_questions` (
  `id` int(11) NOT NULL,
  `encuesta_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `tipo` enum('text','multiple','rating') DEFAULT 'multiple',
  `orden` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `encuesta_responses`
--

CREATE TABLE `encuesta_responses` (
  `id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `response_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `entidad_relaciones`
--

CREATE TABLE `entidad_relaciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `source_entity_type` varchar(20) NOT NULL,
  `source_entity_id` int(11) NOT NULL,
  `source_mapita_id` varchar(64) DEFAULT NULL,
  `target_entity_type` varchar(20) NOT NULL,
  `target_entity_id` int(11) NOT NULL,
  `target_mapita_id` varchar(64) DEFAULT NULL,
  `relation_type` varchar(50) NOT NULL DEFAULT 'relacionado',
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estrategia_optima`
--

CREATE TABLE `estrategia_optima` (
  `id` int(11) NOT NULL,
  `marca_id` int(11) NOT NULL,
  `camino_recomendado` varchar(255) DEFAULT NULL,
  `secuencia_acciones` text DEFAULT NULL,
  `inversion_requerida` varchar(255) DEFAULT NULL,
  `horizonte_temporal` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eventos`
--

CREATE TABLE `eventos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `hora` time DEFAULT NULL,
  `organizador` varchar(255) DEFAULT NULL,
  `lat` decimal(10,8) NOT NULL COMMENT 'Latitud del evento',
  `lng` decimal(11,8) NOT NULL COMMENT 'Longitud del evento',
  `dest_lat` decimal(10,8) DEFAULT NULL COMMENT 'Latitud del destino (si aplica)',
  `dest_lng` decimal(11,8) DEFAULT NULL COMMENT 'Longitud del destino (si aplica)',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `youtube_link` varchar(255) DEFAULT NULL COMMENT 'URL del video de YouTube para el evento',
  `categoria` varchar(100) DEFAULT 'General',
  `ubicacion` varchar(255) DEFAULT NULL,
  `mapita_id` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `eventos`
--

INSERT INTO `eventos` (`id`, `titulo`, `descripcion`, `fecha`, `hora`, `organizador`, `lat`, `lng`, `dest_lat`, `dest_lng`, `activo`, `created_at`, `updated_at`, `youtube_link`, `categoria`, `ubicacion`, `mapita_id`) VALUES
(1, 'Feria Gastronómica', 'Gran feria con los mejores platos de la región', '2025-04-15', '10:00:00', 'Asociación de Restaurantes', -34.60414180, -58.38342910, -34.60895600, -58.37522900, 1, '2025-03-30 06:03:45', '2026-04-19 19:29:50', '', 'General', '', NULL),
(2, 'Workshop de Marketing Digital', 'Aprende las últimas tendencias en marketing', '2025-04-20', '14:30:00', 'Digital Academy', -34.59895600, -58.37022900, -34.59595600, -58.36522900, 1, '2025-03-30 06:03:45', '2026-04-19 19:30:02', '', 'General', '', NULL),
(3, 'Concierto al aire libre', 'Bandas locales tocarán en vivo', '2025-04-25', '19:00:00', 'Municipalidad', -34.60681700, -58.43575100, -34.60681700, -58.43575100, 1, '2025-03-30 06:03:45', '2026-04-19 07:07:54', '', 'General', '', NULL),
(4, 'Comparza Mari Mari', 'La comparsa Marí Marí nació en 1981 en Gualeguaychú, bajo la dirección de Nelita Bermudez. \nEl nombre Marí Marí significa \"buen día\" o \"el amanecer\". \nFelicita Fouce fue reina de la comparsa Marí Marí y luego fue coronada como soberana del Carnaval del País de Gualeguaychú. Este año estuvo, una vez más, en la localidad de Cosquín, Córdoba.', '2026-04-20', '00:00:00', '', -31.23719050, -64.46409340, -31.23719050, -64.46409340, 1, '2025-04-01 00:48:27', '2026-04-21 02:45:53', 'https://youtu.be/787Alo6PA9w?si=L1ibSN-68V0rFtgF', 'General', '', ''),
(5, 'Tragico Terremoto en Myanmar', 'La junta militar de Myanmar declaró este lunes 31 de marzo una semana de luto nacional por el terremoto del pasado viernes, cuya cifra de muertos ascendió a 2.065, mientras en Tailandia el número de víctimas mortales aumentó a un total de 19. Las autoridades intensifican los esfuerzos para tratar de hallar sobrevivientes bajo los escombros tras el movimiento telúrico de 7,7 de magnitud.', '2025-03-31', '00:00:00', '', 22.40785460, 96.39404300, 22.40785460, 96.39404300, 1, '2025-04-01 02:30:56', '2026-04-19 07:07:43', 'https://youtu.be/ela97UPTSrY?si=zS9-7Nglktx0-7Tj', 'General', '', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horarios_disponibles`
--

CREATE TABLE `horarios_disponibles` (
  `id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `dia_semana` tinyint(4) NOT NULL COMMENT '0=domingo, 1=lunes, 2=martes, 3=miércoles, 4=jueves, 5=viernes, 6=sábado',
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `tipo_sesion` enum('individual','grupal','taller','evaluacion','clase_prueba') DEFAULT 'individual',
  `cupos_disponibles` int(11) DEFAULT 1 COMMENT 'Cupos totales para este horario',
  `fecha_inicio_vigencia` date DEFAULT NULL COMMENT 'Fecha desde la cual aplica este horario',
  `fecha_fin_vigencia` date DEFAULT NULL COMMENT 'Fecha hasta la cual aplica este horario',
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Horarios disponibles semanales recurrentes';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `hoteles`
--

CREATE TABLE `hoteles` (
  `id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `numero_habitaciones` int(11) DEFAULT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL,
  `servicios` text DEFAULT NULL,
  `precio_noche` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `industrial_sectors`
--

CREATE TABLE `industrial_sectors` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('mineria','energia','agro','infraestructura','inmobiliario','industrial') NOT NULL,
  `subtype` varchar(100) DEFAULT NULL,
  `geometry` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'GeoJSON (Feature o Geometry)' CHECK (json_valid(`geometry`)),
  `status` enum('proyecto','activo','potencial') NOT NULL DEFAULT 'potencial',
  `investment_level` enum('bajo','medio','alto') NOT NULL DEFAULT 'medio',
  `risk_level` enum('bajo','medio','alto') NOT NULL DEFAULT 'medio',
  `jurisdiction` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `industries`
--

CREATE TABLE `industries` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT 'Usuario propietario',
  `industrial_sector_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK a industrial_sectors (catálogo)',
  `business_id` int(11) DEFAULT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `website` varchar(500) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `country_code` char(2) DEFAULT NULL COMMENT 'ISO 3166-1 alpha-2',
  `language_code` char(5) DEFAULT NULL COMMENT 'BCP 47 del idioma principal',
  `currency_code` char(3) DEFAULT NULL COMMENT 'ISO 4217 — moneda de referencia',
  `region` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `employees_range` enum('1-10','11-50','51-200','201-500','500+') DEFAULT NULL COMMENT 'Rango de empleados',
  `annual_revenue` enum('micro','pequeña','mediana','grande','corporación') DEFAULT NULL COMMENT 'Escala de la industria',
  `certifications` text DEFAULT NULL COMMENT 'Certificaciones separadas por coma',
  `naics_code` varchar(20) DEFAULT NULL COMMENT 'Código NAICS (opcional)',
  `isic_code` varchar(20) DEFAULT NULL COMMENT 'Código ISIC (opcional)',
  `nace_code` varchar(20) DEFAULT NULL COMMENT 'Clasificador NACE Rev. 2 (Europa)',
  `ciiu_code` varchar(20) DEFAULT NULL COMMENT 'Clasificador CIIU/ISIC Rev. 4 (OIT/LATAM)',
  `status` enum('borrador','activa','archivada') NOT NULL DEFAULT 'borrador',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `encuestas_permitidas` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = la industria puede crear encuestas; 0 = no puede'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inmobiliarias`
--

CREATE TABLE `inmobiliarias` (
  `id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `tipos_propiedades` text DEFAULT NULL,
  `zonas_operacion` text DEFAULT NULL,
  `comision` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inmuebles`
--

CREATE TABLE `inmuebles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_id` int(11) NOT NULL COMMENT 'ID de la inmobiliaria',
  `operacion` enum('venta','alquiler') NOT NULL DEFAULT 'venta',
  `titulo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(15,2) DEFAULT NULL,
  `moneda` varchar(10) NOT NULL DEFAULT 'ARS',
  `direccion` varchar(500) DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `foto_url` varchar(500) DEFAULT NULL,
  `contacto` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `job_applications`
--

CREATE TABLE `job_applications` (
  `id` int(10) UNSIGNED NOT NULL,
  `business_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Login obligatorio — NOT NULL',
  `applicant_name` varchar(255) NOT NULL,
  `applicant_email` varchar(255) NOT NULL,
  `applicant_phone` varchar(50) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `estado` enum('pendiente','vista','aceptada','rechazada') NOT NULL DEFAULT 'pendiente',
  `consent` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `marcadores`
--

CREATE TABLE `marcadores` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `lat` decimal(10,6) NOT NULL,
  `lng` decimal(10,6) NOT NULL,
  `created_at` datetime NOT NULL,
  `created_by` varchar(100) NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `marcadores`
--

INSERT INTO `marcadores` (`id`, `titulo`, `descripcion`, `lat`, `lng`, `created_at`, `created_by`, `updated_at`, `updated_by`) VALUES
(1, 'Casa de Lulu', 'Muy linda casa', -34.611312, -58.407784, '2025-03-18 19:58:31', 'pablofarias19', NULL, NULL),
(2, 'VENTA CASA BARRIO PANAMERICANO', '456', -34.573440, -58.452415, '2025-03-18 19:58:31', 'pablofarias19', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `marcas`
--

CREATE TABLE `marcas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `rubro` varchar(255) DEFAULT NULL,
  `ubicacion` varchar(255) DEFAULT NULL,
  `estado` enum('IDEA','EN USO','REGISTRADA') NOT NULL,
  `scope` varchar(100) DEFAULT NULL,
  `channels` varchar(255) DEFAULT NULL,
  `annual_revenue` varchar(50) DEFAULT NULL,
  `founded_year` int(11) DEFAULT NULL,
  `extended_description` longtext DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  `tiene_zona` tinyint(1) NOT NULL DEFAULT 0,
  `zona_radius_km` int(11) DEFAULT 10,
  `tiene_licencia` tinyint(1) NOT NULL DEFAULT 0,
  `licencia_detalle` varchar(255) DEFAULT NULL,
  `es_franquicia` tinyint(1) NOT NULL DEFAULT 0,
  `franchise_details` varchar(255) DEFAULT NULL,
  `zona_exclusiva` tinyint(1) NOT NULL DEFAULT 0,
  `zona_exclusiva_radius_km` int(11) DEFAULT 2,
  `logo_url` varchar(255) DEFAULT NULL COMMENT 'Ruta pública del logo del mapa',
  `mapita_id` varchar(64) DEFAULT NULL,
  `og_image_url` varchar(255) DEFAULT NULL COMMENT 'URL pública de la imagen Open Graph de la marca'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modelos_negocio`
--

CREATE TABLE `modelos_negocio` (
  `id` int(11) NOT NULL,
  `marca_id` int(11) NOT NULL,
  `tipo` enum('EXPLOTACION_DIRECTA','LICENCIAMIENTO','FRANQUICIA','MARCA_BLANCA','ACTIVO_DIGITAL') DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `monetizacion`
--

CREATE TABLE `monetizacion` (
  `id` int(11) NOT NULL,
  `marca_id` int(11) NOT NULL,
  `fuentes_ingresos` text DEFAULT NULL,
  `escalabilidad` varchar(255) DEFAULT NULL,
  `margen_potencial` varchar(255) DEFAULT NULL,
  `valor_activo` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `negocios_radio_operacion`
--

CREATE TABLE `negocios_radio_operacion` (
  `id` int(11) NOT NULL,
  `negocio_id` int(11) NOT NULL COMMENT 'ID del negocio relacionado',
  `categoria_servicio` enum('transporte','reparto','servicios','comercio') NOT NULL DEFAULT 'transporte',
  `radio_operacion` int(11) NOT NULL COMMENT 'Radio de operación en metros',
  `centro_lat` decimal(10,8) NOT NULL,
  `centro_lng` decimal(11,8) NOT NULL,
  `disponible` tinyint(1) DEFAULT 1,
  `horas_operacion` varchar(100) DEFAULT NULL COMMENT 'Formato: "L-V: 9-18, S: 10-14"',
  `capacidad_maxima` int(11) DEFAULT 1,
  `carga_trabajo_actual` int(11) DEFAULT 0,
  `acepta_multiples_paradas` tinyint(1) DEFAULT 0,
  `acepta_rutas_programadas` tinyint(1) DEFAULT 0,
  `precio_base` decimal(10,2) DEFAULT 0.00,
  `precio_por_km` decimal(10,2) DEFAULT 0.00,
  `config_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Configuraciones adicionales en formato JSON' CHECK (json_valid(`config_json`)),
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `negocios_radio_operacion`
--

INSERT INTO `negocios_radio_operacion` (`id`, `negocio_id`, `categoria_servicio`, `radio_operacion`, `centro_lat`, `centro_lng`, `disponible`, `horas_operacion`, `capacidad_maxima`, `carga_trabajo_actual`, `acepta_multiples_paradas`, `acepta_rutas_programadas`, `precio_base`, `precio_por_km`, `config_json`, `fecha_creacion`, `fecha_actualizacion`, `activo`) VALUES
(1, 1234, 'transporte', 100, -31.42010000, -64.50040000, 1, 'L-D: 8-22', 4, 0, 1, 1, 500.00, 120.00, '{\"vehiculo\": \"Sedan\", \"patente\": \"AB123CD\", \"servicios_adicionales\": [\"aire_acondicionado\", \"wifi\"]}', '2025-04-11 20:24:50', '2025-04-12 05:40:16', 1),
(2, 5678, 'reparto', 80, -31.24440000, -64.46190000, 1, 'L-S: 9-20, D: 10-16', 10, 0, 1, 0, 300.00, 80.00, '{\"tipo_vehiculo\": \"Moto\", \"tiempo_estimado_por_km\": 2, \"tamanio_maximo_paquete\": \"mediano\"}', '2025-04-11 20:25:01', '2025-04-12 05:39:54', 1),
(3, 9101, 'transporte', 15, -31.08740000, -64.47960000, 1, 'L-V: 6-23, S-D: 8-21', 6, 0, 1, 1, 600.00, 130.00, '{\"vehiculo\": \"Combi\", \"patente\": \"XY789ZW\", \"servicios_adicionales\": [\"aire_acondicionado\", \"asientos_reclinables\", \"espacio_equipaje\"]}', '2025-04-11 20:25:18', '2025-04-12 05:39:31', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `noticias`
--

CREATE TABLE `noticias` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `contenido` longtext NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `categoria` varchar(100) DEFAULT 'General',
  `user_id` int(11) DEFAULT NULL,
  `vistas` int(11) DEFAULT 0,
  `activa` tinyint(1) DEFAULT 1,
  `fecha_publicacion` datetime DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `lat` decimal(10,6) DEFAULT NULL COMMENT 'Latitud de la noticia',
  `lng` decimal(10,6) DEFAULT NULL COMMENT 'Longitud de la noticia',
  `ubicacion` varchar(255) DEFAULT NULL COMMENT 'Lugar al que refiere la noticia',
  `link` varchar(500) DEFAULT NULL COMMENT 'URL a la noticia completa',
  `resumen_popup` text DEFAULT NULL COMMENT 'Resumen breve para mostrar en popup del mapa',
  `tags` varchar(500) DEFAULT NULL COMMENT 'Etiquetas separadas por comas'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ofertas`
--

CREATE TABLE `ofertas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio_normal` decimal(10,2) DEFAULT NULL,
  `precio_oferta` decimal(10,2) DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_expiracion` date DEFAULT NULL,
  `imagen_url` varchar(255) DEFAULT NULL,
  `lat` decimal(10,8) NOT NULL COMMENT 'Latitud de la oferta',
  `lng` decimal(11,8) NOT NULL COMMENT 'Longitud de la oferta',
  `business_id` int(11) DEFAULT NULL COMMENT 'ID del negocio relacionado (si aplica)',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `es_destacada` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ofertas`
--

INSERT INTO `ofertas` (`id`, `nombre`, `descripcion`, `precio_normal`, `precio_oferta`, `fecha_inicio`, `fecha_expiracion`, `imagen_url`, `lat`, `lng`, `business_id`, `activo`, `created_at`, `updated_at`, `es_destacada`) VALUES
(1, '2x1 en Pizzas', 'Todos los martes, lleva 2 pizzas grandes por el precio de 1', 1200.00, 600.00, '2025-03-01', '2025-04-30', NULL, -31.24038680, -64.47176460, 11, 1, '2025-03-30 06:04:15', '2025-04-03 01:37:56', 0),
(2, 'Descuento en Laptop', 'Notebooks con 30% de descuento', 150000.00, 105000.00, '2025-03-15', '2025-04-15', NULL, -34.59895600, -58.37022900, NULL, 1, '2025-03-30 06:04:15', '2025-03-30 06:04:15', 0),
(3, 'Happy Hour extendido', 'Happy hour de 18 a 21hs todos los días', 800.00, 400.00, '2025-03-10', '2025-05-10', NULL, -34.60681700, -58.43575100, NULL, 1, '2025-03-30 06:04:15', '2025-03-30 06:04:15', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ownership_transfers`
--

CREATE TABLE `ownership_transfers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `entity_type` enum('business','brand') NOT NULL,
  `entity_id` int(11) NOT NULL,
  `from_user_id` int(11) NOT NULL,
  `to_user_id` int(11) NOT NULL,
  `status` enum('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `accepted_at` datetime DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `paquetes_servicios`
--

CREATE TABLE `paquetes_servicios` (
  `id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo` enum('sesiones','mensualidad','trimestral','semestral','anual','curso_completo') NOT NULL,
  `cantidad_sesiones` int(11) DEFAULT NULL COMMENT 'Número de sesiones incluidas (para tipo sesiones)',
  `duracion_dias` int(11) DEFAULT NULL COMMENT 'Duración en días (para membresías: 30, 90, 180, 365)',
  `sesiones_por_semana` int(11) DEFAULT NULL COMMENT 'Límite semanal de sesiones',
  `precio` decimal(10,2) NOT NULL,
  `precio_descuento` decimal(10,2) DEFAULT NULL COMMENT 'Precio con descuento si hay promoción',
  `descuento_porcentaje` int(11) DEFAULT NULL COMMENT 'Porcentaje de descuento aplicado',
  `fecha_inicio` date DEFAULT NULL COMMENT 'Fecha desde la cual se puede comprar',
  `fecha_expiracion` date DEFAULT NULL COMMENT 'Fecha límite para comprar',
  `nivel_requerido` enum('principiante','intermedio','avanzado','profesional') DEFAULT NULL COMMENT 'Nivel mínimo requerido',
  `edad_minima` int(11) DEFAULT NULL,
  `edad_maxima` int(11) DEFAULT NULL,
  `evaluacion_inicial_incluida` tinyint(1) DEFAULT 0,
  `materiales_incluidos` tinyint(1) DEFAULT 0,
  `certificado_final` tinyint(1) DEFAULT 0,
  `clase_prueba_previa` tinyint(1) DEFAULT 0,
  `cupos_disponibles` int(11) DEFAULT NULL COMMENT 'Cupos totales disponibles (NULL = ilimitado)',
  `cupos_vendidos` int(11) DEFAULT 0,
  `destacado` tinyint(1) DEFAULT 0 COMMENT 'Mostrar como destacado en el frontend',
  `orden` int(11) DEFAULT 0 COMMENT 'Orden de visualización',
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Paquetes y planes de servicios';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `polygons`
--

CREATE TABLE `polygons` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `coordinates` longtext NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `polygons`
--

INSERT INTO `polygons` (`id`, `name`, `coordinates`, `created_at`, `updated_at`) VALUES
(1, 'Area de Reparto Centro', '[[-31.409033152571638,-64.2008399963379],[-31.426026495288117,-64.200496673584],[-31.42675889774461,-64.17268753051759],[-31.409619181152895,-64.1704559326172],[-31.409033152571638,-64.2008399963379]]', '2025-04-12 19:56:22', '2025-04-12 19:56:22'),
(2, 'Zona del abasto', '[[-31.381046028549964,-64.17114257812501],[-31.415332768002248,-64.16170120239259],[-31.409765687726317,-64.1100311279297],[-31.377968401176226,-64.11981582641603],[-31.381046028549964,-64.17114257812501]]', '2025-04-13 00:27:05', '2025-04-13 00:27:05'),
(3, 'transporte carlitos', '[[-31.43129966528685,-64.25457000732423],[-31.460443225497368,-64.24787521362306],[-31.45297513680278,-64.2030715942383],[-31.425440569204103,-64.21646118164064],[-31.43129966528685,-64.25457000732423]]', '2025-04-13 00:27:20', '2025-04-13 00:27:20'),
(4, 'Transporte Ricardito', '[[-31.368881484505586,-64.28890228271486],[-31.359940273455518,-64.21903610229494],[-31.3829511759192,-64.21766281127931],[-31.40141444796409,-64.27963256835939],[-31.368881484505586,-64.28890228271486]]', '2025-04-13 00:28:09', '2025-04-13 00:28:09'),
(5, 'transporte carlitos 2', '[[-31.376356270408557,-64.1865921020508],[-31.393941656166795,-64.167537689209],[-31.39921662977518,-64.22401428222658],[-31.376356270408557,-64.1865921020508]]', '2025-04-13 01:08:01', '2025-04-13 01:08:01'),
(6, 'transporte carlitos 3', '[[-31.407861084429,-64.26881790161134],[-31.409912194070973,-64.23465728759767],[-31.434668479738775,-64.24581527709962],[-31.43217849811961,-64.28529739379884],[-31.407861084429,-64.26881790161134]]', '2025-04-13 01:17:38', '2025-04-13 01:17:38'),
(7, 'transporte carlitos 5', '[[-31.419288124288357,-64.11037445068361],[-31.394381248621443,-64.11037445068361],[-31.394381248621443,-64.05853271484376],[-31.419288124288357,-64.05853271484376],[-31.419288124288357,-64.11037445068361]]', '2025-04-13 01:27:29', '2025-04-13 01:27:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `portfolio_trabajos`
--

CREATE TABLE `portfolio_trabajos` (
  `id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `categoria` varchar(100) DEFAULT NULL COMMENT 'Tipo de trabajo/proyecto',
  `fecha_realizacion` date DEFAULT NULL,
  `cliente_anonimo` varchar(150) DEFAULT NULL COMMENT 'Nombre genérico sin identificar: Empresa X, Cliente Y',
  `ubicacion` varchar(150) DEFAULT NULL COMMENT 'Dónde se realizó el trabajo',
  `imagen_principal` varchar(255) DEFAULT NULL,
  `imagenes_adicionales` text DEFAULT NULL COMMENT 'JSON array de URLs de imágenes',
  `video_url` varchar(255) DEFAULT NULL COMMENT 'Link a video (YouTube, Vimeo, etc)',
  `resultado_cuantificable` varchar(200) DEFAULT NULL COMMENT 'Ej: Incremento 30% eficiencia, Reducción 20% costos',
  `antes_despues` tinyint(1) DEFAULT 0 COMMENT 'Si tiene fotos antes/después',
  `tecnologias_utilizadas` text DEFAULT NULL COMMENT 'JSON array',
  `duracion_dias` int(11) DEFAULT NULL COMMENT 'Cuántos días tomó el proyecto',
  `destacado` tinyint(1) DEFAULT 0,
  `publico` tinyint(1) DEFAULT 1 COMMENT 'Si es visible públicamente',
  `orden` int(11) DEFAULT 0 COMMENT 'Orden de visualización',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Portfolio de trabajos y casos de éxito';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `preguntas_encuesta`
--

CREATE TABLE `preguntas_encuesta` (
  `id` int(11) NOT NULL,
  `encuesta_id` int(11) DEFAULT NULL,
  `texto_pregunta` text DEFAULT NULL,
  `tipo` varchar(20) DEFAULT NULL,
  `opciones` text DEFAULT NULL,
  `orden` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `preguntas_encuesta`
--

INSERT INTO `preguntas_encuesta` (`id`, `encuesta_id`, `texto_pregunta`, `tipo`, `opciones`, `orden`) VALUES
(1, 5, 'Cree que la gestión de Cardinalli es?', 'radio', 'Mala, Buena, Muy buena', 1),
(2, 5, 'Sabe cuanto gasta el municipio y en que cosas?', 'radio', 'Si, No, Poco', 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `imagen` varchar(255) DEFAULT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `profesionales`
--

CREATE TABLE `profesionales` (
  `id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `profesion` varchar(100) DEFAULT NULL,
  `especialidad` varchar(100) DEFAULT NULL,
  `matricula` varchar(50) DEFAULT NULL,
  `horario_atencion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `promociones`
--

CREATE TABLE `promociones` (
  `id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `descuento` varchar(50) DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rate_limit_log`
--

CREATE TABLE `rate_limit_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `ip` varchar(45) NOT NULL,
  `endpoint` varchar(100) NOT NULL,
  `hit_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `rate_limit_log`
--

INSERT INTO `rate_limit_log` (`id`, `ip`, `endpoint`, `hit_at`) VALUES
(6, '181.9.226.160', 'login', '2026-04-24 21:33:07'),
(5, '201.235.95.238', 'login', '2026-04-24 18:43:46'),
(7, '201.235.95.238', 'login', '2026-04-25 02:49:30'),
(8, '201.235.95.238', 'login', '2026-04-25 12:35:38');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `remates`
--

CREATE TABLE `remates` (
  `id` int(10) UNSIGNED NOT NULL,
  `business_id` int(11) NOT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_inicio` datetime NOT NULL,
  `fecha_fin` datetime DEFAULT NULL,
  `fecha_cierre` datetime DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservas`
--

CREATE TABLE `reservas` (
  `id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL COMMENT 'NULL si es reserva sin login',
  `nombre_cliente` varchar(150) DEFAULT NULL,
  `email_cliente` varchar(150) DEFAULT NULL,
  `telefono_cliente` varchar(20) DEFAULT NULL,
  `fecha_reserva` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `duracion_minutos` int(11) DEFAULT NULL COMMENT 'Duración calculada',
  `tipo_sesion` enum('individual','grupal','taller','evaluacion','clase_prueba') DEFAULT 'individual',
  `nivel` enum('principiante','intermedio','avanzado','profesional') DEFAULT NULL,
  `modalidad` enum('presencial','online','domicilio') DEFAULT 'presencial',
  `direccion_servicio` varchar(255) DEFAULT NULL,
  `lat_servicio` decimal(10,8) DEFAULT NULL,
  `lng_servicio` decimal(11,8) DEFAULT NULL,
  `paquete_id` int(11) DEFAULT NULL COMMENT 'FK a compras_paquetes si la reserva consume de un paquete',
  `sesion_numero` int(11) DEFAULT NULL COMMENT 'Número de sesión dentro del paquete',
  `estado` enum('pendiente','confirmada','en_proceso','completada','cancelada','no_asistio') DEFAULT 'pendiente',
  `confirmada_por_proveedor` tinyint(1) DEFAULT 0,
  `fecha_confirmacion` timestamp NULL DEFAULT NULL,
  `precio_acordado` decimal(10,2) DEFAULT NULL,
  `pagado` tinyint(1) DEFAULT 0,
  `metodo_pago` enum('efectivo','transferencia','tarjeta','mercadopago','otro') DEFAULT NULL,
  `fecha_pago` timestamp NULL DEFAULT NULL,
  `notas_cliente` text DEFAULT NULL COMMENT 'Notas/comentarios del cliente al reservar',
  `notas_proveedor` text DEFAULT NULL COMMENT 'Notas internas del proveedor',
  `motivo_cancelacion` text DEFAULT NULL,
  `fecha_cancelacion` timestamp NULL DEFAULT NULL,
  `cancelado_por` enum('cliente','proveedor','sistema') DEFAULT NULL,
  `recordatorio_enviado` tinyint(1) DEFAULT 0,
  `fecha_recordatorio` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Reservas y citas de servicios';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `respuestas_encuesta`
--

CREATE TABLE `respuestas_encuesta` (
  `id` int(11) NOT NULL,
  `encuesta_id` int(11) DEFAULT NULL,
  `pregunta_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `respuesta` text DEFAULT NULL,
  `fecha_respuesta` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `respuestas_encuesta`
--

INSERT INTO `respuestas_encuesta` (`id`, `encuesta_id`, `pregunta_id`, `user_id`, `respuesta`, `fecha_respuesta`) VALUES
(1, 5, 1, NULL, 'Buena', '2025-03-31 06:08:05'),
(2, 5, 1, NULL, 'Buena', '2025-03-31 06:11:58'),
(3, 5, 1, NULL, 'Mala', '2025-03-31 06:29:04'),
(4, 5, 2, NULL, 'No', '2025-03-31 06:29:04'),
(5, 5, 1, NULL, 'Mala', '2025-04-01 03:45:33'),
(6, 5, 2, NULL, 'No', '2025-04-01 03:45:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `restaurantes`
--

CREATE TABLE `restaurantes` (
  `id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `mesas` int(11) DEFAULT NULL,
  `carta` text DEFAULT NULL,
  `horario_apertura` time DEFAULT NULL,
  `horario_cierre` time DEFAULT NULL,
  `dias_cierre` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `riesgo_legal`
--

CREATE TABLE `riesgo_legal` (
  `id` int(11) NOT NULL,
  `marca_id` int(11) NOT NULL,
  `riesgo_oposicion` text DEFAULT NULL,
  `riesgo_nulidad` text DEFAULT NULL,
  `riesgo_infraccion` text DEFAULT NULL,
  `estrategias_defensivas` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicios_profesionales`
--

CREATE TABLE `servicios_profesionales` (
  `id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `categoria_servicio` enum('educativo','deportivo','industrial','tecnico') NOT NULL,
  `subcategoria` varchar(100) DEFAULT NULL COMMENT 'Ej: clases_particulares, entrenamiento_personal, mantenimiento',
  `modalidad_presencial` tinyint(1) DEFAULT 1,
  `modalidad_online` tinyint(1) DEFAULT 0,
  `modalidad_hibrido` tinyint(1) DEFAULT 0,
  `modalidad_domicilio` tinyint(1) DEFAULT 0,
  `capacidad_maxima_simultanea` int(11) DEFAULT NULL COMMENT 'Cupos por sesión',
  `duracion_sesion_minutos` int(11) DEFAULT NULL COMMENT 'Duración estándar de una sesión',
  `acepta_reservas` tinyint(1) DEFAULT 1,
  `requiere_reserva_previa` tinyint(1) DEFAULT 0,
  `anticipacion_minima_horas` int(11) DEFAULT 24 COMMENT 'Horas de anticipación mínima para reservar',
  `nivel_principiante` tinyint(1) DEFAULT 1,
  `nivel_intermedio` tinyint(1) DEFAULT 1,
  `nivel_avanzado` tinyint(1) DEFAULT 1,
  `nivel_profesional` tinyint(1) DEFAULT 0,
  `precio_sesion_individual` decimal(10,2) DEFAULT NULL COMMENT 'Precio por sesión individual',
  `precio_sesion_grupal` decimal(10,2) DEFAULT NULL COMMENT 'Precio por sesión grupal',
  `precio_paquete_5_sesiones` decimal(10,2) DEFAULT NULL,
  `precio_paquete_10_sesiones` decimal(10,2) DEFAULT NULL,
  `precio_mensualidad` decimal(10,2) DEFAULT NULL,
  `precio_trimestral` decimal(10,2) DEFAULT NULL,
  `precio_anual` decimal(10,2) DEFAULT NULL,
  `precio_evaluacion_inicial` decimal(10,2) DEFAULT NULL COMMENT 'Para servicios industriales/evaluaciones',
  `precio_clase_prueba` decimal(10,2) DEFAULT 0.00 COMMENT 'Clase de prueba, puede ser gratis (0)',
  `equipamiento_incluido` text DEFAULT NULL COMMENT 'JSON array: ["pesas", "colchonetas", "etc"]',
  `instalaciones_disponibles` text DEFAULT NULL COMMENT 'JSON array: ["vestuarios", "ducha", "estacionamiento"]',
  `certificaciones` text DEFAULT NULL COMMENT 'JSON array de certificados',
  `anios_experiencia` int(11) DEFAULT 0 COMMENT 'Años de experiencia del profesional',
  `formacion_academica` text DEFAULT NULL COMMENT 'Descripción de formación',
  `politica_cancelacion` text DEFAULT NULL COMMENT 'Política de cancelación del servicio',
  `requisitos_previos` text DEFAULT NULL COMMENT 'Requisitos previos del cliente',
  `materiales_incluidos` text DEFAULT NULL COMMENT 'JSON array de materiales incluidos',
  `ofrece_certificado` tinyint(1) DEFAULT 0 COMMENT 'Si entrega certificado al finalizar',
  `duracion_curso_completo` int(11) DEFAULT NULL COMMENT 'Duración total en días si es un curso',
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Información específica de servicios profesionales';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `share_tokens`
--

CREATE TABLE `share_tokens` (
  `id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `encuesta_id` int(11) NOT NULL,
  `evento_id` int(11) DEFAULT NULL,
  `oferta_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expiry_date` datetime NOT NULL DEFAULT (current_timestamp() + interval 48 hour)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `share_tokens`
--

INSERT INTO `share_tokens` (`id`, `token`, `encuesta_id`, `evento_id`, `oferta_id`, `user_id`, `created_at`, `expiry_date`) VALUES
(7, 'bf6bb9958ad9df65f27e4a3b23ec4311', 3, NULL, NULL, 2, '2025-04-06 02:50:08', '2025-04-08 02:50:08'),
(8, '57eb8066047f886eb808158f7efb0fa1', 0, NULL, 3, 2, '2025-04-06 02:50:20', '2025-04-08 02:50:20'),
(9, 'd5e0367a2bf1d2003f20cbd7839c0214', 5, NULL, NULL, 2, '2025-04-07 02:40:08', '2025-04-09 02:40:08'),
(11, 'c8c757119e089325eb7c392f24cf3b3e', 0, 1, NULL, 2, '2025-04-11 13:25:09', '2025-04-13 13:25:09'),
(12, 'eb09f1bfdf9cb643be92dfb827026753', 0, NULL, 1, 2, '2025-04-12 02:44:52', '2025-04-14 02:44:52'),
(14, '490fb6eee9c090877a3038991cf45fb0', 4, NULL, NULL, 2, '2025-10-08 00:59:16', '2025-10-10 00:59:16'),
(15, 'e459164ce9007049021b33952075c819', 0, 4, NULL, 2, '2025-10-25 18:03:53', '2025-10-27 18:03:53'),
(16, '017f195dd53fc49a7287a10dd4490a72', 0, 4, NULL, 5, '2026-04-10 19:06:07', '2026-04-12 19:06:07');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `subcategories`
--

CREATE TABLE `subcategories` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) DEFAULT NULL,
  `icon` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `subcategories`
--

INSERT INTO `subcategories` (`id`, `category_id`, `name`, `slug`, `icon`) VALUES
(1, 1, 'Comercio', 'comercio', '🛍️'),
(2, 1, 'Hotel', 'hotel', '🏨'),
(3, 1, 'Restaurante', 'restaurante', '🍽️'),
(4, 1, 'Inmobiliaria', 'inmobiliaria', '🏠'),
(5, 1, 'Farmacia', 'farmacia', '💊'),
(6, 2, 'Cafetería', 'cafeteria', '☕️'),
(7, 2, 'Heladería', 'heladeria', '🍦'),
(8, 2, 'Pastelería', 'pasteleria', '🍰'),
(9, 2, 'Bar', 'bar', '🍹'),
(10, 2, 'Comida Rápida', 'comida_rapida', '🍔'),
(11, 3, 'Gimnasio', 'gimnasio', '🏋️‍♂️'),
(12, 3, 'Spa', 'spa', '🧖‍♀️'),
(13, 3, 'Peluquería', 'peluqueria', '💇‍♀️'),
(14, 3, 'Barbería', 'barberia', '💈'),
(15, 3, 'Estética', 'estetica', '💅'),
(16, 4, 'Abogacía', 'abogacia', '⚖️'),
(17, 4, 'Escribanía', 'escribania', '📜'),
(18, 4, 'Contabilidad', 'contabilidad', '📊'),
(19, 4, 'Diseño Gráfico', 'diseno_grafico', '🎨'),
(20, 4, 'Informática', 'informatica', '💻'),
(21, 5, 'Clínica', 'clinica', '🏥'),
(22, 5, 'Laboratorio', 'laboratorio', '🔬'),
(23, 5, 'Veterinaria', 'veterinaria', '🐶'),
(24, 5, 'Dentista', 'dentista', '🦷'),
(25, 5, 'Óptica', 'optica', '👓'),
(26, 6, 'Decoración', 'decoracion', '🛋️'),
(27, 6, 'Jardinería', 'jardineria', '🌳'),
(28, 6, 'Ferretería', 'ferreteria', '🛠️'),
(29, 6, 'Electricidad', 'electricidad', '💡'),
(30, 6, 'Plomería', 'plomeria', '🚿'),
(31, 7, 'Cine', 'cine', '🎬'),
(32, 7, 'Teatro', 'teatro', '🎭'),
(33, 7, 'Discoteca', 'discoteca', '💃'),
(34, 7, 'Karaoke', 'karaoke', '🎤'),
(35, 7, 'Juegos', 'juegos', '🎲'),
(36, 1, 'comercio', NULL, '🛍️'),
(37, 1, 'hotel', NULL, '🏨'),
(38, 1, 'restaurante', NULL, '🍽️'),
(39, 1, 'inmobiliaria', NULL, '🏠'),
(40, 1, 'farmacia', NULL, '💊'),
(41, 2, 'cafeteria', NULL, '☕️'),
(42, 2, 'heladeria', NULL, '🍦'),
(43, 2, 'pasteleria', NULL, '🍰'),
(44, 2, 'bar', NULL, '🍹'),
(45, 2, 'comida_rapida', NULL, '🍔'),
(46, 3, 'gimnasio', NULL, '🏋️‍♂️'),
(47, 3, 'spa', NULL, '🧖‍♀️'),
(48, 3, 'peluqueria', NULL, '💇‍♀️'),
(49, 3, 'barberia', NULL, '💈'),
(50, 3, 'estetica', NULL, '💅'),
(51, 4, 'abogacia', NULL, '⚖️'),
(52, 4, 'escribania', NULL, '📜'),
(53, 4, 'contabilidad', NULL, '📊'),
(54, 4, 'diseno_grafico', NULL, '🎨'),
(55, 4, 'informatica', NULL, '💻'),
(56, 5, 'clinica', NULL, '🏥'),
(57, 5, 'laboratorio', NULL, '🔬'),
(58, 5, 'veterinaria', NULL, '🐶'),
(59, 5, 'dentista', NULL, '🦷'),
(60, 5, 'optica', NULL, '👓'),
(61, 6, 'decoracion', NULL, '🛋️'),
(62, 6, 'jardineria', NULL, '🌳'),
(63, 6, 'ferreteria', NULL, '🛠️'),
(64, 6, 'electricidad', NULL, '💡'),
(65, 6, 'plomeria', NULL, '🚰'),
(66, 7, 'cine', NULL, '🎬'),
(67, 7, 'teatro', NULL, '🎭'),
(68, 7, 'discoteca', NULL, '💃'),
(69, 7, 'karaoke', NULL, '🎤'),
(70, 7, 'juegos', NULL, '🎲');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `system_config`
--

CREATE TABLE `system_config` (
  `id` int(11) NOT NULL,
  `config_key` varchar(100) NOT NULL,
  `config_value` text NOT NULL,
  `config_description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `system_config`
--

INSERT INTO `system_config` (`id`, `config_key`, `config_value`, `config_description`, `updated_at`) VALUES
(1, 'upload_max_size', '1000000', 'Tamaño máximo para subidas de archivos (en bytes)', '2025-03-19 20:50:49'),
(2, 'upload_images_path', 'uploads/images', 'Ruta para subidas de imágenes', '2025-03-19 20:50:49'),
(3, 'upload_files_path', 'uploads/files', 'Ruta para subidas de archivos generales', '2025-03-19 20:50:49'),
(4, 'max_images_business', '2', 'Número máximo de imágenes por negocio', '2025-03-19 20:50:49'),
(5, 'max_images_emoji', '10', 'Número máximo de imágenes por emoji', '2025-03-19 20:50:49'),
(6, 'optimize_images', '1', 'Optimizar imágenes automáticamente (1=sí, 0=no)', '2025-03-19 20:50:49'),
(7, 'optimize_threshold', '500000', 'Umbral de tamaño para optimización de imágenes (en bytes)', '2025-03-19 20:50:49'),
(8, 'site_name', 'Mapita', 'Nombre del sitio', '2025-03-19 20:50:49'),
(9, 'admin_email', 'admin@mapita.com.ar', 'Email de administración', '2025-03-19 20:50:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `transmisiones`
--

CREATE TABLE `transmisiones` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo` enum('youtube_live','radio_stream','audio_stream','video_stream') NOT NULL DEFAULT 'youtube_live',
  `stream_url` varchar(500) DEFAULT NULL,
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  `business_id` int(11) DEFAULT NULL,
  `evento_id` int(11) DEFAULT NULL,
  `en_vivo` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `fecha_inicio` datetime DEFAULT NULL COMMENT 'Fecha y hora de inicio programada de la transmisión',
  `fecha_fin` datetime DEFAULT NULL COMMENT 'Fecha y hora de fin programada de la transmisión'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `transmisiones`
--

INSERT INTO `transmisiones` (`id`, `titulo`, `descripcion`, `tipo`, `stream_url`, `lat`, `lng`, `business_id`, `evento_id`, `en_vivo`, `activo`, `created_at`, `updated_at`, `fecha_inicio`, `fecha_fin`) VALUES
(1, 'Radio Local en Vivo', 'Transmision de radio local las 24hs con noticias y musica regional.', 'radio_stream', 'https://stream.zeno.fm/ejemplo', -34.60370000, -58.38160000, NULL, NULL, 0, 1, '2026-04-17 23:45:20', '2026-04-17 23:45:20', NULL, NULL),
(2, 'Canal Municipal YouTube', 'Sesiones del Consejo Municipal y eventos oficiales en vivo.', 'youtube_live', 'https://www.youtube.com/@municipio/live', -34.60500000, -58.39000000, NULL, NULL, 0, 1, '2026-04-17 23:45:20', '2026-04-17 23:45:20', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `transmisiones_vivo`
--

CREATE TABLE `transmisiones_vivo` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo` enum('youtube','audio_streaming','zoom_publico') DEFAULT NULL,
  `youtube_stream_url` text DEFAULT NULL,
  `audio_provider` varchar(100) DEFAULT NULL,
  `audio_stream_url` text DEFAULT NULL,
  `organizador_id` int(11) DEFAULT NULL,
  `fecha_inicio` datetime DEFAULT NULL,
  `fecha_fin` datetime DEFAULT NULL,
  `estado` enum('programada','en_vivo','finalizada','cancelada') DEFAULT NULL,
  `visitas` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `transmision_participantes`
--

CREATE TABLE `transmision_participantes` (
  `id` int(11) NOT NULL,
  `transmision_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `fecha_union` timestamp NULL DEFAULT NULL,
  `duracion_minutos` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `transporte`
--

CREATE TABLE `transporte` (
  `id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `tipo_unidad` varchar(50) DEFAULT NULL,
  `rutas` text DEFAULT NULL,
  `horarios` text DEFAULT NULL,
  `capacidad` int(11) DEFAULT NULL,
  `precio_base` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `transporte_asignaciones`
--

CREATE TABLE `transporte_asignaciones` (
  `id` int(11) NOT NULL,
  `transportista_nombre` varchar(255) NOT NULL COMMENT 'Nombre del transportista asignado',
  `negocio_id` int(11) NOT NULL COMMENT 'ID del negocio asignado (FK a tabla negocios)',
  `negocio_nombre` varchar(255) NOT NULL COMMENT 'Nombre del negocio asignado',
  `fecha_asignacion` datetime NOT NULL COMMENT 'Fecha y hora de la asignación',
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `nombre_zona` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `transporte_asignaciones`
--

INSERT INTO `transporte_asignaciones` (`id`, `transportista_nombre`, `negocio_id`, `negocio_nombre`, `fecha_asignacion`, `creado_en`, `actualizado_en`, `nombre_zona`) VALUES
(1, 'Prueba Transportista', 1, 'Negocio de Prueba', '2025-04-12 15:00:00', '2025-04-13 01:21:53', '2025-04-13 01:21:53', NULL),
(2, 'Transportista Ejemplo', 2, '🥩 Carnicería El Gaucho', '2025-04-13 02:04:33', '2025-04-13 02:04:38', '2025-04-13 02:04:38', NULL),
(3, 'Transportista Ejemplo', 1, '🛒 Supermercado Central', '2025-04-13 02:08:36', '2025-04-13 02:08:42', '2025-04-13 02:08:42', 'ruta3'),
(4, 'Transportista Ejemplo', 2, '🥩 Carnicería El Gaucho', '2025-04-13 02:08:36', '2025-04-13 02:08:42', '2025-04-13 02:08:42', 'ruta3');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `trivias`
--

CREATE TABLE `trivias` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `dificultad` enum('facil','medio','dificil') NOT NULL DEFAULT 'medio',
  `tiempo_limite` int(11) NOT NULL DEFAULT 30,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `lat` decimal(10,6) DEFAULT NULL COMMENT 'Latitud donde se realiza la trivia',
  `lng` decimal(10,6) DEFAULT NULL COMMENT 'Longitud donde se realiza la trivia',
  `ubicacion` varchar(255) DEFAULT NULL COMMENT 'Nombre del lugar',
  `business_id` int(11) DEFAULT NULL COMMENT 'Negocio que la organiza',
  `svg` varchar(500) DEFAULT NULL COMMENT 'URL o path a imagen SVG ilustrativa',
  `referencia` varchar(255) DEFAULT NULL COMMENT 'Referencia del juego',
  `tipo` varchar(100) DEFAULT NULL COMMENT 'Tipo de juego',
  `edad` varchar(50) DEFAULT NULL COMMENT 'Edad recomendada',
  `emojis` varchar(255) DEFAULT NULL COMMENT 'Emojis decorativos del popup',
  `app_path` varchar(500) DEFAULT NULL COMMENT 'Path relativo del archivo PHP de la app (dentro de apps/trivias/)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `trivia_games`
--

CREATE TABLE `trivia_games` (
  `game_id` varchar(32) NOT NULL,
  `question_order` text NOT NULL,
  `current_question_ptr` int(11) NOT NULL,
  `score` int(11) NOT NULL DEFAULT 0,
  `errors` int(11) NOT NULL DEFAULT 0,
  `game_active` tinyint(1) NOT NULL DEFAULT 1,
  `feedback` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `trivia_games`
--

INSERT INTO `trivia_games` (`game_id`, `question_order`, `current_question_ptr`, `score`, `errors`, `game_active`, `feedback`, `created_at`, `updated_at`) VALUES
('0531179206c7b18612b41f6521e3c039', '[5,24,28,27,22,6,19,15,23,14,26,20,12,16,9,1,13,4,0,3,21,8,11,25,17,2,18,7,29,10]', 0, 0, 0, 1, NULL, '2025-04-03 02:53:29', '2025-04-03 02:53:29'),
('1af8c1e2301ad97cbdd84f91b71cb563', '[23,9,5,29,20,14,28,8,26,10,2,7,15,27,17,19,1,24,0,12,11,13,22,16,4,21,18,6,3,25]', 0, 0, 0, 1, NULL, '2025-10-25 18:02:51', '2025-10-25 18:02:51'),
('48d26e5fef6083a31467441d5600f432', '[24,23,7,28,22,20,15,13,26,8,9,6,18,27,21,2,19,3,11,17,16,0,14,4,1,5,29,25,10,12]', 16, 64, 0, 0, '<p class=\'feedback correct\'>¡Correcto! 👍 +4 puntos.</p>', '2025-04-03 18:06:28', '2025-04-03 18:08:12'),
('4fa9ab3793f67f025e0cb54c27982c5c', '[6,11,12,2,9,14,1,7,19,28,5,17,13,4,20,0,27,22,16,23,18,29,21,3,24,8,15,26,10,25]', 16, 52, 3, 0, '<p class=\'feedback incorrect\'>¡Incorrecto! 👎 La respuesta correcta era: <strong>A) Verdadero</strong>.</p>', '2025-04-03 03:30:11', '2025-04-03 03:32:48'),
('6bcb86e5e77c4ccab24a0d518ac7f10e', '[9,21,29,22,23,11,2,16,24,10,28,8,13,17,20,4,12,25,15,5,7,14,19,18,6,1,3,27,0,26]', 16, 64, 0, 0, '<p class=\'feedback correct\'>¡Correcto! 👍 +4 puntos.</p>', '2025-04-03 17:45:14', '2025-04-03 17:47:24'),
('77324139d50675bfed59c49bae08830c', '[1,27,9,23,22,4,13,29,2,15,19,14,20,11,25,5,21,3,24,6,12,10,28,0,26,17,18,8,16,7]', 16, 64, 0, 0, '<p class=\'feedback correct\'>¡Correcto! 👍 +4 puntos.</p>', '2025-04-03 02:53:43', '2025-04-03 03:17:53'),
('7d43ea85a6566b8ad952e0a46b2da802', '[12,18,4,6,2,22,7,20,29,15,14,13,16,17,23,1,21,10,25,9,26,27,11,8,5,28,24,3,19,0]', 12, 36, 3, 0, '<p class=\'feedback incorrect\'>¡Incorrecto! 👎 La respuesta correcta era: <strong>A) Cierto</strong>.</p>', '2025-04-08 11:12:04', '2025-04-08 11:16:50'),
('89aebef64c99651681160eb601b7f788', '[15,29,24,27,10,7,8,12,17,4,26,23,1,13,3,19,16,20,6,21,25,14,11,22,5,18,28,2,0,9]', 0, 0, 0, 1, NULL, '2025-04-04 15:33:59', '2025-04-04 15:33:59');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `trivia_scores`
--

CREATE TABLE `trivia_scores` (
  `id` int(11) NOT NULL,
  `trivia_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `puntos` int(11) NOT NULL DEFAULT 0,
  `respuestas_correctas` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `trivia_stats`
--

CREATE TABLE `trivia_stats` (
  `stat_id` int(11) NOT NULL,
  `game_id` varchar(32) NOT NULL,
  `total_score` int(11) NOT NULL DEFAULT 0,
  `total_questions` int(11) NOT NULL DEFAULT 0,
  `correct_answers` int(11) NOT NULL DEFAULT 0,
  `is_win` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `trivia_stats`
--

INSERT INTO `trivia_stats` (`stat_id`, `game_id`, `total_score`, `total_questions`, `correct_answers`, `is_win`, `created_at`) VALUES
(1, '77324139d50675bfed59c49bae08830c', 64, 16, 16, 1, '2025-04-03 03:17:53'),
(2, '4fa9ab3793f67f025e0cb54c27982c5c', 52, 16, 13, 0, '2025-04-03 03:32:48'),
(3, '6bcb86e5e77c4ccab24a0d518ac7f10e', 64, 16, 16, 1, '2025-04-03 17:47:24'),
(4, '48d26e5fef6083a31467441d5600f432', 64, 16, 16, 1, '2025-04-03 18:08:12'),
(5, '7d43ea85a6566b8ad952e0a46b2da802', 36, 12, 9, 0, '2025-04-08 11:16:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `turnos`
--

CREATE TABLE `turnos` (
  `id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `duracion` int(11) DEFAULT NULL,
  `estado` enum('pendiente','confirmado','cancelado','completado') DEFAULT 'pendiente',
  `notas` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `email_verification_token` varchar(64) DEFAULT NULL,
  `email_token_expiry` datetime DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `phone`, `email_verified`, `email_verification_token`, `email_token_expiry`, `is_admin`, `created_at`, `updated_at`, `reset_token`, `reset_token_expiry`) VALUES
(1, 'admin', '$2y$10$GRQo0c/b0kLku/I9NRURE.kdOSbePXx7ao28Khb8qYymED3lHD6ri', NULL, NULL, 1, NULL, NULL, 1, '2025-03-19 00:00:05', '2026-04-20 15:00:02', NULL, NULL),
(2, 'Lulu1', '$2y$10$aRlnyV1MUROFGas8gfud1O.O.lQT0HvxEVPXAOVg3ZT3IJ8Vh.Ac2', NULL, NULL, 1, NULL, NULL, 0, '2025-03-29 02:44:35', '2026-04-20 15:00:02', NULL, NULL),
(3, 'Lulu13', '$2y$10$paGP0LTwW.FI3imVvRds0.whMd28EKyLXk/Lgp9WCRoEBRJMmtxvO', NULL, NULL, 1, NULL, NULL, 0, '2025-03-29 17:52:53', '2026-04-20 15:00:02', NULL, NULL),
(4, 'martilleracelesteortiz@gmail.com', '$2y$10$ZiXMPysIFxGcgWWAxhzmSOD.2woxXw5vJxi8o5ejgrrB9wOk8YxbS', NULL, NULL, 1, NULL, NULL, 0, '2025-04-01 03:44:07', '2026-04-20 15:00:02', NULL, NULL),
(5, 'Pablo_Farias', '$2y$10$2/lTT0OjnVkUVMaJt6i4Yuw40uhT4v2focifLJUYnb9GDrRKTLb1S', 'pablofarias19@gmail.com', NULL, 0, NULL, NULL, 1, '2026-04-10 16:06:07', '2026-04-25 12:35:38', NULL, NULL),
(6, 'Nicolas_FO', '$2y$10$iJKRWAHEyxVW.5jzL593zOEO4/uWjiFB2Y0A25kv2LghjjHRSIfWu', NULL, NULL, 1, NULL, NULL, 0, '2026-04-20 06:13:48', '2026-04-20 15:00:02', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `valoraciones_servicios`
--

CREATE TABLE `valoraciones_servicios` (
  `id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `reserva_id` int(11) DEFAULT NULL COMMENT 'Para validar que realmente usó el servicio',
  `usuario_id` int(11) DEFAULT NULL,
  `calificacion_general` tinyint(4) NOT NULL CHECK (`calificacion_general` between 1 and 5),
  `calificacion_puntualidad` tinyint(4) DEFAULT NULL CHECK (`calificacion_puntualidad` between 1 and 5),
  `calificacion_profesionalismo` tinyint(4) DEFAULT NULL CHECK (`calificacion_profesionalismo` between 1 and 5),
  `calificacion_comunicacion` tinyint(4) DEFAULT NULL CHECK (`calificacion_comunicacion` between 1 and 5),
  `calificacion_precio_calidad` tinyint(4) DEFAULT NULL CHECK (`calificacion_precio_calidad` between 1 and 5),
  `calificacion_resultados` tinyint(4) DEFAULT NULL CHECK (`calificacion_resultados` between 1 and 5),
  `calificacion_instalaciones` tinyint(4) DEFAULT NULL CHECK (`calificacion_instalaciones` between 1 and 5),
  `calificacion_higiene` tinyint(4) DEFAULT NULL CHECK (`calificacion_higiene` between 1 and 5),
  `calificacion_didactica` tinyint(4) DEFAULT NULL CHECK (`calificacion_didactica` between 1 and 5),
  `calificacion_material_apoyo` tinyint(4) DEFAULT NULL CHECK (`calificacion_material_apoyo` between 1 and 5),
  `calificacion_atencion_personalizada` tinyint(4) DEFAULT NULL CHECK (`calificacion_atencion_personalizada` between 1 and 5),
  `calificacion_calidad_trabajo` tinyint(4) DEFAULT NULL CHECK (`calificacion_calidad_trabajo` between 1 and 5),
  `calificacion_limpieza_post_trabajo` tinyint(4) DEFAULT NULL CHECK (`calificacion_limpieza_post_trabajo` between 1 and 5),
  `calificacion_equipamiento` tinyint(4) DEFAULT NULL CHECK (`calificacion_equipamiento` between 1 and 5),
  `titulo_review` varchar(150) DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `lo_recomendaria` tinyint(1) DEFAULT NULL,
  `volveria_contratar` tinyint(1) DEFAULT NULL,
  `servicio_utilizado` varchar(100) DEFAULT NULL COMMENT 'Qué servicio específico',
  `nivel_servicio` enum('principiante','intermedio','avanzado','profesional') DEFAULT NULL,
  `modalidad_recibida` enum('presencial','online','domicilio') DEFAULT NULL,
  `verificada` tinyint(1) DEFAULT 0 COMMENT 'Si se validó que es cliente real',
  `fecha_verificacion` timestamp NULL DEFAULT NULL,
  `respuesta_proveedor` text DEFAULT NULL,
  `fecha_respuesta` timestamp NULL DEFAULT NULL,
  `util_count` int(11) DEFAULT 0 COMMENT 'Cuántos usuarios marcaron como útil',
  `no_util_count` int(11) DEFAULT 0,
  `reportada` tinyint(1) DEFAULT 0,
  `motivo_reporte` text DEFAULT NULL,
  `aprobada` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Valoraciones y reviews de servicios';

--
-- Disparadores `valoraciones_servicios`
--
DELIMITER $$
CREATE TRIGGER `tr_actualizar_promedio_calificacion` AFTER INSERT ON `valoraciones_servicios` FOR EACH ROW BEGIN
    -- Trigger "no-op" (deja el esqueleto listo). Si querés que haga algo real,
    -- reemplazá este SET por el UPDATE que corresponda.
    SET @noop := 1;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vehiculos_venta`
--

CREATE TABLE `vehiculos_venta` (
  `id` int(10) UNSIGNED NOT NULL,
  `business_id` int(11) NOT NULL,
  `tipo_vehiculo` varchar(30) DEFAULT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `modelo` varchar(120) DEFAULT NULL,
  `anio` smallint(6) DEFAULT NULL,
  `km` int(11) DEFAULT NULL,
  `precio` decimal(14,2) DEFAULT NULL,
  `contacto` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_businesses_with_icons`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_businesses_with_icons` (
`id` int(11)
,`user_id` int(11)
,`name` varchar(255)
,`address` varchar(255)
,`lat` decimal(10,6)
,`lng` decimal(10,6)
,`phone` varchar(50)
,`email` varchar(100)
,`website` varchar(255)
,`business_type` varchar(50)
,`visible` tinyint(1)
,`status` enum('active','inactive','pending')
,`created_at` timestamp
,`updated_at` timestamp
,`price_range` int(1)
,`description` text
,`subcategory_id` int(11)
,`emoji` varchar(10)
,`icon_class` varchar(100)
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `wt_messages`
--

CREATE TABLE `wt_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `entity_type` enum('negocio','marca','evento','encuesta') NOT NULL,
  `entity_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_name` varchar(80) NOT NULL DEFAULT 'Invitado',
  `sender_key` varchar(120) NOT NULL,
  `message` varchar(140) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `wt_messages`
--

INSERT INTO `wt_messages` (`id`, `entity_type`, `entity_id`, `user_id`, `user_name`, `sender_key`, `message`, `created_at`, `updated_at`) VALUES
(10, 'negocio', 9146, 5, 'Pablo_Farias', 'uid:5', 'Gracias 🙌', '2026-04-20 13:23:33', '2026-04-20 13:23:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `wt_presence`
--

CREATE TABLE `wt_presence` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `entity_type` enum('negocio','marca','evento','encuesta') NOT NULL,
  `entity_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_name` varchar(80) NOT NULL DEFAULT 'Invitado',
  `sender_key` varchar(120) NOT NULL,
  `last_seen` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `wt_presence`
--

INSERT INTO `wt_presence` (`id`, `entity_type`, `entity_id`, `user_id`, `user_name`, `sender_key`, `last_seen`, `updated_at`) VALUES
(1, 'negocio', 9142, 5, 'Pablo_Farias', 'uid:5', '2026-04-20 19:46:33', '2026-04-20 19:46:33'),
(12, 'negocio', 9146, 5, 'Pablo_Farias', 'uid:5', '2026-04-20 18:24:33', '2026-04-20 18:24:33'),
(15, 'negocio', 9143, 5, 'Pablo_Farias', 'uid:5', '2026-04-21 18:22:23', '2026-04-21 18:22:23'),
(72, 'negocio', 9145, 5, 'Pablo_Farias', 'uid:5', '2026-04-21 15:49:16', '2026-04-21 15:49:16'),
(163, 'negocio', 9148, 5, 'Pablo_Farias', 'uid:5', '2026-04-20 21:57:02', '2026-04-20 21:57:02'),
(205, 'negocio', 9144, 5, 'Pablo_Farias', 'uid:5', '2026-04-21 02:44:33', '2026-04-21 02:44:33'),
(218, 'negocio', 9145, NULL, 'Invitado', 'sid:e0e1b39a232e638e79f8938ebb6d210fb2d2de3e', '2026-04-20 01:07:59', '2026-04-20 01:07:59'),
(220, 'negocio', 9144, NULL, 'Invitado', 'sid:e0e1b39a232e638e79f8938ebb6d210fb2d2de3e', '2026-04-20 01:07:51', '2026-04-20 01:07:51'),
(221, 'negocio', 9146, NULL, 'Invitado', 'sid:e0e1b39a232e638e79f8938ebb6d210fb2d2de3e', '2026-04-20 01:07:38', '2026-04-20 01:07:38'),
(227, 'negocio', 9148, NULL, 'Invitado', 'sid:e0e1b39a232e638e79f8938ebb6d210fb2d2de3e', '2026-04-20 01:07:45', '2026-04-20 01:07:45'),
(387, 'negocio', 9148, NULL, 'Invitado', 'sid:444f8825ce2281b52deea490043af7ab23c703e1', '2026-04-20 06:13:45', '2026-04-20 06:13:45'),
(390, 'negocio', 9145, NULL, 'Invitado', 'sid:444f8825ce2281b52deea490043af7ab23c703e1', '2026-04-20 06:13:05', '2026-04-20 06:13:05'),
(403, 'negocio', 9148, 6, 'Nicolas_FO', 'uid:6', '2026-04-21 11:11:32', '2026-04-21 11:11:32'),
(406, 'negocio', 9149, 6, 'Nicolas_FO', 'uid:6', '2026-04-21 02:47:40', '2026-04-21 02:47:40'),
(410, 'negocio', 9149, 5, 'Pablo_Farias', 'uid:5', '2026-04-23 22:31:34', '2026-04-23 22:31:34'),
(763, 'negocio', 9149, NULL, 'Invitado', 'sid:4ba5d14a4b4aa99f127d17e4d3c805dddd206881', '2026-04-20 14:20:43', '2026-04-20 14:20:43'),
(1442, 'marca', 3, 5, 'Pablo_Farias', 'uid:5', '2026-04-24 14:13:22', '2026-04-24 14:13:22'),
(1955, 'negocio', 9147, 5, 'Pablo_Farias', 'uid:5', '2026-04-20 21:57:00', '2026-04-20 21:57:00'),
(2536, 'marca', 4, 5, 'Pablo_Farias', 'uid:5', '2026-04-24 21:44:16', '2026-04-24 21:44:16'),
(2921, 'evento', 4, 5, 'Pablo_Farias', 'uid:5', '2026-04-21 18:22:33', '2026-04-21 18:22:33'),
(3248, 'negocio', 9147, 6, 'Nicolas_FO', 'uid:6', '2026-04-21 11:19:04', '2026-04-21 11:19:04'),
(3295, 'evento', 4, 6, 'Nicolas_FO', 'uid:6', '2026-04-21 11:19:11', '2026-04-21 11:19:11'),
(3396, 'negocio', 9144, NULL, 'Invitado', 'sid:2c67c3481eae76e457aa0805f105f86a20645535', '2026-04-21 16:47:06', '2026-04-21 16:47:06'),
(3400, 'evento', 4, NULL, 'Invitado', 'sid:2c67c3481eae76e457aa0805f105f86a20645535', '2026-04-21 15:16:47', '2026-04-21 15:16:47'),
(3408, 'negocio', 9149, NULL, 'Invitado', 'sid:2c67c3481eae76e457aa0805f105f86a20645535', '2026-04-21 15:16:29', '2026-04-21 15:16:29'),
(3430, 'negocio', 9142, NULL, 'Invitado', 'sid:2c67c3481eae76e457aa0805f105f86a20645535', '2026-04-21 16:47:06', '2026-04-21 16:47:06'),
(3446, 'negocio', 9149, NULL, 'Invitado', 'sid:db6aa0a2537a6fe242faa943cd62d44c180c7287', '2026-04-21 13:43:07', '2026-04-21 13:43:07'),
(4357, 'negocio', 9146, NULL, 'Invitado', 'sid:4a7c1c6f13af0ec3e9ae91c6a5a25dbb6f93deca', '2026-04-21 15:38:50', '2026-04-21 15:38:50'),
(4523, 'negocio', 9142, NULL, 'Invitado', 'sid:f99c50632d21e07636168111b7f8a6c507f86e1a', '2026-04-21 18:18:53', '2026-04-21 18:18:53'),
(4673, 'marca', 2, NULL, 'Invitado', 'sid:f99c50632d21e07636168111b7f8a6c507f86e1a', '2026-04-21 18:18:57', '2026-04-21 18:18:57'),
(4689, 'negocio', 9145, NULL, 'Invitado', 'sid:a6f4c201e4968d9a2d8e9fffe392b226536dbb63', '2026-04-22 03:06:27', '2026-04-22 03:06:27'),
(4694, 'negocio', 9149, NULL, 'Invitado', 'sid:a6f4c201e4968d9a2d8e9fffe392b226536dbb63', '2026-04-22 03:06:17', '2026-04-22 03:06:17'),
(4703, 'negocio', 9149, NULL, 'Invitado', 'sid:546cd793bdfd4072daa9314248c9bbc1f80abe43', '2026-04-22 18:53:47', '2026-04-22 18:53:47'),
(5165, 'negocio', 9145, NULL, 'Invitado', 'sid:0e045fcdd76a5012ca840622bc92c7046e4425aa', '2026-04-23 00:48:56', '2026-04-23 00:48:56'),
(5169, 'negocio', 9149, NULL, 'Invitado', 'sid:0e045fcdd76a5012ca840622bc92c7046e4425aa', '2026-04-23 00:12:42', '2026-04-23 00:12:42'),
(5187, 'negocio', 9144, NULL, 'Invitado', 'sid:0e045fcdd76a5012ca840622bc92c7046e4425aa', '2026-04-23 00:12:34', '2026-04-23 00:12:34'),
(5200, 'negocio', 9148, NULL, 'Invitado', 'sid:0e045fcdd76a5012ca840622bc92c7046e4425aa', '2026-04-23 00:12:45', '2026-04-23 00:12:45'),
(5206, 'marca', 4, NULL, 'Invitado', 'sid:0e045fcdd76a5012ca840622bc92c7046e4425aa', '2026-04-23 01:45:58', '2026-04-23 01:45:58'),
(5211, 'marca', 2, NULL, 'Invitado', 'sid:0e045fcdd76a5012ca840622bc92c7046e4425aa', '2026-04-23 00:57:25', '2026-04-23 00:57:25'),
(5214, 'marca', 3, NULL, 'Invitado', 'sid:0e045fcdd76a5012ca840622bc92c7046e4425aa', '2026-04-23 20:32:18', '2026-04-23 20:32:18'),
(5442, 'marca', 5, NULL, 'Invitado', 'sid:0e045fcdd76a5012ca840622bc92c7046e4425aa', '2026-04-23 00:52:16', '2026-04-23 00:52:16'),
(5565, 'marca', 5, NULL, 'Invitado', 'sid:ece1ef09c8a7429f581df5c027502faafbf24c5a', '2026-04-23 01:50:19', '2026-04-23 01:50:19'),
(5567, 'marca', 2, NULL, 'Invitado', 'sid:ece1ef09c8a7429f581df5c027502faafbf24c5a', '2026-04-23 01:50:16', '2026-04-23 01:50:16'),
(5852, 'negocio', 9149, NULL, 'Invitado', 'sid:441d8adadf3dc88d478292ce66a48fbacd431c93', '2026-04-23 12:15:56', '2026-04-23 12:15:56'),
(5856, 'marca', 3, NULL, 'Invitado', 'sid:441d8adadf3dc88d478292ce66a48fbacd431c93', '2026-04-23 12:15:50', '2026-04-23 12:15:50'),
(5880, 'negocio', 9145, NULL, 'Invitado', 'sid:441d8adadf3dc88d478292ce66a48fbacd431c93', '2026-04-23 12:30:29', '2026-04-23 12:30:29'),
(6860, 'negocio', 9149, NULL, 'Invitado', 'sid:c896563784d3f3052962f76cb3a2c1be0a7ebdeb', '2026-04-24 01:01:19', '2026-04-24 01:01:19'),
(6868, 'marca', 4, NULL, 'Invitado', 'sid:c896563784d3f3052962f76cb3a2c1be0a7ebdeb', '2026-04-24 01:01:31', '2026-04-24 01:01:31'),
(6883, 'marca', 5, 5, 'Pablo_Farias', 'uid:5', '2026-04-24 14:07:08', '2026-04-24 14:07:08'),
(6895, 'negocio', 9150, 5, 'Pablo_Farias', 'uid:5', '2026-04-25 02:48:41', '2026-04-25 02:48:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `wt_user_areas`
--

CREATE TABLE `wt_user_areas` (
  `user_id` int(11) NOT NULL,
  `area_slug` varchar(64) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `wt_user_blocks`
--

CREATE TABLE `wt_user_blocks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `blocker_user_id` int(11) NOT NULL,
  `blocked_user_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `wt_user_preferences`
--

CREATE TABLE `wt_user_preferences` (
  `user_id` int(11) NOT NULL,
  `wt_mode` enum('open','selective','closed') NOT NULL DEFAULT 'open',
  `areas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array de slugs de áreas, usado cuando wt_mode=selective' CHECK (json_valid(`areas`)),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `analisis_marcario`
--
ALTER TABLE `analisis_marcario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `marca_id` (`marca_id`);

--
-- Indices de la tabla `articulos`
--
ALTER TABLE `articulos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `autor_id` (`autor_id`);

--
-- Indices de la tabla `attachments`
--
ALTER TABLE `attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `business_id` (`business_id`),
  ADD KEY `brand_id` (`brand_id`);

--
-- Indices de la tabla `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_user` (`user_id`),
  ADD KEY `idx_audit_action` (`action`),
  ADD KEY `idx_audit_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_audit_created` (`created_at`);

--
-- Indices de la tabla `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `visible` (`visible`),
  ADD KEY `idx_scope` (`scope`),
  ADD KEY `idx_founded` (`founded_year`),
  ADD KEY `idx_brands_country_code` (`country_code`),
  ADD KEY `idx_brands_crear_franquicia` (`crear_franquicia`);

--
-- Indices de la tabla `brand_delegations`
--
ALTER TABLE `brand_delegations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_brand_delegate` (`brand_id`,`user_id`),
  ADD KEY `idx_brand_delegations_brand` (`brand_id`),
  ADD KEY `idx_brand_delegations_user` (`user_id`),
  ADD KEY `fk_brand_delegations_created_by` (`created_by`);

--
-- Indices de la tabla `brand_gallery`
--
ALTER TABLE `brand_gallery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_brand` (`brand_id`),
  ADD KEY `idx_principal` (`es_principal`);

--
-- Indices de la tabla `brand_gallery_v2`
--
ALTER TABLE `brand_gallery_v2`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bgv2_brand` (`brand_id`),
  ADD KEY `idx_bgv2_principal` (`es_principal`);

--
-- Indices de la tabla `businesses`
--
ALTER TABLE `businesses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_business_price_range` (`price_range`),
  ADD KEY `fk_subcategory` (`subcategory_id`),
  ADD KEY `idx_verified` (`verified`),
  ADD KEY `idx_has_delivery` (`has_delivery`),
  ADD KEY `idx_instagram` (`instagram`),
  ADD KEY `idx_business_oferta_activa` (`oferta_activa_id`),
  ADD KEY `idx_businesses_country_code` (`country_code`);

--
-- Indices de la tabla `business_categories`
--
ALTER TABLE `business_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `business_delegations`
--
ALTER TABLE `business_delegations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_business_delegate` (`business_id`,`user_id`),
  ADD KEY `idx_business_delegations_business` (`business_id`),
  ADD KEY `idx_business_delegations_user` (`user_id`),
  ADD KEY `fk_business_delegations_created_by` (`created_by`);

--
-- Indices de la tabla `business_emoji_groups`
--
ALTER TABLE `business_emoji_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_business_group` (`business_id`,`group_id`),
  ADD KEY `group_id` (`group_id`);

--
-- Indices de la tabla `business_emoji_links`
--
ALTER TABLE `business_emoji_links`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_business_emoji` (`business_id`,`emoji_id`),
  ADD KEY `emoji_id` (`emoji_id`);

--
-- Indices de la tabla `business_icons`
--
ALTER TABLE `business_icons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `business_type` (`business_type`);

--
-- Indices de la tabla `business_images`
--
ALTER TABLE `business_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `business_id` (`business_id`);

--
-- Indices de la tabla `business_subcategories`
--
ALTER TABLE `business_subcategories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `category_id` (`category_id`);

--
-- Indices de la tabla `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `certificaciones_profesionales`
--
ALTER TABLE `certificaciones_profesionales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_business` (`business_id`),
  ADD KEY `idx_tipo` (`tipo`),
  ADD KEY `idx_verificado` (`verificado`),
  ADD KEY `idx_destacada` (`destacada`);

--
-- Indices de la tabla `clasificacion_niza`
--
ALTER TABLE `clasificacion_niza`
  ADD PRIMARY KEY (`id`),
  ADD KEY `marca_id` (`marca_id`);

--
-- Indices de la tabla `comercios`
--
ALTER TABLE `comercios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `business_id` (`business_id`);

--
-- Indices de la tabla `compras_paquetes`
--
ALTER TABLE `compras_paquetes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paquete_id` (`paquete_id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_email` (`email_cliente`),
  ADD KEY `idx_business` (`business_id`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_vigencia` (`fecha_inicio_vigencia`,`fecha_fin_vigencia`),
  ADD KEY `idx_usuario_estado` (`usuario_id`,`estado`);

--
-- Indices de la tabla `consultas_destinatarios`
--
ALTER TABLE `consultas_destinatarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cd_consulta_negocio` (`consulta_id`,`business_id`),
  ADD KEY `idx_cd_negocio` (`business_id`);

--
-- Indices de la tabla `consultas_masivas`
--
ALTER TABLE `consultas_masivas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cm_user` (`user_id`),
  ADD KEY `idx_cm_tipo` (`tipo`),
  ADD KEY `idx_cm_created_at` (`created_at`);

--
-- Indices de la tabla `consultas_respuestas`
--
ALTER TABLE `consultas_respuestas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cr_consulta` (`consulta_id`),
  ADD KEY `idx_cr_business` (`business_id`),
  ADD KEY `idx_cr_created_at` (`created_at`);

--
-- Indices de la tabla `content_reports`
--
ALTER TABLE `content_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reports_status` (`status`),
  ADD KEY `idx_reports_content` (`content_type`,`content_id`),
  ADD KEY `idx_reports_reporter` (`reporter_user_id`),
  ADD KEY `idx_reports_created` (`created_at`);

--
-- Indices de la tabla `convocatorias`
--
ALTER TABLE `convocatorias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_conv_business` (`business_id`),
  ADD KEY `idx_conv_user` (`user_id`),
  ADD KEY `idx_conv_estado` (`estado`);

--
-- Indices de la tabla `convocatoria_destinatarios`
--
ALTER TABLE `convocatoria_destinatarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_conv_dest` (`convocatoria_id`,`business_id`),
  ADD KEY `idx_cd_business` (`business_id`);

--
-- Indices de la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indices de la tabla `curso_inscripciones`
--
ALTER TABLE `curso_inscripciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `curso_id` (`curso_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `disponibles_items`
--
ALTER TABLE `disponibles_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_disp_business` (`business_id`),
  ADD KEY `idx_disp_activo` (`business_id`,`activo`);

--
-- Indices de la tabla `disponibles_solicitudes`
--
ALTER TABLE `disponibles_solicitudes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dispsol_business` (`business_id`),
  ADD KEY `idx_dispsol_user` (`user_id`),
  ADD KEY `idx_dispsol_estado` (`business_id`,`estado`);

--
-- Indices de la tabla `disponibles_solicitud_items`
--
ALTER TABLE `disponibles_solicitud_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sol_item` (`solicitud_id`,`item_id`),
  ADD KEY `idx_dispsolitem_item` (`item_id`);

--
-- Indices de la tabla `emoji_favorites`
--
ALTER TABLE `emoji_favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`emoji_id`),
  ADD KEY `user_id_2` (`user_id`),
  ADD KEY `emoji_id` (`emoji_id`);

--
-- Indices de la tabla `emoji_groups`
--
ALTER TABLE `emoji_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indices de la tabla `emoji_group_members`
--
ALTER TABLE `emoji_group_members`
  ADD PRIMARY KEY (`group_id`,`emoji_id`),
  ADD KEY `emoji_id` (`emoji_id`);

--
-- Indices de la tabla `emoji_history`
--
ALTER TABLE `emoji_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `entity_type` (`entity_type`,`entity_id`),
  ADD KEY `user` (`user`),
  ADD KEY `action_at` (`action_at`);

--
-- Indices de la tabla `emoji_images`
--
ALTER TABLE `emoji_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `emoji_id` (`emoji_id`),
  ADD KEY `created_by_user_id` (`created_by_user_id`);

--
-- Indices de la tabla `emoji_markers`
--
ALTER TABLE `emoji_markers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `lat` (`lat`,`lng`),
  ADD KEY `idx_emoji_price_range` (`price_range`);

--
-- Indices de la tabla `emoji_relations`
--
ALTER TABLE `emoji_relations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `from_id` (`from_id`),
  ADD KEY `to_id` (`to_id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indices de la tabla `encuestas`
--
ALTER TABLE `encuestas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_encuestas_coords` (`lat`,`lng`),
  ADD KEY `idx_encuestas_fechas` (`fecha_creacion`,`fecha_expiracion`);

--
-- Indices de la tabla `encuestas_zona`
--
ALTER TABLE `encuestas_zona`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `encuesta_participaciones`
--
ALTER TABLE `encuesta_participaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_unica_participacion` (`encuesta_id`,`user_id`),
  ADD KEY `idx_encuesta_id` (`encuesta_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indices de la tabla `encuesta_questions`
--
ALTER TABLE `encuesta_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_question_encuesta` (`encuesta_id`);

--
-- Indices de la tabla `encuesta_responses`
--
ALTER TABLE `encuesta_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_response_question` (`question_id`),
  ADD KEY `fk_response_user` (`user_id`);

--
-- Indices de la tabla `entidad_relaciones`
--
ALTER TABLE `entidad_relaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rel_source` (`source_entity_type`,`source_entity_id`),
  ADD KEY `idx_rel_target` (`target_entity_type`,`target_entity_id`),
  ADD KEY `idx_rel_source_mapita` (`source_mapita_id`),
  ADD KEY `idx_rel_target_mapita` (`target_mapita_id`);

--
-- Indices de la tabla `estrategia_optima`
--
ALTER TABLE `estrategia_optima`
  ADD PRIMARY KEY (`id`),
  ADD KEY `marca_id` (`marca_id`);

--
-- Indices de la tabla `eventos`
--
ALTER TABLE `eventos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_eventos_coords` (`lat`,`lng`),
  ADD KEY `idx_eventos_fecha` (`fecha`),
  ADD KEY `idx_coords` (`lat`,`lng`);

--
-- Indices de la tabla `horarios_disponibles`
--
ALTER TABLE `horarios_disponibles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_horario` (`business_id`,`dia_semana`,`hora_inicio`,`tipo_sesion`),
  ADD KEY `idx_business_dia` (`business_id`,`dia_semana`),
  ADD KEY `idx_dia_hora` (`dia_semana`,`hora_inicio`),
  ADD KEY `idx_tipo_sesion` (`tipo_sesion`),
  ADD KEY `idx_activo` (`activo`);

--
-- Indices de la tabla `hoteles`
--
ALTER TABLE `hoteles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `business_id` (`business_id`);

--
-- Indices de la tabla `industrial_sectors`
--
ALTER TABLE `industrial_sectors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_is_type` (`type`),
  ADD KEY `idx_is_status` (`status`);

--
-- Indices de la tabla `industries`
--
ALTER TABLE `industries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ind_user_id` (`user_id`),
  ADD KEY `idx_ind_industrial_sector_id` (`industrial_sector_id`),
  ADD KEY `idx_ind_name` (`name`),
  ADD KEY `idx_ind_status` (`status`),
  ADD KEY `idx_industries_country_code` (`country_code`),
  ADD KEY `idx_industries_business_id` (`business_id`),
  ADD KEY `idx_industries_brand_id` (`brand_id`);

--
-- Indices de la tabla `inmobiliarias`
--
ALTER TABLE `inmobiliarias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `business_id` (`business_id`);

--
-- Indices de la tabla `inmuebles`
--
ALTER TABLE `inmuebles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inm_business` (`business_id`),
  ADD KEY `idx_inm_operacion` (`operacion`),
  ADD KEY `idx_inm_activo` (`activo`);

--
-- Indices de la tabla `job_applications`
--
ALTER TABLE `job_applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_jobapp_user_biz` (`business_id`,`user_id`),
  ADD KEY `idx_jobapp_business` (`business_id`),
  ADD KEY `idx_jobapp_user` (`user_id`),
  ADD KEY `idx_jobapp_estado` (`business_id`,`estado`);

--
-- Indices de la tabla `marcadores`
--
ALTER TABLE `marcadores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indices de la tabla `marcas`
--
ALTER TABLE `marcas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_scope` (`scope`),
  ADD KEY `idx_founded` (`founded_year`);

--
-- Indices de la tabla `modelos_negocio`
--
ALTER TABLE `modelos_negocio`
  ADD PRIMARY KEY (`id`),
  ADD KEY `marca_id` (`marca_id`);

--
-- Indices de la tabla `monetizacion`
--
ALTER TABLE `monetizacion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `marca_id` (`marca_id`);

--
-- Indices de la tabla `negocios_radio_operacion`
--
ALTER TABLE `negocios_radio_operacion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `negocio_id` (`negocio_id`),
  ADD KEY `categoria_servicio` (`categoria_servicio`),
  ADD KEY `centro_lat` (`centro_lat`,`centro_lng`),
  ADD KEY `disponible` (`disponible`,`activo`);

--
-- Indices de la tabla `noticias`
--
ALTER TABLE `noticias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activa` (`activa`),
  ADD KEY `idx_fecha` (`fecha_publicacion`),
  ADD KEY `idx_categoria` (`categoria`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_not_coords` (`lat`,`lng`);

--
-- Indices de la tabla `ofertas`
--
ALTER TABLE `ofertas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ofertas_coords` (`lat`,`lng`),
  ADD KEY `idx_ofertas_fechas` (`fecha_inicio`,`fecha_expiracion`),
  ADD KEY `fk_ofertas_business` (`business_id`),
  ADD KEY `idx_ofertas_destacada` (`business_id`,`activo`,`es_destacada`);

--
-- Indices de la tabla `ownership_transfers`
--
ALTER TABLE `ownership_transfers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ownership_transfers_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_ownership_transfers_status` (`status`),
  ADD KEY `idx_ownership_transfers_to_user` (`to_user_id`,`status`),
  ADD KEY `fk_ownership_transfers_from_user` (`from_user_id`);

--
-- Indices de la tabla `paquetes_servicios`
--
ALTER TABLE `paquetes_servicios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_business` (`business_id`),
  ADD KEY `idx_tipo` (`tipo`),
  ADD KEY `idx_destacado` (`destacado`,`orden`),
  ADD KEY `idx_activo` (`activo`),
  ADD KEY `idx_vigencia` (`fecha_inicio`,`fecha_expiracion`);

--
-- Indices de la tabla `polygons`
--
ALTER TABLE `polygons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_polygon_name` (`name`);

--
-- Indices de la tabla `portfolio_trabajos`
--
ALTER TABLE `portfolio_trabajos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_business` (`business_id`),
  ADD KEY `idx_categoria` (`categoria`),
  ADD KEY `idx_destacado` (`destacado`,`orden`),
  ADD KEY `idx_publico` (`publico`);

--
-- Indices de la tabla `preguntas_encuesta`
--
ALTER TABLE `preguntas_encuesta`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `business_id` (`business_id`);

--
-- Indices de la tabla `profesionales`
--
ALTER TABLE `profesionales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `business_id` (`business_id`);

--
-- Indices de la tabla `promociones`
--
ALTER TABLE `promociones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `business_id` (`business_id`);

--
-- Indices de la tabla `rate_limit_log`
--
ALTER TABLE `rate_limit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rl_ip_endpoint` (`ip`,`endpoint`,`hit_at`);

--
-- Indices de la tabla `remates`
--
ALTER TABLE `remates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_remates_business` (`business_id`),
  ADD KEY `idx_remates_activo_fechas` (`activo`,`fecha_inicio`,`fecha_fin`,`fecha_cierre`);

--
-- Indices de la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_reserva` (`business_id`,`fecha_reserva`,`hora_inicio`,`modalidad`),
  ADD KEY `idx_business_fecha` (`business_id`,`fecha_reserva`),
  ADD KEY `idx_fecha` (`fecha_reserva`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_email` (`email_cliente`),
  ADD KEY `idx_confirmada` (`confirmada_por_proveedor`,`fecha_reserva`);

--
-- Indices de la tabla `respuestas_encuesta`
--
ALTER TABLE `respuestas_encuesta`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_unica_respuesta_usuario` (`encuesta_id`,`pregunta_id`,`user_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indices de la tabla `restaurantes`
--
ALTER TABLE `restaurantes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `business_id` (`business_id`);

--
-- Indices de la tabla `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_review` (`business_id`,`user_id`),
  ADD KEY `fk_review_user` (`user_id`);

--
-- Indices de la tabla `riesgo_legal`
--
ALTER TABLE `riesgo_legal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `marca_id` (`marca_id`);

--
-- Indices de la tabla `servicios_profesionales`
--
ALTER TABLE `servicios_profesionales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `business_id` (`business_id`),
  ADD KEY `idx_categoria` (`categoria_servicio`),
  ADD KEY `idx_modalidad_presencial` (`modalidad_presencial`),
  ADD KEY `idx_modalidad_online` (`modalidad_online`),
  ADD KEY `idx_modalidad_domicilio` (`modalidad_domicilio`),
  ADD KEY `idx_activo` (`activo`);

--
-- Indices de la tabla `share_tokens`
--
ALTER TABLE `share_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token_unique` (`token`),
  ADD KEY `encuesta_id_idx` (`encuesta_id`),
  ADD KEY `user_id_idx` (`user_id`);

--
-- Indices de la tabla `subcategories`
--
ALTER TABLE `subcategories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `category_id` (`category_id`);

--
-- Indices de la tabla `system_config`
--
ALTER TABLE `system_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `config_key` (`config_key`);

--
-- Indices de la tabla `transmisiones`
--
ALTER TABLE `transmisiones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activo` (`activo`),
  ADD KEY `idx_en_vivo` (`en_vivo`),
  ADD KEY `idx_coords` (`lat`,`lng`),
  ADD KEY `idx_tipo` (`tipo`),
  ADD KEY `idx_trans_ventana` (`activo`,`fecha_inicio`,`fecha_fin`);

--
-- Indices de la tabla `transmisiones_vivo`
--
ALTER TABLE `transmisiones_vivo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `organizador_id` (`organizador_id`);

--
-- Indices de la tabla `transmision_participantes`
--
ALTER TABLE `transmision_participantes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transmision_id` (`transmision_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `transporte`
--
ALTER TABLE `transporte`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `business_id` (`business_id`);

--
-- Indices de la tabla `transporte_asignaciones`
--
ALTER TABLE `transporte_asignaciones`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `trivias`
--
ALTER TABLE `trivias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activa` (`activa`),
  ADD KEY `idx_tri_coords` (`lat`,`lng`);

--
-- Indices de la tabla `trivia_games`
--
ALTER TABLE `trivia_games`
  ADD PRIMARY KEY (`game_id`);

--
-- Indices de la tabla `trivia_scores`
--
ALTER TABLE `trivia_scores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_trivia` (`trivia_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_puntos` (`puntos`);

--
-- Indices de la tabla `trivia_stats`
--
ALTER TABLE `trivia_stats`
  ADD PRIMARY KEY (`stat_id`),
  ADD KEY `game_id` (`game_id`);

--
-- Indices de la tabla `turnos`
--
ALTER TABLE `turnos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `business_id` (`business_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indices de la tabla `valoraciones_servicios`
--
ALTER TABLE `valoraciones_servicios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reserva_id` (`reserva_id`),
  ADD KEY `idx_business` (`business_id`),
  ADD KEY `idx_calificacion` (`calificacion_general`),
  ADD KEY `idx_verificada` (`verificada`),
  ADD KEY `idx_aprobada` (`aprobada`),
  ADD KEY `idx_created` (`created_at` DESC);

--
-- Indices de la tabla `vehiculos_venta`
--
ALTER TABLE `vehiculos_venta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vehiculos_business` (`business_id`),
  ADD KEY `idx_vehiculos_tipo` (`tipo_vehiculo`);

--
-- Indices de la tabla `wt_messages`
--
ALTER TABLE `wt_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_wt_entity_time` (`entity_type`,`entity_id`,`created_at`),
  ADD KEY `idx_wt_sender_time` (`sender_key`,`created_at`);

--
-- Indices de la tabla `wt_presence`
--
ALTER TABLE `wt_presence`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_wt_presence` (`entity_type`,`entity_id`,`sender_key`),
  ADD KEY `idx_wt_presence_seen` (`entity_type`,`entity_id`,`last_seen`);

--
-- Indices de la tabla `wt_user_areas`
--
ALTER TABLE `wt_user_areas`
  ADD PRIMARY KEY (`user_id`,`area_slug`),
  ADD KEY `idx_wt_area_slug_user` (`area_slug`,`user_id`);

--
-- Indices de la tabla `wt_user_blocks`
--
ALTER TABLE `wt_user_blocks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_wt_block` (`blocker_user_id`,`blocked_user_id`),
  ADD KEY `idx_wt_block_blocker` (`blocker_user_id`),
  ADD KEY `idx_wt_block_blocked` (`blocked_user_id`),
  ADD KEY `idx_wt_block_blocked_blocker` (`blocked_user_id`,`blocker_user_id`);

--
-- Indices de la tabla `wt_user_preferences`
--
ALTER TABLE `wt_user_preferences`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `analisis_marcario`
--
ALTER TABLE `analisis_marcario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `articulos`
--
ALTER TABLE `articulos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `attachments`
--
ALTER TABLE `attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `brand_delegations`
--
ALTER TABLE `brand_delegations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `brand_gallery`
--
ALTER TABLE `brand_gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `brand_gallery_v2`
--
ALTER TABLE `brand_gallery_v2`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `businesses`
--
ALTER TABLE `businesses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9151;

--
-- AUTO_INCREMENT de la tabla `business_categories`
--
ALTER TABLE `business_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `business_delegations`
--
ALTER TABLE `business_delegations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `business_emoji_groups`
--
ALTER TABLE `business_emoji_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `business_emoji_links`
--
ALTER TABLE `business_emoji_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `business_icons`
--
ALTER TABLE `business_icons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT de la tabla `business_images`
--
ALTER TABLE `business_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `business_subcategories`
--
ALTER TABLE `business_subcategories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `certificaciones_profesionales`
--
ALTER TABLE `certificaciones_profesionales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `clasificacion_niza`
--
ALTER TABLE `clasificacion_niza`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `comercios`
--
ALTER TABLE `comercios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `compras_paquetes`
--
ALTER TABLE `compras_paquetes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `consultas_destinatarios`
--
ALTER TABLE `consultas_destinatarios`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `consultas_masivas`
--
ALTER TABLE `consultas_masivas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `consultas_respuestas`
--
ALTER TABLE `consultas_respuestas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `content_reports`
--
ALTER TABLE `content_reports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `convocatorias`
--
ALTER TABLE `convocatorias`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `convocatoria_destinatarios`
--
ALTER TABLE `convocatoria_destinatarios`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cursos`
--
ALTER TABLE `cursos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `curso_inscripciones`
--
ALTER TABLE `curso_inscripciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `disponibles_items`
--
ALTER TABLE `disponibles_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `disponibles_solicitudes`
--
ALTER TABLE `disponibles_solicitudes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `disponibles_solicitud_items`
--
ALTER TABLE `disponibles_solicitud_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `emoji_favorites`
--
ALTER TABLE `emoji_favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `emoji_groups`
--
ALTER TABLE `emoji_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `emoji_history`
--
ALTER TABLE `emoji_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `emoji_images`
--
ALTER TABLE `emoji_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `emoji_markers`
--
ALTER TABLE `emoji_markers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `emoji_relations`
--
ALTER TABLE `emoji_relations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `encuestas`
--
ALTER TABLE `encuestas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `encuestas_zona`
--
ALTER TABLE `encuestas_zona`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `encuesta_participaciones`
--
ALTER TABLE `encuesta_participaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `encuesta_questions`
--
ALTER TABLE `encuesta_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `encuesta_responses`
--
ALTER TABLE `encuesta_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `entidad_relaciones`
--
ALTER TABLE `entidad_relaciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estrategia_optima`
--
ALTER TABLE `estrategia_optima`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `eventos`
--
ALTER TABLE `eventos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `horarios_disponibles`
--
ALTER TABLE `horarios_disponibles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT de la tabla `hoteles`
--
ALTER TABLE `hoteles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `industrial_sectors`
--
ALTER TABLE `industrial_sectors`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `industries`
--
ALTER TABLE `industries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inmobiliarias`
--
ALTER TABLE `inmobiliarias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inmuebles`
--
ALTER TABLE `inmuebles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `job_applications`
--
ALTER TABLE `job_applications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `marcadores`
--
ALTER TABLE `marcadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `marcas`
--
ALTER TABLE `marcas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `modelos_negocio`
--
ALTER TABLE `modelos_negocio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `monetizacion`
--
ALTER TABLE `monetizacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `negocios_radio_operacion`
--
ALTER TABLE `negocios_radio_operacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `noticias`
--
ALTER TABLE `noticias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ofertas`
--
ALTER TABLE `ofertas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `ownership_transfers`
--
ALTER TABLE `ownership_transfers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `paquetes_servicios`
--
ALTER TABLE `paquetes_servicios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `polygons`
--
ALTER TABLE `polygons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `portfolio_trabajos`
--
ALTER TABLE `portfolio_trabajos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `preguntas_encuesta`
--
ALTER TABLE `preguntas_encuesta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `profesionales`
--
ALTER TABLE `profesionales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `promociones`
--
ALTER TABLE `promociones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `rate_limit_log`
--
ALTER TABLE `rate_limit_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `remates`
--
ALTER TABLE `remates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reservas`
--
ALTER TABLE `reservas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `respuestas_encuesta`
--
ALTER TABLE `respuestas_encuesta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `restaurantes`
--
ALTER TABLE `restaurantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `riesgo_legal`
--
ALTER TABLE `riesgo_legal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `servicios_profesionales`
--
ALTER TABLE `servicios_profesionales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `share_tokens`
--
ALTER TABLE `share_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `subcategories`
--
ALTER TABLE `subcategories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT de la tabla `system_config`
--
ALTER TABLE `system_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT de la tabla `transmisiones`
--
ALTER TABLE `transmisiones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `transmisiones_vivo`
--
ALTER TABLE `transmisiones_vivo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `transmision_participantes`
--
ALTER TABLE `transmision_participantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `transporte`
--
ALTER TABLE `transporte`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `transporte_asignaciones`
--
ALTER TABLE `transporte_asignaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `trivias`
--
ALTER TABLE `trivias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `trivia_scores`
--
ALTER TABLE `trivia_scores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `trivia_stats`
--
ALTER TABLE `trivia_stats`
  MODIFY `stat_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `turnos`
--
ALTER TABLE `turnos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `valoraciones_servicios`
--
ALTER TABLE `valoraciones_servicios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `vehiculos_venta`
--
ALTER TABLE `vehiculos_venta`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `wt_messages`
--
ALTER TABLE `wt_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `wt_presence`
--
ALTER TABLE `wt_presence`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7023;

--
-- AUTO_INCREMENT de la tabla `wt_user_blocks`
--
ALTER TABLE `wt_user_blocks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_businesses_with_icons`
--
DROP TABLE IF EXISTS `v_businesses_with_icons`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u580580751_mapita`@`127.0.0.1` SQL SECURITY DEFINER VIEW `v_businesses_with_icons`  AS SELECT `b`.`id` AS `id`, `b`.`user_id` AS `user_id`, `b`.`name` AS `name`, `b`.`address` AS `address`, `b`.`lat` AS `lat`, `b`.`lng` AS `lng`, `b`.`phone` AS `phone`, `b`.`email` AS `email`, `b`.`website` AS `website`, `b`.`business_type` AS `business_type`, `b`.`visible` AS `visible`, `b`.`status` AS `status`, `b`.`created_at` AS `created_at`, `b`.`updated_at` AS `updated_at`, `b`.`price_range` AS `price_range`, `b`.`description` AS `description`, `b`.`subcategory_id` AS `subcategory_id`, coalesce(`bi`.`emoji`,'📍') AS `emoji`, `bi`.`icon_class` AS `icon_class` FROM (`businesses` `b` left join `business_icons` `bi` on(`b`.`business_type` = `bi`.`business_type`)) ;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `analisis_marcario`
--
ALTER TABLE `analisis_marcario`
  ADD CONSTRAINT `analisis_marcario_ibfk_1` FOREIGN KEY (`marca_id`) REFERENCES `marcas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `articulos`
--
ALTER TABLE `articulos`
  ADD CONSTRAINT `articulos_ibfk_1` FOREIGN KEY (`autor_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `attachments`
--
ALTER TABLE `attachments`
  ADD CONSTRAINT `attachments_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attachments_ibfk_2` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `brand_delegations`
--
ALTER TABLE `brand_delegations`
  ADD CONSTRAINT `fk_brand_delegations_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_brand_delegations_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_brand_delegations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `brand_gallery`
--
ALTER TABLE `brand_gallery`
  ADD CONSTRAINT `fk_gallery_brand` FOREIGN KEY (`brand_id`) REFERENCES `marcas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `brand_gallery_v2`
--
ALTER TABLE `brand_gallery_v2`
  ADD CONSTRAINT `fk_bgv2_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `businesses`
--
ALTER TABLE `businesses`
  ADD CONSTRAINT `businesses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_subcategory` FOREIGN KEY (`subcategory_id`) REFERENCES `business_subcategories` (`id`);

--
-- Filtros para la tabla `business_delegations`
--
ALTER TABLE `business_delegations`
  ADD CONSTRAINT `fk_business_delegations_business` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_business_delegations_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_business_delegations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `business_emoji_groups`
--
ALTER TABLE `business_emoji_groups`
  ADD CONSTRAINT `business_emoji_groups_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `business_emoji_groups_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `emoji_groups` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `business_emoji_links`
--
ALTER TABLE `business_emoji_links`
  ADD CONSTRAINT `business_emoji_links_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `business_emoji_links_ibfk_2` FOREIGN KEY (`emoji_id`) REFERENCES `emoji_markers` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `business_images`
--
ALTER TABLE `business_images`
  ADD CONSTRAINT `business_images_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `business_subcategories`
--
ALTER TABLE `business_subcategories`
  ADD CONSTRAINT `business_subcategories_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `business_categories` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `certificaciones_profesionales`
--
ALTER TABLE `certificaciones_profesionales`
  ADD CONSTRAINT `certificaciones_profesionales_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `clasificacion_niza`
--
ALTER TABLE `clasificacion_niza`
  ADD CONSTRAINT `clasificacion_niza_ibfk_1` FOREIGN KEY (`marca_id`) REFERENCES `marcas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `comercios`
--
ALTER TABLE `comercios`
  ADD CONSTRAINT `comercios_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `compras_paquetes`
--
ALTER TABLE `compras_paquetes`
  ADD CONSTRAINT `compras_paquetes_ibfk_1` FOREIGN KEY (`paquete_id`) REFERENCES `paquetes_servicios` (`id`),
  ADD CONSTRAINT `compras_paquetes_ibfk_2` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`);

--
-- Filtros para la tabla `consultas_destinatarios`
--
ALTER TABLE `consultas_destinatarios`
  ADD CONSTRAINT `fk_cd_consulta` FOREIGN KEY (`consulta_id`) REFERENCES `consultas_masivas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cd_negocio` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `consultas_masivas`
--
ALTER TABLE `consultas_masivas`
  ADD CONSTRAINT `fk_cm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `consultas_respuestas`
--
ALTER TABLE `consultas_respuestas`
  ADD CONSTRAINT `fk_cr_consulta` FOREIGN KEY (`consulta_id`) REFERENCES `consultas_masivas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `convocatorias`
--
ALTER TABLE `convocatorias`
  ADD CONSTRAINT `fk_conv_business` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_conv_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `convocatoria_destinatarios`
--
ALTER TABLE `convocatoria_destinatarios`
  ADD CONSTRAINT `fk_cd_business` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cd_conv` FOREIGN KEY (`convocatoria_id`) REFERENCES `convocatorias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD CONSTRAINT `cursos_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `curso_inscripciones`
--
ALTER TABLE `curso_inscripciones`
  ADD CONSTRAINT `curso_inscripciones_ibfk_1` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`),
  ADD CONSTRAINT `curso_inscripciones_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `disponibles_items`
--
ALTER TABLE `disponibles_items`
  ADD CONSTRAINT `fk_dispitems_business` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `disponibles_solicitudes`
--
ALTER TABLE `disponibles_solicitudes`
  ADD CONSTRAINT `fk_dispsol_business` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `disponibles_solicitud_items`
--
ALTER TABLE `disponibles_solicitud_items`
  ADD CONSTRAINT `fk_dispsolitem_item` FOREIGN KEY (`item_id`) REFERENCES `disponibles_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dispsolitem_sol` FOREIGN KEY (`solicitud_id`) REFERENCES `disponibles_solicitudes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `emoji_favorites`
--
ALTER TABLE `emoji_favorites`
  ADD CONSTRAINT `emoji_favorites_ibfk_1` FOREIGN KEY (`emoji_id`) REFERENCES `emoji_markers` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `emoji_group_members`
--
ALTER TABLE `emoji_group_members`
  ADD CONSTRAINT `emoji_group_members_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `emoji_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `emoji_group_members_ibfk_2` FOREIGN KEY (`emoji_id`) REFERENCES `emoji_markers` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `emoji_images`
--
ALTER TABLE `emoji_images`
  ADD CONSTRAINT `emoji_images_ibfk_1` FOREIGN KEY (`emoji_id`) REFERENCES `emoji_markers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `emoji_images_ibfk_2` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `emoji_relations`
--
ALTER TABLE `emoji_relations`
  ADD CONSTRAINT `emoji_relations_ibfk_1` FOREIGN KEY (`from_id`) REFERENCES `emoji_markers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `emoji_relations_ibfk_2` FOREIGN KEY (`to_id`) REFERENCES `emoji_markers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `emoji_relations_ibfk_3` FOREIGN KEY (`group_id`) REFERENCES `emoji_groups` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `encuesta_participaciones`
--
ALTER TABLE `encuesta_participaciones`
  ADD CONSTRAINT `fk_participacion_encuesta` FOREIGN KEY (`encuesta_id`) REFERENCES `encuestas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_participacion_usuario` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `encuesta_questions`
--
ALTER TABLE `encuesta_questions`
  ADD CONSTRAINT `fk_question_encuesta` FOREIGN KEY (`encuesta_id`) REFERENCES `encuestas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `encuesta_responses`
--
ALTER TABLE `encuesta_responses`
  ADD CONSTRAINT `fk_response_question` FOREIGN KEY (`question_id`) REFERENCES `encuesta_questions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_response_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `estrategia_optima`
--
ALTER TABLE `estrategia_optima`
  ADD CONSTRAINT `estrategia_optima_ibfk_1` FOREIGN KEY (`marca_id`) REFERENCES `marcas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `horarios_disponibles`
--
ALTER TABLE `horarios_disponibles`
  ADD CONSTRAINT `horarios_disponibles_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `hoteles`
--
ALTER TABLE `hoteles`
  ADD CONSTRAINT `hoteles_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `industries`
--
ALTER TABLE `industries`
  ADD CONSTRAINT `fk_industries_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_industries_business` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `inmobiliarias`
--
ALTER TABLE `inmobiliarias`
  ADD CONSTRAINT `inmobiliarias_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `inmuebles`
--
ALTER TABLE `inmuebles`
  ADD CONSTRAINT `fk_inm_business` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `job_applications`
--
ALTER TABLE `job_applications`
  ADD CONSTRAINT `fk_jobapp_business` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `marcas`
--
ALTER TABLE `marcas`
  ADD CONSTRAINT `marcas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `modelos_negocio`
--
ALTER TABLE `modelos_negocio`
  ADD CONSTRAINT `modelos_negocio_ibfk_1` FOREIGN KEY (`marca_id`) REFERENCES `marcas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `monetizacion`
--
ALTER TABLE `monetizacion`
  ADD CONSTRAINT `monetizacion_ibfk_1` FOREIGN KEY (`marca_id`) REFERENCES `marcas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `noticias`
--
ALTER TABLE `noticias`
  ADD CONSTRAINT `noticias_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `ownership_transfers`
--
ALTER TABLE `ownership_transfers`
  ADD CONSTRAINT `fk_ownership_transfers_from_user` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ownership_transfers_to_user` FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `paquetes_servicios`
--
ALTER TABLE `paquetes_servicios`
  ADD CONSTRAINT `paquetes_servicios_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `portfolio_trabajos`
--
ALTER TABLE `portfolio_trabajos`
  ADD CONSTRAINT `portfolio_trabajos_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `profesionales`
--
ALTER TABLE `profesionales`
  ADD CONSTRAINT `profesionales_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `promociones`
--
ALTER TABLE `promociones`
  ADD CONSTRAINT `promociones_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `remates`
--
ALTER TABLE `remates`
  ADD CONSTRAINT `fk_remates_business` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD CONSTRAINT `reservas_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `respuestas_encuesta`
--
ALTER TABLE `respuestas_encuesta`
  ADD CONSTRAINT `fk_respuesta_usuario` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `restaurantes`
--
ALTER TABLE `restaurantes`
  ADD CONSTRAINT `restaurantes_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_review_business` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_review_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `riesgo_legal`
--
ALTER TABLE `riesgo_legal`
  ADD CONSTRAINT `riesgo_legal_ibfk_1` FOREIGN KEY (`marca_id`) REFERENCES `marcas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `servicios_profesionales`
--
ALTER TABLE `servicios_profesionales`
  ADD CONSTRAINT `servicios_profesionales_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `subcategories`
--
ALTER TABLE `subcategories`
  ADD CONSTRAINT `subcategories_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Filtros para la tabla `transmisiones_vivo`
--
ALTER TABLE `transmisiones_vivo`
  ADD CONSTRAINT `transmisiones_vivo_ibfk_1` FOREIGN KEY (`organizador_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `transmision_participantes`
--
ALTER TABLE `transmision_participantes`
  ADD CONSTRAINT `transmision_participantes_ibfk_1` FOREIGN KEY (`transmision_id`) REFERENCES `transmisiones_vivo` (`id`),
  ADD CONSTRAINT `transmision_participantes_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `transporte`
--
ALTER TABLE `transporte`
  ADD CONSTRAINT `transporte_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `trivia_scores`
--
ALTER TABLE `trivia_scores`
  ADD CONSTRAINT `fk_score_trivia` FOREIGN KEY (`trivia_id`) REFERENCES `trivias` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_score_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `trivia_stats`
--
ALTER TABLE `trivia_stats`
  ADD CONSTRAINT `trivia_stats_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `trivia_games` (`game_id`);

--
-- Filtros para la tabla `turnos`
--
ALTER TABLE `turnos`
  ADD CONSTRAINT `turnos_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `turnos_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `valoraciones_servicios`
--
ALTER TABLE `valoraciones_servicios`
  ADD CONSTRAINT `valoraciones_servicios_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `valoraciones_servicios_ibfk_2` FOREIGN KEY (`reserva_id`) REFERENCES `reservas` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `vehiculos_venta`
--
ALTER TABLE `vehiculos_venta`
  ADD CONSTRAINT `fk_vehiculos_business` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `wt_user_areas`
--
ALTER TABLE `wt_user_areas`
  ADD CONSTRAINT `fk_wt_user_areas_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `wt_user_blocks`
--
ALTER TABLE `wt_user_blocks`
  ADD CONSTRAINT `fk_wt_block_blocked` FOREIGN KEY (`blocked_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_wt_block_blocker` FOREIGN KEY (`blocker_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `wt_user_preferences`
--
ALTER TABLE `wt_user_preferences`
  ADD CONSTRAINT `fk_wt_prefs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
