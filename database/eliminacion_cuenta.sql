-- =============================================================
-- BiGlobal · Eliminación de cuenta con 30 días de gracia
-- =============================================================
-- Estados nuevos de usuarios.activo (los anteriores no cambian):
--   0 = inactiva   1 = activa   2 = docente pendiente
--   3 = eliminación pendiente (30 días para recuperarla iniciando sesión)
--   4 = eliminada (datos personales borrados; la fila queda anónima)
--
-- Columnas nuevas:
--   eliminacion_solicitada_en    cuándo la pidió
--   eliminacion_programada_para  desde cuándo se puede borrar (solicitud + 30 días)
--   activo_previo                el estado que tenía, para devolvérselo si la recupera
--
-- Pasados los 30 días, la tarea diaria (src/tareas/eliminaciones.php)
-- ANONIMIZA la cuenta: borra nombre, correo, contraseña, Google, progreso,
-- notificaciones, etc. Se conservan los pagos (sin datos personales) por
-- contabilidad, y los cursos de un docente.
--
-- Solo agrega columnas. Si se corre dos veces da "Duplicate column": se puede ignorar.
-- =============================================================

ALTER TABLE usuarios ADD COLUMN eliminacion_solicitada_en DATETIME NULL DEFAULT NULL;

ALTER TABLE usuarios ADD COLUMN eliminacion_programada_para DATETIME NULL DEFAULT NULL;

ALTER TABLE usuarios ADD COLUMN activo_previo TINYINT NULL DEFAULT NULL;
