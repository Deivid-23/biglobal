<?php

/**
 * Acciones sobre las solicitudes de docentes (solo POST).
 *
 *   aprobar    -> admin y super administrador (también reactiva). Avisa al docente.
 *   rechazar   -> admin y super administrador, con MOTIVO obligatorio. Avisa al docente.
 *   desactivar -> desactiva a un docente ya aprobado (no es un rechazo de solicitud)
 *   eliminar   -> SOLO super administrador (borrado definitivo)
 *
 * El permiso se revisa aquí, en el servidor: aunque un admin envíe
 * el formulario a mano, "eliminar" le responde 403.
 */

require_once __DIR__ . "/../../app/bootstrap.php";
require_once __DIR__ . "/../../app/Models/DocenteSolicitudModel.php";

protegerRol("admin");

$volver = "index.php?page=solicitudes";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: " . $volver);
    exit;
}

verificarCsrfFormulario($_POST["csrf_token"] ?? "");

$accion = $_POST["accion"] ?? "";
$id     = (int) ($_POST["id"] ?? 0);

if ($id <= 0) {
    Panel::volver($volver, "error", "Solicitud no válida.");
}

if ($accion === "eliminar" && !esSuperAdmin()) {
    http_response_code(403);
    Panel::volver($volver, "error", "Solo el super administrador puede eliminar cuentas de forma definitiva.");
}

try {
    $conexion = Conexion::conectar();

    /* Solo se actúa sobre cuentas que de verdad son de docente */
    $stmt = $conexion->prepare("
        SELECT u.id, u.activo FROM usuarios u
        INNER JOIN roles r ON u.rol_id = r.id
        WHERE u.id = ? AND r.nombre = 'docente'
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $docente = $stmt->fetch();

    if (!$docente) {
        Panel::volver($volver, "error", "Docente no encontrado.");
    }

    /* Para el registro de auditoría (Super admin → Auditoría) */
    $nombreDocente = Auditoria::nombreCuenta($conexion, $id);
    $estadoAntes = Auditoria::ESTADOS[(int) $docente["activo"]] ?? (string) $docente["activo"];

    switch ($accion) {
        case "aprobar":
            if (!in_array((int) $docente["activo"], [0, 2], true)) {
                Panel::volver($volver, "error", "Este docente ya estaba activo.");
            }
            $conexion->prepare("UPDATE usuarios SET activo = 1 WHERE id = ?")->execute([$id]);
            DocenteSolicitudModel::aprobar($conexion, $id, (int) $_SESSION["usuario_id"]);
            Auditoria::registrar("docente.aprobar", "cuenta", $id, $nombreDocente, $estadoAntes, Auditoria::ESTADOS[1]);
            Panel::volver($volver, "ok", "Docente aprobado. Le avisamos por la campana y por correo.");

        case "rechazar":
            $motivo = trim((string) ($_POST["motivo"] ?? ""));
            if (mb_strlen($motivo) < 10 || mb_strlen($motivo) > 500) {
                Panel::volver($volver, "error", "Escribe el motivo del rechazo (entre 10 y 500 caracteres): se lo enviamos al docente.");
            }
            if ((int) $docente["activo"] !== 2) {
                Panel::volver($volver, "error", "Solo se rechazan solicitudes pendientes. Para un docente activo usa «Desactivar».");
            }
            $conexion->prepare("UPDATE usuarios SET activo = 0 WHERE id = ?")->execute([$id]);
            DocenteSolicitudModel::rechazar($conexion, $id, (int) $_SESSION["usuario_id"], $motivo);
            Auditoria::registrar("docente.rechazar", "cuenta", $id, $nombreDocente, $estadoAntes, Auditoria::ESTADOS[0] . " · " . $motivo);
            Panel::volver($volver, "ok", "Solicitud rechazada. Le enviamos el motivo al docente.");

        case "desactivar":
            if ((int) $docente["activo"] !== 1) {
                Panel::volver($volver, "error", "Este docente no está activo.");
            }
            $conexion->prepare("UPDATE usuarios SET activo = 0 WHERE id = ?")->execute([$id]);
            Auditoria::registrar("cuenta.estado", "cuenta", $id, $nombreDocente, $estadoAntes, Auditoria::ESTADOS[0]);
            Panel::volver($volver, "ok", "Docente desactivado.");

        case "eliminar":
            $conexion->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$id]);
            Auditoria::registrar("cuenta.eliminar", "cuenta", $id, $nombreDocente, "docente", null);
            Panel::volver($volver, "ok", "Cuenta eliminada definitivamente.");

        default:
            Panel::volver($volver, "error", "Acción no reconocida.");
    }

} catch (PDOException $e) {
    error_log("[BiGlobal][solicitudes] " . $e->getMessage());
    Panel::volver($volver, "error", "Error de base de datos. Intenta de nuevo.");
}
