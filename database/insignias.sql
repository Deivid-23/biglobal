-- =============================================================
-- BiGlobal · Insignias (requerimiento 11)
-- =============================================================
-- Amplía la tabla medallas (en la página se llaman "Insignias") para que
-- el admin las cree y edite con una condición y una meta. Las 4 medallas
-- de antes y las que ya ganaron los estudiantes se conservan.
--
--   categoria : ruta | constancia | cursos | grupos | clases | comunidad
--   condicion : qué se cuenta (ver InsigniaModel::CONDICIONES)
--   meta      : cuánto hace falta (ej. 10 lecciones)
--   idioma_id, nivel : solo para la condición ruta_nivel (ej. A1 de LSC)
--   activa    : 0 = no se da ni se muestra (quien la tenía la conserva)
--   secreta   : 1 = no se muestra hasta ganarla
--
-- Se puede ejecutar más de una vez.
-- =============================================================

SET NAMES utf8mb4;

ALTER TABLE medallas ADD COLUMN IF NOT EXISTS categoria VARCHAR(20) NOT NULL DEFAULT 'cursos';
ALTER TABLE medallas ADD COLUMN IF NOT EXISTS condicion VARCHAR(30) NULL;
ALTER TABLE medallas ADD COLUMN IF NOT EXISTS meta INT NOT NULL DEFAULT 1;
ALTER TABLE medallas ADD COLUMN IF NOT EXISTS idioma_id INT NULL;
ALTER TABLE medallas ADD COLUMN IF NOT EXISTS nivel CHAR(2) NULL;
ALTER TABLE medallas ADD COLUMN IF NOT EXISTS color VARCHAR(12) NOT NULL DEFAULT 'azul';
ALTER TABLE medallas ADD COLUMN IF NOT EXISTS orden INT NOT NULL DEFAULT 100;
ALTER TABLE medallas ADD COLUMN IF NOT EXISTS activa TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE medallas ADD COLUMN IF NOT EXISTS secreta TINYINT(1) NOT NULL DEFAULT 0;

-- ---------- Las 4 medallas de antes pasan al sistema nuevo ----------
UPDATE medallas SET categoria = 'cursos', condicion = 'curso_lecciones', meta = 1, color = 'azul', orden = 301,
    descripcion = 'Completaste tu primera lección de un curso' WHERE codigo = 'primera_leccion' AND condicion IS NULL;
UPDATE medallas SET categoria = 'constancia', condicion = 'racha', meta = 3, color = 'naranja', orden = 201 WHERE codigo = 'racha_3' AND condicion IS NULL;
UPDATE medallas SET categoria = 'constancia', condicion = 'racha', meta = 7, color = 'naranja', orden = 202 WHERE codigo = 'racha_7' AND condicion IS NULL;
UPDATE medallas SET categoria = 'cursos', condicion = 'cursos_terminados', meta = 1, color = 'verde', orden = 303 WHERE codigo = 'primer_curso' AND condicion IS NULL;

-- ---------- Insignias nuevas ----------
INSERT IGNORE INTO medallas (codigo, nombre, descripcion, icono, categoria, condicion, meta, color, orden) VALUES
    -- Ruta de aprendizaje
    ('ruta_1',          'Primer paso en la ruta', 'Completaste tu primera lección de la ruta',        'fa-route',            'ruta', 'ruta_lecciones', 1,   'azul',    101),
    ('ruta_10',         'En camino',              'Completaste 10 lecciones de la ruta',             'fa-person-walking',   'ruta', 'ruta_lecciones', 10,  'azul',    102),
    ('ruta_25',         'Explorador',             'Completaste 25 lecciones de la ruta',             'fa-compass',          'ruta', 'ruta_lecciones', 25,  'azul',    103),
    ('ruta_50',         'Viajero',                'Completaste 50 lecciones de la ruta',             'fa-map-location-dot', 'ruta', 'ruta_lecciones', 50,  'morado',  104),
    ('ruta_100',        'Leyenda de la ruta',     'Completaste 100 lecciones de la ruta',            'fa-mountain-sun',     'ruta', 'ruta_lecciones', 100, 'morado',  105),
    ('perfecta_1',      'Sin errores',            'Terminaste una lección sin fallar ninguna',       'fa-bullseye',         'ruta', 'ruta_perfectas', 1,   'verde',   106),
    ('perfecta_10',     'Precisión',              'Terminaste 10 lecciones sin fallar ninguna',      'fa-crosshairs',       'ruta', 'ruta_perfectas', 10,  'verde',   107),
    ('poliglota_2',     'Bilingüe',               'Practicaste en 2 idiomas de la ruta',             'fa-language',         'ruta', 'ruta_idiomas',   2,   'rosa',    111),
    ('poliglota_3',     'Políglota',              'Practicaste en los 3 idiomas de la ruta',         'fa-earth-americas',   'ruta', 'ruta_idiomas',   3,   'rosa',    112),
    -- Constancia
    ('racha_14',        'Dos semanas',            '14 días seguidos aprendiendo',                    'fa-fire-flame-simple','constancia', 'racha', 14,   'naranja', 203),
    ('racha_30',        'Un mes de fuego',        '30 días seguidos aprendiendo',                    'fa-calendar-check',   'constancia', 'racha', 30,   'naranja', 204),
    ('racha_100',       'Imparable',              '100 días seguidos aprendiendo',                   'fa-meteor',           'constancia', 'racha', 100,  'rojo',    205),
    ('xp_100',          'Primeros 100 XP',        'Sumaste 100 XP',                                  'fa-star',             'constancia', 'xp',    100,  'ambar',   211),
    ('xp_1000',         'Mil XP',                 'Sumaste 1.000 XP',                                'fa-star-half-stroke', 'constancia', 'xp',    1000, 'ambar',   212),
    ('xp_5000',         'Estrella BiGlobal',      'Sumaste 5.000 XP',                                'fa-crown',            'constancia', 'xp',    5000, 'ambar',   213),
    -- Cursos
    ('curso_10',        'Estudioso',              'Completaste 10 lecciones de cursos',              'fa-book-open-reader', 'cursos', 'curso_lecciones',  10, 'azul',  302),
    ('cursos_3',        'Coleccionista',          'Terminaste 3 cursos',                             'fa-graduation-cap',   'cursos', 'cursos_terminados', 3, 'verde', 304),
    ('actividad_1',     'Primera actividad',      'Aprobaste tu primera actividad',                  'fa-list-check',       'cursos', 'actividades',       1, 'azul',  305),
    -- Grupos
    ('grupo_1',         'En equipo',              'Te uniste a tu primer grupo',                     'fa-people-group',     'grupos', 'grupos',            1,  'azul',   401),
    ('grupo_3',         'Muy conectado',          'Estás en 3 grupos',                               'fa-users',            'grupos', 'grupos',            3,  'azul',   402),
    ('tarea_1',         'Tarea entregada',        'Entregaste tu primera tarea',                     'fa-file-arrow-up',    'grupos', 'tareas_entregadas', 1,  'verde',  403),
    ('tarea_10',        'Cumplido',               'Entregaste 10 tareas',                            'fa-folder-open',      'grupos', 'tareas_entregadas', 10, 'verde',  404),
    ('puntual_5',       'Puntual',                'Entregaste 5 tareas a tiempo',                    'fa-clock',            'grupos', 'tareas_a_tiempo',   5,  'morado', 405),
    ('nota_perfecta',   'Nota perfecta',          'Sacaste el puntaje máximo en una tarea',          'fa-medal',            'grupos', 'tareas_perfectas',  1,  'ambar',  406),
    -- Clases en vivo y comunidad
    ('clase_1',         'Primera clase en vivo',  'Te inscribiste a tu primera clase en vivo',       'fa-video',            'clases', 'clases',     1,  'rojo',  501),
    ('clase_5',         'Siempre presente',       'Te inscribiste a 5 clases en vivo',               'fa-chalkboard-user',  'clases', 'clases',     5,  'rojo',  502),
    ('comentario_1',    'Rompiste el hielo',      'Dejaste tu primer comentario en un grupo',        'fa-comment',          'comunidad', 'comentarios', 1,  'rosa', 601),
    ('comentario_25',   'Conversador',            'Dejaste 25 comentarios en tus grupos',            'fa-comments',         'comunidad', 'comentarios', 25, 'rosa', 602);

-- Terminar el A1 de cada idioma de la ruta
INSERT IGNORE INTO medallas (codigo, nombre, descripcion, icono, categoria, condicion, meta, idioma_id, nivel, color, orden)
SELECT CONCAT('a1_', LOWER(i.codigo)),
       CONCAT('A1 de ', CASE i.codigo WHEN 'EN' THEN 'inglés' WHEN 'ES' THEN 'español' ELSE 'LSC' END),
       CONCAT('Terminaste el nivel A1 de ', CASE i.codigo WHEN 'EN' THEN 'inglés' WHEN 'ES' THEN 'español' ELSE 'Lengua de Señas Colombiana' END),
       CASE i.codigo WHEN 'LSC' THEN 'fa-hands' ELSE 'fa-flag-checkered' END,
       'ruta', 'ruta_nivel', 1, i.id, 'A1', 'verde', CASE i.codigo WHEN 'EN' THEN 108 WHEN 'ES' THEN 109 ELSE 110 END
FROM idiomas i
WHERE i.codigo IN ('EN', 'ES', 'LSC');
