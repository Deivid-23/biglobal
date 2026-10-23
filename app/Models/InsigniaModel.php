<?php

/**
 * InsigniaModel
 *
 * Insignias del estudiante (tabla medallas ampliada en database/insignias.sql
 * y usuario_medallas). En la página siempre se llaman "Insignias".
 *
 * - Cada insignia tiene una condición (qué se cuenta) y una meta.
 * - revisar($usuario, $evento) se llama DESPUÉS de guardar algo (terminar
 *   una lección, entregar una tarea...) y solo mira las condiciones de ese
 *   evento. Da las que ya se cumplen y avisa por la campana.
 * - Una insignia desactivada no se da ni se muestra; quien ya la tenía la
 *   conserva y vuelve a verla si se reactiva.
 * - Las secretas no se muestran hasta ganarlas.
 */
class InsigniaModel
{
    /** condición => [texto para el admin, unidad, eventos que la pueden cumplir] */
    public const CONDICIONES = [
        "ruta_lecciones"    => ["Lecciones de la ruta completadas", "lecciones", ["ruta"]],
        "ruta_perfectas"    => ["Lecciones de la ruta sin errores", "lecciones perfectas", ["ruta"]],
        "ruta_nivel"        => ["Terminar un nivel de un idioma de la ruta", "nivel", ["ruta"]],
        "ruta_idiomas"      => ["Idiomas distintos practicados en la ruta", "idiomas", ["ruta"]],
        "racha"             => ["Racha de días (la más alta)", "días", ["ruta", "curso", "actividad"]],
        "xp"                => ["XP total", "XP", ["ruta", "curso", "actividad"]],
        "curso_lecciones"   => ["Lecciones de cursos completadas", "lecciones", ["curso"]],
        "cursos_terminados" => ["Cursos terminados", "cursos", ["curso"]],
        "actividades"       => ["Actividades aprobadas", "actividades", ["actividad"]],
        "grupos"            => ["Grupos a los que pertenece", "grupos", ["grupo"]],
        "tareas_entregadas" => ["Tareas entregadas", "tareas", ["tarea"]],
        "tareas_a_tiempo"   => ["Tareas entregadas a tiempo", "tareas", ["tarea"]],
        "tareas_perfectas"  => ["Tareas con el puntaje máximo", "tareas", ["calificacion"]],
        "clases"            => ["Inscripciones a clases en vivo", "clases", ["clase"]],
        "comentarios"       => ["Comentarios en el tablón de los grupos", "comentarios", ["comentario"]],
    ];

    public const CATEGORIAS = [
        "ruta"       => "Ruta de aprendizaje",
        "constancia" => "Constancia",
        "cursos"     => "Cursos",
        "grupos"     => "Grupos y tareas",
        "clases"     => "Clases en vivo",
        "comunidad"  => "Comunidad",
    ];

    public const COLORES = ["azul" => "Azul", "verde" => "Verde", "naranja" => "Naranja", "ambar" => "Ámbar",
                            "rojo" => "Rojo", "rosa" => "Rosa", "morado" => "Morado"];

    public static function disponible(PDO $c): bool
    {
        static $disponible = null;
        if ($disponible === null) {
            try {
                $disponible = (int) $c->query("
                    SELECT COUNT(*) FROM information_schema.columns
                    WHERE table_schema = DATABASE() AND table_name = 'medallas' AND column_name IN ('condicion', 'activa')
                ")->fetchColumn() === 2;
            } catch (Throwable $e) {
                $disponible = false;
            }
        }
        return $disponible;
    }

    /* =========================================================
       CONTAR CUÁNTO LLEVA EL ESTUDIANTE
    ========================================================= */

    /** Valor actual de una condición para un estudiante (0 si la tabla no existe todavía). */
    public static function conteo(PDO $c, int $usuarioId, array $insignia): int
    {
        $sql = [
            "ruta_lecciones"    => "SELECT COUNT(*) FROM ruta_progreso WHERE usuario_id = ?",
            "ruta_perfectas"    => "SELECT COUNT(*) FROM ruta_progreso WHERE usuario_id = ? AND mejor_porcentaje = 100",
            "ruta_idiomas"      => "SELECT COUNT(DISTINCT n.idioma_id) FROM ruta_progreso p
                                    INNER JOIN ruta_lecciones l ON l.id = p.leccion_id
                                    INNER JOIN ruta_unidades u ON u.id = l.unidad_id
                                    INNER JOIN ruta_niveles n ON n.id = u.nivel_id
                                    WHERE p.usuario_id = ?",
            "racha"             => "SELECT COALESCE(MAX(racha_maxima), 0) FROM estudiante_stats WHERE usuario_id = ?",
            "xp"                => "SELECT COALESCE(MAX(xp_total), 0) FROM estudiante_stats WHERE usuario_id = ?",
            "curso_lecciones"   => "SELECT COUNT(*) FROM progreso WHERE usuario_id = ? AND completado = 1",
            "cursos_terminados" => "SELECT COUNT(*) FROM certificados WHERE usuario_id = ?",
            "actividades"       => "SELECT COUNT(DISTINCT actividad_id) FROM actividad_intentos WHERE usuario_id = ? AND aprobado = 1",
            "grupos"            => "SELECT COUNT(*) FROM grupo_miembros WHERE usuario_id = ?",
            "tareas_entregadas" => "SELECT COUNT(*) FROM tarea_entregas WHERE estudiante_id = ?",
            "tareas_a_tiempo"   => "SELECT COUNT(*) FROM tarea_entregas WHERE estudiante_id = ? AND tardia = 0",
            "tareas_perfectas"  => "SELECT COUNT(*) FROM tarea_entregas e INNER JOIN grupo_tareas t ON t.id = e.tarea_id
                                    WHERE e.estudiante_id = ? AND e.puntaje IS NOT NULL AND e.puntaje >= t.puntaje_max",
            "clases"            => "SELECT COUNT(*) FROM clase_inscripciones i INNER JOIN clases_vivo cl ON cl.id = i.clase_id
                                    WHERE i.usuario_id = ? AND i.estado = 'inscrito' AND cl.estado <> 'cancelada'",
            "comentarios"       => "SELECT COUNT(*) FROM anuncio_comentarios WHERE autor_id = ?",
        ];
        try {
            if ($insignia["condicion"] === "ruta_nivel") {
                return self::nivelCompleto($c, $usuarioId, (int) $insignia["idioma_id"], (string) $insignia["nivel"]) ? 1 : 0;
            }
            if (!isset($sql[$insignia["condicion"]])) {
                return 0;
            }
            $stmt = $c->prepare($sql[$insignia["condicion"]]);
            $stmt->execute([$usuarioId]);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;   // la tabla de esa función todavía no existe
        }
    }

    /** ¿Completó todas las lecciones jugables publicadas de un nivel de la ruta? */
    private static function nivelCompleto(PDO $c, int $usuarioId, int $idiomaId, string $nivel): bool
    {
        $stmt = $c->prepare("
            SELECT COUNT(*) AS total, COUNT(p.leccion_id) AS hechas
            FROM ruta_lecciones l
            INNER JOIN ruta_unidades u ON u.id = l.unidad_id AND u.publicado = 1
            INNER JOIN ruta_niveles n ON n.id = u.nivel_id AND n.publicado = 1 AND n.idioma_id = ? AND n.codigo = ?
            LEFT JOIN ruta_progreso p ON p.leccion_id = l.id AND p.usuario_id = ?
            WHERE l.publicado = 1
              AND EXISTS (SELECT 1 FROM ruta_ejercicios e WHERE e.leccion_id = l.id AND e.activo = 1
                          AND (e.tipo <> 'sena' OR COALESCE(e.video_url, '') <> ''))
        ");
        $stmt->execute([$idiomaId, $nivel, $usuarioId]);
        $f = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $f["total"] > 0 && (int) $f["hechas"] === (int) $f["total"];
    }

    /* =========================================================
       DAR INSIGNIAS
    ========================================================= */

    /**
     * Revisa las insignias activas del evento y da las que ya se cumplen.
     * Nunca rompe lo que el estudiante estaba haciendo: un error va al log.
     * @return array Nombres de las insignias NUEVAS.
     */
    public static function revisar(PDO $c, int $usuarioId, string $evento): array
    {
        if (!self::disponible($c)) {
            return [];
        }
        $condiciones = array_keys(array_filter(self::CONDICIONES, fn($d) => in_array($evento, $d[2], true)));
        if (!$condiciones) {
            return [];
        }
        try {
            $marcas = implode(",", array_fill(0, count($condiciones), "?"));
            $stmt = $c->prepare("
                SELECT m.* FROM medallas m
                LEFT JOIN usuario_medallas um ON um.medalla_id = m.id AND um.usuario_id = ?
                WHERE m.activa = 1 AND m.condicion IN ($marcas) AND um.medalla_id IS NULL
                ORDER BY m.orden, m.id
            ");
            $stmt->execute(array_merge([$usuarioId], $condiciones));
            $nuevas = [];
            $cache = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $m) {
                $clave = $m["condicion"] . ":" . $m["idioma_id"] . ":" . $m["nivel"];
                $cache[$clave] ??= self::conteo($c, $usuarioId, $m);
                if ($cache[$clave] >= max(1, (int) $m["meta"]) && self::dar($c, $usuarioId, $m)) {
                    $nuevas[] = $m["nombre"];
                }
            }
            return $nuevas;
        } catch (Throwable $e) {
            error_log("[BiGlobal][Insignias] " . $e->getMessage());
            return [];
        }
    }

    /** Guarda la insignia y avisa por la campana. false si ya la tenía. */
    private static function dar(PDO $c, int $usuarioId, array $m): bool
    {
        $stmt = $c->prepare("INSERT IGNORE INTO usuario_medallas (usuario_id, medalla_id) VALUES (?, ?)");
        $stmt->execute([$usuarioId, (int) $m["id"]]);
        if ($stmt->rowCount() === 0) {
            return false;
        }
        Notificador::aUsuario($usuarioId, "insignia", "¡Nueva insignia: " . $m["nombre"] . "!",
            (string) ($m["descripcion"] ?? ""), "estudiante/index.php?page=insignias");
        return true;
    }

    /** " ¡Nueva insignia: «X»!" para sumar al mensaje de la página ("" si no hay). */
    public static function texto(array $nombres): string
    {
        if (!$nombres) {
            return "";
        }
        return count($nombres) === 1
            ? " ¡Nueva insignia: «" . $nombres[0] . "»!"
            : " ¡Nuevas insignias: «" . implode("», «", $nombres) . "»!";
    }

    /**
     * Admin: da la insignia a todos los estudiantes activos que ya cumplen.
     * @return int a cuántos se la dio
     */
    public static function darAQuienesCumplen(PDO $c, int $insigniaId): int
    {
        $m = self::porId($c, $insigniaId);
        if (!$m || (int) $m["activa"] !== 1 || !isset(self::CONDICIONES[$m["condicion"]])) {
            return 0;
        }
        $stmt = $c->prepare("
            SELECT u.id FROM usuarios u
            INNER JOIN roles r ON r.id = u.rol_id AND r.nombre = 'estudiante'
            LEFT JOIN usuario_medallas um ON um.usuario_id = u.id AND um.medalla_id = ?
            WHERE u.activo = 1 AND um.medalla_id IS NULL
        ");
        $stmt->execute([$insigniaId]);
        $dadas = 0;
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $uid) {
            if (self::conteo($c, (int) $uid, $m) >= max(1, (int) $m["meta"]) && self::dar($c, (int) $uid, $m)) {
                $dadas++;
            }
        }
        return $dadas;
    }

    /* =========================================================
       LECTURAS PARA EL ESTUDIANTE
    ========================================================= */

    /**
     * Insignias para "Mi perfil": activas (las secretas solo si las ganó),
     * con la fecha si la ganó y el avance si no.
     */
    public static function delEstudiante(PDO $c, int $usuarioId): array
    {
        if (!self::disponible($c)) {
            return [];
        }
        $stmt = $c->prepare("
            SELECT m.*, um.fecha AS fecha_ganada
            FROM medallas m
            LEFT JOIN usuario_medallas um ON um.medalla_id = m.id AND um.usuario_id = ?
            WHERE m.activa = 1 AND (m.secreta = 0 OR um.medalla_id IS NOT NULL)
            ORDER BY (um.medalla_id IS NULL), m.orden, m.id
        ");
        $stmt->execute([$usuarioId]);
        $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $cache = [];
        foreach ($lista as &$m) {
            if ($m["fecha_ganada"] === null && isset(self::CONDICIONES[$m["condicion"]])) {
                $clave = $m["condicion"] . ":" . $m["idioma_id"] . ":" . $m["nivel"];
                $cache[$clave] ??= self::conteo($c, $usuarioId, $m);
                $m["avance"] = min((int) $cache[$clave], max(1, (int) $m["meta"]));
            }
        }
        unset($m);
        return $lista;
    }

    /** Texto del avance: "7 de 10 lecciones". */
    public static function textoAvance(array $m): string
    {
        if ($m["condicion"] === "ruta_nivel") {
            return "Termina todas sus lecciones";
        }
        $unidad = self::CONDICIONES[$m["condicion"]][1] ?? "";
        return (int) ($m["avance"] ?? 0) . " de " . (int) $m["meta"] . " " . $unidad;
    }

    /* =========================================================
       ADMIN
    ========================================================= */

    public static function porId(PDO $c, int $id): ?array
    {
        $stmt = $c->prepare("SELECT * FROM medallas WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Todas, con cuántos estudiantes la tienen. */
    public static function listado(PDO $c): array
    {
        return $c->query("
            SELECT m.*, i.nombre AS idioma, (SELECT COUNT(*) FROM usuario_medallas um WHERE um.medalla_id = m.id) AS ganadores
            FROM medallas m
            LEFT JOIN idiomas i ON i.id = m.idioma_id
            ORDER BY m.activa DESC, m.orden, m.id
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crea o edita una insignia desde el formulario del admin.
     * @return int|string id o el texto del error
     */
    public static function guardar(PDO $c, ?array $insignia, array $post)
    {
        $nombre = trim((string) ($post["nombre"] ?? ""));
        if ($nombre === "" || mb_strlen($nombre) > 100) {
            return "El nombre es obligatorio (máximo 100 caracteres).";
        }
        $descripcion = trim((string) ($post["descripcion"] ?? ""));
        if ($descripcion === "" || mb_strlen($descripcion) > 255) {
            return "La descripción es obligatoria (máximo 255 caracteres): dile al estudiante qué hizo para ganarla.";
        }
        $icono = trim((string) ($post["icono"] ?? ""));
        if (!preg_match('/^fa-[a-z0-9-]{2,40}$/', $icono)) {
            return "El ícono debe ser un nombre de Font Awesome, por ejemplo fa-star.";
        }
        $categoria = (string) ($post["categoria"] ?? "");
        $condicion = (string) ($post["condicion"] ?? "");
        $color = (string) ($post["color"] ?? "");
        if (!isset(self::CATEGORIAS[$categoria]) || !isset(self::CONDICIONES[$condicion]) || !isset(self::COLORES[$color])) {
            return "Elige la categoría, la condición y el color de la lista.";
        }
        $meta = (int) ($post["meta"] ?? 0);
        $idiomaId = null;
        $nivel = null;
        if ($condicion === "ruta_nivel") {
            $meta = 1;
            // El formulario manda un solo valor "idioma-nivel", por ejemplo "3-A1"
            [$idiomaTexto, $nivelTexto] = array_pad(explode("-", (string) ($post["nivel_ruta"] ?? ""), 2), 2, "");
            $idiomaId = (int) $idiomaTexto;
            $nivel = strtoupper(trim($nivelTexto));
            $existe = $c->prepare("SELECT 1 FROM ruta_niveles WHERE idioma_id = ? AND codigo = ? LIMIT 1");
            $existe->execute([$idiomaId, $nivel]);
            if (!$existe->fetchColumn()) {
                return "Elige un idioma y un nivel que existan en la ruta.";
            }
        } elseif ($meta < 1 || $meta > 100000) {
            return "La meta va de 1 a 100.000.";
        }
        $orden = max(1, min(9999, (int) ($post["orden"] ?? 100)));
        $secreta = !empty($post["secreta"]) ? 1 : 0;

        $valores = [$nombre, $descripcion, $icono, $categoria, $condicion, $meta, $idiomaId, $nivel, $color, $orden, $secreta];
        if ($insignia) {
            $c->prepare("
                UPDATE medallas SET nombre = ?, descripcion = ?, icono = ?, categoria = ?, condicion = ?, meta = ?,
                       idioma_id = ?, nivel = ?, color = ?, orden = ?, secreta = ?
                WHERE id = ?
            ")->execute(array_merge($valores, [(int) $insignia["id"]]));
            return (int) $insignia["id"];
        }
        $c->prepare("
            INSERT INTO medallas (nombre, descripcion, icono, categoria, condicion, meta, idioma_id, nivel, color, orden, secreta, codigo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute(array_merge($valores, ["adm-" . bin2hex(random_bytes(6))]));
        return (int) $c->lastInsertId();
    }

    public static function activar(PDO $c, int $id, bool $activa): bool
    {
        $stmt = $c->prepare("UPDATE medallas SET activa = ? WHERE id = ?");
        $stmt->execute([$activa ? 1 : 0, $id]);
        return $stmt->rowCount() > 0;
    }
}
