-- =============================================================
-- BiGlobal · Regla de acceso: qué es gratis y qué es de pago
-- =============================================================
-- Gratis : TODA la ruta de aprendizaje (A1 a C1, todos los idiomas)
--          para cualquier usuario registrado.
-- Pago   : solo lo que se hace con docentes: clases en vivo y grupos
--          de Classroom (plan Premium).
-- Excepción: el admin puede marcar una clase en vivo
--          (clases_vivo.abierta_para_todos) o un grupo (grupos.gratuito)
--          como gratuito para todos.
--
-- ruta_niveles.premium queda en 0 y el código ya no la usa.
-- grupos.gratuito: los grupos que YA existen quedan gratuitos (nadie
-- pierde el acceso de golpe); los nuevos nacen de pago (DEFAULT 0).
--
-- No borra datos. Se puede ejecutar más de una vez: si la columna ya
-- existe, no vuelve a marcar los grupos como gratuitos.
-- =============================================================

SET NAMES utf8mb4;

UPDATE ruta_niveles SET premium = 0 WHERE premium <> 0;

-- Al crear la columna con DEFAULT 1, los grupos existentes quedan gratuitos
ALTER TABLE grupos ADD COLUMN IF NOT EXISTS gratuito TINYINT(1) NOT NULL DEFAULT 1 AFTER archivado;
-- Desde ahora, los grupos nuevos nacen de pago
ALTER TABLE grupos ALTER COLUMN gratuito SET DEFAULT 0;
