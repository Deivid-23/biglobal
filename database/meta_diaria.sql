-- =============================================================
-- BiGlobal · Meta diaria de XP
-- =============================================================
-- usuarios.meta_xp_diaria : XP que el estudiante se propone ganar cada
--                           día (10, 20, 30 o 50; 20 por defecto). Se
--                           cambia en Mi perfil → Meta diaria.
--
-- El XP del día sale de xp_movimientos (ya existe), contado en la zona
-- horaria del estudiante. No da XP extra ni insignias.
-- Reglas en app/Models/GamificacionModel.php (META_*).
-- No borra datos. Se puede ejecutar más de una vez.
-- =============================================================

SET NAMES utf8mb4;

ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS meta_xp_diaria SMALLINT NOT NULL DEFAULT 20;
