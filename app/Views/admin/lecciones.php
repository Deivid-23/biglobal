<?php
/** Lecciones. Variables: $lecciones, $porCurso, $modal */
$tipoNuevo = "lecciones";
?>

<header class="pnl-encabezado">
    <div>
        <h1>Lecciones</h1>
        <p><?= e(plural_panel(count($lecciones), "lección", "lecciones")) ?> en todos los cursos.</p>
    </div>
    <button class="boton boton--primario" type="button" data-abrir-modal="modal-nuevo">
        <i class="fa-solid fa-plus" aria-hidden="true"></i> Nueva lección
    </button>
</header>

<?= Panel::grafico("grafico-lecciones-curso", "Lecciones por curso", "Los cursos con más contenido",
    array_column($porCurso, "etiqueta"), array_column($porCurso, "total"), "bar") ?>

<section class="pnl-tarjeta" aria-labelledby="tabla-lecciones">
    <div class="pnl-tarjeta__cabecera"><h2 id="tabla-lecciones">Todas las lecciones</h2></div>
    <?php if (empty($lecciones)): ?>
        <p class="texto-suave">Todavía no hay lecciones.</p>
    <?php else: ?>
        <div class="pnl-tabla-caja">
            <table class="pnl-tabla">
                <thead><tr><th>ID</th><th>Título</th><th>Curso</th><th>Orden</th><th>Estado</th></tr></thead>
                <tbody>
                    <?php foreach ($lecciones as $l): ?>
                        <tr>
                            <td class="col-id">#<?= (int) $l["id"] ?></td>
                            <td class="col-fuerte"><?= e($l["titulo"]) ?></td>
                            <td class="col-suave"><?= e($l["curso"]) ?></td>
                            <td class="col-suave"><?= (int) $l["orden"] ?></td>
                            <td><?= (int) $l["estado"] === 1 ? '<span class="chip chip--exito">Publicada</span>' : '<span class="chip chip--neutro">Borrador</span>' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . "/_modal_nuevo.php"; ?>
