<?php

require_once __DIR__ . "/GamificacionModel.php";

/**
 * RutaModel
 *
 * Ruta de aprendizaje por idioma (tablas de database/ruta_aprendizaje.sql).
 * Lo usan el admin (src/admin/ruta_acciones.php y AdminController::ruta)
 * y el estudiante (EstudianteController). Todas las reglas viven aquí:
 *
 * - Niveles A1–C1 por idioma. A1 y A2 gratis; desde B1, Premium.
 * - Se avanza en orden: cada lección se abre al completar la anterior y
 *   cada nivel al completar el anterior. Una lección sin ejercicios que se
 *   puedan jugar aparece "en preparación" y no bloquea el camino.
 * - Los ejercicios de tipo "sena" (ver la seña) se ocultan hasta que
 *   tengan video.
 * - Se califica SIEMPRE en el servidor: el navegador recibe los ejercicios
 *   sin la respuesta. El intento vive en la sesión y obliga a responder en
 *   orden; lo que se falla vuelve al final de la cola.
 * - Al terminar: XP de la lección (+5 si fue perfecta) la primera vez;
 *   repetirla da 2 XP una vez al día. Las dos cuentan para la racha.
 */
class RutaModel
{
    public const TIPOS = [
        "opcion"    => "Opción múltiple",
        "completar" => "Completar la frase",
        "ordenar"   => "Ordenar palabras",
        "emparejar" => "Emparejar",
        "vf"        => "Verdadero o falso",
        "sena"      => "Ver la seña (LSC)",
        "escuchar"  => "Escuchar y elegir",
        "dictado"   => "Dictado (escuchar y escribir)",
        "traducir"  => "Traducir la frase",
        "error"     => "Encontrar el error",
        "clasificar" => "Clasificar en grupos",
        "huecos"    => "Varios espacios con fichas",
    ];
    public const NIVELES = ["A1", "A2", "B1", "B2", "C1"];
    public const XP_PERFECTA = 5;
    public const XP_PRACTICA = 2;
    public const ICONOS = ["fa-star", "fa-hand", "fa-comments", "fa-hashtag", "fa-palette", "fa-people-roof",
                           "fa-sun", "fa-utensils", "fa-house", "fa-briefcase", "fa-plane", "fa-heart", "fa-book-open", "fa-font"];

    /** Voz del navegador para "escuchar" según el idioma. */
    public const VOCES = ["EN" => "en-US", "ES" => "es-CO"];

    public static function disponible(PDO $c): bool
    {
        static $disponible = null;
        if ($disponible === null) {
            try {
                $disponible = (int) $c->query("
                    SELECT COUNT(*) FROM information_schema.tables
                    WHERE table_schema = DATABASE()
                      AND table_name IN ('ruta_niveles', 'ruta_unidades', 'ruta_lecciones', 'ruta_ejercicios', 'ruta_progreso')
                ")->fetchColumn() === 5;
            } catch (Throwable $e) {
                $disponible = false;
            }
        }
        return $disponible;
    }

    /* =========================================================
       LECTURAS COMUNES
    ========================================================= */

    /** Idiomas activos que tienen niveles (publicados si $soloPublicados). */
    public static function idiomas(PDO $c, bool $soloPublicados): array
    {
        return $c->query("
            SELECT DISTINCT i.id, i.nombre, i.codigo
            FROM idiomas i
            INNER JOIN ruta_niveles n ON n.idioma_id = i.id" . ($soloPublicados ? " AND n.publicado = 1" : "") . "
            WHERE i.estado = 1
            ORDER BY FIELD(i.codigo, 'EN', 'ES', 'LSC'), i.nombre
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function niveles(PDO $c, int $idiomaId, bool $soloPublicados): array
    {
        $stmt = $c->prepare("
            SELECT * FROM ruta_niveles
            WHERE idioma_id = ?" . ($soloPublicados ? " AND publicado = 1" : "") . "
            ORDER BY orden ASC
        ");
        $stmt->execute([$idiomaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** "Inicial · A1" o, en LSC, "LSC Inicial · equivale a A1". */
    public static function nombreNivel(array $nivel, string $codigoIdioma): string
    {
        return strtoupper($codigoIdioma) === "LSC"
            ? "LSC " . $nivel["nombre"] . " · equivale a " . $nivel["codigo"]
            : $nivel["nombre"] . " · " . $nivel["codigo"];
    }

    /** Condición SQL de un ejercicio que se puede jugar (alias e). */
    private const JUGABLE = "e.activo = 1 AND (e.tipo <> 'sena' OR COALESCE(e.video_url, '') <> '')";

    /* =========================================================
       ADMIN: ÁRBOL DE UN NIVEL
    ========================================================= */

    public static function nivel(PDO $c, int $nivelId): ?array
    {
        $stmt = $c->prepare("
            SELECT n.*, i.nombre AS idioma, i.codigo AS idioma_codigo
            FROM ruta_niveles n INNER JOIN idiomas i ON i.id = n.idioma_id
            WHERE n.id = ? LIMIT 1
        ");
        $stmt->execute([$nivelId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Unidades del nivel con sus lecciones y conteos (todo, publicado o no). */
    public static function arbolAdmin(PDO $c, int $nivelId): array
    {
        $stmt = $c->prepare("SELECT * FROM ruta_unidades WHERE nivel_id = ? ORDER BY orden ASC, id ASC");
        $stmt->execute([$nivelId]);
        $unidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$unidades) {
            return [];
        }

        $stmt = $c->prepare("
            SELECT l.*,
                   (SELECT COUNT(*) FROM ruta_ejercicios e WHERE e.leccion_id = l.id) AS ejercicios,
                   (SELECT COUNT(*) FROM ruta_ejercicios e WHERE e.leccion_id = l.id AND " . self::JUGABLE . ") AS jugables,
                   (SELECT COUNT(*) FROM ruta_ejercicios e WHERE e.leccion_id = l.id AND e.tipo = 'sena' AND COALESCE(e.video_url, '') = '') AS senas_sin_video
            FROM ruta_lecciones l
            INNER JOIN ruta_unidades u ON u.id = l.unidad_id
            WHERE u.nivel_id = ?
            ORDER BY l.orden ASC, l.id ASC
        ");
        $stmt->execute([$nivelId]);
        $porUnidad = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $l) {
            $porUnidad[(int) $l["unidad_id"]][] = $l;
        }
        foreach ($unidades as &$u) {
            $u["lecciones"] = $porUnidad[(int) $u["id"]] ?? [];
        }
        unset($u);
        return $unidades;
    }

    public static function unidad(PDO $c, int $unidadId): ?array
    {
        $stmt = $c->prepare("SELECT * FROM ruta_unidades WHERE id = ? LIMIT 1");
        $stmt->execute([$unidadId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Lección con su unidad, nivel e idioma. */
    public static function leccion(PDO $c, int $leccionId): ?array
    {
        $stmt = $c->prepare("
            SELECT l.*, u.titulo AS unidad, u.nivel_id, u.publicado AS unidad_publicada,
                   n.codigo AS nivel_codigo, n.idioma_id, n.premium, n.publicado AS nivel_publicado,
                   i.codigo AS idioma_codigo, i.nombre AS idioma
            FROM ruta_lecciones l
            INNER JOIN ruta_unidades u ON u.id = l.unidad_id
            INNER JOIN ruta_niveles n ON n.id = u.nivel_id
            INNER JOIN idiomas i ON i.id = n.idioma_id
            WHERE l.id = ? LIMIT 1
        ");
        $stmt->execute([$leccionId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function ejercicios(PDO $c, int $leccionId, bool $soloJugables): array
    {
        $stmt = $c->prepare("
            SELECT e.* FROM ruta_ejercicios e
            WHERE e.leccion_id = ?" . ($soloJugables ? " AND " . self::JUGABLE : "") . "
            ORDER BY e.orden ASC, e.id ASC
        ");
        $stmt->execute([$leccionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function ejercicio(PDO $c, int $ejercicioId): ?array
    {
        $stmt = $c->prepare("SELECT * FROM ruta_ejercicios WHERE id = ? LIMIT 1");
        $stmt->execute([$ejercicioId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Lecciones de LSC con ejercicios "ver la seña" sin video o sin video propio. */
    public static function pendientesVideo(PDO $c, int $idiomaId): array
    {
        $stmt = $c->prepare("
            SELECT * FROM (
                SELECT l.id, l.titulo, n.codigo AS nivel, u.titulo AS unidad, n.orden AS o1, u.orden AS o2, l.orden AS o3,
                       (SELECT COUNT(*) FROM ruta_ejercicios e WHERE e.leccion_id = l.id AND e.tipo = 'sena' AND COALESCE(e.video_url, '') = '') AS senas_sin_video,
                       (COALESCE(l.video_url, '') = '') AS sin_video_leccion
                FROM ruta_lecciones l
                INNER JOIN ruta_unidades u ON u.id = l.unidad_id
                INNER JOIN ruta_niveles n ON n.id = u.nivel_id
                WHERE n.idioma_id = ?
            ) x
            WHERE x.senas_sin_video > 0 OR x.sin_video_leccion = 1
            ORDER BY x.o1, x.o2, x.o3
        ");
        $stmt->execute([$idiomaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================================================
       ADMIN: VALIDAR Y GUARDAR
    ========================================================= */


    /** Video opcional: null si está vacío, ["url"] si es válido o el texto del error. */
    private static function videoOpcional(array $post)
    {
        $v = Video::normalizar((string) ($post["video_url"] ?? ""));
        return $v === null ? ["url" => null] : $v;
    }

    /** @return true|string */
    public static function guardarNivel(PDO $c, array $nivel, array $post)
    {
        $nombre = trim((string) ($post["nombre"] ?? ""));
        if ($nombre === "" || mb_strlen($nombre) > 60) {
            return "El nombre del nivel es obligatorio (máximo 60 caracteres).";
        }
        $descripcion = trim((string) ($post["descripcion"] ?? ""));
        if (mb_strlen($descripcion) > 255) {
            return "La descripción puede tener máximo 255 caracteres.";
        }
        $c->prepare("UPDATE ruta_niveles SET nombre = ?, descripcion = ?, premium = ?, publicado = ? WHERE id = ?")
          ->execute([$nombre, $descripcion !== "" ? $descripcion : null, !empty($post["premium"]) ? 1 : 0, !empty($post["publicado"]) ? 1 : 0, (int) $nivel["id"]]);
        return true;
    }

    /** Crea o edita una unidad. @return int|string id o error */
    public static function guardarUnidad(PDO $c, int $nivelId, ?array $unidad, array $post)
    {
        $titulo = trim((string) ($post["titulo"] ?? ""));
        if ($titulo === "" || mb_strlen($titulo) > 120) {
            return "El título es obligatorio (máximo 120 caracteres).";
        }
        $descripcion = trim((string) ($post["descripcion"] ?? ""));
        if (mb_strlen($descripcion) > 255) {
            return "La descripción puede tener máximo 255 caracteres.";
        }
        $icono = in_array($post["icono"] ?? "", self::ICONOS, true) ? $post["icono"] : "fa-star";
        $orden = max(1, min(999, (int) ($post["orden"] ?? 1)));
        $publicado = !empty($post["publicado"]) ? 1 : 0;

        if ($unidad) {
            $c->prepare("UPDATE ruta_unidades SET titulo = ?, descripcion = ?, icono = ?, orden = ?, publicado = ? WHERE id = ?")
              ->execute([$titulo, $descripcion ?: null, $icono, $orden, $publicado, (int) $unidad["id"]]);
            return (int) $unidad["id"];
        }
        $c->prepare("INSERT INTO ruta_unidades (nivel_id, clave, titulo, descripcion, icono, orden, publicado) VALUES (?, ?, ?, ?, ?, ?, ?)")
          ->execute([$nivelId, self::claveNueva("u"), $titulo, $descripcion ?: null, $icono, $orden, $publicado]);
        return (int) $c->lastInsertId();
    }

    /** Crea o edita una lección. @return int|string */
    public static function guardarLeccion(PDO $c, int $unidadId, ?array $leccion, array $post)
    {
        $titulo = trim((string) ($post["titulo"] ?? ""));
        if ($titulo === "" || mb_strlen($titulo) > 120) {
            return "El título es obligatorio (máximo 120 caracteres).";
        }
        $descripcion = trim((string) ($post["descripcion"] ?? ""));
        if (mb_strlen($descripcion) > 255) {
            return "La descripción puede tener máximo 255 caracteres.";
        }
        $video = self::videoOpcional($post);
        if (is_string($video)) {
            return $video;
        }
        $orden = max(1, min(999, (int) ($post["orden"] ?? 1)));
        $xp = max(1, min(100, (int) ($post["xp"] ?? 10)));
        $publicado = !empty($post["publicado"]) ? 1 : 0;

        if ($leccion) {
            $c->prepare("UPDATE ruta_lecciones SET titulo = ?, descripcion = ?, video_url = ?, orden = ?, xp = ?, publicado = ? WHERE id = ?")
              ->execute([$titulo, $descripcion ?: null, $video["url"], $orden, $xp, $publicado, (int) $leccion["id"]]);
            return (int) $leccion["id"];
        }
        $c->prepare("INSERT INTO ruta_lecciones (unidad_id, clave, titulo, descripcion, video_url, orden, xp, publicado) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
          ->execute([$unidadId, self::claveNueva("l"), $titulo, $descripcion ?: null, $video["url"], $orden, $xp, $publicado]);
        return (int) $c->lastInsertId();
    }

    /** Líneas no vacías de un textarea. */
    private static function lineas(string $texto): array
    {
        return array_values(array_filter(array_map("trim", preg_split('/\R/u', $texto) ?: []), fn($l) => $l !== ""));
    }

    /**
     * Convierte el formulario del admin en [tipo, enunciado, datos (JSON), video_url].
     * @return array|string
     */
    public static function datosEjercicio(array $post)
    {
        $tipo = (string) ($post["tipo"] ?? "");
        if (!isset(self::TIPOS[$tipo])) {
            return "Elige el tipo de ejercicio.";
        }
        $enunciado = trim((string) ($post["enunciado"] ?? ""));
        if ($enunciado === "" || mb_strlen($enunciado) > 500) {
            return "El enunciado es obligatorio (máximo 500 caracteres).";
        }
        $video = self::videoOpcional($post);
        if (is_string($video)) {
            return $video;
        }

        $datos = [];
        if (in_array($tipo, ["opcion", "sena", "escuchar"], true)) {
            $opciones = self::lineas((string) ($post["opciones"] ?? ""));
            if (count($opciones) < 2 || count($opciones) > 4) {
                return "Escribe de 2 a 4 opciones, una por línea.";
            }
            if (count(array_unique(array_map([self::class, "normalizar"], $opciones))) !== count($opciones)) {
                return "Las opciones no se pueden repetir.";
            }
            foreach ($opciones as $o) {
                if (mb_strlen($o) > 150) {
                    return "Cada opción puede tener máximo 150 caracteres.";
                }
            }
            $correcta = (int) ($post["correcta"] ?? 0);
            if ($correcta < 1 || $correcta > count($opciones)) {
                return "Indica cuál opción es la correcta (de 1 a " . count($opciones) . ").";
            }
            $datos = ["opciones" => $opciones, "correcta" => $correcta - 1];
            if ($tipo === "escuchar") {
                $decir = trim((string) ($post["texto_audio"] ?? ""));
                if ($decir === "" || mb_strlen($decir) > 200) {
                    return "Escribe lo que va a sonar (máximo 200 caracteres).";
                }
                $datos["texto"] = $decir;
            }
        } elseif ($tipo === "completar") {
            $frase = trim((string) ($post["frase"] ?? ""));
            if (substr_count($frase, "___") !== 1 || mb_strlen($frase) > 300) {
                return "La frase debe tener un solo espacio para completar, escrito como ___ (tres guiones bajos).";
            }
            $respuestas = self::lineas((string) ($post["respuestas"] ?? ""));
            if (!$respuestas || count($respuestas) > 10) {
                return "Escribe de 1 a 10 respuestas aceptadas, una por línea.";
            }
            $datos = ["frase" => $frase, "respuestas" => $respuestas];
        } elseif ($tipo === "ordenar") {
            $frase = trim((string) preg_replace('/\s+/u', " ", (string) ($post["frase"] ?? "")));
            $palabras = $frase === "" ? [] : explode(" ", $frase);
            if (count($palabras) < 2 || count($palabras) > 12) {
                return "La frase para ordenar debe tener de 2 a 12 palabras.";
            }
            $extra = array_values(array_filter(explode(" ", trim((string) preg_replace('/\s+/u', " ", (string) ($post["extra"] ?? ""))))));
            if (count($extra) > 4) {
                return "Agrega máximo 4 palabras de más.";
            }
            $datos = ["frase" => $frase, "extra" => $extra, "aceptadas" => self::lineas((string) ($post["aceptadas"] ?? ""))];
        } elseif ($tipo === "emparejar") {
            $pares = [];
            foreach (self::lineas((string) ($post["pares"] ?? "")) as $l) {
                $partes = array_map("trim", explode("=", $l, 2));
                if (count($partes) !== 2 || $partes[0] === "" || $partes[1] === "") {
                    return "Escribe cada pareja en una línea así: palabra = traducción.";
                }
                $pares[] = $partes;
            }
            if (count($pares) < 2 || count($pares) > 6) {
                return "Escribe de 2 a 6 parejas.";
            }
            $izq = array_map([self::class, "normalizar"], array_column($pares, 0));
            $der = array_map([self::class, "normalizar"], array_column($pares, 1));
            if (count(array_unique($izq)) !== count($izq) || count(array_unique($der)) !== count($der)) {
                return "Las palabras de cada lado no se pueden repetir.";
            }
            $datos = ["pares" => $pares];
        } elseif ($tipo === "vf") {
            if (!in_array($post["correcta_vf"] ?? "", ["1", "0"], true)) {
                return "Indica si la afirmación es verdadera o falsa.";
            }
            $datos = ["correcta" => $post["correcta_vf"] === "1"];
        } elseif ($tipo === "dictado") {
            $decir = trim((string) ($post["texto_audio"] ?? ""));
            if ($decir === "" || mb_strlen($decir) > 200) {
                return "Escribe lo que va a sonar (máximo 200 caracteres).";
            }
            $datos = ["texto" => $decir, "aceptadas" => self::lineas((string) ($post["aceptadas"] ?? ""))];
        } elseif ($tipo === "traducir") {
            $original = trim((string) ($post["original"] ?? ""));
            if ($original === "" || mb_strlen($original) > 200) {
                return "Escribe la frase que se va a traducir (máximo 200 caracteres).";
            }
            $respuestas = self::lineas((string) ($post["respuestas"] ?? ""));
            if (!$respuestas || count($respuestas) > 10) {
                return "Escribe de 1 a 10 traducciones aceptadas, una por línea.";
            }
            $datos = ["original" => $original, "respuestas" => $respuestas];
        } elseif ($tipo === "error") {
            $frase = trim((string) preg_replace('/\s+/u', " ", (string) ($post["frase"] ?? "")));
            $palabras = $frase === "" ? [] : explode(" ", $frase);
            $marcadas = array_keys(array_filter($palabras, fn($p) => preg_match('/^\*[^*]+\*[.,;:!?]*$/u', $p)));
            if (count($palabras) < 3 || count($palabras) > 15 || count($marcadas) !== 1) {
                return "Escribe una frase de 3 a 15 palabras y marca la palabra equivocada entre asteriscos, ej. «She *go* to school».";
            }
            $correccion = trim((string) ($post["correccion"] ?? ""));
            if ($correccion === "" || mb_strlen($correccion) > 60) {
                return "Escribe la palabra correcta que reemplaza al error.";
            }
            $error = $marcadas[0];
            $palabras[$error] = str_replace("*", "", $palabras[$error]);
            $datos = ["palabras" => $palabras, "error" => $error, "correccion" => $correccion];
        } elseif ($tipo === "clasificar") {
            $grupos = self::lineas((string) ($post["grupos"] ?? ""));
            if (count($grupos) < 2 || count($grupos) > 3) {
                return "Escribe 2 o 3 grupos, uno por línea.";
            }
            $normGrupos = array_map([self::class, "normalizar"], $grupos);
            if (count(array_unique($normGrupos)) !== count($grupos)) {
                return "Los grupos no se pueden repetir.";
            }
            $items = [];
            foreach (self::lineas((string) ($post["items"] ?? "")) as $l) {
                $partes = array_map("trim", explode("=", $l, 2));
                $g = count($partes) === 2 ? array_search(self::normalizar($partes[1]), $normGrupos, true) : false;
                if ($partes[0] === "" || $g === false) {
                    return "Escribe cada palabra en una línea así: palabra = grupo (usando el nombre exacto de un grupo).";
                }
                $items[] = [$partes[0], (int) $g];
            }
            if (count($items) < 4 || count($items) > 8) {
                return "Escribe de 4 a 8 palabras para clasificar.";
            }
            $norm = array_map(fn($i) => self::normalizar($i[0]), $items);
            if (count(array_unique($norm)) !== count($norm)) {
                return "Las palabras para clasificar no se pueden repetir.";
            }
            if (count(array_unique(array_column($items, 1))) !== count($grupos)) {
                return "Cada grupo debe tener al menos una palabra.";
            }
            $datos = ["grupos" => $grupos, "items" => $items];
        } elseif ($tipo === "huecos") {
            $texto = trim((string) ($post["texto_huecos"] ?? ""));
            $huecos = substr_count($texto, "___");
            if ($huecos < 2 || $huecos > 4 || mb_strlen($texto) > 400) {
                return "El texto debe tener de 2 a 4 espacios marcados con ___ (máximo 400 caracteres).";
            }
            $respuestas = self::lineas((string) ($post["respuestas"] ?? ""));
            if (count($respuestas) !== $huecos) {
                return "Escribe una respuesta por cada espacio, en orden, una por línea.";
            }
            $extra = array_values(array_filter(explode(" ", trim((string) preg_replace('/\s+/u', " ", (string) ($post["extra"] ?? ""))))));
            if (count($extra) > 4) {
                return "Agrega máximo 4 palabras de más.";
            }
            $banco = array_map([self::class, "normalizar"], array_merge($respuestas, $extra));
            if (count(array_unique($banco)) !== count($banco)) {
                return "Las fichas (respuestas y palabras de más) no se pueden repetir.";
            }
            $datos = ["texto" => $texto, "respuestas" => $respuestas, "extra" => $extra];
        }

        return [
            "tipo"      => $tipo,
            "enunciado" => $enunciado,
            "datos"     => json_encode($datos, JSON_UNESCAPED_UNICODE),
            "video_url" => $video["url"],
            "orden"     => max(1, min(999, (int) ($post["orden"] ?? 1))),
            "activo"    => !empty($post["activo"]) ? 1 : 0,
        ];
    }

    /** @return int|string */
    public static function guardarEjercicio(PDO $c, int $leccionId, ?array $ejercicio, array $post)
    {
        $d = self::datosEjercicio($post);
        if (is_string($d)) {
            return $d;
        }
        if ($ejercicio) {
            $c->prepare("UPDATE ruta_ejercicios SET tipo = ?, enunciado = ?, datos = ?, video_url = ?, orden = ?, activo = ? WHERE id = ?")
              ->execute([$d["tipo"], $d["enunciado"], $d["datos"], $d["video_url"], $d["orden"], $d["activo"], (int) $ejercicio["id"]]);
            return (int) $ejercicio["id"];
        }
        $c->prepare("INSERT INTO ruta_ejercicios (leccion_id, clave, tipo, enunciado, datos, video_url, orden, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
          ->execute([$leccionId, self::claveNueva("e"), $d["tipo"], $d["enunciado"], $d["datos"], $d["video_url"], $d["orden"], $d["activo"]]);
        return (int) $c->lastInsertId();
    }

    /** Valores del formulario a partir de un ejercicio guardado (para editar). */
    public static function formularioDe(array $e): array
    {
        $d = json_decode((string) $e["datos"], true) ?: [];
        $f = ["tipo" => $e["tipo"], "enunciado" => $e["enunciado"], "video_url" => $e["video_url"], "orden" => $e["orden"], "activo" => $e["activo"]];
        if (isset($d["opciones"])) {
            $f["opciones"] = implode("\n", $d["opciones"]);
            $f["correcta"] = (int) $d["correcta"] + 1;
        }
        $f["texto_audio"] = $d["texto"] ?? "";
        if ($e["tipo"] === "completar") {
            $f["frase"] = $d["frase"] ?? "";
            $f["respuestas"] = implode("\n", $d["respuestas"] ?? []);
        }
        if ($e["tipo"] === "ordenar") {
            $f["frase"] = $d["frase"] ?? "";
            $f["extra"] = implode(" ", $d["extra"] ?? []);
            $f["aceptadas"] = implode("\n", $d["aceptadas"] ?? []);
        }
        if ($e["tipo"] === "emparejar") {
            $f["pares"] = implode("\n", array_map(fn($p) => $p[0] . " = " . $p[1], $d["pares"] ?? []));
        }
        if ($e["tipo"] === "vf") {
            $f["correcta_vf"] = !empty($d["correcta"]) ? "1" : "0";
        }
        if ($e["tipo"] === "dictado") {
            $f["aceptadas"] = implode("\n", $d["aceptadas"] ?? []);
        }
        if ($e["tipo"] === "traducir") {
            $f["original"] = $d["original"] ?? "";
            $f["respuestas"] = implode("\n", $d["respuestas"] ?? []);
        }
        if ($e["tipo"] === "error") {
            $palabras = $d["palabras"] ?? [];
            if (isset($palabras[$d["error"] ?? -1])) {
                $palabras[$d["error"]] = "*" . $palabras[$d["error"]] . "*";
            }
            $f["frase"] = implode(" ", $palabras);
            $f["correccion"] = $d["correccion"] ?? "";
        }
        if ($e["tipo"] === "clasificar") {
            $f["grupos"] = implode("\n", $d["grupos"] ?? []);
            $f["items"] = implode("\n", array_map(fn($i) => $i[0] . " = " . ($d["grupos"][$i[1]] ?? ""), $d["items"] ?? []));
        }
        if ($e["tipo"] === "huecos") {
            $f["texto_huecos"] = $d["texto"] ?? "";
            $f["respuestas"] = implode("\n", $d["respuestas"] ?? []);
            $f["extra"] = implode(" ", $d["extra"] ?? []);
        }
        return $f;
    }

    public static function eliminar(PDO $c, string $que, int $id): bool
    {
        $tablas = ["unidad" => "ruta_unidades", "leccion" => "ruta_lecciones", "ejercicio" => "ruta_ejercicios"];
        if (!isset($tablas[$que])) {
            return false;
        }
        $stmt = $c->prepare("DELETE FROM {$tablas[$que]} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    private static function claveNueva(string $tipo): string
    {
        return "adm-" . $tipo . "-" . bin2hex(random_bytes(8));
    }

    /* =========================================================
       ESTUDIANTE: MAPA DE LA RUTA Y DESBLOQUEO
    ========================================================= */

    /**
     * Niveles publicados del idioma con sus unidades y lecciones, y el estado
     * de cada una para el estudiante:
     *   lección: completada | disponible | bloqueada | preparacion
     *   nivel:   abierto | bloqueado | premium
     */
    public static function mapa(PDO $c, int $idiomaId, int $usuarioId, bool $esPremium): array
    {
        $niveles = self::niveles($c, $idiomaId, true);
        if (!$niveles) {
            return [];
        }
        $stmt = $c->prepare("
            SELECT l.id, l.titulo, l.descripcion, l.xp, l.orden, l.unidad_id,
                   u.titulo AS unidad, u.descripcion AS unidad_descripcion, u.icono, u.orden AS unidad_orden, u.nivel_id,
                   (SELECT COUNT(*) FROM ruta_ejercicios e WHERE e.leccion_id = l.id AND " . self::JUGABLE . ") AS jugables,
                   p.mejor_porcentaje, p.completada_en
            FROM ruta_lecciones l
            INNER JOIN ruta_unidades u ON u.id = l.unidad_id AND u.publicado = 1
            INNER JOIN ruta_niveles n ON n.id = u.nivel_id AND n.publicado = 1 AND n.idioma_id = ?
            LEFT JOIN ruta_progreso p ON p.leccion_id = l.id AND p.usuario_id = ?
            WHERE l.publicado = 1
            ORDER BY n.orden, u.orden, u.id, l.orden, l.id
        ");
        $stmt->execute([$idiomaId, $usuarioId]);
        $lecciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $porNivel = [];
        foreach ($lecciones as $l) {
            $porNivel[(int) $l["nivel_id"]][] = $l;
        }

        $anteriorCompleto = true;
        $resultado = [];
        foreach ($niveles as $n) {
            $lista = $porNivel[(int) $n["id"]] ?? [];
            // Si ya avanzó en este nivel, sigue abierto aunque después se agreguen lecciones al anterior
            $conAvance = (bool) array_filter($lista, fn($l) => $l["completada_en"] !== null);
            $estadoNivel = !$anteriorCompleto && !$conAvance ? "bloqueado" : (((int) $n["premium"] === 1 && !$esPremium) ? "premium" : "abierto");

            $abierta = $estadoNivel === "abierto";   // la siguiente lección jugable está disponible
            $jugables = 0;
            $hechas = 0;
            $unidades = [];
            foreach ($lista as $l) {
                if ((int) $l["jugables"] === 0) {
                    $l["estado"] = "preparacion";
                } elseif ($l["completada_en"] !== null) {
                    $l["estado"] = $estadoNivel === "abierto" ? "completada" : "bloqueada";
                    $jugables++;
                    $hechas++;
                } else {
                    $l["estado"] = $abierta ? "disponible" : "bloqueada";
                    $abierta = false;
                    $jugables++;
                }
                $uid = (int) $l["unidad_id"];
                if (!isset($unidades[$uid])) {
                    $unidades[$uid] = ["id" => $uid, "titulo" => $l["unidad"], "descripcion" => $l["unidad_descripcion"], "icono" => $l["icono"], "lecciones" => []];
                }
                $unidades[$uid]["lecciones"][] = $l;
            }

            $completo = $jugables > 0 ? $hechas === $jugables : true;
            $resultado[] = $n + ["estado" => $estadoNivel, "unidades" => array_values($unidades), "jugables" => $jugables, "hechas" => $hechas];
            $anteriorCompleto = $completo && $estadoNivel === "abierto";
        }
        return $resultado;
    }

    /** Estado de una lección para el estudiante ("" si no aparece en su ruta). */
    public static function estadoLeccion(PDO $c, array $leccion, int $usuarioId, bool $esPremium): string
    {
        foreach (self::mapa($c, (int) $leccion["idioma_id"], $usuarioId, $esPremium) as $n) {
            foreach ($n["unidades"] as $u) {
                foreach ($u["lecciones"] as $l) {
                    if ((int) $l["id"] === (int) $leccion["id"]) {
                        return $n["estado"] === "premium" ? "premium" : $l["estado"];
                    }
                }
            }
        }
        return "";
    }

    /** Siguiente lección disponible del idioma (para "Continúa tu ruta"), o null. */
    public static function siguiente(PDO $c, int $idiomaId, int $usuarioId, bool $esPremium): ?array
    {
        foreach (self::mapa($c, $idiomaId, $usuarioId, $esPremium) as $n) {
            foreach ($n["unidades"] as $u) {
                foreach ($u["lecciones"] as $l) {
                    if ($l["estado"] === "disponible") {
                        return $l + ["nivel" => $n["codigo"]];
                    }
                }
            }
        }
        return null;
    }

    /** Idioma en el que el estudiante practicó por última vez (o null). */
    public static function idiomaReciente(PDO $c, int $usuarioId): ?int
    {
        $stmt = $c->prepare("
            SELECT n.idioma_id FROM ruta_progreso p
            INNER JOIN ruta_lecciones l ON l.id = p.leccion_id
            INNER JOIN ruta_unidades u ON u.id = l.unidad_id
            INNER JOIN ruta_niveles n ON n.id = u.nivel_id
            WHERE p.usuario_id = ?
            ORDER BY p.ultima_vez DESC LIMIT 1
        ");
        $stmt->execute([$usuarioId]);
        $id = $stmt->fetchColumn();
        return $id ? (int) $id : null;
    }

    /* =========================================================
       ESTUDIANTE: JUGAR UNA LECCIÓN
    ========================================================= */

    /**
     * Lo que el navegador recibe de cada ejercicio: SIN la respuesta.
     * Las opciones, fichas y parejas van mezcladas.
     */
    public static function vistaPublica(array $e, string $codigoIdioma): array
    {
        $d = json_decode((string) $e["datos"], true) ?: [];
        $v = ["id" => (int) $e["id"], "tipo" => $e["tipo"], "enunciado" => $e["enunciado"]];
        $video = Video::reproductor($e["video_url"] ?? null);
        if ($video) {
            $v["video"] = $video["src"];
        }
        switch ($e["tipo"]) {
            case "opcion":
            case "sena":
            case "escuchar":
                $v["opciones"] = $d["opciones"] ?? [];
                shuffle($v["opciones"]);
                if ($e["tipo"] === "escuchar") {
                    // La voz del navegador necesita el texto: es práctica, no examen
                    $v["texto"] = $d["texto"] ?? "";
                    $v["voz"] = self::VOCES[strtoupper($codigoIdioma)] ?? "es-CO";
                }
                break;
            case "completar":
                [$antes, $despues] = array_pad(explode("___", (string) ($d["frase"] ?? ""), 2), 2, "");
                $v["antes"] = $antes;
                $v["despues"] = $despues;
                break;
            case "ordenar":
                $fichas = array_merge(explode(" ", (string) ($d["frase"] ?? "")), $d["extra"] ?? []);
                shuffle($fichas);
                $v["fichas"] = $fichas;
                break;
            case "emparejar":
                $izq = array_column($d["pares"] ?? [], 0);
                $der = array_column($d["pares"] ?? [], 1);
                shuffle($izq);
                shuffle($der);
                $v["izquierda"] = $izq;
                $v["derecha"] = $der;
                break;
            case "dictado":
                // Igual que "escuchar": la voz del navegador necesita el texto
                $v["texto"] = $d["texto"] ?? "";
                $v["voz"] = self::VOCES[strtoupper($codigoIdioma)] ?? "es-CO";
                break;
            case "traducir":
                $v["original"] = $d["original"] ?? "";
                break;
            case "error":
                $v["palabras"] = $d["palabras"] ?? [];
                break;
            case "clasificar":
                $v["grupos"] = $d["grupos"] ?? [];
                $v["items"] = array_column($d["items"] ?? [], 0);
                shuffle($v["items"]);
                break;
            case "huecos":
                $v["partes"] = explode("___", (string) ($d["texto"] ?? ""));
                $v["fichas"] = array_merge($d["respuestas"] ?? [], $d["extra"] ?? []);
                shuffle($v["fichas"]);
                break;
        }
        return $v;
    }

    /** "  ¡Hola,   MAMÁ! " -> "hola mama" (para comparar respuestas escritas). */
    public static function normalizar(string $t): string
    {
        $t = mb_strtolower(trim($t), "UTF-8");
        $t = strtr($t, ["á" => "a", "é" => "e", "í" => "i", "ó" => "o", "ú" => "u", "ü" => "u", "’" => "'", "‘" => "'"]);
        $t = (string) preg_replace('/[.,;:!¡?¿"()]+/u', " ", $t);
        return trim((string) preg_replace('/\s+/u', " ", $t));
    }

    /**
     * Califica una respuesta.
     * @param mixed $respuesta texto, "1"/"0" o lista de parejas [[izq, der], ...]
     * @return array ["correcto" => bool, "solucion" => texto para mostrar]
     */
    public static function calificar(array $e, $respuesta): array
    {
        $d = json_decode((string) $e["datos"], true) ?: [];
        switch ($e["tipo"]) {
            case "opcion":
            case "sena":
            case "escuchar":
                $correcta = (string) ($d["opciones"][(int) ($d["correcta"] ?? 0)] ?? "");
                return ["correcto" => is_string($respuesta) && $respuesta === $correcta, "solucion" => $correcta];

            case "vf":
                $correcta = !empty($d["correcta"]);
                return ["correcto" => $respuesta === ($correcta ? "1" : "0"), "solucion" => $correcta ? "Verdadero" : "Falso"];

            case "completar":
                $aceptadas = array_map([self::class, "normalizar"], $d["respuestas"] ?? []);
                $ok = is_string($respuesta) && $respuesta !== "" && in_array(self::normalizar($respuesta), $aceptadas, true);
                return ["correcto" => $ok, "solucion" => str_replace("___", (string) ($d["respuestas"][0] ?? ""), (string) ($d["frase"] ?? ""))];

            case "ordenar":
                $aceptadas = array_map([self::class, "normalizar"], array_merge([(string) ($d["frase"] ?? "")], $d["aceptadas"] ?? []));
                $ok = is_string($respuesta) && in_array(self::normalizar($respuesta), $aceptadas, true);
                return ["correcto" => $ok, "solucion" => (string) ($d["frase"] ?? "")];

            case "emparejar":
                $esperado = [];
                foreach ($d["pares"] ?? [] as [$a, $b]) {
                    $esperado[self::normalizar($a)] = self::normalizar($b);
                }
                $recibido = [];
                if (is_array($respuesta)) {
                    foreach ($respuesta as $par) {
                        if (is_array($par) && count($par) === 2 && is_string($par[0]) && is_string($par[1])) {
                            $recibido[self::normalizar($par[0])] = self::normalizar($par[1]);
                        }
                    }
                }
                ksort($esperado);
                ksort($recibido);
                return ["correcto" => $esperado === $recibido,
                        "solucion" => implode(" · ", array_map(fn($p) => $p[0] . " = " . $p[1], $d["pares"] ?? []))];

            case "dictado":
                $aceptadas = array_map([self::class, "normalizar"], array_merge([(string) ($d["texto"] ?? "")], $d["aceptadas"] ?? []));
                $ok = is_string($respuesta) && $respuesta !== "" && in_array(self::normalizar($respuesta), $aceptadas, true);
                return ["correcto" => $ok, "solucion" => (string) ($d["texto"] ?? "")];

            case "traducir":
                $aceptadas = array_map([self::class, "normalizar"], $d["respuestas"] ?? []);
                $ok = is_string($respuesta) && $respuesta !== "" && in_array(self::normalizar($respuesta), $aceptadas, true);
                return ["correcto" => $ok, "solucion" => (string) ($d["respuestas"][0] ?? "")];

            case "error":
                $palabras = $d["palabras"] ?? [];
                $error = (int) ($d["error"] ?? -1);
                $corregida = $palabras;
                $corregida[$error] = (string) ($d["correccion"] ?? "");
                $ok = is_string($respuesta) && ctype_digit($respuesta) && (int) $respuesta === $error;
                return ["correcto" => $ok, "solucion" => "«" . ($palabras[$error] ?? "") . "» → " . implode(" ", $corregida)];

            case "clasificar":
                $grupos = $d["grupos"] ?? [];
                $esperado = [];
                foreach ($d["items"] ?? [] as [$palabra, $g]) {
                    $esperado[self::normalizar($palabra)] = self::normalizar((string) ($grupos[$g] ?? ""));
                }
                $recibido = [];
                if (is_array($respuesta)) {
                    foreach ($respuesta as $par) {
                        if (is_array($par) && count($par) === 2 && is_string($par[0]) && is_string($par[1])) {
                            $recibido[self::normalizar($par[0])] = self::normalizar($par[1]);
                        }
                    }
                }
                ksort($esperado);
                ksort($recibido);
                $porGrupo = [];
                foreach ($d["items"] ?? [] as [$palabra, $g]) {
                    $porGrupo[$g][] = $palabra;
                }
                ksort($porGrupo);
                return ["correcto" => $esperado === $recibido,
                        "solucion" => implode(" · ", array_map(fn($g, $lista) => ($grupos[$g] ?? "") . ": " . implode(", ", $lista), array_keys($porGrupo), $porGrupo))];

            case "huecos":
                $respuestas = $d["respuestas"] ?? [];
                $ok = is_array($respuesta) && count($respuesta) === count($respuestas);
                foreach ($respuestas as $i => $r) {
                    $ok = $ok && is_string($respuesta[$i] ?? null) && self::normalizar($respuesta[$i]) === self::normalizar($r);
                }
                $partes = explode("___", (string) ($d["texto"] ?? ""));
                $solucion = "";
                foreach ($partes as $i => $p) {
                    $solucion .= $p . ($respuestas[$i] ?? "");
                }
                return ["correcto" => $ok, "solucion" => $solucion];
        }
        return ["correcto" => false, "solucion" => ""];
    }

    /** Empieza (o reinicia) el intento de una lección en la sesión. */
    public static function iniciarIntento(int $leccionId, array $ejercicios): void
    {
        $ids = array_map(fn($e) => (int) $e["id"], $ejercicios);
        $_SESSION["ruta_intento"] = [
            "leccion"  => $leccionId,
            "cola"     => $ids,
            "fallados" => [],
            "aciertos" => 0,
            "total"    => count($ids),
        ];
    }

    /**
     * Respuesta a UN ejercicio del intento en curso.
     * @return array correcto, solucion, siguiente (id o null), avance (0–100) y,
     *               al terminar, "resultado" (porcentaje, xp, racha, primera)
     */
    public static function responder(PDO $c, int $usuarioId, int $leccionId, int $ejercicioId, $respuesta): array
    {
        $intento = $_SESSION["ruta_intento"] ?? null;
        if (!$intento || (int) $intento["leccion"] !== $leccionId || !$intento["cola"]) {
            return ["error" => "La lección se reinició. Vuelve a abrirla."];
        }
        if ((int) $intento["cola"][0] !== $ejercicioId) {
            return ["error" => "Responde los ejercicios en orden.", "siguiente" => (int) $intento["cola"][0]];
        }
        $e = self::ejercicio($c, $ejercicioId);
        if (!$e || (int) $e["leccion_id"] !== $leccionId) {
            return ["error" => "Ese ejercicio ya no existe. Vuelve a abrir la lección."];
        }

        $r = self::calificar($e, $respuesta);
        array_shift($intento["cola"]);
        if ($r["correcto"]) {
            if (empty($intento["fallados"][$ejercicioId])) {
                $intento["aciertos"]++;
            }
        } else {
            $intento["fallados"][$ejercicioId] = true;
            $intento["cola"][] = $ejercicioId;   // vuelve al final
        }

        $pendientes = count(array_unique($intento["cola"]));
        $r["avance"] = $intento["total"] > 0 ? (int) round(($intento["total"] - $pendientes) * 100 / $intento["total"]) : 100;
        $r["siguiente"] = $intento["cola"][0] ?? null;

        if ($r["siguiente"] === null) {
            unset($_SESSION["ruta_intento"]);
            $r["resultado"] = self::terminar($c, $usuarioId, $leccionId, $intento["total"] > 0 ? (int) round($intento["aciertos"] * 100 / $intento["total"]) : 100);
        } else {
            $_SESSION["ruta_intento"] = $intento;
        }
        return $r;
    }

    /** Guarda el progreso y da el XP (dentro de una transacción). */
    private static function terminar(PDO $c, int $usuarioId, int $leccionId, int $porcentaje): array
    {
        $leccion = self::leccion($c, $leccionId);
        $c->beginTransaction();
        try {
            $stmt = $c->prepare("
                SELECT mejor_porcentaje, intentos, (DATE(ultima_vez) = CURDATE()) AS hoy
                FROM ruta_progreso WHERE usuario_id = ? AND leccion_id = ? FOR UPDATE
            ");
            $stmt->execute([$usuarioId, $leccionId]);
            $previo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$previo) {
                $c->prepare("INSERT INTO ruta_progreso (usuario_id, leccion_id, mejor_porcentaje) VALUES (?, ?, ?)")
                  ->execute([$usuarioId, $leccionId, $porcentaje]);
                $xp = (int) ($leccion["xp"] ?? 10) + ($porcentaje === 100 ? self::XP_PERFECTA : 0);
                $motivo = "ruta_leccion";
            } else {
                $c->prepare("
                    UPDATE ruta_progreso SET intentos = intentos + 1, mejor_porcentaje = GREATEST(mejor_porcentaje, ?), ultima_vez = NOW()
                    WHERE usuario_id = ? AND leccion_id = ?
                ")->execute([$porcentaje, $usuarioId, $leccionId]);
                // Practicar: 2 XP una vez al día (el día en que se completó también cuenta un repaso)
                $yaRepasoHoy = (int) $previo["hoy"] === 1 && (int) $previo["intentos"] > 1;
                $xp = $yaRepasoHoy ? 0 : self::XP_PRACTICA;
                $motivo = "ruta_practica";
            }

            $racha = null;
            if ($xp > 0 && GamificacionModel::disponible($c)) {   // 0 XP = ya practicó hoy: la racha ya contó
                $racha = GamificacionModel::registrarActividad($c, $usuarioId, $xp, $motivo, $leccionId);
            }
            $c->commit();
        } catch (Throwable $e) {
            $c->rollBack();
            throw $e;
        }
        return ["porcentaje" => $porcentaje, "xp" => $xp, "racha" => $racha, "primera" => !$previo];
    }
}
