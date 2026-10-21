-- =============================================================
-- BiGlobal · Pasa las fechas viejas de UTC a hora de Colombia
-- =============================================================
-- Hasta ahora la conexión a TiDB trabajaba en UTC: NOW() y los
-- DEFAULT CURRENT_TIMESTAMP de las columnas DATETIME guardaron la hora
-- de Londres (5 horas más que Colombia). Desde src/bd/conexion.php la
-- conexión usa SET time_zone = '-05:00', así que las fechas NUEVAS ya
-- salen en hora de Colombia. Este script corrige las VIEJAS.
--
-- Se resta 5 horas UNA SOLA VEZ a las columnas DATETIME que llenaba la BD.
-- NO se tocan:
--   - notificaciones.creada_en y leida_en: ya se guardaban con la hora de
--     PHP (Colombia).
--   - Las columnas TIMESTAMP (xp_movimientos, auditoria, actividad_intentos,
--     usuario_medallas, configuracion, recuperacion_password.fecha_creacion):
--     se guardan en UTC por dentro y se convierten solas al leerlas.
--
-- ¡NO ejecutar dos veces! Restaría otras 5 horas.
-- Comprobado el 2026-10-21: progreso.fecha_completado coincide al segundo
-- con xp_movimientos.fecha (TIMESTAMP leído en UTC), o sea, estaba en UTC.
-- =============================================================

UPDATE usuarios      SET fecha_registro   = fecha_registro   - INTERVAL 5 HOUR WHERE fecha_registro   IS NOT NULL;
UPDATE idiomas       SET fecha_creacion   = fecha_creacion   - INTERVAL 5 HOUR WHERE fecha_creacion   IS NOT NULL;
UPDATE cursos        SET fecha_creacion   = fecha_creacion   - INTERVAL 5 HOUR WHERE fecha_creacion   IS NOT NULL;
UPDATE lecciones     SET fecha_creacion   = fecha_creacion   - INTERVAL 5 HOUR WHERE fecha_creacion   IS NOT NULL;
UPDATE actividades   SET fecha_creacion   = fecha_creacion   - INTERVAL 5 HOUR WHERE fecha_creacion   IS NOT NULL;
UPDATE certificados  SET fecha_emision    = fecha_emision    - INTERVAL 5 HOUR WHERE fecha_emision    IS NOT NULL;
UPDATE progreso      SET fecha_completado = fecha_completado - INTERVAL 5 HOUR WHERE fecha_completado IS NOT NULL;
UPDATE recuperacion_password SET expiracion = expiracion - INTERVAL 5 HOUR WHERE expiracion IS NOT NULL;
