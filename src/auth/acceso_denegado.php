<?php
/* Pantalla 403: se muestra cuando alguien entra a un panel que no es de su rol.
   La incluye protegerRol(); no se abre directamente. */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso denegado | BiGlobal</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Manrope:wght@600;800&display=swap">
    <link rel="stylesheet" href="../css/tokens.css">
    <link rel="stylesheet" href="../css/componentes.css">
    <link rel="stylesheet" href="../css/auth.css">
</head>
<body class="auth-body">
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
