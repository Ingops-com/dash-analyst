-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 04-12-2025 a las 15:55:10
-- Versión del servidor: 11.8.3-MariaDB-log
-- Versión de PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u808144455_sgccazh_`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `analyst_companies`
--

CREATE TABLE `analyst_companies` (
  `user_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `analyst_companies`
--

INSERT INTO `analyst_companies` (`user_id`, `company_id`) VALUES
(2, 1),
(2, 2),
(3, 3),
(2, 1),
(2, 2),
(3, 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `annexes`
--

CREATE TABLE `annexes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `codigo_anexo` varchar(255) DEFAULT NULL,
  `placeholder` varchar(255) DEFAULT NULL,
  `content_type` enum('image','text','table') DEFAULT 'image',
  `table_columns` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Configuración de columnas para anexos tipo tabla' CHECK (json_valid(`table_columns`)),
  `table_header_color` varchar(7) DEFAULT '#153366' COMMENT 'Color hexadecimal para cabeceras de tabla',
  `tipo` enum('ISO 22000','PSB','Invima') DEFAULT NULL,
  `status` enum('En revisión','Aprobado','Obsoleto') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `annexes`
--

INSERT INTO `annexes` (`id`, `nombre`, `codigo_anexo`, `placeholder`, `content_type`, `table_columns`, `table_header_color`, `tipo`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Certificado de Fumigación', 'ANX-CF-01', 'Anexo_3', 'table', '[\"nombre\",\"cancion\"]', '#153366', 'PSB', 'Aprobado', '2025-10-19 10:03:33', '2025-11-17 16:35:12'),
(2, 'Factura de Insumos de Limpieza', 'ANX-FI-02', NULL, 'table', '[\"nombre\",\"pelicula\"]', '#153366', 'PSB', 'Aprobado', '2025-10-19 10:03:33', '2025-11-17 16:34:24'),
(3, 'Registro Fotográfico de Trampas', 'ANX-RF-03', 'anexo prueba', 'image', NULL, NULL, 'PSB', 'En revisión', '2025-10-19 10:03:33', '2025-11-17 16:41:00'),
(4, 'Checklist Interno de Limpieza', 'ANX-CI-04', 'Anexo 2', 'image', NULL, '#153366', 'PSB', 'Aprobado', '2025-10-19 10:03:33', '2025-11-06 02:34:49'),
(5, 'Manual de Calidad', 'ANX-MC-05', NULL, 'image', NULL, '#153366', 'ISO 22000', 'Aprobado', '2025-10-19 10:03:33', '2025-10-19 10:03:33'),
(6, 'Control de Temperaturas de Neveras', 'ANX-CT-06', NULL, 'image', NULL, '#153366', 'Invima', 'Aprobado', '2025-10-19 10:03:33', '2025-10-19 10:03:33'),
(7, 'anexo pruea', 'AP01', 'anexo_prueba', 'text', NULL, '#153366', 'PSB', 'En revisión', '2025-11-04 01:35:42', '2025-11-10 23:02:20'),
(8, 'anexoTablaPrueba', 'AHJSD34', 'Anexo 2', 'table', '[\"nombre\",\"apellido\",\"CedulA\"]', '#153366', 'ISO 22000', 'En revisión', '2025-11-12 13:23:12', '2025-11-13 19:55:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `annex_change_logs`
--

CREATE TABLE `annex_change_logs` (
  `id` int(11) NOT NULL,
  `annex_version_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` enum('Creación','Actualización','Aprobación','Rechazo') DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `annex_change_logs`
--

INSERT INTO `annex_change_logs` (`id`, `annex_version_id`, `user_id`, `action`, `comments`, `created_at`) VALUES
(1, 1, 1, 'Creación', 'Versión inicial del formato de fumigación.', '2025-10-19 10:03:33'),
(2, 2, 1, 'Creación', 'Se carga la primera versión del Manual de Calidad.', '2025-10-19 10:03:33'),
(3, 3, 2, 'Actualización', 'Ajustes en el capítulo 3.1 sobre control de proveedores.', '2025-10-19 10:03:33'),
(4, 3, 1, 'Aprobación', 'Versión 2 aprobada para publicación.', '2025-10-19 10:03:33'),
(1, 1, 1, 'Creación', 'Versión inicial del formato de fumigación.', '2025-10-19 10:03:33'),
(2, 2, 1, 'Creación', 'Se carga la primera versión del Manual de Calidad.', '2025-10-19 10:03:33'),
(3, 3, 2, 'Actualización', 'Ajustes en el capítulo 3.1 sobre control de proveedores.', '2025-10-19 10:03:33'),
(4, 3, 1, 'Aprobación', 'Versión 2 aprobada para publicación.', '2025-10-19 10:03:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `annex_versions`
--

CREATE TABLE `annex_versions` (
  `id` int(11) NOT NULL,
  `annex_id` int(11) DEFAULT NULL,
  `version_number` int(11) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `annex_versions`
--

INSERT INTO `annex_versions` (`id`, `annex_id`, `version_number`, `file_path`, `file_name`, `file_size`, `mime_type`, `created_by`, `approved_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'uploads/anexos/certificado_fumigacion_v1.pdf', 'certificado_fumigacion_v1.pdf', 102400, 'application/pdf', 1, 2, '2025-10-19 10:03:33', '2025-10-19 10:03:33'),
(2, 5, 1, 'uploads/anexos/manual_calidad_v1.pdf', 'manual_calidad_v1.pdf', 512000, 'application/pdf', 1, NULL, '2025-10-19 10:03:33', '2025-10-19 10:03:33'),
(3, 5, 2, 'uploads/anexos/manual_calidad_v2_rev.pdf', 'manual_calidad_v2_rev.pdf', 515000, 'application/pdf', 2, 1, '2025-10-19 10:03:33', '2025-10-19 10:03:33'),
(1, 1, 1, 'uploads/anexos/certificado_fumigacion_v1.pdf', 'certificado_fumigacion_v1.pdf', 102400, 'application/pdf', 1, 2, '2025-10-19 10:03:33', '2025-10-19 10:03:33'),
(2, 5, 1, 'uploads/anexos/manual_calidad_v1.pdf', 'manual_calidad_v1.pdf', 512000, 'application/pdf', 1, NULL, '2025-10-19 10:03:33', '2025-10-19 10:03:33'),
(3, 5, 2, 'uploads/anexos/manual_calidad_v2_rev.pdf', 'manual_calidad_v2_rev.pdf', 515000, 'application/pdf', 2, 1, '2025-10-19 10:03:33', '2025-10-19 10:03:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel_cache_admin@cazh.com|201.244.165.238', 'i:3;', 1764278603),
('laravel_cache_admin@cazh.com|201.244.165.238:timer', 'i:1764278603;', 1764278603),
('laravel_cache_administracion@cblk.edu.co|201.244.121.38', 'i:1;', 1764599428),
('laravel_cache_administracion@cblk.edu.co|201.244.121.38:timer', 'i:1764599428;', 1764599428),
('laravel_cache_analista1@cazh.com|201.244.165.238', 'i:2;', 1764343589),
('laravel_cache_analista1@cazh.com|201.244.165.238:timer', 'i:1764343589;', 1764343589),
('laravel_cache_analista2@cazh.com|181.236.24.78', 'i:2;', 1763940668),
('laravel_cache_analista2@cazh.com|181.236.24.78:timer', 'i:1763940668;', 1763940668),
('laravel_cache_carlosalbertozorro@gmail.com|191.156.48.208', 'i:2;', 1763939736),
('laravel_cache_carlosalbertozorro@gmail.com|191.156.48.208:timer', 'i:1763939736;', 1763939736),
('laravel_cache_david.rios@carnesdelsur.co|201.244.165.238', 'i:1;', 1764278900),
('laravel_cache_david.rios@carnesdelsur.co|201.244.165.238:timer', 'i:1764278900;', 1764278900),
('laravel_cache_direcciongeneral@cblk.edu.co|201.244.121.38', 'i:1;', 1764599508),
('laravel_cache_direcciongeneral@cblk.edu.co|201.244.121.38:timer', 'i:1764599508;', 1764599508),
('laravel_cache_juan.perez@frescos.com|181.236.24.78', 'i:1;', 1763940739),
('laravel_cache_juan.perez@frescos.com|181.236.24.78:timer', 'i:1763940739;', 1763940739),
('laravel_cache_lozanonicolas65@gmail.com|181.53.13.118', 'i:1;', 1764852928),
('laravel_cache_lozanonicolas65@gmail.com|181.53.13.118:timer', 'i:1764852928;', 1764852928);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cache_locks`
--

INSERT INTO `cache_locks` (`key`, `owner`, `expiration`) VALUES
('report:generation:company:2', 'worker-process-xyz', 1760868513);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `companies`
--

CREATE TABLE `companies` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `nit_empresa` varchar(255) DEFAULT NULL,
  `correo` varchar(255) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `telefono` varchar(255) DEFAULT NULL,
  `representante_legal` varchar(255) DEFAULT NULL,
  `encargado_sgc` varchar(255) DEFAULT NULL,
  `version` varchar(255) DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_verificacion` date DEFAULT NULL,
  `actividades` text DEFAULT NULL,
  `logo_izquierdo` varchar(255) DEFAULT NULL,
  `logo_derecho` varchar(255) DEFAULT NULL,
  `logo_pie_de_pagina` varchar(255) DEFAULT NULL,
  `habilitado` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `companies`
--

INSERT INTO `companies` (`id`, `nombre`, `nit_empresa`, `correo`, `direccion`, `telefono`, `representante_legal`, `encargado_sgc`, `version`, `fecha_inicio`, `fecha_verificacion`, `actividades`, `logo_izquierdo`, `logo_derecho`, `logo_pie_de_pagina`, `habilitado`, `created_at`, `updated_at`) VALUES
(1, 'Alimentos Frescos S.A.S', '900123452-9', 'contacto@frescos.com', 'Calle 100 # 20-30', '3101234567', 'Ana García', 'Carlos Ruiz', '1.0', '2024-01-15', '2025-01-15', 'Procesamiento de frutas y verduras', 'logos/382iBiOKql0ZNjeijH9XfwS08gzZni9zn2O3Izfk.png', NULL, NULL, 1, '2025-10-19 10:03:33', '2025-11-11 20:30:37'),
(2, 'Lácteos del Campo S.A.', '800.987.654-2', 'info@lacteosdelcampo.com', 'Carrera 5 # 15-25', '3209876543', 'Pedro Martínez', 'Sofía Gómez', '2.1', '2023-05-20', '2024-05-20', 'Producción de quesos y yogures', NULL, NULL, NULL, 1, '2025-10-19 10:03:33', '2025-10-19 10:03:33'),
(3, 'Carnes del Sur Ltda.', '901.555.888-3', 'admin@carnesdelsur.co', 'Avenida Sur # 45-10', '3158889900', 'Lucía Fernández', 'Mateo Vargas', '1.5', '2024-03-01', '2025-03-01', 'Procesamiento y empaque de productos cárnicos', NULL, NULL, NULL, 1, '2025-10-19 10:03:33', '2025-10-19 10:03:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `company_annex_submissions`
--

CREATE TABLE `company_annex_submissions` (
  `id` int(11) NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `annex_id` int(11) DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
  `content_text` text DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `status` enum('Pendiente','Aprobado','Rechazado','Obsoleto') DEFAULT NULL,
  `submitted_by` int(11) DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `company_annex_submissions`
--

INSERT INTO `company_annex_submissions` (`id`, `company_id`, `annex_id`, `program_id`, `file_path`, `file_name`, `mime_type`, `content_text`, `file_size`, `status`, `submitted_by`, `reviewed_by`, `created_at`, `updated_at`) VALUES
(75, 1, 8, 1, NULL, NULL, 'application/json', '[{\"nombre\":\"nicolas\",\"apellido\":\"lozano\",\"CedulA\":\"1007102988\"},{\"nombre\":\"daniel\",\"apellido\":\"cardenas\",\"CedulA\":\"1111111\"}]', 42, 'Pendiente', 8, NULL, '2025-11-12 13:29:09', '2025-11-13 19:55:57'),
(76, 1, 7, 1, NULL, NULL, 'text/plain', '<p>este seria el texto que sube el anexo</p>', 44, 'Pendiente', 8, NULL, '2025-11-12 13:58:01', '2025-11-12 13:58:01'),
(77, 3, 7, 1, NULL, NULL, 'text/plain', '<p>Está es una prueba</p>', 26, 'Pendiente', 8, NULL, '2025-11-13 19:41:44', '2025-11-13 19:41:44'),
(78, 1, 1, 1, NULL, NULL, 'application/json', '[{\"nombre\":\"nicolas\",\"cancion\":\"hola\"}]', 39, 'Pendiente', 8, NULL, '2025-11-17 16:35:56', '2025-11-17 16:35:56'),
(79, 1, 3, 1, 'anexos/company_1/program_1/6928bbfacc47d_Diagramas de flujo SM - SALCHICHÓN .jpeg', 'Diagramas de flujo SM - SALCHICHÓN .jpeg', 'image/jpeg', NULL, 81935, 'Pendiente', 8, NULL, '2025-11-27 21:00:42', '2025-11-27 21:00:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `company_poe_records`
--

CREATE TABLE `company_poe_records` (
  `id` int(11) NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `poe_id` int(11) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_ejecucion` date DEFAULT NULL,
  `ejecutado_por` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `company_poe_records`
--

INSERT INTO `company_poe_records` (`id`, `company_id`, `poe_id`, `observaciones`, `fecha_ejecucion`, `ejecutado_por`, `created_at`) VALUES
(1, 1, 1, 'Limpieza realizada en todas las áreas según procedimiento. Sin novedades.', '2025-10-18', 4, '2025-10-19 10:03:33'),
(2, 2, 2, 'Temperatura de cava 1 en 3°C. Cava 2 en 2.5°C. Todo OK.', '2025-10-19', 5, '2025-10-19 10:03:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `company_programs`
--

CREATE TABLE `company_programs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `company_program_annex_configs`
--

CREATE TABLE `company_program_annex_configs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `program_id` bigint(20) UNSIGNED NOT NULL,
  `annex_id` bigint(20) UNSIGNED NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `company_program_config`
--

CREATE TABLE `company_program_config` (
  `company_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `annex_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `company_program_config`
--

INSERT INTO `company_program_config` (`company_id`, `program_id`, `annex_id`) VALUES
(2, 1, 1),
(2, 2, 5),
(2, 3, 6),
(3, 1, 1),
(3, 3, 6),
(1, 1, 1),
(1, 1, 3),
(1, 1, 2),
(1, 1, 4),
(1, 1, 7),
(1, 1, 8),
(1, 3, 6);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `company_user`
--

CREATE TABLE `company_user` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `company_user`
--

INSERT INTO `company_user` (`id`, `company_id`, `user_id`, `created_at`, `updated_at`) VALUES
(2, 3, 8, '2025-11-10 05:46:10', '2025-11-10 05:46:10'),
(5, 3, 6, '2025-11-10 06:48:48', '2025-11-10 06:48:48'),
(6, 2, 5, '2025-11-10 06:48:54', '2025-11-10 06:48:54'),
(7, 1, 4, '2025-11-10 06:49:02', '2025-11-10 06:49:02'),
(10, 1, 3, '2025-11-10 06:50:52', '2025-11-10 06:50:52'),
(11, 3, 2, '2025-11-10 06:50:57', '2025-11-10 06:50:57'),
(12, 2, 1, '2025-11-10 06:53:13', '2025-11-10 06:53:13'),
(17, 1, 10, '2025-11-23 23:32:14', '2025-11-23 23:32:14'),
(18, 3, 10, '2025-11-23 23:34:20', '2025-11-23 23:34:20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `failed_jobs`
--

INSERT INTO `failed_jobs` (`id`, `uuid`, `connection`, `queue`, `payload`, `exception`, `failed_at`) VALUES
(1, 'uuid-unico-de-job-fallido-123', 'redis', 'default', '{\"job\":\"App\\\\Jobs\\\\SyncWithApi\"}', 'Exception: API connection timeout', '2025-10-19 10:03:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `jobs`
--

INSERT INTO `jobs` (`id`, `queue`, `payload`, `attempts`, `reserved_at`, `available_at`, `created_at`) VALUES
(1, 'emails', '{\"job\":\"App\\\\Jobs\\\\SendWeeklyReport\",\"data\":{\"companyId\":1}}', 0, NULL, 1760868213, 1760868213);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(2, '2025_11_03_163138_fix_annexes_id_auto_increment', 1),
(3, '2025_11_03_163841_add_template_path_to_programs_table', 2),
(4, '2025_11_03_170500_add_placeholder_to_annexes_table', 3),
(5, '2025_11_04_100000_create_company_program_annex_configs_table', 4),
(6, '2025_11_09_100000_add_content_type_to_annexes_table', 5),
(7, '2025_11_09_212048_add_content_text_to_company_annex_submissions_table', 6),
(8, '2025_11_09_234246_create_company_user_and_company_programs_tables', 7),
(9, '2025_11_12_072423_add_table_config_to_annexes_table', 8),
(10, '2025_11_12_074354_update_content_type_enum_in_annexes_table', 9),
(11, '2025_11_12_092422_add_generate_metadata_to_annexes_table', 10),
(12, '2025_11_12_100506_remove_generate_metadata_from_annexes_table', 11);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`email`, `token`, `created_at`) VALUES
('maria.lopez@lacteosdelcampo.com', 'tokendejemplo123456789abcdef', '2025-10-19 10:03:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `poes`
--

CREATE TABLE `poes` (
  `id` int(11) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `frecuencia` varchar(255) DEFAULT NULL,
  `codigo_poe` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `poes`
--

INSERT INTO `poes` (`id`, `program_id`, `nombre`, `descripcion`, `frecuencia`, `codigo_poe`, `created_at`, `updated_at`) VALUES
(1, 1, 'Limpieza de Áreas Comunes', 'Procedimiento para la limpieza y desinfección de pasillos y oficinas.', 'Diaria', 'POE-AC-01', '2025-10-19 10:03:33', '2025-10-19 10:03:33'),
(2, 3, 'Verificación de Temperatura en Cavas', 'Procedimiento para medir y registrar la temperatura de las cavas de refrigeración.', 'Cada 6 horas', 'POE-TC-01', '2025-10-19 10:03:33', '2025-10-19 10:03:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `programs`
--

CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `version` varchar(255) DEFAULT NULL,
  `codigo` varchar(255) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `tipo` enum('ISO 22000','PSB','Invima') DEFAULT NULL,
  `template_path` varchar(255) DEFAULT NULL COMMENT 'Ruta relativa de la plantilla Word en storage/plantillas/',
  `description` text DEFAULT NULL COMMENT 'Descripción del programa/documento',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `programs`
--

INSERT INTO `programs` (`id`, `nombre`, `version`, `codigo`, `fecha`, `tipo`, `template_path`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Plan de Saneamiento Básico', '1.0', 'PSB-001', '2025-01-01', 'PSB', 'planDeSaneamientoBasico/Plantilla.docx', 'Documento PSB (Plan de Saneamiento Básico) con anexos de fumigación, limpieza y control de plagas', '2025-10-19 10:03:33', '2025-11-03 21:40:03'),
(2, 'Sistema de Gestión de Calidad', '2.5', 'ISO-22000-001', '2025-03-10', 'ISO 22000', NULL, NULL, '2025-10-19 10:03:33', '2025-10-19 10:03:33'),
(3, 'Buenas Prácticas de Manufactura', '1.2', 'INV-BPM-001', '2024-11-05', 'Invima', NULL, NULL, '2025-10-19 10:03:33', '2025-10-19 10:03:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `program_annexes`
--

CREATE TABLE `program_annexes` (
  `program_id` int(11) NOT NULL,
  `annex_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `program_annexes`
--

INSERT INTO `program_annexes` (`program_id`, `annex_id`) VALUES
(2, 5),
(3, 6),
(1, 4),
(1, 7),
(2, 7),
(1, 8),
(1, 2),
(1, 1),
(1, 3),
(2, 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('fq0Z4BOYPjiFyulkHAMmG2Ip4GaGrsWJkkLUUnJv', 8, '201.244.165.238', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiZzIwU3B0Q09VWW5FZnJlMkJYRW5obHJ3a0pWeVJjMTFVM3hPZDZNQiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjI5OiJodHRwczovL3NnY2Nhemgub3JnL2Rhc2hib2FyZCI7czo1OiJyb3V0ZSI7czo5OiJkYXNoYm9hcmQiO31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTo4O30=', 1764356521),
('KxERALQjCgJcnRvIYDx76BJ5myJ60xVzb2idyYgH', NULL, '2001:67c:6ec:2913:145:220:91:19', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:122.0) Gecko/20100101 Firefox/122.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiTlNxM0dTZ29QT2xRMXJySk1DdE1XWTZPTmxNWVB6TUw3bzlLVHRFYyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vc2djY2F6aC5vcmciO3M6NToicm91dGUiO3M6Mjc6ImdlbmVyYXRlZDo6dDVBUHN4TG5HWDZQTGNZRyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1764382213),
('j5XdCQaqSAgiDgQDKyHEEwNgcNyRLpUrp6o7Txvj', NULL, '2001:67c:6ec:2913:145:220:91:19', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:122.0) Gecko/20100101 Firefox/122.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiYzJGazdtQ3drWTRmVFN4MTBIcHZrYjAxM0R6WWpkV3dNT1ZHZ2tPTiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764382214),
('jyudMYtuQiPPPWZBdO8T3UyH0XsAfmFvVNJFWlMq', NULL, '64.227.189.237', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiTUlORVhGN3dRMGllUWdubEpaYnEyZEhmRnp6dGQ4VTdUQWtCbDM4MyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764406929),
('SwYKz2HeiVbCIUNR9kupiTFwEYZD4VgneE90bBhO', NULL, '54.39.6.15', 'Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiU014YVBKMWhqTkNZdEwzejRlRXdJMjhSbGRSeHc5NkhIZDZWOXNKSiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vc2djY2F6aC5vcmciO3M6NToicm91dGUiO3M6Mjc6ImdlbmVyYXRlZDo6dDVBUHN4TG5HWDZQTGNZRyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1764408074),
('awV79h1Outfk1bh4x1o3nCgqomxlKksnBeC4dxZ8', NULL, '2a02:4780:b:b::2', 'Go-http-client/2.0', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiTTR6YVJnZllJTGVoWUVhcm1CSlNmOXZhSk1BMThKZmlDUjVCVTEwUiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764409089),
('ukLikpStmPih8EvuGcUnBE8xej0qr3dP1CNOsgV0', NULL, '2a02:4780:b:b::2', 'Go-http-client/2.0', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiVGZKbWMxS2JIbGVSQXZORlBZWFR1S1BCc1NHOWduMjNaTDY2TDkyWCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764409089),
('e7Dp1xqZwS60ZwXk3iYLiRQifMYiNpC8Ns563soH', NULL, '54.213.237.203', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiQnd5SHRBV3JqY1lZVDBnb2Y1akxzZGljMVBZVXZsd1dKUkI3YTQyMCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vc2djY2F6aC5vcmciO3M6NToicm91dGUiO3M6Mjc6ImdlbmVyYXRlZDo6dDVBUHN4TG5HWDZQTGNZRyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1764414674),
('3myN5L3Vas0ACoecn2wMKLIFmh03QkQV8uiKXzGR', NULL, '54.213.237.203', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiaU9PTmVoREZCT09vU295Q0JZZzQ2aGJGTTc5dXA2am9MbDJ0QkZDdCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764414674),
('6DvTLdc5q5QHEbl54GzPLeXyhV0q1jjWfCo7f9qm', NULL, '2a01:4f8:171:1de8::2', 'serpstatbot/2.1 (advanced backlink tracking bot; https://serpstatbot.com/; abuse@serpstatbot.com)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiWGN6UHNUNWpSUGkzb3EzeTlaWWd4NU54TmtQZUdhYzFaUDU1Tm1PTiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vc2djY2F6aC5vcmciO3M6NToicm91dGUiO3M6Mjc6ImdlbmVyYXRlZDo6dDVBUHN4TG5HWDZQTGNZRyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1764447402),
('Fo6DeJybjOo7DN2pVyTu6gwpC8pS19k34AJsMF7I', NULL, '2a01:4f8:171:1de8::2', 'serpstatbot/2.1 (advanced backlink tracking bot; https://serpstatbot.com/; abuse@serpstatbot.com)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiUENGNExBNGpMTDNEN3JKRHB0M1M3MFhJeWNJZ3Jib0JxbVE0RVhUNCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764451315),
('sodD5zk3Lq8YcFLrsyQ7Mag1Xbh2bMSeKBiHDACN', NULL, '43.128.19.78', 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.88 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiczJzRFJ1MVpvYXJmWVozaVdwa2hZQWZ0NGE3QWF6d3k2QTZ1SFBBTSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vc2djY2F6aC5vcmciO3M6NToicm91dGUiO3M6Mjc6ImdlbmVyYXRlZDo6dDVBUHN4TG5HWDZQTGNZRyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1764452523),
('ayJaPXrPLgj92ISRqW5oTFJI4NDtNZK3PNtJXIJF', NULL, '43.128.19.78', 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.88 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiRGNZMnQ0UnVNNllaSnA5Z0VEcmZnR3FpbWM0YkJJOXVtbm9FamF4YSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764452524),
('j416iBewiBgjICUn6pdxqx39uEiz3CRn7Rl3iJsk', NULL, '18.204.208.127', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/91.0.4472.124', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiYnZRanRYeEppUzljd1ltcXZGZG5iSnNnc2t0NkpPT1FVenlNVUc2QSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764472859),
('tERy8JyupOhFd8tY1aGb9080HLYBQZJWCIsyWFVM', NULL, '161.115.234.221', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.51 Safari/537.36 Edg/99.0.1150.30', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiakFNM1ZUY3kwaHR6NUdQV2IxaXVTMnVBeE84UEtWRHZ0MHRiQmJnNyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764475253),
('DN3JxeAjh6ck5dgUXKhs48Yr570L3EOmfmW2eNvo', NULL, '91.84.99.6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiam5DR3hTT3J2UzB2dVFZcFBFb29ocWJHcVlENnpCb0M1WnllNGxTZCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764478333),
('lqsLzhcyXWycgfTLTi02SJtd0yZrdhoHO5UYN1u0', NULL, '52.167.144.169', 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm) Chrome/116.0.1938.76 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiWEpiVVBjU2hZNXhDTUx1OHFvRW9kbkU3NkJpY2lldUp6eW8xbnFKVyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764479183),
('sppD9dfRUB2OmVV70fCTH2S5mlyGFdNSd4becYWG', NULL, '2001:4ba0:cafe:b2c::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Safari/537.36 Edg/91.0.864.54', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiM0JzRmlPcHBUc25MTHpHaFd4UmdEczdLUXo2dld0V3hnc2F4d21PMSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vc2djY2F6aC5vcmciO3M6NToicm91dGUiO3M6Mjc6ImdlbmVyYXRlZDo6dDVBUHN4TG5HWDZQTGNZRyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1764480430),
('UqrYf5KDJviBT4vO74aNoZvKvrvc8q01QDkTxXK8', NULL, '2001:4ba0:cafe:b2c::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Safari/537.36 Edg/91.0.864.54', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoid3VGUWt3RDRFazJWTWp0bkxzdXZ2OUtoWEg4cU9JRmFvakpHSkR4OCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764480430),
('aWSUuFkxadc0esyuzuosQQmmJYh9rNj6HK3lcP2N', NULL, '47.88.90.156', 'Mozilla/5.0 (Linux; Android 11; M2004J15SC) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.5060.114 Mobile Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiNEljSW9oaTN3ZGZFQk5wQk42N01lWm9GczRWcFRUYVNWd01GVTdpMyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjM6Imh0dHBzOi8vd3d3LnNnY2Nhemgub3JnIjtzOjU6InJvdXRlIjtzOjI3OiJnZW5lcmF0ZWQ6OnQ1QVBzeExuR1g2UExjWUciO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764485493),
('GbabZShgaf60Q2SZgCeFQ1AE2SOYKKPH4HNr5pWG', NULL, '47.88.90.156', 'Mozilla/5.0 (Linux; Android 11; M2004J15SC) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.5060.114 Mobile Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiQ3NiZDk4d3NnT1JUekhZbzhXTDR0SHBMT2M1d3ZqbzZDbzVNSThvRiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vd3d3LnNnY2Nhemgub3JnL2xvZ2luIjtzOjU6InJvdXRlIjtzOjU6ImxvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764485494),
('RlQ73w33N2bLkf9foQwFWiVJhAt3kbYnUvCD7QSV', NULL, '47.89.193.162', 'Mozilla/5.0 (Linux; Android 11; M2004J15SC) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.5060.114 Mobile Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiUjE4bDBlTGhQNEEwT2VaMFlRQjk1UGVyZDB5bUV5M0t1Q2RHR08wOCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vc2djY2F6aC5vcmciO3M6NToicm91dGUiO3M6Mjc6ImdlbmVyYXRlZDo6dDVBUHN4TG5HWDZQTGNZRyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1764485500),
('R8ZuBcZkHzxkxdf2N6ybQUKiiAFjCVP9OO14jV8m', NULL, '47.89.193.162', 'Mozilla/5.0 (Linux; Android 11; M2004J15SC) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.5060.114 Mobile Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiMDdRQVp1UFVqYnIxTmlydENzV0c5T1M1c2x6V3YzRTVSWmZheDF4WiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764485500),
('vAC0rw7POgt6Ky0WqCqqwtnJDJJa7FaO6Zutc70G', NULL, '47.88.94.161', 'Mozilla/5.0 (Linux; Android 11; M2004J15SC) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.5060.114 Mobile Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoia0ljWkpOZHh5MVVibWxQeVlBRGF6WUFmMFpUaWt0N1lDMFZrMkZiSiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjM6Imh0dHBzOi8vd3d3LnNnY2Nhemgub3JnIjtzOjU6InJvdXRlIjtzOjI3OiJnZW5lcmF0ZWQ6OnQ1QVBzeExuR1g2UExjWUciO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764485513),
('QGVaF5HTCvmER9eAs4eEiMvGW0eTzgjWMbraNZH7', NULL, '47.88.87.97', 'Mozilla/5.0 (Linux; Android 11; M2004J15SC) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.5060.114 Mobile Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiQ0w3Mnh5QXNZMWc5TlVNWnk1Y1VYYm1rVFphS21iREE2YlBKNWhsQyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vc2djY2F6aC5vcmciO3M6NToicm91dGUiO3M6Mjc6ImdlbmVyYXRlZDo6dDVBUHN4TG5HWDZQTGNZRyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1764485519),
('aHd2mbd153LbLFBzea54R8J7lQrkSxB8QxyoQ1xF', NULL, '47.88.87.97', 'Mozilla/5.0 (Linux; Android 11; M2004J15SC) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.5060.114 Mobile Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiQTJSbUFnSGtzclA2OTdTR0h5NWVMSVZ4Y2lFNVlmeG8xc0VjTlhTRiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764485519),
('WTZP0BzmkpQcq6SmOQxlLFtjWUrF3iZHJEsyf8Id', NULL, '3.136.86.139', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/91.0.4472.124', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoibmZUbmtFNGRxOU90MmxoYmIwVWdXVWxmdElOY2c2TTBHSWp1c1plZiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764496184),
('2BrD2IeDFpYfhE4TPWZUgzHHT1eYbJlGh4sRBhvE', NULL, '2a02:4780:b:b::2', 'Go-http-client/2.0', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiZ2wxODA5Z2lDWVNNakFOeWhXTXRubjU0emxiWmhRTzdKMjc3ZGhyRiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764496867),
('aeHWK0ge9s48JdwJ1mRANv4Txpebl64Wtqq1ErMz', NULL, '2a02:4780:b:b::2', 'Go-http-client/2.0', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoidGdiNkl5TDJsMXlMdGFWWmFsbmtzdkdONU16NmNOQ0U1T29iamszdSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764496867),
('v6SjhSf1sXjMoCQKuzMeww8vTXLBtkunnDIgySHy', NULL, '180.153.236.6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0; 360Spider', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiaHpUbU1LVkNKMmFwbTZXQU5yNENaWXZIbFdFcTBmRDJSd0NxR0tueiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764501389),
('bpexmxAtxzdP87Lx2NfNW52L6NTFUDa4Vfk1gSYc', NULL, '2001:4ca0:108:42::7', 'quic-go-HTTP/3', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiYzRLVG82Y0VkbWt0UkhmblJGUWo2c0RRWUUzelRBOGZGdDdXMVZxcSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764509096),
('EkQKVgOnRrVXlyx6Jp06TAJfbzwW5ETlmsUdRunj', NULL, '180.153.236.173', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0; 360Spider', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoieHNoM3JvdVA4SUcwZ1pjU3c3d0x6aklmVHJsNWRkMnl1R3RSSm03WCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vd3d3LnNnY2Nhemgub3JnL2xvZ2luIjtzOjU6InJvdXRlIjtzOjU6ImxvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764528025),
('9v1FmZtgv5zn8pKlrLwbW5TKfojg7wDx6DRvgV7e', NULL, '91.227.68.187', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.43 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36 OPR/115.0.0.0', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiUzJtMVNFT0Z0cXVKVE5aQlBrTngxNmpBOWdRTzNrZDlIN2l3UUVoMiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764530960),
('ez4VQSONWtbRySb59bTBA2cDEV7FCutghmNwOjGO', NULL, '91.227.68.187', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.43 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36 OPR/115.0.0.0', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiN1ZXRG9VNWE3OVJ0OWpnYzVMOXdvaEJ6YVVFMklPd3Q5UUJkWVhkSyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764530960),
('NtbivBRtDix0jmmTb4kzLW6bUdmGhm6EimtnVr4k', NULL, '2001:4ca0:108:42::7', 'quic-go-HTTP/3', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoidTRwaDVFU0FHTGlWR3hOQlFGbzA5bllwUFdKbk5SSFI3MnA0RTJyciI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764533101),
('RTQzRFj0BhgD2LIEiny4xuJMHEUjJHtXA8xE7lWG', NULL, '44.248.224.137', 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:46.0) Gecko/20100101 Firefox/46.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiek5JTWN4ZHg1amFmS0lkbm1aUHBUUUpYZlhQTnJHSXNiVkZCUThXbiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764549799),
('vThI6yKpfdkLgCfZ5Jnk5T8EkTnDQC8ClBPw21ea', NULL, '15.235.27.145', 'Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiOGc1bWRCWFVpaWJKREFlOEN5NWtyMXAxSjhvWXptclFRbm1HQ0g0NyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764557074),
('Jc157yES6qlOwVE2aVjdBdJjaugF7V1RSbyChfUK', NULL, '23.81.60.4', '\"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36\"', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiUmtsczVXVTl2ZWZHQlV5dmJSS3FJeWdNMnZWeHN2TVpxT0tadzlKbiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vd3d3LnNnY2Nhemgub3JnL2xvZ2luIjtzOjU6InJvdXRlIjtzOjU6ImxvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764559406),
('wPp5hlOdo7yJOB1EBYdEkkEKndFx4UHqdL1ALl3Y', NULL, '2a02:4780:b:b::2', 'Go-http-client/2.0', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiMkkyaGFxMkx2cjFBYkRNQ2N3Y1lVek5rNGd3WTVWa2dFWll6elY0QSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764568349),
('2bOOTBlLynL6BBzzXx5LMzi7KQ8lEtHY11QXxGLB', NULL, '2a02:4780:b:b::2', 'Go-http-client/2.0', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoidGNJN1VkbTBQQ0xDUTFPcGxpOHlMMmU5YmZvNGFZQkZUbmtoZmh0dyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764568350),
('7HW6aq7BBMSmu2no2gIvl91RYhl8osJazvaQEMyy', NULL, '159.203.7.195', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiRWVURXRxS1V2dGpMaklmaWhiUmlHdVJIb1RRWXBEVlZ6cVZ3TVVzOCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764578467),
('Vr4WNj9teR2r77uXhUClCPeRdnwDYifTkCux6lZb', NULL, '93.158.91.13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoieUY4emVMQlhFQlkwN0J6eVVZc0hTUk1jOVUwVFNWaTR0Z0VFek5UZiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vc2djY2F6aC5vcmciO3M6NToicm91dGUiO3M6Mjc6ImdlbmVyYXRlZDo6dDVBUHN4TG5HWDZQTGNZRyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1764586019),
('v1ihAAcPJzKRHt5NGXhlawHPpQngIxb1sI9dMB6n', NULL, '93.158.91.13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiZmV2RmZuejlRYzhWOW1sZXZOUGU2ZFlnNXRFN1VEeVlwaUkyT0JNaCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764586021),
('o4vHRN21xoV8WvmRvFlwSq5vISHBy16hUNn1do7t', NULL, '54.39.89.158', 'Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiaTROOGJOc21sZEV6d204bE1YOUJmRHJSdnYySkU2V1o2eVpCdzhoayI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vd3d3LnNnY2Nhemgub3JnL2xvZ2luIjtzOjU6InJvdXRlIjtzOjU6ImxvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764586994),
('N6JL6D8QGu8583QdmUJ01VKCNAUWXK5jhLfPzhgj', NULL, '191.156.125.75', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.1 Mobile/15E148 Safari/604.1', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoib09ieTJCWlY0T2o5R2tFVmxUVGtSMlpUMlpFSlhtT1kxMGFhUjFNNSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764599256),
('HniqjqdqZ5LShNvX3YCbgogGGpLtHPAAuxVvMqzK', NULL, '201.244.121.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoidFhsQnVZRXk2dWxQQzJlQUJuNzVabGJ1Y3BGYmRMVU1RbVh5blBNYyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764599449),
('lP8n0K87qXGfILeccdauSieIwpqNCkc3uuhK35zo', NULL, '200.29.113.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoicFdRSmhJUWZPakNLM1BKcHJPQ1NZWGZ6T0pjU0FWbmRWbTFjY3c2MyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764599328),
('qXycYLrF0Aq2Z2RILgZRsd4dMausAYgfNtLyLatI', NULL, '15.235.96.120', 'Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiTVV3c1JXSkhBMnVRczNPTzB2TmxLVzlodll2SVhkOUQyR2hUQTZ6NSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzI6Imh0dHBzOi8vc2djY2F6aC5vcmcvcHVibGljL2xvZ2luIjtzOjU6InJvdXRlIjtzOjU6ImxvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764610098),
('dp5KmwKaDIVRYHe74G8SchRNoY26oXfWIMk4oy3l', NULL, '94.103.93.19', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiWHMyeXZNcWtMWGxIZWdjcFVPazBZa3FNekdQSnBmQnV2azg2MzdNMyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764635088),
('ChNvgYiMbQI64bbK5bAB1YnizhWjK0r22CtTtYwy', NULL, '15.235.96.225', 'Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiSFFidDlTcmhSanplQ0R5Q1Q2ZkJMUnZYT1NoNGVtWG5YVGxlSm5FOCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjg6Imh0dHBzOi8vc2djY2F6aC5vcmcvcmVnaXN0ZXIiO3M6NToicm91dGUiO3M6ODoicmVnaXN0ZXIiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764636832),
('Pe9EwLxpoJxlbK0rzDxeew1bv4rLOztYrijq2esU', NULL, '15.235.27.187', 'Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiRkVHUG43ZWJHdkR3N1laU3hab29FU2hzRHBvaGVMMXdiSmZBakdCdSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vc2djY2F6aC5vcmciO3M6NToicm91dGUiO3M6Mjc6ImdlbmVyYXRlZDo6dDVBUHN4TG5HWDZQTGNZRyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1764647602),
('kgfJryHHkyT1lIK3HVbTo9ulU70Ku8S5YvCaiuar', NULL, '2a02:4780:b:b::2', 'Go-http-client/2.0', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoic0dsM1pkcXZTanVxd1RNOE1JVjdxUHV3WjRrV3U3VWY5STBHcHZrMyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764648899),
('3OOM9npjPWfnMKFLZF98YUS0si1BKCXCQNOp0Wca', NULL, '2a02:4780:b:b::2', 'Go-http-client/2.0', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiU0ZlVWppWnh4MFNBUmxKMXhVeTdCQ21lbEFwOEZHb0NpNFd3UVcxNSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764648899),
('J1Xh4wry88Ptl2GS7xekoOiU5lf9VrwQN8885iqq', NULL, '65.108.235.95', 'GuzzleHttp/7', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiN1NaMEc0VkZrTWhCcDVPSFlkdThyc2l2bkYzS1lHV3F5a1Q1eHBXSCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764656152),
('Ycl933tmdfzcnN49X7GyxIDvoTGhVvuKd5Csik2N', NULL, '65.108.235.95', 'GuzzleHttp/7', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiUll1YzVySVlKcFpEaEdkdzhLRVRxN2NsYUpGSUkwS3pXcFBydndaVyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764656152),
('oEWVMMPG68ibnz81AAuSppRJS7PySoSSvLN5SXfz', NULL, '65.108.235.95', 'GuzzleHttp/7', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoib2F5ZVgyM29ZZmJzUk1iVlc4U2dET2lqbG1aNjdWSml1VktZcHVUaCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764656153),
('0MsmyIDfbp5qYSiDpwaAxkhVONgtHm7jV0G4Wcq7', NULL, '65.108.235.95', 'GuzzleHttp/7', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiOEZzQXE2bUgwWTNWWlVlcHV6Z29VckxGNkU1dkxiWUZMaVZiVktQbCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764656153),
('NOCYJKpMZmhEHUfekK1vb8GtUo1we6gxtfCLBcu4', NULL, '198.46.154.21', 'Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B367 Safari/531.21.10', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoidVpIUERPREFvWDlxVk55V3BXeFI2VnIyNFR4ZnRSYXF1SWlCdWFSQyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vc2djY2F6aC5vcmciO3M6NToicm91dGUiO3M6Mjc6ImdlbmVyYXRlZDo6dDVBUHN4TG5HWDZQTGNZRyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1764656351),
('Dgy701RsCIEhqS5si3jxL3KLxEQubZgo34pb6QRX', NULL, '198.46.154.21', 'Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B367 Safari/531.21.10', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoibFVJTk9pVWsyWHpWQkYxbTFlalF1NGZEYzAxMGlZYThyWmhmcDRuNyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764656351),
('PX4rovsNcJvrKSXB55sDxKL11tfyGPRrBfzFrpFc', NULL, '89.110.115.121', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiT3ZZUDlmN1pSOU03WjlnSGZDOW5SdzZVOXhCSnk3U3Z4UEMzTE0yNSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764666622),
('b8AkmzkvMeWakfmLVDZxfen46E7gPwa2DjVXGeTr', NULL, '51.161.65.207', 'Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiQTZZOHVzUk1ycVdVN01IZ0ZrRk5TM01kdUFncTRzeGo2OFRHQXFQaSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzY6Imh0dHBzOi8vd3d3LnNnY2Nhemgub3JnL3B1YmxpYy9sb2dpbiI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1764679420),
('Un5lTu9bOxziYBfFgbU6FZJK259iwMEFmDshwAIW', NULL, '2a02:4780:b:b::2', 'Go-http-client/2.0', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiUW1xRXVWeGphMFBrSXdRbjZPV3dSemFQQ0poYzh0QXRCVkhWdHdnUiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764735489),
('lxR05ex2Lr6zG7Mmwu9qRfbBPtQEZ8H7qX0B56TI', NULL, '2a02:4780:b:b::2', 'Go-http-client/2.0', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiYlFSdzU1NHdKQjVjT1U4czN2ZzZrOTJPelFmT2lqQXNWVllTRnlZWCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764735489),
('UbWLmeauoUjPlJsioYACQ9YLL3HtruUC6IEdqk2E', NULL, '3.35.24.44', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/91.0.4472.124', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiVEsyV2NsMjl0bk53RVlvaGJoOVpjYWVCT2VNWUZjTlk1bUQwM3puQyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764743576),
('BOICgLINB1qAdPNWGtnThJrzxU1vJkYyInWCWYYd', NULL, '143.198.47.109', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoicmJkOVlpT2VhMkFBeXdPanQ2Tm5nQUtGMjZMeGxzMTBPcUo1WG5LaSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764744693),
('wGhiFuz8dYRpmW7wh1kEYlcaXo02JyCfNboqBJwu', NULL, '2a04:5200:fff6::88c', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36 Edg/122.0.0.0', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiNGwzUXRDc2dHa2ROaDJHcFNGMDdJanF4UU14MjRJRTB0cnhtQk9UYiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764753758),
('Porwy25d6uxOELpklZW2vKuQIZcj7DixVopnH1je', NULL, '2a04:5200:fff6::88c', 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36 Edg/128.0.0.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiWkxOeU1BOWFId3JWaHh4UDJ6YnhoWDFyN1B1YTF2WFRCTmxZdk5tViI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764753759),
('dAQL4chpwRIPf4vRBqxShkjwAiZVqPoSOEKaZ3Px', NULL, '54.92.144.105', 'Opera/9.64 (X11; Linux i686; U; Linux Mint; nb) Presto/2.1.1', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiQUlZcmhWZng1VUVLNnpvUnZGV2VrODFGc3BMdVRPYUZnUW81WjFTVyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjM6Imh0dHBzOi8vd3d3LnNnY2Nhemgub3JnIjtzOjU6InJvdXRlIjtzOjI3OiJnZW5lcmF0ZWQ6OnQ1QVBzeExuR1g2UExjWUciO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764753971),
('aK9GXmjgoOz2Yv3mBPWj8vTYos0K4cU5888ZoRyq', NULL, '54.92.144.105', 'Opera/9.64 (X11; Linux i686; U; Linux Mint; nb) Presto/2.1.1', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiTVlJeFhIQmxVR2dFMWM2eE5MbmgydE9Xa3VhTjIwUHh3WXhqRGtqNCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vd3d3LnNnY2Nhemgub3JnL2xvZ2luIjtzOjU6InJvdXRlIjtzOjU6ImxvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764753971),
('msSWxHIlfactIqlLAtu9bMxFAcYbsLSVk2CNDeYP', NULL, '91.227.68.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:136.0) Gecko/20100101 Firefox/136.0', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiek5oc1luRHFMUFJSenk0d2ZtQ1FWZUZNRXVjaXlxbTNOZHprOEJvdSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764754761),
('G5cocyEtt9W81jc3WCK7Q1kDuaYpf6jzZDj9XjEV', NULL, '91.227.68.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36 Edg/124.0.0.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiTXo1U000bkd3QVpNMHgwVTJFSEhXd3NDZHp2VUYzSE5JTjFOWmZXRSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764754762),
('f832pW7LtvJVc79RDchCtmMpvOKlOqiPUBdlYfFh', NULL, '142.44.228.83', 'Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiUDVkdE1jTGpGTzVGZm41elU2cmVwUnNPd3dPYmNkUEthblh4cjl6SiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764761935),
('xXtBh1rJ1NqTs0ciZboryPhce0AhiNpGB5wHA7dt', NULL, '128.140.119.19', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiM3BMYTlGU1dGdkdVU2FVT1U4d1BFeGVWQ21OTjBsSUd6YjdzR3dXRCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764763162),
('9D9QSGrj2svMEzs7q7BtxwFDGANs8mkWi5DuydfL', NULL, '34.26.237.206', 'Mozilla/5.0 (compatible; CMS-Checker/1.0; +https://example.com)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiWlROV2dqZk1XUnkzR1ZLQ0ZseFNhVnZhOVFlMDlRRzhvd2k1SUdQcyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vc2djY2F6aC5vcmciO3M6NToicm91dGUiO3M6Mjc6ImdlbmVyYXRlZDo6dDVBUHN4TG5HWDZQTGNZRyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1764770824),
('0UU5gbvtlNLYzrPVpOshqyPKUlJflGMMTttL4SHT', NULL, '34.26.237.206', 'Mozilla/5.0 (compatible; CMS-Checker/1.0; +https://example.com)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiaVJaVU5PS090YWp1VlZDSUpxYW8yQzBaNHVFSkFlanNsV29oOFozNyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764770824),
('eCHVC2juAZR3BRqUZhkksdsweYqZ7LKCgUMQgPg9', NULL, '54.92.144.105', 'SonyEricssonW850i/R1ED Browser/NetFront/3.3 Profile/MIDP-2.0 Configuration/CLDC-1.1', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoicDVLVzV2dzRYVFpWUXdJVjZWUGRYVXY3eUJ1VmZQNTNYSTludVNLRiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vc2djY2F6aC5vcmciO3M6NToicm91dGUiO3M6Mjc6ImdlbmVyYXRlZDo6dDVBUHN4TG5HWDZQTGNZRyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1764773339),
('tNOLOb3xbOn2biV8HWTe6jS02BSEXGc0r7nd3eUK', NULL, '54.92.144.105', 'SonyEricssonW850i/R1ED Browser/NetFront/3.3 Profile/MIDP-2.0 Configuration/CLDC-1.1', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiT2xadllGQnFaaUZCMzZwaGlZSzFMQTZZWXZYWUlZd3Fka2h2V1VXUiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764773339),
('WKyl9aJIftbteKKM5nQ9rLi5jSpwD5EV5h8Cpn1U', NULL, '149.56.160.241', 'Mozilla/5.0 (compatible; Dataprovider.com)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoic01nM01Na2tRcWhoRU9BNVJDQmR4eFlMSUVtbHZkd2NqdThqNEY2UyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjM6Imh0dHBzOi8vd3d3LnNnY2Nhemgub3JnIjtzOjU6InJvdXRlIjtzOjI3OiJnZW5lcmF0ZWQ6OnQ1QVBzeExuR1g2UExjWUciO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764778129),
('1vjaR7bCPGgON5bCbpZBgvA0fcpyFuN1xXq464WY', NULL, '149.56.160.241', 'Mozilla/5.0 (compatible; Dataprovider.com)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiZk5ucUpUWGRCNE50ZHpwWUQzcFFsSU1QY3dUZW9mSlM4S0puZ0JLdyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vd3d3LnNnY2Nhemgub3JnL2xvZ2luIjtzOjU6InJvdXRlIjtzOjU6ImxvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764778129),
('gZFQQeF951i2FdW7P2p72NhYesyOatb84b0Xq86Z', NULL, '149.56.160.241', 'Mozilla/5.0 (compatible; Dataprovider.com)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiSWlSRUJBUzlteE9PcmpVRFM5OE1YVGNTRWRnT3dFU1VPUzhsMUdpMSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vd3d3LnNnY2Nhemgub3JnL2xvZ2luIjtzOjU6InJvdXRlIjtzOjU6ImxvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764778130),
('hhgWliEhVQ4AWVK7TADWnivgbfR03kEG78l86X4j', NULL, '149.56.160.241', 'Mozilla/5.0 (Linux; Android 10; SM-G981B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.162 Mobile Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiOVNpRE9PM2cyeHA0Rlk3Qk95eVN0UzNIMVQ0VExNZGtYMnZvSFlpcSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vd3d3LnNnY2Nhemgub3JnL2xvZ2luIjtzOjU6InJvdXRlIjtzOjU6ImxvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764778132),
('SqY7uG0eA32ZmCRO4fACIvJgZfeoEP3M5S04HP4O', NULL, '149.56.160.250', 'Mozilla/5.0 (compatible; Dataprovider.com)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoibUdoS29LQnQ5a1dyZWl0cWw0eU1TMHQ0SmVxNWlmZ3VCd3p2NWhxYSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vd3d3LnNnY2Nhemgub3JnL2xvZ2luIjtzOjU6InJvdXRlIjtzOjU6ImxvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764778144),
('LS6H1wL2MgrhAv6VlHtNNnUrxUuOyJhnYxzklX5f', NULL, '149.56.160.250', 'Mozilla/5.0 (compatible; Dataprovider.com)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoic2Vsb1ZyVnJxZUJyc2tqU254d0h2RnhxaEVabmNuSnFUTkx5UzFPRyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjM6Imh0dHBzOi8vd3d3LnNnY2Nhemgub3JnIjtzOjU6InJvdXRlIjtzOjI3OiJnZW5lcmF0ZWQ6OnQ1QVBzeExuR1g2UExjWUciO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764778146),
('CElD71MkxGTgJSpMH7TPaliWKaRh0NKf9pSYhgcI', NULL, '149.56.160.250', 'Mozilla/5.0 (compatible; Dataprovider.com)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiR2trdXpyN1NlcVhZaDZxak1rOWZscG8zQVA4aXFoS004UDZmWm01QyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vd3d3LnNnY2Nhemgub3JnL2xvZ2luIjtzOjU6InJvdXRlIjtzOjU6ImxvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764778147),
('QnDvZys787qbnNMhrYhuV46IfwBQbkXBfSeiIg3L', NULL, '35.194.74.5', 'Mozilla/5.0 (compatible; CMS-Checker/1.0; +https://example.com)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiZjdTSVNUc3A1NXJkYlA0dVJ0OWVQVXNneGZxNzh6YU5ZV0E0cDdKayI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vc2djY2F6aC5vcmciO3M6NToicm91dGUiO3M6Mjc6ImdlbmVyYXRlZDo6dDVBUHN4TG5HWDZQTGNZRyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1764783078),
('VZCR7kCQWUnNe8lAJpIbJzxDtgPSLCoIlV1hG5r8', NULL, '35.194.74.5', 'Mozilla/5.0 (compatible; CMS-Checker/1.0; +https://example.com)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiYnc4a3FBSlQ5a0ZEZERKMk50TXNzbnZuT1lTYURPV3Z0VmR5ckRRdSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764783078),
('NBvmMf31oSI3WoS1k54qbMvqe37MAyK1QvXH9FXc', NULL, '35.202.162.77', 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:77.0; Mandajanganpergi) Gecko/20190101 Firefox/77.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiWGkzVzlOM2dyZU03eGZ5UkVrMFVhN2p6TEZ5ODFKTHJrNzQ5SEZncyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vc2djY2F6aC5vcmciO3M6NToicm91dGUiO3M6Mjc6ImdlbmVyYXRlZDo6dDVBUHN4TG5HWDZQTGNZRyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1764785357),
('CKkmwweRYpslPv6pud1UVpzxUvSy8hzMtwGqZupA', NULL, '35.202.162.77', 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:77.0; Mandajanganpergi) Gecko/20190101 Firefox/77.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiazh0cDRRVHAyOFpYQmdXdlY3TkZqRFUzcm1HWmVYN1p5a09VamlaSCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764785357),
('fdzLiEJT0oLA5DoJQjK0p3SmDRHIfT3Llwrgx05n', NULL, '35.193.238.146', 'Mozilla/5.0 (compatible; CMS-Checker/1.0; +https://example.com)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiaFp2TE1Pa05ESlN5bFpTVjhoN2VaN0t3c2FteHhhS1dxMDliM0JOciI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjM6Imh0dHBzOi8vd3d3LnNnY2Nhemgub3JnIjtzOjU6InJvdXRlIjtzOjI3OiJnZW5lcmF0ZWQ6OnQ1QVBzeExuR1g2UExjWUciO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764785913),
('9FgUu5UPENGzw6CqPmLqHdUMBxVTazcqpLiJ4qjo', NULL, '35.193.238.146', 'Mozilla/5.0 (compatible; CMS-Checker/1.0; +https://example.com)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiTVhnWGhOQkt1d3pXYTBWVlVKT1A1WDF6SkdjeGlGOTM2eVhNNExoSCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vd3d3LnNnY2Nhemgub3JnL2xvZ2luIjtzOjU6InJvdXRlIjtzOjU6ImxvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764785913),
('glDJPLfp95J1WNE4cpCxUmoNA6UtxycrX01c2Ym5', NULL, '2a03:2880:f80e:13::', 'meta-externalagent/1.1 (+https://developers.facebook.com/docs/sharing/webmasters/crawler)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiN0ZTU3U1R2FaandwdVV6R0Uzb2FtNHB3UWZkUDFpV3pFcTZpZ2VQMyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjM6Imh0dHBzOi8vd3d3LnNnY2Nhemgub3JnIjtzOjU6InJvdXRlIjtzOjI3OiJnZW5lcmF0ZWQ6OnQ1QVBzeExuR1g2UExjWUciO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764799685),
('i2pjTmGhVZ5bwrS0IDzvyRSvnWT9J7HaTEtCWEhO', NULL, '2a03:2880:f80e:50::', 'meta-externalagent/1.1 (+https://developers.facebook.com/docs/sharing/webmasters/crawler)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiNURTTE9RSlBEY3lrbE5aMzk5dDVaOUZ6YkZpVmVPTFBmT1dtd0hFVyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vc2djY2F6aC5vcmciO3M6NToicm91dGUiO3M6Mjc6ImdlbmVyYXRlZDo6dDVBUHN4TG5HWDZQTGNZRyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1764805275),
('jzysehMh7pxtbPDCk7u3sAIXSdhgspDjbsgKwoNo', NULL, '2a02:4780:b:b::2', 'Go-http-client/2.0', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiakhpNmUxTWVmU0ZNeEs2eTZ4ZHNCeDlTcHVmRk5kYVFsZUkzQ3JGTCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764830313),
('L2krtrl3j3hyPWuxfnG3yVGHssexOgIsv1XCo8uN', NULL, '2a02:4780:b:b::2', 'Go-http-client/2.0', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoicnEyMFBSSU9NOFpLWGRkc1praTRNNVh5bEZYdkFNczVnWjJGMDNXYiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764830313),
('kbPwEx62CdnbSKPAspD1IAQrXHDpVduncS7H0DjJ', NULL, '180.153.236.246', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0; 360Spider', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiV0Vsck42QUdwN0g5djR0TzNXM1QxTWkzdkNzdG9aNWZqbVNKUUg1UiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjM6Imh0dHBzOi8vd3d3LnNnY2Nhemgub3JnIjtzOjU6InJvdXRlIjtzOjI3OiJnZW5lcmF0ZWQ6OnQ1QVBzeExuR1g2UExjWUciO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764842923),
('3QMakGIVjniVdyVXCCCtFR3d1UZAqXOoOo6htlm3', NULL, '180.153.236.72', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0; 360Spider', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiR21oUXNadjQ4OW4xbEpDUTlnZktiRk5mNmpNTzR3QnM1bXpoRGhVdSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764845811),
('BpbkhdRf2z7GWlZ0wAfDH1RBHm6C5R29Zscx3J4p', NULL, '192.36.109.86', 'Mozilla/5.0 (Linux; Android 14; SM-S901B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.6099.280 Mobile Safari/537.36 OPR/80.4.4244.7786', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiNmJkd2d6Z003M2tUNjdZbVpiVHJmVGFBc1lhOHFxSndTWlJ4NFVFWSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vc2djY2F6aC5vcmciO3M6NToicm91dGUiO3M6Mjc6ImdlbmVyYXRlZDo6dDVBUHN4TG5HWDZQTGNZRyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1764851609),
('sQwQQXVflEjKMIyqlptukTKdIvZpHU37Tq5BY2eV', NULL, '192.36.109.73', 'Mozilla/5.0 (Linux; Android 14; SM-S901B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.6099.280 Mobile Safari/537.36 OPR/80.4.4244.7786', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiUzZraFVzMkMyQ1BhNHNhSnE3Yk1VM1M4TmlNREJRMU9VekFSS2hMNSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764851610),
('92uAhQ6CoUqAF0OyP0YLJtzQY8rlRICZuh3UFGga', 1, '181.53.13.118', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiMWhzaHBZNTYxdGhKTVZBT1dyNkFyeXUwZkZEalU2dmc4aEtJRUR1dCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTt9', 1764853409),
('s3tG2jJp9dot3qWBF6qzzsFVT2jdFjJMfGDa8MRc', NULL, '109.248.12.138', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36/Nutch-1.21-SNAPSHOT', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiN0p6RjBUbEJUUVhnaENOaEd3R3d6QTU0OFI4akl0cXZNcnhBUFpoNyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764855171),
('acZz2TWheSQuYmhdCBgV4XXtvdQfckFadsMZhlYA', NULL, '181.53.13.118', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiTnhTRFBMNGo1NEx1emZLaVRVWG1zN21rZktOQW10eW1YZFRkVlFsUCI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czoyOToiaHR0cHM6Ly9zZ2NjYXpoLm9yZy9kYXNoYm9hcmQiO31zOjk6Il9wcmV2aW91cyI7YToyOntzOjM6InVybCI7czoyOToiaHR0cHM6Ly9zZ2NjYXpoLm9yZy9kYXNoYm9hcmQiO3M6NToicm91dGUiO3M6OToiZGFzaGJvYXJkIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764857482),
('Owqy0hxuVhV9nAmNfNik88dloj6adTRIHpzKSHtR', NULL, '181.53.13.118', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiWGZpUkV5OTlSelhyZHpOaW5YMGZLQTJZT1FzenNScWxGVmhpcG1FYSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764857482),
('KF01PJHsGxufuFIB7LkSxgq8XhkFQu3Z6wDvShgr', NULL, '181.53.13.118', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiZmFXOVdjWEZqQVJlN0dGQVFGbVdDMkdDQk00Yk9HdHlBQXF3RkdBUyI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozNDoiaHR0cHM6Ly9zZ2NjYXpoLm9yZy9saXN0YS11c3VhcmlvcyI7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjM0OiJodHRwczovL3NnY2Nhemgub3JnL2xpc3RhLXVzdWFyaW9zIjtzOjU6InJvdXRlIjtzOjEwOiJ1c2Vycy5saXN0Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764857542),
('yI0ToRTwfoknCIMj0UjVeuUNN8raDh66hXbF4cq6', NULL, '181.53.13.118', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiNkl5TWFBUXFBWWRkRTB3Q1M2UURCa3h5eG5BUzFpdVdub2dCSHJlRSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764857542),
('D0LUBWmDYrRo4Vfc4Xev9576jAai8TWvBolqfqOn', NULL, '181.53.13.118', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoidkxydFY5Q25zVWRmZk5PQ0ZMajBqRHYxQnF1TVBzc0ZmZE84M2NRMCI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czoyOToiaHR0cHM6Ly9zZ2NjYXpoLm9yZy9wcm9ncmFtYXMiO31zOjk6Il9wcmV2aW91cyI7YToyOntzOjM6InVybCI7czoyOToiaHR0cHM6Ly9zZ2NjYXpoLm9yZy9wcm9ncmFtYXMiO3M6NToicm91dGUiO3M6MTQ6InByb2dyYW1zLmluZGV4Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1764857603),
('A8bD8fknyEfwmTe148O5Sng0V8ELKqjdD6zD4um6', NULL, '181.53.13.118', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiUTFJQkt1Y2ZwYjYzd3c5cEx4VDhBbUloNEpzWW9NbXVYM0hneUNiUSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vc2djY2F6aC5vcmcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1764857603),
('geTQyIG62AH06jCNwvY4kHnk0MkPDf90Pect9hie', NULL, '51.222.168.79', 'Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiQms3SGg5WUVHRFRxcXZKZms4aHRRQkFNbTB4YzIwS0lTNHM2VGF0dyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MTk6Imh0dHBzOi8vc2djY2F6aC5vcmciO3M6NToicm91dGUiO3M6Mjc6ImdlbmVyYXRlZDo6dDVBUHN4TG5HWDZQTGNZRyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1764861133);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `rol` enum('super-admin','admin','analista','usuario') DEFAULT NULL,
  `habilitado` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `rol`, `habilitado`, `created_at`, `updated_at`) VALUES
(1, 'Admin General', 'admin@cazh.com', '$2y$12$IB1cFpicRZxfnj/G9COkWuuC1/O20AotB7vhqRNnmklzD6A8rw2Zm', 'admin', 1, '2025-10-19 10:03:33', '2025-12-04 13:03:16'),
(2, 'Analista Senior', 'analista1@cazh.com', '$2y$12$RqPoCxwT/prgYC2VTLuyRuD/8xn2c4cOwYfRckdczQsPvmz03IxAW', 'analista', 1, '2025-10-19 10:03:33', '2025-11-10 01:38:27'),
(3, 'Analista Junior', 'analista2@cazh.com', '$2y$12$RqPoCxwT/prgYC2VTLuyRuD/8xn2c4cOwYfRckdczQsPvmz03IxAW', 'analista', 1, '2025-10-19 10:03:33', '2025-11-10 01:38:24'),
(4, 'Juan Pérez (Frescos)', 'juan.perez@frescos.com', '$2y$12$RqPoCxwT/prgYC2VTLuyRuD/8xn2c4cOwYfRckdczQsPvmz03IxAW', 'usuario', 1, '2025-10-19 10:03:33', '2025-11-10 01:38:33'),
(5, 'Maria Lopez (Lácteos)', 'maria.lopez@lacteosdelcampo.com', '$2y$12$RqPoCxwT/prgYC2VTLuyRuD/8xn2c4cOwYfRckdczQsPvmz03IxAW', 'usuario', 1, '2025-10-19 10:03:33', '2025-11-10 01:38:37'),
(6, 'David Ríos (Carnes)', 'david.rios@carnesdelsur.co', '$2y$12$RqPoCxwT/prgYC2VTLuyRuD/8xn2c4cOwYfRckdczQsPvmz03IxAW', 'usuario', 1, '2025-10-19 10:03:33', '2025-11-10 07:26:47'),
(8, 'daniel', 'test@gmail.com', '$2y$12$IB1cFpicRZxfnj/G9COkWuuC1/O20AotB7vhqRNnmklzD6A8rw2Zm', 'super-admin', 1, '2025-11-10 02:38:33', '2025-11-10 01:42:04'),
(9, 'Nicolas', 'lozanonicolas65@gmail.com', '$2y$12$WjcbjzIYxU259YbrrwNILO3pWNL/xwUF7mEeKJzuARRYCI5bxYHtC', 'analista', 1, '2025-11-10 07:25:23', '2025-11-10 07:48:11'),
(10, 'nicolas', 'jnicolasloz@hotmail.com', '$2y$12$WmMS1a0QwKXXl82O.07HTu9fkc91T4/pPPLKHjGBhnXAS/wNsDAtu', 'analista', 1, '2025-11-23 23:32:14', '2025-11-23 23:32:14');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `annexes`
--
ALTER TABLE `annexes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indices de la tabla `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indices de la tabla `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nit_empresa` (`nit_empresa`);

--
-- Indices de la tabla `company_annex_submissions`
--
ALTER TABLE `company_annex_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `annex_id` (`annex_id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `submitted_by` (`submitted_by`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indices de la tabla `company_programs`
--
ALTER TABLE `company_programs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_programs_company_id_program_id_unique` (`company_id`,`program_id`),
  ADD KEY `company_programs_program_id_index` (`program_id`);

--
-- Indices de la tabla `company_program_annex_configs`
--
ALTER TABLE `company_program_annex_configs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_company_program_annex` (`company_id`,`program_id`,`annex_id`);

--
-- Indices de la tabla `company_user`
--
ALTER TABLE `company_user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_user_company_id_user_id_unique` (`company_id`,`user_id`),
  ADD KEY `company_user_user_id_index` (`user_id`);

--
-- Indices de la tabla `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `annexes`
--
ALTER TABLE `annexes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `company_annex_submissions`
--
ALTER TABLE `company_annex_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT de la tabla `company_programs`
--
ALTER TABLE `company_programs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `company_program_annex_configs`
--
ALTER TABLE `company_program_annex_configs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `company_user`
--
ALTER TABLE `company_user`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
