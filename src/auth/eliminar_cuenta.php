<?php
/**
 * Pedir la eliminación de la propia cuenta (cualquier rol). Solo POST con CSRF.
 * Confirmación: la contraseña (si la cuenta tiene) o escribir ELIMINAR
 * (cuentas que solo entran con Google). Ver CuentaEliminacionModel.
 */
require_once __DIR__ . "/../../app/bootstrap.php";
require_once __DIR__ . "/../../app/Models/CuentaEliminacionModel.php";

protegerSesion();

$usuarioId = (int) $_SESSION["usuario_id"];
$esEstudiante = rolActual() === "estudiante";
$perfil = $esEstudiante ? "../estudiante/index.php?page=perfil"
    : (rolActual() === "docente" ? "../profesor/index.php?page=perfil" : "../admin/index.php?page=perfil");

$volver = function (string $mensaje) use ($esEstudiante, $perfil): void {
    if ($esEstudiante) {
        $_SESSION["flash_estudiante"] = ["tipo" => "error", "mensaje" => $mensaje];
    } else {
        $_SESSION["flash_panel"] = ["tipo" => "error", "mensaje" => $mensaje];
    }
    header("Location: " . $perfil . "#titulo-eliminar");
    exit;
};

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: " . $perfil);
    exit;
}
verificarCsrfFormulario($_POST["csrf_token"] ?? "");

// Pocos intentos: confirmar es como volver a iniciar sesión
if (!LimiteIntentos::permitir("eliminar_cuenta:usuario:" . $usuarioId, 5, 900)) {
    $volver("Hiciste muchos intentos. Espera unos minutos.");
}

$conexion = Conexion::conectar();
$stmt = $conexion->prepare("SELECT password FROM usuarios WHERE id = ? LIMIT 1");
$stmt->execute([$usuarioId]);
$hash = (string) $stmt->fetchColumn();

if ($hash !== "") {
    if (!password_verify((string) ($_POST["password"] ?? ""), $hash)) {
        $volver("La contraseña no es correcta. Tu cuenta sigue igual.");
    }
} elseif (trim((string) ($_POST["confirmacion"] ?? "")) !== "ELIMINAR") {
    $volver("Para confirmar escribe ELIMINAR en mayúsculas. Tu cuenta sigue igual.");
}

$resultado = CuentaEliminacionModel::solicitar($conexion, $usuarioId);
if (str_starts_with($resultado, "error:")) {
    $volver(substr($resultado, 6));
}

cerrarSesion();
header("Location: login.html?ok=eliminacion_programada");
exit;
