<?php

require_once __DIR__ . "/RutaModel.php";
require_once __DIR__ . "/GamificacionModel.php";

/**
 * RepasoModel
 *
 * Repaso de errores de la ruta (tabla ruta_repaso, database/ruta_repaso.sql).
 * Lo usan RutaModel::responder (anota cada fallo en una lección) y el
 * estudiante (EstudianteController::repaso y repasoResponder). Reglas:
 *
 * - Un ejercicio entra a la lista cuando el estudiante lo falla en una lección
 *   de la ruta (aunque luego lo corrija en la misma lección). El examen de
 *   ubicación no cuenta.
 * - El repaso es por idioma: hasta MAX ejercicios pendientes, los más viejos
 *   primero. Solo ejercicios activos, de lecciones publicadas y que se puedan
 *   jugar (los de seña necesitan video).
 * - Acertar en el repaso lo resuelve (sale de la lista); fallar lo deja.
 * - Se califica en el servidor con RutaModel::calificar; el intento vive en
 *   la sesión y obliga a responder en orden. El navegador no recibe respuestas.
 * - Terminar un repaso da XP_PRACTICA una vez al día (día del estudiante) y
 *   cuenta para la racha.
 */
class RepasoModel
{
    public const MAX = 10;
    public const MOTIVO_XP = "ruta_repaso";

    public static function disponible(PDO $c): bool
    {
        static $disponible = null;
        if ($disponible === null) {
            try {
                $disponible = RutaModel::disponible($c) && (int) $c->query("
                    SELECT COUNT(*) FROM information_schema.tables
                    WHERE table_schema = DATABASE() AND table_name = 'ruta_repaso'
                ")->fetchColumn() === 1;
            } catch (Throwable $e) {
                $disponible = false;
            }
        }
        return $disponible;
    }

    /** Lo llama RutaModel::responder cuando falla un ejercicio. Nunca rompe la lección. */
    public static function anotarFallo(PDO $c, int $usuarioId, int $ejercicioId): void
    {
        if (!self::disponible($c)) {
            return;
        }
        try {
            $c->prepare("
                INSERT INTO ruta_repaso (usuario_id, ejercicio_id, fallado_en) VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE fallado_en = NOW(), resuelto_en = NULL
            ")->execute([$usuarioId, $ejercicioId]);
        } catch (Throwable $e) {
            error_log("[BiGlobal][repaso][anotar] " . $e->getMessage());
        }
    }

    /** Condición de "pendiente y se puede jugar" para el idioma (alias r, e, l, u, n). */
    private const PENDIENTE = "
        FROM ruta_repaso r
        INNER JOIN ruta_ejercicios e ON e.id = r.ejercicio_id AND e.activo = 1
                                     AND (e.tipo NOT IN ('sena', 'deletreo') OR COALESCE(e.video_url, '') <> '')
        INNER JOIN ruta_lecciones l ON l.id = e.leccion_id AND l.publicado = 1
        INNER JOIN ruta_unidades u ON u.id = l.unidad_id AND u.publicado = 1
        INNER JOIN ruta_niveles n ON n.id = u.nivel_id AND n.publicado = 1 AND n.idioma_id = ?
        WHERE r.usuario_id = ? AND r.resuelto_en IS NULL";

    /** Cuántos ejercicios tiene por repasar en ese idioma. */
    public static function pendientes(PDO $c, int $usuarioId, int $idiomaId): int
    {
        if (!self::disponible($c)) {
            return 0;
        }
        $stmt = $c->prepare("SELECT COUNT(*) " . self::PENDIENTE);
        $stmt->execute([$idiomaId, $usuarioId]);
        return (int) $stmt->fetchColumn();
    }

    /** Empieza un repaso: hasta MAX pendientes, los más viejos primero. Devuelve los ejercicios. */
    public static function iniciar(PDO $c, int $usuarioId, int $idiomaId): array
    {
        if (!self::disponible($c)) {
            return [];
        }
        $stmt = $c->prepare("SELECT e.* " . self::PENDIENTE . " ORDER BY r.fallado_en ASC, e.id ASC LIMIT " . self::MAX);
        $stmt->execute([$idiomaId, $usuarioId]);
        $ejercicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $_SESSION["ruta_repaso"] = [
            "idioma"     => $idiomaId,
            "cola"       => array_map(fn($e) => (int) $e["id"], $ejercicios),
            "total"      => count($ejercicios),
            "resueltos"  => 0,
        ];
        return $ejercicios;
    }

    /**
     * Respuesta a UN ejercicio del repaso en curso.
     * @return array correcto, solucion, siguiente (id o null), avance (0–100) y,
     *               al terminar, "resultado" (resueltos, total, quedan, xp, racha)
     */
    public static function responder(PDO $c, int $usuarioId, int $idiomaId, int $ejercicioId, $respuesta): array
    {
        $x = $_SESSION["ruta_repaso"] ?? null;
        if (!$x || (int) $x["idioma"] !== $idiomaId || !$x["cola"]) {
            return ["error" => "El repaso se reinició. Vuelve a abrirlo desde tu ruta."];
        }
        if ((int) $x["cola"][0] !== $ejercicioId) {
            return ["error" => "Responde los ejercicios en orden.", "siguiente" => (int) $x["cola"][0]];
        }
        $e = RutaModel::ejercicio($c, $ejercicioId);
        if (!$e) {
            array_shift($x["cola"]);
            $_SESSION["ruta_repaso"] = $x;
            return ["error" => "Ese ejercicio ya no existe.", "siguiente" => $x["cola"][0] ?? null];
        }

        $r = RutaModel::calificar($e, $respuesta);
        if ($r["correcto"]) {
            $c->prepare("UPDATE ruta_repaso SET resuelto_en = NOW() WHERE usuario_id = ? AND ejercicio_id = ?")->execute([$usuarioId, $ejercicioId]);
            $x["resueltos"]++;
        } else {
            $c->prepare("UPDATE ruta_repaso SET fallado_en = NOW() WHERE usuario_id = ? AND ejercicio_id = ?")->execute([$usuarioId, $ejercicioId]);
        }
        array_shift($x["cola"]);   // en el repaso un fallo no se repite: queda para otro día

        $r["siguiente"] = $x["cola"][0] ?? null;
        $r["avance"] = $x["total"] > 0 ? (int) round(($x["total"] - count($x["cola"])) * 100 / $x["total"]) : 100;
        if ($r["siguiente"] === null) {
            unset($_SESSION["ruta_repaso"]);
            $r["resultado"] = self::terminar($c, $usuarioId, $idiomaId, (int) $x["resueltos"], (int) $x["total"]);
        } else {
            $_SESSION["ruta_repaso"] = $x;
        }
        return $r;
    }

    /** XP de práctica una vez al día (en la zona del estudiante) y la racha. */
    private static function terminar(PDO $c, int $usuarioId, int $idiomaId, int $resueltos, int $total): array
    {
        $xp = 0;
        $racha = null;
        if (GamificacionModel::disponible($c) && !self::yaDioXpHoy($c, $usuarioId)) {
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
            "resueltos" => $resueltos,
            "total"     => $total,
            "quedan"    => self::pendientes($c, $usuarioId, $idiomaId),
            "xp"        => $xp,
            "racha"     => $racha,
            "meta"      => $xp > 0 && GamificacionModel::conMeta($c) ? GamificacionModel::estadoMeta($c, $usuarioId, $xp) : null,
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
