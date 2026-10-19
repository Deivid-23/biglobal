<?php
/** Lecciones del docente. Variables: $cursoActual, $leccionesCurso, $leccionesDocente, $csrfToken */
?>

<?php if ($cursoActual): ?>

    <header class="pnl-encabezado">
        <div>
            <a href="index.php?page=curso&amp;id=<?= (int) $cursoActual["id"] ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> <?= e($cursoActual["nombre"]) ?></a>
            <h1 style="margin-top: var(--esp-2);">Lecciones</h1>
            <p><?= e(plural_panel(count($leccionesCurso), "lección creada", "lecciones creadas")) ?> en este curso.</p>
        </div>
    </header>

    <div class="pnl-rejilla pnl-rejilla--ancha">
        <section class="pnl-tarjeta" aria-labelledby="titulo-lecciones">
            <div class="pnl-tarjeta__cabecera"><h2 id="titulo-lecciones">En orden</h2></div>
            <?php if (empty($leccionesCurso)): ?>
                <p class="texto-suave">Aún no hay lecciones. Crea la primera con el formulario.</p>
            <?php else: ?>
                <ul class="pnl-lista">
                    <?php foreach ($leccionesCurso as $l): ?>
                        <li class="pnl-item">
                            <span class="pnl-dato__icono pnl-dato__icono--verde" aria-hidden="true"><?= (int) $l["orden"] ?></span>
                            <div class="pnl-item__cuerpo">
                                <p class="pnl-item__titulo"><?= e($l["titulo"]) ?></p>
                                <p class="pnl-item__texto"><?= e($l["descripcion"] ?: "Sin descripción") ?></p>
                            </div>
                            <form method="POST" action="acciones.php" data-confirmar="¿Eliminar la lección «<?= e($l["titulo"]) ?>»? También se borran sus actividades.">
                                <input type="hidden" name="accion" value="eliminar_leccion">
                                <input type="hidden" name="leccion_id" value="<?= (int) $l["id"] ?>">
                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                <button class="boton boton--peligro boton--sm" type="submit" aria-label="Eliminar <?= e($l["titulo"]) ?>">
                                    <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                </button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="pnl-tarjeta" aria-labelledby="titulo-nueva">
            <div class="pnl-tarjeta__cabecera"><h2 id="titulo-nueva">Nueva lección</h2></div>
            <form class="pnl-form" method="POST" action="acciones.php">
                <input type="hidden" name="accion" value="crear_leccion">
                <input type="hidden" name="curso_id" value="<?= (int) $cursoActual["id"] ?>">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <div class="campo">
                    <label class="campo__etiqueta" for="l-titulo">Título</label>
                    <input class="entrada" id="l-titulo" name="titulo" maxlength="150" required placeholder="Ej. Saludos y presentaciones">
                </div>
                <div class="campo">
                    <label class="campo__etiqueta" for="l-desc">Descripción</label>
                    <textarea class="entrada" id="l-desc" name="descripcion" rows="2"></textarea>
                </div>
                <div class="campo">
                    <label class="campo__etiqueta" for="l-cont">Contenido</label>
                    <textarea class="entrada" id="l-cont" name="contenido" rows="6" placeholder="El texto que leerán tus estudiantes"></textarea>
                </div>
                <div class="campo" style="max-width: 160px;">
                    <label class="campo__etiqueta" for="l-orden">Orden</label>
                    <input class="entrada" type="number" id="l-orden" name="orden" min="1" required value="<?= count($leccionesCurso) + 1 ?>">
                </div>
                <div class="pnl-form__acciones"><button class="boton boton--primario" type="submit">Crear lección</button></div>
            </form>
        </section>
    </div>

<?php else: ?>

    <header class="pnl-encabezado">
        <div>
            <h1>Lecciones</h1>
            <p><?= e(plural_panel(count($leccionesDocente), "lección", "lecciones")) ?> en todos tus cursos. Para crear una, entra a un curso.</p>
        </div>
    </header>

    <section class="pnl-tarjeta" aria-labelledby="titulo-todas">
        <div class="pnl-tarjeta__cabecera"><h2 id="titulo-todas">Todas tus lecciones</h2></div>
        <?php if (empty($leccionesDocente)): ?>
            <p class="texto-suave">Aún no has creado lecciones. <a href="index.php?page=cursos">Ve a tus cursos</a> para agregar.</p>
        <?php else: ?>
            <div class="pnl-tabla-caja">
                <table class="pnl-tabla">
                    <thead><tr><th>Título</th><th>Curso</th><th>Orden</th><th class="col-acciones"></th></tr></thead>
                    <tbody>
                        <?php foreach ($leccionesDocente as $l): ?>
                            <tr>
                                <td class="col-fuerte"><?= e($l["titulo"]) ?></td>
                                <td class="col-suave"><?= e($l["curso"]) ?></td>
                                <td class="col-suave"><?= (int) $l["orden"] ?></td>
                                <td class="col-acciones"><a href="index.php?page=lecciones&amp;id=<?= (int) $l["curso_id"] ?>">Ver curso</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

<?php endif; ?>
