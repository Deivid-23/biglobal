-- =============================================================
-- BiGlobal · Migrar la base de TiDB (estructura VIEJA) a la REAL
-- =============================================================
-- Úsalo SOLO en la base de TiDB que tiene el modelo viejo:
--   roles 'Administrador'/'Estudiante'/'Instructor', usuarios.estado
--   en texto ('Activo'...) y sin columna usuarios.activo.
-- NO lo ejecutes en tu base local de XAMPP (esa ya está bien).
--
-- Conserva TODOS los datos: usuarios, cursos, lecciones, idiomas...
-- Antes de ejecutarlo: php tools/respaldar_bd.php
--
-- Cómo se cambia una columna de texto (ENUM) a número sin perder datos:
--   1. se crea una columna temporal nueva (ej. estado_num)
--   2. se llena traduciendo cada valor ('Publicado' -> 1)
--   3. se borra la columna vieja y la nueva toma su nombre
-- =============================================================

SET NAMES utf8mb4;

-- ---------- 1. ROLES: nombres que usa el código ----------
UPDATE roles SET nombre = 'admin'      WHERE nombre = 'Administrador';
UPDATE roles SET nombre = 'estudiante' WHERE nombre = 'Estudiante';
UPDATE roles SET nombre = 'docente'    WHERE nombre = 'Instructor';

-- ---------- 2. USUARIOS ----------
-- Login con Google: la contraseña puede quedar vacía (NULL)
ALTER TABLE usuarios MODIFY COLUMN password VARCHAR(255) NULL;

ALTER TABLE usuarios ADD COLUMN google_id VARCHAR(50) NULL AFTER password;
ALTER TABLE usuarios ADD COLUMN google_avatar VARCHAR(255) NULL AFTER google_id;
ALTER TABLE usuarios ADD UNIQUE KEY google_id (google_id);

-- activo: 1 activo · 0 inactivo · 2 docente pendiente (lo que usa el login)
ALTER TABLE usuarios ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER rol_id;
UPDATE usuarios SET activo = CASE estado
    WHEN 'Activo'    THEN 1
    WHEN 'Pendiente' THEN 2
    ELSE 0
END;

-- estado pasa de texto a número (queda solo por compatibilidad)
ALTER TABLE usuarios ADD COLUMN estado_num TINYINT(1) NOT NULL DEFAULT 1;
UPDATE usuarios SET estado_num = IF(estado = 'Activo', 1, 0);
ALTER TABLE usuarios DROP COLUMN estado;
ALTER TABLE usuarios CHANGE COLUMN estado_num estado TINYINT(1) NOT NULL DEFAULT 1;

ALTER TABLE usuarios ALTER COLUMN rol_id SET DEFAULT 2;

-- ---------- 3. CURSOS ----------
-- estado: 1 publicado · 0 no publicado
ALTER TABLE cursos ADD COLUMN estado_num TINYINT(1) NOT NULL DEFAULT 1;
UPDATE cursos SET estado_num = IF(estado = 'Publicado', 1, 0);
ALTER TABLE cursos DROP COLUMN estado;
ALTER TABLE cursos CHANGE COLUMN estado_num estado TINYINT(1) NOT NULL DEFAULT 1;

-- nivel: de lista fija (ENUM) a texto libre
ALTER TABLE cursos ADD COLUMN nivel_txt VARCHAR(50) NULL;
UPDATE cursos SET nivel_txt = nivel;
ALTER TABLE cursos DROP COLUMN nivel;
ALTER TABLE cursos CHANGE COLUMN nivel_txt nivel VARCHAR(50) NULL;

-- ---------- 4. LECCIONES ----------
ALTER TABLE lecciones ADD COLUMN contenido TEXT NULL AFTER descripcion;

ALTER TABLE lecciones ADD COLUMN estado_num TINYINT(1) NOT NULL DEFAULT 1;
UPDATE lecciones SET estado_num = IF(estado = 'Publicado', 1, 0);
ALTER TABLE lecciones DROP COLUMN estado;
ALTER TABLE lecciones CHANGE COLUMN estado_num estado TINYINT(1) NOT NULL DEFAULT 1;

-- ---------- 5. IDIOMAS ----------
ALTER TABLE idiomas ADD COLUMN estado_num TINYINT(1) NOT NULL DEFAULT 1;
UPDATE idiomas SET estado_num = IF(estado = 'Activo', 1, 0);
ALTER TABLE idiomas DROP COLUMN estado;
ALTER TABLE idiomas CHANGE COLUMN estado_num estado TINYINT(1) NOT NULL DEFAULT 1;

-- ---------- 6. ACTIVIDADES ----------
-- La estructura real usa "descripcion"; se copia lo que hubiera en "contenido"
ALTER TABLE actividades ADD COLUMN descripcion TEXT NULL AFTER titulo;
UPDATE actividades SET descripcion = contenido;
ALTER TABLE actividades DROP COLUMN contenido;
ALTER TABLE actividades DROP COLUMN orden;
ALTER TABLE actividades DROP COLUMN estado;
ALTER TABLE actividades MODIFY COLUMN tipo VARCHAR(50) NULL DEFAULT NULL;

-- ---------- 7. CONFIGURACIÓN ----------
ALTER TABLE configuracion ADD COLUMN tipo VARCHAR(20) DEFAULT 'text';
ALTER TABLE configuracion ADD COLUMN actualizado TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Claves nuevas (INSERT IGNORE: si alguna ya existe, no la toca)
INSERT IGNORE INTO configuracion (clave, valor, tipo) VALUES
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
    ('sitio_descripcion', 'Plataforma de aprendizaje de idiomas', 'text'),
    ('sitio_idioma', 'es', 'text'),
    ('sitio_zona', 'America/Bogota', 'text'),
    ('smtp_encriptacion', 'tls', 'text'),
    ('smtp_host', '', 'text'),
    ('smtp_password', '', 'text'),
    ('smtp_puerto', '587', 'int'),
    ('smtp_remitente', '', 'text'),
    ('smtp_usuario', '', 'text');

-- Las claves viejas pasan a los nombres nuevos (se copian, no se borran)
INSERT IGNORE INTO configuracion (clave, valor, tipo)
    SELECT 'sitio_nombre', valor, 'text' FROM configuracion WHERE clave = 'nombre_sitio';
INSERT IGNORE INTO configuracion (clave, valor, tipo)
    SELECT 'sitio_correo', valor, 'text' FROM configuracion WHERE clave = 'correo_contacto';
INSERT IGNORE INTO configuracion (clave, valor, tipo) VALUES
    ('sitio_nombre', 'BiGlobal', 'text'),
    ('sitio_correo', 'contacto@biglobal.com', 'text');

-- ---------- 8. TABLAS QUE FALTABAN ----------
CREATE TABLE IF NOT EXISTS recuperacion_password (
    id             INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    usuario_id     INT NOT NULL,
    token          VARCHAR(255) NOT NULL,
    expiracion     DATETIME NOT NULL,
    usado          TINYINT(1) NOT NULL DEFAULT 0,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY token (token),
    KEY fk_recuperacion_usuario (usuario_id),
    CONSTRAINT fk_recuperacion_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usuario_roles (
    id         INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    rol_id     INT NOT NULL,
    KEY usuario_id (usuario_id),
    KEY rol_id (rol_id),
    CONSTRAINT usuario_roles_ibfk_1 FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT usuario_roles_ibfk_2 FOREIGN KEY (rol_id) REFERENCES roles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La tabla reportes_contenido se deja tal cual (no se borra nada).
-- Después de este script ejecuta también: database/gamificacion.sql
