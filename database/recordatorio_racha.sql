-- =============================================================
-- BiGlobal · Recordatorio de racha (campana + correo)
-- =============================================================
-- Si un estudiante tiene la racha viva y hoy todavía no estudia, entre
-- las 7 y las 10 p. m. de su zona le llega un aviso. La tarea la lanza
-- GitHub Actions cada hora (.github/workflows/recordatorio_racha.yml).
--
-- estudiante_stats.recordatorio_dia : día (en su zona) del último aviso,
--                                     para mandar uno solo por día.
-- usuarios.recordatorio_racha       : 1 = quiere el aviso (por defecto).
--                                     Se cambia en Mi perfil → Correos.
--
-- El interruptor general es la clave racha_recordatorio de la tabla
-- configuracion (Super admin → Configuración → Notificaciones); si no
-- existe, está encendido.
--
-- No borra datos. Se puede ejecutar más de una vez.
-- =============================================================

SET NAMES utf8mb4;

ALTER TABLE estudiante_stats ADD COLUMN IF NOT EXISTS recordatorio_dia DATE NULL;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS recordatorio_racha TINYINT(1) NOT NULL DEFAULT 1;
