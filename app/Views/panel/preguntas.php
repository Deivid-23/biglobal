<?php
/**
 * Editor de preguntas de una actividad (admin y docente).
 * Variables: $actividad, $preguntas, $intentos, $volverUrl,
 *            $formAction (a dónde va el POST), $accionGuardar, $accionEliminar
 */
$conPreguntas = QuizModel::tipoConPreguntas($actividad["tipo"]);
$camposBase = ["actividad_id" => (int) $actividad["id"]];
$siguienteOrden = $preguntas ? max(array_map("intval", array_column($preguntas, "orden"))) + 1 : 1;
$formAction = $formAction ?? "index.php";
?>

<header class="pnl-encabezado">
    <div>
        <a href="<?= e($volverUrl) ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Actividades</a>
        <h1 style="margin-top: var(--esp-2);">Preguntas de «<?= e($actividad["titulo"]) ?>»</h1>
        <p>
            <span class="chip chip--neutro"><?= e($actividad["tipo"] ?: "Sin tipo") ?></span>
            <?= e($actividad["leccion"]) ?> · <?= e($actividad["curso"]) ?>
        </p>
    </div>
</header>

<?php if (!$conPreguntas): ?>

    <div class="aviso aviso--alerta">
        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
        <span>Solo las actividades de tipo <?= e(implode(", ", QuizModel::TIPOS_CON_PREGUNTAS)) ?> llevan preguntas.
            Si quieres que esta se pueda resolver, cámbiale el tipo con «Editar».
            <?php if ($preguntas): ?>Sus <?= count($preguntas) ?> preguntas se guardan, pero el estudiante no las ve.<?php endif; ?></span>
    </div>

<?php else: ?>

    <div class="aviso aviso--info">
        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
        <span>El estudiante responde estas preguntas en la lección. Necesita acertar al menos el
            <?= QuizModel::PORCENTAJE_APROBAR ?>&nbsp;% para aprobar, y debe aprobar para terminar la lección.
            <?php if ($intentos > 0): ?>
                Ya hay <?= e(plural_panel($intentos, "intento", "intentos")) ?> de estudiantes: puedes editar las preguntas
                (cuentan para los próximos intentos), pero no borrarlas.
            <?php endif; ?></span>
    </div>

    <section class="pnl-tarjeta" aria-labelledby="titulo-preguntas">
        <div class="pnl-tarjeta__cabecera">
            <h2 id="titulo-preguntas"><?= e(plural_panel(count($preguntas), "pregunta", "preguntas")) ?></h2>
        </div>

        <?php if (empty($preguntas)): ?>
            <p class="texto-suave">Todavía no hay preguntas. Agrega la primera abajo; mientras no tenga ninguna, la actividad se muestra solo como texto.</p>
        <?php else: ?>
            <ol class="pnl-preguntas">
                <?php foreach ($preguntas as $n => $p): ?>
                    <li class="pnl-pregunta">
                        <div class="pnl-pregunta__cabecera">
                            <p class="pnl-pregunta__enunciado"><span class="pnl-pregunta__numero"><?= $n + 1 ?>.</span> <?= nl2br(e($p["enunciado"])) ?></p>
                            <?php if ($intentos === 0): ?>
                                <form method="POST" action="<?= e($formAction) ?>" data-confirmar="¿Borrar la pregunta <?= $n + 1 ?>?">
                                    <?= campoCsrf() ?>
                                    <input type="hidden" name="accion" value="<?= e($accionEliminar) ?>">
                                    <input type="hidden" name="actividad_id" value="<?= (int) $actividad["id"] ?>">
                                    <input type="hidden" name="pregunta_id" value="<?= (int) $p["id"] ?>">
                                    <button class="boton boton--peligro boton--sm" type="submit" aria-label="Borrar la pregunta <?= $n + 1 ?>">
                                        <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <ul class="pnl-pregunta__opciones">
                            <?php foreach ($p["opciones"] as $o): ?>
                                <li class="<?= $o["es_correcta"] ? "es-correcta" : "" ?>">
                                    <i class="fa-solid <?= $o["es_correcta"] ? "fa-circle-check" : "fa-circle" ?>" aria-hidden="true"></i>
                                    <?= e($o["texto"]) ?>
                                    <?php if ($o["es_correcta"]): ?><span class="sr-only">(correcta)</span><?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <details class="pnl-pregunta__editar">
                            <summary>Editar pregunta <?= $n + 1 ?></summary>
                            <?php
                            $pregunta = $p;
                            $prefijo = "p" . (int) $p["id"];
                            $formCampos = $camposBase + ["accion" => $accionGuardar];
                            require __DIR__ . "/_form_pregunta.php";
                            ?>
                        </details>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </section>

    <section class="pnl-tarjeta" aria-labelledby="titulo-nueva-pregunta">
        <div class="pnl-tarjeta__cabecera"><h2 id="titulo-nueva-pregunta">Nueva pregunta</h2></div>
        <?php
        $pregunta = null;
        $prefijo = "nueva";
        $formCampos = $camposBase + ["accion" => $accionGuardar];
        require __DIR__ . "/_form_pregunta.php";
        ?>
    </section>

<?php endif; ?>
