<?php
/**
 * Formulario de una pregunta (nueva o para editar). Lo usa preguntas.php.
 * Variables: $pregunta (null = nueva), $prefijo (para ids únicos),
 *            $formAction, $formCampos (hidden extra), $siguienteOrden
 */
$opcionesForm = $pregunta["opciones"] ?? [];
$correctaForm = 0;
foreach ($opcionesForm as $i => $o) {
    if (!empty($o["es_correcta"])) $correctaForm = $i;
}
?>
<form class="pnl-form pnl-pregunta-form" method="POST" action="<?= e($formAction) ?>">
    <?= campoCsrf() ?>
    <?php foreach ($formCampos as $campo => $valor): ?>
        <input type="hidden" name="<?= e($campo) ?>" value="<?= e($valor) ?>">
    <?php endforeach; ?>
    <?php if ($pregunta): ?>
        <input type="hidden" name="pregunta_id" value="<?= (int) $pregunta["id"] ?>">
    <?php endif; ?>

    <div class="campo">
        <label class="campo__etiqueta" for="<?= e($prefijo) ?>-enunciado">Pregunta</label>
        <textarea class="entrada" id="<?= e($prefijo) ?>-enunciado" name="enunciado" rows="2" maxlength="1000" required
                  placeholder="Ej. ¿Cómo se dice «gato» en inglés?"><?= e($pregunta["enunciado"] ?? "") ?></textarea>
    </div>

    <fieldset class="campo pnl-opciones">
        <legend class="campo__etiqueta">Opciones <span class="campo__ayuda">(marca la correcta; mínimo <?= QuizModel::MIN_OPCIONES ?>)</span></legend>
        <?php for ($i = 0; $i < QuizModel::MAX_OPCIONES; $i++): ?>
            <div class="pnl-opcion">
                <input type="radio" name="correcta" value="<?= $i ?>" id="<?= e($prefijo) ?>-correcta-<?= $i ?>"
                       <?= $i === $correctaForm ? "checked" : "" ?>>
                <label class="sr-only" for="<?= e($prefijo) ?>-correcta-<?= $i ?>">La opción <?= $i + 1 ?> es la correcta</label>
                <label class="sr-only" for="<?= e($prefijo) ?>-opcion-<?= $i ?>">Opción <?= $i + 1 ?></label>
                <input class="entrada" id="<?= e($prefijo) ?>-opcion-<?= $i ?>" name="opciones[<?= $i ?>]" maxlength="255"
                       value="<?= e($opcionesForm[$i]["texto"] ?? "") ?>"
                       placeholder="Opción <?= $i + 1 ?><?= $i >= QuizModel::MIN_OPCIONES ? " (opcional)" : "" ?>"
                       <?= $i < QuizModel::MIN_OPCIONES ? "required" : "" ?>>
            </div>
        <?php endfor; ?>
    </fieldset>

    <div class="campo pnl-campo-corto">
        <label class="campo__etiqueta" for="<?= e($prefijo) ?>-orden">Orden</label>
        <input class="entrada" type="number" min="1" max="999" id="<?= e($prefijo) ?>-orden" name="orden"
               value="<?= (int) ($pregunta["orden"] ?? $siguienteOrden) ?>">
    </div>

    <div class="pnl-form__acciones">
        <button class="boton boton--primario" type="submit">
            <?= $pregunta ? "Guardar cambios" : '<i class="fa-solid fa-plus" aria-hidden="true"></i> Agregar pregunta' ?>
        </button>
    </div>
</form>
