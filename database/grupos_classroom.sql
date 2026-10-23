-- =============================================================
-- BiGlobal · Grupos tipo Classroom (requerimiento 9, parte A)
-- =============================================================
-- grupos               : los crea un docente; los estudiantes entran con
--                        un código de 7 caracteres.
-- grupo_miembros       : estudiantes de cada grupo.
-- grupo_anuncios       : tablón del grupo (solo escribe el docente).
-- anuncio_comentarios  : comentarios simples de docente y estudiantes.
-- grupo_material       : material de apoyo (archivo, enlace o video).
-- archivos             : archivos subidos (PDF, JPG, PNG, MP3, M4A; máx.
--                        5 MB). Se guardan en la BD porque el disco del
--                        plan gratis de Render se borra en cada despliegue.
--                        contexto: 'material' | 'tarea' | 'entrega'.
--
-- Solo crea tablas NUEVAS. Se puede ejecutar más de una vez.
-- =============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS grupos (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    docente_id   INT NOT NULL,
    nombre       VARCHAR(120) NOT NULL,
    descripcion  VARCHAR(1000) NULL,
    curso_id     INT NULL,                          -- curso relacionado (opcional)
    codigo       CHAR(7) NOT NULL,
    archivado    TINYINT(1) NOT NULL DEFAULT 0,
    creado       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_grupo_codigo (codigo),
    KEY idx_grupo_docente (docente_id),
    CONSTRAINT fk_grupo_docente FOREIGN KEY (docente_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_grupo_curso FOREIGN KEY (curso_id) REFERENCES cursos (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS grupo_miembros (
    grupo_id     INT NOT NULL,
    usuario_id   INT NOT NULL,
    unido_en     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (grupo_id, usuario_id),
    KEY idx_miembro_usuario (usuario_id),
    CONSTRAINT fk_miembro_grupo FOREIGN KEY (grupo_id) REFERENCES grupos (id) ON DELETE CASCADE,
    CONSTRAINT fk_miembro_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS grupo_anuncios (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    grupo_id     INT NOT NULL,
    autor_id     INT NOT NULL,
    texto        TEXT NOT NULL,
    creado       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_anuncio_grupo (grupo_id, creado),
    CONSTRAINT fk_anuncio_grupo FOREIGN KEY (grupo_id) REFERENCES grupos (id) ON DELETE CASCADE,
    CONSTRAINT fk_anuncio_autor FOREIGN KEY (autor_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS anuncio_comentarios (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    anuncio_id   INT NOT NULL,
    autor_id     INT NOT NULL,
    texto        VARCHAR(1000) NOT NULL,
    creado       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_comentario_anuncio (anuncio_id, creado),
    CONSTRAINT fk_comentario_anuncio FOREIGN KEY (anuncio_id) REFERENCES grupo_anuncios (id) ON DELETE CASCADE,
    CONSTRAINT fk_comentario_autor FOREIGN KEY (autor_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS archivos (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    dueno_id     INT NOT NULL,
    contexto     VARCHAR(20) NOT NULL,              -- 'material' | 'tarea' | 'entrega'
    contexto_id  INT NOT NULL,
    nombre       VARCHAR(150) NOT NULL,             -- ya limpio
    mime         VARCHAR(40) NOT NULL,
    tamano       INT NOT NULL,                      -- bytes
    sha256       CHAR(64) NOT NULL,
    contenido    MEDIUMBLOB NOT NULL,
    creado       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_archivo_contexto (contexto, contexto_id),
    CONSTRAINT fk_archivo_dueno FOREIGN KEY (dueno_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS grupo_material (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    grupo_id     INT NOT NULL,
    tipo         VARCHAR(10) NOT NULL,              -- 'archivo' | 'enlace' | 'video'
    titulo       VARCHAR(150) NOT NULL,
    descripcion  VARCHAR(1000) NULL,
    url          VARCHAR(500) NULL,                 -- enlace o video (YouTube / Vimeo)
    archivo_id   INT NULL,
    creado       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_material_grupo (grupo_id, creado),
    CONSTRAINT fk_material_grupo FOREIGN KEY (grupo_id) REFERENCES grupos (id) ON DELETE CASCADE,
    CONSTRAINT fk_material_archivo FOREIGN KEY (archivo_id) REFERENCES archivos (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
