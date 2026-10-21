-- =============================================================
-- BiGlobal · Borra dos tablas que nunca se usaron
-- =============================================================
-- usuario_roles      : vacía. El rol de cada cuenta está en usuarios.rol_id.
-- reportes_contenido : vacía. Nunca se hizo la función de reportes.
--
-- Ningún archivo del código las lee ni las escribe y ninguna otra
-- tabla depende de ellas (solo tenían llaves hacia usuarios y roles).
-- Autorizado por el dueño del proyecto el 2026-10-21.
-- Respaldo previo: respaldos/respaldo_biglobal_2026-10-21_033103.sql
-- =============================================================

DROP TABLE IF EXISTS usuario_roles;

DROP TABLE IF EXISTS reportes_contenido;
