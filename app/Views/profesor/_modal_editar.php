<?php
/**
 * Modal "Editar" del docente. Se incluye con $editarTipo:
 *   curso | leccion | actividad
 * Envía a acciones.php (que revisa que el contenido sea del docente).
 * Los campos se llenan desde el botón "Editar" (ver panel.js).
 */
$configEditar = [
    "curso"     => ["Editar curso",     "editar_curso",     "curso_id"],
    "leccion"   => ["Editar lección",   "editar_leccion",   "leccion_id"],
    "actividad" => ["Editar actividad", "editar_actividad", "actividad_id"],
];
if (!isset($configEditar[$editarTipo])) return;
[$tituloEditar, $accionEditar, $campoId] = $configEditar[$editarTipo];
?>
<dialog class="pnl-modal" id="modal-editar" aria-labelledby="modal-editar-titulo">
    <form method="POST" action="acciones.php">
        <?= campoCsrf() ?>
        <input type="hidden" name="accion" value="<?= e($accionEditar) ?>">
        <input type="hidden" name="<?= e($campoId) ?>" value="">
        <?php if ($editarTipo !== "curso" && !empty($cursoActual)): ?>
            <input type="hidden" name="curso_id" value="<?= (int) $cursoActual["id"] ?>">
        <?php endif; ?>

        <div class="pnl-modal__cabecera">
            <h2 id="modal-editar-titulo" data-editar-titulo="<?= e($tituloEditar) ?>"><?= e($tituloEditar) ?></h2>
            <button class="pnl-icono-boton" type="button" data-cerrar-modal aria-label="Cerrar">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>

        <div class="pnl-modal__cuerpo">
            <?php if ($editarTipo === "curso"): ?>
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
                        <?php foreach ($idiomasActivos as $idi): ?>
                            <option value="<?= (int) $idi["id"] ?>"><?= e($idi["nombre"]) ?> (<?= e($idi["codigo"]) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="pnl-form__fila">
                    <div class="campo">
                        <label class="campo__etiqueta" for="e-nivel">Nivel</label>
                        <select class="entrada" id="e-nivel" name="nivel" required>
                            <?php foreach (ContenidoModel::NIVELES as $n): ?>
                                <option value="<?= e($n) ?>"><?= e($n) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="campo">
                        <label class="campo__etiqueta" for="e-estado">Estado</label>
                        <select class="entrada" id="e-estado" name="estado">
                            <option value="1">Publicado</option>
                            <option value="0">Borrador (oculto)</option>
                        </select>
                    </div>
                </div>

            <?php elseif ($editarTipo === "leccion"): ?>
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
                    <textarea class="entrada" id="e-contenido" name="contenido" rows="8"></textarea>
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
                            <option value="0">Borrador (oculta)</option>
                        </select>
                    </div>
                </div>

            <?php else: ?>
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
