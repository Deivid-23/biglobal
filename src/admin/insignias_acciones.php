<?php

/**
 * Cambios del admin en las insignias (todo por POST con CSRF).
 *   accion = guardar | activar | dar_ahora
 * Las reglas están en app/Models/InsigniaModel.php.
 */

require_once __DIR__ . "/../../app/bootstrap.php";
require_once __DIR__ . "/../../app/Models/InsigniaModel.php";

protegerRol("admin");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php?page=insignias");
    exit;
}
verificarCsrfFormulario($_POST["csrf_token"] ?? "");

$c = Conexion::conectar();
$volver = "index.php?page=insignias";
if (!InsigniaModel::disponible($c)) {
    Panel::volver($volver, "error", "Las insignias aún no están disponibles.");
}

$accion = (string) ($_POST["accion"] ?? "");
$insignia = (int) ($_POST["insignia_id"] ?? 0) > 0 ? InsigniaModel::porId($c, (int) $_POST["insignia_id"]) : null;
if ($accion !== "guardar" && !$insignia) {
    Panel::volver($volver, "error", "La insignia no existe.");
}

try {
    switch ($accion) {

        case "guardar":
            if ((int) ($_POST["insignia_id"] ?? 0) > 0 && !$insignia) {
                Panel::volver($volver, "error", "La insignia no existe.");
            }
            $r = InsigniaModel::guardar($c, $insignia, $_POST);
            if (is_string($r)) {
                $_SESSION["insignia_form"] = $_POST;
                Panel::volver($volver . ($insignia ? "&editar=" . (int) $insignia["id"] : "") . "#form-insignia", "error", $r);
            }
            Auditoria::registrar($insignia ? "insignia.editar" : "insignia.crear", "insignia", $r, trim((string) $_POST["nombre"]));
            Panel::volver($volver . "#insignia-" . $r, "ok", $insignia
                ? "Insignia guardada."
                : "Insignia creada. Usa «Dar ahora» si quieres dársela a quienes ya cumplen.");

        case "activar":
            $activa = ($_POST["activa"] ?? "") === "1";
            InsigniaModel::activar($c, (int) $insignia["id"], $activa);
            Auditoria::registrar($activa ? "insignia.activar" : "insignia.desactivar", "insignia", (int) $insignia["id"], $insignia["nombre"]);
            Panel::volver($volver . "#insignia-" . (int) $insignia["id"], "ok", $activa
                ? "Insignia activada."
                : "Insignia desactivada: ya no se da ni se muestra. Quienes la tenían la conservan.");

        case "dar_ahora":
            if (!LimiteIntentos::permitir("insignias:dar:" . (int) $_SESSION["usuario_id"], 20, 600)) {
                Panel::volver($volver, "error", "Hiciste muchas revisiones seguidas. Espera unos minutos.");
            }
            $dadas = InsigniaModel::darAQuienesCumplen($c, (int) $insignia["id"]);
            Auditoria::registrar("insignia.dar_ahora", "insignia", (int) $insignia["id"], $insignia["nombre"], null, (string) $dadas);
            Panel::volver($volver . "#insignia-" . (int) $insignia["id"], "ok", $dadas > 0
                ? "Listo: «" . $insignia["nombre"] . "» se le dio a " . plural_panel($dadas, "estudiante", "estudiantes") . " (les llegó a la campana)."
                : "Nadie más cumple todavía la condición de «" . $insignia["nombre"] . "».");

        default:
            Panel::volver($volver, "error", "Acción no válida.");
    }
} catch (Throwable $e) {
    error_log("[BiGlobal][insignias_acciones] " . $e->getMessage());
    Panel::volver($volver, "error", "No pudimos guardar el cambio. Inténtalo de nuevo.");
}
