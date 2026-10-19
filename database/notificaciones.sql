-- =============================================================
-- BiGlobal · Notificaciones (campana del panel + correo)
-- =============================================================
-- 1) Tabla nueva `notificaciones`: una fila por cada aviso que recibe
--    un admin o super admin. leida_en = NULL mientras no la lea.
-- 2) Dos claves nuevas en `configuracion`:
--      notif_docente_pendiente  → aviso cuando un docente pide aprobación
--      resumen_ultimo_envio     → fecha del último resumen semanal
--                                 (para no mandarlo dos veces la misma semana)
--
-- No borra ni cambia datos existentes. Se puede correr más de una vez:
-- CREATE TABLE IF NOT EXISTS e INSERT IGNORE no repiten nada.
-- Antes de correrlo en TiDB: php tools/respaldar_bd.php
-- =============================================================

CREATE TABLE IF NOT EXISTS notificaciones (
    id          INT(11)      NOT NULL AUTO_INCREMENT,
    usuario_id  INT(11)      NOT NULL,            -- quién la recibe
    tipo        VARCHAR(40)  NOT NULL,            -- nuevo_usuario, docente_pendiente, nuevo_curso, resumen_semanal
    titulo      VARCHAR(150) NOT NULL,
    mensaje     VARCHAR(500) NOT NULL DEFAULT '',
    enlace      VARCHAR(255) NULL DEFAULT NULL,   -- ruta relativa dentro de src/ (ej. admin/index.php?page=solicitudes)
    leida_en    DATETIME     NULL DEFAULT NULL,
    creada_en   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,  -- la escribe PHP con hora de Colombia
    PRIMARY KEY (id),
    KEY idx_notif_usuario_leida (usuario_id, leida_en),
    CONSTRAINT fk_notif_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO configuracion (clave, valor, tipo) VALUES
    ('notif_docente_pendiente', '1', 'bool'),
    ('resumen_ultimo_envio', '', 'text');
