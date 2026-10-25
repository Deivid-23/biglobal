-- =============================================================
-- BiGlobal · Tabla de posiciones (XP de la semana)
-- =============================================================
-- usuarios.posiciones_visible : 1 = aparece en la tabla de posiciones
--                               (por defecto); 0 = oculto para los demás
--                               (sigue viendo su propia posición).
--                               Se cambia en Mi perfil → Privacidad.
--
-- Los datos salen de xp_movimientos (ya existe). Reglas en
-- app/Models/PosicionesModel.php.
-- No borra datos. Se puede ejecutar más de una vez.
-- =============================================================

SET NAMES utf8mb4;

ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS posiciones_visible TINYINT(1) NOT NULL DEFAULT 1;
