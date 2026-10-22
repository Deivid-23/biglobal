<?php
/**
 * Revisión de contenido de los docentes. Variables: $disponible, $pendientes
 * Cada elemento: tipo (curso|leccion), id, titulo, descripcion, contenido, video_url,
 *                estado (1 = ya era visible: es una edición), curso, docente, n_hijos
 */
?>
<header class="pnl-encabezado">
    <div>
        <h1>Revisión de contenido</h1>
        <p>Cursos y lecciones que los docentes enviaron. Aprueba para publicar; si pides cambios o rechazas, escribe un comentario: se lo enviamos al docente.</p>
    </div>
</header>

<?php if (!$disponible): ?>
    <div class="aviso aviso--alerta">
        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
        <span>La revisión aún no está activa: falta aplicar <code>database/revision_contenido.sql</code>.</span>
    </div>
<?php elseif (!$pendientes): ?>
    <div class="vacio">
        <span class="vacio__icono"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span>
        <p class="vacio__titulo">No hay nada por revisar</p>
        <p class="vacio__texto">Cuando un docente envíe un curso o una lección, aparecerá aquí.</p>
    </div>
<?php else: ?>
    <ul class="revision-cola">
        <?php foreach ($pendientes as $p):
            $esCurso = $p["tipo"] === "curso";
            $video = !$esCurso ? Video::reproductor($p["video_url"]) : null;
            $idForm = $p["tipo"] . "-" . (int) $p["id"];
        ?>
            <li class="pnl-tarjeta revision-item" id="revisar-<?= e($idForm) ?>">
                <div class="revision-item__cabecera">
                    <span class="chip chip--neutro"><i class="fa-solid <?= $esCurso ? "fa-book" : "fa-book-open" ?>" aria-hidden="true"></i> <?= $esCurso ? "Curso" : "Lección" ?></span>
                    <?php if ((int) $p["estado"] === 1): ?>
                        <span class="chip chip--alerta">Edición de algo publicado · sigue visible</span>
                    <?php else: ?>
                        <span class="chip chip--neutro">Nuevo · aún no se ve</span>
                    <?php endif; ?>
                </div>
                <h2 class="revision-item__titulo"><?= e($p["titulo"]) ?></h2>
                <p class="col-suave">
                    <?= e(trim($p["docente"] ?? "") ?: "Sin docente") ?>
                    <?php if (!$esCurso): ?> · curso «<?= e($p["curso"]) ?>»<?php endif; ?>
                    · <?= $esCurso ? plural_panel((int) $p["n_hijos"], "lección", "lecciones") : plural_panel((int) $p["n_hijos"], "actividad", "actividades") ?>
                    <?php if ($esCurso && $p["nivel"]): ?> · nivel <?= e($p["nivel"]) ?><?php endif; ?>
                </p>
                <?php if (trim((string) $p["descripcion"]) !== ""): ?>
                    <p class="revision-item__texto"><?= nl2br(e($p["descripcion"])) ?></p>
                <?php endif; ?>
                <?php if (!$esCurso): ?>
                    <details class="revision-item__contenido">
                        <summary>Ver el contenido de la lección</summary>
                        <?php if ($video): ?>
                            <p><i class="fa-solid fa-circle-play" aria-hidden="true"></i> Video en <?= e($video["proveedor"]) ?>:
                                <a href="<?= e($p["video_url"]) ?>" target="_blank" rel="noopener noreferrer"><?= e($p["video_url"]) ?></a></p>
                        <?php endif; ?>
                        <div class="revision-item__texto"><?= trim((string) $p["contenido"]) !== "" ? nl2br(e($p["contenido"])) : '<span class="col-suave">Sin texto.</span>' ?></div>
                        <p><a href="index.php?page=actividades">Ver las actividades</a></p>
                    </details>
                <?php else: ?>
                    <p><a href="index.php?page=lecciones">Ver las lecciones del curso</a></p>
                <?php endif; ?>

                <form class="pnl-form revision-item__form" method="POST" action="index.php">
                    <?= campoCsrf() ?>
                    <input type="hidden" name="accion" value="revisar">
                    <input type="hidden" name="tipo" value="<?= e($p["tipo"]) ?>">
                    <input type="hidden" name="id" value="<?= (int) $p["id"] ?>">
                    <div class="campo">
                        <label class="campo__etiqueta" for="comentario-<?= e($idForm) ?>">Comentario para el docente <span class="campo__ayuda">(obligatorio si pides cambios o rechazas)</span></label>
                        <textarea class="entrada" id="comentario-<?= e($idForm) ?>" name="comentario" rows="2" maxlength="1000"
                                  placeholder="Ej. Agrega ejemplos de pronunciación y corrige la actividad 2."></textarea>
                    </div>
                    <div class="pnl-form__acciones">
                        <button class="boton boton--exito boton--sm" type="submit" name="decision" value="aprobar"><i class="fa-solid fa-check" aria-hidden="true"></i> Aprobar y publicar</button>
                        <button class="boton boton--secundario boton--sm" type="submit" name="decision" value="cambios"><i class="fa-solid fa-pen" aria-hidden="true"></i> Solicitar cambios</button>
                        <button class="boton boton--peligro boton--sm" type="submit" name="decision" value="rechazar"><i class="fa-solid fa-xmark" aria-hidden="true"></i> Rechazar</button>
                    </div>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
