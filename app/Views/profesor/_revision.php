<?php
/**
 * Estado de revisión de un curso o una lección del docente, con sus botones.
 * Variables: $revTipo ("curso" | "leccion"), $revFila (fila con id, revision, revision_comentario, estado),
 *            $csrfToken, $revVolver (página a la que vuelve)
 */
$rev = $revFila["revision"] ?? null;
if ($rev === null) {
    return;   // aún no se aplicó database/revision_contenido.sql
}
$idCampo = $revTipo === "curso" ? "curso_id" : "leccion_id";
?>
<div class="revision" data-revision="<?= e($rev) ?>">
    <?= RevisionModel::chip($rev) ?>
    <?php if ($rev === "en_revision" && (int) $revFila["estado"] === 1): ?>
        <span class="revision__nota">Visible mientras se revisan tus cambios</span>
    <?php endif; ?>

    <?php if (in_array($rev, ["borrador", "cambios", "rechazado"], true)): ?>
        <form method="POST" action="acciones.php">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="accion" value="enviar_revision">
            <input type="hidden" name="tipo" value="<?= e($revTipo) ?>">
            <input type="hidden" name="<?= $idCampo ?>" value="<?= (int) $revFila["id"] ?>">
            <input type="hidden" name="volver" value="<?= e($revVolver) ?>">
            <button class="boton boton--primario boton--sm" type="submit"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Enviar a revisión</button>
        </form>
    <?php endif; ?>

    <?php if ($rev !== "borrador"): ?>
        <form method="POST" action="acciones.php"
              data-confirmar="¿Pasar a borrador? Deja de verse para los estudiantes hasta que lo vuelvan a aprobar.">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="accion" value="a_borrador">
            <input type="hidden" name="tipo" value="<?= e($revTipo) ?>">
            <input type="hidden" name="<?= $idCampo ?>" value="<?= (int) $revFila["id"] ?>">
            <input type="hidden" name="volver" value="<?= e($revVolver) ?>">
            <button class="boton boton--fantasma boton--sm" type="submit">Pasar a borrador</button>
        </form>
    <?php endif; ?>
</div>
<?php if (in_array($rev, ["cambios", "rechazado"], true) && !empty($revFila["revision_comentario"])): ?>
    <p class="revision__comentario">
        <i class="fa-solid fa-comment" aria-hidden="true"></i>
        <strong>Comentario del equipo:</strong> <?= e($revFila["revision_comentario"]) ?>
    </p>
<?php endif; ?>
