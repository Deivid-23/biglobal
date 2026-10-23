<?php
/** Campos de una unidad de la ruta (crear o editar). Variables: $unidadForm, $interruptor */
$sufijo = (int) ($unidadForm["id"] ?? 0);
?>
<div class="campo">
    <label class="campo__etiqueta" for="u-titulo-<?= $sufijo ?>">Título</label>
    <input class="entrada" id="u-titulo-<?= $sufijo ?>" name="titulo" maxlength="120" required value="<?= e($unidadForm["titulo"] ?? "") ?>"
           placeholder="Ej. Saludos y presentaciones">
</div>
<div class="campo">
    <label class="campo__etiqueta" for="u-desc-<?= $sufijo ?>">Descripción <span class="campo__ayuda">(opcional)</span></label>
    <input class="entrada" id="u-desc-<?= $sufijo ?>" name="descripcion" maxlength="255" value="<?= e($unidadForm["descripcion"] ?? "") ?>">
</div>
<div class="pnl-form__fila">
    <div class="campo">
        <label class="campo__etiqueta" for="u-icono-<?= $sufijo ?>">Ícono</label>
        <select class="entrada" id="u-icono-<?= $sufijo ?>" name="icono">
            <?php foreach (RutaModel::ICONOS as $icono): ?>
                <option value="<?= e($icono) ?>" <?= ($unidadForm["icono"] ?? "fa-star") === $icono ? "selected" : "" ?>><?= e(str_replace("fa-", "", $icono)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="campo">
        <label class="campo__etiqueta" for="u-orden-<?= $sufijo ?>">Orden</label>
        <input class="entrada" type="number" id="u-orden-<?= $sufijo ?>" name="orden" min="1" max="999" value="<?= (int) ($unidadForm["orden"] ?? 1) ?>">
    </div>
</div>
<?php $interruptor("publicado", (int) ($unidadForm["publicado"] ?? 0) === 1, "Publicada", "Los estudiantes la ven en su ruta."); ?>
