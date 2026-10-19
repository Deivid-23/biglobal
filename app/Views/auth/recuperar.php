<?php
/**
 * Pantalla "¿Olvidaste tu contraseña?"
 * Variables: $estado (formulario|enviado), $error, $correo, $enlaceLocal
 */
$tituloPagina = "Recupera tu contraseña";
require __DIR__ . "/_cabeza.php";
?>

<section class="auth-card">
    <?php if ($estado === "enviado"): ?>

        <h1 class="auth-title">Revisa tu correo</h1>
        <p class="auth-text">
            Si <strong><?= e($correo) ?></strong> tiene una cuenta en BiGlobal, te enviamos un enlace
            para crear una nueva contraseña. Vence en 60 minutos.
        </p>
        <p class="auth-text">¿No llega? Mira en la carpeta de spam o vuelve a intentarlo en unos minutos.</p>

        <?php if ($enlaceLocal): ?>
            <!-- Solo aparece con APP_ENV=local cuando no hay SMTP configurado -->
            <div class="aviso aviso--alerta" style="margin-top: var(--esp-5);">
                <i class="fa-solid fa-flask" aria-hidden="true"></i>
                <span>Modo local (sin servidor de correo): <a href="<?= e($enlaceLocal) ?>">abrir el enlace de prueba</a>.</span>
            </div>
        <?php endif; ?>

        <div class="auth-actions" style="justify-content: flex-start;">
            <a class="boton boton--primario" href="login.html">Volver a iniciar sesión</a>
        </div>

    <?php else: ?>

        <h1 class="auth-title">¿Olvidaste tu contraseña?</h1>
        <p class="auth-text">Escribe el correo de tu cuenta y te enviaremos un enlace para crear una nueva.</p>

        <?php if ($error): ?>
            <div class="aviso aviso--error" role="alert" style="margin-top: var(--esp-5);">
                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" style="margin-top: var(--esp-6);" novalidate>
            <?= campoCsrf() ?>
            <div class="campo">
                <label class="campo__etiqueta" for="correo">Correo electrónico</label>
                <div class="entrada-con-icono">
                    <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                    <input class="entrada" type="email" id="correo" name="correo" value="<?= e($correo) ?>"
                           autocomplete="email" inputmode="email" placeholder="tucorreo@ejemplo.com"
                           <?= $error ? 'aria-invalid="true"' : "" ?> required autofocus>
                </div>
            </div>

            <button type="submit" class="boton boton--primario boton--bloque" style="margin-top: var(--esp-6);">
                Enviar enlace
            </button>
        </form>

        <p class="nota-legal"><a href="login.html">Volver a iniciar sesión</a></p>

    <?php endif; ?>
</section>

</main>
</body>
</html>
