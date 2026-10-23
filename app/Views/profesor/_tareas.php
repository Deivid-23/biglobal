<?php
/**
 * Pestaña Tareas del grupo (docente).
 * Variables: $grupoActual, $grupoId, $archivado, $base, $tareas, $tareaActual,
 *            $adjuntosTarea, $entregas, $adjuntosEntregas, $acceptArchivo, $tiposArchivo, $csrfToken
 */
$fechaTarea = fn(?string $f): string => $f ? ClaseModel::fechaHora($f) : "Sin fecha límite";

/** Campos de la tarea (crear o editar). */
$camposTarea = function (?array $t) use ($acceptArchivo, $tiposArchivo): void {
    $sufijo = $t ? "-e" : "-n"; ?>
    <div class="campo">
        <label class="campo__etiqueta" for="t-titulo<?= $sufijo ?>">Título</label>
        <input class="entrada" id="t-titulo<?= $sufijo ?>" name="titulo" maxlength="150" required
               value="<?= e($t["titulo"] ?? "") ?>" placeholder="Ej. Graba tu presentación personal">
    </div>
    <div class="campo">
        <label class="campo__etiqueta" for="t-inst<?= $sufijo ?>">Instrucciones <span class="campo__ayuda">(opcional)</span></label>
        <textarea class="entrada" id="t-inst<?= $sufijo ?>" name="instrucciones" rows="4" maxlength="<?= TareaModel::MAX_TEXTO ?>"
                  placeholder="Qué deben hacer y cómo lo vas a calificar"><?= e($t["instrucciones"] ?? "") ?></textarea>
    </div>
    <div class="pnl-form__fila">
        <div class="campo">
            <label class="campo__etiqueta" for="t-fecha<?= $sufijo ?>">Fecha de entrega <span class="campo__ayuda">(opcional, hora de Colombia)</span></label>
            <input class="entrada" type="datetime-local" id="t-fecha<?= $sufijo ?>" name="fecha_entrega"
                   value="<?= !empty($t["fecha_entrega"]) ? e(date("Y-m-d\TH:i", strtotime($t["fecha_entrega"]))) : "" ?>">
        </div>
        <div class="campo">
            <label class="campo__etiqueta" for="t-puntaje<?= $sufijo ?>">Puntaje máximo</label>
            <input class="entrada" type="number" id="t-puntaje<?= $sufijo ?>" name="puntaje_max" min="1" max="1000" required
                   value="<?= (int) ($t["puntaje_max"] ?? 100) ?>">
        </div>
    </div>
    <div class="campo">
        <label class="campo__etiqueta" for="t-adj<?= $sufijo ?>"><?= $t ? "Agregar adjuntos" : "Adjuntos" ?> <span class="campo__ayuda">(opcional)</span></label>
        <input class="entrada" type="file" id="t-adj<?= $sufijo ?>" name="adjuntos[]" multiple accept="<?= e($acceptArchivo) ?>" aria-describedby="t-adj-ayuda<?= $sufijo ?>">
        <p class="campo__ayuda" id="t-adj-ayuda<?= $sufijo ?>"><?= e($tiposArchivo) ?>. Hasta <?= TareaModel::MAX_ARCHIVOS ?> archivos de 5 MB.</p>
    </div>
<?php };
?>

<?php if (!$tareaActual): ?>

    <div class="pnl-rejilla pnl-rejilla--ancha">
        <section class="pnl-tarjeta" aria-labelledby="titulo-tareas">
            <div class="pnl-tarjeta__cabecera"><h2 id="titulo-tareas">Tareas</h2></div>
            <?php if (!$tareas): ?>
                <p class="texto-suave">Todavía no hay tareas. Publica la primera con el formulario.</p>
            <?php else: ?>
                <ul class="pnl-lista">
                    <?php foreach ($tareas as $t): ?>
                        <li class="pnl-item">
                            <span class="pnl-dato__icono" aria-hidden="true"><i class="fa-solid fa-clipboard-list"></i></span>
                            <div class="pnl-item__cuerpo">
                                <p class="pnl-item__titulo"><a href="<?= $base ?>&amp;tab=tareas&amp;tarea=<?= (int) $t["id"] ?>"><?= e($t["titulo"]) ?></a></p>
                                <p class="pnl-item__texto">
                                    <?php if ((int) $t["vencida"] === 1): ?><span class="chip chip--neutro">Venció</span><?php endif; ?>
                                    <?= e($fechaTarea($t["fecha_entrega"])) ?> ·
                                    <?= (int) $t["entregas"] ?> de <?= (int) $grupoActual["miembros"] ?> entregaron
                                    <?php if ((int) $t["por_calificar"] > 0): ?>
                                        · <span class="chip chip--alerta"><?= e(plural_panel((int) $t["por_calificar"], "por calificar", "por calificar")) ?></span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <?php if (!$archivado): ?>
            <section class="pnl-tarjeta" aria-labelledby="titulo-nueva-tarea">
                <div class="pnl-tarjeta__cabecera"><h2 id="titulo-nueva-tarea">Nueva tarea</h2></div>
                <form class="pnl-form" method="POST" action="grupos_acciones.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="accion" value="crear_tarea">
                    <input type="hidden" name="grupo_id" value="<?= $grupoId ?>">
                    <?php $camposTarea(null); ?>
                    <div class="pnl-form__acciones"><button class="boton boton--primario" type="submit">Publicar tarea</button></div>
                </form>
            </section>
        <?php endif; ?>
    </div>

<?php else:
    $tareaId = (int) $tareaActual["id"];
    $max = (int) $tareaActual["puntaje_max"];
    $conEntrega = array_values(array_filter($entregas, fn($en) => $en["id"] !== null));
?>

    <p><a href="<?= $base ?>&amp;tab=tareas"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Todas las tareas</a></p>

    <section class="pnl-tarjeta" aria-labelledby="titulo-tarea">
        <div class="pnl-tarjeta__cabecera">
            <div>
                <h2 id="titulo-tarea"><?= e($tareaActual["titulo"]) ?></h2>
                <p>
                    <?php if ((int) $tareaActual["vencida"] === 1): ?><span class="chip chip--neutro">Venció</span><?php endif; ?>
                    <?= e($fechaTarea($tareaActual["fecha_entrega"])) ?> · <?= $max ?> puntos ·
                    <?= count($conEntrega) ?> de <?= (int) $grupoActual["miembros"] ?> entregaron
                </p>
            </div>
        </div>
        <?php if ($tareaActual["instrucciones"]): ?><p class="grupo-tarea__texto"><?= nl2br(e($tareaActual["instrucciones"])) ?></p><?php endif; ?>
        <?php $listaAdjuntos = $adjuntosTarea; require __DIR__ . "/../grupos/_adjuntos.php"; ?>
    </section>

    <section class="pnl-tarjeta" aria-labelledby="titulo-entregas">
        <div class="pnl-tarjeta__cabecera"><h2 id="titulo-entregas">Entregas</h2></div>
        <?php if (!$entregas): ?>
            <p class="texto-suave">El grupo todavía no tiene estudiantes.</p>
        <?php else: ?>
            <ul class="grupo-entregas">
                <?php foreach ($entregas as $en):
                    $tiene = $en["id"] !== null;
                    $calificada = $tiene && $en["puntaje"] !== null;
                ?>
                    <li class="grupo-entrega<?= $tiene ? "" : " grupo-entrega--vacia" ?>"<?= $tiene ? ' id="entrega-' . (int) $en["id"] . '"' : "" ?>>
                        <div class="grupo-entrega__cabecera">
                            <div>
                                <p class="pnl-item__titulo"><?= e(nombre_panel($en)) ?></p>
                                <p class="pnl-item__texto">
                                    <?php if (!$tiene): ?>
                                        <span class="chip chip--neutro">Sin entregar</span>
                                    <?php else: ?>
                                        <?php if ($calificada): ?>
                                            <span class="chip chip--exito"><?= e(TareaModel::numero($en["puntaje"])) ?>/<?= $max ?></span>
                                        <?php else: ?>
                                            <span class="chip chip--alerta">Por calificar</span>
                                        <?php endif; ?>
                                        <?php if ((int) $en["tardia"] === 1): ?><span class="chip chip--error">Tardía</span><?php endif; ?>
                                        <?= e(ClaseModel::fechaHora($en["enviada_en"])) ?>
                                        <?= (int) $en["envios"] > 1 ? " · enviada " . (int) $en["envios"] . " veces" : "" ?>
                                    <?php endif; ?>
                                    <?php if ((int) $en["ex_miembro"] === 1): ?> · ya no está en el grupo<?php endif; ?>
                                </p>
                            </div>
                        </div>

                        <?php if ($tiene): ?>
                            <?php if ($en["texto"]): ?><p class="grupo-entrega__texto"><?= nl2br(e($en["texto"])) ?></p><?php endif; ?>
                            <?php $listaAdjuntos = $adjuntosEntregas[(int) $en["id"]] ?? []; require __DIR__ . "/../grupos/_adjuntos.php"; ?>

                            <form class="grupo-calificar" method="POST" action="grupos_acciones.php">
                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                <input type="hidden" name="accion" value="calificar">
                                <input type="hidden" name="grupo_id" value="<?= $grupoId ?>">
                                <input type="hidden" name="tarea_id" value="<?= $tareaId ?>">
                                <input type="hidden" name="entrega_id" value="<?= (int) $en["id"] ?>">
                                <div class="campo grupo-calificar__nota">
                                    <label class="campo__etiqueta" for="nota-<?= (int) $en["id"] ?>">Nota (de 0 a <?= $max ?>)</label>
                                    <input class="entrada" id="nota-<?= (int) $en["id"] ?>" name="puntaje" inputmode="decimal" required
                                           pattern="\d{1,4}([.,]\d{1,2})?" value="<?= $calificada ? e(TareaModel::numero($en["puntaje"])) : "" ?>">
                                </div>
                                <div class="campo grupo-calificar__comentario">
                                    <label class="campo__etiqueta" for="com-<?= (int) $en["id"] ?>">Comentario <span class="campo__ayuda">(opcional)</span></label>
                                    <textarea class="entrada" id="com-<?= (int) $en["id"] ?>" name="comentario" rows="2" maxlength="2000"><?= e($en["comentario_docente"] ?? "") ?></textarea>
                                </div>
                                <div class="pnl-form__acciones">
                                    <button class="boton <?= $calificada ? "boton--secundario" : "boton--primario" ?> boton--sm" type="submit"><?= $calificada ? "Actualizar nota" : "Calificar" ?></button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="pnl-tarjeta" id="editar-tarea" aria-labelledby="titulo-editar-tarea">
        <div class="pnl-tarjeta__cabecera"><h2 id="titulo-editar-tarea">Editar tarea</h2></div>
        <form class="pnl-form" method="POST" action="grupos_acciones.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="accion" value="editar_tarea">
            <input type="hidden" name="grupo_id" value="<?= $grupoId ?>">
            <input type="hidden" name="tarea_id" value="<?= $tareaId ?>">
            <?php $camposTarea($tareaActual); ?>
            <?php if ($adjuntosTarea): ?>
                <fieldset class="campo">
                    <legend class="campo__etiqueta">Quitar adjuntos</legend>
                    <?php foreach ($adjuntosTarea as $adj): ?>
                        <label class="grupo-casilla"><input type="checkbox" name="quitar_adjunto[]" value="<?= (int) $adj["id"] ?>"> <span><?= e($adj["nombre"]) ?></span></label>
                    <?php endforeach; ?>
                </fieldset>
            <?php endif; ?>
            <p class="campo__ayuda">Si cambias la fecha, se vuelve a revisar qué entregas llegaron tarde.</p>
            <div class="pnl-form__acciones"><button class="boton boton--primario" type="submit">Guardar cambios</button></div>
        </form>

        <form class="pnl-form" method="POST" action="grupos_acciones.php"
              data-confirmar="¿Eliminar «<?= e($tareaActual["titulo"]) ?>»? También se borran las <?= count($conEntrega) ?> entregas y sus archivos.">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="accion" value="eliminar_tarea">
            <input type="hidden" name="grupo_id" value="<?= $grupoId ?>">
            <input type="hidden" name="tarea_id" value="<?= $tareaId ?>">
            <div class="pnl-form__acciones"><button class="boton boton--peligro" type="submit">Eliminar tarea</button></div>
        </form>
    </section>

<?php endif; ?>
