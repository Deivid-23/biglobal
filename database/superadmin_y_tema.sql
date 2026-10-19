-- =============================================================
-- BiGlobal · Rol super administrador + tema guardado en la cuenta
-- =============================================================
-- Se puede ejecutar más de una vez sin dañar nada.
-- No borra datos. Antes: php tools/respaldar_bd.php
-- =============================================================

SET NAMES utf8mb4;

-- 1) Rol nuevo: superadmin (id 4)
INSERT INTO roles (id, nombre)
SELECT 4, 'superadmin'
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE nombre = 'superadmin');

-- 2) Primer super administrador: el admin activo más antiguo,
--    SOLO si todavía no existe ningún super administrador.
UPDATE usuarios
SET rol_id = (SELECT id FROM (SELECT id FROM roles WHERE nombre = 'superadmin') r)
WHERE id = (
        SELECT id FROM (
            SELECT MIN(u.id) AS id
            FROM usuarios u
            INNER JOIN roles ro ON ro.id = u.rol_id
            WHERE ro.nombre = 'admin' AND u.activo = 1
        ) primero
    )
  AND NOT EXISTS (
        SELECT 1 FROM (
            SELECT u2.id FROM usuarios u2
            INNER JOIN roles ro2 ON ro2.id = u2.rol_id
            WHERE ro2.nombre = 'superadmin'
        ) ya_hay
    );

-- 3) Tema de cada usuario: 'claro', 'oscuro' o NULL (= el del sistema)
--    (Si la columna ya existe, esta línea da error "Duplicate column":
--     es normal y se puede ignorar.)
ALTER TABLE usuarios ADD COLUMN tema VARCHAR(10) NULL DEFAULT NULL AFTER activo;
