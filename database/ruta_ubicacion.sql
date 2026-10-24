-- =============================================================
-- BiGlobal · Examen de ubicación de la ruta
-- =============================================================
-- ruta_ubicacion : una fila por estudiante e idioma.
--   nivel_codigo   : nivel donde quedó ubicado (el mejor resultado; NULL
--                    si todavía no ha terminado ningún examen). Ese nivel
--                    y los anteriores quedan abiertos en su ruta.
--   detalle        : aciertos por nivel del mejor resultado, de los que
--                    respondió ("A1 6/6 · A2 5/6 · B1 1/4").
--   ultimo_intento : cuándo empezó el último examen (hora de Colombia).
--                    Se puede hacer una vez al día por idioma.
--
-- Reglas en app/Models/UbicacionModel.php. No da XP ni insignias.
-- Solo crea una tabla NUEVA. Se puede ejecutar más de una vez.
-- =============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS ruta_ubicacion (
    usuario_id      INT NOT NULL,
    idioma_id       INT NOT NULL,
    nivel_codigo    CHAR(2) NULL,
    detalle         VARCHAR(255) NULL,
    ubicado_en      DATETIME NULL,
    ultimo_intento  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (usuario_id, idioma_id),
    CONSTRAINT fk_ubicacion_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_ubicacion_idioma FOREIGN KEY (idioma_id) REFERENCES idiomas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
