<?php

/**
 * RevisionModel
 *
 * Revisión del contenido de los docentes (database/revision_contenido.sql).
 * Cursos y lecciones: Borrador -> En revisión -> Publicado / Cambios solicitados / Rechazado.
 *
 * "estado" (1 = lo ven los estudiantes) lo maneja SOLO esta clase cuando
 * actúa un docente:
 *   - Lo que crea un docente nace en borrador (estado 0).
 *   - Solo el admin publica (estado 1).
 *   - Si el docente edita algo ya publicado, vuelve a "en_revision" pero sigue
 *     visible (no se corta el curso a quien lo está haciendo).
 *   - Rechazar lo oculta (estado 0); pedir cambios no cambia la visibilidad.
 * Lo que crea o edita el admin queda publicado o en borrador según elija.
 */
class RevisionModel
{
    public const TABLAS = ["curso" => "cursos", "leccion" => "lecciones"];

    public const ESTADOS = [
        "borrador"    => ["Borrador", "chip--neutro"],
        "en_revision" => ["En revisión", "chip--alerta"],
        "publicado"   => ["Publicado", "chip--exito"],
        "cambios"     => ["Cambios solicitados", "chip--alerta"],
        "rechazado"   => ["Rechazado", "chip--error"],
    ];

    public static function disponible(PDO $c): bool
    {
        static $disponible = null;
        if ($disponible === null) {
            try {
                $disponible = (int) $c->query("
                    SELECT COUNT(*) FROM information_schema.columns
                    WHERE table_schema = DATABASE() AND column_name = 'revision' AND table_name IN ('cursos', 'lecciones')
                ")->fetchColumn() === 2;
            } catch (Throwable $e) {
                $disponible = false;
            }
        }
        return $disponible;
    }

    private static function tabla(string $tipo): string
    {
        if (!isset(self::TABLAS[$tipo])) {
            throw new InvalidArgumentException("Tipo de contenido desconocido: {$tipo}");
        }
        return self::TABLAS[$tipo];
    }

    public static function fila(PDO $c, string $tipo, int $id): ?array
    {
        $stmt = $c->prepare("SELECT * FROM " . self::tabla($tipo) . " WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /* =========================================================
       LO QUE HACE EL DOCENTE
    ========================================================= */

    /** Recién creado por un docente: borrador y oculto. */
    public static function alCrearDocente(PDO $c, string $tipo, int $id): void
    {
        if (!self::disponible($c)) {
            return;
        }
        $c->prepare("UPDATE " . self::tabla($tipo) . " SET revision = 'borrador', estado = 0 WHERE id = ?")->execute([$id]);
    }

    /** El docente editó: si estaba publicado, vuelve a revisión (sigue visible). */
    public static function alEditarDocente(PDO $c, string $tipo, int $id): bool
    {
        if (!self::disponible($c)) {
            return false;
        }
        $stmt = $c->prepare("UPDATE " . self::tabla($tipo) . " SET revision = 'en_revision', revision_comentario = NULL WHERE id = ? AND revision = 'publicado'");
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 1) {
            self::avisarEquipo($c, $tipo, $id, true);
            return true;
        }
        return false;
    }

    /** "Enviar a revisión" (desde borrador, cambios o rechazado). */
    public static function enviar(PDO $c, string $tipo, int $id): bool
    {
        $stmt = $c->prepare("
            UPDATE " . self::tabla($tipo) . " SET revision = 'en_revision'
            WHERE id = ? AND revision IN ('borrador', 'cambios', 'rechazado')
        ");
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 1) {
            self::avisarEquipo($c, $tipo, $id, false);
            return true;
        }
        return false;
    }

    /** "Pasar a borrador": deja de verse y sale de la cola de revisión. */
    public static function aBorrador(PDO $c, string $tipo, int $id): void
    {
        $c->prepare("UPDATE " . self::tabla($tipo) . " SET revision = 'borrador', estado = 0 WHERE id = ?")->execute([$id]);
    }

    private static function avisarEquipo(PDO $c, string $tipo, int $id, bool $esEdicion): void
    {
        $f = self::fila($c, $tipo, $id);
        $titulo = $f[$tipo === "curso" ? "nombre" : "titulo"] ?? "#{$id}";
        Notificador::avisar("contenido_revision",
            ($esEdicion ? "Cambios por revisar: " : "Por revisar: ") . ($tipo === "curso" ? "curso «" : "lección «") . $titulo . "»",
            ($esEdicion ? "Un docente editó contenido ya publicado (sigue visible mientras lo revisas)." : "Un docente envió contenido nuevo a revisión."),
            "admin/index.php?page=revision", (int) ($_SESSION["usuario_id"] ?? 0));
    }

    /* =========================================================
       LO QUE HACE EL ADMIN
    ========================================================= */

    /** Admin crea o edita: queda publicado u oculto según el estado que eligió. */
    public static function alGuardarAdmin(PDO $c, string $tipo, int $id): void
    {
        if (!self::disponible($c)) {
            return;
        }
        $c->prepare("
            UPDATE " . self::tabla($tipo) . "
            SET revision = IF(estado = 1, 'publicado', 'borrador'), revision_comentario = NULL
            WHERE id = ?
        ")->execute([$id]);
    }

    /** Todo lo que espera revisión (cursos y lecciones), lo más antiguo primero. */
    public static function pendientes(PDO $c): array
    {
        if (!self::disponible($c)) {
            return [];
        }
        $cursos = $c->query("
            SELECT 'curso' AS tipo, cu.id, cu.nombre AS titulo, cu.descripcion, NULL AS contenido, NULL AS video_url,
                   cu.estado, cu.nivel, NULL AS curso, cu.id AS curso_id, cu.fecha_creacion AS fecha,
                   CONCAT(u.nombre, ' ', COALESCE(u.apellido, '')) AS docente,
                   (SELECT COUNT(*) FROM lecciones l WHERE l.curso_id = cu.id) AS n_hijos
            FROM cursos cu LEFT JOIN usuarios u ON u.id = cu.instructor_id
            WHERE cu.revision = 'en_revision'
        ")->fetchAll(PDO::FETCH_ASSOC);
        $lecciones = $c->query("
            SELECT 'leccion' AS tipo, l.id, l.titulo, l.descripcion, l.contenido, l.video_url,
                   l.estado, NULL AS nivel, cu.nombre AS curso, cu.id AS curso_id, l.fecha_creacion AS fecha,
                   CONCAT(u.nombre, ' ', COALESCE(u.apellido, '')) AS docente,
                   (SELECT COUNT(*) FROM actividades a WHERE a.leccion_id = l.id) AS n_hijos
            FROM lecciones l INNER JOIN cursos cu ON cu.id = l.curso_id LEFT JOIN usuarios u ON u.id = cu.instructor_id
            WHERE l.revision = 'en_revision'
        ")->fetchAll(PDO::FETCH_ASSOC);

        $todo = array_merge($cursos, $lecciones);
        usort($todo, fn($a, $b) => strcmp((string) $a["fecha"], (string) $b["fecha"]));
        return $todo;
    }

    public static function contarPendientes(PDO $c): int
    {
        if (!self::disponible($c)) {
            return 0;
        }
        return (int) $c->query("
            SELECT (SELECT COUNT(*) FROM cursos WHERE revision = 'en_revision') + (SELECT COUNT(*) FROM lecciones WHERE revision = 'en_revision')
        ")->fetchColumn();
    }

    /**
     * Decisión del admin: "aprobar" | "cambios" | "rechazar".
     * Devuelve null si salió bien, o el motivo del error.
     */
    public static function revisar(PDO $c, string $tipo, int $id, string $decision, string $comentario, int $adminId): ?string
    {
        $f = self::fila($c, $tipo, $id);
        if (!$f || $f["revision"] !== "en_revision") {
            return "Este contenido ya no está en revisión.";
        }
        $comentario = trim($comentario);
        if ($decision !== "aprobar" && (mb_strlen($comentario) < 10 || mb_strlen($comentario) > 1000)) {
            return "Escribe un comentario para el docente (entre 10 y 1000 caracteres): qué debe cambiar o por qué se rechaza.";
        }

        $sql = [
            "aprobar"  => "revision = 'publicado', estado = 1",
            "cambios"  => "revision = 'cambios'",              // la visibilidad no cambia
            "rechazar" => "revision = 'rechazado', estado = 0",
        ][$decision] ?? null;
        if ($sql === null) {
            return "Decisión no válida.";
        }

        $c->prepare("UPDATE " . self::tabla($tipo) . " SET {$sql}, revision_comentario = ?, revisado_por = ?, revisado_en = NOW() WHERE id = ?")
          ->execute([$decision === "aprobar" ? ($comentario ?: null) : $comentario, $adminId, $id]);

        // Aviso al docente dueño del curso
        $titulo = $f[$tipo === "curso" ? "nombre" : "titulo"];
        $cursoId = $tipo === "curso" ? $id : (int) $f["curso_id"];
        $stmt = $c->prepare("SELECT instructor_id FROM cursos WHERE id = ?");
        $stmt->execute([$cursoId]);
        $docenteId = (int) $stmt->fetchColumn();

        $que = ($tipo === "curso" ? "Tu curso «" : "Tu lección «") . $titulo . "»";
        $textos = [
            "aprobar"  => [$que . ($tipo === "curso" ? " fue aprobado" : " fue aprobada"), "Ya lo pueden ver los estudiantes." . ($comentario ? " Comentario: {$comentario}" : "")],
            "cambios"  => ["Te pidieron cambios en " . mb_strtolower(mb_substr($que, 0, 1)) . mb_substr($que, 1), "Comentario del equipo: {$comentario} Cuando hagas los cambios, envíalo de nuevo a revisión."],
            "rechazar" => [$que . ($tipo === "curso" ? " no fue aprobado" : " no fue aprobada"), "Comentario del equipo: {$comentario}"],
        ][$decision];
        if ($docenteId > 0) {
            Notificador::aUsuario($docenteId, "contenido_revisado", $textos[0], $textos[1],
                $tipo === "curso" ? "profesor/index.php?page=curso&id={$cursoId}" : "profesor/index.php?page=lecciones&id={$cursoId}");
        }
        return null;
    }

    /** Chip HTML de un estado de revisión. */
    public static function chip(?string $revision): string
    {
        [$texto, $clase] = self::ESTADOS[$revision ?? ""] ?? ["Sin estado", "chip--neutro"];
        return '<span class="chip ' . $clase . '">' . htmlspecialchars($texto, ENT_QUOTES, "UTF-8") . '</span>';
    }
}
