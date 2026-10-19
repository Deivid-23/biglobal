<?php
require_once __DIR__ . "/../auth/proteger.php";
require_once __DIR__ . "/../bd/conexion.php";
require_once __DIR__ . "/../auth/csrf.php";

protegerRol("admin");

$conexion = Conexion::conectar();

/* Redirigir siempre a solicitudes */
function volverCon(string $tipo, string $mensaje): void
{
    $_SESSION["flash_solicitudes"] = [
        "tipo"    => $tipo,
        "mensaje" => $mensaje,
    ];
    header("Location: index.php?page=solicitudes");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php?page=solicitudes");
    exit;
}

verificarCsrfFormulario($_POST["csrf_token"] ?? "");

$accion = $_POST["accion"] ?? "";
$id     = (int) ($_POST["id"] ?? 0);

if ($id <= 0) {
    volverCon("error", "ID inválido.");
}

try {

    /* Verificar que sea un docente */
    $stmt = $conexion->prepare("
        SELECT u.id, u.nombre, u.activo
        FROM usuarios u
        INNER JOIN roles r ON u.rol_id = r.id
        WHERE u.id = ? AND LOWER(r.nombre) = 'docente'
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $docente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$docente) {
        volverCon("error", "Docente no encontrado.");
    }

    switch ($accion) {

        case "aprobar":
            $stmt = $conexion->prepare("UPDATE usuarios SET activo = 1 WHERE id = ?");
            $stmt->execute([$id]);
            volverCon("ok", "Docente aprobado correctamente.");
            break;

        case "rechazar":
            $stmt = $conexion->prepare("UPDATE usuarios SET activo = 0 WHERE id = ?");
            $stmt->execute([$id]);
            volverCon("ok", "Docente rechazado.");
            break;

        case "eliminar":
            $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            volverCon("ok", "Docente eliminado definitivamente.");
            break;

        default:
            volverCon("error", "Acción no reconocida.");
    }

} catch (PDOException $e) {
    error_log("[BiGlobal][solicitudes] " . $e->getMessage());
    volverCon("error", "Error de base de datos. Intenta de nuevo.");
}