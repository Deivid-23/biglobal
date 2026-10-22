-- =============================================================
-- BiGlobal · Clases en vivo
-- =============================================================
-- clases_vivo          : la clase (enlace externo de Meet, Zoom...).
-- clase_inscripciones  : quién va (inscrito) y la lista de espera.
--
-- Reglas (app/Models/ClaseModel.php):
--   - Cupo máximo 30. Si se llena, los siguientes quedan en "espera".
--   - Si un inscrito se retira, entra el primero de la espera (por orden
--     de llegada = id más bajo) y se le avisa.
--   - Premium: todas las clases. Gratuito: solo las "abiertas para todos".
--   - El enlace solo lo ve un inscrito, 15 minutos antes del inicio.
--
-- Solo crea tablas NUEVAS. Se puede ejecutar más de una vez.
-- =============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS clases_vivo (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    docente_id          INT NOT NULL,
    idioma_id           INT NULL,
    titulo              VARCHAR(150) NOT NULL,
    descripcion         TEXT NULL,
    inicio              DATETIME NOT NULL,              -- hora de Colombia
    duracion_min        INT NOT NULL DEFAULT 60,
    enlace              VARCHAR(500) NOT NULL,          -- Meet, Zoom, Teams... (solo https)
    cupo                INT NOT NULL DEFAULT 30,        -- máximo 30
    abierta_para_todos  TINYINT(1) NOT NULL DEFAULT 0,  -- 1 = también para el plan Gratuito (req. 4)
    estado              VARCHAR(20) NOT NULL DEFAULT 'programada',   -- 'programada' | 'cancelada'
    motivo_cancelacion  VARCHAR(255) NULL,
    creada_en           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizada_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_clase_inicio (estado, inicio),
    KEY idx_clase_docente (docente_id, inicio),
    CONSTRAINT fk_clase_docente FOREIGN KEY (docente_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_clase_idioma FOREIGN KEY (idioma_id) REFERENCES idiomas (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS clase_inscripciones (
    id           INT AUTO_INCREMENT PRIMARY KEY,          -- el orden de llegada para la lista de espera
    clase_id     INT NOT NULL,
    usuario_id   INT NOT NULL,
    estado       VARCHAR(20) NOT NULL,                     -- 'inscrito' | 'espera' | 'retirado'
    creada_en    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_inscripcion (clase_id, usuario_id),
    KEY idx_inscripcion_estado (clase_id, estado, id),
    KEY idx_inscripcion_usuario (usuario_id, estado),
    CONSTRAINT fk_inscripcion_clase FOREIGN KEY (clase_id) REFERENCES clases_vivo (id) ON DELETE CASCADE,
    CONSTRAINT fk_inscripcion_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
