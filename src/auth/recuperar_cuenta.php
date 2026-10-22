<?php
/**
 * Recuperar una cuenta con eliminación pendiente.
 *
 * Se llega aquí SOLO después de verificar la contraseña o Google
 * (AuthController / google_callback -> iniciarRecuperacionCuenta), que
 * deja en la sesión el id de la cuenta por 15 minutos, sin iniciar sesión.
 *   GET  -> muestra la fecha de borrado y el botón "Recuperar mi cuenta"
 *   POST -> (con CSRF) devuelve la cuenta a su estado e inicia sesión
 */
require_once __DIR__ . "/../../app/bootstrap.php";
require_once __DIR__ . "/../../app/Models/CuentaEliminacionModel.php";

$id = (int) ($_SESSION["recuperar_cuenta_id"] ?? 0);
if ($id <= 0 || (int) ($_SESSION["recuperar_cuenta_hasta"] ?? 0) < time()) {
    $_SESSION = [];
    header("Location: login.html");
    exit;
}

$conexion = Conexion::conectar();
$cuenta = CuentaEliminacionModel::pendiente($conexion, $id);
if (!$cuenta) {
    $_SESSION = [];
    header("Location: login.html?error=cuenta_inactiva");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verificarCsrfFormulario($_POST["csrf_token"] ?? "");

    if (($_POST["accion"] ?? "") === "salir") {
        cerrarSesion();
        header("Location: ../index.html");
        exit;
    }

    // Se inicia la sesión (la auditoría usa la sesión para saber quién lo hizo)
    session_regenerate_id(true);
    $_SESSION = [
        "usuario_id" => (int) $cuenta["id"],
        "nombre"     => $cuenta["nombre"],
        "apellido"   => $cuenta["apellido"],
        "correo"     => $cuenta["correo"],
        "rol_id"     => (int) $cuenta["rol_id"],
        "rol"        => $cuenta["rol"],
    ];

    if (!CuentaEliminacionModel::recuperar($conexion, $id)) {
        $_SESSION = [];
        header("Location: login.html?error=cuenta_inactiva");
        exit;
    }

    // Un docente que aún no estaba aprobado vuelve a su pantalla de espera
    $stmt = $conexion->prepare("SELECT activo FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: " . ((int) $stmt->fetchColumn() === 2 ? "../profesor/pendiente.php" : panelDeRol($cuenta["rol"])));
    exit;
}

$fecha = CuentaEliminacionModel::fecha($cuenta["eliminacion_programada_para"]);
?>
<!DOCTYPE html>
<html lang="es"<?= Panel::atributosHtml() ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <script src="../js/tema.js"></script>
    <meta name="theme-color" content="#EFF6FF">
    <title>Recuperar tu cuenta | BiGlobal</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Manrope:wght@600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha384-PPIZEGYM1v8zp5Py7UjFb79S58UeqCL9pYVnVPURKEqvioPROaVAJKKLzvH2rDnI" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="../css/tokens.css">
    <link rel="stylesheet" href="../css/componentes.css">
    <link rel="stylesheet" href="../css/auth.css">
</head>
<body class="auth-body">
<main class="auth-shell">
    <a class="marca-logo" href="../index.html">
        <img src="../img/logo-Biglobal.png" alt="" width="40" height="40"> BiGlobal
    </a>

    <section class="auth-card auth-card--center">
        <h1 class="auth-title">Tu cuenta se eliminará el <?= e($fecha) ?></h1>
        <p class="auth-text">
            Hola, <?= e($cuenta["nombre"]) ?>. Pediste eliminar tu cuenta. Si la recuperas ahora, todo queda como estaba:
            tu progreso, XP, medallas y certificados.
        </p>
        <form class="auth-actions" method="POST" action="recuperar_cuenta.php">
            <?= campoCsrf() ?>
            <button class="boton boton--primario" type="submit" name="accion" value="recuperar">
                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Recuperar mi cuenta
            </button>
            <button class="boton boton--secundario" type="submit" name="accion" value="salir">Seguir con la eliminación</button>
        </form>
    </section>
</main>
</body>
</html>
