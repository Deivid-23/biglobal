<?php

/**
 * RutaEstadisticasModel
 *
 * Estadísticas de la ruta (tablas de database/ruta_estadisticas.sql).
 * - Registro: RutaModel avisa cuando se abre una lección (abrirIntento), cada
 *   respuesta (respuesta) y el final (terminar). Si algo falla aquí, la lección
 *   sigue igual: el error va al log y ya.
 * - Lectura: Admin → Ruta → Estadísticas (AdminController::estadisticas).
 *   Solo totales; nunca estudiantes por nombre.
 *
 * Definiciones:
 * - Lección "empezada": intento con al menos una respuesta (abrir y cerrar sin
 *   responder no cuenta, así recargar la página no infla el abandono).
 * - "Abandonada": empezada, sin terminar y abierta hace más de una hora (la
 *   última hora puede estar todavía en curso).
 * - "Acierto a la primera": de las primeras respuestas a un ejercicio en cada
 *   intento, cuántas fueron correctas. Solo se muestran ejercicios con
 *   MIN_RESPUESTAS o más, para no sacar conclusiones con muy pocos datos.
 */
class RutaEstadisticasModel
{
    public const MIN_RESPUESTAS = 10;
    public const MIN_EMPEZADAS = 5;
    public const PERIODOS = ["30" => "Últimos 30 días", "90" => "Últimos 90 días", "todo" => "Todo"];

    public static function disponible(PDO $c): bool
    {
        static $disponible = null;
        if ($disponible === null) {
            try {
                $disponible = (int) $c->query("
                    SELECT COUNT(*) FROM information_schema.tables
                    WHERE table_schema = DATABASE() AND table_name IN ('ruta_intentos', 'ruta_respuestas')
                ")->fetchColumn() === 2;
            } catch (Throwable $e) {
                $disponible = false;
            }
        }
        return $disponible;
    }

    /* =========================================================
       REGISTRO (lo llama RutaModel)
    ========================================================= */

    /** Id del intento nuevo, o null si no hay tablas o falló. */
    public static function abrirIntento(PDO $c, int $usuarioId, int $leccionId): ?int
    {
        if (!self::disponible($c)) {
            return null;
        }
        try {
            $c->prepare("INSERT INTO ruta_intentos (usuario_id, leccion_id) VALUES (?, ?)")->execute([$usuarioId, $leccionId]);
            return (int) $c->lastInsertId();
        } catch (Throwable $e) {
            error_log("[BiGlobal][ruta_estadisticas][abrir] " . $e->getMessage());
            return null;
        }
    }

    public static function respuesta(PDO $c, ?int $intentoId, int $ejercicioId, bool $correcto, bool $primera): void
    {
        if (!$intentoId || !self::disponible($c)) {
            return;
        }
        try {
            $c->prepare("INSERT INTO ruta_respuestas (intento_id, ejercicio_id, correcto, primera) VALUES (?, ?, ?, ?)")
              ->execute([$intentoId, $ejercicioId, $correcto ? 1 : 0, $primera ? 1 : 0]);
        } catch (Throwable $e) {
            error_log("[BiGlobal][ruta_estadisticas][respuesta] " . $e->getMessage());
        }
    }

    public static function terminar(PDO $c, ?int $intentoId, int $porcentaje): void
    {
        if (!$intentoId || !self::disponible($c)) {
            return;
        }
        try {
            $c->prepare("UPDATE ruta_intentos SET terminado = NOW(), porcentaje = ? WHERE id = ? AND terminado IS NULL")
              ->execute([max(0, min(100, $porcentaje)), $intentoId]);
        } catch (Throwable $e) {
            error_log("[BiGlobal][ruta_estadisticas][terminar] " . $e->getMessage());
        }
    }

    /* =========================================================
       LECTURA (Admin)
    ========================================================= */

    /** Condición común (alias i, n) y sus parámetros: idioma, nivel opcional y período. */
    private static function filtro(int $idiomaId, ?int $nivelId, string $periodo): array
    {
        $sql = "n.idioma_id = ?";
        $params = [$idiomaId];
        if ($nivelId) {
            $sql .= " AND n.id = ?";
            $params[] = $nivelId;
        }
        if (isset(self::PERIODOS[$periodo]) && $periodo !== "todo") {
            $sql .= " AND i.iniciado >= NOW() - INTERVAL " . (int) $periodo . " DAY";
        }
        return [$sql, $params];
    }

    private const UNIR = "
        FROM ruta_intentos i
        INNER JOIN ruta_lecciones l ON l.id = i.leccion_id
        INNER JOIN ruta_unidades u ON u.id = l.unidad_id
        INNER JOIN ruta_niveles n ON n.id = u.nivel_id";

    /** Activos 7 y 30 días, empezadas, terminadas, abandonadas y % de abandono. */
    public static function resumen(PDO $c, int $idiomaId, ?int $nivelId, string $periodo): array
    {
        [$donde, $params] = self::filtro($idiomaId, $nivelId, $periodo);
        $stmt = $c->prepare("
            SELECT
                COUNT(DISTINCT CASE WHEN i.iniciado >= NOW() - INTERVAL 7 DAY THEN i.usuario_id END) AS activos7,
                COUNT(DISTINCT CASE WHEN i.iniciado >= NOW() - INTERVAL 30 DAY THEN i.usuario_id END) AS activos30,
                COUNT(*) AS empezadas,
                SUM(i.terminado IS NOT NULL) AS terminadas,
                SUM(i.terminado IS NULL AND i.iniciado < NOW() - INTERVAL 1 HOUR) AS abandonadas
            " . self::UNIR . "
            WHERE $donde AND EXISTS (SELECT 1 FROM ruta_respuestas r WHERE r.intento_id = i.id)
        ");
        $stmt->execute($params);
        $f = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $empezadas = (int) ($f["empezadas"] ?? 0);
        $abandonadas = (int) ($f["abandonadas"] ?? 0);
        return [
            "activos7"    => (int) ($f["activos7"] ?? 0),
            "activos30"   => (int) ($f["activos30"] ?? 0),
            "empezadas"   => $empezadas,
            "terminadas"  => (int) ($f["terminadas"] ?? 0),
            "abandonadas" => $abandonadas,
            "abandono"    => $empezadas > 0 ? (int) round($abandonadas * 100 / $empezadas) : null,
        ];
    }

    /** Ejercicios con menos acierto a la primera (mínimo MIN_RESPUESTAS). */
    public static function ejerciciosDificiles(PDO $c, int $idiomaId, ?int $nivelId, string $periodo, int $limite = 15): array
    {
        [$donde, $params] = self::filtro($idiomaId, $nivelId, $periodo);
        $stmt = $c->prepare("
            SELECT e.id, e.tipo, e.enunciado, l.id AS leccion_id, l.titulo AS leccion, n.codigo AS nivel,
                   COUNT(*) AS respuestas, SUM(r.correcto) AS aciertos
            " . self::UNIR . "
            INNER JOIN ruta_respuestas r ON r.intento_id = i.id AND r.primera = 1
            INNER JOIN ruta_ejercicios e ON e.id = r.ejercicio_id
            WHERE $donde
            GROUP BY e.id, e.tipo, e.enunciado, l.id, l.titulo, n.codigo
            HAVING COUNT(*) >= " . self::MIN_RESPUESTAS . "
            ORDER BY SUM(r.correcto) / COUNT(*) ASC, COUNT(*) DESC
            LIMIT " . max(1, min(50, $limite))
        );
        $stmt->execute($params);
        return array_map(fn($f) => $f + ["acierto" => (int) round($f["aciertos"] * 100 / max(1, $f["respuestas"]))], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Lecciones con más abandono (mínimo MIN_EMPEZADAS empezadas) y el ejercicio
     * que más gente respondió de último antes de dejarla.
     */
    public static function leccionesAbandono(PDO $c, int $idiomaId, ?int $nivelId, string $periodo, int $limite = 15): array
    {
        [$donde, $params] = self::filtro($idiomaId, $nivelId, $periodo);
        $stmt = $c->prepare("
            SELECT l.id, l.titulo, n.codigo AS nivel, u.titulo AS unidad,
                   COUNT(*) AS empezadas,
                   SUM(i.terminado IS NOT NULL) AS terminadas,
                   SUM(i.terminado IS NULL AND i.iniciado < NOW() - INTERVAL 1 HOUR) AS abandonadas
            " . self::UNIR . "
            WHERE $donde AND EXISTS (SELECT 1 FROM ruta_respuestas r WHERE r.intento_id = i.id)
            GROUP BY l.id, l.titulo, n.codigo, u.titulo
            HAVING COUNT(*) >= " . self::MIN_EMPEZADAS . " AND SUM(i.terminado IS NULL AND i.iniciado < NOW() - INTERVAL 1 HOUR) > 0
            ORDER BY SUM(i.terminado IS NULL AND i.iniciado < NOW() - INTERVAL 1 HOUR) / COUNT(*) DESC, COUNT(*) DESC
            LIMIT " . max(1, min(50, $limite))
        );
        $stmt->execute($params);
        $lecciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$lecciones) {
            return [];
        }

        // Último ejercicio respondido en cada intento abandonado de esas lecciones
        $ids = array_map(fn($l) => (int) $l["id"], $lecciones);
        $marcas = implode(",", array_fill(0, count($ids), "?"));
        $stmt = $c->prepare("
            SELECT i.leccion_id,
                   (SELECT r.ejercicio_id FROM ruta_respuestas r WHERE r.intento_id = i.id ORDER BY r.id DESC LIMIT 1) AS ultimo
            " . self::UNIR . "
            WHERE $donde AND i.leccion_id IN ($marcas) AND i.terminado IS NULL AND i.iniciado < NOW() - INTERVAL 1 HOUR
        ");
        $stmt->execute(array_merge($params, $ids));
        $conteo = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $f) {
            if ($f["ultimo"] !== null) {
                $conteo[(int) $f["leccion_id"]][(int) $f["ultimo"]] = ($conteo[(int) $f["leccion_id"]][(int) $f["ultimo"]] ?? 0) + 1;
            }
        }
        $enunciados = [];
        $todos = array_unique(array_merge([], ...array_map("array_keys", array_values($conteo))));
        if ($todos) {
            $m = implode(",", array_fill(0, count($todos), "?"));
            $stmt = $c->prepare("SELECT id, tipo, enunciado FROM ruta_ejercicios WHERE id IN ($m)");
            $stmt->execute(array_values($todos));
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $e) {
                $enunciados[(int) $e["id"]] = $e;
            }
        }
        foreach ($lecciones as &$l) {
            $lista = $conteo[(int) $l["id"]] ?? [];
            arsort($lista);
            $ultimo = $lista ? (int) array_key_first($lista) : null;
            $l["abandono"] = (int) round($l["abandonadas"] * 100 / max(1, $l["empezadas"]));
            $l["donde"] = $ultimo !== null && isset($enunciados[$ultimo]) ? $enunciados[$ultimo] + ["veces" => $lista[$ultimo]] : null;
        }
        unset($l);
        return $lecciones;
    }

    /**
     * Avance por nivel, con todo lo guardado en ruta_progreso (sirve desde ya):
     * cuántos empezaron el nivel (al menos una lección completada) y cuántos lo terminaron.
     */
    public static function avancePorNivel(PDO $c, int $idiomaId): array
    {
        $niveles = $c->prepare("
            SELECT n.id, n.codigo, n.nombre,
                   (SELECT COUNT(*) FROM ruta_lecciones l INNER JOIN ruta_unidades u ON u.id = l.unidad_id AND u.publicado = 1
                    WHERE u.nivel_id = n.id AND l.publicado = 1
                      AND EXISTS (SELECT 1 FROM ruta_ejercicios e WHERE e.leccion_id = l.id AND e.activo = 1
                                  AND (e.tipo NOT IN ('sena', 'deletreo') OR COALESCE(e.video_url, '') <> ''))) AS jugables
            FROM ruta_niveles n WHERE n.idioma_id = ? AND n.publicado = 1 ORDER BY n.orden
        ");
        $niveles->execute([$idiomaId]);
        $porUsuario = $c->prepare("
            SELECT u.nivel_id, p.usuario_id, COUNT(*) AS hechas
            FROM ruta_progreso p
            INNER JOIN ruta_lecciones l ON l.id = p.leccion_id AND l.publicado = 1
            INNER JOIN ruta_unidades u ON u.id = l.unidad_id AND u.publicado = 1
            INNER JOIN ruta_niveles n ON n.id = u.nivel_id AND n.idioma_id = ?
            GROUP BY u.nivel_id, p.usuario_id
        ");
        $porUsuario->execute([$idiomaId]);
        $hechas = [];
        foreach ($porUsuario->fetchAll(PDO::FETCH_ASSOC) as $f) {
            $hechas[(int) $f["nivel_id"]][] = (int) $f["hechas"];
        }
        $resultado = [];
        foreach ($niveles->fetchAll(PDO::FETCH_ASSOC) as $n) {
            $lista = $hechas[(int) $n["id"]] ?? [];
            $jugables = (int) $n["jugables"];
            $resultado[] = $n + [
                "empezaron"  => count($lista),
                "terminaron" => $jugables > 0 ? count(array_filter($lista, fn($h) => $h >= $jugables)) : 0,
            ];
        }
        return $resultado;
    }
}
