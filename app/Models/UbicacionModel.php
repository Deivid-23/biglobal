<?php

require_once __DIR__ . "/RutaModel.php";

/**
 * UbicacionModel
 *
 * Examen de ubicación de la ruta (tabla ruta_ubicacion, database/ruta_ubicacion.sql).
 * Lo usa el estudiante (EstudianteController::ubicacion y ubicacionResponder)
 * y RutaModel::mapa para abrir los niveles. Todas las reglas viven aquí:
 *
 * - Solo inglés y español (LSC necesita video y todavía no tiene niveles).
 * - Va por niveles: POR_NIVEL ejercicios del A1; con APROBAR aciertos o más
 *   pasa al A2, y así hasta el C1. En el primer nivel que no aprueba, termina
 *   y queda ubicado en ese nivel (si aprueba todo, en el último).
 * - Los ejercicios salen al azar de los repasos (la última lección de cada
 *   unidad). Sin "escuchar" ni "dictado" (el navegador recibe su texto) ni "sena".
 * - Se califica en el servidor con RutaModel::calificar; el intento vive en
 *   la sesión y obliga a responder en orden. Un fallo no se repite.
 * - Una vez al día por idioma (día del estudiante). Solo sube: se guarda el
 *   mejor resultado y nunca cierra niveles. No da XP ni insignias ni marca
 *   lecciones como completadas.
 */
class UbicacionModel
{
    public const POR_NIVEL = 6;
    public const APROBAR = 4;
    public const IDIOMAS = ["EN", "ES"];
    private const TIPOS_FUERA = ["escuchar", "dictado", "sena"];

    public static function disponible(PDO $c): bool
    {
        static $disponible = null;
        if ($disponible === null) {
            try {
                $disponible = RutaModel::disponible($c) && (int) $c->query("
                    SELECT COUNT(*) FROM information_schema.tables
                    WHERE table_schema = DATABASE() AND table_name = 'ruta_ubicacion'
                ")->fetchColumn() === 1;
            } catch (Throwable $e) {
                $disponible = false;
            }
        }
        return $disponible;
    }

    public static function tieneExamen(array $idioma): bool
    {
        return in_array(strtoupper((string) $idioma["codigo"]), self::IDIOMAS, true);
    }

    /** La fila del estudiante en ese idioma, o null. */
    public static function de(PDO $c, int $usuarioId, int $idiomaId): ?array
    {
        if (!self::disponible($c)) {
            return null;
        }
        $stmt = $c->prepare("SELECT * FROM ruta_ubicacion WHERE usuario_id = ? AND idioma_id = ? LIMIT 1");
        $stmt->execute([$usuarioId, $idiomaId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Nivel donde quedó ubicado ("B1"), o null. Lo usa RutaModel::mapa. */
    public static function nivelDe(PDO $c, int $usuarioId, int $idiomaId): ?string
    {
        $fila = self::de($c, $usuarioId, $idiomaId);
        return $fila && $fila["nivel_codigo"] !== null ? (string) $fila["nivel_codigo"] : null;
    }

    /** ¿Puede hacer el examen hoy? (una vez al día, en su zona horaria) */
    public static function puedeHoy(PDO $c, int $usuarioId, int $idiomaId): bool
    {
        $fila = self::de($c, $usuarioId, $idiomaId);
        if (!$fila) {
            return true;
        }
        $zona = GamificacionModel::zonaDe($c, $usuarioId);
        return GamificacionModel::diaEn((string) $fila["ultimo_intento"], $zona) !== GamificacionModel::hoy($zona);
    }

    /**
     * Bloques del examen: [["codigo" => "A1", "ejercicios" => [...]], ...]
     * Solo niveles publicados con suficientes ejercicios, en orden.
     */
    private static function armar(PDO $c, int $idiomaId): array
    {
        $fuera = implode(",", array_map(fn($t) => "'" . $t . "'", self::TIPOS_FUERA));
        $stmt = $c->prepare("
            SELECT e.*, n.codigo AS nivel_codigo
            FROM ruta_ejercicios e
            INNER JOIN ruta_lecciones l ON l.id = e.leccion_id AND l.publicado = 1
            INNER JOIN ruta_unidades u ON u.id = l.unidad_id AND u.publicado = 1
            INNER JOIN ruta_niveles n ON n.id = u.nivel_id AND n.publicado = 1 AND n.idioma_id = ?
            WHERE e.activo = 1 AND e.tipo NOT IN ($fuera)
              AND l.orden = (SELECT MAX(l2.orden) FROM ruta_lecciones l2 WHERE l2.unidad_id = l.unidad_id AND l2.publicado = 1)
            ORDER BY n.orden, RAND()
        ");
        $stmt->execute([$idiomaId]);

        $porNivel = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $e) {
            $codigo = (string) $e["nivel_codigo"];
            if (count($porNivel[$codigo] ?? []) < self::POR_NIVEL) {
                $porNivel[$codigo][] = $e;
            }
        }
        $bloques = [];
        foreach ($porNivel as $codigo => $lista) {
            if (count($lista) < self::POR_NIVEL) {
                break;   // un nivel sin repasos suficientes corta el examen ahí
            }
            $bloques[] = ["codigo" => $codigo, "ejercicios" => $lista];
        }
        return $bloques;
    }

    /**
     * Empieza el examen: guarda el intento en la sesión y anota el día
     * (si sale a mitad, hoy ya no lo repite).
     * @return array ejercicios de todos los bloques (con respuesta; la vista usa RutaModel::vistaPublica)
     */
    public static function iniciar(PDO $c, int $usuarioId, int $idiomaId): array
    {
        $bloques = self::armar($c, $idiomaId);
        if (!$bloques) {
            return [];
        }
        $c->prepare("
            INSERT INTO ruta_ubicacion (usuario_id, idioma_id, ultimo_intento) VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE ultimo_intento = NOW()
        ")->execute([$usuarioId, $idiomaId]);

        $_SESSION["ruta_ubicacion"] = [
            "idioma"   => $idiomaId,
            "bloques"  => array_map(fn($b) => ["codigo" => $b["codigo"], "ids" => array_map(fn($e) => (int) $e["id"], $b["ejercicios"])], $bloques),
            "bloque"   => 0,
            "pos"      => 0,
            "aciertos" => 0,
            "detalle"  => [],
        ];
        return array_merge(...array_map(fn($b) => $b["ejercicios"], $bloques));
    }

    /**
     * Respuesta a UN ejercicio del examen en curso.
     * @return array correcto, solucion, siguiente (id o null), avance (0–100) y,
     *               al terminar, "resultado" (nivel, nombre, detalle, mejoro, url)
     */
    public static function responder(PDO $c, int $usuarioId, int $idiomaId, int $ejercicioId, $respuesta): array
    {
        $x = $_SESSION["ruta_ubicacion"] ?? null;
        if (!$x || (int) $x["idioma"] !== $idiomaId) {
            return ["error" => "El examen se reinició. Vuelve a abrirlo desde tu ruta."];
        }
        $bloque = $x["bloques"][$x["bloque"]];
        $esperado = (int) $bloque["ids"][$x["pos"]];
        if ($esperado !== $ejercicioId) {
            return ["error" => "Responde los ejercicios en orden.", "siguiente" => $esperado];
        }
        $e = RutaModel::ejercicio($c, $ejercicioId);
        if (!$e) {
            unset($_SESSION["ruta_ubicacion"]);
            return ["error" => "Ese ejercicio ya no existe. Vuelve a abrir el examen mañana."];
        }

        $r = RutaModel::calificar($e, $respuesta);
        $x["pos"]++;
        if ($r["correcto"]) {
            $x["aciertos"]++;
        }
        $fallos = $x["pos"] - $x["aciertos"];
        $total = count($bloque["ids"]);
        $reprobado = $fallos > $total - self::APROBAR;   // ya no le alcanza para aprobar este nivel

        $siguiente = null;
        if ($reprobado || $x["pos"] >= $total) {
            // Aciertos de los que respondió (si ya no le alcanzaba, el nivel se corta antes)
            $x["detalle"][] = $bloque["codigo"] . " " . $x["aciertos"] . "/" . $x["pos"];
            if (!$reprobado && isset($x["bloques"][$x["bloque"] + 1])) {
                $x["bloque"]++;
                $x["pos"] = 0;
                $x["aciertos"] = 0;
                $siguiente = (int) $x["bloques"][$x["bloque"]]["ids"][0];
            } else {
                // Termina: queda en el nivel que no aprobó o, si aprobó todo, en el último
                unset($_SESSION["ruta_ubicacion"]);
                $r["siguiente"] = null;
                $r["avance"] = 100;
                $r["resultado"] = self::guardar($c, $usuarioId, $idiomaId, $bloque["codigo"], $x["detalle"], $reprobado ? $x["bloque"] : $x["bloque"] + 1);
                return $r;
            }
        } else {
            $siguiente = (int) $bloque["ids"][$x["pos"]];
        }

        $_SESSION["ruta_ubicacion"] = $x;
        $hechos = $x["bloque"] * self::POR_NIVEL + $x["pos"];
        $r["siguiente"] = $siguiente;
        $r["avance"] = (int) round($hechos * 100 / (count($x["bloques"]) * self::POR_NIVEL));
        return $r;
    }

    /** Guarda el resultado si es mejor que el anterior. */
    private static function guardar(PDO $c, int $usuarioId, int $idiomaId, string $nivel, array $detalle, int $aprobados): array
    {
        $antes = self::nivelDe($c, $usuarioId, $idiomaId);
        $orden = array_flip(RutaModel::NIVELES);
        $mejoro = $antes === null || ($orden[$nivel] ?? 0) > ($orden[$antes] ?? 0);
        $texto = implode(" · ", $detalle);
        if ($mejoro) {
            $c->prepare("UPDATE ruta_ubicacion SET nivel_codigo = ?, detalle = ?, ubicado_en = NOW() WHERE usuario_id = ? AND idioma_id = ?")
              ->execute([$nivel, mb_substr($texto, 0, 255), $usuarioId, $idiomaId]);
        }
        $final = $mejoro ? $nivel : $antes;

        $stmt = $c->prepare("SELECT i.codigo, n.nombre, n.codigo AS nivel FROM ruta_niveles n INNER JOIN idiomas i ON i.id = n.idioma_id WHERE n.idioma_id = ? AND n.codigo = ? LIMIT 1");
        $stmt->execute([$idiomaId, $final]);
        $n = $stmt->fetch(PDO::FETCH_ASSOC) ?: ["codigo" => "", "nombre" => "", "nivel" => $final];

        return [
            "nivel"     => $final,
            "nombre"    => $n["nombre"],
            "detalle"   => $texto,
            "aprobados" => $aprobados,
            "mejoro"    => $mejoro,
            "antes"     => $antes,
            "url"       => "index.php?page=ruta&idioma=" . rawurlencode((string) $n["codigo"]) . "&nivel=" . rawurlencode((string) $final),
        ];
    }
}
