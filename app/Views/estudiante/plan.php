<?php
/**
 * Mi plan: Premium, pago con Wompi, renovación e historial.
 * Variables: $disponible, $configurado, $suscripcion, $conRenovacion, $reactivable,
 *            $pagoConsultado, $historial, $elegido, $puedeComprar, $terminos,
 *            $wompiApi, $wompiPublica, $puedeProbar (prueba gratis disponible para esta cuenta)
 *
 * Seguridad: los campos de la tarjeta NO tienen "name": nunca se envían a
 * BiGlobal. js/pago.js los manda directo a Wompi y aquí solo llega el token.
 */
$estadosPago = [
    "APPROVED" => ["Aprobado", "chip--exito"],
    "PENDING"  => ["En proceso", "chip--neutro"],
    "DECLINED" => ["Rechazado", "chip--error"],
    "VOIDED"   => ["Anulado", "chip--neutro"],
    "ERROR"    => ["Con error", "chip--error"],
];
$metodos = ["CARD" => "Tarjeta", "PSE" => "PSE", "NEQUI" => "Nequi", "BANCOLOMBIA_TRANSFER" => "Botón Bancolombia", "BANCOLOMBIA" => "Bancolombia"];
$precio = Plan::PLANES[$elegido]["centavos"];
?>

<a class="enlace-atras" href="index.php?page=perfil">
    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Mi perfil
</a>

<header class="encabezado-pagina">
    <h1>Mi plan</h1>
    <p>Gratuito: toda la ruta de aprendizaje, de A1 a C1. Premium: además, todas las clases en vivo y los grupos con docentes.</p>
</header>

<?php if ($pagoConsultado):
    $ep = $pagoConsultado["estado"]; ?>
    <div class="aviso <?= $ep === "APPROVED" ? "aviso--exito" : ($ep === "PENDING" ? "aviso--info" : "aviso--error") ?> plan-aviso" role="status">
        <i class="fa-solid <?= $ep === "APPROVED" ? "fa-circle-check" : ($ep === "PENDING" ? "fa-hourglass-half" : "fa-circle-exclamation") ?>" aria-hidden="true"></i>
        <span>
            <?php if ($ep === "APPROVED"): ?>
                <strong>Pago aprobado.</strong> Ya eres Premium. ¡Nos vemos en las clases y los grupos con docentes!
            <?php elseif ($ep === "PENDING"): ?>
                <strong>Estamos confirmando tu pago con Wompi.</strong> Puede tardar unos minutos (con PSE, hasta que el banco responda).
                Te avisaremos por correo. <a href="index.php?page=plan&amp;ref=<?= e(rawurlencode($pagoConsultado["referencia"])) ?>">Actualizar</a>
            <?php else: ?>
                <strong>El pago no se aprobó.</strong>
                <?= e($pagoConsultado["mensaje"] ?: "No se te cobró nada.") ?> Puedes intentarlo otra vez.
            <?php endif; ?>
            <br><small>Referencia: <?= e($pagoConsultado["referencia"]) ?></small>
        </span>
    </div>
<?php endif; ?>

<!-- ================= PLAN ACTUAL ================= -->
<section class="perfil-tarjeta plan-actual" aria-labelledby="titulo-plan-actual">
    <div class="perfil-plan__cabecera">
        <h2 id="titulo-plan-actual">Tu plan</h2>
        <?php if ($suscripcion): ?>
            <span class="chip chip--logro"><i class="fa-solid fa-crown" aria-hidden="true"></i> <?= e(Plan::nombre($suscripcion["plan"])) ?></span>
        <?php else: ?>
            <span class="chip chip--neutro">Gratuito</span>
        <?php endif; ?>
    </div>

    <?php if ($conRenovacion): ?>
        <?php if ($conRenovacion["estado"] === "en_reintento"): ?>
            <div class="aviso aviso--alerta plan-aviso">
                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                <span>El último cobro con tu tarjeta no se aprobó. Lo intentaremos otra vez el
                    <strong><?= e(fechaCorta($conRenovacion["proximo_intento"])) ?></strong>
                    (intento <?= (int) $conRenovacion["intentos_fallidos"] ?> de <?= count(PagoModel::REINTENTOS_DIAS) ?>). Mientras tanto sigues siendo Premium.</span>
            </div>
        <?php else: ?>
            <p class="perfil-tarjeta__ayuda">
                Se renueva solo el <strong><?= e(fechaCorta($conRenovacion["fin"])) ?></strong>
                por <?= e(Plan::pesos((int) (Plan::PLANES[$conRenovacion["plan"]]["centavos"] ?? 0))) ?>
                con tu <?= e($conRenovacion["tarjeta_marca"] ?: "tarjeta") ?> •••• <?= e($conRenovacion["tarjeta_ultimos4"] ?: "····") ?>.
            </p>
        <?php endif; ?>
        <form method="POST" action="pagar.php"
              data-confirmar="¿Cancelar la renovación automática? Sigues siendo Premium hasta la fecha de corte y después pasas a Gratuito sin perder tu progreso.">
            <?= campoCsrf() ?>
            <input type="hidden" name="accion" value="cancelar_renovacion">
            <button class="boton boton--secundario" type="submit">Cancelar renovación automática</button>
        </form>
    <?php elseif ($suscripcion): ?>
        <p class="perfil-tarjeta__ayuda">
            Premium hasta el <strong><?= e(fechaCorta($suscripcion["fin"])) ?></strong>. No se renueva solo:
            después pasas a Gratuito sin perder tu progreso.
        </p>
        <?php if ($reactivable): ?>
            <form method="POST" action="pagar.php">
                <?= campoCsrf() ?>
                <input type="hidden" name="accion" value="reactivar_renovacion">
                <button class="boton boton--secundario" type="submit">Reactivar renovación automática</button>
            </form>
        <?php endif; ?>
    <?php else: ?>
        <p class="perfil-tarjeta__ayuda">Tienes la ruta de aprendizaje completa y las clases y grupos gratuitos para todos.</p>
    <?php endif; ?>
</section>

<!-- ================= PRUEBA GRATIS (una vez, sin tarjeta) ================= -->
<?php if ($puedeProbar): ?>
    <section class="perfil-tarjeta plan-compra plan-gratis" id="prueba" aria-labelledby="titulo-prueba">
        <span class="plan-gratis__icono" aria-hidden="true"><i class="fa-solid fa-gift"></i></span>
        <div class="plan-gratis__texto">
            <h2 id="titulo-prueba">Prueba Premium gratis <?= Plan::diasPrueba() ?> días</h2>
            <p class="perfil-tarjeta__ayuda">Sin tarjeta y sin cobros: únete a las clases en vivo y a los grupos con docentes.
                Al terminar vuelves al plan Gratuito sin perder nada. Es una sola vez por cuenta.</p>
        </div>
        <form method="POST" action="pagar.php">
            <?= campoCsrf() ?>
            <input type="hidden" name="accion" value="empezar_prueba">
            <button class="boton boton--primario" type="submit">Empezar mi prueba</button>
        </form>
    </section>
<?php endif; ?>

<!-- ================= COMPRAR / EXTENDER PREMIUM ================= -->
<?php if (!$disponible || !$configurado): ?>
    <section class="perfil-tarjeta plan-compra" aria-labelledby="titulo-comprar">
        <h2 id="titulo-comprar">Pasarte a Premium</h2>
        <p class="perfil-tarjeta__ayuda">Muy pronto podrás pagar aquí con tarjeta, PSE, Nequi o Botón Bancolombia.</p>
    </section>
<?php elseif ($puedeComprar): ?>
    <section class="perfil-tarjeta plan-compra" aria-labelledby="titulo-comprar">
        <h2 id="titulo-comprar"><?= $suscripcion ? "Extender tu Premium" : "Pasarte a Premium" ?></h2>

        <!-- 1. Plan -->
        <nav class="plan-opciones" aria-label="Elige el plan">
            <?php foreach (Plan::PLANES as $codigo => $p): ?>
                <a class="plan-opcion <?= $codigo === $elegido ? "es-activa" : "" ?>" href="index.php?page=plan&amp;elegir=<?= e($codigo) ?>"
                   <?= $codigo === $elegido ? 'aria-current="true"' : "" ?>>
                    <span class="plan-opcion__nombre"><?= e($p["nombre"]) ?></span>
                    <span class="plan-opcion__precio"><?= e(Plan::pesos($p["centavos"])) ?></span>
                    <span class="plan-opcion__nota"><?= $codigo === Plan::ANUAL ? "2 meses gratis · " . e(Plan::anualPorMes()) . " al mes" : "por mes" ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php if ($terminos === null): ?>
            <div class="aviso aviso--error plan-aviso">
                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                <span>No pudimos conectarnos con Wompi. Recarga la página en unos minutos.</span>
            </div>
        <?php else: ?>
            <form class="plan-pago" method="POST" action="pagar.php" data-pago-wompi
                  data-wompi-api="<?= e($wompiApi) ?>" data-wompi-publica="<?= e($wompiPublica) ?>">
                <?= campoCsrf() ?>
                <input type="hidden" name="plan" value="<?= e($elegido) ?>">
                <input type="hidden" name="token_tarjeta" value="">

                <!-- 2. Términos de Wompi (obligatorios) -->
                <fieldset class="plan-terminos">
                    <legend class="campo__etiqueta">Antes de pagar</legend>
                    <label class="plan-check">
                        <input type="checkbox" name="acepta_terminos" value="1" required>
                        <span>Acepto los <a href="<?= e($terminos["aceptacion"]["enlace"]) ?>" target="_blank" rel="noopener noreferrer">términos y condiciones de Wompi</a>.</span>
                    </label>
                    <label class="plan-check">
                        <input type="checkbox" name="acepta_datos" value="1" required>
                        <span>Autorizo el <a href="<?= e($terminos["datos"]["enlace"]) ?>" target="_blank" rel="noopener noreferrer">tratamiento de mis datos personales</a> por parte de Wompi.</span>
                    </label>
                </fieldset>

                <!-- 3a. Tarjeta: se renueva sola -->
                <div class="plan-metodo">
                    <h3><i class="fa-solid fa-credit-card" aria-hidden="true"></i> Tarjeta de crédito o débito</h3>
                    <p class="perfil-tarjeta__ayuda">Se renueva sola cada <?= $elegido === Plan::ANUAL ? "año" : "mes" ?>. Puedes cancelar la renovación cuando quieras.
                        Tus datos van directo a Wompi: BiGlobal nunca los ve ni los guarda.</p>
                    <?php if (Wompi::ambiente() === "sandbox"): ?>
                        <p class="plan-prueba"><i class="fa-solid fa-flask" aria-hidden="true"></i> Modo de pruebas: usa la tarjeta 4242 4242 4242 4242 (aprobada) o 4111 1111 1111 1111 (rechazada).</p>
                    <?php endif; ?>
                    <div class="plan-tarjeta">
                        <div class="campo plan-tarjeta__numero">
                            <label class="campo__etiqueta" for="t-numero">Número de la tarjeta</label>
                            <input class="entrada" id="t-numero" data-tarjeta="number" inputmode="numeric" autocomplete="cc-number" maxlength="23" placeholder="0000 0000 0000 0000">
                        </div>
                        <div class="campo">
                            <label class="campo__etiqueta" for="t-mes">Mes</label>
                            <input class="entrada" id="t-mes" data-tarjeta="exp_month" inputmode="numeric" autocomplete="cc-exp-month" maxlength="2" placeholder="MM">
                        </div>
                        <div class="campo">
                            <label class="campo__etiqueta" for="t-anio">Año</label>
                            <input class="entrada" id="t-anio" data-tarjeta="exp_year" inputmode="numeric" autocomplete="cc-exp-year" maxlength="2" placeholder="AA">
                        </div>
                        <div class="campo">
                            <label class="campo__etiqueta" for="t-cvc">CVC</label>
                            <input class="entrada" id="t-cvc" data-tarjeta="cvc" inputmode="numeric" autocomplete="cc-csc" maxlength="4" placeholder="123">
                        </div>
                        <div class="campo plan-tarjeta__titular">
                            <label class="campo__etiqueta" for="t-titular">Nombre como aparece en la tarjeta</label>
                            <input class="entrada" id="t-titular" data-tarjeta="card_holder" autocomplete="cc-name" maxlength="60">
                        </div>
                    </div>
                    <p class="campo__error" data-pago-error role="alert" hidden></p>
                    <button class="boton boton--primario" type="submit" name="accion" value="tarjeta">
                        <i class="fa-solid fa-lock" aria-hidden="true"></i> Pagar <?= e(Plan::pesos($precio)) ?> con tarjeta
                    </button>
                </div>

                <!-- 3b. Checkout de Wompi: pago único -->
                <div class="plan-metodo">
                    <h3><i class="fa-solid fa-building-columns" aria-hidden="true"></i> PSE, Nequi o Botón Bancolombia</h3>
                    <p class="perfil-tarjeta__ayuda">Pago único en la página segura de Wompi. No se renueva solo: te avisaremos unos días antes de que venza.</p>
                    <button class="boton boton--secundario" type="submit" name="accion" value="checkout">
                        Pagar <?= e(Plan::pesos($precio)) ?> en Wompi <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </section>
<?php endif; ?>

<!-- ================= HISTORIAL ================= -->
<section class="perfil-tarjeta plan-historial" aria-labelledby="titulo-historial">
    <h2 id="titulo-historial">Historial de pagos</h2>
    <?php if (empty($historial)): ?>
        <p class="perfil-tarjeta__ayuda">Todavía no tienes pagos.</p>
    <?php else: ?>
        <ul class="plan-pagos">
            <?php foreach ($historial as $h):
                [$txtEstado, $clsEstado] = $estadosPago[$h["estado"]] ?? [$h["estado"], "chip--neutro"]; ?>
                <li class="plan-pago-fila">
                    <div>
                        <p class="plan-pago-fila__titulo">
                            <?= e(Plan::nombre($h["plan"])) ?><?= $h["tipo"] === "renovacion" ? " · renovación" : "" ?>
                        </p>
                        <p class="perfil-tarjeta__ayuda">
                            <?= e(fechaCorta($h["creado_en"])) ?> ·
                            <?= e($metodos[$h["metodo"] ?? ""] ?? ($h["canal"] === "tarjeta" ? "Tarjeta" : "Wompi")) ?>
                            <?php if ($h["tarjeta_ultimos4"]): ?>•••• <?= e($h["tarjeta_ultimos4"]) ?><?php endif; ?>
                            <?php if ($h["estado"] !== "APPROVED" && $h["mensaje"]): ?> · <?= e($h["mensaje"]) ?><?php endif; ?>
                        </p>
                    </div>
                    <div class="plan-pago-fila__monto">
                        <strong><?= e(Plan::pesos((int) $h["centavos"])) ?></strong>
                        <span class="chip <?= $clsEstado ?>"><?= e($txtEstado) ?></span>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<script src="js/pago.js<?= version("estudiante/js/pago.js") ?>"></script>
