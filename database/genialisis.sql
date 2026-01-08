-- =====================================================
-- GENIALISIS - Base de Datos
-- Sistema de Información Académico para Jardines Infantiles
-- =====================================================

-- Configuración
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 1. Tabla: configuracion_portal
-- Almacena configuraciones generales del sistema
-- =====================================================
CREATE TABLE `configuracion_portal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `tipo` enum('texto','numero','url','boolean','json') DEFAULT 'texto',
  `categoria` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos iniciales de configuración
INSERT INTO `configuracion_portal` (`clave`, `valor`, `descripcion`, `tipo`, `categoria`) VALUES
('google_analytics_id', '', 'ID de Google Analytics', 'texto', 'analytics'),
('calendly_url', '', 'URL de Calendly para agendar demos', 'url', 'contacto'),
('honeypot_enabled', '1', 'Activar honeypot anti-spam', 'boolean', 'seguridad'),
('contacto_limite_por_hora', '3', 'Límite de contactos por hora por IP', 'numero', 'seguridad'),
('contacto_correos', '["contacto@genialisis.com"]', 'Correos de contacto', 'json', 'contacto'),
('contacto_telefono', '', 'Teléfono de contacto', 'texto', 'contacto'),
('contacto_whatsapp', '', 'URL de WhatsApp', 'url', 'contacto');

-- =====================================================
-- 2. Tabla: estados_contacto
-- Estados del proceso de seguimiento de leads
-- =====================================================
CREATE TABLE `estados_contacto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `color` varchar(20) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `orden` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Estados del funnel de ventas
INSERT INTO `estados_contacto` (`nombre`, `color`, `orden`) VALUES
('Nuevo', '#3b82f6', 1),
('Contactado', '#f59e0b', 2),
('Demo Agendada', '#8b5cf6', 3),
('En Evaluación', '#06b6d4', 4),
('Cerrado Ganado', '#10b981', 5),
('Cerrado Perdido', '#ef4444', 6);

-- =====================================================
-- 3. Tabla: tipos_consulta
-- Tipos de consulta que puede hacer un prospecto
-- =====================================================
CREATE TABLE `tipos_consulta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `orden` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tipos de consulta específicos de Genialisis
INSERT INTO `tipos_consulta` (`nombre`, `orden`) VALUES
('Solicitar demostración', 1),
('Información general', 2),
('Consulta técnica', 3),
('Migración desde otro sistema', 4),
('Capacitación', 5),
('Otro', 6);

-- =====================================================
-- 4. Tabla: tipos_como_conocio
-- Canales de adquisición de leads
-- =====================================================
CREATE TABLE `tipos_como_conocio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `pide_detalle` tinyint(1) DEFAULT 0 COMMENT 'Indica si requiere campo de detalle adicional',
  `placeholder_detalle` varchar(150) DEFAULT NULL COMMENT 'Texto placeholder para el campo de detalle',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Canales de marketing
INSERT INTO `tipos_como_conocio` (`nombre`, `pide_detalle`, `placeholder_detalle`) VALUES
('Google / Búsqueda web', 0, NULL),
('Redes sociales', 1, '¿Cuál red social?'),
('Recomendación', 1, '¿Quién te recomendó?'),
('Email marketing', 0, NULL),
('WhatsApp', 0, NULL),
('Evento / Feria', 1, '¿Qué evento?'),
('Publicidad online', 1, '¿Dónde viste el anuncio?'),
('Otro', 1, 'Por favor especifica');

-- =====================================================
-- 5. Tabla: contactos
-- Leads y prospectos de Genialisis
-- =====================================================
CREATE TABLE `contactos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_jardin` varchar(255) DEFAULT NULL COMMENT 'Nombre del jardín infantil',
  `numero_estudiantes` int(11) DEFAULT NULL COMMENT 'Cantidad de estudiantes del jardín',
  `nombre_contacto` varchar(255) NOT NULL COMMENT 'Nombre de la persona que contacta',
  `email` varchar(255) NOT NULL,
  `telefono` varchar(50) NOT NULL,
  `mensaje` text NOT NULL,
  `como_conocio_detalle` varchar(255) DEFAULT NULL,
  `id_tipo_consulta` int(11) NOT NULL,
  `id_como_conocio` int(11) NOT NULL,
  `id_estado` int(11) DEFAULT 1,
  `notas_internas` text DEFAULT NULL COMMENT 'Notas del equipo de ventas',
  `fecha_cita` datetime DEFAULT NULL COMMENT 'Fecha de demo agendada',
  `calendly_event_uri` varchar(255) DEFAULT NULL,
  `calendly_invitee_uri` varchar(255) DEFAULT NULL,
  `calendly_event_type` varchar(255) DEFAULT NULL,
  `cita_estado` enum('pendiente','confirmada','cancelada','completada','reprogramada') DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `honeypot` varchar(255) DEFAULT NULL COMMENT 'Campo honeypot anti-spam',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_tipo_consulta` (`id_tipo_consulta`),
  KEY `id_como_conocio` (`id_como_conocio`),
  KEY `idx_estado` (`id_estado`),
  KEY `idx_email` (`email`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_fecha_cita` (`fecha_cita`),
  KEY `idx_ip_created` (`ip_address`,`created_at`),
  CONSTRAINT `contactos_ibfk_1` FOREIGN KEY (`id_tipo_consulta`) REFERENCES `tipos_consulta` (`id`),
  CONSTRAINT `contactos_ibfk_2` FOREIGN KEY (`id_como_conocio`) REFERENCES `tipos_como_conocio` (`id`),
  CONSTRAINT `contactos_ibfk_3` FOREIGN KEY (`id_estado`) REFERENCES `estados_contacto` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Índices adicionales para optimización
-- =====================================================
ALTER TABLE `contactos` ADD INDEX `idx_jardin_estudiantes` (`nombre_jardin`, `numero_estudiantes`);
ALTER TABLE `contactos` ADD INDEX `idx_cita_estado` (`cita_estado`, `fecha_cita`);

-- =====================================================
-- Restaurar configuración
-- =====================================================
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================
