-- =============================================================
-- BiGlobal · Foto de perfil
-- =============================================================
-- Una foto por usuario (JPG o PNG, máx. 2 MB; el navegador la recorta a
-- un cuadrado de 512 px antes de subirla). Se guarda en la BD porque el
-- disco del plan gratis de Render se borra en cada despliegue.
-- La ven solo usuarios con sesión (src/auth/foto.php).
--
-- Solo crea una tabla NUEVA. Se puede ejecutar más de una vez.
-- =============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS usuario_fotos (
    usuario_id   INT NOT NULL PRIMARY KEY,
    mime         VARCHAR(20) NOT NULL,
    tamano       INT NOT NULL,
    contenido    MEDIUMBLOB NOT NULL,
    actualizada  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_foto_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
