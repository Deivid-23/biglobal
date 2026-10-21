<?php
/** Configuración global. Variables: $tab, $sistema, $correoEnv, $proveedor, $remitente, $miCorreo */
$pestanas = [
    "general"        => ["General",        "fa-globe"],
    "apariencia"     => ["Apariencia",     "fa-palette"],
    "notificaciones" => ["Notificaciones", "fa-bell"],
    "seguridad"      => ["Seguridad",      "fa-shield-halved"],
    "correo"         => ["Correo",         "fa-envelope"],
    "sistema"        => ["Sistema",        "fa-server"],
];
$g = fn(string $clave, $defecto = "") => Config::get($clave, $defecto);
?>

<header class="pnl-encabezado">
    <div>
        <h1>Configuración</h1>
        <p>Ajustes que afectan a toda la plataforma. Solo el super administrador puede cambiarlos.</p>
    </div>
</header>

<nav class="pnl-pestanas" aria-label="Secciones de la configuración">
    <?php foreach ($pestanas as $clave => [$texto, $icono]): ?>
        <a class="pnl-pestana <?= $tab === $clave ? "es-activa" : "" ?>" href="index.php?page=configuracion&amp;tab=<?= $clave ?>"
           <?= $tab === $clave ? 'aria-current="page"' : "" ?>>
            <i class="fa-solid <?= $icono ?>" aria-hidden="true"></i> <?= $texto ?>
        </a>
    <?php endforeach; ?>
</nav>

<section class="pnl-tarjeta" aria-labelledby="titulo-config">

<?php if ($tab === "general"): ?>
    <div class="pnl-tarjeta__cabecera"><div><h2 id="titulo-config">Información del sitio</h2><p>Nombre, contacto e idioma</p></div></div>
    <form class="pnl-form" method="POST" action="configuracion.php">
        <?= campoCsrf() ?><input type="hidden" name="accion" value="general">
        <div class="campo">
            <label class="campo__etiqueta" for="c-nombre">Nombre del sitio</label>
            <input class="entrada" id="c-nombre" name="sitio_nombre" required value="<?= e($g("sitio_nombre", "BiGlobal")) ?>">
        </div>
        <div class="campo">
            <label class="campo__etiqueta" for="c-desc">Descripción</label>
            <textarea class="entrada" id="c-desc" name="sitio_descripcion" rows="2"><?= e($g("sitio_descripcion")) ?></textarea>
        </div>
        <div class="campo">
            <label class="campo__etiqueta" for="c-correo">Correo de contacto</label>
            <input class="entrada" type="email" id="c-correo" name="sitio_correo" value="<?= e($g("sitio_correo")) ?>">
        </div>
        <div class="pnl-form__fila">
            <div class="campo">
                <label class="campo__etiqueta" for="c-idioma">Idioma por defecto</label>
                <select class="entrada" id="c-idioma" name="sitio_idioma">
                    <?php foreach (["es" => "Español", "en" => "English", "pt" => "Português"] as $cod => $txt): ?>
                        <option value="<?= $cod ?>" <?= $g("sitio_idioma") === $cod ? "selected" : "" ?>><?= $txt ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label class="campo__etiqueta" for="c-zona">Zona horaria</label>
                <input class="entrada" id="c-zona" name="sitio_zona" value="<?= e($g("sitio_zona", "America/Bogota")) ?>">
            </div>
        </div>
        <div class="pnl-form__acciones"><button class="boton boton--primario" type="submit">Guardar</button></div>
    </form>

<?php elseif ($tab === "apariencia"): ?>
    <div class="pnl-tarjeta__cabecera"><div><h2 id="titulo-config">Apariencia</h2><p>Tema por defecto y color de los botones de los paneles</p></div></div>
    <form class="pnl-form" method="POST" action="configuracion.php">
        <?= campoCsrf() ?><input type="hidden" name="accion" value="apariencia">
        <fieldset class="campo" style="border:0;padding:0;margin:0;">
            <legend class="campo__etiqueta">Tema por defecto del panel</legend>
            <div class="pnl-form__fila">
                <?php foreach (["light" => "Claro", "dark" => "Oscuro"] as $valor => $txt): ?>
                    <label class="pnl-interruptor">
                        <span><strong><?= $txt ?></strong><small>Cada persona puede cambiarlo con el botón de tema</small></span>
                        <input type="radio" name="tema_default" value="<?= $valor ?>" <?= $g("tema_default", "light") === $valor ? "checked" : "" ?>>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <fieldset class="campo" style="border:0;padding:0;margin:0;">
            <legend class="campo__etiqueta">Color por defecto del sitio</legend>
            <?php
                $acentoGuardado = strtolower($g("color_acento", "blue"));
                $nombresAcento = ["blue", "green", "orange", "purple", "pink"];
                $esLibre = !in_array($acentoGuardado, $nombresAcento, true) && Panel::esColor($acentoGuardado);
            ?>
            <div class="pnl-colores">
                <?php foreach (["blue" => ["#2563EB", "Azul (marca)"], "green" => ["#15803D", "Verde"], "orange" => ["#C2410C", "Naranja"], "purple" => ["#7C3AED", "Morado"], "pink" => ["#DB2777", "Rosa"]] as $valor => [$hex, $txt]): ?>
                    <div class="pnl-color">
                        <input type="radio" id="acento-<?= $valor ?>" name="color_acento" value="<?= $valor ?>" <?= $acentoGuardado === $valor ? "checked" : "" ?>>
                        <label for="acento-<?= $valor ?>"><span style="background: <?= $hex ?>"></span><?= $txt ?></label>
                    </div>
                <?php endforeach; ?>
                <div class="pnl-color">
                    <input type="radio" id="acento-libre" name="color_acento" value="libre" <?= $esLibre ? "checked" : "" ?>>
                    <label for="acento-libre">
                        <input type="color" class="pnl-color__selector" name="color_acento_libre" aria-label="Elegir cualquier color"
                               value="<?= e($esLibre ? $acentoGuardado : "#0d9488") ?>">
                        Otro color
                    </label>
                </div>
            </div>
            <p class="campo__ayuda">
                Lo ven todas las personas que no han elegido su propio color en «Mi perfil».
                Cada usuario puede cambiar el suyo. Si el color no se lee bien, se ajusta solo para mantener el contraste.
            </p>
        </fieldset>
        <div class="pnl-form__acciones"><button class="boton boton--primario" type="submit">Guardar</button></div>
    </form>

<?php elseif ($tab === "notificaciones"): ?>
    <div class="pnl-tarjeta__cabecera"><div><h2 id="titulo-config">Notificaciones</h2><p>Avisos que reciben los administradores y super administradores</p></div></div>
    <form class="pnl-form" method="POST" action="configuracion.php">
        <?= campoCsrf() ?><input type="hidden" name="accion" value="notificaciones">
        <?php foreach ([
            "notif_nuevo_usuario"     => ["Nuevo usuario registrado",     "Cuando alguien crea una cuenta, con correo o con Google", true],
            "notif_docente_pendiente" => ["Docente pendiente de aprobar", "Cuando un docente se registra y espera tu aprobación", true],
            "notif_nuevo_curso"       => ["Nuevo curso creado",           "Cuando se crea un curso desde el panel de admin o de docente", true],
            "notif_resumen_semanal"   => ["Resumen semanal",              "Cada lunes: usuarios nuevos, docentes pendientes, cursos y lecciones completadas", false],
        ] as $clave => [$titulo, $texto, $porDefecto]): ?>
            <label class="pnl-interruptor">
                <span><strong><?= $titulo ?></strong><small><?= $texto ?></small></span>
                <input type="checkbox" name="<?= $clave ?>" <?= Config::getBool($clave, $porDefecto) ? "checked" : "" ?>>
            </label>
        <?php endforeach; ?>
        <p class="campo__ayuda">
            Los avisos aparecen en la campana del panel.
            <?php if (Correo::estaConfigurado()): ?>
                También llegan por correo a cada administrador.
            <?php else: ?>
                Para que también lleguen por correo, configura el servidor en la pestaña <a href="index.php?page=configuracion&amp;tab=correo">Correo</a>.
            <?php endif; ?>
        </p>
        <div class="pnl-form__acciones"><button class="boton boton--primario" type="submit">Guardar</button></div>
    </form>

<?php elseif ($tab === "seguridad"): ?>
    <div class="pnl-tarjeta__cabecera"><div><h2 id="titulo-config">Seguridad</h2><p>Reglas de contraseñas y bloqueo por intentos fallidos</p></div></div>
    <form class="pnl-form" method="POST" action="configuracion.php">
        <?= campoCsrf() ?><input type="hidden" name="accion" value="seguridad">
        <div class="pnl-form__fila">
            <div class="campo">
                <label class="campo__etiqueta" for="s-min">Largo mínimo de contraseña</label>
                <input class="entrada" type="number" id="s-min" name="seg_min_password" min="<?= Seguridad::MIN_PASSWORD ?>" max="<?= Seguridad::MAX_PASSWORD ?>" value="<?= Seguridad::minimoPassword() ?>">
            </div>
            <div class="campo">
                <label class="campo__etiqueta" for="s-int">Intentos antes de bloquear</label>
                <input class="entrada" type="number" id="s-int" name="seg_intentos_login" min="1" max="20" value="<?= Config::getInt("seg_intentos_login", 5) ?>">
            </div>
        </div>
        <div class="campo" style="max-width: 300px;">
            <label class="campo__etiqueta" for="s-blo">Minutos de bloqueo</label>
            <input class="entrada" type="number" id="s-blo" name="seg_bloqueo_minutos" min="1" max="120" value="<?= Config::getInt("seg_bloqueo_minutos", 1) ?>">
        </div>
        <label class="pnl-interruptor"><span><strong>Exigir una mayúscula</strong><small>En registro, recuperación y cambio de contraseña</small></span>
            <input type="checkbox" name="seg_requiere_mayuscula" <?= Config::getBool("seg_requiere_mayuscula") ? "checked" : "" ?>></label>
        <label class="pnl-interruptor"><span><strong>Exigir un número</strong><small>En registro, recuperación y cambio de contraseña</small></span>
            <input type="checkbox" name="seg_requiere_numero" <?= Config::getBool("seg_requiere_numero") ? "checked" : "" ?>></label>
        <div class="pnl-form__acciones"><button class="boton boton--primario" type="submit">Guardar</button></div>
    </form>

<?php elseif ($tab === "correo"): ?>
    <div class="pnl-tarjeta__cabecera"><div><h2 id="titulo-config">Correo</h2><p>Enlaces de recuperación de contraseña y avisos al equipo</p></div></div>

    <?php
    $listo = Correo::estaConfigurado();
    $nombresProveedor = ["brevo" => "API de Brevo", "smtp" => "Servidor SMTP"];
    ?>
    <div class="aviso <?= $listo ? "aviso--exito" : "aviso--alerta" ?> pnl-aviso-arriba">
        <i class="fa-solid <?= $listo ? "fa-circle-check" : "fa-triangle-exclamation" ?>" aria-hidden="true"></i>
        <span>
            <?php if ($listo): ?>
                Los correos salen por <strong><?= e($nombresProveedor[$proveedor]) ?></strong>
                con el remitente <strong><?= e($remitente !== "" ? $remitente : "(el usuario SMTP)") ?></strong>.
            <?php elseif ($proveedor === "brevo"): ?>
                La clave de Brevo está puesta, pero falta un <strong>correo remitente</strong> válido
                (variable CORREO_REMITENTE en Render o el campo de abajo).
            <?php else: ?>
                Todavía no salen correos. En Render gratis el SMTP está bloqueado: usa la <strong>API de Brevo</strong>
                (pasos abajo).
            <?php endif; ?>
        </span>
    </div>

    <?php if ($listo): ?>
        <form class="pnl-form pnl-aviso-arriba" method="POST" action="configuracion.php">
            <?= campoCsrf() ?><input type="hidden" name="accion" value="correo_prueba">
            <p class="campo__ayuda">Manda un correo de prueba a tu cuenta (<?= e($miCorreo) ?>) para confirmar que todo funciona.</p>
            <div class="pnl-form__acciones" style="justify-content: flex-start;">
                <button class="boton boton--secundario" type="submit">
                    <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Enviar correo de prueba
                </button>
            </div>
        </form>
    <?php endif; ?>

    <details class="pnl-ayuda-brevo pnl-aviso-arriba" <?= $proveedor === "" ? "open" : "" ?>>
        <summary>Cómo conectar Brevo (gratis, 300 correos al día)</summary>
        <ol>
            <li>Crea una cuenta en brevo.com.</li>
            <li>En <strong>Senders, Domains &amp; Dedicated IPs → Senders</strong>, agrega y verifica el correo que usarás como remitente.</li>
            <li>En <strong>SMTP &amp; API → API Keys</strong>, crea una clave.</li>
            <li>En Render → biglobal → Environment, agrega <code>BREVO_API_KEY</code> (la clave) y <code>CORREO_REMITENTE</code> (el correo verificado).</li>
            <li>Cuando Render reinicie, vuelve aquí y usa «Enviar correo de prueba».</li>
        </ol>
        <p class="campo__ayuda">La clave de Brevo nunca se guarda en la base de datos: solo vive en Render.</p>
    </details>

    <h3 class="pnl-subtitulo">Remitente y SMTP (alternativa)</h3>
    <p class="campo__ayuda pnl-aviso-arriba">El remitente sirve para Brevo y para SMTP. Los datos SMTP solo funcionan en tu computador o en un plan pago de Render.</p>
    <?php if ($correoEnv): ?>
        <div class="aviso aviso--info pnl-aviso-arriba">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <span>El servidor ya está definido con variables de entorno (SMTP_*) en Render, y esas tienen prioridad sobre estos campos.</span>
        </div>
    <?php endif; ?>
    <form class="pnl-form" method="POST" action="configuracion.php">
        <?= campoCsrf() ?><input type="hidden" name="accion" value="correo">
        <div class="campo">
            <label class="campo__etiqueta" for="m-host">Servidor SMTP</label>
            <input class="entrada" id="m-host" name="smtp_host" placeholder="smtp.gmail.com" value="<?= e($g("smtp_host")) ?>">
        </div>
        <div class="pnl-form__fila">
            <div class="campo">
                <label class="campo__etiqueta" for="m-puerto">Puerto</label>
                <input class="entrada" type="number" id="m-puerto" name="smtp_puerto" value="<?= Config::getInt("smtp_puerto", 587) ?>">
            </div>
            <div class="campo">
                <label class="campo__etiqueta" for="m-enc">Cifrado</label>
                <select class="entrada" id="m-enc" name="smtp_encriptacion">
                    <?php foreach (["tls" => "TLS (587)", "ssl" => "SSL (465)", "none" => "Sin cifrado"] as $cod => $txt): ?>
                        <option value="<?= $cod ?>" <?= $g("smtp_encriptacion") === $cod ? "selected" : "" ?>><?= $txt ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="campo">
            <label class="campo__etiqueta" for="m-usuario">Usuario</label>
            <input class="entrada" id="m-usuario" name="smtp_usuario" autocomplete="off" value="<?= e($g("smtp_usuario")) ?>">
        </div>
        <div class="campo">
            <label class="campo__etiqueta" for="m-clave">Contraseña</label>
            <input class="entrada" type="password" id="m-clave" name="smtp_password" autocomplete="new-password"
                   placeholder="<?= $g("smtp_password") !== "" ? "•••••••• (guardada)" : "" ?>">
            <p class="campo__ayuda">Déjala vacía para no cambiarla. Más seguro: ponerla como SMTP_PASS en Render.</p>
        </div>
        <div class="campo">
            <label class="campo__etiqueta" for="m-rem">Correo remitente</label>
            <input class="entrada" type="email" id="m-rem" name="smtp_remitente" value="<?= e($g("smtp_remitente")) ?>">
        </div>
        <div class="pnl-form__acciones"><button class="boton boton--primario" type="submit">Guardar</button></div>
    </form>

<?php else: /* sistema */ ?>
    <div class="pnl-tarjeta__cabecera"><div><h2 id="titulo-config">Sistema</h2><p>Detalles técnicos del servidor</p></div></div>
    <dl class="pnl-definiciones">
        <?php foreach ($sistema as $k => $v): ?>
            <div><dt><?= e($k) ?></dt><dd><?= e($v ?: "—") ?></dd></div>
        <?php endforeach; ?>
    </dl>
    <form method="POST" action="configuracion.php" style="margin-top: var(--esp-6);">
        <?= campoCsrf() ?><input type="hidden" name="accion" value="sistema"><input type="hidden" name="sub" value="limpiar_cache">
        <button class="boton boton--secundario" type="submit"><i class="fa-solid fa-broom" aria-hidden="true"></i> Limpiar caché de configuración</button>
    </form>
<?php endif; ?>

</section>
