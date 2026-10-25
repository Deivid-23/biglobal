<?php

/**
 * Videos de LSC (Admin › Ruta › Videos de LSC). Las reglas están en
 * app/Models/VideosLscModel.php.
 *   GET  ?descargar=1[&nivel=B1]       -> CSV de los videos que faltan
 *   POST accion = analizar             -> sube el CSV lleno y arma la vista previa (no guarda nada)
 *   POST accion = aplicar | cancelar   -> guarda la vista previa o la descarta
 */

require_once __DIR__ . "/../../app/bootstrap.php";
require_once __DIR__ . "/../../app/Models/VideosLscModel.php";

protegerRol("admin");

$c = Conexion::conectar();
$volver = "index.php?page=videos";

if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["descargar"])) {
    $nivel = strtoupper((string) ($_GET["nivel"] ?? ""));
    $nivel = preg_match('/^[ABC][12]$/', $nivel) ? $nivel : null;
    $csv = VideosLscModel::csv(VideosLscModel::pendientes($c, $nivel));
    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"lsc_videos_" . ($nivel ? strtolower($nivel) : "todos") . ".csv\"");
    header("X-Content-Type-Options: nosniff");
    header("Cache-Control: no-store");
    echo $csv;
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: " . $volver);
    exit;
}
verificarCsrfFormulario($_POST["csrf_token"] ?? "");

try {
    switch ((string) ($_POST["accion"] ?? "")) {

        case "analizar":
            unset($_SESSION["videos_lsc"]);
            $archivo = $_FILES["archivo"] ?? null;
            if (!is_array($archivo) || (int) ($archivo["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                Panel::volver($volver, "error", "Elige el archivo CSV.");
            }
            if ((int) $archivo["error"] !== UPLOAD_ERR_OK || !is_uploaded_file((string) $archivo["tmp_name"])) {
                Panel::volver($volver, "error", "No se pudo subir el archivo. Intenta de nuevo.");
            }
            if ((int) $archivo["size"] > VideosLscModel::MAX_BYTES) {
                Panel::volver($volver, "error", "El archivo es demasiado grande (máximo 1 MB).");
            }
            if (strtolower(pathinfo((string) $archivo["name"], PATHINFO_EXTENSION)) !== "csv") {
                Panel::volver($volver, "error", "El archivo debe ser .csv (en Excel: Guardar como › CSV UTF-8).");
            }
            $r = VideosLscModel::analizar($c, (string) file_get_contents((string) $archivo["tmp_name"]));
            if (is_string($r)) {
                Panel::volver($volver, "error", $r);
            }
            $_SESSION["videos_lsc"] = $r + ["token" => bin2hex(random_bytes(16)), "archivo" => mb_strimwidth((string) $archivo["name"], 0, 80, "…")];
            Panel::volver($volver . "#vista-previa", "ok", "Revisa la vista previa. Todavía no se ha guardado nada.");

        case "aplicar":
            $previa = $_SESSION["videos_lsc"] ?? null;
            if (!$previa || !hash_equals((string) $previa["token"], (string) ($_POST["token"] ?? ""))) {
                Panel::volver($volver, "error", "La vista previa ya no está. Sube el archivo otra vez.");
            }
            unset($_SESSION["videos_lsc"]);
            if (!$previa["cambios"]) {
                Panel::volver($volver, "error", "No había ningún video para guardar.");
            }
            $hechos = VideosLscModel::aplicar($c, $previa["cambios"]);
            Auditoria::registrar("ruta.videos", "ruta_ejercicios", null,
                "Importó " . $hechos . " videos de LSC desde " . $previa["archivo"],
                null, implode(", ", array_slice(array_column($previa["cambios"], 2), 0, 40)));
            Panel::volver($volver, "ok", "Listo: " . plural_panel($hechos, "video guardado", "videos guardados") . ".");

        case "cancelar":
            unset($_SESSION["videos_lsc"]);
            Panel::volver($volver, "ok", "Vista previa descartada. No se guardó nada.");

        default:
            Panel::volver($volver, "error", "Acción no válida.");
    }
} catch (Throwable $e) {
    error_log("[BiGlobal][videos_acciones] " . $e->getMessage());
    Panel::volver($volver, "error", "No se pudo completar. Intenta de nuevo.");
}
