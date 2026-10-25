-- =============================================================
-- BiGlobal · Contrarreloj ("Práctica rápida" de la ruta)
-- =============================================================
-- 60 segundos para responder la mayor cantidad de ejercicios de las
-- lecciones que el estudiante ya completó. Da XP de práctica una vez al
-- día (como el repaso). Aquí se guarda la mejor marca por idioma.
--
-- ruta_contrarreloj : una fila por estudiante e idioma.
--   mejor     : más aciertos en una partida.
--   mejor_en  : cuándo hizo esa marca.
--   partidas  : cuántas ha jugado.
--
-- Reglas en app/Models/ContrarrelojModel.php. Al eliminar una cuenta se
-- borra (cascada y CuentaEliminacionModel).
-- Solo crea una tabla NUEVA. Se puede ejecutar más de una vez.
-- =============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS ruta_contrarreloj (
    usuario_id  INT NOT NULL,
    idioma_id   INT NOT NULL,
    mejor       SMALLINT NOT NULL DEFAULT 0,
    mejor_en    DATETIME NULL,
    partidas    INT NOT NULL DEFAULT 0,
    PRIMARY KEY (usuario_id, idioma_id),
    CONSTRAINT fk_contrarreloj_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_contrarreloj_idioma FOREIGN KEY (idioma_id) REFERENCES idiomas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
