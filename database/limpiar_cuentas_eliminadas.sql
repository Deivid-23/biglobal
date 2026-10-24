-- =============================================================
-- BiGlobal · Limpieza de cuentas ya eliminadas (revisión de seguridad)
-- =============================================================
-- Hasta ahora, al anonimizar una cuenta (usuarios.activo = 4) quedaban:
--   - los documentos de la solicitud de docente (pueden ser de identidad)
--     y la solicitud misma;
--   - el resultado del examen de ubicación.
-- Desde ahora CuentaEliminacionModel los borra. Este script borra los
-- que quedaron de cuentas eliminadas antes.
--
-- Solo toca cuentas con activo = 4 (eliminadas). Hacer respaldo antes.
-- Se puede ejecutar más de una vez.
-- =============================================================

SET NAMES utf8mb4;

DELETE FROM docente_documentos WHERE usuario_id IN (SELECT id FROM usuarios WHERE activo = 4);
DELETE FROM docente_solicitudes WHERE usuario_id IN (SELECT id FROM usuarios WHERE activo = 4);
DELETE FROM ruta_ubicacion WHERE usuario_id IN (SELECT id FROM usuarios WHERE activo = 4);
