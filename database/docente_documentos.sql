-- =============================================================
-- BiGlobal · Registro de docente con diplomas o certificados
-- =============================================================
-- docente_solicitudes : una por docente. Idioma que enseñará, estado de la
--                       revisión y motivo si se rechaza.
-- docente_documentos  : los archivos (PDF, JPG o PNG, máx. 5 MB cada uno,
--                       hasta 5). Se guardan en la BD porque el disco del
--                       plan gratis de Render se borra en cada despliegue.
--
-- Solo los abren el admin, el super admin y el propio docente
-- (src/admin/documento.php y src/profesor/documento.php).
--
-- Solo crea tablas NUEVAS. Se puede ejecutar más de una vez.
-- =============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS docente_solicitudes (
    usuario_id     INT NOT NULL PRIMARY KEY,
    idioma_id      INT NULL,                        -- idioma que enseñará
    estado         VARCHAR(20) NOT NULL DEFAULT 'pendiente',   -- 'pendiente' | 'aprobada' | 'rechazada'
    motivo         VARCHAR(500) NULL,               -- obligatorio al rechazar
    revisado_por   INT NULL,
    revisado_en    DATETIME NULL,
    creada_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_solicitud_docente FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_solicitud_idioma FOREIGN KEY (idioma_id) REFERENCES idiomas (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS docente_documentos (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT NOT NULL,
    nombre_original VARCHAR(150) NOT NULL,          -- ya limpio (sin rutas ni caracteres raros)
    tipo_mime       VARCHAR(40) NOT NULL,           -- application/pdf | image/jpeg | image/png
    tamano          INT NOT NULL,                   -- bytes
    sha256          CHAR(64) NOT NULL,
    contenido       MEDIUMBLOB NOT NULL,
    subido_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_documento_usuario (usuario_id),
    CONSTRAINT fk_documento_docente FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
