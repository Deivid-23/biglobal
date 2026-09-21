-- =============================================================
-- BiGlobal - Esquema de base de datos
-- =============================================================
-- Este esquema documenta y corrige el modelo de datos que ya
-- usaba el proyecto. Principales correcciones respecto al
-- código original:
--
--   1. Se elimina la tabla "usuario_roles" (rol múltiple) y se
--      deja un único "rol_id" en "usuarios", que es el modelo
--      que en realidad usan login.php y el dashboard del admin.
--      Antes existían dos modelos de roles incompatibles entre
--      sí (uno rompía el login de cualquier usuario creado
--      desde el panel de administrador).
--   2. Se usa una sola columna de estado: "estado" ENUM
--      ('Activo','Inactivo'), en vez de mezclar "activo" (entero)
--      y "estado" (texto) como pasaba antes.
--   3. Se agregan las tablas nuevas que ahora respaldan el
--      panel de administrador (reportes, moderación,
--      certificados, configuración) y el nuevo rol de
--      estudiante (progreso, certificados).
-- =============================================================

CREATE DATABASE IF NOT EXISTS biglobal
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE biglobal;

-- -------------------------------------------------------------
-- Roles
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS roles (
    id     INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO roles (id, nombre) VALUES
    (1, 'Administrador'),
    (2, 'Estudiante'),
    (3, 'Instructor')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- -------------------------------------------------------------
-- Usuarios
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    nombre         VARCHAR(100) NOT NULL,
    apellido       VARCHAR(100) NOT NULL,
    correo         VARCHAR(150) NOT NULL UNIQUE,
    password       VARCHAR(255) NOT NULL,
    rol_id         INT NOT NULL,
    estado         ENUM('Activo', 'Inactivo', 'Pendiente') NOT NULL DEFAULT 'Activo',
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_usuarios_rol
        FOREIGN KEY (rol_id) REFERENCES roles(id)
) ENGINE=InnoDB;

-- Nota: para crear el primer administrador, genera un hash con
-- `php tools/generar_hash.php "TuContraseñaSegura"` y luego
-- inserta el usuario manualmente, por ejemplo:
--
-- INSERT INTO usuarios (nombre, apellido, correo, password, rol_id, estado)
-- VALUES ('Admin', 'BiGlobal', 'admin@biglobal.com', '<hash generado>', 1, 'Activo');

-- -------------------------------------------------------------
-- Idiomas
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS idiomas (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(100) NOT NULL UNIQUE,
    codigo          VARCHAR(10) NOT NULL UNIQUE,
    estado          ENUM('Activo', 'Inactivo') NOT NULL DEFAULT 'Activo',
    fecha_creacion  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO idiomas (nombre, codigo, estado) VALUES
    ('Inglés', 'EN', 'Activo'),
    ('Español', 'ES', 'Activo'),
    ('Lengua de Señas', 'LSC', 'Activo')
ON DUPLICATE KEY UPDATE estado = VALUES(estado);

-- -------------------------------------------------------------
-- Cursos
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cursos (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    nombre         VARCHAR(150) NOT NULL,
    descripcion    TEXT NULL,
    nivel          ENUM('Básico', 'Intermedio', 'Avanzado') NOT NULL,
    estado         ENUM('Borrador', 'Publicado', 'Inactivo') NOT NULL DEFAULT 'Borrador',
    instructor_id  INT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_cursos_instructor
        FOREIGN KEY (instructor_id) REFERENCES usuarios(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- Lecciones
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS lecciones (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    curso_id       INT NOT NULL,
    titulo         VARCHAR(150) NOT NULL,
    descripcion    TEXT NULL,
    orden          INT NOT NULL DEFAULT 1,
    estado         ENUM('Borrador', 'Publicado', 'Inactivo') NOT NULL DEFAULT 'Borrador',
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_lecciones_curso
        FOREIGN KEY (curso_id) REFERENCES cursos(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- Actividades
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS actividades (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    leccion_id     INT NOT NULL,
    titulo         VARCHAR(150) NOT NULL,
    tipo           VARCHAR(50) NOT NULL DEFAULT 'General',
    contenido      TEXT NULL,
    orden          INT NOT NULL DEFAULT 1,
    estado         ENUM('Borrador', 'Publicado', 'Inactivo') NOT NULL DEFAULT 'Borrador',
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_actividades_leccion
        FOREIGN KEY (leccion_id) REFERENCES lecciones(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- Progreso del estudiante (nuevo, para el rol estudiante)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS progreso (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id        INT NOT NULL,
    leccion_id        INT NOT NULL,
    completado        TINYINT(1) NOT NULL DEFAULT 0,
    fecha_completado  DATETIME NULL,

    CONSTRAINT fk_progreso_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_progreso_leccion
        FOREIGN KEY (leccion_id) REFERENCES lecciones(id)
        ON DELETE CASCADE,

    UNIQUE KEY uq_progreso_usuario_leccion (usuario_id, leccion_id)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- Certificados (emitidos al completar un curso)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS certificados (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id     INT NOT NULL,
    curso_id       INT NOT NULL,
    codigo         VARCHAR(50) NOT NULL UNIQUE,
    fecha_emision  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_certificados_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_certificados_curso
        FOREIGN KEY (curso_id) REFERENCES cursos(id)
        ON DELETE CASCADE,

    UNIQUE KEY uq_certificado_usuario_curso (usuario_id, curso_id)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- Reportes de contenido (moderación)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reportes_contenido (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    tipo           VARCHAR(50) NOT NULL,
    referencia     VARCHAR(255) NULL,
    motivo         TEXT NOT NULL,
    estado         ENUM('Pendiente', 'Revisado', 'Descartado') NOT NULL DEFAULT 'Pendiente',
    creado_por     INT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_reportes_usuario
        FOREIGN KEY (creado_por) REFERENCES usuarios(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- Configuración general de la plataforma (clave / valor)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS configuracion (
    clave VARCHAR(100) PRIMARY KEY,
    valor TEXT NULL
) ENGINE=InnoDB;

INSERT INTO configuracion (clave, valor) VALUES
    ('nombre_sitio', 'BiGlobal'),
    ('correo_contacto', 'contacto@biglobal.com'),
    ('modo_mantenimiento', '0')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);
