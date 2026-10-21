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
                            <?php if ((int) $l["estado"] !== 1): ?><span class="chip chip--neutro">Borrador</span><?php endif; ?>
                            <?php $acciones = [
                                "nombre" => $l["titulo"], "modal" => "modal-editar", "action" => "acciones.php",
                                "datos" => ["leccion_id" => $l["id"], "titulo" => $l["titulo"], "descripcion" => $l["descripcion"],
                                            "contenido" => $l["contenido"], "video_url" => $l["video_url"] ?? null, "orden" => $l["orden"], "estado" => $l["estado"]],
                                "eliminar" => ["accion" => "eliminar_leccion", "campos" => ["leccion_id" => $l["id"]],
                                               "confirmar" => "¿Eliminar la lección «" . $l["titulo"] . "» y sus actividades? Solo se puede si ningún estudiante ha avanzado."],
                            ]; require __DIR__ . "/../panel/_acciones_fila.php"; ?>
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
                <div class="campo">
                    <label class="campo__etiqueta" for="l-video">Video <span class="campo__ayuda">(opcional)</span></label>
                    <input class="entrada" type="url" id="l-video" name="video_url" maxlength="255" inputmode="url" placeholder="https://youtu.be/… o https://vimeo.com/…" aria-describedby="l-video-ayuda">
                    <p class="campo__ayuda" id="l-video-ayuda">Enlace de YouTube o Vimeo. Ideal para Lengua de Señas: el estudiante lo ve arriba del texto.</p>
                </div>
                <div class="campo" style="max-width: 160px;">
                    <label class="campo__etiqueta" for="l-orden">Orden</label>
                    <input class="entrada" type="number" id="l-orden" name="orden" min="1" required value="<?= count($leccionesCurso) + 1 ?>">
                </div>
                <div class="pnl-form__acciones"><button class="boton boton--primario" type="submit">Crear lección</button></div>
            </form>
        </section>
    </div>

    <?php $editarTipo = "leccion"; require __DIR__ . "/_modal_editar.php"; ?>

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
