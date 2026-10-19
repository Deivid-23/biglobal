-- =============================================================
-- BiGlobal · Cada curso ligado a un idioma (Inglés, Español, LSC…)
-- =============================================================
-- 1) Columna nueva cursos.idioma_id (puede quedar vacía = "sin idioma").
--    Si un idioma se borra, sus cursos quedan "sin idioma" (no se borran).
-- 2) Asigna el idioma a los cursos que ya existen, adivinando por el
--    nombre (solo llena los que están vacíos; no cambia nada más).
--
-- No borra datos. Antes de correrlo en TiDB: php tools/respaldar_bd.php
-- Si se corre dos veces, el paso 1 da "Duplicate column": se puede ignorar.
-- =============================================================

-- ---------- 1. Columna, índice y llave foránea ----------
ALTER TABLE cursos ADD COLUMN idioma_id INT NULL DEFAULT NULL AFTER nivel;

ALTER TABLE cursos ADD KEY fk_curso_idioma (idioma_id);

ALTER TABLE cursos ADD CONSTRAINT fk_curso_idioma
    FOREIGN KEY (idioma_id) REFERENCES idiomas (id)
    ON DELETE SET NULL ON UPDATE CASCADE;

-- ---------- 2. Idioma de los cursos que ya existen ----------
UPDATE cursos SET idioma_id = (SELECT id FROM idiomas WHERE codigo = 'LSC' LIMIT 1)
WHERE idioma_id IS NULL AND (LOWER(nombre) LIKE '%señas%' OR LOWER(nombre) LIKE '%senas%' OR LOWER(nombre) LIKE '%lsc%');

UPDATE cursos SET idioma_id = (SELECT id FROM idiomas WHERE codigo = 'EN' LIMIT 1)
WHERE idioma_id IS NULL AND (LOWER(nombre) LIKE '%ingl%' OR LOWER(nombre) LIKE '%english%');

UPDATE cursos SET idioma_id = (SELECT id FROM idiomas WHERE codigo = 'ES' LIMIT 1)
WHERE idioma_id IS NULL AND (LOWER(nombre) LIKE '%españ%' OR LOWER(nombre) LIKE '%espan%' OR LOWER(nombre) LIKE '%spanish%');
