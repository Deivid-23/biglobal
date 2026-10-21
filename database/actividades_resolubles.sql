-- =============================================================
-- BiGlobal · Actividades que se pueden resolver (opción múltiple)
-- =============================================================
-- Es la parte 3 de propuesta_siguiente_version.sql, con una columna
-- más en los intentos (aprobado).
--
-- Solo las actividades de tipo Quiz, Ejercicio o Examen llevan
-- preguntas (eso lo valida el código, no la BD).
-- Para completar una lección hay que aprobar (>= 70 %) cada actividad
-- de la lección que tenga preguntas.
--
-- Solo crea tablas NUEVAS: no toca datos existentes.
-- Se puede ejecutar más de una vez (CREATE TABLE IF NOT EXISTS).
-- Antes de correrlo en TiDB: php tools/respaldar_bd.php
-- =============================================================

SET NAMES utf8mb4;

-- Preguntas de cada actividad
CREATE TABLE IF NOT EXISTS actividad_preguntas (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    actividad_id  INT NOT NULL,
    enunciado     TEXT NOT NULL,
    orden         INT NOT NULL DEFAULT 1,
    KEY idx_pregunta_actividad (actividad_id, orden),
    CONSTRAINT fk_pregunta_actividad FOREIGN KEY (actividad_id)
        REFERENCES actividades (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Opciones de cada pregunta (exactamente una es la correcta)
CREATE TABLE IF NOT EXISTS actividad_opciones (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    pregunta_id   INT NOT NULL,
    texto         VARCHAR(255) NOT NULL,
    es_correcta   TINYINT(1) NOT NULL DEFAULT 0,
    orden         INT NOT NULL DEFAULT 1,
    KEY idx_opcion_pregunta (pregunta_id),
    CONSTRAINT fk_opcion_pregunta FOREIGN KEY (pregunta_id)
        REFERENCES actividad_preguntas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cada vez que un estudiante envía una actividad
CREATE TABLE IF NOT EXISTS actividad_intentos (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id    INT NOT NULL,
    actividad_id  INT NOT NULL,
    aciertos      INT NOT NULL DEFAULT 0,
    total         INT NOT NULL DEFAULT 0,
    aprobado      TINYINT(1) NOT NULL DEFAULT 0,
    fecha         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_intento_usuario (usuario_id, actividad_id),
    KEY idx_intento_actividad (actividad_id),
    CONSTRAINT fk_intento_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_intento_actividad FOREIGN KEY (actividad_id)
        REFERENCES actividades (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
