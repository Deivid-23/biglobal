<?php
/* Pantalla 403: se muestra cuando alguien entra a un panel que no es de su rol.
   La incluye protegerRol(); no se abre directamente. */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="../js/tema.js"></script>
    <title>Acceso denegado | BiGlobal</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Manrope:wght@600;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha384-PPIZEGYM1v8zp5Py7UjFb79S58UeqCL9pYVnVPURKEqvioPROaVAJKKLzvH2rDnI" crossorigin="anonymous" referrerpolicy="no-referrer">
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
        <section class="auth-card auth-card--center">
            <h1 class="auth-title">Esta sección no es para tu cuenta</h1>
            <p class="auth-text">Tu rol no tiene permiso para ver esta página. Vuelve a tu panel o inicia sesión con otra cuenta.</p>
            <div class="auth-actions">
                <a class="boton boton--primario" href="../auth/login.html">Ir al inicio de sesión</a>
                <a class="boton boton--secundario" href="../index.html">Página principal</a>
            </div>
        </section>
    </main>
</body>
</html>
