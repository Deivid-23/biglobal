<?php

/**
 * Cambios del admin en la ruta de aprendizaje (todo por POST con CSRF).
 * Las reglas están en app/Models/RutaModel.php.
 *   accion = guardar_nivel | guardar_unidad | guardar_leccion | guardar_ejercicio | eliminar
 */

require_once __DIR__ . "/../../app/bootstrap.php";
require_once __DIR__ . "/../../app/Models/RutaModel.php";

protegerRol("admin");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php?page=ruta");
    exit;
}
verificarCsrfFormulario($_POST["csrf_token"] ?? "");

$c = Conexion::conectar();
if (!RutaModel::disponible($c)) {
    Panel::volver("index.php?page=ruta", "error", "La ruta de aprendizaje aún no está disponible.");
}

$accion = (string) ($_POST["accion"] ?? "");
$urlNivel = fn(int $nivelId): string => "index.php?page=ruta&nivel=" . $nivelId;
$urlLeccion = fn(int $leccionId): string => "index.php?page=ruta&leccion=" . $leccionId;

try {
    switch ($accion) {

        case "guardar_nivel":
            $nivel = RutaModel::nivel($c, (int) ($_POST["nivel_id"] ?? 0));
            if (!$nivel) {
                Panel::volver("index.php?page=ruta", "error", "El nivel no existe.");
            }
            $r = RutaModel::guardarNivel($c, $nivel, $_POST);
            Panel::volver($urlNivel((int) $nivel["id"]), $r === true ? "ok" : "error", $r === true ? "Nivel guardado." : $r);

        case "guardar_unidad":
            $nivel = RutaModel::nivel($c, (int) ($_POST["nivel_id"] ?? 0));
            if (!$nivel) {
                Panel::volver("index.php?page=ruta", "error", "El nivel no existe.");
            }
            $unidad = null;
            if ((int) ($_POST["unidad_id"] ?? 0) > 0) {
                $unidad = RutaModel::unidad($c, (int) $_POST["unidad_id"]);
                if (!$unidad || (int) $unidad["nivel_id"] !== (int) $nivel["id"]) {
                    Panel::volver($urlNivel((int) $nivel["id"]), "error", "La unidad no existe.");
                }
            }
            $r = RutaModel::guardarUnidad($c, (int) $nivel["id"], $unidad, $_POST);
            Panel::volver($urlNivel((int) $nivel["id"]) . (is_int($r) ? "#unidad-" . $r : ""), is_int($r) ? "ok" : "error",
                is_int($r) ? ($unidad ? "Unidad guardada." : "Unidad creada. Agrégale lecciones.") : $r);

        case "guardar_leccion":
            $leccion = null;
            if ((int) ($_POST["leccion_id"] ?? 0) > 0) {
                $leccion = RutaModel::leccion($c, (int) $_POST["leccion_id"]);
                if (!$leccion) {
                    Panel::volver("index.php?page=ruta", "error", "La lección no existe.");
                }
                $unidad = RutaModel::unidad($c, (int) $leccion["unidad_id"]);
            } else {
                $unidad = RutaModel::unidad($c, (int) ($_POST["unidad_id"] ?? 0));
                if (!$unidad) {
                    Panel::volver("index.php?page=ruta", "error", "La unidad no existe.");
                }
            }
            $r = RutaModel::guardarLeccion($c, (int) $unidad["id"], $leccion, $_POST);
            if (is_int($r)) {
                Panel::volver($urlLeccion($r), "ok", $leccion ? "Lección guardada." : "Lección creada. Ahora agrégale ejercicios.");
            }
            Panel::volver($leccion ? $urlLeccion((int) $leccion["id"]) : $urlNivel((int) $unidad["nivel_id"]) . "#unidad-" . (int) $unidad["id"], "error", $r);

        case "guardar_ejercicio":
            $leccion = RutaModel::leccion($c, (int) ($_POST["leccion_id"] ?? 0));
            if (!$leccion) {
                Panel::volver("index.php?page=ruta", "error", "La lección no existe.");
            }
            $ejercicio = null;
            if ((int) ($_POST["ejercicio_id"] ?? 0) > 0) {
                $ejercicio = RutaModel::ejercicio($c, (int) $_POST["ejercicio_id"]);
                if (!$ejercicio || (int) $ejercicio["leccion_id"] !== (int) $leccion["id"]) {
                    Panel::volver($urlLeccion((int) $leccion["id"]), "error", "El ejercicio no existe.");
                }
            }
            $r = RutaModel::guardarEjercicio($c, (int) $leccion["id"], $ejercicio, $_POST);
            if (is_int($r)) {
                Panel::volver($urlLeccion((int) $leccion["id"]) . "#ejercicio-" . $r, "ok", $ejercicio ? "Ejercicio guardado." : "Ejercicio agregado.");
            }
            // Error: vuelve al formulario con lo que escribió
            $_SESSION["ruta_form_ejercicio"] = $_POST;
            Panel::volver($urlLeccion((int) $leccion["id"]) . ($ejercicio ? "&ejercicio=" . (int) $ejercicio["id"] : "") . "#form-ejercicio", "error", $r);

        case "eliminar":
            $que = (string) ($_POST["que"] ?? "");
            $id = (int) ($_POST["id"] ?? 0);
            $volver = (string) ($_POST["volver"] ?? "");
            // Solo se vuelve a páginas de la ruta (nada de redirecciones a otro sitio)
            $volver = preg_match('/^index\.php\?page=ruta(&[a-z]+=\d+)*$/', $volver) ? $volver : "index.php?page=ruta";
            $nombre = "";
            if ($que === "unidad" && ($u = RutaModel::unidad($c, $id))) {
                $nombre = $u["titulo"];
            } elseif ($que === "leccion" && ($l = RutaModel::leccion($c, $id))) {
                $nombre = $l["titulo"];
            } elseif ($que === "ejercicio" && ($e = RutaModel::ejercicio($c, $id))) {
                $nombre = $e["enunciado"];
            }
            if ($nombre === "" || !RutaModel::eliminar($c, $que, $id)) {
                Panel::volver($volver, "error", "Ya no existe.");
            }
            Auditoria::registrar("ruta.eliminar", "ruta_" . $que, $id, mb_strimwidth($nombre, 0, 120, "…"));
            Panel::volver($volver, "ok", ["unidad" => "Unidad eliminada.", "leccion" => "Lección eliminada.", "ejercicio" => "Ejercicio eliminado."][$que]);

        default:
            Panel::volver("index.php?page=ruta", "error", "Acción no válida.");
    }
} catch (Throwable $e) {
    error_log("[BiGlobal][ruta_acciones] " . $e->getMessage());
    Panel::volver("index.php?page=ruta", "error", "No pudimos guardar el cambio. Inténtalo de nuevo.");
}
