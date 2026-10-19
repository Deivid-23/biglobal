<?php
/** Resumen del sistema. Variables: $porRol, $administradores, $stats, $seguridad, $correoListo, $appUrl, $usuarioPanel */
$estados = [1 => ["Activo", "chip chip--exito"], 2 => ["Pendiente", "chip chip--alerta"], 0 => ["Inactivo", "chip chip--neutro"]];
$superActivos = count(array_filter($administradores, fn($a) => $a["rol"] === "superadmin" && (int) $a["activo"] === 1));
?>

<section class="pnl-bienvenida" aria-labelledby="bienvenida-titulo">
    <div>
        <h1 id="bienvenida-titulo">Hola, <?= e($usuarioPanel["primer"]) ?></h1>
        <p>Desde aquí controlas quién administra BiGlobal, la seguridad y la configuración global del sitio.</p>
    </div>
    <div class="pnl-bienvenida__acciones">
        <a class="boton boton--primario" href="index.php?page=cuentas"><i class="fa-solid fa-user-shield" aria-hidden="true"></i> Cuentas y roles</a>
        <a class="boton boton--secundario" href="../admin/index.php">Ir al panel admin</a>
    </div>
</section>

<?php if ($superActivos <= 1): ?>
    <div class="aviso aviso--info pnl-aviso-arriba">
        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
        <span>Eres el único super administrador activo. Te recomendamos nombrar otro desde <a href="index.php?page=cuentas&amp;rol=admin">Cuentas y roles</a>, por si pierdes el acceso.</span>
    </div>
<?php endif; ?>

<section class="pnl-datos" aria-label="Estado del sistema">
    <div class="pnl-dato">
        <span class="pnl-dato__icono"><i class="fa-solid fa-users" aria-hidden="true"></i></span>
        <span><span class="pnl-dato__numero"><?= $stats["usuarios"] ?></span><br><span class="pnl-dato__texto">Cuentas en total</span></span>
    </div>
    <div class="pnl-dato">
        <span class="pnl-dato__icono pnl-dato__icono--naranja"><i class="fa-solid fa-crown" aria-hidden="true"></i></span>
        <span><span class="pnl-dato__numero"><?= count($administradores) ?></span><br><span class="pnl-dato__texto">Administradores</span></span>
    </div>
    <a class="pnl-dato" href="index.php?page=configuracion&amp;tab=seguridad">
        <span class="pnl-dato__icono pnl-dato__icono--verde"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i></span>
        <span><span class="pnl-dato__numero"><?= (int) $seguridad["minimo"] ?>+</span><br><span class="pnl-dato__texto">Caracteres mínimos de contraseña</span></span>
    </a>
    <a class="pnl-dato" href="index.php?page=configuracion&amp;tab=correo">
        <span class="pnl-dato__icono <?= $correoListo ? "pnl-dato__icono--verde" : "pnl-dato__icono--rojo" ?>"><i class="fa-solid fa-envelope" aria-hidden="true"></i></span>
        <span><span class="pnl-dato__numero"><?= $correoListo ? "Listo" : "Falta" ?></span><br><span class="pnl-dato__texto">Correo SMTP</span></span>
    </a>
</section>

<div class="pnl-rejilla pnl-rejilla--ancha">
    <section class="pnl-tarjeta" aria-labelledby="titulo-admins">
        <div class="pnl-tarjeta__cabecera">
            <div><h2 id="titulo-admins">Quién administra</h2><p>Administradores y super administradores</p></div>
            <a href="index.php?page=cuentas">Gestionar</a>
        </div>
        <?php if (empty($administradores)): ?>
            <p class="texto-suave">No hay administradores.</p>
        <?php else: ?>
            <ul class="pnl-lista pnl-lista--compacta">
                <?php foreach ($administradores as $a): [$txt, $cls] = $estados[(int) $a["activo"]] ?? $estados[0]; ?>
                    <li class="pnl-item">
                        <span class="pnl-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($a["nombre"], 0, 1))) ?></span>
                        <div class="pnl-item__cuerpo">
                            <p class="pnl-item__titulo"><?= e(nombre_panel($a)) ?></p>
                            <p class="pnl-item__texto"><?= e($a["correo"]) ?></p>
                        </div>
                        <span class="<?= $a["rol"] === "superadmin" ? "chip chip--super" : "chip" ?>"><?= $a["rol"] === "superadmin" ? "Super admin" : "Admin" ?></span>
                        <span class="<?= $cls ?>"><?= $txt ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <?= Panel::grafico("grafico-roles", "Cuentas por rol", "Toda la plataforma",
        array_map("ucfirst", array_column($porRol, "etiqueta")), array_column($porRol, "total"), "doughnut") ?>
</div>

<section class="pnl-tarjeta" aria-labelledby="titulo-chequeo">
    <div class="pnl-tarjeta__cabecera"><div><h2 id="titulo-chequeo">Revisión rápida</h2><p>Lo que conviene tener listo en producción</p></div></div>
    <ul class="pnl-lista pnl-lista--compacta">
        <li class="pnl-item">
            <i class="fa-solid <?= $correoListo ? "fa-circle-check" : "fa-triangle-exclamation" ?>" style="color: var(<?= $correoListo ? "--verde-600" : "--ambar-700" ?>);" aria-hidden="true"></i>
            <div class="pnl-item__cuerpo">
                <p class="pnl-item__titulo">Correo para recuperar contraseñas</p>
                <p class="pnl-item__texto"><?= $correoListo ? "Configurado." : "Sin configurar: los enlaces de «Olvidé mi contraseña» no se envían." ?></p>
            </div>
            <a class="boton boton--secundario boton--sm" href="index.php?page=configuracion&amp;tab=correo">Revisar</a>
        </li>
        <li class="pnl-item">
            <i class="fa-solid <?= $appUrl !== "" ? "fa-circle-check" : "fa-triangle-exclamation" ?>" style="color: var(<?= $appUrl !== "" ? "--verde-600" : "--ambar-700" ?>);" aria-hidden="true"></i>
            <div class="pnl-item__cuerpo">
                <p class="pnl-item__titulo">Dirección pública del sitio (APP_URL)</p>
                <p class="pnl-item__texto"><?= $appUrl !== "" ? e($appUrl) : "Falta en las variables de entorno." ?></p>
            </div>
        </li>
        <li class="pnl-item">
            <i class="fa-solid fa-lock" style="color: var(--marca-texto);" aria-hidden="true"></i>
            <div class="pnl-item__cuerpo">
                <p class="pnl-item__titulo">Bloqueo por intentos fallidos</p>
                <p class="pnl-item__texto"><?= (int) $seguridad["intentos"] ?> intentos · <?= (int) $seguridad["bloqueo"] ?> min de espera<?= $seguridad["mayuscula"] ? " · exige mayúscula" : "" ?><?= $seguridad["numero"] ? " · exige número" : "" ?></p>
            </div>
            <a class="boton boton--secundario boton--sm" href="index.php?page=configuracion&amp;tab=seguridad">Ajustar</a>
        </li>
    </ul>
</section>
