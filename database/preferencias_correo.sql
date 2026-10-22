-- =============================================================
-- BiGlobal · Preferencia de correos no esenciales
-- =============================================================
-- usuarios.correos_opcionales: 1 = recibe también los correos no
-- esenciales (invitaciones a clases gratuitas, cupo liberado, contenido
-- aprobado o rechazado, recordatorio de Premium por vencer).
-- 0 = solo los esenciales (pagos, cuenta, aprobación de docente).
-- La campana de la plataforma siempre recibe todo.
-- Ver Notificador::CANAL.
--
-- Solo agrega una columna (encendida para todos). No toca otros datos.
-- Si se corre dos veces da "Duplicate column": se puede ignorar.
-- =============================================================

ALTER TABLE usuarios ADD COLUMN correos_opcionales TINYINT(1) NOT NULL DEFAULT 1 AFTER color_acento;
