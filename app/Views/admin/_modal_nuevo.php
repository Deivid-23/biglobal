<?php
/**
 * Modal para crear un registro. Se incluye con la variable $tipoNuevo:
 *   usuarios | cursos | idiomas | lecciones | actividades
 * Usa $modal (listas para los selects) que llega desde AdminController.
 * Se abre con un botón: data-abrir-modal="modal-nuevo"
 * $accionModal (opcional): a dónde se envía. Por defecto index.php del admin.
 */
$titulosNuevo = [
    "usuarios"    => "Nuevo usuario",
    "cursos"      => "Nuevo curso",
    "idiomas"     => "Nuevo idioma",
    "lecciones"   => "Nueva lección",
    "actividades" => "Nueva actividad",
];
?>
<dialog class="pnl-modal" id="modal-nuevo" aria-labelledby="modal-nuevo-titulo">
    <form method="POST" action="<?= e($accionModal ?? "index.php") ?>">
        <?= campoCsrf() ?>
        <input type="hidden" name="accion" value="crear">
        <input type="hidden" name="registro" value="<?= e($tipoNuevo) ?>">

        <div class="pnl-modal__cabecera">
            <h2 id="modal-nuevo-titulo"><?= e($titulosNuevo[$tipoNuevo] ?? "Nuevo registro") ?></h2>
            <button class="pnl-icono-boton" type="button" data-cerrar-modal aria-label="Cerrar">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>

        <div class="pnl-modal__cuerpo">
            <?php if ($tipoNuevo === "usuarios"): ?>
                <div class="pnl-form__fila">
                    <div class="campo">
                        <label class="campo__etiqueta" for="n-nombre">Nombre</label>
                        <input class="entrada" id="n-nombre" name="nombre" maxlength="100" required>
                    </div>
                    <div class="campo">
                        <label class="campo__etiqueta" for="n-apellido">Apellido</label>
                        <input class="entrada" id="n-apellido" name="apellido" maxlength="100" required>
                    </div>
                </div>
                <div class="campo">
                    <label class="campo__etiqueta" for="n-correo">Correo</label>
                    <input class="entrada" type="email" id="n-correo" name="correo" maxlength="150" required>
                </div>
                <div class="campo">
                    <label class="campo__etiqueta" for="n-password">Contraseña inicial</label>
                    <input class="entrada" type="password" id="n-password" name="password" autocomplete="new-password" required>
                    <p class="campo__ayuda">
                        Mínimo <?= (int) $modal["reglas"]["minimo"] ?> caracteres<?= $modal["reglas"]["mayuscula"] ? ", una mayúscula" : "" ?><?= $modal["reglas"]["numero"] ? ", un número" : "" ?>.
                        Compártela por un medio seguro.
                    </p>
                </div>
                <div class="campo">
                    <label class="campo__etiqueta" for="n-rol">Rol</label>
                    <select class="entrada" id="n-rol" name="rol_id" required>
                        <?php foreach ($modal["roles"] as $r): ?>
                            <option value="<?= (int) $r["id"] ?>"><?= e(ucfirst($r["nombre"])) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!$esSuper): ?>
                        <p class="campo__ayuda">Solo el super administrador puede crear administradores.</p>
                    <?php endif; ?>
                </div>

            <?php elseif ($tipoNuevo === "cursos"): ?>
                <div class="campo">
                    <label class="campo__etiqueta" for="n-nombre">Nombre del curso</label>
                    <input class="entrada" id="n-nombre" name="nombre" maxlength="150" required>
                </div>
                <div class="campo">
                    <label class="campo__etiqueta" for="n-descripcion">Descripción</label>
                    <textarea class="entrada" id="n-descripcion" name="descripcion" rows="3"></textarea>
                </div>
                <div class="campo">
                    <label class="campo__etiqueta" for="n-idioma">Idioma</label>
                    <select class="entrada" id="n-idioma" name="idioma_id">
                        <option value="">Sin idioma</option>
                        <?php foreach ($modal["idiomas"] as $idi): ?>
                            <option value="<?= (int) $idi["id"] ?>"><?= e($idi["nombre"]) ?> (<?= e($idi["codigo"]) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="pnl-form__fila">
                    <div class="campo">
                        <label class="campo__etiqueta" for="n-nivel">Nivel</label>
                        <select class="entrada" id="n-nivel" name="nivel">
                            <option value="Básico">Básico</option>
                            <option value="Intermedio">Intermedio</option>
                            <option value="Avanzado">Avanzado</option>
                        </select>
                    </div>
                    <div class="campo">
                        <label class="campo__etiqueta" for="n-docente">Docente</label>
                        <select class="entrada" id="n-docente" name="instructor_id">
                            <option value="">Sin asignar</option>
                            <?php foreach ($modal["docentes"] as $d): ?>
                                <option value="<?= (int) $d["id"] ?>"><?= e($d["nombre"]) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

            <?php elseif ($tipoNuevo === "idiomas"): ?>
                <div class="pnl-form__fila">
                    <div class="campo">
                        <label class="campo__etiqueta" for="n-nombre">Nombre</label>
                        <input class="entrada" id="n-nombre" name="nombre" maxlength="50" required>
                    </div>
                    <div class="campo">
                        <label class="campo__etiqueta" for="n-codigo">Código</label>
                        <input class="entrada" id="n-codigo" name="codigo" maxlength="10" placeholder="ES, EN, LSC…" required>
                    </div>
                </div>

            <?php elseif ($tipoNuevo === "lecciones"): ?>
                <?php if (empty($modal["cursos"])): ?>
                    <div class="aviso aviso--alerta"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i><span>Primero crea un curso.</span></div>
                <?php else: ?>
                    <div class="campo">
                        <label class="campo__etiqueta" for="n-curso">Curso</label>
                        <select class="entrada" id="n-curso" name="curso_id" required>
                            <?php foreach ($modal["cursos"] as $c): ?>
                                <option value="<?= (int) $c["id"] ?>"><?= e($c["nombre"]) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="campo">
                        <label class="campo__etiqueta" for="n-titulo">Título</label>
                        <input class="entrada" id="n-titulo" name="titulo" maxlength="150" required>
                    </div>
                    <div class="campo">
                        <label class="campo__etiqueta" for="n-descripcion">Descripción</label>
                        <textarea class="entrada" id="n-descripcion" name="descripcion" rows="2"></textarea>
                    </div>
                    <div class="campo">
                        <label class="campo__etiqueta" for="n-contenido">Contenido</label>
                        <textarea class="entrada" id="n-contenido" name="contenido" rows="4"></textarea>
                    </div>
                    <div class="campo">
                        <label class="campo__etiqueta" for="n-video">Video <span class="campo__ayuda">(opcional)</span></label>
                        <input class="entrada" type="url" id="n-video" name="video_url" maxlength="255" inputmode="url" placeholder="https://youtu.be/… o https://vimeo.com/…" aria-describedby="n-video-ayuda">
                        <p class="campo__ayuda" id="n-video-ayuda">Enlace de YouTube o Vimeo. Ideal para Lengua de Señas: el estudiante lo ve arriba del texto.</p>
                    </div>
                    <div class="campo">
                        <label class="campo__etiqueta" for="n-orden">Orden</label>
                        <input class="entrada" type="number" id="n-orden" name="orden" value="1" min="1" style="max-width: 140px;">
                    </div>
                <?php endif; ?>

            <?php elseif ($tipoNuevo === "actividades"): ?>
                <?php if (empty($modal["lecciones"])): ?>
                    <div class="aviso aviso--alerta"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i><span>Primero crea una lección.</span></div>
                <?php else: ?>
                    <div class="campo">
                        <label class="campo__etiqueta" for="n-leccion">Lección</label>
                        <select class="entrada" id="n-leccion" name="leccion_id" required>
                            <?php foreach ($modal["lecciones"] as $l): ?>
                                <option value="<?= (int) $l["id"] ?>"><?= e($l["nombre"]) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="campo">
                        <label class="campo__etiqueta" for="n-titulo">Título</label>
                        <input class="entrada" id="n-titulo" name="titulo" maxlength="150" required>
                    </div>
                    <div class="campo">
                        <label class="campo__etiqueta" for="n-descripcion">Descripción</label>
                        <textarea class="entrada" id="n-descripcion" name="descripcion" rows="3"></textarea>
                    </div>
                    <div class="campo">
                        <label class="campo__etiqueta" for="n-tipo">Tipo</label>
                        <select class="entrada" id="n-tipo" name="tipo">
                            <?php foreach (ContenidoModel::TIPOS_ACTIVIDAD as $t): ?>
                                <option value="<?= $t ?>"><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="pnl-modal__pie">
            <button type="button" class="boton boton--secundario" data-cerrar-modal>Cancelar</button>
            <button type="submit" class="boton boton--primario">Guardar</button>
        </div>
    </form>
</dialog>
