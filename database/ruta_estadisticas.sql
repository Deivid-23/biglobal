-- =============================================================
-- BiGlobal · Estadísticas de la ruta (Admin → Ruta → Estadísticas)
-- =============================================================
-- ruta_intentos   : cada vez que un estudiante abre una lección de la ruta.
--                   terminado/porcentaje se llenan si la termina.
-- ruta_respuestas : cada respuesta de ese intento: solo si acertó y si era
--                   la primera vez que veía ese ejercicio en el intento.
--                   NO se guarda lo que escribió.
--
-- El examen de ubicación no se guarda aquí. Las estadísticas solo muestran
-- totales (nunca estudiantes por nombre). Al eliminar una cuenta se borran
-- sus intentos y, en cascada, sus respuestas.
-- Reglas en app/Models/RutaEstadisticasModel.php.
--
-- Solo crea tablas NUEVAS. Se puede ejecutar más de una vez.
-- =============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS ruta_intentos (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT NOT NULL,
    leccion_id  INT NOT NULL,
    iniciado    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    terminado   DATETIME NULL,
    porcentaje  TINYINT NULL,
    KEY idx_rintento_leccion (leccion_id, iniciado),
    KEY idx_rintento_usuario (usuario_id, iniciado),
    CONSTRAINT fk_rintento_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_rintento_leccion FOREIGN KEY (leccion_id) REFERENCES ruta_lecciones (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ruta_respuestas (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    intento_id    INT NOT NULL,
    ejercicio_id  INT NOT NULL,
    correcto      TINYINT(1) NOT NULL,
    primera       TINYINT(1) NOT NULL,
    creado        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_rrespuesta_ejercicio (ejercicio_id, creado),
    KEY idx_rrespuesta_intento (intento_id, id),
    CONSTRAINT fk_rrespuesta_intento FOREIGN KEY (intento_id) REFERENCES ruta_intentos (id) ON DELETE CASCADE,
    CONSTRAINT fk_rrespuesta_ejercicio FOREIGN KEY (ejercicio_id) REFERENCES ruta_ejercicios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
