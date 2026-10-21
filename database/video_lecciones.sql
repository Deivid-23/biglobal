-- =============================================================
-- BiGlobal · Video en las lecciones (YouTube o Vimeo)
-- =============================================================
-- Columna NUEVA lecciones.video_url (puede quedar vacía).
-- Guarda el enlace ya normalizado (lo hace app/Helpers/Video.php):
--   https://www.youtube.com/watch?v=XXXXXXXXXXX
--   https://vimeo.com/123456789
-- Sirve sobre todo para LSC (Lengua de Señas), que se aprende viendo.
--
-- No toca datos existentes. Si se corre dos veces da "Duplicate column":
-- se puede ignorar.
-- =============================================================

ALTER TABLE lecciones ADD COLUMN video_url VARCHAR(255) NULL DEFAULT NULL AFTER contenido;
