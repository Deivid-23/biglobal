-- =============================================================
-- BiGlobal · Tareas de los grupos (requerimiento 9, parte B)
-- =============================================================
-- grupo_tareas    : tareas que publica el docente en un grupo, con fecha
--                   de entrega opcional (hora de Colombia) y puntaje máximo.
-- tarea_entregas  : UNA fila por estudiante y tarea. Se puede reenviar
--                   hasta que se califica; cada envío vuelve a calcular
--                   si llegó tarde (NOW() > fecha_entrega).
--
-- Los adjuntos van a la tabla archivos (database/grupos_classroom.sql):
--   contexto 'tarea'   -> contexto_id = grupo_tareas.id
--   contexto 'entrega' -> contexto_id = tarea_entregas.id
--
-- Solo crea tablas NUEVAS. Se puede ejecutar más de una vez.
-- Requiere antes database/grupos_classroom.sql.
-- =============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS grupo_tareas (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    grupo_id       INT NOT NULL,
    docente_id     INT NOT NULL,
    titulo         VARCHAR(150) NOT NULL,
    instrucciones  TEXT NULL,
    fecha_entrega  DATETIME NULL,                   -- NULL = sin fecha límite
    puntaje_max    SMALLINT NOT NULL DEFAULT 100,
    creado         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    editado        DATETIME NULL,
    KEY idx_tarea_grupo (grupo_id, creado),
    CONSTRAINT fk_tarea_grupo FOREIGN KEY (grupo_id) REFERENCES grupos (id) ON DELETE CASCADE,
    CONSTRAINT fk_tarea_docente FOREIGN KEY (docente_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tarea_entregas (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    tarea_id            INT NOT NULL,
    estudiante_id       INT NOT NULL,
    texto               TEXT NULL,
    enviada_en          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tardia              TINYINT(1) NOT NULL DEFAULT 0,
    envios              INT NOT NULL DEFAULT 1,
    puntaje             DECIMAL(6,2) NULL,              -- NULL = sin calificar
    comentario_docente  VARCHAR(2000) NULL,
    calificada_en       DATETIME NULL,
    calificada_por      INT NULL,
    UNIQUE KEY uq_entrega (tarea_id, estudiante_id),
    KEY idx_entrega_estudiante (estudiante_id),
    CONSTRAINT fk_entrega_tarea FOREIGN KEY (tarea_id) REFERENCES grupo_tareas (id) ON DELETE CASCADE,
    CONSTRAINT fk_entrega_estudiante FOREIGN KEY (estudiante_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_entrega_calificador FOREIGN KEY (calificada_por) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
