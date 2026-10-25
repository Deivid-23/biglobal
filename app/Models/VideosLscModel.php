<?php

require_once __DIR__ . "/../Helpers/Video.php";

/**
 * VideosLscModel
 *
 * Videos del INSOR para los ejercicios de LSC que los necesitan
 * («Ver la seña» y «Deletreo»). Lo usa Admin › Ruta › Videos de LSC.
 *
 *   resumen($c)              -> por nivel: cuántos tienen video y cuántos faltan
 *   pendientes($c, "B1")     -> ejercicios sin video (todos los niveles con null)
 *   csv($filas)              -> el CSV para el revisor (";" y UTF-8 con BOM)
 *   analizar($c, $texto)     -> revisa el CSV lleno SIN guardar nada
 *   aplicar($c, $cambios)    -> guarda los enlaces válidos (una transacción)
 *
 * Los enlaces pasan siempre por Video::normalizar: nunca se guarda lo que
 * escribió alguien tal cual.
 */
class VideosLscModel
{
    public const MAX_BYTES = 1024 * 1024;
    public const MAX_FILAS = 3000;
    public const COLUMNAS = ["clave", "unidad", "leccion", "tipo", "el video debe mostrar", "opciones que ve el estudiante", "estado", "enlace del video (INSOR)"];

    /** Ejercicios de LSC que necesitan video (alias e, l, u, n, i). */
    private const DE_LSC = "
        FROM ruta_ejercicios e
        INNER JOIN ruta_lecciones l ON l.id = e.leccion_id
        INNER JOIN ruta_unidades u ON u.id = l.unidad_id
        INNER JOIN ruta_niveles n ON n.id = u.nivel_id
        INNER JOIN idiomas i ON i.id = n.idioma_id
        WHERE i.codigo = 'LSC' AND e.tipo IN ('sena', 'deletreo')";

    /** Por nivel de LSC: [id, idioma_id, codigo, nombre, publicado, total, con_video, faltan]. */
    public static function resumen(PDO $c): array
    {
        $filas = $c->query("
            SELECT n.id, n.idioma_id, n.codigo, n.nombre, n.publicado,
                   COUNT(e.id) AS total,
                   COALESCE(SUM(COALESCE(e.video_url, '') <> ''), 0) AS con_video
            FROM ruta_niveles n
            INNER JOIN idiomas i ON i.id = n.idioma_id
            LEFT JOIN ruta_unidades u ON u.nivel_id = n.id
            LEFT JOIN ruta_lecciones l ON l.unidad_id = u.id
            LEFT JOIN ruta_ejercicios e ON e.leccion_id = l.id AND e.tipo IN ('sena', 'deletreo')
            WHERE i.codigo = 'LSC'
            GROUP BY n.id, n.idioma_id, n.codigo, n.nombre, n.publicado, n.orden
            ORDER BY n.orden
        ")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($filas as &$f) {
            $f["faltan"] = (int) $f["total"] - (int) $f["con_video"];
        }
        return $filas;
    }

    /** Filas del CSV de videos que faltan (de un nivel, o de todos con null). */
    public static function pendientes(PDO $c, ?string $nivel = null): array
    {
        $stmt = $c->prepare("
            SELECT e.clave, e.tipo, e.datos, e.activo, l.titulo AS leccion, l.publicado AS leccion_publicada,
                   u.titulo AS unidad, u.publicado AS unidad_publicada, n.publicado AS nivel_publicado
            " . self::DE_LSC . " AND COALESCE(e.video_url, '') = ''" . ($nivel !== null ? " AND n.codigo = ?" : "") . "
            ORDER BY n.orden, u.orden, l.orden, e.orden
        ");
        $stmt->execute($nivel !== null ? [strtoupper($nivel)] : []);
        $filas = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $filas[] = [$r["clave"], $r["unidad"], $r["leccion"], ...self::queMuestra($r["tipo"], $r["datos"]), self::estado($r), ""];
        }
        return $filas;
    }

    /** El CSV listo para descargar o guardar. */
    public static function csv(array $filas): string
    {
        $f = fopen("php://temp", "w+");
        fwrite($f, "\xEF\xBB\xBF");
        fputcsv($f, self::COLUMNAS, ";");
        foreach ($filas as $fila) {
            fputcsv($f, $fila, ";");
        }
        rewind($f);
        $texto = stream_get_contents($f);
        fclose($f);
        return $texto;
    }

    /**
     * Revisa el CSV que llenó el revisor. No guarda nada.
     * @return array|string ["filas" => [...], "cambios" => [[id, url, clave]], "cuenta" => [estado => n]] o el texto del error
     *
     * Estado de cada fila: nuevo, reemplaza, igual (ya tenía ese video),
     * vacia (sin enlace: se ignora) o error.
     */
    public static function analizar(PDO $c, string $texto)
    {
        if (strlen($texto) > self::MAX_BYTES) {
            return "El archivo es demasiado grande (máximo 1 MB).";
        }
        if (str_contains($texto, "\0")) {
            return "El archivo no es un CSV de texto.";
        }
        $texto = preg_replace('/^\xEF\xBB\xBF/', "", $texto);
        if (!mb_check_encoding($texto, "UTF-8")) {
            $texto = mb_convert_encoding($texto, "UTF-8", "Windows-1252");   // CSV guardado por Excel sin UTF-8
        }
        $primera = strtok($texto, "\n");
        $sep = substr_count((string) $primera, ";") >= substr_count((string) $primera, ",") ? ";" : ",";

        $f = fopen("php://temp", "w+");
        fwrite($f, $texto);
        rewind($f);
        $cabecera = array_map(fn($v) => mb_strtolower(trim((string) $v)), fgetcsv($f, 0, $sep) ?: []);
        $colClave = array_search("clave", $cabecera, true);
        $colEnlace = false;
        foreach ($cabecera as $i => $nombre) {
            if (str_starts_with($nombre, "enlace")) {
                $colEnlace = $i;
            }
        }
        if ($colClave === false || $colEnlace === false) {
            fclose($f);
            return "El archivo debe tener las columnas «clave» y «enlace del video (INSOR)», como el CSV que se descarga aquí.";
        }

        $ejercicios = [];
        foreach ($c->query("SELECT e.id, e.clave, e.tipo, e.datos, e.video_url, n.codigo AS nivel, l.titulo AS leccion " . self::DE_LSC)->fetchAll(PDO::FETCH_ASSOC) as $e) {
            $ejercicios[$e["clave"]] = $e;
        }

        $filas = [];
        $cambios = [];
        $vistas = [];
        $cuenta = ["nuevo" => 0, "reemplaza" => 0, "igual" => 0, "vacia" => 0, "error" => 0];
        $linea = 1;
        while (($datos = fgetcsv($f, 0, $sep)) !== false) {
            $linea++;
            if ($datos === [null] || count(array_filter($datos, fn($v) => trim((string) $v) !== "")) === 0) {
                continue;   // línea en blanco
            }
            if (count($filas) >= self::MAX_FILAS) {
                fclose($f);
                return "El archivo tiene demasiadas filas (máximo " . self::MAX_FILAS . ").";
            }
            $clave = trim((string) ($datos[$colClave] ?? ""));
            $enlace = trim((string) ($datos[$colEnlace] ?? ""));
            $fila = ["linea" => $linea, "clave" => $clave, "enlace" => $enlace, "url" => null, "anterior" => null,
                     "nivel" => "", "leccion" => "", "muestra" => "", "estado" => "error", "mensaje" => ""];
            $e = $ejercicios[$clave] ?? null;
            if ($e) {
                $fila["nivel"] = $e["nivel"];
                $fila["leccion"] = $e["leccion"];
                $fila["muestra"] = self::queMuestra($e["tipo"], $e["datos"])[1];
                $fila["anterior"] = (string) $e["video_url"] !== "" ? $e["video_url"] : null;
            }
            $video = Video::normalizar($enlace);

            if (!$e) {
                $fila["mensaje"] = $clave === "" ? "Falta la clave." : "No es un ejercicio de seña o deletreo de LSC.";
            } elseif (isset($vistas[$clave])) {
                $fila["mensaje"] = "La clave está repetida en el archivo (línea " . $vistas[$clave] . ").";
            } elseif ($video === null) {
                $fila["estado"] = "vacia";
                $fila["mensaje"] = "Sin enlace: se deja como está.";
            } elseif (is_string($video)) {
                $fila["mensaje"] = $video;
            } else {
                $fila["url"] = $video["url"];
                if ($fila["anterior"] === $video["url"]) {
                    $fila["estado"] = "igual";
                    $fila["mensaje"] = "Ya tenía este video.";
                } else {
                    $fila["estado"] = $fila["anterior"] === null ? "nuevo" : "reemplaza";
                    $cambios[] = [(int) $e["id"], $video["url"], $clave];
                }
            }
            if ($e && !isset($vistas[$clave])) {
                $vistas[$clave] = $linea;
            }
            $cuenta[$fila["estado"]]++;
            $filas[] = $fila;
        }
        fclose($f);
        if (!$filas) {
            return "El archivo no tiene filas.";
        }
        return ["filas" => $filas, "cambios" => $cambios, "cuenta" => $cuenta];
    }

    /** Guarda los enlaces revisados. Devuelve cuántos ejercicios cambiaron. */
    public static function aplicar(PDO $c, array $cambios): int
    {
        $stmt = $c->prepare("
            UPDATE ruta_ejercicios e
            INNER JOIN ruta_lecciones l ON l.id = e.leccion_id
            INNER JOIN ruta_unidades u ON u.id = l.unidad_id
            INNER JOIN ruta_niveles n ON n.id = u.nivel_id
            INNER JOIN idiomas i ON i.id = n.idioma_id
            SET e.video_url = ?
            WHERE e.id = ? AND i.codigo = 'LSC' AND e.tipo IN ('sena', 'deletreo')
        ");
        $hechos = 0;
        $c->beginTransaction();
        try {
            foreach ($cambios as [$id, $url]) {
                $video = Video::normalizar((string) $url);   // por si acaso: solo enlaces limpios
                if (is_array($video)) {
                    $stmt->execute([$video["url"], (int) $id]);
                    $hechos += $stmt->rowCount();
                }
            }
            $c->commit();
        } catch (Throwable $e) {
            $c->rollBack();
            throw $e;
        }
        return $hechos;
    }

    /** [tipo para mostrar, lo que debe mostrar el video, opciones que ve el estudiante]. */
    private static function queMuestra(string $tipo, string $datosJson): array
    {
        $d = json_decode($datosJson, true) ?: [];
        if ($tipo === "sena") {
            return ["Ver la seña", "La seña de: " . ($d["opciones"][(int) ($d["correcta"] ?? 0)] ?? ""), implode(" | ", $d["opciones"] ?? [])];
        }
        return ["Deletreo", "Deletreo con el alfabeto manual de: " . mb_strtoupper((string) ($d["respuestas"][0] ?? "")), "(el estudiante escribe la palabra)"];
    }

    private static function estado(array $r): string
    {
        if (!(int) $r["nivel_publicado"]) {
            return "nivel sin publicar";
        }
        if (!(int) $r["leccion_publicada"] || !(int) $r["unidad_publicada"]) {
            return "lección nueva (sin publicar)";
        }
        return (int) $r["activo"] ? "lección publicada" : "lección publicada, ejercicio nuevo (inactivo)";
    }
}
