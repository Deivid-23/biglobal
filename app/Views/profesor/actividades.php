<?php
/** Actividades del docente. Variables: $cursoActual, $leccionesCurso, $actividadesCurso, $actividadesDocente, $numPreguntas, $csrfToken */
?>

<?php if ($cursoActual): ?>

    <header class="pnl-encabezado">
        <div>
            <a href="index.php?page=curso&amp;id=<?= (int) $cursoActual["id"] ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> <?= e($cursoActual["nombre"]) ?></a>
            <h1 style="margin-top: var(--esp-2);">Actividades</h1>
            <p><?= e(plural_panel(count($actividadesCurso), "actividad", "actividades")) ?> en este curso.</p>
        </div>
    </header>

    <div class="pnl-rejilla pnl-rejilla--ancha">
        <section class="pnl-tarjeta" aria-labelledby="titulo-actividades">
            <div class="pnl-tarjeta__cabecera"><h2 id="titulo-actividades">De este curso</h2></div>
            <?php if (empty($actividadesCurso)): ?>
                <p class="texto-suave">Este curso aún no tiene actividades.</p>
            <?php else: ?>
                <ul class="pnl-lista">
                    <?php foreach ($actividadesCurso as $a): ?>
                        <li class="pnl-item">
                            <span class="pnl-dato__icono pnl-dato__icono--naranja" aria-hidden="true"><i class="fa-solid fa-list-check"></i></span>
                            <div class="pnl-item__cuerpo">
                                <p class="pnl-item__titulo"><?= e($a["titulo"]) ?></p>
                                <p class="pnl-item__texto"><span class="chip chip--neutro"><?= e($a["tipo"] ?: "Sin tipo") ?></span> <?= e($a["leccion"]) ?></p>
                            </div>
                            <?php if ($numPreguntas !== null && QuizModel::tipoConPreguntas($a["tipo"])): ?>
                                <a class="boton boton--secundario boton--sm" href="index.php?page=preguntas&amp;actividad=<?= (int) $a["id"] ?>">
                                    <i class="fa-solid fa-circle-question" aria-hidden="true"></i> Preguntas (<?= (int) ($numPreguntas[(int) $a["id"]] ?? 0) ?>)
                                    <span class="sr-only">de <?= e($a["titulo"]) ?></span>
                                </a>
                            <?php endif; ?>
                            <?php $acciones = [
                                "nombre" => $a["titulo"], "modal" => "modal-editar", "action" => "acciones.php",
                                "datos" => ["actividad_id" => $a["id"], "titulo" => $a["titulo"], "descripcion" => $a["descripcion"], "tipo" => $a["tipo"]],
                                "eliminar" => ["accion" => "eliminar_actividad", "campos" => ["actividad_id" => $a["id"], "curso_id" => $cursoActual["id"]],
                                               "confirmar" => "¿Eliminar la actividad «" . $a["titulo"] . "»?"],
                            ]; require __DIR__ . "/../panel/_acciones_fila.php"; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="pnl-tarjeta" aria-labelledby="titulo-nueva">
            <div class="pnl-tarjeta__cabecera"><h2 id="titulo-nueva">Nueva actividad</h2></div>
            <?php if (empty($leccionesCurso)): ?>
                <div class="aviso aviso--alerta">
                    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                    <span>Este curso aún no tiene lecciones. <a href="index.php?page=lecciones&amp;id=<?= (int) $cursoActual["id"] ?>">Crea una lección</a> primero.</span>
                </div>
            <?php else: ?>
                <form class="pnl-form" method="POST" action="acciones.php">
                    <input type="hidden" name="accion" value="crear_actividad">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <div class="campo">
                        <label class="campo__etiqueta" for="a-leccion">Lección</label>
                        <select class="entrada" id="a-leccion" name="leccion_id" required>
                            <option value="">Elige una lección</option>
                            <?php foreach ($leccionesCurso as $l): ?>
                                <option value="<?= (int) $l["id"] ?>">#<?= (int) $l["orden"] ?> — <?= e($l["titulo"]) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="campo">
                        <label class="campo__etiqueta" for="a-titulo">Título</label>
                        <input class="entrada" id="a-titulo" name="titulo" maxlength="150" required placeholder="Ej. Cuestionario de vocabulario">
                    </div>
                    <div class="campo">
                        <label class="campo__etiqueta" for="a-desc">Descripción</label>
                        <textarea class="entrada" id="a-desc" name="descripcion" rows="3"></textarea>
                    </div>
                    <div class="campo">
                        <label class="campo__etiqueta" for="a-tipo">Tipo</label>
                        <select class="entrada" id="a-tipo" name="tipo" required>
                            <option value="">Elige un tipo</option>
                            <?php foreach (ContenidoModel::TIPOS_ACTIVIDAD as $t): ?>
                                <option value="<?= e($t) ?>"><?= e($t) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="pnl-form__acciones"><button class="boton boton--primario" type="submit">Crear actividad</button></div>
                </form>
            <?php endif; ?>
        </section>
    </div>

    <?php $editarTipo = "actividad"; require __DIR__ . "/_modal_editar.php"; ?>

<?php else: ?>

    <header class="pnl-encabezado">
        <div>
            <h1>Actividades</h1>
            <p><?= e(plural_panel(count($actividadesDocente), "actividad", "actividades")) ?> en todos tus cursos. Para crear una, entra a un curso.</p>
        </div>
    </header>

    <section class="pnl-tarjeta" aria-labelledby="titulo-todas">
        <div class="pnl-tarjeta__cabecera"><h2 id="titulo-todas">Todas tus actividades</h2></div>
        <?php if (empty($actividadesDocente)): ?>
            <p class="texto-suave">Aún no has creado actividades. <a href="index.php?page=cursos">Entra a un curso</a> para agregar.</p>
        <?php else: ?>
            <div class="pnl-tabla-caja">
                <table class="pnl-tabla">
                    <thead><tr><th>Título</th><th>Tipo</th><th>Lección</th><th>Curso</th><?php if ($numPreguntas !== null): ?><th>Preguntas</th><?php endif; ?></tr></thead>
                    <tbody>
                        <?php foreach ($actividadesDocente as $a): ?>
                            <tr>
                                <td class="col-fuerte"><?= e($a["titulo"]) ?></td>
                                <td><span class="chip chip--neutro"><?= e($a["tipo"] ?: "—") ?></span></td>
                                <td class="col-suave"><?= e($a["leccion"]) ?></td>
                                <td class="col-suave"><?= e($a["curso"]) ?></td>
                                <?php if ($numPreguntas !== null): ?>
                                    <td>
                                        <?php if ($numPreguntas !== null && QuizModel::tipoConPreguntas($a["tipo"])): ?>
                                            <a class="boton boton--secundario boton--sm" href="index.php?page=preguntas&amp;actividad=<?= (int) $a["id"] ?>">
                                                <i class="fa-solid fa-circle-question" aria-hidden="true"></i> Preguntas (<?= (int) ($numPreguntas[(int) $a["id"]] ?? 0) ?>)
                                                <span class="sr-only">de <?= e($a["titulo"]) ?></span>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

<?php endif; ?>
