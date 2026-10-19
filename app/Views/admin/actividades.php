<?php
/** Actividades. Variables: $actividades, $porTipo, $modal */
$tipoNuevo = "actividades";
?>

<header class="pnl-encabezado">
    <div>
        <h1>Actividades</h1>
        <p><?= e(plural_panel(count($actividades), "actividad", "actividades")) ?> en todas las lecciones.</p>
    </div>
    <button class="boton boton--primario" type="button" data-abrir-modal="modal-nuevo">
        <i class="fa-solid fa-plus" aria-hidden="true"></i> Nueva actividad
    </button>
</header>

<?= Panel::grafico("grafico-actividades-tipo", "Actividades por tipo", "Distribución por categoría",
    array_column($porTipo, "etiqueta"), array_column($porTipo, "total"), "bar") ?>

<section class="pnl-tarjeta" aria-labelledby="tabla-actividades">
    <div class="pnl-tarjeta__cabecera"><h2 id="tabla-actividades">Todas las actividades</h2></div>
    <?php if (empty($actividades)): ?>
        <p class="texto-suave">Todavía no hay actividades.</p>
    <?php else: ?>
        <div class="pnl-tabla-caja">
            <table class="pnl-tabla">
                <thead><tr><th>ID</th><th>Título</th><th>Tipo</th><th>Lección</th><th>Descripción</th><th class="col-acciones">Acciones</th></tr></thead>
                <tbody>
                    <?php foreach ($actividades as $a): ?>
                        <tr>
                            <td class="col-id">#<?= (int) $a["id"] ?></td>
                            <td class="col-fuerte"><?= e($a["titulo"]) ?></td>
                            <td><span class="chip chip--neutro"><?= e($a["tipo"] ?: "Sin tipo") ?></span></td>
                            <td class="col-suave"><?= e($a["leccion"]) ?></td>
                            <td class="col-suave pnl-tabla__celda-larga" title="<?= e($a["descripcion"]) ?>"><?= e($a["descripcion"] ?: "—") ?></td>
                            <td class="col-acciones">
                                <?php $acciones = [
                                    "nombre" => $a["titulo"], "modal" => "modal-editar", "action" => "index.php",
                                    "datos" => ["id" => $a["id"], "leccion_id" => $a["leccion_id"], "titulo" => $a["titulo"],
                                                "descripcion" => $a["descripcion"], "tipo" => $a["tipo"]],
                                    "eliminar" => ["accion" => "eliminar", "campos" => ["registro" => "actividades", "id" => $a["id"]],
                                                   "confirmar" => "¿Eliminar la actividad «" . $a["titulo"] . "»?"],
                                ]; require __DIR__ . "/../panel/_acciones_fila.php"; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . "/_modal_nuevo.php"; ?>
<?php require __DIR__ . "/_modal_editar.php"; ?>
