<?php
/**
 * Botones "Editar" y "Eliminar" de una fila (admin y docente).
 *
 * Variable: $acciones = [
 *   "nombre"    => "Inglés A1",                 // para los textos accesibles
 *   "datos"     => [...],                       // valores que rellenan el modal (por nombre de campo)
 *   "modal"     => "modal-editar",              // id del <dialog> de edición
 *   "action"    => "index.php",                 // a dónde va el POST de eliminar
 *   "eliminar"  => ["accion" => "eliminar", "campos" => ["registro" => "cursos", "id" => 5],
 *                   "confirmar" => "¿Eliminar…?"] // o null para no mostrar el botón
 * ]
 * El JS (panel.js) toma data-editar, rellena el formulario del modal y lo abre.
 */
?>
<div class="pnl-item__acciones pnl-acciones-fila">
    <button class="boton boton--secundario boton--sm" type="button"
            data-editar="<?= e(json_encode($acciones["datos"], JSON_UNESCAPED_UNICODE)) ?>"
            data-editar-modal="<?= e($acciones["modal"]) ?>"
            data-editar-nombre="<?= e($acciones["nombre"]) ?>">
        <i class="fa-solid fa-pen" aria-hidden="true"></i> Editar
        <span class="sr-only"><?= e($acciones["nombre"]) ?></span>
    </button>

    <?php if (!empty($acciones["eliminar"])): ?>
        <form method="POST" action="<?= e($acciones["action"]) ?>" data-confirmar="<?= e($acciones["eliminar"]["confirmar"]) ?>">
            <?= campoCsrf() ?>
            <input type="hidden" name="accion" value="<?= e($acciones["eliminar"]["accion"]) ?>">
            <?php foreach ($acciones["eliminar"]["campos"] as $campo => $valor): ?>
                <input type="hidden" name="<?= e($campo) ?>" value="<?= e($valor) ?>">
            <?php endforeach; ?>
            <button class="boton boton--peligro boton--sm" type="submit" aria-label="Eliminar <?= e($acciones["nombre"]) ?>">
                <i class="fa-solid fa-trash" aria-hidden="true"></i>
            </button>
        </form>
    <?php endif; ?>
</div>
