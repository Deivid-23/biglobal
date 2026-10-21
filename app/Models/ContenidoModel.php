<?php

/**
 * ContenidoModel
 *
 * Editar y borrar cursos, lecciones, actividades e idiomas.
 * Lo usan el panel de admin (AdminController) y el del docente
 * (src/profesor/acciones.php), así las reglas son las mismas en los dos.
 *
 * Regla para borrar: un curso o una lección NO se borra si algún
 * estudiante ya avanzó en él (la BD borraría en cascada su progreso y,
 * en un curso, sus certificados). En ese caso se sugiere pasarlo a
 * "Borrador", que lo oculta sin perder nada. Una actividad tampoco se
 * borra si algún estudiante ya la respondió.
 */
require_once __DIR__ . "/QuizModel.php";

class ContenidoModel
{
    public const NIVELES = ["Básico", "Intermedio", "Avanzado"];

    public const TIPOS_ACTIVIDAD = ["Quiz", "Tarea", "Ejercicio", "Taller", "Proyecto", "Examen"];

    /* =========================================================
       LEER Y VALIDAR LO QUE LLEGA DEL FORMULARIO
       Devuelven el arreglo limpio, o un texto con el error.
    ========================================================= */

    /** @return array|string */
    public static function datosCurso(array $post)
    {
        $nombre = trim($post["nombre"] ?? "");
        $nivel  = trim($post["nivel"] ?? "");

        if ($nombre === "" || mb_strlen($nombre) > 150) {
            return "El nombre del curso es obligatorio (máximo 150 caracteres).";
        }
        // El nivel no es estricto: hay cursos viejos con otros valores (ej. "A1")
        if (mb_strlen($nivel) > 50) {
            return "El nivel es demasiado largo (máximo 50 caracteres).";
        }

        return [
            "nombre"      => $nombre,
            "descripcion" => trim($post["descripcion"] ?? ""),
            "nivel"       => $nivel !== "" ? $nivel : null,
            "estado"      => (int) ($post["estado"] ?? 1) === 1 ? 1 : 0,
        ];
    }

    /** @return array|string */
    public static function datosLeccion(array $post)
    {
        $titulo = trim($post["titulo"] ?? "");

        if ($titulo === "" || mb_strlen($titulo) > 150) {
            return "El título es obligatorio (máximo 150 caracteres).";
        }

        // Video opcional: solo YouTube o Vimeo (ver app/Helpers/Video.php)
        $video = Video::normalizar((string) ($post["video_url"] ?? ""));
        if (is_string($video)) {
            return $video;
        }

        return [
            "titulo"      => $titulo,
            "descripcion" => trim($post["descripcion"] ?? ""),
            "contenido"   => trim($post["contenido"] ?? ""),
            "video_url"   => $video["url"] ?? null,
            "orden"       => max(1, (int) ($post["orden"] ?? 1)),
            "estado"      => (int) ($post["estado"] ?? 1) === 1 ? 1 : 0,
        ];
    }

    /** @return array|string */
    public static function datosActividad(array $post)
    {
        $titulo = trim($post["titulo"] ?? "");
        $tipo   = trim($post["tipo"] ?? "");

        if ($titulo === "" || mb_strlen($titulo) > 150) {
            return "El título es obligatorio (máximo 150 caracteres).";
        }
        if (!in_array($tipo, self::TIPOS_ACTIVIDAD, true)) {
            return "Elige un tipo de actividad de la lista.";
        }

        return [
            "titulo"      => $titulo,
            "descripcion" => trim($post["descripcion"] ?? ""),
            "tipo"        => $tipo,
        ];
    }

    /** @return array|string */
    public static function datosIdioma(array $post)
    {
        $nombre = trim($post["nombre"] ?? "");
        $codigo = strtoupper(trim($post["codigo"] ?? ""));

        if ($nombre === "" || $codigo === "" || mb_strlen($nombre) > 50 || mb_strlen($codigo) > 10) {
            return "El nombre (máx. 50) y el código (máx. 10) son obligatorios.";
        }

        return [
            "nombre" => $nombre,
            "codigo" => $codigo,
            "estado" => (int) ($post["estado"] ?? 1) === 1 ? 1 : 0,
        ];
    }

    /* =========================================================
       IDIOMAS (para el campo "Idioma" de los cursos)
    ========================================================= */

    /** Idiomas activos para los selects: id, nombre, codigo. */
    public static function idiomasActivos(PDO $c): array
    {
        try {
            return $c->query("SELECT id, nombre, codigo FROM idiomas WHERE estado = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("[BiGlobal][ContenidoModel] " . $e->getMessage());
            return [];
        }
    }

    /**
     * Revisa el idioma elegido en el formulario.
     * Devuelve [idioma_id o null, error o null]. Vacío = "sin idioma".
     */
    public static function idiomaElegido(PDO $c, $valor): array
    {
        $id = (int) $valor;
        if ($id <= 0) {
            return [null, null];
        }
        $ids = array_map("intval", array_column(self::idiomasActivos($c), "id"));
        return in_array($id, $ids, true) ? [$id, null] : [null, "Elige un idioma activo de la lista."];
    }

    /* =========================================================
       ¿ES DEL DOCENTE? (devuelven el id del curso, o 0)
    ========================================================= */

    public static function cursoDelDocente(PDO $c, int $cursoId, int $docenteId): int
    {
        $stmt = $c->prepare("SELECT id FROM cursos WHERE id = ? AND instructor_id = ? LIMIT 1");
        $stmt->execute([$cursoId, $docenteId]);
        return (int) $stmt->fetchColumn();
    }

    public static function leccionDelDocente(PDO $c, int $leccionId, int $docenteId): int
    {
        $stmt = $c->prepare("
            SELECT l.curso_id FROM lecciones l
            INNER JOIN cursos cu ON cu.id = l.curso_id
            WHERE l.id = ? AND cu.instructor_id = ? LIMIT 1
        ");
        $stmt->execute([$leccionId, $docenteId]);
        return (int) $stmt->fetchColumn();
    }

    public static function actividadDelDocente(PDO $c, int $actividadId, int $docenteId): int
    {
        $stmt = $c->prepare("
            SELECT l.curso_id FROM actividades a
            INNER JOIN lecciones l ON l.id = a.leccion_id
            INNER JOIN cursos cu ON cu.id = l.curso_id
            WHERE a.id = ? AND cu.instructor_id = ? LIMIT 1
        ");
        $stmt->execute([$actividadId, $docenteId]);
        return (int) $stmt->fetchColumn();
    }

    /* =========================================================
       ¿HAY ESTUDIANTES CON AVANCE?
    ========================================================= */

    /** Estudiantes distintos con progreso o certificado en el curso. */
    public static function estudiantesConAvanceEnCurso(PDO $c, int $cursoId): int
    {
        $stmt = $c->prepare("
            SELECT COUNT(DISTINCT usuario_id) FROM (
                SELECT p.usuario_id FROM progreso p
                INNER JOIN lecciones l ON l.id = p.leccion_id
                WHERE l.curso_id = ?
                UNION ALL
                SELECT ce.usuario_id FROM certificados ce WHERE ce.curso_id = ?
            ) t
        ");
        $stmt->execute([$cursoId, $cursoId]);
        return (int) $stmt->fetchColumn();
    }

    public static function estudiantesConAvanceEnLeccion(PDO $c, int $leccionId): int
    {
        $stmt = $c->prepare("SELECT COUNT(DISTINCT usuario_id) FROM progreso WHERE leccion_id = ?");
        $stmt->execute([$leccionId]);
        return (int) $stmt->fetchColumn();
    }

    /* =========================================================
       GUARDAR CAMBIOS
    ========================================================= */

    /**
     * $d trae también "idioma_id" (null = sin idioma).
     * $instructorId: false = no cambiarlo (docente); null o un id = asignarlo (admin).
     */
    public static function actualizarCurso(PDO $c, int $id, array $d, $instructorId = false): void
    {
        if ($instructorId === false) {
            $stmt = $c->prepare("UPDATE cursos SET nombre = ?, descripcion = ?, nivel = ?, idioma_id = ?, estado = ? WHERE id = ?");
            $stmt->execute([$d["nombre"], $d["descripcion"], $d["nivel"], $d["idioma_id"], $d["estado"], $id]);
            return;
        }
        $stmt = $c->prepare("UPDATE cursos SET nombre = ?, descripcion = ?, nivel = ?, idioma_id = ?, estado = ?, instructor_id = ? WHERE id = ?");
        $stmt->execute([$d["nombre"], $d["descripcion"], $d["nivel"], $d["idioma_id"], $d["estado"], $instructorId, $id]);
    }

    /** $cursoId: null = dejar la lección en su curso. */
    public static function actualizarLeccion(PDO $c, int $id, array $d, ?int $cursoId = null): void
    {
        if ($cursoId === null) {
            $stmt = $c->prepare("UPDATE lecciones SET titulo = ?, descripcion = ?, contenido = ?, video_url = ?, orden = ?, estado = ? WHERE id = ?");
            $stmt->execute([$d["titulo"], $d["descripcion"], $d["contenido"], $d["video_url"], $d["orden"], $d["estado"], $id]);
            return;
        }
        $stmt = $c->prepare("UPDATE lecciones SET curso_id = ?, titulo = ?, descripcion = ?, contenido = ?, video_url = ?, orden = ?, estado = ? WHERE id = ?");
        $stmt->execute([$cursoId, $d["titulo"], $d["descripcion"], $d["contenido"], $d["video_url"], $d["orden"], $d["estado"], $id]);
    }

    /** $leccionId: null = dejar la actividad en su lección. */
    public static function actualizarActividad(PDO $c, int $id, array $d, ?int $leccionId = null): void
    {
        if ($leccionId === null) {
            $stmt = $c->prepare("UPDATE actividades SET titulo = ?, descripcion = ?, tipo = ? WHERE id = ?");
            $stmt->execute([$d["titulo"], $d["descripcion"], $d["tipo"], $id]);
            return;
        }
        $stmt = $c->prepare("UPDATE actividades SET leccion_id = ?, titulo = ?, descripcion = ?, tipo = ? WHERE id = ?");
        $stmt->execute([$leccionId, $d["titulo"], $d["descripcion"], $d["tipo"], $id]);
    }

    /** @return string|null Error si ya existe otro idioma con ese nombre o código. */
    public static function actualizarIdioma(PDO $c, int $id, array $d): ?string
    {
        $stmt = $c->prepare("SELECT id FROM idiomas WHERE (nombre = ? OR codigo = ?) AND id <> ? LIMIT 1");
        $stmt->execute([$d["nombre"], $d["codigo"], $id]);
        if ($stmt->fetch()) {
            return "Ya existe otro idioma con ese nombre o código.";
        }
        $c->prepare("UPDATE idiomas SET nombre = ?, codigo = ?, estado = ? WHERE id = ?")
          ->execute([$d["nombre"], $d["codigo"], $d["estado"], $id]);
        return null;
    }

    /* =========================================================
       BORRAR (devuelven null si se borró, o el motivo si no)
    ========================================================= */

    public static function eliminarCurso(PDO $c, int $id): ?string
    {
        $avance = self::estudiantesConAvanceEnCurso($c, $id);
        if ($avance > 0) {
            return plural_panel($avance, "estudiante tiene", "estudiantes tienen")
                . " avance o certificado en este curso. Pásalo a «Borrador» para ocultarlo sin borrar su progreso.";
        }
        $nombre = self::nombreDe($c, "SELECT nombre FROM cursos WHERE id = ?", $id);
        $c->prepare("DELETE FROM cursos WHERE id = ?")->execute([$id]);
        Auditoria::registrar("curso.eliminar", "curso", $id, $nombre);
        return null;
    }

    public static function eliminarLeccion(PDO $c, int $id): ?string
    {
        $avance = self::estudiantesConAvanceEnLeccion($c, $id);
        if ($avance > 0) {
            return plural_panel($avance, "estudiante ya avanzó", "estudiantes ya avanzaron")
                . " en esta lección. Pásala a «Borrador» para ocultarla sin borrar su progreso.";
        }
        $nombre = self::nombreDe($c, "
            SELECT CONCAT(l.titulo, ' · ', cu.nombre) FROM lecciones l
            INNER JOIN cursos cu ON cu.id = l.curso_id WHERE l.id = ?", $id);
        $c->prepare("DELETE FROM lecciones WHERE id = ?")->execute([$id]);
        Auditoria::registrar("leccion.eliminar", "leccion", $id, $nombre);
        return null;
    }

    /** No se borra si algún estudiante ya la respondió (se perderían sus intentos). */
    public static function eliminarActividad(PDO $c, int $id): ?string
    {
        if (QuizModel::disponible($c)) {
            $stmt = $c->prepare("SELECT COUNT(DISTINCT usuario_id) FROM actividad_intentos WHERE actividad_id = ?");
            $stmt->execute([$id]);
            $avance = (int) $stmt->fetchColumn();
            if ($avance > 0) {
                return plural_panel($avance, "estudiante ya respondió", "estudiantes ya respondieron")
                    . " esta actividad. Puedes editarla, pero no borrarla.";
            }
        }
        $nombre = self::nombreDe($c, "
            SELECT CONCAT(a.titulo, ' · ', l.titulo) FROM actividades a
            INNER JOIN lecciones l ON l.id = a.leccion_id WHERE a.id = ?", $id);
        $c->prepare("DELETE FROM actividades WHERE id = ?")->execute([$id]);
        Auditoria::registrar("actividad.eliminar", "actividad", $id, $nombre);
        return null;
    }

    /** Nombre en texto de un registro antes de borrarlo (para la auditoría). */
    private static function nombreDe(PDO $c, string $sql, int $id): string
    {
        $stmt = $c->prepare($sql);
        $stmt->execute([$id]);
        return (string) ($stmt->fetchColumn() ?: "#" . $id);
    }
}
