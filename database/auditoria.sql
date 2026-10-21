-- =============================================================
-- BiGlobal · Registro de auditoría
-- =============================================================
-- Guarda quién cambió roles o estados, quién borró cuentas o
-- contenido y quién tocó la configuración global.
-- Lo ve solo el super administrador (Super admin → Auditoría) y
-- desde la app no se puede editar ni borrar.
--
-- A propósito NO tiene llaves foráneas: si se borra la cuenta de
-- quien hizo el cambio (o la cuenta afectada), el registro se queda.
-- Por eso guarda también el nombre en texto.
--
-- Solo crea una tabla NUEVA: no toca datos existentes.
-- Se puede ejecutar más de una vez (CREATE TABLE IF NOT EXISTS).
-- Antes de correrlo en TiDB: php tools/respaldar_bd.php
-- =============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS auditoria (
    id             BIGINT AUTO_INCREMENT PRIMARY KEY,
    actor_id       INT NULL,                 -- quién lo hizo (id de usuarios, sin FK)
    actor_nombre   VARCHAR(200) NOT NULL DEFAULT '',
    actor_rol      VARCHAR(30)  NOT NULL DEFAULT '',
    accion         VARCHAR(50)  NOT NULL,    -- 'cuenta.rol', 'curso.eliminar', 'config.cambiar'...
    objeto_tipo    VARCHAR(30)  NOT NULL DEFAULT '',   -- 'cuenta', 'curso', 'leccion'...
    objeto_id      INT NULL,
    objeto_nombre  VARCHAR(255) NOT NULL DEFAULT '',
    antes          VARCHAR(255) NULL,
    despues        VARCHAR(255) NULL,
    ip             VARCHAR(45)  NOT NULL DEFAULT '',
    fecha          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_auditoria_fecha (fecha),
    KEY idx_auditoria_accion (accion, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
