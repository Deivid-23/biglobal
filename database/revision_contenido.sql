-- =============================================================
-- BiGlobal · Revisión del contenido de los docentes
-- =============================================================
-- Cursos y lecciones creados por un docente pasan por revisión del
-- admin antes de verse. Columna nueva "revision":
--   'borrador'     : lo está preparando el docente (no se ve)
--   'en_revision'  : el docente lo envió; espera al admin
--   'publicado'    : aprobado (se ve)
--   'cambios'      : el admin pidió cambios (con comentario)
--   'rechazado'    : el admin lo rechazó (con comentario; no se ve)
--
-- La columna "estado" de siempre sigue diciendo si los estudiantes lo
-- ven (1) o no (0); la maneja app/Models/RevisionModel.php. Una edición
-- de algo ya publicado vuelve a "en_revision" pero sigue visible.
--
-- Lo que ya existe queda igual: lo publicado (estado = 1) queda
-- 'publicado' y lo demás 'borrador'. No se borra nada.
-- Si se corre dos veces, los ALTER dan "Duplicate column": se puede ignorar
-- (los UPDATE solo tocan filas sin revisión).
-- =============================================================

ALTER TABLE cursos ADD COLUMN revision VARCHAR(20) NULL DEFAULT NULL;
ALTER TABLE cursos ADD COLUMN revision_comentario VARCHAR(1000) NULL DEFAULT NULL;
ALTER TABLE cursos ADD COLUMN revisado_por INT NULL DEFAULT NULL;
ALTER TABLE cursos ADD COLUMN revisado_en DATETIME NULL DEFAULT NULL;

ALTER TABLE lecciones ADD COLUMN revision VARCHAR(20) NULL DEFAULT NULL;
ALTER TABLE lecciones ADD COLUMN revision_comentario VARCHAR(1000) NULL DEFAULT NULL;
ALTER TABLE lecciones ADD COLUMN revisado_por INT NULL DEFAULT NULL;
ALTER TABLE lecciones ADD COLUMN revisado_en DATETIME NULL DEFAULT NULL;

UPDATE cursos    SET revision = IF(estado = 1, 'publicado', 'borrador') WHERE revision IS NULL;
UPDATE lecciones SET revision = IF(estado = 1, 'publicado', 'borrador') WHERE revision IS NULL;
