<?php
/**
 * Modal "Editar" del admin. Se incluye con $tipoNuevo:
 *   cursos | idiomas | lecciones | actividades
 * Los campos se llenan desde el botón "Editar" de cada fila
 * (data-editar con los valores; ver panel.js). Usa $modal para los selects.
 */
$titulosEditar = [
    "cursos"      => "Editar curso",
    "idiomas"     => "Editar idioma",
    "lecciones"   => "Editar lección",
    "actividades" => "Editar actividad",
];
if (!isset($titulosEditar[$tipoNuevo])) return;
?>
<dialog class="pnl-modal" id="modal-editar" aria-labelledby="modal-editar-titulo">
    <form method="POST" action="index.php">
        <?= campoCsrf() ?>
        <input type="hidden" name="accion" value="editar">
        <input type="hidden" name="registro" value="<?= e($tipoNuevo) ?>">
        <input type="hidden" name="id" value="">

        <div class="pnl-modal__cabecera">
            <h2 id="modal-editar-titulo" data-editar-titulo="<?= e($titulosEditar[$tipoNuevo]) ?>"><?= e($titulosEditar[$tipoNuevo]) ?></h2>
            <button class="pnl-icono-boton" type="button" data-cerrar-modal aria-label="Cerrar">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>

        <div class="pnl-modal__cuerpo">
            <?php if ($tipoNuevo === "cursos"): ?>
                <div class="campo">
                    <label class="campo__etiqueta" for="e-nombre">Nombre del curso</label>
                    <input class="entrada" id="e-nombre" name="nombre" maxlength="150" required>
                </div>
                <div class="campo">
                    <label class="campo__etiqueta" for="e-descripcion">Descripción</label>
                    <textarea class="entrada" id="e-descripcion" name="descripcion" rows="3"></textarea>
                </div>
                <div class="campo">
                    <label class="campo__etiqueta" for="e-idioma">Idioma</label>
                    <select class="entrada" id="e-idioma" name="idioma_id">
                        <option value="">Sin idioma</option>
                        <?php foreach ($modal["idiomas"] as $idi): ?>
                            <option value="<?= (int) $idi["id"] ?>"><?= e($idi["nombre"]) ?> (<?= e($idi["codigo"]) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="pnl-form__fila">
                    <div class="campo">
                        <label class="campo__etiqueta" for="e-nivel">Nivel</label>
                        <select class="entrada" id="e-nivel" name="nivel">
                            <option value="">Sin nivel</option>
                            <?php foreach (ContenidoModel::NIVELES as $n): ?>
                                <option value="<?= e($n) ?>"><?= e($n) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="campo">
                        <label class="campo__etiqueta" for="e-docente">Docente</label>
                        <select class="entrada" id="e-docente" name="instructor_id">
                            <option value="">Sin asignar</option>
                            <?php foreach ($modal["docentes"] as $d): ?>
                                <option value="<?= (int) $d["id"] ?>"><?= e($d["nombre"]) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="campo">
                    <label class="campo__etiqueta" for="e-estado">Estado</label>
                    <select class="entrada" id="e-estado" name="estado">
                        <option value="1">Publicado: lo ven los estudiantes</option>
                        <option value="0">Borrador: oculto para los estudiantes</option>
                    </select>
                </div>

            <?php elseif ($tipoNuevo === "idiomas"): ?>
                <div class="pnl-form__fila">
                    <div class="campo">
                        <label class="campo__etiqueta" for="e-nombre">Nombre</label>
                        <input class="entrada" id="e-nombre" name="nombre" maxlength="50" required>
                    </div>
                    <div class="campo">
                        <label class="campo__etiqueta" for="e-codigo">Código</label>
                        <input class="entrada" id="e-codigo" name="codigo" maxlength="10" required>
                    </div>
                </div>
                <div class="campo">
                    <label class="campo__etiqueta" for="e-estado">Estado</label>
                    <select class="entrada" id="e-estado" name="estado">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>

            <?php elseif ($tipoNuevo === "lecciones"): ?>
                <div class="campo">
                    <label class="campo__etiqueta" for="e-curso">Curso</label>
                    <select class="entrada" id="e-curso" name="curso_id" required>
                        <?php foreach ($modal["cursos"] as $c): ?>
                            <option value="<?= (int) $c["id"] ?>"><?= e($c["nombre"]) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo">
                    <label class="campo__etiqueta" for="e-titulo">Título</label>
                    <input class="entrada" id="e-titulo" name="titulo" maxlength="150" required>
                </div>
                <div class="campo">
                    <label class="campo__etiqueta" for="e-descripcion">Descripción</label>
                    <textarea class="entrada" id="e-descripcion" name="descripcion" rows="2"></textarea>
                </div>
                <div class="campo">
                    <label class="campo__etiqueta" for="e-contenido">Contenido</label>
                    <textarea class="entrada" id="e-contenido" name="contenido" rows="6"></textarea>
                </div>
                <div class="campo">
                    <label class="campo__etiqueta" for="e-video">Video <span class="campo__ayuda">(opcional)</span></label>
                    <input class="entrada" type="url" id="e-video" name="video_url" maxlength="255" inputmode="url" placeholder="https://youtu.be/… o https://vimeo.com/…" aria-describedby="e-video-ayuda">
                    <p class="campo__ayuda" id="e-video-ayuda">Enlace de YouTube o Vimeo. Ideal para Lengua de Señas: el estudiante lo ve arriba del texto.</p>
                </div>
                <div class="pnl-form__fila">
                    <div class="campo">
                        <label class="campo__etiqueta" for="e-orden">Orden</label>
                        <input class="entrada" type="number" id="e-orden" name="orden" min="1" required>
                    </div>
                    <div class="campo">
                        <label class="campo__etiqueta" for="e-estado">Estado</label>
                        <select class="entrada" id="e-estado" name="estado">
                            <option value="1">Publicada</option>
                            <option value="0">Borrador</option>
                        </select>
                    </div>
                </div>

            <?php elseif ($tipoNuevo === "actividades"): ?>
                <div class="campo">
                    <label class="campo__etiqueta" for="e-leccion">Lección</label>
                    <select class="entrada" id="e-leccion" name="leccion_id" required>
                        <?php foreach ($modal["lecciones"] as $l): ?>
                            <option value="<?= (int) $l["id"] ?>"><?= e($l["nombre"]) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo">
                    <label class="campo__etiqueta" for="e-titulo">Título</label>
                    <input class="entrada" id="e-titulo" name="titulo" maxlength="150" required>
                </div>
                <div class="campo">
                    <label class="campo__etiqueta" for="e-descripcion">Descripción</label>
                    <textarea class="entrada" id="e-descripcion" name="descripcion" rows="3"></textarea>
                </div>
                <div class="campo">
                    <label class="campo__etiqueta" for="e-tipo">Tipo</label>
                    <select class="entrada" id="e-tipo" name="tipo" required>
                        <?php foreach (ContenidoModel::TIPOS_ACTIVIDAD as $t): ?>
                            <option value="<?= e($t) ?>"><?= e($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
        </div>

        <div class="pnl-modal__pie">
            <button type="button" class="boton boton--secundario" data-cerrar-modal>Cancelar</button>
            <button type="submit" class="boton boton--primario">Guardar cambios</button>
        </div>
    </form>
</dialog>
