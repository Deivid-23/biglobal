<?php
/**
 * Pantalla para crear la nueva contraseña (desde el enlace del correo).
 * Variables: $estado (formulario|invalido), $error, $token, $reglas, $solicitud
 */
$tituloPagina = "Nueva contraseña";
require __DIR__ . "/_cabeza.php";

$textoReglas = "Mínimo {$reglas['minimo']} caracteres"
    . ($reglas["mayuscula"] ? ", una mayúscula" : "")
    . ($reglas["numero"] ? ", un número" : "") . ".";
?>

<section class="auth-card">
    <?php if ($estado === "invalido"): ?>

        <h1 class="auth-title">Este enlace ya no sirve</h1>
        <p class="auth-text">Puede que haya vencido (dura 60 minutos) o que ya lo hayas usado. Pide uno nuevo y ábrelo desde el último correo.</p>
        <div class="auth-actions" style="justify-content: flex-start;">
            <a class="boton boton--primario" href="recuperar.php">Pedir un enlace nuevo</a>
            <a class="boton boton--secundario" href="login.html">Iniciar sesión</a>
        </div>

    <?php else: ?>

        <h1 class="auth-title">Crea tu nueva contraseña</h1>
        <p class="auth-text">Para la cuenta <strong><?= e($solicitud["correo"]) ?></strong>.</p>

        <?php if ($error): ?>
            <div class="aviso aviso--error" role="alert" style="margin-top: var(--esp-5);">
                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" style="margin-top: var(--esp-6);" novalidate>
            <?= campoCsrf() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">

            <div class="campo">
                <label class="campo__etiqueta" for="password">Nueva contraseña</label>
                <div class="entrada-con-icono">
                    <i class="fa-solid fa-lock" aria-hidden="true"></i>
                    <input class="entrada" type="password" id="password" name="password"
                           autocomplete="new-password" aria-describedby="reglas" required autofocus>
                </div>
                <p class="campo__ayuda" id="reglas"><?= e($textoReglas) ?></p>
            </div>

            <div class="campo">
                <label class="campo__etiqueta" for="confirmar">Confírmala</label>
                <div class="entrada-con-icono">
                    <i class="fa-solid fa-lock" aria-hidden="true"></i>
                    <input class="entrada" type="password" id="confirmar" name="confirmar"
                           autocomplete="new-password" required>
                </div>
            </div>

            <button type="submit" class="boton boton--primario boton--bloque" style="margin-top: var(--esp-6);">
                Guardar contraseña
            </button>
        </form>

    <?php endif; ?>
</section>

</main>
</body>
</html>
