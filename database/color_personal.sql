-- =============================================================
-- BiGlobal · Color de acento personal de cada usuario
-- =============================================================
-- usuarios.color_acento: color en formato #RRGGBB elegido en "Mi perfil".
-- NULL = usa el color del sitio (Configuración > Apariencia).
-- Solo agrega una columna vacía: no toca datos.
-- (Si ya existe, da "Duplicate column": se puede ignorar.)
-- =============================================================

ALTER TABLE usuarios ADD COLUMN color_acento VARCHAR(7) NULL DEFAULT NULL AFTER tema;
