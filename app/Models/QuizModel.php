<?php

/**
 * QuizModel
 *
 * Actividades que se pueden resolver (opción múltiple).
 * Usa las tablas de database/actividades_resolubles.sql:
 *   actividad_preguntas, actividad_opciones, actividad_intentos
 *
 * Reglas:
 * - Solo las actividades de tipo Quiz, Ejercicio o Examen llevan preguntas.
 *   Una actividad es "resoluble" si es de uno de esos tipos y tiene al
 *   menos una pregunta.
 * - Se aprueba con el 70 % o más. Para completar una lección hay que
 *   aprobar cada actividad resoluble de la lección.
 * - Se califica SIEMPRE en el servidor: al estudiante nunca le llega
 *   cuál opción es la correcta antes de enviar.
 * - Una pregunta no se borra si la actividad ya tiene intentos (se
 *   puede editar).
 *
 * Lo usan el admin (AdminController), el docente (src/profesor) y el
 * estudiante (EstudianteController), así las reglas son las mismas.
 */
class QuizModel
{
    public const TIPOS_CON_PREGUNTAS = ["Quiz", "Ejercicio", "Examen"];

    public const PORCENTAJE_APROBAR = 70;

    public const MIN_OPCIONES = 2;
    public const MAX_OPCIONES = 4;

    /** XP la primera vez que se aprueba cada actividad */
    public const XP_ACTIVIDAD = 5;

    /* =========================================================
       ¿Existen las tablas? (se pregunta una sola vez por visita)
    ========================================================= */
    public static function disponible(PDO $c): bool
    {
        static $disponible = null;

        if ($disponible === null) {
            $stmt = $c->query("
                SELECT COUNT(*) FROM information_schema.tables
                WHERE table_schema = DATABASE()
                  AND table_name IN ('actividad_preguntas', 'actividad_opciones', 'actividad_intentos')
            ");
            $disponible = (int) $stmt->fetchColumn() === 3;
        }

        return $disponible;
    }

    public static function tipoConPreguntas(?string $tipo): bool
    {
        return in_array(trim((string) $tipo), self::TIPOS_CON_PREGUNTAS, true);
    }

    public static function aprueba(int $aciertos, int $total): bool
    {
        return $total > 0 && $aciertos * 100 >= self::PORCENTAJE_APROBAR * $total;
    }

    /* =========================================================
       EDITOR (admin y docente)
    ========================================================= */

    /** Actividad con su lección y curso (para el encabezado del editor). */
    public static function actividad(PDO $c, int $actividadId)
    {
        $stmt = $c->prepare("
            SELECT a.id, a.titulo, a.tipo, a.descripcion, a.leccion_id,
                   l.titulo AS leccion, l.curso_id, cu.nombre AS curso
            FROM actividades a
            INNER JOIN lecciones l ON l.id = a.leccion_id
            INNER JOIN cursos cu ON cu.id = l.curso_id
            WHERE a.id = ? LIMIT 1
        ");
        $stmt->execute([$actividadId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Preguntas de una actividad con sus opciones.
     * $conCorrecta = false para el estudiante (no se manda es_correcta).
     */
    public static function preguntas(PDO $c, int $actividadId, bool $conCorrecta = true): array
    {
        $stmt = $c->prepare("
            SELECT id, enunciado, orden FROM actividad_preguntas
            WHERE actividad_id = ? ORDER BY orden ASC, id ASC
        ");
        $stmt->execute([$actividadId]);
        $preguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$preguntas) {
            return [];
        }

        $ids = array_map("intval", array_column($preguntas, "id"));
        $marcas = implode(",", array_fill(0, count($ids), "?"));
        $stmt = $c->prepare("
            SELECT id, pregunta_id, texto, es_correcta FROM actividad_opciones
            WHERE pregunta_id IN ($marcas) ORDER BY orden ASC, id ASC
        ");
        $stmt->execute($ids);

        $opciones = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $o) {
            $fila = ["id" => (int) $o["id"], "texto" => $o["texto"]];
            if ($conCorrecta) {
                $fila["es_correcta"] = (int) $o["es_correcta"] === 1;
            }
            $opciones[(int) $o["pregunta_id"]][] = $fila;
        }

        foreach ($preguntas as &$p) {
            $p["id"] = (int) $p["id"];
            $p["opciones"] = $opciones[$p["id"]] ?? [];
        }
        unset($p);

        return $preguntas;
    }

    /** Número de preguntas por actividad: [actividad_id => n]. */
    public static function contarPreguntas(PDO $c, array $actividadIds): array
    {
        $ids = array_values(array_filter(array_map("intval", $actividadIds)));
        if (!$ids) {
            return [];
        }
        $marcas = implode(",", array_fill(0, count($ids), "?"));
        $stmt = $c->prepare("
            SELECT actividad_id, COUNT(*) AS n FROM actividad_preguntas
            WHERE actividad_id IN ($marcas) GROUP BY actividad_id
        ");
        $stmt->execute($ids);
        return array_map("intval", array_column($stmt->fetchAll(PDO::FETCH_ASSOC), "n", "actividad_id"));
    }

    public static function contarIntentos(PDO $c, int $actividadId): int
    {
        $stmt = $c->prepare("SELECT COUNT(*) FROM actividad_intentos WHERE actividad_id = ?");
        $stmt->execute([$actividadId]);
        return (int) $stmt->fetchColumn();
    }

    /** id de la actividad a la que pertenece la pregunta (0 si no existe). */
    public static function actividadDePregunta(PDO $c, int $preguntaId): int
    {
        $stmt = $c->prepare("SELECT actividad_id FROM actividad_preguntas WHERE id = ? LIMIT 1");
        $stmt->execute([$preguntaId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Lee y valida el formulario de una pregunta.
     * Espera: enunciado, orden, opciones[] (textos) y correcta (índice).
     * @return array|string
     */
    public static function datosPregunta(array $post)
    {
        $enunciado = trim((string) ($post["enunciado"] ?? ""));
        if ($enunciado === "" || mb_strlen($enunciado) > 1000) {
            return "Escribe la pregunta (máximo 1000 caracteres).";
        }

        $textos = is_array($post["opciones"] ?? null) ? array_slice($post["opciones"], 0, self::MAX_OPCIONES) : [];
        $correcta = filter_var($post["correcta"] ?? null, FILTER_VALIDATE_INT);

        $opciones = [];
        $indiceCorrecta = null;
        foreach ($textos as $i => $texto) {
            $texto = trim((string) $texto);
            if ($texto === "") {
                continue;
            }
            if (mb_strlen($texto) > 255) {
                return "Cada opción puede tener máximo 255 caracteres.";
            }
            if ((int) $i === $correcta) {
                $indiceCorrecta = count($opciones);
            }
            $opciones[] = $texto;
        }

        if (count($opciones) < self::MIN_OPCIONES) {
            return "Escribe al menos " . self::MIN_OPCIONES . " opciones.";
        }
        if (count(array_unique(array_map("mb_strtolower", $opciones))) !== count($opciones)) {
            return "Hay opciones repetidas.";
        }
        if ($indiceCorrecta === null) {
            return "Marca cuál opción es la correcta (y que no esté vacía).";
        }

        return [
            "enunciado" => $enunciado,
            "orden"     => max(1, min(999, (int) ($post["orden"] ?? 1))),
            "opciones"  => $opciones,
            "correcta"  => $indiceCorrecta,
        ];
    }

    /**
     * Crea ($preguntaId = 0) o reemplaza una pregunta con sus opciones.
     * Las opciones se reescriben completas: los intentos guardan solo
     * aciertos y total, así que no se pierde nada de los estudiantes.
     */
    public static function guardarPregunta(PDO $c, int $actividadId, int $preguntaId, array $d): void
    {
        $c->beginTransaction();
        try {
            if ($preguntaId > 0) {
                $c->prepare("UPDATE actividad_preguntas SET enunciado = ?, orden = ? WHERE id = ? AND actividad_id = ?")
                  ->execute([$d["enunciado"], $d["orden"], $preguntaId, $actividadId]);
                $c->prepare("DELETE FROM actividad_opciones WHERE pregunta_id = ?")->execute([$preguntaId]);
            } else {
                $c->prepare("INSERT INTO actividad_preguntas (actividad_id, enunciado, orden) VALUES (?, ?, ?)")
                  ->execute([$actividadId, $d["enunciado"], $d["orden"]]);
                $preguntaId = (int) $c->lastInsertId();
            }

            $stmt = $c->prepare("INSERT INTO actividad_opciones (pregunta_id, texto, es_correcta, orden) VALUES (?, ?, ?, ?)");
            foreach ($d["opciones"] as $i => $texto) {
                $stmt->execute([$preguntaId, $texto, $i === $d["correcta"] ? 1 : 0, $i + 1]);
            }

            $c->commit();
        } catch (Throwable $e) {
            $c->rollBack();
            throw $e;
        }
    }

    /** @return string|null Motivo si no se pudo borrar. */
    public static function eliminarPregunta(PDO $c, int $actividadId, int $preguntaId): ?string
    {
        $intentos = self::contarIntentos($c, $actividadId);
        if ($intentos > 0) {
            return "Esta actividad ya tiene {$intentos} " . ($intentos === 1 ? "intento" : "intentos")
                . " de estudiantes, así que sus preguntas no se borran. Puedes editarla.";
        }
        $c->prepare("DELETE FROM actividad_preguntas WHERE id = ? AND actividad_id = ?")
          ->execute([$preguntaId, $actividadId]);
        return null;
    }

    /* =========================================================
       ESTUDIANTE
    ========================================================= */

    /**
     * Estado del estudiante en cada actividad resoluble de la lección:
     * [actividad_id => ["preguntas" => [...], "aprobada" => bool, "mejor" => [aciertos, total] | null]]
     * Las actividades que no son resolubles no aparecen.
     */
    public static function estadoLeccion(PDO $c, array $actividades, int $usuarioId): array
    {
        $estado = [];
        foreach ($actividades as $a) {
            if (!self::tipoConPreguntas($a["tipo"] ?? "")) {
                continue;
            }
            $preguntas = self::preguntas($c, (int) $a["id"], false);
            if (!$preguntas) {
                continue;
            }
            // Opciones en otro orden para cada estudiante (y estable al recargar),
            // así la correcta no queda siempre en el mismo lugar
            foreach ($preguntas as &$p) {
                usort($p["opciones"], fn($x, $y) => strcmp(
                    md5($usuarioId . ":" . $x["id"]), md5($usuarioId . ":" . $y["id"])
                ));
            }
            unset($p);
            $estado[(int) $a["id"]] = ["preguntas" => $preguntas] + self::mejorIntento($c, (int) $a["id"], $usuarioId);
        }
        return $estado;
    }

    /** ["aprobada" => bool, "mejor" => [aciertos, total] | null] */
    public static function mejorIntento(PDO $c, int $actividadId, int $usuarioId): array
    {
        $stmt = $c->prepare("
            SELECT aciertos, total, aprobado FROM actividad_intentos
            WHERE actividad_id = ? AND usuario_id = ?
            ORDER BY aprobado DESC, (aciertos / NULLIF(total, 0)) DESC, id DESC
            LIMIT 1
        ");
        $stmt->execute([$actividadId, $usuarioId]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            "aprobada" => $fila && (int) $fila["aprobado"] === 1,
            "mejor"    => $fila ? [(int) $fila["aciertos"], (int) $fila["total"]] : null,
        ];
    }

    /** Cuántas actividades resolubles de la lección le faltan por aprobar. */
    public static function pendientesEnLeccion(PDO $c, int $leccionId, int $usuarioId): int
    {
        $marcas = implode(",", array_fill(0, count(self::TIPOS_CON_PREGUNTAS), "?"));
        $stmt = $c->prepare("
            SELECT COUNT(*) FROM actividades a
            WHERE a.leccion_id = ?
              AND a.tipo IN ($marcas)
              AND EXISTS (SELECT 1 FROM actividad_preguntas p WHERE p.actividad_id = a.id)
              AND NOT EXISTS (SELECT 1 FROM actividad_intentos i
                              WHERE i.actividad_id = a.id AND i.usuario_id = ? AND i.aprobado = 1)
        ");
        $stmt->execute(array_merge([$leccionId], self::TIPOS_CON_PREGUNTAS, [$usuarioId]));
        return (int) $stmt->fetchColumn();
    }

    /**
     * Califica las respuestas ([pregunta_id => opcion_id]).
     * Una pregunta sin responder cuenta como incorrecta.
     * Devuelve aciertos, total y el detalle por pregunta (elegida y correcta).
     */
    public static function calificar(PDO $c, int $actividadId, array $respuestas): array
    {
        $preguntas = self::preguntas($c, $actividadId, true);

        $aciertos = 0;
        $detalle = [];
        foreach ($preguntas as $p) {
            $correcta = null;
            foreach ($p["opciones"] as $o) {
                if ($o["es_correcta"]) {
                    $correcta = $o["id"];
                }
            }
            $elegida = filter_var($respuestas[$p["id"]] ?? null, FILTER_VALIDATE_INT) ?: null;
            // Solo vale una opción que sea de esta misma pregunta
            if ($elegida !== null && !in_array($elegida, array_column($p["opciones"], "id"), true)) {
                $elegida = null;
            }
            $bien = $elegida !== null && $elegida === $correcta;
            if ($bien) {
                $aciertos++;
            }
            $detalle[$p["id"]] = ["elegida" => $elegida, "correcta" => $correcta, "bien" => $bien];
        }

        return ["aciertos" => $aciertos, "total" => count($preguntas), "detalle" => $detalle];
    }

    /** ¿Ya la había aprobado antes? (para no repetir el XP) */
    public static function yaAprobada(PDO $c, int $actividadId, int $usuarioId): bool
    {
        $stmt = $c->prepare("SELECT 1 FROM actividad_intentos WHERE actividad_id = ? AND usuario_id = ? AND aprobado = 1 LIMIT 1");
        $stmt->execute([$actividadId, $usuarioId]);
        return (bool) $stmt->fetchColumn();
    }

    public static function guardarIntento(PDO $c, int $usuarioId, int $actividadId, int $aciertos, int $total, bool $aprobado): void
    {
        $c->prepare("
            INSERT INTO actividad_intentos (usuario_id, actividad_id, aciertos, total, aprobado)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([$usuarioId, $actividadId, $aciertos, $total, $aprobado ? 1 : 0]);
    }
}
