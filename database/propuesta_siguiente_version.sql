-- =============================================================
-- BiGlobal · PROPUESTA para la siguiente versión (NO EJECUTAR AÚN)
-- =============================================================
-- Estas tablas respaldan las funciones que quedaron en el plan:
-- gamificación, página principal desde la BD, LSC, boletín, contacto
-- y actividades que se puedan resolver. Revísalas con el equipo
-- (el módulo de profesor también tendría que usarlas) antes de ejecutar.
-- Todas son tablas o columnas NUEVAS: no tocan datos existentes.
-- =============================================================

-- ---------- 1. Idioma de cada curso (y LSC) ----------
INSERT INTO idiomas (nombre, codigo, estado)
SELECT 'Lengua de Señas Colombiana', 'LSC', 1
WHERE NOT EXISTS (SELECT 1 FROM idiomas WHERE codigo = 'LSC');

ALTER TABLE cursos
    ADD COLUMN idioma_id INT NULL AFTER nivel,
    ADD KEY fk_curso_idioma (idioma_id),
    ADD CONSTRAINT fk_curso_idioma FOREIGN KEY (idioma_id)
        REFERENCES idiomas (id) ON DELETE SET NULL ON UPDATE CASCADE;

-- ---------- 2. Gamificación del estudiante ----------
-- XP total, racha y última fecha con actividad (una fila por estudiante)
CREATE TABLE estudiante_stats (
    usuario_id      INT NOT NULL PRIMARY KEY,
    xp_total        INT NOT NULL DEFAULT 0,
    racha_actual    INT NOT NULL DEFAULT 0,
    racha_maxima    INT NOT NULL DEFAULT 0,
    ultima_actividad DATE NULL,
    CONSTRAINT fk_stats_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Historial de XP (de dónde salió cada punto)
CREATE TABLE xp_movimientos (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT NOT NULL,
    puntos      INT NOT NULL,
    motivo      VARCHAR(50) NOT NULL,          -- 'leccion', 'actividad', 'racha'
    referencia_id INT NULL,                    -- id de la lección/actividad
    fecha       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_xp_usuario_fecha (usuario_id, fecha),
    CONSTRAINT fk_xp_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Medallas (catálogo) y medallas ganadas
CREATE TABLE medallas (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    codigo      VARCHAR(50) NOT NULL UNIQUE,   -- 'primera_leccion', 'racha_7'
    nombre      VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255) NULL,
    icono       VARCHAR(50) NOT NULL DEFAULT 'fa-medal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usuario_medallas (
    usuario_id  INT NOT NULL,
    medalla_id  INT NOT NULL,
    fecha       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (usuario_id, medalla_id),
    CONSTRAINT fk_um_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_um_medalla FOREIGN KEY (medalla_id) REFERENCES medallas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO medallas (codigo, nombre, descripcion, icono) VALUES
    ('primera_leccion', 'Primer paso',      'Completaste tu primera lección', 'fa-shoe-prints'),
    ('racha_3',         'Constancia',       '3 días seguidos aprendiendo',    'fa-fire'),
    ('racha_7',         'Semana perfecta',  '7 días seguidos aprendiendo',    'fa-fire-flame-curved'),
    ('primer_curso',    'Curso terminado',  'Completaste tu primer curso',    'fa-award');

-- ---------- 3. Actividades que se pueden resolver (quiz) ----------
CREATE TABLE actividad_preguntas (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    actividad_id  INT NOT NULL,
    enunciado     TEXT NOT NULL,
    orden         INT NOT NULL DEFAULT 1,
    CONSTRAINT fk_pregunta_actividad FOREIGN KEY (actividad_id)
        REFERENCES actividades (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE actividad_opciones (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    pregunta_id   INT NOT NULL,
    texto         VARCHAR(255) NOT NULL,
    es_correcta   TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_opcion_pregunta FOREIGN KEY (pregunta_id)
        REFERENCES actividad_preguntas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE actividad_intentos (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id    INT NOT NULL,
    actividad_id  INT NOT NULL,
    aciertos      INT NOT NULL DEFAULT 0,
    total         INT NOT NULL DEFAULT 0,
    fecha         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_intento_usuario (usuario_id, actividad_id),
    CONSTRAINT fk_intento_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_intento_actividad FOREIGN KEY (actividad_id) REFERENCES actividades (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 4. Boletín y contacto de la página principal ----------
CREATE TABLE boletin_suscriptores (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    correo      VARCHAR(150) NOT NULL UNIQUE,
    confirmado  TINYINT(1) NOT NULL DEFAULT 0,
    fecha       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mensajes_contacto (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(150) NOT NULL,
    correo      VARCHAR(150) NOT NULL,
    mensaje     TEXT NOT NULL,
    atendido    TINYINT(1) NOT NULL DEFAULT 0,
    fecha       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 5. Testimonios reales (solo los aprobados se muestran) ----------
CREATE TABLE testimonios (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT NULL,
    nombre      VARCHAR(150) NOT NULL,
    texto       VARCHAR(500) NOT NULL,
    aprobado    TINYINT(1) NOT NULL DEFAULT 0,
    fecha       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_testimonio_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
