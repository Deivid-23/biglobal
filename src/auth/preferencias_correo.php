<?php
/**
 * Guarda la preferencia de correos no esenciales (cualquier rol).
 * Solo POST con CSRF. Cada persona cambia solo la suya.
 * Los correos esenciales (pagos, cuenta, aprobación de docente) no se pueden apagar.
 */
require_once __DIR__ . "/../../app/bootstrap.php";

protegerSesion();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: " . panelDeRol(rolActual()));
    exit;
}
verificarCsrfFormulario($_POST["csrf_token"] ?? "");

$recibir = !empty($_POST["correos_opcionales"]);
$ok = Notificador::guardarPreferencia((int) $_SESSION["usuario_id"], $recibir);

// Estudiante: la casilla del recordatorio de racha va en el mismo formulario
if ($ok && rolActual() === "estudiante" && !empty($_POST["con_recordatorio"])) {
    require_once __DIR__ . "/../../app/Models/GamificacionModel.php";
    GamificacionModel::guardarRecordatorio(Conexion::conectar(), (int) $_SESSION["usuario_id"], !empty($_POST["recordatorio_racha"]));
}

$mensaje = !$ok ? "No se pudo guardar tu preferencia. Inténtalo de nuevo."
    : ($recibir ? "Listo: recibirás también los correos no esenciales." : "Listo: solo recibirás los correos esenciales (pagos y cuenta). Todo lo demás seguirá en la campana.");

// Volver al perfil de su panel, con el aviso en el formato de cada panel
$panel = panelDeRol(rolActual());
if (rolActual() === "estudiante") {
    $_SESSION["flash_estudiante"] = ["tipo" => $ok ? "exito" : "error", "mensaje" => $mensaje];
    header("Location: ../estudiante/index.php?page=perfil#titulo-correos");
} else {
    $_SESSION["flash_panel"] = ["tipo" => $ok ? "ok" : "error", "mensaje" => $mensaje];
    $perfil = rolActual() === "docente" ? "../profesor/index.php?page=perfil" : "../admin/index.php?page=perfil";
    header("Location: " . $perfil . "#titulo-correos");
}
exit;
