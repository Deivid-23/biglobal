-- =============================================================
-- BiGlobal · Quita la columna premium de la ruta
-- =============================================================
-- Toda la ruta de aprendizaje es gratis (database/acceso_gratis_y_pago.sql
-- dejó la columna en 0 y el código ya no la usa desde el commit bf9d154).
--
-- Borra solo esa columna; no toca niveles, lecciones ni progreso.
-- Se puede ejecutar más de una vez.
-- =============================================================

SET NAMES utf8mb4;

ALTER TABLE ruta_niveles DROP COLUMN IF EXISTS premium;
