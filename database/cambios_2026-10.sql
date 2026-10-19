-- =============================================================
-- BiGlobal · Cambios para la base de datos EXISTENTE (oct. 2026)
-- =============================================================
-- Ejecuta ESTE archivo en tu base actual (local y TiDB).
-- No borra datos ni tablas. Haz un respaldo antes:
--   mysqldump -u root -p biglobal > respaldo_antes_de_cambios.sql
-- =============================================================

-- 1) Alinear el color del panel admin con la identidad azul.
--    Hoy la BD tiene 'orange'. El código ya usa 'blue' por defecto,
--    pero este valor guardado tiene prioridad.
UPDATE configuracion SET valor = 'blue'  WHERE clave = 'color_acento';

-- 2) Tema claro por defecto (ya está en 'light'; se deja por si acaso).
UPDATE configuracion SET valor = 'light' WHERE clave = 'tema_default';

-- 3) Idioma del sitio: la plataforma está en español.
--    (Hoy dice 'en'. Si lo cambiaste a propósito, omite esta línea.)
UPDATE configuracion SET valor = 'es'    WHERE clave = 'sitio_idioma';

-- 4) Recuperación de contraseña: NO necesita cambios.
--    La tabla recuperacion_password ya existe y se usa tal cual.
--    (La columna "token" ahora guarda la huella SHA-256 del token.)

-- 5) usuarios.estado: NO se borra. El código usa solo usuarios.activo.
--    Cuando confirmes que nadie del equipo usa "estado", podrás quitarla con:
--    ALTER TABLE usuarios DROP COLUMN estado;   -- (NO ejecutar todavía)
