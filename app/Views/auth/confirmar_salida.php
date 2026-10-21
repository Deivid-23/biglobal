<?php
/**
 * "¿Cerrar sesión?" — se muestra cuando alguien abre auth/logout.php
 * con un enlace (GET). Cerrar sesión solo se hace con este botón (POST + CSRF).
 */
$volverA = panelDeRol(rolActual());
?>
<!DOCTYPE html>
<html lang="es"<?= Panel::atributosHtml() ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <script src="../js/tema.js"></script>
    <meta name="theme-color" content="#EFF6FF">
    <title>Cerrar sesión | BiGlobal</title>
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
        <h1 class="auth-title">¿Cerrar sesión?</h1>
        <p class="auth-text">Tu progreso queda guardado. Podrás volver a entrar cuando quieras.</p>

        <form class="auth-actions" method="POST" action="logout.php">
            <?= campoCsrf() ?>
            <button class="boton boton--primario" type="submit">
                <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i> Cerrar sesión
            </button>
            <a class="boton boton--secundario" href="<?= e($volverA) ?>">Volver a mi panel</a>
        </form>
    </section>
</main>
</body>
</html>
