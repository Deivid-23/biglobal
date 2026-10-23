<?php
/**
 * Campos del grupo (crear o editar).
 * Variables: $grupoForm (null al crear), $cursosDocente
 */
?>
<div class="campo">
    <label class="campo__etiqueta" for="g-nombre">Nombre del grupo</label>
    <input class="entrada" id="g-nombre" name="nombre" maxlength="120" required
           value="<?= e($grupoForm["nombre"] ?? "") ?>" placeholder="Ej. Inglés A1 · Sábados">
</div>
<div class="campo">
    <label class="campo__etiqueta" for="g-curso">Curso relacionado <span class="campo__ayuda">(opcional)</span></label>
    <select class="entrada" id="g-curso" name="curso_id" aria-describedby="g-curso-ayuda">
        <option value="">Sin curso</option>
        <?php foreach ($cursosDocente as $cu): ?>
            <option value="<?= (int) $cu["id"] ?>" <?= (int) ($grupoForm["curso_id"] ?? 0) === (int) $cu["id"] ? "selected" : "" ?>><?= e($cu["nombre"]) ?></option>
        <?php endforeach; ?>
    </select>
    <p class="campo__ayuda" id="g-curso-ayuda">Si eliges un curso, verás cuántas lecciones lleva cada estudiante.</p>
</div>
<div class="campo">
    <label class="campo__etiqueta" for="g-desc">Descripción <span class="campo__ayuda">(opcional)</span></label>
    <textarea class="entrada" id="g-desc" name="descripcion" rows="3" maxlength="1000"
              placeholder="Horario, nivel o lo que deben saber al entrar"><?= e($grupoForm["descripcion"] ?? "") ?></textarea>
</div>
