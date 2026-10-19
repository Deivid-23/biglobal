-- =============================================================
-- BiGlobal - Esquema de base de datos (instalación nueva)
-- =============================================================
-- Esta estructura es IGUAL a la base real (biglobal.sql, oct. 2026).
-- Úsala solo para crear una base VACÍA (por ejemplo en TiDB Cloud
-- la primera vez). Si ya tienes datos, importa tu volcado .sql.
--
-- Modelo real que usa el código:
--   - Roles (tabla roles): admin (1), estudiante (2), docente (3).
--   - Estado de la cuenta: usuarios.activo
--       0 = inactivo / rechazado
--       1 = activo
--       2 = docente pendiente de aprobación
--     La columna usuarios.estado existe pero NO se usa (compatibilidad).
--   - cursos.estado, lecciones.estado, idiomas.estado: 1 = activo/publicado, 0 = no.
--   - usuario_roles existe en la BD pero el código no la usa.
-- =============================================================

CREATE DATABASE IF NOT EXISTS biglobal
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE biglobal;

-- Las tablas se crean en orden alfabético: apagamos la revisión de llaves
-- foráneas mientras se crean y la volvemos a encender al final.
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE `actividades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `leccion_id` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_actividad_leccion` (`leccion_id`),
  CONSTRAINT `fk_actividad_leccion` FOREIGN KEY (`leccion_id`) REFERENCES `lecciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `certificados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `fecha_emision` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  UNIQUE KEY `unico_certificado` (`usuario_id`,`curso_id`),
  KEY `curso_id` (`curso_id`),
  CONSTRAINT `certificados_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `certificados_ibfk_2` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
CREATE TABLE `configuracion` (
  `clave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `tipo` varchar(20) DEFAULT 'text',
  `actualizado` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
CREATE TABLE `cursos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `nivel` varchar(50) DEFAULT NULL,
  `instructor_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_curso_instructor` (`instructor_id`),
  CONSTRAINT `fk_curso_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `idiomas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `codigo` varchar(10) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `lecciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `curso_id` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `contenido` text DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `orden` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_leccion_curso` (`curso_id`),
  CONSTRAINT `fk_leccion_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `progreso` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `leccion_id` int(11) NOT NULL,
  `completado` tinyint(1) DEFAULT 0,
  `fecha_completado` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unico_progreso` (`usuario_id`,`leccion_id`),
  KEY `leccion_id` (`leccion_id`),
  CONSTRAINT `progreso_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `progreso_ibfk_2` FOREIGN KEY (`leccion_id`) REFERENCES `lecciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
CREATE TABLE `recuperacion_password` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expiracion` datetime NOT NULL,
  `usado` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `fk_recuperacion_usuario` (`usuario_id`),
  CONSTRAINT `fk_recuperacion_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `usuario_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `rol_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `rol_id` (`rol_id`),
  CONSTRAINT `usuario_roles_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `usuario_roles_ibfk_2` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `correo` varchar(150) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `google_id` varchar(50) DEFAULT NULL,
  `google_avatar` varchar(255) DEFAULT NULL,
  `rol_id` int(11) NOT NULL DEFAULT 2,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `correo` (`correo`),
  UNIQUE KEY `google_id` (`google_id`),
  KEY `fk_usuario_rol` (`rol_id`),
  CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================
-- Datos base
-- =============================================================
INSERT INTO roles (id, nombre) VALUES
    (1, 'admin'),
    (2, 'estudiante'),
    (3, 'docente');

INSERT INTO idiomas (nombre, codigo, estado) VALUES
    ('Español', 'es', 1),
    ('Inglés', 'en', 1);

INSERT INTO configuracion (clave, valor, tipo) VALUES
    ('color_acento', 'blue', 'text'),
    ('tema_default', 'light', 'text'),
    ('notif_nuevo_curso', '1', 'bool'),
    ('notif_nuevo_reporte', '1', 'bool'),
    ('notif_nuevo_usuario', '1', 'bool'),
    ('notif_resumen_semanal', '0', 'bool'),
    ('seg_bloqueo_minutos', '1', 'int'),
    ('seg_intentos_login', '5', 'int'),
    ('seg_min_password', '6', 'int'),
    ('seg_requiere_mayuscula', '1', 'bool'),
    ('seg_requiere_numero', '0', 'bool'),
    ('sitio_correo', 'contacto@biglobal.com', 'text'),
    ('sitio_descripcion', 'Plataforma de aprendizaje de idiomas', 'text'),
    ('sitio_idioma', 'es', 'text'),
    ('sitio_nombre', 'BiGlobal', 'text'),
    ('sitio_zona', 'America/Bogota', 'text'),
    ('smtp_encriptacion', 'tls', 'text'),
    ('smtp_host', 'smtp.gmail.com', 'text'),
    ('smtp_password', '', 'text'),
    ('smtp_puerto', '587', 'int'),
    ('smtp_remitente', 'no-reply@biglobal.com', 'text'),
    ('smtp_usuario', '', 'text');

-- Primer administrador: genera el hash con
--   php tools/generar_hash.php "TuContraseñaSegura"
-- y luego:
-- INSERT INTO usuarios (nombre, apellido, correo, password, rol_id, activo, estado)
-- VALUES ('Admin', 'BiGlobal', 'admin@tudominio.com', '<hash generado>', 1, 1, 1);
