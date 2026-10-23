<?php
/**
 * Sube o quita la foto de perfil de la PROPIA cuenta (todos los roles).
 * POST con CSRF: accion = subir (archivo "foto") | quitar.
 * Vuelve a "Mi perfil" de cada rol con el mensaje.
 */
require_once __DIR__ . "/../../app/bootstrap.php";

protegerSesion();
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../index.html");
    exit;
}
verificarCsrfFormulario($_POST["csrf_token"] ?? "");

$usuarioId = (int) $_SESSION["usuario_id"];
$rol = rolActual();

$volver = function (bool $ok, string $mensaje) use ($rol): void {
    if ($rol === "estudiante") {
        $_SESSION["flash_estudiante"] = ["tipo" => $ok ? "exito" : "error", "mensaje" => $mensaje];
        header("Location: ../estudiante/index.php?page=perfil#foto");
        exit;
    }
    Panel::volver(($rol === "docente" ? "../profesor/index.php?page=perfil" : "../admin/index.php?page=perfil") . "#foto", $ok ? "ok" : "error", $mensaje);
};

if (!in_array($rol, ["estudiante", "docente", "admin", "superadmin"], true)) {
    http_response_code(403);
    exit;
}
if (!LimiteIntentos::permitir("foto_perfil:usuario:" . $usuarioId, 15, 600)) {
    $volver(false, "Cambiaste tu foto muchas veces seguidas. Espera unos minutos.");
}

try {
    if (($_POST["accion"] ?? "") === "quitar") {
        FotoPerfil::quitar($usuarioId);
        $volver(true, "Quitamos tu foto. Ahora se muestran tus iniciales.");
    }
    $r = FotoPerfil::guardar($usuarioId, $_FILES["foto"] ?? null);
    $volver($r === true, $r === true ? "¡Listo! Tu foto de perfil quedó guardada." : $r);
} catch (Throwable $e) {
    error_log("[BiGlobal][foto_perfil] " . $e->getMessage());
    $volver(false, "No pudimos guardar tu foto. Inténtalo de nuevo.");
}
