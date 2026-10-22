<?php
/**
 * Clases en vivo (estudiante).
 * Variables: $disponible, $clases (próximas, con mi_estado, mi_posicion, permitida, enlace_abierto), $premium,
 *            $invitaciones (clases gratuitas a las que lo invitaron y no ha respondido)
 *
 * El enlace nunca está en esta página: "Entrar" pasa por entrar_clase.php,
 * que revisa en el servidor si estás inscrito y si ya es la hora.
 */
$mias = array_values(array_filter($clases, fn($c) => in_array($c["mi_estado"], ["inscrito", "espera"], true)));
$otras = array_values(array_filter($clases, fn($c) => !in_array($c["mi_estado"], ["inscrito", "espera"], true)));

$tarjeta = function (array $cl) {
    $libres = max(0, (int) $cl["cupo"] - (int) $cl["inscritos"]);
    $estado = $cl["mi_estado"];
?>
    <li class="clase<?= $cl["permitida"] ? "" : " clase--bloqueada" ?>" id="clase-<?= (int) $cl["id"] ?>">
        <div class="clase__fecha" aria-hidden="true">
            <span class="clase__dia"><?= e(date("j", strtotime($cl["inicio"]))) ?></span>
            <span class="clase__mes"><?= e(["ene","feb","mar","abr","may","jun","jul","ago","sep","oct","nov","dic"][(int) date("n", strtotime($cl["inicio"])) - 1]) ?></span>
        </div>
        <div class="clase__cuerpo">
            <p class="clase__titulo">
                <?= e($cl["titulo"]) ?>
                <?php if ($cl["idioma_codigo"]): ?><span class="chip chip--neutro"><?= e($cl["idioma_codigo"]) ?></span><?php endif; ?>
                <?php if ((int) $cl["abierta_para_todos"] === 1): ?><span class="chip chip--exito">Abierta para todos</span><?php endif; ?>
                <?php if (!$cl["permitida"]): ?><span class="chip chip--logro"><i class="fa-solid fa-crown" aria-hidden="true"></i> Premium</span><?php endif; ?>
            </p>
            <p class="clase__meta">
                <i class="fa-regular fa-clock" aria-hidden="true"></i> <?= e(ClaseModel::fechaHora($cl["inicio"])) ?> · <?= (int) $cl["duracion_min"] ?> min
                · <i class="fa-solid fa-chalkboard-user" aria-hidden="true"></i> <?= e(trim($cl["docente"])) ?>
            </p>
            <?php if (trim((string) $cl["descripcion"]) !== ""): ?>
                <p class="clase__texto"><?= nl2br(e($cl["descripcion"])) ?></p>
            <?php endif; ?>
            <p class="clase__cupos">
                <?= $libres > 0 ? e(plural_estudiante($libres, "cupo libre", "cupos libres")) . " de " . (int) $cl["cupo"] : "Clase llena · hay lista de espera" ?>
            </p>
        </div>
        <div class="clase__acciones">
            <?php if (!$cl["permitida"] && in_array($estado, ["inscrito", "espera"], true)): ?>
                <!-- Se inscribió siendo Premium y su plan ya no incluye la clase -->
                <span class="clase__nota">Te inscribiste, pero tu plan actual ya no incluye esta clase.</span>
                <a class="boton boton--secundario boton--sm" href="index.php?page=plan">Volver a Premium</a>
                <form method="POST" action="clase.php">
                    <?= campoCsrf() ?>
                    <input type="hidden" name="accion" value="retirar">
                    <input type="hidden" name="clase_id" value="<?= (int) $cl["id"] ?>">
                    <button class="boton boton--fantasma boton--sm" type="submit">Liberar mi cupo</button>
                </form>
            <?php elseif (!$cl["permitida"]): ?>
                <a class="boton boton--secundario boton--sm" href="index.php?page=plan">Ver Premium</a>
            <?php elseif ($estado === "inscrito"): ?>
                <span class="chip chip--exito"><i class="fa-solid fa-check" aria-hidden="true"></i> Inscrito</span>
                <?php if ((int) $cl["enlace_abierto"] === 1): ?>
                    <a class="boton boton--exito boton--sm" href="entrar_clase.php?id=<?= (int) $cl["id"] ?>" target="_blank" rel="noopener noreferrer">
                        <i class="fa-solid fa-video" aria-hidden="true"></i> Entrar a la clase
                    </a>
                <?php else: ?>
                    <span class="clase__nota">El enlace se habilita <?= ClaseModel::MINUTOS_ANTES ?> min antes.</span>
                <?php endif; ?>
                <form method="POST" action="clase.php" data-confirmar="¿Retirarte de «<?= e($cl["titulo"]) ?>»? Tu cupo pasará a la siguiente persona de la lista de espera.">
                    <?= campoCsrf() ?>
                    <input type="hidden" name="accion" value="retirar">
                    <input type="hidden" name="clase_id" value="<?= (int) $cl["id"] ?>">
                    <button class="boton boton--fantasma boton--sm" type="submit">Retirarme</button>
                </form>
            <?php elseif ($estado === "espera"): ?>
                <span class="chip chip--neutro">En espera · puesto <?= (int) $cl["mi_posicion"] ?></span>
                <form method="POST" action="clase.php">
                    <?= campoCsrf() ?>
                    <input type="hidden" name="accion" value="retirar">
                    <input type="hidden" name="clase_id" value="<?= (int) $cl["id"] ?>">
                    <button class="boton boton--fantasma boton--sm" type="submit">Salir de la lista</button>
                </form>
            <?php else: ?>
                <form method="POST" action="clase.php">
                    <?= campoCsrf() ?>
                    <input type="hidden" name="accion" value="inscribir">
                    <input type="hidden" name="clase_id" value="<?= (int) $cl["id"] ?>">
                    <button class="boton <?= $libres > 0 ? "boton--primario" : "boton--secundario" ?> boton--sm" type="submit">
                        <?= $libres > 0 ? "Inscribirme" : "Unirme a la lista de espera" ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </li>
<?php };

function plural_estudiante(int $n, string $uno, string $varios): string
{
    return $n . " " . ($n === 1 ? $uno : $varios);
}
?>

<header class="encabezado-pagina">
    <h1>Clases en vivo</h1>
    <p>Practica con docentes en clases por Meet o Zoom de hasta <?= ClaseModel::CUPO_MAX ?> estudiantes.
        <?= $premium ? "Con tu plan Premium puedes unirte a todas." : "Con el plan Gratuito te unes a las abiertas para todos; con Premium, a todas." ?></p>
</header>

<?php if (!empty($invitaciones)): ?>
    <section class="invitaciones" aria-labelledby="titulo-invitaciones">
        <h2 id="titulo-invitaciones"><i class="fa-solid fa-gift" aria-hidden="true"></i> Te invitaron a <?= count($invitaciones) === 1 ? "una clase gratis" : count($invitaciones) . " clases gratis" ?></h2>
        <ul class="invitaciones__lista">
            <?php foreach ($invitaciones as $inv): $libres = max(0, (int) $inv["cupo"] - (int) $inv["inscritos"]); ?>
                <li class="invitacion">
                    <div>
                        <p class="invitacion__titulo"><?= e($inv["titulo"]) ?></p>
                        <p class="clase__meta">
                            <?= e(ClaseModel::fechaHora($inv["inicio"])) ?> · <?= (int) $inv["duracion_min"] ?> min · <?= e(trim($inv["docente"])) ?>
                            · <?= $libres > 0 ? $libres . " cupos libres" : "llena: entrarías a la lista de espera" ?>
                        </p>
                    </div>
                    <div class="invitacion__acciones">
                        <form method="POST" action="clase.php">
                            <?= campoCsrf() ?>
                            <input type="hidden" name="accion" value="aceptar_invitacion">
                            <input type="hidden" name="clase_id" value="<?= (int) $inv["id"] ?>">
                            <button class="boton boton--primario boton--sm" type="submit">Aceptar</button>
                        </form>
                        <form method="POST" action="clase.php">
                            <?= campoCsrf() ?>
                            <input type="hidden" name="accion" value="rechazar_invitacion">
                            <input type="hidden" name="clase_id" value="<?= (int) $inv["id"] ?>">
                            <button class="boton boton--fantasma boton--sm" type="submit">Rechazar</button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<?php if (!$disponible): ?>
    <div class="vacio"><p>Las clases en vivo llegan muy pronto.</p></div>
<?php elseif (!$clases): ?>
    <div class="vacio">
        <span class="vacio__icono" aria-hidden="true"><i class="fa-solid fa-video"></i></span>
        <p>Por ahora no hay clases programadas. Vuelve pronto: los docentes publican nuevas cada semana.</p>
    </div>
<?php else: ?>
    <?php if ($mias): ?>
        <section aria-labelledby="titulo-mis-clases" class="clases-grupo">
            <h2 id="titulo-mis-clases">Tus clases</h2>
            <ul class="clases"><?php foreach ($mias as $cl) $tarjeta($cl); ?></ul>
        </section>
    <?php endif; ?>
    <?php if ($otras): ?>
        <section aria-labelledby="titulo-proximas" class="clases-grupo">
            <h2 id="titulo-proximas">Próximas clases</h2>
            <ul class="clases"><?php foreach ($otras as $cl) $tarjeta($cl); ?></ul>
        </section>
    <?php endif; ?>
<?php endif; ?>
