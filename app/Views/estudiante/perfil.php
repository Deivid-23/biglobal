<?php
/**
 * Mi perfil.
 * Variables: $cuenta (datos guardados), $valores (lo que se muestra en el formulario),
 *            $errores (["datos" => ?, "password" => ?]), $reglas, $estudiante,
 *            $suscripcion (Premium vigente, o null si es Gratuito)
 */
$tienePassword = (int) $cuenta["tiene_password"] === 1;
$conGoogle = (int) $cuenta["con_google"] === 1;

$textoReglas = "Mínimo {$reglas['minimo']} caracteres"
    . ($reglas["mayuscula"] ? ", una mayúscula" : "")
    . ($reglas["numero"] ? ", un número" : "") . ".";
?>

<header class="encabezado-pagina perfil-cabecera">
    <span class="est-avatar est-avatar--grande" aria-hidden="true"><?= e($estudiante["iniciales"]) ?></span>
    <div>
        <h1><?= e($estudiante["nombre"]) ?></h1>
        <p>
            <?= e($cuenta["correo"]) ?>
            <?php if ($conGoogle): ?>
                <span class="chip chip--neutro"><i class="fa-brands fa-google" aria-hidden="true"></i> Vinculada con Google</span>
            <?php endif; ?>
        </p>
        <?php if (!empty($cuenta["fecha_registro"])): ?>
            <p class="perfil-desde">Aprendiendo en BiGlobal desde el <?= e(fechaCorta($cuenta["fecha_registro"])) ?></p>
        <?php endif; ?>
    </div>
</header>

<div class="perfil-rejilla">

    <!-- ================= PLAN (Gratuito / Premium) ================= -->
    <section class="perfil-tarjeta perfil-plan" aria-labelledby="titulo-plan">
        <?php if ($suscripcion): ?>
            <div class="perfil-plan__cabecera">
                <h2 id="titulo-plan">Tu plan</h2>
                <span class="chip chip--logro"><i class="fa-solid fa-crown" aria-hidden="true"></i> <?= e(Plan::nombre($suscripcion["plan"])) ?></span>
            </div>
            <p class="perfil-tarjeta__ayuda">
                Tienes la ruta de aprendizaje completa y acceso a todas las clases en vivo.
                <?php if ((int) $suscripcion["renovacion_automatica"] === 1 && $suscripcion["estado"] === "activa"): ?>
                    Se renueva el <strong><?= e(fechaCorta($suscripcion["fin"])) ?></strong>.
                <?php else: ?>
                    Premium hasta el <strong><?= e(fechaCorta($suscripcion["fin"])) ?></strong>. Después pasas al plan Gratuito sin perder tu progreso.
                <?php endif; ?>
            </p>
            <a class="boton boton--secundario" href="index.php?page=plan">Ver mi plan y pagos</a>
        <?php else: ?>
            <div class="perfil-plan__cabecera">
                <h2 id="titulo-plan">Tu plan</h2>
                <span class="chip chip--neutro">Gratuito</span>
            </div>
            <p class="perfil-tarjeta__ayuda">
                Tienes la ruta de aprendizaje completa de cada idioma y las clases en vivo abiertas para todos.
                Con Premium te unes a todas las clases en vivo con docentes.
            </p>
            <ul class="perfil-plan__precios">
                <li>
                    <strong><?= e(Plan::pesos(Plan::PLANES[Plan::MENSUAL]["centavos"])) ?></strong> al mes
                </li>
                <li>
                    <strong><?= e(Plan::pesos(Plan::PLANES[Plan::ANUAL]["centavos"])) ?></strong> al año
                    <span class="chip chip--exito">2 meses gratis</span>
                </li>
            </ul>
            <a class="boton boton--primario" href="index.php?page=plan"><i class="fa-solid fa-crown" aria-hidden="true"></i> Ver Premium</a>
        <?php endif; ?>
    </section>

    <!-- ================= DATOS PERSONALES ================= -->
    <section class="perfil-tarjeta" aria-labelledby="titulo-datos">
        <h2 id="titulo-datos">Tus datos</h2>
        <p class="perfil-tarjeta__ayuda">Así te verán tus docentes y así aparecerá tu nombre en los certificados.</p>

        <?php if ($errores["datos"]): ?>
            <div class="aviso aviso--error" role="alert">
                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                <span><?= e($errores["datos"]) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php?page=perfil" novalidate>
            <?= campoCsrf() ?>
            <input type="hidden" name="accion" value="datos">

            <div class="perfil-fila">
                <div class="campo">
                    <label class="campo__etiqueta" for="nombre">Nombre</label>
                    <input class="entrada" id="nombre" name="nombre" maxlength="100" autocomplete="given-name"
                           value="<?= e($valores["nombre"]) ?>" required>
                </div>
                <div class="campo">
                    <label class="campo__etiqueta" for="apellido">Apellido <span class="opcional">(opcional)</span></label>
                    <input class="entrada" id="apellido" name="apellido" maxlength="100" autocomplete="family-name"
                           value="<?= e($valores["apellido"]) ?>">
                </div>
            </div>

            <div class="campo">
                <label class="campo__etiqueta" for="correo">Correo electrónico</label>
                <input class="entrada" type="email" id="correo" name="correo" maxlength="150"
                       autocomplete="email" inputmode="email" value="<?= e($valores["correo"]) ?>"
                       data-original="<?= e(mb_strtolower($cuenta["correo"])) ?>" required>
            </div>

            <?php if ($tienePassword): ?>
                <!-- Solo aparece si cambias el correo (ver el script de abajo) -->
                <div class="campo" id="confirmar-correo" <?= mb_strtolower($valores["correo"]) === mb_strtolower($cuenta["correo"]) ? "hidden" : "" ?>>
                    <label class="campo__etiqueta" for="password-correo">Tu contraseña actual</label>
                    <input class="entrada" type="password" id="password-correo" name="password_actual" autocomplete="current-password">
                    <p class="campo__ayuda">Por seguridad, para cambiar el correo confirma tu contraseña.</p>
                </div>
            <?php endif; ?>

            <button type="submit" class="boton boton--primario">Guardar datos</button>
        </form>
    </section>

    <!-- ================= CONTRASEÑA ================= -->
    <section class="perfil-tarjeta" aria-labelledby="titulo-password">
        <h2 id="titulo-password">Contraseña</h2>

        <?php if ($errores["password"]): ?>
            <div class="aviso aviso--error" role="alert">
                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                <span><?= e($errores["password"]) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!$tienePassword): ?>

            <p class="perfil-tarjeta__ayuda">
                Entras con Google, así que tu cuenta no tiene contraseña. Si quieres entrar también con tu correo,
                crea una: te enviaremos un enlace para hacerlo de forma segura.
            </p>
            <a class="boton boton--secundario" href="../auth/recuperar.php">
                <i class="fa-solid fa-key" aria-hidden="true"></i> Crear contraseña
            </a>

        <?php else: ?>

            <p class="perfil-tarjeta__ayuda">Usa una que no uses en otros sitios.</p>

            <form method="POST" action="index.php?page=perfil" novalidate>
                <?= campoCsrf() ?>
                <input type="hidden" name="accion" value="password">

                <div class="campo">
                    <label class="campo__etiqueta" for="password-actual">Contraseña actual</label>
                    <input class="entrada" type="password" id="password-actual" name="password_actual"
                           autocomplete="current-password" required>
                </div>

                <div class="campo">
                    <label class="campo__etiqueta" for="password-nueva">Nueva contraseña</label>
                    <input class="entrada" type="password" id="password-nueva" name="password_nueva"
                           autocomplete="new-password" aria-describedby="reglas-perfil" required>
                    <p class="campo__ayuda" id="reglas-perfil"><?= e($textoReglas) ?></p>
                </div>

                <div class="campo">
                    <label class="campo__etiqueta" for="password-confirmar">Confirma la nueva contraseña</label>
                    <input class="entrada" type="password" id="password-confirmar" name="password_confirmar"
                           autocomplete="new-password" required>
                </div>

                <button type="submit" class="boton boton--primario">Cambiar contraseña</button>
            </form>

        <?php endif; ?>
    </section>
</div>

<div style="margin-top: var(--esp-5);">
    <?php $claseTarjeta = "perfil-tarjeta"; require __DIR__ . "/../panel/_personalizacion.php"; ?>

    <!-- ================= CORREOS ================= -->
    <?php $claseTarjeta = "perfil-tarjeta"; require __DIR__ . "/../panel/_correos.php"; ?>
</div>

<!-- ================= SESIÓN ================= -->
<section class="perfil-salir" aria-label="Sesión">
    <div>
        <p class="perfil-salir__titulo">Cerrar sesión</p>
        <p class="perfil-tarjeta__ayuda">Hazlo si usas un computador compartido. Tu progreso queda guardado.</p>
    </div>
    <form method="POST" action="../auth/logout.php">
        <?= campoCsrf() ?>
        <button class="boton boton--peligro" type="submit">
            <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i> Cerrar sesión
        </button>
    </form>
</section>

