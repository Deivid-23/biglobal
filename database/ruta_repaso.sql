-- =============================================================
-- BiGlobal · Repaso de errores de la ruta
-- =============================================================
-- ruta_repaso : una fila por estudiante y ejercicio que falló en una
--               lección de la ruta.
--   fallado_en  : la última vez que lo falló (en una lección o en el repaso).
--   resuelto_en : cuándo lo acertó en el repaso (NULL = sigue pendiente).
--                 Si lo vuelve a fallar en una lección, vuelve a NULL.
--
-- Reglas en app/Models/RepasoModel.php. El examen de ubicación no cuenta.
-- Al eliminar una cuenta se borra (cascada y CuentaEliminacionModel).
-- Solo crea una tabla NUEVA. Se puede ejecutar más de una vez.
-- =============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS ruta_repaso (
    usuario_id    INT NOT NULL,
    ejercicio_id  INT NOT NULL,
    fallado_en    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resuelto_en   DATETIME NULL,
    PRIMARY KEY (usuario_id, ejercicio_id),
    KEY idx_repaso_pendiente (usuario_id, resuelto_en, fallado_en),
    CONSTRAINT fk_repaso_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_repaso_ejercicio FOREIGN KEY (ejercicio_id) REFERENCES ruta_ejercicios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
