<?php

require_once __DIR__ . "/GrupoModel.php";
require_once __DIR__ . "/ClaseModel.php";

/**
 * TareaModel
 *
 * Tareas de los grupos (tablas de database/grupos_tareas.sql).
 * Lo usan el docente (src/profesor/grupos_acciones.php) y el estudiante
 * (EstudianteController). Todas las reglas viven aquí:
 *
 * - El docente publica tareas con fecha de entrega opcional (hora de
 *   Colombia), puntaje máximo y hasta 5 adjuntos.
 * - Cada estudiante tiene UNA entrega por tarea (texto y/o hasta 5
 *   archivos). Puede reenviarla hasta que el docente la califica; cada
 *   envío reemplaza todo y vuelve a calcular si llegó tarde.
 * - "Tardía" se calcula en SQL con NOW() > fecha_entrega (la conexión
 *   usa la hora de Colombia). Las entregas tardías se aceptan.
 * - Un grupo archivado no recibe entregas nuevas.
 * - Al editar, el puntaje máximo no puede quedar por debajo de una nota ya puesta,
 *   y si cambia la fecha se recalcula qué entregas fueron tardías.
 */
class TareaModel
{
    public const MAX_ARCHIVOS = 5;
    public const MAX_TEXTO = 10000;

    public static function disponible(PDO $c): bool
    {
        static $disponible = null;
        if ($disponible === null) {
            try {
                $disponible = GrupoModel::disponible($c) && (int) $c->query("
                    SELECT COUNT(*) FROM information_schema.tables
                    WHERE table_schema = DATABASE() AND table_name IN ('grupo_tareas', 'tarea_entregas')
                ")->fetchColumn() === 2;
            } catch (Throwable $e) {
                $disponible = false;
            }
        }
        return $disponible;
    }

    /* =========================================================
       DOCENTE: CREAR, EDITAR, ELIMINAR
    ========================================================= */

    /**
     * Valida el formulario de la tarea.
     * @param int $notaMaxima La nota más alta ya puesta (al editar).
     * @return array|string
     */
    public static function datosTarea(array $post, float $notaMaxima = 0)
    {
        $titulo = trim((string) ($post["titulo"] ?? ""));
        if ($titulo === "" || mb_strlen($titulo) > 150) {
            return "El título es obligatorio (máximo 150 caracteres).";
        }
        $instrucciones = trim((string) ($post["instrucciones"] ?? ""));
        if (mb_strlen($instrucciones) > self::MAX_TEXTO) {
            return "Las instrucciones pueden tener máximo " . self::MAX_TEXTO . " caracteres.";
        }

        $fecha = null;
        $textoFecha = trim((string) ($post["fecha_entrega"] ?? ""));
        if ($textoFecha !== "") {
            $d = DateTimeImmutable::createFromFormat("Y-m-d\TH:i", $textoFecha, new DateTimeZone("America/Bogota"));
            if (!$d || $d->format("Y-m-d\TH:i") !== $textoFecha) {
                return "La fecha de entrega no es válida.";
            }
            $fecha = $d->format("Y-m-d H:i:00");
        }

        $puntaje = (int) ($post["puntaje_max"] ?? 100);
        if ($puntaje < 1 || $puntaje > 1000) {
            return "El puntaje máximo va de 1 a 1000.";
        }
        if ($puntaje < $notaMaxima) {
            return "Ya pusiste una nota de " . self::numero($notaMaxima) . ": el puntaje máximo no puede ser menor.";
        }

        return [
            "titulo"        => $titulo,
            "instrucciones" => $instrucciones !== "" ? $instrucciones : null,
            "fecha_entrega" => $fecha,
            "puntaje_max"   => $puntaje,
        ];
    }

    /** @return int|string Id de la tarea nueva o el texto del error. */
    public static function crear(PDO $c, array $grupo, int $docenteId, array $post, $archivosCampo)
    {
        if ((int) $grupo["archivado"] === 1) {
            return "El grupo está archivado. Restáuralo para publicar tareas.";
        }
        $datos = self::datosTarea($post);
        if (is_string($datos)) {
            return $datos;
        }
        $archivos = Archivo::validar($archivosCampo, GrupoModel::GRUPOS_ARCHIVO, self::MAX_ARCHIVOS, 0, false);
        if (!is_array($archivos)) {
            return $archivos;
        }

        $c->beginTransaction();
        try {
            $c->prepare("
                INSERT INTO grupo_tareas (grupo_id, docente_id, titulo, instrucciones, fecha_entrega, puntaje_max)
                VALUES (?, ?, ?, ?, ?, ?)
            ")->execute([(int) $grupo["id"], $docenteId, $datos["titulo"], $datos["instrucciones"], $datos["fecha_entrega"], $datos["puntaje_max"]]);
            $tareaId = (int) $c->lastInsertId();
            foreach ($archivos as $a) {
                GrupoModel::guardarArchivo($c, $docenteId, "tarea", $tareaId, $a);
            }
            $c->commit();
        } catch (Throwable $e) {
            $c->rollBack();
            throw $e;
        }

        $cuando = $datos["fecha_entrega"] ? "Entrega: " . ClaseModel::fechaHora($datos["fecha_entrega"]) . "." : "Sin fecha límite.";
        Notificador::aVarios(
            GrupoModel::idsMiembros($c, (int) $grupo["id"]),
            "grupo_tarea",
            "Nueva tarea en «" . $grupo["nombre"] . "»: " . $datos["titulo"],
            $cuando,
            "estudiante/index.php?page=grupo&id=" . (int) $grupo["id"] . "&tab=tareas&tarea=" . $tareaId
        );
        return $tareaId;
    }

    /**
     * Edita la tarea. Se pueden agregar adjuntos y quitar los marcados ($quitar = ids).
     * @return true|string
     */
    public static function editar(PDO $c, array $tarea, array $post, $archivosCampo, array $quitar)
    {
        $tareaId = (int) $tarea["id"];
        $notaMaxima = (float) self::valor($c, "SELECT COALESCE(MAX(puntaje), 0) FROM tarea_entregas WHERE tarea_id = ?", [$tareaId]);
        $datos = self::datosTarea($post, $notaMaxima);
        if (is_string($datos)) {
            return $datos;
        }

        $actuales = array_map("intval", array_column(self::adjuntos($c, "tarea", [$tareaId])[$tareaId] ?? [], "id"));
        $quitar = array_values(array_intersect($actuales, array_map("intval", $quitar)));
        $archivos = Archivo::validar($archivosCampo, GrupoModel::GRUPOS_ARCHIVO, self::MAX_ARCHIVOS, count($actuales) - count($quitar), false);
        if (!is_array($archivos)) {
            return $archivos;
        }

        $c->beginTransaction();
        try {
            $c->prepare("
                UPDATE grupo_tareas SET titulo = ?, instrucciones = ?, fecha_entrega = ?, puntaje_max = ?, editado = NOW()
                WHERE id = ?
            ")->execute([$datos["titulo"], $datos["instrucciones"], $datos["fecha_entrega"], $datos["puntaje_max"], $tareaId]);
            // Si cambió la fecha, la marca de tardía se recalcula con la hora de cada envío
            $c->prepare("UPDATE tarea_entregas SET tardia = (? IS NOT NULL AND enviada_en > ?) WHERE tarea_id = ?")
              ->execute([$datos["fecha_entrega"], $datos["fecha_entrega"], $tareaId]);
            if ($quitar) {
                $marcas = implode(",", array_fill(0, count($quitar), "?"));
                $c->prepare("DELETE FROM archivos WHERE contexto = 'tarea' AND contexto_id = ? AND id IN ($marcas)")
                  ->execute(array_merge([$tareaId], $quitar));
            }
            foreach ($archivos as $a) {
                GrupoModel::guardarArchivo($c, (int) $tarea["docente_id"], "tarea", $tareaId, $a);
            }
            $c->commit();
        } catch (Throwable $e) {
            $c->rollBack();
            throw $e;
        }
        return true;
    }

    /** Borra la tarea con sus entregas y TODOS sus archivos. */
    public static function eliminar(PDO $c, int $tareaId, int $grupoId): bool
    {
        if (!self::deGrupo($c, $tareaId, $grupoId)) {
            return false;
        }
        $c->beginTransaction();
        try {
            $c->prepare("
                DELETE ar FROM archivos ar
                INNER JOIN tarea_entregas e ON e.id = ar.contexto_id AND ar.contexto = 'entrega'
                WHERE e.tarea_id = ?
            ")->execute([$tareaId]);
            $c->prepare("DELETE FROM archivos WHERE contexto = 'tarea' AND contexto_id = ?")->execute([$tareaId]);
            $c->prepare("DELETE FROM grupo_tareas WHERE id = ? AND grupo_id = ?")->execute([$tareaId, $grupoId]);
            $c->commit();
        } catch (Throwable $e) {
            $c->rollBack();
            throw $e;
        }
        return true;
    }

    /* =========================================================
       CONSULTAS
    ========================================================= */

    /** Una tarea del grupo (con "vencida" calculada en SQL), o null. */
    public static function deGrupo(PDO $c, int $tareaId, int $grupoId): ?array
    {
        $stmt = $c->prepare("
            SELECT t.*, (t.fecha_entrega IS NOT NULL AND NOW() > t.fecha_entrega) AS vencida
            FROM grupo_tareas t
            WHERE t.id = ? AND t.grupo_id = ?
            LIMIT 1
        ");
        $stmt->execute([$tareaId, $grupoId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Tareas del grupo para el docente, con cuántos entregaron y cuántas faltan por calificar. */
    public static function tareasDocente(PDO $c, int $grupoId): array
    {
        $stmt = $c->prepare("
            SELECT t.id, t.titulo, t.fecha_entrega, t.puntaje_max, t.creado,
                   (t.fecha_entrega IS NOT NULL AND NOW() > t.fecha_entrega) AS vencida,
                   COUNT(e.id) AS entregas,
                   SUM(e.id IS NOT NULL AND e.puntaje IS NULL) AS por_calificar
            FROM grupo_tareas t
            LEFT JOIN tarea_entregas e ON e.tarea_id = t.id
            WHERE t.grupo_id = ?
            GROUP BY t.id
            ORDER BY t.creado DESC, t.id DESC
        ");
        $stmt->execute([$grupoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Tareas del grupo para un estudiante, con el estado de SU entrega. */
    public static function tareasEstudiante(PDO $c, int $grupoId, int $usuarioId): array
    {
        $stmt = $c->prepare("
            SELECT t.id, t.titulo, t.fecha_entrega, t.puntaje_max, t.creado,
                   (t.fecha_entrega IS NOT NULL AND NOW() > t.fecha_entrega) AS vencida,
                   e.id AS entrega_id, e.enviada_en, e.tardia, e.puntaje
            FROM grupo_tareas t
            LEFT JOIN tarea_entregas e ON e.tarea_id = t.id AND e.estudiante_id = ?
            WHERE t.grupo_id = ?
            ORDER BY (e.id IS NULL) DESC, t.fecha_entrega IS NULL, t.fecha_entrega ASC, t.creado DESC
        ");
        $stmt->execute([$usuarioId, $grupoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Adjuntos (sin el contenido) agrupados por contexto_id. */
    public static function adjuntos(PDO $c, string $contexto, array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map("intval", $ids))));
        if (!$ids) {
            return [];
        }
        $marcas = implode(",", array_fill(0, count($ids), "?"));
        $stmt = $c->prepare("
            SELECT id, contexto_id, nombre, mime, tamano FROM archivos
            WHERE contexto = ? AND contexto_id IN ($marcas)
            ORDER BY id ASC
        ");
        $stmt->execute(array_merge([$contexto], $ids));
        $mapa = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $a) {
            $mapa[(int) $a["contexto_id"]][] = $a;
        }
        return $mapa;
    }

    /** La entrega de un estudiante en una tarea (o null). */
    public static function entregaDe(PDO $c, int $tareaId, int $estudianteId): ?array
    {
        $stmt = $c->prepare("SELECT * FROM tarea_entregas WHERE tarea_id = ? AND estudiante_id = ? LIMIT 1");
        $stmt->execute([$tareaId, $estudianteId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Para el docente: cada estudiante del grupo con su entrega (o sin ella).
     * Las entregas de quien ya salió del grupo también aparecen (ex_miembro = 1).
     */
    public static function entregasDeTarea(PDO $c, int $tareaId, int $grupoId): array
    {
        $stmt = $c->prepare("
            SELECT u.id AS estudiante_id, u.nombre, u.apellido, u.correo,
                   e.id, e.texto, e.enviada_en, e.tardia, e.envios, e.puntaje, e.comentario_docente, e.calificada_en,
                   (m.usuario_id IS NULL) AS ex_miembro
            FROM usuarios u
            LEFT JOIN grupo_miembros m ON m.usuario_id = u.id AND m.grupo_id = :grupo
            LEFT JOIN tarea_entregas e ON e.estudiante_id = u.id AND e.tarea_id = :tarea
            WHERE u.id IN (
                SELECT usuario_id FROM grupo_miembros WHERE grupo_id = :grupo2
                UNION
                SELECT estudiante_id FROM tarea_entregas WHERE tarea_id = :tarea2
            ) AND u.activo <> 4
            ORDER BY (e.id IS NOT NULL AND e.puntaje IS NULL) DESC, (e.id IS NULL) ASC, u.nombre ASC, u.apellido ASC
        ");
        $stmt->execute([":grupo" => $grupoId, ":tarea" => $tareaId, ":grupo2" => $grupoId, ":tarea2" => $tareaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Para la pestaña Estudiantes: tareas entregadas y promedio (%) de cada uno. */
    public static function resumenPorEstudiante(PDO $c, int $grupoId): array
    {
        $stmt = $c->prepare("
            SELECT e.estudiante_id,
                   COUNT(*) AS entregadas,
                   AVG(CASE WHEN e.puntaje IS NOT NULL THEN e.puntaje * 100 / t.puntaje_max END) AS promedio
            FROM tarea_entregas e
            INNER JOIN grupo_tareas t ON t.id = e.tarea_id AND t.grupo_id = ?
            GROUP BY e.estudiante_id
        ");
        $stmt->execute([$grupoId]);
        $mapa = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $f) {
            $mapa[(int) $f["estudiante_id"]] = $f;
        }
        return $mapa;
    }

    public static function totalTareas(PDO $c, int $grupoId): int
    {
        return (int) self::valor($c, "SELECT COUNT(*) FROM grupo_tareas WHERE grupo_id = ?", [$grupoId]);
    }

    /* =========================================================
       ESTUDIANTE: ENTREGAR O REENVIAR
    ========================================================= */

    /**
     * El que llama ya revisó que el estudiante es miembro de un grupo activo.
     * @return true|string
     */
    public static function entregar(PDO $c, array $grupo, array $tarea, int $estudianteId, string $texto, $archivosCampo)
    {
        $texto = trim($texto);
        if (mb_strlen($texto) > self::MAX_TEXTO) {
            return "Tu respuesta puede tener máximo " . self::MAX_TEXTO . " caracteres.";
        }
        $anterior = self::entregaDe($c, (int) $tarea["id"], $estudianteId);
        if ($anterior && $anterior["puntaje"] !== null) {
            return "Tu docente ya calificó esta tarea: no se puede reenviar.";
        }
        $archivos = Archivo::validar($archivosCampo, GrupoModel::GRUPOS_ARCHIVO, self::MAX_ARCHIVOS, 0, false);
        if (!is_array($archivos)) {
            return $archivos;
        }
        if ($texto === "" && !$archivos) {
            return "Escribe tu respuesta o adjunta al menos un archivo.";
        }

        $c->beginTransaction();
        try {
            if ($anterior) {
                // Reenvío: reemplaza todo. "puntaje IS NULL" evita pisar una nota puesta justo ahora.
                $stmt = $c->prepare("
                    UPDATE tarea_entregas e
                    INNER JOIN grupo_tareas t ON t.id = e.tarea_id
                    SET e.texto = ?, e.enviada_en = NOW(), e.envios = e.envios + 1,
                        e.tardia = (t.fecha_entrega IS NOT NULL AND NOW() > t.fecha_entrega)
                    WHERE e.id = ? AND e.puntaje IS NULL
                ");
                $stmt->execute([$texto !== "" ? $texto : null, (int) $anterior["id"]]);
                if ($stmt->rowCount() === 0) {
                    $c->rollBack();
                    return "Tu docente ya calificó esta tarea: no se puede reenviar.";
                }
                $entregaId = (int) $anterior["id"];
                $c->prepare("DELETE FROM archivos WHERE contexto = 'entrega' AND contexto_id = ?")->execute([$entregaId]);
            } else {
                $c->prepare("
                    INSERT INTO tarea_entregas (tarea_id, estudiante_id, texto, tardia)
                    SELECT t.id, ?, ?, (t.fecha_entrega IS NOT NULL AND NOW() > t.fecha_entrega)
                    FROM grupo_tareas t WHERE t.id = ?
                ")->execute([$estudianteId, $texto !== "" ? $texto : null, (int) $tarea["id"]]);
                $entregaId = (int) $c->lastInsertId();
            }
            foreach ($archivos as $a) {
                GrupoModel::guardarArchivo($c, $estudianteId, "entrega", $entregaId, $a);
            }
            $c->commit();
        } catch (PDOException $e) {
            $c->rollBack();
            if (($e->errorInfo[1] ?? 0) === 1062) {   // doble clic: la otra petición ya creó la entrega
                return "Tu entrega ya se estaba enviando. Revisa abajo y reenvíala si hace falta.";
            }
            throw $e;
        } catch (Throwable $e) {
            $c->rollBack();
            throw $e;
        }

        $tardia = (int) self::valor($c, "SELECT tardia FROM tarea_entregas WHERE id = ?", [$entregaId]) === 1;
        Notificador::aUsuario(
            (int) $tarea["docente_id"],
            "tarea_entrega",
            ($anterior ? "Reenvío" : "Entrega") . ($tardia ? " tardía" : "") . " en «" . $tarea["titulo"] . "»",
            ($_SESSION["nombre"] ?? "Un estudiante") . " · grupo «" . $grupo["nombre"] . "»",
            "profesor/index.php?page=grupo&grupo=" . (int) $grupo["id"] . "&tab=tareas&tarea=" . (int) $tarea["id"] . "#entrega-" . $entregaId
        );
        return true;
    }

    /* =========================================================
       DOCENTE: CALIFICAR
    ========================================================= */

    /** @return true|string */
    public static function calificar(PDO $c, array $grupo, array $tarea, int $entregaId, int $docenteId, string $puntajeTexto, string $comentario)
    {
        $puntajeTexto = str_replace(",", ".", trim($puntajeTexto));
        if (!preg_match('/^\d{1,4}(\.\d{1,2})?$/', $puntajeTexto)) {
            return "Escribe la nota como un número (por ejemplo 85 o 85,5).";
        }
        $puntaje = (float) $puntajeTexto;
        if ($puntaje < 0 || $puntaje > (int) $tarea["puntaje_max"]) {
            return "La nota va de 0 a " . (int) $tarea["puntaje_max"] . ".";
        }
        $comentario = trim($comentario);
        if (mb_strlen($comentario) > 2000) {
            return "El comentario puede tener máximo 2000 caracteres.";
        }

        $stmt = $c->prepare("SELECT id, estudiante_id, puntaje FROM tarea_entregas WHERE id = ? AND tarea_id = ? LIMIT 1");
        $stmt->execute([$entregaId, (int) $tarea["id"]]);
        $entrega = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$entrega) {
            return "La entrega ya no existe.";
        }

        $c->prepare("
            UPDATE tarea_entregas SET puntaje = ?, comentario_docente = ?, calificada_en = NOW(), calificada_por = ?
            WHERE id = ?
        ")->execute([$puntaje, $comentario !== "" ? $comentario : null, $docenteId, $entregaId]);

        Notificador::aUsuario(
            (int) $entrega["estudiante_id"],
            "tarea_calificada",
            ($entrega["puntaje"] === null ? "Calificaron" : "Cambió la nota de") . " tu tarea «" . $tarea["titulo"] . "»",
            "Nota: " . self::numero($puntaje) . " de " . (int) $tarea["puntaje_max"] . ($comentario !== "" ? ". " . mb_strimwidth($comentario, 0, 140, "…") : "."),
            "estudiante/index.php?page=grupo&id=" . (int) $grupo["id"] . "&tab=tareas&tarea=" . (int) $tarea["id"]
        );
        return true;
    }

    /* =========================================================
       AYUDANTES
    ========================================================= */

    /** 85 -> "85", 85.5 -> "85,5" */
    public static function numero($valor): string
    {
        $n = round((float) $valor, 2);
        return floor($n) == $n ? (string) (int) $n : rtrim(rtrim(number_format($n, 2, ",", ""), "0"), ",");
    }

    private static function valor(PDO $c, string $sql, array $parametros)
    {
        $stmt = $c->prepare($sql);
        $stmt->execute($parametros);
        return $stmt->fetchColumn();
    }
}
