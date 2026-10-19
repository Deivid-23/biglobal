<?php

require_once __DIR__ . "/../../app/bootstrap.php";

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* Si no hay datos de Google en sesión → fuera */
if (empty($_SESSION["google_nuevo"])) {
    header("Location: login.html");
    exit;
}

$datos = $_SESSION["google_nuevo"];

/* =========================================================
   Procesar el POST cuando eligen un rol
========================================================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $rolElegido = $_POST["rol"] ?? "";

    if (!csrfValido($_POST["csrf_token"] ?? "")) {
        $error = "Tu sesión expiró. Vuelve a elegir tu rol.";
    } elseif (!in_array($rolElegido, ["estudiante", "docente"], true)) {
        $error = "Debes elegir un rol válido.";
    } else {

        try {
            $conexion = Conexion::conectar();

            /* Verificar que el correo no exista ya */
            $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE correo = ? LIMIT 1");
            $stmt->execute([$datos["correo"]]);

            if ($stmt->fetch()) {
                /* Alguien lo creó mientras tanto → limpiar y volver al login */
                unset($_SESSION["google_nuevo"]);
                header("Location: login.html?error=correo_ya_existe");
                exit;
            }

            /* Rol y activo según elección */
            if ($rolElegido === "docente") {
                $rolId         = 3;
                $activoInicial = 2;
            } else {
                $rolId         = 2;
                $activoInicial = 1;
            }

            /* Crear usuario con password NULL (solo Google) */
            $stmt = $conexion->prepare("
                INSERT INTO usuarios
                    (nombre, apellido, correo, password, google_id, google_avatar, rol_id, activo)
                VALUES
                    (:nombre, :apellido, :correo, NULL, :gid, :avatar, :rol_id, :activo)
            ");
            $stmt->execute([
                ":nombre"   => $datos["nombre"],
                ":apellido" => $datos["apellido"],
                ":correo"   => $datos["correo"],
                ":gid"      => $datos["google_id"],
                ":avatar"   => $datos["avatar"],
                ":rol_id"   => $rolId,
                ":activo"   => $activoInicial,
            ]);

            $nuevoId = (int) $conexion->lastInsertId();

            /* Iniciar sesión automáticamente */
            session_regenerate_id(true);

            $_SESSION["usuario_id"] = $nuevoId;
            $_SESSION["nombre"]     = $datos["nombre"];
            $_SESSION["apellido"]   = $datos["apellido"];
            $_SESSION["correo"]     = $datos["correo"];
            $_SESSION["rol_id"]     = $rolId;
            $_SESSION["rol"]        = $rolElegido;

            /* Limpiar datos temporales */
            unset($_SESSION["google_nuevo"]);

            /* Redirigir según rol */
            if ($rolElegido === "docente") {
                header("Location: ../profesor/pendiente.php");
                exit;
            }

            header("Location: ../estudiante/index.php");
            exit;

        } catch (PDOException $e) {
            error_log("[BiGlobal][elegir_rol] " . $e->getMessage());
            $error = "Error al crear la cuenta. Intenta de nuevo.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <script src="../js/tema.js"></script>
    <title>Elige cómo usar BiGlobal</title>
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

    <section class="auth-card">
        <h1 class="auth-title">Casi listo, <?= htmlspecialchars($datos["nombre"], ENT_QUOTES, "UTF-8") ?></h1>
        <p class="auth-text">¿Cómo quieres usar BiGlobal? Elige una opción para terminar tu cuenta.</p>

        <?php if (!empty($error)): ?>
            <div class="aviso aviso--error" role="alert" style="margin-top: var(--esp-4);">
                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                <span><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" class="lista-roles">
            <?= campoCsrf() ?>

            <button type="submit" name="rol" value="estudiante" class="opcion-grande">
                <i class="fa-solid fa-graduation-cap" aria-hidden="true"></i>
                <span><strong>Quiero aprender</strong>Toma cursos de inglés, español y LSC.</span>
            </button>

            <button type="submit" name="rol" value="docente" class="opcion-grande">
                <i class="fa-solid fa-chalkboard-user" aria-hidden="true"></i>
                <span><strong>Quiero enseñar</strong>Crea cursos. Un administrador aprueba tu cuenta.</span>
            </button>
        </form>

        <p class="nota-legal">
            ¿No eras tú? <a href="logout_cancelar_google.php">Cancelar y volver</a>
        </p>
    </section>

</main>
</body>
</html>
