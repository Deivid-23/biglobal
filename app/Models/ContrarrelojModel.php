<?php

require_once __DIR__ . "/RutaModel.php";
require_once __DIR__ . "/GamificacionModel.php";

/**
 * ContrarrelojModel
 *
 * "Práctica rápida": DURACION segundos para responder la mayor cantidad de
 * ejercicios de lecciones que el estudiante YA completó en ese idioma
 * (tabla ruta_contrarreloj, database/contrarreloj.sql). Reglas:
 *
 * - Solo tipos rápidos (RAPIDOS): nada de memoria, videos ni ejercicios
 *   largos. Hace falta un mínimo de MINIMO ejercicios para jugar.
 * - Se califica en el servidor con RutaModel::calificar, en orden; la
 *   partida vive en la sesión. Un fallo no se repite: pasa al siguiente.
 * - El servidor lleva el tiempo: respuestas que llegan después de
 *   DURACION + GRACIA segundos ya no cuentan y la partida termina.
 * - Al terminar: se guarda la mejor marca y da XP_PRACTICA una vez al día
 *   (día del estudiante), que cuenta para la racha y la meta.
 */
class ContrarrelojModel
{
    public const DURACION = 60;
    public const GRACIA = 3;          // margen por la demora de la red
    public const MINIMO = 5;
    public const MAXIMO = 60;         // ejercicios que se preparan por partida
    public const RAPIDOS = ["opcion", "vf", "completar", "escuchar", "elegir", "error", "traducir"];
    public const MOTIVO_XP = "ruta_contrarreloj";

    public static function disponible(PDO $c): bool
    {
        static $disponible = null;
        if ($disponible === null) {
            try {
                $disponible = RutaModel::disponible($c) && (int) $c->query("
                    SELECT COUNT(*) FROM information_schema.tables
                    WHERE table_schema = DATABASE() AND table_name = 'ruta_contrarreloj'
                ")->fetchColumn() === 1;
            } catch (Throwable $e) {
                $disponible = false;
            }
        }
        return $disponible;
    }

    /** Ejercicios rápidos de lecciones completadas (opción de elegir con videos queda fuera). */
    private static function pozo(PDO $c, int $usuarioId, int $idiomaId, ?int $limite = null): array
    {
        $tipos = implode(",", array_map(fn($t) => "'" . $t . "'", self::RAPIDOS));
        $stmt = $c->prepare("
            SELECT e.* FROM ruta_ejercicios e
            INNER JOIN ruta_progreso p ON p.leccion_id = e.leccion_id AND p.usuario_id = ?
            INNER JOIN ruta_lecciones l ON l.id = e.leccion_id AND l.publicado = 1
            INNER JOIN ruta_unidades u ON u.id = l.unidad_id AND u.publicado = 1
            INNER JOIN ruta_niveles n ON n.id = u.nivel_id AND n.publicado = 1 AND n.idioma_id = ?
            WHERE " . RutaModel::condicionJugable("e") . " AND e.tipo IN ($tipos)
              AND COALESCE(e.video_url, '') = '' AND NOT (e.tipo = 'elegir' AND e.datos LIKE '%http%')
            ORDER BY RAND()" . ($limite ? " LIMIT " . (int) $limite : "")
        );
        $stmt->execute([$usuarioId, $idiomaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** ¿Tiene suficientes ejercicios para jugar? (cuántos hay, hasta MAXIMO) */
    public static function cuantos(PDO $c, int $usuarioId, int $idiomaId): int
    {
        return self::disponible($c) ? count(self::pozo($c, $usuarioId, $idiomaId, self::MAXIMO)) : 0;
    }

    public static function marca(PDO $c, int $usuarioId, int $idiomaId): ?array
    {
        if (!self::disponible($c)) {
            return null;
        }
        $stmt = $c->prepare("SELECT mejor, mejor_en, partidas FROM ruta_contrarreloj WHERE usuario_id = ? AND idioma_id = ?");
        $stmt->execute([$usuarioId, $idiomaId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Prepara una partida. Devuelve los ejercicios ([] si no hay suficientes). $ahora solo en las pruebas. */
    public static function iniciar(PDO $c, int $usuarioId, int $idiomaId, ?int $ahora = null): array
    {
        $ejercicios = self::disponible($c) ? self::pozo($c, $usuarioId, $idiomaId, self::MAXIMO) : [];
        if (count($ejercicios) < self::MINIMO) {
            unset($_SESSION["ruta_contrarreloj"]);
            return [];
        }
        $_SESSION["ruta_contrarreloj"] = [
            "idioma"      => $idiomaId,
            "cola"        => array_map(fn($e) => (int) $e["id"], $ejercicios),
            "inicio"      => null,          // empieza a contar con la primera respuesta o con "empezar"
            "aciertos"    => 0,
            "respondidas" => 0,
        ];
        return $ejercicios;
    }

    /** El reloj arranca cuando el estudiante pulsa "Empezar" (no al cargar la página). */
    public static function arrancar(int $idiomaId, ?int $ahora = null): array
    {
        $x = $_SESSION["ruta_contrarreloj"] ?? null;
        if (!$x || (int) $x["idioma"] !== $idiomaId) {
            return ["error" => "La práctica se reinició. Vuelve a abrirla desde tu ruta."];
        }
        if ($x["inicio"] === null) {
            $x["inicio"] = $ahora ?? time();
            $_SESSION["ruta_contrarreloj"] = $x;
        }
        return ["ok" => true, "segundos" => self::DURACION];
    }

    /** Respuesta a UN ejercicio. Si se acabó el tiempo o la cola, devuelve "resultado". */
    public static function responder(PDO $c, int $usuarioId, int $idiomaId, int $ejercicioId, $respuesta, ?int $ahora = null, bool $saltar = false): array
    {
        $ahora = $ahora ?? time();
        $x = $_SESSION["ruta_contrarreloj"] ?? null;
        if (!$x || (int) $x["idioma"] !== $idiomaId || $x["inicio"] === null) {
            return ["error" => "La práctica se reinició. Vuelve a abrirla desde tu ruta."];
        }
        if ($ahora - $x["inicio"] > self::DURACION + self::GRACIA) {
            return ["tiempo" => true, "siguiente" => null, "resultado" => self::terminar($c, $usuarioId)];
        }
        if (!$x["cola"] || (int) $x["cola"][0] !== $ejercicioId) {
            return ["error" => "Responde los ejercicios en orden.", "siguiente" => $x["cola"][0] ?? null];
        }
        $e = RutaModel::ejercicio($c, $ejercicioId);
        if ($saltar) {
            // «No puedo escuchar ahora»: pasa al siguiente sin sumar ni restar
            $r = $e ? RutaModel::saltar($e) : ["error" => "Este ejercicio no se puede saltar."];
            if (isset($r["error"])) {
                return $r + ["siguiente" => $x["cola"][0]];
            }
            array_shift($x["cola"]);
        } else {
            $r = $e ? RutaModel::calificar($e, $respuesta) : ["correcto" => false, "solucion" => ""];
            array_shift($x["cola"]);
            $x["respondidas"]++;
            if ($r["correcto"]) {
                $x["aciertos"]++;
            }
        }
        $_SESSION["ruta_contrarreloj"] = $x;
        $r["aciertos"] = $x["aciertos"];
        $r["siguiente"] = $x["cola"][0] ?? null;
        if ($r["siguiente"] === null) {
            $r["resultado"] = self::terminar($c, $usuarioId);
        }
        return $r;
    }

    /**
     * Cierra la partida (se acabó el tiempo, la cola, o el estudiante salió):
     * guarda la marca y da el XP del día. Sin partida en la sesión devuelve error.
     */
    public static function terminar(PDO $c, int $usuarioId): array
    {
        $x = $_SESSION["ruta_contrarreloj"] ?? null;
        unset($_SESSION["ruta_contrarreloj"]);
        if (!$x) {
            return ["error" => "No hay una práctica en curso."];
        }
        $idiomaId = (int) $x["idioma"];
        $aciertos = (int) $x["aciertos"];
        $antes = self::marca($c, $usuarioId, $idiomaId);
        $record = $aciertos > 0 && $aciertos > (int) ($antes["mejor"] ?? 0);
        $c->prepare("
            INSERT INTO ruta_contrarreloj (usuario_id, idioma_id, mejor, mejor_en, partidas) VALUES (?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE partidas = partidas + 1,
                mejor_en = IF(VALUES(mejor) > mejor, VALUES(mejor_en), mejor_en),
                mejor = GREATEST(mejor, VALUES(mejor))
        ")->execute([$usuarioId, $idiomaId, $aciertos, $aciertos > 0 ? date("Y-m-d H:i:s") : null]);

        $xp = 0;
        $racha = null;
        if ($x["respondidas"] > 0 && GamificacionModel::disponible($c) && !self::yaDioXpHoy($c, $usuarioId)) {
            $xp = RutaModel::XP_PRACTICA;
            $c->beginTransaction();
            try {
                $racha = GamificacionModel::registrarActividad($c, $usuarioId, $xp, self::MOTIVO_XP, $idiomaId);
                $c->commit();
            } catch (Throwable $e) {
                $c->rollBack();
                throw $e;
            }
        }
        return [
            "aciertos"    => $aciertos,
            "respondidas" => (int) $x["respondidas"],
            "mejor"       => max($aciertos, (int) ($antes["mejor"] ?? 0)),
            "record"      => $record,
            "xp"          => $xp,
            "racha"       => $racha,
            "meta"        => $xp > 0 && GamificacionModel::conMeta($c) ? GamificacionModel::estadoMeta($c, $usuarioId, $xp) : null,
        ];
    }

    private static function yaDioXpHoy(PDO $c, int $usuarioId): bool
    {
        $stmt = $c->prepare("SELECT MAX(fecha) FROM xp_movimientos WHERE usuario_id = ? AND motivo = ?");
        $stmt->execute([$usuarioId, self::MOTIVO_XP]);
        $ultima = $stmt->fetchColumn();
        if (!$ultima) {
            return false;
        }
        $zona = GamificacionModel::zonaDe($c, $usuarioId);
        return GamificacionModel::diaEn((string) $ultima, $zona) === GamificacionModel::hoy($zona);
    }
}
