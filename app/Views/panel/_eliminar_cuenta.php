<?php
/**
 * Tarjeta "Eliminar cuenta" (todos los roles). Guarda en src/auth/eliminar_cuenta.php.
 * Variables: $claseTarjeta (opcional), $tienePassword (bool)
 */
require_once __DIR__ . "/../../Models/CuentaEliminacionModel.php";
$claseTarjeta = $claseTarjeta ?? "pnl-tarjeta";
$ayuda = $claseTarjeta === "perfil-tarjeta" ? "perfil-tarjeta__ayuda" : "campo__ayuda";
?>
<section class="<?= e($claseTarjeta) ?> tarjeta-eliminar" aria-labelledby="titulo-eliminar">
    <h2 id="titulo-eliminar">Eliminar cuenta</h2>
    <p class="<?= $ayuda ?>">
        Tu cuenta se desactiva hoy y, a los <?= CuentaEliminacionModel::DIAS_GRACIA ?> días, borramos definitivamente tus datos personales:
        nombre, correo, progreso, XP, medallas y certificados. Si inicias sesión antes, puedes recuperarla.
        También cancelamos la renovación de Premium y te retiramos de tus clases en vivo.
    </p>
    <details class="eliminar-cuenta">
        <summary>Quiero eliminar mi cuenta</summary>
        <form method="POST" action="../auth/eliminar_cuenta.php"
              data-confirmar="¿Seguro? Tu cuenta se desactiva ahora y se borra en <?= CuentaEliminacionModel::DIAS_GRACIA ?> días.">
            <?= campoCsrf() ?>
            <div class="campo">
                <?php if ($tienePassword): ?>
                    <label class="campo__etiqueta" for="eliminar-password">Confirma con tu contraseña</label>
                    <input class="entrada" type="password" id="eliminar-password" name="password" autocomplete="current-password" required>
                <?php else: ?>
                    <label class="campo__etiqueta" for="eliminar-confirmacion">Escribe ELIMINAR para confirmar</label>
                    <input class="entrada" id="eliminar-confirmacion" name="confirmacion" autocomplete="off" required pattern="ELIMINAR" placeholder="ELIMINAR">
                <?php endif; ?>
            </div>
            <button class="boton boton--peligro" type="submit">Eliminar mi cuenta</button>
        </form>
    </details>
</section>
