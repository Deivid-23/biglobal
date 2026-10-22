<?php
/** Perfil del admin / super admin / docente. Variables: $perfil, $reglas, $usuarioPanel */
$tienePassword = (int) ($perfil["tiene_password"] ?? 0) === 1;
$accionPerfil = "../auth/mi_cuenta.php";
?>

<header class="pnl-encabezado">
    <div>
        <h1>Mi perfil</h1>
        <p><?= e($usuarioPanel["etiqueta"]) ?> · <?= e($perfil["correo"] ?? "") ?></p>
    </div>
</header>

<div class="pnl-rejilla pnl-rejilla--2">
    <section class="pnl-tarjeta" aria-labelledby="titulo-datos">
        <div class="pnl-tarjeta__cabecera"><div><h2 id="titulo-datos">Tus datos</h2><p>Nombre y correo de tu cuenta</p></div></div>
        <form class="pnl-form" method="POST" action="<?= e($accionPerfil) ?>">
            <?= campoCsrf() ?>
            <input type="hidden" name="accion" value="perfil">
            <div class="pnl-form__fila">
                <div class="campo">
                    <label class="campo__etiqueta" for="p-nombre">Nombre</label>
                    <input class="entrada" id="p-nombre" name="nombre" maxlength="100" required value="<?= e($perfil["nombre"] ?? "") ?>">
                </div>
                <div class="campo">
                    <label class="campo__etiqueta" for="p-apellido">Apellido</label>
                    <input class="entrada" id="p-apellido" name="apellido" maxlength="100"
                           value="<?= e(($perfil["apellido"] ?? "") === "No especificado" ? "" : ($perfil["apellido"] ?? "")) ?>">
                </div>
            </div>
            <div class="campo">
                <label class="campo__etiqueta" for="p-correo">Correo electrónico</label>
                <input class="entrada" type="email" id="p-correo" name="correo" maxlength="150" required value="<?= e($perfil["correo"] ?? "") ?>">
            </div>
            <div class="pnl-form__acciones"><button class="boton boton--primario" type="submit">Guardar datos</button></div>
        </form>
    </section>

    <section class="pnl-tarjeta" aria-labelledby="titulo-clave">
        <div class="pnl-tarjeta__cabecera"><div><h2 id="titulo-clave">Contraseña</h2><p>Usa una que no uses en otros sitios</p></div></div>
        <?php if (!$tienePassword): ?>
            <p class="texto-suave" style="margin-bottom: var(--esp-4);">Tu cuenta entra con Google y no tiene contraseña.</p>
            <a class="boton boton--secundario" href="../auth/recuperar.php"><i class="fa-solid fa-key" aria-hidden="true"></i> Crear contraseña</a>
        <?php else: ?>
            <form class="pnl-form" method="POST" action="<?= e($accionPerfil) ?>">
                <?= campoCsrf() ?>
                <input type="hidden" name="accion" value="password">
                <div class="campo">
                    <label class="campo__etiqueta" for="p-actual">Contraseña actual</label>
                    <input class="entrada" type="password" id="p-actual" name="password_actual" autocomplete="current-password" required>
                </div>
                <div class="campo">
                    <label class="campo__etiqueta" for="p-nueva">Nueva contraseña</label>
                    <input class="entrada" type="password" id="p-nueva" name="password_nueva" autocomplete="new-password" required>
                    <p class="campo__ayuda">Mínimo <?= (int) $reglas["minimo"] ?> caracteres<?= $reglas["mayuscula"] ? ", una mayúscula" : "" ?><?= $reglas["numero"] ? ", un número" : "" ?>.</p>
                </div>
                <div class="campo">
                    <label class="campo__etiqueta" for="p-confirmar">Confírmala</label>
                    <input class="entrada" type="password" id="p-confirmar" name="password_confirmar" autocomplete="new-password" required>
                </div>
                <div class="pnl-form__acciones"><button class="boton boton--primario" type="submit">Cambiar contraseña</button></div>
            </form>
        <?php endif; ?>
    </section>
</div>

<div style="margin-top: var(--esp-5);">
    <?php $claseTarjeta = "pnl-tarjeta"; require __DIR__ . "/../panel/_personalizacion.php"; ?>
</div>

<div style="margin-top: var(--esp-5);">
    <?php $claseTarjeta = "pnl-tarjeta"; require __DIR__ . "/../panel/_correos.php"; ?>
</div>

<div style="margin-top: var(--esp-5);">
    <?php $claseTarjeta = "pnl-tarjeta"; require __DIR__ . "/../panel/_eliminar_cuenta.php"; ?>
</div>
