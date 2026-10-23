<?php

/**
 * Acciones del docente sobre sus grupos (todas por POST con CSRF).
 * Las reglas están en app/Models/GrupoModel.php; aquí solo se revisa que
 * el grupo sea del docente y se redirige con el mensaje.
 */

require_once __DIR__ . "/../../app/bootstrap.php";
require_once __DIR__ . "/../../app/Models/TareaModel.php";

protegerRol("docente");
require_once __DIR__ . "/verificar_aprobado.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php?page=grupos");
    exit;
}
verificarCsrfFormulario($_POST["csrf_token"] ?? "");

$conexion = Conexion::conectar();
$docenteId = (int) $_SESSION["usuario_id"];

function volverGrupo(string $tipo, string $mensaje, string $url = "index.php?page=grupos"): void
{
    $_SESSION["flash_docente"] = ["tipo" => $tipo, "mensaje" => $mensaje];
    header("Location: " . $url);
    exit;
}

if (!GrupoModel::disponible($conexion)) {
    volverGrupo("error", "Los grupos aún no están disponibles.");
}

$accion = (string) ($_POST["accion"] ?? "");

/* Crear no necesita un grupo existente */
if ($accion === "crear_grupo") {
    $datos = GrupoModel::datosGrupo($conexion, $_POST, $docenteId);
    if (is_string($datos)) {
        volverGrupo("error", $datos);
    }
    $nuevoId = GrupoModel::crear($conexion, $docenteId, $datos);
    volverGrupo("ok", "Grupo creado. Comparte el código con tus estudiantes para que entren.",
        "index.php?page=grupo&grupo=" . $nuevoId . "&tab=estudiantes");
}

/* El resto: el grupo tiene que ser del docente */
$grupo = GrupoModel::deDocente($conexion, (int) ($_POST["grupo_id"] ?? 0), $docenteId);
if (!$grupo) {
    volverGrupo("error", "El grupo no existe o no es tuyo.");
}
$grupoId = (int) $grupo["id"];
$base = "index.php?page=grupo&grupo=" . $grupoId;

try {
    switch ($accion) {

        case "editar_grupo":
            $datos = GrupoModel::datosGrupo($conexion, $_POST, $docenteId);
            if (is_string($datos)) {
                volverGrupo("error", $datos, $base . "&tab=estudiantes");
            }
            GrupoModel::editar($conexion, $grupoId, $docenteId, $datos);
            volverGrupo("ok", "Datos del grupo guardados.", $base . "&tab=estudiantes");

        case "archivar_grupo":
            $archivar = ($_POST["archivar"] ?? "1") === "1";
            GrupoModel::archivar($conexion, $grupoId, $docenteId, $archivar);
            volverGrupo("ok", $archivar
                ? "Grupo archivado. Los estudiantes ya no lo ven y nadie nuevo puede entrar."
                : "Grupo restaurado. Los estudiantes lo vuelven a ver.", $archivar ? "index.php?page=grupos" : $base);

        case "regenerar_codigo":
            $codigo = GrupoModel::regenerarCodigo($conexion, $grupoId, $docenteId);
            volverGrupo("ok", "Nuevo código: " . $codigo . ". El anterior ya no sirve.", $base . "&tab=estudiantes");

        case "quitar_miembro":
            $ok = GrupoModel::quitarMiembro($conexion, $grupoId, $docenteId, (int) ($_POST["usuario_id"] ?? 0));
            volverGrupo($ok ? "ok" : "error", $ok ? "Quitaste al estudiante del grupo." : "Ese estudiante no está en el grupo.", $base . "&tab=estudiantes");

        case "publicar_anuncio":
            $r = GrupoModel::publicarAnuncio($conexion, $grupo, $docenteId, (string) ($_POST["texto"] ?? ""));
            volverGrupo($r === true ? "ok" : "error", $r === true ? "Anuncio publicado. Les avisamos a los estudiantes." : $r, $base);

        case "eliminar_anuncio":
            $ok = GrupoModel::eliminarAnuncio($conexion, (int) ($_POST["anuncio_id"] ?? 0), $grupoId);
            volverGrupo($ok ? "ok" : "error", $ok ? "Anuncio eliminado." : "El anuncio ya no existe.", $base);

        case "comentar":
            $anuncioId = (int) ($_POST["anuncio_id"] ?? 0);
            $r = GrupoModel::comentar($conexion, $grupoId, $anuncioId, $docenteId, (string) ($_POST["texto"] ?? ""));
            volverGrupo($r === true ? "ok" : "error", $r === true ? "Comentario publicado." : $r, $base . "#anuncio-" . $anuncioId);

        case "borrar_comentario":
            // El docente puede borrar cualquier comentario de su grupo
            $ok = GrupoModel::borrarComentario($conexion, $grupoId, (int) ($_POST["comentario_id"] ?? 0), null);
            volverGrupo($ok ? "ok" : "error", $ok ? "Comentario eliminado." : "El comentario ya no existe.", $base . "#anuncio-" . (int) ($_POST["anuncio_id"] ?? 0));

        case "agregar_material":
            $r = GrupoModel::agregarMaterial($conexion, $grupo, $docenteId, $_POST, $_FILES["archivo"] ?? null);
            volverGrupo($r === true ? "ok" : "error", $r === true ? "Material agregado." : $r, $base . "&tab=material");

        case "eliminar_material":
            $ok = GrupoModel::eliminarMaterial($conexion, (int) ($_POST["material_id"] ?? 0), $grupoId);
            volverGrupo($ok ? "ok" : "error", $ok ? "Material eliminado." : "El material ya no existe.", $base . "&tab=material");

        /* ---------- Tareas ---------- */
        case "crear_tarea":
            if (!TareaModel::disponible($conexion)) {
                volverGrupo("error", "Las tareas aún no están disponibles.", $base);
            }
            $r = TareaModel::crear($conexion, $grupo, $docenteId, $_POST, $_FILES["adjuntos"] ?? null);
            if (is_string($r)) {
                volverGrupo("error", $r, $base . "&tab=tareas");
            }
            volverGrupo("ok", "Tarea publicada. Les avisamos a los estudiantes.", $base . "&tab=tareas&tarea=" . $r);

        case "editar_tarea":
        case "eliminar_tarea":
        case "calificar":
            $tarea = TareaModel::disponible($conexion) ? TareaModel::deGrupo($conexion, (int) ($_POST["tarea_id"] ?? 0), $grupoId) : null;
            if (!$tarea) {
                volverGrupo("error", "La tarea ya no existe.", $base . "&tab=tareas");
            }
            $urlTarea = $base . "&tab=tareas&tarea=" . (int) $tarea["id"];

            if ($accion === "eliminar_tarea") {
                TareaModel::eliminar($conexion, (int) $tarea["id"], $grupoId);
                volverGrupo("ok", "Tarea eliminada con sus entregas.", $base . "&tab=tareas");
            }
            if ($accion === "editar_tarea") {
                $quitar = array_map("intval", (array) ($_POST["quitar_adjunto"] ?? []));
                $r = TareaModel::editar($conexion, $tarea, $_POST, $_FILES["adjuntos"] ?? null, $quitar);
                volverGrupo($r === true ? "ok" : "error", $r === true ? "Tarea guardada." : $r, $urlTarea . "#editar-tarea");
            }
            $entregaId = (int) ($_POST["entrega_id"] ?? 0);
            $r = TareaModel::calificar($conexion, $grupo, $tarea, $entregaId, $docenteId,
                (string) ($_POST["puntaje"] ?? ""), (string) ($_POST["comentario"] ?? ""));
            volverGrupo($r === true ? "ok" : "error", $r === true ? "Calificación guardada. Le avisamos al estudiante." : $r, $urlTarea . "#entrega-" . $entregaId);

        default:
            volverGrupo("error", "Acción no válida.", $base);
    }
} catch (Throwable $e) {
    error_log("[BiGlobal][grupos_acciones] " . $e->getMessage());
    volverGrupo("error", "No pudimos guardar el cambio. Inténtalo de nuevo.", $base);
}
