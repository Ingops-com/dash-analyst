-- phpMyadmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 20-10-2025 a las 14:35:55
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `gsccazh_`
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
(3, 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `annexes`
--

CREATE TABLE `annexes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `codigo_anexo` varchar(255) DEFAULT NULL,
  `tipo` enum('ISO 22000','PSB','Invima') DEFAULT NULL,
  `status` enum('En revisión','Aprobado','Obsoleto') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `annexes`
--

INSERT INTO `annexes` (`id`, `nombre`, `codigo_anexo`, `tipo`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Certificado de Fumigación', 'ANX-CF-01', 'PSB', 'Aprobado', '2025-10-19 10:03:33', '2025-10-19 10:03:33'),
(2, 'Factura de Insumos de Limpieza', 'ANX-FI-02', 'PSB', 'Aprobado', '2025-10-19 10:03:33', '2025-10-19 10:03:33'),
(3, 'Registro Fotográfico de Trampas', 'ANX-RF-03', 'PSB', 'En revisión', '2025-10-19 10:03:33', '2025-10-19 10:03:33'),
(4, 'Checklist Interno de Limpieza', 'ANX-CI-04', 'PSB', 'Aprobado', '2025-10-19 10:03:33', '2025-10-19 10:03:33'),
(5, 'Manual de Calidad', 'ANX-MC-05', 'ISO 22000', 'Aprobado', '2025-10-19 10:03:33', '2025-10-19 10:03:33'),
(6, 'Control de Temperaturas de Neveras', 'ANX-CT-06', 'Invima', 'Aprobado', '2025-10-19 10:03:33', '2025-10-19 10:03:33');

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
('config:permissions:2', 'a:3:{s:4:\"view\";b:1;s:4:\"edit\";b:1;s:6:\"delete\";b:0;}', 1760871813);

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
(1, 'Alimentos Frescos S.A.S', '900.123.456-1', 'contacto@frescos.com', 'Calle 100 # 20-30', '3101234567', 'Ana García', 'Carlos Ruiz', '1.0', '2024-01-15', '2025-01-15', 'Procesamiento de frutas y verduras', NULL, NULL, NULL, 1, '2025-10-19 10:03:33', '2025-10-19 10:03:33'),
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

INSERT INTO `company_annex_submissions` (`id`, `company_id`, `annex_id`, `program_id`, `file_path`, `file_name`, `mime_type`, `file_size`, `status`, `submitted_by`, `reviewed_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 'submissions/frescos/fumigacion_oct25.pdf', 'fumigacion_oct25.pdf', 'application/pdf', 204800, 'Aprobado', 4, 2, '2025-10-19 10:03:33', '2025-10-19 10:03:33'),
(2, 2, 6, 3, 'submissions/lacteos/temps_semana40.xlsx', 'temps_semana40.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 51200, 'Pendiente', 5, NULL, '2025-10-19 10:03:33', '2025-10-19 10:03:33'),
(3, 3, 1, 1, 'submissions/carnes/fumigacion_oct25.pdf', 'fumigacion_oct25.pdf', 'application/pdf', 215300, 'Rechazado', 6, 3, '2025-10-19 10:03:33', '2025-10-19 10:03:33');

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
(1, 1, 1),
(1, 1, 2),
(1, 1, 4),
(2, 1, 1),
(2, 2, 5),
(2, 3, 6),
(3, 1, 1),
(3, 3, 6);

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
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `programs`
--

INSERT INTO `programs` (`id`, `nombre`, `version`, `codigo`, `fecha`, `tipo`, `created_at`, `updated_at`) VALUES
(1, 'Plan de Saneamiento Básico', '1.0', 'PSB-001', '2025-01-01', 'PSB', '2025-10-19 10:03:33', '2025-10-19 10:03:33'),
(2, 'Sistema de Gestión de Calidad', '2.5', 'ISO-22000-001', '2025-03-10', 'ISO 22000', '2025-10-19 10:03:33', '2025-10-19 10:03:33'),
(3, 'Buenas Prácticas de Manufactura', '1.2', 'INV-BPM-001', '2024-11-05', 'Invima', '2025-10-19 10:03:33', '2025-10-19 10:03:33');

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
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(2, 5),
(3, 6);

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
('rfMwTh9vz8fYRdBQK3SrzlRaNL7ZjwCH8qLN7HcN', 7, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiSmNBUEZpMXhoZDFNTU51SzU2elZxS0IzMTJwNjJoajBDU1JaMWF1OCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjc7fQ==', 1760868262),
('sesionUnicaABC123', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) ...', 'payload_codificado_en_base64_aqui', 1760868213);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `rol` enum('administrador','analista','usuario') DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `habilitado` tinyint(1) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email`, `email_verified_at`, `password`, `rol`, `company_id`, `habilitado`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'admin General', 'admin', 'admin@cazh.com', NULL, '$2y$12$RqPoCxwT/prgYC2VTLuyRuD/8xn2c4cOwYfRckdczQsPvmz03IxAW', 'administrador', NULL, 1, NULL, '2025-10-19 10:03:33', '2025-10-19 10:03:33'),
(2, 'analista Senior', 'analista1', 'analista1@cazh.com', NULL, '$2y$12$RqPoCxwT/prgYC2VTLuyRuD/8xn2c4cOwYfRckdczQsPvmz03IxAW', 'analista', NULL, 1, NULL, '2025-10-19 10:03:33', '2025-10-19 10:03:33'),
(3, 'analista Junior', 'analista2', 'analista2@cazh.com', NULL, '$2y$12$RqPoCxwT/prgYC2VTLuyRuD/8xn2c4cOwYfRckdczQsPvmz03IxAW', 'analista', NULL, 1, NULL, '2025-10-19 10:03:33', '2025-10-19 10:03:33'),
(4, 'Juan Pérez (Frescos)', 'jperez', 'juan.perez@frescos.com', NULL, '$2y$12$RqPoCxwT/prgYC2VTLuyRuD/8xn2c4cOwYfRckdczQsPvmz03IxAW', 'usuario', 1, 1, NULL, '2025-10-19 10:03:33', '2025-10-19 10:03:33'),
(5, 'Maria Lopez (Lácteos)', 'mlopez', 'maria.lopez@lacteosdelcampo.com', NULL, '$2y$12$RqPoCxwT/prgYC2VTLuyRuD/8xn2c4cOwYfRckdczQsPvmz03IxAW', 'usuario', 2, 1, NULL, '2025-10-19 10:03:33', '2025-10-19 10:03:33'),
(6, 'David Ríos (Carnes)', 'drios', 'david.rios@carnesdelsur.co', NULL, '$2y$12$RqPoCxwT/prgYC2VTLuyRuD/8xn2c4cOwYfRckdczQsPvmz03IxAW', 'usuario', 3, 1, NULL, '2025-10-19 10:03:33', '2025-10-19 10:03:33'),
(7, 'Niko5778', NULL, 'lozanonicolas65@gmail.com', NULL, '$2y$12$n39uxzBjiQbU255JwaNf5OKSue9ruGrPRVw4Hvm7v.P6Ju7ZKDoEu', NULL, NULL, NULL, NULL, '2025-10-19 15:04:19', '2025-10-19 15:04:19');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `analyst_companies`
--
ALTER TABLE `analyst_companies`
  ADD PRIMARY KEY (`user_id`,`company_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indices de la tabla `annexes`
--
ALTER TABLE `annexes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_anexo` (`codigo_anexo`);

--
-- Indices de la tabla `annex_change_logs`
--
ALTER TABLE `annex_change_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `annex_version_id` (`annex_version_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `annex_versions`
--
ALTER TABLE `annex_versions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `annex_id` (`annex_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `approved_by` (`approved_by`);

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
-- Indices de la tabla `company_poe_records`
--
ALTER TABLE `company_poe_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `poe_id` (`poe_id`),
  ADD KEY `ejecutado_por` (`ejecutado_por`);

--
-- Indices de la tabla `company_program_config`
--
ALTER TABLE `company_program_config`
  ADD PRIMARY KEY (`company_id`,`program_id`,`annex_id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `annex_id` (`annex_id`);

--
-- Indices de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`);

--
-- Indices de la tabla `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indices de la tabla `poes`
--
ALTER TABLE `poes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_poe` (`codigo_poe`),
  ADD KEY `program_id` (`program_id`);

--
-- Indices de la tabla `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `program_annexes`
--
ALTER TABLE `program_annexes`
  ADD PRIMARY KEY (`program_id`,`annex_id`),
  ADD KEY `annex_id` (`annex_id`);

--
-- Indices de la tabla `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `company_id` (`company_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `annexes`
--
ALTER TABLE `annexes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `annex_change_logs`
--
ALTER TABLE `annex_change_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `annex_versions`
--
ALTER TABLE `annex_versions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `company_annex_submissions`
--
ALTER TABLE `company_annex_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `company_poe_records`
--
ALTER TABLE `company_poe_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `poes`
--
ALTER TABLE `poes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `analyst_companies`
--
ALTER TABLE `analyst_companies`
  ADD CONSTRAINT `analyst_companies_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `analyst_companies_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`);

--
-- Filtros para la tabla `annex_change_logs`
--
ALTER TABLE `annex_change_logs`
  ADD CONSTRAINT `annex_change_logs_ibfk_1` FOREIGN KEY (`annex_version_id`) REFERENCES `annex_versions` (`id`),
  ADD CONSTRAINT `annex_change_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `annex_versions`
--
ALTER TABLE `annex_versions`
  ADD CONSTRAINT `annex_versions_ibfk_1` FOREIGN KEY (`annex_id`) REFERENCES `annexes` (`id`),
  ADD CONSTRAINT `annex_versions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `annex_versions_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `company_annex_submissions`
--
ALTER TABLE `company_annex_submissions`
  ADD CONSTRAINT `company_annex_submissions_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `company_annex_submissions_ibfk_2` FOREIGN KEY (`annex_id`) REFERENCES `annexes` (`id`),
  ADD CONSTRAINT `company_annex_submissions_ibfk_3` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`),
  ADD CONSTRAINT `company_annex_submissions_ibfk_4` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `company_annex_submissions_ibfk_5` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `company_poe_records`
--
ALTER TABLE `company_poe_records`
  ADD CONSTRAINT `company_poe_records_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `company_poe_records_ibfk_2` FOREIGN KEY (`poe_id`) REFERENCES `poes` (`id`),
  ADD CONSTRAINT `company_poe_records_ibfk_3` FOREIGN KEY (`ejecutado_por`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `company_program_config`
--
ALTER TABLE `company_program_config`
  ADD CONSTRAINT `company_program_config_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `company_program_config_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`),
  ADD CONSTRAINT `company_program_config_ibfk_3` FOREIGN KEY (`annex_id`) REFERENCES `annexes` (`id`);

--
-- Filtros para la tabla `poes`
--
ALTER TABLE `poes`
  ADD CONSTRAINT `poes_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`);

--
-- Filtros para la tabla `program_annexes`
--
ALTER TABLE `program_annexes`
  ADD CONSTRAINT `program_annexes_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`),
  ADD CONSTRAINT `program_annexes_ibfk_2` FOREIGN KEY (`annex_id`) REFERENCES `annexes` (`id`);

--
-- Filtros para la tabla `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
