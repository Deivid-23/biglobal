-- =============================================================
-- BiGlobal · Invitaciones a clases gratuitas para todos
-- =============================================================
-- Cuando un docente o un admin marca una clase en vivo como
-- "gratuita para todos" (clases_vivo.abierta_para_todos = 1), cada
-- estudiante activo del plan Gratuito recibe UNA invitación (campana +
-- correo) con los botones Aceptar y Rechazar.
-- Aceptar = inscribirse (sujeto al cupo y a la lista de espera).
--
-- Solo crea una tabla NUEVA. Se puede ejecutar más de una vez.
-- =============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS clase_invitaciones (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    clase_id      INT NOT NULL,
    usuario_id    INT NOT NULL,
    estado        VARCHAR(20) NOT NULL DEFAULT 'pendiente',   -- 'pendiente' | 'aceptada' | 'rechazada'
    creada_en     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    respondida_en DATETIME NULL,
    UNIQUE KEY uq_invitacion (clase_id, usuario_id),
    KEY idx_invitacion_usuario (usuario_id, estado),
    CONSTRAINT fk_invitacion_clase FOREIGN KEY (clase_id) REFERENCES clases_vivo (id) ON DELETE CASCADE,
    CONSTRAINT fk_invitacion_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
