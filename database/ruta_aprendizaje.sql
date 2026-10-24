-- =============================================================
-- BiGlobal · Ruta de aprendizaje por idioma (requerimiento 10)
-- =============================================================
-- Contenido de la PLATAFORMA (lo administra el equipo admin), aparte de
-- los cursos que crean los docentes.
--
--   ruta_niveles     : A1–C1 de cada idioma (en LSC, su equivalente).
--                      Toda la ruta es gratis (database/acceso_gratis_y_pago.sql).
--   ruta_unidades    : unidades de cada nivel.
--   ruta_lecciones   : lecciones de cada unidad, con espacio para el video
--                      de LSC (YouTube o Vimeo).
--   ruta_ejercicios  : ejercicios interactivos. "datos" es JSON (se valida
--                      en PHP, RutaModel::datosEjercicio).
--                      tipos: opcion, completar, ordenar, emparejar, vf,
--                             sena (ver la seña; exige video), escuchar.
--   ruta_progreso    : lecciones completadas por cada estudiante.
--
-- "clave" (única) permite que el contenido inicial se cargue con
-- INSERT IGNORE sin duplicar nada (database/ruta_a1_contenido.sql).
--
-- Solo crea tablas NUEVAS y los niveles. Se puede ejecutar más de una vez.
-- =============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS ruta_niveles (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    idioma_id    INT NOT NULL,
    codigo       CHAR(2) NOT NULL,                  -- A1, A2, B1, B2, C1
    nombre       VARCHAR(60) NOT NULL,
    descripcion  VARCHAR(255) NULL,
    orden        TINYINT NOT NULL,
    publicado    TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY uq_nivel (idioma_id, codigo),
    CONSTRAINT fk_nivel_idioma FOREIGN KEY (idioma_id) REFERENCES idiomas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ruta_unidades (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    nivel_id     INT NOT NULL,
    clave        VARCHAR(60) NOT NULL,
    titulo       VARCHAR(120) NOT NULL,
    descripcion  VARCHAR(255) NULL,
    icono        VARCHAR(40) NOT NULL DEFAULT 'fa-star',
    orden        INT NOT NULL DEFAULT 1,
    publicado    TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY uq_unidad_clave (clave),
    KEY idx_unidad_nivel (nivel_id, orden),
    CONSTRAINT fk_unidad_nivel FOREIGN KEY (nivel_id) REFERENCES ruta_niveles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ruta_lecciones (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    unidad_id    INT NOT NULL,
    clave        VARCHAR(60) NOT NULL,
    titulo       VARCHAR(120) NOT NULL,
    descripcion  VARCHAR(255) NULL,
    video_url    VARCHAR(255) NULL,                 -- video de la lección (sobre todo LSC)
    orden        INT NOT NULL DEFAULT 1,
    xp           SMALLINT NOT NULL DEFAULT 10,
    publicado    TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY uq_leccion_clave (clave),
    KEY idx_leccion_unidad (unidad_id, orden),
    CONSTRAINT fk_rleccion_unidad FOREIGN KEY (unidad_id) REFERENCES ruta_unidades (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ruta_ejercicios (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    leccion_id   INT NOT NULL,
    clave        VARCHAR(60) NOT NULL,
    tipo         VARCHAR(20) NOT NULL,
    enunciado    VARCHAR(500) NOT NULL,
    datos        TEXT NOT NULL,                     -- JSON
    video_url    VARCHAR(255) NULL,                 -- obligatorio en tipo 'sena'
    orden        INT NOT NULL DEFAULT 1,
    activo       TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_ejercicio_clave (clave),
    KEY idx_ejercicio_leccion (leccion_id, orden),
    CONSTRAINT fk_ejercicio_leccion FOREIGN KEY (leccion_id) REFERENCES ruta_lecciones (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ruta_progreso (
    usuario_id        INT NOT NULL,
    leccion_id        INT NOT NULL,
    mejor_porcentaje  TINYINT NOT NULL DEFAULT 0,   -- aciertos a la primera (%)
    intentos          INT NOT NULL DEFAULT 1,
    completada_en     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultima_vez        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (usuario_id, leccion_id),
    KEY idx_progreso_leccion (leccion_id),
    CONSTRAINT fk_rprogreso_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_rprogreso_leccion FOREIGN KEY (leccion_id) REFERENCES ruta_lecciones (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Niveles de cada idioma (A1 publicado; el resto en borrador) ----------
INSERT IGNORE INTO ruta_niveles (idioma_id, codigo, nombre, descripcion, orden, publicado)
SELECT i.id, n.codigo, n.nombre, n.descripcion, n.orden, n.publicado
FROM idiomas i
INNER JOIN (
    SELECT 'A1' AS codigo, 'Inicial' AS nombre, 'Saludos, números, la familia y la vida diaria.' AS descripcion, 1 AS orden, 1 AS publicado
    UNION ALL SELECT 'A2', 'Básico', 'Conversaciones sencillas sobre temas cotidianos.', 2, 0
    UNION ALL SELECT 'B1', 'Intermedio', 'Viajes, trabajo y experiencias propias.', 3, 0
    UNION ALL SELECT 'B2', 'Intermedio alto', 'Opiniones, debates y textos más largos.', 4, 0
    UNION ALL SELECT 'C1', 'Avanzado', 'Lenguaje fluido y preciso en cualquier contexto.', 5, 0
) n
WHERE i.codigo IN ('EN', 'ES', 'LSC');
