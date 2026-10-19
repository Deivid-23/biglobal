<?php

/**
 * Pantalla de espera para docentes que aún no han sido aprobados.
 * Si el docente ya fue aprobado, va a su panel; si fue rechazado, se cierra la sesión.
 */

require_once __DIR__ . "/../../app/bootstrap.php";

/* =========================================================
   VERIFICAR SESIÓN Y ROL
========================================================= */
if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../auth/login.html");
    exit;
}

/* Otros roles van a su propio panel */
if (rolActual() !== "docente") {
    header("Location: " . panelDeRol(rolActual()));
    exit;
}

/* =========================================================
   VERIFICAR ESTADO DEL DOCENTE
========================================================= */
$conexion = Conexion::conectar();

$stmt = $conexion->prepare("SELECT activo FROM usuarios WHERE id = ? LIMIT 1");
$stmt->execute([(int) $_SESSION["usuario_id"]]);
$activo = (int) $stmt->fetchColumn();

/* Si ya fue aprobado → mandarlo a su panel */
if ($activo === 1) {
    header("Location: index.php");
    exit;
}

/* Si fue rechazado/bloqueado → cerrar sesión */
if ($activo === 0) {
    header("Location: ../auth/logout.php");
    exit;
}

/* activo === 2 → pendiente, seguir mostrando la pantalla */
$nombre = $_SESSION["nombre"] ?? "Docente";
?>
<!DOCTYPE html>
<html lang="es"<?= Panel::atributosHtml() ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <script src="../js/tema.js"></script>
    <meta name="theme-color" content="#EFF6FF">
    <title>Solicitud en revisión | BiGlobal</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Manrope:wght@600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../css/tokens.css">
    <link rel="stylesheet" href="../css/componentes.css">
    <link rel="stylesheet" href="../css/auth.css">
</head>
<body class="auth-body">
<div class="tema-esquina">
    <button class="boton-tema" type="button" data-alternar-tema aria-label="Cambiar a modo oscuro">
        <i class="fa-solid fa-sun icono-sol" aria-hidden="true"></i>
        <i class="fa-solid fa-moon icono-luna" aria-hidden="true"></i>
    </button>
</div>

<main class="auth-shell">
    <a class="marca-logo" href="../index.html">
        <img src="../img/logo-Biglobal.png" alt="" width="40" height="40"> BiGlobal
    </a>

    <section class="auth-card auth-card--center">
        <span class="vacio__icono" style="margin: 0 auto var(--esp-4); background: var(--naranja-100); color: var(--naranja-700);">
            <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i>
        </span>
        <h1 class="auth-title">Tu solicitud está en revisión</h1>
        <p class="auth-text">
            Hola, <?= e($nombre) ?>. Tu cuenta de <strong>docente</strong> se creó correctamente,
            pero un administrador todavía debe aprobarla.
        </p>

        <div class="aviso aviso--info" style="margin-top: var(--esp-6); text-align: left;">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <span><strong>¿Qué sigue?</strong> Cuando aprueben tu cuenta podrás entrar a tu panel con tu correo y contraseña.</span>
        </div>

        <div class="auth-actions">
            <a class="boton boton--primario" href="../auth/logout.php">
                <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i> Cerrar sesión
            </a>
            <a class="boton boton--secundario" href="../index.html">Volver al inicio</a>
        </div>
    </section>
</main>
</body>
</html>
