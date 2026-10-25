<?php
/**
 * Genera el SQL del contenido de LSC de un nivel a partir de tools/lsc/<nivel>.php
 *
 *   php tools/generar_lsc.php a1     ->  database/lsc_a1_juegos.sql
 *
 * Cada ejercicio se valida con RutaModel::datosEjercicio (las mismas reglas
 * del formulario de Admin > Ruta). Si uno no pasa, no se escribe nada.
 * Los tipos que necesitan video ("sena", "deletreo") se validan con un
 * enlace de prueba y se guardan SIN video: no se muestran hasta que el
 * revisor pegue el video del INSOR.
 *
 * Por defecto lo que se suma a lecciones existentes entra inactivo y las
 * unidades nuevas sin publicar. Un nivel que todavía está oculto puede
 * pedir "activo" => 1 y "publicar" => 1: la única puerta es publicar el nivel.
 *
 * No toca ninguna base de datos.
 */
require_once __DIR__ . "/../app/Helpers/Video.php";
require_once __DIR__ . "/../app/Models/RutaModel.php";

$nivel = strtolower(trim((string) ($argv[1] ?? "")));
$fuente = __DIR__ . "/lsc/" . $nivel . ".php";
if (!preg_match('/^[abc][12]$/', $nivel) || !is_file($fuente)) {
    fwrite(STDERR, "Uso: php tools/generar_lsc.php a1   (lee tools/lsc/a1.php)\n");
    exit(1);
}
$contenido = require $fuente;
$idioma = $contenido["idioma"];
$codigoNivel = $contenido["nivel"];
$activoAgregados = !empty($contenido["activo"]) ? 1 : 0;
$publicar = !empty($contenido["publicar"]) ? 1 : 0;
$q = fn($v) => "'" . str_replace(["\\", "'"], ["\\\\", "''"], (string) $v) . "'";

$errores = [];
$sql = [];
$total = ["agregados" => 0, "nuevos" => 0, "video" => 0];

/** Valida un ejercicio y devuelve la fila lista (o null con el error anotado). */
$preparar = function (array $ej, string $clave) use (&$errores, &$total): ?array {
    [$tipo, $enunciado, $campos] = $ej;
    $post = $campos + ["tipo" => $tipo, "enunciado" => $enunciado, "activo" => "1"];
    if (in_array($tipo, ["opcion", "sena", "elegir"], true) && !isset($post["correcta"])) {
        $post["correcta"] = "1";
    }
    $necesitaVideo = in_array($tipo, RutaModel::NECESITAN_VIDEO, true);
    if ($necesitaVideo) {
        $post["video_url"] = "https://www.youtube.com/watch?v=aaaaaaaaaaa";
    }
    $r = RutaModel::datosEjercicio($post);
    if (is_string($r)) {
        $errores[] = "$clave: $r";
        return null;
    }
    if ($necesitaVideo) {
        $total["video"]++;
    }
    return $r;
};

$insertarEjercicio = function (string $leccion, string $clave, array $fila, int $orden, int $activo) use ($q): string {
    return "INSERT IGNORE INTO ruta_ejercicios (leccion_id, clave, tipo, enunciado, datos, video_url, orden, activo) SELECT l.id, "
        . $q($clave) . ", " . $q($fila["tipo"]) . ", " . $q($fila["enunciado"]) . ", " . $q($fila["datos"]) . ", NULL, "
        . $orden . ", " . $activo . " FROM ruta_lecciones l WHERE l.clave = " . $q($leccion) . ";";
};

// ---------- Ejercicios nuevos en lecciones que ya existen (inactivos) ----------
if (!empty($contenido["agregar"])) {
    $sql[] = "-- ---------- Juegos para las lecciones que ya existen (entran " . ($activoAgregados ? "activos" : "inactivos") . ") ----------";
    foreach ($contenido["agregar"] as $leccion => $grupo) {
        foreach ($grupo["ej"] as $i => $ej) {
            $orden = (int) $grupo["desde"] + $i;
            $clave = $leccion . "-e" . $orden;
            if ($fila = $preparar($ej, $clave)) {
                $sql[] = $insertarEjercicio($leccion, $clave, $fila, $orden, $activoAgregados);
                $total["agregados"]++;
            }
        }
    }
}

// ---------- Lecciones existentes que cambian de nombre ----------
if (!empty($contenido["renombrar"])) {
    $sql[] = "";
    $sql[] = "-- ---------- Lecciones que cambian de nombre ----------";
    foreach ($contenido["renombrar"] as $leccion => [$titulo, $descripcion]) {
        $sql[] = "UPDATE ruta_lecciones SET titulo = " . $q($titulo) . ", descripcion = " . $q($descripcion) . " WHERE clave = " . $q($leccion) . ";";
    }
}

// ---------- Unidades y lecciones nuevas ----------
foreach ($contenido["unidades"] ?? [] as $u) {
    $sql[] = "";
    $sql[] = "-- ---------- " . $u["titulo"] . ($publicar ? " (publicada; el nivel sigue oculto)" : " (sin publicar)") . " ----------";
    $sql[] = "INSERT IGNORE INTO ruta_unidades (nivel_id, clave, titulo, descripcion, icono, orden, publicado) SELECT n.id, "
        . $q($u["clave"]) . ", " . $q($u["titulo"]) . ", " . $q($u["descripcion"]) . ", " . $q($u["icono"]) . ", " . (int) $u["orden"]
        . ", " . $publicar . " FROM ruta_niveles n INNER JOIN idiomas i ON i.id = n.idioma_id WHERE i.codigo = " . $q($idioma) . " AND n.codigo = " . $q($codigoNivel) . ";";
    foreach ($u["lecciones"] as $li => $l) {
        $claveL = $u["clave"] . "-l" . ($li + 1);
        $sql[] = "INSERT IGNORE INTO ruta_lecciones (unidad_id, clave, titulo, descripcion, video_url, orden, xp, publicado) SELECT u.id, "
            . $q($claveL) . ", " . $q($l["titulo"]) . ", " . $q($l["descripcion"]) . ", NULL, " . ($li + 1)
            . ", 10, " . $publicar . " FROM ruta_unidades u WHERE u.clave = " . $q($u["clave"]) . ";";
        foreach ($l["ej"] as $i => $ej) {
            $clave = $claveL . "-e" . ($i + 1);
            if ($fila = $preparar($ej, $clave)) {
                $sql[] = $insertarEjercicio($claveL, $clave, $fila, $i + 1, 1);
                $total["nuevos"]++;
            }
        }
    }
}

if ($errores) {
    fwrite(STDERR, "No se generó nada. Ejercicios con errores:\n - " . implode("\n - ", $errores) . "\n");
    exit(1);
}

$salida = __DIR__ . "/../database/lsc_" . $nivel . "_juegos.sql";
$estado = $publicar || $activoAgregados
    ? "-- - El nivel {$codigoNivel} sigue SIN PUBLICAR: los juegos entran activos y las\n--   unidades nuevas publicadas; nadie los ve hasta que se publique el nivel."
    : "-- - Los ejercicios que se suman a lecciones ya publicadas entran\n--   INACTIVOS (activo = 0): el revisor los activa en Admin > Ruta.\n-- - Las unidades y lecciones nuevas entran SIN PUBLICAR.";
$cabecera = <<<SQL
-- =============================================================
-- BiGlobal · LSC {$codigoNivel}: juegos y lecciones nuevas (fase 2)
-- =============================================================
-- Generado con: php tools/generar_lsc.php {$nivel}  (fuente: tools/lsc/{$nivel}.php)
-- Validado con RutaModel::datosEjercicio. Requiere el contenido base de LSC.
--
{$estado}
-- - Los ejercicios «Ver la seña» y «Deletreo» van sin video y no se
--   muestran hasta que se pegue el video (lista en database/lsc_videos_{$nivel}.csv).
--
-- Usa INSERT IGNORE con claves únicas: se puede ejecutar más de una vez.
-- =============================================================

SET NAMES utf8mb4;

SQL;
file_put_contents($salida, $cabecera . "\n" . implode("\n", $sql) . "\n");
printf("Listo: %s\n  %d ejercicios en lecciones existentes (" . ($activoAgregados ? "activos" : "inactivos") . "), %d en lecciones nuevas; %d esperan video.\n",
    "database/lsc_" . $nivel . "_juegos.sql", $total["agregados"], $total["nuevos"], $total["video"]);
