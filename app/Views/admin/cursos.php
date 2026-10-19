<?php
/** Cursos. Variables: $cursos, $porNivel, $modal */
$tipoNuevo = "cursos";
?>

<header class="pnl-encabezado">
    <div>
        <h1>Cursos</h1>
        <p><?= e(plural_panel(count($cursos), "curso en el catálogo", "cursos en el catálogo")) ?>.</p>
    </div>
    <button class="boton boton--primario" type="button" data-abrir-modal="modal-nuevo">
        <i class="fa-solid fa-plus" aria-hidden="true"></i> Nuevo curso
    </button>
</header>

<?= Panel::grafico("grafico-cursos-nivel", "Cursos por nivel", "Distribución del catálogo",
    array_column($porNivel, "etiqueta"), array_column($porNivel, "total"), "bar") ?>

<section class="pnl-tarjeta" aria-labelledby="tabla-cursos">
    <div class="pnl-tarjeta__cabecera"><h2 id="tabla-cursos">Catálogo</h2></div>
    <?php if (empty($cursos)): ?>
        <div class="vacio">
            <span class="vacio__icono"><i class="fa-solid fa-book" aria-hidden="true"></i></span>
            <p class="vacio__titulo">Todavía no hay cursos</p>
            <p class="vacio__texto">Crea el primero con el botón «Nuevo curso».</p>
        </div>
    <?php else: ?>
        <div class="pnl-tabla-caja">
            <table class="pnl-tabla">
                <thead><tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Nivel</th><th>Docente</th><th>Estado</th><th class="col-acciones">Acciones</th></tr></thead>
                <tbody>
                    <?php foreach ($cursos as $cu): ?>
                        <tr>
                            <td class="col-id">#<?= (int) $cu["id"] ?></td>
                            <td class="col-fuerte"><?= e($cu["nombre"]) ?></td>
                            <td class="col-suave pnl-tabla__celda-larga" title="<?= e($cu["descripcion"]) ?>"><?= e($cu["descripcion"] ?: "—") ?></td>
                            <td class="col-suave"><?= e($cu["nivel"] ?: "—") ?></td>
                            <td class="col-suave"><?= e($cu["docente"] ?: "Sin asignar") ?></td>
                            <td><?= (int) $cu["estado"] === 1 ? '<span class="chip chip--exito">Publicado</span>' : '<span class="chip chip--neutro">Borrador</span>' ?></td>
                            <td class="col-acciones">
                                <?php $acciones = [
                                    "nombre" => $cu["nombre"], "modal" => "modal-editar", "action" => "index.php",
                                    "datos" => ["id" => $cu["id"], "nombre" => $cu["nombre"], "descripcion" => $cu["descripcion"],
                                                "nivel" => $cu["nivel"], "instructor_id" => $cu["instructor_id"], "estado" => $cu["estado"]],
                                    "eliminar" => ["accion" => "eliminar", "campos" => ["registro" => "cursos", "id" => $cu["id"]],
                                                   "confirmar" => "¿Eliminar el curso «" . $cu["nombre"] . "» con sus lecciones y actividades? Solo se puede si ningún estudiante ha avanzado."],
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
