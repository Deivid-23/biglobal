-- =============================================================
-- BiGlobal · Gamificación del estudiante (racha, XP, medallas)
-- =============================================================
-- Es solo la parte 2 de propuesta_siguiente_version.sql.
-- Se puede ejecutar más de una vez sin dañar nada:
-- crea las tablas solo si no existen y no repite medallas.
-- Solo crea tablas NUEVAS: no toca datos existentes.
-- Ejecútalo en local y en TiDB.
-- =============================================================

-- Para que las tildes y la ñ se guarden bien aunque se ejecute
-- desde la consola de Windows
SET NAMES utf8mb4;

-- XP total, racha y última fecha con actividad (una fila por estudiante)
CREATE TABLE IF NOT EXISTS estudiante_stats (
    usuario_id       INT NOT NULL PRIMARY KEY,
    xp_total         INT NOT NULL DEFAULT 0,
    racha_actual     INT NOT NULL DEFAULT 0,
    racha_maxima     INT NOT NULL DEFAULT 0,
    ultima_actividad DATE NULL,
    CONSTRAINT fk_stats_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Historial de XP (de dónde salió cada punto)
CREATE TABLE IF NOT EXISTS xp_movimientos (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id    INT NOT NULL,
    puntos        INT NOT NULL,
    motivo        VARCHAR(50) NOT NULL,          -- 'leccion', 'racha', 'curso'
    referencia_id INT NULL,                      -- id de la lección/curso
    fecha         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_xp_usuario_fecha (usuario_id, fecha),
    CONSTRAINT fk_xp_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Catálogo de medallas
CREATE TABLE IF NOT EXISTS medallas (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    codigo      VARCHAR(50) NOT NULL UNIQUE,     -- 'primera_leccion', 'racha_7'
    nombre      VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255) NULL,
    icono       VARCHAR(50) NOT NULL DEFAULT 'fa-medal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Medallas que ganó cada estudiante
CREATE TABLE IF NOT EXISTS usuario_medallas (
    usuario_id  INT NOT NULL,
    medalla_id  INT NOT NULL,
    fecha       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (usuario_id, medalla_id),
    CONSTRAINT fk_um_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_um_medalla FOREIGN KEY (medalla_id) REFERENCES medallas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Medallas iniciales (INSERT IGNORE: si ya existen, no se repiten)
INSERT IGNORE INTO medallas (codigo, nombre, descripcion, icono) VALUES
    ('primera_leccion', 'Primer paso',     'Completaste tu primera lección', 'fa-shoe-prints'),
    ('racha_3',         'Constancia',      '3 días seguidos aprendiendo',    'fa-fire'),
    ('racha_7',         'Semana perfecta', '7 días seguidos aprendiendo',    'fa-fire-flame-curved'),
    ('primer_curso',    'Curso terminado', 'Completaste tu primer curso',    'fa-award');
