<?php
/**
 * El docente PENDIENTE agrega diplomas o certificados a su solicitud.
 * Solo POST con CSRF. Mismas reglas que en el registro (DocenteSolicitudModel).
 */
require_once __DIR__ . "/../../app/bootstrap.php";
require_once __DIR__ . "/../../app/Models/DocenteSolicitudModel.php";

protegerRol("docente");

$volver = function (string $tipo, string $mensaje): void {
    $_SESSION["aviso_documentos"] = ["tipo" => $tipo, "mensaje" => $mensaje];
    header("Location: pendiente.php");
    exit;
};

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: pendiente.php");
    exit;
}
verificarCsrfFormulario($_POST["csrf_token"] ?? "");

$conexion = Conexion::conectar();
$usuarioId = (int) $_SESSION["usuario_id"];

// Solo mientras la solicitud está pendiente
$stmt = $conexion->prepare("SELECT activo FROM usuarios WHERE id = ?");
$stmt->execute([$usuarioId]);
if ((int) $stmt->fetchColumn() !== 2 || !DocenteSolicitudModel::disponible($conexion)) {
    header("Location: index.php");
    exit;
}

if (!LimiteIntentos::permitir("documentos:usuario:" . $usuarioId, 10, 3600)) {
    $volver("error", "Subiste muchos archivos seguidos. Espera un rato.");
}

$archivos = DocenteSolicitudModel::validarArchivos($_FILES["documentos"] ?? null, count(DocenteSolicitudModel::documentosDe($conexion, $usuarioId)));
if (is_string($archivos)) {
    $volver("error", $archivos);
}

DocenteSolicitudModel::guardarSolicitud($conexion, $usuarioId, null);
DocenteSolicitudModel::guardarDocumentos($conexion, $usuarioId, $archivos);
$volver("ok", count($archivos) === 1 ? "Subimos tu documento." : "Subimos tus " . count($archivos) . " documentos.");
